import {
  createFormAdminPanel,
  TFormAdminPanel,
  TFormAdminPanelOptions,
  TFormAdminPanelSource,
} from "./form-admin-panel";
import {
  createFormDebugPanel,
  TFormDebugPanel,
  TFormDebugPanelOptions,
} from "./form-debug-panel";
import {
  createResumeStatusPanel,
  TFormResumeStatusPanel,
  TFormResumeStatusPanelOptions,
} from "./form-resume-status-panel";

export type TFormOpsPanel = {
  element: HTMLElement;
  debugPanel: TFormDebugPanel;
  adminPanel: TFormAdminPanel;
  resumePanel: TFormResumeStatusPanel | null;
  refresh(): void;
  clearSnapshot(): void;
  detach(): void;
};

export type TFormOpsPanelOptions = {
  className?: string;
  layoutClassName?: string;
  title?: string;
  debug?: TFormDebugPanelOptions;
  admin?: TFormAdminPanelOptions;
  resume?: TFormResumeStatusPanelOptions | false;
};

export function createFormOpsPanel(
  target: EventTarget,
  source: TFormAdminPanelSource,
  options: TFormOpsPanelOptions = {},
): TFormOpsPanel {
  const element = document.createElement("section");
  element.className = options.className || "xpressui-ops-panel";

  const title = document.createElement("strong");
  title.textContent = options.title || "Form Ops";

  const layout = document.createElement("div");
  layout.className = options.layoutClassName || "xpressui-ops-panel__layout";

  const debugPanel = createFormDebugPanel(target, {
    title: options.debug?.title || "Debug",
    className: options.debug?.className,
    maxEvents: options.debug?.maxEvents,
  });
  const adminPanel = createFormAdminPanel(source, {
    title: options.admin?.title || "Admin",
    className: options.admin?.className,
    incidentLimit: options.admin?.incidentLimit,
  });
  const resumePanel = options.resume === false
    ? null
    : createResumeStatusPanel(target, {
        title: options.resume?.title || "Resume Status",
        className: options.resume?.className,
      });

  layout.appendChild(debugPanel.element);
  layout.appendChild(adminPanel.element);
  if (resumePanel) {
    layout.appendChild(resumePanel.element);
  }
  element.appendChild(title);
  element.appendChild(layout);

  return {
    element,
    debugPanel,
    adminPanel,
    resumePanel,
    refresh() {
      debugPanel.refresh();
      adminPanel.refresh();
      resumePanel?.refresh();
    },
    clearSnapshot() {
      debugPanel.clearSnapshot();
      adminPanel.refresh();
      resumePanel?.refresh();
    },
    detach() {
      debugPanel.detach();
      adminPanel.detach();
      resumePanel?.detach();
      element.remove();
    },
  };
}
