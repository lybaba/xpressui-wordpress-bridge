type TPaymentRuntimeHost = any;

export type TPaymentConfirmResult = { ok: true } | { ok: false; error: string };

let stripeInstance: any = null;

function getStripe(publishableKey: string): any | null {
  if (stripeInstance) return stripeInstance;
  const StripeConstructor = (window as any).Stripe;
  if (!StripeConstructor) return null;
  stripeInstance = StripeConstructor(publishableKey);
  return stripeInstance;
}

function readStripeStyle(wrap: HTMLElement, mountNode: HTMLElement): object {
  const cs = getComputedStyle(wrap);
  const cssVar = (name: string) => cs.getPropertyValue(name).trim();
  // font-size: read computed value from the mount node itself
  const fontSize = getComputedStyle(mountNode).fontSize || "16px";
  // colors: read from template CSS custom properties, fall back to safe defaults
  const color = cssVar("--template-text") || "#0f172a";
  const fontFamily = cssVar("--template-font-family") || "system-ui, sans-serif";
  // placeholder and invalid: read from data attributes set by the template, with defaults
  const placeholderColor = wrap.dataset.stripePlaceholderColor || "#94a3b8";
  const invalidColor = wrap.dataset.stripeInvalidColor || "#ef4444";
  return {
    base: { fontSize, color, fontFamily, "::placeholder": { color: placeholderColor } },
    invalid: { color: invalidColor },
  };
}

export function createPaymentRuntime(host: TPaymentRuntimeHost) {
  const cardElements = new Map<string, { stripe: any; cardElement: any }>();

  return {
    initField(fieldConfig: { name: string }) {
      const fn = fieldConfig.name;
      const wrap = host.querySelector(
        `[data-stripe-payment-field="${fn}"]`,
      ) as HTMLElement | null;
      if (!wrap || wrap.dataset.stripeReady) return;
      wrap.dataset.stripeReady = "1";

      const publishableKey = (window as any)
        .XPRESSUI_STRIPE_PUBLISHABLE_KEY as string | undefined;
      if (!publishableKey) return;

      const stripe = getStripe(publishableKey);
      if (!stripe) return;

      const mountNode = wrap.querySelector(
        `[data-stripe-card-element="${fn}"]`,
      ) as HTMLElement | null;
      if (!mountNode) return;

      const cardElement = stripe.elements().create("card", {
        style: readStripeStyle(wrap, mountNode),
      });
      cardElement.mount(mountNode);
      cardElements.set(fn, { stripe, cardElement });

      cardElement.addEventListener("change", (event: any) => {
        const errorEl = wrap.querySelector(
          `[data-stripe-error="${fn}"]`,
        ) as HTMLElement | null;
        if (errorEl) {
          errorEl.textContent = event.error ? event.error.message : "";
        }
      });
    },

    async confirmPaymentsIfNeeded(): Promise<TPaymentConfirmResult> {
      const paymentFields = Array.from(
        host.querySelectorAll(
          "[data-stripe-payment-field]",
        ) as NodeListOf<HTMLElement>,
      );
      if (!paymentFields.length) return { ok: true };

      for (const wrap of paymentFields) {
        if (wrap.dataset.paymentConfirmed) continue;

        const fn = wrap.dataset.stripePaymentField!;
        const input = host.querySelector(
          `input[data-name="${fn}"]`,
        ) as HTMLInputElement | null;

        if (input?.value) {
          wrap.dataset.paymentConfirmed = "1";
          continue;
        }

        const pair = cardElements.get(fn);
        if (!pair) {
          const hasKey = Boolean((window as any).XPRESSUI_STRIPE_PUBLISHABLE_KEY);
          return {
            ok: false,
            error: hasKey
              ? "Payment card element not ready. Please reload the page and try again."
              : "Stripe is not configured. Set XPRESSUI_STRIPE_PUBLISHABLE_KEY before using payment fields.",
          };
        }

        const { stripe, cardElement } = pair;
        const intentUrl = (window as any)
          .XPRESSUI_STRIPE_PAYMENT_INTENT_URL as string | undefined;
        if (!intentUrl) {
          return { ok: false, error: "Payment endpoint not configured." };
        }

        const amount = Number(wrap.dataset.stripeAmount || "0");
        const currency = wrap.dataset.stripeCurrency || "usd";
        const projectSlug = wrap.dataset.stripeProjectSlug || "";

        let clientSecret: string;
        try {
          const res = await fetch(intentUrl, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ amount, currency, projectSlug, fieldName: fn }),
          });
          const data = await res.json();
          if (!res.ok || !data.clientSecret) {
            return {
              ok: false,
              error: data.message || "Payment intent creation failed.",
            };
          }
          clientSecret = data.clientSecret;
        } catch {
          return { ok: false, error: "Network error during payment." };
        }

        const result = await stripe.confirmCardPayment(clientSecret, {
          payment_method: { card: cardElement },
        });

        if (result.error) {
          return {
            ok: false,
            error: result.error.message || "Payment failed.",
          };
        }

        const paymentIntentId = result.paymentIntent.id;
        if (input) {
          input.value = paymentIntentId;
          input.dispatchEvent(new Event("input", { bubbles: true }));
        }
        wrap.dataset.paymentConfirmed = "1";
      }

      return { ok: true };
    },
  };
}
