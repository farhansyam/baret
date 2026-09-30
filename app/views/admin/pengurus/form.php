<?php
$isEdit = $p !== null;
$v = fn(string $k) => old($k, $p[$k] ?? '');
$memberOpts = [];
foreach ($members as $m) {
    $memberOpts[$m['id']] = $m['nama'] . ($m['nia'] ? ' — ' . $m['nia'] : '');
}
$wOpts = [];
foreach ($wilayahOptions as $o) {
    $wOpts[$o['id']] = $o['label'];
}
?>
<?= partial('admin-head', ['heading' => $isEdit ? 'Ubah Pengurus' : 'Tambah Pengurus', 'crumbs' => [['Dashboard', '/admin'], ['Pengurus', '/admin/pengurus'], [$isEdit ? $p['member_nama'] : 'Tambah', null]]]) ?>

<?php if (!$memberOpts): ?>
  <div class="alert alert--warning mb-6"><?= icon('alert') ?><div>Belum ada anggota berstatus aktif. Pengurus harus dipilih dari anggota aktif — <a href="<?= url('/admin/anggota?status=menunggu') ?>">verifikasi pendaftar</a> terlebih dahulu.</div></div>
<?php endif; ?>

<form class="card" method="post" action="<?= $isEdit ? url('/admin/pengurus/' . $p['id']) : url('/admin/pengurus') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="card__body form-grid form-grid--2">
    <?= partial('field', ['name' => 'member_id', 'label' => 'Anggota', 'type' => 'select', 'options' => $memberOpts, 'value' => $v('member_id'), 'required' => true, 'placeholder' => '— Pilih anggota aktif —']) ?>
    <?= partial('field', ['name' => 'wilayah_id', 'label' => 'Unit/wilayah', 'type' => 'select', 'options' => $wOpts, 'value' => $v('wilayah_id'), 'required' => true]) ?>
    <?= partial('field', ['name' => 'jabatan', 'label' => 'Jabatan', 'value' => $v('jabatan'), 'required' => true, 'placeholder' => 'Mis. Ketua, Sekretaris, Bendahara', 'attrs' => 'maxlength="80" list="jabatan-list"']) ?>
    <datalist id="jabatan-list"><?php foreach (['Ketua Umum', 'Wakil Ketua Umum', 'Sekretaris Jenderal', 'Bendahara Umum', 'Ketua', 'Wakil Ketua', 'Sekretaris', 'Bendahara', 'Koordinator Lapangan', 'Anggota Pengurus'] as $j): ?><option value="<?= $j ?>"><?php endforeach; ?></datalist>
    <?= partial('field', ['name' => 'urutan', 'label' => 'Urutan tampil', 'type' => 'number', 'value' => $v('urutan') ?: 100, 'hint' => 'Angka kecil tampil lebih dulu di halaman Struktur (1 = ketua).', 'attrs' => 'min="1" max="999"']) ?>
    <?= partial('field', ['name' => 'periode_mulai', 'label' => 'Periode mulai', 'type' => 'date', 'value' => $v('periode_mulai'), 'required' => true]) ?>
    <?= partial('field', ['name' => 'periode_selesai', 'label' => 'Periode selesai', 'type' => 'date', 'value' => $v('periode_selesai'), 'optional' => true, 'hint' => 'Kosongkan bila masih menjabat. Isi untuk mengakhiri/mengganti periode.']) ?>
    <?= partial('field', ['name' => 'sk_nomor', 'label' => 'Nomor SK', 'value' => $v('sk_nomor'), 'optional' => true, 'attrs' => 'maxlength="80"']) ?>
    <div class="stack">
      <?php if ($isEdit && $p['sk_file']): ?><div class="current-file"><?= icon('file-text') ?><a href="<?= url('/admin/file/sk/' . $p['sk_file']) ?>" target="_blank" rel="noopener">Lihat SK saat ini</a></div><?php endif; ?>
      <?= partial('file', ['name' => 'sk_file', 'label' => 'Arsip SK (PDF)', 'optional' => true, 'accept' => 'application/pdf', 'icon' => 'file-text', 'hint' => 'PDF, maks. ' . App\Core\Upload::human((int) config('upload.max_doc')), 'max' => (int) config('upload.max_doc')]) ?>
    </div>
  </div>
  <div class="card__foot form-actions form-actions--end">
    <a class="btn btn--ghost" href="<?= url('/admin/pengurus') ?>">Batal</a>
    <button type="submit" class="btn btn--primary"><?= icon('check', 'icon-sm') ?> Simpan</button>
  </div>
</form>
