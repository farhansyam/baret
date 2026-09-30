<?php
use App\Core\Auth;
use App\Core\Database;

$u = auth();
$pendingMembers = 0;
if (can('members.verify')) {
    $pp = [];
    $sc = Auth::scopeSql('wilayah_id', $pp);
    $pendingMembers = (int) Database::value("SELECT COUNT(*) FROM members WHERE status = 'menunggu' AND deleted_at IS NULL $sc", $pp);
}
$newAspirasi = can('aspirasi.view') ? (int) Database::value("SELECT COUNT(*) FROM aspirasi WHERE status = 'baru' AND archived_at IS NULL") : 0;

$groups = [
    'Utama' => [
        ['/admin', can('dashboard.view') ? 'Dashboard' : 'Beranda', 'dashboard', null, true, true],
    ],
    'Keanggotaan' => [
        ['/admin/anggota', 'Anggota', 'users', $pendingMembers, false, can('members.view')],
        ['/admin/wilayah', 'Struktur Wilayah', 'network', null, false, can('wilayah.view')],
        ['/admin/pengurus', 'Pengurus', 'briefcase', null, false, can('pengurus.view')],
    ],
    'Konten' => [
        ['/admin/berita', 'Berita', 'newspaper', null, false, can('news.manage')],
        ['/admin/kategori', 'Kategori Berita', 'tag', null, false, can('news.manage')],
        ['/admin/kegiatan', 'Kegiatan', 'calendar', null, false, can('events.manage')],
        ['/admin/aspirasi', 'Aspirasi', 'megaphone', $newAspirasi, false, can('aspirasi.view')],
    ],
    'Sistem' => [
        ['/admin/pengguna', 'Pengguna & Peran', 'user-cog', null, false, can('users.manage')],
        ['/admin/log', 'Log Aktivitas', 'activity', null, false, can('logs.view')],
        ['/admin/pengaturan', 'Pengaturan Situs', 'settings', null, false, can('settings.manage')],
    ],
];
?>
<!doctype html>
<html lang="id" class="no-js">
<head>
<?= partial('head', get_defined_vars()) ?>
<meta name="robots" content="noindex, nofollow">
</head>
<body class="admin">
<a class="skip-link" href="#konten">Lewati ke konten utama</a>

<aside class="sidebar" id="sidebar" aria-label="Navigasi dashboard">
  <a class="sidebar__brand brand" href="<?= url('/admin') ?>">
    <img src="<?= asset('img/logo-320.webp') ?>" alt="" width="44" height="44">
    <span class="brand__text"><span class="brand__name">BARET</span><span class="brand__tag">Dashboard</span></span>
  </a>
  <nav class="sidebar__nav">
    <?php foreach ($groups as $g => $links):
        $visible = array_filter($links, fn($l) => $l[5]);
        if (!$visible) continue; ?>
      <div class="sidebar__group"><?= e($g) ?></div>
      <?php foreach ($visible as [$href, $label, $ic, $count, $exact]): $act = nav_active($href, $exact); ?>
        <a class="sidebar__link<?= $act ?>" href="<?= url($href) ?>"<?= $act ? ' aria-current="page"' : '' ?>>
          <?= icon($ic) ?><span><?= e($label) ?></span>
          <?php if ($count): ?><span class="sidebar__count" aria-label="<?= (int) $count ?> perlu ditindaklanjuti"><?= (int) $count ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar__foot">
    <a href="<?= url('/') ?>" target="_blank" rel="noopener"><?= icon('external', 'icon-sm') ?> Lihat website publik</a>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>

<div class="admin__main">
  <header class="topbar">
    <button type="button" class="topbar__toggle" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Buka navigasi"><?= icon('menu') ?></button>
    <?php if ($u['wilayah_nama'] && Auth::isScoped()): ?>
      <span class="badge badge--muted"><?= icon('map-pin', 'icon-sm') ?> <?= e($u['wilayah_nama']) ?></span>
    <?php endif; ?>
    <div class="user-menu">
      <button type="button" data-dropdown aria-controls="user-dropdown" aria-expanded="false">
        <span class="avatar avatar--red"><?= e(initials($u['nama'])) ?></span>
        <span class="user-menu__who"><strong><?= e($u['nama']) ?></strong><small><?= e($u['role_nama']) ?></small></span>
        <?= icon('chevron-down', 'icon-sm') ?>
      </button>
      <div class="dropdown" id="user-dropdown">
        <a href="<?= url('/admin/akun') ?>"><?= icon('user', 'icon-sm') ?> Akun saya</a>
        <a href="<?= url('/admin/akun#sandi') ?>"><?= icon('key', 'icon-sm') ?> Ubah kata sandi</a>
        <hr>
        <form method="post" action="<?= url('/logout') ?>">
          <?= csrf_field() ?>
          <button type="submit"><?= icon('log-out', 'icon-sm') ?> Keluar</button>
        </form>
      </div>
    </div>
  </header>

  <?= partial('flash') ?>

  <main class="admin__content" id="konten" tabindex="-1">
    <?= $content ?>
  </main>
</div>

<?= partial('confirm-modal') ?>
</body>
</html>
