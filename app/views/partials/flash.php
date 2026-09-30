<?php
$map = ['success' => ['alert--success', 'check-circle'], 'error' => ['alert--error', 'alert'], 'warning' => ['alert--warning', 'alert'], 'info' => ['alert--info', 'info']];
$list = flashes();
if (!$list) {
    return;
}
?>
<div class="flash-stack" role="status" aria-live="polite">
  <?php foreach ($list as $type => $msg): [$cls, $ic] = $map[$type] ?? $map['info']; ?>
    <div class="alert <?= $cls ?>">
      <?= icon($ic) ?>
      <div><?= e($msg) ?></div>
      <button type="button" class="alert__close" aria-label="Tutup pesan"><?= icon('x', 'icon-sm') ?></button>
    </div>
  <?php endforeach; ?>
</div>
