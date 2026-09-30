<div class="page-head">
  <div>
    <?php if (!empty($crumbs)): ?>
      <nav class="crumbs" aria-label="Breadcrumb">
        <?php foreach ($crumbs as $i => [$label, $href]): ?>
          <?php if ($i): ?><?= icon('chevron-right', 'icon-sm') ?><?php endif; ?>
          <?php if ($href): ?><a href="<?= url($href) ?>"><?= e($label) ?></a><?php else: ?><span><?= e($label) ?></span><?php endif; ?>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>
    <h1><?= e($heading) ?></h1>
  </div>
  <?php if (!empty($actions)): ?><div class="page-head__actions"><?= $actions ?></div><?php endif; ?>
</div>
