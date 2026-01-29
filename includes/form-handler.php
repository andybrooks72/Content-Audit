<?php
/**
 * Content Audit Form Handler.
 *
 * @package ContentAudit
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Include settings functions if not already included.
if ( ! function_exists( 'content_audit_get_display_settings' ) ) {
	require_once __DIR__ . '/admin/settings.php';
}

/**
 * Insert submission into the database.
 *
 * @param array $data Submission data.
 * @return int|false The number of rows inserted, or false on error.
 */
function content_audit_insert_submission( $data ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'content_audit_submissions';

	// Check if the table has the new column structure.
	$cache_key      = 'content_audit_has_content_id_column';
	$has_content_id = wp_cache_get( $cache_key );

	if ( false === $has_content_id ) {
		$has_content_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SHOW COLUMNS FROM `{$wpdb->prefix}content_audit_submissions` LIKE %s",
				'content_id'
			)
		);
		wp_cache_set( $cache_key, $has_content_id );
	}

	if ( $has_content_id ) {
		// New column structure exists, use content_id and content_title.
		return $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table_name,
			array(
				'content_id'             => $data['content_id'],
				'content_title'          => $data['content_title'],
				'content_type'           => $data['content_type'],
				'stakeholder_name'       => $data['stakeholder_name'],
				'stakeholder_email'      => $data['stakeholder_email'],
				'stakeholder_department' => $data['stakeholder_department'],
				'submission_date'        => $data['submission_date'],
				'needs_changes'          => $data['needs_changes'],
				'support_ticket_url'     => $data['support_ticket_url'],
				'next_review_date'       => $data['next_review_date'],
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
			)
		);
	} else {
		// Old column structure, use page_id and page_title.
		return $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table_name,
			array(
				'page_id'                => $data['content_id'],
				'page_title'             => $data['content_title'],
				'content_type'           => $data['content_type'],
				'stakeholder_name'       => $data['stakeholder_name'],
				'stakeholder_email'      => $data['stakeholder_email'],
				'stakeholder_department' => $data['stakeholder_department'],
				'submission_date'        => $data['submission_date'],
				'needs_changes'          => $data['needs_changes'],
				'support_ticket_url'     => $data['support_ticket_url'],
				'next_review_date'       => $data['next_review_date'],
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
			)
		);
	}
}

/**
 * Display the content audit form shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string Form HTML.
 */
function content_audit_form_shortcode( $atts ) {
	// Extract shortcode attributes.
	$atts = shortcode_atts(
		array(
			'content_id' => 0,
			'token'      => '',
		),
		$atts,
		'content_audit_form'
	);

	// Check if content ID and token are provided as URL parameters if not in shortcode.
	if ( empty( $atts['content_id'] ) && isset( $_GET['content_page_id'] ) ) {
		$atts['content_id'] = absint( $_GET['content_page_id'] );
	}

	if ( empty( $atts['token'] ) && isset( $_GET['token'] ) ) {
		$atts['token'] = sanitize_text_field( wp_unslash( $_GET['token'] ) );
	}

	// Check if content ID and token are provided.
	if ( empty( $atts['content_id'] ) || empty( $atts['token'] ) ) {
		// If this is the form page created by the plugin, show a helpful message.
		$form_page_id = get_option( 'content_audit_form_page_id' );
		if ( $form_page_id && get_the_ID() === (int) $form_page_id ) {
			return '<div class="content-audit-form-message">
				<h2>' . esc_html__( 'Content Review Form', 'ab-content-audit' ) . '</h2>
				<p>' . esc_html__( 'This form is designed to be accessed from the link provided in content review emails.', 'ab-content-audit' ) . '</p>
				<p>' . esc_html__( 'If you received an email about reviewing content, please use the link in that email to access this form with the correct parameters.', 'ab-content-audit' ) . '</p>
				<p>' . esc_html__( 'If you are a site administrator testing this form, you need to provide content_page_id and token parameters.', 'ab-content-audit' ) . '</p>
			</div>';
		} else {
			// For other pages using the shortcode directly.
			return '<div class="content-audit-form-error">
				<p>' . esc_html__( 'Invalid form parameters. This form must be accessed from the link provided in the content review email.', 'ab-content-audit' ) . '</p>
			</div>';
		}
	}

	// Verify token.
	$content_id     = absint( $atts['content_id'] );
	$token          = sanitize_text_field( $atts['token'] );
	$expected_token = wp_hash( 'content_audit_' . $content_id . get_the_title( $content_id ) );

	if ( $token !== $expected_token ) {
		return '<p>' . esc_html__( 'Invalid or expired form link.', 'ab-content-audit' ) . '</p>';
	}

	// Get content data.
	$content = get_post( $content_id );
	if ( ! $content ) {
		return '<p>' . esc_html__( 'Content not found.', 'ab-content-audit' ) . '</p>';
	}

	// Check if content type is in the allowed post types from settings.
	if ( ! function_exists( 'content_audit_get_post_types_settings' ) ) {
		require_once __DIR__ . '/admin/settings.php';
	}
	$post_types_settings = content_audit_get_post_types_settings();
	$allowed_post_types  = isset( $post_types_settings['post_types'] ) && is_array( $post_types_settings['post_types'] )
		? $post_types_settings['post_types']
		: array( 'page', 'post' );

	if ( ! in_array( $content->post_type, $allowed_post_types, true ) ) {
		return '<p>' . esc_html__( 'Content not found.', 'ab-content-audit' ) . '</p>';
	}

	// Get content type.
	$content_type = $content->post_type;

	/**
	 * Get the display label for a post type.
	 *
	 * @param string $post_type The post type slug.
	 * @return string The display label for the post type (not escaped).
	 */
	function content_audit_get_post_type_label( $post_type ) {
		if ( 'post' === $post_type ) {
			return __( 'Post', 'ab-content-audit' );
		} elseif ( 'page' === $post_type ) {
			return __( 'Page', 'ab-content-audit' );
		} else {
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object && isset( $post_type_object->labels->singular_name ) ) {
				return $post_type_object->labels->singular_name;
			}
			return __( 'Content', 'ab-content-audit' );
		}
	}

	// Get the content type label for display.
	$content_type_label = content_audit_get_post_type_label( $content_type );

	// Get ACF fields.
	$content_audit = get_fields( $content_id );
	if ( empty( $content_audit ) ) {
		return '<p>' . esc_html__( 'Content audit data not found.', 'ab-content-audit' ) . '</p>';
	}

	$stakeholder_name       = $content_audit['stakeholder_name'];
	$stakeholder_email      = $content_audit['stakeholder_email'];
	$stakeholder_department = $content_audit['stakeholder_department'];
	$last_review_date       = $content_audit['last_review_date'];
	$next_review_date       = $content_audit['next_review_date'];

	// Process form submission.
	$form_submitted  = false;
	$form_errors     = array();
	$success_message = '';

	if ( isset( $_POST['content_audit_submit'] ) && isset( $_POST['content_audit_nonce'] ) ) {
		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['content_audit_nonce'] ) ), 'content_audit_form_' . $content_id ) ) {
			$form_errors[] = esc_html__( 'Security verification failed. Please try again.', 'ab-content-audit' );
		} else {
			// Process form data.
			$needs_changes      = isset( $_POST['needs_changes'] ) && '1' === $_POST['needs_changes'] ? 1 : 0;
			$support_ticket_url = '';

			// Process and sanitize the support ticket URL.
			if ( isset( $_POST['support_ticket_url'] ) && ! empty( $_POST['support_ticket_url'] ) ) {
				$raw_url = sanitize_text_field( wp_unslash( $_POST['support_ticket_url'] ) );

				// Ensure URL starts with https://.
				if ( strpos( $raw_url, 'https://' ) !== 0 && strpos( $raw_url, 'http://' ) !== 0 ) {
					$raw_url = 'https://' . trim( $raw_url );
				}

				$support_ticket_url = esc_url_raw( $raw_url );

				// Verify that we still have a valid URL after sanitization.
				if ( empty( $support_ticket_url ) || 'https://' === $support_ticket_url ) {
					$support_ticket_url = '';
				}
			}

			// Get the current next review date or calculate a new one if not set.
			$current_next_review_date = ! empty( $next_review_date ) ? $next_review_date : gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );

			// Save to database using the helper function.
			$result = content_audit_insert_submission(
				array(
					'content_id'             => $content_id,
					'content_title'          => get_the_title( $content_id ),
					'content_type'           => $content_type,
					'stakeholder_name'       => $stakeholder_name,
					'stakeholder_email'      => $stakeholder_email,
					'stakeholder_department' => $stakeholder_department,
					'submission_date'        => gmdate( 'Y-m-d H:i:s' ),
					'needs_changes'          => $needs_changes,
					'support_ticket_url'     => $support_ticket_url,
					'next_review_date'       => $current_next_review_date,
				)
			);

			if ( false !== $result ) {
				$form_submitted = true;

				// Get the success message from settings.
				$form_settings   = content_audit_get_form_settings();
				$success_message = $form_settings['success_message'];

				// Update ACF fields.
				$current_date = gmdate( 'Y-m-d' );
				update_field( 'last_review_date', $current_date, $content_id );

				// If no changes are needed, set next review date to 1 year from now.
				if ( ! $needs_changes ) {
					// Calculate date 1 year from now.
					$next_review_date = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year', strtotime( $current_date ) ) );

					// Update the next review date field.
					update_field( 'next_review_date', $next_review_date, $content_id );
				}

				// Send email notification to admin.
				$admin_email = get_option( 'admin_email' );
				$subject     = sprintf(
					/* translators: %s: page title */
					esc_html__( 'Content Review Submission for %s', 'ab-content-audit' ),
					get_the_title( $content_id )
				);

				// Get the relative path from the permalink.
				$permalink     = get_permalink( $content_id );
				$site_url      = site_url();
				$relative_path = str_replace( $site_url, '', $permalink );

				// Get base URL from settings.
				$display_settings = content_audit_get_display_settings();
				$base_url         = isset( $display_settings['base_url'] ) ? $display_settings['base_url'] : home_url();
				// Create the live site URL.
				$live_site_url = untrailingslashit( $base_url ) . $relative_path;

				// Get submissions URL.
				$submissions_url = admin_url( 'admin.php?page=content-audit-submissions' );

				// Get email template settings for header image.
				$email_template_settings = content_audit_get_email_template_settings();
				$header_image             = isset( $email_template_settings['header_image'] ) && ! empty( $email_template_settings['header_image'] ) 
					? $email_template_settings['header_image'] 
					: '';

				// Get email settings for from name.
				$email_settings = content_audit_get_email_settings();
				$from_name      = isset( $email_settings['from_name'] ) ? $email_settings['from_name'] : 'Pepper Money UX Team';

				// Get form settings for colors.
				$form_settings     = content_audit_get_form_settings();
				$button_bg_color   = isset( $form_settings['button_background_color'] ) ? $form_settings['button_background_color'] : '#d9042b';
				$button_text_color = isset( $form_settings['button_text_color'] ) ? $form_settings['button_text_color'] : '#ffffff';
				$link_text_color   = isset( $form_settings['link_text_color'] ) ? $form_settings['link_text_color'] : '#0073aa';

				// Prepare template variables.
				$template_args = array(
					'content_id'            => $content_id,
					'content_title'        => get_the_title( $content_id ),
					'live_site_url'         => $live_site_url,
					'stakeholder_name'     => $stakeholder_name,
					'stakeholder_department' => $stakeholder_department,
					'needs_changes'        => $needs_changes,
					'support_ticket_url'   => $support_ticket_url,
					'submissions_url'      => $submissions_url,
					'header_image'         => $header_image,
					'from_name'            => $from_name,
					'button_bg_color'      => $button_bg_color,
					'button_text_color'    => $button_text_color,
					'link_text_color'      => $link_text_color,
				);

				// Load email template.
				$message = content_audit_load_email_template( 'submission-notification', $template_args );

				// Get email settings.
				$email_settings     = content_audit_get_email_settings();
				$notification_email = $email_settings['notification_email'];
				$from_email         = $email_settings['from_email'];

				// Set up email headers for HTML.
				$headers = array(
					'From: ' . $from_name . ' <' . $from_email . '>',
					'Content-Type: text/html; charset=UTF-8',
					'Reply-To: ' . $notification_email,
				);

				wp_mail( $notification_email, $subject, $message, $headers );
			} else {
				$form_errors[] = esc_html__( 'Failed to save your submission. Please try again.', 'ab-content-audit' );
			}
		}
	}

	// Display the form.
	ob_start();

	// Get form settings for colors.
	$form_settings = content_audit_get_form_settings();
	$button_bg_color = isset( $form_settings['button_background_color'] ) ? $form_settings['button_background_color'] : '#d9042b';
	$button_text_color = isset( $form_settings['button_text_color'] ) ? $form_settings['button_text_color'] : '#ffffff';
	$link_text_color = isset( $form_settings['link_text_color'] ) ? $form_settings['link_text_color'] : '#0073aa';

	if ( $form_submitted ) {
		echo '<div class="content-audit-success">';
		echo '<h2>' . esc_html__( 'Thank You!', 'ab-content-audit' ) . '</h2>';
		echo '<p>' . esc_html( $success_message ) . '</p>';
		echo '</div>';
	} else {
		if ( ! empty( $form_errors ) ) {
			echo '<div class="content-audit-errors">';
			foreach ( $form_errors as $error ) {
				echo '<p>' . esc_html( $error ) . '</p>';
			}
			echo '</div>';
		}

		?>
		<style>
			.content-audit-form-wrapper .content-audit-grid a,
			.content-audit-form-wrapper a {
				color: <?php echo esc_attr( $link_text_color ); ?> !important;
			}
			.content-audit-form-wrapper #support_ticket_field .elementor-button,
			.content-audit-form-wrapper .content-audit-submit input[type="submit"] {
				background-color: <?php echo esc_attr( $button_bg_color ); ?> !important;
				border-color: <?php echo esc_attr( $button_bg_color ); ?> !important;
				color: <?php echo esc_attr( $button_text_color ); ?> !important;
			}
			.content-audit-form-wrapper #support_ticket_field .elementor-button:hover,
			.content-audit-form-wrapper #support_ticket_field .elementor-button:focus {
				background-color: <?php echo esc_attr( $button_text_color ); ?> !important;
				border-color: <?php echo esc_attr( $button_bg_color ); ?> !important;
				color: <?php echo esc_attr( $button_bg_color ); ?> !important;
			}
		</style>
		<div class="content-audit-form-wrapper">
			<div class="content-audit-header">
				<h2><?php echo esc_html__( 'Content Review Form', 'ab-content-audit' ); ?></h2>
				<p><?php echo esc_html__( 'Please review the content and submit your feedback.', 'content-audit' ); ?></p>
			</div>

			<div class="content-audit-page-info">
				<h3>
					<?php
					// Display heading with content type label.
					/* translators: %s: Content type label (Post, Page, or custom post type name) */
					echo esc_html( sprintf( __( '%s Information', 'ab-content-audit' ), $content_type_label ) );
					?>
				</h3>

				<div class="content-audit-grid">
					<div>
						<p>
							<strong>
								<?php
								// Display label with content type label.
								/* translators: %s: Content type label (Post, Page, or custom post type name) */
								echo esc_html( sprintf( __( '%s Title:', 'ab-content-audit' ), $content_type_label ) );
								?>
							</strong>
							<span><?php echo esc_html( get_the_title( $content_id ) ); ?></span>
						</p>

						<p>
							<strong><?php echo esc_html__( 'URL:', 'ab-content-audit' ); ?></strong>
							<?php
							// Get the relative path from the permalink.
							$permalink     = get_permalink( $content_id );
							$site_url      = site_url();
							$relative_path = str_replace( $site_url, '', $permalink );

							// Get base URL from settings.
							$display_settings = content_audit_get_display_settings();
							$base_url         = isset( $display_settings['base_url'] ) ? $display_settings['base_url'] : home_url();
							// Create the live site URL.
							$live_site_url = untrailingslashit( $base_url ) . $relative_path;
							?>
							<a href="<?php echo esc_url( $live_site_url ); ?>" target="_blank"><?php echo esc_url( $live_site_url ); ?></a>
						</p>
					</div>

					<div>
						<p>
							<strong><?php echo esc_html__( 'Last Review Date:', 'ab-content-audit' ); ?></strong>
							<span><?php echo esc_html( ! empty( $last_review_date ) ? date_i18n( 'F j, Y', strtotime( $last_review_date ) ) : esc_html__( 'Not set', 'ab-content-audit' ) ); ?></span>
						</p>

						<p>
							<strong><?php echo esc_html__( 'Next Review Date:', 'ab-content-audit' ); ?></strong>
							<span><?php echo esc_html( ! empty( $next_review_date ) ? date_i18n( 'F j, Y', strtotime( $next_review_date ) ) : esc_html__( 'Not set', 'ab-content-audit' ) ); ?></span>
						</p>
					</div>
				</div>

				<p class="content-audit-stakeholder">
					<strong><?php echo esc_html__( 'Stakeholder:', 'ab-content-audit' ); ?></strong>
					<span><?php echo esc_html( $stakeholder_name ); ?> (<?php echo esc_html( $stakeholder_department ); ?>)</span>
				</p>

			</div>

			<form method="post" class="content-audit-form">
				<?php wp_nonce_field( 'content_audit_form_' . $content_id, 'content_audit_nonce' ); ?>

				<div class="form-field">
					<legend>
						<?php
						// Display label with content type label.
						/* translators: %s: Content type label (Post, Page, or custom post type name) */
						echo esc_html( sprintf( __( '%s Review Status:', 'ab-content-audit' ), $content_type_label ) );
						?>
					</legend>

					<p>
						<strong>
							<?php
							// Display text with content type label.
							/* translators: %s: Content type label (Post, Page, or custom post type name) */
							echo esc_html( sprintf( __( 'If the %s is still up to date or if it needs editing or removing:', 'ab-content-audit' ), strtolower( $content_type_label ) ) );
							?>
						</strong>
					</p>
					<ul>
						<li>
							<?php
							// Display text with content type label.
							/* translators: %s: Content type label (Post, Page, or custom post type name) */
							echo esc_html( sprintf( __( 'If the %s is still up to date confirm this by selecting "Content is accurate and up-to-date"', 'ab-content-audit' ), strtolower( $content_type_label ) ) );
							?>
						</li>
						<li>
							<?php
							// Display text with content type label.
							/* translators: %s: Content type label (Post, Page, or custom post type name) */
							echo esc_html( sprintf( __( 'If the %s needs changing/removing confirm this by selecting "Content needs changes / I have raised an Support Ticket"', 'ab-content-audit' ), strtolower( $content_type_label ) ) );
							?>
						</li>
					</ul>

					<div class="content-audit-radio-options">
						<label for="needs_changes_no" class="content-audit-radio-label">
							<input type="radio" name="needs_changes" id="needs_changes_no" value="0" checked="checked" />
							<span><?php echo esc_html__( 'Content is accurate and up-to-date', 'ab-content-audit' ); ?></span>
							</label>

							<label for="needs_changes_yes" class="content-audit-radio-label needs-changes">
								<input type="radio" name="needs_changes" id="needs_changes_yes" value="1" />
								<span><?php echo esc_html__( 'Content needs changes / I have raised an Support Ticket', 'ab-content-audit' ); ?></span>
							</label>
						</div>
					</fieldset>
				</div>

				<div id="support_ticket_field">
					<?php
					// Get support ticket URL from settings.
					$display_settings   = content_audit_get_display_settings();
					$support_ticket_url = isset( $display_settings['support_ticket_url'] ) ? $display_settings['support_ticket_url'] : '';
					if ( ! empty( $support_ticket_url ) ) :
						?>
						<p>
							<a class="button elementor-button" href="<?php echo esc_url( $support_ticket_url ); ?>" target="_blank"><?php echo esc_html__( 'Raise an Support Ticket', 'ab-content-audit' ); ?></a>
						</p>
						<?php
					endif;
					?>
					<label for="support_ticket_url"><?php echo esc_html__( 'Support Ticket URL:', 'ab-content-audit' ); ?></label>
					<input type="url" name="support_ticket_url" id="support_ticket_url" class="regular-text" placeholder="https://" />
					<p class="description"><?php echo esc_html__( 'If you have created a Support Ticket for the required changes, please enter the URL to the ticket here.', 'ab-content-audit' ); ?></p>
				</div>

				<div class="content-audit-submit">
					<input type="submit" name="content_audit_submit" class="button button-primary" value="<?php echo esc_attr__( 'Submit Review', 'ab-content-audit' ); ?>" />
				</div>
			</form>
		</div>
		<?php
	}

	return ob_get_clean();
}

/**
 * Generate a secure URL for the content audit form.
 *
 * @param int $content_id The content ID (post or page).
 * @return string The form URL.
 */
function content_audit_generate_form_url( $content_id ) {
	// Create a secure token.
	$token = wp_hash( 'content_audit_' . $content_id . get_the_title( $content_id ) );

	// Look for a page with the content review form shortcode.
	$form_page = null;

	// First check if we have a saved form page ID.
	$form_page_id = get_option( 'content_audit_form_page_id' );
	if ( $form_page_id ) {
		$form_page = get_post( $form_page_id );
		// Verify the page still exists and is published.
		if ( ! $form_page || 'publish' !== $form_page->post_status || false === strpos( $form_page->post_content, '[content_audit_form' ) ) {
			$form_page = null;
		}
	}

	// If we don't have a valid form page, search for one.
	if ( ! $form_page ) {
		// Search for pages with the shortcode.
		$content_review_pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				's'              => '[content_audit_form',
			)
		);

		if ( ! empty( $content_review_pages ) ) {
			$form_page    = $content_review_pages[0];
			$form_page_id = $form_page->ID;
			update_option( 'content_audit_form_page_id', $form_page_id );
		} else {
			// Create a new page for the form.
			$form_page_id = wp_insert_post(
				array(
					'post_title'     => esc_html__( 'Content Review Form', 'ab-content-audit' ),
					'post_content'   => '[content_audit_form]',
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);

			if ( ! is_wp_error( $form_page_id ) ) {
				update_option( 'content_audit_form_page_id', $form_page_id );
			} else {
				return '';
			}
		}
	}

	// Ensure we're using the form page ID and not the content ID for the URL base.
	$form_page_url = get_permalink( $form_page_id );

	// Get the content type (post or page).
	$content_post = get_post( $content_id );
	$content_type = ( 'post' === $content_post->post_type ) ? 'post' : 'page';

	// Generate the URL with parameters.
	$url = add_query_arg(
		array(
			'content_page_id' => $content_id,
			'token'           => $token,
			'content_type'    => $content_type,
		),
		$form_page_url
	);

	// Make sure the URL is properly formed.
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		// Fallback to a simpler URL construction if add_query_arg fails.
		$url = trailingslashit( $form_page_url ) . '?content_page_id=' . rawurlencode( $content_id ) . '&token=' . rawurlencode( $token ) . '&content_type=' . rawurlencode( $content_type );
	}

	return $url;
}
