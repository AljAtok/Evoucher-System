            
			<?=$top_nav?>
            <div id="admin-content">
                <?php
                    if($this->session->flashdata('message') != "" ){
                        echo $this->session->flashdata('message');
                    }
                ?>

                <button type="button" class="btn btn-add btn-sm" data-toggle="modal" data-target="#modal-add-employee">
                   <span class="fas fa-plus-circle"></span> Employee
                </button>
                <table class="table table-striped data-table">
                    <thead>
                        <tr>
                            <th scope="col">First Name</th>
                            <th scope="col">Last Name</th>
                            <th scope="col">Employee No</th>
                            <th scope="col">Email</th>
                            <th scope="col">User Type</th>
                            <th scope="col">Access</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    
                    <tbody>

                        <?php
                            foreach($users as $row):
                                if($row->user_status == 1){
                                    $badge = '<span class="badge badge-success">Active</span>';
                                    $toggle = '<a href="" class="toggle-active text-success" data-id="' . encode($row->user_id) . '"><span class="fas fa-toggle-on fa-2x"></a></span>';
                                }elseif($row->user_status == 0){
                                    $badge = '<span class="badge badge-warning">Inactive</span>';
                                    $toggle = '<a href="#" class="toggle-inactive text-warning" data-id="' . encode($row->user_id) . '"><span class="fas fa-toggle-off fa-2x"></span></a>';
                                }
                        ?>
                            
                            <tr>
                                <td><?= $row->user_fname ?></td>
                                <td><?= $row->user_lname ?></td>
                                <td><?= $row->user_employee_no ?></td>
                                <td><?= $row->user_email ?></td>
                                <td><?= $row->user_type_name ?></td>
                                <td><?= ($row->access == '') ? '-' : $row->access ?></td>
                                <td><?= $badge ?></td>
                                <td>
                                    <a href="" class="edit" data-url="/modal-employee/" data-id="<?=encode($row->user_id)?>"><span class="fas fa-pencil-alt fa-lg"></span></a>
                                    <a href="" class="reset" data-url="/modal-employee-password-reset/" data-id="<?=encode($row->user_id)?>"><span class="fas fa-redo-alt fa-lg"></span></a>
                                    &nbsp;&nbsp;&nbsp;<?=$toggle?>
                                </td>
                            </tr>

                        <?php endforeach;?>
                    </tbody>
                </table>


                <div class="modal fade" id="modal-add-employee" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="exampleModalLabel"><strong>Add Employee</strong></h6>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST" action="<?=base_url('admin/store-employee')?>" class="needs-validation" novalidate>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <div class="form-group">
                                            <label for="fname">First Name: </label>
                                            <input type="text" id="fname" name="fname" class="form-control form-control-sm" value="" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="lname">Last Name: </label>
                                            <input type="text" id="lname" name="lname" class="form-control form-control-sm" value="" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="employee_no">Employee No: </label>
                                            <input type="text" id="employee_no" name="employee_no" class="form-control form-control-sm" value="" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Employee Email: </label>
                                            <input type="email" id="email" name="email" class="form-control form-control-sm" value="" required>
                                        </div>
                                        <div class="form-group">
                                            <?php $temp_password = generate_random(8) ?>
                                            <label for="password">Employee Password: </label>
                                            <input type="text" id="password" name="password" class="form-control form-control-sm" value="<?= $temp_password ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>User Type:</label>
                                            <select name="user_type" class="form-control form-control-sm" required>
                                              <option value="">Select User Type: </option>
                                                <?php foreach($user_types as $row):?>
                                                <option value="<?=encode($row->user_type_id)?>"><?=$row->user_type_name?></option>
                                                <?php endforeach;?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Allowed Access:</label>
                                            <select name="access[]" class="form-control form-control-sm" multiple="multiple" required>
                                              <option value="">Select Access: </option>
                                                <?php foreach($coupon_category as $row):?>
                                                <option value="<?=encode($row->coupon_cat_id)?>"><?=$row->coupon_cat_name?></option>
                                                <?php endforeach;?>
                                            </select>
                                        </div>
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

                <div class="modal fade" id="modal-reset" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="exampleModalLabel"><strong>Reset Employee</strong></h6>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST" action="<?=base_url('admin/reset-employee')?>" id="reset-employee" class="needs-validation" novalidate>
                                <div class="modal-body">
                                </div>
                            
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-main btn-sm">Reset</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modal-edit" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="exampleModalLabel"><strong>Update Employee</strong></h6>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST" action="<?=base_url('admin/update-employee')?>" id="update-employee">
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

                <div class="modal fade" id="modal-active" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-sm" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="exampleModalLabel"><strong>Activate Employee</strong></h6>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST" action="<?=base_url('admin/activate-employee')?>" id="activate-form">
                                <div class="modal-body">
                                    <input type="hidden" name="id" id="id">
                                    <p class="text-center"><strong>Are you sure to activate this employee?</strong></p>

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
                                <h6 class="modal-title" id="exampleModalLabel"><strong>Deactivate Employee</strong></h6>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST" action="<?=base_url('admin/deactivate-employee')?>" id="deactivate-form">
                                <div class="modal-body">
                                    <input type="hidden" name="id" id="id">
                                    <p class="text-center"><strong>Are you sure to deactivate this employee?</strong></p>

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
