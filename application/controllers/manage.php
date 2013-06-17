<?php

class Manage extends CI_Controller {
	
	public function __construct()
	{
		parent::__construct();
                $this->load->model('user_model');
                $this->load->library('session');
	}
        
        public function check()
	{
            $userEmail = $_POST["uEmail"];
            $uPassword = $_POST["uPass"];
            
            if ($this->user_model->login_user($userEmail, $uPassword)) {
                Header("Location: " . RELATIVEPATH);
            } else {
                //print_r($this->user_model->login_user($userEmail, $uPassword));
                //exit;
                Header("Location: " . RELATIVEPATH . "manage/login?status=0");
            }
	
	}
        
	public function login()
	{
            $data['page_title'] = 'Login';
            $data['userdata'] = $this->session->all_userdata();
            
            if (isset($_GET["status"])) {
                $data['login_status'] = $_GET["status"];
            } else {
                $data['login_status'] = "--";
            }

            $this->load->view('templates/htmlhead', $data);
            $this->load->view('templates/login', $data);
            $this->load->view('templates/htmlfoot');
	}
        
	public function logout()
	{
            $this->user_model->logout_user();
            Header("Location: " . RELATIVEPATH . "manage/login?status=1");
	}
}

?>
