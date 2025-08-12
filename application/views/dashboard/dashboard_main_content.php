        <?=$top_nav?>
        <div id="admin-content">
			
            <?= $this->session->flashdata('message') ?>
			<div class="tab-pane fade show active" id="dashboard-coupons" role="tabpanel" aria-labelledby="nav-product-coupon-tab">
				<div class="d-sm-flex align-items-center justify-content-between mb-2">
					<h1 class="d-sm-inline-block h6 mb-0"><?=@$filter_display?></h1>
					<button href="#" id="dashboard-filter-btn" class="btn btn-md btn-danger" data-toggle="modal" data-target="#modal-dashboard-filter">
						<i class="fas fa-plus mr-1"></i> Apply Filter
					</button>
				</div>
				<nav>
                    <div class="nav nav-tabs" id="nav-tab1" role="tablist">
                        <a class="nav-link active" id="nav-dash-overview-tab" data-toggle="tab" href="#nav-dash-overview" role="tab" aria-controls="nav-dash-overview" aria-selected="true">
							<span class="fas fa-fw fa-chart-pie"></span>  OVERVIEW
                        </a>
                        
                        <a class="nav-link" id="nav-dash-credit-tab" data-toggle="tab" href="#nav-dash-credit" role="tab" aria-controls="nav-dash-credit" aria-selected="false">
							<span class="fas fa-fw fa-credit-card"></span>  CREDIT TRANS
                        </a>

						<a class="nav-link" id="nav-dash-non-credit-tab" data-toggle="tab" href="#nav-dash-non-credit" role="tab" aria-controls="nav-dash-non-credit" aria-selected="false">
							<span class="fas fa-fw fa-wallet"></span>  NON-CREDIT TRANS
                        </a>
						
						<a class="nav-link" id="nav-dash-inventory-tab" data-toggle="tab" href="#nav-dash-inventory" role="tab" aria-controls="nav-dash-inventory" aria-selected="false">
							<span class="fas fa-fw fa-cubes"></span>  RECON
                        </a>
                    </div>
                </nav>
				<div class="tab-content mt-2">
					<div class="tab-pane fade show active" id="nav-dash-overview" role="tabpanel" aria-labelledby="nav-dash-overview-tab">
						
						
						<div class="row">
							
							<div class="col-xl-6 col-lg-5 my-1 d-flex">
								<!-- Area Chart -->
								<div class="card shadow mb-4 flex-fill">
									<!-- Card Header - Dropdown -->
									<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
										<h6 class="m-0 font-weight-bold text-primary text-uppercase"><?=SEC_SYS_NAME?> Qty Volume base on Status</h6>
										
									</div>
									<!-- Card Body -->
									<div class="card-body">
										<div class="row">
											<div class="col-xl-4">
												<div class="chartInfo">
													<small>Chart Details</small>
													<ul id="volBasedOnStatLabel" class="list-group list-group-flush compact-list"></ul>
												</div>
											</div>
											<div class="col-xl-8">
												<div class="chart-container">
		
													<canvas id="volBasedOnStat"></canvas>
		
												</div>
												
											</div>
										</div>
										<!-- <div class="chart-info">
											<h3 id="info-title"></h3>
											<p id="info-value"></p>
											<p id="info-percentage"></p>
										</div> -->
										
										<!-- <div class="chartInfo">
											<label><strong>Chart Details</strong></label>
											<ul id="volBasedOnStatLabel"></ul>
										</div> -->
									</div>
									<div class="card-footer text-center font-weight-bold text-primary">
										<div id="totalVolBasedOnStat">TOTAL</div>
									</div>
								</div>
							</div>
		
							<div class="col-xl-6 col-lg-5 my-1 d-flex">
								<div class="card shadow mb-4 flex-fill">
									<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
										<h6 class="m-0 font-weight-bold text-primary text-uppercase">Active <?=SEC_SYS_NAME?> Qty Volume base on Payment Types</h6>
										
									</div>
									<div class="card-body">
										<div class="row">
											<div class="col-xl-4">
												<div class="chartInfo">
													<small>Chart Details</small>
													<ul id="volBasedOnPaymentTypeLabel" class="list-group list-group-flush compact-list"></ul>
												</div>
											</div>
											<div class="col-xl-8">
												<div class="chart-container">
		
													<canvas id="volBasedOnPaymentType"></canvas>
												</div>
											</div>
										</div>
									</div>
									<div class="card-footer text-center font-weight-bold text-primary">
										<div id="totalVolBasedOnPaymentType">TOTAL</div>
									</div>
								</div>
							</div>
							
							<div class="col-xl-6 col-lg-5 my-1 d-flex">
								<div class="card shadow mb-4 flex-fill">
									<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
										<h6 class="m-0 font-weight-bold text-primary text-uppercase"><?=SEC_SYS_NAME?> Amount base on Status</h6>
										
									</div>
									<div class="card-body">
										<div class="row">
											<div class="col-xl-4">
												<div class="chartInfo">
													<small>Chart Details</small>
													<ul id="amountBasedOnStatLabel" class="list-group list-group-flush compact-list"></ul>
												</div>
											</div>
											<div class="col-xl-8">
												<div class="chart-container">
		
													<canvas id="amountBasedOnStat" height="255"></canvas>
												</div>
											</div>
										</div>
									</div>
									<div class="card-footer text-center font-weight-bold text-primary">
										<div id="totalAmountBasedOnStat">TOTAL</div>
									</div>
								</div>
							</div>
							
							<div class="col-xl-6 col-lg-5 my-1 d-flex">
								<div class="card shadow mb-4 flex-fill">
									<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
										<h6 class="m-0 font-weight-bold text-primary text-uppercase">ACTIVE <?=SEC_SYS_NAME?> Amount base on Payment Type</h6>
										
									</div>
									<div class="card-body">
										<div class="row">
											<div class="col-xl-4">
												<div class="chartInfo">
													<small>Chart Details</small>
													<ul id="amountBasedOnPaymentTypeLabel" class="list-group list-group-flush compact-list"></ul>
												</div>
											</div>
											<div class="col-xl-8">
												<div class="chart-container">
		
													<canvas id="amountBasedOnPaymentType" height="255"></canvas>
												</div>
											</div>
										</div>
									</div>
									<div class="card-footer text-center font-weight-bold text-primary">
										<div id="totalAmountBasedOnPaymentType">TOTAL</div>
									</div>
								</div>
							</div>
							
							<!-- 
							<div class="col-xl-6 col-lg-5 my-2">
								<div class="card shadow mb-4">
									<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
										<h6 class="m-0 font-weight-bold text-primary">Active <?=SEC_SYS_NAME?> Expiration Volume </h6>
										
									</div>
									<div class="card-body">
										<div class="row">
											<div class="col-xl-4">
												<div class="chartInfo">
													<small>Chart Details</small>
													<ul id="volBasedOnExpirationLabel" class="list-group"></ul>
												</div>
											</div>
											<div class="col-xl-8">
												<div class="chart-container">
		
													<canvas id="volBasedOnExpiration"></canvas>
		
													
												</div>
												<div id="totalVolBasedOnExpiration">TOTAL</div>
											</div>
										</div>
									</div>
								</div>
							</div> -->
						</div>
						<div class="row">
							<div class="col-xl-12 col-lg-7 my-2">
								<!-- Area Chart -->
								<div class="card shadow mb-4">
									<!-- Card Header - Dropdown -->
									<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
										<h6 class="m-0 font-weight-bold text-primary text-uppercase">Monthly <?=SEC_SYS_NAME?> Trend</h6>
										
									</div>
									<!-- Card Body -->
									<div class="card-body">
										<div class="chart-container">
											<canvas id="voucherTrend" height="340"></canvas>
										</div>
									</div>
								</div>
							</div>
							
							
						</div>
					</div>

					<div class="tab-pane fade show" id="nav-dash-credit" role="tabpanel" aria-labelledby="nav-dash-credit-tab">
						<div class="tab-pane fade show active" id="credit-coupons" role="tabpanel" aria-labelledby="nav-dash-credit-tab">
							<nav>
								<div class="nav nav-tabs" id="nav-tab2" role="tablist">
									<a class="nav-link active" id="nav-receivables-tab" data-toggle="tab" href="#nav-receivables" role="tab" aria-controls="nav-receivables" aria-selected="true">
										<span class="fas fa-fw fa-file-invoice"></span>  RECEIVABLES
									</a>
									<a class="nav-link" id="nav-cleared-tab" data-toggle="tab" href="#nav-cleared" role="tab" aria-controls="nav-cleared" aria-selected="false">
										<span class="fas fa-fw fa-coins"></span>  CLEARED
									</a>
								</div>
							</nav>
							<div class="tab-content" id="nav-tabContent">
								<div class="tab-pane fade show active" id="nav-receivables" role="tabpanel" aria-labelledby="nav-receivables-tab">
									
									<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-4 mt-3">
										<div class="col mb-3">
											<div class="card card-secondary bg-info shadow h-80 py-1">
												<div class="card-body bubble-shadow">
													<div class="row no-gutters align-items-center">
														<div class="col mr-2">
															
															<div class="numbers">
																
																<p class="card-category font-weight-bold text-uppercase mb-1 text-white"><?=SEC_SYS_NAME?> Total Qty</p>
																<div class="card-title mb-0 font-weight-bold text-white" id="totalVoucherQty"></div>
															</div>
														</div>
														<div class="col-auto text-white">
															<i class="fas fa-gift fa-2x text-gray-300"></i>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col mb-3">
											<div class="card card-secondary bg-warning shadow h-80 py-1">
												<div class="card-body bubble-shadow">
													<div class="row no-gutters align-items-center">
														<div class="col mr-2">
															
															<div class="numbers">
																
																<p class="card-category font-weight-bold text-uppercase mb-1 text-white">Avg. <?=SEC_SYS_NAME?> Price</p>
																<div class="card-title mb-0 font-weight-bold text-white" id="avgCouponValue"></div>
															</div>
														</div>
														<div class="col-auto text-white">
															<i class="fas fa-coins fa-2x text-gray-300"></i>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col mb-3">
											<div class="card card-secondary bg-primary shadow h-80 py-1">
												<div class="card-body bubble-shadow">
													<div class="row no-gutters align-items-center">
														<div class="col mr-2">
															
															<div class="numbers">
																
																<p class="card-category font-weight-bold text-uppercase mb-1 text-white"><?=SEC_SYS_NAME?> Total Amount</p>
																<div class="card-title mb-0 font-weight-bold text-white" id="totalVoucherAmount"></div>
															</div>
														</div>
														<div class="col-auto text-white">
															<i class="fas fa-money-bill-alt fa-2x text-gray-300"></i>
														</div>
													</div>
												</div>
											</div>
										</div>
										
										<div class="col mb-3">
											<div class="card card-secondary bg-success shadow h-80 py-1">
												<div class="card-body bubble-shadow">
													<div class="row no-gutters align-items-center">
														<div class="col mr-2">
															
															<div class="numbers">
																
																<p class="card-category font-weight-bold text-uppercase mb-1 text-white">Avg. Payment Terms</p>
																<div class="card-title mb-0 font-weight-bold text-white" id="avgPaymentTerms"></div>
															</div>
														</div>
														<div class="col-auto text-white">
															<i class="fas fa-file-contract fa-2x text-gray-300"></i>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col mb-3">
											<div class="card card-danger bg-danger shadow h-80 py-1">
												<div class="card-body bubble-shadow">
													<div class="row no-gutters align-items-center">
														<div class="col mr-2">
															
															<div class="numbers">
																
																<p class="card-category font-weight-bold text-uppercase mb-1 text-white">Nearest Due Date</p>
																<div class="card-title mb-0 font-weight-bold text-white" id="nearDue"></div>
															</div>
														</div>
														<div class="col-auto text-white">
															<i class="fas fa-calendar-check fa-2x text-gray-300"></i>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="row mb-2">
										<div class="offset-md-10 col-md-2">
											<a href="#" class="dl_tbl_dashboard_receivables"><i class="fas fa-download"></i> &nbsp;Download</a>
										</div>
									</div>
									<div class="table-responsive">
										<table class="table table-striped table-condensed" width="100%" id="tbl_dashboard_receivables">
											<thead>
												<tr>
													<th scope="col"><?=SEC_SYS_NAME?> Name</th>
													<th scope="col">Customer</th>
													<th scope="col">Category</th>
													<th scope="col">Qty</th>
													<th scope="col">Price</th>
													<th scope="col">Amount</th>

													<th scope="col">Terms</th>
													<th scope="col">Due On</th>
													<th scope="col">Creator</th>
													<th scope="col">Created On</th>
													<th scope="col">Payment Status</th>
												</tr>
											</thead>
											
											<tbody>
												
											</tbody>
											<tfoot>
												<tr>
													<th colspan="3"></th>

													<th></th>
													<th></th>
													<th></th>
													
													
													<th colspan="5"></th>
												</tr>
											</tfoot>
										</table></br>
									</div>
								</div>
			
								<div class="tab-pane fade" id="nav-cleared" role="tabpanel" aria-labelledby="nav-cleared-tab">
									<div class="row">
										<div class="col-xl-3 col-md-6 col-sm-6 mt-3 mb-3">
											<div class="card card-secondary bg-info shadow h-80 py-1">
												<div class="card-body bubble-shadow">
													<div class="row no-gutters align-items-center">
														<div class="col mr-2">
															
															<div class="numbers">
																
																<p class="card-category font-weight-bold text-uppercase mb-1 text-white"><?=SEC_SYS_NAME?> Total Qty</p>
																<div class="card-title mb-0 font-weight-bold text-white" id="totalVoucherQtyPaid"></div>
															</div>
														</div>
														<div class="col-auto text-white">
															<i class="fas fa-gift fa-2x text-gray-300"></i>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-xl-3 col-md-6 col-sm-6 mt-3 mb-3">
											<div class="card card-secondary bg-warning shadow h-80 py-1">
												<div class="card-body bubble-shadow">
													<div class="row no-gutters align-items-center">
														<div class="col mr-2">
															
															<div class="numbers">
																
																<p class="card-category font-weight-bold text-uppercase mb-1 text-white">Avg. <?=SEC_SYS_NAME?> Price</p>
																<div class="card-title mb-0 font-weight-bold text-white" id="avgCouponValuePaid"></div>
															</div>
														</div>
														<div class="col-auto text-white">
															<i class="fas fa-coins fa-2x text-gray-300"></i>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-xl-3 col-md-6 col-sm-6 mt-3 mb-3">
											<div class="card card-secondary bg-primary shadow h-80 py-1">
												<div class="card-body bubble-shadow">
													<div class="row no-gutters align-items-center">
														<div class="col mr-2">
															
															<div class="numbers">
																
																<p class="card-category font-weight-bold text-uppercase mb-1 text-white"><?=SEC_SYS_NAME?> Total Amount</p>
																<div class="card-title mb-0 font-weight-bold text-white" id="totalVoucherAmountPaid"></div>
															</div>
														</div>
														<div class="col-auto text-white">
															<i class="fas fa-money-bill-alt fa-2x text-gray-300"></i>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-xl-3 col-md-6 col-sm-6 mt-3 mb-3">
											<div class="card card-secondary bg-success shadow h-80 py-1">
												<div class="card-body bubble-shadow">
													<div class="row no-gutters align-items-center">
														<div class="col mr-2">
															
															<div class="numbers">
																
																<p class="card-category font-weight-bold text-uppercase mb-1 text-white">Avg. Payment Terms</p>
																<div class="card-title mb-0 font-weight-bold text-white" id="avgPaymentTermsPaid"></div>
															</div>
														</div>
														<div class="col-auto text-white">
															<i class="fas fa-file-contract fa-2x text-gray-300"></i>
														</div>
													</div>
												</div>
											</div>
										</div>
										
									</div>
									<div class="row mb-2">
										<div class="offset-md-10 col-md-2">
											<a href="#" class="dl_tbl_dashboard_cleared"><i class="fas fa-download"></i> &nbsp;Download</a>
										</div>
									</div>
									<div class="table-responsive">
										<table class="table table-striped table-condensed" width="100%" id="tbl_dashboard_cleared">
											<thead>
												<tr>
													<th scope="col"><?=SEC_SYS_NAME?> Name</th>
													<th scope="col">Customer</th>
													<th scope="col">Category</th>

													<th scope="col">Qty</th>
													<th scope="col">Price</th>
													<th scope="col">Amount</th>

													<th scope="col">Terms</th>
													<th scope="col">Due On</th>
													<th scope="col">Creator</th>
													<th scope="col">Created On</th>
													<th scope="col">Payment Status</th>
												</tr>
											</thead>
											
											<tbody>
												
											</tbody>
											<tfoot>
												<tr>
													<th colspan="3"></th>

													<th></th>
													<th></th>
													<th></th>
													
													<th colspan="5"></th>
												</tr>
											</tfoot>
										</table></br>
									</div>
								</div>
			
							</div>
						</div>
					</div>
					
					<div class="tab-pane fade show" id="nav-dash-non-credit" role="tabpanel" aria-labelledby="nav-dash-non-credit-tab">
						
						<div class="row">
							<div class="col-xl-4 col-md-6 col-sm-6 mt-3 mb-3">
								<div class="card card-secondary bg-info shadow h-80 py-1">
									<div class="card-body bubble-shadow">
										<div class="row no-gutters align-items-center">
											<div class="col mr-2">
												
												<div class="numbers">
													
													<p class="card-category font-weight-bold text-uppercase mb-1 text-white"><?=SEC_SYS_NAME?> Total Qty</p>
													<div class="card-title mb-0 font-weight-bold text-white" id="totalVoucherQtyNonCredit"></div>
												</div>
											</div>
											<div class="col-auto text-white">
												<i class="fas fa-gift fa-2x text-gray-300"></i>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-xl-4 col-md-6 col-sm-6 mt-3 mb-3">
								<div class="card card-secondary bg-warning shadow h-80 py-1">
									<div class="card-body bubble-shadow">
										<div class="row no-gutters align-items-center">
											<div class="col mr-2">
												
												<div class="numbers">
													
													<p class="card-category font-weight-bold text-uppercase mb-1 text-white">Avg. <?=SEC_SYS_NAME?> Price</p>
													<div class="card-title mb-0 font-weight-bold text-white" id="avgCouponValueNonCredit"></div>
												</div>
											</div>
											<div class="col-auto text-white">
												<i class="fas fa-coins fa-2x text-gray-300"></i>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-xl-4 col-md-6 col-sm-6 mt-3 mb-3">
								<div class="card card-secondary bg-primary shadow h-80 py-1">
									<div class="card-body bubble-shadow">
										<div class="row no-gutters align-items-center">
											<div class="col mr-2">
												
												<div class="numbers">
													
													<p class="card-category font-weight-bold text-uppercase mb-1 text-white"><?=SEC_SYS_NAME?> Total Amount</p>
													<div class="card-title mb-0 font-weight-bold text-white" id="totalVoucherAmountNonCredit"></div>
												</div>
											</div>
											<div class="col-auto text-white">
												<i class="fas fa-money-bill-alt fa-2x text-gray-300"></i>
											</div>
										</div>
									</div>
								</div>
							</div>
							
							
						</div>

						<div class="row mb-2">
							<div class="offset-md-10 col-md-2">
								<a href="#" class="dl_tbl_dashboard_non_credit"><i class="fas fa-download"></i> &nbsp;Download</a>
							</div>
						</div>
						<div class="table-responsive">
							<table class="table table-striped table-condensed" width="100%" id="tbl_dashboard_non_credit">
								<thead>
									<tr>
										<th scope="col"><?=SEC_SYS_NAME?> Name</th>
										<th scope="col">Customer</th>
										<th scope="col">Category</th>

										<th scope="col">Qty</th>
										<th scope="col">Price</th>
										<th scope="col">Amount</th>

										<th scope="col">Payment Type</th>
										<th scope="col">Due On</th>
										<th scope="col">Creator</th>
										<th scope="col">Created On</th>
									</tr>
								</thead>
								
								<tbody>
									
								</tbody>
								<tfoot>
									<tr>
										<th colspan="3"></th>
										<th></th>
										<th></th>
										<th></th>
										
										<th colspan="4"></th>
									</tr>
								</tfoot>
							</table></br>
						</div>
					</div>

					<div class="tab-pane fade show" id="nav-dash-inventory" role="tabpanel" aria-labelledby="nav-dash-inventory-tab">
						<div class="tab-pane fade show active" id="credit-coupons" role="tabpanel" aria-labelledby="nav-dash-inventory-tab">
							<nav>
								<div class="nav nav-tabs" id="nav-tab3" role="tablist">
									<a class="nav-link active" id="nav-monthly-all-tab" data-toggle="tab" href="#nav-monthly-all" role="tab" aria-controls="nav-monthly-all" aria-selected="false">
										<span class="fas fa-fw fa-calendar"></span>  PER MONTH
									</a>
									<a class="nav-link" id="nav-transaction-all-tab" data-toggle="tab" href="#nav-transaction-all" role="tab" aria-controls="nav-transaction-all" aria-selected="true">
										<span class="fas fa-fw fa-money-bill-wave"></span>  PER TRANSACTION
									</a>
									<a class="nav-link" id="nav-customer-all-tab" data-toggle="tab" href="#nav-customer-all" role="tab" aria-controls="nav-customer-all" aria-selected="false">
										<span class="fas fa-fw fa-users"></span>  PER CUSTOMER
									</a>
									
								</div>
							</nav>
							<div class="tab-content" id="nav-tabContent">
								<div class="tab-pane fade show active" id="nav-monthly-all" role="tabpanel" aria-labelledby="nav-monthly-all-tab">
									
									<div class="container-fluid row mb-2">
										<div class="offset-md-8 col-md-2">
											<input type="checkbox" id="is_active_only_for_monthly_all" class="form-check-input" value="1">
											<label class="form-check-label" for="is_active_only_for_monthly_all">Display Active Only</label>
										</div>
										<div class="col-md-2 text-right">
											<a href="#" class="dl_tbl_dashboard_monthly_all"><i class="fas fa-download"></i> &nbsp;Download</a>
										</div>
									</div>
									<div class="table-responsive">
										<table class="table table-striped table-condensed" width="100%" id="tbl_dashboard_monthly_all">
											<thead>
												<tr>
													<th scope="col">Month</th>
													<th scope="col">Category</th>
													
													<th scope="col">Qty</th>
													<th scope="col">Active</th>
													<th scope="col">Redeemed</th>
													<th scope="col">Expired</th>
													<th scope="col">Inactive</th>

													<th scope="col">Avg. Price</th>
													<th scope="col">Amount</th>
													
												</tr>
											</thead>
											
											<tbody>
												
											</tbody>
											<tfoot>
												<tr>
													<th colspan="2"></th>
													
													<th></th>
													<th></th>
													<th></th>
													<th></th>
													<th></th>
													<th></th>
													<th></th>
												</tr>
											</tfoot>
										</table></br>
									</div>
								</div>

								<div class="tab-pane fade" id="nav-transaction-all" role="tabpanel" aria-labelledby="nav-transaction-all-tab">
									
									
									<div class="container-fluid row mb-2">
										<div class="offset-md-8 col-md-2">
											<input type="checkbox" id="is_active_only_for_transaction_all" class="form-check-input" value="1">
											<label class="form-check-label" for="is_active_only_for_transaction_all">Display Active Only</label>
										</div>
										<div class="col-md-2 text-right">
											<a href="#" class="dl_tbl_dashboard_transaction_all"><i class="fas fa-download"></i> &nbsp;Download</a>
										</div>
									</div>
									<div class="table-responsive">
										<table class="table table-striped table-condensed" width="100%" id="tbl_dashboard_transaction_all">
											<thead>
												<tr>
													<th scope="col"><?=SEC_SYS_NAME?> Name</th>
													<th scope="col">Customer</th>
													<th scope="col">Category</th>
													
													<th scope="col">Qty</th>
													<th scope="col">Active</th>
													<th scope="col">Redeemed</th>
													<th scope="col">Expired</th>
													<th scope="col">Inactive</th>
													<th scope="col">Price</th>
													<th scope="col">Amount</th>

													<th scope="col">Payment Type</th>
													<th scope="col">Creator</th>
													<th scope="col">Payment Status</th>
												</tr>
											</thead>
											
											<tbody>
												
											</tbody>
											<tfoot>
												<tr>
													<th colspan="3"></th>
													
													<th></th>
													<th></th>
													<th></th>
													<th></th>
													<th></th>
													<th></th>
													<th></th>
													
													<th colspan="3"></th>
												</tr>
											</tfoot>
										</table></br>
									</div>
								</div>
			
								<div class="tab-pane fade" id="nav-customer-all" role="tabpanel" aria-labelledby="nav-customer-all-tab">
									
									<div class="container-fluid row mb-2">
										<div class="offset-md-8 col-md-2">
											<input type="checkbox" id="is_active_only_for_customer_all" class="form-check-input" value="1">
											<label class="form-check-label" for="is_active_only_for_customer_all">Display Active Only</label>
										</div>
										<div class="col-md-2 text-right">
											<a href="#" class="dl_tbl_dashboard_customer_all"><i class="fas fa-download"></i> &nbsp;Download</a>
										</div>
									</div>
									<div class="table-responsive">
										<table class="table table-striped table-condensed" width="100%" id="tbl_dashboard_customer_all">
											<thead>
												<tr>
													<th scope="col">Customer</th>
													<th scope="col">Category</th>
													
													<th scope="col">Qty</th>
													<th scope="col">Active</th>
													<th scope="col">Redeemed</th>
													<th scope="col">Expired</th>
													<th scope="col">Inactive</th>
													<th scope="col">Avg. Price</th>
													<th scope="col">Amount</th>
												</tr>
											</thead>
											
											<tbody>
												
											</tbody>
											<tfoot>
												<tr>
													<th colspan="2"></th>
													
													<th></th>
													<th></th>
													<th></th>
													<th></th>
													<th></th>
													<th></th>
													<th></th>
												</tr>
											</tfoot>
										</table></br>
									</div>
								</div>
								
								
			
							</div>
						</div>
					</div>

					
				</div>
			</div>

			<div class="modal fade" id="modal-dashboard-filter"   role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
				<div class="modal-dialog modal-sm" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h6 class="modal-title" id="exampleModalLabel"><strong>Apply Filter</strong></h6>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<form method="POST" action="<?=base_url('dashboard/dashboard-main')?>" id="apply-dashboard-filter">
							<div class="modal-body">
								<div class="row">
									<div class="col-lg-12">
										<div class="form-group">
											<!-- <label>Delivery Date</label> -->
											<!-- <label>Date Range:</label><br>
                                    		<input type="text" class="form-control" name="" id="dashboard-filter-calendar" value="<?=@$range_date?>" placeholder="Pick Date"> -->
											<label>Date Range:</label><br>
											<div class="input-group">
												<input type="text" class="form-control" name="date-range" id="dashboard-filter-calendar" value="<?=@$range_date?>" placeholder="Pick Date">
												<span class="input-group-text">
													<i class="fa fa-calendar"></i>
												</span>
											</div>
										</div>

										<div class="form-group">
											<div class="form-check">
												<input type="checkbox" name="end_up_to_date" class="form-check-input" id="up_to_date" <?=$up_tp_date_checked?> value="1">
												<label class="form-check-label" for="up_to_date">Always end to current date</label>
											</div>
										</div>
	
										<!-- <div class="form-group">
											<label>To:</label><br>
											<label for="" class="input-group">
												<div class="input-group p-0">
													<input type="text" placeholder="Pick a month" class="form-control form-control-md datepicker" name="yearTo" required="true" value="<?=@$yearTo;?>">
					
													<div class="input-group-append"><span class="input-group-text px-4"><i class="fa fa-calendar datepicker-icon"></i></span></div>
												</div>
											</label>
										</div> -->
									</div>
								</div>
	
							</div>
						
							<div class="modal-footer">
								<button type="submit" class="btn btn-danger btn-md btn-round">Load</button>
							</div>
						</form>
					</div>
				</div>
			</div>
        </div>



