import type TFormConfig from './TFormConfig';
import type { TFormRule, TFormRuleAction, TFormRuleCondition } from './TFormConfig';
import { CUSTOM_SECTION } from './Constants';
import { resolveHydrationSubmissionEndpoint } from './export-runtime';

type THydrationRuntimeConfig = TFormConfig & {
  mode?: 'form' | 'form-multi-step';
  submit?: {
    endpoint?: string;
    method?: 'POST';
    mode?: 'json' | 'form-data';
    includeDocumentData?: boolean;
    metadata?: Record<string, string>;
  };
  rules?: TFormRule[];
};

export type TCreateExportHydrationRuntimeConfigOptions = {
  config: TFormConfig;
  projectId: string;
  projectSlug: string;
  showProjectTitle?: boolean;
  sectionLabelVisibility?: 'auto' | 'show' | 'hide';
};

const SUPPORTED_PUBLIC_RULE_ACTION_TYPES = new Set<TFormRuleAction['type']>([
  'show',
  'hide',
  'show-section',
  'hide-section',
  'enable',
  'disable',
  'set-value',
  'clear-value',
  'set-required',
  'clear-required',
  'set-error',
  'lock-submit',
]);

const SUPPORTED_PUBLIC_RULE_OPERATORS = new Set<NonNullable<TFormRuleCondition['operator']>>([
  'equals',
  'not_equals',
  'exists',
  'empty',
  'contains',
  'in',
  'gt',
  'lt',
]);

export function normalizeExportHydrationRules(rules: unknown): TFormRule[] {
  if (!Array.isArray(rules)) {
    return [];
  }

  return rules.flatMap((rule, index) => {
    if (!rule || typeof rule !== 'object') {
      return [];
    }

    const candidateRule = rule as Partial<TFormRule> & {
      id?: string;
      logic?: 'AND' | 'OR' | string;
      conditions?: unknown;
      actions?: unknown;
    };

    const conditions = Array.isArray(candidateRule.conditions)
      ? candidateRule.conditions.flatMap((condition) => {
          if (!condition || typeof condition !== 'object') {
            return [];
          }

          const nextCondition = condition as TFormRuleCondition;
          const operator = (nextCondition.operator ?? 'equals') as NonNullable<TFormRuleCondition['operator']>;
          if (!SUPPORTED_PUBLIC_RULE_OPERATORS.has(operator)) {
            return [];
          }

          return [{
            ...nextCondition,
            operator,
          } satisfies TFormRuleCondition];
        })
      : [];

    const actions = Array.isArray(candidateRule.actions)
      ? candidateRule.actions.flatMap((action) => {
          if (!action || typeof action !== 'object') {
            return [];
          }

          const nextAction = action as TFormRuleAction;
          if (!SUPPORTED_PUBLIC_RULE_ACTION_TYPES.has(nextAction.type)) {
            return [];
          }

          const requiresField = nextAction.type !== 'lock-submit';
          if (requiresField && (typeof nextAction.field !== 'string' || nextAction.field.trim() === '')) {
            return [];
          }

          return [{
            ...nextAction,
            field: requiresField ? nextAction.field.trim() : (nextAction.field ?? ''),
          } satisfies TFormRuleAction];
        })
      : [];

    if (!conditions.length || !actions.length) {
      return [];
    }

    return [{
      id: typeof candidateRule.id === 'string' && candidateRule.id.trim() !== ''
        ? candidateRule.id
        : `runtime-rule-${index + 1}`,
      logic: candidateRule.logic === 'OR' ? 'OR' : 'AND',
      conditions,
      actions,
    } satisfies TFormRule];
  });
}

export function createExportHydrationRuntimeConfig(options: TCreateExportHydrationRuntimeConfigOptions): TFormConfig {
  const runtimeConfig = options.config as THydrationRuntimeConfig;
  const customSections = options.config.sections[CUSTOM_SECTION] ?? [];
  const configuredMode = typeof runtimeConfig.mode === 'string' ? runtimeConfig.mode.trim().toLowerCase() : '';
  const runtimeMode = configuredMode === 'form' || configuredMode === 'form-multi-step'
    ? configuredMode
    : customSections.length > 1
      ? 'form-multi-step'
      : 'form';
  const sectionNames = customSections.map((section) => section.name);
  const fields = sectionNames.flatMap((sectionName) => options.config.sections[sectionName] ?? []);
  const workflowConfig = options.config.workflowConfig;
  const hasUploadFields = fields.some((field) => ['file', 'upload-image', 'camera-photo', 'qr-scan', 'document-scan'].includes(field.type));
  const endpoint = resolveHydrationSubmissionEndpoint({
    providerMode: workflowConfig?.providerMode,
    submissionEndpoint: workflowConfig?.submissionEndpoint,
  });
  const normalizedRules = normalizeExportHydrationRules(runtimeConfig.rules);

  return {
    ...runtimeConfig,
    rules: normalizedRules,
    showProjectTitle: options.showProjectTitle ?? true,
    sectionLabelVisibility: options.sectionLabelVisibility ?? 'auto',
    mode: runtimeMode,
    submit: {
      ...(runtimeConfig.submit ?? {}),
      endpoint,
      method: 'POST',
      mode: hasUploadFields ? 'form-data' : 'json',
      includeDocumentData: hasUploadFields,
      metadata: {
        ...((runtimeConfig.submit?.metadata as Record<string, string> | undefined) ?? {}),
        projectId: options.projectId,
        projectSlug: options.projectSlug,
      },
    },
  } as TFormConfig;
}
