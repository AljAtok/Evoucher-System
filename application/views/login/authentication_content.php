<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= base_url('assets/img/favicon.ico') ?>" type="image/x-icon" />
    <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/bootstrap.min.css')?>"/>
    <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/admin.css?v=1.2')?>">
    <title><?=$title?> - Authentication</title>
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
                                <strong>Two Factor Authentication. OTP code sent to your email and valid for 15 minutes.</strong><br /><br />
                                <img src="<?=base_url('assets/img/2fa.png')?>" width="150px">
                            </div>
                            <br>
                            <?= $this->session->flashdata('message') ?>
                        </div>
                    </div>
                    <form method="post" id="otp-form" action="<?= base_url('login/auth-otp')?>" class="needs-validation" novalidate>

                        <div id="otp-msg" class="text-center"></div>

                        <div id="otp-form-inputs">
                            <div class="form-group">
								<div class="input-container">
									<input type="text" name="otp_code" placeholder="" id="otp-code" required>
									<label for="otp-code"><strong>OTP Code: *</strong></label>
								</div>
                                <!-- <label for=""><strong>OTP Code : *</strong></label>
                                <input type="text" name="otp_code" class="form-control" id="otp-code" required>
                                <div class="invalid-feedback">
                                    Email is required.
                                </div> -->
                            </div>
                            
                            <button type="submit" class="btn btn-danger btn-block my-4">Verify</button>
                        </div>
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
    <script type="text/javascript" src="<?=base_url('assets/js/login.js?v=2.4')?>"></script>
</body>
</html>
