<?php
/**
 * Plugin Settings Tab.
 *
 * @package ContentAudit
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Plugin Settings Section.
content_audit_email_settings_section_callback();
?>
<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<label for="content_audit_notification_email"><?php esc_html_e( 'Notification Email', 'peppermoney-content-audit' ); ?></label>
			</th>
			<td>
				<?php content_audit_notification_email_callback(); ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="content_audit_from_email"><?php esc_html_e( 'From Email', 'peppermoney-content-audit' ); ?></label>
			</th>
			<td>
				<?php content_audit_from_email_callback(); ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="content_audit_from_name"><?php esc_html_e( 'From Name', 'peppermoney-content-audit' ); ?></label>
			</th>
			<td>
				<?php content_audit_from_name_callback(); ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="content_audit_base_url"><?php esc_html_e( 'Base URL', 'peppermoney-content-audit' ); ?></label>
			</th>
			<td>
				<?php content_audit_base_url_callback(); ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="content_audit_support_ticket_url"><?php esc_html_e( 'Support Ticket URL', 'peppermoney-content-audit' ); ?></label>
			</th>
			<td>
				<?php content_audit_support_ticket_url_callback(); ?>
			</td>
		</tr>
	</tbody>
</table>

<?php
// Display Settings Section.
content_audit_display_settings_section_callback();
?>
<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<label for="content_audit_show_admin_columns"><?php esc_html_e( 'Show Admin Columns', 'peppermoney-content-audit' ); ?></label>
			</th>
			<td>
				<?php content_audit_show_admin_columns_callback(); ?>
			</td>
		</tr>
	</tbody>
</table>

<?php
// Post Types Settings Section.
content_audit_post_types_settings_section_callback();
?>
<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Post Types', 'peppermoney-content-audit' ); ?>
			</th>
			<td>
				<?php content_audit_post_types_callback(); ?>
			</td>
		</tr>
	</tbody>
</table>
