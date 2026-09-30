<?php
use App\Core\Auth;
$isEdit = $e !== null;
$v = fn(string $k) => old($k, $e[$k] ?? '');
$wOpts = [];
foreach ($wilayahOptions as $o) {
    $wOpts[$o['id']] = $o['label'];
}
?>
<?= partial('admin-head', ['heading' => $isEdit ? 'Ubah Kegiatan' : 'Tambah Kegiatan', 'crumbs' => [['Dashboard', '/admin'], ['Kegiatan', '/admin/kegiatan'], [$isEdit ? 'Ubah' : 'Tambah', null]],
    'actions' => $isEdit && $e['status'] === 'publish' ? '<a class="btn btn--outline" href="' . url('/kegiatan/' . $e['slug']) . '" target="_blank" rel="noopener">' . icon('external', 'icon-sm') . ' Lihat di website</a>' : '']) ?>

<form method="post" action="<?= $isEdit ? url('/admin/kegiatan/' . $e['id']) : url('/admin/kegiatan') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="detail-grid">
    <section class="card">
      <div class="card__body form-grid form-grid--2">
        <?= partial('field', ['name' => 'judul', 'label' => 'Judul kegiatan', 'value' => $v('judul'), 'required' => true, 'attrs' => 'maxlength="200" minlength="5"', 'class' => 'span-all']) ?>
        <?= partial('field', ['name' => 'mulai', 'label' => 'Waktu mulai', 'type' => 'datetime-local', 'value' => dt_local($v('mulai') ?: null), 'required' => true]) ?>
        <?= partial('field', ['name' => 'selesai', 'label' => 'Waktu selesai', 'type' => 'datetime-local', 'value' => dt_local($v('selesai') ?: null), 'required' => true, 'hint' => 'Tidak boleh lebih awal dari waktu mulai.']) ?>
        <?= partial('field', ['name' => 'lokasi', 'label' => 'Lokasi', 'value' => $v('lokasi'), 'required' => true, 'attrs' => 'maxlength="160"', 'class' => 'span-all']) ?>
        <?= partial('field', ['name' => 'deskripsi', 'label' => 'Deskripsi', 'type' => 'textarea', 'value' => $v('deskripsi'), 'required' => true, 'attrs' => 'rows="8" minlength="20" maxlength="10000"', 'class' => 'span-all']) ?>
      </div>
    </section>
    <div class="stack">
      <section class="card">
        <div class="card__head"><h2>Publikasi</h2></div>
        <div class="card__body form-grid">
          <?= partial('field', ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft (tidak tampil publik)', 'publish' => 'Publish (tampil di website)'], 'value' => $v('status') ?: 'draft', 'required' => true]) ?>
          <?= partial('field', ['name' => 'wilayah_id', 'label' => 'Penyelenggara', 'type' => 'select', 'options' => $wOpts, 'value' => $v('wilayah_id'), 'required' => Auth::isScoped(), 'placeholder' => Auth::isScoped() ? '— Pilih wilayah —' : '— Pusat / umum —']) ?>
          <?= partial('field', ['name' => 'penanggung_jawab', 'label' => 'Penanggung jawab', 'value' => $v('penanggung_jawab'), 'optional' => true, 'attrs' => 'maxlength="120"']) ?>
        </div>
      </section>
      <section class="card">
        <div class="card__head"><h2>Gambar Sampul</h2></div>
        <div class="card__body stack">
          <?php if ($isEdit && $e['gambar']): ?>
            <div class="current-file"><img src="<?= e(upload_url('events', $e['gambar'])) ?>" alt="Sampul saat ini"><label class="check"><input type="checkbox" name="hapus_gambar" value="1"> Hapus gambar</label></div>
          <?php endif; ?>
          <?= partial('file', ['name' => 'gambar', 'label' => 'Unggah sampul', 'optional' => true, 'accept' => 'image/jpeg,image/png,image/webp', 'icon' => 'image', 'hint' => 'Dikompres otomatis ke WebP.', 'max' => (int) config('upload.max_image')]) ?>
        </div>
      </section>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary btn--block"><?= icon('check', 'icon-sm') ?> <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Kegiatan' ?></button>
        <a class="btn btn--ghost btn--block" href="<?= url('/admin/kegiatan') ?>">Batal</a>
      </div>
    </div>
  </div>
</form>

<?php if ($isEdit): ?>
<section class="card mt-6" id="galeri">
  <div class="card__head"><h2>Galeri Dokumentasi</h2><span class="text-sm text-muted"><?= count($photos) ?> foto</span></div>
  <div class="card__body stack">
    <?php if ($photos): ?>
      <div class="photo-grid">
        <?php foreach ($photos as $p): ?>
          <figure class="photo-tile">
            <img src="<?= e(upload_url('events', $p['file'])) ?>" alt="<?= e($p['caption'] ?? 'Foto kegiatan') ?>" loading="lazy" width="200" height="150">
            <form method="post" action="<?= url('/admin/kegiatan/foto/' . $p['id'] . '/hapus') ?>" data-confirm="Hapus foto ini dari galeri?" data-confirm-title="Hapus foto" data-confirm-ok="Hapus">
              <?= csrf_field() ?><button class="btn btn--sm btn--icon btn--danger" type="submit" aria-label="Hapus foto" title="Hapus"><?= icon('trash', 'icon-sm') ?></button>
            </form>
            <figcaption><?= e($p['caption'] ?: 'Tanpa keterangan') ?></figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-muted mb-0">Belum ada foto dokumentasi.</p>
    <?php endif; ?>
    <form class="form-grid form-grid--2" method="post" action="<?= url('/admin/kegiatan/' . $e['id'] . '/foto') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <?= partial('file', ['name' => 'foto', 'label' => 'Tambah foto', 'multiple' => true, 'required' => true, 'accept' => 'image/jpeg,image/png,image/webp', 'icon' => 'image', 'hint' => 'Bisa pilih beberapa sekaligus (maks. 12).', 'cta' => 'Pilih foto', 'max' => (int) config('upload.max_image')]) ?>
      <div class="stack">
        <?= partial('field', ['name' => 'caption', 'label' => 'Keterangan', 'value' => '', 'optional' => true, 'attrs' => 'maxlength="160"']) ?>
        <button class="btn btn--dark" type="submit"><?= icon('upload', 'icon-sm') ?> Unggah ke Galeri</button>
      </div>
    </form>
  </div>
</section>
<?php endif; ?>
