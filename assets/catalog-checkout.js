/**
 * Headless catalog checkout — persist the order/booking in the WordPress submission.
 *
 * The XPressUI runtime serializes the submit payload from the *declared form-config
 * fields only* (its internal `values` state) — DOM-injected hidden <input>s never reach
 * the server. So to carry the cart/booking we intercept the submit `fetch` and merge the
 * order into the payload the runtime sends. This mirrors the SaaS, which stores the order
 * summary on the submission; here the WP submission inbox + Orders page read it back from
 * `payload.xpressuiProductCart` / `xpressuiProductTotal` / `xpressuiProductCurrency`.
 *
 * Order source resolved at submit time:
 *   - product catalogs  → the localStorage cart (window.xpressuiCatalogCheckout.storageKey)
 *   - time-slots booking → the chosen slot carried in the checkout URL query params
 *
 * (A future, opt-in paid "bypass" can POST the same order to the SaaS orders endpoint —
 * the developer-token endpoint + REST proxy are already in place for that.)
 */
(function () {
  if (window.__xpuiCheckoutFetchWrapped) { return; }
  var cfg = window.xpressuiCatalogCheckout || {};

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

  // Merge the order fields into the submission payload object. Written both at the top
  // level (WP Orders page fallback) and into `.values` when present (where the form
  // fields live + where the SaaS order-summary normaliser looks).
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

  function isSubmitUrl(url) {
    return typeof url === 'string' && url.indexOf('xpressui/v1/submit') !== -1;
  }

  var origFetch = window.fetch;
  if (typeof origFetch === 'function') {
    window.__xpuiCheckoutFetchWrapped = true;
    window.fetch = function (input, init) {
      try {
        var url = (typeof input === 'string') ? input : (input && input.url) || '';
        var method = (init && init.method) || (input && input.method) || 'GET';
        if (String(method).toUpperCase() === 'POST' && isSubmitUrl(url) && init) {
          var order = resolveOrder();
          if (order && order.items && order.items.length) {
            if (typeof init.body === 'string') {
              try {
                var b = JSON.parse(init.body);
                var t = (b && typeof b.payload === 'object' && b.payload) ? b.payload : b;
                injectOrder(t, order);
                init.body = JSON.stringify(b);
              } catch (_e) {}
            } else if (typeof FormData !== 'undefined' && init.body instanceof FormData) {
              var pf = init.body.get('payload');
              if (typeof pf === 'string') {
                try { var pj = JSON.parse(pf); injectOrder(pj, order); init.body.set('payload', JSON.stringify(pj)); } catch (_e) {}
              } else {
                injectOrderFormData(init.body, order);
              }
            }
          }
        }
      } catch (_e) {}
      return origFetch.apply(this, arguments);
    };
  }

  // Clear the product cart once the submission succeeds (runtime flips to submitted).
  if (cfg.storageKey) {
    try {
      var observer = new MutationObserver(function () {
        if (document.querySelector('[data-workflow-state="submitted"]')) {
          try { window.localStorage.removeItem(cfg.storageKey); } catch (_e) {}
          observer.disconnect();
        }
      });
      observer.observe(document.body, { attributes: true, subtree: true, attributeFilter: ['data-workflow-state'] });
    } catch (_e) {}
  }
})();
