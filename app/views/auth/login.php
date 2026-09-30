<h1>Masuk</h1>
<p class="text-muted mb-6">Khusus pengurus dan anggota yang telah memiliki akun.</p>
<form method="post" action="<?= url('/login') ?>" class="form-grid">
  <?= csrf_field() ?>
  <?= partial('field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => old('email'), 'required' => true, 'attrs' => 'autocomplete="username" autofocus maxlength="160"']) ?>
  <?= partial('field', ['name' => 'password', 'label' => 'Kata sandi', 'type' => 'password', 'required' => true, 'attrs' => 'autocomplete="current-password"']) ?>
  <div class="row row--between">
    <a class="text-sm" href="<?= url('/lupa-sandi') ?>">Lupa kata sandi?</a>
  </div>
  <button type="submit" class="btn btn--primary btn--block"><?= icon('log-in', 'icon-sm') ?> Masuk</button>
</form>
<p class="text-sm text-muted mt-6"><?= icon('shield', 'icon-sm') ?> Demi keamanan, akun dikunci sementara setelah <?= (int) config('security.login_max_attempts') ?> kali gagal masuk.</p>
<p class="text-sm mt-4"><a href="<?= url('/') ?>"><?= icon('arrow-left', 'icon-sm') ?> Kembali ke website</a></p>
