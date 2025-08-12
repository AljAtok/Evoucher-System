    <input type="hidden" name="id" value="<?= encode($coupon->coupon_id) ?>">
    <div class="form-group">
        <label for=""><?=SEC_SYS_NAME?> Code: <strong><?= $coupon->coupon_code ?></strong></label>
    </div> 
    <div class="form-group">
        <label>Business Center: <?=$coupon->bc?></label>
    </div>
    <div class="form-group">
        <label>Brand: <?=$coupon->brands?></label>
    </div>
    <div class="form-group">
        <label>Value Type: <?=$coupon->coupon_value_type_name?></label>
    </div>
    <div class="form-group">
        <label>Category: <?= $coupon->coupon_cat_name?></label>
    </div>
    <div class="form-group">
        <label for="">Name: <?= $coupon->coupon_name ?></label>
    </div> 
    <div class="form-group">
        <label for="">Discount Amount: <?= $coupon->coupon_amount ?></label>
    </div>
    <div class="form-group">
        <label for="">Qty: <?= $coupon->coupon_qty ?></label>
    </div> 
    <div class="form-group">
        <label>Holder Type: <?= $coupon->coupon_holder_type_name ?></label>
    </div>
    <div class="form-group">
        <label for="">Holder Name: <?= $coupon->coupon_holder_name ?></label>
    </div> 
    <div class="form-group">
        <label for="">Holder Email: <?= $coupon->coupon_holder_email ?></label>
    </div> 
    <div class="form-group">
        <label for="">Holder Contact: <?= $coupon->coupon_holder_contact ?></label>
    </div> 
    <?php 
        $start = date('m/d/Y', strtotime($coupon->coupon_start));
        $end   = date('m/d/Y', strtotime($coupon->coupon_end));
    ?>
    <div class="form-group">
        <label for=""><?=SEC_SYS_NAME?> Start & End:</label>
        <input type="text" name="date_range" class="form-control form-control-sm date-range" value="<?= $start . ' - ' . $end ?>" autocomplete="off" required>
    </div> 
    <div class="additional-field">
    <?php if ($coupon->coupon_cat_id == '1') : ?>
        <div class="form-group">
            <label for="">Attachment:</label>
            <input type="file" name="attachment[]" class="form-control-file" accept="image/png, image/jpeg, image/jpg, document/pdf" multiple>
        </div>
    <?php elseif ($coupon->coupon_cat_id == '3') : ?>
        <?php if ($coupon->coupon_holder_type_id == '4') : ?>
            <div class="form-group">
                <label for=""><?=SEC_SYS_NAME?> Paid Amount: <?= $coupon->coupon_value ?></label>
            </div> 
            <div class="form-group">
                <label for="">Holder Address: <?= $coupon->coupon_holder_address ?></label>
            </div> 
            <div class="form-group">
                <label for="">Holder TIN: <?= $coupon->coupon_holder_tin ?></label>
            </div> 
            <div class="form-group">
                <label for="">Invoice: <?= $coupon->invoice_number ?> </label>
            </div>
            <label for="">Attachment:</label><br>
            <div class="custom-file mb-3">
                <input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple>
                <label class="custom-file-label" for="attachment[]">Choose file...</label>
            </div>
        <?php else : ?>
            <div class="form-group">
                <label for=""><?=SEC_SYS_NAME?> Paid Amount: <?= $coupon->coupon_value ?></label>
            </div> 
            <div class="form-group">
                <label for="">Holder Address: <?= $coupon->coupon_holder_address ?></label>
            </div> 
            <div class="form-group">
                <label for="">Holder TIN: <?= $coupon->coupon_holder_tin ?></label>
            </div> 
            <label for="">Attachment:</label><br>
            <div class="custom-file mb-3">
                <input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple>
                <label class="custom-file-label" for="attachment[]">Choose file...</label>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    </div>
