import { CUSTOM_SECTION } from "./Constants";
import TFieldConfig, { TStepTransition, TStepTransitionCondition } from "./TFieldConfig";
import TFormConfig from "./TFormConfig";

export type TFormStepProgress = {
  stepIndex: number;
  stepNumber: number;
  stepCount: number;
  percent: number;
};

export type TFormWorkflowSnapshot = {
  workflowState: string;
  currentStepName: string | null;
  currentStepIndex: number;
  progress: TFormStepProgress;
  nextStepTarget: string | null;
};

export class FormStepRuntime {
  formConfig: TFormConfig | null;
  stepNames: string[];
  currentStepIndex: number;
  workflowState: string;

  constructor(formConfig: TFormConfig | null = null) {
    this.formConfig = null;
    this.stepNames = [];
    this.currentStepIndex = 0;
    this.workflowState = "draft";
    this.setFormConfig(formConfig);
  }

  setFormConfig(formConfig: TFormConfig | null): void {
    this.formConfig = formConfig;
    this.stepNames = (formConfig?.stepSections || formConfig?.sections?.[CUSTOM_SECTION] || [])
      .map((section) => section.name)
      .filter(Boolean);
    this.currentStepIndex = 0;
  }

  getStepNames(): string[] {
    return [...this.stepNames];
  }

  getCurrentStepIndex(): number {
    return this.currentStepIndex;
  }

  getCurrentStepName(): string | null {
    return this.stepNames[this.currentStepIndex] || null;
  }

  setCurrentStepIndex(index: number): boolean {
    if (index < 0 || index >= this.stepNames.length) {
      return false;
    }

    this.currentStepIndex = index;
    return true;
  }

  getStepProgress(): TFormStepProgress {
    const stepCount = Math.max(this.stepNames.length, 1);
    const stepNumber = Math.min(this.currentStepIndex + 1, stepCount);
    return {
      stepIndex: this.currentStepIndex,
      stepNumber,
      stepCount,
      percent: Math.round((stepNumber / stepCount) * 100),
    };
  }

  getWorkflowState(): string {
    return this.workflowState;
  }

  setWorkflowState(nextState: string): void {
    this.workflowState = nextState;
  }

  getWorkflowStepTarget(state?: string): string | null {
    if (!this.formConfig?.workflowStepTargets) {
      return null;
    }

    const nextState = state || this.workflowState;
    const target = this.formConfig.workflowStepTargets[nextState];
    return typeof target === "string" ? target : null;
  }

  isLastStep(): boolean {
    return this.stepNames.length <= 1 || this.currentStepIndex >= this.stepNames.length - 1;
  }

  getCurrentStepConfig(): TFieldConfig | null {
    const currentStepName = this.getCurrentStepName();
    if (!currentStepName) {
      return null;
    }

    return (
      (this.formConfig?.stepSections || this.formConfig?.sections?.[CUSTOM_SECTION] || []).find(
        (section) => section.name === currentStepName,
      ) || null
    );
  }

  shouldValidateCurrentStepForWorkflow(): boolean {
    const stepConfig = this.getCurrentStepConfig();
    const allowedStates = stepConfig?.stepValidateWhenWorkflowStates;
    if (!allowedStates?.length) {
      return true;
    }

    return allowedStates.includes(this.workflowState);
  }

  isCurrentStepSkippable(): boolean {
    return Boolean(this.getCurrentStepConfig()?.stepSkippable);
  }

  toTimestamp(value: any): number | null {
    if (value === undefined || value === null || value === "") {
      return null;
    }

    if (typeof value === "number") {
      return Number.isFinite(value) ? value : null;
    }

    const parsed = Date.parse(String(value));
    return Number.isNaN(parsed) ? null : parsed;
  }

  matchesTransitionCondition(
    condition: TStepTransitionCondition,
    values: Record<string, any>,
  ): boolean {
    const observedValue = values[condition.whenField];
    const operator = condition.operator || "equals";

    switch (operator) {
      case "truthy":
        return Boolean(observedValue);
      case "date_before": {
        const observedTs = this.toTimestamp(observedValue);
        const targetTs = this.toTimestamp(condition.value);
        return observedTs !== null && targetTs !== null
          ? observedTs < targetTs
          : false;
      }
      case "date_after": {
        const observedTs = this.toTimestamp(observedValue);
        const targetTs = this.toTimestamp(condition.value);
        return observedTs !== null && targetTs !== null
          ? observedTs > targetTs
          : false;
      }
      case "date_between": {
        const range = Array.isArray(condition.value) ? condition.value : [];
        const [start, end] = range;
        const observedTs = this.toTimestamp(observedValue);
        const startTs = this.toTimestamp(start);
        const endTs = this.toTimestamp(end);
        return observedTs !== null && startTs !== null && endTs !== null
          ? observedTs >= startTs && observedTs <= endTs
          : false;
      }
      case "not_equals":
        return String(observedValue) !== String(condition.value);
      case "in":
        return Array.isArray(condition.value)
          ? condition.value.map((entry) => String(entry)).includes(String(observedValue))
          : false;
      case "not_in":
        return Array.isArray(condition.value)
          ? !condition.value.map((entry) => String(entry)).includes(String(observedValue))
          : true;
      case "equals":
      default:
        return String(observedValue) === String(condition.value);
    }
  }

  matchesTransition(transition: TStepTransition, values: Record<string, any>): boolean {
    if (Array.isArray(transition.conditions) && transition.conditions.length) {
      const logic = transition.logic || "AND";
      const matches = transition.conditions.map((condition) =>
        this.matchesTransitionCondition(condition, values),
      );
      return logic === "OR" ? matches.some(Boolean) : matches.every(Boolean);
    }

    if (!transition.whenField) {
      return false;
    }

    return this.matchesTransitionCondition(
      {
        whenField: transition.whenField,
        operator: transition.operator,
        value: transition.value,
      },
      values,
    );
  }

  getConditionalNextStepName(values: Record<string, any>): string | null {
    const stepConfig = this.getCurrentStepConfig();
    const explicitTransitions = stepConfig?.stepTransitions || [];
    for (const transition of explicitTransitions) {
      if (transition?.target && this.matchesTransition(transition, values)) {
        return transition.target;
      }
    }

    if (!stepConfig?.nextStepWhenField || !stepConfig.nextStepTarget) {
      return null;
    }

    const observedValue = values[stepConfig.nextStepWhenField];
    const expectedValue = stepConfig.nextStepWhenEquals;
    const excludedValue = stepConfig.nextStepWhenNotEquals;

    if (typeof excludedValue !== "undefined") {
      if (Array.isArray(excludedValue)) {
        return excludedValue.map((entry) => String(entry)).includes(String(observedValue))
          ? null
          : stepConfig.nextStepTarget;
      }

      return String(observedValue) !== String(excludedValue) ? stepConfig.nextStepTarget : null;
    }

    if (Array.isArray(expectedValue)) {
      return expectedValue.map((entry) => String(entry)).includes(String(observedValue))
        ? stepConfig.nextStepTarget
        : null;
    }

    if (stepConfig.nextStepWhenEquals === undefined) {
      return observedValue ? stepConfig.nextStepTarget : null;
    }

    return String(observedValue) === String(expectedValue) ? stepConfig.nextStepTarget : null;
  }

  canGoToStep(index: number): boolean {
    return index >= 0 && index < this.stepNames.length && index !== this.currentStepIndex;
  }

  goToStep(index: number): boolean {
    if (!this.canGoToStep(index)) {
      return false;
    }

    this.currentStepIndex = index;
    return true;
  }

  nextStep(values: Record<string, any>, isStepValid: boolean): boolean {
    if (!this.isCurrentStepSkippable() && !isStepValid) {
      return false;
    }

    const conditionalTarget = this.getConditionalNextStepName(values);
    if (conditionalTarget) {
      const targetIndex = this.stepNames.indexOf(conditionalTarget);
      if (targetIndex >= 0) {
        return this.goToStep(targetIndex);
      }
    }

    return this.goToStep(this.currentStepIndex + 1);
  }

  previousStep(): boolean {
    return this.goToStep(this.currentStepIndex - 1);
  }

  goToWorkflowStep(state?: string): boolean {
    const targetName = this.getWorkflowStepTarget(state);
    if (!targetName) {
      return false;
    }

    const targetIndex = this.stepNames.indexOf(targetName);
    if (targetIndex < 0) {
      return false;
    }

    return this.goToStep(targetIndex);
  }

  getStepSummary(
    values: Record<string, any>,
    fields: Record<string, TFieldConfig>,
  ): Array<{ field: string; label: string; value: any }> {
    const currentStepConfig = this.getCurrentStepConfig();
    if (!currentStepConfig?.stepSummary) {
      return [];
    }

    return Object.values(fields)
      .filter((field) => field.type !== "section")
      .map((field) => ({
        field: field.name,
        label: field.label,
        value: values[field.name],
      }))
      .filter((entry) => entry.value !== undefined && entry.value !== "");
  }

  getWorkflowSnapshot(values: Record<string, any> = {}): TFormWorkflowSnapshot {
    return {
      workflowState: this.workflowState,
      currentStepName: this.getCurrentStepName(),
      currentStepIndex: this.getCurrentStepIndex(),
      progress: this.getStepProgress(),
      nextStepTarget: this.getConditionalNextStepName(values),
    };
  }
}
