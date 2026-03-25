const mountNode = document.getElementById('xpressui-root');
const i18n = window.XPRESSUI_I18N || {};

function t(key, fallback) {
  return typeof i18n[key] === 'string' && i18n[key].trim() !== ''
    ? i18n[key]
    : fallback;
}

async function initXPressUI() {
  if (!mountNode) {
    console.error('Missing #xpressui-root mount node.');
    return;
  }

  let formConfig;
  
  try {
    const configNode = document.getElementById('xpressui-custom-config');
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

  // --- DYNAMIC DOM SYNCHRONIZATION ---
  // Apply customizations from JSON config directly to the HTML DOM
  const syncDomWithConfig = () => {
    if (!formConfig || !formConfig.sections) return;
    const headerNode = mountNode.querySelector('.template-form-header');
    const titleText = typeof formConfig.title === 'string' && formConfig.title.trim() !== ''
      ? formConfig.title
      : '';
    const showProjectTitle = formConfig.showProjectTitle !== false;
    let titleNode = headerNode?.querySelector('.template-form-title');
    let subtitleNode = headerNode?.querySelector('.template-form-subtitle');

    if (headerNode instanceof HTMLElement) {
      if (showProjectTitle && titleText) {
        if (!(titleNode instanceof HTMLElement)) {
          titleNode = document.createElement('h1');
          titleNode.className = 'template-form-title';
          if (subtitleNode instanceof HTMLElement) {
            headerNode.insertBefore(titleNode, subtitleNode);
          } else {
            headerNode.appendChild(titleNode);
          }
        }
        titleNode.textContent = titleText;
        titleNode.style.display = '';
      } else if (titleNode instanceof HTMLElement) {
        titleNode.remove();
        titleNode = null;
      }

      if (!(subtitleNode instanceof HTMLElement)) {
        subtitleNode = document.createElement('p');
        subtitleNode.className = 'template-form-subtitle';
        headerNode.appendChild(subtitleNode);
      }
      subtitleNode.textContent = t('requiredFields', '* Required fields');
      subtitleNode.style.display = '';

      headerNode.style.display = (titleNode instanceof HTMLElement || subtitleNode instanceof HTMLElement)
        ? ''
        : 'none';
    }
    
    // Sync Section Titles
    if (formConfig.sections.custom) {
      formConfig.sections.custom.forEach(section => {
        if (section.name && section.label) {
          const sectionHeader = mountNode.querySelector(`[data-section-name="${section.name}"] .template-section-label`);
          if (sectionHeader) sectionHeader.textContent = section.label;
        }
      });
    }

    // Sync Fields (Labels, Required state, Select choices)
    Object.keys(formConfig.sections).forEach(sectionKey => {
      if (sectionKey === 'custom' || sectionKey === 'btngroup') return;
      const fields = formConfig.sections[sectionKey];
      if (!Array.isArray(fields)) return;
      
      fields.forEach(field => {
        if (!field.name) return;
        const fieldNode = mountNode.querySelector(`[data-field-name="${field.name}"]`);
        if (!fieldNode) return;

        if (field.label) {
          const labelSpan = fieldNode.querySelector('.template-field-label span:not(.template-required)');
          if (labelSpan) labelSpan.textContent = field.label;
          const input = fieldNode.querySelector(`[name="${field.name}"]`);
          if (input) input.dataset.label = field.label;
        }

        if (field.required !== undefined) {
          let reqSpan = fieldNode.querySelector('.template-required');
          const input = fieldNode.querySelector(`[name="${field.name}"]`);
          
          if (field.required && !reqSpan) {
            const labelElem = fieldNode.querySelector('.template-field-label');
            if (labelElem) {
              reqSpan = document.createElement('span');
              reqSpan.className = 'template-required';
              reqSpan.setAttribute('aria-hidden', 'true');
              reqSpan.textContent = '*';
              labelElem.appendChild(reqSpan);
            }
          } else if (!field.required && reqSpan) {
            reqSpan.remove();
          }
          
          if (input) {
            if (field.required) {
              input.setAttribute('required', 'true');
              input.setAttribute('aria-required', 'true');
            } else {
              input.removeAttribute('required');
              input.removeAttribute('aria-required');
            }
          }
        }

        if (field.choices && Array.isArray(field.choices)) {
          const select = fieldNode.querySelector(`select[name="${field.name}"]`);
          if (select) {
            const hasEmptyFirst = select.options.length > 0 && select.options[0].value === "";
            const firstOption = hasEmptyFirst ? select.options[0].outerHTML : "";
            select.innerHTML = firstOption + field.choices.map(c => {
              const val = c.value || c.id || c.name || '';
              const lbl = c.label || c.name || val;
              const safeVal = val.replace(/"/g, '&quot;');
              const safeLbl = lbl.replace(/</g, '&lt;').replace(/>/g, '&gt;');
              return `<option value="${safeVal}">${safeLbl}</option>`;
            }).join('');
          }
        }
      });
    });
  };
  syncDomWithConfig();
  // --- END DOM SYNCHRONIZATION ---

  const feedbackNode = document.querySelector('[data-submit-feedback]');
  const feedbackMessageNode = document.querySelector('[data-submit-feedback-message]');
  const feedbackTitleNode = document.querySelector('.template-submit-feedback-title');
  const submitRowNode = document.querySelector('[data-template-zone="submit_actions"]');
  const stepActionsNode = document.querySelector('[data-form-step-actions]');
  const formElement = mountNode.querySelector('form');

  const submitFeedbackConfig = formConfig.submitFeedback || {
    error_title: t('submissionFailedTitle', 'Submission failed'),
    error_message: t('submissionFailedMessage', 'Submission failed. Please review the form and try again.'),
    idle_message: t('submissionFeedbackIdle', 'Submission feedback will appear here after the runtime handles the form.'),
    loading_message: t('submitting', 'Submitting...'),
    success_title: t('submissionReceivedTitle', 'Submission received'),
    success_message: t('submissionReceivedMessage', 'Submission received.'),
    title: t('submissionStatusTitle', 'Submission status')
  };

  const configuredSuccessMessage = submitFeedbackConfig.success_message || '';
  const configuredErrorMessage = submitFeedbackConfig.error_message || '';
  const defaultSuccessMessage = t('submissionReceivedMessage', 'Submission received.');
  const defaultErrorMessage = t('submissionFailedMessage', 'Submission failed. Please review the form and try again.');

  const handleSuccessRedirect = (result) => {
    const urlParams = new URLSearchParams(window.location.search);
    const redirectUrl = urlParams.get('redirect')
      || (result && result.redirectUrl)
      || (formConfig.workflowConfig && formConfig.workflowConfig.redirectUrl);
    if (redirectUrl) {
      setTimeout(() => {
        try { window.top.location.href = redirectUrl; }
        catch (e) { window.location.href = redirectUrl; }
      }, 1500);
    }
  };

  const syncPostSubmitUi = (state) => {
    const hideActions = state === 'success';
    if (submitRowNode instanceof HTMLElement) {
      submitRowNode.style.display = hideActions ? 'none' : '';
    }
    if (stepActionsNode instanceof HTMLElement) {
      stepActionsNode.style.display = hideActions ? 'none' : '';
    }
  };

  const setFeedbackState = (state, message, title = submitFeedbackConfig.title || t('submissionStatusTitle', 'Submission status')) => {
    if (feedbackNode instanceof HTMLElement) {
      feedbackNode.style.display = '';
      feedbackNode.dataset.submitFeedbackState = state;
    }
    syncPostSubmitUi(state);
    if (feedbackTitleNode instanceof HTMLElement) {
      feedbackTitleNode.textContent = title;
    }
    if (feedbackMessageNode instanceof HTMLElement) {
      feedbackMessageNode.textContent = message;
    }
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

  const ensureSubmitMetadata = (values) => ({
    ...(values || {}),
    projectId: formConfig.submit?.metadata?.projectId || "",
    projectSlug: formConfig.submit?.metadata?.projectSlug || "",
    projectConfigVersion: formConfig.submit?.metadata?.projectConfigVersion || "",
    submissionId: values?.submissionId || buildSubmissionId(),
    projectConfigSnapshotJson: JSON.stringify(formConfig),
  });

  const attachRuntimeFeedbackHandlers = (node) => {
    if (!(node instanceof HTMLElement) || node.dataset.xpressuiRuntimeFeedbackAttached === 'true') {
      return;
    }

    node.dataset.xpressuiRuntimeFeedbackAttached = 'true';
    node.addEventListener('xpressui:submit', () => {
      setFeedbackState('loading', submitFeedbackConfig.loading_message || 'Submitting...', 'Submitting');
    });
    node.addEventListener('xpressui:submit-success', (event) => {
      const result = event?.detail?.result;
      setFeedbackState('success', configuredSuccessMessage || result?.message || defaultSuccessMessage, submitFeedbackConfig.success_title || 'Submission received');
      handleSuccessRedirect(result);
    });
    node.addEventListener('xpressui:submit-error', (event) => {
      const result = event?.detail?.result;
      const error = event?.detail?.error;
      setFeedbackState('error', configuredErrorMessage || result?.message || error?.message || defaultErrorMessage, submitFeedbackConfig.error_title || 'Submission failed');
    });
    node.addEventListener('xpressui:submit-locked', (event) => {
      const result = event?.detail?.result;
      setFeedbackState('warning', result?.message || 'Complete the required fields before submitting.', 'Submission blocked');
    });
  };

  const attachFallbackSubmitHandler = (form) => {
    if (!(form instanceof HTMLFormElement) || form.dataset.xpressuiFallbackSubmitAttached === 'true') {
      return;
    }

    form.dataset.xpressuiFallbackSubmitAttached = 'true';
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(form);

      if (!formData.get('submissionId')) {
        formData.append('submissionId', buildSubmissionId());
      }
      if (!formData.get('projectId')) {
        formData.append('projectId', formConfig.submit?.metadata?.projectId || "");
      }
      if (!formData.get('projectSlug')) {
        formData.append('projectSlug', formConfig.submit?.metadata?.projectSlug || "");
      }
      if (!formData.get('projectConfigVersion')) {
        formData.append('projectConfigVersion', formConfig.submit?.metadata?.projectConfigVersion || "");
      }
      if (!formData.get('projectConfigSnapshotJson')) {
        formData.append('projectConfigSnapshotJson', JSON.stringify(formConfig));
      }

      setFeedbackState('loading', submitFeedbackConfig.loading_message || 'Submitting...', 'Submitting');

      try {
        const submitEndpoint = typeof formConfig.submit?.endpoint === 'string' && formConfig.submit.endpoint && formConfig.submit.endpoint !== '__XPRESSUI_WORDPRESS_REST_URL__'
          ? formConfig.submit.endpoint
          : resolveWordPressRestEndpoint();
        if (!submitEndpoint) {
          throw new Error('Missing WordPress submit endpoint. Open this package inside WordPress or define XPRESSUI_WORDPRESS_REST_URL.');
        }
        
        const response = await fetch(submitEndpoint, {
          method: (form.method || 'POST').toUpperCase(),
          body: formData,
          headers: {
            Accept: 'application/json',
          },
        });
        const responseText = await response.text();
        let result = null;

        try {
          result = responseText ? JSON.parse(responseText) : null;
        } catch {
          result = null;
        }

        if (!response.ok) {
          throw new Error(result?.message || `Submit failed with status ${response.status}.`);
        }

        setFeedbackState('success', configuredSuccessMessage || result?.message || defaultSuccessMessage, submitFeedbackConfig.success_title || 'Submission received');
        handleSuccessRedirect(result);
      } catch (error) {
        console.error(error);
        setFeedbackState('error', configuredErrorMessage || (error instanceof Error ? error.message : '') || defaultErrorMessage, submitFeedbackConfig.error_title || 'Submission failed');
      }
    });
  };

  try {
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
      throw new Error('Missing bundled XPressUI runtime.');
    }
    const hydrated = hydrateForm(mountNode, formConfig);
    if (!hydrated) {
      throw new Error('XPressUI runtime could not hydrate the exported form shell.');
    }
    if (hydrated.formConfig) {
      hydrated.formConfig.submit = hydrated.formConfig.submit || {};
      hydrated.formConfig.submit.lifecycle = hydrated.formConfig.submit.lifecycle || {};
      hydrated.formConfig.submit.lifecycle.preSubmit = ({ values }) => ensureSubmitMetadata(values);
    }
    attachRuntimeFeedbackHandlers(mountNode);

    // Definitive iframe autoresize: observe document.body from inside the iframe
    // and postMessage the height to the parent on every layout change.
    //
    // Why body, not the form-ui element:
    //   form-ui is a custom HTMLElement whose default display is 'inline'.
    //   ResizeObserver behaviour on inline elements is inconsistent across browsers.
    //   document.body is always display:block and is the canonical source of truth
    //   for the total iframe content height.
    //
    // Why from inside (postMessage) rather than relying only on the parent's observer:
    //   The parent's ResizeObserver observes the same body but resets frame height to
    //   0 before measuring (iOS Safari workaround), which can race with React renders.
    //   Measuring from inside avoids that reset and fires in the same JS context as
    //   the DOM mutation, giving the most up-to-date value.
    if (window.parent !== window && window.ResizeObserver) {
      new ResizeObserver(function () {
        var h = Math.max(
          document.body.scrollHeight  || 0,
          document.body.offsetHeight  || 0,
          document.documentElement ? (document.documentElement.scrollHeight || 0) : 0,
          document.documentElement ? (document.documentElement.offsetHeight || 0) : 0
        );
        if (h > 0) {
          try { window.parent.postMessage({ type: 'xpressui:resize', height: h }, '*'); } catch (_e) {}
        }
      }).observe(document.body);
    }
  } catch (error) {
    console.error(error);
    attachFallbackSubmitHandler(formElement);
    setFeedbackState('warning', error instanceof Error ? error.message : 'Runtime hydration failed. Native browser fallback is active.', 'Runtime warning');
  }
}

initXPressUI();
