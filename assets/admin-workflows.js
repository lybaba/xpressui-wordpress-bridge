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
			var email   = document.getElementById('xpressui_notify_email');
			var url     = document.getElementById('xpressui_redirect_url');
			var webhook = document.getElementById('xpressui_webhook_url');
			var title   = document.getElementById('xpressui_show_project_title');
			var req     = document.getElementById('xpressui_show_required_fields_note');
			var vis     = document.getElementById('xpressui_section_label_visibility');
			if (email)   { email.value   = s.notifyEmail  || ''; }
			if (url)     { url.value     = s.redirectUrl   || ''; }
			if (webhook) { webhook.value = s.webhookUrl    || ''; }
			if (title)   { title.checked = s.showProjectTitle === '1'; }
			if (req)     { req.checked   = s.showRequiredFieldsNote === '1'; }
			if (vis)     { vis.value     = s.sectionLabelVisibility || 'auto'; }
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
}(window.xpressuiBridgeAdmin));
