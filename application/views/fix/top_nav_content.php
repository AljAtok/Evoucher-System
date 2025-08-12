<?php

$bg_color = 'bg-light';
$text_color = $bg_color == 'bg-light' ? '' : 'text-white';

?>
<nav class="navbar navbar-expand-lg navbar-light <?=$bg_color?> <?=$text_color?> sticky-top border-bottom">
	<a href="#" id="sidebarCollapse">
		<i class="fas fa-bars fa-lg"></i>
	</a>
	
	<!-- <div class="container-fluid">
		<ul class="navbar-nav me-auto">
			<li class="nav-item">
				<?=$title?>
			</li>
			
		</ul>
		<ul class="navbar-nav ms-auto">
			<li class="nav-item">
				<span class="nav-link">
					<i class="fas fa-user-circle"></i> <?=$this->session->userdata['evoucher-user']['user_fullname']?>
				</span>
			</li>
		</ul>
	</div> -->
	<div class="container-fluid">
		<ul class="navbar-nav me-auto">
			<li class="nav-item">
				<?=@$title?>
			</li>
		</ul>
		<ul class="navbar-nav ml-auto">
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle <?=$text_color?>" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<i class="fas fa-user-circle"></i>  <?=$this->session->userdata['evoucher-user']['user_fullname']?>
				</a>
				<div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
					<a class="dropdown-item" href="<?=base_url('/profile')?>"><span class="fas fa-fw fa-user-tie"></span> Profile</a>
					<div class="dropdown-divider"></div>
					<a class="dropdown-item" href="<?=base_url('login/logout-process/')?>"><span class="fas fa-fw fa-power-off"></span> Logout</a>
				</div>
			</li>
		</ul>
	</div>
</nav>
