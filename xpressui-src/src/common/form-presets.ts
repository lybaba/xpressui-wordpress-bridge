import TFieldConfig, { TStepTransition } from "./TFieldConfig";
import TFormConfig, { FORM_MULTI_STEP_MODE, TFormProviderRequest, TFormRule, TFormStorageConfig, TFormSubmitRequest } from "./TFormConfig";
import { CAMERA_PHOTO_TYPE, DOCUMENT_SCAN_TYPE, EMAIL_TYPE, IMAGE_GALLERY_TYPE, PRODUCT_LIST_TYPE, QR_SCAN_TYPE, QUIZ_TYPE, SELECT_MULTIPLE_TYPE, SELECT_ONE_TYPE, SELECT_PRODUCT_TYPE, SETTING_TYPE, TEL_TYPE, TEXTAREA_TYPE, TEXT_TYPE, normalizeFieldName } from "./field";
import { createFormConfig, TSimpleFieldInput, TSimpleFormInput } from "./form-config-factory";
import { validatePublicFormConfig } from "./public-schema";

type TFieldOverrides = Partial<TFieldConfig>;

export type TFormPresetName =
  | "contact"
  | "booking-request"
  | "booking-wizard"
  | "payment-request"
  | "identity-check"
  | "identity-onboarding"
  | "ecommerce-checkout";

export type TCreateFormPresetOptions = {
  name?: string;
  title?: string;
  submit?: TFormSubmitRequest;
  provider?: TFormProviderRequest;
  storage?: TFormStorageConfig;
  rules?: TFormRule[];
  fields?: TSimpleFieldInput[];
  sectionName?: string;
  sectionLabel?: string;
};

function buildField(
  type: string,
  name: string,
  label: string,
  overrides?: TFieldOverrides,
): TSimpleFieldInput {
  return {
    type,
    name: normalizeFieldName(name),
    label,
    ...(overrides || {}),
  };
}

export const fieldFactory = {
  text(name: string, label: string, overrides?: TFieldOverrides): TSimpleFieldInput {
    return buildField(TEXT_TYPE, name, label, overrides);
  },

  email(name: string, label: string, overrides?: TFieldOverrides): TSimpleFieldInput {
    return buildField(EMAIL_TYPE, name, label, overrides);
  },

  phone(name: string, label: string, overrides?: TFieldOverrides): TSimpleFieldInput {
    return buildField(TEL_TYPE, name, label, overrides);
  },

  textarea(name: string, label: string, overrides?: TFieldOverrides): TSimpleFieldInput {
    return buildField(TEXTAREA_TYPE, name, label, overrides);
  },

  selectOne(
    name: string,
    label: string,
    choices: NonNullable<TFieldConfig["choices"]>,
    overrides?: TFieldOverrides,
  ): TSimpleFieldInput {
    return buildField(SELECT_ONE_TYPE, name, label, {
      choices,
      ...(overrides || {}),
    });
  },

  selectMultiple(
    name: string,
    label: string,
    choices: NonNullable<TFieldConfig["choices"]>,
    overrides?: TFieldOverrides,
  ): TSimpleFieldInput {
    return buildField(SELECT_MULTIPLE_TYPE, name, label, {
      choices,
      ...(overrides || {}),
    });
  },

  file(name: string, label: string, overrides?: TFieldOverrides): TSimpleFieldInput {
    return buildField("file", name, label, overrides);
  },

  cameraPhoto(name: string, label: string, overrides?: TFieldOverrides): TSimpleFieldInput {
    return buildField(CAMERA_PHOTO_TYPE, name, label, overrides);
  },

  documentScan(name: string, label: string, overrides?: TFieldOverrides): TSimpleFieldInput {
    return buildField(DOCUMENT_SCAN_TYPE, name, label, overrides);
  },

  qrScan(name: string, label: string, overrides?: TFieldOverrides): TSimpleFieldInput {
    return buildField(QR_SCAN_TYPE, name, label, overrides);
  },

  productList(
    name: string,
    label: string,
    choices: NonNullable<TFieldConfig["choices"]>,
    overrides?: TFieldOverrides,
  ): TSimpleFieldInput {
    return buildField(PRODUCT_LIST_TYPE, name, label, {
      choices,
      ...(overrides || {}),
    });
  },

  imageGallery(
    name: string,
    label: string,
    choices: NonNullable<TFieldConfig["choices"]>,
    overrides?: TFieldOverrides,
  ): TSimpleFieldInput {
    return buildField(IMAGE_GALLERY_TYPE, name, label, {
      choices,
      ...(overrides || {}),
    });
  },

  selectProduct(
    name: string,
    label: string,
    choices: NonNullable<TFieldConfig["choices"]>,
    overrides?: TFieldOverrides,
  ): TSimpleFieldInput {
    return buildField(SELECT_PRODUCT_TYPE, name, label, {
      choices,
      ...(overrides || {}),
    });
  },

  quiz(
    name: string,
    label: string,
    choices?: NonNullable<TFieldConfig["choices"]>,
    overrides?: TFieldOverrides,
  ): TSimpleFieldInput {
    return buildField(QUIZ_TYPE, name, label, {
      ...(choices ? { choices } : {}),
      ...(overrides || {}),
    });
  },

  setting(
    name: string,
    label: string,
    value: any,
    overrides?: TFieldOverrides,
  ): TSimpleFieldInput {
    return buildField(SETTING_TYPE, name, label, {
      value,
      ...(overrides || {}),
    });
  },

  settingPublic(
    name: string,
    label: string,
    value: any,
    overrides?: TFieldOverrides,
  ): TSimpleFieldInput {
    return buildField(SETTING_TYPE, name, label, {
      value,
      includeInSubmit: true,
      ...(overrides || {}),
    });
  },

  settingSensitive(
    name: string,
    label: string,
    value: any,
    overrides?: TFieldOverrides,
  ): TSimpleFieldInput {
    return buildField(SETTING_TYPE, name, label, {
      value,
      includeInSubmit: false,
      ...(overrides || {}),
    });
  },
};

export const stepFactory = {
  section(
    name: string,
    label: string,
    overrides?: Partial<TFieldConfig>,
  ): TFieldOverrides & Pick<TFieldConfig, "type" | "name" | "label"> {
    return {
      type: "section",
      name,
      label,
      ...(overrides || {}),
    };
  },

  transition(
    whenField: string,
    target: string,
    options: Partial<Omit<TStepTransition, "whenField" | "target">> = {},
  ): TStepTransition {
    return {
      whenField,
      target,
      ...(options || {}),
    };
  },
};

function getBasePresetInput(preset: TFormPresetName): TSimpleFormInput | TFormConfig {
  switch (preset) {
    case "booking-request":
      return {
        name: "booking-request-form",
        title: "Booking Request",
        fields: [
          fieldFactory.text("first_name", "First Name", { required: true }),
          fieldFactory.text("last_name", "Last Name", { required: true }),
          fieldFactory.email("email", "Email", { required: true }),
          buildField("date", "booking_date", "Booking Date", { required: true }),
          fieldFactory.selectOne(
            "service",
            "Service",
            [
              { value: "consultation", label: "Consultation" },
              { value: "onsite", label: "On-site Visit" },
              { value: "follow_up", label: "Follow-up" },
            ],
            { required: true },
          ),
          fieldFactory.textarea("notes", "Notes"),
        ],
      };

    case "payment-request":
      return {
        name: "payment-request-form",
        title: "Payment Request",
        fields: [
          fieldFactory.email("email", "Email", { required: true }),
          buildField("number", "amount", "Amount", {
            required: true,
            min: 0,
            step: 0.01,
          }),
          fieldFactory.selectOne(
            "currency",
            "Currency",
            [
              { value: "EUR", label: "EUR" },
              { value: "USD", label: "USD" },
              { value: "GBP", label: "GBP" },
            ],
            { required: true },
          ),
          fieldFactory.text("reference", "Reference"),
        ],
      };

    case "booking-wizard":
      return validatePublicFormConfig({
        version: 1,
        id: 'booking_wizard_form',
        uid: 'booking_wizard_form_uid',
        name: "booking-wizard-form",
        title: "Booking Wizard",
        type: "multistepform",
        mode: FORM_MULTI_STEP_MODE,
        navigationLabels: {
          prevLabel: "Previous",
          nextLabel: "Continue",
        },
        stepSections: [
          stepFactory.section('details_step', 'Details'),
          stepFactory.section('review_step', 'Review', {
            stepSummary: true,
          }),
          stepFactory.section('scheduling_step', 'Scheduling'),
          stepFactory.section('confirmation_step', 'Confirmation', { stepSummary: true }),
        ],
        sections: {
          custom: [
            stepFactory.section('details_step', 'Details'),
            stepFactory.section('review_step', 'Review', {
              stepSummary: true,
            }),
            stepFactory.section('scheduling_step', 'Scheduling'),
            stepFactory.section('confirmation_step', 'Confirmation', { stepSummary: true }),
          ],
          details_step: [
            fieldFactory.text("first_name", "First Name", { required: true }),
            fieldFactory.text("last_name", "Last Name", { required: true }),
            fieldFactory.email("email", "Email", { required: true }),
            fieldFactory.selectOne(
              "service",
              "Service",
              [
                { value: "consultation", label: "Consultation" },
                { value: "onsite", label: "On-site Visit" },
                { value: "follow_up", label: "Follow-up" },
              ],
              { required: true },
            ),
          ],
          review_step: [
            fieldFactory.textarea("notes", "Notes"),
          ],
          scheduling_step: [
            buildField("date", "booking_date", "Booking Date", { required: true }),
            buildField("time", "booking_time", "Booking Time", { required: true }),
          ],
          confirmation_step: [
            buildField("approval-state", "approval_state", "Status"),
          ],
        },
        workflowStepTargets: {
          pending_approval: "confirmation_step",
          approved: "confirmation_step",
        },
      } as any);

    case "identity-check":
      return {
        name: "identity-check-form",
        title: "Identity Check",
        provider: {
          type: "identity-verification",
          endpoint: "/api/identity/verify",
        },
        submit: {
          endpoint: "/api/identity/verify",
          includeDocumentData: true,
          documentDataMode: "summary",
          documentFieldPaths: [
            "mrz.documentNumber",
            "mrz.nationality",
            "mrz.birthDate",
            "mrz.expiryDate",
            "mrz.valid",
            "fields.firstName",
            "fields.lastName",
          ],
        },
        fields: [
          fieldFactory.email("email", "Email", { required: true }),
          fieldFactory.documentScan("passport", "Passport", {
            required: true,
            enableDocumentOcr: true,
            requireValidDocumentMrz: true,
            documentFirstNameTargetField: "first_name",
            documentLastNameTargetField: "last_name",
            documentNumberTargetField: "document_number",
            documentNationalityTargetField: "nationality",
            documentBirthDateTargetField: "birth_date",
            documentExpiryDateTargetField: "expiry_date",
          }),
          fieldFactory.text("first_name", "First Name", { required: true }),
          fieldFactory.text("last_name", "Last Name", { required: true }),
          fieldFactory.text("document_number", "Document Number", { required: true }),
          fieldFactory.text("nationality", "Nationality"),
          buildField("date", "birth_date", "Birth Date"),
          buildField("date", "expiry_date", "Expiry Date"),
        ],
      };

    case "identity-onboarding":
      return validatePublicFormConfig({
        version: 1,
        id: 'identity_onboarding_form',
        uid: 'identity_onboarding_form_uid',
        name: "identity-onboarding-form",
        title: "Identity Onboarding",
        type: "multistepform",
        mode: FORM_MULTI_STEP_MODE,
        stepSections: [
          { type: 'section', name: 'identity_step', label: 'Identity' },
          { type: 'section', name: 'document_step', label: 'Document' },
          { type: 'section', name: 'review_step', label: 'Review', stepSummary: true },
        ],
        sections: {
          custom: [
            { type: 'section', name: 'identity_step', label: 'Identity' },
            { type: 'section', name: 'document_step', label: 'Document' },
            { type: 'section', name: 'review_step', label: 'Review', stepSummary: true },
          ],
          identity_step: [
            fieldFactory.email("email", "Email", { required: true }),
            fieldFactory.text("first_name", "First Name", { required: true }),
            fieldFactory.text("last_name", "Last Name", { required: true }),
          ],
          document_step: [
            fieldFactory.documentScan("passport", "Passport", {
              required: true,
              enableDocumentOcr: true,
              requireValidDocumentMrz: true,
            }),
          ],
          review_step: [
            buildField("approval-state", "approval_state", "Status"),
          ],
        },
      } as any);

    case "ecommerce-checkout":
      return {
        name: "ecommerce-checkout-form",
        title: "E-commerce Checkout",
        fields: [
          fieldFactory.settingPublic("checkout_currency_setting", "Currency Setting", "EUR"),
          fieldFactory.settingSensitive("checkout_shipping_setting", "Shipping Setting", 8.9),
          fieldFactory.settingSensitive("checkout_tax_rate_setting", "Tax Rate Setting", 0.2),
          fieldFactory.text("customer_name", "Customer Name", { required: true }),
          fieldFactory.email("customer_email", "Email", { required: true }),
          fieldFactory.productList(
            "products",
            "Products",
            [
              {
                value: "sku_camera",
                name: "Nomad Camera Bag",
                label: "Nomad Camera Bag",
                sale_price: 129,
                discount_price: 99,
                image_thumbnail: "",
                image_medium: "",
                photos_full: [],
              },
              {
                value: "sku_headset",
                name: "Studio Headset",
                label: "Studio Headset",
                sale_price: 189,
                discount_price: 159,
                image_thumbnail: "",
                image_medium: "",
                photos_full: [],
              },
            ],
            { required: true },
          ),
          fieldFactory.text("coupon", "Coupon"),
          fieldFactory.textarea("shipping_notes", "Shipping Notes"),
        ],
      };

    case "contact":
    default:
      return {
        name: "contact-form",
        title: "Contact Us",
        fields: [
          fieldFactory.text("full_name", "Full Name", { required: true }),
          fieldFactory.email("email", "Email", { required: true }),
          fieldFactory.phone("phone", "Phone"),
          fieldFactory.textarea("message", "Message", { required: true }),
        ],
      };
  }
}

export function createFormPreset(
  preset: TFormPresetName,
  options: TCreateFormPresetOptions = {},
): TFormConfig {
  const base = getBasePresetInput(preset);

  if (!('fields' in base)) {
    return {
      ...base,
      name: options.name || base.name,
      title: options.title || base.title,
      provider: options.provider || base.provider,
      submit: options.submit || base.submit,
      storage: options.storage || base.storage,
      rules: options.rules || base.rules,
    };
  }

  return createFormConfig({
    ...base,
    ...options,
    name: options.name || base.name,
    title: options.title || base.title,
    provider: options.provider || base.provider,
    submit: options.submit || base.submit,
    storage: options.storage || base.storage,
    rules: options.rules || base.rules,
    sectionName: options.sectionName || base.sectionName,
    sectionLabel: options.sectionLabel || base.sectionLabel,
    fields: [...base.fields, ...(options.fields || [])],
  });
}
