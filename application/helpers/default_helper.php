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
}
