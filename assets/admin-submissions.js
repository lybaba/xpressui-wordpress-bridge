/**
 * XPressUI Bridge — submission detail admin scripts.
 */
/* global wp */
(function () {

	// --- "Needs modification" label sync ----------------------------------------
	document.querySelectorAll('.xpressui-inline-flagged-switch').forEach(function (toggleWrap) {
		var checkbox = toggleWrap.querySelector('input[type="checkbox"]');
		var label = toggleWrap.querySelector('.xpressui-inline-flagged-switch__text');
		if (!checkbox || !label) { return; }

		function syncFlaggedLabel() {
			label.style.display = checkbox.checked ? '' : 'none';
		}

		checkbox.addEventListener('change', syncFlaggedLabel);
		syncFlaggedLabel();
	});

	// --- Reference file media picker --------------------------------------------
	var mediaFrame = null;
	var activeField = null;

	function openMediaPicker(fieldName) {
		activeField = fieldName;
		if (mediaFrame) {
			mediaFrame.off('select');
		}
		mediaFrame = wp.media({
			title: 'Select reference file',
			button: { text: 'Attach file' },
			multiple: false,
		});
		mediaFrame.on('select', function () {
			var attachment = mediaFrame.state().get('selection').first().toJSON();
			setRefFile(fieldName, attachment.id, attachment.filename || attachment.title || '');
		});
		mediaFrame.open();
	}

	function getHiddenInput(fieldName) {
		if (fieldName === '__afile_pending__') {
			return document.querySelector('input[name="xpressui_afile_ref_file_id"]');
		}
		if (fieldName === '__afile_done__') {
			return document.querySelector('input[name="xpressui_done_info_file_id"]');
		}
		return document.querySelector('input[name="xpressui_ref_files[' + fieldName + ']"]');
	}

	function setRefFile(fieldName, attachmentId, fileName) {
		var hidden = getHiddenInput(fieldName);
		var preview = document.querySelector('.xpressui-ref-file-preview[data-field="' + fieldName + '"]');
		if (hidden) { hidden.value = attachmentId; }
		if (preview) {
			var link = preview.querySelector('a');
			if (link) {
				link.textContent = fileName;
			} else {
				var a = document.createElement('a');
				a.target = '_blank';
				a.rel = 'noreferrer';
				a.textContent = fileName;
				preview.insertBefore(a, preview.firstChild);
			}
			preview.style.display = '';
		}
	}

	function clearRefFile(fieldName) {
		var hidden = getHiddenInput(fieldName);
		var preview = document.querySelector('.xpressui-ref-file-preview[data-field="' + fieldName + '"]');
		if (hidden) { hidden.value = ''; }
		if (preview) { preview.style.display = 'none'; }
	}

	document.addEventListener('click', function (e) {
		// Attach button
		var btn = e.target.closest('.xpressui-ref-file-btn');
		if (btn) {
			e.preventDefault();
			openMediaPicker(btn.dataset.field);
			return;
		}
		// Remove button
		var rem = e.target.closest('.xpressui-ref-file-remove');
		if (rem) {
			e.preventDefault();
			clearRefFile(rem.dataset.field);
		}
	});

}());
