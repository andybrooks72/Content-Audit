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
 * Create the submissions table if it doesn't exist.
 *
 * @return void
 */
function content_audit_create_submissions_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'content_audit_submissions';
	$charset_collate = $wpdb->get_charset_collate();

	// Check if table exists.
	$table_exists = $wpdb->get_var(
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$wpdb->esc_like( $table_name )
		)
	) !== null;

	// If table doesn't exist, create it.
	if ( ! $table_exists ) {
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			content_id bigint(20) NOT NULL,
			content_title varchar(255) NOT NULL,
			content_type varchar(20) NOT NULL DEFAULT 'page',
			stakeholder_name varchar(100) NOT NULL,
			stakeholder_email varchar(100) NOT NULL,
			stakeholder_department varchar(100) NOT NULL,
			submission_date datetime NOT NULL,
			needs_changes tinyint(1) NOT NULL DEFAULT 0,
			support_ticket_url varchar(255) DEFAULT '',
			next_review_date datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	} else {
		// Check if the content_type column exists, add it if it doesn't.
		$column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $table_name LIKE 'content_type'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( empty( $column_exists ) ) {
			$wpdb->query( "ALTER TABLE $table_name ADD COLUMN content_type varchar(20) NOT NULL DEFAULT 'page' AFTER page_title" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}

		// Check if the content_id column exists, add it if it doesn't.
		$column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $table_name LIKE 'content_id'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( empty( $column_exists ) ) {
			// Create a temporary table with the new structure
			$temp_table_name = $table_name . '_temp';
			
			// Drop the temporary table if it exists
			$wpdb->query( "DROP TABLE IF EXISTS $temp_table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			
			// Create the temporary table with the new structure
			$sql = "CREATE TABLE $temp_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				content_id bigint(20) NOT NULL,
				content_title varchar(255) NOT NULL,
				content_type varchar(20) NOT NULL DEFAULT 'page',
				stakeholder_name varchar(100) NOT NULL,
				stakeholder_email varchar(100) NOT NULL,
				stakeholder_department varchar(100) NOT NULL,
				submission_date datetime NOT NULL,
				needs_changes tinyint(1) NOT NULL DEFAULT 0,
				support_ticket_url varchar(255) DEFAULT '',
				next_review_date datetime NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;";
			
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
			
			// Copy data from the old table to the new one
			$wpdb->query( "INSERT INTO $temp_table_name (id, content_id, content_title, content_type, stakeholder_name, stakeholder_email, stakeholder_department, submission_date, needs_changes, support_ticket_url, next_review_date) 
			               SELECT id, page_id, page_title, content_type, stakeholder_name, stakeholder_email, stakeholder_department, submission_date, needs_changes, support_ticket_url, next_review_date 
			               FROM $table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			
			// Drop the old table
			$wpdb->query( "DROP TABLE $table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			
			// Rename the temporary table to the original name
			$wpdb->query( "RENAME TABLE $temp_table_name TO $table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
	}
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

	// Check if the table has the new column structure
	$has_content_id = $wpdb->get_var( "SHOW COLUMNS FROM $table_name LIKE 'content_id'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

	if ( $has_content_id ) {
		// New column structure exists, use content_id and content_title
		return $wpdb->insert(
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
		// Old column structure, use page_id and page_title
		return $wpdb->insert(
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
			return '<div class="content-audit-form-message" style="background-color: #f8f8f8; border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px;">
				<h2>' . esc_html__( 'Content Review Form', 'content-audit' ) . '</h2>
				<p>' . esc_html__( 'This form is designed to be accessed from the link provided in content review emails.', 'content-audit' ) . '</p>
				<p>' . esc_html__( 'If you received an email about reviewing content, please use the link in that email to access this form with the correct parameters.', 'content-audit' ) . '</p>
				<p>' . esc_html__( 'If you are a site administrator testing this form, you need to provide content_page_id and token parameters.', 'content-audit' ) . '</p>
			</div>';
		} else {
			// For other pages using the shortcode directly.
			return '<div class="content-audit-form-error" style="background-color: #fff8f8; border: 1px solid #ffdddd; padding: 15px; margin: 10px 0; border-radius: 4px;">
				<p>' . esc_html__( 'Invalid form parameters. This form must be accessed from the link provided in the content review email.', 'content-audit' ) . '</p>
			</div>';
		}
	}

	// Verify token.
	$content_id        = absint( $atts['content_id'] );
	$token             = sanitize_text_field( $atts['token'] );
	$expected_token    = wp_hash( 'content_audit_' . $content_id . get_the_title( $content_id ) );

	if ( $token !== $expected_token ) {
		return '<p>' . esc_html__( 'Invalid or expired form link.', 'content-audit' ) . '</p>';
	}

	// Get content data.
	$content = get_post( $content_id );
	if ( ! $content || ! in_array( $content->post_type, array( 'page', 'post' ), true ) ) {
		return '<p>' . esc_html__( 'Content not found.', 'content-audit' ) . '</p>';
	}

	// Get content type.
	$content_type = $content->post_type;

	// Get ACF fields.
	$content_audit = get_fields( $content_id );
	if ( empty( $content_audit ) ) {
		return '<p>' . esc_html__( 'Content audit data not found.', 'content-audit' ) . '</p>';
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
			$form_errors[] = esc_html__( 'Security verification failed. Please try again.', 'content-audit' );
		} else {
			// Check if the submissions table exists and create it if it doesn't.
			content_audit_create_submissions_table();

			// Process form data.
			$needs_changes      = isset( $_POST['needs_changes'] ) && '1' === $_POST['needs_changes'] ? 1 : 0;
			$support_ticket_url = '';

			// Process and sanitize the support ticket URL.
			if ( isset( $_POST['support_ticket_url'] ) && ! empty( $_POST['support_ticket_url'] ) ) {
				$raw_url = wp_unslash( $_POST['support_ticket_url'] );

				// Add https:// if no protocol is specified.
				if ( ! preg_match( '~^(?:f|ht)tps?://~i', $raw_url ) ) {
					$raw_url = 'https://' . $raw_url;
				}

				// Use esc_url_raw to sanitize but preserve the URL functionality.
				$support_ticket_url = esc_url_raw( $raw_url );

				// Verify that we still have a valid URL after sanitization.
				if ( empty( $support_ticket_url ) || $support_ticket_url === 'https://' ) {
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
				$form_submitted  = true;
				$success_message = esc_html__( 'Thank you for reviewing this content. Your submission has been recorded.', 'content-audit' );

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
					esc_html__( 'Content Review Submission for %s', 'content-audit' ),
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
							&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
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
				
				// Get the relative path from the permalink
				$permalink = get_permalink( $content_id );
				$site_url  = site_url();
				$relative_path = str_replace( $site_url, '', $permalink );
				// Create the live site URL
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
															<td style='text-align: left; padding: 10px; border-bottom: 1px solid #eee; font-family: sans-serif; font-size: 15px; line-height: 20px; " . ($needs_changes ? 'color: #d9042b; font-weight: bold;' : 'color: #46b450;') . "'>" . ($needs_changes ? esc_html__( 'Yes', 'content-audit' ) : esc_html__( 'No', 'content-audit' )) . "</td>
														</tr>";
				
				// Only include support ticket URL if provided.
				if ( ! empty( $support_ticket_url ) ) {
					$message .= "
														<tr>
															<th style='text-align: left; padding: 10px; border-bottom: 1px solid #eee; background-color: #f9f9f9; font-weight: 600; font-family: sans-serif; font-size: 15px; line-height: 20px;'>Support Ticket</th>
															<td style='text-align: left; padding: 10px; border-bottom: 1px solid #eee; font-family: sans-serif; font-size: 15px; line-height: 20px;'><a href='" . esc_url( $support_ticket_url ) . "'>" . esc_html__( 'View Ticket', 'content-audit' ) . "</a></td>
														</tr>";
				}
				
				$message .= "
													</table>
													<p style='margin: 0 0 10px 0;'>You can view all content audit submissions in the admin dashboard.</p>
													<p style='margin: 0 0 20px 0;'>Kind regards,<br>Pepper Money UX Team</p>
													<a href='" . esc_url( $submissions_url ) . "' style='display: block; background-color: #d9042b; color: #fff; padding: 10px 15px; text-decoration: none; border-radius: 3px; margin-top: 15px;'>" . esc_html__( 'View All Submissions', 'content-audit' ) . "</a>
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

				// Set up email headers for HTML.
				$headers = array(
					'From: ux@pepper.money',
					'Content-Type: text/html; charset=UTF-8',
					'Cc: ' . $admin_email,
					'Reply-To: ' . $admin_email,
				);

				wp_mail( $admin_email, $subject, $message, $headers );
			} else {
				$form_errors[] = esc_html__( 'Failed to save your submission. Please try again.', 'content-audit' );
			}
		}
	}

	// Display the form.
	ob_start();

	if ( $form_submitted ) {
		echo '<div class="content-audit-success">';
		echo '<h2>' . esc_html__( 'Thank You!', 'content-audit' ) . '</h2>';
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
			.content-audit-form-wrapper {
				max-width: 800px;
				margin: 40px auto;
				background-color: #fff;
				border-radius: 8px;
				box-shadow: 0 2px 10px rgba(0,0,0,0.1);
				padding: 30px;
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			}
			.content-audit-header {
				border-bottom: 1px solid #eee;
				margin-bottom: 25px;
				padding-bottom: 15px;
			}
			.content-audit-header h2 {
				color: #333;
				margin-top: 0;
				margin-bottom: 10px;
				font-size: 24px;
				font-weight: 600;
			}
			.content-audit-header p {
				color: #666;
				margin-bottom: 0;
				font-size: 16px;
			}
			.content-audit-page-info {
				background-color: #f9f9f9;
				border-radius: 6px;
				padding: 20px;
				margin-bottom: 25px;
			}
			.content-audit-page-info h3 {
				color: #333;
				margin-top: 0;
				margin-bottom: 15px;
				font-size: 18px;
				font-weight: 600;
			}
			.content-audit-grid {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 15px;
			}
			.content-audit-grid p {
				margin: 0 0 10px;
				font-size: 15px;
			}
			.content-audit-grid strong {
				color: #555;
				display: block;
				margin-bottom: 4px;
			}
			.content-audit-grid span, 
			.content-audit-grid a {
				color: #333;
				font-size: 15px;
			}
			.content-audit-grid a {
				color: #d9042b;
				text-decoration: none;
				word-break: break-all;
			}
			.content-audit-stakeholder {
				margin: 10px 0 0;
				font-size: 15px;
			}
			.content-audit-stakeholder strong {
				color: #555;
				display: inline;
				margin-right: 5px;
			}
			.content-audit-stakeholder span {
				color: #333;
				font-size: 15px;
			}
			.content-audit-form .form-field {
				margin-bottom: 25px;
			}
			.content-audit-form fieldset {
				border: none;
				padding: 0;
				margin: 0;
			}
			.content-audit-form legend {
				font-weight: 600;
				color: #333;
				margin-bottom: 12px;
				font-size: 16px;
				display: block;
			}
			.content-audit-radio-options {
				display: flex;
				gap: 20px;
				flex-wrap: wrap;
			}
			.content-audit-radio-label {
				display: flex;
				align-items: center;
				background-color: #f1f8f1;
				border: 1px solid #ddd;
				border-radius: 6px;
				padding: 15px;
				cursor: pointer;
				flex: 1;
				min-width: 200px;
			}
			.content-audit-radio-label.needs-changes {
				background-color: #fff8f8;
			}
			.content-audit-radio-label input[type="radio"] {
				margin-right: 10px;
			}
			.content-audit-radio-label span {
				font-size: 15px;
				color: #333;
			}
			#support_ticket_field {
				display: none;
				margin-bottom: 25px;
				background-color: #fff8f8;
				border-radius: 6px;
				padding: 20px;
				border: 1px solid #ffdddd;
			}
			#support_ticket_field label {
				display: block;
				margin-bottom: 10px;
				font-weight: 600;
				color: #333;
				font-size: 15px;
			}
			#support_ticket_field input[type="url"] {
				width: 100%;
				padding: 10px;
				border: 1px solid #ddd;
				border-radius: 4px;
				font-size: 15px;
			}
			#support_ticket_field .description {
				margin-top: 8px;
				color: #666;
				font-size: 13px;
			}
			.content-audit-submit {
				margin-top: 30px;
			}
			.content-audit-submit input[type="submit"] {
				background-color: #d9042b;
				border-color: #d9042b;
				color: #fff;
				padding: 10px 20px;
				font-size: 16px;
				border-radius: 4px;
				cursor: pointer;
				border: none;
				font-weight: 500;
				transition: all 0.2s ease;
			}
			.content-audit-errors {
				background-color: #ffeeee;
				border-left: 4px solid #d9042b;
				padding: 15px 20px;
				margin-bottom: 25px;
				border-radius: 4px;
				box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			}
			.content-audit-errors p {
				margin: 0;
				color: #d9042b;
				font-size: 15px;
			}
			.content-audit-success {
				max-width: 800px;
				margin: 40px auto;
				background-color: #f1f8f1;
				border-left: 4px solid #46b450;
				padding: 25px;
				border-radius: 4px;
				box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			}
			.content-audit-success h2 {
				margin-top: 0;
				color: #333;
				font-size: 20px;
			}
			.content-audit-success p {
				margin-bottom: 0;
				font-size: 16px;
				color: #333;
			}
		</style>

		<div class="content-audit-form-wrapper">
			<div class="content-audit-header">
				<h2><?php echo esc_html__( 'Content Review Form', 'content-audit' ); ?></h2>
				<p><?php echo esc_html__( 'Please review the content and submit your feedback.', 'content-audit' ); ?></p>
			</div>
			
			<div class="content-audit-page-info">
				<h3>
					<?php 
					// Display different heading based on content type.
					if ( 'page' === $content_type ) {
						echo esc_html__( 'Page Information', 'content-audit' );
					} else {
						echo esc_html__( 'Post Information', 'content-audit' );
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
									echo esc_html__( 'Page Title:', 'content-audit' );
								} else {
									echo esc_html__( 'Post Title:', 'content-audit' );
								}
								?>
							</strong>
							<span><?php echo esc_html( get_the_title( $content_id ) ); ?></span>
						</p>
					
						<p>
							<strong><?php echo esc_html__( 'URL:', 'content-audit' ); ?></strong>
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
							<strong><?php echo esc_html__( 'Last Review Date:', 'content-audit' ); ?></strong>
							<span><?php echo esc_html( ! empty( $last_review_date ) ? date_i18n( 'F j, Y', strtotime( $last_review_date ) ) : esc_html__( 'Not set', 'content-audit' ) ); ?></span>
						</p>
					
						<p>
							<strong><?php echo esc_html__( 'Next Review Date:', 'content-audit' ); ?></strong>
							<span><?php echo esc_html( ! empty( $next_review_date ) ? date_i18n( 'F j, Y', strtotime( $next_review_date ) ) : esc_html__( 'Not set', 'content-audit' ) ); ?></span>
						</p>
					</div>
				</div>
				
				<p class="content-audit-stakeholder">
					<strong><?php echo esc_html__( 'Stakeholder:', 'content-audit' ); ?></strong>
					<span><?php echo esc_html( $stakeholder_name ); ?> (<?php echo esc_html( $stakeholder_department ); ?>)</span>
				</p>
			</div>
			
			<form method="post" class="content-audit-form">
				<?php wp_nonce_field( 'content_audit_form_' . $content_id, 'content_audit_nonce' ); ?>
				
				<div class="form-field">
					<fieldset>
						<legend>
							<?php 
							// Display different label based on content type.
							if ( 'page' === $content_type ) {
								echo esc_html__( 'Page Review Status:', 'content-audit' );
							} else {
								echo esc_html__( 'Post Review Status:', 'content-audit' );
							}
							?>
						</legend>
						
						<div class="content-audit-radio-options">
							<label for="needs_changes_no" class="content-audit-radio-label">
								<input type="radio" name="needs_changes" id="needs_changes_no" value="0" checked="checked" />
								<span><?php echo esc_html__( 'Content is accurate and up-to-date', 'content-audit' ); ?></span>
							</label>
							
							<label for="needs_changes_yes" class="content-audit-radio-label needs-changes">
								<input type="radio" name="needs_changes" id="needs_changes_yes" value="1" />
								<span><?php echo esc_html__( 'Content needs changes', 'content-audit' ); ?></span>
							</label>
						</div>
					</fieldset>
				</div>
				
				<div id="support_ticket_field">
					<label for="support_ticket_url"><?php echo esc_html__( 'Support Ticket URL (if applicable):', 'content-audit' ); ?></label>
					<input type="url" name="support_ticket_url" id="support_ticket_url" class="regular-text" placeholder="https://" />
					<p class="description"><?php echo esc_html__( 'If you have created a support ticket for the required changes, please enter the URL here.', 'content-audit' ); ?></p>
				</div>
				
				<div class="content-audit-submit">
					<input type="submit" name="content_audit_submit" class="button button-primary" value="<?php echo esc_attr__( 'Submit Review', 'content-audit' ); ?>" />
				</div>
			</form>
			
			<script>
				document.addEventListener('DOMContentLoaded', function() {
					const needsChangesYes = document.getElementById('needs_changes_yes');
					const needsChangesNo = document.getElementById('needs_changes_no');
					const supportTicketField = document.getElementById('support_ticket_field');
					const submitButton = document.querySelector('input[name="content_audit_submit"]');
					
					// Function to toggle support ticket field visibility
					function toggleSupportTicketField() {
						if (needsChangesYes.checked) {
							supportTicketField.style.display = 'block';
						} else {
							supportTicketField.style.display = 'none';
							document.getElementById('support_ticket_url').value = '';
						}
					}
					
					// Add event listeners
					needsChangesYes.addEventListener('change', toggleSupportTicketField);
					needsChangesNo.addEventListener('change', toggleSupportTicketField);
					
					// Add focus styles to form elements
					const formInputs = document.querySelectorAll('input[type="url"], input[type="text"]');
					formInputs.forEach(input => {
						input.addEventListener('focus', function() {
							this.style.borderColor = '#d9042b';
							this.style.boxShadow = '0 0 0 1px #d9042b';
							this.style.outline = 'none';
						});
						input.addEventListener('blur', function() {
							this.style.borderColor = '#ddd';
							this.style.boxShadow = 'none';
						});
					});
					
					// Initial state
					toggleSupportTicketField();
				});
			</script>
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
					'post_title'     => esc_html__( 'Content Review Form', 'content-audit' ),
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

	// Generate the URL with parameters.
	$url = add_query_arg(
		array(
			'content_page_id' => $content_id,
			'token'           => $token,
		),
		$form_page_url
	);

	// Make sure the URL is properly formed.
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		// Fallback to a simpler URL construction if add_query_arg fails.
		$url = trailingslashit( $form_page_url ) . '?content_page_id=' . rawurlencode( $content_id ) . '&token=' . rawurlencode( $token );
	}

	return $url;
}
