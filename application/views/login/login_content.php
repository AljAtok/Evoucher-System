<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= base_url('assets/img/favicon.ico') ?>" type="image/x-icon" />
    <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/bootstrap.min.css')?>"/>
    <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/admin.css?v=1.2')?>">
    <title><?=$title?> - Login</title>
</head>
<body class="login-wallpaper" id="<?=$title?>">
    <?php $uri = $this->uri->segment(2);?>
    <input type="hidden" value="<?=base_url()?>" id="base_url">
    <div id="container">
        <div class="d-flex align-items-center justify-content-center h-100">
            <div class="card login-card shadow">
                <div class="card-body">
					
                    <div class="row py-1">
                        <div class="col-md-12">
                            <div class="text-center">
                                <img src="<?=base_url('assets/img/svg/login-icon.svg')?>" width="150px">
                            </div>
                            <br>
                            <?= $this->session->flashdata('message') ?>
                        </div>
                    </div>
                    <form method="post" action="<?= base_url('login/login_process')?>" class="needs-validation" novalidate>
                        <div class="form-group">
							<div class="input-container">
								<input type="text" name="email" placeholder="" id="email" required>
								<label for="email"><strong>Email: *</strong></label>
							</div>
                            <!-- <label for="">Email : *</label>
                            <input type="email" name="email" class="form-control" required>
                            <div class="invalid-feedback">
                                Email is required.
                            </div> -->
                        </div>
                        <div class="form-group">
							<div class="input-container">
								<input type="password" name="password" minlength="6" maxlength="16" placeholder="" id="password" required>
								<label for="password"><strong>Password: *</strong></label>
							</div>
                            <!-- <label for="">Password: *</label>
                            <input type="password" name="password" minlength="6" maxlength="16" class="form-control" required>
                            <div class="invalid-feedback">
                            Password is required.
                            </div> -->
                        </div>
                        <button type="submit" class="btn btn-danger btn-block my-4">Login</button>
                    </form>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="text-center">
                                <small class="text-muted">&#169; Chookstogo, Inc.</small>
                            </div>

							<div class="d-flex justify-content-center">
								<img class="" width="120" src="<?=base_url('assets\img\positivessl_trust_seal_lg_222x54.png')?>">
							</div>
                        </div>
                    </div>
                </div> 
            </div>
        </div>
    </div>
    <script type="text/javascript" src="<?=base_url('assets/js/jquery-3.3.1.min.js')?>"></script>
    <script type="text/javascript" src="<?=base_url('assets/js/popper.min.js')?>"></script>
    <script type="text/javascript" src="<?=base_url('assets/js/bootstrap.min.js')?>"></script>
    <script type="text/javascript" src="<?=base_url('assets/js/font-awesome.js')?>"></script>
</body>
</html>
