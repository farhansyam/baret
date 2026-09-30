<?php
$isEdit = $n !== null;
$v = fn(string $k) => old($k, $n[$k] ?? '');
$cats = array_column($categories, 'nama', 'id');
$errIsi = error('isi');
?>
<?= partial('admin-head', ['heading' => $isEdit ? 'Ubah Berita' : 'Tulis Berita', 'crumbs' => [['Dashboard', '/admin'], ['Berita', '/admin/berita'], [$isEdit ? 'Ubah' : 'Tulis', null]],
    'actions' => $isEdit && $n['status'] === 'publish' ? '<a class="btn btn--outline" href="' . url('/berita/' . $n['slug']) . '" target="_blank" rel="noopener">' . icon('external', 'icon-sm') . ' Lihat di website</a>' : '']) ?>

<form method="post" action="<?= $isEdit ? url('/admin/berita/' . $n['id']) : url('/admin/berita') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="detail-grid">
    <section class="card">
      <div class="card__body form-grid">
        <?= partial('field', ['name' => 'judul', 'label' => 'Judul', 'value' => $v('judul'), 'required' => true, 'attrs' => 'maxlength="200" minlength="5"']) ?>
        <?= partial('field', ['name' => 'ringkasan', 'label' => 'Ringkasan', 'type' => 'textarea', 'value' => $v('ringkasan'), 'optional' => true, 'hint' => 'Tampil di kartu berita & mesin pencari. Maks. 300 karakter.', 'attrs' => 'rows="2" maxlength="300" data-count="ringkasan-count"']) ?>
        <p class="hint" id="ringkasan-count" aria-live="polite"></p>
        <div class="field<?= $errIsi ? ' has-error' : '' ?>">
          <span class="label">Isi berita<span class="req" aria-hidden="true">*</span></span>
          <div class="editor" data-editor="isi">
            <div class="editor__bar" role="toolbar" aria-label="Format teks" hidden>
              <button type="button" data-cmd="bold" title="Tebal (Ctrl+B)" aria-label="Tebal"><strong>B</strong></button>
              <button type="button" data-cmd="italic" title="Miring (Ctrl+I)" aria-label="Miring"><em>I</em></button>
              <button type="button" data-cmd="underline" title="Garis bawah" aria-label="Garis bawah"><u>U</u></button>
              <span class="sep"></span>
              <button type="button" data-cmd="formatBlock" data-val="h2" title="Subjudul" aria-label="Subjudul">H2</button>
              <button type="button" data-cmd="formatBlock" data-val="h3" title="Sub-subjudul" aria-label="Sub-subjudul">H3</button>
              <button type="button" data-cmd="formatBlock" data-val="blockquote" title="Kutipan" aria-label="Kutipan">❝</button>
              <span class="sep"></span>
              <button type="button" data-cmd="insertUnorderedList" title="Daftar poin" aria-label="Daftar poin">• List</button>
              <button type="button" data-cmd="insertOrderedList" title="Daftar bernomor" aria-label="Daftar bernomor">1. List</button>
              <span class="sep"></span>
              <button type="button" data-cmd="createLink" title="Sisipkan tautan" aria-label="Sisipkan tautan">Link</button>
              <button type="button" data-cmd="unlink" title="Hapus tautan" aria-label="Hapus tautan">Unlink</button>
              <button type="button" data-cmd="removeFormat" title="Hapus format" aria-label="Hapus format">Tx</button>
            </div>
            <div class="editor__area prose" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Isi berita" data-placeholder="Tulis isi berita di sini…" hidden></div>
          </div>
          <textarea class="textarea" id="isi" name="isi" rows="14" required aria-label="Isi berita"><?= e($v('isi')) ?></textarea>
          <p class="hint">Tempelan dari Word/web otomatis menjadi teks polos. HTML disaring ulang oleh server.</p>
          <?php if ($errIsi): ?><p class="field-error"><?= icon('alert') ?><?= e($errIsi) ?></p><?php endif; ?>
        </div>
      </div>
    </section>

    <div class="stack">
      <section class="card">
        <div class="card__head"><h2>Publikasi</h2></div>
        <div class="card__body form-grid">
          <?= partial('field', ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft (tidak tampil publik)', 'publish' => 'Publish (tampil di website)'], 'value' => $v('status') ?: 'draft', 'required' => true]) ?>
          <?= partial('field', ['name' => 'published_at', 'label' => 'Tanggal terbit', 'type' => 'datetime-local', 'value' => dt_local($v('published_at') ?: null), 'optional' => true, 'hint' => 'Kosongkan untuk waktu saat diterbitkan. Isi tanggal mendatang untuk menjadwalkan.']) ?>
          <?= partial('field', ['name' => 'kategori_id', 'label' => 'Kategori', 'type' => 'select', 'options' => $cats, 'value' => $v('kategori_id'), 'required' => true]) ?>
        </div>
      </section>
      <section class="card">
        <div class="card__head"><h2>Gambar Utama</h2></div>
        <div class="card__body stack">
          <?php if ($isEdit && $n['gambar']): ?>
            <div class="current-file">
              <img src="<?= e(upload_url('news', $n['gambar'])) ?>" alt="Gambar saat ini">
              <label class="check"><input type="checkbox" name="hapus_gambar" value="1"> Hapus gambar</label>
            </div>
          <?php endif; ?>
          <?= partial('file', ['name' => 'gambar', 'label' => $isEdit && $n['gambar'] ? 'Ganti gambar' : 'Unggah gambar', 'optional' => true, 'accept' => 'image/jpeg,image/png,image/webp', 'icon' => 'image', 'hint' => 'Rasio 16:9 disarankan. Dikompres otomatis ke WebP.', 'max' => (int) config('upload.max_image')]) ?>
        </div>
      </section>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--block"><?= icon('check', 'icon-sm') ?> <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Berita' ?></button>
        <a class="btn btn--ghost btn--block" href="<?= url('/admin/berita') ?>">Batal</a>
      </div>
    </div>
  </div>
</form>
