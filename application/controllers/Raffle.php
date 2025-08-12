
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Raffle extends CI_Controller {

	public function __construct()
    {
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
				}elseif($user_type == "9" || $user_type == "2" || $user_type == "11"){
                    redirect('redeem');
				}elseif($user_type == "12"){
                    redirect('first-approver');
				}elseif($user_type == "13"){
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

	public function index()
    {
		$info      = $this->_require_login();
		$this->raffle_draw();
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
						'survey_ref_id'			=> $survey_ref_id,
						'ref_id'				=> $ref_id,
						'form_id'				=> $form_id,
						'coupon_id'				=> $coupon_ids[$i],
						'survey_winner_status'	=> 1,
						'created_at'			=> date('Y-m-d H:i:s'),
						'survey_winner_email'	=> '',
						'created_by'			=> $user_id,
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
        $this->load->view('raffle/templates', $data);
    }

	private function _get_participants($form_id, $order_by = FALSE, $select = FALSE){
		$sibling_db 							= sibling_one_db();
		$parent_db 								= parent_db();
		$check_form 							= $this->main->check_data("{$sibling_db}.form_tbl", ['form_id' => $form_id], TRUE);
		$start_date								= $check_form['result'] ? $check_form['info']->start_date : date("Y-m-d");
		$end_date								= $check_form['result'] ? $check_form['info']->end_date : date("Y-m-d");

		$get_participating_bcs 					= $this->main->get_data('survey_participating_bcs_tbl', ['form_id' => $form_id, 'survey_participating_bc_status' => 1]);
		$join 									= [
			"{$parent_db}.bc_tbl b" 			=> 'a.bc_id = b.bc_id'
		];
		$get_participating_bcs 					= $this->main->get_join('survey_participating_bcs_tbl a', $join, FALSE, FALSE, FALSE, 'a.*, b.bc_name', ['a.form_id' => $form_id, 'a.survey_participating_bc_status' => 1]);
		
		if(!empty($get_participating_bcs)){
			$bcs = array_column($get_participating_bcs, 'bc_id');
			$bcs = implode(',', $bcs);

			$filter									= 'status = 1 and form_id = '.$form_id.' and survey_ref_id not in (SELECT survey_ref_id from survey_winners_tbl where survey_winner_status = 1 and form_id= '.$form_id.') and created_at >= "'.$start_date.'" AND created_at <= "'.$end_date.'"';
			$join 									= [
				"{$parent_db}.provinces_tbl b" 		=> 'a.province_id = b.province_id and b.bc_id IN ('.$bcs.')',
				"{$parent_db}.town_groups_tbl c" 	=> 'a.town_group_id = c.town_group_id',
				"{$parent_db}.barangay_tbl d" 		=> 'a.barangay_id = d.barangay_id',
			];
			$participants         					= $this->main->get_join('survey_reference_tbl a', $join, FALSE, $order_by, FALSE, $select, $filter);
			
		} else {
			$participants = [];
			$bcs = [];
		}
		$result = [
			'bcs' => $bcs,
			'participants' => $participants,
		];
		return $result;
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

			echo json_encode($data);
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
					'survey_reference_tbl b' => 'a.survey_ref_id = b.survey_ref_id'
				];
				$where = [
					'a.form_id' => 5,
					'a.survey_winner_status' => 1,
					'a.survey_winner_email_result' => 0,
					'a.survey_winner_email' => '',
					'a.survey_winner_validated' => 0
				];
				$select = 'a.survey_winner_id, a.survey_ref_id, b.name, RIGHT(ref_no, 9) AS ref_no';
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
					] : null
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

			$survey_winner_id = isset($data['id']) ? clean_data($data['id']) : null;
			if(!empty($survey_winner_id)){
				$survey_ref = $this->main->get_data('survey_winners_tbl', ['survey_winner_id' => $survey_winner_id], true, 'survey_ref_id');
				$survey_ref_id = !empty($survey_ref) ? $survey_ref->survey_ref_id : null;
				$response = [];
				if (!empty($survey_ref_id)) {
					$send_winner_email = $this->email_survey_winner(5, $survey_ref_id);
					if ($send_winner_email['result']) {
						$success_msg = $send_winner_email['Message'];
						$error_msg = "";
					} else {
						$error_msg = $send_winner_email['Message'];
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

			$survey_winner_id = isset($data['id']) ? clean_data($data['id']) : null;
			if(!empty($survey_winner_id)){
				$survey_ref = $this->main->get_data('survey_winners_tbl', ['survey_winner_id' => $survey_winner_id], true, 'survey_ref_id, coupon_id');
				$survey_ref_id = !empty($survey_ref) ? $survey_ref->survey_ref_id : null;
				$response = [];
				if (!empty($survey_ref_id)) {
					
					$set = [
						'survey_winner_status' => 0,
						'survey_winner_validated' => 0,
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
			'a.survey_winner_email_result' 			=> 0,
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
}
?>
