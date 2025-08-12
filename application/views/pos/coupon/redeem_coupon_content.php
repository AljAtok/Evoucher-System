
<?php

$bg_color = 'bg-light';
$text_color = $bg_color == 'bg-light' ? '' : 'text-white';

?>
<nav class="navbar navbar-expand-lg navbar-light <?=$bg_color?> <?=$text_color?> sticky-top border-bottom">
	<a href="#" id="sidebarCollapse" class="my-2">
		<i class="fas fa-bars fa-lg"></i>
	</a>
	
	<div class="container-fluid">
		<ul class="navbar-nav me-auto">
			<li class="nav-item">
				<?=@$title?>
			</li>
		</ul>
		
	</div>
</nav>
<!-- <div class="container py-4"> -->
<div id="admin-content">
	<div class="tab-pane fade show active" id="redeem-coupons" role="tabpanel" aria-labelledby="nav-redeem-coupon-tab">
		<nav>
			<div class="nav nav-tabs" id="nav-tab" role="tablist">
				<a class="nav-link active" id="nav-redeem-coupon-tab" data-toggle="tab" href="#nav-redeem-coupon" role="tab" aria-controls="nav-redeem-coupon" aria-selected="true">
					<span class="fas fa-fw fa-stamp"></span>  REDEEM
				</a>
				
				<a class="nav-link" id="nav-faq-coupon-tab" data-toggle="tab" href="#nav-faq-coupon" role="tab" aria-controls="nav-faq-coupon" aria-selected="false">
					<span class="fas fa-fw fa-question-circle"></span>  FAQ's
				</a>
			</div>
		</nav>
	</div>
	<div class="tab-content my-4">
		<div class="tab-pane fade show active" id="nav-redeem-coupon" role="tabpanel" aria-labelledby="nav-redeem-coupon-tab">

			<h3 class="text-center text-danger" style="display: block;">Redeem <?=SEC_SYS_NAME?></h3><br />
			<div id="message-box"></div>
			<form action="#" autocomplete="OFF">
				<div class="row">
					<div class="col-lg-4 offset-lg-4">
						<div class="form-group">
							<input type="hidden" name="user_id" value="<?=$user_id?>">
							<div class="input-container">
								<input type="text" name="code" placeholder="" id="code">
								<label for="code"><strong><?=SEC_SYS_NAME?> Code</strong></label>
							</div>
						</div>
						
						<div class="form-group">
							<div class="input-container">
								<input type="text" name="store-code" placeholder="" id="store-code">
								<label for="store-code"><strong>Store Code (FOR REDEEM ONLY)</strong></label>
							</div>
						</div>

						<div class="form-group">
							<div class="input-container">
								<input type="text" name="crew-code" placeholder="" id="crew-code">
								<label for="crew-code"><strong>Your Crew Code (FOR REDEEM ONLY)</strong></label>
							</div>
						</div>
						
						

					</div>
				</div>
				<div class="row d-flex justify-content-center w-100">
					
					<div class="form-group mt-3">
						
						<div class="col-md-12 text-left">
							<button type="button" class="btn btn-danger btn-md emboss" id="coupon-verify-button-new">VERIFY</button>&nbsp;&nbsp;
							<button type="button" class="btn btn-danger btn-md emboss" id="coupon-redeem-button-new">REDEEM</button>
						</div>
					</div>
				</div>
			</form>
		</div>
		
		
		<div class="tab-pane fade show" id="nav-faq-coupon" role="tabpanel" aria-labelledby="nav-faq-coupon-tab">
			

			<div class="container">
				<h2 class="text-center mb-4">Frequently Asked Questions about Redeeming</h2>
				<div class="accordion" id="faqAccordion">
					<?php foreach ($parent_faqs as $r): ?>
					<div class="card acc-card">
						<div class="card-header acc-card-hdr" id="faqHeading<?=$r->faq_id?>" data-toggle="collapse" data-target="#faqCollapse<?=$r->faq_id?>" aria-expanded="<?=$r->faq_id==1 ? 'true': 'false'?>" aria-controls="faqCollapse<?=$r->faq_id?>">
							<h2 class="mb-0">
								<button class="text-white btn btn-link <?=$r->faq_id==1 ? '': 'collapsed'?>" type="button">
									<?=$r->faq_desc?>
								</button>
							</h2>
						</div>
						<div id="faqCollapse<?=$r->faq_id?>" class="collapse <?=$r->faq_id==1 ? 'show': ''?>" aria-labelledby="faqHeading<?=$r->faq_id?>" data-parent="#faqAccordion">
							<div class="card-body acc-card-body">
								<div class="table-responsive">
									<table class="table table-bordered bg-light table-condensed" width="100%">
										
										<tbody>
											<?php
											foreach ($child_faqs as $row):
												if($row->parent_id == $r->faq_id):
											?>
													<tr>
														<td class="<?=$row->class_name?>" scope="col"><?=$row->child_name?></td>
													</tr>
											<?php
											    endif;
											endforeach;
											?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>

					<?php endforeach; ?>
					
					<!-- <div class="card">
						<div class="card-header" id="faqHeading2">
							<h2 class="mb-0">
								<button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
									How do I create a voucher code?
								</button>
							</h2>
						</div>
						<div id="faqCollapse2" class="collapse" aria-labelledby="faqHeading2" data-parent="#faqAccordion">
							<div class="card-body">
								You can generate voucher codes through the eVoucher system, typically using an automated code generation tool that ensures uniqueness and security.
							</div>
						</div>
					</div>
					
					<div class="card">
						<div class="card-header" id="faqHeading3">
							<h2 class="mb-0">
								<button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
									How can I redeem a voucher?
								</button>
							</h2>
						</div>
						<div id="faqCollapse3" class="collapse" aria-labelledby="faqHeading3" data-parent="#faqAccordion">
							<div class="card-body">
								Voucher codes can be redeemed through a designated platform or app by entering the code, which is then validated against the system's database.
							</div>
						</div>
					</div> -->
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="modal-show-how-tos" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h6 class="modal-title" id=""><strong>FREQUENTLY ASKED QUESTIONS</strong></h6>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="row container ml-1">
						<h2 class="text-center mb-4">Frequently Asked Questions about Redeeming</h2>
						<div class="accordion w-100" id="faqAccordionMod">
							<?php foreach ($parent_faqs as $r): ?>
							<div class="card acc-card">
								<div class="card-header acc-card-hdr" id="faqHeadingMod<?=$r->faq_id?>" data-toggle="collapse" data-target="#faqCollapseMod<?=$r->faq_id?>" aria-expanded="<?=$r->faq_id==1 ? 'true': 'false'?>" aria-controls="faqCollapseMod<?=$r->faq_id?>">
									<h2 class="mb-0">
										<button class="text-white btn btn-link <?=$r->faq_id==1 ? '': 'collapsed'?>" type="button" >
											<?=$r->faq_desc?>
										</button>
									</h2>
								</div>
								<div id="faqCollapseMod<?=$r->faq_id?>" class="collapse <?=$r->faq_id==1 ? 'show': ''?>" aria-labelledby="faqHeadingMod<?=$r->faq_id?>" data-parent="#faqAccordionMod">
									<div class="card-body acc-card-body">
										<div class="table-responsive">
											<table class="table table-bordered bg-light table-condensed" width="100%">
												
												<tbody>
													<?php
													foreach ($child_faqs as $row):
														if($row->parent_id == $r->faq_id):
													?>
															<tr>
																<td class="<?=$row->class_name?>" scope="col"><?=$row->child_name?></td>
															</tr>
													<?php
														endif;
													endforeach;
													?>
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>

							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>



    
</div>
