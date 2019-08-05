<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Distributor_profile extends CI_Controller{

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('spazapp_model');
        $this->load->model('customer_model');
        $this->load->model('order_model');
        $this->load->model('news_model');

        $this->user = $this->aauth->get_user();
        $d_id = $this->user->distributor_id;

        //redirect if not logged inorder_item
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Management')){
            $this->event_model->track('error','permissions', 'Management');
            $this->aauth->logout();
            redirect('/login');
        }

        // Check for Distributor id
        if ($d_id <= 0){
            $this->aauth->logout();
            redirect('/login');
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

    function index()
    {

        $distribute = $this->aauth->get_user();
        $distributor_id = $distribute->distributor_id;
        $id = $distribute->id;

        $data['profile'] = $this->customer_model->get_user_profile($id);
        $data['message'] = $this->session->flashdata('message');
        $data['page_title'] = 'Update Profile';
        $this->show_view('update_profile', $data);
    }

    public function update_profile()
    {
        $distributor = $this->aauth->get_user();
        $id = $distributor->id;

        $name = $this->input->post("name");
        $email = $this->input->post("email");
        $cellphone = $this->input->post("cellphone");

        // Error Handling


        if (strlen($name) < 2)
        {

            $message = '<p style="color: red;">The length of your name is too short</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/');
            return;

        }

        else if (strlen($email) < 5)
        {

            $message = '<p style="color: red;">The length of your email is too short</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/');
            return;

        }

        else if (strlen($cellphone) < 9)
        {

            $message = '<p style="color: red;">The length of your cellphone is too short</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/');
            return;

        }

        else 
        {
            $message = '<p style="color: green;">Succesfully updated your profile</p> <br />';
            $this->customer_model->update_profile($id, array(
                "name" => $name, 
                "email" => $email, 
                "cellphone" => $cellphone      
                )
            );

            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/');
        }
    }

    public function update_password()
    {  

        $distributor = $this->aauth->get_user();
        $userid = $distributor->id;

        $oldPassword = $distributor->pass;

        $oldPass = $this->input->post("oldPass");
        $pass = $this->input->post("pass");
        $confirmPass = $this->input->post("confirmPass");
        $oldPassed = $this->aauth->hash_password($oldPass, $userid);

        // Error Handling
       
        if(strlen($oldPass) == 0)
        {

            $message = '<p style="color: red;">Please enter your old password</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/');
            return;
        }

        else if(strlen($pass) == 0)
        {

            $message = '<p style="color: red;">Please enter your new message</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/');
            return;     

        }

        else if(strlen($confirmPass) == 0)
        {
 
            $message = '<p style="color: red;">Please confirm your new password</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/');
            return;

        }

        else if (strlen($pass) < 3)
        {

            $message = '<p style="color: red;">The password length is too short!</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/');
            return;
            
        }

        else if($pass != $confirmPass)
        {
 
            $message = '<p style="color: red;">Your new password and the confirm password do not match!</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/');
            return;  

        }
        
        else if ($oldPassed != $oldPassword)
        {

            $message = '<p style="color: red;">Your old password does not match the previous one. Enter the correct password!</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/');
            return;
            
        }

        else
        {
            $message = '<p style="color: green;">You succesfully updated your password</p> <br />';
            $this->aauth->update_password($userid, $pass);
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/');
        }

    }

    function company() 
    {

        $distribute = $this->aauth->get_user();
        $distributor_id = $distribute->distributor_id;
        $name = $this->order_model->getDistributorNameByID($distributor_id);

        $data['distributor'] = $this->customer_model->get_distributor_details($distributor_id);
        $data['page_title'] = $name->company_name.' Profile';
        $this->show_view('company_profile', $data);
    }

    public function update_company()
    {  

        $distributor = $this->aauth->get_user();
        $distributor_id = $distributor->distributor_id;

        $company_name = $this->input->post("company_name");
        $contact_name = $this->input->post("contact_name");
        $number = $this->input->post("number");
        $email = $this->input->post("email");
        $address = $this->input->post("address");

        // Error Handling
       
        if(empty($company_name))
        {

            $message = '<p style="color: red;">Please enter the company name!</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/company');
            return;

        }

        else if(empty($contact_name))
        {

            $message = '<p style="color: red;">Please enter the contact name!</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/company');
            return;

        }

        else if(empty($number))
        {

            $message = '<p style="color: red;">Please enter the phone number!</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/company');
            return;

        }

        else if(empty($email))
        {

            $message = '<p style="color: red;">Please enter the email address!</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/company');
            return;

        }

        else if(empty($address))
        {

            $message = '<p style="color: red;">Please enter the address!</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/company');
            return;
        }

        else if (strlen($company_name) < 2)
        {

            $message = '<p style="color: red;">The length of the company name is too short!</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/company');
            return;

        }

        else if (strlen($contact_name) < 2)
        {

            $message = '<p style="color: red;">The length of the contact name is too short!</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/company');
            return;

        }

        else if (strlen($number) < 9)
        {

            $message = '<p style="color: red;">The length of the phone number is too short!</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/company');
            return;

        }

        else if (strlen($address) < 2)
        {

            $message = '<p style="color: red;">The length of the address is too short!</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/company');
            return;

        }

        else
        {   

            $message = '<p style="color: green;">You succesfully updated your company profile</p> <br />';
            $this->customer_model->update_distributor($distributor_id, array(
                "company_name" => $company_name, 
                "contact_name" => $contact_name, 
                "number" => $number,
                "email" => $email,
                "address" => $address       
                )
            );

            $this->session->set_flashdata('message', $message);
            redirect('/distributors/distributor_profile/company');
        }
    }

    function user(){

        $distributor = $this->aauth->get_user();
        $distributor_id = $distributor->distributor_id;
        $name = $this->order_model->getDistributorNameByID($distributor_id);

        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('aauth_users');
            $crud->set_subject('Users');
            $crud->where('user_link_id', $distributor_id);
            $crud->columns('username','email','name','last_login');

            $crud->set_relation_n_n('groups', 'aauth_user_to_group', 'aauth_groups', 'user_id', 'group_id', 'name','priority');

            $crud->set_relation('user_link_id','customers','company_name');
            $crud->set_relation('default_usergroup','aauth_groups','name');
            $crud->set_relation('distributor_id','distributors','company_name');

            $crud->add_fields(array('email','cellphone','username','pass','name','user_link_id'));

            $crud->unset_edit_fields('pass','last_login','default_usergroup','distributor_id','pushtoken','groups','user_link_id', 'default_app');

            $crud->callback_column('parent_id',array($this,'_callback_populate_parent'));

            $crud->callback_edit_field('parent_id',array($this,'_callback_get_parent'));

            //_add_distributor_id
            $crud->callback_add_field('user_link_id',array($this,'_add_distributor_id'));

            $crud->add_action('Reset Password', '', '/users/reset_password','ui-icon-plus');

            $crud->change_field_type('ip_address','invisible');
            $crud->change_field_type('banned','invisible');
            $crud->change_field_type('last_activity','invisible');
            $crud->change_field_type('last_login_attempt','invisible');
            $crud->change_field_type('forgot_exp','invisible');
            $crud->change_field_type('remember_time','invisible');
            $crud->change_field_type('remember_exp','invisible');
            $crud->change_field_type('verification_code','invisible');
            $crud->change_field_type('login_attempts','invisible');
            $crud->change_field_type('parent_id','invisible');
            $crud->change_field_type('default_usergroup','invisible');

            $output = $crud->render();

            $output->page_title = $name->company_name.' Users';

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
            $crud->change_field_type('default_app','invisible');
            $crud->change_field_type('pushtoken','invisible');
            $crud->change_field_type('default_usergroup','invisible');
            $crud->change_field_type('distributor_id','invisible');
            $crud->change_field_type('user_link_id','invisible');
            $crud->change_field_type('pass','invisible');

            //$crud->set_relation('default_usergroup','aauth_groups','name');

            //$crud->set_rules('default_usergroup', 'Default Usergroup','trim|required');

            //$crud->add_fields(array('email','cellphone','username','pass','name','user_link_id'));

            //$crud->callback_add_field('user_link_id',array($this,'_add_distributor_id'));

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
            redirect('/distributors/distributor_profile/user');
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
        $default_usergroup = "11";

        $distributor = $this->aauth->get_user();
        $distributor_id = $distributor->user_link_id;

        $new_user_id = '';
        $this->load->model('user_model');

        $password = $this->generateRandomString(6);

        $new_user_id = $this->aauth->create_user($post_array['email'],$password,$post_array['name'],$post_array['username'],$default_usergroup);
        $errors = $this->aauth->get_errors_array();

        if(is_array($errors) && count($errors) >= 1){
            $this->event_model->track('user','attempted_add_user', $errors[0]);
            $this->form_validation->set_message('create_user', $errors[0]);
            return false;

        }else{

            $this->user_model->update_user_cellphone($post_array['email'], array(
                "cellphone" => $post_array['cellphone'],
                "user_link_id" => $distributor_id,
                "parent_id" => $new_user_id
                ));
            $this->send_welcome($post_array['email'],$post_array['name'],$post_array['username'],$post_array['cellphone'],$password);
            $this->event_model->track('user','add_user', $new_user_id);
            $this->form_validation->set_message('create_user', "<script>window.location.replace('/distributors/distributor_profile/user');</script>");
            return false;
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

    function _add_distributor_id(){ 

        $distributor = $this->aauth->get_user();
        $distributor_id = $distributor->distributor_id;   

        return '<input id="user_link_id" name="user_link_id" type="text" value="'.$distributor_id.'" maxlength="10" readonly>';
        //return $return;
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
    
}