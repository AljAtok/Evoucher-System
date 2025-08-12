<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="<?= base_url('assets/img/favicon.ico') ?>" type="image/x-icon" />
        <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/bootstrap.min.css')?>"/>
        <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/jquery.mCustomScrollbar.min.css')?>">
        <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/dataTables.bootstrap4.min.css')?>">
        <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/select2.min.css')?>"/>
        <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/select2-bootstrap4.min.css')?>">
        <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/daterangepicker.css') ?>" />
        <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/lobibox.min.css') ?>" />
        <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/datepicker.min.css') ?>" />
		<link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/buttons.dataTables.min.css')?>" />
        <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/admin.css?v=2.7')?>">

        <title><?=SYS_NAME?> System - <?=$title?></title>
    </head>
    <body  id="<?=$title?>">

        <?php $uri = $this->uri->segment(2);?>

        <div class="wrapper">

            <div id="loader-div">
                <div id="loader-wrapper">
                    <div id="loader"></div>
                </div>
            </div>

            <input type="hidden" value="<?=base_url($this->uri->segment(1))?>" id="base_url">

            <!-- Sidebar -->
            <nav id="sidebar">
                <div class="sidebar-header text-center">
                    <img src="<?=base_url('assets/img/bounty-logo.png')?>" class="img-responsive">
                    <h6><strong><?=SYS_NAME?></strong></h4>
                </div>

                <ul class="list-unstyled components" id="main-menu">
                    
					<!-- <li>
                        <a href="<?=base_url('first-approver/redeem-coupon')?>"><strong><span class="fas fa-fw fa-stamp"></span> Redeem <?=SEC_SYS_NAME?></strong></a>
                    </li> -->
					<li>
                        <a href="<?=base_url('dashboard')?>"><strong><span class="fas fa-fw fa-tachometer-alt"></span> Dashboard</strong></a>
                    </li>
                    <li>
                        <a href="#fm-sub-menu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><strong><span class="fas fa-fw fa-gift"></span> <?=SYS_NAME?></strong></a>
                        <?php $fm_links = ['standard-coupon', 'product-coupon', 'standard_coupon', 'product_coupon'] ?>
                        <ul class="collapse list-unstyled <?= (in_array($uri, $fm_links)) ? 'show' : '' ?>" id="fm-sub-menu">
                            <li>
                                <a href="<?=base_url('first-approver/product-coupon')?>"><span class="fas fa-fw fa-chevron-circle-right"></span>  Product <?=SEC_SYS_NAME?></a>
                            </li>
                        </ul>
                    </li>
                    
                </ul>

            </nav>
            <!-- Page Content -->
            <div id="content" class="mb-5">
                <?=$content?>
            </div>
        </div>


        <script type="text/javascript" src="<?=base_url('assets/js/jquery-3.3.1.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/moment.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/daterangepicker.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/popper.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/bootstrap.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/jquery.mCustomScrollbar.concat.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/jquery.dataTables.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/dataTables.bootstrap4.min.js')?>"></script>

		<script type="text/javascript" src="<?=base_url('assets/js/dataTables.buttons.min.js')?>"></script>
		<script type="text/javascript" src="<?=base_url('assets/js/jszip.min.js')?>"></script>
		<script type="text/javascript" src="<?=base_url('assets/js/buttons.html5.min.js')?>"></script>

        <script type="text/javascript" src="<?=base_url('assets/js/font-awesome.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/select2.min.js')?>"></script>
        <!-- <script type="text/javascript" src="<?=base_url('assets/js/select2-dropdownPosition.js')?>"></script> -->
        <script type="text/javascript" src="<?=base_url('assets/js/notifications.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/bootstrap-datepicker.min.js')?>"></script>

		<!-- <script type="module" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"> </script> -->
		<script type="text/javascript" src="<?=base_url()?>assets/js/chart.min.js"></script>
		 
		<!-- jQuery Sparkline -->
		<script src="<?=base_url()?>assets/js/jquery.sparkline.min.js"></script>
		<!-- Chart Circle -->
		<script src="<?=base_url()?>assets/js/circles.min.js"></script>
		
        <script type="text/javascript" src="<?=base_url('assets/js/admin.js?v=3.9')?>"></script>
    </body>
</html>
