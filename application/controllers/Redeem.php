<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Redeem extends CI_Controller {

	public function __construct()
    {
    	parent::__construct();
    	$this->load->model('main_model', 'main');
        $GLOBALS['parent_db'] = parent_db();
	}

	public function index()
    {
		$info = $this->_require_login();
		$redirect = $this->_get_view_temp()->redirect;
		redirect($redirect);
	}

	public function _require_login()
    {
		$login = $this->session->userdata('evoucher-user');
		if(isset($login)){
			$user_type = decode($login['user_type_id']);
			if($login['user_reset'] == 0){
				if($user_type == "9"){ // REDEEMER
					return $login;
                }elseif($user_type == "11"){ // REDEEM VIEWING
					return $login;
				}elseif($user_type == "2"){ // BC RBA REDEEM VIEWING
					return $login;
				}elseif($user_type == "1"){
					redirect('admin');
				}elseif($user_type == "7"){
					redirect('creator');
				}elseif($user_type == "8"){
					redirect('approver');
				}elseif($user_type == "12"){
					redirect('first-approver');
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

	public function _get_view_temp(){
		$info		= $this->_require_login();
		$user_type	= decode($info['user_type_id']);

		$redirect = 'redeem/redeem_coupon';
		$view = 'redeem/templates';
		if($user_type == "11"){
			$redirect = 'redeem/redeem_logs';
			$view = "redeem/templates-logs";
		}elseif($user_type == "2"){
			$redirect = 'redeem/redeem_logs';
			$view = "redeem/templates-logs";
		}
		$data = array(
			'redirect' => $redirect,
			'view' => $view
		);
		$data = (object) $data;
		return $data;
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

    public function redeem_coupon()
    {
		$info      = $this->_require_login();
		$data['user_id']   = $info['user_id'];
        $data['title']   = 'Redeem '.SEC_SYS_NAME.'';
		$data['top_nav']     = $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content'] = $this->load->view('redeem/coupon/redeem_coupon_content', $data, TRUE);
		$main_view = $this->_get_view_temp()->view;
		
        $this->load->view($main_view, $data);
    }
    
	public function redeem_coupon_emp()
    {
		$info      = $this->_require_login();
		$data['user_id']   = $info['user_id'];
        $data['title']   = 'Redeem '.SEC_SYS_NAME.'';
		$data['top_nav']     = $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content'] = $this->load->view('redeem/coupon/redeem_coupon_emp_content', $data, TRUE);
		$main_view = $this->_get_view_temp()->view;
		
        $this->load->view($main_view, $data);
    }

	public function pos_redeem_coupon()
    {
		
		$data['user_id']   = NULL;
        $data['title']   = 'Redeem '.SEC_SYS_NAME.'';

		$data['parent_faqs']  = $this->main->get_data('faqs_tbl a', ['faq_status' => 1, 'parent_id' => 0]);

		$join = array(
    		'faqs_tbl c, LEFT' => 'c.parent_id = p.faq_id and c.faq_id IS NOT NULL',
    	);
		$select = ' 
			p.faq_id AS parent_id,
			p.faq_desc AS parent_name,
			c.faq_id AS child_id,
			c.faq_desc AS child_name,
			c.class_name';
    	$data['child_faqs'] = $this->main->get_join('faqs_tbl p', $join, false, $order='p.faq_id, c.order', $group=FALSE, $select);
		
        $data['content'] = $this->load->view('pos/coupon/redeem_coupon_content', $data, TRUE);
        $this->load->view('pos/templates', $data);
    }

    public function redeem_coupon_v1()
    {
		$info      = $this->_require_login();
        $data['title']   = 'Redeem '.SEC_SYS_NAME.'';
		$data['top_nav']     = $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content'] = $this->load->view('redeem/coupon/redeem_coupon_content_v1', $data, TRUE);
        $main_view = $this->_get_view_temp()->view;
		
        $this->load->view($main_view, $data);
    }

    public function web_validate_coupon()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
           show_404();
        }

		$parent_db   = $GLOBALS['parent_db'];

		$coupon_code = clean_data( strtoupper($this->input->post('code')) );
		$coupon_code = trim($coupon_code);

		$check_spam_ip = $this->_check_spam_ip('validate', 15, 300);
		if($check_spam_ip['success'] === FALSE){
			$msg = $check_spam_ip['msg'];
			$response_data = array(
				'result'  => 0,
				'html' => $this->alert_template($msg, FALSE)
			);
			echo json_encode($response_data);
			exit;
		}
		
		$check_spam = $this->_check_spam_code('validate', 6, 180, $coupon_code);
		if($check_spam['success'] === FALSE){
			$msg = $check_spam['msg'];
			$response_data = array(
				'result'  => 0,
				'html' => $this->alert_template($msg, FALSE)
			);
			echo json_encode($response_data);
			exit;
		}

        

		if(empty($coupon_code)){
			$msg = 'Voucher Code must not be blank.';
			$response_data = array(
				'result'  => 0,
				'html' => $this->alert_template($msg, FALSE)
			);
			echo json_encode($response_data);
			exit;
		}
		
        /* $coupon_select  = "*,
        IF(a.is_nationwide = 1, 
            'Nationwide', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'ORC',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'"; */
		$coupon_select = 'a.*, b.coupon_cat_name, d.coupon_scope_masking';
        $coupon_join    = [
			'coupon_category_tbl b' => 'b.coupon_cat_id = a.coupon_cat_id AND a.coupon_code = "' . $coupon_code .'" AND a.coupon_status = 1',
			'coupon_transaction_details_tbl c' => 'a.coupon_id = c.coupon_id',
			'coupon_transaction_header_tbl d' => 'c.coupon_transaction_header_id = d.coupon_transaction_header_id',
		];
        $check_coupon     = $this->main->check_join('coupon_tbl a', $coupon_join, TRUE, FALSE, FALSE, $coupon_select);
        $date_now       = strtotime(date("Y-m-d"));


        /*if ($check_code['result'] == FALSE || $check_code['info']->coupon_status != 1) {
            $response_data = [
                'result'  => 0,
                'html' => '
                <div class="alert alert-danger" role="alert">
                    Invalid '.SEC_SYS_NAME.' Code
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>'
            ];
            echo json_encode($response_data);
            return;
        }

        $coupon_start = strtotime($check_code['info']->coupon_start);
        $coupon_end   = strtotime($check_code['info']->coupon_end);
        if ($date_now < $coupon_start) {
            $response_data = [
                'result'  => 0,
                'html' => '
                <div class="alert alert-danger" role="alert">
                    '.SEC_SYS_NAME.' Code have not yet started
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>'
            ];
            echo json_encode($response_data);
            return;
        }

        if ($date_now > $coupon_end) {
            $response_data = [
                'result'  => 0,
                'html' => '
                <div class="alert alert-danger" role="alert">
                    '.SEC_SYS_NAME.' Code have not yet started
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>'
            ];
            echo json_encode($response_data);
            return;
        }


        if ($check_code['info']->coupon_use >= $check_code['info']->coupon_qty) {
            $response_data = [
                'result'  => 0,
                'html' => '
                <div class="alert alert-danger" role="alert">
                    '.SEC_SYS_NAME.' Code has already been redeemed
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>'
            ];
            echo json_encode($response_data);
            return;
        }


        $response_data = [
            'result' => 1,
            'html' => '
            <div class="alert alert-success" role="alert">
                Ang '.SYS_NAME.' mo ay valid ng 1 ORC at valid NATIONWIDE. Ito ay <strong><i>'. $check_code['info']->coupon_cat_name. '</i></strong>.
                <button type="button" class="close" data-dismiss="alert" aria-label="close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>'
        ];

        echo json_encode($response_data);
        return;*/

        $message = $coupon_code;
        if($check_coupon['result'] == TRUE){
            $coupon_id = $check_coupon['info']->coupon_id;
            $use = $check_coupon['info']->coupon_use;
            $coupon_qty = $check_coupon['info']->coupon_qty;
            $coupon_start = date('Y-m-d', strtotime($check_coupon['info']->coupon_start));
            $coupon_end = date('Y-m-d', strtotime($check_coupon['info']->coupon_end));
            $today_date = date('Y-m-d');

            $category = $check_coupon['info']->coupon_cat_name;

            $coupon_type = $check_coupon['info']->coupon_type_id;
            $value_type = $check_coupon['info']->coupon_value_type_id;
            $amount = $check_coupon['info']->coupon_amount;
            $scope_masking = $check_coupon['info']->coupon_scope_masking;

            $mobile = '';
            if($use < $coupon_qty){
                if($today_date <= $coupon_end){//Check coupon if expired
                    if($today_date >= $coupon_start){//Check coupon if redeemd date is started

                        if($coupon_type == 1){  // STANDARD EVOUCHER
                            if($value_type == 1){ // For percentage Discount
                                if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide

                                    $sms = 'Ang '.SYS_NAME.' mo ay valid worth ' . $amount . '% at valid NATIONWIDE. Ito ay ' . $category . '.';
                                }else{ //Find valid BC
                                    
                                    $bc = $this->_get_bc($coupon_id);

                                    $sms = 'Ang '.SYS_NAME.' mo ay valid worth ' . $amount . '% discount at valid sa ' . $bc .'. Ito ay '. $category . '.';
                                }
                            }elseif($value_type == 2){ // Flat amount Discount
                                if($check_coupon['info']->is_nationwide == 1){

                                    $sms = 'Ang '.SYS_NAME.' mo ay valid worth P' . $amount . ' discount at valid NATIONWIDE. Ito ay '. $category . '.';
                                }else{
                                    //Find valid BC
                                    $bc = $this->_get_bc($coupon_id);

                                    $sms = 'Ang '.SYS_NAME.' mo ay valid worth P' . $amount . ' discount at valid sa ' . $bc .'. Ito ay '. $category . '.';
                                }
                            }

                            $result = 1;
                        }elseif($coupon_type == 2){ // PRODUCT E-VOUCHER
                            if($value_type == 1){ // For percentage Discount                        
                                if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide
                                    if($check_coupon['info']->is_orc == 1){ // CHECK IF ORC ONLY
                                        if($check_coupon['info']->coupon_amount == 100){
                                            
											$amount_product = '1 ORC';
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, 'NATIONWIDE');
                                        }else{
                                            
											$amount_product = 'worth ' . $amount . '% discount ng ORC';
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, 'NATIONWIDE');
                                        }
                                    }else{
                                        $prod = $this->_get_prod($coupon_id);
                                        if($check_coupon['info']->coupon_amount == 100){
                                            
											$amount_product = '1 '.$prod;
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, 'NATIONWIDE');
                                        }else{
                                            
											$amount_product = 'worth ' . $amount . '% discount ng ' . $prod;
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, 'NATIONWIDE');
                                        }
                                    }
                                }else{ //Find valid BC
                                    
                                    $bc = $this->_get_bc($coupon_id);

                                    if($check_coupon['info']->is_orc == 1){
                                        if($check_coupon['info']->coupon_amount == 100){
                                            
											$amount_product = '1 ORC';
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, $bc);
                                        }else{
                                            
											$amount_product = 'worth ' . $amount . '% discount ng ORC';
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, $bc);
                                        }
                                    }else{
                                        $prod = $this->_get_prod($coupon_id);
                                        if($check_coupon['info']->coupon_amount == 100){
                                            
											$amount_product = '1 '.$prod;
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, $bc);
                                        }else{
                                            
											$amount_product = 'worth ' . $amount . '% discount ng ' . $prod;
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, $bc);
                                        }
                                    }
                                }
                            }elseif($value_type == 2){  // Flat amount Discount
                               

                                if($check_coupon['info']->is_nationwide == 1){
                                    if($check_coupon['info']->is_orc == 1){
                                        
										$amount_product = $amount . ' discount para sa ORC';
										$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, 'NATIONWIDE');
                                    }else{
                                        $prod = $this->_get_prod($coupon_id);
                                        
										$amount_product = $amount . ' discount para sa ' . $prod;
										$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, 'NATIONWIDE');
                                    }
                                }else{ //Find valid BC
                                    $bc = $this->_get_bc($coupon_id);

                                    if($check_coupon['info']->is_orc == 1){
                                        
										$amount_product = $amount . ' discount para sa ORC';
										$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, $bc);
                                    }else{
                                        $prod = $this->_get_prod($coupon_id);
                                        
										$amount_product = $amount . ' discount para sa ' . $prod;
										$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, $bc);   
                                    }
                                }
                            }

                            $result = 1;
                        }else{
                            $result = 0;
                            $sms = $this->_invalid_response_msg(['type' => 'invalid_type']);
                        }                        
                    }else{
                        $result = 0;
						$params = ['type' => 'redemption_not_started', 'coupon_start' => $coupon_start];
						$sms = $this->_invalid_response_msg($params);
                    }
                }else{ //Invalid coupon is expired
                    
                    $result = 0;
                    $sms = $this->_invalid_response_msg(['type' => 'expired']);
                }
            }else{
                $result = 0;
                // $sms = 'Sorry '.SYS_NAME.' was already redeemed.';
				$filter = array(
					'redeemed_coupon_log_code'		=> $message,
					'redeemed_coupon_log_status'	=> 1
				);
				$check_voucher_code = $this->main->check_data('redeemed_coupon_log_tbl', $filter, TRUE);
				if($check_voucher_code['result'] == TRUE){
					$redeemer_number = $check_voucher_code['info']->redeemed_coupon_log_contact_number;
					$redeemer_outlet_ifs = $check_voucher_code['info']->outlet_ifs;
					$redeemer_outlet_name = $check_voucher_code['info']->outlet_name;
					$redeemer_staff_name = $check_voucher_code['info']->staff_name;
					$redeemer_ts = $check_voucher_code['info']->redeemed_coupon_log_added;
					// $sms = 'Sorry '.SYS_NAME.' was already redeemed by '.$redeemer_number.' on '.date_format(date_create($redeemer_ts),"M d, Y h:i:s A").'.';
					if(strlen($redeemer_number) == 11 && $redeemer_outlet_name == '' && $redeemer_staff_name == ''){
						
						$params = [
							'type' 							=> 'already_redeemed_old',
							'redeemer_ts_date' 				=> date_format(date_create($redeemer_ts),"M d, Y"),
							'redeemer_ts_time' 				=> date_format(date_create($redeemer_ts),"h:i:s A"),
							'redeemer_number' 				=> $redeemer_number,
						];
						$sms = $this->_invalid_response_msg($params);
					} else {
						$params = [
							'type' 							=> 'already_redeemed_new',
							'redeemer_staff_name' 			=> $redeemer_staff_name,
							'redeemer_outlet_name' 			=> $redeemer_outlet_name,
							'redeemer_ts_date' 				=> date_format(date_create($redeemer_ts),"M d, Y"),
							'redeemer_ts_time' 				=> date_format(date_create($redeemer_ts),"h:i:s A"),
						];
						$sms = $this->_invalid_response_msg($params);
					}
				} else {
					$sms = 'Sorry, Ang '.SEC_SYS_NAME.' CODE ay REDEEMED na.';
				}
            }
        }else{
            //Invalid and already redeem
            $result = 0;
			$sms = $this->_invalid_response_msg(['type' => 'invalid_code']);
        }

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $sms = ''.SEC_SYS_NAME.' Redeem Failed. Please Try Again!';
            $result = 0;
        } else {
            $this->db->trans_commit();
        }

        if($result == 0){
            $response_msg = '
                <div class="alert alert-danger" role="alert">
                    ' . $sms . '
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            ';
        }elseif($result == 1){
            $response_msg = '
                <div class="alert alert-success" role="alert">
                    ' . $sms . '
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            ';
        }

        $response_data = array(
            'result'  => $result,
            'html' => $response_msg
        );
        echo json_encode($response_data);
        exit;

    }

    public function web_redeem_coupon()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
           show_404();
        }

        $parent_db      = $GLOBALS['parent_db'];
        $coupon_code    = clean_data($this->input->post('code'));
        $contact_number = clean_data($this->input->post('contact'));

		$coupon_select = 'a.*, b.coupon_cat_name, d.coupon_scope_masking';
        $join_coupon = array(
			'coupon_category_tbl b' => "a.coupon_cat_id = b.coupon_cat_id AND a.coupon_status = 1 AND a.coupon_code = '" . $coupon_code . "'",
			'coupon_transaction_details_tbl c' => 'a.coupon_id = c.coupon_id',
			'coupon_transaction_header_tbl d' => 'c.coupon_transaction_header_id = d.coupon_transaction_header_id'
		);
        $check_coupon = $this->main->check_join('coupon_tbl a', $join_coupon, TRUE, FALSE, FALSE, $coupon_select);
        $date_now       = strtotime(date("Y-m-d"));
        
        if (empty($contact_number)) {
            $response_data = [
                'result'  => 0,
                'html' => '
                <div class="alert alert-danger" role="alert">
                    Contact Number is Required
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>'
            ];
            echo json_encode($response_data);
            return;
        }

        if (strlen($contact_number) != 11) {
            $response_data = [
                'result'  => 0,
                'html' => '
                <div class="alert alert-danger" role="alert">
                    Contact Number length is invalid
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>'
            ];
            echo json_encode($response_data);
            return;
        }

        $where  = [ 'contact_number_prefix' => substr($contact_number, 0, 4) ];
        $result = $this->main->check_data("{$parent_db}.contact_number_prefix_tbl", $where);
        if (!$result) {
            $response_data = [
                'result'  => 0,
                'html' => '
                <div class="alert alert-danger" role="alert">
                    Invalid Contact Prefix
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>'
            ];
            echo json_encode($response_data);
            return;
        }

        /*if ($check_code['result'] == FALSE || $check_code['info']->coupon_status != 1) {
            $this->_store_failed_coupon_redeem_log($coupon_code);
            $response_data = [
                'result'  => 0,
                'html' => '
                <div class="alert alert-danger" role="alert">
                    Invalid '.SEC_SYS_NAME.' Code
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>'
            ];
            echo json_encode($response_data);
            return;
        }*/

        /*$coupon_start = strtotime($check_code['info']->coupon_start);
        $coupon_end   = strtotime($check_code['info']->coupon_end);
        if ($date_now < $coupon_start) {
            $this->_store_failed_coupon_redeem_log($coupon_code);
            $response_data = [
                'result'  => 0,
                'html' => '
                <div class="alert alert-danger" role="alert">
                    '.SEC_SYS_NAME.' Code have not yet started
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>'
            ];
            echo json_encode($response_data);
            return;
        }

        if ($date_now > $coupon_end) {
            $this->_store_failed_coupon_redeem_log($coupon_code);
            $response_data = [
                'result'  => 0,
                'html' => '
                <div class="alert alert-danger" role="alert">
                    '.SEC_SYS_NAME.' Code is already expired 
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>'
            ];
            echo json_encode($response_data);
            return;
        }


        if ($check_code['info']->coupon_use >= $check_code['info']->coupon_qty) {
            $this->_store_failed_coupon_redeem_log($coupon_code);
            $response_data = [
                'result'  => 0,
                'html' => '
                <div class="alert alert-danger" role="alert">
                    '.SEC_SYS_NAME.' Code has already been redeemed
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>'
            ];
            echo json_encode($response_data);
            return;
        }*/

        /*$reference_code = 'CPN' . generate_random_coupon(6);
        $log_data = [
            'redeemed_coupon_log_reference_code' => $reference_code,
            'redeemed_coupon_log_code'           => $coupon_code,
            'redeem_type_id'                     => 1,
            'redeemed_coupon_log_added'          => date_now(),
            'redeemed_coupon_log_status'         => 1,
        ];
        $redeemed_coupon_result = $this->main->insert_data('redeemed_coupon_log_tbl', $log_data, TRUE);*/

        /*if ($redeemed_coupon_result['result']) {
            $products = $check_code['info']->products;

            $message = 'Ang '.SYS_NAME.' mo ay valid ng 1 ORC at valid NATIONWIDE. Ito ay '. $check_code['info']->coupon_cat_name. '. Maari mo nang iinput sa POS ang approval code ' . $reference_code;

            $response_data = [
                'result' => 1,
                'html' => '
                <div class="alert alert-success" role="alert">
                    Ang '.SYS_NAME.' mo ay valid ng 1 ORC at valid NATIONWIDE. Ito ay <strong><i>'. $check_code['info']->coupon_cat_name. '</i></strong>. Maari mo nang iinput sa POS ang approval code <strong><i>' . $reference_code .'</i></strong>
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>'
            ];

            $this->db->trans_start();

            $coupon_set    = [ 'coupon_use' => (int)$check_code['info']->coupon_use + 1 ];
            $coupon_where  = [ 'coupon_id' => $check_code['info']->coupon_id ];
            $coupon_result = $this->main->update_data('coupon_tbl', $coupon_set, $coupon_where);
            $sms_result    = itexmo($contact_number, $message, 'BAVI-TEST4321');

            $redeem_outgoing_data = [
                'redeem_outgoing_sms'      => $message,
                'redeem_outgoing_no'       => $contact_number,
                'redeem_outgoing_response' => $sms_result,
                'redeem_outgoing_added'    => date_now(),
                'redeem_outgoing_status'   => 1
            ];
            $redeem_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $redeem_outgoing_data, TRUE);
            $coupon_link_data  = [ 
                'coupon_id'              => $check_code['info']->coupon_id,
                'redeemed_coupon_log_id' => $redeemed_coupon_result['id'],
                'redeem_outgoing_id'     => $redeem_outgoing['id'] // Pending
            ];
            $coupon_link_result = $this->main->insert_data('redeem_coupon_tbl', $coupon_link_data);

            if($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                $response_data = [
                    'result'  => 0,
                    'html' => '
                    <div class="alert alert-danger" role="alert">
                        '.SEC_SYS_NAME.' Redeem Failed. Please Try Again
                        <button type="button" class="close" data-dismiss="alert" aria-label="close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>'
                ];
                echo json_encode($response_data);
                return;
            } else {
                $this->db->trans_commit();
            }

            echo json_encode($response_data);
            return;
        } else {
            $response_data = [
                'result'  => 0,
                'html' => '
                <div class="alert alert-danger" role="alert">
                    '.SEC_SYS_NAME.' Redeem Failed. Please Try Again
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>'
            ];
            echo json_encode($response_data);
            return;
        }*/
        $this->db->trans_start();
        $message = $coupon_code;
        $mobile = $contact_number;
        if($check_coupon['result'] == TRUE){
            $coupon_id = $check_coupon['info']->coupon_id;
            $use = $check_coupon['info']->coupon_use;
            $coupon_qty = $check_coupon['info']->coupon_qty;
            $coupon_start = date('Y-m-d', strtotime($check_coupon['info']->coupon_start));
            $coupon_end = date('Y-m-d', strtotime($check_coupon['info']->coupon_end));
            $today_date = date('Y-m-d');

            $category = $check_coupon['info']->coupon_cat_name;

            $coupon_type = $check_coupon['info']->coupon_type_id;
            $value_type = $check_coupon['info']->coupon_value_type_id;
            $amount = $check_coupon['info']->coupon_amount;
			$scope_masking = $check_coupon['info']->coupon_scope_masking;
			$coupon_cat_id = $check_coupon['info']->coupon_cat_id;

            if($use < $coupon_qty){ // Valid coupon
                if($today_date <= $coupon_end){// Check coupon if expired
                    if($today_date >= $coupon_start){//Check coupon if redeemd date is started

                        $order_no = '';
                        $counter = TRUE;
                        while($counter){
                            $reference_no = 'CPN' . generate_random_coupon(6);

                            $check_ref = $this->main->check_data('redeemed_coupon_log_tbl', array('redeemed_coupon_log_reference_code' => $reference_no));

                            if($check_ref == FALSE){
                                $counter = FALSE;
                            }
                        }

                        $set_redeem = array(
                            'redeem_type_id' => 1,
                            'redeemed_coupon_log_reference_code' => $reference_no,
                            'redeemed_coupon_log_code' => $message,
                            'redeemed_coupon_log_contact_number' => $mobile,
                            'redeem_coupon_gateway' => '',
                            'sms_server_timestamp' => '',
                            'redeemed_coupon_log_added' => date_now(),
                            'redeemed_coupon_log_status' => 1
                        );

                        $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);
                        if($insert_redeem['result'] == TRUE){
                            $redeemed_coupon_log_id = $insert_redeem['id'];
                            $new_count = $use + 1;
                            $set_coupon = array('coupon_use' => $new_count);
                            $where_coupon = array('coupon_id' => $coupon_id);

                            $update_coupon = $this->main->update_data('coupon_tbl', $set_coupon, $where_coupon);
                            if($update_coupon == TRUE){
                                if($coupon_type == 1){ // STANDARD COUPON
                                    if($value_type == 1){ // For percentage Discount
                                        if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide

                                            $sms = 'Ang '.SYS_NAME.' mo ay valid worth ' . $amount . '% at valid NATIONWIDE. Ito ay ' . $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }else{ //Find valid BC
                                            
                                            $bc = $this->_get_bc($coupon_id);

                                            $sms = 'Ang '.SYS_NAME.' mo ay valid worth ' . $amount . '% discount at valid sa ' . $bc .'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }
                                    }elseif($value_type == 2){ //Flat amount Discount
                                        if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide

                                            $sms = 'Ang '.SYS_NAME.' mo ay valid worth P' . $amount . ' discount at valid NATIONWIDE. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }else{ //Find valid BC
                                            
                                            $bc = $this->_get_bc($coupon_id);

                                            $sms = 'Ang '.SYS_NAME.' mo ay valid worth P' . $amount . ' discount at valid sa ' . $bc .'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }
                                    }

                                    $result = 1;
                                    $send_sms = send_sms($mobile, $sms, 'BAVI-TEST4321', 'CHOOKS');

                                    $set_outgoing = array(
                                        'redeem_outgoing_sms' => $sms,
                                        'redeem_outgoing_no' => $mobile,
                                        'redeem_outgoing_response' => $send_sms,
                                        'redeem_outgoing_added' => date_now(),
                                        'redeem_outgoing_status' => 1
                                    );

                                    $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing, TRUE);
                                }elseif($coupon_type == 2){ // PRODUCT COUPON
                                    if($value_type == 1){ // For percentage Discount
                                        if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide
                                            if($check_coupon['info']->is_orc == 1){ // check if ORC only
                                                if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
													$amount_product = '1 ORC';
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE');
                                                }else{
													$amount_product = 'worth ' . $amount . '% discount ng ORC';
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE');
                                                }
                                            }else{
                                                $prod = $this->_get_prod($coupon_id);
                                                if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
													$amount_product = '1 '.$prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE');
                                                }else{
													$amount_product = 'worth ' . $amount . '% discount ng ' . $prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE');
                                                }
                                            }
                                        }else{ //Find valid BC
                                            $bc = $scope_masking == '' ? $this->_get_bc($coupon_id) : $scope_masking;
                                            if($check_coupon['info']->is_orc == 1){ // check if ORC only
                                                if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
													$amount_product = '1 ORC';
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc);
                                                }else{
													$amount_product = 'worth ' . $amount . '% discount ng ORC';
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc);
                                                }
                                            }else{
                                                $prod = $this->_get_prod($coupon_id);
                                                if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
													$amount_product = '1 '.$prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc);
                                                }else{
													$amount_product = 'worth ' . $amount . '% discount ng ' . $prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc);
                                                }
                                            }
                                        }
                                    }elseif($value_type == 2){ //Flat amount Discount
                                        if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide
                                            if($check_coupon['info']->is_orc == 1){ // check if ORC only
												$amount_product = $amount . ' discount para sa ORC';
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE');
                                            }else{
												$prod = $this->_get_prod($coupon_id);
												$amount_product = $amount . ' discount para sa ' . $prod;
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE');
                                            }
                                        }else{ //Find valid BC
                                            $bc = $scope_masking == '' ? $this->_get_bc($coupon_id) : $scope_masking;
                                            if($check_coupon['info']->is_orc == 1){ // check if ORC only
												$amount_product = $amount . ' discount para sa ORC';
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc);          
                                            }else{
                                                $prod = $this->_get_prod($coupon_id);
												$amount_product = $amount . ' discount para sa ' . $prod;
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc);  
                                            }
                                        }
                                    }

                                    $result = 1;
                                    $send_sms = send_sms($mobile, $sms, 'BAVI-TEST4321', 'CHOOKS');

                                    $set_outgoing = array(
                                        'redeem_outgoing_sms' => $sms,
                                        'redeem_outgoing_no' => $mobile,
                                        'redeem_outgoing_response' => $send_sms,
                                        'redeem_outgoing_added' => date_now(),
                                        'redeem_outgoing_status' => 1
                                    );

                                    $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing, TRUE);
                                }else{ // Invalid Coupon Type
                                    $result = 0;
									$sms = $this->_invalid_response_msg(['type' => 'invalid_type']);
                                }

                                $outgoing_id = $insert_outgoing['id'];

                                $update_redeem = $this->main->update_data('redeemed_coupon_log_tbl', array('redeemed_coupon_log_response' => $sms), array('redeemed_coupon_log_id' => $redeemed_coupon_log_id));

                                $insert_con = $this->main->insert_data('redeem_coupon_tbl', array('redeemed_coupon_log_id' => $redeemed_coupon_log_id, 'coupon_id' => $coupon_id, 'redeem_outgoing_id' => $outgoing_id, 'redeem_coupon_added' => date_now(), 'redeem_coupon_status' => 1));
                            }else{ // Error while updating data
                                $result = 0;
                                $sms = 'Error while updating data. Please try again';

                                $update_redeem = $this->main->update_data('redeemed_coupon_log_tbl', array('redeemed_coupon_log_status' => 2, 'redeemed_coupon_log_response' => $sms), array('redeemed_coupon_log_id' => $redeemed_coupon_log_id));
                                

                                $set_outgoing = array(
                                    'redeem_outgoing_sms' => $sms,
                                    'redeem_outgoing_no' => $mobile,
                                    'redeem_outgoing_response' => '',
                                    'redeem_outgoing_added' => date_now(),
                                    'redeem_outgoing_status' => 1
                                );

                                $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                            }
                        }else{ // Error while inserting data
                            
                            $result = 0;
                            $sms = 'Error while processing. Please try again';

                            $set_redeem = array(
                                'redeem_type_id' => 1,
                                'redeemed_coupon_log_reference_code' => '',
                                'redeemed_coupon_log_code' => $message,
                                'redeemed_coupon_log_contact_number' => $mobile,
                                'redeem_coupon_gateway' => '',
                                'sms_server_timestamp' => '',
                                'redeemed_coupon_log_response' => $sms,
                                'redeemed_coupon_log_added' => date_now(),
                                'redeemed_coupon_log_status' => 2
                            );

                            $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

                            $set_outgoing = array(
                                'redeem_outgoing_sms' => $sms,
                                'redeem_outgoing_no' => $mobile,
                                'redeem_outgoing_response' => '',
                                'redeem_outgoing_added' => date_now(),
                                'redeem_outgoing_status' => 1
                            );

                            $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                        }
                    }else{ // redemption has not yet started
                        $result = 0;
						$params = ['type' => 'redemption_not_started', 'coupon_start' => $coupon_start];
						$sms = $this->_invalid_response_msg($params);
                        $set_redeem = array(
                            'redeem_type_id' => 1,
                            'redeemed_coupon_log_reference_code' => '',
                            'redeemed_coupon_log_code' => $message,
                            'redeemed_coupon_log_contact_number' => $mobile,
                            'redeem_coupon_gateway' => '',
                            'sms_server_timestamp' => '',
                            'redeemed_coupon_log_response' => $sms,
                            'redeemed_coupon_log_added' => date_now(),
                            'redeemed_coupon_log_status' => 2
                        );

                        $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

                        $set_outgoing = array(
                            'redeem_outgoing_sms' => $sms,
                            'redeem_outgoing_no' => $mobile,
                            'redeem_outgoing_response' => '',
                            'redeem_outgoing_added' => date_now(),
                            'redeem_outgoing_status' => 1
                        );

                        $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                    }
                }else{ // Invalid, coupon is expired
                    
                    $result = 0;
					$sms = $this->_invalid_response_msg(['type' => 'expired']);

                    $set_redeem = array(
                        'redeem_type_id' => 1,
                        'redeemed_coupon_log_reference_code' => '',
                        'redeemed_coupon_log_code' => $message,
                        'redeemed_coupon_log_contact_number' => $mobile,
                        'redeem_coupon_gateway' => '',
                        'sms_server_timestamp' => '',
                        'redeemed_coupon_log_response' => $sms,
                        'redeemed_coupon_log_added' => date_now(),
                        'redeemed_coupon_log_status' => 2
                    );

                    $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

                    $set_outgoing = array(
                        'redeem_outgoing_sms' => $sms,
                        'redeem_outgoing_no' => $mobile,
                        'redeem_outgoing_response' => '',
                        'redeem_outgoing_added' => date_now(),
                        'redeem_outgoing_status' => 1
                    );

                    $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                }
            }else{ // e-Voucher was already redeemed
                $result = 0;
                // $sms = 'Sorry '.SYS_NAME.' was already redeemed.';
				$filter = array(
					'redeemed_coupon_log_code'		=> $message,
					'redeemed_coupon_log_status'	=> 1
				);
				$check_voucher_code = $this->main->check_data('redeemed_coupon_log_tbl', $filter, TRUE);
				if($check_voucher_code['result'] == TRUE){
					$redeemer_number = $check_voucher_code['info']->redeemed_coupon_log_contact_number;
					$redeemer_outlet_ifs = $check_voucher_code['info']->outlet_ifs;
					$redeemer_outlet_name = $check_voucher_code['info']->outlet_name;
					$redeemer_staff_name = $check_voucher_code['info']->staff_name;
					$redeemer_ts = $check_voucher_code['info']->redeemed_coupon_log_added;
					// $sms = 'Sorry '.SYS_NAME.' was already redeemed by '.$redeemer_number.' on '.date_format(date_create($redeemer_ts),"M d, Y h:i:s A").'.';
					if(strlen($redeemer_number) == 11 && $redeemer_outlet_name == '' && $redeemer_staff_name == ''){
						
						$params = [
							'type' 							=> 'already_redeemed_old',
							'redeemer_ts_date' 				=> date_format(date_create($redeemer_ts),"M d, Y"),
							'redeemer_ts_time' 				=> date_format(date_create($redeemer_ts),"h:i:s A"),
							'redeemer_number' 				=> $redeemer_number,
						];
						$sms = $this->_invalid_response_msg($params);
					} else {

						$params = [
							'type' 							=> 'already_redeemed_new',
							'redeemer_staff_name' 			=> $redeemer_staff_name,
							'redeemer_outlet_name' 			=> $redeemer_outlet_name,
							'redeemer_ts_date' 				=> date_format(date_create($redeemer_ts),"M d, Y"),
							'redeemer_ts_time' 				=> date_format(date_create($redeemer_ts),"h:i:s A"),
						];
						$sms = $this->_invalid_response_msg($params);
					}
				} else {
					$sms = 'Sorry, Ang '.SEC_SYS_NAME.' CODE ay REDEEMED na.';
				}

                $set_redeem = array(
                    'redeem_type_id' => 1,
                    'redeemed_coupon_log_reference_code' => '',
                    'redeemed_coupon_log_code' => $message,
                    'redeemed_coupon_log_contact_number' => $mobile,
                    'redeem_coupon_gateway' => '',
                    'sms_server_timestamp' => '',
                    'redeemed_coupon_log_response' => $sms,
                    'redeemed_coupon_log_added' => date_now(),
                    'redeemed_coupon_log_status' => 2
                );

                $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

                $set_outgoing = array(
                    'redeem_outgoing_sms' => $sms,
                    'redeem_outgoing_no' => $mobile,
                    'redeem_outgoing_response' => '',
                    'redeem_outgoing_added' => date_now(),
                    'redeem_outgoing_status' => 1
                );

                $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                //Invalid and already redeem
            }
        }else{ // Invalid and already redeem
            
            $result = 0;
			$sms = $this->_invalid_response_msg(['type' => 'invalid_code']);

            $set_redeem = array(
                'redeem_type_id' => 1,
                'redeemed_coupon_log_reference_code' => '',
                'redeemed_coupon_log_code' => $message,
                'redeemed_coupon_log_contact_number' => $mobile,
                'redeem_coupon_gateway' => '',
                'sms_server_timestamp' => '',
                'redeemed_coupon_log_response' => $sms,
                'redeemed_coupon_log_added' => date_now(),
                'redeemed_coupon_log_status' => 2
            );

            $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

            $set_outgoing = array(
                'redeem_outgoing_sms' => $sms,
                'redeem_outgoing_no' => $mobile,
                'redeem_outgoing_response' => '',
                'redeem_outgoing_added' => date_now(),
                'redeem_outgoing_status' => 1
            );

            $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
        }

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $sms = ''.SEC_SYS_NAME.' Redeem Failed. Please Try Again!';
            $result = 0;
        } else {
            $this->db->trans_commit();
        }

        if($result == 0){
            $response_msg = '
                <div class="alert alert-danger" role="alert">
                    ' . $sms . '
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            ';
        }elseif($result == 1){
            $response_msg = '
                <div class="alert alert-success" role="alert">
                    ' . $sms . '
                    <button type="button" class="close" data-dismiss="alert" aria-label="close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            ';
        }

        $response_data = array(
            'result'  => $result,
            'html' => $response_msg
        );
        echo json_encode($response_data);
        exit;
    }

	public function enhanced_web_redeem_coupon()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
           show_404();
        }

		$coupon_code = clean_data( strtoupper($this->input->post('code')) );
		$coupon_code = trim($coupon_code);
		$user_id = clean_data($this->input->post('user_id')) != '' ? decode(clean_data($this->input->post('user_id'))) : 0;

		if($user_id == 0){
			$check_spam_ip = $this->_check_spam_ip('redeem', 15, 300);
			if($check_spam_ip['success'] === FALSE){
				$msg = $check_spam_ip['msg'];
				$response_data = array(
					'result'  => 0,
					'html' => $this->alert_template($msg, FALSE)
				);
				echo json_encode($response_data);
				exit;
			}
			
			$check_spam_code = $this->_check_spam_code('redeem', 5, 180, $coupon_code);
			if($check_spam_code['success'] === FALSE){
				$msg = $check_spam_code['msg'];
				$response_data = array(
					'result'  => 0,
					'html' => $this->alert_template($msg, FALSE)
				);
				echo json_encode($response_data);
				exit;
			}
		}

		$device_info = $this->_get_device_info();

		

        $parent_db   = $GLOBALS['parent_db'];
        $outlet_ifs = clean_data( strtoupper($this->input->post('store_code')) );
        $outlet_ifs = trim($outlet_ifs);
        $crew_code = clean_data( strtoupper($this->input->post('crew_code')) );
        $crew_code = trim($crew_code);
		$staff_code = $crew_code;

		if(empty($coupon_code)){
			$msg = 'Voucher Code must not be blank.';
			$response_data = array(
				'result'  => 0,
				'html' => $this->alert_template($msg, FALSE)
			);
			echo json_encode($response_data);
			exit;
		}
		if(empty($outlet_ifs)){
			$msg = 'Store Code must not be blank.';
			$response_data = array(
				'result'  => 0,
				'html' => $this->alert_template($msg, FALSE)
			);
			echo json_encode($response_data);
			exit;
		}
		if(empty($staff_code)){
			$msg = 'Crew Code must not be blank.';
			$response_data = array(
				'result'  => 0,
				'html' => $this->alert_template($msg, FALSE)
			);
			echo json_encode($response_data);
			exit;
		}

		
		
		$coupon_select = 'a.*, b.coupon_cat_name, d.coupon_scope_masking, d.coupon_transaction_header_id, d.coupon_transaction_header_added';
        $join_coupon = array(
			'coupon_category_tbl b' => "a.coupon_cat_id = b.coupon_cat_id AND a.coupon_status = 1 AND a.coupon_code = '" . $coupon_code . "'",
			'coupon_transaction_details_tbl c' => 'a.coupon_id = c.coupon_id',
			'coupon_transaction_header_tbl d' => 'c.coupon_transaction_header_id = d.coupon_transaction_header_id'
		);
        $check_coupon = $this->main->check_join('coupon_tbl a', $join_coupon, TRUE, FALSE, FALSE, $coupon_select);
        $date_now       = strtotime(date("Y-m-d"));


		$this->db->trans_start();
        $message = $coupon_code;
        $mobile = '';
		$outlet_code = NULL;
		$bc_code = NULL;
		$outlet_name = '';
		$staff_name = '';
		
        if($check_coupon['result'] == TRUE){
            $coupon_id = $check_coupon['info']->coupon_id;
            $use = $check_coupon['info']->coupon_use;
            $coupon_qty = $check_coupon['info']->coupon_qty;
            $coupon_start = date('Y-m-d', strtotime($check_coupon['info']->coupon_start));
            $coupon_end = date('Y-m-d', strtotime($check_coupon['info']->coupon_end));
            $today_date = date('Y-m-d');

            $category = $check_coupon['info']->coupon_cat_name;

            $coupon_type = $check_coupon['info']->coupon_type_id;
            $value_type = $check_coupon['info']->coupon_value_type_id;
            $amount = $check_coupon['info']->coupon_amount;
			$scope_masking = $check_coupon['info']->coupon_scope_masking;
			$coupon_cat_id = $check_coupon['info']->coupon_cat_id;
			// $trans_hdr_details = '[ '.$check_coupon['info']->coupon_transaction_header_id.' - '.$check_coupon['info']->coupon_transaction_header_added.' ] ';
			$trans_hdr_details = '';

			//* CHECK OUTLET IFS ON RECORD AND COMPARE IT TO COUPON BC DEFINED
			$bc = $scope_masking == '' ? $this->_get_bc($coupon_id) : $scope_masking;
			$verify_outlet = $this->_verify_outlet($coupon_id, $outlet_ifs, $check_coupon['info']->is_nationwide, $bc);
			
			// $this->_checker($verify_outlet['outletIFS']);
			if( $verify_outlet['status'] == 'error' ){
				$response_data = array(
					'result'  => 0,
					'html' => $this->alert_template($verify_outlet['msg'], FALSE)
				);
				echo json_encode($response_data);
				exit;
			}
			$outlet_name = $verify_outlet['outletDesc'];
			$outlet_code = $verify_outlet['outletCode'];
			$outlet_ifs = $verify_outlet['outletIFS'];
			$mobile = $outlet_ifs; //* AS ORIGINATOR
			$bc_code = $verify_outlet['branchCode'];

			//* CHECK CREW CODE ON RECORD AND COMPARE IT TO COUPON BC DEFINED
			$verify_crew = $this->_verify_crew($coupon_id, $staff_code, $outlet_ifs);
			// $this->_checker($verify_crew['crew_fullname']);
			if( $verify_crew['status'] == 'error' ){
				$response_data = array(
					'result'  => 0,
					'html' => $this->alert_template($verify_crew['msg'], FALSE)
				);
				echo json_encode($response_data);
				exit;
			}
			$staff_name = $verify_crew['crew_fullname'];

            if($use < $coupon_qty){ // Valid coupon
                if($today_date <= $coupon_end){// Check coupon if expired
                    if($today_date >= $coupon_start){//Check coupon if redeemd date is started

                        $order_no = '';
                        $counter = TRUE;
                        while($counter){
                            $reference_no = 'CPN' . generate_random_coupon(6);

                            $check_ref = $this->main->check_data('redeemed_coupon_log_tbl', array('redeemed_coupon_log_reference_code' => $reference_no));

                            if($check_ref == FALSE){
                                $counter = FALSE;
                            }
                        }

                        $set_redeem = array(
                            'redeem_type_id' => 1,
                            'redeemed_coupon_log_reference_code' => $reference_no,
                            'redeemed_coupon_log_code' => $message,
                            'redeemed_coupon_log_contact_number' => $mobile,
                            'redeem_coupon_gateway' => '',
                            'sms_server_timestamp' => '',
                            'redeemed_coupon_log_added' => date_now(),
                            'redeemed_coupon_log_status' => 1,
							'outlet_ifs' => $outlet_ifs,
							'outlet_name' => $outlet_name,
							'staff_code' => $staff_code,
							'staff_name' => $staff_name,
							'outlet_code' => $outlet_code,
							'bc_code' => $bc_code,
							'user_id' => $user_id,
							'user_agent' => $device_info['user_agent'],
							'detected_os' => $device_info['detected_os'],
							'browser' => $device_info['browser'],
							'device_type' => $device_info['device_type'],
							'ip_address' => $device_info['ip_address'],
                        );

                        $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);
                        if($insert_redeem['result'] == TRUE){
                            $redeemed_coupon_log_id = $insert_redeem['id'];
                            $new_count = $use + 1;
                            $set_coupon = array('coupon_use' => $new_count);
                            $where_coupon = array('coupon_id' => $coupon_id);

                            $update_coupon = $this->main->update_data('coupon_tbl', $set_coupon, $where_coupon);
                            if($update_coupon == TRUE){
                                if($coupon_type == 1){ //* STANDARD COUPON
                                    if($value_type == 1){ // For percentage Discount
                                        if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide

                                            $sms = 'Ang '.SYS_NAME.' mo ay valid worth ' . $amount . '% at valid NATIONWIDE. Ito ay ' . $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }else{ //Find valid BC
                                            
                                            $bc = $this->_get_bc($coupon_id);

                                            $sms = 'Ang '.SYS_NAME.' mo ay valid worth ' . $amount . '% discount at valid sa ' . $bc .'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }
                                    }elseif($value_type == 2){ //Flat amount Discount
                                        if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide

                                            $sms = 'Ang '.SYS_NAME.' mo ay valid worth P' . $amount . ' discount at valid NATIONWIDE. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }else{ //Find valid BC
                                            
                                            $bc = $this->_get_bc($coupon_id);

                                            $sms = 'Ang '.SYS_NAME.' mo ay valid worth P' . $amount . ' discount at valid sa ' . $bc .'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }
                                    }

                                    $result = 1;
                                    // $send_sms = send_sms($mobile, $sms, 'BAVI-TEST4321', 'CHOOKS');
									$send_sms = TRUE;

                                    $set_outgoing = array(
                                        'redeem_outgoing_sms' => $sms,
                                        'redeem_outgoing_no' => $mobile,
                                        'redeem_outgoing_response' => $send_sms,
                                        'redeem_outgoing_added' => date_now(),
                                        'redeem_outgoing_status' => 1,
										'redeem_outgoing_outlet_ifs' => $outlet_ifs,
										'redeem_outgoing_outlet_name' => $outlet_name,
										'redeem_outgoing_staff_code' => $staff_code,
										'redeem_outgoing_staff_name' => $staff_name,
										'redeem_outgoing_outlet_code' => $outlet_code,
										'redeem_outgoing_bc_code' => $bc_code,
										'user_id' => $user_id
                                    );

                                    $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing, TRUE);
                                }elseif($coupon_type == 2){ //* PRODUCT COUPON
                                    if($value_type == 1){ //* For percentage Discount
                                        if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide
                                            if($check_coupon['info']->is_orc == 1){ // check if ORC only
                                                if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
													$amount_product = '1 ORC';
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                                }else{
													$amount_product = 'worth ' . $amount . '% discount ng ORC';
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                                }
                                            }else{
                                                $prod = $this->_get_prod($coupon_id);
                                                if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
													$amount_product = '1 '.$prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                                }else{
													$amount_product = 'worth ' . $amount . '% discount ng ' . $prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                                }
                                            }
                                        }else{ //* Find valid BC
                                            $bc = $scope_masking == '' ? $this->_get_bc($coupon_id) : $scope_masking;
                                            if($check_coupon['info']->is_orc == 1){ // check if ORC only
                                                if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
													$amount_product = '1 ORC';
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details);
                                                }else{
													$amount_product = 'worth ' . $amount . '% discount ng ORC';
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details);
                                                }
                                            }else{
                                                $prod = $this->_get_prod($coupon_id);
                                                if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
													$amount_product = '1 '.$prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details);
                                                }else{
													$amount_product = 'worth ' . $amount . '% discount ng ' . $prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details);
                                                }
                                            }
                                        }
                                    }elseif($value_type == 2){ //* Flat amount Discount
                                        if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide
                                            if($check_coupon['info']->is_orc == 1){ // check if ORC only
												$amount_product = $amount . ' discount para sa ORC';
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                            }else{
												$prod = $this->_get_prod($coupon_id);
												$amount_product = $amount . ' discount para sa ' . $prod;
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                            }
                                        }else{ //* Find valid BC
                                            $bc = $scope_masking == '' ? $this->_get_bc($coupon_id) : $scope_masking;
                                            if($check_coupon['info']->is_orc == 1){ // check if ORC only
												$amount_product = $amount . ' discount para sa ORC';
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details);
                                            }else{
                                                $prod = $this->_get_prod($coupon_id);
												$amount_product = $amount . ' discount para sa ' . $prod;
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details);
                                            }
                                        }
                                    }

                                    $result = 1;
                                    // $send_sms = send_sms($mobile, $sms, 'BAVI-TEST4321', 'CHOOKS');
									$send_sms = TRUE;

                                    $set_outgoing = array(
                                        'redeem_outgoing_sms' => $sms,
                                        'redeem_outgoing_no' => $mobile,
                                        'redeem_outgoing_response' => $send_sms,
                                        'redeem_outgoing_added' => date_now(),
                                        'redeem_outgoing_status' => 1,
										'redeem_outgoing_outlet_ifs' => $outlet_ifs,
										'redeem_outgoing_outlet_name' => $outlet_name,
										'redeem_outgoing_staff_code' => $staff_code,
										'redeem_outgoing_staff_name' => $staff_name,
										'redeem_outgoing_outlet_code' => $outlet_code,
										'redeem_outgoing_bc_code' => $bc_code,
										'user_id' => $user_id
                                    );

                                    $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing, TRUE);
                                }else{ //* Invalid Coupon Type
                                    $result = 0;
                                    $sms = $this->_invalid_response_msg(['type' => 'invalid_type']);

									$new_count--;
									$set_coupon = array('coupon_use' => $new_count);
									$where_coupon = array('coupon_id' => $coupon_id);

									$update_coupon = $this->main->update_data('coupon_tbl', $set_coupon, $where_coupon);

									$update_redeem = $this->main->update_data('redeemed_coupon_log_tbl', array('redeemed_coupon_log_status' => '2'), array('redeemed_coupon_log_id' => $redeemed_coupon_log_id));
                                }

                                $outgoing_id = $insert_outgoing['id'];

                                $update_redeem = $this->main->update_data('redeemed_coupon_log_tbl', array('redeemed_coupon_log_response' => $sms), array('redeemed_coupon_log_id' => $redeemed_coupon_log_id));

                                $insert_con = $this->main->insert_data('redeem_coupon_tbl', array('redeemed_coupon_log_id' => $redeemed_coupon_log_id, 'coupon_id' => $coupon_id, 'redeem_outgoing_id' => $outgoing_id, 'redeem_coupon_added' => date_now(), 'redeem_coupon_status' => 1));
                            }else{ //* Error while updating data
                                $result = 0;
                                $sms = 'Error while updating data. Please try again';

                                $update_redeem = $this->main->update_data('redeemed_coupon_log_tbl', array('redeemed_coupon_log_status' => 2, 'redeemed_coupon_log_response' => $sms), array('redeemed_coupon_log_id' => $redeemed_coupon_log_id));
                                

                                $set_outgoing = array(
                                    'redeem_outgoing_sms' => $sms,
                                    'redeem_outgoing_no' => $mobile,
                                    'redeem_outgoing_response' => '',
                                    'redeem_outgoing_added' => date_now(),
                                    'redeem_outgoing_status' => 1,
									'redeem_outgoing_outlet_ifs' => $outlet_ifs,
									'redeem_outgoing_outlet_name' => $outlet_name,
									'redeem_outgoing_staff_code' => $staff_code,
									'redeem_outgoing_staff_name' => $staff_name,
									'redeem_outgoing_outlet_code' => $outlet_code,
									'redeem_outgoing_bc_code' => $bc_code,
									'user_id' => $user_id
                                );

                                $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                            }
                        }else{ //* Error while inserting data
                            
                            $result = 0;
                            $sms = 'Error while processing. Please try again';

                            $set_redeem = array(
                                'redeem_type_id' => 1,
                                'redeemed_coupon_log_reference_code' => '',
                                'redeemed_coupon_log_code' => $message,
                                'redeemed_coupon_log_contact_number' => $mobile,
                                'redeem_coupon_gateway' => '',
                                'sms_server_timestamp' => '',
                                'redeemed_coupon_log_response' => $sms,
                                'redeemed_coupon_log_added' => date_now(),
                                'redeemed_coupon_log_status' => 2,
								'outlet_ifs' => $outlet_ifs,
								'outlet_name' => $outlet_name,
								'staff_code' => $staff_code,
								'staff_name' => $staff_name,
								'outlet_code' => $outlet_code,
								'bc_code' => $bc_code,
								'user_id' => $user_id,
								'user_agent' => $device_info['user_agent'],
								'detected_os' => $device_info['detected_os'],
								'browser' => $device_info['browser'],
								'device_type' => $device_info['device_type'],
								'ip_address' => $device_info['ip_address'],
                            );

                            $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

                            $set_outgoing = array(
                                'redeem_outgoing_sms' => $sms,
                                'redeem_outgoing_no' => $mobile,
                                'redeem_outgoing_response' => '',
                                'redeem_outgoing_added' => date_now(),
                                'redeem_outgoing_status' => 1,
								'redeem_outgoing_outlet_ifs' => $outlet_ifs,
								'redeem_outgoing_outlet_name' => $outlet_name,
								'redeem_outgoing_staff_code' => $staff_code,
								'redeem_outgoing_staff_name' => $staff_name,
								'redeem_outgoing_outlet_code' => $outlet_code,
								'redeem_outgoing_bc_code' => $bc_code,
								'user_id' => $user_id
                            );

                            $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                        }
                    }else{ //* redemption has not yet started
                        $result = 0;
                        $params = ['type' => 'redemption_not_started', 'coupon_start' => $coupon_start];
						$sms = $this->_invalid_response_msg($params);
                        $set_redeem = array(
                            'redeem_type_id' => 1,
                            'redeemed_coupon_log_reference_code' => '',
                            'redeemed_coupon_log_code' => $message,
                            'redeemed_coupon_log_contact_number' => $mobile,
                            'redeem_coupon_gateway' => '',
                            'sms_server_timestamp' => '',
                            'redeemed_coupon_log_response' => $sms,
                            'redeemed_coupon_log_added' => date_now(),
                            'redeemed_coupon_log_status' => 2,
							'outlet_ifs' => $outlet_ifs,
							'outlet_name' => $outlet_name,
							'staff_code' => $staff_code,
							'staff_name' => $staff_name,
							'outlet_code' => $outlet_code,
							'bc_code' => $bc_code,
							'user_id' => $user_id,
							'user_agent' => $device_info['user_agent'],
							'detected_os' => $device_info['detected_os'],
							'browser' => $device_info['browser'],
							'device_type' => $device_info['device_type'],
							'ip_address' => $device_info['ip_address'],
                        );

                        $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

                        $set_outgoing = array(
                            'redeem_outgoing_sms' => $sms,
                            'redeem_outgoing_no' => $mobile,
                            'redeem_outgoing_response' => '',
                            'redeem_outgoing_added' => date_now(),
                            'redeem_outgoing_status' => 1,
							'redeem_outgoing_outlet_ifs' => $outlet_ifs,
							'redeem_outgoing_outlet_name' => $outlet_name,
							'redeem_outgoing_staff_code' => $staff_code,
							'redeem_outgoing_staff_name' => $staff_name,
							'redeem_outgoing_outlet_code' => $outlet_code,
							'redeem_outgoing_bc_code' => $bc_code,
							'user_id' => $user_id
                        );

                        $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                    }
                }else{ //* Invalid, coupon is expired
                    
                    $result = 0;
                    $sms = $this->_invalid_response_msg(['type' => 'expired']);

                    $set_redeem = array(
                        'redeem_type_id' => 1,
                        'redeemed_coupon_log_reference_code' => '',
                        'redeemed_coupon_log_code' => $message,
                        'redeemed_coupon_log_contact_number' => $mobile,
                        'redeem_coupon_gateway' => '',
                        'sms_server_timestamp' => '',
                        'redeemed_coupon_log_response' => $sms,
                        'redeemed_coupon_log_added' => date_now(),
                        'redeemed_coupon_log_status' => 2,
						'outlet_ifs' => $outlet_ifs,
						'outlet_name' => $outlet_name,
						'staff_code' => $staff_code,
						'staff_name' => $staff_name,
						'outlet_code' => $outlet_code,
						'bc_code' => $bc_code,
						'user_id' => $user_id,
						'user_agent' => $device_info['user_agent'],
						'detected_os' => $device_info['detected_os'],
						'browser' => $device_info['browser'],
						'device_type' => $device_info['device_type'],
						'ip_address' => $device_info['ip_address'],
                    );

                    $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

                    $set_outgoing = array(
                        'redeem_outgoing_sms' => $sms,
                        'redeem_outgoing_no' => $mobile,
                        'redeem_outgoing_response' => '',
                        'redeem_outgoing_added' => date_now(),
                        'redeem_outgoing_status' => 1,
						'redeem_outgoing_outlet_ifs' => $outlet_ifs,
						'redeem_outgoing_outlet_name' => $outlet_name,
						'redeem_outgoing_staff_code' => $staff_code,
						'redeem_outgoing_staff_name' => $staff_name,
						'redeem_outgoing_outlet_code' => $outlet_code,
						'redeem_outgoing_bc_code' => $bc_code,
						'user_id' => $user_id
                    );

                    $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                }
            }else{ //* e-Voucher was already redeemed
                $result = 0;
                // $sms = 'Sorry '.SYS_NAME.' was already redeemed.';
				$filter = array(
					'redeemed_coupon_log_code'		=> $message,
					'redeemed_coupon_log_status'	=> 1
				);
				$check_voucher_code = $this->main->check_data('redeemed_coupon_log_tbl', $filter, TRUE);
				if($check_voucher_code['result'] == TRUE){
					$redeemer_number = $check_voucher_code['info']->redeemed_coupon_log_contact_number;
					$redeemer_outlet_ifs = $check_voucher_code['info']->outlet_ifs;
					$redeemer_outlet_name = $check_voucher_code['info']->outlet_name;
					$redeemer_staff_name = $check_voucher_code['info']->staff_name;
					$redeemer_ts = $check_voucher_code['info']->redeemed_coupon_log_added;

					if(strlen($redeemer_number) == 11 && $redeemer_outlet_name == '' && $redeemer_staff_name == ''){
						
						$params = [
							'type' 							=> 'already_redeemed_old',
							'redeemer_ts_date' 				=> date_format(date_create($redeemer_ts),"M d, Y"),
							'redeemer_ts_time' 				=> date_format(date_create($redeemer_ts),"h:i:s A"),
							'redeemer_number' 				=> $redeemer_number,
						];
						$sms = $this->_invalid_response_msg($params);
					} else {
						
						$params = [
							'type' 							=> 'already_redeemed_new',
							'redeemer_staff_name' 			=> $redeemer_staff_name,
							'redeemer_outlet_name' 			=> $redeemer_outlet_name,
							'redeemer_ts_date' 				=> date_format(date_create($redeemer_ts),"M d, Y"),
							'redeemer_ts_time' 				=> date_format(date_create($redeemer_ts),"h:i:s A"),
						];
						$sms = $this->_invalid_response_msg($params);
					}
				} else {
					$sms = 'Sorry, Ang '.SEC_SYS_NAME.' CODE ay REDEEMED na.';
				}

                $set_redeem = array(
                    'redeem_type_id' => 1,
                    'redeemed_coupon_log_reference_code' => '',
                    'redeemed_coupon_log_code' => $message,
                    'redeemed_coupon_log_contact_number' => $mobile,
                    'redeem_coupon_gateway' => '',
                    'sms_server_timestamp' => '',
                    'redeemed_coupon_log_response' => $sms,
                    'redeemed_coupon_log_added' => date_now(),
                    'redeemed_coupon_log_status' => 2,
					'outlet_ifs' => $outlet_ifs,
					'outlet_name' => $outlet_name,
					'staff_code' => $staff_code,
					'staff_name' => $staff_name,
					'outlet_code' => $outlet_code,
					'bc_code' => $bc_code,
					'user_id' => $user_id,
					'user_agent' => $device_info['user_agent'],
					'detected_os' => $device_info['detected_os'],
					'browser' => $device_info['browser'],
					'device_type' => $device_info['device_type'],
					'ip_address' => $device_info['ip_address'],
                );

                $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

                $set_outgoing = array(
                    'redeem_outgoing_sms' => $sms,
                    'redeem_outgoing_no' => $mobile,
                    'redeem_outgoing_response' => '',
                    'redeem_outgoing_added' => date_now(),
                    'redeem_outgoing_status' => 1,
					'redeem_outgoing_outlet_ifs' => $outlet_ifs,
					'redeem_outgoing_outlet_name' => $outlet_name,
					'redeem_outgoing_staff_code' => $staff_code,
					'redeem_outgoing_staff_name' => $staff_name,
					'redeem_outgoing_outlet_code' => $outlet_code,
					'redeem_outgoing_bc_code' => $bc_code,
					'user_id' => $user_id
                );

                $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                //Invalid and already redeem
            }
        }else{ //* Invalid and already redeem
            
            $result = 0;
			$sms = $this->_invalid_response_msg(['type' => 'invalid_code']);

            $set_redeem = array(
                'redeem_type_id' => 1,
                'redeemed_coupon_log_reference_code' => '',
                'redeemed_coupon_log_code' => $message,
                'redeemed_coupon_log_contact_number' => $mobile,
                'redeem_coupon_gateway' => '',
                'sms_server_timestamp' => '',
                'redeemed_coupon_log_response' => $sms,
                'redeemed_coupon_log_added' => date_now(),
                'redeemed_coupon_log_status' => 2,
				'outlet_ifs' => $outlet_ifs,
				'outlet_name' => $outlet_name,
				'staff_code' => $staff_code,
				'staff_name' => $staff_name,
				'outlet_code' => $outlet_code,
				'bc_code' => $bc_code,
				'user_id' => $user_id,
				'user_agent' => $device_info['user_agent'],
				'detected_os' => $device_info['detected_os'],
				'browser' => $device_info['browser'],
				'device_type' => $device_info['device_type'],
				'ip_address' => $device_info['ip_address'],
            );

            $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

            $set_outgoing = array(
                'redeem_outgoing_sms' => $sms,
                'redeem_outgoing_no' => $mobile,
                'redeem_outgoing_response' => '',
                'redeem_outgoing_added' => date_now(),
                'redeem_outgoing_status' => 1,
				'redeem_outgoing_outlet_ifs' => $outlet_ifs,
				'redeem_outgoing_outlet_name' => $outlet_name,
				'redeem_outgoing_staff_code' => $staff_code,
				'redeem_outgoing_staff_name' => $staff_name,
				'redeem_outgoing_outlet_code' => $outlet_code,
				'redeem_outgoing_bc_code' => $bc_code,
				'user_id' => $user_id
            );

            $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
        }

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $sms = ''.SEC_SYS_NAME.' Redeem Failed. Please Try Again!';
            $result = 0;
        } else {
            $this->db->trans_commit();
        }

		$response_msg = $this->alert_template($sms, $result);

        $response_data = array(
            'result'  => $result,
            'html' => $response_msg
        );
        echo json_encode($response_data);
        exit;

    }
	
	public function enhanced_web_redeem_emp_coupon()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
           show_404();
        }

		$coupon_code = clean_data( strtoupper($this->input->post('code')) );
		$coupon_code = trim($coupon_code);
		$added_info = clean_data( strtoupper($this->input->post('added_info')) );
		$added_info = trim($added_info);
		$user_id = clean_data($this->input->post('user_id')) != '' ? decode(clean_data($this->input->post('user_id'))) : 0;

		if($user_id == 0){
			$check_spam_ip = $this->_check_spam_ip('redeem', 15, 300);
			if($check_spam_ip['success'] === FALSE){
				$msg = $check_spam_ip['msg'];
				$response_data = array(
					'result'  => 0,
					'html' => $this->alert_template($msg, FALSE)
				);
				echo json_encode($response_data);
				exit;
			}
			
			$check_spam_code = $this->_check_spam_code('redeem', 5, 180, $coupon_code);
			if($check_spam_code['success'] === FALSE){
				$msg = $check_spam_code['msg'];
				$response_data = array(
					'result'  => 0,
					'html' => $this->alert_template($msg, FALSE)
				);
				echo json_encode($response_data);
				exit;
			}
		}

		$device_info = $this->_get_device_info();

		

        $parent_db   = $GLOBALS['parent_db'];
        $outlet_ifs = "";
        $outlet_ifs = trim($outlet_ifs);
        $crew_code = "";
        $crew_code = trim($crew_code);
		$staff_code = $crew_code;

		if(empty($coupon_code)){
			$msg = 'Voucher Code must not be blank.';
			$response_data = array(
				'result'  => 0,
				'html' => $this->alert_template($msg, FALSE)
			);
			echo json_encode($response_data);
			exit;
		}
		

		
		
		$coupon_select = 'a.*, b.coupon_cat_name, d.coupon_scope_masking, d.coupon_transaction_header_id, d.coupon_transaction_header_added';
        $join_coupon = array(
			'coupon_category_tbl b' => "a.coupon_cat_id = b.coupon_cat_id AND a.coupon_status = 1 AND a.coupon_code = '" . $coupon_code . "'",
			'coupon_transaction_details_tbl c' => 'a.coupon_id = c.coupon_id',
			'coupon_transaction_header_tbl d' => 'c.coupon_transaction_header_id = d.coupon_transaction_header_id'
		);
        $check_coupon = $this->main->check_join('coupon_tbl a', $join_coupon, TRUE, FALSE, FALSE, $coupon_select);
        $date_now       = strtotime(date("Y-m-d"));


		$this->db->trans_start();
        $message = $coupon_code;
        $mobile = '';
		$outlet_code = NULL;
		$bc_code = NULL;
		$outlet_name = '';
		$staff_name = '';
		
        if($check_coupon['result'] == TRUE){
            $coupon_id = $check_coupon['info']->coupon_id;
            $use = $check_coupon['info']->coupon_use;
            $coupon_qty = $check_coupon['info']->coupon_qty;
            $coupon_start = date('Y-m-d', strtotime($check_coupon['info']->coupon_start));
            $coupon_end = date('Y-m-d', strtotime($check_coupon['info']->coupon_end));
            $today_date = date('Y-m-d');

            $category = $check_coupon['info']->coupon_cat_name;

            $coupon_type = $check_coupon['info']->coupon_type_id;
            $value_type = $check_coupon['info']->coupon_value_type_id;
            $amount = $check_coupon['info']->coupon_amount;
			$scope_masking = $check_coupon['info']->coupon_scope_masking;
			$coupon_cat_id = $check_coupon['info']->coupon_cat_id;
			// $trans_hdr_details = '[ '.$check_coupon['info']->coupon_transaction_header_id.' - '.$check_coupon['info']->coupon_transaction_header_added.' ] ';
			$trans_hdr_details = '';

			if($coupon_cat_id != 7){
				$msg = 'Voucher category is not allowed in your access.';
				$response_data = array(
					'result'  => 0,
					'html' => $this->alert_template($msg, FALSE)
				);
				echo json_encode($response_data);
				exit;
			}

			

            if($use < $coupon_qty){ // Valid coupon
                if($today_date <= $coupon_end){// Check coupon if expired
                    if($today_date >= $coupon_start){//Check coupon if redeemd date is started

                        $order_no = '';
                        $counter = TRUE;
                        while($counter){
                            $reference_no = 'CPN' . generate_random_coupon(6);

                            $check_ref = $this->main->check_data('redeemed_coupon_log_tbl', array('redeemed_coupon_log_reference_code' => $reference_no));

                            if($check_ref == FALSE){
                                $counter = FALSE;
                            }
                        }

                        $set_redeem = array(
                            'redeem_type_id' => 1,
                            'redeemed_coupon_log_reference_code' => $reference_no,
                            'redeemed_coupon_log_code' => $message,
                            'redeemed_coupon_log_contact_number' => $mobile,
                            'redeem_coupon_gateway' => '',
                            'sms_server_timestamp' => '',
                            'redeemed_coupon_log_added' => date_now(),
                            'redeemed_coupon_log_status' => 1,
							'outlet_ifs' => $outlet_ifs,
							'outlet_name' => $outlet_name,
							'staff_code' => $staff_code,
							'staff_name' => $staff_name,
							'outlet_code' => $outlet_code,
							'bc_code' => $bc_code,
							'user_id' => $user_id,
							'user_agent' => $device_info['user_agent'],
							'detected_os' => $device_info['detected_os'],
							'browser' => $device_info['browser'],
							'device_type' => $device_info['device_type'],
							'ip_address' => $device_info['ip_address'],
							'added_info' => $added_info,
                        );

                        $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);
                        if($insert_redeem['result'] == TRUE){
                            $redeemed_coupon_log_id = $insert_redeem['id'];
                            $new_count = $use + 1;
                            $set_coupon = array('coupon_use' => $new_count);
                            $where_coupon = array('coupon_id' => $coupon_id);

                            $update_coupon = $this->main->update_data('coupon_tbl', $set_coupon, $where_coupon);
                            if($update_coupon == TRUE){
                                if($coupon_type == 1){ //* STANDARD COUPON
                                    if($value_type == 1){ // For percentage Discount
                                        if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide

                                            $sms = 'Ang '.SYS_NAME.' mo ay valid worth ' . $amount . '% at valid NATIONWIDE. Ito ay ' . $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }else{ //Find valid BC
                                            
                                            $bc = $this->_get_bc($coupon_id);

                                            $sms = 'Ang '.SYS_NAME.' mo ay valid worth ' . $amount . '% discount at valid sa ' . $bc .'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }
                                    }elseif($value_type == 2){ //Flat amount Discount
                                        if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide

                                            $sms = 'Ang '.SYS_NAME.' mo ay valid worth P' . $amount . ' discount at valid NATIONWIDE. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }else{ //Find valid BC
                                            
                                            $bc = $this->_get_bc($coupon_id);

                                            $sms = 'Ang '.SYS_NAME.' mo ay valid worth P' . $amount . ' discount at valid sa ' . $bc .'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }
                                    }

                                    $result = 1;
                                    // $send_sms = send_sms($mobile, $sms, 'BAVI-TEST4321', 'CHOOKS');
									$send_sms = TRUE;

                                    $set_outgoing = array(
                                        'redeem_outgoing_sms' => $sms,
                                        'redeem_outgoing_no' => $mobile,
                                        'redeem_outgoing_response' => $send_sms,
                                        'redeem_outgoing_added' => date_now(),
                                        'redeem_outgoing_status' => 1,
										'redeem_outgoing_outlet_ifs' => $outlet_ifs,
										'redeem_outgoing_outlet_name' => $outlet_name,
										'redeem_outgoing_staff_code' => $staff_code,
										'redeem_outgoing_staff_name' => $staff_name,
										'redeem_outgoing_outlet_code' => $outlet_code,
										'redeem_outgoing_bc_code' => $bc_code,
										'user_id' => $user_id
                                    );

                                    $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing, TRUE);
                                }elseif($coupon_type == 2){ //* PRODUCT COUPON
                                    if($value_type == 1){ //* For percentage Discount
                                        if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide
                                            if($check_coupon['info']->is_orc == 1){ // check if ORC only
                                                if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
													$amount_product = '1 ORC';
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                                }else{
													$amount_product = 'worth ' . $amount . '% discount ng ORC';
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                                }
                                            }else{
                                                $prod = $this->_get_prod($coupon_id);
                                                if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
													$amount_product = '1 '.$prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                                }else{
													$amount_product = 'worth ' . $amount . '% discount ng ' . $prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                                }
                                            }
                                        }else{ //* Find valid BC
                                            $bc = $scope_masking == '' ? $this->_get_bc($coupon_id) : $scope_masking;
                                            if($check_coupon['info']->is_orc == 1){ // check if ORC only
                                                if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
													$amount_product = '1 ORC';
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details);
                                                }else{
													$amount_product = 'worth ' . $amount . '% discount ng ORC';
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details);
                                                }
                                            }else{
                                                $prod = $this->_get_prod($coupon_id);
                                                if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
													$amount_product = '1 '.$prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details);
                                                }else{
													$amount_product = 'worth ' . $amount . '% discount ng ' . $prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details);
                                                }
                                            }
                                        }
                                    }elseif($value_type == 2){ //* Flat amount Discount
                                        if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide
                                            if($check_coupon['info']->is_orc == 1){ // check if ORC only
												$amount_product = $amount . ' discount para sa ORC';
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                            }else{
												$prod = $this->_get_prod($coupon_id);
												$amount_product = $amount . ' discount para sa ' . $prod;
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                            }
                                        }else{ //* Find valid BC
                                            $bc = $scope_masking == '' ? $this->_get_bc($coupon_id) : $scope_masking;
                                            if($check_coupon['info']->is_orc == 1){ // check if ORC only
												$amount_product = $amount . ' discount para sa ORC';
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details);
                                            }else{
                                                $prod = $this->_get_prod($coupon_id);
												$amount_product = $amount . ' discount para sa ' . $prod;
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details);
                                            }
                                        }
                                    }

                                    $result = 1;
                                    // $send_sms = send_sms($mobile, $sms, 'BAVI-TEST4321', 'CHOOKS');
									$send_sms = TRUE;

                                    $set_outgoing = array(
                                        'redeem_outgoing_sms' => $sms,
                                        'redeem_outgoing_no' => $mobile,
                                        'redeem_outgoing_response' => $send_sms,
                                        'redeem_outgoing_added' => date_now(),
                                        'redeem_outgoing_status' => 1,
										'redeem_outgoing_outlet_ifs' => $outlet_ifs,
										'redeem_outgoing_outlet_name' => $outlet_name,
										'redeem_outgoing_staff_code' => $staff_code,
										'redeem_outgoing_staff_name' => $staff_name,
										'redeem_outgoing_outlet_code' => $outlet_code,
										'redeem_outgoing_bc_code' => $bc_code,
										'user_id' => $user_id
                                    );

                                    $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing, TRUE);
                                }else{ //* Invalid Coupon Type
                                    $result = 0;
                                    $sms = $this->_invalid_response_msg(['type' => 'invalid_type']);

									$new_count--;
									$set_coupon = array('coupon_use' => $new_count);
									$where_coupon = array('coupon_id' => $coupon_id);

									$update_coupon = $this->main->update_data('coupon_tbl', $set_coupon, $where_coupon);

									$update_redeem = $this->main->update_data('redeemed_coupon_log_tbl', array('redeemed_coupon_log_status' => '2'), array('redeemed_coupon_log_id' => $redeemed_coupon_log_id));
                                }

                                $outgoing_id = $insert_outgoing['id'];

                                $update_redeem = $this->main->update_data('redeemed_coupon_log_tbl', array('redeemed_coupon_log_response' => $sms), array('redeemed_coupon_log_id' => $redeemed_coupon_log_id));

                                $insert_con = $this->main->insert_data('redeem_coupon_tbl', array('redeemed_coupon_log_id' => $redeemed_coupon_log_id, 'coupon_id' => $coupon_id, 'redeem_outgoing_id' => $outgoing_id, 'redeem_coupon_added' => date_now(), 'redeem_coupon_status' => 1));
                            }else{ //* Error while updating data
                                $result = 0;
                                $sms = 'Error while updating data. Please try again';

                                $update_redeem = $this->main->update_data('redeemed_coupon_log_tbl', array('redeemed_coupon_log_status' => 2, 'redeemed_coupon_log_response' => $sms), array('redeemed_coupon_log_id' => $redeemed_coupon_log_id));
                                

                                $set_outgoing = array(
                                    'redeem_outgoing_sms' => $sms,
                                    'redeem_outgoing_no' => $mobile,
                                    'redeem_outgoing_response' => '',
                                    'redeem_outgoing_added' => date_now(),
                                    'redeem_outgoing_status' => 1,
									'redeem_outgoing_outlet_ifs' => $outlet_ifs,
									'redeem_outgoing_outlet_name' => $outlet_name,
									'redeem_outgoing_staff_code' => $staff_code,
									'redeem_outgoing_staff_name' => $staff_name,
									'redeem_outgoing_outlet_code' => $outlet_code,
									'redeem_outgoing_bc_code' => $bc_code,
									'user_id' => $user_id
                                );

                                $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                            }
                        }else{ //* Error while inserting data
                            
                            $result = 0;
                            $sms = 'Error while processing. Please try again';

                            $set_redeem = array(
                                'redeem_type_id' => 1,
                                'redeemed_coupon_log_reference_code' => '',
                                'redeemed_coupon_log_code' => $message,
                                'redeemed_coupon_log_contact_number' => $mobile,
                                'redeem_coupon_gateway' => '',
                                'sms_server_timestamp' => '',
                                'redeemed_coupon_log_response' => $sms,
                                'redeemed_coupon_log_added' => date_now(),
                                'redeemed_coupon_log_status' => 2,
								'outlet_ifs' => $outlet_ifs,
								'outlet_name' => $outlet_name,
								'staff_code' => $staff_code,
								'staff_name' => $staff_name,
								'outlet_code' => $outlet_code,
								'bc_code' => $bc_code,
								'user_id' => $user_id,
								'user_agent' => $device_info['user_agent'],
								'detected_os' => $device_info['detected_os'],
								'browser' => $device_info['browser'],
								'device_type' => $device_info['device_type'],
								'ip_address' => $device_info['ip_address'],
								'added_info' => $added_info,
                            );

                            $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

                            $set_outgoing = array(
                                'redeem_outgoing_sms' => $sms,
                                'redeem_outgoing_no' => $mobile,
                                'redeem_outgoing_response' => '',
                                'redeem_outgoing_added' => date_now(),
                                'redeem_outgoing_status' => 1,
								'redeem_outgoing_outlet_ifs' => $outlet_ifs,
								'redeem_outgoing_outlet_name' => $outlet_name,
								'redeem_outgoing_staff_code' => $staff_code,
								'redeem_outgoing_staff_name' => $staff_name,
								'redeem_outgoing_outlet_code' => $outlet_code,
								'redeem_outgoing_bc_code' => $bc_code,
								'user_id' => $user_id
                            );

                            $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                        }
                    }else{ //* redemption has not yet started
                        $result = 0;
                        $params = ['type' => 'redemption_not_started', 'coupon_start' => $coupon_start];
						$sms = $this->_invalid_response_msg($params);
                        $set_redeem = array(
                            'redeem_type_id' => 1,
                            'redeemed_coupon_log_reference_code' => '',
                            'redeemed_coupon_log_code' => $message,
                            'redeemed_coupon_log_contact_number' => $mobile,
                            'redeem_coupon_gateway' => '',
                            'sms_server_timestamp' => '',
                            'redeemed_coupon_log_response' => $sms,
                            'redeemed_coupon_log_added' => date_now(),
                            'redeemed_coupon_log_status' => 2,
							'outlet_ifs' => $outlet_ifs,
							'outlet_name' => $outlet_name,
							'staff_code' => $staff_code,
							'staff_name' => $staff_name,
							'outlet_code' => $outlet_code,
							'bc_code' => $bc_code,
							'user_id' => $user_id,
							'user_agent' => $device_info['user_agent'],
							'detected_os' => $device_info['detected_os'],
							'browser' => $device_info['browser'],
							'device_type' => $device_info['device_type'],
							'ip_address' => $device_info['ip_address'],
							'added_info' => $added_info,
                        );

                        $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

                        $set_outgoing = array(
                            'redeem_outgoing_sms' => $sms,
                            'redeem_outgoing_no' => $mobile,
                            'redeem_outgoing_response' => '',
                            'redeem_outgoing_added' => date_now(),
                            'redeem_outgoing_status' => 1,
							'redeem_outgoing_outlet_ifs' => $outlet_ifs,
							'redeem_outgoing_outlet_name' => $outlet_name,
							'redeem_outgoing_staff_code' => $staff_code,
							'redeem_outgoing_staff_name' => $staff_name,
							'redeem_outgoing_outlet_code' => $outlet_code,
							'redeem_outgoing_bc_code' => $bc_code,
							'user_id' => $user_id
                        );

                        $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                    }
                }else{ //* Invalid, coupon is expired
                    
                    $result = 0;
                    $sms = $this->_invalid_response_msg(['type' => 'expired']);

                    $set_redeem = array(
                        'redeem_type_id' => 1,
                        'redeemed_coupon_log_reference_code' => '',
                        'redeemed_coupon_log_code' => $message,
                        'redeemed_coupon_log_contact_number' => $mobile,
                        'redeem_coupon_gateway' => '',
                        'sms_server_timestamp' => '',
                        'redeemed_coupon_log_response' => $sms,
                        'redeemed_coupon_log_added' => date_now(),
                        'redeemed_coupon_log_status' => 2,
						'outlet_ifs' => $outlet_ifs,
						'outlet_name' => $outlet_name,
						'staff_code' => $staff_code,
						'staff_name' => $staff_name,
						'outlet_code' => $outlet_code,
						'bc_code' => $bc_code,
						'user_id' => $user_id,
						'user_agent' => $device_info['user_agent'],
						'detected_os' => $device_info['detected_os'],
						'browser' => $device_info['browser'],
						'device_type' => $device_info['device_type'],
						'ip_address' => $device_info['ip_address'],
						'added_info' => $added_info,
                    );

                    $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

                    $set_outgoing = array(
                        'redeem_outgoing_sms' => $sms,
                        'redeem_outgoing_no' => $mobile,
                        'redeem_outgoing_response' => '',
                        'redeem_outgoing_added' => date_now(),
                        'redeem_outgoing_status' => 1,
						'redeem_outgoing_outlet_ifs' => $outlet_ifs,
						'redeem_outgoing_outlet_name' => $outlet_name,
						'redeem_outgoing_staff_code' => $staff_code,
						'redeem_outgoing_staff_name' => $staff_name,
						'redeem_outgoing_outlet_code' => $outlet_code,
						'redeem_outgoing_bc_code' => $bc_code,
						'user_id' => $user_id
                    );

                    $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                }
            }else{ //* e-Voucher was already redeemed
                $result = 0;
                // $sms = 'Sorry '.SYS_NAME.' was already redeemed.';
				$filter = array(
					'redeemed_coupon_log_code'		=> $message,
					'redeemed_coupon_log_status'	=> 1
				);
				$check_voucher_code = $this->main->check_data('redeemed_coupon_log_tbl', $filter, TRUE);
				if($check_voucher_code['result'] == TRUE){
					$redeemer_number = $check_voucher_code['info']->redeemed_coupon_log_contact_number;
					$redeemer_outlet_ifs = $check_voucher_code['info']->outlet_ifs;
					$redeemer_outlet_name = $check_voucher_code['info']->outlet_name;
					$redeemer_staff_name = $check_voucher_code['info']->staff_name;
					$redeemer_ts = $check_voucher_code['info']->redeemed_coupon_log_added;

					$params = [
						'type' 							=> 'already_redeemed_old',
						'redeemer_ts_date' 				=> date_format(date_create($redeemer_ts),"M d, Y"),
						'redeemer_ts_time' 				=> date_format(date_create($redeemer_ts),"h:i:s A"),
						'redeemer_number' 				=> $redeemer_number,
					];
					$sms = $this->_invalid_response_msg($params);
					
				} else {
					$sms = 'Sorry, Ang '.SEC_SYS_NAME.' CODE ay REDEEMED na.';
				}

                $set_redeem = array(
                    'redeem_type_id' => 1,
                    'redeemed_coupon_log_reference_code' => '',
                    'redeemed_coupon_log_code' => $message,
                    'redeemed_coupon_log_contact_number' => $mobile,
                    'redeem_coupon_gateway' => '',
                    'sms_server_timestamp' => '',
                    'redeemed_coupon_log_response' => $sms,
                    'redeemed_coupon_log_added' => date_now(),
                    'redeemed_coupon_log_status' => 2,
					'outlet_ifs' => $outlet_ifs,
					'outlet_name' => $outlet_name,
					'staff_code' => $staff_code,
					'staff_name' => $staff_name,
					'outlet_code' => $outlet_code,
					'bc_code' => $bc_code,
					'user_id' => $user_id,
					'user_agent' => $device_info['user_agent'],
					'detected_os' => $device_info['detected_os'],
					'browser' => $device_info['browser'],
					'device_type' => $device_info['device_type'],
					'ip_address' => $device_info['ip_address'],
					'added_info' => $added_info,
                );

                $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

                $set_outgoing = array(
                    'redeem_outgoing_sms' => $sms,
                    'redeem_outgoing_no' => $mobile,
                    'redeem_outgoing_response' => '',
                    'redeem_outgoing_added' => date_now(),
                    'redeem_outgoing_status' => 1,
					'redeem_outgoing_outlet_ifs' => $outlet_ifs,
					'redeem_outgoing_outlet_name' => $outlet_name,
					'redeem_outgoing_staff_code' => $staff_code,
					'redeem_outgoing_staff_name' => $staff_name,
					'redeem_outgoing_outlet_code' => $outlet_code,
					'redeem_outgoing_bc_code' => $bc_code,
					'user_id' => $user_id
                );

                $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
                //Invalid and already redeem
            }
        }else{ //* Invalid and already redeem
            
            $result = 0;
			$sms = $this->_invalid_response_msg(['type' => 'invalid_code']);

            $set_redeem = array(
                'redeem_type_id' => 1,
                'redeemed_coupon_log_reference_code' => '',
                'redeemed_coupon_log_code' => $message,
                'redeemed_coupon_log_contact_number' => $mobile,
                'redeem_coupon_gateway' => '',
                'sms_server_timestamp' => '',
                'redeemed_coupon_log_response' => $sms,
                'redeemed_coupon_log_added' => date_now(),
                'redeemed_coupon_log_status' => 2,
				'outlet_ifs' => $outlet_ifs,
				'outlet_name' => $outlet_name,
				'staff_code' => $staff_code,
				'staff_name' => $staff_name,
				'outlet_code' => $outlet_code,
				'bc_code' => $bc_code,
				'user_id' => $user_id,
				'user_agent' => $device_info['user_agent'],
				'detected_os' => $device_info['detected_os'],
				'browser' => $device_info['browser'],
				'device_type' => $device_info['device_type'],
				'ip_address' => $device_info['ip_address'],
				'added_info' => $added_info,
            );

            $insert_redeem = $this->main->insert_data('redeemed_coupon_log_tbl', $set_redeem, TRUE);

            $set_outgoing = array(
                'redeem_outgoing_sms' => $sms,
                'redeem_outgoing_no' => $mobile,
                'redeem_outgoing_response' => '',
                'redeem_outgoing_added' => date_now(),
                'redeem_outgoing_status' => 1,
				'redeem_outgoing_outlet_ifs' => $outlet_ifs,
				'redeem_outgoing_outlet_name' => $outlet_name,
				'redeem_outgoing_staff_code' => $staff_code,
				'redeem_outgoing_staff_name' => $staff_name,
				'redeem_outgoing_outlet_code' => $outlet_code,
				'redeem_outgoing_bc_code' => $bc_code,
				'user_id' => $user_id
            );

            $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing);
        }

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $sms = ''.SEC_SYS_NAME.' Redeem Failed. Please Try Again!';
            $result = 0;
        } else {
            $this->db->trans_commit();
        }

		$response_msg = $this->alert_template($sms, $result);

        $response_data = array(
            'result'  => $result,
            'html' => $response_msg
        );
        echo json_encode($response_data);
        exit;

    }

	public function _check_spam_ip($prefix, $limit, $cooldown_time) {
		$this->load->helper('url');
        $ip_address = $this->input->ip_address();  // Get user IP address
        $key = $prefix.'_request_count_' . $ip_address;    // Unique key for each IP
        // $limit = 5;  // Maximum allowed attempts
        // $cooldown_time = 180; // Cooldown duration (3 minutes)
		$cool_mins = $cooldown_time / 60;

		$data['msg'] = "";
        $data['success'] = TRUE;

        // Check if request count exists for this IP
        if ($this->session->tempdata($key)) {
            $count = $this->session->tempdata($key);

            if ($count >= $limit) {
                // $data['msg'] = "Too many attempts from IP: <strong>$ip_address</strong>. Please wait for $cool_mins minutes cool down.";
                // $data['msg'] = "Sorry, Too many attempts from your device. Please wait for $cool_mins minutes cool down.";
				$data['msg'] = "Sorry, Maraming beses mo nang sinubukan mula sa iyong device. Pakihintay ng $cool_mins minuto bago muling subukan.";
                $data['success'] = FALSE;
                $data['req_count'] = $count;
            }

            // Increment request count for this IP
            $this->session->set_tempdata($key, $count + 1, $cooldown_time);
        } else {
            // First attempt - Set tempdata for this IP
            $this->session->set_tempdata($key, 1, $cooldown_time);
        }

        // echo "Request allowed! Attempt #". $this->session->tempdata($key) . " from IP: $ip_address";

		return $data;
    }
	
	public function _check_spam_code($prefix, $limit, $cooldown_time, $code) {
		// $this->load->helper('url');
        // $ip_address = $this->input->ip_address();  // Get user IP address
        $key = $prefix.'_request_count_' . $code;    // Unique key for each code
        // $limit = 5;  // Maximum allowed attempts
        // $cooldown_time = 180; // Cooldown duration (3 minutes)
		$cool_mins = $cooldown_time / 60;

		$data['msg'] = "";
        $data['success'] = TRUE;

        // Check if request count exists for this IP
        if ($this->session->tempdata($key)) {
            $count = $this->session->tempdata($key);

            if ($count >= $limit) {
                // $data['msg'] = "Sorry, Too many attempts for ".SEC_SYS_NAME." CODE : <strong>$code</strong>. Please wait for $cool_mins minutes cool down.";
				$data['msg'] = "Sorry,  Masyadong maraming pagtatangka para sa ".SEC_SYS_NAME." CODE: <strong>$code</strong>. Pakihintay ng $cool_mins minuto bago muling subukan.";
                $data['success'] = FALSE;
                $data['req_count'] = $count;
            }

            // Increment request count for this IP
            $this->session->set_tempdata($key, $count + 1, $cooldown_time);
        } else {
            // First attempt - Set tempdata for this IP
            $this->session->set_tempdata($key, 1, $cooldown_time);
        }

        // echo "Request allowed! Attempt #". $this->session->tempdata($key) . " from IP: $ip_address";

		return $data;
    }

	public function get_device_info() {
		$this->load->library('user_agent');

        // $os = $this->agent->platform(); // Get operating system
		$user_agent = $this->input->user_agent(); // Get full user-agent string
		$os = $this->_detect_os($user_agent); // Call custom function to detect OS
        $browser = $this->agent->browser(); // Get browser name
        $device = ($this->agent->is_mobile()) ? "Mobile" : "Desktop"; // Detect device type
        $ip_address = $this->input->ip_address(); // Get user IP

        echo "User-Agent: $user_agent <br>";
        echo "Detected OS: $os <br>";
        echo "Browser: $browser <br>";
        echo "Device Type: $device <br>";
        echo "IP Address: $ip_address";
    }
	
	private function _get_device_info() {
		$this->load->library('user_agent');

        // $os = $this->agent->platform(); // Get operating system
		$user_agent 						= $this->input->user_agent(); // Get full user-agent string
		$data['user_agent'] 				= $user_agent;
		$data['detected_os'] 						= $this->_detect_os($user_agent); // Call custom function to detect OS
        $data['browser'] 					= $this->agent->browser(); // Get browser name
        $data['device_type'] 				= ($this->agent->is_mobile()) ? "Mobile" : "Desktop"; // Detect device type
        $data['ip_address'] 				= $this->input->ip_address(); // Get user IP

        return $data;
    }

	private function _detect_os($user_agent) {
        // Default OS from CI's platform() method
        $os = $this->agent->platform();

        // Check if User-Agent contains Windows 11-specific signatures
        if (strpos($user_agent, 'Windows NT 10.0') !== false) {
            if (strpos($user_agent, 'Win64') !== false || strpos($user_agent, 'x64') !== false) {
                $os = "Windows 11"; // More accurate assumption for newer Windows versions
            } else {
                $os = "Windows 10"; // Default to Windows 10 if not sure
            }
        }

        return $os;
    }

	function _response_msg($value_type, $category, $reference_no, $amount_product, $location){
		if($value_type == 1){ // PERCENTAGE
			$old_sms = 'Ang '.SYS_NAME.' mo ay valid ng '.$amount_product .' at valid sa '.$location.'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
			
			$sms = 'Ang '. $category . ' ay valid para sa '.$amount_product .' with '.$location.' scope. Maaari mo ng itransact sa POS VOUCHER MODULE ang approval code na <strong>' . $reference_no.'</strong>.';
			if($category == "CHOOKSIE QR PROMO EVOUCHER"){
				$sms = 'Ang '. $category . ' ay valid para sa '.$amount_product .' with '.$location.' scope. Ito ay may approval code na <strong>' . $reference_no.'</strong>.';
			}
		} elseif ($value_type == 2){
			$old_sms = 'Ang '.SYS_NAME.' mo ay valid worth P' . $amount_product . ' at valid sa '.$location.'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
			
			$sms = 'Ang '. $category . ' ay valid worth P' . $amount_product . ' at '.$location.' scope. Maari mo nang iinput sa POS VOUCHER MODULE ang approval code na <strong>' . $reference_no.'</strong>.';
			
		}
		return $sms;

	}

	function _invalid_response_msg($params){

		if($params['type'] == 'invalid_type'){
			$sms = 'Error, Invalid '.SEC_SYS_NAME.' Type. Please try again';
		}
		elseif($params['type'] == 'redemption_not_started'){
			$sms = 'Sorry, '.SEC_SYS_NAME.' redemption has not yet started. Redemption start on ' . $params['coupon_start'] . '.';
		}
		elseif($params['type'] == 'expired'){
			// $sms = 'Sorry '.SYS_NAME.' was already expired.';
			$sms = 'Sorry, Expired na ang '.SEC_SYS_NAME.' CODE.';
		}
		elseif($params['type'] == 'already_redeemed_old'){
			$suffix = $params['redeemer_number'] ? ' ng '.$params['redeemer_number'].'.' : '.';
			$sms = 'Sorry, Ang '.SEC_SYS_NAME.' CODE ay REDEEMED na noong '.$params['redeemer_ts_date'].' sa oras na '.$params['redeemer_ts_time'].$suffix;
		}
		elseif($params['type'] == 'already_redeemed_new'){
			// $sms = 'Sorry, Ang '.SEC_SYS_NAME.' CODE ay REDEEMED na ni '.$params['redeemer_staff_name'].' sa '.$params['redeemer_outlet_name'].' noong '.$params['redeemer_ts_date'].' sa oras na '.$params['redeemer_ts_time'].'.';
			$sms = 'Sorry, Ang '.SEC_SYS_NAME.' CODE ay REDEEMED na ni '.$params['redeemer_staff_name'].' sa '.$params['redeemer_outlet_name'].' noong '.$params['redeemer_ts_date'].', '.$params['redeemer_ts_time'].'.';
		}
		elseif($params['type'] == 'invalid_code'){
			$sms = 'Sorry, mali ang '.SEC_SYS_NAME.' CODE, i check ng mabuti ang '.SEC_SYS_NAME.' code at siguraduhing tama ang nai-type na code. Subukang i-redeem ulet.';
		}
		else {
			$sms = 'Sorry, Redemption failed for unknown reason.';
		}

		return $sms;
	}

    function _get_prod($coupon_id){
        
        $main_db = parent_db();
        $join_prod = array("$main_db.product_sale_tbl b" => 'a.prod_sale_id = b.prod_sale_id');
        $where_prod = array('coupon_id' => $coupon_id);
        $get_prod = $this->main->get_join('coupon_prod_sale_tbl a', $join_prod, FALSE, FALSE, FALSE, FALSE, $where_prod);

        $count_prod = count($get_prod);
        $checker_prod = 1;
        $prod = '';
        foreach($get_prod as $row_prod){
            if($checker_prod == 1){
                $prod .= $row_prod->prod_sale_name;
            }elseif($checker_prod == $count_prod){
                $prod .= ' & ' . $row_prod->prod_sale_name;
            }else{
                $prod .= ', ' . $row_prod->prod_sale_name;
            }
            $checker_prod++;
        }

        return $prod;
    }

    function _get_bc($coupon_id){
        $main_db = parent_db();
        $join_bc = array("$main_db.bc_tbl b" => 'a.bc_id = b.bc_id AND a.coupon_bc_status = 1 AND a.coupon_id = ' . $coupon_id);
        $get_bc = $this->main->get_join('coupon_bc_tbl a', $join_bc);
        $count_bc = count($get_bc);
        $checker_bc = 1;
        $bc = '';
        foreach($get_bc as $row_bc){
            if($checker_bc == 1){
                $bc .= $row_bc->bc_masking;
            }elseif($checker_bc == $count_bc){
                $bc .= ' & ' . $row_bc->bc_masking;
            }else{
                $bc .= ', ' . $row_bc->bc_masking;
            }
            $checker_bc++;
        }

        return $bc;
    }

	function _get_bc_codes($coupon_id){
		$delivery_db = $this->load->database('default', TRUE);
		$this->db = $delivery_db;

		$main_db = parent_db();
		$join_bc = array("$main_db.bc_tbl b" => 'a.bc_id = b.bc_id AND a.coupon_bc_status = 1 AND a.coupon_id = ' . $coupon_id);
		$get_bc = $this->main->get_join('coupon_bc_tbl a', $join_bc);
		
		$bc_codes = array();
		foreach($get_bc as $row_bc){
			
			$bc_codes[] = $row_bc->bc_code;
		}

		return $bc_codes;
		// return join(", ", $bc_codes);
	}

	function _verify_outlet($coupon_id, $outlet_ifs, $is_nationwide, $bc){
		if($is_nationwide != 1){
			// $bc_codes = join(", ", $this->_get_bc_codes($coupon_id));
			// $addtl_filter = ' AND b.branchCode IN (' . $bc_codes .')';
			$bc_codes = $this->_get_bc_codes($coupon_id);
		} else {
			// $addtl_filter = '';
			$bc_codes = array();
		}
		$sems_db = $this->load->database('sems', TRUE);
		$this->db = $sems_db;

		$select = 'a.outletIFS, a.outletDesc, a.outletCode, b.branchCode';
		$join_bc = array('branches b' => 'a.brnID = b.brnID AND a.status = 1 AND (a.outletIFS = "'.$outlet_ifs.'" OR a.outletCode = "'.$outlet_ifs.'")');
		$get_outlet = $this->main->get_join('outlets a', $join_bc, true, false, false, $select, false, false);

		$result = array();
		if(!empty($get_outlet)){
			$result['outletIFS'] = $get_outlet->outletIFS;
			$result['outletDesc'] = $get_outlet->outletDesc;
			$result['outletCode'] = $get_outlet->outletCode;
			$result['branchCode'] = $get_outlet->branchCode;

			$result['msg'] = '';
			$result['status'] = 'success';
			if(!empty($bc_codes)){
				if(in_array($get_outlet->branchCode, $bc_codes)){
					$result['msg'] = '';
					$result['status'] = 'success';
				} else {
					$result['msg'] = 'Ang tindahan ay hindi sakop ng assigned '.SEC_SYS_NAME.' scope. Maaari lamang itong i-claim sa mga tindahan na matatagpuan sa '.$bc.' area.';
					$result['status'] = 'error';
				}
			}
		} else {
			$result['msg'] = 'Ang store code ay wala sa record, suriing maiigi ang pagkaka type ng store code at i-validate ulet.';
			$result['status'] = 'error';
		}

		$default_db = $this->load->database('default', TRUE);
		$this->db = $default_db;

		return $result;
		// return $this->_get_bc_codes($coupon_id);
	}
	
	function _verify_crew($coupon_id, $crew_code, $outlet_ifs){
		// $bc_codes = $this->_get_bc_codes($coupon_id);
		// $bc_codes = join(", ", $this->_get_bc_codes($coupon_id));
		$sems_db = $this->load->database('sems', TRUE);
		$this->db = $sems_db;

		$select = 'a.crewCode, IF(a.mi IS NULL, CONCAT( a.surName, ", ", a.firstName ), CONCAT( a.surName, ", ", a.firstName, " ", a.mi )) AS crew_fullname';
		$join_bc = array(
			'crew_outlet b' => 'a.crewID = b.crewID AND b.statusID = 1 AND b.crewCode = "'.$crew_code.'"',
			// 'branches c' => 'b.brnID = c.brnID AND c.branchCode IN (' . $bc_codes .')',
			'outlets d' => 'b.outletID = d.outletID AND (d.outletIFS = "'.$outlet_ifs.'" OR d.outletCode = "'.$outlet_ifs.'")'
		);
		$get_record = $this->main->get_join('crew a', $join_bc, true, false, false, $select, false, false);

		// return $get_record;

		$result = array();
		if(!empty($get_record)){
			$result['crewCode'] = $get_record->crewCode;
			$result['crew_fullname'] = trim($get_record->crew_fullname);
			$result['status'] = 'success';
			$result['msg'] = '';
		} else {
			$result['status'] = 'error';
			$result['msg'] = 'Ang Crew Code ay wala sa record, suriing maiigi ang pagkaka type ng crew code at i-validate ulet.';
		}

		$default_db = $this->load->database('default', TRUE);
		$this->db = $default_db;

		return $result;
		// return $this->_get_bc_codes($coupon_id);
	}

    private function _store_failed_coupon_redeem_log($coupon_code)
    {
        $log_data = [
            'redeemed_coupon_log_code'   => $coupon_code,
            'redeem_type_id'             => 1,
            'redeemed_coupon_log_added'  => date_now(),
            'redeemed_coupon_log_status' => 2,
        ];
        $redeemed_coupon_result = $this->main->insert_data('redeemed_coupon_log_tbl', $log_data);
    }
    
    public function check_contact_prefix()
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $contact_number = trim(clean_data($this->input->post('contact_number')));
            $where  = [ 'contact_number_prefix' => substr($contact_number, 0, 4) ];
            $result = $this->main->check_data("{$parent_db}.contact_number_prefix_tbl", $where);
            $data   = [ 'result' => $result ];
            echo json_encode($data);
        }
    }

    public function redeem_logs(){
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];
        
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime($end_date . ' - 1 week'));
        $data['range_date'] = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));

        $data['title']   = 'Redeem Logs';

        $join = array(
            'redeem_type_tbl b' => 'a.redeem_type_id = b.redeem_type_id',
            'redeemed_coupon_status_tbl c' => 'a.redeemed_coupon_log_status = c.redeemed_coupon_status_id'
        );

        $where = 'DATE(a.redeemed_coupon_log_added) >=  "' . $start_date . '" AND DATE(a.redeemed_coupon_log_added) <= "' . $end_date . '"';

        $get_redeem = $this->main->get_join('redeemed_coupon_log_tbl a', $join, FALSE, 'a.redeemed_coupon_log_added DESC', FALSE, FALSE, $where);
		

        $data['tbl_logs'] = $this->_get_redeem_logs_tbl($get_redeem);
		$data['top_nav']     = $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content'] = $this->load->view('redeem/redeem_coupon_logs_content', $data, TRUE);
        $main_view = $this->_get_view_temp()->view;
        
		$this->load->view($main_view, $data);
    }

    public function get_redeem_logs_data(){
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $daterange  = explode(' - ', clean_data($this->input->post('date')));
            $start_date = date('y-m-d', strtotime($daterange[0]));
            $end_date   = date('y-m-d', strtotime($daterange[1]));

            $join = array(
                'redeem_type_tbl b' => 'a.redeem_type_id = b.redeem_type_id',
                'redeemed_coupon_status_tbl c' => 'a.redeemed_coupon_log_status = c.redeemed_coupon_status_id'
            );

            
			$where = 'DATE(a.redeemed_coupon_log_added) >=  "' . $start_date . '" AND DATE(a.redeemed_coupon_log_added) <= "' . $end_date . '"';

            $get_redeem = $this->main->get_join('redeemed_coupon_log_tbl a', $join, FALSE, 'a.redeemed_coupon_log_added DESC', FALSE, FALSE, $where, false);
			
            $data['tbl_logs'] = $this->_get_redeem_logs_tbl($get_redeem);
            $data['result'] = 1;

        }else{
            $data['result'] = 0;
            $data['msg'] = 'Error please try again!';
        }

        echo json_encode($data);
        exit;
    }

    public function _get_redeem_logs_tbl($get_redeem){
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

        $redeem_tbl = '';
        foreach($get_redeem as $row){
            $redeem_tbl .= '
                <tr>
                    <td>' . $row->redeemed_coupon_log_code . '</td>
                    <td>' . $row->redeemed_coupon_log_reference_code . '</td>
                    <td>' . $row->redeemed_coupon_log_contact_number . '</td>
                    <td>' . $row->outlet_ifs . '</td>
                    <td>' . $row->outlet_name . '</td>
                    <td>' . $row->staff_code . '</td>
                    <td>' . $row->staff_name . '</td>
                    <td>' . $row->redeemed_coupon_log_response . '</td>
                    <td>' . $row->added_info . '</td>
                    <td>' . $row->redeem_type_name . '</td>
                    <td>' . date('Y-m-d h:i:s A', strtotime($row->redeemed_coupon_log_added)) . '</td>
                    <td>' . $row->redeemed_coupon_status_name . '</td>
                </tr>
            ';
        }

        return $redeem_tbl;
    }

	public function download_redeemed_logs(){
		ini_set('max_execution_time', 0);
		ini_set('memory_limit','2048M');

		$info = $this->_require_login();
		$user_id = decode($info['user_id']);
		
		
		if($_SERVER['REQUEST_METHOD'] == 'GET'){
			$daterange  = explode(' - ', clean_data($this->input->get('date')));
            $start_date = date('Y-m-d', strtotime($daterange[0]));
            $end_date   = date('Y-m-d', strtotime($daterange[1]));
			$as_of = date('Y.m.d', strtotime($daterange[0])) .' - '.date('Y.m.d', strtotime($daterange[1]));;
			if(!empty($daterange)){
				
				$this->load->library('excel');
				$spreadsheet = $this->excel;

				$style_bold = array(
					'font' 	=> array(
							'bold' => true,
					)
				);

				$sheet1 = $spreadsheet->createSheet(0);

				$sheet1->getStyle("A1:Y1")->applyFromArray($style_bold);

				$sheet1->setCellValue("A1", ''.SYS_NAME.' Code')
					->setCellValue("B1", 'Approval Code')
					->setCellValue("C1", 'Originator')
					->setCellValue("D1", 'Store IFS')
					->setCellValue("E1", 'Store')
					->setCellValue("F1", 'Crew Code')
					->setCellValue("G1", 'Crew')
					->setCellValue("H1", 'Response')
					->setCellValue("I1", 'Added Info')
					->setCellValue("J1", 'Redemption Type')
					->setCellValue("K1", 'Timestamp')
					->setCellValue("L1", 'Status')
				;

				$join = array(
					'redeem_type_tbl b' => 'a.redeem_type_id = b.redeem_type_id',
					'redeemed_coupon_status_tbl c' => 'a.redeemed_coupon_log_status = c.redeemed_coupon_status_id'
				);
	
				$where = 'DATE(a.redeemed_coupon_log_added) >=  "' . $start_date . '" AND DATE(a.redeemed_coupon_log_added) <= "' . $end_date . '"';
	
				$get_redeem = $this->main->get_join('redeemed_coupon_log_tbl a', $join, FALSE, 'a.redeemed_coupon_log_added DESC', FALSE, FALSE, $where);

				$x = 2;
				foreach($get_redeem as $row){
					$sheet1
						->setCellValue('A' . $x, $row->redeemed_coupon_log_code)
						->setCellValue('B' . $x, $row->redeemed_coupon_log_reference_code)
						->setCellValue('C' . $x, $row->redeemed_coupon_log_contact_number)

						->setCellValue('D' . $x, $row->outlet_ifs)
						->setCellValue('E' . $x, $row->outlet_name)
						->setCellValue('F' . $x, $row->staff_code)
						->setCellValue('G' . $x, $row->staff_name)
						
						->setCellValue('H' . $x, $row->redeemed_coupon_log_response)
						->setCellValue('I' . $x, $row->added_info)
						->setCellValue('J' . $x, $row->redeem_type_name)
						->setCellValue('K' . $x, date('Y-m-d h:i:s A', strtotime($row->redeemed_coupon_log_added)))
						->setCellValue('L' . $x, $row->redeemed_coupon_status_name)
					;

					$x++;
				}

				$sheet1->getStyle('O1:K' . $x)->getNumberFormat()->setFormatCode('#,##0.00');

				$high = $sheet1->getHighestDataColumn();
				$cell_num = PHPExcel_Cell::columnIndexFromString($high);

				for($index=0 ; $index <= $cell_num ; $index++){
					$col = PHPExcel_Cell::stringFromColumnIndex($index);
					$sheet1->getColumnDimension($col)->setAutoSize(TRUE);
				}

				$sheet1->setTitle('Redeemed Logs');

				$filename = 'Redeemed Logs as of '.$as_of;

				// set right to left direction
				//		$spreadsheet->getActiveSheet()->setRightToLeft(true);

				// Set active sheet index to the first sheet, so Excel opens this as the first sheet
				$spreadsheet->setActiveSheetIndex(0);

				// Redirect output to a client’s web browser (Excel2007)
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				$today = date('m.d.Y');
				$random = generate_random(4);
				header('Content-Disposition: attachment;filename="'.$filename.' (' . $random . ' - ' . $today . ').xlsx"');
				header('Cache-Control: max-age=0');
				// If you're serving to IE 9, then the following may be needed
				header('Cache-Control: max-age=1');

				// If you're serving to IE over SSL, then the following may be needed
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
				header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
				header('Pragma: public'); // HTTP/1.0

				$writer = PHPExcel_IOFactory::createWriter($spreadsheet, 'Excel2007');
				$writer->save('php://output');
				exit;
			}
		} else {
			echo 'Error please try again!';
		}
	}
}
