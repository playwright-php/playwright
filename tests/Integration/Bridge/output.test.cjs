const { test } = require('node:test');
const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const { FramedWriter } = require('../../../bin/lib/core');

test('event buffering is bounded while large command results remain supported', () => {
  const callbacks = [];
  const output = new FramedWriter({ write: (_frame, callback) => callbacks.push(callback) });
  const content = 'x'.repeat(10 * 1024 * 1024);
  output.write({ event: 'console', params: { text: content } });
  assert.throws(() => output.write({ event: 'console', params: { text: content } }), /queue limit/);
  assert.doesNotThrow(() => output.write({ binary: content + content }));
  callbacks.splice(0).forEach(callback => callback());
  assert.doesNotThrow(() => output.write({ event: 'console', params: { text: content } }));
});

test('event overflow terminates the bridge explicitly', () => {
  const core = require.resolve('../../../bin/lib/core');
  const result = spawnSync(process.execPath, ['-e', `
    process.stdout.write = () => false;
    const { sendFramedResponse } = require(${JSON.stringify(core)});
    const event = { event: 'console', params: { text: 'x'.repeat(10 * 1024 * 1024) } };
    sendFramedResponse(event);
    sendFramedResponse(event);
  `], { encoding: 'utf8' });
  assert.equal(result.status, 1);
  assert.match(result.stderr, /event queue limit/);
});

