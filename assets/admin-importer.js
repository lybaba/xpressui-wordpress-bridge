/*
 * Form Importer wizard (Tools → Form Importer tab) behaviour.
 *
 * Extracted from the inline <script> block previously emitted by
 * xpressui_render_form_importer_tab() (includes/form-importer.php). All values
 * that used to be PHP-interpolated (ajax URL, nonces, the SaaS-connected flag,
 * and every translated string) now arrive via the localised `xpressuiImporter`
 * global set up in xpressui_enqueue_admin_assets() with wp_localize_script().
 */
( function ( $ ) {
	'use strict';

	var XP = window.xpressuiImporter || {};
	var i18n = XP.i18n || {};
	var ajaxUrl = XP.ajaxUrl || window.ajaxurl || '';

	// Static decorative arrow appended to the primary footer button label.
	var arrowIcon = ' <span class="dashicons dashicons-arrow-right-alt2" style="font-size:16px; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center;"></span>';

	$( function () {
		// The wizard markup only exists on the import tab; bail otherwise.
		if ( ! $( '.xpui-wiz-container' ).length ) {
			return;
		}

		var currentStep = 1;
		var sourceFormVal = '';
		var isSaasConnected = !! XP.isSaasConnected;
		var importMode = isSaasConnected ? 'saas' : 'local';
		// Slug of the just-created local workflow, set on a fallback result so the
		// "Retry sync to Console" button can re-attempt the Console create for it.
		var retrySyncSlug = '';

		// Step Navigation Line & Badge updates
		function updateStepUi() {
			$( '.xpui-wiz-step-item' ).removeClass( 'active completed' );
			for ( var s = 1; s <= 4; s++ ) {
				var item = $( '.xpui-wiz-step-item[data-step="' + s + '"]' );
				if ( s < currentStep ) {
					item.addClass( 'completed' );
				} else if ( s === currentStep ) {
					item.addClass( 'active' );
				}
			}

			// Line progress. The connector spans 12.5%..87.5% of the row (75% wide),
			// so the fill width is measured against that span: reaching badge 2 is
			// 25%, badge 3 is 50%, badge 4 (the end) is the full 75%.
			var progressPercent = 0;
			if ( currentStep === 2 ) progressPercent = 25;
			if ( currentStep === 3 ) progressPercent = 50;
			if ( currentStep === 4 ) progressPercent = 75;
			$( '#xpui-line-progress' ).css( 'width', progressPercent + '%' );

			// button row toggles
			if ( currentStep === 1 ) {
				$( '#xpui-wiz-prev' ).hide();
				$( '#xpui-wiz-next' ).show().html( i18n.next + arrowIcon ).prop( 'disabled', ! sourceFormVal );
			} else if ( currentStep === 2 ) {
				$( '#xpui-wiz-prev' ).show();
				$( '#xpui-wiz-next' ).show().html( i18n.convertSync + arrowIcon ).prop( 'disabled', false );
			} else {
				// Step 3 (Processing) and Step 4 (Finish) hide the footer buttons row
				$( '#xpui-wiz-buttons-row' ).hide();
			}

			// show hide step bodies
			$( '.xpui-wiz-step-body' ).removeClass( 'active' );
			$( '#xpui-wiz-step-' + currentStep + '-body' ).addClass( 'active' );
		}

		// Step 1 input selection
		$( '#xpui-wizard-select' ).on( 'change', function () {
			sourceFormVal = $( this ).val();
			var title = $( this ).find( 'option:selected' ).data( 'title' ) || '';
			if ( title ) {
				$( '#xpui-wizard-name' ).val( title );
				var cleanSlug = title.toLowerCase().replace( /[^a-z0-9_-]+/g, '-' ).replace( /^-+|-+$/g, '' );
				$( '#xpui-wizard-slug' ).val( cleanSlug );
			}
			updateStepUi();
		} );

		// Destination is decided automatically (saas when connected, else local) — see
		// `importMode` above; the server re-derives it authoritatively, so no UI choice.

		// Footer buttons handlers
		$( '#xpui-wiz-next' ).on( 'click', function () {
			if ( currentStep === 1 ) {
				currentStep = 2;
				updateStepUi();
			} else if ( currentStep === 2 ) {
				currentStep = 3;
				updateStepUi();
				startConversion();
			}
		} );

		$( '#xpui-wiz-prev' ).on( 'click', function () {
			if ( currentStep === 2 ) {
				currentStep = 1;
				updateStepUi();
			} else if ( currentStep === 3 ) {
				currentStep = 2;
				updateStepUi();
			}
		} );

		// Copy shortcode to clipboard
		$( '#xpui-wiz-copy-btn' ).on( 'click', function () {
			var text = $( this ).attr( 'data-clipboard-text' );
			var $btn = $( this );
			navigator.clipboard.writeText( text ).then( function () {
				$btn.text( i18n.copied ).css( 'background', '#dcfce7' );
				setTimeout( function () {
					$btn.text( i18n.copy ).css( 'background', '#ffffff' );
				}, 1500 );
			} );
		} );

		// Retry sync to Console (fallback state only). Re-attempts the Console
		// "create" for the just-created local workflow, reusing the existing
		// xpressui_console_create_local_workflow AJAX (keyed by slug). On success,
		// flip the Success step to the SaaS-success presentation.
		$( '#xpui-wiz-retry-sync-btn' ).on( 'click', function () {
			if ( ! retrySyncSlug ) { return; }
			var $btn = $( this );
			$btn.prop( 'disabled', true );
			$( '#xpui-wiz-retry-status' ).css( 'color', '#64748b' ).text( i18n.syncingToConsole );

			$.ajax( {
				url: ajaxUrl,
				type: 'POST',
				data: {
					action: 'xpressui_console_create_local_workflow',
					nonce: XP.syncNonce,
					slug: retrySyncSlug
				},
				success: function ( response ) {
					if ( ! response || ! response.success ) {
						var msg = ( response && response.data && response.data.message ) ? response.data.message : i18n.syncFailed;
						$( '#xpui-wiz-retry-status' ).css( 'color', '#b91c1c' ).text( msg );
						$btn.prop( 'disabled', false );
						return;
					}
					// Console create + sync succeeded — flip to the SaaS-success state.
					var newSlug = response.data.slug || retrySyncSlug;
					var newShortcode = '[xpressui id="' + newSlug + '"]';
					$( '#xpui-wiz-shortcode' ).text( newShortcode );
					$( '#xpui-wiz-copy-btn' ).attr( 'data-clipboard-text', newShortcode );
					$( '#xpui-wiz-success-title' ).css( 'color', '#15803d' ).text( i18n.createdOnConsole );
					$( '#xpui-wiz-success-msg' ).text( i18n.syncedBack );
					$( '#xpui-wiz-fallback-note' ).hide();
					$( '#xpui-wiz-connect-nudge' ).hide();
					var editUrl = response.data.edit_url || '';
					if ( editUrl ) {
						$( '#xpui-wiz-edit-btn' ).attr( 'href', editUrl ).show();
					} else {
						$( '#xpui-wiz-edit-btn' ).hide();
					}
					$( '#xpui-wiz-success-actions' ).show();
				},
				error: function () {
					$( '#xpui-wiz-retry-status' ).css( 'color', '#b91c1c' ).text( i18n.networkError );
					$btn.prop( 'disabled', false );
				}
			} );
		} );

		// Start AJAX Conversion & Sync
		function startConversion() {
			var parts = sourceFormVal.split( ':' );
			var sourceType = parts[0];
			var sourceId = parts[1];

			// Reset progress screen
			$( '#xpui-progress-fill' ).css( {
				'width': '10%',
				'background': 'linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%)'
			} );

			$( '.xpui-wiz-progress-step' ).removeClass( 'done active error' );
			$( '.xpui-wiz-progress-step-text' ).css( 'color', '' );

			$( '#xpui-prog-step-1' ).addClass( 'active' ).find( '.xpui-wiz-progress-step-icon' ).text( '⏳' );
			$( '#xpui-prog-step-2' ).find( '.xpui-wiz-progress-step-icon' ).text( '⏳' );
			$( '#xpui-prog-step-3' ).find( '.xpui-wiz-progress-step-icon' ).text( '⏳' );

			if ( importMode === 'saas' ) {
				$( '#xpui-prog-text-2' ).text( i18n.uploadingProject );
				$( '#xpui-prog-text-3' ).text( i18n.downloadingFiles );
			} else {
				$( '#xpui-prog-text-2' ).text( i18n.generatingLocal );
				$( '#xpui-prog-text-3' ).text( i18n.importComplete );
			}

			setTimeout( function () {
				// Parse stage done
				$( '#xpui-progress-fill' ).css( 'width', '40%' );
				$( '#xpui-prog-step-1' ).removeClass( 'active' ).addClass( 'done' ).find( '.xpui-wiz-progress-step-icon' ).text( '✓' );
				$( '#xpui-prog-step-2' ).addClass( 'active' );

				// Fire AJAX request
				var data = {
					action: 'xpressui_import_form_wizard',
					nonce: XP.importNonce,
					source_type: sourceType,
					source_form_id: sourceId,
					import_mode: importMode,
					custom_name: $( '#xpui-wizard-name' ).val(),
					custom_slug: $( '#xpui-wizard-slug' ).val()
				};

				$.ajax( {
					url: ajaxUrl,
					type: 'POST',
					data: data,
					success: function ( response ) {
						if ( ! response.success ) {
							// Handle error in Step 3
							$( '#xpui-progress-fill' ).css( 'background', '#ef4444' );
							$( '#xpui-prog-step-2' ).removeClass( 'active' ).addClass( 'error' );
							$( '#xpui-prog-step-2' ).find( '.xpui-wiz-progress-step-icon' ).text( '❌' );
							$( '#xpui-prog-text-2' ).css( 'color', '#ef4444' ).text( response.data.message );
							// Provide back button to try again
							$( '#xpui-wiz-buttons-row' ).show();
							$( '#xpui-wiz-prev' ).show();
							$( '#xpui-wiz-next' ).hide();
							return;
						}

						// Step 2 done
						$( '#xpui-progress-fill' ).css( 'width', '75%' );
						$( '#xpui-prog-step-2' ).removeClass( 'active' ).addClass( 'done' ).find( '.xpui-wiz-progress-step-icon' ).text( '✓' );
						$( '#xpui-prog-step-3' ).addClass( 'active' );

						setTimeout( function () {
							// Step 3 done
							$( '#xpui-progress-fill' ).css( 'width', '100%' );
							$( '#xpui-prog-step-3' ).removeClass( 'active' ).addClass( 'done' ).find( '.xpui-wiz-progress-step-icon' ).text( '✓' );

							setTimeout( function () {
								// Move to Step 4 (Finish)
								currentStep = 4;
								updateStepUi();

								// Render the Success step for exactly one of three outcomes:
								//   (a) SaaS success    -> saas:true
								//   (b) Pure local      -> saas:false, no fallback (no account)
								//   (c) Fallback->local -> saas:false, fallback:true (Console error)
								// Hierarchy: header -> (fallback only) one note -> shortcode -> nudge.
								var d = response.data;

								// Shortcode embed (primary, always present).
								$( '#xpui-wiz-shortcode' ).text( d.shortcode );
								$( '#xpui-wiz-copy-btn' ).attr( 'data-clipboard-text', d.shortcode );

								if ( d.saas ) {
									// (a) SaaS success.
									$( '#xpui-wiz-success-title' ).css( 'color', '#15803d' ).text( i18n.createdOnConsole );
									$( '#xpui-wiz-success-msg' ).text( i18n.syncedBack );
									$( '#xpui-wiz-fallback-note' ).hide();
									$( '#xpui-wiz-connect-nudge' ).hide();
									$( '#xpui-wiz-success-actions' ).show();
									if ( d.edit_url ) {
										$( '#xpui-wiz-edit-btn' ).attr( 'href', d.edit_url ).show();
									} else {
										$( '#xpui-wiz-edit-btn' ).hide();
									}
								} else {
									// (b) + (c): saved locally. Header + nudge; default action row hidden
									// (the nudge carries its own CTA + 'continue' link).
									$( '#xpui-wiz-success-title' ).css( 'color', '#15803d' ).text( i18n.savedLocally );
									$( '#xpui-wiz-edit-btn' ).hide();
									$( '#xpui-wiz-success-actions' ).hide();
									$( '#xpui-wiz-connect-nudge' ).show();

									if ( d.fallback ) {
										// (c) Fallback: one friendly note (NOT duplicated in the subtitle);
										// the raw technical reason stays subtle/muted underneath.
										$( '#xpui-wiz-success-msg' ).text( i18n.convertedRunning );
										$( '#xpui-wiz-fallback-note-text' ).text( d.notice || d.message || '' );
										if ( d.reason ) {
											$( '#xpui-wiz-fallback-note-reason' ).text( i18n.details + ' ' + d.reason ).show();
										} else {
											$( '#xpui-wiz-fallback-note-reason' ).hide();
										}
										retrySyncSlug = d.slug || '';
										$( '#xpui-wiz-retry-status' ).text( '' );
										$( '#xpui-wiz-retry-sync-btn' ).prop( 'disabled', false ).show();
										$( '#xpui-wiz-fallback-note' ).css( 'display', 'flex' );
									} else {
										// (b) Pure local (no account): no error/note at all.
										$( '#xpui-wiz-success-msg' ).text( i18n.convertedWorkflow );
										$( '#xpui-wiz-fallback-note' ).hide();
									}
								}
							}, 800 );
						}, 600 );
					},
					error: function () {
						$( '#xpui-progress-fill' ).css( 'background', '#ef4444' );
						$( '#xpui-prog-step-2' ).removeClass( 'active' ).addClass( 'error' );
						$( '#xpui-prog-step-2' ).find( '.xpui-wiz-progress-step-icon' ).text( '❌' );
						$( '#xpui-prog-text-2' ).css( 'color', '#ef4444' ).text( i18n.networkError );
						$( '#xpui-wiz-buttons-row' ).show();
						$( '#xpui-wiz-prev' ).show();
						$( '#xpui-wiz-next' ).hide();
					}
				} );

			}, 1000 );
		}

		// Init UI
		updateStepUi();
	} );
} )( jQuery );
