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
 * Check if the submissions table exists and create it if it doesn't.
 *
 * @return void
 */
function content_audit_check_submissions_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'content_audit_submissions';

	// Check if the table exists.
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			page_id bigint(20) NOT NULL,
			page_title varchar(255) NOT NULL,
			stakeholder_name varchar(100) NOT NULL,
			stakeholder_email varchar(100) NOT NULL,
			stakeholder_department varchar(100) NOT NULL,
			submission_date datetime NOT NULL,
			needs_changes tinyint(1) NOT NULL DEFAULT 0,
			support_ticket_url varchar(255) DEFAULT '',
			next_review_date varchar(20) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
add_action( 'admin_init', 'content_audit_check_submissions_table' );

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

	// Ensure the submissions table exists.
	content_audit_check_submissions_table();

	global $wpdb;
	$table_name = $wpdb->prefix . 'content_audit_submissions';

	// Handle CSV export if requested.
	if ( isset( $_GET['export_csv'] ) && '1' === $_GET['export_csv'] ) {
		content_audit_export_submissions_csv( $table_name );
		exit; // Ensure no further output.
	}

	// Get submissions from the database.
	$submissions = array();

	// Check if the table exists before querying.
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		// Process filter parameters if set.
		$where_clause = '';
		$filter_stakeholder = isset( $_GET['filter_stakeholder'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_stakeholder'] ) ) : '';
		
		// Add stakeholder filter if provided.
		if ( ! empty( $filter_stakeholder ) ) {
			$where_clause = $wpdb->prepare( " WHERE stakeholder_name LIKE %s", '%' . $wpdb->esc_like( $filter_stakeholder ) . '%' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
		
		// Get all stakeholder names for the filter dropdown.
		$stakeholders = $wpdb->get_col( "SELECT DISTINCT stakeholder_name FROM $table_name ORDER BY stakeholder_name ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		
		// Get filtered submissions.
		$submissions = $wpdb->get_results(
			"SELECT * FROM $table_name" . $where_clause . " ORDER BY submission_date DESC", // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			ARRAY_A
		);
	} else {
		// Try to create the table if it doesn't exist.
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			page_id bigint(20) NOT NULL,
			page_title varchar(255) NOT NULL,
			stakeholder_name varchar(100) NOT NULL,
			stakeholder_email varchar(100) NOT NULL,
			stakeholder_department varchar(100) NOT NULL,
			submission_date datetime NOT NULL,
			needs_changes tinyint(1) NOT NULL DEFAULT 0,
			support_ticket_url varchar(255) DEFAULT '',
			next_review_date varchar(20) NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Check if the table was created successfully.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			// Process filter parameters if set.
			$where_clause = '';
			$filter_stakeholder = isset( $_GET['filter_stakeholder'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_stakeholder'] ) ) : '';
			
			// Add stakeholder filter if provided.
			if ( ! empty( $filter_stakeholder ) ) {
				$where_clause = $wpdb->prepare( " WHERE stakeholder_name LIKE %s", '%' . $wpdb->esc_like( $filter_stakeholder ) . '%' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			}
			
			// Get all stakeholder names for the filter dropdown.
			$stakeholders = $wpdb->get_col( "SELECT DISTINCT stakeholder_name FROM $table_name ORDER BY stakeholder_name ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			
			// Get filtered submissions.
			$submissions = $wpdb->get_results(
				"SELECT * FROM $table_name" . $where_clause . " ORDER BY submission_date DESC", // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				ARRAY_A
			);
		}
	}

	// Add admin page wrapper.
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Content Audit Submissions', 'content-audit' ); ?></h1>
		<p><?php esc_html_e( 'View all content review submissions from stakeholders.', 'content-audit' ); ?></p>
		
		<?php if ( ! empty( $submissions ) ) : ?>
			<div class="tablenav top">
				<div class="alignleft actions">
					<!-- Stakeholder filter dropdown -->
					<form method="get" action="" style="display: inline-block; margin-right: 10px;">
						<input type="hidden" name="page" value="content-audit-submissions" />
						<select name="filter_stakeholder" id="filter-stakeholder">
							<option value=""><?php esc_html_e( 'All Stakeholders', 'content-audit' ); ?></option>
							<?php foreach ( $stakeholders as $stakeholder ) : ?>
								<option value="<?php echo esc_attr( $stakeholder ); ?>" <?php selected( $filter_stakeholder, $stakeholder ); ?>>
									<?php echo esc_html( $stakeholder ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'content-audit' ); ?>" />
						<?php if ( ! empty( $filter_stakeholder ) ) : ?>
							<a href="<?php echo esc_url( remove_query_arg( 'filter_stakeholder' ) ); ?>" class="button">
								<?php esc_html_e( 'Reset', 'content-audit' ); ?>
							</a>
						<?php endif; ?>
					</form>
					
					<a href="<?php echo esc_url( add_query_arg( 'export_csv', '1' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Export to CSV', 'content-audit' ); ?>
					</a>
				</div>
				<br class="clear">
			</div>
		<?php endif; ?>
		
		<div id="content-audit-submissions" style="padding: 10px 10px 20px 10px; background-color: #fff; margin-top: 20px;">
			<?php if ( empty( $submissions ) ) : ?>
				<p><?php esc_html_e( 'No submissions found.', 'content-audit' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped content-audit-submissions-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Page Title', 'content-audit' ); ?></th>
							<th><?php esc_html_e( 'Stakeholder', 'content-audit' ); ?></th>
							<th><?php esc_html_e( 'Department', 'content-audit' ); ?></th>
							<th><?php esc_html_e( 'Submission Date', 'content-audit' ); ?></th>
							<th><?php esc_html_e( 'Needs Changes', 'content-audit' ); ?></th>
							<th><?php esc_html_e( 'Support Ticket', 'content-audit' ); ?></th>
							<th><?php esc_html_e( 'Next Review', 'content-audit' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $submissions as $submission ) : ?>
							<tr>
								<td>
									<?php
									$edit_link = get_edit_post_link( $submission['page_id'] );
									if ( $edit_link ) {
										echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $submission['page_title'] ) . '</a>';
									} else {
										echo esc_html( $submission['page_title'] );
									}
									?>
								</td>
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
										if ( $ticket_url === 'https://' ) {
											echo '—';
										} else {
											// Sanitize the URL for output.
											$ticket_url = esc_url( $ticket_url );

											// Only output if we have a valid URL.
											if ( ! empty( $ticket_url ) && $ticket_url !== 'https://' ) {
												echo '<a href="' . $ticket_url . '" target="_blank">' . esc_html__( 'View Ticket', 'content-audit' ) . '</a>';
											} else {
												echo '—';
											}
										}
									} else {
										echo '—';
									}
									?>
								</td>
								<td>
									<?php
									$next_review = new DateTime( $submission['next_review_date'] );
									echo esc_html( $next_review->format( 'F d, Y' ) );
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Export submissions data to a CSV file.
 *
 * @param string $table_name The database table name.
 * @return void
 */
function content_audit_export_submissions_csv( $table_name ) {
	global $wpdb;

	// Check if the table exists before querying.
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return;
	}

	// Process filter parameters if set.
	$where_clause = '';
	$filter_stakeholder = isset( $_GET['filter_stakeholder'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_stakeholder'] ) ) : '';
	
	// Add stakeholder filter if provided.
	if ( ! empty( $filter_stakeholder ) ) {
		$where_clause = $wpdb->prepare( " WHERE stakeholder_name LIKE %s", '%' . $wpdb->esc_like( $filter_stakeholder ) . '%' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	// Get submissions from the database.
	$submissions = $wpdb->get_results(
		"SELECT * FROM $table_name" . $where_clause . " ORDER BY submission_date DESC", // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		ARRAY_A
	);

	if ( empty( $submissions ) ) {
		return;
	}

	// Prevent any output before headers.
	if ( ob_get_length() ) {
		ob_clean();
	}

	// Set headers for CSV download.
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=content-audit-submissions-' . gmdate( 'Y-m-d' ) . '.csv' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	// Create a file pointer connected to the output stream.
	$output = fopen( 'php://output', 'w' );

	// Add UTF-8 BOM for Excel compatibility.
	fputs( $output, "\xEF\xBB\xBF" );

	// Set up the column headers.
	$headers = array(
		'Page Title',
		'Stakeholder',
		'Department',
		'Submission Date',
		'Needs Changes',
		'Support Ticket',
		'Next Review',
	);

	// Output the column headers.
	fputcsv( $output, $headers );

	// Output each row of data.
	foreach ( $submissions as $submission ) {
		// Format dates for better readability.
		$submission_date  = new DateTime( $submission['submission_date'] );
		$next_review_date = new DateTime( $submission['next_review_date'] );

		$row = array(
			$submission['page_title'],
			$submission['stakeholder_name'],
			$submission['stakeholder_department'],
			$submission_date->format( 'F d, Y H:i' ),
			$submission['needs_changes'] ? 'Yes' : 'No',
			empty( $submission['support_ticket_url'] ) ? 'N/A' : $submission['support_ticket_url'],
			$next_review_date->format( 'F d, Y' ),
		);

		fputcsv( $output, $row );
	}

	// Close the file pointer.
	fclose( $output );

	// Stop execution to prevent any additional output.
	exit;
}
