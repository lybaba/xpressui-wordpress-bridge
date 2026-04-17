import TFormConfig from './TFormConfig';
import { validatePublicFormConfig } from './public-schema';
import { createFormConfig, TSimpleFormInput } from './form-config-factory';
import {
  assertRuntimeCompatibility,
  TXPressUIRuntimeTier,
} from './runtime-compatibility';
import { attachShellFeedbackHandlers, syncShellPostSubmitUi } from './shell-dom-sync';

export type THydratableFormInput = TSimpleFormInput | TFormConfig;

type FormUIElement = HTMLElement & {
  initialize?: () => void;
  initialized?: boolean;
  __xpressuiHydrationConfig?: TFormConfig;
};

const HYDRATE_TPL_ID = 'xpressui-hydrate-tpl';

/**
 * Mount a form-ui element into `container` using the template approach (no
 * hydrate-existing attribute), which sets allowCreate=true so the runtime
 * renders all fields from the config. Used when no server-rendered <form>
 * is present in the container.
 */
function mountFormUIFromConfig(container: Element, config: TFormConfig): FormUIElement {
  const tpl = document.createElement('template') as HTMLTemplateElement;
  tpl.id = HYDRATE_TPL_ID;
  const tplForm = document.createElement('form');
  tplForm.id = `${HYDRATE_TPL_ID}_form`;
  tpl.content.appendChild(tplForm);

  const element = document.createElement('form-ui') as FormUIElement;
  element.setAttribute('name', HYDRATE_TPL_ID);
  element.__xpressuiHydrationConfig = config;

  container.appendChild(tpl);
  container.appendChild(element);
  return element;
}

export function hydrateFormForRuntime(
  container: Element,
  input: THydratableFormInput,
  runtimeTier: TXPressUIRuntimeTier = 'standard',
): HTMLElement | null {
  const config = assertRuntimeCompatibility(
    'fields' in input ? createFormConfig(input) : validatePublicFormConfig(input),
    runtimeTier,
  );

  const existingForm = container.querySelector('form') as HTMLFormElement | null;
  let element: FormUIElement;

  if (existingForm) {
    // Hydrate an existing server-rendered <form>.
    element = document.createElement('form-ui') as FormUIElement;
    if (config.mode) {
      element.setAttribute('mode', config.mode);
    }
    element.setAttribute('hydrate-existing', 'true');
    element.__xpressuiHydrationConfig = config;
    const parentNode = existingForm.parentNode;
    const nextSibling = existingForm.nextSibling;
    existingForm.remove();
    element.appendChild(existingForm);
    if (parentNode) {
      parentNode.insertBefore(element, nextSibling);
    }
  } else {
    // No server-rendered form — render from config with allowCreate=true.
    element = mountFormUIFromConfig(container, config);
  }

  if (
    'initialize' in element &&
    typeof element.initialize === 'function' &&
    !element.initialized
  ) {
    element.initialize();
  }

  // Wire up submit feedback (loading → success / error / warning) and post-submit
  // zone hiding. Safe to call multiple times — attachShellFeedbackHandlers is idempotent.
  if (container instanceof HTMLElement) {
    attachShellFeedbackHandlers(container, config);
  }
  container.addEventListener(
    'xpressui:submit-success',
    () => syncShellPostSubmitUi(container, 'success'),
    { once: true },
  );

  return element;
}

export function hydrateForm(
  container: Element,
  input: THydratableFormInput,
): HTMLElement | null {
  return hydrateFormForRuntime(container, input, 'standard');
}
