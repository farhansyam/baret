<?php
use App\Models\Wilayah;

$render = function (array $nodes) use (&$render, $counts) {
    ob_start(); ?>
    <ul class="tree">
      <?php foreach ($nodes as $n): ?>
        <li>
          <div class="tree-item">
            <span class="org-level org-level--<?= e($n['tingkat']) ?>"><?= e(Wilayah::TINGKAT[$n['tingkat']]) ?></span>
            <div class="grow">
              <span class="tree-item__name"><?= e($n['nama']) ?></span>
              <span class="text-sm text-muted"> · <span class="mono"><?= e($n['kode']) ?></span> · <?= number($counts[$n['id']] ?? 0) ?> anggota aktif</span>
            </div>
            <div class="row">
              <a class="btn btn--sm btn--ghost" href="<?= url('/admin/anggota?wilayah_id=' . $n['id']) ?>"><?= icon('users', 'icon-sm') ?> Anggota</a>
              <?php if (can('wilayah.manage')): ?>
                <a class="btn btn--sm btn--ghost btn--icon" href="<?= url('/admin/wilayah/' . $n['id'] . '/ubah') ?>" aria-label="Ubah <?= e($n['nama']) ?>" title="Ubah"><?= icon('pencil', 'icon-sm') ?></a>
                <form method="post" action="<?= url('/admin/wilayah/' . $n['id'] . '/hapus') ?>" data-confirm="Hapus <?= e($n['nama']) ?>? Penghapusan ditolak bila masih ada sub-wilayah, anggota, pengurus, pengguna, atau kegiatan terkait." data-confirm-title="Hapus wilayah" data-confirm-ok="Hapus">
                  <?= csrf_field() ?>
                  <button class="btn btn--sm btn--ghost btn--icon" type="submit" aria-label="Hapus <?= e($n['nama']) ?>" title="Hapus"><?= icon('trash', 'icon-sm') ?></button>
                </form>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($n['children']) echo $render($n['children']); ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php return ob_get_clean();
};
$actions = can('wilayah.manage') ? '<a class="btn btn--primary" href="' . url('/admin/wilayah/tambah') . '">' . icon('plus', 'icon-sm') . ' Tambah Wilayah</a>' : '';
?>
<?= partial('admin-head', ['heading' => 'Struktur Wilayah', 'crumbs' => [['Dashboard', '/admin'], ['Struktur Wilayah', null]], 'actions' => $actions]) ?>

<p class="text-muted">Hierarki pusat → wilayah → cabang → ranting. Kode wilayah dipakai pada NIA anggota (<span class="mono">BRT-{KODE}-{TAHUN}-{URUT}</span>).</p>
<?php if ($tree): ?>
  <?= $render($tree) ?>
<?php else: ?>
  <div class="card"><?= partial('empty', ['icon' => 'network', 'title' => 'Belum ada wilayah', 'action' => can('wilayah.manage') ? ['Tambah wilayah', url('/admin/wilayah/tambah')] : null]) ?></div>
<?php endif; ?>
