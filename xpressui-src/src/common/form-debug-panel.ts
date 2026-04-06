import {
  attachFormDebugObserver,
  TFormDebugObserver,
} from "./form-debug";

export type TFormDebugPanel = {
  element: HTMLElement;
  observer: TFormDebugObserver;
  refresh(): void;
  clearSnapshot(): void;
  detach(): void;
};

export type TFormDebugPanelOptions = {
  maxEvents?: number;
  className?: string;
  title?: string;
  maxVisibleEvents?: number;
  eventFilterPlaceholder?: string;
};

const DEBUG_FILTER_PRESETS = [
  { label: "All", value: "" },
  { label: "Resume", value: "resume" },
  { label: "Provider", value: "provider" },
  { label: "Queue", value: "queue" },
  { label: "Workflow", value: "workflow" },
];

export function createFormDebugPanel(
  target: EventTarget,
  options: TFormDebugPanelOptions = {},
): TFormDebugPanel {
  const element = document.createElement("section");
  element.className = options.className || "xpressui-debug-panel";

  const title = document.createElement("strong");
  title.textContent = options.title || "Form Debug";

  const counts = document.createElement("div");
  counts.className = "xpressui-debug-panel__counts";

  const status = document.createElement("div");
  status.className = "xpressui-debug-panel__status";

  const lastUpdated = document.createElement("div");
  lastUpdated.className = "xpressui-debug-panel__updated";

  const actions = document.createElement("div");
  actions.className = "xpressui-debug-panel__actions";

  const eventFilter = document.createElement("input");
  eventFilter.type = "search";
  eventFilter.className = "xpressui-debug-panel__event-filter";
  eventFilter.placeholder = options.eventFilterPlaceholder || "Filter events";

  const filterPresets = document.createElement("div");
  filterPresets.className = "xpressui-debug-panel__filter-presets";

  const clearButton = document.createElement("button");
  clearButton.type = "button";
  clearButton.className = "xpressui-debug-panel__clear";
  clearButton.textContent = "Clear Snapshot";

  const clearEventsButton = document.createElement("button");
  clearEventsButton.type = "button";
  clearEventsButton.className = "xpressui-debug-panel__clear-events";
  clearEventsButton.textContent = "Clear Events";

  const rulesTitle = document.createElement("strong");
  rulesTitle.textContent = "Recent Rules";

  const rules = document.createElement("pre");
  rules.className = "xpressui-debug-panel__rules";

  const warningsTitle = document.createElement("strong");
  warningsTitle.textContent = "Active Template Warnings";

  const warnings = document.createElement("pre");
  warnings.className = "xpressui-debug-panel__warnings";

  const workflowTitle = document.createElement("strong");
  workflowTitle.textContent = "Workflow";

  const workflow = document.createElement("pre");
  workflow.className = "xpressui-debug-panel__workflow";

  const outputSnapshotTitle = document.createElement("strong");
  outputSnapshotTitle.textContent = "Output Snapshot";

  const outputSnapshot = document.createElement("pre");
  outputSnapshot.className = "xpressui-debug-panel__output-snapshot";

  const resumeClaimTitle = document.createElement("strong");
  resumeClaimTitle.textContent = "Resume Claim";

  const resumeClaim = document.createElement("pre");
  resumeClaim.className = "xpressui-debug-panel__resume-claim";

  const providerWarningTitle = document.createElement("strong");
  providerWarningTitle.textContent = "Provider Contract Warning";

  const providerWarning = document.createElement("pre");
  providerWarning.className = "xpressui-debug-panel__provider-warning";

  const eventTimelineTitle = document.createElement("strong");
  eventTimelineTitle.textContent = "Recent Events";

  const eventTimeline = document.createElement("pre");
  eventTimeline.className = "xpressui-debug-panel__timeline";

  element.appendChild(title);
  element.appendChild(counts);
  element.appendChild(status);
  element.appendChild(lastUpdated);
  element.appendChild(eventFilter);
  element.appendChild(filterPresets);
  actions.appendChild(clearButton);
  actions.appendChild(clearEventsButton);
  element.appendChild(actions);
  element.appendChild(rulesTitle);
  element.appendChild(rules);
  element.appendChild(warningsTitle);
  element.appendChild(warnings);
  element.appendChild(workflowTitle);
  element.appendChild(workflow);
  element.appendChild(outputSnapshotTitle);
  element.appendChild(outputSnapshot);
  element.appendChild(resumeClaimTitle);
  element.appendChild(resumeClaim);
  element.appendChild(providerWarningTitle);
  element.appendChild(providerWarning);
  element.appendChild(eventTimelineTitle);
  element.appendChild(eventTimeline);

  const render = () => {
    const snapshot = observer.getSnapshot();
    const normalizedFilter = eventFilter.value.trim().toLowerCase();
    const visibleEvents = observer
      .getEvents()
      .filter((event) => {
        if (!normalizedFilter) {
          return true;
        }
        const haystack = `${event.type}\n${JSON.stringify(event.detail || {})}`.toLowerCase();
        return haystack.includes(normalizedFilter);
      })
      .slice(-(options.maxVisibleEvents ?? 10));
    counts.textContent = [
      `events: ${observer.getEvents().length}`,
      `ruleHistory: ${observer.getRuleHistory().length}`,
      `templateDiagnostics: ${observer.getTemplateDiagnostics().length}`,
    ].join(" | ");
    status.textContent = "Status: listening";
    const latestEvent = observer.getEvents().at(-1);
    lastUpdated.textContent = latestEvent
      ? `Last Updated: ${new Date(latestEvent.timestamp).toISOString()}`
      : "Last Updated: never";
    rules.textContent = JSON.stringify(snapshot.recentAppliedRules, null, 2);
    warnings.textContent = JSON.stringify(snapshot.activeTemplateWarnings, null, 2);
    workflow.textContent = JSON.stringify(snapshot.lastWorkflowSnapshot?.detail?.result || null, null, 2);
    outputSnapshot.textContent = JSON.stringify(snapshot.lastOutputSnapshot?.detail?.result || null, null, 2);
    resumeClaim.textContent = JSON.stringify(
      snapshot.lastResumeShareCodeClaimState?.detail?.result || null,
      null,
      2,
    );
    providerWarning.textContent = JSON.stringify(
      snapshot.lastProviderContractWarning?.detail?.result || null,
      null,
      2,
    );
    eventTimeline.textContent = JSON.stringify(
      visibleEvents.map((event) => ({
        type: event.type,
        timestamp: event.timestamp,
        result: event.detail?.result,
      })),
      null,
      2,
    );
    Array.from(filterPresets.querySelectorAll("button")).forEach((button) => {
      button.setAttribute(
        "data-active",
        button.getAttribute("data-filter-value") === eventFilter.value ? "true" : "false",
      );
    });
  };

  const observer = attachFormDebugObserver(target, {
    maxEvents: options.maxEvents,
    onEvent: () => render(),
  });

  clearButton.addEventListener("click", () => {
    observer.clearSnapshot();
    render();
  });

  clearEventsButton.addEventListener("click", () => {
    observer.clear();
    render();
  });

  eventFilter.addEventListener("input", render);

  DEBUG_FILTER_PRESETS.forEach((preset) => {
    const button = document.createElement("button");
    button.type = "button";
    button.textContent = preset.label;
    button.className = "xpressui-debug-panel__filter-preset";
    button.setAttribute("data-filter-value", preset.value);
    button.addEventListener("click", () => {
      eventFilter.value = preset.value;
      render();
    });
    filterPresets.appendChild(button);
  });

  render();

  return {
    element,
    observer,
    refresh() {
      render();
    },
    clearSnapshot() {
      observer.clearSnapshot();
      render();
    },
    detach() {
      status.textContent = "Status: detached";
      observer.detach();
      element.remove();
    },
  };
}
