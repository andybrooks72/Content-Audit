<?php
/**
 * Content Audit Table Display.
 *
 * @package ContentAudit
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Display the content audit table.
 *
 * @return void
 */
function content_audit_display_table() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Get filter value from URL parameter, default to 30days.
	$filter = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : '30days';

	// Get content type from URL parameter, default to pages.
	$content_type = isset( $_GET['content_type'] ) ? sanitize_text_field( wp_unslash( $_GET['content_type'] ) ) : 'pages';

	// Ensure content_type is valid.
	if ( ! in_array( $content_type, array( 'pages', 'posts' ), true ) ) {
		$content_type = 'pages';
	}

	// Add tabs for switching between pages and posts.
	?>
	<div class="nav-tab-wrapper">
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page'         => 'content-audit',
					'content_type' => 'pages',
					'filter'       => $filter,
				)
			)
		);
		?>
					" class="nav-tab <?php echo 'pages' === $content_type ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Pages', 'content-audit' ); ?>
		</a>
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page'         => 'content-audit',
					'content_type' => 'posts',
					'filter'       => $filter,
				)
			)
		);
		?>
					" class="nav-tab <?php echo 'posts' === $content_type ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Posts', 'content-audit' ); ?>
		</a>
	</div>
	
	<!-- Add filter dropdown -->
	<div class="tablenav top">
		<div class="alignleft actions">
			<form method="get">
				<input type="hidden" name="page" value="content-audit">
				<input type="hidden" name="content_type" value="<?php echo esc_attr( $content_type ); ?>">
				<select name="filter">
					<option value="30days" <?php selected( $filter, '30days' ); ?>><?php esc_html_e( 'Next 30 Days & Overdue', 'content-audit' ); ?></option>
					<option value="overdue" <?php selected( $filter, 'overdue' ); ?>><?php esc_html_e( 'Overdue Only', 'content-audit' ); ?></option>
					<option value="3months" <?php selected( $filter, '3months' ); ?>><?php esc_html_e( 'Next 3 Months', 'content-audit' ); ?></option>
					<option value="6months" <?php selected( $filter, '6months' ); ?>><?php esc_html_e( 'Next 6 Months', 'content-audit' ); ?></option>
					<option value="12months" <?php selected( $filter, '12months' ); ?>><?php esc_html_e( 'Next 12 Months', 'content-audit' ); ?></option>
					<option value="all" <?php selected( $filter, 'all' ); ?>><?php esc_html_e( 'All Content', 'content-audit' ); ?></option>
				</select>
				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'content-audit' ); ?>">
			</form>
		</div>
		<br class="clear">
	</div>
	<?php

	echo '<table class="content-audit-table">';
	echo '<thead>';
	echo '<tr>';
	echo '<th>' . esc_html( 'posts' === $content_type ? 'Post Title' : 'Page Title' ) . '</th>';
	echo '<th>Stakeholder Name</th>';
	echo '<th>Stakeholder Dept</th>';
	echo '<th>Email</th>';
	echo '<th>Last Review Date</th>';
	echo '<th>Next Review Date</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';

	$posts_per_page = 200;
	$today          = gmdate( 'Ymd' );

	// Set up meta query based on filter.
	$meta_query = array();

	// Base meta query to ensure all required fields exist.
	$base_meta_query = array(
		'relation' => 'AND',
		array(
			'key'     => 'next_review_date',
			'compare' => 'EXISTS',
		),
		array(
			'key'     => 'next_review_date',
			'compare' => '!=',
			'value'   => '',
		),
		array(
			'key'     => 'stakeholder_name',
			'compare' => 'EXISTS',
		),
		array(
			'key'     => 'stakeholder_name',
			'compare' => '!=',
			'value'   => '',
		),
	);

	// Add filter-specific conditions.
	switch ( $filter ) {
		case 'overdue':
			// Only show overdue content.
			$meta_query = array(
				'relation' => 'AND',
				$base_meta_query,
				array(
					'key'     => 'next_review_date',
					'value'   => $today,
					'compare' => '<',
					'type'    => 'DATE',
				),
			);
			break;

		case '30days':
			// Show content due in next 30 days or overdue.
			$thirty_days = gmdate( 'Ymd', strtotime( '+30 days' ) );
			$meta_query  = array(
				'relation' => 'AND',
				$base_meta_query,
				array(
					'relation' => 'OR',
					array(
						'key'     => 'next_review_date',
						'value'   => $today,
						'compare' => '<',
						'type'    => 'DATE',
					),
					array(
						'key'     => 'next_review_date',
						'value'   => array( $today, $thirty_days ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					),
				),
			);
			break;

		case '3months':
			// Show content due in next 3 months.
			$three_months = gmdate( 'Ymd', strtotime( '+3 months' ) );
			$meta_query   = array(
				'relation' => 'AND',
				$base_meta_query,
				array(
					'key'     => 'next_review_date',
					'value'   => array( $today, $three_months ),
					'compare' => '<=',
					'type'    => 'DATE',
				),
			);
			break;

		case '6months':
			// Show content due in next 6 months.
			$six_months = gmdate( 'Ymd', strtotime( '+6 months' ) );
			$meta_query = array(
				'relation' => 'AND',
				$base_meta_query,
				array(
					'key'     => 'next_review_date',
					'value'   => array( $today, $six_months ),
					'compare' => '<=',
					'type'    => 'DATE',
				),
			);
			break;

		case '12months':
			// Show content due in next 12 months.
			$twelve_months = gmdate( 'Ymd', strtotime( '+12 months' ) );
			$meta_query    = array(
				'relation' => 'AND',
				$base_meta_query,
				array(
					'key'     => 'next_review_date',
					'value'   => array( $today, $twelve_months ),
					'compare' => '<=',
					'type'    => 'DATE',
				),
			);
			break;

		case 'all':
			// Show all content with next_review_date.
			$meta_query = $base_meta_query;
			break;

		default:
			// Default to 30 days.
			$thirty_days = gmdate( 'Ymd', strtotime( '+30 days' ) );
			$meta_query  = array(
				'relation' => 'AND',
				$base_meta_query,
				array(
					'relation' => 'OR',
					array(
						'key'     => 'next_review_date',
						'value'   => $today,
						'compare' => '<',
						'type'    => 'DATE',
					),
					array(
						'key'     => 'next_review_date',
						'value'   => array( $today, $thirty_days ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					),
				),
			);
			break;
	}

	// Query arguments.
	$args = array(
		'post_type'      => 'posts' === $content_type ? 'post' : 'page',
		'post_status'    => 'publish',
		'posts_per_page' => $posts_per_page,
		'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		'meta_key'       => 'next_review_date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'orderby'        => 'meta_value',
		'order'          => 'ASC', // Show soonest dates first.
	);

	$ca_query = new WP_Query( $args );

	if ( $ca_query->have_posts() ) :

		while ( $ca_query->have_posts() ) :
			$ca_query->the_post();
			$content_audit = get_fields();

			// Skip if required fields are not set.
			if ( empty( $content_audit ) || empty( $content_audit['next_review_date'] ) || empty( $content_audit['stakeholder_name'] ) ) {
				continue;
			}

			$page_title        = get_the_title();
			$permalink         = get_the_permalink();
			$site_url          = site_url();
			$relative_path     = str_replace( $site_url, '', $permalink );
			$page_url          = 'https://www.pepper.money' . $relative_path;
			$stakeholder_name  = $content_audit['stakeholder_name'];
			$stakeholder_dept  = $content_audit['stakeholder_department'];
			$stakeholder_email = $content_audit['stakeholder_email'];
			$last_review_date  = $content_audit['last_review_date'];
			$next_review_date  = $content_audit['next_review_date'];
			$format_out        = 'F d, Y';
			$date              = new DateTime( $next_review_date );
			$admin_email       = get_option( 'admin_email' );
			$unique_id         = 'send_email_' . get_the_ID();
			$nonce             = wp_create_nonce( 'send_email_nonce' );
			$page_id           = get_the_ID();
			$is_overdue        = $next_review_date < $today;

			?>
			<tr id="page-<?php echo esc_attr( $page_id ); ?>" class="<?php echo $is_overdue ? 'overdue' : ''; ?>">
				<th scope="row">
					<a href="<?php echo esc_url( $page_url ); ?>"><?php echo esc_html( $page_title ); ?></a>
					<?php
					// Add edit link for WordPress admin.
					$edit_link = get_edit_post_link( $page_id );
					if ( $edit_link ) {
						echo '<a href="' . esc_url( $edit_link ) . '" class="edit-link" title="' . esc_attr__( 'Edit this content', 'content-audit' ) . '"><span class="dashicons dashicons-edit"></span></a>';
					}
					?>
				</th>
				<td>
				<?php echo esc_html( $stakeholder_name ); ?>
				</td>
				<td>
				<?php echo esc_html( $stakeholder_dept ); ?>
				</td>
				<td>
					<div class="flex">
					<form action="" method="post">
						<input type="submit" value="Send Email" class="button <?php echo $is_overdue ? 'button-red' : 'button-primary'; ?>">
						<input type="hidden" name="<?php echo esc_attr( $unique_id ); ?>" value="1">
						<input type="hidden" name="send_email_nonce" value="<?php echo esc_attr( $nonce ); ?>">
						<input type="hidden" name="page_id" value="<?php echo esc_attr( $page_id ); ?>">
						<input type="hidden" name="content_type" value="<?php echo esc_attr( $content_type ); ?>">
					</form>
					<?php
					if ( isset( $_POST['send_email_nonce'] ) ) {
						$nonce = sanitize_text_field( wp_unslash( $_POST['send_email_nonce'] ) );
						if ( isset( $_POST[ "$unique_id" ] ) && null !== $nonce && wp_verify_nonce( $nonce, 'send_email_nonce' ) ) {
							$to = $stakeholder_email;

							// Get the actual post type from the database.
							$post_obj         = get_post( $page_id );
							$actual_post_type = $post_obj ? $post_obj->post_type : 'page';

							// Use proper translation strings for different content types.
							if ( 'post' === $actual_post_type ) {
								/* translators: %s: post title */
								$subject = sprintf( esc_html__( 'The following post requires your attention: %s', 'content-audit' ), get_the_title() );
							} else {
								/* translators: %s: page title */
								$subject = sprintf( esc_html__( 'The following page requires your attention: %s', 'content-audit' ), get_the_title() );
							}

							// Set up email headers.
							$admin_email = get_option( 'admin_email' );

							// Get email settings.
							$email_settings = content_audit_get_email_settings();
							$from_email     = $email_settings['from_email'];
							$from_name      = $email_settings['from_name'];

							$headers = 'From: ' . $from_name . ' <' . $from_email . '>' . "\r\n" .
								'Reply-To: ' . $email_settings['notification_email'] . "\r\n" .
								'Content-Type: text/html; charset=UTF-8';

							// Email template with form URL.
							$message = content_audit_get_email_template( $page_title, $page_url, $date, $format_out, $page_id );

							// Debug information.
							$debug_info = '';
							if ( current_user_can( 'manage_options' ) ) {
								$debug_info  = '<div style="margin-top: 20px; padding: 10px; background-color: #f8f8f8; border: 1px solid #ddd; font-size: 12px;">';
								$debug_info .= '<p>Debug Info:</p>';
								$debug_info .= '<p>Page ID: ' . $page_id . '</p>';
								$debug_info .= '<p>Content Type: ' . $actual_post_type . '</p>';
								$debug_info .= '<p>Form URL: ' . content_audit_generate_form_url( $page_id ) . '</p>';
								$debug_info .= '</div>';

								$message .= $debug_info;
							}

							wp_mail( $to, $subject, $message, $headers );

							echo '<div class="message">✔</div>';
						}
					}
					?>
					</div>
				</td>
				<td>
				<?php echo esc_html( $last_review_date ); ?>
				</td>
				<td>
				<?php
				if ( $is_overdue ) {
					echo '<span class="warning-icon">⚠</span> <span class="overdue-date">';
					echo esc_html( $date->format( $format_out ) );
					echo '</span>';
				} else {
					echo esc_html( $date->format( $format_out ) );
				}
				?>
				</td>
			</tr>
			<?php

		endwhile;

	elseif ( 'posts' === $content_type ) :
			echo '<tr><td colspan="6">' . esc_html__( 'No posts found matching the selected criteria.', 'content-audit' ) . '</td></tr>';
		else :
			echo '<tr><td colspan="6">' . esc_html__( 'No pages found matching the selected criteria.', 'content-audit' ) . '</td></tr>';
	endif;

		wp_reset_postdata();

		echo '</tbody>';
		echo '</table>';
}

/**
 * Get email template for content audit notifications.
 *
 * @param string   $page_title The page title.
 * @param string   $page_url The page URL.
 * @param DateTime $date The date object.
 * @param string   $format_out The date format.
 * @param int      $page_id The page ID for generating the form URL.
 * @return string The email template.
 */
function content_audit_get_email_template( $page_title, $page_url, $date, $format_out, $page_id = 0 ) {
	// Generate form URL if page ID is provided.
	$form_url = '';
	if ( $page_id && function_exists( 'content_audit_generate_form_url' ) ) {
		$form_url = content_audit_generate_form_url( $page_id );
	}

	// Get the content type (post or page).
	$content_type = 'content';
	if ( $page_id ) {
		$post = get_post( $page_id );
		if ( $post && in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			$content_type = $post->post_type;
		}
	}

	// Create a properly capitalized content type label.
	$content_type_label = ucfirst( $content_type );

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

		<!-- Web Font / @font-face : BEGIN -->
		<!-- NOTE: If web fonts are not required, lines 23 - 41 can be safely removed. -->

		<!-- Desktop Outlook chokes on web font references and defaults to Times New Roman, so we force a safe fallback font. -->
		<!--[if mso]>
								<style>
									* {
										font-family: sans-serif !important;
									}
								</style>
							<![endif]-->

		<!-- All other clients get the webfont reference; some will render the font and others will silently fail to the fallbacks. More on that here: http://stylecampaign.com/blog/2015/02/webfont-support-in-email/ -->
		<!--[if !mso]><!-->
		<!-- insert web font reference, eg: <link href='https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700' rel='stylesheet' type='text/css'> -->
		<!--<![endif]-->

		<!-- Web Font / @font-face : END -->

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
	<!--
							The email background color (#222222) is defined in three places:
							1. body tag: for most email clients
							2. center tag: for Gmail and Inbox mobile apps and web versions of Gmail, GSuite, Inbox, Yahoo, AOL, Libero, Comcast, freenet, Mail.ru, Orange.fr
							3. mso conditional: For Windows 10 Mail
						-->

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

			<!-- Create white space after the desired preview text so email clients don't pull other distracting text into the inbox preview. Extend as necessary. -->
			<!-- Preview Text Spacing Hack : BEGIN -->
			<div
				style='display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all; font-family: sans-serif;'>
				&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
			</div>
			<!-- Preview Text Spacing Hack : END -->

			<!--
								Set the email width. Defined in two places:
								1. max-width for all clients except Desktop Windows Outlook, allowing the email to squish on narrow but never go wider than 600px.
								2. MSO tags for Desktop Windows Outlook enforce a 600px width.
							-->
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
											You have been assigned a $content_type_label to review the content of: $page_title</h1>
										<p style='margin: 0;'>The $content_type_label that needs to be reviewed: <a href='$page_url'>$page_title</a></p>
									</td>
								</tr>
								<tr>
									<td
										style='padding: 20px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;'>
										<h2
											style='margin: 0 0 10px 0; font-family: sans-serif; font-size: 18px; line-height: 22px; color: #333333; font-weight: bold;'>
											Please can review the content on the $content_type_label by:</h2>
										<ul style='padding: 0; margin: 0 0 10px 0; list-style-type: disc;'>
											<li style='margin:0 0 10px 30px;' class='list-item-first'>Click the $content_type_label link above to view the content that needs review.</li>
											<li style='margin:0 0 10px 30px;'>Review the content of the $content_type_label.</li>
											" . ($form_url ? "<li style='margin:0 0 10px 30px; font-weight: bold;'>Complete the review by using this form: <a href='$form_url' style='color: #0073aa; text-decoration: underline; font-weight: bold; background-color: #f0f8ff; padding: 3px 6px; border-radius: 3px;'>Content Review Form</a></li>" : "") . "
										</ul>
										<p style='margin: 0 0 10px 0;'>The content needs to be reviewed by the folowing date: <strong>" . $date->format( $format_out ) . "</strong></p>
										<p style='margin: 0 0 10px 0;'>Kind regards</p>
										<p style='margin: 0;'>Pepper Money UX Team</p>
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

	// Apply filter to allow adding the form URL to the email template.
	$message = apply_filters( 'content_audit_email_template', $message, $page_title, $page_url, $date, $page_id );

	return $message;
}
