<?php
/**
 * Orders admin page.
 *
 * Lists the catalog-checkout submissions (those carrying a product cart) as orders,
 * mirroring the SaaS Orders view. Catalog orders are saved in WordPress by default
 * (see assets/catalog-checkout.js): the localStorage cart is injected into the
 * checkout form as hidden fields (xpressuiProductCart / Total / Currency / Count /
 * xpressuiPaymentMethod), so each order is an `xpressui_submission` whose payload
 * carries a non-empty product cart.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function xpressui_register_orders_page() {
	add_submenu_page(
		'edit.php?post_type=xpressui_submission',
		__( 'Orders', 'xpressui-bridge' ),
		__( 'Orders', 'xpressui-bridge' ),
		'manage_options',
		'xpressui-orders',
		'xpressui_render_orders_page'
	);
}

/**
 * Moves the "Orders" submenu item to right after "All Submissions" (the post-type
 * default item), regardless of registration order. Runs late so every submenu is
 * already registered.
 */
function xpressui_reorder_orders_submenu() {
	global $submenu;
	$parent = 'edit.php?post_type=xpressui_submission';
	if ( empty( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) {
		return;
	}
	$orders_item = null;
	foreach ( $submenu[ $parent ] as $item ) {
		if ( isset( $item[2] ) && 'xpressui-orders' === $item[2] ) {
			$orders_item = $item;
			break;
		}
	}
	if ( null === $orders_item ) {
		return;
	}
	$rebuilt = [];
	foreach ( $submenu[ $parent ] as $item ) {
		if ( isset( $item[2] ) && 'xpressui-orders' === $item[2] ) {
			continue; // drop the original; re-inserted right after "All Submissions"
		}
		$rebuilt[] = $item;
		if ( isset( $item[2] ) && $parent === $item[2] ) {
			$rebuilt[] = $orders_item;
		}
	}
	$submenu[ $parent ] = array_values( $rebuilt );
}

/**
 * Extracts the product cart from a submission payload, or null when it is not a
 * catalog order. Reads from payload.values (runtime submit) or the payload root.
 *
 * @param array|null $payload Decoded `_xpressui_payload_json`.
 * @return array|null { items, total, currency, method } or null.
 */
function xpressui_order_from_payload( $payload ) {
	if ( ! is_array( $payload ) ) {
		return null;
	}
	$values   = ( isset( $payload['values'] ) && is_array( $payload['values'] ) ) ? $payload['values'] : $payload;
	$cart_raw = $values['xpressuiProductCart'] ?? ( $payload['xpressuiProductCart'] ?? '' );
	$items    = is_string( $cart_raw ) ? json_decode( $cart_raw, true ) : ( is_array( $cart_raw ) ? $cart_raw : null );
	if ( empty( $items ) || ! is_array( $items ) ) {
		return null;
	}
	return [
		'items'    => $items,
		'total'    => (string) ( $values['xpressuiProductTotal'] ?? ( $payload['xpressuiProductTotal'] ?? '' ) ),
		'currency' => (string) ( $values['xpressuiProductCurrency'] ?? ( $payload['xpressuiProductCurrency'] ?? '' ) ),
		'method'   => (string) ( $values['xpressuiPaymentMethod'] ?? ( $payload['xpressuiPaymentMethod'] ?? '' ) ),
	];
}

/**
 * Best-effort customer label (email, then a name field) from a submission payload.
 *
 * @param array|null $payload Decoded payload.
 * @return string
 */
function xpressui_order_customer_label( $payload ) {
	$values = ( is_array( $payload ) && isset( $payload['values'] ) && is_array( $payload['values'] ) ) ? $payload['values'] : ( is_array( $payload ) ? $payload : [] );
	foreach ( [ 'email', 'Email', 'emailAddress', 'customerEmail' ] as $key ) {
		if ( ! empty( $values[ $key ] ) && is_string( $values[ $key ] ) ) {
			return $values[ $key ];
		}
	}
	$first = '';
	foreach ( [ 'firstName', 'first_name', 'firstname', 'name', 'fullName', 'lastName', 'last_name' ] as $key ) {
		if ( ! empty( $values[ $key ] ) && is_string( $values[ $key ] ) ) {
			$first = trim( $first . ' ' . $values[ $key ] );
		}
	}
	return $first !== '' ? $first : __( '(anonymous)', 'xpressui-bridge' );
}

/**
 * Comma-joined "Label ×qty" list for an order's items.
 *
 * @param array $items Cart items.
 * @return string
 */
function xpressui_order_items_label( $items ) {
	$parts = [];
	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$label = (string) ( $item['label'] ?? ( $item['id'] ?? '' ) );
		$qty   = max( 1, (int) ( $item['quantity'] ?? 1 ) );
		if ( '' === $label ) {
			continue;
		}
		$parts[] = $qty > 1 ? $label . ' ×' . $qty : $label;
	}
	return implode( ', ', $parts );
}

function xpressui_render_orders_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$ids = get_posts(
		[
			'post_type'      => 'xpressui_submission',
			'post_status'    => [ 'private', 'publish' ],
			'posts_per_page' => 500,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
		]
	);

	$orders = [];
	foreach ( $ids as $id ) {
		$payload = json_decode( (string) get_post_meta( $id, '_xpressui_payload_json', true ), true );
		$order   = xpressui_order_from_payload( $payload );
		if ( null === $order ) {
			continue;
		}
		$order['id']       = $id;
		$order['date']     = get_the_date( 'Y-m-d H:i', $id ) ?: '';
		$order['status']   = (string) get_post_meta( $id, '_xpressui_submission_status', true ) ?: 'new';
		$order['customer'] = xpressui_order_customer_label( $payload );
		$orders[]          = $order;
	}

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Orders', 'xpressui-bridge' ) . '</h1>';
	echo '<p class="description">' . esc_html__( 'Product-catalog checkout orders. Saved in WordPress by default.', 'xpressui-bridge' ) . '</p>';

	if ( empty( $orders ) ) {
		echo '<p>' . esc_html__( 'No orders yet.', 'xpressui-bridge' ) . '</p></div>';
		return;
	}

	echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
	echo '<th>' . esc_html__( 'Date', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Customer', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Items', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Total', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Payment', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Status', 'xpressui-bridge' ) . '</th>';
	echo '</tr></thead><tbody>';

	foreach ( $orders as $order ) {
		$edit  = get_edit_post_link( $order['id'] );
		$total = trim( $order['total'] . ' ' . $order['currency'] );
		echo '<tr>';
		echo '<td><a href="' . esc_url( (string) $edit ) . '">' . esc_html( $order['date'] ) . '</a></td>';
		echo '<td>' . esc_html( $order['customer'] ) . '</td>';
		echo '<td>' . esc_html( xpressui_order_items_label( $order['items'] ) ) . '</td>';
		echo '<td>' . esc_html( '' !== $total ? $total : '—' ) . '</td>';
		echo '<td>' . esc_html( '' !== $order['method'] ? $order['method'] : '—' ) . '</td>';
		echo '<td>' . esc_html( $order['status'] ) . '</td>';
		echo '</tr>';
	}

	echo '</tbody></table></div>';
}
