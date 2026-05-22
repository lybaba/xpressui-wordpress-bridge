<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><?php $xpressui_ctx['control_id'] = xpressui_bridge_template_concat([xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'), "_", xpressui_bridge_template_context_get($xpressui_ctx, 'row_index'), "_", xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'name')]); ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'type'), "textarea"))): ?>
  <textarea
    id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'control_id'))); ?>"
    class="template-textarea template-repeater-control"
    placeholder="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'placeholder'))); ?>"
    data-repeater-control
    data-repeater-control-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'name'))); ?>"
    data-repeater-control-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'type'))); ?>"
    data-repeater-control-required="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'required')) ? "true" : "false"))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'required'))): ?>
aria-required="true"<?php endif; ?>
  ></textarea>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'type'), "select-one"))): ?>
  <select
    id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'control_id'))); ?>"
    class="template-input template-repeater-control"
    data-repeater-control
    data-repeater-control-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'name'))); ?>"
    data-repeater-control-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'type'))); ?>"
    data-repeater-control-required="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'required')) ? "true" : "false"))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'required'))): ?>
aria-required="true"<?php endif; ?>
  >
    <option value=""><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'placeholder'), "Select an option"))); ?></option>
<?php
$xpressui_loop_parent_ctx_2 = $xpressui_ctx;
$xpressui_loop_items_1 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'choices'));
foreach ($xpressui_loop_items_1 as $xpressui_loop_index_3 => $xpressui_loop_value_4):
    $xpressui_ctx = $xpressui_loop_parent_ctx_2;
    $xpressui_ctx['choice'] = $xpressui_loop_value_4;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_3 + 1,
        'index0' => $xpressui_loop_index_3,
        'first'  => $xpressui_loop_index_3 === 0,
        'last'   => ($xpressui_loop_index_3 + 1) === count($xpressui_loop_items_1),
    ];
?>
      <option value="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'value'))); ?>"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'label'))); ?></option>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_2; ?>
  </select>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'type'), "checkbox"))): ?>
  <label class="template-repeater-choice-row" for="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'control_id'))); ?>">
    <input
      id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'control_id'))); ?>"
      type="checkbox"
      class="template-repeater-control"
      data-repeater-control
      data-repeater-control-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'name'))); ?>"
      data-repeater-control-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'type'))); ?>"
      data-repeater-control-required="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'required')) ? "true" : "false"))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'required'))): ?>
aria-required="true"<?php endif; ?>
    />
    <span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'label'))); ?></span>
  </label>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'type'), ["checkboxes", "radio-buttons"]))): ?>
  <div class="template-repeater-choice-group" role="group" aria-labelledby="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'control_id'))); ?>_label">
    <div id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'control_id'))); ?>_label" class="template-repeater-subfield-label">
      <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'label'))); ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'required'))): ?>
<span class="template-required" aria-hidden="true">*</span><?php endif; ?>
    </div>
    <div class="template-repeater-choice-list">
<?php
$xpressui_loop_parent_ctx_6 = $xpressui_ctx;
$xpressui_loop_items_5 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'choices'));
foreach ($xpressui_loop_items_5 as $xpressui_loop_index_7 => $xpressui_loop_value_8):
    $xpressui_ctx = $xpressui_loop_parent_ctx_6;
    $xpressui_ctx['choice'] = $xpressui_loop_value_8;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_7 + 1,
        'index0' => $xpressui_loop_index_7,
        'first'  => $xpressui_loop_index_7 === 0,
        'last'   => ($xpressui_loop_index_7 + 1) === count($xpressui_loop_items_5),
    ];
?>
<?php $xpressui_ctx['choice_id'] = xpressui_bridge_template_concat([xpressui_bridge_template_context_get($xpressui_ctx, 'control_id'), "_", xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'loop'), 'index0')]); ?>
        <label class="template-repeater-choice-row" for="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_id'))); ?>">
          <input
            id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_id'))); ?>"
            type="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'type'), "radio-buttons")) ? "radio" : "checkbox"))); ?>"
            class="template-repeater-control"
            value="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'value'))); ?>"
            data-repeater-control
            data-repeater-control-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'name'))); ?>"
            data-repeater-control-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'type'))); ?>"
            data-repeater-control-required="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'required')) ? "true" : "false"))); ?>"
          />
          <span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'label'))); ?></span>
        </label>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_6; ?>
    </div>
  </div>
<?php else: ?>
  <input
    id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'control_id'))); ?>"
    class="template-input template-repeater-control"
    type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'input_type'))); ?>"
    placeholder="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'placeholder'))); ?>"
    data-repeater-control
    data-repeater-control-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'name'))); ?>"
    data-repeater-control-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'type'))); ?>"
    data-repeater-control-required="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'required')) ? "true" : "false"))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'subfield'), 'required'))): ?>
aria-required="true"<?php endif; ?>
  />
<?php endif; ?>

