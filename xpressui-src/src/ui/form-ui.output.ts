import TFieldConfig from "../common/TFieldConfig";
import { IMAGE_GALLERY_TYPE, isFileLikeValue } from "../common/field";
import type {
  TFieldOutputRendererOverride,
  TFormHtmlSanitizer,
  TFormOutputRenderer,
  TFormRenderMode,
  TMediaDisplayPolicy,
  TOutputRendererType,
} from "./form-ui.types";

export function normalizeViewValue(value: any): any {
  if (value === undefined || value === null) {
    return value;
  }

  if (Array.isArray(value)) {
    return value
      .map((entry) => normalizeViewValue(entry))
      .filter((entry) => entry !== undefined && entry !== null && entry !== "");
  }

  if (isFileLikeValue(value)) {
    return value;
  }

  if (typeof value === "object") {
    const mediaValue =
      value.url
      || value.href
      || value.src
      || value.path
      || value.value
      || value.fileName
      || value.name;
    return mediaValue !== undefined ? mediaValue : value;
  }

  return value;
}

export function getDisplayText(value: any): string {
  if (value === undefined || value === null) {
    return "";
  }

  if (Array.isArray(value)) {
    return value
      .map((entry) => getDisplayText(entry))
      .filter(Boolean)
      .join(", ");
  }

  if (isFileLikeValue(value)) {
    return value.name || "";
  }

  if (typeof value === "object") {
    return JSON.stringify(value);
  }

  return String(value);
}

export function getFileMeta(value: any): { href: string; label: string } | null {
  const normalized = normalizeViewValue(value);
  if (isFileLikeValue(value)) {
    return {
      href: "",
      label: value.name || "file",
    };
  }

  const source = Array.isArray(normalized) ? normalized[0] : normalized;
  if (typeof source !== "string" || !source) {
    return null;
  }

  const fileNameCandidate = source.split("?")[0].split("/").pop();
  return {
    href: source,
    label: fileNameCandidate || source,
  };
}

export function getFileMetas(value: any): Array<{ href: string; label: string }> {
  if (Array.isArray(value)) {
    return value
      .map((entry) => getFileMeta(entry))
      .filter((entry): entry is { href: string; label: string } => Boolean(entry));
  }

  const meta = getFileMeta(value);
  return meta ? [meta] : [];
}

export function getMediaSources(value: any): string[] {
  const normalized = normalizeViewValue(value);
  const entries = Array.isArray(normalized) ? normalized : [normalized];
  return entries.filter((entry): entry is string => typeof entry === "string" && Boolean(entry));
}

export function isEmbeddableDocumentSource(source: string): boolean {
  if (!source) {
    return false;
  }

  if (source.startsWith("blob:") || source.startsWith("data:application/pdf")) {
    return true;
  }

  const lowerSource = source.toLowerCase();
  if (lowerSource.includes(".pdf")) {
    return true;
  }

  try {
    const parsed = new URL(source);
    const pathname = parsed.pathname.toLowerCase();
    return pathname.endsWith(".pdf");
  } catch {
    return false;
  }
}

export function getMapSources(value: any): string[] {
  const fromEntry = (entry: any): string[] => {
    if (entry === undefined || entry === null) {
      return [];
    }

    if (typeof entry === "string") {
      return entry ? [entry] : [];
    }

    if (Array.isArray(entry)) {
      return entry.flatMap((item) => fromEntry(item));
    }

    if (typeof entry === "object") {
      const latitude = Number((entry as any).lat ?? (entry as any).latitude);
      const longitude = Number((entry as any).lng ?? (entry as any).lon ?? (entry as any).longitude);
      if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
        return [`https://www.google.com/maps?q=${latitude},${longitude}&output=embed`];
      }

      const url =
        (entry as any).url
        || (entry as any).href
        || (entry as any).src
        || (entry as any).value
        || "";
      return typeof url === "string" && url ? [url] : [];
    }

    return [];
  };

  return fromEntry(value);
}

export function isSafeMapEmbedSource(source: string): boolean {
  try {
    const parsed = new URL(source);
    if (parsed.protocol !== "https:") {
      return false;
    }

    const allowedHosts = new Set([
      "www.google.com",
      "maps.google.com",
      "google.com",
      "www.openstreetmap.org",
      "openstreetmap.org",
      "www.bing.com",
      "bing.com",
    ]);
    return allowedHosts.has(parsed.hostname.toLowerCase());
  } catch {
    return false;
  }
}

export function resolveMediaDisplayPolicy(
  fieldMediaPolicies: Record<string, TMediaDisplayPolicy>,
  fieldConfig: TFieldConfig,
  inputElement: HTMLElement | null,
  rendererType?: string,
): TMediaDisplayPolicy {
  const runtimePolicy = fieldMediaPolicies[fieldConfig.name];
  if (runtimePolicy) {
    return runtimePolicy;
  }

  const attributePolicy =
    inputElement?.getAttribute("data-view-media-display")
    || inputElement?.getAttribute("data-view-resource-display");
  if (
    attributePolicy === "thumbnail"
    || attributePolicy === "large"
    || attributePolicy === "link"
    || attributePolicy === "gallery"
    || attributePolicy === "embed"
  ) {
    return attributePolicy;
  }

  if (rendererType === "file" || rendererType === "link") {
    return "link";
  }
  if (rendererType === "document") {
    return "embed";
  }

  return "large";
}

export function createDefaultHtmlSanitizer(): TFormHtmlSanitizer {
  return (html: string) => {
    if (typeof document === "undefined") {
      return html
        .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, "")
        .replace(/\son[a-z]+\s*=\s*(['"]).*?\1/gi, "");
    }

    const template = document.createElement("template");
    template.innerHTML = html;

    const blockedTags = ["script", "style", "iframe", "object", "embed", "link", "meta", "base"];
    template.content
      .querySelectorAll(blockedTags.join(","))
      .forEach((node) => node.remove());

    template.content.querySelectorAll("*").forEach((element) => {
      Array.from(element.attributes).forEach((attribute) => {
        const attributeName = attribute.name.toLowerCase();
        const attributeValue = String(attribute.value || "").trim().toLowerCase();
        if (attributeName.startsWith("on")) {
          element.removeAttribute(attribute.name);
          return;
        }

        if (attributeName === "srcdoc") {
          element.removeAttribute(attribute.name);
          return;
        }

        if (
          (attributeName === "href" || attributeName === "src" || attributeName === "xlink:href")
          && attributeValue.startsWith("javascript:")
        ) {
          element.removeAttribute(attribute.name);
        }
      });
    });

    return template.innerHTML;
  };
}

export function escapeHtml(value: string): string {
  return value
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

export function readTemplateTokenValue(values: Record<string, any>, tokenPath: string): any {
  const path = String(tokenPath || "").trim();
  if (!path) {
    return "";
  }

  if (Object.prototype.hasOwnProperty.call(values, path)) {
    return values[path];
  }

  const segments = path.split(".");
  let cursor: any = values;
  for (const segment of segments) {
    if (!cursor || typeof cursor !== "object" || !(segment in cursor)) {
      return "";
    }
    cursor = cursor[segment];
  }

  return cursor;
}

export function renderViewTemplate(
  template: string,
  values: Record<string, any>,
  escapeValues: boolean,
): string {
  return String(template).replace(/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/g, (_, token: string) => {
    const value = readTemplateTokenValue(values, token);
    const textValue = getDisplayText(value);
    return escapeValues ? escapeHtml(textValue) : textValue;
  });
}

export function resolveViewTemplateValue(
  fieldConfig: TFieldConfig,
  inputElement: HTMLElement,
  rendererType: string,
  value: any,
  valuesContext?: Record<string, any>,
): any {
  if (rendererType !== "html") {
    return value;
  }

  const template = inputElement.getAttribute("data-view-template") || fieldConfig.viewTemplate;
  if (!template) {
    return value;
  }

  const rawUnsafe =
    inputElement.getAttribute("data-view-template-unsafe")
    ?? (fieldConfig.viewTemplateUnsafe ? "true" : "false");
  const templateUnsafe = rawUnsafe === "true";
  const tokenValues = {
    ...(valuesContext || {}),
    value,
  };
  return renderViewTemplate(template, tokenValues, !templateUnsafe);
}

export function shouldRenderUnsafeHtml(
  host: HTMLElement,
  allowUnsafeHtml: boolean,
  inputElement: HTMLElement | null,
): boolean {
  if (allowUnsafeHtml) {
    return true;
  }

  const unsafeGlobalAttr = host.getAttribute("allow-unsafe-html") || host.getAttribute("data-allow-unsafe-html");
  if (unsafeGlobalAttr === "true") {
    return true;
  }

  if (!inputElement) {
    return false;
  }

  return inputElement.getAttribute("data-view-html-unsafe") === "true";
}

export function createDefaultOutputRenderers(options: {
  htmlSanitizer: TFormHtmlSanitizer;
}): Record<TOutputRendererType, TFormOutputRenderer> {
  const textRenderer: TFormOutputRenderer = ({ value }) => {
    const element = document.createElement("span");
    element.textContent = getDisplayText(value);
    return element;
  };

  const htmlRenderer: TFormOutputRenderer = ({ value, fieldConfig, mode, unsafeHtml }) => {
    const element = document.createElement("div");
    const htmlContent = getDisplayText(value);
    element.innerHTML = unsafeHtml
      ? htmlContent
      : options.htmlSanitizer(htmlContent, { fieldConfig, mode });
    return element;
  };

  const imageRenderer: TFormOutputRenderer = ({ value, fieldConfig, mediaDisplayPolicy }) => {
    const element = document.createElement("div");
    const sources = fieldConfig.type === IMAGE_GALLERY_TYPE
      ? (Array.isArray(value) ? value : [])
        .filter((entry) => entry && typeof entry === "object")
        .map((entry) =>
          String((entry as any).image_medium || (entry as any).image_thumbnail || ""),
        )
        .filter(Boolean)
      : getMediaSources(value);
    if (!sources.length) {
      return element;
    }

    if (mediaDisplayPolicy === "link") {
      sources.forEach((source) => {
        const link = document.createElement("a");
        link.href = source;
        link.target = "_blank";
        link.rel = "noreferrer";
        link.textContent = source;
        link.style.display = "block";
        element.appendChild(link);
      });
      return element;
    }

    const renderImage = (source: string) => {
      const image = document.createElement("img");
      image.src = source;
      image.alt = fieldConfig.label || fieldConfig.name || "image";
      image.style.height = "auto";
      image.style.display = "block";
      image.style.maxWidth = mediaDisplayPolicy === "thumbnail" ? "160px" : "100%";
      image.style.objectFit = mediaDisplayPolicy === "thumbnail" ? "cover" : "contain";
      return image;
    };

    if (mediaDisplayPolicy === "gallery") {
      const gallery = document.createElement("div");
      gallery.setAttribute("data-media-gallery", "true");
      gallery.style.display = "grid";
      gallery.style.gridTemplateColumns = "repeat(auto-fill, minmax(120px, 1fr))";
      gallery.style.gap = "8px";
      sources.forEach((source) => {
        gallery.appendChild(renderImage(source));
      });
      element.appendChild(gallery);
      return element;
    }

    element.appendChild(renderImage(sources[0]));
    return element;
  };

  const linkRenderer: TFormOutputRenderer = ({ value }) => {
    const element = document.createElement("div");
    const normalized = normalizeViewValue(value);
    const href = Array.isArray(normalized) ? normalized[0] : normalized;
    if (typeof href !== "string" || !href) {
      return element;
    }

    const link = document.createElement("a");
    link.href = href;
    link.target = "_blank";
    link.rel = "noreferrer";
    link.textContent = href;
    element.appendChild(link);
    return element;
  };

  const fileRenderer: TFormOutputRenderer = ({ value, mediaDisplayPolicy }) => {
    const element = document.createElement("div");
    const fileMetas = getFileMetas(value);
    if (!fileMetas.length) {
      return element;
    }

    const items = mediaDisplayPolicy === "gallery" ? fileMetas : [fileMetas[0]];
    items.forEach((fileMeta) => {
      if (!fileMeta.href) {
        const text = document.createElement("span");
        text.textContent = fileMeta.label;
        text.style.display = "block";
        element.appendChild(text);
        return;
      }

      const link = document.createElement("a");
      link.href = fileMeta.href;
      link.target = "_blank";
      link.rel = "noreferrer";
      link.download = "";
      link.textContent = fileMeta.label;
      link.style.display = "block";
      element.appendChild(link);
    });
    return element;
  };

  const documentRenderer: TFormOutputRenderer = ({ value, mediaDisplayPolicy }) => {
    const element = document.createElement("div");
    const sources = getMediaSources(value);
    if (!sources.length) {
      return element;
    }

    if (mediaDisplayPolicy === "link") {
      sources.forEach((source) => {
        const link = document.createElement("a");
        link.href = source;
        link.target = "_blank";
        link.rel = "noreferrer";
        link.textContent = source;
        link.style.display = "block";
        element.appendChild(link);
      });
      return element;
    }

    const renderEmbed = (source: string) => {
      if (!isEmbeddableDocumentSource(source)) {
        const link = document.createElement("a");
        link.href = source;
        link.target = "_blank";
        link.rel = "noreferrer";
        link.textContent = source;
        link.style.display = "block";
        return link;
      }

      const frame = document.createElement("iframe");
      frame.src = source;
      frame.loading = "lazy";
      frame.style.width = "100%";
      frame.style.border = "1px solid rgba(15, 23, 42, 0.12)";
      frame.style.borderRadius = "10px";
      frame.style.background = "#ffffff";
      frame.style.height =
        mediaDisplayPolicy === "thumbnail"
          ? "220px"
          : mediaDisplayPolicy === "large"
            ? "520px"
            : "420px";
      frame.setAttribute("title", "Document preview");
      return frame;
    };

    if (mediaDisplayPolicy === "gallery") {
      const gallery = document.createElement("div");
      gallery.setAttribute("data-media-gallery", "true");
      gallery.style.display = "grid";
      gallery.style.gap = "10px";
      sources.forEach((source) => {
        gallery.appendChild(renderEmbed(source));
      });
      element.appendChild(gallery);
      return element;
    }

    element.appendChild(renderEmbed(sources[0]));
    return element;
  };

  const videoRenderer: TFormOutputRenderer = ({ value, mediaDisplayPolicy }) => {
    const element = document.createElement("div");
    const sources = getMediaSources(value);
    if (!sources.length) {
      return element;
    }

    if (mediaDisplayPolicy === "link") {
      sources.forEach((source) => {
        const link = document.createElement("a");
        link.href = source;
        link.target = "_blank";
        link.rel = "noreferrer";
        link.textContent = source;
        link.style.display = "block";
        element.appendChild(link);
      });
      return element;
    }

    const renderVideo = (source: string) => {
      const video = document.createElement("video");
      video.controls = true;
      video.preload = "metadata";
      video.style.maxWidth = mediaDisplayPolicy === "thumbnail" ? "220px" : "100%";
      video.src = source;
      return video;
    };

    if (mediaDisplayPolicy === "gallery") {
      const list = document.createElement("div");
      list.setAttribute("data-media-gallery", "true");
      list.style.display = "grid";
      list.style.gap = "8px";
      sources.forEach((source) => {
        list.appendChild(renderVideo(source));
      });
      element.appendChild(list);
      return element;
    }

    element.appendChild(renderVideo(sources[0]));
    return element;
  };

  const audioRenderer: TFormOutputRenderer = ({ value, mediaDisplayPolicy }) => {
    const element = document.createElement("div");
    const sources = getMediaSources(value);
    if (!sources.length) {
      return element;
    }

    if (mediaDisplayPolicy === "link") {
      sources.forEach((source) => {
        const link = document.createElement("a");
        link.href = source;
        link.target = "_blank";
        link.rel = "noreferrer";
        link.textContent = source;
        link.style.display = "block";
        element.appendChild(link);
      });
      return element;
    }

    const renderAudio = (source: string) => {
      const audio = document.createElement("audio");
      audio.controls = true;
      audio.preload = "metadata";
      audio.src = source;
      audio.style.width = mediaDisplayPolicy === "thumbnail" ? "220px" : "100%";
      return audio;
    };

    if (mediaDisplayPolicy === "gallery") {
      const list = document.createElement("div");
      list.setAttribute("data-media-gallery", "true");
      list.style.display = "grid";
      list.style.gap = "8px";
      sources.forEach((source) => {
        list.appendChild(renderAudio(source));
      });
      element.appendChild(list);
      return element;
    }

    element.appendChild(renderAudio(sources[0]));
    return element;
  };

  const mapRenderer: TFormOutputRenderer = ({ value, mediaDisplayPolicy }) => {
    const element = document.createElement("div");
    const sources = getMapSources(value);
    if (!sources.length) {
      return element;
    }

    if (mediaDisplayPolicy === "link") {
      sources.forEach((source) => {
        const link = document.createElement("a");
        link.href = source;
        link.target = "_blank";
        link.rel = "noreferrer";
        link.textContent = source;
        link.style.display = "block";
        element.appendChild(link);
      });
      return element;
    }

    const embedSource = sources.find((source) => isSafeMapEmbedSource(source));
    if (!embedSource) {
      const fallback = document.createElement("a");
      fallback.href = sources[0];
      fallback.target = "_blank";
      fallback.rel = "noreferrer";
      fallback.textContent = sources[0];
      element.appendChild(fallback);
      return element;
    }

    const frame = document.createElement("iframe");
    frame.src = embedSource;
    frame.loading = "lazy";
    frame.referrerPolicy = "no-referrer-when-downgrade";
    frame.style.width = "100%";
    frame.style.height = mediaDisplayPolicy === "thumbnail" ? "200px" : "320px";
    frame.style.border = "0";
    frame.setAttribute("title", "Map preview");
    frame.setAttribute("allowfullscreen", "");
    element.appendChild(frame);
    return element;
  };

  return {
    text: textRenderer,
    html: htmlRenderer,
    image: imageRenderer,
    file: fileRenderer,
    video: videoRenderer,
    audio: audioRenderer,
    map: mapRenderer,
    link: linkRenderer,
    document: documentRenderer,
  };
}

export function resolveOutputRendererForField(options: {
  fieldConfig: TFieldConfig;
  inputElement: HTMLElement;
  outputRenderers: Record<string, TFormOutputRenderer>;
  fieldOutputRenderers: Record<string, TFieldOutputRendererOverride>;
  getOutputRendererType: (fieldConfig: TFieldConfig) => TOutputRendererType;
}): { rendererType: string; renderer: TFormOutputRenderer } {
  const fieldOverride = options.fieldOutputRenderers[options.fieldConfig.name];
  if (typeof fieldOverride === "function") {
    return {
      rendererType: "custom",
      renderer: fieldOverride,
    };
  }

  if (typeof fieldOverride === "string" && options.outputRenderers[fieldOverride]) {
    return {
      rendererType: fieldOverride,
      renderer: options.outputRenderers[fieldOverride],
    };
  }

  const rendererOverride = options.inputElement.getAttribute("data-view-renderer");
  if (rendererOverride && options.outputRenderers[rendererOverride]) {
    return {
      rendererType: rendererOverride,
      renderer: options.outputRenderers[rendererOverride],
    };
  }

  const defaultRendererType = options.getOutputRendererType(options.fieldConfig);
  return {
    rendererType: defaultRendererType,
    renderer: options.outputRenderers[defaultRendererType] || options.outputRenderers.text,
  };
}
