
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pos_redeem extends CI_Controller {

	public function __construct()
    {
    	parent::__construct();
    	$this->load->model('main_model', 'main');
        $GLOBALS['parent_db'] = parent_db();
	}

	public function index()
    {
		$data['user_id']   = NULL;
        $data['title']   = 'Redeem '.SEC_SYS_NAME.'';
        $data['content'] = $this->load->view('pos/coupon/redeem_coupon_content', $data, TRUE);
        $this->load->view('pos/templates', $data);
	}
}
?>
