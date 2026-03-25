( function () {
	'use strict';

	function parsePayload() {
		var payloadNode = document.getElementById( 'xpressui-shell-payload' );
		if ( ! payloadNode || ! payloadNode.textContent ) {
			return null;
		}

		try {
			return JSON.parse( payloadNode.textContent );
		} catch ( error ) {
			console.error( 'XPressUI shell payload parse error:', error );
			return null;
		}
	}

	function getTranslation( payload, key, fallback ) {
		if ( payload && payload.translations && typeof payload.translations[ key ] === 'string' && payload.translations[ key ].trim() !== '' ) {
			return payload.translations[ key ];
		}
		if ( window.XPRESSUI_I18N && typeof window.XPRESSUI_I18N[ key ] === 'string' && window.XPRESSUI_I18N[ key ].trim() !== '' ) {
			return window.XPRESSUI_I18N[ key ];
		}
		return fallback;
	}

	function loadScript( src ) {
		return new Promise( function ( resolve, reject ) {
			if ( ! src ) {
				resolve();
				return;
			}

			var script = document.createElement( 'script' );
			script.src = src;
			script.async = false;
			script.onload = resolve;
			script.onerror = function () {
				reject( new Error( 'Failed to load script: ' + src ) );
			};
			document.body.appendChild( script );
		} );
	}

	function loadScriptWithFallback( primaryUrl, fallbackUrl ) {
		var urls = [ primaryUrl, fallbackUrl ].filter( function ( entry, index, array ) {
			return !!entry && array.indexOf( entry ) === index;
		} );

		if ( ! urls.length ) {
			return Promise.resolve();
		}

		return urls.reduce( function ( chain, url ) {
			return chain.catch( function () {
				return loadScript( url );
			} );
		}, Promise.reject() ).catch( function () {
			throw new Error( 'Failed to load workflow bootstrap script.' );
		} );
	}

	function postHeight() {
		var body = document.body;
		var docEl = document.documentElement;
		var height = Math.max(
			body ? body.scrollHeight : 0,
			body ? body.offsetHeight : 0,
			docEl ? docEl.scrollHeight : 0,
			docEl ? docEl.offsetHeight : 0
		);
		if ( window.parent && height > 0 ) {
			window.parent.postMessage( { type: 'xpressui:resize', height: height }, '*' );
		}
	}

	function watchHeight() {
		postHeight();
		if ( window.ResizeObserver ) {
			var observer = new ResizeObserver( function () {
				postHeight();
			} );
			if ( document.body ) {
				observer.observe( document.body );
			}
		}
		window.addEventListener( 'load', postHeight );
		window.addEventListener( 'resize', postHeight );
		setTimeout( postHeight, 100 );
		setTimeout( postHeight, 500 );
		setTimeout( postHeight, 1500 );
	}

	function applyBaseHref( href ) {
		if ( ! href ) {
			return;
		}
		var base = document.createElement( 'base' );
		base.href = href;
		document.head.appendChild( base );
	}

	function appendTemplateStyles( templateDocument ) {
		var styles = templateDocument.querySelectorAll( 'style, link[rel="stylesheet"]' );
		styles.forEach( function ( node ) {
			document.head.appendChild( node.cloneNode( true ) );
		} );
	}

	function buildMinimalPluginShellTemplate() {
		return [
			'<!doctype html>',
			'<html lang="en">',
			'<head>',
			'  <meta charset="utf-8" />',
			'  <meta name="viewport" content="width=device-width, initial-scale=1" />',
			'  <meta name="robots" content="noindex, nofollow" />',
			'  <style>',
			'    html, body { margin: 0; padding: 0; background: transparent; }',
			'    * { box-sizing: border-box; }',
			'    .page-shell { min-height: 0; padding: 2px; background: transparent; }',
			'    .form-frame { width: 100%; max-width: 100%; margin: 0 auto; background: transparent; box-shadow: none; border: none; }',
			'  </style>',
			'</head>',
			'<body>',
			'  <div id="xpressui-root" class="page-shell" data-template-zone="page_shell">',
			'    <main class="form-frame" data-template-zone="form_frame"></main>',
			'  </div>',
			'</body>',
			'</html>',
		].join( '\n' );
	}

	function appendTemplateMarkup( templateDocument ) {
		var shellRoot = document.getElementById( 'xpressui-shell-root' );
		if ( ! shellRoot ) {
			throw new Error( 'Missing shell root.' );
		}

		shellRoot.innerHTML = '';
		Array.prototype.slice.call( templateDocument.body.children ).forEach( function ( child ) {
			if ( child.tagName === 'SCRIPT' ) {
				return;
			}
			shellRoot.appendChild( child.cloneNode( true ) );
		} );
	}

	async function fetchTextWithFallback( primaryUrl, fallbackUrl, errorMessage ) {
		var urls = [ primaryUrl, fallbackUrl ].filter( function ( entry, index, array ) {
			return !!entry && array.indexOf( entry ) === index;
		} );

		for ( var i = 0; i < urls.length; i++ ) {
			var response = await fetch( urls[ i ], { credentials: 'same-origin' } );
			if ( response.ok ) {
				return response.text();
			}
		}

		return '';
	}

	function injectConfigNode( config ) {
		var existing = document.getElementById( 'xpressui-custom-config' );
		if ( existing ) {
			existing.remove();
		}
		var node = document.createElement( 'script' );
		node.type = 'application/json';
		node.id = 'xpressui-custom-config';
		node.textContent = JSON.stringify( config );
		document.body.appendChild( node );
	}

	async function bootstrap() {
		var payload = parsePayload();
		if ( ! payload ) {
			return;
		}

		window.XPRESSUI_WORDPRESS_REST_URL = payload.restUrl || '';
		applyBaseHref( payload.packageBaseUrl || '' );

		var templateHtml = await fetchTextWithFallback(
			payload.preferredTemplateUrl,
			payload.templateUrl,
			'Failed to load workflow template.'
		);
		if ( ! templateHtml ) {
			templateHtml = buildMinimalPluginShellTemplate();
		}
		var parser = new DOMParser();
		var templateDocument = parser.parseFromString( templateHtml, 'text/html' );

		appendTemplateStyles( templateDocument );
		appendTemplateMarkup( templateDocument );

		var configResponse = await fetch( payload.configUrl, { credentials: 'same-origin' } );
		if ( ! configResponse.ok ) {
			throw new Error( 'Failed to load workflow config.' );
		}
		var config = await configResponse.json();
		injectConfigNode( config );

		await loadScript( payload.runtimeUrl );
		await loadScriptWithFallback( payload.preferredInitUrl, payload.initUrl );
		watchHeight();
		postHeight();
	}

	bootstrap().catch( function ( error ) {
		var payload = parsePayload();
		console.error( 'XPressUI shell bootstrap error:', error );
		document.body.innerHTML = '<p style="font-family:system-ui,sans-serif;padding:16px;color:#991b1b;">' + getTranslation( payload, 'unableLoadWorkflow', 'Unable to load this XPressUI workflow.' ) + '</p>';
	} );
}() );
