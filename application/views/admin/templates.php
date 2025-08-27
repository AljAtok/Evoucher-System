<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="<?= base_url('assets/img/favicon.ico') ?>" type="image/x-icon" />
        <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/bootstrap.min.css')?>"/>
        <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/jquery.mCustomScrollbar.min.css')?>">
        <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/dataTables.bootstrap4.min.css')?>">
		<link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/responsive.bootstrap4.min.css')?>">
		<link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/fixedColumns.bootstrap4.min.css')?>">
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
                <div class="sidebar-header text-center ">
					<div class="d-flex justify-content-center">
						<img src="<?=base_url('assets/img/bounty-logo.png')?>" class="img-responsive">
					</div>
                    <h6><strong><?=SYS_NAME?></strong></h4>
                </div>

                <ul class="list-unstyled components" id="main-menu">
                    <li>
                        <a href="<?=base_url('dashboard')?>"><strong><span class="fas fa-fw fa-tachometer-alt"></span> Dashboard</strong></a>
                    </li>
                    <li>
                        <a href="<?=base_url('admin/employee')?>"><strong><span class="fas fa-fw fa-user"></span> Users</strong></a>
                    </li>
                    <li>
                        <a href="<?=base_url('admin/employee-list')?>"><strong><span class="fas fa-fw fa-user"></span> Employee List</strong></a>
                    </li>
                    <li>
                        <a href="<?=base_url('admin/voucher-summary')?>"><strong><span class="far fa-fw fa-list-alt"></span> <?=SEC_SYS_NAME?> Summary</strong></a>
                    </li>
                    <!-- <li>
                        <a href="<?=base_url('admin/stores/')?>"><strong><span class="fas fa-fw fa-store"></span> Stores</strong></a>
                    </li> -->
                    <li>
                        <a href="#fm-sub-menu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><strong><span class="fas fa-fw fa-gift"></span> <?=SYS_NAME?></strong></a>
                        <?php $fm_links = ['standard-coupon', 'product-coupon', 'standard_coupon', 'product_coupon'] ?>
                        <ul class="collapse list-unstyled <?= (in_array($uri, $fm_links)) ? 'show' : '' ?>" id="fm-sub-menu">
                            <!-- <li>
                                <a href="<?=base_url('admin/standard-coupon')?>"><span class="fas fa-fw fa-chevron-circle-right"></span>  Standard <?=SEC_SYS_NAME?></a>
                            </li> -->
                            <li>
                                <a href="<?=base_url('admin/product-coupon')?>"><span class="fas fa-fw fa-chevron-circle-right"></span>  Product <?=SEC_SYS_NAME?></a>
                            </li>
                        </ul>
                    </li>

					<li>
                        <a href="<?=base_url('admin/redeem-coupon-v1')?>"><strong><span class="fas fa-fw fa-stamp"></span>Old Redeem <?=SEC_SYS_NAME?></strong></a>
                    </li>
					
					<li>
                        <a href="<?=base_url('admin/redeem-coupon')?>"><strong><span class="fas fa-fw fa-stamp"></span>New Redeem <?=SEC_SYS_NAME?></strong></a>
                    </li>

					<li>
                        <a href="<?=base_url('admin/redeem-coupon-emp')?>"><strong><span class="fas fa-fw fa-stamp"></span> Redeem <?=SEC_SYS_NAME?></strong></a>
                    </li>

					<li>
                        <a href="<?=base_url('admin/redeem-logs')?>"><strong><span class="far fa-fw fa-file-alt"></span> Redeem Logs</strong></a>
                    </li>
					
					<li>
                        <a href="<?=base_url('admin/raffle-draw')?>"><strong><span class="far fa-fw fa-file-alt"></span> Raffle Draw</strong></a>
                    </li>
                    <!-- <li>
                        <a href="<?=base_url('login/logout-process/')?>"><strong><span class="fas fa-fw fa-power-off"></span> Logout</strong></a>
                    </li> -->
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

		<script type="text/javascript" src="<?=base_url('assets/js/datatables.responsive.min.js')?>"></script>
		<script type="text/javascript" src="<?=base_url('assets/js/dataTables.fixedColumns.min.js')?>"></script>

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
        
		<script type="text/javascript" src="<?=base_url('assets/js/admin.js?v=4.0')?>"></script>
		<script type="text/javascript" src="<?=base_url('assets/js/adv-order.js?v=1.0')?>"></script>
		<?php if($uri == 'raffle-draw'): ?>
			<script type="text/javascript" src="<?=base_url('assets/js/raffle.js?v=4.0')?>"></script>
		<?php endif; ?>

    </body>
</html>
