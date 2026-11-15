const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const { PageHandler } = require('../../../bin/lib/handlers');
const { PlaywrightServer } = require('../../../bin/playwright-server');

test('Chromium arguments keep origin checks and caller sandbox choices', () => {
  const server = new PlaywrightServer();
  const options = server.buildLaunchOptions({ browser: 'chromium', options: { chromiumSandbox: true } });
  assert.equal(options.args.includes('--disable-web-security'), false);
  assert.equal(options.args.includes('--no-sandbox'), false);
  assert.equal(options.chromiumSandbox, true);
  assert.equal(server.buildLaunchOptions({ options: { args: ['--disable-web-security'] } }).args.includes('--disable-web-security'), true);
});

test('browser predicates stay in their browser realm and keep arguments', async () => {
  delete globalThis.hostPredicateMarker;
  const page = {
    waitForFunction: async (callback, payload, options) => {
      assert.deepEqual(options, { polling: 20, timeout: 1000 });
      const result = vm.runInNewContext(`(${callback.toString()})(payload)`, { payload, document: { title: 'Ready' } });
      assert.equal(result, true);
    },
  };
  const handler = new PageHandler();
  await handler.waitForFunction(page, {
    pageFunction: '(globalThis.hostPredicateMarker = true, arg => document.title === arg)',
    arg: 'Ready', options: { polling: 20, timeout: 1000 },
  });
  assert.equal(globalThis.hostPredicateMarker, undefined);
});

