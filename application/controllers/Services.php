<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Services extends CI_Controller {

    public function __construct() {
		parent::__construct();
		$this->load->helper('url'); 
    }

    public function login() {
        $this->load->model('User_model', 'user');
        $this->user->login();
    }
	
	public function school_login() {
        $this->load->model('School_model', 'school');
        $this->school->school_login();
    }

	public function admin_users_list() {
        $this->load->model('School_model', 'school');
        $this->school->admin_users_list();
	}
	
	public function delete_admin_user() {
        $this->load->model('School_model', 'school');
        $this->school->delete_admin_user();
	}
	
	public function recover_admin_user() {
        $this->load->model('School_model', 'school');
        $this->school->recover_admin_user();
    }

    public function signup() {
        $this->load->model('User_model', 'user');
        $this->user->signup();
    }

    public function send_otp() {
        $this->load->model('User_model', 'user');
        $this->user->send_otp();
    }

    public function verify_otp() {
        $this->load->model('User_model', 'user');
        $this->user->verify_otp();
    }

    public function states() {
        $this->load->model('School_model', 'school');
        $this->school->available_states();
    }

    public function cities() {
        $this->load->model('School_model', 'school');
        $this->school->available_cities();
    }

    public function boards() {
        $this->load->model('School_model', 'school');
        $this->school->boards();
    }

    public function school_types() {
        $this->load->model('School_model', 'school');
        $this->school->school_types();
    }

    public function mediums() {
        $this->load->model('School_model', 'school');
        $this->school->mediums();
    }

    public function schools() {
        $this->load->model('School_model', 'school');
        $this->school->schools_list();
    }

    public function schools_search() {
        $this->load->model('School_model', 'school');
        $this->school->schools_search();
    }

	public function relation_with_school() {
        $this->load->model('School_model', 'school');
        $this->school->relation_with_school();
    }

	public function schools_filter() {
        $this->load->model('School_model', 'school');
        $this->school->schools_filter();
	}

	public function school_profile() {
        $this->load->model('School_model', 'school');
        $this->school->school_profile();
    }

	public function update_profile() {
        $this->load->model('User_model', 'user');
        $this->user->update_profile();
    }
	
	public function get_profile() {
        $this->load->model('User_model', 'user');
        $this->user->get_profile();
    }
	
	public function add_new_school() {
        $this->load->model('School_model', 'school');
        $this->school->add_new_school();
	}
	
	public function delete_school() {
        $this->load->model('School_model', 'school');
        $this->school->delete_school();
    }
	
	public function recover_school() {
        $this->load->model('School_model', 'school');
        $this->school->recover_school();
    }

	public function approve_school() {
        $this->load->model('School_model', 'school');
        $this->school->approve_school();
	}

	public function reject_school() {
        $this->load->model('School_model', 'school');
        $this->school->reject_school();
    }
	
	public function banners() {
        $this->load->model('School_model', 'school');
        $this->school->banners();
	}
	
	public function get_banners() {
        $this->load->model('School_model', 'school');
        $this->school->get_banners();
	}

	public function setUnsetBookmark() {
        $this->load->model('User_model', 'user');
        $this->user->setUnsetBookmark();
    }
	
	public function bookmarksList() {
        $this->load->model('User_model', 'user');
        $this->user->bookmarksList();
    }
	
	public function banner_upload() {
        $this->load->model('School_model', 'school');
        $this->school->banner_upload();
    }
	
	public function school_info($schoolId) {
        $this->load->model('School_model', 'school');
        $this->school->school_info($schoolId);
    }
	
	public function admission_request() {
        $this->load->model('School_model', 'school');
        $this->school->admission_request();
	}
	
	public function job_request() {
        $this->load->model('School_model', 'school');
        $this->school->job_request();
	}
	
	public function job_requests_list() {
        $this->load->model('School_model', 'school');
        $this->school->job_requests_list();
	}

	public function admission_requests_list() {
        $this->load->model('School_model', 'school');
        $this->school->admission_requests_list();
    }
	
	public function admission_requests_list_by_school() {
        $this->load->model('School_model', 'school');
        $this->school->admission_requests_list_by_school();
    }
	
	public function school_gallery() {
        $this->load->model('School_model', 'school');
        $this->school->school_gallery();
    }
	
	public function school_gallery_upload() {
        $this->load->model('School_model', 'school');
        $this->school->school_gallery_upload();
    }
	
	public function school_gallery_delete_image() {
        $this->load->model('School_model', 'school');
        $this->school->school_gallery_delete_image();
	}

	public function job_requests_list_by_school() {
        $this->load->model('School_model', 'school');
        $this->school->job_requests_list_by_school();
    }
	
	public function add_new_user() {
        $this->load->model('School_model', 'school');
        $this->school->add_new_user();
    }
	
	public function get_schools() {
        $this->load->model('School_model', 'school');
        $this->school->get_schools();
	}
	
	public function delete_banner() {
        $this->load->model('School_model', 'school');
        $this->school->delete_banner();
	}

	public function recover_banner() {
        $this->load->model('School_model', 'school');
        $this->school->recover_banner();
    }
	
	public function recent_search() {
        $this->load->model('School_model', 'school');
        $this->school->recent_search();
	}
	
	public function approve_banner() {
        $this->load->model('School_model', 'school');
        $this->school->approve_banner();
	}

	public function reject_banner() {
        $this->load->model('School_model', 'school');
        $this->school->reject_banner();
	}
	
	public function delete_admission_request() {
        $this->load->model('School_model', 'school');
        $this->school->delete_admission_request();
	}

	public function recover_admission_request() {
        $this->load->model('School_model', 'school');
        $this->school->recover_admission_request();
    }

    public function createChecksum() {
        $this->load->model('Paytm_model');
        $this->Paytm_model->createChecksum();
    }

    public function verifychecksum() {
        $this->load->model('Paytm_model');
        $this->Paytm_model->verifychecksum();
    }

    public function generateChecksum() {
        $this->load->model('Paytm_model');
        $this->Paytm_model->generateChecksum();
    }

    public function teacher_pricing() {
        $this->load->model('Teacher_model');
        $this->Teacher_model->teacher_pricing();
    }
     public function delete_board() {
        $this->load->model('School_model','school');
        $this->school->delete_board();
    }
    public function recover_board() {
        $this->load->model('School_model','school');
        $this->school->recover_board();
    }

    public function get_dashboard() {
        $this->load->model('School_model','school');
        $this->school->get_dashboard();
    }

    public function delete_job_request() {
        $this->load->model('School_model','school');
        $this->school->delete_job_request();
    }

    public function recover_job_request() {
        $this->load->model('School_model','school');
        $this->school->recover_job_request();
    }

    public function upload_questions() {
        $this->load->model('School_model','school');
        $this->school->upload_questions();
    }

    public function get_questions() {
        $this->load->model('School_model','school');
        $this->school->get_questions();
    }

    public function quiz_upload() {
        $this->load->model('School_model','school');
        $this->school->quiz_upload();
    }

    public function get_quiz_list() {
        $this->load->model('School_model','school');
		$this->school->get_quiz_list();
		
	}	
    public function pgRedirect() {
		$this->load->model('Paytm_model');
		$this->Paytm_model->school_paytm_save();
        $this->load->view("pgredirect");
    }
    
    public function pgResponse() {
        $this->load->model('Paytm_model');
		$status = $this->Paytm_model->callback();
		$url = str_replace('admin/', 'admino/dist/', base_url());
		if ($status) {
			$url .= 'register/success';
			redirect($url);
		} else {
			$url .= 'register/failed';
			redirect($url);
		}
    }

	public function bnResponse() {
        $this->load->model('Paytm_model');
		$status = $this->Paytm_model->bnResponse();
		$url = str_replace('admin/', 'admino/dist/', base_url());
		if ($status) {
			$url .= 'register/success';
			redirect($url);
		} else {
			$url .= 'register/failed';
			redirect($url);
		}
    }

    public function get_quiz() {
        $this->load->model('School_model','school');
        $this->school->get_quiz();
    }

    public function get_quiz_info() {
        $this->load->model('School_model','school');
        $this->school->get_quiz_info();
    }

	public function save_leaderboard() {
		$this->load->model('School_model','school');
		$this->school->save_leaderboard();
	}

	public function get_leaderboard() {
		$this->load->model('School_model','school');
		$this->school->get_leaderboard();
	}

	public function save_winners() {
		$this->load->model('School_model','school');
		$this->school->save_winners();
	}
	
	public function most_popular() {
		$this->load->model('School_model','school');
		$this->school->most_popular();
	}

	public function delete_quiz() {
		$this->load->model('School_model','school');
		$this->school->delete_quiz();
	}

	public function update_quiz() {
		$this->load->model('School_model','school');
		$this->school->update_quiz();
	}

	public function get_questions_for_update() {
		$this->load->model('School_model','school');
		$this->school->get_questions_for_update();
	}

	public function update_question() {
		$this->load->model('School_model','school');
		$this->school->update_question();
	}

	public function get_server_time() {
		$this->load->model('School_model','school');
		$this->school->get_server_time();
	}

	public function view_job_request() {
		$this->load->model('School_model','school');
		$this->school->view_job_request();
	}

	public function get_admin_email() {
		$this->load->model('School_model','school');
		$this->school->get_admin_email();
	}

	public function reset_password() {
		$this->load->model('User_model','user');
		$this->user->reset_password();
	}

	public function find_user() {
		$this->load->model('User_model','user');
		$this->user->send_link();
	}

	public function verify_key() {
		$this->load->model('User_model','user');
		$this->user->verify_key();
	}
	
	public function update_password() {
		$this->load->model('User_model','user');
		$this->user->update_password();
	}

	public function get_completed_quiz() {
		$this->load->model('School_model','school');
		$this->school->get_completed_quiz();
	}

	public function get_leaderboard_only() {
		$this->load->model('School_model','school');
		$this->school->get_leaderboard_only();
	}

	public function get_winners() {
		$this->load->model('School_model','school');
		$this->school->get_winners();
	}

	public function get_exam_list() {
		$this->load->model('School_model','school');
		$this->school->get_exam_list();
	}

	public function create_exam() {
		$this->load->model('School_model','school');
		$this->school->create_exam();
	}

	public function get_language_list() {
		$this->load->model('School_model','school');
		$this->school->get_language_list();
	}

	public function get_exam_info() {
		$this->load->model('School_model','school');
		$this->school->get_exam_info();
	}

	public function add_new_subject() {
		$this->load->model('School_model','school');
		$this->school->add_new_subject();
	}

	public function get_subject_list() {
		$this->load->model('School_model','school');
		$this->school->get_subject_list();
	}

	public function get_subject_info() {
		$this->load->model('School_model','school');
		$this->school->get_subject_info();
	}

	public function get_subject_language() {
		$this->load->model('School_model','school');
		$this->school->get_subject_language();
	}

	public function submit_exam_question() {
		$this->load->model('School_model','school');
		$this->school->submit_exam_question();
	}

	public function get_question_list() {
		$this->load->model('School_model','school');
		$this->school->get_question_list();
	}

	public function get_exams() {
		$this->load->model('School_model','school');
		$this->school->get_exams();
	}

	public function get_exam_subjects() {
		$this->load->model('School_model','school');
		$this->school->get_exam_subjects();
	}

	public function get_exam_questions() {
		$this->load->model('School_model','school');
		$this->school->get_exam_questions();
	}

	public function exResponse() {
        $this->load->model('Paytm_model');
		$status = $this->Paytm_model->exResponse();
		$url = str_replace('admin/', 'admino/dist/', base_url());
		if ($status) {
			$url .= 'register/success/exam';
			redirect($url);
		} else {
			$url .= 'register/failed/exam';
			redirect($url);
		}
    }
	
	public function check_app_version() {
		$this->load->model('School_model','school');
		$this->school->check_app_version();
	}

	public function save_exam_result(){
		$this->load->model('School_model','school');
		$this->school->save_exam_result();
	}

	public function get_exam_result(){
		$this->load->model('School_model','school');
		$this->school->get_exam_result();
	}

	public function access_to_exam(){
		$this->load->model('School_model','school');
		$this->school->access_to_exam();
	}

	public function bnRedirect() {
		$this->load->model('Paytm_model');
		$this->Paytm_model->banner_paytm_save();
        $this->load->view("pgredirect");
	}
	
	public function exRedirect() {
		$this->load->model('Paytm_model');
		$this->Paytm_model->exam_paytm_save();
        $this->load->view("pgredirect");
	}
	

	public function check_attempt(){
		$this->load->model('School_model','school');
		$this->school->check_attempt();
	}

	public function get_subscribed_exam(){
		$this->load->model('School_model','school');
		$this->school->get_subscribed_exam();
	}
}
