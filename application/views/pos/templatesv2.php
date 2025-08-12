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
        <link rel="stylesheet" type="text/css" href="<?=base_url('assets/css/admin.css?v=2.7')?>">

        <title><?=SYS_NAME?> System - <?=$title?></title>

		<style>
			body {
				background-color: #f8f9fa;
				font-family: 'Poppins', sans-serif;
			}
			.container {
				max-width: 600px;
				background: #ffffff;
				padding: 30px;
				border-radius: 10px;
				box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
			}
			h2, h3 {
				color: #343a40;
			}
			.btn-primary {
				background-color: #007bff;
				border: none;
				transition: background 0.3s;
			}
			.btn-primary:hover {
				background-color: #0056b3;
			}
			.accordion-button {
				background-color: #007bff;
				color: white;
				font-weight: bold;
			}
			.accordion-button:not(.collapsed) {
				background-color: #0056b3;
			}
			.accordion-body {
				background: #f1f3f5;
			}
		</style>
    </head>
    <body  id="<?=$title?>">

        <?php $uri = $this->uri->segment(2);?>

        <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
			<div class="container-fluid">
				<a class="navbar-brand" href="#"><img src="<?=base_url('assets/img/bounty-logo.png')?>" class="img-responsive"></a>
			</div>
		</nav>
		
		<?=$content?>


        <script type="text/javascript" src="<?=base_url('assets/js/jquery-3.3.1.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/moment.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/daterangepicker.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/popper.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/bootstrap.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/jquery.mCustomScrollbar.concat.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/jquery.dataTables.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/dataTables.bootstrap4.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/font-awesome.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/select2.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/select2-dropdownPosition.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/notifications.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/bootstrap-datepicker.min.js')?>"></script>
        <script type="text/javascript" src="<?=base_url('assets/js/admin.js?v=3.9')?>"></script>
    </body>
</html>
