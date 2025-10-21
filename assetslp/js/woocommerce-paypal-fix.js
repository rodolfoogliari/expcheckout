/**
 * PayPal Button Enhancement Script
 * Handles dynamic positioning and loading states
 * Version: 1.3.0
 */

(function($) {
    'use strict';

    // Store observers for cleanup
    const observers = new Map();

    // Store interval reference for cleanup
    let paypalCheckInterval = null;

    // Wait for PayPal SDK to load
    $(document).ready(function() {

        // Monitor PayPal button containers
        function initPayPalFix() {
            const containers = document.querySelectorAll('.ppcp-button-wrapper, #ppc-button');

            containers.forEach(function(container) {
                // Add loading class
                container.classList.add('loading');

                // Disconnect existing observer if any
                if (observers.has(container)) {
                    observers.get(container).disconnect();
                }

                // Create MutationObserver to watch for PayPal iframe insertion
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeName === 'IFRAME' &&
                                    (node.name.includes('zoid') || node.id.includes('paypal'))) {
                                    // PayPal iframe loaded, remove loading state
                                    container.classList.remove('loading');

                                    // Ensure iframe is properly positioned
                                    fixIframePosition(node);

                                    // Disconnect observer after iframe loads
                                    observer.disconnect();
                                    observers.delete(container);
                                }
                            });
                        }
                    });
                });

                // Store observer for later cleanup
                observers.set(container, observer);

                // Start observing
                observer.observe(container, {
                    childList: true,
                    subtree: true
                });
            });
        }

        // Fix iframe positioning issues
        function fixIframePosition(iframe) {
            // Validate it's actually a PayPal iframe for security
            if (!iframe.src || !iframe.src.match(/paypal\.com/i)) {
                return;
            }

            // Ensure iframe doesn't break layout
            const parent = iframe.parentElement;
            if (parent) {
                const parentRect = parent.getBoundingClientRect();
                const iframeRect = iframe.getBoundingClientRect();

                // Check if iframe is breaking out of parent bounds
                if (iframeRect.width > parentRect.width) {
                    iframe.style.maxWidth = '100%';
                    iframe.style.width = '100%';
                }

                // Ensure iframe height is set
                if (!iframe.style.height || iframe.style.height === '0px') {
                    iframe.style.minHeight = '45px';
                }
            }
        }

        // Initialize on page load
        initPayPalFix();

        // Re-initialize on AJAX cart updates (for variable products)
        $(document).on('found_variation', function() {
            // Increased timeout to 250ms to ensure PayPal SDK has time to re-render
            setTimeout(initPayPalFix, 250);
        });

        // Handle PayPal SDK late loading
        try {
            if (typeof paypal !== 'undefined' && paypal.Buttons) {
                // PayPal SDK already loaded
                initPayPalFix();
            } else {
                // Wait for PayPal SDK - store interval reference for cleanup
                paypalCheckInterval = setInterval(function() {
                    try {
                        if (typeof paypal !== 'undefined' && paypal.Buttons) {
                            clearInterval(paypalCheckInterval);
                            paypalCheckInterval = null;
                            initPayPalFix();
                        }
                    } catch (error) {
                        console.warn('PayPal SDK check failed:', error);
                        clearInterval(paypalCheckInterval);
                        paypalCheckInterval = null;
                    }
                }, 500);

                // Stop checking after 10 seconds
                setTimeout(function() {
                    if (paypalCheckInterval) {
                        clearInterval(paypalCheckInterval);
                        paypalCheckInterval = null;
                    }
                }, 10000);
            }
        } catch (error) {
            console.warn('PayPal initialization failed:', error);
        }

        // Handle responsive behavior
        let resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                document.querySelectorAll('iframe[name*="zoid"]').forEach(fixIframePosition);
            }, 250);
        });

        // Cleanup observers and intervals on page unload
        $(window).on('beforeunload', function() {
            // Disconnect all observers
            observers.forEach(function(observer) {
                observer.disconnect();
            });
            observers.clear();

            // Clear interval if still running
            if (paypalCheckInterval) {
                clearInterval(paypalCheckInterval);
                paypalCheckInterval = null;
            }
        });
    });

})(jQuery);
