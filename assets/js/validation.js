/* Client-side validation for forms marked data-validate.
   Mirrors the PHP rules for instant feedback; the server always re-validates. */
(function () {
  'use strict';
  var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function message(el) {
    var v = el.value.trim();
    var label = (el.labels && el.labels[0] ? el.labels[0].textContent.replace('*', '').trim() : 'This field');
    if (el.required && v === '' && el.type !== 'file') { return label + ' is required.'; }
    if (v === '') { return ''; }
    if (el.type === 'email' && !EMAIL.test(v)) { return 'Enter a valid email address.'; }
    if (el.type === 'number') {
      var n = Number(v);
      if (isNaN(n)) { return 'Enter a valid number.'; }
      if (el.min !== '' && n < Number(el.min)) { return 'Must be at least ' + el.min + '.'; }
      if (el.max !== '' && n > Number(el.max)) { return 'Must be ' + el.max + ' or less.'; }
      if (el.step === '1' && !/^-?\d+$/.test(v)) { return 'Enter a whole number.'; }
    }
    if (el.type === 'date') {
      if (el.min && v < el.min) { return 'Choose a date on or after ' + el.min + '.'; }
      if (el.max && v > el.max) { return 'Choose a date on or before ' + el.max + '.'; }
    }
    if (el.minLength > 0 && v.length < el.minLength) { return label + ' must be at least ' + el.minLength + ' characters.'; }
    if (el.getAttribute('pattern') && !new RegExp('^(?:' + el.getAttribute('pattern') + ')$').test(v)) {
      return el.getAttribute('data-pattern-message') || 'Please match the requested format.';
    }
    if (el.getAttribute('data-match')) {
      var other = document.getElementById(el.getAttribute('data-match'));
      if (other && other.value !== el.value) { return 'Passwords do not match.'; }
    }
    if (el.getAttribute('data-password') !== null) {
      if (v.length < 8) { return 'Password must be at least 8 characters.'; }
      if (!/[A-Za-z]/.test(v) || !/[0-9]/.test(v)) { return 'Password must include a letter and a number.'; }
    }
    return '';
  }

  function show(el, msg) {
    var wrap = el.closest('.field');
    if (!wrap) { return; }
    var err = wrap.querySelector('.field-error');
    if (!msg) {
      wrap.classList.remove('has-error');
      el.removeAttribute('aria-invalid');
      if (err) { err.remove(); }
      return;
    }
    wrap.classList.add('has-error');
    el.setAttribute('aria-invalid', 'true');
    if (!err) {
      err = document.createElement('p');
      err.className = 'field-error';
      err.id = el.id + '_err';
      err.setAttribute('role', 'alert');
      wrap.appendChild(err);
      el.setAttribute('aria-describedby', ((el.getAttribute('aria-describedby') || '') + ' ' + err.id).trim());
    }
    err.textContent = msg;
  }

  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.setAttribute('novalidate', 'novalidate');
    var fields = form.querySelectorAll('input:not([type=hidden]):not([type=file]), select, textarea');
    fields.forEach(function (el) {
      el.addEventListener('blur', function () { show(el, message(el)); });
      el.addEventListener('input', function () { if (el.closest('.has-error')) { show(el, message(el)); } });
    });
    form.addEventListener('submit', function (e) {
      var first = null;
      fields.forEach(function (el) {
        var m = message(el);
        show(el, m);
        if (m && !first) { first = el; }
      });
      if (first) { e.preventDefault(); e.stopImmediatePropagation(); first.focus(); }
    }, true);
  });
})();
