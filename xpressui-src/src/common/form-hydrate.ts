import TFormConfig from './TFormConfig';
import { validatePublicFormConfig } from './public-schema';
import { createFormConfig, TSimpleFormInput } from './form-config-factory';
import {
  assertRuntimeCompatibility,
  TXPressUIRuntimeTier,
} from './runtime-compatibility';

export type THydratableFormInput = TSimpleFormInput | TFormConfig;

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

  if (!existingForm) {
    return null;
  }

  const element = document.createElement('form-ui') as HTMLElement & {
    initialize?: () => void;
    initialized?: boolean;
    __xpressuiHydrationConfig?: TFormConfig;
  };

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

  if (
    'initialize' in element &&
    typeof element.initialize === 'function' &&
    !element.initialized
  ) {
    element.initialize();
  }

  return element;
}

export function hydrateForm(
  container: Element,
  input: THydratableFormInput,
): HTMLElement | null {
  return hydrateFormForRuntime(container, input, 'standard');
}
