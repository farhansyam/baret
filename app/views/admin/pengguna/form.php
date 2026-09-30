<?php
use App\Core\Auth;
$isEdit = $u !== null;
$v = fn(string $k) => old($k, $u[$k] ?? '');
$roleOpts = array_column($roles, 'nama', 'id');
$wOpts = [];
foreach ($wilayahOptions as $o) {
    $wOpts[$o['id']] = $o['label'];
}
$mOpts = [];
foreach ($members as $m) {
    if ($m['user_id'] && (!$isEdit || (int) $m['user_id'] !== (int) $u['id'])) continue;
    $mOpts[$m['id']] = $m['nama'] . ' — ' . $m['nia'];
}
$self = $isEdit && (int) $u['id'] === Auth::id();
?>
<?= partial('admin-head', ['heading' => $isEdit ? 'Ubah Pengguna' : 'Tambah Pengguna', 'crumbs' => [['Dashboard', '/admin'], ['Pengguna', '/admin/pengguna'], [$isEdit ? $u['email'] : 'Tambah', null]]]) ?>

<div class="detail-grid">
  <form class="card" method="post" action="<?= $isEdit ? url('/admin/pengguna/' . $u['id']) : url('/admin/pengguna') ?>">
    <?= csrf_field() ?>
    <div class="card__head"><h2>Data Akun</h2></div>
    <div class="card__body form-grid form-grid--2">
      <?= partial('field', ['name' => 'nama', 'label' => 'Nama', 'value' => $v('nama'), 'required' => true, 'attrs' => 'maxlength="120"']) ?>
      <?= partial('field', ['name' => 'email', 'label' => 'Email (untuk login)', 'type' => 'email', 'value' => $v('email'), 'required' => true, 'attrs' => 'maxlength="160" autocomplete="off"']) ?>
      <?= partial('field', ['name' => 'role_id', 'label' => 'Peran', 'type' => 'select', 'options' => $roleOpts, 'value' => $v('role_id'), 'required' => true, 'attrs' => $self ? 'disabled' : '', 'hint' => $self ? 'Anda tidak dapat mengubah peran akun sendiri.' : null]) ?>
      <?php if ($self): ?><input type="hidden" name="role_id" value="<?= (int) $u['role_id'] ?>"><?php endif; ?>
      <?= partial('field', ['name' => 'wilayah_id', 'label' => 'Wilayah', 'type' => 'select', 'options' => $wOpts, 'value' => $v('wilayah_id'), 'placeholder' => '— Tidak dibatasi —', 'hint' => 'Wajib untuk Pengurus Wilayah/Cabang: data dibatasi ke wilayah ini dan turunannya.']) ?>
      <?= partial('field', ['name' => 'member_id', 'label' => 'Tautkan ke data anggota', 'type' => 'select', 'options' => $mOpts, 'value' => $v('member_id'), 'placeholder' => '— Tidak ditautkan —', 'hint' => 'Wajib untuk peran Anggota (hanya anggota aktif yang belum punya akun).', 'class' => 'span-all']) ?>
      <?php if (!$isEdit): ?>
        <?= partial('field', ['name' => 'password', 'label' => 'Kata sandi awal', 'type' => 'password', 'required' => true, 'hint' => 'Min. 8 karakter, huruf dan angka.', 'attrs' => 'autocomplete="new-password" minlength="8"']) ?>
        <?= partial('field', ['name' => 'password_confirmation', 'label' => 'Ulangi kata sandi', 'type' => 'password', 'required' => true, 'attrs' => 'autocomplete="new-password" minlength="8"']) ?>
      <?php endif; ?>
    </div>
    <div class="card__foot form-actions form-actions--end">
      <a class="btn btn--ghost" href="<?= url('/admin/pengguna') ?>">Batal</a>
      <button type="submit" class="btn btn--primary"><?= icon('check', 'icon-sm') ?> Simpan</button>
    </div>
  </form>

  <?php if ($isEdit): ?>
  <form class="card" id="sandi" method="post" action="<?= url('/admin/pengguna/' . $u['id'] . '/sandi') ?>" data-confirm="Atur ulang kata sandi <?= e($u['email']) ?>? Kata sandi lama tidak berlaku lagi." data-confirm-title="Reset kata sandi" data-confirm-ok="Reset" data-confirm-tone="primary">
    <?= csrf_field() ?>
    <div class="card__head"><h2>Reset Kata Sandi</h2></div>
    <div class="card__body form-grid">
      <?= partial('field', ['name' => 'password', 'id' => 'reset-password', 'label' => 'Kata sandi baru', 'type' => 'password', 'required' => true, 'attrs' => 'autocomplete="new-password" minlength="8"']) ?>
      <?= partial('field', ['name' => 'password_confirmation', 'id' => 'reset-password2', 'label' => 'Ulangi kata sandi baru', 'type' => 'password', 'required' => true, 'attrs' => 'autocomplete="new-password" minlength="8"']) ?>
      <button class="btn btn--outline" type="submit"><?= icon('key', 'icon-sm') ?> Reset Kata Sandi</button>
    </div>
  </form>
  <?php endif; ?>
</div>
