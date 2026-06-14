<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><?php $xpressui_ctx['choice_layout'] = (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'layout'), ["horizontal", "vertical"])) ? xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'layout') : "horizontal"); ?>
<div class="template-field" data-template-zone="field" data-field-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" data-field-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'))); ?>">
  <div class="template-field-label-row">
    <div class="template-field-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'label'))); ?></div>
    <span class="template-required"<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'required'))))): ?> style="display:none"<?php endif; ?>>*</span>
  </div>

<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'choices'))): ?>
    <input type="hidden" id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" data-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" data-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'label'))); ?>" data-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'))); ?>" data-section-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'section'), 'name'))); ?>" />
    <div class="template-quiz-wrap">
      <div id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>_selection" data-quiz-zone="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>">
        <div class="template-choice-grid template-choice-grid--<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_layout'))); ?>" data-quiz-catalog="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" data-choice-layout="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_layout'))); ?>">
<?php
$xpressui_loop_parent_ctx_2 = $xpressui_ctx;
$xpressui_loop_items_1 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'choices'));
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
<?php $xpressui_ctx['choice_id'] = xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'id'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'value')); ?>
<?php $xpressui_ctx['choice_title'] = xpressui_bridge_template_or_value(xpressui_bridge_template_or_value(xpressui_bridge_template_or_value(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'label'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'title')), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'name')), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'value')), xpressui_bridge_template_concat(["Choice ", xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'loop'), 'index')])); ?>
<?php $xpressui_ctx['choice_has_media'] = xpressui_bridge_template_or_value(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'image_url'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'image_medium')), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'image_thumbnail')); ?>
<?php $xpressui_ctx['choice_desc'] = xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'desc'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'helpText')); ?>
            <article class="template-choice-card" data-quiz-answer-card="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'id'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'value')))); ?>" data-quiz-answer-action="toggle" data-quiz-answer-id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'id'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'value')))); ?>">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_has_media'))): ?>
                <div class="template-choice-media">
                  <img src="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'image_url'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'image_medium')), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'image_thumbnail')))); ?>" alt="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_title'))); ?>" loading="lazy" data-quiz-answer-image="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_id'))); ?>" />
                </div>
<?php endif; ?>
              <div class="template-choice-title" data-quiz-answer-title="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_id'))); ?>"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_title'))); ?></div>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_desc'))): ?>
                <div class="template-field-help" data-quiz-answer-desc="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_id'))); ?>"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_desc'))); ?></div>
<?php endif; ?>
              <div class="template-gallery-caption" data-quiz-answer-state="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_id'))); ?>">
                <span data-quiz-mode="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_id'))); ?>" hidden></span>
                <span data-quiz-selected-state="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_id'))); ?>" hidden></span>
              </div>
            </article>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_2; ?>
        </div>
      </div>
    </div>
<?php else: ?>
    <div class="template-quiz-wrap">
      <input
        type="hidden"
        id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
        name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
        data-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
        data-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'label'))); ?>"
        data-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'))); ?>"
        data-section-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'section'), 'name'))); ?>"
      />
      <div
        id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>_selection"
        data-quiz-zone="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
      >
        <div data-quiz-open-wrapper="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>">
          <textarea
            class="template-textarea"
            placeholder="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'placeholder')) ? xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'placeholder') : "Write your answer"))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'required'))): ?>
required aria-required="true"<?php endif; ?>
            data-quiz-open-answer="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
          ></textarea>
        </div>
      </div>
    </div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'desc'))): ?>
    <div class="template-field-help"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'desc'))); ?></div>
<?php endif; ?>
<?php xpressui_bridge_template_include_template('field-meta.php', $xpressui_ctx); ?>
</div>
