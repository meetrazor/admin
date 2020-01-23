<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Paytm_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('app_helper');
    }

    public function response($error = true, $msg = '', $data = array()) {
        echo json_encode(array('error' => $error, 'message' => $msg, 'data' => $data));
        exit;
    }

    public function verifychecksum() {
        $error = false;
        $msg = "";
        $return_array = $_POST;
		try {
            header("Pragma: no-cache");
            header("Cache-Control: no-cache");
            header("Expires: 0");

            require_once APPPATH . "/third_party/paytm/lib/config_paytm.php";
            require_once APPPATH . "/third_party/paytm/lib/encdec_paytm.php";

            $paytmChecksum = "";
            $paramList = array();
			$isValidChecksum = FALSE;

            $paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : ""; //Sent by Paytm pg
			$paytmChecksum = str_replace('\\/', '/', $paytmChecksum);

			// below code snippet is mandatory, so that no one can use your checksumgeneration url for other purpose .
			$findme   = 'REFUND';
			$findmepipe = '|';
			$paramList = array();
			$paramList["MID"] = '';
			$paramList["ORDER_ID"] = '';
			$paramList["CUST_ID"] = '';
			$paramList["INDUSTRY_TYPE_ID"] = '';
			$paramList["CHANNEL_ID"] = '';
			$paramList["TXN_AMOUNT"] = '';
			$paramList["CALLBACK_URL"] = '';
			$paramList["WEBSITE"] = '';
			foreach($_POST as $key => $value) {
				$pos = strpos($value, $findme);
				$pospipe = strpos($value, $findmepipe);
				if ($pos === false || $pospipe === false) {
					$paramList[$key] = $value;
				}
			}

            //Verify all parameters received from Paytm pg to your application. Like MID received from paytm pg is same as your application’s MID, TXN_AMOUNT and ORDER_ID are same as what was sent by you to Paytm PG for initiating transaction etc.
            $isValidChecksum = verifychecksum_e($paramList, PAYTM_MERCHANT_KEY, $paytmChecksum); //will return TRUE or FALSE string.
            if ($isValidChecksum === true) {
                $msg = "Payment Success";
            } else {
                $error = true;
                $msg = "Payment Failed";
			}
			$status = $error ? 2 : 1;
			$this->db->insert("transactions", array(
				"order_id" => $paramList["ORDER_ID"],
				"user_type_id" => "2",
				"amount" => $paramList["TXN_AMOUNT"],
				"response" => json_encode($_POST, JSON_UNESCAPED_SLASHES),
				"status" => $status
			));
			$this->db->update("users", array("is_prime" => 1, "expiry_time" => date("Y-m-d", strtotime('+1 year'))));
        } catch (\Throwable $th) {
            $error = true;
            $msg = 'Exception';
		}
		echo json_encode(array('error' => $error, 'message' => $msg, 'data' => $return_array));
		exit;
        // $this->response($error, $msg, $return_array);
    }

    public function generateChecksum() {
        header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        header("Expires: 0");
        // following files need to be included
        require_once APPPATH . "/third_party/paytm/lib/config_paytm.php";
        require_once APPPATH . "/third_party/paytm/lib/encdec_paytm.php";
        $checkSum = "";

        // below code snippet is mandatory, so that no one can use your checksumgeneration url for other purpose .
        $findme   = 'REFUND';
        $findmepipe = '|';
        $paramList = array();
        $paramList["MID"] = '';
        $paramList["ORDER_ID"] = '';
        $paramList["CUST_ID"] = '';
        $paramList["INDUSTRY_TYPE_ID"] = '';
        $paramList["CHANNEL_ID"] = '';
        $paramList["TXN_AMOUNT"] = '';
        $paramList["CALLBACK_URL"] = '';
        $paramList["WEBSITE"] = '';
        foreach($_POST as $key => $value) {
            $pos = strpos($value, $findme);
            $pospipe = strpos($value, $findmepipe);
            if ($pos === false || $pospipe === false) {
                $paramList[$key] = $value;
            }
        }

        //Here checksum string will return by getChecksumFromArray() function.
        $checkSum = getChecksumFromArray($paramList, PAYTM_MERCHANT_KEY);

        echo json_encode(array("CHECKSUMHASH" => $checkSum, "ORDER_ID" => $_POST["ORDER_ID"], "payt_STATUS" => "1"), JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function transectionStatus() {
        header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        header("Expires: 0");
        // following files need to be included
        require_once APPPATH . "/third_party/paytm/lib/config_paytm.php";
        require_once APPPATH . "/third_party/paytm/lib/encdec_paytm.php";
        $checkSum = "";

        // below code snippet is mandatory, so that no one can use your checksumgeneration url for other purpose .
        $findme   = 'REFUND';
        $findmepipe = '|';
        $paramList = array();
        $paramList["MID"] = '';
        $paramList["ORDER_ID"] = '';
        $paramList["CUST_ID"] = '';
        $paramList["INDUSTRY_TYPE_ID"] = '';
        $paramList["CHANNEL_ID"] = '';
        $paramList["TXN_AMOUNT"] = '';
        $paramList["WEBSITE"] = '';
        foreach($_POST as $key=>$value) {
            $pos = strpos($value, $findme);
            $pospipe = strpos($value, $findmepipe);
            if ($pos === false || $pospipe === false) {
                $paramList[$key] = $value;
            }
        }
        $paytmParams = array();
        $checkSum = getChecksumFromArray($paramList, PAYTM_MERCHANT_KEY);

        /* put generated checksum value here */
        $paytmParams["CHECKSUMHASH"] = $checkSum;

        /* prepare JSON string for request */
        $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

        /* for Staging */
        $url = "https://securegw-stage.paytm.in/order/status";

        /* for Production */
        // $url = "https://securegw.paytm.in/order/status";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));  
        $response = curl_exec($ch);
        $responseData = json_decode($response, true);
        // $status = $this->getPaymentStatus($responseData['STATUS']);
        $this->response(false, '', $responseData);
    }

    private function getPaymentStatus($status) {
        $statusList = array('TXN_SUCCESS' => 'COMPLETED', 'TXN_FAILURE' => 'FAILED', 'PENDING' => 'PROCESSING');
        return $statusList[$status];
    }

    public function callback() {
        header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        header("Expires: 0");

        // following files need to be included
        require_once APPPATH . "/third_party/paytm/lib/config_paytm.php";
        require_once APPPATH . "/third_party/paytm/lib/encdec_paytm.php";

        $paytmChecksum = "";
        $paramList = array();
        $isValidChecksum = "FALSE";

        $paramList = $_POST;
        $paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : ""; //Sent by Paytm pg

        // Verify all parameters received from Paytm pg to your application. Like MID received from paytm pg is same as your application�s MID, TXN_AMOUNT and ORDER_ID are same as what was sent by you to Paytm PG for initiating transaction etc.
        $isValidChecksum = verifychecksum_e($paramList, PAYTM_MERCHANT_KEY, $paytmChecksum); //will return TRUE or FALSE string.
        $transaction_status = false;

        if($isValidChecksum == "TRUE") {
            // echo "<b>Checksum matched and following are the transaction details:</b>" . "<br/>";
            if ($_POST["STATUS"] == "TXN_SUCCESS") {
                $transaction_status = true;
                $msg = "Transaction status is success";
                // Process your transaction here as success transaction.
                // Verify amount & order id received from Payment gateway with your application's order id and amount.
            }
            else {
                $transaction_status = false;
                $msg = "Transaction status is failure";
            }

            if (isset($_POST) && count($_POST) > 0) {
				$status = $transaction_status ? 2 : 1;
				$this->db->set(array(
					"response" => json_encode($_POST, JSON_UNESCAPED_SLASHES),
					"status" => $status
				));
				$this->db->where("order_id" ,$_POST["ORDERID"]);
				$this->db->update("transactions");
				if ($transaction_status) {
					$this->db->select("school_id");
					$this->db->where("order_id" ,$_POST["ORDERID"]);
					$query = $this->db->get("transactions");
					$school_id = $query->first_row();
					$this->db->set( array("is_prime" => 1, "expiry_time" => date("Y-m-d", strtotime('+1 year'))));
					$this->db->where("school_id",$school_id->school_id);
					$this->db->update("schools");
				}
            }
        }
        else {
            $transaction_status = false;
            $msg = "Checksum mismatched.";
            //Process transaction as suspicious.
        }
		// $this->response($transaction_status, $msg, $_POST);
		return $transaction_status;
	}
	
	public function bnResponse() {
		header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        header("Expires: 0");

        // following files need to be included
        require_once APPPATH . "/third_party/paytm/lib/config_paytm.php";
        require_once APPPATH . "/third_party/paytm/lib/encdec_paytm.php";

        $paytmChecksum = "";
        $paramList = array();
        $isValidChecksum = "FALSE";

        $paramList = $_POST;
        $paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : ""; //Sent by Paytm pg

        // Verify all parameters received from Paytm pg to your application. Like MID received from paytm pg is same as your application�s MID, TXN_AMOUNT and ORDER_ID are same as what was sent by you to Paytm PG for initiating transaction etc.
        $isValidChecksum = verifychecksum_e($paramList, PAYTM_MERCHANT_KEY, $paytmChecksum); //will return TRUE or FALSE string.
        $transaction_status = false;

        if($isValidChecksum == "TRUE") {
            // echo "<b>Checksum matched and following are the transaction details:</b>" . "<br/>";
            if ($_POST["STATUS"] == "TXN_SUCCESS") {
                $transaction_status = true;
                $msg = "Transaction status is success";
                // Process your transaction here as success transaction.
                // Verify amount & order id received from Payment gateway with your application's order id and amount.
            }
            else {
                $transaction_status = false;
                $msg = "Transaction status is failure";
            }

            if (isset($_POST) && count($_POST) > 0) {
				$status = $transaction_status ? 2 : 1;
				$this->db->set(array(
					"response" => json_encode($_POST, JSON_UNESCAPED_SLASHES),
					"status" => $status
				));
				$this->db->where("order_id" ,$_POST["ORDERID"]);
				$this->db->update("transactions");
				if ($transaction_status) {
					$this->db->select("banner_id");
					$this->db->where("order_id" ,$_POST["ORDERID"]);
					$query = $this->db->get("transactions");
					$banner_id = $query->first_row();
					$this->db->set(array("is_paid" => 1, "expiry_time" => date("Y-m-d", strtotime('+1 month'))));
					$this->db->where("banner_id",$banner_id->banner_id);
					$this->db->update("banners");
				}
            }
        }
        else {
            $transaction_status = false;
            $msg = "Checksum mismatched.";
            //Process transaction as suspicious.
        }
		// $this->response($transaction_status, $msg, $_POST);
		return $transaction_status;
	}

	public function exResponse() {
		header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        header("Expires: 0");

        // following files need to be included
        require_once APPPATH . "/third_party/paytm/lib/config_paytm.php";
        require_once APPPATH . "/third_party/paytm/lib/encdec_paytm.php";

        $paytmChecksum = "";
        $paramList = array();
        $isValidChecksum = "FALSE";

        $paramList = $_POST;
        $paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : ""; //Sent by Paytm pg

        // Verify all parameters received from Paytm pg to your application. Like MID received from paytm pg is same as your application�s MID, TXN_AMOUNT and ORDER_ID are same as what was sent by you to Paytm PG for initiating transaction etc.
        $isValidChecksum = verifychecksum_e($paramList, PAYTM_MERCHANT_KEY, $paytmChecksum); //will return TRUE or FALSE string.
        $transaction_status = false;

        if($isValidChecksum == "TRUE") {
            // echo "<b>Checksum matched and following are the transaction details:</b>" . "<br/>";
            if ($_POST["STATUS"] == "TXN_SUCCESS") {
                $transaction_status = true;
                $msg = "Transaction status is success";
                // Process your transaction here as success transaction.
                // Verify amount & order id received from Payment gateway with your application's order id and amount.
            }
            else {
                $transaction_status = false;
                $msg = "Transaction status is failure";
            }

            if (isset($_POST) && count($_POST) > 0) {
				$status = $transaction_status ? 2 : 1;
				$this->db->insert("transactions", array(
					"order_id" => $_POST["ORDERID"] ,
					"user_type_id" => "1",
					"amount" => $_POST["TXNAMOUNT"],
					"response" => json_encode($_POST, JSON_UNESCAPED_SLASHES),
					"status" => $status
				));
				if ($transaction_status) {
					$this->db->set(array("is_paid" => 1));
					$this->db->where("order_id",$_POST['ORDERID']);
					$this->db->update("paid_exam");
				}
            }
        }
        else {
            $transaction_status = false;
            $msg = "Checksum mismatched.";
            //Process transaction as suspicious.
        }
		// $this->response($transaction_status, $msg, $_POST);
		return $transaction_status;
	}

	public function school_paytm_save() {
		$school = array(
			'user_type_id' => 3, 
			'order_id' => $_POST["ORDER_ID"],
			'school_id' => $_POST["CUST_ID"],
			'amount' => $_POST["TXN_AMOUNT"],
			'status' => 0,
		);
		$this->db->insert('transactions',$school);
		return true;
	}

	public function banner_paytm_save(){
		$banner = array(
			'user_type_id' => 3, 
			'order_id' => $_POST["ORDER_ID"],
			'banner_id' => $_POST["CUST_ID"],
			'amount' => $_POST["TXN_AMOUNT"],
			'status' => 0,
		);
		$this->db->insert('transactions',$banner);
		return true;
	}

	public function exam_paytm_save(){
		$exam = array(
			'order_id' => $_POST["ORDER_ID"],
			'exam_id' => $_POST["EXAM_ID"],
			'user_id' => $_POST["CUST_ID"],
			'language_id' => $_POST["LANGUAGE_ID"],
			'is_paid' => 0,
		);
		$this->db->insert('paid_exam',$exam);
		return true;
	}
}
