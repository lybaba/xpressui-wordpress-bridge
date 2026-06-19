<?php
/**
 * Headless time-slots (services / booking) catalog rendering for hosted links.
 *
 * Renders the booking list + resource detail server-side (SEO/LCP) from the synced
 * catalog.json snapshot, mirroring the SaaS Jinja templates
 * `export/_partials/time-slots-catalog/date-grouped-list.j2` and `resource-detail.j2`
 * so the bundled, self-contained `time-slots-booking.js` (copied verbatim from the
 * SaaS `booking-script.j2`) hydrates it via the same `data-*` hooks.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * First character of a string (multibyte-safe when mbstring is available),
 * used for the resource avatar initial.
 *
 * @param string $value
 * @return string
 */
function xpressui_ts_initial( $value ) {
	$value = (string) $value;
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 1 ) : substr( $value, 0, 1 );
}

/**
 * Resolves the active resource for a headless time-slots detail view from
 * `?xpui_product=<resource url_id>`, or null (list view).
 *
 * @param array $catalog Decoded catalog.json snapshot.
 * @return array|null The matching resource group, or null.
 */
function xpressui_get_time_slots_active_resource( $catalog ) {
	// filter_input() reads this public, read-only navigation var without touching the raw
	// superglobal, so no nonce is required; sanitize_text_field() preserves the prior sanitization.
	$raw = sanitize_text_field( (string) filter_input( INPUT_GET, 'xpui_product', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
	if ( '' === $raw || ! is_array( $catalog ) ) {
		return null;
	}
	$groups = ( isset( $catalog['resource_groups'] ) && is_array( $catalog['resource_groups'] ) ) ? $catalog['resource_groups'] : [];
	foreach ( $groups as $group ) {
		if ( is_array( $group ) && (string) ( $group['url_id'] ?? '' ) === $raw ) {
			return $group;
		}
	}
	return null;
}

/**
 * Renders one slot pill button, mirroring the SaaS slot button hooks.
 *
 * @param array $slot A slot choice from the snapshot.
 * @return string
 */
function xpressui_render_time_slot_pill( $slot ) {
	if ( ! is_array( $slot ) ) {
		return '';
	}
	$disabled = ! empty( $slot['disabled'] ) || ! empty( $slot['slot_full'] );
	$value    = (string) ( $slot['value'] ?? '' );
	$label    = (string) ( $slot['label'] ?? '' );
	$start    = (string) ( $slot['slot_start_time'] ?? '' );
	$text     = $disabled ? __( 'Full', 'xpressui-bridge' ) : ( '' !== $start ? $start : $label );
	$capacity = ( isset( $slot['capacity'] ) && null !== $slot['capacity'] ) ? (string) $slot['capacity'] : '';
	$price    = ( isset( $slot['price'] ) && null !== $slot['price'] ) ? (string) $slot['price'] : '';

	ob_start();
	?>
<button type="button"
	class="template-time-slot-pill<?php echo $disabled ? ' template-time-slot-pill--disabled' : ''; ?>"
	data-choice-option-action="toggle"
	data-choice-option-value="<?php echo esc_attr( $value ); ?>"
	data-slot-label="<?php echo esc_attr( $label ); ?>"
	data-selected="false"
	data-disabled="<?php echo $disabled ? 'true' : 'false'; ?>"
	data-slot-starts-at="<?php echo esc_attr( (string) ( $slot['starts_at'] ?? '' ) ); ?>"
	data-slot-ends-at="<?php echo esc_attr( (string) ( $slot['ends_at'] ?? '' ) ); ?>"
	data-slot-capacity="<?php echo esc_attr( $capacity ); ?>"
	data-slot-booked-count="<?php echo esc_attr( (string) ( $slot['booked_count'] ?? '' ) ); ?>"
	data-slot-price="<?php echo esc_attr( $price ); ?>"
	data-slot-currency="<?php echo esc_attr( (string) ( $slot['currency'] ?? '' ) ); ?>"
	data-slot-pricing-mode="<?php echo esc_attr( (string) ( $slot['pricing_mode'] ?? '' ) ); ?>"
	data-slot-resource-key="<?php echo esc_attr( (string) ( $slot['resource_key'] ?? '' ) ); ?>"
	data-slot-resource-label="<?php echo esc_attr( (string) ( $slot['resource_label'] ?? '' ) ); ?>"
	data-slot-resource-image="<?php echo esc_url( (string) ( $slot['resource_image_url'] ?? '' ) ); ?>"
	data-slot-resource-video-url="<?php echo esc_url( (string) ( $slot['resource_video_url'] ?? '' ) ); ?>"
	data-slot-product-sku="<?php echo esc_attr( (string) ( $slot['product_sku'] ?? '' ) ); ?>"
	role="button"
	aria-pressed="false"
	<?php echo $disabled ? 'aria-disabled="true" disabled' : ''; ?>
><?php echo esc_html( $text ); ?></button>
	<?php
	return (string) ob_get_clean();
}

/**
 * Renders the 4-direction window navigation (shown when a board has > 5 days).
 *
 * @return string
 */
function xpressui_render_time_slots_window_nav() {
	ob_start();
	?>
<div class="template-time-slot-window-nav">
	<button type="button" data-time-slot-window-nav="prev_week"><?php esc_html_e( 'Previous week', 'xpressui-bridge' ); ?></button>
	<button type="button" data-time-slot-window-nav="next_week"><?php esc_html_e( 'Next week', 'xpressui-bridge' ); ?></button>
	<button type="button" data-time-slot-window-nav="prev_month"><?php esc_html_e( 'Previous month', 'xpressui-bridge' ); ?></button>
	<button type="button" data-time-slot-window-nav="next_month"><?php esc_html_e( 'Next month', 'xpressui-bridge' ); ?></button>
</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Opens the field wrapper + hidden input + choice grid (shared by list & detail).
 *
 * @param array  $catalog     Snapshot.
 * @param string $extra_class Extra grid class (e.g. for the detail list).
 * @return string Opening HTML (callers must close </div></div>).
 */
function xpressui_render_time_slots_grid_open( $catalog, $extra_class = '' ) {
	$field    = ( isset( $catalog['field'] ) && is_array( $catalog['field'] ) ) ? $catalog['field'] : [];
	$name     = (string) ( $field['name'] ?? 'timeSlots' );
	$type     = (string) ( $field['type'] ?? 'radio-buttons' );
	$multiple = ! empty( $field['time_slot_allow_multiple_selection'] );
	$choices  = ( isset( $field['choices'] ) && is_array( $field['choices'] ) ) ? $field['choices'] : [];
	$template = (string) ( $catalog['template_id'] ?? 'date-grouped-list' );

	ob_start();
	?>
<div class="template-field" data-template-zone="field" data-field-name="<?php echo esc_attr( $name ); ?>" data-field-type="<?php echo esc_attr( $type ); ?>">
	<input type="hidden" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>"
		data-name="<?php echo esc_attr( $name ); ?>"
		data-label="<?php echo esc_attr( (string) ( $field['label'] ?? '' ) ); ?>"
		data-type="<?php echo esc_attr( $type ); ?>"
		data-section-name="time_slots"
		data-choices="<?php echo esc_attr( wp_json_encode( $choices ) ); ?>"
		data-choice-catalog-id="<?php echo esc_attr( (string) ( $field['choice_catalog_id'] ?? ( $catalog['catalog_id'] ?? '' ) ) ); ?>"
		data-choice-catalog-kind="time_slots"
		data-min-num-of-choices="<?php echo esc_attr( (string) ( $field['min_choices'] ?? 1 ) ); ?>"
	/>
	<div class="template-choice-grid template-choice-grid--vertical<?php echo '' !== $extra_class ? ' ' . esc_attr( $extra_class ) : ''; ?>"
		data-choice-list-grid="<?php echo esc_attr( $name ); ?>"
		data-choice-layout="vertical"
		data-choice-time-slots="true"
		data-time-slot-template="<?php echo esc_attr( $template ); ?>"
		data-time-slot-selection-mode="<?php echo $multiple ? 'multiple' : 'single'; ?>"
		data-time-slot-allow-multiple-selection="<?php echo $multiple ? 'true' : 'false'; ?>"
		data-time-slot-timezone="<?php echo esc_attr( (string) ( $field['time_slot_timezone'] ?? '' ) ); ?>"
		data-time-slot-display-mode="<?php echo esc_attr( (string) ( $field['time_slot_display_mode'] ?? '' ) ); ?>"
		data-time-slot-booking-mode="<?php echo esc_attr( (string) ( $field['time_slot_booking_mode'] ?? '' ) ); ?>"
		data-time-slot-linked-product-catalog-id="<?php echo esc_attr( (string) ( $field['time_slot_linked_product_catalog_id'] ?? '' ) ); ?>"
		data-time-slot-product-link-field="<?php echo esc_attr( (string) ( $field['time_slot_product_link_field'] ?? '' ) ); ?>"
	>
	<?php
	return (string) ob_get_clean();
}

/**
 * Renders the booking LIST (resource groups), mirroring date-grouped-list.j2.
 *
 * @param array  $catalog     Snapshot.
 * @param string $detail_base Resource detail base URL (cards link to {base}?xpui_product=url_id).
 * @return string
 */
function xpressui_render_time_slots_list( $catalog, $detail_base = '' ) {
	$groups = ( isset( $catalog['resource_groups'] ) && is_array( $catalog['resource_groups'] ) ) ? $catalog['resource_groups'] : [];
	$days   = ( isset( $catalog['calendar_days'] ) && is_array( $catalog['calendar_days'] ) ) ? $catalog['calendar_days'] : [];

	ob_start();
	echo xpressui_render_time_slots_grid_open( $catalog ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- internal markup, escaped at source.
	?>
	<div class="template-time-slot-date-anchors" aria-hidden="true">
		<?php foreach ( $days as $day ) : ?>
		<span id="time-slot-date-<?php echo esc_attr( (string) ( $day['date'] ?? '' ) ); ?>"></span>
		<?php endforeach; ?>
	</div>
	<?php if ( empty( $groups ) ) : ?>
	<section class="template-time-slot-empty-state" data-time-slot-empty="timeSlots">
		<div class="template-time-slot-empty-state-icon"></div>
		<div class="template-time-slot-empty-state-copy">
			<h2><?php esc_html_e( 'No services are available yet', 'xpressui-bridge' ); ?></h2>
			<p><?php esc_html_e( 'No bookable slots are available right now. Please check back later.', 'xpressui-bridge' ); ?></p>
		</div>
	</section>
	<?php else : ?>
		<?php foreach ( $groups as $group ) : ?>
			<?php
			if ( ! is_array( $group ) ) {
				continue;
			}
			$day_slots = ( isset( $group['day_slots'] ) && is_array( $group['day_slots'] ) ) ? $group['day_slots'] : [];
			$title     = (string) ( $group['title'] ?? '' );
			$image     = trim( (string) ( $group['image_url'] ?? '' ) );
			$url_id    = (string) ( $group['url_id'] ?? '' );
			$href      = ( '' !== $detail_base && '' !== $url_id ) ? add_query_arg( 'xpui_product', $url_id, $detail_base ) : '';
			?>
		<article class="template-time-slot-availability-row">
			<a class="template-time-slot-resource-panel" href="<?php echo esc_url( $href ); ?>" data-resource-detail-link="<?php echo esc_attr( (string) ( $group['key'] ?? '' ) ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
				<?php if ( '' !== $image ) : ?>
				<img class="template-time-slot-resource-avatar" src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
				<?php else : ?>
				<div class="template-time-slot-resource-avatar template-time-slot-resource-avatar--empty"><?php echo esc_html( xpressui_ts_initial( $title ) ); ?></div>
				<?php endif; ?>
				<div class="template-time-slot-resource-copy">
					<h3><?php echo esc_html( $title ); ?></h3>
					<?php if ( ! empty( $group['subtitle'] ) ) : ?><p class="template-time-slot-resource-subtitle"><?php echo esc_html( (string) $group['subtitle'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $group['location'] ) ) : ?><p class="template-time-slot-resource-meta"><?php echo esc_html( (string) $group['location'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $group['description'] ) ) : ?><p class="template-time-slot-resource-description"><?php echo esc_html( (string) $group['description'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $group['price_display'] ) ) : ?><p class="template-time-slot-resource-price"><?php echo esc_html( (string) $group['price_display'] ); ?></p><?php endif; ?>
				</div>
			</a>
			<?php $ts_cols = min( 5, max( 1, count( $day_slots ) ) ); ?>
			<div class="template-time-slot-week-board" data-time-slot-window-board data-ts-cols="<?php echo esc_attr( (string) $ts_cols ); ?>">
				<div class="template-time-slot-week-head" aria-hidden="true">
					<?php foreach ( $day_slots as $day ) : ?>
					<div class="template-time-slot-week-day" data-time-slot-date="<?php echo esc_attr( (string) ( $day['date'] ?? '' ) ); ?>" data-time-slot-window-day>
						<span><?php echo esc_html( (string) ( $day['weekday'] ?? '' ) ); ?></span>
						<strong><?php echo esc_html( (string) ( $day['day'] ?? '' ) ); ?></strong>
						<small><?php echo esc_html( (string) ( $day['month'] ?? '' ) ); ?></small>
					</div>
					<?php endforeach; ?>
				</div>
				<div class="template-time-slot-week-slots">
					<?php foreach ( $day_slots as $day ) : ?>
						<?php $slots = ( isset( $day['slots'] ) && is_array( $day['slots'] ) ) ? $day['slots'] : []; ?>
					<div class="template-time-slot-day-column" data-time-slot-date="<?php echo esc_attr( (string) ( $day['date'] ?? '' ) ); ?>" data-time-slot-window-day>
						<?php if ( ! empty( $slots ) ) : ?>
							<?php foreach ( array_slice( $slots, 0, 4 ) as $slot ) : ?>
								<?php echo xpressui_render_time_slot_pill( $slot ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped at source. ?>
							<?php endforeach; ?>
							<?php if ( count( $slots ) > 4 ) : ?><span class="template-time-slot-more">+<?php echo esc_html( (string) ( count( $slots ) - 4 ) ); ?></span><?php endif; ?>
						<?php else : ?>
						<span class="template-time-slot-empty">-</span>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>
				<?php if ( count( $day_slots ) > 5 ) : ?>
					<?php echo xpressui_render_time_slots_window_nav(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped at source. ?>
				<?php endif; ?>
			</div>
		</article>
		<?php endforeach; ?>
	<?php endif; ?>
	</div>
</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Renders the resource DETAIL (one resource, all its slots), mirroring resource-detail.j2.
 *
 * @param array $catalog  Snapshot.
 * @param array $resource The active resource group.
 * @return string
 */
function xpressui_render_time_slots_resource_detail( $catalog, $resource ) {
	if ( ! is_array( $resource ) ) {
		return '';
	}
	$day_slots = ( isset( $resource['day_slots'] ) && is_array( $resource['day_slots'] ) ) ? $resource['day_slots'] : [];
	$title     = (string) ( $resource['title'] ?? '' );
	$image     = trim( (string) ( $resource['image_url'] ?? '' ) );

	ob_start();
	echo xpressui_render_time_slots_grid_open( $catalog, 'template-time-slot-availability-list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped at source.
	?>
	<section class="template-time-slot-resource-detail">
		<div class="template-time-slot-resource-detail-layout">
			<div class="template-time-slot-resource-detail-media">
				<?php if ( '' !== $image ) : ?>
				<img src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy" />
				<?php else : ?>
				<div class="template-time-slot-resource-detail-empty"><?php echo esc_html( xpressui_ts_initial( $title ) ); ?></div>
				<?php endif; ?>
			</div>
			<div class="template-time-slot-resource-detail-copy">
				<p class="template-time-slot-calendar-kicker"><?php esc_html_e( 'Resource', 'xpressui-bridge' ); ?></p>
				<h1><?php echo esc_html( $title ); ?></h1>
				<?php if ( ! empty( $resource['location'] ) ) : ?><p class="template-time-slot-resource-meta"><?php echo esc_html( (string) $resource['location'] ); ?></p><?php endif; ?>
				<?php if ( ! empty( $resource['description'] ) ) : ?><p class="template-time-slot-resource-description"><?php echo esc_html( (string) $resource['description'] ); ?></p><?php endif; ?>
				<?php if ( ! empty( $resource['price_display'] ) ) : ?><p class="template-time-slot-resource-price"><?php echo esc_html( (string) $resource['price_display'] ); ?></p><?php endif; ?>
				<div class="template-time-slot-detail-days" data-time-slot-window-board>
					<?php foreach ( $day_slots as $day ) : ?>
						<?php $slots = ( isset( $day['slots'] ) && is_array( $day['slots'] ) ) ? $day['slots'] : []; ?>
					<div class="template-time-slot-detail-day" data-time-slot-date="<?php echo esc_attr( (string) ( $day['date'] ?? '' ) ); ?>" data-time-slot-window-day>
						<h2><?php echo esc_html( trim( ( $day['weekday'] ?? '' ) . ' ' . ( $day['day'] ?? '' ) . ' ' . ( $day['month'] ?? '' ) ) ); ?></h2>
						<div class="template-time-slot-detail-pills">
							<?php if ( ! empty( $slots ) ) : ?>
								<?php foreach ( $slots as $slot ) : ?>
									<?php echo xpressui_render_time_slot_pill( $slot ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped at source. ?>
								<?php endforeach; ?>
							<?php else : ?>
							<span class="template-time-slot-empty">-</span>
							<?php endif; ?>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<?php if ( count( $day_slots ) > 5 ) : ?>
					<?php echo xpressui_render_time_slots_window_nav(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped at source. ?>
				<?php endif; ?>
			</div>
		</div>
	</section>
	</div>
</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Formats a booking window from ISO-8601 start/end into a short human string,
 * e.g. "Tue 02 Jun · 17:00–21:00" (single day) or "02 Jun 17:00 → 03 Jun 09:00".
 *
 * @param string $starts ISO-8601 start.
 * @param string $ends   ISO-8601 end.
 * @return string
 */
function xpressui_format_booking_window( $starts, $ends ) {
	$starts = trim( (string) $starts );
	if ( '' === $starts ) {
		return '';
	}
	try {
		$s = new DateTime( $starts );
	} catch ( Exception $e ) {
		return '';
	}
	$e_dt = null;
	if ( '' !== trim( (string) $ends ) ) {
		try {
			$e_dt = new DateTime( $ends );
		} catch ( Exception $e ) {
			$e_dt = null;
		}
	}
	$day = $s->format( 'D d M' );
	if ( null === $e_dt ) {
		return $day . ' · ' . $s->format( 'H:i' );
	}
	if ( $s->format( 'Y-m-d' ) === $e_dt->format( 'Y-m-d' ) ) {
		return $day . ' · ' . $s->format( 'H:i' ) . '–' . $e_dt->format( 'H:i' );
	}
	return $s->format( 'd M H:i' ) . ' → ' . $e_dt->format( 'd M H:i' );
}

/**
 * Formats an amount + currency, e.g. "150 000 XOF" (space thousands separator).
 *
 * @param string $amount Numeric amount.
 * @param string $currency Currency code.
 * @return string
 */
function xpressui_format_money_amount( $amount, $currency ) {
	$amount = trim( (string) $amount );
	if ( '' === $amount || ! is_numeric( $amount ) ) {
		return '';
	}
	$n    = (float) $amount;
	$text = ( floor( $n ) === $n )
		? number_format( $n, 0, ',', ' ' )
		: number_format( $n, 2, ',', ' ' );
	$currency = trim( (string) $currency );
	return '' !== $currency ? $text . ' ' . $currency : $text;
}

/**
 * Builds the time-slots checkout artifacts from the selected slot (carried in the
 * query params the booking script appends): the collapsed "Booking summary" bar
 * (shown inside the form card, parity with the SaaS) and the hidden fields that
 * capture the booking in the WordPress submission (stored as a one-item product cart
 * so it also surfaces on the Orders page).
 *
 * @return array{summary:string,fields:string} Empty strings when no slot is selected.
 */
function xpressui_render_time_slots_checkout() {
	// These are public, read-only query vars set by the booking redirect. filter_input() reads
	// them without touching the raw superglobal, so no nonce is required; sanitize_text_field()
	// preserves the prior sanitization.
	$get = static function ( $key ) {
		return sanitize_text_field( (string) filter_input( INPUT_GET, $key, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
	};
	$slot_id = $get( 'timeSlotId' );
	if ( '' === $slot_id ) {
		return [ 'summary' => '', 'fields' => '' ];
	}
	$label    = $get( 'timeSlotLabel' );
	$resource = $get( 'timeSlotResource' );
	$starts   = $get( 'timeSlotStartsAt' );
	$ends     = $get( 'timeSlotEndsAt' );
	$price    = $get( 'timeSlotPrice' );
	$currency = $get( 'timeSlotCurrency' );
	// filter_input() reads this public, read-only query var without touching the raw superglobal,
	// so no nonce is required; esc_url_raw() preserves the prior URL sanitization.
	$image    = esc_url_raw( (string) filter_input( INPUT_GET, 'timeSlotResourceImage', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );

	$title         = '' !== $resource ? $resource : ( '' !== $label ? $label : $slot_id );
	$window        = xpressui_format_booking_window( $starts, $ends );
	$price_display = xpressui_format_money_amount( $price, $currency );

	// Styles for the collapsed bar live in the cart-summary stylesheet.
	$css_path = XPRESSUI_BRIDGE_DIR . 'assets/catalog-cart-summary.css';
	$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : XPRESSUI_BRIDGE_VERSION;
	wp_enqueue_style( 'xpressui-cart-summary', XPRESSUI_BRIDGE_URL . 'assets/catalog-cart-summary.css', [], $css_ver );

	// Capture the booking as a one-item product cart so the WordPress submission +
	// the Orders page treat it like any other order.
	$cart_item = [
		'id'       => $slot_id,
		'label'    => $title,
		'quantity' => 1,
		'price'    => is_numeric( $price ) ? (float) $price : 0,
		'currency' => $currency,
		'image'    => $image,
		'startsAt' => $starts,
		'endsAt'   => $ends,
	];
	$fields  = '<input type="hidden" name="xpressuiProductCart" value="' . esc_attr( wp_json_encode( [ $cart_item ] ) ) . '" />';
	$fields .= '<input type="hidden" name="xpressuiProductTotal" value="' . esc_attr( is_numeric( $price ) ? $price : '' ) . '" />';
	$fields .= '<input type="hidden" name="xpressuiProductCurrency" value="' . esc_attr( $currency ) . '" />';
	$fields .= '<input type="hidden" name="xpressuiProductCount" value="1" />';

	ob_start();
	?>
<details id="xpui-cart-summary" class="xpui-cart-summary" open aria-label="<?php esc_attr_e( 'Booking summary', 'xpressui-bridge' ); ?>">
	<summary class="xpui-cs-header">
		<span class="xpui-cs-title"><?php esc_html_e( 'Booking summary', 'xpressui-bridge' ); ?></span>
		<?php if ( '' !== $price_display ) : ?><strong class="xpui-cs-header-total"><?php echo esc_html( $price_display ); ?></strong><?php endif; ?>
	</summary>
	<div class="xpui-cs-body">
		<div class="xpui-cs-row">
			<span class="xpui-cs-item">
				<?php if ( '' !== $image ) : ?><img class="xpui-cs-thumb" src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy"><?php endif; ?>
				<span class="xpui-cs-label">
					<span class="xpui-cs-label-main"><?php echo esc_html( $title ); ?></span>
					<?php if ( '' !== $window ) : ?><span class="xpui-cs-meta xpui-cs-meta--booking-period"><?php echo esc_html( $window ); ?></span><?php endif; ?>
				</span>
			</span>
			<?php if ( '' !== $price_display ) : ?><span class="xpui-cs-amount"><?php echo esc_html( $price_display ); ?></span><?php endif; ?>
		</div>
	</div>
</details>
	<?php
	$summary = wp_kses( (string) ob_get_clean(), xpressui_get_shell_allowed_html() );
	return [ 'summary' => $summary, 'fields' => $fields ];
}

/**
 * Renders the full headless time-slots embed (list or resource detail).
 *
 * Mirrors `export/catalog-time-slots-page.html.j2`: the page-shell > form-frame >
 * landing wrapper (carrying the booking URLs) > the list or detail field. Enqueues
 * the ported time-slots CSS + the bundled booking script. The whole journey stays on
 * WordPress: selecting a slot redirects to the WP checkout (?xpui_checkout=1) with the
 * slot details as query params (no SaaS call).
 *
 * @param array  $catalog      Snapshot.
 * @param string $link_id      Hosted link id.
 * @param string $grid_url     Booking list URL (back link / return).
 * @param string $checkout_url WP checkout URL the slot selection navigates to.
 * @return string
 */
function xpressui_render_time_slots_embed( $catalog, $link_id, $grid_url, $checkout_url ) {
	$mount_id = 'xpressui-root';

	// Styles + theme vars (the CSS resolves var(--template-*)).
	$css_path = XPRESSUI_BRIDGE_DIR . 'assets/time-slots-catalog.css';
	$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : XPRESSUI_BRIDGE_VERSION;
	wp_enqueue_style( 'xpressui-time-slots', XPRESSUI_BRIDGE_URL . 'assets/time-slots-catalog.css', [], $css_ver );
	$vars = is_array( $catalog['theme_css_vars'] ?? null ) ? $catalog['theme_css_vars'] : [];
	wp_add_inline_style( 'xpressui-time-slots', xpressui_catalog_theme_vars_css( '#' . $mount_id, $vars ) );
	// Blend into the host WordPress page: drop the full-viewport shell + gradient and the
	// 1440px-centered frame padding (which overflow / float the catalog on a content
	// column), and let a narrow booking board scroll horizontally instead of overflowing.
	$sel    = '#' . $mount_id;
	$reset  = $sel . '.page-shell--time-slots-catalog{min-height:auto !important;background:transparent !important;padding:clamp(24px,4vw,40px) 0 !important;display:block !important;place-items:unset !important;overflow:visible !important;}';
	$reset .= $sel . ' .form-frame--time-slots-catalog{width:100% !important;max-width:100% !important;margin:0 !important;padding:0 !important;box-sizing:border-box;}';
	$reset .= $sel . ' .template-time-slot-availability-row{max-width:100% !important;width:100% !important;float:none !important;box-sizing:border-box !important;}';
	$reset .= $sel . ' .template-time-slot-week-board{min-width:0;overflow:visible;}';
	$reset .= $sel . ' .template-time-slot-week-slots,' . $sel . ' .template-time-slot-resource-panel{min-width:0;}';
	$reset .= $sel . ' .template-time-slot-day-column,' . $sel . ' .template-time-slot-pill{min-width:0;box-sizing:border-box;max-width:100%;}';
	// The week grid is a fixed 5 columns at minmax(78px,1fr) (~390px min): on a narrow WP
	// content column the columns are too tight and the pills overlap. Size the grid to the
	// actual number of days (data-ts-cols, ≤5) so the columns fit without overlap/scroll.
	for ( $c = 1; $c <= 5; $c++ ) {
		$reset .= $sel . ' .template-time-slot-week-board[data-ts-cols="' . $c . '"] .template-time-slot-week-head,'
			. $sel . ' .template-time-slot-week-board[data-ts-cols="' . $c . '"] .template-time-slot-week-slots'
			. '{grid-template-columns:repeat(' . $c . ',minmax(0,1fr)) !important;}';
	}
	// Scroll-margin-top offset to avoid content being hidden behind sticky theme headers.
	$reset .= $sel . ',' . $sel . ' *{scroll-margin-top:calc(var(--xpressui-header-offset,0px) + 24px) !important;}';

	wp_add_inline_style( 'xpressui-time-slots', $reset );

	wp_enqueue_script(
		'xpressui-header-offset',
		XPRESSUI_BRIDGE_URL . 'assets/shell/xpressui-header-offset.js',
		[],
		XPRESSUI_BRIDGE_VERSION,
		true
	);

	// Self-contained booking hydration (copied from the SaaS booking-script).
	$js_path = XPRESSUI_BRIDGE_DIR . 'assets/time-slots-booking.js';
	$js_ver  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : XPRESSUI_BRIDGE_VERSION;
	wp_enqueue_script( 'xpressui-time-slots-booking', XPRESSUI_BRIDGE_URL . 'assets/time-slots-booking.js', [ 'xpressui-header-offset' ], $js_ver, true );

	$active = xpressui_get_time_slots_active_resource( $catalog );
	$inner  = is_array( $active )
		? xpressui_render_time_slots_resource_detail( $catalog, $active )
		: xpressui_render_time_slots_list( $catalog, $grid_url );

	ob_start();
	?>
<div class="xpressui-embed-wrapper xpressui-inline-embed">
	<div id="<?php echo esc_attr( $mount_id ); ?>" class="xpressui-embed page-shell page-shell--catalog-public page-shell--time-slots-catalog" data-template-zone="page_shell" data-hosted-link-id="<?php echo esc_attr( (string) $link_id ); ?>">
		<main class="form-frame form-frame--catalog-public form-frame--catalog-landing form-frame--time-slots-catalog">
			<div class="template-catalog-time-slots-landing"
				data-template-zone="workflow_time_slots_landing"
				data-time-slot-checkout-url="<?php echo esc_url( $checkout_url ); ?>"
				data-time-slot-cart-summary-url=""
				data-time-slot-cart-token-url=""
				data-time-slot-catalog-return-url="<?php echo esc_url( $grid_url ); ?>"
			>
				<section class="template-catalog-slots-body">
					<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped at source. ?>
				</section>
			</div>
		</main>
	</div>
</div>
	<?php
	return wp_kses( (string) ob_get_clean(), xpressui_get_shell_allowed_html() );
}
