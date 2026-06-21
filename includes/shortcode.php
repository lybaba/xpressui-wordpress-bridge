<?php
/**
 * [xpressui] shortcode — embeds an installed XPressUI workflow package inline.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensures a registered, enqueued no-src style handle exists for inline CSS, returning
 * its handle. Used as a fallback when a renderer is called without a caller-supplied
 * style handle so its inline CSS is still emitted via the WordPress style API
 * (wp_add_inline_style) instead of a raw, unescaped <style> echo. Shortcodes render
 * after wp_head, so an enqueued-but-unprinted handle is flushed by core in wp_footer.
 *
 * @return string The registered inline style handle.
 */
function xpressui_ensure_inline_style_handle() {
	$handle = 'xpressui-bridge-inline';
	if ( ! wp_style_is( $handle, 'registered' ) ) {
		wp_register_style( $handle, false, [], XPRESSUI_BRIDGE_VERSION );
	}
	if ( ! wp_style_is( $handle, 'enqueued' ) ) {
		wp_enqueue_style( $handle );
	}
	return $handle;
}

/**
 * The wp_kses() allowlist for the static inline SVG file-type icons in the reference
 * documents block. Restricts output to <svg>/<path> with the geometry/presentation
 * attributes those hardcoded icons use, so the icon echo is escaped (no raw output).
 *
 * @return array<string,array<string,bool>> Allowed-HTML map for wp_kses().
 */
function xpressui_ref_docs_svg_allowed_html() {
	return [
		'svg'  => [
			'viewbox'     => true,
			'fill'        => true,
			'width'       => true,
			'height'      => true,
			'aria-hidden' => true,
			'xmlns'       => true,
		],
		'path' => [
			'd'    => true,
			'fill' => true,
		],
	];
}

/**
 * Renders the "Reference documents" collapsible block from a synced Hosted Link
 * presentation, for parity with the SaaS hosted-link render. Returns '' when the
 * link has no reference documents.
 *
 * @param array  $presentation Hosted Link presentation (from link.config.json).
 * @param string $locale       'fr' or 'en' (for the default section title).
 * @return string HTML (kses-safe: details/summary/section/div/span/a/svg).
 */
function xpressui_render_reference_documents( $presentation, $locale = 'en', $style_handle = '' ) {
	if ( ! is_array( $presentation ) || ! is_array( $presentation['referenceDocuments'] ?? null ) ) {
		return '';
	}

	$documents = [];
	foreach ( $presentation['referenceDocuments'] as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$url = trim( (string) ( $item['url'] ?? '' ) );
		if ( $url === '' || ( stripos( $url, 'http://' ) !== 0 && stripos( $url, 'https://' ) !== 0 ) ) {
			continue;
		}
		$path = strtok( strtolower( $url ), '?#' );
		$ext  = ( $path !== false && strrpos( $path, '.' ) !== false ) ? substr( $path, strrpos( $path, '.' ) + 1 ) : '';
		if ( 'pdf' === $ext ) {
			$kind = 'pdf';
		} elseif ( in_array( $ext, [ 'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg' ], true ) ) {
			$kind = 'image';
		} elseif ( in_array( $ext, [ 'zip', 'rar', '7z', 'tar', 'gz', 'tgz' ], true ) ) {
			$kind = 'archive';
		} else {
			$kind = 'file';
		}
		$documents[] = [
			'name'     => trim( (string) ( $item['name'] ?? '' ) ),
			'url'      => $url,
			'kind'     => $kind,
			'filename' => rawurldecode( (string) basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ),
		];
		if ( count( $documents ) >= 25 ) {
			break;
		}
	}

	if ( empty( $documents ) ) {
		return '';
	}

	$configured_title = trim( (string) ( $presentation['referenceDocumentsTitle'] ?? '' ) );
	$title            = '' !== $configured_title
		? $configured_title
		: ( 'fr' === $locale ? __( 'Documents de référence', 'xpressui-bridge' ) : __( 'Reference documents', 'xpressui-bridge' ) );

	$icons = [
		'pdf'     => '<svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true"><path d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM9.5 11.5c0 .83-.67 1.5-1.5 1.5H7v2H5.5V9H8c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V9H13c.83 0 1.5.67 1.5 1.5v4zm4-3H17v1h1.5V14H17v2h-1.5V9h3v1.5zM7 11.5h1v-1H7v1zm5 0h1v3h-1v-3zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6z"></path></svg>',
		'image'   => '<svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"></path></svg>',
		'archive' => '<svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true"><path d="M22 8c0-1.1-.9-2-2-2h-8l-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h7v-2h2v2h7c1.1 0 2-.9 2-2V8zm-9 2h-2V8h2v2zm0 4h-2v-2h2v2zm0 4h-2v-2h2v2z"></path></svg>',
		'file'    => '<svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true"><path d="M6 2c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6H6zm7 7V3.5L18.5 9H13z"></path></svg>',
	];
	$colors  = [ 'pdf' => '#b91c1c', 'image' => '#2563eb', 'archive' => '#ca8a04', 'file' => '#64748b' ];

	$ref_docs_css = '.xpressui-ref-docs{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin:0 0 1.25rem;overflow:hidden;font-size:.9rem;box-shadow:0 1px 2px rgba(15,23,42,.04)}'
		. '.xpressui-ref-docs summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:.75rem;padding:.85rem 1.25rem;font-weight:700;color:#0f172a;border:none!important;outline:none!important;box-shadow:none!important}'
		. '.xpressui-ref-docs summary:focus,.xpressui-ref-docs summary:active{outline:none!important;border:none!important;box-shadow:none!important}'
		. '.xpressui-ref-docs summary::-webkit-details-marker{display:none}'
		. '.xpressui-ref-docs__count{margin-left:auto;color:#64748b;font-weight:700;font-size:.82rem}'
		. '.xpressui-ref-docs__chev{flex:0 0 auto;width:.5rem;height:.5rem;border-right:2px solid #64748b;border-bottom:2px solid #64748b;transform:rotate(45deg);transition:transform .16s ease}'
		. '.xpressui-ref-docs[open] summary .xpressui-ref-docs__chev{transform:rotate(225deg)}'
		. '.xpressui-ref-docs__body{border-top:1px solid #e2e8f0;padding:.2rem .85rem .35rem}'
		. '.xpressui-ref-docs__row+.xpressui-ref-docs__row{border-top:1px solid #e2e8f0}'
		. '.xpressui-ref-docs__item{display:flex;align-items:center;gap:.7rem;width:100%;min-width:0;padding:.6rem .35rem;color:#0f172a}'
		. '.xpressui-ref-docs__icon{flex:0 0 auto;display:inline-flex;width:28px;height:28px}'
		. '.xpressui-ref-docs__icon svg{width:28px;height:28px;display:block}'
		. '.xpressui-ref-docs__text{display:grid;gap:.08rem;min-width:0;flex:1 1 auto}'
		. '.xpressui-ref-docs__name{min-width:0;color:#0f172a;font-weight:700;line-height:1.25;word-break:break-word}'
		. '.xpressui-ref-docs__file{color:#64748b;font-size:.78rem;line-height:1.2;word-break:break-all}'
		. '.xpressui-ref-docs__download{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;color:#64748b;text-decoration:none!important;transition:background 120ms ease,color 120ms ease;align-self:center}'
		. '.xpressui-ref-docs__download:hover{background:#f1f5f9;color:#0f172a}'
		. '.xpressui-ref-docs__download .dashicons{font-size:16px!important;width:16px!important;height:16px!important;line-height:1!important;align-self:center}';

	// Emit the CSS through the WordPress style API instead of a raw <style> echo. When no
	// caller handle is supplied, fall back to a registered no-src handle so the inline CSS is
	// still enqueued (and escaped by core) rather than printed unescaped.
	$ref_docs_handle = ! empty( $style_handle ) ? $style_handle : xpressui_ensure_inline_style_handle();
	wp_add_inline_style( $ref_docs_handle, $ref_docs_css );

	ob_start();
	?>
<details class="xpressui-ref-docs" open>
	<summary>
		<span><?php echo esc_html( $title ); ?></span>
		<span class="xpressui-ref-docs__count"><?php echo (int) count( $documents ); ?></span>
		<span class="xpressui-ref-docs__chev" aria-hidden="true"></span>
	</summary>
	<div class="xpressui-ref-docs__body">
		<?php foreach ( $documents as $doc ) : ?>
		<div class="xpressui-ref-docs__row">
			<div class="xpressui-ref-docs__item">
				<span class="xpressui-ref-docs__icon" style="color:<?php echo esc_attr( $colors[ $doc['kind'] ] ); ?>"><?php echo wp_kses( $icons[ $doc['kind'] ], xpressui_ref_docs_svg_allowed_html() ); ?></span>
				<span class="xpressui-ref-docs__text">
					<span class="xpressui-ref-docs__name"><?php echo esc_html( '' !== $doc['name'] ? $doc['name'] : $doc['url'] ); ?></span>
					<?php if ( '' !== $doc['filename'] && $doc['filename'] !== $doc['name'] ) : ?>
					<span class="xpressui-ref-docs__file"><?php echo esc_html( $doc['filename'] ); ?></span>
					<?php endif; ?>
				</span>
				<a class="xpressui-ref-docs__download" href="<?php echo esc_url( $doc['url'] ); ?>" download aria-label="<?php esc_attr_e( 'Download document', 'xpressui-bridge' ); ?>" title="<?php esc_attr_e( 'Download', 'xpressui-bridge' ); ?>">
					<span class="dashicons dashicons-download" aria-hidden="true"></span>
				</a>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
</details>
	<?php
	return (string) ob_get_clean();
}

/**
 * Renders the intro/welcome markdown into kses-safe HTML, mirroring the SaaS
 * `_render_intro_markdown`: paragraphs split on blank lines, **bold** / *italic*
 * inline, per-block alignment (`::center` / `::right`) and emphasis color (`!!`).
 *
 * @param mixed $markdown Raw intro content markdown.
 * @return string HTML (only <p style>/<strong>/<em>/<br>, user text escaped).
 */
function xpressui_render_intro_markdown( $markdown ) {
	$text = trim( (string) $markdown );
	if ( '' === $text ) {
		return '';
	}
	$text = str_replace( [ "\r\n", "\r" ], "\n", $text );

	$render_inline = static function ( $value ) {
		$escaped = esc_html( $value );
		$escaped = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped );
		$escaped = preg_replace( '/\*(.+?)\*/', '<em>$1</em>', $escaped );
		return $escaped;
	};

	$html = '';
	foreach ( preg_split( '/\n\s*\n/', $text ) as $raw_block ) {
		$block = trim( (string) $raw_block );
		if ( '' === $block ) {
			continue;
		}
		$lines      = explode( "\n", $block );
		$first_line = strtolower( trim( $lines[0] ) );
		$align      = '';
		if ( in_array( $first_line, [ '::center', '> center', '[center]' ], true ) ) {
			$align = 'center';
			$block = trim( implode( "\n", array_slice( $lines, 1 ) ) );
		} elseif ( in_array( $first_line, [ '::right', '> right', '[right]' ], true ) ) {
			$align = 'right';
			$block = trim( implode( "\n", array_slice( $lines, 1 ) ) );
		}
		$color = '';
		if ( 0 === strpos( $block, '!!' ) ) {
			$color = '#dc2626';
			$block = trim( substr( $block, 2 ) );
		}
		if ( '' === $block ) {
			continue;
		}
		$style_parts = [ 'margin:0 0 12px', 'font-size:16px', 'line-height:1.55' ];
		if ( '' !== $align ) {
			$style_parts[] = 'text-align:' . $align;
		}
		if ( '' !== $color ) {
			$style_parts[] = 'color:' . $color;
			$style_parts[] = 'font-weight:800';
		}
		$rendered_lines = array_map(
			static function ( $line ) use ( $render_inline ) {
				return $render_inline( trim( $line ) );
			},
			explode( "\n", $block )
		);
		$html .= '<p style="' . implode( ';', $style_parts ) . '">' . implode( '<br>', $rendered_lines ) . '</p>';
	}

	return $html;
}

/**
 * Formats the intro price + currency for display, mirroring the SaaS
 * `_format_intro_price_display` (space thousands separator, comma decimals).
 *
 * @param mixed $price    Raw price string/number.
 * @param mixed $currency Currency label.
 * @return string e.g. "1 200 €" or "1 200,50 €".
 */
function xpressui_format_intro_price( $price, $currency ) {
	$raw_price     = trim( (string) $price );
	$currency_text = trim( (string) $currency );
	if ( '' === $raw_price ) {
		return $currency_text;
	}
	$normalized      = str_replace( [ "\xc2\xa0", ' ' ], '', $raw_price );
	$numeric_display = $raw_price;
	if ( preg_match( '/^[+-]?\d[\d.,]*$/', $normalized ) ) {
		$last_dot   = strrpos( $normalized, '.' );
		$last_comma = strrpos( $normalized, ',' );
		$decimal_sep = '';
		if ( false !== $last_dot && false !== $last_comma ) {
			$decimal_sep = $last_dot > $last_comma ? '.' : ',';
		} elseif ( false !== $last_comma ) {
			$trailing    = strlen( $normalized ) - $last_comma - 1;
			$decimal_sep = in_array( $trailing, [ 1, 2 ], true ) ? ',' : '';
		} elseif ( false !== $last_dot ) {
			$trailing    = strlen( $normalized ) - $last_dot - 1;
			$decimal_sep = in_array( $trailing, [ 1, 2 ], true ) ? '.' : '';
		}
		if ( '' !== $decimal_sep ) {
			$thousands_sep = '.' === $decimal_sep ? ',' : '.';
			$number_text   = str_replace( $decimal_sep, '.', str_replace( $thousands_sep, '', $normalized ) );
		} else {
			$number_text = str_replace( [ ',', '.' ], '', $normalized );
		}
		if ( is_numeric( $number_text ) ) {
			$amount = (float) $number_text;
			if ( abs( fmod( $amount, 1 ) ) < 0.005 ) {
				$numeric_display = number_format( $amount, 0, '.', ' ' );
			} else {
				$numeric_display = number_format( $amount, 2, ',', ' ' );
			}
		}
	}

	return trim( $numeric_display . ' ' . $currency_text );
}

/**
 * Resolves the hero image + ordered gallery list from a synced presentation,
 * mirroring the SaaS dedup logic (context.py): the hero is excluded from the
 * gallery; with no gallery + a hero on a non-showcase template the hero becomes
 * the single gallery image; on a gallery-showcase template with no hero the first
 * gallery image is promoted to the hero. https/http only, capped at 8.
 *
 * @param array  $presentation  Hosted Link presentation (from link.config.json).
 * @param string $page_template Resolved page template (e.g. 'gallery-showcase').
 * @return array{hero:string,gallery:string[]}
 */
function xpressui_resolve_intro_media( $presentation, $page_template = '' ) {
	$is_http = static function ( $url ) {
		return '' !== $url && ( stripos( $url, 'http://' ) === 0 || stripos( $url, 'https://' ) === 0 );
	};
	$hero = trim( (string) ( $presentation['heroImageUrl'] ?? '' ) );
	if ( ! $is_http( $hero ) ) {
		$hero = '';
	}
	$gallery   = [];
	$raw_items = $presentation['introImageUrls'] ?? [];
	if ( is_array( $raw_items ) ) {
		foreach ( $raw_items as $item ) {
			$url = trim( (string) $item );
			if ( ! $is_http( $url ) || $url === $hero || in_array( $url, $gallery, true ) ) {
				continue;
			}
			$gallery[] = $url;
			if ( count( $gallery ) >= 8 ) {
				break;
			}
		}
	}
	if ( empty( $gallery ) && '' !== $hero && 'gallery-showcase' !== $page_template ) {
		$gallery = [ $hero ];
	}
	if ( 'gallery-showcase' === $page_template && '' === $hero && ! empty( $gallery ) ) {
		$hero = array_shift( $gallery );
	}
	return [ 'hero' => $hero, 'gallery' => $gallery ];
}

/**
 * Embed-context overrides for the reused SaaS gallery-showcase stylesheet.
 *
 * The SaaS stylesheet styles the showcase as a full-page splash (fixed/100svh,
 * negative full-bleed margins, opaque background). In the inline WordPress embed
 * it flows in the page content, so neutralise those page-level rules while leaving
 * the component styling (stage, thumbnails, buy box, CTA) untouched for parity.
 *
 * @return string CSS appended via wp_add_inline_style().
 */
function xpressui_gallery_showcase_embed_css() {
	// The SaaS shell sizes its left column with 52vw (viewport-relative). Inside a
	// narrower theme content column (e.g. a Bootstrap .col-lg-8) that starves the right
	// column and squeezes/clips the buy box. On wide screens re-size both columns
	// relative to the grid container (fr units, container-relative) so they always fit
	// the available width; the SaaS mobile breakpoint (<=860px) keeps the stacked layout.
	return '.xpressui-embed-wrapper .xpressui-splash--gallery-showcase{position:relative;inset:auto;z-index:auto;min-height:0;padding:0;background:transparent;overflow:visible}'
		// The SaaS page ships a global `* { box-sizing: border-box }` reset that is not
		// loaded in the embed (only hosted-intro.css is). Without it the buy box CTA
		// (width:100% + padding) overflows its card. Restore border-box for the showcase.
		. '.xpressui-embed-wrapper .xpressui-splash--gallery-showcase,.xpressui-embed-wrapper .xpressui-splash--gallery-showcase *,.xpressui-embed-wrapper .xpressui-splash--gallery-showcase *::before,.xpressui-embed-wrapper .xpressui-splash--gallery-showcase *::after{box-sizing:border-box}'
		. '.xpressui-embed-wrapper .xpressui-gallery-showcase-hero{margin:0}'
		. '.xpressui-embed-wrapper .xpressui-gallery-showcase-shell{margin-bottom:clamp(20px,4vw,40px);max-width:none;overflow-x:visible}'
		// Render the buy-box CTA as a plain text link (accent + arrow), matching the
		// non-gallery intro CTA, instead of the SaaS filled button.
		. '.xpressui-embed-wrapper .xpressui-gallery-showcase-cta{display:inline-flex;align-items:center;gap:.35rem;width:auto;min-height:0;padding:0;border:0;border-radius:0;background:none!important;color:var(--xpressui-accent,#0f766e)!important;box-shadow:none!important;text-decoration:none!important;font-weight:800;font-size:.95rem;transition:opacity .15s ease}'
		. '.xpressui-embed-wrapper .xpressui-gallery-showcase-cta::after{content:"\2192";font-weight:700}'
		. '.xpressui-embed-wrapper .xpressui-gallery-showcase-cta:hover{opacity:.72;transform:none;filter:none;box-shadow:none!important}'
		. '@media (min-width:861px){.xpressui-embed-wrapper .xpressui-gallery-showcase-shell{grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);gap:clamp(24px,3vw,56px)}}';
}

/**
 * Renders the gallery-showcase intro (product-detail layout) from a synced Hosted
 * Link presentation, for pixel parity with the SaaS hosted-link render
 * (templates/hosted/link-intro-gallery.html.j2). Reuses the SaaS intro stylesheet
 * (assets/hosted-intro.css) and script (assets/hosted-intro.js), so this only
 * emits the same markup. Returns '' when the link is not a gallery-showcase.
 *
 * Differences from the SaaS render, by request: the public header is omitted (the
 * WordPress page chrome provides it), the workflow title <h1> is omitted (the
 * WordPress page title is shown instead), and the CTA targets the embedded form
 * mount node rather than #xpressui-root.
 *
 * @param array  $presentation  Hosted Link presentation (from link.config.json).
 * @param string $locale        'fr' or 'en'.
 * @param string $mount_node_id The form mount element id (CTA scroll target).
 * @param string $accent_color  Accent color for the CTA (--xpressui-accent).
 * @return string HTML (kses-safe), or '' when not a gallery-showcase link.
 */
function xpressui_render_gallery_showcase( $presentation, $locale = 'en', $mount_node_id = 'xpressui-root', $accent_color = '#0f766e' ) {
	if ( ! is_array( $presentation ) ) {
		return '';
	}
	$page_template = strtolower( trim( (string) ( $presentation['pageTemplate'] ?? '' ) ) );
	$media         = xpressui_resolve_intro_media( $presentation, $page_template );
	$hero          = $media['hero'];
	$gallery       = $media['gallery'];

	// is_gallery_showcase: explicit template, or >= 2 gallery images (matches SaaS).
	$is_showcase = ( 'gallery-showcase' === $page_template && ( '' !== $hero || ! empty( $gallery ) ) )
		|| count( $gallery ) >= 2;
	if ( ! $is_showcase ) {
		return '';
	}

	$source_label   = trim( (string) ( $presentation['sourceLabel'] ?? '' ) );
	$intro_title    = trim( (string) ( $presentation['introTitle'] ?? '' ) );
	$display_title  = '' !== $intro_title ? $intro_title : $source_label;
	$content_md     = $presentation['introContentMarkdown'] ?? ( $presentation['introDescription'] ?? '' );
	$content_html   = xpressui_render_intro_markdown( $content_md );
	$deadline       = trim( (string) ( $presentation['introDeadlineNotice'] ?? '' ) );
	$price_display  = xpressui_format_intro_price( $presentation['introPrice'] ?? '', $presentation['introCurrency'] ?? '' );
	$button_label   = trim( (string) ( $presentation['introButtonLabel'] ?? '' ) );
	if ( '' === $button_label ) {
		$button_label = '' !== $display_title
			? $display_title
			: ( 'fr' === $locale ? __( 'Ouvrir le formulaire', 'xpressui-bridge' ) : __( 'Open form', 'xpressui-bridge' ) );
	}
	$theme       = trim( (string) ( $presentation['theme'] ?? 'light' ) );
	$cta_target  = '#' . sanitize_html_class( $mount_node_id );
	$has_gallery = ! empty( $gallery );

	ob_start();
	?>
<div
	id="xpressui-splash"
	class="xpressui-splash xpressui-splash--gallery-showcase"
	data-page-template="<?php echo esc_attr( $page_template ?: 'gallery-showcase' ); ?>"
	data-xpressui-inline-intro="true"
	data-theme="<?php echo esc_attr( $theme ); ?>"
	role="region"
	aria-label="<?php echo esc_attr( $display_title ); ?>"
	style="--xpressui-accent: <?php echo esc_attr( $accent_color ); ?>;"
>
	<?php if ( '' !== $hero ) : ?>
	<section class="xpressui-gallery-showcase-hero" aria-label="<?php echo esc_attr( $display_title ); ?>">
		<div class="xpressui-gallery-showcase-hero-frame">
			<img src="<?php echo esc_url( $hero ); ?>" alt="" loading="eager" decoding="async">
		</div>
	</section>
	<?php endif; ?>

	<div
		class="xpressui-gallery-showcase-shell<?php echo $has_gallery ? '' : ' has-no-gallery'; ?>"
		<?php if ( $has_gallery ) : ?>data-xpressui-intro-gallery data-gallery-mode="showcase"<?php endif; ?>
	>
		<?php if ( $has_gallery ) : ?>
		<div class="xpressui-gallery-showcase-media">
			<div class="xpressui-gallery-showcase-stage" data-xpressui-gallery-stage>
				<?php foreach ( $gallery as $index => $image_url ) : ?>
				<img class="xpressui-splash-slide<?php echo 0 === $index ? ' is-active' : ''; ?>" src="<?php echo esc_url( $image_url ); ?>" alt=""<?php echo 0 === $index ? '' : ' loading="lazy"'; ?>>
				<?php endforeach; ?>
				<?php if ( count( $gallery ) > 1 ) : ?>
				<button class="xpressui-splash-gallery-button is-prev" type="button" data-xpressui-gallery-prev aria-label="<?php echo esc_attr( 'fr' === $locale ? __( 'Image précédente', 'xpressui-bridge' ) : __( 'Previous image', 'xpressui-bridge' ) ); ?>">&lsaquo;</button>
				<button class="xpressui-splash-gallery-button is-next" type="button" data-xpressui-gallery-next aria-label="<?php echo esc_attr( 'fr' === $locale ? __( 'Image suivante', 'xpressui-bridge' ) : __( 'Next image', 'xpressui-bridge' ) ); ?>">&rsaquo;</button>
				<?php endif; ?>
			</div>
			<?php if ( count( $gallery ) > 1 ) : ?>
			<div class="xpressui-gallery-showcase-thumbs" aria-label="<?php echo esc_attr( __( 'Image thumbnails', 'xpressui-bridge' ) ); ?>">
				<?php foreach ( $gallery as $index => $image_url ) : ?>
				<button class="xpressui-gallery-showcase-thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" type="button" data-xpressui-gallery-thumb="<?php echo (int) $index; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: image number */ __( 'Image %d', 'xpressui-bridge' ), $index + 1 ) ); ?>">
					<img src="<?php echo esc_url( $image_url ); ?>" alt="" loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>">
				</button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<div class="xpressui-gallery-showcase-copy template-product-detail-copy">
			<?php if ( '' !== $source_label && $source_label !== $display_title ) : ?>
			<p class="xpressui-gallery-showcase-kicker template-product-landing-kicker"><?php echo esc_html( $source_label ); ?></p>
			<?php endif; ?>

			<div class="xpressui-gallery-showcase-buy-box template-product-detail-buy-box">
				<?php if ( '' !== $price_display ) : ?>
				<div class="xpressui-gallery-showcase-price-row template-product-detail-price-row">
					<strong class="xpressui-gallery-showcase-price template-product-detail-price"><?php echo esc_html( $price_display ); ?></strong>
				</div>
				<?php endif; ?>
				<?php if ( '' !== $deadline ) : ?>
				<div class="xpressui-splash-deadline xpressui-gallery-showcase-deadline"><?php echo esc_html( $deadline ); ?></div>
				<?php endif; ?>
				<div class="xpressui-gallery-showcase-actions xpui-product-view-actions">
					<a class="xpressui-gallery-showcase-cta xpui-product-detail-checkout-btn" href="<?php echo esc_attr( $cta_target ); ?>"><?php echo esc_html( $button_label ); ?></a>
				</div>
			</div>

			<?php if ( '' !== $content_html ) : ?>
			<div class="xpressui-gallery-showcase-desc template-product-detail-description"><?php echo wp_kses_post( $content_html ); // built by xpressui_render_intro_markdown (user text already esc_html'd); wp_kses_post keeps the generated p/strong/em/br and satisfies output escaping. ?></div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $has_gallery ) : ?>
	<div class="xpressui-gallery-lightbox" data-xpressui-gallery-lightbox hidden aria-modal="true" role="dialog" aria-label="<?php echo esc_attr( __( 'Image preview', 'xpressui-bridge' ) ); ?>">
		<button type="button" class="xpressui-gallery-lightbox-close-btn" data-xpressui-gallery-lightbox-close aria-label="<?php echo esc_attr( __( 'Close image preview', 'xpressui-bridge' ) ); ?>">&times;</button>
		<?php if ( count( $gallery ) > 1 ) : ?>
		<button type="button" class="xpressui-gallery-lightbox-nav is-prev" data-xpressui-gallery-lightbox-prev aria-label="<?php echo esc_attr( 'fr' === $locale ? __( 'Image précédente', 'xpressui-bridge' ) : __( 'Previous image', 'xpressui-bridge' ) ); ?>">&lsaquo;</button>
		<button type="button" class="xpressui-gallery-lightbox-nav is-next" data-xpressui-gallery-lightbox-next aria-label="<?php echo esc_attr( 'fr' === $locale ? __( 'Image suivante', 'xpressui-bridge' ) : __( 'Next image', 'xpressui-bridge' ) ); ?>">&rsaquo;</button>
		<?php endif; ?>
		<img data-xpressui-gallery-lightbox-image alt="">
	</div>
	<?php endif; ?>
</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Renders the intro/welcome block (title + markdown content + price + deadline)
 * from a synced Hosted Link presentation, for parity with the SaaS splash intro.
 * Returns '' when none of the intro fields are set.
 *
 * @param array  $presentation Hosted Link presentation (from link.config.json).
 * @param string $locale       'fr' or 'en'.
 * @param string $style_handle Registered style handle for wp_add_inline_style().
 * @return string HTML (kses-safe: section/h2/div/span/p/strong/em/br).
 */
function xpressui_render_intro_welcome( $presentation, $locale = 'en', $style_handle = '', $cta_anchor_id = '', $accent_color = '#0f766e' ) {
	if ( ! is_array( $presentation ) ) {
		return '';
	}
	$title         = trim( (string) ( $presentation['introTitle'] ?? '' ) );
	$content_md    = $presentation['introContentMarkdown'] ?? ( $presentation['introDescription'] ?? '' );
	$content_html  = xpressui_render_intro_markdown( $content_md );
	$deadline      = trim( (string) ( $presentation['introDeadlineNotice'] ?? '' ) );
	$price_display = xpressui_format_intro_price( $presentation['introPrice'] ?? '', $presentation['introCurrency'] ?? '' );

	if ( '' === $title && '' === $content_html && '' === $deadline && '' === $price_display ) {
		return '';
	}

	// When a scroll target is provided, render a CTA that jumps to the embedded form.
	$button_label = '';
	if ( '' !== $cta_anchor_id ) {
		$button_label = trim( (string) ( $presentation['introButtonLabel'] ?? '' ) );
		if ( '' === $button_label ) {
			$button_label = 'fr' === $locale ? __( 'Accéder au formulaire', 'xpressui-bridge' ) : __( 'Go to the form', 'xpressui-bridge' );
		}
	}

	$intro_css = '.xpressui-intro{width:min(100%,900px);margin:0 auto 1.25rem;box-sizing:border-box;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.1rem 1.25rem;box-shadow:0 1px 2px rgba(15,23,42,.04)}'
		. '.xpressui-intro__title{margin:0 0 .6rem;font-size:1.15rem;font-weight:800;line-height:1.3;color:#0f172a}'
		. '.xpressui-intro__content{color:#334155;font-size:.95rem;line-height:1.55}'
		. '.xpressui-intro__content p:last-child{margin-bottom:0!important}'
		. '.xpressui-intro__meta{display:flex;flex-wrap:wrap;align-items:center;gap:.6rem;margin-top:.85rem}'
		. '.xpressui-intro__price{font-weight:800;font-size:1.05rem;color:#0f172a}'
		. '.xpressui-intro__deadline{display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .6rem;border-radius:999px;background:#fef3c7;color:#92400e;font-size:.82rem;font-weight:700;line-height:1.2}'
		. '.xpressui-intro__actions{margin-top:.85rem}'
		. '.xpressui-intro__cta{display:inline-flex;align-items:center;gap:.35rem;padding:0;background:none;border:0;color:var(--xpressui-accent,#0f766e)!important;font-weight:700;font-size:.95rem;text-decoration:none!important;cursor:pointer;transition:opacity .15s ease}'
		. '.xpressui-intro__cta::after{content:"\2192";font-weight:700;text-decoration:none}'
		. '.xpressui-intro__cta:hover{opacity:.72}';

	// Emit the CSS through the WordPress style API instead of a raw <style> echo. When no
	// caller handle is supplied, fall back to a registered no-src handle so the inline CSS is
	// still enqueued (and escaped by core) rather than printed unescaped.
	$intro_handle = ! empty( $style_handle ) ? $style_handle : xpressui_ensure_inline_style_handle();
	wp_add_inline_style( $intro_handle, $intro_css );

	$aria = '' !== $title
		? $title
		: ( 'fr' === $locale ? __( 'Introduction', 'xpressui-bridge' ) : __( 'Introduction', 'xpressui-bridge' ) );

	ob_start();
	?>
<section class="xpressui-intro" aria-label="<?php echo esc_attr( $aria ); ?>" style="--xpressui-accent: <?php echo esc_attr( $accent_color ); ?>;">
	<?php if ( '' !== $title ) : ?>
	<h2 class="xpressui-intro__title"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>
	<?php if ( '' !== $content_html ) : ?>
	<div class="xpressui-intro__content"><?php echo wp_kses_post( $content_html ); // built by xpressui_render_intro_markdown (user text already esc_html'd); wp_kses_post keeps the generated p/strong/em/br and satisfies output escaping. ?></div>
	<?php endif; ?>
	<?php if ( '' !== $price_display || '' !== $deadline ) : ?>
	<div class="xpressui-intro__meta">
		<?php if ( '' !== $price_display ) : ?><span class="xpressui-intro__price"><?php echo esc_html( $price_display ); ?></span><?php endif; ?>
		<?php if ( '' !== $deadline ) : ?><span class="xpressui-intro__deadline"><?php echo esc_html( $deadline ); ?></span><?php endif; ?>
	</div>
	<?php endif; ?>
	<?php if ( '' !== $button_label ) : ?>
	<div class="xpressui-intro__actions">
		<a class="xpressui-intro__cta" href="#<?php echo esc_attr( $cta_anchor_id ); ?>"><?php echo esc_html( $button_label ); ?></a>
	</div>
	<?php endif; ?>
</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Renders the [xpressui id="project-slug"] shortcode.
 *
 * Inline rendering: form HTML and CSS are output directly into the page.
 * The runtime UMD and init script are enqueued from plugin assets (never
 * from the uploads directory), satisfying WordPress.org guideline 8.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string HTML output.
 */
function xpressui_render_shortcode( $atts ) {
	$atts = shortcode_atts(
		[
			'id'      => '',
			'link'    => '',
			'link_id' => '',
			'title'   => __( 'XPressUI Form', 'xpressui-bridge' ),
		],
		$atts,
		'xpressui'
	);

	$link_attr = isset( $atts['link'] ) && '' !== $atts['link']
		? sanitize_file_name( (string) $atts['link'] )
		: ( isset( $atts['link_id'] ) && '' !== $atts['link_id'] ? sanitize_file_name( (string) $atts['link_id'] ) : '' );

	$slug = '';
	$link_config = null;

	if ( $link_attr !== '' ) {
		$base_dir = xpressui_get_workflows_base_dir();
		if ( $base_dir !== '' && is_dir( $base_dir ) ) {
			$subdirs = glob( trailingslashit( $base_dir ) . '*', GLOB_ONLYDIR );
			if ( is_array( $subdirs ) ) {
				foreach ( $subdirs as $subdir ) {
					$potential_slug = basename( $subdir );
					$config_file = trailingslashit( $subdir ) . 'hosted-links/' . $link_attr . '/link.config.json';
					if ( file_exists( $config_file ) ) {
						$slug = $potential_slug;
						$link_config_raw = file_get_contents( $config_file );
						if ( is_string( $link_config_raw ) ) {
							$link_config = json_decode( $link_config_raw, true );
						}
						break;
					}
				}
			}
		}
	} else {
		$slug = sanitize_title( (string) $atts['id'] );
	}

	if ( $slug === '' ) {
		return wp_kses_post(
			'<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: the "id" or "link" attribute is required.', 'xpressui-bridge' )
			. '</p>'
		);
	}

	// Headless product catalog: when this hosted link fronts a product catalog and the
	// snapshot was synced (hosted-links/<id>/catalogs/catalog.json), render the storefront
	// (grid / product detail / cart summary) server-side (SEO/LCP) instead of the form.
	// The whole journey stays on WordPress. The final checkout step (?xpui_checkout=1)
	// falls through to the hosted-link FORM below, which collects the order and submits
	// to WordPress — so it is excluded here. The storefront is not subject to form
	// expiry/submission gating, so this returns before the link-status checks below.
	// Public, read-only storefront navigation flag (links are generated by the catalog
	// renderer). filter_input() reads it without touching the raw superglobal — so no nonce
	// is required for this stateless GET read — and a nonce would wrongly break public caching.
	$is_catalog_checkout = ! empty( filter_input( INPUT_GET, 'xpui_checkout', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
	if ( $link_attr !== '' && is_array( $link_config ) && ! $is_catalog_checkout ) {
		$fc_presentation = is_array( $link_config['presentation'] ?? null ) ? $link_config['presentation'] : [];
		$front_catalog   = is_array( $fc_presentation['frontCatalog'] ?? null ) ? $fc_presentation['frontCatalog'] : [];
		if ( ! empty( $front_catalog['catalogId'] ) ) {
			$catalog_snapshot = xpressui_get_hosted_link_catalog( $slug, $link_attr );
			if ( is_array( $catalog_snapshot ) ) {
				$catalog_embed = xpressui_render_hosted_catalog_embed( $catalog_snapshot, $slug, $link_attr );
				if ( '' !== $catalog_embed ) {
					return $catalog_embed;
				}
			}
		}
	}

	if ( is_array( $link_config ) ) {
		$link_status = $link_config['status'] ?? 'active';

		$expires_at = ! empty( $link_config['expiresAt'] ) ? strtotime( $link_config['expiresAt'] ) : null;
		if ( $expires_at && $expires_at <= time() ) {
			$link_status = 'expired';
		}

		$max_subs = isset( $link_config['maxSubmissions'] ) ? (int) $link_config['maxSubmissions'] : 0;
		$sub_count = isset( $link_config['submissionCount'] ) ? (int) $link_config['submissionCount'] : 0;
		if ( $max_subs > 0 && $sub_count >= $max_subs ) {
			$link_status = 'used_up';
		}

		if ( 'active' !== $link_status ) {
			$close_message = '';
			if ( 'paused' === $link_status ) {
				$close_msg = (string) ( $link_config['payload']['closeMessage'] ?? '' );
				$close_message = $close_msg !== '' ? $close_msg : __( 'This form is currently paused.', 'xpressui-bridge' );
			} elseif ( 'maintenance' === $link_status ) {
				$maintenance_msg = (string) ( $link_config['payload']['maintenanceMessage'] ?? '' );
				$close_message = $maintenance_msg !== '' ? $maintenance_msg : __( 'This form is under maintenance.', 'xpressui-bridge' );
			} elseif ( 'expired' === $link_status ) {
				$close_message = __( 'This link has expired.', 'xpressui-bridge' );
			} elseif ( 'used_up' === $link_status ) {
				$close_message = __( 'This link has already reached its maximum submission limit.', 'xpressui-bridge' );
			} else {
				$close_message = __( 'This form is currently unavailable.', 'xpressui-bridge' );
			}
			return wp_kses_post(
				'<div class="xpressui-embed-wrapper xpressui-inline-embed xpressui-link-closed">'
				. '<div class="xpressui-closed-card">'
				. '<p style="text-align:center;padding:24px;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;border-radius:8px;font-weight:500;">'
				. esc_html( $close_message )
				. '</p>'
				. '</div>'
				. '</div>'
			);
		}
	}

	$base_dir = xpressui_get_workflows_base_dir();
	if ( $base_dir === '' ) {
		return wp_kses_post(
			'<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: could not resolve the uploads directory.', 'xpressui-bridge' )
			. '</p>'
		);
	}

	$package_dir = xpressui_get_workflow_package_dir( $slug );

	if ( ! is_dir( $package_dir ) || ! xpressui_workflow_directory_has_required_artifacts( $package_dir ) ) {
		return wp_kses_post(
			'<p class="xpressui-embed-error">'
			. esc_html(
				sprintf(
					/* translators: %s: project slug */
					__( '[xpressui] error: no package found for id "%s". Please install the workflow package first.', 'xpressui-bridge' ),
					$slug
				)
			)
			. '</p>'
		);
	}

	// Load template context from uploads — JSON data only, no executable code.
	$template_context = xpressui_load_workflow_template_context( $slug );
	if ( empty( $template_context ) ) {
		// No template.context.json — build a minimal skeleton; rendered_form will be
		// populated from form.config.json below if that file exists.
		$template_context = [
			'project' => [ 'id' => $slug, 'slug' => $slug, 'name' => $slug ],
			'theme'   => [],
			'target'  => 'wordpress',
			'runtime' => [],
		];
	}

	// Allow extensions (e.g. the pro plugin) to modify the template context before rendering.
	$template_context = apply_filters( 'xpressui_template_context', $template_context, $slug );

	$show_project_title  = xpressui_get_project_setting_flag( $slug, 'showProjectTitle', false );
	$show_required_note  = xpressui_get_project_setting_flag( $slug, 'showRequiredFieldsNote', false );
	$section_label_visibility = xpressui_get_project_setting_choice( $slug, 'sectionLabelVisibility', [ 'auto', 'show', 'hide' ], 'auto' );
	$section_count = 0;
	if ( is_array( $template_context['rendered_form']['sections'] ?? null ) ) {
		$section_count = count( $template_context['rendered_form']['sections'] );
	}
	$show_section_headers = 'show' === $section_label_visibility
		? true
		: ( 'hide' === $section_label_visibility ? false : $section_count > 1 );
	// Resolve form config: prefer inlined runtime.form_config_json, fall back to form.config.json.
	$raw_form_config_json = $template_context['runtime']['form_config_json'] ?? '';
	if ( '' === $raw_form_config_json ) {
		$raw_form_config_json = xpressui_get_workflow_artifact_contents( $slug, 'config', 'form.config.json' );
	}
	$form_config = is_string( $raw_form_config_json ) && '' !== $raw_form_config_json
		? json_decode( $raw_form_config_json, true )
		: null;

	// The workflow's authored title visibility (config side). The SaaS default is
	// "shown" unless the project explicitly disables it; mirror that so a hosted-link
	// embed respects the config instead of always hiding the title.
	$config_show_title = ! is_array( $form_config ) || ! array_key_exists( 'showProjectTitle', $form_config )
		? true
		: (bool) $form_config['showProjectTitle'];

	if ( is_array( $form_config ) ) {
		$form_config = xpressui_normalize_form_config( $form_config, $slug );

		if ( is_array( $link_config ) ) {
			$presentation = is_array( $link_config['presentation'] ?? null ) ? $link_config['presentation'] : [];
			$payload_data = is_array( $link_config['payload'] ?? null ) ? $link_config['payload'] : [];

			if ( ! is_array( $form_config['workflowConfig'] ?? null ) ) {
				$form_config['workflowConfig'] = [];
			}

			if ( ! empty( $presentation['successMessage'] ) ) {
				$form_config['workflowConfig']['successMessage'] = $presentation['successMessage'];
			}
			if ( ! empty( $presentation['successTitle'] ) ) {
				$form_config['workflowConfig']['successTitle'] = $presentation['successTitle'];
			}
			if ( ! empty( $presentation['successRedirectUrl'] ) ) {
				$form_config['workflowConfig']['redirectUrl'] = $presentation['successRedirectUrl'];
			}
			if ( ! empty( $presentation['bookingUrl'] ) ) {
				$form_config['workflowConfig']['bookingUrl'] = $presentation['bookingUrl'];
			}
			if ( ! empty( $presentation['bookingButtonLabel'] ) ) {
				$form_config['workflowConfig']['bookingButtonLabel'] = $presentation['bookingButtonLabel'];
			}
			if ( ! empty( $payload_data['notifyEmails'] ) ) {
				$form_config['workflowConfig']['notifyEmail'] = implode( ',', (array) $payload_data['notifyEmails'] );
			}
			if ( ! empty( $payload_data['webhookUrl'] ) ) {
				$form_config['workflowConfig']['webhookUrl'] = $payload_data['webhookUrl'];
			}
			if ( ! is_array( $form_config['submit'] ?? null ) ) {
				$form_config['submit'] = [];
			}
			if ( ! is_array( $form_config['submit']['metadata'] ?? null ) ) {
				$form_config['submit']['metadata'] = [];
			}
			$form_config['submit']['metadata']['hostedLinkId'] = $link_config['id'];

			// Override properties in template context
			if ( ! empty( $link_config['label'] ) ) {
				$template_context['project']['name'] = $link_config['label'];
			}
			if ( ! empty( $presentation['backgroundImageUrl'] ) ) {
				$template_context['project']['background_image_url'] = $presentation['backgroundImageUrl'];
			}
			if ( ! empty( $presentation['accentColor'] ) ) {
				$template_context['theme']['colors']['primary'] = $presentation['accentColor'];
			}
			if ( ! empty( $presentation['pageTemplate'] ) ) {
				$template_context['theme']['frame_style'] = $presentation['pageTemplate'] === 'panel' ? 'panel' : 'card';
			}
		} else {
			if ( ! is_array( $form_config['workflowConfig'] ?? null ) ) {
				$form_config['workflowConfig'] = [];
			}
			if ( empty( $form_config['workflowConfig']['redirectUrl'] ) ) {
				// No hosted link (default render) → redirect to the site home.
				$form_config['workflowConfig']['redirectUrl'] = home_url( '/' );
			}
		}

		// Apply local settings overrides (takes precedence over SaaS synced config for the local shortcode embed)
		$all_settings  = get_option( 'xpressui_project_settings', [] );
		$project_settings = is_array( $all_settings[ $slug ] ?? null ) ? $all_settings[ $slug ] : [];
		if ( ! empty( $project_settings['submitSuccessMessage'] ) ) {
			$form_config['workflowConfig']['successMessage'] = $project_settings['submitSuccessMessage'];
		}
		if ( ! empty( $project_settings['submitErrorMessage'] ) ) {
			$form_config['workflowConfig']['errorMessage'] = $project_settings['submitErrorMessage'];
		}
		if ( ! empty( $project_settings['redirectUrl'] ) ) {
			$form_config['workflowConfig']['redirectUrl'] = $project_settings['redirectUrl'];
		}
		if ( ! empty( $project_settings['bookingUrl'] ) ) {
			$form_config['workflowConfig']['bookingUrl'] = $project_settings['bookingUrl'];
		}
		if ( ! empty( $project_settings['bookingButtonLabel'] ) ) {
			$form_config['workflowConfig']['bookingButtonLabel'] = $project_settings['bookingButtonLabel'];
		}
		if ( ! empty( $project_settings['notifyEmail'] ) ) {
			$form_config['workflowConfig']['notifyEmail'] = $project_settings['notifyEmail'];
		}
		if ( ! empty( $project_settings['webhookUrl'] ) ) {
			$form_config['workflowConfig']['webhookUrl'] = $project_settings['webhookUrl'];
		}

		$fresh_rendered_form = xpressui_build_rendered_form_from_config( $form_config );
		// Refresh rendered_form sections from the live form config so shortcode output
		// stays aligned with the current runtime even when template.context.json is stale.
		if ( ! is_array( $template_context['rendered_form'] ?? null ) ) {
			$template_context['rendered_form'] = $fresh_rendered_form;
		} elseif ( is_array( $fresh_rendered_form['sections'] ?? null ) ) {
			$template_context['rendered_form']['sections'] = $fresh_rendered_form['sections'];
			if ( isset( $fresh_rendered_form['navigation_labels'] ) ) {
				$template_context['rendered_form']['navigation_labels'] = $fresh_rendered_form['navigation_labels'];
			}
			if ( isset( $fresh_rendered_form['submit_label'] ) ) {
				$template_context['rendered_form']['submit_label'] = $fresh_rendered_form['submit_label'];
			}
			if ( isset( $fresh_rendered_form['step_status'] ) ) {
				$template_context['rendered_form']['step_status'] = array_merge(
					is_array( $template_context['rendered_form']['step_status'] ?? null )
						? $template_context['rendered_form']['step_status']
						: [],
					$fresh_rendered_form['step_status']
				);
			}
		}
		// Recompute section count now that rendered_form is populated/refreshed.
		$section_count        = count( $template_context['rendered_form']['sections'] ?? [] );
		$show_section_headers = 'show' === $section_label_visibility
			? true
			: ( 'hide' === $section_label_visibility ? false : $section_count > 1 );
		if ( ! is_array( $template_context['runtime'] ?? null ) ) {
			$template_context['runtime'] = [];
		}
		$form_config['showProjectTitle']       = is_array( $link_config ) ? $config_show_title : $show_project_title;
		$form_config['showRequiredFieldsNote'] = $show_required_note;
		$form_config['sectionLabelVisibility'] = $section_label_visibility;
		$template_context['runtime']['form_config_json'] = wp_json_encode( $form_config );
	}

	// Apply show_* flags to rendered_form (works whether built above or loaded from template context).
	if ( is_array( $template_context['rendered_form'] ?? null ) ) {
		// Respect the workflow's authored title visibility on a synced Hosted Link embed
		// (shown unless disabled in the config); fall back to the local flag otherwise.
		$template_context['rendered_form']['show_title']           = is_array( $link_config ) ? $config_show_title : $show_project_title;
		$template_context['rendered_form']['show_subtitle']        = $show_required_note;
		$template_context['rendered_form']['show_section_headers'] = $show_section_headers;
		// Ensure camera-photo-list/camera-photo fields always have the right input_type and
		// photo_placeholder_slots (may be absent in old exports that pre-date this feature).
		if ( ! empty( $template_context['rendered_form']['sections'] ) ) {
			$patched_sections = [];
			foreach ( $template_context['rendered_form']['sections'] as $xp_sec ) {
				if ( ! empty( $xp_sec['fields'] ) ) {
					$patched_fields = [];
					foreach ( $xp_sec['fields'] as $xp_fld ) {
						$xp_type = (string) ( $xp_fld['type'] ?? '' );
						if ( $xp_type === 'camera-photo-list' ) {
							$xp_fld['input_type'] = 'file';
							if ( ! isset( $xp_fld['photo_placeholder_slots'] ) ) {
								$xp_min                              = (int) ( $xp_fld['minFiles'] ?? $xp_fld['min_files'] ?? 2 );
								$xp_max                              = (int) ( $xp_fld['maxFiles'] ?? $xp_fld['max_files'] ?? 5 );
								$xp_fld['min_files']                 = $xp_min;
								$xp_fld['max_files']                 = $xp_max;
								$xp_fld['photo_placeholder_slots']   = array_fill( 0, $xp_max, null );
							}
						} elseif ( $xp_type === 'camera-photo' || $xp_type === 'payment-proof' ) {
							$xp_fld['input_type'] = 'file';
							if ( $xp_type === 'payment-proof' && empty( $xp_fld['accept'] ) ) {
								$xp_fld['accept'] = '.pdf,application/pdf,.jpg,.jpeg,image/jpeg,.png,image/png';
							}
						}
						$patched_fields[] = $xp_fld;
					}
					$xp_sec['fields'] = $patched_fields;
				}
				$patched_sections[] = $xp_sec;
			}
			$template_context['rendered_form']['sections'] = $patched_sections;
		}
	}

	// Ensure the PHP template runtime helpers are available.
	$runtime_file = XPRESSUI_BRIDGE_DIR . 'templates/runtime.php';
	if ( ! file_exists( $runtime_file ) ) {
		return wp_kses_post(
			'<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: template runtime not found.', 'xpressui-bridge' )
			. '</p>'
		);
	}
	require_once $runtime_file;

	// Ensure the fragment template exists.
	$fragment_path = XPRESSUI_BRIDGE_DIR . 'templates/form-fragment.php';
	if ( ! file_exists( $fragment_path ) ) {
		return wp_kses_post(
			'<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: form fragment template not found.', 'xpressui-bridge' )
			. '</p>'
		);
	}

	// Inject unique IDs so multiple forms on the same page don't collide.
	// _mount_node_id  → outer .page-shell div (init.js mounts here)
	// runtime.mount_node_id → inner runtime-mount div (rendered-form wrapper)
	$mount_node_id                      = 'xpressui-root-' . $slug;
	$config_script_id                   = 'xpressui-config-' . $slug;
	$template_context['_mount_node_id'] = $mount_node_id;
	if ( is_array( $template_context['runtime'] ?? null ) ) {
		$template_context['runtime']['mount_node_id']        = 'xpressui-mount-' . $slug;
		
		$booking_url = '';
		if ( is_array( $link_config ) && ! empty( $link_config['presentation']['bookingUrl'] ) ) {
			$booking_url = $link_config['presentation']['bookingUrl'];
		}
		$local_booking_url = xpressui_get_project_setting( $slug, 'bookingUrl' );
		if ( ! empty( $local_booking_url ) ) {
			$booking_url = $local_booking_url;
		}
		$template_context['runtime']['booking_url'] = $booking_url;

		$_booking_btn = '';
		if ( is_array( $link_config ) && ! empty( $link_config['presentation']['bookingButtonLabel'] ) ) {
			$_booking_btn = $link_config['presentation']['bookingButtonLabel'];
		}
		$local_booking_btn = xpressui_get_project_setting( $slug, 'bookingButtonLabel' );
		if ( ! empty( $local_booking_btn ) ) {
			$_booking_btn = $local_booking_btn;
		}
		$template_context['runtime']['booking_button_label'] = '' !== $_booking_btn ? $_booking_btn : __( 'Book an appointment', 'xpressui-bridge' );
	}

	// Render the form fragment (HTML only; CSS and scripts are enqueued below).
	// form-fragment.php lives in templates/ (not generated/) as it is manually maintained.
	// Read-only, admin-gated preview toggle (a manual URL param, no state change). filter_input()
	// reads it without the raw superglobal, so it is sanitized and needs no nonce verification.
	$is_preview = '1' === filter_input( INPUT_GET, 'xpui_preview', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) && current_user_can( 'manage_options' );

	ob_start();
	$xpressui_ctx = $template_context;
	include $fragment_path;
	$fragment_html = (string) ob_get_clean();

	// -----------------------------------------------------------------
	// Enqueue the bundled XPressUI light runtime.
	// Served from plugin/runtime/, never from uploads.
	// -----------------------------------------------------------------
	$runtime_handle    = 'xpressui-runtime';
	$runtime_url       = XPRESSUI_BRIDGE_URL . 'runtime/xpressui-' . XPRESSUI_BRIDGE_RUNTIME_VERSION . '.umd.js';
	$runtime_file_path = XPRESSUI_BRIDGE_DIR . 'runtime/xpressui-' . XPRESSUI_BRIDGE_RUNTIME_VERSION . '.umd.js';

	// Allow extensions (e.g. the pro plugin) to override the runtime URL and file path.
	$runtime_url       = (string) apply_filters( 'xpressui_runtime_url', $runtime_url, $slug );
	$runtime_file_path = (string) apply_filters( 'xpressui_runtime_file_path', $runtime_file_path, $slug );

	// Use file mtime as cache-bust version so rebuilds invalidate the browser cache without a version bump.
	$runtime_ver = file_exists( $runtime_file_path ) ? (string) filemtime( $runtime_file_path ) : XPRESSUI_BRIDGE_RUNTIME_VERSION;

	wp_enqueue_script(
		$runtime_handle,
		$runtime_url,
		[],
		$runtime_ver,
		false
	);

	wp_enqueue_script(
		'xpressui-header-offset',
		XPRESSUI_BRIDGE_URL . 'assets/shell/xpressui-header-offset.js',
		[],
		XPRESSUI_BRIDGE_VERSION,
		true
	);

	// Enqueue the shell init script (depends on the runtime and header offset).
	wp_enqueue_script(
		'xpressui-shell-init',
		XPRESSUI_BRIDGE_URL . 'assets/shell/plugin-shell-init.js',
		[ $runtime_handle, 'xpressui-header-offset' ],
		XPRESSUI_BRIDGE_VERSION,
		true
	);

	// Inject REST endpoint, translations, and shell metadata before init runs.
	$booking_url = '';
	if ( is_array( $link_config ) && ! empty( $link_config['presentation']['bookingUrl'] ) ) {
		$booking_url = $link_config['presentation']['bookingUrl'];
	}
	$local_booking_url = xpressui_get_project_setting( $slug, 'bookingUrl' );
	if ( ! empty( $local_booking_url ) ) {
		$booking_url = $local_booking_url;
	}

	$shell_meta = [
		'mountNodeId'   => $mount_node_id,
		'configId'      => $config_script_id,
		'slug'          => $slug,
		'runtimeSource' => 'plugin-bundled',
		'runtimeUrl'    => $runtime_url,
		'bookingUrl'    => $booking_url,
	];

	$inline_before  = 'window.XPRESSUI_WORDPRESS_REST_URL = ' . wp_json_encode( rest_url( 'xpressui/v1/submit' ) ) . ';';
	$inline_before .= 'window.XPRESSUI_I18N = ' . wp_json_encode( xpressui_get_shell_translations() ) . ';';
	$inline_before .= 'window.XPRESSUI_SHELL_META = ' . wp_json_encode( $shell_meta ) . ';';

	wp_add_inline_script( 'xpressui-shell-init', $inline_before, 'before' );

	// Enqueue the shortcode-specific CSS via the WordPress style API.
	// The static CSS file covers component base styles; dynamic/scoped overrides
	// (theme colours, mount-ID scope) are appended as an inline style block.
	wp_enqueue_style( 'dashicons' );

	$style_handle = 'xpressui-shortcode-' . sanitize_key( $slug );
	$static_css_url  = XPRESSUI_BRIDGE_URL . 'assets/css/xpressui-shortcode.css';
	$static_css_path = XPRESSUI_BRIDGE_DIR . 'assets/css/xpressui-shortcode.css';
	$static_css_ver  = file_exists( $static_css_path ) ? (string) filemtime( $static_css_path ) : XPRESSUI_BRIDGE_VERSION;
	if ( file_exists( $static_css_path ) ) {
		wp_enqueue_style( $style_handle, $static_css_url, [], $static_css_ver );
	} else {
		// No static file yet — register a virtual handle so wp_add_inline_style works.
		wp_register_style( $style_handle, false, [], XPRESSUI_BRIDGE_VERSION );
		wp_enqueue_style( $style_handle );
	}
	$inline_css = xpressui_build_shortcode_inline_css( $template_context, $mount_node_id );
	if ( '' !== $inline_css ) {
		wp_add_inline_style( $style_handle, $inline_css );
	}

	// Per-workflow appearance overrides (primary color, surface, page background,
	// frame/font, radii) are applied earlier via the `xpressui_template_context`
	// filter (includes/overlay.php → xpressui_pro_patch_theme), which patches
	// $template_context['theme'] before xpressui_build_shortcode_inline_css() emits
	// the --template-* CSS variables above. No separate customizer pass is needed.

	// Resume-mode detection: set data-resume-loading on the mount element when
	// ?xpressui_resume= is present. Delivered via wp_add_inline_script.
	$resume_script = 'try{if(/[?&]xpressui_resume=/.test(location.search)){var _xpEl=document.getElementById(' . wp_json_encode( $mount_node_id ) . ');if(_xpEl)_xpEl.setAttribute("data-resume-loading","");}}catch(e){}';
	wp_add_inline_script( 'xpressui-shell-init', $resume_script, 'after' );

	// Dynamic pre-filling via URL parameters
	$prefill_script = 'try{var params=new URLSearchParams(location.search);params.forEach(function(val,key){var input=document.querySelector(\'[name="\'+key+\'"], [data-field-id="\'+key+\'"], #\'+key);if(input){input.value=val;input.dispatchEvent(new Event("input",{bubbles:true}));input.dispatchEvent(new Event("change",{bubbles:true}));}});}catch(e){}';
	wp_add_inline_script( 'xpressui-shell-init', $prefill_script, 'after' );

	// Local Draft Auto-Save & Restore
	$draft_script = 'try{(function(){'
		. 'var slug=' . wp_json_encode( $slug ) . ';'
		. 'var storageKey="xpui_draft_"+slug;'
		. 'var mountId=' . wp_json_encode( $mount_node_id ) . ';'
		. 'var origFetch=window.fetch;'
		. 'window.fetch=function(){'
		. 'var args=arguments;'
		. 'return origFetch.apply(this,args).then(function(res){'
		. 'try{if(res.ok&&typeof args[0]==="string"&&args[0].indexOf("/xpressui/v1/submit")!==-1){localStorage.removeItem(storageKey);}}catch(e){}'
		. 'return res;'
		. '});'
		. '};'
		. 'document.addEventListener("DOMContentLoaded",function(){'
		. 'var container=document.getElementById(mountId);'
		. 'if(!container)return;'
		. 'container.addEventListener("input",function(e){'
		. 'var t=e.target;'
		. 'if(t&&(t.tagName==="INPUT"||t.tagName==="TEXTAREA"||t.tagName==="SELECT")){'
		. 'if(t.type==="password"||t.name==="xpressui_honeypot")return;'
		. 'var key=t.name||t.getAttribute("data-field-id")||t.id;'
		. 'if(key){'
		. 'var draft={};'
		. 'try{draft=JSON.parse(localStorage.getItem(storageKey)||"{}");}catch(ex){}'
		. 'if(t.type==="checkbox"){draft[key]=t.checked;}'
		. 'else if(t.type==="radio"){if(t.checked){draft[key]=t.value;}}'
		. 'else{draft[key]=t.value;}'
		. 'localStorage.setItem(storageKey,JSON.stringify(draft));'
		. '}'
		. '}'
		. '});'
		. 'var restore=function(){'
		. 'try{'
		. 'var draft=JSON.parse(localStorage.getItem(storageKey)||"{}");'
		. 'var params=new URLSearchParams(location.search);'
		. 'for(var key in draft){'
		. 'if(params.has(key))continue;'
		. 'var val=draft[key];'
		. 'var input=container.querySelector(\'[name="\'+key+\'"], [data-field-id="\'+key+\'"], #\'+key);'
		. 'if(input){'
		. 'if(input.type==="checkbox"){input.checked=!!val;}'
		. 'else if(input.type==="radio"){'
		. 'var r=container.querySelector(\'[name="\'+key+\'"][value="\'+val+\'"]\');'
		. 'if(r)r.checked=true;'
		. '}else{input.value=val;}'
		. 'input.dispatchEvent(new Event("input",{bubbles:true}));'
		. 'input.dispatchEvent(new Event("change",{bubbles:true}));'
		. '}'
		. '}'
		. '}catch(ex){}'
		. '};'
		. 'setTimeout(restore,500);'
		. 'setTimeout(restore,1500);'
		. 'setTimeout(restore,3000);'
		. '});'
		. '})();}catch(e){}';
	wp_add_inline_script( 'xpressui-shell-init', $draft_script, 'after' );

	// Handle reference documents direct download interception for cross-origin URLs.
	$download_script = 'try{document.addEventListener("click",function(e){var btn=e.target.closest(".xpressui-ref-docs__download");if(!btn)return;var url=btn.getAttribute("href");if(!url)return;e.preventDefault();btn.style.opacity="0.5";btn.style.pointerEvents="none";fetch(url).then(function(res){if(!res.ok)throw new Error("Fetch failed");return res.blob();}).then(function(blob){var blobUrl=URL.createObjectURL(blob);var a=document.createElement("a");a.href=blobUrl;a.download=btn.getAttribute("download")||url.substring(url.lastIndexOf("/")+1)||"document";document.body.appendChild(a);a.click();document.body.removeChild(a);URL.revokeObjectURL(blobUrl);}).catch(function(err){console.error("Download failed:",err);window.open(url,"_blank","noopener,noreferrer");}).finally(function(){btn.style.opacity="";btn.style.pointerEvents="";});});}catch(e){}';
	wp_add_inline_script( 'xpressui-shell-init', $download_script, 'after' );

	do_action( 'xpressui_shortcode_scripts_enqueued', $slug, $template_context );

	// Embed the form config as inert JSON markup read by plugin-shell-init.js.
	$form_config_json = $template_context['runtime']['form_config_json'] ?? '{}';

	$config_tag = '<template id="' . esc_attr( $config_script_id ) . '">'
		. esc_html( wp_json_encode( json_decode( $form_config_json, true ) ) )
		. '</template>';

	// Parity with the SaaS hosted render. The intro renders OUTSIDE the form card with
	// a CTA that scrolls to the form: a gallery-showcase link renders the full
	// product-detail intro (hero + gallery + buy box), reusing the SaaS stylesheet/
	// script verbatim; other links render the intro/welcome block (title + content +
	// price/deadline + CTA). The reference-documents block sits above the form, inside
	// the card.
	// A gallery-showcase renders full-width OUTSIDE the form card (in the wrapper). The
	// non-gallery intro/welcome card renders INSIDE the page-shell, just above the form
	// card, so it shares the form card's container and is always the exact same width.
	$intro_outside_html = '';
	$intro_card_html    = '';
	$prelude_html       = '';
	if ( is_array( $link_config ) ) {
		$pre_presentation = is_array( $link_config['presentation'] ?? null ) ? $link_config['presentation'] : [];
		$pre_locale       = ( ( $pre_presentation['locale'] ?? '' ) === 'fr' ) ? 'fr' : 'en';
		$pre_accent       = trim( (string) ( $pre_presentation['accentColor'] ?? '' ) );
		if ( '' === $pre_accent ) {
			$pre_accent = '#0f766e';
		}

		$intro_outside_html = xpressui_render_gallery_showcase( $pre_presentation, $pre_locale, $mount_node_id, $pre_accent );
		if ( '' !== $intro_outside_html ) {
			$intro_css_path = XPRESSUI_BRIDGE_DIR . 'assets/hosted-intro.css';
			$intro_css_ver  = file_exists( $intro_css_path ) ? (string) filemtime( $intro_css_path ) : XPRESSUI_BRIDGE_VERSION;
			wp_enqueue_style( 'xpressui-hosted-intro', XPRESSUI_BRIDGE_URL . 'assets/hosted-intro.css', [], $intro_css_ver );
			wp_add_inline_style( 'xpressui-hosted-intro', xpressui_gallery_showcase_embed_css() );

			$intro_js_path = XPRESSUI_BRIDGE_DIR . 'assets/hosted-intro.js';
			$intro_js_ver  = file_exists( $intro_js_path ) ? (string) filemtime( $intro_js_path ) : XPRESSUI_BRIDGE_VERSION;
			wp_enqueue_script( 'xpressui-hosted-intro', XPRESSUI_BRIDGE_URL . 'assets/hosted-intro.js', [], $intro_js_ver, true );
		} else {
			// Non-gallery link: intro/welcome card above the form, with a CTA to the form.
			$intro_card_html = xpressui_render_intro_welcome( $pre_presentation, $pre_locale, $style_handle, $mount_node_id, $pre_accent );
		}

		$prelude_html = xpressui_render_reference_documents( $pre_presentation, $pre_locale, $style_handle );
	}

	// Inject the intro/welcome card inside the page-shell, right before the form card
	// (<main class="form-frame">), so both cards share the same container + width.
	if ( '' !== $intro_card_html ) {
		$ff_class_pos = strpos( $fragment_html, 'class="form-frame' );
		$tag_start    = false !== $ff_class_pos ? strrpos( substr( $fragment_html, 0, $ff_class_pos ), '<' ) : false;
		if ( false !== $tag_start ) {
			$fragment_html = substr( $fragment_html, 0, $tag_start ) . $intro_card_html . substr( $fragment_html, $tag_start );
		} else {
			$fragment_html = $intro_card_html . $fragment_html;
		}
	}

	if ( '' !== $prelude_html ) {
		$form_pos = strpos( $fragment_html, '<form' );
		if ( false !== $form_pos ) {
			$fragment_html = substr( $fragment_html, 0, $form_pos ) . $prelude_html . substr( $fragment_html, $form_pos );
		} else {
			$fragment_html = $prelude_html . $fragment_html;
		}
	}

	// Catalog checkout (?xpui_checkout=1): show the read-only Order summary above the
	// form, mirroring the SaaS checkout form. The cart is read from localStorage by
	// catalog-cart-summary.js; nothing is fetched from the SaaS for this block.
	$checkout_order_summary = '';
	$checkout_form_fields   = '';
	if ( $is_catalog_checkout && $link_attr !== '' && is_array( $link_config ) ) {
		$co_grid = remove_query_arg( [ 'xpui_product', 'xpui_cart', 'xpui_checkout' ], get_permalink() ? get_permalink() : home_url( '/' ) );
		$co_presentation = is_array( $link_config['presentation'] ?? null ) ? $link_config['presentation'] : [];
		$co_front        = is_array( $co_presentation['frontCatalog'] ?? null ) ? $co_presentation['frontCatalog'] : [];
		if ( ! empty( $co_front['catalogId'] ) ) {
			$co_catalog = xpressui_get_hosted_link_catalog( $slug, $link_attr );
			$co_kind    = is_array( $co_catalog ) ? (string) ( $co_catalog['catalog_kind'] ?? '' ) : '';
			if ( 'time_slots' === $co_kind ) {
				// Time-slots checkout: the chosen slot is carried in the query params the
				// booking redirect appends. Render the collapsed Booking summary (inside the
				// form card, parity with the SaaS).
				$ts_checkout            = xpressui_render_time_slots_checkout();
				$checkout_order_summary = $ts_checkout['summary'];
				// Persist the booking in the WP submission: catalog-checkout.js intercepts the
				// runtime submit and merges the slot (read from the URL params) into the payload
				// — the runtime only serializes config fields, so DOM hidden inputs won't do.
				$co_checkout_path = XPRESSUI_BRIDGE_DIR . 'assets/catalog-checkout.js';
				$co_checkout_ver  = file_exists( $co_checkout_path ) ? (string) filemtime( $co_checkout_path ) : XPRESSUI_BRIDGE_VERSION;
				wp_enqueue_script( 'xpressui-catalog-checkout', XPRESSUI_BRIDGE_URL . 'assets/catalog-checkout.js', [], $co_checkout_ver, true );
				wp_localize_script(
					'xpressui-catalog-checkout',
					'xpressuiCatalogCheckout',
					[
						'slug'      => $slug,
						'returnUrl' => $co_grid,
					]
				);
			}
			// Product catalogs use a localStorage cart + order summary + payment selector.
			if ( is_array( $co_catalog ) && 'time_slots' !== $co_kind ) {
				$checkout_order_summary = xpressui_render_checkout_order_summary( $co_catalog, $link_attr, $co_grid );
				// Payment-method block intentionally omitted: the WP checkout no longer renders
				// the manual/Stripe "Payment method" selector. The submit handler tolerates an
				// absent xpui_payment_method field (rest-endpoint.php sanitizes it to a string).

				// Save the catalog order in WordPress by default: inject the localStorage
				// cart + chosen payment method into the form as hidden fields so the normal
				// submit to xpressui/v1/submit stores the order in the WP submission inbox.
				// (A future opt-in paid "bypass" can POST to the SaaS orders endpoint
				// instead — the REST proxy is already in place.)
				$co_checkout_path = XPRESSUI_BRIDGE_DIR . 'assets/catalog-checkout.js';
				$co_checkout_ver  = file_exists( $co_checkout_path ) ? (string) filemtime( $co_checkout_path ) : XPRESSUI_BRIDGE_VERSION;
				wp_enqueue_script( 'xpressui-catalog-checkout', XPRESSUI_BRIDGE_URL . 'assets/catalog-checkout.js', [], $co_checkout_ver, true );
				wp_localize_script(
					'xpressui-catalog-checkout',
					'xpressuiCatalogCheckout',
					[
						'slug'       => $slug,
						'storageKey' => xpressui_catalog_cart_storage_key( $link_attr, (string) ( $co_catalog['catalog_id'] ?? '' ) ),
						'returnUrl'  => $co_grid,
					]
				);
			}
		}
	}

	// Inject the catalog checkout order summary + payment selector INSIDE the form card,
	// right before <form> — parity with the SaaS hosted checkout (the collapsed order
	// summary sits inside the form card, above the fields).
	if ( '' !== $checkout_order_summary ) {
		$checkout_prelude = '<div class="xpui-checkout-prelude">' . $checkout_order_summary . '</div>';
		$co_form_pos      = strpos( $fragment_html, '<form' );
		if ( false !== $co_form_pos ) {
			$fragment_html = substr( $fragment_html, 0, $co_form_pos ) . $checkout_prelude . substr( $fragment_html, $co_form_pos );
		} else {
			$fragment_html = $checkout_prelude . $fragment_html;
		}
	}

	// Time-slots checkout captures the booking server-side: inject the hidden fields
	// just inside the <form> (after the opening tag) so the normal submit stores them.
	if ( '' !== $checkout_form_fields ) {
		$co_form_open = strpos( $fragment_html, '<form' );
		if ( false !== $co_form_open ) {
			$co_form_gt = strpos( $fragment_html, '>', $co_form_open );
			if ( false !== $co_form_gt ) {
				$fragment_html = substr( $fragment_html, 0, $co_form_gt + 1 ) . $checkout_form_fields . substr( $fragment_html, $co_form_gt + 1 );
			}
		}
	}

	$hide_branding = get_option( 'xpressui_hide_branding', '0' ) === '1';
	// Only connected Pro sites can actually activate hide_branding.
	// Safety fallback check: if hide_branding is set but license is NOT active, force show.
	if ( $hide_branding && ! xpressui_pro_is_license_active() ) {
		$hide_branding = false;
	}

	$branding_footer = '';
	if ( ! $hide_branding ) {
		$branding_footer = '<div class="xpressui-form-branding-footer" style="text-align: center; margin-top: 15px; font-size: 11px; color: #94a3b8; font-family: -apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif; width: 100%;">'
			. '<a href="https://intakeflow.dev" target="_blank" rel="noopener noreferrer" style="color: #94a3b8; text-decoration: none; font-weight: 500;">'
			. sprintf( esc_html__( 'Powered by %s', 'xpressui-bridge' ), 'IntakeFlow' )
			. '</a>'
			. '</div>';
	}

	$html_out = '<div class="xpressui-embed-wrapper xpressui-inline-embed">'
		. $intro_outside_html
		. $fragment_html
		. $config_tag
		. $branding_footer
		. '</div>';

	if ( $is_preview ) {
		$preview_style = 'border: 3px dashed #ffb900; padding: 15px; margin: 15px 0; background: #fffdf5; position: relative; border-radius: 4px;';
		$preview_badge = '<div style="position: absolute; top: -12px; left: 15px; background: #ffb900; color: #000; font-size: 11px; font-weight: bold; padding: 2px 8px; border-radius: 3px; font-family: -apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Oxygen-Sans,Ubuntu,Cantarell,\'Helvetica Neue\',sans-serif; text-transform: uppercase;">' . esc_html__( 'Sandbox Preview Mode', 'xpressui-bridge' ) . '</div>';
		$html_out = '<div class="xpressui-sandbox-preview-container" style="' . esc_attr( $preview_style ) . '">' . $preview_badge . $html_out . '</div>';
	}

	return wp_kses( $html_out, xpressui_get_shell_allowed_html() );
}
