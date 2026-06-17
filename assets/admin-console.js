/**
 * XPressUI Console admin page script.
 */
/* global xpressuiBridgeConsole, ajaxurl */
(function ($) {
	'use strict';

	$(function () {
		var cfg = window.xpressuiBridgeConsole;
		if (!cfg) {
			return;
		}

		var iframe = document.getElementById('xpressui-console-iframe');
		var loader = document.getElementById('xpressui-console-loader');
		var apiToken = cfg.apiToken || '';
		var consoleUrl = cfg.apiUrl || '';

		if (!iframe) {
			return;
		}

		iframe.onload = function () {
			if (loader) {
				loader.style.display = 'none';
			}
			iframe.style.display = 'block';

			if (apiToken) {
				iframe.contentWindow.postMessage({
					type: 'xpressui_auth_token',
					token: apiToken
				}, '*');
			}
		};

		window.addEventListener('message', function (event) {
			if (!consoleUrl) {
				return;
			}
			try {
				if (event.origin !== new URL(consoleUrl).origin) {
					return;
				}
			} catch (e) {
				return;
			}

			if (event.data && event.data.type === 'xpressui_sync_required') {
				var projectSlug = event.data.projectSlug || '';
				if (projectSlug) {
					var syncData = new URLSearchParams();
					syncData.set('action', 'xpressui_console_sync_project');
					syncData.set('project_id', projectSlug);
					syncData.set('nonce', cfg.nonce);

					var notice = $('<div class="notice notice-info is-dismissible" style="position:fixed; bottom:20px; right:20px; z-index:9999; margin:0; box-shadow:0 2px 10px rgba(0,0,0,0.1);"><p>' + 
						'<strong>IntakeFlow:</strong> ' + (cfg.i18n.syncing || 'Synchronizing workflow updates...') + 
						'</p></div>').appendTo('body');

					fetch(ajaxurl, {
						method: 'POST',
						body: syncData,
						credentials: 'same-origin'
					})
					.then(function (r) { return r.json(); })
					.then(function (res) {
						notice.fadeOut(function () { $(this).remove(); });
						if (res.success) {
							$('<div class="notice notice-success is-dismissible" style="position:fixed; bottom:20px; right:20px; z-index:9999; margin:0; box-shadow:0 2px 10px rgba(0,0,0,0.1);"><p>' + 
								'<strong>IntakeFlow:</strong> ' + (cfg.i18n.synced || 'Workflow synchronized successfully!') + 
								'</p></div>').appendTo('body').delay(3000).fadeOut(function () { $(this).remove(); });
						} else {
							$('<div class="notice notice-error is-dismissible" style="position:fixed; bottom:20px; right:20px; z-index:9999; margin:0; box-shadow:0 2px 10px rgba(0,0,0,0.1);"><p>' + 
								'<strong>IntakeFlow:</strong> ' + (res.data ? res.data.message : (cfg.i18n.failed || 'Sync failed.')) + 
								'</p></div>').appendTo('body').delay(5000).fadeOut(function () { $(this).remove(); });
						}
					})
					.catch(function () {
						notice.fadeOut(function () { $(this).remove(); });
					});
				}
			}
		});
	});
})(jQuery);
