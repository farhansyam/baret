<?php
$actions = can('pengurus.manage') ? '<a class="btn btn--primary" href="' . url('/admin/pengurus/tambah') . '">' . icon('plus', 'icon-sm') . ' Tambah Pengurus</a>' : '';
$qs = fn(array $x) => '?' . http_build_query(array_filter(array_merge(['wilayah_id' => $f['wilayah_id'], 'q' => $f['q']], $x)));
?>
<?= partial('admin-head', ['heading' => 'Pengurus', 'crumbs' => [['Dashboard', '/admin'], ['Pengurus', null]], 'actions' => $actions]) ?>

<nav class="tabs" aria-label="Periode">
  <a class="<?= $f['periode'] === 'aktif' ? 'is-active' : '' ?>" href="<?= e($qs(['periode' => 'aktif'])) ?>">Menjabat</a>
  <a class="<?= $f['periode'] === 'selesai' ? 'is-active' : '' ?>" href="<?= e($qs(['periode' => 'selesai'])) ?>">Periode selesai</a>
  <a class="<?= $f['periode'] === 'semua' ? 'is-active' : '' ?>" href="<?= e($qs(['periode' => 'semua'])) ?>">Semua</a>
</nav>

<form class="filters" method="get" action="<?= url('/admin/pengurus') ?>" role="search">
  <input type="hidden" name="periode" value="<?= e($f['periode']) ?>">
  <div class="field field--grow"><label class="label" for="q">Cari</label><input class="input" type="search" id="q" name="q" value="<?= e($f['q']) ?>" placeholder="Nama atau jabatan"></div>
  <div class="field">
    <label class="label" for="wilayah_id">Wilayah</label>
    <select class="select" id="wilayah_id" name="wilayah_id" data-autosubmit>
      <option value="">Semua wilayah</option>
      <?php foreach ($wilayahOptions as $o): ?><option value="<?= $o['id'] ?>"<?= selected($f['wilayah_id'], $o['id']) ?>><?= e($o['label']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn--dark" type="submit"><?= icon('filter', 'icon-sm') ?> Terapkan</button>
</form>

<?php if ($items): ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th scope="col">Nama</th><th scope="col">Jabatan</th><th scope="col">Wilayah</th><th scope="col">Masa jabatan</th><th scope="col">SK</th><th scope="col" class="col-actions"><span class="sr-only">Aksi</span></th></tr></thead>
      <tbody>
        <?php foreach ($items as $p): $selesai = $p['periode_selesai'] && strtotime($p['periode_selesai']) < strtotime('today'); ?>
          <tr>
            <td><div class="table__title"><?= e($p['nama']) ?></div><div class="table__sub mono"><?= e($p['nia'] ?? '') ?></div></td>
            <td><?= e($p['jabatan']) ?></td>
            <td><?= e($p['wilayah_nama']) ?></td>
            <td class="nowrap"><?= tanggal($p['periode_mulai'], false, true) ?> – <?= $p['periode_selesai'] ? tanggal($p['periode_selesai'], false, true) : 'sekarang' ?> <?= $selesai ? '<span class="badge badge--muted">Selesai</span>' : '' ?></td>
            <td><?php if ($p['sk_file']): ?><a href="<?= url('/admin/file/sk/' . $p['sk_file']) ?>" target="_blank" rel="noopener"><?= icon('file-text', 'icon-sm') ?> <?= e($p['sk_nomor'] ?: 'Lihat') ?></a><?php else: ?><?= e($p['sk_nomor'] ?: '—') ?><?php endif; ?></td>
            <td class="col-actions">
              <?php if (can('pengurus.manage')): ?>
              <div class="row">
                <a class="btn btn--sm btn--ghost btn--icon" href="<?= url('/admin/pengurus/' . $p['id'] . '/ubah') ?>" aria-label="Ubah" title="Ubah / ganti periode"><?= icon('pencil', 'icon-sm') ?></a>
                <form method="post" action="<?= url('/admin/pengurus/' . $p['id'] . '/hapus') ?>" data-confirm="Hapus data jabatan <?= e($p['jabatan']) ?> atas nama <?= e($p['nama']) ?>? Untuk mengakhiri jabatan sebaiknya isi tanggal selesai periode." data-confirm-title="Hapus pengurus" data-confirm-ok="Hapus">
                  <?= csrf_field() ?><button class="btn btn--sm btn--ghost btn--icon" type="submit" aria-label="Hapus" title="Hapus"><?= icon('trash', 'icon-sm') ?></button>
                </form>
              </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= partial('pagination', ['pager' => $pager, 'label' => 'pengurus']) ?>
<?php else: ?>
  <div class="card"><?= partial('empty', ['icon' => 'briefcase', 'title' => 'Belum ada data pengurus', 'text' => 'Tambahkan pengurus dari anggota berstatus aktif.', 'action' => can('pengurus.manage') ? ['Tambah pengurus', url('/admin/pengurus/tambah')] : null]) ?></div>
<?php endif; ?>
