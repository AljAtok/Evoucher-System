<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {

	public function __construct()
    {
    	parent::__construct();
    	$this->load->model('main_model', 'main');
        $GLOBALS['parent_db'] = parent_db();
	}

	public function index()
    {
		$info = $this->_require_login();
		redirect('admin/employee');
	}

	public function _require_login()
    {
		$login = $this->session->userdata('evoucher-user');
		if(isset($login)){
			$user_type = decode($login['user_type_id']);
			if($login['user_reset'] == 0){
				if($user_type == "1"){
					return $login;
				}elseif($user_type == "7"){
					redirect('creator');
				}elseif($user_type == "8"){
					redirect('approver');
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

    public function employee()
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

        $where  = 'a.user_type_id IN (7,8,9,11)';
        $join   = [ "{$parent_db}.user_type_tbl b" => 'b.user_type_id = a.user_type_id' ];
        $select = "a.*, b.user_type_name, (SELECT GROUP_CONCAT(x.coupon_cat_name SEPARATOR ', ') FROM user_access_tbl z JOIN coupon_category_tbl x ON x.coupon_cat_id = z.coupon_cat_id WHERE z.user_id = a.user_id AND user_access_status = 1) AS 'access'";

        $data['title']           = 'Employee';
        $data['bc']              = $this->main->get_data("{$parent_db}.bc_tbl", [ 'bc_status' => 1 ]);
        $data['user_types']      = $this->main->get_data("{$parent_db}.user_type_tbl", 'user_type_status = 1 AND user_type_id IN (7,8,9,11)');
        $data['coupon_category'] = $this->main->get_data('coupon_category_tbl', ['coupon_cat_status' => 1]);
        $data['users']           = $this->main->get_join("{$parent_db}.user_tbl a", $join, FALSE, FALSE, FALSE, $select, $where);
        $data['content']         = $this->load->view('admin/employee/employee_content', $data, TRUE);
        $this->load->view('admin/templates', $data);
    }

    public function store_employee()
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            redirect($_SERVER['HTTP_REFERER']);
        }

        $rules = [
            [ 'field' => 'fname'      , 'label' => 'First Name'    , 'rules' => 'required'             ],
            [ 'field' => 'lname'      , 'label' => 'Last Name'     , 'rules' => 'required'             ],
            [ 'field' => 'password'   , 'label' => 'Password'      , 'rules' => 'required'             ],
            [ 'field' => 'email'      , 'label' => 'Email'         , 'rules' => 'required|valid_email' ],
            [ 'field' => 'employee_no', 'label' => 'Employee No'   , 'rules' => 'required'             ],
            [ 'field' => 'user_type'  , 'label' => 'User Type'     , 'rules' => 'required'             ],
            [ 'field' => 'access[]'   , 'label' => 'Allowed Access', 'rules' => 'required'             ],
        ];

        $this->_run_form_validation($rules);

        $fname     = clean_data($this->input->post('fname'));
        $lname     = clean_data($this->input->post('lname'));
        $emp_no    = clean_data($this->input->post('employee_no'));
        $email     = clean_data($this->input->post('email'));
        $password  = clean_data($this->input->post('password'));
        $user_type = clean_data(decode($this->input->post('user_type')));

        $check_employee_no = $this->main->check_data("{$parent_db}.user_tbl", ['user_employee_no' => $emp_no]);
        if ($check_employee_no) {
            $alert_message = $this->alert_template('Empoyee No Already Used', false);
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER']);
        }

        if (!empty($email)) {
            $check_email = $this->main->check_data("{$parent_db}.user_tbl", ['user_email' => $email]);
            if ($check_email) {
                $alert_message = $this->alert_template('Email Already Used', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }
        }

        $data = [
            'user_fname'       => strtoupper(trim($fname)),
            'user_lname'       => strtoupper(trim($lname)),
            'user_employee_no' => $emp_no,
            'user_email'       => $email,
            'user_password'    => encode($password),
            'user_type_id'     => $user_type,
            'user_reset'       => 1,
            'user_status'      => 1
        ];
        
        $this->db->trans_start();
        $result = $this->main->insert_data("{$parent_db}.user_tbl", $data, TRUE);
        if ($result['result']) {
            $this->_store_user_access($result['id']);
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
        redirect($_SERVER['HTTP_REFERER']);
    }

    private function _store_user_access($user_id)
    {
        $access_list  = $this->input->post('access');
        $access_count = count($access_list);
        for ($index = 0; $index < $access_count; $index++) {
            $access = decode(clean_data($access_list[$index]));
            $data     = [
                'user_id'            => $user_id,
                'coupon_cat_id'      => $access,
                'user_access_status' => 1,
            ];
            $result = $this->main->insert_data('user_access_tbl', $data);
        } 
    }

    private function _update_user_access($user_id)
    {
        $set          = [ 'user_access_status' => 0 ];
        $where        = [ 'user_id' => $user_id ];
        $result       = $this->main->update_data('user_access_tbl', $set, $where);
        $access_list  = $this->input->post('access');
        $access_count = count($access_list);

        for ($index = 0; $index < $access_count; $index++) {
            $access = decode(clean_data($access_list[$index]));
            $check_user_access = $this->main->check_data('user_access_tbl', ['user_id' => $user_id, 'coupon_cat_id' => $access], TRUE);
            if ($check_user_access['result'] ) {
                $set    = [ 'user_access_status' => 1 ];
                $where  = [ 'user_access_id' => $check_user_access['info']->user_access_id ];
                $result = $this->main->update_data('user_access_tbl', $set, $where);
            } else {
                $data     = [
                    'user_id'            => $user_id,
                    'coupon_cat_id'      => $access,
                    'user_access_status' => 1,
                ];
                $result = $this->main->insert_data('user_access_tbl', $data, TRUE);
            }
        } 
    }

    public function update_employee()
    {
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            redirect($_SERVER['HTTP_REFERER']);
        }

        $rules = [
            [ 'field' => 'fname'      , 'label' => 'First Name'    ,'rules' => 'required'            ],
            [ 'field' => 'lname'      , 'label' => 'Last Name'     ,'rules' => 'required'            ],
            [ 'field' => 'email'      , 'label' => 'Email'         ,'rules' => 'required|valid_email'],
            [ 'field' => 'employee_no', 'label' => 'Employee No'   ,'rules' => 'required'            ],
            [ 'field' => 'user_type'  , 'label' => 'User Type'     ,'rules' => 'required'            ],
            [ 'field' => 'access[]'   , 'label' => 'Allowed Access','rules' => 'required'            ],
        ];
        $this->_run_form_validation($rules);

        $user_id   = clean_data(decode($this->input->post('id')));
        $fname     = clean_data($this->input->post('fname'));
        $lname     = clean_data($this->input->post('lname'));
        $emp_no    = clean_data($this->input->post('employee_no'));
        $email     = clean_data($this->input->post('email'));
        $user_type = clean_data(decode($this->input->post('user_type')));

        $check_id = $this->main->check_data("{$parent_db}.user_tbl", ['user_id' => $user_id]);
        if (!$check_id) {
            $alert_message = $this->alert_template('Employee ID Doesn\'t Exist', false);
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER']);
        }

        $check_employee_no = $this->main->check_data("{$parent_db}.user_tbl", ['user_id !=' => $user_id, 'user_employee_no'=> $emp_no]);
        if ($check_employee_no) {
            $alert_message = $this->alert_template('Empoyee No Already Used', false);
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER']);
        }

        if (!empty($email)) {
            $check_email = $this->main->check_data("{$parent_db}.user_tbl", ['user_id !=' => $user_id, 'user_email' => $email]);
            if ($check_email) {
                $alert_message = $this->alert_template('Email Already Used', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }
        }

        $set = [
            'user_fname'       => strtoupper(trim($fname)),
            'user_lname'       => strtoupper(trim($lname)),
            'user_employee_no' => $emp_no,
            'user_email'       => $email,
            'user_type_id'     => $user_type,
        ];

        $where = [
            'user_id' => $user_id
        ];

        $this->db->trans_start();
        $result = $this->main->update_data("{$parent_db}.user_tbl", $set, $where);
        
        if ($result) {
            $this->_update_user_access($user_id);
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
        redirect($_SERVER['HTTP_REFERER']);
    }
    
    public function modal_employee($id)
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];
        $id        = clean_data(decode($id));
        $check_id  = $this->main->check_data("{$parent_db}.user_tbl", ['user_id' => $id], TRUE);
        if ($check_id['result']) {
            $user_access = $this->main->get_data('user_access_tbl', ['user_access_status' => 1, 'user_id' => $id], FALSE, FALSE, FALSE, 'coupon_cat_id');
            $data['user_types']      = $this->main->get_data("{$parent_db}.user_type_tbl", 'user_type_status = 1 AND user_type_id IN (7,8,9,11)');
            $data['coupon_category'] = $this->main->get_data('coupon_category_tbl', ['coupon_cat_status' => 1]);
            $data['access']          = array_column($user_access, 'coupon_cat_id');
            $data['user']            = $check_id['info'];

            $html = $this->load->view('admin/employee/employee_edit_modal_content', $data, TRUE);
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

    public function modal_employee_password_reset($id)
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];
        $id        = clean_data(decode($id));
        $check_id  = $this->main->check_data("{$parent_db}.user_tbl", ['user_id' => $id], TRUE);
        if ($check_id['result']) {
            $data['user']   = $check_id['info'];
            $html = $this->load->view('admin/employee/employee_password_reset_edit_modal_content', $data, TRUE);
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

    public function reset_employee()
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            redirect($_SERVER['HTTP_REFERER']);
        }

        $password = clean_data($this->input->post('password'));
        $user_id  = clean_data(decode($this->input->post('id')));

        $check_id = $this->main->check_data("{$parent_db}.user_tbl", ['user_id' => $user_id]);
        if (!$check_id) {
            $alert_message = $this->alert_template('Employee ID Doesn\'t Exist', false);
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER']);
        }

        if (empty($password)) {
            $alert_message = $this->alert_template('Password is Required', false);
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER']);
        }

        $set = [
            'user_password' => encode($password),
            'user_reset'    => 1,
        ];

        $where = [
            'user_id' => $user_id
        ];

        $this->db->trans_start();
        $this->main->update_data("{$parent_db}.user_tbl", $set, $where);
        
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
        redirect($_SERVER['HTTP_REFERER']);
    }

	public function deactivate_employee()
    {
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $user_id = clean_data(decode($this->input->post('id')));
            if (empty($user_id)) {
                $alert_message = $this->alert_template('Employee ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data("{$parent_db}.user_tbl", ['user_id' => $user_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template('Employee ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->user_status == 0) {
                $alert_message = $this->alert_template('Employee is already Deactivated', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

			$set    = ['user_status' => 0];
			$where  = ['user_id' => $user_id];
			$result = $this->main->update_data("{$parent_db}.user_tbl", $set, $where);
			$msg    = ($result == TRUE) ? '<div class="alert alert-success">Employee successfully deactivated.</div>' : '<div class="alert alert-danger">Error please try again!</div>';
            $this->session->set_flashdata('message', $msg);
            redirect($_SERVER['HTTP_REFERER']);
		}else{
			redirect('admin');
		}
	}

	public function activate_employee()
    {
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $user_id = clean_data(decode($this->input->post('id')));

            if (empty($user_id)) {
                $alert_message = $this->alert_template('Employee ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data("{$parent_db}.user_tbl", ['user_id' => $user_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template('Employee ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->user_status == 1) {
                $alert_message = $this->alert_template('Employee is already Activated', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

			$set    = ['user_status' => 1];
			$where  = ['user_id' => $user_id];
			$result = $this->main->update_data("{$parent_db}.user_tbl", $set, $where);
			$msg    = ($result == TRUE) ? '<div class="alert alert-success">Employee successfully Activated.</div>' : '<div class="alert alert-danger">Error please try again!</div>';
            $this->session->set_flashdata('message', $msg);
            redirect($_SERVER['HTTP_REFERER']);
		}else{
			redirect('admin');
		}
    }

    public function redeem_coupon()
    {
        $data['title']   = 'Redeem Coupon';
        $data['content'] = $this->load->view('admin/coupon/redeem_coupon_content', $data, TRUE);
        $this->load->view('admin/templates', $data);
    }

    public function standard_coupon()
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

		$join_salable = [
			"{$parent_db}.product_tbl b" => 'a.prod_id = b.prod_id',
			"{$parent_db}.unit_tbl c"    => 'a.unit_id = c.unit_id',
			"{$parent_db}.unit_tbl d"    => 'a.2nd_uom = d.unit_id'
        ];

        $coupon_join = [
            'coupon_value_type_tbl b'  => 'b.coupon_value_type_id = a.coupon_value_type_id AND a.coupon_type_id = 1',
            'coupon_holder_type_tbl c' => 'c.coupon_holder_type_id = a.coupon_holder_type_id',
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


        $data['products']        = $this->main->get_join("{$parent_db}.product_sale_tbl a", $join_salable);
        $data['brand']           = $this->main->get_data("{$parent_db}.brand_tbl", ['brand_status' => 1]);
        $data['bc']              = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
        $data['category']        = $this->main->get_data('coupon_category_tbl a', ['coupon_cat_status' => 1]);
        $data['holder_type']     = $this->main->get_data('coupon_holder_type_tbl a', ['coupon_holder_type_status' => 1]);
        $data['coupon_type']     = $this->main->get_data('coupon_type_tbl', ['coupon_type_status' => 1]);
        $data['value_type']      = $this->main->get_data('coupon_value_type_tbl a', ['coupon_value_type_status' => 1]);
        $data['pending_coupon']  = $this->main->get_join('coupon_tbl a', $coupon_join, FALSE, 'coupon_added DESC', FALSE ,$coupon_select, ['a.coupon_status' => 2]);
        $data['approved_coupon'] = $this->main->get_join('coupon_tbl a', $coupon_join, FALSE, 'coupon_added DESC', FALSE ,$coupon_select , ['a.coupon_status' => 1]);
        $data['inactive_coupon'] = $this->main->get_join('coupon_tbl a', $coupon_join, FALSE, 'coupon_added DESC', FALSE ,$coupon_select , ['a.coupon_status' => 0]);
        $data['title']           = 'Standard Coupon';
        $data['content']         = $this->load->view('admin/coupon/standard_coupon_content', $data, TRUE);
        $this->load->view('admin/templates', $data);
    }

    public function product_coupon()
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

		$join_salable = [
			"{$parent_db}.product_tbl b" => 'a.prod_id = b.prod_id',
        ];

        $coupon_trans_select = '*, (SELECT COUNT(*) FROM coupon_transaction_details_tbl z WHERE a.coupon_transaction_header_id = z.coupon_transaction_header_id ) as `coupon_qty`';

        $join_coupon = array(
        	"{$parent_db}.user_tbl b" => 'a.user_id = b.user_id',
        	'coupon_category_tbl c'   => 'a.coupon_cat_id = c.coupon_cat_id'
        );
        
        $data['pending_coupon_trans']  = $this->main->get_join('coupon_transaction_header_tbl a', $join_coupon, FALSE, 'coupon_transaction_header_added DESC', FALSE, $coupon_trans_select, ['a.coupon_transaction_header_status' => 2]);
        $data['approved_coupon_trans'] = $this->main->get_join('coupon_transaction_header_tbl a', $join_coupon, FALSE, 'coupon_transaction_header_added DESC', FALSE, $coupon_trans_select, ['a.coupon_transaction_header_status' => 1]);
        $data['inactive_coupon_trans'] = $this->main->get_join('coupon_transaction_header_tbl a', $join_coupon, FALSE, 'coupon_transaction_header_added DESC', FALSE, $coupon_trans_select, ['a.coupon_transaction_header_status' => 0]);

        $data['products']    = $this->main->get_join("{$parent_db}.product_sale_tbl a", $join_salable);
        $data['brand']       = $this->main->get_data("{$parent_db}.brand_tbl", ['brand_status' => 1]);
        $data['bc']          = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
        $data['coupon_type'] = $this->main->get_data('coupon_type_tbl', ['coupon_type_status' => 1]);
        $data['value_type']  = $this->main->get_data('coupon_value_type_tbl a', ['coupon_value_type_status' => 1]);
        $data['category']    = $this->main->get_data('coupon_category_tbl a', ['coupon_cat_status' => 1]);
        $data['holder_type'] = $this->main->get_data('coupon_holder_type_tbl a', ['coupon_holder_type_status' => 1]);
        $data['title']       = 'Product Coupon';
        $data['content']     = $this->load->view('admin/coupon/product_coupon_content', $data, TRUE);
        $this->load->view('admin/templates', $data);
    }

    public function zip_coupon($trans_id)
    {
    	$info = $this->_require_login();
    	ini_set('max_execution_time', 0); 
        ini_set('memory_limit','2048M');
    	$trans_id = decode($trans_id);

    	$this->load->library('zip');
        
    	$join_voucher = array(
    		'coupon_transaction_details_tbl b' => 'a.coupon_transaction_header_id = b.coupon_transaction_header_id AND b.coupon_transaction_details_status = 1 AND a.coupon_transaction_header_status = 1 AND a.coupon_transaction_header_id = ' . $trans_id,
    		'coupon_tbl c'                     => 'b.coupon_id = c.coupon_id AND c.coupon_status = 1'
    	);
    	$get_voucher = $this->main->get_join('coupon_transaction_header_tbl a', $join_voucher);


    	foreach($get_voucher as $row){
    		$file_name = $row->coupon_transaction_header_name;
    		$this->zip->read_file(FCPATH . '/' . $row->coupon_pdf_path);
    	}

    	$this->zip->archive(FCPATH . '/assets/coupons/' . $file_name . '.zip');
         
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
            [ 'field' => 'name'        , 'label' => 'Coupon Name'         ,'rules' => 'required|max_length[70]' ],
            [ 'field' => 'code'        , 'label' => 'Coupon Code'         ,'rules' => 'required'                ],
            [ 'field' => 'amount'      , 'label' => 'Coupon amount'       ,'rules' => 'required|integer'        ],
            [ 'field' => 'qty'         , 'label' => 'Coupon Qty'          ,'rules' => 'required|integer'        ],
            [ 'field' => 'date_range'  , 'label' => 'Voucher Start & End' ,'rules' => 'required'                ],
            [ 'field' => 'category'    , 'label' => 'Coupon Category'     ,'rules' => 'required'                ],
            [ 'field' => 'value_type'  , 'label' => 'Value Type'          ,'rules' => 'required'                ],
            [ 'field' => 'holder_type' , 'label' => 'Holder Type'         ,'rules' => 'required'                ],
        ];

        $this->_run_form_validation($rules);
        $category = decode(clean_data($this->input->post('category')));

        if ($category == '3' || $category == '1') { 
            $this->_validate_attachment();
        }

        $additional_rules = [];
        if ($category == '3') { 
            $rules = [
                [
                    'field' => 'address',
                    'label' => 'Holder Address',
                    'rules' => 'required'
                ],
                [
                    'field' => 'voucher-value',
                    'label' => 'Voucher Paid Value',
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
            $this->_send_approver_notification($name, $category, 'Standard Voucher');

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

    private function _store_coupon_trans_header($coupon_name, $category, $start, $end, $payment_status, $invoice_number)
    {
    	$info    = $this->_require_login();
    	$user_id = decode($info['user_id']);
        $data = [
        	'coupon_cat_id'                    => $category,
        	'user_id'                          => $user_id,
        	'invoice_number'                   => $invoice_number,
        	'payment_status'                   => $payment_status,
        	'coupon_transaction_header_name'   => strtoupper(trim($coupon_name)),
        	'coupon_transaction_header_start'  => $start,
        	'coupon_transaction_header_end'    => $end,
        	'coupon_transaction_header_added'  => date_now(),
        	'coupon_transaction_header_status' => 2
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
            [ 'field' => 'bc[]'          , 'label' => 'Business Center'     ,'rules' => 'required'                ],
            [ 'field' => 'brand[]'       , 'label' => 'Brand'               ,'rules' => 'required'                ],
            [ 'field' => 'product[]'     , 'label' => 'Product'             ,'rules' => 'required'                ],
            [ 'field' => 'name'          , 'label' => 'Coupon Name'         ,'rules' => 'required|max_length[70]' ],
            [ 'field' => 'amount'        , 'label' => 'Coupon amount'       ,'rules' => 'required|integer'        ],
            [ 'field' => 'voucher-value' , 'label' => 'e-Voucher amount'    ,'rules' => 'integer'                 ],
            [ 'field' => 'date_range'    , 'label' => 'Voucher Start & End' ,'rules' => 'required'                ],
            [ 'field' => 'category'      , 'label' => 'Coupon Category'     ,'rules' => 'required'                ],
            [ 'field' => 'value_type'    , 'label' => 'Value Type'          ,'rules' => 'required'                ],
            [ 'field' => 'holder_type'   , 'label' => 'Holder Type'         ,'rules' => 'required'                ],
        ];

        $this->_run_form_validation($rules);

        $category    = decode(clean_data($this->input->post('category')));
        $holder_type = decode(clean_data($this->input->post('holder_type')));

        if ($category == '3' || $category == '1') { 
            if ($holder_type != '1') {
                $this->_validate_attachment();
            }
        }

        $additional_rules = [];
        if ($category == '3') { 
            $rules = [
                [ 'field' => 'address'      , 'label' => 'Holder Address'    , 'rules' => 'required'],
                [ 'field' => 'voucher-value', 'label' => 'Voucher Paid Value', 'rules' => 'required'],
                [ 'field' => 'tin'          , 'label' => 'Holder TIN'        , 'rules' => 'required'],
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
        $payment_status     = ($holder_type == 4) ? 0 : 1;
        $invoice_number     = ($holder_type == 4) ? clean_data($this->input->post('invoice_num')) : '';

        if ($holder_type == 4) {
            if ($this->input->post('invoice_num') == NULL || empty($this->input->post('invoice_num'))) {
                $alert_message = $this->alert_template('Invoice Number is Required', FALSE);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }
        }

        $this->_validate_prod_sale($prod_sale);
        $this->_validate_brand($brand);
        $this->_validate_bc($bc);
        $this->_validate_category($category);
        $this->_validate_value_type($value_type);
        $this->_validate_holder_type($holder_type);

        $this->db->trans_start();
        $trans_result = $this->_store_coupon_trans_header($name, $category, $start, $end, $payment_status, $invoice_number);
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
                'payment_status'        => $payment_status
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

                $pdf_path = $this->_generate_coupon_pdf($coupon_result['id']);
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

            $this->_send_approver_notification($name, $category, $category_name);
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
            [ 'field' => 'id'          , 'label' => 'Coupon ID'           , 'rules' => 'required'                ],
        ];
        $this->_run_form_validation($rules);

        $coupon_id    = decode(clean_data($this->input->post('id')));
        $check_coupon = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
        if ($check_coupon['result'] != TRUE) {
            $this->session->set_flashdata('message', 'Invalid Coupon ID');
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
            [ 'field' => 'date_range'  , 'label' => 'Voucher Start & End' , 'rules' => 'required'                ],
        ];

        $this->_run_form_validation($rules);
        $category = decode(clean_data($this->input->post('category')));

        if ($category == '3' || $category == '1') { 
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
            [ 'field' => 'id'          , 'label' => 'Coupon ID'           , 'rules' => 'required'                ],
            [ 'field' => 'date_range'  , 'label' => 'Voucher Start & End' , 'rules' => 'required'                ],
        ];

        $this->_run_form_validation($rules);

        $coupon_id    = decode(clean_data($this->input->post('id')));
        $check_coupon = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
        if ($check_coupon['result'] != TRUE) {
            $this->session->set_flashdata('message', 'Invalid Coupon ID');
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

        if ($category == '3' || $category == '1') { 
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
            [ 'field' => 'id', 'label' => 'Coupon ID', 'rules' => 'required' ],
        ];
        $this->_run_form_validation($rules);

        $transaction_id    = decode(clean_data($this->input->post('id')));
        $check_transaction = $this->main->check_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $transaction_id], TRUE);
        if ($check_transaction['result'] != TRUE) {
            $alert_message = $this->alert_template('Invalid Coupon ID', FALSE);
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
            [ 'field' => 'date_range'  , 'label' => 'Voucher Start & End' , 'rules' => 'required'                ],
        ];

        $this->_run_form_validation($rules);
        $category = $check_transaction['info']->coupon_cat_id;
        $dates    = explode(' - ', clean_data($this->input->post('date_range')));
        $start    = date('Y-m-d', strtotime($dates[0]));
        $end      = date('Y-m-d', strtotime($dates[1]));

        if ($category == '3' || $category == '1') { 
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
            'coupon_transaction_header_start'  => $start,
            'coupon_transaction_header_end'    => $end,
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
                $alert_message = $this->alert_template('Coupon ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template('Coupon ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->coupon_status == 1) {
                $alert_message = $this->alert_template('Coupon is already Activated', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

			$set    = ['coupon_status' => 1];
			$where  = ['coupon_id' => $coupon_id];
			$result = $this->main->update_data('coupon_tbl', $set, $where);
			$msg    = ($result == TRUE) ? '<div class="alert alert-success">Coupon successfully Activated.</div>' : '<div class="alert alert-danger">Error please try again!</div>';
            if ($result) {
                $this->_store_coupon_action_log(3, $coupon_id);
            }
            $this->session->set_flashdata('message', $msg);
            redirect($_SERVER['HTTP_REFERER'].'#nav-approved');
		}else{
			redirect('admin');
		}
    }

	public function deactivate_coupon(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){

            $coupon_id = clean_data(decode($this->input->post('id')));

            if (empty($coupon_id)) {
                $alert_message = $this->alert_template('Coupon ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template('Coupon ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->coupon_status == 0) {
                $alert_message = $this->alert_template('Coupon is already Deactivated', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

			$set         = ['coupon_status' => 0];
			$where       = ['coupon_id' => $coupon_id];
			$result      = $this->main->update_data('coupon_tbl', $set, $where);
			$msg         = ($result == TRUE) ? '<div class="alert alert-success">Coupon successfully deactivated.</div>' : '<div class="alert alert-danger">Error please try again!</div>';
            if ($result) {
                $this->_store_coupon_action_log(4, $coupon_id);
            }
            $this->session->set_flashdata('message', $msg);
            redirect($_SERVER['HTTP_REFERER'].'#nav-inactive');
		}else{
			redirect('admin');
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
            $data['category']     = $this->main->get_data('coupon_category_tbl a', ['coupon_cat_status' => 1]);
            $holder_type_where = ($check_id['info']->coupon_cat_id != 3) ? 'coupon_holder_type_status = 1 AND coupon_holder_type_id != 4' : 'coupon_holder_type_status = 1';
            $data['holder_type']  = $this->main->get_data('coupon_holder_type_tbl a', $holder_type_where);
            $data['brand']        = $this->main->get_data("{$parent_db}.brand_tbl", ['brand_status' => 1]);
            $data['bc']           = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
            $data['coupon_type']  = $this->main->get_data('coupon_type_tbl', ['coupon_type_status' => 1]);
            $data['value_type']   = $this->main->get_data('coupon_value_type_tbl a', ['coupon_value_type_status' => 1]);


            $html = $this->load->view('admin/coupon/standard_coupon_edit_modal_content', $data, TRUE);

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

            if ( !empty($coupon->is_orc) ) {
                $orc_list = array_column($this->main->get_data("{$parent_db}.orc_list_tbl", ['orc_list_status' => 1]), 'prod_sale_id');
                $orcs      = implode(', ', $orc_list);
                $coupon_prod_where = 'coupon_prod_sale_status = 1 AND coupon_id = ' . $id . ' AND prod_sale_id NOT IN (' . $orcs . ')';
                $data['coupon_prod']  = array_column($this->main->get_data('coupon_prod_sale_tbl', $coupon_prod_where, FALSE, 'prod_sale_id'), 'prod_sale_id');
            } else {
                $data['coupon_prod']  = array_column($this->main->get_data('coupon_prod_sale_tbl', ['coupon_id' => $id, 'coupon_prod_sale_status' => 1], FALSE, 'prod_sale_id'), 'prod_sale_id');
            }

            $data['coupon_brand'] = array_column($this->main->get_data('coupon_brand_tbl', ['coupon_id' => $id, 'coupon_brand_status' => 1], FALSE, 'brand_id'), 'brand_id');
            $data['coupon_bc']    = array_column($this->main->get_data('coupon_bc_tbl', ['coupon_id' => $id, 'coupon_bc_status' => 1], FALSE, 'bc_id'), 'bc_id');
            $holder_type_where = ($check_transaction['info']->coupon_cat_id != 3) ? 'coupon_holder_type_status = 1 AND coupon_holder_type_id != 4' : 'coupon_holder_type_status = 1';
            $data['holder_type']  = $this->main->get_data('coupon_holder_type_tbl a', $holder_type_where);
            $data['category']     = $this->main->get_data('coupon_category_tbl a', ['coupon_cat_status' => 1]);
            $data['brand']        = $this->main->get_data("{$parent_db}.brand_tbl", ['brand_status' => 1]);
            $data['bc']           = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
            $data['coupon_type']  = $this->main->get_data('coupon_type_tbl', ['coupon_type_status' => 1]);
            $data['value_type']   = $this->main->get_data('coupon_value_type_tbl a', ['coupon_value_type_status' => 1]);

            $html = $this->load->view('admin/coupon/transaction_coupon_edit_modal_content', $data, TRUE);

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
            $holder_type_where = ($check_id['info']->coupon_cat_id != 3) ? 'coupon_holder_type_status = 1 AND coupon_holder_type_id != 4' : 'coupon_holder_type_status = 1';
            $data['holder_type']  = $this->main->get_data('coupon_holder_type_tbl a', $holder_type_where);
            $data['category']     = $this->main->get_data('coupon_category_tbl a', ['coupon_cat_status' => 1]);
            $data['brand']        = $this->main->get_data("{$parent_db}.brand_tbl", ['brand_status' => 1]);
            $data['bc']           = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
            $data['coupon_type']  = $this->main->get_data('coupon_type_tbl', ['coupon_type_status' => 1]);
            $data['value_type']   = $this->main->get_data('coupon_value_type_tbl a', ['coupon_value_type_status' => 1]);

            $html = $this->load->view('admin/coupon/product_coupon_edit_modal_content', $data, TRUE);

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

    private function _generate_coupon_pdf($coupon_id)
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
        $pdf->SetAutoPageBreak(true);
        $pdf->SetAuthor('Bounty Agro Ventures, Inc.');
        $pdf->SetDisplayMode('real', 'default');
        $pdf->SetFont('helvetica', 'B', 14, '', 'false');

        $custom_layout = [ 215.9, 92.9 ];
		if($one_pager){
			$custom_layout = [ 215.9, 183 ];
		}
        $pdf->AddPage('L', $custom_layout);

        $style = [ 
            'border'  => false,
            'padding' => 0,
            'bgcolor' => false
        ];

        

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


        if($coupon->coupon_cat_id == 1){ //GIFT EVOUCHER

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

			if($one_pager){
				$y = 96;
			} else {
				$y = 7;
				$pdf->AddPage('L', $custom_layout);
			}

	        $pdf->setJPEGQuality(100);
	        $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
	        $pdf->Image($back_design, 7, $y, 200, 80, 'JPG', '', '', true, 150, '', false, false, 1, false, false, false);



	    }elseif($coupon->coupon_cat_id == 2){ //BIRTHDAY EVOUCHER

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

			if($one_pager){
				$y = 96;
			} else {
				$y = 7;
				$pdf->AddPage('L', $custom_layout);
			}

            $pdf->setJPEGQuality(100);
            $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
            $pdf->Image($back_design, 7, $y, 200, 80, 'JPG', '', '', true, 150, '', false, false, 1, false, false, false);
	    }elseif($coupon->coupon_cat_id == 3){ //PAID EVOUCHER
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
		$one_pager = true;

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
        $pdf->SetAuthor('Bounty Agro Ventures, Inc.');
        $pdf->SetDisplayMode('real', 'default');
        $pdf->SetFont('helvetica', 'B', 14, '', 'false');

        $custom_layout = [ 215.9, 92.9 ];
		if($one_pager){
			$custom_layout = [ 215.9, 183 ];
		}
        $pdf->AddPage('L', $custom_layout);

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

        //$pdf->writeHTML($html, true, false, true, false, '');

        $invalid_chars = [ '<' ,'>' ,':' ,'"' ,'/' ,'\\' ,'|' ,'?' ,'*', ' ', '[', ']', '\'', '(', ')' ];
        $cleaned_name  = str_replace($invalid_chars, '-', $coupon->coupon_name);
        $date_created = strtotime($coupon->coupon_added);
        $file_name     = $cleaned_name . '_' . $coupon->coupon_code . '_' . $date_created . '.pdf';

        $pdf->Output($file_name . '.pdf', 'I');

        /*$cwd          = getcwd();
        $date_created = strtotime($coupon->coupon_added);

        $invalid_chars = [ '<' ,'>' ,':' ,'"' ,'/' ,'\\' ,'|' ,'?' ,'*', ' ', '[', ']', '\'', '(', ')' ];
        $cleaned_name  = str_replace($invalid_chars, '-', $coupon->coupon_name);
        $file_name     = $cleaned_name . '_' . $coupon->coupon_code . '_' . $date_created . '.pdf';
        $save_path     = '/assets/coupons/' . $file_name;

        $pdf->Output($cwd . $save_path, 'F');
        return $save_path;*/
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
            ];

            $data['trans_details'] = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join, FALSE, 'b.coupon_added DESC', FALSE, $coupon_trans_select);
            $data['trans_header']  = $check_id['info'];

            $html = $this->load->view('admin/coupon/coupon_trans_modal_content', $data, TRUE);

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
        ];

        $data['trans_details'] = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join, FALSE, 'b.coupon_added DESC', FALSE, $coupon_trans_select);

        return $this->load->view('admin/coupon/success_product_coupon_trans_details', $data, TRUE);

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
                        $message = 'Hi ka-Chooks! Ikaw ay nakatanggap ng Chooks-to-Go eVoucher for ' . $value . '. Ang iyong voucher code ay ' . $code . ' at ito ay valid lamang ' . $coupon['info']->bc;
                    } else {
                        if ($coupon['info']->coupon_value_type_id == 1 && $coupon['info']->coupon_amount == '100') { // PERCENTAGE
                            $message = 'Hi ka-Chooks! Ikaw ay nakatanggap ng Chooks-to-Go eVoucher for one(1) ' . $coupon['info']->products . '. Ang iyong voucher code ay ' . $code . ' at ito ay valid lamang ' . $location;
                        } else {
                            $message = 'Hi ka-Chooks! Ikaw ay nakatanggap ng Chooks-to-Go eVoucher for ' . $value . ' '  . $coupon['info']->products . '. Ang iyong voucher code ay ' . $code . ' at ito ay valid lamang ' . $location;
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
 	$config = Array(
		    'protocol' => 'smtp',
		    'smtp_host' => 'ssl://smtp.gmail.com',
		    'smtp_port' => 465,
		    'smtp_user' => 'noreply@chookstogoinc.com.ph',
		    'smtp_pass' => 'jbbb qutt gvii bsyk',
		    'mailtype'  => 'html', 
		    'charset'   => 'iso-8859-1'
		);
        
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
        $data['title']    = 'Chooks-To-Go E-Coupon';
        $data['name']     = $coupon->coupon_holder_name;

        $content  = $this->load->view('email_templates/product_coupon', $data, TRUE);

        $this->load->library('email', $config);
        $this->email->set_newline("\r\n");

        // Set to, from, message, etc.
        $email = $coupon->coupon_holder_email;
        $this->email->from('noreply@chookstogodelivery.com', 'Chooks-to-Go E-Coupon');
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
 	$config = Array(
		    'protocol' => 'smtp',
		    'smtp_host' => 'ssl://smtp.gmail.com',
		    'smtp_port' => 465,
		    'smtp_user' => 'noreply@chookstogoinc.com.ph',
		    'smtp_pass' => 'jbbb qutt gvii bsyk',
		    'mailtype'  => 'html', 
		    'charset'   => 'iso-8859-1'
		);

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
        $data['start']    = date_format(date_create($row->coupon_start),"M d, Y");
        $data['end']      = date_format(date_create($row->coupon_end),"M d, Y");
        $data['bc']       = $coupon->bc;
        $data['products'] = $coupon->products;
        $data['title']    = 'Chooks-To-Go E-Coupon';
        $data['name']     = $coupon->coupon_holder_name;

        $content = $this->load->view('email_templates/standard_coupon', $data, TRUE);

        $this->load->library('email', $config);
        $this->email->set_newline("\r\n");

        // Set to, from, message, etc.
        $email = $coupon->coupon_holder_email;
        $this->email->from('noreply@chookstogodelivery.com', 'Chooks-to-Go E-Coupon');
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
        $coupon_select  = "*,
        IF(a.is_nationwide = 1, 
            'Nationwide', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bc',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'ORC',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";
        $coupon_join    = ['coupon_category_tbl b' => 'b.coupon_cat_id = a.coupon_cat_id AND a.coupon_code = "' . $coupon_code .'" AND a.coupon_status = 1'];
        $check_coupon     = $this->main->check_join('coupon_tbl a', $coupon_join, TRUE, FALSE, FALSE, $coupon_select);
        $date_now       = strtotime(date("Y-m-d"));


        /*if ($check_code['result'] == FALSE || $check_code['info']->coupon_status != 1) {
            $response_data = [
                'result'  => 0,
                'html' => '
                <div class="alert alert-danger" role="alert">
                    Invalid Coupon Code
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
                    Coupon Code have not yet started
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
                    Coupon Code have not yet started
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
                    Coupon Code has already been redeemed
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
                Ang e-Voucher mo ay valid ng 1 ORC at valid NATIONWIDE. Ito ay <strong><i>'. $check_code['info']->coupon_cat_name. '</i></strong>.
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

            $mobile = '';
            if($use < $coupon_qty){
                if($today_date <= $coupon_end){//Check coupon if expired
                    if($today_date >= $coupon_start){//Check coupon if redeemd date is started

                        if($coupon_type == 1){
                            if($value_type == 1){
                                //For percentage Discount

                                //Check is nationwide
                                if($check_coupon['info']->is_nationwide == 1){

                                    $sms = 'Ang e-Voucher mo ay valid worth ' . $amount . '% at valid NATIONWIDE. Ito ay ' . $category . '.';
                                }else{
                                    //Find valid BC
                                    $bc = $this->_get_bc($coupon_id);

                                    $sms = 'Ang e-Voucher mo ay valid worth ' . $amount . '% discount at valid sa ' . $bc .'. Ito ay '. $category . '.';
                                }
                            }elseif($value_type == 2){
                                //Flat amount Discount

                                if($check_coupon['info']->is_nationwide == 1){

                                    $sms = 'Ang e-Voucher mo ay valid worth P' . $amount . ' discount at valid NATIONWIDE. Ito ay '. $category . '.';
                                }else{
                                    //Find valid BC
                                    $bc = $this->_get_bc($coupon_id);

                                    $sms = 'Ang e-Voucher mo ay valid worth P' . $amount . ' discount at valid sa ' . $bc .'. Ito ay '. $category . '.';
                                }
                            }

                            $result = 1;
                        }elseif($coupon_type == 2){
                            if($value_type == 1){
                                //For percentage Discount

                                //Check is nationwide
                                if($check_coupon['info']->is_nationwide == 1){
                                    if($check_coupon['info']->is_orc == 1){
                                        if($check_coupon['info']->coupon_amount == 100){
                                            $sms = 'Ang e-Voucher mo ay valid ng 1 ORC at valid NATIONWIDE. Ito ay '. $category . '.';
                                        }else{
                                            $sms = 'Ang e-Voucher mo ay valid ng worth ' . $amount . '% discount ng ORC at valid NATIONWIDE. Ito ay '. $category . '.';
                                        }
                                    }else{
                                        $prod = $this->_get_prod($coupon_id);
                                        if($check_coupon['info']->coupon_amount == 100){
                                            $sms = 'Ang e-Voucher mo ay valid ng 1 ' . $prod . ' at valid NATIONWIDE. Ito ay '. $category . '.';
                                        }else{

                                            $sms = 'Ang e-Voucher mo ay valid ng worth ' . $amount . '% discount ng ' . $prod . ' at valid NATIONWIDE. Ito ay '. $category . '.';
                                        }
                                    }
                                }else{
                                    //Find valid BC
                                    $bc = $this->_get_bc($coupon_id);

                                    if($check_coupon['info']->is_orc == 1){
                                        if($check_coupon['info']->coupon_amount == 100){
                                            $sms = 'Ang e-Voucher mo ay valid ng 1 ORC at valid sa ' . $bc . '. Ito ay '. $category . '.';
                                        }else{
                                            $sms = 'Ang e-Voucher mo ay valid ng worth ' . $amount . '% discount ng ORC at valid sa ' . $bc . '. Ito ay '. $category . '.';
                                        }
                                    }else{
                                        $prod = $this->_get_prod($coupon_id);
                                        if($check_coupon['info']->coupon_amount == 100){
                                            $sms = 'Ang e-Voucher mo ay valid ng 1 ' . $prod . ' at valid sa ' . $bc . '. Ito ay '. $category . '.';
                                        }else{

                                            $sms = 'Ang e-Voucher mo ay valid ng worth ' . $amount . '% discount ng ' . $prod . ' at valid sa ' . $bc . '. Ito ay '. $category . '.';
                                        }
                                    }
                                }
                            }elseif($value_type == 2){
                                //Flat amount Discount

                                if($check_coupon['info']->is_nationwide == 1){
                                    if($check_coupon['info']->is_orc == 1){
                                        $sms = 'Ang e-Voucher mo ay valid worth P' . $amount . ' discount para sa ORC at valid NATIONWIDE. Ito ay '. $category . '.';
                                    }else{
                                        $prod = $this->_get_prod($coupon_id);
                                        $sms = 'Ang e-Voucher mo ay valid worth P' . $amount . ' discount para sa ' . $prod . ' at valid NATIONWIDE. Ito ay '. $category . '.';
                                    }
                                }else{
                                    //Find valid BC

                                    $bc = $this->_get_bc($coupon_id);

                                    if($check_coupon['info']->is_orc == 1){
                                        $sms = 'Ang e-Voucher mo ay valid worth P' . $amount . ' discount para sa ORC at valid sa ' . $bc . '. Ito ay '. $category . '.';
                                    }else{
                                        $prod = $this->_get_prod($coupon_id);

                                        $sms = 'Ang e-Voucher mo ay valid worth P' . $amount . ' discount para sa ' . $prod . ' at valid sa ' . $bc . '. Ito ay '. $category . '.';      
                                    }
                                }
                            }

                            $result = 1;
                        }else{
                            $result = 0;
                            $sms = 'Error Invalid Coupon Type. Please try again';
                        }                        
                    }else{
                        $result = 0;
                        $sms = 'Sorry e-Voucher redemption has not yet started. Redemption start on ' . $coupon_start . '.';
                    }
                }else{
                    //Invalid coupon is expired
                    $result = 0;
                    $sms = 'Sorry e-Voucher was already expired.';
                }
            }else{
                $result = 0;
                $sms = 'Sorry e-Voucher was already redeemed.';
            }
        }else{
            //Invalid and already redeem
            $result = 0;
            $sms = 'Sorry invalid ang iyong e-Voucher code. Subukan ulit ang pag-redeem.';
        }

        if($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            $sms = 'Coupon Redeem Failed. Please Try Again!';
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

        $join_coupon = array('coupon_category_tbl b' => "a.coupon_cat_id = b.coupon_cat_id AND a.coupon_status = 1 AND a.coupon_code = '" . $coupon_code . "'");
        $check_coupon = $this->main->check_join('coupon_tbl a', $join_coupon, TRUE);
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
                    Invalid Coupon Code
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
                    Coupon Code have not yet started
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
                    Coupon Code is already expired 
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
                    Coupon Code has already been redeemed
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

            $message = 'Ang e-Voucher mo ay valid ng 1 ORC at valid NATIONWIDE. Ito ay '. $check_code['info']->coupon_cat_name. '. Maari mo nang iinput sa POS ang approval code ' . $reference_code;

            $response_data = [
                'result' => 1,
                'html' => '
                <div class="alert alert-success" role="alert">
                    Ang e-Voucher mo ay valid ng 1 ORC at valid NATIONWIDE. Ito ay <strong><i>'. $check_code['info']->coupon_cat_name. '</i></strong>. Maari mo nang iinput sa POS ang approval code <strong><i>' . $reference_code .'</i></strong>
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
                        Coupon Redeem Failed. Please Try Again
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
                    Coupon Redeem Failed. Please Try Again
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

            if($use < $coupon_qty){
                if($today_date <= $coupon_end){//Check coupon if expired
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
                                if($coupon_type == 1){
                                    if($value_type == 1){
                                        //For percentage Discount

                                        //Check is nationwide
                                        if($check_coupon['info']->is_nationwide == 1){

                                            $sms = 'Ang e-Voucher mo ay valid worth ' . $amount . '% at valid NATIONWIDE. Ito ay ' . $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }else{
                                            //Find valid BC
                                            $bc = $this->_get_bc($coupon_id);

                                            $sms = 'Ang e-Voucher mo ay valid worth ' . $amount . '% discount at valid sa ' . $bc .'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }
                                    }elseif($value_type == 2){
                                        //Flat amount Discount

                                        if($check_coupon['info']->is_nationwide == 1){

                                            $sms = 'Ang e-Voucher mo ay valid worth P' . $amount . ' discount at valid NATIONWIDE. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                        }else{
                                            //Find valid BC
                                            $bc = $this->_get_bc($coupon_id);

                                            $sms = 'Ang e-Voucher mo ay valid worth P' . $amount . ' discount at valid sa ' . $bc .'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
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
                                }elseif($coupon_type == 2){
                                    if($value_type == 1){
                                        //For percentage Discount

                                        //Check is nationwide
                                        if($check_coupon['info']->is_nationwide == 1){
                                            if($check_coupon['info']->is_orc == 1){
                                                if($check_coupon['info']->coupon_amount == 100){
                                                    $sms = 'Ang e-Voucher mo ay valid ng 1 ORC at valid NATIONWIDE. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                                }else{
                                                    $sms = 'Ang e-Voucher mo ay valid ng worth ' . $amount . '% discount ng ORC at valid NATIONWIDE. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                                }
                                            }else{
                                                $prod = $this->_get_prod($coupon_id);
                                                if($check_coupon['info']->coupon_amount == 100){
                                                    $sms = 'Ang e-Voucher mo ay valid ng 1 ' . $prod . ' at valid NATIONWIDE. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                                }else{

                                                    $sms = 'Ang e-Voucher mo ay valid ng worth ' . $amount . '% discount ng ' . $prod . ' at valid NATIONWIDE. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                                }
                                            }
                                        }else{
                                            //Find valid BC
                                            $bc = $this->_get_bc($coupon_id);

                                            if($check_coupon['info']->is_orc == 1){
                                                if($check_coupon['info']->coupon_amount == 100){
                                                    $sms = 'Ang e-Voucher mo ay valid ng 1 ORC at valid sa ' . $bc . '. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                                }else{
                                                    $sms = 'Ang e-Voucher mo ay valid ng worth ' . $amount . '% discount ng ORC at valid sa ' . $bc . '. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                                }
                                            }else{
                                                $prod = $this->_get_prod($coupon_id);
                                                if($check_coupon['info']->coupon_amount == 100){
                                                    $sms = 'Ang e-Voucher mo ay valid ng 1 ' . $prod . ' at valid sa ' . $bc . '. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                                }else{

                                                    $sms = 'Ang e-Voucher mo ay valid ng worth ' . $amount . '% discount ng ' . $prod . ' at valid sa ' . $bc . '. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                                }
                                            }
                                        }
                                    }elseif($value_type == 2){
                                        //Flat amount Discount

                                        if($check_coupon['info']->is_nationwide == 1){
                                            if($check_coupon['info']->is_orc == 1){
                                                $sms = 'Ang e-Voucher mo ay valid worth P' . $amount . ' discount para sa ORC at valid NATIONWIDE. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                            }else{
                                                $prod = $this->_get_prod($coupon_id);
                                                $sms = 'Ang e-Voucher mo ay valid worth P' . $amount . ' discount para sa ' . $prod . ' at valid NATIONWIDE. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                            }
                                        }else{
                                            //Find valid BC

                                            $bc = $this->_get_bc($coupon_id);

                                            if($check_coupon['info']->is_orc == 1){
                                                $sms = 'Ang e-Voucher mo ay valid worth P' . $amount . ' discount para sa ORC at valid sa ' . $bc . '. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
                                            }else{
                                                $prod = $this->_get_prod($coupon_id);

                                                $sms = 'Ang e-Voucher mo ay valid worth P' . $amount . ' discount para sa ' . $prod . ' at valid sa ' . $bc . '. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;      
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
                                }else{
                                    $result = 0;
                                    $sms = 'Error Invalid Coupon Type. Please try again';
                                }

                                $outgoing_id = $insert_outgoing['id'];

                                $update_redeem = $this->main->update_data('redeemed_coupon_log_tbl', array('redeemed_coupon_log_response' => $sms), array('redeemed_coupon_log_id' => $redeemed_coupon_log_id));

                                $insert_con = $this->main->insert_data('redeem_coupon_tbl', array('redeemed_coupon_log_id' => $redeemed_coupon_log_id, 'coupon_id' => $coupon_id, 'redeem_outgoing_id' => $outgoing_id, 'redeem_coupon_added' => date_now(), 'redeem_coupon_status' => 1));
                            }else{
                                //Error while updating data
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

                        }else{
                            //Error while inserting data
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
                    }else{
                        $result = 0;
                        $sms = 'Sorry e-Voucher redemption has not yet started. Redemption start on ' . $coupon_start . '.';
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
                }else{
                    //Invalid coupon is expired
                    $result = 0;
                    $sms = 'Sorry e-Voucher was already expired.';

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
            }else{
                $result = 0;
                $sms = 'Sorry e-Voucher was already redeemed.';

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
        }else{
            //Invalid and already redeem
            $result = 0;
            $sms = 'Sorry invalid ang iyong e-Voucher code. Subukan ulit ang pag-redeem.';

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
            $sms = 'Coupon Redeem Failed. Please Try Again!';
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
                $bc .= $row_bc->bc_name;
            }elseif($checker_bc == $count_bc){
                $bc .= ' & ' . $row_bc->bc_name;
            }else{
                $bc .= ', ' . $row_bc->bc_name;
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
                $alert_message = $this->alert_template('Coupon ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template('Coupon ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->coupon_status == 1) {
                $alert_message = $this->alert_template('Coupon is already Approve', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->coupon_cat_id == '3') {
                if ($this->input->post('invoice_num') == NULL || empty($this->input->post('invoice_num'))) {
                    $alert_message = $this->alert_template('Invoice Number is Required', FALSE);
                    $this->session->set_flashdata('message', $alert_message);
                    redirect($_SERVER['HTTP_REFERER']);
                }

                if ($check_id['info']->coupon_holder_type_id != '4') {
                    if ($this->input->post('sap_doc_no') == NULL || empty($this->input->post('sap_doc_no'))) {
                        $alert_message = $this->alert_template('SAP Document Number is Required', FALSE);
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

			$msg    = ($result == TRUE) ? '<div class="alert alert-success">Coupon successfully Activated.</div>' : '<div class="alert alert-danger">Error please try again!</div>';
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

            if ($check_id['info']->coupon_cat_id == '3') {
                $coupon_join         = [ 'coupon_tbl b' => 'b.coupon_id = a.coupon_id AND a.coupon_transaction_header_id = ' . $coupon_transaction_header_id ];
                $transaction_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join, TRUE);

                if ($transaction_details->coupon_holder_type_id != 4) {
                    if ($this->input->post('sap_doc_no') == NULL || empty($this->input->post('sap_doc_no'))) {
                        $alert_message = $this->alert_template('SAP Document Number is Required' . $transaction_details->coupon_holder_type_id, FALSE);
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
                    $this->_upload_transaction_attachment($coupon_transaction_header_id, 2);
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
			redirect('admin');
		}
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
            if ($coupon_cat == '1') { // GIFT VOUCHER
                $result = '
                        <label for="">Attachment : *</label><br>
                        <div class="custom-file mb-3">
                            <input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple required>
                            <label class="custom-file-label" for="attachment[]">Choose file...</label>
                        </div>';
            } elseif ($coupon_cat == '3') { // PAID VOUCHER
                if ($holder_type == '4') {
                    $result = '
                        <div class="form-group">
                            <label for="">Voucher Paid Amount : *</label>
                            <input type="number" name="voucher-value" class="form-control form-control-sm" placeholder="" min="0.01" step="0.01" required>
                        </div> 
                        <div class="form-group">
                            <label for="">Holder Address : *</label>
                            <input type="text" name="address" class="form-control form-control-sm" placeholder="" required>
                        </div> 
                        <div class="form-group">
                            <label for="">Holder TIN : *</label>
                            <input type="text" name="tin" class="form-control form-control-sm" placeholder="" required>
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
                } elseif ($holder_type == '1') {
                    $result = '
                        <div class="form-group">
                            <label for="">Voucher Paid Amount : *</label>
                            <input type="number" name="voucher-value" class="form-control form-control-sm" placeholder="" min="0.01" step="0.01" required>
                        </div> 
                        <div class="form-group">
                            <label for="">Holder Address : *</label>
                            <input type="text" name="address" class="form-control form-control-sm" placeholder="" required>
                        </div> 
                        <div class="form-group">
                            <label for="">Holder TIN : *</label>
                            <input type="text" name="tin" class="form-control form-control-sm" placeholder="" required>
                        </div> 
                        <label for="">Attachment : </label><br>
                        <div class="custom-file mb-3">
                            <input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple>
                            <label class="custom-file-label" for="attachment[]">Choose file...</label>
                        </div>';
                } else {
                    $result = '
                        <div class="form-group">
                            <label for="">Voucher Paid Amount : *</label>
                            <input type="number" name="voucher-value" class="form-control form-control-sm" placeholder="" min="0.01" step="0.01" required>
                        </div> 
                        <div class="form-group">
                            <label for="">Holder Address : *</label>
                            <input type="text" name="address" class="form-control form-control-sm" placeholder="" required>
                        </div> 
                        <div class="form-group">
                            <label for="">Holder TIN : *</label>
                            <input type="text" name="tin" class="form-control form-control-sm" placeholder="" required>
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
            $where = [ 'coupon_transaction_header_id' => $transaction_id , 'coupon_transaction_header_attachment_process_sequence' => $sequence];
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
            if ($check_coupon['info']->coupon_cat_id == '3') {
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
                            <label for="">SAP Doc No:</label>
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
                            <label for="">SAP Doc No:</label>
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
            if ($check_coupon['info']->coupon_cat_id == '3') {
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
                            <label for="">SAP Doc No:</label>
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
                            <label for="">SAP Doc No:</label>
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
        $subject = 'eVoucher Approved Notification';

        $data['title'] = 'eVoucher Request Approved';
        $data['name'] =  $employee_name;
        $data['voucher']  = $voucher_name;
        $data['type'] = $voucher_type;

        $message = $this->load->view('email/email_creator_content', $data, TRUE);
        return $this->_send_email($recipient, $subject, $message);
    }

    private function _send_pending_notif($employee_name, $recipient, $voucher_name, $voucher_type)
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
        $subject = 'eVoucher New Request Notification';

        $data['title'] = 'eVoucher New Request';
        $data['name'] =  $employee_name;
        $data['voucher']  = $voucher_name;
        $data['type'] = $voucher_type;

        $message = $this->load->view('email/email_approver_content', $data, TRUE);
        return $this->_send_email($recipient, $subject, $message);
    }

    public function view_approver_email(){
        
        $url     = base_url();
        $subject = 'eVoucher New Request Notification';

        $data['title'] = 'eVoucher New Request';
        $data['name'] =  'Jonel';
        $data['voucher']  = '3x3 eVoucher';
        $data['type'] = 'Gift eVoucher';

        $message = $this->load->view('email/email_approver_content', $data);
    }

    public function view_creator_email(){
        
        $url     = base_url();
        $subject = 'eVoucher Approved Notification';

        $data['title'] = 'eVoucher Request Approved';
        $data['name'] =  'Jonel';
        $data['voucher']  = '3x3 eVoucher';
        $data['type'] = 'Gift eVoucher';

        $message = $this->load->view('email/email_creator_content', $data);
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

        $config = [ 
            'protocol'  => 'smtp',
            'smtp_host' => 'ssl://server10.synermaxx.net',
            'smtp_port' => 465,
            'smtp_user' => 'alerts@bountyagro.com.ph',
            'smtp_pass' => '',
            'mailtype'  => 'html',
            'charset'   => 'utf-8',
            'wordwrap'  => TRUE
        ];
        $this->load->library('email', $config);
        $this->email->set_newline("\r\n")
                    ->from('alerts@bountyagro.com.ph', 'eVoucher System Notification')
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

            print_r($email_result);
            exit;
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
        $this->_validate_result($check_code, 'Coupon Code Already Used');
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

    private function _validate_attachment()
    {
        if (empty($_FILES['attachment']['name']) || !isset($_FILES['attachment'])) {
            $alert_message = $this->alert_template('Attachment Is Required', FALSE);
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER']);
        }
    }

    public function modal_transaction_coupon_attachment($id)
    {
        $info      = $this->_require_login();
        $id        = clean_data(decode($id));
        $check_id  = $this->main->check_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $id], TRUE);
        if ($check_id['result']) {
            $where               = ['coupon_transaction_header_attachment_status' => 1, 'coupon_transaction_header_id' => $id];
            $attachments         = $this->main->get_data('coupon_transaction_header_attachment_tbl', $where);
            $pending_count       = 1;
            $approved_count      = 1;
            $paid_count          = 1;
            $pending_attachment  = '<tr><td colspan="2"><b>Pending Attachment</b></td></tr>';
            $approved_attachment = '<tr><td colspan="2"><b>Approved Attachment</b></td></tr>';
            $paid_attachment     = '<tr><td colspan="2"><b>Payment Attachment</b></td></tr>';
            foreach ($attachments as $row) {
                $link = base_url($row->coupon_transaction_header_attachment_path);
                $name = $row->coupon_transaction_header_attachment_name;
                if ($row->coupon_transaction_header_attachment_process_sequence == 1) {
                    $pending_attachment .= '<tr><td>'.$pending_count.'</td><td><a href="'.$link.'" target="_blank">'.$name.'</a></td></tr>';
                    $pending_count++;
                } else if ($row->coupon_transaction_header_attachment_process_sequence == 2) {
                    $approved_attachment .= '<tr><td>'.$approved_count.'</td><td><a href="'.$link.'" target="_blank">'.$name.'</a></td></tr>';
                    $approved_count++;
                } else if ($row->coupon_transaction_header_attachment_process_sequence == 3) {
                    $paid_attachment .= '<tr><td>'.$paid_count.'</td><td><a href="'.$link.'" target="_blank">'.$name.'</a></td></tr>';
                    $paid_count++;
                }
            }

            $items = $pending_attachment;

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
            $pending_attachment = '<tr><td colspan="2"><b>Pending Attachment</b></td></tr>';
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

    private function _send_approver_notification($name, $category, $voucher_type) 
    {
        $parent_db = $GLOBALS['parent_db'];
        $where     = "a.user_type_id IN (8) AND a.user_status = 1 AND a.user_id IN (SELECT user_id FROM user_access_tbl z WHERE z.coupon_cat_id = {$category} AND user_access_status = 1)";
        $approver  = $this->main->get_data("{$parent_db}.user_tbl a", $where);
        foreach ($approver as $row) {
            if ($row->user_email != '') {
                $employee_name = ucwords(strtolower(("{$row->user_fname}")));
                $recipient     = $row->user_email;
                $voucher_name  = $name;
                $this->_send_pending_notif($employee_name, $recipient, $voucher_name, $voucher_type);
            }
        }
    }

    private function _send_transaction_creator_notification($transaction_id) 
    {
        $parent_db   = $GLOBALS['parent_db'];
        $transaction = $this->main->get_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $transaction_id], TRUE);
        $creator     = $this->main->get_data("{$parent_db}.user_tbl", ['user_id' => $transaction->user_id], TRUE);
        if ($creator->user_email != '') {
            $employee_name = ucwords(strtolower(("{$creator->user_fname}")));
            $recipient     = $creator->user_email;
            $voucher_name  = $transaction->coupon_transaction_header_name;

            $category = $transaction->coupon_cat_id;
            $check_category = $this->main->check_data('coupon_category_tbl', array('coupon_cat_id' => $category, 'coupon_cat_status' => 1), TRUE);

            $category_name = '';
            if($check_category['result'] == TRUE){
                $category_name = $check_category['info']->coupon_cat_name;
            }
            $this->_send_approved_notif($employee_name, $recipient, $voucher_name, $category_name);
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
            $this->_send_approved_notif($employee_name, $recipient, $voucher_name, 'Standard Voucher');
        }
    }

    public function voucher_summary() 
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

		$end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime($end_date . ' - 5 months'));
		
        $data['range_date'] = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));

		$join_salable = [
			"{$parent_db}.product_tbl b" => 'a.prod_id = b.prod_id',
			"{$parent_db}.unit_tbl c"    => 'a.unit_id = c.unit_id',
			"{$parent_db}.unit_tbl d"    => 'a.2nd_uom = d.unit_id'
        ];
		
		$filter = [
			'start_date'								=> $start_date,
			'end_date'									=> $end_date,
			
		];
		
		$filter['unused_flag']				= 0;
        $filter['date_filter_type']			= 'creation_date';
		$used								= $this->_used_voucher_data($filter);
        $data['tbl_used']					= $this->_get_used_voucher_tbl($used);
		$data['used_coupon_trans']			= $this->_coupon_data($filter);

		$filter['unused_flag']				= 1;
		$filter['date_filter_type']			= 'creation_date';
		$unused								= $this->_unused_voucher_data($filter);
        $data['tbl_unused']					= $this->_get_unused_voucher_tbl($unused);
        $data['unused_coupon_trans']		= $this->_coupon_data($filter);
        

        $data['title']						= 'Standard Coupon';
        $data['content']					= $this->load->view('admin/coupon/coupon_summary_content', $data, TRUE);
        $this->load->view('admin/templates', $data);
    }

	public function _coupon_data($filter){


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
			$result = '<option value="-1">Select All</option>';
			foreach ($coupon_trans as $row) {
				
				$result .= '<option value="'.encode($row->coupon_transaction_header_id).'">'.$row->coupon_transaction_header_id.' - '.$row->coupon_transaction_header_name.'</option>';
			}
		} else {
			$result = '<option>No record found</option>';
		}

		return $result;
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
			$date_type	= decode($date_type) == 1 ? 'creation_date' : 'redemption_date';


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

	public function _used_voucher_data($filter){
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
        ];
		
		$coupon_select = "
		d.coupon_type_name,
		e.coupon_cat_name,
		a.coupon_name,
		a.coupon_code,
		a.coupon_amount,
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
		g.redeemed_coupon_log_added,
		g.redeemed_coupon_log_reference_code,
		g.redeemed_coupon_log_contact_number,

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
		$where = $coupon_use." and DATE($date_field) >=  '$start_date' AND DATE($date_field) <= '$end_date'";
		if(!empty($filter['coupon_transaction_header_ids'])){
			
			$coupon_transaction_header_ids = $filter['coupon_transaction_header_ids'];
			$where .= " AND h.coupon_transaction_header_id IN ($coupon_transaction_header_ids)";
		}

		$used   = $this->main->get_join('coupon_tbl a', $coupon_join, FALSE, $order_by, FALSE ,$coupon_select, $where);

		return $used;
	}
	
	public function _unused_voucher_data($filter){
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
        ];

        $coupon_select = "
		d.coupon_type_name,
		e.coupon_cat_name,
		a.coupon_name,
		a.coupon_code,
		a.coupon_amount,
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
		$where = $coupon_use." and DATE($date_field) >=  '$start_date' AND DATE($date_field) <= '$end_date'";
		if(!empty($filter['coupon_transaction_header_ids'])){
			
			$coupon_transaction_header_ids = $filter['coupon_transaction_header_ids'];
			$where .= " AND h.coupon_transaction_header_id IN ($coupon_transaction_header_ids)";
		}

		$unused  = $this->main->get_join('coupon_tbl a', $coupon_join, FALSE, $order_by, FALSE ,$coupon_select , $where);

		return $unused;
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
			$date_type										= decode($date_type) == 1 ? 'creation_date' : 'redemption_date';
			$coupon_transaction_header_id					= clean_data($this->input->post('coupon_transaction_header_id'));
			$coupon_trans_id_arrays							= [];
			if(!empty($coupon_transaction_header_id)){
				foreach($coupon_transaction_header_id as $row){
					$coupon_trans_id_arrays[] = decode($row);
				}
			}
			$coupon_transaction_header_ids					= join(',',$coupon_trans_id_arrays);
			
			$filter = [
				'start_date'								=> $start_date,
				'end_date'									=> $end_date,
				'date_filter_type'							=> $date_type,
				'unused_flag'								=> $unused,
				'coupon_transaction_header_ids'				=> $coupon_transaction_header_ids
			];

			$used = $this->_used_voucher_data($filter);

            $data['tbl_used'] = $this->_get_used_voucher_tbl($used);
			$data['coupon_transaction_header_ids'] = $coupon_transaction_header_ids;
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
            $start_date 									= date('y-m-d', strtotime($daterange[0]));
            $end_date   									= date('y-m-d', strtotime($daterange[1]));
			$unused											= 1;
			$date_type										= clean_data($this->input->post('date_type'));
			$date_type										= decode($date_type) == 1 ? 'creation_date' : 'redemption_date';
			$coupon_transaction_header_id					= clean_data($this->input->post('coupon_transaction_header_id'));
			$coupon_trans_id_arrays							= [];
			if(!empty($coupon_transaction_header_id)){
				foreach($coupon_transaction_header_id as $row){
					$coupon_trans_id_arrays[] = decode($row);
				}
			}
			$coupon_transaction_header_ids					= join(',',$coupon_trans_id_arrays);
			
			$filter = [
				'start_date'								=> $start_date,
				'end_date'									=> $end_date,
				'date_filter_type'							=> $date_type,
				'unused_flag'								=> $unused,
				'coupon_transaction_header_ids'				=> $coupon_transaction_header_ids
			];
			
			$unused = $this->_unused_voucher_data($filter);
			
            $data['tbl_unused'] = $this->_get_unused_voucher_tbl($unused);
            $data['coupon_transaction_header_ids'] = $coupon_transaction_header_ids;
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
			$date_type										= decode($date_type) == 1 ? 'creation_date' : 'redemption_date';
			$coupon_transaction_header_ids					= clean_data($this->input->get('coupon_transaction_header_id'));
			
			
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

			$sheet1->getStyle("A1:T1")->applyFromArray($style_bold);

			$sheet1->setCellValue("A1", 'Business Center')
				->setCellValue("B1", 'Brand')
				->setCellValue("C1", 'Type')
				->setCellValue("D1", 'Category')
				->setCellValue("E1", 'Name')
				->setCellValue("F1", 'Code')
				->setCellValue("G1", 'Voucher Discount Amount')
				->setCellValue("H1", 'Voucher Paid Amount')
				->setCellValue("I1", 'Qty')
				->setCellValue("J1", 'Usage')
				->setCellValue("K1", 'Value Type')
				->setCellValue("L1", 'Start')
				->setCellValue("M1", 'End')
				->setCellValue("N1", 'Holder Type')
				->setCellValue("O1", 'Holder Name')
				->setCellValue("P1", 'Holder Email')
				->setCellValue("Q1", 'Holder Contact')
				->setCellValue("R1", 'Holder Address')
				->setCellValue("S1", 'Holder TIN')
				->setCellValue("T1", 'Added')
			;

			$x = 2;
			foreach($unused as $row){
				$sheet1
					->setCellValue('A' . $x, $row->bc )
					->setCellValue('B' . $x, $row->brands )
					->setCellValue('C' . $x, $row->coupon_type_name )
					->setCellValue('D' . $x, $row->coupon_cat_name )
					->setCellValue('E' . $x, $row->coupon_name )
					->setCellValue('F' . $x, $row->coupon_code )
					->setCellValue('G' . $x, $row->coupon_amount )
					->setCellValue('H' . $x, $row->coupon_value )
					->setCellValue('I' . $x, $row->coupon_qty )
					->setCellValue('J' . $x, $row->coupon_use )
					->setCellValue('K' . $x, $row->coupon_value_type_name )
					->setCellValue('L' . $x, date_format(date_create($row->coupon_start),"M d, Y") )
					->setCellValue('M' . $x, date_format(date_create($row->coupon_end),"M d, Y") )
					->setCellValue('N' . $x, $row->coupon_holder_type_name )
					->setCellValue('O' . $x, $row->coupon_holder_name )
					->setCellValue('P' . $x, $row->coupon_holder_email )
					->setCellValue('Q' . $x, $row->coupon_holder_contact )
					->setCellValue('R' . $x, $row->coupon_holder_address )
					->setCellValue('S' . $x, $row->coupon_holder_tin )
					->setCellValue('T' . $x, date_format(date_create($row->coupon_added),"M d, Y h:i:s A") )
				;

				$x++;
			}

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
			$date_type										= decode($date_type) == 1 ? 'creation_date' : 'redemption_date';
			$coupon_transaction_header_ids					= clean_data($this->input->get('coupon_transaction_header_id'));
			
			
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

			$sheet1->getStyle("A1:W1")->applyFromArray($style_bold);

			$sheet1
				->setCellValue("A1", 'Business Center')
				->setCellValue("B1", 'Brand')
				->setCellValue("C1", 'Type')
				->setCellValue("D1", 'Category')
				->setCellValue("E1", 'Name')
				->setCellValue("F1", 'Code')
				->setCellValue("G1", 'Voucher Discount Amount')
				->setCellValue("H1", 'Voucher Paid Amount')
				->setCellValue("I1", 'Qty')
				->setCellValue("J1", 'Usage')
				->setCellValue("K1", 'Value Type')
				->setCellValue("L1", 'Start')
				->setCellValue("M1", 'End')
				->setCellValue("N1", 'Holder Type')
				->setCellValue("O1", 'Holder Name')
				->setCellValue("P1", 'Holder Email')
				->setCellValue("Q1", 'Holder Contact')
				->setCellValue("R1", 'Holder Address')
				->setCellValue("S1", 'Holder TIN')
				->setCellValue("T1", 'Added')
				->setCellValue("U1", 'Redeemed Contact #')
				->setCellValue("V1", 'Redeemed Ref Code')
				->setCellValue("W1", 'Redeemed TS')
			;

			$x = 2;
			foreach($used as $row){
				$sheet1
					->setCellValue('A' . $x, $row->bc )
					->setCellValue('B' . $x, $row->brands )
					->setCellValue('C' . $x, $row->coupon_type_name )
					->setCellValue('D' . $x, $row->coupon_cat_name )
					->setCellValue('E' . $x, $row->coupon_name )
					->setCellValue('F' . $x, $row->coupon_code )
					->setCellValue('G' . $x, $row->coupon_amount )
					->setCellValue('H' . $x, $row->coupon_value )
					->setCellValue('I' . $x, $row->coupon_qty )
					->setCellValue('J' . $x, $row->coupon_use )
					->setCellValue('K' . $x, $row->coupon_value_type_name )
					->setCellValue('L' . $x, date_format(date_create($row->coupon_start),"M d, Y") )
					->setCellValue('M' . $x, date_format(date_create($row->coupon_end),"M d, Y") )
					->setCellValue('N' . $x, $row->coupon_holder_type_name )
					->setCellValue('O' . $x, $row->coupon_holder_name )
					->setCellValue('P' . $x, $row->coupon_holder_email )
					->setCellValue('Q' . $x, $row->coupon_holder_contact )
					->setCellValue('R' . $x, $row->coupon_holder_address )
					->setCellValue('S' . $x, $row->coupon_holder_tin )
					->setCellValue('T' . $x, date_format(date_create($row->coupon_added),"M d, Y h:i:s A") )
					->setCellValue('U' . $x, $row->redeemed_coupon_log_contact_number )
					->setCellValue('V' . $x, $row->redeemed_coupon_log_reference_code )
					->setCellValue('W' . $x, date_format(date_create($row->redeemed_coupon_log_added),"M d, Y h:i:s A") )
				;

				$x++;
			}

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
                    <td>' . $row->coupon_value . '</td>
                    <td>' . $row->coupon_qty . '</td>
                    <td>' . $row->coupon_use . '</td>
                    <td>' . $row->coupon_value_type_name . '</td>
                    <td>' . date_format(date_create($row->coupon_start),"M d, Y") . '</td>
                    <td>' . date_format(date_create($row->coupon_end),"M d, Y") . '</td>
                    <td>' . $row->coupon_holder_type_name . '</td>
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
                    <td>' . $row->coupon_value . '</td>
                    <td>' . $row->coupon_qty . '</td>
                    <td>' . $row->coupon_use . '</td>
                    <td>' . $row->coupon_value_type_name . '</td>
                    <td>' . date_format(date_create($row->coupon_start),"M d, Y") . '</td>
                    <td>' . date_format(date_create($row->coupon_end),"M d, Y") . '</td>
                    <td>' . $row->coupon_holder_type_name . '</td>
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
                $alert_message = $this->alert_template('Coupon ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template('Coupon ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->coupon_status == 3) {
                $alert_message = $this->alert_template('Coupon is already Canceled', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $set    = ['coupon_status' => 3];
            $where  = ['coupon_id' => $coupon_id];
            $result = $this->main->update_data('coupon_tbl', $set, $where);
            $msg    = ($result == TRUE) ? '<div class="alert alert-success">Coupon successfully Canceled.</div>' : '<div class="alert alert-danger">Error please try again!</div>';
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
                    $set    = ['coupon_status' => 3];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);
                    if ($result) {
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
        $check_cat  = $this->main->check_data('coupon_category_tbl', ['coupon_cat_id' => $coupon_cat]);
        $result     = '<option>Select Category First</option>';
        if($check_cat) {
            $holder_types = $this->main->get_data('coupon_holder_type_tbl', ['coupon_holder_type_status' => 1]);
            $result = '<option>Select Holder Type</option>';
            foreach ($holder_types as $row) {
                if ($coupon_cat != 3 && $row->coupon_holder_type_id == 4) {
                    continue;
                }
                $result .= '<option value="'.encode($row->coupon_holder_type_id).'">'.$row->coupon_holder_type_name.'</option>';
            }
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
                $alert_message = $this->alert_template('SAP Document Number is Required', FALSE);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $this->_validate_attachment();

            $sap_doc_no = ($this->input->post('sap_doc_no') != NULL) ? clean_data($this->input->post('sap_doc_no')) : '';

            $this->db->trans_start();

			$set       = [
                'payment_status' => 1,
                'sap_doc_no'     => $sap_doc_no,
            ];
			$where  = ['coupon_transaction_header_id' => $coupon_transaction_header_id];
			$result = $this->main->update_data('coupon_transaction_header_tbl', $set, $where);
            $this->_store_transaction_action_log(3, $coupon_transaction_header_id);

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
                $alert_message = $this->alert_template('Coupon ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('coupon_tbl', ['coupon_id' => $coupon_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template('Coupon ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->payment_status == 1) {
                $alert_message = $this->alert_template('Coupon is already Paid', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($this->input->post('sap_doc_no') == NULL || empty($this->input->post('sap_doc_no'))) {
                $alert_message = $this->alert_template('SAP Document Number is Required', FALSE);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $this->_validate_attachment();
            $sap_doc_no = ($this->input->post('sap_doc_no') != NULL) ? clean_data($this->input->post('sap_doc_no')) : '';

            $set        = [
                'payment_status' => 1,
                'sap_doc_no'     => $sap_doc_no,
            ];

			$where  = ['coupon_id' => $coupon_id];
            $this->db->trans_start();
			$result = $this->main->update_data('coupon_tbl', $set, $where);
            if ($result) {
                if (isset($_FILES['attachment']) && $_FILES['attachment']['name'][0] != '') {
                    $this->_upload_coupon_attachment($result['id'], 3);
                }
            }
            if($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                $message       = 'Error please try again!';
                $alert_message = $this->alert_template($message, FALSE);
            }else{
                $this->db->trans_commit();
                $message       = 'Coupon successfully Paid.';
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
                        <label for="">SAP Doc No:</label>
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
                        <label for="">SAP Doc No:</label>
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

    public function stores()
    {
        $info        = $this->_require_login();
        $parent_db   = $GLOBALS['parent_db'];
        $join = [
            "{$parent_db}.town_groups_tbl b" => 'b.town_group_id = a.town_group_id AND a.store_status = 1',
            "{$parent_db}.brand_tbl c"       => 'c.brand_id = a.brand_id',
            "{$parent_db}.provinces_tbl d"   => 'd.province_id = b.province_id',
            "{$parent_db}.bc_tbl e"          => 'e.bc_id = b.bc_id',
        ];

        $select = '*, (SELECT GROUP_CONCAT(z.store_contact_number SEPARATOR ", ") FROM store_contact_tbl z WHERE z.store_id = a.store_id) as `contacts`';
        $data['title']   = 'Store';
        $data['stores']  = $this->main->get_join("{$parent_db}.store_tbl a", $join, FALSE, FALSE, FALSE, $select);
        $data['content'] = $this->load->view('admin/store/store_content', $data, TRUE);
        $this->load->view('admin/templates', $data);
    }

    public function add_store_contact($store_id)
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];
        $where     = ['store_id' => decode($store_id)];

        $data['title']          = 'Add Store Contact Number';
        $data['store']          = $this->main->get_data("{$parent_db}.store_tbl a", $where, TRUE);
        $data['store_contacts'] = $this->main->get_data('store_contact_tbl a', $where);
        $data['content']        = $this->load->view('admin/store/add_store_contact_content', $data, TRUE);
        $this->load->view('admin/templates', $data);
    }

    function check_contact_number($field_value)
    {
        $parent_db = $GLOBALS['parent_db'];
        $this->form_validation->set_message('check_contact_number', '%s prefix is invalid');
        return $this->main->check_data("{$parent_db}.contact_number_prefix_tbl", [ 'contact_number_prefix' => substr(trim(clean_data($field_value)), 0, 4) ]);
    }

    function check_store_id($field_value)
    {
        $parent_db = $GLOBALS['parent_db'];
        $this->form_validation->set_message('check_store_id', '%s Store ID Doesn\'t Exist');
        return $this->main->check_data("{$parent_db}.store_tbl", [ 'store_id' => decode($field_value) ]);
    }

    public function store_contact_number()
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            redirect($_SERVER['HTTP_REFERER']);
        }

        $rules = [
            [ 'field' => 'contact_number', 'label' => 'Contact Number', 'rules' => 'required|integer|exact_length[11]|callback_check_contact_number' ],
            [ 'field' => 'store_id'      , 'label' => 'Store ID'      , 'rules' => 'required'                                                        ],
        ];

        $this->_run_form_validation($rules);

        $store_id       = clean_data(decode($this->input->post('store_id')));
        $contact_number = clean_data($this->input->post('contact_number'));

        $data = [
            'store_id'             => $store_id,
            'store_contact_number' => $contact_number,
            'store_contact_status' => 1
        ];
        
        $this->db->trans_start();
        $result = $this->main->insert_data('store_contact_tbl', $data, TRUE);
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
        redirect($_SERVER['HTTP_REFERER']);
    }

	public function deactivate_store_contact()
    {
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $store_contact_id = clean_data(decode($this->input->post('id')));
            if (empty($store_contact_id)) {
                $alert_message = $this->alert_template('Store Contact ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('store_contact_tbl', ['store_contact_id' => $store_contact_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template('Store ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->store_contact_status == 0) {
                $alert_message = $this->alert_template('Store Contact is already Deactivated', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

			$set    = ['store_contact_status' => 0];
			$where  = ['store_contact_id' => $store_contact_id];
			$result = $this->main->update_data('store_contact_tbl', $set, $where);
			$msg    = ($result == TRUE) ? '<div class="alert alert-success">Contact successfully deactivated.</div>' : '<div class="alert alert-danger">Error please try again!</div>';
            $this->session->set_flashdata('message', $msg);
            redirect($_SERVER['HTTP_REFERER']);
		}else{
			redirect('admin');
		}
	}

	public function activate_store_contact()
    {
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $store_contact_id = clean_data(decode($this->input->post('id')));
            if (empty($store_contact_id)) {
                $alert_message = $this->alert_template('Store Contact ID is Required', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $check_id = $this->main->check_data('store_contact_tbl', ['store_contact_id' => $store_contact_id], TRUE);
            if (!$check_id['result']) {
                $alert_message = $this->alert_template('Store ID Doesn\'t Exist', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            if ($check_id['info']->store_contact_status == 1) {
                $alert_message = $this->alert_template('Store Contact is already Deactivated', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

			$set    = ['store_contact_status' => 1];
			$where  = ['store_contact_id' => $store_contact_id];
			$result = $this->main->update_data('store_contact_tbl', $set, $where);
			$msg    = ($result == TRUE) ? '<div class="alert alert-success">Contact successfully deactivated.</div>' : '<div class="alert alert-danger">Error please try again!</div>';
            $this->session->set_flashdata('message', $msg);
            redirect($_SERVER['HTTP_REFERER']);
		}else{
			redirect('admin');
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
            ->setCellValue('G1','SAP Document Number')
            ->setCellValue('H1','Voucher Discount Amount')
            ->setCellValue('I1','Voucher Paid Amount')
            ->setCellValue('J1','Qty')
            ->setCellValue('K1','Usage')
            ->setCellValue('L1','Value Type')
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
                ->setCellValue('I'.$count, $row->coupon_value)
                ->setCellValue('J'.$count, $row->coupon_qty)
                ->setCellValue('K'.$count, $row->coupon_use)
                ->setCellValue('L'.$count, $row->coupon_value_type_name)
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

        $data['content'] = $this->load->view('admin/coupon/redeem_coupon_logs_content', $data, TRUE);
        $this->load->view('admin/templates', $data);
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

            $get_redeem = $this->main->get_join('redeemed_coupon_log_tbl a', $join, FALSE, 'a.redeemed_coupon_log_added DESC', FALSE, FALSE, $where);

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
                    <td>' . $row->redeemed_coupon_log_response . '</td>
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

				$sheet1->setCellValue("A1", 'eVoucher Code')
					->setCellValue("B1", 'Approval Code')
					->setCellValue("C1", 'Mobile No.')
					->setCellValue("D1", 'Response')
					->setCellValue("E1", 'Redemption Type')
					->setCellValue("F1", 'Timestamp')
					->setCellValue("G1", 'Status')
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
						->setCellValue('D' . $x, $row->redeemed_coupon_log_response)
						->setCellValue('E' . $x, $row->redeem_type_name)
						->setCellValue('F' . $x, date('Y-m-d h:i:s A', strtotime($row->redeemed_coupon_log_added)))
						->setCellValue('G' . $x, $row->redeemed_coupon_status_name)
					;

					$x++;
				}

				$sheet1->getStyle('O1:W' . $x)->getNumberFormat()->setFormatCode('#,##0.00');

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
