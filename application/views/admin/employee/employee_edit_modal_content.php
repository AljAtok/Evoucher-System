
<input type="hidden" name="id" value="<?= encode($user->user_id) ?>">
<div class="form-group">
    <label for="fname">First Name: </label>
    <input type="text" id="fname" name="fname" class="form-control form-control-sm" value="<?= $user->user_fname ?>" required>
</div>
<div class="form-group">
    <label for="lname">Last Name: </label>
    <input type="text" id="lname" name="lname" class="form-control form-control-sm" value="<?= $user->user_lname ?>" required>
</div>
<div class="form-group">
    <label for="employee_no">Employee No: </label>
    <input type="text" id="employee_no" name="employee_no" class="form-control form-control-sm" value="<?= $user->user_employee_no ?>" required>
</div>
<div class="form-group">
    <label for="email">Employee Email: </label>
    <input type="email" id="email" name="email" class="form-control form-control-sm" value="<?= $user->user_email ?>" required>
</div>
<div class="form-group">
    <label>User Type:</label>
    <select name="user_type" class="form-control form-control-sm" required>
        <option value="">Select User Type: </option>
        <?php foreach($user_types as $row):?>
        <?php $is_selected = ($row->user_type_id == $user->user_type_id) ? 'selected' : '' ?>
        <option value="<?=encode($row->user_type_id)?>" <?=$is_selected?> ><?=$row->user_type_name?></option>
        <?php endforeach;?>
    </select>
</div>
<div class="form-group">
    <label>Allowed Access:</label>
    <select name="access[]" class="form-control form-control-sm" multiple="multiple" required>
        <option value="">Select Access: </option>
        <?php foreach($coupon_category as $row):?>
        <?php
			$is_selected = (in_array($row->coupon_cat_id, $access)) ? 'selected' : ''; ?>
        <option value="<?=encode($row->coupon_cat_id)?>" <?=$is_selected?> ><?=$row->coupon_cat_name?></option>
        <?php endforeach;?>
    </select>
</div>
