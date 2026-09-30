<?php
use App\Controllers\Admin\LogController;
$aksiLabel = [
    'create' => 'Tambah', 'update' => 'Ubah', 'delete' => 'Hapus', 'status' => 'Ubah status', 'archive' => 'Arsip', 'restore' => 'Pulihkan',
    'login' => 'Masuk', 'logout' => 'Keluar', 'login_gagal' => 'Gagal masuk', 'export' => 'Ekspor', 'activate' => 'Aktifkan', 'deactivate' => 'Nonaktifkan',
    'reset_sandi' => 'Reset sandi', 'reset_diminta' => 'Minta reset sandi', 'ubah_sandi' => 'Ubah sandi',
];
$tone = ['create' => 'success', 'delete' => 'danger', 'login_gagal' => 'danger', 'deactivate' => 'danger', 'archive' => 'warning', 'status' => 'info', 'update' => 'info'];
$fmt = fn($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v;
?>
<?= partial('admin-head', ['heading' => 'Log Aktivitas', 'crumbs' => [['Dashboard', '/admin'], ['Log Aktivitas', null]]]) ?>

<form class="filters" method="get" action="<?= url('/admin/log') ?>">
  <div class="field">
    <label class="label" for="tabel">Modul</label>
    <select class="select" id="tabel" name="tabel"><option value="">Semua modul</option>
      <?php foreach (LogController::TABLES as $k => $l): ?><option value="<?= $k ?>"<?= selected($f['tabel'], $k) ?>><?= e($l) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label class="label" for="aksi">Aksi</label>
    <select class="select" id="aksi" name="aksi"><option value="">Semua aksi</option>
      <?php foreach ($aksiList as $a): ?><option value="<?= e($a['aksi']) ?>"<?= selected($f['aksi'], $a['aksi']) ?>><?= e($aksiLabel[$a['aksi']] ?? $a['aksi']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label class="label" for="user_id">Pengguna</label>
    <select class="select" id="user_id" name="user_id"><option value="">Semua pengguna</option>
      <?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"<?= selected($f['user_id'], $u['id']) ?>><?= e($u['nama']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="field"><label class="label" for="dari">Dari</label><input class="input" type="date" id="dari" name="dari" value="<?= e($f['dari']) ?>"></div>
  <div class="field"><label class="label" for="sampai">Sampai</label><input class="input" type="date" id="sampai" name="sampai" value="<?= e($f['sampai']) ?>"></div>
  <button class="btn btn--dark" type="submit"><?= icon('filter', 'icon-sm') ?> Terapkan</button>
  <?php if (array_filter($f)): ?><a class="btn btn--ghost" href="<?= url('/admin/log') ?>">Reset</a><?php endif; ?>
</form>

<?php if ($items): ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th scope="col">Waktu</th><th scope="col">Pengguna</th><th scope="col">Aksi</th><th scope="col">Data</th><th scope="col">Perubahan</th><th scope="col">IP</th></tr></thead>
      <tbody>
        <?php foreach ($items as $l):
            $old = $l['data_lama'] ? json_decode($l['data_lama'], true) : [];
            $new = $l['data_baru'] ? json_decode($l['data_baru'], true) : [];
            $keys = array_unique(array_merge(array_keys($old ?: []), array_keys($new ?: [])));
        ?>
          <tr>
            <td class="nowrap"><?= tanggal($l['waktu'], false, true) ?><div class="table__sub"><?= date('H.i.s', strtotime($l['waktu'])) ?></div></td>
            <td><?= e($l['user_nama'] ?? 'Publik/Sistem') ?></td>
            <td><span class="badge badge--<?= $tone[$l['aksi']] ?? 'muted' ?>"><?= e($aksiLabel[$l['aksi']] ?? $l['aksi']) ?></span></td>
            <td class="nowrap"><?= e(LogController::TABLES[$l['tabel']] ?? $l['tabel']) ?><?= $l['record_id'] ? ' <span class="mono text-sm">#' . e($l['record_id']) . '</span>' : '' ?></td>
            <td>
              <?php if ($keys): ?>
                <details class="log-detail">
                  <summary><?= count($keys) ?> kolom</summary>
                  <div class="json-diff mt-2">
                    <?php foreach ($keys as $k): ?>
                      <div><strong><?= e($k) ?></strong>:
                        <?php if (array_key_exists($k, $old ?: [])): ?><del><?= e(str_limit($fmt($old[$k]), 120)) ?></del><?php endif; ?>
                        <?php if (array_key_exists($k, $new ?: [])): ?> <ins><?= e(str_limit($fmt($new[$k]), 120)) ?></ins><?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </details>
              <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td class="mono text-sm"><?= e($l['ip']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= partial('pagination', ['pager' => $pager, 'label' => 'catatan']) ?>
<?php else: ?>
  <div class="card"><?= partial('empty', ['icon' => 'activity', 'title' => 'Tidak ada catatan', 'text' => 'Tidak ada aktivitas yang cocok dengan filter.']) ?></div>
<?php endif; ?>
<p class="text-sm text-muted mt-4"><?= icon('lock', 'icon-sm') ?> Log bersifat permanen: tidak dapat diubah atau dihapus dari aplikasi. Data sensitif (kata sandi, NIK) tidak dicatat.</p>
