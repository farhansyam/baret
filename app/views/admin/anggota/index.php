<?php
use App\Models\Member;
$qs = fn(array $extra) => '?' . http_build_query(array_filter(array_merge(['q' => $f['q'], 'wilayah_id' => $f['wilayah_id']], $extra)));
$actions = '';
if (can('members.export')) {
    $actions .= '<a class="btn btn--outline" href="' . url('/admin/anggota/ekspor') . e('?' . http_build_query(array_filter($f))) . '">' . icon('download', 'icon-sm') . ' Ekspor CSV</a>';
}
if (can('members.manage')) {
    $actions .= '<a class="btn btn--primary" href="' . url('/admin/anggota/tambah') . '">' . icon('user-plus', 'icon-sm') . ' Tambah Anggota</a>';
}
?>
<?= partial('admin-head', ['heading' => 'Anggota', 'crumbs' => [['Dashboard', '/admin'], ['Anggota', null]], 'actions' => $actions]) ?>

<nav class="tabs" aria-label="Filter status">
  <a class="<?= !$f['status'] && !$f['arsip'] ? 'is-active' : '' ?>" href="<?= e($qs([])) ?>">Semua</a>
  <?php foreach (Member::STATUS as $k => $l): ?>
    <a class="<?= $f['status'] === $k && !$f['arsip'] ? 'is-active' : '' ?>" href="<?= e($qs(['status' => $k])) ?>"><?= e($l) ?></a>
  <?php endforeach; ?>
  <a class="<?= $f['arsip'] ? 'is-active' : '' ?>" href="<?= e($qs(['arsip' => '1'])) ?>"><?= icon('archive', 'icon-sm') ?> Arsip</a>
</nav>

<form class="filters" method="get" action="<?= url('/admin/anggota') ?>" role="search">
  <?php if ($f['status']): ?><input type="hidden" name="status" value="<?= e($f['status']) ?>"><?php endif; ?>
  <?php if ($f['arsip']): ?><input type="hidden" name="arsip" value="1"><?php endif; ?>
  <div class="field field--grow">
    <label class="label" for="q">Cari</label>
    <input class="input" type="search" id="q" name="q" value="<?= e($f['q']) ?>" placeholder="Nama, NIA, no. registrasi, atau no. HP">
  </div>
  <div class="field">
    <label class="label" for="wilayah_id">Wilayah</label>
    <select class="select" id="wilayah_id" name="wilayah_id" data-autosubmit>
      <option value="">Semua wilayah</option>
      <?php foreach ($wilayahOptions as $o): ?><option value="<?= $o['id'] ?>"<?= selected($f['wilayah_id'], $o['id']) ?>><?= e($o['label']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn--dark" type="submit"><?= icon('filter', 'icon-sm') ?> Terapkan</button>
  <?php if ($f['q'] || $f['wilayah_id']): ?><a class="btn btn--ghost" href="<?= url('/admin/anggota') ?>">Reset</a><?php endif; ?>
</form>

<?php if ($items): ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th scope="col">Anggota</th><th scope="col">NIA / Registrasi</th><th scope="col">Wilayah</th><th scope="col">Status</th><th scope="col">Terdaftar</th><th scope="col" class="col-actions"><span class="sr-only">Aksi</span></th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $m): ?>
          <tr>
            <td>
              <div class="row">
                <span class="avatar" aria-hidden="true"><?= e(initials($m['nama'])) ?></span>
                <div><a class="table__title" href="<?= url('/admin/anggota/' . $m['id']) ?>"><?= e($m['nama']) ?></a><div class="table__sub"><?= e($m['telepon']) ?></div></div>
              </div>
            </td>
            <td><?php if ($m['nia']): ?><span class="mono"><?= e($m['nia']) ?></span><?php else: ?><span class="table__sub mono"><?= e($m['no_registrasi']) ?></span><?php endif; ?></td>
            <td><?= e($m['wilayah_nama']) ?></td>
            <td><?= $m['deleted_at'] ? '<span class="badge badge--muted">Diarsipkan</span>' : status_badge($m['status']) ?></td>
            <td class="nowrap"><?= tanggal($m['created_at'], false, true) ?></td>
            <td class="col-actions">
              <div class="row">
                <a class="btn btn--sm btn--ghost btn--icon" href="<?= url('/admin/anggota/' . $m['id']) ?>" aria-label="Lihat <?= e($m['nama']) ?>" title="Lihat"><?= icon('eye', 'icon-sm') ?></a>
                <?php if (can('members.manage') && !$m['deleted_at']): ?>
                  <a class="btn btn--sm btn--ghost btn--icon" href="<?= url('/admin/anggota/' . $m['id'] . '/ubah') ?>" aria-label="Ubah <?= e($m['nama']) ?>" title="Ubah"><?= icon('pencil', 'icon-sm') ?></a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= partial('pagination', ['pager' => $pager, 'label' => 'anggota']) ?>
<?php else: ?>
  <div class="card">
    <?= partial('empty', [
        'icon' => $f['q'] ? 'search' : 'users',
        'title' => $f['q'] || $f['status'] || $f['wilayah_id'] || $f['arsip'] ? 'Tidak ada hasil' : 'Belum ada anggota',
        'text' => $f['q'] || $f['status'] || $f['wilayah_id'] || $f['arsip'] ? 'Tidak ada anggota yang cocok dengan filter ini.' : 'Anggota akan muncul di sini setelah mendaftar melalui website atau diinput admin.',
        'action' => can('members.manage') && !$f['q'] ? ['Tambah anggota', url('/admin/anggota/tambah'), 'user-plus'] : null,
    ]) ?>
  </div>
<?php endif; ?>
