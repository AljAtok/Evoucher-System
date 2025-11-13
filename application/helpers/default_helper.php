<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('encode')){

	// function encode($data){
	// 	$instanceName =& get_instance();
	// 	$instanceName->load->library('encrypt');
	// 	$id = $instanceName->encrypt->encode($data);
	// 	$encode_id= strtr(
	// 	    $id,
	// 	    array(
	//             '+' => '.',
	//             '=' => '-',
	// 	        '/' => '~'
	// 	    )
	//     );
	//    	return $encode_id;
	// }

	// function decode($data){
	// 	$instanceName =& get_instance();
	// 	$instanceName->load->library('encrypt');
	// 	$decode_id= strtr(
	// 	    $data,
	// 	    array(
	//             '.' => '+',
	//             '-' => '=',
	// 	        '~' => '/'
	// 	    )
	//     );
	//    	$id = $instanceName->encrypt->decode($decode_id);
	//    	return $id;
	// }

	function encode($token){
		$cipher_method = 'aes-128-ctr';
	  	$enc_key = 'jonel';
	  	$enc_iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher_method));
	  	$crypted_token = openssl_encrypt($token, $cipher_method, $enc_key, 0, $enc_iv) . "::" . bin2hex($enc_iv);
	  	unset($token, $cipher_method, $enc_key, $enc_iv);
		
		$encode_id= strtr(
		    $crypted_token,
		    array(
	            '+' => '.',
	            '=' => '-',
		        '/' => '~'
		    )
	    );

	   	return $encode_id;
	}

	function decode($token){
		if(!empty($token)){
			$enc_key = 'jonel';
			$decode_id= strtr(
			    $token,
			    array(
		            '.' => '+',
		            '-' => '=',
			        '~' => '/'
			    )
		    );

			if(count(explode("::", $decode_id)) > 1){
				list($crypted_token, $enc_iv) = explode("::", $decode_id);
			}else{
				list($crypted_token, $enc_iv) = array($decode_id, '');
			}

		  	$crypted_token = $crypted_token;
			$cipher_method = 'aes-128-ctr';
			$token = openssl_decrypt($crypted_token, $cipher_method, $enc_key, 0, hex2bin($enc_iv));
			unset($crypted_token, $cipher_method, $enc_key, $enc_iv);
		}
		
		return $token;
	}

    function base64url_encode($data)
    { 
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); 
    } 
    
    function base64url_decode($data)
    { 
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)); 
    } 
    //edits ends here

	function clean_data($data){
		$instanceName =& get_instance();
		$instanceName->load->helper('security');
		$clean = $instanceName->security->xss_clean($instanceName->db->escape_str($data));
		return $clean;
	}

	function generate_random($length){
		$random = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
		return $random;
	}

	function generate_random_coupon($length){
		/*$random = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);*/
		//Remove 0, 1, i, l, o, I, L, O,
		$random = substr(str_shuffle("23456789ABCDEFGHJKMNPQRSTUVWXYZ"), 0, $length);
		return $random;
	}

	function create_id($format, $count){
		
		if($count > 0 && $count < 10){
			$id = $format . '00000' . $count;
		}elseif($count >= 10 && $count <= 99){
			$id = $format . '0000' . $count;
		}elseif($count >= 100 && $count <= 999){
			$id = $format . '000' . $count;
		}elseif($count >= 1000 && $count <= 9999){
			$id = $format . '00' . $count;
		}elseif($count >= 10000 && $count <= 99999){
			$id = $format . '0' . $count;
		}else{
			$id = $format . $count;
		}

		return $id;
	}

	

	function check_num($num){
		if(!is_null($num)){
			return $num;
		}else{
			return 0;
		}
	}

	function check_null($num){
		if(!is_null($num) || $num == 0){
			return $num;
		}else{
			return null;
		}
	}

	function check_array($var){
			return $var;
		if(isset($var)){
		}else{
			return 0;
		}
	}

	function convert_num($value){
		if($value >= 1000000000){
			$value = $value/1000000000;
			$value = number_format($value, 2) . ' B';
		}else if($value >= 1000000 && $value < 1000000000){
			$value = $value/1000000;
			$value = number_format($value, 2) . ' M';
		}else if($value > 1000 && $value < 1000000){
			$value = $value/1000;
			$value = number_format($value) . ' K';
		}else if($value > 99 && $value < 999){
			$value = $value/1000;
			$value = number_format($value, 2) . ' K';
		}else{
			 $value = '';
		}
		return $value;
    }
    

    function itexmo($number,$message,$apicode){
		$url = 'https://www.itexmo.com/php_api/api.php';
		$itexmo = array('1' => $number, '2' => $message, '3' => $apicode, 'passwd' => 'vm!3175)w!', '6' => 'CHOOKS');
		$param = array(
		    'http' => array(
		        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		        'method'  => 'POST',
		        'content' => http_build_query($itexmo),
		    ),
		);
		$context  = stream_context_create($param);
		return file_get_contents($url, false, $context);
	}

	function parent_db(){
		$db_name = PARENT_DB;
		return $db_name;
	}

	function send_sms_old($number,$message,$apicode, $sender){
		$url = 'https://www.itexmo.com/php_api/api.php';
		$itexmo = array('1' => $number, '2' => $message, '3' => $apicode, 'passwd' => 'vm!3175)w!', '6' => $sender);
		$param = array(
		    'http' => array(
		        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		        'method'  => 'POST',
		        'content' => http_build_query($itexmo),
		    ),
		);
		$context  = stream_context_create($param);
		return file_get_contents($url, false, $context);
	}

	function send_sms($number, $message, $apicode, $sender){
		
		ini_set('max_execution_time', 0);

		$error_count = 0;
		$curl_flag = TRUE;
		$retry_count = 10;

		while($curl_flag == TRUE){

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://api.itexmo.com/api/broadcast-otp',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS =>'{
					"Email": "jtbelandres@bountyagro.com.ph",
					"Password": "temppass123",
					"Recipients": [ "' . $number . '"],
					"Message": "' . $message . '",
					"ApiCode": "' . $apicode . '",
					"SenderId": "' . $sender . '"
				}',
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json',
					'Authorization: Basic' . base64_encode("jtbelandres@bountyagro.com.ph:temppass123")
				),
			));

			$response = curl_exec($curl);
			

			if (!curl_errno($curl)) {
				curl_close($curl);    
				$ci =& get_instance();
				$ci->load->model('main_model', 'main');

				$set = array(
					'sms_logs_recipient' => $number,
					'sms_logs_message' => $message,
					'sms_logs_response' => $response,
					'sms_logs_added' => date('Y-m-d H:i:s'),
					'sms_logs_status' => 1
				);
				$ci->main->insert_data('sms_logs_tbl', $set);


				$response = json_decode($response);

				
				
				$status = $response->Error;
				if($status == '' || $status == FALSE){
					$response_status = TRUE;
				}elseif($status == 'true'){
					$error = $response->Message;
					$response_status = '';
				}

				$curl_flag = FALSE;
			}else{
				$error_msg = curl_error($curl);
				curl_close($curl);

				$ci =& get_instance();
				$ci->load->model('main_model', 'main');

				$set = array(
					'sms_logs_recipient' => $number,
					'sms_logs_message' => $message,
					'sms_logs_response' => $error_msg,
					'sms_logs_added' => date('Y-m-d H:i:s'),
					'sms_logs_status' => 1
				);
				$ci->main->insert_data('sms_logs_tbl', $set);

				
				$response_status = '';

				if($error_count <= $retry_count){
					$curl_flag = TRUE;
					$error_count++;
					sleep(2);
				}else{
					$curl_flag = FALSE;
				}
			}
		}

		return $response_status;
	}

	function email_config(){
		// $config = Array(
        //     'protocol'    => 'smtp',
        //     'smtp_host'   => 'smtp.zoho.com',
        //     'smtp_crypto' => 'tls',
        //     'smtp_port'   => 587,
        //     'smtp_user'   => 'no-reply@chookstogodelivery.com',
        //     'smtp_pass'   => 'u4HEDahxXnaR',
        //     'mailtype'    => 'html',
        //     'charset'     => 'iso-8859-1',
        //     /*'smptp_auth' => TRUE,*/
        // );
		// $config = [ 
        //     'protocol'  => 'smtp',
        //     'smtp_host' => 'ssl://server10.synermaxx.net',
        //     'smtp_port' => 465,
        //     'smtp_user' => 'alerts@bountyagro.com.ph',
        //     'smtp_pass' => '',
        //     'mailtype'  => 'html',
        //     'charset'   => 'utf-8',
        //     'wordwrap'  => TRUE
        // ];
		$config = Array(
		    'protocol' => 'smtp',
		    'smtp_host' => 'ssl://smtp.gmail.com',
		    'smtp_port' => 465,
		    'smtp_user' => 'noreply@chookstogoinc.com.ph',
		    'smtp_pass' => 'vmov kifv vqjn jkpd',
		    'mailtype'  => 'html', 
		    // 'charset'   => 'iso-8859-1'
		    'charset'   => 'UTF-8'
		);

		return $config;
	}

	function decimal_format($num, $dec_places=2){
		if($num == '' || $num <= 0){
			if($num < 0){
				//return '('.number_format(-$num,$dec_places,'.',',').')';
				return number_format($num,$dec_places,'.',',');
			} else {
				return '-';
			}
		}else{	
			return number_format($num,$dec_places,'.',',');
		}
	}
	
	function decimal_format_new($num, $dec_places=2){
		if($num == '' || $num <= 0){
			if($num < 0){
				//return '('.number_format(-$num,$dec_places,'.',',').')';
				return number_format($num,$dec_places,'.','');
			} else {
				return '-';
			}
		}else{	
			return number_format($num,$dec_places,'.','');
		}
	}

	function gift_and_paid_category(){
		$coupon_cat_id = [1, 3, 5, 6]; // FOR GIFT AND PAID CATEGORY
		return $coupon_cat_id;
	}
	
	function paid_category(){
		// $coupon_cat_id = ['3', '5']; // FOR PAID CATEGORY
		$coupon_cat_id = [3, 5]; // FOR PAID CATEGORY
		return $coupon_cat_id;
	}

	function add_date($date, $years = 0, $months = 0, $days = 0, $hours = 0, $minutes = 0, $seconds = 0, $format = "m/d/Y") {
		$dateTime = new DateTime($date);
		$dateTime->modify("+" . $years . " years");
		$dateTime->modify("+" . $months . " months");
		$dateTime->modify("+" . $days . " days");
		$dateTime->modify("+" . $hours . " hours");
		$dateTime->modify("+" . $minutes . " minutes");
		$dateTime->modify("+" . $seconds . " seconds");
	
		return $dateTime->format($format); // Or any desired format
	}

	function pretty_dump($data){
		echo '<pre>';
        print_r($data);
        echo '</pre>';
		exit;
	}

	function date_now($format = 'Y-m-d H:i:s'){
		$date = date($format);
		return $date;
	}
	
	function date_display($myTS, $format = 'Y-m-d H:i'){
        if($myTS){
            $sd = date($format,strtotime($myTS));
        } else {
            $sd = NULL;
        }
        return $sd;
    }

	function coming_shortly(){
		return '<i class="fas fa-spinner fa-spin text-danger"></i> Coming shortly...';
	}

	function sibling_one_db(){
		$db_name = 'chooksurvey';
		return $db_name;
	}

	/**
	 * Returns the action buttons HTML for a transaction, based on access type and transaction state.
	 * Supports dynamic extension for other access types.
	 * If $params['one_liner'] is true, renders each menu item as one line with description.
	 */
	function action_buttons($params) {
		$coupon_transaction_header_id = $params['coupon_transaction_header_id'];
		$access_type = $params['access_type'];
		$one_liner = isset($params['one_liner']) ? $params['one_liner'] : false;

		$generic_btns = generic_actions($coupon_transaction_header_id, $one_liner, $access_type);

		$access_handlers = [
			$access_type => $access_type.'_action_buttons',
		];

		if (isset($access_handlers[$access_type])) {
			$handler = $access_handlers[$access_type];
			$dropdown_items = $handler($params, $generic_btns, $one_liner);
		} else {
			// Default: show only view details for unknown access
			$dropdown_items = $one_liner
				? dropdown_item($generic_btns['view_details_button'], 'View transaction details')
				: '
				<div class="action-menu px-2 py-1">
					<div class="d-flex align-items-center mb-2 gap-2">
						' . $generic_btns['view_details_button'] . '
					</div>
				</div>';
		}

		$dropdown = '
			<div class="dropdown">
				<button class="btn btn-light btn-xs dropdown-toggle py-0 px-1" type="button" id="actionDropdown' . $coupon_transaction_header_id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="font-size: 0.8rem; line-height: 1;">
					<i class="fas fa-sliders-h"></i>
				</button>
				<div class="dropdown-menu dropdown-menu-right shadow-lg" aria-labelledby="actionDropdown' . $coupon_transaction_header_id . '">
					' . $dropdown_items . '
				</div>
			</div>';
		return $dropdown;
	}

	/**
	 * Handler for admin access type.
	 * Returns HTML for dropdown items based on transaction type.
	 * Supports one_liner param for one item per line with description.
	 */
	function admin_action_buttons($params, $generic_btns, $one_liner = false) {
		$coupon_transaction_header_id = $params['coupon_transaction_header_id'];
		$coupon_cat_id = $params['coupon_cat_id'];
		$transaction_type = $params['transaction_type'];

		switch ($transaction_type) {
			case 'pending':
				$btns = pending_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['edit_button'], 'Edit Transaction') .
						dropdown_item($generic_btns['duplicate_button'], 'Duplicate Transaction') .
						dropdown_item($generic_btns['cancel_button'], 'Cancel Transaction') .
						dropdown_item($generic_btns['view_details_button'], 'View Details') .
						dropdown_item($btns['appr_btn'], 'Approve Transaction');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-1 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['edit_button'] . '
							' . $generic_btns['cancel_button'] . '
							' . $generic_btns['view_details_button'] . '
							' . $btns['appr_btn'] . '
						</div>
					</div>';
			case 'first-approved':
				$btns = first_approve_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						dropdown_item($btns['return_btn'], 'Return to Pending') .
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['edit_button'], 'Edit Transaction') .
						dropdown_item($btns['edit_appr_btn'], 'Edit Approval') .
						dropdown_item($generic_btns['cancel_button'], 'Cancel Transaction') .
						dropdown_item($generic_btns['view_details_button'], 'View Details') .
						dropdown_item($generic_btns['toggle'], 'Deactivate Transaction') .
						dropdown_item($btns['appr_btn'], 'Approve Transaction');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . $btns['return_btn'] . '
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['edit_button'] . '
							' . $btns['edit_appr_btn'] . '
						</div>
						<div class="dropdown-divider"></div>
						<div class="d-flex align-items-center mb-0 gap-2">
							' . $generic_btns['cancel_button'] . '
							' . $generic_btns['view_details_button'] . '
							' . $generic_btns['toggle'] . '
							' . $btns['appr_btn'] . '
						</div>
					</div>';
			case 'second-approved':
				$payment_status = $params['payment_status'];
				$btns = second_approve_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						dropdown_item($btns['return_btn'], 'Return to Treasury Approved') .
						($payment_status == 0 ? dropdown_item($btns['payment_btn'], 'Pay Transaction') : '') .
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['edit_button'], 'Edit Transaction') .
						dropdown_item($btns['edit_appr_btn'], 'Edit Approval') .
						dropdown_item($generic_btns['cancel_button'], 'Cancel Transaction') .
						dropdown_item($generic_btns['view_details_button'], 'View Details') .
						dropdown_item($generic_btns['toggle'], 'Deactivate Transaction') .
						dropdown_item($btns['appr_btn'], 'Publish Transaction');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . $btns['return_btn'] . '
							' . ($payment_status == 0 ? $btns['payment_btn'] : '') . '
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['edit_button'] . '
							' . $btns['edit_appr_btn'] . '
						</div>
						<div class="dropdown-divider"></div>
						<div class="d-flex align-items-center mb-0 gap-2">
							' . $generic_btns['cancel_button'] . '
							' . $generic_btns['view_details_button'] . '
							' . $generic_btns['toggle'] . '
							' . $btns['appr_btn'] . '
						</div>
					</div>';
			case 'active':
				$payment_status = $params['payment_status'];
				$coupon_pdf_archived = $params['coupon_pdf_archived'];
				$coupon_for_printing = $params['coupon_for_printing'];
				$coupon_for_image_conv = $params['coupon_for_image_conv'];
				$btns = active_actions($coupon_transaction_header_id, $one_liner, $params['access_type']);

				$regenerate_pdf_btn = '';
				$return_btn = '';
				$archive_btn = '';
				$export_bulk_pdf_for_printing_btn = '';
				$export_zipped_jpg_imgs = '';
				if ($coupon_pdf_archived == 1) {
					$mid_btns = $btns['regenerate_pdf_btn'];
					$regenerate_pdf_btn = $btns['regenerate_pdf_btn'];
					
				} else {
					$mid_btns = $btns['return_btn'] . $btns['archive_btn'];
					$return_btn = $btns['return_btn'];
					$archive_btn = $btns['archive_btn'];
					if ($coupon_for_printing == 1) {
						$mid_btns .= $btns['export_bulk_pdf_for_printing_btn'];
						$export_bulk_pdf_for_printing_btn = $btns['export_bulk_pdf_for_printing_btn'];
					}
					if ($coupon_for_image_conv == 1) {
						$mid_btns .= $btns['export_zipped_jpg_imgs'];
						$export_zipped_jpg_imgs = $btns['export_zipped_jpg_imgs'];
					}
				}
				if ($one_liner) {
					return
						($regenerate_pdf_btn ? dropdown_item($regenerate_pdf_btn, 'Regenerate PDF') : '') .
						($return_btn ? dropdown_item($return_btn, 'Return to Finance Approved') : '') .
						($archive_btn ? dropdown_item($archive_btn, 'Archive PDF') : '') .
						($export_bulk_pdf_for_printing_btn ? dropdown_item($export_bulk_pdf_for_printing_btn, 'Export Bulk Pdf For Printing') : '') .
						($export_zipped_jpg_imgs ? dropdown_item($export_zipped_jpg_imgs, 'Export Zipped JPG Images') : '') .
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['edit_button'], 'Edit Transaction') .
						dropdown_item($generic_btns['duplicate_button'], 'Duplicate Transaction') .
						dropdown_item($generic_btns['cancel_button'], 'Cancel Transaction') .
						dropdown_item($generic_btns['view_details_button'], 'View Details') .
						dropdown_item($generic_btns['export_trans_details_btn'], 'Export Transaction Details') .
						($coupon_pdf_archived == 0 ? dropdown_item($btns['download_zipped_pdf_btn'], 'Download Zipped PDF') : '') .
						dropdown_item($generic_btns['toggle'], 'Deactivate Transaction');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . $mid_btns . '
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['edit_button'] . '
						</div>
						<div class="dropdown-divider"></div>
						<div class="d-flex align-items-center mb-0 gap-2">
							' . $generic_btns['cancel_button'] . '
							' . $generic_btns['view_details_button'] . '
							' . $generic_btns['export_trans_details_btn'] . '
							' . ($coupon_pdf_archived == 0 ? $btns['download_zipped_pdf_btn'] : '') . '
							' . $generic_btns['toggle'] . '
						</div>
					</div>';
			case 'inactive':
				$btns = inactive_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['edit_button'], 'Edit Transaction') .
						dropdown_item($generic_btns['view_details_button'], 'View Details') .
						dropdown_item($generic_btns['export_trans_details_btn'], 'Export Transaction Details') .
						dropdown_item($btns['toggle'], 'Activate Transaction');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['edit_button'] . '
							' . $generic_btns['view_details_button'] . '
							' . $generic_btns['export_trans_details_btn'] . '
							' . $btns['toggle'] . '
						</div>
					</div>';
			default:
				if ($one_liner) {
					return dropdown_item($generic_btns['view_details_button'], 'View transaction details');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . $generic_btns['view_details_button'] . '
						</div>
					</div>';
		}
	}
	
	function creator_action_buttons($params, $generic_btns, $one_liner = false) {
		$coupon_transaction_header_id = $params['coupon_transaction_header_id'];
		$coupon_cat_id = $params['coupon_cat_id'];
		$transaction_type = $params['transaction_type'];

		switch ($transaction_type) {
			case 'pending':
				$btns = pending_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['edit_button'], 'Edit Transaction') .
						dropdown_item($generic_btns['view_details_button'], 'View Details');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-1 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['edit_button'] . '
							' . $generic_btns['cancel_button'] . '
							' . $generic_btns['view_details_button'] . '
						</div>
					</div>';
			case 'first-approved':
				$btns = first_approve_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['edit_button'], 'Edit Transaction') .
						dropdown_item($generic_btns['view_details_button'], 'View Details') .
						dropdown_item($generic_btns['toggle'], 'Deactivate Transaction');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['edit_button'] . '
							' . $generic_btns['cancel_button'] . '
						</div>
						<div class="dropdown-divider"></div>
						<div class="d-flex align-items-center mb-0 gap-2">
							' . $generic_btns['view_details_button'] . '
							' . $generic_btns['toggle'] . '
						</div>
					</div>';
			case 'second-approved':
				$payment_status = $params['payment_status'];
				$btns = second_approve_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['edit_button'], 'Edit Transaction') .
						dropdown_item($generic_btns['view_details_button'], 'View Details') .
						dropdown_item($generic_btns['toggle'], 'Deactivate Transaction') .
						dropdown_item($btns['appr_btn'], 'Publish Transaction');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['edit_button'] . '
							' . $generic_btns['cancel_button'] . '
						</div>
						<div class="dropdown-divider"></div>
						<div class="d-flex align-items-center mb-0 gap-2">
							' . $generic_btns['view_details_button'] . '
							' . $generic_btns['toggle'] . '
							' . $btns['appr_btn'] . '
						</div>
					</div>';
			case 'active':
				$payment_status = $params['payment_status'];
				$coupon_pdf_archived = $params['coupon_pdf_archived'];
				$coupon_for_printing = $params['coupon_for_printing'];
				$coupon_for_image_conv = $params['coupon_for_image_conv'];
				$btns = active_actions($coupon_transaction_header_id, $one_liner, $params['access_type']);

				$regenerate_pdf_btn = '';
				$return_btn = '';
				$archive_btn = '';
				$export_bulk_pdf_for_printing_btn = '';
				$export_zipped_jpg_imgs = '';
				if ($coupon_pdf_archived == 1) {
					$mid_btns = $btns['regenerate_pdf_btn'];
					$regenerate_pdf_btn = $btns['regenerate_pdf_btn'];
					
				} else {
					$mid_btns = $btns['return_btn'] . $btns['archive_btn'];
					$return_btn = $btns['return_btn'];
					$archive_btn = $btns['archive_btn'];
					if ($coupon_for_printing == 1) {
						$mid_btns .= $btns['export_bulk_pdf_for_printing_btn'];
						$export_bulk_pdf_for_printing_btn = $btns['export_bulk_pdf_for_printing_btn'];
					}
					if ($coupon_for_image_conv == 1) {
						$mid_btns .= $btns['export_zipped_jpg_imgs'];
						$export_zipped_jpg_imgs = $btns['export_zipped_jpg_imgs'];
					}
				}
				if ($one_liner) {
					return
						($return_btn ? dropdown_item($return_btn, 'Return to Finance Approved') : '') .
						($export_bulk_pdf_for_printing_btn ? dropdown_item($export_bulk_pdf_for_printing_btn, 'Export Bulk Pdf For Printing') : '') .
						($export_zipped_jpg_imgs ? dropdown_item($export_zipped_jpg_imgs, 'Export Zipped JPG Images') : '') .
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						
						dropdown_item($generic_btns['view_details_button'], 'View Details') .
						dropdown_item($generic_btns['export_trans_details_btn'], 'Export Transaction Details') .
						($coupon_pdf_archived == 0 ? dropdown_item($btns['download_zipped_pdf_btn'], 'Download Zipped PDF') : '');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . $mid_btns . '
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['edit_button'] . '
						</div>
						<div class="dropdown-divider"></div>
						<div class="d-flex align-items-center mb-0 gap-2">
							' . $generic_btns['cancel_button'] . '
							' . $generic_btns['view_details_button'] . '
							' . $generic_btns['export_trans_details_btn'] . '
							' . ($coupon_pdf_archived == 0 ? $btns['download_zipped_pdf_btn'] : '') . '
							' . $generic_btns['toggle'] . '
						</div>
					</div>';
			case 'inactive':
				$btns = inactive_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['edit_button'], 'Edit Transaction') .
						dropdown_item($generic_btns['view_details_button'], 'View Details');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['edit_button'] . '
							' . $generic_btns['view_details_button'] . '
						</div>
					</div>';
			default:
				if ($one_liner) {
					return dropdown_item($generic_btns['view_details_button'], 'View transaction details');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . $generic_btns['view_details_button'] . '
						</div>
					</div>';
		}
	}
	
	function first_approver_action_buttons($params, $generic_btns, $one_liner = false) {
		$coupon_transaction_header_id = $params['coupon_transaction_header_id'];
		$coupon_cat_id = $params['coupon_cat_id'];
		$transaction_type = $params['transaction_type'];

		switch ($transaction_type) {
			case 'pending':
				$btns = pending_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($btns['appr_btn'], 'Approve Transaction');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-1 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $btns['appr_btn'] . '
						</div>
					</div>';
			case 'first-approved':
				$btns = first_approve_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						dropdown_item($btns['return_btn'], 'Return to Pending') .
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['view_details_button'], 'View Details');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . $btns['return_btn'] . '
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['view_details_button'] . '
						</div>
					</div>';
			case 'second-approved':
				$payment_status = $params['payment_status'];
				$btns = second_approve_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['view_details_button'], 'View Details');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['view_details_button'] . '
						</div>
					</div>';
			case 'active':
				$payment_status = $params['payment_status'];
				$coupon_pdf_archived = $params['coupon_pdf_archived'];
				$coupon_for_printing = $params['coupon_for_printing'];
				$coupon_for_image_conv = $params['coupon_for_image_conv'];
				$btns = active_actions($coupon_transaction_header_id, $one_liner, $params['access_type']);

				$regenerate_pdf_btn = '';
				$return_btn = '';
				$archive_btn = '';
				$export_bulk_pdf_for_printing_btn = '';
				$export_zipped_jpg_imgs = '';
				if ($coupon_pdf_archived == 1) {
					$mid_btns = $btns['regenerate_pdf_btn'];
					$regenerate_pdf_btn = $btns['regenerate_pdf_btn'];
					
				} else {
					$mid_btns = $btns['return_btn'] . $btns['archive_btn'];
					$return_btn = $btns['return_btn'];
					$archive_btn = $btns['archive_btn'];
					if ($coupon_for_printing == 1) {
						$mid_btns .= $btns['export_bulk_pdf_for_printing_btn'];
						$export_bulk_pdf_for_printing_btn = $btns['export_bulk_pdf_for_printing_btn'];
					}
					if ($coupon_for_image_conv == 1) {
						$mid_btns .= $btns['export_zipped_jpg_imgs'];
						$export_zipped_jpg_imgs = $btns['export_zipped_jpg_imgs'];
					}
				}
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['view_details_button'], 'View Details');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['view_details_button'] . '
						</div>
					</div>';
			case 'inactive':
				$btns = inactive_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['view_details_button'], 'View Details');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['view_details_button'] . '
						</div>
					</div>';
			default:
				if ($one_liner) {
					return dropdown_item($generic_btns['view_details_button'], 'View transaction details');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . $generic_btns['view_details_button'] . '
						</div>
					</div>';
		}
	}
	
	function approver_action_buttons($params, $generic_btns, $one_liner = false) {
		$coupon_transaction_header_id = $params['coupon_transaction_header_id'];
		$coupon_cat_id = $params['coupon_cat_id'];
		$transaction_type = $params['transaction_type'];

		switch ($transaction_type) {
			case 'pending':
				$btns = pending_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-1 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
						</div>
					</div>';
			case 'first-approved':
				$btns = first_approve_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($btns['appr_btn'], 'Approve Transaction');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $btns['appr_btn'] . '
						</div>
					</div>';
			case 'second-approved':
				$payment_status = $params['payment_status'];
				$btns = second_approve_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						dropdown_item($btns['return_btn'], 'Return to Treasury Approved') .
						($payment_status == 0 ? dropdown_item($btns['payment_btn'], 'Pay Transaction') : '') .
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($btns['edit_appr_btn'], 'Edit Approval');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . $btns['return_btn'] . '
							' . ($payment_status == 0 ? $btns['payment_btn'] : '') . '
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $btns['edit_appr_btn'] . '
						</div>
					</div>';
			case 'active':
				$payment_status = $params['payment_status'];
				$coupon_pdf_archived = $params['coupon_pdf_archived'];
				$coupon_for_printing = $params['coupon_for_printing'];
				$coupon_for_image_conv = $params['coupon_for_image_conv'];
				$btns = active_actions($coupon_transaction_header_id, $one_liner, $params['access_type']);

				$regenerate_pdf_btn = '';
				$return_btn = '';
				$archive_btn = '';
				$export_bulk_pdf_for_printing_btn = '';
				$export_zipped_jpg_imgs = '';
				if ($coupon_pdf_archived == 1) {
					$mid_btns = $btns['regenerate_pdf_btn'];
					$regenerate_pdf_btn = $btns['regenerate_pdf_btn'];
					
				} else {
					$mid_btns = $btns['return_btn'] . $btns['archive_btn'];
					$return_btn = $btns['return_btn'];
					$archive_btn = $btns['archive_btn'];
					if ($coupon_for_printing == 1) {
						$mid_btns .= $btns['export_bulk_pdf_for_printing_btn'];
						$export_bulk_pdf_for_printing_btn = $btns['export_bulk_pdf_for_printing_btn'];
					}
					if ($coupon_for_image_conv == 1) {
						$mid_btns .= $btns['export_zipped_jpg_imgs'];
						$export_zipped_jpg_imgs = $btns['export_zipped_jpg_imgs'];
					}
				}
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['view_details_button'], 'View Details');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['view_details_button'] . '
						</div>
					</div>';
			case 'inactive':
				$btns = inactive_actions($coupon_transaction_header_id, $one_liner);
				if ($one_liner) {
					return
						($coupon_cat_id != '2' ? dropdown_item($generic_btns['attachment_button'], 'View Attachments') : '') .
						dropdown_item($generic_btns['view_details_button'], 'View Details');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . ($coupon_cat_id != '2' ? $generic_btns['attachment_button'] : '') . '
							' . $generic_btns['view_details_button'] . '
						</div>
					</div>';
			default:
				if ($one_liner) {
					return dropdown_item($generic_btns['view_details_button'], 'View transaction details');
				}
				return '
					<div class="action-menu px-2 py-1">
						<div class="d-flex align-items-center mb-2 gap-2">
							' . $generic_btns['view_details_button'] . '
						</div>
					</div>';
		}
	}

	// --- Button Generators ---

	function generic_actions($coupon_transaction_header_id, $one_liner = false, $access_type = null){
		return [
			'toggle' => '
				<a href="#" title="Deactivate Transaction" class="toggle-active-transaction btn btn-sm btn-light text-success" data-id="' . encode($coupon_transaction_header_id) . '">
					<span class="fas fa-toggle-on fa-lg"></span>
				</a>',
			'attachment_button' => '
				<a class="btn btn-light btn-sm view-attachments text-success" href="#" data-url="/modal_transaction_coupon_attachment/" data-id="' . encode($coupon_transaction_header_id) . '" title="View Attachments">
					<i class="fas fa-paperclip fa-lg"></i>
				</a>',
			'edit_button' => '
				<a class="btn btn-light btn-sm edit-transaction-coupon text-info" href="#" data-id="' . encode($coupon_transaction_header_id) . '" title="Edit Transaction">
					<i class="fas fa-pencil-alt fa-lg"></i>
				</a>',
			'duplicate_button' => '
				<a class="btn btn-light btn-sm duplicate-transaction-coupon text-success" href="#" data-id="' . encode($coupon_transaction_header_id) . '" title="Duplicate Transaction">
					<i class="fas fa-copy fa-lg"></i>
				</a>',
			'cancel_button' => '
				<a class="btn btn-light btn-sm cancel-transaction text-danger" href="#" data-id="' . encode($coupon_transaction_header_id) . '" title="Cancel Transaction">
					<i class="fas fa-times-circle fa-lg"></i>
				</a>',
			'view_details_button' => '
				<a class="btn btn-light btn-sm view-product-coupon-details" href="#" data-id="' . encode($coupon_transaction_header_id) . '" title="View Details">
					<i class="far fa-eye fa-lg"></i>
				</a>',
			'export_trans_details_btn' => '
				<a href="'.base_url($access_type.'/export-trans-details/') . encode($coupon_transaction_header_id).'" class="btn btn-light btn-sm text-success">
					<span class="fas fa-file-excel fa-lg"></span>
				</a>',
		];
	}

	function first_approve_actions($coupon_transaction_header_id, $one_liner = false){
		return [
			'return_btn' => '
				<a href="#" title="Back to Pending" class="btn btn-light btn-sm return-pending-transaction text-primary" data-id="' . encode($coupon_transaction_header_id) . '">
					<i class="fas fa-arrow-circle-left fa-lg"></i>
				</a>',
			'appr_btn' => '
				<a href="#" title="Approve" class="btn btn-light btn-sm text-primary approve-transaction" data-id="' . encode($coupon_transaction_header_id) . '">
					<i class="fas fa-arrow-circle-right fa-lg"></i>
				</a>',
			'edit_appr_btn' => '
				<a href="#" title="Edit Approval" class="btn btn-light text-info btn-sm edit-first-approve-transaction" data-id="' . encode($coupon_transaction_header_id) . '">
					<i class="fas fa-edit fa-lg"></i>
				</a>',
		];
	}

	function second_approve_actions($coupon_transaction_header_id, $one_liner = false){
		return [
			'return_btn' => '
				<a href="#" title="Back to Treasury Approved" class="btn btn-light btn-sm return-first-approve-transaction text-primary" data-id="' . encode($coupon_transaction_header_id) . '">
					<i class="fas fa-arrow-circle-left fa-lg"></i>
				</a>',
			'appr_btn' => '
				<a href="#" title="Publish" class="btn btn-light btn-sm text-primary publish-transaction" data-id="' . encode($coupon_transaction_header_id) . '">
					<i class="fas fa-arrow-circle-right fa-lg"></i>
				</a>',
			'edit_appr_btn' => '
				<a href="#" title="Edit Approval" class="btn btn-light text-info btn-sm edit-approve-transaction" data-id="' . encode($coupon_transaction_header_id) . '">
					<i class="fas fa-edit fa-lg"></i>
				</a>',
			'payment_btn' => '
				<a href="#" class="btn btn-light btn-sm text-success pay-transaction" data-id="'.encode($coupon_transaction_header_id).'">
					<span class="fas fa-money-bill fa-lg"></span>
				</a>',
		];
	}

	function active_actions($coupon_transaction_header_id, $one_liner = false, $access_type = null){
		return [
			'return_btn' => '
				<a href="#" title="Back to Finance Approved" class="btn btn-light btn-sm return-approve-transaction text-primary" data-id="' . encode($coupon_transaction_header_id) . '">
					<i class="fas fa-arrow-circle-left fa-lg"></i>
				</a>',
			'appr_btn' => '',
			'edit_appr_btn' => '
				<a href="#" title="Edit Approval" class="btn btn-light text-info btn-sm edit-approve-transaction" data-id="' . encode($coupon_transaction_header_id) . '">
					<i class="fas fa-edit fa-lg"></i>
				</a>',
			'payment_btn' => '
				<a href="#" class="btn btn-light btn-sm text-success pay-transaction" data-id="'.encode($coupon_transaction_header_id).'">
					<span class="fas fa-money-bill fa-lg"></span>
				</a>',
			'regenerate_pdf_btn' => '
				<a title="Regenerate PDF" href="#" class="btn btn-light btn-sm regenerate-pdf text-danger" data-id="'.encode($coupon_transaction_header_id) .'">
					<i class="fas fa-undo fa-lg"></i>
				</a>',
			'archive_btn' => '
				<a title="Archive PDF" href="#" class="btn btn-light btn-sm archive-pdf text-danger" data-id="'.encode($coupon_transaction_header_id) .'">
					<i class="fas fa-archive fa-lg"></i>
				</a>',
			'export_bulk_pdf_for_printing_btn' => '
				<a title="Export Bulk Pdf For Printing" href="'.base_url($access_type.'/download-multi-coupon-pdf/' . encode($coupon_transaction_header_id)).'" target="_blank" class="btn btn-light btn-sm text-danger">
					<i class="fas fa-print fa-lg"></i>
				</a>',
			'export_zipped_jpg_imgs' => '
				<a title="Export Zipped JPG Images" href="'.base_url($access_type.'/download-pdf-to-image/' . encode($coupon_transaction_header_id)).'" target="_blank" class="btn btn-light btn-sm text-secondary">
					<i class="far fa-file-image fa-lg"></i>
				</a>',
			'download_zipped_pdf_btn' => '
				<a title="Download Zipped PDF" href="'.base_url($access_type.'/zip-coupon/' . encode($coupon_transaction_header_id)).'" target="_blank" class="btn btn-light btn-sm text-danger">
					<i class="fas fa-file-archive fa-lg"></i>
				</a>',
		];
	}

	function inactive_actions($coupon_transaction_header_id, $one_liner = false){
		return [
			'toggle' => '
				<a href="" class="toggle-inactive-transaction btn btn-light btn-sm text-warning" data-id="' . encode($coupon_transaction_header_id) . '">
					<span class="fas fa-toggle-off fa-lg"></span>
				</a>',
		];
	}

	function pending_actions($coupon_transaction_header_id, $one_liner = false){
		return [
			'return_btn' => '',
			'appr_btn' => '
				<a href="#" title="Approve" class="btn btn-light btn-sm text-primary first-approve-transaction" data-id="' . encode($coupon_transaction_header_id) . '">
					<span class="fas fa-arrow-circle-right fa-lg"></span>
				</a>',
			'edit_appr_btn' => '',
		];
	}

	/**
	 * Helper to render a dropdown item with description, one per line.
	 */
	function dropdown_item($button_html, $desc) {
		// Extract the first <a> tag from $button_html and merge the description inside it, making the whole item clickable
		if (preg_match('/<a\b([^>]*)>(.*?)<\/a>/is', $button_html, $matches)) {
			$attrs = $matches[1];
			$inner = $matches[2];
			// Add dropdown-item and flex classes to <a>
			if (preg_match('/class=["\']([^"\']*)["\']/', $attrs)) {
				$attrs = preg_replace('/class=["\']([^"\']*)["\']/', 'class="$1 dropdown-item d-flex align-items-center px-2 py-1"', $attrs);
			} else {
				$attrs .= ' class="dropdown-item d-flex align-items-center px-2 py-1"';
			}
			// Add style for increased line-height and padding for better spacing
			if (preg_match('/style=["\']([^"\']*)["\']/', $attrs)) {
				$attrs = preg_replace('/style=["\']([^"\']*)["\']/', 'style="$1;line-height:1.8;padding-top:8px;padding-bottom:8px;"', $attrs);
			} else {
				$attrs .= ' style="line-height:1.8;padding-top:8px;padding-bottom:8px;"';
			}
			return '<a' . $attrs . '>'
				. '<span style="display:inline-flex;align-items:center;min-width:24px;justify-content:center;">' . $inner . '</span>'
				. '<span class="text-muted" style="font-size:0.95em; margin-left:8px; margin-right:8px;">' . htmlspecialchars($desc) . '</span>'
				. '</a>';
		} else {
			// Fallback: wrap everything in <a>
			return '<a href="#" class="dropdown-item d-flex align-items-center px-2 py-1" style="line-height:1.8;padding-top:8px;padding-bottom:8px;">'
				. '<span style="display:inline-flex;align-items:center;min-width:24px;justify-content:center;">' . $button_html . '</span>'
				. '<span class="text-muted" style="font-size:0.95em; margin-left:8px;">' . htmlspecialchars($desc) . '</span>'
				. '</a>';
		}
	}

	function modal_actions($params){
		$coupon_id = $params['coupon_id'];
		$coupon_status = $params['coupon_status'];
		$access_type = $params['access_type'];
		$coupon_pdf_path = $params['coupon_pdf_path'];
		$is_advance_order = $params['is_advance_order'];

		// Define all possible actions
		$actions = [
			'edit_btn' => '
				<a href="#" title="Edit" class="btn btn-light btn-sm text-primary edit-product-coupon" data-id="' . encode($coupon_id) . '">
					<span class="fas fa-pencil-alt fa-lg"></span>
				</a>',
			'toggle_btn' => '
				<a href="#" class="btn btn-light btn-sm toggle-active-coupon text-success" data-id="' . encode($coupon_id) . '">
					<span class="fas fa-toggle-on fa-lg"></span>
				</a>',
			'pdf_btn' => '
				<a href="' . base_url($coupon_pdf_path) . '" class="btn btn-light btn-sm text-danger" target="_blank" rel="noreferer">
					<i class="fas fa-file-pdf fa-lg"></i>
				</a>',
		];

		// Map access types to allowed actions
		$access_map = [
			'admin'   				=> ['edit_btn', 'toggle_btn', 'pdf_btn'],
			'creator' 				=> ['edit_btn', 'pdf_btn'],
			'first-approver'  		=> ['pdf_btn'],
			'approver'  			=> ['pdf_btn'],
			// Add more access types as needed
		];

		// Get allowed actions for current access type, fallback to 'viewer'
		$allowed = isset($access_map[$access_type]) ? $access_map[$access_type] : $access_map['viewer'];

		// Refine allowed actions based on coupon_status
		$status_allowed = [];
		foreach ($allowed as $key) {
			if ($key == 'edit_btn') {
				if ($coupon_status == 0 || $coupon_status == 1 || $coupon_status == 2 || $coupon_status == 4 || $coupon_status == 5) {
					if($is_advance_order == 0){
						$status_allowed[] = $key;
					}
				}
		 	} else if ($key == 'toggle_btn') {
				if ($coupon_status == 1 || $coupon_status == 2 || $coupon_status == 4 || $coupon_status == 5) {
					$status_allowed[] = $key;
				}
			} else if ($key == 'pdf_btn') {
				if ($coupon_status == 1) {
					$status_allowed[] = $key;
				}
			}
		}

		// Build result array with only access & status allowed actions
		$output = '';
		foreach ($status_allowed as $key) {
			if (isset($actions[$key])) {
				$output .= $actions[$key];
			}
		}

		return $output;
	}
}
