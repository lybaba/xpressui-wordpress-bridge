/**
 * Shell DOM helpers — generic utilities for syncing a pre-rendered HTML form shell
 * with a live form config and managing submit feedback state.
 *
 * These functions operate exclusively on the DOM using well-known data attributes
 * and CSS class names produced by XPressUI export templates. They have no
 * dependency on WordPress or any server-side platform.
 */

export type TShellFeedbackState = 'idle' | 'loading' | 'success' | 'error' | 'warning';

export type TShellI18nResolver = (key: string, fallback: string) => string;

export interface TShellSubmitFeedbackConfig {
  title?: string;
  error_title?: string;
  error_message?: string;
  loading_message?: string;
  success_title?: string;
  success_message?: string;
  idle_message?: string;
}

export interface TShellFormConfig {
  title?: string;
  showProjectTitle?: boolean;
  showRequiredFieldsNote?: boolean;
  sectionLabelVisibility?: 'show' | 'hide' | 'auto';
  sections?: Record<string, any[]> & { custom?: Array<{ name: string; label: string }> };
  submitFeedback?: TShellSubmitFeedbackConfig;
  workflowConfig?: { redirectUrl?: string };
}

export interface TAttachShellFeedbackOptions {
  t?: TShellI18nResolver;
}

export interface TAttachShellSubmitOverlayOptions {
  overlaySelector?: string;
}

function defaultT(_key: string, fallback: string): string {
  return fallback;
}

/**
 * Synchronise the pre-rendered HTML shell with overrides stored in the form config
 * (title, section labels, field labels, required markers, select choices).
 */
export function syncShellDomWithConfig(
  mountNode: HTMLElement,
  formConfig: TShellFormConfig,
  t: TShellI18nResolver = defaultT,
): void {
  if (!formConfig?.sections) return;

  const headerNode = mountNode.querySelector('.template-form-header');
  const titleText =
    typeof formConfig.title === 'string' && formConfig.title.trim() !== ''
      ? formConfig.title
      : '';
  const showProjectTitle = formConfig.showProjectTitle !== false;
  const showRequiredFieldsNote = formConfig.showRequiredFieldsNote === true;
  const sectionLabelVisibility =
    formConfig.sectionLabelVisibility === 'show'
      ? 'show'
      : formConfig.sectionLabelVisibility === 'hide'
        ? 'hide'
        : 'auto';
  const customSections = Array.isArray(formConfig.sections.custom)
    ? formConfig.sections.custom
    : [];
  const showSectionHeaders =
    sectionLabelVisibility === 'show'
      ? true
      : sectionLabelVisibility === 'hide'
        ? false
        : customSections.length > 1;

  let titleNode = (headerNode?.querySelector('.template-form-title') as HTMLElement | null) ?? null;
  let subtitleNode =
    (headerNode?.querySelector('.template-form-subtitle') as HTMLElement | null) ?? null;

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

    if (showRequiredFieldsNote) {
      if (!(subtitleNode instanceof HTMLElement)) {
        subtitleNode = document.createElement('p');
        subtitleNode.className = 'template-form-subtitle';
        headerNode.appendChild(subtitleNode);
      }
      subtitleNode.textContent = t('requiredFields', '* Required fields');
      subtitleNode.style.display = '';
    } else if (subtitleNode instanceof HTMLElement) {
      subtitleNode.remove();
      subtitleNode = null;
    }

    headerNode.style.display =
      titleNode instanceof HTMLElement || subtitleNode instanceof HTMLElement ? '' : 'none';
  }

  // Sync Section Titles
  customSections.forEach((section) => {
    if (!section.name || !section.label) return;
    const sectionNode = mountNode.querySelector(`[data-section-name="${section.name}"]`);
    if (!sectionNode) return;
    const sectionHeaderNode = sectionNode.querySelector('.template-section-header');
    let sectionLabel =
      (sectionNode.querySelector('.template-section-label') as HTMLElement | null) ?? null;
    if (showSectionHeaders) {
      if (!(sectionHeaderNode instanceof HTMLElement)) {
        const header = document.createElement('header');
        header.className = 'template-section-header';
        const title = document.createElement('h2');
        title.className = 'template-section-label';
        header.appendChild(title);
        sectionNode.insertBefore(header, sectionNode.firstChild);
        sectionLabel = title;
      }
      if (sectionLabel instanceof HTMLElement) {
        sectionLabel.textContent = section.label;
      }
    } else if (sectionHeaderNode instanceof HTMLElement) {
      sectionHeaderNode.remove();
    }
  });

  // Sync Fields (labels, required markers, select choices)
  Object.keys(formConfig.sections).forEach((sectionKey) => {
    if (sectionKey === 'custom' || sectionKey === 'btngroup') return;
    const fields = (formConfig.sections as Record<string, any[]>)[sectionKey];
    if (!Array.isArray(fields)) return;

    fields.forEach((field: any) => {
      if (!field.name) return;
      const fieldNode = mountNode.querySelector(`[data-field-name="${field.name}"]`);
      if (!fieldNode) return;

      if (field.label) {
        const labelSpan = fieldNode.querySelector(
          '.template-field-label span:not(.template-required)',
        );
        if (labelSpan) labelSpan.textContent = field.label;
        const input = fieldNode.querySelector(`[name="${field.name}"]`) as HTMLElement | null;
        if (input) (input as any).dataset.label = field.label;
      }

      if (field.required !== undefined) {
        let reqSpan =
          (fieldNode.querySelector('.template-required') as HTMLElement | null) ?? null;
        const input = fieldNode.querySelector(
          `[name="${field.name}"]`,
        ) as HTMLInputElement | null;

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
        const select = fieldNode.querySelector(
          `select[name="${field.name}"]`,
        ) as HTMLSelectElement | null;
        if (select) {
          const hasEmptyFirst = select.options.length > 0 && select.options[0].value === '';
          const firstOption = hasEmptyFirst ? select.options[0].outerHTML : '';
          select.innerHTML =
            firstOption +
            (field.choices as any[])
              .map((c) => {
                const val = String(c.value ?? c.id ?? c.name ?? '');
                const lbl = String(c.label ?? c.name ?? val);
                const safeVal = val.replace(/"/g, '&quot;');
                const safeLbl = lbl.replace(/</g, '&lt;').replace(/>/g, '&gt;');
                return `<option value="${safeVal}">${safeLbl}</option>`;
              })
              .join('');
        }
      }
    });
  });
}

/**
 * Enable or disable all submit/navigation buttons within the mount node.
 */
export function setShellActionButtonsDisabled(mountNode: Element, disabled: boolean): void {
  mountNode
    .querySelectorAll(
      'button[data-step-action="back"], button[data-step-action="next"], button[type="submit"], input[type="submit"]',
    )
    .forEach((button) => {
      if (!(button instanceof HTMLButtonElement) && !(button instanceof HTMLInputElement)) return;
      button.disabled = Boolean(disabled);
      button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
    });
}

function showShellSubmitOverlay(overlay: HTMLElement | null): void {
  overlay?.setAttribute('data-active', '');
}

function hideShellSubmitOverlay(overlay: HTMLElement | null): void {
  overlay?.removeAttribute('data-active');
}

/**
 * Toggle the submit overlay in response to runtime submit events.
 * Safe to call multiple times — attaches only once per mount node.
 */
export function attachShellSubmitOverlayHandlers(
  mountNode: HTMLElement,
  options: TAttachShellSubmitOverlayOptions = {},
): void {
  if (mountNode.dataset.xpressuiShellSubmitOverlayAttached === 'true') return;
  mountNode.dataset.xpressuiShellSubmitOverlayAttached = 'true';

  const overlaySelector = options.overlaySelector ?? '[data-submit-overlay]';
  const overlay = mountNode.querySelector(overlaySelector) as HTMLElement | null;
  if (!(overlay instanceof HTMLElement)) {
    return;
  }

  mountNode.addEventListener('xpressui:submit', () => {
    showShellSubmitOverlay(overlay);
  });

  const hide = () => {
    hideShellSubmitOverlay(overlay);
  };

  mountNode.addEventListener('xpressui:submit-success', hide);
  mountNode.addEventListener('xpressui:submit-error', hide);
  mountNode.addEventListener('xpressui:submit-locked', hide);
  mountNode.addEventListener('xpressui:validation-blocked-submit', hide);
  mountNode.addEventListener('xpressui:submit-canceled', hide);
}

const SHELL_SUCCESS_HIDE_SELECTOR =
  '[data-template-zone="form_header"],' +
  '[data-template-zone="step_status"],' +
  '[data-template-zone="section"],' +
  '[data-template-zone="submit_actions"],' +
  '[data-form-step-actions]';

/**
 * Hide all form content zones so only the feedback message is visible.
 * Called automatically by hydrateForm on first submit success.
 */
export function syncShellPostSubmitUi(mountNode: Element, state: TShellFeedbackState): void {
  if (state !== 'success') return;
  mountNode.querySelectorAll(SHELL_SUCCESS_HIDE_SELECTOR).forEach((node) => {
    if (node instanceof HTMLElement) node.style.display = 'none';
  });
}

/**
 * Update the [data-submit-feedback] element's state attribute and visible text.
 */
export function setShellFeedbackState(
  mountNode: Element,
  state: TShellFeedbackState,
  message: string,
  title: string,
): void {
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

function isMeaningfulSubmitMessage(value: unknown): value is string {
  if (typeof value !== 'string') return false;
  const normalized = value.trim();
  if (!normalized) return false;
  return !/^Submit failed with status \d+\.$/.test(normalized);
}

function resolveValidationBlockedMessage(result: any): string {
  const fieldNames = Array.isArray(result?.fieldNames)
    ? result.fieldNames.filter((fieldName: unknown): fieldName is string => typeof fieldName === 'string' && fieldName.trim() !== '')
    : [];

  if (fieldNames.length === 1) {
    return `Please review the highlighted field before submitting.`;
  }

  if (fieldNames.length > 1) {
    return `Please review the highlighted fields before submitting.`;
  }

  return 'Please review the form and correct the highlighted fields before submitting.';
}

function getBlockedSubmissionTitle(): string {
  return 'Submission blocked';
}

/**
 * Pick the most informative error message from a submit result / error object.
 */
export function resolveShellSubmitErrorMessage(
  result: any,
  error: any,
  configuredErrorMessage: string,
  defaultErrorMessage: string,
): string {
  const candidates: unknown[] = [
    result?.message,
    result?.data?.message,
    result?.error,
    error?.result?.message,
    error?.result?.data?.message,
    error?.result?.error,
    error?.message,
    configuredErrorMessage,
    defaultErrorMessage,
  ];
  return candidates.find(isMeaningfulSubmitMessage) ?? defaultErrorMessage;
}

/**
 * Redirect to the configured success URL after a short delay.
 * Checks (in order): ?redirect= query param → result.redirectUrl → formConfig.workflowConfig.redirectUrl.
 */
export function handleShellSuccessRedirect(result: any, formConfig: TShellFormConfig): void {
  if (typeof window === 'undefined') return;
  const urlParams = new URLSearchParams(window.location.search);
  const redirectUrl =
    urlParams.get('redirect') ||
    (result?.redirectUrl as string | undefined) ||
    formConfig.workflowConfig?.redirectUrl;
  if (redirectUrl) {
    setTimeout(() => {
      try {
        window.top!.location.href = redirectUrl;
      } catch {
        window.location.href = redirectUrl;
      }
    }, 1500);
  }
}

/**
 * Attach listeners for XPressUI runtime events (xpressui:submit, :submit-success,
 * :submit-error, :submit-locked) and update the shell feedback UI accordingly.
 * Safe to call multiple times — attaches only once per node.
 */
export function attachShellFeedbackHandlers(
  mountNode: HTMLElement,
  formConfig: TShellFormConfig,
  options: TAttachShellFeedbackOptions = {},
): void {
  if (mountNode.dataset.xpressuiShellFeedbackAttached === 'true') return;
  mountNode.dataset.xpressuiShellFeedbackAttached = 'true';

  const t = options.t ?? defaultT;

  const cfg: TShellSubmitFeedbackConfig = formConfig.submitFeedback ?? {
    error_title: t('submissionFailedTitle', 'Submission failed'),
    error_message: t(
      'submissionFailedMessage',
      'Submission failed. Please review the form and try again.',
    ),
    loading_message: t('submitting', 'Submitting...'),
    success_title: t('submissionReceivedTitle', 'Submission received'),
    success_message: t('submissionReceivedMessage', 'Submission received.'),
    title: t('submissionStatusTitle', 'Submission status'),
  };

  const configuredSuccessMessage = cfg.success_message ?? '';
  const configuredErrorMessage = cfg.error_message ?? '';
  const defaultSuccessMessage = t('submissionReceivedMessage', 'Submission received.');
  const defaultErrorMessage = t(
    'submissionFailedMessage',
    'Submission failed. Please review the form and try again.',
  );

  mountNode.addEventListener('xpressui:submit', () => {
    setShellActionButtonsDisabled(mountNode, true);
    setShellFeedbackState(
      mountNode,
      'loading',
      cfg.loading_message ?? 'Submitting...',
      'Submitting',
    );
  });

  mountNode.addEventListener('xpressui:submit-success', (event) => {
    const result = (event as CustomEvent)?.detail?.result;
    setShellFeedbackState(
      mountNode,
      'success',
      configuredSuccessMessage || result?.message || defaultSuccessMessage,
      cfg.success_title ?? 'Submission received',
    );
    handleShellSuccessRedirect(result, formConfig);
  });

  mountNode.addEventListener('xpressui:submit-error', (event) => {
    const result = (event as CustomEvent)?.detail?.result;
    const error = (event as CustomEvent)?.detail?.error;
    setShellActionButtonsDisabled(mountNode, false);
    setShellFeedbackState(
      mountNode,
      'error',
      resolveShellSubmitErrorMessage(result, error, configuredErrorMessage, defaultErrorMessage),
      cfg.error_title ?? 'Submission failed',
    );
  });

  mountNode.addEventListener('xpressui:submit-locked', (event) => {
    const result = (event as CustomEvent)?.detail?.result;
    setShellActionButtonsDisabled(mountNode, false);
    setShellFeedbackState(
      mountNode,
      'warning',
      result?.message ?? 'Complete the required fields before submitting.',
      getBlockedSubmissionTitle(),
    );
  });

  mountNode.addEventListener('xpressui:validation-blocked-submit', (event) => {
    const result = (event as CustomEvent)?.detail?.result;
    setShellActionButtonsDisabled(mountNode, false);
    setShellFeedbackState(
      mountNode,
      'warning',
      resolveValidationBlockedMessage(result),
      getBlockedSubmissionTitle(),
    );
  });

  mountNode.addEventListener('xpressui:submit-canceled', () => {
    setShellActionButtonsDisabled(mountNode, false);
    setShellFeedbackState(
      mountNode,
      'warning',
      'Submission canceled.',
      'Submission canceled',
    );
  });
}
