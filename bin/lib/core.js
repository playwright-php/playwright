const fs = require('fs');
const path = require('path');

const DEBUG = process.env.PLAYWRIGHT_DEBUG === 'true';
const DEBUG_LEVEL = process.env.PLAYWRIGHT_DEBUG_LEVEL || 'info';
const DEBUG_LOG_FILE = process.env.PLAYWRIGHT_DEBUG_LOG || 'debug-dispatch.log';
const DEBUG_LOG_DIR = process.env.PLAYWRIGHT_DEBUG_DIR || process.cwd();

class Logger {
  constructor() {
    this.logPath = path.join(DEBUG_LOG_DIR, DEBUG_LOG_FILE);
    this.enabled = DEBUG;
    this.level = DEBUG_LEVEL.toLowerCase();
    this.levels = { 'error': 0, 'warn': 1, 'info': 2, 'debug': 3 };
    this.currentLevelPriority = this.levels[this.level] ?? this.levels['info'];
    this.logStream = null;
    this.initConsoleLogging();
  }

  initConsoleLogging() {
    if (DEBUG) {
      this.logStream = fs.createWriteStream('playwright-server.log', {flags: 'a'});
      const originalConsoleLog = console.log;
      console.log = (d) => {
        this.logStream.write(new Date().toISOString() + ' - ' + d + '\n');
        originalConsoleLog(d);
      };
    }
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
    } catch (error) {}
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
      command: command?.action, error: message, stack: error.stack, requestId, pageId: command?.pageId
    });
    return {
      requestId, error: message,
      stack: process.env.PLAYWRIGHT_DEBUG === 'true' ? error.stack : undefined,
      command: command?.action
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

class BaseHandler {
  constructor(deps = {}) { Object.assign(this, deps); }
  validateResource(resourceMap, resourceId, resourceType) {
    const resource = resourceMap.get(resourceId);
    if (!resource) {
      logger.error(`${resourceType} not found`, { 
        resourceId,
        availableIds: Array.from(resourceMap.keys()),
        totalResources: resourceMap.size,
        mapType: resourceMap.constructor.name
      });
      throw new Error(`${resourceType} not found: ${resourceId}`);
    }
    return resource;
  }
  wrapResult(value) { return value === undefined || value === null ? { success: true } : value; }
  createValueResult(value) { return { value }; }
  async executeWithRegistry(registry, method, ...args) { return await registry.execute(method, ...args); }
}

class PromiseUtils {
  static wrapValue(promise) { return promise.then(value => ({ value })); }
  static wrapValues(promise) { return promise.then(values => ({ values })); }
  static wrapBinary(promise) { return promise.then(buffer => ({ binary: buffer.toString('base64') })); }
}

class FrameUtils {
  static resolve(page, chain) {
    if (!chain || chain === ':root') return page;
    const parts = String(chain).split(' >> ').filter(Boolean);
    let fl = page;
    for (const part of parts) fl = fl.frameLocator(part);
    return fl;
  }

  static async evaluateInFrame(page, frameLocator, isMainFrame, expression) {
    if (isMainFrame) return await page.evaluate(expression);
    const loc = frameLocator.locator('html');
    const count = await loc.count();
    if (count === 0) return null;
    return await loc.evaluate(expression);
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
  static async setupContextRoute(context, command, generateId, routes, extractRequestData, sendFramedResponse) {
    await context.route(command.url, async (route) => {
      const routeId = generateId('route');
      routes.set(routeId, { route, contextId: command.contextId });
      const req = route.request();
      const requestData = extractRequestData(req);
      logger.info('ROUTE SETUP', { url: requestData.url, method: requestData.method, type: 'context' });
      sendFramedResponse({ objectId: command.contextId, event: 'route', params: { routeId, request: requestData } });
    });
    return { success: true };
  }

  static async setupPageRoute(page, command, generateId, routes, extractRequestData, sendFramedResponse, routeCounter) {
    await page.route(command.url, async (route) => {
      const routeId = `route_${++routeCounter.value}`;
      routes.set(routeId, { route, contextId: command.pageId });
      const req = route.request();
      logger.info('ROUTE SETUP', { url: req.url(), method: req.method(), type: 'page' });
      sendFramedResponse({ objectId: command.pageId, event: 'route', params: { routeId, request: extractRequestData(req) } });
    });
  }
}

const logger = new Logger();

module.exports = {
  logger, ErrorHandler, LspFraming, sendFramedResponse,
  CommandRegistry, BaseHandler, PromiseUtils, FrameUtils, RouteUtils
};
