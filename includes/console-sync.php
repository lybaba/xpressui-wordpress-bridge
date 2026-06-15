<?php
/**
 * Console Sync — pull workflow packs directly from the IntakeFlow Console.
 *
 * @package XPressUI_Bridge_Pro
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Settings helpers
// ---------------------------------------------------------------------------

function xpressui_get_console_connection(): array {
	$defaults = [ 'apiUrl' => 'https://app.intakeflow.dev', 'apiToken' => '' ];
	$stored   = get_option( 'xpressui_console_connection', [] );
	$conn     = is_array( $stored ) ? array_merge( $defaults, $stored ) : $defaults;
	if ( defined( 'XPRESSUI_CONSOLE_API_URL' ) ) {
		$conn['apiUrl'] = XPRESSUI_CONSOLE_API_URL;
	} else {
		$conn['apiUrl'] = 'https://app.intakeflow.dev';
	}
	return $conn;
}

function xpressui_render_console_connection_form(): void {
	$conn = xpressui_get_console_connection();
	?>
	<form id="xpressui-console-connection-form" method="post">
		<?php wp_nonce_field( 'xpressui_console_connection_action', 'xpressui_console_connection_nonce' ); ?>
		<input type="hidden" name="xpressui_save_console_connection" value="1">
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="xpressui_console_api_url"><?php esc_html_e( 'Console API URL', 'xpressui-bridge' ); ?></label></th>
				<td>
					<input type="url" id="xpressui_console_api_url" name="xpressui_console_api_url"
						value="<?php echo esc_url( $conn['apiUrl'] ); ?>"
						class="regular-text" readonly>
					<p class="description"><?php esc_html_e( 'Base URL of your IntakeFlow Console instance.', 'xpressui-bridge' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="xpressui_console_api_token"><?php esc_html_e( 'API Token', 'xpressui-bridge' ); ?></label></th>
				<td>
					<input type="password" id="xpressui_console_api_token" name="xpressui_console_api_token"
						value="<?php echo esc_attr( $conn['apiToken'] ); ?>"
						class="regular-text" autocomplete="off">
					<p class="description">
						<?php esc_html_e( 'Generate a token in the Console under Profile → API Tokens.', 'xpressui-bridge' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Save Connection', 'xpressui-bridge' ), 'secondary', 'submit', false ); ?>
		<span class="xpressui-ajax-status" style="margin-left:1rem;vertical-align:middle"></span>
	</form>
	<?php
}

// ---------------------------------------------------------------------------
// AJAX: save console connection
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_xpressui_save_console_connection', 'xpressui_ajax_save_console_connection' );

function xpressui_ajax_save_console_connection(): void {
	check_ajax_referer( 'xpressui_console_connection_action', 'xpressui_console_connection_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'xpressui-bridge' ) ], 403 );
	}
		$api_url   = isset( $_POST['xpressui_console_api_url'] ) ? esc_url_raw( trim( sanitize_text_field( wp_unslash( (string) $_POST['xpressui_console_api_url'] ) ) ) ) : '';
	$api_token = sanitize_text_field( wp_unslash( (string) ( $_POST['xpressui_console_api_token'] ?? '' ) ) );
	update_option( 'xpressui_console_connection', [ 'apiUrl' => $api_url, 'apiToken' => $api_token ] );
	wp_send_json_success( [ 'message' => __( 'Console connection saved.', 'xpressui-bridge' ) ] );
}

// ---------------------------------------------------------------------------
// AJAX: list projects from the Console
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_xpressui_console_list_projects', 'xpressui_ajax_console_list_projects' );

function xpressui_ajax_console_list_projects(): void {
	check_ajax_referer( 'xpressui_console_sync_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'xpressui-bridge' ) ], 403 );
	}

	if ( ! xpressui_pro_is_license_active() ) {
		wp_send_json_error( [ 'message' => __( 'Console Sync requires an active Console Connection.', 'xpressui-bridge' ) ], 403 );
	}

	$conn = xpressui_get_console_connection();
	if ( empty( $conn['apiUrl'] ) || empty( $conn['apiToken'] ) ) {
		wp_send_json_error( [ 'message' => __( 'Console connection not configured. Save your API URL and token first.', 'xpressui-bridge' ) ] );
	}

	$response = wp_remote_get(
		trailingslashit( $conn['apiUrl'] ) . 'api/v1/projects',
		[
			'headers' => [
				'X-Api-Token' => $conn['apiToken'],
				'Accept'      => 'application/json',
			],
			'timeout' => 15,
		]
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( [ 'message' => $response->get_error_message() ] );
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		/* translators: %d: HTTP status code returned by the Console API */
		wp_send_json_error( [ 'message' => sprintf( __( 'Console API returned status %d. Check your API URL and token.', 'xpressui-bridge' ), $code ) ] );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	wp_send_json_success( [ 'projects' => is_array( $body['items'] ?? null ) ? $body['items'] : [] ] );
}

// ---------------------------------------------------------------------------
// AJAX: sync (download + install) one project
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_xpressui_console_sync_project', 'xpressui_ajax_console_sync_project' );

function xpressui_ajax_console_sync_project(): void {
	check_ajax_referer( 'xpressui_console_sync_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'xpressui-bridge' ) ], 403 );
	}

	if ( ! xpressui_pro_is_license_active() ) {
		wp_send_json_error( [ 'message' => __( 'Console Sync requires an active Console Connection.', 'xpressui-bridge' ) ], 403 );
	}

	$project_id = sanitize_text_field( wp_unslash( (string) ( $_POST['project_id'] ?? '' ) ) );
	if ( '' === $project_id ) {
		wp_send_json_error( [ 'message' => __( 'Missing project_id.', 'xpressui-bridge' ) ] );
	}

	$conn = xpressui_get_console_connection();
	if ( empty( $conn['apiUrl'] ) || empty( $conn['apiToken'] ) ) {
		wp_send_json_error( [ 'message' => __( 'Console connection not configured.', 'xpressui-bridge' ) ] );
	}

	$response = wp_remote_get(
		trailingslashit( $conn['apiUrl'] ) . 'api/v1/projects/' . rawurlencode( $project_id ) . '/download',
		[
			'headers' => [
				'X-Api-Token' => $conn['apiToken'],
			],
			'timeout' => 30,
		]
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( [ 'message' => $response->get_error_message() ] );
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		/* translators: %d: HTTP status code returned by the Console API */
		wp_send_json_error( [ 'message' => sprintf( __( 'Download failed (status %d).', 'xpressui-bridge' ), $code ) ] );
	}

	$zip_content = wp_remote_retrieve_body( $response );
	$tmp_zip     = wp_tempnam( 'xpressui-sync' ) . '.zip';
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	if ( false === file_put_contents( $tmp_zip, $zip_content ) ) {
		wp_send_json_error( [ 'message' => __( 'Could not write temporary ZIP file.', 'xpressui-bridge' ) ] );
	}

	$content_disp  = wp_remote_retrieve_header( $response, 'content-disposition' );
	preg_match( '/filename="?([^";\s]+)"?/', (string) $content_disp, $matches );
	$original_name = isset( $matches[1] ) ? sanitize_file_name( $matches[1] ) : 'sync.zip';

	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();

	$inspection = xpressui_validate_workflow_zip( $tmp_zip, $original_name );
	if ( is_wp_error( $inspection ) ) {
		wp_delete_file( $tmp_zip );
		wp_send_json_error( [ 'message' => $inspection->get_error_message() ] );
	}

	$target_dir = xpressui_get_workflows_base_dir();
	if ( '' === $target_dir ) {
		wp_delete_file( $tmp_zip );
		wp_send_json_error( [ 'message' => __( 'The uploads directory is not available.', 'xpressui-bridge' ) ] );
	}

	if ( ! file_exists( $target_dir ) ) {
		wp_mkdir_p( $target_dir );
		global $wp_filesystem;
		$wp_filesystem->put_contents( $target_dir . 'index.php', '<?php' . PHP_EOL . '// Silence is golden.' . PHP_EOL, FS_CHMOD_FILE );
	}

	$slug     = (string) $inspection['slug'];
	$slug_dir = trailingslashit( $target_dir ) . $slug . '/';

	global $wp_filesystem;
	if ( file_exists( $slug_dir ) ) {
		$wp_filesystem->delete( $slug_dir, true );
	}

	$unzip_result = unzip_file( $tmp_zip, $target_dir );
	wp_delete_file( $tmp_zip );

	if ( is_wp_error( $unzip_result ) ) {
		wp_send_json_error( [ 'message' => $unzip_result->get_error_message() ] );
	}

	// Fetch and save hosted link configurations
	$configs_response = wp_remote_get(
		trailingslashit( $conn['apiUrl'] ) . 'api/v1/projects/' . rawurlencode( $slug ) . '/hosted-links/configs',
		[
			'headers' => [
				'X-Api-Token' => $conn['apiToken'],
				'Accept'      => 'application/json',
			],
			'timeout' => 20,
		]
	);

	if ( ! is_wp_error( $configs_response ) && 200 === wp_remote_retrieve_response_code( $configs_response ) ) {
		$configs = json_decode( wp_remote_retrieve_body( $configs_response ), true );
		if ( is_array( $configs ) ) {
			$hosted_links_dir = $slug_dir . 'hosted-links/';
			if ( ! file_exists( $hosted_links_dir ) ) {
				wp_mkdir_p( $hosted_links_dir );
			}
			foreach ( $configs as $config ) {
				// Only retrieve and synchronize hosted links of type default (pas les relances)
				$payload_data = is_array( $config['payload'] ?? null ) ? $config['payload'] : [];
				$follow_up    = $payload_data['followUp'] ?? $payload_data['follow_up'] ?? [];
				if ( is_array( $follow_up ) && ( ! empty( $follow_up['parentSubmissionId'] ) || ! empty( $follow_up['parent_submission_id'] ) ) ) {
					continue;
				}

				$link_id = sanitize_file_name( (string) ( $config['id'] ?? '' ) );
				if ( '' !== $link_id ) {
					$link_dir = $hosted_links_dir . $link_id . '/';
					if ( ! file_exists( $link_dir ) ) {
						wp_mkdir_p( $link_dir );
					}
					$wp_filesystem->put_contents( $link_dir . 'link.config.json', wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), FS_CHMOD_FILE );
				}
			}
		}
	}

	wp_send_json_success( [
		'slug'    => $slug,
		/* translators: %s: installed workflow slug */
		'message' => sprintf( __( 'Synced! Embed with: [xpressui id="%s"]', 'xpressui-bridge' ), $slug ),
	] );
}

// ---------------------------------------------------------------------------
// UI section — injected into the free plugin's Workflows page
// ---------------------------------------------------------------------------

add_action( 'xpressui_workflows_page_sections', 'xpressui_pro_render_console_sync_section' );

function xpressui_pro_render_console_sync_section(): void {
	$nonce = wp_create_nonce( 'xpressui_console_sync_nonce' );
	$is_license_active = function_exists( 'xpressui_pro_is_license_active' ) && xpressui_pro_is_license_active();
	?>
	<div class="card xpressui-admin-card">
		<h2><?php esc_html_e( 'Console Sync', 'xpressui-bridge' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Pull workflow packs directly from your IntakeFlow Console — no ZIP download required.', 'xpressui-bridge' ); ?>
		</p>

		<h3 style="margin-top:1rem"><?php esc_html_e( 'Connection', 'xpressui-bridge' ); ?></h3>
		<?php xpressui_render_console_connection_form(); ?>

		<?php if ( $is_license_active ) : ?>
			<h3 style="margin-top:1.5rem"><?php esc_html_e( 'Your Workflows', 'xpressui-bridge' ); ?></h3>
			<p>
				<button type="button" id="xpressui-load-projects" class="button button-secondary">
					<?php esc_html_e( 'Load from Console', 'xpressui-bridge' ); ?>
				</button>
				<button type="button" id="xpressui-sync-all-projects" class="button button-primary" style="display:none; margin-left: 10px;">
					<?php esc_html_e( 'Sync All Workflows', 'xpressui-bridge' ); ?>
				</button>
			</p>
			<div id="xpressui-projects-list"></div>

			<?php
			$console_sync_script = sprintf(
				<<<'JS'
(function () {
	var nonce = %1$s;
	var ajaxUrl = %2$s;
	var labels = %3$s;

	function escapeHtml(value) {
		return String(value || '').replace(/[&<>"']/g, function (char) {
			return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
		});
	}

	function setStatus(el, msg, isError) {
		el.innerHTML = '<p style="color:' + (isError ? '#c00' : '#3a3') + '">' + escapeHtml(msg) + '</p>';
	}

	var loadedProjects = [];

	document.getElementById('xpressui-load-projects').addEventListener('click', function () {
		var list = document.getElementById('xpressui-projects-list');
		list.innerHTML = '<p>' + escapeHtml(labels.loading) + '</p>';
		var syncAllBtn = document.getElementById('xpressui-sync-all-projects');
		if (syncAllBtn) {
			syncAllBtn.style.display = 'none';
		}

		var data = new URLSearchParams();
		data.set('action', 'xpressui_console_list_projects');
		data.set('nonce', nonce);

		fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res.success) {
					setStatus(list, res.data.message, true);
					return;
				}
				var projects = res.data.projects;
				loadedProjects = projects;
				if (!projects.length) {
					list.innerHTML = '<p>' + escapeHtml(labels.noProjects) + '</p>';
					return;
				}
				if (syncAllBtn) {
					syncAllBtn.style.display = 'inline-block';
					syncAllBtn.disabled = false;
				}
				var html = '<table class="widefat striped"><thead><tr>'
					+ '<th>' + escapeHtml(labels.name) + '</th>'
					+ '<th>' + escapeHtml(labels.slug) + '</th>'
					+ '<th>' + escapeHtml(labels.updated) + '</th>'
					+ '<th></th></tr></thead><tbody>';
				projects.forEach(function (p) {
					var id = escapeHtml(p.id);
					var date = p.updatedAt ? new Date(p.updatedAt).toLocaleDateString() : '-';
					html += '<tr id="xpressui-row-' + id + '">'
						+ '<td>' + escapeHtml(p.name) + '</td>'
						+ '<td><code>' + escapeHtml(p.slug) + '</code></td>'
						+ '<td>' + escapeHtml(date) + '</td>'
						+ '<td><button type="button" class="button button-primary xpressui-sync-btn" data-id="' + id + '">'
						+ escapeHtml(labels.sync) + '</button></td>'
						+ '</tr>';
				});
				html += '</tbody></table>';
				list.innerHTML = html;

				list.querySelectorAll('.xpressui-sync-btn').forEach(function (btn) {
					btn.addEventListener('click', function () {
						var projectId = this.dataset.id;
						var row = document.getElementById('xpressui-row-' + projectId);
						this.disabled = true;
						this.textContent = labels.syncing;
						if (syncAllBtn) {
							syncAllBtn.disabled = true;
						}

						var syncData = new URLSearchParams();
						syncData.set('action', 'xpressui_console_sync_project');
						syncData.set('nonce', nonce);
						syncData.set('project_id', projectId);

						fetch(ajaxUrl, { method: 'POST', body: syncData, credentials: 'same-origin' })
							.then(function (r) { return r.json(); })
							.then(function (res) {
								if (syncAllBtn) {
									syncAllBtn.disabled = false;
								}
								if (!res.success) {
									row.querySelector('td:last-child').innerHTML =
										'<span style="color:#c00">' + escapeHtml(res.data.message) + '</span>';
								} else {
									row.querySelector('td:last-child').innerHTML =
										'<span style="color:#3a3">' + escapeHtml(labels.syncedPrefix + ' ' + res.data.message + ' - ' + labels.reloading) + '</span>';
									sessionStorage.setItem('xpressui_synced_slug', res.data.slug || '');
									setTimeout(function () { window.location.reload(); }, 1200);
								}
							})
							.catch(function () {
								if (syncAllBtn) {
									syncAllBtn.disabled = false;
								}
								row.querySelector('td:last-child').innerHTML =
									'<span style="color:#c00">' + escapeHtml(labels.networkError) + '</span>';
							});
					});
				});
			})
			.catch(function () {
				setStatus(list, labels.networkConnectionError, true);
			});
	});

	var syncAllBtn = document.getElementById('xpressui-sync-all-projects');
	if (syncAllBtn) {
		syncAllBtn.addEventListener('click', function () {
			if (!loadedProjects || !loadedProjects.length) return;
			var list = document.getElementById('xpressui-projects-list');

			document.getElementById('xpressui-load-projects').disabled = true;
			syncAllBtn.disabled = true;

			var progressDiv = document.getElementById('xpressui-global-sync-progress');
			if (!progressDiv) {
				progressDiv = document.createElement('div');
				progressDiv.id = 'xpressui-global-sync-progress';
				progressDiv.style.margin = '15px 0';
				progressDiv.style.padding = '12px 16px';
				progressDiv.style.background = '#f0f9ff';
				progressDiv.style.border = '1px solid #bae6fd';
				progressDiv.style.color = '#0369a1';
				progressDiv.style.borderRadius = '6px';
				progressDiv.style.fontWeight = '500';
				list.insertBefore(progressDiv, list.firstChild);
			}

			var index = 0;
			var total = loadedProjects.length;

			function syncNext() {
				if (index >= total) {
					progressDiv.style.background = '#f0fdf4';
					progressDiv.style.border = '1px solid #bbf7d0';
					progressDiv.style.color = '#15803d';
					progressDiv.innerHTML = '<strong>' + escapeHtml(labels.allSynced) + '</strong> ' + escapeHtml(labels.reloading);
					setTimeout(function () {
						window.location.reload();
					}, 1500);
					return;
				}

				var p = loadedProjects[index];
				progressDiv.innerHTML = escapeHtml(labels.syncingAll) + ' <strong>' + (index + 1) + '/' + total + '</strong> (' + escapeHtml(p.name) + ')...';

				var row = document.getElementById('xpressui-row-' + p.id);
				var rowBtn = row ? row.querySelector('.xpressui-sync-btn') : null;
				if (rowBtn) {
					rowBtn.disabled = true;
					rowBtn.textContent = labels.syncing;
				}

				var syncData = new URLSearchParams();
				syncData.set('action', 'xpressui_console_sync_project');
				syncData.set('nonce', nonce);
				syncData.set('project_id', p.id);

				fetch(ajaxUrl, { method: 'POST', body: syncData, credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (res) {
						if (row) {
							if (!res.success) {
								row.querySelector('td:last-child').innerHTML =
									'<span style="color:#c00">' + escapeHtml(res.data.message) + '</span>';
							} else {
								row.querySelector('td:last-child').innerHTML =
									'<span style="color:#3a3">' + escapeHtml(labels.syncedPrefix + ' ' + res.data.message) + '</span>';
							}
						}
						index++;
						syncNext();
					})
					.catch(function () {
						if (row) {
							row.querySelector('td:last-child').innerHTML =
								'<span style="color:#c00">' + escapeHtml(labels.networkError) + '</span>';
						}
						index++;
						syncNext();
					});
			}

			syncNext();
		});
	}
}());
JS,
				wp_json_encode( $nonce ),
				wp_json_encode( admin_url( 'admin-ajax.php' ) ),
				wp_json_encode(
					[
						'loading'                => __( 'Loading...', 'xpressui-bridge' ),
						'noProjects'             => __( 'No projects found in your Console.', 'xpressui-bridge' ),
						'name'                   => __( 'Name', 'xpressui-bridge' ),
						'slug'                   => __( 'Slug', 'xpressui-bridge' ),
						'updated'                => __( 'Updated', 'xpressui-bridge' ),
						'sync'                   => __( 'Sync', 'xpressui-bridge' ),
						'syncing'                => __( 'Syncing...', 'xpressui-bridge' ),
						'syncedPrefix'           => __( 'Synced:', 'xpressui-bridge' ),
						'reloading'              => __( 'Reloading...', 'xpressui-bridge' ),
						'networkError'           => __( 'Network error.', 'xpressui-bridge' ),
						'networkConnectionError' => __( 'Network error. Check your connection.', 'xpressui-bridge' ),
						'syncingAll'             => __( 'Syncing workflows:', 'xpressui-bridge' ),
						'allSynced'              => __( 'All workflows synchronized!', 'xpressui-bridge' ),
					]
				)
			);
			wp_print_inline_script_tag( $console_sync_script );
			?>
		<?php else : ?>
			<p style="margin-top:1.5rem;color:#c00">
				<strong><?php esc_html_e( 'Console connection required.', 'xpressui-bridge' ); ?></strong><br>
				<?php esc_html_e( 'Please enter your API token above and save the connection to start syncing workflows.', 'xpressui-bridge' ); ?>
			</p>
		<?php endif; ?>

		<?php
		$connection_form_script = sprintf(
			<<<'JS'
(function () {
	var form = document.getElementById('xpressui-console-connection-form');
	if (!form) { return; }
	form.addEventListener('submit', function (e) {
		e.preventDefault();
		var statusEl  = form.querySelector('.xpressui-ajax-status');
		var submitBtn = form.querySelector('[type="submit"]');
		if (submitBtn) { submitBtn.disabled = true; }
		if (statusEl) { statusEl.textContent = %1$s; statusEl.style.color = ''; }

		var data = new FormData(form);
		data.set('action', 'xpressui_save_console_connection');

		fetch(%2$s, { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (submitBtn) { submitBtn.disabled = false; }
				if (statusEl) {
					statusEl.textContent = res.success ? %3$s : (res.data.message || 'Error.');
					statusEl.style.color = res.success ? '#3a3' : '#c00';
				}
				if (res.success) {
					setTimeout(function () { window.location.reload(); }, 1000);
				}
			})
			.catch(function () {
				if (submitBtn) { submitBtn.disabled = false; }
				if (statusEl) { statusEl.textContent = %4$s; statusEl.style.color = '#c00'; }
			});
	});
}());
JS,
			wp_json_encode( __( 'Saving…', 'xpressui-bridge' ) ),
			wp_json_encode( admin_url( 'admin-ajax.php' ) ),
			wp_json_encode( __( 'Saved. Reloading…', 'xpressui-bridge' ) ),
			wp_json_encode( __( 'Network error.', 'xpressui-bridge' ) )
		);
		wp_print_inline_script_tag( $connection_form_script );
		?>
	</div>
	<?php
}
