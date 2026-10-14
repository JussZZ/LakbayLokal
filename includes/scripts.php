<?php /** Confirm dialog + shared scripts. Optional: $extraJs (array of file names). */ ?>
<dialog id="confirmDialog" class="dialog" aria-labelledby="confirmTitle">
  <form method="dialog">
    <h2 id="confirmTitle">Please confirm</h2>
    <p id="confirmMessage"></p>
    <div class="dialog__actions">
      <button class="btn btn--ghost" value="cancel">Cancel</button>
      <button class="btn btn--amber" value="ok" id="confirmOk">Confirm</button>
    </div>
  </form>
</dialog>
<script src="<?= e(asset('js/main.js')) ?>"></script>
<script src="<?= e(asset('js/validation.js')) ?>"></script>
<?php foreach (($extraJs ?? []) as $js): ?>
<script src="<?= e(asset('js/' . $js)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
