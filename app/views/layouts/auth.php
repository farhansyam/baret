<!doctype html>
<html lang="id" class="no-js">
<head>
<?= partial('head', get_defined_vars()) ?>
<meta name="robots" content="noindex">
</head>
<body>
<?= partial('flash') ?>
<div class="auth">
  <aside class="auth__brand">
    <a class="brand" href="<?= url('/') ?>"><span class="brand__text"><span class="brand__name">BARET</span><span class="brand__tag">Barisan Relawan Tangguh</span></span></a>
    <img src="<?= asset('img/logo.webp') ?>" alt="Logo BARET" width="280" height="271">
    <div>
      <h2>Satu Barisan.<br><span class="text-red">Satu Komando.</span></h2>
      <p>Sistem informasi internal pengurus BARET.</p>
    </div>
  </aside>
  <main class="auth__main" id="konten">
    <div class="auth__card">
      <a class="brand" href="<?= url('/') ?>" aria-label="Kembali ke beranda">
        <img src="<?= asset('img/logo-320.webp') ?>" alt="" width="52" height="52">
        <span class="brand__text"><span class="brand__name">BARET</span><span class="brand__tag">Barisan Relawan Tangguh</span></span>
      </a>
      <?= $content ?>
    </div>
  </main>
</div>
</body>
</html>
