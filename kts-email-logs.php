<?php
/**
 * Plugin Name: Email Logs
 * Plugin URI: https://timkaye.org
 * Description: Stores email logs in a custom database table
 * Version: 1.2.0
 * Author: Tim Kaye
 * Author URI: https://timkaye.org
 * Requires CP: 2.1
 * Requires PHP: 7.4
 * Requires at least: 6.2.3
 * License: GPLv3
 * Text Domain: kts-email-logs
*/

/* INCLUDE REQUIRED FILES */
require_once __DIR__ . '/inc/class-list-table.php'; // extends the WP_List_Table class
require_once __DIR__ . '/inc/database.php'; // creates custom database table
require_once __DIR__ . '/inc/settings.php'; // enables changes of settings for this plugin

/* FIRE HOOK FOR DATABASE CREATION */
register_activation_hook( __FILE__, 'kts_email_logs_create_db' );

# SEND DATA TO EMAIL LOGS
function kts_send_data_to_email_logs_on_success( $mail_data ) { // $mail_data
	global $wpdb;
	$table_name = $wpdb->prefix . 'kts_email_logs';

	$emails = '';
	$users = '';
	foreach( $mail_data['to'] as $key => $email ) {
		if ( $key === 0 ) {
			$emails .= sanitize_email( $email );
		}
		else {
			$emails .= ', ' . sanitize_email( $email );
		}

		$user = get_user_by( 'email', $email );
		if ( ! empty( $user ) ) {
			if ( empty( $users ) ) {
				$users .= sanitize_text_field( $user->display_name );
			}
			else {
				$users .= ', ' . sanitize_text_field( $user->display_name );
			}
		}
	}

	$headers = is_array( $mail_data['headers'] ) ? array_map( 'sanitize_text_field', $mail_data['headers'] ) : [sanitize_text_field( $mail_data['headers'] )];

	$attachments = is_array( $mail_data['attachments'] ) ? array_map( 'esc_url_raw', $mail_data['attachments'] ) : [esc_url_raw( $mail_data['attachments'] )];

	$email_array = array(
		'status'		=> 1,
		'recipient'		=> $users ?: '',
		'email'			=> $emails,
		'subject'		=> sanitize_text_field( $mail_data['subject'] ),
		'message'		=> filter_var( $mail_data['message'], FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
		'sent'			=> time(),
		'headers'		=> wp_json_encode( $headers ),
		'attachments'	=> wp_json_encode( $attachments ),
	);

	$wpdb->insert( $table_name, $email_array ); // $wpdb->insert sanitizes data
}
add_action( 'wp_mail_succeeded', 'kts_send_data_to_email_logs_on_success' ); // WP


function kts_send_data_to_email_logs_on_failure( $error ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'kts_email_logs';

	$emails = '';
	$users = '';
	foreach( $error->error_data['wp_mail_failed']['to'] as $key => $email ) {
		if ( $key === 0 ) {
			$emails .= sanitize_email( $email );
		}
		else {
			$emails .= ', ' . sanitize_email( $email );
		}

		$user = get_user_by( 'email', $email );
		if ( ! empty( $user ) ) {
			if ( empty( $users ) ) {
				$users .= sanitize_text_field( $user->display_name );
			}
			else {
				$users .= ', ' . sanitize_text_field( $user->display_name );
			}
		}
	}

	$headers = is_array( $error->error_data['wp_mail_failed']['headers'] ) ? array_map( 'sanitize_text_field', $error->error_data['wp_mail_failed']['headers'] ) : [sanitize_text_field( $error->error_data['wp_mail_failed']['headers'] )];

	$attachments = is_array( $error->error_data['wp_mail_failed']['attachments'] ) ? array_map( 'esc_url_raw', $error->error_data['wp_mail_failed']['attachments'] ) : [esc_url_raw( $error->error_data['wp_mail_failed']['attachments'] )];

	$email_array = array(
		'status'		=> 0,
		'recipient'		=> $users ?: '',
		'email'			=> $emails,
		'subject'		=> sanitize_text_field( $error->error_data['wp_mail_failed']['subject'] ),
		'message'		=> filter_var( $error->error_data['wp_mail_failed']['message'], FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
		'sent'			=> time(),
		'headers'		=> wp_json_encode( $headers ),
		'attachments'	=> wp_json_encode( $attachments ),
		'error'			=> sanitize_text_field( $error->errors['wp_mail_failed'][0] ),
		'exception'		=> absint( $error->error_data['wp_mail_failed']['phpmailer_exception_code'] ),
	);

	$wpdb->insert( $table_name, $email_array ); // $wpdb->insert sanitizes data
}
add_action( 'wp_mail_failed', 'kts_send_data_to_email_logs_on_failure' );


# RETRIEVE DATA FROM EMAIL LOGS
function kts_set_screen_email_logs( $status, $option, $value ) {
	return $value;
}
add_filter( 'set-screen-option', 'kts_set_screen_email_logs', 10, 3 );

function kts_menu_email_logs() {

	$hook = add_submenu_page(
		'tools.php',
		esc_html__( 'Email Logs', 'kts-email-logs' ),
		esc_html__( 'Email Logs', 'kts-email-logs' ),
		'manage_options',
		'email-logs',
		'kts_list_email_logs'
	);

	add_action( 'load-' . $hook, 'kts_screen_option_email_logs' );
	add_action( 'load-' . $hook, 'kts_csv_email_logs' );
}
add_action( 'admin_menu', 'kts_menu_email_logs' );

function kts_list_email_logs() {
	$logs = new KTS_Email_Logs();
	$logs->prepare_items();
	?>

	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Email Logs', 'kts-email-logs' ); ?></h1>
		<hr class="wp-header-end">
		<h2 class="screen-reader-text"><?php esc_html_e( 'Filter list of email logs', 'kts-email-logs' ); ?></h2>

		<?php
		$logs->views();
		?>

		<form id="email-logs-filter" method="get">
			<?php
			$logs->search_box( 'Search Logs', 'logs' );
			
			/*
			 * Ensures the current page reloads after bulk action or search submitted
			 * 
			 * Also submits true nonce with form using Real Nonce 
			 */
			?>

			<input type="hidden" name="page" value="email-logs">

			<?php
			wp_nonce_field( 'kts_email_logs_nonce', 'kts_email_logs_nonce' );
			$logs->display();
			?>

		</form>

		<br class="clear">
	</div>

<?php
}


# SCREEN OPTIONS
function kts_screen_option_email_logs() {

	$option = 'per_page';
	$args   = array(
		'label'   => esc_html__( 'Logs', 'kts-email-logs' ),
		'default' => 20,
		'option'  => 'logs_per_page'
	);

	add_screen_option( $option, $args );

	$logs = new KTS_Email_Logs();
}


# CSV EXPORT OF ALL OR MULTIPLE SELECTED ERROR LOGS
function kts_csv_email_logs() {

	# Bail if no action set
	if ( empty( $_GET['action'] ) ) {
		return;
	}

	# Bail if the action is neither of these
	if ( ! in_array( $_GET['action'], ['export-all-logs', 'bulk-export'], true ) ) {
		return;
	}

	# Bail if the action is bulk-export, but no IDs have been post in the $_GET variable
	if ( $_GET['action'] === 'bulk-export' && empty( $_GET['email_logs'] ) ) {
		return;
	}

	# Verify nonce
	$nonce = isset( $_GET['kts_email_logs_nonce'] ) ? sanitize_key( $_GET['kts_email_logs_nonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, 'kts_email_logs_nonce' ) ) {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'That action is not possible without a suitable nonce.', 'kts-email-logs' ) . '</p></div>';
		return;
	}

	# Get specified logs
	$logs = [];
	if ( $_GET['action'] === 'export-all-logs' ) {
		$logs = KTS_Email_Logs::get_all_logs();
	} else {
		$ids = array_map( 'absint', $_GET['email_logs'] );
		$logs = KTS_Email_Logs::get_selected_logs( $ids );
	}

	# Just in case logs are empty, bail out
	if ( empty( $logs) ) {
		return;
	}

	# Set details for the redirect to force download and name of CSV file
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' ); 
	header( 'Content-Description: File Transfer' ); 
	header( 'Content-type: text/csv' );
	header( 'Content-Disposition: attachment; filename="email-error-logs-' . wp_date( 'Y-m-d' ) . '.csv"' );
	header( 'Pragma: public' );
	header( 'Expires: 0' );

	# Create the CSV file
	$timezone = get_option( 'timezone_string' );

	$headers = array(
		__( 'Message ID', 'kts-email-logs' ),
		__( 'Status', 'kts-email-logs' ),
		__( 'Recipient', 'kts-email-logs' ),
		__( 'Email', 'kts-email-logs' ),
		__( 'Subject', 'kts-email-logs' ),
		__( 'Message', 'kts-email-logs' ),
		__( 'Headers', 'kts-email-logs' ),
		__( 'Attachments', 'kts-email-logs' ),
		__( 'Date Sent', 'kts-email-logs' ),
	);

	$file = fopen( 'php://output', 'w' );

	fputcsv( $file, $headers, ',', '"', '' );

	foreach( $logs as $log ) {

		$headers = '';
		$log_headers = json_decode( $log['headers'], true );
		if ( is_iterable( $log_headers ) ) {
			foreach( $log_headers as $key => $header ) {
				if ( $key === 0 ) {
					$headers .= $header;
				}
				else {
					$headers .= ', ' . $header;
				}
			}
		}

		$attachments = '';
		$log_attachments = json_decode( $log['attachments'], true );
		if ( is_iterable( $log_attachments ) ) {
			foreach( $log_attachments as $key => $attachment ) {
				if ( $key === 0 ) {
					$attachments .= $attachment;
				}
				else {
					$attachments .= ', ' . basename( $attachment );
				}
			}
		}

		fputcsv(
			$file,
			array(
				absint( $log['message_id'] ),
				esc_html( $log['status'] ),
				esc_html( $log['recipient'] ),
				esc_html( $log['email'] ),
				esc_html( $log['subject'] ),
				apply_filters( 'email_message_csv', $log['message'] ),
				esc_html( $headers ),
				esc_html( $attachments ),
				kts_wp_date ( 'l, F jS, Y \a\t g:ia', $log['sent'] ),
			),
			',',
			'"',
			''
		);

	}

	fclose( $file ); // CPCS: Ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose because the file is being created for download only.
	exit;
}


/* PARSE HTML EMAIL MESSAGE */
function kts_parse_html_message( $message ) {
	return wp_strip_all_tags( str_replace( ['</p>', '<br>'], ['</p> ', ' '], wp_specialchars_decode( $message, ENT_QUOTES ) ) );
}
add_filter( 'email_message', 'kts_parse_html_message' );

function kts_parse_html_message_in_csv_export( $message ) {
	preg_match_all( '~<p>(.*?)<\/p>~s', wp_specialchars_decode( $message, ENT_QUOTES ), $match );
	return wp_strip_all_tags( implode( ' ', $match[1] ) );
}
add_filter( 'email_message_csv', 'kts_parse_html_message_in_csv_export' );

function kts_wp_date( $format, $timestamp ) {
	$timezone = get_option( 'timezone_string' );
	$date = new DateTime();
	$date->setTimestamp( $timestamp );
	$date->setTimezone( new DateTimeZone( $timezone ) );
	return $date->format( $format );
}


# DELETE OLD LOGS
function kts_delete_old_email_logs() {

	global $wpdb;
	$table_name = $wpdb->prefix . 'kts_email_logs';
	$email_logs = (array) get_option( 'email-logs' );
	
	# Set length of time before deletion (with default of one week)
	$storage = WEEK_IN_SECONDS;
	$storage_options = array( 0, 604800, 1209600, 1814400, 2419200, 15780000 );

	if ( isset( $email_logs['storage'] ) && in_array( $email_logs['storage'], $storage_options ) ) {
		if ( $email_logs['storage'] === 0 ) { // do not delete logs
			return;
		}
		$storage = $email_logs['storage'];
	}
	$time_ago = time() - $storage;

	# $wpdb->query does not sanitize data, so use $wpdb->prepare
	$wpdb->query( $wpdb->prepare( "DELETE FROM %i WHERE sent < %d", $table_name, $time_ago ) ); // CPCS: %i supported since WP 6.2.
}
add_action( 'kts_email_logs_hook', 'kts_delete_old_email_logs' );

function kts_email_logs_cronjobs() {
	if ( ! wp_next_scheduled( 'kts_email_logs_hook' ) ) {
		wp_schedule_event( time(), 'hourly', 'kts_email_logs_hook' );
	}
}	
register_activation_hook( __FILE__, 'kts_email_logs_cronjobs' );
