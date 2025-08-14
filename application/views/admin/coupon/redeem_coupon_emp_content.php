
<?=$top_nav?>
<div id="admin-content">
    <h3 class="text-center text-danger" style="display: block;">Redeem <?=SEC_SYS_NAME?></h3><br />
    <div id="message-box"></div>
    <div class="row">
		<div class="col-lg-4 offset-lg-4">
			<div class="form-group">
				<input type="hidden" name="user_id" value="<?=$user_id?>">
				<div class="input-container">
					<input type="text" name="code" placeholder="" id="code">
					<label for="code"><strong><?=SEC_SYS_NAME?> Code</strong></label>
				</div>
            </div>
			
			<div class="form-group">
				<div class="input-container">
					<input type="text" name="added-info" placeholder="" id="added-info">
					<label for="added-info"><strong>Customer Name</strong></label>
				</div>
			</div>

			
			
			<!-- <div class="form-group">
                <label for="code"> <strong><?=SEC_SYS_NAME?> Code:</strong> </label>
                <input type="text" name="code" class="form-control form-control-sm mb-2 mr-sm-2" placeholder="Input Voucher Code" value="">
            </div>
		
			<div class="form-group">
                <label for="code"><strong>Store Code</strong> (Required on REDEEM): </label>
                <input type="text" name="store-code" class="form-control form-control-sm mb-2 mr-sm-2" placeholder="Input Store Code" value="">
            </div>
            
			<div class="form-group">
				<label for="contact"><strong>Crew Code</strong> (Required on REDEEM): </label>
				<input type="text" name="crew-code" class="form-control form-control-sm mb-2 mr-sm-2" placeholder="Input your Crew Code" value="">
			</div> -->

			

        </div>
    </div>
    <div class="row d-flex justify-content-center w-100">
		
        <div class="form-group mt-3">
			
            <div class="col-md-12 text-left">
                <button type="button" class="btn btn-danger btn-md emboss" id="coupon-verify-button-emp">VERIFY</button>&nbsp;&nbsp;
                <button type="button" class="btn btn-danger btn-md emboss" id="coupon-redeem-button-emp">REDEEM</button>
            </div>
        </div>
    </div>
</div>
