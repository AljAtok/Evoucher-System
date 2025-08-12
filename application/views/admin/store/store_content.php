            
			<?=$top_nav?>
            <div id="admin-content">

                <h3>Store</h3>
                <hr>
                <table class="table table-striped data-table">
                    <thead>
                        <tr>
                            <th scope="col">Business Center</th>
                            <th scope="col">Province</th>
                            <th scope="col">Town Group</th>
                            <th scope="col">Store Code</th>
                            <th scope="col">Store IFS Code</th>
                            <th scope="col">Store Name</th>
                            <th scope="col">Store Address</th>
                            <th scope="col">Store Contact Number</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    
                    <tbody>

                        <?php
                            foreach($stores as $row):
                                if($row->store_status == 1){
                                    $badge = '<span class="badge badge-success">Active</span>';
                                }elseif($row->store_status == 0){
                                    $badge = '<span class="badge badge-warning">Inactive</span>';
                                }
                        ?>
                            
                            <tr>
                                <td><?= $row->bc_name ?></td>
                                <td><?= $row->province_name ?></td>
                                <td><?= $row->town_group_name ?></td>
                                <td><?= $row->store_code ?></td>
                                <td><?= $row->store_ifs_code ?></td>
                                <td><?= $row->store_name ?></td>
                                <td><?= $row->store_address ?></td>
                                <td><?= $row->contacts ?></td>
                                <td><?= $badge ?></td>
                                <td>
                                    <a href="<?=base_url('admin/add-store-contact/') . encode($row->store_id)?>"><span class="fas fa-search fa-lg"></span></a>
                                </td>
                            </tr>

                        <?php endforeach;?>
                    </tbody>
                </table>
                
            </div>
