        <?=$top_nav?>
        <div id="admin-content">
            <?= $this->session->flashdata('message') ?>

            <div class="tab-pane fade show active" id="standard-coupon" role="tabpanel" aria-labelledby="nav-standard-coupon-tab">
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-link active" id="nav-unused-tab" data-toggle="tab" href="#nav-unused" role="tab" aria-controls="nav-unused" aria-selected="true">
                            UNUSED
                        </a>
                        <a class="nav-link" id="nav-used-tab" data-toggle="tab" href="#nav-used" role="tab" aria-controls="nav-used" aria-selected="false">
                            USED
                        </a>
                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-unused" role="tabpanel" aria-labelledby="nav-unused-tab">
                        
						
						<div class="container-fluid">
							<div class="row">
								<div class="col-md-2">
									<button type="button" class="btn btn-add btn-sm" data-toggle="modal" data-target="#modal-unused-voucher">
										<span class="fas fa-plus-circle"></span> Filter
									</button>
								</div>
								<div class="offset-md-8 col-md-2">
									<button type="button" class="btn btn-add btn-sm" id="download-unused-voucher">
										<span class="fas fa-download"></span> Download
									</button>
								</div>
	
							</div>
							<input type="hidden" id="unused_coupon_transaction_header_ids">
							
                            <div class="table-responsive">
                                <table class="table table-striped table-condensed" id="tbl-unused-voucher">
                                    <thead>
                                        <tr>
                                            <th scope="col">Business Center</th>
                                            <th scope="col">Brand</th>
                                            <th scope="col">Type</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">ID</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Code</th>
											<th scope="col">Document No.</th>
                                            <th scope="col"><?=SEC_SYS_NAME?> Value</th>

											<th scope="col"><?=SEC_SYS_NAME?> Regular Amount</th>
                                            <th scope="col"><?=SEC_SYS_NAME?> Paid Amount</th>
                                            <th scope="col">Qty</th>
                                            <th scope="col">Usage</th>
                                            <th scope="col">Start</th>
                                            <th scope="col">End</th>
                                            <th scope="col">Holder Type</th>
                                            <th scope="col">Requestor's Company</th>
                                            <th scope="col">Holder Name</th>
                                            <th scope="col">Holder Email</th>
                                            <th scope="col">Holder Contact</th>
                                            <th scope="col">Holder Address</th>
                                            <th scope="col">Holder TIN</th>
                                            <th scope="col">Added</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </br>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="nav-used" role="tabpanel" aria-labelledby="nav-used-tab">
                        
                        <div class="container-fluid">
							<div class="row">
								<div class="col-md-2">
									<button type="button" class="btn btn-add btn-sm" data-toggle="modal" data-target="#modal-used-voucher">
										<span class="fas fa-plus-circle"></span> Filter
									</button>
								</div>
								<div class="offset-md-8 col-md-2">
									<button type="button" class="btn btn-add btn-sm" id="download-used-voucher">
										<span class="fas fa-download"></span> Download
									</button>
								</div>
								
								
							</div>
							
							<input type="hidden" id="used_coupon_transaction_header_ids">
                            <div class="table-responsive">
                                <table class="table table-striped table-condensed" id="tbl-used-voucher">
                                    <thead>
                                        <tr>
                                            <th scope="col">Business Center</th>
                                            <th scope="col">Brand</th>
                                            <th scope="col">Type</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">ID</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Code</th>
                                            <th scope="col">Document No.</th>
                                            <th scope="col"><?=SEC_SYS_NAME?> Value</th>

                                            <th scope="col"><?=SEC_SYS_NAME?> Regular Amount</th>
                                            <th scope="col"><?=SEC_SYS_NAME?> Paid Amount</th>
                                            <th scope="col">Qty</th>
                                            <th scope="col">Usage</th>

                                            <th scope="col">Start</th>
                                            <th scope="col">End</th>
                                            <th scope="col">Holder Type</th>
                                            <th scope="col">Requestor's Company</th>
                                            <th scope="col">Holder Name</th>
                                            <th scope="col">Holder Email</th>
                                            <th scope="col">Holder Contact</th>
                                            <th scope="col">Holder Address</th>
                                            <th scope="col">Holder TIN</th>
                                            <th scope="col">Added</th>
                                            <th scope="col">Redeemer Originator</th>
                                            <th scope="col">Redeemer Store IFS</th>
                                            <th scope="col">Redeemer Store</th>
                                            <th scope="col">Redeemer Crew Code</th>
                                            <th scope="col">Redeemer Crew</th>
                                            <th scope="col">Redeemer Approval Code</th>
                                            <th scope="col">Added Info</th>
                                            <th scope="col">Redeemed TS</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </br>
                        </div>
                    </div>

                </div>
            </div>

			<div class="modal fade" id="modal-unused-voucher" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-md" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Add Filter</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
						<form method="POST" id="unused-voucher-form">
							<div class="modal-body">
								<div class="form-group">
									<label>Type : *</label>
									<select name="date_type" class="form-control form-control-sm unused-date-type" required>
										<!-- <option value="">Select...</option> -->
										<option selected value="<?=encode(1)?>">CREATION DATE</option>
										<!-- <option value="2">REDEMPTION DATE</option> -->
									</select>
								</div>
	
								<div class="form-group">
									<label>Date Range : *</label>
									<input name="date" type="text" class="form-control" name="" id="unused-voucher-calendar" value="<?=$range_date?>" placeholder="Pick Date">
								</div>
	
								
								<div class="form-group">
									<label><?=SEC_SYS_NAME?> Transactions :</label>
									<select name="coupon_transaction_header_id[]" class="form-control form-control-sm unused-coupon-transaction-header-ids" multiple="multiple">
										
										<?=$unused_coupon_trans ?>
									</select>
								</div>
							</div>
							<div class="modal-footer">
								<button type="submit" class="btn btn-main btn-sm">Load</button>
								<!-- <button type="submit" class="btn btn-main btn-sm">Export</button> -->
							</div>
						</form>
                    </div>
                </div>
            </div>
			
			<div class="modal fade" id="modal-used-voucher" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-md" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Add Filter</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
						<form method="POST" id="used-voucher-form">
							<div class="modal-body">
								<div class="form-group">
									<label>Type : *</label>
									<select name="date_type" class="form-control form-control-sm used-date-type" required>
										<!-- <option value="">Select...</option> -->
										<option selected value="<?=encode(1)?>">CREATION DATE</option>
										<option value="<?=encode(2)?>">REDEMPTION DATE</option>
										
									</select>
								</div>
								<div class="form-group">
									<label>Date Range : *</label>
									<input name="date" type="text" class="form-control" name="" id="used-voucher-calendar" value="<?=$range_date?>" placeholder="Pick Date">
									
								</div>
	
								<div class="form-group">
									<label><?=SEC_SYS_NAME?> Transactions :</label>
									<select name="coupon_transaction_header_id[]" class="form-control form-control-sm used-coupon-transaction-header-ids" multiple="multiple">
										
										<?=$used_coupon_trans ?>					
									</select>
								</div>
							</div>
							<div class="modal-footer">
								<button type="submit" class="btn btn-main btn-sm">Load</button>
								<!-- <button type="button" class="btn btn-main btn-sm">Export</button> -->
							</div>
						</form>
                    </div>
                </div>
            </div>


        </div>


