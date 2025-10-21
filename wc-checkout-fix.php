<?php
/**
 * WooCommerce Stripe Express Checkout Button Fix
 * 
 * This file fixes the issue where Stripe Express Checkout buttons appear
 * outside the intended container. Add this code to your theme's functions.php
 * or create a custom plugin.
 */

// Initialize payment gateways properly for Stripe Express Checkout
add_action('woocommerce_before_single_product', 'initialize_payment_gateways', 5);
function initialize_payment_gateways() {
    if (function_exists('WC')) {
        WC()->payment_gateways();
    }
}

// Custom function to render payment buttons in the correct container
add_action('woocommerce_after_add_to_cart_button', 'render_custom_payment_buttons', 20);
function render_custom_payment_buttons() {
    if (!is_product()) {
        return;
    }
    
    // Initialize gateways to ensure Stripe is properly loaded
    if (function_exists('WC')) {
        WC()->payment_gateways();
    }
    ?>
    
    <!-- Main Payment Buttons Container -->
    <div id="custom-payment-buttons-wrapper" class="payment-buttons-container">
        
        <!-- Express Checkout Container (for Stripe, Apple Pay, Google Pay) -->
        <div id="buttons-container" class="express-checkout-container">
            <div class="express-checkout-inner">
                <!-- This is where Stripe Express Checkout should render -->
                <div id="wc-stripe-express-checkout-element"></div>
            </div>
        </div>
        
        <!-- PayPal Container (if you're also using PayPal) -->
        <?php if (class_exists('WC_Gateway_PPCP')): ?>
        <div class="paypal-container">
            <!-- Pay in 3 messaging -->
            <div class="ppcp-messages-container">
                <div id="ppcp-messages" 
                     data-partner-attribution-id="WooCommerce_PCP">
                </div>
                <?php 
                do_action('ppcp-credit-messaging-wrapper');
                do_action('woocommerce_paypal_payments_single_product_message_render');
                ?>
            </div>
            
            <!-- PayPal button -->
            <div class="paypal-button-container">
                <?php do_action('woocommerce_paypal_payments_single_product_button_render'); ?>
            </div>
            
            <!-- Digital Wallet (Google Pay/Apple Pay via PayPal) -->
            <div class="digital-wallet-container">
                <?php do_action('woocommerce_paypal_payments_smart_button_wrapper'); ?>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    <?php
}

// Force Stripe Express Checkout to render in the correct container
add_action('wp_footer', 'fix_stripe_express_checkout_positioning');
function fix_stripe_express_checkout_positioning() {
    if (!is_product()) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Wait for Stripe to initialize
        var stripeCheckInterval = setInterval(function() {
            var stripeElement = document.querySelector('.wc-stripe-express-checkout-element');
            var targetContainer = document.getElementById('wc-stripe-express-checkout-element');
            
            if (stripeElement && targetContainer) {
                // Check if Stripe element is outside the intended container
                if (!targetContainer.contains(stripeElement)) {
                    // Move the Stripe element into the correct container
                    targetContainer.appendChild(stripeElement);
                }
                
                // Also check for the iframe and HeightObserver
                var heightObserver = document.querySelector('.p-HeightObserverProvider');
                if (heightObserver && !targetContainer.contains(heightObserver)) {
                    targetContainer.appendChild(heightObserver);
                }
                
                clearInterval(stripeCheckInterval);
            }
        }, 100);
        
        // Stop checking after 5 seconds
        setTimeout(function() {
            clearInterval(stripeCheckInterval);
        }, 5000);
    });
    </script>
    <?php
}

// Alternative method using MutationObserver for more robust handling
add_action('wp_footer', 'add_mutation_observer_for_stripe', 15);
function add_mutation_observer_for_stripe() {
    if (!is_product()) {
        return;
    }
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        // Target container where buttons should be
        var targetContainer = document.getElementById('buttons-container');
        if (!targetContainer) return;
        
        // Create a MutationObserver to watch for Stripe elements being added
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    // Check if this is a Stripe-related element
                    if (node.nodeType === 1) { // Element node
                        if (node.classList && (
                            node.classList.contains('wc-stripe-express-checkout-element') ||
                            node.classList.contains('p-HeightObserverProvider') ||
                            (node.tagName === 'IFRAME' && node.src && node.src.includes('stripe'))
                        )) {
                            // If it's not already in the target container, move it
                            if (!targetContainer.contains(node)) {
                                console.log('Moving Stripe element to correct container');
                                targetContainer.appendChild(node);
                            }
                        }
                    }
                });
            });
        });
        
        // Start observing the document body for added nodes
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        // Stop observing after 10 seconds to prevent performance issues
        setTimeout(function() {
            observer.disconnect();
        }, 10000);
    });
    </script>
    <?php
}

// Remove default Stripe Express Checkout positioning if needed
add_action('init', 'remove_default_stripe_hooks', 20);
function remove_default_stripe_hooks() {
    // Remove default Stripe Express Checkout actions if they conflict
    // Adjust these based on your specific Stripe plugin version
    remove_action('woocommerce_after_add_to_cart_form', 'wc_stripe_express_checkout_button', 1);
    remove_action('woocommerce_after_add_to_cart_form', array('WC_Stripe_Express_Checkout_Element', 'display_express_checkout_button'), 1);
}

// Re-add Stripe Express Checkout in the correct location
add_action('init', 'add_custom_stripe_hooks', 25);
function add_custom_stripe_hooks() {
    // Add our custom action that will be called from our container
    add_action('render_stripe_express_checkout', function() {
        if (class_exists('WC_Stripe_Express_Checkout_Element')) {
            WC_Stripe_Express_Checkout_Element::display_express_checkout_button();
        } elseif (function_exists('wc_stripe_express_checkout_button')) {
            wc_stripe_express_checkout_button();
        }
    });
}
