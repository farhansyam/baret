<h1>Lupa Sandi</h1>
<p class="text-muted mb-6">Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang kata sandi (berlaku 1 jam).</p>
<form method="post" action="<?= url('/lupa-sandi') ?>" class="form-grid">
  <?= csrf_field() ?>
  <?= partial('field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => old('email'), 'required' => true, 'attrs' => 'autocomplete="email" autofocus maxlength="160"']) ?>
  <button type="submit" class="btn btn--primary btn--block"><?= icon('send', 'icon-sm') ?> Kirim Tautan</button>
</form>
<p class="text-sm mt-6"><a href="<?= url('/login') ?>"><?= icon('arrow-left', 'icon-sm') ?> Kembali ke halaman masuk</a></p>
