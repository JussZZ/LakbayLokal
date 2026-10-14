/* Landing-page hero: crossfading destination background.
   Data comes from #heroData (rendered by PHP from the database), so images
   and text can change without touching this file. */
(function () {
  'use strict';
  var root = document.querySelector('[data-hero]');
  if (!root) { return; }
  var slides = [];
  try { slides = JSON.parse(document.getElementById('heroData').textContent); } catch (err) { return; }
  if (!slides.length) { return; }

  var DURATION = 950;                         // matches the CSS crossfade
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  var layers = root.querySelectorAll('.hero__layer');
  var copy = root.querySelector('[data-hero-copy]');
  var els = {
    loc: root.querySelector('[data-hero-location]'),
    title: root.querySelector('[data-hero-title]'),
    desc: root.querySelector('[data-hero-desc]'),
    book: root.querySelector('[data-hero-book]'),
    more: root.querySelector('[data-hero-link]'),
    cur: root.querySelector('[data-hero-current]'),
    bar: root.querySelector('[data-hero-bar]')
  };
  var thumbs = root.querySelectorAll('.thumb');
  var current = 0, activeLayer = 0, locked = false, pending = null, textTimer = null;

  function pad(n) { return (n < 10 ? '0' : '') + n; }

  function paint(layer, s) {
    layer.style.setProperty('--hue', s.hue);
    if (s.image) {
      layer.style.backgroundImage = 'url("' + String(s.image).replace(/"/g, '%22') + '")';
      layer.classList.remove('is-placeholder');
    } else {
      layer.style.backgroundImage = '';
      layer.classList.add('is-placeholder');
    }
  }

  function preload(i) {
    var s = slides[(i + slides.length) % slides.length];
    if (!s.image || s._probed) { return; }
    s._probed = true;
    var img = new Image();
    img.onerror = function () {                // graceful fallback if the file vanished
      s.image = null;
      if (slides[current] === s) { paint(layers[activeLayer], s); }
    };
    img.src = s.image;
  }

  function setText(s) {
    els.loc.textContent = s.location;
    els.title.textContent = s.name;
    els.desc.textContent = s.description;
    els.book.href = s.bookUrl;
    els.more.href = s.url;
    els.book.setAttribute('aria-label', 'Book ' + s.name);
    els.cur.textContent = pad(current + 1);
    els.bar.style.setProperty('--p', (current + 1) / slides.length);
  }

  function markThumbs() {
    thumbs.forEach(function (t, i) {
      var on = i === current;
      t.classList.toggle('is-active', on);
      t.setAttribute('aria-pressed', on ? 'true' : 'false');
      if (on) { t.setAttribute('aria-current', 'true'); } else { t.removeAttribute('aria-current'); }
    });
    var active = thumbs[current];
    if (active && active.scrollIntoView) {
      var rail = active.parentNode;
      rail.scrollTo({ left: Math.max(0, active.offsetLeft - rail.offsetLeft - 8), behavior: reduce.matches ? 'auto' : 'smooth' });
    }
  }

  function goTo(index, first) {
    index = (index + slides.length) % slides.length;
    if (!first && index === current) { return; }
    if (locked) { pending = index; return; }     // rapid clicks: remember only the latest request
    var s = slides[index];
    current = index;
    if (first) {
      paint(layers[0], s); layers[0].classList.add('is-active');
      setText(s); markThumbs(); preload(index + 1); preload(index - 1);
      return;
    }
    locked = true;
    var next = 1 - activeLayer;
    paint(layers[next], s);
    layers[next].classList.add('is-active');
    layers[activeLayer].classList.remove('is-active');
    activeLayer = next;
    markThumbs();
    els.cur.textContent = pad(current + 1);
    els.bar.style.setProperty('--p', (current + 1) / slides.length);
    clearTimeout(textTimer);
    if (reduce.matches) { setText(s); } else {
      copy.classList.add('is-changing');
      textTimer = setTimeout(function () { setText(s); copy.classList.remove('is-changing'); }, 280);
    }
    preload(index + 1); preload(index - 1);
    setTimeout(function () {
      locked = false;
      if (pending !== null) { var p = pending; pending = null; if (p !== current) { goTo(p); } }
    }, reduce.matches ? 0 : DURATION);
  }

  thumbs.forEach(function (t, i) { t.addEventListener('click', function () { goTo(i); }); });
  var prev = root.querySelector('[data-hero-prev]'), nextBtn = root.querySelector('[data-hero-next]');
  if (prev) { prev.addEventListener('click', function () { goTo(current - 1); }); }
  if (nextBtn) { nextBtn.addEventListener('click', function () { goTo(current + 1); }); }
  root.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowRight') { goTo(current + 1); } else if (e.key === 'ArrowLeft') { goTo(current - 1); }
  });
  goTo(0, true);
})();
