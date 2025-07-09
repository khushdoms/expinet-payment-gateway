<?php 
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Expinent_Payment_Log extends WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct(
			[
				'singular' => __( 'Expinent Payment Log', 'epg' ), // Singular name of listed records
				'plural'   => __( 'Expinent Payment Logs', 'epg' ), // Plural name of listed records
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

		$sql = "SELECT * FROM {$wpdb->prefix}expinent_api_data";

		if ( ! empty( $_REQUEST['s'] ) ) {
			$search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
			$sql .= $wpdb->prepare( ' WHERE order_id LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$order_by = esc_sql( wp_unslash( $_REQUEST['orderby'] ) );
			$order    = ! empty( $_REQUEST['order'] ) ? esc_sql( wp_unslash( $_REQUEST['order'] ) ) : 'ASC';
			$sql     .= " ORDER BY $order_by $order";
		} else {
			$sql .= ' ORDER BY api_date DESC';
		}

		$offset = ( $page_number - 1 ) * $per_page;
		$sql   .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, $offset );

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Delete an expinent payment record.
	 *
	 * @param int $id Expinent payment ID.
	 */
	public static function delete_expinent( $id ) {
		global $wpdb;

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

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}expinent_api_data";

		return $wpdb->get_var( $sql );
	}


	/** Text displayed when no expinent data is available */
	public function no_items() {
		_e( 'No expinent payment avaliable.', 'epg' );
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
                return $item[ $column_name ];
			case 'api_request':
            case 'api_response' :
                return print_r( unserialize($item[ $column_name ]), true );
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
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

		$title = '<strong>' . $item['name'] . '</strong>';

		$actions = [
			'delete' => sprintf( '<a href="?page=%s&action=%s&expinent=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce )
		];

		return $title . $this->row_actions( $actions );
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'cb'           => '<input type="checkbox" />',
			'api_date'     => __( 'Date', 'epg' ),
			'order_id'     => __( 'Order ID', 'epg' ),
			'api_request'  => __( 'Request', 'epg' ),
			'api_response' => __( 'Response', 'epg' ),
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
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
            return;
        }
 
        $input_id = $input_id . '-search-input';
 
        if ( ! empty( $_REQUEST['orderby'] ) ) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
        }
        if ( ! empty( $_REQUEST['order'] ) ) {
            echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
        }
        if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
            echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
        }
        if ( ! empty( $_REQUEST['detached'] ) ) {
            echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
        }
        ?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
			<input type="search" placeholder="Order/SO Number" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
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
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, 'sp_delete_expinent' ) ) {
				die( 'Go get a life script kiddies' );
			}
			else {
				self::delete_expinent( absint( $_GET['expinent'] ) );
		                wp_redirect( esc_url_raw(add_query_arg()) );
				exit;
			}

		}

		// If the delete bulk action is triggered
		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$delete_ids = esc_sql( $_POST['bulk-delete'] );

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