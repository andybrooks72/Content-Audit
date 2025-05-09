<?php
/**
 * Plugin Name: Peppermoney Content Audit
 * Plugin URI: https://www.pepper.money
 * Description: Adds a custom Content Audit page to help track and manage content review dates.
 * Version: 1.1.2
 * Author: Pepper Money
 * Author URI: https://www.pepper.money
 * Text Domain: peppermoney-content-audit
 *
 * @package ContentAudit
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants.
define( 'CONTENT_AUDIT_VERSION', '1.1.2' );
define( 'CONTENT_AUDIT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CONTENT_AUDIT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files.
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/audit-panel-updated.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/form-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/submissions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/helper-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-columns.php';

/**
 * Initialize the plugin.
 *
 * @return void
 */
function content_audit_init() {
	// Load text domain for translations.
	load_plugin_textdomain( 'content-audit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'content_audit_init' );

/**
 * Enqueue frontend scripts and styles.
 *
 * @return void
 */
function content_audit_enqueue_frontend_assets() {
	// Only enqueue on pages that have the content audit shortcode.
	global $post;
	if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'content_audit_form' ) ) {
		// Enqueue the CSS file.
		wp_enqueue_style(
			'content-audit-frontend-styles',
			CONTENT_AUDIT_PLUGIN_URL . 'assets/css/frontend-styles.css',
			array(),
			CONTENT_AUDIT_VERSION
		);

		// Enqueue the JS file.
		wp_enqueue_script(
			'content-audit-frontend-scripts',
			CONTENT_AUDIT_PLUGIN_URL . 'assets/js/frontend-scripts.js',
			array(),
			CONTENT_AUDIT_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'content_audit_enqueue_frontend_assets' );

/**
 * Add plugin action links.
 *
 * @param array $links Array of plugin action links.
 * @return array Modified array of plugin action links.
 */
function content_audit_plugin_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=content-audit' ) ) . '">' . esc_html__( 'Content Audit', 'content-audit' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'content_audit_plugin_action_links' );

/**
 * Create database tables on plugin activation.
 *
 * @return void
 */
function content_audit_activate() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name      = $wpdb->prefix . 'content_audit_submissions';
	// Create the submissions table.
	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		content_id bigint(20) NOT NULL,
		content_title varchar(255) NOT NULL,
		content_type varchar(20) NOT NULL DEFAULT 'page',
		stakeholder_name varchar(100) NOT NULL,
		stakeholder_email varchar(100) NOT NULL,
		stakeholder_department varchar(100) NOT NULL,
		submission_date datetime NOT NULL,
		needs_changes tinyint(1) NOT NULL DEFAULT 0,
		support_ticket_url varchar(255) DEFAULT '',
		next_review_date datetime NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	// Add version to options.
	update_option( 'content_audit_db_version', CONTENT_AUDIT_VERSION );
}
register_activation_hook( __FILE__, 'content_audit_activate' );

// Add dependancy for ACF field creation.
require_once CONTENT_AUDIT_PLUGIN_DIR . 'vendor/autoload.php';
require_once CONTENT_AUDIT_PLUGIN_DIR . 'includes/class-acfauditfields.php';
ContentAudit\ACFAuditFields::initialise();

/**
 * Check and update database tables when plugin is loaded.
 * This ensures tables exist even if the activation hook wasn't triggered.
 *
 * @return void
 */
function content_audit_check_db_tables() {
	if ( get_option( 'content_audit_db_version' ) !== CONTENT_AUDIT_VERSION ) {
		content_audit_activate();
	}
}
add_action( 'plugins_loaded', 'content_audit_check_db_tables', 5 );

/**
 * Register shortcode for content audit form.
 */
add_shortcode( 'content_audit_form', 'content_audit_form_shortcode' );

/**
 * Add submissions page to admin menu.
 *
 * @return void
 */
function content_audit_add_submissions_page() {
	add_submenu_page(
		'content-audit',
		esc_html__( 'Content Review Submissions', 'peppermoney-content-audit' ),
		esc_html__( 'Submissions', 'peppermoney-content-audit' ),
		'manage_options',
		'content-audit-submissions',
		'content_audit_render_submissions_page'
	);
}
add_action( 'admin_menu', 'content_audit_add_submissions_page' );

/**
 * Uninstall the plugin.
 *
 * WARNING: This will delete all content audit data including submissions.
 * All data will be permanently lost when uninstalling this plugin.
 *
 * @return void
 */
function content_audit_uninstall() {
	global $wpdb;

	// Delete the database table.
	$table_name = $wpdb->prefix . 'content_audit_submissions';

	// We need to use a direct query to drop the table.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'content_audit_submissions' );
	// phpcs:enable

	// Delete all plugin options.
	delete_option( 'content_audit_db_version' );
	delete_option( 'content_audit_email_settings' );
	delete_option( 'content_audit_form_settings' );
	delete_option( 'content_audit_display_settings' );

	// Delete all post meta related to content audit.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'next_review_date'" );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'last_review_date'" );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'stakeholder_name'" );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_next_review_date'" );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_last_review_date'" );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_stakeholder_name'" );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'stakeholder_department'" );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'stakeholder_email'" );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_stakeholder_department'" );
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_stakeholder_email'" );

	// phpcs:enable
}
register_uninstall_hook( __FILE__, 'content_audit_uninstall' );

/**
 * Add a confirmation dialog when uninstalling the plugin.
 *
 * @return void
 */
function content_audit_add_uninstall_confirmation() {
	// Only run on the plugins page.
	$screen = get_current_screen();
	if ( ! $screen || 'plugins' !== $screen->id ) {
		return;
	}

	// Add JavaScript to create a confirmation dialog.
	?>
	<script type="text/javascript">
	document.addEventListener('DOMContentLoaded', function() {
		// Find the Content Audit plugin's delete link.
		const deleteLinks = document.querySelectorAll('a.delete[data-plugin="content-audit/content-audit.php"]');

		if (deleteLinks.length > 0) {
			// Add click event listener to each delete link.
			deleteLinks.forEach(function(link) {
				link.addEventListener('click', function(event) {
					// Prevent the default action.
					event.preventDefault();

					// Show confirmation dialog.
					if (confirm('<?php echo esc_js( __( 'WARNING: Uninstalling the Content Audit plugin will permanently delete all content audit data, including all submissions. This action cannot be undone. Are you sure you want to continue?', 'peppermoney-content-audit' ) ); ?>')) {
						// If confirmed, follow the original link.
						window.location.href = this.href;
					}
				});
			});
		}
	});
	</script>
	<?php
}
add_action( 'admin_head', 'content_audit_add_uninstall_confirmation' );
