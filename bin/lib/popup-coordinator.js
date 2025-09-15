const { logger } = require('./core');

/**
 * Popup-specific coordination logic
 */
class PopupCoordinator {
    /**
     * Create coordination phases for page popup operations
     */
    static createPagePopupPhases(dependencies) {
        const { pages, pageContexts, setupPageEventListeners, generateId } = dependencies;
        
        return [
            {
                name: 'setupListener',
                handler: async (data) => {
                    const { page, timeout, requestId } = data;
                    
                    logger.info('Setting up popup listener', { 
                        pageId: data.pageId,
                        timeout,
                        requestId 
                    });
                    
                    // Get the context from the page and listen for new pages (popups)
                    const context = page.context();
                    const popupPromise = context.waitForEvent('page', { timeout });
                    
                    return { 
                        popupPromise, 
                        context,
                        listenerId: generateId('popup_listener'),
                        setupTime: Date.now()
                    };
                },
                waitForCallback: true,
                callbackType: 'readyForAction'
            },
            {
                name: 'waitForPopup',
                handler: async (data) => {
                    const { popupPromise, requestId, pageId } = data;
                    
                    logger.info('Waiting for popup event', { requestId, pageId });
                    
                    try {
                        // Now actually wait for the popup event
                        const popup = await popupPromise;
                        const popupPageId = generateId('page');
                        
                        logger.info('Popup created successfully', { 
                            requestId,
                            popupPageId,
                            originalPageId: pageId
                        });
                        
                        // Register popup page
                        pages.set(popupPageId, popup);
                        
                        // Inherit context from parent page
                        const contextId = pageContexts.get(pageId);
                        if (contextId) {
                            pageContexts.set(popupPageId, contextId);
                        }

                        // Setup event listeners for popup
                        if (setupPageEventListeners) {
                            setupPageEventListeners(popup, popupPageId);
                        }
                        
                        return { popupPageId, popup };
                    } catch (error) {
                        logger.error('Popup wait failed', { 
                            requestId, 
                            error: error.message 
                        });
                        
                        // Return null to indicate failure
                        return { popupPageId: null };
                    }
                },
                waitForCallback: false
            }
        ];
    }

    /**
     * Create coordination phases for context popup operations
     */
    static createContextPopupPhases(dependencies) {
        const { pages, pageContexts, setupPageEventListeners, generateId } = dependencies;
        
        return [
            {
                name: 'setupListener',
                handler: async (data) => {
                    const { context, timeout, requestId } = data;
                    
                    logger.info('Setting up context popup listener', { 
                        contextId: data.contextId,
                        timeout,
                        requestId 
                    });
                    
                    // Start listening for new page (popup) but don't wait yet
                    const popupPromise = context.waitForEvent('page', { timeout });
                    
                    return { 
                        popupPromise, 
                        listenerId: generateId('context_popup_listener'),
                        setupTime: Date.now()
                    };
                },
                waitForCallback: true,
                callbackType: 'readyForAction'
            },
            {
                name: 'waitForPopup',
                handler: async (data) => {
                    const { popupPromise, requestId, contextId } = data;
                    
                    logger.info('Waiting for context popup event', { requestId, contextId });
                    
                    try {
                        // Now actually wait for the popup event
                        const popup = await popupPromise;
                        const popupPageId = generateId('page');
                        
                        logger.info('Context popup created successfully', { 
                            requestId,
                            popupPageId,
                            contextId
                        });
                        
                        // Register popup page
                        pages.set(popupPageId, popup);
                        pageContexts.set(popupPageId, contextId);

                        // Setup event listeners for popup
                        if (setupPageEventListeners) {
                            setupPageEventListeners(popup, popupPageId);
                        }
                        
                        return { popupPageId, popup };
                    } catch (error) {
                        logger.error('Context popup wait failed', { 
                            requestId, 
                            error: error.message 
                        });
                        
                        // Return null to indicate failure
                        return { popupPageId: null };
                    }
                },
                waitForCallback: false
            }
        ];
    }

    /**
     * Validate popup coordination result
     */
    static validatePopupResult(result) {
        if (!result || typeof result !== 'object') {
            return { valid: false, error: 'Invalid result object' };
        }
        
        if (!result.popupPageId || typeof result.popupPageId !== 'string') {
            return { valid: false, error: 'Missing or invalid popupPageId' };
        }
        
        return { valid: true };
    }
}

module.exports = {
    PopupCoordinator
};