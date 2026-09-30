<?php
$isEdit = $m !== null;
$v = fn(string $k) => old($k, $m[$k] ?? '');
$opts = [];
foreach ($wilayahOptions as $o) {
    $opts[$o['id']] = $o['label'];
}
$maxImg = (int) config('upload.max_image');
$action = $isEdit ? url('/admin/anggota/' . $m['id']) : url('/admin/anggota');
?>
<?= partial('admin-head', ['heading' => $isEdit ? 'Ubah Anggota' : 'Tambah Anggota', 'crumbs' => [['Dashboard', '/admin'], ['Anggota', '/admin/anggota'], [$isEdit ? $m['nama'] : 'Tambah', $isEdit ? '/admin/anggota/' . $m['id'] : null]]]) ?>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <section class="card section-card">
    <div class="card__head"><h2>Data Diri</h2></div>
    <div class="card__body form-grid form-grid--2">
      <?= partial('field', ['name' => 'nama', 'label' => 'Nama lengkap', 'value' => $v('nama'), 'required' => true, 'attrs' => 'maxlength="120"', 'class' => 'span-all']) ?>
      <?= partial('field', ['name' => 'nik', 'label' => 'NIK', 'value' => $v('nik'), 'required' => true, 'hint' => '16 digit. Disimpan terenkripsi.', 'attrs' => 'inputmode="numeric" pattern="\d{16}" maxlength="16" autocomplete="off"']) ?>
      <?= partial('field', ['name' => 'jk', 'label' => 'Jenis kelamin', 'type' => 'select', 'options' => ['L' => 'Laki-laki', 'P' => 'Perempuan'], 'value' => $v('jk'), 'required' => true]) ?>
      <?= partial('field', ['name' => 'tempat_lahir', 'label' => 'Tempat lahir', 'value' => $v('tempat_lahir'), 'required' => true, 'attrs' => 'maxlength="80"']) ?>
      <?= partial('field', ['name' => 'tgl_lahir', 'label' => 'Tanggal lahir', 'type' => 'date', 'value' => $v('tgl_lahir'), 'required' => true, 'attrs' => 'max="' . date('Y-m-d', strtotime('-1 day')) . '"']) ?>
      <?= partial('field', ['name' => 'pekerjaan', 'label' => 'Pekerjaan', 'value' => $v('pekerjaan'), 'optional' => true, 'attrs' => 'maxlength="80"']) ?>
    </div>
  </section>

  <section class="card section-card">
    <div class="card__head"><h2>Kontak &amp; Wilayah</h2></div>
    <div class="card__body form-grid form-grid--2">
      <?= partial('field', ['name' => 'telepon', 'label' => 'No. HP/WhatsApp', 'type' => 'tel', 'value' => $v('telepon'), 'required' => true, 'attrs' => 'maxlength="16"']) ?>
      <?= partial('field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $v('email'), 'optional' => true, 'attrs' => 'maxlength="160"']) ?>
      <?= partial('field', ['name' => 'alamat', 'label' => 'Alamat lengkap', 'type' => 'textarea', 'value' => $v('alamat'), 'required' => true, 'attrs' => 'rows="3" maxlength="500"', 'class' => 'span-all']) ?>
      <?= partial('field', ['name' => 'wilayah_id', 'label' => 'Wilayah/Cabang', 'type' => 'select', 'options' => $opts, 'value' => $v('wilayah_id'), 'required' => true, 'class' => 'span-all', 'hint' => $isEdit && $m['nia'] ? 'NIA tidak berubah walaupun wilayah dipindahkan.' : null]) ?>
    </div>
  </section>

  <section class="card section-card">
    <div class="card__head"><h2>Berkas</h2></div>
    <div class="card__body form-grid form-grid--2">
      <div class="stack">
        <?php if ($isEdit && $m['foto']): ?><div class="current-file"><img src="<?= url('/admin/file/members/' . $m['foto']) ?>" alt="Pas foto saat ini"><span>Foto saat ini. Unggah file baru untuk mengganti.</span></div><?php endif; ?>
        <?= partial('file', ['name' => 'foto', 'label' => 'Pas foto', 'optional' => true, 'accept' => 'image/jpeg,image/png,image/webp', 'icon' => 'user', 'hint' => 'JPG/PNG/WebP, maks. ' . App\Core\Upload::human($maxImg), 'max' => $maxImg]) ?>
      </div>
      <div class="stack">
        <?php if ($isEdit && $m['ktp_file']): ?><div class="current-file"><?= icon('id-card') ?><a href="<?= url('/admin/file/ktp/' . $m['ktp_file']) ?>" target="_blank" rel="noopener">Lihat KTP saat ini</a></div><?php endif; ?>
        <?= partial('file', ['name' => 'ktp', 'label' => 'Foto KTP', 'optional' => true, 'accept' => 'image/jpeg,image/png,image/webp', 'icon' => 'id-card', 'hint' => 'Hanya dapat dilihat pengurus berwenang.', 'max' => $maxImg]) ?>
      </div>
      <?php if (!$isEdit && can('members.verify')): ?>
        <label class="check span-all"><input type="checkbox" name="langsung_aktif" value="1"<?= checked((bool) old('langsung_aktif')) ?>><span><strong>Setujui langsung</strong> — data sudah diverifikasi, terbitkan NIA sekarang.</span></label>
      <?php endif; ?>
    </div>
  </section>

  <div class="form-actions form-actions--end mt-6">
    <a class="btn btn--ghost" href="<?= $isEdit ? url('/admin/anggota/' . $m['id']) : url('/admin/anggota') ?>">Batal</a>
    <button type="submit" class="btn btn--primary"><?= icon('check', 'icon-sm') ?> <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Anggota' ?></button>
  </div>
</form>
