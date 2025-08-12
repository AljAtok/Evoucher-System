<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Creator extends CI_Controller {

	public function __construct()
    {
    	parent::__construct();
    	$this->load->model('main_model', 'main');
        $GLOBALS['parent_db'] = parent_db();
	}

	public function index()
    {
		$info = $this->_require_login();
		// redirect('creator/product-coupon');
		redirect('dashboard');
	}

	public function _require_login()
    {
		$login = $this->session->userdata('evoucher-user');
		if(isset($login)){
			$user_type = decode($login['user_type_id']);
			if($login['user_reset'] == 0){
				if($user_type == "1"){
					redirect('admin');
                }elseif($user_type == "7"){
                    return $login;
                }elseif($user_type == "8"){
                    redirect('approver');
				}elseif($user_type == "12"){
                    redirect('first-approver');
				}elseif($user_type == "9" || $user_type == "2" || $user_type == "11"){
                    redirect('redeem');
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

    public function redeem_coupon()
    {
		show_404();
        $data['title']   = 'Redeem '.SEC_SYS_NAME.'';
		$data['top_nav']     = $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content'] = $this->load->view('creator/coupon/redeem_coupon_content', $data, TRUE);
        $this->load->view('creator/templates', $data);
    }

    public function standard_coupon()
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

        $coupon_join = [
            'coupon_value_type_tbl b'  => 'b.coupon_value_type_id = a.coupon_value_type_id AND a.coupon_type_id = 1',
            'coupon_holder_type_tbl c' => 'c.coupon_holder_type_id = a.coupon_holder_type_id',
        ];

		$join_salable = [
			"{$parent_db}.product_tbl b" => 'a.prod_id = b.prod_id',
			"{$parent_db}.unit_tbl c"    => 'a.unit_id = c.unit_id',
			"{$parent_db}.unit_tbl d"    => 'a.2nd_uom = d.unit_id'
        ];

        $coupon_select = "*,
        (SELECT GROUP_CONCAT(x.brand_name SEPARATOR ', ') FROM coupon_brand_tbl z JOIN {$parent_db}.brand_tbl x ON z.brand_id = x.brand_id WHERE z.coupon_id = a.coupon_id AND coupon_brand_status = 1) AS brands,
        IF(a.is_nationwide = 1, 
            'Nationwide', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'ORC',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";


        $user_id        = clean_data(decode($info['user_id']));
        $category_where = "a.coupon_cat_id IN (SELECT z.coupon_cat_id FROM user_access_tbl z WHERE z.user_id = {$user_id} AND user_access_status = 1)";

        $data['products']        = $this->main->get_join("{$parent_db}.product_sale_tbl a", $join_salable);
        $data['brand']           = $this->main->get_data("{$parent_db}.brand_tbl", ['brand_status' => 1]);
        $data['bc']              = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
        $data['category']        = $this->main->get_data('coupon_category_tbl a', 'coupon_cat_status = 1 AND ' . $category_where);
        $data['holder_type']     = $this->main->get_data('coupon_holder_type_tbl a', ['coupon_holder_type_status' => 1]);
        $data['coupon_type']     = $this->main->get_data('coupon_type_tbl', ['coupon_type_status' => 1]);
        $data['value_type']      = $this->main->get_data('coupon_value_type_tbl a', ['coupon_value_type_status' => 1]);
        $data['pending_coupon']  = $this->main->get_join('coupon_tbl a', $coupon_join, FALSE, 'coupon_added DESC', FALSE ,$coupon_select, 'a.coupon_status = 2 AND ' . $category_where);
        $data['approved_coupon'] = $this->main->get_join('coupon_tbl a', $coupon_join, FALSE, 'coupon_added DESC', FALSE ,$coupon_select , 'a.coupon_status = 1 AND ' . $category_where);
        $data['inactive_coupon'] = $this->main->get_join('coupon_tbl a', $coupon_join, FALSE, 'coupon_added DESC', FALSE ,$coupon_select , 'a.coupon_status = 0 AND ' . $category_where);
        $data['title']           = 'Standard '.SEC_SYS_NAME.'';
		$data['top_nav']     = $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content']         = $this->load->view('creator/coupon/standard_coupon_content', $data, TRUE);
        $this->load->view('creator/templates', $data);
    }

    public function product_coupon()
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

		$join_salable = [
			"{$parent_db}.product_tbl b" => 'a.prod_id = b.prod_id',
        ];

		$coupon_trans_select = '*, total_coupon_qty as `coupon_qty`';

        $join_coupon = array(
        	"{$parent_db}.user_tbl b" => 'a.user_id = b.user_id',
        	'coupon_category_tbl c'   => 'a.coupon_cat_id = c.coupon_cat_id',
        	'payment_types_tbl d, LEFT'   => 'a.payment_type_id = d.payment_type_id',
        	'customers_tbl e'   => 'a.customer_id = e.customer_id',
        );
        
        $user_id        = clean_data(decode($info['user_id']));
        $category_where = "a.coupon_cat_id IN (SELECT z.coupon_cat_id FROM user_access_tbl z WHERE z.user_id = {$user_id} AND user_access_status = 1)";

        $data['pending_coupon_trans']  = $this->main->get_join('coupon_transaction_header_tbl a', $join_coupon, FALSE, 'coupon_transaction_header_added DESC', FALSE, $coupon_trans_select, 'a.coupon_transaction_header_status = 2 AND ' . $category_where);
        $data['approved_coupon_trans'] = $this->main->get_join('coupon_transaction_header_tbl a', $join_coupon, FALSE, 'coupon_transaction_header_added DESC', FALSE, $coupon_trans_select, 'a.coupon_transaction_header_status = 1 AND ' . $category_where);
		$data['first_appr_coupon_trans'] = $this->main->get_join('coupon_transaction_header_tbl a', $join_coupon, FALSE, 'coupon_transaction_header_added DESC', FALSE, $coupon_trans_select, 'a.coupon_transaction_header_status = 4 AND ' . $category_where);
        $data['inactive_coupon_trans'] = $this->main->get_join('coupon_transaction_header_tbl a', $join_coupon, FALSE, 'coupon_transaction_header_added DESC', FALSE, $coupon_trans_select, 'a.coupon_transaction_header_status = 0 AND ' . $category_where);

        $data['products']    = $this->main->get_join("{$parent_db}.product_sale_tbl a", $join_salable);
        $data['brand']       = $this->main->get_data("{$parent_db}.brand_tbl", ['brand_status' => 1]);
        $data['bc']          = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
        $data['coupon_type'] = $this->main->get_data('coupon_type_tbl', ['coupon_type_status' => 1]);
        $data['value_type']  = $this->main->get_data('coupon_value_type_tbl a', ['coupon_value_type_status' => 1]);
        $data['category']    = $this->main->get_data('coupon_category_tbl a', 'coupon_cat_status = 1 AND ' . $category_where);
        $data['holder_type'] = $this->main->get_data('coupon_holder_type_tbl a', ['coupon_holder_type_status' => 1]);
		$data['scope_masking']          = $this->main->get_data("scope_masking_tbl", ['scope_masking_status' => 1]);
		$data['customer']          	= $this->main->get_data("customers_tbl", ['customer_status' => 1]);
        $data['title']       = 'Product '.SEC_SYS_NAME.'';

		$data['top_nav']     = $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content']     = $this->load->view('creator/coupon/product_coupon_content', $data, TRUE);
        $this->load->view('creator/templates', $data);
    }

    public function zip_coupon($trans_id)
    {
    	$info = $this->_require_login();
    	ini_set('max_execution_time', 0); 
        ini_set('memory_limit','2048M');
    	$trans_id = decode($trans_id);

    	$this->load->library('zip');

		$zip_path =  FCPATH . '/assets/coupons/zipped/';
		if (!is_dir($zip_path)) {
			mkdir($zip_path, 0777, true);
		}
        
    	$join_voucher = array(
    		'coupon_transaction_details_tbl b' => 'a.coupon_transaction_header_id = b.coupon_transaction_header_id AND b.coupon_transaction_details_status = 1 AND a.coupon_transaction_header_status = 1 AND a.coupon_transaction_header_id = ' . $trans_id,
    		'coupon_tbl c'                     => 'b.coupon_id = c.coupon_id AND c.coupon_status = 1'
    	);
    	$get_voucher = $this->main->get_join('coupon_transaction_header_tbl a', $join_voucher);


    	foreach($get_voucher as $row){
    		$file_name = $row->coupon_transaction_header_name;
    		$this->zip->read_file(FCPATH . '/' . $row->coupon_pdf_path);
    	}

    	// $this->zip->archive($zip_path . $file_name . '.zip');

		// array_map('unlink', glob("$zip_path/*.*"));
         
        $this->zip->download($file_name . '.zip');
    }

    private function _get_coupon(){
    	$counter = TRUE;
        while($counter){
            $coupon = generate_random_coupon(7);

            $check_coupon = $this->main->check_data('coupon_tbl', array('coupon_code' => $coupon));
            if($check_coupon == FALSE){
                $counter = FALSE;
            }
        }

        return $coupon;
    }

    public function store_standard_coupon()
    {
        $info      = $this->_require_login();
        $user_id   = decode($info['user_id']);
        $parent_db = $GLOBALS['parent_db'];

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            redirect($_SERVER['HTTP_REFERER']);
        }

        $rules = [
            [ 'field' => 'bc[]'        , 'label' => 'Business Center'     ,'rules' => 'required'                ],
            [ 'field' => 'brand[]'     , 'label' => 'Brand'               ,'rules' => 'required'                ],
            [ 'field' => 'name'        , 'label' => ''.SEC_SYS_NAME.' Name'         ,'rules' => 'required|max_length[70]' ],
            [ 'field' => 'code'        , 'label' => ''.SEC_SYS_NAME.' Code'         ,'rules' => 'required'                ],
            [ 'field' => 'amount'      , 'label' => ''.SEC_SYS_NAME.' amount'       ,'rules' => 'required|integer'        ],
            [ 'field' => 'qty'         , 'label' => ''.SEC_SYS_NAME.' Qty'          ,'rules' => 'required|integer'        ],
            [ 'field' => 'date_range'  , 'label' => ''.SEC_SYS_NAME.' Start & End' ,'rules' => 'required'                ],
            [ 'field' => 'category'    , 'label' => ''.SEC_SYS_NAME.' Category'     ,'rules' => 'required'                ],
            [ 'field' => 'value_type'  , 'label' => 'Value Type'          ,'rules' => 'required'                ],
            [ 'field' => 'holder_type' , 'label' => 'Holder Type'         ,'rules' => 'required'                ],
        ];

        $this->_run_form_validation($rules);
        $category = decode(clean_data($this->input->post('category')));

        if(in_array($category, gift_and_paid_category())){
            $this->_validate_attachment();
        }

        $additional_rules = [];
		if(in_array($category, paid_category())){
            $rules = [
                [
                    'field' => 'address',
                    'label' => 'Holder Address',
                    'rules' => 'required'
                ],
                [
                    'field' => 'voucher-value',
                    'label' => ''.SEC_SYS_NAME.' Paid Value',
                    'rules' => 'required'
                ],
                [
                    'field' => 'tin',
                    'label' => 'Holder TIN',
                    'rules' => 'required'
                ],
            ];
            array_push($additional_rules, $rules);
        }

        if ($this->input->post('holder_email') != '') {
            $email_rule = [
                    'field' => 'holder_email',
                    'label' => 'Holder Email',
                    'rules' => 'valid_email'
                ];
            array_push($additional_rules, $email_rule);
        }
        
        if (count($additional_rules) > 0) {
            $this->_run_form_validation($additional_rules);
        }
        
        $bc             = $this->input->post('bc');
        $brand          = $this->input->post('brand');
        $name           = clean_data($this->input->post('name'));
        $code           = clean_data($this->input->post('code'));
        $amount         = clean_data($this->input->post('amount'));
        $qty            = clean_data($this->input->post('qty'));
        $dates          = explode(' - ', clean_data($this->input->post('date_range')));
        $start          = date('Y-m-d', strtotime($dates[0]));
        $end            = date('Y-m-d', strtotime($dates[1]));
        $value_type     = decode(clean_data($this->input->post('value_type')));
        $holder_type    = decode(clean_data($this->input->post('holder_type')));
        $holder_name    = clean_data($this->input->post('holder_name'));
        $holder_email   = clean_data($this->input->post('holder_email'));
        $holder_contact = clean_data($this->input->post('holder_contact'));
        $voucher_value  = ($this->input->post('voucher-value') != NULL)? clean_data($this->input->post('voucher-value')) : 0;
        $holder_address = ($this->input->post('address') != NULL) ? clean_data($this->input->post('address')) : '';
        $holder_tin     = ($this->input->post('tin') != NULL) ? clean_data($this->input->post('tin')) : '';
        $payment_status = ($holder_type == 4) ? 0 : 1;
        $invoice_number = ($holder_type == 4) ? clean_data($this->input->post('invoice_num')) : '';
        $pdf_path       = '';

        if ($holder_type == 4) {
            if ($this->input->post('invoice_num') == NULL || empty($this->input->post('invoice_num'))) {
                $alert_message = $this->alert_template('Invoice Number is Required', FALSE);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }
        }

        $this->_validate_brand($brand);
        $this->_validate_bc($bc);
        $this->_validate_code($code);
        $this->_validate_category($category);
        $this->_validate_value_type($value_type);
        $this->_validate_holder_type($holder_type);
        
        $data = [
            'coupon_name'           => strtoupper(trim($name)),
            'coupon_code'           => $code,
            'coupon_amount'         => $amount,
            'coupon_value'          => $voucher_value,
            'coupon_qty'            => $qty,
            'coupon_use'            => 0,
            'coupon_value_type_id'  => $value_type,
            'coupon_type_id'        => 1,
            'coupon_cat_id'         => $category,
            'user_id'               => $user_id,
            'coupon_start'          => $start,
            'coupon_end'            => $end,
            'coupon_pdf_path'       => $pdf_path,
            'coupon_holder_type_id' => $holder_type,
            'coupon_holder_name'    => strtoupper(trim($holder_name)),
            'coupon_holder_email'   => $holder_email,
            'coupon_holder_contact' => $holder_contact,
            'coupon_holder_address' => strtoupper(trim($holder_address)),
            'coupon_holder_tin'     => $holder_tin,
            'coupon_added'          => date_now(),
            'coupon_status'         => 2,
            'is_nationwide'         => (in_array('nationwide', $bc)) ? 1 : 0,
            'invoice_number'        => $invoice_number,
            'payment_status'        => $payment_status
        ];

        $this->db->trans_start();
        $result = $this->main->insert_data('coupon_tbl', $data, TRUE);

        if ($result['result']) {

            if (isset($_FILES['attachment'])) {
                $this->_upload_coupon_attachment($result['id'], 1);
            }

            $this->_store_coupon_action_log(1, $result['id']);
            foreach ($brand as $brand_row) {
                $clean_brand_row = decode(clean_data($brand_row));
                $coupon_brand_data  = [
                    'brand_id'            => $clean_brand_row,
                    'coupon_id'           => $result['id'],
                    'coupon_brand_status' => 1
                ];
                $this->main->insert_data('coupon_brand_tbl', $coupon_brand_data);
            }

            if (in_array('nationwide', $bc)) {
                $bc_list = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
                foreach ($bc_list as $bc_row) {
                    $coupon_bc_data = [
                        'bc_id'            => $bc_row->bc_id,
                        'coupon_id'        => $result['id'],
                        'coupon_bc_status' => 1
                    ];
                    $this->main->insert_data('coupon_bc_tbl', $coupon_bc_data);
                }
            } else {
                foreach ($bc as $bc_row) {
                    $clean_bc_row = decode(clean_data($bc_row));
                    $coupon_bc_data = [
                        'bc_id'            => $clean_bc_row,
                        'coupon_id'        => $result['id'],
                        'coupon_bc_status' => 1
                    ];
                    $this->main->insert_data('coupon_bc_tbl', $coupon_bc_data);
                }
            }
        }


        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $message       = 'Data Save Failed';
            $alert_message = $this->alert_template($message, FALSE);
        }else{
            $this->db->trans_commit();

            $pdf_path = $this->_generate_coupon_pdf($result['id']);
            $set      = [ 'coupon_pdf_path' => $pdf_path ];
            $where    = [ 'coupon_id' => $result['id'] ];
            $this->main->update_data('coupon_tbl', $set, $where);
            $this->_send_approver_notification($name, $category, 'Standard '.SEC_SYS_NAME.'');

            $message       = 'Data Saved Successfully';
            $alert_message = $this->alert_template($message, TRUE);

            // if ($this->input->post('email_notif') != FALSE && $holder_email != '') {
            //     $this->_email_coupon($result['id']);
            // }

            // if ($this->input->post('sms_notif') != FALSE && $holder_contact != '') {
            //     $this->_send_coupon_sms($result['id']);
            // }
        }
        $this->session->set_flashdata('message', $alert_message);
        redirect($_SERVER['HTTP_REFERER']);

    }

    private function _store_coupon_trans_header($coupon_name, $category, $start, $end, $payment_status, $invoice_number, $product_coupon_qty=0, $voucher_value=0, $for_printing=0, $scope_masking="", $display_exp=0, $for_image_conv=0, $payment_terms=0, $payment_type_id=1, $customer_id=0)
    {
    	$info    = $this->_require_login();
    	$user_id = decode($info['user_id']);
		$total_coupon_value = $product_coupon_qty * $voucher_value;
        $data = [
        	'coupon_cat_id'                    			=> $category,
        	'user_id'                          			=> $user_id,
        	'invoice_number'                   			=> $invoice_number,
        	'payment_status'                   			=> $payment_status,
			'total_coupon_value'               			=> $total_coupon_value,
        	'total_coupon_qty'               			=> $product_coupon_qty,
        	'coupon_transaction_header_name'   			=> strtoupper(trim($coupon_name)),
			'coupon_for_printing'  						=> $for_printing,
			'coupon_for_image_conv'  					=> $for_image_conv,
			'coupon_display_exp'  						=> $display_exp,
			'coupon_scope_masking'  					=> strtoupper(trim($scope_masking)),
        	'coupon_transaction_header_start'  			=> $start,
        	'coupon_transaction_header_end'    			=> $end,
			'payment_type_id'    						=> $payment_type_id,
        	'payment_terms'    							=> $payment_terms,
        	'customer_id'    							=> $customer_id,
        	'coupon_transaction_header_added'  			=> date_now(),
        	'coupon_pdf_archived'  						=> 0,
        	'coupon_transaction_header_status' 			=> 2
        ];
        return $this->main->insert_data('coupon_transaction_header_tbl', $data, TRUE);
    }


    private function _store_coupon_trans_details($header_id, $coupon_id)
    {
        $data = [
            'coupon_transaction_header_id'      => $header_id,
            'coupon_id'                         => $coupon_id,
            'coupon_transaction_details_added'  => date_now(),
            'coupon_transaction_details_status' => 1
        ];
        $result = $this->main->insert_data('coupon_transaction_details_tbl', $data, TRUE);
    }


    public function store_product_coupon()
    {
        $info      = $this->_require_login();
        $user_id   = decode($info['user_id']);
        $parent_db = $GLOBALS['parent_db'];

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            redirect($_SERVER['HTTP_REFERER']);
        }

        $rules = [
            [ 'field' => 'bc[]'          		, 'label' => 'Business Center'     		,'rules' => 'required'                ],
            [ 'field' => 'brand[]'       		, 'label' => 'Brand'               		,'rules' => 'required'                ],
            [ 'field' => 'product[]'     		, 'label' => 'Product'             		,'rules' => 'required'                ],
            [ 'field' => 'name'          		, 'label' => ''.SEC_SYS_NAME.' Name'         		,'rules' => 'required|max_length[70]' ],
            [ 'field' => 'amount'        		, 'label' => ''.SEC_SYS_NAME.' amount'       		,'rules' => 'required|integer'        ],
            [ 'field' => 'voucher-value' 		, 'label' => SEC_SYS_NAME.' amount'    		,'rules' => 'integer'                 ],
            [ 'field' => 'date_range'    		, 'label' => ''.SEC_SYS_NAME.' Start & End' 		,'rules' => 'required'                ],
            [ 'field' => 'category'      		, 'label' => ''.SEC_SYS_NAME.' Category'     		,'rules' => 'required'                ],
            [ 'field' => 'value_type'    		, 'label' => 'Value Type'          		,'rules' => 'required'                ],
            [ 'field' => 'holder_type'   		, 'label' => 'Holder Type'         		,'rules' => 'required'                ],
			[ 'field' => 'total-voucher-value' 	, 'label' => SEC_SYS_NAME.' total amount'   ,'rules' => 'integer'                 ],
			[ 'field' => 'voucher-regular-value' 		, 'label' => SEC_SYS_NAME.' regular amount'    		,'rules' => 'integer'                 ],
			[ 'field' => 'company_id'       	, 'label' => 'Requestor\'s Company'               		,'rules' => 'required'                ],
			[ 'field' => 'customer_id'       	, 'label' => 'Customer'               		,'rules' => 'required'                ],
        ];

        $this->_run_form_validation($rules);

        $category    = decode(clean_data($this->input->post('category')));
        $holder_type = decode(clean_data($this->input->post('holder_type')));
		$company_id  = decode(clean_data($this->input->post('company_id')));

		if(in_array($category, gift_and_paid_category())){
			$this->_validate_attachment();
            if ($holder_type != '1') {
            }
        }

		if($category == '4'){
			if($holder_type == '1'){
				$this->_validate_attachment();
			} else {
				$message       = 'Meal Evoucher is for Employee only';
            	$alert_message = $this->alert_template($message, FALSE);
				$this->session->set_flashdata('message', $alert_message);
        		redirect($_SERVER['HTTP_REFERER']);
			}
		}

        $additional_rules = [];
		if(in_array($category, paid_category())){
            $rules = [
                [ 'field' => 'address'      		, 'label' => 'Holder Address'    , 'rules' => 'required'],
				[ 'field' => 'voucher-regular-value'		, 'label' => ''.SEC_SYS_NAME.' Regular Value', 'rules' => 'required'],
                [ 'field' => 'voucher-value'		, 'label' => ''.SEC_SYS_NAME.' Paid Value', 'rules' => 'required'],
                [ 'field' => 'tin'          		, 'label' => 'Holder TIN'        , 'rules' => 'required'],
                [ 'field' => 'total-voucher-value'	, 'label' => ''.SEC_SYS_NAME.' Paid Value', 'rules' => 'required'],
            ];
            array_push($additional_rules, $rules);
        }

        if ($this->input->post('holder_email') != '') {
            $rule = [ 'field' => 'holder_email', 'label' => 'Holder Email', 'rules' => 'valid_email'];
            array_push($additional_rules, $rule);
        }

        if (count($additional_rules) > 0) {
            $this->_run_form_validation($additional_rules);
        }

        $product_coupon_qty = (int) clean_data($this->input->post('product_coupon_qty'));
        $brand              = $this->input->post('brand');
        $prod_sale          = $this->input->post('product');
        $bc                 = $this->input->post('bc');
        $name               = clean_data($this->input->post('name'));
		$for_printing       = clean_data($this->input->post('for_printing'));
		$for_image_conv     = clean_data($this->input->post('for_image_conv'));
        $scope_masking      = clean_data($this->input->post('scope_masking'));
		$display_exp      	= clean_data($this->input->post('display_exp'));
		$amount             = clean_data($this->input->post('amount'));
        $dates              = explode(' - ', clean_data($this->input->post('date_range')));
        $start              = date('Y-m-d', strtotime($dates[0]));
        $end                = date('Y-m-d', strtotime($dates[1]));
        $value_type         = decode(clean_data($this->input->post('value_type')));
        $holder_name        = clean_data($this->input->post('holder_name'));
        $holder_email       = clean_data($this->input->post('holder_email'));
        $holder_contact     = clean_data($this->input->post('holder_contact'));
        $voucher_value      = ($this->input->post('voucher-value') != NULL)? clean_data($this->input->post('voucher-value')) : 0;
        $holder_address     = ($this->input->post('address') != NULL) ? clean_data($this->input->post('address')) : '';
        $holder_tin         = ($this->input->post('tin') != NULL) ? clean_data($this->input->post('tin')) : '';
		$payment_terms      = ($this->input->post('payment_terms') != NULL) ? clean_data($this->input->post('payment_terms')) : 0;
        $payment_type_id    = ($this->input->post('payment_type_id') != NULL) ? clean_data(decode($this->input->post('payment_type_id'))) : 1;
		$customer_id    	= ($this->input->post('customer_id') != NULL) ? clean_data(decode($this->input->post('customer_id'))) : NULL;
        // $payment_status     = ($holder_type == 4) ? 0 : 1;
		$payment_status     = ($payment_type_id == 4) ? 0 : 1;  //* UNPAID WHEN CREDIT PAYMENT TYPE
        $invoice_number     = ($holder_type == 4) ? clean_data($this->input->post('invoice_num')) : '';
		$voucher_regular_value      = ($this->input->post('voucher-regular-value') != NULL)? clean_data($this->input->post('voucher-regular-value')) : 0;

        if ($holder_type == 4) {
            if ($this->input->post('invoice_num') == NULL || empty($this->input->post('invoice_num'))) {
                $alert_message = $this->alert_template('Invoice Number is Required', FALSE);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }
        }

		if(strlen($scope_masking) > 20){
			$alert_message = $this->alert_template('Scope Masking should not be greater than 20 characters', FALSE);
			$this->session->set_flashdata('message', $alert_message);
			redirect($_SERVER['HTTP_REFERER']);
		}

		$customer_name = "";
		if(!is_numeric($customer_id)){
			$customer_name = clean_data(trim($this->input->post('customer_id')));
		}
		$customer_id = $this->_validate_customer($customer_id, $customer_name);

        $this->_validate_prod_sale($prod_sale);
        $this->_validate_brand($brand);
        $this->_validate_bc($bc);
        $this->_validate_category($category);
        $this->_validate_value_type($value_type);
        $this->_validate_holder_type($holder_type);
        $this->_validate_company($company_id);
        $this->_validate_payment_type($payment_type_id);

        $this->db->trans_start();
        $trans_result = $this->_store_coupon_trans_header($name, $category, $start, $end, $payment_status, $invoice_number, $product_coupon_qty, $voucher_value, $for_printing, $scope_masking, $display_exp, $for_image_conv, $payment_terms, $payment_type_id, $customer_id);
        if ($trans_result) {
            $this->_store_transaction_action_log(1, $trans_result['id']);
            if (isset($_FILES['attachment']) && $_FILES['attachment']['name'][0] != '') {
                $this->_upload_transaction_attachment($trans_result['id'], 1);
            }
        }

        $is_nationwide = (in_array('nationwide', $bc)) ? 1 : 0;
        $is_orc        = '';
        if (in_array('all', $prod_sale)) {
            $is_orc = 2;
        } else if (in_array('orc', $prod_sale)) {
            $is_orc = 1;
        } else {
            $is_orc = 0;
        }

        $name           = strtoupper(trim($name));
        $holder_name    = strtoupper(trim($holder_name));
        $holder_address = strtoupper(trim($holder_address));

        for ($i = 1; $i <= $product_coupon_qty; $i++) {
            $code = $this->_get_coupon();
            $data = [
                'coupon_name'           => $name,
                'coupon_code'           => $code,
                'coupon_amount'         => $amount,
                'coupon_value'          => $voucher_value,
				'coupon_regular_value'  => $voucher_regular_value,
                'coupon_qty'            => 1,
                'coupon_use'            => 0,
                'coupon_value_type_id'  => $value_type,
                'coupon_type_id'        => 2,
                'coupon_cat_id'         => $category,
                'user_id'               => $user_id,
                'coupon_start'          => $start,
                'coupon_end'            => $end,
                'coupon_holder_name'    => $holder_name,
                'coupon_holder_type_id' => $holder_type,
                'coupon_holder_email'   => $holder_email,
                'coupon_holder_contact' => $holder_contact,
                'coupon_holder_address' => $holder_address,
                'coupon_holder_tin'     => $holder_tin,
                'coupon_added'          => date_now(),
                'coupon_status'         => 2,
                'is_nationwide'         => $is_nationwide,
                'is_orc'                => $is_orc,
                'invoice_number'        => $invoice_number,
                'payment_status'        => $payment_status,
				'company_id'        	=> $company_id,
				'customer_id'        	=> $customer_id,
            ];


            $coupon_result = $this->main->insert_data('coupon_tbl', $data, TRUE);
            
            if ($coupon_result['result']) {


                $this->_store_coupon_action_log(1, $coupon_result['id']);

                foreach ($brand as $brand_row) {
                    $clean_brand_row = decode(clean_data($brand_row));
                    $coupon_brand_data  = [
                        'brand_id'            => $clean_brand_row,
                        'coupon_id'           => $coupon_result['id'],
                        'coupon_brand_status' => 1
                    ];
                    $this->main->insert_data('coupon_brand_tbl', $coupon_brand_data);
                }
                
                if (in_array('nationwide', $bc)) {
                    $bc_list = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
                    foreach ($bc_list as $bc_row) {
                        $coupon_bc_data = [
                            'bc_id'            => $bc_row->bc_id,
                            'coupon_id'        => $coupon_result['id'],
                            'coupon_bc_status' => 1
                        ];
                        $this->main->insert_data('coupon_bc_tbl', $coupon_bc_data);
                    }
                } else {
                    foreach ($bc as $bc_row) {
                        $clean_bc_row = decode(clean_data($bc_row));
                        $coupon_bc_data = [
                            'bc_id'            => $clean_bc_row,
                            'coupon_id'        => $coupon_result['id'],
                            'coupon_bc_status' => 1
                        ];
                        $this->main->insert_data('coupon_bc_tbl', $coupon_bc_data);
                    }
                }

                $orc_list = [];
                if (in_array('orc', $prod_sale)) {
                    $orcs = $this->main->get_data("{$parent_db}.orc_list_tbl", ['orc_list_status' => 1]);
                    $orc_list = array_column($orcs, 'prod_sale_id');
                    foreach ($orcs as $row) {
                        $prod_sale_data = [
                            'prod_sale_id'            => $row->prod_sale_id,
                            'coupon_id'               => $coupon_result['id'],
                            'coupon_prod_sale_status' => 1
                        ];
                        $this->main->insert_data('coupon_prod_sale_tbl', $prod_sale_data);
                    }
                }

                $orc_list_count = count($orc_list);
                foreach ($prod_sale as $row) {
                    if ($row != 'orc' && $row != 'all') {
                        $clean_prod_row = decode(clean_data($row));
                        if ($orc_list_count > 0) {
                            if (in_array($clean_prod_row, $orc_list)) {
                                continue;
                            }
                        }

                        $prod_sale_data = [
                            'prod_sale_id'            => $clean_prod_row,
                            'coupon_id'               => $coupon_result['id'],
                            'coupon_prod_sale_status' => 1
                        ];
                        $this->main->insert_data('coupon_prod_sale_tbl', $prod_sale_data);
                    }
                }

                $pdf_path = $this->_generate_coupon_pdf($coupon_result['id'], $scope_masking, $display_exp);
                $set      = [ 'coupon_pdf_path' => $pdf_path ];
                $where    = [ 'coupon_id' => $coupon_result['id'] ];
                $this->main->update_data('coupon_tbl', $set, $where);
            }

            if ($trans_result['result'] && $coupon_result['result']) {
                $this->_store_coupon_trans_details($trans_result['id'], $coupon_result['id']);

                // if ($this->input->post('email_notif') != FALSE) {
                //     $this->_email_transaction_coupon($trans_result['id']);
                // }

                // if ($this->input->post('sms_notif') != FALSE) {
                //     $this->_send_coupon_sms($coupon_result['id']);
                // }
            }
        }

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $message       = 'Data Save Failed';
            $alert_message = $this->alert_template($message, FALSE);
        }else{
            $this->db->trans_commit();

            $check_category = $this->main->check_data('coupon_category_tbl', array('coupon_cat_id' => $category, 'coupon_cat_status' => 1), TRUE);

            $category_name = '';
            if($check_category['result'] == TRUE){
                $category_name = $check_category['info']->coupon_cat_name;
            }

            $this->_send_approver_notification($name, $category, $category_name, 12);
            $message       = 'Data Save Success. If created coupons Modal doesn\'t pop up <a href="#success-product-coupon-details" data-toggle="modal">Click Here!</a>';
            $alert_message = $this->alert_template($message, TRUE);
            $html = $this->_success_coupon_trans_details($trans_result['id']);
            $this->session->set_flashdata('html', $html);
        }
        $this->session->set_flashdata('message', $alert_message);
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function update_standard_coupon()
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            redirect($_SERVER['HTTP_REFERER']);
        }

        $rules = [
            [ 'field' => 'id'          , 'label' => ''.SEC_SYS_NAME.' ID'           , 'rules' => 'required'                ],
        ];
        $this->_run_form_validation($rules);

        $coupon_id    = decode(clean_data($this->input->post('id')));
        $check_coupon = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
        if ($check_coupon['result'] != TRUE) {
            $this->session->set_flashdata('message', 'Invalid '.SEC_SYS_NAME.' ID');
            redirect($_SERVER['HTTP_REFERER']);
        }

        $link_hash = '';
        if ($check_coupon['info']->coupon_status == 1) {
            $link_hash = '#nav-approved';
        } elseif ($check_coupon['info']->coupon_status == 2) {
            $link_hash = '#nav-pending';
        } elseif ($check_coupon['info']->coupon_status == 0) {
            $link_hash = '#nav-inactive';
        }

        $rules = [
            [ 'field' => 'date_range'  , 'label' => ''.SEC_SYS_NAME.' Start & End' , 'rules' => 'required'                ],
        ];

        $this->_run_form_validation($rules);
        $category = decode(clean_data($this->input->post('category')));

		if(in_array($category, gift_and_paid_category())){
            $attachment_count_where = ['coupon_attachment_status' => 1, 'coupon_id' => $coupon_id];
            $attachment_count       = $this->main->get_count('coupon_attachment_tbl', $attachment_count_where);
            if ($attachment_count < 1) {
                $alert_message = $this->alert_template('Attachment Is Required', FALSE);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }
        }

        $dates    = explode(' - ', clean_data($this->input->post('date_range')));
        $start    = date('Y-m-d', strtotime($dates[0]));
        $end      = date('Y-m-d', strtotime($dates[1]));
        $pdf_path = '';

        $set = [
            'coupon_start' => $start,
            'coupon_end'   => $end,
        ];

        $where = ['coupon_id' => $coupon_id]; 
        $this->db->trans_start();
        $result = $this->main->update_data('coupon_tbl', $set, $where);
        
        if ($result) {
            if (isset($_FILES['attachment']) && $_FILES['attachment']['name'][0] != '') {
                $attachment = $this->_upload_coupon_attachment($coupon_id, 1);
            }
            $this->_store_coupon_action_log(2, $coupon_id);
        }

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $message       = 'Data Save Failed';
            $alert_message = $this->alert_template($message, FALSE);
        }else{

            $pdf_path = $this->_generate_coupon_pdf($coupon_id);
            $set      = [ 'coupon_pdf_path' => $pdf_path ];
            $where    = [ 'coupon_id' => $coupon_id ];
            $this->main->update_data('coupon_tbl', $set, $where);

            $this->db->trans_commit();
            $message       = 'Data Saved Successfully';
            $alert_message = $this->alert_template($message, TRUE);
        }
        $this->session->set_flashdata('message', $alert_message);
        redirect($_SERVER['HTTP_REFERER'] . $link_hash);
    }

    public function update_product_coupon()
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

        if($_SERVER['REQUEST_METHOD'] != 'POST') {
            redirect($_SERVER['HTTP_REFERER']);
        }

        $rules = [
            [ 'field' => 'id'          , 'label' => ''.SEC_SYS_NAME.' ID'           , 'rules' => 'required'                ],
            [ 'field' => 'date_range'  , 'label' => ''.SEC_SYS_NAME.' Start & End' , 'rules' => 'required'                ],
        ];

        $this->_run_form_validation($rules);

        $coupon_id    = decode(clean_data($this->input->post('id')));
        $check_coupon = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
        if ($check_coupon['result'] != TRUE) {
            $this->session->set_flashdata('message', 'Invalid '.SEC_SYS_NAME.' ID');
            redirect($_SERVER['HTTP_REFERER']);
        }
        
        $link_hash    = '';
        if ($check_coupon['info']->coupon_status == 1) {
            $link_hash = '#nav-approved';
        } elseif ($check_coupon['info']->coupon_status == 2) {
            $link_hash = '#nav-pending';
        } elseif ($check_coupon['info']->coupon_status == 0) {
            $link_hash = '#nav-inactive';
        }
        
        $dates      = explode(' - ', clean_data($this->input->post('date_range')));
        $start      = date('Y-m-d', strtotime($dates[0]));
        $end        = date('Y-m-d', strtotime($dates[1]));

		$category = $check_coupon['info']->coupon_cat_id;
		if(in_array($category, gift_and_paid_category())){
            if ($check_coupon['info']->coupon_attachment == '') {
                $alert_message = $this->alert_template('Attachment Is Required', FALSE);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }
        }

        $set = [
            'coupon_start' => $start,
            'coupon_end'   => $end,
        ];

        $where = ['coupon_id' => $coupon_id]; 
        $this->db->trans_start();
        $result = $this->main->update_data('coupon_tbl', $set, $where);

        if ($result) {
            $this->_store_coupon_action_log(2, $coupon_id);

            $pdf_path = $this->_generate_coupon_pdf($coupon_id);
            $set      = [ 'coupon_pdf_path' => $pdf_path ];
            $where    = [ 'coupon_id' => $coupon_id ];
            $this->main->update_data('coupon_tbl', $set, $where);
        }

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $message       = 'Data Save Failed';
            $alert_message = $this->alert_template($message, FALSE);
        }else{

            $this->db->trans_commit();
            $message       = 'Data Saved Successfully';
            $alert_message = $this->alert_template($message, TRUE);
        }
        $this->session->set_flashdata('message', $alert_message);
        redirect($_SERVER['HTTP_REFERER'] . $link_hash);
    }

    public function update_transaction_coupon()
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

        if($_SERVER['REQUEST_METHOD'] != 'POST') {
            redirect($_SERVER['HTTP_REFERER']);
        }

        $rules = [
            [ 'field' => 'id', 'label' => ''.SEC_SYS_NAME.' ID', 'rules' => 'required' ],
        ];
        $this->_run_form_validation($rules);

        
        $transaction_id    = decode(clean_data($this->input->post('id')));
		$payment_terms      = ($this->input->post('payment_terms') != NULL) ? clean_data($this->input->post('payment_terms')) : 0;
        $payment_type_id    = ($this->input->post('payment_type_id') != NULL) ? clean_data(decode($this->input->post('payment_type_id'))) : 1;
		$payment_status     = ($payment_type_id == 4) ? 0 : 1; //* UNPAID WHEN CREDIT PAYMENT TYPE
		$customer_id    	= ($this->input->post('upd_customer_id') != NULL) ? clean_data(decode($this->input->post('upd_customer_id'))) : NULL;
		$customer_name = "";
		if(!is_numeric($customer_id)){
			$customer_name = clean_data(trim($this->input->post('customer_id')));
		}
		$customer_id = $this->_validate_customer($customer_id, $customer_name);

        $check_transaction = $this->main->check_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $transaction_id], TRUE);
        if ($check_transaction['result'] != TRUE) {
            $alert_message = $this->alert_template('Invalid '.SEC_SYS_NAME.' ID', FALSE);
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER']);
        }
        
        $link_hash = '';
        if ($check_transaction['info']->coupon_transaction_header_status == 1) {
            $link_hash = '#nav-approved';
        } elseif ($check_transaction['info']->coupon_transaction_header_status == 2) {
            $link_hash = '#nav-pending';
        } elseif ($check_transaction['info']->coupon_transaction_header_status == 0) {
            $link_hash = '#nav-inactive';
        }

        $rules = [
            [ 'field' => 'date_range'  , 'label' => ''.SEC_SYS_NAME.' Start & End' , 'rules' => 'required'                ],
        ];

        $this->_run_form_validation($rules);
		$category          = $check_transaction['info']->coupon_cat_id;
        $for_printing    	= clean_data(decode($this->input->post('for_printing')));
        $for_image_conv    	= clean_data(decode($this->input->post('for_image_conv')));
        $dates          	= explode(' - ', clean_data($this->input->post('date_range')));
        $start          	= date('Y-m-d', strtotime($dates[0]));
        $end            	= date('Y-m-d', strtotime($dates[1]));

		if(in_array($category, gift_and_paid_category())){
            $attachment_count_where = ['coupon_transaction_header_attachment_status' => 1, 'coupon_transaction_header_id' => $transaction_id];
            $attachment_count       = $this->main->get_count('coupon_transaction_header_attachment_tbl', $attachment_count_where);
            if ($attachment_count < 1) {
                $alert_message = $this->alert_template('Attachment Is Required', FALSE);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }
        }

        $this->db->trans_start();

        $set = [
            'coupon_transaction_header_start'  	=> $start,
            'coupon_transaction_header_end'    	=> $end,
			'coupon_for_printing'    			=> $for_printing,
            'coupon_for_image_conv'    			=> $for_image_conv,
            'payment_type_id'    				=> $payment_type_id,
            'payment_terms'    					=> $payment_terms,
			'payment_status'    				=> $payment_status,
            'customer_id'    					=> $customer_id,
			'coupon_pdf_archived'    			=> 0
        ];

        $where = ['coupon_transaction_header_id' => $transaction_id];
        $trans_result  = $this->main->update_data('coupon_transaction_header_tbl', $set, $where);
        if ($trans_result) {
            $this->_store_transaction_action_log(2, $transaction_id);

            if (isset($_FILES['attachment']) && $_FILES['attachment']['name'][0] != '') {
                $this->_upload_transaction_attachment($transaction_id, 1);
            }

            $join   = [ 'coupon_tbl b' => "b.coupon_id = a.coupon_id AND a.coupon_transaction_header_id = {$transaction_id}"];
            $coupons = $this->main->get_join('coupon_transaction_details_tbl a', $join);
            foreach ($coupons as $row) {
                $coupon_id = $row->coupon_id;
                $set = [
                    'coupon_start'          => $start,
                    'coupon_end'            => $end,
                    'payment_status'            => $payment_status,
					'customer_id'    			=> $customer_id,
                ];

                $where = ['coupon_id' => $coupon_id]; 
                $result = $this->main->update_data('coupon_tbl', $set, $where);
                if ($result) {
                    $this->_store_coupon_action_log(2, $coupon_id);
                    $pdf_path = $this->_generate_coupon_pdf($coupon_id);
                    $set      = [ 'coupon_pdf_path' => $pdf_path ];
                    $where    = [ 'coupon_id' => $coupon_id ];
                    $this->main->update_data('coupon_tbl', $set, $where);
                }
            }
        }

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $message       = 'Data Save Failed';
            $alert_message = $this->alert_template($message, FALSE);
        }else{

            $this->db->trans_commit();
            $message       = 'Data Saved Successfully';
            $alert_message = $this->alert_template($message, TRUE);
        }
        $this->session->set_flashdata('message', $alert_message);
        redirect($_SERVER['HTTP_REFERER'] . $link_hash);
    }

	public function activate_coupon(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_id = clean_data(decode($this->input->post('id')));

            if (empty($coupon_id)) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->coupon_status == 1) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' is already Activated', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

			$set    = ['coupon_status' => 1];
			$where  = ['coupon_id' => $coupon_id];
			$result = $this->main->update_data('coupon_tbl', $set, $where);
			$msg    = ($result == TRUE) ? '<div class="alert alert-success">'.SEC_SYS_NAME.' successfully Activated.</div>' : '<div class="alert alert-danger">Error please try again!</div>';
            if ($result) {
                $this->_store_coupon_action_log(3, $coupon_id);
            }
            $this->session->set_flashdata('message', $msg);
            redirect($_SERVER['HTTP_REFERER'].'#nav-approved');
		}else{
			redirect('creator');
		}
    }

	public function deactivate_coupon(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){

            $coupon_id = clean_data(decode($this->input->post('id')));

            if (empty($coupon_id)) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->coupon_status == 0) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' is already Deactivated', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

			$set         = ['coupon_status' => 0];
			$where       = ['coupon_id' => $coupon_id];
			$result      = $this->main->update_data('coupon_tbl', $set, $where);
			$msg         = ($result == TRUE) ? '<div class="alert alert-success">'.SEC_SYS_NAME.' successfully deactivated.</div>' : '<div class="alert alert-danger">Error please try again!</div>';
            if ($result) {
                $this->_store_coupon_action_log(4, $coupon_id);
            }
            $this->session->set_flashdata('message', $msg);
            redirect($_SERVER['HTTP_REFERER'].'#nav-inactive');
		}else{
			redirect('creator');
		}
    }

    public function modal_standard_coupon($id)
    {
        $info      = $this->_require_login();
        $id        = clean_data(decode($id));
        $parent_db = $GLOBALS['parent_db'];
        $coupon_join = [
            'coupon_value_type_tbl b'  => 'b.coupon_value_type_id = a.coupon_value_type_id AND a.coupon_id = ' . $id,
            'coupon_holder_type_tbl c' => 'c.coupon_holder_type_id = a.coupon_holder_type_id',
            'coupon_category_tbl d'    => 'd.coupon_cat_id = a.coupon_cat_id',
        ];

        $coupon_select = "*,
        (SELECT GROUP_CONCAT(x.brand_name SEPARATOR ', ') FROM coupon_brand_tbl z JOIN {$parent_db}.brand_tbl x ON z.brand_id = x.brand_id WHERE z.coupon_id = a.coupon_id AND coupon_brand_status = 1) AS brands,
        IF(a.is_nationwide = 1, 
            'Nationwide', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'ORC',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";

        $check_id = $this->main->check_join('coupon_tbl a', $coupon_join, TRUE, FALSE, FALSE, $coupon_select);
        if ($check_id['result']) {
            $data['coupon']       = $check_id['info'];
            $data['coupon_bc']    = array_column($this->main->get_data('coupon_bc_tbl', ['coupon_id' => $id, 'coupon_bc_status' => 1], FALSE, 'bc_id'), 'bc_id');
            $data['coupon_brand'] = array_column($this->main->get_data('coupon_brand_tbl', ['coupon_id' => $id, 'coupon_brand_status' => 1], FALSE, 'brand_id'), 'brand_id');
            $user_id        = clean_data(decode($info['user_id']));
            $category_where = "a.coupon_cat_id IN (SELECT z.coupon_cat_id FROM user_access_tbl z WHERE z.user_id = {$user_id} AND user_access_status = 1)";
            $data['category']    = $this->main->get_data('coupon_category_tbl a', 'coupon_cat_status = 1 AND ' . $category_where);
            $holder_type_where = (!in_array($check_id['info']->coupon_cat_id, paid_category())) ? 'coupon_holder_type_status = 1 AND coupon_holder_type_id != 4' : 'coupon_holder_type_status = 1';
            $data['holder_type']  = $this->main->get_data('coupon_holder_type_tbl a', $holder_type_where);
            $data['brand']       = $this->main->get_data("{$parent_db}.brand_tbl", ['brand_status' => 1]);
            $data['bc']          = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
            $data['coupon_type'] = $this->main->get_data('coupon_type_tbl', ['coupon_type_status' => 1]);
            $data['value_type']  = $this->main->get_data('coupon_value_type_tbl a', ['coupon_value_type_status' => 1]);


            $html = $this->load->view('creator/coupon/standard_coupon_edit_modal_content', $data, TRUE);

            $result = [
                'result' => TRUE,
                'html'   => $html
            ];
        } else {
            $result = [
                'result' => FALSE
            ];
        }
        
        echo json_encode($result);
    }

    public function modal_transaction_coupon($id)
    {
        $info                = $this->_require_login();
        $id                  = clean_data(decode($id));
        $parent_db           = $GLOBALS['parent_db'];
        $where               = ['coupon_transaction_header_id' => $id];
        $coupon_trans_select = '*, (SELECT COUNT(*) FROM coupon_transaction_details_tbl z WHERE a.coupon_transaction_header_id = z.coupon_transaction_header_id ) as `coupon_qty`';
        $check_transaction   = $this->main->check_data('coupon_transaction_header_tbl a', $where, TRUE, $coupon_trans_select);
        if ($check_transaction['result']) {
            $coupon_join = [
                'coupon_value_type_tbl b'          => 'b.coupon_value_type_id = a.coupon_value_type_id',
                'coupon_holder_type_tbl c'         => 'c.coupon_holder_type_id = a.coupon_holder_type_id',
                'coupon_transaction_details_tbl d' => "d.coupon_id = a.coupon_id AND d.coupon_transaction_header_id = {$id}",
                'coupon_category_tbl e'            => 'e.coupon_cat_id = a.coupon_cat_id',
				'company_tbl f'            		=> 'f.company_id = a.company_id',
            ];

            $coupon_select = "*,
            (SELECT GROUP_CONCAT(x.brand_name SEPARATOR ', ') FROM coupon_brand_tbl z JOIN {$parent_db}.brand_tbl x ON z.brand_id = x.brand_id WHERE z.coupon_id = a.coupon_id AND coupon_brand_status = 1) AS brands,
            IF(a.is_nationwide = 1, 
                'Nationwide', 
                (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
            IF(a.is_orc = 2, 'All Products',
            IF(a.is_orc = 1, 
                CONCAT_WS(', ', 'ORC',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
                (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";

            $coupon = $this->main->get_join('coupon_tbl a', $coupon_join, TRUE, FALSE, FALSE, $coupon_select);

            $join_salable = [
                "{$parent_db}.product_tbl b" => 'a.prod_id = b.prod_id',
                "{$parent_db}.unit_tbl c"    => 'a.unit_id = c.unit_id',
                "{$parent_db}.unit_tbl d"    => 'a.2nd_uom = d.unit_id'
            ];

            $data['products']    = $this->main->get_join("{$parent_db}.product_sale_tbl a", $join_salable);
            $data['coupon']      = $coupon;
            $data['transaction'] = $check_transaction['info'];

            if ( $coupon->is_orc ) {
                $orc_list = array_column($this->main->get_data("{$parent_db}.orc_list_tbl", ['orc_list_status' => 1]), 'prod_sale_id');
                $orcs      = implode(', ', $orc_list);
                $coupon_prod_where = 'coupon_prod_sale_status = 1 AND coupon_id = ' . $id . ' AND prod_sale_id NOT IN (' . $orcs . ')';
                $data['coupon_prod']  = array_column($this->main->get_data('coupon_prod_sale_tbl', $coupon_prod_where, FALSE, 'prod_sale_id'), 'prod_sale_id');
            } else {
                $data['coupon_prod']  = array_column($this->main->get_data('coupon_prod_sale_tbl', ['coupon_id' => $id, 'coupon_prod_sale_status' => 1], FALSE, 'prod_sale_id'), 'prod_sale_id');
            }

            $data['coupon_brand'] = array_column($this->main->get_data('coupon_brand_tbl', ['coupon_id' => $id, 'coupon_brand_status' => 1], FALSE, 'brand_id'), 'brand_id');
            $data['coupon_bc']    = array_column($this->main->get_data('coupon_bc_tbl', ['coupon_id' => $id, 'coupon_bc_status' => 1], FALSE, 'bc_id'), 'bc_id');
            
            $holder_type_where = (!in_array($check_transaction['info']->coupon_cat_id, paid_category())) ? 'coupon_holder_type_status = 1 AND coupon_holder_type_id != 4' : 'coupon_holder_type_status = 1';
            $data['holder_type']  = $this->main->get_data('coupon_holder_type_tbl a', $holder_type_where);
            $user_id        = clean_data(decode($info['user_id']));
            $category_where = "a.coupon_cat_id IN (SELECT z.coupon_cat_id FROM user_access_tbl z WHERE z.user_id = {$user_id} AND user_access_status = 1)";
            $data['category']    = $this->main->get_data('coupon_category_tbl a', 'coupon_cat_status = 1 AND ' . $category_where);
            $data['brand']       = $this->main->get_data("{$parent_db}.brand_tbl", ['brand_status' => 1]);
            $data['bc']          = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
            $data['coupon_type'] = $this->main->get_data('coupon_type_tbl', ['coupon_type_status' => 1]);
            $data['value_type']  = $this->main->get_data('coupon_value_type_tbl a', ['coupon_value_type_status' => 1]);
			$data['user_id'] = decode($info['user_id']);
			$data['customer_select'] = $this->_get_customers_selection($check_transaction['info']->customer_id);
			$payment_select = $this->_get_payment_types_selection($check_transaction['info']->payment_type_id);
			$data['payment_fields'] = $this->_get_payment_details_fields($check_transaction['info']->payment_type_id, $check_transaction['info']->payment_type_id, $check_transaction['info']->payment_terms, $payment_select);

            $html = $this->load->view('creator/coupon/transaction_coupon_edit_modal_content', $data, TRUE);

            $result = [
                'result' => TRUE,
                'html'   => $html
            ];
        } else {
            $result = [
                'result' => FALSE
            ];
        }
        
        echo json_encode($result);
    }

    public function modal_product_coupon($id)
    {
        $info      = $this->_require_login();
        $id        = clean_data(decode($id));
        $parent_db = $GLOBALS['parent_db'];
        $coupon_join = [
            'coupon_value_type_tbl b'  => 'b.coupon_value_type_id = a.coupon_value_type_id AND a.coupon_id = ' . $id,
            'coupon_holder_type_tbl c' => 'c.coupon_holder_type_id = a.coupon_holder_type_id',
            'coupon_category_tbl d'    => 'd.coupon_cat_id = a.coupon_cat_id',
        ];

        $coupon_select = "*,
        (SELECT GROUP_CONCAT(x.brand_name SEPARATOR ', ') FROM coupon_brand_tbl z JOIN {$parent_db}.brand_tbl x ON z.brand_id = x.brand_id WHERE z.coupon_id = a.coupon_id AND coupon_brand_status = 1) AS brands,
        IF(a.is_nationwide = 1, 
            'Nationwide', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'ORC',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";

        $check_id = $this->main->check_join('coupon_tbl a', $coupon_join, TRUE, FALSE, FALSE, $coupon_select);
        if ($check_id['result']) {
            $join_salable = [
                "{$parent_db}.product_tbl b" => 'a.prod_id = b.prod_id',
                "{$parent_db}.unit_tbl c"    => 'a.unit_id = c.unit_id',
                "{$parent_db}.unit_tbl d"    => 'a.2nd_uom = d.unit_id'
            ];

            $data['products'] = $this->main->get_join("{$parent_db}.product_sale_tbl a", $join_salable);
            $data['coupon']   = $check_id['info'];

            if ( $check_id['info']->is_orc ) {
                $orc_list = array_column($this->main->get_data("{$parent_db}.orc_list_tbl", ['orc_list_status' => 1]), 'prod_sale_id');
                $orcs      = implode(', ', $orc_list);
                $coupon_prod_where = 'coupon_prod_sale_status = 1 AND coupon_id = ' . $id . ' AND prod_sale_id NOT IN (' . $orcs . ')';
                $data['coupon_prod']  = array_column($this->main->get_data('coupon_prod_sale_tbl', $coupon_prod_where, FALSE, 'prod_sale_id'), 'prod_sale_id');
            } else {
                $data['coupon_prod']  = array_column($this->main->get_data('coupon_prod_sale_tbl', ['coupon_id' => $id, 'coupon_prod_sale_status' => 1], FALSE, 'prod_sale_id'), 'prod_sale_id');
            }

            $data['coupon_brand'] = array_column($this->main->get_data('coupon_brand_tbl', ['coupon_id' => $id, 'coupon_brand_status' => 1], FALSE, 'brand_id'), 'brand_id');
            $data['coupon_bc']    = array_column($this->main->get_data('coupon_bc_tbl', ['coupon_id' => $id, 'coupon_bc_status' => 1], FALSE, 'bc_id'), 'bc_id');
            $holder_type_where = (!in_array($check_id['info']->coupon_cat_id, paid_category())) ? 'coupon_holder_type_status = 1 AND coupon_holder_type_id != 4' : 'coupon_holder_type_status = 1';
            $data['holder_type']  = $this->main->get_data('coupon_holder_type_tbl a', $holder_type_where);
            $user_id        = clean_data(decode($info['user_id']));
            $category_where = "a.coupon_cat_id IN (SELECT z.coupon_cat_id FROM user_access_tbl z WHERE z.user_id = {$user_id} AND user_access_status = 1)";
            $data['category']    = $this->main->get_data('coupon_category_tbl a', 'coupon_cat_status = 1 AND ' . $category_where);
            $data['brand']       = $this->main->get_data("{$parent_db}.brand_tbl", ['brand_status' => 1]);
            $data['bc']          = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
            $data['coupon_type'] = $this->main->get_data('coupon_type_tbl', ['coupon_type_status' => 1]);
            $data['value_type']  = $this->main->get_data('coupon_value_type_tbl a', ['coupon_value_type_status' => 1]);

            $html = $this->load->view('creator/coupon/product_coupon_edit_modal_content', $data, TRUE);

            $result = [
                'result' => TRUE,
                'html'   => $html
            ];
        } else {
            $result = [
                'result' => FALSE
            ];
        }
        
        echo json_encode($result);
    }

    private function _generate_coupon_pdf($coupon_id, $scope_masking="", $display_exp=0)
    {
    	ini_set('max_execution_time', 0); 
        ini_set('memory_limit','2048M');
        $parent_db = $GLOBALS['parent_db'];
		$one_pager = true;

        $this->load->library('Pdf');

        $select = "*,
        IF(a.is_nationwide = 1, 
            'NATIONWIDE', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bcs',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'OVEN ROASTED CHICKEN',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";

        $join = array('coupon_category_tbl a1' => 'a.coupon_cat_id = a1.coupon_cat_id AND a1.coupon_cat_status = 1 AND a.coupon_id = ' . $coupon_id);

        $coupon      = $this->main->get_join('coupon_tbl a', $join, TRUE, FALSE, FALSE, $select);
        $design      = $coupon->coupon_cat_design;
        $back_design = $coupon->coupon_cat_design_back;

        if(date('Y-m', strtotime($coupon->coupon_start)) == date('Y-m', strtotime($coupon->coupon_end))){
        	$month = date('M.', strtotime($coupon->coupon_start));
        	$year = date('Y', strtotime($coupon->coupon_start));
        	$day_start = date('d', strtotime($coupon->coupon_start));
        	$day_end = date('d', strtotime($coupon->coupon_end));
        	$valid_till = $month . ' ' . $day_start . ' to ' . $day_end . ', ' . $year;	
        }else{
        	$year_start = date('Y', strtotime($coupon->coupon_start));
        	$year_end = date('Y', strtotime($coupon->coupon_end));
        	if($year_start == $year_end){
        		$valid_till = date_format(date_create($coupon->coupon_start),"M. d") . ' - ' . date_format(date_create($coupon->coupon_end),"M. d, Y");
        	}else{
        		$valid_till = date_format(date_create($coupon->coupon_start),"M. d, Y") . ' - ' . date_format(date_create($coupon->coupon_end),"M. d, Y");
        	}
        }

        $pdf = new Pdf('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetMargins(5, 10, 5, true);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetTitle($coupon->coupon_code);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetAuthor('Chookstogo, Inc.');
        $pdf->SetDisplayMode('real', 'default');
        $pdf->SetFont('helvetica', 'B', 14, '', 'false');

        $custom_layout = [ 215.9, 92.9 ];
		if($one_pager){
			$custom_layout = [ 215.9, 183 ];
		}
        

        $style = [ 
            'border'  => false,
            'padding' => 0,
            'bgcolor' => false
        ];

        

        if(!$scope_masking){
			$coupon_bcs = explode(', ', $coupon->bcs);
			$coupon_bcs_count = count($coupon_bcs);
			$bcs = '';
			for ($index = 0; $index < $coupon_bcs_count; $index++) {
				if ($index == 0) {
					$bcs .= $coupon_bcs[$index];
				} elseif ($index == ($coupon_bcs_count - 1) && $index != 0) {
					$bcs .= ' and ' . $coupon_bcs[$index];
				} else {
					$bcs .= ', ' . $coupon_bcs[$index];
				}
			}
		} else {
			$bcs = $scope_masking;
		}


        if($coupon->coupon_cat_id == 1){ // GIFT EVOUCHER

        	if($coupon->coupon_type_id == 1){

		        if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
		            $value = $coupon->coupon_amount.'%';
		            if ($coupon->coupon_amount == 100) {
		                $additional_details = 'AMOUNT    : 1 ' . $coupon->products;
		            } else {
		                $additional_details = 'AMOUNT    : ' . $value . ' Discount';
		            }
		        } else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
		            $value = 'P' . $coupon->coupon_amount;
		            $additional_details = 'AMOUNT    : ' . $value . ' Discount';
		        }
		    }elseif($coupon->coupon_type_id == 2){
		    	if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
		            $value = $coupon->coupon_amount.'%';
		            if ($coupon->coupon_amount == 100) {
		                $additional_details = 'AMOUNT    : 1 ' . $coupon->products;
		            } else {
		                $additional_details = 'AMOUNT    : ' . $value . ' Discount for ' . $coupon->products;
		            }
		        } else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
		            $value = 'P' . $coupon->coupon_amount;
		            $additional_details = 'AMOUNT    : ' . $value . ' Discount for ' . $coupon->products;
		        }
		    }
			$pdf->AddPage('L', $custom_layout);
	        $pdf->setJPEGQuality(100);
	        $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
	        $pdf->Image($design, 7, 7, 200, 80, 'JPG', '', '', true, 150, '', false, false, 1, false, false, false);
	        $pdf->write2DBarcode($coupon->coupon_code, 'QRCODE,H', 26, 26, 32, 32, $style, 'N');
	        $pdf->Text(29, 65, $coupon->coupon_code);
	        $pdf->SetFont('helvetica', '', 8);
	        $pdf->Text(64, 65, 'LOCATION : ' . $bcs);
	        $pdf->Text(64, 69, $additional_details);
	        $pdf->SetFont('helvetica', '', 10);
	        $pdf->SetTextColor(255,255,255);
	        $pdf->Text(37, 74.7, $valid_till);

			if($back_design){
				if($one_pager){
					$y = 96;
				} else {
					$y = 7;
					$pdf->AddPage('L', $custom_layout);
				}
	
				$pdf->setJPEGQuality(100);
				$pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
				$pdf->Image($back_design, 7, $y, 200, 80, 'JPG', '', '', true, 150, '', false, false, 1, false, false, false);
			}



	    }elseif($coupon->coupon_cat_id == 4){ // MEAL EVOUCHER

        	if($coupon->coupon_type_id == 1){

		        if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
		            $value = $coupon->coupon_amount.'%';
		            if ($coupon->coupon_amount == 100) {
		                $additional_details = 'AMOUNT    : 1 ' . $coupon->products;
		            } else {
		                $additional_details = 'AMOUNT    : ' . $value . ' Discount';
		            }
		        } else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
		            $value = 'P' . $coupon->coupon_amount;
		            $additional_details = 'AMOUNT    : ' . $value . ' Discount';
		        }
		    }elseif($coupon->coupon_type_id == 2){
		    	if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
		            $value = $coupon->coupon_amount.'%';
		            if ($coupon->coupon_amount == 100) {
		                $additional_details = 'AMOUNT    : 1 ' . $coupon->products;
		            } else {
		                $additional_details = 'AMOUNT    : ' . $value . ' Discount for ' . $coupon->products;
		            }
		        } else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
		            $value = 'P' . $coupon->coupon_amount;
		            $additional_details = 'AMOUNT    : ' . $value . ' Discount for ' . $coupon->products;
		        }
		    }
			$pdf->AddPage('L', $custom_layout);
	        $pdf->setJPEGQuality(100);
            $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
            $pdf->Image($design, 7, 7, 200, 80, 'JPG', '', '', true, 150, '', false, false, 1, false, false, false);
            $pdf->write2DBarcode($coupon->coupon_code, 'QRCODE,H', 23.6, 21.5, 32, 32, $style, 'N');
            $pdf->Text(27, 60, $coupon->coupon_code);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Text(62, 59, 'LOCATION : ' . $bcs);
            $pdf->Text(62, 64, $additional_details);
            $pdf->SetFont('helvetica', '', 10);
            //$pdf->SetTextColor(255,255,255);
            $pdf->Text(48, 72.5, $valid_till);

			if($back_design){
				if($one_pager){
					$y = 96;
				} else {
					$y = 7;
					$pdf->AddPage('L', $custom_layout);
				}
	
				$pdf->setJPEGQuality(100);
				$pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
				$pdf->Image($back_design, 7, $y, 200, 80, 'JPG', '', '', true, 150, '', false, false, 1, false, false, false);
			}


	    }elseif($coupon->coupon_cat_id == 2){ // BIRTHDAY EVOUCHER

	    	if($coupon->coupon_type_id == 1){

		        if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
		            $value = $coupon->coupon_amount.'%';
		            if ($coupon->coupon_amount == 100) {
		                $additional_details = 'AMOUNT : 1 ' . $coupon->products;
		            } else {
		                $additional_details = 'AMOUNT : ' . $value . ' Discount';
		            }
		        } else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
		            $value = 'P' . $coupon->coupon_amount;
		            $additional_details = 'AMOUNT : ' . $value . ' Discount';
		        }
		    }elseif($coupon->coupon_type_id == 2){
		    	if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
		            $value = $coupon->coupon_amount.'%';
		            if ($coupon->coupon_amount == 100) {
		                $additional_details = 'AMOUNT : 1 ' . $coupon->products;
		            } else {
		                $additional_details = 'AMOUNT : ' . $value . ' Discount for' . $coupon->products;
		            }
		        } else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
		            $value = 'P' . $coupon->coupon_amount;
		            $additional_details = 'AMOUNT : ' . $value . ' Discount for ' . $coupon->products;
		        }
		    }

			$pdf->AddPage('L', $custom_layout);
	    	$pdf->setJPEGQuality(100);
            $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
            $pdf->Image($design, 7, 7, 200, 80, 'JPG', '', '', true, 150, '', false, false, 1, false, false, false);
            $pdf->write2DBarcode($coupon->coupon_code, 'QRCODE,H', 21, 18, 32, 32, $style, 'N');
            $pdf->Text(25, 58.3, $coupon->coupon_code);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Text(60.5, 57.5, 'LOCATION : ' . $bcs);
            $pdf->Text(60.5, 62.5, $additional_details);
            $pdf->SetFont('helvetica', '', 10);
            //$pdf->SetTextColor(255,255,255);
            $pdf->Text(60.5, 72, $valid_till);

			if($back_design){
				if($one_pager){
					$y = 96;
				} else {
					$y = 7;
					$pdf->AddPage('L', $custom_layout);
				}
	
				$pdf->setJPEGQuality(100);
				$pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
				$pdf->Image($back_design, 7, $y, 200, 80, 'JPG', '', '', true, 150, '', false, false, 1, false, false, false);
			}
	    }elseif($coupon->coupon_cat_id == 3){ // PAID EVOUCHER
	    	if($coupon->coupon_type_id == 1){

		        if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
		            $value = $coupon->coupon_amount.'%';
		            if ($coupon->coupon_amount == 100) {
		                $additional_details = 'AMOUNT : 1 ' . $coupon->products;
		            } else {
		                $additional_details = 'AMOUNT : ' . $value . ' Discount';
		            }
		        } else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
		            $value = 'P' . $coupon->coupon_amount;
		            $additional_details = 'AMOUNT : ' . $value . ' Discount';
		        }
		    }elseif($coupon->coupon_type_id == 2){
		    	if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
		            $value = $coupon->coupon_amount.'%';
		            if ($coupon->coupon_amount == 100) {
		                $additional_details = 'AMOUNT : 1 ' . $coupon->products;
		            } else {
		                $additional_details = 'AMOUNT : ' . $value . ' Discount for' . $coupon->products;
		            }
		        } else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
		            $value = 'P' . $coupon->coupon_amount;
		            $additional_details = 'AMOUNT : ' . $value . ' Discount for ' . $coupon->products;
		        }
		    }

			$pdf->AddPage('L', $custom_layout);
		    $pdf->setJPEGQuality(100);
            $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
            $pdf->Image($design, 7, 7, 200, 80, 'JPG', '', '', true, 150, '', false, false, 1, false, false, false);
            $pdf->write2DBarcode($coupon->coupon_code, 'QRCODE,H', 23.6, 21.5, 32, 32, $style, 'N');
            $pdf->Text(27, 60, $coupon->coupon_code);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Text(62, 59, 'LOCATION : ' . $bcs);
            $pdf->Text(62, 64, $additional_details);
            $pdf->SetFont('helvetica', '', 10);
            //$pdf->SetTextColor(255,255,255);
            $pdf->Text(48, 72.5, $valid_till);


            if($one_pager){
				$y = 96;
			} else {
				$y = 7;
				$pdf->AddPage('L', $custom_layout);
			}

            $pdf->setJPEGQuality(100);
            $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
            $pdf->Image($back_design, 7, $y, 200, 80, 'JPG', '', '', true, 150, '', false, false, 1, false, false, false);

	    }elseif($coupon->coupon_cat_id == 5){ // ROASTED CHICKEN ECOUPON
	    	if($coupon->coupon_type_id == 1){

		        if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
		            $value = $coupon->coupon_amount.'%';
		            if ($coupon->coupon_amount == 100) {
		                $additional_details = 'AMOUNT : 1 ' . $coupon->products;
		            } else {
		                $additional_details = 'AMOUNT : ' . $value . ' Discount';
		            }
		        } else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
		            $value = 'P' . $coupon->coupon_amount;
		            $additional_details = 'AMOUNT : ' . $value . ' Discount';
		        }
		    }elseif($coupon->coupon_type_id == 2){
		    	if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
		            $value = $coupon->coupon_amount.'%';
		            if ($coupon->coupon_amount == 100) {
		                $additional_details = 'AMOUNT : 1 ' . $coupon->products;
		            } else {
		                $additional_details = 'AMOUNT : ' . $value . ' Discount for' . $coupon->products;
		            }
		        } else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
		            $value = 'P' . $coupon->coupon_amount;
		            $additional_details = 'AMOUNT : ' . $value . ' Discount for ' . $coupon->products;
		        }
		    }

			$custom_layout = [ 442, 226 ];

			$image_w = 432;
			$image_h = 216;

			$image_x_arr = [5];
			$image_y_arr = [5];

			$coupon_code_x_arr = [360];
			$coupon_code_y_arr = [20];

			$qr_x_arr = [376.5];
			$qr_y_arr = [86.5];
			
			$scope_x_arr = [286.5];
			$scope_y_arr = [199];

			$image_x = $image_x_arr[0];
			$coupon_code_x = $coupon_code_x_arr[0];
			$qr_x = $qr_x_arr[0];
			$scope_x = $scope_x_arr[0];

			$image_y = $image_y_arr[0];
			$coupon_code_y = $coupon_code_y_arr[0];
			$qr_y = $qr_y_arr[0];
			$scope_y = $scope_y_arr[0];

			
			$pdf->AddPage('L', $custom_layout);

		    $pdf->setJPEGQuality(100);
			// $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
			$pdf->Image($design, $image_x, $image_y, $image_w, $image_h, 'JPG', '', '', true, 150, '', false, false, 0, true, false, false);
			$pdf->SetFont('helvetica', '', 32);
			$pdf->Text($coupon_code_x, $coupon_code_y, $coupon->coupon_code);
			$pdf->write2DBarcode($coupon->coupon_code, 'QRCODE,H', $qr_x, $qr_y, 34, 34, $style, 'N');
			$pdf->SetFont('helvetica', '', 14);
			$pdf->Text($scope_x, $scope_y, $bcs);

			
	    }

        $cwd          = getcwd();
        $date_created = strtotime($coupon->coupon_added);

        $invalid_chars = [ '<' ,'>' ,':' ,'"' ,'/' ,'\\' ,'|' ,'?' ,'*', ' ', '[', ']', '\'', '(', ')' ];
        $cleaned_name  = str_replace($invalid_chars, '-', $coupon->coupon_name);
        $file_name     = $cleaned_name . '_' . $coupon->coupon_code . '_' . $date_created . '.pdf';
        $save_path     = '/assets/coupons/' . $file_name;

        $pdf->Output($cwd . $save_path, 'F');
        return $save_path;
    }

    public function test_coupon_pdf($coupon_id)
    {
        $info      = $this->_require_login();

        $this->load->library('Pdf');
        $parent_db = $GLOBALS['parent_db'];
		$one_pager = TRUE;

        $select = "*,
        IF(a.is_nationwide = 1, 
            'NATIONWIDE', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bcs',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'OVEN ROASTED CHICKEN',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";

        $join = array('coupon_category_tbl a1' => 'a.coupon_cat_id = a1.coupon_cat_id AND a1.coupon_cat_status = 1 AND a.coupon_id = ' . $coupon_id);

        $coupon      = $this->main->get_join('coupon_tbl a', $join, TRUE, FALSE, FALSE, $select);

        if(date('Y-m', strtotime($coupon->coupon_start)) == date('Y-m', strtotime($coupon->coupon_end))){
            $month = date('M.', strtotime($coupon->coupon_start));
            $year = date('Y', strtotime($coupon->coupon_start));
            $day_start = date('d', strtotime($coupon->coupon_start));
            $day_end = date('d', strtotime($coupon->coupon_end));
            $valid_till = $month . ' ' . $day_start . ' to ' . $day_end . ', ' . $year; 
        }else{
            $year_start = date('Y', strtotime($coupon->coupon_start));
            $year_end = date('Y', strtotime($coupon->coupon_end));
            if($year_start == $year_end){
                $valid_till = date_format(date_create($coupon->coupon_start),"M. d") . ' - ' . date_format(date_create($coupon->coupon_end),"M. d, Y");
            }else{
                $valid_till = date_format(date_create($coupon->coupon_start),"M. d, Y") . ' - ' . date_format(date_create($coupon->coupon_end),"M. d, Y");
            }
        }
        
        $design      = $coupon->coupon_cat_design;
        $back_design = $coupon->coupon_cat_design_back;

        $pdf        = new Pdf('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetMargins(5, 10, 5, true);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetTitle($coupon->coupon_code);
        $pdf->SetAutoPageBreak(true);
        $pdf->SetAuthor('Chookstogo, Inc.');
        // $pdf->SetDisplayMode('real', 'default');
        $pdf->SetDisplayMode('real');
        $pdf->SetFont('helvetica', 'B', 14, '', 'false');

        // $custom_layout = [ 215.9, 92.9 ];
        // $custom_layout = [ 215.9, 183.1 ];
        
		

        $style = [ 
            'border'  => false,
            'padding' => 0,
            /*'fgcolor' => array(237, 31, 36),*/
            'bgcolor' => false
        ];

        if($coupon->coupon_type_id == 1){

	        if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
	            $value = $coupon->coupon_amount.'%';
	            if ($coupon->coupon_amount == 100) {
	                $additional_details = 'AMOUNT : 1 ' . $coupon->products;
	            } else {
	                $additional_details = 'AMOUNT : ' . $value . ' Discount';
	            }
	        } else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
	            $value = 'P' . $coupon->coupon_amount;
	            $additional_details = 'AMOUNT : ' . $value . ' Discount';
	        }
	    }elseif($coupon->coupon_type_id == 2){
	    	if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
	            $value = $coupon->coupon_amount.'%';
	            if ($coupon->coupon_amount == 100) {
	                $additional_details = 'AMOUNT : 1 ' . $coupon->products;
	            } else {
	                $additional_details = 'AMOUNT : ' . $value . ' Discount for' . $coupon->products;
	            }
	        } else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
	            $value = 'P' . $coupon->coupon_amount;
	            $additional_details = 'AMOUNT : ' . $value . ' Discount for ' . $coupon->products;
	        }
	    }

        $coupon_bcs = explode(', ', $coupon->bcs);
        $coupon_bcs_count = count($coupon_bcs);
        $bcs = '';
        for ($index = 0; $index < $coupon_bcs_count; $index++) {
            if ($index == 0) {
                $bcs .= $coupon_bcs[$index];
            } elseif ($index == ($coupon_bcs_count - 1) && $index != 0) {
                $bcs .= ' and ' . $coupon_bcs[$index];
            } else {
                $bcs .= ', ' . $coupon_bcs[$index];
            }
        }

		$custom_layout = [ 215.9, 92.9 ];
		if($one_pager){
			$custom_layout = [ 215.9, 183 ];
			// $custom_layout = [ 295, 151 ];
			$custom_layout = [ 442, 226 ];
			
		}
		// $custom_layout = [ 302, 158 ];

		// $image_w = 288;
		// $image_h = 144;

		// $image_x_arr = [7, 441.89];
		// $image_y_arr = [7, 207.5, 397.5];

		// $coupon_code_x_arr = [241, 739.89];
		// $coupon_code_y_arr = [16.3, 220.5, 410.5];

		// $qr_x_arr = [253.8, 750.89];
		// $qr_y_arr = [60.2, 275, 465];
		
		// $scope_x_arr = [194.5, 676.89];
		// $scope_y_arr = [136, 369, 559];

        $pdf->AddPage('L', $custom_layout);

		$image_w = 432;
		$image_h = 216;

		$image_x_arr = [5];
		$image_y_arr = [5];

		$coupon_code_x_arr = [360];
		$coupon_code_y_arr = [20];

		$qr_x_arr = [376.5];
		$qr_y_arr = [86.5];
		
		$scope_x_arr = [286.5];
		$scope_y_arr = [199];

		$image_x = $image_x_arr[0];
		$coupon_code_x = $coupon_code_x_arr[0];
		$qr_x = $qr_x_arr[0];
		$scope_x = $scope_x_arr[0];

		$image_y = $image_y_arr[0];
		$coupon_code_y = $coupon_code_y_arr[0];
		$qr_y = $qr_y_arr[0];
		$scope_y = $scope_y_arr[0];

        // $pdf->setJPEGQuality(100);
        // $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
        // $pdf->Image($design, 7, 7, 200, 80, 'JPG', '', '', true, 150, '', false, false, 1, false, false, false);
        // $pdf->write2DBarcode($coupon->coupon_code, 'QRCODE,H', 23.6, 21.5, 32, 32, $style, 'N');
        // $pdf->Text(27, 60, $coupon->coupon_code);
        // $pdf->SetFont('helvetica', '', 8);
        // $pdf->Text(62, 59, 'LOCATION : ' . $bcs);
        // $pdf->Text(62, 64, $additional_details);
        // $pdf->SetFont('helvetica', '', 10);
        // //$pdf->SetTextColor(255,255,255);
        // $pdf->Text(48, 72.5, $valid_till);


		$pdf->setJPEGQuality(100);
		// $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
		$pdf->Image($design, $image_x, $image_y, $image_w, $image_h, 'JPG', '', '', true, 150, '', false, false, 0, true, false, false);
		$pdf->SetFont('helvetica', '', 32);
		$pdf->Text($coupon_code_x, $coupon_code_y, $coupon->coupon_code);
		$pdf->write2DBarcode($coupon->coupon_code, 'QRCODE,H', $qr_x, $qr_y, 34, 34, $style, 'N');
		$pdf->SetFont('helvetica', '', 14);
		$pdf->Text($scope_x, $scope_y, $bcs);

		if($back_design){
			if($one_pager){
				$y = 96;
			} else {
				$y = 7;
				$pdf->AddPage('L', $custom_layout);
			}
	
			$pdf->setJPEGQuality(100);
			$pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
			$pdf->Image($back_design, 7, $y, 200, 80, 'JPG', '', '', true, 150, '', false, false, 1, false, false, false);
		}

        //$pdf->writeHTML($html, true, false, true, false, '');

        $invalid_chars = [ '<' ,'>' ,':' ,'"' ,'/' ,'\\' ,'|' ,'?' ,'*', ' ', '[', ']', '\'', '(', ')' ];
        $cleaned_name  = str_replace($invalid_chars, '-', $coupon->coupon_name);
        $date_created = strtotime($coupon->coupon_added);
        $file_name     = $cleaned_name . '_' . $coupon->coupon_code . '_' . $date_created . '.pdf';

        $pdf->Output($file_name , 'I');

		


		// $pdf_file = escapeshellarg( $file_name );
		// $jpg_file = escapeshellarg( "output.jpg" );

		// $result = 0;
		// exec( "convert -density 300 {$pdf_file} {$jpg_file}", array(), $result );


        // $cwd          = getcwd();
        // $date_created = strtotime($coupon->coupon_added);

        // $invalid_chars = [ '<' ,'>' ,':' ,'"' ,'/' ,'\\' ,'|' ,'?' ,'*', ' ', '[', ']', '\'', '(', ')' ];
        // $cleaned_name  = str_replace($invalid_chars, '-', $coupon->coupon_name);
        // $file_name     = $cleaned_name . '_' . $coupon->coupon_code . '_' . $date_created . '.pdf';
        // $save_path     = '/assets/coupons/' . $file_name;

        // $pdf->Output($cwd . $save_path, 'F');
		// return $save_path;

		// $fp_pdf = fopen(FCPATH. $save_path, 'rb');

		// $img = new imagick(); // [0] can be used to set page number
		// $img->setResolution(300,300);
		// $img->readImageFile($fp_pdf);
		// $img->setImageFormat( "jpg" );
		// $img->setImageCompression(imagick::COMPRESSION_JPEG); 
		// $img->setImageCompressionQuality(90); 

		// $img->setImageUnits(imagick::RESOLUTION_PIXELSPERINCH);

		// $data = $img->getImageBlob(); 
		
        
    }

	public function test_multi_coupon_pdf($header_id)
    {
        $info      = $this->_require_login();

        $this->load->library('Pdf');
        $parent_db = $GLOBALS['parent_db'];
		$one_pager = TRUE;

		$image_x_arr = [40, 441.89];
		$image_y_arr = [17.5, 207.5, 397.5]; // 190 diff

		// $coupon_code_x_arr = [340, 741.89];
		$coupon_code_x_arr = [338, 739.89];
		// $coupon_code_y_arr = [30, 220, 410]; // 12.5 diff
		$coupon_code_y_arr = [30.5, 220.5, 410.5]; // 12.5 diff

		$qr_x_arr = [349, 750.89];
		$qr_y_arr = [85, 275, 465]; // 67.5 diff
		
		$scope_x_arr = [275, 676.89];
		// $scope_y_arr = [180, 370, 560]; // 162.5 diff
		$scope_y_arr = [179, 369, 559]; // 162.5 diff

		$pdf        = new Pdf('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetMargins(5, 10, 5, true);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetTitle($header_id);
        $pdf->SetAutoPageBreak(true);
        $pdf->SetAuthor('Chookstogo, Inc.');
        // $pdf->SetDisplayMode('real', 'default');
        $pdf->SetDisplayMode('real');
        $pdf->SetFont('helvetica', 'B', 14, '', 'false');

        // $custom_layout = [ 215.9, 92.9 ];
        
		$custom_layout = array(  595.276,   841.890);

        $style = [ 
            'border'  => false,
            'padding' => 0,
            /*'fgcolor' => array(237, 31, 36),*/
            'bgcolor' => false
        ];

        $select = "a.*, a1.*,
        IF(a.is_nationwide = 1, 
            'NATIONWIDE', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bcs',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'OVEN ROASTED CHICKEN',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";

        $join = array(
			'coupon_category_tbl a1' => 'a.coupon_cat_id = a1.coupon_cat_id AND a1.coupon_cat_status = 1',
			'coupon_transaction_details_tbl b' => 'a.coupon_id = b.coupon_id',
			'coupon_transaction_header_tbl c' => 'b.coupon_transaction_header_id = c.coupon_transaction_header_id AND c.coupon_transaction_header_id = '.$header_id
		);

        $coupon_result      = $this->main->get_join('coupon_tbl a', $join, false, FALSE, FALSE, $select);

		$i = 1;
		$add_page = TRUE;
		if(!empty($coupon_result)){
			foreach($coupon_result as $coupon){

				if($add_page) $pdf->AddPage('L', $custom_layout);

				if(date('Y-m', strtotime($coupon->coupon_start)) == date('Y-m', strtotime($coupon->coupon_end))){
					$month = date('M.', strtotime($coupon->coupon_start));
					$year = date('Y', strtotime($coupon->coupon_start));
					$day_start = date('d', strtotime($coupon->coupon_start));
					$day_end = date('d', strtotime($coupon->coupon_end));
					$valid_till = $month . ' ' . $day_start . ' to ' . $day_end . ', ' . $year; 
				}else{
					$year_start = date('Y', strtotime($coupon->coupon_start));
					$year_end = date('Y', strtotime($coupon->coupon_end));
					if($year_start == $year_end){
						$valid_till = date_format(date_create($coupon->coupon_start),"M. d") . ' - ' . date_format(date_create($coupon->coupon_end),"M. d, Y");
					}else{
						$valid_till = date_format(date_create($coupon->coupon_start),"M. d, Y") . ' - ' . date_format(date_create($coupon->coupon_end),"M. d, Y");
					}
				}
				
				$design      = $coupon->coupon_cat_design;
				$back_design = $coupon->coupon_cat_design_back;
				
				
				if($coupon->coupon_type_id == 1){
		
					if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
						$value = $coupon->coupon_amount.'%';
						if ($coupon->coupon_amount == 100) {
							$additional_details = 'AMOUNT : 1 ' . $coupon->products;
						} else {
							$additional_details = 'AMOUNT : ' . $value . ' Discount';
						}
					} else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
						$value = 'P' . $coupon->coupon_amount;
						$additional_details = 'AMOUNT : ' . $value . ' Discount';
					}
				}elseif($coupon->coupon_type_id == 2){
					if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
						$value = $coupon->coupon_amount.'%';
						if ($coupon->coupon_amount == 100) {
							$additional_details = 'AMOUNT : 1 ' . $coupon->products;
						} else {
							$additional_details = 'AMOUNT : ' . $value . ' Discount for' . $coupon->products;
						}
					} else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
						$value = 'P' . $coupon->coupon_amount;
						$additional_details = 'AMOUNT : ' . $value . ' Discount for ' . $coupon->products;
					}
				}
				
				$coupon_bcs = explode(', ', $coupon->bcs);
				$coupon_bcs_count = count($coupon_bcs);
				$bcs = '';
				for ($index = 0; $index < $coupon_bcs_count; $index++) {
					if ($index == 0) {
						$bcs .= $coupon_bcs[$index];
					} elseif ($index == ($coupon_bcs_count - 1) && $index != 0) {
						$bcs .= ' and ' . $coupon_bcs[$index];
					} else {
						$bcs .= ', ' . $coupon_bcs[$index];
					}
				}


				$odd = $i % 2;
				if($odd){
					$image_x = $image_x_arr[0];
					$coupon_code_x = $coupon_code_x_arr[0];
					$qr_x = $qr_x_arr[0];
					$scope_x = $scope_x_arr[0];
				} else {
					$image_x = $image_x_arr[1];
					$coupon_code_x = $coupon_code_x_arr[1];
					$qr_x = $qr_x_arr[1];
					$scope_x = $scope_x_arr[1];
				}

				if($i < 3){ // first row
					$image_y = $image_y_arr[0];
					$coupon_code_y = $coupon_code_y_arr[0];
					$qr_y = $qr_y_arr[0];
					$scope_y = $scope_y_arr[0];
				}elseif($i < 5){
					$image_y = $image_y_arr[1];
					$coupon_code_y = $coupon_code_y_arr[1];
					$qr_y = $qr_y_arr[1];
					$scope_y = $scope_y_arr[1];
				}else{
					$image_y = $image_y_arr[2];
					$coupon_code_y = $coupon_code_y_arr[2];
					$qr_y = $qr_y_arr[2];
					$scope_y = $scope_y_arr[2];
				}

				$pdf->setJPEGQuality(100);
				$pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
				$pdf->Image($design, $image_x, $image_y, 360, 180, 'JPG', '', '', true, 150, '', false, false, 1, false, false, false);
				$pdf->SetFont('helvetica', '', 24);
				$pdf->Text($coupon_code_x, $coupon_code_y, $coupon->coupon_code);
				$pdf->write2DBarcode($coupon->coupon_code, 'QRCODE,H', $qr_x, $qr_y, 30, 30, $style, 'N');
				$pdf->SetFont('helvetica', '', 12);
				$pdf->Text($scope_x, $scope_y, $bcs);
				
				if($i==6){
					$i = 1;
					$add_page = TRUE;
				} else {
					$i++;
					$add_page = FALSE;
				}
			}
		}

        //$pdf->writeHTML($html, true, false, true, false, '');

        $invalid_chars = [ '<' ,'>' ,':' ,'"' ,'/' ,'\\' ,'|' ,'?' ,'*', ' ', '[', ']', '\'', '(', ')' ];
        $cleaned_name  = str_replace($invalid_chars, '-', $coupon->coupon_name);
        $date_created = strtotime($coupon->coupon_added);
        $file_name     = $cleaned_name . '_' . $coupon->coupon_code . '_' . $date_created . '.pdf';

        // $pdf->Output($file_name , 'D');
        $pdf->Output($file_name , 'I');

        /*$cwd          = getcwd();
        $date_created = strtotime($coupon->coupon_added);

        $invalid_chars = [ '<' ,'>' ,':' ,'"' ,'/' ,'\\' ,'|' ,'?' ,'*', ' ', '[', ']', '\'', '(', ')' ];
        $cleaned_name  = str_replace($invalid_chars, '-', $coupon->coupon_name);
        $file_name     = $cleaned_name . '_' . $coupon->coupon_code . '_' . $date_created . '.pdf';
        $save_path     = '/assets/coupons/' . $file_name;

        $pdf->Output($cwd . $save_path, 'F');
        return $save_path;*/
    }
	
	public function download_multi_coupon_pdf($header_id)
    {
        $info      = $this->_require_login();
		$header_id = decode($header_id);
        $this->load->library('Pdf');
        $parent_db = $GLOBALS['parent_db'];
		$one_pager = TRUE;

		$image_x_arr = [40, 441.89];
		$image_y_arr = [17.5, 207.5, 397.5]; // 190 diff

		// $coupon_code_x_arr = [340, 741.89];
		$coupon_code_x_arr = [338, 739.89];
		// $coupon_code_y_arr = [30, 220, 410]; // 12.5 diff
		$coupon_code_y_arr = [30.5, 220.5, 410.5]; // 12.5 diff

		$qr_x_arr = [349.5, 751.39];
		$qr_y_arr = [85, 275, 465]; // 67.5 diff
		
		$scope_x_arr = [275, 676.89];
		// $scope_y_arr = [180, 370, 560]; // 162.5 diff
		$scope_y_arr = [179, 369, 559]; // 162.5 diff

		$pdf        = new Pdf('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetMargins(5, 10, 5, true);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetTitle($header_id);
        $pdf->SetAutoPageBreak(true);
        $pdf->SetAuthor('Chookstogo, Inc.');
        // $pdf->SetDisplayMode('real', 'default');
        $pdf->SetDisplayMode('real');
        $pdf->SetFont('helvetica', 'B', 14, '', 'false');

        // $custom_layout = [ 215.9, 92.9 ];
        
		$custom_layout = array(  595.276,   841.890);

        $style = [ 
            'border'  => false,
            'padding' => 0,
            /*'fgcolor' => array(237, 31, 36),*/
            'bgcolor' => false
        ];

        $select = "a.*, a1.*, c.coupon_scope_masking,
        IF(a.is_nationwide = 1, 
            'NATIONWIDE', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bcs',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'OVEN ROASTED CHICKEN',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";

        $join = array(
			'coupon_category_tbl a1' => 'a.coupon_cat_id = a1.coupon_cat_id AND a1.coupon_cat_status = 1',
			'coupon_transaction_details_tbl b' => 'a.coupon_id = b.coupon_id',
			'coupon_transaction_header_tbl c' => 'b.coupon_transaction_header_id = c.coupon_transaction_header_id AND c.coupon_transaction_header_id = '.$header_id
		);

        $coupon_result      = $this->main->get_join('coupon_tbl a', $join, false, FALSE, FALSE, $select);

		$i = 1;
		$add_page = TRUE;
		if(!empty($coupon_result)){
			foreach($coupon_result as $coupon){

				if($add_page) $pdf->AddPage('L', $custom_layout);

				if(date('Y-m', strtotime($coupon->coupon_start)) == date('Y-m', strtotime($coupon->coupon_end))){
					$month = date('M.', strtotime($coupon->coupon_start));
					$year = date('Y', strtotime($coupon->coupon_start));
					$day_start = date('d', strtotime($coupon->coupon_start));
					$day_end = date('d', strtotime($coupon->coupon_end));
					$valid_till = $month . ' ' . $day_start . ' to ' . $day_end . ', ' . $year; 
				}else{
					$year_start = date('Y', strtotime($coupon->coupon_start));
					$year_end = date('Y', strtotime($coupon->coupon_end));
					if($year_start == $year_end){
						$valid_till = date_format(date_create($coupon->coupon_start),"M. d") . ' - ' . date_format(date_create($coupon->coupon_end),"M. d, Y");
					}else{
						$valid_till = date_format(date_create($coupon->coupon_start),"M. d, Y") . ' - ' . date_format(date_create($coupon->coupon_end),"M. d, Y");
					}
				}
				
				$design      = $coupon->coupon_cat_design;
				$back_design = $coupon->coupon_cat_design_back;
				
				
				if($coupon->coupon_type_id == 1){
		
					if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
						$value = $coupon->coupon_amount.'%';
						if ($coupon->coupon_amount == 100) {
							$additional_details = 'AMOUNT : 1 ' . $coupon->products;
						} else {
							$additional_details = 'AMOUNT : ' . $value . ' Discount';
						}
					} else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
						$value = 'P' . $coupon->coupon_amount;
						$additional_details = 'AMOUNT : ' . $value . ' Discount';
					}
				}elseif($coupon->coupon_type_id == 2){
					if ($coupon->coupon_value_type_id == 1) { // PERCENTAGE
						$value = $coupon->coupon_amount.'%';
						if ($coupon->coupon_amount == 100) {
							$additional_details = 'AMOUNT : 1 ' . $coupon->products;
						} else {
							$additional_details = 'AMOUNT : ' . $value . ' Discount for' . $coupon->products;
						}
					} else if ($coupon->coupon_value_type_id == 2) { // FLAT AMOUNT
						$value = 'P' . $coupon->coupon_amount;
						$additional_details = 'AMOUNT : ' . $value . ' Discount for ' . $coupon->products;
					}
				}
				
				if(!$coupon->coupon_scope_masking){
					$coupon_bcs = explode(', ', $coupon->bcs);
					$coupon_bcs_count = count($coupon_bcs);
					$bcs = '';
					for ($index = 0; $index < $coupon_bcs_count; $index++) {
						if ($index == 0) {
							$bcs .= $coupon_bcs[$index];
						} elseif ($index == ($coupon_bcs_count - 1) && $index != 0) {
							$bcs .= ' and ' . $coupon_bcs[$index];
						} else {
							$bcs .= ', ' . $coupon_bcs[$index];
						}
					}
				} else {
					$bcs = $coupon->coupon_scope_masking;
				}


				$odd = $i % 2;
				if($odd){
					$image_x = $image_x_arr[0];
					$coupon_code_x = $coupon_code_x_arr[0];
					$qr_x = $qr_x_arr[0];
					$scope_x = $scope_x_arr[0];
				} else {
					$image_x = $image_x_arr[1];
					$coupon_code_x = $coupon_code_x_arr[1];
					$qr_x = $qr_x_arr[1];
					$scope_x = $scope_x_arr[1];
				}

				if($i < 3){ // first row
					$image_y = $image_y_arr[0];
					$coupon_code_y = $coupon_code_y_arr[0];
					$qr_y = $qr_y_arr[0];
					$scope_y = $scope_y_arr[0];
				}elseif($i < 5){
					$image_y = $image_y_arr[1];
					$coupon_code_y = $coupon_code_y_arr[1];
					$qr_y = $qr_y_arr[1];
					$scope_y = $scope_y_arr[1];
				}else{
					$image_y = $image_y_arr[2];
					$coupon_code_y = $coupon_code_y_arr[2];
					$qr_y = $qr_y_arr[2];
					$scope_y = $scope_y_arr[2];
				}

				$pdf->setJPEGQuality(100);
				// $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
				$pdf->Image($design, $image_x, $image_y, 360, 180, 'JPG', '', '', true, 150, '', false, false, 0, false, false, false);
				$pdf->SetFont('helvetica', '', 24);
				$pdf->Text($coupon_code_x, $coupon_code_y, $coupon->coupon_code);
				$pdf->write2DBarcode($coupon->coupon_code, 'QRCODE,H', $qr_x, $qr_y, 30, 30, $style, 'N');
				$pdf->SetFont('helvetica', '', 12);
				$pdf->Text($scope_x, $scope_y, $bcs);
				
				if($i==6){
					$i = 1;
					$add_page = TRUE;
				} else {
					$i++;
					$add_page = FALSE;
				}
			}
		}

        //$pdf->writeHTML($html, true, false, true, false, '');

        $invalid_chars = [ '<' ,'>' ,':' ,'"' ,'/' ,'\\' ,'|' ,'?' ,'*', ' ', '[', ']', '\'', '(', ')' ];
        $cleaned_name  = str_replace($invalid_chars, '-', $coupon->coupon_name);
        $date_created = strtotime($coupon->coupon_added);
        $file_name     = $cleaned_name . '_' . $coupon->coupon_code . '_' . $date_created . '.pdf';

        // $pdf->Output($file_name , 'I');
        $pdf->Output($file_name , 'D');

        /*$cwd          = getcwd();
        $date_created = strtotime($coupon->coupon_added);

        $invalid_chars = [ '<' ,'>' ,':' ,'"' ,'/' ,'\\' ,'|' ,'?' ,'*', ' ', '[', ']', '\'', '(', ')' ];
        $cleaned_name  = str_replace($invalid_chars, '-', $coupon->coupon_name);
        $file_name     = $cleaned_name . '_' . $coupon->coupon_code . '_' . $date_created . '.pdf';
        $save_path     = '/assets/coupons/' . $file_name;

        $pdf->Output($cwd . $save_path, 'F');
        return $save_path;*/
    }

	public function check_php($id){
		//phpinfo();
		$id_array = [1, 2, 3];
		if(in_array($id, $id_array)){
			echo 'yes';
		}
		exit;
		// error_reporting(E_ALL); 
		// ini_set( 'display_errors','1');
		// /* Create a new imagick object */
		// $im = new Imagick();
		// /* Create new image. This will be used as fill pattern */
		// $im->newPseudoImage(50, 50, "gradient:red-black");
		// /* Create imagickdraw object */
		// $draw = new ImagickDraw();
		// /* Start a new pattern called "gradient" */
		// $draw->pushPattern('gradient', 0, 0, 50, 50);
		// /* Composite the gradient on the pattern */
		// $draw->composite(Imagick::COMPOSITE_OVER, 0, 0, 50, 50, $im);
		// /* Close the pattern */
		// $draw->popPattern();
		// /* Use the pattern called "gradient" as the fill */
		// $draw->setFillPatternURL('#gradient');
		// /* Set font size to 52 */
		// $draw->setFontSize(52);
		// /* Annotate some text */
		// $draw->annotation(20, 50, "Hello World!");
		// /* Create a new canvas object and a white image */
		// $canvas = new Imagick();
		// $canvas->newImage(350, 70, "white");
		// /* Draw the ImagickDraw on to the canvas */
		// $canvas->drawImage($draw);
		// /* 1px black border around the image */
		// $canvas->borderImage('black', 1, 1);
		// /* Set the format to PNG */
		// $canvas->setImageFormat('png');
		// /* Output the image */
		// header("Content-Type: image/png");
		// echo $canvas;


		
	}

	public function check_pdf_to_image(){
		$im = new Imagick();

		$im->setResolution(300,300);
		$im->readimage('document.pdf[0]'); 
		$im->setImageFormat('jpeg');    
		$im->writeImage('thumb.jpg'); 
		$im->clear(); 
		$im->destroy();
	}

	public function download_pdf_to_image($header_id){

		
		$info = $this->_require_login();
    	ini_set('max_execution_time', 0); 
        ini_set('memory_limit','2048M');
		$this->load->library('zip');
		$this->zip->compression_level = 9;

		$trans_id = decode($header_id);

		

		$im = new Imagick();
		

		$join_voucher = array(
    		'coupon_transaction_details_tbl b' => 'a.coupon_transaction_header_id = b.coupon_transaction_header_id AND b.coupon_transaction_details_status = 1 AND a.coupon_transaction_header_status = 1 AND a.coupon_transaction_header_id = ' . $trans_id,
    		'coupon_tbl c'                     => 'b.coupon_id = c.coupon_id AND c.coupon_status = 1'
    	);
    	$get_voucher = $this->main->get_join('coupon_transaction_header_tbl a', $join_voucher);

		if(empty($get_voucher)){
			echo 'No Record found.';
			exit;
		}

		// echo "<pre>";
		// print_r($get_voucher);
		// echo "</pre>";
		// exit;
		$zip_path =  FCPATH . '/assets/zip-coupons-img/';
		if (!is_dir($zip_path)) {
			mkdir($zip_path, 0777, true);
		}
		$zip_file_name = $get_voucher[0]->coupon_transaction_header_name;

		$output_path = FCPATH . '/assets/coupons/images/'.$get_voucher[0]->coupon_transaction_header_id.' '.$get_voucher[0]->coupon_transaction_header_name.'/';

		if (!is_dir($output_path)) {
			mkdir($output_path, 0777, true);
		} else {
			array_map('unlink', glob("$output_path/*.*"));
			// rmdir($output_path);
		}

		foreach($get_voucher as $row){
    		$file_name = FCPATH . '/' . $row->coupon_pdf_path;
            
			// echo $file_name;
			
			$output_file = $output_path.$row->coupon_code.'.jpg';
    		// echo $output.'<br>';
			// if (strlen($file_name) < 10) {
			// 	echo 'content is not readable.';
			// }else {
			// 	echo 'content is readable.';
			// }
			$im->setResolution(165,165);
			$im->readimage($file_name);
			
			$im->setImageType(\Imagick::IMGTYPE_TRUECOLORMATTE);
			// $im->setImageType(\Imagick::IMGTYPE_OPTIMIZE);
			$im->setImageFormat('jpeg');
			// $im->setImageCompression(imagick::COMPRESSION_JPEG); 
			// $im->setImageCompressionQuality(100);
			$im->writeImage($output_file); 
			$im->clear(); 
			
			$this->zip->read_file($output_file);
    	}

		$im->destroy();
		array_map('unlink', glob("$output_path/*.*"));

		// $this->zip->archive($zip_path . $zip_file_name . '.zip');
        
		// array_map('unlink', glob("$zip_path/*.*"));
		
        $this->zip->download($zip_file_name . '.zip');

		// rmdir($output_path);

		// echo "PDF already converted to jpeg.";

	}

    public function modal_coupon_trans_details($id)
    {
        $info      = $this->_require_login();
        $id        = clean_data(decode($id));
        $parent_db = $GLOBALS['parent_db'];
        $where     = ['coupon_transaction_header_id' => $id];
        $check_id  = $this->main->check_data('coupon_transaction_header_tbl a', $where, TRUE);
        if ($check_id['result']) {

            $coupon_trans_select = "*,
			IF(c.coupon_value_type_id=1,CONCAT(b.coupon_amount,'%'),CONCAT('P',b.coupon_amount)) AS coupon_amount,
            IF(b.is_nationwide = 1, 
                'Nationwide', 
                (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
            IF(b.is_orc = 1, 
                CONCAT_WS(', ', 'ORC', (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
                (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1)) AS 'products',
            (SELECT GROUP_CONCAT(x.brand_name SEPARATOR ', ') FROM coupon_brand_tbl z JOIN {$parent_db}.brand_tbl x ON z.brand_id = x.brand_id WHERE z.coupon_id = b.coupon_id AND z.coupon_brand_status = 1) AS brands";

            $coupon_join = [
                'coupon_tbl b'            => 'b.coupon_id = a.coupon_id',
                'coupon_value_type_tbl c' => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $id,
                'coupon_holder_type_tbl d' => 'd.coupon_holder_type_id = b.coupon_holder_type_id',
				'company_tbl e'            => 'b.company_id = e.company_id',
            ];

            $data['trans_details'] = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join, FALSE, 'b.coupon_added DESC', FALSE, $coupon_trans_select);
            $data['trans_header']  = $check_id['info'];

            $html = $this->load->view('creator/coupon/coupon_trans_modal_content', $data, TRUE);

            $result = [
                'result' => TRUE,
                'html'   => $html
            ];
        } else {
            $result = [
                'result' => FALSE
            ];
        }
        
        echo json_encode($result);
    }

    private function _success_coupon_trans_details($id)
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];
        $where     = ['coupon_transaction_header_id' => $id];

        $coupon_trans_select = "*,
		IF(c.coupon_value_type_id=1,CONCAT(b.coupon_amount,'%'),CONCAT('P',b.coupon_amount)) AS coupon_amount,
        IF(b.is_nationwide = 1, 
            'Nationwide', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
        IF(b.is_orc = 1, 
            CONCAT_WS(', ', 'ORC', (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1)) AS 'products',
        (SELECT GROUP_CONCAT(x.brand_name SEPARATOR ', ') FROM coupon_brand_tbl z JOIN {$parent_db}.brand_tbl x ON z.brand_id = x.brand_id WHERE z.coupon_id = b.coupon_id AND z.coupon_brand_status = 1) AS brands";

        $coupon_join = [
            'coupon_tbl b'            => 'b.coupon_id = a.coupon_id',
            'coupon_value_type_tbl c' => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $id,
            'coupon_holder_type_tbl d' => 'd.coupon_holder_type_id = b.coupon_holder_type_id',
			'company_tbl e'				=> 'b.company_id = e.company_id'
        ];

        $data['trans_details'] = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join, FALSE, 'b.coupon_added DESC', FALSE, $coupon_trans_select);

        return $this->load->view('creator/coupon/success_product_coupon_trans_details', $data, TRUE);

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

    private function _send_coupon_sms($coupon_id)
    {
        $parent_db = $GLOBALS['parent_db'];
        $coupon_select = "*,
        IF(a.is_nationwide = 1, 
            'Nationwide', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'ORC',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";

        $coupon = $this->main->check_data('coupon_tbl a', ['a.coupon_id' => $coupon_id], TRUE, $coupon_select);
        if ($coupon['result']) {
            $number = $coupon['info']->coupon_holder_contact;
            $code   = $coupon['info']->coupon_code;
            if ($number != '') {

                $coupon_sms_log_data = [
                    'coupon_id'      => $coupon_id,
                    'contact_number' => $number,
                    'coupon_sms_log_added' => date_now(),
                    'coupon_sms_log_status' => 1
                ];
                $sms_log_result = $this->main->insert_data('coupon_sms_log_tbl', $coupon_sms_log_data);
                if ($sms_log_result) {
                    if ($coupon['info']->coupon_value_type_id == 1) { // PERCENTAGE
                        $value = $coupon['info']->coupon_amount.'%';
                    } else if ($coupon['info']->coupon_value_type_id == 2) { // FLAT AMOUNT
                        $value = 'P'.$coupon['info']->coupon_amount;
                    }

                    $location = ($coupon['info']->bc == 'Nationwide') ? $coupon['info']->bc : 'sa ' . $coupon['info']->bc;

                    if ($coupon['info']->coupon_type_id == 1) {
                        $message = 'Hi ka-Chooks! Ikaw ay nakatanggap ng Chooks-to-Go '.SYS_NAME.' for ' . $value . '. Ang iyong voucher code ay ' . $code . ' at ito ay valid lamang ' . $coupon['info']->bc;
                    } else {
                        if ($coupon['info']->coupon_value_type_id == 1 && $coupon['info']->coupon_amount == '100') { // PERCENTAGE
                            $message = 'Hi ka-Chooks! Ikaw ay nakatanggap ng Chooks-to-Go '.SYS_NAME.' for one(1) ' . $coupon['info']->products . '. Ang iyong voucher code ay ' . $code . ' at ito ay valid lamang ' . $location;
                        } else {
                            $message = 'Hi ka-Chooks! Ikaw ay nakatanggap ng Chooks-to-Go '.SYS_NAME.' for ' . $value . ' '  . $coupon['info']->products . '. Ang iyong voucher code ay ' . $code . ' at ito ay valid lamang ' . $location;
                        }
                    }
                    itexmo($number, $message, 'BAVI-TEST4321');
                }

            }
        } 
    }

    private function _email_transaction_coupon($transaction_id)
    {
        $parent_db = $GLOBALS['parent_db'];
        ini_set('max_execution_time', 0); 
        ini_set('memory_limit','2048M');
        $config = email_config();
        
        $cwd = getcwd();
        $coupon_select = "*,
        IF(a.is_nationwide = 1, 
            'Nationwide', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'ORC',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";

        $coupon_join = ['coupon_transaction_details_tbl b' => 'b.coupon_id = a.coupon_id AND b.coupon_transaction_header_id = ' . $transaction_id];
        $coupon      = $this->main->get_join('coupon_tbl a', $coupon_join, TRUE, FALSE, FALSE, $coupon_select);
        $coupons     = $this->main->get_join('coupon_tbl a', $coupon_join, FALSE, FALSE, FALSE, $coupon_select);


        $data['code']     = $coupon->coupon_code;
        $data['start']    = date_format(date_create($coupon->coupon_start),"M d, Y");
        $data['end']      = date_format(date_create($coupon->coupon_end),"M d, Y");
        $data['bc']       = $coupon->bc;
        $data['products'] = $coupon->products;
        $data['title']    = 'Chooks-To-Go '.SYS_NAME.'';
        $data['name']     = $coupon->coupon_holder_name;

        $content  = $this->load->view('email_templates/product_coupon', $data, TRUE);

        $this->load->library('email', $config);
        $this->email->set_newline("\r\n");

        // Set to, from, message, etc.
        $email = $coupon->coupon_holder_email;
        $this->email->from('noreply@chookstogoinc.com.ph', 'Chooks-to-Go '.SYS_NAME.'');
        $this->email->to($email);

        $this->email->subject($data['title']);
        $this->email->message($content);

        foreach ($coupons as $row) {
            $this->email->attach($cwd . $row->coupon_pdf_path);
        }

        if ($email != '') {
            $result = $this->email->send();
            echo $this->email->print_debugger();
            die();
        }

		$result = '';
        if ($result) {
            $coupon_email_log_data = [
                'coupon_transaction_header_id' => $transaction_id,
                'email'          => $email,
            ];
            $email_log_result = $this->main->insert_data('coupon_transaction_email_log_tbl', $coupon_email_log_data);
        }
        return $result;
    }

    private function _email_coupon($coupon_id)
    {
        $parent_db = $GLOBALS['parent_db'];
        ini_set('max_execution_time', 0); 
        ini_set('memory_limit','2048M');
        $config = email_config();

        $coupon_select = "*,
        IF(a.is_nationwide = 1, 
            'Nationwide', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'ORC',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";

        $coupon_join = ['coupon_transaction_details_tbl b' => 'b.coupon_id = a.coupon_id AND a.coupon_id = ' . $coupon_id];
        $coupon      = $this->main->get_join('coupon_tbl a', $coupon_join, FALSE, FALSE, FALSE, $coupon_select);

        $data['code']     = $coupon->coupon_code;
        $data['start']    = date_format(date_create($coupon->coupon_start),"M d, Y");
        $data['end']      = date_format(date_create($coupon->coupon_end),"M d, Y");
        $data['bc']       = $coupon->bc;
        $data['products'] = $coupon->products;
        $data['title']    = 'Chooks-To-Go '.SYS_NAME.'';
        $data['name']     = $coupon->coupon_holder_name;

        $content = $this->load->view('email_templates/standard_coupon', $data, TRUE);

        $this->load->library('email', $config);
        $this->email->set_newline("\r\n");

        // Set to, from, message, etc.
        $email = $coupon->coupon_holder_email;
        $this->email->from('noreply@chookstogoinc.com.ph', 'Chooks-to-Go '.SYS_NAME.'');
        $this->email->to($email);

        $this->email->subject($data['title']);
        $this->email->message($content);

        $coupon = $this->main->get_join('coupon_tbl', [ 'coupon_id' => $coupon_id ]);
        $cwd    = getcwd();
        $this->email->attach($cwd . $coupon->coupon_pdf_path);

        $result = FALSE;
        if ($email != '') {
            $coupon_email_log_data = [
                'coupon_id' => $coupon_id,
                'email'     => $email,
                'coupon_email_log_added' => date_now(),
                'coupon_email_log_status' => 1
            ];
            $email_log_result = $this->main->insert_data('coupon_email_log_tbl', $coupon_email_log_data);
            if ($email_log_result) {
                $result = $this->email->send();
            }
        }


        if ($email != '') {
            $result = $this->email->send();
        }

        if ($result) {
            $coupon_email_log_data = [
                'coupon_id' => $coupon_id,
                'email'     => $email,
            ];
            $email_log_result = $this->main->insert_data('coupon_email_log_tbl', $coupon_email_log_data);
        }
        return $result;
    }

    public function web_validate_coupon()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
           show_404();
        }

        $parent_db   = $GLOBALS['parent_db'];
        $coupon_code = clean_data($this->input->post('code'));
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
                            $sms = 'Error Invalid '.SEC_SYS_NAME.' Type. Please try again';
                        }                        
                    }else{
                        $result = 0;
                        $sms = 'Sorry '.SYS_NAME.' redemption has not yet started. Redemption start on ' . $coupon_start . '.';
                    }
                }else{ //Invalid coupon is expired
                    
                    $result = 0;
                    $sms = 'Sorry '.SYS_NAME.' was already expired.';
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
					$redeemer_ts = $check_voucher_code['info']->redeemed_coupon_log_added;
					// $sms = 'Sorry '.SYS_NAME.' was already redeemed by '.$redeemer_number.' on '.date_format(date_create($redeemer_ts),"M d, Y h:i:s A").'.';
					$sms = 'Sorry, Ang '.SEC_SYS_NAME.' CODE ay REDEEMED na noong '.date_format(date_create($redeemer_ts),"M d, Y").' sa oras na '.date_format(date_create($redeemer_ts),"h:i:s A").' ng '.$redeemer_number.'.';
				} else {
					$sms = 'Sorry, Ang '.SEC_SYS_NAME.' CODE ay REDEEMED na.';
				}
            }
        }else{
            //Invalid and already redeem
            $result = 0;
            $sms = 'Sorry mali ang '.SEC_SYS_NAME.' CODE. Subukan ulit ang pag-redeem.';
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
                    '.SEC_SYS_NAME.'SYS_NAME.' Code has already been redeemed
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
                                    $sms = 'Error Invalid '.SEC_SYS_NAME.' Type. Please try again';
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
                        $sms = 'Sorry '.SYS_NAME.' redemption has not yet started. Redemption start on ' . $coupon_start . '.';
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
                    $sms = 'Sorry '.SYS_NAME.' was already expired.';

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
					$redeemer_ts = $check_voucher_code['info']->redeemed_coupon_log_added;
					// $sms = 'Sorry '.SYS_NAME.' was already redeemed by '.$redeemer_number.' on '.date_format(date_create($redeemer_ts),"M d, Y h:i:s A").'.';
					$sms = 'Sorry, Ang '.SEC_SYS_NAME.' CODE ay REDEEMED na noong '.date_format(date_create($redeemer_ts),"M d, Y").' sa oras na '.date_format(date_create($redeemer_ts),"h:i:s A").' ng '.$redeemer_number.'.';
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
            $sms = 'Sorry mali ang '.SEC_SYS_NAME.' CODE. Subukan ulit ang pag-redeem.';

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

	function _response_msg($value_type, $category, $reference_no, $amount_product, $location){
		if($value_type == 1){ // PERCENTAGE
			$old_sms = 'Ang '.SYS_NAME.' mo ay valid ng '.$amount_product .' at valid sa '.$location.'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
			
			$sms = 'Ang '. $category . ' ay valid ng '.$amount_product .' at '.$location.' scope. Maaari mo ng itransact sa POS VOUCHER MODULE ang approval code na ' . $reference_no.'.';
		} elseif ($value_type == 2){
			$old_sms = 'Ang '.SYS_NAME.' mo ay valid worth P' . $amount_product . ' at valid sa '.$location.'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
			
			$sms = 'Ang '. $category . ' ay valid worth P' . $amount_product . ' at '.$location.' scope. Maari mo nang iinput sa POS VOUCHER MODULE ang approval code na ' . $reference_no.'.';
			
		}
		return $sms;

	}

	function _get_prod($coupon_id){
        $main_db = 'chooks_delivery_db';
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
        $main_db = 'chooks_delivery_db';
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

	public function approve_coupon(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_id = clean_data(decode($this->input->post('id')));

            if (empty($coupon_id)) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->coupon_status == 1) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' is already Approve', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

			if(in_array($check_id['info']->coupon_cat_id, paid_category())){
                if ($this->input->post('invoice_num') == NULL || empty($this->input->post('invoice_num'))) {
                    $alert_message = $this->alert_template('Invoice Number is Required', FALSE);
                    $this->session->set_flashdata('message', $alert_message);
                    redirect($_SERVER['HTTP_REFERER']);
                }

                if ($check_id['info']->coupon_holder_type_id != '4') {
                    if ($this->input->post('sap_doc_no') == NULL || empty($this->input->post('sap_doc_no'))) {
                        $alert_message = $this->alert_template('Document Number is Required', FALSE);
                        $this->session->set_flashdata('message', $alert_message);
                        redirect($_SERVER['HTTP_REFERER']);
                    }
                }

                $invoice_number =  clean_data($this->input->post('invoice_num'));
                $sap_doc_no     =  clean_data($this->input->post('sap_doc_no'));
            } else {

                $invoice_number =  '';
                $sap_doc_no     =  '';
            }


			$set    = [
                'coupon_status'  => 1,
                'invoice_number' => $invoice_number,
                'sap_doc_no'     => $sap_doc_no,
            ];

			$where  = ['coupon_id' => $coupon_id];
			$result = $this->main->update_data('coupon_tbl', $set, $where);
            if ($result) {
                $this->_send_coupon_creator_notification($coupon_id);
                $this->_upload_coupon_attachment($coupon_id, 2);
            }

			$msg    = ($result == TRUE) ? '<div class="alert alert-success">'.SEC_SYS_NAME.' successfully Activated.</div>' : '<div class="alert alert-danger">Error please try again!</div>';
            $this->session->set_flashdata('message', $msg);
            redirect($_SERVER['HTTP_REFERER'].'#nav-approved');
		}else{
			redirect('admin');
		}
    }

	public function approve_transaction(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_transaction_header_id = clean_data(decode($this->input->post('id')));

            if (empty($coupon_transaction_header_id)) {
                $alert_message = $this->alert_template('Transaction Header ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $coupon_transaction_header_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template('Transaction Header ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->coupon_transaction_header_status == 1) {
                $alert_message = $this->alert_template('Transaction Header is already Approve', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

			if(in_array($check_id['info']->coupon_cat_id, paid_category())){
                $coupon_join         = [ 'coupon_tbl b' => 'b.coupon_id = a.coupon_id AND a.coupon_transaction_header_id = ' . $coupon_transaction_header_id ];
                $transaction_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join, TRUE);

                if ($transaction_details->coupon_holder_type_id != 4) {
                    if ($this->input->post('sap_doc_no') == NULL || empty($this->input->post('sap_doc_no'))) {
                        $alert_message = $this->alert_template('Document Number is Required' . $transaction_details->coupon_holder_type_id, FALSE);
                        $this->session->set_flashdata('message', $alert_message);
                        redirect($_SERVER['HTTP_REFERER']);
                    }
                }

                if ($transaction_details->coupon_holder_type_id != 1) {
                    if ($this->input->post('invoice_num') == NULL || empty($this->input->post('invoice_num'))) {
                        $alert_message = $this->alert_template('Invoice Number is Required', FALSE);
                        $this->session->set_flashdata('message', $alert_message);
                        redirect($_SERVER['HTTP_REFERER']);
                    }
                }

                $invoice_number =  clean_data($this->input->post('invoice_num'));
                $sap_doc_no     =  clean_data($this->input->post('sap_doc_no'));
            } else {

                $invoice_number =  '';
                $sap_doc_no     =  '';
            }


            $this->db->trans_start();

			$set       = [
                'coupon_transaction_header_status' => 1,
                'invoice_number'                   => $invoice_number,
                'sap_doc_no'                       => $sap_doc_no,
            ];
			$where     = ['coupon_transaction_header_id' => $coupon_transaction_header_id];
			$result    = $this->main->update_data('coupon_transaction_header_tbl', $set, $where);
			$action_id = ($check_id['info']->coupon_transaction_header_status == 0) ? 3 : 5 ;
            $this->_store_transaction_action_log($action_id, $coupon_transaction_header_id);

            if ($result == TRUE) {
                $coupon_join = [
                    'coupon_tbl b'            => 'b.coupon_id = a.coupon_id',
                    'coupon_value_type_tbl c' => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $coupon_transaction_header_id
                ];

                $transaction_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join);
                foreach ($transaction_details as $row) {
                    $set    = [
                        'coupon_status'  => 1,
                        'invoice_number' => $invoice_number,
                        'sap_doc_no'     => $sap_doc_no,
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);
                }

                if ($action_id == 5) {
                    $this->_send_transaction_creator_notification($coupon_transaction_header_id);
                }

                if (isset($_FILES['attachment']) && $_FILES['attachment']['name'][0] != '') {
                    $this->_upload_transaction_attachment($coupon_transaction_header_id, 3);
                }
            }

            if($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                $message       = 'Error please try again!';
                $alert_message = $this->alert_template($message, FALSE);
            }else{
                $this->db->trans_commit();
                $message       = 'Transaction successfully Activated.';
                $alert_message = $this->alert_template($message, TRUE);
            }
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER'].'#nav-approved');
		}else{
			redirect('admin');
		}
    }

	public function deactivate_transaction(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_transaction_header_id = clean_data(decode($this->input->post('id')));

            if (empty($coupon_transaction_header_id)) {
                $alert_message = $this->alert_template('Transaction Header ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $coupon_transaction_header_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template('Transaction Header ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->coupon_transaction_header_status == 0) {
                $alert_message = $this->alert_template('Transaction Header is already deactivated', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $this->db->trans_start();

			$set    = ['coupon_transaction_header_status' => 0];
			$where  = ['coupon_transaction_header_id' => $coupon_transaction_header_id];
			$result = $this->main->update_data('coupon_transaction_header_tbl', $set, $where);
            
            if ($result == TRUE) {
                $coupon_join = [
                    'coupon_tbl b'            => 'b.coupon_id = a.coupon_id',
                    'coupon_value_type_tbl c' => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $coupon_transaction_header_id
                ];

                $transaction_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join);
                foreach ($transaction_details as $row) {
                    $set    = ['coupon_status' => 0];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);
                    if ($result) {
                        $this->_store_coupon_action_log(4, $row->coupon_id);
                    }
                }
            }

            if($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                $message       = 'Error please try again!';
                $alert_message = $this->alert_template($message, FALSE);
            }else{
                $this->db->trans_commit();
                $message       = 'Transaction successfully Activated.';
                $alert_message = $this->alert_template($message, TRUE);
            }
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER'].'#nav-inactive');
		}else{
			redirect('creator');
		}
    }

	public function _get_customers_selection($customer_id=NULL){
		
		$customers         = $this->main->get_data('customers_tbl a', ['customer_status' => 1]);
		$customer_select = '<option value="">Select Customer</option>';
		foreach ($customers as $row) {
			$selected = '';
			
			if($customer_id == $row->customer_id) $selected = 'selected';
			$customer_select .= '<option '.$selected.' value="'.encode($row->customer_id).'">'.$row->customer_name.'</option>';
		}

		return $customer_select;
	}

	public function _get_payment_types_selection($payment_type_id=NULL, $rec_payment_type_id=NULL){
		$where               = ['payment_type_status' => 1];
		$payment_types         = $this->main->get_data('payment_types_tbl', $where);
		$payment_select = '';
		foreach ($payment_types as $row) {
			$selected = '';
			if($payment_type_id){
				if($payment_type_id == $row->payment_type_id) $selected = 'selected';
			} else {
				if($rec_payment_type_id == $row->payment_type_id) $selected = 'selected';
			}
			$payment_select .= '<option '.$selected.' value="'.encode($row->payment_type_id).'">'.$row->payment_type.'</option>';
		}

		return $payment_select;
	}

    public function get_additional_fields($coupon_category, $holder_type)
    {
        $info              = $this->_require_login();
        $coupon_cat        = clean_data(decode($coupon_category));
        $check_cat         = $this->main->check_data('coupon_category_tbl', ['coupon_cat_id' => $coupon_cat]);
        $holder_type       = clean_data(decode($holder_type));
        $check_holder_type = $this->main->check_data('coupon_holder_type_tbl', ['coupon_holder_type_id' => $holder_type]);

		

        $result            = '';
        if ($check_cat && $check_holder_type) {
			if ($coupon_cat == '1' || $coupon_cat == '4') { // GIFT VOUCHER, MEAL EVOUCHER			
				$company_filter = ['company_status' => 1, 'company_class' => 1];
			} elseif(in_array($coupon_cat, paid_category())){ // PAID VOUCHER
			
				if($holder_type == 1){ // employee
					$company_filter = ['company_status' => 1, 'company_class' => 1];
				}
				// elseif($holder_type == 2 || $holder_type == 3){ // crew, non employee, external company
				// 	$company_filter = ['company_status' => 1, 'company_class' => 2];
				// }
				else {
					$company_filter = ['company_status' => 1];
				}
			}
			$companies         = $this->main->get_data("company_tbl", $company_filter);
			$company_select = '<option value="">Select</option>';
			foreach ($companies as $row) {
				$company_select .= '<option value="'.encode($row->company_id).'">'.$row->company_name.'</option>';
			}

			$payment_select = $this->_get_payment_types_selection();
			$payment_fields = $this->_get_payment_details_fields(NULL, NULL, NULL, $payment_select);

            if ($coupon_cat == '1' || $coupon_cat == '4') { // GIFT VOUCHER, MEAL EVOUCHER
                $result = '
						<div class="form-group">
                            <label for="">Requestor\'s Company : *</label>
                            <select name="company_id" class="form-control form-control-sm" required>
								'.$company_select.'
							</select>
                        </div>
						<div class="form-group">
							<label for="">Attachment : *</label><br>
							<div class="custom-file mb-3">
								<input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple required>
								<label class="custom-file-label" for="attachment[]">Choose file...</label>
							</div>
						</div>';
            } elseif(in_array($coupon_cat, paid_category())){ // PAID VOUCHER
                if ($holder_type == '4') { // BFFI or CLIENT WITH LATE PAYMENT
                    $result = '
						<div class="form-group">
                            <label for="">Requestor\'s Company : *</label>
                            <select name="company_id" class="form-control form-control-sm" required>
								'.$company_select.'
							</select>
                        </div> 
                        <div class="form-group">
                            <label for="">'.SEC_SYS_NAME.' Regular Amount : *</label>
                            <input type="number" name="voucher-regular-value" placeholder="Per '.SEC_SYS_NAME.' Regular Amount" class="form-control form-control-sm voucher-regular-value" min="0.01" step="0.01" required>
                        </div> 
                        <div class="form-group">
                            <label for="">'.SEC_SYS_NAME.' Paid Amount : *</label>
                            <input type="number" name="voucher-value" placeholder="Per '.SEC_SYS_NAME.' Paid Amount" class="form-control form-control-sm voucher-value" min="0.01" step="0.01" required>
                        </div> 
						<div class="form-group">
                            <label for="">Total Paid Amount : *</label>
                            <input type="number" name="total-voucher-value" readonly class="form-control form-control-sm total-voucher-value" placeholder="" min="0.01" step="0.01" required>
                        </div> 
                        <div class="form-group">
                            <label for="">Holder Address : *</label>
                            <input type="text" name="address" class="form-control form-control-sm" placeholder="" required>
                        </div> 
                        <div class="form-group">
                            <label for="">Holder TIN : *</label>
                            <input type="text" name="tin" class="form-control form-control-sm" placeholder="" required>
                        </div>
						<div class="payment-det-field-container">
							'.$payment_fields.'
						</div>
                        <div class="form-group">
                            <label for="">Invoice : *</label>
                            <input type="text" name="invoice_num" class="form-control form-control-sm" placeholder="" required>
                        </div>
                        <label for="">Attachment : *</label><br>
                        <div class="custom-file mb-3">
                            <input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple required>
                            <label class="custom-file-label" for="attachment[]">Choose file...</label>
                        </div>';
                } elseif ($holder_type == '1') { // BAVI EMPLOYEE
                    $result = '
						<div class="form-group">
                            <label for="">Requestor\'s Company : *</label>
                            <select name="company_id" class="form-control form-control-sm" required>
								'.$company_select.'
							</select>
                        </div>
						<div class="form-group">
                            <label for="">'.SEC_SYS_NAME.' Regular Amount : *</label>
                            <input type="number" name="voucher-regular-value" placeholder="Per '.SEC_SYS_NAME.' Regular Amount" class="form-control form-control-sm voucher-regular-value" min="0.01" step="0.01" required>
                        </div> 
                        <div class="form-group">
                            <label for="">'.SEC_SYS_NAME.' Paid Amount : *</label>
                            <input type="number" name="voucher-value" placeholder="Per '.SEC_SYS_NAME.' Paid Amount" class="form-control form-control-sm voucher-value" min="0.01" step="0.01" required>
                        </div> 
						<div class="form-group">
                            <label for="">Total Paid Amount : *</label>
                            <input type="number" name="total-voucher-value" readonly class="form-control form-control-sm total-voucher-value" placeholder="" min="0.01" step="0.01" required>
                        </div> 
                        <div class="form-group">
                            <label for="">Holder Address : *</label>
                            <input type="text" name="address" class="form-control form-control-sm" placeholder="" required>
                        </div> 
                        <div class="form-group">
                            <label for="">Holder TIN : *</label>
                            <input type="text" name="tin" class="form-control form-control-sm" placeholder="" required>
                        </div>
						<div class="payment-det-field-container">
							'.$payment_fields.'
						</div>
                        <label for="">Attachment : </label><br>
                        <div class="custom-file mb-3">
                            <input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple required>
                            <label class="custom-file-label" for="attachment[]">Choose file...</label>
                        </div>';
                } else {
                    $result = '
						<div class="form-group">
                            <label for="">Requestor\'s Company : *</label>
                            <select name="company_id" class="form-control form-control-sm" required>
								'.$company_select.'
							</select>
                        </div>
						<div class="form-group">
                            <label for="">'.SEC_SYS_NAME.' Regular Amount : *</label>
                            <input type="number" name="voucher-regular-value" placeholder="Per '.SEC_SYS_NAME.' Regular Amount" class="form-control form-control-sm voucher-regular-value" min="0.01" step="0.01" required>
                        </div> 
                        <div class="form-group">
                            <label for="">'.SEC_SYS_NAME.' Paid Amount : *</label>
                            <input type="number" name="voucher-value" placeholder="Per '.SEC_SYS_NAME.' Paid Amount" class="form-control form-control-sm voucher-value" min="0.01" step="0.01" required>
                        </div> 
						<div class="form-group">
                            <label for="">Total Paid Amount : *</label>
                            <input type="number" name="total-voucher-value" readonly class="form-control form-control-sm total-voucher-value" placeholder="" min="0.01" step="0.01" required>
                        </div> 
                        <div class="form-group">
                            <label for="">Holder Address : *</label>
                            <input type="text" name="address" class="form-control form-control-sm" placeholder="" required>
                        </div> 
                        <div class="form-group">
                            <label for="">Holder TIN : *</label>
                            <input type="text" name="tin" class="form-control form-control-sm" placeholder="" required>
                        </div>
						<div class="payment-det-field-container">
							'.$payment_fields.'
						</div>
                        <label for="">Attachment : *</label><br>
                        <div class="custom-file mb-3">
                            <input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple required>
                            <label class="custom-file-label" for="attachment[]">Choose file...</label>
                        </div>';
                }

            }
        }
        echo $result;
    }

    private function _upload_coupon_attachment($coupon_id, $sequence)
    {
        $check_coupon_id = $this->main->check_data('coupon_attachment_tbl', ['coupon_id' => $coupon_id]);
        if ($check_coupon_id) {
            $set   = [ 'coupon_attachment_status' => 0 ];
            $where = [ 'coupon_id' => $coupon_id, 'coupon_attachment_sequence' => $sequence ];
            $this->main->update_data('coupon_attachment_tbl', $set, $where);
        }

        $this->load->library('upload');
        $config['overwrite']     = 1;
        $config['upload_path']   = 'assets/documents/';
        $config['allowed_types'] = 'jpg|jpeg|png|pdf';
        $files = $_FILES['attachment'];
        foreach ($files['name'] as $key => $image) {
            $_FILES['files[]']['name']     = $files['name'][$key];
            $_FILES['files[]']['type']     = $files['type'][$key];
            $_FILES['files[]']['tmp_name'] = $files['tmp_name'][$key];
            $_FILES['files[]']['error']    = $files['error'][$key];
            $_FILES['files[]']['size']     = $files['size'][$key];

            $file_name             = generate_random(7) . '_' . date('Y-m-d');
            $uploaded_file         = $_FILES['files[]']['name'];
            $ext                   = pathinfo($uploaded_file, PATHINFO_EXTENSION);
            $file_path             = "assets/documents/{$file_name}.{$ext}";
            $check_file_name       = $this->main->check_data('coupon_attachment_tbl', ['coupon_attachment_path' => $file_path]);
            $check_trans_file_name = $this->main->check_data('coupon_transaction_header_attachment_tbl', ['coupon_transaction_header_attachment_path' => $file_path]);
            while ($check_file_name && $check_trans_file_name) {
                $file_name             = generate_random(7) . '-' . date('Y-m-d');
                $file_path             = "assets/documents/{$file_name}.{$ext}";
                $check_file_name       = $this->main->check_data('coupon_attachment_tbl', ['coupon_attachment_path' => $file_path]);
                $check_trans_file_name = $this->main->check_data('coupon_transaction_header_attachment_tbl', ['coupon_transaction_header_attachment_path' => $file_path]);
            }

            $config['file_name'] = $file_name;
            $this->upload->initialize($config);
            $result = $this->upload->do_upload('files[]');
            if ($result) {
                $data = [
                    'coupon_id'                  => $coupon_id,
                    'coupon_attachment_name'     => $files['name'][$key],
                    'coupon_attachment_path'     => $file_path,
                    'coupon_attachment_status'   => 1,
                    'coupon_attachment_sequence' => $sequence,
                    'coupon_attachment_added'    => date_now(),
                ];
                $this->main->insert_data('coupon_attachment_tbl', $data);
            } else {
                $alert_message = $this->alert_template($this->upload->display_errors(), FALSE);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }

    private function _upload_transaction_attachment($transaction_id, $sequence)
    {
        $check_trans_id = $this->main->check_data('coupon_transaction_header_attachment_tbl', ['coupon_transaction_header_id' => $transaction_id]);
        if ($check_trans_id) {
            $set   = [ 'coupon_transaction_header_attachment_status' => 0 ];
            $where = [ 'coupon_transaction_header_id' => $transaction_id , 'coupon_transaction_header_attachment_process_sequence' => $sequence ];
            $this->main->update_data('coupon_transaction_header_attachment_tbl', $set, $where);
        }

        $this->load->library('upload');
        $config['overwrite']     = 1;
        $config['upload_path']   = 'assets/documents/';
        $config['allowed_types'] = 'jpg|jpeg|png|pdf';
        $files = $_FILES['attachment'];
        foreach ($files['name'] as $key => $image) {
            $_FILES['files[]']['name']     = $files['name'][$key];
            $_FILES['files[]']['type']     = $files['type'][$key];
            $_FILES['files[]']['tmp_name'] = $files['tmp_name'][$key];
            $_FILES['files[]']['error']    = $files['error'][$key];
            $_FILES['files[]']['size']     = $files['size'][$key];

            $file_name             = generate_random(7) . '_' . date('Y-m-d');
            $uploaded_file         = $_FILES['files[]']['name'];
            $ext                   = pathinfo($uploaded_file, PATHINFO_EXTENSION);
            $file_path             = "assets/documents/{$file_name}.{$ext}";
            $check_file_name       = $this->main->check_data('coupon_attachment_tbl', ['coupon_attachment_path' => $file_path]);
            $check_trans_file_name = $this->main->check_data('coupon_transaction_header_attachment_tbl', ['coupon_transaction_header_attachment_path' => $file_path]);
            while ($check_file_name && $check_trans_file_name) {
                $file_name             = generate_random(7) . '-' . date('Y-m-d');
                $file_path             = "assets/documents/{$file_name}.{$ext}";
                $check_file_name       = $this->main->check_data('coupon_attachment_tbl', ['coupon_attachment_path' => $file_path]);
                $check_trans_file_name = $this->main->check_data('coupon_transaction_header_attachment_tbl', ['coupon_transaction_header_attachment_path' => $file_path]);
            }

            $config['file_name'] = $file_name;
            $this->upload->initialize($config);
            $result = $this->upload->do_upload('files[]');
            if ($result) {
                $data = [
                    'coupon_transaction_header_id'                          => $transaction_id,
                    'coupon_transaction_header_attachment_name'             => $files['name'][$key],
                    'coupon_transaction_header_attachment_path'             => $file_path,
                    'coupon_transaction_header_attachment_status'           => 1,
                    'coupon_transaction_header_attachment_process_sequence' => $sequence,
                    'coupon_transaction_header_attachment_added'            => date_now(),
                ];
                $this->main->insert_data('coupon_transaction_header_attachment_tbl', $data);
            } else {
                $alert_message = $this->alert_template($this->upload->display_errors(), FALSE);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }

    public function get_invoice_coupon_field($coupon_id)
    {
        $info         = $this->_require_login();
        $coupon_id    = clean_data(decode($coupon_id));
        $check_coupon = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
        $result       = '';
        if ($check_coupon['result']) {
			if(in_array($check_coupon['info']->coupon_cat_id, paid_category())){
                if ($check_coupon['info']->coupon_holder_type_id == 4) {
                    $result = '
                        <div class="form-group">
                            <label for="">Invoice:</label>
                            <input type="text" name="invoice_num" class="form-control form-control-sm" value="'.$check_coupon['info']->invoice_number.'" readonly required>
                        </div>';
                } elseif ($check_coupon['info']->coupon_holder_type_id == 4) {
                    $result = '
                        <div class="form-group">
                            <label for="">Invoice:</label>
                            <input type="text" name="invoice_num" class="form-control form-control-sm" value="">
                        </div>
                        <div class="form-group">
                            <label for="">Doc No:</label>
                            <input type="text" name="sap_doc_no" class="form-control form-control-sm" value="" required>
                        </div>
                        <label for="">Attachment:</label><br>
                        <div class="custom-file mb-3">
                            <input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple >
                            <label class="custom-file-label" for="attachment[]">Choose file...</label>
                        </div>';
                } else {
                    $result = '
                        <div class="form-group">
                            <label for="">Invoice:</label>
                            <input type="text" name="invoice_num" class="form-control form-control-sm" value="" required>
                        </div>
                        <div class="form-group">
                            <label for="">Doc No:</label>
                            <input type="text" name="sap_doc_no" class="form-control form-control-sm" value="" required>
                        </div>
                        <label for="">Attachment:</label><br>
                        <div class="custom-file mb-3">
                            <input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple required>
                            <label class="custom-file-label" for="attachment[]">Choose file...</label>
                        </div>';
                }
            }
        }
        echo $result;
    }

    public function get_invoice_trans_field($transaction_id)
    {
        $info           = $this->_require_login();
        $transaction_id = clean_data(decode($transaction_id));
        $check_coupon   = $this->main->check_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $transaction_id], TRUE);
        $result         = '';
        if ($check_coupon['result']) {
			if(in_array($check_coupon['info']->coupon_cat_id, paid_category())){
                $join        = ['coupon_tbl b' => 'b.coupon_id = a.coupon_id AND a.coupon_transaction_header_id = ' . $transaction_id];
                $coupon      = $this->main->get_join('coupon_transaction_details_tbl a', $join, TRUE);
                if ($coupon->coupon_holder_type_id == 4) {
                    $result = '
                        <div class="form-group">
                            <label for="">Invoice:</label>
                            <input type="text" name="invoice_num" class="form-control form-control-sm" value="'.$coupon->invoice_number.'" readonly required>
                        </div>';

                } elseif ($coupon->coupon_holder_type_id == 1) {
                    $result = '
                        <div class="form-group">
                            <label for="">Invoice:</label>
                            <input type="text" name="invoice_num" class="form-control form-control-sm" value="">
                        </div>
                        <div class="form-group">
                            <label for="">Doc No:</label>
                            <input type="text" name="sap_doc_no" class="form-control form-control-sm" value="" required>
                        </div>
                        <label for="">Attachment:</label><br>
                        <div class="custom-file mb-3">
                            <input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple>
                            <label class="custom-file-label" for="attachment[]">Choose file...</label>
                        </div>';
                } else {
                    $result = '
                        <div class="form-group">
                            <label for="">Invoice:</label>
                            <input type="text" name="invoice_num" class="form-control form-control-sm" value="" required>
                        </div>
                        <div class="form-group">
                            <label for="">Doc No:</label>
                            <input type="text" name="sap_doc_no" class="form-control form-control-sm" value="" required>
                        </div>
                        <label for="">Attachment:</label><br>
                        <div class="custom-file mb-3">
                            <input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple required>
                            <label class="custom-file-label" for="attachment[]">Choose file...</label>
                        </div>';
                }
            }
        }
        echo $result;
    }

    private function _store_coupon_action_log($action, $coupon_id)
    {
        $info    = $this->_require_login();
        $user_id = clean_data(decode($info['user_id']));
        $data = [
            'user_id'                 => $user_id,
            'action_id'               => $action,
            'coupon_id'               => $coupon_id,
            'coupon_action_log_added' => date_now()
        ];
        $this->main->insert_data('coupon_action_log_tbl', $data);
    }

    private function _store_transaction_action_log($action, $coupon_id)
    {
        $info    = $this->_require_login();
        $user_id = clean_data(decode($info['user_id']));
        $data = [
            'user_id'                             => $user_id,
            'action_id'                           => $action,
            'coupon_transaction_header_id'        => $coupon_id,
            'coupon_transaction_action_log_added' => date_now()
        ];
        $this->main->insert_data('coupon_transaction_action_log_tbl', $data);
    }

    private function _old_send_approved_notif($employee_name, $recipient, $voucher_name, $voucher_type)
    {
        if(empty($employee_name) || empty($recipient)) {
            $email_result = [
                'result'  => FALSE,
                'Message' => 'Parameter Empty'
            ];
            $this->_store_email_log($email_result, $recipient);
            return $email_result;
        }
        $url     = base_url();
        $subject = SYS_NAME.' Approved Reminder Notification';
        $message = '
        <!DOCTYPE html>
        <html lang="en">
            <style>
                body {
                    margin: 0;
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 15px;
                    color: #212529;
                    background-color: #fff;
                }
            </style>
            <head>
                <title>'.SYS_NAME.' Approved Reminder Notification</title>
            </head>
            <body>
                <section>
                    <div class="header" stlye="font-size 14px;">
                        <h4 style="display:block;background-color:#151247;color:white;text-align:center;padding: 30px 0px;">'.SYS_NAME.' System Notification</h4>
                    </div>
                    <div class="content" style="padding-left:50px;">
                        <br>
                            <p class="salutation">
                                <p>Hello '.$employee_name.',</p>
                            </p>
                            <p class="body">
                                We would like to inform about a '.ucwords($voucher_type).' '.SYS_NAME.' named <b>"'.$voucher_name.'"</b> that you created has been approved  and you may now distribute the '.SYS_NAME.'
                                <br>"<a href="'.$url.'"><u>You may click here to go directly to '.SYS_NAME.' System</u></a>" 
                            </p>
                            <p class="complimentary-close">
                                Thank you!
                            </p>
                        <br>
                    </div>
                    <div class="footer">
                        <h4 style="display:block;background-color:#151247;color:white;text-align:center;padding: 30px 0px;">&copy;&nbsp;Chookstogo, Inc.</h4>
                    </div>
                </section>
            </body>
        </html>';
        return $this->_send_email($recipient, $subject, $message);
    }

	private function _send_approved_notif($employee_name, $recipient, $voucher_name, $voucher_type)
    {
        if(empty($employee_name) || empty($recipient)) {
            $email_result = [
                'result'  => FALSE,
                'Message' => 'Parameter Empty'
            ];
            $this->_store_email_log($email_result, $recipient);
            return $email_result;
        }
        $url     = base_url();
        $subject = ''.SYS_NAME.' Approved Reminder Notification';

        $data['title'] = ''.SYS_NAME.' Request Approved';
        $data['name'] =  $employee_name;
        $data['voucher']  = $voucher_name;
        $data['type'] = $voucher_type;

        $message = $this->load->view('email/email_creator_content', $data, TRUE);
        return $this->_send_email($recipient, $subject, $message);
    }

    private function _send_pending_notif($employee_name, $recipient, $voucher_name, $voucher_type, $user_type_id)
    {
        if(empty($employee_name) || empty($recipient)) {
            $email_result = [
                'result'  => FALSE,
                'Message' => 'Parameter Empty'
            ];
            $this->_store_email_log($email_result, $recipient);
            return $email_result;
        }
        $url     = base_url();
        
        $data['name'] =  $employee_name;
        $data['voucher']  = $voucher_name;
        $data['type'] = $voucher_type;

		if($user_type_id == 12){ //* FIRST LEVEL APPROVER
			$data['body_addtl_msg'] = 'You may login in the system to approve';
			$data['title'] = SYS_NAME.' New Request';
		} else { //* FINAL LEVEL APPROVER
			$data['body_addtl_msg'] = 'You may login in the system for final approval of the';
			$data['title'] = SYS_NAME.' for Final Approval';
		}
		$subject = $data['title'].' Notification';

        $message = $this->load->view('email/email_approver_content', $data, TRUE);
        return $this->_send_email($recipient, $subject, $message);
    }

    private function _send_email($recipient ,$subject ,$message)
    {
        if(empty($recipient) || empty($subject) || empty($message)) {
            $email_result = [
                'result'  => FALSE,
                'Message' => 'Parameter Empty'
            ];
            $this->_store_email_log($email_result, $recipient);
            return $email_result;
        }

        $config = email_config();
        $this->load->library('email', $config);
        $this->email->set_newline("\r\n")
                    ->from('noreply@chookstogoinc.com.ph', SYS_NAME.' System Notification')
                    ->to($recipient)
                    ->subject($subject)
                    ->message($message);
        if($this->email->send()){
            $email_result = [
                'result'  => TRUE,
                'Message' => 'Email Sent'
            ];
            $this->_store_email_log($email_result, $recipient);
            return $email_result;
        }else{
            $email_result = [
                'result'  => FALSE,
                'Message' => $this->email->print_debugger()
            ];
            $this->_store_email_log($email_result, $recipient);
            return $email_result;
        }
    }

    private function _store_email_log($email_result, $recipient)
    {
        $data = [
            'email_notif_log_message'   => $email_result['Message'],
            'email_notif_log_result'    => $email_result['result'],
            'email_notif_log_recipient' => $recipient,
            'email_notif_log_added'     => date_now(),
        ];
        $result = $this->main->insert_data('email_notif_log_tbl', $data, TRUE);
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

    public function check_valid_email()
    {
        $info = $this->_require_login();
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->load->library('form_validation');
            $this->form_validation
                ->set_data( ['email' => trim(clean_data($this->input->post('email'))) ])
                ->set_rules('email', 'Email', 'required|valid_email');
            $result = $this->form_validation->run();
            $data   = [ 'result' => $result ];
            echo json_encode($data);
        }
    }

    private function _run_form_validation($rules, $redirect = NULL)
    {
        $redirect         = ($redirect === NULL) ?  $_SERVER['HTTP_REFERER'] : $redirect;
        $delimiter_prefix = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        $delimiter_suffix = '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        $this->load->library('form_validation');
        $this->form_validation
        ->set_rules($rules)
        ->set_error_delimiters($delimiter_prefix, $delimiter_suffix);
        if (!$this->form_validation->run()) {
            // print_r($rules);
            // die();
            $this->session->set_flashdata('message', validation_errors());
            redirect($redirect);
        }
    }

    private function _validate_result($data, $message, $redirect = NULL)
    {
        $redirect = ($redirect === NULL) ?  $_SERVER['HTTP_REFERER'] : $redirect;
        if ($data) {
            $alert_message = $this->alert_template($message, FALSE);
            $this->session->set_flashdata('message', $alert_message);
            redirect($redirect);
        }
    }

    private function _validate_contact_number($contact)
    {
        $parent_db     = $GLOBALS['parent_db'];
        $where         = [ 'contact_number_prefix' => substr(trim(clean_data($contact)), 0, 4) ];
        $check_contact = $this->main->check_data("{$parent_db}.contact_number_prefix_tbl", $where);
        $this->_validate_result($check_contact, 'Invalid Contact Number Prefix');
    }

    private function _validate_prod_sale($prod_sale)
    {
        $parent_db = $GLOBALS['parent_db'];
        foreach ($prod_sale as $prod_sale_row) {
            if ($prod_sale_row != 'orc' && $prod_sale_row != 'all') {
                $clean_prod_sale_row = decode(clean_data($prod_sale_row));
                $check_prod_sale = $this->main->check_data("{$parent_db}.product_sale_tbl", ['prod_sale_id' => $clean_prod_sale_row]);
                if (!$check_prod_sale) {
                    $alert_message = $this->alert_template('Product Doesn\'t Exist', false);
                    $this->session->set_flashdata('message', $alert_message);
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }
        }
    }

    private function _validate_brand($brand)
    {
        $parent_db = $GLOBALS['parent_db'];
        foreach ($brand as $brand_row) {
            $clean_brand_row = decode(clean_data($brand_row));
            $check_brand = $this->main->check_data("{$parent_db}.brand_tbl", ['brand_id' => $clean_brand_row]);
            $this->_validate_result(!$check_brand, 'Brand Doesn\'t Exist');
        }
    }

    private function _validate_bc($bc)
    {
        $parent_db = $GLOBALS['parent_db'];
        if (!in_array('nationwide', $bc)) {
            foreach ($bc as $bc_row) {
                $clean_bc_row = decode(clean_data($bc_row));
                $check_bc = $this->main->check_data("{$parent_db}.bc_tbl", ['bc_id' => $clean_bc_row]);
                $this->_validate_result(!$check_bc, 'Business Center Doesn\'t Exist');
            }
        }
    }

    private function _validate_code($code)
    {
        $parent_db  = $GLOBALS['parent_db'];
        $check_code = $this->main->check_data('coupon_tbl', ['coupon_code' => $code]);
        $this->_validate_result($check_code, ''.SEC_SYS_NAME.' Code Already Used');
    }

    private function _validate_category($category)
    {
        $parent_db      = $GLOBALS['parent_db'];
        $check_category = $this->main->check_data('coupon_category_tbl', ['coupon_cat_id' => $category]);
        $this->_validate_result(!$check_category, 'Category Doesn\'t Exist');
    }

    private function _validate_value_type($value_type)
    {
        $parent_db        = $GLOBALS['parent_db'];
        $check_value_type = $this->main->check_data('coupon_value_type_tbl', ['coupon_value_type_id' => $value_type]);
        $this->_validate_result(!$check_value_type, 'Value Type Doesn\'t Exist');
    }

    private function _validate_holder_type($holder_type)
    {
        $parent_db         = $GLOBALS['parent_db'];
        $check_holder_type = $this->main->check_data('coupon_holder_type_tbl', ['coupon_holder_type_id' => $holder_type]);
        $this->_validate_result(!$check_holder_type, 'Holder Type Doesn\'t Exist');
    }

	private function _validate_company($id)
    {
        $parent_db        = $GLOBALS['parent_db'];
        $check_company = $this->main->check_data('company_tbl', ['company_id' => $id]);
        $this->_validate_result(!$check_company, 'Company Doesn\'t Exist');
    }

	private function _validate_customer($id, $name)
    {
		$info      = $this->_require_login();
        $parent_db        = $GLOBALS['parent_db'];
        $check_cust = $this->main->check_data('customers_tbl', ['customer_id' => $id], TRUE);
		if(!$check_cust['result']){
			$check_cust_name = $this->main->check_data('customers_tbl', ['customer_name' => $name], TRUE);
			if(!$check_cust_name['result']){
				$set      = [
					'customer_name' 			=> $name,
					'user_id' 					=> decode($info['user_id']),
					'customer_status' 			=> 1,
					'customer_added'			=> date_now(),
				];
				$new_id = $this->main->insert_data('customers_tbl', $set, TRUE)['id'];
				
			} else {
				$new_id = $check_cust_name['info']->customer_id;
			}
		} else {
			$new_id = $check_cust['info']->customer_id;
		}
        return $new_id;
    }

	private function _validate_payment_type($id)
    {
        $parent_db        = $GLOBALS['parent_db'];
        $check_payment = $this->main->check_data('payment_types_tbl', ['payment_type_id' => $id]);
        $this->_validate_result(!$check_payment, 'Payment Type Doesn\'t Exist');
    }

    private function _validate_attachment($hash='')
    {
        if (empty($_FILES['attachment']['name']) || !isset($_FILES['attachment']) || !$_FILES['attachment']['name'][0]) {
            $alert_message = $this->alert_template('Attachment Is Required', FALSE);
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER'].$hash);
        }
    }

    public function modal_transaction_coupon_attachment($id)
    {
        $info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];
        $id        = clean_data(decode($id));
        $check_id  = $this->main->check_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $id], TRUE);
        if ($check_id['result']) {
            $where               		= ['coupon_transaction_header_attachment_status' => 1, 'coupon_transaction_header_id' => $id];
            $attachments         		= $this->main->get_data('coupon_transaction_header_attachment_tbl', $where);
            $pending_count       		= 1;
            $first_approved_count      	= 1;
            $approved_count      		= 1;
            $paid_count          		= 1;
            $pending_attachment  		= '<tr><td colspan="2"><b>Creation Attachment</b></td></tr>';
            $first_approved_attachment 	= '<tr><td colspan="2"><b>Treasury Approval Attachment</b></td></tr>';
            $approved_attachment 		= '<tr><td colspan="2"><b>Finance Approval Attachment</b></td></tr>';
            $paid_attachment     		= '<tr><td colspan="2"><b>Payment Attachment</b></td></tr>';
            foreach ($attachments as $row) {
                $link = base_url($row->coupon_transaction_header_attachment_path);
                $name = $row->coupon_transaction_header_attachment_name;
                if ($row->coupon_transaction_header_attachment_process_sequence == 1) {
                    $pending_attachment .= '<tr><td>'.$pending_count.'</td><td><a href="'.$link.'" target="_blank">'.$name.'</a></td></tr>';
                    $pending_count++;
                } else if ($row->coupon_transaction_header_attachment_process_sequence == 2) {
                    $first_approved_attachment .= '<tr><td>'.$first_approved_count.'</td><td><a href="'.$link.'" target="_blank">'.$name.'</a></td></tr>';
                    $first_approved_count++;
                } else if ($row->coupon_transaction_header_attachment_process_sequence == 3) {
                    $approved_attachment .= '<tr><td>'.$approved_count.'</td><td><a href="'.$link.'" target="_blank">'.$name.'</a></td></tr>';
                    $approved_count++;
                } else if ($row->coupon_transaction_header_attachment_process_sequence == 4) {
                    $paid_attachment .= '<tr><td>'.$paid_count.'</td><td><a href="'.$link.'" target="_blank">'.$name.'</a></td></tr>';
                    $paid_count++;
                }
            }

			$join        = [
				"action_tbl b" => "b.action_id = a.action_id AND a.coupon_transaction_header_id = " . $id,
				"{$parent_db}.user_tbl c" => "c.user_id = a.user_id"
			];
            $coupon_action_logs      = $this->main->get_join('coupon_transaction_action_log_tbl a', $join, FALSE);
			$logs     		= '';
			foreach ($coupon_action_logs as $row){
				$logs .= '
					<tr>
						<td>'.$row->action_name.'</td>
						<td>'.$row->user_fname.' '.$row->user_lname.'</td>
						<td>'.date('M d, Y h:i A', strtotime($row->coupon_transaction_action_log_added)).'</td>
					</tr>
				';
			}

            $items = $pending_attachment;

            if ($first_approved_count > 1) {
                $items .= $first_approved_attachment;
            }

            if ($approved_count > 1) {
                $items .= $approved_attachment;
            }

            if ($paid_count > 1) {
                $items .= $paid_attachment;
            }

            $html = '
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><strong>#</strong></th>
                            <th scope="col"><strong>Attachment</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                    '.$items.'
                    </tbody>
                </table>
            ';

			$html .= '
				<table class="table">
                    <thead>
                        <tr>
                            <th scope="col text-center" colspan="3"><strong>Action Logs History</strong></th>
                        </tr>
                        <tr>
                            <th scope="col"><strong>Action</strong></th>
                            <th scope="col"><strong>User</strong></th>
                            <th scope="col"><strong>Timestamp</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                    '.$logs.'
                    </tbody>
                </table>';
            
            $result      = [
                'result' => TRUE,
                'html'   => $html
            ];
        } else {
            $result = [
                'result' => FALSE
            ];
        }
        echo json_encode($result);
    }

    public function modal_coupon_attachment($id)
    {
        $info      = $this->_require_login();
        $id        = clean_data(decode($id));
        $check_id  = $this->main->check_data('coupon_tbl', ['coupon_id' => $id], TRUE);
        if ($check_id['result']) {
            $where              = ['coupon_attachment_status' => 1, 'coupon_id' => $id];
            $attachments        = $this->main->get_data('coupon_attachment_tbl', $where);
            $pending_count      = 1;
            $paid_count         = 1;
            $pending_attachment = '<tr><td colspan="2"><b>Creation Attachment</b></td></tr>';
            $paid_attachment    = '<tr><td colspan="2"><b>Payment Attachment</b></td></tr>';
            foreach ($attachments as $row) {
                $link = base_url($row->coupon_attachment_path);
                $name = $row->coupon_attachment_name;
                if ($row->coupon_attachment_sequence == 1) {
                    $pending_attachment .= '<tr><td>'.$pending_count.'</td><td><a href="'.$link.'" target="_blank">'.$name.'</a></td></tr>';
                    $pending_count++;
                } else {
                    $paid_attachment .= '<tr><td>'.$paid_count.'</td><td><a href="'.$link.'" target="_blank">'.$name.'</a></td></tr>';
                    $paid_count++;
                }
            }

            if ($paid_count > 1) {
                $items = $pending_attachment . $paid_attachment;
            } else {
                $items = $pending_attachment;
            }

            $html = '
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><strong>#</strong></th>
                            <th scope="col"><strong>Attachment</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                    '.$items.'
                    </tbody>
                </table>
            ';
            
            $result      = [
                'result' => TRUE,
                'html'   => $html
            ];
        } else {
            $result = [
                'result' => FALSE
            ];
        }
        echo json_encode($result);
    }

    private function _send_creator_notification($name, $category, $voucher_type) 
    {
        $parent_db = $GLOBALS['parent_db'];
        $where     = "a.user_type_id IN (8) AND a.user_id IN (SELECT user_id FROM user_access_tbl z WHERE z.coupon_cat_id = {$category} AND user_access_status = 1)";
        $creator  = $this->main->get_data("{$parent_db}.user_tbl a", $where);
        foreach ($creator as $row) {
            if ($row->user_email != '') {
                $employee_name = ucwords(strtolower(("{$row->user_fname}")));
                $recipient     = $row->user_email;
                $voucher_name  = $name;
				
                $this->_send_pending_notif($employee_name, $recipient, $voucher_name, $voucher_type, 12);
            }
        }
    }

    private function _send_transaction_creator_notification($transaction_id) 
    {
        $parent_db   = $GLOBALS['parent_db'];
        $transaction = $this->main->get_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $transaction_id], TRUE);
        $creator     = $this->main->get_data("{$parent_db}.user_tbl", ['user_id' => $transaction->user_id], TRUE);
        if ($creator->user_email != '') {
            $employee_name = "{$creator->user_fname} {$creator->user_lname}";
            $recipient     = $creator->user_email;
            $voucher_name  = $transaction->coupon_transaction_header_name;
            $this->_send_approved_notif($employee_name, $recipient, $voucher_name, 'Product '.SEC_SYS_NAME.'');
        }
    }


    private function _send_coupon_creator_notification($coupon_id) 
    {
        $parent_db = $GLOBALS['parent_db'];
        $coupon    = $this->main->get_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
        $creator   = $this->main->get_data("{$parent_db}.user_tbl", ['user_id' => $coupon->user_id], TRUE);
        if ($creator->user_email != '') {
            $employee_name = "{$creator->user_fname} {$creator->user_lname}";
            $recipient     = $creator->user_email;
            $voucher_name  = $coupon->coupon_name;
            $this->_send_approved_notif($employee_name, $recipient, $voucher_name, 'Standard '.SEC_SYS_NAME.'');
        }
    }

    public function voucher_summary() 
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

		$end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime($end_date . ' - 3 months'));
		
        $data['range_date'] = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));

		$join_salable = [
			"{$parent_db}.product_tbl b" => 'a.prod_id = b.prod_id',
			"{$parent_db}.unit_tbl c"    => 'a.unit_id = c.unit_id',
			"{$parent_db}.unit_tbl d"    => 'a.2nd_uom = d.unit_id'
        ];
		
		$filter = [
			'start_date'								=> $start_date,
			'end_date'									=> $end_date			
		];
		
		$filter['unused_flag']				= 0;
        $filter['date_filter_type']			= 'creation_date';
		// $used								= $this->_used_voucher_data($filter);
        // $data['tbl_used']					= $this->_get_used_voucher_tbl($used);
		$data['used_coupon_trans']			= $this->_coupon_data($filter);

		$filter['unused_flag']				= 1;
		$filter['date_filter_type']			= 'creation_date';
		// $unused								= $this->_unused_voucher_data($filter);
        // $data['tbl_unused']					= $this->_get_unused_voucher_tbl($unused);
        $data['unused_coupon_trans']		= $this->_coupon_data($filter);
        

        $data['title']						= 'Standard '.SEC_SYS_NAME.'';
		$data['top_nav']     				= $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content']					= $this->load->view('creator/coupon/coupon_summary_content', $data, TRUE);
        $this->load->view('creator/templates', $data);
    }

	private function _coupon_data($filter){


		$start_date = $filter['start_date'];
		$end_date = $filter['end_date'];
		$unused_flag = $filter['unused_flag'];
		$date_filter_type = $filter['date_filter_type'];

		ini_set('max_execution_time', 0);
        ini_set('memory_limit','4048M');
		$coupon_join = [
			'coupon_transaction_details_tbl b'		=> 'b.coupon_id = a.coupon_id',
			'coupon_transaction_header_tbl c'		=> 'c.coupon_transaction_header_id = b.coupon_transaction_header_id',
		];
		$coupon_select = 'c.coupon_transaction_header_name, c.coupon_transaction_header_id';
		$group_by = "c.coupon_transaction_header_id";

		if($unused_flag){
			$coupon_use = "a.coupon_use < 1";
		} else {
			$coupon_join['redeem_coupon_tbl f'] = 'f.coupon_id = a.coupon_id';
			$coupon_use = "a.coupon_use > 0";
		}
		if($date_filter_type == 'creation_date'){
			$date_field = 'a.coupon_added';
			$order_by = "$date_field DESC";
		} elseif($date_filter_type == 'redemption_date') {
			$date_field = 'f.redeem_coupon_added';
			$order_by = "$date_field DESC";
		}
		$where = $coupon_use." and DATE($date_field) >=  '$start_date' AND DATE($date_field) <= '$end_date'";
		
		
		$coupon_trans  = $this->main->get_join('coupon_tbl a', $coupon_join, FALSE, $order_by, $group_by ,$coupon_select , $where, false);

		if(!empty($coupon_trans)){
			$result = '';
			foreach ($coupon_trans as $row) {
				
				$result .= '<option value="'.encode($row->coupon_transaction_header_id).'">'.$row->coupon_transaction_header_id.' - '.$row->coupon_transaction_header_name.'</option>';
			}
		} else {
			$result = '<option>No record found</option>';
		}

		return $result;
	}

	private function _get_date_type($date_type){
		if($date_type == 1){
			$data = 'creation_date';
		} elseif($date_type == 2){
			$data = 'redemption_date';
		} elseif($date_type == 3){
			$data = 'creation_and_redemption_date';
		}
		return $data;
	}

	public function get_coupon_trans_data(){
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			$daterange  = explode(' - ', clean_data($this->input->post('date')));
            $start_date = date('y-m-d', strtotime($daterange[0]));
            $end_date   = date('y-m-d', strtotime($daterange[1]));
			$unused		= clean_data($this->input->post('unused'));
			$date_type	= clean_data($this->input->post('date_type'));
			$date_type	= $this->_get_date_type(decode($date_type));


			$filter = [
				'start_date' => $start_date,
				'end_date' => $end_date,
				'date_filter_type' => $date_type,
				'unused_flag' => $unused,
			];

			
			$coupon_trans = $this->_coupon_data($filter);
			

			$data['coupon_trans'] = $coupon_trans;
            $data['result'] = 1;
		}else{
            $data['result'] = 0;
            $data['msg'] = 'Error please try again!';
        }

        echo json_encode($data);
        exit;
	}

	public function unused_voucher_grid($filter=NULL){

		$info      = $this->_require_login();
		$start_date 	= clean_data($this->input->get('start_date'));
		$end_date 	= clean_data($this->input->get('end_date'));
		$date_filter_type 	= clean_data($this->input->get('date_type'));
		$coupon_transaction_header_id 	= clean_data(decode($this->input->get('coupon_transaction_header_id')));
		if(empty($start_date) && empty($end_date)){
			$end_date = date('Y-m-d');
			$start_date = date('Y-m-d', strtotime($end_date . ' - 3 months'));
			
			$data['range_date'] = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));
			$filter = [
				'start_date'								=> $start_date,
				'end_date'									=> $end_date,
				'unused_flag'								=> 1,
				'date_filter_type'							=> 'creation_date'
			];
		} else {
			$filter = [
				'start_date'								=> $start_date,
				'end_date'									=> $end_date,
				'unused_flag'								=> 1,
				'date_filter_type'							=> $date_filter_type,
				'coupon_transaction_header_ids'				=> $coupon_transaction_header_id,
			];
		}


		$unused = $this->_unused_voucher_data($filter, TRUE)->unused;
		
		$data = array();
		if(!empty($unused['result'])){
			foreach($unused['result']->result() as $row) {
				$badge  = '<span class="badge badge-warning">Pending</span>';

				$data[] = array(
                    $row->bc,
                    $row->brands,
                    $row->coupon_type_name,
                    $row->coupon_cat_name,
                    $row->coupon_transaction_header_id,
                    $row->coupon_name,
                    $row->coupon_code,
                    $row->sap_doc_no,
                    $row->coupon_amount,
                    $row->coupon_regular_value,
                    $row->coupon_value,
                    $row->coupon_qty,
                    $row->coupon_use,
                    
                    date_format(date_create($row->coupon_start),"M d, Y"),
                    date_format(date_create($row->coupon_end),"M d, Y"),
                    $row->coupon_holder_type_name,
                    $row->company_name,
                    $row->coupon_holder_name,
                    $row->coupon_holder_email,
                    $row->coupon_holder_contact,
                    $row->coupon_holder_address,
                    $row->coupon_holder_tin,
                    date_format(date_create($row->coupon_added),"M d, Y h:i:s A"),
                    $badge,
                
				);
			}
		}
		$output = array(
			"draw" => $_POST['draw'],
			"recordsTotal" => $this->_unused_voucher_data($filter, TRUE)->recordsTotal,
			"recordsFiltered" => $this->_unused_voucher_data($filter, TRUE)->recordsFiltered,
			"data" => $data,
		    // "query" =>  $unused['query']
	   	);
	   	echo json_encode($output);
		exit();
	}
	
	public function used_voucher_grid($filter=NULL){

		$info      = $this->_require_login();
		$start_date 	= clean_data($this->input->get('start_date'));
		$end_date 	= clean_data($this->input->get('end_date'));
		$date_filter_type 	= clean_data($this->input->get('date_type'));
		$coupon_transaction_header_id 	= clean_data(decode($this->input->get('coupon_transaction_header_id')));
		if(empty($start_date) && empty($end_date)){
			$end_date = date('Y-m-d');
			$start_date = date('Y-m-d', strtotime($end_date . ' - 3 months'));
			
			$data['range_date'] = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));
			$filter = [
				'start_date'								=> $start_date,
				'end_date'									=> $end_date,
				'unused_flag'								=> 0,
				'date_filter_type'							=> 'creation_date'
			];
		} else {
			$filter = [
				'start_date'								=> $start_date,
				'end_date'									=> $end_date,
				'unused_flag'								=> 0,
				'date_filter_type'							=> $date_filter_type,
				'coupon_transaction_header_ids'				=> $coupon_transaction_header_id,
			];
		}


		$unused = $this->_used_voucher_data($filter, TRUE)->unused;
		$data = array();
		if(!empty($unused['result'])){
			foreach($unused['result']->result() as $row) {
				// $badge  = '<span class="badge badge-warning">Pending</span>';
				$badge  = '<span class="badge badge-success">Approved</span>';

				$data[] = array(
                    $row->bc,
                    $row->brands,
                    $row->coupon_type_name,
                    $row->coupon_cat_name,
                    $row->coupon_transaction_header_id,
                    $row->coupon_name,
                    $row->coupon_code,
                    $row->sap_doc_no,
                    $row->coupon_amount,
                    $row->coupon_regular_value,
                    $row->coupon_value,
                    $row->coupon_qty,
                    $row->coupon_use,
                    
                    date_format(date_create($row->coupon_start),"M d, Y"),
                    date_format(date_create($row->coupon_end),"M d, Y"),
                    $row->coupon_holder_type_name,
                    $row->company_name,
                    $row->coupon_holder_name,
                    $row->coupon_holder_email,
                    $row->coupon_holder_contact,
                    $row->coupon_holder_address,
                    $row->coupon_holder_tin,
                    date_format(date_create($row->coupon_added),"M d, Y h:i:s A"),
                    $row->redeemed_coupon_log_contact_number,
					$row->outlet_ifs,
					$row->outlet_name,
					$row->staff_code,
					$row->staff_name,
                    $row->redeemed_coupon_log_reference_code,
                    date_format(date_create($row->redeemed_coupon_log_added),"M d, Y h:i:s A"),
                    $badge
                
				);
			}
		}
		$output = array(
			"draw" => $_POST['draw'],
			"recordsTotal" => $this->_used_voucher_data($filter, TRUE)->recordsTotal,
			"recordsFiltered" => $this->_used_voucher_data($filter, TRUE)->recordsFiltered,
			"data" => $data,
		   //  "query" =>  $recFound['query']
	   	);
	   	echo json_encode($output);
		exit();
	}

	public function _used_voucher_data($filter, $datagrid=FALSE){
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];
		$start_date = $filter['start_date'];
		$end_date = $filter['end_date'];
		$unused_flag = $filter['unused_flag'];
		$date_filter_type = $filter['date_filter_type'];

		ini_set('max_execution_time', 0);
        ini_set('memory_limit','4048M');

		$coupon_join = [
            'coupon_value_type_tbl b'  				=> 'b.coupon_value_type_id = a.coupon_value_type_id',
            'coupon_holder_type_tbl c' 				=> 'c.coupon_holder_type_id = a.coupon_holder_type_id',
            'coupon_type_tbl d'        				=> 'd.coupon_type_id = a.coupon_type_id',
            'coupon_category_tbl e'    				=> 'e.coupon_cat_id = a.coupon_cat_id',
            'redeem_coupon_tbl f'					=> 'f.coupon_id = a.coupon_id',
            'redeemed_coupon_log_tbl g'				=> 'g.redeemed_coupon_log_id = f.redeemed_coupon_log_id',
			'coupon_transaction_details_tbl h'		=> 'h.coupon_id = a.coupon_id',
			'company_tbl i'							=> 'i.company_id = a.company_id',
        ];
		
		$coupon_select = "
		h.coupon_transaction_header_id,
		a.invoice_number,
		a.sap_doc_no,
		d.coupon_type_name,
		e.coupon_cat_name,
		a.coupon_name,
		a.coupon_code,
		IF(a.coupon_value_type_id=1,CONCAT(a.coupon_amount,'%'),CONCAT('P',a.coupon_amount)) AS coupon_amount,
		a.coupon_value,
		a.coupon_regular_value,
		a.coupon_qty,
		a.coupon_use,
		b.coupon_value_type_name,
		a.coupon_start,
		a.coupon_end,
		c.coupon_holder_type_name,
		a.coupon_holder_name,
		a.coupon_holder_email,
		a.coupon_holder_contact,
		a.coupon_holder_address,
		a.coupon_holder_tin,
		a.coupon_added,
		g.redeemed_coupon_log_added,
		g.redeemed_coupon_log_reference_code,
		g.redeemed_coupon_log_contact_number,
		g.outlet_ifs,
		g.outlet_name,
		g.staff_code,
		g.staff_name,
		i.company_name,

        (SELECT GROUP_CONCAT(x.brand_name SEPARATOR ', ') FROM coupon_brand_tbl z JOIN {$parent_db}.brand_tbl x ON z.brand_id = x.brand_id WHERE z.coupon_id = a.coupon_id AND coupon_brand_status = 1) AS brands,
        IF(a.is_nationwide = 1, 
            'Nationwide', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'ORC',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";
        
		if($unused_flag){
			$coupon_use = "a.coupon_use < 1";
		} else {
			$coupon_join['redeem_coupon_tbl f'] = 'a.coupon_id = f.coupon_id';
			$coupon_use = "a.coupon_use > 0";
		}
		if($date_filter_type == 'creation_date'){
			$date_field = 'a.coupon_added';
			$order_by = "$date_field DESC";
		} elseif($date_filter_type == 'redemption_date') {
			$date_field = 'f.redeem_coupon_added';
			$order_by = "$date_field DESC";
		}
		$where = $coupon_use." and DATE($date_field) >=  '$start_date' AND DATE($date_field) <= '$end_date' and a.coupon_status = 1";
		if(!empty($filter['coupon_transaction_header_ids'])){
			
			$coupon_transaction_header_ids = $filter['coupon_transaction_header_ids'];
			$where .= " AND h.coupon_transaction_header_id IN ($coupon_transaction_header_ids)";
		}

		if($datagrid){
			$column_order = array(
        	
				'bc',
				'brand',
				'coupon_type_name',
				'coupon_cat_name',
				'coupon_transaction_header_id',
				'coupon_name',
				'coupon_code',
				'sap_doc_no',
				'coupon_amount',
				'coupon_regular_value',
				'coupon_value',
				'coupon_qty',
				'coupon_use',
				'coupon_start',
				'coupon_end',
				'coupon_holder_type_name',
				'company_name',
				'coupon_holder_name',
				'coupon_holder_email',
				'coupon_holder_contact',
				'coupon_holder_address',
				'coupon_holder_tin',
				'coupon_added',
				'redeemed_coupon_log_contact_number',
				'outlet_ifs',
				'outlet_name',
				'staff_code',
				'staff_name',
				'redeemed_coupon_log_reference_code',
				'redeemed_coupon_log_added',
				null
			);
			$column_search = array(
        	
				"IF(a.is_nationwide = 1, 
				'Nationwide', 
				(SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1))",
				"(SELECT GROUP_CONCAT(x.brand_name SEPARATOR ', ') FROM coupon_brand_tbl z JOIN {$parent_db}.brand_tbl x ON z.brand_id = x.brand_id WHERE z.coupon_id = a.coupon_id AND coupon_brand_status = 1)",
				'coupon_type_name',
				'coupon_cat_name',
				'coupon_transaction_header_id',
				'coupon_name',
				'coupon_code',
				'sap_doc_no',
				'coupon_amount',
				'coupon_regular_value',
				'coupon_value',
				'coupon_qty',
				'coupon_use',
				'coupon_start',
				'coupon_end',
				'coupon_holder_type_name',
				'company_name',
				'coupon_holder_name',
				'coupon_holder_email',
				'coupon_holder_contact',
				'coupon_holder_address',
				'coupon_holder_tin',
				'redeemed_coupon_log_contact_number',
				'outlet_ifs',
				'outlet_name',
				'staff_code',
				'staff_name',
				'redeemed_coupon_log_reference_code',
				'redeemed_coupon_log_added',
				'coupon_added'
			);

			
			$table = 'coupon_tbl a';
			$group_by = FALSE;
			$data['unused']  = $this->main->get_dynamic_dt($_POST, $table, $column_order, $column_search, $order_by, $coupon_select, $coupon_join, $where, $group_by);
			$data['recordsTotal'] = $this->main->countAll($table);
			$data['recordsFiltered'] = $this->main->countFiltered($_POST, $table, $column_order, $column_search, $order_by, $coupon_select, $coupon_join, $where, $group_by);
			$object = (object) $data;
			return $object;
		} else {

			$used   = $this->main->get_join('coupon_tbl a', $coupon_join, FALSE, $order_by, FALSE ,$coupon_select, $where);
			return $used;
		}


	}
	
	public function _unused_voucher_data($filter, $datagrid=FALSE){
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];
		$start_date = $filter['start_date'];
		$end_date = $filter['end_date'];
		$unused_flag = $filter['unused_flag'];
		$date_filter_type = $filter['date_filter_type'];

		ini_set('max_execution_time', 0);
        ini_set('memory_limit','4048M');

		$coupon_join = [
            'coupon_value_type_tbl b'  				=> 'b.coupon_value_type_id = a.coupon_value_type_id',
            'coupon_holder_type_tbl c' 				=> 'c.coupon_holder_type_id = a.coupon_holder_type_id',
            'coupon_type_tbl d'        				=> 'd.coupon_type_id = a.coupon_type_id',
            'coupon_category_tbl e'    				=> 'e.coupon_cat_id = a.coupon_cat_id',
			'coupon_transaction_details_tbl h'		=> 'h.coupon_id = a.coupon_id',
			'company_tbl i'							=> 'i.company_id = a.company_id',
        ];

        $coupon_select = "
		h.coupon_transaction_header_id,
		a.invoice_number,
		a.sap_doc_no,
		d.coupon_type_name,
		e.coupon_cat_name,
		a.coupon_name,
		a.coupon_code,
		IF(a.coupon_value_type_id=1,CONCAT(a.coupon_amount,'%'),CONCAT('P',a.coupon_amount)) AS coupon_amount,
		a.coupon_regular_value,
		a.coupon_value,
		a.coupon_qty,
		a.coupon_use,
		b.coupon_value_type_name,
		a.coupon_start,
		a.coupon_end,
		c.coupon_holder_type_name,
		a.coupon_holder_name,
		a.coupon_holder_email,
		a.coupon_holder_contact,
		a.coupon_holder_address,
		a.coupon_holder_tin,
		a.coupon_added,
		i.company_name,

        (SELECT GROUP_CONCAT(x.brand_name SEPARATOR ', ') FROM coupon_brand_tbl z JOIN {$parent_db}.brand_tbl x ON z.brand_id = x.brand_id WHERE z.coupon_id = a.coupon_id AND coupon_brand_status = 1) AS brands,
        IF(a.is_nationwide = 1, 
            'Nationwide', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'ORC',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";
		
		if($unused_flag){
			$coupon_use = "a.coupon_use < 1";
		} else {
			$coupon_join['redeem_coupon_tbl f'] = 'a.coupon_id = f.coupon_id';
			$coupon_use = "a.coupon_use > 0";
		}
		if($date_filter_type == 'creation_date'){
			$date_field = 'a.coupon_added';
			$order_by = "$date_field DESC";
		} elseif($date_filter_type == 'redemption_date') {
			$date_field = 'f.redeem_coupon_added';
			$order_by = "$date_field DESC";
		}
		$where = $coupon_use." and DATE($date_field) >=  '$start_date' AND DATE($date_field) <= '$end_date' and a.coupon_status = 1";
		if(!empty($filter['coupon_transaction_header_ids'])){
			
			$coupon_transaction_header_ids = $filter['coupon_transaction_header_ids'];
			$where .= " AND h.coupon_transaction_header_id IN ($coupon_transaction_header_ids)";
		}

		if($datagrid){
			$column_order = array(
        	
				'bc',
				'brand',
				'coupon_type_name',
				'coupon_cat_name',
				'coupon_transaction_header_id',
				'coupon_name',
				'coupon_code',
				'sap_doc_no',
				'coupon_amount',
				'coupon_regular_value',
				'coupon_value',
				'coupon_qty',
				'coupon_use',
				'coupon_start',
				'coupon_end',
				'coupon_holder_type_name',
				'company_name',
				'coupon_holder_name',
				'coupon_holder_email',
				'coupon_holder_contact',
				'coupon_holder_address',
				'coupon_holder_tin',
				'coupon_added',
				null
			);
			$column_search = array(
        	
				"IF(a.is_nationwide = 1, 
				'Nationwide', 
				(SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1))",
				"(SELECT GROUP_CONCAT(x.brand_name SEPARATOR ', ') FROM coupon_brand_tbl z JOIN {$parent_db}.brand_tbl x ON z.brand_id = x.brand_id WHERE z.coupon_id = a.coupon_id AND coupon_brand_status = 1)",
				'coupon_type_name',
				'coupon_cat_name',
				'coupon_transaction_header_id',
				'coupon_name',
				'coupon_code',
				'sap_doc_no',
				'coupon_amount',
				'coupon_regular_value',
				'coupon_value',
				'coupon_qty',
				'coupon_use',
				'coupon_start',
				'coupon_end',
				'coupon_holder_type_name',
				'company_name',
				'coupon_holder_name',
				'coupon_holder_email',
				'coupon_holder_contact',
				'coupon_holder_address',
				'coupon_holder_tin',
				'coupon_added'
			);

			
			$table = 'coupon_tbl a';
			$group_by = FALSE;
			$data['unused']  = $this->main->get_dynamic_dt($_POST, $table, $column_order, $column_search, $order_by, $coupon_select, $coupon_join, $where, $group_by);
			$data['recordsTotal'] = $this->main->countAll($table);
			$data['recordsFiltered'] = $this->main->countFiltered($_POST, $table, $column_order, $column_search, $order_by, $coupon_select, $coupon_join, $where, $group_by);
			$object = (object) $data;
			return $object;
		} else {
			$unused  = $this->main->get_join('coupon_tbl a', $coupon_join, FALSE, $order_by, FALSE ,$coupon_select , $where);
			return $unused;
		}

	}

	public function get_used_voucher_data(){
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			
            $daterange  									= explode(' - ', clean_data($this->input->post('date')));
            $start_date 									= date('y-m-d', strtotime($daterange[0]));
            $end_date   									= date('y-m-d', strtotime($daterange[1]));
			$unused											= 0;
			$date_type										= clean_data($this->input->post('date_type'));
			$date_type										= $this->_get_date_type(decode($date_type));
			$coupon_transaction_header_id					= clean_data($this->input->post('coupon_transaction_header_id'));
			$coupon_trans_id_arrays							= [];
			if(!empty($coupon_transaction_header_id)){
				foreach($coupon_transaction_header_id as $row){
					$coupon_trans_id_arrays[] = decode($row);
				}
			}
			$coupon_transaction_header_ids					= join(',',$coupon_trans_id_arrays);
			
			$data = [
				'start_date'								=> $start_date,
				'end_date'									=> $end_date,
				'date_filter_type'							=> $date_type,
				'unused_flag'								=> $unused,
				// 'coupon_transaction_header_ids'				=> $coupon_transaction_header_ids
			];

			// $used = $this->_used_voucher_data($filter);
            // $data['tbl_used'] = $this->_get_used_voucher_tbl($used);

			$data['coupon_transaction_header_ids'] = encode($coupon_transaction_header_ids);
            $data['result'] = 1;

        }else{
            $data['result'] = 0;
            $data['msg'] = 'Error please try again!';
        }

        echo json_encode($data);
        exit;
	}

	public function get_unused_voucher_data(){
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			
            $daterange  									= explode(' - ', clean_data($this->input->post('date')));
            $start_date 									= date('Y-m-d', strtotime($daterange[0]));
            $end_date   									= date('Y-m-d', strtotime($daterange[1]));
			$unused											= 1;
			$date_type										= clean_data($this->input->post('date_type'));
			$date_type										= $this->_get_date_type(decode($date_type));
			$coupon_transaction_header_id					= clean_data($this->input->post('coupon_transaction_header_id'));
			$coupon_trans_id_arrays							= [];
			if(!empty($coupon_transaction_header_id)){
				foreach($coupon_transaction_header_id as $row){
					$coupon_trans_id_arrays[] = decode($row);
				}
			}
			$coupon_transaction_header_ids					= join(',',$coupon_trans_id_arrays);
			
			$data = [
				'start_date'								=> $start_date,
				'end_date'									=> $end_date,
				'date_filter_type'							=> $date_type,
				'unused_flag'								=> $unused,
				// 'coupon_transaction_header_ids'				=> $coupon_transaction_header_ids
			];

			// $unused = $this->_unused_voucher_data($filter);
            // $data['tbl_unused'] = $this->_get_unused_voucher_tbl($unused);

			
			
            $data['coupon_transaction_header_ids'] = encode($coupon_transaction_header_ids);
            $data['result'] = 1;

        }else{
            $data['result'] = 0;
            $data['msg'] = 'Error please try again!';
        }

        echo json_encode($data);
        exit;
	}

	public function download_unused_voucher_data(){
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

		ini_set('max_execution_time', 0);
		ini_set('memory_limit','2048M');

		if($_SERVER['REQUEST_METHOD'] == 'GET'){
			$daterange  									= explode(' - ', clean_data($this->input->get('date')));
            $start_date 									= date('y-m-d', strtotime($daterange[0]));
            $end_date   									= date('y-m-d', strtotime($daterange[1]));
			$as_of											= date('Y.m.d', strtotime($daterange[0])) .' - '.date('Y.m.d', strtotime($daterange[1]));;
			$unused											= 1;
			$date_type										= clean_data($this->input->get('date_type'));
			$date_type										= $this->_get_date_type(decode($date_type));
			$coupon_transaction_header_ids					= clean_data($this->input->get('coupon_transaction_header_id'));
			$coupon_transaction_header_ids					= $coupon_transaction_header_ids ? decode($coupon_transaction_header_ids) : '';
			
			
			$filter = [
				'start_date'								=> $start_date,
				'end_date'									=> $end_date,
				'date_filter_type'							=> $date_type,
				'unused_flag'								=> $unused,
				'coupon_transaction_header_ids'				=> $coupon_transaction_header_ids
			];
			
			$unused = $this->_unused_voucher_data($filter);

			$this->load->library('excel');
			$spreadsheet = $this->excel;

			$style_bold = array(
				'font' 	=> array(
						'bold' => true,
						'color' => array('rgb' => 'ffffff')
				),
				'fill'	=> array(
					'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'startcolor' => array(
						'rgb' => 'b30000'
					)
				)
			);

			$sheet1 = $spreadsheet->createSheet(0);

			

			$sheet1
				->setCellValue("A1", 'Business Center')
				->setCellValue("B1", 'Brand')
				->setCellValue("C1", 'Type')
				->setCellValue("D1", 'Category')
				->setCellValue("E1", 'ID')
				->setCellValue("F1", 'Name')
				->setCellValue("G1", 'Code')
				->setCellValue("H1", 'Document No.')

				->setCellValue("I1", ''.SEC_SYS_NAME.' Value')
				->setCellValue("J1", ''.SEC_SYS_NAME.' Regular Amount')
				->setCellValue("K1", ''.SEC_SYS_NAME.' Paid Amount')
				->setCellValue("L1", 'Qty')
				->setCellValue("M1", 'Usage')
				
				->setCellValue("N1", 'Start')
				->setCellValue("O1", 'End')
				->setCellValue("P1", 'Holder Type')
				->setCellValue("Q1", 'Requestor\'s Company')
				->setCellValue("R1", 'Holder Name')
				->setCellValue("S1", 'Holder Email')
				->setCellValue("T1", 'Holder Contact')
				->setCellValue("U1", 'Holder Address')
				->setCellValue("V1", 'Holder TIN')
				->setCellValue("W1", 'Added')
			;

			$x = 2;
			foreach($unused as $row){
				$sheet1
					->setCellValue('A' . $x, $row->bc )
					->setCellValue('B' . $x, $row->brands )
					->setCellValue('C' . $x, $row->coupon_type_name )
					->setCellValue('D' . $x, $row->coupon_cat_name )
					->setCellValue('E' . $x, $row->coupon_transaction_header_id )
					->setCellValue('F' . $x, $row->coupon_name )
					->setCellValue('G' . $x, $row->coupon_code )
					->setCellValue('H' . $x, $row->sap_doc_no )
					->setCellValue('I' . $x, $row->coupon_amount )

					->setCellValue('J' . $x, $row->coupon_regular_value )
					->setCellValue('K' . $x, $row->coupon_value )
					->setCellValue('L' . $x, $row->coupon_qty )
					->setCellValue('M' . $x, $row->coupon_use )

					->setCellValue('N' . $x, date_format(date_create($row->coupon_start),"M d, Y") )
					->setCellValue('O' . $x, date_format(date_create($row->coupon_end),"M d, Y") )
					->setCellValue('P' . $x, $row->coupon_holder_type_name )
					->setCellValue('Q' . $x, $row->company_name )
					->setCellValue('R' . $x, $row->coupon_holder_name )
					->setCellValue('S' . $x, $row->coupon_holder_email )
					->setCellValue('T' . $x, $row->coupon_holder_contact )
					->setCellValue('U' . $x, $row->coupon_holder_address )
					->setCellValue('V' . $x, $row->coupon_holder_tin )
					->setCellValue('W' . $x, date_format(date_create($row->coupon_added),"M d, Y h:i:s A") )
				;

				$x++;
			}

			$sheet1->getStyle("A1:W1")->applyFromArray($style_bold);
			$sheet1->getStyle('O1:W' . $x)->getNumberFormat()->setFormatCode('#,##0.00');

			$high = $sheet1->getHighestDataColumn();
			$cell_num = PHPExcel_Cell::columnIndexFromString($high);

			for($index=0 ; $index <= $cell_num ; $index++){
				$col = PHPExcel_Cell::stringFromColumnIndex($index);
				$sheet1->getColumnDimension($col)->setAutoSize(TRUE);
			}

			$sheet1->setTitle('Unused Evoucher');

			$filename = 'Unused Evoucher as of '.$as_of;

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
	}
	
	public function download_used_voucher_data(){
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

		ini_set('max_execution_time', 0);
		ini_set('memory_limit','2048M');

		if($_SERVER['REQUEST_METHOD'] == 'GET'){
			$daterange  									= explode(' - ', clean_data($this->input->get('date')));
            $start_date 									= date('y-m-d', strtotime($daterange[0]));
            $end_date   									= date('y-m-d', strtotime($daterange[1]));
			$as_of											= date('Y.m.d', strtotime($daterange[0])) .' - '.date('Y.m.d', strtotime($daterange[1]));;
			$unused											= 0;
			$date_type										= clean_data($this->input->get('date_type'));
			$date_type										= $this->_get_date_type(decode($date_type));
			$coupon_transaction_header_ids					= clean_data($this->input->get('coupon_transaction_header_id'));
			$coupon_transaction_header_ids					= $coupon_transaction_header_ids ? decode($coupon_transaction_header_ids) : '';
			
			
			$filter = [
				'start_date'								=> $start_date,
				'end_date'									=> $end_date,
				'date_filter_type'							=> $date_type,
				'unused_flag'								=> $unused,
				'coupon_transaction_header_ids'				=> $coupon_transaction_header_ids
			];
			
			$used = $this->_used_voucher_data($filter);

			

			$this->load->library('excel');
			$spreadsheet = $this->excel;

			$style_bold = array(
				'font' 	=> array(
						'bold' => true,
						'color' => array('rgb' => 'ffffff')
				),
				'fill'	=> array(
					'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'startcolor' => array(
						'rgb' => 'b30000'
					)
				)
			);

			$sheet1 = $spreadsheet->createSheet(0);

			$sheet1
				->setCellValue("A1", 'Business Center')
				->setCellValue("B1", 'Brand')
				->setCellValue("C1", 'Type')
				->setCellValue("D1", 'Category')
				->setCellValue("E1", 'ID')
				->setCellValue("F1", 'Name')
				->setCellValue("G1", 'Code')
				->setCellValue("H1", 'Document No.')

				->setCellValue("I1", ''.SEC_SYS_NAME.' Value')
				->setCellValue("J1", ''.SEC_SYS_NAME.' Regular Amount')
				->setCellValue("K1", ''.SEC_SYS_NAME.' Paid Amount')
				->setCellValue("L1", 'Qty')
				->setCellValue("M1", 'Usage')
				
				->setCellValue("N1", 'Start')
				->setCellValue("O1", 'End')
				->setCellValue("P1", 'Holder Type')
				->setCellValue("Q1", 'Requestor\'s Company')
				->setCellValue("R1", 'Holder Name')
				->setCellValue("S1", 'Holder Email')
				->setCellValue("T1", 'Holder Contact')
				->setCellValue("U1", 'Holder Address')
				->setCellValue("V1", 'Holder TIN')
				->setCellValue("W1", 'Added')

				->setCellValue("X1", 'Redeemer Originator')

				->setCellValue("Y1", 'Redeemer Store IFS')
				->setCellValue("Z1", 'Redeemer Store')
				->setCellValue("AA1", 'Redeemer Crew Code')
				->setCellValue("AB1", 'Redeemer Crew')

				->setCellValue("AC1", 'Redeemer Approval Code')
				->setCellValue("AD1", 'Redeemed TS')
			;

			$x = 2;
			foreach($used as $row){
				$sheet1
					->setCellValue('A' . $x, $row->bc )
					->setCellValue('B' . $x, $row->brands )
					->setCellValue('C' . $x, $row->coupon_type_name )
					->setCellValue('D' . $x, $row->coupon_cat_name )
					->setCellValue('E' . $x, $row->coupon_transaction_header_id )
					->setCellValue('F' . $x, $row->coupon_name )
					->setCellValue('G' . $x, $row->coupon_code )
					->setCellValue('H' . $x, $row->sap_doc_no )
					->setCellValue('I' . $x, $row->coupon_amount )

					->setCellValue('J' . $x, $row->coupon_regular_value )
					->setCellValue('K' . $x, $row->coupon_value )
					->setCellValue('L' . $x, $row->coupon_qty )
					->setCellValue('M' . $x, $row->coupon_use )

					->setCellValue('N' . $x, date_format(date_create($row->coupon_start),"M d, Y") )
					->setCellValue('O' . $x, date_format(date_create($row->coupon_end),"M d, Y") )
					->setCellValue('P' . $x, $row->coupon_holder_type_name )
					->setCellValue('Q' . $x, $row->company_name )
					->setCellValue('R' . $x, $row->coupon_holder_name )
					->setCellValue('S' . $x, $row->coupon_holder_email )
					->setCellValue('T' . $x, $row->coupon_holder_contact )
					->setCellValue('U' . $x, $row->coupon_holder_address )
					->setCellValue('V' . $x, $row->coupon_holder_tin )
					->setCellValue('W' . $x, date_format(date_create($row->coupon_added),"M d, Y h:i:s A") )

					->setCellValue('X' . $x, $row->redeemed_coupon_log_contact_number )
					
					->setCellValue('Y' . $x, $row->outlet_ifs )
					->setCellValue('Z' . $x, $row->outlet_name )
					->setCellValue('AA' . $x, $row->staff_code )
					->setCellValue('AB' . $x, $row->staff_name )
					
					->setCellValue('AC' . $x, $row->redeemed_coupon_log_reference_code )
					->setCellValue('AD' . $x, date_format(date_create($row->redeemed_coupon_log_added),"M d, Y h:i:s A") )
				;

				$x++;
			}

			$sheet1->getStyle("A1:AD1")->applyFromArray($style_bold);
			$sheet1->getStyle('O1:W' . $x)->getNumberFormat()->setFormatCode('#,##0.00');

			$high = $sheet1->getHighestDataColumn();
			$cell_num = PHPExcel_Cell::columnIndexFromString($high);

			for($index=0 ; $index <= $cell_num ; $index++){
				$col = PHPExcel_Cell::stringFromColumnIndex($index);
				$sheet1->getColumnDimension($col)->setAutoSize(TRUE);
			}

			$sheet1->setTitle('Used Evoucher');

			$filename = 'Used Evoucher as of '.$as_of;

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
	}

	public function _get_used_voucher_tbl($used){
		$info      = $this->_require_login();
		$tbl = '';
        foreach($used as $row){
			$badge  = '<span class="badge badge-success">Approved</span>';
            $tbl .= '
                <tr>
                    <td>' . $row->bc . '</td>
                    <td>' . $row->brands . '</td>
                    <td>' . $row->coupon_type_name . '</td>
                    <td>' . $row->coupon_cat_name . '</td>
                    <td>' . $row->coupon_name . '</td>
                    <td>' . $row->coupon_code . '</td>
                    <td>' . $row->coupon_amount . '</td>

					<td>' . $row->coupon_regular_value . '</td>
                    <td>' . $row->coupon_value . '</td>
                    <td>' . $row->coupon_qty . '</td>
                    <td>' . $row->coupon_use . '</td>
                    
                    <td>' . date_format(date_create($row->coupon_start),"M d, Y") . '</td>
                    <td>' . date_format(date_create($row->coupon_end),"M d, Y") . '</td>
                    <td>' . $row->coupon_holder_type_name . '</td>
                    <td>' . $row->company_name . '</td>
                    <td>' . $row->coupon_holder_name . '</td>
                    <td>' . $row->coupon_holder_email . '</td>
                    <td>' . $row->coupon_holder_contact . '</td>
                    <td>' . $row->coupon_holder_address . '</td>
                    <td>' . $row->coupon_holder_tin . '</td>
					
                    <td>' . date_format(date_create($row->coupon_added),"M d, Y h:i:s A") . '</td>
                    <td>' . $row->redeemed_coupon_log_contact_number . '</td>
                    <td>' . $row->redeemed_coupon_log_reference_code . '</td>
                    <td>' . date_format(date_create($row->redeemed_coupon_log_added),"M d, Y h:i:s A") . '</td>
                    <td>' . $badge . '</td>
                </tr>
            ';
        }
		return $tbl;
	}
	
	public function _get_unused_voucher_tbl($unused){
		$info      = $this->_require_login();
		$tbl = '';
        foreach($unused as $row){
			$badge  = '<span class="badge badge-warning">Pending</span>';
            $tbl .= '
                <tr>
                    <td>' . $row->bc . '</td>
                    <td>' . $row->brands . '</td>
                    <td>' . $row->coupon_type_name . '</td>
                    <td>' . $row->coupon_cat_name . '</td>
                    <td>' . $row->coupon_name . '</td>
                    <td>' . $row->coupon_code . '</td>
                    <td>' . $row->coupon_amount . '</td>

                    <td>' . $row->coupon_regular_value . '</td>
                    <td>' . $row->coupon_value . '</td>
                    <td>' . $row->coupon_qty . '</td>
                    <td>' . $row->coupon_use . '</td>
                    
                    <td>' . date_format(date_create($row->coupon_start),"M d, Y") . '</td>
                    <td>' . date_format(date_create($row->coupon_end),"M d, Y") . '</td>
                    <td>' . $row->coupon_holder_type_name . '</td>
                    <td>' . $row->company_name . '</td>
                    <td>' . $row->coupon_holder_name . '</td>
                    <td>' . $row->coupon_holder_email . '</td>
                    <td>' . $row->coupon_holder_contact . '</td>
                    <td>' . $row->coupon_holder_address . '</td>
                    <td>' . $row->coupon_holder_tin . '</td>
                    <td>' . date_format(date_create($row->coupon_added),"M d, Y h:i:s A") . '</td>
                    <td>' . $badge . '</td>
                </tr>
            ';
        }
		return $tbl;
	}


    public function cancel_coupon(){
        $info = $this->_require_login();

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_id = clean_data(decode($this->input->post('id')));

            if (empty($coupon_id)) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->coupon_status == 3) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' is already Canceled', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $set    = ['coupon_status' => 3];
            $where  = ['coupon_id' => $coupon_id];
            $result = $this->main->update_data('coupon_tbl', $set, $where);
            $msg    = ($result == TRUE) ? '<div class="alert alert-success">'.SEC_SYS_NAME.' successfully Canceled.</div>' : '<div class="alert alert-danger">Error please try again!</div>';
            if ($result) {
                $this->_store_coupon_action_log(6, $coupon_id);
            }
            $this->session->set_flashdata('message', $msg);
            redirect($_SERVER['HTTP_REFERER']);
        }else{
            redirect('admin');
        }
    }

    public function cancel_transaction(){
        $info = $this->_require_login();

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_transaction_header_id = clean_data(decode($this->input->post('id')));

            if (empty($coupon_transaction_header_id)) {
                $alert_message = $this->alert_template('Transaction Header ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $coupon_transaction_header_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template('Transaction Header ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->coupon_transaction_header_status == 3) {
                $alert_message = $this->alert_template('Transaction Header is already Canceled', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $this->db->trans_start();

            $set    = ['coupon_transaction_header_status' => 3];
            $where  = ['coupon_transaction_header_id' => $coupon_transaction_header_id];
            $result = $this->main->update_data('coupon_transaction_header_tbl', $set, $where);
            
            if ($result == TRUE) {
                $coupon_join = [
                    'coupon_tbl b'            => 'b.coupon_id = a.coupon_id',
                    'coupon_value_type_tbl c' => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $coupon_transaction_header_id
                ];

                $transaction_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join);
                foreach ($transaction_details as $row) {
                    $set    = [
						'coupon_status' => 3,
						'coupon_code'	=> $row->coupon_code.'-xx'
					];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);
					$file_name = FCPATH . '/' . $row->coupon_pdf_path;
                    if ($result) {
						unlink($file_name);
                        $this->_store_coupon_action_log(6, $row->coupon_id);
                    }
                }
            }

            if($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                $message       = 'Error please try again!';
                $alert_message = $this->alert_template($message, FALSE);
            }else{
                $this->db->trans_commit();
                $message       = 'Transaction successfully Canceled.';
                $alert_message = $this->alert_template($message, TRUE);
            }
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER']);
        }else{
            redirect('admin');
        }
    }

    public function get_holder_type($coupon_category)
    {
        $info       = $this->_require_login();
        $coupon_cat = clean_data(decode($coupon_category));
        if($coupon_cat == 3){
			$result = '<option value="">Select another Category [Paid Evoucher is deprecated]</option>';
		} else {
			$check_cat  = $this->main->check_data('coupon_category_tbl', ['coupon_cat_id' => $coupon_cat]);
			$result     = '<option>Select Category First</option>';
			if($check_cat) {
				$holder_types = $this->main->get_data('coupon_holder_type_tbl', ['coupon_holder_type_status' => 1]);
				$result = '<option value="">Select Holder Type</option>';
				foreach ($holder_types as $row) {
					if ((!in_array($coupon_cat, paid_category())) && $row->coupon_holder_type_id == 4) {
						continue;
					}
					$result .= '<option value="'.encode($row->coupon_holder_type_id).'">'.$row->coupon_holder_type_name.'</option>';
				}
			}
		}
        
        echo $result;
    }

	public function get_scope_bc($scope_masking)
    {
        $info       = $this->_require_login();
		
        $scope_masking_id = clean_data(decode($scope_masking));
		$parent_db   = $GLOBALS['parent_db'];
        
		
		$check_scope  = $this->main->check_data('scope_masking_bc_tbl', ['scope_masking_id' => $scope_masking_id]);
		$scope_masking_bc_id_array = [];
		$result     = '
					<option value="">Select Business Center</option>
                    <option value="nationwide">NATIONWIDE</option>
		';
		if($check_scope) {
			$join_tbl = ["{$parent_db}.bc_tbl b" => 'a.bc_id = b.bc_id and b.bc_status = 1'];
			$scope_masking_bcs = $this->main->get_join('scope_masking_bc_tbl a', $join_tbl, FALSE, 'b.bc_name', false, false, ['scope_masking_id' => $scope_masking_id, 'scope_masking_bc_status' => 1]);
			// $result = '<option value="-1">Select All</option>';
			foreach ($scope_masking_bcs as $row) {
				array_push($scope_masking_bc_id_array,$row->bc_id);
			}
		}

		$bcs          = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
		foreach ($bcs as $row) {
			$selected = in_array($row->bc_id, $scope_masking_bc_id_array) ? 'selected' : '';
			$result .= '<option '.$selected.' value="'.encode($row->bc_id).'">'.$row->bc_name.'</option>';
		}
        echo $result;
    }

	public function pay_transaction()
    {
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_transaction_header_id = clean_data(decode($this->input->post('id')));

            if (empty($coupon_transaction_header_id)) {
                $alert_message = $this->alert_template('Transaction Header ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $coupon_transaction_header_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template('Transaction Header ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($this->input->post('sap_doc_no') == NULL || empty($this->input->post('sap_doc_no'))) {
                $alert_message = $this->alert_template('Document Number is Required', FALSE);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $this->db->trans_start();
            if ($check_id['result']) {
                $coupon_join = [
                    'coupon_tbl b'            => 'b.coupon_id = a.coupon_id AND b.coupon_status = 1',
                    'coupon_value_type_tbl c' => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $coupon_transaction_header_id
                ];

                $transaction_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join);
                $sap_doc_no          = clean_data($this->input->post('sap_doc_no'));
                foreach ($transaction_details as $row) {
                    $set    = [
                        'payment_status' => 1,
                        'sap_doc_no'     => $sap_doc_no,
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);
                }
                $this->_upload_transaction_attachment($coupon_transaction_header_id, 4);
            }

            if($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                $message       = 'Error please try again!';
                $alert_message = $this->alert_template($message, FALSE);
            }else{
                $this->db->trans_commit();
                $message       = 'Transaction successfully Paid.';
                $alert_message = $this->alert_template($message, TRUE);
            }
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER'].'#nav-approved');
		}else{
			redirect('admin');
		}
    }

	public function pay_coupon(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_id = clean_data(decode($this->input->post('id')));

            if (empty($coupon_id)) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->payment_status == 1) {
                $alert_message = $this->alert_template(''.SEC_SYS_NAME.' is already Paid', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($this->input->post('sap_doc_no') == NULL || empty($this->input->post('sap_doc_no'))) {
                $alert_message = $this->alert_template('Document Number is Required', FALSE);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $sap_doc_no = clean_data($this->input->post('sap_doc_no'));
            $set        = [
                'payment_status' => 1,
                'sap_doc_no'     => $sap_doc_no,
            ];

			$where  = ['coupon_id' => $coupon_id];
            $this->db->trans_start();
			$result = $this->main->update_data('coupon_tbl', $set, $where);
            if ($result) {
                $this->_upload_coupon_attachment($result['id'], 3);
            }
            if($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                $message       = 'Error please try again!';
                $alert_message = $this->alert_template($message, FALSE);
            }else{
                $this->db->trans_commit();
                $message       = ''.SEC_SYS_NAME.' successfully Paid.';
                $alert_message = $this->alert_template($message, TRUE);
            }
            $this->session->set_flashdata('message', $message);
            redirect($_SERVER['HTTP_REFERER'].'#nav-approved');
		}else{
			redirect('admin');
		}
    }

    public function get_pay_coupon_field($coupon_id)
    {
        $info         = $this->_require_login();
        $coupon_id    = clean_data(decode($coupon_id));
        $check_coupon = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
        $result       = '';
        if ($check_coupon['result']) {
            if ($check_coupon['info']->payment_status == '0') {
                $result = '
                    <div class="form-group">
                        <label for="">Doc No:</label>
                        <input type="text" name="sap_doc_no" class="form-control form-control-sm" value="" required>
                    </div>
                    <div class="form-group">
                        <label for="">Attachment:</label>
                        <input type="file" name="attachment[]" class="form-control-file" accept="image/png, image/jpeg, image/jpg, document/pdf" multiple required>
                    </div>';
            }
        }
        echo $result;
    }

    public function get_pay_trans_field($transaction_id)
    {
        $info           = $this->_require_login();
        $transaction_id = clean_data(decode($transaction_id));
        $check_coupon   = $this->main->check_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $transaction_id], TRUE);
        $result         = '';
        if ($check_coupon['result']) {
            $join        = ['coupon_tbl b' => 'b.coupon_id = a.coupon_id AND a.coupon_transaction_header_id = ' . $transaction_id];
            $coupon      = $this->main->get_join('coupon_transaction_details_tbl a', $join, TRUE);
            if ($coupon->payment_status == '0') {
                $result = '
                    <div class="form-group">
                        <label for="">Doc No:</label>
                        <input type="text" name="sap_doc_no" class="form-control form-control-sm" value="" required>
                    </div>
                    <div class="form-group">
                        <label for="">Attachment:</label>
                        <input type="file" name="attachment[]" class="form-control-file" accept="image/png, image/jpeg, image/jpg, document/pdf" multiple required>
                    </div>';
            }
        }
        echo $result;
    }

	public function get_payment_det_trans_field($transaction_id = NULL, $payment_type_id = NULL)
    {
		
		$read_only = FALSE;
		$has_attachement = FALSE;
        $info           = $this->_require_login();
        $transaction_id = $transaction_id !== NULL || $transaction_id != 0 ? clean_data(decode($transaction_id)) : NULL;
        $check_coupon   = $this->main->check_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $transaction_id], TRUE);
        $result         = '';
		
        if ($check_coupon['result']) {
			if(in_array($check_coupon['info']->coupon_cat_id, paid_category())){
				$join        = ['coupon_tbl b' => 'b.coupon_id = a.coupon_id AND a.coupon_transaction_header_id = ' . $transaction_id];
				// $coupon      = $this->main->get_join('coupon_transaction_details_tbl a', $join, TRUE);
				$rec_payment_type_id = $check_coupon['info']->payment_type_id;
				$rec_payment_terms = $check_coupon['info']->payment_terms;

				$dropdown = $this->_get_payment_types_selection(decode($payment_type_id), $rec_payment_type_id);
				
				$result = $this->_get_payment_details_fields(decode($payment_type_id), $rec_payment_type_id, $rec_payment_terms, $dropdown, $has_attachement, $read_only);
			}
        } else {
			$dropdown = $this->_get_payment_types_selection(decode($payment_type_id));
			$result = $this->_get_payment_details_fields(decode($payment_type_id), NULL, NULL, $dropdown);
		}



		// '<input type="file" name="attachment[]" class="form-control-file" accept="image/png, image/jpeg, image/jpg, document/pdf" multiple required>'
        echo $result;
    }

	public function _get_payment_details_fields($payment_type_id=NULL, $rec_payment_type_id=NULL,$rec_payment_terms=NULL,$dropdown=NULL, $has_attachement=FALSE, $read_only=FALSE){
		$disabled = $read_only ? 'disabled' : '';

		if(!$read_only){
			$result = '
				<div class="form-group">
					<label for="">Payment Type: *</label>
					<select name="payment_type_id" class="form-control form-control-sm" required>
						'.$dropdown.'
					</select>
				</div>';
			if($payment_type_id){
				if($payment_type_id == 4){
					$result .= '
						<div class="form-group">
							<label for="">Terms (in days): *</label>
							<input type="number" name="payment_terms" placeholder="" value="'.$rec_payment_terms.'" class="form-control form-control-sm" min="0.01" step="0.01" required>
						</div>';
				}
			} else {
				if($rec_payment_type_id == 4) {
					$result .= '
						<div class="form-group">
							<label for="">Terms (in days): *</label>
							<input type="number" name="payment_terms" placeholder="" value="'.$rec_payment_terms.'" class="form-control form-control-sm" min="0.01" step="0.01" required>
						</div>';
				}
			}
			
			if($has_attachement){
				$result .= '
					<label for="">Attachment:</label><br>
					<div class="custom-file mb-3">
						<input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple>
						<label class="custom-file-label" for="attachment[]">Choose file...</label>
					</div>';
			}
		} else {
			$result = '
				<div class="form-group">
					<label for="">Payment Type: *</label>
					<select '.$disabled.' class="form-control form-control-sm" required>
						'.$dropdown.'
					</select>
				</div>';
			$result .= '
				<div class="form-group">
					<label for="">Terms (in days):</label>
					<input type="number" '.$disabled.' placeholder="" value="'.$rec_payment_terms.'" class="form-control form-control-sm" min="0.01" step="0.01" required>
				</div>';
		}
		

		return $result;
	}

    private function _send_approver_notification($name, $category, $voucher_type, $user_type_id = 8) 
    {
        $parent_db = $GLOBALS['parent_db'];
        $where     = "a.user_type_id IN ($user_type_id) AND a.user_status = 1 AND a.user_id IN (SELECT user_id FROM user_access_tbl z WHERE z.coupon_cat_id = {$category} AND user_access_status = 1)";
        $approver  = $this->main->get_data("{$parent_db}.user_tbl a", $where);
        foreach ($approver as $row) {
            if ($row->user_email != '') {
                $employee_name = ucwords(strtolower(("{$row->user_fname}")));
                $recipient     = $row->user_email;
                $voucher_name  = $name;
                $this->_send_pending_notif($employee_name, $recipient, $voucher_name, $voucher_type, $user_type_id);
            }
        }
    }

    public function export_trans_details($id)
    {
		ini_set('max_execution_time', 0);
		ini_set('memory_limit','2048M');

		$parent_db = $GLOBALS['parent_db'];
		$info      = $this->_require_login();
		$id        = clean_data(decode($id));
        
        $coupon_trans_select = "*,
		IF(c.coupon_value_type_id=1,CONCAT(b.coupon_amount,'%'),CONCAT('P',b.coupon_amount)) AS coupon_amount,
        IF(b.is_nationwide = 1, 
            'Nationwide', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
        IF(b.is_orc = 1, 
            CONCAT_WS(', ', 'ORC', (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1)) AS 'products',
        (SELECT GROUP_CONCAT(x.brand_name SEPARATOR ', ') FROM coupon_brand_tbl z JOIN {$parent_db}.brand_tbl x ON z.brand_id = x.brand_id WHERE z.coupon_id = b.coupon_id AND z.coupon_brand_status = 1) AS brands";

        $coupon_join = [
            'coupon_tbl b'             => 'b.coupon_id = a.coupon_id',
            'coupon_value_type_tbl c'  => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $id,
            'coupon_holder_type_tbl d' => 'd.coupon_holder_type_id = b.coupon_holder_type_id',
            'coupon_category_tbl e'    => 'e.coupon_cat_id= b.coupon_cat_id'
        ];

        $trans_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join, FALSE, 'b.coupon_added DESC', FALSE, $coupon_trans_select);
        $trans_header  = $this->main->get_data('coupon_transaction_header_tbl a', ['coupon_transaction_header_id' => $id], TRUE);

        $this->load->library('excel');
        $spreadsheet = $this->excel;

        $style_bold = [ 
            'font' 	=> [ 
                'bold' => true,
            ]
        ];

        $sheet1 = $spreadsheet->createSheet(0);
        $sheet1
            ->setCellValue('A1','Business Center')
            ->setCellValue('B1','Brand')
            ->setCellValue('C1','Category')
            ->setCellValue('D1','Name')
            ->setCellValue('E1','Code')
            ->setCellValue('F1','Invoice Number')
            ->setCellValue('G1','Document Number')
            ->setCellValue('H1',''.SEC_SYS_NAME.' Value')

            ->setCellValue('I1',''.SEC_SYS_NAME.' Regular Amount')
            ->setCellValue('J1',''.SEC_SYS_NAME.' Paid Amount')
            ->setCellValue('K1','Qty')
            ->setCellValue('L1','Usage')

            ->setCellValue('M1','Start')
            ->setCellValue('N1','End')
            ->setCellValue('O1','Holder Type')
            ->setCellValue('P1','Holder Name')
            ->setCellValue('Q1','Holder Email')
            ->setCellValue('R1','Holder Contact')
            ->setCellValue('S1','Holder Address')
            ->setCellValue('T1','Holder TIN')
            ->setCellValue('U1','Status');

        $sheet1->getStyle('A1:T1')->applyFromArray($style_bold);
        $count = 2;
        foreach($trans_details as $row) { 
            $badge = '';
            if($row->coupon_status == 1 && $trans_header->coupon_transaction_header_status == 1){
                $badge  = 'Approved';
            }elseif($row->coupon_status == 0 && $trans_header->coupon_transaction_header_status == 0){
                $badge  = 'Inactive';
            }elseif($row->coupon_status == 2 && $trans_header->coupon_transaction_header_status == 2){
                $badge  = 'Pending';
            }

            $sheet1
                ->setCellValue('A'.$count, $row->bc)
                ->setCellValue('B'.$count, $row->brands)
                ->setCellValue('C'.$count, $row->coupon_cat_name)
                ->setCellValue('D'.$count, $row->coupon_name)
                ->setCellValue('E'.$count, $row->coupon_code)
                ->setCellValue('F'.$count, $row->invoice_number)
                ->setCellValue('G'.$count, $row->sap_doc_no)
                ->setCellValue('H'.$count, $row->coupon_amount)

                ->setCellValue('I'.$count, $row->coupon_regular_value)
                ->setCellValue('J'.$count, $row->coupon_value)
                ->setCellValue('K'.$count, $row->coupon_qty)
                ->setCellValue('L'.$count, $row->coupon_use)

                ->setCellValue('M'.$count, date_format(date_create($row->coupon_start),"M d, Y"))
                ->setCellValue('N'.$count, date_format(date_create($row->coupon_end),"M d, Y"))
                ->setCellValue('O'.$count, $row->coupon_holder_type_name)
                ->setCellValue('P'.$count, $row->coupon_holder_name)
                ->setCellValue('Q'.$count, $row->coupon_holder_email)
                ->setCellValue('R'.$count, $row->coupon_holder_contact)
                ->setCellValue('S'.$count, $row->coupon_holder_address)
                ->setCellValue('T'.$count, $row->coupon_holder_tin)
                ->setCellValue('U'.$count, $badge);

            $count++;
        }

        $high     = $sheet1->getHighestDataColumn();
        $cell_num = PHPExcel_Cell::columnIndexFromString($high);

        for($index = 0; $index <= $cell_num; $index++){
            $col = PHPExcel_Cell::stringFromColumnIndex($index);
            $sheet1->getColumnDimension($col)->setAutoSize(TRUE);
        }

        $sheet1->setTitle('Export Transaction Details');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $today = date('m/d/Y');
        header('Content-Disposition: attachment;filename="'. $trans_header->coupon_transaction_header_name . '-trans_details-' . $today . '.xlsx"');
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

}
