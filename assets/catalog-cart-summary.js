/**
 * Headless WordPress cart-summary hydrator.
 *
 * Unlike the SaaS workspace-cart-summary.js (which only refreshes server-rendered
 * rows), this builds the order-summary rows entirely from the client-side cart in
 * localStorage — the cart catalog-init.js persists already carries full product
 * metadata (label, image, price, currency, quantity, lineTotal). Nothing is sent to
 * the SaaS: the cart lives on the WordPress origin, and checkout navigates to the WP
 * checkout page. Money formatting mirrors catalog-init.js / cart-summary-page.js
 * (fr-FR locale, narrow no-break space normalised).
 */
(function () {
  var root = document.querySelector('.xpressui-cart-summary .cs-page');
  if (!root) { return; }

  var storageKey = root.getAttribute('data-storage-key') || '';
  var catalogUrl = root.getAttribute('data-catalog-url') || '';
  var checkoutUrl = root.getAttribute('data-checkout-url') || '';
  var fallbackCurrency = root.getAttribute('data-currency') || '';
  // Read-only mode: the order summary shown inside the checkout form. No steppers /
  // remove / checkout — the form's submit is the checkout. Pure display.
  var readonly = root.getAttribute('data-readonly') === '1';
  var itemsEl = root.querySelector('[data-cart-items]');
  var totalEl = root.querySelector('[data-cart-total]');
  var footerEl = root.querySelector('[data-cart-footer]');
  var totalsEl = root.querySelector('[data-cart-totals]');
  var checkoutBtn = root.querySelector('[data-cart-checkout]');

  function parseNumber(value) {
    var n = Number(String(value == null ? '' : value).replace(/\s/g, '').replace(',', '.'));
    return Number.isFinite(n) ? n : 0;
  }

  function formatMoney(amount, currency) {
    var rounded = Number.isInteger(amount) ? amount : Number(amount.toFixed(2));
    var text = new Intl.NumberFormat('fr-FR').format(rounded).replace(/ /g, ' ');
    return (text + (currency ? ' ' + currency : '')).trim();
  }

  function detailUrl(id) {
    if (!catalogUrl) { return ''; }
    var sep = catalogUrl.indexOf('?') === -1 ? '?' : '&';
    return catalogUrl + sep + 'xpui_product=' + encodeURIComponent(id);
  }

  function readCart() {
    if (!storageKey) { return []; }
    try {
      var raw = window.localStorage.getItem(storageKey);
      if (!raw) { return []; }
      var parsed = JSON.parse(raw);
      var cart = parsed && Array.isArray(parsed.cart) ? parsed.cart : [];
      return cart
        .map(function (item) {
          if (!item || typeof item !== 'object') { return null; }
          var id = String(item.id || '').trim();
          if (!id) { return null; }
          var quantity = Math.max(0, Math.floor(parseNumber(item.quantity == null ? 1 : item.quantity)));
          if (quantity <= 0) { return null; }
          var price = parseNumber(item.price);
          var currency = String(item.currency || fallbackCurrency || '').trim();
          return {
            id: id,
            sku: String(item.sku || '').trim(),
            label: String(item.label || id),
            image: String(item.image || ''),
            quantity: quantity,
            price: price,
            currency: currency,
            lineTotal: price * quantity
          };
        })
        .filter(Boolean);
    } catch (_err) {
      return [];
    }
  }

  function writeCart(cart) {
    if (!storageKey) { return; }
    try {
      var items = {};
      var stored = [];
      cart.forEach(function (item) {
        if (item.quantity > 0) {
          items[item.id] = item.quantity;
          stored.push({
            id: item.id, sku: item.sku, label: item.label, quantity: item.quantity,
            price: item.price, currency: item.currency, image: item.image,
            lineTotal: item.price * item.quantity
          });
        }
      });
      window.localStorage.setItem(storageKey, JSON.stringify({ items: items, cart: stored, updatedAt: Date.now() }));
    } catch (_err) {}
  }

  function svgImagePlaceholder() {
    return '<span class="cs-thumb-empty" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 10.5 12 3l9 7.5"></path></svg></span>';
  }
  function svgTrash() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v5"></path><path d="M14 11v5"></path></svg>';
  }

  function esc(value) {
    var div = document.createElement('div');
    div.textContent = String(value == null ? '' : value);
    return div.innerHTML;
  }

  function buildRow(item) {
    var li = document.createElement('li');
    li.className = 'cs-item';
    li.setAttribute('data-cart-item', '');
    li.setAttribute('data-item-id', item.id);
    li.setAttribute('data-item-price', String(item.price));
    li.setAttribute('data-item-currency', item.currency);
    var media = item.image
      ? '<img src="' + esc(item.image) + '" alt="" loading="lazy">'
      : svgImagePlaceholder();
    if (readonly) {
      li.innerHTML =
        '<span class="cs-thumb">' + media + '</span>' +
        '<div class="cs-item-body">' +
          '<p class="cs-item-label">' + esc(item.label) + '</p>' +
          '<p class="cs-item-qty">' + esc(formatMoney(item.price, item.currency)) + ' × ' + esc(item.quantity) + '</p>' +
        '</div>' +
        '<span class="cs-item-subtotal" data-cart-subtotal>' + esc(formatMoney(item.lineTotal, item.currency)) + '</span>';
      return li;
    }
    var href = detailUrl(item.id);
    li.innerHTML =
      '<a class="cs-thumb" href="' + esc(href) + '" aria-label="' + esc(item.label) + '">' + media + '</a>' +
      '<div class="cs-item-body">' +
        '<p class="cs-item-label"><a class="cs-item-link" href="' + esc(href) + '">' + esc(item.label) + '</a></p>' +
        '<p class="cs-item-qty">' + esc(formatMoney(item.price, item.currency)) + '</p>' +
        '<div class="cs-item-actions">' +
          '<div class="cs-stepper">' +
            '<button type="button" class="cs-stepper-btn" data-cart-decrease aria-label="Decrease quantity">−</button>' +
            '<span class="cs-stepper-qty" data-cart-quantity>' + esc(item.quantity) + '</span>' +
            '<button type="button" class="cs-stepper-btn" data-cart-increase aria-label="Increase quantity">+</button>' +
          '</div>' +
          '<button type="button" class="cs-remove" data-cart-remove aria-label="Remove">' + svgTrash() + '</button>' +
        '</div>' +
      '</div>' +
      '<span class="cs-item-subtotal" data-cart-subtotal>' + esc(formatMoney(item.lineTotal, item.currency)) + '</span>';
    return li;
  }

  function render() {
    var cart = readCart();
    if (!itemsEl) { return cart; }
    itemsEl.innerHTML = '';
    if (!cart.length) {
      var empty = document.createElement('li');
      empty.className = 'cs-empty';
      empty.textContent = root.getAttribute('data-empty-label') || 'Your cart is empty.';
      itemsEl.appendChild(empty);
      if (totalsEl) { totalsEl.hidden = true; }
      if (footerEl) { footerEl.hidden = true; }
      return cart;
    }
    if (totalsEl) { totalsEl.hidden = false; }
    if (footerEl) { footerEl.hidden = false; }
    var total = 0;
    var currency = fallbackCurrency;
    cart.forEach(function (item) {
      total += item.lineTotal;
      if (item.currency) { currency = item.currency; }
      itemsEl.appendChild(buildRow(item));
    });
    if (totalEl) { totalEl.textContent = formatMoney(total, currency); }
    return cart;
  }

  function changeQuantity(id, delta) {
    var cart = readCart();
    var next = [];
    cart.forEach(function (item) {
      if (item.id === id) {
        var q = item.quantity + delta;
        if (q > 0) { item.quantity = q; item.lineTotal = item.price * q; next.push(item); }
      } else {
        next.push(item);
      }
    });
    writeCart(next);
    render();
  }

  function removeItem(id) {
    var cart = readCart().filter(function (item) { return item.id !== id; });
    writeCart(cart);
    render();
  }

  root.addEventListener('click', function (event) {
    if (readonly) { return; }
    var target = event.target;
    if (!(target instanceof Element)) { return; }
    var row = target.closest('[data-cart-item]');
    var id = row instanceof HTMLElement ? (row.getAttribute('data-item-id') || '') : '';

    if (target.closest('[data-cart-checkout]')) {
      event.preventDefault();
      if (!readCart().length) {
        if (catalogUrl) { window.location.href = catalogUrl; }
        return;
      }
      if (checkoutUrl) { window.location.href = checkoutUrl; }
      return;
    }
    if (!row || !id) { return; }
    if (target.closest('[data-cart-decrease]')) { changeQuantity(id, -1); return; }
    if (target.closest('[data-cart-increase]')) { changeQuantity(id, 1); return; }
    if (target.closest('[data-cart-remove]')) { removeItem(id); return; }
    if (!target.closest('a, button')) {
      var link = row.querySelector('.cs-item-link');
      if (link instanceof HTMLAnchorElement && link.href) { window.location.href = link.href; }
    }
  });

  render();
  window.addEventListener('pageshow', render);
})();

/**
 * Checkout payment-method selector: reveal the instructions block for the selected
 * manual method (Stripe has none). Independent of the cart-summary page.
 */
(function () {
  var wrap = document.querySelector('[data-checkout-payment]');
  if (!wrap) { return; }
  function sync() {
    var checked = wrap.querySelector('input[name="xpui_payment_method"]:checked');
    var value = checked ? checked.value : '';
    wrap.querySelectorAll('[data-payment-instructions]').forEach(function (node) {
      node.hidden = node.getAttribute('data-payment-instructions') !== value;
    });
  }
  wrap.addEventListener('change', function (e) {
    if (e.target && e.target.name === 'xpui_payment_method') { sync(); }
  });
  sync();
})();
