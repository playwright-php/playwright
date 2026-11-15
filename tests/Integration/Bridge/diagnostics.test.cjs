const { test } = require('node:test');
const assert = require('node:assert/strict');
const { mkdtempSync, readFileSync, rmSync } = require('node:fs');
const { tmpdir } = require('node:os');
const path = require('node:path');
const { Logger } = require('../../../bin/lib/core');

test('diagnostics keep command metadata and omit arbitrary payloads', () => {
  const directory = mkdtempSync(path.join(tmpdir(), 'playwright-log-'));
  try {
    const logger = new Logger();
    logger.enabled = true;
    logger.logPath = path.join(directory, 'dispatch.log');
    logger.info('Dispatching command', {
      action: 'locator.fill', requestId: 1, value: 'fill-secret',
      error: 'Call log: Authorization: header-secret',
      url: 'https://user:password@example.test?token=url-secret',
      callbackResult: { cookies: [{ name: 'session', value: 'cookie-secret' }] },
    });
    const log = readFileSync(logger.logPath, 'utf8');
    assert.match(log, /locator.fill/);
    for (const secret of ['fill-secret', 'header-secret', 'url-secret', 'cookie-secret', 'password']) assert.equal(log.includes(secret), false);
  } finally {
    rmSync(directory, { recursive: true, force: true });
  }
});

