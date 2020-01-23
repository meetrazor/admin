<?php
	defined('BASEPATH') OR exit('No direct script access allowed');

    class Teacher_model extends CI_Model {

        function __construct() {
            parent::__construct();
            $this->load->database();
            $this->load->helper('app_helper');
        }
        
        public function response($error = true, $msg = '', $data = array()) {
            echo json_encode(array('error' => $error, 'message' => $msg, 'data' => $data));
            exit;
        }

		public function teacher_pricing() {
            $error = false;
            $msg = '';
            $response = new stdClass();
            try {
                $this->db->select("amount, discount, final_amount, information_text, duration, duration_text");
                $this->db->where("type", "prime_teacher");
                $query = $this->db->get("pricing");
                $data = $query->first_row();
                if ($data) {
                    $response = $data;
                }
            } catch (\Throwable $th) {
                $error = true;
                $msg = "Exception";
            }
            $this->response($error, $msg, $response);
        }
	}
?>
