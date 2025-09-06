const fs = require('fs');
const path = require('path');

const DEBUG = process.env.PLAYWRIGHT_DEBUG === 'true';
const DEBUG_LEVEL = process.env.PLAYWRIGHT_DEBUG_LEVEL || 'info';
const DEBUG_LOG_FILE = process.env.PLAYWRIGHT_DEBUG_LOG || 'debug-dispatch.log';
const DEBUG_LOG_DIR = process.env.PLAYWRIGHT_DEBUG_DIR || process.cwd();

class DebugLogger {
  constructor() {
    this.logPath = path.join(DEBUG_LOG_DIR, DEBUG_LOG_FILE);
    this.enabled = DEBUG;
    this.level = DEBUG_LEVEL.toLowerCase();
    
    
    this.levels = {
      'error': 0,
      'warn': 1,
      'info': 2,
      'debug': 3
    };
    
    this.currentLevelPriority = this.levels[this.level] ?? this.levels['info'];
  }

  shouldLog(level) {
    if (!this.enabled) return false;
    const levelPriority = this.levels[level] ?? this.levels['info'];
    return levelPriority <= this.currentLevelPriority;
  }

  log(message, data = null, level = 'info') {
    if (!this.shouldLog(level)) return;

    const timestamp = new Date().toISOString();
    let logMessage = `[${timestamp}] [${level.toUpperCase()}] ${message}`;

    if (data) {
      if (typeof data === 'object') {
        logMessage += `\n${JSON.stringify(data, null, 2)}`;
      } else {
        logMessage += ` ${data}`;
      }
    }

    logMessage += '\n';

    try {
      fs.appendFileSync(this.logPath, logMessage);
    } catch (error) {
      
    }
  }

  error(message, data = null) {
    this.log(message, data, 'error');
  }

  warn(message, data = null) {
    this.log(message, data, 'warn');
  }

  info(message, data = null) {
    this.log(message, data, 'info');
  }

  debug(message, data = null) {
    this.log(message, data, 'debug');
  }

  separator(title) {
    this.log(`=== ${title} ===`, null, 'info');
  }
}

const debugLogger = new DebugLogger();

let logStream;

if (DEBUG) {
  logStream = fs.createWriteStream('playwright-server.log', {flags: 'a'});
  const originalConsoleLog = console.log;
  console.log = function (d) {
    logStream.write(new Date().toISOString() + ' - ' + d + '\n');
    originalConsoleLog(d);
  };
}

const {chromium, firefox, webkit} = require('playwright');

class LspFraming {
  static encode(content) {
    const contentLength = Buffer.byteLength(content, 'utf8');
    return `Content-Length: ${contentLength}\r\n\r\n${content}`;
  }

  static decode(buffer) {
    const messages = [];
    let remaining = buffer;

    while (true) {
      const result = this.extractOneMessage(remaining);
      if (!result) break;
      
      const [message, newRemaining] = result;
      messages.push(message);
      remaining = newRemaining;
    }

    return { messages, remainingBuffer: remaining };
  }

  static extractOneMessage(buffer) {
    const headerEndPos = buffer.indexOf('\r\n\r\n');
    if (headerEndPos === -1) return null;

    const headers = buffer.slice(0, headerEndPos).toString('utf8');
    const contentStart = headerEndPos + 4;

    const contentLength = this.parseContentLength(headers);
    if (contentLength === null) {
      throw new Error('Missing or invalid Content-Length header');
    }

    if (buffer.length < contentStart + contentLength) return null;

    const content = buffer.slice(contentStart, contentStart + contentLength).toString('utf8');
    const remaining = buffer.slice(contentStart + contentLength);

    return [content, remaining];
  }

  static parseContentLength(headers) {
    const lines = headers.split('\r\n');
    for (const line of lines) {
      const match = line.trim().match(/^Content-Length:\s*(\d+)$/i);
      if (match) {
        return parseInt(match[1], 10);
      }
    }
    return null;
  }
}

function sendFramedResponse(data) {
  const json = JSON.stringify(data);
  const framed = LspFraming.encode(json);
  process.stdout.write(framed);
}

class PlaywrightServer {
  constructor() {
    this.browsers = new Map();
    this.contexts = new Map();
    this.pages = new Map();
    this.pageContexts = new Map();
    this.responses = new Map();
    this.browserCounter = 0;
    this.contextCounter = 0;
    this.pageCounter = 0;
    this.responseCounter = 0;
    this.routes = new Map();
    this.routeCounter = 0;
    this.contextThrottling = new Map();
    this.dialogs = new Map();
    this.elementHandleCounter = 0;
    this.elementHandles = new Map();
  }

  async handleCommand(command) {
    const {requestId} = command;
    try {
      const result = await this.dispatch(command);
      const finalResult = (result === undefined || result === null) ? {success: true} : result;
      return {requestId, ...finalResult};
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);

      
      if (DEBUG) {
        debugLogger.error('DETAILED ERROR', {
          command: command.action,
          error: message,
          stack: error.stack,
          pageId: command.pageId
        });
      }

      
      debugLogger.error('Command execution failed', {
        command: command.action,
        error: message,
        stack: error.stack,
        requestId
      });

      return {
        requestId,
        error: message,
        stack: DEBUG ? error.stack : undefined,
        command: command.action
      };
    }
  }

  async dispatch(command) {
    debugLogger.info('Dispatching command', {
      action: command.action,
      pageId: command.pageId,
      selector: command.selector
    });

    const [actionPrefix, actionMethod] = command.action.split('.');
    debugLogger.debug(`Action: ${actionPrefix}.${actionMethod}`);

    if (actionPrefix === 'context') {
      return await this.handleContextCommand(command, actionMethod);
    }
    if (actionPrefix === 'page') {
      return await this.handlePageCommand(command, actionMethod);
    }
    if (actionPrefix === 'locator') {
      return await this.handleLocatorCommand(command, actionMethod);
    }
    if (actionPrefix === 'route') {
      return await this.handleRouteCommand(command, actionMethod);
    }
    if (actionPrefix === 'response') {
      return await this.handleResponseCommand(command, actionMethod);
    }
    if (actionPrefix === 'mouse') {
      return await this.handleMouseCommand(command, actionMethod);
    }
    if (actionPrefix === 'keyboard') {
      return await this.handleKeyboardCommand(command, actionMethod);
    }
    if (actionPrefix === 'frame') {
      return await this.handleFrameCommand(command, actionMethod);
    }
    switch (command.action) {
      case 'launch':
        return await this.launchBrowser(command);
      case 'newContext':
        return await this.newContext(command);
      case 'close':
        return await this.closeBrowser(command);
      case 'exit':
        return await this.exit();
      default:
        throw new Error(`Unknown action: ${command.action}`);
    }
  }

  async handleFrameCommand(command, method) {
    const page = this.pages.get(command.pageId);
    if (!page) throw new Error(`Page not found: ${command.pageId}`);

    const isMainFrame = !command.frameSelector || command.frameSelector === ':root';
    const resolveFrameLocator = (page, chain) => {
      if (!chain || chain === ':root') return page;
      const parts = String(chain).split(' >> ').filter(Boolean);
      let fl = page;
      for (const part of parts) fl = fl.frameLocator(part);
      return fl;
    };
    const frameLocator = isMainFrame ? null : resolveFrameLocator(page, command.frameSelector);

    const evalInFrame = async (expression) => {
      if (isMainFrame) {
        return await page.evaluate(expression);
      }
      const loc = frameLocator.locator('html');
      const count = await loc.count();
      if (count === 0) return null;
      return await loc.evaluate(expression);
    };

    const waitForReadyState = async (state, timeoutMs) => {
      const start = Date.now();
      const deadline = start + timeoutMs;
      const target = state || 'load';
      const predicate = async () => {
        const readyState = await evalInFrame(() => document.readyState);
        if (readyState === null) return false; // detached
        if (target === 'load') return readyState === 'complete';
        if (target === 'domcontentloaded') return readyState === 'interactive' || readyState === 'complete';
        if (target === 'networkidle') return readyState === 'complete'; // approximation
        return readyState === 'complete';
      };
      while (Date.now() < deadline) {
        try {
          if (await predicate()) return;
        } catch {}
        await new Promise(r => setTimeout(r, 50));
      }
      throw new Error(`Timeout waiting for load state: ${state || 'load'}`);
    };

    switch (method) {
      case 'name': {
        const value = await evalInFrame(() => window.name || '');
        return { value: value ?? '' };
      }
      case 'url': {
        const value = await evalInFrame(() => document.location.href);
        return { value: value ?? '' };
      }
      case 'isDetached': {
        if (isMainFrame) return { value: false };
        const count = await frameLocator.locator('html').count();
        return { value: count === 0 };
      }
      case 'waitForLoadState': {
        const timeout = (command.options && typeof command.options.timeout === 'number') ? command.options.timeout : 30000;
        if (isMainFrame) {
          await page.waitForLoadState(command.state || 'load', command.options || {});
          return { success: true };
        }
        await frameLocator.locator('body').waitFor({ state: 'attached', timeout });
        await waitForReadyState(command.state || 'load', timeout);
        return { success: true };
      }
      case 'parent': {
        if (isMainFrame) return { selector: null };
        const parts = String(command.frameSelector).split(' >> ').filter(Boolean);
        parts.pop();
        const parentSelector = parts.length ? parts.join(' >> ') : ':root';
        return { selector: parentSelector };
      }
      case 'children': {
        const buildSelectors = async () => {
          const compute = (root) => {
            const esc = (s) => (typeof CSS !== 'undefined' && CSS.escape) ? CSS.escape(s) : String(s).replace(/[^a-zA-Z0-9_-]/g, (m) => `\\${m}`);
            const iframes = Array.from(root.ownerDocument.querySelectorAll('iframe'));
            return iframes.map((node, idx) => {
              const id = node.id;
              if (id) return `iframe#${esc(id)}`;
              const name = node.getAttribute('name');
              if (name) return `iframe[name="${name}"]`;
              const src = node.getAttribute('src');
              if (src) return `iframe[src="${src}"]`;
              return `iframe >> nth=${idx}`;
            });
          };
          if (isMainFrame) {
            return await page.evaluate(() => {
              const esc = (s) => (typeof CSS !== 'undefined' && CSS.escape) ? CSS.escape(s) : String(s).replace(/[^a-zA-Z0-9_-]/g, (m) => `\\${m}`);
              const iframes = Array.from(document.querySelectorAll('iframe'));
              return iframes.map((node, idx) => {
                const id = node.id;
                if (id) return `iframe#${esc(id)}`;
                const name = node.getAttribute('name');
                if (name) return `iframe[name="${name}"]`;
                const src = node.getAttribute('src');
                if (src) return `iframe[src="${src}"]`;
                return `iframe >> nth=${idx}`;
              });
            });
          }
          const results = await frameLocator.locator('html').evaluate((root) => {
            const esc = (s) => (typeof CSS !== 'undefined' && CSS.escape) ? CSS.escape(s) : String(s).replace(/[^a-zA-Z0-9_-]/g, (m) => `\\${m}`);
            const iframes = Array.from(root.ownerDocument.querySelectorAll('iframe'));
            return iframes.map((node, idx) => {
              const id = node.id;
              if (id) return `iframe#${esc(id)}`;
              const name = node.getAttribute('name');
              if (name) return `iframe[name="${name}"]`;
              const src = node.getAttribute('src');
              if (src) return `iframe[src="${src}"]`;
              return `iframe >> nth=${idx}`;
            });
          });
          return results || [];
        };

        const childSelectors = await buildSelectors();
        const prefix = isMainFrame ? '' : (String(command.frameSelector) + ' >> ');
        const frames = childSelectors.map(sel => ({ selector: prefix + sel }));
        return { frames };
      }
      default:
        throw new Error(`Unknown frame action: ${method}`);
    }
  }

  async handleContextCommand(command, method) {
    const context = this.contexts.get(command.contextId)?.context;
    if (!context) throw new Error(`Context not found: ${command.contextId}`);

    
    if (!context._initScriptPromise) context._initScriptPromise = Promise.resolve();

    switch (method) {
      case 'addInitScript':
        if (command.script) {
          await context.addInitScript(command.script);
        }
        return {success: true};
      case 'setOffline':
        await context.setOffline(!!command.offline);
        return {success: true};
      case 'setGeolocation':
        if (command.geolocation) {
          await context.setGeolocation(command.geolocation);
        }
        return {success: true};
      case 'addCookies':
        if (Array.isArray(command.cookies)) {
          await context.addCookies(command.cookies);
        }
        return {success: true};
      case 'clearCookies':
        await context.clearCookies();
        return {success: true};
      case 'grantPermissions':
        if (Array.isArray(command.permissions)) {
          if (command.origin) {
            await context.grantPermissions(command.permissions, { origin: command.origin });
          } else {
            await context.grantPermissions(command.permissions);
          }
        }
        return {success: true};
      case 'clearPermissions':
        await context.clearPermissions();
        return {success: true};
      case 'startTracing':
        await context.tracing.start(command.options || {});
        return {success: true};
      case 'stopTracing':
        await context.tracing.stop({ path: command.path });
        return {success: true};
      case 'waitForEvent':
        await context.waitForEvent(command.event, { timeout: command.timeout });
        return {success: true};
      case 'setNetworkThrottling':
        if (command.throttling && typeof command.throttling === 'object') {
          this.contextThrottling.set(command.contextId, {
            latency: Number(command.throttling.latency) || 0,
            downloadThroughput: Number(command.throttling.downloadThroughput) || 0,
            uploadThroughput: Number(command.throttling.uploadThroughput) || 0,
          });
        }
        return {success: true};
      case 'route':
        await context.route(command.url, async (route) => {
          const routeId = `route_${++this.routeCounter}`;
          this.routes.set(routeId, { route, contextId: command.contextId });

          const req = route.request();
          let postData = null;
          try {
            postData = req.postData();
          } catch (e) {
            postData = null;
          }
          debugLogger.info('CTX ROUTE', { url: req.url(), method: req.method() });
          const event = {
            objectId: command.contextId,
            event: 'route',
            params: {
              routeId,
              request: {
                url: req.url(),
                method: req.method(),
                headers: req.headers(),
                postData: postData ?? null,
                resourceType: req.resourceType ? req.resourceType() : 'document'
              }
            }
          };
          sendFramedResponse(event);
        });
        return {success: true};
      case 'unroute':
        await context.unroute(command.url);
        return {success: true};
      case 'newPage':
        const page = await context.newPage(command.options);
        const pageId = `page_${++this.pageCounter}`;
        this.pages.set(pageId, page);
        this.pageContexts.set(pageId, command.contextId);

        // Forward important page events
        page.on('console', (msg) => {
          const params = {
            type: msg.type(),
            text: msg.text(),
            args: [],
            location: msg.location ? msg.location() : {},
          };
          sendFramedResponse({ objectId: pageId, event: 'console', params });
        });

        page.on('dialog', (dialog) => {
          const dialogId = `dialog_${Date.now()}_${Math.floor(Math.random()*1000)}`;
          this.dialogs.set(dialogId, dialog);
          const params = {
            dialogId,
            type: dialog.type(),
            message: dialog.message(),
            defaultValue: dialog.defaultValue ? dialog.defaultValue() : null,
          };
          sendFramedResponse({ objectId: pageId, event: 'dialog', params });
        });

        page.on('request', (req) => {
          const params = {
            request: {
              url: req.url(),
              method: req.method(),
              headers: req.headers(),
              postData: (() => { try { return req.postData(); } catch { return null; } })(),
              resourceType: req.resourceType ? req.resourceType() : 'document',
            }
          };
          sendFramedResponse({ objectId: pageId, event: 'request', params });
        });

        page.on('response', (res) => {
          const params = { response: this.serializeResponse(res) };
          sendFramedResponse({ objectId: pageId, event: 'response', params });
        });

        page.on('requestfailed', (req) => {
          const params = {
            request: {
              url: req.url(),
              method: req.method(),
              headers: req.headers(),
              postData: (() => { try { return req.postData(); } catch { return null; } })(),
              resourceType: req.resourceType ? req.resourceType() : 'document',
            }
          };
          sendFramedResponse({ objectId: pageId, event: 'requestfailed', params });
        });

        return {pageId: pageId};
      case 'cookies':
        return {cookies: await context.cookies(command.urls)};
      case 'storageState':
        return {storageState: await context.storageState(command.options)};
      case 'clipboardText':
        
        const pages = context.pages();
        if (pages.length === 0) {
          throw new Error('No pages available in context to read clipboard');
        }
        const activePage = pages[0]; 
        return {value: await activePage.evaluate(() => navigator.clipboard.readText())};
      default:
        throw new Error(`Unknown context action: ${method}`);
    }
  }

  async handlePageCommand(command, method) {
    const page = this.pages.get(command.pageId);
    if (!page) throw new Error(`Page not found: ${command.pageId}`);

    switch (method) {
      case 'pause':
        await page.pause();
        return;
      case 'close':
        await page.close();
        this.pages.delete(command.pageId);
        return;
      case 'goto':
        const gotoResponse = await page.goto(command.url, command.options);
        return {response: this.serializeResponse(gotoResponse)};
      case 'evaluate':
        try {
          // Attempt function-like evaluation first to support strings like '() => 42' or 'async () => {...}'
          const attempt = await page.evaluate(async ({expression, arg}) => {
            try {
              const func = eval(`(${expression})`);
              if (typeof func === 'function') {
                const value = await func(arg);
                return { ok: true, value };
              }
              return { ok: false, reason: 'not-a-function' };
            } catch (e) {
              return { ok: false, reason: e.message };
            }
          }, {expression: command.expression, arg: command.arg});

          if (attempt && attempt.ok === true) {
            const value = attempt.value === undefined ? null : attempt.value;
            debugLogger.info('PAGE EVALUATE(FUNC) OK', { type: typeof value });
            return {result: value};
          }

          // Fallback: treat expression as direct JS expression (e.g., 'window.foo')
          const result = await page.evaluate(command.expression, command.arg);
          const value = result === undefined ? null : result;
          debugLogger.info('PAGE EVALUATE(EXPR) OK', { type: typeof value });
          return {result: value};
        } catch (error) {
          debugLogger.error('PAGE EVALUATE ERROR', { message: error.message });
          throw error;
        }
      case 'waitForResponse':
        const jsAction = command.jsAction;
        const [response] = await Promise.all([
          page.waitForResponse(command.url, command.options),
          jsAction ? page.evaluate(jsAction) : Promise.resolve(),
        ]);
        return {response: this.serializeResponse(response)};
      case 'content':
        return {content: await page.content()};
      case 'setContent':
        await page.setContent(command.html, command.options);
        return;
      case 'querySelector':
        const element = await page.$(command.selector);
        if (!element) {
          return {elementHandleId: null};
        }
        const elementHandleId = `element_${++this.elementHandleCounter}`;
        this.elementHandles.set(elementHandleId, element);
        return {elementHandleId};
      case 'url':
        return {value: page.url()};
      case 'title':
        return {value: await page.title()};
      case 'setViewportSize':
        await page.setViewportSize(command.size);
        return;
      case 'viewportSize':
        return {value: page.viewportSize()};
      case 'waitForURL':
        await page.waitForURL(command.url, command.options);
        return;
      case 'waitForSelector':
        await page.waitForSelector(command.selector, command.options);
        return;
      case 'screenshot':
        const buffer = await page.screenshot(command.options);
        return {binary: buffer.toString('base64')};
      case 'evaluateHandle':
        const handle = await page.evaluateHandle(command.expression, command.arg);
        const handleId = `element_${++this.elementHandleCounter}`;
        this.elementHandles.set(handleId, handle);
        return {elementHandleId: handleId};
      case 'addScriptTag':
        await page.addScriptTag(command.options);
        return;
      case 'addStyleTag':
        await page.addStyleTag(command.options);
        return {success: true};
      case 'handleDialog':
        const dialog = this.dialogs.get(command.dialogId);
        if (dialog) {
          if (command.accept) {
            await dialog.accept(command.promptText);
          } else {
            await dialog.dismiss();
          }
          this.dialogs.delete(command.dialogId);
        }
        return {success: true};
      case 'route':
        await page.route(command.url, async (route) => {
          const routeId = `route_${++this.routeCounter}`;
          const contextId = this.pageContexts.get(command.pageId);
          this.routes.set(routeId, { route, contextId });
          
          const req = route.request();
          let postData = null;
          try {
            postData = req.postData();
          } catch (e) {
            postData = null;
          }
          debugLogger.info('PAGE ROUTE', { url: req.url(), method: req.method() });
          const event = {
            objectId: command.pageId,
            event: 'route',
            params: {
              routeId,
              request: {
                url: req.url(),
                method: req.method(),
                headers: req.headers(),
                postData: postData ?? null,
                resourceType: req.resourceType ? req.resourceType() : 'document'
              }
            }
          };
          sendFramedResponse(event);
        });
        return;
      case 'goBack':
        await page.goBack(command.options);
        return;
      case 'goForward':
        await page.goForward(command.options);
        return;
      case 'reload':
        await page.reload(command.options);
        return;
      case 'frames': {
        const all = page.frames();
        const main = page.mainFrame();
        const framesOut = [];
        const selectorFor = async (frame) => {
          if (frame === main) return ':root';
          const chain = [];
          let cur = frame;
          while (cur && cur !== main) {
            const element = await cur.frameElement();
            const sel = await element.evaluate((node) => {
              const esc = (s) => (typeof CSS !== 'undefined' && CSS.escape) ? CSS.escape(s) : String(s).replace(/[^a-zA-Z0-9_-]/g, (m) => `\\${m}`);
              const id = node.id;
              if (id) return `iframe#${esc(id)}`;
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
        };
        for (const f of all) {
          if (f === main) continue;
          const selector = await selectorFor(f);
          framesOut.push({ selector });
        }
        return { frames: framesOut };
      }
      case 'frame': {
        const opts = command.options || {};
        const all = page.frames();
        const main = page.mainFrame();
        let target = null;
        for (const f of all) {
          if (f === main) continue;
          const fname = f.name();
          const furl = f.url();
          let matched = false;
          if (opts.name && typeof opts.name === 'string') matched = fname === opts.name;
          if (!matched && opts.url && typeof opts.url === 'string') matched = furl === opts.url;
          if (!matched && opts.urlRegex && typeof opts.urlRegex === 'string') {
            try {
              const raw = String(opts.urlRegex);
              let regex;
              if (raw.startsWith('/')) {
                const last = raw.lastIndexOf('/');
                const pattern = raw.slice(1, last > 0 ? last : raw.length);
                const flags = last > 0 ? raw.slice(last + 1) : '';
                regex = new RegExp(pattern, flags);
              } else {
                regex = new RegExp(raw);
              }
              matched = regex.test(furl);
            } catch {}
          }
          if (matched) { target = f; break; }
        }
        if (!target) return { selector: null };
        const selector = await (async () => {
          const chain = [];
          let cur = target;
          while (cur && cur !== main) {
            const element = await cur.frameElement();
            const sel = await element.evaluate((node) => {
              const esc = (s) => (typeof CSS !== 'undefined' && CSS.escape) ? CSS.escape(s) : String(s).replace(/[^a-zA-Z0-9_-]/g, (m) => `\\${m}`);
              const id = node.id;
              if (id) return `iframe#${esc(id)}`;
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
        })();
        return { selector };
      }
      default:
        throw new Error(`Unknown page action: ${method}`);
    }
  }

  async handleLocatorCommand(command, method) {
    const page = this.pages.get(command.pageId);
    if (!page) throw new Error('Page not found');

    let locator;
    if (command.frameSelector) {
      const resolveFrameLocator = (page, chain) => {
        if (!chain || chain === ':root') return page;
        const parts = String(chain).split(' >> ').filter(Boolean);
        let fl = page;
        for (const part of parts) fl = fl.frameLocator(part);
        return fl;
      };
      locator = resolveFrameLocator(page, command.frameSelector).locator(command.selector);
    } else {
      locator = page.locator(command.selector);
    }

    switch (method) {
      case 'check':
        await locator.check(command.options);
        return;
      case 'uncheck':
        await locator.uncheck(command.options);
        return;
      case 'clear':
        await locator.clear(command.options);
        return;
      case 'isHidden':
        return {value: await locator.isHidden()};
      case 'isDisabled':
        return {value: await locator.isDisabled()};
      case 'click':
        await locator.click(command.options);
        return;
      case 'dblclick':
        await locator.dblclick(command.options);
        return;
      case 'textContent':
        const textResult = await locator.textContent();
        return {value: textResult === null ? null : textResult};
      case 'innerText':
        return {value: await locator.innerText()};
      case 'innerHTML':
        return {value: await locator.innerHTML()};
      case 'inputValue':
        return {value: await locator.inputValue()};
      case 'getAttribute':
        return {value: await locator.getAttribute(command.name)};
      case 'count':
        return {value: await locator.count()};
      case 'isVisible':
        return {value: await locator.isVisible()};
      case 'isEnabled':
        return {value: await locator.isEnabled()};
      case 'isChecked':
        return {value: await locator.isChecked()};
      case 'waitFor':
        await locator.waitFor(command.options);
        return;
      case 'selectOption':
        return {values: await locator.selectOption(command.values, command.options)};
      case 'type':
        await locator.type(command.text, command.options);
        return;
      case 'hover':
        await locator.hover(command.options);
        return;
      case 'screenshot':
        const buffer = await locator.screenshot(command.options);
        return {binary: buffer.toString('base64')};
      case 'setInputFiles':
        await locator.setInputFiles(command.files, command.options);
        return;
      case 'evaluate':
        try {
          const count = await locator.count();
          if (count === 0) {
            return {value: null};
          }

          
          const alternativeResult = await page.evaluate(({selector, expression, arg}) => {
            const element = document.querySelector(selector);
            if (!element) {
              return {error: 'Element not found in DOM'};
            }

            try {
              const func = eval(`(${expression})`);
              const result = func(element, arg);
              return {success: true, result: result};
            } catch (e) {
              return {error: e.message, stack: e.stack};
            }
          }, {selector: command.selector, expression: command.expression, arg: command.arg});

          if (alternativeResult.success) {
            return {value: alternativeResult.result === undefined ? null : alternativeResult.result};
          }

          
          const result = await locator.first().evaluate(command.expression, command.arg);
          return {value: result === undefined ? null : result};
        } catch (error) {
          debugLogger.error('Locator evaluate failed', {
            selector: command.selector,
            error: error.message
          });
          throw error;
        }
      case 'focus':
        await locator.focus(command.options);
        return;
      case 'fill':
        await locator.fill(command.value, command.options);
        return;
      default:
        throw new Error(`Unknown locator action: ${method}`);
    }
  }

  async handleRouteCommand(command, method) {
    const info = this.routes.get(command.routeId);
    if (!info) throw new Error(`Route not found: ${command.routeId}`);
    const route = info.route || info;

    switch (method) {
      case 'fulfill':
        debugLogger.info('ROUTE FULFILL', { routeId: command.routeId });
        await route.fulfill(command.options);
        break;
      case 'abort':
        debugLogger.info('ROUTE ABORT', { routeId: command.routeId, errorCode: command.errorCode });
        await route.abort(command.errorCode);
        break;
      case 'continue':
        debugLogger.info('ROUTE CONTINUE', { routeId: command.routeId });
        try {
          const ctxId = info.contextId;
          const throttle = ctxId ? this.contextThrottling.get(ctxId) : null;
          if (throttle && throttle.latency && throttle.latency > 0) {
            await new Promise(r => setTimeout(r, throttle.latency));
          }
        } catch {}
        await route.continue(command.options || undefined);
        break;
      default:
        throw new Error(`Unknown route action: ${method}`);
    }
    this.routes.delete(command.routeId);
  }

  async handleResponseCommand(command, method) {
    switch (method) {
      case 'body':
        const res = this.responses.get(command.responseId);
        if (!res) throw new Error(`Response not found: ${command.responseId}`);
        const buf = await res.body();
        return { binary: Buffer.from(buf).toString('base64') };
      default:
        throw new Error(`Unknown response action: ${method}`);
    }
  }

  async handleMouseCommand(command, method) {
    const page = this.pages.get(command.pageId);
    if (!page) throw new Error(`Page not found: ${command.pageId}`);

    switch (method) {
      case 'click':
        await page.mouse.click(command.x, command.y, command.options);
        return;
      case 'move':
        await page.mouse.move(command.x, command.y, command.options);
        return;
      case 'wheel':
        await page.mouse.wheel(command.deltaX, command.deltaY);
        return;
      default:
        throw new Error(`Unknown mouse action: ${method}`);
    }
  }

  async handleKeyboardCommand(command, method) {
    const page = this.pages.get(command.pageId);
    if (!page) throw new Error(`Page not found: ${command.pageId}`);

    switch (method) {
      case 'insertText':
        await page.keyboard.insertText(command.text);
        return;
      case 'press':
        await page.keyboard.press(command.key, command.options);
        return;
      case 'type':
        await page.keyboard.type(command.text, command.options);
        return;
      default:
        throw new Error(`Unknown keyboard action: ${method}`);
    }
  }

  serializeResponse(response) {
    if (!response) return null;
    const responseId = `response_${++this.responseCounter}`;
    this.responses.set(responseId, response);
    return {
      responseId,
      url: response.url(),
      status: response.status(),
      statusText: response.statusText(),
      headers: response.headers(),
    };
  }

  async launchBrowser(command) {
    try {
      const browserType = command.browser === 'firefox' ? firefox :
        command.browser === 'webkit' ? webkit : chromium;

      const launchOptions = {
        headless: true,
        ...command.options
      };

      if (command?.options?.env?.PWDEBUG) {
        try {
          process.env.PWDEBUG = String(command.options.env.PWDEBUG);
        } catch (e) {
          
        }
      }

      if (command.browser === 'chromium' || !command.browser) {
        launchOptions.args = [
          '--no-sandbox',
          '--disable-dev-shm-usage',
          '--disable-web-security',
          '--disable-features=VizDisplayCompositor',
          '--disable-background-timer-throttling',
          '--disable-backgrounding-occluded-windows',
          '--disable-renderer-backgrounding',
          '--disable-field-trial-config',
          '--disable-ipc-flooding-protection',
          ...(launchOptions.args || [])
        ];
      }

      const browser = await browserType.launch(launchOptions);
      const browserId = `browser_${++this.browserCounter}`;
      this.browsers.set(browserId, browser);

      
      const context = await browser.newContext();
      const contextId = `context_${++this.contextCounter}`;
      this.contexts.set(contextId, {context, browserId, id: contextId});

      return {browserId, defaultContextId: contextId, version: browser.version()};
    } catch (error) {
      debugLogger.error('launchBrowser error', {message: error.message});
      throw error;
    }
  }

  async newContext(command) {
    const browser = this.browsers.get(command.browserId);
    if (!browser) throw new Error('Browser not found');
    const context = await browser.newContext(command.options || {});
    const contextId = `context_${++this.contextCounter}`;
    this.contexts.set(contextId, {context, browserId: command.browserId, id: contextId});
    return {contextId};
  }

  async closeBrowser(command) {
    const browser = this.browsers.get(command.browserId);
    if (!browser) return;
    await browser.close();
    this.browsers.delete(command.browserId);

    for (const [contextId, contextInfo] of this.contexts.entries()) {
      if (contextInfo.browserId === command.browserId) {
        this.contexts.delete(contextId);
      }
    }

    for (const [pageId, page] of this.pages.entries()) {
      try {
        if (page.context().browser() === browser) {
          this.pages.delete(pageId);
        }
      } catch (e) {
        this.pages.delete(pageId);
      }
    }
  }

  async exit() {
    for (const browser of this.browsers.values()) {
      try {
        await browser.close();
      } catch (e) {
        
      }
    }
    if (logStream) {
      logStream.end();
    }
    process.exit(0);
  }
}

const server = new PlaywrightServer();
let inputBuffer = Buffer.alloc(0);

sendFramedResponse({type: 'ready', message: 'READY'});

process.stdin.on('data', async (chunk) => {
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
        if (result) {
          if (command.requestId) {
            result.requestId = command.requestId;
          }
          sendFramedResponse(result);
        }
      } catch (error) {
        if (DEBUG) {
          debugLogger.error('SERVER ERROR', {
            error: error.message, 
            stack: error.stack, 
            command: messageContent
          });
        }
        const errorResponse = {error: error.message, parseError: true};
        if (command && command.requestId) {
          errorResponse.requestId = command.requestId;
        }
        sendFramedResponse(errorResponse);
      }
    }
  } catch (error) {
    if (DEBUG) {
      debugLogger.error('LSP FRAMING ERROR', {
        error: error.message,
        stack: error.stack
      });
    }
    const errorResponse = {error: 'LSP framing error: ' + error.message};
    sendFramedResponse(errorResponse);
  }
});
