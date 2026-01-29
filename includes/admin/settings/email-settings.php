<?php
/**
 * Email Settings Tab.
 *
 * @package ContentAudit
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Email Template Settings Section.
content_audit_email_template_settings_section_callback();
?>
<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row">
				<label for="content_audit_email_header_image"><?php esc_html_e( 'Email Header Image', 'ab-content-audit' ); ?></label>
			</th>
			<td>
				<?php content_audit_email_header_image_callback(); ?>
			</td>
		</tr>
	</tbody>
</table>
