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



// ---------------------------------------------------------------------------
// Resume / partial-resubmission mode
// ---------------------------------------------------------------------------

function resolveResumeEndpoint(token) {
  const apiRootLink = document.querySelector('link[rel="https://api.w.org/"]');
  const apiRootHref = apiRootLink instanceof HTMLLinkElement ? apiRootLink.href : '';
  if (apiRootHref) {
    return new URL(`xpressui/v1/resume?token=${encodeURIComponent(token)}`, apiRootHref).toString();
  }
  const currentUrl = new URL(window.location.href);
  const contentIndex = currentUrl.pathname.indexOf('/wp-content/');
  const sitePath = contentIndex >= 0 ? currentUrl.pathname.slice(0, contentIndex) : '';
  const basePath = sitePath ? `${sitePath.replace(/\/$/, '')}/` : '/';
  return `${currentUrl.origin}${basePath}?rest_route=/xpressui/v1/resume&token=${encodeURIComponent(token)}`;
}

async function fetchResumeData(token) {
  try {
    const response = await fetch(resolveResumeEndpoint(token), {
      headers: { Accept: 'application/json' },
    });
    if (!response.ok) return { expired: true };
    const data = await response.json();
    return data?.success ? data : { expired: true };
  } catch {
    return null;
  }
}

function applyResumeMode(mountNode, form, resumeData, token) {
  const { payload, flaggedFields, note } = resumeData;
  if (!payload || !form) return;

  const flaggedSet = new Set(Array.isArray(flaggedFields) ? flaggedFields : []);
  const additionalFiles = Array.isArray(resumeData.additionalFiles) && resumeData.additionalFiles.length
    ? resumeData.additionalFiles
    : (resumeData.additionalFile
        ? [{ id: 'xpressui_afile', ...resumeData.additionalFile }]
        : []);
  const activeAdditionalFileIds = new Set(
    additionalFiles
      .filter((slot) => slot && slot.active && typeof slot.id === 'string' && slot.id.trim() !== '')
      .map((slot) => slot.id),
  );
  // Show all fields only for a general note with no specific instruction (no flags and no additional file request).
  // When specific fields are flagged OR an additional file is requested, hide everything not explicitly requested.
  const showAllFields = flaggedSet.size === 0 && activeAdditionalFileIds.size === 0;

  // Banner — show pre-rendered element and fill in the operator note
  if (note && note.trim()) {
    const banner = form.querySelector('[data-resume-banner]');
    const noteEl = banner?.querySelector('[data-resume-banner-note]');
    if (banner && noteEl) {
      noteEl.textContent = note;
      banner.style.display = '';
    }
  }

  // Resume token — enable pre-rendered hidden input and fill its value
  const tokenInput = form.querySelector('[data-resume-token]');
  if (tokenInput instanceof HTMLInputElement) {
    tokenInput.disabled = false;
    tokenInput.value = token;
  }

  // Prefill all text/select/checkbox fields; hide containers of non-flagged ones.
  const hiddenFieldContainers = new Set();
  form.querySelectorAll('input:not([type="file"]):not([type="submit"]):not([type="button"]):not([type="hidden"]), textarea, select').forEach((input) => {
    const name = input.name;
    if (!name || name === 'xpressui_confirm_email') return;

    const value = payload[name];
    const isFlagged = showAllFields || flaggedSet.has(name);

    if (value !== undefined && value !== null) {
      if (input instanceof HTMLSelectElement) {
        input.value = String(value);
        input.dispatchEvent(new Event('change', { bubbles: true }));
      } else if (input instanceof HTMLInputElement && (input.type === 'checkbox' || input.type === 'radio')) {
        input.checked = input.value === String(value) || value === true || value === 'true';
        input.dispatchEvent(new Event('change', { bubbles: true }));
      } else {
        input.value = typeof value === 'object' ? '' : String(value);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }

    if (!isFlagged) {
      const fieldNode = input.closest('[data-field-name]');
      if (fieldNode && !hiddenFieldContainers.has(fieldNode)) {
        hiddenFieldContainers.add(fieldNode);
        fieldNode.style.display = 'none';
      }
      input.disabled = true;
    }
  });

  // Handle file fields
  const referenceFiles = resumeData.referenceFiles || {};
  form.querySelectorAll('input[type="file"]').forEach((fileInput) => {
    const name = fileInput.name;
    if (!name) return;
    const isAdditionalFileSlot = activeAdditionalFileIds.has(name);
    const isFlagged = showAllFields || flaggedSet.has(name) || isAdditionalFileSlot;
    const refFile = referenceFiles[name];

    // Reference file — show pre-rendered block above the upload input when the field is flagged
    if (refFile && refFile.url && isFlagged) {
      const fieldNode = fileInput.closest('[data-field-name]');
      const refBlock = fieldNode?.querySelector('[data-ref-file-block]');
      const refLink  = refBlock?.querySelector('[data-ref-file-link]');
      const refHint  = refBlock?.querySelector('[data-ref-file-hint]');
      if (refBlock && refLink && refHint) {
        refLink.href        = refFile.url;
        refLink.textContent = '⬇ ' + (refFile.name || t('resume.downloadFile', 'Download file'));
        refHint.textContent = t('resume.refFileHint', 'Download this file, complete or sign it, then re-upload it below.');
        refBlock.style.display = '';
      }
    }

    // Non-flagged file field — hide the whole container (server keeps original value)
    if (!isFlagged) {
      const fieldNode = fileInput.closest('[data-field-name]');
      if (fieldNode && !hiddenFieldContainers.has(fieldNode)) {
        hiddenFieldContainers.add(fieldNode);
        fieldNode.style.display = 'none';
      }
      fileInput.disabled = true;
    } else {
      fileInput.disabled = false;
    }
  });

  // Section visibility — hide sections that contain no flagged fields.
  // (In resume mode the runtime is single-step so all sections are initially visible.)
  if (!showAllFields) {
    form.querySelectorAll('[data-template-zone="section"]:not([data-afile-slot])').forEach((section) => {
      const hasFlagged = Array.from(section.querySelectorAll('[data-field-name]')).some((field) => {
        const name = field.getAttribute('data-field-name');
        return name !== null && flaggedSet.has(name);
      });
      if (!hasFlagged) {
        section.style.display = 'none';
      }
    });
  }

  // Additional file slots — show and configure each active slot.
  additionalFiles.forEach((additionalFile) => {
    if (!additionalFile?.active || typeof additionalFile.id !== 'string' || !additionalFile.id) {
      return;
    }
    const slot = form.querySelector(`[data-afile-slot="${additionalFile.id}"]`);
    if (slot) {
      slot.style.display = '';
      const fieldNode = slot.querySelector(`[data-field-name="${additionalFile.id}"]`);
      if (fieldNode) {
        fieldNode.style.display = '';
      }
      const fileInput = slot.querySelector(`input[type="file"][name="${additionalFile.id}"]`);
      if (fileInput instanceof HTMLInputElement) {
        fileInput.required = true;
        fileInput.setAttribute('aria-required', 'true');
      }
      const labelEl = slot.querySelector('[data-afile-label]');
      if (labelEl && additionalFile.label) {
        labelEl.textContent = additionalFile.label;
      }
      const requiredMarker = slot.querySelector('.template-required');
      if (requiredMarker instanceof HTMLElement) {
        requiredMarker.style.display = '';
        requiredMarker.setAttribute('aria-hidden', 'true');
      }
      const refBlock = slot.querySelector('[data-afile-ref-block]');
      const refLink  = slot.querySelector('[data-afile-ref-link]');
      const refHint  = slot.querySelector('[data-afile-ref-hint]');
      if (refBlock && refLink && refHint && additionalFile.refFile?.url) {
        refLink.href        = additionalFile.refFile.url;
        refLink.textContent = '⬇ ' + (additionalFile.refFile.name || t('resume.downloadFile', 'Download file'));
        refHint.textContent = t('resume.refFileHint', 'Download this file, complete or sign it, then re-upload it below.');
        refBlock.style.display = '';
      }
    }
  });
}

// ---------------------------------------------------------------------------

function finalizeResumeSession(form) {
  if (!(form instanceof HTMLFormElement)) return;

  const tokenInput = form.querySelector('[data-resume-token]');
  if (tokenInput instanceof HTMLInputElement) {
    tokenInput.value = '';
    tokenInput.disabled = true;
  }

  form.querySelectorAll('input, textarea, select, button').forEach((el) => {
    if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement || el instanceof HTMLSelectElement || el instanceof HTMLButtonElement) {
      el.disabled = true;
      el.setAttribute('aria-disabled', 'true');
    }
  });

  if (typeof window !== 'undefined' && window.history?.replaceState) {
    const url = new URL(window.location.href);
    if (url.searchParams.has('xpressui_resume')) {
      url.searchParams.delete('xpressui_resume');
      const nextSearch = url.searchParams.toString();
      const nextUrl = `${url.pathname}${nextSearch ? `?${nextSearch}` : ''}${url.hash || ''}`;
      window.history.replaceState({}, document.title, nextUrl);
    }
  }
}

// ---------------------------------------------------------------------------

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
        if (response.status === 404 || response.status === 410) {
          finalizeResumeSession(form);
        }
        throw new Error(resolveErrorMessage(result, null, configuredErrorMessage, defaultErrorMessage) || `Submit failed with status ${response.status}.`);
      }

      if (formData.get('xpressui_resume_token')) {
        finalizeResumeSession(form);
      }

      setFeedback(
        'success',
        configuredSuccessMessage || result?.message || defaultSuccessMessage,
        formConfig.submitFeedback?.success_title || 'Submission received',
      );

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

  // Detect resume token before anything else.
  // The inline script in form-fragment.php already set data-resume-loading on mountNode
  // so CSS hides the form and shows the loader while we fetch resume data.
  const resumeToken = new URLSearchParams(window.location.search).get('xpressui_resume') || '';
  let resumeData = null;
  if (resumeToken) {
    resumeData = await fetchResumeData(resumeToken);
    if (resumeData?.expired) {
      mountNode.removeAttribute('data-resume-loading');
      _localSetFeedbackState(
        mountNode,
        'error',
        t('resume.expired', 'This resubmission link has already been used or has expired. Please contact us if you need to make further corrections.'),
        t('resume.expiredTitle', 'Link expired'),
      );
      const form = mountNode.querySelector('form');
      if (form) {
        form.querySelectorAll('input, textarea, select, button[type="submit"]').forEach((el) => { el.disabled = true; });
      }
      return;
    }
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

  // Honeypot is pre-rendered in the form template (xpressui_confirm_email).

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
    // Resume mode: collapse multi-step to single-step so the runtime hides nav/progress
    // and shows the submit button immediately. Section visibility is handled by applyResumeMode.
    if (resumeData && formConfig.mode === 'form-multi-step') {
      formConfig = { ...formConfig, mode: 'form' };
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
      mountNode.removeAttribute('data-resume-loading');
      throw new Error('XPressUI runtime could not hydrate the exported form shell.');
    }
    if (hydrated.formConfig) {
      hydrated.formConfig.submit = hydrated.formConfig.submit || {};
      hydrated.formConfig.submit.lifecycle = hydrated.formConfig.submit.lifecycle || {};
      hydrated.formConfig.submit.lifecycle.preSubmit = ({ values }) => ensureSubmitMetadata(values, formConfig);
      const existingPostSuccess = hydrated.formConfig.submit.lifecycle.postSuccess;
      const finalizeResumePostSuccess = (detail) => {
        if (detail?.values?.xpressui_resume_token) {
          finalizeResumeSession(mountNode.querySelector('form'));
        }
      };
      hydrated.formConfig.submit.lifecycle.postSuccess = existingPostSuccess
        ? Array.isArray(existingPostSuccess)
          ? [...existingPostSuccess, finalizeResumePostSuccess]
          : [existingPostSuccess, finalizeResumePostSuccess]
        : finalizeResumePostSuccess;
    }

    // Apply resume mode after hydration (re-query form in case runtime replaced it).
    if (resumeData) {
      applyResumeMode(mountNode, mountNode.querySelector('form'), resumeData, resumeToken);
    }
    mountNode.removeAttribute('data-resume-loading');

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
    mountNode.removeAttribute('data-resume-loading');
    const currentForm = mountNode.querySelector('form');
    if (resumeData) {
      applyResumeMode(mountNode, currentForm, resumeData, resumeToken);
    }
    attachFallbackSubmitHandler(currentForm, mountNode, formConfig);
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
