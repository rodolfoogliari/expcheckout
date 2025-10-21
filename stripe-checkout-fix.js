/**
 * WooCommerce Stripe Express Checkout Enhanced Handler
 * 
 * This script ensures Stripe Express Checkout buttons render in the correct container
 * and provides a clean, minimalistic user experience.
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        targetContainerId: 'buttons-container',
        stripeElementClass: 'wc-stripe-express-checkout-element',
        heightObserverClass: 'p-HeightObserverProvider',
        maxRetries: 50,
        retryInterval: 100,
        debug: false // Set to true for console logging
    };

    // Logging helper
    function log(message, data = null) {
        if (config.debug) {
            if (data) {
                console.log(`[Stripe Fix] ${message}`, data);
            } else {
                console.log(`[Stripe Fix] ${message}`);
            }
        }
    }

    // Main initialization
    function initStripeButtonFix() {
        log('Initializing Stripe Express Checkout fix');
        
        // Wait for DOM to be fully loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupFix);
        } else {
            setupFix();
        }
    }

    // Setup the fix
    function setupFix() {
        const targetContainer = document.getElementById(config.targetContainerId);
        
        if (!targetContainer) {
            log('Target container not found, creating it');
            createTargetContainer();
        }
        
        // Method 1: Direct relocation
        relocateStripeElements();
        
        // Method 2: MutationObserver
        setupMutationObserver();
        
        // Method 3: Interval checking
        setupIntervalCheck();
        
        // Method 4: Event-based detection
        setupEventListeners();
    }

    // Create target container if it doesn't exist
    function createTargetContainer() {
        const addToCartButton = document.querySelector('.single_add_to_cart_button');
        
        if (addToCartButton) {
            const container = document.createElement('div');
            container.id = config.targetContainerId;
            container.className = 'express-checkout-container';
            
            const wrapper = document.createElement('div');
            wrapper.id = 'custom-payment-buttons-wrapper';
            wrapper.className = 'payment-buttons-container';
            wrapper.appendChild(container);
            
            // Insert after add to cart button
            addToCartButton.parentNode.insertBefore(wrapper, addToCartButton.nextSibling);
            
            log('Created target container');
        }
    }

    // Method 1: Direct relocation of Stripe elements
    function relocateStripeElements() {
        const targetContainer = document.getElementById(config.targetContainerId);
        if (!targetContainer) return;
        
        // Find Stripe elements
        const stripeElements = document.querySelectorAll(
            `.${config.stripeElementClass}, .${config.heightObserverClass}, iframe[src*="stripe"]`
        );
        
        stripeElements.forEach(element => {
            if (!targetContainer.contains(element)) {
                log('Relocating element', element);
                targetContainer.appendChild(element);
            }
        });
    }

    // Method 2: MutationObserver to catch dynamically added elements
    function setupMutationObserver() {
        const targetContainer = document.getElementById(config.targetContainerId);
        if (!targetContainer) return;
        
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        handleNewNode(node, targetContainer);
                    }
                });
            });
        });
        
        // Observe body for new nodes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        // Disconnect after 10 seconds to prevent performance issues
        setTimeout(() => {
            observer.disconnect();
            log('MutationObserver disconnected');
        }, 10000);
        
        log('MutationObserver setup complete');
    }

    // Handle newly added nodes
    function handleNewNode(node, targetContainer) {
        const isStripeElement = 
            node.classList?.contains(config.stripeElementClass) ||
            node.classList?.contains(config.heightObserverClass) ||
            (node.tagName === 'IFRAME' && node.src?.includes('stripe'));
        
        if (isStripeElement && !targetContainer.contains(node)) {
            log('Moving Stripe element via MutationObserver', node);
            
            // Wrap in requestAnimationFrame to ensure DOM is ready
            requestAnimationFrame(() => {
                targetContainer.appendChild(node);
                
                // Trigger custom event
                targetContainer.dispatchEvent(new CustomEvent('stripeElementMoved', {
                    detail: { element: node }
                }));
            });
        }
    }

    // Method 3: Interval checking for elements
    function setupIntervalCheck() {
        let retries = 0;
        
        const checkInterval = setInterval(() => {
            const targetContainer = document.getElementById(config.targetContainerId);
            
            if (targetContainer) {
                relocateStripeElements();
                
                // Check if Stripe has initialized
                const stripeElement = targetContainer.querySelector(`.${config.stripeElementClass}`);
                if (stripeElement) {
                    clearInterval(checkInterval);
                    log('Stripe element found and positioned correctly');
                    
                    // Apply final styling
                    applyFinalStyling(targetContainer);
                }
            }
            
            retries++;
            if (retries >= config.maxRetries) {
                clearInterval(checkInterval);
                log('Max retries reached');
            }
        }, config.retryInterval);
    }

    // Method 4: Listen for Stripe-specific events
    function setupEventListeners() {
        // Listen for Stripe initialization events
        document.addEventListener('stripe_express_checkout_init', () => {
            log('Stripe initialization detected');
            setTimeout(relocateStripeElements, 100);
        });
        
        // Listen for WooCommerce events
        $(document.body).on('init_checkout updated_checkout', () => {
            log('WooCommerce checkout event detected');
            relocateStripeElements();
        });
        
        // Listen for custom events
        document.addEventListener('stripeElementMoved', (e) => {
            log('Stripe element moved event', e.detail);
        });
    }

    // Apply final styling to ensure proper display
    function applyFinalStyling(container) {
        // Ensure container is visible
        container.style.display = 'block';
        container.style.visibility = 'visible';
        container.style.width = '100%';
        
        // Ensure child elements are properly sized
        const children = container.querySelectorAll('*');
        children.forEach(child => {
            if (child.tagName === 'IFRAME' || child.classList?.contains(config.stripeElementClass)) {
                child.style.width = '100%';
                child.style.maxWidth = '100%';
                child.style.position = 'relative';
            }
        });
        
        log('Final styling applied');
    }

    // Enhanced fix for specific Stripe issues
    function fixStripeSpecificIssues() {
        // Fix for Stripe Payment Request Button
        const prButton = document.querySelector('.payment-request-button');
        if (prButton) {
            const targetContainer = document.getElementById(config.targetContainerId);
            if (targetContainer && !targetContainer.contains(prButton)) {
                targetContainer.appendChild(prButton);
                log('Moved Payment Request Button');
            }
        }
        
        // Fix for duplicate buttons
        const duplicates = document.querySelectorAll('.wc-stripe-express-checkout-button-wrapper');
        duplicates.forEach((dup, index) => {
            if (index > 0) {
                dup.style.display = 'none';
                log('Hidden duplicate button wrapper');
            }
        });
    }

    // Clean up orphaned elements
    function cleanupOrphanedElements() {
        // Remove any Stripe elements outside the target container
        const targetContainer = document.getElementById(config.targetContainerId);
        if (!targetContainer) return;
        
        const orphanedElements = document.querySelectorAll(
            `body > .${config.stripeElementClass}, 
             body > .${config.heightObserverClass}, 
             body > iframe[src*="stripe"]`
        );
        
        orphanedElements.forEach(element => {
            log('Removing orphaned element', element);
            element.remove();
        });
    }

    // Add custom CSS dynamically if needed
    function injectCustomStyles() {
        if (document.getElementById('stripe-fix-styles')) return;
        
        const styles = `
            #${config.targetContainerId} {
                width: 100% !important;
                display: block !important;
                position: relative !important;
                z-index: 100 !important;
            }
            
            #${config.targetContainerId} iframe,
            #${config.targetContainerId} .${config.stripeElementClass} {
                width: 100% !important;
                max-width: 100% !important;
                position: relative !important;
            }
        `;
        
        const styleElement = document.createElement('style');
        styleElement.id = 'stripe-fix-styles';
        styleElement.innerHTML = styles;
        document.head.appendChild(styleElement);
        
        log('Custom styles injected');
    }

    // Public API for debugging
    window.StripeCheckoutFix = {
        relocate: relocateStripeElements,
        cleanup: cleanupOrphanedElements,
        fixSpecific: fixStripeSpecificIssues,
        debug: (enabled) => {
            config.debug = enabled;
            log('Debug mode', enabled ? 'enabled' : 'disabled');
        }
    };

    // Initialize on various events to ensure it runs
    $(document).ready(initStripeButtonFix);
    $(window).on('load', () => {
        setTimeout(() => {
            relocateStripeElements();
            fixStripeSpecificIssues();
            cleanupOrphanedElements();
        }, 500);
    });
    
    // Also initialize immediately
    initStripeButtonFix();
    
    // Inject styles
    injectCustomStyles();

})(jQuery);
