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
      $this->app_settings = get_app_settings(base_url());
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

    function insurance_sales_report(){
        
        $from = $this->input->post('from');
        $to  = $this->input->post('to');
        
        $death_certificate ='';
        
        $data['page_title'] = 'Insurance Sales Report';

        $data['sales_results']= $this->insurance_model->get_all_insurance_sales($from, $to, $death_certificate);
        $data['sales_results_stats']= $this->insurance_model->get_all_insurance_sales_stats($from, $to, $death_certificate);
        $data['funeral_stats']= $this->insurance_model->get_funeral_product_stats($from, $to, $death_certificate);
        $data['query']= $this->insurance_model->insurance_report_csv($from, $to, $death_certificate);

        $this->show_view('ins_applications', $data);
    
    }
    
    
    function insurance_claim_report(){
        $from = $this->input->post('from');
        $to  = $this->input->post('to');
       
        $death_certificate ='1';
        $data['page_title'] = "Insurance Claim Report";

        $data['sales_results']= $this->insurance_model->get_all_insurance_sales($from, $to, $death_certificate);
        $data['sales_results_stats']= $this->insurance_model->get_all_insurance_sales_stats($from, $to,  $death_certificate);
        $data['funeral_stats']= $this->insurance_model->get_funeral_product_stats($from, $to, $death_certificate);
        $data['query']= $this->insurance_model->insurance_report_csv($from, $to, $death_certificate);


        $this->show_view('ins_applications', $data);
    
    }

    function rep_loctions(){
         //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);
        $this->load->library('googlemaps');
        $data['page_title'] = 'Rep Locations';
        $data['customers'] = $this->customer_model->get_insurance_reps();

        $config['center'] = '-29.8590, 31.0189';
        $config['zoom'] = '1';
        $this->googlemaps->initialize($config);

        foreach ($data['customers'] as $customer) 
        {
            $marker = array();
            $marker['position'] = $customer['location_lat']. ', '.$customer['location_long'];
            $marker['draggable'] = true;
            $marker['infowindow_content'] = '<img src="/assets/uploads/customer/'.$customer['store_picture'].'" alt="Store Picture" height="90" width="90"><br /><strong>'.$customer['company_name']. '</strong>&nbsp; <a href="/libertylife/dashboard/street_view?q='.$customer['id'].'"><i class="fa fa-male"></i></a> <br />'.$customer['address'].'<br />'.$customer['suburb'].'<br />'.$customer['name'].'';
            $marker['animation'] = 'DROP';
            $marker['icon'] = '/assets/img/custom_map_icon.png';
            $this->googlemaps->add_marker($marker);
        }
        

        $data['map'] = $this->googlemaps->create_map();


        $this->show_view('rep_locations', $data);

    }
     public function street_view()
    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);
        $id = $_GET['q'];
        $customer = $this->customer_model->getCustomerDetailsById($id);
        $data['customer'] = $customer;
        //echo $customer->company_name;
        //exit;
        $this->load->library('googlemaps');
        $data['page_title'] = $customer->company_name.' Street View';

        $config['center'] = $customer->location_lat.', '.$customer->location_long;
        $config['map_type'] = 'STREET';
        $config['streetViewPovHeading'] = 90;
        $this->googlemaps->initialize($config);
        $data['map'] = $this->googlemaps->create_map();


        $this->show_view('street_view', $data);
    }
}