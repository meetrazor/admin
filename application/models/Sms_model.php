<?php
defined('BASEPATH') OR exit('No direct script access allowed');
    class Sms_model extends CI_Model {
        
        function __construct() {
            parent::__construct();
        }

        public function sendSms($message, $mobileNumber, $isOtp)
        {
            try {
                $otp = '';
                if ($isOtp) {
                    $otp = rand(1000, 9999);
                    $message .= $otp;
                }
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "http://api.msg91.com/api/v2/sendsms?country=91&sender=&route=&mobiles=&authkey=&encrypt=&message=&flash=&unicode=&schtime=&afterminutes=&response=&campaign=",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => "{ \"sender\": \"ADMINO\", \"route\": \"4\", \"country\": \"91\", \"sms\": [ { \"message\": \"$message\", \"to\": [ \"$mobileNumber\" ] } ] }",
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_HTTPHEADER => array(
                            "authkey: 271148AGteVZmSwJl5d486778",
                            "content-type: application/json"
                        ),
                    )
                );

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                    return array('error' => true, 'message' => "cURL Error #:" . $err);
                } else {
                    return array('error' => false, 'message' => $response, 'otp' => $otp);
                }
            } catch (\Throwable $th) {
                //throw $th;
                return array('error' => true, 'message' => "cURL Error #:" . $err);
            }
        }

        public function verifyOtp($mobileNumber, $otp)
        {

        }
		
		public function sendSmsWithoutotp($message, $mobileNumber)
        {
            try {
               
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => "http://api.msg91.com/api/v2/sendsms?country=91&sender=&route=&mobiles=&authkey=&encrypt=&message=&flash=&unicode=&schtime=&afterminutes=&response=&campaign=",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => "{ \"sender\": \"ADMINO\", \"route\": \"4\", \"country\": \"91\", \"sms\": [ { \"message\": \"$message\", \"to\": [ \"$mobileNumber\" ] } ] }",
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_HTTPHEADER => array(
                            "authkey: 271148AGteVZmSwJl5d486778",
                            "content-type: application/json"
                        ),
                    )
                );

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                    return array('error' => true, 'message' => "cURL Error #:" . $err);
                } else {
                    return array('error' => false, 'message' => $response, 'msg' => 'Success');
                }
            } catch (\Throwable $th) {
                //throw $th;
                return array('error' => true, 'message' => "cURL Error #:" . $err);
            }
        }
	
	}
?>
