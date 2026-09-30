<?php
use App\Controllers\Admin\SettingController as S;
$F = S::FIELDS;
$v = fn(string $k) => old($k, $values[$k] ?? '');
$f = function (string $k, string $type = 'text', array $extra = []) use ($F, $v) {
    $rules = is_array($F[$k][1]) ? $F[$k][1] : explode('|', $F[$k][1]);
    return partial('field', array_merge([
        'name' => $k, 'label' => $F[$k][0], 'type' => $type, 'value' => $v($k),
        'required' => in_array('required', $rules, true), 'optional' => in_array('nullable', $rules, true),
    ], $extra));
};
?>
<?= partial('admin-head', ['heading' => 'Pengaturan Situs', 'crumbs' => [['Dashboard', '/admin'], ['Pengaturan', null]]]) ?>

<form method="post" action="<?= url('/admin/pengaturan') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <section class="card section-card">
    <div class="card__head"><h2>Identitas &amp; Kontak</h2></div>
    <div class="card__body form-grid form-grid--2">
      <?= $f('nama_organisasi') ?>
      <?= $f('singkatan') ?>
      <?= $f('tagline', 'text', ['class' => 'span-all']) ?>
      <?= $f('alamat', 'textarea', ['attrs' => 'rows="2"', 'class' => 'span-all']) ?>
      <?= $f('telepon') ?>
      <?= $f('whatsapp', 'text', ['hint' => 'Contoh: 6281234567890']) ?>
      <?= $f('email', 'email') ?>
      <?= $f('instagram', 'url', ['placeholder' => 'https://instagram.com/…']) ?>
      <?= $f('facebook', 'url', ['placeholder' => 'https://facebook.com/…']) ?>
      <?= $f('youtube', 'url', ['placeholder' => 'https://youtube.com/…']) ?>
      <?= $f('map_lat', 'text', ['hint' => 'Koordinat sekretariat untuk peta di halaman Kontak.']) ?>
      <?= $f('map_lng') ?>
    </div>
  </section>

  <section class="card section-card">
    <div class="card__head"><h2>Beranda &amp; Profil</h2></div>
    <div class="card__body form-grid form-grid--2">
      <?= $f('nama_ketua') ?>
      <div></div>
      <?= $f('sambutan_ketua', 'textarea', ['attrs' => 'rows="4"', 'class' => 'span-all']) ?>
      <?= $f('sejarah', 'textarea', ['attrs' => 'rows="6"', 'class' => 'span-all']) ?>
      <?= $f('visi', 'textarea', ['attrs' => 'rows="2"', 'class' => 'span-all']) ?>
      <?= $f('misi', 'textarea', ['attrs' => 'rows="5"', 'class' => 'span-all']) ?>
      <?= $f('nilai', 'textarea', ['attrs' => 'rows="5"', 'hint' => 'Satu nilai per baris, format: Judul|Deskripsi', 'class' => 'span-all']) ?>
      <?= $f('legalitas', 'textarea', ['attrs' => 'rows="3"', 'class' => 'span-all']) ?>
      <div class="span-all stack">
        <?php if (!empty($values['ad_art_file'])): ?>
          <div class="current-file"><?= icon('file-text') ?><a href="<?= e(upload_url('docs', $values['ad_art_file'])) ?>" target="_blank" rel="noopener">AD/ART saat ini (PDF)</a><label class="check"><input type="checkbox" name="hapus_ad_art" value="1"> Hapus</label></div>
        <?php endif; ?>
        <?= partial('file', ['name' => 'ad_art_file', 'label' => 'File AD/ART (publik, dapat diunduh)', 'optional' => true, 'accept' => 'application/pdf', 'icon' => 'file-text', 'hint' => 'PDF, maks. ' . App\Core\Upload::human((int) config('upload.max_doc')), 'max' => (int) config('upload.max_doc')]) ?>
      </div>
    </div>
  </section>

  <div class="form-actions form-actions--end mt-6">
    <button type="submit" class="btn btn--primary"><?= icon('check', 'icon-sm') ?> Simpan Pengaturan</button>
  </div>
</form>
