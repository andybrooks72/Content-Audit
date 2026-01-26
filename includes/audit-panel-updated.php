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

// Include settings functions if not already included.
if ( ! function_exists( 'content_audit_get_post_types_settings' ) ) {
	require_once __DIR__ . '/admin/settings.php';
}
if ( ! function_exists( 'content_audit_get_display_settings' ) ) {
	require_once __DIR__ . '/admin/settings.php';
}
// Include helper functions if not already included.
if ( ! function_exists( 'content_audit_load_email_template' ) ) {
	require_once __DIR__ . '/helper-functions.php';
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

	// Get selected post types from settings.
	$post_types_settings = content_audit_get_post_types_settings();
	$selected_post_types = isset( $post_types_settings['post_types'] ) && is_array( $post_types_settings['post_types'] )
		? $post_types_settings['post_types']
		: array( 'page', 'post' );

	// Get content type from URL parameter, default to first selected post type.
	$content_type = isset( $_GET['content_type'] ) ? sanitize_text_field( wp_unslash( $_GET['content_type'] ) ) : $selected_post_types[0];

	// Ensure content_type is valid (must be one of the selected post types).
	if ( ! in_array( $content_type, $selected_post_types, true ) ) {
		$content_type = $selected_post_types[0];
	}

	// Get post type objects for labels.
	$post_type_objects = array();
	foreach ( $selected_post_types as $post_type_slug ) {
		$post_type_obj = get_post_type_object( $post_type_slug );
		if ( $post_type_obj ) {
			$post_type_objects[ $post_type_slug ] = $post_type_obj;
		}
	}

	// Add tabs for switching between selected post types.
	?>
	<div class="nav-tab-wrapper">
		<?php foreach ( $selected_post_types as $post_type_slug ) : ?>
			<?php
			$post_type_obj = isset( $post_type_objects[ $post_type_slug ] ) ? $post_type_objects[ $post_type_slug ] : null;
			if ( ! $post_type_obj ) {
				continue;
			}
			?>
			<a href="
			<?php
			echo esc_url(
				add_query_arg(
					array(
						'page'         => 'content-audit',
						'content_type' => $post_type_slug,
						'filter'       => $filter,
					)
				)
			);
			?>
						" class="nav-tab <?php echo $content_type === $post_type_slug ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $post_type_obj->labels->name ); ?>
			</a>
		<?php endforeach; ?>
	</div>

	<!-- Add filter dropdown -->
	<div class="tablenav top">
		<div class="alignleft actions">
			<form method="get">
				<input type="hidden" name="page" value="content-audit">
				<input type="hidden" name="content_type" value="<?php echo esc_attr( $content_type ); ?>">
				<select name="filter">
					<option value="30days" <?php selected( $filter, '30days' ); ?>><?php esc_html_e( 'Next 30 Days & Overdue', 'peppermoney-content-audit' ); ?></option>
					<option value="overdue" <?php selected( $filter, 'overdue' ); ?>><?php esc_html_e( 'Overdue Only', 'peppermoney-content-audit' ); ?></option>
					<option value="3months" <?php selected( $filter, '3months' ); ?>><?php esc_html_e( 'Next 3 Months', 'peppermoney-content-audit' ); ?></option>
					<option value="6months" <?php selected( $filter, '6months' ); ?>><?php esc_html_e( 'Next 6 Months', 'peppermoney-content-audit' ); ?></option>
					<option value="12months" <?php selected( $filter, '12months' ); ?>><?php esc_html_e( 'Next 12 Months', 'peppermoney-content-audit' ); ?></option>
					<option value="all" <?php selected( $filter, 'all' ); ?>><?php esc_html_e( 'All Content', 'peppermoney-content-audit' ); ?></option>
				</select>
				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'peppermoney-content-audit' ); ?>">
			</form>
		</div>
		<br class="clear">
	</div>
	<?php

	// Get post type object for current content type.
	$current_post_type_obj = get_post_type_object( $content_type );
	$content_type_label    = $current_post_type_obj ? $current_post_type_obj->labels->singular_name : ucfirst( $content_type );

	echo '<table class="content-audit-table">';
	echo '<thead>';
	echo '<tr>';
	echo '<th>' . esc_html( sprintf( '%s Title', $content_type_label ) ) . '</th>';
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
		'post_type'      => $content_type,
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

			$page_title    = get_the_title();
			$permalink     = get_the_permalink();
			$site_url      = site_url();
			$relative_path = str_replace( $site_url, '', $permalink );

			// Get base URL from settings.
			$display_settings  = content_audit_get_display_settings();
			$base_url          = isset( $display_settings['base_url'] ) ? $display_settings['base_url'] : home_url();
			$page_url          = untrailingslashit( $base_url ) . $relative_path;
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
						echo '<a href="' . esc_url( $edit_link ) . '" class="edit-link" title="' . esc_attr__( 'Edit this content', 'peppermoney-content-audit' ) . '"><span class="dashicons dashicons-edit"></span></a>';
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
							$actual_post_type = $post_obj ? $post_obj->post_type : $content_type;
							$post_type_obj    = get_post_type_object( $actual_post_type );
							$type_label       = $post_type_obj ? $post_type_obj->labels->singular_name : ucfirst( $actual_post_type );

							// Use proper translation strings for different content types.
							/* translators: %1$s: content type label, %2$s: content title */
							$subject = sprintf( esc_html__( 'The following %1$s requires your attention: %2$s', 'peppermoney-content-audit' ), strtolower( $type_label ), get_the_title() );

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

	else :
		// Get post type object for current content type.
		$current_post_type_obj = get_post_type_object( $content_type );
		$content_type_label    = $current_post_type_obj ? $current_post_type_obj->labels->name : ucfirst( $content_type );
		/* translators: %s: content type label */
		echo '<tr><td colspan="6">' . esc_html( sprintf( __( 'No %s found matching the selected criteria.', 'peppermoney-content-audit' ), strtolower( $content_type_label ) ) ) . '</td></tr>';
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

	// Get support ticket URL from settings.
	$display_settings   = content_audit_get_display_settings();
	$support_ticket_url = isset( $display_settings['support_ticket_url'] ) ? $display_settings['support_ticket_url'] : '';

	// Get the content type from the post.
	$content_type = 'content';
	if ( $page_id ) {
		$post = get_post( $page_id );
		if ( $post ) {
			$content_type = $post->post_type;
		}
	}

	// Get post type object for proper label.
	$post_type_obj      = get_post_type_object( $content_type );
	$content_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : ucfirst( $content_type );

	// Get email template settings for header image.
	$email_template_settings = content_audit_get_email_template_settings();
	$header_image           = isset( $email_template_settings['header_image'] ) && ! empty( $email_template_settings['header_image'] ) 
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

	// Format the review date.
	$review_date = $date->format( $format_out );

	// Prepare template variables.
	$template_args = array(
		'page_title'         => $page_title,
		'page_url'           => $page_url,
		'content_type_label' => $content_type_label,
		'form_url'           => $form_url,
		'support_ticket_url' => $support_ticket_url,
		'review_date'        => $review_date,
		'header_image'       => $header_image,
		'from_name'          => $from_name,
		'button_bg_color'    => $button_bg_color,
		'button_text_color'  => $button_text_color,
		'link_text_color'    => $link_text_color,
	);

	// Load email template.
	$message = content_audit_load_email_template( 'backend-submission-notification', $template_args );

	// Apply filter to allow adding the form URL to the email template.
	$message = apply_filters( 'content_audit_email_template', $message, $page_title, $page_url, $date, $page_id );

	return $message;
}
