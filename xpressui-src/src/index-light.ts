import './form-ui';
import * as XPressUI from './public-runtime';
import { createFormConfig } from './common/form-config-factory';
import { hydrateFormForRuntime } from './common/form-hydrate';
import { assertRuntimeCompatibility } from './common/runtime-compatibility';

export * from './public-runtime';
export { createFormConfig };
export type { TSimpleFieldInput, TSimpleFormInput } from './common/form-config-factory';

declare const __XPRESSUI_VERSION__: string;
export const VERSION = typeof __XPRESSUI_VERSION__ !== 'undefined'
    ? __XPRESSUI_VERSION__
    : '1.0.0';

export const RUNTIME_TIER = 'light';

export function hydrateForm(container: Element, input: Parameters<typeof hydrateFormForRuntime>[1]) {
    return hydrateFormForRuntime(container, input, RUNTIME_TIER);
}

export function validateLightRuntimeConfig(input: Parameters<typeof assertRuntimeCompatibility>[0]) {
    return assertRuntimeCompatibility(input, RUNTIME_TIER);
}

if (typeof window !== 'undefined') {
    (window as any).XPressUI = XPressUI;
    (window as any).hydrateForm = hydrateForm;
    (window as any).createFormConfig = createFormConfig;
    (window as any).XPressUI_RUNTIME_TIER = RUNTIME_TIER;

    window.dispatchEvent(new CustomEvent('xpressui:ready'));
}
