<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Cocacola extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('news_model');
        $this->load->model('tt_merchant_model');

        $this->user = $this->aauth->get_user();

        if (!$this->aauth->is_loggedin()){
            redirect('/cocacola/login');
        }
    }

    function show_view($view, $data=''){
     
      $this->app_settings = get_app_settings(base_url());
      $this->load->view($this->app_settings['app_folder'].'include/header', $data);
      $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), $data);
      $this->load->view($this->app_settings['app_folder'].$view, $data);
      $this->load->view($this->app_settings['app_folder'].'include/footer', $data);


    }

    function resettotester($user_id){
        $pass = 'tester';
        echo $this->aauth->hash_password($pass, $user_id);
    }

    function index(){
       
        $logged_user_info = $this->aauth->get_user();
        if (!$this->event_model->seen_latest_news($logged_user_info->id)){
            redirect('/admin/news');
        }
        $this->load->model('user_model');
        $supplier = $this->user_model->get_supplier($logged_user_info->user_link_id);
        $supplier['company_name'];
        
        

        if($this->user->default_usergroup == 15){
            $data['page_title'] = 'Spaza Application';
            $data['merchant'] = $this->tt_merchant_model->get_tt_merchants_by_user_id($logged_user_info->id);           
        }

        if($this->user->default_usergroup == 14){
            $data['page_title'] = 'Spaza Application';
            $data['parent'] = $this->user_model->getParentId($logged_user_info->id);
        }




        $data['page_title'] = 'Welcome to Coca Cola Application';
        $date = date('Y-m-d',strtotime("-1 days"));

        $this->show_view('home/'. get_defult_page($this->user), $data);

    }

    function news(){
        $data['news'] = $this->event_model->get_latest_news();
        $data['page_title'] = $data['news']['heading'];
        $this->show_view('news', $data);
    }

    function add_seen_news_event($news_id){
        $logged_user_info = $this->aauth->get_user();
        $this->event_model->add_seen_news_event($logged_user_info->id, $news_id);
        redirect('/cocacola/cocacola/');
    }

    public function logout() {

        $this->aauth->logout();
        redirect("/cocacola/login/index/logout");
    }

    function permissions(){
        $data['page_title'] = 'Permissions';
        $this->show_view('permissions', $data);
    }

}