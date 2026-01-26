<?php
/**
 * Email template for content review assignment notifications.
 *
 * This template can be overridden by copying it to your theme:
 * your-theme/pm-content-audit/email/backend-submission-notification.php
 *
 * @package ContentAudit
 *
 * Available variables:
 * @var string $page_title         The title of the content to review.
 * @var string $page_url           The URL of the content to review.
 * @var string $content_type_label The label for the content type (Post, Page, etc.).
 * @var string $form_url           The URL to the content review form.
 * @var string $support_ticket_url The support ticket URL (optional, may be empty).
 * @var string $review_date        The date by which the content needs to be reviewed (formatted).
 * @var string $header_image       The header image URL for the email.
 * @var string $from_name          The from name for the email (from plugin settings).
 * @var string $button_bg_color    The button background color (from plugin settings).
 * @var string $button_text_color  The button text color (from plugin settings).
 * @var string $link_text_color    The link text color (from plugin settings).
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Set default colors if not provided.
$button_bg_color   = isset( $button_bg_color ) ? $button_bg_color : '#d9042b';
$button_text_color  = isset( $button_text_color ) ? $button_text_color : '#ffffff';
$link_text_color    = isset( $link_text_color ) ? $link_text_color : '#0073aa';
?>
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
			&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
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
						<?php if ( ! empty( $header_image ) ) : ?>
							<img src='<?php echo esc_url( $header_image ); ?>'
								style='border:0;display:block;outline:none;text-decoration:none;height:auto;width:100%;font-size:13px;'
								width='200' height='auto' alt='<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>' />
						<?php else : ?>
							<h1 style='margin: 0 0 10px 0; font-family: sans-serif; font-size: 28px; line-height: 32px; color: #333333; font-weight: bold;'>
								<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
							</h1>
							<?php if ( get_bloginfo( 'description' ) ) : ?>
								<p style='margin: 0; font-family: sans-serif; font-size: 16px; line-height: 20px; color: #666666;'>
									<?php echo esc_html( get_bloginfo( 'description' ) ); ?>
								</p>
							<?php endif; ?>
						<?php endif; ?>
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
										style='margin: 0 0 10px 0; font-family: sans-serif; font-size: 22px; line-height: 30px; color: #333333; font-weight: normal;'>
										<?php
										/* translators: %1$s: Content type label, %2$s: Content title */
										echo sprintf(
											esc_html__( 'You have been assigned a %1$s to review: %2$s', 'peppermoney-content-audit' ),
											esc_html( strtolower( $content_type_label ) ),
											'<a href="' . esc_url( $page_url ) . '">' . esc_html( $page_title ) . '</a>'
										);
										?>
									</h1>
									<p style='margin: 0 0 10px 0;'><?php esc_html_e( 'Please click on the link above to check if the post is still up to date or if it needs editing or removing', 'peppermoney-content-audit' ); ?></p>
									<p style='margin: 0 0 10px 0;'>
										<?php
										/* translators: %s: Email address */
										echo sprintf(
											esc_html__( 'If you have any issues accessing the link, email the UX team on %s or contact a member of the team', 'peppermoney-content-audit' ),
											'<a href="mailto:ux@pepper.money">ux@pepper.money</a>'
										);
										?>
									</p>
								</td>
							</tr>
							<tr>
								<td
									style='padding: 20px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;'>
									<p style='margin: 0 0 10px 0;'><?php esc_html_e( 'Once you\'ve finished your review', 'peppermoney-content-audit' ); ?></p>
									<ul style='padding: 0; margin: 0 0 10px 0; list-style-type: disc;'>
										<li style='margin:0 0 10px 20px;' class='list-item-first'>
											<?php
											if ( ! empty( $form_url ) ) {
												/* translators: %s: Form URL */
												echo sprintf(
													esc_html__( 'If the post is still up to date confirm this by completing the %s.', 'peppermoney-content-audit' ),
													'<a href="' . esc_url( $form_url ) . '" style="color: ' . esc_attr( $link_text_color ) . '; text-decoration: underline; font-weight: bold; background-color: #f0f8ff; padding: 3px 6px; border-radius: 3px;">' . esc_html__( 'Content Review Form', 'peppermoney-content-audit' ) . '</a>'
												);
											} else {
												esc_html_e( 'If the post is still up to date confirm this by completing the Content Review Form.', 'peppermoney-content-audit' );
											}
											?>
										</li>
										<?php if ( ! empty( $form_url ) && ! empty( $support_ticket_url ) ) : ?>
										<li style='margin:0 0 10px 20px;'>
											<?php
											/* translators: %1$s: Support ticket URL, %2$s: Form URL */
											echo sprintf(
												esc_html__( 'If the post needs editing or removing raise an %1$s detailing the change before completing the %2$s. Please note you will need to include your Support ticket number on the form.', 'peppermoney-content-audit' ),
												'<a href="' . esc_url( $support_ticket_url ) . '" style="color: ' . esc_attr( $link_text_color ) . '; text-decoration: underline; font-weight: bold; background-color: #f0f8ff; padding: 3px 6px; border-radius: 3px;">' . esc_html__( 'Support ticket', 'peppermoney-content-audit' ) . '</a>',
												'<a href="' . esc_url( $form_url ) . '" style="color: ' . esc_attr( $link_text_color ) . '; text-decoration: underline; font-weight: bold; background-color: #f0f8ff; padding: 3px 6px; border-radius: 3px;">' . esc_html__( 'Content Review Form', 'peppermoney-content-audit' ) . '</a>'
											);
											?>
										</li>
										<?php endif; ?>
									</ul>
									<p style='margin: 0 0 10px 0;'>
										<?php
										/* translators: %s: Review date */
										echo sprintf(
											esc_html__( 'The content needs to be reviewed by: %s', 'peppermoney-content-audit' ),
											'<strong>' . esc_html( $review_date ) . '</strong>'
										);
										?>
									</p>
									<p style='margin: 0 0 10px 0;'><?php esc_html_e( 'Kind regards', 'peppermoney-content-audit' ); ?></p>
									<p style='margin: 0;'><?php echo esc_html( $from_name ); ?></p>
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
