<div class="modal" id="confirm-modal" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-message">
  <div class="modal__backdrop" data-confirm-cancel></div>
  <div class="modal__box">
    <div class="modal__body">
      <div class="modal__icon"><?= icon('alert') ?></div>
      <div>
        <h2 id="confirm-title">Konfirmasi</h2>
        <p id="confirm-message"></p>
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn btn--ghost" data-confirm-cancel>Batal</button>
      <button type="button" class="btn btn--danger" data-confirm-ok>Ya, lanjutkan</button>
    </div>
  </div>
</div>
