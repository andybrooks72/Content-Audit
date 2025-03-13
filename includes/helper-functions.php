<?php
/**
 * Helper functions for Content Audit plugin.
 *
 * @package ContentAudit
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Update the email template function to include form URL.
 *
 * @param string   $page_title The page title.
 * @param string   $page_url The page URL.
 * @param DateTime $date The date object.
 * @param string   $format_out The date format.
 * @param int      $page_id The page ID for generating the form URL.
 * @return string The email template.
 */
function content_audit_get_email_template_with_form( $page_title, $page_url, $date, $format_out, $page_id = 0 ) {
	// Generate form URL if page ID is provided.
	$form_url = '';
	if ( $page_id && function_exists( 'content_audit_generate_form_url' ) ) {
		$form_url = content_audit_generate_form_url( $page_id );
	}

	// Get the original template.
	$message = content_audit_get_email_template( $page_title, $page_url, $date, $format_out );

	// If we have a form URL, add it to the template.
	if ( $form_url ) {
		// Find the list item for reviewing content.
		$review_content_li = '<li style="margin:0 0 10px 30px;">Review the content of the page.</li>';

		// Add the form URL after the review content list item.
		$form_url_li = '<li style="margin:0 0 10px 30px;">Complete the review form: <a href="' . esc_url( $form_url ) . '" style="color: #0073aa; text-decoration: underline;">Content Review Form</a></li>';

		// Replace the content.
		$message = str_replace( $review_content_li, $review_content_li . $form_url_li, $message );
	}

	return $message;
}

/**
 * Hook into the email sending process to use the updated template.
 *
 * @return void
 */
function content_audit_update_email_process() {
	// Override the email template function in audit-panel-updated.php.
	add_filter( 'content_audit_email_template', 'content_audit_filter_email_template', 10, 5 );
}
add_action( 'init', 'content_audit_update_email_process' );

/**
 * Filter the email template to include the form URL.
 *
 * @param string   $message The email message.
 * @param string   $page_title The page title.
 * @param string   $page_url The page URL.
 * @param DateTime $date The date object.
 * @param int      $page_id The page ID.
 * @return string The filtered email message.
 */
function content_audit_filter_email_template( $message, $page_title, $page_url, $date, $page_id ) {
	// Generate form URL if page ID is provided.
	$form_url = '';
	if ( $page_id && function_exists( 'content_audit_generate_form_url' ) ) {
		$form_url = content_audit_generate_form_url( $page_id );
	}

	// If we have a form URL, add it to the template.
	if ( $form_url ) {
		// Find the list item for reviewing content.
		$review_content_li = '<li style="margin:0 0 10px 30px;">Review the content of the page.</li>';

		// Add the form URL after the review content list item.
		$form_url_li = '<li style="margin:0 0 10px 30px;">Complete the review form: <a href="' . esc_url( $form_url ) . '" style="color: #0073aa; text-decoration: underline;">Content Review Form</a></li>';

		// Replace the content.
		$message = str_replace( $review_content_li, $review_content_li . $form_url_li, $message );
	}

	return $message;
}
add_action( 'init', 'content_audit_update_email_process' );

/**
 * Update email content to include form URL.
 *
 * @param array $args Email arguments.
 * @return array Modified email arguments.
 */
function content_audit_update_email_content_with_form( $args ) {
	// Check if this is a content audit email.
	if ( isset( $args['subject'] ) && strpos( $args['subject'], 'requires your attention' ) !== false ) {
		// Extract page ID from the email content or from hidden field.
		$page_id = 0;

		// Try to extract from URL parameter.
		preg_match( '/page-id=(\d+)/', $args['message'], $matches );
		if ( isset( $matches[1] ) ) {
			$page_id = intval( $matches[1] );
		}

		// If we have a page ID, update the email content.
		if ( $page_id ) {
			// Get page details.
			$page_title = get_the_title( $page_id );

			// Get the permalink.
			$permalink = get_permalink( $page_id );

			// Convert content page URLs to use the live site URL.
			$site_url      = site_url();
			$relative_path = str_replace( $site_url, '', $permalink );
			$page_url      = 'https://www.pepper.money' . $relative_path;

			// Get next review date.
			$next_review_date = get_field( 'next_review_date', $page_id );
			if ( empty( $next_review_date ) ) {
				return $args;
			}

			$date       = new DateTime( $next_review_date );
			$format_out = 'F d, Y';

			// Generate new email content with form URL.
			$args['message'] = content_audit_get_email_template_with_form( $page_title, $page_url, $date, $format_out, $page_id );
		}
	}

	return $args;
}
