<?php
/*
 * Plugin Name: Expinet Payment Gateway
 * Description: Credit card payments gateway to accept the payment on your WooCommerce store.
 * Author: Kaushik Domadiya
 * Author URI: https://kaushikdomadiya.live
 * Version: 1.0.0
 * Text Domain: expinet-payment-gateway
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('EPG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EPG_PLUGIN_URL', plugin_dir_url(__FILE__));
define( 'EPG_PLUGIN_VERSION', '1.0.3' );

// Include required class files
require_once plugin_dir_path( __FILE__ ) . 'includes/Activator.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/Deactivator.php';
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/Admin/ExpinentPaymentList.php';
}

// Activation hook
register_activation_hook( __FILE__, 'expinet_activate_plugin' );
function expinet_activate_plugin() {
    ExpinetPaymentGateway\Activator::activate();
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'expinet_deactivate_plugin' );
function expinet_deactivate_plugin() {
    ExpinetPaymentGateway\Deactivator::deactivate();
}

// Initialize the plugin after WooCommerce is loaded
add_action( 'plugins_loaded', 'expinet_initialize_gateway' );
function expinet_initialize_gateway() {
    // Check if WooCommerce is active
    if ( class_exists( 'WC_Payment_Gateway' ) ) {

        // Require your gateway class file here — AFTER WooCommerce is loaded
        require_once plugin_dir_path( __FILE__ ) . 'includes/Gateway/ExpinetGateway.php';

        // Register it to WooCommerce payment gateways
        add_filter( 'woocommerce_payment_gateways', function( $gateways ) {
            $gateways[] = 'ExpinetPaymentGateway\Gateway\ExpinetGateway';
            return $gateways;
        });
    }
}

// Expinet logs WP table Admin Custom Styles
function expinet_admin_custom_styles() {
    echo '<style>
        .wp-list-table .column-api_date { width: 150px; }
        .wp-list-table .column-order_id { width: 100px; }
        .wp-list-table .column-api_request,
        .wp-list-table .column-api_response { width: 450px; }
        .wp-list-table .column-api_request pre,
        .wp-list-table .column-api_response pre {
            max-width: 100%;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-word;
            background: #f8f8f8;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .wp-list-table tbody tr:hover {
            background-color: #f5f7fa;
        }
    </style>';
}
add_action( 'admin_head', 'expinet_admin_custom_styles' );