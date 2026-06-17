<?php
/**
 * Headless product-catalog rendering for synced Hosted Links.
 *
 * Renders the product grid server-side (SEO / LCP) from the synced
 * `catalogs/catalog.json` snapshot, mirroring the SaaS Jinja template
 * `export/_partials/product-catalog/ecommerce-grid.j2` so the XPressUI UMD
 * runtime hydrates the cart via the same `data-product-*` hooks. The cart
 * checkout flow targets the SaaS hosted checkout (see catalog cart-init).
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the synced product-catalog snapshot for a hosted link.
 *
 * @param string $project_slug Workflow slug.
 * @param string $link_id      Hosted link id.
 * @return array|null Decoded catalog.json, or null when absent/invalid.
 */
function xpressui_get_hosted_link_catalog( $project_slug, $link_id ) {
	$project_slug = sanitize_file_name( (string) $project_slug );
	$link_id      = sanitize_file_name( (string) $link_id );
	if ( '' === $project_slug || '' === $link_id ) {
		return null;
	}
	$uploads = wp_get_upload_dir();
	$file    = trailingslashit( $uploads['basedir'] ) . 'xpressui/' . $project_slug
		. '/hosted-links/' . $link_id . '/catalogs/catalog.json';
	if ( ! is_readable( $file ) ) {
		return null;
	}
	$decoded = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! is_array( $decoded ) || empty( $decoded['product_items'] ) || ! is_array( $decoded['product_items'] ) ) {
		return null;
	}
	return $decoded;
}

/**
 * Renders the headless product-catalog grid HTML from a catalog snapshot.
 *
 * Mirrors export/_partials/product-catalog/ecommerce-grid.j2. The `data-product-*`
 * hooks match what form-ui.choice-catalog-runtime.ts binds to, so the runtime
 * hydrates the cart (stepper, count/total, checkout).
 *
 * @param array  $catalog       Decoded catalog.json snapshot.
 * @param string $detail_base   Optional product-detail base URL (for card links).
 * @return string Catalog grid HTML (kses-safe via the embed allowed-HTML list).
 */
function xpressui_render_product_catalog_grid( $catalog, $detail_base = '' ) {
	if ( ! is_array( $catalog ) ) {
		return '';
	}
	$items = ( isset( $catalog['product_items'] ) && is_array( $catalog['product_items'] ) ) ? $catalog['product_items'] : [];
	if ( empty( $items ) ) {
		return '';
	}

	$cart_enabled    = ! empty( $catalog['cart_enabled'] );
	$supports_many   = ! empty( $catalog['cart_supports_multiple_items'] );
	$render_mode     = (string) ( $catalog['render_mode'] ?? 'add-to-cart' );
	$catalog_id      = (string) ( $catalog['catalog_id'] ?? '' );
	$template_id     = (string) ( $catalog['template_id'] ?? '' );
	$cart_max_qty    = (int) ( $catalog['cart_max_quantity'] ?? 0 );
	$items_per_page  = (int) ( $catalog['items_per_page'] ?? 0 );
	$grid_columns    = (int) ( $catalog['grid_columns'] ?? 0 );
	$cta_label       = trim( (string) ( $catalog['cta_label'] ?? '' ) );
	if ( '' === $cta_label ) {
		$cta_label = __( 'Buy now', 'xpressui-bridge' );
	}
	$cart_label = __( 'Cart', 'xpressui-bridge' );

	ob_start();
	?>
<div class="template-product-landing" data-template-zone="workflow_product_landing" data-workflow-product-landing>
	<div class="template-product-landing-nav" data-product-landing-nav hidden></div>
	<?php if ( $cart_enabled ) : ?>
	<div class="xpui-product-cart-toolbar">
		<button type="button" class="xpui-cart-trigger" data-product-cart-trigger data-empty="true" aria-label="<?php echo esc_attr( $cta_label ); ?>">
			<span class="xpui-cart-trigger-icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" focusable="false"><path d="M6.5 6.5h14l-1.6 7.1a2 2 0 0 1-2 1.6H9.2a2 2 0 0 1-2-1.6L5.6 4.8H3"></path><circle cx="9.5" cy="19" r="1.2"></circle><circle cx="17" cy="19" r="1.2"></circle></svg>
			</span>
			<span class="xpui-sr-only"><?php echo esc_html( $cart_label ); ?></span>
			<span class="xpui-cart-trigger-count" data-product-cart-trigger-count>0</span>
			<span class="xpui-cart-trigger-total" data-product-cart-trigger-total></span>
		</button>
	</div>
	<?php endif; ?>
	<section
		class="template-product-catalog-section"
		data-template-zone="workflow_product_catalog"
		data-section-name="product_catalog"
		data-workflow-product-catalog
		data-product-catalog-id="<?php echo esc_attr( $catalog_id ); ?>"
		data-product-template-id="<?php echo esc_attr( $template_id ); ?>"
		data-render-mode="<?php echo esc_attr( $render_mode ); ?>"
		<?php echo $cart_max_qty ? 'data-cart-max-quantity="' . esc_attr( (string) $cart_max_qty ) . '"' : ''; ?>
		<?php echo $items_per_page ? 'data-items-per-page="' . esc_attr( (string) $items_per_page ) . '"' : ''; ?>
	>
		<div class="template-product-landing-search" data-product-landing-search hidden></div>
		<div class="template-product-grid" data-workflow-product-grid<?php echo $grid_columns ? ' data-grid-columns="' . esc_attr( (string) $grid_columns ) . '"' : ''; ?>>
			<?php foreach ( $items as $item ) : ?>
				<?php
				if ( ! is_array( $item ) ) {
					continue;
				}
				$id            = (string) ( $item['id'] ?? '' );
				$label         = (string) ( $item['label'] ?? '' );
				$image         = trim( (string) ( $item['image_thumbnail'] ?? '' ) );
				$category      = (string) ( $item['category'] ?? '' );
				$unit          = (string) ( $item['unit'] ?? '' );
				$description   = (string) ( $item['description'] ?? '' );
				$price         = ( isset( $item['price'] ) && null !== $item['price'] ) ? (string) $item['price'] : '';
				$price_display = (string) ( $item['price_display'] ?? '' );
				$member_price  = ( isset( $item['member_price'] ) && null !== $item['member_price'] ) ? (string) $item['member_price'] : '';
				$member_disp   = (string) ( $item['member_price_display'] ?? '' );
				$max_qty       = ( isset( $item['max_quantity'] ) && null !== $item['max_quantity'] ) ? (string) $item['max_quantity'] : '';
				$detail_link   = ( '' !== $detail_base && '' !== $id ) ? add_query_arg( 'xpui_product', $id, $detail_base ) : '';
				?>
			<article
				class="template-product-card"
				data-product-id="<?php echo esc_attr( $id ); ?>"
				data-product-label="<?php echo esc_attr( $label ); ?>"
				data-product-sku="<?php echo esc_attr( (string) ( $item['sku'] ?? '' ) ); ?>"
				data-product-price="<?php echo esc_attr( $price ); ?>"
				data-product-price-display="<?php echo esc_attr( $price_display ); ?>"
				data-product-currency="<?php echo esc_attr( (string) ( $item['currency'] ?? '' ) ); ?>"
				data-product-category="<?php echo esc_attr( $category ); ?>"
				data-product-unit="<?php echo esc_attr( $unit ); ?>"
				data-product-description="<?php echo esc_attr( $description ); ?>"
				data-product-image="<?php echo esc_attr( $image ); ?>"
				data-product-max-quantity="<?php echo esc_attr( $max_qty ); ?>"
				data-product-member-price="<?php echo esc_attr( $member_price ); ?>"
				data-product-member-price-display="<?php echo esc_attr( $member_disp ); ?>"
				data-has-image="<?php echo $image ? 'true' : 'false'; ?>"
				data-product-in-cart-label="<?php esc_attr_e( 'In cart', 'xpressui-bridge' ); ?>"
				data-product-sold-out-label="<?php esc_attr_e( 'Sold out', 'xpressui-bridge' ); ?>"
			>
				<?php if ( '' !== $image ) : ?>
				<?php if ( '' !== $detail_link ) : ?><a class="template-product-media" href="<?php echo esc_url( $detail_link ); ?>" data-product-detail-link><?php endif; ?>
					<img src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy">
				<?php if ( '' !== $detail_link ) : ?></a><?php endif; ?>
				<?php endif; ?>
				<?php if ( '' !== $category ) : ?>
				<span class="template-product-category-chip"><?php echo esc_html( $category ); ?></span>
				<?php endif; ?>
				<h3 class="template-product-title">
					<?php if ( '' !== $detail_link ) : ?>
					<a href="<?php echo esc_url( $detail_link ); ?>" data-product-detail-link><?php echo esc_html( '' !== $label ? $label : $id ); ?></a>
					<?php else : ?>
					<?php echo esc_html( '' !== $label ? $label : $id ); ?>
					<?php endif; ?>
				</h3>
				<div class="template-product-meta">
					<?php if ( '' !== $price_display ) : ?><span class="template-product-price" data-product-price-label><?php echo esc_html( $price_display ); ?></span><?php endif; ?>
					<?php if ( '' !== $member_disp ) : ?><span class="template-product-price template-product-price--member" data-product-member-price-label style="display:none"><?php echo esc_html( $member_disp ); ?></span><?php endif; ?>
					<?php if ( '' !== $unit ) : ?><span><?php echo esc_html( $unit ); ?></span><?php endif; ?>
				</div>
				<?php if ( '' !== $description ) : ?>
				<p class="template-product-description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
				<?php if ( $cart_enabled ) : ?>
				<div class="xpui-product-card-actions">
					<?php if ( $supports_many ) : ?>
					<div class="xpui-product-controls">
						<button type="button" class="xpui-product-action-btn" data-product-decrease aria-label="<?php esc_attr_e( 'Decrease quantity', 'xpressui-bridge' ); ?>">-</button>
						<span class="xpui-product-qty-label" data-product-quantity>0</span>
						<button type="button" class="xpui-product-action-btn xpui-product-action-btn--add" data-product-increase aria-label="<?php esc_attr_e( 'Increase quantity', 'xpressui-bridge' ); ?>">+</button>
					</div>
					<?php endif; ?>
					<button type="button" class="xpui-product-buy-now-btn" data-product-card-buy-now><?php echo esc_html( $cta_label ); ?></button>
				</div>
				<?php endif; ?>
				<span class="template-product-sold-out-badge" aria-hidden="true"><?php esc_html_e( 'Sold out', 'xpressui-bridge' ); ?></span>
			</article>
			<?php endforeach; ?>
		</div>
		<nav class="xpui-product-pagination" data-product-pagination aria-label="<?php esc_attr_e( 'Products pagination', 'xpressui-bridge' ); ?>" hidden>
			<button type="button" class="xpui-product-pagination-btn" data-product-pagination-prev aria-label="<?php esc_attr_e( 'Previous page', 'xpressui-bridge' ); ?>" disabled>&larr;</button>
			<span class="xpui-product-pagination-info" data-product-pagination-info></span>
			<button type="button" class="xpui-product-pagination-btn" data-product-pagination-next aria-label="<?php esc_attr_e( 'Next page', 'xpressui-bridge' ); ?>">&rarr;</button>
		</nav>
		<p class="template-field-error" data-workflow-product-error hidden><?php esc_html_e( 'Please select a product before continuing.', 'xpressui-bridge' ); ?></p>
	</section>
</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Renders a complete headless product-catalog embed for a hosted link.
 *
 * Mirrors the SaaS catalog page (export/catalog-page.html.j2): the product grid
 * (server-rendered for SEO/LCP) inside a `page-shell` > `form-frame`, plus a hidden
 * cart-state form shell, the `__xpuiCatalog*` globals, and the SaaS `catalog-init.js`.
 *
 * `catalog-init.js` is fully self-contained for cart + checkout (no XPressUI runtime
 * UMD dependency): it binds the `data-product-*` hooks, persists the cart in the
 * hidden form shell, and redirects checkout to the SaaS hosted-link form
 * (`checkout_form_url`) — products render on WordPress, checkout completes on the SaaS.
 *
 * @param array  $catalog      Decoded catalog.json snapshot (with checkout/init/token URLs).
 * @param string $project_slug Workflow slug (for a stable mount id).
 * @param string $link_id      Hosted link id (for a stable mount id).
 * @return string Embed HTML, or '' when the snapshot has no product items.
 */
function xpressui_render_hosted_catalog_embed( $catalog, $project_slug, $link_id ) {
	if ( ! is_array( $catalog ) || empty( $catalog['product_items'] ) ) {
		return '';
	}

	// catalog-init.js (the SaaS asset) hard-binds to #xpressui-root and reads the cart
	// inputs/cards within it, so the mount and cart shell MUST use these exact ids — the
	// same ids the SaaS catalog page (catalog-page.html.j2) uses. One storefront per page.
	$mount_id = 'xpressui-root';
	$shell_id = 'xpressui-catalog-shell';

	// Page URLs for the headless journey — all on WordPress, nothing sent to the SaaS.
	$return_url = get_permalink();
	if ( ! $return_url ) {
		$return_url = home_url( '/' );
	}
	$grid_url     = remove_query_arg( [ 'xpui_product', 'xpui_cart', 'xpui_checkout' ], $return_url );
	$cart_url     = add_query_arg( 'xpui_cart', '1', $grid_url );
	$checkout_url = add_query_arg( 'xpui_checkout', '1', $grid_url );

	// --- Order placed: success notice after a manual order or a Stripe return. ---
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public, read-only navigation var.
	$checkout_state = isset( $_GET['xpuiCheckout'] ) ? sanitize_key( wp_unslash( $_GET['xpuiCheckout'] ) ) : '';
	if ( 'success' === $checkout_state ) {
		$ok_css_path = XPRESSUI_BRIDGE_DIR . 'assets/catalog-cart-summary.css';
		$ok_css_ver  = file_exists( $ok_css_path ) ? (string) filemtime( $ok_css_path ) : XPRESSUI_BRIDGE_VERSION;
		wp_enqueue_style( 'xpressui-cart-summary', XPRESSUI_BRIDGE_URL . 'assets/catalog-cart-summary.css', [], $ok_css_ver );
		$ok_vars = is_array( $catalog['theme_css_vars'] ?? null ) ? $catalog['theme_css_vars'] : [];
		wp_add_inline_style( 'xpressui-cart-summary', xpressui_catalog_theme_vars_css( '.xpressui-cart-summary', $ok_vars ) );
		$ok  = '<div class="xpressui-embed-wrapper xpressui-inline-embed xpressui-cart-summary"><div class="cs-page"><div class="cs-card">';
		$ok .= '<div class="cs-header"><div class="cs-heading"><div class="cs-header-titles">';
		$ok .= '<p class="cs-kicker">' . esc_html__( 'Thank you', 'xpressui-bridge' ) . '</p>';
		$ok .= '<h1 class="cs-title">' . esc_html__( 'Order received', 'xpressui-bridge' ) . '</h1></div></div></div>';
		$ok .= '<div class="cs-empty">' . esc_html__( 'Your order has been received. Thank you for your purchase.', 'xpressui-bridge' ) . '</div>';
		$ok .= '<div class="cs-footer"><a class="cs-cta" href="' . esc_url( $grid_url ) . '">' . esc_html__( 'Back to the store', 'xpressui-bridge' ) . '</a></div>';
		$ok .= '</div></div></div>';
		return wp_kses( $ok, xpressui_get_shell_allowed_html() );
	}

	// --- Cart-summary page (headless on WP). The cart lives in localStorage on the WP
	// origin; rows are rebuilt client-side. Nothing is sent to the SaaS. ---
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public, read-only navigation var.
	if ( ! empty( $_GET['xpui_cart'] ) ) {
		return xpressui_render_cart_summary_embed( $catalog, $link_id, $grid_url, $checkout_url );
	}

	// --- Storefront / detail styles. The product card/grid/cart styles live in the shell. ---
	$shell_css_path = XPRESSUI_BRIDGE_DIR . 'assets/shell/xpressui-shell.css';
	$shell_css_ver  = file_exists( $shell_css_path ) ? (string) filemtime( $shell_css_path ) : XPRESSUI_BRIDGE_VERSION;
	wp_enqueue_style( 'xpressui-shell', XPRESSUI_BRIDGE_URL . 'assets/shell/xpressui-shell.css', [], $shell_css_ver );

	// Scoped theme CSS variables + page-shell reset, mirroring head.j2 (the --template-*
	// vars the product-card/button styles depend on) and _hosted_catalog_styles.j2 (the
	// page-level reset so the storefront blends into the WordPress page, not a full-screen
	// shell). Without the vars the card chrome collapses and the buy buttons render blank.
	$theme_css = xpressui_build_catalog_embed_css( $mount_id, $shell_id, is_array( $catalog['theme_css_vars'] ?? null ) ? $catalog['theme_css_vars'] : [] );
	wp_add_inline_style( 'xpressui-shell', $theme_css );

	// --- Cart globals + catalog-init.js. Everything stays on WordPress: the cart icon and
	// Buy now navigate to the WP cart-summary page — NO cart-token POST, NO SaaS redirect.
	// Token URL is left empty so doCheckout takes the no-token path and just navigates. ---
	$init_url = (string) ( $catalog['catalog_init_url'] ?? '' );

	$globals  = 'window.__xpuiCatalogBaseUrl=' . wp_json_encode( $grid_url ) . ';';
	$globals .= 'window.__xpuiCatalogCheckoutUrl=' . wp_json_encode( $cart_url ) . ';';
	$globals .= 'window.__xpuiCatalogCartSummaryUrl="";';
	$globals .= 'window.__xpuiCatalogTokenUrl="";';
	$globals .= 'window.__xpuiCatalogInitHandlesHeaderCart=true;';
	$globals .= 'window.__xpuiCatalogGate=null;';

	if ( '' !== $init_url ) {
		wp_enqueue_script( 'xpressui-catalog-init', $init_url, [], null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Cross-origin SaaS asset; the SaaS handles its own cache-busting.
		wp_add_inline_script( 'xpressui-catalog-init', $globals, 'before' );
	}

	// --- Markup: mount > form-frame > hidden cart shell + (product grid | product detail).
	// The detail page renders headless on WP too: ?xpui_product=<id> on the same page
	// shows the product detail (parity with the SaaS /catalog/<uid>/<slug>/<item>), and
	// grid cards link back to this WP page with that query. catalog-init.js hydrates the
	// detail card via [data-product-detail-page] exactly as on the SaaS.
	$active_product = xpressui_get_catalog_active_product( $catalog );
	if ( is_array( $active_product ) ) {
		$inner = xpressui_render_product_detail_section( $catalog, $active_product, $grid_url );
	} else {
		// Cards link to this same WP page with ?xpui_product=<id> (headless detail).
		$inner = xpressui_render_product_catalog_grid( $catalog, $grid_url );
	}

	$shell  = '<form id="' . esc_attr( $shell_id ) . '" class="xpressui-catalog-cart-shell" hidden aria-hidden="true" style="display:none">';
	$shell .= '<input type="hidden" name="xpressuiProductCart" value="" />';
	$shell .= '<input type="hidden" name="xpressuiProductTotal" value="" />';
	$shell .= '<input type="hidden" name="xpressuiProductCurrency" value="" />';
	$shell .= '<input type="hidden" name="xpressuiProductCount" value="" />';
	$shell .= '</form>';

	$html  = '<div class="xpressui-embed-wrapper xpressui-inline-embed">';
	$html .= '<div id="' . esc_attr( $mount_id ) . '" class="xpressui-embed page-shell page-shell--product-catalog" data-template-zone="page_shell" data-hosted-link-id="' . esc_attr( (string) $link_id ) . '">';
	$html .= '<div class="form-frame form-frame--commerce-landing">';
	$html .= $shell;
	$html .= $inner;
	$html .= '</div></div></div>';

	return wp_kses( $html, xpressui_get_shell_allowed_html() );
}

/**
 * Builds the scoped CSS for a headless catalog embed.
 *
 * Emits the `--template-*` variables (mirroring `export/_partials/head.j2`) that the
 * product-card/button styles in the shell stylesheet resolve against, plus the
 * page-shell reset from `_hosted_catalog_styles.j2` so the storefront blends into the
 * host WordPress page instead of behaving as a full-viewport shell. Scoped to the
 * mount id so nothing leaks to the rest of the page.
 *
 * @param string $mount_id Mount element id.
 * @param string $shell_id Hidden cart-shell form id.
 * @param array  $vars     theme_css_vars from catalog.json (font/colours/radii).
 * @return string CSS (no <style> wrapper; passed to wp_add_inline_style).
 */
/**
 * Emits the --template-* CSS variable declarations for a selector (mirrors head.j2),
 * so any catalog surface (storefront, detail, cart summary) resolves the shared theme.
 *
 * @param string $selector CSS selector to scope the variables to.
 * @param array  $vars     theme_css_vars from catalog.json.
 * @param string $extra    Extra declarations appended inside the same rule.
 * @return string CSS rule text.
 */
function xpressui_catalog_theme_vars_css( $selector, $vars, $extra = '' ) {
	$font = trim( (string) ( $vars['font_family'] ?? '' ) );
	$font = '' !== $font ? $font : 'inherit';
	$css  = $selector . '{';
	$css .= '--template-font-family:' . $font . ';';
	$css .= '--template-page-background:' . (string) ( $vars['page_background'] ?? '#edf3fb' ) . ';';
	$css .= '--template-surface:' . (string) ( $vars['surface'] ?? '#ffffff' ) . ';';
	$css .= '--template-text:' . (string) ( $vars['text'] ?? '#0f172a' ) . ';';
	$css .= '--template-muted-text:color-mix(in srgb, var(--template-text) 65%, transparent);';
	$css .= '--template-primary:' . (string) ( $vars['primary'] ?? '#2563eb' ) . ';';
	$css .= '--template-border:' . (string) ( $vars['border'] ?? '#d7e0ea' ) . ';';
	$css .= '--template-card-radius:' . (int) ( $vars['card_radius'] ?? 28 ) . 'px;';
	$css .= '--template-input-radius:' . (int) ( $vars['input_radius'] ?? 14 ) . 'px;';
	$css .= '--template-button-radius:' . (int) ( $vars['button_radius'] ?? 14 ) . 'px;';
	$css .= $extra . '}';
	return $css;
}

function xpressui_build_catalog_embed_css( $mount_id, $shell_id, $vars ) {
	$sel = '#' . $mount_id;
	// Theme vars + page-shell reset (mirrors _hosted_catalog_styles.j2): blend into WP.
	$css = xpressui_catalog_theme_vars_css(
		$sel,
		$vars,
		'min-height:auto;display:block;place-items:unset;overflow:visible;padding:0;background:transparent;font-family:var(--template-font-family);color:var(--template-text);'
	);
	$css .= $sel . ' .template-product-landing{min-height:auto;gap:18px;}';
	$css .= $sel . ' .template-product-landing-actions--floating{display:none;}';
	$css .= $sel . ' .template-product-catalog-section{padding-top:0;}';
	$css .= $sel . ' .template-product-grid{margin-bottom:0;}';
	// Defuse host-theme spacing bleed on the card content (themes add margins to
	// article/h3/p inside .entry-content, which breaks the card rhythm).
	$css .= $sel . ' .template-product-card,' . $sel . ' .template-product-card *{margin-top:0;margin-bottom:0;}';
	$css .= $sel . ' .template-product-title{margin:0;}';
	$css .= $sel . ' .template-product-detail-page *{margin-top:0;margin-bottom:0;}';
	// Back-to-storefront link on the headless detail page (no SaaS chrome to navigate from).
	$css .= $sel . ' .xpui-catalog-back-link{display:inline-flex;align-items:center;gap:6px;margin:0 0 18px;font-size:13px;font-weight:700;text-decoration:none;color:var(--template-primary);}';
	$css .= $sel . ' .xpui-catalog-back-link:hover{text-decoration:underline;}';
	// Hard-hide the cart-state form shell.
	$css .= $sel . ' #' . $shell_id . ',' . $sel . ' #' . $shell_id . ' *{display:none !important;visibility:hidden !important;width:0 !important;height:0 !important;overflow:hidden !important;pointer-events:none !important;opacity:0 !important;}';

	return $css;
}

/**
 * Resolves the active product for a headless detail view.
 *
 * Reads the `xpui_product` query var (set on grid card links) and returns the matching
 * product item from the catalog snapshot, or null when absent / not found (grid view).
 *
 * @param array $catalog Decoded catalog.json snapshot.
 * @return array|null The product item, or null for the grid view.
 */
function xpressui_get_catalog_active_product( $catalog ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public, read-only navigation query var (like pagination); sanitized below, no state change.
	$raw = isset( $_GET['xpui_product'] ) ? sanitize_text_field( wp_unslash( $_GET['xpui_product'] ) ) : '';
	if ( '' === $raw || ! is_array( $catalog ) ) {
		return null;
	}
	$items = ( isset( $catalog['product_items'] ) && is_array( $catalog['product_items'] ) ) ? $catalog['product_items'] : [];
	foreach ( $items as $item ) {
		if ( is_array( $item ) && (string) ( $item['id'] ?? '' ) === $raw ) {
			return $item;
		}
	}
	return null;
}

/**
 * Renders the headless product-detail page HTML from a catalog snapshot + one product.
 *
 * Mirrors `export/_partials/product-catalog/product-detail-page.j2`: the landing wrapper,
 * cart toolbar, an empty hidden catalog carrier section (catalog id / render mode / cart
 * max), and the `[data-product-detail-page]` section with all `data-product-*` hooks +
 * buy box (stepper + CTA). The same `catalog-init.js` hydrates the cart from these hooks.
 * A back-to-storefront link is added (the WP page has no SaaS chrome to navigate from).
 *
 * @param array  $catalog    Decoded catalog.json snapshot.
 * @param array  $product    The active product item.
 * @param string $return_url Storefront (grid) URL to link back to.
 * @return string Detail HTML (escaped at the point of output; kses-filtered by the caller).
 */
function xpressui_render_product_detail_section( $catalog, $product, $return_url = '' ) {
	if ( ! is_array( $catalog ) || ! is_array( $product ) ) {
		return '';
	}

	$cart_enabled  = ! empty( $catalog['cart_enabled'] );
	$supports_many = ! empty( $catalog['cart_supports_multiple_items'] );
	$render_mode   = (string) ( $catalog['render_mode'] ?? 'add-to-cart' );
	$catalog_id    = (string) ( $catalog['catalog_id'] ?? '' );
	$template_id   = (string) ( $catalog['template_id'] ?? '' );
	$cart_max_qty  = (int) ( $catalog['cart_max_quantity'] ?? 0 );
	$cta_label     = trim( (string) ( $catalog['cta_label'] ?? '' ) );
	if ( '' === $cta_label ) {
		$cta_label = __( 'Buy now', 'xpressui-bridge' );
	}
	$cart_label = __( 'Cart', 'xpressui-bridge' );

	$id            = (string) ( $product['id'] ?? '' );
	$label         = (string) ( $product['label'] ?? '' );
	$sku           = (string) ( $product['sku'] ?? '' );
	$image         = trim( (string) ( $product['image_thumbnail'] ?? '' ) );
	$category      = (string) ( $product['category'] ?? '' );
	$unit          = (string) ( $product['unit'] ?? '' );
	$description   = (string) ( $product['description'] ?? '' );
	$currency      = (string) ( $product['currency'] ?? '' );
	$price         = ( isset( $product['price'] ) && null !== $product['price'] ) ? (string) $product['price'] : '';
	$price_display = (string) ( $product['price_display'] ?? '' );
	$max_qty       = ( isset( $product['max_quantity'] ) && null !== $product['max_quantity'] ) ? (string) $product['max_quantity'] : '';
	$max_qty_attr  = '' !== $max_qty ? $max_qty : '20';
	$catalog_name  = trim( (string) ( $catalog['catalog_name'] ?? '' ) );
	$back_label    = '' !== $catalog_name ? $catalog_name : __( 'Back to products', 'xpressui-bridge' );

	ob_start();
	?>
<div class="template-product-landing" data-template-zone="workflow_product_landing" data-workflow-product-landing>
	<?php if ( '' !== $return_url ) : ?>
	<a class="xpui-catalog-back-link" href="<?php echo esc_url( $return_url ); ?>">&larr; <?php echo esc_html( $back_label ); ?></a>
	<?php endif; ?>
	<?php if ( $cart_enabled ) : ?>
	<div class="xpui-product-cart-toolbar">
		<button type="button" class="xpui-cart-trigger" data-product-cart-trigger data-empty="true" aria-label="<?php echo esc_attr( $cta_label ); ?>">
			<span class="xpui-cart-trigger-icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" focusable="false"><path d="M6.5 6.5h14l-1.6 7.1a2 2 0 0 1-2 1.6H9.2a2 2 0 0 1-2-1.6L5.6 4.8H3"></path><circle cx="9.5" cy="19" r="1.2"></circle><circle cx="17" cy="19" r="1.2"></circle></svg>
			</span>
			<span class="xpui-sr-only"><?php echo esc_html( $cart_label ); ?></span>
			<span class="xpui-cart-trigger-count" data-product-cart-trigger-count>0</span>
			<span class="xpui-cart-trigger-total" data-product-cart-trigger-total></span>
		</button>
	</div>
	<?php endif; ?>
	<section
		class="template-product-catalog-section"
		data-template-zone="workflow_product_catalog"
		data-workflow-product-catalog
		data-product-catalog-id="<?php echo esc_attr( $catalog_id ); ?>"
		data-product-template-id="<?php echo esc_attr( $template_id ); ?>"
		data-render-mode="<?php echo esc_attr( $render_mode ); ?>"
		<?php echo $cart_max_qty ? 'data-cart-max-quantity="' . esc_attr( (string) $cart_max_qty ) . '"' : ''; ?>
		hidden
		aria-hidden="true"
	></section>
	<section
		class="template-product-detail-page"
		data-template-zone="workflow_product_detail"
		data-product-detail-page
		data-product-id="<?php echo esc_attr( $id ); ?>"
		data-product-sku="<?php echo esc_attr( $sku ); ?>"
		data-product-label="<?php echo esc_attr( $label ); ?>"
		data-product-price="<?php echo esc_attr( $price ); ?>"
		data-product-price-display="<?php echo esc_attr( $price_display ); ?>"
		data-product-currency="<?php echo esc_attr( $currency ); ?>"
		data-product-category="<?php echo esc_attr( $category ); ?>"
		data-product-unit="<?php echo esc_attr( $unit ); ?>"
		data-product-description="<?php echo esc_attr( $description ); ?>"
		data-product-image="<?php echo esc_attr( $image ); ?>"
		data-product-max-quantity="<?php echo esc_attr( $max_qty ); ?>"
		data-prerendered="true"
	>
		<div class="template-product-detail-layout">
			<div class="template-product-detail-media">
				<?php if ( '' !== $image ) : ?>
				<img data-product-detail-image src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $label ); ?>" loading="lazy" />
				<?php else : ?>
				<img data-product-detail-image alt="" loading="lazy" hidden />
				<div class="template-product-detail-media-empty" data-product-detail-image-empty aria-hidden="true">
					<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="m21 15-5-5L5 21"></path></svg>
				</div>
				<?php endif; ?>
			</div>
			<div class="template-product-detail-copy">
				<?php if ( '' !== $category ) : ?>
				<p class="template-product-landing-kicker" data-product-detail-category><?php echo esc_html( $category ); ?></p>
				<?php else : ?>
				<p class="template-product-landing-kicker" data-product-detail-category hidden></p>
				<?php endif; ?>
				<h2 class="template-product-detail-title" data-product-detail-title><?php echo esc_html( '' !== $label ? $label : $id ); ?></h2>
				<p class="template-product-detail-description" data-product-detail-description><?php echo esc_html( $description ); ?></p>
				<?php if ( $cart_enabled ) : ?>
				<div class="template-product-detail-buy-box">
					<div class="template-product-detail-price-row">
						<strong class="template-product-detail-price" data-product-detail-price><?php echo esc_html( $price_display ); ?></strong>
						<span class="template-product-detail-unit" data-product-detail-unit><?php echo esc_html( $unit ); ?></span>
					</div>
					<div class="xpui-product-view-actions">
						<?php if ( $supports_many ) : ?>
						<div class="xpui-qty-stepper" role="group" aria-label="<?php esc_attr_e( 'Quantity', 'xpressui-bridge' ); ?>">
							<button type="button" class="xpui-qty-stepper-btn" data-product-detail-qty-dec aria-label="<?php esc_attr_e( 'Decrease quantity', 'xpressui-bridge' ); ?>" disabled>
								<svg width="12" height="2" viewBox="0 0 12 2" fill="none" aria-hidden="true"><path d="M1 1h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path></svg>
							</button>
							<input type="number" class="xpui-qty-stepper-input" data-product-detail-qty-select id="xpui-detail-qty-select" value="0" min="0" max="<?php echo esc_attr( $max_qty_attr ); ?>" aria-label="<?php esc_attr_e( 'Quantity', 'xpressui-bridge' ); ?>" readonly />
							<button type="button" class="xpui-qty-stepper-btn" data-product-detail-qty-inc aria-label="<?php esc_attr_e( 'Increase quantity', 'xpressui-bridge' ); ?>">
								<svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path></svg>
							</button>
						</div>
						<?php endif; ?>
						<button type="button" class="xpui-product-detail-checkout-btn" data-product-detail-cta><?php echo esc_html( $cta_label ); ?></button>
					</div>
				</div>
				<?php else : ?>
				<div class="template-product-detail-price-row">
					<strong class="template-product-detail-price" data-product-detail-price><?php echo esc_html( $price_display ); ?></strong>
					<span class="template-product-detail-unit" data-product-detail-unit><?php echo esc_html( $unit ); ?></span>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php if ( $cart_enabled ) : ?>
	<div class="template-product-landing-cta-row" hidden>
		<button type="button" class="template-product-landing-cta" data-product-landing-cta><?php echo esc_html( $cta_label ); ?></button>
	</div>
	<?php endif; ?>
</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Builds the client-side cart storage key for a hosted-link catalog.
 *
 * Mirrors xpuiCartStorageKey/xpuiCartScopeId in the SaaS product-cart-core
 * (`xpressui:product-cart:<scope>:<catalogId>`, scope = hosted link id) so the WP
 * cart-summary reads the exact same localStorage entry catalog-init.js writes.
 *
 * @param string $link_id    Hosted link id (the cart scope).
 * @param string $catalog_id Catalog id.
 * @return string localStorage key.
 */
function xpressui_catalog_cart_storage_key( $link_id, $catalog_id ) {
	$scope = '' !== (string) $link_id ? (string) $link_id : 'project';
	$cat   = '' !== (string) $catalog_id ? (string) $catalog_id : 'catalog';
	return 'xpressui:product-cart:' . $scope . ':' . $cat;
}

/**
 * Renders the headless WordPress cart-summary embed (order summary).
 *
 * Mirrors the SaaS cart-summary-page.html.j2 DOM/styles, but the rows are built
 * client-side from the localStorage cart (assets/catalog-cart-summary.js) — nothing
 * is sent to the SaaS. The checkout CTA navigates to the WP checkout page.
 *
 * @param array  $catalog      Decoded catalog.json snapshot.
 * @param string $link_id      Hosted link id (cart scope).
 * @param string $grid_url     Storefront (grid) URL — back link + empty-cart redirect.
 * @param string $checkout_url WP checkout URL the CTA navigates to.
 * @return string Cart-summary embed HTML.
 */
function xpressui_render_cart_summary_embed( $catalog, $link_id, $grid_url, $checkout_url ) {
	$catalog_id  = (string) ( $catalog['catalog_id'] ?? '' );
	$storage_key = xpressui_catalog_cart_storage_key( $link_id, $catalog_id );
	$currency    = (string) ( $catalog['default_currency'] ?? '' );
	$catalog_name = trim( (string) ( $catalog['catalog_name'] ?? '' ) );
	$kicker       = '' !== $catalog_name ? $catalog_name : __( 'Catalog', 'xpressui-bridge' );

	// Styles + hydrator. Rows are built client-side from localStorage.
	$css_path = XPRESSUI_BRIDGE_DIR . 'assets/catalog-cart-summary.css';
	$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : XPRESSUI_BRIDGE_VERSION;
	wp_enqueue_style( 'xpressui-cart-summary', XPRESSUI_BRIDGE_URL . 'assets/catalog-cart-summary.css', [], $css_ver );
	$vars = is_array( $catalog['theme_css_vars'] ?? null ) ? $catalog['theme_css_vars'] : [];
	wp_add_inline_style( 'xpressui-cart-summary', xpressui_catalog_theme_vars_css( '.xpressui-cart-summary', $vars ) );

	$js_path = XPRESSUI_BRIDGE_DIR . 'assets/catalog-cart-summary.js';
	$js_ver  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : XPRESSUI_BRIDGE_VERSION;
	wp_enqueue_script( 'xpressui-cart-summary', XPRESSUI_BRIDGE_URL . 'assets/catalog-cart-summary.js', [], $js_ver, true );

	ob_start();
	?>
<div class="xpressui-embed-wrapper xpressui-inline-embed xpressui-cart-summary">
	<div
		class="cs-page"
		data-storage-key="<?php echo esc_attr( $storage_key ); ?>"
		data-catalog-url="<?php echo esc_url( $grid_url ); ?>"
		data-checkout-url="<?php echo esc_url( $checkout_url ); ?>"
		data-currency="<?php echo esc_attr( $currency ); ?>"
		data-empty-label="<?php esc_attr_e( 'Your cart is empty.', 'xpressui-bridge' ); ?>"
	>
		<div class="cs-card">
			<div class="cs-header">
				<div class="cs-heading">
					<a href="<?php echo esc_url( $grid_url ); ?>" class="cs-back" aria-label="<?php esc_attr_e( 'Back to catalog', 'xpressui-bridge' ); ?>">
						<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
					</a>
					<div class="cs-header-titles">
						<p class="cs-kicker"><?php echo esc_html( $kicker ); ?></p>
						<h1 class="cs-title"><?php esc_html_e( 'Order summary', 'xpressui-bridge' ); ?></h1>
					</div>
				</div>
			</div>
			<ul class="cs-items" data-cart-items></ul>
			<div class="cs-totals" data-cart-totals>
				<div class="cs-totals-row cs-totals-row--total">
					<span><?php esc_html_e( 'Total', 'xpressui-bridge' ); ?></span>
					<strong data-cart-total></strong>
				</div>
			</div>
			<div class="cs-footer" data-cart-footer>
				<a href="<?php echo esc_url( $checkout_url ); ?>" class="cs-cta" data-cart-checkout>
					<?php esc_html_e( 'Proceed to checkout', 'xpressui-bridge' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>
	<?php
	return wp_kses( (string) ob_get_clean(), xpressui_get_shell_allowed_html() );
}

/**
 * Renders the payment-method selector shown inside the WP checkout form.
 *
 * Mirrors the SaaS hosted checkout: a card option (when Stripe is enabled on a
 * charges-enabled Connect account) and/or the workspace manual methods (Wave,
 * Orange Money, bank, cash) with their instructions. The chosen method is posted
 * with the order (field name xpui_payment_method). Returns '' when the link
 * collects no payment.
 *
 * @param array $payment The catalog.json `payment` block.
 * @return string Selector HTML, or '' when there is nothing to collect.
 */
function xpressui_render_checkout_payment_methods( $payment ) {
	if ( ! is_array( $payment ) ) {
		return '';
	}
	$collection = (string) ( $payment['collection'] ?? 'none' );
	$stripe     = ! empty( $payment['stripe_enabled'] );
	$methods    = ( isset( $payment['methods'] ) && is_array( $payment['methods'] ) ) ? $payment['methods'] : [];
	if ( 'none' === $collection || ( ! $stripe && empty( $methods ) ) ) {
		return '';
	}

	ob_start();
	?>
<div class="xpui-checkout-payment" data-checkout-payment>
	<p class="xpui-checkout-payment-title"><?php esc_html_e( 'Payment method', 'xpressui-bridge' ); ?></p>
	<div class="xpui-payment-methods">
		<?php if ( $stripe ) : ?>
		<label class="xpui-payment-method">
			<input type="radio" name="xpui_payment_method" value="stripe" data-payment-method checked />
			<span class="xpui-payment-method-label"><?php esc_html_e( 'Credit / debit card', 'xpressui-bridge' ); ?></span>
		</label>
		<?php endif; ?>
		<?php foreach ( $methods as $i => $method ) : ?>
			<?php
			if ( ! is_array( $method ) ) {
				continue;
			}
			$mid   = (string) ( $method['id'] ?? '' );
			$label = (string) ( $method['label'] ?? $mid );
			if ( '' === $mid ) {
				continue;
			}
			$checked = ( ! $stripe && 0 === $i ) ? ' checked' : '';
			?>
		<label class="xpui-payment-method">
			<input type="radio" name="xpui_payment_method" value="<?php echo esc_attr( $mid ); ?>" data-payment-method<?php echo esc_attr( $checked ); ?> />
			<span class="xpui-payment-method-label"><?php echo esc_html( $label ); ?></span>
		</label>
		<?php endforeach; ?>
	</div>
	<?php foreach ( $methods as $method ) : ?>
		<?php
		if ( ! is_array( $method ) ) {
			continue;
		}
		$mid          = (string) ( $method['id'] ?? '' );
		$instructions = trim( (string) ( $method['instructions'] ?? '' ) );
		$merchant     = trim( (string) ( $method['merchant_name'] ?? '' ) );
		$phone        = trim( (string) ( $method['merchant_phone'] ?? '' ) );
		$iban         = trim( (string) ( $method['iban'] ?? '' ) );
		if ( '' === $mid ) {
			continue;
		}
		?>
	<div class="xpui-payment-instructions" data-payment-instructions="<?php echo esc_attr( $mid ); ?>" hidden>
		<?php if ( '' !== $merchant ) : ?><p class="xpui-payment-merchant"><?php echo esc_html( $merchant ); ?><?php echo '' !== $phone ? ' — ' . esc_html( $phone ) : ''; ?></p><?php endif; ?>
		<?php if ( '' !== $iban ) : ?><p class="xpui-payment-iban"><?php echo esc_html( $iban ); ?></p><?php endif; ?>
		<?php if ( '' !== $instructions ) : ?><p class="xpui-payment-instructions-text"><?php echo esc_html( $instructions ); ?></p><?php endif; ?>
	</div>
	<?php endforeach; ?>
</div>
	<?php
	return wp_kses( (string) ob_get_clean(), xpressui_get_shell_allowed_html() );
}

/**
 * Renders the read-only Order summary block shown inside the WP checkout form.
 *
 * Same client-side hydration as the cart-summary page (catalog-cart-summary.js reads
 * the localStorage cart), but read-only (data-readonly="1"): no steppers, no remove,
 * no checkout CTA — the form's own submit is the checkout. Mirrors the SaaS checkout
 * form which shows the order summary above the customer fields.
 *
 * @param array  $catalog  Decoded catalog.json snapshot.
 * @param string $link_id  Hosted link id (cart scope).
 * @param string $grid_url Storefront URL (back link).
 * @return string Order-summary HTML.
 */
function xpressui_render_checkout_order_summary( $catalog, $link_id, $grid_url ) {
	if ( ! is_array( $catalog ) ) {
		return '';
	}
	$catalog_id  = (string) ( $catalog['catalog_id'] ?? '' );
	$storage_key = xpressui_catalog_cart_storage_key( $link_id, $catalog_id );
	$currency    = (string) ( $catalog['default_currency'] ?? '' );

	$css_path = XPRESSUI_BRIDGE_DIR . 'assets/catalog-cart-summary.css';
	$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : XPRESSUI_BRIDGE_VERSION;
	wp_enqueue_style( 'xpressui-cart-summary', XPRESSUI_BRIDGE_URL . 'assets/catalog-cart-summary.css', [], $css_ver );
	$vars = is_array( $catalog['theme_css_vars'] ?? null ) ? $catalog['theme_css_vars'] : [];
	wp_add_inline_style( 'xpressui-cart-summary', xpressui_catalog_theme_vars_css( '.xpressui-cart-summary', $vars ) );

	$js_path = XPRESSUI_BRIDGE_DIR . 'assets/catalog-cart-summary.js';
	$js_ver  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : XPRESSUI_BRIDGE_VERSION;
	wp_enqueue_script( 'xpressui-cart-summary', XPRESSUI_BRIDGE_URL . 'assets/catalog-cart-summary.js', [], $js_ver, true );

	ob_start();
	?>
<div class="xpressui-cart-summary xpressui-checkout-summary">
	<div
		class="cs-page"
		data-readonly="1"
		data-storage-key="<?php echo esc_attr( $storage_key ); ?>"
		data-catalog-url="<?php echo esc_url( $grid_url ); ?>"
		data-currency="<?php echo esc_attr( $currency ); ?>"
		data-empty-label="<?php esc_attr_e( 'Your cart is empty.', 'xpressui-bridge' ); ?>"
	>
		<div class="cs-card">
			<div class="cs-header">
				<div class="cs-heading">
					<div class="cs-header-titles">
						<p class="cs-kicker"><?php esc_html_e( 'Your order', 'xpressui-bridge' ); ?></p>
						<h1 class="cs-title"><?php esc_html_e( 'Order summary', 'xpressui-bridge' ); ?></h1>
					</div>
				</div>
			</div>
			<ul class="cs-items" data-cart-items></ul>
			<div class="cs-totals" data-cart-totals>
				<div class="cs-totals-row cs-totals-row--total">
					<span><?php esc_html_e( 'Total', 'xpressui-bridge' ); ?></span>
					<strong data-cart-total></strong>
				</div>
			</div>
		</div>
	</div>
</div>
	<?php
	return wp_kses( (string) ob_get_clean(), xpressui_get_shell_allowed_html() );
}
