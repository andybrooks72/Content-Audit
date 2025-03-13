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
		<p><?php esc_html_e( 'Track and manage content review dates for your pages.', 'content-audit' ); ?></p>
		
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
	// Only load on our plugin page.
	if ( 'toplevel_page_content-audit' !== $hook ) {
		return;
	}

	// Add custom styles.
	wp_enqueue_style(
		'content-audit-admin-styles',
		CONTENT_AUDIT_PLUGIN_URL . 'assets/css/admin-styles.css',
		array(),
		CONTENT_AUDIT_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'content_audit_admin_enqueue_scripts' );
