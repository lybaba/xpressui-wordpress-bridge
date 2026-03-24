/**
 * XPressUI iframe auto-resize.
 * Supports: Chrome, Firefox, Edge, Safari, iOS Safari, Android Chrome.
 *
 * iOS Safari bug: scrollHeight returns the container height, not the content height.
 * Fix: temporarily set iframe height to 0 before measuring, forcing a reflow.
 */
( function () {
	'use strict';

	function getContentHeight( frame ) {
		try {
			var doc = frame.contentDocument || ( frame.contentWindow && frame.contentWindow.document );
			if ( ! doc || ! doc.body ) {
				return 0;
			}

			// iOS Safari fix: reset to 0 to force correct scrollHeight recalculation.
			frame.style.height = '0px';

			var h = Math.max(
				doc.body.scrollHeight       || 0,
				doc.body.offsetHeight       || 0,
				doc.documentElement ? ( doc.documentElement.scrollHeight || 0 ) : 0,
				doc.documentElement ? ( doc.documentElement.offsetHeight || 0 ) : 0
			);

			return h;
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

		resize();

		// ResizeObserver (same-origin, modern browsers including Android Chrome).
		try {
			var doc = frame.contentDocument || ( frame.contentWindow && frame.contentWindow.document );
			if ( doc && doc.body && window.ResizeObserver ) {
				new ResizeObserver( resize ).observe( doc.body );
				// Safety net: re-measure after React finishes first paint (all platforms).
				setTimeout( resize, 100 );
				setTimeout( resize, 500 );
				setTimeout( resize, 1500 );
				return;
			}
		} catch ( _e ) {}

		// Fallback polling (iOS Safari < 13.4, older Android).
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

	function tryWatchFrame( frame ) {
		try {
			var doc = frame.contentDocument || ( frame.contentWindow && frame.contentWindow.document );
			if ( doc && doc.readyState === 'complete' ) {
				watchFrame( frame );
				return;
			}
		} catch ( _e ) {}

		var loaded = false;

		frame.addEventListener( 'load', function () {
			loaded = true;
			watchFrame( frame );
		} );

		// Chrome/Edge/Android: poll readyState as safety net alongside load event.
		var check = setInterval( function () {
			try {
				var d = frame.contentDocument || ( frame.contentWindow && frame.contentWindow.document );
				if ( d && d.readyState === 'complete' ) {
					clearInterval( check );
					if ( ! loaded ) {
						loaded = true;
						watchFrame( frame );
					}
				}
			} catch ( _e ) {
				clearInterval( check );
			}
		}, 100 );

		setTimeout( function () { clearInterval( check ); }, 15000 );
	}

	function initFrames() {
		var frames = document.querySelectorAll( 'iframe[data-xpressui-autoresize]' );
		for ( var i = 0; i < frames.length; i++ ) {
			tryWatchFrame( frames[ i ] );
		}
	}

	// Cross-origin / runtime postMessage fallback.
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
				if ( frames[ i ].contentWindow === event.source ) {
					applyHeight( frames[ i ], height );
				}
			} catch ( _e ) {}
		}
	}, false );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initFrames );
	} else {
		initFrames();
	}
}() );
