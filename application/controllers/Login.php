<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

	public function __construct() {
    	parent::__construct();
    	$this->load->model('main_model', 'main');
        $GLOBALS['parent_db'] = parent_db();
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
					redirect('creator');
				}elseif($user_type == "8"){
					redirect('approver');
				}else{
					$this->session->unset_userdata('evoucher-user');
				}
			}else{
				$this->session->unset_userdata('evoucher-user');
				redirect('login/change-password/' . $login['user_id']);
			}
		}else{
			$this->session->unset_userdata('evoucher-user');
		}
	}

	public function index()
	{
		$info = $this->_require_login();
		$data['title'] = ''.SYS_NAME.' System';
		$this->load->view('login/login_content', $data);

	}

	public function logout_process()
	{
		$this->session->unset_userdata('evoucher-user');
		redirect('login');
	}

	public function login_process()
	{
		if($_SERVER['REQUEST_METHOD'] != 'POST'){
			redirect('login');
		}

		$rules = [
			[
				'field' => 'email',
				'label' => 'Email',
				'rules' => 'required'
			],
			[
				'field' => 'password',
				'label' => 'Password',
				'rules' => 'required'
			],
		];

		$this->load->library('form_validation');
		$this->form_validation
			->set_rules($rules)
			->set_error_delimiters(
				'<div class="alert alert-danger alert-dismissible fade show" role="alert">', 
					'<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>');
		if ($this->form_validation->run() == FALSE) {
			$this->session->set_flashdata('message', validation_errors());
			redirect('login');
		}

		$parent_db   = $GLOBALS['parent_db'];
		$email       = trim(clean_data($this->input->post('email')));
		$password    = trim(clean_data($this->input->post('password')));
		$where       = "user_email = '{$email}' AND user_status = 1 AND user_type_id IN (1,2,7,8,9,11,12,13)";
		$check_login = $this->main->check_data("{$parent_db}.user_tbl", $where, TRUE);

		if($check_login['result'] == TRUE){
			$check_login['info']->user_password;
			if(decode($check_login['info']->user_password) == $password){
				$user_id = $check_login['info']->user_id;
				$name = $check_login['info']->user_fname;
				$email = $check_login['info']->user_email;

				$session = array(
					'user_id'      => encode($check_login['info']->user_id),
					'user_type_id' => encode($check_login['info']->user_type_id),
					'user_reset'   => $check_login['info']->user_reset
				);

				$this->_store_login_log($check_login['info']->user_id);
				if($check_login['info']->user_status == 1){

					$set_otp = $this->_create_otp($user_id);

					if($set_otp['result'] == TRUE){
						$otp_code = $set_otp['code'];
						$otp_id =  $set_otp['id'];
						$email_content = $this->otp_email_content($name, $otp_code);
						$subject = ''.SYS_NAME.' Two Factor Authentication';

						$send_email = $this->_send_email($email, $email_content, $subject);
						$email_result = $send_email['msg'];

						$set_email = array('login_otp_email' => $email_result);
						$where_email = array('login_otp_id' => $otp_id);
						$insert_email = $this->main->update_data('login_otp_tbl', $set_email, $where_email);

						$expiration = 900; //15 mins expiration
						$this->session->set_tempdata('evoucher-otp', encode($user_id), $expiration);
						$this->session->set_tempdata('evoucher-otp-attempts', 0, $expiration);

						redirect('login/two-factor-authentication');
					}
				}else{
					$msg = '<div class="alert alert-danger">Error please contact your administrator.</div>';
					$this->session->set_flashdata('message', $msg);
					redirect('login');
				}
			}else{
				$msg = '<div class="alert alert-danger">Invalid email and password.</div>';
				$this->session->set_flashdata('message', $msg);
				redirect('login');
			}
		
		}else{
			$msg = '<div class="alert alert-danger">Invalid email and password.</div>';
			$this->session->set_flashdata('message', $msg);
			redirect('login');
		}
	}

	public function _create_otp($user_id){

		$update_otp = $this->main->update_data('login_otp_tbl', array('login_otp_status' => 0), array('user_id' => $user_id));

		$otp_code = generate_random_coupon(5);
		$duration = 15;
		$date_now = date_now();
		$expiration = date('Y-m-d H:i:s', strtotime('+' . $duration . ' minutes', strtotime($date_now)));
		$set = array(
			'user_id' => $user_id,
			'login_otp_code' => $otp_code,
			'login_otp_duration' => $duration,
			'login_otp_expiration' => $expiration,
			'login_otp_added' => date_now(),
			'login_otp_status' => 1
		);

		$insert_otp = $this->main->insert_data('login_otp_tbl', $set, TRUE);

		if($insert_otp['result'] == TRUE){
			$id = $insert_otp['id'];

			$data['id'] = $id;
			$data['code'] = $otp_code;
			$data['result'] = 1;
		}

		return $data;

	}

	public function two_factor_authentication(){
		$info = $this->_require_login();
		$parent_db   = $GLOBALS['parent_db'];

		$auth_sess = $this->session->tempdata('evoucher-otp');
		if(isset($auth_sess)){

			$user_id = decode($auth_sess);
			$check_id = $this->main->check_data("{$parent_db}.user_tbl", array('user_id' => $user_id, 'user_status' => 1), TRUE);
			if($check_id['result'] == TRUE){

				$date_now = date_now();
				$where = array(
					'user_id' => $user_id,
					'login_otp_expiration >=' => $date_now,
					'login_otp_status' => 1
				);
				$check_otp = $this->main->check_data('login_otp_tbl', $where);
				if($check_otp == TRUE){

					$data['title'] = ''.SYS_NAME.' System';
					$this->load->view('login/authentication_content', $data);
				}
			}else{
				redirect();	
			}
		}else{
			redirect();
		}
	}

	public function authenticate_otp_code(){
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			$parent_db   = $GLOBALS['parent_db'];
			$otp_code = $this->input->post('otp_code');

			$auth_sess = $this->session->tempdata('evoucher-otp');
			
			if(isset($auth_sess)){
				$attempt = $this->session->tempdata('evoucher-otp-attempts');
				if(isset($attempt)){
					if($attempt < 4){
						$attempt += 1;

						$expiration = 900; //15 mins expiration
						$attempt = $this->session->set_tempdata('evoucher-otp-attempts', $attempt, $expiration);	
					}else{

						$this->session->unset_tempdata('evoucher-otp-attempts');
						$this->session->unset_tempdata('evoucher-otp');

						$data['result'] = 2;
						$data['link'] = base_url();
						$data['msg'] = 'You exceed maximum attempts for OTP. Please try again later';

						echo json_encode($data);
						exit;
					}
				}else{
					$expiration = 900; //15 mins expiration
					$attempt = 1;
					$set_attempt = $this->session->set_tempdata('evoucher-otp-attempts', $attempt, $expiration);
					
				}

				$user_id = decode($auth_sess);
				$check_id = $this->main->check_data("{$parent_db}.user_tbl", array('user_id' => $user_id, 'user_status' => 1), TRUE);
				if($check_id['result'] == TRUE){

					$session = array(
						'user_id'      => encode($check_id['info']->user_id),
						'user_type_id' => encode($check_id['info']->user_type_id),
						'user_reset'   => $check_id['info']->user_reset,
						'user_fullname'   => $check_id['info']->user_fname.' '.$check_id['info']->user_lname,

					);

					$date_now = date_now();
					$where_otp = array(
						'user_id' => $user_id,
						'login_otp_code' => $otp_code,
						'login_otp_expiration >=' => $date_now,
						'login_otp_status' => 1
					);

					$check_otp = $this->main->check_data('login_otp_tbl', $where_otp, TRUE);
					if($check_otp['result'] == TRUE){
						$this->_store_otp_log($user_id, $otp_code);

						$this->session->set_userdata('evoucher-user', $session);

						if($check_id['info']->user_type_id == 1){
							$link = base_url('admin/');
							$data['result'] = 1;
							$data['link'] = $link;
							$data['msg'] = 'Your OTP is valid. Thank you!';

							$this->session->unset_tempdata('evoucher-otp-attempts');
							$this->session->unset_tempdata('evoucher-otp');
						}elseif($check_id['info']->user_type_id == "7"){
							$link = base_url('creator/');
							$data['result'] = 1;
							$data['link'] = $link;
							$data['msg'] = 'Your OTP is valid. Thank you!';

							$this->session->unset_tempdata('evoucher-otp-attempts');
							$this->session->unset_tempdata('evoucher-otp');
						}elseif($check_id['info']->user_type_id == "8"){
							$link = base_url('approver/');
							$data['result'] = 1;
							$data['link'] = $link;
							$data['msg'] = 'Your OTP is valid. Thank you!';

							$this->session->unset_tempdata('evoucher-otp-attempts');
							$this->session->unset_tempdata('evoucher-otp');
						}elseif($check_id['info']->user_type_id == "9"){
							$link = base_url('redeem/');
							$data['result'] = 1;
							$data['link'] = $link;
							$data['msg'] = 'Your OTP is valid. Thank you!';

							$this->session->unset_tempdata('evoucher-otp-attempts');
							$this->session->unset_tempdata('evoucher-otp');
						}elseif($check_id['info']->user_type_id == "11"){ // REDEEM VIEWING
							$link = base_url('redeem/');
							$data['result'] = 1;
							$data['link'] = $link;
							$data['msg'] = 'Your OTP is valid. Thank you!';

							$this->session->unset_tempdata('evoucher-otp-attempts');
							$this->session->unset_tempdata('evoucher-otp');
						}elseif($check_id['info']->user_type_id == "2"){ // BC RBA
							$link = base_url('redeem/');
							$data['result'] = 1;
							$data['link'] = $link;
							$data['msg'] = 'Your OTP is valid. Thank you!';

							$this->session->unset_tempdata('evoucher-otp-attempts');
							$this->session->unset_tempdata('evoucher-otp');
						}elseif($check_id['info']->user_type_id == "12"){
							$link = base_url('first-approver/');
							$data['result'] = 1;
							$data['link'] = $link;
							$data['msg'] = 'Your OTP is valid. Thank you!';

							$this->session->unset_tempdata('evoucher-otp-attempts');
							$this->session->unset_tempdata('evoucher-otp');
						}elseif($check_id['info']->user_type_id == "13"){
							$link = base_url('raffle/');
							$data['result'] = 1;
							$data['link'] = $link;
							$data['msg'] = 'Your OTP is valid. Thank you!';

							$this->session->unset_tempdata('evoucher-otp-attempts');
							$this->session->unset_tempdata('evoucher-otp');
						}else{

							$this->session->unset_tempdata('evoucher-otp-attempts');
							$this->session->unset_tempdata('evoucher-otp');

							$this->session->unset_userdata('evoucher-user');
							$this->session->sess_destroy();

							$link = base_url();
							$data['msg'] = 'Error please try again. Thank you!';
							$data['result'] = 0;
							$data['link'] = $link;
						}						
						
					}else{

						$this->_store_otp_log($user_id, $otp_code);

						$attempt = $this->session->tempdata('evoucher-otp-attempts');
						$remaining_attempt = 5 - $attempt;
						$data['result'] = 0;
						$data['msg'] = 'Invalid OTP you have ' . $remaining_attempt . ' remaining attempts. Please try again';
					}
				}else{
					$data['result'] = 0;
					$data['msg'] = 'Error please try again!';
					$data['link'];
				}
			}else{
				$data['result'] = 0;
				$data['msg'] = 'Error please try again!';
				$data['link'] = '';
			}
		}else{
			$data['result'] = 0;
			$data['msg'] = 'Error wrong method.';
		}

		echo json_encode($data);
		exit;
	}

	public function change_password($id = null)
	{
		$user_id   = decode($id);
		$parent_db = $GLOBALS['parent_db'];
		if(!empty($user_id)){
			$check_id = $this->main->check_data("{$parent_db}.user_tbl", "user_id = {$user_id} AND user_reset = 1 AND user_type_id IN (1,2,7,8,9,11,12,13)");
			if($check_id == TRUE){
				$data['user_id'] = encode($user_id);
				$data['title'] = ''.SYS_NAME.' System';
				$this->load->view('login/change_password_content', $data);
			}else{
				redirect(base_url());
			}
		}else{
			redirect(base_url());
		}
	}

	public function change_process()
	{
		if($_SERVER['REQUEST_METHOD'] != 'POST'){
			redirect(base_url());
		}

		$rules = [
			[
				'field' => 'password',
				'label' => 'Password',
				'rules' => 'required'
			],
			[
				'field' => 'repeat_password',
				'label' => 'Repeat Password',
				'rules' => 'required'
			],
		];

		$this->load->library('form_validation');
		$this->form_validation
			->set_rules($rules)
			->set_error_delimiters(
				'<div class="alert alert-danger alert-dismissible fade show" role="alert">', 
					'<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>');
		if ($this->form_validation->run() == FALSE) {
			$this->session->set_flashdata('message', validation_errors());
			redirect('login');
		}

		$user_id   = clean_data(decode($this->input->post('id')));
		$password  = clean_data($this->input->post('password'));
		$rpassword = clean_data($this->input->post('repeat_password'));

		$parent_db = $GLOBALS['parent_db'];
		$check_id  = $this->main->check_data("{$parent_db}.user_tbl", "user_id = {$user_id} AND user_reset = 1 AND user_type_id IN (1,2,7,8,9,11,12,13)");
		if($check_id == TRUE){
			if($password == $rpassword){

				$set = array(
					'user_password' => encode($password),
					'user_reset' => 0
				);

				$where  = array('user_id' => $user_id);
				$result = $this->main->update_data("{$parent_db}.user_tbl", $set, $where);

				if($result == TRUE){
					$msg = '<div class="alert alert-success">Login now with your new password.</div>';
					$this->session->set_flashdata('message', $msg);
					redirect('login');
				}else{
					$msg = '<div class="alert alert-danger">Error please try again.</div>';
					$this->session->set_flashdata('message', $msg);
					redirect('login');
				}
			}else{
				$msg = '<div class="alert alert-danger">Password not match. Please try again!</div>';
				$this->session->set_flashdata('message', $msg);
				redirect('login/change-password/' . encode($user_id));
			}
		}else{
			redirect();	
		}
	}

    private function _store_login_log($user_id)
    {
        $data = [
            'user_id'         => $user_id,
            'login_log_added' => date_now()
        ];
        $this->main->insert_data('login_log_tbl', $data);
    }

    private function _store_otp_log($user_id, $otp_code){
        
        $data = array(
            'user_id' => $user_id,
            'login_otp_logs_code' => $otp_code,
            'login_otp_logs_added' => date_now(),
            'login_otp_logs_status' => 1
        );

        $this->main->insert_data('login_otp_logs_tbl', $data);
    }

    public function view_email_otp(){
		$info = $this->_require_login();
		$parent_db   = $GLOBALS['parent_db'];

		$data['title'] = ''.SYS_NAME.' System';
		$data['otp_code'] = 'Afw31';
		$data['name'] = 'Jose';
		$this->load->view('login/email_otp_content', $data, TRUE);
		
	}

	public function otp_email_content($name, $otp_code){
		$info = $this->_require_login();
		$parent_db   = $GLOBALS['parent_db'];

		$data['title'] = ''.SYS_NAME.' System';
		$data['otp_code'] = $otp_code;
		$data['name'] = $name;
		
		$content = $this->load->view('login/email_otp_content', $data, TRUE);
		
		return $content;
	}

	private function _send_email($recipient, $email_content, $subject){

		$config = email_config();
        $this->load->library('email', $config);
        $this->email->set_newline("\r\n")
                    ->from('noreply@chookstogoinc.com.ph', ''.SYS_NAME.' Notification')
                    ->to($recipient)
                    ->subject($subject)
                    ->message($email_content);

        if($this->email->send()){
            $email_result = [
                'result'  => TRUE,
                'msg' => 'Email Sent'
            ];
            //$this->_store_email_log($email_result, $recipient);
            return $email_result;
        }else{
            $email_result = [
                'result'  => FALSE,
                'msg' => $this->email->print_debugger()
            ];
            //$this->_store_email_log($email_result, $recipient);
            return $email_result;
        }
    }
}
