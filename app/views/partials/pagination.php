<?php /** @var App\Core\Paginator $pager */
if ($pager->total === 0) {
    return;
}
$label = $label ?? 'data';
?>
<nav class="pagination" aria-label="Navigasi halaman">
  <p class="pagination__info">Menampilkan <?= number($pager->from()) ?>–<?= number($pager->to()) ?> dari <?= number($pager->total) ?> <?= e($label) ?></p>
  <?php if ($pager->pages > 1): ?>
  <ul class="pagination__list">
    <?php if ($pager->current() > 1): ?>
      <li><a href="<?= e($pager->url($pager->current() - 1)) ?>" aria-label="Halaman sebelumnya"><?= icon('chevron-left', 'icon-sm') ?></a></li>
    <?php endif; ?>
    <?php foreach ($pager->window() as $p): ?>
      <?php if ($p === null): ?>
        <li><span class="is-gap">…</span></li>
      <?php elseif ($p === $pager->current()): ?>
        <li><span class="is-current" aria-current="page"><?= $p ?></span></li>
      <?php else: ?>
        <li><a href="<?= e($pager->url($p)) ?>"><?= $p ?></a></li>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($pager->current() < $pager->pages): ?>
      <li><a href="<?= e($pager->url($pager->current() + 1)) ?>" aria-label="Halaman berikutnya"><?= icon('chevron-right', 'icon-sm') ?></a></li>
    <?php endif; ?>
  </ul>
  <?php endif; ?>
</nav>
