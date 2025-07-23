<?php
namespace ExpinetPaymentGateway;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Activator {

    /**
     * Code to run during plugin activation.
     */
    public static function activate() {
        // Flush rewrite rules if your plugin registers custom rewrite rules
        flush_rewrite_rules();

        // Example: create custom table if needed
        global $wpdb;

        $table_name = $wpdb->prefix . 'expinent_api_data';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            api_request longtext NOT NULL,
            api_response longtext NOT NULL,
            api_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}