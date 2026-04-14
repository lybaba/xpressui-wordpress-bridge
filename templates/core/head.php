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
      padding: 32px 16px;
      position: relative;
      isolation: isolate;
      overflow: hidden;
    }
    #xpressui-root {
      position: relative;
      isolation: isolate;
      width: 100%;
      padding: 48px 16px;
      box-sizing: border-box;
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
<?php endif; ?><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'background_image_url'), xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'background_style'), "full-bleed")))): ?>
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
<?php endif; ?>    .form-frame {
      width: min(100%, 960px);
      background: <?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'background_image_url'), (!xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'background_style'), "none"))))): ?>color-mix(in srgb, var(--template-surface) 96%, transparent)<?php else: ?>color-mix(in srgb, var(--template-surface) 92%, white)<?php endif; ?>;
      border-radius: var(--template-card-radius);
      padding: 24px;
      box-shadow: <?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'background_image_url'), (!xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'background_style'), "none"))))): ?>0 28px 80px -38px rgba(0,0,0,0.42)<?php else: ?>0 20px 60px rgba(0,0,0,0.08)<?php endif; ?>;
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'background_image_url'), (!xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'background_style'), "none"))))): ?>
backdrop-filter: blur(18px) saturate(1.08);<?php endif; ?>      position: relative;
      z-index: 1;
    }
    .template-runtime-shell { display: grid; gap: 20px; }
    .template-form-header { display: grid; gap: 4px; padding: 4px 2px 0; }
    .template-form-title { margin: 0; font-size: clamp(28px, 4vw, 42px); line-height: 1.02; letter-spacing: -0.04em; color: var(--template-text); font-weight: 900; }
    .template-form-subtitle { margin: 0; color: var(--template-muted-text); font-size: 12px; line-height: 1.45; max-width: 720px; }
    .template-step-status { display: grid; gap: 10px; padding: 0 4px 10px; background: transparent; border: none; }
    .template-step-status[data-step-feedback-state='loading'] { background: color-mix(in srgb, #3b82f6 8%, var(--template-surface)); border-color: color-mix(in srgb, #3b82f6 20%, transparent); }
    .template-step-status[data-step-feedback-state='success'] { background: color-mix(in srgb, #22c55e 8%, var(--template-surface)); border-color: color-mix(in srgb, #22c55e 20%, transparent); }
    .template-step-status-title { font-size: 12px; font-weight: 800; color: var(--template-primary); text-transform: uppercase; letter-spacing: 0.06em; display: inline-flex; align-items: center; align-self: start; background: color-mix(in srgb, var(--template-primary) 8%, transparent); padding: 6px 12px; border-radius: 999px; }
    .template-step-status-message { display: none; /* Cleaner to hide this and rely on Section labels */ }
    .template-section { display: grid; gap: 16px; padding: 20px; border-radius: max(calc(var(--template-card-radius) - 6px), 18px); border: 1px solid color-mix(in srgb, var(--template-border) 72%, rgba(15,23,42,0.08)); background: color-mix(in srgb, var(--template-surface) 84%, white); }
    .template-section-header { display: grid; gap: 4px; }
    .template-section-label { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.03em; color: var(--template-text); }
    .template-section-desc { margin: 0 0 4px 0; color: var(--template-muted-text); font-size: 14px; line-height: 1.5; }
    .template-fields { display: flex; flex-direction: column; gap: 14px; width: 100%; }
    .template-field { display: flex; flex-direction: column; gap: 8px; width: 100%; min-width: 0; }
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
    .template-field-pill { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px; background: color-mix(in srgb, var(--template-border) 40%, transparent); border: 1px solid color-mix(in srgb, var(--template-border) 80%, transparent); color: var(--template-text); font-size: 12px; font-weight: 700; line-height: 1; }
    .template-input,
    .template-textarea { display: block; width: 100%; min-width: 0; max-width: none; box-sizing: border-box; border: 1px solid var(--template-border); border-radius: var(--template-input-radius); background: color-mix(in srgb, var(--template-surface) 96%, white); color: var(--template-text); font: inherit; padding: 14px 16px; }
    .template-runtime-shell select { display: block; width: 100%; min-width: 0; max-width: none; box-sizing: border-box; border: 1px solid var(--template-border) !important; border-radius: var(--template-input-radius) !important; background-color: color-mix(in srgb, var(--template-surface) 96%, white) !important; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23888888'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") !important; background-repeat: no-repeat !important; background-position: right 16px center !important; background-size: 16px !important; color: var(--template-text) !important; font: inherit; padding: 14px 40px 14px 16px !important; -webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important; accent-color: var(--template-primary) !important; }
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
    .template-stepper-btn { width: 36px; height: 36px; border: 1px solid color-mix(in srgb, var(--template-border) 50%, transparent); border-radius: 999px; background: color-mix(in srgb, var(--template-text) 96%, transparent); color: var(--template-surface); font-size: 18px; line-height: 1; display: inline-flex; align-items: center; justify-content: center; }
    .template-stepper-btn.is-muted { background: transparent; color: var(--template-muted-text); }
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
    .template-upload-box[data-file-drag-active='true'] { border-color: var(--template-primary); background: color-mix(in srgb, var(--template-primary) 8%, var(--template-surface)); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--template-primary) 20%, transparent); }
    .template-upload-box[data-file-drop-state='selected'] { grid-template-columns: auto 1fr; align-items: center; justify-items: start; gap: 10px 14px; padding: 10px 14px; text-align: left; }
    .template-upload-box[data-file-drop-state='selected'] .template-upload-icon { width: 28px; height: 28px; border-radius: 10px; font-size: 14px; grid-row: 1 / span 2; }
    .template-upload-box[data-file-drop-state='selected'] .template-field-label { font-size: 12px; }
    .template-upload-box[data-file-drop-state='selected'] .template-field-help,
    .template-upload-box[data-file-drop-state='selected'] .template-upload-pills { display: none; }
    .template-upload-box[data-file-drop-state='selected'] .template-input { grid-column: 2; margin-top: 0; }
    .template-upload-icon { width: 36px; height: 36px; border-radius: 12px; background: color-mix(in srgb, var(--template-border) 40%, transparent); color: var(--template-text); display: inline-flex; align-items: center; justify-content: center; font-size: 18px; }
    .template-upload-pills { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; }
    .template-upload-box .template-field-label { font-size: 13px; }
    .template-upload-box .template-field-help { max-width: 520px; }
    .template-upload-box .template-input { margin-top: 4px; padding-top: 10px; padding-bottom: 10px; }
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
      .template-gallery-grid { grid-template-columns: 1fr; }
      .template-image-gallery-list { grid-template-columns: 1fr; }
    }
    .template-submit-feedback { display: grid; gap: 6px; padding: 14px 16px; border-radius: 16px; border: 1px solid color-mix(in srgb, var(--template-border) 72%, transparent); background: color-mix(in srgb, var(--template-surface) 94%, transparent); }
    .template-submit-feedback[data-submit-feedback-state='success'] { background: color-mix(in srgb, #22c55e 8%, var(--template-surface)); border-color: color-mix(in srgb, #22c55e 20%, transparent); }
    .template-submit-feedback[data-submit-feedback-state='error'] { background: color-mix(in srgb, #ef4444 8%, var(--template-surface)); border-color: color-mix(in srgb, #ef4444 20%, transparent); }
    .template-submit-feedback[data-submit-feedback-state='loading'] { background: color-mix(in srgb, #3b82f6 8%, var(--template-surface)); border-color: color-mix(in srgb, #3b82f6 20%, transparent); }
    .template-submit-feedback-title { font-size: 13px; font-weight: 800; color: var(--template-text); }
    .template-submit-feedback-message { font-size: 13px; color: var(--template-muted-text); line-height: 1.45; }
    button.template-field-pill { appearance: none; font: inherit; cursor: pointer; transition: background 0.15s, border-color 0.15s, color 0.15s; }
    button.template-field-pill[data-document-scan-active="true"] { background: var(--template-primary); border-color: var(--template-primary); color: #f8fafc; }
    .template-submit-row { display: flex; justify-content: flex-end; padding-top: 18px; margin-top: 2px; border-top: 1px solid color-mix(in srgb, var(--template-border) 60%, transparent); }
    .template-submit-btn { border: none; border-radius: var(--template-button-radius); background: var(--template-primary); color: #f8fafc; font: inherit; font-weight: 700; padding: 14px 20px; min-width: 140px; }
    .template-step-progress-track { background: color-mix(in srgb, var(--template-border) 40%, transparent); border-radius: 999px; height: 4px; overflow: hidden; margin-top: 4px; }
    .template-step-progress-bar { height: 100%; background: linear-gradient(90deg, var(--template-primary) 0%, color-mix(in srgb, var(--template-primary) 76%, var(--template-text)) 100%); border-radius: 999px; }
    .template-step-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 18px 0 0; }
    .template-step-actions-leading, .template-step-actions-trailing { display: flex; align-items: center; gap: 12px; }
    .template-step-actions [data-step-action] { appearance: none; border: 1px solid color-mix(in srgb, var(--template-border) 80%, transparent); border-radius: var(--template-button-radius); font: inherit; font-size: 14px; font-weight: 700; line-height: 1; min-width: 132px; padding: 14px 20px; box-shadow: none; cursor: pointer; transition: transform 160ms ease, background 160ms ease, color 160ms ease, border-color 160ms ease; }
    .template-step-actions [data-step-action]:hover { transform: translateY(-1px); }
    .template-step-actions [data-step-action="back"] { background: var(--template-surface); color: var(--template-text); }
    .template-step-actions [data-step-action="next"] { background: var(--template-primary); border-color: var(--template-primary); color: #f8fafc; }
    .template-step-actions [data-step-action]:disabled { opacity: 0.54; cursor: not-allowed; transform: none; }

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
  </style>
</head>
