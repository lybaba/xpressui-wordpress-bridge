<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// PRO field — server-side shell rendered by XPressUI Bridge PRO.
// The PRO runtime hydrates this element into a fully interactive field.
if ( ! isset( $xpressui_ctx ) || ! is_array( $xpressui_ctx ) ) {
	throw new RuntimeException( 'Missing template context array.' );
}
?><div class="template-field" data-template-zone="field" data-field-name="<?php echo esc_attr( xpressui_bridge_template_stringify( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'field' ), 'name' ) ) ); ?>" data-field-type="<?php echo esc_attr( xpressui_bridge_template_stringify( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'field' ), 'type' ) ) ); ?>">
  <div class="template-field-label-row">
    <div class="template-field-label"><?php echo esc_attr( xpressui_bridge_template_stringify( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'field' ), 'label' ) ) ); ?></div>
    <div class="template-field-meta-inline">
<?php if ( xpressui_bridge_template_truthy( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'field' ), 'required' ) ) ) : ?>        <span class="template-required">*</span>
<?php endif; ?>    </div>
  </div>
  <input
    type="hidden"
    id="<?php echo esc_attr( xpressui_bridge_template_stringify( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'field' ), 'name' ) ) ); ?>"
    name="<?php echo esc_attr( xpressui_bridge_template_stringify( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'field' ), 'name' ) ) ); ?>"
    data-name="<?php echo esc_attr( xpressui_bridge_template_stringify( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'field' ), 'name' ) ) ); ?>"
    data-label="<?php echo esc_attr( xpressui_bridge_template_stringify( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'field' ), 'label' ) ) ); ?>"
    data-type="<?php echo esc_attr( xpressui_bridge_template_stringify( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'field' ), 'type' ) ) ); ?>"
    data-section-name="<?php echo esc_attr( xpressui_bridge_template_stringify( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'section' ), 'name' ) ) ); ?>"
  />
  <div id="<?php echo esc_attr( xpressui_bridge_template_stringify( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'field' ), 'name' ) ) ); ?>_selection" data-pro-field-shell="<?php echo esc_attr( xpressui_bridge_template_stringify( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'field' ), 'type' ) ) ); ?>"></div>
<?php if ( xpressui_bridge_template_truthy( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'field' ), 'desc' ) ) ) : ?>    <div class="template-field-help"><?php echo esc_attr( xpressui_bridge_template_stringify( xpressui_bridge_template_attr( xpressui_bridge_template_context_get( $xpressui_ctx, 'field' ), 'desc' ) ) ); ?></div>
<?php endif; ?><?php xpressui_bridge_template_include_template( 'field-meta.php', $xpressui_ctx ); ?></div>
