    <input type="hidden" name="id" id="id" value="<?= encode($transaction->coupon_transaction_header_id) ?>">
    <input type="hidden" name="order_type" id="order_type" value="<?= encode($transaction->is_advance_order) ?>">

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
        
		<label for="">Name : *</label>
		<input type="text" name="name" class="form-control form-control-sm" placeholder="" value="<?= $coupon->coupon_name ?>" required>
    </div> 
    <div class="form-group">
        <label for=""><?=SEC_SYS_NAME?> Qty: <?= $transaction->coupon_qty ?></label>
    </div>
    <div class="form-group">
        <label>Scope Masking: <?=$transaction->coupon_scope_masking?></label>
    </div>
    <div class="form-group">
        <label>Business Center: <?=$bc_select?></label>
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
    
	<?php if(!$transaction->is_advance_order): ?>
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
	<?php endif; ?>

    <?php 
        $start = date('m/d/Y', strtotime($coupon->coupon_start));
        $end   = date('m/d/Y', strtotime($coupon->coupon_end));
    ?>
    <div class="form-group">
        <label for=""><?=SEC_SYS_NAME?> Start & End:</label>
        <input type="text" name="date_range" <?=$transaction->is_advance_order ? 'disabled' : ''?> class="form-control form-control-sm date-range" value="<?= $start . ' - ' . $end ?>" autocomplete="off" required>

		<?php if($transaction->is_advance_order): ?>
			<small class="form-text text-muted">Date Start & End is disabled for advance orders.</small>
			<input type="hidden" name="date_range" value="<?= $start . ' - ' . $end ?>">
		<?php endif; ?>
    </div>
    <div class="additional-field">
		<?php if ($coupon->coupon_cat_id == '1') : ?>
			<div class="form-group" id="customer-select-parent2">
				<label>Customer : *</label>
				<select name="upd_customer_id" class="form-control form-control-sm upd-customer-id" required>
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
					<select name="upd_customer_id" class="form-control form-control-sm upd-customer-id" required>
						<?=$customer_select?>
					</select>
				</div>
				<label for="">Attachment:</label><br>
				<div class="custom-file mb-3">
					<input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple>
					<label class="custom-file-label" for="attachment[]">Choose file...</label>
				</div>
			<?php else : ?>
				<?php if(!$transaction->is_advance_order): ?>
					<div class="form-group">
						<label for=""><?=SEC_SYS_NAME?> Regular Amount : *</label>
						<input type="number" name="voucher-regular-value" placeholder="<?='Per '.SEC_SYS_NAME.' Regular Amount'?>" class="form-control form-control-sm voucher-regular-value" value="<?= $coupon->coupon_regular_value ?>" min="0.01" step="0.01" required>
					</div> 
					<div class="form-group">
						<label for=""><?=SEC_SYS_NAME?> Paid Amount : *</label>
						<input type="number" name="voucher-value" placeholder="<?='Per '.SEC_SYS_NAME.' Paid Amount'?>" class="form-control form-control-sm voucher-value" value="<?= $coupon->coupon_value ?>" min="0.01" step="0.01" required>
					</div> 
					<div class="form-group">
						<label for="">Holder Address : *</label>
						<input type="text" name="address" class="form-control form-control-sm" value="<?= $coupon->coupon_holder_address ?>" placeholder="" required>
					</div> 
					<div class="form-group">
						<label for="">Holder TIN : *</label>
						<input type="text" name="tin" class="form-control form-control-sm" placeholder="" value="<?= $coupon->coupon_holder_tin ?>" required>
					</div>
				<?php endif; ?>

				<div class="payment-det-field-container">
					<?=$payment_fields?>
				</div>
				
				<div class="form-group" id="customer-select-parent2">
					<label>Customer : *</label>
					<select name="upd_customer_id" class="form-control form-control-sm upd-customer-id" <?=$transaction->is_advance_order ? 'disabled' : 'required'?>>
						<?=$customer_select?>
					</select>
					<?php if($transaction->is_advance_order): ?>
						<small class="form-text text-muted">Customer selection is disabled for advance orders.</small>
						<input type="hidden" name="upd_customer_id" value="<?= encode($transaction->customer_id) ?>">
					<?php endif; ?>
				</div>
				<label for="">Attachment:</label><br>
				<div class="custom-file mb-3">
					<input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple>
					<label class="custom-file-label" for="attachment[]">Choose file...</label>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<div class="form-group" id="customer-select-parent2">
				<label>Customer : *</label>
				<select name="upd_customer_id" class="form-control form-control-sm upd-customer-id" required>
					<?=$customer_select?>
				</select>
			</div>
			<div class="form-group">
			
				<label for="">Attachment:</label><br>
				<div class="custom-file mb-3">
					<input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple>
					<label class="custom-file-label" for="attachment[]">Choose file...</label>
				</div>
			</div>
		<?php endif; ?>
    </div>
