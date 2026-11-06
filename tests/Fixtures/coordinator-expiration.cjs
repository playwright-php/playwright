const assert = require('node:assert/strict');
const { test } = require('node:test');
const { AsyncCommandCoordinator } = require('../../bin/lib/coordination');

async function withClock(run) {
    const originalNow = Date.now;
    let now = originalNow();
    Date.now = () => now;
    const coordinator = new AsyncCommandCoordinator();

    try {
        await run(coordinator, (milliseconds) => { now += milliseconds; });
    } finally {
        Date.now = originalNow;
        clearInterval(coordinator.cleanupInterval);
    }
}

async function waitForCallback(coordinator, timeout) {
    coordinator.registerAsyncCommand('request', [
        { name: 'listen', handler: async () => ({}), waitForCallback: true },
        { name: 'finish', handler: async () => ({ done: true }) },
    ]);
    await coordinator.executeNextPhase('request', { timeout });
}

test('a callback can continue after 60 seconds within its operation timeout', () => withClock(async (coordinator, advance) => {
    await waitForCallback(coordinator, 180000);
    advance(120001);
    coordinator.cleanupTimedOutSequences();

    assert.equal(coordinator.isWaitingForCallback('request'), true);
    const result = await coordinator.continueAfterCallback('request');
    assert.equal(result.completed, true);
    assert.equal(result.result.done, true);
}));

test('an unlimited operation is not expired by the fallback age', () => withClock(async (coordinator, advance) => {
    await waitForCallback(coordinator, 0);
    advance(3600000);
    coordinator.cleanupTimedOutSequences();

    assert.equal(coordinator.isWaitingForCallback('request'), true);
    assert.equal((await coordinator.continueAfterCallback('request')).completed, true);
}));

test('an abandoned callback expires after its operation timeout and callback grace', () => withClock(async (coordinator, advance) => {
    await waitForCallback(coordinator, 180000);
    advance(180001);
    coordinator.cleanupTimedOutSequences();
    assert.equal(coordinator.isWaitingForCallback('request'), true);

    advance(60000);
    coordinator.cleanupTimedOutSequences();
    assert.deepEqual(coordinator.getActiveSequences(), []);
    assert.equal(coordinator.cleanupInterval, null);
}));

test('commands without an operation timeout retain the fallback expiration', () => withClock(async (coordinator, advance) => {
    await waitForCallback(coordinator, undefined);
    advance(60001);
    coordinator.cleanupTimedOutSequences();

    assert.deepEqual(coordinator.getActiveSequences(), []);
    assert.equal(coordinator.cleanupInterval, null);
}));

test('cleanup does not delete a phase whose handler is still running', () => withClock(async (coordinator, advance) => {
    let finish;
    coordinator.registerAsyncCommand('request', [{
        name: 'wait',
        handler: () => new Promise((resolve) => { finish = resolve; }),
    }]);
    const pending = coordinator.executeNextPhase('request', { timeout: 1000 });
    advance(120001);
    coordinator.cleanupTimedOutSequences();

    try {
        assert.equal(coordinator.getActiveSequences().length, 1);
    } finally {
        finish({ done: true });
        await pending;
    }
    assert.deepEqual(coordinator.getActiveSequences(), []);
    assert.equal(coordinator.cleanupInterval, null);
}));

test('the cleanup timer stops after completion and restarts for new work', () => withClock(async (coordinator) => {
    await waitForCallback(coordinator, 1000);
    assert.notEqual(coordinator.cleanupInterval, null);
    await coordinator.continueAfterCallback('request');
    assert.equal(coordinator.cleanupInterval, null);

    await waitForCallback(coordinator, 1000);
    assert.notEqual(coordinator.cleanupInterval, null);
    await coordinator.continueAfterCallback('request');
    assert.equal(coordinator.cleanupInterval, null);
}));

test('a failed phase releases its sequence and cleanup timer', () => withClock(async (coordinator) => {
    const failure = new Error('Native operation failed');
    coordinator.registerAsyncCommand('request', [{ name: 'fail', handler: async () => { throw failure; } }]);

    await assert.rejects(coordinator.executeNextPhase('request'), (error) => error === failure);
    assert.deepEqual(coordinator.getActiveSequences(), []);
    assert.equal(coordinator.cleanupInterval, null);
}));
