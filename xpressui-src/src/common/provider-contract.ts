export const PROVIDER_RESPONSE_CONTRACT_VERSION = "provider-envelope-v2" as const;

export type TProviderResponseContractVersion = typeof PROVIDER_RESPONSE_CONTRACT_VERSION;

export type TFormProviderTransition =
  | {
      type: "step";
      target: string | number;
    }
  | {
      type: "workflow";
      state: string;
    };

export type TNormalizedProviderError = {
  source: string;
  code?: string;
  field?: string;
  message?: string;
  raw?: any;
};

export type TNormalizedProviderNextAction = {
  type: string;
  label?: string;
  href?: string;
  method?: string;
  target?: string;
  payload?: any;
  meta?: Record<string, any>;
};

export type TNormalizedProviderResult = {
  status: string | null;
  transition: TFormProviderTransition | null;
  messages: string[];
  errors: TNormalizedProviderError[];
  nextActions?: TNormalizedProviderNextAction[];
  data: any;
};

export type TProviderResponseEnvelopeV2 = {
  status?: string;
  transition?: TFormProviderTransition;
  messages?: string[];
  errors?: Array<string | Partial<TNormalizedProviderError>>;
  nextActions?: TNormalizedProviderNextAction[];
  data?: any;
};

function normalizeProviderTransition(
  transition: unknown,
): TFormProviderTransition | null {
  if (!transition || typeof transition !== "object") {
    return null;
  }

  const candidate = transition as Record<string, any>;
  if (
    candidate.type === "step" &&
    (typeof candidate.target === "string" || Number.isFinite(candidate.target))
  ) {
    return {
      type: "step",
      target: candidate.target as string | number,
    };
  }

  if (candidate.type === "workflow" && typeof candidate.state === "string" && candidate.state) {
    return {
      type: "workflow",
      state: candidate.state,
    };
  }

  return null;
}

function normalizeProviderErrorEntry(entry: unknown): TNormalizedProviderError | null {
  if (typeof entry === "string" && entry) {
    return {
      source: "provider",
      message: entry,
    };
  }

  if (!entry || typeof entry !== "object" || Array.isArray(entry)) {
    return null;
  }

  const candidate = entry as Record<string, any>;
  const source = typeof candidate.source === "string" && candidate.source
    ? candidate.source
    : "provider";

  return {
    source,
    ...(typeof candidate.code === "string" && candidate.code ? { code: candidate.code } : {}),
    ...(typeof candidate.field === "string" && candidate.field ? { field: candidate.field } : {}),
    ...(typeof candidate.message === "string" && candidate.message ? { message: candidate.message } : {}),
    ...("raw" in candidate ? { raw: candidate.raw } : {}),
  };
}

function normalizeNextActionEntry(entry: unknown): TNormalizedProviderNextAction | null {
  if (typeof entry === "string" && entry) {
    return {
      type: entry,
    };
  }

  if (!entry || typeof entry !== "object" || Array.isArray(entry)) {
    return null;
  }

  const candidate = entry as Record<string, any>;
  if (typeof candidate.type !== "string" || !candidate.type) {
    return null;
  }

  return {
    type: candidate.type,
    ...(typeof candidate.label === "string" && candidate.label ? { label: candidate.label } : {}),
    ...(typeof candidate.href === "string" && candidate.href ? { href: candidate.href } : {}),
    ...(typeof candidate.method === "string" && candidate.method ? { method: candidate.method } : {}),
    ...(typeof candidate.target === "string" && candidate.target ? { target: candidate.target } : {}),
    ...("payload" in candidate ? { payload: candidate.payload } : {}),
    ...(candidate.meta && typeof candidate.meta === "object" && !Array.isArray(candidate.meta)
      ? { meta: candidate.meta as Record<string, any> }
      : {}),
  };
}

export function createNormalizedProviderResult(
  input: Partial<TNormalizedProviderResult> & Pick<TNormalizedProviderResult, "data">,
): TNormalizedProviderResult {
  const transition = normalizeProviderTransition(input.transition);
  const messages = Array.isArray(input.messages)
    ? input.messages.filter((entry): entry is string => typeof entry === "string" && entry.length > 0)
    : [];
  const errors = Array.isArray(input.errors)
    ? input.errors
        .map((entry) => normalizeProviderErrorEntry(entry))
        .filter((entry): entry is TNormalizedProviderError => Boolean(entry))
    : [];
  const nextActions = Array.isArray(input.nextActions)
    ? input.nextActions
        .map((entry) => normalizeNextActionEntry(entry))
        .filter((entry): entry is TNormalizedProviderNextAction => Boolean(entry))
    : [];

  return {
    status: input.status ?? null,
    transition,
    messages,
    errors,
    ...(nextActions.length ? { nextActions } : {}),
    data: input.data,
  };
}

export function isNormalizedProviderResult(value: unknown): value is TNormalizedProviderResult {
  if (!value || typeof value !== "object") {
    return false;
  }

  const result = value as Record<string, any>;
  const transitionValid = result.transition === null || result.transition === undefined || Boolean(normalizeProviderTransition(result.transition));
  const errorsValid =
    Array.isArray(result.errors) &&
    result.errors.every((entry: unknown) => Boolean(normalizeProviderErrorEntry(entry)));
  const nextActionsValid =
    !("nextActions" in result) ||
    (
      Array.isArray(result.nextActions) &&
      result.nextActions.every((entry: unknown) => Boolean(normalizeNextActionEntry(entry)))
    );

  return (
    (result.status === null || typeof result.status === "string") &&
    transitionValid &&
    Array.isArray(result.messages) &&
    result.messages.every((entry: unknown) => typeof entry === "string") &&
    errorsValid &&
    nextActionsValid &&
    "data" in result
  );
}
