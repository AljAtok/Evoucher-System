    <input type="hidden" name="id" value="<?= encode($transaction->coupon_transaction_header_id) ?>">
    
	<div class="form-group">
		<div class="form-check">
			<input type="checkbox" name="for_printing" class="form-check-input" id="for-printing-edit" value="<?=encode(1)?>" <?= $transaction->coupon_for_printing ? 'checked' : '' ?>>
			<label class="form-check-label" for="for-printing-edit">For Printing</label>
		</div>
	</div>
	
	<div class="form-group">
		<div class="form-check">
			<input type="checkbox" name="for_image_conv" class="form-check-input" id="for-image-conv-edit" value="<?=encode(1)?>" <?= $transaction->coupon_for_image_conv ? 'checked' : '' ?>>
			<label class="form-check-label" for="for-image-conv-edit">With Image Convertion</label>
		</div>
	</div>

	<div class="form-group">
        <label for=""><?=SEC_SYS_NAME?> Qty: <?= $transaction->coupon_qty ?></label>
    </div>
	<div class="form-group">
        <label>Scope Masking: <?=$transaction->coupon_scope_masking?></label>
    </div>
    <div class="form-group">
        <label>Business Center: <?=$coupon->bc?></label>
    </div>
    <div class="form-group">
        <label>Brand: <?=$coupon->brands?></label>
    </div>
    <div class="form-group">
        <label><?=SEC_SYS_NAME?> Value Type: <?=$coupon->coupon_value_type_name?></label>
    </div>
	<div class="form-group">
        <label for=""><?=SEC_SYS_NAME?> Value: <?= $coupon->coupon_amount ?></label>
    </div>
    <div class="form-group">
        <label>Products: <?= $coupon->products?></label>
    </div>
    <div class="form-group">
        <label>Category: <?= $coupon->coupon_cat_name?></label>
    </div>
    <div class="form-group">
        <label for="">Name: <?= $coupon->coupon_name ?></label>
    </div> 
    
    <div class="form-group">
        <label>Holder Type:<?= $coupon->coupon_holder_type_name ?></label>
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
	<div class="form-group">
        <label for="">Requestor's Company: <?= $coupon->company_name ?></label>
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
		<div class="form-group" id="customer-select-parent2">
			<label>Customer : *</label>
			<select name="upd_customer_id" class="form-control form-control-sm" required>
				<?=$customer_select?>
			</select>
		</div>
        <div class="form-group">
            <label for="">Attachment:</label>
            <input type="file" name="attachment[]" class="form-control-file" accept="image/png, image/jpeg, image/jpg, document/pdf" multiple>
        </div>
	<?php elseif (in_array($coupon->coupon_cat_id, paid_category())) : ?>
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
			<div class="payment-det-field-container">
				<?=$payment_fields?>
			</div>
            <div class="form-group">
                <label for="">Invoice: <?= $coupon->invoice_number ?> </label>
            </div>
			<div class="form-group" id="customer-select-parent2">
				<label>Customer : *</label>
				<select name="upd_customer_id" class="form-control form-control-sm" required>
					<?=$customer_select?>
				</select>
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
			<div class="payment-det-field-container">
				<?=$payment_fields?>
			</div>
			<div class="form-group" id="customer-select-parent2">
				<label>Customer : *</label>
				<select name="upd_customer_id" class="form-control form-control-sm" required>
					<?=$customer_select?>
				</select>
			</div>
            <label for="">Attachment:</label><br>
            <div class="custom-file mb-3">
                <input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple>
                <label class="custom-file-label" for="attachment[]">Choose file...</label>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    </div>
