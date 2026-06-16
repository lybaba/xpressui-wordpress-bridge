(function () {
  function revealSplashForm(splash) {
    if (!splash) { return; }
    if (document.body.classList.contains('hosted-catalog-page')) {
      document.body.classList.remove('xpressui-hosted-intro-active');
      var catalogRoot = document.getElementById('xpressui-root');
      if (catalogRoot && typeof catalogRoot.scrollIntoView === 'function') {
        catalogRoot.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
      return;
    }
    splash.style.opacity = '0';
    splash.style.pointerEvents = 'none';
    window.setTimeout(function () {
      splash.style.display = 'none';
      document.body.classList.remove('has-hosted-splash');
      document.body.classList.remove('xpressui-hosted-intro-active');
    }, 380);
  }

  function initIntro() {
    var splash = document.getElementById('xpressui-splash');
    if (!splash) { return; }
    var isInlineIntro = splash.getAttribute('data-xpressui-inline-intro') === 'true';
    if (!isInlineIntro) {
      document.body.classList.add('xpressui-hosted-intro-active');
    }

    var gallery = splash.querySelector('[data-xpressui-intro-gallery]');
    if (gallery) {
      var isShowcaseMode = gallery.getAttribute('data-gallery-mode') === 'showcase';
      var slides = Array.prototype.slice.call(gallery.querySelectorAll('.xpressui-splash-slide'));
      var dots = Array.prototype.slice.call(gallery.querySelectorAll('[data-xpressui-gallery-dot]'));
      var thumbnails = Array.prototype.slice.call(gallery.querySelectorAll('[data-xpressui-gallery-thumb]'));
      var detailsModal = splash.querySelector('[data-xpressui-intro-details-modal]');
      var detailsOpen = splash.querySelector('[data-xpressui-intro-details-open]');
      var detailsClose = detailsModal ? detailsModal.querySelector('[data-xpressui-intro-details-close]') : null;
      var openDetails = function () {
        if (!detailsModal) { return; }
        detailsModal.hidden = false;
        detailsModal.classList.add('is-open');
        document.body.classList.add('xpressui-intro-details-open');
      };
      var closeDetails = function () {
        if (!detailsModal) { return; }
        detailsModal.classList.remove('is-open');
        detailsModal.hidden = true;
        document.body.classList.remove('xpressui-intro-details-open');
      };
      var currentSlide = 0;
      var slideTimer = null;
      var showSlide = function (index) {
        if (!slides.length) { return; }
        currentSlide = (index + slides.length) % slides.length;
        slides.forEach(function (slide, slideIndex) {
          slide.classList.toggle('is-active', slideIndex === currentSlide);
        });
        dots.forEach(function (dot, dotIndex) {
          dot.classList.toggle('is-active', dotIndex === currentSlide);
        });
        thumbnails.forEach(function (thumbnail, thumbnailIndex) {
          thumbnail.classList.toggle('is-active', thumbnailIndex === currentSlide);
        });
      };
      var scheduleNextSlide = function () {
        if (isShowcaseMode) { return; }
        if (slideTimer) { window.clearInterval(slideTimer); }
        if (slides.length <= 1) { return; }
        slideTimer = window.setInterval(function () {
          showSlide(currentSlide + 1);
        }, 5200);
      };
      var goToSlide = function (index) {
        showSlide(index);
        scheduleNextSlide();
      };
      var previousButton = gallery.querySelector('[data-xpressui-gallery-prev]');
      var nextButton = gallery.querySelector('[data-xpressui-gallery-next]');
      var lightbox = splash.querySelector('[data-xpressui-gallery-lightbox]');
      var lightboxImage = lightbox ? lightbox.querySelector('[data-xpressui-gallery-lightbox-image]') : null;
      var lightboxClose = lightbox ? lightbox.querySelector('[data-xpressui-gallery-lightbox-close]') : null;
      var lightboxPrev = lightbox ? lightbox.querySelector('[data-xpressui-gallery-lightbox-prev]') : null;
      var lightboxNext = lightbox ? lightbox.querySelector('[data-xpressui-gallery-lightbox-next]') : null;
      var openLightbox = function (index) {
        if (!lightbox || !lightboxImage || !slides.length) { return; }
        if (typeof index === 'number' && !Number.isNaN(index)) {
          showSlide(index);
        }
        var activeImage = slides[currentSlide];
        lightboxImage.setAttribute('src', activeImage.getAttribute('src') || '');
        lightbox.hidden = false;
        lightbox.classList.add('is-open');
        document.body.classList.add('xpressui-gallery-lightbox-open');
      };
      var closeLightbox = function () {
        if (!lightbox) { return; }
        lightbox.classList.remove('is-open');
        lightbox.hidden = true;
        document.body.classList.remove('xpressui-gallery-lightbox-open');
      };
      if (previousButton) {
        previousButton.addEventListener('click', function (event) {
          event.stopPropagation();
          goToSlide(currentSlide - 1);
        });
      }
      if (nextButton) {
        nextButton.addEventListener('click', function (event) {
          event.stopPropagation();
          goToSlide(currentSlide + 1);
        });
      }
      if (detailsOpen) {
        detailsOpen.addEventListener('click', function (event) {
          event.stopPropagation();
          openDetails();
        });
      }
      if (detailsClose) {
        detailsClose.addEventListener('click', function (event) {
          event.stopPropagation();
          closeDetails();
        });
      }
      if (detailsModal) {
        detailsModal.addEventListener('click', function (event) {
          if (event.target === detailsModal) { closeDetails(); }
        });
      }
      if (lightboxClose) {
        lightboxClose.addEventListener('click', function (event) {
          event.stopPropagation();
          closeLightbox();
        });
      }
      if (lightbox) {
        lightbox.addEventListener('click', function (event) {
          if (event.target === lightbox) { closeLightbox(); }
        });
      }
      if (lightboxPrev) {
        lightboxPrev.addEventListener('click', function (event) {
          event.stopPropagation();
          openLightbox(currentSlide - 1);
        });
      }
      if (lightboxNext) {
        lightboxNext.addEventListener('click', function (event) {
          event.stopPropagation();
          openLightbox(currentSlide + 1);
        });
      }

      // Swipe gestures for touch devices
      var touchStartX = 0;
      var touchStartY = 0;
      var minSwipeDistance = 40;

      var handleTouchStart = function (event) {
        if (!event.touches || event.touches.length === 0) { return; }
        touchStartX = event.touches[0].clientX;
        touchStartY = event.touches[0].clientY;
      };

      var handleTouchEnd = function (event, onSwipeLeft, onSwipeRight) {
        if (event.target.closest('button') || event.target.closest('a')) { return; }
        if (!event.changedTouches || event.changedTouches.length === 0) { return; }
        var touchEndX = event.changedTouches[0].clientX;
        var touchEndY = event.changedTouches[0].clientY;
        var diffX = touchEndX - touchStartX;
        var diffY = touchEndY - touchStartY;

        // Trigger swipe if horizontal motion was dominant and exceeded threshold
        if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > minSwipeDistance) {
          if (diffX > 0) {
            onSwipeRight();
          } else {
            onSwipeLeft();
          }
        }
      };

      if (gallery) {
        gallery.addEventListener('touchstart', handleTouchStart, { passive: true });
        gallery.addEventListener('touchend', function (event) {
          handleTouchEnd(event, function () {
            goToSlide(currentSlide + 1);
          }, function () {
            goToSlide(currentSlide - 1);
          });
        }, { passive: true });
      }

      if (lightbox) {
        lightbox.addEventListener('touchstart', handleTouchStart, { passive: true });
        lightbox.addEventListener('touchend', function (event) {
          handleTouchEnd(event, function () {
            openLightbox(currentSlide + 1);
          }, function () {
            openLightbox(currentSlide - 1);
          });
        }, { passive: true });
      }
      dots.forEach(function (dot) {
        dot.addEventListener('click', function (event) {
          event.stopPropagation();
          var index = parseInt(dot.getAttribute('data-xpressui-gallery-dot') || '0', 10);
          goToSlide(index);
        });
      });
      thumbnails.forEach(function (thumbnail) {
        thumbnail.addEventListener('click', function (event) {
          event.stopPropagation();
          var index = parseInt(thumbnail.getAttribute('data-xpressui-gallery-thumb') || '0', 10);
          goToSlide(index);
          if (!isShowcaseMode) {
            openLightbox(index);
          }
        });
      });
      if (isShowcaseMode) {
        var stage = gallery.querySelector('[data-xpressui-gallery-stage]');
        if (stage) {
          stage.addEventListener('click', function (event) {
            var isNavBtn = event.target.closest('[data-xpressui-gallery-prev],[data-xpressui-gallery-next]');
            if (!isNavBtn) { openLightbox(currentSlide); }
          });
        }
      } else {
        gallery.addEventListener('click', openLightbox);
      }
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          closeLightbox();
          closeDetails();
        }
        if (!lightbox || lightbox.hidden) { return; }
        if (event.key === 'ArrowLeft') { goToSlide(currentSlide - 1); openLightbox(); }
        if (event.key === 'ArrowRight') { goToSlide(currentSlide + 1); openLightbox(); }
      });
      showSlide(0);
      scheduleNextSlide();
    }

    var dismissButton = splash.querySelector('[data-xpressui-splash-dismiss]');
    if (dismissButton) {
      dismissButton.addEventListener('click', function (event) {
        if (event && window === window.top) { event.preventDefault(); }
        revealSplashForm(splash);
      });
    }

    if (isInlineIntro) { return; }

    var isEmbedRequest = new URLSearchParams(window.location.search).get('embed') === '1';
    if (window === window.top && !isEmbedRequest) { return; }

    document.documentElement.classList.add('xpressui-hosted-embed-launch');
    document.body.classList.add('xpressui-hosted-embed-launch');
    document.documentElement.style.overflow = 'hidden';
    document.documentElement.style.height = 'auto';
    document.documentElement.style.minHeight = '0';
    document.body.style.overflow = 'hidden';
    document.body.style.height = 'auto';
    document.body.style.minHeight = '0';
    document.body.style.margin = '0';
    document.body.style.padding = '0';

    var lastHeight = 0;
    var sendHeight = function () {
      var card = splash.querySelector('.xpressui-splash-card');
      var target = card || splash;
      var rect = target.getBoundingClientRect();
      var style = window.getComputedStyle(splash);
      var paddingTop = parseFloat(style.paddingTop) || 0;
      var paddingBottom = parseFloat(style.paddingBottom) || 0;
      var height = Math.max(220, Math.ceil(rect.height + paddingTop + paddingBottom + 4));
      if (Math.abs(height - lastHeight) < 3) { return; }
      lastHeight = height;
      window.parent.postMessage({ type: 'xpressui-embed-height', mode: 'launch', height: height }, '*');
    };

    new MutationObserver(sendHeight).observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      characterData: true,
    });
    window.addEventListener('load', sendHeight);
    window.addEventListener('resize', sendHeight);
    window.requestAnimationFrame(sendHeight);
    window.setTimeout(sendHeight, 80);
    window.setTimeout(sendHeight, 240);
    sendHeight();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initIntro);
  } else {
    initIntro();
  }
})();
