/* LakbayLokal: navigation toggles, toast dismissal, confirmation dialogs */
(function () {
  'use strict';

  // Mobile menu toggles (public nav + admin sidebar)
  document.querySelectorAll('.nav-toggle').forEach(function (btn) {
    var target = document.getElementById(btn.getAttribute('aria-controls'));
    if (!target) { return; }
    btn.addEventListener('click', function () {
      var open = target.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && target.classList.contains('is-open')) {
        target.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
        btn.focus();
      }
    });
  });

  // Toasts
  document.querySelectorAll('[data-dismiss]').forEach(function (b) {
    b.addEventListener('click', function () { b.closest('.toast').remove(); });
  });
  document.querySelectorAll('.toast--success, .toast--info').forEach(function (t) {
    setTimeout(function () { if (t.parentNode) { t.remove(); } }, 7000);
  });

  // Confirmation dialog for forms with data-confirm="message"
  var dialog = document.getElementById('confirmDialog');
  var skip = new WeakSet();
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches || !form.matches('form[data-confirm]') || skip.has(form)) { return; }
    e.preventDefault();
    var message = form.getAttribute('data-confirm');
    var submitter = e.submitter || null;
    var proceed = function () {
      skip.add(form);
      if (form.requestSubmit) { form.requestSubmit(submitter); } else { form.submit(); }
    };
    if (dialog && typeof dialog.showModal === 'function') {
      document.getElementById('confirmMessage').textContent = message;
      document.getElementById('confirmOk').textContent = form.getAttribute('data-confirm-label') || 'Confirm';
      dialog.returnValue = '';
      dialog.addEventListener('close', function onClose() {
        dialog.removeEventListener('close', onClose);
        if (dialog.returnValue === 'ok') { proceed(); }
      });
      dialog.showModal();
    } else if (window.confirm(message)) {
      proceed();
    }
  });

  // Destination image preview on the admin form
  var fileInput = document.getElementById('f_image_file');
  var preview = document.getElementById('imgPreview');
  if (fileInput && preview) {
    fileInput.addEventListener('change', function () {
      var f = fileInput.files && fileInput.files[0];
      if (!f || !/^image\//.test(f.type)) { return; }
      preview.innerHTML = '';
      var img = document.createElement('img');
      img.className = 'cover';
      img.alt = 'Selected image preview';
      img.src = URL.createObjectURL(f);
      preview.appendChild(img);
    });
  }

  // Live total on the booking form
  var trav = document.getElementById('f_travelers');
  var total = document.getElementById('estTotal');
  if (trav && total) {
    var price = parseFloat(total.getAttribute('data-price') || '0');
    var sym = total.getAttribute('data-symbol') || '';
    var update = function () {
      var n = parseInt(trav.value, 10);
      total.textContent = (n > 0 && price >= 0) ? sym + (n * price).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
    };
    trav.addEventListener('input', update);
    update();
  }
})();
