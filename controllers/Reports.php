<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Reports extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->model("app_model");
        $this->load->helper('url');
        $this->load->helper('date_helper');
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
        $this->load->model('trader_model');
        $this->load->library('table');
        $this->load->library('pagination');
        $this->load->library('javascript_library');

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
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

        }elseif($this->session->userdata('reports_date_from') && $this->session->userdata('reports_date_from') != ''){            

            $date_from = $this->session->userdata('reports_date_from');
            $date_to = $this->session->userdata('reports_date_to');

        }else{

            $date_minus1week = date("Y-m-d H:m", strtotime('-1 week', time()));
            $date_from = $date_minus1week;
            $date_to = date("Y-m-d H:i");
        }

        $this->session->set_userdata('reports_date_from', $date_from);
        $this->session->set_userdata('reports_date_to', $date_to);
        
    }


function show_view($view, $data=''){
      $data['user_info'] = $this->user;
      $data['app_settings'] = $this->app_settings;
      $this->load->view($this->app_settings['app_folder'].'include/header', $data);
      $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), $data);
      $this->load->view($this->app_settings['app_folder'].$view, $data);
      $this->load->view($this->app_settings['app_folder'].'include/footer', $data);
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

    function sparks_report(){   
        $comma='';
        $labels='';
        $values='';
        $dataset='';

        $sparks_data = $this->trader_model->get_trader_data(); 
        
        foreach($sparks_data as $row){   

$dataset.='dataSet.push(["'.$row['id'].
                        '","'.$row['first_name']." ".$row['last_name'].
                        '","'.$row['username'].
                        '","'.$row['deposits_count'].
                        '","'.$row['deposits_total'].
                        '","'.$row['airtime_count'].
                        '","'.$row['airtime_total'].
                        '","'.$row['airtime_unique_numbers'].
                        '","'.$row['airtime_self_sales'].
                        '","'.$row['total_stores'].
                        '","'.$row['store_rewards_total'].
                        '","'.$row['last_activity'].
                        '","'.$row['last_activity_date'].
                        '","'.$row['completed_tasks'].
                        '","'.$row['region'].
                        '","'.$row['wallet_balance'].
                        '"]);';
        }

        $columns="{title:'ID'},".
        "{title:'Name'},".
        "{title:'Cellphone'},".
        "{title:'Deposits Count'},".
        "{title:'Deposits Total'},".
        "{title:'Airtime Count'},".
        "{title:'Airtime Total'},".
        "{title:'Airtime Unique Numbers'},".
        "{title:'Airtime Self Sales'},".
        "{title:'Stores Rewards Count'},".
        "{title:'Registration Rewards'},".
        "{title:'Last Activity'},".
        "{title:'Last Activity Date'},".
        "{title:'Completed Tasks'},".
        "{title:'Region'},".
        "{title:'Wallet Balance'},";

        $data['script'] = "".$this->data_table_script($dataset,$columns);
        $data['page_title'] = 'Sparks Report';
        $this->show_view('reports/sparks_report', $data);
            
    }

    function verified_users(){
        $verified_users=$this->user_model->get_verified_users();
        $dataset='';
        foreach ($verified_users as $key => $row) {
            $dataset.='dataSet.push([
            "'.$row['id'].'",
            "'.$row['company_name'].'",
            "'.$row['name'].'",
            "'.$row['msisdn'].'",
            "'.$row['province'].'",
            "'.$row['region'].'",
            "'.$row['createdate'].'"
            ]);';
        }

        $columns="{title:'Customer ID'},
        {title:'Customer'},
        {title:'Name'},
        {title:'MSISDN'},
        {title:'Province'},
        {title:'Region'},
        {title:'Createdate'}";

        $data['script'] = "".$this->data_table_script($dataset,$columns);
        $data['page_title']="Verified Users";

        $this->show_view('reports/verified_users',$data);

    }

    function data_table_script($dataset, $columns, $order_index=0){
        $data_table = $this->javascript_library->data_table_script($dataset, $columns, $order_index);
        return $data_table;
    }

}