<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Code written by TangleSkills

class Qrimages extends CI_Controller {

	public function __construct()
    {
        parent::__construct();
	  	$this->load->helper('url');
	  	$this->load->model('qrcode_model');
	   	$this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('financial_model');
        $this->load->model('customer_model');
        $this->load->model('user_model');
        $this->load->model('insurance_model');

        $this->user = $this->aauth->get_user();
        $this->app_settings = get_app_settings(base_url());

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Finanancial')){
            $this->event_model->track('error','permissions', 'Finanancial');
            redirect('/'.$this->app_settings.'admin/permissions');
        }

        if(isset($_POST['date_from']) || isset($_POST['date_to'])){

            $date_from = $_POST['date_from'];
            $date_to = $_POST['date_to'];

        }elseif($this->session->userdata('dashboard_date_from') && $this->session->userdata('dashboard_date_from') != ''){            

            $date_from = $this->session->userdata('dashboard_date_from');
            $date_to = $this->session->userdata('dashboard_date_to');

        }else{

            $date_minus1week = date("Y-m-d H:m", strtotime('-1 week', time()));
            $date_from = $date_minus1week;
            $date_to = date("Y-m-d H:i");
        }

        $this->session->set_userdata('dashboard_date_from', $date_from);
        $this->session->set_userdata('dashboard_date_to', $date_to);
    }

function show_view($view, $data=''){
      $data['user_info'] = $this->user;
      $data['app_settings'] = $this->app_settings;
      $this->load->view($this->app_settings['app_folder'].'include/header', $data);
      $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), $data);
      $this->load->view($this->app_settings['app_folder'].$view, $data);
      $this->load->view($this->app_settings['app_folder'].'include/footer', $data);
    }

    function _example_output($output = null)
    {

        $this->load->model('checklist_model');
        $output->checklist_nav = $this->checklist_model->get_navigation();
        $this->load->view('include/header', $output);
        $this->load->view('include/nav/'. get_defult_page($this->user));
        $this->load->view('report_table',$output);
        $this->load->view('include/footer', $output);
    }

	
	public function index()
	{
		$data['img_url']=$this->qrcode_model->generate_qrcode($this->input->post());
		$data['page_title']="Generate QRcode";
		$this->show_view('qrcode',$data);
	}
}
