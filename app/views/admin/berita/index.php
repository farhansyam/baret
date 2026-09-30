<?php
$actions = '<a class="btn btn--outline" href="' . url('/admin/kategori') . '">' . icon('tag', 'icon-sm') . ' Kategori</a>'
    . '<a class="btn btn--primary" href="' . url('/admin/berita/tambah') . '">' . icon('pencil', 'icon-sm') . ' Tulis Berita</a>';
$qs = fn(array $x) => '?' . http_build_query(array_filter(array_merge(['q' => $f['q'], 'kategori_id' => $f['kategori_id']], $x)));
?>
<?= partial('admin-head', ['heading' => 'Berita', 'crumbs' => [['Dashboard', '/admin'], ['Berita', null]], 'actions' => $actions]) ?>

<nav class="tabs" aria-label="Status">
  <a class="<?= !$f['status'] ? 'is-active' : '' ?>" href="<?= e($qs([])) ?>">Semua</a>
  <a class="<?= $f['status'] === 'publish' ? 'is-active' : '' ?>" href="<?= e($qs(['status' => 'publish'])) ?>">Terbit</a>
  <a class="<?= $f['status'] === 'draft' ? 'is-active' : '' ?>" href="<?= e($qs(['status' => 'draft'])) ?>">Draft</a>
</nav>

<form class="filters" method="get" action="<?= url('/admin/berita') ?>" role="search">
  <?php if ($f['status']): ?><input type="hidden" name="status" value="<?= e($f['status']) ?>"><?php endif; ?>
  <div class="field field--grow"><label class="label" for="q">Cari judul</label><input class="input" type="search" id="q" name="q" value="<?= e($f['q']) ?>"></div>
  <div class="field">
    <label class="label" for="kategori_id">Kategori</label>
    <select class="select" id="kategori_id" name="kategori_id" data-autosubmit>
      <option value="">Semua kategori</option>
      <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"<?= selected($f['kategori_id'], $c['id']) ?>><?= e($c['nama']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn--dark" type="submit"><?= icon('filter', 'icon-sm') ?> Terapkan</button>
</form>

<?php if ($items): ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th scope="col">Judul</th><th scope="col">Kategori</th><th scope="col">Status</th><th scope="col">Terbit</th><th scope="col">Dibaca</th><th scope="col" class="col-actions"><span class="sr-only">Aksi</span></th></tr></thead>
      <tbody>
        <?php foreach ($items as $n): $live = $n['status'] === 'publish' && $n['published_at'] && strtotime($n['published_at']) <= time(); ?>
          <tr>
            <td>
              <div class="row">
                <?php if ($n['gambar']): ?><img class="thumb-sm" src="<?= e(upload_url('news', $n['gambar'])) ?>" alt="" loading="lazy" width="56" height="40"><?php endif; ?>
                <div><a class="table__title" href="<?= url('/admin/berita/' . $n['id'] . '/ubah') ?>"><?= e($n['judul']) ?></a><div class="table__sub"><?= e($n['penulis'] ?? '—') ?></div></div>
              </div>
            </td>
            <td><?= e($n['kategori'] ?? '—') ?></td>
            <td><?= status_badge($n['status']) ?><?= $n['status'] === 'publish' && !$live ? ' <span class="badge badge--info">Terjadwal</span>' : '' ?></td>
            <td class="nowrap"><?= $n['published_at'] ? tanggal($n['published_at'], false, true) : '—' ?></td>
            <td><?= number($n['views']) ?></td>
            <td class="col-actions">
              <div class="row">
                <?php if ($live): ?><a class="btn btn--sm btn--ghost btn--icon" href="<?= url('/berita/' . $n['slug']) ?>" target="_blank" rel="noopener" aria-label="Lihat di website" title="Lihat di website"><?= icon('external', 'icon-sm') ?></a><?php endif; ?>
                <form method="post" action="<?= url('/admin/berita/' . $n['id'] . '/status') ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn--sm btn--outline" type="submit"><?= $n['status'] === 'publish' ? 'Jadikan draft' : 'Terbitkan' ?></button>
                </form>
                <a class="btn btn--sm btn--ghost btn--icon" href="<?= url('/admin/berita/' . $n['id'] . '/ubah') ?>" aria-label="Ubah" title="Ubah"><?= icon('pencil', 'icon-sm') ?></a>
                <form method="post" action="<?= url('/admin/berita/' . $n['id'] . '/hapus') ?>" data-confirm="Hapus berita “<?= e($n['judul']) ?>” beserta gambarnya? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus berita" data-confirm-ok="Hapus">
                  <?= csrf_field() ?><button class="btn btn--sm btn--ghost btn--icon" type="submit" aria-label="Hapus" title="Hapus"><?= icon('trash', 'icon-sm') ?></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= partial('pagination', ['pager' => $pager, 'label' => 'berita']) ?>
<?php else: ?>
  <div class="card"><?= partial('empty', ['icon' => 'newspaper', 'title' => $f['q'] || $f['status'] || $f['kategori_id'] ? 'Tidak ada hasil' : 'Belum ada berita', 'text' => 'Tulis berita pertama untuk ditampilkan di website.', 'action' => ['Tulis berita', url('/admin/berita/tambah'), 'pencil']]) ?></div>
<?php endif; ?>
