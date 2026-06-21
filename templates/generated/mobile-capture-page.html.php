<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Mobile Capture</title>
<meta name="robots" content="noindex,nofollow">
<?php echo '<style>'; ?>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:system-ui,-apple-system,sans-serif;background:#f8fafc;min-height:100dvh;display:flex;flex-direction:column;}
.cp-wrap{display:flex;flex-direction:column;flex:1;max-width:480px;margin:0 auto;width:100%;padding:16px;}
.cp-header h1{font-size:18px;font-weight:600;color:#0f172a;text-align:center;margin-bottom:16px;}
.cp-body{display:flex;flex-direction:column;gap:12px;}
.cp-body canvas{border:2px solid #d1d5db;border-radius:8px;background:#fff;width:100%;touch-action:none;cursor:crosshair;}
.cp-body button{border:none;border-radius:8px;padding:14px 20px;font-size:16px;font-weight:600;cursor:pointer;width:100%;}
.cp-body .cp-btn-primary{background:#2563eb;color:#fff;}
.cp-body .cp-btn-secondary{background:#f1f5f9;color:#475569;}
.cp-body .cp-status{text-align:center;font-size:14px;color:#64748b;padding:12px;}
.cp-body .cp-status.success{color:#16a34a;font-weight:600;}
.cp-body .cp-status.error{color:#ef4444;}
.cp-body video{width:100%;border-radius:8px;background:#000;}
<?php echo '</style>'; ?>
</head>
<body>
<div class="cp-wrap">
  <div class="cp-header"><h1><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'field_label'))); ?></h1></div>
  <div class="cp-body" id="cp-body">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'field_type'), "signature"))): ?>
    <canvas id="sig-canvas" height="200"></canvas>
    <button class="cp-btn-secondary" type="button">Clear</button>
    <button class="cp-btn-primary" id="submit-btn" type="button">Submit Signature</button>
    <div class="cp-status" id="status-msg"></div>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'field_type'), "qr-scan"))): ?>
    <video id="video" autoplay playsinline></video>
    <canvas id="qr-canvas" style="display:none"></canvas>
    <div class="cp-status" id="status-msg">Point your camera at a QR code…</div>
<?php else: ?>
    <video id="video" autoplay playsinline></video>
    <div style="display:flex;gap:8px;">
      <button class="cp-btn-primary" id="capture-btn" type="button" style="flex:1;">Take Photo</button>
      <button class="cp-btn-secondary" id="flip-btn" type="button" style="flex:0 0 auto;width:52px;">🔄</button>
    </div>
    <canvas id="photo-canvas" style="display:none"></canvas>
    <div class="cp-status" id="status-msg"></div>
<?php endif; ?>
  </div>
</div>
<?php
wp_print_inline_script_tag(
	'window.XPRESSUI_CAPTURE_CONFIG=' . wp_json_encode(
		[
			'relayUrl'  => xpressui_bridge_template_stringify( xpressui_bridge_template_context_get( $xpressui_ctx, 'relay_url' ) ),
			'fieldType' => xpressui_bridge_template_stringify( xpressui_bridge_template_context_get( $xpressui_ctx, 'field_type' ) ),
		]
	) . ';'
);
wp_print_script_tag(
	[
		'src' => esc_url( xpressui_bridge_template_stringify( xpressui_bridge_template_context_get( $xpressui_ctx, 'runtime_url' ) ) ),
	]
);
?>
</body>
</html>
