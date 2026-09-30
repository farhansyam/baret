/* BARET — interaksi ringan tanpa framework. */
(function () {
  'use strict';
  var root = document.documentElement;
  root.classList.remove('no-js');
  root.classList.add('js');

  function $(sel, ctx) { return (ctx || document).querySelector(sel); }
  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }
  function on(el, ev, fn, opt) { if (el) el.addEventListener(ev, fn, opt); }

  document.addEventListener('DOMContentLoaded', function () {
    headerShadow();
    drawer();
    sidebar();
    dropdowns();
    flashes();
    confirmModal();
    loadingButtons();
    fileInputs();
    stepper();
    editors();
    lightbox();
    charts();
    autosubmit();
    counters();
    statusForms();
  });

  function headerShadow() {
    var h = $('.site-header');
    if (!h) return;
    var tick = function () { h.classList.toggle('is-scrolled', window.scrollY > 8); };
    on(window, 'scroll', tick, { passive: true });
    tick();
  }

  function trapEscape(close) {
    var handler = function (e) { if (e.key === 'Escape') close(); };
    document.addEventListener('keydown', handler);
    return function () { document.removeEventListener('keydown', handler); };
  }

  function drawer() {
    var d = $('#drawer');
    if (!d) return;
    var openers = $$('[data-drawer-open]');
    var release = null;
    function open() {
      d.classList.add('is-open');
      d.setAttribute('aria-hidden', 'false');
      openers.forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
      document.body.classList.add('modal-open');
      var first = $('a, button', d.querySelector('.drawer__panel'));
      if (first) first.focus();
      release = trapEscape(close);
    }
    function close() {
      d.classList.remove('is-open');
      d.setAttribute('aria-hidden', 'true');
      openers.forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
      document.body.classList.remove('modal-open');
      if (release) release();
      if (openers[0]) openers[0].focus();
    }
    openers.forEach(function (b) { on(b, 'click', open); });
    $$('[data-drawer-close]', d).forEach(function (b) { on(b, 'click', close); });
  }

  function sidebar() {
    var sb = $('#sidebar'), bd = $('#sidebar-backdrop'), btn = $('[data-sidebar-toggle]');
    if (!sb || !btn) return;
    var release = null;
    function set(open) {
      sb.classList.toggle('is-open', open);
      bd.classList.toggle('is-open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) release = trapEscape(function () { set(false); });
      else if (release) release();
    }
    on(btn, 'click', function () { set(!sb.classList.contains('is-open')); });
    on(bd, 'click', function () { set(false); });
  }

  function dropdowns() {
    $$('[data-dropdown]').forEach(function (btn) {
      var menu = document.getElementById(btn.getAttribute('aria-controls'));
      if (!menu) return;
      function close() { menu.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); }
      on(btn, 'click', function (e) {
        e.stopPropagation();
        var open = !menu.classList.contains('is-open');
        menu.classList.toggle('is-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      on(document, 'click', function (e) { if (!menu.contains(e.target)) close(); });
      on(document, 'keydown', function (e) { if (e.key === 'Escape') close(); });
    });
  }

  function flashes() {
    $$('.flash-stack .alert').forEach(function (a) {
      function hide() { a.classList.add('is-hiding'); setTimeout(function () { a.remove(); }, 260); }
      on($('.alert__close', a), 'click', hide);
      if (a.classList.contains('alert--success')) setTimeout(hide, 6000);
    });
  }

  /* Modal konfirmasi sebelum aksi hapus/ubah status */
  function confirmModal() {
    var modal = $('#confirm-modal');
    if (!modal) return;
    var title = $('#confirm-title', modal), msg = $('#confirm-message', modal), ok = $('[data-confirm-ok]', modal);
    var pending = null, pendingBtn = null, lastFocus = null, release = null;
    function close() {
      modal.classList.remove('is-open');
      document.body.classList.remove('modal-open');
      if (release) release();
      if (lastFocus) lastFocus.focus();
      pending = null;
    }
    $$('[data-confirm-cancel]', modal).forEach(function (b) { on(b, 'click', close); });
    on(ok, 'click', function () {
      if (!pending) return;
      var f = pending, b = pendingBtn;
      f.dataset.confirmed = '1';
      close();
      if (f.requestSubmit) f.requestSubmit(b && b.form === f ? b : undefined); else f.submit();
    });
    on(document, 'submit', function (e) {
      var f = e.target;
      var sub = e.submitter;
      var needs = f.matches('form[data-confirm]') || (sub && sub.hasAttribute('data-confirm'));
      if (!needs || f.dataset.confirmed === '1') return;
      e.preventDefault();
      e.stopImmediatePropagation();
      pending = f;
      pendingBtn = e.submitter || null;
      lastFocus = document.activeElement;
      var src = pendingBtn && pendingBtn.hasAttribute('data-confirm') ? pendingBtn : f;
      title.textContent = src.getAttribute('data-confirm-title') || f.getAttribute('data-confirm-title') || 'Konfirmasi';
      msg.textContent = src.getAttribute('data-confirm');
      ok.textContent = src.getAttribute('data-confirm-ok') || 'Ya, lanjutkan';
      ok.className = 'btn ' + (src.getAttribute('data-confirm-tone') === 'primary' ? 'btn--primary' : 'btn--danger');
      modal.classList.add('is-open');
      document.body.classList.add('modal-open');
      ok.focus();
      release = trapEscape(close);
    }, true);
  }

  /* Status loading pada tombol submit */
  function loadingButtons() {
    on(document, 'submit', function (e) {
      var f = e.target;
      if (e.defaultPrevented || f.hasAttribute('data-no-loading')) return;
      var btn = e.submitter || $('button[type=submit], button:not([type])', f);
      if (!btn || btn.classList.contains('is-loading')) return;
      if (btn.name) {
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = btn.name; h.value = btn.value; f.appendChild(h);
      }
      setTimeout(function () { btn.classList.add('is-loading'); btn.disabled = true; }, 0);
      if (f.method.toLowerCase() === 'get') setTimeout(function () { btn.classList.remove('is-loading'); btn.disabled = false; }, 4000);
    });
    // Kembali via tombol back: pulihkan tombol
    on(window, 'pageshow', function () { $$('.btn.is-loading').forEach(function (b) { b.classList.remove('is-loading'); b.disabled = false; }); });
  }

  function fileInputs() {
    $$('.file-drop').forEach(function (drop) {
      var input = $('input[type=file]', drop), text = $('.file-drop__text strong', drop), icon = $('.file-drop__icon', drop);
      if (!input) return;
      var original = text ? text.textContent : '';
      var originalIcon = icon ? icon.innerHTML : '';
      on(input, 'change', function () {
        var files = input.files;
        if (!files || !files.length) { if (text) text.textContent = original; if (icon) icon.innerHTML = originalIcon; return; }
        if (text) text.textContent = files.length > 1 ? files.length + ' file dipilih' : files[0].name;
        var max = parseInt(input.getAttribute('data-max') || '0', 10);
        var tooBig = max && Array.prototype.some.call(files, function (f) { return f.size > max; });
        var field = drop.closest('.field');
        var err = field && $('.field-error[data-client]', field);
        if (tooBig) {
          if (!err && field) { err = document.createElement('p'); err.className = 'field-error'; err.setAttribute('data-client', ''); field.appendChild(err); }
          err.textContent = 'Ukuran file melebihi batas ' + Math.round(max / 1048576) + ' MB.';
          field.classList.add('has-error');
          input.value = '';
          if (text) text.textContent = original;
          return;
        }
        if (err) { err.remove(); field.classList.remove('has-error'); }
        if (icon && files[0].type.indexOf('image/') === 0 && window.URL) {
          var img = new Image();
          img.alt = '';
          img.src = URL.createObjectURL(files[0]);
          icon.innerHTML = '';
          icon.appendChild(img);
        }
      });
      ['dragenter', 'dragover'].forEach(function (ev) { on(drop, ev, function () { drop.classList.add('is-drag'); }); });
      ['dragleave', 'drop'].forEach(function (ev) { on(drop, ev, function () { drop.classList.remove('is-drag'); }); });
    });
  }

  /* Form pendaftaran 3 langkah */
  function stepper() {
    var form = $('[data-stepper]');
    if (!form) return;
    var steps = $$('.step', form), marks = $$('.stepper li', form.parentNode), current = 0;
    function show(i, focus) {
      current = Math.max(0, Math.min(i, steps.length - 1));
      steps.forEach(function (s, k) { s.classList.toggle('is-active', k === current); });
      marks.forEach(function (m, k) {
        m.classList.toggle('is-active', k === current);
        m.classList.toggle('is-done', k < current);
        if (k === current) m.setAttribute('aria-current', 'step'); else m.removeAttribute('aria-current');
      });
      if (current === steps.length - 1) review();
      var head = steps[current].querySelector('legend');
      if (head && focus) { head.setAttribute('tabindex', '-1'); head.focus({ preventScroll: true }); }
      if (focus) {
        var mark = form.parentNode.querySelector('.stepper') || form;
        mark.scrollIntoView({ block: 'start', behavior: 'smooth' });
      }
    }
    function valid(step) {
      var fields = $$('input, select, textarea', step);
      for (var k = 0; k < fields.length; k++) {
        if (!fields[k].checkValidity()) { fields[k].reportValidity(); return false; }
      }
      return true;
    }
    function review() {
      var box = $('[data-review]', form);
      if (!box) return;
      var get = function (n) { var el = form.elements[n]; return el ? el.value : ''; };
      var sel = form.elements.wilayah_id;
      var jk = form.querySelector('input[name=jk]:checked');
      var rows = [
        ['Nama', get('nama')], ['NIK', get('nik').replace(/^(\d{4})\d+(\d{4})$/, '$1••••••••$2')],
        ['Tempat, tgl lahir', get('tempat_lahir') + ', ' + get('tgl_lahir')],
        ['Jenis kelamin', jk ? (jk.value === 'L' ? 'Laki-laki' : 'Perempuan') : '-'],
        ['No. HP', get('telepon')], ['Wilayah', sel && sel.selectedIndex > 0 ? sel.options[sel.selectedIndex].text.replace(/^[—\s]+/, '') : '-']
      ];
      box.innerHTML = '';
      rows.forEach(function (r) {
        var d = document.createElement('div');
        var b = document.createElement('strong'); b.textContent = r[0] + ': ';
        d.appendChild(b); d.appendChild(document.createTextNode(r[1] || '-'));
        box.appendChild(d);
      });
    }
    $$('[data-step-next]', form).forEach(function (b) { on(b, 'click', function () { if (valid(steps[current])) show(current + 1, true); }); });
    $$('[data-step-prev]', form).forEach(function (b) { on(b, 'click', function () { show(current - 1, true); }); });
    on(form, 'submit', function (e) {
      for (var k = 0; k < steps.length; k++) {
        if (!valid(steps[k])) { e.preventDefault(); show(k); valid(steps[k]); return; }
      }
    });
    // Setelah validasi server gagal, buka langkah yang memuat error pertama
    var firstErr = $('.has-error', form);
    var start = 0;
    if (firstErr) steps.forEach(function (s, k) { if (s.contains(firstErr) && !start) start = k; });
    show(start);
    if (firstErr) { var inp = $('input, select, textarea', firstErr); if (inp) setTimeout(function () { inp.focus(); }, 50); }
  }

  /* Editor teks sederhana (contenteditable) -> disaring ulang di server */
  function editors() {
    $$('[data-editor]').forEach(function (wrap) {
      var area = $('.editor__area', wrap), target = document.getElementById(wrap.getAttribute('data-editor'));
      if (!area || !target) return;
      area.innerHTML = target.value;
      target.hidden = true;
      target.required = false; // divalidasi server; textarea tersembunyi tidak bisa difokuskan browser
      area.hidden = false;
      $('.editor__bar', wrap).hidden = false;
      try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) { /* abaikan */ }
      function sync() { target.value = area.innerHTML; }
      on(area, 'input', sync);
      on(area, 'blur', sync);
      on(area, 'paste', function (e) {
        // Tempel sebagai teks polos agar gaya dari sumber lain tidak ikut
        e.preventDefault();
        var t = (e.clipboardData || window.clipboardData).getData('text/plain');
        document.execCommand('insertText', false, t);
      });
      $$('[data-cmd]', wrap).forEach(function (b) {
        on(b, 'mousedown', function (e) { e.preventDefault(); });
        on(b, 'click', function () {
          var cmd = b.getAttribute('data-cmd'), val = b.getAttribute('data-val') || null;
          area.focus();
          if (cmd === 'createLink') {
            val = window.prompt('Alamat tautan (https://…)', 'https://');
            if (!val || !/^(https?:\/\/|mailto:|\/)/i.test(val)) return;
          }
          if (cmd === 'formatBlock') {
            var cur = (document.queryCommandValue('formatBlock') || '').toLowerCase();
            if (cur === val) val = 'p';
            val = '<' + val + '>';
          }
          document.execCommand(cmd, false, val);
          sync(); state();
        });
      });
      function state() {
        $$('[data-cmd]', wrap).forEach(function (b) {
          var cmd = b.getAttribute('data-cmd'), on_ = false;
          try {
            if (cmd === 'formatBlock') on_ = (document.queryCommandValue('formatBlock') || '').toLowerCase() === b.getAttribute('data-val');
            else if (cmd !== 'createLink' && cmd !== 'removeFormat') on_ = document.queryCommandState(cmd);
          } catch (e) { on_ = false; }
          b.classList.toggle('is-on', !!on_);
          b.setAttribute('aria-pressed', on_ ? 'true' : 'false');
        });
      }
      on(document, 'selectionchange', function () { if (document.activeElement === area) state(); });
      var form = wrap.closest('form');
      on(form, 'submit', sync, true);
    });
  }

  function lightbox() {
    var links = $$('[data-lightbox]');
    if (!links.length) return;
    var box = document.createElement('div');
    box.className = 'lightbox';
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.setAttribute('aria-label', 'Pratinjau foto');
    box.innerHTML = '<button type="button" class="btn btn--outline-light btn--icon lightbox__close" aria-label="Tutup">✕</button><img alt=""><p class="lightbox__cap"></p>';
    document.body.appendChild(box);
    var img = $('img', box), cap = $('.lightbox__cap', box), last = null, release = null;
    function close() { box.classList.remove('is-open'); document.body.classList.remove('modal-open'); if (release) release(); if (last) last.focus(); }
    on($('button', box), 'click', close);
    on(box, 'click', function (e) { if (e.target === box) close(); });
    links.forEach(function (a) {
      on(a, 'click', function (e) {
        e.preventDefault();
        last = a;
        img.src = a.href;
        img.alt = a.getAttribute('data-caption') || '';
        cap.textContent = a.getAttribute('data-caption') || '';
        box.classList.add('is-open');
        document.body.classList.add('modal-open');
        $('button', box).focus();
        release = trapEscape(close);
      });
    });
  }

  /* Tooltip grafik batang */
  function charts() {
    $$('.chart').forEach(function (chart) {
      var tip = $('.chart__tip', chart);
      if (!tip) return;
      function show(g) {
        var r = g.querySelector('.bar-hit').getBoundingClientRect(), c = chart.getBoundingClientRect();
        var bar = g.querySelector('.bar');
        var br = bar ? bar.getBoundingClientRect() : r;
        tip.innerHTML = '';
        var s = document.createElement('strong'); s.textContent = g.getAttribute('data-value') + ' pendaftar';
        var t = document.createElement('div'); t.textContent = g.getAttribute('data-label');
        tip.appendChild(s); tip.appendChild(t);
        var x = r.left + r.width / 2 - c.left;
        x = Math.max(70, Math.min(c.width - 70, x));
        tip.style.left = x + 'px';
        tip.style.top = (Math.min(br.top, r.bottom - 4) - c.top) + 'px';
        tip.classList.add('is-on');
      }
      function hide() { tip.classList.remove('is-on'); }
      $$('.bar-group', chart).forEach(function (g) {
        on(g, 'mouseenter', function () { show(g); });
        on(g, 'focus', function () { show(g); });
        on(g, 'mouseleave', hide);
        on(g, 'blur', hide);
      });
    });
  }

  function autosubmit() {
    $$('[data-autosubmit]').forEach(function (el) {
      on(el, 'change', function () { if (el.form) { if (el.form.requestSubmit) el.form.requestSubmit(); else el.form.submit(); } });
    });
  }

  function counters() {
    $$('[data-count]').forEach(function (el) {
      var out = document.getElementById(el.getAttribute('data-count'));
      var max = parseInt(el.getAttribute('maxlength') || '0', 10);
      if (!out || !max) return;
      var upd = function () { out.textContent = el.value.length + '/' + max; };
      on(el, 'input', upd);
      upd();
    });
  }

  /* Formulir verifikasi anggota: alasan wajib bila ditolak */
  function statusForms() {
    $$('[data-status-form]').forEach(function (f) {
      var note = f.querySelector('textarea[name=catatan]');
      $$('button[name=status]', f).forEach(function (b) {
        on(b, 'click', function () {
          if (!note) return;
          note.required = b.value === 'ditolak';
        });
      });
    });
  }
})();
