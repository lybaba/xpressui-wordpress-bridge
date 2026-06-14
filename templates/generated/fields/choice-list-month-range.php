<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><div class="template-field" data-template-zone="field" data-field-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" data-field-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'))); ?>">
  <div class="template-field-label-row">
    <div class="template-field-label">
      <span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'label'))); ?></span>
      <span class="template-required" aria-hidden="true"<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'required'))))): ?> style="display:none"<?php endif; ?>>*</span>
    </div>
    <div class="template-field-meta-inline">
      <span class="template-field-pill template-month-range-total" data-month-range-total="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" hidden>
        <span data-month-range-total-count="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"></span>
        <span aria-hidden="true">·</span>
        <span data-month-range-total-amount="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"></span>
      </span>
    </div>
  </div>
  <input
    type="hidden"
    id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
    name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
    data-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
    data-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'label'))); ?>"
    data-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'))); ?>"
    data-section-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'section'), 'name'))); ?>"
    data-choices='<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_mark_safe(xpressui_bridge_template_filter_tojson(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'choices'))))); ?>'
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'max_choices'))): ?>
data-max-num-of-choices="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'max_choices'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'min_choices'))): ?>
data-min-num-of-choices="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'min_choices'))); ?>"<?php endif; ?>
  />
  <div class="template-month-range-grid" id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>_selection" data-choice-list-grid="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" data-choice-month-range="true">
<?php $xpressui_bridge_ns_active_year = ['value' => ""]; ?>
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
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_and_value((!xpressui_bridge_template_truthy(($xpressui_bridge_ns_active_year['value'] ?? null))), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'default_selected')), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year')))): ?>
<?php $xpressui_bridge_ns_active_year['value'] = xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year'); ?>
<?php endif; ?>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_2; ?>
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(($xpressui_bridge_ns_active_year['value'] ?? null))))): ?>
<?php
$xpressui_loop_parent_ctx_6 = $xpressui_ctx;
$xpressui_loop_items_5 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'choices'));
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
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_and_value((!xpressui_bridge_template_truthy(($xpressui_bridge_ns_active_year['value'] ?? null))), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year')), xpressui_bridge_template_equals(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year')), xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'current_year')))))): ?>
<?php $xpressui_bridge_ns_active_year['value'] = xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year'); ?>
<?php endif; ?>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_6; ?>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(($xpressui_bridge_ns_active_year['value'] ?? null))))): ?>
<?php
$xpressui_loop_parent_ctx_10 = $xpressui_ctx;
$xpressui_loop_items_9 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'choices'));
foreach ($xpressui_loop_items_9 as $xpressui_loop_index_11 => $xpressui_loop_value_12):
    $xpressui_ctx = $xpressui_loop_parent_ctx_10;
    $xpressui_ctx['choice'] = $xpressui_loop_value_12;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_11 + 1,
        'index0' => $xpressui_loop_index_11,
        'first'  => $xpressui_loop_index_11 === 0,
        'last'   => ($xpressui_loop_index_11 + 1) === count($xpressui_loop_items_9),
    ];
?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value((!xpressui_bridge_template_truthy(($xpressui_bridge_ns_active_year['value'] ?? null))), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year')))): ?>
<?php $xpressui_bridge_ns_active_year['value'] = xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year'); ?>
<?php endif; ?>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_10; ?>
<?php endif; ?>
<?php $xpressui_bridge_ns_year_group = ['key' => "", 'open' => false]; ?>
<?php
$xpressui_loop_parent_ctx_14 = $xpressui_ctx;
$xpressui_loop_items_13 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'choices'));
foreach ($xpressui_loop_items_13 as $xpressui_loop_index_15 => $xpressui_loop_value_16):
    $xpressui_ctx = $xpressui_loop_parent_ctx_14;
    $xpressui_ctx['choice'] = $xpressui_loop_value_16;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_15 + 1,
        'index0' => $xpressui_loop_index_15,
        'first'  => $xpressui_loop_index_15 === 0,
        'last'   => ($xpressui_loop_index_15 + 1) === count($xpressui_loop_items_13),
    ];
?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year'), (!xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year'), ($xpressui_bridge_ns_year_group['key'] ?? null)))))): ?>
<?php if (xpressui_bridge_template_truthy(($xpressui_bridge_ns_year_group['open'] ?? null))): ?>
            </div>
          </section>
<?php endif; ?>
<?php $xpressui_bridge_ns_year_group['key'] = xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year'); ?>
<?php $xpressui_bridge_ns_year_group['open'] = true; ?>
        <section class="template-month-range-year" data-choice-year-group="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year'))); ?>" data-active="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year'), ($xpressui_bridge_ns_active_year['value'] ?? null))) ? "true" : "false"))); ?>"<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year'), ($xpressui_bridge_ns_active_year['value'] ?? null))))): ?> hidden<?php endif; ?>>
          <div class="template-month-range-year-header">
            <label class="template-month-range-year-select-label">
              <span>Year</span>
              <select class="template-month-range-year-select" data-month-range-year-select="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>">
<?php $xpressui_bridge_ns_seen_years = ['values' => []]; ?>
<?php
$xpressui_loop_parent_ctx_18 = $xpressui_ctx;
$xpressui_loop_items_17 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'choices'));
foreach ($xpressui_loop_items_17 as $xpressui_loop_index_19 => $xpressui_loop_value_20):
    $xpressui_ctx = $xpressui_loop_parent_ctx_18;
    $xpressui_ctx['year_choice'] = $xpressui_loop_value_20;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_19 + 1,
        'index0' => $xpressui_loop_index_19,
        'first'  => $xpressui_loop_index_19 === 0,
        'last'   => ($xpressui_loop_index_19 + 1) === count($xpressui_loop_items_17),
    ];
?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'year_choice'), 'year'), (!xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'year_choice'), 'year'), ($xpressui_bridge_ns_seen_years['values'] ?? null)))))): ?>
<?php $xpressui_bridge_ns_seen_years['values'] = xpressui_bridge_template_add(($xpressui_bridge_ns_seen_years['values'] ?? null), [xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'year_choice'), 'year')]); ?>
                    <option value="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'year_choice'), 'year'))); ?>"<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'year_choice'), 'year'), ($xpressui_bridge_ns_active_year['value'] ?? null)))): ?> selected<?php endif; ?>><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'year_choice'), 'year'))); ?></option>
<?php endif; ?>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_18; ?>
              </select>
            </label>
            <label class="template-month-range-year-check-label">
              <input
                type="checkbox"
                class="template-month-range-year-check"
                data-month-range-year-checkbox="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
                data-choice-year="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'year'))); ?>"
                data-selected="false"
                data-mixed="false"
              />
              <span>Select full year</span>
            </label>
          </div>
          <div class="template-month-range-year-grid">
<?php endif; ?>
      <article
        class="template-month-range-card"
        data-choice-option-action="toggle"
        data-choice-option-value="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'value'))); ?>"
        data-selected="false"
        data-disabled="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'disabled')) ? "true" : "false"))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'month'))): ?>
data-choice-month="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'month'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'amount'))): ?>
data-choice-month-amount="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'amount'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'currency'))): ?>
data-choice-month-currency="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'currency'))); ?>"<?php endif; ?>
        role="checkbox"
        tabindex="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'disabled')) ? "-1" : "0"))); ?>"
        aria-checked="false"
      >
        <span class="template-month-range-copy">
          <span class="template-month-range-title" data-choice-option-title="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'value'))); ?>"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'label'))); ?></span>
          <span class="template-month-range-subtext"<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'amount_display'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'desc')))): ?> data-choice-option-description="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'value'))); ?>"<?php endif; ?>><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'amount_display'))): ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'amount_display'))); ?><?php else: ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'desc'))); ?><?php endif; ?></span>
        </span>
        <span class="template-month-range-check" aria-hidden="true"></span>
        <span class="template-choice-footer" data-choice-option-footer="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'value'))); ?>" hidden></span>
      </article>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_14; ?>
<?php if (xpressui_bridge_template_truthy(($xpressui_bridge_ns_year_group['open'] ?? null))): ?>
        </div>
      </section>
<?php endif; ?>
  </div>

<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'desc'))): ?>
    <div class="template-field-help"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'desc'))); ?></div>
<?php endif; ?>
<?php xpressui_bridge_template_include_template('field-meta.php', $xpressui_ctx); ?>
</div>
