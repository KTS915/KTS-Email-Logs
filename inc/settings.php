<?php

function kts_email_logs_settings() {

	$hook = add_submenu_page(
		'options-general.php',
		'Email Logs',
		'Email Logs',
		'manage_options',
		'email-logs',
		'kts_email_logs_settings_setup'
	);

	//add_action( 'load-' . $hook, 'kts_email_logs_function' ); just in case!
}
add_action( 'admin_menu', 'kts_email_logs_settings' );

function kts_email_logs_settings_init() {

	# Specify name of option for saving and retrieving values
	register_setting( 'kts_email_logs_settings', 'email-logs', array( 
		'sanitize_callback' => 'kts_email_logs_sanitize_options_array',
	) );

	# Status Indicator
	add_settings_section(
		'kts_email_logs_status_section',
		'',
		'',
		'kts_email_logs_settings'
	);

	add_settings_field(
		'kts_email_logs_status',
		esc_html__( 'Preferred Status Indicator', 'kts-email-logs' ),
		'kts_email_logs_status_render',
		'kts_email_logs_settings',
		'kts_email_logs_status_section'
	);

	# Log Storage
	add_settings_section(
		'kts_email_logs_storage_section',
		'',
		'',
		'kts_email_logs_settings'
	);

	add_settings_field(
		'kts_email_logs_storage',
		esc_html__( 'Auto-delete logs?', 'kts-email-logs' ),
		'kts_email_logs_storage_render',
		'kts_email_logs_settings',
		'kts_email_logs_storage_section'
	);
}
add_action( 'admin_init', 'kts_email_logs_settings_init' );

/* SANITIZE OPTION FIELDS */
function kts_email_logs_sanitize_options_array( $inputs_array ) {

	# Create array for storing the sanitized options
	$output = [];

	# Loop through each of the options inputs
	foreach( $inputs_array as $key => $value ) {

		# Storage should be digits only
		if ( $key === 'storage' ) {
			$output[$key] = absint( $value );
		}

		# Sanitize all other text inputs
		else {
			$output[$key] = sanitize_text_field( $value );
		}
	}

	# Return output array
	return apply_filters( 'kts_email_logs_options_array', $output, $inputs_array );
}


/* CHOOSE STATUS INDICATOR */
function kts_email_logs_status_render() {

	$email_logs = (array) get_option( 'email-logs' );

	# Set default indicator to colored dot
	$status_options = array( 'colors', 'symbols', 'text' );
	if ( empty( $email_logs ) || empty( $email_logs['status'] ) || ! in_array( $email_logs['status'], $status_options ) ) {
		$email_logs['status'] = 'colors';
	}
	?>

	<fieldset>
		<label>
			<input type="radio" name="email-logs[status]" value="colors" <?php checked( $email_logs['status'], 'colors' ); ?>>
			<span class=""><?php esc_html_e( 'Colors', 'kts-email-logs' ); ?></span>
		</label>
	</fieldset>

	<fieldset>
		<label>
			<input type="radio" name="email-logs[status]" value="symbols" <?php checked( $email_logs['status'], 'symbols' ); ?>>
			<span class=""><?php esc_html_e( 'Symbols', 'kts-email-logs' ); ?></span>
		</label>
	</fieldset>

	<fieldset>
		<label>
			<input type="radio" name="email-logs[status]" value="text" <?php checked( $email_logs['status'], 'text' ); ?>>
			<span class=""><?php esc_html_e( 'Text', 'kts-email-logs' ); ?></span>
		</label>
	</fieldset>

	<?php
}

/* CHOOSE LENGTH OF TIME TO STORE LOGS */
function kts_email_logs_storage_render() {

	$email_logs = (array) get_option( 'email-logs' );

	# Set default storage time to one week
	$storage_options = array( 604800, 1209600, 1814400, 2419200, 15780000 );
	if ( empty( $email_logs ) || empty( $email_logs['storage'] ) || ! in_array( $email_logs['timescale'], $storage_options ) ) {
		$email_logs['storage'] = 1;
		$email_logs['timescale'] = 604800;
	}
	?>

	<fieldset>
		<label>
			<input type="radio" name="email-logs[storage]" value="0" <?php checked( $email_logs['storage'], 0 ); ?>>
			<span class="date-time-text date-time-custom-text"><?php esc_html_e( 'No', 'kts-email-logs' ); ?></span>
		</label>
	<fieldset>

	<fieldset>
		<label>
			<input type="radio" name="email-logs[storage]" value="1" <?php checked( $email_logs['storage'], 1 ); ?>>
			<span class="date-time-text date-time-custom-text"><?php esc_html_e( 'Yes: delete messages that are over', 'kts-email-logs' ); ?> <span>

				<select id="timescale" name="email-logs[timescale]">

					<option value="604800" <?php selected( $email_logs['timescale'], 604800 ); ?>><?php esc_html_e( '1 week', 'kts-email-logs' ); ?></option>

					<option value="1209600" <?php selected( $email_logs['timescale'], 1209600 ); ?>><?php esc_html_e( '2 weeks', 'kts-email-logs' ); ?></option>

					<option value="1814400" <?php selected( $email_logs['timescale'], 1814400 ); ?>><?php esc_html_e( '3 weeks', 'kts-email-logs' ); ?></option>

					<option value="2419200" <?php selected( $email_logs['timescale'], 2419200 ); ?>><?php esc_html_e( '4 weeks', 'kts-email-logs' ); ?></option>

					<option value="15780000" <?php selected( $email_logs['timescale'], 15780000 ); ?>><?php esc_html_e( '6 months', 'kts-email-logs' ); ?></option>

				</select>

			</span> <?php esc_html_e( 'old', 'kts-email-logs' ); ?></span>

		</label>
	</fieldset>

	<?php
}

function kts_email_logs_settings_setup() {
	?>

	<div class="wrap">
		<form action='options.php' method='post'>

			<h1><?php esc_html_e( 'Email Log Settings', 'kts-email-logs' ); ?></h1>

			<?php
			settings_fields( 'kts_email_logs_settings' );
			do_settings_sections( 'kts_email_logs_settings' );
			submit_button();
			?>

		</form>
	</div>

	<?php
}
