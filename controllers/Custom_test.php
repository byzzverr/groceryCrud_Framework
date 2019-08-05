<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Custom_test extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('spazapp_model');
        $this->load->model('customer_model');
        $this->load->model('testing_model');

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Management')){
            $this->event_model->track('error','permissions', 'Management');
            redirect('/admin/permissions');
        } 
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

        $this->load->view('include/header', $output);
        $this->load->view('include/nav/'. get_defult_page($this->user));
        $this->load->view('example',$output);
        $this->load->view('include/footer', $output);
    }

function index(){
   $crud = new grocery_CRUD();
        
        $crud->set_table('test_methods');

        $crud->set_subject('Test');

        $crud->columns('name','description','folder','controller','method_name','createdate');

/*        $this->session->set_userdata(array('table' => 'customers'));
        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));*/

        $output = $crud->render();

        $output->page_title = 'Spazapp API Test Methods';

        $this->crud_view($output);
}
function custom_test(){
     $data['page_title'] = 'Spazapp API Custom Test Methods';
    //$data['test_methods'] = $this->testing_model->fetch_api_test_methods();
     $this->show_view('/testing/custom_test_list', $data);
}
}