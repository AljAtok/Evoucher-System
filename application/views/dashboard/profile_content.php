<?=$top_nav?>
<div class="container-fluid py-3">
    <?= $this->session->flashdata('message') ?>
    <nav>
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
            <a class="nav-item nav-link active" id="nav-user-profile-tab" data-toggle="tab" href="#user-profile" role="tab" aria-controls="nav-user-profile" aria-selected="true">PROFILE</a>
            <a class="nav-item nav-link" id="nav-security-tab" data-toggle="tab" href="#security" role="tab" aria-controls="nav-security" aria-selected="false">SECURITY</a>
        </div>
    </nav>
    <div class="tab-content" id="nav-tabcontent">
        <div class="tab-pane fade show active" id="user-profile" role="tabpanel" aria-labelledby="nav-user-profile-tab">
            <div class="container py-5">
                <div class="row">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <img style="background-image: url(<?=base_url('assets/img/default_profile.jpg')?>);background-size: cover; height: 250px; width: 250px;margin:auto; border: 4px solid rgb(233, 102, 84); border-radius: 50%; background-position: center;" class="img-fluid">
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="text-left">
                                    <h3 class="registration-header">Profile</h1>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="container">
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">First Name:</label>
                                        <input type="text" name="first_name" class="form-control form-control-sm" value="<?= $user->user_fname ?>" disabled>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">First Name:</label>
                                        <input type="text" name="last_name" class="form-control form-control-sm" value="<?= $user->user_lname ?>" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="employee_no">Employee NO:</label>
                                        <input type="text" name="employee_no" class="form-control form-control-sm" value="<?= $user->user_employee_no ?>" disabled>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email:</label>
                                        <input type="email" name="email" class="form-control form-control-sm" value="<?= $user->user_email ?>" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="employee_no">User Type:</label>
                                        <input type="text" name="user_type_id" class="form-control form-control-sm" value="<?= $user->user_type_name ?>" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="nav-security-tab">
            <div class="container py-5">
                <div class="row">
                    <div class="col-md-9 offset-md-2">
                        <form method="post" action="<?= base_url('profile/change-pass')?>" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="text-left">
                                        <h1 class="registration-header">Change Password</h1>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <h3>Password</h3>
                            <div class="row my-2">
                                <div class="col-md-12">
                                    <label for="old_password">Old Password *</label>
                                    <input type="password" class="form-control" minlength="6" maxlength="16" id="old_password" name="old_password" placeholder="" value="" required>
                                    <div class="invalid-feedback">
                                    Correct Old password is required.
                                    </div>
                                </div>
                            </div>
                            <div class="row my-2">
                                <div class="col-md-6">
                                    <label for="password">New Password *</label>
                                    <input type="password" class="form-control" minlength="6" maxlength="16" id="password" name="password" placeholder="" value="" required>
                                    <div class="invalid-feedback">
                                    Valid password is required.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="repeatPassword">Repeat New Password *</label>
                                    <input type="password" class="form-control" minlength="6" maxlength="16" id="repeatPassword" name="repeat_password" placeholder="" value="" required>
                                    <div class="invalid-feedback">
                                    Valid repeat password is required.
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted">Fields with * is required.</small>
                            <div class="text-right">
                                <button type="submit" class="btn btn-danger mt-3">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
