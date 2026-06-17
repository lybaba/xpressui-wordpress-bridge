(function(){
  var root = document.querySelector('[data-template-zone="workflow_time_slots_landing"]');
  if (!root) return;
  var grid = root.querySelector('[data-choice-time-slots]');
  if (!(grid instanceof HTMLElement)) return;
  var fieldName = grid.getAttribute('data-choice-list-grid') || 'timeSlots';
  var input = root.querySelector('input[name="' + fieldName + '"]');
  var checkoutUrl = root.getAttribute('data-time-slot-checkout-url') || '';
  var cartSummaryUrl = root.getAttribute('data-time-slot-cart-summary-url') || '';
  var cartTokenUrl = root.getAttribute('data-time-slot-cart-token-url') || '';
  var multiple = grid.getAttribute('data-time-slot-selection-mode') === 'multiple'
    || grid.getAttribute('data-time-slot-allow-multiple-selection') === 'true';

  function cards() {
    return Array.prototype.slice.call(root.querySelectorAll('[data-choice-option-value]'));
  }
  function selectedCards() {
    var seen = {};
    return cards().filter(function(card){
      var value = card.getAttribute('data-choice-option-value') || '';
      if (!value || seen[value] || card.getAttribute('data-selected') !== 'true') return false;
      seen[value] = true;
      return true;
    });
  }
  function setCard(card, selected) {
    var value = card.getAttribute('data-choice-option-value') || '';
    cards().forEach(function(other) {
      if ((other.getAttribute('data-choice-option-value') || '') !== value) return;
      other.setAttribute('data-selected', selected ? 'true' : 'false');
      other.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });
  }
  function updateValue() {
    var selected = selectedCards();
    var values = selected.map(function(card){ return card.getAttribute('data-choice-option-value') || ''; }).filter(Boolean);
    if (input) input.value = multiple ? JSON.stringify(values) : (values[0] || '');
  }
  function buildCheckoutUrl(first, values) {
    if (!checkoutUrl || checkoutUrl === '#') return '';
    var selectedValues = values && values.length
      ? values
      : [first.getAttribute('data-choice-option-value') || ''].filter(Boolean);
    if (!selectedValues.length) return '';
    var url = new URL(checkoutUrl, window.location.href);
    url.searchParams.set('timeSlotId', selectedValues[0] || '');
    url.searchParams.set('timeSlotIds', JSON.stringify(selectedValues));
    url.searchParams.set('slotField', fieldName);
    url.searchParams.set('timeSlotLabel', first.getAttribute('data-slot-label') || '');
    url.searchParams.set('timeSlotDate', first.getAttribute('data-slot-starts-at') || '');
    url.searchParams.set('timeSlotStartsAt', first.getAttribute('data-slot-starts-at') || '');
    url.searchParams.set('timeSlotEndsAt', first.getAttribute('data-slot-ends-at') || '');
    url.searchParams.set('timeSlotResource', first.getAttribute('data-slot-resource-label') || '');
    url.searchParams.set('timeSlotPrice', first.getAttribute('data-slot-price') || '');
    url.searchParams.set('timeSlotCurrency', first.getAttribute('data-slot-currency') || '');
    url.searchParams.set('timeSlotResourceImage', first.getAttribute('data-slot-resource-image') || '');
    return url.toString();
  }
  function catalogReturnUrl() {
    var configured = root.getAttribute('data-time-slot-catalog-return-url') || '';
    try {
      var url = new URL(configured || window.location.href, window.location.href);
      return url.toString();
    } catch (_err) {
      var fallback = new URL(window.location.href);
      fallback.hash = '';
      return fallback.toString();
    }
  }
  function goToBookingSummary(card) {
    var value = card.getAttribute('data-choice-option-value') || '';
    if (!value || !cartSummaryUrl || !cartTokenUrl) return false;
    fetch(cartTokenUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items: [{ id: value, quantity: 1 }] }),
    })
      .then(function(response) {
        if (!response.ok) { throw new Error('cart-token ' + response.status); }
        return response.json();
      })
      .then(function(data) {
        var url = new URL(cartSummaryUrl, window.location.origin);
        url.searchParams.set('cartToken', data.token);
        url.searchParams.set('redirect', catalogReturnUrl());
        window.location.href = url.toString();
      })
      .catch(function() {
        var fallback = buildCheckoutUrl(card, [value]);
        if (fallback) window.location.href = fallback;
      });
    return true;
  }
  function goToCheckout(card) {
    if (!(card instanceof HTMLElement)) return false;
    if (card.getAttribute('data-disabled') === 'true' || card.getAttribute('aria-disabled') === 'true') return false;
    if ((!checkoutUrl || checkoutUrl === '#') && !cartSummaryUrl) return false;
    if (!multiple) cards().forEach(function(other){ setCard(other, false); });
    setCard(card, true);
    updateValue();
    var value = card.getAttribute('data-choice-option-value') || '';
    if (goToBookingSummary(card)) return true;
    var url = buildCheckoutUrl(card, value ? [value] : []);
    if (!url) return false;
    window.location.href = url;
    return true;
  }
  function parseDate(value) {
    var parts = String(value || '').split('-').map(function(part){ return Number(part); });
    if (parts.length !== 3 || !parts[0] || !parts[1] || !parts[2]) return null;
    return new Date(parts[0], parts[1] - 1, parts[2]);
  }
  function formatDate(date) {
    if (!(date instanceof Date) || isNaN(date.getTime())) return '';
    return [
      date.getFullYear(),
      String(date.getMonth() + 1).padStart(2, '0'),
      String(date.getDate()).padStart(2, '0')
    ].join('-');
  }
  function addDays(date, amount) {
    var next = new Date(date.getTime());
    next.setDate(next.getDate() + amount);
    return next;
  }
  function addMonths(date, amount) {
    var next = new Date(date.getTime());
    next.setMonth(next.getMonth() + amount);
    return next;
  }
  function boardDates(board) {
    var dates = [];
    Array.prototype.slice.call(board.querySelectorAll('[data-time-slot-window-day]')).forEach(function(node) {
      var date = node.getAttribute('data-time-slot-date') || '';
      if (date && dates.indexOf(date) === -1) dates.push(date);
    });
    return dates.sort();
  }
  function setBoardWindow(board, startIndex) {
    var dates = boardDates(board);
    if (!dates.length) return;
    var maxStart = Math.max(0, dates.length - 5);
    var nextStart = Math.max(0, Math.min(startIndex, maxStart));
    board.setAttribute('data-time-slot-window-start', String(nextStart));
    var visible = dates.slice(nextStart, nextStart + 5);
    Array.prototype.slice.call(board.querySelectorAll('[data-time-slot-window-day]')).forEach(function(node) {
      var show = visible.indexOf(node.getAttribute('data-time-slot-date') || '') !== -1;
      node.hidden = !show;
    });
    Array.prototype.slice.call(board.querySelectorAll('[data-time-slot-window-nav]')).forEach(function(button) {
      var action = button.getAttribute('data-time-slot-window-nav') || '';
      var isPrevious = action.indexOf('prev_') === 0;
      button.disabled = isPrevious ? nextStart <= 0 : nextStart >= maxStart;
    });
  }
  function moveBoardWindow(board, action) {
    var dates = boardDates(board);
    if (!dates.length) return;
    var currentIndex = Number(board.getAttribute('data-time-slot-window-start') || '0');
    var currentDate = parseDate(dates[currentIndex] || dates[0]);
    if (!currentDate) return;
    var targetDate = currentDate;
    if (action === 'next_week') targetDate = addDays(currentDate, 7);
    if (action === 'prev_week') targetDate = addDays(currentDate, -7);
    if (action === 'next_month') targetDate = addMonths(currentDate, 1);
    if (action === 'prev_month') targetDate = addMonths(currentDate, -1);
    var target = formatDate(targetDate);
    var nextIndex = currentIndex;
    if (action.indexOf('next_') === 0) {
      nextIndex = dates.findIndex(function(date){ return date >= target; });
      if (nextIndex < 0) nextIndex = dates.length - 1;
    } else if (action.indexOf('prev_') === 0) {
      nextIndex = 0;
      for (var i = 0; i < dates.length; i += 1) {
        if (dates[i] <= target) nextIndex = i;
      }
    }
    setBoardWindow(board, nextIndex);
  }
  function initBoardWindows() {
    Array.prototype.slice.call(root.querySelectorAll('[data-time-slot-window-board]')).forEach(function(board) {
      setBoardWindow(board, Number(board.getAttribute('data-time-slot-window-start') || '0'));
    });
  }
  function toggleCard(card) {
    if (!(card instanceof HTMLElement)) return;
    if (card.getAttribute('data-disabled') === 'true' || card.getAttribute('aria-disabled') === 'true') return;
    var nextSelected = card.getAttribute('data-selected') !== 'true';
    if (!multiple) cards().forEach(function(other){ setCard(other, false); });
    setCard(card, nextSelected);
    updateValue();
  }
  root.addEventListener('click', function(event) {
    var target = event.target;
    if (!(target instanceof Element)) return;
    var nav = target.closest('[data-time-slot-window-nav]');
    if (nav && root.contains(nav)) {
      var board = nav.closest('[data-time-slot-window-board]');
      if (board) {
        event.preventDefault();
        moveBoardWindow(board, nav.getAttribute('data-time-slot-window-nav') || '');
      }
      return;
    }
    var card = target.closest('[data-choice-option-value]');
    if (card && root.contains(card)) {
      event.preventDefault();
      if (!goToCheckout(card)) toggleCard(card);
    }
  });
  root.addEventListener('keydown', function(event) {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    var target = event.target;
    if (!(target instanceof Element)) return;
    var card = target.closest('[data-choice-option-value]');
    if (card && root.contains(card)) {
      event.preventDefault();
      if (!goToCheckout(card)) toggleCard(card);
    }
  });
  updateValue();
  initBoardWindows();
})();
