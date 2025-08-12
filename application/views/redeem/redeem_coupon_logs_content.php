        <?=$top_nav?>
        <div id="admin-content">
            <?= $this->session->flashdata('message') ?>

            <div class="row">
                <div class="offset-md-7 col-md-3">
                    <input type="text" class="form-control" name="" id="redeem-logs-calendar" value="<?=$range_date?>" placeholder="Pick Date">
                    <br />
                </div>
				<div class="col-md-2">
					<a href="<?=base_url('redeem/download-redeemed-logs?date='.$range_date)?>" id="download-redeemed-logs" target="_blank"><i class="fas fa-download"></i> &nbsp;Download</a>
				</div>

            </div>
            
            <div class="row">
                <div class="container-fluid">
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed" id="tbl-redeem-logs">
                            <thead>
                                <tr>
                                    <th scope="col"><?=SYS_NAME?> Code</th>
                                    <th scope="col">Approval Code</th>
                                    <th scope="col">Originator</th>
                                    <th scope="col">Store IFS</th>
                                    <th scope="col">Store</th>
                                    <th scope="col">Crew Code</th>
                                    <th scope="col">Crew</th>
                                    <th scope="col">Response</th>
                                    <th scope="col">Added Info</th>
                                    <th scope="col">Redemption Type</th>
                                    <th scope="col">Timestamp</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            
                            <tbody>
                                <?=$tbl_logs?>
                            </tbody>
                        </table>
                    </div>
                </br>
                </div>
            </div>
        </div>


