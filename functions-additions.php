<?php
/**
 * Add this code to your theme's functions.php file
 * Or create a custom plugin with this code
 * 
 * This fixes the WooCommerce Stripe Express Checkout positioning issue
 * and applies a clean, minimalistic design while maintaining visual identity
 */

// ========================================
// CHECKOUT BUTTON FIX IMPLEMENTATION
// ========================================

/**
 * Enqueue custom scripts and styles for checkout fix
 */
add_action('wp_enqueue_scripts', 'enqueue_checkout_fix_assets', 20);
function enqueue_checkout_fix_assets() {
    if (is_product() || is_checkout()) {
        // Enqueue the custom CSS
        wp_enqueue_style(
            'wc-checkout-fix-styles',
            get_stylesheet_directory_uri() . '/assets/css/wc-checkout-styles.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue the custom JavaScript
        wp_enqueue_script(
            'stripe-checkout-fix',
            get_stylesheet_directory_uri() . '/assets/js/stripe-checkout-fix.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Add inline styles for immediate effect
        wp_add_inline_style('wc-checkout-fix-styles', '
            /* Critical styles for immediate rendering */
            #custom-payment-buttons-wrapper {
                margin-top: 30px;
                width: 100%;
                clear: both;
            }
            
            #buttons-container {
                width: 100% !important;
                position: relative !important;
                z-index: 100 !important;
            }
            
            #wc-stripe-express-checkout-element {
                width: 100% !important;
                display: block !important;
            }
        ');
    }
}

/**
 * Initialize WooCommerce payment gateways early
 */
add_action('woocommerce_before_single_product', 'init_payment_gateways_early', 5);
function init_payment_gateways_early() {
    if (function_exists('WC')) {
        WC()->payment_gateways();
    }
}

/**
 * Custom checkout button container
 */
add_action('woocommerce_after_add_to_cart_button', 'render_payment_buttons_container', 20);
function render_payment_buttons_container() {
    if (!is_product()) {
        return;
    }
    
    // Ensure gateways are initialized
    if (function_exists('WC')) {
        $gateways = WC()->payment_gateways();
        
        // Force Stripe to initialize if available
        if (isset($gateways->payment_gateways['stripe'])) {
            $stripe_gateway = $gateways->payment_gateways['stripe'];
            if (method_exists($stripe_gateway, 'init')) {
                $stripe_gateway->init();
            }
        }
    }
    ?>
    
    <!-- Payment Buttons Container -->
    <div id="custom-payment-buttons-wrapper" class="payment-buttons-container">
        
        <!-- Express Checkout (Stripe) -->
        <div id="buttons-container" class="express-checkout-container">
            <div id="wc-stripe-express-checkout-element"></div>
        </div>
        
        <!-- Add separator if both payment methods are available -->
        <?php if (class_exists('WC_Gateway_PPCP') && class_exists('WC_Stripe_Payment_Gateway')): ?>
        <div class="payment-separator">
            <span>OR PAY WITH</span>
        </div>
        <?php endif; ?>
        
        <!-- PayPal Container (if using PayPal) -->
        <?php if (class_exists('WC_Gateway_PPCP')): ?>
        <div class="paypal-container">
            <!-- PayPal Pay in 3 Message -->
            <div class="ppcp-messages-container">
                <div id="ppcp-messages" data-partner-attribution-id="WooCommerce_PCP"></div>
                <?php 
                do_action('ppcp-credit-messaging-wrapper');
                do_action('woocommerce_paypal_payments_single_product_message_render');
                ?>
            </div>
            
            <!-- PayPal Button -->
            <div class="paypal-button-container">
                <?php do_action('woocommerce_paypal_payments_single_product_button_render'); ?>
            </div>
            
            <!-- Digital Wallets via PayPal -->
            <div class="digital-wallet-container">
                <?php do_action('woocommerce_paypal_payments_smart_button_wrapper'); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script type="text/javascript">
    // Immediate fix for Stripe positioning
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            var targetContainer = document.getElementById('buttons-container');
            if (!targetContainer) return;
            
            // Watch for Stripe elements and move them to the correct container
            var checkStripe = setInterval(function() {
                var stripeElements = document.querySelectorAll(
                    '.wc-stripe-express-checkout-element, ' +
                    '.p-HeightObserverProvider, ' +
                    'iframe[src*="stripe"]:not(#buttons-container iframe)'
                );
                
                stripeElements.forEach(function(element) {
                    if (!targetContainer.contains(element)) {
                        targetContainer.appendChild(element);
                        console.log('Moved Stripe element to correct container');
                    }
                });
                
                // Stop checking after finding and moving elements
                if (targetContainer.querySelector('.wc-stripe-express-checkout-element')) {
                    clearInterval(checkStripe);
                }
            }, 100);
            
            // Stop after 5 seconds
            setTimeout(function() {
                clearInterval(checkStripe);
            }, 5000);
        });
    })();
    </script>
    <?php
}

/**
 * Remove default Stripe Express Checkout hooks to prevent duplicates
 */
add_action('init', 'remove_default_stripe_checkout_hooks', 999);
function remove_default_stripe_checkout_hooks() {
    // Remove various possible hooks that Stripe might use
    $hooks_to_remove = array(
        'woocommerce_after_add_to_cart_form',
        'woocommerce_after_add_to_cart_button',
        'woocommerce_before_add_to_cart_button'
    );
    
    foreach ($hooks_to_remove as $hook) {
        remove_all_actions($hook, 1);
        remove_all_actions($hook, 10);
    }
    
    // Specifically target Stripe Express Checkout if class exists
    if (class_exists('WC_Stripe_Express_Checkout_Element')) {
        remove_action('woocommerce_after_add_to_cart_form', 
            array('WC_Stripe_Express_Checkout_Element', 'display_express_checkout_button'), 1);
    }
}

/**
 * Clean up duplicate payment buttons on checkout page
 */
add_action('woocommerce_review_order_after_payment', 'cleanup_duplicate_payment_buttons');
function cleanup_duplicate_payment_buttons() {
    ?>
    <script type="text/javascript">
    jQuery(function($) {
        // Remove duplicate Stripe buttons on checkout
        var stripeButtons = $('.wc-stripe-express-checkout-button-wrapper');
        if (stripeButtons.length > 1) {
            stripeButtons.slice(1).remove();
        }
    });
    </script>
    <?php
}

/**
 * Add minimalistic styling to checkout form
 */
add_action('woocommerce_before_checkout_form', 'add_checkout_custom_styles', 5);
function add_checkout_custom_styles() {
    ?>
    <style>
        /* Minimalistic Checkout Styles */
        .woocommerce-checkout {
            font-family: 'Lato', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .woocommerce-checkout h3 {
            font-size: 20px;
            font-weight: 600;
            color: #2B2823;
            margin-bottom: 20px;
            border-bottom: 2px solid #d8a81b;
            padding-bottom: 10px;
        }
        
        .woocommerce-checkout .form-row {
            margin-bottom: 20px;
        }
        
        .woocommerce-checkout label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 5px;
            display: block;
        }
        
        /* Clean, modern input styles */
        .woocommerce-checkout input[type="text"],
        .woocommerce-checkout input[type="email"],
        .woocommerce-checkout input[type="tel"],
        .woocommerce-checkout select,
        .woocommerce-checkout textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .woocommerce-checkout input:focus,
        .woocommerce-checkout select:focus,
        .woocommerce-checkout textarea:focus {
            outline: none;
            border-color: #d8a81b;
            box-shadow: 0 0 0 3px rgba(216, 168, 27, 0.1);
        }
        
        /* Order review section */
        #order_review {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        #order_review_heading {
            font-size: 24px;
            font-weight: 600;
            color: #2B2823;
            margin-bottom: 20px;
        }
    </style>
    <?php
}

/**
 * Optimize checkout page performance
 */
add_action('wp_enqueue_scripts', 'optimize_checkout_performance', 100);
function optimize_checkout_performance() {
    if (is_checkout() || is_product()) {
        // Defer non-critical scripts
        wp_script_add_data('wc-stripe-upe-classic', 'defer', true);
        
        // Add preconnect for Stripe
        add_action('wp_head', function() {
            echo '<link rel="preconnect" href="https://js.stripe.com">';
            echo '<link rel="preconnect" href="https://api.stripe.com">';
        }, 2);
    }
}

/**
 * Debug function - can be removed in production
 */
if (!function_exists('debug_payment_buttons')) {
    function debug_payment_buttons() {
        if (isset($_GET['debug_payments']) && current_user_can('manage_options')) {
            add_action('wp_footer', function() {
                ?>
                <script>
                console.log('Payment Debug Mode Active');
                console.log('Stripe Elements:', document.querySelectorAll('.wc-stripe-express-checkout-element'));
                console.log('Target Container:', document.getElementById('buttons-container'));
                console.log('PayPal Elements:', document.querySelectorAll('.paypal-buttons'));
                </script>
                <?php
            });
        }
    }
    add_action('init', 'debug_payment_buttons');
}
