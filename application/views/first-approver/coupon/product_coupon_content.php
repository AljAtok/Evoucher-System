        <?=$top_nav?>
        <div id="approver-content">
            <?= $this->session->flashdata('message') ?>
            <div class="tab-pane fade show active" id="product-coupon" role="tabpanel" aria-labelledby="nav-product-coupon-tab">
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
                        <a class="nav-link" id="nav-inactive-tab" data-toggle="tab" href="#nav-inactive" role="tab" aria-controls="nav-inactive" aria-selected="false">
                            INACTIVE
                        </a>
                    </div>
                </nav>
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="nav-pending" role="tabpanel" aria-labelledby="nav-pending-tab">
                        <br>
                        <div class="table-responsive">
                            <table class="table table-striped table-condensed data-table">
                                <thead>
                                    <tr>
                                        <th scope="col"><?=SEC_SYS_NAME?> Name</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Start</th>
                                        <th scope="col">End</th>
                                        <th scope="col">Qty</th>
                                        <th scope="col">Amount</th>
                                        <th scope="col">User</th>
                                        <th scope="col">Added</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                
                                <tbody>
                                    <?php foreach($pending_coupon_trans as $row):
                                            $badge  = '<span class="badge badge-warning">Pending</span>';
                                            $toggle = '<a href="" class="first-approve-transaction" data-id="' . encode($row->coupon_transaction_header_id) . '"><span class="fas fa-arrow-circle-right fa-lg"></span></a>';
                                        ?>
                                    <tr>
                                        <td><?= $row->coupon_transaction_header_name ?></td>
                                        <td><?= $row->customer_name ?></td>
                                        <td><?= $row->coupon_cat_name ?></td>
                                        <td><?= date('M d, Y', strtotime($row->coupon_transaction_header_start)) ?></td>
                                        <td><?= date('M d, Y', strtotime($row->coupon_transaction_header_end)) ?></td>
                                        <td><?= $row->coupon_qty ?></td>
										<td><?= decimal_format($row->total_coupon_value) ?></td>
                                        <td><?= $row->user_lname . ', ' . $row->user_fname ?></td>
                                        <td><?= date_format(date_create($row->coupon_transaction_header_added),"M d, Y h:i A");?></td>
                                        <td><?= $badge ?></td>
                                        <td class="d-flex d-inline justify-content-center align-items-center">
											<?php if ($row->coupon_cat_id != '2') : ?>
                                                <a href="" class="view-attachments" data-url="/modal_transaction_coupon_attachment/" data-id="<?=encode($row->coupon_transaction_header_id)?>"><i class="fas fa-paperclip fa-lg"></i></a>
                                                &nbsp;&nbsp;
                                            <?php endif; ?>
                                            <!-- <a href="#" class="view-product-coupon-details" data-id="<?=encode($row->coupon_transaction_header_id)?>">
                                            <span class="far fa-eye fa-lg"></span></a>
                                            &nbsp;&nbsp; -->
                                            <!-- <a href="<?=base_url('first-approver/export-trans-details/') . encode($row->coupon_transaction_header_id)?>" target="_blank"><span class="fas fa-file-excel fa-lg"></span></a>
                                            &nbsp;&nbsp; -->
											
											<?= $toggle ?>
                                        </td>
                                    </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table></br>
                        </div>
                    </div>

					<div class="tab-pane fade" id="nav-first-approved" role="tabpanel" aria-labelledby="nav-primary-appr-tab">
                        <br>
                        <div class="table-responsive">
                            <table class="table table-striped table-condensed data-table">
                                <thead>
                                    <tr>
                                        <th scope="col">ID</th>
                                        <th scope="col"><?=SEC_SYS_NAME?> Name</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Start</th>
                                        <th scope="col">End</th>
                                        <th scope="col">Qty</th> 
                                        <th scope="col">Amount</th> 
                                        <th scope="col">Payment Type</th>
                                        
                                        <th scope="col">User</th>
                                        <th scope="col">Added</th>
                                        <th scope="col">Payment Status</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                
                                <tbody>
                                    <?php foreach($first_appr_coupon_trans as $row): 
                                            $badge  = '<span class="badge badge-success">Approved</span>';
                                            $toggle = '';
											$return_btn = '<a href="#" title="Back to Pending" class="return-pending-transaction" data-id="' . encode($row->coupon_transaction_header_id) . '"><span class="fas fa-arrow-circle-left fa-lg"></span></a>&nbsp;&nbsp;';
											// $appr_btn = '<a href="#" title="Approve" class="approve-transaction" data-id="' . encode($row->coupon_transaction_header_id) . '"><span class="fas fa-arrow-circle-right fa-lg"></span></a>';
											$appr_btn = '';
											// $edit_appr_btn = '<a href="#" title="Edit Approval" class="edit-first-approve-transaction" data-id="' . encode($row->coupon_transaction_header_id) . '"><span class="fas fa-edit fa-lg"></span></a>';
											$edit_appr_btn = '';

											
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
                                        <td><?= $row->coupon_cat_name ?></td>
                                        <td><?= date('M d, Y', strtotime($row->coupon_transaction_header_start)) ?></td>
                                        <td><?= date('M d, Y', strtotime($row->coupon_transaction_header_end)) ?></td>
                                        <td><?= $row->coupon_qty ?></td>
										<td><?= decimal_format($row->total_coupon_value) ?></td>
                                        <td><?= $row->payment_type_id == 4 ? $row->payment_type .' ('.$row->payment_terms.' DAYS)' : $row->payment_type  ?></td>
                                        <td><?= $row->user_lname . ', ' . $row->user_fname ?></td>
                                        <td><?= date_format(date_create($row->coupon_transaction_header_added),"M d, Y h:i A");?></td>
                                        <td><?= (in_array($row->coupon_cat_id, paid_category())) ? $payment_badge : '-' ?></td>
                                        <td><?= $badge ?></td>
                                        <td class="d-flex d-inline justify-content-center align-items-center">
											<?= $return_btn ?>

											<?php if ($row->payment_status == 0) :?>
                                                <!-- <a href="#" class="pay-transaction" data-id="<?=encode($row->coupon_transaction_header_id)?>"><span class="fas fa-arrow-circle-right fa-lg"></span></a>
                                                &nbsp;&nbsp; -->
                                            <?php endif; ?>
											
                                            
                                            <?php if ($row->coupon_cat_id != '2') : ?>
                                                <a href="" class="view-attachments" data-url="/modal_transaction_coupon_attachment/" data-id="<?=encode($row->coupon_transaction_header_id)?>"><i class="fas fa-paperclip fa-lg"></i></a>
                                                &nbsp;&nbsp;
                                            <?php endif; ?>

                                            <?= $edit_appr_btn ?>

                                            <!-- <a href="#" class="view-product-coupon-details" data-id="<?=encode($row->coupon_transaction_header_id)?>">
                                            <span class="far fa-eye fa-lg"></span></a>
                                            &nbsp;&nbsp; -->

                                            <!-- <a href="<?=base_url('first-approver/export-trans-details/') . encode($row->coupon_transaction_header_id)?>"><span class="fas fa-file-excel fa-lg text-success"></span></a>
                                            &nbsp;&nbsp; -->
                                            
											

                                            <?= $toggle ?>
                                            <?= $appr_btn ?>
                                        </td>
                                    </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table></br>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="nav-approved" role="tabpanel" aria-labelledby="nav-approved-tab">
                        <br>
                        <div class="table-responsive">
                            <table class="table table-striped table-condensed data-table">
                                <thead>
                                    <tr>
                                        <th scope="col"><?=SEC_SYS_NAME?> Name</th>
                                        <th scope="col">Customer Name</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Start</th>
                                        <th scope="col">End</th>
                                        <th scope="col">Qty</th>
                                        <th scope="col">Amount</th>
                                        <th scope="col">Invoice Number</th>
                                        <th scope="col">Document Number</th>
                                        <th scope="col">User</th>
                                        <th scope="col">Added</th>
                                        <th scope="col">Payment Status</th>
                                        <th scope="col">Status</th>
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
                                        <td><?= $row->coupon_transaction_header_name ?></td>
                                        <td><?= $row->customer_name ?></td>
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
                                        <td><?= $badge ?></td>
                                        <td class="d-flex d-inline justify-content-center align-items-center">
                                            <?php if ($row->payment_status == 0) :?>
                                                <!-- <a href="#" class="pay-transaction" data-id="<?=encode($row->coupon_transaction_header_id)?>"><span class="fas fa-arrow-circle-right fa-lg"></span></a>
                                                &nbsp;&nbsp; -->
                                            <?php endif; ?>
                                            <?php if ($row->coupon_cat_id != '2') : ?>
                                                <a href="" class="view-attachments" data-url="/modal_transaction_coupon_attachment/" data-id="<?=encode($row->coupon_transaction_header_id)?>"><i class="fas fa-paperclip fa-lg"></i></a>
                                                &nbsp;&nbsp;
                                            <?php endif; ?>
                                            <!-- <a href="#" class="view-product-coupon-details" data-id="<?=encode($row->coupon_transaction_header_id)?>">
                                            <span class="far fa-eye fa-lg"></span></a>
                                            &nbsp;&nbsp;
                                            <a href="<?=base_url('first-approver/export-trans-details/') . encode($row->coupon_transaction_header_id)?>" target="_blank"><span class="fas fa-file-excel fa-lg"></span></a> -->
                                        </td>
                                    </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table></br>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="nav-inactive" role="tabpanel" aria-labelledby="nav-inactive-tab">
                        <br>
                        <div class="table-responsive">
                            <table class="table table-striped table-condensed data-table">
                                <thead>
                                    <tr>
                                        <th scope="col"><?=SEC_SYS_NAME?> Name</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Start</th>
                                        <th scope="col">End</th>
                                        <th scope="col">Qty</th> 
                                        <th scope="col">Amount</th> 
                                        <th scope="col">User</th>
                                        <th scope="col">Added</th>
                                        <th scope="col">Status</th>
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
                                        <td><?= $row->coupon_cat_name ?></td>
                                        <td><?= date('M d, Y', strtotime($row->coupon_transaction_header_start)) ?></td>
                                        <td><?= date('M d, Y', strtotime($row->coupon_transaction_header_end)) ?></td>
                                        <td><?= $row->coupon_qty ?></td>
										<td><?= decimal_format($row->total_coupon_value) ?></td>
                                        <td><?= $row->user_lname . ', ' . $row->user_fname ?></td>
                                        <td><?= date_format(date_create($row->coupon_transaction_header_added),"M d, Y h:i A");?></td>
                                        <td><?= $badge ?></td>
                                        <td class="d-flex d-inline justify-content-center align-items-center">
											<?php if ($row->coupon_cat_id != '2') : ?>
                                                <a href="" class="view-attachments" data-url="/modal_transaction_coupon_attachment/" data-id="<?=encode($row->coupon_transaction_header_id)?>"><i class="fas fa-paperclip fa-lg"></i></a>
                                                &nbsp;&nbsp;
                                            <?php endif; ?>
                                            <!-- <a href="#" class="view-product-coupon-details" data-id="<?=encode($row->coupon_transaction_header_id)?>">
                                            <span class="far fa-eye fa-lg"></span></a> -->
                                            &nbsp;&nbsp;
                                            <!-- <?= $toggle ?> -->
                                        </td>
                                    </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table></br>
                        </div>
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

            <div class="modal fade" id="modal-approve-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Approve Transaction</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url('first-approver/approve-transaction')?>" id="approve-transaction" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to approve this Transaction?</strong></p>
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
                        <form method="POST" action="<?=base_url('first-approver/return-to-pending-transaction')?>" id="return-pending-transaction" enctype="multipart/form-data" class="needs-validation" novalidate>
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

			<div class="modal fade" id="modal-first-approve-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-md" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Approve Transaction</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url('first-approver/first-approve-transaction')?>" id="first-approve-transaction" enctype="multipart/form-data" class="needs-validation" novalidate>
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
                        <form method="POST" action="<?=base_url('first-approver/deactivate-transaction')?>" id="deactivate-transaction">
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
                        <form method="POST" action="<?=base_url('first-approver/approve-transaction')?>" id="activate-transaction">
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

            <div class="modal fade" id="modal-pay-transaction" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Pay Transaction</strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url('first-approver/pay-transaction')?>" id="pay-transaction" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to pay this Transaction?</strong></p>
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


        </div>


