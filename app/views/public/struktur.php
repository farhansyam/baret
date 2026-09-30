<?php
use App\Models\Wilayah;

$renderNode = function (array $node, int $depth) use (&$renderNode, $pengurus) {
    $people = $pengurus[(int) $node['id']] ?? [];
    ob_start(); ?>
    <details class="org-node"<?= $depth < 1 ? ' open' : '' ?>>
      <summary>
        <span class="org-level org-level--<?= e($node['tingkat']) ?>"><?= e(Wilayah::TINGKAT[$node['tingkat']]) ?></span>
        <span>
          <span class="org-node__name"><?= e($node['nama']) ?></span>
          <span class="org-node__kode">· <?= e($node['kode']) ?><?= $node['children'] ? ' · ' . count($node['children']) . ' unit di bawahnya' : '' ?></span>
        </span>
        <?= icon('chevron-right', 'chev') ?>
      </summary>
      <div class="org-node__body">
        <?php if ($people): ?>
          <div class="people">
            <?php foreach ($people as $p): ?>
              <div class="person">
                <span class="avatar<?= $p['urutan'] <= 1 ? ' avatar--red' : '' ?>" aria-hidden="true"><?= e(initials($p['nama'])) ?></span>
                <div><div class="person__name"><?= e($p['nama']) ?></div><div class="person__role"><?= e($p['jabatan']) ?></div></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-sm text-muted mb-0">Susunan pengurus belum diumumkan.</p>
        <?php endif; ?>
        <?php if ($node['children']): ?>
          <div class="org-children">
            <?php foreach ($node['children'] as $c) echo $renderNode($c, $depth + 1); ?>
          </div>
        <?php endif; ?>
      </div>
    </details>
    <?php return ob_get_clean();
};
?>
<?= partial('page-hero', ['heading' => 'Struktur Organisasi', 'lead' => 'Susunan kepengurusan BARET dari tingkat pusat, wilayah, cabang, hingga ranting.', 'crumbs' => [['Struktur', null]]]) ?>

<section class="section">
  <div class="container">
    <?php if ($tree): ?>
      <div class="org-root">
        <?php foreach ($tree as $n) echo $renderNode($n, 0); ?>
      </div>
      <p class="text-sm text-muted mt-6"><?= icon('info', 'icon-sm') ?> Klik nama unit untuk membuka atau menutup susunan di bawahnya. Hanya pengurus dengan masa jabatan aktif yang ditampilkan.</p>
    <?php else: ?>
      <?= partial('empty', ['icon' => 'network', 'title' => 'Struktur belum tersedia']) ?>
    <?php endif; ?>
  </div>
</section>
