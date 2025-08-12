            <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top border-bottom">
                <a href="#" id="sidebarCollapse">
                    <i class="fas fa-bars fa-lg"></i>
                </a>

                <div class="container-fluid">
                    <span>
                        <a href="<?=base_url('admin/stores')?>">Store</a> / Add Store Contact Number
                    </span>
                </div>
            </nav>
            <div id="admin-content">
                <?php
                    if($this->session->flashdata('message') != "" ){
                        echo $this->session->flashdata('message');
                    }
                ?>

                <h4><?= $store->store_name ?></h4>
                <hr>

                <button type="button" class="btn btn-add btn-sm" data-toggle="modal" data-target="#modal-add-employee">
                   <span class="fas fa-plus-circle"></span> Add Contact Number
                </button>
                <table class="table table-striped data-table">
                    <thead>
                        <tr>
                            <th scope="col">Contact Number</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php
                            foreach($store_contacts as $row):
                                if($row->store_contact_status == 1){
                                    $badge = '<span class="badge badge-success">Active</span>';
                                    $toggle = '<a href="" class="toggle-active text-success" data-id="' . encode($row->store_contact_id) . '"><span class="fas fa-toggle-on fa-2x"></a></span>';
                                }elseif($row->store_contact_status == 0){
                                    $badge = '<span class="badge badge-warning">Inactive</span>';
                                    $toggle = '<a href="#" class="toggle-inactive text-warning" data-id="' . encode($row->store_contact_id) . '"><span class="fas fa-toggle-off fa-2x"></span></a>';
                                }
                        ?>
                            
                            <tr>
                                <td><?= $row->store_contact_number ?></td>
                                <td><?= $badge ?></td>
                                <td><?= $toggle ?></td>
                            </tr>

                        <?php endforeach;?>
                    </tbody>
                </table>


                <div class="modal fade" id="modal-add-employee" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="exampleModalLabel"><strong>Add Store Contact</strong></h6>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST" action="<?=base_url('admin/store-contact-number')?>" class="needs-validation" novalidate>
                                <div class="modal-body">
                                    <input type="hidden" name="store_id" value="<?= encode($store->store_id) ?>">
                                    <div class="form-group">
                                        <label for="">Store Code: <?= $store->store_code ?></label>
                                    </div> 
                                    <div class="form-group">
                                        <label for="">Store Name: <?= $store->store_name ?></label>
                                    </div> 
                                    <div class="form-group">
                                        <label for="">Contact Number:</label>
                                        <input type="text" name="contact_number" class="form-control form-control-sm validate-contact" maxlength="11" autocomplete="off" required>
                                    </div> 
                                </div>
                            
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-main btn-sm" id="submit-btn">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modal-active" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-sm" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="exampleModalLabel"><strong>Activate Contact</strong></h6>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST" action="<?=base_url('admin/activate-store-contact')?>" id="activate-form">
                                <div class="modal-body">
                                    <input type="hidden" name="id" id="id">
                                    <p class="text-center"><strong>Are you sure to activate this contact?</strong></p>
                                    <p class="text-center">
                                        <button type="submit" class="btn btn-sm btn-success btn-yes">Yes</button>&nbsp;
                                        <button type="button" class="btn btn-danger btn-sm btn-no" data-dismiss="modal">No</button>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modal-deactivate" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-sm" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="exampleModalLabel"><strong>Deactivate Contact</strong></h6>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST" action="<?=base_url('admin/deactivate-store-contact')?>" id="deactivate-form">
                                <div class="modal-body">
                                    <input type="hidden" name="id" id="id">
                                    <p class="text-center"><strong>Are you sure to deactivate this contact?</strong></p>
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
