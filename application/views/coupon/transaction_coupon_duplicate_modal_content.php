    <!-- <input type="hidden" name="id" id="id" value="<?= encode($transaction->coupon_transaction_header_id) ?>"> -->
    <!-- <input type="hidden" name="order_type" id="order_type" value="<?= encode($transaction->is_advance_order) ?>"> -->
    <input type="hidden" name="order_type" id="order_type" value="<?=encode('normal')?>">

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
        <label for=""><?=SEC_SYS_NAME?> Qty : *</label>
		<input type="number" name="product_coupon_qty" class="form-control form-control-sm" placeholder="" value="<?= $transaction->coupon_qty ?>" required>
    </div>
    <div class="form-group">
        <label>Business Center : *</label>
		<select name="bc[]" class="form-control form-control-sm coupon-bc" multiple="multiple" required>
			<?=$bc_select?>
		</select>
    </div>
	<div class="form-group">
        <label>Scope Masking : *</label>
		<input type="text" name="scope_masking" maxlength="20" class="form-control form-control-sm" placeholder="Applicable if scope masking is needed (for multiple BCs)" value="<?=$transaction->coupon_scope_masking?>">
    </div>
    <div class="form-group">
        <label>Brand : *</label>
		<select name="brand[]" class="form-control form-control-sm coupon-brand" required>
			<?=$brand_select?>
		</select>
    </div>
    <div class="form-group">
        <label><?=SEC_SYS_NAME?> Value Type : *</label>
		<select name="value_type" class="form-control form-control-sm coupon-value-type" required>
			<?=$value_type_select?>
		</select>
    </div>
	<div class="form-group">
        <label for=""><?=SEC_SYS_NAME?> Value : *</label>
		<input type="number" name="amount" class="form-control form-control-sm" placeholder="" value="<?= intval($coupon->coupon_amount) ?>" min="1" max="100" required>
    </div>
    <div class="form-group">
        <label>Products : *</label>
		<select name="product[]" class="form-control form-control-sm coupon-product" required>
			<?=$products_select?>
		</select>
    </div>
    <div class="form-group">
        <label>Category: <?= $coupon->coupon_cat_name?></label>
		<input type="hidden" name="category" class="form-control form-control-sm" placeholder="" value="<?=encode($coupon->coupon_cat_id)?>" required>
    </div>
    
	<?php if(!$transaction->is_advance_order): ?>
		<div class="form-group">
			<label>Holder Type:<?= $coupon->coupon_holder_type_name ?></label>
			<input type="hidden" name="holder_type" class="form-control form-control-sm" placeholder="" value="<?=encode($coupon->coupon_holder_type_id)?>" required>
		</div>
		<div class="form-group">
			<label for="">Holder Name: <?= $coupon->coupon_holder_name ?></label>
			<input type="hidden" name="holder_name" class="form-control form-control-sm" placeholder="" value="<?= $coupon->coupon_holder_name ?>" required>
		</div> 
		<div class="form-group">
			<label for="">Holder Email: <?= $coupon->coupon_holder_email ?></label>
			<input type="hidden" name="holder_email" class="form-control form-control-sm" placeholder="" value="<?= $coupon->coupon_holder_email ?>" required>
		</div> 
		<div class="form-group">
			<label for="">Holder Contact: <?= $coupon->coupon_holder_contact ?></label>
			<input type="hidden" name="holder_contact" class="form-control form-control-sm" placeholder="" value="<?= $coupon->coupon_holder_contact ?>" required>
		</div> 
		<div class="form-group">
			<label for="">Requestor's Company: <?= $coupon->company_name ?></label>
			<input type="hidden" name="company_id" class="form-control form-control-sm" placeholder="" value="<?=encode($coupon->company_id)?>" required>
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
				<select name="customer_id" class="form-control form-control-sm upd-customer-id" required>
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
					<select name="customer_id" class="form-control form-control-sm upd-customer-id" required>
						<?=$customer_select?>
					</select>
				</div>
				<label for="">Attachment:</label><br>
				<div class="custom-file mb-3">
					<input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple required>
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
					<select name="customer_id" class="form-control form-control-sm upd-customer-id" <?=$transaction->is_advance_order ? 'disabled' : 'required'?>>
						<?=$customer_select?>
					</select>
					<?php if($transaction->is_advance_order): ?>
						<small class="form-text text-muted">Customer selection is disabled for advance orders.</small>
						<input type="hidden" name="upd_customer_id" value="<?= encode($transaction->customer_id) ?>">
					<?php endif; ?>
				</div>
				<label for="">Attachment:</label><br>
				<div class="custom-file mb-3">
					<input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple required>
					<label class="custom-file-label" for="attachment[]">Choose file...</label>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<div class="form-group">
				<div class="form-check">
					<input type="checkbox" name="allocate_to_each_bc" class="form-check-input" id="allocate_to_each_bc" value="1" <?= $transaction->allocation_count > 0 ? 'checked' : '' ?>>
					<label class="form-check-label" for="allocate_to_each_bc">Allocate Qty to BC selected</label>
				</div>
			</div>
			<div class="form-group">
				<label for="">Allocation Qty per BC :</label>
				<input type="number" name="allocation_count" class="form-control form-control-sm" value="<?=$transaction->allocation_count > 0 ? $transaction->allocation_count : ''?>" placeholder="">
			</div>
			<div class="form-group" id="customer-select-parent2">
				<label>Customer : *</label>
				<select name="customer_id" class="form-control form-control-sm upd-customer-id" required>
					<?=$customer_select?>
				</select>
			</div>
			<div class="form-group">
			
				<label for="">Attachment:</label><br>
				<div class="custom-file mb-3">
					<input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple required>
					<label class="custom-file-label" for="attachment[]">Choose file...</label>
				</div>
			</div>
		<?php endif; ?>
    </div>
