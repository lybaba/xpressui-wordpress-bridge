<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><?php $xpressui_ctx['choice_layout'] = (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'layout'), ["horizontal", "vertical"])) ? xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'layout') : "vertical"); ?>
<div class="template-field" data-template-zone="field" data-field-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" data-field-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'))); ?>">
  <div class="template-field-label-row">
    <div class="template-field-label">
      <span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'label'))); ?></span>
      <span class="template-required" aria-hidden="true"<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'required'))))): ?> style="display:none"<?php endif; ?>>*</span>
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
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'choice_catalog_id'))): ?>
data-choice-catalog-id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'choice_catalog_id'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'choice_catalog_kind'))): ?>
data-choice-catalog-kind="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'choice_catalog_kind'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'max_choices'))): ?>
data-max-num-of-choices="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'max_choices'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'min_choices'))): ?>
data-min-num-of-choices="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'min_choices'))); ?>"<?php endif; ?>
  />

  <div class="template-time-slot-date-anchors" aria-hidden="true">
<?php
$xpressui_loop_parent_ctx_2 = $xpressui_ctx;
$xpressui_loop_items_1 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'time_slots_catalog'), 'calendar_days'));
foreach ($xpressui_loop_items_1 as $xpressui_loop_index_3 => $xpressui_loop_value_4):
    $xpressui_ctx = $xpressui_loop_parent_ctx_2;
    $xpressui_ctx['day'] = $xpressui_loop_value_4;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_3 + 1,
        'index0' => $xpressui_loop_index_3,
        'first'  => $xpressui_loop_index_3 === 0,
        'last'   => ($xpressui_loop_index_3 + 1) === count($xpressui_loop_items_1),
    ];
?>
      <span id="time-slot-date-<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'day'), 'date'))); ?>"></span>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_2; ?>
  </div>

  <div
    class="template-choice-grid template-choice-grid--<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_layout'))); ?> template-time-slot-availability-list"
    id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>_selection"
    data-choice-list-grid="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
    data-choice-layout="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'choice_layout'))); ?>"
    data-choice-time-slots="true"
    data-time-slot-template="date-grouped-list"
    data-time-slot-selection-mode="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "checkboxes")) ? "multiple" : "single"))); ?>"
    data-time-slot-allow-multiple-selection="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'time_slot_allow_multiple_selection')) ? "true" : "false"))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'time_slot_timezone'))): ?>
data-time-slot-timezone="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'time_slot_timezone'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'time_slot_display_mode'))): ?>
data-time-slot-display-mode="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'time_slot_display_mode'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'time_slot_booking_mode'))): ?>
data-time-slot-booking-mode="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'time_slot_booking_mode'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'time_slot_linked_product_catalog_id'))): ?>
data-time-slot-linked-product-catalog-id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'time_slot_linked_product_catalog_id'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'time_slot_product_link_field'))): ?>
data-time-slot-product-link-field="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'time_slot_product_link_field'))); ?>"<?php endif; ?>
  >
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'time_slots_catalog'), 'resource_groups'))): ?>
<?php
$xpressui_loop_parent_ctx_6 = $xpressui_ctx;
$xpressui_loop_items_5 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'time_slots_catalog'), 'resource_groups'));
foreach ($xpressui_loop_items_5 as $xpressui_loop_index_7 => $xpressui_loop_value_8):
    $xpressui_ctx = $xpressui_loop_parent_ctx_6;
    $xpressui_ctx['group'] = $xpressui_loop_value_8;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_7 + 1,
        'index0' => $xpressui_loop_index_7,
        'first'  => $xpressui_loop_index_7 === 0,
        'last'   => ($xpressui_loop_index_7 + 1) === count($xpressui_loop_items_5),
    ];
?>
        <article class="template-time-slot-availability-row">
          <a
            class="template-time-slot-resource-panel"
            href="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'catalog_page'), 'base_url'))); ?>/<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'url_id'))); ?>"
            data-resource-detail-link="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'key'))); ?>"
            aria-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'title'))); ?>"
          >
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'image_url'))): ?>
              <img class="template-time-slot-resource-avatar" src="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'image_url'))); ?>" alt="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'title'))); ?>" loading="lazy" />
<?php else: ?>
              <div class="template-time-slot-resource-avatar template-time-slot-resource-avatar--empty" aria-hidden="true"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_slice(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'title'), null, 1))); ?></div>
<?php endif; ?>
            <div class="template-time-slot-resource-copy">
              <h3><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'title'))); ?></h3>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'subtitle'))): ?>
<p class="template-time-slot-resource-subtitle"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'subtitle'))); ?></p><?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'location'))): ?>
<p class="template-time-slot-resource-meta"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'location'))); ?></p><?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'description'))): ?>
<p class="template-time-slot-resource-description"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'description'))); ?></p><?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'price_display'))): ?>
<p class="template-time-slot-resource-price"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'price_display'))); ?></p><?php endif; ?>
            </div>
          </a>

          <div class="template-time-slot-week-board" data-time-slot-window-board>
            <div class="template-time-slot-week-head" aria-hidden="true">
<?php
$xpressui_loop_parent_ctx_10 = $xpressui_ctx;
$xpressui_loop_items_9 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'day_slots'));
foreach ($xpressui_loop_items_9 as $xpressui_loop_index_11 => $xpressui_loop_value_12):
    $xpressui_ctx = $xpressui_loop_parent_ctx_10;
    $xpressui_ctx['day'] = $xpressui_loop_value_12;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_11 + 1,
        'index0' => $xpressui_loop_index_11,
        'first'  => $xpressui_loop_index_11 === 0,
        'last'   => ($xpressui_loop_index_11 + 1) === count($xpressui_loop_items_9),
    ];
?>
                <div class="template-time-slot-week-day" data-time-slot-date="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'day'), 'date'))); ?>" data-time-slot-window-day>
                  <span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'day'), 'weekday'))); ?></span>
                  <strong><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'day'), 'day'))); ?></strong>
                  <small><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'day'), 'month'))); ?></small>
                </div>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_10; ?>
            </div>
            <div class="template-time-slot-week-slots">
<?php
$xpressui_loop_parent_ctx_14 = $xpressui_ctx;
$xpressui_loop_items_13 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'day_slots'));
foreach ($xpressui_loop_items_13 as $xpressui_loop_index_15 => $xpressui_loop_value_16):
    $xpressui_ctx = $xpressui_loop_parent_ctx_14;
    $xpressui_ctx['day'] = $xpressui_loop_value_16;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_15 + 1,
        'index0' => $xpressui_loop_index_15,
        'first'  => $xpressui_loop_index_15 === 0,
        'last'   => ($xpressui_loop_index_15 + 1) === count($xpressui_loop_items_13),
    ];
?>
                <div class="template-time-slot-day-column" data-time-slot-date="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'day'), 'date'))); ?>" data-time-slot-window-day>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'day'), 'slots'))): ?>
<?php
$xpressui_loop_parent_ctx_18 = $xpressui_ctx;
$xpressui_loop_items_17 = xpressui_bridge_template_iterable(xpressui_bridge_template_slice(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'day'), 'slots'), null, 4));
foreach ($xpressui_loop_items_17 as $xpressui_loop_index_19 => $xpressui_loop_value_20):
    $xpressui_ctx = $xpressui_loop_parent_ctx_18;
    $xpressui_ctx['choice'] = $xpressui_loop_value_20;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_19 + 1,
        'index0' => $xpressui_loop_index_19,
        'first'  => $xpressui_loop_index_19 === 0,
        'last'   => ($xpressui_loop_index_19 + 1) === count($xpressui_loop_items_17),
    ];
?>
<?php $xpressui_ctx['slot_disabled'] = xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'disabled'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'slot_full')); ?>
                      <button
                        type="button"
                        class="template-time-slot-pill<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'slot_disabled'))): ?> template-time-slot-pill--disabled<?php endif; ?>"
                        data-choice-option-action="toggle"
                        data-choice-option-value="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'value'))); ?>"
                        data-slot-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'label'))); ?>"
                        data-selected="false"
                        data-disabled="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'slot_disabled')) ? "true" : "false"))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'starts_at'))): ?>
data-slot-starts-at="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'starts_at'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'ends_at'))): ?>
data-slot-ends-at="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'ends_at'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_test_none(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'capacity')))))): ?>
data-slot-capacity="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'capacity'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_test_none(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'booked_count')))))): ?>
data-slot-booked-count="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'booked_count'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_test_none(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'price')))))): ?>
data-slot-price="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'price'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'currency'))): ?>
data-slot-currency="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'currency'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'pricing_mode'))): ?>
data-slot-pricing-mode="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'pricing_mode'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_key'))): ?>
data-slot-resource-key="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_key'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_label'))): ?>
data-slot-resource-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_label'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_image_url'))): ?>
data-slot-resource-image="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_image_url'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_video_url'))): ?>
data-slot-resource-video-url="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_video_url'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'product_sku'))): ?>
data-slot-product-sku="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'product_sku'))); ?>"<?php endif; ?>
                        role="button"
                        aria-pressed="false"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'slot_disabled'))): ?>
aria-disabled="true" disabled<?php endif; ?>
                      >
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'slot_disabled'))): ?>
                          <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Full", 'xpressui-bridge'))); ?>
<?php else: ?>
                          <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'slot_start_time'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'label')))); ?>
<?php endif; ?>
                      </button>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_18; ?>
<?php if (xpressui_bridge_template_truthy((xpressui_bridge_template_filter_length(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'day'), 'slots')) > 4))): ?>
                      <span class="template-time-slot-more">+<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_filter_length(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'day'), 'slots')) - 4))); ?></span>
<?php endif; ?>
<?php else: ?>
                    <span class="template-time-slot-empty">-</span>
<?php endif; ?>
                </div>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_14; ?>
            </div>
<?php if (xpressui_bridge_template_truthy((xpressui_bridge_template_filter_length(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'group'), 'day_slots')) > 5))): ?>
              <div class="template-time-slot-window-nav" aria-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Availability navigation", 'xpressui-bridge'))); ?>">
                <button type="button" data-time-slot-window-nav="prev_week"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Previous week", 'xpressui-bridge'))); ?></button>
                <button type="button" data-time-slot-window-nav="next_week"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Next week", 'xpressui-bridge'))); ?></button>
                <button type="button" data-time-slot-window-nav="prev_month"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Previous month", 'xpressui-bridge'))); ?></button>
                <button type="button" data-time-slot-window-nav="next_month"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Next month", 'xpressui-bridge'))); ?></button>
              </div>
<?php endif; ?>
          </div>
        </article>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_6; ?>
<?php else: ?>
      <section class="template-time-slot-empty-state" data-time-slot-empty="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>">
        <div class="template-time-slot-empty-state-icon" aria-hidden="true">
          <span></span>
        </div>
        <div class="template-time-slot-empty-state-copy">
          <h2><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("No services are available yet", 'xpressui-bridge'))); ?></h2>
          <p><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("No bookable slots are available right now. Please check back later.", 'xpressui-bridge'))); ?></p>
        </div>
      </section>
<?php endif; ?>
  </div>

<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'desc'))): ?>
    <div class="template-field-help"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'desc'))); ?></div>
<?php endif; ?>
<?php xpressui_bridge_template_include_template('field-meta.php', $xpressui_ctx); ?>
</div>
