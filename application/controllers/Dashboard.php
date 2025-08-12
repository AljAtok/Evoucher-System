<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {


	public function __construct()
    {
    	parent::__construct();
    	$this->load->model('main_model', 'main');
        $GLOBALS['parent_db'] = parent_db();
	}

	public function index()
    {
		$info = $this->_require_login();
		redirect('dashboard/dashboard-main');
	}

	public function _require_login()
    {
		$login = $this->session->userdata('evoucher-user');
		$allowed_access = array("1", "7", "8", "9", "2", "11", "12");
		if(isset($login)){
			$user_type = decode($login['user_type_id']);
			if($login['user_reset'] == 0){
				if(in_array($user_type, $allowed_access)){
					return $login;
				}else{
					$this->session->unset_userdata('evoucher-user');
					redirect('login');
				}
			}else{
				$this->session->unset_userdata('evoucher-user');
				redirect('login/change-password/' . $login['user_id']);
			}
		}else{
			$this->session->unset_userdata('evoucher-user');
			redirect('login');
		}
	}

    private function alert_template($message, $result)
    {
        $alert_color = ($result) ? 'success' : 'danger';
        return '<div class="alert alert-'.$alert_color.' alert-dismissible fade show" role="alert">' 
            . $message . 
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    }

	public function _get_view_temp(){
		$info		= $this->_require_login();
		$user_type	= decode($info['user_type_id']);
		$redirect = '';

		if($user_type == 1){
			$view = "admin/templates";
		}elseif($user_type == 2 || $user_type == 11){
			$view = "redeem/templates-logs";
		}elseif($user_type == 9){
			$view = "redeem/templates";
		}elseif($user_type == 8){
			$view = "approver/templates";
		}elseif($user_type == 7){
			$view = "creator/templates";
		}elseif($user_type == 12){
			$view = "first-approver/templates";
		}else{
			$view = "admin/templates";
		}
		$data = array(
			'redirect' => $redirect,
			'view' => $view
		);
		$data = (object) $data;
		return $data;
	}

	public function dashboard_main(){
		$info      = $this->_require_login();
		$user_type = decode($info['user_type_id']);
		$user_id        = clean_data(decode($info['user_id']));

		// $start = '2021-01-01';
		// $end = '2025-03-08';
		
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			if($this->input->post('date-range')){
				$daterange  		= explode(' - ', clean_data($this->input->post('date-range')));
				$end_up_to_date  	= $this->input->post('end_up_to_date') ? clean_data($this->input->post('end_up_to_date')) : 0;
				$start 				= date_display($daterange[0], "Y-m-d");
            	$end   				= date_display($daterange[1], "Y-m-d");
				if($end_up_to_date){
					$end 			= date('Y-m-d');
					if(date('Y-m-d') > $end){
					}
				}

				$set      = [ 'year_from' => $start, 'year_to' => $end, 'end_up_to_date' => $end_up_to_date];
				
				$where    = [ 'user_id' => $user_id ];
				$this->main->update_data('dashboard_filters_tbl', $set, $where);
			}
		}
		

		$where = [
			'a.user_id' => $user_id
		];
		$dash_filter_date = $this->main->get_data('dashboard_filters_tbl a', $where, true);
		if(!empty($dash_filter_date)){
			$end = $dash_filter_date->year_to;
			$start = $dash_filter_date->year_from;
			$end_up_to_date = $dash_filter_date->end_up_to_date;
			if($end_up_to_date){
				if(date('Y-m-d') > $end){
					$end 		= date('Y-m-d');
					$set      	= [ 'year_from' => $start, 'year_to' => $end, 'end_up_to_date' => $end_up_to_date];
					
					$where    	= [ 'user_id' => $user_id ];
					$this->main->update_data('dashboard_filters_tbl', $set, $where);
				}
			}
		} else {
			$end = date('Y-m-d');
			$start = date('Y-m-d', strtotime($end . ' - 12 months'));
			$end_up_to_date = 1;

			$set      = [
				'year_from' 		=> $start,
				'year_to' 			=> $end,
				'user_id' 			=> $user_id,
				'end_up_to_date' 	=> $end_up_to_date
			];
            $this->main->insert_data('dashboard_filters_tbl', $set, TRUE);
		}
		$start = date_display($start, "Y-m-d");
		$end = date_display($end, "Y-m-d");

		$data['up_tp_date_checked'] 		= $end_up_to_date ? 'checked' : '';

		$data['range_date'] 				= date('m/d/Y', strtotime($start)) . ' - ' . date('m/d/Y', strtotime($end));

		$filter_display 					= "ALL DATA AS OF ". date_display($start, "M. d, Y") ." - ". date_display($end, "M. d, Y");

		
		// $dashboard_data 					= $this->_get_dashbaord_data_per_coupon_trans($user_id, $user_type, $start, $end);
		// $dashboard_data_non_credit 			= $this->_get_dashbaord_data_per_coupon_trans($user_id, $user_type, $start, $end, FALSE, FALSE)->paid_coupon_trans->result();

		// $data['unpaid_coupon_trans'] 		= $dashboard_data->unpaid_coupon_trans->result();
		// $data['paid_coupon_trans'] 			= $dashboard_data->paid_coupon_trans->result();
		// $data['non_credit_coupon_trans'] 	= $dashboard_data_non_credit;

		 
        
		$data['year_from']       			= $start;
		$data['year_to']       				= $end;
		$data['filter_display']       		= $filter_display;
		$data['title']       				= 'Dashboard';
        $data['top_nav']     				= $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content']     				= $this->load->view('dashboard/dashboard_main_content', $data, TRUE);
		$main_view 							= $this->_get_view_temp()->view;
        $this->load->view($main_view, $data);
	}

	public function _unified_query($end, $type) {
		if($type == 'per_status'){
			$data['group_by'] = "
				CASE
					WHEN f.coupon_status = 4 OR f.coupon_status = 2 THEN 2
					WHEN f.coupon_status = 1 AND f.coupon_use = 0 AND '".$end."' <= f.coupon_end THEN 1
					WHEN f.coupon_status = 1 AND f.coupon_use = 0 AND '".$end."' > f.coupon_end  THEN 5
					WHEN f.coupon_status = 1 AND f.coupon_use = 1 THEN 6
					WHEN f.coupon_status = 0 THEN 0
					ELSE -1
				END
			";
			$data['order_by'] = 'coupon_transaction_header_status_name';
			$data['select'] = "
				CASE
						WHEN f.coupon_status = 4 OR f.coupon_status = 2 THEN 2
						WHEN f.coupon_status = 1 AND f.coupon_use = 0 AND '".$end."' <= f.coupon_end THEN 1
						WHEN f.coupon_status = 1 AND f.coupon_use = 0 AND '".$end."' > f.coupon_end  THEN 5
						WHEN f.coupon_status = 1 AND f.coupon_use = 1 THEN 6
						WHEN f.coupon_status = 0 THEN 0
						ELSE -1
				END AS coupon_transaction_header_status,
				CASE
						WHEN f.coupon_status = 4 OR f.coupon_status = 2 THEN 'PENDING'
						WHEN f.coupon_status = 1 AND f.coupon_use = 0 AND '".$end."' <= f.coupon_end THEN 'ACTIVE'
						WHEN f.coupon_status = 1 AND f.coupon_use = 0 AND '".$end."' > f.coupon_end  THEN 'EXPIRED'
						WHEN f.coupon_status = 1 AND f.coupon_use = 1 THEN 'REDEEMED'
						WHEN f.coupon_status = 0 THEN 'INACTIVE'
						ELSE -1
				END AS coupon_transaction_header_status_name,
				SUM( f.coupon_qty ) AS coupon_qty,
				SUM( f.coupon_value ) AS coupon_value
				";
		}
		
		

		$data = (object) $data;
		return $data;
	}

	public function _unified_where_clause($start, $end, $category_where) {
		$data['where_clause_active'] = '
		f.coupon_status = 1
		AND f.coupon_start >= "'.$start.'"
		AND f.coupon_start <= "'.$end.'"
		AND f.coupon_end >= "'.$end.'"
		AND f.coupon_use = 0'.$category_where;
		
		$data['where_clause_all'] = '
		f.coupon_status != 3
		AND f.coupon_start >= "'.$start.'"
		AND f.coupon_start <= "'.$end.'"'.$category_where;

		$data = (object) $data;
		return $data;
	}

	public function get_dashboard_data(){
		
		
		$info      = $this->_require_login();
		$user_type = decode($info['user_type_id']);
		$user_id        = clean_data(decode($info['user_id']));

		if($this->input->post('date_range')){
			$daterange  = explode(' - ', clean_data($this->input->post('date_range')));
			$start = date_display($daterange[0], "Y-m-d");
			$end   = date_display($daterange[1], "Y-m-d");
		}

		if($_SERVER['REQUEST_METHOD'] != 'POST'){
			echo json_encode([]);
			exit;
		}

		// $data['start'] = date_display($start, "Y-m-01");
		// $data['end'] = date_display($end, "Y-m-t");

		// $start = '2020-01-01';
		// $start = '2024-01-01';
		// $end = '2025-03-08';
		

		$dashboard_data = $this->_get_dashbaord_data_per_coupon_trans($user_id, $user_type, $start, $end, true);

		$dashboard_data_unpaid = $dashboard_data->unpaid_coupon_trans;
		
		$data['result'] = 0;
		$data['total_coupon_qty'] = 0;
		$data['total_coupon_value'] = 0;
		$data['avg_payment_terms'] = 0;
		$data['near_due_date'] = 0;
		$data['coupon_transaction_header_added'] = 0;
		$data['avg_coupon_value'] = 0;
		if(!empty($dashboard_data_unpaid)){
			$data['result'] = 1;
			$data['total_coupon_qty'] = $dashboard_data_unpaid->coupon_qty;
			$data['total_coupon_value'] = $dashboard_data_unpaid->total_coupon_value;
			$data['avg_payment_terms'] = $dashboard_data_unpaid->avg_payment_terms;
			$data['near_due_date'] = date('M. d, Y', strtotime($dashboard_data_unpaid->near_due_date));
			$data['coupon_transaction_header_added'] = date('M. d, Y', strtotime($dashboard_data_unpaid->coupon_transaction_header_added));
			$data['avg_coupon_value'] = $dashboard_data_unpaid->avg_coupon_value;
		}
		
		$dashboard_data_paid = $dashboard_data->paid_coupon_trans;
		
		$data['paid_result'] = 0;
		$data['paid_total_coupon_qty'] = 0;
		$data['paid_total_coupon_value'] = 0;
		$data['paid_avg_payment_terms'] = 0;
		$data['paid_coupon_transaction_header_added'] = 0;
		$data['paid_avg_coupon_value'] = 0;

		if(!empty($dashboard_data_paid)){
			$data['paid_result'] = 1;
			$data['paid_total_coupon_qty'] = $dashboard_data_paid->coupon_qty;
			$data['paid_total_coupon_value'] = $dashboard_data_paid->total_coupon_value;
			$data['paid_avg_payment_terms'] = $dashboard_data_paid->avg_payment_terms;
			$data['paid_avg_coupon_value'] = $dashboard_data_paid->avg_coupon_value;
			$data['paid_coupon_transaction_header_added'] = date('M. d, Y', strtotime($dashboard_data_paid->coupon_transaction_header_added));
		}

		$dashboard_data_non_credit = $this->_get_dashbaord_data_per_coupon_trans($user_id, $user_type, $start, $end, TRUE, FALSE)->paid_coupon_trans;
		$data['non_credit_result'] = 0;
		$data['non_credit_total_coupon_qty'] = 0;
		$data['non_credit_total_coupon_value'] = 0;
		$data['non_credit_avg_coupon_value'] = 0;
		if(!empty($dashboard_data_non_credit)){
			$data['non_credit_result'] = 1;
			$data['non_credit_total_coupon_qty'] = $dashboard_data_non_credit->coupon_qty;
			$data['non_credit_total_coupon_value'] = $dashboard_data_non_credit->total_coupon_value;
			$data['non_credit_avg_coupon_value'] = $dashboard_data_non_credit->avg_coupon_value;
		}


		$coupon_cat_id_where = 'coupon_cat_id IN (3, 5)';
		if($user_type == 1){
			$category_where = ' AND f.'.$coupon_cat_id_where;
		} else {
			$category_where = " AND f.coupon_cat_id IN (SELECT z.coupon_cat_id FROM user_access_tbl z WHERE z.user_id = {$user_id} AND z.".$coupon_cat_id_where." AND user_access_status = 1)";
		}

		$where_clause = $this->_unified_where_clause($start, $end, $category_where);
		$where_clause_all = $where_clause->where_clause_all;
		$where_clause_active = $where_clause->where_clause_active;

		$select = 'd.payment_type, SUM(f.coupon_qty) as coupon_qty, SUM(f.coupon_value) as coupon_value';
		$group = 'a.payment_type_id';
		$order_by = 'a.payment_type_id';
		$dashboard_data_coupon_active = $this->_get_dashboard_data_all($select, $group, $order_by, $where_clause_active, $where_clause_all, true)->active_coupon_trans->result();
		
		// $select = 'IF(a.coupon_transaction_header_status=4,2,a.coupon_transaction_header_status) as coupon_transaction_header_status, SUM(f.coupon_qty) as coupon_qty';
		
		$qry = $this->_unified_query($end, "per_status");
		$order_by = $qry->order_by;
		$group = $qry->group_by;
		$select = $qry->select;
		$dashboard_data_coupon_all = $this->_get_dashboard_data_all($select, $group, $order_by, $where_clause_active, $where_clause_all)->all_coupon_trans->result();
		
		$payment_type_labels = array();
		$payment_type_coupon_qty = array();
		$payment_type_coupon_value = array();
		if(!empty($dashboard_data_coupon_active)){
			foreach($dashboard_data_coupon_active as $r){
				$payment_type_labels[] = $r->payment_type;
				$payment_type_coupon_qty[] = $r->coupon_qty * 1;
				$payment_type_coupon_value[] = $r->coupon_value * 1;
			}
		}
		$data['payment_type_labels'] = $payment_type_labels;
		$data['payment_type_coupon_qty'] = $payment_type_coupon_qty;
		$data['payment_type_coupon_value'] = $payment_type_coupon_value;

		$coupon_status_labels = array();
		$coupon_status_qty = array();
		$coupon_status_value = array();
		$coupon_status_background_color = array();
		$coupon_status_border_color = array();
		if(!empty($dashboard_data_coupon_all)){
			foreach($dashboard_data_coupon_all as $r){
				
				$coupon_status_qty[] = $r->coupon_qty * 1;
				$coupon_status_value[] = $r->coupon_value * 1;
				$coupon_status_labels[] = $r->coupon_transaction_header_status_name;
				if($r->coupon_transaction_header_status_name == 'ACTIVE'){
					$coupon_status_background_color[] = "rgba(67, 150, 50, 0.8)";
					$coupon_status_border_color[] = "rgba(42, 95, 32, 0.8)";
				}
				elseif($r->coupon_transaction_header_status_name == 'PENDING'){
					$coupon_status_background_color[] = "rgba(246, 238, 90, 0.91)";
					$coupon_status_border_color[] = "rgba(155, 151, 77, 0.66)";
				}
				elseif($r->coupon_transaction_header_status_name == 'EXPIRED'){
					$coupon_status_background_color[] = "rgba(254, 38, 0, 0.89)";
					$coupon_status_border_color[] = "rgba(154, 49, 31, 0.8)";
				}
				elseif($r->coupon_transaction_header_status_name == 'REDEEMED'){
					$coupon_status_background_color[] = "rgba(153, 102, 255, 0.8)";
					$coupon_status_border_color[] = "rgb(112, 73, 188)";
				}
				elseif($r->coupon_transaction_header_status_name == 'INACTIVE'){
					$coupon_status_background_color[] = "rgba(255, 194, 81, 0.9)";
					$coupon_status_border_color[] = "rgba(131, 117, 89, 0.9)";
				}
				else{
                    $coupon_status_background_color[] = "rgba(186, 178, 178, 0.8)";
                    $coupon_status_border_color[] = "rgba(24, 22, 22, 0.8)";
                }

				
			}
		}
		$data['coupon_status_labels'] = $coupon_status_labels;
		$data['coupon_status_qty'] = $coupon_status_qty;
		$data['coupon_status_value'] = $coupon_status_value;
		$data['coupon_status_background_color'] = $coupon_status_background_color;
		$data['coupon_status_border_color'] = $coupon_status_border_color;



		$coupon_cat_id_where = ' AND coupon_cat_id IN (3, 5)';
		if($user_type== 1){
			$category_where = '';
		} else {
			$category_where = " AND coupon_cat_id IN (SELECT z.coupon_cat_id FROM user_access_tbl z WHERE z.user_id = {$user_id} ".$coupon_cat_id_where." AND user_access_status = 1)";
		}
		$trend_where = [
			'a.base_month >=' => date_display($start, "Y-m-01"),
			'a.base_month <=' => date_display($end, "Y-m-t"),
		];
		$select = "
			a.base_month,
			(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE MONTH(a.base_month) = MONTH(x.coupon_start) and YEAR(a.base_month) = YEAR(x.coupon_start) AND x.coupon_status = 1 AND x.coupon_use = 0 AND '".$end."' <= x.coupon_end ".$category_where." ".$coupon_cat_id_where.") as active_coupon_qty,

			(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE MONTH(a.base_month) = MONTH(x.coupon_start) and YEAR(a.base_month) = YEAR(x.coupon_start) AND x.coupon_status = 1 AND x.coupon_use = 0 AND '".$end."' > x.coupon_end ".$category_where." ".$coupon_cat_id_where.") as expired_coupon_qty,

			(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE MONTH(a.base_month) = MONTH(x.coupon_start) and YEAR(a.base_month) = YEAR(x.coupon_start) AND x.coupon_status = 1 AND x.coupon_use = 1 ".$category_where." ".$coupon_cat_id_where.") as redeemed_coupon_qty,
			
			(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE MONTH(a.base_month) = MONTH(x.coupon_start) and YEAR(a.base_month) = YEAR(x.coupon_start) AND x.coupon_status = 0  ".$category_where." ".$coupon_cat_id_where.") as inactive_coupon_qty
		";
		$monthly_trend = $this->main->get_data('base_months_tbl a', $trend_where, FALSE, $select);

		$monthly_active_coupon_qty = array();
		$monthly_expired_coupon_qty = array();
		$monthly_redeemed_coupon_qty = array();
		$monthly_inactive_coupon_qty = array();
		$monthly_name = array();
		if(!empty($monthly_trend)){
			foreach($monthly_trend as $r){
				
				$monthly_active_coupon_qty[] = $r->active_coupon_qty * 1;
				$monthly_expired_coupon_qty[] = $r->expired_coupon_qty * 1;
				$monthly_redeemed_coupon_qty[] = $r->redeemed_coupon_qty * 1;
				$monthly_inactive_coupon_qty[] = $r->inactive_coupon_qty * 1;
				$monthly_name[] = date_display($r->base_month, "M Y");
				
			}
		}
		$data['monthly_active_coupon_qty'] = $monthly_active_coupon_qty;
		$data['monthly_expired_coupon_qty'] = $monthly_expired_coupon_qty;
		$data['monthly_redeemed_coupon_qty'] = $monthly_redeemed_coupon_qty;
		$data['monthly_inactive_coupon_qty'] = $monthly_inactive_coupon_qty;
		$data['monthly_name'] = $monthly_name;

		// pretty_dump($data);
		// exit;

		echo json_encode($data);
		
	}

	public function dashboard_grid($date_range, $is_cleared){
		$info      = $this->_require_login();

		$draw = intval($this->input->get("draw"));
		$start = intval($this->input->get("start"));
		$length = intval($this->input->get("length"));
		$data = array();

		$daterange  = explode(' - ', clean_data($date_range));
		$daterange = str_replace("-", "/", $daterange);
		$start_date = date_display($daterange[0], "Y-m-d");
		$end_date   = date_display($daterange[1], "Y-m-d");

		

		$user_type				= decode($info['user_type_id']);
		$user_id        		= clean_data(decode($info['user_id']));
		$dashboard_data 		= $this->_get_dashbaord_data_per_coupon_trans($user_id, $user_type, $start_date, $end_date);
		
		if($is_cleared==1){ //* CREDIT CLEARED TRANS
			$dashboard_data = $dashboard_data->paid_coupon_trans;
		}
		elseif($is_cleared==0) { //* CREDIT RECEIVABLES TRANS
			$dashboard_data = $dashboard_data->unpaid_coupon_trans;
		}
		elseif($is_cleared==2) { //* NON CREDIT TRANS
			$dashboard_data = $this->_get_dashbaord_data_per_coupon_trans($user_id, $user_type, $start_date, $end_date, FALSE, FALSE)->paid_coupon_trans;
		}


		foreach($dashboard_data->result() as $row){
			if($row->payment_status == 1){
				$payment_badge = '<span class="badge badge-success">Paid</span>';
			}elseif($row->payment_status == 0){
				$payment_badge = '<span class="badge badge-warning">Unpaid</span>';
			}

			if($is_cleared == 1 || $is_cleared == 0){
				$data[] = array(	
					$row->coupon_transaction_header_name,
					$row->customer_name,
					$row->coupon_cat_name,
					decimal_format($row->coupon_qty, 0),
					decimal_format($row->coupon_value),
					decimal_format($row->total_coupon_value),
					$row->payment_terms.' DAYS',
					add_date($row->coupon_transaction_header_added, 0, 0, $row->payment_terms),
					$row->user_lname . ', ' . $row->user_fname,
					date_format(date_create($row->coupon_transaction_header_added),"M d, Y h:i A"),
					$payment_badge
				);
			}else{
				$data[] = array(	
					$row->coupon_transaction_header_name,
					$row->customer_name,
					$row->coupon_cat_name,
					decimal_format($row->coupon_qty, 0),
					decimal_format($row->coupon_value),
					decimal_format($row->total_coupon_value),
					$row->payment_type,
					add_date($row->coupon_transaction_header_added, 0, 0, $row->payment_terms),
					$row->user_lname . ', ' . $row->user_fname,
					date_format(date_create($row->coupon_transaction_header_added),"M d, Y h:i A"),
					// $payment_badge
				);
			}
		}

		$output = array(
			"draw" => $draw,
			"recordsTotal" => $dashboard_data->num_rows(),
			"recordsFiltered" => $dashboard_data->num_rows(),
			"data" => $data
	   );
	   echo json_encode($output);
	   exit();
	}


	public function dashboard_grid_monthly_data($date_range, $is_active=0){
		$info      = $this->_require_login();
		$user_type				= decode($info['user_type_id']);
		$user_id        		= clean_data(decode($info['user_id']));

		$draw = intval($this->input->get("draw"));
		$start = intval($this->input->get("start"));
		$length = intval($this->input->get("length"));
		$data = array();

		$daterange  = explode(' - ', clean_data($date_range));
		$daterange = str_replace("-", "/", $daterange);
		$start_date = date_display($daterange[0], "Y-m-d");
		$end_date   = date_display($daterange[1], "Y-m-d");

		$coupon_cat_id_where = ' AND coupon_cat_id IN (3, 5)';
		if($user_type== 1){
			$category_where = '';
		} else {
			$category_where = " AND coupon_cat_id IN (SELECT z.coupon_cat_id FROM user_access_tbl z WHERE z.user_id = {$user_id} ".$coupon_cat_id_where." AND user_access_status = 1)";
		}
		// $trend_where = [
		// 	'a.base_month >=' => date_display($start_date, "Y-m-01"),
		// 	'a.base_month <=' => date_display($end_date, "Y-m-t"),
		// 	'b.coupon_cat <=' => date_display($end_date, "Y-m-t"),
		// ];
		$trend_where = "a.base_month >= '".date_display($start_date, "Y-m-01")."'
		AND a.base_month <= '".date_display($end_date, "Y-m-t")."'
		".$coupon_cat_id_where;

		if($is_active==1){
			$trend_where = $trend_where . " AND (SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE MONTH(a.base_month) = MONTH(x.coupon_start) and YEAR(a.base_month) = YEAR(x.coupon_start) AND x.coupon_status = 1 AND x.coupon_use = 0 AND '".$end_date."' <= x.coupon_end ".$category_where." ".$coupon_cat_id_where." AND b.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') > 0";
		}

		$select = "
			a.base_month,
			b.coupon_cat_id,
			b.coupon_cat_name,

			(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE MONTH(a.base_month) = MONTH(x.coupon_start) and YEAR(a.base_month) = YEAR(x.coupon_start) AND x.coupon_status != 3 ".$category_where." ".$coupon_cat_id_where." AND b.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as total_coupon_qty,

			(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE MONTH(a.base_month) = MONTH(x.coupon_start) and YEAR(a.base_month) = YEAR(x.coupon_start) AND x.coupon_status = 1 AND x.coupon_use = 0 AND '".$end_date."' <= x.coupon_end ".$category_where." ".$coupon_cat_id_where." AND b.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as active_coupon_qty,

			(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE MONTH(a.base_month) = MONTH(x.coupon_start) and YEAR(a.base_month) = YEAR(x.coupon_start) AND x.coupon_status = 1 AND x.coupon_use = 0 AND '".$end_date."' > x.coupon_end ".$category_where." ".$coupon_cat_id_where." AND b.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as expired_coupon_qty,

			(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE MONTH(a.base_month) = MONTH(x.coupon_start) and YEAR(a.base_month) = YEAR(x.coupon_start) AND x.coupon_status = 1 AND x.coupon_use = 1 ".$category_where." ".$coupon_cat_id_where." AND b.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as redeemed_coupon_qty,

			(SELECT IF(AVG(coupon_value), AVG(coupon_value), 0) from coupon_tbl x WHERE MONTH(a.base_month) = MONTH(x.coupon_start) and YEAR(a.base_month) = YEAR(x.coupon_start) AND x.coupon_status != 3 ".$category_where." ".$coupon_cat_id_where." AND b.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as average_coupon_value,

			(SELECT IF(coupon_value, SUM(coupon_value), 0) from coupon_tbl x WHERE MONTH(a.base_month) = MONTH(x.coupon_start) and YEAR(a.base_month) = YEAR(x.coupon_start) AND x.coupon_status != 3 ".$category_where." ".$coupon_cat_id_where." AND b.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as total_coupon_value,

			(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE MONTH(a.base_month) = MONTH(x.coupon_start) and YEAR(a.base_month) = YEAR(x.coupon_start) AND x.coupon_status = 0 ".$category_where." ".$coupon_cat_id_where." AND b.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as inactive_coupon_qty,
		";
		$monthly_trans_data = $this->main->get_join_datatables('base_months_tbl a, coupon_category_tbl b', false, false, 'base_month DESC, coupon_cat_id', $group_by=false, $select, $trend_where);

		
		if(!empty($monthly_trans_data->result())){
			foreach($monthly_trans_data->result() as $row){
				
				$data[] = array(	
					date_display($row->base_month, "F Y"),
					$row->coupon_cat_name,
					decimal_format($row->total_coupon_qty, 0),
					decimal_format($row->active_coupon_qty, 0),
					decimal_format($row->redeemed_coupon_qty, 0),
					decimal_format($row->expired_coupon_qty, 0),
					decimal_format($row->inactive_coupon_qty, 0),
					decimal_format($row->average_coupon_value),
					decimal_format($row->total_coupon_value)
					
				);
				
			}
		}

		$output = array(
			"draw" => $draw,
			"recordsTotal" => $monthly_trans_data->num_rows(),
			"recordsFiltered" => $monthly_trans_data->num_rows(),
			"data" => $data
		);
		echo json_encode($output);
		exit();
	}

	public function dashboard_grid_per_trans($date_range, $per_trans, $is_active=0){
		$info      = $this->_require_login();

		$draw = intval($this->input->get("draw"));
		$start = intval($this->input->get("start"));
		$length = intval($this->input->get("length"));
		$data = array();

		$daterange  = explode(' - ', clean_data($date_range));
		$daterange = str_replace("-", "/", $daterange);
		$start_date = date_display($daterange[0], "Y-m-d");
		$end_date   = date_display($daterange[1], "Y-m-d");

		

		$user_type				= decode($info['user_type_id']);
		$user_id        		= clean_data(decode($info['user_id']));

		$coupon_cat_id_where = 'coupon_cat_id IN (3, 5)';
		if($user_type == 1){
			$category_where = ' AND f.'.$coupon_cat_id_where;
		} else {
			$category_where = " AND f.coupon_cat_id IN (SELECT z.coupon_cat_id FROM user_access_tbl z WHERE z.user_id = {$user_id} AND z.".$coupon_cat_id_where." AND user_access_status = 1)";
		}

		$where_clause = $this->_unified_where_clause($start_date, $end_date, $category_where);
		$where_clause_all = $where_clause->where_clause_all;
		$where_clause_active = $where_clause->where_clause_active;

		

		if($is_active==1){
			$where_clause_all = $where_clause_all . ' AND (SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x INNER JOIN coupon_transaction_details_tbl y ON x.coupon_id = y.coupon_id WHERE x.coupon_status = 1 AND x.coupon_use = 0 AND "'.$end_date.'" <= x.coupon_end AND y.coupon_transaction_header_id = a.coupon_transaction_header_id AND a.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= "'.$start_date.'" AND x.coupon_start <= "'.$end_date.'") > 0';
		}

		
		
		if($per_trans==1){ //* PER TRANS
			$select = "
	
				(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x INNER JOIN coupon_transaction_details_tbl y ON x.coupon_id = y.coupon_id WHERE x.coupon_status = 1 AND x.coupon_use = 0 AND '".$end_date."' <= x.coupon_end AND y.coupon_transaction_header_id = a.coupon_transaction_header_id AND a.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as active_coupon_qty,
				
				(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x INNER JOIN coupon_transaction_details_tbl y ON x.coupon_id = y.coupon_id WHERE x.coupon_status = 1 AND x.coupon_use = 0 AND '".$end_date."' > x.coupon_end AND y.coupon_transaction_header_id = a.coupon_transaction_header_id AND a.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as expired_coupon_qty,
	
				(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x INNER JOIN coupon_transaction_details_tbl y ON x.coupon_id = y.coupon_id WHERE x.coupon_status = 1 AND x.coupon_use = 1 AND y.coupon_transaction_header_id = a.coupon_transaction_header_id AND a.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as redeemed_coupon_qty,

				(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x INNER JOIN coupon_transaction_details_tbl y ON x.coupon_id = y.coupon_id WHERE x.coupon_status = 0 AND y.coupon_transaction_header_id = a.coupon_transaction_header_id AND a.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as inactive_coupon_qty,
	
				f.coupon_value,
				SUM( f.coupon_qty ) AS total_coupon_qty,
				SUM( f.coupon_value ) AS total_coupon_value,
				a.payment_status,
				a.coupon_transaction_header_name,
				a.coupon_transaction_header_id,
				g.customer_name,
				c.coupon_cat_name,
				d.payment_type,
				d.payment_type_id,
				a.payment_terms,
				a.coupon_transaction_header_added,
				b.user_lname,
				b.user_fname
				";
			$group = "a.coupon_transaction_header_id";
			$order_by = 'a.coupon_transaction_header_id DESC';
			$dashboard_data = $this->_get_dashboard_data_all($select, $group, $order_by, $where_clause_active, $where_clause_all)->all_coupon_trans;
		}
		elseif($per_trans==0) { //* PER CUSTOMER
			$select = "
	
				(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE x.coupon_status = 1 AND x.coupon_use = 0 AND '".$end_date."' <= x.coupon_end AND f.customer_id = x.customer_id AND a.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as active_coupon_qty,
				
				(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE x.coupon_status = 1 AND x.coupon_use = 0 AND '".$end_date."' > x.coupon_end AND f.customer_id = x.customer_id AND a.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as expired_coupon_qty,
	
				(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE x.coupon_status = 1 AND x.coupon_use = 1 AND f.customer_id = x.customer_id AND a.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as redeemed_coupon_qty,

				(SELECT IF(SUM(coupon_qty), SUM(coupon_qty), 0) from coupon_tbl x WHERE x.coupon_status = 0 AND f.customer_id = x.customer_id AND a.coupon_cat_id = x.coupon_cat_id AND x.coupon_start >= '".$start_date."' AND x.coupon_start <= '".$end_date."') as inactive_coupon_qty,
	
				f.coupon_value,
				SUM( f.coupon_qty ) AS total_coupon_qty,
				SUM( f.coupon_value ) AS total_coupon_value,
				a.payment_status,
				a.coupon_transaction_header_name,
				a.coupon_transaction_header_id,
				g.customer_name,
				c.coupon_cat_name,
				d.payment_type,
				d.payment_type_id,
				a.payment_terms,
				a.coupon_transaction_header_added,
				b.user_lname,
				b.user_fname
				";
			$group = "a.customer_id, c.coupon_cat_id";
			$order_by = 'g.customer_name, c.coupon_cat_id';
			$dashboard_data = $this->_get_dashboard_data_all($select, $group, $order_by, $where_clause_active, $where_clause_all)->all_coupon_trans;
		}


		foreach($dashboard_data->result() as $row){
			if($row->payment_status == 1){
				$payment_badge = '<span class="badge badge-success">Paid</span>';
			}elseif($row->payment_status == 0){
				$payment_badge = '<span class="badge badge-warning">Unpaid</span>';
			}

			if($per_trans == 1){
				$data[] = array(	
					$row->coupon_transaction_header_name,
					$row->customer_name,
					$row->coupon_cat_name,

					decimal_format($row->total_coupon_qty, 0),
					decimal_format($row->active_coupon_qty, 0),
					decimal_format($row->redeemed_coupon_qty, 0),
					decimal_format($row->expired_coupon_qty, 0),
					decimal_format($row->inactive_coupon_qty, 0),
					decimal_format($row->coupon_value),
					decimal_format($row->total_coupon_value),

					$row->payment_type_id == 4 ? $row->payment_type.' ('.$row->payment_terms.' DAYS)' : $row->payment_type,
					date_format(date_create($row->coupon_transaction_header_added),"M d, Y h:i A").' | '.$row->user_lname . ', ' . $row->user_fname,
					$payment_badge
				);
			}else{
				$data[] = array(
					$row->customer_name,
					$row->coupon_cat_name,
					decimal_format($row->total_coupon_qty, 0),
					decimal_format($row->active_coupon_qty, 0),
					decimal_format($row->redeemed_coupon_qty, 0),
					decimal_format($row->expired_coupon_qty, 0),
					decimal_format($row->inactive_coupon_qty, 0),
					decimal_format($row->coupon_value),
					decimal_format($row->total_coupon_value),
				);
			}
		}

		$output = array(
			"draw" => $draw,
			"recordsTotal" => $dashboard_data->num_rows(),
			"recordsFiltered" => $dashboard_data->num_rows(),
			"data" => $data
	   );
	   echo json_encode($output);
	   exit();
	}


	public function _get_dashbaord_data_per_coupon_trans($user_id = 0, $user_type = 0, $start = 0, $end = 0, $row = FALSE, $credit_only = TRUE){
		
		$parent_db = $GLOBALS['parent_db'];

        $join_coupon = array(
        	"{$parent_db}.user_tbl b" => 'a.user_id = b.user_id',
        	'coupon_category_tbl c'   => 'a.coupon_cat_id = c.coupon_cat_id',
        	'payment_types_tbl d'   => 'a.payment_type_id = d.payment_type_id',
        	'coupon_transaction_details_tbl e'   => 'a.coupon_transaction_header_id = e.coupon_transaction_header_id',
        	'coupon_tbl f'   => 'e.coupon_id = f.coupon_id',
        	'customers_tbl g'   => 'a.customer_id = g.customer_id',
        );


		$coupon_cat_id_where = 'coupon_cat_id IN (3, 5)';
		$category_where = "a.coupon_cat_id IN (SELECT z.coupon_cat_id FROM user_access_tbl z WHERE z.user_id = {$user_id} AND z.".$coupon_cat_id_where." AND user_access_status = 1)";

		
		$group_by = 'a.coupon_transaction_header_id';
		$coupon_trans_select = '*, SUM(f.coupon_qty) as `coupon_qty`, SUM(f.coupon_use) as coupon_use, SUM(f.coupon_qty) - SUM(f.coupon_use) as active_coupon_qty, SUM(f.coupon_value) as total_coupon_value';
		if($row){
			$group_by = 'a.payment_status';
			$coupon_trans_select = '*,
			MIN(DATE_ADD(coupon_transaction_header_added, INTERVAL a.payment_terms DAY)) as near_due_date,
			AVG(payment_terms) as avg_payment_terms,
			AVG(f.coupon_value) as avg_coupon_value,
			SUM(f.coupon_value) as total_coupon_value,
			SUM(f.coupon_qty) as `coupon_qty`';
		}

		$payment_type_cond = '!=';
		if($credit_only === TRUE){
			$payment_type_cond = '=';
		}

		if($user_type == 1){

			$where_clause_unpaid = '
			f.coupon_status = 1
			AND f.coupon_use = 0
			AND "'.$end.'" <= f.coupon_end
			AND a.payment_status = 0
			and a.payment_type_id '.$payment_type_cond.' 4
			AND f.coupon_start >= "'.$start.'"
			AND f.coupon_start <= "'.$end.'"
			AND a.'.$coupon_cat_id_where;

			$where_clause_paid = '
			f.coupon_status = 1
			AND f.coupon_use = 0
			AND "'.$end.'" <= f.coupon_end
			AND a.payment_status = 1
			and a.payment_type_id '.$payment_type_cond.' 4
			AND f.coupon_start >= "'.$start.'"
			AND f.coupon_start <= "'.$end.'"
			AND a.'.$coupon_cat_id_where;;
		} else {

			
			$where_clause_unpaid = '
			f.coupon_status = 1
			AND f.coupon_use = 0
			AND "'.$end.'" <= f.coupon_end
			AND a.payment_status = 0
			and a.payment_type_id '.$payment_type_cond.' 4
			AND f.coupon_start >= "'.$start.'"
			AND f.coupon_start <= "'.$end.'"
			AND ' . $category_where;

			$where_clause_paid = '
			f.coupon_status = 1
			AND f.coupon_use = 0
			AND "'.$end.'" <= f.coupon_end
			AND a.payment_status = 1
			and a.payment_type_id '.$payment_type_cond.' 4
			AND f.coupon_start >= "'.$start.'"
			AND f.coupon_start <= "'.$end.'"
			AND ' . $category_where;
		}
		

		if(!$credit_only){
			$data['paid_coupon_trans']  = $this->main->get_join_datatables('coupon_transaction_header_tbl a', $join_coupon, $row, 'coupon_transaction_header_added DESC', $group_by, $coupon_trans_select, $where_clause_paid);
		} else {
			$data['unpaid_coupon_trans']  = $this->main->get_join_datatables('coupon_transaction_header_tbl a', $join_coupon, $row, 'coupon_transaction_header_added DESC', $group_by, $coupon_trans_select, $where_clause_unpaid);
			
			$data['paid_coupon_trans']  = $this->main->get_join_datatables('coupon_transaction_header_tbl a', $join_coupon, $row, 'coupon_transaction_header_added DESC', $group_by, $coupon_trans_select, $where_clause_paid);
		}

		$data = (object) $data;
		
		return $data;
	}

	public function _get_dashboard_data_all($select = false, $group_by = FALSE, $order_by = FALSE, $where_clause_active, $where_clause_all, $active_only = false){
		$row = FALSE;
		$parent_db = $GLOBALS['parent_db'];

        $join_coupon = array(
        	"{$parent_db}.user_tbl b" => 'a.user_id = b.user_id',
        	'coupon_category_tbl c'   => 'a.coupon_cat_id = c.coupon_cat_id',
        	'payment_types_tbl d'   => 'a.payment_type_id = d.payment_type_id',
			'coupon_transaction_details_tbl e' => 'a.coupon_transaction_header_id = e.coupon_transaction_header_id',
			'coupon_tbl f' => 'e.coupon_id = f.coupon_id',
        	'customers_tbl g'   => 'a.customer_id = g.customer_id',
        );

		$coupon_trans_select = $select;
		
		if($active_only){
			$data['active_coupon_trans']  = $this->main->get_join_datatables('coupon_transaction_header_tbl a', $join_coupon, $row, $order_by, $group_by, $coupon_trans_select, $where_clause_active);
		} else {
			$data['all_coupon_trans']  = $this->main->get_join_datatables('coupon_transaction_header_tbl a', $join_coupon, $row, $order_by, $group_by, $coupon_trans_select, $where_clause_all);
		}
		

		$data = (object) $data;
		
		return $data;
	}

	public function profile(){
		$parent_db = $GLOBALS['parent_db'];
		$info  = $this->_require_login();
        $join = [
            "{$parent_db}.user_type_tbl b" => 'a.user_type_id = b.user_type_id AND a.user_id = ' . decode($info['user_id'])
        ];

        $select = '*';

        $data['title']   = 'Profile';
        $data['user']    = $this->main->get_join( "{$parent_db}.user_tbl a", $join, TRUE, FALSE, FALSE, $select);
		$data['top_nav']     = $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content'] = $this->load->view('dashboard/profile_content', $data, TRUE);
		$main_view = $this->_get_view_temp()->view;
        $this->load->view($main_view, $data);
	}

	public function profile_change_password()
    {
		$parent_db = $GLOBALS['parent_db'];
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            redirect($_SERVER['HTTP_REFERER']);
        }

		$info  = $this->_require_login();
		$user_id = decode($info['user_id']);

		$this->load->library('form_validation');
        $this->form_validation
            ->set_rules($this->_profile_change_password_rules())
            ->set_error_delimiters(
                '<div class="alert alert-danger alert-dismissible fade show" role="alert">', 
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>');
        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('message', validation_errors());
            redirect($_SERVER['HTTP_REFERER'].'#security');
        }


        $password = clean_data(encode($this->input->post('password')));
        $where    = [ 'user_id' => $user_id ];
        $data     = [ 'user_password' => $password ];
        $result   = $this->main->update_data("{$parent_db}.user_tbl", $data, $where);
        if ($result) {
            $message       =  'Password Update Success';
            $alert_message = $this->alert_template($message, $result);
        } else {
            $message       = 'Password Update Failed';
            $alert_message = $this->alert_template($message, $result);
        }
        $this->session->set_flashdata('message', $alert_message);
        redirect($_SERVER['HTTP_REFERER'].'#security');
    }

	private function _profile_change_password_rules()
    {
        return [
            [
                'field' => 'old_password',
                'label' => 'Old Password',
                'rules' => 'required|min_length[6]|max_length[16]|callback_check_old_password'
            ],
            [
                'field' => 'password',
                'label' => 'Password',
                'rules' => 'required|min_length[6]|max_length[16]|callback_check_new_password'
            ],
            [
                'field' => 'repeat_password',
                'label' => 'Repeat Password',
                'rules' => 'required|min_length[6]|max_length[16]|matches[password]'
            ],
        ];
    }

	function check_old_password($field_value)
    {
		$parent_db = $GLOBALS['parent_db'];
        $old_password  = clean_data($this->input->post('old_password'));
        $info  = $this->_require_login();
        $user_id = decode($info['user_id']);

        $this->form_validation->set_message('check_old_password', '%s is incorrect');
        $user = $this->main->get_data("{$parent_db}.user_tbl", ['user_id' => $user_id], TRUE);
		
		if($old_password == decode($user->user_password)) {
			return true;
		}
		return false;
    }

	function check_new_password($field_value)
    {
		$parent_db = $GLOBALS['parent_db'];
        $password      = clean_data($this->input->post('password'));
		$info  = $this->_require_login();
        $user_id = decode($info['user_id']);

        $this->form_validation->set_message('check_new_password', 'New password must not be the same as the old password.');
        $where = ['user_id' => $user_id];
        $user  = $this->main->get_data("{$parent_db}.user_tbl", $where, TRUE);
		if($password != decode($user->user_password)) {
			return true;
		}
		return false;
    }

}
