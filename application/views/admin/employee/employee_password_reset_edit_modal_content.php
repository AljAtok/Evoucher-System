
<input type="hidden" name="id" value="<?= encode($user->user_id) ?>">
<div class="form-group">
    <label for="password">Temporary Password: </label>
    <input type="text" id="password" name="password" class="form-control form-control-sm" value="<?= generate_random(7) ?>" required>
</div>