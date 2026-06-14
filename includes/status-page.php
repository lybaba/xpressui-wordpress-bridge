<?php
/**
 * Client-facing submission status page (Pro).
 *
 * Registers a public /xpressui-status/{token}/ route that any submitter
 * can use to check the current status of their file without logging in.
 * The 32-char hex token is generated at submission time and sent to the
 * submitter inside the confirmation email.
 *
 * @package XPressUI_Bridge_Pro
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Hook registration
// ---------------------------------------------------------------------------

add_action( 'xpressui_submission_first_created', 'xpressui_pro_generate_status_token', 10, 2 );
add_filter( 'xpressui_submit_confirmation_extra_html', 'xpressui_pro_status_link_email_html', 10, 3 );
add_action( 'init', 'xpressui_pro_register_status_rewrite' );
add_filter( 'query_vars', 'xpressui_pro_add_status_query_var' );
add_action( 'template_redirect', 'xpressui_pro_handle_status_route', 1 );

// ---------------------------------------------------------------------------
// Token helpers
// ---------------------------------------------------------------------------

function xpressui_pro_generate_status_token( int $post_id, string $project_slug ): void {
	unset( $project_slug );
	if ( '' !== (string) get_post_meta( $post_id, '_xpressui_status_token', true ) ) {
		return;
	}
	update_post_meta( $post_id, '_xpressui_status_token', bin2hex( random_bytes( 16 ) ) );
}

function xpressui_pro_get_status_page_url( string $token ): string {
	return home_url( '/xpressui-status/' . rawurlencode( $token ) . '/' );
}

function xpressui_pro_get_post_id_by_status_token( string $token ): int {
	if ( strlen( $token ) !== 32 || ! ctype_xdigit( $token ) ) {
		return 0;
	}
	$q = new WP_Query( [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	] );
	foreach ( $q->posts as $post_id ) {
		if ( hash_equals( $token, (string) get_post_meta( (int) $post_id, '_xpressui_status_token', true ) ) ) {
			return (int) $post_id;
		}
	}
	return 0;
}

// ---------------------------------------------------------------------------
// Email injection
// ---------------------------------------------------------------------------

function xpressui_pro_status_link_email_html( string $html, int $post_id, string $project_slug ): string {
	unset( $project_slug );
	$token = (string) get_post_meta( $post_id, '_xpressui_status_token', true );
	if ( '' === $token ) {
		return $html;
	}
	$url = xpressui_pro_get_status_page_url( $token );
	return $html
		. '<div style="margin:24px 0 0;text-align:center;">'
		. '<a href="' . esc_url( $url ) . '" style="display:inline-block;padding:10px 20px;background:#f0f4ff;color:#1e3a8a;text-decoration:none;border-radius:6px;font-size:14px;font-weight:600;border:1px solid #bfdbfe;">'
		. esc_html__( 'Track your submission', 'xpressui-bridge-pro' )
		. '</a>'
		. '<p style="margin:8px 0 0;font-size:12px;color:#6b7280;">'
		. esc_html__( 'This link is private and unique to your submission. Bookmark it to follow up at any time.', 'xpressui-bridge-pro' )
		. '</p>'
		. '</div>';
}

// ---------------------------------------------------------------------------
// Rewrite rule
// ---------------------------------------------------------------------------

function xpressui_pro_register_status_rewrite(): void {
	add_rewrite_rule(
		'^xpressui-status/([a-f0-9]{32})/?$',
		'index.php?xpressui_status_token=$matches[1]',
		'top'
	);
}

function xpressui_pro_add_status_query_var( array $vars ): array {
	$vars[] = 'xpressui_status_token';
	return $vars;
}

// ---------------------------------------------------------------------------
// Page renderer
// ---------------------------------------------------------------------------

function xpressui_pro_handle_status_route(): void {
	$token = (string) get_query_var( 'xpressui_status_token', '' );
	if ( '' === $token ) {
		return;
	}

	$post_id = xpressui_pro_get_post_id_by_status_token( $token );
	if ( 0 === $post_id ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		include get_query_template( '404' );
		exit;
	}

	$post         = get_post( $post_id );
	$project_slug = (string) get_post_meta( $post_id, '_xpressui_project_slug', true );
	$raw_status   = (string) get_post_meta( $post_id, '_xpressui_submission_status', true );
	if ( '' === $raw_status ) {
		$raw_status = 'new';
	}

	$status_map   = xpressui_pro_status_display_map();
	$status_info  = $status_map[ $raw_status ] ?? $status_map['new'];

	$all_settings   = get_option( 'xpressui_project_settings', [] );
	$s              = is_array( $all_settings[ $project_slug ] ?? null ) ? $all_settings[ $project_slug ] : [];
	$workflow_label = trim( (string) ( $s['workflowLabel'] ?? '' ) );
	if ( '' === $workflow_label ) {
		$workflow_label = $project_slug;
	}

	$updated_at = $post instanceof WP_Post ? mysql2date( get_option( 'date_format' ), $post->post_modified ) : '';
	$site_name  = get_bloginfo( 'name' );

	status_header( 200 );
	xpressui_pro_output_status_page( $site_name, $workflow_label, $status_info, $updated_at );
	exit;
}

function xpressui_pro_status_display_map(): array {
	return [
		'new'          => [
			'label' => __( 'Received', 'xpressui-bridge-pro' ),
			'color' => '#374151',
			'bg'    => '#f3f4f6',
			'icon'  => '📬',
			'note'  => __( 'Your file has been received and is waiting for review.', 'xpressui-bridge-pro' ),
		],
		'in-review'    => [
			'label' => __( 'In Review', 'xpressui-bridge-pro' ),
			'color' => '#1e40af',
			'bg'    => '#dbeafe',
			'icon'  => '🔍',
			'note'  => __( 'Our team is currently reviewing your file.', 'xpressui-bridge-pro' ),
		],
		'pending_info' => [
			'label' => __( 'Action Required', 'xpressui-bridge-pro' ),
			'color' => '#92400e',
			'bg'    => '#fef3c7',
			'icon'  => '📋',
			'note'  => __( 'Additional information or documents have been requested. Please check your email.', 'xpressui-bridge-pro' ),
		],
		'done'         => [
			'label' => __( 'Completed', 'xpressui-bridge-pro' ),
			'color' => '#14532d',
			'bg'    => '#dcfce7',
			'icon'  => '✅',
			'note'  => __( 'Your file has been processed successfully.', 'xpressui-bridge-pro' ),
		],
		'rejected'     => [
			'label' => __( 'Rejected', 'xpressui-bridge-pro' ),
			'color' => '#7f1d1d',
			'bg'    => '#fee2e2',
			'icon'  => '❌',
			'note'  => __( 'Your file could not be processed. Please check your email for details.', 'xpressui-bridge-pro' ),
		],
	];
}

function xpressui_pro_output_status_page( string $site_name, string $workflow_label, array $status_info, string $updated_at ): void {
		$label    = (string) ( $status_info['label'] ?? '' );
		$color    = (string) ( $status_info['color'] ?? '#374151' );
		$bg       = (string) ( $status_info['bg'] ?? '#f3f4f6' );
		$icon     = (string) ( $status_info['icon'] ?? '' );
		$note     = (string) ( $status_info['note'] ?? '' );
		$updated  = $updated_at;

	?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
	<title><?php
	printf(
		/* translators: %s: site name */
		esc_html__( 'Submission Status — %s', 'xpressui-bridge-pro' ),
		esc_html( $site_name )
	);
	?></title>
<meta name="robots" content="noindex,nofollow">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f8fafc;color:#1e293b;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px}
.xpsp-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:40px 36px;max-width:480px;width:100%;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.xpsp-card .xpsp-site{font-size:13px;color:#64748b;margin-bottom:24px;font-weight:500}
.xpsp-card .xpsp-workflow{font-size:22px;font-weight:700;color:#0f172a;margin-bottom:20px;line-height:1.3}
.xpsp-card .xpsp-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:999px;font-size:15px;font-weight:600;margin-bottom:20px}
.xpsp-card .xpsp-note{font-size:14px;color:#475569;line-height:1.6;margin-bottom:24px}
.xpsp-card .xpsp-updated{font-size:12px;color:#94a3b8;border-top:1px solid #f1f5f9;padding-top:16px}
</style>
</head>
<body>
<div class="xpsp-card">
		<div class="xpsp-site"><?php echo esc_html( $site_name ); ?></div>
		<div class="xpsp-workflow"><?php echo esc_html( $workflow_label ); ?></div>
		<div class="xpsp-badge" style="background:<?php echo esc_attr( $bg ); ?>;color:<?php echo esc_attr( $color ); ?>">
			<span aria-hidden="true"><?php echo esc_html( $icon ); ?></span>
			<span><?php echo esc_html( $label ); ?></span>
	</div>
		<p class="xpsp-note"><?php echo esc_html( $note ); ?></p>
		<?php if ( '' !== $updated ) : ?>
	<div class="xpsp-updated">
		<?php
		printf(
				/* translators: %s: date of last update */
				esc_html__( 'Last updated: %s', 'xpressui-bridge-pro' ),
				esc_html( $updated )
			);
		?>
	</div>
	<?php endif; ?>
</div>
</body>
</html>
	<?php
}
