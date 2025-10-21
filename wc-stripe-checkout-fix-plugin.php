<?php
/**
 * Plugin Name: WooCommerce Stripe Checkout Fix & Optimizer
 * Description: Fixes Stripe Express Checkout button positioning and applies minimalistic, conversion-optimized design
 * Version: 1.0.0
 * Author: Your Name
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

/**
 * Main Plugin Class
 */
class WC_Stripe_Checkout_Fix {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Core hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 20);
        add_action('woocommerce_before_single_product', array($this, 'init_payment_gateways'), 5);
        add_action('woocommerce_after_add_to_cart_button', array($this, 'render_payment_container'), 20);
        add_action('init', array($this, 'remove_default_hooks'), 999);
        add_action('wp_head', array($this, 'add_critical_styles'), 5);
        add_action('wp_footer', array($this, 'add_positioning_script'), 100);
        
        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Enqueue CSS and JS assets
     */
    public function enqueue_assets() {
        if (!is_product() && !is_checkout()) {
            return;
        }
        
        // Inline critical CSS
        $critical_css = '
            #custom-payment-buttons-wrapper {
                margin-top: 30px;
                width: 100%;
                clear: both;
            }
            #buttons-container {
                width: 100% !important;
                position: relative !important;
                z-index: 100 !important;
                display: block !important;
            }
            #wc-stripe-express-checkout-element {
                width: 100% !important;
                display: block !important;
                position: relative !important;
            }
            .express-checkout-container iframe,
            .express-checkout-container .p-HeightObserverProvider {
                width: 100% !important;
                position: relative !important;
                display: block !important;
            }
            .single_add_to_cart_button {
                background-color: #d8a81b !important;
                color: #2B2823 !important;
                border: none !important;
                padding: 14px 35px !important;
                font-size: 16px !important;
                font-weight: 600 !important;
                border-radius: 8px !important;
                transition: all 0.3s ease !important;
                cursor: pointer;
                width: 100%;
                margin-bottom: 15px;
            }
            .single_add_to_cart_button:hover {
                background-color: #b7780d !important;
                color: #fff !important;
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(216, 168, 27, 0.25);
            }
        ';
        
        wp_add_inline_style('woocommerce-general', $critical_css);
    }
    
    /**
     * Initialize payment gateways early
     */
    public function init_payment_gateways() {
        if (function_exists('WC')) {
            $gateways = WC()->payment_gateways();
            
            // Force Stripe initialization
            if (isset($gateways->payment_gateways['stripe'])) {
                $stripe = $gateways->payment_gateways['stripe'];
                if (method_exists($stripe, 'init')) {
                    $stripe->init();
                }
            }
        }
    }
    
    /**
     * Render the payment buttons container
     */
    public function render_payment_container() {
        if (!is_product()) {
            return;
        }
        ?>
        <!-- WooCommerce Payment Buttons Container -->
        <div id="custom-payment-buttons-wrapper" class="payment-buttons-container">
            
            <!-- Express Checkout Container for Stripe -->
            <div id="buttons-container" class="express-checkout-container">
                <div id="wc-stripe-express-checkout-element"></div>
            </div>
            
            <?php if (class_exists('WC_Gateway_PPCP')): ?>
            <!-- PayPal Container -->
            <div class="paypal-container">
                <div class="ppcp-messages-container">
                    <div id="ppcp-messages" data-partner-attribution-id="WooCommerce_PCP"></div>
                    <?php 
                    do_action('ppcp-credit-messaging-wrapper');
                    do_action('woocommerce_paypal_payments_single_product_message_render');
                    ?>
                </div>
                <div class="paypal-button-container">
                    <?php do_action('woocommerce_paypal_payments_single_product_button_render'); ?>
                </div>
                <div class="digital-wallet-container">
                    <?php do_action('woocommerce_paypal_payments_smart_button_wrapper'); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Remove default Stripe hooks to prevent conflicts
     */
    public function remove_default_hooks() {
        $hooks = array(
            'woocommerce_after_add_to_cart_form',
            'woocommerce_after_add_to_cart_button',
            'woocommerce_before_add_to_cart_button'
        );
        
        foreach ($hooks as $hook) {
            remove_all_actions($hook, 1);
            remove_all_actions($hook, 10);
        }
        
        if (class_exists('WC_Stripe_Express_Checkout_Element')) {
            remove_action('woocommerce_after_add_to_cart_form', 
                array('WC_Stripe_Express_Checkout_Element', 'display_express_checkout_button'), 1);
        }
    }
    
    /**
     * Add critical inline styles
     */
    public function add_critical_styles() {
        if (!is_product() && !is_checkout()) {
            return;
        }
        ?>
        <style id="stripe-checkout-fix-critical">
            /* Prevent FOUC */
            .wc-stripe-express-checkout-button-wrapper {
                display: none !important;
            }
            #custom-payment-buttons-wrapper {
                display: block !important;
                visibility: visible !important;
            }
            /* Full width buttons */
            .gpay-button-container,
            .gpay-button,
            .apple-pay-button {
                width: 100% !important;
                display: block !important;
            }
        </style>
        <?php
    }
    
    /**
     * Add positioning script
     */
    public function add_positioning_script() {
        if (!is_product()) {
            return;
        }
        ?>
        <script id="stripe-checkout-position-fix">
        (function() {
            'use strict';
            
            // Configuration
            const targetId = 'buttons-container';
            const maxAttempts = 50;
            let attempts = 0;
            
            function moveStripeElements() {
                const target = document.getElementById(targetId);
                if (!target) {
                    console.warn('Target container not found');
                    return false;
                }
                
                // Find all Stripe-related elements
                const elements = document.querySelectorAll(
                    '.wc-stripe-express-checkout-element, ' +
                    '.p-HeightObserverProvider, ' +
                    'iframe[src*="stripe"]:not(#' + targetId + ' iframe)'
                );
                
                let moved = false;
                elements.forEach(function(element) {
                    if (!target.contains(element)) {
                        target.appendChild(element);
                        moved = true;
                        console.log('Moved Stripe element:', element);
                    }
                });
                
                return moved || target.querySelector('.wc-stripe-express-checkout-element');
            }
            
            // Method 1: DOMContentLoaded
            document.addEventListener('DOMContentLoaded', function() {
                const interval = setInterval(function() {
                    attempts++;
                    
                    if (moveStripeElements() || attempts >= maxAttempts) {
                        clearInterval(interval);
                        console.log('Stripe positioning complete');
                    }
                }, 100);
            });
            
            // Method 2: MutationObserver
            if (typeof MutationObserver !== 'undefined') {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) {
                                const isStripe = 
                                    (node.classList && (
                                        node.classList.contains('wc-stripe-express-checkout-element') ||
                                        node.classList.contains('p-HeightObserverProvider')
                                    )) ||
                                    (node.tagName === 'IFRAME' && node.src && node.src.includes('stripe'));
                                
                                if (isStripe) {
                                    setTimeout(moveStripeElements, 10);
                                }
                            }
                        });
                    });
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
                
                // Disconnect after 10 seconds
                setTimeout(function() {
                    observer.disconnect();
                }, 10000);
            }
            
            // Method 3: jQuery ready (if available)
            if (typeof jQuery !== 'undefined') {
                jQuery(function($) {
                    moveStripeElements();
                    
                    // Also check after AJAX events
                    $(document).ajaxComplete(function() {
                        setTimeout(moveStripeElements, 100);
                    });
                });
            }
            
            // Clean up orphaned elements
            setTimeout(function() {
                const orphaned = document.querySelectorAll(
                    'body > .wc-stripe-express-checkout-element, ' +
                    'body > .p-HeightObserverProvider'
                );
                orphaned.forEach(function(el) {
                    el.remove();
                });
            }, 5000);
        })();
        </script>
        <?php
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if Stripe plugin is active
        if (!class_exists('WC_Stripe_Payment_Gateway')) {
            ?>
            <div class="notice notice-warning">
                <p><?php _e('WooCommerce Stripe Checkout Fix: WooCommerce Stripe Payment Gateway plugin is not active. Some features may not work correctly.', 'wc-stripe-fix'); ?></p>
            </div>
            <?php
        }
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    WC_Stripe_Checkout_Fix::get_instance();
});

// Activation hook
register_activation_hook(__FILE__, function() {
    // Clear any cached payment gateway data
    delete_transient('wc_payment_gateways');
    
    // Set a flag to show setup notice
    set_transient('wc_stripe_fix_activated', true, 60);
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up transients
    delete_transient('wc_stripe_fix_activated');
});

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=stripe') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
});
