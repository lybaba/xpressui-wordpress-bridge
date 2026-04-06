import type TFieldConfig from "../common/TFieldConfig";
import type TFormConfig from "../common/TFormConfig";
import type { TFormStepProgress } from "../common/form-steps";

export type TStepControlElements = {
  progressContainer: HTMLElement | null;
  progress: HTMLElement | null;
  progressBar: HTMLElement | null;
  summary: HTMLElement | null;
  actionsContainer: HTMLElement | null;
  backButton: HTMLButtonElement | null;
  nextButton: HTMLButtonElement | null;
};

export function getStepUiConfig(formConfig: TFormConfig | null) {
  return {
    progressPlacement: formConfig?.stepUi?.progressPlacement || "top",
    navigationPlacement: formConfig?.stepUi?.navigationPlacement || "bottom",
    backBehavior: formConfig?.stepUi?.backBehavior || "always",
  } as const;
}

export function getStepButtonLabels(
  formConfig: TFormConfig | null,
): { previous: string; next: string } {
  return {
    previous: formConfig?.navigationLabels?.prevLabel || "Back",
    next: formConfig?.navigationLabels?.nextLabel || "Next",
  };
}

export function getCurrentStepFieldElements(
  host: ParentNode,
  currentStepName: string | null,
): Array<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement> {
  if (!currentStepName) {
    return [];
  }

  return Array.from(
    host.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(
      "input[data-section-name], select[data-section-name], textarea[data-section-name]",
    ),
  ).filter((element) => element.getAttribute("data-section-name") === currentStepName);
}

export function getStepElements(host: ParentNode, sectionName: string): HTMLElement[] {
  const fieldNodes = Array.from(host.querySelectorAll("[data-section-name]"))
    .filter((node) => node.getAttribute("data-section-name") === sectionName)
    .map((node) => (node.closest("label") as HTMLElement | null) || node as HTMLElement);
  const sectionNodes = Array.from(host.querySelectorAll('[data-type="section"]'))
    .filter((node) => node.getAttribute("data-name") === sectionName)
    .map((node) => node as HTMLElement);

  return Array.from(new Set([...sectionNodes, ...fieldNodes]));
}

export function ensureStepControls(options: {
  formElem: HTMLFormElement;
  stepCount: number;
  buttonLabels: { previous: string; next: string };
  stepUi: ReturnType<typeof getStepUiConfig>;
  allowCreate?: boolean;
  existing?: TStepControlElements;
  onPrevious: () => void;
  onNext: () => void;
}): TStepControlElements {
  if (options.stepCount <= 1) {
    return {
      progressContainer: null,
      progress: null,
      progressBar: null,
      summary: null,
      actionsContainer: null,
      backButton: null,
      nextButton: null,
    };
  }

  const existingProgressContainer = options.formElem.querySelector("[data-form-step-progress-container]") as HTMLElement | null;
  const existingActionsContainer = options.formElem.querySelector("[data-form-step-actions]") as HTMLElement | null;
  if (existingProgressContainer || existingActionsContainer) {
    const backButton = options.formElem.querySelector('[data-step-action="back"]') as HTMLButtonElement | null;
    const nextButton = options.formElem.querySelector('[data-step-action="next"]') as HTMLButtonElement | null;

    if (backButton) {
      backButton.type = "button";
    }
    if (nextButton) {
      nextButton.type = "button";
    }

    return {
      progressContainer: existingProgressContainer,
      progress: options.formElem.querySelector("[data-form-step-progress]") as HTMLElement | null,
      progressBar: options.formElem.querySelector("[data-form-step-progress-bar]") as HTMLElement | null,
      summary: options.formElem.querySelector("[data-form-step-summary]") as HTMLElement | null,
      actionsContainer: existingActionsContainer,
      backButton,
      nextButton,
    };
  }

  if (options.allowCreate === false) {
    return {
      progressContainer: null,
      progress: null,
      progressBar: null,
      summary: null,
      actionsContainer: null,
      backButton: null,
      nextButton: null,
    };
  }

  const progressContainer = document.createElement("div");
  progressContainer.setAttribute("data-form-step-progress-container", "true");
  progressContainer.style.display = "grid";
  progressContainer.style.gap = "8px";
  progressContainer.style.margin = "16px 0";
  progressContainer.style.padding = "14px 16px";
  progressContainer.style.border = "1px solid rgba(148, 163, 184, 0.2)";
  progressContainer.style.borderRadius = "18px";
  progressContainer.style.background = "rgba(248, 250, 252, 0.92)";
  progressContainer.style.boxShadow = "0 16px 36px -30px rgba(15, 23, 42, 0.18)";

  const progressElement = document.createElement("div");
  progressElement.setAttribute("data-form-step-progress", "true");
  progressElement.className = "text-sm font-medium";
  progressElement.style.fontSize = "14px";
  progressElement.style.fontWeight = "600";
  progressElement.style.color = "rgb(15, 23, 42)";

  const progressTrack = document.createElement("div");
  progressTrack.setAttribute("data-form-step-progress-track", "true");
  progressTrack.style.width = "100%";
  progressTrack.style.height = "8px";
  progressTrack.style.borderRadius = "999px";
  progressTrack.style.background = "rgba(148, 163, 184, 0.2)";
  progressTrack.style.overflow = "hidden";

  const progressBar = document.createElement("div");
  progressBar.setAttribute("data-form-step-progress-bar", "true");
  progressBar.style.width = "0%";
  progressBar.style.height = "100%";
  progressBar.style.borderRadius = "999px";
  progressBar.style.background = "linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%)";
  progressBar.style.transition = "width 180ms ease";
  progressTrack.appendChild(progressBar);

  const summaryElement = document.createElement("div");
  summaryElement.setAttribute("data-form-step-summary", "true");
  summaryElement.className = "text-xs opacity-80";
  summaryElement.style.fontSize = "12px";
  summaryElement.style.opacity = "0.8";
  summaryElement.style.color = "rgb(71, 85, 105)";

  progressContainer.appendChild(progressElement);
  progressContainer.appendChild(progressTrack);
  progressContainer.appendChild(summaryElement);

  const actionsContainer = document.createElement("div");
  actionsContainer.setAttribute("data-form-step-actions", "true");
  actionsContainer.style.display = "flex";
  actionsContainer.style.flexWrap = "wrap";
  actionsContainer.style.alignItems = "center";
  actionsContainer.style.justifyContent = "space-between";
  actionsContainer.style.gap = "8px";
  actionsContainer.style.margin = "16px 0";
  actionsContainer.style.padding = "12px 16px";
  actionsContainer.style.border = "1px solid rgba(148, 163, 184, 0.2)";
  actionsContainer.style.borderRadius = "18px";
  actionsContainer.style.background = "rgba(255, 255, 255, 0.96)";
  actionsContainer.style.boxShadow = "0 16px 36px -30px rgba(15, 23, 42, 0.16)";

  const leadingActions = document.createElement("div");
  leadingActions.style.display = "flex";
  leadingActions.style.flexWrap = "wrap";
  leadingActions.style.alignItems = "center";
  leadingActions.style.gap = "8px";

  const trailingActions = document.createElement("div");
  trailingActions.style.display = "flex";
  trailingActions.style.flexWrap = "wrap";
  trailingActions.style.alignItems = "center";
  trailingActions.style.gap = "8px";

  const backButton = document.createElement("button");
  backButton.type = "button";
  backButton.textContent = options.buttonLabels.previous;
  backButton.setAttribute("data-step-action", "back");
  backButton.className = "btn btn-outline btn-sm";
  backButton.addEventListener("click", options.onPrevious);
  backButton.style.minWidth = "120px";

  const nextButton = document.createElement("button");
  nextButton.type = "button";
  nextButton.textContent = options.buttonLabels.next;
  nextButton.setAttribute("data-step-action", "next");
  nextButton.className = "btn btn-primary btn-sm";
  nextButton.addEventListener("click", options.onNext);
  nextButton.style.minWidth = "120px";

  leadingActions.appendChild(backButton);
  trailingActions.appendChild(nextButton);
  actionsContainer.appendChild(leadingActions);
  actionsContainer.appendChild(trailingActions);

  const firstAnchor = options.formElem.querySelector('[data-type="section"], [data-section-name]') as HTMLElement | null;
  if (options.stepUi.progressPlacement === "top" && firstAnchor) {
    options.formElem.insertBefore(progressContainer, firstAnchor);
  } else {
    options.formElem.appendChild(progressContainer);
  }

  if (options.stepUi.navigationPlacement === "top" && firstAnchor) {
    options.formElem.insertBefore(actionsContainer, firstAnchor);
  } else {
    options.formElem.appendChild(actionsContainer);
  }

  return {
    progressContainer,
    progress: progressElement,
    progressBar,
    summary: summaryElement,
    actionsContainer,
    backButton,
    nextButton,
  };
}

export function syncStepVisibility(options: {
  stepNames: string[];
  currentStepIndex: number;
  getStepElements: (sectionName: string) => HTMLElement[];
}): void {
  if (options.stepNames.length <= 1) {
    return;
  }

  options.stepNames.forEach((sectionName, index) => {
    const isActive = index === options.currentStepIndex;
    options.getStepElements(sectionName).forEach((element) => {
      const isStepHidden = element.getAttribute("data-step-hidden") === "true";
      if (!isActive) {
        element.setAttribute("data-step-hidden", "true");
        element.style.display = "none";
        return;
      }

      if (isStepHidden) {
        element.removeAttribute("data-step-hidden");
        element.style.display = "";
      }
    });
  });
}

export function formatStepSummary(
  summary: Array<{ field: string; label: string; value: any }>,
): string {
  return summary
    .map((entry) => `${entry.label}: ${Array.isArray(entry.value) ? entry.value.join(", ") : String(entry.value)}`)
    .join(" | ");
}

export function syncStepControls(options: {
  formElement: Element | null;
  stepCount: number;
  currentStepIndex: number;
  isLastStep: boolean;
  progress: TFormStepProgress;
  isCurrentStepSkippable: boolean;
  summary: Array<{ field: string; label: string; value: any }>;
  submitLockedByRules: boolean;
  submitLockMessage: string | null;
  isSubmitting: boolean;
  stepUi: ReturnType<typeof getStepUiConfig>;
  controls: TStepControlElements;
}): void {
  if (!options.formElement) {
    return;
  }

  const submitButtons = Array.from(
    options.formElement.querySelectorAll<HTMLButtonElement | HTMLInputElement>(
      'button[type="submit"], input[type="submit"]',
    ),
  );

  if (options.stepCount <= 1) {
    if (options.controls.progressContainer) {
      options.controls.progressContainer.style.display = "none";
    }
    if (options.controls.actionsContainer) {
      options.controls.actionsContainer.style.display = "none";
    }
    submitButtons.forEach((button) => {
      button.disabled = options.submitLockedByRules || options.isSubmitting;
      (button as HTMLElement).style.display = "";
      if (button instanceof HTMLButtonElement) {
        button.title = options.submitLockedByRules && options.submitLockMessage
          ? options.submitLockMessage
          : "";
      }
    });
    return;
  }

  if (options.controls.progressContainer) {
    options.controls.progressContainer.style.display = options.stepUi.progressPlacement === "hidden" ? "none" : "";
  }
  if (options.controls.actionsContainer) {
    options.controls.actionsContainer.style.display = "";
  }

  if (options.controls.progress) {
    const suffix = options.isCurrentStepSkippable ? " (Optional)" : "";
    options.controls.progress.textContent =
      `Step ${options.progress.stepNumber} of ${options.progress.stepCount} (${options.progress.percent}%)${suffix}`;
  }
  if (options.controls.progressBar) {
    options.controls.progressBar.style.width = `${options.progress.percent}%`;
  }
  if (options.controls.summary) {
    if (!options.summary.length) {
      options.controls.summary.textContent = "";
      options.controls.summary.style.display = "none";
    } else {
      options.controls.summary.textContent = formatStepSummary(options.summary);
      options.controls.summary.style.display = "";
    }
  }
  if (options.controls.backButton) {
    const canGoBack = options.stepUi.backBehavior === "always" && options.currentStepIndex > 0;
    options.controls.backButton.disabled = !canGoBack;
    options.controls.backButton.style.display = canGoBack ? "" : "none";
  }
  if (options.controls.nextButton) {
    options.controls.nextButton.disabled = options.isLastStep;
    options.controls.nextButton.style.display = options.isLastStep ? "none" : "";
    options.controls.nextButton.setAttribute("aria-disabled", options.isLastStep ? "true" : "false");
  }

  submitButtons.forEach((button) => {
    button.disabled = !options.isLastStep || options.submitLockedByRules || options.isSubmitting;
    (button as HTMLElement).style.display = options.isLastStep ? "" : "none";
    if (button instanceof HTMLButtonElement) {
      button.title = options.submitLockedByRules && options.submitLockMessage
        ? options.submitLockMessage
        : "";
    }
  });
}
