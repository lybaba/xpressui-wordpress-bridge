type TChoiceCatalogHost = any;

function getMonthRangeAmount(choice: any): number | null {
  const amount = Number(choice.amount ?? choice.monthAmount ?? choice.paymentAmount ?? choice.salePrice ?? choice.sale_price);
  return Number.isFinite(amount) ? amount : null;
}

function formatMonthRangeMoney(amount: number, currency: string): string {
  const formattedAmount = new Intl.NumberFormat("fr-FR", {
    minimumFractionDigits: Number.isInteger(amount) ? 0 : 2,
    maximumFractionDigits: Number.isInteger(amount) ? 0 : 2,
  }).format(amount).replace(/[  ]/g, " ");
  return `${formattedAmount} ${currency}`.trim();
}

function getDateRangeMonth(choice: any): { key: string; label: string; date: string } {
  const date = String(choice.date || choice.startsAt || choice.value || "");
  const key = date.length >= 7 ? date.slice(0, 7) : "dates";
  const locale = String(choice.locale || "fr-FR").replace("_", "-");
  const parsedDate = date.length >= 10 ? new Date(`${date.slice(0, 10)}T00:00:00`) : null;
  const formattedMonth = parsedDate && !Number.isNaN(parsedDate.getTime())
    ? new Intl.DateTimeFormat(locale, { month: "long", year: "numeric" }).format(parsedDate)
    : "";
  const fallbackLabel = formattedMonth
    ? `${formattedMonth.charAt(0).toUpperCase()}${formattedMonth.slice(1)}`
    : key;
  const label = choice.monthName && choice.year
    ? `${choice.monthName} ${choice.year}`
    : fallbackLabel;
  return { key, label, date };
}

export function createChoiceCatalogRuntime(host: TChoiceCatalogHost) {
  const self = {
    isDateRange(fieldConfig: any): boolean {
      return (fieldConfig as any).choiceCatalogSnapshot?.kind === "date_range"
        || (fieldConfig as any).choiceCatalogRef?.catalogKind === "date_range"
        || (fieldConfig.choices || []).some((choice: any) => typeof choice?.date === "string");
    },

    isMonthRange(fieldConfig: any): boolean {
      return (fieldConfig as any).choiceCatalogSnapshot?.kind === "month_range"
        || (fieldConfig as any).choiceCatalogRef?.catalogKind === "month_range"
        || (fieldConfig.choices || []).some((choice: any) => typeof choice?.month === "string");
    },

    getMonthRangeBaselineAmountDisplay(fieldConfig: any): string {
      const choices = fieldConfig.choices || [];
      const currentMonth = new Date().toISOString().slice(0, 7);
      const baselineChoice =
        choices.find((choice: any) => Boolean(choice.defaultSelected || choice.default_selected) && !choice.disabled && choice.enabled !== false)
        || choices.find((choice: any) => String(choice.month || choice.value || "") === currentMonth && !choice.disabled && choice.enabled !== false)
        || choices.find((choice: any) => !choice.disabled && choice.enabled !== false && getMonthRangeAmount(choice) !== null);
      if (!baselineChoice) {
        return "";
      }
      const amount = getMonthRangeAmount(baselineChoice);
      if (amount === null) {
        return "";
      }
      const currency = String((baselineChoice as any).currency || (baselineChoice as any).monthCurrency || (baselineChoice as any).paymentCurrency || "").trim();
      return formatMonthRangeMoney(amount, currency);
    },

    updateMonthRangeTotal(fieldConfig: any, value: any): void {
      const totalNode = host.querySelector(`[data-month-range-total="${fieldConfig.name}"]`) as HTMLElement | null;
      if (!totalNode) {
        return;
      }
      const selectedValues = new Set(host.getChoiceSelectionItems(fieldConfig, value));
      let totalAmount = 0;
      let selectedCount = 0;
      let currency = "";
      (fieldConfig.choices || []).forEach((choice: any) => {
        const optionValue = String(choice.value ?? choice.id ?? choice.label ?? "");
        if (!optionValue || !selectedValues.has(optionValue)) {
          return;
        }
        selectedCount += 1;
        const amount = getMonthRangeAmount(choice);
        if (amount !== null) {
          totalAmount += amount;
          currency = currency || String(choice.currency || choice.monthCurrency || choice.paymentCurrency || "").trim();
        }
      });
      const countNode = totalNode.querySelector(`[data-month-range-total-count="${fieldConfig.name}"]`) as HTMLElement | null;
      const amountNode = totalNode.querySelector(`[data-month-range-total-amount="${fieldConfig.name}"]`) as HTMLElement | null;
      if (selectedCount > 0) {
        totalNode.removeAttribute("hidden");
        if (!totalNode.style.display || totalNode.style.display === "none") {
          totalNode.style.display = "inline-flex";
        }
        if (countNode) {
          countNode.textContent = `${selectedCount} cotisation${selectedCount > 1 ? "s" : ""}`;
        }
        if (amountNode) {
          amountNode.textContent = totalAmount > 0 ? formatMonthRangeMoney(totalAmount, currency) : "";
        }
        if (totalAmount > 0) {
          host.updatePaymentProofExpectedAmount(formatMonthRangeMoney(totalAmount, currency));
        }
      } else {
        totalNode.toggleAttribute("hidden", true);
        if (countNode) countNode.textContent = "";
        if (amountNode) amountNode.textContent = "";
        host.updatePaymentProofExpectedAmount(self.getMonthRangeBaselineAmountDisplay(fieldConfig));
      }
    },

    renderDateRangeSelection(fieldConfig: any, value: any, selectionElement: HTMLElement): void {
      const choices = fieldConfig.choices || [];
      const selectedValues = host.getChoiceSelectionItems(fieldConfig, value);
      const selectedMap = selectedValues.reduce((accumulator: Record<string, boolean>, item: string) => {
        accumulator[item] = true;
        return accumulator;
      }, {} as Record<string, boolean>);
      const months = new Map<string, { label: string; choices: any[] }>();
      const checkIcon =
        "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 13l4 4L19 7'/%3E%3C/svg%3E\")";
      const themeBorder = "color-mix(in srgb, var(--template-border) 75%, transparent)";
      const themeCheckBorder = "color-mix(in srgb, var(--template-muted-text) 70%, transparent)";
      const themeDisabledBackground = "color-mix(in srgb, var(--template-border) 36%, transparent)";
      const themeSelectedBackground = "color-mix(in srgb, var(--template-primary) 22%, var(--template-surface))";

      choices.forEach((choice: any) => {
        const month = getDateRangeMonth(choice);
        if (!months.has(month.key)) {
          months.set(month.key, { label: month.label, choices: [] });
        }
        months.get(month.key)?.choices.push(choice);
      });

      selectionElement.replaceChildren();
      selectionElement.className = "template-date-range-grid";
      selectionElement.setAttribute("data-choice-list-grid", fieldConfig.name);
      selectionElement.setAttribute("data-choice-date-range", "true");
      Object.assign(selectionElement.style, {
        display: "grid",
        gridTemplateColumns: "repeat(auto-fit, minmax(240px, 1fr))",
        gap: "14px",
        alignItems: "start",
        marginTop: "12px",
      });

      months.forEach((month, monthKey) => {
        const monthElement = document.createElement("div");
        monthElement.className = "template-date-range-month";
        monthElement.setAttribute("data-choice-date-month", monthKey);
        Object.assign(monthElement.style, {
          overflow: "hidden",
          border: `1.5px solid ${themeBorder}`,
          borderRadius: "12px",
          background: "var(--template-surface)",
        });

        const title = document.createElement("div");
        title.className = "template-date-range-month-title";
        const availableMonthChoices = month.choices
          .filter((choice: any) => !choice.disabled && choice.enabled !== false)
          .map((choice: any) => String(choice.value ?? choice.id ?? choice.label ?? ""))
          .filter(Boolean);
        const selectedMonthCount = availableMonthChoices.filter((choiceValue) => selectedMap[choiceValue]).length;
        const isMonthSelected = availableMonthChoices.length > 0 && selectedMonthCount === availableMonthChoices.length;
        const isMonthMixed = selectedMonthCount > 0 && !isMonthSelected;
        Object.assign(title.style, {
          display: "grid",
          gridTemplateColumns: "minmax(0, 1fr) 18px",
          alignItems: "center",
          gap: "6px",
          padding: "8px 10px",
          textAlign: "left",
          fontSize: "14px",
          fontWeight: "900",
          color: "var(--template-text)",
          borderBottom: `1px solid ${themeBorder}`,
          background: "color-mix(in srgb, var(--template-primary) 6%, var(--template-surface))",
        });

        const titleLabel = document.createElement("span");
        titleLabel.className = "template-date-range-month-label";
        titleLabel.textContent = month.label;
        Object.assign(titleLabel.style, { minWidth: "0", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" });
        title.appendChild(titleLabel);

        const monthToggle = document.createElement("button");
        monthToggle.type = "button";
        monthToggle.className = "template-date-range-month-toggle";
        monthToggle.setAttribute("data-date-range-month-toggle", fieldConfig.name);
        monthToggle.setAttribute("data-choice-date-month", monthKey);
        monthToggle.setAttribute("data-selected", isMonthSelected ? "true" : "false");
        monthToggle.setAttribute("data-mixed", isMonthMixed ? "true" : "false");
        monthToggle.setAttribute("aria-pressed", isMonthSelected ? "true" : "false");
        monthToggle.setAttribute(
          "aria-label",
          `${isMonthSelected ? "Retirer" : "Selectionner"} tous les jours disponibles de ${month.label}`,
        );
        Object.assign(monthToggle.style, {
          display: "grid",
          placeItems: "center",
          width: "18px",
          height: "18px",
          padding: "0",
          border: "0",
          borderRadius: "4px",
          background: "transparent",
          cursor: availableMonthChoices.length ? "pointer" : "not-allowed",
        });
        const monthCheck = document.createElement("span");
        monthCheck.className = "template-date-range-check";
        Object.assign(monthCheck.style, {
          width: "14px",
          height: "14px",
          border: `1.5px solid ${isMonthSelected || isMonthMixed ? "var(--template-primary)" : themeCheckBorder}`,
          borderRadius: "4px",
          backgroundColor: isMonthSelected || isMonthMixed ? "var(--template-primary)" : "var(--template-surface)",
          backgroundImage: isMonthMixed
            ? "linear-gradient(#fff, #fff)"
            : isMonthSelected
              ? checkIcon
              : "",
          backgroundPosition: "center",
          backgroundRepeat: "no-repeat",
          backgroundSize: isMonthMixed ? "8px 2px" : "10px",
        });
        monthToggle.appendChild(monthCheck);
        title.appendChild(monthToggle);
        monthElement.appendChild(title);

        month.choices.forEach((choice: any) => {
          const optionValue = String(choice.value ?? choice.id ?? choice.label ?? "");
          const selected = Boolean(selectedMap[optionValue]);
          const disabled = Boolean(choice.disabled || choice.enabled === false);
          const date = getDateRangeMonth(choice).date;
          const dayName = String(choice.dayName || choice.day_name || choice.label || optionValue).replace(/\s+\d+$/, "");
          const dayNumber = String(choice.dayNumber || choice.day_number || (date.length >= 10 ? date.slice(8, 10) : ""));
          const rawNote = String(choice.desc || choice.reason || "");
          const note = rawNote === "weekday_not_allowed" ? "" : rawNote;

          const row = document.createElement("article");
          row.className = "template-date-range-day";
          row.setAttribute("data-choice-option-action", "toggle");
          row.setAttribute("data-choice-option-value", optionValue);
          row.setAttribute("data-selected", selected ? "true" : "false");
          row.setAttribute("data-disabled", disabled ? "true" : "false");
          if (date) row.setAttribute("data-choice-date", date);
          row.setAttribute("role", "checkbox");
          row.setAttribute("tabindex", disabled ? "-1" : "0");
          row.setAttribute("aria-checked", selected ? "true" : "false");
          Object.assign(row.style, {
            display: "grid",
            gridTemplateColumns: "minmax(76px, 1fr) 34px minmax(0, 1.4fr) 18px",
            alignItems: "center",
            minHeight: "30px",
            gap: "6px",
            padding: "4px 8px",
            borderBottom: `1px solid ${themeBorder}`,
            cursor: disabled ? "not-allowed" : "pointer",
            color: disabled ? "var(--template-muted-text)" : "var(--template-text)",
            background: selected ? themeSelectedBackground : disabled ? themeDisabledBackground : "var(--template-surface)",
          });

          const dayNameElement = document.createElement("span");
          dayNameElement.className = "template-date-range-day-name";
          dayNameElement.setAttribute("data-choice-option-title", optionValue);
          dayNameElement.textContent = dayName;
          Object.assign(dayNameElement.style, { minWidth: "0", fontSize: "13px", fontWeight: "800", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" });
          row.appendChild(dayNameElement);

          const dayNumberElement = document.createElement("span");
          dayNumberElement.className = "template-date-range-day-number";
          dayNumberElement.textContent = dayNumber;
          Object.assign(dayNumberElement.style, { fontSize: "13px", fontWeight: "800", textAlign: "center", fontVariantNumeric: "tabular-nums" });
          row.appendChild(dayNumberElement);

          const noteElement = document.createElement("span");
          noteElement.className = "template-date-range-note";
          if (note) noteElement.setAttribute("data-choice-option-description", optionValue);
          noteElement.textContent = note;
          Object.assign(noteElement.style, { minWidth: "0", fontSize: "12px", fontWeight: "700", textAlign: "center", color: "var(--template-muted-text)", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" });
          row.appendChild(noteElement);

          const check = document.createElement("span");
          check.className = "template-date-range-check";
          Object.assign(check.style, {
            width: "14px",
            height: "14px",
            border: `1.5px solid ${selected ? "var(--template-primary)" : themeCheckBorder}`,
            borderRadius: "4px",
            backgroundColor: selected ? "var(--template-primary)" : "var(--template-surface)",
            backgroundImage: selected ? checkIcon : "",
            backgroundPosition: "center",
            backgroundRepeat: "no-repeat",
            backgroundSize: "10px",
          });
          row.appendChild(check);

          const footer = document.createElement("span");
          footer.className = "template-choice-footer";
          footer.setAttribute("data-choice-option-footer", optionValue);
          footer.hidden = !disabled;
          footer.textContent = disabled ? "Unavailable" : "";
          row.appendChild(footer);

          monthElement.appendChild(row);
        });

        selectionElement.appendChild(monthElement);
      });
    },

    renderMonthRangeSelection(fieldConfig: any, value: any, selectionElement: HTMLElement): void {
      const choices = fieldConfig.choices || [];
      const selectedValues = host.getChoiceSelectionItems(fieldConfig, value);
      const selectedMap = selectedValues.reduce((accumulator: Record<string, boolean>, item: string) => {
        accumulator[item] = true;
        return accumulator;
      }, {} as Record<string, boolean>);
      const yearGroups = new Map<string, any[]>();
      choices.forEach((choice: any) => {
        const year = String(choice.year || String(choice.month || choice.value || "").slice(0, 4) || "Months");
        if (!yearGroups.has(year)) {
          yearGroups.set(year, []);
        }
        yearGroups.get(year)?.push(choice);
      });
      const yearKeys = Array.from(yearGroups.keys());
      const grid =
        (selectionElement.querySelector(
          `[data-choice-list-grid="${fieldConfig.name}"]`,
        ) as HTMLDivElement | null)
        ?? (selectionElement as HTMLDivElement);
      const selectedChoice = choices.find((choice: any) => {
        const optionValue = String(choice.value ?? choice.id ?? choice.label ?? "");
        return optionValue && selectedMap[optionValue];
      }) as any;
      const defaultChoice = choices.find((choice: any) => Boolean(choice.defaultSelected || choice.default_selected)) as any;
      const currentYear = String(new Date().getFullYear());
      const currentActiveYear = String(
        (selectionElement.querySelector("[data-month-range-year-select]") as HTMLSelectElement | null)?.value
        || grid?.dataset.activeYear
        || selectedChoice?.year
        || String(selectedChoice?.month || selectedChoice?.value || "").slice(0, 4)
        || defaultChoice?.year
        || String(defaultChoice?.month || defaultChoice?.value || "").slice(0, 4)
        || (yearKeys.includes(currentYear) ? currentYear : "")
        || "",
      );
      const activeYear = yearKeys.includes(currentActiveYear) ? currentActiveYear : yearKeys[0] || "";

      grid.className = "template-month-range-grid";
      grid.setAttribute("data-choice-list-grid", fieldConfig.name);
      grid.setAttribute("data-choice-month-range", "true");
      grid.dataset.activeYear = activeYear;
      grid.removeAttribute("style");
      grid.replaceChildren();

      yearGroups.forEach((yearChoices, year) => {
        const yearWrapper = document.createElement("section");
        yearWrapper.className = "template-month-range-year";
        yearWrapper.setAttribute("data-choice-year-group", year);
        yearWrapper.setAttribute("data-active", year === activeYear ? "true" : "false");
        yearWrapper.hidden = year !== activeYear;
        grid.appendChild(yearWrapper);

        const availableYearChoices = yearChoices
          .filter((choice: any) => !choice.disabled && choice.enabled !== false)
          .map((choice: any) => String(choice.value ?? choice.id ?? choice.label ?? ""))
          .filter(Boolean);
        const selectedYearCount = availableYearChoices.filter((choiceValue) => selectedMap[choiceValue]).length;
        const isYearSelected = availableYearChoices.length > 0 && selectedYearCount === availableYearChoices.length;
        const isYearMixed = selectedYearCount > 0 && !isYearSelected;

        const yearHeader = document.createElement("div");
        yearHeader.className = "template-month-range-year-header";
        yearWrapper.appendChild(yearHeader);

        const yearSelectLabel = document.createElement("label");
        yearSelectLabel.className = "template-month-range-year-select-label";
        yearHeader.appendChild(yearSelectLabel);

        const yearSelectText = document.createElement("span");
        yearSelectText.textContent = "Year";
        yearSelectLabel.appendChild(yearSelectText);

        const yearSelect = document.createElement("select");
        yearSelect.className = "template-month-range-year-select";
        yearSelect.setAttribute("data-month-range-year-select", fieldConfig.name);
        yearSelectLabel.appendChild(yearSelect);
        yearKeys.forEach((yearKey) => {
          const option = document.createElement("option");
          option.value = yearKey;
          option.textContent = yearKey;
          option.selected = yearKey === activeYear;
          yearSelect.appendChild(option);
        });

        const yearCheckLabel = document.createElement("label");
        yearCheckLabel.className = "template-month-range-year-check-label";
        yearHeader.appendChild(yearCheckLabel);

        const yearCheck = document.createElement("input");
        yearCheck.type = "checkbox";
        yearCheck.className = "template-month-range-year-check";
        yearCheck.setAttribute("data-month-range-year-checkbox", fieldConfig.name);
        yearCheck.setAttribute("data-choice-year", year);
        yearCheck.setAttribute("data-selected", isYearSelected ? "true" : "false");
        yearCheck.setAttribute("data-mixed", isYearMixed ? "true" : "false");
        yearCheck.checked = isYearSelected;
        yearCheck.indeterminate = isYearMixed;
        yearCheckLabel.appendChild(yearCheck);

        const yearCheckText = document.createElement("span");
        yearCheckText.textContent = "Select full year";
        yearCheckLabel.appendChild(yearCheckText);

        const cardsGrid = document.createElement("div");
        cardsGrid.className = "template-month-range-year-grid";
        yearWrapper.appendChild(cardsGrid);

        yearChoices.forEach((choice: any) => {
          const optionValue = String(choice.value ?? choice.id ?? choice.label ?? "");
          const selected = Boolean(selectedMap[optionValue]);
          const disabled = Boolean(choice.disabled || choice.enabled === false);
          const amount = getMonthRangeAmount(choice);
          const currency = String(choice.currency || choice.monthCurrency || choice.paymentCurrency || "").trim();
          const subtext = String(choice.amountDisplay || choice.amount_display || (amount !== null ? formatMonthRangeMoney(amount, currency) : choice.desc || "") || "");

          const card = document.createElement("article");
          cardsGrid.appendChild(card);
          card.className = "template-month-range-card";
          card.setAttribute("data-choice-option-action", "toggle");
          card.setAttribute("data-choice-option-value", optionValue);
          card.setAttribute("data-selected", selected ? "true" : "false");
          card.setAttribute("data-disabled", disabled ? "true" : "false");
          card.setAttribute("role", "checkbox");
          card.setAttribute("tabindex", disabled ? "-1" : "0");
          card.setAttribute("aria-checked", selected ? "true" : "false");
          if (choice.month) card.setAttribute("data-choice-month", String(choice.month));
          if (amount !== null) card.setAttribute("data-choice-month-amount", String(amount));
          if (currency) card.setAttribute("data-choice-month-currency", currency);

          const copy = document.createElement("span");
          copy.className = "template-month-range-copy";
          card.appendChild(copy);

          const title = document.createElement("span");
          title.className = "template-month-range-title";
          title.setAttribute("data-choice-option-title", optionValue);
          copy.appendChild(title);
          title.textContent = String(choice.label ?? optionValue);

          const description = document.createElement("span");
          description.className = "template-month-range-subtext";
          description.setAttribute("data-choice-option-description", optionValue);
          copy.appendChild(description);
          description.textContent = subtext;

          const check = document.createElement("span");
          check.className = "template-month-range-check";
          check.setAttribute("aria-hidden", "true");
          card.appendChild(check);

          const footer = document.createElement("span");
          footer.className = "template-choice-footer";
          footer.setAttribute("data-choice-option-footer", optionValue);
          footer.hidden = !disabled;
          footer.textContent = disabled ? "Unavailable" : "";
          card.appendChild(footer);
        });
      });

      Array.from(grid.querySelectorAll("[data-choice-option-value]")).forEach((node) => {
        const optionValue = (node as HTMLElement).getAttribute("data-choice-option-value");
        if (optionValue && !choices.some((choice: any) => String(choice.value ?? choice.id ?? choice.label ?? "") === optionValue)) {
          node.remove();
        }
      });
      self.updateMonthRangeTotal(fieldConfig, value);
    },

    getNextChoiceMonthSelectionValue(fieldConfig: any, currentValue: any, monthKey: string): any {
      const currentValues = Array.isArray(currentValue)
        ? currentValue.map((entry) => String(entry))
        : [];
      const nextValues = new Set(currentValues);
      const monthChoices = (fieldConfig.choices || [])
        .filter((choice: any) => getDateRangeMonth(choice).key === monthKey)
        .filter((choice: any) => !choice.disabled && choice.enabled !== false)
        .map((choice: any): string => String(choice.value ?? choice.id ?? choice.label ?? ""))
        .filter(Boolean);
      if (!monthChoices.length) {
        return currentValues;
      }
      const shouldClearMonth = (monthChoices as string[]).every((choiceValue) => nextValues.has(choiceValue));
      (monthChoices as string[]).forEach((choiceValue) => {
        if (shouldClearMonth) {
          nextValues.delete(choiceValue);
        } else {
          nextValues.add(choiceValue);
        }
      });
      const knownChoiceValues = new Set(
        (fieldConfig.choices || [])
          .map((choice: any) => String(choice.value ?? choice.id ?? choice.label ?? ""))
          .filter(Boolean),
      );
      const orderedKnownValues = (fieldConfig.choices || [])
        .map((choice: any) => String(choice.value ?? choice.id ?? choice.label ?? ""))
        .filter((choiceValue: string) => choiceValue && nextValues.has(choiceValue));
      const unknownValues = currentValues.filter((choiceValue: string) => !knownChoiceValues.has(choiceValue));
      return [...orderedKnownValues, ...unknownValues];
    },

    getNextChoiceYearSelectionValue(fieldConfig: any, currentValue: any, year: string): any {
      const currentValues = Array.isArray(currentValue)
        ? currentValue.map((entry) => String(entry))
        : [];
      const nextValues = new Set(currentValues);
      const yearChoices = (fieldConfig.choices || [])
        .filter((choice: any) => String(choice.year || "").trim() === year)
        .filter((choice: any) => !choice.disabled && choice.enabled !== false)
        .map((choice: any) => String(choice.value ?? choice.id ?? choice.label ?? ""))
        .filter(Boolean);
      if (!yearChoices.length) {
        return currentValues;
      }
      const shouldClearYear = (yearChoices as string[]).every((choiceValue) => nextValues.has(choiceValue));
      (yearChoices as string[]).forEach((choiceValue) => {
        if (shouldClearYear) {
          nextValues.delete(choiceValue);
        } else {
          nextValues.add(choiceValue);
        }
      });
      const knownChoiceValues = new Set(
        (fieldConfig.choices || [])
          .map((choice: any) => String(choice.value ?? choice.id ?? choice.label ?? ""))
          .filter(Boolean),
      );
      const orderedKnownValues = (fieldConfig.choices || [])
        .map((choice: any) => String(choice.value ?? choice.id ?? choice.label ?? ""))
        .filter((choiceValue: string) => choiceValue && nextValues.has(choiceValue));
      const unknownValues = currentValues.filter((choiceValue: string) => !knownChoiceValues.has(choiceValue));
      return [...orderedKnownValues, ...unknownValues];
    },
  };

  return self;
}
