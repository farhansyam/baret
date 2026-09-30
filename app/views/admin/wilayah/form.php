<?php
use App\Models\Wilayah;
$isEdit = $w !== null;
$v = fn(string $k) => old($k, $w[$k] ?? '');
$parentOpts = [];
foreach ($parents as $o) {
    if ($o['tingkat'] === 'ranting' || ($isEdit && $o['id'] === (int) $w['id'])) continue;
    $parentOpts[$o['id']] = $o['label'] . ' (' . Wilayah::TINGKAT[$o['tingkat']] . ')';
}
?>
<?= partial('admin-head', ['heading' => $isEdit ? 'Ubah Wilayah' : 'Tambah Wilayah', 'crumbs' => [['Dashboard', '/admin'], ['Struktur Wilayah', '/admin/wilayah'], [$isEdit ? $w['nama'] : 'Tambah', null]]]) ?>

<form class="card" method="post" action="<?= $isEdit ? url('/admin/wilayah/' . $w['id']) : url('/admin/wilayah') ?>">
  <?= csrf_field() ?>
  <div class="card__body form-grid form-grid--2">
    <?= partial('field', ['name' => 'nama', 'label' => 'Nama', 'value' => $v('nama'), 'required' => true, 'placeholder' => 'Mis. Cabang Jakarta Selatan', 'attrs' => 'maxlength="120"']) ?>
    <?= partial('field', ['name' => 'kode', 'label' => 'Kode', 'value' => $v('kode'), 'required' => true, 'hint' => '2–12 huruf kapital/angka/tanda hubung, mis. JKS. Dipakai di NIA.', 'attrs' => 'maxlength="12" pattern="[A-Za-z0-9-]{2,12}"']) ?>
    <?= partial('field', ['name' => 'tingkat', 'label' => 'Tingkat', 'type' => 'select', 'options' => Wilayah::TINGKAT, 'value' => $v('tingkat'), 'required' => true]) ?>
    <?= partial('field', ['name' => 'parent_id', 'label' => 'Induk', 'type' => 'select', 'options' => $parentOpts, 'value' => $v('parent_id'), 'placeholder' => '— Tanpa induk (khusus Pusat) —', 'hint' => 'Wilayah → induk Pusat; Cabang → induk Wilayah; Ranting → induk Cabang.']) ?>
    <?= partial('field', ['name' => 'alamat', 'label' => 'Alamat sekretariat', 'type' => 'textarea', 'value' => $v('alamat'), 'optional' => true, 'attrs' => 'rows="2" maxlength="255"', 'class' => 'span-all']) ?>
  </div>
  <div class="card__foot form-actions form-actions--end">
    <a class="btn btn--ghost" href="<?= url('/admin/wilayah') ?>">Batal</a>
    <button type="submit" class="btn btn--primary"><?= icon('check', 'icon-sm') ?> Simpan</button>
  </div>
</form>
