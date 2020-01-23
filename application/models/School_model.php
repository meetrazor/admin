<?php
defined('BASEPATH') or exit('No direct script access allowed');
class School_model extends CI_Model
{

	function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->helper('app_helper');
	}

	public function response($error = true, $msg = '', $data = array())
	{
		echo json_encode(array('error' => $error, 'message' => $msg, 'data' => $data));
		exit;
	}

	public function available_states()
	{
		$error = false;
		$msg = '';
		try {
			$action = $this->input->get("action");
			if ($action && $action === "all") {
				$this->db->select("state_id, state_name");
				$query = $this->db->get("states");
			} else {
				$query = $this->db->query("call list_of_available_states");
			}
			$states = $query->result_array();
			$this->response($error, $msg, $states);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', array());
		}
	}

	public function available_cities()
	{
		$error = false;
		$msg = '';
		$cities = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('state'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$stateId = $data['state'];
				$action = $this->input->get("action");
				if ($action && $action === "all") {
					$this->db->select("city_id, city_name");
					$this->db->where("state_id", $stateId);
					$query = $this->db->get("cities");
				} else {
					$query = $this->db->query("call list_of_available_cities($stateId)");
				}
				$cities = $query->result_array();
			}
			$this->response($error, $msg, $cities);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', array());
		}
	}

	public function boards()
	{
		$error = false;
		$msg = '';
		try {
			$this->db->select("board_id, board_name, is_deleted");
			$this->db->order_by("board_id");
			$query = $this->db->get("boards");
			$boards = $query->result_array();
			$this->response($error, $msg, $boards);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', array());
		}
	}

	public function school_types()
	{
		$error = false;
		$msg = '';
		try {
			$this->db->select("school_type_id, type_name");
			$this->db->order_by("school_type_id");
			$query = $this->db->get("school_types");
			$school_types = $query->result_array();
			$this->response($error, $msg, $school_types);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', array());
		}
	}

	public function mediums()
	{
		$error = false;
		$msg = '';
		try {
			$this->db->select("medium_id, medium");
			$this->db->order_by("medium_id");
			$query = $this->db->get("mediums");
			$mediums = $query->result_array();
			$this->response($error, $msg, $mediums);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', array());
		}
	}

	public function relation_with_school()
	{
		$error = false;
		$msg = '';
		try {
			$this->db->select("relation_with_school_id, relation_with_school");
			$this->db->order_by("relation_with_school_id");
			$query = $this->db->get("relation_with_school");
			$relation_with_school = $query->result_array();
			$this->response($error, $msg, $relation_with_school);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', array());
		}
	}

	public function schools_list()
	{
		$error = false;
		$msg = '';
		$schools = array();
		try {
			$this->db->select("school_id, school_name, is_approved, is_deleted");
			$this->db->where("is_deleted", 0);
			$this->db->order_by("school_id");
			$query = $this->db->get("schools");
			$schools = $query->result_array();
			$this->response($error, $msg, $schools);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', array());
		}
	}

	public function schools_search()
	{
		$error = false;
		$msg = '';
		$schools = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('search'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select("s.school_id, s.school_name");
				$this->db->like("school_name", $data["search"]);
				$this->db->join("medium_link as ml", "ml.school_id = s.school_id", "left");
				$this->db->join("mediums as m", "m.medium_id = ml.medium_id", "left");
				$this->db->join("boards_link as bl", "bl.school_id = s.school_id", "left");
				$this->db->join("boards as b", "b.board_id = bl.board_id", "left");
				if (isset($data["board_id"]) && !empty($data["board_id"])) {
					$this->db->where("bl.board_id", $data["board_id"]);
				}
				if (isset($data["medium_id"]) && !empty($data["medium_id"])) {
					$this->db->where("ml.medium_id", $data["medium_id"]);
				}
				$this->db->where("s.is_approved", 1);
				$this->db->where("s.is_deleted", 0);
				$this->db->group_by("s.school_id");
				$this->db->limit(15);
				$query = $this->db->get("schools as s");
				$schools = $query->result_array();
			}
			$this->response($error, $msg, $schools);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', array());
		}
	}

	public function schools_filter()
	{
		$error = false;
		$msg = '';
		$schools = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('search', 'city_id', 'state_id'));
			$checkLatLong = checkSetEmpty($data, array('lat', 'long'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->school_group($data, false);
				$this->db->group_start();
				$this->db->like("s.school_name", $data["search"]);
				$this->db->or_like("c.city_name", $data["search"]);
				$this->db->or_like("st.state_name", $data["search"]);
				$this->db->group_end();
				if (isset($data["board_id"]) && !empty($data["board_id"])) {
					$this->db->where("bl.board_id", $data["board_id"]);
				}

				if (isset($data["school_type_id"]) && !empty($data["school_type_id"])) {
					$this->db->where("s.school_type_id", $data["school_type_id"]);
				}
				if (isset($data["medium_id"]) && !empty($data["medium_id"])) {
					$this->db->where("ml.medium_id", $data["medium_id"]);
				}
				$this->db->where(array("c.city_id" => $data["city_id"], "st.state_id" => $data["state_id"]));
				$this->db->group_by("s.school_id");
				if (!$checkLatLong) {
					$this->db->order_by("distanceOrder");
				}
				$this->db->limit(25);
				$query = $this->db->get();
				$schools = $query->result_array();
			}
			$this->response($error, $msg, $schools);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', array());
		}
	}

	public function school_profile()
	{
		$error = false;
		$msg = '';
		$school = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('school_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$checkLatLong = checkSetEmpty($data, array('lat', 'long'));
				if ($checkLatLong) {
					$distance = "'' as distance";
				} else {
					$lat = $data['lat'];
					$long = $data['long'];
					$distance = "CONCAT(getDistance(s.latitude, s.longitude, '$lat', '$long'), ' km') as distance";
				}
				$this->db->select("s.school_id, s.class_range, s.established, ms.mobile_no as mobile_no_id, s.school_email_id, s.scholarship_description, s.description, s.latitude, s.longitude, IF(bs.bookmark_id IS NULL, 'false', 'true') as bookmark, s.timings, s.address, s.school_name, IFNULL(s.banner, '') as banner, IFNULL(b.board_name, '') as board_name, IFNULL(GROUP_CONCAT(DISTINCT CONCAT(m.medium) separator ', '), '') as medium, s.admission_open, s.apply_for_admission, " . $distance);
				$this->db->join("medium_link as ml", "ml.school_id = s.school_id", "left");
				$this->db->join("mediums as m", "m.medium_id = ml.medium_id", "left");
				$this->db->join("boards_link as bl", "bl.school_id = s.school_id", "left");
				$this->db->join("boards as b", "b.board_id = bl.board_id", "left");
				$this->db->join("mobile_nos as ms", "ms.mobile_no_id = s.mobile_no_id", "left");
				if (!isset($data["user_id"]) || !$data["user_id"]) {
					$data["user_id"] = 0;
				}
				$this->db->join("bookmarks as bs", "bs.school_id = s.school_id AND bs.user_id = " . $data["user_id"], "left");
				$this->db->where(array("s.school_id" => $data["school_id"]));
				$this->db->group_by("s.school_id");
				$this->db->limit(1);
				$query = $this->db->get("schools as s");
				$school = $query->first_row();

				$this->db->select("path");
				$this->db->where("school_id", $data["school_id"]);
				$this->db->where("is_deleted", 0);
				$query = $this->db->get("school_gallery");
				if ($query->num_rows()) {
					$school->other_images = $query->result_array();
					$school->other_images = array_column($school->other_images, "path");
				} else {
					$school->other_images = array();
				}
			}

			$this->response($error, $msg, $school);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', new stdClass());
		}
	}

	public function add_new_school()
	{
		$error = false;
		$msg = '';
		$school_id = 0;
		$school_update = false;
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array(
				'school_name',
				'state_id',
				'city_id',
				'description',
				'address',
				'contact_name',
				'phone_no',
				'relation_with_school_id',
				'mediums',
				'boards',
				'school_type_id',
				'latitude',
				'longitude',
				'class_range'
			));

			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$school_id = isset($data["school_id"]) && !empty($data["school_id"]) ? $data["school_id"] : false;
				if (isset($data["mobile_no_id"]) && $data["mobile_no_id"]) {
					$this->load->model('User_model', 'user');
					$data["mobile_no_id"] = $this->user->getMobileNoId($data["mobile_no_id"]);
				}
				if ($school_id) {
					$school_update = true;
					$school = array(
						'school_name' => $data['school_name'],
						'school_email_id' => $data['school_email_id'],
						'description' => $data['description'],
						'mobile_no_id' => $data['mobile_no_id'],
						'scholarship_description' => $data['scholarship_description'],
						'timings' => $data['timings'],
						'school_type_id' => $data['school_type_id'],
						'city_id' => $data['city_id'],
						'latitude' => $data['latitude'],
						'longitude' => $data['longitude'],
						'address' => $data['address'],
						'website' => $data['website'],
						'admission_open' => $data['admission_open'],
						'class_range' => $data['class_range'],
						'established' => $data['established']
					);
					$this->db->set($school);
					$this->db->where("school_id", $school_id);
					$this->db->update("schools");
				} else {
					$school = setData($data, "school_name, class_range, established, school_email_id, description, mobile_no_id, scholarship_description, timings, school_type_id, city_id, latitude, longitude, address, website, admission_open");
					$this->db->insert("schools", $school);
					$school_id = $this->db->insert_id();
				}

				if ($school_update && $school_id) {
					$contact_details = array(
						'contact_name' => $data['contact_name'],
						'relation_with_school_id' => $data['relation_with_school_id'],
						'phone_no' => $data['phone_no'],
						'email_id' => $data['email_id']
					);
					$this->db->set($contact_details);
					$this->db->where(array("school_id" => $school_id, "is_primary" => 1));
					$this->db->update("contact_details");
				} else {
					$contact_details = setData($data, "contact_name, relation_with_school_id, phone_no, email_id");
					$contact_details["school_id"] = $school_id;
					$contact_details["is_primary"] = 1;
					$this->db->insert("contact_details", $contact_details);
				}

				$boards = array();
				foreach ($data['boards'] as $board) {
					if (isset($board['selected']) && $board['selected']) {
						array_push($boards, array('board_id' => $board['board_id'], 'school_id' => $school_id));
					}
				}
				if ($school_update && $school_id) {
					$this->db->where("school_id", $school_id);
					$this->db->delete("boards_link");
				}
				if (count($boards)) {
					$this->db->insert_batch("boards_link", $boards);
				}

				$mediums = array();
				foreach ($data['mediums'] as $medium) {
					if (isset($medium['selected']) && $medium['selected']) {
						array_push($mediums, array('medium_id' => $medium['medium_id'], 'school_id' => $school_id));
					}
				}
				if ($school_update && $school_id) {
					$this->db->where("school_id", $school_id);
					$this->db->delete("medium_link");
				}
				if (count($mediums)) {
					$this->db->insert_batch("medium_link", $mediums);
				}
			}
			$this->response($error, $msg, array("school_id" => $school_id));
		} catch (\Throwable $th) {
			throw $th;
			$this->response(true, 'Exception', new stdClass());
		}
	}

	public function school_group($data, $forBookmark)
	{
		$checkLatLong = checkSetEmpty($data, array('lat', 'long'));
		if ($checkLatLong) {
			$distance = "'' as distance";
		} else {
			$lat = $data['lat'];
			$long = $data['long'];
			$distance = "getDistance(s.latitude, s.longitude, '$lat', '$long') as distanceOrder, CONCAT(getDistance(s.latitude, s.longitude, '$lat', '$long'), ' km') as distance";
		}
		$this->db->select("s.school_id, s.latitude, s.longitude, s.school_name, IFNULL(sg.path, '') as banner, IF(bs.bookmark_id IS NULL, 'false', 'true') as bookmark, IFNULL(b.board_name, '') as board_name, IFNULL(GROUP_CONCAT(DISTINCT CONCAT(m.medium) separator ', '), '') as medium, s.admission_open, s.apply_for_admission, " . $distance);
		$this->db->from("schools as s");
		$this->db->join("cities as c", "c.city_id = s.city_id");
		$this->db->join("school_gallery as sg", "sg.school_gallery_id = s.banner", "left");
		$this->db->join("states as st", "st.state_id = c.state_id");
		$this->db->join("medium_link as ml", "ml.school_id = s.school_id", "left");
		$this->db->join("mediums as m", "m.medium_id = ml.medium_id", "left");
		$this->db->join("boards_link as bl", "bl.school_id = s.school_id", "left");
		$this->db->join("boards as b", "b.board_id = bl.board_id", "left");
		if (!isset($data["user_id"]) || !$data["user_id"]) {
			$data["user_id"] = 0;
		}
		$this->db->join("bookmarks as bs", "bs.school_id = s.school_id AND bs.user_id = " . $data["user_id"], $forBookmark ? "" : "left");
		$this->db->where("s.is_approved", 1);
		$this->db->where("s.is_deleted", 0);
	}

	public function banners()
	{
		$error = false;
		$msg = '';
		$banners = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('city_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select("b.banner_id, b.banner_path, b.school_id");
				$this->db->join("schools as s", "s.school_id = b.school_id");
				$this->db->where(array("b.is_deleted" => 0, "b.is_approved" => 1, "s.is_deleted" => 0, "s.is_approved" => 1, "s.city_id" => $data["city_id"]));
				$this->db->order_by("b.priority_order");
				$query = $this->db->get("banners as b");
				$banners = $query->result_array();
			}
			$this->response($error, $msg, $banners);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', new stdClass());
		}
	}

	public function get_banners()
	{
		$error = false;
		$msg = '';
		$banners = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$this->db->select("b.banner_id, CONCAT('" . base_url() . "', b.banner_path) as banner_path, b.school_id, s.school_name, b.plan_type, b.is_paid, b.expiry_time, b.is_approved, b.is_deleted");
			$this->db->join("schools as s", "s.school_id = b.school_id");
			$this->db->where("b.is_deleted", "0");
			if ($data['school_id']) {
				$this->db->where("b.school_id", $data["school_id"]);
			}
			$query = $this->db->get("banners as b");
			$banners = $query->result_array();
			$this->response($error, $msg, $banners);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', new stdClass());
		}
	}

	public function banner_upload()
	{
		$error = false;
		$msg = '';
		try {
			$school_id = $this->input->post("school_id");
			$city_id = $this->input->post("city_id");
			$state_id = $this->input->post("state_id");
			$plan_type = $this->input->post("plan_type");
			if (isset($_FILES['banner']) && $school_id > 0) {
				$this->db->select("school_id");
				$query = $this->db->get_where("schools", array('school_id' => $school_id));
				$school = $query->first_row();
				if ($school && isset($school->school_id)) {
					$extension = pathinfo($_FILES["banner"]["name"], PATHINFO_EXTENSION);
					if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png') {
						$target_path = 'banners/' . md5(uniqid($school_id, true)) . '.' . $extension;
						if (move_uploaded_file($_FILES['banner']['tmp_name'], $target_path)) {
							$insert_banner = array(
								"banner_path" => $target_path,
								"school_id" => $school_id,
								"city_id" => $city_id,
								"state_id" => $state_id,
								"plan_type" => $plan_type
							);
							$this->db->insert("banners", $insert_banner);
							$msg = "File uploaded successfully!";
						} else {
							$msg = "Sorry, file not uploaded, please try again!";
						}
					} else {
						$msg = "File is not image";
					}
				} else {
					$msg = "School not found";
				}
			} else {
				$msg = "Sorry, file not uploaded, please try again!";
			}
		} catch (\Throwable $th) {
			// throw $th;
			$msg = 'Exception';
		}
		$this->response($error, $msg, new stdClass());
	}

	public function school_info($school_id)
	{
		$error = false;
		$msg = '';
		$school = new stdClass();
		try {
			if (!$school_id) {
				$error = true;
				$msg = "";
			} else {
				$this->db->select("s.school_name, s.established, s.class_range, m.mobile_no as mobile_no_id, s.school_email_id, s.scholarship_description, s.timings, CONCAT('" . base_url() . "', s.banner) as imgURL, c.state_id, s.description, s.school_type_id, s.city_id, s.latitude, s.longitude, s.address, s.website, s.admission_open");
				$this->db->join("cities as c", "c.city_id = s.city_id");
				$this->db->join("mobile_nos as m", "m.mobile_no_id = s.mobile_no_id", "left");
				$this->db->where("school_id", $school_id);
				$query = $this->db->get("schools as s");
				$school_info = $query->first_row();

				$this->db->select("contact_name, relation_with_school_id, phone_no, email_id");
				$this->db->where("school_id", $school_id);
				$query = $this->db->get("contact_details");
				$contact = $query->first_row();
				$school = (object) array_merge((array) $school_info, (array) $contact);

				$this->db->select("board_id, 'true' as selected");
				$this->db->where("school_id", $school_id);
				$query = $this->db->get("boards_link");
				$school->boards = $query->result_array();

				$this->db->select("medium_id, 'true' as selected");
				$this->db->where("school_id", $school_id);
				$query = $this->db->get("medium_link");
				$school->mediums = $query->result_array();

				$this->db->select("CONCAT('" . base_url() . "', path) as path");
				$this->db->where("school_id", $school_id);
				$this->db->where("is_deleted", 0);
				$query = $this->db->get("school_gallery");
				$school->other_images = $query->result_array();
				$school->other_images = array_column($school->other_images, "path");
			}

			$this->response($error, $msg, $school);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', new stdClass());
		}
	}

	public function admission_request()
	{
		$error = false;
		$msg = 'Success';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array(
				'first_name',
				'last_name',
				'user_id',
				'school_id',
				'gender',
				'birth_date',
				'email_id',
				'mobile_no_id',
				'standard',
				'city_id'
			));

			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				if (isset($data["mobile_no_id"]) && $data["mobile_no_id"]) {
					$this->load->model('User_model', 'user');
					$data["mobile_no_id"] = $this->user->getMobileNoId($data["mobile_no_id"]);
				}

				$admission_request = setData($data, 'first_name, last_name, user_id, school_id, gender, birth_date, email_id, mobile_no_id, standard, city_id, description');
				$this->db->insert("admission_requests", $admission_request);
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, new stdClass());
	}

	public function admission_requests_list()
	{
		$error = false;
		$msg = 'Success';
		$admission_requests_list = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('user_id'));

			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select("a.first_name, a.last_name, a.user_id, a.school_id, s.school_name, IFNULL(sg.path, '') as banner, a.gender, a.birth_date, a.email_id, m.mobile_no as mobile_no_id, a.standard, a.city_id, c.city_name, a.description");
				$this->db->join("cities as c", "c.city_id = a.city_id");
				$this->db->join("mobile_nos as m", "m.mobile_no_id = a.mobile_no_id");
				$this->db->join("schools as s", "s.school_id = a.school_id");
				$this->db->join("school_gallery as sg", "sg.school_gallery_id = s.banner", "left");
				$this->db->where("user_id", $data["user_id"]);
				$query = $this->db->get("admission_requests as a");
				$admission_requests_list = $query->result_array();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $admission_requests_list);
	}

	public function admission_requests_list_by_school()
	{
		$error = false;
		$msg = 'Success';
		$admission_requests = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$this->db->select("a.admission_request_id, a.first_name, a.last_name, a.user_id, a.school_id, s.school_name, IFNULL(sg.path, '') as banner, a.gender, a.birth_date, a.email_id, m.mobile_no as mobile_no_id, a.standard, a.city_id, c.city_name, a.description, a.is_deleted");
			$this->db->join("cities as c", "c.city_id = a.city_id");
			$this->db->join("mobile_nos as m", "m.mobile_no_id = a.mobile_no_id");
			$this->db->join("schools as s", "s.school_id = a.school_id");
			$this->db->join("school_gallery as sg", "sg.school_gallery_id = s.banner", "left");
			$this->db->where("a.is_deleted", "0");
			if ($data["school_id"]) {
				$this->db->where("s.school_id", $data["school_id"]);
			}
			$query = $this->db->get("admission_requests as a");
			$admission_requests = $query->result_array();
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $admission_requests);
	}

	public function school_gallery()
	{
		$error = false;
		$msg = 'Success';
		$school_gallery = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('school_id'));

			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select('path');
				$this->db->where(array("school_id" => $data["school_id"], "document_type" => 1));
				$query = $this->db->get("school_gallery");
				$school_gallery = $query->result_array();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $school_gallery);
	}

	public function school_gallery_upload()
	{
		$error = false;
		$msg = '';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('school_id', 'filesToUpload'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$school_id = $data["school_id"];
				$school_gallery = array();
				foreach ($data['filesToUpload'] as $data) {
					if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
						$data = substr($data, strpos($data, ',') + 1);
						$type = strtolower($type[1]); // jpg, png, gif
						if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
							$error = true;
							$msg = 'invalid image type';
						} else {
							$data = base64_decode($data);
							if ($data === false) {
								$msg = 'base64_decode failed';
							} else {
								$target_path = 'banners/' . md5(uniqid($school_id, true)) . '.' . $type;
								file_put_contents($target_path, $data);
								array_push($school_gallery, array("school_id" => $school_id, "document_type" => 1, "path" => $target_path));
							}
						}
					} else {
						$msg = 'did not match data URI with image data';
					}
				}
				if (count($school_gallery)) {
					$this->db->insert_batch("school_gallery", $school_gallery);
					$this->db->select("school_gallery_id");
					$query = $this->db->get_where("school_gallery", array("school_id" => $school_id));
					$school_gallery_id = $query->first_row()->school_gallery_id;
					$this->db->set("banner", $school_gallery_id);
					$this->db->where("school_id", $school_id);
					$this->db->update("schools");
				} else {
					$error = true;
					$msg = "No Images";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, new stdClass());
	}

	public function school_gallery_delete_image()
	{
		$error = false;
		$msg = '';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('school_id', 'img_url'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$school_id = $data["school_id"];
				$img_url = str_replace(base_url(), "", $data["img_url"]);
				$this->db->set("is_deleted", 1);
				$this->db->where(array("school_id" => $school_id, "path" => $img_url));
				$this->db->update("school_gallery");
				if ($this->db->affected_rows()) {
					$msg = "Image deleted successfully";
				} else {
					$error = true;
					$msg = "Error while deleting image";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg);
	}

	public function delete_school()
	{
		$error = false;
		$msg = '';
		$school = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('school_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_deleted", 1);
				$this->db->where("school_id", $data["school_id"]);
				$this->db->update("schools");

				if ($this->db->affected_rows()) {
					$this->db->set("is_deleted", 1);
					$this->db->where("school_id", $data["school_id"]);
					$this->db->update("admin_users");
					$msg = "Deleted Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $school);
	}

	public function recover_school()
	{
		$error = false;
		$msg = '';
		$school = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('school_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_deleted", 0);
				$this->db->where("school_id", $data["school_id"]);
				$this->db->update("schools");

				if ($this->db->affected_rows()) {
					$this->db->set("is_deleted", 0);
					$this->db->where("school_id", $data["school_id"]);
					$this->db->update("admin_users");
					$msg = "Recovered Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $school);
	}

	public function approve_school()
	{
		$error = false;
		$msg = '';
		$school = new stdClass();
		$password = '';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('school_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select("cd.contact_detail_id, cd.contact_name, cd.phone_no, cd.email_id, s.is_approved");
				$this->db->join("contact_details as cd", "cd.school_id=s.school_id AND cd.is_primary = 1");
				$this->db->where("s.school_id", $data["school_id"]);
				$query = $this->db->get("schools as s");
				$school = $query->first_row();

				if ($school && $school->is_approved == 0) {
					$password = random_strings(8);
					$this->load->model('Sms_model', 'sms');
					$this->sms->sendSms("Hello " . $school->contact_name . ", Welcome to admino, Your school is approved. Use your Email ID:$school->email_id and Password:$password to login https://admino.in/admino/dist/login", $school->phone_no, false);
					$this->load->model('User_model', 'user');
					$mobile_no_id = $this->user->getMobileNoId($school->phone_no);
					$admin_user = array(
						"email_id" => $school->email_id,
						"password" => md5($password),
						"user_type" => 3,
						"admin_user_name" => $school->contact_name,
						"school_id" => $data["school_id"],
						"mobile_no_id" => $mobile_no_id
					);

					$this->db->insert("admin_users", $admin_user);
				}

				$updateSchool = array("is_approved" => 1);
				$this->db->set($updateSchool);
				$this->db->where("school_id", $data["school_id"]);
				$this->db->update("schools");

				if ($this->db->affected_rows()) {
					$this->load->model('Email_model', 'email');
					$this->email->school_login($data["school_id"]);
					$msg = "Approved Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $school);
	}

	public function reject_school()
	{
		$error = false;
		$msg = '';
		$school = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('school_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_approved", 2);
				$this->db->where("school_id", $data["school_id"]);
				$this->db->update("schools");

				if ($this->db->affected_rows()) {
					$msg = "Rejected Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $school);
	}

	public function job_request()
	{
		$error = false;
		$msg = 'Success';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array(
				'first_name',
				'last_name',
				'user_id',
				'school_id',
				'gender',
				'birth_date',
				'email_id',
				'mobile_no_id',
				'city_id',
				'description'
			));

			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				if (isset($data["mobile_no_id"]) && $data["mobile_no_id"]) {
					$this->load->model('User_model', 'user');
					$data["mobile_no_id"] = $this->user->getMobileNoId($data["mobile_no_id"]);
				}

				if (isset($data['photo']) && $data['photo']) {
					$photo = $data['photo'];
					if (preg_match('/^data:image\/(\w+);base64,/', $photo, $type)) {
						$photo = substr($photo, strpos($photo, ',') + 1);
						$type = strtolower($type[1]); // jpg, png, gif
						if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
							$error = true;
							$msg = 'invalid image type';
						} else {
							$photo = base64_decode($photo);
							if ($photo === false) {
								$msg = 'base64_decode failed';
							} else {
								$target_path = 'user_photo/' . md5(uniqid($data['user_id'], true)) . '.' . $type;
								file_put_contents($target_path, $photo);
								$data['photo'] = $target_path;
							}
						}
					} else {
						$error = true;
						$msg = 'Did not match data URI with image data';
					}
				}

				if (!$error) {
					$job_request = setData($data, 'first_name, qualification, photo, last_school, last_name, user_id, school_id, gender, birth_date, email_id, mobile_no_id, city_id, description, experience_years, experience_months');
					$this->db->insert("job_requests", $job_request);
					$job_request_id = $this->db->insert_id();
					if (isset($_FILES["resume"]) && $_FILES["resume"]) {
						$extension = pathinfo($_FILES["resume"]["name"], PATHINFO_EXTENSION);
						if ($extension == 'txt' || $extension == 'doc' || $extension == 'pdf') {
							if (!is_dir('resume')) {
								mkdir('resume');
							}
							$target_path = 'resume/' . md5(uniqid($job_request_id, true)) . '.' . $extension;
							if (move_uploaded_file($_FILES['resume']['tmp_name'], $target_path)) {
								$insert_resume = array(
									"resume_path" => $target_path,
								);
								$this->db->where("job_request_id", $job_request_id);
								$this->db->update("job_request", $insert_resume);
								$msg = "File uploaded successfully!";
							} else {
								$error = true;
								$msg = "Sorry, file not uploaded, please try again!";
							}
						} else {
							$error = true;
							$msg = "File is not supported";
						}
					}
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, new stdClass());
	}

	public function job_requests_list()
	{
		$error = false;
		$msg = 'Success';
		$job_requests = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('user_id'));

			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select("a.first_name, a.last_name, a.experience_years, a.experience_months, a.user_id, a.school_id, s.school_name, IFNULL(sg.path, '') as banner, a.gender, a.birth_date, a.email_id, m.mobile_no as mobile_no_id, a.city_id, c.city_name, a.description");
				$this->db->join("cities as c", "c.city_id = a.city_id");
				$this->db->join("mobile_nos as m", "m.mobile_no_id = a.mobile_no_id");
				$this->db->join("schools as s", "s.school_id = a.school_id");
				$this->db->join("school_gallery as sg", "sg.school_gallery_id = s.banner", "left");
				$this->db->where("user_id", $data["user_id"]);
				$query = $this->db->get("job_requests as a");
				$job_requests = $query->result_array();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $job_requests);
	}

	public function job_requests_list_by_school()
	{
		$error = false;
		$msg = 'Success';
		$job_requests = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$this->db->select("a.job_request_id, a.first_name, a.last_name, a.experience_years, a.experience_months, a.user_id, a.school_id, s.school_name, IFNULL(sg.path, '') as banner, a.gender, a.birth_date, a.email_id, m.mobile_no as mobile_no_id, a.city_id, c.city_name, a.description, a.is_deleted");
			$this->db->join("cities as c", "c.city_id = a.city_id");
			$this->db->join("mobile_nos as m", "m.mobile_no_id = a.mobile_no_id");
			$this->db->join("schools as s", "s.school_id = a.school_id");
			$this->db->join("school_gallery as sg", "sg.school_gallery_id = s.banner", "left");
			$this->db->where("a.is_deleted", "0");
			if ($data["school_id"]) {
				$this->db->where("a.school_id", $data["school_id"]);
			}
			$query = $this->db->get("job_requests as a");
			$job_requests = $query->result_array();
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $job_requests);
	}

	public function school_login()
	{
		$error = false;
		$msg = "";
		$userInfo = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$normalLogin = checkSetEmpty($data, array('username', 'password'));
			if ($normalLogin) {
				$error = true;
				$this->response($error, $normalLogin, $userInfo);
			}

			$this->db->select("a.admin_user_id, a.admin_user_name, a.user_type, a.school_id, s.is_prime, s.expiry_time,");
			$this->db->join("schools as s", "s.school_id = a.school_id", "left");
			$this->db->where("email_id", $data["username"]);
			$this->db->where("password", md5($data["password"]));
			$this->db->where("a.is_deleted", 0);
			$query = $this->db->get("admin_users as a");
			$userInfo = $query->first_row();

			if (isset($userInfo)) {
				$error = false;
			} else {
				$msg = 'Invalid credential';
				$error = true;
				$userInfo = new stdClass();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $userInfo);
	}

	public function admin_users_list()
	{
		$error = false;
		$msg = "";
		$userInfo = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('user_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select("admin_user_id, admin_user_name, user_type, school_id");
				$this->db->where('admin_user_id', $data["user_id"]);
				$query = $this->db->get("admin_users");
				$userInfo = $query->first_row();
				if (isset($userInfo)) {
					$error = false;
					$this->db->select("a.admin_user_id, a.admin_user_name, a.email_id, a.user_type, a.school_id, a.is_deleted, IFNULL(s.school_name, 'Admino') as school_name");
					$this->db->join("schools as s", "s.school_id = a.school_id", "left");
					if (!($userInfo->user_type == 1)) {
						$this->db->where('a.school_id', $data["school_id"]);
					}
					$this->db->where("a.is_deleted", "0");
					$query = $this->db->get("admin_users as a");
					$userInfo = $query->result_array();
				} else {
					$error = true;
					$msg = 'Invalid User Id';
					$userInfo = new stdClass();
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $userInfo);
	}

	public function delete_admin_user()
	{
		$error = false;
		$msg = '';
		$admin_user = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('admin_user_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_deleted", 1);
				$this->db->where("admin_user_id", $data["admin_user_id"]);
				$this->db->update("admin_users");

				if ($this->db->affected_rows()) {
					$msg = "Deleted Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $admin_user);
	}

	public function recover_admin_user()
	{
		$error = false;
		$msg = '';
		$admin_user = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('admin_user_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_deleted", 0);
				$this->db->where("admin_user_id", $data["admin_user_id"]);
				$this->db->update("admin_users");

				if ($this->db->affected_rows()) {
					$msg = "Recovered Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $admin_user);
	}

	public function add_new_user()
	{
		$error = false;
		$msg = '';
		$admin_user = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('admin_user_name', 'email_id', 'mobile_no_id', 'relation_with_school_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
				$phone_no = '';
			} else {
				if (isset($data["mobile_no_id"]) && $data["mobile_no_id"]) {
					$phone_no = $data["mobile_no_id"];
					$this->load->model('User_model', 'user');
					$data["mobile_no_id"] = $this->user->getMobileNoId($data["mobile_no_id"]);
				}

				$this->db->select("admin_user_id, admin_user_name, user_type, school_id");
				$this->db->where("email_id", $data["email_id"]);
				$query = $this->db->get("admin_users");
				$userInfo = $query->first_row();
				if ($userInfo) {
					$error = true;
					$msg = "Email already exists";
				} else {
					$password = random_strings(8);
					$this->load->model('Sms_model', 'sms');
					$this->sms->sendSms("Hello " . $data["admin_user_name"] . ", Welcome to admino, Your school is approved. Use your Email ID:" . $data["email_id"] . " and Password:$password to login https://admino.in/admino/dist/login", $phone_no, false);

					$admin_users = setData($data, "admin_user_name, email_id, mobile_no_id, relation_with_school_id, school_id");
					$admin_users["password"] = md5($password);

					$this->db->insert("admin_users", $admin_users);
					$admin_user_id = $this->db->insert_id();
					if ($admin_user_id) {
						$msg = "Success";
					} else {
						$error = true;
						$msg = "Error";
					}
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $admin_user);
	}

	public function get_schools()
	{
		$error = false;
		$msg = '';
		$schools = array();
		try {
			$this->db->select("school_id, school_name");
			$this->db->where("is_deleted", 0);
			$this->db->where("is_approved", 1);
			$query = $this->db->get("schools");
			$schools = $query->result_array();
			$this->response($error, $msg, $schools);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', array());
		}
	}

	public function delete_banner()
	{
		$error = false;
		$msg = '';
		$banner = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('banner_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_deleted", 1);
				$this->db->where("banner_id", $data["banner_id"]);
				$this->db->update("banners");

				if ($this->db->affected_rows()) {
					$msg = "Deleted Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $banner);
	}

	public function recover_banner()
	{
		$error = false;
		$msg = '';
		$banner = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('banner_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_deleted", 0);
				$this->db->where("banner_id", $data["banner_id"]);
				$this->db->update("banners");

				if ($this->db->affected_rows()) {
					$msg = "Recovered Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $banner);
	}

	public function recent_search()
	{
		$error = false;
		$msg = '';
		$schools = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('school_id'));
			$checkLatLong = checkSetEmpty($data, array('lat', 'long'));
			if (!$requiredParams && is_array($data["school_id"])) {
				$this->school_group($data, false);
				$this->db->where_in("s.school_id", $data['school_id']);
				$this->db->group_by("s.school_id");
				if (!$checkLatLong) {
					$this->db->order_by("distanceOrder");
				}
				$this->db->limit(25);
				$query = $this->db->get();
				$schools = $query->result_array();
			} else {
				$error = true;
				$msg = $requiredParams;
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
			$schools = array();
		}
		$this->response($error, $msg, $schools);
	}

	public function approve_banner()
	{
		$error = false;
		$msg = '';
		$banner = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('banner_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select("banner_id, is_approved");
				$query = $this->db->get("banners");
				$banner = $query->first_row();

				$updatebanner = array("is_approved" => 1);
				$this->db->set($updatebanner);
				$this->db->where("banner_id", $data["banner_id"]);
				$this->db->update("banners");

				if ($this->db->affected_rows()) {
					$msg = "Approved Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $banner);
	}

	public function reject_banner()
	{
		$error = false;
		$msg = '';
		$banner = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('banner_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_approved", 2);
				$this->db->where("banner_id", $data["banner_id"]);
				$this->db->update("banners");

				if ($this->db->affected_rows()) {
					$msg = "Rejected Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $banner);
	}

	public function delete_admission_request()
	{
		$error = false;
		$msg = '';
		$admission_request = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('admission_request_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_deleted", 1);
				$this->db->where("admission_request_id", $data["admission_request_id"]);
				$this->db->update("admission_requests");

				if ($this->db->affected_rows()) {
					$msg = "Deleted Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $admission_request);
	}

	public function recover_admission_request()
	{
		$error = false;
		$msg = '';
		$admission_request = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('admission_request_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_deleted", 0);
				$this->db->where("admission_request_id", $data["admission_request_id"]);
				$this->db->update("admission_requests");

				if ($this->db->affected_rows()) {
					$msg = "Recovered Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $admission_request);
	}

	public function delete_board()
	{
		$error = false;
		$msg = '';
		$boards = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('board_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_deleted", 1);
				$this->db->where("board_id", $data["board_id"]);
				$this->db->update("boards");

				if ($this->db->affected_rows()) {
					$msg = "Deleted Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $boards);
	}

	public function recover_board()
	{
		$error = false;
		$msg = '';
		$boards = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('board_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_deleted", 0);
				$this->db->where("board_id", $data["board_id"]);
				$this->db->update("boards");

				if ($this->db->affected_rows()) {
					$msg = "Recover Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $boards);
	}


	public function get_dashboard()
	{
		$error = false;
		$msg = '';
		$dashboard = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('school_id'));
			if ($requiredParams) {
				$this->db->select('user_id');
				$this->db->where('user_type', 1);
				$student = $this->db->count_all_results('users');
				$data = array("school_id" => 0);
				$this->db->reset_query();
				$this->db->select('user_id');
				$this->db->where('user_type', 2);
				$teacher = $this->db->count_all_results('users');
				$this->db->reset_query();
			} else {
				$student = 0;
				$teacher = 0;
			}
			$this->db->select('school_id');
			$this->db->where('is_deleted', 0);
			$schools = $this->db->count_all_results('schools');
			$this->db->reset_query();

			$this->db->select('job_request_id');
			if ($data['school_id'] !== 0) {
				$this->db->where('school_id', $data['school_id']);
			}
			$this->db->where('is_deleted', 0);
			$job_request = $this->db->count_all_results('job_requests');
			$this->db->reset_query();

			$this->db->select('admin_user_id');
			if ($data['school_id'] !== 0) {
				$this->db->where('school_id', $data['school_id']);
			}
			$this->db->where('is_deleted', 0);
			$admin_user = $this->db->count_all_results('admin_users');
			$this->db->reset_query();

			$this->db->select('admission_request_id');
			if ($data['school_id'] !== 0) {
				$this->db->where('school_id', $data['school_id']);
			}
			$this->db->where('is_deleted', 0);
			$admission_request = $this->db->count_all_results('admission_requests');

			array_push($dashboard, json_encode($schools), json_encode($job_request), json_encode($admin_user), json_encode($admission_request), json_encode($student), json_encode($teacher));
			$this->response($error, $msg, $dashboard);
		} catch (\Throwable $th) {
			$this->response(true, 'Exception', array());
		}
	}

	public function delete_job_request()
	{
		$error = false;
		$msg = '';
		$job_request = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('job_request_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_deleted", 1);
				$this->db->where("job_request_id", $data["job_request_id"]);
				$this->db->update("job_requests");

				if ($this->db->affected_rows()) {
					$msg = "Deleted Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $job_request);
	}

	public function recover_job_request()
	{
		$error = false;
		$msg = '';
		$job_request = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('job_request_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_deleted", 0);
				$this->db->where("job_request_id", $data["job_request_id"]);
				$this->db->update("job_requests");

				if ($this->db->affected_rows()) {
					$msg = "Recover Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $job_request);
	}

	public function upload_questions()
	{
		$error = false;
		$msg = '';
		$questions = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('quiz_id', 'question', 'option_a', 'option_b', 'option_c', 'true_answer'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$uploadQuestion = array(
					'quiz_id' => $data['quiz_id'],
					'question' => $data['question'],
				);
				$this->db->insert('questions', $uploadQuestion);
				$questionId = $this->db->insert_id();
				$uploadAnswers = array(
					'question_id' => $questionId,
					'option_a' => $data['option_a'],
					'option_b' => $data['option_b'],
					'option_c' => $data['option_c'],
					'true_answer' => $data['true_answer'],
				);
				$this->db->insert('answers', $uploadAnswers);
				$msg = "question and answer uploaded";
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $questions);
	}

	public function get_questions()
	{
		$error = false;
		$msg = '';
		$questions = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('quiz_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select('q.question,JSON_ARRAY(a.option_a ,a.option_b ,a.option_c) as options,a.true_answer');
				$this->db->from('answers as a');
				$this->db->join('questions as q', 'q.question_id = a.question_id');
				$this->db->where('quiz_id', $data['quiz_id']);
				$query = $this->db->get();
				$questions = $query->result_array();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $questions);
	}

	public function quiz_upload()
	{
		$error = false;
		$msg = '';
		$quiz = new stdClass();
		try {
			$quiz_name = $this->input->post('quiz_name');
			$prize = $this->input->post('prize_name');
			$quiz_date = $this->input->post('quiz_date');
			$winners = $this->input->post('winners');
			$number_of_questions = $this->input->post('number_of_questions');
			$time_per_question = $this->input->post('time_per_questions');
			$theme = $this->input->post('theme');
			$extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
			$prizeimgextension = strtolower(pathinfo($_FILES['prize_image']['name'], PATHINFO_EXTENSION));
			if (($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png' || $extension == 'svg') && ($prizeimgextension == 'jpg' || $prizeimgextension == 'jpeg' || $prizeimgextension == 'png' || $prizeimgextension == 'svg')
			) {
				$target_path = 'quiz_image/' . md5(uniqid($quiz_date, true)) . '.' . $extension;
				$prize_image_path = 'prize_image/' . md5(uniqid($quiz_date, true)) . '.' . $prizeimgextension;
				if (!is_dir('prize_image')) {
					mkdir('prize_image');
				}
				if (!is_dir('quiz_image')) {
					mkdir('quiz_image');
				}
				if ((move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) && (move_uploaded_file($_FILES['prize_image']['tmp_name'], $prize_image_path))) {
					$uploadquiz = array(
						'number_of_winners' => $winners,
						'prize_image' => $prize_image_path,
						'image' => $target_path,
						'quiz_name' => $quiz_name,
						'prize_name' => $prize,
						'quiz_date' => $quiz_date,
						'number_of_questions' => $number_of_questions,
						'time_per_question' => $time_per_question,
						'theme' => $theme,
					);
					$this->db->insert('quiz', $uploadquiz);
					$quizId = $this->db->insert_id();
					$quiz = array('quizId' => $quizId, 'questions' => (int) $number_of_questions);
				} else {
					$msg = "Sorry, file not uploaded, please try again!";
				}
			} else {
				$msg = "File is not image";
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $quiz);
	}

	public function get_quiz_list()
	{
		$error = false;
		$msg = '';
		$quiz = new stdClass();
		try {
			$this->db->select('quiz_id,quiz_name, quiz_date, number_of_questions, time_per_question, theme, is_deleted, image , (quiz_date  < now()+ INTERVAL 330 MINUTE) as "over"');
			$this->db->from('quiz');
			$this->db->where('is_deleted', '0');
			$this->db->order_by('quiz_date', "desc");
			$query = $this->db->get();
			$quiz = $query->result_array();
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $quiz);
	}

	public function get_quiz()
	{
		$error = false;
		$msg = '';
		$quiz = new stdClass();
		try {
			$today = time();
			$this->db->select('quiz_id, quiz_date, theme, image, quiz_name');
			$this->db->from('quiz');
			$this->db->where('is_deleted', '0');
			//19800 means 5 hour and 30 mints time diffrance from server to mysql - 30 sec for  wait time
			$this->db->where('unix_timestamp(quiz_date)-19770 >', $today);
			// $this->db->where('unix_timestamp(quiz_date) >', $today);
			$this->db->order_by('quiz_date');
			$query = $this->db->get();
			$quiz = $query->result_array();
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $quiz);
	}

	public function get_quiz_info()
	{
		$error = false;
		$msg = '';
		$quiz = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('quiz_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$quiz_id = $data['quiz_id'];
				$this->db->select("quiz_id,quiz_name, quiz_date, image, theme,  time_per_question, number_of_questions ,prize_name, prize_image,number_of_winners");
				$query = $this->db->get_where("quiz", array('quiz_id' => $quiz_id));
				$quiz = $query->first_row();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $quiz);
	}

	public function save_leaderboard()
	{
		$error = false;
		$msg = '';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('user_id', 'quiz_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$save_leaderboard = array(
					'user_id' => $data['user_id'],
					'quiz_id' => $data['quiz_id'],
					'score' => $data['score'],
				);
				$this->db->insert('leaderboard', $save_leaderboard);
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, new stdClass());
	}

	public function get_leaderboard()
	{
		$leaderboard = new stdClass();
		$error = false;
		$msg = '';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('user_id', 'quiz_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				// $this->db->select("concat(u.first_name,' ',u.last_name) as name, l.score, DENSE_RANK() OVER( ORDER BY l.score desc) as rank, l.id,mn.mobile_no");
				$this->db->select("concat(u.first_name,' ',u.last_name) as name, l.score, (@row := ifnull(@row, 0) + 1) as rank, l.id,mn.mobile_no");
				// $this->db->select("concat(u.first_name,' ',u.last_name) as name, l.score, DENSE_RANK() OVER(PARTITION BY l.quiz_id  ORDER BY l.score desc) as rank, l.id,mn.mobile_no");
				$this->db->join("users as u", "l.user_id = u.user_id", "left");
				$this->db->join("mobile_nos as mn", "u.mobile_no_id = mn.mobile_no_id", "left");
				$this->db->order_by("l.score", "desc");
				// $this->db->where("l.quiz_id", $data['quiz_id']); comment this lines for this quiz so we get both result, 
				$query = $this->db->get("leaderboard as l");
				$result1 = $query->result_array();
				$this->db->select("concat(u.first_name,' ', u.last_name) as name, l.score, l.id ,mn.mobile_no");
				$this->db->join("users as u", "l.user_id = u.user_id", "left");
				$this->db->join("mobile_nos as mn", "u.mobile_no_id = mn.mobile_no_id", "left");
				$this->db->where("l.quiz_id", $data['quiz_id']);
				$this->db->where("u.user_id", $data['user_id']);
				$query2 = $this->db->get('leaderboard as l');
				$result2 = $query2->first_row();
				$leaderboard = array('list' => $result1, 'myrow' => $result2);
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $leaderboard);
	}

	public function save_winners()
	{
		$error = false;
		$msg = '';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('quizid', 'userid', 'leaderboardid', 'address1', 'address2', 'landmark', 'city', 'state', 'pincode'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$winners = array(
					'user_id' => $data['userid'],
					'leaderboard_id' => $data['leaderboardid'],
					'quiz_id' => $data['quizid'],
					'address1' => $data['address1'],
					'address2' => $data['address2'],
					'landmark' => $data['landmark'],
					'city' => $data['city'],
					'state' => $data['state'],
					'pincode' => $data['pincode'],
				);
				$this->db->insert('winners', $winners);
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, new stdClass());
	}

	public function most_popular()
	{
		$error = false;
		$msg = '';
		$schools = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('state_id'));
			$checkLatLong = checkSetEmpty($data, array('lat', 'long'));
			if (!$requiredParams) {
				$this->school_group($data, false);
				$this->db->where("st.state_id", $data['state_id']);
				// $this->db->where("s.is_prime", 1);
				$this->db->group_by("s.school_id");
				if (!$checkLatLong) {
					$this->db->order_by("distanceOrder");
				}
				$query = $this->db->get();
				$schools = $query->result_array();
			} else {
				$error = true;
				$msg = $requiredParams;
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
			$schools = array();
		}
		$this->response($error, $msg, $schools);
	}

	public function delete_quiz()
	{
		$error = false;
		$msg = '';
		$quiz = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('quiz_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->set("is_deleted", 1);
				$this->db->where("quiz_id", $data["quiz_id"]);
				$this->db->update("quiz");

				if ($this->db->affected_rows()) {
					$msg = "Deleted Successfully";
				} else {
					$msg = "Nothing updated";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $quiz);
	}

	public function update_quiz()
	{
		$error = false;
		$msg = '';
		$quiz = new stdClass();
		try {
			$quizId = $this->input->post('quiz_id');
			$quiz_name = $this->input->post('quiz_name');
			$prize = $this->input->post('prize_name');
			$quiz_date = $this->input->post('quiz_date');
			$winners = $this->input->post('winners');
			$number_of_questions = $this->input->post('number_of_questions');
			$time_per_question = $this->input->post('time_per_questions');
			$theme = $this->input->post('theme');
			$updatequiz = array(
				'number_of_winners' => $winners,
				'quiz_name' => $quiz_name,
				'prize_name' => $prize,
				'quiz_date' => $quiz_date,
				'number_of_questions' => $number_of_questions,
				'time_per_question' => $time_per_question,
				'theme' => $theme,
			);

			if (!empty($_FILES['image'])) {
				$extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
				if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png' || $extension == 'svg') {
					$target_path = 'quiz_image/' . md5(uniqid($quiz_date, true)) . '.' . $extension;
					if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
						$updatequiz['image'] = $target_path;
					} else {
						$error = true;
						$msg = "Sorry, file not uploaded, please try again!";
					}
				} else {
					$error = true;
					$msg = "file is not image";
				}
			}
			if (!empty($_FILES['prize_image'])) {
				$prizeimgextension = strtolower(pathinfo($_FILES['prize_image']['name'], PATHINFO_EXTENSION));
				if ($prizeimgextension == 'jpg' || $prizeimgextension == 'jpeg' || $prizeimgextension == 'png' || $prizeimgextension == 'svg') {
					$prize_image_path = 'prize_image/' . md5(uniqid($quiz_date, true)) . '.' . $prizeimgextension;
					if (move_uploaded_file($_FILES['prize_image']['tmp_name'], $prize_image_path)) {
						$updatequiz['prize_image'] = $prize_image_path;
					} else {
						$error = true;
						$msg = "Sorry, file not uploaded, please try again!";
					}
				} else {
					$error = true;
					$msg = "file is not image";
				}
			}

			$this->db->set($updatequiz);
			$this->db->where('quiz_id', $quizId);
			$this->db->update('quiz');
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $quiz);
	}

	public function get_questions_for_update()
	{
		$error = false;
		$msg = '';
		$questions = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('quiz_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select('q.question,a.option_a ,a.option_b ,a.option_c,a.true_answer,q.question_id');
				$this->db->from('answers as a');
				$this->db->join('questions as q', 'q.question_id = a.question_id');
				$this->db->where('quiz_id', $data['quiz_id']);
				$query = $this->db->get();
				$questions = $query->result_array();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $questions);
	}

	public function update_question()
	{
		$error = false;
		$msg = '';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('question_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$updatequestions = array(
					'question' => $data['question'],
				);
				$this->db->set($updatequestions);
				$this->db->where('question_id', $data['question_id']);
				$this->db->update('questions');
				$updateanswer  = array(
					'true_answer' => $data['true_answer'],
					'option_a' => $data['option_a'],
					'option_b' => $data['option_b'],
					'option_c' => $data['option_c'],
				);
				$this->db->set($updateanswer);
				$this->db->where('question_id', $data['question_id']);
				$this->db->update('answers');
				$msg = 'Question Updated';
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, new stdClass());
	}

	public function get_server_time()
	{
		$error = false;
		$msg = '';
		$timeDiffrence = 19800;
		$time = time();
		$this->response($error, $msg, $time);
	}

	public function view_job_request()
	{
		$error = false;
		$msg = 'Success';
		$job_requests = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('job_request_id'));

			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select("a.first_name, a.last_name, a.experience_years, a.experience_months, a.user_id, a.school_id, IFNULL(a.photo, '') as banner, a.gender, a.birth_date, a.email_id, m.mobile_no as mobile_no_id, a.city_id, c.city_name, a.description");
				$this->db->join("cities as c", "c.city_id = a.city_id");
				$this->db->join("mobile_nos as m", "m.mobile_no_id = a.mobile_no_id");
				$this->db->where("job_request_id", $data["job_request_id"]);
				$query = $this->db->get("job_requests as a");
				$job_requests = $query->first_row();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $job_requests);
	}

	public function get_admin_email()
	{
		$error = false;
		$msg = '';
		$email = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('admin_user_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select("email_id");
				$this->db->where("admin_user_id", $data["admin_user_id"]);
				$query = $this->db->get('admin_users');
				$email = $query->first_row();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $email);
	}

	public function get_leaderboard_only()
	{
		$leaderboard = new stdClass();
		$error = false;
		$msg = '';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('quiz_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				// $this->db->select("concat(u.first_name,' ',u.last_name) as name, l.score, DENSE_RANK() OVER(PARTITION BY l.quiz_id  ORDER BY l.score desc) as rank, l.id,mn.mobile_no");
				$this->db->select("concat(u.first_name,' ',u.last_name) as name, l.score, (@row := ifnull(@row, 0) + 1) as rank, l.id,mn.mobile_no");
				$this->db->join("users as u", "l.user_id = u.user_id", "left");
				$this->db->join("mobile_nos as mn", "u.mobile_no_id = mn.mobile_no_id", "left");
				$this->db->where("l.quiz_id", $data['quiz_id']);
				$this->db->order_by("l.score", "desc");
				$query = $this->db->get("leaderboard as l");
				$leaderboard = $query->result_array();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $leaderboard);
	}

	public function get_winners()
	{
		$winners = new stdClass();
		$error = false;
		$msg = '';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$this->db->select("concat(u.first_name,' ',u.last_name) as name, w.address1, w.address2, w.landmark, w.city, w.state, w.pincode,mn.mobile_no,q.quiz_name,l.score, q.quiz_date");
			$this->db->join("users as u", "w.user_id = u.user_id", "left");
			$this->db->join("mobile_nos as mn", "u.mobile_no_id = mn.mobile_no_id", "left");
			$this->db->join("quiz as q", "q.quiz_id = w.quiz_id", "left");
			$this->db->join("leaderboard as l", "l.id = w.leaderboard_id", "left");
			$query = $this->db->get("winners as w");
			$winners = $query->result_array();
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $winners);
	}

	public function get_exam_list()
	{

		$exam = new stdClass();
		$error = false;
		$msg = '';
		try {
			$this->db->select("e.exam_id, e.exam_name,e.exam_start_date,e.exam_end_date,e.theme , count(s.subject_id)as total_subject,e.exam_fees,e.scholarship");
			$this->db->join('subject as s', 's.exam_id = e.exam_id', 'left');
			$this->db->where('e.is_deleted', 0);
			$this->db->group_by('e.exam_id');
			$this->db->order_by('e.exam_start_date');
			$query = $this->db->get('exam as e');
			$exam = $query->result_array();
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $exam);
	}

	public function create_exam()
	{
		$error = false;
		$msg = '';
		$quiz = new stdClass();
		try {
			$exam_name = $this->input->post('exam_name');
			$startdate = $this->input->post('start_date');
			$enddate = $this->input->post('end_date');
			$theme = $this->input->post('theme');
			$examfee = $this->input->post('fees');
			$scholarship = $this->input->post('scholarship');
			$extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
			if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png' || $extension == 'svg') {
				$target_path = 'exam_image/' . md5(uniqid($startdate, true)) . '.' . $extension;
				if (!is_dir('exam_image')) {
					mkdir('exam_image');
				}
				if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
					$upload = array(
						'exam_name' => $exam_name,
						'exam_start_date' => $startdate,
						'exam_end_date' => $enddate,
						'theme' => $theme,
						'image' => $target_path,
						'exam_fees' => $examfee,
						'scholarship'=>$scholarship,
					);
					$this->db->insert('exam', $upload);
					$examId = $this->db->insert_id();
					$exam = array('examId' => $examId);
					$msg = "success";
				} else {
					$msg = "Sorry, file not uploaded, please try again!";
				}
			} else {
				$msg = "File is not image";
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, new stdClass());
	}

	public function get_language_list()
	{
		$error = false;
		$msg = '';

		$this->db->select('language_name as language, language_id');
		$query = $this->db->get('languages');
		$language = $query->result_array();
		$this->response($error, $msg, $language);
	}

	public function get_exam_info()
	{
		$error = false;
		$msg = '';
		$info = array();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('exam_id'));

			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select('e.scholarship,e.exam_id,e.exam_name,e.exam_start_date,e.exam_end_date,e.theme,e.image,count(DISTINCT s.subject_id) as subjects,e.exam_fees');
				$this->db->join('subject as s', 's.exam_id = e.exam_id', 'left');
				$query = $this->db->get_where("exam as e", array('e.exam_id' => $data['exam_id']));
				$info = $query->first_row();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $info);
	}

	public function add_new_subject()
	{
		$error = false;
		$msg = 'Success';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array(
				'examId',
				'name',
				'start_date',
				'end_date',
				'theme',
				'time_per_question',
				'positive_marks',
				'language',
				'image'
			));

			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {

				if (isset($data['image']) && $data['image']) {
					$photo = $data['image'];
					if (preg_match('/^data:image\/(\w+);base64,/', $photo, $type)) {
						$photo = substr($photo, strpos($photo, ',') + 1);
						$type = strtolower($type[1]); // jpg, png, gif
						if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
							$error = true;
							$msg = 'invalid image type';
						} else {
							$photo = base64_decode($photo);
							if ($photo === false) {
								$msg = 'base64_decode failed';
							} else {
								$target_path = 'exam_image/' . md5(uniqid($data['start_date'], true)) . '.' . $type;
								file_put_contents($target_path, $photo);
								$data['image'] = $target_path;
							}
						}
					} else {
						$error = true;
						$msg = 'Did not match data URI with image data';
					}
				}

				if (!$error) {
					$subject = array(
						'exam_id' => $data['examId'],
						'subject_name' => $data['name'],
						'subject_start_date' => $data['start_date'],
						'subject_end_date' => $data['end_date'],
						'time_per_question' => $data['time_per_question'],
						'image' => $data['image'],
						'theme' => $data['theme'],
						'correct_answer_marks' => $data['positive_marks'],
						'incorrect_answer_marks' => 0,
					);
					if (isset($data['nagetive_marks']) && $data['nagetive_marks']) {
						$subject['incorrect_answer_marks'] = $data['nagetive_marks'];
					}
					$this->db->insert('subject', $subject);
					$subject_id = $this->db->insert_id();
					if ($subject_id) {
						$languages = array();
						foreach ($data['language'] as $language) {
							if (isset($language['selected']) && $language['selected']) {
								array_push($languages, array('language_id' => $language['language_id'], 'subject_id' => $subject_id));
							}
						}
						if (count($languages)) {
							$this->db->insert_batch("language_link", $languages);
						}
					}
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, new stdClass());
	}

	public function get_subject_list()
	{
		$error = false;
		$msg = '';
		$exam = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('exam_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select(" s.subject_id,s.subject_name , s.subject_start_date,s.subject_end_date, s.correct_answer_marks, count(DISTINCT q.question_id) as question,
				s.incorrect_answer_marks,time_per_question,theme,IFNULL(GROUP_CONCAT(DISTINCT CONCAT(l.language_name) separator ', '),' ') as language");
				$this->db->join('language_link as ll', 's.subject_id = ll.subject_id', 'left');
				$this->db->join('languages as l', 'll.language_id = l.language_id', 'left');
				$this->db->join('exam_questions as q', 'q.subject_id = s.subject_id ', 'left');
				$this->db->where('s.is_deleted', '0');
				$this->db->where('s.exam_id', $data['exam_id']);
				$this->db->group_by('s.subject_id', 'q.subject_id');
				$query = $this->db->get('subject as s');
				$exam = $query->result_array();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $exam);
	}

	public function get_subject_info()
	{
		$error = false;
		$msg = '';
		$exam = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('subject_id', 'language_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select(" s.subject_id,s.subject_name , s.subject_start_date,s.subject_end_date,l.language_name,l.language_id,s.image,
				s.correct_answer_marks,s.incorrect_answer_marks,time_per_question,theme, IFNULL(count(DISTINCT q.question_id),'') as questions");
				$this->db->join('language_link as ll', 's.subject_id = ll.subject_id', 'left');
				$this->db->join('languages as l', 'll.language_id = l.language_id', 'left');
				$this->db->join('exam_questions as q', 'q.subject_id = s.subject_id AND q.language_id = l.language_id', 'left');
				$this->db->where('s.is_deleted', '0');
				$this->db->where('s.subject_id', $data['subject_id']);
				$this->db->where('l.language_id', $data['language_id']);
				$this->db->group_by('s.subject_id , q.subject_id');
				$query = $this->db->get('subject as s');
				$exam = $query->first_row();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $exam);
	}

	public function get_subject_language()
	{
		$error = false;
		$msg = '';
		$exam = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('subject_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select('l.language_name,l.language_id');
				$this->db->join('language_link as ll', 'll.subject_id = s.subject_id', 'left');
				$this->db->join('languages as l', 'l.language_id = ll.language_id', 'left');
				$this->db->where('s.subject_id', $data['subject_id']);
				$query = $this->db->get('subject as s');
				$exam = $query->result_array();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $exam);
	}

	public function submit_exam_question()
	{
		$error = false;
		$msg = '';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('subjectId', 'languageId', 'question', 'answers', 'correctAnswer'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$que = array(
					'subject_id' => $data['subjectId'],
					'language_id' => $data['languageId'],
					'question' => $data['question']
				);
				$this->db->insert('exam_questions', $que);
				$questionId = $this->db->insert_id();
				if ($questionId) {
					$ans  = array();
					foreach ($data['answers'] as $answer) {
						array_push($ans, array('question_id' => $questionId, 'answer' => $answer));
					}
					$this->db->insert_batch('exam_answers', $ans);
					if ($this->db->affected_rows()) {
						$this->db->select('answer_id');
						$this->db->where('question_id', $questionId);
						$this->db->where('answer', $data['correctAnswer']);
						$query = $this->db->get('exam_answers');
						$correctAnswerId = $query->first_row();
						if ($correctAnswerId) {
							$corans = array('question_id' => $questionId, 'answer_id' => $correctAnswerId->answer_id);
							$this->db->insert('exam_correct_answer', $corans);
							if ($this->db->affected_rows()) {
								$msg = 'Question Answer Uploaded Successfully';
							} else {
								$error = true;
								$msg = 'Correct Answer Not Uploaded';
							}
						} else {
							$error = true;
							$msg = "Correct Answer Not Found";
						}
					} else {
						$error = true;
						$msg = "Answers Not Uploaded";
					}
				} else {
					$error = true;
					$msg = "Question Not Uploaded";
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, new stdClass());
	}

	public function get_question_list()
	{
		$error = false;
		$msg = '';
		$exam = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('subject_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select("eq.question,IFNULL(GROUP_CONCAT(DISTINCT CONCAT(ea.answer) separator ', '), '') as answers,answer, l.language_name");
				$this->db->join('exam_answers as ea', 'ea.question_id = eq.question_id', 'left');
				$this->db->join('languages as l', 'l.language_id = eq.language_id');
				$this->db->join('exam_correct_answer as ca', 'ca.answer_id = ea.answer_id AND ca.question_id = eq.question_id', 'left');
				$this->db->where('eq.subject_id', $data['subject_id']);
				$this->db->group_by('eq.question_id');
				$query = $this->db->get('exam_questions as eq');
				$exam = $query->result_array();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $exam);
	}

	public function get_Exams()
	{
		$error = false;
		$msg = '';
		$exam = new stdClass();
		try {
			$this->db->select('exam_id, exam_name, theme, image, exam_start_date, exam_end_date, exam_fees');
			$this->db->from('exam');
			$this->db->where('is_deleted', '0');
			//19800 means 5 hour and 30 mints time diffrance from server to mysql - 30 sec for  wait time
			// $this->db->where('exam_end_date > now()+INTERVAL 330 MINUTE');
			$this->db->where('exam_end_date > now()');
			$this->db->order_by('exam_start_date');
			$query = $this->db->get();
			$exam = $query->result_array();
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $exam);
	}

	public function get_exam_subjects()
	{
		$error = false;
		$msg = '';
		$exam = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('exam_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->from('subject');
				$this->db->where('is_deleted', '0');
				$this->db->where('exam_id', $data['exam_id']);
				$this->db->order_by('subject_start_date');
				$query = $this->db->get();
				$exam = $query->result_array();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $exam);
	}

	public function get_exam_questions()
	{
		$error = false;
		$msg = '';
		$exam = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('subject_id', 'language_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select("eq.question,IFNULL(GROUP_CONCAT(DISTINCT CONCAT(ea.answer) separator 'aseparator '), '') as options,answer, l.language_name");
				$this->db->join('exam_answers as ea', 'ea.question_id = eq.question_id', 'left');
				$this->db->join('languages as l', 'l.language_id = eq.language_id');
				$this->db->join('exam_correct_answer as ca', 'ca.answer_id = ea.answer_id AND ca.question_id = eq.question_id', 'left');
				$this->db->where('eq.subject_id', $data['subject_id']);
				$this->db->where('eq.language_id', $data['language_id']);
				$this->db->group_by('eq.question_id');
				$query = $this->db->get('exam_questions as eq');
				$exam = $query->result_array();
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $exam);
	}

	public function check_app_version()
	{
		$response = new stdClass();
		$response->update = false;
		$response->force = false;
		$error = false;
		$msg = '';
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('app_version'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				// compare with current app version
				if ($data["app_version"] < 1.6) {
					$response->update = true;
				}
				// compare for force update
				if ($data["app_version"] < 1.5) {
					$response->force = true;
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $response);
	}

	public function save_exam_result()
	{
		$error = false;
		$msg = '';
		$exam = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('subject_id', 'user_id','total_marks'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$result = array(
					'user_id' => $data['user_id'],
					'subject_id' => $data['subject_id'],
					'total_marks' =>$data['total_marks'],
					'obtain_marks' =>$data['obtain_marks'],
					'language_id' =>$data['language_id']
				);
				$this->db->insert('exam_results', $result);
				if ($this->db->affected_rows()) {
					$msg = 'Result Save Successfully';
				} else {
					$error = true;
					$msg = 'Error While Saving the Result';
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $exam);
	}

	public function get_exam_result()
	{
		$error = false;
		$msg = '';
		$exam = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('user_id', 'exam_id','language_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select("concat(u.first_name,' ',u.last_name) as name, u.birth_date,e.exam_name");
				$this->db->join('paid_exam as pe', 'pe.user_id = u.user_id', 'left');
				$this->db->join('exam as e', 'e.exam_id = pe.exam_id', 'left');
				$this->db->where('u.user_id', $data['user_id']);
				$query = $this->db->get('users as u');
				$user = $query->first_row();
				$this->db->select('s.subject_name,r.total_marks,r.obtain_marks,r.created_date as date');
				$this->db->join('exam as e', 'e.exam_id = s.exam_id', 'left');
				$this->db->join('exam_results as r', 'r.subject_id = s.subject_id', 'left');
				$this->db->where('e.exam_id', $data['exam_id']);
				$this->db->where('r.user_id', $data['user_id']);
				$this->db->where('r.language_id', $data['language_id']);
				$query2 = $this->db->get('subject as s');
				$result = $query2->result_array();
				$exam = array('user' => $user, 'result' => $result);
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $exam);
	}

	public function access_to_exam()
	{
		$error = false;
		$msg = '';
		$exam = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('user_id', 'exam_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select('user_id,exam_id,language_id');
				$this->db->where('exam_id',$data['exam_id']);
				$this->db->where('user_id',$data['user_id']);
				$this->db->where('is_paid',1);
				$query = $this->db->get('paid_exam');
				$exam = $query->first_row();
				if (!$exam) {
					$error = true;
					$msg = 'notallowed';
				} else {
					$msg = 'allowed';
				}
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $exam);
	}

	public function check_attempt()
	{
		$error = false;
		$msg = '';
		$exam = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('user_id','subject_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select('result_id');
				$this->db->where('subject_id',$data['subject_id']);
				$this->db->where('user_id',$data['user_id']);
				$exam = $this->db->count_all_results('exam_results');
				if ($exam) {
					$error = true;
					$msg = 'notallowed';
				}else {
					$msg = 'allowed';
				}
				
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $exam);
	}

	public function get_subscribed_exam()
	{
		$error = false;
		$msg = '';
		$exam = new stdClass();
		try {
			$data = json_decode(file_get_contents('php://input'), true);
			$requiredParams = checkSetEmpty($data, array('user_id'));
			if ($requiredParams) {
				$error = true;
				$msg = $requiredParams;
			} else {
				$this->db->select('result_id');
				$this->db->where('subject_id',$data['subject_id']);
				$this->db->where('user_id',$data['user_id']);
				$exam = $this->db->count_all_results('exam_results');
				if ($exam) {
					$error = true;
					$msg = 'notallowed';
				}else {
					$msg = 'allowed';
				}
				
			}
		} catch (\Throwable $th) {
			$error = true;
			$msg = 'Exception';
		}
		$this->response($error, $msg, $exam);
	}
}
