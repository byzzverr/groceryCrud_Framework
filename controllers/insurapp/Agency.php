<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Agency extends CI_Controller {

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

  
     function user_agency_products(){
        $agency_id = $this->aauth->get_user()->user_link_id;
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_agen_prod_link');
        $crud->set_subject('product');

        if(is_numeric($agency_id)){
            $crud->where(array('agency_id' => $agency_id));
            $crud->field_type('agency_id','hidden', $agency_id);
            $agency = $this->insurance_model->get_agency($agency_id);
        }

        $crud->columns('product_id','credit');
       
        $crud->unset_delete();
        $crud->unset_edit();

        $crud->set_relation('product_id','ins_products','name');

        $crud->add_action('Sales Split', '', '/insurapp/insurance/sales_split/'.$agency_id,'ui-icon-plus');

        $this->session->set_userdata(array('table' => 'ins_agen_prod_link'));

        $output = $crud->render();

        if(is_numeric($agency_id)){
            $output->page_title = $agency['name'] . ' Products';
        }else{
            $output->page_title = 'Insurance Products';
        }

        $this->crud_view($output);
    }

  

    function products($agency_id){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_agen_prod_link');
        $crud->set_subject('product');

        if(is_numeric($agency_id)){
            $crud->where(array('agency_id' => $agency_id));
            $crud->field_type('agency_id','hidden', $agency_id);
            $agency = $this->insurance_model->get_agency($agency_id);
        }

        $crud->columns('product_id','credit');
       
        $crud->unset_delete();
        $crud->unset_edit();

        $crud->set_relation('product_id','ins_products','name');

        $crud->add_action('Sales Split', '', '/insurapp/insurance/sales_split/'.$agency_id,'ui-icon-plus');

        $this->session->set_userdata(array('table' => 'ins_agen_prod_link'));

        $output = $crud->render();

        if(is_numeric($agency_id)){
            $output->page_title = $agency['name'] . ' Products';
        }else{
            $output->page_title = 'Insurance Products';
        }

        $this->crud_view($output);
    }

   

    
    function _callback_ins_applications_death_certificates($value, $row){
        return '<a href="'.base_url().'aassets/uploads/insurance/death_certificates/'.$value.'" class="image-thumbnail"><img src="'.base_url().'assets/uploads/insurance/pictures/'.$value.'" width="100" /></a>';
    }
    
    function _callback_ins_applications_image($value, $row){
        return '<a href="'.base_url().'assets/uploads/insurance/pictures/'.$value.'" class="image-thumbnail"><img src="'.base_url().'assets/uploads/insurance/pictures/'.$value.'" width="100" /></a>';
    }
    
    function _callback_ins_applications_signature($value, $row){
        return '<a href="'.base_url().'assets/uploads/insurance/signatures/'.$value.'" class="image-thumbnail"><img src="'.base_url().'assets/uploads/insurance/signatures/'.$value.'" width="100" /></a>';
    }
    function _callback_ins_product_info($value, $row){
        $type='';
        $product = $this->insurance_model->get_insurance_product_id($row->ins_prod_id);
      
        return '<a href="/management/funeral_product_info/'.$row->ins_prod_id.'/'.$row->policy_number.'">'.$product['type'].'</a>';
    }
    
    function _callback_ins_dependent($value, $row){
    
        return '<a href="/management/ins_dependent/'.$row->policy_number.'">Dependent</a>';
    }


    function track_insert($post_array,$primary_key){
        $catgory = 'insurance';
        $action = 'insert';
        $label = 'User added a new entry to the '.$this->session->userdata('table').' table';
        $value = $primary_key;
        $this->event_model->track_event($catgory, $action, $label, $value);

        if($this->session->userdata('table') == 'ins_products'){
            $this->db->insert('ins_product_split',array("product_id" => $product_id));
        }

        $this->session->unset_userdata(array('table'));
    }

    function track_update($post_array,$primary_key){
        $catgory = 'insurance';
        $action = 'update';
        $label = 'User updated an entry in the '.$this->session->userdata('table').' table';
        $value = $primary_key;
        $this->event_model->track_event($catgory, $action, $label, $value);
        $this->session->unset_userdata(array('table'));
    }

    function _callback_colour1($value, $row){
        return '<svg style="width:100%; height:100px">
                    <rect style="width:100%; height:100px;fill:#'.$row->colour1.';stroke-width:0;stroke:rgb(0,0,0)" />
                </svg>';
    }

    function _callback_colour2($value, $row){
      
        return '<svg style="width:100%; height:100px">
                    <rect style="width:100%; height:100px;fill:#'.$row->colour2.';stroke-width:0;stroke:rgb(0,0,0)" />
                </svg>';
    } 

    function _callback_colour3($value, $row){
        return '<svg style="width:100%; height:100px">
                    <rect style="width:100%; height:100px;fill:#'.$row->colour3.';stroke-width:0;stroke:rgb(0,0,0)" />
                </svg>';
    } 

    function _callback_colour4($value, $row){
         return '<svg style="width:100%; height:100px">
                    <rect style="width:100%; height:100px;fill:#'.$row->colour4.';stroke-width:0;stroke:rgb(0,0,0)" />
                </svg>';
    }




}
