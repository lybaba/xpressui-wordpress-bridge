import { registerProvider } from "./provider-registry";

registerProvider("reservation", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "reservation" }; },
  buildPayload(values) { return { action: "reservation", reservation: values }; },
  successEventName: "xpressui:reservation-success",
});

registerProvider("payment", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "payment" }; },
  buildPayload(values) { return { action: "payment", payment: values }; },
  successEventName: "xpressui:payment-success",
  errorEventName: "xpressui:payment-error",
});

registerProvider("payment-stripe", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "payment-stripe" }; },
  buildPayload(values) { return { action: "payment-stripe", payment: values }; },
  successEventName: "xpressui:payment-stripe-success",
  errorEventName: "xpressui:payment-stripe-error",
});

registerProvider("webhook", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "webhook" }; },
  buildPayload(values) { return { action: "webhook", data: values }; },
  successEventName: "xpressui:webhook-success",
  errorEventName: "xpressui:webhook-error",
});

registerProvider("booking-availability", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "booking-availability" }; },
  buildPayload(values) { return { action: "booking-availability", availability: values }; },
  successEventName: "xpressui:booking-availability-success",
  errorEventName: "xpressui:booking-availability-error",
});

registerProvider("calendar-booking", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "calendar-booking" }; },
  buildPayload(values) { return { action: "calendar-booking", booking: values }; },
  successEventName: "xpressui:calendar-booking-success",
  errorEventName: "xpressui:calendar-booking-error",
});

registerProvider("calendar-cancel", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "calendar-cancel" }; },
  buildPayload(values) { return { action: "calendar-cancel", cancellation: values }; },
  successEventName: "xpressui:calendar-cancel-success",
  errorEventName: "xpressui:calendar-cancel-error",
});

registerProvider("calendar-reschedule", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "calendar-reschedule" }; },
  buildPayload(values) { return { action: "calendar-reschedule", reschedule: values }; },
  successEventName: "xpressui:calendar-reschedule-success",
  errorEventName: "xpressui:calendar-reschedule-error",
});

registerProvider("approval-request", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "approval-request" }; },
  buildPayload(values) { return { action: "approval-request", approval: values }; },
  resolveTransition(result) {
    if (!result || typeof result !== "object") return null;
    const status = typeof result.status === "string" ? result.status : "";
    if (status === "pending_approval" || status === "approved" || status === "completed" || status === "rejected") {
      return { type: "workflow", state: status };
    }
    return null;
  },
  successEventName: "xpressui:approval-request-success",
  errorEventName: "xpressui:approval-request-error",
});

registerProvider("approval-decision", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "approval-decision" }; },
  buildPayload(values) { return { action: "approval-decision", decision: values }; },
  resolveTransition(result) {
    if (!result || typeof result !== "object") return null;
    const status = typeof result.status === "string" ? result.status : "";
    if (status === "approved" || status === "completed" || status === "rejected") return { type: "workflow", state: status };
    return null;
  },
  successEventName: "xpressui:approval-decision-success",
  errorEventName: "xpressui:approval-decision-error",
});

registerProvider("approval-comment", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "approval-comment" }; },
  buildPayload(values) { return { action: "approval-comment", comment: values }; },
  successEventName: "xpressui:approval-comment-success",
  errorEventName: "xpressui:approval-comment-error",
});

registerProvider("email", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "email" }; },
  buildPayload(values) { return { action: "email", email: values }; },
  successEventName: "xpressui:email-success",
  errorEventName: "xpressui:email-error",
});

registerProvider("crm", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "crm" }; },
  buildPayload(values) { return { action: "crm", contact: values }; },
  successEventName: "xpressui:crm-success",
  errorEventName: "xpressui:crm-error",
});

registerProvider("identity-verification", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "identity-verification" }; },
  buildPayload(values) { return { action: "identity-verification", identity: values }; },
  successEventName: "xpressui:identity-verification-success",
  errorEventName: "xpressui:identity-verification-error",
});

registerProvider("identity-verification-stripe", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "identity-verification-stripe" }; },
  buildPayload(values) { return { action: "identity-verification-stripe", identity: values }; },
  successEventName: "xpressui:identity-verification-stripe-success",
  errorEventName: "xpressui:identity-verification-stripe-error",
});

registerProvider("identity-verification-webhook", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "identity-verification-webhook" }; },
  buildPayload(values) { return { action: "identity-verification-webhook", identity: values }; },
  successEventName: "xpressui:identity-verification-webhook-success",
  errorEventName: "xpressui:identity-verification-webhook-error",
});

registerProvider("calendar-availability-hold", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "calendar-availability-hold" }; },
  buildPayload(values) { return { action: "calendar-availability-hold", hold: values }; },
  resolveTransition(result) {
    if (!result || typeof result !== "object") return null;
    const status = typeof result.status === "string" ? result.status : typeof result.holdStatus === "string" ? result.holdStatus : "";
    if (status === "hold_pending" || status === "hold_confirmed" || status === "hold_expired") return { type: "workflow", state: status };
    return null;
  },
  successEventName: "xpressui:calendar-availability-hold-success",
  errorEventName: "xpressui:calendar-availability-hold-error",
});

registerProvider("payment-capture", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "payment-capture" }; },
  buildPayload(values) { return { action: "payment-capture", capture: values }; },
  resolveTransition(result) {
    if (!result || typeof result !== "object") return null;
    const status = typeof result.status === "string" ? result.status : typeof result.captureStatus === "string" ? result.captureStatus : "";
    if (status === "captured" || status === "succeeded" || status === "completed") return { type: "workflow", state: "completed" };
    if (status === "failed" || status === "declined" || status === "rejected") return { type: "workflow", state: "error" };
    return null;
  },
  successEventName: "xpressui:payment-capture-success",
  errorEventName: "xpressui:payment-capture-error",
});

registerProvider("identity-review", {
  createSubmitRequest(provider) { return { endpoint: provider.endpoint, method: provider.method || "POST", headers: provider.headers, action: "identity-review" }; },
  buildPayload(values) { return { action: "identity-review", review: values }; },
  resolveTransition(result) {
    if (!result || typeof result !== "object") return null;
    const status = typeof result.status === "string" ? result.status : typeof result.reviewStatus === "string" ? result.reviewStatus : "";
    if (status === "pending_approval" || status === "approved" || status === "completed" || status === "rejected") return { type: "workflow", state: status };
    return null;
  },
  successEventName: "xpressui:identity-review-success",
  errorEventName: "xpressui:identity-review-error",
});
