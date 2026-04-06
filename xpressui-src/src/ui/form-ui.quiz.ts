import TFieldConfig from "../common/TFieldConfig";
import type { TQuizAnswerItem } from "./form-ui.types";

export function isOpenQuizField(fieldConfig: TFieldConfig): boolean {
  return !Array.isArray(fieldConfig.choices) || !fieldConfig.choices.length;
}

export function getQuizCatalog(fieldConfig: TFieldConfig): TQuizAnswerItem[] {
  const source = Array.isArray(fieldConfig.choices) ? fieldConfig.choices : [];
  return source
    .slice(0, 20)
    .map((choice, index) => {
      const id = String((choice as any).value || (choice as any).id || `quiz_answer_${index + 1}`);
      const name = String((choice as any).label || (choice as any).title || (choice as any).name || id);
      const desc = typeof (choice as any).desc === "string" ? (choice as any).desc : "";
      const image_thumbnail = String((choice as any).image_thumbnail || (choice as any).imageThumbnail || "");
      const image_medium = String(
        (choice as any).image_medium || (choice as any).imageMedium || image_thumbnail,
      );
      const photosSource = (choice as any).photos_full ?? (choice as any).photosFull;
      const photos_full = Array.isArray(photosSource)
        ? photosSource.map((entry: any) => String(entry)).filter(Boolean)
        : [];
      const maxNumOfChoicesRaw = Number((choice as any).maxNumOfChoices);

      return {
        id,
        name,
        desc,
        image_thumbnail,
        image_medium,
        photos_full,
        maxNumOfChoices: Number.isFinite(maxNumOfChoicesRaw) && maxNumOfChoicesRaw > 0
          ? Math.round(maxNumOfChoicesRaw)
          : undefined,
      };
    });
}

export function getQuizSelectionItems(value: any): TQuizAnswerItem[] {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .filter((entry) => entry && typeof entry === "object")
    .map((entry, index) => ({
      id: String((entry as any).id || (entry as any).value || `quiz_answer_${index + 1}`),
      name: String((entry as any).label || (entry as any).title || (entry as any).name || (entry as any).id || ""),
      desc: typeof (entry as any).desc === "string" ? (entry as any).desc : "",
      image_thumbnail: String((entry as any).image_thumbnail || ""),
      image_medium: String((entry as any).image_medium || ""),
      photos_full: Array.isArray((entry as any).photos_full)
        ? (entry as any).photos_full.map((photo: any) => String(photo)).filter(Boolean)
        : [],
      maxNumOfChoices: Number.isFinite(Number((entry as any).maxNumOfChoices)) && Number((entry as any).maxNumOfChoices) > 0
        ? Math.round(Number((entry as any).maxNumOfChoices))
        : undefined,
    }))
    .filter((entry) => Boolean(entry.id));
}

export function getQuizSelectionLimit(fieldConfig: TFieldConfig): number {
  const catalogSize = getQuizCatalog(fieldConfig).length;
  if (!catalogSize) {
    return 0;
  }

  if (!fieldConfig.multiple) {
    return 1;
  }

  const configuredLimit = Number(fieldConfig.maxNumOfChoices);
  if (Number.isFinite(configuredLimit) && configuredLimit > 0) {
    return Math.min(Math.round(configuredLimit), catalogSize);
  }

  return catalogSize;
}

export function getNextQuizSelectionItems(
  fieldConfig: TFieldConfig,
  currentValue: any,
  answerId: string,
): TQuizAnswerItem[] {
  const catalog = getQuizCatalog(fieldConfig);
  const answer = catalog.find((entry) => entry.id === answerId);
  const currentItems = getQuizSelectionItems(currentValue);
  const existingIndex = currentItems.findIndex((entry) => entry.id === answerId);

  if (!answer) {
    return currentItems;
  }

  if (!fieldConfig.multiple) {
    return existingIndex >= 0 ? [] : [answer];
  }

  if (existingIndex >= 0) {
    return currentItems.filter((entry) => entry.id !== answerId);
  }

  const limit = getQuizSelectionLimit(fieldConfig);
  if (limit > 0 && currentItems.length >= limit) {
    return currentItems;
  }

  return [...currentItems, answer];
}
