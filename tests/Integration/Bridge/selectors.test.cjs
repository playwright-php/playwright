const { test } = require('node:test');
const assert = require('node:assert/strict');
const { EventEmitter } = require('node:events');

test('native frame references expire on detachment and page closure', () => {
  const { NativeFrames } = require('../../../bin/lib/frames');
  const page = new EventEmitter();
  page.isClosed = () => false;
  const frame = { page: () => page, isDetached: () => false };
  const reference = NativeFrames.bind(frame, 'iframe');
  assert.equal(NativeFrames.bind(frame, 'iframe'), reference);
  assert.equal(NativeFrames.resolve(page, reference).frame, frame);
  assert.equal(NativeFrames.bind(frame, 'iframe[name="changed"]'), reference);
  assert.equal(NativeFrames.resolve(page, `iframe[name=${JSON.stringify(reference)}]`), null);
  assert.equal(NativeFrames.resolve(page, `iframe/* ${reference} */`), null);
  assert.equal(NativeFrames.resolve(page, `iframe[name="${reference}\\`), null);
  assert.equal(NativeFrames.resolve(page, reference + ' >> iframe').remainder, ' >> iframe');
  page.emit('framedetached', frame);
  assert.equal(NativeFrames.detached(page, reference), true);
  assert.throws(() => NativeFrames.resolve(page, reference), /expired/);
  const other = { page: () => page, isDetached: () => false };
  const next = NativeFrames.bind(other, 'iframe#other');
  page.emit('close');
  assert.throws(() => NativeFrames.resolve(page, next), /expired/);
});
