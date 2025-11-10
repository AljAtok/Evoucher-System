<?=$top_nav?>
<div id="admin-content">
    <h3 class="text-center text-danger" style="display: block;">Redeem <?=SEC_SYS_NAME?></h3><br />
    <div id="message-box"></div>
    <div class="row">
        <div class="col-md-4 offset-md-4">
            <div class="form-group">
                <label for="code"><?=SEC_SYS_NAME?> Code: </label>
                <input type="text" name="code" class="form-control form-control-sm mb-2 mr-sm-2" placeholder="Search Code" value="">
            </div>

            <div class="form-group">
                <label for="contact">Contact Number (For Redeem Only): </label>
                <input type="text" name="contact" class="form-control form-control-sm mb-2 mr-sm-2 validate-contact" placeholder="09xxxxxxxxx" value="">
            </div>
        </div>
    </div>
    <div class="row d-flex justify-content-center w-100">
        <div class="form-group">
            <div class="col-md-12 text-left">
                <button type="button" class="btn btn-danger btn-sm mb-2" id="coupon-verify-button">Verify</button>
                <button type="button" class="btn btn-danger btn-sm mb-2" id="coupon-redeem-button">Redeem</button>
            </div>
        </div>
    </div>
</div>
