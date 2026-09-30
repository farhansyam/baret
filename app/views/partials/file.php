<?php
/** Input file dengan area seret-lepas. $name, $label, $accept, $hint, $required, $multiple, $max (bytes) */
$id = 'f-' . $name;
$err = error($name);
?>
<div class="field<?= $err ? ' has-error' : '' ?>">
  <span class="label" id="<?= e($id) ?>-label"><?= e($label) ?><?php if (!empty($required)): ?><span class="req" aria-hidden="true">*</span><?php elseif (!empty($optional)): ?> <span class="opt">(opsional)</span><?php endif; ?></span>
  <div class="file-drop">
    <span class="file-drop__icon"><?= icon($icon ?? 'upload') ?></span>
    <span class="file-drop__text">
      <strong><?= e($cta ?? 'Pilih file atau seret ke sini') ?></strong>
      <span class="hint"><?= e($hint ?? '') ?></span>
    </span>
    <input type="file" id="<?= e($id) ?>" name="<?= e($name) ?><?= !empty($multiple) ? '[]' : '' ?>" accept="<?= e($accept ?? '') ?>"
      aria-labelledby="<?= e($id) ?>-label"<?= !empty($required) ? ' required' : '' ?><?= !empty($multiple) ? ' multiple' : '' ?>
      <?= !empty($max) ? 'data-max="' . (int) $max . '"' : '' ?><?= $err ? ' aria-invalid="true" aria-describedby="' . e($id) . '-err"' : '' ?>>
  </div>
  <?php if ($err): ?><p class="field-error" id="<?= e($id) ?>-err"><?= icon('alert') ?><?= e($err) ?></p><?php endif; ?>
</div>
