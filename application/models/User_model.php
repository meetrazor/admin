<?php
defined('BASEPATH') OR exit('No direct script access allowed');
    class User_model extends CI_Model {

        function __construct() {
            parent::__construct();
            $this->load->database();
            $this->load->helper('app_helper');
        }

        public function response($error = true, $msg = "", $data) {
            echo json_encode(array('error' => $error, 'message' => $msg, 'data' => $data));
            exit;
        }

        public function userProfile($data, $fbFlag, $gFlag, $passwordCheck) {
            $this->db->select('u.user_id, u.user_type, u.first_name, u.last_name, mn.mobile_no, u.email_id, u.profile_pic, u.is_prime');
            $this->db->join('mobile_nos as mn', 'mn.mobile_no_id = u.mobile_no_id', 'left');
            if (!$fbFlag) {
                $this->db->where('u.fb_id', $data['fb_id']);
            } else if (!$gFlag) {
                $this->db->where('u.g_id', $data['g_id']);
            } else {
                $this->db->group_start();
                if (isset($data['username'])) {
                    $username = $data['username'];
                    $this->db->where('u.mobile_no', $username);
                    if (!empty($data['email_id'])) {
                        $this->db->or_where('u.email_id', $username);
                    }
                } else {
                    $this->db->where('u.mobile_no', $data['mobile_no']);
                    if (!empty($data['email_id'])) {
                        $this->db->or_where('u.email_id', $data['email_id']);
                    }
                }
                $this->db->group_end();
                if ($passwordCheck) {
                    $this->db->where('u.password', md5($data['password']));
                }
            }
            $query = $this->db->get('users as u');
            $userInfo = $query->row();
            return $userInfo;
        }

        public function login() {
            $error = false;
            $msg = "";
            $userInfo = new stdClass();
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                $normalLogin = checkSetEmpty($data, array('username', 'password'));
                $fbLogin = checkSetEmpty($data, array('fb_id'));
                $gLogin = checkSetEmpty($data, array('g_id'));
                if ($normalLogin && $fbLogin && $gLogin) {
                    $error = true;
                    $msg = $normalLogin . ' or ' . $fbLogin . ' or ' . $gLogin;
                    $this->response($error, $msg, $userInfo);
                }
                $userInfo = $this->userProfile($data, $fbLogin, $gLogin, true);
                if (isset($userInfo)) {
                    $error = false;
                } else {
                    $msg = 'Invalid credential';
                    $error = true;
                    $userInfo = new stdClass();
                }
                $this->response($error, $msg, $userInfo);
            } catch (\Throwable $th) {
                //throw $th;
                $this->response(true, 'Exception', new stdClass());
            }
        }

        public function signup() {
            $error = false;
            $msg = "";
            $userInfo = new stdClass();

            try {
                $data = json_decode(file_get_contents('php://input'), true);
                $fbSignUp = checkSetEmpty($data, array('fb_id'));
                $gSignUp = checkSetEmpty($data, array('g_id'));
                $normalSignUp = checkSetEmpty($data, array('first_name', 'last_name', 'mobile_no', 'password'));
                if ($normalSignUp && $fbSignUp && $gSignUp) {
                    $error = true;
                    $msg = $normalSignUp . ' or ' . $fbSignUp . ' or ' . $gSignUp;
                    $this->response($error, $msg, $userInfo);
                }

                $userInfo = $this->userProfile($data, $fbSignUp, $gSignUp, false);
                if (isset($userInfo)) {
                    if ($fbSignUp && $gSignUp) {
                        $error = true;
                        $msg = 'User already exist';
                        $this->response($error, $msg, new stdClass());
                    } else {
                        // facebook login || google login
                        $msg = 'Social Login';
                        $this->response($error, $msg, $userInfo);
                    }
                }

                if (!$fbSignUp) {
                    // facebook signup
                    $userData = setData($data, "first_name, last_name, fb_id, email_id");
                } else if (!$gSignUp) {
                    // google signup
                    $userData = setData($data, "first_name, last_name, g_id, email_id");
                } else if (!$normalSignUp && strlen($data['mobile_no']) == 10) {
                    // normal login /signup
                    $data['password'] = md5($data['password']);
                    $data['mobile_no_id'] = $this->getMobileNoId($data['mobile_no']);
                    $userData = setData($data, "first_name, last_name, password, email_id, user_type, mobile_no_id");
                } else {
                    $msg = 'Invalid mobile no';
                    $error = true;
                    $this->response($error, $msg, new stdClass());
                }

                if (isset($userData)) {
                    $this->db->trans_start();
                    $this->db->insert('users', $userData);
                    $this->db->trans_complete();
                    if ($this->db->trans_status() === FALSE) {
                        $msg = 'Error Sign up';
                        $error = true;
                        $userInfo = new stdClass();
                    } else {
                        $userInfo = $this->userProfile($userData, $fbSignUp, $gSignUp, false);
                    }
                } else {
                    $msg = 'Error Sign up';
                    $error = true;
                    $userInfo = new stdClass();
                }
                $this->response($error, $msg, $userInfo);
            } catch (\Throwable $th) {
                //throw $th;
                $this->response(true, 'Exception', new stdClass());
            }
        }

        public function search() {
            $error = false;
            $msg = "";
            try {
                $query = $this->db->query("call list_of_available_cities");
                $schools = $query->result_array();
                $this->response($error, $msg, $schools);
            } catch (\Throwable $th) {
                //throw $th;
                $this->response(true, 'Exception', array());
            }
        }

        public function send_otp() {
            $error = false;
            $msg = "";
            $data = json_decode(file_get_contents('php://input'), true);
            try {
                $normalSignUp = checkSetEmpty($data, array('mobile_no'));
                if ($normalSignUp || strlen($data['mobile_no']) != 10) {
                    $error = true;
                    $msg = $normalSignUp ? $normalSignUp : "Invalid mobile number";
                    $this->response($error, $msg, new stdClass());
                }

                $this->load->model('Sms_model', 'sms');
                $response = $this->sms->sendSms("Please use this OTP: ", $data['mobile_no'], true);
                if ($response['error']) {
                    $error = true;
                    $msg = "Otp sending failed";
                } else {
                    $error = false;
                    $mobileNumberId = $this->getMobileNoId($data['mobile_no']);
                    $this->db->insert('mobile_verification', array('mobile_no_id' => $mobileNumberId, 'otp' => $response['otp']));
                    $msg = "Otp sent successfully";
                }
                $this->response($error, $msg, new stdClass());
            } catch (\Throwable $th) {
                //throw $th;
                $this->response(true, 'Exception', new stdClass());
            }
        }

        public function getMobileNoId($mobileNumber) {
            $this->db->select('mobile_no_id');
            $query = $this->db->get_where('mobile_nos', array('mobile_no' => $mobileNumber));
            $mobile_no_id = 0;
            if ($query->num_rows()) {
                $userInfo = $query->row();
                $mobile_no_id = $userInfo->mobile_no_id;
            } else {
                $this->db->insert('mobile_nos', array('mobile_no' => $mobileNumber));
                $mobile_no_id = $this->db->insert_id();
            }
            return $mobile_no_id;
        }

        public function getSetUserData($inputs, $setUserData) {
            $userInfo = new stdClass();
            if (count($inputs)) {
                $mobile_no_id = $this->getMobileNoId($inputs['mobile_no']);
                $this->db->select('u.mobile_no_id, u.user_id, u.user_type, u.first_name, u.last_name, mn.mobile_no, u.email_id, u.profile_pic, u.is_prime');
                $this->db->join('mobile_nos as mn', 'mn.mobile_no_id = u.mobile_no_id');
                $this->db->where(array('u.user_type' => $inputs['user_type'], 'u.mobile_no_id' => $mobile_no_id));
                $query = $this->db->get('users as u');
                $userInfo = $query->row();
                if ($query->num_rows() == 0 && $setUserData) {
                    $data = array(
                        'user_type' => $inputs['user_type'],
                        'mobile_no_id' => $mobile_no_id
                    );
                    $this->db->insert('users', $data);
                    $userInfo = $this->getSetUserData($inputs, false);
                }
            }
            return $userInfo;
		}

		public function getUserDataFromUserId($userId) {
			$userInfo = new stdClass();
			if ($userId) {
				$this->db->select("u.user_id, u.first_name, u.last_name, IFNULL(mn.mobile_no, '') as mobile_no, IFNULL(mv.is_verified, '') as is_verified, u.email_id, u.gender, u.birth_date, u.user_type");
				$this->db->join("mobile_nos as mn", "mn.mobile_no_id = u.mobile_no_id", "left");
				$this->db->join("mobile_verification as mv", "mv.mobile_no_id = mn.mobile_no_id AND mv.is_verified = 1", "left");
				$query = $this->db->get_where("users as u", array("user_id" => $userId));
				$userInfo = $query->first_row();
			}
			return $userInfo;
		}
		
		public function updateUserData($inputs) {
			$userInfo = new stdClass();
			try {
				if (count($inputs)) {
					$userInfo = $this->getUserDataFromUserId($inputs["user_id"]);
					$data = setData($inputs, 'first_name, last_name, email_id, gender, birth_date');
					if ($userInfo && count($data)) {
						if ($userInfo->mobile_no == null && isset($inputs["mobile_no"]) && !empty($inputs["mobile_no"])) {
							$this->db->select("mobile_no_id");
							$query = $this->db->get_where("mobile_nos", array("mobile_no" => $inputs["mobile_no"]));
							if ($query->num_rows()) {
								$mobileNumberId = $query->first_row()->mobile_no_id;
							} else {
								$this->db->insert("mobile_nos", array("mobile_no" => $inputs["mobile_no"]));
								$mobileNumberId = $this->db->insert_id();
							}
							$data["mobile_no_id"] = $mobileNumberId;
						}
						$this->db->set($data);
						$this->db->where(array('user_id' => $inputs['user_id']));
						$this->db->update("users");
						$userInfo = $this->getUserDataFromUserId($inputs["user_id"]);
					}
				}
			} catch (\Throwable $th) {
				throw $th;
			}
			return $userInfo;
        }

        public function verify_otp() {
            $error = false;
            $msg = "";
            $userInfo = new stdClass();
            $data = json_decode(file_get_contents('php://input'), true);
            try {
                $verifyMobileNumber = checkSetEmpty($data, array('mobile_no', 'otp', 'user_type'));
                if ($verifyMobileNumber || strlen($data['mobile_no']) != 10) {
                    $error = true;
                    $msg = $verifyMobileNumber ? $verifyMobileNumber : "Invalid mobile number";
                    $this->response($error, $msg, new stdClass());
                }
                $this->db->select('mv.mobile_verification_id, mv.is_verified');
                $this->db->join('mobile_nos as mn', 'mn.mobile_no_id = mv.mobile_no_id');
                $this->db->where(array('mn.mobile_no' => $data['mobile_no'], 'mv.otp' => $data['otp']));
                $query = $this->db->get('mobile_verification as mv');
                if ($query->num_rows()) {
                    $is_verified = $query->row()->is_verified;
                    if ($is_verified == 1) {
                        $error = true;
                        $msg = 'Otp already used';
                    } else {
                        $mobileVerificationId = $query->row()->mobile_verification_id;
                        $this->db->set('is_verified', 1);
                        $this->db->where('mobile_verification_id', $mobileVerificationId);
                        $this->db->update('mobile_verification');
                        if ($this->db->affected_rows()) {
                            $userInfo = $this->getSetUserData(array('user_type' => $data['user_type'], 'mobile_no' => $data['mobile_no']), true);
                        }
                        $msg = 'Otp verified successfully';
                    }
                } else {
                    $error = true;
                    $msg = 'Invalid otp';
                }
                $this->response($error, $msg, $userInfo);
            } catch (\Throwable $th) {
                //throw $th;
                $this->response(true, 'Exception', new stdClass());
            }
		}

		public function update_profile() {
			$error = false;
            $msg = "";
			$userInfo = new stdClass();
			$data = json_decode(file_get_contents('php://input'), true);

            try {
				$checkSetEmpty = checkSetEmpty($data, array('user_id'));
				if ($checkSetEmpty) {
                    $error = true;
                    $msg = $checkSetEmpty;
                } else {
					$userInfo = $this->updateUserData($data);
					if (!$userInfo) {
						$error = true;
						$msg = "Error while updating profile";
						$userInfo = new stdClass();
					}
				}
				$this->response($error, $msg, $userInfo);
            } catch (\Throwable $th) {
                throw $th;
                $this->response(true, 'Exception', new stdClass());
            }
		}

		public function get_profile() {
			$error = false;
            $msg = "";
			$userInfo = new stdClass();
			$data = json_decode(file_get_contents('php://input'), true);

            try {
				$checkSetEmpty = checkSetEmpty($data, array('user_id'));
				if ($checkSetEmpty) {
                    $error = true;
                    $msg = $checkSetEmpty;
                } else {
					$userInfo = $this->getUserDataFromUserId($data["user_id"]);
					if (!$userInfo) {
						$error = true;
						$msg = "Error while fetching data";
						$userInfo = new stdClass();
					}
				}
				$this->response($error, $msg, $userInfo);
            } catch (\Throwable $th) {
                //throw $th;
                $this->response(true, 'Exception', new stdClass());
            }
		}

		public function setUnsetBookmark() {
			$error = false;
            $msg = "";
			$data = json_decode(file_get_contents('php://input'), true);
            try {
				$checkSetEmpty = checkSetEmpty($data, array('bookmark', 'user_id', 'school_id'));
				if ($checkSetEmpty) {
                    $error = true;
                    $msg = $checkSetEmpty;
                } else {
					if ($data['bookmark'] === "true") {
						$this->db->select("school_id");
						$query = $this->db->get_where("bookmarks", array("school_id" => $data['school_id'], 'user_id' => $data['user_id']));
						if ($query->num_rows()) {
							$error = true;
							$msg = "Already Bookmarked";
						} else {
							$this->db->insert("bookmarks", array("school_id" => $data['school_id'], 'user_id' => $data['user_id']));
							$msg = "Set successfully";
						}
					} else {
						$this->db->delete("bookmarks", array("school_id" => $data['school_id'], 'user_id' => $data['user_id']));
						$msg = "Removed successfully";
					}
				}
				$this->response($error, $msg, new stdClass());
            } catch (\Throwable $th) {
                //throw $th;
                $this->response(true, 'Exception', new stdClass());
            }
		}

		public function bookmarksList() {
			$error = false;
			$msg = "";
			$schools = array();
			$data = json_decode(file_get_contents('php://input'), true);
            try {
				$checkSetEmpty = checkSetEmpty($data, array('user_id'));
				if ($checkSetEmpty) {
                    $error = true;
                    $msg = $checkSetEmpty;
                } else {
					$this->load->model('School_model', 'school');
					$this->school->school_group($data, true);
					// $this->db->where(array("bs.user_id" => $data['user_id']));
					$query = $this->db->get();
					$schools = $query->result_array();
					if ($schools && count($schools) === 1 && $schools[0]["school_id"] === null) {
						$schools = array();
					}
				}
            } catch (\Throwable $th) {
				throw $th;
				$error = true;
				$msg = 'Exception';
            }
			$this->response($error, $msg, $schools);
		}
		
		public function reset_password()
		{
			$error = false;
			$msg = '';
			
			try {
				$data = json_decode(file_get_contents('php://input'), true);
				$requiredParams = checkSetEmpty($data, array('email','admin_user_id','oldPassword','newPassword'));
				if ($requiredParams) {
					$error = true;
					$msg = $requiredParams;
				} else {
					$password = array('password' => md5($data['newPassword']));
					$this->db->set($password);
					$this->db->where('password',md5($data['oldPassword']));
					$this->db->where('admin_user_id',$data['admin_user_id']);
					$this->db->where('email_id',$data['email']);
					$this->db->update('admin_users');
					if ($this->db->affected_rows()) {
						$msg = 'Password Change Successfully';
					}else {
						$msg = 'Old Password Did Not Match';
					}
				}
			} catch (\Throwable $th) {
				$error = true;
				$msg = 'Exception';
			}
			$this->response($error, $msg, new stdClass());
		}
	
		public function find_user($data)
		{
			$error = false;
			$msg = '';
			$user = array();
			try {
					$this->db->select('au.admin_user_id, au.email_id');
					$this->db->join('mobile_nos as mn', 'mn.mobile_no_id = au.mobile_no_id','left');
					$this->db->where('au.email_id',$data['email']);
					$this->db->where('mn.mobile_no', $data['phone']);
					if ($data['schoolName'] !== 'admino') {
						$this->db->join('schools as s','s.school_id = au.school_id','left');
						$this->db->where('s.school_name',$data['schoolName']);
					}else{
						$this->db->where('au.user_type',1);
					}
					$this->db->where('au.admin_user_name',$data['name']);
					$query = $this->db->get('admin_users as au');
					$user = $query->result_array();
					if (!$user | count($user)>1) {
						$error = true;
						$msg = "Sorry can't Find You";
						return false;
					}else{
						$user = $user[0];
						$user['mobile_no'] = $data['phone'];
						$user['name'] = $data['name'];
						$msg = "Success";
						return $user;
				}
			} catch (\Throwable $th) {
				$error = true;
				$msg = 'Exception';
			}
		}

		public function send_link()
		{
			$error = false;
			$msg = '';
			$test ;
			// $response = array();
			try {
				$data = json_decode(file_get_contents('php://input'), true);
				$requiredParams = checkSetEmpty($data, array('email','phone','schoolName','name'));
				if ($requiredParams) {
					$error = true;
					$msg = $requiredParams;
				} else {
					$this->load->model('User_model','user');
					$response=$this->user->find_user($data);
					if ($response) {
						$expiredtime = 3600;//3600 seconds = 1 hour
						$uniqPasswordLink = md5(uniqid($response['email_id']));
						$reset = array(
							'user_id' =>$response['admin_user_id'] ,
							'password_link' =>$uniqPasswordLink,
							'created_date' => date('y-m-d h:i:s',time()),
							'expired_date' => date('y-m-d h:i:s',time()+$expiredtime), 
						);
						$this->db->insert('reset_password',$reset);
						$message = "Hello ". $response['name'] .", Welcome Back to admino, You Can Reset Your Password here https://admino.in/admino/dist/forgotpassword/resetpassword/". $uniqPasswordLink;
						$this->load->model('Sms_model','sms');
						$smsResponse = $this->sms->sendSmsWithoutotp($message,$response['mobile_no']);
						$msg = 'success';
					}else{
						$error = true;
						$msg = "Sorry can't Find You";
					}
				}
			} catch (\Throwable $th) {
				$error = true;
				$msg = 'Exception';
			}
			$this->response($error, $msg, new stdClass());
		}

		public function verify_key()
		{
			$error = false;
			$msg = "";
			$key = array();
			$data = json_decode(file_get_contents('php://input'), true);
            try {
				$checkSetEmpty = checkSetEmpty($data, array('key'));
				if ($checkSetEmpty) {
                    $error = true;
                    $msg = $checkSetEmpty;
                } else {
					$query = $this->db->get_where('reset_password',array('password_link' => $data['key']));
					$key = $query->first_row();
					if ($key) {
						$key->currenttime = date('Y-m-d h:i:s',time());
					}
					else{
						$error = true;
						$msg = 'Key Not Found';
					}
				}
            } catch (\Throwable $th) {
				throw $th;
				$error = true;
				$msg = 'Exception';
            }
			$this->response($error, $msg, $key);
		}
		
		public function update_password()
		{
			$error = false;
			$msg = "";
			$data = json_decode(file_get_contents('php://input'), true);
            try {
				$checkSetEmpty = checkSetEmpty($data, array('user_id','password','id'));
				if ($checkSetEmpty) {
                    $error = true;
                    $msg = $checkSetEmpty;
                } else {
					$password = array('password' => md5($data['password']) );
					$this->db->set($password);
					$this->db->where('admin_user_id',$data['user_id']);
					$this->db->update('admin_users');
					if ($this->db->affected_rows()) {
						$array = array('is_used' => 1 );
						$this->db->set($array);
						$this->db->where('id',$data['id']);
						$this->db->update('reset_password');
						$msg = 'Success';
					}

				}
            } catch (\Throwable $th) {
				throw $th;
				$error = true;
				$msg = 'Exception';
            }
			$this->response($error, $msg, new stdClass());
		}
	}
?>
