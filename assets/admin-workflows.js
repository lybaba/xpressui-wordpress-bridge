/**
 * XPressUI Bridge — Workflows admin page scripts.
 *
 * Dynamic data injected via wp_localize_script / wp_add_inline_script
 * into window.xpressuiBridgeAdmin:
 *   - ajaxUrl     {string}
 *   - settingsMap {object}  per-slug project settings (added inline when available)
 *   - i18n        {object}  translated strings
 */
/* global xpressuiBridgeAdmin */
(function (cfg) {
	// --- Project settings switcher -------------------------------------------
	var settingsMap = (cfg && cfg.settingsMap) ? cfg.settingsMap : {};
	var sel = document.getElementById('xpressui_settings_slug');
	if (sel) {
		function applySettings(slug) {
			var s = settingsMap[slug] || {};
			var email    = document.getElementById('xpressui_notify_email');
			var url      = document.getElementById('xpressui_redirect_url');
			var webhook  = document.getElementById('xpressui_webhook_url');
			var title    = document.getElementById('xpressui_show_project_title');
			var req      = document.getElementById('xpressui_show_required_fields_note');
			var vis      = document.getElementById('xpressui_section_label_visibility');
			var notifSub = document.getElementById('xpressui_notify_submitter');
			if (email)    { email.value    = s.notifyEmail  || ''; }
			if (url)      { url.value      = s.redirectUrl   || ''; }
			if (webhook)  { webhook.value  = s.webhookUrl    || ''; }
			if (title)    { title.checked  = s.showProjectTitle === '1'; }
			if (req)      { req.checked    = s.showRequiredFieldsNote === '1'; }
			if (vis)      { vis.value      = s.sectionLabelVisibility || 'auto'; }
			if (notifSub) { notifSub.checked = s.notifySubmitter === '1'; }
		}
		sel.addEventListener('change', function () { applySettings(this.value); });
	}

	// --- Generic AJAX interceptor for data-ajax-action forms -----------------
	var ajaxUrl = cfg && cfg.ajaxUrl ? cfg.ajaxUrl : '';
	var i18n    = cfg && cfg.i18n ? cfg.i18n : {};
	document.querySelectorAll('form[data-ajax-action]').forEach(function (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var action    = form.dataset.ajaxAction;
			var statusEl  = form.querySelector('.xpressui-ajax-status');
			var submitBtn = form.querySelector('[type="submit"]');
			if (submitBtn) { submitBtn.disabled = true; }
			if (statusEl)  { statusEl.textContent = i18n.saving || ''; statusEl.style.color = ''; }
			var data = new FormData(form);
			data.set('action', action);
			fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (submitBtn) { submitBtn.disabled = false; }
					if (statusEl) {
						statusEl.textContent = (res.data && res.data.message)
							? res.data.message
							: (res.success ? (i18n.saved || '') : (i18n.error || ''));
						statusEl.style.color = res.success ? '#3a3' : '#c00';
						setTimeout(function () { if (statusEl) { statusEl.textContent = ''; } }, 4000);
					}
				})
				.catch(function () {
					if (submitBtn) { submitBtn.disabled = false; }
					if (statusEl) { statusEl.textContent = i18n.networkError || ''; statusEl.style.color = '#c00'; }
				});
		});
	});

	// --- Highlight newly synced workflow row after reload --------------------
	var syncedSlug = sessionStorage.getItem('xpressui_synced_slug');
	if (syncedSlug) {
		sessionStorage.removeItem('xpressui_synced_slug');
		document.querySelectorAll('.xpressui-table--workflows tbody tr').forEach(function (row) {
			var cell = row.querySelector('td:first-child strong');
			if (cell && cell.textContent.trim() === syncedSlug) {
				row.style.transition = 'background-color 2s ease';
				row.style.backgroundColor = '#d4f5d4';
				setTimeout(function () { row.style.backgroundColor = ''; }, 3000);
			}
		});
	}

	// --- Collapsible card sections -------------------------------------------
	document.querySelectorAll('.xpressui-admin-card').forEach(function (card) {
		var h2 = card.querySelector('h2');
		if (!h2) { return; }

		var toggle = document.createElement('button');
		toggle.type = 'button';
		toggle.style.cssText = 'float:right;background:none;border:none;cursor:pointer;font-size:1rem;padding:0 4px;line-height:1;color:#666;';
		toggle.setAttribute('aria-label', i18n.toggleSection || 'Toggle section');
		h2.style.cursor = 'pointer';

		var body = document.createElement('div');
		body.className = 'xpressui-card-body';
		while (h2.nextSibling) { body.appendChild(h2.nextSibling); }
		card.appendChild(body);
		h2.appendChild(toggle);

		var key = 'xpressui_section_' + h2.textContent.trim().replace(/\s+/g, '_').replace(/[^a-zA-Z0-9_]/g, '').toLowerCase();
		var collapsed = localStorage.getItem(key) === '1';

		function apply() {
			body.style.display = collapsed ? 'none' : '';
			toggle.textContent = collapsed ? '▶' : '▼';
		}
		apply();

		h2.addEventListener('click', function () {
			collapsed = !collapsed;
			localStorage.setItem(key, collapsed ? '1' : '0');
			apply();
		});
	});

	// --- "Create on Console" per-row action ----------------------------------
	if (cfg && cfg.nonce) {
		document.querySelectorAll('.xpressui-create-on-console-link').forEach(function (link) {
			link.addEventListener('click', function (e) {
				e.preventDefault();
				if (link.dataset.busy === '1') { return; }
				var slug = link.dataset.slug;
				if (!slug) { return; }

				link.dataset.busy = '1';
				var originalText = link.textContent;
				link.textContent = i18n.creating || 'Creating…';
				link.style.pointerEvents = 'none';
				link.style.opacity = '0.6';

				var data = new URLSearchParams();
				data.set('action', 'xpressui_console_create_local_workflow');
				data.set('nonce', cfg.nonce);
				data.set('slug', slug);

				fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (res) {
						if (res && res.success) {
							// Highlight the synced row after reload, then refresh state.
							var syncedSlug = (res.data && res.data.slug) ? res.data.slug : slug;
							try { sessionStorage.setItem('xpressui_synced_slug', syncedSlug); } catch (err) {}
							window.alert((res.data && res.data.message) ? res.data.message : (i18n.created || 'Workflow created on your IntakeFlow Console.'));
							window.location.reload();
							return;
						}
						link.dataset.busy = '';
						link.textContent = originalText;
						link.style.pointerEvents = '';
						link.style.opacity = '';
						window.alert((res && res.data && res.data.message) ? res.data.message : (i18n.error || 'Error.'));
					})
					.catch(function () {
						link.dataset.busy = '';
						link.textContent = originalText;
						link.style.pointerEvents = '';
						link.style.opacity = '';
						window.alert(i18n.networkError || 'Network error.');
					});
			});
		});
	}

	// --- Async freshness check ------------------------------------------------
	if (cfg && cfg.localWorkflows && cfg.nonce) {
		var localWorkflows = cfg.localWorkflows;
		var nonce = cfg.nonce;
		var data = new URLSearchParams();
		data.set('action', 'xpressui_console_list_projects');
		data.set('nonce', nonce);

		fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res.success && res.data && res.data.projects) {
					var projects = res.data.projects;
					projects.forEach(function (p) {
						var slug = p.slug;
						var local = localWorkflows[slug];
						if (local && local.generatedAt && p.updatedAt) {
							var localTime = new Date(local.generatedAt).getTime();
							var remoteTime = new Date(p.updatedAt).getTime();
							if (remoteTime > localTime + 2000) { // Add a small buffer of 2s for clock skew
								// Find the row and append an "Out of Sync" warning badge
								var row = document.querySelector('.xpressui-workflow-row[data-slug="' + slug + '"]');
								if (row) {
									var titleCell = row.querySelector('.column-title strong');
									if (titleCell && !titleCell.querySelector('.xpressui-badge--out-of-sync')) {
										var badge = document.createElement('span');
										badge.className = 'xpressui-badge xpressui-badge--out-of-sync';
										badge.style.cssText = 'margin-left: 8px; vertical-align: middle; background: #fff8e1; border: 1px solid #ffe082; color: #b78103; font-weight: 500; font-size: 11px; padding: 2px 6px; border-radius: 4px; display: inline-block;';
										badge.textContent = i18n.outOfSync || 'Out of Sync';
										titleCell.appendChild(badge);
									}
								}
							}
						}
					});
				}
			})
			.catch(function (err) {
				console.error('Failed to run async sync freshness check:', err);
			});
	}
}(window.xpressuiBridgeAdmin));
