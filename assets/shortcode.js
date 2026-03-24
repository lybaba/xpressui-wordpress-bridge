/**
 * XPressUI iframe auto-resize.
 * Loaded only on pages that contain an [xpressui] shortcode.
 */
( function () {
	'use strict';

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

		resize();

		try {
			var doc = frame.contentDocument || ( frame.contentWindow && frame.contentWindow.document );
			if ( doc && doc.body && window.ResizeObserver ) {
				new ResizeObserver( resize ).observe( doc.body );
				return;
			}
		} catch ( _e ) {}

		// Fallback: poll every 150 ms for 10 s, then every 500 ms.
		var polls = 0;
		var interval = setInterval( function () {
			resize();
			polls++;
			if ( polls === 67 ) {
				clearInterval( interval );
				setInterval( resize, 500 );
			}
		}, 150 );
	}

	function initFrames() {
		var frames = document.querySelectorAll( 'iframe[data-xpressui-autoresize]' );
		for ( var i = 0; i < frames.length; i++ ) {
			( function ( frame ) {
				var doc = frame.contentDocument || ( frame.contentWindow && frame.contentWindow.document );
				if ( frame.complete || ( doc && doc.readyState === 'complete' ) ) {
					watchFrame( frame );
				} else {
					frame.addEventListener( 'load', function () { watchFrame( frame ); } );
				}
			}( frames[ i ] ) );
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
