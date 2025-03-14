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
		esc_html__( 'Content Audit', 'content-audit' ),
		esc_html__( 'Content Audit', 'content-audit' ),
		'manage_options',
		'content-audit',
		'content_audit_render_admin_page',
		'dashicons-visibility',
		95
	);
}
add_action( 'admin_menu', 'content_audit_register_admin_page' );

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
		<p><?php esc_html_e( 'Track and manage content review dates for your pages and posts.', 'content-audit' ); ?></p>
		
		<div id="content-audit-panel" style="padding: 10px 10px 20px 10px; background-color: #fff; margin-top: 20px;">
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
	if ( 'toplevel_page_content-audit' !== $hook && 'content-audit_page_content-audit-submissions' !== $hook ) {
		return;
	}

	// Register and enqueue the admin stylesheet.
	wp_register_style(
		'content-audit-admin-styles',
		plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin-styles.css',
		array(),
		'1.0.1',
		'all'
	);
	wp_enqueue_style( 'content-audit-admin-styles' );

	// Add custom inline styles for the tabbed interface.
	$custom_css = "
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
	";
	wp_add_inline_style( 'content-audit-admin-styles', $custom_css );
}
add_action( 'admin_enqueue_scripts', 'content_audit_admin_enqueue_scripts' );
