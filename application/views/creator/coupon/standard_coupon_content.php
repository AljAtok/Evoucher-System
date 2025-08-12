        <?=$top_nav?>
        <div id="admin-content">
            <?= $this->session->flashdata('message') ?>

            <div class="tab-pane fade show active" id="standard-coupon" role="tabpanel" aria-labelledby="nav-standard-coupon-tab">
                <button type="button" class="btn btn-add btn-sm my-3" data-toggle="modal" data-target="#modal-add-standard-coupon">
                    <span class="fas fa-plus-circle"></span> Standard <?=SEC_SYS_NAME?>
                </button>
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
                                        <th scope="col">Code</th>
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
                                        ?>
                                    <tr>
                                        <td><?= $row->bc ?></td>
                                        <td><?= $row->brands ?></td>
                                        <td><?= $row->coupon_name ?></td>
                                        <td><?= $row->coupon_code ?></td>
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
                                                &nbsp;&nbsp;
                                            <?php endif; ?>
                                            <a href="#" class="edit-standard-coupon" data-id="<?=encode($row->coupon_id)?>"><span class="fas fa-pencil-alt fa-lg"></span></a>
                                            &nbsp;&nbsp;
                                            <a href="#" class="cancel-coupon" data-id="<?=encode($row->coupon_id)?>"><span class="fas fa-times-circle fa-lg"></span></a>
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
                                        <th scope="col">Code</th>
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
                                        <td><?= $row->coupon_code ?></td>
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
                                                &nbsp;&nbsp;
                                            <?php endif; ?>

                                            <a href="<?= base_url($row->coupon_pdf_path) ?>" target="_blank" rel="noreferer"><i class="fas fa-file-pdf fa-lg"></i></a>
                                            &nbsp;&nbsp;
                                            <a href="#" class="edit-standard-coupon" data-id="<?=encode($row->coupon_id)?>"><span class="fas fa-pencil-alt fa-lg"></span></a>
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
                                        <th scope="col">Code</th>
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
                                            // $toggle = '<a href="" class="toggle-inactive-coupon text-warning" data-id="' . encode($row->coupon_id) . '"><span class="fas fa-toggle-off fa-2x"></span></a>';
                                        ?>
                                    <tr>
                                        <td><?= $row->bc ?></td>
                                        <td><?= $row->brands ?></td>
                                        <td><?= $row->coupon_name ?></td>
                                        <td><?= $row->coupon_code ?></td>
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
                                                &nbsp;&nbsp;
                                            <?php endif; ?>
                                            <a href="#" class="edit-standard-coupon" data-id="<?=encode($row->coupon_id)?>"><span class="fas fa-pencil-alt fa-lg"></span></a>
                                        </td>
                                    </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table></br>
                        </div>
                    </div>

                </div>
            </div>


            
            <div class="modal fade" id="modal-add-standard-coupon" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Add Standard <?=SEC_SYS_NAME?></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php $random_standard_code = generate_random_coupon(7) ?>
                        <form method="POST" action="<?=base_url('creator/store-standard-coupon')?>" class="needs-validation" enctype="multipart/form-data" novalidate>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="">Name:</label>
                                    <input type="text" name="name" class="form-control form-control-sm" placeholder="" required>
                                </div> 
                                <div class="form-group">
                                    <label>Business Center:</label>
                                    <select name="bc[]" class="form-control form-control-sm coupon-bc" required>
                                        <option value="">Select Business Center</option>
                                        <option value="nationwide">NATIONWIDE</option>
                                        <?php foreach($bc as $row):?>
                                            <option value="<?=encode($row->bc_id)?>"><?=$row->bc_name?></option>
                                        <?php endforeach;?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Brand:</label>
                                    <select name="brand[]" class="form-control form-control-sm coupon-brand" required>
                                        <option value="">Select Brand</option>
                                        <?php foreach($brand as $row):?>
                                            <?php if($row->brand_id == 1):?>
                                            <option value="<?=encode($row->brand_id)?>"><?=$row->brand_name?></option>
                                            <?php endif;?>
                                        <?php endforeach;?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Value Type:</label>
                                    <select name="value_type" class="form-control form-control-sm coupon-value-type" required>
                                        <option value="">Select Value Type</option>
                                        <?php foreach($value_type as $row):?>
                                            <option value="<?=encode($row->coupon_value_type_id)?>"><?=$row->coupon_value_type_name?></option>
                                        <?php endforeach;?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="">Discount Amount:</label>
                                    <input type="number" name="amount" class="form-control form-control-sm" placeholder="" min="1" max="100" required>
                                </div> 

                                <div class="form-group">
                                    <label>Category:</label>
                                    <select name="category" class="form-control form-control-sm coupon-category" required>
                                        <option value="">Select Category</option>
                                        <?php foreach($category as $row):?>
                                            <option value="<?=encode($row->coupon_cat_id)?>"><?=$row->coupon_cat_name?></option>
                                        <?php endforeach;?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="">Code:</label>
                                    <input type="text" name="code" class="form-control form-control-sm" placeholder="" value="<?=$random_standard_code ?>" required>
                                </div> 
                                <div class="form-group">
                                    <label for="">Qty:</label>
                                    <input type="number" name="qty" class="form-control form-control-sm" placeholder="" required>
                                </div> 
                                <div class="form-group">
                                    <label>Holder Type:</label>
                                    <select name="holder_type" class="form-control form-control-sm holder-type" required>
                                        <option value="">Select Category First</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="">Holder Name:</label>
                                    <input type="text" name="holder_name" class="form-control form-control-sm" placeholder="" >
                                </div> 
                                <div class="form-group">
                                    <label for="">Holder Email:</label>
                                    <input type="email" name="holder_email" class="form-control form-control-sm validate-email" placeholder="" >
                                </div> 
                                <div class="form-group">
                                    <label for="">Holder Contact:</label>
                                    <input type="text" name="holder_contact" class="form-control form-control-sm validate-contact" maxlength="11" placeholder="" >
                                </div> 
                                <div class="form-group">
                                    <label for=""><?=SEC_SYS_NAME?> Start & End:</label>
                                    <input type="text" name="date_range" class="form-control form-control-sm date-range" placeholder="" autocomplete="off" required>
                                </div> 
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
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-main btn-sm">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <div class="modal fade" id="modal-edit-standard-coupon" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Update Standard <?=SEC_SYS_NAME?></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url('creator/update-standard-coupon')?>" class="needs-validation" enctype="multipart/form-data" novalidate>
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

            <div class="modal fade" id="modal-active-coupon" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Activate <?=SEC_SYS_NAME?></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url('creator/activate-coupon')?>" id="activate-coupon">
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
                        <form method="POST" action="<?=base_url('creator/deactivate-coupon')?>" id="deactivate-coupon">
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
                        <form method="POST" action="<?=base_url('creator/approve-coupon')?>" id="approve-coupon" enctype="multipart/form-data">
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


            <div class="modal fade" id="modal-cancel-coupon" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h6 class="modal-title" id="exampleModalLabel"><strong>Cancel <?=SEC_SYS_NAME?></strong></h6>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" action="<?=base_url('creator/cancel-coupon')?>" id="cancel-coupon">
                            <div class="modal-body">
                                <input type="hidden" name="id" id="id">
                                <p class="text-center"><strong>Are you sure to cancel this <?=SEC_SYS_NAME?>?</strong></p>

                                <p class="text-center">
                                    <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                    <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                </p>
                            </div>
                        </form>
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
                        <form method="POST" action="<?=base_url('creator/pay-coupon')?>" id="pay-coupon" enctype="multipart/form-data" class="needs-validation" novalidate>
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


