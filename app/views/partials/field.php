<?php
/**
 * Field form standar.
 * $name, $label, $type (text|email|tel|date|datetime-local|number|password|textarea|select), $value,
 * $required, $hint, $options [value => label], $attrs (string atribut tambahan), $placeholder, $class
 */
$type = $type ?? 'text';
$id = $id ?? 'f-' . $name;
$err = error($name);
$value = $value ?? '';
$required = $required ?? false;
$attrs = $attrs ?? '';
$describedBy = trim((!empty($hint) ? "$id-hint " : '') . ($err ? "$id-err" : ''));
$common = 'id="' . e($id) . '" name="' . e($name) . '"' . ($required ? ' required' : '') . ($err ? ' aria-invalid="true"' : '')
    . ($describedBy ? ' aria-describedby="' . e($describedBy) . '"' : '') . (!empty($placeholder) ? ' placeholder="' . e($placeholder) . '"' : '') . ' ' . $attrs;
?>
<div class="field<?= $err ? ' has-error' : '' ?><?= !empty($class) ? ' ' . e($class) : '' ?>">
  <label class="label" for="<?= e($id) ?>"><?= e($label) ?><?php if ($required): ?><span class="req" aria-hidden="true">*</span><?php elseif (!empty($optional)): ?> <span class="opt">(opsional)</span><?php endif; ?></label>
  <?php if ($type === 'textarea'): ?>
    <textarea class="textarea" <?= $common ?>><?= e($value) ?></textarea>
  <?php elseif ($type === 'select'): ?>
    <select class="select" <?= $common ?>>
      <option value=""><?= e($placeholder ?? '— Pilih —') ?></option>
      <?php foreach ($options as $ov => $ol): ?>
        <option value="<?= e($ov) ?>"<?= selected($value, $ov) ?>><?= e($ol) ?></option>
      <?php endforeach; ?>
    </select>
  <?php else: ?>
    <input class="input" type="<?= e($type) ?>" value="<?= e($value) ?>" <?= $common ?>>
  <?php endif; ?>
  <?php if (!empty($hint)): ?><p class="hint" id="<?= e($id) ?>-hint"><?= e($hint) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="field-error" id="<?= e($id) ?>-err"><?= icon('alert') ?><?= e($err) ?></p><?php endif; ?>
</div>
