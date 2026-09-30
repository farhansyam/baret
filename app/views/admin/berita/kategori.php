<?= partial('admin-head', ['heading' => 'Kategori Berita', 'crumbs' => [['Dashboard', '/admin'], ['Berita', '/admin/berita'], ['Kategori', null]]]) ?>

<div class="detail-grid">
  <section class="card">
    <?php if ($items): ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th scope="col">Nama</th><th scope="col">Slug</th><th scope="col">Jumlah berita</th><th scope="col" class="col-actions"><span class="sr-only">Aksi</span></th></tr></thead>
          <tbody>
            <?php foreach ($items as $c): ?>
              <tr>
                <td>
                  <?php if ($editId === (int) $c['id']): ?>
                    <form class="row" method="post" action="<?= url('/admin/kategori/' . $c['id']) ?>">
                      <?= csrf_field() ?>
                      <label class="sr-only" for="nama-<?= $c['id'] ?>">Nama kategori</label>
                      <input class="input" id="nama-<?= $c['id'] ?>" name="nama" value="<?= e(old('nama', $c['nama'])) ?>" required maxlength="60" autofocus>
                      <button class="btn btn--sm btn--primary" type="submit">Simpan</button>
                      <a class="btn btn--sm btn--ghost" href="<?= url('/admin/kategori') ?>">Batal</a>
                    </form>
                    <?php if ($err = error('nama')): ?><p class="field-error"><?= icon('alert') ?><?= e($err) ?></p><?php endif; ?>
                  <?php else: ?>
                    <span class="table__title"><?= e($c['nama']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="mono text-sm"><?= e($c['slug']) ?></td>
                <td><?= number($c['jumlah']) ?></td>
                <td class="col-actions">
                  <div class="row">
                    <a class="btn btn--sm btn--ghost btn--icon" href="<?= url('/admin/kategori?ubah=' . $c['id']) ?>" aria-label="Ubah <?= e($c['nama']) ?>" title="Ubah"><?= icon('pencil', 'icon-sm') ?></a>
                    <form method="post" action="<?= url('/admin/kategori/' . $c['id'] . '/hapus') ?>" data-confirm="Hapus kategori “<?= e($c['nama']) ?>”? Kategori yang masih dipakai berita tidak dapat dihapus." data-confirm-title="Hapus kategori" data-confirm-ok="Hapus">
                      <?= csrf_field() ?><button class="btn btn--sm btn--ghost btn--icon" type="submit" aria-label="Hapus <?= e($c['nama']) ?>" title="Hapus"><?= icon('trash', 'icon-sm') ?></button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <?= partial('empty', ['icon' => 'tag', 'title' => 'Belum ada kategori']) ?>
    <?php endif; ?>
  </section>
  <section class="card">
    <div class="card__head"><h2>Tambah Kategori</h2></div>
    <form class="card__body form-grid" method="post" action="<?= url('/admin/kategori') ?>">
      <?= csrf_field() ?>
      <?= partial('field', ['name' => 'nama', 'id' => 'nama-baru', 'label' => 'Nama kategori', 'value' => $editId ? '' : old('nama'), 'required' => true, 'attrs' => 'maxlength="60"']) ?>
      <button class="btn btn--primary" type="submit"><?= icon('plus', 'icon-sm') ?> Tambah</button>
    </form>
  </section>
</div>
