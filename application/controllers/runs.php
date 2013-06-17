<?php

class Runs extends CI_Controller {
	
	public function __construct()
	{
		parent::__construct();
		$this->load->model('runs_model');
                $this->load->library('session');
	}

	public function all()
	{
		$data['runs'] = $this->runs_model->get_all();
		$data['page_title'] = 'All Runs';
                $data['userdata'] = $this->session->all_userdata();
                
		$this->load->view('templates/htmlhead', $data);
		$this->load->view('templates/runs', $data);
		$this->load->view('templates/htmlfoot');
	}
}

?>
