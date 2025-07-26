<?php
/*
 * Plugin Name: Expinet Payment Gateway
 * Description: Credit card payments gateway to accept the payment on your WooCommerce store.
 * Author: Kaushik Domadiya
 * Author URI: https://kaushikdomadiya.live
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: expinet-payment-gateway
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('EXPIPAGA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EXPIPAGA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EXPIPAGA_PLUGIN_VERSION', '1.0.3' );

// Include required class files
require_once plugin_dir_path( __FILE__ ) . 'includes/Activator.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/Deactivator.php';
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/Admin/ExpinetPaymentList.php';
}

// Activation hook
register_activation_hook( __FILE__, 'expipaga_activate_plugin' );
function expipaga_activate_plugin() {
    ExpinetPaymentGateway\Activator::activate();
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'expipaga_deactivate_plugin' );
function expipaga_deactivate_plugin() {
    ExpinetPaymentGateway\Deactivator::deactivate();
}

// Initialize the plugin after WooCommerce is loaded
add_action( 'plugins_loaded', 'expipaga_initialize_gateway' );
function expipaga_initialize_gateway() {
    // Check if WooCommerce is active
    if ( class_exists( 'WC_Payment_Gateway' ) ) {

        // Require your gateway class file here — AFTER WooCommerce is loaded
        require_once plugin_dir_path( __FILE__ ) . 'includes/Gateway/ExpinetGateway.php';

        // Register it to WooCommerce payment gateways
        add_filter( 'woocommerce_payment_gateways', function( $gateways ) {
            $gateways[] = 'ExpinetPaymentGateway\Gateway\EXPIPAGA_Gateway';
            return $gateways;
        });
    }
}

// Expinet logs WP table Admin Custom Styles
function expipaga_enqueue_admin_style() {
    wp_enqueue_style(
            'expipaga-admin-style',
            EXPIPAGA_PLUGIN_URL . 'css/expinet-admin-style.css',
            array(),
            EXPIPAGA_PLUGIN_VERSION,
        );
}
add_action( 'admin_enqueue_scripts', 'expipaga_enqueue_admin_style' );