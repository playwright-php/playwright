const fs = require('fs');
const path = require('path');
const { NativeFrames } = require('./frames');

const DEBUG = process.env.PLAYWRIGHT_DEBUG === 'true';
const DEBUG_LEVEL = process.env.PLAYWRIGHT_DEBUG_LEVEL || 'info';
const DEBUG_LOG_FILE = process.env.PLAYWRIGHT_DEBUG_LOG || 'debug-dispatch.log';
const DEBUG_LOG_DIR = process.env.PLAYWRIGHT_DEBUG_DIR || process.cwd();
const MAX_NAVIGATION_REDIRECTS = 10;

class Logger {
  constructor() {
    this.logPath = path.join(DEBUG_LOG_DIR, DEBUG_LOG_FILE);
    this.enabled = DEBUG;
    this.level = DEBUG_LEVEL.toLowerCase();
    this.levels = { error: 0, warn: 1, info: 2, debug: 3 };
    this.currentLevelPriority = this.levels[this.level] ?? this.levels.info;
    this.logStream = null;
    this.initConsoleLogging();
  }

  initConsoleLogging() {
    if (DEBUG) {
      this.logStream = fs.createWriteStream('playwright-server.log', { flags: 'a' });
      const originalConsoleLog = console.log;
      console.log = (d) => {
        this.logStream.write(new Date().toISOString() + ' - ' + d + '\n');
        originalConsoleLog(d);
      };
    }
  }

  shouldLog(level) {
    if (!this.enabled) return false;
    const levelPriority = this.levels[level] ?? this.levels.info;
    return levelPriority <= this.currentLevelPriority;
  }

  log(message, data = null, level = 'info') {
    if (!this.shouldLog(level)) return;
    const timestamp = new Date().toISOString();
    let logMessage = `[${timestamp}] [${level.toUpperCase()}] ${message}`;
    if (data) {
      if (typeof data === 'object') {
        try {
          logMessage += `\n${JSON.stringify(data, null, 2)}`;
        } catch (e) {
          logMessage += ` [Object: ${String(data)}]`;
        }
      } else {
        logMessage += ` ${data}`;
      }
    }
    logMessage += '\n';
    try {
      fs.appendFileSync(this.logPath, logMessage);
    } catch (_error) {}
  }

  error(message, data = null) { this.log(message, data, 'error'); }
  warn(message, data = null) { this.log(message, data, 'warn'); }
  info(message, data = null) { this.log(message, data, 'info'); }
  debug(message, data = null) { this.log(message, data, 'debug'); }
  separator(title) { this.log(`=== ${title} ===`, null, 'info'); }
  close() { if (this.logStream) this.logStream.end(); }
}

class ErrorHandler {
  static formatError(error, command, requestId) {
    const message = error instanceof Error ? error.message : String(error);
    logger.error('Command execution failed', {
      command: command?.action,
      error: message,
      stack: error.stack,
      requestId,
      pageId: command?.pageId,
    });
    return {
      requestId,
      error: message,
      errorName: error.name,
      stack: process.env.PLAYWRIGHT_DEBUG === 'true' ? error.stack : undefined,
      command: command?.action,
    };
  }

  static async safeExecute(fn, context = {}) {
    try {
      return await fn();
    } catch (error) {
      logger.error('Safe execution failed', { error: error.message, stack: error.stack, context });
      throw error;
    }
  }

  static wrapHandler(handler) {
    return async (...args) => {
      try {
        return await handler(...args);
      } catch (error) {
        logger.error('Handler error', { error: error.message, stack: error.stack, handler: handler.name });
        throw error;
      }
    };
  }
}

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
    if (contentLength === null) throw new Error('Missing or invalid Content-Length header');
    if (buffer.length < contentStart + contentLength) return null;
    const content = buffer.slice(contentStart, contentStart + contentLength).toString('utf8');
    const remaining = buffer.slice(contentStart + contentLength);
    return [content, remaining];
  }

  static parseContentLength(headers) {
    const lines = headers.split('\r\n');
    for (const line of lines) {
      const match = line.trim().match(/^Content-Length:\s*(\d+)$/i);
      if (match) return parseInt(match[1], 10);
    }
    return null;
  }
}

const sendFramedResponse = (data) => {
  const json = JSON.stringify(data);
  const framed = LspFraming.encode(json);
  process.stdout.write(framed);
};

class CommandRegistry {
  constructor() { this.handlers = new Map(); }
  register(name, handler) { this.handlers.set(name, handler); return this; }
  async execute(name, ...args) {
    const handler = this.handlers.get(name);
    if (!handler) throw new Error(`Unknown action: ${name}`);
    return await handler(...args);
  }
  has(name) { return this.handlers.has(name); }
  static create(handlerMap) {
    const registry = new CommandRegistry();
    Object.entries(handlerMap).forEach(([name, handler]) => registry.register(name, handler));
    return registry;
  }
}

class NavigationRedirects extends Map {
  #waiters = new Map();

  async runUntilFulfilled(pageId, action) {
    let fulfill;
    const fulfilled = new Promise(resolve => { fulfill = resolve; });
    this.#waiters.set(pageId, fulfill);

    try {
      return await Promise.race([action(), fulfilled]);
    } finally {
      this.#forgetWaiter(pageId, fulfill);
    }
  }

  markFulfilled(pageId) {
    this.#waiters.get(pageId)?.();
    this.#waiters.delete(pageId);
  }

  #forgetWaiter(pageId, fulfill) {
    if (this.#waiters.get(pageId) !== fulfill) return;

    this.#waiters.delete(pageId);
  }
}

class BaseHandler {
  constructor(deps = {}) { Object.assign(this, deps); }
  validateResource(resourceMap, resourceId, resourceType) {
    const resource = resourceMap.get(resourceId);
    if (!resource) {
      logger.error(`${resourceType} not found`, {
        resourceId,
        availableIds: Array.from(resourceMap.keys()),
        totalResources: resourceMap.size,
        mapType: resourceMap.constructor.name,
      });
      throw new Error(`${resourceType} not found: ${resourceId}`);
    }
    return resource;
  }
  wrapResult(value) { return value === undefined || value === null ? { success: true } : value; }
  createValueResult(value) { return { value }; }
  storeHandle(handle) {
    const handleId = this.generateId('element');
    this.elementHandles.set(handleId, handle);
    return { handleId };
  }
  async executeWithRegistry(registry, method, ...args) { return await registry.execute(method, ...args); }
  async followNavigationRedirects(pageId, page, action, settlesOnRedirect = false) {
    let nextAction = action;
    let redirectCount = 0;
    let result;

    while (true) {
      this.navigationRedirects.delete(pageId);

      try {
        // from Playwright 1.64 only goto() settles on the redirect's 204; a goto() to the same url before it does fails with net::ERR_ABORTED
        result = await (settlesOnRedirect
          ? nextAction()
          : this.navigationRedirects.runUntilFulfilled(pageId, nextAction));
      } catch (error) {
        if (!this.navigationRedirects.has(pageId)) throw error;
      }

      const target = this.navigationRedirects.get(pageId);
      if (!target) return result;

      this.navigationRedirects.delete(pageId);
      if (++redirectCount > MAX_NAVIGATION_REDIRECTS) {
        throw new Error(`Too many navigation redirects for page ${pageId}`);
      }
      nextAction = () => page.goto(target);
      settlesOnRedirect = true;
    }
  }
}

class PromiseUtils {
  static wrapValue(promise) { return promise.then(value => ({ value })); }
  static wrapValues(promise) { return promise.then(values => ({ values })); }
  static wrapBinary(promise) { return promise.then(buffer => ({ binary: buffer.toString('base64') })); }
}

class FrameUtils {
  static resolve(page, chain) {
    if (!chain || chain === ':root') return page;
    const reference = NativeFrames.resolve(page, chain);
    const parts = this.parts(reference ? reference.remainder : chain);
    let fl = reference ? reference.frame : page;

    if (parts[0] === ':root') parts.shift();

    if (reference && /^nth=-?\d+$/.test(parts[0])) {
      const index = Number(parts.shift().slice(4));

      if (index !== 0 && index !== -1) fl = reference.frame.frameLocator('iframe:not(*)');
    }

    for (const part of parts) fl = fl.frameLocator(part);
    return fl;
  }

  static parts(chain) {
    const parts = [];

    for (const part of String(chain).split(' >> ').filter(Boolean)) {
      if (/^nth=-?\d+$/.test(part) && parts.length) {
        parts[parts.length - 1] += ' >> ' + part;
        continue;
      }

      parts.push(part);
    }

    return parts;
  }

  static async evaluateInFrame(page, frameLocator, isMainFrame, expression, arg) {
    if (isMainFrame) return await page.evaluate(expression, arg);
    const loc = frameLocator.locator('html');
    const count = await loc.count();
    if (count === 0) return null;
    if (typeof expression === 'function') return await loc.evaluate(expression);
    return await loc.evaluate(async (element, payload) => {
      const value = eval(`(${payload.expression})`);
      return typeof value === 'function'
        ? await value(payload.arg)
        : await eval(payload.expression);
    }, { expression, arg });
  }

  static async resolveNativeFrame(page, chain) {
    if (!chain || chain === ':root') return page.mainFrame();

    const reference = NativeFrames.resolve(page, chain);

    if (reference && !reference.remainder.trim()) return reference.frame;

    const locator = this.resolve(page, chain).owner();
    const elements = await locator.elementHandles();

    try {
      if (elements.length !== 1) throw new Error(`Frame selector must match one frame: ${chain}`);

      const frame = await elements[0].contentFrame();

      if (!frame) throw new Error(`Frame not found for selector: ${chain}`);

      return frame;
    } finally {
      await Promise.all(elements.map(element => element.dispose()));
    }
  }

  /** Page code can replace DOM helpers, so read attributes through Playwright. */
  static async selectorForNativeFrame(frame, mainFrame) {
    if (frame === mainFrame) return ':root';

    const chain = [];
    let current = frame;
    while (current && current !== mainFrame) {
      const element = await current.frameElement();
      let selector;

      try {
        const id = await element.getAttribute('id');
        const name = await element.getAttribute('name');
        const src = await element.getAttribute('src');
        selector = await this.selectorForElement(current.parentFrame(), element, { id, name, src });
      } finally {
        await element.dispose();
      }

      chain.unshift(selector);
      current = current.parentFrame();
    }

    return NativeFrames.bind(frame, chain.join(' >> '));
  }

  static escapeAttribute(value) {
    return value.replace(/[^a-zA-Z0-9_-]/gu, character => `\\${character.codePointAt(0).toString(16)} `);
  }

  static async selectorForElement(parent, element, { id, name, src }) {
    if (id && /^[a-zA-Z_][-_a-zA-Z0-9]*$/.test(id) && id !== '-') return `iframe#${id}`;

    if (id && !id.includes('\0')) return `iframe[id="${this.escapeAttribute(id)}"]`;

    if (name && !name.includes('\0')) return `iframe[name="${this.escapeAttribute(name)}"]`;

    if (src && !src.includes('\0')) return `iframe[src="${this.escapeAttribute(src)}"]`;

    const index = await parent.locator('iframe').evaluateAll((elements, target) => {
      for (let index = 0; index < elements.length; index++) {
        if (elements[index] === target) return index;
      }

      return -1;
    }, element);

    if (index < 0) throw new Error('Frame element has been detached.');

    return `iframe >> nth=${index}`;
  }

  static async waitForReadyState(evalInFrame, state, timeoutMs) {
    const deadline = Date.now() + timeoutMs;
    const target = state || 'load';
    const predicate = async () => {
      const readyState = await evalInFrame(() => document.readyState);
      if (readyState === null) return false;
      if (target === 'load') return readyState === 'complete';
      if (target === 'domcontentloaded') return readyState === 'interactive' || readyState === 'complete';
      if (target === 'networkidle') return readyState === 'complete';
      return readyState === 'complete';
    };
    while (Date.now() < deadline) {
      try { if (await predicate()) return; } catch {}
      await new Promise(r => setTimeout(r, 50));
    }
    throw new Error(`Timeout waiting for load state: ${state || 'load'}`);
  }
}

class RouteUtils {
  // Playwright removes a single registration only when unroute() gets the very
  // callback route() was given, so each one is kept under the PHP handlerId.
  static async setupRoute(owner, ownerId, url, generateId, routes, extractRequestData, sendFramedResponse, routeHandlers = null, handlerId = null) {
    const callback = async (route) => {
      const routeId = generateId('route');
      routes.set(routeId, { route, contextId: ownerId });
      const req = route.request();
      const requestData = extractRequestData(req);
      logger.info('ROUTE SETUP', { url: requestData.url || req.url(), method: requestData.method || req.method(), ownerId });
      const params = handlerId ? { routeId, handlerId, request: requestData } : { routeId, request: requestData };
      sendFramedResponse({ objectId: ownerId, event: 'route', params });
    };
    await owner.route(url, callback);
    if (handlerId) routeHandlers.set(handlerId, { ownerId, url, callback });
    return { success: true };
  }

  static async unroute(owner, ownerId, routeHandlers, handlerIds) {
    for (const handlerId of handlerIds) {
      const handler = routeHandlers.get(handlerId);
      if (!handler || handler.ownerId !== ownerId) continue;
      routeHandlers.delete(handlerId);
      await owner.unroute(handler.url, handler.callback);
    }
  }

  // unrouteAll drops the handlers inside Playwright, but the pending Route
  // objects we handed out stay in the map and can never be answered again.
  static forgetRoutes(routes, routeHandlers, ownerId) {
    for (const [routeId, info] of routes.entries()) {
      if (info?.contextId === ownerId) routes.delete(routeId);
    }
    for (const [handlerId, handler] of routeHandlers.entries()) {
      if (handler.ownerId === ownerId) routeHandlers.delete(handlerId);
    }
  }
}

const logger = new Logger();

module.exports = {
  logger, ErrorHandler, LspFraming, sendFramedResponse,
  CommandRegistry, NavigationRedirects, BaseHandler, PromiseUtils, FrameUtils, RouteUtils,
};
