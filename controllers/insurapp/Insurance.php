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
        $this->load->library('javascript_library');
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
        $crud->set_subject('product');

        $crud->columns('id','master_company','code','name','type','premium','cover','status','createdate');
        $crud->set_field_upload('image','assets/uploads/insurance/products');
       
        $crud->unset_delete();

        $crud->set_relation('status','gbl_statuses','name', "id IN (8,14)");
        $crud->set_relation('insurer','ins_entities','name', array('type' => '1'));
        $crud->set_relation('type','ins_types','name');
        $crud->set_relation('master_company','ins_master_companies','name');
        $crud->set_relation('terms','ins_terms','heading');

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

    function master_company_products(){
        $user_info = $this->aauth->get_user();
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_products');
        $crud->set_subject('product');

        $crud->where('master_company', $user_info->user_link_id);

        $crud->columns('id','master_company','code','name','type','premium','cover','status','createdate');
        $crud->set_field_upload('image','assets/uploads/insurance/products');
       
        $crud->unset_delete();

        $crud->set_relation('status','gbl_statuses','name', "id IN (8,14)");
        $crud->set_relation('insurer','ins_insurers','name');
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

    function agency_products($agency_id){
        
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

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        if(is_numeric($agency_id)){
            $output->page_title = $agency['name'] . ' Products';
        }else{
            $output->page_title = 'Insurance Products';
        }

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

    function insurers(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_underwriters');
        $crud->set_subject('insurers ');

        $crud->columns('id','name','logo');
       
        $crud->unset_delete();
        $crud->set_field_upload('logo','assets/uploads/insurance/underwriters');

        $this->session->set_userdata(array('table' => 'ins_underwriters'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Insurance Underwriters';

        $this->crud_view($output);
    }

    function master_companies(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_master_companies');
        $crud->set_subject('master company');

        $crud->columns('id','name');
        $crud->set_relation('design','design','app');
       
        $crud->unset_delete();

        $this->session->set_userdata(array('table' => 'ins_master_companies'));
        $crud->add_action('Manage Agencies', '', '/insurapp/insurance/agencies','ui-icon-plus');

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Master Companies';

        $this->crud_view($output);
    }

    function entities(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_entities');
        $crud->set_subject('entity');

        $crud->columns('id','name','type');
        $crud->set_relation('design','design','app');
        $crud->set_relation('type','ins_entity_types','name');
        $crud->add_action('View Commission', '', '','ui-icon-image',array($this,'_callmback_comm_view'));
        $crud->unset_delete();

        $this->session->set_userdata(array('table' => 'ins_entities'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Entities';

        $this->crud_view($output);
    }

    function _callmback_comm_view($primary_key , $row){
        
        return site_url('/insurapp/insurance/commission').'/'.$row->id.'/entity';
    }
   
    function agencies($master_company=false){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_agencies');
        $crud->set_subject('insurance agency');
        
        if(is_numeric($master_company)){
            $crud->where(array('master_company' => $master_company));
            $crud->field_type('master_company','hidden', $master_company);
            $master_company_details = $this->insurance_model->get_master_company($master_company);
            $crud->columns('name');
        }else{
            $crud->set_relation('master_company','ins_master_companies','name');
            $crud->columns('master_company','name');
        }

        $crud->set_relation('design','design','app');
       
        $crud->unset_delete();
        $crud->set_field_upload('logo','assets/uploads/insurance/agencies');

        $crud->set_relation_n_n('products', 'ins_agen_prod_link', 'ins_products', 'agency_id', 'product_id', 'code','priority');
        $crud->add_action('Manage Branches', '', '/insurapp/insurance/branches','ui-icon-plus');
        $crud->add_action('Manage Products', '', '/insurapp/insurance/agency_products','ui-icon-plus');
        $crud->add_action('View Commission', '', '','ui-icon-image',array($this,'_agency_comm_view'));
        $crud->add_action('View Sales', '', '','ui-icon-image',array($this,'_agency_sales_view'));
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

    function _agency_comm_view($primary_key , $row){
        
        return site_url('/insurapp/insurance/commission').'/'.$row->id.'/agency';
    }
    function _agency_sales_view($primary_key , $row){
        
        return site_url('/insurapp/insurance/sales_report').'/'.$row->id.'/agency';
    }
    function _branch_sales_view($primary_key , $row){
        
        return site_url('/insurapp/insurance/sales_report').'/'.$row->id.'/branch';
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
        $crud->add_action('View Commission', '', '','ui-icon-image',array($this,'_branch_comm_view'));
        $crud->add_action('View Sales', '', '','ui-icon-image',array($this,'_branch_sales_view'));
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

    function statuses(){
       
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_statuses');
        $crud->set_subject('insurance status');

        $crud->columns('code','name');
        
        $crud->unset_delete();

        $this->session->set_userdata(array('table' => 'ins_statuses'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Insurance Statuses';

        $this->crud_view($output);
    }

    function terms(){
       
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_terms');
        $crud->set_subject('insurance terms');

        $crud->columns('id','heading','status','createdate');

        $crud->set_relation('status','gbl_statuses','name', "id IN (8,14)");
        
        $crud->unset_delete();

        $this->session->set_userdata(array('table' => 'ins_terms'));

        $crud->set_field_upload('file','assets/uploads/insurance/terms');

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Insurance Terms';

        $this->crud_view($output);
    }

    function _branch_comm_view($primary_key , $row){
        
        return site_url('/insurapp/insurance/commission').'/'.$row->id.'/branch';
    }

    function design(){
        $crud = new grocery_CRUD();

        
        $crud->set_table('design');
        $crud->set_subject('Design');
        $crud->set_field_upload('logo','assets/img');
        $crud->callback_column('colour1',array($this,'_callback_colour1'));
        $crud->callback_column('colour2',array($this,'_callback_colour2'));
        $crud->callback_column('colour3',array($this,'_callback_colour3'));
        $crud->callback_column('colour4',array($this,'_callback_colour4'));

        $this->session->set_userdata('table', 'design');
        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));
        
        $output = $crud->render();

        $output->page_title = 'Design';

        $this->crud_view($output);
    }

    function settings($product_id=false){


        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_settings');
        $crud->set_subject('product setting');

        if(is_numeric($product_id)){
            $crud->where(array('product_id' => $product_id));
            $crud->field_type('product_id','hidden', $product_id);
            $product = $this->insurance_model->get_product($product_id);
            $crud->columns('id','name', 'value', 'status', 'createdate');
        }else{
            $crud->columns('id','product_id','name', 'value', 'status', 'createdate');
            $crud->set_relation('product_id','ins_products','name');
        }

        $crud->set_relation('status','gbl_statuses','name', "id IN (8,14)");
       
        $crud->unset_delete();

        $this->session->set_userdata(array('table' => 'ins_terms_audio'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();
        $output->page_title = 'Product Settings';
        
        if(isset($product)){
            $output->page_title = $product['name'] . ' Settings';
        }

        $this->crud_view($output);

    }

    function terms_audio($product_id=false){


        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_terms_audio');
        $crud->set_subject('insurance terms');

        if(is_numeric($product_id)){
            $crud->where(array('product_id' => $product_id));
            $crud->field_type('product_id','hidden', $product_id);
            $product = $this->insurance_model->get_product($product_id);
            $crud->columns('id','language', 'status', 'createdate');
        }else{
            $crud->columns('id','product_id','language', 'status', 'createdate');
            $crud->set_relation('product_id','ins_products','name');
        }

        $crud->set_relation('status','gbl_statuses','name', "id IN (8,14)");
       
        $crud->unset_delete();
        $crud->set_field_upload('file','assets/uploads/insurance/terms_audio');

        $this->session->set_userdata(array('table' => 'ins_terms_audio'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();
        $output->page_title = 'Insurance Terms & Conditions';
        
        if(isset($product)){
            $output->page_title = $product['name'] . ' Terms & Conditions';
        }

        $this->crud_view($output);

    }

    function premium_split($product_id){

        if(isset($_POST['insurer_split']) && $_POST['insurer_split'] != 0){
            $total = 0;
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'split') !== false) {
                    $total += $value;
                }
            }

            if(round($total) == 100){
                $this->insurance_model->update_premium_split($product_id, $_POST);
            }
        }
        
        $data['product'] = $this->insurance_model->get_product($product_id);
        $data['insurers'] = $this->insurance_model->get_insurers();
        $data['entities'] = $this->insurance_model->get_entities();
        $data['page_title'] = 'Premium Split';
        $data['error'] = false;

        $this->show_view('product_split',$data);
    }

    function sales_split($agency_id, $link_id){

        $product_id = $this->insurance_model->get_product_id_from_agency_link_id($link_id);

        if(isset($_POST['agency']) && $_POST['agency'] != 0){
            $total = 0;
            foreach ($_POST as $key => $value) {
                if(in_array($key, array('agency','branch','tier_1'))){
                    $total += $value;
                }
            }
            if(round($total) == 100){
                $this->insurance_model->update_sales_split($agency_id, $product_id, $_POST);
            }
        }
        
        $data['product'] = $this->insurance_model->get_product($product_id, $agency_id);

        $data['agency_total'] = number_format($data['product']['premium'] * ($data['product']['split']['sales_channel_split'] / 100), 2);

        $data['page_title'] = 'Sales Split';
        $data['error'] = false;

        $this->show_view('sales_split',$data);
    }

    function sales(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_applications');

        $crud->where('sale_complete = 1');
        $crud->display_as('sale_complete','status');
        
        $crud->set_subject('insurance');

        $crud->set_relation('ins_prod_id','ins_products','name');
        $crud->set_relation('sale_complete','ins_statuses','name');
        
        $crud->columns('ins_prod_id','policy_number','premium','first_name','last_name','sa_id','sale_complete','picture','signature');
        
        $crud->display_as('id','SA ID Number');  
        
        $crud->callback_column('picture',array($this,'_callback_ins_applications_image'));
        $crud->callback_column('signature',array($this,'_callback_ins_applications_signature'));
        $crud->callback_column('dependent',array($this,'_callback_ins_dependent'));

        $crud->add_action('See Comms', '', '/insurapp/financial/application_comms','ui-icon-plus');
        $crud->add_action('Reverse Sale', '', '/insurapp/financial/reverse_comms','ui-icon-plus');
       
        $crud->unset_delete();
        $crud->unset_add();
        /*$crud->unset_edit();*/

        $this->session->set_userdata(array('table' => 'ins_applications'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Insurance Applications';

        $this->crud_view($output);
    }

    function applications(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_applications');

        $crud->where('sale_complete != 1');
        $crud->display_as('sale_complete','status');
        
        $crud->set_subject('insurance');

        $crud->set_relation('ins_prod_id','ins_products','name');
        $crud->set_relation('sale_complete','ins_statuses','name');
        
        $crud->columns('ins_prod_id','policy_number','premium','first_name','last_name','sa_id','sale_complete','picture','signature','application_date');
        
        $crud->order_by('application_date','DESC');  
        $crud->display_as('id','SA ID Number');  
        
        $crud->callback_column('picture',array($this,'_callback_ins_applications_image'));
        $crud->callback_column('signature',array($this,'_callback_ins_applications_signature'));
      
        $crud->add_action('See Comms', '', '/insurapp/financial/application_comms','ui-icon-plus');
        $crud->add_action('Reverse Sale', '', '/insurapp/financial/reverse_comms','ui-icon-plus');
        $crud->add_action('View Dependents', '', '','ui-icon-image',array($this,'_callback_ins_dependent'));
       
        $crud->unset_delete();
        $crud->unset_add();
        /*$crud->unset_edit();*/

        $this->session->set_userdata(array('table' => 'ins_applications'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Insurance Applications';

        $this->crud_view($output);
    } 
    
    function awaiting_payment(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_applications');

        $crud->where('sale_complete = 22');
        $crud->display_as('sale_complete','status');
        
        $crud->set_subject('insurance');

        $crud->set_relation('ins_prod_id','ins_products','name');
        $crud->set_relation('sale_complete','ins_statuses','name');
        
        $crud->columns('ins_prod_id','policy_number','premium','first_name','last_name','sa_id','sale_complete','picture','signature');
        
        $crud->display_as('id','SA ID Number');  
        
        $crud->callback_column('picture',array($this,'_callback_ins_applications_image'));
        $crud->callback_column('signature',array($this,'_callback_ins_applications_signature'));
        $crud->callback_column('dependent',array($this,'_callback_ins_dependent'));

        $crud->add_action('See Comms', '', '/insurapp/financial/application_comms','ui-icon-plus');
        $crud->add_action('Reverse Sale', '', '/insurapp/financial/reverse_comms','ui-icon-plus');
       
        $crud->unset_delete();
        $crud->unset_add();
        /*$crud->unset_edit();*/

        $this->session->set_userdata(array('table' => 'ins_applications'));

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
    
        return site_url('/insurapp/insurance/ins_dependent')."/".$row->policy_number;
    }


    function track_insert($post_array,$primary_key){


        $catgory = 'insurance';
        $action = 'insert';
        $label = 'User added a new entry to the '.$this->session->userdata('table').' table';
        $value = $primary_key;
        $this->event_model->track_event($catgory, $action, $label, $value);

        if($this->session->userdata('table') == 'ins_products'){

            $this->db->insert('ins_product_split',array("product_id" => $post_array['product_id']));
        }

        if($this->session->userdata('table') == 'ins_agen_prod_link'){
            $agency_id = $this->uri->segment(4);

            if(is_numeric($agency_id)){
                $this->db->insert('ins_product_sales_split',array("product_id" => $post_array['product_id'], "agency_id" => $agency_id));
            }
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



     function master_company_sales(){
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

function detailed_sales(){

        $data['date_from']=$this->input->post('date_from');
        $data['date_to']=$this->input->post('date_to');

        $master_company = 1;
        $user = $this->aauth->get_user();
        $type = $user->default_usergroup;
        $id = $user->user_link_id;

        if($type==23){
            $type = 'master_company';
            $commission_owner=$this->insurance_model->get_master_company($id)['name'];
            $id = $this->get_string_agencies($id);
        }

        if($type==24){
            $type = 'agency';
            $username=$this->insurance_model->get_agency_wallet($id);
            $commission_owner=$this->insurance_model->get_agency($id)['name'];
        }

        if($type==25){
            $type = 'branch';
            $username=$this->insurance_model->get_branch_wallet($id);
            $commission_owner=$this->insurance_model->get_branch($id)['name'];
        }
       
        if(in_array($type, array(26,27,28,29,30))){
            $username=$user->username;
            $commission_owner=$user->name;
        }

        $commission_owner = 'All';

        
        $sales = $this->insurance_model->get_applications($id, $type, $data['date_from'], $data['date_to']);

        $data['sales']['count'] = 0;
        $data['sales']['total'] = 0;

        $dataset='';
        foreach ($sales as $key => $r) {


            if(isset($data['products'][$r['product_name']])){
                $data['products'][$r['product_name']]['count']++;
                $data['products'][$r['product_name']]['total'] += $r['premium'];
            }else{
                $data['products'][$r['product_name']]['count'] = 1;
                $data['products'][$r['product_name']]['total'] = $r['premium'];
            }

            if(isset($data['branches'][$r['branch']])){
                $data['branches'][$r['branch']]['count']++;
                $data['branches'][$r['branch']]['total'] += $r['premium'];
            }else{
                $data['branches'][$r['branch']]['count'] = 1;
                $data['branches'][$r['branch']]['total'] = $r['premium'];
            }

            if(isset($data['agencies'][$r['agency']])){
                $data['agencies'][$r['agency']]['count']++;
                $data['agencies'][$r['agency']]['total'] += $r['premium'];
            }else{
                $data['agencies'][$r['agency']]['count'] = 1;
                $data['agencies'][$r['agency']]['total'] = $r['premium'];
            }

            $data['sales']['count']++;
            $data['sales']['total'] += $r['premium'];

                $dataset.='dataSet.push([
                             "'.$r['policy_number'].'",
                             "'.$r['product_name'].'",
                             "'.$r['product_type'].'",
                             "'.$r['premium'].'",
                             "'.$r['agency'].'",
                             "'.$r['branch'].'",
                             "'.$r['agent'].'",
                             "'.$r['application_date'].'"
                            ]);';
    
        }

        $columns="{ title: 'Policy Number'},
        { title: 'Product Name'},
        { title: 'Product Type'},
        { title: 'Premium'},
        { title: 'Agency'},
        { title: 'Branch'},
        { title: 'Agent'},
        { title: 'Createdate'}";


        $data['script'] =$this->javascript_library->data_table_script($dataset,$columns, 7)."";

        $data['page_title']=$commission_owner ." - Sales";
        $this->show_view('detailed_sales',$data);
    }

function sales_report($id,$type){

        $data['date_from']=$this->input->post('date_from');
        $data['date_to']=$this->input->post('date_to');

        if($type=='agency'){
            $username=$this->insurance_model->get_agency_wallet($id);
            $commission_owner=$this->insurance_model->get_agency($id)['name'];
        }

        if($type=='branch'){
            $username=$this->insurance_model->get_branch_wallet($id);
            $commission_owner=$this->insurance_model->get_branch($id)['name'];
        }
       
        if($type=='sales_agent'){
            $user=$this->user_model->get_user($id);
            $username=$user->username;
            $commission_owner=$user->name;
        }
        
        $sales = $this->insurance_model->get_applications($id, $type, $data['date_from'], $data['date_to']);
        $commission = $this->insurance_model->get_comm_wallet_transactions($username,$data['date_from'], $data['date_to']);

        $data['commission']['count'] = 0;
        $data['commission']['credit'] = 0;
        $data['commission']['debit'] = 0;
        $data['commission']['total'] = 0;


        foreach ($commission as $c) {
            $data['commission']['count']++;
            $data['commission']['credit'] += $c['credit'];
            $data['commission']['debit'] += $c['debit'];
        }

        $data['commission']['total'] = $data['commission']['credit']-$data['commission']['debit'];

        $data['sales']['count'] = 0;
        $data['sales']['total'] = 0;

        $dataset='';
        foreach ($sales as $key => $r) {


            if(isset($data['products'][$r['product_name']])){
                $data['products'][$r['product_name']]['count']++;
                $data['products'][$r['product_name']]['total'] += $r['premium'];
            }else{
                $data['products'][$r['product_name']]['count'] = 1;
                $data['products'][$r['product_name']]['total'] = $r['premium'];
            }

            if(isset($data['branches'][$r['branch']])){
                $data['branches'][$r['branch']]['count']++;
                $data['branches'][$r['branch']]['total'] += $r['premium'];
            }else{
                $data['branches'][$r['branch']]['count'] = 1;
                $data['branches'][$r['branch']]['total'] = $r['premium'];
            }

            $data['sales']['count']++;
            $data['sales']['total'] += $r['premium'];

                $dataset.='dataSet.push([
                             "'.$r['policy_number'].'",
                             "'.$r['product_name'].'",
                             "'.$r['product_type'].'",
                             "'.$r['premium'].'",
                             "'.$r['branch'].'",
                             "'.$r['agent'].'",
                             "'.$r['application_date'].'"
                            ]);';
    
        }

        $columns="{ title: 'Policy Number'},
        { title: 'Product Name'},
        { title: 'Product Type'},
        { title: 'Premium'},
        { title: 'Branch'},
        { title: 'Agent'},
        { title: 'Createdate'}";


        $data['script'] = "".$this->data_table_script($dataset,$columns)."";

        $data['page_title']=$commission_owner ." - Sales";
        $this->show_view('sales',$data);
    }


function commission($id,$type){

        $data['date_from']=$this->input->post('date_from');
        $data['date_to']=$this->input->post('date_to');

        if($type=='entity'){
            $username=$this->insurance_model->get_entity_wallet($id);
            $commission_owner=$this->insurance_model->get_entity($id)['name'];
        }

        if($type=='branch'){
            $username=$this->insurance_model->get_branch_wallet($id);
            $commission_owner=$this->insurance_model->get_branch($id)['name'];
        }

        if($type=='agency'){
            $username=$this->insurance_model->get_agency_wallet($id);
            $commission_owner=$this->insurance_model->get_agency($id)['name'];
        }
        
        if($type=='sales_agent'){
            $user=$this->user_model->get_user($id);
            $username=$user->username;
            $commission_owner=$user->name;
        }
        
        $commissions=$this->insurance_model->get_comm_wallet_transactions($username,$data['date_from'],$data['date_to']);

        $data['sales']['count'] = 0;;
        $data['sales']['total'] = 0;
        $data['products'] = array();

        $dataset='';
        foreach ($commissions as $key => $r) {

            $data['sales']['count']++;
            $data['sales']['total'] += $r['credit'];

            if(isset($data['products'][$r['application']['product_name']])){
                $data['products'][$r['application']['product_name']]['count']++;
                $data['products'][$r['application']['product_name']]['total'] += $r['credit'];
            }else{
                $data['products'][$r['application']['product_name']]['count'] = 1;
                $data['products'][$r['application']['product_name']]['total'] = $r['credit'];
            }

            if(isset($data['agencies'][$r['sales_agent']['link']['agency_name']])){
                $data['agencies'][$r['sales_agent']['link']['agency_name']]['count']++;
                $data['agencies'][$r['sales_agent']['link']['agency_name']]['total'] += $r['credit'];
            }else{
                $data['agencies'][$r['sales_agent']['link']['agency_name']]['count'] = 1;
                $data['agencies'][$r['sales_agent']['link']['agency_name']]['total'] = $r['credit'];
            }

            if(isset($data['branches'][$r['sales_agent']['link']['branch_name']])){
                $data['branches'][$r['sales_agent']['link']['branch_name']]['count']++;
                $data['branches'][$r['sales_agent']['link']['branch_name']]['total'] += $r['credit'];
            }else{
                $data['branches'][$r['sales_agent']['link']['branch_name']]['count'] = 1;
                $data['branches'][$r['sales_agent']['link']['branch_name']]['total'] = $r['credit'];
            }

            if(isset($data['agents'][$r['sales_agent']['name']])){
                $data['agents'][$r['sales_agent']['name']]['count']++;
                $data['agents'][$r['sales_agent']['name']]['total'] += $r['credit'];
            }else{
                $data['agents'][$r['sales_agent']['name']]['count'] = 1;
                $data['agents'][$r['sales_agent']['name']]['total'] = $r['credit'];
            }
        
                $dataset.='dataSet.push([
                             "'.$r['id'].'",
                             "'.$r['msisdn'].'",
                             "'.$r['debit'].'",
                             "'.$r['credit'].'",
                             "'.$r['reference'].'",
                             "'.$r['application']['policy_number'].'",
                             "'.$r['application']['product_name'].'",
                             "'.$r['application']['sales_agent'].'",
                             "'.$r['sales_agent']['link']['branch_name'].'",
                             "'.$r['sales_agent']['link']['agency_name'].'",
                             "'.$r['createdate'].'"
                            ]);';
    
        }

        $columns="{ title: 'id'},{ title: 'Msisdn'},{ title: 'Debit'},{ title: 'Credit'},{ title: 'Reference'},{ title: 'Policy Number'},{ title: 'Product Name'},{ title: 'Sales Agent'},{ title: 'Branch'},{ title: 'Agency'},{ title: 'Createdate'}";


        $data['script'] = "".$this->data_table_script($dataset,$columns)."";

        $data['page_title']=$commission_owner ." - Commissions";
        $this->show_view('commission',$data);
    }

    function data_table_script($dataset,$columns){
        $excel='<i class="icon-download"></i> Excel';
        $pdf='<i class="glyphicon glyphicon-list-alt"></i> PDF';
        $csv='<i class="icon-download"></i> CSV';
        $copy='<i class=""></i> Copy';
      
        $datatable="var dataSet = [ ];
        ". $dataset."
        $(document).ready(function() {
            $('#report_table').DataTable( 
            {
                'order': [[ 0,'desc' ]],
                data:dataSet,
                columns: [
                    ".$columns."
                ],
            dom: 'Bfrtip',
                  buttons: [
                    {
                      extend:    'copyHtml5',
                       text:'$copy',
                        titleAttr: 'Copy'
                    },
                    {
                        extend:    'excelHtml5',
                        text:      '$excel',
                        titleAttr: 'Excel'
                    },
                    {
                        extend:    'csvHtml5',
                        
                        text:      '$csv',
                       
                        titleAttr: 'CSV'
                    },
                    {
                        extend:    'pdfHtml5',
                        text:      '$pdf',
                       
                        titleAttr: 'PDF'
                    }
                ]
            } 
            );

        } );";
        return $datatable;
    }

        function ins_dependent($policy_number){
    
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_app_dependants');
        
        $crud->where('policy_number',$policy_number);
        
        $crud->set_subject('insurance');
        $crud->columns('policy_number','first_name','last_name','type','dob','createdate');
        $crud->unset_delete();
        ///$crud->unset_add();
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

    function get_string_agencies($master_company){
        $query = $this->db->query("SELECT id FROM ins_agencies WHERE master_company = $master_company");
        $res = $query->result_array();
        return implode(',', $res);
    }

}
