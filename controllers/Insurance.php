<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Insurance extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('spazapp_model');
        $this->load->model('customer_model');
        $this->load->model('product_model');
        $this->load->model('insurance_model');
        $this->load->model('news_model');
       

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin())
        {
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Insurance'))
        {
            $this->event_model->track('error','permissions', 'Insurance');
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

    function crud_view($output){
        
        $output->user_info = $this->user;
        $output->app_settings = $this->app_settings;
        $this->load->view('include/crud_header', (array)$output);
        $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), (array)$output);
        $this->load->view('crud_view', (array)$output);
        $this->load->view('include/crud_footer', (array)$output);
    }

    function products(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_products');
        $crud->set_subject('products');

        $crud->columns('id','code','name','type','premium','cover','status','createdate');
        $crud->set_field_upload('image','assets/uploads/insurance/products');
       
        $crud->unset_delete();

        $crud->set_relation('status','gbl_statuses','name', "id IN (8,14)");
        $crud->set_relation('insurer','ins_entities','name', array('type' => '1'));
        $crud->set_relation('underwriter','ins_underwriters','name');
        $crud->set_relation('type','ins_types','name');

        $this->session->set_userdata(array('table' => 'ins_products'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Insurance Products';

        $this->crud_view($output);
    }


    function types(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_types');
        $crud->set_subject('insurance types');

        $crud->columns('id','name');
       
        $crud->unset_delete();

        $this->session->set_userdata(array('table' => 'ins_products'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Insurance Types';

        $this->crud_view($output);
    }

    function underwriters(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_underwriters');
        $crud->set_subject('insurance underwriters');

        $crud->columns('id','name','logo');
       
        $crud->unset_delete();
        $crud->set_field_upload('logo','assets/uploads/insurance/underwriters');

        $this->session->set_userdata(array('table' => 'ins_products'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Insurance Underwriters';

        $this->crud_view($output);
    }
}
