<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {


	public function __construct()
    {
    	parent::__construct();
    	$this->load->model('main_model', 'main');
        $GLOBALS['parent_db'] = parent_db();
		$this->controller = strtolower(str_replace('_', '-', __CLASS__));
	}

	public function index()
    {
		$info = $this->_require_login();
		redirect('dashboard');
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
				}elseif($user_type == "9" || $user_type == "2" || $user_type == "11"){
                    redirect('redeem');
				}elseif($user_type == "12"){
                    redirect('first-approver');
				}elseif($user_type == "13"){
                    redirect('raffle');
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

        $where  = 'a.user_type_id IN (7,8,9,11,12,13)';
        $join   = [ "{$parent_db}.user_type_tbl b" => 'b.user_type_id = a.user_type_id' ];
        $select = "a.*, b.user_type_name, (SELECT GROUP_CONCAT(x.coupon_cat_name SEPARATOR ', ') FROM user_access_tbl z JOIN coupon_category_tbl x ON x.coupon_cat_id = z.coupon_cat_id WHERE z.user_id = a.user_id AND user_access_status = 1) AS 'access'";

        $data['title']           = 'Employee';
        $data['bc']              = $this->main->get_data("{$parent_db}.bc_tbl", [ 'bc_status' => 1 ]);
        $data['user_types']      = $this->main->get_data("{$parent_db}.user_type_tbl", 'user_type_status = 1 AND user_type_id IN (7,8,9,11,12,13)');
        $data['coupon_category'] = $this->main->get_data('coupon_category_tbl', ['coupon_cat_status' => 1]);
        $data['users']           = $this->main->get_join("{$parent_db}.user_tbl a", $join, FALSE, FALSE, FALSE, $select, $where);
		$data['top_nav']     	 = $this->load->view('fix/top_nav_content', $data, TRUE);
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
			if (!empty($email)) {
                // $employee_name = $fname . ' ' . $lname;
                $employee_name = $fname;
                $recipient     = $email;
                $this->_new_user_notif($employee_name ,$password ,$recipient);
            }
            $message       = 'Data Saved Successfully';
            $alert_message = $this->alert_template($message, TRUE);
        }
        $this->session->set_flashdata('message', $alert_message);
        redirect($_SERVER['HTTP_REFERER']);
    }


	public function test_user_notif(){
		$employee_name = "ALJUNE";
		$temp_password = '1234';
		$recipient = 'akatok@chookstogoinc.com.ph';

		echo $this->_reset_user_notif($employee_name, $temp_password, $recipient);
	}

	private function _new_user_notif($employee_name ,$temp_password ,$recipient)
    {
		$parent_db = $GLOBALS['parent_db'];

		$caution_msg = '<strong><center><font style="font-size:10px">-- This is a system generated email. Please do not reply. --</font></center></strong>';

        if(empty($employee_name) || empty($temp_password) || empty($recipient)) {
            $email_result = [
                'result'  => FALSE,
                'Message' => 'Parameter Empty'
            ];
            $this->_store_email_log($email_result, $recipient);
            return $email_result;
        }

        $url            = base_url();
        

        $subject = SYS_NAME.' User Enrollment Notification';
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
                <title>'.SYS_NAME.' Welcome Notification</title>
            </head>
            <body>
                <section>
                    <div class="header" stlye="font-size 14px;">
                    <p>
                        <h4 style="display:block;background-color:#151247;color:white;text-align:center;padding: 30px 0px;">'.SYS_NAME.' System Notification</h4>
                    </p>
                    </div>
                    <div class="content" style="padding-left:50px;">
                    <br>
                        <p class="salutation">
                            <p>Hello '.$employee_name.',</p>
                        </p>
                        <p class="body">
                            <p>Welcome to <strong>'.SYS_NAME.' System</strong>.<br>
							You are enrolled as a user and below is your credentials.<br>
							<br>Email: <strong>'.$recipient.'</strong>
							<br>Temporary password: <strong>'.$temp_password.'</strong></p>
                            <p>You may click <a target="_blank" href="'.$url.'"><u> here </u></a>to go directly to <strong>'.SYS_NAME.' System</strong>.</p>
                        </p>
                        <p class="complimentary-close">
                        <p>Thank you.</p>
                        </p>
                    <br>
						<div style="padding-bottom:00px;width:100%;padding-right:0;padding-left:0;">
							'.$caution_msg.'
						</div>
                    </div>
                    <div class="footer">
                        <h4 style="display:block;background-color:#151247;color:white;text-align:center;padding: 30px 0px;">&copy;&nbsp;Chookstogo, Inc.</h4>
                    </div>
                </section>
            </body>
        </html>';
		// return $message;
        // return $this->_send_email($recipient, $subject, $message);
		$this->_send_email($recipient, $subject, $message);
    }
	
	private function _reset_user_notif($employee_name ,$temp_password ,$recipient)
    {
		$parent_db = $GLOBALS['parent_db'];

		$caution_msg = '<strong><center><font style="font-size:10px">-- This is a system generated email. Please do not reply. --</font></center></strong>';

        if(empty($employee_name) || empty($temp_password) || empty($recipient)) {
            $email_result = [
                'result'  => FALSE,
                'Message' => 'Parameter Empty'
            ];
            $this->_store_email_log($email_result, $recipient);
            return $email_result;
        }

        $url            = base_url();
        

        $subject = SYS_NAME.' User Reset Notification';
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
                <title>'.SYS_NAME.' Notification</title>
            </head>
            <body>
                <section>
                    <div class="header" stlye="font-size 14px;">
                    <p>
                        <h4 style="display:block;background-color:#151247;color:white;text-align:center;padding: 30px 0px;">'.SYS_NAME.' System Notification</h4>
                    </p>
                    </div>
                    <div class="content" style="padding-left:50px;">
                    <br>
                        <p class="salutation">
                            <p>Hello '.$employee_name.',</p>
                        </p>
                        <p class="body">
                            
							A password reset was made in your account and below is your credentials.<br>
							<br>Email: <strong>'.$recipient.'</strong>
							<br>Temporary password: <strong>'.$temp_password.'</strong></p>
                            <p>You may click <a target="_blank" href="'.$url.'"><u> here </u></a>to go directly to <strong>'.SYS_NAME.' System</strong>.</p>
                        </p>
                        <p class="complimentary-close">
                        <p>Thank you.</p>
                        </p>
                    <br>
						<div style="padding-bottom:00px;width:100%;padding-right:0;padding-left:0;">
							'.$caution_msg.'
						</div>
                    </div>
                    <div class="footer">
                        <h4 style="display:block;background-color:#151247;color:white;text-align:center;padding: 30px 0px;">&copy;&nbsp;Chookstogo, Inc.</h4>
                    </div>
                </section>
            </body>
        </html>';
		// return $message;
        // return $this->_send_email($recipient, $subject, $message);
		$this->_send_email($recipient, $subject, $message);
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
            $data['user_types']      = $this->main->get_data("{$parent_db}.user_type_tbl", 'user_type_status = 1 AND user_type_id IN (7,8,9,11,12,13)');
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

        $check_id = $this->main->check_data("{$parent_db}.user_tbl", ['user_id' => $user_id], TRUE);
        if (!$check_id['result']) {
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
			$email = $check_id['info']->user_email;
			if (!empty($email)) {
                $employee_name = $check_id['info']->user_fname;
                $recipient     = $email;
                $this->_reset_user_notif($employee_name ,$password ,$recipient);
            }
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

	public function employee_list(){
		$info      = $this->_require_login();

        $where  = FALSE;
        $join   = FALSE;
        $select = FALSE;

        $data['title']           = 'Employee List';
        
        // $data['employees']       = $this->main->get_join('employee_tbl a', $join, FALSE, FALSE, FALSE, $select, $where);
        $data['employees']       = $this->main->get_data('employee_tbl a');

		$data['top_nav']     	 = $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content']         = $this->load->view('admin/employee/employee_list_content', $data, TRUE);
        $this->load->view('admin/templates', $data);
	}


	public function upload_employee_list(){
		$info    = $this->_require_login();
        $user_id = decode($info['user_id']);
		$this->load->library('excel');
		ini_set('max_execution_time', 0);
		ini_set('memory_limit','2048M');

		$path = 'assets/employee-list/';
		if (!is_dir($path)) {
			mkdir($path, 0777, TRUE);
		}

		if(!empty($_FILES['employee-list-file']['name'])){
			$config['upload_path'] = $path;
	        $config['file_name'] = time() . '_' . generate_random(6);
	        $config['allowed_types'] = '*';
	        $config['max_size'] = 2048;
	        
	        $this->load->library('upload', $config);
	        $this->upload->initialize($config);
	        if(!$this->upload->do_upload('employee-list-file')){
	        	$errors = $this->upload->display_errors();
	        	$msg = '<div class="alert alert-danger">' . $errors . '</div>';
				$this->session->set_flashdata('message', $msg);
				redirect($_SERVER['HTTP_REFERER']);
	        }else{
	        	$upload_data = array('upload_data' => $this->upload->data());
	        	$file_name = $upload_data['upload_data']['file_name'];
	            $upload_path = $path . $file_name;
	        }
		} else {
			$msg = '<div class="alert alert-danger">Please select a file</div>';
			$this->session->set_flashdata('message', $msg);
			redirect($_SERVER['HTTP_REFERER']);
		}

		$this->db->trans_start();

        $file   = $upload_path;

		$objPHPExcel     	= PHPExcel_IOFactory::load($file);
		$cell_collection 	= $objPHPExcel->getActiveSheet()->getCellCollection();
		$high            	= $objPHPExcel->getActiveSheet()->getHighestRow();
		$msg_error       	= '<div class="alert alert-danger">';
		$success_count   	= 0;
		$update_count   	= 0;
		$line_count 		= 0;
		$error_counter 		= 0;

		for($a = 2; $a <= $high; $a++){
			$employee_number    		= $objPHPExcel->getActiveSheet()->getCell('A' . $a)->getValue();
			$name            			= $objPHPExcel->getActiveSheet()->getCell('B' . $a)->getValue();
			$employee_location      	= $objPHPExcel->getActiveSheet()->getCell('C' . $a)->getValue();
			$employee_status      		= $objPHPExcel->getActiveSheet()->getCell('D' . $a)->getValue();
			$employee_number    		= trim(clean_data($employee_number));
			$name    					= trim(clean_data($name));
			$employee_location    		= trim(clean_data($employee_location));
			$employee_status    		= trim(clean_data($employee_status));
			$employee_status			= $employee_status == 'ACTIVE' ? 1 : 0;

			
			$update_record = false;
			$insert_record = false;
			
			if(!empty($employee_number)){

				if(!empty($employee_number)){
					$check_emp_number = $this->main->check_data('employee_tbl', [ 'employee_number' => $employee_number ], TRUE);
					if($check_emp_number['result']){
						$employee_id = $check_emp_number['info']->employee_id;
						$status = $check_emp_number['info']->employee_status == 1 ? 'ACTIVE' : 'INACTIVE';
						$status_id = $check_emp_number['info']->employee_status;
						// $msg_error .= 'Employee number already exist with status of '.$status.'. Line number ' . $a . '!<br>';
						// $error_counter++;
						if($status_id != $employee_status
						|| $employee_location != $check_emp_number['info']->employee_location
						|| $name != $check_emp_number['info']->employee_name
						){
							$update_record = TRUE;
						}
					} else {
						$insert_record = TRUE;
					}
				} else {
					$msg_error .= 'Employee number is required!<br>';
					$error_counter++;
				}
				
				if(!empty($name)){
					$check_emp_name = $this->main->check_data('employee_tbl', [ 'employee_name' => $name ], TRUE);
					if($check_emp_name['result']){
						$employee_id = $check_emp_name['info']->employee_id;
						$status = $check_emp_name['info']->employee_status == 1 ? 'ACTIVE' : 'INACTIVE';
						$status_id = $check_emp_name['info']->employee_status;

						if($employee_number != $check_emp_name['info']->employee_number){

							$msg_error .= 'Employee name already exist with status of '.$status.' and employee number of ['.$check_emp_name['info']->employee_number.']. Check Line number ' . $a . '!<br>';
							$error_counter++;
						}

						if($status_id != $employee_status
						|| $employee_location != $check_emp_name['info']->employee_location
						){
							$update_record = TRUE;
						}
					} else {
						$insert_record = TRUE;
					}
				} else {
					$msg_error .= 'Employee name is required!<br>';
					$error_counter++;
				}
	
				if($error_counter == 0){
					$set = [
						'employee_number' 					=> $employee_number,
						'employee_name' 					=> $name,
						'employee_normalized_name' 			=> $this->_normalized_names($name),
						'employee_location' 				=> $employee_location,
						'created_by' 						=> $user_id,
						'employee_status' 					=> $employee_status,
					];
	
					if($update_record){
						unset($set['created_by']);
						$set['updated_by'] = $user_id;
						$where = array('employee_id' => $employee_id);
						$this->main->update_data('employee_tbl', $set, $where);
						$update_count++;
					} elseif($insert_record) {
						$this->main->insert_data('employee_tbl', $set, TRUE);
						$success_count++;
					}
	
				}
				$line_count++;
			} else {
				$a = $high + 1;
	    		break;
			}
		}

		if($this->db->trans_status() === FALSE){
			$this->db->trans_rollback();
			$msg = '<div class="alert alert-danger">Error please try again!</div>';
			$this->session->set_flashdata('message', $msg);
		}else{
			$this->db->trans_commit();
			$msg = '<div class="alert alert-success">Employee list successfully uploaded
			<br>Inserted : ' . $success_count . '/' . $line_count . '
			<br>Updated : ' .$update_count. '/' . $line_count . '</div>';
			if($error_counter > 0) $msg .= $msg_error . '</div>';;
			$this->session->set_flashdata('message', $msg);
		}

		redirect($_SERVER['HTTP_REFERER']);
	}

	private function _normalized_names($full_name){
		// $full_name = 'Aljune K. Atok';

		$full_name = strtolower($full_name);

		//* Remove punctuation
		$full_name = preg_replace("/[^\w\s]/", "", $full_name);

		//* Remove extra spaces
		$full_name = preg_replace("/\s+/", " ", $full_name);
		$full_name = trim($full_name);

		//* Split into words and sort
		$parts = explode(" ", $full_name);
		sort($parts);
		$sortedInputName = implode(' ', $parts);


		$new_name = preg_replace("/\s+/", " ", $sortedInputName);
		$new_name = trim($new_name);
		$new_name = ucwords($new_name);

		return $new_name;
	}

	public function redeem_coupon()
    {
		$info      = $this->_require_login();
		$data['user_id']   = $info['user_id'];
        $data['title']   = 'Redeem '.SEC_SYS_NAME.'';
        $data['top_nav']     = $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content'] 	 = $this->load->view('admin/coupon/redeem_coupon_content', $data, TRUE);
        $this->load->view('admin/templates', $data);
    }

	

    public function redeem_coupon_v1()
    {
		$info      = $this->_require_login();
        $data['title']   = 'Redeem '.SEC_SYS_NAME.'';
        $data['top_nav']     = $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content'] = $this->load->view('admin/coupon/redeem_coupon_content_v1', $data, TRUE);
        $this->load->view('admin/templates', $data);
    }

	public function redeem_coupon_emp()
    {
		$info      = $this->_require_login();
		$data['user_id']   = $info['user_id'];
        $data['title']   = 'Redeem '.SEC_SYS_NAME.'';
		$data['top_nav']     = $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content'] = $this->load->view('admin/coupon/redeem_coupon_emp_content', $data, TRUE);
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
        $data['title']           = 'Standard '.SEC_SYS_NAME.'';
        $data['top_nav']     	 = $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content']         = $this->load->view('admin/coupon/standard_coupon_content', $data, TRUE);
        $this->load->view('admin/templates', $data);
    }

    public function product_coupon($is_advance_order = NULL, $cat_id = NULL)
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];

		$parent_id = FALSE;
		$order_type = 'Normal Orders';
		$no_order_type = FALSE;
		if(!empty($is_advance_order)){
			$is_advance_order = decode($is_advance_order);
			if($is_advance_order == 1){
				$order_type = 'Advance Orders';
			} elseif($is_advance_order == 2){
				$order_type = 'Issued from Advance Orders';
				$parent_id = TRUE;
				$is_advance_order = 0;
			} elseif($is_advance_order == 0){
				$is_advance_order = 0;
			} else {
				$no_order_type = TRUE;
				$order_type = 'All Orders';
			}
		} else {
			$is_advance_order = 0;
			$no_order_type = TRUE;
			$order_type = 'All Orders';
		}

		$filter_category = FALSE;
		if(!empty($cat_id)){
			$cat_id = decode($cat_id);
			if($cat_id == 0){
				$category_name = 'All Categories';
			} else {
				$category = $this->main->get_data('coupon_category_tbl a', ['coupon_cat_id' => $cat_id], TRUE);
				$category_name = !empty($category) ? $category->coupon_cat_name : 'N/A';
				$filter_category = TRUE;
			}
		} else {
			$cat_id = NULL;
			$category_name = 'All Categories';
		}

		$join_salable = [
			"{$parent_db}.product_tbl b" => 'a.prod_id = b.prod_id AND a.prod_id IN (1, 7, 21, 27) and a.company_id = 2',
			// "{$parent_db}.product_tbl b" => 'a.prod_id = b.prod_id',
        ];

        // $coupon_trans_select = '*, (SELECT COUNT(*) FROM coupon_transaction_details_tbl z WHERE a.coupon_transaction_header_id = z.coupon_transaction_header_id ) as `coupon_qty`';
        $coupon_trans_select = '*, total_coupon_qty as `coupon_qty`, IF(a.parent_transaction_header_id, (SELECT CONCAT("#",x.coupon_transaction_header_id," - ", x.coupon_transaction_header_name) FROM coupon_transaction_header_tbl x WHERE a.parent_transaction_header_id = x.coupon_transaction_header_id), "") as parent_trans';

        $join_coupon = array(
        	"{$parent_db}.user_tbl b" => 'a.user_id = b.user_id',
        	'coupon_category_tbl c'   => 'a.coupon_cat_id = c.coupon_cat_id',
        	'payment_types_tbl d'   => 'a.payment_type_id = d.payment_type_id',
        	'customers_tbl e'   => 'a.customer_id = e.customer_id',
        );
        
		$filter 							= ['a.coupon_transaction_header_status' => 2, 'a.is_advance_order' => $is_advance_order];
		if($parent_id) $filter['a.parent_transaction_header_id <>'] = NULL;
		else $filter['a.parent_transaction_header_id'] = NULL;
		if($filter_category) $filter['a.coupon_cat_id'] = $cat_id;
		if($no_order_type) unset($filter['a.is_advance_order'], $filter['a.parent_transaction_header_id']);
        $data['pending_coupon_trans']  		= $this->main->get_join('coupon_transaction_header_tbl a', $join_coupon, FALSE, 'coupon_transaction_header_added DESC', FALSE, $coupon_trans_select, $filter);
		
		$filter 							= ['a.coupon_transaction_header_status' => 5, 'a.is_advance_order' => $is_advance_order];
		if($parent_id) $filter['a.parent_transaction_header_id <>'] = NULL;
		else $filter['a.parent_transaction_header_id'] = NULL;
		if($filter_category) $filter['a.coupon_cat_id'] = $cat_id;
		if($no_order_type) unset($filter['a.is_advance_order'], $filter['a.parent_transaction_header_id']);
        $data['approved_coupon_trans'] 		= $this->main->get_join('coupon_transaction_header_tbl a', $join_coupon, FALSE, 'coupon_transaction_header_added DESC', FALSE, $coupon_trans_select, $filter);
		
		$filter 							= ['a.coupon_transaction_header_status' => 4, 'a.is_advance_order' => $is_advance_order];
		if($parent_id) $filter['a.parent_transaction_header_id <>'] = NULL;
		else $filter['a.parent_transaction_header_id'] = NULL;
		if($filter_category) $filter['a.coupon_cat_id'] = $cat_id;
		if($no_order_type) unset($filter['a.is_advance_order'], $filter['a.parent_transaction_header_id']);
        $data['first_appr_coupon_trans'] 	= $this->main->get_join('coupon_transaction_header_tbl a', $join_coupon, FALSE, 'coupon_transaction_header_added DESC', FALSE, $coupon_trans_select, $filter);
		
		$filter 							= ['a.coupon_transaction_header_status' => 1, 'a.is_advance_order' => $is_advance_order];
		if($parent_id) $filter['a.parent_transaction_header_id <>'] = NULL;
		else $filter['a.parent_transaction_header_id'] = NULL;
		if($filter_category) $filter['a.coupon_cat_id'] = $cat_id;
		if($no_order_type) unset($filter['a.is_advance_order'], $filter['a.parent_transaction_header_id']);
        $data['active_coupon_trans'] 		= $this->main->get_join('coupon_transaction_header_tbl a', $join_coupon, FALSE, 'coupon_transaction_header_added DESC', FALSE, $coupon_trans_select, $filter);
		
		$filter 							= ['a.coupon_transaction_header_status' => 0, 'a.is_advance_order' => $is_advance_order];
		if($parent_id) $filter['a.parent_transaction_header_id <>'] = NULL;
		else $filter['a.parent_transaction_header_id'] = NULL;
		if($filter_category) $filter['a.coupon_cat_id'] = $cat_id;
		if($no_order_type) unset($filter['a.is_advance_order'], $filter['a.parent_transaction_header_id']);
        $data['inactive_coupon_trans'] 		= $this->main->get_join('coupon_transaction_header_tbl a', $join_coupon, FALSE, 'coupon_transaction_header_added DESC', FALSE, $coupon_trans_select, $filter);
		

        $data['products']    				= $this->main->get_join("{$parent_db}.product_sale_tbl a", $join_salable);
        $data['brand']       				= $this->main->get_data("{$parent_db}.brand_tbl", ['brand_status' => 1]);
        $data['bc']          				= $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
        $data['coupon_type'] 				= $this->main->get_data('coupon_type_tbl', ['coupon_type_status' => 1]);
        $data['value_type']  				= $this->main->get_data('coupon_value_type_tbl a', ['coupon_value_type_status' => 1]);
		if($filter_category)$filter_cat['coupon_cat_id'] = $cat_id;
		else $filter_cat['coupon_cat_status'] = 1;
        $data['category']    				= $this->main->get_data('coupon_category_tbl a', $filter_cat);
        $data['category_menu']    			= $this->main->get_data('coupon_category_tbl a', ['coupon_cat_status' => 1]);
		$data['filter_category'] 			= TRUE;
        $data['holder_type'] 				= $this->main->get_data('coupon_holder_type_tbl a', ['coupon_holder_type_status' => 1]);
        $data['scope_masking']          	= $this->main->get_data("scope_masking_tbl", ['scope_masking_status' => 1]);
        $data['customer']          			= $this->main->get_data("customers_tbl", ['customer_status' => 1]);
		$data['title']       				= 'Product '.SEC_SYS_NAME.'';
		$data['is_advance_order'] 			= $is_advance_order;
		$data['parent_id'] 					= $parent_id;
		$data['order_type'] 				= $order_type;
		$data['category_name'] 				= $category_name;
		$qry = "SELECT 
					coupon_qty, 
					coupon_transaction_header_id, 
					coupon_transaction_header_name, 
					coupon_scope
				FROM (
					SELECT 
						COUNT(b.coupon_transaction_details_id) AS coupon_qty,
						a.coupon_transaction_header_id,
						a.coupon_transaction_header_name,
						IF(
							c.is_nationwide = 1, 
							'Nationwide', 
							(
								SELECT GROUP_CONCAT(DISTINCT y.bc_name SEPARATOR ', ')
								FROM coupon_bc_tbl x
								INNER JOIN chooks_delivery_db.bc_tbl y ON x.bc_id = y.bc_id AND y.bc_name <> 'CDI'
								WHERE x.coupon_id = c.coupon_id
							)
						) AS coupon_scope
					FROM coupon_transaction_header_tbl a
					INNER JOIN coupon_transaction_details_tbl b ON a.coupon_transaction_header_id = b.coupon_transaction_header_id
					INNER JOIN coupon_tbl c ON b.coupon_id = c.coupon_id
					WHERE a.coupon_transaction_header_status = 1
					AND a.is_advance_order = 1
					GROUP BY a.coupon_transaction_header_id
				) AS sub
				WHERE coupon_qty > 0";
		$data['advance_orders']          	= $this->main->get_query($qry);
        $data['top_nav']     				= $this->load->view('fix/top_nav_content', $data, TRUE);

		$data['controller']					= $this->controller;
		$data['access_type']				= str_replace('-', '_', $this->controller);
        $dynamic_content 					= 'coupon/product_coupon_content';
        $data['content']     				= $this->load->view($dynamic_content, $data, TRUE);
        $this->load->view('admin/templates', $data);
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

    private function _get_coupon($length){
    	$counter = TRUE;
        while($counter){
            $coupon = generate_random_coupon($length);

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
            [ 'field' => 'bc[]'        			, 'label' => 'Business Center'     		,'rules' => 'required'                ],
            [ 'field' => 'brand[]'     			, 'label' => 'Brand'               		,'rules' => 'required'                ],
            [ 'field' => 'name'        			, 'label' => ''.SEC_SYS_NAME.' Name'         		,'rules' => 'required|max_length[70]' ],
            [ 'field' => 'code'        			, 'label' => ''.SEC_SYS_NAME.' Code'         		,'rules' => 'required'                ],
            [ 'field' => 'amount'      			, 'label' => ''.SEC_SYS_NAME.' amount'       		,'rules' => 'required|integer'        ],
            [ 'field' => 'qty'         			, 'label' => ''.SEC_SYS_NAME.' Qty'          		,'rules' => 'required|integer'        ],
            [ 'field' => 'date_range'  			, 'label' => ''.SEC_SYS_NAME.' Start & End' 		,'rules' => 'required'                ],
            [ 'field' => 'category'    			, 'label' => ''.SEC_SYS_NAME.' Category'     		,'rules' => 'required'                ],
            [ 'field' => 'value_type'  			, 'label' => 'Value Type'          		,'rules' => 'required'                ],
            [ 'field' => 'holder_type' 			, 'label' => 'Holder Type'         		,'rules' => 'required'                ],
			// [ 'field' => 'total-voucher-value' 	, 'label' => SYS_NAME.' total amount'   ,'rules' => 'integer'                 ],
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
                // [
                //     'field' => 'total-voucher-value',
                //     'label' => ''.SEC_SYS_NAME.' Paid Value',
                //     'rules' => 'required'
                // ],
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

    private function _store_coupon_trans_header($coupon_name, $category, $start, $end, $payment_status, $invoice_number, $product_coupon_qty=0, $voucher_value=0, $for_printing=0, $scope_masking="", $display_exp=0, $for_image_conv=0, $payment_terms=0, $payment_type_id=1, $customer_id=0, $order_type="", $parent_transaction_header_id=NULL, $allocation_count=0)
    {
    	$info    = $this->_require_login();
    	$user_id = decode($info['user_id']);
		$total_coupon_value = $product_coupon_qty * $voucher_value;
		$is_advance_order = strtoupper(trim($order_type)) == 'ADVANCE' ? 1 : 0;
        $data = [
        	'coupon_cat_id'                    			=> $category,
        	'user_id'                          			=> $user_id,
        	'invoice_number'                   			=> $invoice_number,
        	'payment_status'                   			=> $payment_status,
			'total_coupon_value'               			=> $total_coupon_value,
        	'total_coupon_qty'               			=> $product_coupon_qty,
        	'orig_total_coupon_qty'               		=> $product_coupon_qty,
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
        	'is_advance_order'    						=> $is_advance_order,
        	'parent_transaction_header_id'				=> $parent_transaction_header_id,
        	'coupon_transaction_header_added'  			=> date_now(),
        	'coupon_pdf_archived'  						=> 0,
			'allocation_count'							=> $allocation_count,
        	'coupon_transaction_header_status' 			=> 2 //* PENDING
        ];
        return $this->main->insert_data('coupon_transaction_header_tbl', $data, TRUE);
    }


    private function _store_coupon_trans_details($header_id, $coupon_id, $order_type, $series_start)
    {
		$coupon_qty = 1;
		$series = [];
		if($order_type == 'advance'){
			if($series_start > 0){
				$series = $this->_series_generator($header_id, $series_start, $coupon_qty);
			}
		}
        $data = [
            'coupon_transaction_header_id'      => $header_id,
            'coupon_id'                         => $coupon_id,
            'coupon_transaction_details_added'  => date_now(),
            'coupon_transaction_details_status' => 1,
			'series_number'						=> !empty($series) ? $series[0] : NULL,
        ];
        $result = $this->main->insert_data('coupon_transaction_details_tbl', $data, TRUE);
    }

	private function _update_coupon_trans_details($params){
		$update_coupon = FALSE;
		$trans_details 				= $this->main->get_data('coupon_transaction_details_tbl a', ['coupon_transaction_header_id' => $params['header_id']], FALSE, FALSE, 'coupon_transaction_details_id', $params['coupon_qty']);
		$trans_header 				= $this->main->get_data('coupon_transaction_header_tbl a', ['coupon_transaction_header_id' => $params['header_id']], TRUE, FALSE, 'coupon_transaction_header_id');
		if(!empty($trans_details) && !empty($trans_header)){
			$issued = $trans_header->orig_total_coupon_qty - $trans_header->total_coupon_qty;
			$series_start = $issued + 1;

			$new_coupon_qty = $trans_header->total_coupon_qty - $params['coupon_qty'];
			$set      = [ 'total_coupon_qty' => $new_coupon_qty ];
			$where    = [ 'coupon_transaction_header_id' => $params['header_id'] ];
			$update_header = $this->main->update_data('coupon_transaction_header_tbl', $set, $where);

			$i = 0;
			if($update_header){
				foreach($trans_details as $row){
					
					$set      = [
						'coupon_transaction_header_id' 			=> $params['new_header_id']
					];
					$where    = [ 'coupon_transaction_details_id' => $row->coupon_transaction_details_id ];
					$update_details = $this->main->update_data('coupon_transaction_details_tbl', $set, $where);
	
					if($update_details){
						$set      = [
							'coupon_name'          				=> $params['coupon_name'],
							'coupon_value'          			=> $params['coupon_value'],
							'coupon_regular_value'  			=> $params['coupon_regular_value'],
							'coupon_start'          			=> $params['coupon_start'],
							'coupon_end'            			=> $params['coupon_end'],
							'coupon_holder_name'    			=> $params['coupon_holder_name'],
							'coupon_holder_type_id' 			=> $params['coupon_holder_type_id'],
							'coupon_holder_email'   			=> $params['coupon_holder_email'],
							'coupon_holder_contact' 			=> $params['coupon_holder_contact'],
							'coupon_holder_address' 			=> $params['coupon_holder_address'],
							'coupon_holder_tin'     			=> $params['coupon_holder_tin'],
							'payment_status'        			=> $params['payment_status'],
							'company_id'        				=> $params['company_id'],
							'customer_id'        				=> $params['customer_id']
						];
						$where    = [ 'coupon_id' => $row->coupon_id ];
						$update_coupon = $this->main->update_data('coupon_tbl', $set, $where);
						$i++;
					}
				}
			}
		}

		return $update_coupon;
	}

	private function _update_coupon_back_to_parent_details($params){
		$update_coupon = FALSE;
		$trans_details 				= $this->main->get_data('coupon_transaction_details_tbl a', ['coupon_transaction_header_id' => $params['header_id']], FALSE, FALSE, 'coupon_transaction_details_id');
		$parent_trans_header 		= $this->main->get_data('coupon_transaction_header_tbl a', ['coupon_transaction_header_id' => $params['parent_header_id']], TRUE, FALSE, 'coupon_transaction_header_id');
		if(!empty($parent_trans_header) && !empty($trans_details)){
			$issued = count($trans_details);

			$new_coupon_qty 		= $parent_trans_header->total_coupon_qty + $issued;
			$set      				= [ 'total_coupon_qty' => $new_coupon_qty ];
			$where    				= [ 'coupon_transaction_header_id' => $params['parent_header_id'] ];
			$update_header 			= $this->main->update_data('coupon_transaction_header_tbl', $set, $where);

			$coupon_name = $parent_trans_header->coupon_transaction_header_name;
			$coupon_start = $parent_trans_header->coupon_transaction_header_start;
			$coupon_end = $parent_trans_header->coupon_transaction_header_end;
			$customer_id = $parent_trans_header->customer_id;

			$i = 0;
			if($update_header){
				foreach($trans_details as $row){
					
					$set      = [
						'coupon_transaction_header_id' 			=> $params['parent_header_id']
					];
					$where    = [ 'coupon_transaction_details_id' => $row->coupon_transaction_details_id ];
					$update_details = $this->main->update_data('coupon_transaction_details_tbl', $set, $where);
	
					if($update_details){
						$set      = [
							'coupon_name'          				=> $coupon_name,
							'coupon_value'          			=> 0,
							'coupon_regular_value'  			=> 0,
							'coupon_start'          			=> $coupon_start,
							'coupon_end'            			=> $coupon_end,
							'coupon_holder_name'    			=> '',
							'coupon_holder_type_id' 			=> 5,
							'coupon_holder_email'   			=> '',
							'coupon_holder_contact' 			=> '',
							'coupon_holder_address' 			=> '',
							'coupon_holder_tin'     			=> '',
							'payment_status'        			=> 0,
							'company_id'        				=> 8,
							'customer_id'        				=> $customer_id
						];
						$where    = [ 'coupon_id' => $row->coupon_id ];
						$update_coupon = $this->main->update_data('coupon_tbl', $set, $where);
						$i++;
					}
				}
			}
		}

		return $update_coupon;
	}

	public function test_series_generator()
	{
		$result = $this->_series_generator('1534', 1, 100);
		pretty_dump($result);
	}

	private function _series_generator($id, $start, $count){
		$series = [];
		for ($i = 0; $i < $count; $i++) {
			$number = str_pad($start + $i, 5, '0', STR_PAD_LEFT);
			$series[] = "{$id}-{$number}";
		}
		return $series;
	}

	private function _test_modulo(){

		$items = [
			['item_id' => 1, 'name' => 'Product A'],
			['item_id' => 2, 'name' => 'Product B'],
			['item_id' => 3, 'name' => 'Product C'],
			['item_id' => 4, 'name' => 'Product D'],
			['item_id' => 5, 'name' => 'Product E'],
			['item_id' => 6, 'name' => 'Product F'],
			['item_id' => 7, 'name' => 'Product G'],
			['item_id' => 8, 'name' => 'Product H'],
			['item_id' => 9, 'name' => 'Product I'],
			['item_id' => 10, 'name' => 'Product J'],
		];
		
		$classCounter = 0;
		
		foreach ($items as &$item) {

			if (($item['item_id'] - 1) % 3 === 0) {
				$item['class_id'] = 'group-' . $classCounter;
				$classCounter++;
			} else {
				$item['class_id'] = 'group-' . ($classCounter - 1);
			}
		}
		
		pretty_dump($items);
	}


    public function store_product_coupon()
    {
        $info      = $this->_require_login();
        $user_id   = decode($info['user_id']);
        $parent_db = $GLOBALS['parent_db'];

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            redirect($_SERVER['HTTP_REFERER']);
        }

		$order_type    	= decode(clean_data($this->input->post('order_type')));
		$order_type		= trim($order_type);
		$holder_type = decode(clean_data($this->input->post('holder_type')));
		$company_id  = decode(clean_data($this->input->post('company_id')));
		$parent_transaction_header_id  = NULL;
		if($order_type == 'normal') {
			$rules = [
				[ 'field' => 'bc[]'          		, 'label' => 'Business Center'     			,'rules' => 'required'                ],
				[ 'field' => 'brand[]'       		, 'label' => 'Brand'               			,'rules' => 'required'                ],
				[ 'field' => 'product[]'     		, 'label' => 'Product'             			,'rules' => 'required'                ],
				[ 'field' => 'name'          		, 'label' => ''.SEC_SYS_NAME.' Name'        ,'rules' => 'required|max_length[70]' ],
				[ 'field' => 'amount'        		, 'label' => ''.SEC_SYS_NAME.' amount'      ,'rules' => 'required|integer'        ],
				[ 'field' => 'voucher-value' 		, 'label' => SEC_SYS_NAME.' paid amount'    ,'rules' => 'integer'                 ],
				[ 'field' => 'date_range'    		, 'label' => SEC_SYS_NAME.' Start & End' 	,'rules' => 'required'                ],
				[ 'field' => 'category'      		, 'label' => ''.SEC_SYS_NAME.' Category'    ,'rules' => 'required'                ],
				[ 'field' => 'value_type'    		, 'label' => 'Value Type'          			,'rules' => 'required'                ],
				[ 'field' => 'holder_type'   		, 'label' => 'Holder Type'         			,'rules' => 'required'                ],
				[ 'field' => 'total-voucher-value' 	, 'label' => SEC_SYS_NAME.' total amount'   ,'rules' => 'integer'                 ],
				[ 'field' => 'voucher-regular-value', 'label' => SEC_SYS_NAME.' regular amount'	,'rules' => 'integer'                 ],
				[ 'field' => 'company_id'       	, 'label' => 'Requestor\'s Company'         ,'rules' => 'required'                ],
				[ 'field' => 'customer_id'       	, 'label' => 'Customer'               		,'rules' => 'required'                ],
				[ 'field' => 'product_coupon_qty'   , 'label' => 'Product Coupon Quantity'      ,'rules' => 'required'                ],
			];
			
		} elseif($order_type == 'advance') {
			$rules = [
				[ 'field' => 'bc[]'          		, 'label' => 'Business Center'     			,'rules' => 'required'                ],
				[ 'field' => 'brand[]'       		, 'label' => 'Brand'               			,'rules' => 'required'                ],
				[ 'field' => 'product[]'     		, 'label' => 'Product'             			,'rules' => 'required'                ],
				[ 'field' => 'name'          		, 'label' => ''.SEC_SYS_NAME.' Name'        ,'rules' => 'required|max_length[70]' ],
				[ 'field' => 'amount'        		, 'label' => ''.SEC_SYS_NAME.' amount'      ,'rules' => 'required|integer'        ],
				[ 'field' => 'category'      		, 'label' => ''.SEC_SYS_NAME.' Category'    ,'rules' => 'required'                ],
				[ 'field' => 'value_type'    		, 'label' => 'Value Type'          			,'rules' => 'required'                ],
			];
			$holder_type = 5; //* NONE
			$company_id  = 8; //* NONE
		}elseif($order_type == 'issue_on_advance') {
			$rules = [
				[ 'field' => 'parent_transaction_header_id'	, 'label' => 'From Advance Order Transaction'   ,'rules' => 'required' ],
				[ 'field' => 'name'          				, 'label' => ''.SEC_SYS_NAME.' Name'        	,'rules' => 'required|max_length[70]' ],
				[ 'field' => 'customer_id'       			, 'label' => 'Customer'               			,'rules' => 'required'                ],
				[ 'field' => 'product_coupon_qty'   		, 'label' => 'Product Coupon Quantity'      	,'rules' => 'required'                ],
				[ 'field' => 'category'      				, 'label' => ''.SEC_SYS_NAME.' Category'    	,'rules' => 'required'                ],
				[ 'field' => 'holder_type'   				, 'label' => 'Holder Type'         				,'rules' => 'required'                ],
				[ 'field' => 'date_range'    				, 'label' => SEC_SYS_NAME.' Start & End' 		,'rules' => 'required'                ],
				[ 'field' => 'company_id'       			, 'label' => 'Requestor\'s Company'         	,'rules' => 'required'                ],
				[ 'field' => 'total-voucher-value' 			, 'label' => SEC_SYS_NAME.' total amount'   	,'rules' => 'integer'                 ],
				[ 'field' => 'voucher-regular-value'		, 'label' => SEC_SYS_NAME.' regular amount'		,'rules' => 'integer'                 ],
			];
			$parent_transaction_header_id  = decode(clean_data($this->input->post('parent_transaction_header_id')));
		}

        $this->_run_form_validation($rules);

        $category    = decode(clean_data($this->input->post('category')));

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
			if($order_type != 'advance') {
				$rules = [
					[ 'field' => 'address'      			, 'label' => 'Holder Address'    				, 'rules' => 'required'],
					[ 'field' => 'voucher-regular-value'	, 'label' => ''.SEC_SYS_NAME.' Regular Value'	, 'rules' => 'required'],
					[ 'field' => 'tin'          			, 'label' => 'Holder TIN'        				, 'rules' => 'required'],
					[ 'field' => 'total-voucher-value'		, 'label' => ''.SEC_SYS_NAME.' Paid Value'		, 'rules' => 'required'],
					[ 'field' => 'voucher-value'			, 'label' => ''.SEC_SYS_NAME.' Paid Value'		, 'rules' => 'required'],
				];
				array_push($additional_rules, $rules);
			}
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
        $for_printing       = clean_data($this->input->post('for_printing'));
        $for_image_conv     = clean_data($this->input->post('for_image_conv'));
        $scope_masking      = clean_data($this->input->post('scope_masking'));
        $display_exp      	= clean_data($this->input->post('display_exp'));
        $name               = clean_data($this->input->post('name'));
        $amount             = clean_data($this->input->post('amount'));
        $dates              = $this->input->post('date_range') ? explode(' - ', clean_data($this->input->post('date_range'))) : [];
        $start              = !empty($dates) && $dates[0] ? date('Y-m-d', strtotime($dates[0])) : date('Y-m-d', strtotime('-1 day'));
        $end                = !empty($dates) && $dates[1] ? date('Y-m-d', strtotime($dates[1])) : date('Y-m-d', strtotime('-1 day'));
        $value_type         = decode(clean_data($this->input->post('value_type')));
        $holder_name        = $this->input->post('holder_name') ? clean_data($this->input->post('holder_name')) : '';
        $holder_email       = $this->input->post('holder_email') ? clean_data($this->input->post('holder_email')) : '';
        $holder_contact     = $this->input->post('holder_contact') ? clean_data($this->input->post('holder_contact')) : '';
        $voucher_value      = ($this->input->post('voucher-value') != NULL)? clean_data($this->input->post('voucher-value')) : 0;
        $allocation_count      = ($this->input->post('allocation_count') != NULL)? clean_data($this->input->post('allocation_count')) : 0;
        $holder_address     = ($this->input->post('address') != NULL) ? clean_data($this->input->post('address')) : '';
        $holder_tin         = ($this->input->post('tin') != NULL) ? clean_data($this->input->post('tin')) : '';
        $payment_terms      = ($this->input->post('payment_terms') != NULL) ? clean_data($this->input->post('payment_terms')) : 0;
        $payment_type_id    = ($this->input->post('payment_type_id') != NULL) ? clean_data(decode($this->input->post('payment_type_id'))) : 1;
        $customer_id    	= ($this->input->post('customer_id') != NULL) ? clean_data(decode($this->input->post('customer_id'))) : NULL;
        // $payment_status     = ($holder_type == 4) ? 0 : 1;
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

		if($order_type == 'normal'){
			$customer_name = "";
			if(!is_numeric($customer_id)){
				$customer_name = clean_data(trim($this->input->post('customer_id')));
			}
			$this->_validate_prod_sale($prod_sale);
			$this->_validate_brand($brand);
			$this->_validate_bc($bc);
			$this->_validate_category($category);
			$this->_validate_value_type($value_type);
			$this->_validate_holder_type($holder_type);
			$this->_validate_company($company_id);
			$this->_validate_payment_type($payment_type_id);
		}elseif($order_type == 'advance'){
			$customer_name = "ADVANCE ORDERS";
			$this->_validate_prod_sale($prod_sale);
			$this->_validate_brand($brand);
			$this->_validate_bc($bc);
			$this->_validate_category($category);
			$this->_validate_value_type($value_type);
			$payment_type_id = 7; //* ADVANCE ORDER
		}elseif($order_type == 'issue_on_advance'){
			$customer_name = "";
			if(!is_numeric($customer_id)){
				$customer_name = clean_data(trim($this->input->post('customer_id')));
			}
			$this->_validate_holder_type($holder_type);
			$this->_validate_company($company_id);
			$this->_validate_payment_type($payment_type_id);
		}
		$customer_id = $this->_validate_customer($customer_id, $customer_name);
		$payment_status     = ($payment_type_id == 4 || $payment_type_id == 7) ? 0 : 1; //* UNPAID WHEN CREDIT & ADVANCE ORDER PAYMENT TYPE

        $this->db->trans_start();
        $trans_result = $this->_store_coupon_trans_header($name, $category, $start, $end, $payment_status, $invoice_number, $product_coupon_qty, $voucher_value, $for_printing, $scope_masking, $display_exp, $for_image_conv, $payment_terms, $payment_type_id, $customer_id, $order_type, $parent_transaction_header_id, $allocation_count);
        if ($trans_result) {
            $this->_store_transaction_action_log(1, $trans_result['id']);
            if (isset($_FILES['attachment']) && $_FILES['attachment']['name'][0] != '') {
                $this->_upload_transaction_attachment($trans_result['id'], 1);
            }
        }

		if($order_type == 'issue_on_advance'){
			//* UPDATE TRANS DETAILS ROUTINE
			$params = [
				'header_id'          				=> $parent_transaction_header_id,
				'new_header_id'          			=> $trans_result['id'],
				'coupon_name'          				=> $name,
				'coupon_qty'          				=> $product_coupon_qty,
				'coupon_start'          			=> $start,
				'coupon_end'            			=> $end,
				'coupon_value'          			=> $voucher_value,
				'coupon_regular_value'  			=> $voucher_regular_value,
				'coupon_holder_name'    			=> $holder_name,
				'coupon_holder_type_id' 			=> $holder_type,
				'coupon_holder_email'   			=> $holder_email,
				'coupon_holder_contact' 			=> $holder_contact,
				'coupon_holder_address' 			=> $holder_address,
				'coupon_holder_tin'     			=> $holder_tin,
				'payment_status'        			=> $payment_status,
				'company_id'        				=> $company_id,
				'customer_id'        				=> $customer_id,
			];
			$update_trans_details = $this->_update_coupon_trans_details($params);
		} else {
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
	
			$coupon_sequence = 0;
			for ($i = 1; $i <= $product_coupon_qty; $i++) {
				$code_length = $category == 6 || $category == 7 ? 8 : 7;
				$code = $this->_get_coupon($code_length);
				$data = [
					'coupon_name'           			=> $name,
					'coupon_code'           			=> $code,
					'coupon_amount'         			=> $amount,
					'coupon_value'          			=> $voucher_value,
					'coupon_regular_value'  			=> $voucher_regular_value,
					'coupon_qty'            			=> 1,
					'coupon_use'            			=> 0,
					'coupon_value_type_id'  			=> $value_type,
					'coupon_type_id'        			=> 2,
					'coupon_cat_id'         			=> $category,
					'user_id'               			=> $user_id,
					'coupon_start'          			=> $start,
					'coupon_end'            			=> $end,
					'coupon_holder_name'    			=> $holder_name,
					'coupon_holder_type_id' 			=> $holder_type,
					'coupon_holder_email'   			=> $holder_email,
					'coupon_holder_contact' 			=> $holder_contact,
					'coupon_holder_address' 			=> $holder_address,
					'coupon_holder_tin'     			=> $holder_tin,
					'coupon_added'          			=> date_now(),
					'coupon_status'         			=> 2,
					'is_nationwide'         			=> $is_nationwide,
					'is_orc'                			=> $is_orc,
					'invoice_number'        			=> $invoice_number,
					'payment_status'        			=> $payment_status,
					'company_id'        				=> $company_id,
					'customer_id'        				=> $customer_id,
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
						
						if($category == 6){ //FOR QR PROMO
							$form_id = $clean_brand_row == 1 ? 3 : 4;
							$freebie_data  = [
								'brand_id'            			=> $clean_brand_row,
								'form_id'            			=> $form_id,
								'coupon_id'           			=> $coupon_result['id'],
								'freebie_date'					=> $start,
								'survey_freebie_cal_status' 	=> 0
							];
							$this->main->insert_data('survey_freebie_calendar_tbl', $freebie_data);
						}
						
						if($category == 7){ //FOR CHOOKSIE QR PROMO
							$form_id = 5;
							$freebie_data  = [
								'brand_id'            			=> $clean_brand_row,
								'form_id'            			=> $form_id,
								'coupon_id'           			=> $coupon_result['id'],
								'freebie_date'					=> $start,
								'survey_freebie_cal_status' 	=> 0
							];
							$this->main->insert_data('survey_freebie_calendar_tbl', $freebie_data);
						}
					}
					
					$allocate_to_each_bc = clean_data($this->input->post('allocate_to_each_bc'));
					$allocation_count = clean_data($this->input->post('allocation_count'));
					$allocation_count = !empty($allocation_count) ? $allocation_count * 1 : 0;
					if($allocate_to_each_bc && $allocation_count){
						//* PER BC ALLOCATION BASE ON ALLOCATION COUPON COUNT, SUITED FOR QR PROMO VOUCHERS
						$encoded_bc = TRUE;
						if (in_array('nationwide', $bc)) {
							$bc_list = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
							$bc = array_column($bc_list, 'bc_id');
							$encoded_bc = FALSE;	
						}
	
						if( ($i - 1) % $allocation_count === 0 ){
							$bc_val = isset($bc[$coupon_sequence]) ? $bc[$coupon_sequence] : $bc[0];
							if($encoded_bc) $bc_val = isset($bc[$coupon_sequence]) ? decode(clean_data($bc[$coupon_sequence])) : decode(clean_data($bc[0]));
							$coupon_sequence++;
						} else {
							$bc_val = isset($bc[$coupon_sequence - 1]) ? $bc[$coupon_sequence - 1] : $bc[0];
							if($encoded_bc) $bc_val = isset($bc[$coupon_sequence - 1]) ? decode(clean_data($bc[$coupon_sequence - 1])) : decode(clean_data($bc[0]));
						}
						//* GET THE BC BASE ON QTY ALLOCATED
						$coupon_bc_data = [
							'bc_id'            					=> $bc_val,
							'coupon_id'        					=> $coupon_result['id'],
							'coupon_bc_status' 					=> 1
						];
						// $coupon_bc_data['bc_id'] 			= $bc_val;
						// $coupon_bc_data['coupon_id'] 			= $coupon_result['id'];
						// $coupon_bc_data['coupon_bc_status'] 	= 1;
						$this->main->insert_data('coupon_bc_tbl', $coupon_bc_data);
					} else {
						//* PER BC SHARED ON COUPON
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
				}
	
				if ($trans_result['result'] && $coupon_result['result']) {
					$this->_store_coupon_trans_details($trans_result['id'], $coupon_result['id'], $order_type, $i);

					$pdf_path = $this->_generate_coupon_pdf($coupon_result['id'], $scope_masking, $trans_result['id'], $display_exp);
					
					$set      = [ 'coupon_pdf_path' => $pdf_path ];
					$where    = [ 'coupon_id' => $coupon_result['id'] ];
					$this->main->update_data('coupon_tbl', $set, $where);
	
					// if ($this->input->post('email_notif') != FALSE) {
					//     $this->_email_transaction_coupon($trans_result['id']);
					// }
	
					// if ($this->input->post('sms_notif') != FALSE) {
					//     $this->_send_coupon_sms($coupon_result['id']);
					// }
				}
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

	public function regenerate_coupon_pdf($coupon_transaction_header_id=0){

		if(!$coupon_transaction_header_id){
			$coupon_transaction_header_id = $this->input->post('id');
		}
		$trans_id = decode($coupon_transaction_header_id);
		$join_voucher = array(
    		'coupon_transaction_details_tbl b' => 'a.coupon_transaction_header_id = b.coupon_transaction_header_id AND b.coupon_transaction_details_status = 1 AND a.coupon_transaction_header_status = 1 AND a.coupon_transaction_header_id = ' . $trans_id,
    		'coupon_tbl c'                     => 'b.coupon_id = c.coupon_id AND c.coupon_status = 1'
    	);
    	$get_voucher = $this->main->get_join('coupon_transaction_header_tbl a', $join_voucher);
		if(!empty($get_voucher)){
			foreach($get_voucher as $row){
				$coupon_id = $row->coupon_id;
				$scope_masking = $row->coupon_scope_masking;
				$pdf_path = $this->_generate_coupon_pdf($coupon_id, $scope_masking, $trans_id, $display_exp=0);		
				$set      = [ 'coupon_pdf_path' => $pdf_path ];
				$where    = [ 'coupon_id' => $coupon_id ];
				$this->main->update_data('coupon_tbl', $set, $where);

				$link_hash    = '';
				if ($row->coupon_status == 1) {
					$link_hash = '#nav-active';
				} elseif ($row->coupon_status == 2) {
					$link_hash = '#nav-pending';
				} elseif ($row->coupon_status == 0) {
					$link_hash = '#nav-inactive';
				}
			}
			
			$set      = [ 'coupon_pdf_archived' => 0 ];
			$where    = [ 'coupon_transaction_header_id' => $trans_id ];
			$this->main->update_data('coupon_transaction_header_tbl', $set, $where);
		}


		// $this->zip_coupon($coupon_transaction_header_id);
		// redirect('admin/zip-coupon/'.$coupon_transaction_header_id);
		redirect($_SERVER['HTTP_REFERER'].$link_hash);
	}
	
	public function archive_coupon_pdf($coupon_transaction_header_id=0){
		if(!$coupon_transaction_header_id){
			$coupon_transaction_header_id = $this->input->post('id');
		}
		$trans_id = decode($coupon_transaction_header_id);
		$join_voucher = array(
    		'coupon_transaction_details_tbl b' => 'a.coupon_transaction_header_id = b.coupon_transaction_header_id AND b.coupon_transaction_details_status = 1 AND a.coupon_transaction_header_status = 1 AND a.coupon_transaction_header_id = ' . $trans_id,
    		'coupon_tbl c'                     => 'b.coupon_id = c.coupon_id AND c.coupon_status = 1'
    	);
    	$get_voucher = $this->main->get_join('coupon_transaction_header_tbl a', $join_voucher);
		if(!empty($get_voucher)){
			$folder_path     = FCPATH. '/assets/coupons/' .$trans_id. '/';
			array_map('unlink', glob("$folder_path/*.*"));
			
			$link_hash = '#nav-active';
			
			$set      = [ 'coupon_pdf_archived' => 1 ];
			$where    = [ 'coupon_transaction_header_id' => $trans_id ];
			$this->main->update_data('coupon_transaction_header_tbl', $set, $where);
		}


		// $this->zip_coupon($coupon_transaction_header_id);
		// redirect('admin/zip-coupon/'.$coupon_transaction_header_id);
		redirect($_SERVER['HTTP_REFERER'].$link_hash);
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
		$coupon_join = array(
			'coupon_transaction_details_tbl b' => 'a.coupon_id = b.coupon_Id AND a.coupon_Id = '.$coupon_id,
			'coupon_transaction_header_tbl c' => 'b.coupon_transaction_header_id = c.coupon_transaction_header_id'
		);
		$coupon_select = 'a.*, c.coupon_transaction_header_id, c.coupon_scope_masking';
        $check_coupon = $this->main->check_join('coupon_tbl a', $coupon_join, TRUE, FALSE, FALSE, $coupon_select);
        if ($check_coupon['result'] != TRUE) {
            $this->session->set_flashdata('message', 'Invalid '.SEC_SYS_NAME.' ID');
            redirect($_SERVER['HTTP_REFERER']);
        }
		$coupon_transaction_header_id = $check_coupon['info']->coupon_transaction_header_id;
		$coupon_scope_masking = $check_coupon['info']->coupon_scope_masking;

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

            $pdf_path = $this->_generate_coupon_pdf($coupon_id, $coupon_scope_masking, $coupon_transaction_header_id);
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
		$coupon_join = array(
			'coupon_transaction_details_tbl b' => 'a.coupon_id = b.coupon_Id AND a.coupon_Id = '.$coupon_id,
			'coupon_transaction_header_tbl c' => 'b.coupon_transaction_header_id = c.coupon_transaction_header_id'
		);
		$coupon_select = 'a.*, c.coupon_transaction_header_id, c.coupon_scope_masking';
        $check_coupon = $this->main->check_join('coupon_tbl a', $coupon_join, TRUE, FALSE, FALSE, $coupon_select);
        // $check_coupon = $this->main->check_data('coupon_tbl a', ['coupon_id' => $coupon_id], TRUE);
        if ($check_coupon['result'] != TRUE) {
            $this->session->set_flashdata('message', 'Invalid '.SEC_SYS_NAME.' ID');
            redirect($_SERVER['HTTP_REFERER']);
        }
		$coupon_transaction_header_id = $check_coupon['info']->coupon_transaction_header_id;
		$coupon_scope_masking = $check_coupon['info']->coupon_scope_masking;
        
        $link_hash    = '';
        if ($check_coupon['info']->coupon_status == 1) {
            $link_hash = '#nav-active';
        } elseif ($check_coupon['info']->coupon_status == 2) {
            $link_hash = '#nav-pending';
        } elseif ($check_coupon['info']->coupon_status == 4) {
            $link_hash = '#nav-first-approved';
        } elseif ($check_coupon['info']->coupon_status == 5) {
            $link_hash = '#nav-approved';
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

            $pdf_path = $this->_generate_coupon_pdf($coupon_id, $coupon_scope_masking, $coupon_transaction_header_id);
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
		$payment_status     = ($payment_type_id == 4 || $payment_type_id == 7) ? 0 : 1; //* UNPAID WHEN CREDIT PAYMENT TYPE
		$customer_id    	= ($this->input->post('upd_customer_id') != NULL) ? clean_data(decode($this->input->post('upd_customer_id'))) : NULL;
		$voucher_value      = ($this->input->post('voucher-value') != NULL)? clean_data($this->input->post('voucher-value')) : 0;
		$voucher_regular_value      = ($this->input->post('voucher-regular-value') != NULL)? clean_data($this->input->post('voucher-regular-value')) : 0;
		$holder_address     = ($this->input->post('address') != NULL) ? clean_data($this->input->post('address')) : '';
        $holder_tin         = ($this->input->post('tin') != NULL) ? clean_data($this->input->post('tin')) : '';
        $name         		= ($this->input->post('name') != NULL) ? clean_data($this->input->post('name')) : '';
		
		if($customer_id){
			$customer_name = "";
			if(!is_numeric($customer_id)){
				$customer_name = clean_data(trim($this->input->post('upd_customer_id')));
			}
			$customer_id = $this->_validate_customer($customer_id, $customer_name);
		}
		
        $check_transaction = $this->main->check_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $transaction_id], TRUE);
        if ($check_transaction['result'] != TRUE) {
            $alert_message = $this->alert_template('Invalid '.SEC_SYS_NAME.' ID', FALSE);
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER']);
        }
		$coupon_scope_masking = $check_transaction['info']->coupon_scope_masking;
        
        $link_hash = '';
        if ($check_transaction['info']->coupon_transaction_header_status == 1) {
            $link_hash = '#nav-active';
        } elseif ($check_transaction['info']->coupon_transaction_header_status == 2) {
            $link_hash = '#nav-pending';
        } elseif ($check_transaction['info']->coupon_transaction_header_status == 5) {
            $link_hash = '#nav-approved';
        } elseif ($check_transaction['info']->coupon_transaction_header_status == 0) {
            $link_hash = '#nav-inactive';
        } elseif ($check_transaction['info']->coupon_transaction_header_status == 4) {
            $link_hash = '#nav-first-approved';
        }

        $rules = [
            [ 'field' => 'date_range'  , 'label' => ''.SEC_SYS_NAME.' Start & End' , 'rules' => 'required'                ],
        ];

        $this->_run_form_validation($rules);
        $category = $check_transaction['info']->coupon_cat_id;
        $for_printing    	= clean_data(decode($this->input->post('for_printing')));
        $for_image_conv    	= clean_data(decode($this->input->post('for_image_conv')));
        $dates    			= explode(' - ', clean_data($this->input->post('date_range')));
        $start    			= date('Y-m-d', strtotime($dates[0]));
        $end      			= date('Y-m-d', strtotime($dates[1]));

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
            'coupon_pdf_archived'    			=> 0,
            'coupon_transaction_header_name'   	=> strtoupper(trim($name)),
        ];
		if($customer_id){
			$set['customer_id']					= $customer_id;
		}

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
                    'coupon_start'          	=> $start,
                    'coupon_end'            	=> $end,
                    'payment_status'            => $payment_status,
					'coupon_value'          	=> $voucher_value,
					'coupon_regular_value'  	=> $voucher_regular_value,
					'coupon_holder_address' 	=> $holder_address,
					'coupon_holder_tin'     	=> $holder_tin,
					'coupon_name'          		=> strtoupper(trim($name)),
                ];

				if($customer_id){
					$set['customer_id']			= $customer_id;
				}

                $where = ['coupon_id' => $coupon_id]; 
                $result = $this->main->update_data('coupon_tbl', $set, $where);

				$set    = [
					'freebie_date'  => $start,
				];
				$where  = ['coupon_id' => $row->coupon_id];
				$this->main->update_data('survey_freebie_calendar_tbl', $set, $where);

                if ($result) {
                    $this->_store_coupon_action_log(2, $coupon_id);
                    $pdf_path = $this->_generate_coupon_pdf($coupon_id, $coupon_scope_masking, $transaction_id);
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
            redirect($_SERVER['HTTP_REFERER'].'#nav-active');
		}else{
			redirect('admin');
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

			$set    = [
				'survey_freebie_cal_status'  => 0
			];
			$where  = ['coupon_id' => $coupon_id];
			$result = $this->main->update_data('survey_freebie_calendar_tbl', $set, $where);
			
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
            $holder_type_where = (!in_array($check_id['info']->coupon_cat_id, paid_category())) ? 'coupon_holder_type_status = 1 AND coupon_holder_type_id != 4' : 'coupon_holder_type_status = 1';
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
                'company_tbl f'            => 'f.company_id = a.company_id',
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
            $holder_type_where = (!in_array($check_transaction['info']->coupon_cat_id, paid_category())) ? 'coupon_holder_type_status = 1 AND coupon_holder_type_id != 4' : 'coupon_holder_type_status = 1';
            $data['holder_type']  = $this->main->get_data('coupon_holder_type_tbl a', $holder_type_where);
            $data['category']     = $this->main->get_data('coupon_category_tbl a', ['coupon_cat_status' => 1]);
            $data['brand']        = $this->main->get_data("{$parent_db}.brand_tbl", ['brand_status' => 1]);
            $data['bc']           = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
            $data['coupon_type']  = $this->main->get_data('coupon_type_tbl', ['coupon_type_status' => 1]);
            $data['value_type']   = $this->main->get_data('coupon_value_type_tbl a', ['coupon_value_type_status' => 1]);
            
			$data['user_id'] = decode($info['user_id']);
            $data['customer_select'] = $this->_get_customers_selection($check_transaction['info']->customer_id, $check_transaction['info']->is_advance_order);
            $payment_select = $this->_get_payment_types_selection($check_transaction['info']->payment_type_id, NULL, $check_transaction['info']->is_advance_order);
			$data['payment_fields'] = $this->_get_payment_details_fields($check_transaction['info']->payment_type_id, $check_transaction['info']->payment_type_id, $check_transaction['info']->payment_terms, $payment_select);

            $dynamic_content = 'coupon/transaction_coupon_edit_modal_content';
            $html = $this->load->view($dynamic_content, $data, TRUE);

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
    
	public function modal_duplicate_transaction_coupon($id)
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
                'company_tbl f'            => 'f.company_id = a.company_id',
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

            $coupon = $this->main->get_join('coupon_tbl a', $coupon_join, TRUE, FALSE, FALSE, $coupon_select, false, false, false);

            $join_salable = [
                "{$parent_db}.product_tbl b" => 'a.prod_id = b.prod_id',
                "{$parent_db}.unit_tbl c"    => 'a.unit_id = c.unit_id',
                "{$parent_db}.unit_tbl d"    => 'a.2nd_uom = d.unit_id'
            ];

            $data['products']    = $this->main->get_join("{$parent_db}.product_sale_tbl a", $join_salable);
            $data['coupon']      = $coupon; 
            $data['transaction'] = $check_transaction['info'];

            
			$join_trans_dtls = [
				'coupon_transaction_details_tbl c' => 'a.coupon_id = c.coupon_id'
			];
			$filter = ['a.coupon_prod_sale_status' => 1, 'c.coupon_transaction_header_id' => $id];
			$data['coupon_prod']  = array_column($this->main->get_join("coupon_prod_sale_tbl a", $join_trans_dtls, FALSE, FALSE, 'prod_sale_id', 'prod_sale_id', $filter), 'prod_sale_id');

			$filter = ['a.coupon_brand_status' => 1, 'c.coupon_transaction_header_id' => $id];
            $data['coupon_brand'] = array_column($this->main->get_join("coupon_brand_tbl a", $join_trans_dtls, FALSE, FALSE, 'brand_id', 'brand_id', $filter), 'brand_id');
            
			$filter = ['a.coupon_bc_status' => 1, 'c.coupon_transaction_header_id' => $id];
            $data['coupon_bc']    = array_column($this->main->get_join("coupon_bc_tbl a", $join_trans_dtls, FALSE, FALSE, 'bc_id', 'bc_id', $filter), 'bc_id');

            $holder_type_where = (!in_array($check_transaction['info']->coupon_cat_id, paid_category())) ? 'coupon_holder_type_status = 1 AND coupon_holder_type_id != 4' : 'coupon_holder_type_status = 1';
            $data['holder_type']  = $this->main->get_data('coupon_holder_type_tbl a', $holder_type_where);
            $data['category']     = $this->main->get_data('coupon_category_tbl a', ['coupon_cat_status' => 1]);
            $data['brand']        = $this->main->get_data("{$parent_db}.brand_tbl", ['brand_status' => 1]);
            $data['bc']           = $this->main->get_data("{$parent_db}.bc_tbl", ['bc_status' => 1]);
            $data['coupon_type']  = $this->main->get_data('coupon_type_tbl', ['coupon_type_status' => 1]);
            $data['value_type']   = $this->main->get_data('coupon_value_type_tbl a', ['coupon_value_type_status' => 1]);
            
			$data['user_id'] = decode($info['user_id']);
			$data['bc_select'] = $this->_get_bc_selection($coupon->is_nationwide, $data['coupon_bc']);
			// pretty_dump($data['bc_select']);
			$data['brand_select'] = $this->_get_brands_selection($data['coupon_brand']);
			$data['value_type_select'] = $this->_get_value_types_selection([$coupon->coupon_value_type_id]);
			$data['products_select'] = $this->_get_products_selection($coupon->is_orc, $data['coupon_prod']);

            // $data['category_select'] = $this->_get_categories_selection($data['category']);
            // $data['holder_type_select'] = $this->_get_holder_types_selection($data['holder_type']);

            $data['customer_select'] = $this->_get_customers_selection($check_transaction['info']->customer_id, $check_transaction['info']->is_advance_order);
            $payment_select = $this->_get_payment_types_selection($check_transaction['info']->payment_type_id, NULL, $check_transaction['info']->is_advance_order);
			$data['payment_fields'] = $this->_get_payment_details_fields($check_transaction['info']->payment_type_id, $check_transaction['info']->payment_type_id, $check_transaction['info']->payment_terms, $payment_select);

            $dynamic_content = 'coupon/transaction_coupon_duplicate_modal_content';
            $html = $this->load->view($dynamic_content, $data, TRUE);

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

    private function _generate_coupon_pdf($coupon_id, $scope_masking="", $transaction_id=0, $display_exp=0)
    {
		
    	ini_set('max_execution_time', 0); 
        ini_set('memory_limit','2048M');
        $parent_db = $GLOBALS['parent_db'];
		$one_pager = true;

        $this->load->library('Pdf');

        $select = "a.*, a1.*, c.series_number, d.coupon_for_printing,
        IF(a.is_nationwide = 1, 
            'NATIONWIDE', 
            (SELECT GROUP_CONCAT(x.bc_name SEPARATOR ', ') FROM coupon_bc_tbl z JOIN {$parent_db}.bc_tbl x ON z.bc_id = x.bc_id WHERE z.coupon_id = a.coupon_id AND coupon_bc_status = 1)) AS 'bcs',
        IF(a.is_orc = 2, 'All Products',
        IF(a.is_orc = 1, 
            CONCAT_WS(', ', 'OVEN ROASTED CHICKEN',(SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1 AND z.prod_sale_id NOT IN (SELECT y.prod_sale_id FROM {$parent_db}.orc_list_tbl y WHERE y.orc_list_status = 1))), 
            (SELECT GROUP_CONCAT(x.prod_sale_name SEPARATOR ', ') FROM coupon_prod_sale_tbl z JOIN {$parent_db}.product_sale_tbl x ON z.prod_sale_id = x.prod_sale_id WHERE z.coupon_id = a.coupon_id AND coupon_prod_sale_status = 1))) AS 'products'";

        $join = array(
			'coupon_category_tbl a1' => 'a.coupon_cat_id = a1.coupon_cat_id AND a1.coupon_cat_status = 1 AND a.coupon_id = ' . $coupon_id,
			'coupon_transaction_details_tbl c' => 'c.coupon_id = a.coupon_id',
			'coupon_transaction_header_tbl d' => 'd.coupon_transaction_header_id = c.coupon_transaction_header_id'
		);

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


        if($coupon->coupon_cat_id == 1){ //* GIFT EVOUCHER

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



	    }elseif($coupon->coupon_cat_id == 4){ //* MEAL EVOUCHER

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


	    }elseif($coupon->coupon_cat_id == 2){ //* BIRTHDAY EVOUCHER

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
	    }elseif($coupon->coupon_cat_id == 3){ //* PAID EVOUCHER
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

	    }elseif($coupon->coupon_cat_id == 5){ //* ROASTED CHICKEN ECOUPON
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
			if($coupon->coupon_for_printing == 1){
				$design      = $coupon->coupon_cat_design;
				$custom_layout = [ 442, 226 ];
	
				$image_w = 432;
				$image_h = 216;
	
				$image_x_arr = [5];
				$image_y_arr = [5];
	
				$coupon_code_x_arr = [360];
				$coupon_code_y_arr = [20];
	
				$qr_x_arr = [376.5];
				$qr_y_arr = [82.5];
				
				$scope_x_arr = [286.5];
				$scope_y_arr = [199];
	
				$series_no_x_arr = [15];
				$series_no_y_arr = [14];

				$coupon_code_text_font = [32];
				$qr_code_size = [35];
			} else {
				$design      = $coupon->coupon_cat_digital_design;
				$custom_layout = [ 442, 226 ];
	
				$image_w = 432;
				$image_h = 216;
	
				$image_x_arr = [5];
				$image_y_arr = [5];
	
				$coupon_code_x_arr = [165];
				$coupon_code_y_arr = [88.5];
	
				$qr_x_arr = [163];
				$qr_y_arr = [33];
				
				$scope_x_arr = [288];
				$scope_y_arr = [201];
	
				$series_no_x_arr = [15];
				$series_no_y_arr = [14];

				$coupon_code_text_font = [27];
				$qr_code_size = [51];
			}

			$image_x = $image_x_arr[0];
			$coupon_code_x = $coupon_code_x_arr[0];
			$qr_x = $qr_x_arr[0];
			$scope_x = $scope_x_arr[0];
			$series_no_x = $series_no_x_arr[0];

			$image_y = $image_y_arr[0];
			$coupon_code_y = $coupon_code_y_arr[0];
			$qr_y = $qr_y_arr[0];
			$scope_y = $scope_y_arr[0];
			$series_no_y = $series_no_y_arr[0];

			$coupon_code_text_font = $coupon_code_text_font[0];
			$qr_code_size = $qr_code_size[0];

			$pdf->AddPage('L', $custom_layout);

			$pdf->setJPEGQuality(100);
			// $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
			$pdf->Image($design, $image_x, $image_y, $image_w, $image_h, 'JPG', '', '', true, 150, '', false, false, 0, true, false, false);
			if($coupon->series_number){
				$pdf->SetFont('helvetica', '', 20);
				$pdf->Text($series_no_x, $series_no_y, $coupon->series_number);
			}
			$pdf->SetFont('helvetica', '', $coupon_code_text_font);
			$pdf->Text($coupon_code_x, $coupon_code_y, $coupon->coupon_code);
			$pdf->write2DBarcode($coupon->coupon_code, 'QRCODE,H', $qr_x, $qr_y, $qr_code_size, $qr_code_size, $style, 'N');
			$pdf->SetFont('helvetica', '', 14);
			$pdf->Text($scope_x, $scope_y, $bcs);
			
	    }

        $cwd          = getcwd();
        $date_created = strtotime($coupon->coupon_added);

        $invalid_chars = [ '<' ,'>' ,':' ,'"' ,'/' ,'\\' ,'|' ,'?' ,'*', ' ', '[', ']', '\'', '(', ')' ];
        $cleaned_name  = str_replace($invalid_chars, '-', $coupon->coupon_name);
        $file_name     = $cleaned_name . '_' . $coupon->coupon_code . '_' . $date_created . '.pdf';
		$save_path     = '/assets/coupons/' . $file_name;
		if($transaction_id){
			$save_path     = '/assets/coupons/' .$transaction_id. '/'. $file_name;
			$folder_path     = FCPATH. '/assets/coupons/' .$transaction_id. '/';
			if (!is_dir($folder_path)) {
				mkdir($folder_path, 0777, true);
			}
		}

        $pdf->Output($cwd . $save_path, 'F');
        return $save_path;
    }
    
	private function _generate_coupon_pdf_backup($coupon_id)
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

		$image_x_arr = [40, 446.89];
		$image_y_arr = [17.5, 207.5, 397.5]; // 190 diff

		// $coupon_code_x_arr = [340, 741.89];
		// $coupon_code_y_arr = [30, 220, 410]; // 12.5 diff
		$coupon_code_x_arr = [333, 738.89];
		$coupon_code_y_arr = [29.5, 219.5, 409.5]; // 12.5 diff

		// $qr_y_arr = [85, 275, 465]; // 67.5 diff
		// $qr_x_arr = [344.5, 751.39];
		$qr_x_arr = [345.5, 752.39];
		$qr_y_arr = [78, 268, 458]; // 67.5 diff
		
		// $scope_y_arr = [180, 370, 560]; // 162.5 diff
		// $scope_x_arr = [275, 676.89];
		// $scope_y_arr = [179, 369, 559]; // 162.5 diff
		$scope_x_arr = [272, 679.89];
		$scope_y_arr = [177, 367, 557]; // 162.5 diff

		$series_no_x_arr = [48, 454.89];
		$series_no_y_arr = [25, 215, 405];

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

        $select = "a.*, a1.*, c.coupon_scope_masking, b.series_number,
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
				
				
				if($coupon->coupon_type_id == 1){ //* STANDARD COUPON
		
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
				}elseif($coupon->coupon_type_id == 2){ //* PRODUCT COUPON
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
					$series_no_x = $series_no_x_arr[0];
				} else {
					$image_x = $image_x_arr[1];
					$coupon_code_x = $coupon_code_x_arr[1];
					$qr_x = $qr_x_arr[1];
					$scope_x = $scope_x_arr[1];
					$series_no_x = $series_no_x_arr[1];
				}

				if($i < 3){ // first row
					$image_y = $image_y_arr[0];
					$coupon_code_y = $coupon_code_y_arr[0];
					$qr_y = $qr_y_arr[0];
					$scope_y = $scope_y_arr[0];
					$series_no_y = $series_no_y_arr[0];
				}elseif($i < 5){
					$image_y = $image_y_arr[1];
					$coupon_code_y = $coupon_code_y_arr[1];
					$qr_y = $qr_y_arr[1];
					$scope_y = $scope_y_arr[1];
					$series_no_y = $series_no_y_arr[1];
				}else{
					$image_y = $image_y_arr[2];
					$coupon_code_y = $coupon_code_y_arr[2];
					$qr_y = $qr_y_arr[2];
					$scope_y = $scope_y_arr[2];
					$series_no_y = $series_no_y_arr[2];
				}

				$pdf->setJPEGQuality(100);
				// $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252,238,33)));
				$image_width = 355;
				$image_height = 178;
				$pdf->Image($design, $image_x, $image_y, $image_width, $image_height, 'JPG', '', '', true, 150, '', false, false, 0, false, false, false);
				if($coupon->series_number){
					$pdf->SetFont('helvetica', '', 16);
					$pdf->Text($series_no_x, $series_no_y, $coupon->series_number);
				}
				$pdf->SetFont('helvetica', '', 26);
				$pdf->Text($coupon_code_x, $coupon_code_y, $coupon->coupon_code);
				$pdf->write2DBarcode($coupon->coupon_code, 'QRCODE,H', $qr_x, $qr_y, 31, 31, $style, 'N');
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

	public function check_php($id=0){
		phpinfo();
		// $id_array = [1, 2, 3];
		// if(in_array($id, $id_array)){
		// 	echo 'yes';
		// }
		// exit;
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

            $coupon_trans_select = "a.*, b.*, c.*, d.*, e.*, f.is_advance_order,
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
				'coupon_transaction_header_tbl f' => 'f.coupon_transaction_header_id = a.coupon_transaction_header_id'
            ];

            $data['trans_details'] = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join, FALSE, 'b.coupon_id DESC', FALSE, $coupon_trans_select);
            $data['trans_header']  = $check_id['info'];

			$max_series = $this->main->get_data('coupon_transaction_details_tbl', ['coupon_transaction_header_id' => $id], TRUE, 'MAX(series_number) AS max_series');
			$data['max_series'] = !empty($max_series) ? $max_series->max_series : 0;
			$min_series = $this->main->get_data('coupon_transaction_details_tbl', ['coupon_transaction_header_id' => $id], TRUE, 'MIN(series_number) AS min_series');
			$data['min_series'] = !empty($min_series) ? $min_series->min_series : 0;

            $data['controller'] = $this->controller;
			$dynamic_content = 'coupon/coupon_trans_modal_content';
            $html = $this->load->view($dynamic_content, $data, TRUE);

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

        $coupon_trans_select = "a.*, b.*, c.*, d.*, e.*, f.is_advance_order,
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
			'company_tbl e'				=> 'b.company_id = e.company_id',
			'coupon_transaction_header_tbl f' => 'f.coupon_transaction_header_id = a.coupon_transaction_header_id'
        ];

        $data['trans_details'] = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join, FALSE, 'b.coupon_id DESC', FALSE, $coupon_trans_select);

		$max_series = $this->main->get_data('coupon_transaction_details_tbl', ['coupon_transaction_header_id' => $id], TRUE, 'MAX(series_number) AS max_series');
		$data['max_series'] = !empty($max_series) ? $max_series->max_series : 0;
		$min_series = $this->main->get_data('coupon_transaction_details_tbl', ['coupon_transaction_header_id' => $id], TRUE, 'MIN(series_number) AS min_series');
		$data['min_series'] = !empty($min_series) ? $min_series->min_series : 0;

        $data['controller'] = $this->controller;
		$dynamic_content = 'coupon/success_product_coupon_trans_details';
        return $this->load->view($dynamic_content, $data, TRUE);

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

		$result = '';
        if ($email != '') {
            $result = $this->email->send();
            // echo $this->email->print_debugger();
            // die();
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
		$coupon_select = 'a.*, b.coupon_cat_name, d.coupon_scope_masking, d.coupon_transaction_header_id, d.coupon_transaction_header_added';
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
			$trans_hdr_details = 'ID: '.$check_coupon['info']->coupon_transaction_header_id.'<br>Creation Date: '.$check_coupon['info']->coupon_transaction_header_added.'<br>';

            $mobile = '';
            if($use < $coupon_qty){
                if($today_date <= $coupon_end){//Check coupon if expired
                    if($today_date >= $coupon_start){//Check coupon if redeemd date is started

                        if($coupon_type == 1){  //* STANDARD EVOUCHER
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
                        }elseif($coupon_type == 2){ //* PRODUCT E-VOUCHER
                            if($value_type == 1){ // For percentage Discount                        
                                if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide
                                    if($check_coupon['info']->is_orc == 1){ // CHECK IF ORC ONLY
                                        if($check_coupon['info']->coupon_amount == 100){
                                            
											$amount_product = '1 ORC';
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                        }else{
                                            
											$amount_product = 'worth ' . $amount . '% discount ng ORC';
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                        }
                                    }else{
                                        $prod = $this->_get_prod($coupon_id);
                                        if($check_coupon['info']->coupon_amount == 100){
                                            
											$amount_product = '1 '.$prod;
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                        }else{
                                            
											$amount_product = 'worth ' . $amount . '% discount ng ' . $prod;
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                        }
                                    }
                                }else{ //Find valid BC
                                    
                                    // $bc = $this->_get_bc($coupon_id);
									$bc = $scope_masking == '' ? $this->_get_bc($coupon_id) : $scope_masking;

                                    if($check_coupon['info']->is_orc == 1){
                                        if($check_coupon['info']->coupon_amount == 100){
                                            
											$amount_product = '1 ORC';
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, $bc, $trans_hdr_details);
                                        }else{
                                            
											$amount_product = 'worth ' . $amount . '% discount ng ORC';
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, $bc, $trans_hdr_details);
                                        }
                                    }else{
                                        $prod = $this->_get_prod($coupon_id);
                                        if($check_coupon['info']->coupon_amount == 100){
                                            
											$amount_product = '1 '.$prod;
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, $bc, $trans_hdr_details);
                                        }else{
                                            
											$amount_product = 'worth ' . $amount . '% discount ng ' . $prod;
											$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, $bc, $trans_hdr_details);
                                        }
                                    }
                                }
                            }elseif($value_type == 2){  // Flat amount Discount
                               

                                if($check_coupon['info']->is_nationwide == 1){
                                    if($check_coupon['info']->is_orc == 1){
                                        
										$amount_product = $amount . ' discount para sa ORC';
										$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                    }else{
                                        $prod = $this->_get_prod($coupon_id);
                                        
										$amount_product = $amount . ' discount para sa ' . $prod;
										$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                    }
                                }else{ //Find valid BC
                                    // $bc = $this->_get_bc($coupon_id);
									$bc = $scope_masking == '' ? $this->_get_bc($coupon_id) : $scope_masking;

                                    if($check_coupon['info']->is_orc == 1){
                                        
										$amount_product = $amount . ' discount para sa ORC';
										$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, $bc, $trans_hdr_details);
                                    }else{
                                        $prod = $this->_get_prod($coupon_id);
                                        
										$amount_product = $amount . ' discount para sa ' . $prod;
										$sms = $this->_response_msg($value_type, $category, $reference_no=0, $amount_product, $bc, $trans_hdr_details);
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
                        // $sms = 'Sorry '.SYS_NAME.' redemption has not yet started. Redemption start on ' . $coupon_start . '.';
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

		$coupon_select = 'a.*, b.coupon_cat_name, d.coupon_scope_masking, d.coupon_transaction_header_id, d.coupon_transaction_header_added';
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
			// $trans_hdr_details = '[ '.$check_coupon['info']->coupon_transaction_header_id.' - '.$check_coupon['info']->coupon_transaction_header_added.' ] ';
			$trans_hdr_details = '';

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
                                    $send_sms = send_sms($mobile, $sms, 'BAVI-TEST4321', 'CHOOKS');

                                    $set_outgoing = array(
                                        'redeem_outgoing_sms' => $sms,
                                        'redeem_outgoing_no' => $mobile,
                                        'redeem_outgoing_response' => $send_sms,
                                        'redeem_outgoing_added' => date_now(),
                                        'redeem_outgoing_status' => 1
                                    );

                                    $insert_outgoing = $this->main->insert_data('redeem_outgoing_tbl', $set_outgoing, TRUE);
                                }elseif($coupon_type == 2){ //* PRODUCT COUPON
                                    if($value_type == 1){ // For percentage Discount
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
                                        }else{ //Find valid BC
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
                                    }elseif($value_type == 2){ //Flat amount Discount
                                        if($check_coupon['info']->is_nationwide == 1){ //Check is nationwide
                                            if($check_coupon['info']->is_orc == 1){ // check if ORC only
												$amount_product = $amount . ' discount para sa ORC';
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                            }else{
												$prod = $this->_get_prod($coupon_id);
												$amount_product = $amount . ' discount para sa ' . $prod;
												$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details);
                                            }
                                        }else{ //Find valid BC
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

			if($coupon_cat_id >= 7){
				$msg = 'Voucher/Coupon category is not allowed in your redeem access.';
				$response_data = array(
					'result'  => 0,
					'html' => $this->alert_template($msg, FALSE)
				);
				echo json_encode($response_data);
				exit;
			}

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
                    if($today_date > $coupon_start){//Check coupon if redeemd date is started

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
		
		if(empty($added_info)){
			$msg = 'Customer Name must not be blank.';
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
				$msg = 'Voucher/Coupon category is not allowed in your redeem access.';
				$response_data = array(
					'result'  => 0,
					'html' => $this->alert_template($msg, FALSE)
				);
				echo json_encode($response_data);
				exit;
			}

			//* LINK THE COUPON TO PROMO WINNER
			$join = array(
				'survey_reference_tbl b' => 'a.survey_ref_id = b.survey_ref_id'
			);
			$promo_winner = $this->main->get_join('survey_winners_tbl a', $join, true, false, false, 'b.*', ['a.coupon_id' => $coupon_id, 'a.survey_winner_status' => 1, 'a.survey_winner_validated' => 1, 'b.status' => 1]);
			$winner_name = !empty($promo_winner) ? $promo_winner->name : '';
			if(!empty($winner_name)){
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
														$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details, $winner_name);
													}else{
														$amount_product = 'worth ' . $amount . '% discount ng ORC';
														$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details, $winner_name);
													}
												}else{
													$prod = $this->_get_prod($coupon_id);
													if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
														$amount_product = '1 '.$prod;
														$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details, $winner_name);
													}else{
														$amount_product = 'worth ' . $amount . '% discount ng ' . $prod;
														$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details, $winner_name);
													}
												}
											}else{ //* Find valid BC
												$bc = $scope_masking == '' ? $this->_get_bc($coupon_id) : $scope_masking;
												if($check_coupon['info']->is_orc == 1){ // check if ORC only
													if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
														$amount_product = '1 ORC';
														$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details, $winner_name);
													}else{
														$amount_product = 'worth ' . $amount . '% discount ng ORC';
														$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details);
													}
												}else{
													$prod = $this->_get_prod($coupon_id);
													if($check_coupon['info']->coupon_amount == 100){ // Check if full percentage
														$amount_product = '1 '.$prod;
														$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details, $winner_name);
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
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details, $winner_name);
												}else{
													$prod = $this->_get_prod($coupon_id);
													$amount_product = $amount . ' discount para sa ' . $prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, 'NATIONWIDE', $trans_hdr_details, $winner_name);
												}
											}else{ //* Find valid BC
												$bc = $scope_masking == '' ? $this->_get_bc($coupon_id) : $scope_masking;
												if($check_coupon['info']->is_orc == 1){ // check if ORC only
													$amount_product = $amount . ' discount para sa ORC';
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details, $winner_name);
												}else{
													$prod = $this->_get_prod($coupon_id);
													$amount_product = $amount . ' discount para sa ' . $prod;
													$sms = $this->_response_msg($value_type, $category, $reference_no, $amount_product, $bc, $trans_hdr_details, $winner_name);
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
					
				}
			} else {
				//* promo winner invalid

				$result = 0;
				$params = [
					'type' 							=> 'promo_winner_invalid',
					'coupon_code' 					=> $coupon_code,
				];
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

	function _response_msg($value_type, $category, $reference_no, $amount_product, $location, $trans_hdr_details='', $winner_name = ''){
		if($value_type == 1){ // PERCENTAGE
			$old_location = $location == 'NATIONWIDE' ? $location : 'sa '.$location;
			$old_sms = 'Ang '.SYS_NAME.' mo ay valid ng '.$amount_product .' at valid '.$old_location.'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
			
			$sms = $trans_hdr_details.'Ang '. $category . ' ay valid para sa '.$amount_product .' with '.$location.' scope. Maaari mo ng itransact sa POS VOUCHER MODULE ang approval code na <strong>' . $reference_no.'</strong>.';
			if($category == "CHOOKSIE QR PROMO EVOUCHER"){
				$sms = 'Ang '. $category . ' ay valid para sa '.$amount_product .' with '.$location.' scope. Ang promo winner nito ay si <strong>'.strtoupper($winner_name).'</strong>. Ito ay may approval code na <strong>' . $reference_no.'</strong>.';
			}
		} elseif ($value_type == 2){ // FLAT AMOUNT
			$old_location = $location == 'NATIONWIDE' ? $location : 'sa '.$location;
			$old_sms = 'Ang '.SYS_NAME.' mo ay valid worth P' . $amount_product . ' at valid sa '.$old_location.'. Ito ay '. $category . '. Maari mo nang iinput sa POS ang approval code ' . $reference_no;
			
			$sms = $trans_hdr_details.'Ang '. $category . ' ay valid worth P' . $amount_product . ' at '.$location.' scope. Maari mo nang iinput sa POS VOUCHER MODULE ang approval code na <strong>' . $reference_no.'</strong>.';
			
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
		elseif($params['type'] == 'promo_winner_invalid'){
			$sms = 'Sorry, ang '.SEC_SYS_NAME.' CODE na ito ay wala pang promo winner na naideklara. I-check ang '.SEC_SYS_NAME.' code at siguraduhing tama ang nai-type na code. Subukang i-redeem ulet.';
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
		$get_record = $this->main->get_join('crew a', $join_bc, true, false, false, $select, false, FALSE);

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

			$msg    = ($result == TRUE) ? '<div class="alert alert-success">'.SEC_SYS_NAME.' successfully Approved.</div>' : '<div class="alert alert-danger">Error please try again!</div>';
            $this->session->set_flashdata('message', $msg);
            redirect($_SERVER['HTTP_REFERER'].'#nav-approved');
		}else{
			redirect('admin');
		}
    }

	public function publish_transaction(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_transaction_header_id = clean_data(decode($this->input->post('id')));

			$status = 1; //* ACTIVE

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

            if ($check_id['info']->coupon_transaction_header_status == $status) {
                $alert_message = $this->alert_template('Transaction Header is already Published', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $this->db->trans_start();

			$set       = [
                'coupon_transaction_header_status' => $status,
            ];
			$where     = ['coupon_transaction_header_id' => $coupon_transaction_header_id];
			$result    = $this->main->update_data('coupon_transaction_header_tbl', $set, $where);
			
			$action_id = 3;
            $this->_store_transaction_action_log($action_id, $coupon_transaction_header_id);

            if ($result == TRUE) {
                $coupon_join = [
                    'coupon_tbl b'            => 'b.coupon_id = a.coupon_id',
                    'coupon_value_type_tbl c' => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $coupon_transaction_header_id
                ];

                $transaction_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join);
                foreach ($transaction_details as $row) {
                    $set    = [
                        'coupon_status'  => $status
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);
                    
					$set    = [
                        'survey_freebie_cal_status'  => $status
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('survey_freebie_calendar_tbl', $set, $where);
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
            redirect($_SERVER['HTTP_REFERER'].'#nav-active');
		}else{
			redirect('admin');
		}
    }

	public function approve_transaction(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_transaction_header_id = clean_data(decode($this->input->post('id')));

			$status = 5;

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

            if ($check_id['info']->coupon_transaction_header_status == $status) {
                $alert_message = $this->alert_template('Transaction Header is already Approve', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }


			if(in_array($check_id['info']->coupon_cat_id, paid_category()) && $check_id['info']->is_advance_order == 0){
				$this->_validate_attachment('#nav-first-approved');

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
                'coupon_transaction_header_status' => $status,
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
                        'coupon_status'  => $status,
                        'invoice_number' => $invoice_number,
                        'sap_doc_no'     => $sap_doc_no,
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);
                    
					$set    = [
                        'survey_freebie_cal_status'  => $status
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('survey_freebie_calendar_tbl', $set, $where);
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
                $message       = 'Transaction successfully Approved.';
                $alert_message = $this->alert_template($message, TRUE);
            }
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER'].'#nav-approved');
		}else{
			redirect('admin');
		}
    }
	
	public function update_approve_transaction(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_transaction_header_id = clean_data(decode($this->input->post('id')));
			$status = 5; //* FINANCE APPROVED

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

            
			
			if(in_array($check_id['info']->coupon_cat_id, paid_category()) && $check_id['info']->is_advance_order == 0){
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
                'coupon_transaction_header_status' => $status,
                'invoice_number'                   => $invoice_number,
                'sap_doc_no'                       => $sap_doc_no,
            ];
			$where     = ['coupon_transaction_header_id' => $coupon_transaction_header_id];
			$result    = $this->main->update_data('coupon_transaction_header_tbl', $set, $where);
			$action_id = 11 ; //* UPDATE SECOND LEVEL APPROVAL INPUTS
			
			
            $this->_store_transaction_action_log($action_id, $coupon_transaction_header_id);

            if ($result == TRUE) {
                $coupon_join = [
                    'coupon_tbl b'            => 'b.coupon_id = a.coupon_id',
                    'coupon_value_type_tbl c' => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $coupon_transaction_header_id
                ];

                $transaction_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join);
                foreach ($transaction_details as $row) {
                    $set    = [
                        'coupon_status'  => $status,
                        'invoice_number' => $invoice_number,
                        'sap_doc_no'     => $sap_doc_no,
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);

					$set    = [
                        'survey_freebie_cal_status'  => $status
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('survey_freebie_calendar_tbl', $set, $where);
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
                $message       = 'Transaction successfully updated.';
                $alert_message = $this->alert_template($message, TRUE);
            }
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER'].'#nav-approved');
		}else{
			redirect('admin');
		}
    }
	
	public function first_approve_transaction(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_transaction_header_id = clean_data(decode($this->input->post('id')));

			// echo '<pre>';
			// print_r($_POST);
			// echo '</pre>';
			// exit;
			// $this->_validate_attachment('#nav-pending');

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
			
			$category = $check_id['info']->coupon_cat_id;
			$status = 4; //* first-approved if not qr promo voucher
			$tab_page = '#nav-first-approved';
			if($category == 6 || $category == 7){ //* QR PROMO VOUCHER
				$tab_page = '#nav-pending';
				$status = 1; //* auto publish if qr promo voucher
			}

            if ($check_id['info']->coupon_transaction_header_status == $status) {
                $alert_message = $this->alert_template('Transaction Header is already Approved in Treasury', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

			
			
			if(in_array($check_id['info']->coupon_cat_id, paid_category())){
                $payment_terms =  clean_data($this->input->post('payment_terms')) ? clean_data($this->input->post('payment_terms')) : 0;
                $payment_type_id     =  $this->input->post('payment_type_id') ? clean_data( decode($this->input->post('payment_type_id'))) : 0;
            } else {

                $payment_terms =  0;
                $payment_type_id     =  0;
            }

			$payment_status     = ($payment_type_id == 4 || $payment_type_id == 7) ? 0 : 1; //* UNPAID WHEN CREDIT PAYMENT TYPE
			
            $this->db->trans_start();

			$set       = [
                'coupon_transaction_header_status' 		=> $status,
                'payment_terms'                   		=> $payment_terms,
                'payment_type_id'                       => $payment_type_id,
				'payment_status'                       	=> $payment_status,
            ];
			if(!$payment_terms) unset($set['payment_terms']);
			if(!$payment_type_id) unset($set['payment_type_id']);

			$where     = ['coupon_transaction_header_id' => $coupon_transaction_header_id];
			$result    = $this->main->update_data('coupon_transaction_header_tbl', $set, $where);
			if($category == 6 || $category == 7){
				$action_id = 5; //* APPROVE QR PROMO VOUCHER
			} else {
				$action_id = ($check_id['info']->coupon_transaction_header_status == 0) ? 3 : 7 ;
			}
			
			
            $this->_store_transaction_action_log($action_id, $coupon_transaction_header_id);

            if ($result == TRUE) {
                $coupon_join = [
                    'coupon_tbl b'            => 'b.coupon_id = a.coupon_id',
                    'coupon_value_type_tbl c' => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $coupon_transaction_header_id
                ];

                $transaction_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join);
                foreach ($transaction_details as $row) {
                    $set    = [
                        'coupon_status'  			=> $status,
						'payment_status'            => $payment_status,
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);

					if($category == 6 || $category == 7){
						$set    = [
							'survey_freebie_cal_status'  => 1
						];
						$where  = ['coupon_id' => $row->coupon_id];
						$result = $this->main->update_data('survey_freebie_calendar_tbl', $set, $where);
					}
                }

                if ($action_id == 7) {
					
					$name = $check_id['info']->coupon_transaction_header_name;
					$check_category = $this->main->check_data('coupon_category_tbl', array('coupon_cat_id' => $category, 'coupon_cat_status' => 1), TRUE);
					$category_name = '';
					if($check_category['result'] == TRUE){
						$category_name = $check_category['info']->coupon_cat_name;
					}
                    // $this->_send_transaction_creator_notification($coupon_transaction_header_id);
					$this->_send_approver_notification($name, $category, $category_name);
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
                $message       = 'Transaction successfully Approved.';
                $alert_message = $this->alert_template($message, TRUE);
            }
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER'].$tab_page);
		}else{
			redirect('admin');
		}
    }
	
	public function update_first_approve_transaction(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_transaction_header_id = clean_data(decode($this->input->post('id')));
			$status = 4; //* first-approved

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
			
			if(in_array($check_id['info']->coupon_cat_id, paid_category())){
                $payment_terms =  clean_data($this->input->post('payment_terms')) ? clean_data($this->input->post('payment_terms')) : 0;
                $payment_type_id     =  clean_data( decode($this->input->post('payment_type_id')));
            } else {

                $payment_terms =  0;
                $payment_type_id     =  0;
            }

			$payment_status     = ($payment_type_id == 4 || $payment_type_id == 7) ? 0 : 1; //* UNPAID WHEN CREDIT PAYMENT TYPE

            $this->db->trans_start();

			$set       = [
                'coupon_transaction_header_status' 		=> $status,
                'payment_terms'                   		=> $payment_terms,
                'payment_type_id'                       => $payment_type_id,
                'payment_status'                       	=> $payment_status,
            ];
			$where     = ['coupon_transaction_header_id' => $coupon_transaction_header_id];
			$result    = $this->main->update_data('coupon_transaction_header_tbl', $set, $where);
			$action_id = 10 ; //* UPDATE FIRST LEVEL APPROVAL INPUTS
			
			
            $this->_store_transaction_action_log($action_id, $coupon_transaction_header_id);
			
			
            if ($result == TRUE) {
				$coupon_join = [
                    'coupon_tbl b'            => 'b.coupon_id = a.coupon_id',
                    'coupon_value_type_tbl c' => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $coupon_transaction_header_id
                ];

                $transaction_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join);
                foreach ($transaction_details as $row) {
                    $set    = [
                        'coupon_status'  			=> $status,
						'payment_status'            => $payment_status,
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);
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
                $message       = 'Transaction successfully updated.';
                $alert_message = $this->alert_template($message, TRUE);
            }
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER'].'#nav-first-approved');
		}else{
			redirect('admin');
		}
    }
	
	public function return_to_pending_transaction(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_transaction_header_id = clean_data(decode($this->input->post('id')));
			$status = 2; //* PENDING

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

            if ($check_id['info']->coupon_transaction_header_status == $status) {
                $alert_message = $this->alert_template('Transaction is already in pending', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }
			
			
			$payment_terms =  0;
			$payment_type_id     =  0;


            $this->db->trans_start();

			$set       = [
                'coupon_transaction_header_status' 		=> $status,
                // 'payment_terms'                   		=> $payment_terms,
                // 'payment_type_id'                       => $payment_type_id,
            ];
			$where     = ['coupon_transaction_header_id' => $coupon_transaction_header_id];
			$result    = $this->main->update_data('coupon_transaction_header_tbl', $set, $where);
			$action_id = 8; //* back to pending
			
			
            $this->_store_transaction_action_log($action_id, $coupon_transaction_header_id);

			if ($result == TRUE) {
                $coupon_join = [
                    'coupon_tbl b'            => 'b.coupon_id = a.coupon_id',
                    'coupon_value_type_tbl c' => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $coupon_transaction_header_id
                ];

                $transaction_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join);
                foreach ($transaction_details as $row) {
                    $set    = [
                        'coupon_status'  => $status
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);
                }

				$this->_upload_transaction_attachment($coupon_transaction_header_id, 2);
				
            }

            if($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                $message       = 'Error please try again!';
                $alert_message = $this->alert_template($message, FALSE);
            }else{
                $this->db->trans_commit();
                $message       = 'Transaction successfully returned to pending.';
                $alert_message = $this->alert_template($message, TRUE);
            }
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER'].'#nav-pending');
		}else{
			redirect('admin');
		}
    }
	
	public function return_to_first_approve_transaction(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_transaction_header_id = clean_data(decode($this->input->post('id')));
			$status = 4; //* FIRST LEVEL APPROVED

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

            if ($check_id['info']->coupon_transaction_header_status == $status) {
                $alert_message = $this->alert_template('Transaction is already in treasury approved', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }
			
			
			$invoice_number =  '';
            $sap_doc_no     =  '';


            $this->db->trans_start();

			$set       = [
                'coupon_transaction_header_status' 		=> $status,
                'invoice_number'                   		=> $invoice_number,
                'sap_doc_no'                       		=> $sap_doc_no,
            ];
			$where     = ['coupon_transaction_header_id' => $coupon_transaction_header_id];
			$result    = $this->main->update_data('coupon_transaction_header_tbl', $set, $where);
			$action_id = 9; //* BACK TO FIRST LEVEL APPROVE
			
			
            $this->_store_transaction_action_log($action_id, $coupon_transaction_header_id);

			if ($result == TRUE) {
                $coupon_join = [
                    'coupon_tbl b'            => 'b.coupon_id = a.coupon_id',
                    'coupon_value_type_tbl c' => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $coupon_transaction_header_id
                ];

                $transaction_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join);
                foreach ($transaction_details as $row) {
                    $set    = [
                        'coupon_status'  => $status,
                        'invoice_number' => $invoice_number,
                        'sap_doc_no'     => $sap_doc_no,
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);
                }

				$this->_upload_transaction_attachment($coupon_transaction_header_id, 3);
            }

            if($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                $message       = 'Error please try again!';
                $alert_message = $this->alert_template($message, FALSE);
            }else{
                $this->db->trans_commit();
                $message       = 'Transaction successfully returned to treasury approved.';
                $alert_message = $this->alert_template($message, TRUE);
            }
            $this->session->set_flashdata('message', $alert_message);
            redirect($_SERVER['HTTP_REFERER'].'#nav-first-approved');
		}else{
			redirect('admin');
		}
    }
	
	public function return_to_approve_transaction(){
		$info = $this->_require_login();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $coupon_transaction_header_id = clean_data(decode($this->input->post('id')));
			$status = 5; //* SECOND LEVEL APPROVED

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

            if ($check_id['info']->coupon_transaction_header_status == $status) {
                $alert_message = $this->alert_template('Transaction is already in finance approved', false);
                $this->session->set_flashdata('message', $alert_message);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $this->db->trans_start();

			$set       = [
                'coupon_transaction_header_status' 		=> $status,
            ];
			$where     = ['coupon_transaction_header_id' => $coupon_transaction_header_id];
			$result    = $this->main->update_data('coupon_transaction_header_tbl', $set, $where);
			
			$action_id = 12; //* BACK TO SECOND LEVEL APPROVE
            $this->_store_transaction_action_log($action_id, $coupon_transaction_header_id);

			if ($result == TRUE) {
                $coupon_join = [
                    'coupon_tbl b'            => 'b.coupon_id = a.coupon_id',
                    'coupon_value_type_tbl c' => 'c.coupon_value_type_id = b.coupon_value_type_id AND coupon_transaction_header_id = ' . $coupon_transaction_header_id
                ];

                $transaction_details = $this->main->get_join('coupon_transaction_details_tbl a', $coupon_join);
                foreach ($transaction_details as $row) {
                    $set    = [
                        'coupon_status'  => $status
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);
                }

				$this->_upload_transaction_attachment($coupon_transaction_header_id, 3);
            }

            if($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                $message       = 'Error please try again!';
                $alert_message = $this->alert_template($message, FALSE);
            }else{
                $this->db->trans_commit();
                $message       = 'Transaction successfully returned to finance approved.';
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

					$set    = [
                        'survey_freebie_cal_status'  => 0
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('survey_freebie_calendar_tbl', $set, $where);
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

	public function _get_products_selection($is_orc=NULL, $product_id=NULL){
		$parent_db = $GLOBALS['parent_db'];
		$filter = ['prod_sale_status_id' => 1];
		$join_salable = [
			"{$parent_db}.product_tbl b" => 'a.prod_id = b.prod_id AND a.prod_id IN (1, 7, 21, 27) and a.company_id = 2 and a.prod_sale_status_id = 1',
			// "{$parent_db}.product_tbl b" => 'a.prod_id = b.prod_id',
        ];

		$product_list         = $this->main->get_join("{$parent_db}.product_sale_tbl a", $join_salable);
		$product_select = '<option value="">Select Product</option>';
		if($is_orc == 1) $selected = "selected";
		else $selected = "";
		$product_select .= '<option '.$selected.' value="orc">ORC</option>';
		foreach ($product_list as $row) {
			$selected = '';
			if($is_orc != 1 && is_array($product_id) && in_array($row->prod_sale_id, $product_id)) $selected = 'selected';
			$product_select .= '<option '.$selected.' value="'.encode($row->prod_sale_id).'">'.$row->prod_sale_code.' - '.$row->prod_sale_name.'</option>';
		}
		return $product_select;
	}
	
	public function _get_bc_selection($is_nationwide=NULL, $bc_id=NULL){
		$parent_db = $GLOBALS['parent_db'];
		$filter = ['bc_status' => 1, 'bc_id <' => 22];
		// if($bc_id){
		// 	if (is_array($bc_id)) {
		// 		$filter = ['bc_id IN (' . implode(',', $bc_id) . ') and bc_status = 1' ];
		// 	} else {
		// 		$filter = ['bc_id' => $bc_id];
		// 	}
		// }
		$bc_list         = $this->main->get_data("{$parent_db}.bc_tbl", $filter, false, 'bc_id, bc_name');
		// return $bc_id;
		$bc_select = '<option value="">Select Business Center</option>';
		if($is_nationwide == 1) $selected = "selected";
		else $selected = "";
		$bc_select .= '<option '.$selected.' value="nationwide">NATIONWIDE</option>';
		foreach ($bc_list as $row) {
			$selected = '';
			if($is_nationwide != 1 && is_array($bc_id) && in_array($row->bc_id, $bc_id)) $selected = 'selected';
			$bc_select .= '<option '.$selected.' value="'.encode($row->bc_id).'">'.$row->bc_name.'</option>';
		}
		return $bc_select;
	}

	public function _get_value_types_selection($value_type_id=NULL){
		
		$filter = ['coupon_value_type_status' => 1];

		$value_type_list         = $this->main->get_data("coupon_value_type_tbl", $filter);
		$value_type_select = '<option value="">Select Value Type</option>';
		foreach ($value_type_list as $row) {
			$selected = '';
			if(is_array($value_type_id) && in_array($row->coupon_value_type_id, $value_type_id)) $selected = 'selected';
			$value_type_select .= '<option '.$selected.' value="'.encode($row->coupon_value_type_id).'">'.$row->coupon_value_type_name.'</option>';
		}
		return $value_type_select;
	}

	public function _get_brands_selection($brand_id=NULL){
		$parent_db = $GLOBALS['parent_db'];
		$filter = ['brand_status' => 1];
		
		$brand_list         = $this->main->get_data("{$parent_db}.brand_tbl", $filter);
		$brand_select = '<option value="">Select Brand</option>';
		foreach ($brand_list as $row) {
			$selected = '';
			if(is_array($brand_id) && in_array($row->brand_id, $brand_id)) $selected = 'selected';
			$brand_select .= '<option '.$selected.' value="'.encode($row->brand_id).'">'.$row->brand_name.'</option>';
		}
		return $brand_select;
	}

	public function _get_customers_selection($customer_id=NULL, $is_advance_order=0){
		
		$filter = ['customer_status' => 1];
		if($is_advance_order){
			$filter = ['customer_id' => $customer_id];
		}
		$customers         = $this->main->get_data('customers_tbl a', $filter);
		$customer_select = '<option value="">Select Customer</option>';
		foreach ($customers as $row) {
			$selected = '';
			
			if($customer_id == $row->customer_id) $selected = 'selected';
			$customer_select .= '<option '.$selected.' value="'.encode($row->customer_id).'">'.$row->customer_name.'</option>';
		}

		return $customer_select;
	}

	public function _get_payment_types_selection($payment_type_id=NULL, $rec_payment_type_id=NULL, $is_advance_order=0){
		$where               = ['payment_type_status' => 1];
		if($is_advance_order){
			if($payment_type_id){
				$where               = ['payment_type_id' => $payment_type_id];
			}
			if($rec_payment_type_id){
				$where               = ['payment_type_id' => $rec_payment_type_id];
			}
		}
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
			$company_filter = ['company_status' => 1];
			if ($coupon_cat == '1' || $coupon_cat == '4') { // GIFT VOUCHER, MEAL EVOUCHER, QR PROMO		
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

            if ($coupon_cat == '1' || $coupon_cat == '4') { //* GIFT VOUCHER, MEAL EVOUCHER
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
            } elseif(in_array($coupon_cat, paid_category())){ //* PAID VOUCHER
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

            } else if($coupon_cat == 6 || $coupon_cat == 7){ //*  QR PROMO
				$result = '
						<div class="form-group">
							<div class="form-check">
								<input type="checkbox" name="allocate_to_each_bc" class="form-check-input" id="allocate_to_each_bc" value="1">
								<label class="form-check-label" for="allocate_to_each_bc">Allocate Qty to BC selected</label>
							</div>
						</div>
						<div class="form-group">
							<label for="">Allocation Qty per BC :</label>
							<input type="number" name="allocation_count" class="form-control form-control-sm" placeholder="">
						</div>

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
		if(!empty($_FILES)){
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
                            <input type="file" class="custom-file-input" accept="image/png, image/jpeg, image/jpg, document/pdf" data-show-upload="false" data-show-caption="true" name="attachment[]" multiple required>
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
			if(in_array($check_coupon['info']->coupon_cat_id, paid_category()) && $check_coupon['info']->is_advance_order == 0){
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
                            <input type="text" name="invoice_num" value="'.$coupon->invoice_number.'" class="form-control form-control-sm" value="">
                        </div>
                        <div class="form-group">
                            <label for="">Doc No:</label>
                            <input type="text" name="sap_doc_no" value="'.$coupon->sap_doc_no.'" class="form-control form-control-sm" value="" required>
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
                            <input type="text" name="invoice_num" value="'.$coupon->invoice_number.'" class="form-control form-control-sm" value="" required>
                        </div>
                        <div class="form-group">
                            <label for="">Doc No:</label>
                            <input type="text" name="sap_doc_no" value="'.$coupon->sap_doc_no.'" class="form-control form-control-sm" value="" required>
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

    public function view_approver_email(){
        
        $url     = base_url();
        $subject = ''.SYS_NAME.' New Request Notification';

        $data['title'] = ''.SYS_NAME.' New Request';
        $data['name'] =  'Jonel';
        $data['voucher']  = '3x3 eVoucher';
        $data['type'] = 'Gift eVoucher';

        $message = $this->load->view('email/email_approver_content', $data);
    }

    public function view_creator_email(){
        
        $url     = base_url();
        $subject = ''.SYS_NAME.' Approved Notification';

        $data['title'] = ''.SYS_NAME.' Request Approved';
        $data['name'] =  'Jonel';
        $data['voucher']  = '3x3 eVoucher';
        $data['type'] = 'Gift eVoucher';

        $message = $this->load->view('email/email_creator_content', $data);
    }

	public function test_email(){
		$recipient = 'akatok@chookstogoinc.com.ph';
		$subject = 'Test';
		$message = 'Test Message';
		$this->_send_email($recipient, $subject, $message);
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
                    // ->from('alerts@bountyagro.com.ph', ''.SYS_NAME.' System Notification')
                    ->from('noreply@chookstogoinc.com.ph', ''.SYS_NAME.' System Notification')
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
            // exit;
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
					'customer_name' 			=> strtoupper($name),
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

    private function _validate_holder_type($holder_type)
    {
        $parent_db         = $GLOBALS['parent_db'];
        $check_holder_type = $this->main->check_data('coupon_holder_type_tbl', ['coupon_holder_type_id' => $holder_type]);
        $this->_validate_result(!$check_holder_type, 'Holder Type Doesn\'t Exist ('.$holder_type.')');
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

			$html = $this->_get_transaction_coupon_attachment($id, TRUE);
            
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

	public function _get_transaction_coupon_attachment($id, $history_logs = FALSE){
		
		$parent_db = $GLOBALS['parent_db'];

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

		if($history_logs){
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
		}

		return $html;
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


    private function _send_transaction_creator_notification($transaction_id) 
    {
		$info = $this->_require_login();
		$user_id = decode($info['user_id']);
        $parent_db   = $GLOBALS['parent_db'];
        $transaction = $this->main->get_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $transaction_id], TRUE);
        $creator     = $this->main->get_data("{$parent_db}.user_tbl", ['user_id' => $transaction->user_id], TRUE);
		if($user_id != $transaction->user_id){
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
        

        $data['title']						= SEC_SYS_NAME.' Summary';
		$data['top_nav']     				= $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content']					= $this->load->view('admin/coupon/coupon_summary_content', $data, TRUE);
        $this->load->view('admin/templates', $data);
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
					$row->added_info,
                    date_format(date_create($row->redeemed_coupon_log_added),"M d, Y h:i:s A"),
                    $badge,
                
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
		g.added_info,
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
				->setCellValue("AD1", 'Added Info')
				->setCellValue("AE1", 'Redeemed TS')
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
					->setCellValue('AD' . $x, $row->added_info )
					->setCellValue('AE' . $x, date_format(date_create($row->redeemed_coupon_log_added),"M d, Y h:i:s A") )
				;

				$x++;
			}

			$sheet1->getStyle("A1:AE1")->applyFromArray($style_bold);
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

			$proceed_to_cancel = TRUE;
			if($check_id['info']->parent_transaction_header_id){ //* a child trans from an advance order.
				$check_parent_id = $this->main->check_data('coupon_transaction_header_tbl', ['coupon_transaction_header_id' => $check_id['info']->parent_transaction_header_id], TRUE);
				if($check_parent_id['result'] && $check_parent_id['info']->coupon_transaction_header_status == 3){//* parent already cancelled
					$proceed_to_cancel = TRUE;
				} else {
					$proceed_to_cancel = FALSE;
				}
			}
			
			if(!$proceed_to_cancel){
				$params = [
					'header_id' => $check_id['info']->coupon_transaction_header_id,
					'parent_header_id' => $check_id['info']->parent_transaction_header_id,
				];
				$this->_update_coupon_back_to_parent_details($params);
			} else {
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
	
						$set    = [
							'survey_freebie_cal_status'  => 3
						];
						$where  = ['coupon_id' => $row->coupon_id];
						$result = $this->main->update_data('survey_freebie_calendar_tbl', $set, $where);
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

            $this->_validate_attachment();

            $sap_doc_no = ($this->input->post('sap_doc_no') != NULL) ? clean_data($this->input->post('sap_doc_no')) : '';

            $this->db->trans_start();

			$set       = [
                'payment_status' => 1,
                'sap_doc_no_2'     => $sap_doc_no,
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
                        'sap_doc_no_2'     => $sap_doc_no,
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);
                }
                
                if (isset($_FILES['attachment']) && $_FILES['attachment']['name'][0] != '') {
                    $this->_upload_transaction_attachment($coupon_transaction_header_id, 4);
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
	
	public function pay_transaction_old()
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
                        'sap_doc_no_2'     => $sap_doc_no,
                    ];
                    $where  = ['coupon_id' => $row->coupon_id];
                    $result = $this->main->update_data('coupon_tbl', $set, $where);
                }
                
                if (isset($_FILES['attachment']) && $_FILES['attachment']['name'][0] != '') {
                    $this->_upload_transaction_attachment($coupon_transaction_header_id, 4);
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
                        
						<input type="file" name="attachment[]" class="form-control-file form-control-sm" accept="image/png, image/jpeg, image/jpg, document/pdf" multiple required>
                    </div>';
            }
        }

		// '<input type="file" name="attachment[]" class="form-control-file" accept="image/png, image/jpeg, image/jpg, document/pdf" multiple required>'
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
		$html			= '';
		
        if ($check_coupon['result']) {
			if(in_array($check_coupon['info']->coupon_cat_id, paid_category())){
				$join        = ['coupon_tbl b' => 'b.coupon_id = a.coupon_id AND a.coupon_transaction_header_id = ' . $transaction_id];
				// $coupon      = $this->main->get_join('coupon_transaction_details_tbl a', $join, TRUE);
				$rec_payment_type_id = $check_coupon['info']->payment_type_id;
				$rec_payment_terms = $check_coupon['info']->payment_terms;

				$dropdown = $this->_get_payment_types_selection(decode($payment_type_id), $rec_payment_type_id, $check_coupon['info']->is_advance_order);
				$html = $this->_get_transaction_coupon_attachment($transaction_id);
				$result = $this->_get_payment_details_fields(decode($payment_type_id), $rec_payment_type_id, $rec_payment_terms, $dropdown, $has_attachement, $read_only);
			}
        } else {
			$dropdown = $this->_get_payment_types_selection(decode($payment_type_id));
			$result = $this->_get_payment_details_fields(decode($payment_type_id), NULL, NULL, $dropdown);
		}

		// '<input type="file" name="attachment[]" class="form-control-file" accept="image/png, image/jpeg, image/jpg, document/pdf" multiple required>'
        echo $html.$result;
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
		$data['top_nav']     = $this->load->view('fix/top_nav_content', $data, TRUE);
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
		$data['top_nav']     = $this->load->view('fix/top_nav_content', $data, TRUE);
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
            }elseif($row->coupon_status == 2 && $trans_header->coupon_transaction_header_status == 4){
                $badge  = 'first-approved';
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

	public function generate_uuid() {
		echo sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}

	private function _get_random_survey_winners($form_id){
		$info      								= $this->_require_login();
		$user_id								= decode($info['user_id']);
		$select 								= "a.*";
		$order_by 								= FALSE;
		$get_participants 						= $this->_get_participants($form_id, $order_by, $select);
		
		$participants 							= $get_participants['participants'];
		$participants 							= array_column($participants, 'ref_id', 'survey_ref_id');
		$participants_count						= count($participants);
		$bcs 									= $get_participants['bcs'];
		
		$failed_msg = '';
		
		if ($participants_count < 1) {
			//* insert to cron logs
			$cron_msg = "[".$form_id."] | Not enough participants ( ".$participants_count." ) for BCs of ".$bcs;
			$failed_msg = "Not enough participants to draw winners!";
		} else {
			$brand_id = $form_id == 5 ? 1 : 2;
			
			$join 									= [
				'coupon_bc_tbl b' 		=> 'a.coupon_id = b.coupon_id and b.coupon_bc_status = 1 and b.bc_id IN ('.$bcs.')'
			];
			$where_freebie = ['survey_freebie_cal_status' => 1, 'form_id' => $form_id, 'is_awarded' => 0, 'freebie_date' => date_now("Y-m-d"), 'brand_id' => $brand_id];
			$freebie_details         = $this->main->get_join('survey_freebie_calendar_tbl a', $join, FALSE, FALSE, 'a.coupon_id', 'a.*', $where_freebie, 1);

			$freebie_count = !empty($freebie_details) ? count($freebie_details) : 0;
			$coupon_ids = array_column($freebie_details, 'coupon_id');

			// pretty_dump($freebie_details);
			
			if($freebie_count > 0){
				
				$random_keys = array_rand($participants, 1);
				//* Normalize to array if only one item is picked
				if (!is_array($random_keys)) {
					$random_keys = [$random_keys];
				}
				//* Build the winner array
				$winners = [];
				foreach ($random_keys as $key) {
					$winners[$key] = $participants[$key];
				}
		
				//* Output the winners
				
				$i = 0;
				foreach ($winners as $survey_ref_id => $ref_id) {
					if( empty($coupon_ids[$i]) || !isset($coupon_ids[$i]) ){
						break;
					}
					$set = [
						'survey_ref_id'					=> $survey_ref_id,
						'ref_id'						=> $ref_id,
						'form_id'						=> $form_id,
						'coupon_id'						=> $coupon_ids[$i],
						'survey_winner_status'			=> 1,
						'created_at'					=> date('Y-m-d H:i:s'),
						'survey_winner_email'			=> '',
						'survey_winner_email_result'	=> 0,
						'created_by'					=> $user_id,
					];
					$this->main->insert_data('survey_winners_tbl', $set, TRUE);

					$set = [
						'is_awarded' => 1,
					];
					$where  = ['coupon_id' => $coupon_ids[$i]];
					$this->main->update_data('survey_freebie_calendar_tbl', $set, $where);

					$i++;
				}
				$cron_msg = "[".$form_id."] | Successfully inserted winners (".implode(',', $winners).") for BCs of ".$bcs;
				
			} else {
				$cron_msg = "[".$form_id."] | Invalid Prize count found ( ".$freebie_count." ) for BCs of ".$bcs;
				$failed_msg = "No more Prizes available for this draw!";
			}
	
		}

		$set = [
			'cron_log_type'						=> 3,
			'cron_log_message'					=> $cron_msg,
			'cron_log_added'					=> date_now(),
			'cron_log_status'					=> 1
		];
		$this->main->insert_data('cron_logs_tbl', $set, TRUE);

		if(!empty($failed_msg)) {
			return [
				'result' => false,
				'message' => $failed_msg
			];
		}
		return [
			'result' => true,
			'message' => ''
		];
		
	}

	public function raffle_draw() 
    {
        $info      = $this->_require_login();
        $parent_db = $GLOBALS['parent_db'];
        

        $data['title']						= 'Raffle Draw';
		$data['top_nav']     				= $this->load->view('fix/top_nav_content', $data, TRUE);
        $data['content']					= $this->load->view('raffle/raffle_draw_content', $data, TRUE);
        $this->load->view('admin/templates', $data);
    }

	public function all_entries()
	{
		$info      = $this->_require_login();

		if ($_SERVER['REQUEST_METHOD'] == 'GET') {

			$form_id = 5;
			$select = "a.survey_ref_id,
			a.name,
			a.email,
			a.contact_number,
			a.age,
			a.address,
			a.or_number,
			a.or_photo,
			RIGHT(a.ref_no, 9) AS ref_no,
			a.created_at AS entry_created_at,
			b.province_name,
			c.town_group_name,
			d.barangay_name,
			e.survey_winner_id,
			e.created_at AS winner_created_at
			";
			$order_by = 'survey_ref_id DESC';
			
			$participants_det = $this->_get_participants($form_id, FALSE, FALSE, FALSE);
			$start_date = $participants_det['start_date'];
			$end_date = $participants_det['end_date'];
			$filter	= 'status = 1 and a.form_id = '.$form_id.' and a.created_at >= "'.$start_date.'" AND a.created_at <= "'.$end_date.'"';
			
			$participants = $this->_get_participants($form_id, $order_by, $select, FALSE, $filter, $with_winner=TRUE)['participants'];


			$survey_photo_url = SURVEY_PHOTO_URL;
			$data = [];
			foreach ($participants as $row) {
				$data[] = $row->survey_winner_id ? [
					'id' => $row->survey_ref_id,
					'name' => $row->name,
					'ref_no' => $row->ref_no,
					'email' => $row->email,
					'contact_number' => $row->contact_number,
					'address' => $row->address,
					'province' => $row->barangay_name. ' ' . $row->town_group_name . ', ' . $row->province_name,
					'or_number' => $row->or_number,
					'or_photo' => $row->or_photo ? $survey_photo_url . $row->or_photo : '',
					'entry_created_at' => date('M d, Y h:i A', strtotime($row->entry_created_at)),
					'is_winner' => $row->survey_winner_id ? "YES" : "NO",
					'winner_created_at' => $row->winner_created_at ? date('M d, Y h:i A', strtotime($row->winner_created_at)) : '',
					'winning_date' => $row->winner_created_at ? date('M d, Y', strtotime($row->winner_created_at)) : '',
				] : [
					'id' => $row->survey_ref_id,
					'name' => $row->name,
					'ref_no' => $row->ref_no,
					'email' => $row->email,
					'contact_number' => $row->contact_number,
					'address' => $row->address,
					'province' => $row->barangay_name. ' ' . $row->town_group_name . ', ' . $row->province_name,
					'or_number' => $row->or_number,
					'or_photo' => $row->or_photo ? $survey_photo_url . $row->or_photo : '',
					'entry_created_at' => date('M d, Y h:i A', strtotime($row->entry_created_at)),
					'is_winner' => $row->survey_winner_id ? "YES" : "NO",
				];
			}

			echo json_encode($data);
			exit;
		}
	}

	public function participants()
	{
		$info      = $this->_require_login();

		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			
			$form_id = 5;
			$select = "a.survey_ref_id,
			a.name,
			a.email,
			a.contact_number,
			a.age,
			a.address,
			a.or_number,
			a.or_photo,
			RIGHT(a.ref_no, 9) AS ref_no,
			a.created_at AS entry_created_at,
			b.province_name,
			c.town_group_name,
			d.barangay_name,
			";
			$order_by = 'survey_ref_id DESC';
			$participants = $this->_get_participants($form_id, $order_by, $select)['participants'];
			

			$survey_photo_url = SURVEY_PHOTO_URL;
			$data = [];
			foreach ($participants as $row) {
				$data[] = [
					'id' => $row->survey_ref_id,
					'name' => $row->name,
					'ref_no' => $row->ref_no,
					'email' => $row->email,
					'contact_number' => $row->contact_number,
					'address' => $row->address,
					'province' => $row->barangay_name. ' ' . $row->town_group_name . ', ' . $row->province_name,
					'or_number' => $row->or_number,
					'or_photo' => $row->or_photo ? $survey_photo_url . $row->or_photo : '',
					'entry_created_at' => date('M d, Y h:i A', strtotime($row->entry_created_at)),
				];
			}

			$this->output
				->set_status_header(200)
				->set_content_type('application/json')
				->set_output(json_encode($data))
				->_display();
			exit;
		}
	}

	public function draw(){
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$parent_db = $GLOBALS['parent_db'];
			
			$winner = $this->_get_random_survey_winners(5);

			if (!$winner['result']) {
				$response = [
					"messages" => [
						"error" => $winner['message']
					]
				];
				$stat_header = 400;
				
			} else {

				//* GET THE WINNER
				$join = [
					'survey_reference_tbl b' => 'a.survey_ref_id = b.survey_ref_id',
					'coupon_prod_sale_tbl c' => 'a.coupon_id = c.coupon_id and c.coupon_prod_sale_status = 1',
				];
				$where = [
					'a.form_id' => 5,
					'a.survey_winner_status' => 1,
					'a.survey_winner_email_result' => 0,
					'a.survey_winner_email' => '',
					'a.survey_winner_validated' => 0
				];
				$select = 'a.survey_winner_id, a.survey_ref_id, b.name, RIGHT(ref_no, 9) AS ref_no, c.prod_sale_id';
				$order_by = 'a.survey_winner_id DESC';
				$participants = $this->main->get_join('survey_winners_tbl a', $join, TRUE, $order_by, FALSE, $select, $where);
	
				$response = [
					"messages" => [
						"error" => $participants ? "" : "No winner found"
					],
					"winner" => !empty($participants) ? [
						"id" => $participants->survey_ref_id,
						"name" => $participants->name,
						"ref_no" => $participants->ref_no
					] : null,
					'not_validated_winners_count' => $this->_get_not_validated_winner_count(date("Y-m-d"), $participants->prod_sale_id),
				];
				$stat_header = $participants ? 200 : 404;
	
			}
			
		} else {
			$response = [
				"messages" => [
					"error" => "Invalid request method."
				]
			];
			$stat_header = 400;
		}
		$this->output
			->set_status_header($stat_header)
			->set_content_type('application/json')
			->set_output(json_encode($response))
			->_display();
		exit;
	}

	public function not_validated_winners()
	{
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			
			$select = "a.survey_winner_id,
			a.survey_ref_id,
			b.name,
			b.email,
			b.contact_number,
			b.age,
			b.address,
			b.or_number,
			b.or_photo,
			RIGHT(b.ref_no, 9) AS ref_no,
			b.created_at AS entry_created_at,
			a.created_at AS winner_created_at,
			f.province_name,
			h.town_group_name,
			i.barangay_name,
			e.prod_sale_promo_name,
			CONCAT(j.user_fname, ' ', j.user_lname) AS created_by,
			CONCAT(k.user_fname, ' ', k.user_lname) AS modified_by
			";
			$where = [
				'a.form_id' => 5,
				'a.survey_winner_status' => 1,
				'a.survey_winner_validated' => 0
			];
			$order_by = 'a.survey_winner_id DESC';
			$participants = $this->_get_winner_query($select, $order_by, $where);

			$survey_photo_url = SURVEY_PHOTO_URL;
			$data = [];
			foreach ($participants as $row) {
				$data[] = [
					'id' => $row->survey_ref_id,
					'winner_id' => $row->survey_winner_id,
					'name' => $row->name,
					'ref_no' => $row->ref_no,
					'email' => $row->email,
					'contact_number' => $row->contact_number,
					'address' => $row->address,
					'province' => $row->barangay_name. ' ' . $row->town_group_name . ', ' . $row->province_name,
					'or_number' => $row->or_number,
					'or_photo' => $row->or_photo ? $survey_photo_url . $row->or_photo : '',
					'entry_created_at' => date('M d, Y h:i A', strtotime($row->entry_created_at)),
					'winner_created_at' => date('M d, Y h:i A', strtotime($row->winner_created_at)),
					'drawn_by' => $row->created_by,
					'winner_prize' => $row->prod_sale_promo_name,
				];
			}

			echo json_encode($data);
			exit;
		}
	}
	
	public function validated_winners()
	{
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$select = "a.survey_ref_id,
			a.survey_winner_id,
			b.name,
			b.email,
			b.contact_number,
			b.age,
			b.address,
			b.or_number,
			b.or_photo,
			RIGHT(b.ref_no, 9) AS ref_no,
			b.created_at AS entry_created_at,
			a.created_at AS winner_created_at,
			a.modified_at AS winner_modified_at,
			f.province_name,
			h.town_group_name,
			i.barangay_name,
			e.prod_sale_promo_name,
			CONCAT(j.user_fname, ' ', j.user_lname) AS created_by,
			CONCAT(k.user_fname, ' ', k.user_lname) AS modified_by
			";
			$where = [
				'a.form_id' => 5,
				'a.survey_winner_status' => 1,
				'a.survey_winner_validated' => 1
			];
			$order_by = 'a.survey_winner_id DESC';
			$participants = $this->_get_winner_query($select, $order_by, $where);

			$survey_photo_url = SURVEY_PHOTO_URL;
			$data = [];
			foreach ($participants as $row) {
				$data[] = [
					'id' => $row->survey_ref_id,
					'winner_id' => $row->survey_winner_id,
					'name' => $row->name,
					'ref_no' => $row->ref_no,
					'email' => $row->email,
					'contact_number' => $row->contact_number,
					'address' => $row->address,
					'province' => $row->barangay_name. ' ' . $row->town_group_name . ', ' . $row->province_name,
					'or_number' => $row->or_number,
					'or_photo' => $row->or_photo ? $survey_photo_url . $row->or_photo : '',
					'entry_created_at' => date('M d, Y h:i A', strtotime($row->entry_created_at)),
					'winner_created_at' => date('M d, Y h:i A', strtotime($row->winner_created_at)),
					'drawn_by' => $row->created_by,
					'validated_at' => date('M d, Y h:i A', strtotime($row->winner_modified_at)),
					'validated_by' => $row->modified_by,
					'winner_prize' => $row->prod_sale_promo_name,
				];
			}

			echo json_encode($data);
			exit;
		}
	}
	
	public function rejected_winners()
	{
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];

		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$select = "a.survey_ref_id,
			a.survey_winner_id,
			a.reason,
			b.name,
			b.email,
			b.contact_number,
			b.age,
			b.address,
			b.or_number,
			b.or_photo,
			RIGHT(b.ref_no, 9) AS ref_no,
			b.created_at AS entry_created_at,
			a.created_at AS winner_created_at,
			a.modified_at AS winner_modified_at,
			f.province_name,
			h.town_group_name,
			i.barangay_name,
			CONCAT(j.user_fname, ' ', j.user_lname) AS created_by,
			CONCAT(k.user_fname, ' ', k.user_lname) AS modified_by
			";
			$where = [
				'a.form_id' => 5,
				'a.survey_winner_status' => 0,
			];
			$order_by = 'a.survey_winner_id DESC';
			$participants = $this->_get_winner_query($select, $order_by, $where);

			$survey_photo_url = SURVEY_PHOTO_URL;
			$data = [];
			foreach ($participants as $row) {
				$data[] = [
					'id' => $row->survey_ref_id,
					'winner_id' => $row->survey_winner_id,
					'name' => $row->name,
					'ref_no' => $row->ref_no,
					'email' => $row->email,
					'contact_number' => $row->contact_number,
					'address' => $row->address,
					'province' => $row->barangay_name. ' ' . $row->town_group_name . ', ' . $row->province_name,
					'or_number' => $row->or_number,
					'or_photo' => $row->or_photo ? $survey_photo_url . $row->or_photo : '',
					'entry_created_at' => date('M d, Y h:i A', strtotime($row->entry_created_at)),
					'winner_created_at' => date('M d, Y h:i A', strtotime($row->winner_created_at)),
					'drawn_by' => $row->created_by,
					'rejected_at' => date('M d, Y h:i A', strtotime($row->winner_modified_at)),
					'reason_for_rejection' => $row->reason,
					'rejected_by' => $row->modified_by,
				];
			}

			echo json_encode($data);
			exit;
		}
	}

	public function validate_winner() {
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];
		$error_msg = "Error! Please try again.";
		$success_msg = "Success! Winner validated.";

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// Accept JSON payload
			$raw = file_get_contents("php://input");
			$data = json_decode($raw, true);

			$survey_winner_id = isset($data['id']) ? trim(clean_data($data['id'])) : null;
			if(!empty($survey_winner_id)){
				// $survey_ref = $this->main->get_data('survey_winners_tbl', ['survey_winner_id' => $survey_winner_id], true, 'survey_ref_id, created_at');
				$join = [
					'coupon_prod_sale_tbl b' => 'a.coupon_id = b.coupon_id and b.coupon_prod_sale_status = 1',
				];
				$survey_ref = $this->main->get_join('survey_winners_tbl a', $join, TRUE, FALSE, FALSE, 'survey_ref_id, prod_sale_id, created_at', ['survey_winner_id' => $survey_winner_id]);
				$survey_ref_id = !empty($survey_ref) ? $survey_ref->survey_ref_id : null;
				$response = [];
				if (!empty($survey_ref_id)) {
					$winning_date 		= $survey_ref->created_at ?? null;
					$winning_date 		= $winning_date ? date('Y-m-d', strtotime($winning_date)) : null;
					$prod_sale_id 		= $survey_ref->prod_sale_id ?? null;
					$winner_count 		= $this->_get_validated_winner_count($winning_date, $prod_sale_id);
					$should_be_winner 	= $this->_should_be_winner($winning_date, $prod_sale_id);
					if($winner_count < $should_be_winner){
						$send_winner_email = $this->email_survey_winner(5, $survey_ref_id);
						if ($send_winner_email['result']) {
							$success_msg = $send_winner_email['Message'];
							$error_msg = "";
						} else {
							$error_msg = $send_winner_email['Message'];
							$success_msg = "";
						}
					} else {
						$error_msg = "Validated winner limit reached for the draw date and specific draw prize. Current validated winner(s): {$winner_count}.";
						$success_msg = "";
					}
				} else {
					$error_msg = "Invalid survey reference ID.";
					$success_msg = "";
				}
			} else {
				$error_msg = "Invalid winner ID.";
				$success_msg = "";
			}
		} else {
			$error_msg = "Invalid request method.";
			$success_msg = "";
		}
		$stat_header = $error_msg ? 400 : 200;
		$response = [
			"messages" => [
				"error" => $error_msg,
				"success" => $success_msg
			]
		];

		$this->output
			->set_status_header($stat_header)
			->set_content_type('application/json')
			->set_output(json_encode($response))
			->_display();
		exit;
	}
	
	public function reject_winner() {
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];
		$error_msg = "Error! Please try again.";
		$success_msg = "Success! Winner validated.";

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// Accept JSON payload
			$raw = file_get_contents("php://input");
			$data = json_decode($raw, true);

			$survey_winner_id = isset($data['id']) ? trim(clean_data($data['id'])) : null;
			$reason = isset($data['remarks']) ? trim(clean_data($data['remarks'])) : null;
			if(!empty($survey_winner_id)){
				$survey_ref = $this->main->get_data('survey_winners_tbl', ['survey_winner_id' => $survey_winner_id], true, 'survey_ref_id, coupon_id');
				$survey_ref_id = !empty($survey_ref) ? $survey_ref->survey_ref_id : null;
				$response = [];
				if (!empty($survey_ref_id)) {
					
					$set = [
						'survey_winner_status' => 0,
						'survey_winner_validated' => 0,
						'reason' => strtoupper($reason),
						'modified_at' => date('Y-m-d H:i:s'),
						'modified_by' => decode($info['user_id'])
					];
					$where = ['survey_winner_id' => $survey_winner_id];
					$update = $this->main->update_data('survey_winners_tbl', $set, $where);
					if (!$update) {
						$error_msg = "Failed to reject winner. Please try again.";
						$success_msg = "";	
					} else {
						$coupon_id = $survey_ref->coupon_id ?? null;
						$set = [
							"survey_freebie_cal_status" => 1,
							"is_awarded" => 0
						];
						$where = ['coupon_id' => $coupon_id];
						$update = $this->main->update_data('survey_freebie_calendar_tbl', $set, $where);
						if (!$update) {
							$error_msg = "Failed to free-up winner prize. Please try again.";
							$success_msg = "";	
						} else {
							$success_msg = "Winner rejected successfully.";
							$error_msg = "";
						}
					}

				} else {
					$error_msg = "Invalid survey reference ID.";
					$success_msg = "";
				}
			} else {
				$error_msg = "Invalid winner ID.";
				$success_msg = "";
			}
		} else {
			$error_msg = "Invalid request method.";
			$success_msg = "";
		}
		$stat_header = $error_msg ? 400 : 200;
		$response = [
			"messages" => [
				"error" => $error_msg,
				"success" => $success_msg
			]
		];

		$this->output
			->set_status_header($stat_header)
			->set_content_type('application/json')
			->set_output(json_encode($response))
			->_display();
		exit;
	}
	
	public function undo_winner() {
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];
		$error_msg = "Error! Please try again.";
		$success_msg = "Success! Winner validated.";

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// Accept JSON payload
			$raw = file_get_contents("php://input");
			$data = json_decode($raw, true);
			// $should_be_winner = 8;

			$survey_winner_id = isset($data['id']) ? trim(clean_data($data['id'])) : null;
			$remarks = isset($data['remarks']) ? trim(clean_data($data['remarks'])) : null;
			if(!empty($survey_winner_id)){
				// $survey_ref = $this->main->get_data('survey_winners_tbl', ['survey_winner_id' => $survey_winner_id], true, 'survey_ref_id, coupon_id, created_at');
				$join = [
					'coupon_prod_sale_tbl b' => 'a.coupon_id = b.coupon_id and b.coupon_prod_sale_status = 1',
				];
				$survey_ref = $this->main->get_join('survey_winners_tbl a', $join, TRUE, FALSE, FALSE, 'survey_ref_id, a.coupon_id, prod_sale_id, created_at', ['survey_winner_id' => $survey_winner_id]);
				$survey_ref_id = !empty($survey_ref) ? $survey_ref->survey_ref_id : null;
				$response = [];
				if (!empty($survey_ref_id)) {
					$winning_date 		= $survey_ref->created_at ?? null;
					$winning_date 		= $winning_date ? date('Y-m-d', strtotime($winning_date)) : null;
					$prod_sale_id 		= $survey_ref->prod_sale_id ?? null;
					$winner_count 		= $this->_get_validated_winner_count($winning_date, $prod_sale_id);
					$should_be_winner 	= $this->_should_be_winner($winning_date, $prod_sale_id);
					if($winner_count >= $should_be_winner){
						$set = [
							'survey_winner_status' => 2,
							'survey_winner_validated' => 0,
							'reason' => strtoupper($remarks),
							'modified_at' => date('Y-m-d H:i:s'),
							'modified_by' => decode($info['user_id'])
						];
						$where = ['survey_winner_id' => $survey_winner_id];
						$update = $this->main->update_data('survey_winners_tbl', $set, $where);
						if (!$update) {
							$error_msg = "Failed to revert winner. Please try again.";
							$success_msg = "";	
						} else {
							$coupon_id = $survey_ref->coupon_id ?? null;
							$set = [
								"survey_freebie_cal_status" => 1,
								"is_awarded" => 0
							];
							$where = ['coupon_id' => $coupon_id];
							$update = $this->main->update_data('survey_freebie_calendar_tbl', $set, $where);
							if (!$update) {
								$error_msg = "Failed to free-up winner prize. Please try again.";
								$success_msg = "";	
							} else {
								$success_msg = "Winner reverted successfully.";
								$error_msg = "";
							}
						}
					} else {
						$error_msg = "Undo is prohibited if the validated winners is less than {$should_be_winner}. Current validated winner(s) for the draw date and specific draw prize: {$winner_count}.";
						$success_msg = "";
					}
				} else {
					$error_msg = "Invalid survey reference ID.";
					$success_msg = "";
				}
			} else {
				$error_msg = "Invalid winner ID.";
				$success_msg = "";
			}
		} else {
			$error_msg = "Invalid request method.";
			$success_msg = "";
		}
		$stat_header = $error_msg ? 400 : 200;
		$response = [
			"messages" => [
				"error" => $error_msg,
				"success" => $success_msg
			]
		];

		$this->output
			->set_status_header($stat_header)
			->set_content_type('application/json')
			->set_output(json_encode($response))
			->_display();
		exit;
	}

	private function _should_be_winner($winning_date, $prod_sale_id = null){
		$where = [
			'survey_draw_set_type' 			=> 1,
			'DATE(survey_draw_date)' 		=> $winning_date,
			'prod_sale_id' 					=> $prod_sale_id,
			'survey_draw_set_status' 		=> 1
		];
		$get_should_be_winner = $this->main->get_data('survey_draw_settings_tbl a', $where, true, 'winner_count');

		$should_be_winner_count = !empty($get_should_be_winner) ? $get_should_be_winner->winner_count : 0;

		return $should_be_winner_count;
	}

	private function _get_validated_winner_count($winning_date, $prod_sale_id = null){
		// $survey_winner = $this->main->get_data('survey_winners_tbl', ['survey_winner_status' => 1, 'survey_winner_validated' => 1, 'DATE(created_at)' => $winning_date], true, 'COUNT(survey_winner_id) as winner_count');
		$join = [
			'coupon_prod_sale_tbl b' => 'a.coupon_id = b.coupon_id and b.coupon_prod_sale_status = 1',
		];
		$survey_winner = $this->main->get_join('survey_winners_tbl a', $join, TRUE, FALSE, FALSE, 'COUNT(survey_winner_id) as winner_count', ['survey_winner_status' => 1, 'survey_winner_validated' => 1, 'DATE(created_at)' => $winning_date, 'b.prod_sale_id' => $prod_sale_id]);
		$winner_count = !empty($survey_winner) ? $survey_winner->winner_count : 0;
		return $winner_count;
	}
	
	private function _get_not_validated_winner_count($winning_date, $prod_sale_id = null){
		// $survey_winner = $this->main->get_data('survey_winners_tbl', ['survey_winner_status' => 1, 'survey_winner_validated' => 0, 'DATE(created_at)' => $winning_date], true, 'COUNT(survey_winner_id) as winner_count');
		$join = [
			'coupon_prod_sale_tbl b' => 'a.coupon_id = b.coupon_id and b.coupon_prod_sale_status = 1',
		];
		$survey_winner = $this->main->get_join('survey_winners_tbl a', $join, TRUE, FALSE, FALSE, 'COUNT(survey_winner_id) as winner_count', ['survey_winner_status' => 1, 'survey_winner_validated' => 0, 'DATE(created_at)' => $winning_date, 'b.prod_sale_id' => $prod_sale_id]);
		$winner_count = !empty($survey_winner) ? $survey_winner->winner_count : 0;
		return $winner_count;
	}

	private function _get_participants($form_id, $order_by = FALSE, $select = FALSE, $limit_to_yesterday = TRUE, $filter = FALSE, $with_winner = FALSE){
		$sibling_db 							= sibling_one_db();
		$parent_db 								= parent_db();
		$check_form 							= $this->main->check_data("{$sibling_db}.form_tbl", ['form_id' => $form_id], TRUE);
		$start_date								= $check_form['result'] ? $check_form['info']->start_date : date("Y-m-d H:i:s");
		$end_date								= $check_form['result'] ? $check_form['info']->end_date : date("Y-m-d H:i:s");
		
		if($limit_to_yesterday){
			if($check_form['result'] && $check_form['info']->data_cutoff_date){
				$end_date = $check_form['info']->data_cutoff_date;
			} else {
				// $end_date = date("Y-m-d H:i:s");
				$end_date = date('Y-m-d 23:59:59', strtotime('-1 day'));
			}
		}

		$get_participating_bcs 					= $this->main->get_data('survey_participating_bcs_tbl', ['form_id' => $form_id, 'survey_participating_bc_status' => 1]);
		$join 									= [
			"{$parent_db}.bc_tbl b" 			=> 'a.bc_id = b.bc_id'
		];
		$get_participating_bcs 					= $this->main->get_join('survey_participating_bcs_tbl a', $join, FALSE, FALSE, FALSE, 'a.*, b.bc_name', ['a.form_id' => $form_id, 'a.survey_participating_bc_status' => 1]);

		$participants = [];
		$bcs = [];
		
		if(!empty($get_participating_bcs)){
			$bcs = array_column($get_participating_bcs, 'bc_id');
			$bcs = implode(',', $bcs);

			if(!$filter){
				// $filter									= 'a.status = 1 and a.form_id = '.$form_id.' and a.survey_ref_id not in (SELECT survey_ref_id from survey_winners_tbl where survey_winner_status IN (1, 0) and form_id= '.$form_id.') and a.created_at >= "'.$start_date.'" AND a.created_at <= "'.$end_date.'"';
				$filter									= '(a.status = 1 and a.form_id = '.$form_id.' and a.survey_ref_id not in (SELECT survey_ref_id from survey_winners_tbl where survey_winner_status IN (1, 0) and form_id= '.$form_id.') and a.created_at >= "'.$start_date.'" AND a.created_at <= "'.$end_date.'") AND (a.status = 1 and a.form_id = '.$form_id.' and a.normalized_name not in (SELECT y.normalized_name from survey_winners_tbl x INNER JOIN survey_reference_tbl y ON x.survey_ref_id = y.survey_ref_id where x.survey_winner_status IN (1) and x.form_id= '.$form_id.') and a.created_at >= "'.$start_date.'" AND a.created_at <= "'.$end_date.'")';
			}
			if($select){
				$join 									= [
					"{$parent_db}.provinces_tbl b" 		=> 'a.province_id = b.province_id and b.bc_id IN ('.$bcs.')',
					"{$parent_db}.town_groups_tbl c" 	=> 'a.town_group_id = c.town_group_id',
					"{$parent_db}.barangay_tbl d" 		=> 'a.barangay_id = d.barangay_id',
				];
	
				if($with_winner){
					$join['survey_winners_tbl e, LEFT'] = 'a.survey_ref_id = e.survey_ref_id AND e.survey_winner_status = 1 AND e.form_id = '.$form_id.' AND e.survey_winner_validated = 1';
				}
				$participants         					= $this->main->get_join('survey_reference_tbl a', $join, FALSE, $order_by, FALSE, $select, $filter);
			}
			
		}

		$result = [
			'bcs' => $bcs,
			'participants' => $participants,
			'start_date' => $start_date,
			'end_date' => $end_date
		];
		return $result;
	}
	
	private function _get_winner_query($select, $order_by, $where){
		$info      = $this->_require_login();
		$parent_db = $GLOBALS['parent_db'];
		$join        = [
			'survey_reference_tbl b'			=> 'b.survey_ref_id = a.survey_ref_id',
			'coupon_tbl c'						=> 'a.coupon_id = c.coupon_id AND c.coupon_status = 1',
			'coupon_prod_sale_tbl d'			=> 'c.coupon_id = d.coupon_id AND c.coupon_status = 1',
			"{$parent_db}.product_sale_tbl e"	=> 'd.prod_sale_id = e.prod_sale_id',
			"{$parent_db}.provinces_tbl f"		=> 'b.province_id = f.province_id',
			"{$parent_db}.bc_tbl g"				=> 'f.bc_id = g.bc_id',
			"{$parent_db}.town_groups_tbl h" 	=> 'b.town_group_id = h.town_group_id',
			"{$parent_db}.barangay_tbl i" 		=> 'b.barangay_id = i.barangay_id',
			"{$parent_db}.user_tbl j" 			=> 'a.created_by = j.user_id',
			"{$parent_db}.user_tbl k, LEFT" 	=> 'a.modified_by = k.user_id',
		];
		
        $winners      = $this->main->get_join('survey_winners_tbl a', $join, FALSE, $order_by, FALSE, $select, $where);

		return $winners;
	}

	private function email_survey_winner($form_id = NULL, $survey_ref_id = NULL) {
		$info      								= $this->_require_login();
		
		$select 								= 'a.survey_winner_id, b.name, b.email, c.coupon_code, c.coupon_end, e.prod_sale_promo_name, g.bc_name, g.bc_address';
        
		$where = [
			'a.form_id' 						=> $form_id,
			'a.survey_winner_email_result' 		=> 0,
			'a.survey_winner_status' 			=> 1,
			'a.survey_winner_validated' 		=> 0,
			'a.survey_ref_id' 					=> $survey_ref_id
		];
		$winners								= $this->_get_winner_query($select, $order_by=FALSE, $where);

		if($form_id == 5){
			$brand_name 								= 'Chooks To Go';
			$brand_logo 								= 'CTG-Digital-Logo.png';
			$brand_bg_color 							= '#ff0000';
			$brand_color 								= '#fff';
			$greetings 									= '<strong><span style="font-size:16px;color:#0c0d0d">CONGRATULATIONS!</span></strong>';
			$subject 									= $brand_name." Chooksie's 8.8 QR Promo Winner Notification";
			$promo_name 								= "Chooksie's 8.8 Promo";
		} else {
			$brand_name 								= 'Uling Roasters';
			$brand_logo 								= 'Uling-Roasters-Logo-transparent.png';
			$brand_bg_color 							= '#ffff00';
			$brand_color 								= '#111';
			$greetings 									= '<strong><span style="font-size:16px;color:#331a00">CONGRATULATIONS!</span></strong>';
			$subject 									= $brand_name.' Promo Winner Notification';
			$promo_name 								= 'Promo';
		}

		if(!empty($winners)){
			foreach($winners as $winner){
				if($winner->email == ''){
					$email_result = [
						'result'					=> FALSE,
						'Message'					=> 'Winner Email is empty'
					];
					
					$this->_store_email_log($email_result, $winner->email);
					continue;
				}
				$data['name'] =  $winner->name;
				$data['coupon_code']  = $winner->coupon_code;
				$data['prod_sale_promo_name'] = $winner->prod_sale_promo_name;
				$data['brand_name'] = $brand_name;
				$data['brand_logo'] = $brand_logo;
				$data['brand_color'] = $brand_color;
				$data['promo_name'] = $promo_name;
				$data['greetings'] = $greetings;
				$data['brand_bg_color'] = $brand_bg_color;
				$data['coupon_end'] = $winner->coupon_end;
				$data['bc_name'] = $winner->bc_name;
				$data['bc_address'] = $winner->bc_address;

				$message = $this->load->view('email/chooksie_email_survey_winner_content', $data, TRUE);
				
				$email_result = $this->_send_email($winner->email, $subject, $message, $brand_name);
				
	
				$set = [
					'survey_winner_email' 			=> $email_result['Message'],
					'survey_winner_email_result' 	=> $email_result['result'],
					'survey_winner_validated' 		=> $email_result['result'] ? 1 : 0,
					'modified_at' 					=> date('Y-m-d H:i:s'),
					'modified_by' 					=> decode($info['user_id'])
				];
				$where  = ['survey_winner_id' => $winner->survey_winner_id];
				$this->main->update_data('survey_winners_tbl', $set, $where);

				$email_sending_result = $email_result['result'] ? "Successfully sent" : "Not successfully sent";
	
				$cron_msg = "[".$form_id."] | ".$email_sending_result." email to ".$winner->email;

				$set = [
					'cron_log_type'					=> 4,
					'cron_log_message'				=> $cron_msg,
					'cron_log_added'				=> date_now(),
					'cron_log_status'				=> 1
				];
				$this->main->insert_data('cron_logs_tbl', $set, TRUE);

				if($email_result['result']) {
					$email_result = [
						'result'					=> TRUE,
						'Message'					=> 'Successfully validated, An email was sent to '.$winner->email.'.'
					];
				} else {
					$email_result = [
						'result'					=> FALSE,
						'Message'					=> 'Failed to validate and send email to '.$winner->email.'. Please try again.'
					];
				}
			}
		} else {
			$email_result = [
				'result'					=> FALSE,
				'Message'					=> 'No winner found'
			];
		}

		return $email_result;

	}
}
