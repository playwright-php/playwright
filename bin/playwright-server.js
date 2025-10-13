const {chromium, firefox, webkit} = require('playwright');
const { logger, ErrorHandler, LspFraming, sendFramedResponse, CommandRegistry, BaseHandler } = require('./lib/core');
const { ContextHandler, PageHandler, LocatorHandler, InteractionHandler, FrameHandler, SelectorsHandler } = require('./lib/handlers');
const { globalCoordinator } = require('./lib/coordination');

class PlaywrightServer extends BaseHandler {
  constructor() {
    super();
    this.initMaps();
    this.initHandlers();
  }

  initMaps() {
    this.browsers = new Map();
    this.contexts = new Map();
    this.pages = new Map();
    this.pageContexts = new Map();
    this.responses = new Map();
    this.routes = new Map();
    this.dialogs = new Map();
    this.elementHandles = new Map();
    this.contextThrottling = new Map();
    this.servers = new Map();
    this.counters = { browser: 0, context: 0, page: 0, response: 0, route: 0, element: 0, server: 0 };
  }

  initHandlers() {
    const deps = {
      contexts: this.contexts, contextThrottling: this.contextThrottling, pages: this.pages,
      pageContexts: this.pageContexts, dialogs: this.dialogs, elementHandles: this.elementHandles,
      responses: this.responses, routes: this.routes, generateId: this.generateId.bind(this),
      extractRequestData: this.extractRequestData.bind(this), serializeResponse: this.serializeResponse.bind(this),
      sendFramedResponse, routeCounter: { value: this.counters.route },
      setupPageEventListeners: this.setupPageEventListeners.bind(this)
    };
    this.contextHandler = new ContextHandler(deps);
    this.pageHandler = new PageHandler(deps);
    this.locatorHandler = new LocatorHandler(deps);
    this.interactionHandler = new InteractionHandler(deps);
    this.frameHandler = new FrameHandler(deps);
    this.selectorsHandler = new SelectorsHandler(deps);
  }

  async handleCommand(command) {
    const { requestId } = command;
    try {
      const result = await this.dispatch(command);
      return { requestId, ...this.wrapResult(result) };
    } catch (error) {
      return ErrorHandler.formatError(error, command, requestId);
    }
  }

  async dispatch(command) {
    logger.info('Dispatching command', { action: command.action, pageId: command.pageId, selector: command.selector });
    
    // Check if this is a callback continuation
    if (command.action === 'callback.continue') {
      return await this.handleCallbackContinuation(command);
    }
    
    const [actionPrefix, actionMethod] = command.action.split('.');

    const handlerRegistry = CommandRegistry.create({
      context: () => this.contextHandler.handle(command, actionMethod),
      page: () => this.pageHandler.handle(command, actionMethod),
      locator: () => this.locatorHandler.handle(command, actionMethod),
      route: () => this.handleRoute(command, actionMethod),
      response: () => this.handleResponse(command, actionMethod),
      mouse: () => this.interactionHandler.handleMouse(command, actionMethod),
      keyboard: () => this.interactionHandler.handleKeyboard(command, actionMethod),
      touchscreen: () => this.interactionHandler.handleTouchscreen(command, actionMethod),
      frame: () => this.frameHandler.handle(command, actionMethod),
      browserServer: () => this.handleBrowserServer(command, actionMethod),
      selectors: () => this.selectorsHandler.handle(command, actionMethod)
    });

    if (handlerRegistry.has(actionPrefix)) {
      return await ErrorHandler.safeExecute(() => this.executeWithRegistry(handlerRegistry, actionPrefix), { action: command.action });
    }

    const directRegistry = CommandRegistry.create({
      launch: () => this.launchBrowser(command),
      // Attach to existing browser servers/endpoints
      connect: () => this.connect(command),
      connectOverCDP: () => this.connectOverCDP(command),
      newContext: () => this.newContext(command),
      close: () => this.closeBrowser(command),
      exit: () => this.exit(),
      launchServer: () => this.launchServer(command)
    });

    return await ErrorHandler.safeExecute(() => this.executeWithRegistry(directRegistry, command.action), { action: command.action });
  }

  async handleRoute(command, method) {
    const info = this.validateResource(this.routes, command.routeId, 'Route');
    const route = info.route || info;
    const registry = CommandRegistry.create({
      fulfill: () => route.fulfill(command.options),
      abort: () => route.abort(command.errorCode),
      continue: () => this.continueRoute(route, info, command)
    });
    logger.info(`ROUTE ${method.toUpperCase()}`, { routeId: command.routeId });
    await ErrorHandler.safeExecute(() => this.executeWithRegistry(registry, method), { method, routeId: command.routeId });
    this.routes.delete(command.routeId);
  }

  async continueRoute(route, info, command) {
    try {
      const throttle = this.contextThrottling.get(info.contextId);
      if (throttle?.latency > 0) await new Promise(r => setTimeout(r, throttle.latency));
    } catch {}
    await route.continue(command.options || undefined);
  }

  async handleResponse(command, method) {
    if (method !== 'body') throw new Error(`Unknown response action: ${method}`);
    const res = this.validateResource(this.responses, command.responseId, 'Response');
    const buf = await res.body();
    return { binary: Buffer.from(buf).toString('base64') };
  }

  setupPageEventListeners(page, pageId) {
    ['console', 'dialog', 'request', 'response', 'requestfailed'].forEach(eventName => {
      page.on(eventName, ErrorHandler.wrapHandler((eventData) => {
        const params = this.formatEventParams(eventName, eventData);
        sendFramedResponse({ objectId: pageId, event: eventName, params });
      }));
    });
  }

  formatEventParams(eventName, eventData) {
    const formatters = {
      console: () => ({ type: eventData.type(), text: eventData.text(), args: [], location: eventData.location ? eventData.location() : {} }),
      dialog: () => {
        const dialogId = this.generateId('dialog');
        this.dialogs.set(dialogId, eventData);
        return { dialogId, type: eventData.type(), message: eventData.message(), defaultValue: eventData.defaultValue ? eventData.defaultValue() : null };
      },
      request: () => ({ request: this.extractRequestData(eventData) }),
      requestfailed: () => ({ request: this.extractRequestData(eventData) }),
      response: () => ({ response: this.serializeResponse(eventData) })
    };
    return formatters[eventName] ? formatters[eventName]() : {};
  }

  generateId(prefix) {
    return prefix === 'dialog' ? `dialog_${Date.now()}_${Math.floor(Math.random() * 1000)}` : `${prefix}_${++this.counters[prefix]}`;
  }

  extractRequestData(req) {
    let postData = null;
    try { postData = req.postData(); } catch {}
    return { url: req.url(), method: req.method(), headers: req.headers(), postData: postData ?? null, resourceType: req.resourceType ? req.resourceType() : 'document' };
  }

  serializeResponse(response) {
    if (!response) return null;
    const responseId = this.generateId('response');
    this.responses.set(responseId, response);
    return { responseId, url: response.url(), status: response.status(), statusText: response.statusText(), headers: response.headers() };
  }

  async launchBrowser(command) {
    try {
      const browserType = this.getBrowserType(command.browser);
      const launchOptions = this.buildLaunchOptions(command);
      const browser = await browserType.launch(launchOptions);
      const browserId = this.generateId('browser');
      this.browsers.set(browserId, browser);
      const context = await browser.newContext();
      const contextId = this.generateId('context');
      this.contexts.set(contextId, { context, browserId, id: contextId });
      return { browserId, defaultContextId: contextId, version: browser.version() };
    } catch (error) {
      logger.error('launchBrowser error', { message: error.message });
      throw error;
    }
  }

  getBrowserType(browserName) {
    const types = { firefox, webkit, chromium };
    return types[browserName] || chromium;
  }

  buildLaunchOptions(command) {
    const options = { headless: true, ...command.options };
    if (command?.options?.env?.PWDEBUG) process.env.PWDEBUG = String(command.options.env.PWDEBUG);
    if (command.browser === 'chromium' || !command.browser) {
      options.args = [
        '--no-sandbox', '--disable-dev-shm-usage', '--disable-web-security', '--disable-features=VizDisplayCompositor',
        '--disable-background-timer-throttling', '--disable-backgrounding-occluded-windows', '--disable-renderer-backgrounding',
        '--disable-field-trial-config', '--disable-ipc-flooding-protection', '--disable-popup-blocking', ...(options.args || [])
      ];
    }
    return options;
  }

  async newContext(command) {
    const browser = this.validateResource(this.browsers, command.browserId, 'Browser');
    const context = await browser.newContext(command.options || {});
    const contextId = this.generateId('context');
    this.contexts.set(contextId, { context, browserId: command.browserId, id: contextId });
    return { contextId };
  }

  async closeBrowser(command) {
    const browser = this.browsers.get(command.browserId);
    if (!browser) return;
    await ErrorHandler.safeExecute(async () => {
      await browser.close();
      this.browsers.delete(command.browserId);
      this.cleanupBrowserResources(command.browserId);
    }, { action: 'closeBrowser', browserId: command.browserId });
  }

  cleanupBrowserResources(browserId) {
    for (const [contextId, contextInfo] of this.contexts.entries()) {
      if (contextInfo.browserId === browserId) this.contexts.delete(contextId);
    }
    for (const [pageId, page] of this.pages.entries()) {
      try {
        if (page.context().browser() === this.browsers.get(browserId)) this.pages.delete(pageId);
      } catch { this.pages.delete(pageId); }
    }
  }

  async handleCallbackContinuation(command) {
    const { requestId, callbackResult } = command;
    
    logger.info('Handling callback continuation', { requestId, callbackResult });
    
    try {
      const result = await globalCoordinator.continueAfterCallback(requestId, callbackResult || {});
      
      if (result.completed) {
        logger.info('Callback continuation completed', { requestId });
        return result.result;
      }
      
      // Should not happen - continuation should always complete or error
      logger.warn('Callback continuation did not complete', { requestId });
      return { error: 'Callback continuation did not complete' };
      
    } catch (error) {
      logger.error('Callback continuation failed', { requestId, error: error.message });
      return { error: error.message };
    }
  }

  async handleBrowserServer(command, method) {
    const server = this.servers.get(command.serverId);
    if (!server) throw new Error(`Unknown BrowserServer: ${command.serverId}`);
    const registry = CommandRegistry.create({
      close: async () => { await server.close(); this.servers.delete(command.serverId); },
      kill: async () => { try { await server.kill(); } finally { this.servers.delete(command.serverId); } }
    });
    logger.info(`BROWSER_SERVER ${method.toUpperCase()}`, { serverId: command.serverId });
    return await ErrorHandler.safeExecute(() => this.executeWithRegistry(registry, method), { method, serverId: command.serverId });
  }

  async launchServer(command) {
    try {
      const browserType = this.getBrowserType(command.browser);
      const launchOptions = this.buildLaunchOptions(command);
      const server = await browserType.launchServer(launchOptions);
      const serverId = this.generateId('server');
      this.servers.set(serverId, server);

      // Auto-cleanup on server close and notify
      try {
        server.on?.('close', () => {
          if (this.servers.has(serverId)) this.servers.delete(serverId);
          try { sendFramedResponse({ objectId: serverId, event: 'close', params: {} }); } catch {}
        });
      } catch {}

      const wsEndpoint = server.wsEndpoint();
      let pid = null;
      try { pid = server.process() ? server.process().pid : null; } catch {}
      return { serverId, wsEndpoint, pid };
    } catch (error) {
      logger.error('launchServer error', { message: error.message });
      throw error;
    }
  }

  // Attach to an existing BrowserServer (WebSocket endpoint)
  async connect(command) {
    try {
      const { browser, wsEndpoint, options } = command;
      if (!wsEndpoint || typeof wsEndpoint !== 'string') throw new Error('connect requires a wsEndpoint string');
      const browserType = this.getBrowserType(browser);
      const attached = await browserType.connect(wsEndpoint, options || {});
      const browserId = this.generateId('browser');
      this.browsers.set(browserId, attached);
      // Create a fresh context to align with launch return shape
      const context = await attached.newContext();
      const contextId = this.generateId('context');
      this.contexts.set(contextId, { context, browserId, id: contextId });
      return { browserId, defaultContextId: contextId, version: attached.version() };
    } catch (error) {
      logger.error('connect error', { message: error.message });
      throw error;
    }
  }

  // Attach over CDP (Chromium only)
  async connectOverCDP(command) {
    try {
      const { endpointURL, options, browser } = command;
      if (!endpointURL || typeof endpointURL !== 'string') throw new Error('connectOverCDP requires an endpointURL string');
      const browserName = browser || 'chromium';
      if (browserName !== 'chromium') throw new Error('connectOverCDP is only supported for chromium');
      const attached = await chromium.connectOverCDP(endpointURL, options || {});
      const browserId = this.generateId('browser');
      this.browsers.set(browserId, attached);
      const context = await attached.newContext();
      const contextId = this.generateId('context');
      this.contexts.set(contextId, { context, browserId, id: contextId });
      return { browserId, defaultContextId: contextId, version: attached.version() };
    } catch (error) {
      logger.error('connectOverCDP error', { message: error.message });
      throw error;
    }
  }

  async exit() {
    logger.info('Shutting down server');
    for (const browser of this.browsers.values()) {
      try { await browser.close(); } catch (e) { logger.warn('Error closing browser during exit', { error: e.message }); }
    }
    logger.close();
    process.exit(0);
  }
}

const server = new PlaywrightServer();
let inputBuffer = Buffer.alloc(0);

sendFramedResponse({ type: 'ready', message: 'READY' });

process.stdin.on('data', ErrorHandler.wrapHandler(async (chunk) => {
  inputBuffer = Buffer.concat([inputBuffer, chunk]);
  try {
    const decoded = LspFraming.decode(inputBuffer);
    inputBuffer = decoded.remainingBuffer;
    for (const messageContent of decoded.messages) {
      if (!messageContent.trim()) continue;
      let command;
      try {
        command = JSON.parse(messageContent);
        const result = await server.handleCommand(command);
        if (result) sendFramedResponse(result);
      } catch (error) {
        logger.error('SERVER ERROR', { error: error.message, stack: error.stack, command: messageContent });
        const errorResponse = { error: error.message, parseError: true };
        if (command?.requestId) errorResponse.requestId = command.requestId;
        sendFramedResponse(errorResponse);
      }
    }
  } catch (error) {
    logger.error('LSP FRAMING ERROR', { error: error.message, stack: error.stack });
    sendFramedResponse({ error: 'LSP framing error: ' + error.message });
  }
}));
