<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WoocommerceExpinetPayment_Log extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			[
				'singular' => __( 'Expinet Payment Log', 'payment-gateway-expinet-and-woocommerce-integration' ), // Singular name of listed records
				'plural'   => __( 'Expinet Payment Logs', 'payment-gateway-expinet-and-woocommerce-integration' ), // Plural name of listed records
				'ajax'     => false, // Does this table support AJAX?
			]
		);
	}


	/**
	 * Retrieve Expinet data from the database.
	 *
	 * @param int $per_page    Number of records per page.
	 * @param int $page_number Current page number.
	 * @return array           Array of Expinet data.
	 */
	public static function get_expinet_data( $per_page = 5, $page_number = 1 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'expinet_api_data';
		$where_sql  = 'WHERE 1=1';
		$params     = [];

		// Search filter
		if ( isset( $_REQUEST['expinet_search_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['expinet_search_nonce'] ) ), 'expinet_payment_log_search' ) ){
			$search = '';
			if ( isset( $_REQUEST['s'] ) ) {
				$search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
			}
			$where_sql .= ' AND order_id LIKE %s';
			$params[]   = '%' . $wpdb->esc_like( $search ) . '%';
		}

		// Whitelist orderby and order - SECURE APPROACH
		$allowed_orderby = [ 
			'order_id' => 'order_id', 
			'api_date' => 'api_date' 
		];
		$order_by = 'api_date'; // default
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$orderby_input = sanitize_key( wp_unslash( $_REQUEST['orderby'] ) );
			if ( array_key_exists( $orderby_input, $allowed_orderby ) ) {
				$order_by = $allowed_orderby[ $orderby_input ];
			}
		}

		// Validate order direction - SECURE APPROACH
		$order = 'DESC'; // default
		if ( ! empty( $_REQUEST['order'] ) ) {
			$order_value = strtolower( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) );
			if ( 'asc' === $order_value ) {
				$order = 'ASC';
			}
		}

		// Pagination
		$offset = ( $page_number - 1 ) * $per_page;

		// SECURE METHOD: Build query without dynamic ORDER BY in prepare()
		// We construct the ORDER BY clause separately since it's been validated against whitelist
		$order_clause = sprintf( 'ORDER BY %s %s', 
			esc_sql( $order_by ), 
			esc_sql( $order ) 
		);

		// Prepare the main query parts separately
		$base_query = "SELECT * FROM {$table_name} {$where_sql}";
		$limit_query = "LIMIT %d OFFSET %d";

		// Combine the queries - ORDER BY is safe because it's whitelisted and escaped
		$sql = $wpdb->prepare(
			"{$base_query} {$order_clause} {$limit_query}",
			array_merge( $params, [ (int) $per_page, (int) $offset ] )
		);

		// Execute the query
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Delete an expinet payment record.
	 *
	 * @param int $id Expinet payment ID.
	 */
	public static function delete_expinet( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			"{$wpdb->prefix}expinet_api_data",
			[ 'id' => absint( $id ) ],
			[ '%d' ]
		);
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;

		$table = esc_sql( $wpdb->prefix . 'expinet_api_data' );

		$sql = "SELECT COUNT(*) FROM {$table}";// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No user input used; table name safely escaped.

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( $sql ); 
	}


	/** Text displayed when no expinet data is available */
	public function no_items() {
		 esc_html_e( 'No Expinet payment available.', 'payment-gateway-expinet-and-woocommerce-integration' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'order_id':
            case 'api_date':
                return esc_html( $item[ $column_name ] );
			case 'api_request':
            case 'api_response' :
                $value = maybe_unserialize( $item[ $column_name ] );
				if ( is_array( $value ) || is_object( $value ) ) {
					// Convert to readable JSON format for safe display
					$output = wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
				} else {
					$output = $value;
				}
				return '<pre>' . esc_html( $output ) . '</pre>';
			default:
				$output = wp_json_encode( $item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
            	return '<pre>' . esc_html( $output ) . '</pre>';
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
		);
	}


	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {
		$delete_nonce = wp_create_nonce( 'sp_delete_expinet' );

		$title = '<strong>' . esc_html( $item['name'] ) . '</strong>';

		$page = '';
		if ( isset( $_REQUEST['expinet_search_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['expinet_search_nonce'] ) ), 'expinet_payment_log_search' ) ){
			if ( isset( $_REQUEST['page'] ) ) {
				$page = sanitize_text_field( wp_unslash( $_REQUEST['page'] ) );
			}

			$actions = [
				'delete' => sprintf(
					'<a href="?page=%s&action=%s&expinet=%s&_wpnonce=%s">Delete</a>',
					esc_attr( $page ),
					'delete',
					absint( $item['id'] ),
					esc_attr( $delete_nonce )
				)
			];

			return $title . $this->row_actions( $actions );
		}
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'cb'           => '<input type="checkbox" />',
			'api_date'     => __( 'Date', 'payment-gateway-expinet-and-woocommerce-integration' ),
			'order_id'     => __( 'Order ID', 'payment-gateway-expinet-and-woocommerce-integration' ),
			'api_request'  => __( 'Request', 'payment-gateway-expinet-and-woocommerce-integration' ),
			'api_response' => __( 'Response', 'payment-gateway-expinet-and-woocommerce-integration' ),
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
            'api_date' => array( 'api_date', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => 'Delete'
		];

		return $actions;
	}

	/**
	 * Search box place holder
	 */

	public function search_box( $text, $input_id ) {
		if ( ! isset( $_REQUEST['expinet_search_nonce'] ) || 
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['expinet_search_nonce'] ) ), 'expinet_payment_log_search' ) ) {
			// Nonce is missing or invalid, so stop processing
			return;
		}

		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
            return;
        }
 
        $input_id = $input_id . '-search-input';
 
        if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) ) . '" />';
        }
        if ( ! empty( $_REQUEST['order'] ) ) {
            echo '<input type="hidden" name="order" value="' . esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['order']) ) ) . '" />';
        }
        if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
            echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['post_mime_type'] ) ) ) . '" />';
        }
        if ( ! empty( $_REQUEST['detached'] ) ) {
            echo '<input type="hidden" name="detached" value="' . esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['detached'] ) ) ) . '" />';
        }
        ?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">
				<?php echo esc_html( $text ); ?>:
			</label>
			<input type="search" placeholder="Order/SO Number" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
			 <?php wp_nonce_field( 'expinet_payment_log_search', 'expinet_search_nonce' ); ?>
				<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
		</p>
        <?php
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'expinet_per_page', 5 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = self::get_expinet_data( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.

			if ( ! isset( $_REQUEST['_wpnonce'] ) || 
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'sp_delete_expinet' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				if ( isset( $_GET['expinet'] ) && ! empty( $_GET['expinet'] ) ) {
					self::delete_expinet( absint( $_GET['expinet'] ) );
							wp_redirect( esc_url_raw(add_query_arg()) );
					exit;
				}
			}

		}

		// If the delete bulk action is triggered
		$delete_ids = [];
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'sp_delete_expinet' ) ) {
			if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
			) {
				if ( isset( $_POST['bulk-delete'] ) && ! empty( $_POST['bulk-delete'] ) ) {
					$raw_ids = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['bulk-delete'] ) );
					$delete_ids = array_map( 'absint', $raw_ids );
					$delete_ids = array_filter( $delete_ids ); // Remove any zero values
				}

				// loop over the array of record IDs and delete them
				foreach ( $delete_ids as $id ) {
					self::delete_expinet( $id );

				}

				// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
					// add_query_arg() return the current url
					wp_redirect( esc_url_raw(add_query_arg()) );
				exit;
			}
		}
	}

}


class WoocommerceExpinetPayment_Plugin {

	// class instance
	static $instance;

	// expinet WP_List_Table object
	public $expinet_payment_log;

	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function plugin_menu() {

		$hook = add_submenu_page(
            'woocommerce',
			'Expinet Payment Log',
			'Expinet Payment Log',
			'manage_options',
			'expinet-payment-log',
			array($this, 'plugin_settings_page')
		);

		add_action( "load-$hook", [ $this, 'screen_option' ] );

	}


	/**
	 * Plugin settings page
	 */
	public function plugin_settings_page() {
		?>
		<div class="wrap">
			<h2>Expinet Payment Log</h2>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder">
					<div id="post-body-content">
						<div class="meta-box-sortables">
							<form method="post">
								<?php
								$this->expinet_payment_log->prepare_items();
                                $this->expinet_payment_log->search_box('Search', 'order_id');
								$this->expinet_payment_log->display(); ?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
	<?php
	}

	/**
	 * Screen options
	 */
	public function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Expinet Data',
			'default' => 5,
			'option'  => 'expinet_per_page'
		];

		add_screen_option( $option, $args );

		$this->expinet_payment_log = new WoocommerceExpinetPayment_Log();
	}


	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
WoocommerceExpinetPayment_Plugin::get_instance();