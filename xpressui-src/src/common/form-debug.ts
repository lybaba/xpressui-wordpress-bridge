import type {
  TFormActiveTemplateWarning,
  TFormRuleAppliedDetail,
} from "./form-dynamic";

export type TFormDebugEventRecord = {
  type: string;
  timestamp: number;
  detail: any;
};

export type TFormDebugRuleRecord = Omit<TFormDebugEventRecord, "type" | "detail"> & {
  type: "xpressui:rule-applied";
  detail: {
    result?: TFormRuleAppliedDetail;
    [key: string]: any;
  };
};

export type TFormDebugTemplateDiagnosticRecord = TFormDebugEventRecord & {
  type: "xpressui:rule-template-missing-field" | "xpressui:rule-template-warning-cleared";
};

export type TFormDebugRuleStateRecord = TFormDebugEventRecord & {
  type: "xpressui:rule-state";
  detail: {
    result?: {
      rules?: TFormRuleAppliedDetail[];
    };
    [key: string]: any;
  };
};

export type TFormDebugTemplateWarningStateRecord = TFormDebugEventRecord & {
  type: "xpressui:rule-template-warning-state";
  detail: {
    result?: {
      warnings?: TFormActiveTemplateWarning[];
    };
    [key: string]: any;
  };
};

export type TFormDebugOutputSnapshotRecord = TFormDebugEventRecord & {
  type: "xpressui:output-snapshot";
};

export type TFormDebugWorkflowSnapshotRecord = TFormDebugEventRecord & {
  type: "xpressui:workflow-step" | "xpressui:workflow-state" | "xpressui:workflow-snapshot";
};

export type TFormDebugResumeClaimStateRecord = TFormDebugEventRecord & {
  type: "xpressui:resume-share-code-claim-state";
};

export type TFormDebugProviderContractWarningRecord = TFormDebugEventRecord & {
  type: "xpressui:provider-contract-warning";
};

export type TFormDebugSnapshot = {
  recentAppliedRules: TFormRuleAppliedDetail[];
  lastRuleState: TFormDebugRuleStateRecord | null;
  activeTemplateWarnings: TFormActiveTemplateWarning[];
  lastTemplateWarningState: TFormDebugTemplateWarningStateRecord | null;
  lastOutputSnapshot: TFormDebugOutputSnapshotRecord | null;
  lastWorkflowSnapshot: TFormDebugWorkflowSnapshotRecord | null;
  lastResumeShareCodeClaimState: TFormDebugResumeClaimStateRecord | null;
  lastProviderContractWarning: TFormDebugProviderContractWarningRecord | null;
};

export type TFormDebugObserver = {
  getEvents(): TFormDebugEventRecord[];
  getRuleHistory(): TFormDebugRuleRecord[];
  getRecentAppliedRules(): TFormRuleAppliedDetail[];
  getLastRuleState(): TFormDebugRuleStateRecord | null;
  getTemplateDiagnostics(): TFormDebugTemplateDiagnosticRecord[];
  getActiveTemplateWarnings(): TFormActiveTemplateWarning[];
  getLastTemplateWarningState(): TFormDebugTemplateWarningStateRecord | null;
  getLastOutputSnapshot(): TFormDebugOutputSnapshotRecord | null;
  getLastWorkflowSnapshot(): TFormDebugWorkflowSnapshotRecord | null;
  getLastResumeShareCodeClaimState(): TFormDebugResumeClaimStateRecord | null;
  getLastProviderContractWarning(): TFormDebugProviderContractWarningRecord | null;
  getSnapshot(): TFormDebugSnapshot;
  clear(): void;
  clearSnapshot(): void;
  clearRuleHistory(): void;
  clearRecentAppliedRules(): void;
  clearLastRuleState(): void;
  clearTemplateDiagnostics(): void;
  clearActiveTemplateWarnings(): void;
  clearLastTemplateWarningState(): void;
  clearLastOutputSnapshot(): void;
  clearLastWorkflowSnapshot(): void;
  clearLastResumeShareCodeClaimState(): void;
  clearLastProviderContractWarning(): void;
  detach(): void;
};

export type TFormDebugOptions = {
  maxEvents?: number;
  onEvent?: (event: TFormDebugEventRecord) => void;
};

const DEFAULT_DEBUG_EVENTS = [
  "xpressui:submit",
  "xpressui:validation-blocked-submit",
  "xpressui:submit-success",
  "xpressui:submit-error",
  "xpressui:options-loaded",
  "xpressui:rule-applied",
  "xpressui:rule-state",
  "xpressui:rule-template-missing-field",
  "xpressui:rule-template-warning-cleared",
  "xpressui:rule-template-warning-state",
  "xpressui:draft-saved",
  "xpressui:draft-restored",
  "xpressui:draft-cleared",
  "xpressui:queued",
  "xpressui:queue-state",
  "xpressui:sync-success",
  "xpressui:sync-error",
  "xpressui:upload-retry",
  "xpressui:file-policy-rejected",
  "xpressui:dead-lettered",
  "xpressui:dead-letter-cleared",
  "xpressui:dead-letter-requeued",
  "xpressui:dead-letter-replayed-success",
  "xpressui:dead-letter-replayed-error",
  "xpressui:reservation-success",
  "xpressui:payment-success",
  "xpressui:payment-error",
  "xpressui:payment-stripe-success",
  "xpressui:payment-stripe-error",
  "xpressui:webhook-success",
  "xpressui:webhook-error",
  "xpressui:booking-availability-success",
  "xpressui:booking-availability-error",
  "xpressui:step-change",
  "xpressui:step-blocked",
  "xpressui:step-skipped",
  "xpressui:step-jumped",
  "xpressui:workflow-state",
  "xpressui:workflow-step",
  "xpressui:workflow-snapshot",
  "xpressui:resume-share-code-claim-state",
  "xpressui:resume-share-code-claim-blocked",
  "xpressui:provider-contract-warning",
  "xpressui:validation-i18n-updated",
  "xpressui:output-snapshot",
];

export function attachFormDebugObserver(
  target: EventTarget,
  options: TFormDebugOptions = {},
): TFormDebugObserver {
  const maxEvents = options.maxEvents ?? 100;
  const events: TFormDebugEventRecord[] = [];
  const ruleEvents: TFormDebugRuleRecord[] = [];
  let recentAppliedRules: TFormRuleAppliedDetail[] = [];
  let lastRuleState: TFormDebugRuleStateRecord | null = null;
  const templateDiagnostics: TFormDebugTemplateDiagnosticRecord[] = [];
  let activeTemplateWarnings: TFormActiveTemplateWarning[] = [];
  let lastTemplateWarningState: TFormDebugTemplateWarningStateRecord | null = null;
  let lastOutputSnapshot: TFormDebugOutputSnapshotRecord | null = null;
  let lastWorkflowSnapshot: TFormDebugWorkflowSnapshotRecord | null = null;
  let lastResumeShareCodeClaimState: TFormDebugResumeClaimStateRecord | null = null;
  let lastProviderContractWarning: TFormDebugProviderContractWarningRecord | null = null;
  const listeners = DEFAULT_DEBUG_EVENTS.map((eventName) => {
    const listener = (event: Event) => {
      const customEvent = event as CustomEvent<any>;
      const record: TFormDebugEventRecord = {
        type: customEvent.type,
        timestamp: Date.now(),
        detail: customEvent.detail,
      };

      events.push(record);
      if (events.length > maxEvents) {
        events.splice(0, events.length - maxEvents);
      }

      if (record.type === "xpressui:rule-applied") {
        ruleEvents.push(record as TFormDebugRuleRecord);
        if (ruleEvents.length > maxEvents) {
          ruleEvents.splice(0, ruleEvents.length - maxEvents);
        }
      }

      if (record.type === "xpressui:rule-state") {
        recentAppliedRules = Array.isArray(record.detail?.result?.rules)
          ? [...record.detail.result.rules]
          : [];
        lastRuleState = record as TFormDebugRuleStateRecord;
      }

      if (
        record.type === "xpressui:rule-template-missing-field" ||
        record.type === "xpressui:rule-template-warning-cleared"
      ) {
        templateDiagnostics.push(record as TFormDebugTemplateDiagnosticRecord);
        if (templateDiagnostics.length > maxEvents) {
          templateDiagnostics.splice(0, templateDiagnostics.length - maxEvents);
        }
      }

      if (record.type === "xpressui:rule-template-warning-state") {
        activeTemplateWarnings = Array.isArray(record.detail?.result?.warnings)
          ? [...record.detail.result.warnings]
          : [];
        lastTemplateWarningState = record as TFormDebugTemplateWarningStateRecord;
      }

      if (record.type === "xpressui:output-snapshot") {
        lastOutputSnapshot = record as TFormDebugOutputSnapshotRecord;
      }

      if (
        record.type === "xpressui:workflow-step" ||
        record.type === "xpressui:workflow-state" ||
        record.type === "xpressui:workflow-snapshot"
      ) {
        lastWorkflowSnapshot = record as TFormDebugWorkflowSnapshotRecord;
      }

      if (record.type === "xpressui:resume-share-code-claim-state") {
        lastResumeShareCodeClaimState = record as TFormDebugResumeClaimStateRecord;
      }

      if (record.type === "xpressui:provider-contract-warning") {
        lastProviderContractWarning = record as TFormDebugProviderContractWarningRecord;
      }

      options.onEvent?.(record);
    };

    target.addEventListener(eventName, listener as EventListener);
    return {
      eventName,
      listener,
    };
  });

  return {
    getEvents() {
      return [...events];
    },
    getRuleHistory() {
      return [...ruleEvents];
    },
    getRecentAppliedRules() {
      return [...recentAppliedRules];
    },
    getLastRuleState() {
      return lastRuleState ? { ...lastRuleState } : null;
    },
    getTemplateDiagnostics() {
      return [...templateDiagnostics];
    },
    getActiveTemplateWarnings() {
      return [...activeTemplateWarnings];
    },
    getLastTemplateWarningState() {
      return lastTemplateWarningState ? { ...lastTemplateWarningState } : null;
    },
    getLastOutputSnapshot() {
      return lastOutputSnapshot ? { ...lastOutputSnapshot } : null;
    },
    getLastWorkflowSnapshot() {
      return lastWorkflowSnapshot ? { ...lastWorkflowSnapshot } : null;
    },
    getLastResumeShareCodeClaimState() {
      return lastResumeShareCodeClaimState ? { ...lastResumeShareCodeClaimState } : null;
    },
    getLastProviderContractWarning() {
      return lastProviderContractWarning ? { ...lastProviderContractWarning } : null;
    },
    getSnapshot() {
      return {
        recentAppliedRules: [...recentAppliedRules],
        lastRuleState: lastRuleState ? { ...lastRuleState } : null,
        activeTemplateWarnings: [...activeTemplateWarnings],
        lastTemplateWarningState: lastTemplateWarningState ? { ...lastTemplateWarningState } : null,
        lastOutputSnapshot: lastOutputSnapshot ? { ...lastOutputSnapshot } : null,
        lastWorkflowSnapshot: lastWorkflowSnapshot ? { ...lastWorkflowSnapshot } : null,
        lastResumeShareCodeClaimState: lastResumeShareCodeClaimState
          ? { ...lastResumeShareCodeClaimState }
          : null,
        lastProviderContractWarning: lastProviderContractWarning
          ? { ...lastProviderContractWarning }
          : null,
      };
    },
    clear() {
      events.splice(0, events.length);
      ruleEvents.splice(0, ruleEvents.length);
      recentAppliedRules = [];
      lastRuleState = null;
      templateDiagnostics.splice(0, templateDiagnostics.length);
      activeTemplateWarnings = [];
      lastTemplateWarningState = null;
      lastOutputSnapshot = null;
      lastWorkflowSnapshot = null;
      lastResumeShareCodeClaimState = null;
      lastProviderContractWarning = null;
    },
    clearSnapshot() {
      recentAppliedRules = [];
      lastRuleState = null;
      activeTemplateWarnings = [];
      lastTemplateWarningState = null;
      lastOutputSnapshot = null;
      lastWorkflowSnapshot = null;
      lastResumeShareCodeClaimState = null;
      lastProviderContractWarning = null;
    },
    clearRuleHistory() {
      ruleEvents.splice(0, ruleEvents.length);
    },
    clearRecentAppliedRules() {
      recentAppliedRules = [];
    },
    clearLastRuleState() {
      lastRuleState = null;
    },
    clearTemplateDiagnostics() {
      templateDiagnostics.splice(0, templateDiagnostics.length);
    },
    clearActiveTemplateWarnings() {
      activeTemplateWarnings = [];
    },
    clearLastTemplateWarningState() {
      lastTemplateWarningState = null;
    },
    clearLastOutputSnapshot() {
      lastOutputSnapshot = null;
    },
    clearLastWorkflowSnapshot() {
      lastWorkflowSnapshot = null;
    },
    clearLastResumeShareCodeClaimState() {
      lastResumeShareCodeClaimState = null;
    },
    clearLastProviderContractWarning() {
      lastProviderContractWarning = null;
    },
    detach() {
      listeners.forEach(({ eventName, listener }) => {
        target.removeEventListener(eventName, listener as EventListener);
      });
    },
  };
}
