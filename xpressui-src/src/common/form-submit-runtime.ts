import type {
  TFormValidationErrorsHook,
  TFormValidationHook,
  TFormSubmitLifecycleHook,
  TFormSubmitLifecycleHookResult,
  TFormSubmitLifecycleStage,
  TFormSubmitRequest,
} from "./TFormConfig";
import { validateProviderResponseEnvelopeV2 } from "./provider-registry";
import {
  TFormProviderTransition,
  PROVIDER_RESPONSE_CONTRACT_VERSION,
} from "./provider-contract";
import type { TNormalizedProviderResult } from "./provider-contract";

export type TResolvedSubmitTransportResult = {
  response?: Response;
  result: any;
};

export type TSubmitResponseError = Error & {
  response: Response;
  result: any;
};

export type TProviderContractWarning = {
  mode: "warn-v2" | "strict-v2";
  errors: string[];
  expectedContract: typeof PROVIDER_RESPONSE_CONTRACT_VERSION;
};

export type TSubmitLifecycleDetail = {
  values: Record<string, any>;
  formConfig: any;
  submit?: TFormSubmitRequest;
  response?: Response;
  result?: any;
  providerResult?: TNormalizedProviderResult;
  error?: unknown;
};

export type TResolvedApprovalState = {
  status: string;
  approvalId?: string;
  result?: any;
  providerResult?: TNormalizedProviderResult;
};

export type TResolvedSubmitEvent = {
  eventName: string;
  detail: TSubmitLifecycleDetail;
};

export async function parseTransportResponsePayload(response: Response): Promise<any> {
  if (response.status === 204 || response.status === 205) {
    return null;
  }
  const contentType = response.headers.get("Content-Type") || "";
  if (contentType.includes("application/json")) {
    try {
      return await response.clone().json();
    } catch {
      return null;
    }
  }
  try {
    const text = await response.clone().text();
    return text ? text : null;
  } catch {
    return null;
  }
}

export function createSubmitResponseError(response: Response, result: any): TSubmitResponseError {
  const error = new Error(
    result && typeof result === "object" && typeof result.error === "string"
      ? result.error
      : `Submit failed with status ${response.status}`,
  ) as TSubmitResponseError;
  error.response = response;
  error.result = result;
  return error;
}

export async function resolveSubmitTransportResult(
  transportResult: any,
): Promise<TResolvedSubmitTransportResult> {
  if (transportResult instanceof Response) {
    const result = await parseTransportResponsePayload(transportResult);
    if (!transportResult.ok) {
      throw createSubmitResponseError(transportResult, result);
    }
    return {
      response: transportResult,
      result,
    };
  }

  if (
    transportResult &&
    typeof transportResult === "object" &&
    ("response" in transportResult || "result" in transportResult)
  ) {
    const response = (transportResult as any).response;
    if (response !== undefined && !(response instanceof Response)) {
      throw new Error("submit.transport envelope response must be a Response instance.");
    }
    const result =
      (transportResult as any).result !== undefined
        ? (transportResult as any).result
        : response
          ? await parseTransportResponsePayload(response)
          : undefined;
    if (response && !response.ok) {
      throw createSubmitResponseError(response, result);
    }
    return {
      response,
      result,
    };
  }

  return {
    result: transportResult,
  };
}

export function getProviderContractWarning(
  result: any,
  submitConfig: TFormSubmitRequest,
): TProviderContractWarning | null {
  const mode = submitConfig.providerResponseContract || "compat";
  if (mode === "compat") {
    return null;
  }

  const validationErrors = validateProviderResponseEnvelopeV2(result);
  if (!validationErrors.length) {
    return null;
  }

  return {
    mode,
    errors: validationErrors,
    expectedContract: PROVIDER_RESPONSE_CONTRACT_VERSION,
  };
}

export function assertProviderResponseContract(
  result: any,
  submitConfig: TFormSubmitRequest,
): TProviderContractWarning | null {
  const warning = getProviderContractWarning(result, submitConfig);
  if (warning?.mode === "strict-v2") {
    throw new Error(`Provider response contract mismatch: ${warning.errors.join("; ")}`);
  }

  return warning;
}

export function getSubmitLifecycleHooks(
  submitConfig: TFormSubmitRequest | undefined,
  stage: TFormSubmitLifecycleStage,
): TFormSubmitLifecycleHook[] {
  const candidate = submitConfig?.lifecycle?.[stage];
  if (!candidate) {
    return [];
  }
  return Array.isArray(candidate) ? candidate : [candidate];
}

export async function runConfiguredSubmitLifecycleStage(
  submitConfig: TFormSubmitRequest | undefined,
  stage: TFormSubmitLifecycleStage,
  detail: TSubmitLifecycleDetail,
): Promise<{ canceled: boolean; values: Record<string, any> }> {
  const hooks = getSubmitLifecycleHooks(submitConfig, stage);
  if (!hooks.length) {
    return { canceled: false, values: detail.values };
  }

  let nextValues = detail.values;
  for (let hookIndex = 0; hookIndex < hooks.length; hookIndex += 1) {
    const hook = hooks[hookIndex];
    let hookResult: TFormSubmitLifecycleHookResult;
    try {
      hookResult = await hook({
        stage,
        values: nextValues,
        formConfig: detail.formConfig,
        submit: detail.submit,
        response: detail.response,
        result: detail.result,
        providerResult: detail.providerResult,
        error: detail.error,
      });
    } catch (error) {
      const lifecycleError = error instanceof Error ? error : new Error(String(error));
      (lifecycleError as any).stage = stage;
      (lifecycleError as any).hookIndex = hookIndex;
      (lifecycleError as any).hookName = hook.name || "anonymous";
      throw lifecycleError;
    }

    if (stage === "preSubmit") {
      if (hookResult === false) {
        return { canceled: true, values: nextValues };
      }

      if (
        hookResult &&
        typeof hookResult === "object" &&
        !Array.isArray(hookResult)
      ) {
        nextValues = hookResult as Record<string, any>;
      }
    }
  }

  return { canceled: false, values: nextValues };
}

export function buildSubmitHookErrorResult(
  stage: TFormSubmitLifecycleStage,
  hookError: unknown,
): { stage: TFormSubmitLifecycleStage; hookIndex?: number; hookName?: string } {
  const hookMeta =
    hookError && typeof hookError === "object"
      ? {
        hookIndex: typeof (hookError as any).hookIndex === "number" ? (hookError as any).hookIndex : undefined,
        hookName: typeof (hookError as any).hookName === "string" ? (hookError as any).hookName : undefined,
      }
      : {};

  return {
    stage,
    ...hookMeta,
  };
}

export function buildProviderMessagesResult(
  providerResult?: TNormalizedProviderResult,
  source: "success" | "error" = "success",
): {
  status: string;
  source: "success" | "error";
  messages: string[];
  nextActions?: TNormalizedProviderResult["nextActions"];
} | null {
  if (!providerResult?.messages?.length) {
    return null;
  }

  return {
    status: providerResult.status || "unknown",
    source,
    messages: providerResult.messages,
    ...(providerResult.nextActions?.length
      ? { nextActions: providerResult.nextActions }
      : {}),
  };
}

export function resolveApprovalStateUpdate(options: {
  action?: TFormSubmitRequest["action"];
  result: any;
  providerResult?: TNormalizedProviderResult;
  currentApprovalId?: string;
  detail: TSubmitLifecycleDetail;
  response?: Response;
}): {
  approvalState: TResolvedApprovalState | null;
  events: TResolvedSubmitEvent[];
} {
  if (options.action !== "approval-request" && options.action !== "approval-decision") {
    return {
      approvalState: null,
      events: [],
    };
  }

  const status = options.providerResult?.status || "";
  const normalizedData = options.providerResult?.data;
  const approvalId =
    (normalizedData &&
    typeof normalizedData === "object" &&
    typeof normalizedData.approvalId === "string"
      ? normalizedData.approvalId
      : undefined) ||
    (options.result && typeof options.result === "object" && typeof options.result.approvalId === "string"
      ? options.result.approvalId
      : undefined) ||
    options.currentApprovalId;

  const approvalState: TResolvedApprovalState = {
    status: status || "unknown",
    approvalId,
    result: options.result,
    providerResult: options.providerResult,
  };

  const events: TResolvedSubmitEvent[] = [
    {
      eventName: "xpressui:approval-state",
      detail: {
        ...options.detail,
        response: options.response,
        result: approvalState,
      },
    },
  ];

  if (status === "pending_approval") {
    events.push({
      eventName: "xpressui:approval-requested",
      detail: {
        ...options.detail,
        response: options.response,
        result: options.result,
        providerResult: options.providerResult,
      },
    });
  } else if (status === "approved" || status === "completed") {
    events.push({
      eventName: "xpressui:approval-complete",
      detail: {
        ...options.detail,
        response: options.response,
        result: options.result,
        providerResult: options.providerResult,
      },
    });
  }

  return {
    approvalState,
    events,
  };
}

export function normalizeExplicitProviderTransition(result: any): TFormProviderTransition | null {
  const transition = result?.transition;
  if (!transition || typeof transition !== "object") {
    return null;
  }

  if (transition.type === "workflow" && typeof transition.state === "string" && transition.state) {
    return {
      type: "workflow",
      state: transition.state,
    };
  }

  if (
    transition.type === "step"
    && (typeof transition.target === "string" || Number.isFinite(transition.target))
  ) {
    return {
      type: "step",
      target: transition.target as string | number,
    };
  }

  return null;
}

export function normalizeStatusWorkflowTransition(
  providerResult?: TNormalizedProviderResult,
): TFormProviderTransition | null {
  const status = providerResult?.status;
  if (
    status === "draft" ||
    status === "submitting" ||
    status === "submitted" ||
    status === "pending_approval" ||
    status === "approved" ||
    status === "completed" ||
    status === "rejected" ||
    status === "error"
  ) {
    return {
      type: "workflow",
      state: status,
    };
  }

  return null;
}

export function getProviderTransitionKey(transition: TFormProviderTransition): string {
  return transition.type === "workflow"
    ? `workflow:${transition.state}`
    : `step:${String(transition.target)}`;
}

export function buildProviderTransitionCandidates(
  policy: NonNullable<TFormSubmitRequest["providerRoutingPolicy"]>,
  result: any,
  providerResult?: TNormalizedProviderResult,
): TFormProviderTransition[] {
  const explicitTransition = normalizeExplicitProviderTransition(result);
  const normalizedTransition = providerResult?.transition || null;
  const statusWorkflowTransition = normalizeStatusWorkflowTransition(providerResult);
  const stepTransition =
    explicitTransition?.type === "step"
      ? explicitTransition
      : normalizedTransition?.type === "step"
        ? normalizedTransition
        : null;
  const workflowTransition =
    explicitTransition?.type === "workflow"
      ? explicitTransition
      : normalizedTransition?.type === "workflow"
        ? normalizedTransition
        : null;

  let ordered: Array<TFormProviderTransition | null> = [];
  if (policy === "workflow-only") {
    ordered = [workflowTransition, statusWorkflowTransition];
  } else if (policy === "step-only") {
    ordered = [stepTransition];
  } else if (policy === "workflow-first") {
    ordered = [workflowTransition, statusWorkflowTransition, stepTransition];
  } else if (policy === "step-first") {
    ordered = [stepTransition, workflowTransition, statusWorkflowTransition];
  } else {
    ordered = [explicitTransition, normalizedTransition, statusWorkflowTransition];
  }

  const unique: TFormProviderTransition[] = [];
  const seen = new Set<string>();
  ordered.forEach((candidate) => {
    if (!candidate) {
      return;
    }
    const key = getProviderTransitionKey(candidate);
    if (seen.has(key)) {
      return;
    }
    seen.add(key);
    unique.push(candidate);
  });

  return unique;
}

export function getValidationHooks(
  formConfig: { validation?: any } | any,
  stage: "preValidate" | "customValidate",
): TFormValidationHook[] {
  const candidate = formConfig?.validation?.[stage];
  if (!candidate) {
    return [];
  }
  return Array.isArray(candidate) ? candidate : [candidate];
}

export function getValidationErrorHooks(
  formConfig: { validation?: any } | any,
): TFormValidationErrorsHook[] {
  const candidate = formConfig?.validation?.postValidate;
  if (!candidate) {
    return [];
  }
  return Array.isArray(candidate) ? candidate : [candidate];
}

export function mergeValidationErrors(
  baseErrors: Record<string, any>,
  incomingErrors: Record<string, any>,
): Record<string, any> {
  return {
    ...(baseErrors || {}),
    ...(incomingErrors || {}),
  };
}

export function runValidationHooks(options: {
  formConfig: any;
  values: Record<string, any>;
  validateValues: (values: Record<string, any>) => Record<string, any>;
}): Record<string, any> {
  let nextValues = options.values;
  const validationContext = {
    formConfig: options.formConfig,
  };

  getValidationHooks(options.formConfig, "preValidate").forEach((hook) => {
    const hookResult = hook(nextValues, validationContext);
    if (hookResult && typeof hookResult === "object" && !Array.isArray(hookResult)) {
      nextValues = hookResult;
    }
  });

  let errors = options.validateValues(nextValues);

  getValidationHooks(options.formConfig, "customValidate").forEach((hook) => {
    const hookResult = hook(nextValues, validationContext);
    if (hookResult && typeof hookResult === "object" && !Array.isArray(hookResult)) {
      errors = mergeValidationErrors(errors, hookResult);
    }
  });

  getValidationErrorHooks(options.formConfig).forEach((hook) => {
    const hookResult = hook(nextValues, errors, validationContext);
    if (hookResult && typeof hookResult === "object" && !Array.isArray(hookResult)) {
      errors = hookResult;
    }
  });

  if (errors && typeof errors === "object") {
    const finalErrors: Record<string, any> = { ...errors };
    let hasChanges = false;

    Object.keys(finalErrors).forEach((key) => {
      const errorValue = finalErrors[key];
      if (
        key !== "FINAL_FORM/form-error" &&
        errorValue &&
        typeof errorValue === "object" &&
        !Array.isArray(errorValue) &&
        !("FINAL_FORM/array-error" in errorValue)
      ) {
        finalErrors[key] = {
          ...errorValue,
          "FINAL_FORM/array-error": errorValue.errorMessage || "Invalid",
        };
        hasChanges = true;
      }
    });

    if (hasChanges) {
      errors = finalErrors;
    }
  }

  return errors;
}
