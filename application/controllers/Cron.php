<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller {


	public function __construct()
    {
    	parent::__construct();
    	$this->load->model('main_model', 'main');
        $GLOBALS['parent_db'] = parent_db();
	}

	// public function test(){
	// 	$fruits = ['mango', 'apple', 'orange'];
	// 	$i = 3;
	// 	if( empty($fruits[$i]) || !isset($fruits[$i]) ){
	// 		echo 'yes exit';
	// 	}
	// }

	public function get_random_survey_winners($form_id, $date=null){
		
		$freebie_date 							= $date ? date('Y-m-d', strtotime($date)) : date_now("Y-m-d");
		$sibling_db 							= sibling_one_db();
		$parent_db 								= parent_db();
		$check_form 							= $this->main->check_data("{$sibling_db}.form_tbl", ['form_id' => $form_id], TRUE);
		$start_date								= $check_form['result'] ? $check_form['info']->start_date : date("Y-m-d");
		$end_date								= $check_form['result'] ? $check_form['info']->end_date : date("Y-m-d");
		$end_date								= $date || $date !== null ? date('Y-m-d', strtotime($date . ' 17:00:00')) : date('Y-m-d', strtotime($end_date . ' 23:59:59'));

		$get_participating_bcs 					= $this->main->get_data('survey_participating_bcs_tbl', ['form_id' => $form_id, 'survey_participating_bc_status' => 1]);
		$join 									= [
			"{$parent_db}.bc_tbl b" 			=> 'a.bc_id = b.bc_id'
		];
		$get_participating_bcs 					= $this->main->get_join('survey_participating_bcs_tbl a', $join, FALSE, FALSE, FALSE, 'a.*, b.bc_name', ['a.form_id' => $form_id, 'a.survey_participating_bc_status' => 1]);
		
		if(!empty($get_participating_bcs)){
			foreach($get_participating_bcs as $r){
				$bc_id = $r->bc_id;
				$bc_name = $r->bc_name;
				// $participants         					= $this->main->get_data('survey_reference_tbl a', $filter);
				// $filter								= ['status' => 1];
				$filter									= 'status = 1 and form_id = '.$form_id.' and survey_ref_id not in (SELECT survey_ref_id from survey_winners_tbl where survey_winner_status = 1 and form_id= '.$form_id.') and created_at >= "'.$start_date.'" AND created_at <= "'.$end_date.'"';
				
				$join 									= [
					"{$parent_db}.provinces_tbl b" 		=> 'a.province_id = b.province_id and b.bc_id = '.$bc_id
				];
				$participants         					= $this->main->get_join('survey_reference_tbl a', $join, FALSE, FALSE, FALSE, 'a.*', $filter);
				$participants 							= array_column($participants, 'ref_id', 'survey_ref_id');
				$participants_count						= count($participants);
		
				
				
				
				if ($participants_count < 1) {
					//* insert to cron logs
					$cron_msg = "[".$form_id."] | Not enough participants ( ".$participants_count." ) for BC of ".$bc_name;
				} else {
					$brand_id = $form_id == 3 ? 1 : 2;
					// $freebie_details         = $this->main->get_data('survey_freebie_calendar_tbl a', ['survey_freebie_cal_status' => 1, 'is_awarded' => 0, 'freebie_date' => date_now("Y-m-d"), 'brand_id' => $brand_id]);
					$join 									= [
						'coupon_bc_tbl b' 		=> 'a.coupon_id = b.coupon_id and b.coupon_bc_status = 1 and b.bc_id = '.$bc_id
					];
					$where_freebie = ['survey_freebie_cal_status' => 1, 'form_id' => $form_id, 'is_awarded' => 0, 'freebie_date' => $freebie_date, 'brand_id' => $brand_id];
					$freebie_details         = $this->main->get_join('survey_freebie_calendar_tbl a', $join, FALSE, FALSE, FALSE, 'a.*', $where_freebie);
					
					$freebie_count = !empty($freebie_details) ? count($freebie_details) : 0;
					$coupon_ids = array_column($freebie_details, 'coupon_id');
					
					
					if($freebie_count > 0 && $freebie_count < 4){
						if($participants_count > $freebie_count){
							//* Shuffle and pick 3 random winners
							$random_keys = array_rand($participants, $freebie_count);
						} else {
							//* auto pick winners
							$random_keys = array_rand($participants, $participants_count);
							// $cron_msg = "Participants count (".$participants_count.") is less than the freebie count (".$freebie_count.")!";
						}
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
								'created_at'			=> $freebie_date . ' ' . date('H:i:s'),
								'survey_winner_email'	=> '',
							];
							$this->main->insert_data('survey_winners_tbl', $set, TRUE);
		
							$set = [
								'is_awarded' => 1,
							];
							$where  = ['coupon_id' => $coupon_ids[$i]];
							$this->main->update_data('survey_freebie_calendar_tbl', $set, $where);
		
							$i++;
						}
						$cron_msg = "[".$form_id."] | Successfully inserted winners (".implode(',', $winners).") for BC of ".$bc_name;
						
					} else {
						$cron_msg = "[".$form_id."] | Invalid freebie count found ( ".$freebie_count." ) for BC of ".$bc_name;
					}
			
				}
		
				$set = [
					'cron_log_type'						=> 1,
					'cron_log_message'					=> $cron_msg,
					'cron_log_added'					=> date_now(),
					'cron_log_status'					=> 1
				];
				$this->main->insert_data('cron_logs_tbl', $set, TRUE);
			}
		}
	}

	public function email_survey_winners($form_id = 0){
		$parent_db = $GLOBALS['parent_db'];
		$join        = [
			'survey_reference_tbl b'			=> 'b.survey_ref_id = a.survey_ref_id AND a.form_id = ' . $form_id.' and survey_winner_email_result = 0 and survey_winner_status = 1',
			'coupon_tbl c'						=> 'a.coupon_id = c.coupon_id AND c.coupon_status = 1',
			'coupon_prod_sale_tbl d'			=> 'c.coupon_id = d.coupon_id AND c.coupon_status = 1',
			"{$parent_db}.product_sale_tbl e"	=> 'd.prod_sale_id = e.prod_sale_id',
			"{$parent_db}.provinces_tbl f"		=> 'b.province_id = f.province_id',
			"{$parent_db}.bc_tbl g"				=> 'f.bc_id = g.bc_id'
		];
		$select = 'a.survey_winner_id, b.name, b.email, c.coupon_code, c.coupon_end, e.prod_sale_promo_name, g.bc_name';
        $winners      = $this->main->get_join('survey_winners_tbl a', $join, FALSE, FALSE, FALSE, $select);

		if($form_id == 3){
			$brand_name = 'Chooks To Go';
			// $brand_logo = 'chooks-logo-transparent.png';
			$brand_logo = 'CTG-Digital-Logo.png';
			$brand_bg_color = '#ff0000';
			$brand_color = '#fff';
			// $greetings = '<strong><span style="font-size:16px;color:yellow">CONGRA</span><span style="font-size:16px;color:red">CHOOKS</span><span style="font-size:16px;color:yellow">LATIONS!</span></strong>';
			$greetings = '<strong><span style="font-size:16px;color:#0c0d0d">CONGRATULATIONS!</span></strong>';
		} else {
			$brand_name = 'Uling Roasters';
			$brand_logo = 'Uling-Roasters-Logo-transparent.png';
			// $brand_bg_color = '#cccc00';
			$brand_bg_color = '#ffff00';
			$brand_color = '#111';
			$greetings = '<strong><span style="font-size:16px;color:#331a00">CONGRATULATIONS!</span></strong>';
		}

		if(!empty($winners)){
			foreach($winners as $winner){
				if($winner->email == ''){
					$email_result = [
						'result'					=> FALSE,
						'Message'					=> 'Parameter Empty'
					];
					
					$this->_store_email_log($email_result, $winner->email);
					continue;
				}
				$subject = $brand_name.' QR Promo Winner Notification';
				$data['name'] =  $winner->name;
				$data['coupon_code']  = $winner->coupon_code;
				$data['prod_sale_promo_name'] = $winner->prod_sale_promo_name;
				$data['brand_name'] = $brand_name;
				$data['brand_logo'] = $brand_logo;
				$data['brand_color'] = $brand_color;
				$data['greetings'] = $greetings;
				$data['brand_bg_color'] = $brand_bg_color;
				$data['coupon_end'] = $winner->coupon_end;
				$data['bc_name'] = $winner->bc_name;

				$message = $this->load->view('email/email_survey_winner_content', $data, TRUE);
				// echo $message;
				// exit;
				$email_result = $this->_send_email($winner->email, $subject, $message, $brand_name);
				
	
				$set = [
					'survey_winner_email' 			=> $email_result['Message'],
					'survey_winner_email_result' 	=> $email_result['result'],
				];
				$where  = ['survey_winner_id' => $winner->survey_winner_id];
				$this->main->update_data('survey_winners_tbl', $set, $where);

				$email_sending_result = $email_result['result'] ? "Successfully sent" : "Not successfully sent";
	
				$cron_msg = "[".$form_id."] | ".$email_sending_result." email to ".$winner->email;

				$set = [
					'cron_log_type'					=> 2,
					'cron_log_message'				=> $cron_msg,
					'cron_log_added'				=> date_now(),
					'cron_log_status'				=> 1
				];
				$this->main->insert_data('cron_logs_tbl', $set, TRUE);
			}
		}

		
	}

	private function _store_email_log($email_result, $recipient)
    {
        $data = [
            'email_notif_log_message'   		=> $email_result['Message'],
            'email_notif_log_result'    		=> $email_result['result'],
            'email_notif_log_recipient' 		=> $recipient,
            'email_notif_log_added'     		=> date_now(),
        ];
        $result = $this->main->insert_data('email_notif_log_tbl', $data, TRUE);
    }

	private function _send_email($recipient ,$subject ,$message, $brand_name)
    {
        if(empty($recipient) || empty($subject) || empty($message)) {
            $email_result = [
                'result'  						=> FALSE,
                'Message' 						=> 'Parameter Empty'
            ];
            $this->_store_email_log($email_result, $recipient);
            return $email_result;
        }

        
		$config = email_config();
        $this->load->library('email', $config);
        $this->email->set_newline("\r\n")
                    ->from('noreply@chookstogoinc.com.ph', $brand_name.' QR Promo Notification')
                    ->to($recipient)
					// ->bcc('akatok@chookstogoinc.com.ph')
                    ->subject($subject)
                    ->message($message);
        if($this->email->send()){
            $email_result = [
                'result' 						=> TRUE,
                'Message' 						=> 'Email Sent'
            ];
            $this->_store_email_log($email_result, $recipient);
            return $email_result;
        }else{
            $email_result = [
                'result' 						=> FALSE,
                'Message' 						=> $this->email->print_debugger()
            ];

            // print_r($email_result);
            // exit;
            $this->_store_email_log($email_result, $recipient);
            return $email_result;
        }
    }

	private function _normalize_names(){
		$full_name = 'Aljune K. Atok';

		$full_name = strtolower($full_name);

		//* Remove punctuation
		$full_name = preg_replace("/[^\w\s]/", "", $full_name);

		//* Remove extra spaces
		$full_name = preg_replace("/\s+/", " ", $full_name);
		$full_name = trim($full_name);

		//* Split into words
		$parts = explode(" ", $full_name);

		$new_name = '';
		foreach($parts as $part){
			$new_name .= $part;
		}

		$new_name = preg_replace("/\s+/", " ", $full_name);
		$new_name = trim($new_name);
		$new_name = ucwords($new_name);

		echo $new_name;
	}
}
