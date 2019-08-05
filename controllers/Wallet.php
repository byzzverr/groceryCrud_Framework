<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Wallet extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/taptuck/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Management')){
            $this->event_model->track('error','permissions', 'Management');
            redirect('/taptuck/admin/permissions');
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

    function crud_view($output){
        
        $output->user_info = $this->user;
        $output->app_settings = $this->app_settings;
        $this->load->view('include/crud_header', (array)$output);
        $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), (array)$output);
        $this->load->view('crud_view', (array)$output);
        $this->load->view('include/crud_footer', (array)$output);
    }

    function global_wallet_transactions(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('wallet_transactions');
        $crud->set_subject('Merchant');
      
        /*$crud->set_relation('msisdn','aauth_users','{name} - {username}');*/

        $crud->columns('id','msisdn','debit','credit','reference','createdate');
      
        $crud->unset_delete();
        $crud->unset_edit();
        $crud->unset_add();

        $this->session->set_userdata(array('table' => 'wallet_transactions'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Global Wallet Transactions';

        $this->crud_view($output);
    }
    
}