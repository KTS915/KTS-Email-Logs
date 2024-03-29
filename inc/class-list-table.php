<?php

# CREATE LIST PAGE
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class KTS_Email_Logs extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'Email Log', 'kts_email_logs' ),
			'plural'   => __( 'Email Logs', 'kts_email_logs' ),
			'ajax'     => false // does not support ajax
		) );

		add_action( 'admin_head', array( $this, 'admin_header' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

	function admin_header() {
		if ( empty( $_GET['page'] ) || $_GET['page'] !== 'email-logs' ) {
			return;
		}
		echo '<style>';
		echo '.wp-list-table .column-status { width: 6em; }';
		echo '.wp-list-table .column-recipient { width: 15em; }';
		echo '.wp-list-table .column-email { width: 20em; }';
		echo '.wp-list-table .column-sent { width: 15em;}';
		echo '.wp-list-table .column-message_id { width: 5em;}';
		echo '.dot-green { height: 10px; width: 10px; margin-left: 15px; border-radius: 50%; display: inline-block;	background-color: green; }';
		echo '.dot-red { height: 10px; width: 10px;	margin-left: 15px; border-radius: 50%; display: inline-block; background-color: red; }';
		echo '#export-all-logs { margin-top: 3px; )';
		echo '</style>';
	}
	
	/* ENQUEUE CSS AND JAVASCRIPT */
	function enqueue_scripts() {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_enqueue_style( 'dialog-css', plugins_url( 'css/dialog.css', dirname( __FILE__ ) ) );
		wp_enqueue_script( 'display-email', plugins_url( 'js/display-email' . $suffix . '.js', dirname( __FILE__ ) ), array(), null, true );
	}

	/**
	 * Retrieve a log record.
	 *
	 * @param int $id log ID
	 */
	public static function get_log( $id ) {
		global $wpdb;		
		$table_name = $wpdb->prefix . 'kts_email_logs';

		$sql = $wpdb->prepare( "SELECT * FROM $table_name WHERE message_id = %d", $id );

		return $wpdb->get_row( $sql, ARRAY_A );
	}

	/**
	 * Retrieve multiple log records.
	 *
	 * @param int $id log ID
	 */
	public static function get_selected_logs( $ids ) {
		global $wpdb;		
		$table_name = $wpdb->prefix . 'kts_email_logs';
		$count = count( $ids );
		$imploded = implode( ',', array_fill( 0, $count, '%d' ) );

		$sql = $wpdb->prepare( "SELECT * FROM $table_name WHERE message_id IN ($imploded)", $ids );

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Retrieve all log records.
	 *
	 * @param int $id log ID
	 */
	public static function get_all_logs() {
		global $wpdb;		
		$table_name = $wpdb->prefix . 'kts_email_logs';
	
		return $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
	}

	/**
	 * Delete a log record.
	 *
	 * @param int $id log ID
	 */
	public static function delete_log( $id ) {
		global $wpdb;		
		$table_name = $wpdb->prefix . 'kts_email_logs';

		$wpdb->delete(
			$table_name,
			['message_id' => $id],
			['%d']
		);
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;		
		$table_name = $wpdb->prefix . 'kts_email_logs';

		$sql = "SELECT COUNT(*) FROM $table_name";

		return $wpdb->get_var( $sql );
	}

	/**
	 * Returns the count of emails successfully sent.
	 *
	 * @return null|string
	 */
	public static function successful_count() {
		global $wpdb;		
		$table_name = $wpdb->prefix . 'kts_email_logs';

		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE status = %d", 1 );

		return $wpdb->get_var( $sql );
	}

	/**
	 * Returns the count of failed emails.
	 *
	 * @return null|string
	 */
	public static function failed_count() {
		global $wpdb;		
		$table_name = $wpdb->prefix . 'kts_email_logs';

		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE status = %d", 0 );

		return $wpdb->get_var( $sql );
	}

	/**
	 * Returns the count of emails matching the specified search temr.
	 *
	 * @return null|string
	 */
	public static function search_count() {
		global $wpdb;		
		$table_name = $wpdb->prefix . 'kts_email_logs';
		$search_term = sanitize_text_field( $_GET['s'] );

		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE recipient = %s OR email = %s OR subject = %s OR message = %s", $search_term, $search_term, $search_term, $search_term );

		return $wpdb->get_var( $sql );
	}


	/** Text displayed when no log data is available */
	public function no_items() {
		_e( 'No logs available.', 'kts_email_logs' );
	}


	/**
	 * Render columns.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		$timezone = get_option( 'timezone_string' );
		$email_logs = get_option( 'email-logs' );
		$indicator = $email_logs && $email_logs['status'] ? $email_logs['status'] : 'symbols';

		switch ( $column_name ) {
			case 'status':
				if ( $item[$column_name] === '1' ) {
					if ( $indicator === 'colors' ) {
						return '<span class="dot-green"></span>';
					}
					elseif ( $indicator === 'symbols' ) {
						return '<span>&#9989;</span>';
					}
					elseif ( $indicator === 'text' ) {
						return '<span>Success</span>';
					}
				} else {
					if ( $indicator === 'colors' ) {
						return '<span class="dot-red"></span>';
					}
					elseif ( $indicator === 'symbols' ) {
						return '<span>&#10060;</span>';
					}
					elseif ( $indicator === 'text' ) {
						return '<span>Failed</span>';
					}
				}
			case 'recipient':
				return esc_html( $item[$column_name] );
			case 'email':
				return make_clickable( esc_html( $item[$column_name] ) );
			case 'subject':
				return esc_html( $item[$column_name] );
			case 'message':
				return apply_filters( 'email_message', $item[$column_name] ) . '<div class="hidden">' . esc_html( $item[$column_name] ) . '</div>';
			case 'headers':
				$headers = '';
				$col_names = json_decode( $item[$column_name], true );
				if ( ! empty( $col_names ) ) {
					foreach( $col_names as $header ) {
						$headers .= esc_html( $header ) . '<br>';
					}
				}
				return $headers;
			case 'attachments':
				$attachments = '';
				$col_attachments = json_decode( $item[$column_name], true );
				if ( ! empty( $col_attachments ) ) {
					foreach( $col_attachments as $attachment ) {
						$attachments .= esc_html( basename( $attachment ) ) . '<br>';
					}
				}
				return $attachments;
			case 'sent':
				$date = new DateTime();
				$date->setTimestamp( $item[$column_name] );
				$date->setTimezone( new DateTimeZone( $timezone ) );
				return $date->format( 'l, F jS, Y \a\t g:ia' );
			case 'message_id':
				return absint( $item[$column_name] );
			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes
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
		$id = absint( $item['message_id'] );
		$recipient = esc_html( $item['recipient'] );
		return sprintf(
			'<label class="screen-reader-text" for="log_%1$d">' . sprintf( __( 'Select Log for %s' ), $recipient ) . '</label><input type="checkbox" name="email_logs[]" id="log_%1$d" value="%1$d" />', $id
		);
	}

	/**
	 * Enable Delete, Resend, and Show for each individual item
	 * 
	 * Uses true nonces
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_recipient( $item ) {

		$page = esc_attr( $_GET['page'] );
		$message_id = absint( $item['message_id'] );
		$real_nonce = cp_set_nonce( 'real_kts_email_nonce' );
		$nonce = $real_nonce['name'] . '-' . $real_nonce['value'];

		$actions = array(

			'delete' => sprintf( '<a href="?page=%s&action=%s&log=%s&_wpnonce=%s">Delete</a>', $page, 'delete', $message_id, $nonce ),

			'resend' => sprintf( '<a href="?page=%s&action=%s&log=%s&_wpnonce=%s">Resend</a>', $page,'resend', $message_id, $nonce ),

			'show'	 => sprintf( '<a class="email-show" data-id="' . $message_id . '" data-micromodal-trigger="modal-1" href="?page=%s&action=%s&log=%s&_wpnonce=%s#email-logs-modal">Show</a>', $page, 'show', $message_id, $nonce )

		);

		return $item['recipient'] . $this->row_actions( $actions );
	}
	
	/**
	 * Add extra markup in the toolbars before (top) or after (bottom) the list
	 * 
	 * @param string $which, either top or bottom
	 */
	function extra_tablenav( $which ) {
		if ( $which === 'top' ) { ?>

			<button type="submit" id="export-all-logs" name="action" class="button button-primary" value="export-all-logs"><?php _e( 'Export All Logs', 'kts_email_logs' ); ?></button> <?php

		}

		elseif ( $which === 'bottom' ) { // modal (to be filled by JavaScript)
		?>
			
			<dialog id="modal-details" aria-labelledby="modal-1-title">

				<header class="modal-header">
					<h2 id="modal-1-title"></h2>
					<button type="button" id="modal-close" class="modal-close" aria-label="Close modal" value="close" autofocus></button>
				</header>

				<div class="modal-content-content">
					<div id="modal-1-content" class="modal-content"></div>
					<p id="modal-1-headers"></p>

					<footer class="modal-footer">
						<button type="button" id="modal-btn" class="modal-btn" value="close">Close</button>
					</footer>
				</div>

			</dialog>
			
		<?php
		}
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'			=> '<input type="checkbox" />',
			'status'		=> __( 'Status', 'kts_email_logs' ),
			'recipient'		=> __( 'Recipient', 'kts_email_logs' ),
			'email'			=> __( 'Email', 'kts_email_logs' ),
			'subject'		=> __( 'Subject', 'kts_email_logs' ),
			'message'		=> __( 'Message', 'kts_email_logs' ),
			'headers'		=> __( 'Headers', 'kts_email_logs' ),
			'attachments'	=> __( 'Attachments', 'kts_email_logs' ),
			'sent'			=> __( 'Sent', 'kts_email_logs' ),
			'message_id'	=> __( 'ID', 'kts_email_logs' )
		);

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'status'	=> array( 'status', true ),
			'recipient'	=> array( 'recipient', true ),
			'email'		=> array( 'email', true ),
			'subject'	=> array( 'subject', true ),
			'message'	=> array( 'message', true ),
			'sent'		=> array( 'sent', true ),
			'message_id'=> array( 'message_id', true )
		);

		return $sortable_columns;
	}

	protected function get_views() {
		$status_links = array();
		$current = ( ! empty( $_GET['status'] ) ) ? $_GET['status'] : 'all';
		
		# All actions
		$class = ( $current === 'all' ) ? ' class="current"' : '';
		$status_links['all'] = '<a href="?page=email-logs"' . $class . '>' . __( 'All', 'kts_email_logs' ) . '</a> (' . self::record_count() . ')';

		$items = array(
			'successful' => array( 'name' => __( 'Successful', 'kts_email_logs' ), 'count' => self::successful_count(), 'status_id' => 1 ),

			'failed' => array( 'name' => __( 'Failed', 'kts_email_logs' ), 'count' => self::failed_count(), 'status_id' => 2 )
		);

		foreach( $items as $key => $item ) {			
			$status_query = '?page=email-logs&status=' . $key;
			$status_class = ( $current === $key ) ? ' class="current"' : '';
			$status_links[$key] = '<a href="' . $status_query . '"' . $status_class . '>' . $item['name'] . '</a> (' . $item['count'] . ')';
		}

		return $status_links;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => 'Delete',
			'bulk-resend' => 'Resend',
			'bulk-export' => 'Export'
		);

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'kts_email_logs';

		$this->_column_headers = $this->get_column_info();

		# Process bulk action
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'logs_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

        # Prepare query params, as usual current page, order by and order direction
		$paged = isset( $_GET['paged'] ) ? ( $per_page * max( 0, absint( $_GET['paged'] ) - 1 ) ) : 0;

		$orderby = ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array_keys( $this->get_sortable_columns() ) ) ) ? $_GET['orderby'] : 'message_id';

		$order = ( isset( $_GET['order'] ) && in_array( $_GET['order'], array('asc', 'desc') ) ) ? $_GET['order'] : 'desc';

		# Display logs
		if ( ( empty( $_GET['status'] ) || ! in_array( $_GET['status'], ['successful', 'failed'] ) ) && empty( $_GET['s'] ) ) { // display all logs

			# Define $items array
			$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged ), ARRAY_A );

			$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $per_page,			
				'total_pages' => ceil( $total_items / $per_page ) // calculate pages count
			) );
		}

		elseif ( ! empty( $_GET['status'] ) ) { // display logs according to status

			if ( $_GET['status'] === 'successful' ) {
				$status = 1;
				$status_items = (int) self::successful_count();
			}
			elseif ( $_GET['status'] === 'failed' ) {
				$status = 0;
				$status_items = (int) self::failed_count();
			}

			# Define $items array
			$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE status = %d ORDER BY $orderby $order LIMIT %d OFFSET %d", $status, $per_page, $paged ), ARRAY_A );

			$this->set_pagination_args( array(
				'total_items' => $status_items,
				'per_page'    => $per_page,			
				'total_pages' => ceil( $status_items / $per_page ) // calculate pages count
			) );

		}

		elseif ( ! empty( $_GET['s'] ) ) { // display logs according to search term

			$search_term = sanitize_text_field( $_GET['s'] );
			$search_items = (int) self::search_count();

			# Define $items array
			$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE recipient = %s OR email = %s OR subject = %s OR message = %s ORDER BY $orderby $order LIMIT %d OFFSET %d", $search_term, $search_term, $search_term, $search_term, $per_page, $paged ), ARRAY_A );

			$this->set_pagination_args( array(
				'total_items' => $search_items,
				'per_page'    => $per_page,			
				'total_pages' => ceil( $search_items / $per_page ) // calculate pages count
			) );

		}
	}	

	public function process_bulk_action() {
		if ( empty( $_GET['_wpnonce'] ) ) {
			return;
		}

		$action = $this->current_action();

		if ( $action !== 'bulk-export' ) {

			 if ( strpos( $action, 'bulk-' ) !== false || ! empty( $_GET['s'] ) ) {

				# Verify true nonce using Real Nonce
				$real_nonce = sanitize_key( $_GET['real_nonce'] );
				$exploded = $check_nonce = false;
				if ( strpos( $real_nonce, '-' ) !== false ) {
					$exploded = explode( '-', $real_nonce );
					$check_nonce = cp_check_nonce( $exploded[0], $exploded[1] );
				}

				if ( in_array( false, [$exploded, $check_nonce] ) ) {
					echo '<div class="notice notice-error is-dismissible"><p>That action is not possible without an appropriate nonce.</p></div>';
					return;
				}

				if ( ! empty( $_GET['email_logs'] ) ) {
					$ids = array_map( 'absint', $_GET['email_logs'] );
					$count = count( $ids ) > 1 ? count( $ids ) . ' logs' : '1 log';
				}

			}

			elseif ( $_GET['action'] !== 'export-all-logs' ) {

				# Verify true nonce using Real Nonce
				$nonce = sanitize_key( $_GET['_wpnonce'] );
				$exploded = $check_nonce = false;
				if ( strpos( $nonce, '-' ) !== false ) {
					$exploded = explode( '-', $nonce );
					$check_nonce = cp_check_nonce( $exploded[0], $exploded[1] );
				}

				if ( in_array( false, [$exploded, $check_nonce] ) ) {
					echo '<div class="notice notice-error is-dismissible"><p>That action is not possible without a nonce.</p></div>';
					return;
				}

				$id = ! empty( $_GET['log'] ) ? absint( $_GET['log'] ) : '';

			}

		}


		if ( $action ) {
			switch ( $action ) {

				case 'delete':
					self::delete_log( $id );

					echo '<div class="notice notice-success is-dismissible"><p>Log ID ' . $id . ' successfully deleted.</p></div>';
					break;

				case 'bulk-delete':
					foreach( $ids as $id ) {
						self::delete_log( $id );
					}

					echo '<div class="notice notice-success is-dismissible"><p>' . $count . ' successfully deleted.</p></div>';
					break;

				case 'resend':
					$log = self::get_log( $id );
					wp_mail(
						esc_html( $log['email'] ),
						esc_html( $log['subject'] ),
						esc_html( $log['message'] ),
						esc_html( $log['headers'] ),
						esc_html( $log['attachments'] ),
					);

					echo '<div class="notice notice-success is-dismissible"><p>Email ID ' . $id . ' resent.</p></div>';
					break;

				case 'bulk-resend':
					foreach( $ids as $id ) {
						$log = self::get_log( $id );
						wp_mail(
							esc_html( $log['email'] ),
							esc_html( $log['subject'] ),
							esc_html( $log['message'] ),
							esc_html( $log['headers'] ),
							esc_html( $log['attachments'] ),
						);
					}

					echo '<div class="notice notice-success is-dismissible"><p>' . str_replace( 'log', 'email', $count ) . ' resent.</p></div>';
					break;

				default:
					// do nothing or something else
					return;
					break;
			}
		}

	}

}
