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
        $crud->set_subject('product');

        $crud->columns('id','master_company','code','name','type','premium','cover','status','createdate');
        $crud->set_field_upload('image','assets/uploads/insurance/products');
       
        $crud->unset_delete();

        $crud->set_relation('status','gbl_statuses','name', "id IN (8,14)");
        $crud->set_relation('insurer','ins_insurers','name');
        $crud->set_relation('type','ins_types','name');
        $crud->set_relation('master_company','ins_master_companies','name');

        $crud->add_action('Premium Split', '', '/africanunity/insurance/premium_split','ui-icon-plus');
        $crud->add_action('Settings', '', '/africanunity/insurance/settings','ui-icon-plus');
        $crud->add_action('Terms and Conditions', '', '/africanunity/insurance/terms_audio','ui-icon-plus');

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

        $crud->add_action('Sales Split', '', '/africanunity/insurance/sales_split/'.$agency_id,'ui-icon-plus');

        $this->session->set_userdata(array('table' => 'ins_agen_prod_link'));

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
        $crud->add_action('Manage Agencies', '', '/africanunity/insurance/agencies','ui-icon-plus');

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
        $crud->add_action('Manage Branches', '', '/africanunity/insurance/branches','ui-icon-plus');
        $crud->add_action('Manage Products', '', '/africanunity/insurance/agency_products','ui-icon-plus');

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

    function sales_split($agency_id, $product_id){

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
