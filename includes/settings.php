<?php
/**
 * Content Audit Settings Page.
 *
 * @package ContentAudit
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register the settings page in the admin menu.
 *
 * @return void
 */
function content_audit_register_settings_page() {
	add_submenu_page(
		'content-audit',
		esc_html__( 'Settings', 'content-audit' ),
		esc_html__( 'Settings', 'content-audit' ),
		'manage_options',
		'content-audit-settings',
		'content_audit_render_settings_page'
	);
}
add_action( 'admin_menu', 'content_audit_register_settings_page' );

/**
 * Register plugin settings.
 *
 * @return void
 */
function content_audit_register_settings() {
	// Register a new setting for this plugin.
	register_setting(
		'content_audit_settings',
		'content_audit_email_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'content_audit_sanitize_email_settings',
			'default'           => array(
				'notification_email' => get_option( 'admin_email' ),
				'from_email'         => 'ux@pepper.money',
				'from_name'          => 'Pepper Money UX Team',
			),
		)
	);

	// Register form settings.
	register_setting(
		'content_audit_settings',
		'content_audit_form_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'content_audit_sanitize_form_settings',
			'default'           => array(
				'success_message' => 'Thank you for reviewing this content. Your submission has been recorded.',
			),
		)
	);

	// Register display settings.
	register_setting(
		'content_audit_settings',
		'content_audit_display_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'content_audit_sanitize_display_settings',
			'default'           => array(
				'show_admin_columns' => 'yes',
			),
		)
	);

	// Add a section for email settings.
	add_settings_section(
		'content_audit_email_settings_section',
		esc_html__( 'Email Settings', 'content-audit' ),
		'content_audit_email_settings_section_callback',
		'content_audit_settings'
	);

	// Add a section for form settings.
	add_settings_section(
		'content_audit_form_settings_section',
		esc_html__( 'Form Settings', 'content-audit' ),
		'content_audit_form_settings_section_callback',
		'content_audit_settings'
	);

	// Add a section for display settings.
	add_settings_section(
		'content_audit_display_settings_section',
		esc_html__( 'Display Settings', 'content-audit' ),
		'content_audit_display_settings_section_callback',
		'content_audit_settings'
	);

	// Add fields to the email settings section.
	add_settings_field(
		'content_audit_notification_email',
		esc_html__( 'Notification Email', 'content-audit' ),
		'content_audit_notification_email_callback',
		'content_audit_settings',
		'content_audit_email_settings_section'
	);

	add_settings_field(
		'content_audit_from_email',
		esc_html__( 'From Email', 'content-audit' ),
		'content_audit_from_email_callback',
		'content_audit_settings',
		'content_audit_email_settings_section'
	);

	add_settings_field(
		'content_audit_from_name',
		esc_html__( 'From Name', 'content-audit' ),
		'content_audit_from_name_callback',
		'content_audit_settings',
		'content_audit_email_settings_section'
	);

	// Add fields to the form settings section.
	add_settings_field(
		'content_audit_success_message',
		esc_html__( 'Success Message', 'content-audit' ),
		'content_audit_success_message_callback',
		'content_audit_settings',
		'content_audit_form_settings_section'
	);

	// Add fields to the display settings section.
	add_settings_field(
		'content_audit_show_admin_columns',
		esc_html__( 'Show Admin Columns', 'content-audit' ),
		'content_audit_show_admin_columns_callback',
		'content_audit_settings',
		'content_audit_display_settings_section'
	);
}
add_action( 'admin_init', 'content_audit_register_settings' );

/**
 * Sanitize email settings.
 *
 * @param array $input The input array to sanitize.
 * @return array The sanitized input.
 */
function content_audit_sanitize_email_settings( $input ) {
	$sanitized_input = array();

	// Sanitize notification email.
	if ( isset( $input['notification_email'] ) ) {
		$sanitized_input['notification_email'] = sanitize_email( $input['notification_email'] );
	} else {
		$sanitized_input['notification_email'] = get_option( 'admin_email' );
	}

	// Sanitize from email.
	if ( isset( $input['from_email'] ) ) {
		$sanitized_input['from_email'] = sanitize_email( $input['from_email'] );
	} else {
		$sanitized_input['from_email'] = 'ux@pepper.money';
	}

	// Sanitize from name.
	if ( isset( $input['from_name'] ) ) {
		$sanitized_input['from_name'] = sanitize_text_field( $input['from_name'] );
	} else {
		$sanitized_input['from_name'] = 'Pepper Money UX Team';
	}

	return $sanitized_input;
}

/**
 * Sanitize form settings.
 *
 * @param array $input The input array to sanitize.
 * @return array The sanitized input.
 */
function content_audit_sanitize_form_settings( $input ) {
	$sanitized_input = array();

	// Sanitize success message.
	if ( isset( $input['success_message'] ) ) {
		$sanitized_input['success_message'] = sanitize_text_field( $input['success_message'] );
	} else {
		$sanitized_input['success_message'] = 'Thank you for reviewing this content. Your submission has been recorded.';
	}

	return $sanitized_input;
}

/**
 * Sanitize display settings.
 *
 * @param array $input The input array to sanitize.
 * @return array The sanitized input.
 */
function content_audit_sanitize_display_settings( $input ) {
	$sanitized_input = array();

	// Sanitize show admin columns.
	if ( isset( $input['show_admin_columns'] ) ) {
		$sanitized_input['show_admin_columns'] = sanitize_text_field( $input['show_admin_columns'] );
	} else {
		$sanitized_input['show_admin_columns'] = 'yes';
	}

	return $sanitized_input;
}

/**
 * Email settings section callback.
 *
 * @return void
 */
function content_audit_email_settings_section_callback() {
	echo '<p>' . esc_html__( 'Configure the email settings for content audit notifications.', 'content-audit' ) . '</p>';
}

/**
 * Form settings section callback.
 *
 * @return void
 */
function content_audit_form_settings_section_callback() {
	echo '<p>' . esc_html__( 'Configure the form settings for content audit submissions.', 'content-audit' ) . '</p>';
}

/**
 * Display settings section callback.
 *
 * @return void
 */
function content_audit_display_settings_section_callback() {
	echo '<p>' . esc_html__( 'Configure the display settings for content audit.', 'content-audit' ) . '</p>';
}

/**
 * Notification email field callback.
 *
 * @return void
 */
function content_audit_notification_email_callback() {
	$options = get_option( 'content_audit_email_settings' );
	$email   = isset( $options['notification_email'] ) ? $options['notification_email'] : get_option( 'admin_email' );
	?>
	<input type="email" id="content_audit_notification_email" name="content_audit_email_settings[notification_email]" value="<?php echo esc_attr( $email ); ?>" class="regular-text" />
	<p class="description"><?php esc_html_e( 'Email address where all content audit notifications will be sent.', 'content-audit' ); ?></p>
	<?php
}

/**
 * From email field callback.
 *
 * @return void
 */
function content_audit_from_email_callback() {
	$options    = get_option( 'content_audit_email_settings' );
	$from_email = isset( $options['from_email'] ) ? $options['from_email'] : 'ux@pepper.money';
	?>
	<input type="email" id="content_audit_from_email" name="content_audit_email_settings[from_email]" value="<?php echo esc_attr( $from_email ); ?>" class="regular-text" />
	<p class="description"><?php esc_html_e( 'Email address that will appear in the From field of all content audit emails.', 'content-audit' ); ?></p>
	<?php
}

/**
 * From name field callback.
 *
 * @return void
 */
function content_audit_from_name_callback() {
	$options   = get_option( 'content_audit_email_settings' );
	$from_name = isset( $options['from_name'] ) ? $options['from_name'] : 'Pepper Money UX Team';
	?>
	<input type="text" id="content_audit_from_name" name="content_audit_email_settings[from_name]" value="<?php echo esc_attr( $from_name ); ?>" class="regular-text" />
	<p class="description"><?php esc_html_e( 'Name that will appear in the From field of all content audit emails.', 'content-audit' ); ?></p>
	<?php
}

/**
 * Success message field callback.
 *
 * @return void
 */
function content_audit_success_message_callback() {
	$options         = get_option( 'content_audit_form_settings' );
	$success_message = isset( $options['success_message'] ) ? $options['success_message'] : 'Thank you for reviewing this content. Your submission has been recorded.';
	?>
	<textarea id="content_audit_success_message" name="content_audit_form_settings[success_message]" rows="3" class="large-text"><?php echo esc_textarea( $success_message ); ?></textarea>
	<p class="description"><?php esc_html_e( 'Message displayed after a successful form submission.', 'content-audit' ); ?></p>
	<?php
}

/**
 * Show admin columns field callback.
 *
 * @return void
 */
function content_audit_show_admin_columns_callback() {
	$options = get_option( 'content_audit_display_settings' );
	$show_admin_columns = isset( $options['show_admin_columns'] ) ? $options['show_admin_columns'] : 'yes';
	?>
	<select id="content_audit_show_admin_columns" name="content_audit_display_settings[show_admin_columns]">
		<option value="yes" <?php selected( $show_admin_columns, 'yes' ); ?>><?php esc_html_e( 'Yes', 'content-audit' ); ?></option>
		<option value="no" <?php selected( $show_admin_columns, 'no' ); ?>><?php esc_html_e( 'No', 'content-audit' ); ?></option>
	</select>
	<p class="description"><?php esc_html_e( 'Show admin columns for content audit.', 'content-audit' ); ?></p>
	<?php
}

/**
 * Render the settings page.
 *
 * @return void
 */
function content_audit_render_settings_page() {
	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Add error/update messages.
	if ( isset( $_GET['settings-updated'] ) ) {
		// Add settings saved message with the class of "updated".
		add_settings_error(
			'content_audit_messages',
			'content_audit_message',
			esc_html__( 'Settings Saved', 'content-audit' ),
			'updated'
		);
	}

	// Show error/update messages.
	settings_errors( 'content_audit_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			// Output security fields for the registered setting.
			settings_fields( 'content_audit_settings' );
			// Output setting sections and their fields.
			do_settings_sections( 'content_audit_settings' );
			// Output save settings button.
			submit_button( esc_html__( 'Save Settings', 'content-audit' ) );
			?>
		</form>
	</div>
	<?php
}

/**
 * Get email settings.
 *
 * @return array Email settings.
 */
function content_audit_get_email_settings() {
	$default_settings = array(
		'notification_email' => get_option( 'admin_email' ),
		'from_email'         => 'ux@pepper.money',
		'from_name'          => 'Pepper Money UX Team',
	);

	$settings = get_option( 'content_audit_email_settings', $default_settings );

	return $settings;
}

/**
 * Get form settings.
 *
 * @return array Form settings.
 */
function content_audit_get_form_settings() {
	$default_settings = array(
		'success_message' => 'Thank you for reviewing this content. Your submission has been recorded.',
	);

	$settings = get_option( 'content_audit_form_settings', $default_settings );

	return $settings;
}

/**
 * Get display settings.
 *
 * @return array Display settings.
 */
function content_audit_get_display_settings() {
	$default_settings = array(
		'show_admin_columns' => 'yes',
	);

	$settings = get_option( 'content_audit_display_settings', $default_settings );

	return $settings;
}
