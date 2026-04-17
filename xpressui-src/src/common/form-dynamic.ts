import slugify from "slugify";
import TChoice from "./TChoice";
import TFieldConfig from "./TFieldConfig";

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
  getFieldContainer(
    fieldName: string,
  ): HTMLElement | null;
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
type TMatchedRuleState = { rule: TDynamicRule; changed: boolean };
type TConditionalRuleState = {
  matchedTrueRules: TDynamicRule[];
  matchedFalseRules: TDynamicRule[];
  hasTrueRule: boolean;
  hasFalseRule: boolean;
};
type TResolvedConditionalState = {
  value: boolean;
  winningRules: TDynamicRule[];
};

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

  getOriginalFieldVisibility(fieldName: string, container: HTMLElement): boolean {
    if (this.originalFieldVisibilityState[fieldName] === undefined) {
      this.originalFieldVisibilityState[fieldName] = container.style.display !== "none";
    }
    return this.originalFieldVisibilityState[fieldName];
  }

  getOriginalFieldDisabled(fieldName: string, fieldElement: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement): boolean {
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

  getConditionalRuleState(
    states: Record<string, TConditionalRuleState>,
    target: string,
  ): TConditionalRuleState {
    if (!states[target]) {
      states[target] = {
        matchedTrueRules: [],
        matchedFalseRules: [],
        hasTrueRule: false,
        hasFalseRule: false,
      };
    }
    return states[target];
  }

  resolveConditionalState(
    state: TConditionalRuleState | undefined,
    options: {
      baseValue: boolean;
      fallbackValueWhenOnlyTrueRule: boolean;
      fallbackValueWhenOnlyFalseRule: boolean;
      fallbackValueWhenBothRuleTypes: boolean;
      preferFalseWhenConflictingMatches?: boolean;
    },
  ): TResolvedConditionalState {
    if (!state) {
      return {
        value: options.baseValue,
        winningRules: [],
      };
    }

    const preferFalse = options.preferFalseWhenConflictingMatches !== false;
    if (state.matchedTrueRules.length && state.matchedFalseRules.length) {
      return preferFalse
        ? { value: false, winningRules: state.matchedFalseRules }
        : { value: true, winningRules: state.matchedTrueRules };
    }

    if (state.matchedFalseRules.length) {
      return {
        value: false,
        winningRules: state.matchedFalseRules,
      };
    }

    if (state.matchedTrueRules.length) {
      return {
        value: true,
        winningRules: state.matchedTrueRules,
      };
    }

    if (state.hasTrueRule && state.hasFalseRule) {
      return {
        value: options.fallbackValueWhenBothRuleTypes,
        winningRules: [],
      };
    }

    if (state.hasTrueRule) {
      return {
        value: options.fallbackValueWhenOnlyTrueRule,
        winningRules: [],
      };
    }

    if (state.hasFalseRule) {
      return {
        value: options.fallbackValueWhenOnlyFalseRule,
        winningRules: [],
      };
    }

    return {
      value: options.baseValue,
      winningRules: [],
    };
  }

  markRulesChanged(
    matchedRuleMap: Map<TDynamicRule, TMatchedRuleState>,
    rules: TDynamicRule[],
  ): void {
    rules.forEach((rule) => {
      const matchedRule = matchedRuleMap.get(rule);
      if (matchedRule) {
        matchedRule.changed = true;
      }
    });
  }

  transformRuleValue(
    value: any,
    transform?: "copy" | "trim" | "lowercase" | "uppercase" | "slugify",
  ): any {
    const nextTransform = transform || "copy";
    if (nextTransform === "copy" || value === undefined || value === null) {
      return value;
    }

    const normalizedValue = String(value);
    if (nextTransform === "trim") {
      return normalizedValue.trim();
    }

    if (nextTransform === "lowercase") {
      return normalizedValue.toLowerCase();
    }

    if (nextTransform === "uppercase") {
      return normalizedValue.toUpperCase();
    }

    if (nextTransform === "slugify") {
      return slugify(normalizedValue, {
        lower: true,
        strict: true,
        trim: true,
      });
    }

    return value;
  }

  renderRuleTemplate(template: string, ruleId?: string, targetField?: string): string {
    const templateWarningKey = `${ruleId || ""}:${targetField || ""}`;
    let hasMissingField = false;
    const renderedValue = template.replace(/\{\{\s*([a-zA-Z0-9_-]+)\s*\}\}/g, (_, fieldName: string) => {
      const hasField = this.options.getFieldConfigs().some((fieldConfig) => fieldConfig.name === fieldName);
      if (!hasField) {
        hasMissingField = true;
        const nextWarning: TFormActiveTemplateWarning = {
          ruleId,
          field: targetField || "",
          template,
          missingField: fieldName,
        };
        const previousWarning = this.activeTemplateWarnings[templateWarningKey];
        const warningChanged = !previousWarning
          || previousWarning.template !== nextWarning.template
          || previousWarning.missingField !== nextWarning.missingField;
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
    });

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

  matchesCondition(
    condition: {
      field: string;
      operator?: "equals" | "not_equals" | "contains" | "in" | "gt" | "lt" | "exists" | "empty";
      value?: any;
    },
  ): boolean {
    const currentValue = this.options.getFieldValue(condition.field);
    const operator = condition.operator || "equals";

    if (operator === "exists") {
      if (Array.isArray(currentValue)) {
        return currentValue.length > 0;
      }

      return !(
        currentValue === undefined ||
        currentValue === null ||
        (typeof currentValue === "string" && currentValue.trim() === "")
      );
    }

    if (operator === "empty") {
      if (Array.isArray(currentValue)) {
        return currentValue.length === 0;
      }

      return (
        currentValue === undefined ||
        currentValue === null ||
        (typeof currentValue === "string" && currentValue.trim() === "")
      );
    }

    if (operator === "contains") {
      return String(currentValue ?? "")
        .toLowerCase()
        .includes(String(condition.value ?? "").toLowerCase());
    }

    if (operator === "in") {
      const allowedValues = Array.isArray(condition.value)
        ? condition.value
        : [condition.value];
      return allowedValues.map((value) => String(value ?? "")).includes(String(currentValue ?? ""));
    }

    if (operator === "gt") {
      return Number(currentValue) > Number(condition.value);
    }

    if (operator === "lt") {
      return Number(currentValue) < Number(condition.value);
    }

    if (operator === "not_equals") {
      return String(currentValue ?? "") !== String(condition.value ?? "");
    }

    return String(currentValue ?? "") === String(condition.value ?? "");
  }

  matchesRule(
    rule: {
      logic?: "AND" | "OR";
      conditions: Array<{
        field: string;
        operator?: "equals" | "not_equals" | "contains" | "in" | "gt" | "lt" | "exists" | "empty";
        value?: any;
      }>;
    },
  ): boolean {
    const logic = rule.logic || "AND";
    const matches = rule.conditions.map((condition) => this.matchesCondition(condition));
    return logic === "OR" ? matches.some(Boolean) : matches.every(Boolean);
  }

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
    const warningKeys = Object.keys(this.activeTemplateWarnings);
    if (!warningKeys.length) {
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

  updateConditionalFields(): void {
    const visibilityOverrides: Record<string, boolean> = {};
    const requiredOverrides: Record<string, boolean> = {};
    let submitLocked = false;
    let submitLockMessage: string | undefined;
    const fieldVisibilityStates: Record<string, TConditionalRuleState> = {};
    const fieldEnabledStates: Record<string, TConditionalRuleState> = {};
    const sectionVisibilityStates: Record<string, TConditionalRuleState> = {};
    const requiredStates: Record<string, TConditionalRuleState> = {};
    const matchedRuleMap = new Map<TDynamicRule, TMatchedRuleState>();

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
      const isVisible = expectedValue === undefined
        ? Boolean(currentValue)
        : String(currentValue ?? "") === String(expectedValue);
      visibilityOverrides[fieldConfig.name] = isVisible;
    });

    this.options.getRules().forEach((rule) => {
      rule.actions.forEach((action) => {
        switch (action.type) {
          case "show":
            this.getConditionalRuleState(fieldVisibilityStates, action.field).hasTrueRule = true;
            break;
          case "hide":
            this.getConditionalRuleState(fieldVisibilityStates, action.field).hasFalseRule = true;
            break;
          case "enable":
            this.getConditionalRuleState(fieldEnabledStates, action.field).hasTrueRule = true;
            break;
          case "disable":
            this.getConditionalRuleState(fieldEnabledStates, action.field).hasFalseRule = true;
            break;
          case "show-section":
            this.getConditionalRuleState(sectionVisibilityStates, action.field).hasTrueRule = true;
            break;
          case "hide-section":
            this.getConditionalRuleState(sectionVisibilityStates, action.field).hasFalseRule = true;
            break;
          case "set-required":
            this.getConditionalRuleState(requiredStates, action.field).hasTrueRule = true;
            break;
          case "clear-required":
            this.getConditionalRuleState(requiredStates, action.field).hasFalseRule = true;
            break;
        }
      });

      if (!rule.conditions.length || !rule.actions.length || !this.matchesRule(rule)) {
        return;
      }

      const matchedRule = { rule, changed: false };
      matchedRuleMap.set(rule, matchedRule);

      rule.actions.forEach((action) => {
        switch (action.type) {
          case "show":
            this.getConditionalRuleState(fieldVisibilityStates, action.field).matchedTrueRules.push(rule);
            break;
          case "hide":
            this.getConditionalRuleState(fieldVisibilityStates, action.field).matchedFalseRules.push(rule);
            break;
          case "enable":
            this.getConditionalRuleState(fieldEnabledStates, action.field).matchedTrueRules.push(rule);
            break;
          case "disable":
            this.getConditionalRuleState(fieldEnabledStates, action.field).matchedFalseRules.push(rule);
            break;
          case "show-section":
            this.getConditionalRuleState(sectionVisibilityStates, action.field).matchedTrueRules.push(rule);
            break;
          case "hide-section":
            this.getConditionalRuleState(sectionVisibilityStates, action.field).matchedFalseRules.push(rule);
            break;
          case "set-required":
            this.getConditionalRuleState(requiredStates, action.field).matchedTrueRules.push(rule);
            break;
          case "clear-required":
            this.getConditionalRuleState(requiredStates, action.field).matchedFalseRules.push(rule);
            break;
          case "clear-value":
            if (this.options.getFieldValue(action.field) !== undefined) {
              matchedRule.changed = true;
            }
            this.options.clearFieldValue(action.field);
            break;
          case "set-value": {
            const nextValue = action.template !== undefined
              ? this.renderRuleTemplate(action.template, rule.id, action.field)
              : action.sourceField
                ? this.options.getFieldValue(action.sourceField)
                : action.value;
            const transformedValue = this.transformRuleValue(nextValue, action.transform);
            if (!Object.is(this.options.getFieldValue(action.field), transformedValue)) {
              matchedRule.changed = true;
            }
            this.options.setFieldValue(action.field, transformedValue);
            break;
          }
          case "fetch-options": {
            const fieldConfig = this.options
              .getFieldConfigs()
              .find((candidate) => candidate.name === action.field && Boolean(candidate.optionsEndpoint));
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
            submitLockMessage = action.message
              || (action.value !== undefined ? String(action.value) : undefined);
            matchedRule.changed = true;
            break;
          case "emit-event": {
            const eventName = String(action.field || "").trim();
            if (!eventName) break;
            const payload = action.template !== undefined
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

      const baseVisible = visibilityOverrides[fieldConfig.name]
        ?? this.getOriginalFieldVisibility(fieldConfig.name, container);
      const baseEnabled = !this.getOriginalFieldDisabled(fieldConfig.name, fieldElement);
      const resolvedVisibility = this.resolveConditionalState(fieldVisibilityStates[fieldConfig.name], {
        baseValue: baseVisible,
        fallbackValueWhenOnlyTrueRule: false,
        fallbackValueWhenOnlyFalseRule: true,
        fallbackValueWhenBothRuleTypes: false,
      });
      const wasVisible = container.style.display !== "none";
      const hadValue = this.options.getFieldValue(fieldConfig.name) !== undefined;
      container.style.display = resolvedVisibility.value ? "" : "none";
      fieldElement.disabled = !resolvedVisibility.value;
      if (!resolvedVisibility.value) {
        this.options.clearFieldValue(fieldConfig.name);
      }
      if (wasVisible !== resolvedVisibility.value || (!resolvedVisibility.value && hadValue)) {
        this.markRulesChanged(matchedRuleMap, resolvedVisibility.winningRules);
      }

      const resolvedEnabled = this.resolveConditionalState(fieldEnabledStates[fieldConfig.name], {
        baseValue: baseEnabled,
        fallbackValueWhenOnlyTrueRule: false,
        fallbackValueWhenOnlyFalseRule: true,
        fallbackValueWhenBothRuleTypes: false,
      });
      const nextDisabled = !resolvedEnabled.value || !resolvedVisibility.value;
      if (!Object.is(fieldElement.disabled, nextDisabled)) {
        this.markRulesChanged(matchedRuleMap, resolvedEnabled.winningRules);
      }
      this.options.setFieldDisabled(fieldConfig.name, nextDisabled);
    });

    Object.keys(sectionVisibilityStates).forEach((sectionName) => {
      const container = this.options.getSectionContainer?.(sectionName);
      if (!container) {
        return;
      }

      const resolvedVisibility = this.resolveConditionalState(sectionVisibilityStates[sectionName], {
        baseValue: this.getOriginalSectionVisibility(sectionName, container),
        fallbackValueWhenOnlyTrueRule: false,
        fallbackValueWhenOnlyFalseRule: true,
        fallbackValueWhenBothRuleTypes: false,
      });
      const wasVisible = container.style.display !== "none";
      container.style.display = resolvedVisibility.value ? "" : "none";
      if (wasVisible !== resolvedVisibility.value) {
        this.markRulesChanged(matchedRuleMap, resolvedVisibility.winningRules);
      }
    });

    Object.keys(requiredStates).forEach((fieldName) => {
      const state = requiredStates[fieldName];
      if (this.originalRequiredState[fieldName] === undefined) {
        const fieldConfig = this.options.getFieldConfigs().find((candidate) => candidate.name === fieldName);
        this.originalRequiredState[fieldName] = Boolean(fieldConfig?.required);
      }
      const resolvedRequired = this.resolveConditionalState(state, {
        baseValue: this.originalRequiredState[fieldName] ?? false,
        fallbackValueWhenOnlyTrueRule: this.originalRequiredState[fieldName] ?? false,
        fallbackValueWhenOnlyFalseRule: this.originalRequiredState[fieldName] ?? false,
        fallbackValueWhenBothRuleTypes: this.originalRequiredState[fieldName] ?? false,
        preferFalseWhenConflictingMatches: false,
      });
      requiredOverrides[fieldName] = resolvedRequired.value;
    });

    // Apply required overrides — diff against previous state to avoid redundant DOM/validator work
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
          const fieldConfig = this.options.getFieldConfigs().find((f) => f.name === fieldName);
          this.originalRequiredState[fieldName] = Boolean(fieldConfig?.required);
        }
        effectiveRequired = newRequired;
      } else {
        effectiveRequired = this.originalRequiredState[fieldName] ?? false;
      }
      this.options.setFieldRequired?.(fieldName, effectiveRequired);
      if (newRequired !== undefined) {
        this.markRulesChanged(matchedRuleMap, requiredStates[fieldName]?.matchedTrueRules ?? []);
        this.markRulesChanged(matchedRuleMap, requiredStates[fieldName]?.matchedFalseRules ?? []);
      }
    });
    this.requiredOverrides = requiredOverrides;

    Array.from(matchedRuleMap.values())
      .filter((matchedRule) => matchedRule.changed)
      .forEach((matchedRule) => this.emitRuleApplied(matchedRule.rule));

    this.options.setSubmitLocked?.(submitLocked, submitLockMessage);
  }

  normalizeRemoteChoices(payload: any, fieldConfig: TFieldConfig): TChoice[] {
    const optionList = Array.isArray(payload)
      ? payload
      : Array.isArray(payload?.items)
        ? payload.items
        : Array.isArray(payload?.options)
          ? payload.options
          : [];
    const labelKey = fieldConfig.optionsLabelKey || "label";
    const valueKey = fieldConfig.optionsValueKey || "value";

    return optionList
      .map((item: any) => {
        if (typeof item === "string") {
          return { value: item, label: item };
        }

        return {
          value: String(item?.[valueKey] ?? item?.value ?? ""),
          label: String(item?.[labelKey] ?? item?.label ?? item?.[valueKey] ?? ""),
        };
      })
      .filter((choice: TChoice) => Boolean(choice.value));
  }

  syncSelectOptionChildren(
    fieldElement: HTMLSelectElement,
    options: TChoice[],
  ): void {
    const desiredOptions = fieldElement.multiple
      ? options
      : [{ value: "", label: "" }, ...options];
    const desiredValues = new Set(desiredOptions.map((choice) => choice.value));

    desiredOptions.forEach((choice, index) => {
      let option = Array.from(fieldElement.options).find((candidate) => candidate.value === choice.value) || null;
      if (!option) {
        option = document.createElement("option");
      }

      option.value = choice.value;
      option.textContent = choice.label;
      const currentOptionAtIndex = fieldElement.options[index] || null;
      if (currentOptionAtIndex !== option) {
        fieldElement.insertBefore(option, currentOptionAtIndex);
      }
    });

    Array.from(fieldElement.options).forEach((option) => {
      if (!desiredValues.has(option.value)) {
        option.remove();
      }
    });
  }

  populateSelectOptions(fieldName: string, options: TChoice[], sourceField?: string): void {
    const fieldElement = this.options.getFieldElement(fieldName);
    if (!(fieldElement instanceof HTMLSelectElement)) {
      return;
    }

    const currentValue = fieldElement.multiple
      ? Array.from(fieldElement.selectedOptions).map((option) => option.value)
      : fieldElement.value;
    this.syncSelectOptionChildren(fieldElement, options);

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
      this.populateSelectOptions(fieldConfig.name, [], sourceFieldName || fieldConfig.optionsDependsOn);
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
      const options = this.normalizeRemoteChoices(payload, fieldConfig);
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
      fieldConfigs.map((fieldConfig) => this.fetchOptionsForField(fieldConfig.name, sourceFieldName))
    );
  }
}
