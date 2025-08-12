<div class="table-responsive">
    <table class="table table-striped table-condensed">
        <thead>
            <tr>
                <th scope="col">Business Center</th>
                <th scope="col">Brand</th>
                <th scope="col">Name</th>
                <th scope="col">Code</th>
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
            </tr>
        </thead>
        
        <tbody>
            <?php foreach($trans_details as $row): 
                    if($row->coupon_status == 1){
                        $badge  = '<span class="badge badge-success">Approved</span>';
                    }elseif($row->coupon_status == 0){
                        $badge    = '<span class="badge badge-warning">Inactive</span>';
                    }elseif($row->coupon_status == 2){
                        $badge    = '<span class="badge badge-warning">Pending</span>';
                    }
                ?>
            <tr>
                <td><?= $row->bc ?></td>
                <td><?= $row->brands ?></td>
                <td><?= $row->coupon_name ?></td>
                <td><strong><?= $row->coupon_code ?></strong></td>
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
            </tr>
            <?php endforeach;?>
        </tbody>
    </table>
</div>
