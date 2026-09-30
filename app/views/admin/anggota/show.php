<?php
use App\Models\Member;
$archived = (bool) $m['deleted_at'];
$actions = '';
if (can('members.manage') && !$archived) {
    $actions .= '<a class="btn btn--outline" href="' . url('/admin/anggota/' . $m['id'] . '/ubah') . '">' . icon('pencil', 'icon-sm') . ' Ubah Data</a>';
}
$fotoUrl = $m['foto'] ? url('/admin/file/members/' . $m['foto']) : null;
?>
<?= partial('admin-head', ['heading' => $m['nama'], 'crumbs' => [['Dashboard', '/admin'], ['Anggota', '/admin/anggota'], ['Detail', null]], 'actions' => $actions]) ?>

<?php if ($archived): ?>
  <div class="alert alert--warning mb-6"><?= icon('archive') ?><div>Anggota ini diarsipkan pada <?= tanggal($m['deleted_at'], true) ?> dan tidak tampil di daftar aktif.</div></div>
<?php endif; ?>

<div class="detail-grid">
  <div class="stack">
    <section class="card">
      <div class="card__body">
        <div class="profile-head">
          <div class="photo-box">
            <?php if ($fotoUrl): ?><img src="<?= e($fotoUrl) ?>" alt="Pas foto <?= e($m['nama']) ?>" width="120" height="160"><?php else: ?><?= icon('user', 'icon-lg') ?><?php endif; ?>
          </div>
          <div class="grow">
            <div class="row mb-2"><?= status_badge($m['status']) ?> <span class="text-sm text-muted">Reg. <span class="mono"><?= e($m['no_registrasi']) ?></span></span></div>
            <h2 class="mb-2"><?= e($m['nama']) ?></h2>
            <?php if ($m['nia']): ?><span class="nia-chip"><?= e($m['nia']) ?></span><?php else: ?><span class="text-sm text-muted">NIA terbit otomatis saat pendaftar disetujui.</span><?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <section class="card">
      <div class="card__head"><h2>Data Anggota</h2></div>
      <div class="card__body">
        <dl class="dl">
          <dt>NIK</dt><dd class="mono"><?= can('members.manage') ? e($m['nik'] ?? '—') : e(mask_nik($m['nik'])) ?></dd>
          <dt>Tempat, tgl lahir</dt><dd><?= e($m['tempat_lahir']) ?>, <?= tanggal($m['tgl_lahir']) ?></dd>
          <dt>Jenis kelamin</dt><dd><?= $m['jk'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></dd>
          <dt>Pekerjaan</dt><dd><?= e($m['pekerjaan'] ?: '—') ?></dd>
          <dt>No. HP/WA</dt><dd><a href="https://wa.me/<?= e(preg_replace('/^0/', '62', $m['telepon'])) ?>" target="_blank" rel="noopener"><?= e($m['telepon']) ?></a></dd>
          <dt>Email</dt><dd><?= $m['email'] ? '<a href="mailto:' . e($m['email']) . '">' . e($m['email']) . '</a>' : '—' ?></dd>
          <dt>Alamat</dt><dd><?= nl2br(e($m['alamat'])) ?></dd>
          <dt>Wilayah/Cabang</dt><dd><?= e($m['wilayah_nama']) ?> <span class="text-muted">(<?= e($m['wilayah_kode']) ?>)</span></dd>
          <dt>Foto KTP</dt><dd><?php if ($m['ktp_file'] && can('members.manage')): ?><a href="<?= url('/admin/file/ktp/' . $m['ktp_file']) ?>" target="_blank" rel="noopener"><?= icon('id-card', 'icon-sm') ?> Lihat KTP</a><?php elseif ($m['ktp_file']): ?>Ada (akses terbatas)<?php else: ?>—<?php endif; ?></dd>
          <dt>Persetujuan data</dt><dd><?= $m['consent_at'] ? 'Disetujui ' . tanggal($m['consent_at'], true) : '—' ?></dd>
          <dt>Terdaftar</dt><dd><?= tanggal($m['created_at'], true) ?></dd>
          <?php if ($m['verified_at']): ?><dt>Diverifikasi</dt><dd><?= tanggal($m['verified_at'], true) ?> oleh <?= e($m['verifier_nama'] ?? '—') ?></dd><?php endif; ?>
        </dl>
      </div>
    </section>

    <?php if ($jabatan): ?>
    <section class="card">
      <div class="card__head"><h2>Riwayat Jabatan</h2></div>
      <ul class="list-plain">
        <?php foreach ($jabatan as $j): ?>
          <li><?= icon('briefcase') ?><div class="grow"><strong><?= e($j['jabatan']) ?></strong> — <?= e($j['wilayah_nama']) ?><div class="table__sub"><?= tanggal($j['periode_mulai']) ?> s.d. <?= $j['periode_selesai'] ? tanggal($j['periode_selesai']) : 'sekarang' ?></div></div></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>
  </div>

  <div class="stack">
    <?php if (!$archived && can('members.verify') && !empty(Member::TRANSITIONS[$m['status']])): ?>
    <section class="card">
      <div class="card__head"><h2><?= $m['status'] === 'menunggu' ? 'Verifikasi' : 'Ubah Status' ?></h2></div>
      <form class="card__body stack" method="post" action="<?= url('/admin/anggota/' . $m['id'] . '/status') ?>" data-status-form>
        <?= csrf_field() ?>
        <div class="field<?= error('catatan') ? ' has-error' : '' ?>">
          <label class="label" for="catatan">Catatan <span class="opt">(wajib bila menolak)</span></label>
          <textarea class="textarea" id="catatan" name="catatan" rows="3" maxlength="255" placeholder="Mis. data sudah dicek dengan KTP"><?= e(old('catatan')) ?></textarea>
          <?php if ($err = error('catatan')): ?><p class="field-error"><?= icon('alert') ?><?= e($err) ?></p><?php endif; ?>
        </div>
        <div class="row">
          <?php if ($m['status'] === 'menunggu'): ?>
            <button class="btn btn--success" type="submit" name="status" value="aktif" data-confirm="Setujui pendaftaran <?= e($m['nama']) ?>? NIA akan diterbitkan otomatis dan tidak dapat diubah." data-confirm-title="Setujui pendaftar" data-confirm-ok="Setujui" data-confirm-tone="primary"><?= icon('check', 'icon-sm') ?> Setujui</button>
            <button class="btn btn--danger" type="submit" name="status" value="ditolak" data-confirm="Tolak pendaftaran <?= e($m['nama']) ?>?" data-confirm-title="Tolak pendaftar" data-confirm-ok="Tolak"><?= icon('x', 'icon-sm') ?> Tolak</button>
          <?php elseif ($m['status'] === 'aktif'): ?>
            <button class="btn btn--danger" type="submit" name="status" value="nonaktif" data-confirm="Nonaktifkan keanggotaan <?= e($m['nama']) ?>? NIA tetap tersimpan." data-confirm-title="Nonaktifkan anggota" data-confirm-ok="Nonaktifkan"><?= icon('x-circle', 'icon-sm') ?> Nonaktifkan</button>
          <?php elseif ($m['status'] === 'nonaktif'): ?>
            <button class="btn btn--success" type="submit" name="status" value="aktif"><?= icon('rotate', 'icon-sm') ?> Aktifkan Kembali</button>
          <?php elseif ($m['status'] === 'ditolak'): ?>
            <button class="btn btn--outline" type="submit" name="status" value="menunggu"><?= icon('rotate', 'icon-sm') ?> Buka Kembali untuk Ditinjau</button>
          <?php endif; ?>
        </div>
      </form>
    </section>
    <?php endif; ?>

    <section class="card">
      <div class="card__head"><h2>Riwayat Status</h2></div>
      <div class="card__body">
        <?php if ($history): ?>
          <ol class="timeline">
            <?php foreach ($history as $h): ?>
              <li>
                <time datetime="<?= e($h['waktu']) ?>"><?= tanggal($h['waktu'], true, true) ?></time>
                <?= $h['status_lama'] ? status_badge($h['status_lama']) . ' → ' : '' ?><?= status_badge($h['status_baru']) ?>
                <div class="text-sm mt-2">oleh <?= e($h['oleh_nama'] ?? 'Sistem/Pendaftar') ?><?= $h['catatan'] ? ' — “' . e($h['catatan']) . '”' : '' ?></div>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php else: ?>
          <p class="text-muted mb-0">Belum ada riwayat.</p>
        <?php endif; ?>
      </div>
    </section>

    <?php if (can('members.manage')): ?>
    <section class="card danger-zone">
      <div class="card__head"><h2>Arsip</h2></div>
      <div class="card__body">
        <?php if ($archived): ?>
          <p class="text-sm text-muted">Pulihkan agar anggota kembali tampil di daftar aktif.</p>
          <form method="post" action="<?= url('/admin/anggota/' . $m['id'] . '/pulihkan') ?>">
            <?= csrf_field() ?>
            <button class="btn btn--outline btn--block" type="submit"><?= icon('rotate', 'icon-sm') ?> Pulihkan dari Arsip</button>
          </form>
        <?php else: ?>
          <p class="text-sm text-muted">Data tidak dihapus permanen (soft delete) dan masih dapat dipulihkan.</p>
          <form method="post" action="<?= url('/admin/anggota/' . $m['id'] . '/arsip') ?>" data-confirm="Arsipkan anggota <?= e($m['nama']) ?>? Data dapat dipulihkan dari tab Arsip." data-confirm-title="Arsipkan anggota" data-confirm-ok="Arsipkan">
            <?= csrf_field() ?>
            <button class="btn btn--danger btn--block" type="submit"><?= icon('archive', 'icon-sm') ?> Arsipkan Anggota</button>
          </form>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>
</div>
