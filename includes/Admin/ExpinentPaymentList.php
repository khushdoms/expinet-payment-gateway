<?php 
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Expinent_Payment_Log extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			[
				'singular' => __( 'Expinent Payment Log', 'expinet-payment-gateway' ), // Singular name of listed records
				'plural'   => __( 'Expinent Payment Logs', 'expinet-payment-gateway' ), // Plural name of listed records
				'ajax'     => false, // Does this table support AJAX?
			]
		);
	}


	/**
	 * Retrieve expinent data from the database.
	 *
	 * @param int $per_page    Number of records per page.
	 * @param int $page_number Current page number.
	 * @return array           Array of expinent data.
	 */
	public static function get_expinent_data( $per_page = 5, $page_number = 1 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'expinent_api_data';
		$where_sql  = 'WHERE 1=1';
		$params     = [];

		// Search filter
		if ( isset( $_REQUEST['expinent_search_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['expinent_search_nonce'] ) ), 'expinent_payment_log_search' ) ){
			$search = '';
			if ( isset( $_REQUEST['s'] ) ) {
				$search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
			}
			$where_sql .= ' AND order_id LIKE %s';
			$params[]   = '%' . $wpdb->esc_like( $search ) . '%';
		}

		// Whitelist orderby and order
		$allowed_orderby = [ 'order_id', 'api_date' ];
		$order_by        = 'api_date';
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$orderby_input = sanitize_key( wp_unslash( $_REQUEST['orderby'] ) );
			if ( in_array( $orderby_input, $allowed_orderby, true ) ) {
				$order_by = $orderby_input;
			}
		}

		$order = 'DESC';
		if ( ! empty( $_REQUEST['order'] ) ) {
			if ( isset( $_REQUEST['order'] ) ) {
				$order_value = strtolower( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) );
			}
			if ( in_array( $order_input, [ 'asc', 'desc' ], true ) ) {
				$order = strtoupper( $order_input );
			}
		}

		// Pagination
		$offset = ( $page_number - 1 ) * $per_page;
		$params[] = (int) $per_page;
		$params[] = (int) $offset;

		// Final query string
		$sql = "
			SELECT *
			FROM {$table_name}
			{$where_sql}
			ORDER BY {$order_by} {$order}
			LIMIT %d OFFSET %d
		";

		// Return the result directly from prepare()
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); 
	}

	/**
	 * Delete an expinent payment record.
	 *
	 * @param int $id Expinent payment ID.
	 */
	public static function delete_expinent( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			"{$wpdb->prefix}expinent_api_data",
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

		$table = esc_sql( $wpdb->prefix . 'expinent_api_data' );

		$sql = "SELECT COUNT(*) FROM {$table}";// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No user input used; table name safely escaped.

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( $sql ); 
	}


	/** Text displayed when no expinent data is available */
	public function no_items() {
		 esc_html_e( 'No expinent payment available.', 'expinet-payment-gateway' );
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
		$delete_nonce = wp_create_nonce( 'sp_delete_expinent' );

		$title = '<strong>' . esc_html( $item['name'] ) . '</strong>';

		$page = '';
		if ( isset( $_REQUEST['expinent_search_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['expinent_search_nonce'] ) ), 'expinent_payment_log_search' ) ){
			if ( isset( $_REQUEST['page'] ) ) {
				$page = sanitize_text_field( wp_unslash( $_REQUEST['page'] ) );
			}

			$actions = [
				'delete' => sprintf(
					'<a href="?page=%s&action=%s&expinent=%s&_wpnonce=%s">Delete</a>',
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
			'api_date'     => __( 'Date', 'expinet-payment-gateway' ),
			'order_id'     => __( 'Order ID', 'expinet-payment-gateway' ),
			'api_request'  => __( 'Request', 'expinet-payment-gateway' ),
			'api_response' => __( 'Response', 'expinet-payment-gateway' ),
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
		if ( ! isset( $_REQUEST['expinent_search_nonce'] ) || 
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['expinent_search_nonce'] ) ), 'expinent_payment_log_search' ) ) {
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
			 <?php wp_nonce_field( 'expinent_payment_log_search', 'expinent_search_nonce' ); ?>
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

		$per_page     = $this->get_items_per_page( 'expinent_per_page', 5 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = self::get_expinent_data( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.

			if ( ! isset( $_REQUEST['_wpnonce'] ) || 
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'sp_delete_expinent' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				if ( isset( $_GET['expinent'] ) && ! empty( $_GET['expinent'] ) ) {
					self::delete_expinent( absint( $_GET['expinent'] ) );
							wp_redirect( esc_url_raw(add_query_arg()) );
					exit;
				}
			}

		}

		// If the delete bulk action is triggered
		$delete_ids = [];
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {
			if ( isset( $_POST['bulk-delete'] ) && ! empty( $_POST['bulk-delete'] ) ) {
				$raw_ids = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['bulk-delete'] ) );
				$delete_ids = array_map( 'absint', $raw_ids );
				$delete_ids = array_filter( $delete_ids ); // Remove any zero values
			}

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				self::delete_expinent( $id );

			}

			// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
		        // add_query_arg() return the current url
		        wp_redirect( esc_url_raw(add_query_arg()) );
			exit;
		}
	}

}


class Expinent_Plugin {

	// class instance
	static $instance;

	// expinent WP_List_Table object
	public $expinent_payment_log;

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
			'Expinent Payment Log',
			'Expinent Payment Log',
			'manage_options',
			'expinent-payment-log',
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
			<h2>Expinent Payment Log</h2>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder">
					<div id="post-body-content">
						<div class="meta-box-sortables">
							<form method="post">
								<?php
								$this->expinent_payment_log->prepare_items();
                                $this->expinent_payment_log->search_box('Search', 'order_id');
								$this->expinent_payment_log->display(); ?>
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
			'label'   => 'Expinent Data',
			'default' => 5,
			'option'  => 'expinent_per_page'
		];

		add_screen_option( $option, $args );

		$this->expinent_payment_log = new Expinent_Payment_Log();
	}


	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
Expinent_Plugin::get_instance();