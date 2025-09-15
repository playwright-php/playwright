const { logger } = require('./core');

/**
 * Popup-specific coordination logic
 */
class PopupCoordinator {
    /**
     * Create coordination phases for page popup operations
     */
    static createPopupPhases(mode, dependencies) {
        const { pages, pageContexts, setupPageEventListeners, generateId } = dependencies;
        const isPage = mode === 'page';

        return [
            {
                name: 'setupListener',
                handler: async (data) => {
                    const { timeout, requestId } = data;
                    const idKey = isPage ? 'pageId' : 'contextId';
                    const src = isPage ? data.page : data.context;
                    const label = isPage ? 'popup' : 'context popup';

                    logger.info(`Setting up ${label} listener`, { [idKey]: data[idKey], timeout, requestId });

                    const popupPromise = isPage
                        ? src.waitForEvent('popup', { timeout })
                        : src.waitForEvent('page', { timeout });

                    return {
                        popupPromise,
                        listenerId: generateId(isPage ? 'popup_listener' : 'context_popup_listener'),
                        setupTime: Date.now()
                    };
                },
                waitForCallback: true,
                callbackType: 'readyForAction'
            },
            {
                name: 'waitForPopup',
                handler: async (data) => {
                    const { popupPromise, requestId } = data;
                    const idKey = isPage ? 'pageId' : 'contextId';
                    const ownerId = data[idKey];

                    logger.debug(`Waiting for ${isPage ? 'popup' : 'context popup'} event`, { requestId, [idKey]: ownerId });

                    try {
                        const popup = await popupPromise;
                        if (!popup) {
                            logger.error('Popup is null or undefined', { requestId, ownerId });
                            return { popupPageId: null };
                        }
                        const popupPageId = generateId('page');

                        // Register popup and context mapping
                        pages.set(popupPageId, popup);
                        if (isPage) {
                            const contextId = pageContexts.get(ownerId);
                            if (contextId) pageContexts.set(popupPageId, contextId);
                        } else {
                            pageContexts.set(popupPageId, ownerId);
                        }

                        if (setupPageEventListeners) setupPageEventListeners(popup, popupPageId);
                        return { popupPageId, popup };
                    } catch (error) {
                        logger.error(`${isPage ? 'Popup' : 'Context popup'} wait failed`, { requestId, error: error.message });
                        return { popupPageId: null };
                    }
                },
                waitForCallback: false
            }
        ];
    }

    // Legacy wrappers removed pre-1.0; use createPopupPhases('page'|'context', deps)

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
