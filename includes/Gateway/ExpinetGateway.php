<?php
namespace ExpinetPaymentGateway\Gateway;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Expinet Payment Gateway Class
 */
class EXPIPAGA_Gateway extends \WC_Payment_Gateway {

    public $test_mode;
    public $api_key;
    public $merchant_id;
    public $api_url;
    public $enabled_hold;
    public $expinet_domain;
    public $expinet_location_id;
    public $expinet_developer_id;
    public $expinet_service_id;
    public $expinet_user_api_key;
    public $expinet_user_id;
    public $expinet_user_hash_key;
    public $apiVersion;
    /**
     * Constructor
     */
    public function __construct() {
        $this->id                 = 'expinet';
        $this->icon               = ''; // Optional: URL to payment method icon
        $this->method_title       = __( 'Expinet Payment', 'payment-gateway-expinet-and-woocommerce-integration' );
        $this->method_description = __( 'Secure credit card payments via Expinet API.', 'payment-gateway-expinet-and-woocommerce-integration' );
        $this->has_fields         = true;

        $enable_order_refund = get_option( 'enable_order_refund' );

        if ( $enable_order_refund ) {
            $this->supports = array( 'products', 'default_credit_card_form', 'refunds' );
        } else {
            $this->supports = array( 'products', 'default_credit_card_form' );
        }

        // Load settings form fields
        $this->init_form_fields();
        $this->init_settings();

        // Get settings values
        $this->title                 = $this->get_option( 'title' );
        $this->description           = $this->get_option( 'description' );
        $this->enabled               = $this->get_option( 'enabled' );
        $this->test_mode             = $this->get_option( 'test_mode' );
        $this->api_url               = $this->get_option( 'api_url' );
        $this->enabled_hold          = $this->get_option( 'enabled_hold' );
        $this->expinet_domain        = $this->get_option( 'expinet_domain' );
        $this->expinet_location_id   = $this->get_option( 'expinet_location_id' );
        $this->expinet_developer_id  = $this->get_option( 'expinet_developer_id' );
        $this->expinet_service_id    = $this->get_option( 'expinet_service_id' );
        $this->expinet_user_api_key  = $this->get_option( 'expinet_user_api_key' );
        $this->expinet_user_id       = $this->get_option( 'expinet_user_id' );
        $this->expinet_user_hash_key = $this->get_option( 'expinet_user_hash_key' );
        $this->apiVersion            = 'v2';

        // Save settings from admin
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );

        // Load gateway scripts
        add_action( 'wp_enqueue_scripts', [ $this, 'expipaga_payment_gateway_scripts' ] );

        // Restrict gateway for subscription-based products
        add_filter( 'woocommerce_available_payment_gateways', [ $this, 'expipaga_woocommerce_available_payment_gateways' ], 10, 1 );

        // Handle completion status
        add_action( 'woocommerce_order_status_completed', [ $this, 'expipaga_handle_order_status_completed' ], 12, 1 );
    }

    /**
     * Admin settings form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __( 'Enable/Disable', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'label'       => __( 'Enable Expinet Gateway', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'type'        => 'checkbox',
                'description' => __( 'Enable the Expinet gateway to accept payments via credit card.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'enabled_hold' => array(
                'title'       => __( 'Enable Payment Hold', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'label'       => __( 'Allow payment to be placed on hold.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'type'        => 'checkbox',
                'description' => __( 'Enable this option to authorize credit card payments without capturing them immediately.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'title' => array(
                'title'       => __( 'Title', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'type'        => 'text',
                'description' => __( 'Controls the payment method title shown to customers during checkout.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'default'     => __( 'Credit Card', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Description', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'type'        => 'textarea',
                'description' => __( 'Controls the payment method description shown to customers during checkout.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'default'     => __( 'Pay securely using your credit card via Expinet payment gateway.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'desc_tip'    => true,
            ),
            'expinet_domain' => array(
                'title'       => __( 'Expinet Domain', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'type'        => 'text',
                'description' => __( 'The base domain URL for Expinet API requests.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'default'     => 'https://api.sandbox.expinet.net',
                'desc_tip'    => true,
            ),
            'expinet_location_id' => array(
                'title'       => __( 'Location/Merchant ID', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'type'        => 'text',
                'description' => __( 'Your Expinet location or merchant ID.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'expinet_developer_id' => array(
                'title'       => __( 'Developer ID', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'type'        => 'text',
                'description' => __( 'Your Expinet developer ID.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'expinet_service_id' => array(
                'title'       => __( 'Service ID', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'type'        => 'text',
                'description' => __( 'Your Expinet service ID.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'expinet_user_id' => array(
                'title'       => __( 'User ID', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'type'        => 'text',
                'description' => __( 'Your Expinet user ID.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'expinet_user_api_key' => array(
                'title'       => __( 'API Key', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'type'        => 'text',
                'description' => __( 'Your Expinet API key for authentication.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'expinet_user_hash_key' => array(
                'title'       => __( 'User Hash Key', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'type'        => 'text',
                'description' => __( 'Hash key used for verifying Expinet API requests.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Enqueue gateway scripts/styles on checkout page
     */
    public function expipaga_payment_gateway_scripts() {

        // Load only on cart, checkout, or pay_for_order pages
        if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        // If the payment gateway is disabled, skip loading scripts
        if ( 'no' === $this->enabled ) {
            return;
        }

        // Enqueue custom stylesheet
        wp_enqueue_style(
            'woocommerce-expinet-payment-gateway-custom',
            EXPIPAGA_PLUGIN_URL . 'css/custom.css',
            array(), // Dependencies
            EXPIPAGA_PLUGIN_VERSION,
        );

        // Enqueue custom JS
        wp_enqueue_script(
            'woocommerce-expinet-payment-gateway-custom-js',
            EXPIPAGA_PLUGIN_URL . 'js/custom.js',
            array( 'jquery' ),
            EXPIPAGA_PLUGIN_VERSION,
            true
        );

        // Enqueue Vanilla Masker.js for input formatting
        wp_enqueue_script(
            'woocommerce-expinet-payment-gateway-vanilla-masker',
            EXPIPAGA_PLUGIN_URL . 'js/vanilla-masker.min.js',
            array( 'jquery' ),
            EXPIPAGA_PLUGIN_VERSION,
            true
        );
    }

    /**
     * Output payment fields at checkout
     */
    public function payment_fields() {

        if ( $this->description ) { 
            echo wp_kses_post( wpautop( $this->description ) );
        }
        
        ?>
    
        <fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class="wc-credit-card-form wc-payment-form" style="background: transparent;">

            <?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>

            <!-- Card Number -->
            <div class="form-row form-row-wide validate-required">
                <label for="expipaga_cardnumber">
                    <?php esc_html_e( 'Card Number', 'payment-gateway-expinet-and-woocommerce-integration' ); ?> <span class="required">*</span>
                </label>
                <input 
                    id="expipaga_cardnumber" 
                    type="text" 
                    name="expipaga_cardnumber" 
                    placeholder="<?php esc_attr_e( 'Enter credit card number', 'payment-gateway-expinet-and-woocommerce-integration' ); ?>" 
                    autocomplete="off"
                />
                <div class="form-control1 card_thumbs pl-3">
                    <!-- phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -->
                    <img alt="Visa" data-type="visa" src="<?php echo esc_url( EXPIPAGA_PLUGIN_URL . 'images/visa.png' ); ?>" />
                    <!-- phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -->
                    <img alt="Master Card" data-type="mastercard" src="<?php echo esc_url( EXPIPAGA_PLUGIN_URL . 'images/master_card.png' ); ?>" />
                    <!-- phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -->
                    <img alt="Discover" data-type="discover" src="<?php echo esc_url( EXPIPAGA_PLUGIN_URL . 'images/discover.png' ); ?>" />
                    <!-- phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -->
                    <img alt="American Express" data-type="amex" src="<?php echo esc_url( EXPIPAGA_PLUGIN_URL . 'images/american_express.png' ); ?>" />
                </div>
            </div>

            <!-- Expiry & CVV -->
            <div class="expinet-row form-row form-row-wide validate-required pd-0">
                <div class="expinet-col-6">
                    <label for="expipaga_expirydate">
                        <?php esc_html_e( 'Expiry Date', 'payment-gateway-expinet-and-woocommerce-integration' ); ?> <span class="required">*</span>
                    </label>
                    <input 
                        id="expipaga_expirydate" 
                        type="text" 
                        name="expipaga_expirydate" 
                        placeholder="<?php esc_attr_e( 'MM/YY', 'payment-gateway-expinet-and-woocommerce-integration' ); ?>" 
                        autocomplete="off" 
                        maxlength="5"
                    />
                </div>

                <div class="expinet-col-6">
                    <label for="ccv">
                        <?php esc_html_e( 'Secure Code (cvv)', 'payment-gateway-expinet-and-woocommerce-integration' ); ?> <span class="required">*</span>
                        <span class="help_info" tabindex="0" role="tooltip">
                            <!-- phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -->
                            <img src="<?php echo esc_url( EXPIPAGA_PLUGIN_URL . 'images/question-icons.png' ); ?>" alt="Help" />
                            <p>
                                <?php esc_html_e( 'The CVV Number ("Card Verification Value") on your credit card or debit card is a 3 digit number on VISA®, MasterCard® and Discover® branded credit and debit cards. On your American Express® branded credit or debit card it is a 4 digit numeric code. Providing your CVV number to an online merchant proves that you actually have the physical credit or debit card - and helps to keep you safe while reducing fraud.', 'payment-gateway-expinet-and-woocommerce-integration' ); ?>
                            </p>
                        </span>
                    </label>
                    <input 
                        id="ccv" 
                        class="form-control" 
                        name="ccv" 
                        placeholder="<?php esc_attr_e( '000/0000', 'payment-gateway-expinet-and-woocommerce-integration' ); ?>" 
                        type="text" 
                        maxlength="4"
                    />
                </div>
            </div>

            <!-- Card Name -->
            <div class="form-row form-row-wide validate-required">
                <label for="expipaga_cardname">
                    <?php esc_html_e( 'Name on card', 'payment-gateway-expinet-and-woocommerce-integration' ); ?> <span class="required">*</span>
                </label>
                <input 
                    id="expipaga_cardname"
                    name="expipaga_cardname" 
                    placeholder="<?php esc_attr_e( 'Name on card', 'payment-gateway-expinet-and-woocommerce-integration' ); ?>" 
                    type="text" 
                    required
                />
            </div>
            <?php wp_nonce_field( 'expinet_payment_process', 'expinet_payment_nonce' ); ?>
            <?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>

        </fieldset>
    
        <?php
        
    }

    public function validate_fields(){
           
        $error = false;
        if ( ! isset( $_POST['expinet_payment_nonce'] ) || 
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['expinet_payment_nonce'] ) ), 'expinet_payment_process' ) ) {
            wc_add_notice( __( 'Payment validation failed. Please try again.', 'payment-gateway-expinet-and-woocommerce-integration' ), 'error' );
            return false;
        }
        // Sanitize and validate card number
        if ( empty( $_POST['expipaga_cardnumber'] ) ) {
            wc_add_notice( __( 'Card Number is required!', 'payment-gateway-expinet-and-woocommerce-integration' ), 'error' );
            $error = true;
        } else {
            $expipaga_cardnumber = preg_replace( '/\s+/', '', sanitize_text_field( wp_unslash( $_POST['expipaga_cardnumber'] ) ) );
            $first_six   = substr( $expipaga_cardnumber, 0, 6 );

            if ( false === get_transient( 'expipaga_card_' . $first_six ) ) {
                set_transient( 'expipaga_card_' . $first_six, $first_six, MINUTE_IN_SECONDS );
            } else {
                wc_add_notice( __( 'Please try again after some time. If still having issues, please contact us.', 'payment-gateway-expinet-and-woocommerce-integration' ), 'error' );
                $error = true;
            }
        }

        // Validate expiry date
        if ( empty( $_POST['expipaga_expirydate'] ) ) {
            wc_add_notice( __( 'Expiry Date is required!', 'payment-gateway-expinet-and-woocommerce-integration' ), 'error' );
            $error = true;
        } else {
            $expipaga_expirydate = sanitize_text_field( wp_unslash( $_POST['expipaga_expirydate'] ) );
            $expiry_parts = explode( '/', $expipaga_expirydate );

            if ( count( $expiry_parts ) !== 2 ) {
                wc_add_notice( __( 'Invalid expiry date format. Use MM/YY.', 'payment-gateway-expinet-and-woocommerce-integration' ), 'error' );
                $error = true;
            } else {
                $passMonth = (int) $expiry_parts[0];
                $passYear  = (int) $expiry_parts[1];

                $currentMonth = (int) gmdate( 'm' );
                $currentYear  = (int) gmdate( 'y' );

                if ( $passYear < $currentYear || ( $passYear === $currentYear && $passMonth < $currentMonth ) ) {
                    wc_add_notice( __( 'Your card is expired!', 'payment-gateway-expinet-and-woocommerce-integration' ), 'error' );
                    $error = true;
                }
            }
        }

        // Validate CVV
        if ( empty( $_POST['ccv'] ) ) {
            wc_add_notice( __( 'CVV is required!', 'payment-gateway-expinet-and-woocommerce-integration' ), 'error' );
            $error = true;
        }

        // Validate name on card
        if ( empty( $_POST['expipaga_cardname'] ) ) {
            wc_add_notice( __( 'Name on card is required!', 'payment-gateway-expinet-and-woocommerce-integration' ), 'error' );
            $error = true;
        } else {
            $expipaga_cardname = sanitize_text_field( wp_unslash( $_POST['expipaga_cardname'] ) );
            if ( ! preg_match( '/^[A-Za-z ]+$/', $expipaga_cardname ) ) {
                wc_add_notice( __( 'Name on card must contain only letters.', 'payment-gateway-expinet-and-woocommerce-integration' ), 'error' );
                $error = true;
            }
        }

        return ! $error;
        
    }

    /**
     * Process payment and return result
     */
    public function process_payment( $order_id ) {

        global $woocommerce;

        $order = wc_get_order( $order_id );
        if ( ! isset( $_POST['expinet_payment_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['expinet_payment_nonce'] ) ), 'expinet_payment_process' ) ) {
            wc_add_notice( __( 'Payment validation failed. Please try again.', 'payment-gateway-expinet-and-woocommerce-integration' ), 'error' );
            return false;
        }

        // Sanitize input fields
        if ( isset( $_REQUEST['expipaga_cardnumber'] ) && ! empty( $_REQUEST['expipaga_cardnumber'] ) ) {
            $expipaga_cardnumber = preg_replace( '/\s+/', '', sanitize_text_field( wp_unslash( $_REQUEST['expipaga_cardnumber'] ) ) );
        }
        if ( isset( $_REQUEST['expipaga_expirydate'] ) && ! empty( $_REQUEST['expipaga_expirydate'] ) ) {
            $expipaga_expirydate = str_replace( '/', '', sanitize_text_field( wp_unslash( $_REQUEST['expipaga_expirydate'] ) ) );
        }
        if ( isset( $_REQUEST['expipaga_cardname'] ) && ! empty( $_REQUEST['expipaga_cardname'] ) ) {
            $name        = sanitize_text_field( wp_unslash( $_REQUEST['expipaga_cardname'] ) );
        }
        $shipping    = $order->get_shipping_total();
        $amount      = $order->get_total();

        $api_request = [];

        // Set transaction type based on hold setting
        $api_request["action"]              = ( 'no' === $this->enabled_hold ) ? 'sale' : 'authonly';
        $api_request["payment_method"]      = 'cc';
        $api_request["account_holder_name"] = $name;
        $api_request["account_number"]      = $expipaga_cardnumber;
        $api_request["exp_date"]            = $expipaga_expirydate;
        $api_request["transaction_amount"]  = $amount;
        $api_request["location_id"]         = $this->expinet_location_id;

        $req['transaction'] = $api_request;
        $request            = wp_json_encode( $req );

        $endPoint = 'transactions';
        $response = $this->apiCall( $order_id, $endPoint, $request );

        // Handle insufficient balance error
        if ( isset( $response['data']['transaction']['reason_code_id'] ) && $response['data']['transaction']['reason_code_id'] == 1201 ) {
            $response['status']                             = "error";
            $response["data"]['errors']['account_number'][0] = __( 'Insufficient Balance.', 'payment-gateway-expinet-and-woocommerce-integration' );
        }

        if ( $response['status'] === "success" && empty( $response['data']['errors'] ) ) {

            // Payment success
            $order->payment_complete();
            $order->reduce_order_stock();

            // Optional SO download trigger
            $enable_order_download = get_option( 'enable_order_download' );

            // Add order notes and metadata
            $order->add_order_note( __( 'Transaction ID: ', 'payment-gateway-expinet-and-woocommerce-integration' ) . $response['data']['transaction']['id'] );
            $order->update_meta_data( '_transaction_id', $response['data']['transaction']['id'] );
            $order->update_meta_data( 'expinet_data', $response['data']['transaction'] );
            $order->save();

            // Empty cart
            $woocommerce->cart->empty_cart();

            // Update order status
            if ( $enable_order_download ) {
                $order->update_status( 'wc-completed' );
            }

            return [
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            ];

        } else {
            // Handle API errors
            $msg = [];

            if ( ! empty( $response["data"]['errors'] ) ) {
                foreach ( $response["data"]['errors'] as $key => $error ) {
                    if ( is_array( $error ) ) {
                        $msg[] = esc_html( $error[0] );
                    }
                }
            } else {
                $msg[] = __( 'Transaction failed. Please try again.', 'payment-gateway-expinet-and-woocommerce-integration' );
            }

            wc_add_notice( implode( "\n", $msg ), 'error' );
            return;
        }
    }

    public function apiCall( $order_id, $endPoint = '', $request = array(), $method = 'POST' ) {
        global $wpdb;

        // Build full API URL
        $domain = trailingslashit( $this->expinet_domain ) . $this->apiVersion . '/' . ltrim( $endPoint, '/' );

        $requestLog = [
            'url'     => esc_url_raw( $domain ),
            'request' => json_decode( $request, true ),
        ];

        // Prepare headers
        $headers = [
            'Content-Type'   => 'application/json',
            'Cache-Control'  => 'no-cache',
            'developer-id'   => $this->expinet_developer_id,
            'user-api-key'   => $this->expinet_user_api_key,
            'user-id'        => $this->expinet_user_id,
        ];

        // Send remote request
        $response = wp_remote_request( $domain, [
            'method'  => $method,
            'headers' => $headers,
            'timeout' => 150,
            'body'    => $request,
        ] );

        $results = [];

        // Handle error
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            $results = [
                'status'       => 'error',
                'data'         => $error_message,
                'errorcodetxt' => $error_message,
            ];
            $results = $this->handleResponse( wp_json_encode( $results ) );

            if ( isset( $results['valid'] ) && $results['valid'] === 'fail' ) {
                $results['status'] = 'success';
                $epoint = ( $endPoint === 'transactions' ) ? 'transaction' : 'routertransaction';
                $results['data'][ $epoint ]['configuration_id'] = $results['errorcode'];
            }
        } else {
            $body = wp_remote_retrieve_body( $response );
            $handleSuccessRes = $this->handleResponse( $body );

            if ( isset( $handleSuccessRes['valid'] ) && $handleSuccessRes['valid'] === 'success' ) {
                $results['status'] = 'success';
                $results['data']   = json_decode( $body, true );

                $epoint     = ( $endPoint === 'transactions' ) ? 'transaction' : 'routertransaction';
                $resPartial = $this->checkIfPartialPaymentAndVoid( $results['data'], $epoint, $order_id );

                if ( $resPartial ) {
                    $results['data'][ $epoint ]['status_id']      = 301;
                    $results['data'][ $epoint ]['reason_code_id'] = 1201;
                }
            } else {
                $results['status'] = 'success';
                $results['data']   = json_decode( $body, true );

                if ( ! isset( $results['data']['errors'] ) ) {
                    $results['data']['errors']['account_number'] = [
                        __( 'Transaction Declined.', 'payment-gateway-expinet-and-woocommerce-integration' ),
                    ];
                }

                $epoint = ( $endPoint === 'transactions' ) ? 'transaction' : 'routertransaction';
                $results['data'][ $epoint ]['configuration_id'] = $handleSuccessRes['errorcode'];
            }
        }

        // Log data
        $table_name = $wpdb->prefix . 'expinet_api_data';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            $table_name,
            [
                'order_id'     => intval( $order_id ),
                'api_request'  => serialize( $this->encodeSensitiveData( $requestLog ) ),
                'api_response' => serialize( $results['data'] ),
            ]
        );

        $results['request'] = $requestLog;

        return $results;
    }

    public function encodeSensitiveData( $request = array() ) {
        if (
            isset( $request['request']['transaction']['account_number'] ) &&
            ! empty( $request['request']['transaction']['account_number'] )
        ) {
            $account_number = sanitize_text_field( $request['request']['transaction']['account_number'] );
            $last_four = substr( $account_number, -4 );
            $request['request']['transaction']['account_number'] = '************' . $last_four;
        }

        if (
            isset( $request['request']['transaction']['exp_date'] ) &&
            ! empty( $request['request']['transaction']['exp_date'] )
        ) {
            $request['request']['transaction']['exp_date'] = '****';
        }

        if (
            isset( $request['request']['transaction']['cvv'] ) &&
            ! empty( $request['request']['transaction']['cvv'] )
        ) {
            $request['request']['transaction']['cvv'] = '****';
        }

        return $request;
    }

    public function handleResponse( $res ) {
        $response = json_decode( $res, true );
        $return   = array();

        if (
            isset( $response['transaction']['status_id'] ) &&
            in_array( $response['transaction']['status_id'], array( 101, 111, 102 ), true )
        ) {
            $return['valid'] = 'success';
        } else {
            $return['valid']     = 'fail';
            $return['errorcode'] = isset( $response['transaction']['configuration_id'] )
                ? sanitize_text_field( $response['transaction']['configuration_id'] )
                : 'unknown';
        }

        return $return;
    }


    public function checkIfPartialPaymentAndVoid( $response, $epoint, $order_id ) {
        if (
            isset( $response[ $epoint ] ) &&
            isset( $response[ $epoint ]['type_id'], $response[ $epoint ]['reason_code_id'], $response[ $epoint ]['transaction_amount'], $response[ $epoint ]['auth_amount'] ) &&
            (int) $response[ $epoint ]['type_id'] === 20 &&
            (int) $response[ $epoint ]['reason_code_id'] === 1201 &&
            $response[ $epoint ]['transaction_amount'] !== $response[ $epoint ]['auth_amount']
        ) {
            $reque = array(
                'transactions_id' => sanitize_text_field( $response[ $epoint ]['id'] ),
            );

            // Make void call
            $this->voidTransaction( $reque, $order_id );

            return true; // Partial payment found
        }

        return false;
    }

    public function voidTransaction( $request = array(), $order_id = 0 ) {
        if ( empty( $request['transactions_id'] ) ) {
            return array(
                'status' => 'error',
                'message' => __( 'Transaction ID is missing.', 'payment-gateway-expinet-and-woocommerce-integration' ),
            );
        }

        $endpoint = 'transactions/' . sanitize_text_field( $request['transactions_id'] );

        $api_request = array(
            'transaction' => array(
                'action' => 'void',
            ),
        );

        $void_request_json = wp_json_encode( $api_request );

        // Make the API call
        $response = $this->apiCall( $order_id, $endpoint, $void_request_json, 'PUT' );

        return $response;
    }

    public function expipaga_handle_order_status_completed( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order instanceof WC_Order ) {
            return false;
        }

        $transaction_id = $order->get_transaction_id();
        $payment_method = $order->get_payment_method();
        $enabled_hold   = $order->get_meta( 'order_enable_hold' );

        if ( $payment_method === 'expinet' && $enabled_hold === 'yes' ) {

            $order_total     = (float) $order->get_total();
            $total_refunded  = (float) $order->get_total_refunded();
            $net_total       = $order_total - $total_refunded;

            if ( $net_total > 0 ) {
                $reque = array(
                    'transactions_id'    => sanitize_text_field( $transaction_id ),
                    'transaction_amount' => $net_total,
                );

                $auth_response = $this->authCompleteTransaction( $reque, $order_id );

                if ( isset( $auth_response['data']['transaction'] ) && is_array( $auth_response['data']['transaction'] ) ) {

                    $auth_expinet_data = $order->get_meta( 'expinet_data' );

                    $order->update_meta_data( 'expinet_data', $auth_response['data']['transaction'] );
                    $order->update_meta_data( 'auth_expinet_data', $auth_expinet_data );
                    $order->save();

                    return true;

                } else {
                    $msg = array();

                    if ( ! empty( $auth_response['data']['errors'] ) ) {
                        foreach ( $auth_response['data']['errors'] as $key => $error ) {
                            $msg[] = esc_html( $error[0] );
                        }
                        wc_add_notice( implode( "\n", $msg ), 'error' );
                    }

                    $order->update_status( 'processing' );
                    $order->save();

                    return false;
                }
            }

            return true;
        }

        return true;
    }

    // Disable payment gateway for Restricted types of product
    public function expipaga_woocommerce_available_payment_gateways( $available_gateways ) {
        if ( is_admin() || ! is_checkout() ) {
            return $available_gateways;
        }

        $restricted_types = array( 'subscription', 'variable-subscription' );
        $unset_expinet = false;

        foreach ( WC()->cart->get_cart_contents() as $item ) {
            $product = wc_get_product( $item['product_id'] );
            if ( $product && in_array( $product->get_type(), $restricted_types, true ) ) {
                $unset_expinet = true;
                break;
            }
        }

        if ( $unset_expinet && isset( $available_gateways['expinet'] ) ) {
            unset( $available_gateways['expinet'] );
        }

        return $available_gateways;
    }

    public function authCompleteTransaction( $request = array(), $order_id = 0 ) {

        if ( empty( $request['transactions_id'] ) || empty( $request['transaction_amount'] ) ) {
            return array(
                'status' => false,
                'msg'    => __( 'Missing transaction details.', 'payment-gateway-expinet-and-woocommerce-integration' )
            );
        }

        $endPoint = 'transactions/' . sanitize_text_field( $request['transactions_id'] );

        $api_request = array(
            'action'             => 'authcomplete',
            'transaction_amount' => floatval( $request['transaction_amount'] ),
        );

        $authcomplete_request = json_encode( array( 'transaction' => $api_request ) );

        $response = $this->apiCall( $order_id, $endPoint, $authcomplete_request, 'PUT' );

        if ( isset( $response['status'] ) && $response['status'] === 'success' && empty( $response['data']['errors'] ) ) {
            return array(
                'request' => $response['request'],
                'data'    => $response['data'],
                'status'  => true,
            );
        }

        $msg = array();
        if ( ! empty( $response['data']['errors'] ) ) {
            foreach ( $response['data']['errors'] as $error ) {
                $msg[] = esc_html( $error[0] );
            }
        }

        return array(
            'request' => $response['request'],
            'data'    => $response['data'],
            'status'  => false,
            'msg'     => implode( "\n", $msg ),
        );
    }

}