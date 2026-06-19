(function() {
  var lastOffset = -1;

  function adjustThemeHeaderOffset() {
    var totalOffset = 0;

    // 1. Detect WordPress Admin Bar
    var wpAdminBar = document.getElementById('wpadminbar');
    if (wpAdminBar) {
      totalOffset += wpAdminBar.getBoundingClientRect().height;
    }

    // 2. Scan for active or configured sticky theme headers
    var potentialHeaders = document.querySelectorAll('header, div.main-header, div.site-header, nav.navbar, [class*="sticky"], [id*="sticky"], [class*="fixed"], [id*="fixed"], [id*="header"], [class*="header"]');
    var maxHeaderHeight = 0;

    for (var i = 0; i < potentialHeaders.length; i++) {
      var el = potentialHeaders[i];
      // Skip our own components
      if (el.closest('.xpressui-embed-wrapper')) {
        continue;
      }

      var style = window.getComputedStyle(el);
      var position = style.position;
      
      var className = typeof el.className === 'string' ? el.className : (el.getAttribute('class') || '');
      var id = typeof el.id === 'string' ? el.id : (el.getAttribute('id') || '');
      var isStickyConfigured = el.classList.contains('is-sticky-menu') || 
                               el.classList.contains('is-sticky-on') || 
                               className.indexOf('sticky') !== -1 ||
                               className.indexOf('fixed') !== -1 ||
                               id.indexOf('sticky') !== -1 ||
                               id.indexOf('fixed') !== -1;

      if (position === 'fixed' || position === 'sticky' || isStickyConfigured) {
        var rect = el.getBoundingClientRect();
        // Element must start near top of viewport, have positive height, and be wide.
        // Relax top constraint slightly for pre-scroll state where it might sit below top bar.
        var isTopValid = (position === 'fixed' || position === 'sticky') ? (rect.top <= 50) : (rect.top <= 150);
        if (isTopValid && rect.bottom > 10 && rect.width > window.innerWidth * 0.4) {
          var h = rect.height;
          if (h > maxHeaderHeight) {
            maxHeaderHeight = h;
          }
        }
      }
    }

    totalOffset += maxHeaderHeight;

    // 3. Inject CSS custom property if changed
    if (totalOffset !== lastOffset) {
      lastOffset = totalOffset;
      document.documentElement.style.setProperty('--xpressui-header-offset', totalOffset + 'px');
      
      var mounts = document.querySelectorAll('[id^="xpressui-root"]');
      mounts.forEach(function(m) {
        m.style.setProperty('--xpressui-header-offset', totalOffset + 'px');
      });
    }
  }

  // Bind to various load/layout lifecycle events
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', adjustThemeHeaderOffset);
  } else {
    adjustThemeHeaderOffset();
  }
  window.addEventListener('load', adjustThemeHeaderOffset);
  window.addEventListener('resize', adjustThemeHeaderOffset);
  window.addEventListener('scroll', adjustThemeHeaderOffset);
})();
