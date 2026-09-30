<?php
/**
 * Grafik batang SVG (satu seri). $data = [['label','full','value'], ...]
 * Batang tipis, ujung atas membulat 4px menempel ke garis dasar, grid samar, label nilai selektif (maks & terakhir),
 * tooltip saat hover/fokus, dan tabel alternatif untuk aksesibilitas.
 */
$W = 720; $H = 260; $padL = 36; $padR = 8; $padT = 24; $padB = 30;
$plotW = $W - $padL - $padR; $plotH = $H - $padT - $padB;
$values = array_column($data, 'value');
$max = max(1, ...$values);
// Skala "rapi": 4 garis bantu dengan langkah bilangan bulat 1/2/5 × 10^n
$ticks = 4;
$raw = $max / $ticks;
$mag = 10 ** floor(log10($raw));
$step = 10 * $mag;
foreach ([1, 2, 5, 10] as $m) {
    if ($m * $mag >= $raw) { $step = $m * $mag; break; }
}
$step = max(1, (int) ceil($step));
$nice = $step * $ticks;
$n = count($data);
$slot = $plotW / $n;
$bw = min(28, $slot * 0.56);
$y = fn($v) => $padT + $plotH - ($v / $nice) * $plotH;
$maxIdx = array_search(max($values), $values, true);
$lastIdx = $n - 1;
$total = array_sum($values);
?>
<div class="chart">
  <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-labelledby="chart-title chart-desc">
    <title id="chart-title"><?= e($title ?? 'Grafik') ?></title>
    <desc id="chart-desc">Total <?= $total ?> pendaftar dalam <?= $n ?> bulan terakhir. Tertinggi <?= max($values) ?> pada <?= e($data[$maxIdx]['full']) ?>.</desc>
    <?php for ($i = 0; $i <= $ticks; $i++): $v = $step * $i; $yy = round($y($v), 1); ?>
      <line class="grid-line" x1="<?= $padL ?>" x2="<?= $W - $padR ?>" y1="<?= $yy ?>" y2="<?= $yy ?>"/>
      <text class="axis-label" x="<?= $padL - 8 ?>" y="<?= $yy + 4 ?>" text-anchor="end"><?= number((int) $v) ?></text>
    <?php endfor; ?>
    <?php foreach ($data as $i => $d):
        $cx = $padL + $slot * $i + $slot / 2;
        $x = round($cx - $bw / 2, 1);
        $top = round($y($d['value']), 1);
        $base = $padT + $plotH;
        $h = $base - $top;
        $r = min(4, $h, $bw / 2);
    ?>
      <g class="bar-group" tabindex="0" data-label="<?= e($d['full']) ?>" data-value="<?= (int) $d['value'] ?>" aria-label="<?= e($d['full']) ?>: <?= (int) $d['value'] ?> pendaftar">
        <rect class="bar-hit" x="<?= round($cx - $slot / 2, 1) ?>" y="<?= $padT ?>" width="<?= round($slot, 1) ?>" height="<?= $plotH ?>"/>
        <?php if ($h > 0): ?>
          <path class="bar" d="M<?= $x ?>,<?= $base ?> V<?= $top + $r ?> Q<?= $x ?>,<?= $top ?> <?= $x + $r ?>,<?= $top ?> H<?= $x + $bw - $r ?> Q<?= $x + $bw ?>,<?= $top ?> <?= $x + $bw ?>,<?= $top + $r ?> V<?= $base ?> Z"/>
        <?php endif; ?>
        <?php if (($i === $maxIdx || $i === $lastIdx) && $d['value'] > 0): ?>
          <text class="value-label" x="<?= round($cx, 1) ?>" y="<?= $top - 7 ?>" text-anchor="middle"><?= (int) $d['value'] ?></text>
        <?php endif; ?>
        <text class="axis-label" x="<?= round($cx, 1) ?>" y="<?= $H - 8 ?>" text-anchor="middle"><?= e($d['label']) ?></text>
      </g>
    <?php endforeach; ?>
    <line x1="<?= $padL ?>" x2="<?= $W - $padR ?>" y1="<?= $padT + $plotH ?>" y2="<?= $padT + $plotH ?>" stroke="var(--gray-300)" stroke-width="1"/>
  </svg>
  <div class="chart__tip" aria-hidden="true"></div>
</div>
<details class="mt-2">
  <summary class="text-sm text-muted">Lihat data dalam tabel</summary>
  <table class="table table--plain mt-2">
    <thead><tr><th scope="col">Bulan</th><th scope="col">Pendaftar</th></tr></thead>
    <tbody><?php foreach ($data as $d): ?><tr><td><?= e($d['full']) ?></td><td><?= (int) $d['value'] ?></td></tr><?php endforeach; ?></tbody>
  </table>
</details>
