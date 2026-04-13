import './form-ui';
import * as XPressUI from './public-runtime';
import { createFormConfig } from './common/form-config-factory';
import { hydrateForm } from './common/form-hydrate';

export * from './public-runtime';
export { createFormConfig };
export type { TSimpleFieldInput, TSimpleFormInput } from './common/form-config-factory';
export { hydrateForm };

declare const __XPRESSUI_VERSION__: string;
export const VERSION = typeof __XPRESSUI_VERSION__ !== 'undefined'
    ? __XPRESSUI_VERSION__
    : '1.0.0';


// Expose the API to the global window object for browser environments
if (typeof window !== 'undefined') {
    (window as any).XPressUI = XPressUI;
    (window as any).hydrateForm = hydrateForm;
    (window as any).createFormConfig = createFormConfig;

    // Dispatch a custom event to notify that XPressUI is ready
    window.dispatchEvent(new CustomEvent('xpressui:ready'));
}
