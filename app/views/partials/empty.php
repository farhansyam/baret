<div class="empty">
  <div class="empty__icon"><?= icon($icon ?? 'inbox') ?></div>
  <h3><?= e($title ?? 'Belum ada data') ?></h3>
  <?php if (!empty($text)): ?><p><?= e($text) ?></p><?php endif; ?>
  <?php if (!empty($action)): ?>
    <a class="btn <?= e($actionClass ?? 'btn--primary') ?>" href="<?= e($action[1]) ?>"><?= icon($action[2] ?? 'plus', 'icon-sm') ?> <?= e($action[0]) ?></a>
  <?php endif; ?>
</div>
