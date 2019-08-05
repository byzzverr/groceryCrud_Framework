<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Rep extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('spazapp_model');

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Rep')){
            $this->event_model->track('error','permissions', 'Rep');
            redirect('/admin/permissions');
        }

        $this->user = $this->aauth->get_user();
    }

function show_view($view, $data=''){
      $data['user_info'] = $this->user;
      $data['app_settings'] = $this->app_settings;
      $this->load->view($this->app_settings['app_folder'].'include/header', $data);
      $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), $data);
      $this->load->view($this->app_settings['app_folder'].$view, $data);
      $this->load->view($this->app_settings['app_folder'].'include/footer', $data);
    }

    function crud_view($output){
        
        $output->user_info = $this->user;
        $output->app_settings = $this->app_settings;
        $this->load->view('include/crud_header', (array)$output);
        $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), (array)$output);
        $this->load->view('crud_view', (array)$output);
        $this->load->view('include/crud_footer', (array)$output);
    }

    function index(){

        $data['page_title'] = "Today's Call report";
        $data['customers'] = $this->spazapp_model->get_reps_customers(19);
        $this->show_view('rep/call_report', $data);
    }

    function fire_call_report(){
        $data['template'] = 'call_report';
        $data['message'] = array();
        $data['subject'] = 'SPAZAPP - Call Report';
        $this->spazapp_model->send_email('mike@ldd.co.za', $data);
        $this->spazapp_model->send_email('tim@spazapp.co.za', $data);
        echo 'Call report Sent';
    }

}