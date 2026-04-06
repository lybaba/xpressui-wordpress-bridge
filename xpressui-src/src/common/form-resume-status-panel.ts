import { getResumeShareCodeClaimPresentation } from "./resume-contract";

export type TFormResumeStatusPanel = {
  element: HTMLElement;
  refresh(): void;
  detach(): void;
};

export type TFormResumeStatusPanelOptions = {
  className?: string;
  title?: string;
};

function formatTimestamp(value?: number): string | null {
  return typeof value === "number" ? new Date(value).toISOString() : null;
}

export function createResumeStatusPanel(
  target: EventTarget,
  options: TFormResumeStatusPanelOptions = {},
): TFormResumeStatusPanel {
  const element = document.createElement("section");
  element.className = options.className || "xpressui-resume-status-panel";

  const title = document.createElement("strong");
  title.textContent = options.title || "Resume Status";

  const claimPill = document.createElement("div");
  claimPill.className = "xpressui-resume-status-panel__claim-pill";
  claimPill.setAttribute("data-tone", "info");

  const restorePill = document.createElement("div");
  restorePill.className = "xpressui-resume-status-panel__restore-pill";
  restorePill.setAttribute("data-tone", "info");

  const details = document.createElement("pre");
  details.className = "xpressui-resume-status-panel__details";

  element.appendChild(title);
  element.appendChild(claimPill);
  element.appendChild(restorePill);
  element.appendChild(details);

  let lastClaim: Record<string, any> | null = null;
  let lastRestore: Record<string, any> | null = null;

  const render = () => {
    const claimPresentation = getResumeShareCodeClaimPresentation(lastClaim);
    claimPill.setAttribute("data-tone", claimPresentation.tone);
    claimPill.textContent = `Claim: ${claimPresentation.label}`;

    const restoreTone = lastRestore?.status === "restored"
      ? "success"
      : lastRestore?.status === "claim_failed"
        ? "error"
        : "info";
    restorePill.setAttribute("data-tone", restoreTone);
    restorePill.textContent = `Restore: ${
      lastRestore?.status === "restored"
        ? "Restored"
        : lastRestore?.status === "claim_failed"
          ? "Restore blocked"
          : "Idle"
    }`;

    details.textContent = JSON.stringify({
      claim: lastClaim
        ? {
            status: lastClaim.status,
            code: lastClaim.code,
            retryAfterSeconds: lastClaim.retryAfterSeconds,
            blockedUntil: formatTimestamp(lastClaim.blockedUntil),
            expiresAt: formatTimestamp(lastClaim.expiresAt),
            message: lastClaim.message,
          }
        : null,
      restore: lastRestore
        ? {
            status: lastRestore.status,
            code: lastRestore.code,
            token: lastRestore.token,
            message: lastRestore.message,
          }
        : null,
    }, null, 2);
  };

  const claimListener = (event: Event) => {
    lastClaim = (event as CustomEvent<any>).detail?.result || null;
    render();
  };
  const restoreListener = (event: Event) => {
    lastRestore = (event as CustomEvent<any>).detail?.result || null;
    render();
  };

  target.addEventListener("xpressui:resume-share-code-claim-state", claimListener as EventListener);
  target.addEventListener("xpressui:resume-share-code-restore-state", restoreListener as EventListener);
  render();

  return {
    element,
    refresh() {
      render();
    },
    detach() {
      target.removeEventListener("xpressui:resume-share-code-claim-state", claimListener as EventListener);
      target.removeEventListener("xpressui:resume-share-code-restore-state", restoreListener as EventListener);
      element.remove();
    },
  };
}
