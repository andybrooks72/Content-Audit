<?php
/**
 * Class ACF Audit Fields.
 *
 * @package ContentAudit
 */

namespace ContentAudit;

/**
 * Class for additional functions for expanding the functionality of Advanced Custom Fields plugin.
 *
 * @package ContentAudit
 */
class ACFAuditFields {
	/**
	 * Static function must be called after require within functions.php
	 * This will setup all acf functions
	 */
	public static function initialise() {
		// Make sure ACF is activated.
		if ( class_exists( 'acf' ) && class_exists( '\StoutLogic\AcfBuilder\FieldsBuilder' ) ) :
			$self = new self();
			add_action( 'after_setup_theme', array( $self, 'audit_fields' ) );
		endif;
	}

	/**
	 * Add fields to pages and posts for content audit
	 *
	 * @return void
	 */
	public function audit_fields() {
		$audit_fields = new \StoutLogic\AcfBuilder\FieldsBuilder(
			'audit_fields',
			array(
				'title'        => __( 'Audit Data', 'ContentAudit' ),
				'show_in_rest' => true,
				'position'     => 'side',
			)
		);

		$audit_fields->addText(
			'stakeholder_name',
			array(
				'label' => __( 'Stakeholder Name', 'ContentAudit' ),
			)
		)->addText(
			'stakeholder_department',
			array(
				'label' => __( 'Stakeholder Department', 'ContentAudit' ),
			)
		)->addText(
			'stakeholder_email',
			array(
				'label' => __( 'Stakeholder Email', 'ContentAudit' ),
			)
		)->addDatePicker(
			'last_review_date',
			array(
				'label'          => __( 'Last Review Date', 'ContentAudit' ),
				'display_format' => 'F j, Y',
				'return_format'  => 'F j, Y',
			)
		)->addDatePicker(
			'next_review_date',
			array(
				'label'          => __( 'Next Review Date', 'ContentAudit' ),
				'display_format' => 'F j, Y',
				'return_format'  => 'F j, Y',
			)
		);

		$audit_fields->setLocation( 'post_type', '==', 'page' )
		->or( 'post_type', '==', 'post' );

		add_action(
			'acf/init',
			function () use ( $audit_fields ) {
				acf_add_local_field_group( $audit_fields->build() );
			}
		);
	}
}
