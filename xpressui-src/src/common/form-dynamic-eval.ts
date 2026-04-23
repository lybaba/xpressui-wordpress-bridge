import { toSlug } from "./field";

// ---------------------------------------------------------------------------
// Internal types used by rule evaluation
// ---------------------------------------------------------------------------

export type TMatchedRuleState<TRule> = { rule: TRule; changed: boolean };

export type TConditionalRuleState<TRule> = {
  matchedTrueRules: TRule[];
  matchedFalseRules: TRule[];
  hasTrueRule: boolean;
  hasFalseRule: boolean;
};

export type TResolvedConditionalState<TRule> = {
  value: boolean;
  winningRules: TRule[];
};

// ---------------------------------------------------------------------------
// Pure helpers
// ---------------------------------------------------------------------------

export function getConditionalRuleState<TRule>(
  states: Record<string, TConditionalRuleState<TRule>>,
  target: string,
): TConditionalRuleState<TRule> {
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

export function resolveConditionalState<TRule>(
  state: TConditionalRuleState<TRule> | undefined,
  options: {
    baseValue: boolean;
    fallbackValueWhenOnlyTrueRule: boolean;
    fallbackValueWhenOnlyFalseRule: boolean;
    fallbackValueWhenBothRuleTypes: boolean;
    preferFalseWhenConflictingMatches?: boolean;
  },
): TResolvedConditionalState<TRule> {
  if (!state) {
    return { value: options.baseValue, winningRules: [] };
  }

  const preferFalse = options.preferFalseWhenConflictingMatches !== false;
  if (state.matchedTrueRules.length && state.matchedFalseRules.length) {
    return preferFalse
      ? { value: false, winningRules: state.matchedFalseRules }
      : { value: true, winningRules: state.matchedTrueRules };
  }

  if (state.matchedFalseRules.length) {
    return { value: false, winningRules: state.matchedFalseRules };
  }

  if (state.matchedTrueRules.length) {
    return { value: true, winningRules: state.matchedTrueRules };
  }

  if (state.hasTrueRule && state.hasFalseRule) {
    return { value: options.fallbackValueWhenBothRuleTypes, winningRules: [] };
  }

  if (state.hasTrueRule) {
    return { value: options.fallbackValueWhenOnlyTrueRule, winningRules: [] };
  }

  if (state.hasFalseRule) {
    return { value: options.fallbackValueWhenOnlyFalseRule, winningRules: [] };
  }

  return { value: options.baseValue, winningRules: [] };
}

export function markRulesChanged<TRule>(
  matchedRuleMap: Map<TRule, TMatchedRuleState<TRule>>,
  rules: TRule[],
): void {
  rules.forEach((rule) => {
    const matchedRule = matchedRuleMap.get(rule);
    if (matchedRule) {
      matchedRule.changed = true;
    }
  });
}

export function transformRuleValue(
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
    return toSlug(normalizedValue, '-', true);
  }

  return value;
}

export function matchesCondition(
  condition: {
    field: string;
    operator?: "equals" | "not_equals" | "contains" | "in" | "gt" | "lt" | "exists" | "empty";
    value?: any;
  },
  getFieldValue: (fieldName: string) => any,
): boolean {
  const currentValue = getFieldValue(condition.field);
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
    const allowedValues = Array.isArray(condition.value) ? condition.value : [condition.value];
    return allowedValues.map((v) => String(v ?? "")).includes(String(currentValue ?? ""));
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

export function matchesRule(
  rule: {
    logic?: "AND" | "OR";
    conditions: Array<{
      field: string;
      operator?: "equals" | "not_equals" | "contains" | "in" | "gt" | "lt" | "exists" | "empty";
      value?: any;
    }>;
  },
  getFieldValue: (fieldName: string) => any,
): boolean {
  const logic = rule.logic || "AND";
  const matches = rule.conditions.map((condition) => matchesCondition(condition, getFieldValue));
  return logic === "OR" ? matches.some(Boolean) : matches.every(Boolean);
}
