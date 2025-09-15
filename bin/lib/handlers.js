const { logger, ErrorHandler, CommandRegistry, BaseHandler, PromiseUtils, FrameUtils, RouteUtils } = require('./core');
const { globalCoordinator } = require('./coordination');
const { PopupCoordinator } = require('./popup-coordinator');

class ContextHandler extends BaseHandler {
  async handle(command, method) {
    const context = this.validateResource(this.contexts, command.contextId, 'Context')?.context;
    if (!context._initScriptPromise) context._initScriptPromise = Promise.resolve();

    const registry = CommandRegistry.create({
      addInitScript: () => context.addInitScript(command.script),
      setOffline: () => context.setOffline(!!command.offline),
      setGeolocation: () => context.setGeolocation(command.geolocation),
      addCookies: () => context.addCookies(command.cookies),
      clearCookies: () => context.clearCookies(),
      grantPermissions: () => context.grantPermissions(command.permissions, command.origin ? { origin: command.origin } : undefined),
      clearPermissions: () => context.clearPermissions(),
      startTracing: () => context.tracing.start(command.options || {}),
      stopTracing: () => context.tracing.stop({ path: command.path }),
      waitForEvent: async () => {
        // Special-case 'page' event to return a serializable payload
        if (command.event === 'page') {
          const popup = await context.waitForEvent('page', { timeout: command.timeout });
          const popupPageId = this.generateId('page');
          this.pages.set(popupPageId, popup);
          this.pageContexts.set(popupPageId, command.contextId);
          if (this.setupPageEventListeners) {
            this.setupPageEventListeners(popup, popupPageId);
          }
          return { popupPageId };
        }
        // For other events, return a basic value wrapper
        const value = await context.waitForEvent(command.event, { timeout: command.timeout });
        return { value: value ?? null };
      },
      waitForPopup: () => this.waitForPopup(context, command),
      setNetworkThrottling: () => this.setThrottling(command),
      route: () => RouteUtils.setupContextRoute(context, command, this.generateId, this.routes, this.extractRequestData, this.sendFramedResponse),
      unroute: () => context.unroute(command.url),
      cookies: async () => ({ cookies: await context.cookies(command.urls) }),
      storageState: async () => ({ storageState: await context.storageState(command.options) }),
      clipboardText: () => this.getClipboardText(context),
      newPage: () => this.createNewPage(context, command)
    });

    const result = await ErrorHandler.safeExecute(() => this.executeWithRegistry(registry, method), { method, contextId: command.contextId });
    return this.wrapResult(result);
  }

  setThrottling(command) {
    if (command.throttling && typeof command.throttling === 'object') {
      this.contextThrottling.set(command.contextId, {
        latency: Number(command.throttling.latency) || 0,
        downloadThroughput: Number(command.throttling.downloadThroughput) || 0,
        uploadThroughput: Number(command.throttling.uploadThroughput) || 0,
      });
    }
  }

  async getClipboardText(context) {
    const pages = context.pages();
    if (pages.length === 0) throw new Error('No pages available in context to read clipboard');
    const value = await pages[0].evaluate(() => navigator.clipboard.readText());
    return this.createValueResult(value);
  }

  async createNewPage(context, command) {
    const page = await context.newPage();
    const pageId = this.generateId('page');
    this.pages.set(pageId, page);
    this.pageContexts.set(pageId, command.contextId);

    if (this.setupPageEventListeners) {
      this.setupPageEventListeners(page, pageId);
    }

    return { pageId };
  }

  async waitForPopup(context, command) {
    const timeout = command.timeout || 30000;
    const requestId = command.requestId || this.generateId('popup_req');
    
    logger.info('Starting context popup coordination', { 
      contextId: command.contextId, 
      timeout, 
      requestId 
    });
    
    // Create coordination phases
    const phases = PopupCoordinator.createContextPopupPhases({
      pages: this.pages,
      pageContexts: this.pageContexts,
      setupPageEventListeners: this.setupPageEventListeners?.bind(this),
      generateId: this.generateId.bind(this)
    });
    
    // Register the async command
    globalCoordinator.registerAsyncCommand(requestId, phases);
    
    try {
      // Start execution with initial data
      const result = await globalCoordinator.executeNextPhase(requestId, {
        context,
        contextId: command.contextId,
        timeout,
        requestId
      });
      
      if (result.type === 'callback') {
        // Command is waiting for callback - this is expected for popup coordination
        logger.debug('Context popup coordination waiting for callback', { requestId, callbackType: result.callbackType });
        return result;
      }
      
      if (result.completed) {
        const validation = PopupCoordinator.validatePopupResult(result.result);
        if (!validation.valid) {
          logger.error('Invalid popup result', { requestId, error: validation.error });
          return { popupPageId: null };
        }
        
        return result.result;
      }
      
      return { popupPageId: null };
      
    } catch (error) {
      logger.error('Context popup coordination failed', { 
        requestId, 
        error: error.message 
      });
      return { popupPageId: null };
    }
  }
}

class PageHandler extends BaseHandler {
  async handle(command, method) {
    const page = this.validateResource(this.pages, command.pageId, 'Page');

    const registry = CommandRegistry.create({
      pause: () => page.pause(),
      close: () => this.closePage(command.pageId),
      goto: () => this.goto(page, command),
      evaluate: () => this.evaluate(page, command),
      waitForResponse: () => this.waitForResponse(page, command),
      content: async () => ({ content: await page.content() }),
      setContent: () => page.setContent(command.html, command.options),
      querySelector: () => this.querySelector(page, command),
      url: () => this.createValueResult(page.url()),
      title: () => PromiseUtils.wrapValue(page.title()),
      setViewportSize: () => page.setViewportSize(command.size),
      viewportSize: () => this.createValueResult(page.viewportSize()),
      waitForURL: () => page.waitForURL(command.url, command.options),
      waitForSelector: () => page.waitForSelector(command.selector, command.options),
      screenshot: () => PromiseUtils.wrapBinary(page.screenshot(command.options)),
      evaluateHandle: () => this.evaluateHandle(page, command),
      addScriptTag: () => page.addScriptTag(command.options),
      addStyleTag: () => page.addStyleTag(command.options).then(() => ({ success: true })),
      handleDialog: () => this.handleDialog(command),
      route: () => RouteUtils.setupPageRoute(page, command, this.generateId, this.routes, this.extractRequestData, this.sendFramedResponse, this.routeCounter),
      goBack: () => page.goBack(command.options),
      goForward: () => page.goForward(command.options),
      reload: () => page.reload(command.options),
      frames: () => this.getFrames(page),
      frame: () => this.getFrame(page, command),
      waitForPopup: () => this.waitForPopup(page, command)
    });

    return await ErrorHandler.safeExecute(() => this.executeWithRegistry(registry, method), { method, pageId: command.pageId });
  }

  async closePage(pageId) {
    const page = this.pages.get(pageId);
    if (page) { await page.close(); this.pages.delete(pageId); }
  }

  async goto(page, command) {
    const gotoResponse = await page.goto(command.url, command.options);
    return { response: this.serializeResponse(gotoResponse) };
  }

  async evaluate(page, command) {
    try {
      const attempt = await page.evaluate(async ({ expression, arg }) => {
        try {
          const func = eval(`(${expression})`);
          return typeof func === 'function' ? { ok: true, value: await func(arg) } : { ok: false };
        } catch (e) {
          return { ok: false, reason: e.message };
        }
      }, { expression: command.expression, arg: command.arg });

      const result = attempt?.ok ? attempt.value : await page.evaluate(command.expression, command.arg);
      const value = result === undefined ? null : result;
      logger.debug('PAGE EVALUATE OK', { type: typeof value, method: attempt?.ok ? 'func' : 'expr' });
      return { result: value };
    } catch (error) {
      logger.error('PAGE EVALUATE ERROR', { message: error.message });
      throw error;
    }
  }

  async waitForResponse(page, command) {
    const jsAction = command.jsAction;
    const [response] = await Promise.all([
      page.waitForResponse(command.url, command.options),
      jsAction ? page.evaluate(jsAction) : Promise.resolve(),
    ]);
    return { response: this.serializeResponse(response) };
  }

  async querySelector(page, command) {
    const element = await page.$(command.selector);
    if (!element) return { elementHandleId: null };
    const elementHandleId = this.generateId('element');
    this.elementHandles.set(elementHandleId, element);
    return { elementHandleId };
  }

  async evaluateHandle(page, command) {
    const handle = await page.evaluateHandle(command.expression, command.arg);
    const handleId = this.generateId('element');
    this.elementHandles.set(handleId, handle);
    return { elementHandleId: handleId };
  }

  async handleDialog(command) {
    const dialog = this.dialogs.get(command.dialogId);
    if (dialog) {
      if (command.accept) await dialog.accept(command.promptText);
      else await dialog.dismiss();
      this.dialogs.delete(command.dialogId);
    }
    return { success: true };
  }

  async getFrames(page) {
    const all = page.frames();
    const main = page.mainFrame();
    const framesOut = [];
    for (const f of all) {
      if (f === main) continue;
      const selector = await this.buildFrameSelector(f, main);
      framesOut.push({ selector });
    }
    return { frames: framesOut };
  }

  async getFrame(page, command) {
    const opts = command.options || {};
    const all = page.frames();
    const main = page.mainFrame();
    let target = null;
    for (const f of all) {
      if (f === main) continue;
      if (this.frameMatches(f, opts)) { target = f; break; }
    }
    if (!target) return { selector: null };
    const selector = await this.buildFrameSelector(target, main);
    return { selector };
  }

  frameMatches(frame, opts) {
    const fname = frame.name();
    const furl = frame.url();
    if (opts.name && fname === opts.name) return true;
    if (opts.url && furl === opts.url) return true;
    if (opts.urlRegex) {
      try {
        const regex = opts.urlRegex.startsWith('/') ? new RegExp(opts.urlRegex.slice(1, -1)) : new RegExp(opts.urlRegex);
        return regex.test(furl);
      } catch {}
    }
    return false;
  }

  async buildFrameSelector(frame, mainFrame) {
    const chain = [];
    let cur = frame;
    while (cur && cur !== mainFrame) {
      const element = await cur.frameElement();
      const sel = await element.evaluate((node) => {
        const esc = (s) => (typeof CSS !== 'undefined' && CSS.escape) ? CSS.escape(s) : String(s).replace(/[^a-zA-Z0-9_-]/g, (m) => `\\${m}`);
        if (node.id) return `iframe#${esc(node.id)}`;
        const name = node.getAttribute('name');
        if (name) return `iframe[name="${name}"]`;
        const src = node.getAttribute('src');
        if (src) return `iframe[src="${src}"]`;
        const iframes = Array.from(node.ownerDocument.querySelectorAll('iframe'));
        const idx = iframes.indexOf(node);
        return `iframe >> nth=${idx}`;
      });
      chain.unshift(sel);
      cur = cur.parentFrame();
    }
    return chain.join(' >> ');
  }

  async waitForPopup(page, command) {
    const timeout = command.timeout || 30000;
    const requestId = command.requestId || this.generateId('popup_req');
    
    logger.info('Starting page popup coordination', { 
      pageId: command.pageId, 
      timeout, 
      requestId 
    });
    
    // Create coordination phases
    const phases = PopupCoordinator.createPagePopupPhases({
      pages: this.pages,
      pageContexts: this.pageContexts,
      setupPageEventListeners: this.setupPageEventListeners?.bind(this),
      generateId: this.generateId.bind(this)
    });
    
    // Register the async command
    globalCoordinator.registerAsyncCommand(requestId, phases);
    
    try {
      // Start execution with initial data
      const result = await globalCoordinator.executeNextPhase(requestId, {
        page,
        pageId: command.pageId,
        timeout,
        requestId
      });
      
      if (result.type === 'callback') {
        // Command is waiting for callback - this is expected for popup coordination
        logger.debug('Page popup coordination waiting for callback', { requestId, callbackType: result.callbackType });
        return result;
      }
      
      if (result.completed) {
        const validation = PopupCoordinator.validatePopupResult(result.result);
        if (!validation.valid) {
          logger.error('Invalid popup result', { requestId, error: validation.error });
          return { popupPageId: null };
        }
        
        // Ensure popup page is registered in main pages Map
        const popupPageId = result.result.popupPageId;
        const popup = result.result.popup;
        
        if (popup && !this.pages.has(popupPageId)) {
          // Re-register the popup page in the main pages Map
          this.pages.set(popupPageId, popup);
          
          // Set up context mapping
          const contextId = this.pageContexts.get(command.pageId);
          if (contextId) {
            this.pageContexts.set(popupPageId, contextId);
          }
          
          logger.debug('Re-registered popup page in main pages Map', {
            popupPageId,
            contextId,
            totalPages: this.pages.size
          });
        }
        
        // Verify registration before returning
        const isRegistered = this.pages.has(popupPageId);
        logger.info('Page popup coordination completed', {
          popupPageId,
          isRegistered,
          totalPages: this.pages.size
        });
        
        return result.result;
      }
      
      return { popupPageId: null };
      
    } catch (error) {
      logger.error('Page popup coordination failed', { 
        requestId, 
        error: error.message 
      });
      return { popupPageId: null };
    }
  }
}

class LocatorHandler extends BaseHandler {
  async handle(command, method) {
    const page = this.validateResource(this.pages, command.pageId, 'Page');
    const locator = this.createLocator(page, command);

    const registry = CommandRegistry.create({
      check: () => locator.check(command.options),
      uncheck: () => locator.uncheck(command.options),
      clear: () => locator.clear(command.options),
      click: () => locator.click(command.options),
      dblclick: () => locator.dblclick(command.options),
      hover: () => locator.hover(command.options),
      blur: () => locator.blur(),
      focus: () => locator.focus(command.options),
      fill: () => locator.fill(command.value, command.options),
      type: () => locator.type(command.text, command.options),
      waitFor: () => locator.waitFor(command.options),
      setInputFiles: () => locator.setInputFiles(command.files, command.options),
      isHidden: () => PromiseUtils.wrapValue(locator.isHidden()),
      isDisabled: () => PromiseUtils.wrapValue(locator.isDisabled()),
      isVisible: () => PromiseUtils.wrapValue(locator.isVisible()),
      isEnabled: () => PromiseUtils.wrapValue(locator.isEnabled()),
      isChecked: () => PromiseUtils.wrapValue(locator.isChecked()),
      textContent: () => PromiseUtils.wrapValue(locator.textContent()),
      innerText: () => PromiseUtils.wrapValue(locator.innerText()),
      innerHTML: () => PromiseUtils.wrapValue(locator.innerHTML()),
      inputValue: () => PromiseUtils.wrapValue(locator.inputValue()),
      count: () => PromiseUtils.wrapValue(locator.count()),
      getAttribute: () => PromiseUtils.wrapValue(locator.getAttribute(command.name)),
      selectOption: () => PromiseUtils.wrapValues(locator.selectOption(command.values, command.options)),
      screenshot: () => PromiseUtils.wrapBinary(locator.screenshot(command.options)),
      evaluate: () => this.evaluateLocator(locator, command),
      dragAndDrop: () => this.handleDragAndDrop(page, command)
    });

    return await this.executeWithRegistry(registry, method);
  }

  createLocator(page, command) {
    if (command.frameSelector) {
      const frameLocator = FrameUtils.resolve(page, command.frameSelector);
      return frameLocator.locator(command.selector);
    }
    return page.locator(command.selector);
  }

  async evaluateLocator(locator, command) {
    try {
      const count = await locator.count();
      if (count === 0) return this.createValueResult(null);

      let result;
      try {
        result = await locator.first().evaluate(command.expression, command.arg);
      } catch (e) {
        result = undefined;
      }
      if (result === undefined || result === null) {
        try {
          result = await locator.first().evaluate((element, payload) => {
            try {
              const func = eval(`(${payload.expression})`);
              return typeof func === 'function' ? func(element, payload.arg) : null;
            } catch {
              return null;
            }
          }, { expression: command.expression, arg: command.arg });
        } catch {}
      }

      return this.createValueResult(result === undefined ? null : result);
    } catch (error) {
      logger.error('Locator evaluate failed', { selector: command.selector, error: error.message });
      throw error;
    }
  }

  async handleDragAndDrop(page, command) {
    logger.debug('Handling drag and drop', { 
      selector: command.selector, 
      target: command.target, 
      options: command.options 
    });
    
    try {
      // Use page.dragAndDrop which is the native Playwright method
      await page.dragAndDrop(command.selector, command.target, {
        strict: true,
        ...command.options
      });
      
      return this.createValueResult(true);
    } catch (error) {
      logger.error('Drag and drop failed', { 
        selector: command.selector, 
        target: command.target, 
        error: error.message 
      });
      throw error;
    }
  }
}

class InteractionHandler extends BaseHandler {
  async handleMouse(command, method) {
    const page = this.validateResource(this.pages, command.pageId, 'Page');
    const registry = CommandRegistry.create({
      click: () => page.mouse.click(command.x, command.y, command.options),
      move: () => page.mouse.move(command.x, command.y, command.options),
      wheel: () => page.mouse.wheel(command.deltaX, command.deltaY)
    });
    await this.executeWithRegistry(registry, method);
  }

  async handleKeyboard(command, method) {
    const page = this.validateResource(this.pages, command.pageId, 'Page');
    const registry = CommandRegistry.create({
      insertText: () => page.keyboard.insertText(command.text),
      press: () => page.keyboard.press(command.key, command.options),
      type: () => page.keyboard.type(command.text, command.options)
    });
    await this.executeWithRegistry(registry, method);
  }
}

class FrameHandler extends BaseHandler {
  async handle(command, method) {
    const page = this.validateResource(this.pages, command.pageId, 'Page');
    const isMainFrame = !command.frameSelector || command.frameSelector === ':root';
    const frameLocator = isMainFrame ? null : FrameUtils.resolve(page, command.frameSelector);
    const evalInFrame = (expression) => FrameUtils.evaluateInFrame(page, frameLocator, isMainFrame, expression);

    const registry = CommandRegistry.create({
      name: () => evalInFrame(() => window.name || '').then(v => this.createValueResult(v ?? '')),
      url: () => evalInFrame(() => document.location.href).then(v => this.createValueResult(v ?? '')),
      isDetached: () => this.checkDetached(isMainFrame, frameLocator),
      waitForLoadState: () => this.waitForLoadState(page, frameLocator, isMainFrame, command),
      parent: () => this.getParent(isMainFrame, command.frameSelector),
      children: () => this.getChildren(page, frameLocator, isMainFrame, command.frameSelector)
    });

    return await this.executeWithRegistry(registry, method);
  }

  async checkDetached(isMainFrame, frameLocator) {
    if (isMainFrame) return this.createValueResult(false);
    const count = await frameLocator.locator('html').count();
    return this.createValueResult(count === 0);
  }

  async waitForLoadState(page, frameLocator, isMainFrame, command) {
    const timeout = command.options?.timeout ?? 30000;
    if (isMainFrame) {
      await page.waitForLoadState(command.state || 'load', command.options || {});
      return { success: true };
    }
    await frameLocator.locator('body').waitFor({ state: 'attached', timeout });
    const evalInFrame = (expression) => FrameUtils.evaluateInFrame(page, frameLocator, isMainFrame, expression);
    await FrameUtils.waitForReadyState(evalInFrame, command.state || 'load', timeout);
    return { success: true };
  }

  getParent(isMainFrame, frameSelector) {
    if (isMainFrame) return { selector: null };
    const parts = String(frameSelector).split(' >> ').filter(Boolean);
    parts.pop();
    const parentSelector = parts.length ? parts.join(' >> ') : ':root';
    return { selector: parentSelector };
  }

  async getChildren(page, frameLocator, isMainFrame, frameSelector) {
    const selectorBuilder = (root) => {
      const esc = (s) => (typeof CSS !== 'undefined' && CSS.escape) ? CSS.escape(s) : String(s).replace(/[^a-zA-Z0-9_-]/g, (m) => `\\${m}`);
      return Array.from(root.ownerDocument.querySelectorAll('iframe')).map((node, idx) => {
        if (node.id) return `iframe#${esc(node.id)}`;
        if (node.getAttribute('name')) return `iframe[name="${node.getAttribute('name')}"]`;
        if (node.getAttribute('src')) return `iframe[src="${node.getAttribute('src')}"]`;
        return `iframe >> nth=${idx}`;
      });
    };

    const childSelectors = isMainFrame ? await page.evaluate(selectorBuilder) : await frameLocator.locator('html').evaluate(selectorBuilder) || [];
    const prefix = isMainFrame ? '' : (String(frameSelector) + ' >> ');
    const frames = childSelectors.map(sel => ({ selector: prefix + sel }));
    return { frames };
  }
}

module.exports = { ContextHandler, PageHandler, LocatorHandler, InteractionHandler, FrameHandler };
