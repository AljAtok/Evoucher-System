<div class="table-responsive">
    <table class="table table-striped table-condensed data-table">
        <thead>
            <tr>
                <th scope="col">Business Center</th>
                <th scope="col">Brand</th>
                <th scope="col">Name</th>
                <th scope="col">Code</th>
                <th scope="col">Invoice Number</th>
				<th scope="col">Document Number</th>
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
                <th scope="col">Status</th>
                <th scope="col" class="text-center">Action</th>
            </tr>
        </thead>
        
        <tbody>
            <?php foreach($trans_details as $row): 
                    $pdf    = '';
                    $toggle = '';
                    if($row->coupon_status == 1 && $trans_header->coupon_transaction_header_status == 1){
                        $pdf    = '<a href="' . base_url($row->coupon_pdf_path) . '" target="_blank" rel="noreferer"><i class="fas fa-file-pdf fa-lg"></i></a>';
                        $badge  = '<span class="badge badge-success">Approved</span>';
                        $toggle = '<a href="" class="toggle-active-coupon text-success" data-id="' . encode($row->coupon_id) . '"><span class="fas fa-toggle-on fa-2x"></a></span>';
                    }elseif($row->coupon_status == 0 && $trans_header->coupon_transaction_header_status == 0){
                        $badge  = '<span class="badge badge-warning">Inactive</span>';
                    }elseif($row->coupon_status == 2 && $trans_header->coupon_transaction_header_status == 2){
                        $badge  = '<span class="badge badge-warning">Pending</span>';
                    }elseif($row->coupon_status == 4 || $trans_header->coupon_transaction_header_status == 4){
                        $badge  = '<span class="badge badge-success">Approved</span>';
                    }
                ?>
            <tr>
                <td><?= $row->bc ?></td>
                <td><?= $row->brands ?></td>
                <td><?= $row->coupon_name ?></td>
                <td><?= $row->coupon_code ?></td>
                <td><?= $row->invoice_number ?></td>
				<td><?= $row->sap_doc_no_2 ? $row->sap_doc_no.';<br>'.$row->sap_doc_no_2: $row->sap_doc_no ?></td>
                <td><?= $row->coupon_amount ?></td>
				<td><?= $row->coupon_regular_value ?></td>
                <td><?= $row->coupon_value ?></td>
                <td><?= $row->coupon_qty ?></td>
                <td><?= $row->coupon_use ?></td>
                <td><?= date_format(date_create($row->coupon_start),"M d, Y");?></td>
                <td><?= date_format(date_create($row->coupon_end),"M d, Y");?></td>
                <td><?= $row->coupon_holder_type_name ?></td>
                <td><?= $row->company_name ?></td>
                <td><?= $row->coupon_holder_name ?></td>
                <td><?= $row->coupon_holder_email ?></td>
                <td><?= $row->coupon_holder_contact ?></td>
                <td><?= $row->coupon_holder_address ?></td>
                <td><?= $row->coupon_holder_tin ?></td>
                <td><?= $badge ?></td>

                <td class="text-center">
                    <div class="d-flex d-inline">
                        <?= $pdf ?> 
                        &nbsp;&nbsp;&nbsp;
                        <a href="#" class="edit-product-coupon" data-id="<?=encode($row->coupon_id)?>"><span class="fas fa-pencil-alt fa-lg"></span></a>
                    </div>
                    &nbsp;&nbsp;&nbsp;
                    <?=$toggle?>
                </td>
            </tr>
            <?php endforeach;?>
        </tbody>
    </table>
</div>
