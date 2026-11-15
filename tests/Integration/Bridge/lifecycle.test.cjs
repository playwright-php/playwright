const { test } = require('node:test');
const assert = require('node:assert/strict');
const { EventEmitter } = require('node:events');
const { LspFraming } = require('../../../bin/lib/core');
const { PlaywrightServer } = require('../../../bin/playwright-server');

test('response identifiers are reused and released with their owning page', () => {
  const server = new PlaywrightServer();
  const page = new EventEmitter();
  const response = { url: () => 'https://example.test', status: () => 200, statusText: () => 'OK', headers: () => ({}), frame: () => ({ page: () => page }) };
  const id = server.registerPage(page, 'context_1');
  const first = server.serializeResponse(response);
  assert.equal(server.serializeResponse(response).responseId, first.responseId);
  assert.equal(server.responses.size, 1);
  const write = process.stdout.write;
  let output = '';
  process.stdout.write = (data, callback) => { output += data; callback?.(); return true; };
  try { page.emit('close'); } finally { process.stdout.write = write; }
  assert.equal(server.responses.size, 0);
  assert.equal(server.responsePages.size, 0);
  assert.equal(server.pages.has(id), false);
  assert.equal(server.pageContexts.has(id), false);
  const events = LspFraming.decode(Buffer.from(output));
  assert.deepEqual(events.messages.map(message => JSON.parse(message).event), ['close', 'pageClosed']);
});

for (const mode of ['page', 'context']) {
  test(`${mode} popup coordination does not register an already closed popup`, async () => {
    const { PopupCoordinator } = require('../../../bin/lib/popup-coordinator');
    const pages = new Map();
    const pageContexts = new Map([['owner', 'context']]);
    const phases = PopupCoordinator.createPopupPhases(mode, { pages, pageContexts, generateId: () => 'popup' });
    await assert.rejects(phases[1].handler({ popupPromise: Promise.resolve({ isClosed: () => true }), requestId: 'wait', pageId: 'owner', contextId: 'context' }), /Popup closed/);
    assert.equal(pages.size, 0);
    assert.equal(pageContexts.size, 1);
  });
}
