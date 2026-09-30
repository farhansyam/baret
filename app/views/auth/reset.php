<h1>Sandi Baru</h1>
<p class="text-muted mb-6">Buat kata sandi baru minimal 8 karakter yang mengandung huruf dan angka.</p>
<form method="post" action="<?= url('/reset-sandi') ?>" class="form-grid">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e($token) ?>">
  <?= partial('field', ['name' => 'password', 'label' => 'Kata sandi baru', 'type' => 'password', 'required' => true, 'attrs' => 'autocomplete="new-password" minlength="8" autofocus']) ?>
  <?= partial('field', ['name' => 'password_confirmation', 'label' => 'Ulangi kata sandi baru', 'type' => 'password', 'required' => true, 'attrs' => 'autocomplete="new-password" minlength="8"']) ?>
  <button type="submit" class="btn btn--primary btn--block"><?= icon('check', 'icon-sm') ?> Simpan Kata Sandi</button>
</form>
