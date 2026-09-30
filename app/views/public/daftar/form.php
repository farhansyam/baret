<?php
$opts = [];
foreach ($wilayahOptions as $o) {
    $opts[$o['id']] = $o['label'];
}
$maxImg = (int) config('upload.max_image');
?>
<?= partial('page-hero', ['heading' => 'Pendaftaran Anggota', 'lead' => 'Isi formulir dalam 3 langkah. Data Anda akan diverifikasi oleh pengurus sebelum Nomor Induk Anggota (NIA) diterbitkan.', 'crumbs' => [['Pendaftaran Anggota', null]]]) ?>

<section class="section">
  <div class="container form-card">
    <ol class="stepper" aria-label="Langkah pendaftaran">
      <li class="is-active"><span>Data Diri</span></li>
      <li><span>Kontak &amp; Wilayah</span></li>
      <li><span>Foto &amp; Persetujuan</span></li>
    </ol>

    <form class="card card__body" method="post" action="<?= url('/daftar') ?>" enctype="multipart/form-data" data-stepper novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="_ts" value="<?= time() ?>">
      <div class="hp-field" aria-hidden="true"><label for="website">Website</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>

      <?php if (errors()): ?>
        <div class="alert alert--error mb-6" role="alert"><?= icon('alert') ?><div><strong>Pendaftaran belum dapat dikirim.</strong> Periksa kembali isian yang ditandai merah. Demi keamanan, file foto perlu dipilih ulang.</div></div>
      <?php endif; ?>

      <fieldset class="fieldset step" data-step="1">
        <legend>1. Data Diri</legend>
        <div class="form-grid form-grid--2">
          <?= partial('field', ['name' => 'nama', 'label' => 'Nama lengkap (sesuai KTP)', 'value' => old('nama'), 'required' => true, 'attrs' => 'maxlength="120" autocomplete="name"', 'class' => 'span-all']) ?>
          <?= partial('field', ['name' => 'nik', 'label' => 'NIK', 'value' => old('nik'), 'required' => true, 'hint' => '16 digit angka sesuai KTP. Disimpan terenkripsi dan tidak pernah ditampilkan publik.', 'attrs' => 'inputmode="numeric" pattern="\d{16}" maxlength="16" minlength="16" autocomplete="off"', 'class' => 'span-all']) ?>
          <?= partial('field', ['name' => 'tempat_lahir', 'label' => 'Tempat lahir', 'value' => old('tempat_lahir'), 'required' => true, 'attrs' => 'maxlength="80"']) ?>
          <?= partial('field', ['name' => 'tgl_lahir', 'label' => 'Tanggal lahir', 'type' => 'date', 'value' => old('tgl_lahir'), 'required' => true, 'attrs' => 'max="' . date('Y-m-d', strtotime('-1 day')) . '" autocomplete="bday"']) ?>
          <div class="field<?= error('jk') ? ' has-error' : '' ?>">
            <span class="label" id="jk-label">Jenis kelamin<span class="req" aria-hidden="true">*</span></span>
            <div class="choice-row" role="radiogroup" aria-labelledby="jk-label">
              <label class="choice"><input type="radio" name="jk" value="L" required<?= checked(old('jk') === 'L') ?>> Laki-laki</label>
              <label class="choice"><input type="radio" name="jk" value="P"<?= checked(old('jk') === 'P') ?>> Perempuan</label>
            </div>
            <?php if ($e = error('jk')): ?><p class="field-error"><?= icon('alert') ?><?= e($e) ?></p><?php endif; ?>
          </div>
          <?= partial('field', ['name' => 'pekerjaan', 'label' => 'Pekerjaan', 'value' => old('pekerjaan'), 'optional' => true, 'attrs' => 'maxlength="80"']) ?>
        </div>
        <div class="step-nav">
          <span></span>
          <button type="button" class="btn btn--primary" data-step-next>Lanjut <?= icon('arrow-right', 'icon-sm') ?></button>
        </div>
      </fieldset>

      <fieldset class="fieldset step" data-step="2">
        <legend>2. Kontak &amp; Wilayah</legend>
        <div class="form-grid form-grid--2">
          <?= partial('field', ['name' => 'telepon', 'label' => 'Nomor HP/WhatsApp', 'type' => 'tel', 'value' => old('telepon'), 'required' => true, 'placeholder' => '08xxxxxxxxxx', 'attrs' => 'inputmode="tel" maxlength="16" autocomplete="tel"']) ?>
          <?= partial('field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => old('email'), 'optional' => true, 'attrs' => 'maxlength="160" autocomplete="email"']) ?>
          <?= partial('field', ['name' => 'alamat', 'label' => 'Alamat lengkap', 'type' => 'textarea', 'value' => old('alamat'), 'required' => true, 'hint' => 'Jalan, RT/RW, kelurahan, kecamatan, kota/kabupaten.', 'attrs' => 'rows="3" minlength="10" maxlength="500" autocomplete="street-address"', 'class' => 'span-all']) ?>
          <?= partial('field', ['name' => 'wilayah_id', 'label' => 'Wilayah/Cabang yang dipilih', 'type' => 'select', 'options' => $opts, 'value' => old('wilayah_id'), 'required' => true, 'placeholder' => '— Pilih wilayah/cabang —', 'hint' => 'Pilih cabang terdekat dengan domisili Anda.', 'class' => 'span-all']) ?>
        </div>
        <div class="step-nav">
          <button type="button" class="btn btn--ghost" data-step-prev><?= icon('arrow-left', 'icon-sm') ?> Kembali</button>
          <button type="button" class="btn btn--primary" data-step-next>Lanjut <?= icon('arrow-right', 'icon-sm') ?></button>
        </div>
      </fieldset>

      <fieldset class="fieldset step" data-step="3">
        <legend>3. Foto &amp; Persetujuan</legend>
        <div class="form-grid form-grid--2">
          <?= partial('file', ['name' => 'foto', 'label' => 'Pas foto', 'required' => true, 'accept' => 'image/jpeg,image/png,image/webp', 'icon' => 'user', 'hint' => 'JPG/PNG/WebP, maks. ' . App\Core\Upload::human($maxImg) . '. Wajah terlihat jelas.', 'max' => $maxImg]) ?>
          <?= partial('file', ['name' => 'ktp', 'label' => 'Foto KTP', 'optional' => true, 'accept' => 'image/jpeg,image/png,image/webp', 'icon' => 'id-card', 'hint' => 'Opsional. Hanya dapat dilihat pengurus berwenang.', 'max' => $maxImg]) ?>
        </div>
        <div class="review mt-6" data-review aria-live="polite"></div>
        <div class="field mt-6<?= error('persetujuan') ? ' has-error' : '' ?>">
          <label class="check">
            <input type="checkbox" name="persetujuan" value="1" required<?= checked((bool) old('persetujuan')) ?>>
            <span>Saya menyatakan data yang saya isi benar, dan <strong>menyetujui penggunaan data pribadi</strong> saya oleh BARET untuk keperluan administrasi keanggotaan sesuai UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi. Data tidak akan dibagikan kepada pihak lain tanpa persetujuan saya.</span>
          </label>
          <?php if ($e = error('persetujuan')): ?><p class="field-error"><?= icon('alert') ?><?= e($e) ?></p><?php endif; ?>
        </div>
        <div class="step-nav">
          <button type="button" class="btn btn--ghost" data-step-prev><?= icon('arrow-left', 'icon-sm') ?> Kembali</button>
          <button type="submit" class="btn btn--primary"><?= icon('send', 'icon-sm') ?> Kirim Pendaftaran</button>
        </div>
      </fieldset>
    </form>

    <p class="text-sm text-muted text-center mt-6">Sudah mendaftar? <a href="<?= url('/daftar/status') ?>">Cek status pendaftaran</a></p>
  </div>
</section>
