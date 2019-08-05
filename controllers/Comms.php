<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Comms extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->model("app_model");
        $this->load->helper('url');
        $this->load->helper('date_helper');
        $this->load->library('grocery_CRUD');
        $this->load->library('comms_library');

     $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Management')){
            $this->event_model->track('error','permissions', 'Comms');
            redirect('/admin/permissions');
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

    function queue_comm($user_id=2848, $comm_id=20, $data=''){
        $data = array('order_id'=>811,'distributor_order_id'=>756,'distributor_id'=>3);

        $this->comms_library->queue_comm($user_id, $comm_id, $data);
    } 

    function queue_comm_group($user_id=2848, $group='add_to_customer_account', $data=''){
         $data = array('amount' => 100, 'balance'=>3000, 'reference'=>'SpazaTestRef', 'createdate'=>date('Y-m-d H:i:s'));
        $this->comms_library->queue_comm_group($user_id, $group, $data);
    }

    function send_queued_comms($user_id='', $comm_id='', $force_send=false){
        $this->comms_library->send_queued_comms($user_id, $comm_id, $force_send);
    }

    function send_order_comms(){
        $this->comms_library->send_order_comms();
    }

	

    
}