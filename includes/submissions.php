<?php
/**
 * Content Audit Submissions Page.
 *
 * @package ContentAudit
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Render the Content Audit Submissions admin page.
 *
 * @return void
 */
function content_audit_render_submissions_page() {
	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'content_audit_submissions';

	// Handle CSV export if requested.
	if ( isset( $_POST['export_csv'] ) && '1' === $_POST['export_csv'] ) {
		content_audit_export_submissions_csv( $table_name );
		exit; // Ensure no further output.
	}

	// Get submissions from the database.
	$submissions = array();

	// Check if the table exists before querying.
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery // phpcs:ignoreWordPress.DB.DirectDatabaseQuery.NoCaching
		// Process filter parameters if set.
		$where_clause       = '';
		$where_conditions   = array();
		$filter_stakeholder = '';
		$content_type       = '';

		// Verify nonce for GET requests.
		if ( isset( $_GET['content_audit_filter_nonce'] ) && wp_verify_nonce( sanitize_key( $_GET['content_audit_filter_nonce'] ), 'content_audit_filter' ) ) {
			$filter_stakeholder = isset( $_GET['filter_stakeholder'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_stakeholder'] ) ) : '';
			$content_type       = isset( $_GET['content_type'] ) ? sanitize_text_field( wp_unslash( $_GET['content_type'] ) ) : '';
		}

		// Build where clause based on filters.
		// Add stakeholder filter if provided.
		if ( ! empty( $filter_stakeholder ) ) {
			$where_conditions[] = $wpdb->prepare( 'stakeholder_name LIKE %s', '%' . $wpdb->esc_like( $filter_stakeholder ) . '%' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}

		// Add content type filter if provided.
		if ( ! empty( $content_type ) && in_array( $content_type, array( 'page', 'post' ), true ) ) {
			$where_conditions[] = $wpdb->prepare( 'content_type = %s', $content_type ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}

		// Combine where conditions if any exist.
		if ( ! empty( $where_conditions ) ) {
			$where_clause = ' WHERE ' . implode( ' AND ', $where_conditions );
		}

		// Get all stakeholder names for the filter dropdown.
		$sql          = "SELECT DISTINCT stakeholder_name FROM {$wpdb->prefix}content_audit_submissions ORDER BY stakeholder_name ASC";
		$stakeholders = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		// Get filtered submissions.
		$sql = "SELECT * FROM {$wpdb->prefix}content_audit_submissions";
		if ( ! empty( $where_clause ) ) {
			$sql .= $where_clause;
		}
		$sql        .= ' ORDER BY submission_date DESC';
		$submissions = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
	}

	// Display the submissions page.
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Content Audit Submissions', 'content-audit' ); ?></h1>
		<p><?php esc_html_e( 'View all content review submissions from stakeholders.', 'content-audit' ); ?></p>
		
		<?php if ( ! empty( $submissions ) ) : ?>
			<div class="tablenav top">
				<div class="alignleft actions">
					<!-- Stakeholder filter dropdown -->
					<form method="get" action="" class="content-audit-filter-form" style="display: inline-block; margin-right: 10px;">
						<input type="hidden" name="page" value="content-audit-submissions" />
						<?php wp_nonce_field( 'content_audit_filter', 'content_audit_filter_nonce' ); ?>
						
						<!-- Content Type filter -->
						<select name="content_type" id="filter-content-type" style="margin-right: 5px;">
							<option value=""><?php esc_html_e( 'All Content Types', 'content-audit' ); ?></option>
							<option value="page" <?php selected( $content_type, 'page' ); ?>><?php esc_html_e( 'Pages', 'content-audit' ); ?></option>
							<option value="post" <?php selected( $content_type, 'post' ); ?>><?php esc_html_e( 'Posts', 'content-audit' ); ?></option>
						</select>
						
						<!-- Stakeholder filter -->
						<select name="filter_stakeholder" id="filter-stakeholder">
							<option value=""><?php esc_html_e( 'All Stakeholders', 'content-audit' ); ?></option>
							<?php foreach ( $stakeholders as $stakeholder ) : ?>
								<option value="<?php echo esc_attr( $stakeholder ); ?>" <?php selected( $filter_stakeholder, $stakeholder ); ?>>
									<?php echo esc_html( $stakeholder ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						
						<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'content-audit' ); ?>" />
						<?php if ( ! empty( $filter_stakeholder ) || ! empty( $content_type ) ) : ?>
							<a href="<?php echo esc_url( remove_query_arg( array( 'filter_stakeholder', 'content_type' ) ) ); ?>" class="button">
								<?php esc_html_e( 'Reset', 'content-audit' ); ?>
							</a>
						<?php endif; ?>
					</form>
					
					<form method="post" action="" class="content-audit-export-form" style="display: inline-block;">
						<?php wp_nonce_field( 'content_audit_export_csv', 'content_audit_nonce' ); ?>
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Export to CSV', 'content-audit' ); ?>
						</button>
					</form>
				</div>
				<br class="clear">
			</div>
		<?php endif; ?>
		
		<div id="content-audit-submissions">
			<?php if ( ! empty( $submissions ) ) : ?>
				<table class="wp-list-table widefat fixed striped content-audit-submissions-table">
					<thead>
						<tr>
							<th id="content-audit-submissions-title"><?php esc_html_e( 'Content Title', 'content-audit' ); ?></th>
							<th><?php esc_html_e( 'Content Type', 'content-audit' ); ?></th>
							<th><?php esc_html_e( 'Stakeholder', 'content-audit' ); ?></th>
							<th><?php esc_html_e( 'Department', 'content-audit' ); ?></th>
							<th><?php esc_html_e( 'Submission Date', 'content-audit' ); ?></th>
							<th><?php esc_html_e( 'Needs Changes', 'content-audit' ); ?></th>
							<th><?php esc_html_e( 'Support Ticket', 'content-audit' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $submissions as $submission ) : ?>
							<tr>
								<td>
									<?php
									$content_id    = isset( $submission['content_id'] ) ? $submission['content_id'] : $submission['page_id'];
									$content_title = isset( $submission['content_title'] ) ? $submission['content_title'] : $submission['page_title'];
									$content_type  = isset( $submission['content_type'] ) ? $submission['content_type'] : 'page';
									$edit_link     = get_edit_post_link( $content_id );
									$view_link     = get_permalink( $content_id );

									if ( $edit_link ) {
										echo '<a href="' . esc_url( $edit_link ) . '" target="_blank">' . esc_html( $content_title ) . '</a>';
									} else {
										echo esc_html( $content_title );
									}

									if ( $view_link ) {
										echo ' <a href="' . esc_url( $view_link ) . '" target="_blank"><span class="dashicons dashicons-visibility" title="' . esc_attr__( 'View Content', 'content-audit' ) . '"></span></a>';
									}
									?>
								</td>
								<td><?php echo esc_html( ucfirst( $content_type ) ); ?></td>
								<td><?php echo esc_html( $submission['stakeholder_name'] ); ?></td>
								<td><?php echo esc_html( $submission['stakeholder_department'] ); ?></td>
								<td>
									<?php
									$submission_date = new DateTime( $submission['submission_date'] );
									echo esc_html( $submission_date->format( 'F d, Y H:i' ) );
									?>
								</td>
								<td>
									<?php echo $submission['needs_changes'] ? '<span style="color: #d9042b;">Yes</span>' : '<span style="color: #46b450;">No</span>'; ?>
								</td>
								<td>
									<?php
									if ( ! empty( $submission['support_ticket_url'] ) ) {
										// Get the stored URL.
										$ticket_url = $submission['support_ticket_url'];

										// Skip empty URLs or placeholder URLs.
										if ( 'https://' === $ticket_url ) {
											echo '—';
										} else {
											// Sanitize the URL for output.
											$ticket_url = esc_url( $ticket_url );

											// Only output if we have a valid URL.
											if ( ! empty( $ticket_url ) && 'https://' !== $ticket_url ) {
												echo '<a href="' . esc_url( $ticket_url ) . '" target="_blank">' . esc_html__( 'View Ticket', 'content-audit' ) . '</a>';
											} else {
												echo '—';
											}
										}
									} else {
										echo '—';
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="notice notice-info inline" style="margin: 15px 0; padding: 10px 12px;">
					<p>
						<?php
						if ( ! empty( $content_type ) ) {
							// Show message specific to the selected content type.
							printf(
								/* translators: %s: Content type (Page or Post) */
								esc_html__( 'No submissions found for content type: %s', 'content-audit' ),
								'<strong>' . esc_html( ucfirst( $content_type ) ) . '</strong>'
							);
						} else {
							// Generic message when no filters are applied.
							esc_html_e( 'No submissions found.', 'content-audit' );
						}
						?>
					</p>
					
					<div class="content-audit-no-results-options">
						<p><?php esc_html_e( 'You can:', 'content-audit' ); ?></p>
						<ul style="list-style: disc; margin-left: 20px;">
							<?php if ( ! empty( $content_type ) ) : ?>
								<li>
									<a href="
									<?php
									echo esc_url(
										add_query_arg(
											array(
												'page' => 'content-audit-submissions',
												'content_type' => ( 'page' === $content_type ? 'post' : 'page' ),
											)
										)
									);
									?>
												">
										<?php
										printf(
											/* translators: %s: Other content type (Page or Post) */
											esc_html__( 'View %s submissions instead', 'content-audit' ),
											'<strong>' . esc_html( ucfirst( 'page' === $content_type ? 'post' : 'page' ) ) . '</strong>'
										);
										?>
									</a>
								</li>
								<li>
									<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'content-audit-submissions' ), remove_query_arg( array( 'content_type', 'filter_stakeholder' ) ) ) ); ?>">
										<?php esc_html_e( 'View all content types', 'content-audit' ); ?>
									</a>
								</li>
							<?php endif; ?>
							
							<?php if ( ! empty( $filter_stakeholder ) ) : ?>
								<li>
									<a href="
									<?php
									echo esc_url(
										add_query_arg(
											array(
												'page' => 'content-audit-submissions',
												'content_type' => $content_type,
											),
											remove_query_arg( 'filter_stakeholder' )
										)
									);
									?>
												">
										<?php esc_html_e( 'Clear stakeholder filter', 'content-audit' ); ?>
									</a>
								</li>
							<?php endif; ?>
							
							<li>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=content-audit' ) ); ?>">
									<?php esc_html_e( 'Go to Content Audit dashboard', 'content-audit' ); ?>
								</a>
							</li>
						</ul>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Register AJAX handlers for CSV export.
 */
function content_audit_register_ajax_handlers() {
	add_action( 'wp_ajax_content_audit_export_csv', 'content_audit_ajax_export_csv' );
}
add_action( 'admin_init', 'content_audit_register_ajax_handlers' );

/**
 * AJAX handler for CSV export.
 */
function content_audit_ajax_export_csv() {
	// Check nonce for security.
	check_ajax_referer( 'content_audit_csv_nonce', 'nonce' );

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized access' );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'content_audit_submissions';

	// Check if the table exists before querying.
	if ( $table_name !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		wp_die( 'Table does not exist' );
	}

	// Process filter parameters if set.
	$where_clause       = '';
	$filter_stakeholder = isset( $_POST['filter_stakeholder'] ) ? sanitize_text_field( wp_unslash( $_POST['filter_stakeholder'] ) ) : '';
	$content_type       = isset( $_POST['content_type'] ) ? sanitize_text_field( wp_unslash( $_POST['content_type'] ) ) : '';

	// Add stakeholder filter if provided.
	if ( ! empty( $filter_stakeholder ) ) {
		$where_clause = $wpdb->prepare( ' WHERE stakeholder_name LIKE %s', '%' . $wpdb->esc_like( $filter_stakeholder ) . '%' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	// Add content type filter if provided.
	if ( ! empty( $content_type ) && in_array( $content_type, array( 'page', 'post' ), true ) ) {
		if ( empty( $where_clause ) ) {
			$where_clause = $wpdb->prepare( ' WHERE content_type = %s', $content_type ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		} else {
			$where_clause .= $wpdb->prepare( ' AND content_type = %s', $content_type ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
	}

	// Get submissions from the database.
	$submissions = $wpdb->get_results(
		$wpdb->prepare( 'SELECT * FROM %s %s ORDER BY submission_date DESC', $table_name, $where_clause ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		ARRAY_A
	);

	if ( empty( $submissions ) ) {
		wp_die( 'No submissions found' );
	}

	// Clear any previous output.
	if ( ob_get_length() ) {
		ob_clean();
	}

	// Set headers for CSV download.
	header( 'Content-Type: text/csv' );
	header( 'Content-Disposition: attachment; filename="content-audit-submissions-' . gmdate( 'Y-m-d' ) . '.csv"' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Pragma: public' );

	// Create a file pointer connected to the output stream.
	$output = fopen( 'php://output', 'w' );

	// Add UTF-8 BOM for Excel compatibility.
	fputs( $output, "\xEF\xBB\xBF" );

	// Set up the column headers.
	$headers = array(
		'ID',
		'Content Title',
		'Content URL',
		'Content Type',
		'Stakeholder',
		'Department',
		'Submission Date',
		'Needs Changes',
		'Support Ticket',
	);

	// Output the column headers.
	fputcsv( $output, $headers );

	// Output each row of data.
	foreach ( $submissions as $submission ) {
		// Format dates for better readability.
		$submission_date = new DateTime( $submission['submission_date'] );

		// Handle the next_review_date format.
		$next_review_date      = $submission['next_review_date'];
		$next_review_formatted = '';

		// Check if the date is already in a formatted string.
		if ( strtotime( $next_review_date ) === false ) {
			// If we can't parse it directly, use it as is.
			$next_review_formatted = $next_review_date;
		} else {
			// We can parse it, so format it.
			$next_review           = new DateTime( $next_review_date );
			$next_review_formatted = $next_review->format( 'F d, Y' );
		}

		// Get content ID for permalink.
		$content_id = isset( $submission['content_id'] ) ? $submission['content_id'] : $submission['page_id'];

		// Get the content URL.
		$content_url = '';
		if ( ! empty( $content_id ) ) {
			// Get the relative path from the permalink.
			$permalink = get_permalink( $content_id );
			if ( $permalink ) {
				$site_url      = site_url();
				$relative_path = str_replace( $site_url, '', $permalink );
				// Create the live site URL.
				$content_url = 'https://www.pepper.money' . $relative_path;
			}
		}

		$row = array(
			$submission['id'],
			$submission['content_title'],
			$content_url,
			isset( $submission['content_type'] ) ? $submission['content_type'] : 'page',
			$submission['stakeholder_name'],
			$submission['stakeholder_department'],
			$submission_date->format( 'F d, Y H:i' ),
			$submission['needs_changes'] ? 'Yes' : 'No',
			empty( $submission['support_ticket_url'] ) ? 'N/A' : $submission['support_ticket_url'],
		);

		fputcsv( $output, $row );
	}

	// Close the file pointer.
	fclose( $output );

	// Stop execution to prevent any additional output.
	wp_die();
}

/**
 * Enqueue scripts for the submissions page.
 */
function content_audit_enqueue_submissions_scripts() {
	// Only enqueue on the submissions page.
	$screen = get_current_screen();
	if ( isset( $screen->id ) && 'content-audit_page_content-audit-submissions' === $screen->id ) {
		wp_enqueue_script(
			'content-audit-submissions',
			plugins_url( '/assets/js/submissions.js', __DIR__ ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/submissions.js' ),
			true
		);

		// Pass data to the script.
		wp_localize_script(
			'content-audit-submissions',
			'contentAuditData',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'content_audit_csv_nonce' ),
				'filterStakeholder' => isset( $_GET['filter_stakeholder'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_stakeholder'] ) ) : '',
				'contentType'       => isset( $_GET['content_type'] ) ? sanitize_text_field( wp_unslash( $_GET['content_type'] ) ) : '',
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'content_audit_enqueue_submissions_scripts' );

/**
 * Export submissions to CSV.
 *
 * @param string $table_name The database table name.
 * @return void
 */
function content_audit_export_submissions_csv( $table_name ) {
	global $wpdb;

	// Check if the table exists before querying.
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return;
	}

	// Process filter parameters if set.
	$where_clause       = '';
	$filter_stakeholder = isset( $_POST['filter_stakeholder'] ) && check_admin_referer( 'content_audit_export_csv', 'content_audit_nonce' ) ? sanitize_text_field( wp_unslash( $_POST['filter_stakeholder'] ) ) : '';
	$content_type       = isset( $_POST['content_type'] ) && check_admin_referer( 'content_audit_export_csv', 'content_audit_nonce' ) ? sanitize_text_field( wp_unslash( $_POST['content_type'] ) ) : '';

	// Add stakeholder filter if provided.
	if ( ! empty( $filter_stakeholder ) ) {
		$where_clause = $wpdb->prepare( ' WHERE stakeholder_name LIKE %s', '%' . $wpdb->esc_like( $filter_stakeholder ) . '%' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	// Add content type filter if provided.
	if ( ! empty( $content_type ) && in_array( $content_type, array( 'page', 'post' ), true ) ) {
		if ( empty( $where_clause ) ) {
			$where_clause = $wpdb->prepare( ' WHERE content_type = %s', $content_type ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		} else {
			$where_clause .= $wpdb->prepare( ' AND content_type = %s', $content_type ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
	}

	// Get submissions from the database.
	$submissions = $wpdb->get_results(
		$wpdb->prepare( 'SELECT * FROM %s %s ORDER BY submission_date DESC', $table_name, $where_clause ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		ARRAY_A
	);

	if ( empty( $submissions ) ) {
		return;
	}

	// Create a temporary file.
	$upload_dir = wp_upload_dir();
	$temp_file  = $upload_dir['basedir'] . '/content-audit-submissions-' . gmdate( 'Y-m-d' ) . '-' . uniqid() . '.csv';

	// Open the file for writing.
	$file = fopen( $temp_file, 'w' );

	// Add UTF-8 BOM for Excel compatibility.
	fputs( $file, "\xEF\xBB\xBF" );

	// Set up the column headers.
	$headers = array(
		'ID',
		'Content Title',
		'Content URL',
		'Content Type',
		'Stakeholder',
		'Department',
		'Submission Date',
		'Needs Changes',
		'Support Ticket',
	);

	// Output the column headers.
	fputcsv( $file, $headers );

	// Output each row of data.
	foreach ( $submissions as $submission ) {
		// Format dates for better readability.
		$submission_date = new DateTime( $submission['submission_date'] );

		// Handle the next_review_date format.
		$next_review_date      = $submission['next_review_date'];
		$next_review_formatted = '';

		// Check if the date is already in a formatted string.
		if ( strtotime( $next_review_date ) === false ) {
			// If we can't parse it directly, use it as is.
			$next_review_formatted = $next_review_date;
		} else {
			// We can parse it, so format it.
			$next_review           = new DateTime( $next_review_date );
			$next_review_formatted = $next_review->format( 'F d, Y' );
		}

		// Get content ID for permalink.
		$content_id = isset( $submission['content_id'] ) ? $submission['content_id'] : $submission['page_id'];

		// Get the content URL.
		$content_url = '';
		if ( ! empty( $content_id ) ) {
			// Get the relative path from the permalink.
			$permalink = get_permalink( $content_id );
			if ( $permalink ) {
				$site_url      = site_url();
				$relative_path = str_replace( $site_url, '', $permalink );
				// Create the live site URL.
				$content_url = 'https://www.pepper.money' . $relative_path;
			}
		}

		$row = array(
			$submission['id'],
			$submission['content_title'],
			$content_url,
			isset( $submission['content_type'] ) ? $submission['content_type'] : 'page',
			$submission['stakeholder_name'],
			$submission['stakeholder_department'],
			$submission_date->format( 'F d, Y H:i' ),
			$submission['needs_changes'] ? 'Yes' : 'No',
			empty( $submission['support_ticket_url'] ) ? 'N/A' : $submission['support_ticket_url'],
		);

		fputcsv( $file, $row );
	}

	// Close the file.
	fclose( $file );

	// Set a transient with the file path for later download.
	set_transient( 'content_audit_csv_download', $temp_file, 60 * 5 ); // 5 minutes expiration

	// Redirect to the same page with a download parameter.
	wp_safe_redirect( add_query_arg( 'download_csv', '1', remove_query_arg( 'export_csv' ) ) );
	exit;
}

/**
 * Handle CSV download from a temporary file.
 */
function content_audit_handle_csv_download() {
	if ( isset( $_GET['download_csv'] ) && '1' === $_GET['download_csv'] && check_admin_referer( 'content_audit_export_csv', 'content_audit_nonce' ) ) {
		// Get the file path from the transient.
		$file_path = get_transient( 'content_audit_csv_download' );

		if ( $file_path && file_exists( $file_path ) ) {
			// Get the filename from the path.
			$filename = basename( $file_path );

			// Set headers for download.
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: text/csv' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . filesize( $file_path ) );

			// Clear output buffer.
			if ( ob_get_length() ) {
				ob_clean();
			}
			flush();

			// Read the file and output it to the browser.
			readfile( $file_path );

			// Delete the temporary file.
			unlink( $file_path );

			// Delete the transient.
			delete_transient( 'content_audit_csv_download' );

			exit;
		}
	}
}
add_action( 'admin_init', 'content_audit_handle_csv_download' );
