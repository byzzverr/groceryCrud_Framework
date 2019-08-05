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

        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->columns('user_link_id','username','email','name','default_usergroup','parent_id');
            $crud->where('default_usergroup !=', '8');

            $crud->set_relation_n_n('groups', 'aauth_user_to_group', 'aauth_groups', 'user_id', 'group_id', 'name','priority');

            //$crud->set_relation('user_link_id','customers','company_name');
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

            $output->page_title = 'Other Users';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }

    function store_owners()
    {
        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->columns('user_link_id','username','email','name','default_usergroup','parent_id');
            $crud->where('default_usergroup', '8');

            $crud->set_relation_n_n('groups', 'aauth_user_to_group', 'aauth_groups', 'user_id', 'group_id', 'name','priority');

            $crud->set_relation('user_link_id','customers','company_name');
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

            $output->page_title = 'Store Owners';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }        
    }

      function admin_users()
    {
        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->columns('user_link_id','username','email','name','default_usergroup','parent_id');
            $crud->where('default_usergroup', '1');

            $crud->set_relation_n_n('groups', 'aauth_user_to_group', 'aauth_groups', 'user_id', 'group_id', 'name','priority');

            $crud->set_relation('user_link_id','customers','company_name');
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

            $output->page_title = 'Admin';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }        
    }
  function traders()
    {
        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->columns('user_link_id','username','email','name','default_usergroup','parent_id');
            $crud->where('default_usergroup', '19');

            $crud->set_relation_n_n('groups', 'aauth_user_to_group', 'aauth_groups', 'user_id', 'group_id', 'name','priority');

            $crud->set_relation('user_link_id','customers','company_name');
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

            $output->page_title = 'Sparks';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }        
    }
function power_sparks()
    {
        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->columns('user_link_id','username','email','name','default_usergroup','parent_id');
            $crud->where('default_usergroup', '34');

            $crud->set_relation_n_n('groups', 'aauth_user_to_group', 'aauth_groups', 'user_id', 'group_id', 'name','priority');

            $crud->set_relation('user_link_id','traders','{first_name} {last_name} {cellphone}');
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

            $output->page_title = 'Power Sparks';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }        
    }

    function distributors()
    {
        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->columns('user_link_id','username','email','name','default_usergroup','parent_id');
            $crud->where('default_usergroup', '11');

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

            $output->page_title = 'Distributor Users';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }        
    }

    function add_user($var){


    	if($var == 'add' || isset($_POST['email'])){

        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->columns('username','email','name');

            $crud->change_field_type('ip_address','invisible');
            $crud->change_field_type('banned','invisible');
            $crud->change_field_type('last_activity','invisible');
            $crud->change_field_type('last_login_attempt','invisible');
            $crud->change_field_type('forgot_exp','invisible');
            $crud->change_field_type('remember_time','invisible');
            $crud->change_field_type('remember_exp','invisible');
            $crud->change_field_type('verification_code','invisible');
            $crud->change_field_type('login_attempts','invisible');
            $crud->change_field_type('last_login','invisible');
            $crud->change_field_type('parent_id','invisible');
            $crud->change_field_type('user_link_id','invisible');
            $crud->change_field_type('pass','invisible');

            $crud->unset_texteditor('pushtoken','full_text');

            $crud->set_relation('default_usergroup','aauth_groups','name');

            $crud->set_rules('default_usergroup', 'Default Usergroup','trim|required');
            $crud->set_rules('name', 'Name','trim|required');
            $crud->set_rules('username', 'User Name','trim|required');
            $crud->set_rules('email', 'Email','trim|required|callback_create_user');
            $crud->set_rules('cellphone', 'Cellphone','trim|required|numeric');

            $output = $crud->render();

            $output->page_title = 'Users';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    	}else{
			redirect('/users/user');
    	}
    }

    function reset_password($user_id){

        $this->load->model('user_model');
        echo $this->user_model->reset_password($user_id,0);
    }


    function create_user($email){

    	/* 

    	Before you say anything. Yes this is a major hack. BUT I am doing this for the greater good.
    	Creating a user happens here. It is then redirected to the index function in order to edit.
    	This was the only way to merge grocery crud with the Aauth library.

    	*/

    	$post_array = $_POST;
    	$new_user_id = '';

        $password = $this->generateRandomString(6);

    	$new_user_id = $this->aauth->create_user($post_array['email'],$password,$post_array['name'],$post_array['username'],$post_array['default_usergroup']);
    	$errors = $this->aauth->get_errors_array();

    	if(is_array($errors) && count($errors) >= 1){
    		$this->event_model->track('user','attempted_add_user', $errors[0]);
    		$this->form_validation->set_message('create_user', $errors[0]);
    		return false;

    	}else{
            $this->load->model('user_model');
            $this->user_model->update_user_cellphone($post_array['email'], array("cellphone" => $post_array['cellphone']));
            $this->send_welcome($post_array['email'],$post_array['name'],$post_array['username'],$post_array['cellphone'],$password);
    		$this->event_model->track('user','add_user', $new_user_id);
    		$this->form_validation->set_message('create_user', "<script>window.location.replace('/users/user');</script>");
    		return false;
    	}
    	
    }

    function send_welcome($email, $name, $username, $cellphone, $password){

        $data['name'] = $name;
        $data['password'] = $password;
        $data['username'] = $username;

        //get email message and send
        $subject = $this->comms_model->fetch_email_subject('welcome', $data);
        $this->comms_model->send_email($email, array('template' => 'welcome', 'subject' => $subject, 'message' => $data));

        //get sms message and send
        $message = $this->comms_model->fetch_sms_message('welcome', $data);
        $this->comms_model->send_sms($cellphone, $message);
    }

    function user_groups(){

        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_groups');
            $crud->set_subject('User Groups');

            $crud->set_relation_n_n('permissions', 'aauth_perm_to_group', 'aauth_perms', 'group_id', 'perm_id', 'name','priority');

            $crud->set_relation('parent_id','aauth_groups','name');

            $output = $crud->render();
            
            $output->page_title = 'User Groups';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }

    function group_permissions(){

        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_perms');
            $crud->set_subject('Group Permissions');

            $output = $crud->render();

            $output->page_title = 'Group Permissions';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }

    function _callback_get_parent($value, $primary_key){

        $options = $this->spazapp_model->get_parent_group_from_child_id($primary_key);
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

function generateRandomString($length=6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
}
    
function change_password($user_id){

    $new_password=$this->input->post('new_password');
    $confirm_password=$this->input->post('confirm_password');
    $username=$this->input->post('username');
    $password=$this->input->post('password');

    $veryfy_password=$this->user_model->veryfy_password($user_id,$password);
    $data['user_info'] = $this->user_model->get_user($user_id);

    $data['error']='';
    $data['message']='';
    if(isset($_POST['submit'])){
        // if($veryfy_password==TRUE){
            if($new_password==$confirm_password){
                $message=$this->user_model->change_password($user_id,$new_password); 

                $data['message']='<div class="alert2"><span class="closebtn">&times;</span>'.$message.'</div>';
            }else{
                $data['error']='<div class="alert1"><span class="closebtn">&times;</span>'.
                "New and Confirm Password don't match".'</div>';
            }
            
        // }else{
        //     $data['error']='<div class="alert1"><span class="closebtn">&times;</span>'.
        //     "Incorrect Password</div>";
        // }
    }
  
    $data['user_id']=$user_id;
    $data['page_title']="Change Password";
    $this->show_view('change_password',$data);
}
 
    
    
}


/*

        //$this->aauth->is_admin()
        //$this->aauth->get_user()
        //$this->aauth->control_group("Mod")
        //$this->aauth->control_perm(1)
        //$this->aauth->list_groups()
        //$this->aauth->list_users()
        //$this->aauth->is_allowed(1)
        //$this->aauth->is_admin()
        //$this->aauth->create_perm("deneme",'defff')
        //$this->aauth->update_perm(3,'dess','asd')
        //$this->aauth->allow(1,1)
        //$this->aauth->add_member(1,1)
        //$this->aauth->deny(1,1)
        //$this->aauth->mail()
        //$this->aauth->create_user('seass@asds.com','asdasdsdsdasd','asd')
        //$this->aauth->verify_user(11, 'MLUguBbXpd9Eeu5B')
        //$this->aauth->remind_password('seass@asds.com')
        //$this->aauth->reset_password(11,'0ghUM3oIC95p7uMa')
        //$this->aauth->is_allowed(1)
        //$this->aauth->control(1)
        //$this->aauth->send_pm(1,2,'asd')
        //$this->session->flashdata('d')
        //$this->aauth->add_member(1,1)
        //$this->aauth->create_user('asd@asd.co','d')
        //$this->aauth->send_pm(1,2,'asd','sad')
        //$this->aauth->list_pms(1,0,3,1)
        //$this->aauth->get_pm(6, false)
        //$this->aauth->delete_pm(6)
        //$this->aauth->set_as_read_pm(13)
        //$this->aauth->create_group('aa')
         $this->aauth->create_perm('asdda')

*/
