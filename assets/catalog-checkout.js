/**
 * Headless catalog checkout — save the order in WordPress (default).
 *
 * The catalog checkout submission is stored in WordPress by default (the plugin's
 * own submission inbox), NOT sent to the SaaS. This mirrors the SaaS cart-prefill:
 * it injects the localStorage cart + chosen payment method into the checkout form as
 * hidden fields (xpressuiProductCart / Total / Currency / Count / xpressuiPaymentMethod)
 * so the form's normal submit to the WP REST endpoint carries the order. The cart is
 * cleared once the submission succeeds.
 *
 * (A future, opt-in paid "bypass" can POST the same order straight to the SaaS cloud
 * — the developer-token orders endpoint + REST proxy are already in place for that.)
 *
 * Config is injected via window.xpressuiCatalogCheckout (wp_localize_script); absent
 * → no-op.
 */
(function () {
  var cfg = window.xpressuiCatalogCheckout;
  if (!cfg || !cfg.storageKey) { return; }
  var form = document.querySelector('.xpressui-inline-embed form');
  if (!form) { return; }

  function setHidden(name, value) {
    var input = form.querySelector('input[type="hidden"][name="' + name + '"][data-xpui-cart]');
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.setAttribute('data-xpui-cart', '');
      form.appendChild(input);
    }
    input.value = value;
  }

  function readCart() {
    try {
      var raw = window.localStorage.getItem(cfg.storageKey);
      if (!raw) { return { items: [], total: 0, currency: '', count: 0 }; }
      var parsed = JSON.parse(raw);
      var cart = parsed && Array.isArray(parsed.cart) ? parsed.cart : [];
      var total = 0;
      var count = 0;
      var currency = '';
      cart.forEach(function (it) {
        if (!it || !it.id) { return; }
        var q = Math.max(1, parseInt(it.quantity, 10) || 1);
        var p = Number(it.price) || 0;
        total += p * q;
        count += q;
        if (it.currency) { currency = it.currency; }
      });
      return { items: cart, total: total, currency: currency, count: count };
    } catch (_err) {
      return { items: [], total: 0, currency: '', count: 0 };
    }
  }

  function selectedPaymentMethod() {
    var r = document.querySelector('input[name="xpui_payment_method"]:checked');
    return r ? r.value : '';
  }

  function syncHidden() {
    var c = readCart();
    setHidden('xpressuiProductCart', JSON.stringify(c.items));
    setHidden('xpressuiProductTotal', String(c.total));
    setHidden('xpressuiProductCurrency', c.currency);
    setHidden('xpressuiProductCount', String(c.count));
    setHidden('xpressuiPaymentMethod', selectedPaymentMethod());
  }

  syncHidden();
  document.addEventListener('change', function (e) {
    if (e.target && e.target.name === 'xpui_payment_method') { syncHidden(); }
  });
  // Re-sync right before submit in case the cart changed in another tab.
  form.addEventListener('submit', function () { syncHidden(); }, true);

  // Clear the cart once the submission succeeds (the runtime shell flips to submitted).
  try {
    var observer = new MutationObserver(function () {
      if (document.querySelector('[data-workflow-state="submitted"]')) {
        try { window.localStorage.removeItem(cfg.storageKey); } catch (_err) {}
        observer.disconnect();
      }
    });
    observer.observe(document.body, { attributes: true, subtree: true, attributeFilter: ['data-workflow-state'] });
  } catch (_err) {}
})();
