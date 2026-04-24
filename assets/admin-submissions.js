/**
 * XPressUI Bridge — submission detail admin scripts.
 */
/* global wp */

// Move "Review Notes" above "Publish" in the sidebar (immediate — no timing dependency).
(function () {
	var reviewBox  = document.getElementById( 'xpressui_submission_review' );
	var publishBox = document.getElementById( 'submitdiv' );
	if ( reviewBox && publishBox && publishBox.parentNode ) {
		publishBox.parentNode.insertBefore( reviewBox, publishBox );
	}
}());

// "Request Additional Document" dynamic toggle.
// PHP (postbox_classes filter) sets initial state based on saved status.
// JS syncs on page load (to clear any stale WP 'hidden' class) and on every dropdown change.
(function () {
	var statusEl = document.getElementById( 'xpressui_submission_status' );
	if ( ! statusEl ) { return; }

	function syncAfileMetabox() {
		var status  = statusEl.value;
		var showBox = status === 'pending_info' || status === 'done';
		var postbox = document.getElementById( 'xpressui_submission_afile_mb' );
		var body    = document.getElementById( 'xpressui-afile-body' );

		if ( ! postbox ) { return; }

		if ( showBox ) {
			// Remove our class AND WP's own 'hidden' class (Screen Options / user meta).
			postbox.classList.remove( 'xpressui-afile-hidden', 'hidden' );
			postbox.style.display = '';
		} else {
			postbox.classList.add( 'xpressui-afile-hidden' );
		}

		if ( showBox && body ) {
			body.style.display = 'block';
			var pending = body.querySelector( '[data-afile-section="pending_info"]' );
			var done    = body.querySelector( '[data-afile-section="done_like"]' );
			if ( pending ) { pending.style.display = status === 'pending_info' ? 'block' : 'none'; }
			if ( done )    { done.style.display    = status === 'done'         ? 'block' : 'none'; }
		}
	}

	// Update the review note hint when status changes.
	var reviewHintEl = document.getElementById( 'xpressui-review-note-hint' );
	if ( reviewHintEl ) {
		var hintMap = {};
		try { hintMap = JSON.parse( reviewHintEl.dataset.hints || '{}' ); } catch (e) {}
		var emailStatuses = (reviewHintEl.dataset.emailStatuses || '').split(',');
		function syncReviewHint() {
			var status = statusEl.value;
			var isEmail = emailStatuses.indexOf(status) !== -1;
			reviewHintEl.textContent = hintMap[ status ] || hintMap['_default'] || '';
			reviewHintEl.classList.toggle( 'xpressui-hint--email', isEmail );
			reviewHintEl.classList.toggle( 'xpressui-hint--internal', !isEmail );
		}
		statusEl.addEventListener( 'change', syncReviewHint );
	}

	statusEl.addEventListener( 'change', syncAfileMetabox );
	syncAfileMetabox(); // Sync on page load — removes stale WP 'hidden' class if needed.
}());

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
			setRefFile(fieldName, attachment.id, attachment.title || attachment.filename || '');
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
		if (fieldName.indexOf('__afile_pending__') === 0) {
			var pendingSlotId = fieldName.replace('__afile_pending__', '');
			return document.querySelector('input[name="xpressui_afile_ref_file_id_' + pendingSlotId + '"]');
		}
		if (fieldName.indexOf('__afile_done__') === 0) {
			var doneSlotId = fieldName.replace('__afile_done__', '');
			return document.querySelector('input[name="xpressui_done_info_file_id_' + doneSlotId + '"]');
		}
		return document.querySelector('input[name="xpressui_ref_files[' + fieldName + ']"]');
	}

	function truncateLabel(str) {
		if (!str) { return ''; }
		return str.length > 48 ? str.slice(0, 47) + '…' : str;
	}

	function setRefFile(fieldName, attachmentId, fileName) {
		var hidden = getHiddenInput(fieldName);
		var preview = document.querySelector('.xpressui-ref-file-preview[data-field="' + fieldName + '"]');
		if (hidden) { hidden.value = attachmentId; }
		if (preview) {
			var displayName = truncateLabel(fileName);
			var link = preview.querySelector('a');
			if (link) {
				link.textContent = displayName;
			} else {
				var a = document.createElement('a');
				a.target = '_blank';
				a.rel = 'noreferrer';
				a.textContent = displayName;
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
