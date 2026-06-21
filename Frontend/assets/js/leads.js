/**
 * Lead capture modal + buyer tracking
 */
(function () {
  'use strict';

  var modal = document.getElementById('lead-modal');
  if (!modal) return;

  var apiUrl = modal.getAttribute('data-api-url') || '';
  var base = (window.APP_BASE || '').replace(/\/$/, '');
  if (!apiUrl) apiUrl = (base ? base + '/' : '/') + 'Backend/api/leads.php';

  var modalBody = document.getElementById('lead-modal-body');
  var modalSuccess = document.getElementById('lead-modal-success');
  var modalClose = document.getElementById('lead-modal-close');
  var modalBackdrop = modal.querySelector('.lead-modal__backdrop');
  var modalDone = document.getElementById('lead-modal-done');
  var modalWhatsapp = document.getElementById('lead-modal-whatsapp');
  var vehicleEl = document.getElementById('lead-modal-vehicle');
  var tabs = modal.querySelectorAll('.lead-forms__tab');
  var forms = modal.querySelectorAll('.lead-form');

  var activeCar = { id: 0, title: '', price: '' };

  function setCarFields(carId, title, price) {
    activeCar = { id: carId, title: title, price: price };
    modal.querySelectorAll('.js-lead-car-id').forEach(function (el) {
      el.value = carId || '';
    });
    modal.querySelectorAll('.js-lead-vehicle').forEach(function (el) {
      el.value = title || '';
    });
    if (vehicleEl) {
      if (title && price) {
        vehicleEl.textContent = title + ' · ' + price;
        vehicleEl.hidden = false;
      } else if (title) {
        vehicleEl.textContent = title;
        vehicleEl.hidden = false;
      } else {
        vehicleEl.hidden = true;
      }
    }
  }

  function resetModalView() {
    if (modalBody) modalBody.hidden = false;
    if (modalSuccess) modalSuccess.hidden = true;
    forms.forEach(function (form) {
      form.reset();
      setCarFields(activeCar.id, activeCar.title, activeCar.price);
      var fb = form.querySelector('.lead-form__feedback');
      if (fb) fb.textContent = '';
    });
  }

  function openModal(carId, title, price) {
    resetModalView();
    setCarFields(carId, title, price);
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('lead-modal-open');

    if (carId && !sessionStorage.getItem('viewed_car_' + carId)) {
      fetch(apiUrl + '?action=track', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ activity_type: 'vehicle_viewed', car_id: carId }),
      }).catch(function () {});
      sessionStorage.setItem('viewed_car_' + carId, '1');
    }

    var firstInput = modal.querySelector('.lead-form:not([hidden]) input:not([type="hidden"])');
    if (firstInput) setTimeout(function () { firstInput.focus(); }, 200);
  }

  function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('lead-modal-open');
  }

  function showSuccess(whatsappUrl) {
    if (modalBody) modalBody.hidden = true;
    if (modalSuccess) modalSuccess.hidden = false;
    if (modalWhatsapp && whatsappUrl) {
      modalWhatsapp.href = whatsappUrl;
    }
  }

  function openWhatsApp(url) {
    if (!url) return;
    var link = document.createElement('a');
    link.href = url;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  document.querySelectorAll('.js-open-lead-modal').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      openModal(
        parseInt(btn.getAttribute('data-car-id'), 10) || 0,
        btn.getAttribute('data-car-title') || '',
        btn.getAttribute('data-car-price') || ''
      );
    });
  });

  if (modalClose) modalClose.addEventListener('click', closeModal);
  if (modalBackdrop) modalBackdrop.addEventListener('click', closeModal);
  if (modalDone) modalDone.addEventListener('click', closeModal);

  if (modalWhatsapp) {
    modalWhatsapp.addEventListener('click', function () {
      var carId = activeCar.id;
      if (carId) {
        fetch(apiUrl + '?action=track', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ activity_type: 'whatsapp_click', car_id: carId }),
          keepalive: true,
        }).catch(function () {});
      }
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
  });

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var target = tab.getAttribute('data-tab');
      tabs.forEach(function (t) {
        t.classList.toggle('is-active', t === tab);
        t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
      });
      forms.forEach(function (form) {
        var match = form.getAttribute('data-inquiry-type') === target;
        form.classList.toggle('is-active', match);
        form.hidden = !match;
      });
    });
  });

  function showFeedback(form, message, isError) {
    var el = form.querySelector('.lead-form__feedback');
    if (!el) return;
    el.textContent = message;
    el.classList.toggle('lead-form__feedback--error', !!isError);
    el.classList.toggle('lead-form__feedback--success', !isError);
  }

  forms.forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var btn = form.querySelector('[type="submit"]');
      var data = {};
      new FormData(form).forEach(function (value, key) {
        data[key] = value;
      });
      data.source = 'website';

      if (!data.full_name || !data.phone_number) {
        showFeedback(form, 'Please fill in your name and phone number.', true);
        return;
      }

      btn.disabled = true;
      showFeedback(form, 'Saving your request…', false);

      fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      })
        .then(function (res) { return res.json(); })
        .then(function (result) {
          if (result.success) {
            showSuccess(result.whatsapp_url);
            setTimeout(function () {
              openWhatsApp(result.whatsapp_url);
            }, 600);
          } else {
            showFeedback(form, result.message || 'Something went wrong. Please try again.', true);
          }
        })
        .catch(function () {
          showFeedback(form, 'Connection error. Please try again.', true);
        })
        .finally(function () {
          btn.disabled = false;
        });
    });
  });
})();
