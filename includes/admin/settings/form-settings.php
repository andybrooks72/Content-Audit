<?php
/**
 * Form Settings Tab.
 *
 * @package ContentAudit
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Form Settings Section.
content_audit_form_settings_section_callback();
?>
<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<label for="content_audit_success_message"><?php esc_html_e( 'Success Message', 'peppermoney-content-audit' ); ?></label>
			</th>
			<td>
				<?php content_audit_success_message_callback(); ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="content_audit_button_background_color"><?php esc_html_e( 'Button Background Color', 'peppermoney-content-audit' ); ?></label>
			</th>
			<td>
				<?php content_audit_button_background_color_callback(); ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="content_audit_button_text_color"><?php esc_html_e( 'Button Text Color', 'peppermoney-content-audit' ); ?></label>
			</th>
			<td>
				<?php content_audit_button_text_color_callback(); ?>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="content_audit_link_text_color"><?php esc_html_e( 'Link Text Color', 'peppermoney-content-audit' ); ?></label>
			</th>
			<td>
				<?php content_audit_link_text_color_callback(); ?>
			</td>
		</tr>
	</tbody>
</table>
