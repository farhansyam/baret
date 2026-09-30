<?php
use App\Models\Event;
$actions = '<a class="btn btn--primary" href="' . url('/admin/kegiatan/tambah') . '">' . icon('plus', 'icon-sm') . ' Tambah Kegiatan</a>';
$qs = fn(array $x) => '?' . http_build_query(array_filter(array_merge(['q' => $f['q'], 'status' => $f['status']], $x)));
?>
<?= partial('admin-head', ['heading' => 'Kegiatan', 'crumbs' => [['Dashboard', '/admin'], ['Kegiatan', null]], 'actions' => $actions]) ?>

<nav class="tabs" aria-label="Waktu">
  <a class="<?= !$f['waktu'] ? 'is-active' : '' ?>" href="<?= e($qs([])) ?>">Semua</a>
  <?php foreach (Event::WAKTU as $k => $l): ?><a class="<?= $f['waktu'] === $k ? 'is-active' : '' ?>" href="<?= e($qs(['waktu' => $k])) ?>"><?= e($l) ?></a><?php endforeach; ?>
</nav>

<form class="filters" method="get" action="<?= url('/admin/kegiatan') ?>" role="search">
  <?php if ($f['waktu']): ?><input type="hidden" name="waktu" value="<?= e($f['waktu']) ?>"><?php endif; ?>
  <div class="field field--grow"><label class="label" for="q">Cari</label><input class="input" type="search" id="q" name="q" value="<?= e($f['q']) ?>" placeholder="Judul atau lokasi"></div>
  <div class="field">
    <label class="label" for="status">Publikasi</label>
    <select class="select" id="status" name="status" data-autosubmit>
      <option value="">Semua</option>
      <option value="publish"<?= selected($f['status'], 'publish') ?>>Publish</option>
      <option value="draft"<?= selected($f['status'], 'draft') ?>>Draft</option>
    </select>
  </div>
  <button class="btn btn--dark" type="submit"><?= icon('filter', 'icon-sm') ?> Terapkan</button>
</form>

<?php if ($items): ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th scope="col">Kegiatan</th><th scope="col">Jadwal</th><th scope="col">Penyelenggara</th><th scope="col">Status</th><th scope="col">Foto</th><th scope="col" class="col-actions"><span class="sr-only">Aksi</span></th></tr></thead>
      <tbody>
        <?php foreach ($items as $e): ?>
          <tr>
            <td><a class="table__title" href="<?= url('/admin/kegiatan/' . $e['id'] . '/ubah') ?>"><?= e($e['judul']) ?></a><div class="table__sub"><?= icon('map-pin', 'icon-sm') ?> <?= e($e['lokasi']) ?></div></td>
            <td class="nowrap"><?= tanggal($e['mulai'], true, true) ?><div class="table__sub">s.d. <?= tanggal($e['selesai'], true, true) ?></div></td>
            <td><?= e($e['wilayah_nama'] ?? 'Pusat') ?></td>
            <td><div class="row"><?= status_badge($e['status']) ?> <?= status_badge(Event::timeStatus($e)) ?></div></td>
            <td><?= (int) $e['jumlah_foto'] ?></td>
            <td class="col-actions">
              <div class="row">
                <?php if ($e['status'] === 'publish'): ?><a class="btn btn--sm btn--ghost btn--icon" href="<?= url('/kegiatan/' . $e['slug']) ?>" target="_blank" rel="noopener" aria-label="Lihat di website" title="Lihat di website"><?= icon('external', 'icon-sm') ?></a><?php endif; ?>
                <form method="post" action="<?= url('/admin/kegiatan/' . $e['id'] . '/status') ?>"><?= csrf_field() ?><button class="btn btn--sm btn--outline" type="submit"><?= $e['status'] === 'publish' ? 'Jadikan draft' : 'Publikasikan' ?></button></form>
                <a class="btn btn--sm btn--ghost btn--icon" href="<?= url('/admin/kegiatan/' . $e['id'] . '/ubah') ?>" aria-label="Ubah" title="Ubah"><?= icon('pencil', 'icon-sm') ?></a>
                <form method="post" action="<?= url('/admin/kegiatan/' . $e['id'] . '/hapus') ?>" data-confirm="Hapus kegiatan “<?= e($e['judul']) ?>” beserta <?= (int) $e['jumlah_foto'] ?> foto galerinya? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus kegiatan" data-confirm-ok="Hapus">
                  <?= csrf_field() ?><button class="btn btn--sm btn--ghost btn--icon" type="submit" aria-label="Hapus" title="Hapus"><?= icon('trash', 'icon-sm') ?></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= partial('pagination', ['pager' => $pager, 'label' => 'kegiatan']) ?>
<?php else: ?>
  <div class="card"><?= partial('empty', ['icon' => 'calendar', 'title' => $f['q'] || $f['status'] || $f['waktu'] ? 'Tidak ada hasil' : 'Belum ada kegiatan', 'action' => ['Tambah kegiatan', url('/admin/kegiatan/tambah')]]) ?></div>
<?php endif; ?>
