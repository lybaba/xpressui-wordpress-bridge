/**
 * Headless catalog checkout — record the order on the WP submission AND route payment
 * to the SaaS.
 *
 * The order-type submission still creates a LOCAL WP entry (so WordPress keeps a record):
 * the XPressUI runtime serializes only declared form-config fields, so we intercept the
 * submit `fetch` and merge the cart/booking into the payload it sends
 * (`xpressuiProductCart` / `xpressuiProductTotal` / `xpressuiProductCurrency` /
 * `xpressuiPaymentMethod`).
 *
 * PAYMENT + processing are owned by the SaaS. The WP submit handler pushes the order to
 * the SaaS synchronously; for a Stripe order the response carries a `checkoutUrl` and we
 * redirect the browser to Stripe, then back to the WP success URL. Manual methods
 * (Wave/OM) redirect straight to the success URL (status pending, instructions shown).
 *
 * Order source resolved at submit time:
 *   - product catalogs  → the localStorage cart (window.xpressuiCatalogCheckout.storageKey)
 *   - time-slots booking → the chosen slot carried in the checkout URL query params
 *
 * Config (window.xpressuiCatalogCheckout, set by includes/shortcode.php):
 *   slug, linkId, storageKey, returnUrl (storefront), successUrl, cancelUrl.
 */
(function () {
  var cfg = window.xpressuiCatalogCheckout || {};

  // On the post-payment return (?xpuiCheckout=success), clear the cart and stop — the
  // order/payment now live on the SaaS; this page is just a confirmation.
  try {
    if (new URLSearchParams(window.location.search).get('xpuiCheckout') === 'success') {
      if (cfg.storageKey) { try { window.localStorage.removeItem(cfg.storageKey); } catch (_e) {} }
      return;
    }
  } catch (_e) {}

  // Move the payment-method selector into the LAST step (next to Submit), mirroring the
  // SaaS hosted checkout. It is server-rendered into a hidden holder at the top of the
  // form; the runtime shows one .template-section (step) at a time, so appending it to
  // the last section makes it appear only on the final/submit step.
  function relocatePaymentMethods() {
    var holder = document.querySelector('[data-xpui-payment-holder]');
    if (!holder) { return; }
    var block = holder.querySelector('.xpui-checkout-payment');
    var form = document.querySelector('form.template-runtime-shell');
    if (!block || !form) { return; }
    var sections = form.querySelectorAll('.template-section');
    var target = sections.length ? sections[sections.length - 1] : form;
    target.appendChild(block);
    if (holder.parentNode) { holder.parentNode.removeChild(holder); }
  }

  function selectedPaymentMethodValue() {
    var r = document.querySelector('input[name="xpui_payment_method"]:checked');
    return r ? r.value : '';
  }

  // Manual methods (Wave/OM/bank) require a payment-proof upload; Stripe (card) does
  // not. Show/hide the proof field based on the chosen method — parity with the SaaS.
  function togglePaymentProof() {
    var proof = document.querySelector('[data-payment-proof]');
    if (!proof) { return; }
    var pm = selectedPaymentMethodValue();
    var show = !! pm && pm !== 'stripe';
    proof.hidden = ! show;
    proof.style.display = show ? '' : 'none';
  }

  function initCheckoutPaymentUi() {
    relocatePaymentMethods();
    togglePaymentProof();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCheckoutPaymentUi);
  } else {
    initCheckoutPaymentUi();
  }
  document.addEventListener('change', function ( e ) {
    if ( e.target && e.target.name === 'xpui_payment_method' ) { togglePaymentProof(); }
  });

  if (window.__xpuiCheckoutFetchWrapped) { return; }

  function readProductCart() {
    if (!cfg.storageKey) { return null; }
    try {
      var raw = window.localStorage.getItem(cfg.storageKey);
      if (!raw) { return null; }
      var parsed = JSON.parse(raw);
      var cart = parsed && Array.isArray(parsed.cart) ? parsed.cart : [];
      if (!cart.length) { return null; }
      var total = 0, count = 0, currency = '';
      cart.forEach(function (it) {
        if (!it || !it.id) { return; }
        var q = Math.max(1, parseInt(it.quantity, 10) || 1);
        total += (Number(it.price) || 0) * q;
        count += q;
        if (it.currency) { currency = it.currency; }
      });
      return { items: cart, total: total, currency: currency, count: count };
    } catch (_e) { return null; }
  }

  function readBookingFromUrl() {
    try {
      var p = new URLSearchParams(window.location.search);
      var id = p.get('timeSlotId');
      if (!id) { return null; }
      var price = Number(p.get('timeSlotPrice')) || 0;
      var currency = p.get('timeSlotCurrency') || '';
      return {
        items: [{
          id: id,
          label: p.get('timeSlotResource') || p.get('timeSlotLabel') || id,
          quantity: 1,
          price: price,
          currency: currency,
          startsAt: p.get('timeSlotStartsAt') || '',
          endsAt: p.get('timeSlotEndsAt') || ''
        }],
        total: price, currency: currency, count: 1
      };
    } catch (_e) { return null; }
  }

  function resolveOrder() { return readProductCart() || readBookingFromUrl(); }

  function selectedPaymentMethod() {
    var r = document.querySelector('input[name="xpui_payment_method"]:checked');
    return r ? r.value : '';
  }

  // Merge the order fields into the submission payload (so the WP entry records the order).
  // Written at the top level AND into `.values` (where the form fields + the SaaS
  // order-summary normaliser look).
  function injectOrder(target, order) {
    if (!target || typeof target !== 'object' || !order) { return; }
    var pm = selectedPaymentMethod();
    var dests = [target];
    if (target.values && typeof target.values === 'object') { dests.push(target.values); }
    dests.forEach(function (d) {
      d.xpressuiProductCart = JSON.stringify(order.items);
      d.xpressuiProductTotal = String(order.total == null ? '' : order.total);
      d.xpressuiProductCurrency = order.currency || '';
      d.xpressuiProductCount = String(order.count == null ? order.items.length : order.count);
      if (pm) { d.xpressuiPaymentMethod = pm; }
    });
  }

  function injectOrderFormData(fd, order) {
    fd.set('xpressuiProductCart', JSON.stringify(order.items));
    fd.set('xpressuiProductTotal', String(order.total == null ? '' : order.total));
    fd.set('xpressuiProductCurrency', order.currency || '');
    fd.set('xpressuiProductCount', String(order.count == null ? order.items.length : order.count));
    var pm = selectedPaymentMethod();
    if (pm) { fd.set('xpressuiPaymentMethod', pm); }
  }

  // Return/cancel URLs are sent at the TOP LEVEL of the request body (beside `payload`),
  // so the WP submit handler can validate (same-origin) and forward them to the SaaS for
  // the Stripe session — the buyer then returns to the WP site after payment.
  function applyOrderRouting(setter) {
    if (cfg.successUrl) { setter('returnUrl', cfg.successUrl); }
    if (cfg.cancelUrl) { setter('cancelUrl', cfg.cancelUrl); }
  }

  function isSubmitUrl(url) {
    return typeof url === 'string' && url.indexOf('xpressui/v1/submit') !== -1;
  }

  function successRedirectUrl() {
    if (cfg.successUrl) { return cfg.successUrl; }
    var base = window.location.protocol + '//' + window.location.host + window.location.pathname;
    return base + (base.indexOf('?') === -1 ? '?' : '&') + 'xpuiCheckout=success';
  }

  // After a Stripe order submit, the SaaS returns { checkoutUrl } → redirect to Stripe.
  // A manual order returns { paymentStatus: 'pending' } (no checkoutUrl) → success page,
  // carrying the chosen method so the page can show its payment instructions.
  function handleOrderResponse(resp) {
    try {
      resp.clone().json().then(function (data) {
        if (!data) { return; }
        if (data.checkoutUrl) { window.location.assign(data.checkoutUrl); return; }
        if (data.paymentStatus) {
          var url = successRedirectUrl();
          var pm = selectedPaymentMethod();
          if (pm && pm !== 'stripe') {
            url += (url.indexOf('?') === -1 ? '?' : '&') + 'xpuiMethod=' + encodeURIComponent(pm);
          }
          window.location.assign(url);
        }
      }).catch(function () {});
    } catch (_e) {}
    return resp;
  }

  var origFetch = window.fetch;
  if (typeof origFetch === 'function') {
    window.__xpuiCheckoutFetchWrapped = true;
    window.fetch = function (input, init) {
      var isOrderSubmit = false;
      try {
        var url = (typeof input === 'string') ? input : (input && input.url) || '';
        var method = (init && init.method) || (input && input.method) || 'GET';
        if (String(method).toUpperCase() === 'POST' && isSubmitUrl(url) && init) {
          var order = resolveOrder();
          if (order && order.items && order.items.length) {
            isOrderSubmit = true;
            if (typeof init.body === 'string') {
              try {
                var b = JSON.parse(init.body);
                var t = (b && typeof b.payload === 'object' && b.payload) ? b.payload : b;
                injectOrder(t, order);
                applyOrderRouting(function (k, v) { b[k] = v; });
                init.body = JSON.stringify(b);
              } catch (_e) {}
            } else if (typeof FormData !== 'undefined' && init.body instanceof FormData) {
              var pf = init.body.get('payload');
              if (typeof pf === 'string') {
                try { var pj = JSON.parse(pf); injectOrder(pj, order); init.body.set('payload', JSON.stringify(pj)); } catch (_e) {}
              } else {
                injectOrderFormData(init.body, order);
              }
              applyOrderRouting(function (k, v) { init.body.set(k, v); });
              // Manual payment: attach the uploaded proof to the multipart submission
              // (stored in WP; the SaaS order gets the file reference). Stripe needs none.
              var pmSel = selectedPaymentMethodValue();
              if (pmSel && pmSel !== 'stripe') {
                var proofInput = document.querySelector('[data-payment-proof-input]');
                if (proofInput && proofInput.files && proofInput.files[0]) {
                  init.body.set('payment_proof', proofInput.files[0]);
                }
              }
            }
          }
        }
      } catch (_e) {}
      var result = origFetch.apply(this, arguments);
      if (isOrderSubmit && result && typeof result.then === 'function') {
        return result.then(handleOrderResponse);
      }
      return result;
    };
  }

  // Redirect to catalog storefront / services page if the checkout cart/booking is empty.
  try {
    var current = resolveOrder();
    if (!current || !current.items || !current.items.length) {
      var redirectUrl = cfg.returnUrl || (window.location.protocol + '//' + window.location.host + window.location.pathname);
      window.location.replace(redirectUrl);
    }
  } catch (_e) {}
})();
