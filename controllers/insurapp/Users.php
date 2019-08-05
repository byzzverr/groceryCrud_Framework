<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Users extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->model("example_model");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('spazapp_model');
        $this->load->model('user_model');
        $this->load->model('insurance_model');

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('User')){
        	$this->event_model->track('error','permissions', 'User');
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

    function index(){
    	redirect("/users/user");
    }

    function user(){
       
        $data['user_info'] = $this->aauth->get_user();
        $data['parent'] = $this->user_model->getParentId( $data['user_info']->id);

        $data['page_title'] =  'Insurapp User';
        
        $this->show_view('user_details',$data);

    }

    function reset_password($user_id){

        $this->load->model('user_model');
        echo $this->user_model->reset_password($user_id,0);
    }

    function your_branch_user(){
         try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->columns('user_link_id','username','email','name','default_usergroup','parent_id');
            $crud->where('default_usergroup', '25');
            $branch_id=$this->aauth->get_user()->user_link_id;
            $crud->where('user_link_id',$branch_id);
            $crud->set_relation_n_n('groups', 'aauth_user_to_group', 'aauth_groups', 'user_id', 'group_id', 'name','priority');

            $crud->set_relation('user_link_id','distributors','company_name');
            $crud->set_relation('default_usergroup','aauth_groups','name');

            $crud->unset_edit_fields('pass','last_login');

            $crud->callback_column('parent_id',array($this,'_callback_populate_parent'));

            $crud->callback_edit_field('parent_id',array($this,'_callback_get_parent'));

            // $crud->add_action('Reset Password', '', '/users/reset_password','ui-icon-plus');
            // $crud->add_action('Update Password', '', '/users/change_password','ui-icon-plus');

            $crud->unset_texteditor('pushtoken','full_text');

            $crud->change_field_type('ip_address','invisible');
            $crud->change_field_type('banned','invisible');
            $crud->change_field_type('last_activity','invisible');
            $crud->change_field_type('last_login_attempt','invisible');
            $crud->change_field_type('forgot_exp','invisible');
            $crud->change_field_type('remember_time','invisible');
            $crud->change_field_type('remember_exp','invisible');
            $crud->change_field_type('verification_code','invisible');
            $crud->change_field_type('login_attempts','invisible');

            $crud->unset_edit();
            $crud->unset_add();
            $crud->unset_delete();
            $output = $crud->render();

            $output->page_title = 'Branch Users';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }      
    }


    function _callback_get_parent($value, $primary_key){

        $options = $this->insurance_model->get_sales_agents_for_parent($primary_key);
        $return = '<select name="parent_id">';
        foreach ($options as $key => $value) {
            $return .= '<option value="'.$value['id'].'">'.$value['name'].'</option>';
        }

        $return .= '</select>';
        return $return;
    }


     function _callback_populate_parent($value, $row){
        if($value == 0 || $value == ''){
            return 'None';
        }else{

        $parent = $this->spazapp_model->get_user($value);
        return $parent['name'];

        }
    }

function master_company_users(){
      try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->columns('user_link_id','username','email','name','default_usergroup','parent_id');
            $crud->where('default_usergroup', '23');

            $crud->set_relation_n_n('groups', 'aauth_user_to_group', 'aauth_groups', 'user_id', 'group_id', 'name','priority');

            $crud->set_relation('user_link_id','ins_master_companies','name');
            $crud->set_relation('default_usergroup','aauth_groups','name');

            $crud->unset_edit_fields('pass','last_login');

            $crud->callback_column('parent_id',array($this,'_callback_populate_parent'));

            $crud->callback_edit_field('parent_id',array($this,'_callback_get_parent'));

            $crud->add_action('Reset Password', '', '/users/reset_password','ui-icon-plus');
            $crud->add_action('Update Password', '', '/users/change_password','ui-icon-plus');

            $crud->unset_texteditor('pushtoken','full_text');

            $crud->change_field_type('ip_address','invisible');
            $crud->change_field_type('banned','invisible');
            $crud->change_field_type('last_activity','invisible');
            $crud->change_field_type('last_login_attempt','invisible');
            $crud->change_field_type('forgot_exp','invisible');
            $crud->change_field_type('remember_time','invisible');
            $crud->change_field_type('remember_exp','invisible');
            $crud->change_field_type('verification_code','invisible');
            $crud->change_field_type('login_attempts','invisible');

            $output = $crud->render();

            $output->page_title = 'Master Company Users';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }        
 }
    

 function agency_users(){
      try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->columns('user_link_id','username','email','name','default_usergroup','parent_id');
            $crud->where('default_usergroup', '24');

            $crud->set_relation_n_n('groups', 'aauth_user_to_group', 'aauth_groups', 'user_id', 'group_id', 'name','priority');

            $crud->set_relation('user_link_id','ins_agencies','name');
            $crud->set_relation('default_usergroup','aauth_groups','name');

            $crud->unset_edit_fields('pass','last_login');

            $crud->callback_column('parent_id',array($this,'_callback_populate_parent'));

            $crud->callback_edit_field('parent_id',array($this,'_callback_get_parent'));

            $crud->add_action('Reset Password', '', '/users/reset_password','ui-icon-plus');
            $crud->add_action('Update Password', '', '/users/change_password','ui-icon-plus');

            $crud->unset_texteditor('pushtoken','full_text');

            $crud->change_field_type('ip_address','invisible');
            $crud->change_field_type('banned','invisible');
            $crud->change_field_type('last_activity','invisible');
            $crud->change_field_type('last_login_attempt','invisible');
            $crud->change_field_type('forgot_exp','invisible');
            $crud->change_field_type('remember_time','invisible');
            $crud->change_field_type('remember_exp','invisible');
            $crud->change_field_type('verification_code','invisible');
            $crud->change_field_type('login_attempts','invisible');

            $output = $crud->render();

            $output->page_title = 'Agency Users';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }        
 }




 function branch_users(){
      try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->columns('user_link_id','username','email','name','default_usergroup','parent_id');
            $crud->where('default_usergroup', '25');

            $crud->set_relation_n_n('groups', 'aauth_user_to_group', 'aauth_groups', 'user_id', 'group_id', 'name','priority');

            $crud->set_relation('user_link_id','ins_branches','name');
            $crud->set_relation('default_usergroup','aauth_groups','name');

            $crud->unset_edit_fields('pass','last_login');

            $crud->callback_column('parent_id',array($this,'_callback_populate_parent'));

            $crud->callback_edit_field('parent_id',array($this,'_callback_get_parent'));

            $crud->add_action('Reset Password', '', '/users/reset_password','ui-icon-plus');
            $crud->add_action('Update Password', '', '/users/change_password','ui-icon-plus');

            $crud->unset_texteditor('pushtoken','full_text');

            $crud->change_field_type('ip_address','invisible');
            $crud->change_field_type('banned','invisible');
            $crud->change_field_type('last_activity','invisible');
            $crud->change_field_type('last_login_attempt','invisible');
            $crud->change_field_type('forgot_exp','invisible');
            $crud->change_field_type('remember_time','invisible');
            $crud->change_field_type('remember_exp','invisible');
            $crud->change_field_type('verification_code','invisible');
            $crud->change_field_type('login_attempts','invisible');

            $output = $crud->render();

            $output->page_title = 'Branch Users';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }        
 }

  function sales_users(){
      try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->columns('user_link_id','username','email','name','default_usergroup','parent_id');
            $where_in=" default_usergroup IN(26,27,28,29,30)";
            $crud->where($where_in);

            $crud->set_relation_n_n('groups', 'aauth_user_to_group', 'aauth_groups', 'user_id', 'group_id', 'name','priority');

            $crud->set_relation('user_link_id','ins_branches','name');

            $crud->set_relation('default_usergroup','aauth_groups','name', ' id IN(26,27,28,29,30)');

           //$crud->set_relation('parent_id','aauth_users','name', ' id IN(26,27,28,29,30)');

            $crud->unset_edit_fields('pass','last_login');

            //$crud->callback_column('parent_id',array($this,'_callback_populate_parent'));

            //$crud->callback_edit_field('parent_id',array($this,'_callback_get_parent'));

            $crud->add_action('Reset Password', '', '/users/reset_password','ui-icon-plus');
            $crud->add_action('Update Password', '', '/users/change_password','ui-icon-plus');
            $crud->add_action('View Commission', '', '','ui-icon-image',array($this,'_callback_comm_view'));

            $crud->unset_texteditor('pushtoken','full_text');

            $crud->change_field_type('ip_address','invisible');
            $crud->change_field_type('banned','invisible');
            $crud->change_field_type('last_activity','invisible');
            $crud->change_field_type('last_login_attempt','invisible');
            $crud->change_field_type('forgot_exp','invisible');
            $crud->change_field_type('remember_time','invisible');
            $crud->change_field_type('remember_exp','invisible');
            $crud->change_field_type('verification_code','invisible');
            $crud->change_field_type('login_attempts','invisible');

            $output = $crud->render();

            $output->page_title = 'Sales Agents';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }        
    }

     function _callback_comm_view($primary_key , $row){
        
        return site_url('/insurapp/insurance/commission').'/'.$row->id.'/sales_agent';
     }

     function all_insurapp_users(){
      try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->columns('user_link_id','username','email','name','default_usergroup','parent_id');
            $where_in=" default_usergroup IN(23,25,26,27,28,29,30)";
            $crud->where($where_in);

            $crud->set_relation_n_n('groups', 'aauth_user_to_group', 'aauth_groups', 'user_id', 'group_id', 'name','priority');

            $crud->set_relation('user_link_id','distributors','company_name');
            $crud->set_relation('default_usergroup','aauth_groups','name');

            $crud->unset_edit_fields('pass','last_login');

            $crud->callback_column('parent_id',array($this,'_callback_populate_parent'));

            $crud->callback_edit_field('parent_id',array($this,'_callback_get_parent'));

            $crud->add_action('Reset Password', '', '/users/reset_password','ui-icon-plus');
            $crud->add_action('Update Password', '', '/users/change_password','ui-icon-plus');

            $crud->unset_texteditor('pushtoken','full_text');

            $crud->change_field_type('ip_address','invisible');
            $crud->change_field_type('banned','invisible');
            $crud->change_field_type('last_activity','invisible');
            $crud->change_field_type('last_login_attempt','invisible');
            $crud->change_field_type('forgot_exp','invisible');
            $crud->change_field_type('remember_time','invisible');
            $crud->change_field_type('remember_exp','invisible');
            $crud->change_field_type('verification_code','invisible');
            $crud->change_field_type('login_attempts','invisible');

            $output = $crud->render();

            $output->page_title = 'All Users';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }        
 }
}