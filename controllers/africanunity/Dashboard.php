<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->model("app_model");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('survey_model');
        $this->load->model('spazapp_model');
        $this->load->model('financial_model');
        $this->load->model('order_model');
        $this->load->model('airtime_model');
        $this->load->model('user_model');
        $this->load->model('customer_model');
        $this->load->model('news_model');
        $this->load->model('delivery_model');
        $this->load->model('insurance_model');
        $this->load->model('fridge_model');
       
        $this->load->library('pagination');

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/africanunity/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
/*        if (!$this->aauth->is_allowed('Dashboard')){
            $this->event_model->track('error','permissions', 'Dashboard');
            redirect('/admin/permissions');
        }*/


        if(isset($_POST['date_from']) || isset($_POST['date_to'])){

            $date_from = $_POST['date_from'];
            $date_to = $_POST['date_to'];

        }elseif($this->session->userdata('dashboard_date_from') && $this->session->userdata('dashboard_date_from') != ''){            

            $date_from = $this->session->userdata('dashboard_date_from');
            $date_to = $this->session->userdata('dashboard_date_to');

        }else{

            $date_minus1week = date("Y-m-d H:m", strtotime('-1 week', time()));
            $date_from = $date_minus1week;
            $date_to = date("Y-m-d H:i");
        }

        $this->session->set_userdata('dashboard_date_from', $date_from);
        $this->session->set_userdata('dashboard_date_to', $date_to);
        
    }


    function show_view($view, $data=''){
      $data['user_info'] = $this->user;
      $data['app_settings'] = $this->app_settings;
      $this->load->view( $this->app_settings['app_folder'].'include/header', $data);
      $this->load->view( $this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), $data);
      $this->load->view( $this->app_settings['app_folder'].$view, $data);
      $this->load->view( $this->app_settings['app_folder'].'include/footer', $data);
    }

    function _example_output($output = null)
    {

        $this->load->model('checklist_model');
        $output->checklist_nav = $this->checklist_model->get_navigation();
        $this->load->view('include/header', $output);
        $this->load->view('include/nav/'. get_defult_page($this->user));
        $this->load->view('report_table',$output);
        $this->load->view('include/footer', $output);
    }

    function user_stats(){

        if(isset($_POST['user_id'])){
            $user_id = $_POST['user_id'];
        }else{
            $user_id = '2';
        }
        
        $data['users'] = $this->event_model->get_all_users("'africanunity','royal'");

        $data['user_info'] = $this->event_model->get_user_from_id($user_id);
        if(isset($data['user_info']->user_link_id) && $data['user_info']->user_link_id >= 1){
            $data['customer_info'] = $this->app_model->get_customer_info($data['user_info']->user_link_id);
        }
        $data['user_events'] = $this->event_model->get_user_events($data['user_info']->id);
        $data['user_stats'] = $this->event_model->get_user_stats($data['user_info']->id);
        $data['page_title'] = $data['user_info']->name . ' Stats';

        $labels = '';
        $values = '';
        $comma = '';

        foreach ($data['user_stats'] as $stat){

             $labels .= $comma.'"'.humanize($stat['action']).'"';
             $comma = ',';
        }

        $comma = '';
        foreach ($data['user_stats'] as $stat){
             $values .= $comma.humanize($stat['count']);
             $comma = ',';
        }

        $data['script'] = '
        var randomScalingFactor = function(){ return Math.round(Math.random()*100)};
        var barChartData = {
            labels : ['.$labels.'],
            datasets : [
                {
                    fillColor : "#d0bfa1",
                    strokeColor : "#c8ba9e",
                    highlightFill : "#bca789",
                    highlightStroke : "#b4a085",
                    data : ['.$values.']
                }
            ]
        }
        window.onload = function(){
            var ctx = document.getElementById("canvas").getContext("2d");
            window.myBar = new Chart(ctx).Bar(barChartData, {
                responsive : true
            });
        }';
        $this->show_view('profile', $data);
    }


}