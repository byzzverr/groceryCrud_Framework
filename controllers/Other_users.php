
<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Other_users extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->model("example_model");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('spazapp_model');
        $this->load->model('tt_merchant_model');

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('User')){
//        	$this->event_model->track('error','permissions', 'User');
//            redirect('/admin/permissions');
        } 
        $this->app_settings = get_app_settings(base_url());
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

    function change_password(){
    $user_info = $this->aauth->get_user();
    $new_password=$this->input->post('new_password');
    $confirm_password=$this->input->post('confirm_password');
    $username=$this->input->post('username');
    $password=$this->input->post('password');

    $veryfy_password=$this->user_model->veryfy_password($user_info->id,$password);
    $user_info = $this->user_model->get_user($user_info->id);
    $data['name'] = $user_info->name;
    $data['username'] = $user_info->username;
    //print_r($user_info->name);exit;
    $data['error']='';
    $data['message']='';
    if(isset($_POST['submit'])){
        if($new_password==$confirm_password){
            $message=$this->user_model->change_password($user_info->id,$new_password); 

            $data['message']='<div class="alert2"><span class="closebtn">&times;</span>'.$message.'</div>';
        }else{
            $data['error']='<div class="alert1"><span class="closebtn">&times;</span>'.
            "New and Confirm Password don't match".'</div>';
        }
    }
  
    $data['user_id']=$user_info->id;
    $data['page_title']="Change Password";
    $this->show_view('change_password',$data);
}
    
}
