<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Training extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
/*        if (!$this->aauth->is_allowed('Dashboard')){
            $this->event_model->track('error','permissions', 'Dashboard');
            redirect('/admin/permissions');
        }*/
        
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

        try{
            $crud = new grocery_CRUD();
            
            
            $crud->set_table('bc_training');
            $crud->set_subject('training');

            $this->session->set_userdata('table', 'bc_training');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));

            $crud->set_relation('status','gbl_statuses','name');
           
            $crud->unset_delete();

            $crud->change_field_type('createdate','invisible');

            $crud->callback_before_insert(array($this, 'set_createdate'));

            $output = $crud->render();

            $output->page_title = 'Training';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }
}