<?php
use App\Models\Aspirasi;
$kat = array_combine(Aspirasi::KATEGORI, Aspirasi::KATEGORI);
?>
<?= partial('page-hero', ['heading' => 'Aspirasi & Pengaduan', 'lead' => 'Sampaikan saran, laporan, atau pengaduan. Setiap aspirasi mendapat nomor tiket yang dapat dilacak statusnya.', 'crumbs' => [['Aspirasi', null]]]) ?>

<section class="section">
  <div class="container layout-aside">
    <form class="card card__body" method="post" action="<?= url('/aspirasi') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="_ts" value="<?= time() ?>">
      <div class="hp-field" aria-hidden="true"><label for="website">Website</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>

      <div class="form-grid form-grid--2">
        <?= partial('field', ['name' => 'nama', 'label' => 'Nama', 'value' => old('nama'), 'required' => true, 'attrs' => 'maxlength="120" autocomplete="name"']) ?>
        <?= partial('field', ['name' => 'kontak', 'label' => 'Email atau No. HP', 'value' => old('kontak'), 'required' => true, 'hint' => 'Untuk dihubungi bila perlu klarifikasi. Tidak ditampilkan publik.', 'attrs' => 'maxlength="160"']) ?>
        <?= partial('field', ['name' => 'kategori', 'label' => 'Kategori', 'type' => 'select', 'options' => $kat, 'value' => old('kategori'), 'required' => true]) ?>
        <?= partial('field', ['name' => 'judul', 'label' => 'Judul singkat', 'value' => old('judul'), 'required' => true, 'attrs' => 'maxlength="160" minlength="5"']) ?>
        <?= partial('field', ['name' => 'isi', 'label' => 'Isi aspirasi', 'type' => 'textarea', 'value' => old('isi'), 'required' => true, 'hint' => 'Jelaskan secara rinci: apa, di mana, kapan. Minimal 20 karakter.', 'attrs' => 'rows="7" minlength="20" maxlength="5000" data-count="isi-count"', 'class' => 'span-all']) ?>
        <div class="span-all">
          <?= partial('file', ['name' => 'lampiran', 'label' => 'Lampiran', 'optional' => true, 'accept' => 'image/jpeg,image/png,image/webp,application/pdf', 'icon' => 'paperclip', 'hint' => 'Foto (JPG/PNG/WebP) atau PDF, maks. ' . App\Core\Upload::human((int) config('upload.max_doc')) . '.', 'max' => (int) config('upload.max_doc')]) ?>
        </div>
      </div>
      <p class="hint mt-2" id="isi-count" aria-live="polite"></p>
      <div class="form-actions mt-4">
        <button type="submit" class="btn btn--primary"><?= icon('send', 'icon-sm') ?> Kirim Aspirasi</button>
      </div>
    </form>
    <aside>
      <div class="aside-block">
        <h3>Sudah Punya Tiket?</h3>
        <p class="text-sm text-muted">Lacak perkembangan aspirasi Anda dengan nomor tiket.</p>
        <a class="btn btn--dark btn--block" href="<?= url('/aspirasi/status') ?>"><?= icon('ticket', 'icon-sm') ?> Cek Status Tiket</a>
      </div>
      <div class="aside-block">
        <h3>Alur Penanganan</h3>
        <ol class="mission-list">
          <li>Aspirasi diterima dan mendapat nomor tiket.</li>
          <li>Pengurus meninjau dan memproses.</li>
          <li>Tanggapan dapat dilihat melalui cek status.</li>
        </ol>
      </div>
    </aside>
  </div>
</section>
