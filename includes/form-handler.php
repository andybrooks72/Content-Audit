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
				<h2>' . esc_html__( 'Content Review Form', 'peppermoney-content-audit' ) . '</h2>
				<p>' . esc_html__( 'This form is designed to be accessed from the link provided in content review emails.', 'peppermoney-content-audit' ) . '</p>
				<p>' . esc_html__( 'If you received an email about reviewing content, please use the link in that email to access this form with the correct parameters.', 'peppermoney-content-audit' ) . '</p>
				<p>' . esc_html__( 'If you are a site administrator testing this form, you need to provide content_page_id and token parameters.', 'peppermoney-content-audit' ) . '</p>
			</div>';
		} else {
			// For other pages using the shortcode directly.
			return '<div class="content-audit-form-error">
				<p>' . esc_html__( 'Invalid form parameters. This form must be accessed from the link provided in the content review email.', 'peppermoney-content-audit' ) . '</p>
			</div>';
		}
	}

	// Verify token.
	$content_id     = absint( $atts['content_id'] );
	$token          = sanitize_text_field( $atts['token'] );
	$expected_token = wp_hash( 'content_audit_' . $content_id . get_the_title( $content_id ) );

	if ( $token !== $expected_token ) {
		return '<p>' . esc_html__( 'Invalid or expired form link.', 'peppermoney-content-audit' ) . '</p>';
	}

	// Get content data.
	$content = get_post( $content_id );
	if ( ! $content || ! in_array( $content->post_type, array( 'page', 'post' ), true ) ) {
		return '<p>' . esc_html__( 'Content not found.', 'peppermoney-content-audit' ) . '</p>';
	}

	// Get content type.
	$content_type = $content->post_type;

	// Get ACF fields.
	$content_audit = get_fields( $content_id );
	if ( empty( $content_audit ) ) {
		return '<p>' . esc_html__( 'Content audit data not found.', 'peppermoney-content-audit' ) . '</p>';
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
			$form_errors[] = esc_html__( 'Security verification failed. Please try again.', 'peppermoney-content-audit' );
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
					esc_html__( 'Content Review Submission for %s', 'peppermoney-content-audit' ),
					get_the_title( $content_id )
				);

				// Build HTML email message using the same template as audit-panel-updated.php.
				$submissions_url = admin_url( 'admin.php?page=content-audit-submissions' );

				// phpcs:disable
				$message = "
				<!DOCTYPE html>
				<html lang='en' xmlns='http://www.w3.org/1999/xhtml' xmlns:v='urn:schemas-microsoft-com:vml'
					xmlns:o='urn:schemas-microsoft-com:office:office'>

				<head>
					<meta charset='utf-8'> <!-- utf-8 works for most cases -->
					<meta name='viewport' content='width=device-width'> <!-- Forcing initial-scale shouldn't be necessary -->
					<meta http-equiv='X-UA-Compatible' content='IE=edge'> <!-- Use the latest (edge) version of IE rendering engine -->
					<meta name='x-apple-disable-message-reformatting'> <!-- Disable auto-scale in iOS 10 Mail entirely -->
					<meta name='format-detection' content='telephone=no,address=no,email=no,date=no,url=no'>
					<!-- Tell iOS not to automatically link certain text strings. -->
					<meta name='color-scheme' content='light'>
					<meta name='supported-color-schemes' content='light'>
					<title></title> <!--   The title tag shows in email notifications, like Android 4.4. -->

					<!-- What it does: Makes background images in 72ppi Outlook render at correct size. -->
					<!--[if gte mso 9]>
					<xml>
						<o:OfficeDocumentSettings>
							<o:AllowPNG/>
							<o:PixelsPerInch>96</o:PixelsPerInch>
						</o:OfficeDocumentSettings>
					</xml>
					<![endif]-->

					<!-- Desktop Outlook chokes on web font references and defaults to Times New Roman, so we force a safe fallback font. -->
					<!--[if mso]>
					<style>
						* {
							font-family: sans-serif !important;
						}
					</style>
					<![endif]-->

					<!-- CSS Reset : BEGIN -->
					<style>
						/* What it does: Tells the email client that only light styles are provided but the client can transform them to dark. A duplicate of meta color-scheme meta tag above. */
						:root {
							color-scheme: light;
							supported-color-schemes: light;
						}

						/* What it does: Remove spaces around the email design added by some email clients. */
						/* Beware: It can remove the padding / margin and add a background color to the compose a reply window. */
						html,
						body {
							margin: 0 auto !important;
							padding: 0 !important;
							height: 100% !important;
							width: 100% !important;
						}

						/* What it does: Stops email clients resizing small text. */
						* {
							-ms-text-size-adjust: 100%;
							-webkit-text-size-adjust: 100%;
						}

						/* What it does: Centers email on Android 4.4 */
						div[style*='margin: 16px 0'] {
							margin: 0 !important;
						}

						/* What it does: forces Samsung Android mail clients to use the entire viewport */
						#MessageViewBody,
						#MessageWebViewDiv {
							width: 100% !important;
						}

						/* What it does: Stops Outlook from adding extra spacing to tables. */
						table,
						td {
							mso-table-lspace: 0pt !important;
							mso-table-rspace: 0pt !important;
						}

						/* What it does: Fixes webkit padding issue. */
						table {
							border-spacing: 0 !important;
							border-collapse: collapse !important;
							table-layout: fixed !important;
							margin: 0 auto !important;
						}

						/* What it does: Uses a better rendering method when resizing images in IE. */
						img {
							-ms-interpolation-mode: bicubic;
						}

						/* What it does: Prevents Windows 10 Mail from underlining links despite inline CSS. Styles for underlined links should be inline. */
						a {
							text-decoration: none;
						}

						/* What it does: A work-around for email clients meddling in triggered links. */
						a[x-apple-data-detectors],
						/* iOS */
						.unstyle-auto-detected-links a,
						.aBn {
							border-bottom: 0 !important;
							cursor: default !important;
							color: inherit !important;
							text-decoration: none !important;
							font-size: inherit !important;
							font-family: inherit !important;
							font-weight: inherit !important;
							line-height: inherit !important;
						}

						/* What it does: Prevents Gmail from displaying a download button on large, non-linked images. */
						.a6S {
							display: none !important;
							opacity: 0.01 !important;
						}

						/* What it does: Prevents Gmail from changing the text color in conversation threads. */
						.im {
							color: inherit !important;
						}

						/* If the above doesn't work, add a .g-img class to any image in question. */
						img.g-img+div {
							display: none !important;
						}

						/* What it does: Removes right gutter in Gmail iOS app: https://github.com/TedGoas/Cerberus/issues/89  */
						/* Create one of these media queries for each additional viewport size you'd like to fix */

						/* iPhone 4, 4S, 5, 5S, 5C, and 5SE */
						@media only screen and (min-device-width: 320px) and (max-device-width: 374px) {
							u~div .email-container {
								min-width: 320px !important;
							}
						}

						/* iPhone 6, 6S, 7, 8, and X */
						@media only screen and (min-device-width: 375px) and (max-device-width: 413px) {
							u~div .email-container {
								min-width: 375px !important;
							}
						}

						/* iPhone 6+, 7+, and 8+ */
						@media only screen and (min-device-width: 414px) {
							u~div .email-container {
								min-width: 414px !important;
							}
						}
					</style>
					<!-- CSS Reset : END -->

					<!-- Progressive Enhancements : BEGIN -->
					<style>
						/* What it does: Hover styles for buttons */
						.button-td,
						.button-a {
							transition: all 100ms ease-in;
						}

						.button-td-primary:hover,
						.button-a-primary:hover {
							background: #555555 !important;
							border-color: #555555 !important;
						}

						/* Media Queries */
						@media screen and (max-width: 600px) {

							/* What it does: Adjust typography on small screens to improve readability */
							.email-container p {
								font-size: 17px !important;
							}

						}
					</style>
					<!-- Progressive Enhancements : END -->

				</head>

				<body width='100%' style='margin: 0; padding: 0 !important; mso-line-height-rule: exactly;'>
					<center role='article' aria-roledescription='email' lang='en' style='width: 100%;'>
						<!--[if mso | IE]>
						<table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%'>
						<tr>
						<td>
						<![endif]-->

						<!-- Visually Hidden Preheader Text : BEGIN -->
						<div style='max-height:0; overflow:hidden; mso-hide:all;' aria-hidden='true'>
						</div>
						<!-- Visually Hidden Preheader Text : END -->

						<!-- Preview Text Spacing Hack : BEGIN -->
						<div
							style='display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all; font-family: sans-serif;'>
							&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
						</div>
						<!-- Preview Text Spacing Hack : END -->

						<div style='max-width: 600px; margin: 0 auto;' class='email-container'>
							<!--[if mso]>
							<table align='center' role='presentation' cellspacing='0' cellpadding='0' border='0' width='600'>
							<tr>
							<td>
							<![endif]-->

							<!-- Email Body : BEGIN -->
							<table align='center' role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'
								style='margin: auto;'>
								<!-- Email Header : BEGIN -->
								<tr>
									<td style='padding: 20px 0; text-align: center'>
										<img src='https://www.pepper.money/wp-content/uploads/pepper-money-color-1052x168-1.png'
											style='border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;'
											width='200' height='auto' />
									</td>
								</tr>
								<!-- Email Header : END -->

								<!-- 1 Column Text + Button : BEGIN -->
								<tr>
									<td style='background-color: #ffffff;'>
										<table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
											<tr>
												<td
													style='padding: 20px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;'>
													<h1
														style='margin: 0 0 10px 0; font-family: sans-serif; font-size: 25px; line-height: 30px; color: #333333; font-weight: normal;'>
														Content Review Submission for \"" . esc_html( get_the_title( $content_id ) ) . "\"</h1>
													<p style='margin: 0;'>A content review has been submitted by <strong>" . esc_html( $stakeholder_name ) . "</strong>.</p>
												</td>
											</tr>
											<tr>
												<td
													style='padding: 20px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;'>
													<h2
														style='margin: 0 0 10px 0; font-family: sans-serif; font-size: 18px; line-height: 22px; color: #333333; font-weight: bold;'>
														Submission Details:</h2>
													<table style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>
														<tr>
															<th style='text-align: left; padding: 10px; border-bottom: 1px solid #eee; background-color: #f9f9f9; font-weight: 600; font-family: sans-serif; font-size: 15px; line-height: 20px;'>Page</th>
															<td style='text-align: left; padding: 10px; border-bottom: 1px solid #eee; font-family: sans-serif; font-size: 15px; line-height: 20px;'><a href='";

				// Get the relative path from the permalink.
				$permalink = get_permalink( $content_id );
				$site_url  = site_url();
				$relative_path = str_replace( $site_url, '', $permalink );
				// Create the live site URL.
				$live_site_url = 'https://www.pepper.money' . $relative_path;

				$message .= esc_url( $live_site_url ) . "'>" . esc_html( get_the_title( $content_id ) ) . "</a></td>
														</tr>
														<tr>
															<th style='text-align: left; padding: 10px; border-bottom: 1px solid #eee; background-color: #f9f9f9; font-weight: 600; font-family: sans-serif; font-size: 15px; line-height: 20px;'>Stakeholder</th>
															<td style='text-align: left; padding: 10px; border-bottom: 1px solid #eee; font-family: sans-serif; font-size: 15px; line-height: 20px;'>" . esc_html( $stakeholder_name ) . "</td>
														</tr>
														<tr>
															<th style='text-align: left; padding: 10px; border-bottom: 1px solid #eee; background-color: #f9f9f9; font-weight: 600; font-family: sans-serif; font-size: 15px; line-height: 20px;'>Department</th>
															<td style='text-align: left; padding: 10px; border-bottom: 1px solid #eee; font-family: sans-serif; font-size: 15px; line-height: 20px;'>" . esc_html( $stakeholder_department ) . "</td>
														</tr>
														<tr>
															<th style='text-align: left; padding: 10px; border-bottom: 1px solid #eee; background-color: #f9f9f9; font-weight: 600; font-family: sans-serif; font-size: 15px; line-height: 20px;'>Changes Needed</th>
															<td style='text-align: left; padding: 10px; border-bottom: 1px solid #eee; font-family: sans-serif; font-size: 15px; line-height: 20px; " . ($needs_changes ? 'color: #d9042b; font-weight: bold;' : 'color: #46b450;') . "'>" . ($needs_changes ? esc_html__( 'Yes', 'content-audit' ) : esc_html__( 'No', 'peppermoney-content-audit' )) . "</td>
														</tr>";

				// Only include support ticket URL if provided.
				if ( ! empty( $support_ticket_url ) ) {
					$message .= "
														<tr>
															<th style='text-align: left; padding: 10px; border-bottom: 1px solid #eee; background-color: #f9f9f9; font-weight: 600; font-family: sans-serif; font-size: 15px; line-height: 20px;'>Support Ticket</th>
															<td style='text-align: left; padding: 10px; border-bottom: 1px solid #eee; font-family: sans-serif; font-size: 15px; line-height: 20px;'><a href='" . esc_url( $support_ticket_url ) . "'>" . esc_html__( 'View Ticket', 'peppermoney-content-audit' ) . "</a></td>
														</tr>";
				}

				$message .= "
													</table>
													<p style='margin: 0 0 10px 0;'>You can view all content audit submissions in the admin dashboard.</p>
													<p style='margin: 0 0 20px 0;'>Kind regards,<br>Pepper Money UX Team</p>
													<a href='" . esc_url( $submissions_url ) . "' style='display: block; background-color: #d9042b; color: #fff; padding: 10px 15px; text-decoration: none; border-radius: 3px; margin-top: 15px;'>" . esc_html__( 'View All Submissions', 'peppermoney-content-audit' ) . "</a>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<!-- 1 Column Text + Button : END -->

							</table>
							<!-- Email Body : END -->

							<!--[if mso]>
							</td>
							</tr>
							</table>
							<![endif]-->
						</div>

						<!--[if mso | IE]>
						</td>
						</tr>
						</table>
						<![endif]-->
					</center>
				</body>

				</html>
				"; // phpcs:enable

				// Get email settings.
				$email_settings     = content_audit_get_email_settings();
				$notification_email = $email_settings['notification_email'];
				$from_email         = $email_settings['from_email'];
				$from_name          = $email_settings['from_name'];

				// Set up email headers for HTML.
				$headers = array(
					'From: ' . $from_name . ' <' . $from_email . '>',
					'Content-Type: text/html; charset=UTF-8',
					'Reply-To: ' . $notification_email,
				);

				wp_mail( $notification_email, $subject, $message, $headers );
			} else {
				$form_errors[] = esc_html__( 'Failed to save your submission. Please try again.', 'peppermoney-content-audit' );
			}
		}
	}

	// Display the form.
	ob_start();

	if ( $form_submitted ) {
		echo '<div class="content-audit-success">';
		echo '<h2>' . esc_html__( 'Thank You!', 'peppermoney-content-audit' ) . '</h2>';
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
		<div class="content-audit-form-wrapper">
			<div class="content-audit-header">
				<h2><?php echo esc_html__( 'Content Review Form', 'peppermoney-content-audit' ); ?></h2>
				<p><?php echo esc_html__( 'Please review the content and submit your feedback.', 'content-audit' ); ?></p>
			</div>

			<div class="content-audit-page-info">
				<h3>
					<?php
					// Display different heading based on content type.
					if ( 'page' === $content_type ) {
						echo esc_html__( 'Page Information', 'peppermoney-content-audit' );
					} else {
						echo esc_html__( 'Post Information', 'peppermoney-content-audit' );
					}
					?>
				</h3>

				<div class="content-audit-grid">
					<div>
						<p>
							<strong>
								<?php
								// Display different label based on content type.
								if ( 'page' === $content_type ) {
									echo esc_html__( 'Page Title:', 'peppermoney-content-audit' );
								} else {
									echo esc_html__( 'Post Title:', 'peppermoney-content-audit' );
								}
								?>
							</strong>
							<span><?php echo esc_html( get_the_title( $content_id ) ); ?></span>
						</p>

						<p>
							<strong><?php echo esc_html__( 'URL:', 'peppermoney-content-audit' ); ?></strong>
							<?php
							// Get the relative path from the permalink.
							$permalink     = get_permalink( $content_id );
							$site_url      = site_url();
							$relative_path = str_replace( $site_url, '', $permalink );
							// Create the live site URL.
							$live_site_url = 'https://www.pepper.money' . $relative_path;
							?>
							<a href="<?php echo esc_url( $live_site_url ); ?>" target="_blank"><?php echo esc_url( $live_site_url ); ?></a>
						</p>
					</div>

					<div>
						<p>
							<strong><?php echo esc_html__( 'Last Review Date:', 'peppermoney-content-audit' ); ?></strong>
							<span><?php echo esc_html( ! empty( $last_review_date ) ? date_i18n( 'F j, Y', strtotime( $last_review_date ) ) : esc_html__( 'Not set', 'peppermoney-content-audit' ) ); ?></span>
						</p>

						<p>
							<strong><?php echo esc_html__( 'Next Review Date:', 'peppermoney-content-audit' ); ?></strong>
							<span><?php echo esc_html( ! empty( $next_review_date ) ? date_i18n( 'F j, Y', strtotime( $next_review_date ) ) : esc_html__( 'Not set', 'peppermoney-content-audit' ) ); ?></span>
						</p>
					</div>
				</div>

				<p class="content-audit-stakeholder">
					<strong><?php echo esc_html__( 'Stakeholder:', 'peppermoney-content-audit' ); ?></strong>
					<span><?php echo esc_html( $stakeholder_name ); ?> (<?php echo esc_html( $stakeholder_department ); ?>)</span>
				</p>

			</div>

			<form method="post" class="content-audit-form">
				<?php wp_nonce_field( 'content_audit_form_' . $content_id, 'content_audit_nonce' ); ?>

				<div class="form-field">
					<legend>
						<?php
						// Display different label based on content type.
						if ( 'page' === $content_type ) {
							echo esc_html__( 'Page Review Status:', 'peppermoney-content-audit' );
						} else {
							echo esc_html__( 'Post Review Status:', 'peppermoney-content-audit' );
						}
						?>
					</legend>

					<p>
						<strong>
							<?php
								// Display different text based on content type.
							if ( 'page' === $content_type ) {
								echo esc_html__( 'If the page is still up to date or if it needs editing or removing:', 'peppermoney-content-audit' );
							} else {
								echo esc_html__( 'If the post is still up to date or if it needs editing or removing:', 'peppermoney-content-audit' );
							}
							?>
						</strong>
					</p>
					<ul>
						<li>
							<?php
							// Display different text based on content type.
							if ( 'page' === $content_type ) {
								echo esc_html__( 'If the page is still up to date confirm this by selecting "Content is accurate and up-to-date"', 'peppermoney-content-audit' );
							} else {
								echo esc_html__( 'If the post is still up to date confirm this by selecting "Content is accurate and up-to-date"', 'peppermoney-content-audit' );
							}
							?>
						</li>
						<li>
							<?php
							// Display different text based on content type.
							if ( 'page' === $content_type ) {
								echo esc_html__( 'If the page needs changing/removing confirm this by selecting "Content needs changes / I have raised an SD Ticket"', 'peppermoney-content-audit' );
							} else {
								echo esc_html__( 'If the post needs changing/removing confirm this by selecting "Content needs changes / I have raised an SD Ticket"', 'peppermoney-content-audit' );
							}
							?>
						</li>
					</ul>

					<div class="content-audit-radio-options">
						<label for="needs_changes_no" class="content-audit-radio-label">
							<input type="radio" name="needs_changes" id="needs_changes_no" value="0" checked="checked" />
							<span><?php echo esc_html__( 'Content is accurate and up-to-date', 'peppermoney-content-audit' ); ?></span>
							</label>

							<label for="needs_changes_yes" class="content-audit-radio-label needs-changes">
								<input type="radio" name="needs_changes" id="needs_changes_yes" value="1" />
								<span><?php echo esc_html__( 'Content needs changes / I have raised an SD Ticket', 'peppermoney-content-audit' ); ?></span>
							</label>
						</div>
					</fieldset>
				</div>

				<div id="support_ticket_field">
					<p>
						<a class="button elementor-button" href="https://helpdesk.pepper.money:8080/homepage.dp?" target="_blank"><?php echo esc_html__( 'Raise an SD Ticket', 'peppermoney-content-audit' ); ?></a>
					</p>
					<label for="support_ticket_url"><?php echo esc_html__( 'SD Ticket URL:', 'peppermoney-content-audit' ); ?></label>
					<input type="url" name="support_ticket_url" id="support_ticket_url" class="regular-text" placeholder="https://" />
					<p class="description"><?php echo esc_html__( 'If you have created a SD Ticket for the required changes, please enter the URL to the ticket here.', 'peppermoney-content-audit' ); ?></p>
				</div>

				<div class="content-audit-submit">
					<input type="submit" name="content_audit_submit" class="button button-primary" value="<?php echo esc_attr__( 'Submit Review', 'peppermoney-content-audit' ); ?>" />
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
					'post_title'     => esc_html__( 'Content Review Form', 'peppermoney-content-audit' ),
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
