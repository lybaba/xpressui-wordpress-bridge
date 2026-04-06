import type TFieldConfig from "../common/TFieldConfig";
import type { TDocumentScanInsight } from "../common/document-contract";
import type {
  TFormProviderTransition,
  TNormalizedProviderResult,
} from "../common/provider-contract";
import type TFormConfig from "../common/TFormConfig";
import type { TFormSubmitRequest } from "../common/TFormConfig";

export type THydratedFormSubmitDetail = {
  values: Record<string, any>;
  formConfig: TFormConfig | null;
  submit?: TFormSubmitRequest;
  response?: Response;
  result?: any;
  providerResult?: TNormalizedProviderResult;
  error?: unknown;
};

export type THydratedFormApprovalState = {
  status: string;
  approvalId?: string;
  result?: any;
  providerResult?: TNormalizedProviderResult;
};

export type THydratedFormWorkflowState =
  | "draft"
  | "submitting"
  | "submitted"
  | "pending_approval"
  | "approved"
  | "completed"
  | "rejected"
  | "error";

export type TWorkflowRouteResult = {
  routed: boolean;
  stepIndex: number;
  stepName: string | null;
};

export type TProviderTransitionRouteResult = {
  routed: boolean;
  stepIndex: number;
  stepName: string | null;
};

export type TFormRenderMode =
  | "form"
  | "view"
  | "hybrid"
  | "form-multi-step"
  | "view-multi-step";

export type TMediaDisplayPolicy = "thumbnail" | "large" | "link" | "gallery" | "embed";

export type TFormOutputRendererContext = {
  fieldConfig: TFieldConfig;
  value: any;
  mode: TFormRenderMode;
  unsafeHtml: boolean;
  mediaDisplayPolicy: TMediaDisplayPolicy;
};

export type TFormOutputRenderer = (context: TFormOutputRendererContext) => HTMLElement;

export type TOutputRendererType =
  | "text"
  | "html"
  | "image"
  | "file"
  | "video"
  | "audio"
  | "map"
  | "link"
  | "document";

export type TFieldOutputRendererOverride = string | TFormOutputRenderer;

export type TFormHtmlSanitizer = (
  html: string,
  context: {
    fieldConfig: TFieldConfig;
    mode: TFormRenderMode;
  },
) => string;

export type TBarcodeDetectorResult = {
  rawValue?: string;
};

export type TQrScannerState = {
  status: "idle" | "starting" | "live" | "error";
  message?: string;
};

export type TDocumentPerspectiveCorners = {
  topLeft: { x: number; y: number };
  topRight: { x: number; y: number };
  bottomRight: { x: number; y: number };
  bottomLeft: { x: number; y: number };
};

export type TProductListItem = {
  id: string;
  name: string;
  sale_price: number | null;
  discount_price: number | null;
  image_thumbnail: string;
  image_medium: string;
  photos_full: string[];
  maxNumOfChoices?: number;
};

export type TProductCartItem = TProductListItem & {
  quantity: number;
};

export type TImageGalleryItem = {
  id: string;
  name: string;
  sale_price?: number | null;
  discount_price?: number | null;
  image_thumbnail: string;
  image_medium: string;
  photos_full: string[];
  maxNumOfChoices?: number;
};

export type TQuizAnswerItem = {
  id: string;
  name: string;
  desc: string;
  image_thumbnail: string;
  image_medium: string;
  photos_full: string[];
  maxNumOfChoices?: number;
};

export type THydratedFormDocumentState = Record<string, TDocumentScanInsight>;

export type THydratedFormProviderTransition = TFormProviderTransition;

export type TFormApprovalState = THydratedFormApprovalState;

export type TFormWorkflowState = THydratedFormWorkflowState;
