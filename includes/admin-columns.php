<?php
/**
 * Admin Columns for Content Audit.
 *
 * @package ContentAudit
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Include settings functions if not already included.
if ( ! function_exists( 'content_audit_get_display_settings' ) ) {
	require_once __DIR__ . '/settings.php';
}

/**
 * Check if admin columns should be displayed.
 *
 * @return bool Whether admin columns should be displayed.
 */
function content_audit_should_display_admin_columns() {
	// Get display settings.
	$display_settings = content_audit_get_display_settings();

	// Check if admin columns should be displayed.
	return isset( $display_settings['show_admin_columns'] ) && 'yes' === $display_settings['show_admin_columns'];
}

/**
 * Add custom columns to post and page admin screens.
 *
 * @param array $columns Array of column names.
 * @return array Modified array of column names.
 */
function content_audit_add_columns( $columns ) {
	// Check if admin columns should be displayed.
	if ( ! content_audit_should_display_admin_columns() ) {
		return $columns;
	}

	// Create a new array with all columns up to 'date'.
	$new_columns = array();

	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;

		// Add our column after the 'Modified' column.
		if ( 'Modified' === $key ) {
			$new_columns['last_review_date'] = esc_html__( 'Last Review Date', 'content-audit' );
			$new_columns['next_review_date'] = esc_html__( 'Next Review Date', 'content-audit' );
			$new_columns['stakeholder_name'] = esc_html__( 'Stakeholder', 'content-audit' );
		}
	}

	return $new_columns;
}

add_filter( 'manage_post_posts_columns', 'content_audit_add_columns' );
add_filter( 'manage_page_posts_columns', 'content_audit_add_columns' );

/**
 * Display custom column content.
 *
 * @param string $column_name Column name.
 * @param int    $post_id Post ID.
 * @return void
 */
function content_audit_custom_column_content( $column_name, $post_id ) {
	// Check if admin columns should be displayed.
	if ( ! content_audit_should_display_admin_columns() ) {
		return;
	}

	if ( 'next_review_date' === $column_name ) {
		// Get the next review date from ACF.
		$next_review_date = get_field( 'next_review_date', $post_id );

		if ( $next_review_date ) {
			// Convert the date string to a DateTime object.
			// ACF returns the date in format 'F j, Y' (e.g., "March 15, 2025").
			$date_obj = DateTime::createFromFormat( 'F j, Y', $next_review_date );

			// If parsing fails, try a fallback method.
			if ( false === $date_obj ) {
				$date_obj = new DateTime( $next_review_date );
			}

			$today = new DateTime( 'now' );
			$today->setTime( 0, 0, 0 ); // Set time to beginning of day.

			$format_out = 'F j, Y';
			$is_overdue = $date_obj < $today;

			// Display the date with overdue styling if needed.
			if ( $is_overdue ) {
				echo '<span class="content-audit-overdue">';
				echo esc_html( $date_obj->format( $format_out ) );
				echo ' ⚠</span>';
			} else {
				echo esc_html( $date_obj->format( $format_out ) );
			}
		} else {
			echo '<span class="content-audit-not-set">—</span>';
		}
	} elseif ( 'last_review_date' === $column_name ) {
		$last_review_date = get_field( 'last_review_date', $post_id );

		if ( $last_review_date ) {
			// Convert the date string to a DateTime object.
			// ACF returns the date in format 'F j, Y' (e.g., "March 15, 2025").
			$date_obj = DateTime::createFromFormat( 'F j, Y', $last_review_date );

			// If parsing fails, try a fallback method.
			if ( false === $date_obj ) {
				$date_obj = new DateTime( $last_review_date );
			}

			$format_out = 'F j, Y';
			echo esc_html( $date_obj->format( $format_out ) );
		} else {
			echo '<span class="content-audit-not-set">—</span>';
		}
	} elseif ( 'stakeholder_name' === $column_name ) {
		$stakeholder_name = get_field( 'stakeholder_name', $post_id );

		if ( $stakeholder_name ) {
			echo esc_html( $stakeholder_name );
		} else {
			echo '<span class="content-audit-not-set">—</span>';
		}
	}
}

add_action( 'manage_posts_custom_column', 'content_audit_custom_column_content', 10, 2 );
add_action( 'manage_pages_custom_column', 'content_audit_custom_column_content', 10, 2 );

/**
 * Make the Next Audit Date column sortable.
 *
 * @param array $columns Array of sortable columns.
 * @return array Modified array of sortable columns.
 */
function content_audit_sortable_columns( $columns ) {
	// Check if admin columns should be displayed.
	if ( ! content_audit_should_display_admin_columns() ) {
		return $columns;
	}

	$columns['next_review_date'] = 'next_review_date';
	$columns['stakeholder_name'] = 'stakeholder_name';
	$columns['last_review_date'] = 'last_review_date';

	return $columns;
}

add_filter( 'manage_edit-post_sortable_columns', 'content_audit_sortable_columns' );
add_filter( 'manage_edit-page_sortable_columns', 'content_audit_sortable_columns' );

/**
 * Modify the query to sort by the next audit date.
 *
 * @param WP_Query $query The WordPress query object.
 * @return void
 */
function content_audit_sort_by_audit_date( $query ) {
	// Check if admin columns should be displayed.
	if ( ! content_audit_should_display_admin_columns() ) {
		return;
	}

	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || ! in_array( $screen->base, array( 'edit', 'edit-tags' ), true ) ) {
		return;
	}

	$orderby = $query->get( 'orderby' );

	if ( 'next_review_date' === $orderby ) {
		$query->set( 'meta_key', 'next_review_date' );
		$query->set( 'orderby', 'meta_value' );

		// Based on previous fixes, ensure we're using the correct date format for sorting.
		// ACF stores dates in 'Y-m-d' format in the database.
		$query->set( 'meta_type', 'DATE' );
	} elseif ( 'stakeholder_name' === $orderby ) {
		$query->set( 'meta_key', 'stakeholder_name' );
		$query->set( 'orderby', 'meta_value' );
	} elseif ( 'last_review_date' === $orderby ) {
		$query->set( 'meta_key', 'last_review_date' );
		$query->set( 'orderby', 'meta_value' );

		// Based on previous fixes, ensure we're using the correct date format for sorting.
		// ACF stores dates in 'Y-m-d' format in the database.
		$query->set( 'meta_type', 'DATE' );
	}
}

add_action( 'pre_get_posts', 'content_audit_sort_by_audit_date' );
