/**
 * Headless catalog checkout: route the checkout-form submit to the SaaS order.
 *
 * On the catalog checkout page (?xpui_checkout=1) the hosted-link form collects the
 * customer info. This intercepts its submit (capture phase, before the runtime's own
 * submit handler — plugin-shell-init binds form 'submit' in bubble phase), gathers the
 * form values + the localStorage cart + the chosen payment method, and POSTs them to
 * the plugin's REST proxy, which calls the SaaS orders endpoint. The SaaS re-prices,
 * creates the submission, and returns a Stripe checkout URL (card → redirect) or a
 * created status (manual → clear cart + success). Config is injected via
 * window.xpressuiCatalogCheckout (wp_localize_script); absent → this is a no-op.
 */
(function () {
  var cfg = window.xpressuiCatalogCheckout;
  if (!cfg || !cfg.restUrl || !cfg.storageKey) { return; }
  var form = document.querySelector('.xpressui-inline-embed form');
  if (!form) { return; }

  function readCartItems() {
    try {
      var raw = window.localStorage.getItem(cfg.storageKey);
      if (!raw) { return []; }
      var parsed = JSON.parse(raw);
      var cart = parsed && Array.isArray(parsed.cart) ? parsed.cart : [];
      return cart
        .map(function (it) {
          return { id: String((it && it.id) || ''), quantity: Math.max(1, parseInt(it && it.quantity, 10) || 1) };
        })
        .filter(function (it) { return it.id; });
    } catch (_err) { return []; }
  }

  function selectedPaymentMethod() {
    var r = document.querySelector('input[name="xpui_payment_method"]:checked');
    return r ? r.value : '';
  }

  function collectFormValues() {
    var values = {};
    try {
      new FormData(form).forEach(function (value, key) {
        if (key === 'xpui_payment_method') { return; }
        if (value instanceof File) { return; } // files are not part of a catalog order
        values[key] = value;
      });
    } catch (_err) {}
    return values;
  }

  function goSuccess() {
    try { window.localStorage.removeItem(cfg.storageKey); } catch (_err) {}
    var sep = cfg.returnUrl && cfg.returnUrl.indexOf('?') === -1 ? '?' : '&';
    window.location.href = (cfg.returnUrl || '/') + sep + 'xpuiCheckout=success';
  }

  form.addEventListener(
    'submit',
    function (event) {
      var items = readCartItems();
      if (!items.length) { return; } // empty cart → let the form behave normally
      event.preventDefault();
      event.stopImmediatePropagation();

      var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
      if (submitBtn) { submitBtn.disabled = true; }

      fetch(cfg.restUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '' },
        body: JSON.stringify({
          projectSlug: cfg.slug,
          linkId: cfg.linkId,
          formValues: collectFormValues(),
          items: items,
          paymentMethod: selectedPaymentMethod(),
        }),
      })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d || {} }; }); })
        .then(function (res) {
          if (!res.ok) {
            throw new Error(res.data.message || res.data.detail || 'Order failed');
          }
          if (res.data.checkoutUrl) {
            window.location.href = res.data.checkoutUrl; // Stripe Checkout
            return;
          }
          goSuccess(); // manual / no payment
        })
        .catch(function (err) {
          if (submitBtn) { submitBtn.disabled = false; }
          window.alert(err && err.message ? err.message : 'Order failed');
        });
    },
    true // capture phase — run before the runtime's bubble-phase submit handler
  );
})();
