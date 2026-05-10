<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex, nofollow" />
  <title><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'name'))); ?></title>
  <?php // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- CSS extracted by xpressui_build_shortcode_inline_css() and delivered via wp_add_inline_style(); standalone-shell path outputs a full HTML document ?>
<style>
    :root {
      --template-font-family: <?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'font_family')) ? xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'font_family') : (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress")) ? "inherit" : "Inter, system-ui, sans-serif")))); ?>;
      --template-page-background: <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'colors'), 'page_background'))); ?>;
      --template-surface: <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'colors'), 'surface'))); ?>;
      --template-text: <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'colors'), 'text'))); ?>;
      --template-muted-text: color-mix(in srgb, var(--template-text) 65%, transparent);
      --template-primary: <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'colors'), 'primary'))); ?>;
      --template-border: <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'colors'), 'border'))); ?>;
      --template-card-radius: <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'radius'), 'card'))); ?>px;
      --template-input-radius: <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'radius'), 'input'))); ?>px;
      --template-button-radius: <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'radius'), 'button'))); ?>px;
    }
    body {
      margin: 0;
      font-family: var(--template-font-family);
      color: var(--template-text);
      background: var(--template-page-background);
    }
    .page-shell {
      min-height: 100dvh;
      height: 100%;
      display: grid;
      place-items: center;
      padding: 24px 16px;
      position: relative;
      isolation: isolate;
      overflow: hidden;
    }
    #xpressui-root {
      position: relative;
      isolation: isolate;
      width: 100%;
      padding: 32px 16px;
      box-sizing: border-box;
      font-size: 14px;
      font-family: var(--template-font-family);
      color: var(--template-text);
    }
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'background_image_url'), xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'background_style'), "panel")))): ?>
    <?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress")) ? "#xpressui-root" : ".page-shell"))); ?>::before {
      content: "";
      position: absolute;
      inset: <?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress")) ? "0" : "18px"))); ?>;
      z-index: -2;
      border-radius: <?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress")) ? "24px" : "36px"))); ?>;
      background:
        linear-gradient(180deg, rgba(15,23,42,0.18), rgba(15,23,42,0.32)),
        url('<?php echo esc_attr(xpressui_bridge_template_stringify(esc_url(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'background_image_url')))); ?>') center center / cover no-repeat;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
    }
    <?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress")) ? "#xpressui-root" : ".page-shell"))); ?>::after {
      content: "";
      position: absolute;
      inset: <?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress")) ? "0" : "18px"))); ?>;
      z-index: -1;
      border-radius: <?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress")) ? "24px" : "36px"))); ?>;
      background:
        linear-gradient(180deg, rgba(255,255,255,0.06), rgba(15,23,42,0.08)),
        linear-gradient(180deg, color-mix(in srgb, var(--template-page-background) 8%, transparent), color-mix(in srgb, var(--template-page-background) 36%, rgba(15,23,42,0.10)));
      pointer-events: none;
    }
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'background_image_url'), xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'background_style'), "full-bleed")))): ?>
    <?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress")) ? "#xpressui-root" : "body"))); ?>::before {
      content: "";
      position: <?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress")) ? "absolute" : "fixed"))); ?>;
      inset: 0;
      z-index: -2;
      background:
        linear-gradient(180deg, rgba(15,23,42,0.18), rgba(15,23,42,0.38)),
        radial-gradient(circle at top, rgba(255,255,255,0.18), transparent 34%),
        url('<?php echo esc_attr(xpressui_bridge_template_stringify(esc_url(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'background_image_url')))); ?>') center center / cover no-repeat;
      filter: saturate(0.9) contrast(0.92) brightness(0.82);
      transform: scale(1.02);
    }
    <?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress")) ? "#xpressui-root" : "body"))); ?>::after {
      content: "";
      position: <?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress")) ? "absolute" : "fixed"))); ?>;
      inset: 0;
      z-index: -1;
      background: linear-gradient(
        180deg,
        color-mix(in srgb, var(--template-page-background) 34%, transparent),
        color-mix(in srgb, var(--template-page-background) 68%, rgba(15,23,42,0.24))
      );
      pointer-events: none;
    }
<?php endif; ?>
    .form-frame {
      width: min(100%, 900px);
      background: <?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'background_image_url'), (!xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'background_style'), "none"))))): ?>color-mix(in srgb, var(--template-surface) 96%, transparent)<?php else: ?>color-mix(in srgb, var(--template-surface) 92%, white)<?php endif; ?>;
      border-radius: var(--template-card-radius);
      padding: 28px;
      box-shadow: <?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'background_image_url'), (!xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'background_style'), "none"))))): ?>0 28px 80px -38px rgba(0,0,0,0.42)<?php else: ?>0 20px 60px rgba(0,0,0,0.08)<?php endif; ?>;
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'background_image_url'), (!xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'background_style'), "none"))))): ?>
backdrop-filter: blur(18px) saturate(1.08);<?php endif; ?>
      position: relative;
      z-index: 1;
    }
    .hosted-link-presentation {
      display: grid;
      gap: 8px;
      margin: 0 0 18px;
      padding: 18px 20px;
      border: 1px solid color-mix(in srgb, var(--template-primary) 22%, var(--template-border));
      border-radius: max(calc(var(--template-card-radius) - 8px), 16px);
      background:
        linear-gradient(135deg, color-mix(in srgb, var(--template-primary) 10%, var(--template-surface)), color-mix(in srgb, var(--template-surface) 96%, white));
      box-shadow: 0 14px 34px -30px color-mix(in srgb, var(--template-primary) 45%, transparent);
    }
    .hosted-link-presentation[data-theme='dark'],
    .hosted-link-presentation[data-theme='primary'] {
      background:
        linear-gradient(135deg, color-mix(in srgb, var(--template-primary) 88%, #0f172a), color-mix(in srgb, #0f172a 86%, var(--template-primary)));
      border-color: color-mix(in srgb, var(--template-primary) 55%, rgba(255,255,255,0.28));
    }
    .hosted-link-eyebrow { color: var(--template-primary); font-size: 11px; font-weight: 900; letter-spacing: 0.08em; line-height: 1; text-transform: uppercase; }
    .hosted-link-title { margin: 0; color: var(--template-text); font-size: 24px; font-weight: 900; line-height: 1.1; letter-spacing: 0; overflow-wrap: anywhere; }
    .hosted-link-description { margin: 0; max-width: 720px; color: var(--template-muted-text); font-size: 14px; line-height: 1.55; }
    .hosted-link-presentation[data-theme='dark'] .hosted-link-eyebrow,
    .hosted-link-presentation[data-theme='primary'] .hosted-link-eyebrow { color: color-mix(in srgb, #ffffff 84%, var(--template-primary)); }
    .hosted-link-presentation[data-theme='dark'] .hosted-link-title,
    .hosted-link-presentation[data-theme='primary'] .hosted-link-title { color: #f8fafc; }
    .hosted-link-presentation[data-theme='dark'] .hosted-link-description,
    .hosted-link-presentation[data-theme='primary'] .hosted-link-description { color: rgba(226,232,240,0.92); }
    .template-runtime-shell { display: grid; gap: 16px; }
    .template-form-header { display: grid; gap: 4px; padding: 0 2px; }
    .template-form-title { margin: 0; font-size: 36px; line-height: 1.04; letter-spacing: 0; color: var(--template-text); font-weight: 900; }
    .template-form-subtitle { margin: 0; color: var(--template-muted-text); font-size: 12px; line-height: 1.45; max-width: 720px; }
    .template-step-status { display: grid; gap: 8px; padding: 0 4px 6px; background: transparent; border: none; }
    .template-step-status[data-step-feedback-state='loading'] { background: color-mix(in srgb, #3b82f6 8%, var(--template-surface)); border-color: color-mix(in srgb, #3b82f6 20%, transparent); }
    .template-step-status[data-step-feedback-state='success'] { background: color-mix(in srgb, #22c55e 8%, var(--template-surface)); border-color: color-mix(in srgb, #22c55e 20%, transparent); }
    .template-step-status-title { font-size: 12px; font-weight: 800; color: var(--template-primary); text-transform: uppercase; letter-spacing: 0.06em; display: inline-flex; align-items: center; align-self: start; background: color-mix(in srgb, var(--template-primary) 8%, transparent); padding: 6px 12px; border-radius: 999px; }
    .template-step-status-message { display: none; /* Cleaner to hide this and rely on Section labels */ }
    .template-section { display: grid; gap: 14px; padding: 18px; border-radius: max(calc(var(--template-card-radius) - 6px), 18px); border: 1px solid color-mix(in srgb, var(--template-border) 72%, rgba(15,23,42,0.08)); background: color-mix(in srgb, var(--template-surface) 84%, white); }
    .template-section-header { display: grid; gap: 4px; }
    .template-section-label { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 0; color: var(--template-text); }
    .template-section-desc { margin: 0 0 4px 0; color: var(--template-muted-text); font-size: 14px; line-height: 1.5; }
    .template-fields { display: grid; grid-template-columns: minmax(0, 1fr); gap: 12px; width: 100%; }
    .template-runtime-shell[data-field-columns='2'] .template-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .template-runtime-shell[data-field-columns='3'] .template-fields { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .template-field { display: flex; flex-direction: column; gap: 6px; width: 100%; min-width: 0; }
    .template-runtime-shell[data-label-position='inline'] .template-field { display: grid; grid-template-columns: minmax(112px, 150px) minmax(0, 1fr); align-items: start; column-gap: 12px; }
    .template-runtime-shell[data-label-position='inline'] .template-field-label-row { padding-top: 14px; }
    .template-runtime-shell[data-label-position='inline'] .template-field-help,
    .template-runtime-shell[data-label-position='inline'] .template-field-meta,
    .template-runtime-shell[data-label-position='inline'] .template-field-messages { grid-column: 2; }
    .template-runtime-shell[data-label-position='hidden'] .template-field-label-row { display: none; }
    .template-field-label-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .template-field-label { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 700; color: var(--template-text); }
    .template-field-meta-inline { display: inline-flex; align-items: center; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
    .template-required { color: var(--template-muted-text); font-size: 13px; font-weight: 700; line-height: 1; }
    .template-field-help { font-size: 13px; color: var(--template-muted-text); }
    .template-field-messages { display: grid; gap: 6px; }
    .template-field-message { padding: 0; border-radius: 0; font-size: 12px; line-height: 1.45; }
    .template-field-message.is-error { background: transparent; color: color-mix(in srgb, #ef4444 80%, var(--template-text)); border: 0; font-weight: 600; }
    .template-field-message.is-success { background: color-mix(in srgb, #22c55e 10%, transparent); color: color-mix(in srgb, #22c55e 80%, var(--template-text)); border: 1px solid color-mix(in srgb, #22c55e 25%, transparent); padding: 8px 12px; border-radius: 8px; }
    .template-field-meta { display: flex; flex-wrap: wrap; gap: 8px; }
    #xpressui-root .template-field-pill { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px; background: color-mix(in srgb, var(--template-border) 40%, transparent); border: 1px solid color-mix(in srgb, var(--template-border) 80%, transparent); color: var(--template-text); font-size: 12px; font-weight: 700; line-height: 1; }
    #xpressui-root [data-product-list-total] { padding: 0; border: 0; background: transparent; color: var(--template-text); font-size: 18px; font-weight: 500; }
    #xpressui-root [data-product-list-total-amount] { font-size: 18px; font-weight: 500; color: var(--template-text); }
    .template-input,
    .template-textarea { display: block; width: 100%; min-width: 0; max-width: none; box-sizing: border-box; border: 1px solid var(--template-border); border-radius: var(--template-input-radius); background: color-mix(in srgb, var(--template-surface) 96%, white); color: var(--template-text); font: inherit; padding: 12px 14px; }
    .template-runtime-shell select { display: block; width: 100%; min-width: 0; max-width: none; box-sizing: border-box; border: 1px solid var(--template-border) !important; border-radius: var(--template-input-radius) !important; background-color: color-mix(in srgb, var(--template-surface) 96%, white) !important; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23888888'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") !important; background-repeat: no-repeat !important; background-position: right 16px center !important; background-size: 16px !important; color: var(--template-text) !important; font: inherit; padding: 12px 40px 12px 14px !important; -webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important; accent-color: var(--template-primary) !important; }
    .template-runtime-shell select option:checked { background: color-mix(in srgb, var(--template-primary) 15%, white) !important; color: var(--template-text) !important; }
    .template-textarea { min-height: 144px; resize: vertical; }
    .template-input::placeholder,
    .template-textarea::placeholder { color: var(--template-muted-text); opacity: 0.8; }
    .template-runtime-shell .template-input:focus,
    .template-runtime-shell .template-textarea:focus,
    .template-runtime-shell select:focus,
    .template-runtime-shell input[type="text"]:focus,
    .template-runtime-shell input[type="email"]:focus,
    .template-runtime-shell input[type="tel"]:focus,
    .template-runtime-shell input[type="number"]:focus,
    .template-runtime-shell input[type="url"]:focus {
      outline: none !important;
      border-color: var(--template-primary) !important;
      box-shadow: 0 0 0 3px color-mix(in srgb, var(--template-primary) 15%, transparent) !important;
    }
    .template-input.input-error,
    .template-textarea.textarea-error,
    .template-runtime-shell select.select-error {
      border-color: #ef4444 !important;
      box-shadow: 0 0 0 3px color-mix(in srgb, #ef4444 15%, transparent) !important;
    }
    .template-runtime-shell input[type="radio"],
    .template-runtime-shell input[type="checkbox"],
    .template-runtime-shell input[type="range"] {
      -webkit-appearance: auto !important;
      -moz-appearance: auto !important;
      appearance: auto !important;
      accent-color: var(--template-primary) !important;
    }
    .template-runtime-shell input[type="radio"]:focus,
    .template-runtime-shell input[type="checkbox"]:focus,
    .template-runtime-shell input[type="range"]:focus,
    .template-runtime-shell input[type="radio"]:focus-visible,
    .template-runtime-shell input[type="checkbox"]:focus-visible,
    .template-runtime-shell input[type="range"]:focus-visible {
      outline: 2px solid var(--template-primary) !important;
      outline-offset: 2px !important;
      box-shadow: none !important;
    }
    .template-product-grid,
    .template-choice-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
    .template-product-card { display: grid; gap: 10px; padding: 14px; border-radius: 18px; border: 1.5px solid color-mix(in srgb, var(--template-border) 60%, transparent); background: var(--template-surface); align-content: start; }
    .template-product-media,
    .template-choice-media { width: 100%; aspect-ratio: 16 / 10; border-radius: 14px; overflow: hidden; background: rgba(226,232,240,0.75); display: grid; place-items: center; }
    .template-product-media img,
    .template-choice-media img { width: 100%; height: 100%; object-fit: cover; object-position: center; display: block; }
    .template-product-title { margin: 0; font-size: 16px; font-weight: 700; color: var(--template-text); }
    .template-product-meta { display: flex; justify-content: space-between; align-items: center; gap: 10px; color: var(--template-muted-text); font-size: 13px; }
    .template-product-price { font-weight: 700; color: var(--template-text); }
    .template-product-actions { display: flex; justify-content: center; align-items: center; gap: 10px; padding-top: 2px; min-height: 36px; }
    #xpressui-root .template-stepper-btn { width: 36px; height: 36px; border: 1px solid color-mix(in srgb, var(--template-border) 50%, transparent); border-radius: 999px; background: color-mix(in srgb, var(--template-text) 96%, transparent); color: var(--template-surface); font-size: 18px; line-height: 1; display: inline-flex; align-items: center; justify-content: center; }
    #xpressui-root .template-stepper-btn.is-muted { background: transparent; color: var(--template-muted-text); }
    .template-quiz-wrap { display: grid; gap: 12px; padding: 16px; border-radius: 18px; background: rgba(248,250,252,0.94); border: 1px solid rgba(15,23,42,0.08); }
    .template-choice-grid { display: grid; gap: 8px; }
    .template-choice-grid--vertical, .template-choice-grid[data-choice-layout="vertical"] { grid-template-columns: 1fr; }
    .template-choice-grid--horizontal, .template-choice-grid[data-choice-layout="horizontal"] { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
    .template-choice-grid--auto, .template-choice-grid[data-choice-layout="auto"] { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
    .template-choice-card { position: relative; display: grid; gap: 4px; align-content: start; min-height: 0; padding: 14px 16px; border-radius: 14px; background: color-mix(in srgb, var(--template-surface) 96%, transparent); border: 1.5px solid color-mix(in srgb, var(--template-border) 60%, transparent); box-shadow: 0 2px 8px -2px rgba(15,23,42,0.04); transition: border-color 200ms ease, box-shadow 200ms ease, background 200ms ease, transform 200ms ease; cursor: pointer; }
    .template-choice-card:hover { transform: translateY(-2px); border-color: color-mix(in srgb, var(--template-primary) 40%, var(--template-border)); box-shadow: 0 8px 24px -6px color-mix(in srgb, var(--template-text) 8%, transparent); }
    .template-choice-card[data-selected="true"] { border-color: var(--template-primary); box-shadow: 0 0 0 1px var(--template-primary), 0 8px 24px -6px color-mix(in srgb, var(--template-primary) 30%, transparent); background: color-mix(in srgb, var(--template-primary) 4%, var(--template-surface)); }
    .template-choice-card[data-selected="true"]::after { content: ""; position: absolute; top: 50%; right: 14px; transform: translateY(-50%); width: 22px; height: 22px; border-radius: 50%; background-color: var(--template-primary); background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 13l4 4L19 7'/%3E%3C/svg%3E"); background-position: center; background-repeat: no-repeat; background-size: 12px; }
    .template-choice-card[data-disabled="true"] { opacity: 0.6; cursor: not-allowed; }
    .template-choice-title { font-size: 14px; font-weight: 700; color: var(--template-text); line-height: 1.25; overflow-wrap: anywhere; word-break: break-word; padding-right: 28px; }
    .template-choice-card[data-selected="true"] .template-choice-title { color: color-mix(in srgb, var(--template-primary) 80%, var(--template-text)); }
    .template-choice-footer { min-height: 18px; font-size: 12px; color: var(--template-muted-text); }
    .template-choice-footer[hidden] { display: none !important; }
    .template-choice-card .template-field-help { margin: 0; color: var(--template-muted-text); font-size: 11px; line-height: 1.35; }
    .template-choice-meta { display: flex; justify-content: space-between; align-items: center; gap: 10px; color: var(--template-muted-text); font-size: 13px; }
    .template-choice-grid[data-choice-density="compact"] { grid-template-columns: repeat(auto-fit, minmax(132px, max-content)); justify-content: start; }
    .template-choice-grid[data-choice-density="compact"] .template-choice-card { min-height: 0; padding: 10px 12px; gap: 2px; }
    .template-choice-grid[data-choice-density="compact"] .template-choice-title { font-size: 12px; line-height: 1.2; padding-right: 12px; }
    .template-choice-grid[data-choice-density="compact"] .template-choice-media,
    .template-choice-grid[data-choice-density="compact"] .template-field-help { display: none; }
    .template-toggle-row { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 16px; background: color-mix(in srgb, var(--template-surface) 96%, transparent); border: 1px solid color-mix(in srgb, var(--template-border) 60%, transparent); }
    .template-toggle-ui { width: 44px; height: 26px; border-radius: 999px; background: color-mix(in srgb, var(--template-border) 80%, transparent); position: relative; flex: none; }
    .template-toggle-ui::after { content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 999px; background: #fff; box-shadow: 0 1px 2px rgba(15,23,42,0.2); }
    .template-upload-box { display: grid; gap: 6px; place-items: center; padding: 16px 18px; border-radius: 18px; border: 1.5px dashed color-mix(in srgb, var(--template-border) 80%, transparent); background: color-mix(in srgb, var(--template-surface) 94%, transparent); text-align: center; }
    .template-upload-box[data-payment-proof='true'] { justify-items: stretch; }
    .template-upload-box[data-file-drag-active='true'] { border-color: var(--template-primary); background: color-mix(in srgb, var(--template-primary) 8%, var(--template-surface)); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--template-primary) 20%, transparent); }
    .template-upload-box[data-file-drop-state='selected'] { grid-template-columns: auto 1fr; align-items: center; justify-items: start; gap: 10px 14px; padding: 10px 14px; text-align: left; }
    .template-upload-box[data-file-drop-state='selected'] .template-upload-icon { width: 28px; height: 28px; border-radius: 10px; font-size: 14px; grid-row: 1 / span 2; }
    .template-upload-box[data-file-drop-state='selected'] .template-field-label { font-size: 12px; }
    .template-upload-box[data-file-drop-state='selected'] .template-field-help,
    .template-upload-box[data-file-drop-state='selected'] .template-upload-pills { display: none; }
    .template-upload-box[data-file-drop-state='selected'] .template-input { grid-column: 2; margin-top: 0; }
    .template-upload-box[data-payment-proof='true'][data-file-drop-state='selected'] { grid-template-columns: 1fr; justify-items: center; text-align: center; padding: 16px 18px; }
    .template-upload-box[data-payment-proof='true'][data-file-drop-state='selected'] .template-upload-icon { grid-row: auto; }
    .template-upload-box[data-payment-proof='true'][data-file-drop-state='selected'] .template-input { grid-column: auto; width: 100%; margin-top: 4px; }
    .template-upload-icon { width: 36px; height: 36px; border-radius: 12px; background: color-mix(in srgb, var(--template-border) 40%, transparent); color: var(--template-text); display: inline-flex; align-items: center; justify-content: center; font-size: 18px; }
    .template-upload-pills { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; }
    .template-upload-box .template-field-label { font-size: 13px; }
    .template-upload-box .template-field-help { max-width: 520px; }
    .template-upload-box .template-input { margin-top: 4px; padding-top: 10px; padding-bottom: 10px; }
    .template-payment-proof-summary { display: grid; gap: 10px; width: min(100%, 560px); max-width: 100%; margin: 0 auto 4px; align-self: center; justify-self: center; padding: 14px 16px; box-sizing: border-box; min-width: 0; border-radius: 16px; background: color-mix(in srgb, var(--template-surface) 98%, transparent); border: 1px solid color-mix(in srgb, var(--template-border) 70%, transparent); text-align: left; box-shadow: 0 10px 28px -24px rgba(15,23,42,0.22); overflow-x: clip; }
    .template-payment-proof-summary-row { display: flex; flex-wrap: wrap; gap: 8px; }
    .template-payment-provider-badge { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px 7px 8px; border-radius: 999px; border: 1px solid color-mix(in srgb, var(--template-border) 80%, transparent); background: color-mix(in srgb, var(--template-surface) 98%, transparent); color: var(--template-text); font-size: 12px; font-weight: 800; line-height: 1; box-shadow: 0 8px 20px -18px rgba(15,23,42,0.24); }
    .template-payment-provider-logo { display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 6px; border-radius: 999px; color: #fff; font-size: 10px; font-weight: 900; letter-spacing: 0; }
    .template-payment-provider-badge[data-payment-provider-badge='wave'] .template-payment-provider-logo,
    .template-payment-provider-logo[data-provider-logo='wave'] { background: linear-gradient(135deg, #1ec8fe 0%, #0b7cda 100%); color: #fff; }
    .template-payment-provider-badge[data-payment-provider-badge='orange-money'] .template-payment-provider-logo { background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' height='800' width='1200' viewBox='-22.35 -9.987675 193.7 59.92605'%3E%3Cg fill-rule='evenodd' fill='none'%3E%3Cpath fill='%23000' d='M31.2955 4.0185H8.458c-2.5608 0-4.6366 2.0759-4.6366 4.6367 0 2.5608 2.0758 4.6366 4.6366 4.6366h11.6438L1.3582 32.0356c-1.811 1.8104-1.811 4.7462 0 6.5571 1.8106 1.8107 4.7464 1.8107 6.557 0l18.7436-18.7436v11.6435c0 2.5608 2.0759 4.6367 4.6367 4.6367 2.5608 0 4.6366-2.0759 4.6366-4.6367V8.6552c0-2.5608-2.0758-4.6367-4.6366-4.6367'/%3E%3Cpath fill='%23FF7900' d='M44.642 35.9321h22.8375c2.5608 0 4.6367-2.0758 4.6367-4.6366 0-2.5608-2.0759-4.6367-4.6367-4.6367H55.8358L74.5794 7.9152c1.8109-1.8106 1.8109-4.7464 0-6.557-1.8107-1.811-4.7464-1.811-6.557 0L49.2786 20.1018V8.458c0-2.5608-2.0758-4.6366-4.6366-4.6366-2.5608 0-4.6367 2.0758-4.6367 4.6366v22.8375c0 2.5608 2.0759 4.6366 4.6367 4.6366'/%3E%3Cpath fill='%23000' d='M92.5 15.4802c2.6111 0 3.5927-2.246 3.5927-4.4733 0-2.3396-.9816-4.5857-3.5927-4.5857-2.611 0-3.5926 2.246-3.5926 4.5857 0 2.2272.9816 4.4733 3.5926 4.4733m0-11.5295c4.0927 0 6.5 3.0882 6.5 7.0562 0 3.8556-2.4073 6.9438-6.5 6.9438-4.0926 0-6.5-3.0882-6.5-6.9438 0-3.968 2.4074-7.0562 6.5-7.0562m7.5 4.2637h2.4323v1.808h.036c.4686-1.2241 1.7298-2.0717 2.9731-2.0717.1803 0 .3964.0379.5586.0944v2.4857c-.2344-.0565-.6127-.0941-.919-.0941-1.8737 0-2.5224 1.4123-2.5224 3.1262v4.3878H100zm13.8256 4.8372c-.4761.3853-1.4685.4037-2.3413.5505-.873.165-1.6666.4403-1.6666 1.3945 0 .9725.8132 1.211 1.7261 1.211 2.2023 0 2.2818-1.6147 2.2818-2.1834zm-6.5081-1.9265c.159-2.4403 2.5202-3.1744 4.8213-3.1744 2.0436 0 4.504.4219 4.504 2.6972v4.936c0 .8622.0991 1.7244.3572 2.1099h-2.857c-.0994-.2936-.1786-.6055-.1986-.9173-.8927.8622-2.202 1.1742-3.4522 1.1742-1.9442 0-3.4922-.899-3.4922-2.844 0-2.1468 1.7464-2.6606 3.4922-2.8809 1.7261-.2385 3.3334-.1834 3.3334-1.2477 0-1.1191-.8334-1.2843-1.8256-1.2843-1.0713 0-1.7654.4037-1.8649 1.4313zM118 8.2144h2.505v1.356h.056c.6679-1.0923 1.8186-1.6197 2.9317-1.6197 2.8022 0 3.5073 1.6008 3.5073 4.0114v5.9886h-2.635v-5.4989c0-1.6008-.464-2.3918-1.6886-2.3918-1.4289 0-2.0413.81-2.0413 2.7872v5.1035h-2.635zm17.3683 4.8589c0-1.6176-.5652-3.062-2.3585-3.062-1.5595 0-2.2417 1.3482-2.2417 2.8308 0 1.425.5459 2.9849 2.2417 2.9849 1.5792 0 2.3585-1.3289 2.3585-2.7537zM138 17.5408c0 1.6369-.585 4.4099-5.2242 4.4099-1.9881 0-4.3077-.9245-4.4443-3.1967h2.7484c.2534 1.0205 1.0918 1.3673 2.0663 1.3673 1.5402 0 2.2417-1.0399 2.2221-2.465V16.347h-.0389c-.604 1.0398-1.8129 1.5407-3.0214 1.5407-3.0214 0-4.308-2.2725-4.308-5.007 0-2.5806 1.501-4.9299 4.3276-4.9299 1.3255 0 2.339.443 3.0018 1.5985h.039v-1.329H138zm8.2318-5.6085c-.252-1.3212-.8531-2.0182-2.191-2.0182-1.745 0-2.2489 1.2843-2.2879 2.0182zm-4.4789 1.6513c.0777 1.6514.9308 2.4037 2.4625 2.4037 1.105 0 1.997-.6424 2.1712-1.2295h2.4238c-.7757 2.2388-2.4238 3.1929-4.692 3.1929-3.1601 0-5.1184-2.055-5.1184-4.991 0-2.8438 2.0746-5.009 5.1185-5.009 3.4122 0 5.0602 2.7154 4.8662 5.633zM92.5685 31.5784h.039l3.0817-9.6277H100v14h-2.8673V26.029h-.039l-3.4136 9.9217H91.32l-3.4134-9.8237h-.0392v9.8237H85v-14h4.311zm14.4221 2.409c1.813 0 2.3552-1.523 2.3552-3.0276 0-1.523-.5422-3.0458-2.3552-3.0458-1.7944 0-2.3364 1.5228-2.3364 3.0458 0 1.5046.542 3.0276 2.3364 3.0276m0-8.0367c3.0467 0 5.0094 1.9818 5.0094 5.0091 0 3.0091-1.9627 4.9909-5.0094 4.9909-3.0278 0-4.9906-1.9818-4.9906-4.9909 0-3.0273 1.9628-5.0091 4.9906-5.0091m6.0094.2637h2.505v1.356h.056c.6679-1.092 1.8186-1.6197 2.9317-1.6197 2.8022 0 3.5073 1.6008 3.5073 4.0115v5.9885h-2.6353v-5.499c0-1.6007-.4637-2.3915-1.6883-2.3915-1.4291 0-2.0413.8097-2.0413 2.787v5.1035h-2.635zm17.2318 3.718c-.252-1.3212-.8531-2.0184-2.191-2.0184-1.7447 0-2.2487 1.2846-2.2876 2.0184zm-4.4786 1.6514c.0774 1.651.9305 2.4036 2.4622 2.4036 1.105 0 1.997-.6424 2.1715-1.2295h2.4237c-.7757 2.2384-2.4237 3.1928-4.6921 3.1928-3.1602 0-5.1185-2.0553-5.1185-4.9911 0-2.8435 2.0746-5.0089 5.1185-5.0089 3.4122 0 5.0602 2.7156 4.8662 5.633zm13.1791 5.1908c-.5839 1.5434-1.5067 2.1761-3.3523 2.1761-.5461 0-1.0924-.0374-1.6385-.0932v-2.1759c.5085.0372 1.0359.1117 1.563.093.9228-.093 1.2241-1.0414.9228-1.8039L133 25.9507h2.8626l2.2034 6.5837h.0376l2.1283-6.5837H143z'/%3E%3C/g%3E%3C/svg%3E") center/90% no-repeat; min-width: 60px; height: 22px; border-radius: 6px; font-size: 0; }
    .template-payment-provider-badge[data-payment-provider-badge='free-money'] .template-payment-provider-logo { background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); }
    .template-payment-provider-badge[data-payment-provider-badge='bank-transfer'] .template-payment-provider-logo { background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Crect width='200' height='200' rx='40' fill='%231A3A6B'/%3E%3Cpolygon points='100,30 180,72 20,72' fill='white'/%3E%3Crect x='38' y='78' width='18' height='70' rx='4' fill='white'/%3E%3Crect x='72' y='78' width='18' height='70' rx='4' fill='white'/%3E%3Crect x='110' y='78' width='18' height='70' rx='4' fill='white'/%3E%3Crect x='144' y='78' width='18' height='70' rx='4' fill='white'/%3E%3Crect x='20' y='154' width='160' height='16' rx='6' fill='white'/%3E%3C/svg%3E") center/contain no-repeat; font-size: 0; }
    .template-payment-provider-badge[data-payment-provider-badge='manual'] .template-payment-provider-logo { background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); }
    .template-payment-proof-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px 14px; }
    .template-payment-proof-summary-item { display: grid; gap: 2px; min-width: 0; }
    .template-payment-proof-summary-item strong { font-size: 13px; color: var(--template-text); line-height: 1.35; overflow-wrap: anywhere; word-break: break-word; }
    .template-payment-proof-phone { color: inherit; text-decoration: none; }
    .template-payment-proof-phone:hover { text-decoration: underline; }
    @media (pointer: fine) { .template-payment-proof-phone { pointer-events: none; cursor: text; } }
    .template-payment-proof-summary-label { font-size: 11px; font-weight: 700; color: var(--template-muted-text); text-transform: uppercase; }
    .template-payment-proof-summary-note { font-size: 12px; color: var(--template-text); line-height: 1.45; }
    .template-payment-proof-qr { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 10px 0 2px; }
    .template-payment-proof-qr-img { width: 160px; height: 160px; object-fit: contain; border-radius: 12px; border: 1px solid color-mix(in srgb, var(--template-border) 60%, transparent); background: #fff; }
    .template-payment-proof-qr-label { font-size: 11px; font-weight: 700; color: var(--template-muted-text); text-transform: uppercase; letter-spacing: 0.04em; }
    .template-payment-proof-checklist { display: grid; gap: 4px; margin: 0; padding-left: 18px; color: var(--template-muted-text); font-size: 12px; line-height: 1.45; }
    .template-payment-proof-checklist li { margin: 0; }
    .template-payment-proof-selector { display: flex; flex-wrap: wrap; gap: 8px; }
    .template-payment-proof-selector-pill { display: inline-flex; align-items: center; gap: 7px; padding: 7px 13px 7px 9px; border-radius: 999px; border: 1.5px solid color-mix(in srgb, var(--template-border) 70%, transparent); background: var(--template-surface); color: var(--template-muted-text); font-size: 12px; font-weight: 800; cursor: pointer; line-height: 1; transition: border-color 0.15s, color 0.15s, background 0.15s; }
    .template-payment-proof-selector-pill:hover { border-color: color-mix(in srgb, var(--template-primary) 50%, transparent); color: var(--template-text); }
    .template-payment-proof-selector-pill.is-active { border-color: var(--template-primary); color: var(--template-primary); background: color-mix(in srgb, var(--template-primary) 7%, var(--template-surface)); }
    .template-payment-proof-provider-block { display: grid; gap: 10px; }
    .template-payment-proof-bank-block { display: grid; gap: 10px; }
    .template-payment-proof-copy-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; min-width: 0; }
    .template-payment-proof-bank-value { font-size: 13px; font-weight: 700; color: var(--template-text); font-variant-numeric: tabular-nums; letter-spacing: 0.02em; overflow-wrap: anywhere; word-break: break-all; }
    .template-payment-proof-reference-value { font-family: monospace; letter-spacing: 0.08em; }
    .template-payment-proof-copy-btn { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 999px; border: 1px solid color-mix(in srgb, var(--template-border) 80%, transparent); background: var(--template-surface); color: var(--template-muted-text); font-size: 11px; font-weight: 700; cursor: pointer; line-height: 1.7; transition: opacity 0.15s; white-space: nowrap; }
    .template-payment-proof-copy-btn:hover:not(:disabled) { opacity: 0.75; }
    .template-payment-proof-copy-btn:disabled { opacity: 0.45; cursor: default; }
    .template-upload-selection { display: grid; gap: 6px; padding: 12px 14px; border-radius: 16px; border: 1px solid color-mix(in srgb, var(--template-border) 60%, transparent); background: var(--template-surface); }
    .template-upload-selection[data-upload-selection-state='selected'] { gap: 4px; padding: 10px 12px; }
    .template-upload-selection[data-upload-selection-state='selected'] [data-upload-selection-message] { display: none !important; }
    .template-upload-selection-row { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
    [data-document-scan-controls] { margin-bottom: 10px; }
    .template-upload-selection-title { font-size: 13px; font-weight: 700; color: var(--template-text); }
    .template-upload-selection[data-upload-selection-state='selected'] [data-upload-selection-kind] { display: none; }
    .template-upload-selection [data-upload-file-list] { display: grid; gap: 8px; }
    .template-upload-selection [data-upload-file-row] { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 12px; border-radius: 12px; border: 1px solid color-mix(in srgb, var(--template-border) 50%, transparent); background: color-mix(in srgb, var(--template-surface) 96%, transparent); }
    .template-upload-selection [data-upload-file-details] { min-width: 0; display: grid; gap: 2px; }
    .template-upload-selection [data-upload-file-name] { font-size: 14px; font-weight: 600; color: var(--template-text); line-height: 1.25; word-break: break-word; }
    .template-upload-selection [data-upload-file-size] { font-size: 12px; color: var(--template-muted-text); }
    .template-upload-selection [data-remove-file-index] { appearance: none; display: inline-flex; align-items: center; justify-content: center; width: 36px; min-width: 36px; height: 36px; border: 1px solid color-mix(in srgb, var(--template-border) 80%, transparent); border-radius: 999px; background: color-mix(in srgb, var(--template-surface) 90%, transparent); color: var(--template-text); font: inherit; font-size: 20px; font-weight: 700; line-height: 1; padding: 0; cursor: pointer; box-shadow: 0 6px 16px -12px rgba(0,0,0,0.15); }
    .template-upload-selection [data-remove-file-index]:hover { background: color-mix(in srgb, var(--template-text) 10%, transparent); border-color: color-mix(in srgb, var(--template-text) 24%, transparent); }
    [data-upload-selection-body] [data-upload-status] { display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; padding: 6px 10px; border-radius: 999px; border: 1px solid rgba(15,23,42,0.08); background: rgba(248,250,252,0.96); color: #334155; font-size: 12px; font-weight: 700; line-height: 1; }
    [data-upload-selection-body] [data-upload-status][data-state='complete'] { border-color: rgba(22,163,74,0.18); background: rgba(240,253,244,0.96); color: #166534; }
    [data-upload-selection-body] [data-upload-status][data-state='uploading'] { border-color: rgba(37,99,235,0.16); background: rgba(239,246,255,0.96); color: #1d4ed8; }
    [data-upload-selection-body] [data-upload-status][data-state='error'] { border-color: rgba(220,38,38,0.18); background: rgba(254,242,242,0.96); color: #b91c1c; }
    .template-content-card { display: grid; gap: 10px; padding: 16px; border-radius: 18px; background: color-mix(in srgb, var(--template-surface) 96%, transparent); border: 1px solid color-mix(in srgb, var(--template-border) 60%, transparent); }
    .template-media-frame { width: 100%; aspect-ratio: 16 / 9; border-radius: 16px; overflow: hidden; background: rgba(226,232,240,0.75); display: grid; place-items: center; }
    .template-media-frame img { width: 100%; height: 100%; object-fit: cover; object-position: center; display: block; }
    .template-link-button { display: inline-flex; align-items: center; justify-content: center; padding: 12px 16px; border-radius: 999px; background: color-mix(in srgb, var(--template-text) 96%, transparent); color: var(--template-surface); text-decoration: none; font-weight: 700; }
    .template-html-block { padding: 14px 16px; border-radius: 16px; background: var(--template-surface); border: 1px solid color-mix(in srgb, var(--template-border) 60%, transparent); color: var(--template-text); }
    .template-image-gallery-shell { display: grid; gap: 16px; padding: 16px; border-radius: 18px; background: color-mix(in srgb, var(--template-surface) 96%, transparent); border: 1px solid color-mix(in srgb, var(--template-border) 60%, transparent); }
    .template-image-gallery-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .template-image-gallery-header-copy { display: grid; gap: 4px; }
    .template-image-gallery-title { font-size: 14px; font-weight: 800; color: var(--template-text); }
    .template-image-gallery-stats { display: flex; align-items: center; gap: 8px; }
    .template-image-gallery-count { display: inline-flex; align-items: center; padding: 7px 12px; border-radius: 999px; background: var(--template-surface); border: 1px solid color-mix(in srgb, var(--template-border) 60%, transparent); color: var(--template-text); font-size: 12px; font-weight: 700; white-space: nowrap; }
    .template-gallery-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
    .template-gallery-card { position: relative; display: grid; gap: 8px; align-content: start; padding: 10px; border-radius: 18px; background: var(--template-surface); border: 1px solid color-mix(in srgb, var(--template-border) 60%, transparent); box-shadow: 0 14px 30px -28px rgba(0,0,0,0.15); overflow: hidden; }
    .template-gallery-media { width: 100%; aspect-ratio: 4 / 3; border-radius: 14px; overflow: hidden; background: color-mix(in srgb, var(--template-border) 40%, transparent); }
    .template-gallery-media img, .template-gallery-card [data-image-preview] { width: 100%; height: 100% !important; object-fit: cover; object-position: center; display: block; }
    .template-gallery-card .template-choice-title { font-size: 15px; font-weight: 700; color: var(--template-text); line-height: 1.3; }
    .template-gallery-caption { display: flex; justify-content: flex-start; align-items: center; gap: 10px; color: var(--template-muted-text); font-size: 12px; }
    .template-gallery-card [data-image-gallery-state] { display: none !important; }
    .template-gallery-card [data-image-gallery-badge] { display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 999px; background: color-mix(in srgb, var(--template-surface) 96%, transparent); color: var(--template-muted-text); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
    .template-gallery-card:has([data-image-gallery-action="toggle"][aria-label^="Remove "]) [data-image-gallery-badge] { font-size: 0; padding: 3px 7px; background: #059669; border: none; }
    .template-gallery-card:has([data-image-gallery-action="toggle"][aria-label^="Remove "]) [data-image-gallery-badge]::after { content: "✓"; font-size: 11px; font-weight: 800; color: #fff; }
    .template-gallery-card [data-image-gallery-control-row] { position: absolute; top: 18px; right: 18px; margin: 0 !important; z-index: 2; }
    .template-gallery-card [data-image-gallery-action="toggle"] { width: auto !important; min-width: 0 !important; height: 34px !important; padding: 0 12px !important; border-radius: 999px !important; border: 1px solid color-mix(in srgb, var(--template-border) 60%, transparent) !important; background: color-mix(in srgb, var(--template-surface) 92%, transparent) !important; color: transparent !important; font-size: 0 !important; font-weight: 700 !important; box-shadow: 0 10px 24px -18px rgba(0,0,0,0.15) !important; backdrop-filter: blur(8px); }
    .template-gallery-card [data-image-gallery-action="toggle"]::before { content: "+"; color: var(--template-text); font-size: 16px; line-height: 1; font-weight: 700; }
    .template-gallery-card [data-image-gallery-action="toggle"][aria-label^="Remove "] { background: color-mix(in srgb, var(--template-text) 92%, transparent) !important; border-color: transparent !important; }
    .template-gallery-card [data-image-gallery-action="toggle"][aria-label^="Remove "]::before { content: "✓"; color: #f8fafc; font-size: 14px; }
    .template-gallery-card [data-image-gallery-action="toggle"][aria-label^="Select "]:disabled { opacity: 0.45; }
    .template-image-gallery-selection { display: none; }
    .template-image-gallery-selection-heading { font-size: 13px; font-weight: 800; color: var(--template-text); }
    .template-image-gallery-selection-body { display: none; }
    .template-image-gallery-empty { display: none; }
    .template-image-gallery-empty-title { font-size: 13px; font-weight: 700; color: var(--template-text); }
    .template-quiz-selection, .template-quiz-selection-heading, .template-quiz-selection-body, .template-quiz-selection-empty, .template-quiz-selection-empty-title, .template-quiz-selection-list { display: none !important; }
    .template-image-gallery-list { display: none; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; }
    .template-image-gallery-list [data-image-gallery-item] { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border-radius: 14px; border: 1px solid color-mix(in srgb, var(--template-border) 60%, transparent); background: var(--template-surface); }
    .template-image-gallery-list [data-image-gallery-name-wrap] { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .template-image-gallery-list [data-image-gallery-thumb] { width: 44px; height: 44px; border-radius: 10px; object-fit: cover; flex: none; }
    .template-image-gallery-list [data-image-gallery-name] { font-size: 13px; font-weight: 600; color: var(--template-text); }
    .template-image-gallery-list [data-image-gallery-action="remove"] { width: 30px; min-width: 30px; height: 30px; border-radius: 999px; border: 1px solid color-mix(in srgb, var(--template-border) 80%, transparent); background: color-mix(in srgb, var(--template-surface) 96%, transparent); color: var(--template-text); }
    @media (max-width: 720px) {
      .page-shell { padding: 14px 10px; overflow: auto; }
      #xpressui-root { padding: 18px 10px; }
      .form-frame { padding: 18px; border-radius: max(calc(var(--template-card-radius) - 8px), 18px); }
      .hosted-link-presentation { padding: 16px; }
      .hosted-link-title { font-size: 21px; }
      .template-form-title { font-size: 28px; }
      .template-section { padding: 16px; }
      .template-section-label { font-size: 21px; }
      .template-runtime-shell[data-field-columns='2'] .template-fields,
      .template-runtime-shell[data-field-columns='3'] .template-fields { grid-template-columns: 1fr; }
      .template-runtime-shell[data-label-position='inline'] .template-field { display: flex; flex-direction: column; gap: 8px; }
      .template-runtime-shell[data-label-position='inline'] .template-field-label-row { padding-top: 0; }
      .template-gallery-grid { grid-template-columns: 1fr; }
      .template-image-gallery-list { grid-template-columns: 1fr; }
    }
    .template-submit-feedback { display: grid; gap: 6px; padding: 14px 16px; border-radius: 16px; border: 1px solid color-mix(in srgb, var(--template-border) 72%, transparent); background: color-mix(in srgb, var(--template-surface) 94%, transparent); }
    .template-submit-feedback[data-submit-feedback-state='success'] { background: color-mix(in srgb, #22c55e 8%, var(--template-surface)); border-color: color-mix(in srgb, #22c55e 20%, transparent); }
    .template-submit-feedback[data-submit-feedback-state='error'] { background: color-mix(in srgb, #ef4444 8%, var(--template-surface)); border-color: color-mix(in srgb, #ef4444 20%, transparent); }
    .template-submit-feedback[data-submit-feedback-state='loading'] { background: color-mix(in srgb, #3b82f6 8%, var(--template-surface)); border-color: color-mix(in srgb, #3b82f6 20%, transparent); }
    .template-submit-feedback-title { font-size: 13px; font-weight: 800; color: var(--template-text); }
    .template-submit-feedback-message { font-size: 13px; color: var(--template-muted-text); line-height: 1.45; }
    .xpressui-booking-btn { display: none; margin-top: 6px; padding: 10px 20px; background: var(--template-primary); color: #fff !important; text-decoration: none !important; border-radius: var(--template-button-radius); font-size: 13px; font-weight: 700; width: auto !important; align-self: start; justify-self: start; box-sizing: border-box; }
    [data-submit-feedback][data-submit-feedback-state="success"] .xpressui-booking-btn { display: inline-block !important; }
    .template-checkout-summary { display: grid; gap: 14px; margin-top: 18px; padding: 16px; border-radius: 18px; border: 1px solid color-mix(in srgb, var(--template-border) 68%, transparent); background: color-mix(in srgb, var(--template-surface) 96%, transparent); box-shadow: 0 18px 38px -34px rgba(15,23,42,0.28); }
    .template-checkout-summary[hidden] { display: none !important; }
    .template-checkout-summary-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
    .template-checkout-summary-kicker { color: var(--template-muted-text); font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; }
    .template-checkout-summary-title { margin: 2px 0 0; color: var(--template-text); font-size: 18px; line-height: 1.25; font-weight: 850; }
    .template-checkout-summary-total-pill { display: inline-flex; align-items: baseline; gap: 8px; padding: 8px 12px; border-radius: 999px; background: color-mix(in srgb, var(--template-primary) 10%, var(--template-surface)); border: 1px solid color-mix(in srgb, var(--template-primary) 28%, transparent); color: var(--template-text); font-size: 12px; font-weight: 700; }
    .template-checkout-summary-total-pill strong { font-size: 14px; font-weight: 850; }
    .template-checkout-summary-body { display: grid; gap: 12px; }
    .template-checkout-summary-empty { margin: 0; padding: 12px; border-radius: 14px; background: color-mix(in srgb, var(--template-border) 22%, transparent); color: var(--template-muted-text); font-size: 13px; line-height: 1.45; }
    .template-checkout-summary-groups { display: grid; gap: 12px; }
    .template-checkout-summary-group { display: grid; gap: 8px; }
    .template-checkout-summary-group-title { margin: 0; color: var(--template-muted-text); font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; }
    .template-checkout-summary-items { display: grid; gap: 8px; }
    .template-checkout-summary-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 10px 12px; border-radius: 14px; border: 1px solid color-mix(in srgb, var(--template-border) 56%, transparent); background: var(--template-surface); }
    .template-checkout-summary-item-main { min-width: 0; display: grid; gap: 3px; }
    .template-checkout-summary-item-name { color: var(--template-text); font-size: 13px; font-weight: 800; line-height: 1.35; overflow-wrap: anywhere; }
    .template-checkout-summary-item-meta { color: var(--template-muted-text); font-size: 12px; line-height: 1.35; }
    .template-checkout-summary-item-subtotal { color: var(--template-text); font-size: 13px; font-weight: 850; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .template-checkout-summary-grand-total { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 12px; border-top: 1px solid color-mix(in srgb, var(--template-border) 60%, transparent); color: var(--template-text); font-size: 15px; font-weight: 850; }
    #xpressui-root button.template-field-pill { appearance: none; font-family: var(--template-font-family); font-size: 12px; font-weight: 700; cursor: pointer; transition: background 0.15s, border-color 0.15s, color 0.15s; }
    #xpressui-root button.template-field-pill[data-document-scan-active="true"] { background: var(--template-primary); border-color: var(--template-primary); color: #f8fafc; }
    .template-submit-row { display: flex; justify-content: flex-end; padding-top: 18px; margin-top: 2px; border-top: 1px solid color-mix(in srgb, var(--template-border) 60%, transparent); }
    #xpressui-root .template-submit-btn { border: none; border-radius: var(--template-button-radius); background: var(--template-primary); color: #f8fafc; font-family: var(--template-font-family); font-size: 14px; font-weight: 700; padding: 14px 20px; min-width: 140px; }
    .template-step-progress-track { background: color-mix(in srgb, var(--template-border) 40%, transparent); border-radius: 999px; height: 4px; overflow: hidden; margin-top: 4px; }
    .template-step-progress-bar { height: 100%; background: linear-gradient(90deg, var(--template-primary) 0%, color-mix(in srgb, var(--template-primary) 76%, var(--template-text)) 100%); border-radius: 999px; }
    .template-step-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 18px 0 0; }
    .template-step-actions-leading, .template-step-actions-trailing { display: flex; align-items: center; gap: 12px; }
    #xpressui-root .template-step-actions [data-step-action] { appearance: none; border: 1px solid color-mix(in srgb, var(--template-border) 80%, transparent); border-radius: var(--template-button-radius); font-family: var(--template-font-family); font-size: 14px; font-weight: 700; line-height: 1; min-width: 132px; padding: 14px 20px; box-shadow: none; cursor: pointer; transition: transform 160ms ease, background 160ms ease, color 160ms ease, border-color 160ms ease; }
    #xpressui-root .template-step-actions [data-step-action]:hover { transform: translateY(-1px); }
    #xpressui-root .template-step-actions [data-step-action="back"] { background: var(--template-surface); color: var(--template-text); }
    #xpressui-root .template-step-actions [data-step-action="next"] { background: var(--template-primary); border-color: var(--template-primary); color: #f8fafc; }
    #xpressui-root .template-step-actions [data-step-action]:disabled { opacity: 0.54; cursor: not-allowed; transform: none; }

    @keyframes xpressui-step-in {
      0% { opacity: 0; transform: translateY(16px) scale(0.98); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    [data-template-zone="section"] { animation: xpressui-step-in 400ms cubic-bezier(0.2, 0.8, 0.2, 1) both; }
    @keyframes xpressui-spin { to { transform: rotate(360deg); } }
    .template-submit-overlay { display: none; position: absolute; inset: 0; z-index: 20; border-radius: var(--template-card-radius); background: color-mix(in srgb, var(--template-surface) 82%, transparent); backdrop-filter: blur(4px); place-items: center; flex-direction: column; gap: 14px; }
    .template-submit-overlay[data-active] { display: grid; }
    .template-submit-overlay-spinner { width: 36px; height: 36px; border: 3px solid color-mix(in srgb, var(--template-primary) 22%, transparent); border-top-color: var(--template-primary); border-radius: 50%; animation: xpressui-spin 0.7s linear infinite; }
    .template-submit-overlay-label { font-size: 13px; font-weight: 700; color: var(--template-muted-text); }
    #xpressui-preview-runtime-banner { font-size: 12px; color: #44515f; background: #eef4f8; padding: 8px 12px; border-bottom: 1px solid #d8e2ea; }
    #xpressui-preview-selection-banner { display: flex; gap: 8px; flex-wrap: wrap; font-size: 12px; color: #1f2937; background: #f8fafc; padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
    .xpressui-preview-selection-chip { display: inline-flex; align-items: center; border-radius: 999px; padding: 2px 10px; background: #dbeafe; color: #1d4ed8; font-weight: 600; }
    .xpressui-preview-selection-chip-warning { background: #fef3c7; color: #92400e; }
    [data-xpressui-spinner] { display: inline-block; width: 0.9em; height: 0.9em; border: 2px solid currentColor; border-top-color: transparent; border-radius: 50%; animation: xpressui-spin 0.65s linear infinite; vertical-align: middle; margin-right: 0.45em; flex-shrink: 0; }
    [data-xpressui-spinner][hidden] { display: none !important; }
    .template-step-progress-container { display: grid; gap: 8px; margin: 16px 0; padding: 14px 16px; border: 1px solid rgba(148,163,184,0.2); border-radius: 18px; background: rgba(248,250,252,0.92); box-shadow: 0 16px 36px -30px rgba(15,23,42,0.18); }
    [data-form-step-progress] { font-size: 14px; font-weight: 600; color: rgb(15,23,42); }
    [data-form-step-summary] { font-size: 12px; opacity: 0.8; color: rgb(71,85,105); }
    dialog:not([open]) { display: none; }
    .xpui-capture-dialog { border: none; border-radius: 12px; padding: 0; max-width: 90vw; box-shadow: 0 8px 32px rgba(0,0,0,0.18); }
    .xpui-capture-dialog::backdrop { background: rgba(0,0,0,0.55); }
    .xpui-capture-panel { display: flex; flex-direction: column; align-items: center; gap: 16px; padding: 28px 32px; text-align: center; max-width: 320px; }
    .xpui-capture-title { font-size: 15px; font-weight: 600; color: #0f172a; }
    .xpui-capture-qr { width: 200px; height: 200px; border-radius: 8px; border: 1px solid #e2e8f0; display: block; }
    .xpui-capture-qr[hidden] { display: none !important; }
    .xpui-capture-status { font-size: 13px; color: #64748b; }
    #xpressui-root [data-mobile-capture-btn] { cursor: pointer; background: color-mix(in srgb, var(--template-primary) 10%, var(--template-surface)); border-color: color-mix(in srgb, var(--template-primary) 30%, transparent); color: var(--template-primary); font-size: 12px; transition: background 0.15s, border-color 0.15s, transform 0.15s; }
    #xpressui-root [data-mobile-capture-btn]:hover { background: color-mix(in srgb, var(--template-primary) 16%, var(--template-surface)); border-color: color-mix(in srgb, var(--template-primary) 50%, transparent); transform: translateY(-1px); }
    .xpui-cart-trigger { position: fixed; right: 20px; bottom: 20px; z-index: 10001; display: inline-flex; align-items: center; justify-content: center; width: 50px; height: 50px; border-radius: 999px; border: none; background: var(--template-primary); color: #f8fafc; cursor: pointer; padding: 0; box-shadow: 0 4px 16px -4px rgba(15,23,42,0.28); }
    .xpui-cart-trigger[hidden] { display: none !important; }
    .xpui-cart-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.26); z-index: 10002; display: none; justify-content: flex-end; opacity: 0; visibility: hidden; transition: opacity 180ms ease; }
    .xpui-cart-overlay[data-state="open"] { display: flex; visibility: visible; }
    .xpui-cart-overlay[data-state="open"] { opacity: 1; }
    .xpui-cart-panel { width: min(340px, 88vw); height: 100%; background: rgba(255,255,255,0.98); border-left: 1px solid rgba(15,23,42,0.08); padding: 14px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; transform: translateX(100%); transition: transform 180ms ease; box-shadow: -24px 0 48px -36px rgba(15,23,42,0.28); }
    .xpui-cart-overlay[data-state="open"] .xpui-cart-panel { transform: translateX(0); }
    .template-product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; margin-bottom: 14px; align-items: stretch; }
    .template-product-grid:has(.template-product-card[data-has-image="false"]):not(:has(.template-product-card[data-has-image="true"])) { grid-template-columns: repeat(2, minmax(280px, 1fr)); gap: 26px 34px; }
    .template-product-card { display: grid; grid-template-rows: auto minmax(38px, auto) minmax(0, auto) auto auto; gap: 8px; min-width: 0; min-height: 258px; padding: 12px; justify-items: stretch; border-radius: 14px; border: 1px solid rgba(148,163,184,0.34); background: rgba(248,250,252,0.9); cursor: default; transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease, background 160ms ease; }
    .template-product-card:hover { border-color: rgba(59,130,246,0.55); box-shadow: 0 10px 24px -20px rgba(15,23,42,0.48); transform: translateY(-1px); }
    .template-product-card[data-in-cart="true"] { border-color: rgb(59 130 246); box-shadow: 0 0 0 2px rgba(59,130,246,0.14); background: rgba(239,246,255,0.82); }
    .template-product-card[data-has-image="false"] { grid-template-columns: minmax(0, 1fr) minmax(142px, auto); grid-template-rows: auto auto minmax(0, auto); grid-template-areas: "title actions" "price actions" "description actions"; align-items: center; min-height: 108px; padding: 16px 18px 13px; border-radius: 0; border-color: color-mix(in srgb, var(--template-text) 88%, var(--template-border)); background: color-mix(in srgb, var(--template-surface) 98%, white); box-shadow: none; }
    .template-product-card[data-has-image="false"]:hover { border-color: var(--template-text); box-shadow: none; transform: none; }
    .template-product-card[data-has-image="false"][data-in-cart="true"] { border-color: var(--template-primary); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--template-primary) 45%, transparent); background: color-mix(in srgb, var(--template-primary) 4%, var(--template-surface)); }
    .template-product-media { position: relative; width: 100%; aspect-ratio: 4/3; min-height: 112px; max-height: 142px; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: rgba(226,232,240,0.72); overflow: hidden; cursor: pointer; border: 1px solid rgba(148,163,184,0.22); }
    .template-product-media img { width: 100%; height: 100%; object-fit: cover; object-position: center; }
    .xpui-product-overlay { position: absolute; left: 0; right: 0; bottom: 0; display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 8px; background: linear-gradient(180deg, rgba(15,23,42,0) 0%, rgba(15,23,42,0.76) 100%); }
    .xpui-product-overlay[hidden] { display: none !important; }
    .xpui-product-qty-pill { display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 999px; background: rgba(255,255,255,0.16); color: #fff; font-size: 11px; font-weight: 700; }
    .xpui-product-subtotal-pill { display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; background: rgba(15,23,42,0.42); color: #fff; font-size: 11px; font-weight: 700; }
    .template-product-title { margin-top: 2px; min-width: 0; width: 100%; min-height: 38px; font-size: 13px; line-height: 1.35; font-weight: 700; color: #111827; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; text-align: left; cursor: pointer; overflow-wrap: anywhere; }
    .template-product-card[data-has-image="false"] .template-product-title { grid-area: title; min-height: 0; margin: 0; font-size: 18px; line-height: 1.18; font-weight: 500; color: var(--template-text); -webkit-line-clamp: 1; cursor: default; }
    .template-product-description { width: 100%; color: #64748b; font-size: 12px; line-height: 1.35; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .template-product-card[data-has-image="false"] .template-product-description { grid-area: description; margin-top: -4px; color: var(--template-muted-text); font-size: 13px; font-style: italic; -webkit-line-clamp: 1; }
    .template-product-meta { margin-top: 0; font-size: 12px; width: 100%; display: flex; align-items: baseline; justify-content: flex-start; gap: 6px; text-align: left; }
    .template-product-card[data-has-image="false"] .template-product-meta { grid-area: price; align-self: end; }
    .template-product-price { font-size: 14px; font-weight: 800; color: #0f172a; overflow-wrap: anywhere; }
    .template-product-card[data-has-image="false"] .template-product-price { font-size: 16px; color: var(--template-text); }
    .template-product-regular-price { margin-right: 5px; color: #94a3b8; font-size: 12px; font-weight: 600; text-decoration: line-through; }
    .xpui-product-controls { display: grid; grid-template-columns: 30px minmax(28px, 1fr) 30px; align-items: center; justify-content: center; flex-shrink: 0; column-gap: 7px; width: 100%; margin: 2px 0 0; padding: 5px; border-radius: 999px; background: #eef2f7; align-self: end; }
    .template-product-card[data-has-image="false"] .xpui-product-controls { grid-area: actions; align-self: end; justify-self: end; width: 142px; margin: 0 0 1px; background: transparent; padding: 0; grid-template-columns: 34px 52px 34px; column-gap: 10px; }
    .xpui-product-action-btn { width: 30px; min-width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; font-size: 13px; font-weight: 800; line-height: 1; box-shadow: none; border: 1px solid rgba(148,163,184,0.42); background: #f8fafc; color: #0f172a; }
    .template-product-card[data-has-image="false"] .xpui-product-action-btn { width: 34px; min-width: 34px; height: 34px; border: 0; border-radius: 0; background: transparent; color: var(--template-text); font-size: 25px; font-weight: 400; box-shadow: none; }
    .xpui-product-action-btn--add { background: #0f172a; color: #fff; border-color: transparent; box-shadow: 0 6px 14px -10px rgba(15,23,42,0.9); }
    .template-product-card[data-has-image="false"] .xpui-product-action-btn--add { background: transparent; color: var(--template-text); box-shadow: none; }
    .xpui-product-action-btn:disabled { opacity: 0.38; cursor: default; }
    .xpui-product-qty-label { min-width: 18px; text-align: center; font-size: 13px; font-weight: 800; font-variant-numeric: tabular-nums; color: var(--template-text); }
    .xpui-product-qty-label input { width: 100%; min-width: 0; height: 30px; padding: 0 4px; border: 1px solid var(--template-border); border-radius: min(6px, var(--template-input-radius)); background: color-mix(in srgb, var(--template-surface) 96%, white); color: inherit; font: inherit; font-variant-numeric: inherit; text-align: center; outline: none; appearance: textfield; box-shadow: inset 0 1px 1px color-mix(in srgb, var(--template-text) 5%, transparent); }
    .xpui-product-qty-label input:hover { border-color: color-mix(in srgb, var(--template-text) 48%, var(--template-border)); }
    .xpui-product-qty-label input:focus { border-color: var(--template-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--template-primary) 16%, transparent); }
    .xpui-product-qty-label input::-webkit-outer-spin-button,
    .xpui-product-qty-label input::-webkit-inner-spin-button { margin: 0; appearance: none; }
    .template-product-card[data-has-image="false"] .xpui-product-qty-label { display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 52px; font-size: 17px; line-height: 1; font-weight: 700; }
    .template-product-card[data-has-image="false"] .xpui-product-qty-label::after { content: "Quantity"; display: block; margin-top: 5px; color: var(--template-muted-text); font-size: 10px; font-weight: 500; line-height: 1; }
    @media (max-width: 640px) {
      .template-product-grid:has(.template-product-card[data-has-image="false"]):not(:has(.template-product-card[data-has-image="true"])) { grid-template-columns: minmax(0, 1fr); }
      .template-product-card[data-has-image="false"] { grid-template-columns: minmax(0, 1fr); grid-template-areas: "title" "price" "description" "actions"; gap: 8px; }
      .template-product-card[data-has-image="false"] .xpui-product-controls { justify-self: start; width: 136px; }
    }
    .xpui-image-toggle-btn { width: 36px; min-width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; font-size: 14px; font-weight: 700; box-shadow: none; border: 1px solid rgba(148,163,184,0.4); background: #0f172a; color: #fff; }
    [data-image-card][data-selected="true"] .xpui-image-toggle-btn { background: transparent; color: #0f172a; border-color: transparent; }
    .xpui-gallery-dialog { position: relative; border: none; border-radius: 14px; padding: 0; width: min(960px, 100%); max-height: 90vh; box-shadow: 0 8px 48px rgba(15,23,42,0.28); }
    .xpui-gallery-dialog[data-product-view="true"] { width: min(1060px, calc(100vw - 28px)); }
    .xpui-gallery-dialog::backdrop { background: rgba(15,23,42,0.8); }
    .xpui-gallery-panel { overflow: auto; background: #fff; border-radius: 14px; padding: 14px; }
    .xpui-gallery-dialog[data-product-view="true"] .xpui-gallery-panel { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(280px, 0.7fr); gap: 22px; padding: 18px; }
    .xpui-gallery-close { float: right; width: 36px; min-width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; border: 1px solid rgba(148,163,184,0.4); background: #fff; color: #0f172a; box-shadow: none; font-size: 18px; }
    .xpui-gallery-dialog[data-product-view="true"] .xpui-gallery-close { position: absolute; top: 18px; right: 18px; z-index: 2; }
    .xpui-gallery-title { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
    .xpui-gallery-dialog[data-product-view="true"] .xpui-gallery-title,
    .xpui-gallery-dialog[data-product-view="true"] .xpui-gallery-meta { display: none; }
    .xpui-gallery-meta { font-size: 12px; opacity: 0.7; margin-bottom: 12px; }
    .xpui-gallery-main-image { width: 100%; max-height: 60vh; object-fit: contain; border-radius: 10px; display: block; }
    .xpui-gallery-dialog[data-product-view="true"] .xpui-gallery-main-image { width: 100%; height: min(62vh, 560px); max-height: none; object-fit: cover; border-radius: 12px; background: #f1f5f9; }
    .xpui-gallery-thumbs { display: flex; gap: 8px; margin-top: 10px; overflow-x: auto; }
    .xpui-gallery-dialog[data-product-view="true"] .xpui-gallery-thumbs { grid-column: 1; }
    .xpui-gallery-thumb { width: 72px; height: 72px; object-fit: cover; border-radius: 8px; cursor: pointer; outline: 1px solid rgba(148,163,184,0.35); outline-offset: 1px; opacity: 0.78; flex-shrink: 0; }
    .xpui-gallery-thumb[data-active="true"] { outline: 2px solid rgb(59 130 246); opacity: 1; }
    .xpui-product-view-details { grid-column: 2; grid-row: 1 / span 2; display: flex; flex-direction: column; gap: 14px; padding: 28px 6px 8px 0; color: var(--template-text); }
    .xpui-product-view-title { font-size: 28px; line-height: 1.08; font-weight: 800; letter-spacing: 0; color: var(--template-text); overflow-wrap: anywhere; }
    .xpui-product-view-description { color: var(--template-muted-text); font-size: 14px; line-height: 1.45; }
    .xpui-product-view-price-row { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; padding-top: 4px; }
    .xpui-product-view-price { font-size: 22px; line-height: 1.1; font-weight: 800; color: var(--template-text); }
    .xpui-product-view-regular-price { font-size: 14px; color: var(--template-muted-text); text-decoration: line-through; }
    .xpui-product-view-controls { display: grid; grid-template-columns: 44px minmax(86px, 112px) 44px; align-items: center; gap: 10px; margin-top: 4px; }
    .xpui-product-view-step { width: 44px; min-width: 44px; height: 44px; border: 1px solid var(--template-border); border-radius: var(--template-input-radius); background: var(--template-surface); color: var(--template-text); font-size: 22px; line-height: 1; font-weight: 600; box-shadow: none; }
    .xpui-product-view-step--add { background: var(--template-primary); border-color: var(--template-primary); color: #fff; }
    .xpui-product-view-step:disabled { opacity: 0.42; cursor: default; }
    .xpui-product-view-quantity { display: grid; gap: 5px; text-align: center; color: var(--template-muted-text); font-size: 11px; font-weight: 600; }
    .xpui-product-view-quantity input { width: 100%; height: 44px; box-sizing: border-box; border: 1px solid var(--template-border); border-radius: var(--template-input-radius); background: color-mix(in srgb, var(--template-surface) 96%, white); color: var(--template-text); font-size: 18px; font-weight: 800; text-align: center; font-variant-numeric: tabular-nums; }
    .xpui-product-view-quantity input:focus { outline: none; border-color: var(--template-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--template-primary) 16%, transparent); }
    .xpui-product-view-subtotal { min-height: 20px; color: var(--template-muted-text); font-size: 13px; font-weight: 600; }
    .xpui-product-view-add { min-height: 46px; border: 0; border-radius: var(--template-button-radius); background: var(--template-primary); color: #fff; font-weight: 800; padding: 0 18px; box-shadow: 0 14px 28px -22px var(--template-primary); }
    @media (max-width: 760px) {
      .xpui-gallery-dialog[data-product-view="true"] .xpui-gallery-panel { grid-template-columns: 1fr; }
      .xpui-gallery-dialog[data-product-view="true"] .xpui-gallery-main-image { height: auto; max-height: 44vh; object-fit: contain; }
      .xpui-product-view-details { grid-column: auto; grid-row: auto; padding: 0 2px 6px; }
      .xpui-product-view-title { font-size: 22px; }
    }
    .xpui-qr-video { display: block; width: 100%; height: 160px; border-radius: 8px; border: 1px solid rgba(148,163,184,0.3); background: #000; object-fit: cover; margin-top: 12px; }
    .xpui-qr-video[hidden] { display: none !important; }
    .xpui-doc-preview-img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .xpui-doc-preview-img[hidden] { display: none !important; }
    .xpui-doc-preview-placeholder { padding: 0 8px; text-align: center; font-size: 12px; opacity: 0.7; }
    .template-upload-box.xpui-capture-only .template-upload-icon,
    .template-upload-box.xpui-capture-only .template-field-label,
    .template-upload-box.xpui-capture-only .template-field-help,
    .template-upload-box.xpui-capture-only .template-upload-pills,
    .template-upload-box.xpui-capture-only input[type="file"] { display: none !important; }
    .template-upload-box.xpui-capture-only { justify-items: center; }
    .xpui-photo-grid { display: flex; flex-wrap: wrap; gap: 10px; }
    .xpui-photo-thumb { position: relative; width: 96px; height: 96px; border-radius: 14px; overflow: hidden; flex-shrink: 0; display: flex; }
    .xpui-photo-thumb--placeholder { flex-direction: column; align-items: center; justify-content: center; gap: 4px; border: 1.5px dashed color-mix(in srgb, var(--template-border) 80%, transparent); background: color-mix(in srgb, var(--template-surface) 94%, transparent); cursor: pointer; text-decoration: none; transition: border-color 0.15s, background 0.15s; padding: 6px; box-sizing: border-box; }
    .xpui-photo-thumb--placeholder:hover { border-color: var(--template-primary); background: color-mix(in srgb, var(--template-primary) 6%, var(--template-surface)); }
    .xpui-photo-thumb-icon { font-size: 22px; line-height: 1; }
    .xpui-photo-thumb-text { font-size: 10px; font-weight: 700; color: var(--template-muted-text); text-align: center; line-height: 1.2; }
    .xpui-photo-thumb--placeholder:hover .xpui-photo-thumb-text { color: var(--template-primary); }
    .xpui-photo-thumb--captured { background: #000; cursor: zoom-in; }
    .xpui-photo-thumb--captured img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .xpui-photo-thumb-remove { position: absolute; top: 4px; right: 4px; width: 20px; height: 20px; border-radius: 50%; background: rgba(15,23,42,0.65); color: #fff; border: none; cursor: pointer; font-size: 14px; font-weight: 700; line-height: 1; display: flex; align-items: center; justify-content: center; padding: 0; backdrop-filter: blur(2px); transition: background 0.12s; }
    .xpui-photo-thumb-remove:hover { background: #ef4444; }
    #xpressui-root .xpressui-signature-wrap { display: flex; flex-direction: column; gap: 8px; }
    #xpressui-root .xpressui-signature-canvas { border: 1px solid var(--template-border); border-radius: 6px; background: var(--template-surface); cursor: crosshair; touch-action: none; pointer-events: auto; width: 100%; max-width: 580px; display: block; }
    #xpressui-root .xpressui-signature-actions { display: flex; align-items: center; gap: 12px; }
    #xpressui-root .xpressui-payment-wrap { display: flex; flex-direction: column; gap: 10px; }
    #xpressui-root [data-stripe-card-element] { border: 1px solid var(--template-border); border-radius: 6px; padding: 14px 16px; background: var(--template-surface); min-height: 44px; }
    #xpressui-root [data-stripe-error] { color: #ef4444; min-height: 1em; }
    #xpressui-root .xpressui-stripe-badge { font-size: 12px; color: var(--template-muted-text); display: flex; align-items: center; gap: 6px; }
  </style>
<?php // phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
</head>
