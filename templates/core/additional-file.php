<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $xpressui_ctx ) || ! is_array( $xpressui_ctx ) ) {
	return;
}

$additional_slots = isset( $xpressui_ctx['rendered_form']['additional_file_slots'] ) && is_array( $xpressui_ctx['rendered_form']['additional_file_slots'] )
	? $xpressui_ctx['rendered_form']['additional_file_slots']
	: [ [ 'id' => 'xpressui_afile', 'label' => '' ] ];

foreach ( $additional_slots as $slot ) :
	$slot_id    = sanitize_key( (string) ( $slot['id'] ?? '' ) );
	$slot_label = (string) ( $slot['label'] ?? '' );
	if ( '' === $slot_id ) {
		continue;
	}
?>
<section
  class="template-section"
  data-template-zone="section"
  data-afile-slot="<?php echo esc_attr( $slot_id ); ?>"
  style="display:none;"
>
  <div class="template-fields">
    <div
      class="template-field"
      data-template-zone="field"
      data-field-name="<?php echo esc_attr( $slot_id ); ?>"
      data-field-type="file"
    >
      <div class="template-field-label-row">
        <div class="template-field-label" data-afile-label><?php echo '' !== $slot_label ? esc_html( $slot_label ) : esc_html__( 'Additional document', 'xpressui-bridge' ); ?></div>
        <div class="template-field-meta-inline">
          <span class="template-required" aria-hidden="true" style="display:none;">*</span>
        </div>
      </div>

      <div class="xpressui-afile-ref-block" data-afile-ref-block style="display:none;">
        <a class="xpressui-afile-ref-link" data-afile-ref-link href="" target="_blank" rel="noopener noreferrer"></a>
        <p class="xpressui-afile-ref-hint" data-afile-ref-hint></p>
      </div>

      <div class="template-upload-box" data-file-drop-zone="<?php echo esc_attr( $slot_id ); ?>" data-file-drag-active="false">
        <span class="template-upload-icon">&#8593;</span>
        <div class="template-field-label"><?php esc_html_e( 'Upload file', 'xpressui-bridge' ); ?></div>
        <div class="template-field-help"><?php esc_html_e( 'Drag a file here or browse from your device.', 'xpressui-bridge' ); ?></div>
        <input
          id="<?php echo esc_attr( $slot_id ); ?>"
          class="template-input"
          type="file"
          name="<?php echo esc_attr( $slot_id ); ?>"
          data-name="<?php echo esc_attr( $slot_id ); ?>"
          data-label="<?php echo '' !== $slot_label ? esc_attr( $slot_label ) : esc_attr__( 'Additional document', 'xpressui-bridge' ); ?>"
          data-type="file"
          data-section-name="xpressui_resume_additional_documents"
        />
      </div>

      <div
        id="<?php echo esc_attr( $slot_id ); ?>_selection"
        class="template-upload-selection"
        data-upload-selection-zone="<?php echo esc_attr( $slot_id ); ?>"
        style="display:none;"
      >
        <div class="template-upload-selection-row">
          <span class="template-upload-selection-title" data-upload-selection-title="<?php echo esc_attr( $slot_id ); ?>"></span>
        </div>
        <div class="template-field-help" data-upload-selection-message="<?php echo esc_attr( $slot_id ); ?>"></div>
        <div data-upload-selection-body="<?php echo esc_attr( $slot_id ); ?>"></div>
      </div>
    </div>
  </div>
</section>
<?php endforeach; ?>
