const i18n = window.XPRESSUI_I18N || {};
const shellMeta = window.XPRESSUI_SHELL_META || {};

function getMountNode() {
  return document.getElementById(shellMeta.mountNodeId || 'xpressui-root');
}

function t(key, fallback) {
  return typeof i18n[key] === 'string' && i18n[key].trim() !== ''
    ? i18n[key]
    : fallback;
}

function logRuntimeResolution() {
  const runtimeUrl = typeof shellMeta.runtimeUrl === 'string' ? shellMeta.runtimeUrl : '';
  const runtimeSource = typeof shellMeta.runtimeSource === 'string' ? shellMeta.runtimeSource : 'unknown';
  const slug = typeof shellMeta.slug === 'string' ? shellMeta.slug : '';
  if (!runtimeUrl) {
    console.warn('[XPressUI] No runtime URL resolved for shell.', {
      slug,
      runtimeSource,
      shellMeta,
    });
    return;
  }

  console.info('[XPressUI] Runtime resolved.', {
    slug,
    runtimeSource,
    runtimeUrl,
    runtimeRelative: shellMeta.runtimeRelative || '',
    workflowPackageUrl: shellMeta.workflowPackageUrl || '',
    shellInitUrl: shellMeta.shellInitUrl || '',
  });
}

// ---------------------------------------------------------------------------
// Minimal local fallbacks used only when the runtime bundle fails to load.
// The full implementations live in window.XPressUI (shell-dom-sync / shell-embed).
// ---------------------------------------------------------------------------

function _localIsMeaningfulMessage(value) {
  if (typeof value !== 'string') return false;
  const normalized = value.trim();
  if (!normalized) return false;
  return !/^Submit failed with status \d+\.$/.test(normalized);
}

function _localResolveErrorMessage(result, error, configuredMsg, defaultMsg) {
  const candidates = [
    result?.message, result?.data?.message, result?.error,
    error?.result?.message, error?.result?.data?.message, error?.result?.error,
    error?.message, configuredMsg, defaultMsg,
  ];
  return candidates.find(_localIsMeaningfulMessage) || defaultMsg;
}

function _localSetActionButtonsDisabled(mountNode, disabled) {
  mountNode
    .querySelectorAll('button[data-step-action="back"], button[data-step-action="next"], button[type="submit"], input[type="submit"]')
    .forEach((btn) => {
      btn.disabled = Boolean(disabled);
      btn.setAttribute('aria-disabled', disabled ? 'true' : 'false');
    });
}

function _localSetFeedbackState(mountNode, state, message, title) {
  const feedbackNode = mountNode.querySelector('[data-submit-feedback]');
  const feedbackMessageNode = mountNode.querySelector('[data-submit-feedback-message]');
  const feedbackTitleNode = mountNode.querySelector('.template-submit-feedback-title');
  if (feedbackNode instanceof HTMLElement) {
    feedbackNode.style.display = '';
    feedbackNode.dataset.submitFeedbackState = state;
  }
  if (feedbackTitleNode instanceof HTMLElement) feedbackTitleNode.textContent = title;
  if (feedbackMessageNode instanceof HTMLElement) feedbackMessageNode.textContent = message;
}

// ---------------------------------------------------------------------------

function _localInjectBookingButton(mountNode, bookingUrl, label) {
  const feedbackNode = mountNode.querySelector('[data-submit-feedback]');
  if (!feedbackNode || feedbackNode.querySelector('.xpressui-booking-btn')) return;
  const btn = document.createElement('a');
  btn.className = 'xpressui-booking-btn';
  btn.href = bookingUrl;
  btn.target = '_blank';
  btn.rel = 'noopener noreferrer';
  btn.textContent = label;
  btn.style.cssText = 'display:inline-block;margin-top:16px;padding:10px 20px;background:#2271b1;color:#fff;text-decoration:none;border-radius:4px;font-size:14px;font-weight:600;';
  feedbackNode.appendChild(btn);
}

function injectBookingButton(mountNode, bookingUrl, label) {
  const fn = window.XPressUI?.injectBookingButton || _localInjectBookingButton;
  fn(mountNode, bookingUrl, label);
}

const resolveWordPressRestEndpoint = () => {
  if (window.location.protocol === 'file:' || !['http:', 'https:'].includes(window.location.protocol)) {
    return '';
  }
  if (typeof window.XPRESSUI_WORDPRESS_REST_URL === 'string' && window.XPRESSUI_WORDPRESS_REST_URL.trim() !== '') {
    return window.XPRESSUI_WORDPRESS_REST_URL;
  }
  const apiRootLink = document.querySelector('link[rel="https://api.w.org/"]');
  const apiRootHref = apiRootLink instanceof HTMLLinkElement ? apiRootLink.href : '';
  if (apiRootHref) {
    return new URL('xpressui/v1/submit', apiRootHref).toString();
  }
  const currentUrl = new URL(window.location.href);
  const contentIndex = currentUrl.pathname.indexOf('/wp-content/');
  const sitePath = contentIndex >= 0 ? currentUrl.pathname.slice(0, contentIndex) : '';
  const basePath = sitePath ? `${sitePath.replace(/\/$/, '')}/` : '/';
  return new URL(`${basePath}?rest_route=/xpressui/v1/submit`, currentUrl.origin).toString();
};

const buildSubmissionId = () => {
  const alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
  const encodeTime = (value, length) => {
    let result = '';
    let nextValue = value;
    for (let index = 0; index < length; index += 1) {
      result = alphabet[nextValue % 32] + result;
      nextValue = Math.floor(nextValue / 32);
    }
    return result;
  };

  const randomValues = new Uint8Array(16);
  if (window.crypto?.getRandomValues) {
    window.crypto.getRandomValues(randomValues);
  } else {
    for (let index = 0; index < randomValues.length; index += 1) {
      randomValues[index] = Math.floor(Math.random() * 256);
    }
  }

  let randomPart = '';
  for (let index = 0; index < 16; index += 1) {
    randomPart += alphabet[randomValues[index] % 32];
  }

  return `${encodeTime(Date.now(), 10)}${randomPart}`;
};

const ensureSubmitMetadata = (values, formConfig) => ({
  ...(values || {}),
  projectId: formConfig.submit?.metadata?.projectId || '',
  projectSlug: formConfig.submit?.metadata?.projectSlug || '',
  projectConfigVersion: formConfig.submit?.metadata?.projectConfigVersion || '',
  submissionId: values?.submissionId || buildSubmissionId(),
  projectConfigSnapshotJson: JSON.stringify(formConfig),
});

const attachFallbackSubmitHandler = (form, mountNode, formConfig) => {
  if (!(form instanceof HTMLFormElement) || form.dataset.xpressuiFallbackSubmitAttached === 'true') {
    return;
  }

  // Prefer runtime feedback helpers; fall back to local stubs if runtime never loaded.
  const setFeedback = window.XPressUI?.setShellFeedbackState
    ? (state, message, title) => window.XPressUI.setShellFeedbackState(mountNode, state, message, title)
    : (state, message, title) => _localSetFeedbackState(mountNode, state, message, title);

  const setButtonsDisabled = window.XPressUI?.setShellActionButtonsDisabled
    ? (disabled) => window.XPressUI.setShellActionButtonsDisabled(mountNode, disabled)
    : (disabled) => _localSetActionButtonsDisabled(mountNode, disabled);

  const resolveErrorMessage = window.XPressUI?.resolveShellSubmitErrorMessage
    || _localResolveErrorMessage;

  const defaultErrorMessage = t('submissionFailedMessage', 'Submission failed. Please review the form and try again.');
  const defaultSuccessMessage = t('submissionReceivedMessage', 'Submission received.');

  form.dataset.xpressuiFallbackSubmitAttached = 'true';
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    setButtonsDisabled(true);
    const formData = new FormData(form);

    if (!formData.get('submissionId')) formData.append('submissionId', buildSubmissionId());
    if (!formData.get('projectId')) formData.append('projectId', formConfig.submit?.metadata?.projectId || '');
    if (!formData.get('projectSlug')) formData.append('projectSlug', formConfig.submit?.metadata?.projectSlug || '');
    if (!formData.get('projectConfigVersion')) formData.append('projectConfigVersion', formConfig.submit?.metadata?.projectConfigVersion || '');
    if (!formData.get('projectConfigSnapshotJson')) formData.append('projectConfigSnapshotJson', JSON.stringify(formConfig));

    const configuredErrorMessage = formConfig.submitFeedback?.error_message || defaultErrorMessage;
    const configuredSuccessMessage = formConfig.submitFeedback?.success_message || '';

    setFeedback('loading', formConfig.submitFeedback?.loading_message || 'Submitting...', 'Submitting');

    try {
      const submitEndpoint =
        typeof formConfig.submit?.endpoint === 'string' &&
        formConfig.submit.endpoint &&
        formConfig.submit.endpoint !== '__XPRESSUI_WORDPRESS_REST_URL__'
          ? formConfig.submit.endpoint
          : resolveWordPressRestEndpoint();
      if (!submitEndpoint) {
        throw new Error('Missing WordPress submit endpoint. Open this package inside WordPress or define XPRESSUI_WORDPRESS_REST_URL.');
      }

      const response = await fetch(submitEndpoint, {
        method: (form.method || 'POST').toUpperCase(),
        body: formData,
        headers: { Accept: 'application/json' },
      });
      const responseText = await response.text();
      let result = null;
      try { result = responseText ? JSON.parse(responseText) : null; } catch { result = null; }

      if (!response.ok) {
        throw new Error(resolveErrorMessage(result, null, configuredErrorMessage, defaultErrorMessage) || `Submit failed with status ${response.status}.`);
      }

      setFeedback(
        'success',
        configuredSuccessMessage || result?.message || defaultSuccessMessage,
        formConfig.submitFeedback?.success_title || 'Submission received',
      );

      const fallbackBookingUrl = typeof shellMeta.bookingUrl === 'string' ? shellMeta.bookingUrl.trim() : '';
      if (fallbackBookingUrl) {
        injectBookingButton(mountNode, fallbackBookingUrl, t('bookingButtonLabel', 'Book an appointment →'));
      }

      // hydrateForm did not run — hide shell zones manually.
      if (window.XPressUI?.syncShellPostSubmitUi) {
        window.XPressUI.syncShellPostSubmitUi(mountNode, 'success');
      }

      if (window.XPressUI?.handleShellSuccessRedirect) {
        window.XPressUI.handleShellSuccessRedirect(result, formConfig);
      }
    } catch (error) {
      console.error(error);
      setButtonsDisabled(false);
      setFeedback(
        'error',
        resolveErrorMessage(null, error, configuredErrorMessage, defaultErrorMessage),
        formConfig.submitFeedback?.error_title || 'Submission failed',
      );
    }
  });
};

async function initXPressUI() {
  const mountNode = getMountNode();
  if (!mountNode) {
    console.error('Missing #xpressui-root mount node.');
    return;
  }

  let formConfig;

  try {
    const configNode = document.getElementById(shellMeta.configId || 'xpressui-custom-config');
    if (configNode) {
      formConfig = JSON.parse(configNode.textContent);
    } else {
      const configUrl = mountNode.dataset.configUrl || './form.config.json';
      const response = await fetch(configUrl);
      if (!response.ok) throw new Error(`Failed to load config: ${response.statusText}`);
      formConfig = await response.json();
    }
  } catch (err) {
    console.error('XPressUI config load error:', err);
    return;
  }

  // Sync pre-rendered DOM with config overrides (labels, required, choices, etc.)
  if (window.XPressUI?.syncShellDomWithConfig) {
    window.XPressUI.syncShellDomWithConfig(mountNode, formConfig, t);
  }

  // Inject honeypot — bots that auto-fill all fields will populate it; server rejects such submissions.
  const formElement0 = mountNode.querySelector('form');
  if (formElement0 instanceof HTMLFormElement && !formElement0.querySelector('[name="xpressui_confirm_email"]')) {
    const hp = document.createElement('input');
    hp.type = 'text';
    hp.name = 'xpressui_confirm_email';
    hp.tabIndex = -1;
    hp.autocomplete = 'off';
    hp.setAttribute('aria-hidden', 'true');
    hp.style.cssText = 'opacity:0;position:absolute;top:0;left:0;height:0;width:0;z-index:-1;pointer-events:none;';
    formElement0.appendChild(hp);
  }

  const formElement = mountNode.querySelector('form');

  try {
    logRuntimeResolution();
    formConfig.submit = formConfig.submit || {};
    if (!formConfig.submit.endpoint || formConfig.submit.endpoint === '__XPRESSUI_WORDPRESS_REST_URL__') {
      const resolvedEndpoint = resolveWordPressRestEndpoint();
      if (resolvedEndpoint) {
        formConfig.submit.endpoint = resolvedEndpoint;
      }
    }
    const hydrateForm = window.hydrateForm
      || window.XPressUI?.hydrateForm
      || window.xpressui?.hydrateForm;
    if (typeof hydrateForm !== 'function') {
      const runtimeUrl = typeof shellMeta.runtimeUrl === 'string' ? shellMeta.runtimeUrl : '';
      const runtimeSource = typeof shellMeta.runtimeSource === 'string' ? shellMeta.runtimeSource : 'unknown';
      throw new Error(`Missing bundled XPressUI runtime. Expected source: ${runtimeSource}. Runtime URL: ${runtimeUrl || 'not resolved'}.`);
    }
    const hydrated = hydrateForm(mountNode, formConfig);
    if (!hydrated) {
      throw new Error('XPressUI runtime could not hydrate the exported form shell.');
    }
    if (hydrated.formConfig) {
      hydrated.formConfig.submit = hydrated.formConfig.submit || {};
      hydrated.formConfig.submit.lifecycle = hydrated.formConfig.submit.lifecycle || {};
      hydrated.formConfig.submit.lifecycle.preSubmit = ({ values }) => ensureSubmitMetadata(values, formConfig);
    }

    // Attach runtime event listeners → update feedback UI
    if (window.XPressUI?.attachShellFeedbackHandlers) {
      window.XPressUI.attachShellFeedbackHandlers(mountNode, formConfig, { t });
    }

    // Attach submit overlay controller — show while submitting, hide on result
    const submitOverlay = mountNode.querySelector('[data-submit-overlay]');
    if (submitOverlay instanceof HTMLElement) {
      mountNode.addEventListener('xpressui:submit', function () {
        submitOverlay.setAttribute('data-active', '');
      });
      mountNode.addEventListener('xpressui:submit-success', function () {
        submitOverlay.removeAttribute('data-active');
        const bookingUrl = typeof shellMeta.bookingUrl === 'string' ? shellMeta.bookingUrl.trim() : '';
        if (bookingUrl) {
          injectBookingButton(mountNode, bookingUrl, t('bookingButtonLabel', 'Book an appointment →'));
        }
      });
      mountNode.addEventListener('xpressui:submit-error', function () {
        submitOverlay.removeAttribute('data-active');
      });
      mountNode.addEventListener('xpressui:validation-blocked-submit', function () {
        submitOverlay.removeAttribute('data-active');
      });
      mountNode.addEventListener('xpressui:submit-locked', function () {
        submitOverlay.removeAttribute('data-active');
      });
      mountNode.addEventListener('xpressui:submit-canceled', function () {
        submitOverlay.removeAttribute('data-active');
      });
    }

    // Attach embed resize reporter
    if (window.XPressUI?.attachEmbedResizeReporter) {
      window.XPressUI.attachEmbedResizeReporter();
    }
  } catch (error) {
    console.error(error);
    attachFallbackSubmitHandler(formElement, mountNode, formConfig);
    _localSetFeedbackState(
      mountNode,
      'warning',
      error instanceof Error ? error.message : 'Runtime hydration failed. Native browser fallback is active.',
      'Runtime warning',
    );
  }
}

function bootXPressUI() {
  if (!getMountNode() && document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootXPressUI, { once: true });
    return;
  }

  initXPressUI();
}

bootXPressUI();
