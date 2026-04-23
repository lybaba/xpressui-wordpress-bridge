import TChoice from "./TChoice";
import TFieldConfig from "./TFieldConfig";
import {
  TConditionalRuleState,
  TMatchedRuleState,
  TResolvedConditionalState,
  getConditionalRuleState,
  markRulesChanged,
  matchesCondition,
  matchesRule,
  resolveConditionalState,
  transformRuleValue,
} from "./form-dynamic-eval";
import { normalizeRemoteChoices, syncSelectOptionChildren } from "./form-dynamic-options";

export type TFormRemoteOptionsDetail = {
  field: string;
  options: TChoice[];
  sourceField?: string;
};

export type TFormRuleAppliedDetail = {
  id?: string;
  logic?: "AND" | "OR";
  conditions: Array<{
    field: string;
    operator?: "equals" | "not_equals" | "contains" | "in" | "gt" | "lt" | "exists" | "empty";
    value?: any;
  }>;
  actions: Array<{
    type:
      | "show"
      | "hide"
      | "enable"
      | "disable"
      | "clear-value"
      | "set-value"
      | "fetch-options"
      | "set-error"
      | "lock-submit"
      | "emit-event"
      | "show-section"
      | "hide-section"
      | "set-required"
      | "clear-required";
    field: string;
    value?: any;
    message?: string;
    sourceField?: string;
    template?: string;
    transform?: "copy" | "trim" | "lowercase" | "uppercase" | "slugify";
  }>;
};

export type TFormRuleTemplateMissingFieldDetail = {
  ruleId?: string;
  field: string;
  template: string;
  missingField: string;
};

export type TFormRuleTemplateWarningClearedDetail = {
  ruleId?: string;
  field: string;
  template: string;
  previousMissingField: string;
};

export type TFormActiveTemplateWarning = {
  ruleId?: string;
  field: string;
  template: string;
  missingField: string;
};

export type TFormTemplateWarningStateDetail = {
  warnings: TFormActiveTemplateWarning[];
};

export type TFormRuleStateDetail = {
  rules: TFormRuleAppliedDetail[];
};

type TFormDynamicRuntimeOptions = {
  getFieldConfigs(): TFieldConfig[];
  getRules(): Array<{
    id?: string;
    logic?: "AND" | "OR";
    conditions: Array<{
      field: string;
      operator?: "equals" | "not_equals" | "contains" | "in" | "gt" | "lt" | "exists" | "empty";
      value?: any;
    }>;
    actions: Array<{
      type:
        | "show"
        | "hide"
        | "enable"
        | "disable"
        | "clear-value"
        | "set-value"
        | "fetch-options"
        | "set-error"
        | "lock-submit"
        | "emit-event"
        | "show-section"
        | "hide-section"
        | "set-required"
        | "clear-required";
      field: string;
      value?: any;
      message?: string;
      sourceField?: string;
      template?: string;
      transform?: "copy" | "trim" | "lowercase" | "uppercase" | "slugify";
    }>;
  }>;
  getFieldContainer(fieldName: string): HTMLElement | null;
  getFieldElement(
    fieldName: string,
  ): HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement | null;
  setFieldDisabled(fieldName: string, disabled: boolean): void;
  setFieldError?(fieldName: string, message?: string): void;
  clearFieldErrors?(): void;
  setSubmitLocked?(locked: boolean, message?: string): void;
  setFieldRequired?(fieldName: string, required: boolean): void;
  getSectionContainer?(sectionName: string): HTMLElement | null;
  getFieldValue(fieldName: string): any;
  clearFieldValue(fieldName: string): void;
  setFieldValue(fieldName: string, value: any): void;
  getFormValues(): Record<string, any>;
  emitEvent(eventName: string, detail: Record<string, any>): boolean;
  getEventContext(): { formConfig: any; submit?: any };
};

type TDynamicRule = ReturnType<TFormDynamicRuntimeOptions["getRules"]>[number];

export class FormDynamicRuntime {
  options: TFormDynamicRuntimeOptions;
  loadingOptions: Record<string, boolean>;
  activeTemplateWarnings: Record<string, TFormActiveTemplateWarning>;
  recentAppliedRules: TFormRuleAppliedDetail[];
  requiredOverrides: Record<string, boolean>;
  originalRequiredState: Record<string, boolean>;
  originalFieldVisibilityState: Record<string, boolean>;
  originalFieldDisabledState: Record<string, boolean>;
  originalSectionVisibilityState: Record<string, boolean>;

  constructor(options: TFormDynamicRuntimeOptions) {
    this.options = options;
    this.loadingOptions = {};
    this.activeTemplateWarnings = {};
    this.requiredOverrides = {};
    this.originalRequiredState = {};
    this.originalFieldVisibilityState = {};
    this.originalFieldDisabledState = {};
    this.originalSectionVisibilityState = {};
    this.recentAppliedRules = [];
  }

  // -------------------------------------------------------------------------
  // Original state capture
  // -------------------------------------------------------------------------

  getOriginalFieldVisibility(fieldName: string, container: HTMLElement): boolean {
    if (this.originalFieldVisibilityState[fieldName] === undefined) {
      this.originalFieldVisibilityState[fieldName] = container.style.display !== "none";
    }
    return this.originalFieldVisibilityState[fieldName];
  }

  getOriginalFieldDisabled(
    fieldName: string,
    fieldElement: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement,
  ): boolean {
    if (this.originalFieldDisabledState[fieldName] === undefined) {
      this.originalFieldDisabledState[fieldName] = Boolean(fieldElement.disabled);
    }
    return this.originalFieldDisabledState[fieldName];
  }

  getOriginalSectionVisibility(sectionName: string, container: HTMLElement): boolean {
    if (this.originalSectionVisibilityState[sectionName] === undefined) {
      this.originalSectionVisibilityState[sectionName] = container.style.display !== "none";
    }
    return this.originalSectionVisibilityState[sectionName];
  }

  // -------------------------------------------------------------------------
  // Delegated rule evaluation helpers
  // -------------------------------------------------------------------------

  getConditionalRuleState(
    states: Record<string, TConditionalRuleState<TDynamicRule>>,
    target: string,
  ): TConditionalRuleState<TDynamicRule> {
    return getConditionalRuleState(states, target);
  }

  resolveConditionalState(
    state: TConditionalRuleState<TDynamicRule> | undefined,
    options: {
      baseValue: boolean;
      fallbackValueWhenOnlyTrueRule: boolean;
      fallbackValueWhenOnlyFalseRule: boolean;
      fallbackValueWhenBothRuleTypes: boolean;
      preferFalseWhenConflictingMatches?: boolean;
    },
  ): TResolvedConditionalState<TDynamicRule> {
    return resolveConditionalState(state, options);
  }

  markRulesChanged(
    matchedRuleMap: Map<TDynamicRule, TMatchedRuleState<TDynamicRule>>,
    rules: TDynamicRule[],
  ): void {
    markRulesChanged(matchedRuleMap, rules);
  }

  transformRuleValue(
    value: any,
    transform?: "copy" | "trim" | "lowercase" | "uppercase" | "slugify",
  ): any {
    return transformRuleValue(value, transform);
  }

  matchesCondition(condition: Parameters<typeof matchesCondition>[0]): boolean {
    return matchesCondition(condition, this.options.getFieldValue.bind(this.options));
  }

  matchesRule(rule: Parameters<typeof matchesRule>[0]): boolean {
    return matchesRule(rule, this.options.getFieldValue.bind(this.options));
  }

  // -------------------------------------------------------------------------
  // Template rendering
  // -------------------------------------------------------------------------

  renderRuleTemplate(template: string, ruleId?: string, targetField?: string): string {
    const templateWarningKey = `${ruleId || ""}:${targetField || ""}`;
    let hasMissingField = false;
    const renderedValue = template.replace(
      /\{\{\s*([a-zA-Z0-9_-]+)\s*\}\}/g,
      (_, fieldName: string) => {
        const hasField = this.options
          .getFieldConfigs()
          .some((fieldConfig) => fieldConfig.name === fieldName);
        if (!hasField) {
          hasMissingField = true;
          const nextWarning: TFormActiveTemplateWarning = {
            ruleId,
            field: targetField || "",
            template,
            missingField: fieldName,
          };
          const previousWarning = this.activeTemplateWarnings[templateWarningKey];
          const warningChanged =
            !previousWarning ||
            previousWarning.template !== nextWarning.template ||
            previousWarning.missingField !== nextWarning.missingField;
          if (warningChanged) {
            this.activeTemplateWarnings[templateWarningKey] = nextWarning;
            const context = this.options.getEventContext();
            this.options.emitEvent("xpressui:rule-template-missing-field", {
              values: this.options.getFormValues(),
              formConfig: context.formConfig,
              submit: context.submit,
              result: {
                ruleId: nextWarning.ruleId,
                field: nextWarning.field,
                template: nextWarning.template,
                missingField: nextWarning.missingField,
              } satisfies TFormRuleTemplateMissingFieldDetail,
            });
            this.emitTemplateWarningState();
          }
          return "";
        }

        const value = this.options.getFieldValue(fieldName);
        return value === undefined || value === null ? "" : String(value);
      },
    );

    if (!hasMissingField) {
      const previousWarning = this.activeTemplateWarnings[templateWarningKey];
      if (previousWarning) {
        delete this.activeTemplateWarnings[templateWarningKey];
        const context = this.options.getEventContext();
        this.options.emitEvent("xpressui:rule-template-warning-cleared", {
          values: this.options.getFormValues(),
          formConfig: context.formConfig,
          submit: context.submit,
          result: {
            ruleId,
            field: targetField || "",
            template,
            previousMissingField: previousWarning.missingField,
          } satisfies TFormRuleTemplateWarningClearedDetail,
        });
        this.emitTemplateWarningState();
      }
    }

    return renderedValue;
  }

  // -------------------------------------------------------------------------
  // State & events
  // -------------------------------------------------------------------------

  emitRuleApplied(rule: TDynamicRule): void {
    const result: TFormRuleAppliedDetail = {
      id: rule.id,
      logic: rule.logic,
      conditions: rule.conditions,
      actions: rule.actions,
    };
    this.recentAppliedRules.push(result);
    if (this.recentAppliedRules.length > 20) {
      this.recentAppliedRules.splice(0, this.recentAppliedRules.length - 20);
    }

    const context = this.options.getEventContext();
    this.options.emitEvent("xpressui:rule-applied", {
      values: this.options.getFormValues(),
      formConfig: context.formConfig,
      submit: context.submit,
      result,
    });
    this.emitRuleState();
  }

  getActiveTemplateWarnings(): TFormActiveTemplateWarning[] {
    return Object.values(this.activeTemplateWarnings);
  }

  clearActiveTemplateWarnings(): void {
    if (!Object.keys(this.activeTemplateWarnings).length) {
      return;
    }

    this.activeTemplateWarnings = {};
    this.emitTemplateWarningState();
  }

  getRecentAppliedRules(): TFormRuleAppliedDetail[] {
    return [...this.recentAppliedRules];
  }

  clearRecentAppliedRules(): void {
    this.recentAppliedRules.splice(0, this.recentAppliedRules.length);
    this.emitRuleState();
  }

  emitTemplateWarningState(): void {
    const context = this.options.getEventContext();
    this.options.emitEvent("xpressui:rule-template-warning-state", {
      values: this.options.getFormValues(),
      formConfig: context.formConfig,
      submit: context.submit,
      result: {
        warnings: this.getActiveTemplateWarnings(),
      } satisfies TFormTemplateWarningStateDetail,
    });
  }

  emitRuleState(): void {
    const context = this.options.getEventContext();
    this.options.emitEvent("xpressui:rule-state", {
      values: this.options.getFormValues(),
      formConfig: context.formConfig,
      submit: context.submit,
      result: {
        rules: this.getRecentAppliedRules(),
      } satisfies TFormRuleStateDetail,
    });
  }

  // -------------------------------------------------------------------------
  // Main rule application loop
  // -------------------------------------------------------------------------

  updateConditionalFields(): void {
    const visibilityOverrides: Record<string, boolean> = {};
    const requiredOverrides: Record<string, boolean> = {};
    let submitLocked = false;
    let submitLockMessage: string | undefined;
    const fieldVisibilityStates: Record<string, TConditionalRuleState<TDynamicRule>> = {};
    const fieldEnabledStates: Record<string, TConditionalRuleState<TDynamicRule>> = {};
    const sectionVisibilityStates: Record<string, TConditionalRuleState<TDynamicRule>> = {};
    const requiredStates: Record<string, TConditionalRuleState<TDynamicRule>> = {};
    const matchedRuleMap = new Map<TDynamicRule, TMatchedRuleState<TDynamicRule>>();

    this.options.clearFieldErrors?.();
    this.options.setSubmitLocked?.(false);

    this.options.getFieldConfigs().forEach((fieldConfig) => {
      if (!fieldConfig.visibleWhenField) {
        return;
      }

      const container = this.options.getFieldContainer(fieldConfig.name);
      const fieldElement = this.options.getFieldElement(fieldConfig.name);
      if (!container || !fieldElement) {
        return;
      }

      const currentValue = this.options.getFieldValue(fieldConfig.visibleWhenField);
      const expectedValue = fieldConfig.visibleWhenEquals;
      const isVisible =
        expectedValue === undefined
          ? Boolean(currentValue)
          : String(currentValue ?? "") === String(expectedValue);
      visibilityOverrides[fieldConfig.name] = isVisible;
    });

    this.options.getRules().forEach((rule) => {
      rule.actions.forEach((action) => {
        switch (action.type) {
          case "show":
            getConditionalRuleState(fieldVisibilityStates, action.field).hasTrueRule = true;
            break;
          case "hide":
            getConditionalRuleState(fieldVisibilityStates, action.field).hasFalseRule = true;
            break;
          case "enable":
            getConditionalRuleState(fieldEnabledStates, action.field).hasTrueRule = true;
            break;
          case "disable":
            getConditionalRuleState(fieldEnabledStates, action.field).hasFalseRule = true;
            break;
          case "show-section":
            getConditionalRuleState(sectionVisibilityStates, action.field).hasTrueRule = true;
            break;
          case "hide-section":
            getConditionalRuleState(sectionVisibilityStates, action.field).hasFalseRule = true;
            break;
          case "set-required":
            getConditionalRuleState(requiredStates, action.field).hasTrueRule = true;
            break;
          case "clear-required":
            getConditionalRuleState(requiredStates, action.field).hasFalseRule = true;
            break;
        }
      });

      if (!rule.conditions.length || !rule.actions.length || !matchesRule(rule, this.options.getFieldValue.bind(this.options))) {
        return;
      }

      const matchedRule = { rule, changed: false };
      matchedRuleMap.set(rule, matchedRule);

      rule.actions.forEach((action) => {
        switch (action.type) {
          case "show":
            getConditionalRuleState(fieldVisibilityStates, action.field).matchedTrueRules.push(rule);
            break;
          case "hide":
            getConditionalRuleState(fieldVisibilityStates, action.field).matchedFalseRules.push(rule);
            break;
          case "enable":
            getConditionalRuleState(fieldEnabledStates, action.field).matchedTrueRules.push(rule);
            break;
          case "disable":
            getConditionalRuleState(fieldEnabledStates, action.field).matchedFalseRules.push(rule);
            break;
          case "show-section":
            getConditionalRuleState(sectionVisibilityStates, action.field).matchedTrueRules.push(rule);
            break;
          case "hide-section":
            getConditionalRuleState(sectionVisibilityStates, action.field).matchedFalseRules.push(rule);
            break;
          case "set-required":
            getConditionalRuleState(requiredStates, action.field).matchedTrueRules.push(rule);
            break;
          case "clear-required":
            getConditionalRuleState(requiredStates, action.field).matchedFalseRules.push(rule);
            break;
          case "clear-value":
            if (this.options.getFieldValue(action.field) !== undefined) {
              matchedRule.changed = true;
            }
            this.options.clearFieldValue(action.field);
            break;
          case "set-value": {
            const nextValue =
              action.template !== undefined
                ? this.renderRuleTemplate(action.template, rule.id, action.field)
                : action.sourceField
                  ? this.options.getFieldValue(action.sourceField)
                  : action.value;
            const transformedValue = transformRuleValue(nextValue, action.transform);
            if (!Object.is(this.options.getFieldValue(action.field), transformedValue)) {
              matchedRule.changed = true;
            }
            this.options.setFieldValue(action.field, transformedValue);
            break;
          }
          case "fetch-options": {
            const fieldConfig = this.options
              .getFieldConfigs()
              .find(
                (candidate) =>
                  candidate.name === action.field && Boolean(candidate.optionsEndpoint),
              );
            if (fieldConfig && !this.loadingOptions[fieldConfig.name]) {
              matchedRule.changed = true;
              void this.fetchOptionsForField(action.field);
            }
            break;
          }
          case "set-error": {
            const nextMessage = action.message || action.value;
            if (nextMessage !== undefined) {
              matchedRule.changed = true;
              this.options.setFieldError?.(action.field, String(nextMessage));
            }
            break;
          }
          case "lock-submit":
            submitLocked = true;
            submitLockMessage =
              action.message || (action.value !== undefined ? String(action.value) : undefined);
            matchedRule.changed = true;
            break;
          case "emit-event": {
            const eventName = String(action.field || "").trim();
            if (!eventName) break;
            const payload =
              action.template !== undefined
                ? this.renderRuleTemplate(action.template, rule.id, action.field)
                : action.sourceField
                  ? this.options.getFieldValue(action.sourceField)
                  : action.value;
            const context = this.options.getEventContext();
            this.options.emitEvent(eventName, {
              values: this.options.getFormValues(),
              formConfig: context.formConfig,
              submit: context.submit,
              result: {
                ruleId: rule.id,
                action: "emit-event",
                eventName,
                payload,
              },
            });
            matchedRule.changed = true;
            break;
          }
        }
      });
    });

    this.options.getFieldConfigs().forEach((fieldConfig) => {
      const container = this.options.getFieldContainer(fieldConfig.name);
      const fieldElement = this.options.getFieldElement(fieldConfig.name);
      if (!container || !fieldElement) {
        return;
      }

      const baseVisible =
        visibilityOverrides[fieldConfig.name] ??
        this.getOriginalFieldVisibility(fieldConfig.name, container);
      const baseEnabled = !this.getOriginalFieldDisabled(fieldConfig.name, fieldElement);
      const resolvedVisibility = resolveConditionalState(
        fieldVisibilityStates[fieldConfig.name],
        {
          baseValue: baseVisible,
          fallbackValueWhenOnlyTrueRule: false,
          fallbackValueWhenOnlyFalseRule: true,
          fallbackValueWhenBothRuleTypes: false,
        },
      );
      const wasVisible = container.style.display !== "none";
      const hadValue = this.options.getFieldValue(fieldConfig.name) !== undefined;
      container.style.display = resolvedVisibility.value ? "" : "none";
      fieldElement.disabled = !resolvedVisibility.value;
      if (!resolvedVisibility.value) {
        this.options.clearFieldValue(fieldConfig.name);
      }
      if (wasVisible !== resolvedVisibility.value || (!resolvedVisibility.value && hadValue)) {
        markRulesChanged(matchedRuleMap, resolvedVisibility.winningRules);
      }

      const resolvedEnabled = resolveConditionalState(fieldEnabledStates[fieldConfig.name], {
        baseValue: baseEnabled,
        fallbackValueWhenOnlyTrueRule: false,
        fallbackValueWhenOnlyFalseRule: true,
        fallbackValueWhenBothRuleTypes: false,
      });
      const nextDisabled = !resolvedEnabled.value || !resolvedVisibility.value;
      if (!Object.is(fieldElement.disabled, nextDisabled)) {
        markRulesChanged(matchedRuleMap, resolvedEnabled.winningRules);
      }
      this.options.setFieldDisabled(fieldConfig.name, nextDisabled);
    });

    Object.keys(sectionVisibilityStates).forEach((sectionName) => {
      const container = this.options.getSectionContainer?.(sectionName);
      if (!container) {
        return;
      }

      const resolvedVisibility = resolveConditionalState(sectionVisibilityStates[sectionName], {
        baseValue: this.getOriginalSectionVisibility(sectionName, container),
        fallbackValueWhenOnlyTrueRule: false,
        fallbackValueWhenOnlyFalseRule: true,
        fallbackValueWhenBothRuleTypes: false,
      });
      const wasVisible = container.style.display !== "none";
      container.style.display = resolvedVisibility.value ? "" : "none";
      if (wasVisible !== resolvedVisibility.value) {
        markRulesChanged(matchedRuleMap, resolvedVisibility.winningRules);
      }
    });

    Object.keys(requiredStates).forEach((fieldName) => {
      const state = requiredStates[fieldName];
      if (this.originalRequiredState[fieldName] === undefined) {
        const fieldConfig = this.options
          .getFieldConfigs()
          .find((candidate) => candidate.name === fieldName);
        this.originalRequiredState[fieldName] = Boolean(fieldConfig?.required);
      }
      const resolvedRequired = resolveConditionalState(state, {
        baseValue: this.originalRequiredState[fieldName] ?? false,
        fallbackValueWhenOnlyTrueRule: this.originalRequiredState[fieldName] ?? false,
        fallbackValueWhenOnlyFalseRule: this.originalRequiredState[fieldName] ?? false,
        fallbackValueWhenBothRuleTypes: this.originalRequiredState[fieldName] ?? false,
        preferFalseWhenConflictingMatches: false,
      });
      requiredOverrides[fieldName] = resolvedRequired.value;
    });

    const allRequiredFields = new Set([
      ...Object.keys(requiredOverrides),
      ...Object.keys(this.requiredOverrides),
    ]);
    allRequiredFields.forEach((fieldName) => {
      const newRequired = requiredOverrides[fieldName];
      const prevRequired = this.requiredOverrides[fieldName];
      if (newRequired === prevRequired) return;

      let effectiveRequired: boolean;
      if (newRequired !== undefined) {
        if (this.originalRequiredState[fieldName] === undefined) {
          const fieldConfig = this.options
            .getFieldConfigs()
            .find((f) => f.name === fieldName);
          this.originalRequiredState[fieldName] = Boolean(fieldConfig?.required);
        }
        effectiveRequired = newRequired;
      } else {
        effectiveRequired = this.originalRequiredState[fieldName] ?? false;
      }
      this.options.setFieldRequired?.(fieldName, effectiveRequired);
      if (newRequired !== undefined) {
        markRulesChanged(matchedRuleMap, requiredStates[fieldName]?.matchedTrueRules ?? []);
        markRulesChanged(matchedRuleMap, requiredStates[fieldName]?.matchedFalseRules ?? []);
      }
    });
    this.requiredOverrides = requiredOverrides;

    Array.from(matchedRuleMap.values())
      .filter((matchedRule) => matchedRule.changed)
      .forEach((matchedRule) => this.emitRuleApplied(matchedRule.rule));

    this.options.setSubmitLocked?.(submitLocked, submitLockMessage);
  }

  // -------------------------------------------------------------------------
  // Remote options
  // -------------------------------------------------------------------------

  normalizeRemoteChoices(payload: any, fieldConfig: TFieldConfig): TChoice[] {
    return normalizeRemoteChoices(payload, fieldConfig);
  }

  syncSelectOptionChildren(fieldElement: HTMLSelectElement, options: TChoice[]): void {
    syncSelectOptionChildren(fieldElement, options);
  }

  populateSelectOptions(fieldName: string, options: TChoice[], sourceField?: string): void {
    const fieldElement = this.options.getFieldElement(fieldName);
    if (!(fieldElement instanceof HTMLSelectElement)) {
      return;
    }

    const currentValue = fieldElement.multiple
      ? Array.from(fieldElement.selectedOptions).map((option) => option.value)
      : fieldElement.value;
    syncSelectOptionChildren(fieldElement, options);

    if (fieldElement.multiple && Array.isArray(currentValue)) {
      Array.from(fieldElement.options).forEach((option) => {
        option.selected = currentValue.includes(option.value);
      });
    } else if (
      typeof currentValue === "string" &&
      currentValue &&
      options.some((choice) => choice.value === currentValue)
    ) {
      fieldElement.value = currentValue;
    }

    const context = this.options.getEventContext();
    this.options.emitEvent("xpressui:options-loaded", {
      values: this.options.getFormValues(),
      formConfig: context.formConfig,
      submit: context.submit,
      result: {
        field: fieldName,
        options,
        sourceField,
      } satisfies TFormRemoteOptionsDetail,
    });
  }

  async fetchOptionsForField(fieldName: string, sourceFieldName?: string): Promise<void> {
    const fieldConfig = this.options
      .getFieldConfigs()
      .find((candidate) => candidate.name === fieldName && Boolean(candidate.optionsEndpoint));
    if (!fieldConfig) {
      return;
    }

    if (this.loadingOptions[fieldConfig.name]) {
      return;
    }

    const dependencyValue = fieldConfig.optionsDependsOn
      ? this.options.getFieldValue(fieldConfig.optionsDependsOn)
      : undefined;

    if (fieldConfig.optionsDependsOn && !dependencyValue) {
      this.populateSelectOptions(
        fieldConfig.name,
        [],
        sourceFieldName || fieldConfig.optionsDependsOn,
      );
      return;
    }

    this.loadingOptions[fieldConfig.name] = true;
    try {
      let url = fieldConfig.optionsEndpoint as string;
      if (fieldConfig.optionsDependsOn) {
        const query = new URLSearchParams({
          [fieldConfig.optionsDependsOn]: String(dependencyValue),
        }).toString();
        url += (url.includes("?") ? "&" : "?") + query;
      }

      const response = await fetch(url);
      const payload = await response.json();
      const options = normalizeRemoteChoices(payload, fieldConfig);
      this.populateSelectOptions(fieldConfig.name, options, sourceFieldName);
    } catch {
      this.populateSelectOptions(fieldConfig.name, [], sourceFieldName);
    } finally {
      this.loadingOptions[fieldConfig.name] = false;
    }
  }

  async refreshRemoteOptions(sourceFieldName?: string): Promise<void> {
    const fieldConfigs = this.options.getFieldConfigs().filter(
      (fieldConfig) =>
        Boolean(fieldConfig.optionsEndpoint) &&
        (!sourceFieldName || fieldConfig.optionsDependsOn === sourceFieldName),
    );

    await Promise.all(
      fieldConfigs.map((fieldConfig) =>
        this.fetchOptionsForField(fieldConfig.name, sourceFieldName),
      ),
    );
  }
}
