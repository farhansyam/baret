<?php
use App\Core\Auth;
$actions = '<a class="btn btn--primary" href="' . url('/admin/pengguna/tambah') . '">' . icon('user-plus', 'icon-sm') . ' Tambah Pengguna</a>';
?>
<?= partial('admin-head', ['heading' => 'Pengguna & Peran', 'crumbs' => [['Dashboard', '/admin'], ['Pengguna', null]], 'actions' => $actions]) ?>

<form class="filters" method="get" action="<?= url('/admin/pengguna') ?>" role="search">
  <div class="field field--grow"><label class="label" for="q">Cari</label><input class="input" type="search" id="q" name="q" value="<?= e($f['q']) ?>" placeholder="Nama atau email"></div>
  <div class="field">
    <label class="label" for="role_id">Peran</label>
    <select class="select" id="role_id" name="role_id" data-autosubmit>
      <option value="">Semua peran</option>
      <?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>"<?= selected($f['role_id'], $r['id']) ?>><?= e($r['nama']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn--dark" type="submit"><?= icon('filter', 'icon-sm') ?> Terapkan</button>
</form>

<?php if ($items): ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th scope="col">Pengguna</th><th scope="col">Peran</th><th scope="col">Wilayah</th><th scope="col">Status</th><th scope="col">Login terakhir</th><th scope="col" class="col-actions"><span class="sr-only">Aksi</span></th></tr></thead>
      <tbody>
        <?php foreach ($items as $u): $self = (int) $u['id'] === Auth::id(); ?>
          <tr>
            <td><div class="row"><span class="avatar<?= $u['role'] === 'super_admin' ? ' avatar--red' : '' ?>" aria-hidden="true"><?= e(initials($u['nama'])) ?></span><div><span class="table__title"><?= e($u['nama']) ?><?= $self ? ' <span class="badge badge--info">Anda</span>' : '' ?></span><div class="table__sub"><?= e($u['email']) ?></div></div></div></td>
            <td><?= e($u['role_nama']) ?></td>
            <td><?= e($u['wilayah_nama'] ?? '—') ?></td>
            <td><?= (int) $u['aktif'] ? '<span class="badge badge--success">Aktif</span>' : '<span class="badge badge--muted">Nonaktif</span>' ?></td>
            <td class="nowrap"><?= $u['last_login'] ? time_ago($u['last_login']) : 'Belum pernah' ?></td>
            <td class="col-actions">
              <div class="row">
                <a class="btn btn--sm btn--ghost btn--icon" href="<?= url('/admin/pengguna/' . $u['id'] . '/ubah') ?>" aria-label="Ubah <?= e($u['nama']) ?>" title="Ubah"><?= icon('pencil', 'icon-sm') ?></a>
                <?php if (!$self): ?>
                  <form method="post" action="<?= url('/admin/pengguna/' . $u['id'] . '/aktif') ?>"<?= (int) $u['aktif'] ? ' data-confirm="Nonaktifkan akun ' . e($u['email']) . '? Pengguna tidak akan bisa masuk sampai diaktifkan kembali." data-confirm-title="Nonaktifkan akun" data-confirm-ok="Nonaktifkan"' : '' ?>>
                    <?= csrf_field() ?><button class="btn btn--sm btn--outline" type="submit"><?= (int) $u['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= partial('pagination', ['pager' => $pager, 'label' => 'pengguna']) ?>
<?php else: ?>
  <div class="card"><?= partial('empty', ['icon' => 'users', 'title' => 'Tidak ada pengguna']) ?></div>
<?php endif; ?>

<section class="card mt-6">
  <div class="card__head"><h2>Hak Akses per Peran</h2></div>
  <div class="card__body">
    <dl class="dl">
      <?php foreach ($roles as $r): ?><dt><?= e($r['nama']) ?></dt><dd><?= e($r['deskripsi']) ?></dd><?php endforeach; ?>
    </dl>
    <p class="text-sm text-muted mt-4 mb-0"><?= icon('info', 'icon-sm') ?> Akun tidak pernah dihapus — cukup dinonaktifkan agar jejak log tetap utuh.</p>
  </div>
</section>
