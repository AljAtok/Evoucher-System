        <?=$top_nav?>
        <div id="approver-content">
            <?= $this->session->flashdata('message') ?>
            <br> 
            <div class="tab-pane fade show active" id="standard-coupon" role="tabpanel" aria-labelledby="nav-standard-coupon-tab">
                <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-link active" id="nav-pending-tab" data-toggle="tab" href="#nav-pending" role="tab" aria-controls="nav-pending" aria-selected="true">
                            Pending
                        </a>
                        <a class="nav-link" id="nav-approved-tab" data-toggle="tab" href="#nav-approved" role="tab" aria-controls="nav-approved" aria-selected="false">
                            Approved
                        </a>
                        <a class="nav-link" id="nav-inactive-tab" data-toggle="tab" href="#nav-inactive" role="tab" aria-controls="nav-inactive" aria-selected="false">
                            Inactive
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
                                        <th scope="col">Business Center</th>
                                        <th scope="col">Brand</th>
                                        <th scope="col">Name</th>
                                        <th scope="col"><?=SEC_SYS_NAME?> Discount Amount</th>
                                        <th scope="col"><?=SEC_SYS_NAME?> Paid Amount</th>
                                        <th scope="col">Qty</th>
                                        <th scope="col">Usage</th>
                                        <th scope="col">Value Type</th>
                                        <th scope="col">Start</th>
                                        <th scope="col">End</th>
                                        <th scope="col">Holder Type</th>
                                        <th scope="col">Holder Name</th>
                                        <th scope="col">Holder Email</th>
                                        <th scope="col">Holder Contact</th>
                                        <th scope="col">Holder Address</th>
                                        <th scope="col">Holder TIN</th>
                                        <th scope="col">Added</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                
                                <tbody>
                                    <?php foreach($pending_coupon as $row): 
                                            $badge  = '<span class="badge badge-warning">Pending</span>';
                                            $toggle = '<a href="" class="approve-coupon" data-id="' . encode($row->coupon_id) . '"><span class="fas fa-arrow-circle-right fa-lg"></span></a>';
                                        ?>
                                    <tr>
                                        <td><?= $row->bc ?></td>
                                        <td><?= $row->brands ?></td>
                                        <td><?= $row->coupon_name ?></td>
                                        <td><?= $row->coupon_amount ?></td>
                                        <td><?= $row->coupon_value ?></td>
                                        <td><?= $row->coupon_qty ?></td>
                                        <td><?= $row->coupon_use ?></td>
                                        <td><?= $row->coupon_value_type_name ?></td>
                                        <td><?= date_format(date_create($row->coupon_start),"M d, Y");?></td>
                                        <td><?= date_format(date_create($row->coupon_end),"M d, Y");?></td>
                                        <td><?= $row->coupon_holder_type_name ?></td>
                                        <td><?= $row->coupon_holder_name ?></td>
                                        <td><?= $row->coupon_holder_email ?></td>
                                        <td><?= $row->coupon_holder_contact ?></td>
                                        <td><?= $row->coupon_holder_address ?></td>
                                        <td><?= $row->coupon_holder_tin ?></td>
                                        <td><?= date_format(date_create($row->coupon_added),"M d, Y h:i:s A");?></td>
                                        <td><?= $badge ?></td>
                                        <td class="d-flex d-inline text-center">
                                            <?php if ($row->coupon_cat_id == '1' || $row->coupon_cat_id == '3') : ?>
                                                <a href="" class="view-attachments" data-url="/modal_coupon_attachment/" data-id="<?=encode($row->coupon_id)?>"><i class="fas fa-paperclip fa-lg"></i></a>
                                                &nbsp;&nbsp;&nbsp;
                                            <?php endif; ?>
                                            <?=$toggle?>
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
                                        <th scope="col">Business Center</th>
                                        <th scope="col">Brand</th>
                                        <th scope="col">Name</th>
                                        <th scope="col"><?=SEC_SYS_NAME?> Discount Amount</th>
                                        <th scope="col"><?=SEC_SYS_NAME?> Paid Amount</th>
                                        <th scope="col">Qty</th>
                                        <th scope="col">Usage</th>
                                        <th scope="col">Value Type</th>
                                        <th scope="col">Invoice Number</th>
                                        <th scope="col">Start</th>
                                        <th scope="col">End</th>
                                        <th scope="col">Holder Type</th>
                                        <th scope="col">Holder Name</th>
                                        <th scope="col">Holder Email</th>
                                        <th scope="col">Holder Contact</th>
                                        <th scope="col">Holder Address</th>
                                        <th scope="col">Holder TIN</th>
                                        <th scope="col">Added</th>
                                        <th scope="col">Payment Status</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                
                                <tbody>
                                    <?php foreach($approved_coupon as $row): 
                                            $badge  = '<span class="badge badge-success">Approved</span>';
                                            $toggle = '<a href="" class="toggle-active-coupon text-success" data-id="' . encode($row->coupon_id) . '"><span class="fas fa-toggle-on fa-2x"></a></span>';

                                            if($row->payment_status == 1){
                                                $payment_badge = '<span class="badge badge-success">Paid</span>';
                                            }elseif($row->payment_status == 0){
                                                $payment_badge = '<span class="badge badge-warning">Unpaid</span>';
                                            }
                                        ?>
                                    <tr>
                                        <td><?= $row->bc ?></td>
                                        <td><?= $row->brands ?></td>
                                        <td><?= $row->coupon_name ?></td>
                                        <td><?= $row->coupon_amount ?></td>
                                        <td><?= $row->coupon_value ?></td>
                                        <td><?= $row->coupon_qty ?></td>
                                        <td><?= $row->coupon_use ?></td>
                                        <td><?= $row->coupon_value_type_name ?></td>
                                        <td><?= ($row->coupon_cat_id == 3) ? $row->invoice_number : '-' ?></td>
                                        <td><?= date_format(date_create($row->coupon_start),"M d, Y");?></td>
                                        <td><?= date_format(date_create($row->coupon_end),"M d, Y");?></td>
                                        <td><?= $row->coupon_holder_type_name ?></td>
                                        <td><?= $row->coupon_holder_name ?></td>
                                        <td><?= $row->coupon_holder_email ?></td>
                                        <td><?= $row->coupon_holder_contact ?></td>
                                        <td><?= $row->coupon_holder_address ?></td>
                                        <td><?= $row->coupon_holder_tin ?></td>
                                        <td><?= date_format(date_create($row->coupon_added),"M d, Y h:i:s A");?></td>
                                        <td><?= ($row->coupon_cat_id == 3) ? $payment_badge : '-' ?></td>
                                        <td><?= $badge ?></td>

                                        <td class="d-flex d-inline text-center">
                                            <?php if ($row->coupon_cat_id == '1' || $row->coupon_cat_id == '3') : ?>
                                                <a href="" class="view-attachments" data-url="/modal_coupon_attachment/" data-id="<?=encode($row->coupon_id)?>"><i class="fas fa-paperclip fa-lg"></i></a>
                                                &nbsp;&nbsp;&nbsp;
                                            <?php endif; ?>
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
                                        <th scope="col">Business Center</th>
                                        <th scope="col">Brand</th>
                                        <th scope="col">Name</th>
                                        <th scope="col"><?=SEC_SYS_NAME?> Discount Amount</th>
                                        <th scope="col"><?=SEC_SYS_NAME?> Paid Amount</th>
                                        <th scope="col">Qty</th>
                                        <th scope="col">Usage</th>
                                        <th scope="col">Value Type</th>
                                        <th scope="col">Invoice Number</th>
                                        <th scope="col">Start</th>
                                        <th scope="col">End</th>
                                        <th scope="col">Holder Type</th>
                                        <th scope="col">Holder Name</th>
                                        <th scope="col">Holder Email</th>
                                        <th scope="col">Holder Contact</th>
                                        <th scope="col">Holder Address</th>
                                        <th scope="col">Holder TIN</th>
                                        <th scope="col">Added</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                
                                <tbody>
                                    <?php foreach($inactive_coupon as $row): 
                                            $badge  = '<span class="badge badge-warning">Inactive</span>';
                                            $toggle = '<a href="" class="toggle-inactive-coupon text-warning" data-id="' . encode($row->coupon_id) . '"><span class="fas fa-toggle-off fa-2x"></span></a>';
                                        ?>
                                    <tr>
                                        <td><?= $row->bc ?></td>
                                        <td><?= $row->brands ?></td>
                                        <td><?= $row->coupon_name ?></td>
                                        <td><?= $row->coupon_amount ?></td>
                                        <td><?= $row->coupon_value ?></td>
                                        <td><?= $row->coupon_qty ?></td>
                                        <td><?= $row->coupon_use ?></td>
                                        <td><?= $row->coupon_value_type_name ?></td>
                                        <td><?= $row->invoice_number ?></td>
                                        <td><?= date_format(date_create($row->coupon_start),"M d, Y");?></td>
                                        <td><?= date_format(date_create($row->coupon_end),"M d, Y");?></td>
                                        <td><?= $row->coupon_holder_type_name ?></td>
                                        <td><?= $row->coupon_holder_name ?></td>
                                        <td><?= $row->coupon_holder_email ?></td>
                                        <td><?= $row->coupon_holder_contact ?></td>
                                        <td><?= $row->coupon_holder_address ?></td>
                                        <td><?= $row->coupon_holder_tin ?></td>
                                        <td><?= date_format(date_create($row->coupon_added),"M d, Y h:i:s A");?></td>
                                        <td><?= $badge ?></td>

                                        <td class="d-flex d-inline text-center">
                                            <?php if ($row->coupon_cat_id == '1' || $row->coupon_cat_id == '3') : ?>
                                                <a href="" class="view-attachments" data-url="/modal_coupon_attachment/" data-id="<?=encode($row->coupon_id)?>"><i class="fas fa-paperclip fa-lg"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table></br>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal fade" id="modal-active-coupon" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Activate <?=SEC_SYS_NAME?></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url('approver/activate-coupon')?>" id="activate-coupon">
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

            <div class="modal fade" id="modal-deactivate-coupon" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Deactivate <?=SEC_SYS_NAME?></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url('approver/deactivate-coupon')?>" id="deactivate-coupon">
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

            <div class="modal fade" id="modal-approve-coupon" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Approve <?=SEC_SYS_NAME?></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url('approver/approve-coupon')?>" id="approve-coupon" enctype="multipart/form-data">
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to approve this <?=SEC_SYS_NAME?>?</strong></p>
                                <div class="invoice-container">
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

            <div class="modal fade" id="modal-view" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Attachment List</strong></h6>
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

            <div class="modal fade" id="modal-pay-coupon" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Pay <?=SEC_SYS_NAME?></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url('approver/pay-coupon')?>" id="pay-coupon" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to pay this <?=SEC_SYS_NAME?>?</strong></p>
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


