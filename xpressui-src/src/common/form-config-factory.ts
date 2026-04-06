import { CUSTOM_SECTION } from './Constants';
import TFieldConfig from './TFieldConfig';
import TFormConfig, {
  CONTACTFORM_TYPE,
  TFormNavigationLabels,
  TFormProviderRequest,
  TFormRule,
  TFormStepUiConfig,
  TFormStorageConfig,
  TFormSubmitRequest,
  TFormValidationConfig,
} from './TFormConfig';
import { generateRuntimeId } from './id';
import type { TFormRenderMode } from '../ui/form-ui.types';
import { createSubmitRequestFromProvider } from './provider-registry';
import { PUBLIC_FORM_SCHEMA_VERSION, validatePublicFormConfig } from './public-schema';

export type TSimpleFieldInput = Partial<TFieldConfig> & {
  type: string;
  name: string;
  label: string;
};

export type TSimpleFormInput = {
  name: string;
  title?: string;
  type?: string;
  mode?: TFormRenderMode;
  fields: TSimpleFieldInput[];
  submit?: TFormSubmitRequest;
  provider?: TFormProviderRequest;
  storage?: TFormStorageConfig;
  workflowStepTargets?: Record<string, string>;
  navigationLabels?: TFormNavigationLabels;
  stepUi?: TFormStepUiConfig;
  rules?: TFormRule[];
  validation?: TFormValidationConfig;
  sectionName?: string;
  sectionLabel?: string;
};

export function createFormConfig(input: TSimpleFormInput): TFormConfig {
  const sectionName = input.sectionName || 'main';
  const sectionLabel = input.sectionLabel || 'Main';
  const fields = input.fields.map((field) => ({ ...field }));
  const provider = input.provider;
  const submit = input.submit || (provider
    ? createSubmitRequestFromProvider(provider)
    : undefined);

  return validatePublicFormConfig({
    version: PUBLIC_FORM_SCHEMA_VERSION,
    id: generateRuntimeId(),
    uid: generateRuntimeId(),
    timestamp: Math.floor(Date.now() / 1000),
    type: input.type || CONTACTFORM_TYPE,
    mode: input.mode,
    name: input.name,
    title: input.title || input.name,
    sections: {
      [CUSTOM_SECTION]: [
        {
          type: 'section',
          name: sectionName,
          label: sectionLabel,
        },
      ],
      [sectionName]: fields,
    },
    stepSections: [
      {
        type: 'section',
        name: sectionName,
        label: sectionLabel,
      },
    ],
    workflowStepTargets: 'workflowStepTargets' in input ? (input as any).workflowStepTargets : undefined,
    submit,
    provider,
    storage: input.storage,
    validation: input.validation,
    navigationLabels: input.navigationLabels,
    stepUi: input.stepUi,
    rules: input.rules,
  });
}
