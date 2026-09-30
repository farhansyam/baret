<?php
use App\Models\Aspirasi;
$qs = fn(array $x) => '?' . http_build_query(array_filter(array_merge(['q' => $f['q'], 'kategori' => $f['kategori']], $x)));
?>
<?= partial('admin-head', ['heading' => 'Aspirasi', 'crumbs' => [['Dashboard', '/admin'], ['Aspirasi', null]]]) ?>

<nav class="tabs" aria-label="Status">
  <a class="<?= !$f['status'] && !$f['arsip'] ? 'is-active' : '' ?>" href="<?= e($qs([])) ?>">Semua</a>
  <?php foreach (Aspirasi::STATUS as $k => $l): ?><a class="<?= $f['status'] === $k && !$f['arsip'] ? 'is-active' : '' ?>" href="<?= e($qs(['status' => $k])) ?>"><?= e($l) ?></a><?php endforeach; ?>
  <a class="<?= $f['arsip'] ? 'is-active' : '' ?>" href="<?= e($qs(['arsip' => '1'])) ?>"><?= icon('archive', 'icon-sm') ?> Arsip</a>
</nav>

<form class="filters" method="get" action="<?= url('/admin/aspirasi') ?>" role="search">
  <?php if ($f['status']): ?><input type="hidden" name="status" value="<?= e($f['status']) ?>"><?php endif; ?>
  <?php if ($f['arsip']): ?><input type="hidden" name="arsip" value="1"><?php endif; ?>
  <div class="field field--grow"><label class="label" for="q">Cari</label><input class="input" type="search" id="q" name="q" value="<?= e($f['q']) ?>" placeholder="No. tiket, judul, atau nama"></div>
  <div class="field">
    <label class="label" for="kategori">Kategori</label>
    <select class="select" id="kategori" name="kategori" data-autosubmit>
      <option value="">Semua</option>
      <?php foreach (Aspirasi::KATEGORI as $k): ?><option<?= selected($f['kategori'], $k) ?>><?= e($k) ?></option><?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn--dark" type="submit"><?= icon('filter', 'icon-sm') ?> Terapkan</button>
</form>

<?php if ($items): ?>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th scope="col">Tiket</th><th scope="col">Judul</th><th scope="col">Kategori</th><th scope="col">Status</th><th scope="col">Masuk</th><th scope="col">Petugas</th></tr></thead>
      <tbody>
        <?php foreach ($items as $a): ?>
          <tr>
            <td class="mono nowrap"><a class="table__title" href="<?= url('/admin/aspirasi/' . $a['id']) ?>"><?= e($a['tiket']) ?></a></td>
            <td><a class="table__title" href="<?= url('/admin/aspirasi/' . $a['id']) ?>"><?= e(str_limit($a['judul'], 70)) ?></a><div class="table__sub"><?= e($a['nama']) ?></div></td>
            <td><?= e($a['kategori']) ?></td>
            <td><?= status_badge($a['status']) ?></td>
            <td class="nowrap"><?= time_ago($a['created_at']) ?></td>
            <td><?= e($a['petugas'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= partial('pagination', ['pager' => $pager, 'label' => 'tiket']) ?>
<?php else: ?>
  <div class="card"><?= partial('empty', ['icon' => 'megaphone', 'title' => $f['q'] || $f['status'] || $f['kategori'] || $f['arsip'] ? 'Tidak ada hasil' : 'Belum ada aspirasi', 'text' => 'Aspirasi dari formulir publik akan muncul di sini.']) ?></div>
<?php endif; ?>
