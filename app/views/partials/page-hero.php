<section class="page-hero">
  <div class="container">
    <ol class="breadcrumb" aria-label="Breadcrumb">
      <li><a href="<?= url('/') ?>">Beranda</a></li>
      <?php foreach ($crumbs ?? [] as [$label, $href]): ?>
        <li><?php if ($href): ?><a href="<?= url($href) ?>"><?= e($label) ?></a><?php else: ?><span aria-current="page"><?= e($label) ?></span><?php endif; ?></li>
      <?php endforeach; ?>
    </ol>
    <h1><?= e($heading) ?></h1>
    <?php if (!empty($lead)): ?><p><?= e($lead) ?></p><?php endif; ?>
  </div>
</section>
