<?php use App\Models\Aspirasi; ?>
<?= partial('admin-head', ['heading' => 'Tiket ' . $a['tiket'], 'crumbs' => [['Dashboard', '/admin'], ['Aspirasi', '/admin/aspirasi'], [$a['tiket'], null]]]) ?>

<?php if ($a['archived_at']): ?>
  <div class="alert alert--warning mb-6"><?= icon('archive') ?><div>Tiket ini diarsipkan pada <?= tanggal($a['archived_at'], true) ?>.</div></div>
<?php endif; ?>

<div class="detail-grid">
  <div class="stack">
    <section class="card">
      <div class="card__head"><h2><?= e($a['judul']) ?></h2><?= status_badge($a['status']) ?></div>
      <div class="card__body">
        <div class="prose"><p><?= nl2br(e($a['isi'])) ?></p></div>
        <?php if ($a['lampiran']): ?>
          <div class="current-file mt-4"><?= icon('paperclip') ?><a href="<?= url('/admin/file/aspirasi/' . $a['lampiran']) ?>" target="_blank" rel="noopener">Buka lampiran (<?= str_ends_with($a['lampiran'], '.pdf') ? 'PDF' : 'gambar' ?>)</a></div>
        <?php endif; ?>
      </div>
    </section>

    <?php if (can('aspirasi.manage')): ?>
    <section class="card">
      <div class="card__head"><h2>Tanggapi</h2></div>
      <form class="card__body form-grid" method="post" action="<?= url('/admin/aspirasi/' . $a['id']) ?>">
        <?= csrf_field() ?>
        <?= partial('field', ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => Aspirasi::STATUS, 'value' => old('status', $a['status']), 'required' => true]) ?>
        <?= partial('field', ['name' => 'tanggapan', 'label' => 'Tanggapan untuk pelapor', 'type' => 'textarea', 'value' => old('tanggapan', $a['tanggapan'] ?? ''), 'hint' => 'Ditampilkan kepada pelapor pada halaman cek status tiket. Wajib diisi bila status bukan "Baru".', 'attrs' => 'rows="5" maxlength="3000"']) ?>
        <div class="form-actions"><button class="btn btn--primary" type="submit"><?= icon('send', 'icon-sm') ?> Simpan Tanggapan</button></div>
      </form>
    </section>
    <?php elseif ($a['tanggapan']): ?>
    <section class="card"><div class="card__head"><h2>Tanggapan</h2></div><div class="card__body"><p class="mb-0"><?= nl2br(e($a['tanggapan'])) ?></p></div></section>
    <?php endif; ?>
  </div>

  <div class="stack">
    <section class="card">
      <div class="card__head"><h2>Info Pelapor</h2></div>
      <div class="card__body">
        <dl class="dl">
          <dt>Nama</dt><dd><?= e($a['nama']) ?></dd>
          <dt>Kontak</dt><dd><?= e($a['kontak']) ?></dd>
          <dt>Kategori</dt><dd><?= e($a['kategori']) ?></dd>
          <dt>Masuk</dt><dd><?= tanggal($a['created_at'], true) ?></dd>
          <dt>Petugas</dt><dd><?= e($a['petugas'] ?? '—') ?></dd>
          <dt>Diperbarui</dt><dd><?= tanggal($a['updated_at'], true) ?></dd>
        </dl>
      </div>
    </section>
    <?php if (can('aspirasi.manage')): ?>
    <section class="card">
      <div class="card__body">
        <form method="post" action="<?= url('/admin/aspirasi/' . $a['id'] . '/arsip') ?>"<?= $a['archived_at'] ? '' : ' data-confirm="Arsipkan tiket ini? Tiket tetap dapat dicek pelapor dan dapat dikeluarkan dari arsip." data-confirm-title="Arsipkan tiket" data-confirm-ok="Arsipkan"' ?>>
          <?= csrf_field() ?>
          <button class="btn btn--outline btn--block" type="submit"><?= icon($a['archived_at'] ? 'rotate' : 'archive', 'icon-sm') ?> <?= $a['archived_at'] ? 'Keluarkan dari Arsip' : 'Arsipkan Tiket' ?></button>
        </form>
      </div>
    </section>
    <?php endif; ?>
  </div>
</div>
