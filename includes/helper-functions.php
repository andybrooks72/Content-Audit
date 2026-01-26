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
 * Locate email template file.
 *
 * Checks theme directory first, then falls back to plugin template.
 * Theme template path: your-theme/pm-content-audit/email/{template-name}.php
 * Plugin template path: templates/email/{template-name}.php
 *
 * @param string $template_name The template name (without .php extension).
 * @return string|false The template path if found, false otherwise.
 */
function content_audit_locate_email_template( $template_name ) {
	// Check theme directory first.
	$theme_template = get_template_directory() . '/pm-content-audit/email/' . $template_name . '.php';
	if ( file_exists( $theme_template ) ) {
		return $theme_template;
	}

	// Check child theme directory if using child theme.
	if ( is_child_theme() ) {
		$child_theme_template = get_stylesheet_directory() . '/pm-content-audit/email/' . $template_name . '.php';
		if ( file_exists( $child_theme_template ) ) {
			return $child_theme_template;
		}
	}

	// Fall back to plugin template.
	$plugin_template = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/email/' . $template_name . '.php';
	if ( file_exists( $plugin_template ) ) {
		return $plugin_template;
	}

	return false;
}

/**
 * Load email template with variables.
 *
 * @param string $template_name The template name (without .php extension).
 * @param array  $args           Array of variables to pass to the template.
 * @return string The rendered template output.
 */
function content_audit_load_email_template( $template_name, $args = array() ) {
	$template_path = content_audit_locate_email_template( $template_name );

	if ( ! $template_path ) {
		return '';
	}

	// Extract variables for use in template.
	extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

	// Start output buffering.
	ob_start();

	// Include the template file.
	include $template_path;

	// Get the buffered content.
	$output = ob_get_clean();

	return $output;
}

/**
 * Update the email template function to include form URL.
 *
 * @param string   $content_title The content title.
 * @param string   $content_url The content URL.
 * @param DateTime $date The date object.
 * @param string   $format_out The date format.
 * @param int      $content_id The content ID for generating the form URL.
 * @return string The email template.
 */
function content_audit_get_email_template_with_form( $content_title, $content_url, $date, $format_out, $content_id = 0 ) {
	// Generate form URL if content ID is provided.
	$form_url = '';
	if ( $content_id && function_exists( 'content_audit_generate_form_url' ) ) {
		$form_url = content_audit_generate_form_url( $content_id );
	}

	// Get the content type from the post.
	$content_type = 'content';
	if ( $content_id ) {
		$post = get_post( $content_id );
		if ( $post ) {
			$content_type = $post->post_type;
		}
	}

	// Get post type object for proper label.
	$post_type_obj      = get_post_type_object( $content_type );
	$content_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : ucfirst( $content_type );

	// Get the original template.
	$message = content_audit_get_email_template( $content_title, $content_url, $date, $format_out );

	// If we have a form URL, add it to the template.
	if ( $form_url ) {
		// Find the list item for reviewing content.
		$review_content_li = '<li style="margin:0 0 10px 30px;">Review the content of the ' . $content_type . '.</li>';

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
 * @param string   $content_title The content title.
 * @param string   $content_url The content URL.
 * @param DateTime $date The date object.
 * @param int      $content_id The content ID.
 * @return string The filtered email message.
 */
function content_audit_filter_email_template( $message, $content_title, $content_url, $date, $content_id ) {
	// Generate form URL if content ID is provided.
	$form_url = '';
	if ( $content_id && function_exists( 'content_audit_generate_form_url' ) ) {
		$form_url = content_audit_generate_form_url( $content_id );
	}

	// Get the content type from the post.
	$content_type = 'content';
	if ( $content_id ) {
		$post = get_post( $content_id );
		if ( $post ) {
			$content_type = $post->post_type;
		}
	}

	// If we have a form URL, add it to the template.
	if ( $form_url ) {
		// Find the list item for reviewing content.
		$review_content_li = '<li style="margin:0 0 10px 30px;">Review the content of the ' . $content_type . '.</li>';

		// Add the form URL after the review content list item.
		$form_url_li = '<li style="margin:0 0 10px 30px;">Complete the review form: <a href="' . esc_url( $form_url ) . '" style="color: #0073aa; text-decoration: underline;">Content Review Form</a></li>';

		// Replace the content.
		$message = str_replace( $review_content_li, $review_content_li . $form_url_li, $message );
	}

	return $message;
}

/**
 * Update email content to include form URL.
 *
 * @param array $args Email arguments.
 * @return array Modified email arguments.
 */
function content_audit_update_email_content_with_form( $args ) {
	// Check if this is a content audit email.
	if ( isset( $args['subject'] ) && strpos( $args['subject'], 'requires your attention' ) !== false ) {
		// Extract content ID from the email content or from hidden field.
		$content_id = 0;

		// Try to extract from URL parameter.
		preg_match( '/page-id=(\d+)/', $args['message'], $matches );
		if ( isset( $matches[1] ) ) {
			$content_id = intval( $matches[1] );
		}

		// If we have a content ID, update the email content.
		if ( $content_id ) {
			// Get content details.
			$content_title = get_the_title( $content_id );

			// Get the permalink.
			$permalink = get_permalink( $content_id );

			// Convert content URLs to use the live site URL.
			$site_url      = site_url();
			$relative_path = str_replace( $site_url, '', $permalink );
			$content_url   = 'https://www.pepper.money' . $relative_path;

			// Get next review date.
			$next_review_date = get_field( 'next_review_date', $content_id );
			if ( empty( $next_review_date ) ) {
				return $args;
			}

			$date       = new DateTime( $next_review_date );
			$format_out = 'F d, Y';

			// Generate new email content with form URL.
			$args['message'] = content_audit_get_email_template_with_form( $content_title, $content_url, $date, $format_out, $content_id );
		}
	}

	return $args;
}
