<?php
/**
 * [xpressui] shortcode — embeds an installed XPressUI workflow package.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the [xpressui id="project-slug"] shortcode.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string HTML output.
 */
function xpressui_render_shortcode( $atts ) {
	$atts = shortcode_atts(
		[
			'id'     => '',
			'height' => '',
			'width'  => '100%',
			'title'  => __( 'XPressUI Form', 'xpressui-bridge' ),
		],
		$atts,
		'xpressui'
	);

	$slug = sanitize_title( (string) $atts['id'] );

	if ( $slug === '' ) {
		return '<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: the "id" attribute is required.', 'xpressui-bridge' )
			. '</p>';
	}

	$upload_dir = wp_get_upload_dir();
	if ( ! empty( $upload_dir['error'] ) ) {
		return '<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: could not resolve the uploads directory.', 'xpressui-bridge' )
			. '</p>';
	}

	$package_dir = trailingslashit( $upload_dir['basedir'] ) . 'xpressui/' . $slug . '/';
	$package_url = trailingslashit( $upload_dir['baseurl'] ) . 'xpressui/' . $slug . '/';

	if ( ! is_dir( $package_dir ) || ! file_exists( $package_dir . 'index.html' ) ) {
		return '<p class="xpressui-embed-error">'
			. esc_html(
				sprintf(
					/* translators: %s: project slug */
					__( '[xpressui] error: no package found for id "%s". Please install the workflow package first.', 'xpressui-bridge' ),
					$slug
				)
			)
			. '</p>';
	}

	$iframe_id  = 'xpressui-frame-' . esc_attr( $slug );
	$src        = esc_url( $package_url . 'index.html' );
	$title      = esc_attr( (string) $atts['title'] );
	$width      = esc_attr( (string) ( $atts['width'] ?: '100%' ) );

	// Fixed-height mode (e.g. height="600") disables the auto-resize script.
	$fixed_height = (string) $atts['height'];
	if ( $fixed_height !== '' && is_numeric( $fixed_height ) ) {
		$height_attr = ' height="' . esc_attr( $fixed_height ) . '"';
		$resize_attr = '';
	} else {
		$height_attr = ' height="600"';
		$resize_attr = ' data-xpressui-autoresize="1"';
	}

	$html  = '<div class="xpressui-embed-wrapper">';
	$html .= '<iframe'
		. ' id="' . $iframe_id . '"'
		. ' src="' . $src . '"'
		. ' title="' . $title . '"'
		. ' style="width:' . $width . ';border:none;display:block;"'
		. $height_attr
		. $resize_attr
		. ' loading="lazy"'
		. ' allow="camera;microphone;geolocation"'
		. '></iframe>';
	$html .= '</div>';
	$html .= xpressui_shortcode_resize_script();

	return $html;
}

/**
 * Returns the inline JS for auto-resizing iframes.
 * Deduplicated — only injected once per page load.
 *
 * @return string
 */
function xpressui_shortcode_resize_script() {
	static $injected = false;

	if ( $injected ) {
		return '';
	}
	$injected = true;

	ob_start();
	?>
	<script>
	(function () {
		'use strict';

		/**
		 * Resize an iframe to match its content height.
		 * Works same-origin (direct DOM access) with a ResizeObserver fallback,
		 * and also handles cross-origin runtimes that post { type: 'xpressui:resize', height }.
		 */

		function getContentHeight( frame ) {
			try {
				var doc = frame.contentDocument || ( frame.contentWindow && frame.contentWindow.document );
				if ( ! doc || ! doc.body ) {
					return 0;
				}
				return Math.max(
					doc.body.scrollHeight,
					doc.body.offsetHeight,
					doc.documentElement ? doc.documentElement.scrollHeight : 0
				);
			} catch ( _e ) {
				return 0;
			}
		}

		function applyHeight( frame, height ) {
			if ( height > 0 ) {
				frame.style.height = height + 'px';
			}
		}

		function watchFrame( frame ) {
			function resize() {
				applyHeight( frame, getContentHeight( frame ) );
			}

			// Initial resize after content is painted.
			resize();

			// Observe content size changes with ResizeObserver (same-origin).
			try {
				var doc = frame.contentDocument || ( frame.contentWindow && frame.contentWindow.document );
				if ( doc && doc.body && window.ResizeObserver ) {
					new ResizeObserver( resize ).observe( doc.body );
					return; // ResizeObserver handles it — no polling needed.
				}
			} catch ( _e ) {}

			// Fallback: poll every 150 ms for the first 10 s, then every 500 ms.
			var polls = 0;
			var interval = setInterval( function () {
				resize();
				polls++;
				if ( polls === 67 ) { // ~10 s at 150 ms
					clearInterval( interval );
					setInterval( resize, 500 );
				}
			}, 150 );
		}

		function initFrames() {
			var frames = document.querySelectorAll( 'iframe[data-xpressui-autoresize]' );
			for ( var i = 0; i < frames.length; i++ ) {
				(function ( frame ) {
					if ( frame.complete || ( frame.contentDocument && frame.contentDocument.readyState === 'complete' ) ) {
						watchFrame( frame );
					} else {
						frame.addEventListener( 'load', function () { watchFrame( frame ); } );
					}
				}( frames[i] ) );
			}
		}

		// Cross-origin fallback: runtime posts { type: 'xpressui:resize', height }.
		window.addEventListener( 'message', function ( event ) {
			var data = event.data;
			if ( ! data || data.type !== 'xpressui:resize' ) {
				return;
			}
			var height = parseInt( data.height, 10 );
			if ( isNaN( height ) || height <= 0 ) {
				return;
			}
			var frames = document.querySelectorAll( 'iframe[data-xpressui-autoresize]' );
			for ( var i = 0; i < frames.length; i++ ) {
				try {
					if ( frames[i].contentWindow === event.source ) {
						applyHeight( frames[i], height );
					}
				} catch ( _e ) {}
			}
		}, false );

		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', initFrames );
		} else {
			initFrames();
		}
	}());
	</script>
	<?php
	return ob_get_clean();
}
