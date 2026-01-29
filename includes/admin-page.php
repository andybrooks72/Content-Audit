<?php
/**
 * Content Audit Admin Page.
 *
 * @package ContentAudit
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register the Content Audit admin menu page.
 *
 * @return void
 */
function content_audit_register_admin_page() {
	add_menu_page(
		esc_html__( 'Content Audit', 'ab-content-audit' ),
		esc_html__( 'Content Audit', 'ab-content-audit' ),
		'manage_options',
		'content-audit',
		'content_audit_render_admin_page',
		'dashicons-visibility',
		95
	);
}
add_action( 'admin_menu', 'content_audit_register_admin_page' );

/**
 * Process Send Email form submission and redirect with success params.
 * Runs before the content audit page is rendered so we can redirect.
 *
 * @return void
 */
function content_audit_process_send_email_form() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$nonce = isset( $_POST['send_email_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['send_email_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'send_email_nonce' ) ) {
		return;
	}

	$page_id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;
	if ( ! $page_id ) {
		return;
	}

	// Check that a Send Email button was clicked (unique_id is dynamic per row).
	$send_clicked = false;
	foreach ( array_keys( $_POST ) as $key ) {
		if ( strpos( $key, 'send_email_' ) === 0 && '1' === $_POST[ $key ] ) {
			$send_clicked = true;
			break;
		}
	}
	if ( ! $send_clicked ) {
		return;
	}

	$sent = content_audit_send_review_email( $page_id );

	$filter       = isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : '30days';
	$content_type = isset( $_POST['content_type'] ) ? sanitize_text_field( wp_unslash( $_POST['content_type'] ) ) : 'page';

	$redirect_args = array(
		'page'                     => 'content-audit',
		'filter'                   => $filter,
		'content_type'             => $content_type,
		'content_audit_email_sent' => $sent ? '1' : '0',
		'content_audit_sent_id'   => $page_id,
	);

	wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'load-toplevel_page_content-audit', 'content_audit_process_send_email_form' );

/**
 * Render the Content Audit admin page.
 *
 * @return void
 */
function content_audit_render_admin_page() {
	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Add admin page wrapper.
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<?php
		// Show standard WordPress notice when email was sent (after redirect).
		if ( isset( $_GET['content_audit_email_sent'] ) && isset( $_GET['content_audit_sent_id'] ) ) {
			$notice_type = '1' === $_GET['content_audit_email_sent'] ? 'success' : 'warning';
			$message    = '1' === $_GET['content_audit_email_sent']
				? __( 'Review email has been sent to the stakeholder.', 'ab-content-audit' )
				: __( 'There was a problem sending the email. Please try again.', 'ab-content-audit' );
			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $notice_type ),
				esc_html( $message )
			);
		}
		?>
		<p><?php esc_html_e( 'Track and manage content review dates for your pages and posts.', 'ab-content-audit' ); ?></p>

		<div id="content-audit-panel">
			<?php
			// Call the function that contains the content audit table.
			content_audit_display_table();
			?>
		</div>
	</div>
	<?php
}

/**
 * Enqueue admin styles.
 *
 * @param string $hook The current admin page hook.
 *
 * @return void
 */
function content_audit_admin_enqueue_scripts( $hook ) {
	// Only load on the content audit page.
	if ( 'toplevel_page_content-audit' !== $hook && 'content-audit_page_content-audit-submissions' !== $hook && 'edit.php' !== $hook ) {
		return;
	}

	// Register and enqueue the admin stylesheet.
	wp_register_style(
		'content-audit-admin-styles',
		plugin_dir_url( __DIR__ ) . 'assets/css/admin-styles.css',
		array( 'dashicons' ),
		'1.0.1',
		'all'
	);
	wp_enqueue_style( 'content-audit-admin-styles' );

	// Add custom inline styles for the tabbed interface.
	$custom_css = '
		.content-audit-table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 15px;
		}
		.content-audit-table th,
		.content-audit-table td {
			padding: 10px;
			text-align: left;
			border-bottom: 1px solid #ddd;
		}
		.content-audit-table th {
			background-color: #333;
		}
		.content-audit-table tr.overdue {
			background-color: #fff8f8;
		}
		.content-audit-table .warning-icon {
			color: #d9042b;
			font-weight: bold;
		}
		.content-audit-table .overdue-date {
			color: #d9042b;
		}
		.content-audit-table .button-red {
			background-color: #d9042b;
			border-color: #d9042b;
			color: #fff;
		}
		.content-audit-table .button-red:hover {
			background-color: #c00;
			border-color: #c00;
		}
		.content-audit-table .flex {
			display: flex;
			align-items: center;
		}
		.content-audit-table .message {
			margin-left: 10px;
			color: #46b450;
			font-weight: bold;
		}
		.nav-tab-wrapper {
			margin-bottom: 15px;
		}
	';
	wp_add_inline_style( 'content-audit-admin-styles', $custom_css );
}
add_action( 'admin_enqueue_scripts', 'content_audit_admin_enqueue_scripts' );
