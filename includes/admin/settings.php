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
		esc_html__( 'Settings', 'peppermoney-content-audit' ),
		esc_html__( 'Settings', 'peppermoney-content-audit' ),
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
				'success_message'        => 'Thank you for reviewing this content. Your submission has been recorded.',
				'button_background_color' => '#d9042b',
				'button_text_color'       => '#ffffff',
				'link_text_color'         => '#0073aa',
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
				'base_url'           => home_url(),
				'support_ticket_url' => '',
			),
		)
	);

	// Register post types settings.
	register_setting(
		'content_audit_settings',
		'content_audit_post_types_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'content_audit_sanitize_post_types_settings',
			'default'           => array(
				'post_types' => array( 'page', 'post' ),
			),
		)
	);

	// Register email template settings.
	register_setting(
		'content_audit_settings',
		'content_audit_email_template_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'content_audit_sanitize_email_template_settings',
			'default'           => array(
				'header_image' => '',
			),
		)
	);

	// Add a section for plugin settings (formerly email settings).
	add_settings_section(
		'content_audit_email_settings_section',
		esc_html__( 'Plugin Settings', 'peppermoney-content-audit' ),
		'content_audit_email_settings_section_callback',
		'content_audit_settings'
	);

	// Add a section for form settings.
	add_settings_section(
		'content_audit_form_settings_section',
		esc_html__( 'Form Settings', 'peppermoney-content-audit' ),
		'content_audit_form_settings_section_callback',
		'content_audit_settings'
	);

	// Add a section for display settings.
	add_settings_section(
		'content_audit_display_settings_section',
		esc_html__( 'Display Settings', 'peppermoney-content-audit' ),
		'content_audit_display_settings_section_callback',
		'content_audit_settings'
	);

	// Add a section for post types settings.
	add_settings_section(
		'content_audit_post_types_settings_section',
		esc_html__( 'Post Types Settings', 'peppermoney-content-audit' ),
		'content_audit_post_types_settings_section_callback',
		'content_audit_settings'
	);

	// Add a section for email template settings.
	add_settings_section(
		'content_audit_email_template_settings_section',
		esc_html__( 'Email Template Settings', 'peppermoney-content-audit' ),
		'content_audit_email_template_settings_section_callback',
		'content_audit_settings'
	);

	// Add fields to the email settings section.
	add_settings_field(
		'content_audit_notification_email',
		esc_html__( 'Notification Email', 'peppermoney-content-audit' ),
		'content_audit_notification_email_callback',
		'content_audit_settings',
		'content_audit_email_settings_section'
	);

	add_settings_field(
		'content_audit_from_email',
		esc_html__( 'From Email', 'peppermoney-content-audit' ),
		'content_audit_from_email_callback',
		'content_audit_settings',
		'content_audit_email_settings_section'
	);

	add_settings_field(
		'content_audit_from_name',
		esc_html__( 'From Name', 'peppermoney-content-audit' ),
		'content_audit_from_name_callback',
		'content_audit_settings',
		'content_audit_email_settings_section'
	);

	add_settings_field(
		'content_audit_base_url',
		esc_html__( 'Base URL', 'peppermoney-content-audit' ),
		'content_audit_base_url_callback',
		'content_audit_settings',
		'content_audit_email_settings_section'
	);

	add_settings_field(
		'content_audit_support_ticket_url',
		esc_html__( 'Support Ticket URL', 'peppermoney-content-audit' ),
		'content_audit_support_ticket_url_callback',
		'content_audit_settings',
		'content_audit_email_settings_section'
	);

	// Add fields to the form settings section.
	add_settings_field(
		'content_audit_success_message',
		esc_html__( 'Success Message', 'peppermoney-content-audit' ),
		'content_audit_success_message_callback',
		'content_audit_settings',
		'content_audit_form_settings_section'
	);

	add_settings_field(
		'content_audit_button_background_color',
		esc_html__( 'Button Background Color', 'peppermoney-content-audit' ),
		'content_audit_button_background_color_callback',
		'content_audit_settings',
		'content_audit_form_settings_section'
	);

	add_settings_field(
		'content_audit_button_text_color',
		esc_html__( 'Button Text Color', 'peppermoney-content-audit' ),
		'content_audit_button_text_color_callback',
		'content_audit_settings',
		'content_audit_form_settings_section'
	);

	add_settings_field(
		'content_audit_link_text_color',
		esc_html__( 'Link Text Color', 'peppermoney-content-audit' ),
		'content_audit_link_text_color_callback',
		'content_audit_settings',
		'content_audit_form_settings_section'
	);

	// Add fields to the display settings section.
	add_settings_field(
		'content_audit_show_admin_columns',
		esc_html__( 'Show Admin Columns', 'peppermoney-content-audit' ),
		'content_audit_show_admin_columns_callback',
		'content_audit_settings',
		'content_audit_display_settings_section'
	);

	// Add fields to the post types settings section.
	add_settings_field(
		'content_audit_post_types',
		esc_html__( 'Post Types', 'peppermoney-content-audit' ),
		'content_audit_post_types_callback',
		'content_audit_settings',
		'content_audit_post_types_settings_section'
	);

	// Add fields to the email template settings section.
	add_settings_field(
		'content_audit_email_header_image',
		esc_html__( 'Email Header Image', 'peppermoney-content-audit' ),
		'content_audit_email_header_image_callback',
		'content_audit_settings',
		'content_audit_email_template_settings_section'
	);
}
add_action( 'admin_init', 'content_audit_register_settings' );

/**
 * Clear ACF cache when post types settings are updated.
 *
 * @param mixed $old_value The old option value.
 * @param mixed $value     The new option value.
 * @return void
 */
function content_audit_clear_acf_cache_on_settings_update( $old_value, $value ) {
	// Clear ACF cache to force field group regeneration when post types change.
	if ( function_exists( 'acf_get_store' ) ) {
		acf_get_store( 'local-groups' )->reset();
	}
}
add_action( 'update_option_content_audit_post_types_settings', 'content_audit_clear_acf_cache_on_settings_update', 10, 2 );

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

	// Sanitize button background color.
	if ( isset( $input['button_background_color'] ) ) {
		$sanitized_input['button_background_color'] = sanitize_hex_color( $input['button_background_color'] );
	} else {
		$sanitized_input['button_background_color'] = '#d9042b';
	}

	// Sanitize button text color.
	if ( isset( $input['button_text_color'] ) ) {
		$sanitized_input['button_text_color'] = sanitize_hex_color( $input['button_text_color'] );
	} else {
		$sanitized_input['button_text_color'] = '#ffffff';
	}

	// Sanitize link text color.
	if ( isset( $input['link_text_color'] ) ) {
		$sanitized_input['link_text_color'] = sanitize_hex_color( $input['link_text_color'] );
	} else {
		$sanitized_input['link_text_color'] = '#0073aa';
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

	// Sanitize base URL.
	if ( isset( $input['base_url'] ) ) {
		$base_url = esc_url_raw( trim( $input['base_url'] ) );
		// Ensure URL has a trailing slash for consistency.
		$base_url                    = untrailingslashit( $base_url );
		$sanitized_input['base_url'] = $base_url;
	} else {
		$sanitized_input['base_url'] = home_url();
	}

	// Sanitize support ticket URL.
	if ( isset( $input['support_ticket_url'] ) ) {
		$support_ticket_url                    = esc_url_raw( trim( $input['support_ticket_url'] ) );
		$sanitized_input['support_ticket_url'] = $support_ticket_url;
	} else {
		$sanitized_input['support_ticket_url'] = '';
	}

	return $sanitized_input;
}

/**
 * Sanitize email template settings.
 *
 * @param array $input The input array to sanitize.
 * @return array The sanitized input.
 */
function content_audit_sanitize_email_template_settings( $input ) {
	$sanitized_input = array();

	// Always set header_image, even if empty, to ensure it can be cleared.
	$sanitized_input['header_image'] = '';

	// Sanitize header image URL if provided.
	if ( isset( $input['header_image'] ) && ! empty( trim( $input['header_image'] ) ) ) {
		$header_image = esc_url_raw( trim( $input['header_image'] ) );
		
		// Validate file extension.
		$allowed_extensions = array( 'gif', 'png', 'jpg', 'jpeg' );
		$file_extension     = strtolower( pathinfo( parse_url( $header_image, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
		
		if ( in_array( $file_extension, $allowed_extensions, true ) ) {
			// Check file size - get attachment ID from URL.
			$attachment_id = attachment_url_to_postid( $header_image );
			if ( $attachment_id ) {
				$file_path = get_attached_file( $attachment_id );
				if ( $file_path && file_exists( $file_path ) ) {
					$file_size = filesize( $file_path );
					$max_size  = 150 * 1024; // 150KB in bytes.
					
					if ( $file_size > $max_size ) {
						// File is too large, add admin notice and don't save.
						add_settings_error(
							'content_audit_email_template_settings',
							'header_image_too_large',
							esc_html__( 'Email header image must be less than 150KB. The uploaded image is too large and was not saved.', 'peppermoney-content-audit' ),
							'error'
						);
						// Keep as empty string.
						$sanitized_input['header_image'] = '';
					} else {
						$sanitized_input['header_image'] = $header_image;
					}
				} else {
					// File doesn't exist, allow URL (might be external).
					$sanitized_input['header_image'] = $header_image;
				}
			} else {
				// Not a WordPress attachment, might be external URL - allow it.
				$sanitized_input['header_image'] = $header_image;
			}
		}
		// If extension doesn't match, header_image remains empty string.
	}

	return $sanitized_input;
}

/**
 * Sanitize post types settings.
 *
 * @param array $input The input array to sanitize.
 * @return array The sanitized input.
 */
function content_audit_sanitize_post_types_settings( $input ) {
	$sanitized_input = array();

	// Get all available post types.
	$available_post_types = get_post_types( array( 'public' => true ), 'names' );

	// Sanitize selected post types.
	if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
		$sanitized_post_types = array();
		foreach ( $input['post_types'] as $post_type ) {
			$post_type = sanitize_key( $post_type );
			// Only allow post types that actually exist.
			if ( in_array( $post_type, $available_post_types, true ) ) {
				$sanitized_post_types[] = $post_type;
			}
		}
		$sanitized_input['post_types'] = $sanitized_post_types;
	} else {
		// Default to page and post if nothing is selected.
		$sanitized_input['post_types'] = array( 'page', 'post' );
	}

	return $sanitized_input;
}

/**
 * Plugin settings section callback (formerly email settings).
 *
 * @return void
 */
function content_audit_email_settings_section_callback() {
	echo '<p>' . esc_html__( 'Configure the plugin settings including email notifications and base URL.', 'peppermoney-content-audit' ) . '</p>';
}

/**
 * Form settings section callback.
 *
 * @return void
 */
function content_audit_form_settings_section_callback() {
	echo '<p>' . esc_html__( 'Configure the form settings for content audit submissions.', 'peppermoney-content-audit' ) . '</p>';
}

/**
 * Display settings section callback.
 *
 * @return void
 */
function content_audit_display_settings_section_callback() {
	echo '<p>' . esc_html__( 'Configure the display settings for content audit.', 'peppermoney-content-audit' ) . '</p>';
}

/**
 * Post types settings section callback.
 *
 * @return void
 */
function content_audit_post_types_settings_section_callback() {
	echo '<p>' . esc_html__( 'Select which post types should display the content audit fields.', 'peppermoney-content-audit' ) . '</p>';
}

/**
 * Email template settings section callback.
 *
 * @return void
 */
function content_audit_email_template_settings_section_callback() {
	echo '<p>' . esc_html__( 'Configure the email template settings for content audit notifications.', 'peppermoney-content-audit' ) . '</p>';
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
	<p class="description"><?php esc_html_e( 'Email address where all content audit notifications will be sent.', 'peppermoney-content-audit' ); ?></p>
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
	<p class="description"><?php esc_html_e( 'Email address that will appear in the From field of all content audit emails.', 'peppermoney-content-audit' ); ?></p>
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
	<p class="description"><?php esc_html_e( 'Name that will appear in the From field of all content audit emails.', 'peppermoney-content-audit' ); ?></p>
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
	<p class="description"><?php esc_html_e( 'Message displayed after a successful form submission.', 'peppermoney-content-audit' ); ?></p>
	<?php
}

/**
 * Button background color callback.
 *
 * @return void
 */
function content_audit_button_background_color_callback() {
	$options                 = get_option( 'content_audit_form_settings' );
	$button_background_color = isset( $options['button_background_color'] ) ? $options['button_background_color'] : '#d9042b';
	?>
	<input type="text" id="content_audit_button_background_color" name="content_audit_form_settings[button_background_color]" value="<?php echo esc_attr( $button_background_color ); ?>" class="content-audit-color-picker" data-default-color="#d9042b" />
	<p class="description"><?php esc_html_e( 'Choose the background color for form buttons.', 'peppermoney-content-audit' ); ?></p>
	<?php
}

/**
 * Button text color callback.
 *
 * @return void
 */
function content_audit_button_text_color_callback() {
	$options           = get_option( 'content_audit_form_settings' );
	$button_text_color = isset( $options['button_text_color'] ) ? $options['button_text_color'] : '#ffffff';
	?>
	<input type="text" id="content_audit_button_text_color" name="content_audit_form_settings[button_text_color]" value="<?php echo esc_attr( $button_text_color ); ?>" class="content-audit-color-picker" data-default-color="#ffffff" />
	<p class="description"><?php esc_html_e( 'Choose the text color for form buttons.', 'peppermoney-content-audit' ); ?></p>
	<?php
}

/**
 * Link text color callback.
 *
 * @return void
 */
function content_audit_link_text_color_callback() {
	$options       = get_option( 'content_audit_form_settings' );
	$link_text_color = isset( $options['link_text_color'] ) ? $options['link_text_color'] : '#0073aa';
	?>
	<input type="text" id="content_audit_link_text_color" name="content_audit_form_settings[link_text_color]" value="<?php echo esc_attr( $link_text_color ); ?>" class="content-audit-color-picker" data-default-color="#0073aa" />
	<p class="description"><?php esc_html_e( 'Choose the text color for links on the form.', 'peppermoney-content-audit' ); ?></p>
	<?php
}

/**
 * Show admin columns field callback.
 *
 * @return void
 */
function content_audit_show_admin_columns_callback() {
	$options            = get_option( 'content_audit_display_settings' );
	$show_admin_columns = isset( $options['show_admin_columns'] ) ? $options['show_admin_columns'] : 'yes';
	?>
	<select id="content_audit_show_admin_columns" name="content_audit_display_settings[show_admin_columns]">
		<option value="yes" <?php selected( $show_admin_columns, 'yes' ); ?>><?php esc_html_e( 'Yes', 'peppermoney-content-audit' ); ?></option>
		<option value="no" <?php selected( $show_admin_columns, 'no' ); ?>><?php esc_html_e( 'No', 'peppermoney-content-audit' ); ?></option>
	</select>
	<p class="description"><?php esc_html_e( 'Show admin columns for content audit.', 'peppermoney-content-audit' ); ?></p>
	<?php
}

/**
 * Base URL field callback.
 *
 * @return void
 */
function content_audit_base_url_callback() {
	$options  = get_option( 'content_audit_display_settings' );
	$base_url = isset( $options['base_url'] ) ? $options['base_url'] : home_url();
	?>
	<input type="url" id="content_audit_base_url" name="content_audit_display_settings[base_url]" value="<?php echo esc_attr( $base_url ); ?>" class="regular-text" />
	<p class="description"><?php esc_html_e( 'Base URL for generating content links in the audit table and email notifications. This should be your production site URL (e.g., https://www.example.com).', 'peppermoney-content-audit' ); ?></p>
	<?php
}

/**
 * Support ticket URL field callback.
 *
 * @return void
 */
function content_audit_support_ticket_url_callback() {
	$options            = get_option( 'content_audit_display_settings' );
	$support_ticket_url = isset( $options['support_ticket_url'] ) ? $options['support_ticket_url'] : '';
	?>
	<input type="url" id="content_audit_support_ticket_url" name="content_audit_display_settings[support_ticket_url]" value="<?php echo esc_attr( $support_ticket_url ); ?>" class="regular-text" />
	<p class="description"><?php esc_html_e( 'URL for the support ticket system (e.g., https://helpdesk.example.com). This will be used in the content review form and email notifications.', 'peppermoney-content-audit' ); ?></p>
	<?php
}

/**
 * Post types field callback.
 *
 * @return void
 */
function content_audit_post_types_callback() {
	$options             = get_option( 'content_audit_post_types_settings' );
	$selected_post_types = isset( $options['post_types'] ) && is_array( $options['post_types'] ) ? $options['post_types'] : array( 'page', 'post' );

	// Get all public post types.
	$post_types = get_post_types( array( 'public' => true ), 'objects' );

	// Sort post types by label.
	usort(
		$post_types,
		function ( $a, $b ) {
			return strcmp( $a->label, $b->label );
		}
	);
	?>
	<fieldset>
		<legend class="screen-reader-text">
			<span><?php esc_html_e( 'Post Types', 'peppermoney-content-audit' ); ?></span>
		</legend>
		<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
			<?php foreach ( $post_types as $post_type ) : ?>
				<label style="display: block; margin-bottom: 8px;">
					<input 
						type="checkbox" 
						name="content_audit_post_types_settings[post_types][]" 
						value="<?php echo esc_attr( $post_type->name ); ?>"
						<?php checked( in_array( $post_type->name, $selected_post_types, true ) ); ?>
					/>
					<?php echo esc_html( $post_type->label ); ?>
					<span style="color: #666; font-size: 11px;">(<?php echo esc_html( $post_type->name ); ?>)</span>
				</label>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php esc_html_e( 'Select which post types should display the content audit fields in the editor.', 'peppermoney-content-audit' ); ?></p>
	</fieldset>
	<?php
}

/**
 * Email header image field callback.
 *
 * @return void
 */
function content_audit_email_header_image_callback() {
	$options      = get_option( 'content_audit_email_template_settings' );
	$header_image = isset( $options['header_image'] ) ? $options['header_image'] : '';
	$image_id     = '';
	$image_url    = '';

	// Get attachment ID from URL if image is set.
	if ( ! empty( $header_image ) ) {
		$image_id = attachment_url_to_postid( $header_image );
		if ( $image_id ) {
			$image_url = wp_get_attachment_image_url( $image_id, 'full' );
		} else {
			$image_url = $header_image;
		}
	}

	// Enqueue media uploader scripts.
	wp_enqueue_media();
	?>
	<div class="content-audit-email-header-image-wrapper">
		<input type="hidden" id="content_audit_email_header_image" name="content_audit_email_template_settings[header_image]" value="<?php echo esc_attr( $header_image ); ?>" />
		<div class="content-audit-email-header-image-preview" style="margin-bottom: 10px;">
			<?php if ( ! empty( $image_url ) ) : ?>
				<img src="<?php echo esc_url( $image_url ); ?>" style="max-width: 300px; height: auto; display: block; margin-bottom: 10px;" />
			<?php endif; ?>
		</div>
		<button type="button" class="button content-audit-upload-header-image" id="content_audit_upload_header_image_btn">
			<?php echo ! empty( $image_url ) ? esc_html__( 'Change Image', 'peppermoney-content-audit' ) : esc_html__( 'Upload Image', 'peppermoney-content-audit' ); ?>
		</button>
		<?php if ( ! empty( $image_url ) ) : ?>
			<button type="button" class="button content-audit-remove-header-image" id="content_audit_remove_header_image_btn" style="margin-left: 10px;">
				<?php esc_html_e( 'Remove Image', 'peppermoney-content-audit' ); ?>
			</button>
		<?php endif; ?>
		<p class="description">
			<?php esc_html_e( 'Upload an image to use as the header in email notifications. Allowed formats: GIF, PNG, JPEG. Maximum file size: 150KB. If no image is uploaded, the site title and tagline will display instead..', 'peppermoney-content-audit' ); ?>
		</p>
	</div>

	<script>
	(function($) {
		'use strict';

		var fileFrame;
		var $imageInput = $('#content_audit_email_header_image');
		var $imagePreview = $('.content-audit-email-header-image-preview');
		var $uploadBtn = $('#content_audit_upload_header_image_btn');
		var $removeBtn = $('#content_audit_remove_header_image_btn');

		// Handle image upload.
		$uploadBtn.on('click', function(e) {
			e.preventDefault();

			// If the media frame already exists, reopen it.
			if (fileFrame) {
				fileFrame.open();
				return;
			}

			// Create the media frame.
			fileFrame = wp.media({
				title: '<?php echo esc_js( __( 'Select Email Header Image', 'peppermoney-content-audit' ) ); ?>',
				button: {
					text: '<?php echo esc_js( __( 'Use this image', 'peppermoney-content-audit' ) ); ?>'
				},
				library: {
					type: ['image']
				},
				multiple: false
			});

			// When an image is selected, run a callback.
			fileFrame.on('select', function() {
				var attachment = fileFrame.state().get('selection').first().toJSON();
				var imageUrl = attachment.url;
				var fileExtension = imageUrl.split('.').pop().toLowerCase();
				var maxSize = 150 * 1024; // 150KB in bytes.

				// Validate file extension.
				if (['gif', 'png', 'jpg', 'jpeg'].indexOf(fileExtension) === -1) {
					alert('<?php echo esc_js( __( 'Invalid file format. Please select a GIF, PNG, or JPEG image.', 'peppermoney-content-audit' ) ); ?>');
					return;
				}

				// Validate file size - check multiple possible properties.
				var fileSize = attachment.filesizeInBytes || attachment.filesize || (attachment.sizes && attachment.sizes.full && attachment.sizes.full.filesize) || 0;
				
				if (fileSize > 0 && fileSize > maxSize) {
					var fileSizeKB = Math.round(fileSize / 1024);
					alert('<?php echo esc_js( __( 'The selected image is too large. Maximum file size is 150KB. The selected file is ', 'peppermoney-content-audit' ) ); ?>' + fileSizeKB + 'KB. <?php echo esc_js( __( 'Please select a smaller image.', 'peppermoney-content-audit' ) ); ?>');
					return;
				}

				// If file size is not available in attachment object, allow it through
				// Server-side validation will catch it if it's too large.
				if (fileSize === 0) {
					// File size not available - will be validated server-side.
					console.log('File size not available in attachment object, will be validated on save.');
				}

				// Set the image URL.
				$imageInput.val(imageUrl);

				// Update preview.
				if ($imagePreview.find('img').length) {
					$imagePreview.find('img').attr('src', imageUrl);
				} else {
					$imagePreview.html('<img src="' + imageUrl + '" style="max-width: 300px; height: auto; display: block; margin-bottom: 10px;" />');
				}

				// Update button text.
				$uploadBtn.text('<?php echo esc_js( __( 'Change Image', 'peppermoney-content-audit' ) ); ?>');

				// Show remove button if not already visible.
				if ($removeBtn.length === 0 || !$removeBtn.is(':visible')) {
					$uploadBtn.after('<button type="button" class="button content-audit-remove-header-image" id="content_audit_remove_header_image_btn" style="margin-left: 10px;"><?php echo esc_js( __( 'Remove Image', 'peppermoney-content-audit' ) ); ?></button>');
					$removeBtn = $('#content_audit_remove_header_image_btn');
					$removeBtn.on('click', handleRemoveImage);
				}
			});

			// Open the media frame.
			fileFrame.open();
		});

		// Handle image removal.
		function handleRemoveImage(e) {
			e.preventDefault();
			$imageInput.val('');
			$imagePreview.html('');
			$uploadBtn.text('<?php echo esc_js( __( 'Upload Image', 'peppermoney-content-audit' ) ); ?>');
			$removeBtn.remove();
		}

		if ($removeBtn.length) {
			$removeBtn.on('click', handleRemoveImage);
		}
	})(jQuery);
	</script>
	<?php
}

/**
 * Render the settings page.
 *
 * @return void
 */
function content_audit_render_settings_page() {
	// Enqueue color picker scripts.
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Get active tab from URL hash, default to plugin-settings.
	$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'plugin-settings'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	// If hash exists in URL, use that instead.
	if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$url_parts = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		if ( isset( $url_parts['fragment'] ) && ! empty( $url_parts['fragment'] ) ) {
			$active_tab = str_replace( '#', '', $url_parts['fragment'] );
		}
	}

	// Validate tab.
	$valid_tabs = array( 'plugin-settings', 'form-settings', 'email-settings' );
	if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
		$active_tab = 'plugin-settings';
	}

	// Add error/update messages.
	// WordPress Settings API handles nonce verification automatically when using settings_fields().
	if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// Add settings saved message with the class of "updated".
		add_settings_error(
			'content_audit_messages',
			'content_audit_message',
			esc_html__( 'Settings Saved', 'peppermoney-content-audit' ),
			'updated'
		);
	}

	// Show error/update messages.
	settings_errors( 'content_audit_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		
		<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Secondary menu', 'peppermoney-content-audit' ); ?>">
			<a href="#plugin-settings" class="nav-tab <?php echo 'plugin-settings' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="plugin-settings">
				<?php esc_html_e( 'Plugin Settings', 'peppermoney-content-audit' ); ?>
			</a>
			<a href="#form-settings" class="nav-tab <?php echo 'form-settings' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="form-settings">
				<?php esc_html_e( 'Form Settings', 'peppermoney-content-audit' ); ?>
			</a>
			<a href="#email-settings" class="nav-tab <?php echo 'email-settings' === $active_tab ? 'nav-tab-active' : ''; ?>" data-tab="email-settings">
				<?php esc_html_e( 'Email Settings', 'peppermoney-content-audit' ); ?>
			</a>
		</nav>

		<form action="options.php" method="post" id="content-audit-settings-form">
			<?php
			// Output security fields for the registered setting.
			settings_fields( 'content_audit_settings' );
			?>

			<div id="plugin-settings-tab" class="content-audit-tab-content" style="<?php echo 'plugin-settings' === $active_tab ? '' : 'display: none;'; ?>">
				<?php
				require_once plugin_dir_path( __FILE__ ) . 'settings/plugin-settings.php';
				?>
			</div>

			<div id="form-settings-tab" class="content-audit-tab-content" style="<?php echo 'form-settings' === $active_tab ? '' : 'display: none;'; ?>">
				<?php
				require_once plugin_dir_path( __FILE__ ) . 'settings/form-settings.php';
				?>
			</div>

			<div id="email-settings-tab" class="content-audit-tab-content" style="<?php echo 'email-settings' === $active_tab ? '' : 'display: none;'; ?>">
				<?php
				require_once plugin_dir_path( __FILE__ ) . 'settings/email-settings.php';
				?>
			</div>

			<?php
			// Output save settings button.
			submit_button( esc_html__( 'Save Settings', 'peppermoney-content-audit' ) );
			?>
		</form>
	</div>

	<script>
	(function() {
		'use strict';

		var tabs = document.querySelectorAll('.nav-tab');
		var tabContents = document.querySelectorAll('.content-audit-tab-content');
		var form = document.getElementById('content-audit-settings-form');
		var currentTab = '<?php echo esc_js( $active_tab ); ?>';

		// Function to switch tabs.
		function switchTab(tabName) {
			// Update active tab.
			tabs.forEach(function(tab) {
				if (tab.getAttribute('data-tab') === tabName) {
					tab.classList.add('nav-tab-active');
				} else {
					tab.classList.remove('nav-tab-active');
				}
			});

			// Show/hide tab content.
			tabContents.forEach(function(content) {
				if (content.id === tabName + '-tab') {
					content.style.display = '';
				} else {
					content.style.display = 'none';
				}
			});

			// Update URL hash without triggering scroll.
			if (history.pushState) {
				history.pushState(null, null, '#' + tabName);
			} else {
				window.location.hash = '#' + tabName;
			}

			currentTab = tabName;
		}

		// Handle tab clicks.
		tabs.forEach(function(tab) {
			tab.addEventListener('click', function(e) {
				e.preventDefault();
				var tabName = this.getAttribute('data-tab');
				switchTab(tabName);
			});
		});

		// Handle form submission - store current tab in sessionStorage.
		if (form) {
			form.addEventListener('submit', function(e) {
				// Store current tab in sessionStorage before form submission.
				if (currentTab && typeof(Storage) !== 'undefined') {
					sessionStorage.setItem('content_audit_active_tab', currentTab);
				}
			});
		}

		// On page load, check for stored tab or hash and activate corresponding tab.
		function activateTabFromHash() {
			var tabToActivate = null;
			
			// First, check sessionStorage for stored tab (from form submission).
			if (typeof(Storage) !== 'undefined') {
				var storedTab = sessionStorage.getItem('content_audit_active_tab');
				if (storedTab && ['plugin-settings', 'form-settings', 'email-settings'].indexOf(storedTab) !== -1) {
					tabToActivate = storedTab;
					// Clear sessionStorage after use.
					sessionStorage.removeItem('content_audit_active_tab');
				}
			}
			
			// If no stored tab, check URL hash.
			if (!tabToActivate) {
				var hash = window.location.hash.replace('#', '');
				if (hash && ['plugin-settings', 'form-settings', 'email-settings'].indexOf(hash) !== -1) {
					tabToActivate = hash;
				}
			}
			
			// Activate the tab if found.
			if (tabToActivate) {
				switchTab(tabToActivate);
			}
		}

		// Run on page load.
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', activateTabFromHash);
		} else {
			activateTabFromHash();
		}

		// Handle hash changes (back/forward browser buttons).
		window.addEventListener('hashchange', function() {
			activateTabFromHash();
		});

		// Initialize color pickers.
		jQuery(document).ready(function($) {
			$('.content-audit-color-picker').wpColorPicker({
				change: function(event, ui) {
					// Optional: Add any change handlers here.
				}
			});
		});
	})();
	</script>
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
		'success_message'        => 'Thank you for reviewing this content. Your submission has been recorded.',
		'button_background_color' => '#d9042b',
		'button_text_color'       => '#ffffff',
		'link_text_color'         => '#0073aa',
	);

	$settings = get_option( 'content_audit_form_settings', $default_settings );

	// Ensure all keys exist.
	if ( ! isset( $settings['button_background_color'] ) ) {
		$settings['button_background_color'] = '#d9042b';
	}
	if ( ! isset( $settings['button_text_color'] ) ) {
		$settings['button_text_color'] = '#ffffff';
	}
	if ( ! isset( $settings['link_text_color'] ) ) {
		$settings['link_text_color'] = '#0073aa';
	}

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
		'base_url'           => home_url(),
		'support_ticket_url' => '',
	);

	$settings = get_option( 'content_audit_display_settings', $default_settings );

	// Ensure base_url is set.
	if ( ! isset( $settings['base_url'] ) || empty( $settings['base_url'] ) ) {
		$settings['base_url'] = home_url();
	}

	// Ensure support_ticket_url is set.
	if ( ! isset( $settings['support_ticket_url'] ) ) {
		$settings['support_ticket_url'] = '';
	}

	return $settings;
}

/**
 * Get post types settings.
 *
 * @return array Post types settings.
 */
function content_audit_get_post_types_settings() {
	$default_settings = array(
		'post_types' => array( 'page', 'post' ),
	);

	$settings = get_option( 'content_audit_post_types_settings', $default_settings );

	return $settings;
}

/**
 * Get email template settings.
 *
 * @return array Email template settings.
 */
function content_audit_get_email_template_settings() {
	$default_settings = array(
		'header_image' => '',
	);

	$settings = get_option( 'content_audit_email_template_settings', $default_settings );

	// Ensure header_image key exists and is a string (even if empty).
	if ( ! isset( $settings['header_image'] ) ) {
		$settings['header_image'] = '';
	}

	return $settings;
}
