<?php
namespace ExpinetPaymentGateway;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Deactivator {

    /**
     * Code to run during plugin deactivation.
     */
    public static function deactivate() {
        // Flush rewrite rules if you registered any custom rules
        flush_rewrite_rules();
    }
}