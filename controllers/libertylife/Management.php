<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Management extends CI_Controller {

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
        $this->load->model('fridge_model');
       

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin())
        {
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Management'))
        {
            $this->event_model->track('error','permissions', 'Management');
            redirect('/admin/permissions');
        } 
    }

    function show_view($view, $data=''){
      //$this->app_settings = get_app_settings(base_url());
        //Did this for testing 
      $this->app_settings['app_folder']='libertylife/';
      $this->load->view($this->app_settings['app_folder'].'include/header', $data);
      $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), $data);
      $this->load->view($this->app_settings['app_folder'].$view, $data);
      $this->load->view($this->app_settings['app_folder'].'include/footer', $data);
    }

    function _example_output($output = null)
    {
       // $this->app_settings = get_app_settings(base_url());

        //Did this for testing 
        $this->app_settings['app_folder']='libertylife/';

        $this->load->view($this->app_settings['app_folder'].'include/header', $output);
        $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user));
        $this->load->view('example',$output);
        $this->load->view($this->app_settings['app_folder'].'include/footer', $output);
    }

  function ins_applications(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_m_applications');
        
        $crud->set_subject('insurance');

        $crud->set_relation('ins_prod_id','ins_m_funeral','type');
        
        $crud->columns('policy_number','name','surname','id','passport_number','picture','signature','product','dependent');
        
        $crud->display_as('id','SA ID Number');  
        
        $crud->callback_column('picture',array($this,'_callback_ins_applications_image'));
        $crud->callback_column('signature',array($this,'_callback_ins_applications_signature'));
        $crud->callback_column('product',array($this,'_callback_ins_product_info'));
        $crud->callback_column('dependent',array($this,'_callback_ins_dependent'));
       
        $crud->unset_delete();
        $crud->unset_add();
        $crud->unset_edit();

        $this->session->set_userdata(array('table' => 'ins_m_funeral'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Insurance Applications';

        $this->crud_view($output);
    }  
    
    function insurance_claims(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_m_applications');
        $crud->where('death_certificate !=','null');
        
        $crud->set_subject('insurance');

        $crud->set_relation('ins_prod_id','ins_m_funeral','type');
        
        $crud->columns('policy_number','name','surname','id','passport_number','picture','signature','death_certificate','product','dependent');
        
        $crud->display_as('id','SA ID Number');  
        
        $crud->callback_column('picture',array($this,'_callback_ins_applications_image'));
        $crud->callback_column('signature',array($this,'_callback_ins_applications_signature'));
        $crud->callback_column('death_certificate',array($this,'_callback_ins_applications_death_certificates'));
        $crud->callback_column('product',array($this,'_callback_ins_product_info'));
        $crud->callback_column('dependent',array($this,'_callback_ins_dependent'));
       
        $crud->unset_delete();
        $crud->unset_add();
        $crud->unset_edit();

        $this->session->set_userdata(array('table' => 'ins_m_funeral'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Insurance Claims';

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
    
    
    function ins_application(){

            $data['ins_results'] = $this->insurance_model->get_all_insurance_app();
            $data['query'] = $this->insurance_model->get_all_info_csv();
            $data['query2'] = $this->insurance_model->get_dependants_csv();
            $data['page_title'] = "Insurance Applications"; 
            $this->show_view('insurance/ins_applications', $data);   
    }

    function ins_dependents($policy_number){

            $data['dependents'] = $this->insurance_model->get_ins_dependent_by_policy_number($policy_number);

            $data['page_title'] = "Dependents"; 
            $data['policy_number'] = $policy_number; 
            $this->show_view('insurance/ins_applications', $data);   
    }
    
    function ins_dependent($policy_number){
    
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_m_app_dependants');
        
        $crud->set_relation('relation_type','ins_m_dependent_types','name');
        
        $crud->where('policy_number',$policy_number);
        
        $crud->set_subject('insurance');
        $crud->columns('dependent_number','relation_first_name','relation_surname','relation_type','relation_date_of_birth','cover_level');
        
        $crud->fields('policy_number','relation_first_name','relation_surname','relation_type','relation_date_of_birth','cover_level');
        $crud->unset_delete();
        $crud->unset_add();
        $crud->unset_edit();
        
         $crud->callback_field('policy_number',array($this,'_callback_policy_number'));
    
        $this->session->set_userdata(array('table' => 'ins_m_funeral'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Insurance Dependent';
        $output->policy_number = $policy_number;

        $this->crud_view($output);   
    }
    function _callback_policy_number(){
        $policy_number = $this->uri->segment(3);
        return '<input type="text" value="'.$policy_number.'" name="policy_number" readonly/>';
    }
    function funeral_product_info($product_id, $policy_number){
        
        $data['product_info'] = $this->insurance_model->get_insurance_product_id($product_id);
        $data['page_title'] = "Funeral Product Info"; 
        $data['policy_number'] = $policy_number; 
        $this->show_view('insurance/ins_applications', $data);   
    }

    function ins_application_form(){
        $data['page_title'] = "Insurance Applications"; 
        $data['products'] = $this->insurance_model->get_ins_products();
        $this->load->view('insurance/ins_application_form_1', $data);   
    }

    function insurance(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_m_funeral');
        $crud->set_subject('insurance');

        $crud->columns('id','type','member_benefit','spouse_option','premium','sale_reward','enabled');
        
        $crud->unset_delete();
        $crud->unset_add();
        $crud->unset_edit();

        $this->session->set_userdata(array('table' => 'ins_m_funeral'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Insurance';

        $this->crud_view($output);
    }

    function reps(){

        $reps=$this->customer_model->get_insurance_reps();
     
        $crud = new grocery_CRUD();
        
        $crud->set_table('customers');
        $crud->set_subject('Customers');

        foreach ($reps as $row) {
            $crud->or_where('customers.id',$row['id']);
        }
      
        $crud->set_relation('region_id','regions','name');
        $crud->set_relation('province','provinces','name');
        
        $crud->columns('first_name','last_name','company_name','cellphone','email','address','region_id','suburb','province');;
        
        $crud->unset_delete();
        $crud->unset_add();
        $crud->unset_edit();

        $this->session->set_userdata(array('table' => 'ins_m_funeral'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Reps';

        $this->crud_view($output);
    }

}
