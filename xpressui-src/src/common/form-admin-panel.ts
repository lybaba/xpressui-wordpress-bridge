import type {
  TLocalFormIncidentSummary,
  TLocalFormOperationalSummary,
} from "./form-admin";

export type TFormAdminPanelSource = {
  getOperationalSummary(): TLocalFormOperationalSummary;
  getIncidentSummary(limit?: number): TLocalFormIncidentSummary;
};

export type TFormAdminPanel = {
  element: HTMLElement;
  refresh(): void;
  detach(): void;
};

export type TFormAdminPanelOptions = {
  className?: string;
  title?: string;
  incidentLimit?: number;
};

export function createFormAdminPanel(
  source: TFormAdminPanelSource,
  options: TFormAdminPanelOptions = {},
): TFormAdminPanel {
  const element = document.createElement("section");
  element.className = options.className || "xpressui-admin-panel";

  const title = document.createElement("strong");
  title.textContent = options.title || "Form Admin";

  const status = document.createElement("div");
  status.className = "xpressui-admin-panel__status";

  const actions = document.createElement("div");
  actions.className = "xpressui-admin-panel__actions";

  const refreshButton = document.createElement("button");
  refreshButton.type = "button";
  refreshButton.className = "xpressui-admin-panel__refresh";
  refreshButton.textContent = "Refresh";

  const operationalTitle = document.createElement("strong");
  operationalTitle.textContent = "Operational Summary";

  const operational = document.createElement("pre");
  operational.className = "xpressui-admin-panel__operational";

  const incidentsTitle = document.createElement("strong");
  incidentsTitle.textContent = "Incident Summary";

  const incidents = document.createElement("pre");
  incidents.className = "xpressui-admin-panel__incidents";

  element.appendChild(title);
  element.appendChild(status);
  actions.appendChild(refreshButton);
  element.appendChild(actions);
  element.appendChild(operationalTitle);
  element.appendChild(operational);
  element.appendChild(incidentsTitle);
  element.appendChild(incidents);

  const render = () => {
    status.textContent = "Status: ready";
    operational.textContent = JSON.stringify(source.getOperationalSummary(), null, 2);
    incidents.textContent = JSON.stringify(
      source.getIncidentSummary(options.incidentLimit),
      null,
      2,
    );
  };

  refreshButton.addEventListener("click", render);
  render();

  return {
    element,
    refresh() {
      render();
    },
    detach() {
      status.textContent = "Status: detached";
      element.remove();
    },
  };
}
