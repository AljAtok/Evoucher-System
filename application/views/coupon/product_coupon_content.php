        <?=$top_nav?>
        <div id="admin-content">
            <?= $this->session->flashdata('message') ?>
            <div class="tab-pane fade show active" id="product-coupon" role="tabpanel" aria-labelledby="nav-product-coupon-tab">
                <!-- <button type="button" class="btn btn-add btn-sm my-3" data-toggle="modal" data-target="#modal-add-product-coupon">
                    <span class="fas fa-plus-circle"></span> Product <?=SEC_SYS_NAME?>
                </button> -->
				<!-- <div class="button-wrapper">
					<button class="btn btn-danger btn-lg mb-2" data-toggle="modal" data-target="#modal-add-product-coupon"><span class="fas fa-plus-circle"></span></button>
					<span class="hover-label">Add Product <?=SEC_SYS_NAME?></span>
				</div> -->

				<div class="d-flex align-items-center justify-content-between w-100">
					<?php if ($controller == 'admin' || $controller == 'creator'): ?>
						<div class="d-flex align-items-center">
							<button type="button" class="btn btn-add btn-sm my-3" data-toggle="modal" data-target="#modal-add-product-coupon">
								<span class="fas fa-plus-circle"></span> Product <?=SEC_SYS_NAME?>
							</button>
						</div>
					<?php endif; ?>
					<div class="ml-auto d-flex">
						<div class="dropdown mr-2">
							<button class="btn btn-light btn-sm dropdown-toggle" type="button" id="orderTypeMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<?=$order_type; ?>
							</button>
							<div class="dropdown-menu dropdown-menu-right shadow-lg" aria-labelledby="orderTypeMenu1">
								<a class="dropdown-item" href="<?=base_url($controller.'/product-coupon')?>" data-value="normal">Normal Orders</a>
								<a class="dropdown-item" href="<?=base_url($controller.'/product-coupon/'.encode(1))?>" data-value="advance">Advance Orders</a>
								<a class="dropdown-item" href="<?=base_url($controller.'/product-coupon/'.encode(2))?>" data-value="issue_on_advance">Issued from Advance Orders</a>
							</div>
						</div>
						<?php if(!empty($category_menu) && $filter_category): ?>
						<div class="dropdown">
							<button class="btn btn-light btn-sm dropdown-toggle" type="button" id="orderTypeMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<?php
								$display_name = $category_name == '' ? 'All Categories' : htmlspecialchars($category_name);
								if (mb_strlen($display_name) > 13) {
									$display_name = mb_substr($display_name, 0, 13) . '...';
								}
								echo ucwords(strtolower($display_name));
								?>
							</button>
							<div class="dropdown-menu dropdown-menu-right shadow-lg" aria-labelledby="orderTypeMenu2">
							<?php
							// Determine selected order type from URL segment or GET param
							$order_type_selected = 'normal';
							$segments = $this->uri->segment_array();
							if (isset($segments[3])) {
								$order_type_selected = $segments[3];
							} else {
								$order_type_selected = encode(0);
							}

							?>
							<a class="dropdown-item" href="<?=base_url($controller.'/product-coupon/'. $order_type_selected . '/' .encode(0))?>" data-value="all-value">
								All Categories
							</a>
							<?php
							// Build category links with selected order type
							foreach ($category_menu as $cat):
								$cat_id_encoded = encode($cat->coupon_cat_id);
								$order_type_param = $order_type_selected;
								$url = base_url($controller . '/product-coupon/' . $order_type_param . '/' . $cat_id_encoded);
							?>
								<a class="dropdown-item" href="<?= $url ?>" data-value="<?= htmlspecialchars($cat->coupon_cat_name) ?>">
									<?= ucwords(strtolower(htmlspecialchars($cat->coupon_cat_name))) ?>
								</a>
							<?php endforeach; ?>
							</div>
						</div>
						<?php endif; ?>
					</div>
				</div>

                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-link active" id="nav-pending-tab" data-toggle="tab" href="#nav-pending" role="tab" aria-controls="nav-pending" aria-selected="true">
                            PENDING
                        </a>
                        <a class="nav-link" id="nav-first-appr-tab" data-toggle="tab" href="#nav-first-approved" role="tab" aria-controls="nav-first-approved" aria-selected="false">
                            TREASURY APPROVED
                        </a>
                        <a class="nav-link" id="nav-approved-tab" data-toggle="tab" href="#nav-approved" role="tab" aria-controls="nav-approved" aria-selected="false">
                            FINANCE APPROVED
                        </a>
                        <a class="nav-link" id="nav-active-tab" data-toggle="tab" href="#nav-active" role="tab" aria-controls="nav-active" aria-selected="false">
                            ACTIVE
                        </a>
                        <a class="nav-link" id="nav-inactive-tab" data-toggle="tab" href="#nav-inactive" role="tab" aria-controls="nav-inactive" aria-selected="false">
                            INACTIVE
                        </a>
                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-pending" role="tabpanel" aria-labelledby="nav-pending-tab">
                        <br> 
                        <div class="table-responsive trans-data">
							
							<table class="table table-striped table-condensed data-table table-hover display nowrap" style="width:100%;">
								<thead>
									<tr>
										<th scope="col">ID</th>
										<th scope="col"><?=SEC_SYS_NAME?> Name</th>
										<th scope="col">Customer</th>
										<?php if ($parent_id): ?>
											<th scope="col" >From Advance Order</th>
										<?php endif; ?>
										<th scope="col" >Category</th>
										<th scope="col" >Start</th>
										<th scope="col" >End</th>
										<th scope="col" >Qty</th>
										<th scope="col" >Amount</th>
										<th scope="col" >Created By</th>
										<th scope="col" >Created On</th>
										<th scope="col" class="text-center">Action</th>
									</tr>
								</thead>
								
								<tbody>
									<?php foreach($pending_coupon_trans as $row):
											$badge  = '<span class="badge badge-warning">Pending</span>';
										?>
									<tr>
										<td><?= $row->coupon_transaction_header_id ?></td>
										<td><?= $row->coupon_transaction_header_name ?></td>
										<td><?= $row->customer_name ?></td>
										<?php if (!empty($row->parent_trans)): ?>
											<td><?= $row->parent_trans ?></td>
										<?php endif; ?>
										<td><?= $row->coupon_cat_name ?></td>
										<td><?= date('M d, Y', strtotime($row->coupon_transaction_header_start)) ?></td>
										<td><?= date('M d, Y', strtotime($row->coupon_transaction_header_end)) ?></td>
										<td><?= $row->coupon_qty ?></td>
										<td><?= decimal_format($row->total_coupon_value) ?></td>
										<td><?= $row->user_lname . ', ' . $row->user_fname ?></td>
										<td><?= date_format(date_create($row->coupon_transaction_header_added),"M d, Y h:i A");?></td>
										<!-- <td><?= $badge ?></td> -->
										<td>
											<div class="mb-0 d-flex justify-content-center align-items-center">
												<?php
												$params = [
													'access_type' 					=> $access_type,
													'transaction_type' 				=> 'pending',
													'coupon_transaction_header_id' 	=> $row->coupon_transaction_header_id,
													'coupon_cat_id' 				=> $row->coupon_cat_id,
													'one_liner'						=> TRUE
												];
												echo action_buttons($params);
												?>
											</div>
										</td>
									</tr>
									<?php endforeach;?>
								</tbody>
							</table></br>
						</div>
                    </div>

                    <div class="tab-pane fade" id="nav-first-approved" role="tabpanel" aria-labelledby="nav-first-approved-tab">
                        <br>
                        <div class="table-responsive trans-data">
                            <table class="table table-striped table-condensed data-table table-hover display nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th >ID</th>
                                        <th ><?=SEC_SYS_NAME?> Name</th>
                                        <th >Customer</th>
                                        <?php if ($parent_id): ?>
                                        	<th >From Advance Order</th>
                                        <?php endif; ?>
                                        <th >Category</th>
                                        <th >Start</th>
                                        <th >End</th>
                                        <th >Qty</th> 
                                        <th >Amount</th> 
                                        <th >Payment Type</th>
                                        <th >Created By</th>
                                        <th >Created On</th>
                                        <th >Payment Status</th>
                                        <th  class="text-center">Action</th>
                                    </tr>
                                </thead>
                                
                                <tbody>
                                    <?php foreach($first_appr_coupon_trans as $row): 
                                            $badge  = '<span class="badge badge-success">Approved</span>';
											
                                            if($row->payment_status == 1){
                                                $payment_badge = '<span class="badge badge-success">Paid</span>';
                                            }elseif($row->payment_status == 0){
                                                $payment_badge = '<span class="badge badge-warning">Unpaid</span>';
                                            }

                                        ?>
                                    <tr>
                                        <td><?= $row->coupon_transaction_header_id ?></td>
                                        <td><?= $row->coupon_transaction_header_name ?></td>
                                        <td><?= $row->customer_name ?></td>
										<?php if (!empty($row->parent_trans)): ?>
                                        <td><?= $row->parent_trans ?></td>
                                        <?php endif; ?>
                                        <td><?= $row->coupon_cat_name ?></td>
                                        <td><?= date('M d, Y', strtotime($row->coupon_transaction_header_start)) ?></td>
                                        <td><?= date('M d, Y', strtotime($row->coupon_transaction_header_end)) ?></td>
                                        <td><?= $row->coupon_qty ?></td>
										<td><?= decimal_format($row->total_coupon_value) ?></td>
                                        <td><?= $row->payment_type_id == 4 ? $row->payment_type .' ('.$row->payment_terms.' DAYS)' : $row->payment_type  ?></td>
                                        <td><?= $row->user_lname . ', ' . $row->user_fname ?></td>
                                        <td><?= date_format(date_create($row->coupon_transaction_header_added),"M d, Y h:i A");?></td>
                                        <td><?= (in_array($row->coupon_cat_id, paid_category())) ? $payment_badge : '-' ?></td>
                                        <!-- <td><?= $badge ?></td> -->
                                        <td>
											<div class="mb-0 d-flex justify-content-center align-items-center">
												<?php
												$params = [
													'access_type' 					=> $access_type,
													'transaction_type' 				=> 'first-approved',
													'coupon_transaction_header_id' 	=> $row->coupon_transaction_header_id,
													'coupon_cat_id' 				=> $row->coupon_cat_id,
													'one_liner'						=> true
												];
												echo action_buttons($params);
												?>
											</div>
										</td>
                                    </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table></br>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="nav-approved" role="tabpanel" aria-labelledby="nav-approved-tab">
                        <br>
                        <div class="table-responsive trans-data">
							<table class="table table-striped table-condensed data-table table-hover display nowrap" style="width:100%">
								<thead>
									<tr>
										<th scope="col">ID</th>
										<th scope="col"><?=SEC_SYS_NAME?> Name</th>
										<th scope="col">Customer</th>
										<?php if ($parent_id): ?>
										<th scope="col">From Advance Order</th>
										<?php endif; ?>
										<th scope="col">Category</th>
										<th scope="col">Start</th>
										<th scope="col">End</th>
										<th scope="col">Qty</th> 
										<th scope="col">Amount</th> 
										<th scope="col">Invoice Number</th>
										<th scope="col">Document Number</th>
										<th scope="col">Created By</th>
										<th scope="col">Created On</th>
										<th scope="col">Payment Status</th>
										<th scope="col" class="text-center">Action</th>
									</tr>
								</thead>
								
								<tbody>
									<?php foreach($approved_coupon_trans as $row): 
											$badge  = '<span class="badge badge-success">Approved</span>';
	
											if($row->payment_status == 1){
												$payment_badge = '<span class="badge badge-success">Paid</span>';
											}elseif($row->payment_status == 0){
												$payment_badge = '<span class="badge badge-warning">Unpaid</span>';
											}
	
										?>
									<tr>
										<td><?= $row->coupon_transaction_header_id ?></td>
										<td><?= $row->coupon_transaction_header_name ?></td>
										<td><?= $row->customer_name ?></td>
										<?php if (!empty($row->parent_trans)): ?>
										<td><?= $row->parent_trans ?></td>
										<?php endif; ?>
										<td><?= $row->coupon_cat_name ?></td>
										<td><?= date('M d, Y', strtotime($row->coupon_transaction_header_start)) ?></td>
										<td><?= date('M d, Y', strtotime($row->coupon_transaction_header_end)) ?></td>
										<td><?= $row->coupon_qty ?></td>
										<td><?= decimal_format($row->total_coupon_value) ?></td>
										<td><?= $row->invoice_number ?></td>
										<td><?= $row->sap_doc_no_2 ? $row->sap_doc_no.';<br>'.$row->sap_doc_no_2: $row->sap_doc_no ?></td>
										<td><?= $row->user_lname . ', ' . $row->user_fname ?></td>
										<td><?= date_format(date_create($row->coupon_transaction_header_added),"M d, Y h:i A");?></td>
										<td><?= (in_array($row->coupon_cat_id, paid_category())) ? $payment_badge : '-' ?></td>
										<!-- <td><?= $badge ?></td> -->
										<td >
											<div class="mb-0 d-flex justify-content-center align-items-center">
												<?php
												$params = [
													'access_type' 					=> $access_type,
													'transaction_type' 				=> 'second-approved',
													'coupon_transaction_header_id' 	=> $row->coupon_transaction_header_id,
													'coupon_cat_id' 				=> $row->coupon_cat_id,
													'payment_status' 				=> $row->payment_status,
													'one_liner' 					=> TRUE
												];
												echo action_buttons($params);
												?>
											</div>
										</td>
									</tr>
									<?php endforeach;?>
								</tbody>
							</table></br>
						</div>
                    </div>

					<div class="tab-pane fade" id="nav-active" role="tabpanel" aria-labelledby="nav-active-tab">
                        <br>
                        <div class="table-responsive trans-data">
                            <table class="table table-striped table-condensed data-table table-hover display nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col"><?=SEC_SYS_NAME?> Name</th>
                                        <th scope="col">Customer</th>
                                        <?php if ($parent_id): ?>
                                        <th scope="col">From Advance Order</th>
                                        <?php endif; ?>
                                        <th scope="col">Category</th>
                                        <th scope="col">Start</th>
                                        <th scope="col">End</th>
                                        <th scope="col">Qty</th> 
                                        <th scope="col">Amount</th> 
                                        <th scope="col">Invoice Number</th>
                                        <th scope="col">Document Number</th>
                                        <th scope="col">Created By</th>
                                        <th scope="col">Created On</th>
                                        <!-- <th scope="col">Payment Status</th> -->
                                        
                                        <th scope="col" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                
                                <tbody>
                                    <?php foreach($active_coupon_trans as $row): 
                                            $badge  = '<span class="badge badge-success">Active</span>';
											
											
                                            if($row->payment_status == 1){
                                                $payment_badge = '<span class="badge badge-success">Paid</span>';
                                            }elseif($row->payment_status == 0){
                                                $payment_badge = '<span class="badge badge-warning">Unpaid</span>';
                                            }

                                        ?>
                                    <tr>
                                        <td><?= $row->coupon_transaction_header_id ?></td>
                                        <td><?= $row->coupon_transaction_header_name ?></td>
                                        <td><?= $row->customer_name ?></td>
                                        <?php if (!empty($row->parent_trans)): ?>
                                        <td><?= $row->parent_trans ?></td>
                                        <?php endif; ?>
                                        <td><?= $row->coupon_cat_name ?></td>
                                        <td><?= date('M d, Y', strtotime($row->coupon_transaction_header_start)) ?></td>
                                        <td><?= date('M d, Y', strtotime($row->coupon_transaction_header_end)) ?></td>
                                        <td><?= $row->coupon_qty ?></td>
										<td><?= decimal_format($row->total_coupon_value) ?></td>
                                        <td><?= $row->invoice_number ?></td>
                                        <td><?= $row->sap_doc_no_2 ? $row->sap_doc_no.';<br>'.$row->sap_doc_no_2: $row->sap_doc_no ?></td>
                                        <td><?= $row->user_lname . ', ' . $row->user_fname ?></td>
                                        <td><?= date_format(date_create($row->coupon_transaction_header_added),"M d, Y h:i A");?></td>
                                        <!-- <td><?= (in_array($row->coupon_cat_id, paid_category())) ? $payment_badge : '-' ?></td> -->
                                        <!-- <td><?= $badge ?></td> -->
                                        <td >
											<div class="mb-0 d-flex justify-content-center align-items-center">
												<?php
												$params = [
													'access_type' 					=> $access_type,
													'transaction_type' 				=> 'active',
													'coupon_transaction_header_id' 	=> $row->coupon_transaction_header_id,
													'coupon_cat_id' 				=> $row->coupon_cat_id,
													'payment_status' 				=> $row->payment_status,
													'coupon_pdf_archived' 			=> $row->coupon_pdf_archived,
													'coupon_for_printing' 			=> $row->coupon_for_printing,
													'coupon_for_image_conv' 		=> $row->coupon_for_image_conv,
													'one_liner'						=> true
												];
												echo action_buttons($params);
												?>
											</div>
                                        </td>
                                    </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table></br>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="nav-inactive" role="tabpanel" aria-labelledby="nav-inactive-tab">
                        <br>
                        <div class="table-responsive trans-data">
                            <table class="table table-striped table-condensed data-table table-hover display nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th scope="col"><?=SEC_SYS_NAME?> Name</th>
                                        <th scope="col">Customer</th>
                                        <?php if ($parent_id): ?>
                                        <th scope="col">From Advance Order</th>
                                        <?php endif; ?>
                                        <th scope="col">Category</th>
                                        <th scope="col">Start</th>
                                        <th scope="col">End</th>
                                        <th scope="col">Qty</th>
                                        <th scope="col">Amount</th>
                                        <th scope="col">Created By</th>
                                        <th scope="col">Created On</th>
                                        
                                        <th scope="col" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                
                                <tbody>
                                    <?php foreach($inactive_coupon_trans as $row): 
                                            $badge  = '<span class="badge badge-warning">Inactive</span>';
                                            $toggle = '<a href="" class="toggle-inactive-transaction text-warning" data-id="' . encode($row->coupon_transaction_header_id) . '"><span class="fas fa-toggle-off fa-lg"></span></a>';
                                        ?>
                                    <tr>
                                        <td><?= $row->coupon_transaction_header_name ?></td>
                                        <td><?= $row->customer_name ?></td>
										<?php if (!empty($row->parent_trans)): ?>
                                        <td><?= $row->parent_trans ?></td>
                                        <?php endif; ?>
                                        <td><?= $row->coupon_cat_name ?></td>
                                        <td><?= date('M d, Y', strtotime($row->coupon_transaction_header_start)) ?></td>
                                        <td><?= date('M d, Y', strtotime($row->coupon_transaction_header_end)) ?></td>
                                        <td><?= $row->coupon_qty ?></td>
                                        <td><?= decimal_format($row->total_coupon_value) ?></td>
                                        <td><?= $row->user_lname . ', ' . $row->user_fname ?></td>
                                        <td><?= date_format(date_create($row->coupon_transaction_header_added),"M d, Y h:i A");?></td>
                                        <!-- <td><?= $badge ?></td> -->
                                        <td>
											<div class="mb-0 d-flex justify-content-center align-items-center">
												<?php
												$params = [
													'access_type' 					=> $access_type,
													'transaction_type' 				=> 'inactive',
													'coupon_transaction_header_id' 	=> $row->coupon_transaction_header_id,
													'coupon_cat_id' 				=> $row->coupon_cat_id,
													'one_liner'						=> TRUE
												];
												echo action_buttons($params);
												?>
											</div>
										</td>
                                    </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table></br>
                        </div>
                    </div>

                </div>
            </div>


            <div class="modal fade" id="modal-add-product-coupon" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Add Product <?=SEC_SYS_NAME?></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/store-product-coupon')?>" class="needs-validation product-transaction" enctype="multipart/form-data" novalidate >
                            <div class="modal-body">
								<div class="form-group">
									<small class="form-text text-muted">Please select type of transaction: *.</small>
									<div class="d-flex flex-wrap gap-2">
										<div class="form-check mr-3 mb-2">
											<input type="radio" name="order_type" class="form-check-input" id="for-normal-order" value="<?=encode('normal')?>">
											<label class="form-check-label" for="for-normal-order">Normal Order</label>
										</div>
										<div class="form-check mr-3 mb-2">
											<input type="radio" name="order_type" class="form-check-input" id="for-advance-order" value="<?=encode('advance')?>">
											<label class="form-check-label" for="for-advance-order">Advance Order</label>
										</div>
										<div class="form-check mr-3 mb-2">
											<input type="radio" name="order_type" class="form-check-input" id="for-issue-on-advance-order" value="<?=encode('issue_on_advance')?>">
											<label class="form-check-label" for="for-issue-on-advance-order">Issue from Advance Order</label>
										</div>
									</div>
								</div>

								<div id="for-usual-order-details">
									<div class="for-usual-trans-inputs">
										<div class="form-group">
											<div class="form-check">
												<input type="checkbox" name="for_printing" class="form-check-input" id="for-printing" value="1">
												<label class="form-check-label" for="for-printing">For Printing</label>
											</div>
										</div>
										
										<div class="form-group">
											<div class="form-check">
												<input type="checkbox" name="for_image_conv" class="form-check-input" id="for-image-conv" value="1">
												<label class="form-check-label" for="for-image-conv">With Image Convertion</label>
											</div>
										</div>
									</div>

									<div class="parent-transaction">
										<div class="form-group">
											<label>From Advance Order Transaction : *</label>
											<select name="parent_transaction_header_id" class="form-control form-control-sm">
												<option value="">Select Advance Order</option>
												<?php foreach($advance_orders as $row):?>
													<option
													data-stock="<?=$row->coupon_qty?>"
													value="<?=encode($row->coupon_transaction_header_id)?>"
													title="Coupon Scope: <?=$row->coupon_scope?>"
													>
														<?=$row->coupon_transaction_header_name.' [Current stock: '.$row->coupon_qty.']'?>
													</option>
												<?php endforeach;?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label for="">Name : *</label>
										<input type="text" name="name" class="form-control form-control-sm" placeholder="" required>
									</div>
									<div class="form-group" id="customer-select-parent">
										<label>Customer : *</label>
										<select name="customer_id" class="form-control form-control-sm" required>
											<option value="">Select Customer</option>
											<?php foreach($customer as $row):?>
												<option value="<?=encode($row->customer_id)?>"><?=$row->customer_name?></option>
											<?php endforeach;?>
										</select>
									</div>
									<div class="form-group">
										<label for=""><?=SEC_SYS_NAME?> Qty : *</label>
										<input type="number" name="product_coupon_qty" class="form-control form-control-sm" placeholder="" required>
									</div>

									<div class="for-usual-trans-inputs">
										<div class="form-group">
											<label>Region :</label>
											<select  class="form-control form-control-sm coupon-scope">
												<option value="">Select</option>
												<?php foreach($scope_masking as $row):?>
													<option value="<?=encode($row->scope_masking_id)?>"><?=$row->scope_masking_name?></option>
												<?php endforeach;?>
											</select>
										</div>
										<div class="form-group">
											<label>Business Center : *</label>
											<select name="bc[]" class="form-control form-control-sm coupon-bc" multiple="multiple" required>
												<option value="">Select Business Center</option>
												<option value="nationwide">NATIONWIDE</option>
												<?php foreach($bc as $row):?>
													<option value="<?=encode($row->bc_id)?>"><?=$row->bc_name?></option>
												<?php endforeach;?>
											</select>
										</div>
										<div class="form-group">
											<label for="">Scope Masking :</label>
											<input type="text" name="scope_masking" maxlength="20" class="form-control form-control-sm" placeholder="Applicable if scope masking is needed (for multiple BCs)">
										</div>
		
										
										<div class="form-group">
											<label>Brand : *</label>
											<select name="brand[]" class="form-control form-control-sm coupon-brand" required>
												<option value="">Select Brand</option>
												<?php foreach($brand as $row):?>
													<?php // if($row->brand_id == 1):?>
													<option value="<?=encode($row->brand_id)?>"><?=$row->brand_name?></option>
													<?php //endif;?>
												<?php endforeach;?>
											</select>
										</div>
		
										<div class="form-group">
											<label><?=SEC_SYS_NAME?> Value Type : *</label>
											<select name="value_type" class="form-control form-control-sm coupon-value-type" required>
												<option value="">Select Value Type</option>
												<?php foreach($value_type as $row):?>
													<option value="<?=encode($row->coupon_value_type_id)?>"><?=$row->coupon_value_type_name?></option>
												<?php endforeach;?>
											</select>
										</div>
		
										<div class="form-group">
											<label for=""><?=SEC_SYS_NAME?> Value : *</label>
											<input type="number" name="amount" class="form-control form-control-sm" placeholder="" min="1" max="100" required>
										</div> 
		
										<div class="form-group">
											<label>Products : *</label>
											<select name="product[]" class="form-control form-control-sm coupon-product" required>
												<option value="">Select Product</option>
												<!-- <option value="all">All Products</option> -->
												<option value="orc">ORC</option>
												<?php foreach($products as $row):?>
													<!-- <option value="<?=encode($row->prod_sale_id)?>"><?=$row->prod_sale_id . ' - ' . $row->prod_sale_name?></option> -->
													<option value="<?=encode($row->prod_sale_id)?>"><?=$row->prod_sale_code.' - '.$row->prod_sale_name?></option>
												<?php endforeach;?>
											</select>
										</div>
									</div>

									
									<div class="form-group">
										<label>Category : *</label>
										<select name="category" class="form-control form-control-sm coupon-category" required>
											<option value="">Select Category</option>
											<?php foreach($category as $row):?>
												<option value="<?=encode($row->coupon_cat_id)?>"><?=$row->coupon_cat_name?></option>
											<?php endforeach;?>
										</select>
									</div>
									
									<div class="form-group">
										<label>Holder Type : *</label>
										<select name="holder_type" class="form-control form-control-sm holder-type" required>
											<option value="">Select Category First</option>
										</select>
									</div>
	
									<div class="form-group">
										<label for="">Holder Name:</label>
										<input type="text" name="holder_name" class="form-control form-control-sm" placeholder="" autocomplete="off" >
									</div> 
									<div class="form-group">
										<label for="">Holder Email:</label>
										<input type="email" name="holder_email" class="form-control form-control-sm validate-email" placeholder="" autocomplete="off">
									</div> 
									<div class="form-group">
										<label for="">Holder Contact:</label>
										<input type="text" name="holder_contact" class="form-control form-control-sm validate-contact" maxlength="11" placeholder="" autocomplete="off">
									</div> 
									<div class="form-group">
										<label for=""><?=SEC_SYS_NAME?> Start & End : *</label>
										<input type="text" name="date_range" class="form-control form-control-sm date-range" placeholder="" autocomplete="off" required>
									</div>
									<!-- <div class="form-group">
										<div class="form-check">
											<input type="checkbox" name="display_exp" class="form-check-input" id="display-exp" value="1">
											<label class="form-check-label" for="display-exp">Display Expiration Date : </label>
										</div>
									</div> -->
	
									<div class="additional-field">
									</div>
									
									<!-- <div class="form-check">
										<input type="checkbox" class="form-check-input" name="email_notif" id="sendEmail">
										<label class="form-check-label" for="sendEmail">Send Email Notification?</label>
									</div>
									<div class="form-check">
										<input type="checkbox" class="form-check-input" name="sms_notif" id="sendSMS">
										<label class="form-check-label" for="sendSMS">Send SMS Notification?</label>
									</div> -->
								</div>

								<div id="for-advance-order-details">
									<div class="form-group">
										<div class="form-check">
											<input type="checkbox" name="for_printing" class="form-check-input" id="adv-for-printing" value="1">
											<label class="form-check-label" for="adv-for-printing">For Printing</label>
										</div>
									</div>
									
									<div class="form-group">
										<div class="form-check">
											<input type="checkbox" name="for_image_conv" class="form-check-input" id="adv-for-image-conv" value="1">
											<label class="form-check-label" for="adv-for-image-conv">With Image Convertion</label>
										</div>
									</div>
									
									
									<div class="form-group">
										<label for="">Name : *</label>
										<input type="text" name="name" class="form-control form-control-sm" placeholder="" required>
									</div>

									<div class="form-group">
										<label for=""><?=SEC_SYS_NAME?> Qty : *</label>
										<input type="number" name="product_coupon_qty" class="form-control form-control-sm" placeholder="" required>
									</div>
									<div class="form-group">
										<label>Region :</label>
										<select  class="form-control form-control-sm coupon-scope">
											<option value="">Select</option>
											<?php foreach($scope_masking as $row):?>
												<option value="<?=encode($row->scope_masking_id)?>"><?=$row->scope_masking_name?></option>
											<?php endforeach;?>
										</select>
									</div>
									<div class="form-group">
										<label>Business Center : *</label>
										<select name="bc[]" class="form-control form-control-sm coupon-bc" multiple="multiple" required>
											<option value="">Select Business Center</option>
											<option value="nationwide">NATIONWIDE</option>
											<?php foreach($bc as $row):?>
												<option value="<?=encode($row->bc_id)?>"><?=$row->bc_name?></option>
											<?php endforeach;?>
										</select>
									</div>
									<div class="form-group">
										<label for="">Scope Masking :</label>
										<input type="text" name="scope_masking" maxlength="20" class="form-control form-control-sm" placeholder="Applicable if scope masking is needed (for multiple BCs)">
									</div>
	
									
									<div class="form-group">
										<label>Brand : *</label>
										<select name="brand[]" class="form-control form-control-sm coupon-brand" required>
											<option value="">Select Brand</option>
											<?php foreach($brand as $row):?>
												<?php // if($row->brand_id == 1):?>
												<option value="<?=encode($row->brand_id)?>"><?=$row->brand_name?></option>
												<?php //endif;?>
											<?php endforeach;?>
										</select>
									</div>
	
									<div class="form-group">
										<label>Category : *</label>
										<select name="category" class="form-control form-control-sm coupon-category" required>
											<option value="">Select Category</option>
											<?php foreach($category as $row):?>
												<option value="<?=encode($row->coupon_cat_id)?>"><?=$row->coupon_cat_name?></option>
											<?php endforeach;?>
										</select>
									</div>
	
									<div class="form-group">
										<label><?=SEC_SYS_NAME?> Value Type : *</label>
										<select name="value_type" class="form-control form-control-sm coupon-value-type" required>
											<option value="">Select Value Type</option>
											<?php foreach($value_type as $row):?>
												<option value="<?=encode($row->coupon_value_type_id)?>"><?=$row->coupon_value_type_name?></option>
											<?php endforeach;?>
										</select>
									</div>
	
									<div class="form-group">
										<label for=""><?=SEC_SYS_NAME?> Value : *</label>
										<input type="number" name="amount" class="form-control form-control-sm" placeholder="" min="1" max="100" required>
									</div> 
	
									<div class="form-group">
										<label>Products : *</label>
										<select name="product[]" class="form-control form-control-sm coupon-product" required>
											<option value="">Select Product</option>
											<!-- <option value="all">All Products</option> -->
											<option value="orc">ORC</option>
											<?php foreach($products as $row):?>
												<!-- <option value="<?=encode($row->prod_sale_id)?>"><?=$row->prod_sale_id . ' - ' . $row->prod_sale_name?></option> -->
												<option value="<?=encode($row->prod_sale_id)?>"><?=$row->prod_sale_code.' - '.$row->prod_sale_name?></option>
											<?php endforeach;?>
										</select>
									</div>

									<label for="">Attachment : *</label><br>
									<div class="custom-file mb-3">
										<input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple required>
										<label class="custom-file-label" for="attachment[]">Choose file...</label>
									</div>
								</div>


                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-main btn-sm">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modal-edit-product-coupon" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Update Product <?=SEC_SYS_NAME?></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/update-product-coupon')?>" class="needs-validation" enctype="multipart/form-data" novalidate>
                            <div class="modal-body">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-main btn-sm">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modal-edit-transaction-coupon" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Update Transaction <?=SEC_SYS_NAME?></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/update-transaction-coupon')?>" class="needs-validation product-transaction" enctype="multipart/form-data" novalidate >
                            <div class="modal-body">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-main btn-sm">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modal-view-prod-coupon-details" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>View Product <?=SEC_SYS_NAME?> Details</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modal-active-coupon" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Activate <?=SEC_SYS_NAME?></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/activate-coupon')?>" id="activate-coupon">
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to activate this <?=SEC_SYS_NAME?>?</strong></p>

                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modal-deactivate-coupon" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Deactivate <?=SEC_SYS_NAME?></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/deactivate-coupon')?>" id="deactivate-coupon">
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to deactivate this <?=SEC_SYS_NAME?>?</strong></p>

                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php if ($this->session->flashdata('html') != '') :?>
            <div class="modal fade" id="success-product-coupon-details" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>View Product <?=SEC_SYS_NAME?> Details</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <?= $this->session->flashdata('html') ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>


            <div class="modal fade" id="modal-approve-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/approve-transaction')?>" id="approve-transaction" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center modal-msg"></p>
                                <div class="invoice-field-container">
                                </div>
                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
			<div class="modal fade" id="modal-publish-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/publish-transaction')?>" id="publish-transaction" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center modal-msg"></p>
                                <div class="invoice-field-container">
                                </div>
                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
			<div class="modal fade" id="modal-return-pending-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Return to Pending Transaction</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/return-to-pending-transaction')?>" id="return-pending-transaction" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to return this Transaction to Pending?</strong></p>
                                
                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
			
			<div class="modal fade" id="modal-return-first-approve-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Return to Treasury Approved Transaction</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/return-to-first-approve-transaction')?>" id="return-first-approve-transaction" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to return this Transaction to Treasury Approved?</strong></p>
                                
                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
			
			<div class="modal fade" id="modal-return-approve-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Return to Finance Approved Transaction</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/return-to-approve-transaction')?>" id="return-approve-transaction" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to return this Transaction to Finance Approved?</strong></p>
                                
                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
			<div class="modal fade" id="modal-first-approve-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-md" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/first-approve-transaction')?>" id="first-approve-transaction" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center modal-msg"></p>
                                <div class="payment-det-field-container">
                                </div>
                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <div class="modal fade" id="modal-deactivate-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Deactivate Transaction</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/deactivate-transaction')?>" id="deactivate-transaction">
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to deactivate this Transaction?</strong></p>
                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modal-activate-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Activate Transaction</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/approve-transaction')?>" id="activate-transaction">
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to activate this Transaction?</strong></p>
                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modal-view" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Attachment & History List</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>


            <div class="modal fade" id="modal-cancel-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Cancel Transaction</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/cancel-transaction')?>" id="cancel-transaction">
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to cancel this Transaction?</strong></p>
                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modal-pay-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Transaction Payment</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/pay-transaction')?>" id="pay-transaction" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to input payment details on this Transaction?</strong></p>
                                <div class="pay-field-container">
                                </div><br>
                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

			<div class="modal fade" id="modal-archive-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id=""><strong>Archive Transaction</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/archive-coupon-pdf')?>" id="archive-transaction">
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to archive PDF on this Transaction?</strong></p>
                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
			
			<div class="modal fade" id="modal-restore-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id=""><strong>Restore Transaction</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url($controller.'/regenerate-coupon-pdf')?>" id="restore-transaction">
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to restore PDF on this Transaction?</strong></p>
                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>


