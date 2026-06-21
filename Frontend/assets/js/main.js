/**
 * VA Auto Sales - Main frontend JS
 * Stage 2: add lead tracking on WhatsApp clicks
 */
(function () {
  'use strict';

  // Mobile nav toggle
  var navToggle = document.querySelector('.nav-toggle');
  var siteNav = document.getElementById('site-nav');

  if (navToggle && siteNav) {
    navToggle.addEventListener('click', function () {
      var isOpen = siteNav.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', isOpen);
    });
  }

  // Sticky header shadow on scroll
  var siteHeader = document.getElementById('site-header');
  if (siteHeader) {
    var onScroll = function () {
      siteHeader.classList.toggle('is-scrolled', window.scrollY > 20);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Premium smooth scroll for anchor links (accounts for sticky header)
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      var targetId = this.getAttribute('href');
      if (targetId.length <= 1) return;

      var target = document.querySelector(targetId);
      if (!target) return;

      e.preventDefault();
      var headerOffset = siteHeader ? siteHeader.offsetHeight + 16 : 88;
      var top = target.getBoundingClientRect().top + window.pageYOffset - headerOffset;

      window.scrollTo({ top: top, behavior: 'smooth' });
    });
  });

  // Hero carousel
  var carousel = document.getElementById('hero-carousel');
  if (carousel) {
    var slides = carousel.querySelectorAll('.hero-carousel__slide');
    var dots = carousel.querySelectorAll('.hero-carousel__dot');
    var prevBtn = carousel.querySelector('.hero-carousel__btn--prev');
    var nextBtn = carousel.querySelector('.hero-carousel__btn--next');
    var current = 0;
    var autoplayTimer = null;
    var autoplayDelay = 5000;

    function goToSlide(index) {
      if (!slides.length) return;

      current = (index + slides.length) % slides.length;

      slides.forEach(function (slide, i) {
        slide.classList.toggle('is-active', i === current);
      });

      dots.forEach(function (dot, i) {
        dot.classList.toggle('is-active', i === current);
      });
    }

    function nextSlide() { goToSlide(current + 1); }
    function prevSlide() { goToSlide(current - 1); }

    function startAutoplay() {
      stopAutoplay();
      if (slides.length > 1) {
        autoplayTimer = setInterval(nextSlide, autoplayDelay);
      }
    }

    function stopAutoplay() {
      if (autoplayTimer) {
        clearInterval(autoplayTimer);
        autoplayTimer = null;
      }
    }

    if (nextBtn) nextBtn.addEventListener('click', function () { nextSlide(); startAutoplay(); });
    if (prevBtn) prevBtn.addEventListener('click', function () { prevSlide(); startAutoplay(); });

    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        goToSlide(parseInt(dot.getAttribute('data-index'), 10));
        startAutoplay();
      });
    });

    carousel.addEventListener('mouseenter', stopAutoplay);
    carousel.addEventListener('mouseleave', startAutoplay);
    carousel.addEventListener('focusin', stopAutoplay);
    carousel.addEventListener('focusout', startAutoplay);

    startAutoplay();
  }

  // Collapsible filters (listings + homepage search)
  document.querySelectorAll('.filter-mobile-toggle').forEach(function (toggle) {
    var panelId = toggle.getAttribute('aria-controls');
    var panel = panelId ? document.getElementById(panelId) : null;
    if (!panel) return;

    toggle.addEventListener('click', function () {
      var isOpen = panel.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen);
    });

    panel.addEventListener('submit', function () {
      if (window.innerWidth <= 992) {
        panel.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  });

  // Populate homepage brand dropdown from API (fallback if empty)
  var BASE = window.APP_BASE || '';
  var homeBrand = document.getElementById('home-brand');
  if (homeBrand && homeBrand.options.length <= 1) {
    fetch(BASE + '/Backend/api/cars.php?meta=1')
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.success && data.brands) {
          data.brands.forEach(function (brand) {
            var opt = document.createElement('option');
            opt.value = brand;
            opt.textContent = brand;
            homeBrand.appendChild(opt);
          });
        }
      })
      .catch(function () { /* silent fail */ });
  }

  // Scroll reveal for sections
  var revealEls = document.querySelectorAll('.reveal-on-scroll');
  if ('IntersectionObserver' in window && revealEls.length) {
    var revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach(function (el) { revealObserver.observe(el); });
  }

  // Intersection observer for card animations
  var cards = document.querySelectorAll('.car-card:not(.animate-fade-up)');
  if ('IntersectionObserver' in window && cards.length) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-fade-up');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    cards.forEach(function (card) { observer.observe(card); });
  }

  // Car image grid lightbox (index + listings)
  var lightbox = document.getElementById('image-lightbox');
  if (lightbox) {
    var lightboxImg = document.getElementById('lightbox-img');
    var lightboxTitle = document.getElementById('lightbox-title');
    var lightboxCounter = document.getElementById('lightbox-counter');
    var lightboxThumbs = document.getElementById('lightbox-thumbs');
    var lightboxClose = document.getElementById('lightbox-close');
    var lightboxPrev = document.getElementById('lightbox-prev');
    var lightboxNext = document.getElementById('lightbox-next');
    var lightboxBackdrop = lightbox.querySelector('.image-lightbox__backdrop');
    var galleryImages = [];
    var galleryIndex = 0;
    var galleryTitle = '';

    function renderLightboxThumbs() {
      lightboxThumbs.innerHTML = '';
      galleryImages.forEach(function (src, i) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'image-lightbox__thumb' + (i === galleryIndex ? ' is-active' : '');
        btn.innerHTML = '<img src="' + src + '" alt="">';
        btn.addEventListener('click', function () { showLightboxImage(i); });
        lightboxThumbs.appendChild(btn);
      });
    }

    function showLightboxImage(index) {
      galleryIndex = index;
      lightboxImg.src = galleryImages[galleryIndex];
      lightboxImg.alt = galleryTitle + ' — photo ' + (galleryIndex + 1);
      lightboxCounter.textContent = (galleryIndex + 1) + ' / ' + galleryImages.length;
      lightboxPrev.style.display = galleryImages.length > 1 ? 'flex' : 'none';
      lightboxNext.style.display = galleryImages.length > 1 ? 'flex' : 'none';
      lightboxThumbs.style.display = galleryImages.length > 1 ? 'flex' : 'none';
      renderLightboxThumbs();
    }

    function openLightbox(images, title, startIndex) {
      galleryImages = images;
      galleryTitle = title || 'Car photos';
      galleryIndex = startIndex || 0;
      lightboxTitle.textContent = galleryTitle;
      showLightboxImage(galleryIndex);
      lightbox.classList.add('is-open');
      lightbox.setAttribute('aria-hidden', 'false');
      document.body.classList.add('lightbox-open');
    }

    function closeLightbox() {
      lightbox.classList.remove('is-open');
      lightbox.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('lightbox-open');
      lightboxImg.src = '';
    }

    document.querySelectorAll('.car-card__gallery').forEach(function (gallery) {
      gallery.addEventListener('click', function (e) {
        var thumb = e.target.closest('.car-card__thumb');
        if (!thumb) return;

        e.preventDefault();
        e.stopPropagation();

        var images = [];
        try {
          images = JSON.parse(gallery.getAttribute('data-gallery') || '[]');
        } catch (err) {
          images = [];
        }

        if (!images.length) return;

        var startIndex = parseInt(thumb.getAttribute('data-index'), 10) || 0;
        openLightbox(images, gallery.getAttribute('data-title') || '', startIndex);
      });
    });

    if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
    if (lightboxBackdrop) lightboxBackdrop.addEventListener('click', closeLightbox);
    if (lightboxPrev) lightboxPrev.addEventListener('click', function () {
      showLightboxImage((galleryIndex - 1 + galleryImages.length) % galleryImages.length);
    });
    if (lightboxNext) lightboxNext.addEventListener('click', function () {
      showLightboxImage((galleryIndex + 1) % galleryImages.length);
    });

    document.addEventListener('keydown', function (e) {
      if (!lightbox.classList.contains('is-open')) return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowLeft') showLightboxImage((galleryIndex - 1 + galleryImages.length) % galleryImages.length);
      if (e.key === 'ArrowRight') showLightboxImage((galleryIndex + 1) % galleryImages.length);
    });
  }

  // Track WhatsApp clicks (Stage 2 hook point)
  document.querySelectorAll('a[href*="wa.me"]').forEach(function (link) {
    link.addEventListener('click', function () {
      console.debug('WhatsApp contact initiated');
    });
  });

  // Copy car listing link to clipboard
  function showShareToast(message) {
    var toast = document.getElementById('share-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'share-toast';
      toast.className = 'share-toast';
      toast.setAttribute('role', 'status');
      toast.setAttribute('aria-live', 'polite');
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.add('is-visible');
    clearTimeout(toast._hideTimer);
    toast._hideTimer = setTimeout(function () {
      toast.classList.remove('is-visible');
    }, 2600);
  }

  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
      var textarea = document.createElement('textarea');
      textarea.value = text;
      textarea.setAttribute('readonly', '');
      textarea.style.position = 'fixed';
      textarea.style.left = '-9999px';
      document.body.appendChild(textarea);
      textarea.select();
      try {
        var ok = document.execCommand('copy');
        document.body.removeChild(textarea);
        ok ? resolve() : reject(new Error('copy failed'));
      } catch (err) {
        document.body.removeChild(textarea);
        reject(err);
      }
    });
  }

  document.querySelectorAll('.js-share-car').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      var url = btn.getAttribute('data-share-url');
      if (!url) return;

      var originalLabel = btn.innerHTML;

      copyText(url)
        .then(function () {
          showShareToast('Link copied! Share it anywhere.');
          btn.classList.add('is-copied');
          if (btn.classList.contains('btn--share')) {
            btn.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg> Copied';
          }
          clearTimeout(btn._resetTimer);
          btn._resetTimer = setTimeout(function () {
            btn.classList.remove('is-copied');
            btn.innerHTML = originalLabel;
          }, 2000);
        })
        .catch(function () {
          showShareToast('Could not copy. Link: ' + url);
        });
    });
  });
})();
