const { logger } = require('./core');

/**
 * Coordinates multi-phase async operations between PHP client and JavaScript server
 */
class AsyncCommandCoordinator {
    constructor() {
        this.pendingCallbacks = new Map();
        this.commandSequences = new Map();
        this.generateId = this.generateId.bind(this);
        this.cleanupInterval = null;
    }

    /**
     * Register a multi-phase command that requires coordination
     */
    registerAsyncCommand(requestId, phases) {
        logger.info('Registering async command', { requestId, phaseCount: phases.length });
        
        this.commandSequences.set(requestId, {
            phases,
            currentPhase: 0,
            executing: false,
            phaseData: {},
            startTime: Date.now(),
            requestId
        });
        
        this.startCleanupIfNeeded();
    }

    /**
     * Execute next phase of async command
     */
    async executeNextPhase(requestId, phaseData = {}) {
        const sequence = this.commandSequences.get(requestId);
        if (!sequence) {
            throw new Error(`Unknown command sequence: ${requestId}`);
        }
        
        const phase = sequence.phases[sequence.currentPhase];
        sequence.phaseData = { ...sequence.phaseData, ...phaseData };
        
        logger.info('Executing phase', { 
            requestId, 
            phase: phase.name, 
            currentPhase: sequence.currentPhase 
        });
        
        sequence.executing = true;
        try {
            const result = await phase.handler(sequence.phaseData);

            if (result && typeof result === 'object') {
                sequence.phaseData = { ...sequence.phaseData, ...result };
            }

            if (phase.waitForCallback) {
                logger.info('Phase requires callback', { 
                    requestId, 
                    phase: phase.name, 
                    callbackType: phase.callbackType 
                });

                return { 
                    type: 'callback',
                    requestId,
                    callbackType: phase.callbackType,
                    data: result || {}
                };
            } else {
                sequence.currentPhase++;

                if (sequence.currentPhase >= sequence.phases.length) {
                    logger.info('Command sequence completed', { 
                        requestId, 
                        totalPhases: sequence.phases.length,
                        elapsed: Date.now() - sequence.startTime 
                    });

                    this.removeSequence(requestId);
                    return { completed: true, result: sequence.phaseData };
                } else {
                    logger.info('Advancing to next phase', { 
                        requestId, 
                        nextPhase: sequence.currentPhase 
                    });

                    return await this.executeNextPhase(requestId);
                }
            }
        } catch (error) {
            logger.error('Phase execution failed', { 
                requestId, 
                phase: phase.name, 
                error: error.message 
            });
            
            this.removeSequence(requestId);
            throw error;
        } finally {
            sequence.executing = false;
        }
    }

    /**
     * Continue execution after callback completion
     */
    async continueAfterCallback(requestId, callbackResult = {}) {
        const sequence = this.commandSequences.get(requestId);
        if (!sequence) {
            throw new Error(`Unknown command sequence for callback: ${requestId}`);
        }
        
        logger.info('Continuing after callback', { requestId, callbackResult });
        
        // Merge callback result into phase data
        sequence.phaseData = { ...sequence.phaseData, ...callbackResult };
        
        // Advance to next phase
        sequence.currentPhase++;
        
        if (sequence.currentPhase >= sequence.phases.length) {
            // All phases complete
            this.removeSequence(requestId);
            return { completed: true, result: sequence.phaseData };
        } else {
            // Continue to next phase
            return await this.executeNextPhase(requestId);
        }
    }

    // Deprecated callback pathway removed; coordination uses return values instead

    /**
     * Check if request is waiting for callback
     */
    isWaitingForCallback(requestId) {
        const sequence = this.commandSequences.get(requestId);
        if (!sequence) return false;
        
        const phase = sequence.phases[sequence.currentPhase];
        return phase && phase.waitForCallback;
    }

    /**
     * Get active command sequences (for debugging)
     */
    getActiveSequences() {
        const active = [];
        for (const [requestId, sequence] of this.commandSequences.entries()) {
            active.push({
                requestId,
                currentPhase: sequence.currentPhase,
                totalPhases: sequence.phases.length,
                phaseName: sequence.phases[sequence.currentPhase]?.name,
                elapsed: Date.now() - sequence.startTime
            });
        }
        return active;
    }

    /**
     * Clean up timed out sequences
     */
    cleanupTimedOutSequences(maxAge = 60000) {
        const now = Date.now();
        for (const [requestId, sequence] of this.commandSequences.entries()) {
            const timeout = sequence.phaseData.timeout;
            // Native handlers own their timeout and listener cleanup. Do not
            // discard a running phase or impose a limit on an unlimited wait.
            if (sequence.executing || timeout === 0) continue;

            // Allow the operation's full budget, then a grace period for PHP
            // to finish its callback and collect the result (including errors).
            const expiryAge = typeof timeout === 'number' && Number.isFinite(timeout) && timeout > 0
                ? timeout + maxAge
                : maxAge;
            if (now - sequence.startTime > expiryAge) {
                logger.warn('Cleaning up timed out sequence', { 
                    requestId, 
                    elapsed: now - sequence.startTime 
                });
                this.removeSequence(requestId);
            }
        }
    }

    removeSequence(requestId) {
        this.commandSequences.delete(requestId);
        if (this.commandSequences.size === 0 && this.cleanupInterval !== null) {
            clearInterval(this.cleanupInterval);
            this.cleanupInterval = null;
        }
    }

    generateId(prefix = 'coord') {
        return `${prefix}_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
    }
    
    /**
     * Start cleanup interval only when needed
     */
    startCleanupIfNeeded() {
        if (this.cleanupInterval === null) {
            this.cleanupInterval = setInterval(() => {
                this.cleanupTimedOutSequences();
            }, 60000);
        }
    }
}

/**
 * Global coordinator instance
 */
const globalCoordinator = new AsyncCommandCoordinator();

module.exports = {
    AsyncCommandCoordinator,
    globalCoordinator
};
