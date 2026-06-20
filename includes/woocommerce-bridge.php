<?php
/**
 * WooCommerce integration bridge for IntakeFlow storefront catalogs.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize WooCommerce bridge hooks.
 */
function xpressui_woocommerce_bridge_init() {
	if ( get_option( 'xpressui_enable_woocommerce', '0' ) !== '1' ) {
		return;
	}

	add_action( 'template_redirect', 'xpressui_handle_woocommerce_checkout_redirect' );
	add_action( 'wp_footer', 'xpressui_inject_woocommerce_checkout_interceptor' );
}
add_action( 'init', 'xpressui_woocommerce_bridge_init' );

/**
 * Intercepts the checkout redirect from the storefront catalog and handles
 * adding products to the WooCommerce cart before forwarding to WooCommerce checkout.
 */
function xpressui_handle_woocommerce_checkout_redirect() {
	if ( ! isset( $_GET['xpressui_wc_checkout'] ) || '1' !== $_GET['xpressui_wc_checkout'] ) {
		return;
	}

	if ( ! class_exists( 'WooCommerce' ) || null === WC()->cart ) {
		return;
	}

	$raw_cart = isset( $_GET['cart_data'] ) ? sanitize_text_field( wp_unslash( $_GET['cart_data'] ) ) : '';
	if ( empty( $raw_cart ) ) {
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	$cart_json = base64_decode( $raw_cart );
	if ( ! $cart_json ) {
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	$cart_items = json_decode( $cart_json, true );
	if ( ! is_array( $cart_items ) ) {
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	// Empty the WooCommerce cart to start fresh for this catalog order
	WC()->cart->empty_cart();

	foreach ( $cart_items as $item ) {
		if ( ! is_array( $item ) || empty( $item['id'] ) ) {
			continue;
		}

		$item_id   = sanitize_title( $item['id'] );
		$item_name = isset( $item['label'] ) ? sanitize_text_field( $item['label'] ) : $item_id;
		$item_price = isset( $item['price'] ) ? floatval( $item['price'] ) : 0.0;
		$qty       = isset( $item['quantity'] ) ? max( 1, intval( $item['quantity'] ) ) : 1;
		$sku       = 'xpressui_' . $item_id;

		// Find or create virtual WooCommerce product
		$product_id = xpressui_get_or_create_virtual_wc_product( $sku, $item_name, $item_price );

		if ( $product_id ) {
			WC()->cart->add_to_cart( $product_id, $qty );
		}
	}

	// Redirect directly to the native WooCommerce checkout page
	wp_safe_redirect( wc_get_checkout_url() );
	exit;
}

/**
 * Gets the ID of a WooCommerce product by SKU, or creates a simple virtual product if it doesn't exist.
 *
 * @param string $sku   Unique product SKU.
 * @param string $name  Product name.
 * @param float  $price Product regular price.
 * @return int|false Product ID, or false on failure.
 */
function xpressui_get_or_create_virtual_wc_product( $sku, $name, $price ) {
	$product_id = wc_get_product_id_by_sku( $sku );
	if ( $product_id ) {
		return $product_id;
	}

	try {
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$product->set_regular_price( $price );
		$product->set_sku( $sku );
		$product->set_virtual( true );
		$product->set_sold_individually( false );
		$product->set_manage_stock( false );
		$product->set_stock_status( 'instock' );
		$product->set_status( 'publish' );
		$product_id = $product->save();
		return $product_id;
	} catch ( Exception $e ) {
		error_log( 'IntakeFlow WooCommerce Bridge creation failed: ' . $e->getMessage() );
		return false;
	}
}

/**
 * Injects the JavaScript capturing interceptor in the footer to override the Proceed to Checkout click.
 */
function xpressui_inject_woocommerce_checkout_interceptor() {
	?>
	<script type="text/javascript">
	document.addEventListener('click', function(e) {
		var btn = e.target.closest('[data-cart-checkout]');
		if (btn && window.localStorage) {
			var root = btn.closest('[data-storage-key]');
			if (root) {
				var storageKey = root.getAttribute('data-storage-key');
				var raw = window.localStorage.getItem(storageKey);
				if (raw) {
					var parsed = JSON.parse(raw);
					var cart = parsed && Array.isArray(parsed.cart) ? parsed.cart : [];
					if (cart.length > 0) {
						e.preventDefault();
						e.stopPropagation();
						// Serialize cart to base64
						var cartData = btoa(unescape(encodeURIComponent(JSON.stringify(cart))));
						var sep = window.location.search ? '&' : '?';
						// Clean query parameters from current URL
						var cleanSearch = window.location.search
							.replace(/[\?&]xpui_cart=[^&]*/, '')
							.replace(/[\?&]xpui_checkout=[^&]*/, '');
						if (cleanSearch && cleanSearch.charAt(0) !== '?') {
							cleanSearch = '?' + cleanSearch;
						}
						var dest = window.location.origin + window.location.pathname + cleanSearch;
						sep = dest.indexOf('?') === -1 ? '?' : '&';
						window.location.href = dest + sep + 'xpressui_wc_checkout=1&cart_data=' + encodeURIComponent(cartData);
					}
				}
			}
		}
	}, true); // Use capture phase to intercept before plugin default script
	</script>
	<?php
}
