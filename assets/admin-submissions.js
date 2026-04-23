/**
 * XPressUI Bridge — submission detail admin scripts.
 *
 * Keeps the "Needs modification" helper label in sync with the
 * correction toggles without requiring a page reload.
 */
(function () {
	document.querySelectorAll('.xpressui-inline-flagged-switch').forEach(function (toggleWrap) {
		var checkbox = toggleWrap.querySelector('input[type="checkbox"]');
		var label = toggleWrap.querySelector('.xpressui-inline-flagged-switch__text');
		if (!checkbox || !label) {
			return;
		}

		function syncFlaggedLabel() {
			label.style.display = checkbox.checked ? '' : 'none';
		}

		checkbox.addEventListener('change', syncFlaggedLabel);
		syncFlaggedLabel();
	});
}());
