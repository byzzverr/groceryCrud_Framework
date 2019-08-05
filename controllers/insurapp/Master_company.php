<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Master_company extends CI_Controller {

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
        // if (!$this->aauth->is_allowed('Insurance'))
        // {
        //     $this->event_model->track('error','permissions', 'Insurance');
        //     redirect('/admin/permissions');
        // } 
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
        $user_info = $this->aauth->get_user();
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_products');
        $crud->set_subject('product');

        $crud->where('master_company', $user_info->user_link_id);

        $crud->columns('id','master_company','code','name','type','premium','cover','status','createdate');
        $crud->set_field_upload('image','assets/uploads/insurance/products');
       
        $crud->unset_delete();

        $crud->set_relation('status','gbl_statuses','name', "id IN (8,14)");
        $crud->set_relation('insurer','ins_entities','name', array('type' => '1'));
        $crud->set_relation('type','ins_types','name');
        $crud->set_relation('master_company','ins_master_companies','name');

        $crud->add_action('Premium Split', '', '/insurapp/insurance/premium_split','ui-icon-plus');
        $crud->add_action('Settings', '', '/insurapp/insurance/settings','ui-icon-plus');
        $crud->add_action('Terms and Conditions', '', '/insurapp/insurance/terms_audio','ui-icon-plus');

        $this->session->set_userdata(array('table' => 'ins_products'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Insurance Products';

        $this->crud_view($output);
    }

    function entities(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_entities');
        $crud->set_subject('entity');

        $crud->columns('id','name','type');
        $crud->set_relation('design','design','app');
        $crud->set_relation('type','ins_entity_types','name');
       
        $crud->unset_delete();

        $this->session->set_userdata(array('table' => 'ins_entities'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Entities';

        $this->crud_view($output);
    }

    function agencies($master_company=false){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_agencies');
        $crud->set_subject('insurance agency');
        
        if(is_numeric($master_company)){
            $crud->where(array('master_company' => $master_company));
            $crud->field_type('master_company','hidden', $master_company);
            $master_company_details = $this->insurance_model->get_master_company($master_company);
        }else{
            $crud->set_relation('master_company','ins_master_companies','name');
        }

        $crud->columns('master_company','name');
        $crud->set_relation('design','design','app');
       
        $crud->unset_delete();
        $crud->set_field_upload('logo','assets/uploads/insurance/agencies');

        $crud->set_relation_n_n('products', 'ins_agen_prod_link', 'ins_products', 'agency_id', 'product_id', 'code','priority');
        $crud->add_action('Manage Branches', '', '/insurapp/insurance/branches','ui-icon-plus');
        $crud->add_action('Manage Products', '', '/insurapp/insurance/agency_products','ui-icon-plus');

        $this->session->set_userdata(array('table' => 'ins_agencies'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        if(is_numeric($master_company)){
            $output->master_company = $master_company_details;
            $output->page_title = $master_company_details['name'] . ' Agencies';
        }else{
            $output->page_title = 'Insurance Agencies';
        }

        $this->crud_view($output);
    }

    function branches($agency=false){
       
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_branches');
        $crud->set_subject('insurance branch');

        $crud->columns('id','name');
        
        if(is_numeric($agency)){
            $crud->where(array('agency' => $agency));
            $crud->field_type('agency','hidden', $agency);
            $agency_details = $this->insurance_model->get_agency($agency);
        }else{
            $crud->set_relation('agency','ins_agencies','name');
        }

        $crud->unset_delete();

        $this->session->set_userdata(array('table' => 'ins_branches'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        if(is_numeric($agency)){
            $output->agency = $agency;
            $output->page_title = $agency_details['name'] . ' Branches';
        }else{
            $output->page_title = 'Insurance Branches';
        }

        $this->crud_view($output);
    }



    function sales(){
        
        $user_info=$this->aauth->get_user();

        $products=$this->insurance_model->get_master_company_products($user_info->user_link_id);
        $comma='';
        $ins_prod_id='';
        foreach ($products as $key => $value) {
            $ins_prod_id.=$comma.$value['id'];
            $comma=',';
        }
       
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_applications');
       
        $crud->set_subject('insurance');

        $crud->where('ins_prod_id IN ('.$ins_prod_id.')');

        $crud->set_relation('ins_prod_id','ins_products','name');
        
        $crud->columns('ins_prod_id','policy_number','premium','first_name','last_name','sa_id','picture','signature');
        
        $crud->display_as('id','SA ID Number');  
        
        $crud->callback_column('picture',array($this,'_callback_ins_applications_image'));
        $crud->callback_column('signature',array($this,'_callback_ins_applications_signature'));
        $crud->callback_column('dependent',array($this,'_callback_ins_dependent'));

        $crud->add_action('See Comms', '', '/insurapp/financial/application_comms','ui-icon-plus');
       
        $crud->unset_delete();
        $crud->unset_add();
        /*$crud->unset_edit();*/

        $this->session->set_userdata(array('table' => 'ins_m_funeral'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Insurance Applications';

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
