import {
  getNextQuizSelectionItems as getNextNormalizedQuizSelectionItems,
  getQuizCatalog as getQuizCatalogItems,
  getQuizSelectionItems as getNormalizedQuizSelectionItems,
  getQuizSelectionLimit as getNormalizedQuizSelectionLimit,
} from "./form-ui.quiz";

type TQuizRuntimeHost = any;

export function createQuizRuntime(host: TQuizRuntimeHost) {
  return {
    getQuizCatalog(fieldConfig: any) {
      return getQuizCatalogItems(fieldConfig);
    },

    getQuizSelectionItems(value: any) {
      return getNormalizedQuizSelectionItems(value);
    },

    getQuizSelectionLimit(fieldConfig: any) {
      return getNormalizedQuizSelectionLimit(fieldConfig);
    },

    getNextQuizSelectionItems(fieldConfig: any, currentValue: any, answerId: string) {
      return getNextNormalizedQuizSelectionItems(fieldConfig, currentValue, answerId);
    },

    renderQuizSelection(fieldConfig: any, value: any, selectionElement: HTMLElement | null) {
      if (!selectionElement) {
        return;
      }

      if (host.isOpenQuizField(fieldConfig)) {
        const wrapper = host.ensureSelectionChild(
          selectionElement,
          `[data-quiz-open-wrapper="${fieldConfig.name}"]`,
          "div",
          "grid gap-2",
          "data-quiz-open-wrapper",
          fieldConfig.name,
        ) as HTMLDivElement;
        wrapper.style.gap = "6px";
        const textarea = host.ensureSelectionChild(
          wrapper,
          `[data-quiz-open-answer="${fieldConfig.name}"]`,
          "textarea",
          "textarea textarea-bordered w-full",
          "data-quiz-open-answer",
          fieldConfig.name,
        ) as HTMLTextAreaElement;
        textarea.rows = 2;
        textarea.placeholder = fieldConfig.placeholder || "Write your answer";
        textarea.style.background = "#ffffff";
        textarea.style.minHeight = "72px";

        const nextValue = typeof value === "string" ? value : "";
        if (textarea.value !== nextValue && document.activeElement !== textarea) {
          textarea.value = nextValue;
        }
        const maxLen = Number(fieldConfig.maxLen);
        if (Number.isFinite(maxLen) && maxLen > 0) {
          textarea.maxLength = Math.round(maxLen);
        } else {
          textarea.removeAttribute("maxlength");
        }
        return;
      }

      const answers = host.getQuizCatalog(fieldConfig);
      const selectedItems = host.getQuizSelectionItems(value);
      const selectedMap = selectedItems.reduce((accumulator: Record<string, boolean>, item: any) => {
        accumulator[item.id] = true;
        return accumulator;
      }, {});
      const selectionLimit = host.getQuizSelectionLimit(fieldConfig);
      const limitReached = selectionLimit > 0 && selectedItems.length >= selectionLimit;

      let grid = selectionElement.querySelector(
        `[data-quiz-catalog="${fieldConfig.name}"]`,
      ) as HTMLDivElement | null;
      if (!grid) {
        grid = selectionElement as HTMLDivElement;
        grid.setAttribute("data-quiz-catalog", fieldConfig.name);
      }
      const quizLayout = host.resolveChoiceLayout(fieldConfig, grid, "horizontal");
      host.applyChoiceLayout(grid, quizLayout, "150px");
      grid.style.marginBottom = "14px";

      answers.forEach((answer: any) => {
        const selected = Boolean(selectedMap[answer.id]);
        const disabled = !selected && limitReached && fieldConfig.multiple;
        const previewSrc = answer.image_medium || answer.image_thumbnail;
        const displayLabel = host.getChoiceDisplayLabel(answer, answer.id);
        const compactCard = host.isCompactChoiceCard(displayLabel, answer.desc, previewSrc);

        let card = grid.querySelector(`[data-quiz-answer-card="${answer.id}"]`) as HTMLDivElement | null;
        if (!card) {
          card = document.createElement("div");
          card.setAttribute("data-quiz-answer-card", answer.id);
          grid.appendChild(card);
        }
        card.setAttribute("data-quiz-answer-action", "toggle");
        card.setAttribute("data-quiz-answer-id", answer.id);
        card.setAttribute("data-selected", selected ? "true" : "false");
        card.setAttribute("data-disabled", disabled ? "true" : "false");
        card.setAttribute("role", "button");
        card.setAttribute("tabindex", disabled ? "-1" : "0");
        card.setAttribute("aria-pressed", selected ? "true" : "false");
        card.className = "template-choice-card";
        card.setAttribute("data-choice-density", compactCard ? "compact" : "default");
        card.style.cursor = disabled ? "not-allowed" : "pointer";
        card.style.opacity = disabled ? "0.55" : "1";
        card.style.borderColor = selected ? "rgba(15, 118, 110, 0.38)" : "";
        card.style.boxShadow = selected ? "0 0 0 2px rgba(15, 118, 110, 0.12)" : "";
        card.style.background = selected ? "rgba(240, 253, 250, 0.96)" : "rgba(255, 255, 255, 0.98)";
        card.style.minHeight = compactCard ? "0" : previewSrc ? "unset" : "56px";
        card.style.padding = compactCard ? "10px 12px" : "12px 14px";

        let preview = card.querySelector("[data-quiz-answer-image]") as HTMLImageElement | null;
        if (!preview && previewSrc) {
          preview = document.createElement("img");
          preview.setAttribute("data-quiz-answer-image", answer.id);
          card.appendChild(preview);
        }
        if (preview) {
          preview.src = previewSrc || "";
          preview.alt = displayLabel;
          preview.style.width = "100%";
          preview.style.height = "120px";
          preview.style.objectFit = "cover";
          preview.style.borderRadius = "12px";
          preview.style.marginBottom = "4px";
        }

        let title = card.querySelector("[data-quiz-answer-title]") as HTMLDivElement | null;
        if (!title) {
          title = document.createElement("div");
          title.setAttribute("data-quiz-answer-title", answer.id);
          card.appendChild(title);
        }
        title.className = "template-choice-title";
        title.style.overflowWrap = "anywhere";
        title.style.wordBreak = "break-word";
        title.textContent = displayLabel;

        let stateRow = card.querySelector("[data-quiz-answer-state]") as HTMLDivElement | null;
        if (!stateRow) {
          stateRow = document.createElement("div");
          stateRow.setAttribute("data-quiz-answer-state", answer.id);
          card.appendChild(stateRow);
        }
        stateRow.className = "template-gallery-caption";
        stateRow.style.justifyContent = "flex-start";

        let modeBadge = stateRow.querySelector("[data-quiz-mode]") as HTMLSpanElement | null;
        if (!modeBadge) {
          modeBadge = document.createElement("span");
          modeBadge.setAttribute("data-quiz-mode", answer.id);
          stateRow.appendChild(modeBadge);
        }
        modeBadge.hidden = true;
        modeBadge.textContent = "";

        let selectedBadge = stateRow.querySelector("[data-quiz-selected-state]") as HTMLSpanElement | null;
        if (!selectedBadge) {
          selectedBadge = document.createElement("span");
          selectedBadge.setAttribute("data-quiz-selected-state", answer.id);
          stateRow.appendChild(selectedBadge);
        }
        if (selected) {
          selectedBadge.hidden = true;
          selectedBadge.className = "";
          selectedBadge.style.padding = "0";
          selectedBadge.style.borderRadius = "";
          selectedBadge.style.background = "transparent";
          selectedBadge.style.color = "";
          selectedBadge.style.whiteSpace = "nowrap";
          selectedBadge.textContent = "";
        } else if (disabled) {
          selectedBadge.hidden = false;
          selectedBadge.className = "template-choice-footer";
          selectedBadge.style.padding = "4px 8px";
          selectedBadge.style.borderRadius = "999px";
          selectedBadge.style.background = "rgba(148, 163, 184, 0.12)";
          selectedBadge.style.color = "#475569";
          selectedBadge.style.whiteSpace = "nowrap";
          selectedBadge.textContent = "Limit reached";
        } else {
          selectedBadge.hidden = true;
          selectedBadge.className = "template-choice-footer";
          selectedBadge.style.padding = "0";
          selectedBadge.style.background = "transparent";
          selectedBadge.style.color = "";
          selectedBadge.style.whiteSpace = "nowrap";
          selectedBadge.textContent = "";
        }
      });

      const compactOnly = answers.length > 0 && answers.every((answer: any) =>
        host.isCompactChoiceCard(
          host.getChoiceDisplayLabel(answer, answer.id),
          answer.desc,
          answer.image_medium || answer.image_thumbnail,
        )
      );
      if (compactOnly) {
        grid.setAttribute("data-choice-density", "compact");
        grid.style.gridTemplateColumns = "repeat(auto-fit, minmax(132px, max-content))";
        grid.style.justifyContent = "start";
      } else {
        grid.removeAttribute("data-choice-density");
        grid.style.justifyContent = "";
        host.applyChoiceLayout(grid, quizLayout, "150px");
      }

      Array.from(grid.querySelectorAll("[data-quiz-answer-card]")).forEach((node) => {
        const answerId = (node as HTMLElement).getAttribute("data-quiz-answer-card");
        if (answerId && !answers.some((answer: any) => answer.id === answerId)) {
          node.remove();
        }
      });

      const selectedPanel = selectionElement.querySelector(
        `[data-quiz-selection="${fieldConfig.name}"]`,
      ) as HTMLDivElement | null;
      if (selectedPanel) {
        selectedPanel.style.display = "none";
        Array.from(selectedPanel.querySelectorAll("[data-quiz-selection-item]")).forEach((node) => node.remove());
      }
    },
  };
}
