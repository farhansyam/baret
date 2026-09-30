<?= partial('admin-head', ['heading' => 'Akun Saya', 'crumbs' => [['Dashboard', '/admin'], ['Akun', null]]]) ?>

<div class="detail-grid">
  <form class="card" method="post" action="<?= url('/admin/akun') ?>">
    <?= csrf_field() ?>
    <div class="card__head"><h2>Profil</h2><span class="badge badge--muted"><?= e($u['role_nama']) ?></span></div>
    <div class="card__body form-grid form-grid--2">
      <?= partial('field', ['name' => 'nama', 'label' => 'Nama', 'value' => old('nama', $u['nama']), 'required' => true, 'attrs' => 'maxlength="120"']) ?>
      <?= partial('field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => old('email', $u['email']), 'required' => true, 'attrs' => 'maxlength="160"']) ?>
      <p class="text-sm text-muted span-all mb-0">Login terakhir: <?= tanggal($u['last_login'], true) ?><?= $u['wilayah_nama'] ? ' · Wilayah: ' . e($u['wilayah_nama']) : '' ?></p>
    </div>
    <div class="card__foot form-actions form-actions--end"><button class="btn btn--primary" type="submit"><?= icon('check', 'icon-sm') ?> Simpan Profil</button></div>
  </form>

  <form class="card" id="sandi" method="post" action="<?= url('/admin/akun/sandi') ?>">
    <?= csrf_field() ?>
    <div class="card__head"><h2>Ubah Kata Sandi</h2></div>
    <div class="card__body form-grid">
      <?= partial('field', ['name' => 'password_lama', 'label' => 'Kata sandi lama', 'type' => 'password', 'required' => true, 'attrs' => 'autocomplete="current-password"']) ?>
      <?= partial('field', ['name' => 'password', 'label' => 'Kata sandi baru', 'type' => 'password', 'required' => true, 'hint' => 'Min. 8 karakter, huruf dan angka.', 'attrs' => 'autocomplete="new-password" minlength="8"']) ?>
      <?= partial('field', ['name' => 'password_confirmation', 'label' => 'Ulangi kata sandi baru', 'type' => 'password', 'required' => true, 'attrs' => 'autocomplete="new-password" minlength="8"']) ?>
      <button class="btn btn--outline" type="submit"><?= icon('key', 'icon-sm') ?> Ubah Kata Sandi</button>
    </div>
  </form>
</div>
