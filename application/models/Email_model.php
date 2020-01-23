<?php
	defined('BASEPATH') OR exit('No direct script access allowed');

    class Email_model extends CI_Model {

        function __construct() {
            parent::__construct();
            $this->load->database();
            $this->load->helper('app_helper');
		}

		public function school_login($school_id) {
			
		}

		public function send_email(){
			$config = Array(
				'protocol' => 'smtp',
				'smtp_host' => 'ssl://smtp.googlemail.com',
				'smtp_port' => 465,
				'smtp_user' => '',
				'smtp_pass' => '',
				'mailtype'  => 'html', 
				'charset'   => 'iso-8859-1'
			);
			$this->load->library('email', $config);
			$this->email->set_newline("\r\n");
			// Set to, from, message, etc.
			$result = $this->email->send();
		}
	}
?>
