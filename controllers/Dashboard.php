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
        $this->load->model('task_model');
        $this->load->model('ott_model');
        $this->load->model('photosnap_model');
        $this->load->model('smartcall_model');
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
        $dataset='';
        if(isset($_POST['user_id'])){
            $user_id = $_POST['user_id'];
        }else{
            $user_id = '2';
        }
        
        $data['users'] = $this->event_model->get_all_users();
        $data['user_info'] = $this->event_model->get_user_from_id($user_id);
        if(isset($data['user_info']->user_link_id) && $data['user_info']->user_link_id >= 1){
            $data['customer_info'] = $this->app_model->get_customer_info($data['user_info']->user_link_id);
        }
        $data['user_events'] = $this->event_model->get_user_events($data['user_info']->id);
        $data['user_stats'] = $this->event_model->get_user_stats($data['user_info']->id);
        $data['page_title'] = $data['user_info']->name . ' Stats';

        foreach ($data['user_events'] as $key => $row) {
            $dataset.='dataset.push([
             "'.$row['event_id'].'",
             "'.$row['category'].'",
             "'.$row['action'].'",
             "'.$row['label'].'",
             "'.$row['value'].'",
             "'.$row['createdate'].'"
             ]);';
        }
        $data['dataset']=$dataset;

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

    function customer_stats(){

        if(isset($_POST['customer_id'])){
            $customer_id = $_POST['customer_id'];
        }else{
            $customer_id = '1';
        }

        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');
        $data['customers'] = $this->spazapp_model->get_all_customers();
        $data['customer_info'] = $this->spazapp_model->get_customer($customer_id);
        $data['customer_events'] = $this->event_model->get_customer_events($data['customer_info']['id']);
        $data['customer_orders'] = $this->spazapp_model->get_orders_for_customer($data['customer_info']['id']); 
        $data['page_title'] = $data['customer_info']['company_name'] . ' Stats';
        $labels = '';
        $values = '';
        $comma = '';
        $data['customer_stats'] = $this->spazapp_model->get_prod_order_items($data['customer_info']['id']);
        foreach ($data['customer_stats'] as $stat){

             $labels .= $comma.'"'.humanize($stat['name']).'"';
             $comma = ',';
        }

        $comma = '';
        foreach ($data['customer_stats'] as $stat){
             $values .= $comma.humanize($stat['quantity']);
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
        }

        $(function() {
            $("#datepicker" ).datepicker( { dateFormat: "yy-mm-dd" });
            $("#datepicker1" ).datepicker( { dateFormat: "yy-mm-dd" });
        });';
        $this->show_view('customer_stats', $data);


}
 function supplier_customer_stats(){

        $user_info = $this->aauth->get_user();
        $supplier = $this->user_model->get_supplier($user_info->user_link_id);
     
        $data['date_from'] = $this->session->userdata('dashboard_date_from');

        $data['date_to'] = $this->session->userdata('dashboard_date_to');
        $data['customers'] = $this->spazapp_model->get_all_customers();
        $data['customer_info'] = $this->spazapp_model->get_customer($user_info->user_link_id);

        $data['customer_events'] = $this->event_model->get_customer_events($data['customer_info']['id']);

        $data['customer_orders'] = $this->spazapp_model->get_orders_for_customer($data['customer_info']['id']);

        $data['customer_stats'] = $this->spazapp_model->get_prod_order_items($data['customer_info']['id']);



        $data['page_title'] = $data['customer_info']['company_name'] . ' Stats';

        $labels = '';
        $values = '';
        $comma = '';

        foreach ($data['customer_stats'] as $stat){

             $labels .= $comma.'"'.humanize($stat['name']).'"';
             $comma = ',';
        }

        $comma = '';
        foreach ($data['customer_stats'] as $stat){
             $values .= $comma.humanize($stat['quantity']);
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
    }

        $(function() {
            $("#datepicker" ).datepicker( { dateFormat: "yy-mm-dd" });
            $("#datepicker1" ).datepicker( { dateFormat: "yy-mm-dd" });
        });

        ';
        $this->show_view('customer_stats', $data);


    }

    function get_all_events($limit=0){
        $count = 20;
        $query_results = $this->event_model->get_all_events($limit,$count);
        $page_title = 'All Events';

        /* Pagination Config */
        $config['num_links'] = 2;
        $config['base_url'] = '/dashboard/get_all_events/';
        $config['total_rows'] = $query_results['num_rows'];
        $config['per_page'] = $count;

        $this->pagination->initialize($config); 

        $pagination_links = $this->pagination->create_links();

        $this->view($query_results['results'], $page_title, $pagination_links,$query_results['query']);
    }

    function get_orders($limit=0){
        $count = 20;
        
        $query_results['orders_results']  = $this->app_model->get_all_orders($limit,$count);
        $data['query_results'] = $query_results['orders_results']['orders'];
        $data['query'] = $query_results['orders_results']['query'];
        $data['page_title'] = ' All Orders';
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');

        $stores_orders = $this->spazapp_model->get_strores_orders();
        $products_orders = $this->spazapp_model->get_products_orders();
        $countResult = COUNT($products_orders) + 1;
        $data['script'] ='';

        $labels = '';
        $values = '';
        $comma = '';

        foreach ($stores_orders as $stat){

             $labels .= $comma.'"'.humanize($stat['company_name']).'"';
             $comma = ',';
        }

        $comma = '';
        foreach ($stores_orders as $stat){
             $values .= $comma.humanize($stat['orders']);
             $comma = ',';
        }

        $data['script'] .= '
        var randomScalingFactor = function(){ return Math.round(Math.random()*100)};

        var barChartData1 = {
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
        ';

        $labels = '';
        $values = '';
        $comma = '';

        foreach ($products_orders as $stat){

             $labels .= $comma.'"'.humanize($stat['name']).'"';
             $comma = ',';
        }

        $comma = '';
        foreach ($products_orders as $stat){
             $values .= $comma.humanize($stat['quantity']);
             $comma = ',';
        }

        $data['script'] .= '
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
            var index = 11;
            var counts = '.$countResult.'
            var ctx1 = document.getElementById("canvas1").getContext("2d");
            window.myBar = new Chart(ctx1).Bar(barChartData1, {
                responsive : true,
                barValueSpacing: 2
            });

            var ctx = document.getElementById("canvas2").getContext("2d");
            window.myBar = new Chart(ctx).Bar(barChartData, {
                responsive : true,
                barValueSpacing: 2
            });

        }';

        $this->show_view('reports/orders_report',$data);

   
    } 
   function get_supplier_orders($limit=0){
        $user_info = $this->aauth->get_user();
        $supplier = $this->user_model->get_supplier($user_info->user_link_id);
      
        $count = 20;
        $query_results = $this->app_model->get_all_supplier_orders($limit,$count,$supplier['id']);
        $page_title = 'All Orders';
        $name = '';

        /* Pagination Config */
        $config['num_links'] = 2;
        $config['base_url'] = '/dashboard/get_orders/';
        $config['total_rows'] = $query_results['num_rows'];
        $config['per_page'] = $count;

        $this->pagination->initialize($config); 

        $pagination_links = $this->pagination->create_links();
        
        $this->view($query_results['results'], $page_title, $pagination_links, $query_results['query']);
    }

    function get_slot_info($limit=0){
        $count = 20;
        $query_results = $this->auction_model->get_all_slot_info($limit,$count);
        $page_title = 'Slots';

        /* Pagination Config */
        $config['num_links'] = 2;
        $config['base_url'] = '/dashboard/get_slot_info/';
        $config['total_rows'] = $query_results['num_rows'];
        $config['per_page'] = $count;

        $this->pagination->initialize($config); 

        $pagination_links = $this->pagination->create_links();

        $this->view($query_results['results'], $page_title, $pagination_links,$query_results['query']);
    }

    function view($results, $page_title, $pagination_links,$query=''){
        $distributor = $this->input->post('distributor_id');

        $data['script'] = '';

        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $name = $this->order_model->getDistributorNameByID($distributor_id);
        
        $data['name'] = $name;

        if($page_title == 'All Orders'){

            $stores_orders = $this->spazapp_model->get_strores_orders();
            $products_orders = $this->spazapp_model->get_products_orders();
            $countResult = COUNT($products_orders) + 1;

            $labels = '';
            $values = '';
            $comma = '';

            foreach ($stores_orders as $stat){

                 $labels .= $comma.'"'.humanize($stat['company_name']).'"';
                 $comma = ',';
            }

            $comma = '';
            foreach ($stores_orders as $stat){
                 $values .= $comma.humanize($stat['orders']);
                 $comma = ',';
            }

            $data['script'] .= '
            var randomScalingFactor = function(){ return Math.round(Math.random()*100)};

            var barChartData1 = {
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
            ';

            $labels = '';
            $values = '';
            $comma = '';

            foreach ($products_orders as $stat){

                 $labels .= $comma.'"'.humanize($stat['name']).'"';
                 $comma = ',';
            }

            $comma = '';
            foreach ($products_orders as $stat){
                 $values .= $comma.humanize($stat['quantity']);
                 $comma = ',';
            }

            $data['script'] .= '
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
                var index = 11;
                var counts = '.$countResult.'
                var ctx1 = document.getElementById("canvas1").getContext("2d");
                window.myBar = new Chart(ctx1).Bar(barChartData1, {
                    responsive : true,
                    barValueSpacing: 2
                });

                var ctx = document.getElementById("canvas2").getContext("2d");
                window.myBar = new Chart(ctx).Bar(barChartData, {
                    responsive : true,
                    barValueSpacing: 2
                });


            }
            ';
        }

        if($page_title == 'Login Report'){

        $data['passwords'] = $this->event_model->get_reset_password_count();
        $data['registration'] = $this->event_model->get_registration_count();
        $data['failedRegistration'] = $this->event_model->get_failed_registration_count();
        $data['login'] = $this->event_model->get_login_count();
        $data['failedLogin'] = $failedLogCount = $this->event_model->get_failed_login_count();
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');

        }

        if($page_title == 'Sales Report'){
             
            $distributor = $this->input->post('distributor_id');
     
            $data['sales'] = $this->order_model->get_daily_sales_results($limit='0, 50', $count='',$distributor);
            
            $data['suppliers'] = $this->order_model->getSupplierStats($distributor);  
            $data['distributor'] = $this->order_model->getDistributorNames();          

        }


        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');

        $data['script'] .= '
        $(function() {
            $("#datepicker" ).datepicker( { dateFormat: "yy-mm-dd" });
            $("#datepicker1" ).datepicker( { dateFormat: "yy-mm-dd" });
        });
        ';

        $data['results'] = $results;
        $data['query'] = $query;
        $data['pagination_links'] = $pagination_links;
        $data['page_title'] = $page_title;

        $this->show_view('table_report', $data);
    }

    function download_csv(){
        $query = $_POST['query'];
        $this->load->helper('download');
        $filename = $this->event_model->csv_from_query($query);
        $data = file_get_contents("./assets/exports/".$filename); 
        force_download($filename, $data);
    }

    function download_csv_2($query){
        $this->load->helper('download');
        $filename = $this->event_model->csv_from_query($query);
        $data = file_get_contents("./assets/exports/".$filename); 
        force_download($filename, $data);
    }


    // Login Report Controller

    function login_report($limit=0){
        $count = 50;
        
        $query_results  = $this->event_model->get_login_statics_with_filters($limit, $count);
        $data['query_results'] = $query_results['results'];
        $data['page_title'] = 'Login Report';
        $data['query'] = $query_results['query'];

        $data['passwords'] = $this->event_model->get_reset_password_count();
        $data['registration'] = $this->event_model->get_registration_count();
        $data['failedRegistration'] = $this->event_model->get_failed_registration_count();
        $data['login'] = $this->event_model->get_login_count();
        $data['failedLogin'] = $failedLogCount = $this->event_model->get_failed_login_count();
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');

        $this->show_view('login_report', $data);
    }
    // Login Report Controller

    function rep_login_report($limit=0){
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        
        $count = 50;
        $query_results = $this->event_model->get_rep_login_statics_with_filters($limit, $count,$distributor_id);
        $page_title = 'Rep Login Report';

        /* Pagination Config */
        $config['num_links'] = 2;
        $config['base_url'] = '/dashboard/login_report/';
        $config['total_rows'] = $query_results['num_rows'];
        $config['per_page'] = $count;

        $this->pagination->initialize($config); 

        $pagination_links = $this->pagination->create_links();

        $this->view($query_results['results'], $page_title, $pagination_links, $query_results['query']);
    }


    function supplier_delivered_products($supplier_id,$distributor_id=''){
        
        $dataset='';
       
        $data['date_from'] = $_GET['date_from'];
        $data['date_to'] = $_GET['date_to'];
        
        $result=$this->order_model->get_supplier_delivered_products($supplier_id,$distributor_id,$data['date_from'],$data['date_to']);
        
        foreach ($result as $row) {
         $dataset.='dataSet.push(["'.$row['order_id'].'","'.$row['name'].'","'.$row['customer'].'","'.$row['status'].'",
         "'.$row['payment_type'].'","'.$row['quantity'].'","'.number_format($row['price'],2,'.',' ').'",
         "'.$row['createdate'].'"]);';
        }

        $columns="{ title: 'Order Id'},
                  { title: 'Product Id'},
                  { title: 'Customer'},
                  { title: 'Status'},
                  { title: 'Payment Type'},
                  { title: 'Quantity'},
                  { title: 'Price'},
                  { title: 'Createdate'}";

        $data['supplier']=$this->customer_model->get_supplier_by_id($supplier_id);
        $data['script'] = "".$this->data_table_script($dataset,$columns)."".$this->chart_script($labels='',$values='')."";
        $data['page_title']   = 'Delivered Products';
        $this->show_view('product_list', $data);
    }
    function sales_report($limit=0){
        
        $data['date_from'] = $this->input->post('date_from');
        $data['date_to']= $this->input->post('date_to');
        $distributor_id = $this->input->post('distributor_id');
        $data['suppliers']=$this->customer_model->get_suppliers();
        $data['distributors']= $this->order_model->getDistributorNames(); 
        $distributor= $this->product_model->getDistributorById($distributor_id); 

         if(!empty($distributor_id)){
            $data['distributor_id'] = $distributor['id'];
            $data['distributor_name'] = $distributor['company_name'];
        }else{
            $data['distributor_name']= 'All';
            $data['distributor_id'] = '';

        }

        $dataset ='';
        $sales= $this->order_model->getSupplierSalesSimple($distributor_id);
        foreach ($sales as $row) {
         $dataset.='dataSet.push([
                "'.$row['order_id'].'",
                 "'.$row['customer'].'",
                 "'.$row['status_name'].'",
                 "'.$row['payment_type'].'",
                 "'.number_format($row['total'],2,'.',' ').'",
                 "'.$row['createdate'].'"
            ]);';
        }
        $columns="{ title: 'Distro OID'},
                  { title: 'Customer'},
                  { title: 'Status'},
                  { title: 'Payment Type'},
                  { title: 'Total'},
                  { title: 'Createdate'}";

        $data['script'] = $this->data_table_script($dataset,$columns,5);
        $data['dataset'] = $this->get_supplier_sales($distributor_id);
        $data['page_title']   = 'Sales Report';
        
        $this->show_view('sales_report', $data);
    }

    function get_supplier_sales($distributor_id){
        $dataset='';
        $sales_results = $this->order_model->supplierSalesTotal($distributor_id);
        foreach ($sales_results as  $value) {
            $total_recieved=$this->order_model->getSupplierDeliveredOrdersTotal($value['supplier_id']);
            $dataset.='dataSet2.push([
                "'.$value['company_name'].'",
                "'.number_format($total_recieved['total'],2,'.',', ').'",
                "'.number_format($value['total'],2,'.',', ').'"
            ]);';
        }
        $script="var dataSet2 = [ ];
            ". $dataset."
            $(document).ready(function() {
            $('#supplier_sales').DataTable({
                    stateSave: true,
                    'bProcessing': true,
                    'bServerSide': false,
                    'bDeferRender': true,
                    'order': [[ 0,'desc' ]],
                    data:dataSet2,
                    columns: [
                        { title: 'Supplier'},
                        { title: 'Total Delivered Orders'},
                        { title: 'Total Placed Orders'}
                    ]
                });
            });";
        return $dataset;   
    }


    function order_details($order_id){
       
        $sales = $this->order_model->get_order_details($order_id); 
        $data['result']=$sales['result'];
        $data['query']=$sales['query']; 
        $dataset='';
        foreach ($data['result'] as $row) {
         $dataset.='dataSet.push(["'.$row['product'].'",
         "'.$row['customer'].'",
         "'.$row['supplier'].'",
         "'.$row['status'].'",
         "'.$row['quantity'].'",
         "'.number_format($row['price'],2,'.',' ').'",
         "'.number_format($row['price']*$row['quantity'],2,'.',' ').'",
         "'.$row['createdate'].'"]);';
        }
        $columns="  { title: 'Product'},
                    { title: 'Customer'},
                    { title: 'Supplier'},
                    { title: 'Status'},
                    { title: 'Quantity'},
                    { title: 'Price'},
                    { title: 'Total'},
                    { title: 'Createdate'}";

        $data['script'] = "".$this->data_table_script($dataset,$columns);
       
        $data['page_title']=$order_id. " - Order Details";
        $this->show_view("order_details",$data);

    }
    function supplier_sales_report($limit=0)
    {
        $user_info = $this->aauth->get_user();
        $supplier = $this->user_model->get_supplier($user_info->user_link_id);
        $count = 20;
        $query_results = $this->order_model->get_supp_daily_sales_results($limit, $count,$supplier['id']);
        
        $user_info = $this->aauth->get_user();
        $supplier['supp_dist'] = $this->user_model->get_supplier($user_info->user_link_id);
        $data['supp_dist'] = $this->user_model->get_dist_supplier($user_info->user_link_id);
        
    
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $distributor = $this->input->post('distributor');
    
        $data['sales_results']= $this->order_model->get_supp_dist_daily_sales_results($start_date,$end_date,$supplier['supp_dist']['id'],$distributor);
        $data['sales_stats']= $this->order_model->get_supp_dist_daily_sales_results_stats($start_date,$end_date,$supplier['supp_dist']['id'],$distributor);
        $data['query']= $this->order_model->get_supp_dist_daily_sales_results_export($start_date,$end_date,$supplier['supp_dist']['id'],$distributor);
        
        $data['page_title'] = $supplier['supp_dist']['company_name'].' Supplier Sales Report';
    
        $this->show_view('supplier/supplier_sales_report', $data);
    }

 function event_log_report(){
    
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $id = $this->input->post('next') or $id = $this->input->post('back');
       
        $data['event_query']= $this->event_model->get_event_log($start_date,$end_date,$id);
        $data['page_title']='Login - Report'; 
    
        $this->show_view('reports/event_log_report_view', $data);
}
   

    
function event_log_details($id){
        $data['page_title']='Login  - Report';
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $user_id= $this->input->post('user_id');
        $data['event_query_1']= $this->event_model->get_event_log_details_1($start_date,$end_date,$id);
        $data['event_query_2']= $this->event_model->get_event_log_details_2($start_date,$end_date,$id);
        $this->load->view('reports/report_table', $data);
}

 function airtime_report(){
    if (!$this->aauth->is_allowed('Management')){
        $this->event_model->track('error','permissions', 'Dashboard');
        redirect('/admin/permissions');
    }

    $dataset='';
    $data['user_group']='';
    $data['user'] ="";
    $data['status']='';

    $data['user_id'] =$this->input->post("user_id");
    $default_usergroup =$this->input->post("default_usergroup");
    $status_id =$this->input->post("status_id");

    if(isset($default_usergroup) && !empty($default_usergroup)){
        // $data['user_id']='';
        $data['user_group']=$this->user_model->get_user_group($default_usergroup);
    }

    if(isset($data['user_id']) && !empty($data['user_id'])) {
        $data['user'] = $this->user_model->get_user($data['user_id']);
    } 

    if(isset($status_id)){
        $data['status']=$this->airtime_model->get_airtime_statuses_by_id($status_id);
    }


    $data['airtime_report'] = $this->airtime_model->get_airtime_report($data['user_id'],$status_id,$default_usergroup);
    foreach($data['airtime_report'] as $r){
        $username=$r['name'];
        if(empty($r['name'])){
            $username='Null';
        }
        $status=substr($r['status'],7);
        if(empty($r['status'])){
            $status=$r['v_status'];
        }
        if(!empty($r['network'])){
             if($r['code']==107){            
                $refund_button="<a href='/dashboard/refund/".$r['id']."/".$r['sellvalue']."/' class='btn' style='color:#000'>"."Refund"."</a>";
             }else{
                $refund_button='';
             }
            $dataset.='dataSet.push([
                "'.$r['id'].'",
                "'.$username.'",
                "'.$r['default_usergroup'].'",
                "'.$r['orderno'].'",
                "'.$r['cellphone'].'",
                "'.$r['network'].'",
                "'.$r['amount'].'",
                "'.$r['sellvalue'].'",
                "'.$status.'",
                "'.$r['createdate'].'",
                "'.$refund_button.'"
                ]);';
        }else{
            $dataset='';
        } 
    }
    $columns='{ title: "Id"},
            { title: "User"},
            { title: "Default User Group"},
            { title: "Order Number"},
            { title: "Cellphone Credited"},
            { title: "Network"},
            { title: "Amount"},
            { title: "Sell Price"},
            { title: "Status" },
            { title: "Createdate"},
            { title: "Action"}';

     $data['script'] = $this->data_table_script($dataset,$columns);
     $data['airtime_statuses']=$this->airtime_model->get_airtime_statuses();
     $data['airtime_report_users'] = $this->airtime_model->get_airtime_report_users(); 
     $data['airtime_report_stats'] = $this->airtime_model->get_airtime_report_stats($data['user_id'], $status_id,$default_usergroup);
     
     $data['page_title'] = 'Airtime Report';

     $this->show_view('airt_report_view', $data);  
    }  

    public function refund($purchase_id, $sell_price){
        $this->financial_model->refund_airtime_purchase($purchase_id, $sell_price);
        $this->airtime_model->update_status($purchase_id, 0);
        $data['message']="<strong class='alert1'>"."Voucher has been succesfully refunded</strong>";
        redirect('/dashboard/airtime_report',$data);
    }

    public function survey_report(){
            $data['date_from'] = $this->input->post('date_from');
            $data['date_to'] = $this->input->post('date_to');
            $status_id = $this->input->post('status_id');
            $data["survey_stats"] = $this->survey_model->get_survey_stats($status_id,'survey_stats','', $data['date_from'], $data['date_to']);
            $data["user_stats"] = $this->survey_model->get_survey_stats($status_id,'user_stats','', $data['date_from'], $data['date_to']);
            $data["statuses"] = $this->survey_model->survey_tatuses();

            if(!empty($status_id)){
                $data["status"] = $this->task_model->getStatusById($status_id);
            }
            $dataset='';
            $data["survey_result"] = $this->survey_model->get_survey_list($status_id, $data['date_from'], $data['date_to']);
            foreach($data["survey_result"] as $row){ 

                $survey_id = $row->survey_id;
                $user_id = $row->user_id;
                $task_id = $row->task_id;
                $task_result_id = $row->task_result_id;

                $dataset.='dataSet.push([ 
                    "'.$row->id.'", 
                    "'. $row->user.'", 
                    "'.$row->task.'",
                    "'."<a href='/dashboard/survey_report_details/$survey_id/$user_id'>".$row->survey.'</a>",
                    "'.$row->status.'",
                    "'.$row->createdate.'"
                    ]);';
            }

            $columns='{ title: "ID" },
                    { title: "User Id" },
                    { title: "Task" },
                    { title: "Survey" },
                    { title: "Status" },
                    { title: "Createdate" }';

            $data['script'] = "".$this->data_table_script($dataset,$columns)."";
            $data['page_title']='Brand Connect - Survey ';
            $this->show_view('reports/survey_report_view', $data);
    } 


    public function photosnap_report($status_id=''){
            
            $data["photosnap_stats"] = $this->photosnap_model->get_photosnap_stats($status_id,'photosnap_stats');
            $data["user_stats"] = $this->photosnap_model->get_photosnap_stats($status_id,'user_stats');
            $data["statuses"] = $this->survey_model->survey_tatuses();

            if(!empty($status_id)){
                $data["status"] = $this->task_model->getStatusById($status_id);
            }
            $dataset='';
            $data["photosnap_result"] = $this->photosnap_model->get_photosnap_list($status_id);
            foreach($data["photosnap_result"] as $row){ 
                $dataset.='dataSet.push([ 
                    "'.$row->id.'", 
                    "'.$row->user.'", 
                    "'.$row->task.'",
                    "'.$row->photosnap.'</a>",
                    "'.$row->status.'",
                    "'.$row->createdate.'"
                    ]);';
            }

            $columns='{ title: "ID" },
                    { title: "User Id" },
                    { title: "Task" },
                    { title: "Photosnap" },
                    { title: "Status" },
                    { title: "Createdate" }';

            $data['script'] = "".$this->data_table_script($dataset,$columns)."";
            $data['page_title']='Brand Connect - Photosnap';
            $this->show_view('reports/photosnap_report', $data);
    } 


    public function supplier_survey_report(){
            $data['page_title']='Survey - Report';
            $data["survey_report"] = $this->survey_model->get_survey_list();

            $this->show_view('reports/survey_report_view', $data);
    }

    public function survey_report_details($survey_id,$user_id){
          

            $template = array(
                    'table_open' => '<table id="tableID" class="display">'
            );

            $this->table->set_template($template);

            $dataset='';
            $data["results"]    = $this->survey_model->get_survey_questions($survey_id,$user_id);
            
            foreach ($data['results'] as $key => $value) {

                $name = $value['name'];
                $Cellphone = $value['cellphone'];
                $createdate = $value['createdate']; 

                $dataset.='dataSet.push([
                    "'.$value['question_id'].'",
                    "'.$value['question_text'].'", 
                    "'.$this->survey_model->get_response($value['question_id'],$user_id).'",
                    "'.$value['createdate'].'"]);';
                

            }

            $columns='{ title: "ID" },
                    { title: "Question" },
                    { title: "Answer" },
                    { title: "Createdate" }';

            $data['script'] = "".$this->data_table_script($dataset,$columns)."";

            if(!empty($name)){
                $this->table->add_row('<strong>Participant</strong>', " : $name");
                $this->table->add_row('<strong>Cellphone</strong>', " : $Cellphone");
            }

            $data['participant_details']=$this->table->generate();

            $data["title"]=$this->survey_model->get_survey_by_survey_id($survey_id,$user_id);

            foreach($data["title"]->result() as $row){ 
                $survey_title=$row->title;
            } 

            $data['survey_title']=$survey_title; 

            $data['page_title'] ='Survey - Report';
            $this->show_view('survey_report_details_view', $data);
    }

    public function supplier_survey_report_details($survey_id){

        $user_info = $this->aauth->get_user();
        $supplier = $this->user_model->get_supplier($user_info->user_link_id);
        $data['page_title']     =   'Survey - Report';
        $data["survey_report_"] =   $this->survey_model->get_supplier_survey_questions($survey_id);
        $data["survey_report_by_id"] = $this->survey_model->get_survey_by_survey_id($survey_id);
        $this->show_view('survey_report_details_view', $data);
    }

    public function survey_report_answers($survey_id,$question_id){
        
        $data['page_title']='Survey - Report';
        
        $data["survey_report_by_id"]    = $this->survey_model->get_survey_by_survey_id($survey_id,$question_id);  
        $data["survey_report_answers"]  = $this->survey_model->get_survey_answers($survey_id,$question_id);  
        $data["survey_report_question"] = $this->survey_model->get_survey_question_by_id($question_id);
        $data["participants"]           = $this->survey_model->get_survey_participants($survey_id,$question_id); 
        $data["participants_stats"]     = $this->survey_model->get_survey_participants_stats($survey_id,$question_id);
        $data["query"]                  = $this->survey_model->get_survey_answers_csv($survey_id,$question_id);  
        $this->show_view('survey_report_answers_view', $data);
    }

    public function sales_report_()   
    {
        $data['page_title'] = 'Sales - Report';
       // $data['suppliers_sql']= $this->order_model->all_suppliers();
        $data['order_query']= $this->order_model->get_spark_store_sales();
        $this->show_view('sales_report_view', $data);
    }



    public function spark_sales_commission()   
    {
    	$data['page_title'] = 'Spark Sales Commission';
        $data['spark_sales'] = $this->order_model->get_spark_store_sales();
    
            $dataset='';
        foreach ($data['spark_sales'] as $key => $row) {
            $dataset.='dataSet.push([
            "'.$row['spark_id'].'",
            "'.$row['spark_name'].'",
            "'.$row['spark_cell'].'",
            "'.$row['sales'].'",
            "R '.round($row['total'],2).'",
            "R '.round($row['total']*0.05,2).'"
            ]);';
        }

        $columns="{title:'Spark ID'},
        {title:'Spark Name'},
        {title:'Cellphone'},
        {title:'Sales'},
        {title:'Total'},
        {title:'Commission'}";

        $data['script'] = "".$this->data_table_script($dataset,$columns);

        $this->show_view('reports/spark_sales_commission',$data);

    }

    // Customer Locations Google Maps Api
    
    public function customer_locations()
    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);
        $this->load->library('googlemaps');
        $data['page_title'] = 'Customer Locations';
        $data['customers'] = $this->customer_model->getCustomerDetails();

        $config['center'] = '-29.085214, 26.15957609999998';
        $config['zoom'] = '5';
        $this->googlemaps->initialize($config);

        foreach ($data['customers'] as $customer) 
        {
            $marker = array();
            $marker['position'] = $customer['location_lat']. ', '.$customer['location_long'];
            $marker['draggable'] = true;
            $marker['infowindow_content'] = '<img src="/assets/uploads/customer/'.$customer['store_picture'].'" alt="Store Picture" height="90" width="90"><br /><strong>'.preg_replace('/[^A-Za-z0-9\-]/', '',$customer['company_name']). '</strong>&nbsp; <a href="/dashboard/street_view?q='.$customer['id'].'"><i class="fa fa-male"></i></a> <br />'.preg_replace('/[^A-Za-z0-9\-]/', '',$customer['address']).'<br />'.preg_replace('/[^A-Za-z0-9\-]/', '',$customer['suburb']).'<br />'.preg_replace('/[^A-Za-z0-9\-]/', '',$customer['name']).'';
            $marker['animation'] = 'DROP';
            $marker['icon'] = '/assets/img/custom_map_icon.png';
            $this->googlemaps->add_marker($marker);
        }
        

        $data['map'] = $this->googlemaps->create_map();

        $data['regions'] = $this->spazapp_model->get_all_regions(); 
         
        $this->show_view('customer_locations', $data);
    }

     function get_custmer_location($customer_id){
        $results=$this->spazapp_model->get_customer_location($customer_id);

        echo json_encode($results);
     }

    public function street_view()
    {
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
 function order_trend_report()
    {
       
        $distributor = $this->input->post('distributor');
        $customer = $this->input->post('customer_id');
        $dates = $this->input->post('dates');
        $createdate ='';

        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');
        $data['sales_results']= $this->order_model->get_trend_orders($customer,$createdate,$data['date_from'],$data['date_to']);
        $data['sales_results_stats']= $this->order_model->get_trend_customers_stats($customer,$data['date_from'],$data['date_to']);
        $data['sales_results_stats2']= $this->order_model->get_trend_customers_stats2($customer,$data['date_from'],$data['date_to']);
        $data['customers']= $this->order_model->get_trend_customers($customer);
        $data['query']= $this->order_model->get_trend_order_results_export($customer,$createdate);
  
        $data['page_title'] = 'Order Trend Report';
       
        $this->show_view('reports/order_trend_report', $data); 
      
    
        
    }
function order_trend_report_()
{
        $createdate = $this->input->post('createdate');
    
        if(isset($createdate)){
           $createdate_ =  strtoupper(date("M Y", strtotime($this->input->post('createdate'))))." "; 
        }else{
           $createdate_ =''; 
        }
        
        
        $distributor = $this->input->post('distributor');
        $customer = $this->input->post('customer');
        $dates = $this->input->post('dates');
        
        $data['sales_results']= $this->order_model->get_trend_orders($customer, $createdate);
        $data['sales_results_stats']= $this->order_model->get_trend_customers_stats($customer);
        $data['customers']= $this->order_model->get_trend_customers($customer);
        $data['query']= $this->order_model->get_trend_order_results_export($customer,$createdate);
       
    
       
        $data['page_title'] = $createdate_.'Order Trend Report';
        $this->load->view('reports/report_table', $data);
    
       
        
}

function sms_report(){   
    if (!$this->aauth->is_allowed('Management')){
        $this->event_model->track('error','permissions', 'Dashboard');
        redirect('/admin/permissions');
    }

    $comma='';
    $labels='';
    $values='';
    $dataset='';
    $customer_id=$this->input->post('customer_id');
    $data['date_from'] = $this->session->userdata('dashboard_date_from');
    $data['date_to'] = $this->session->userdata('dashboard_date_to');

    $sms_log= $this->event_model->getCustomerSmsLog( $data['date_from'], $data['date_to'],$customer_id);
    $myModalData='';
    foreach($sms_log as $row){  
        $user=$this->event_model->getUserName(substr($row['to'],3,10));
        $id=$row['id'];

        $myModalData.="<div id='myModal$id' class='modal hide fade' tabindex='-1' role='dialog' aria-labelledby='myModalLabel' aria-hidden='true'>
            <div id='printableArea'>
                <b>User</b>: ".$user['name']."<hr/>".$row['message']."<hr/> 
                <b>Cellphone No</b> : ".$row['to']."<br/>
                <b>Createdate</b> : ".$row['createdate']."
            </div>
        </div>";

        $message="<a href='#myModal$id' role='button' data-toggle='modal'><a href='#myModal$id' role='button' data-toggle='modal'><div class=''>".substr($row['message'],0,50)."...".'</div></a>';   

        $dataset.='dataSet.push([
        "'.$row['id'].'",
        "'.$user['name'].'",
        "'.$row['to'].'",
        "'.$message.'",
        "'.$row['createdate'].'"
        ]);';
        
    }

    $data['myModalData']=$myModalData;

    $columns='{ title: "Id" },{ title: "User" },{ title: "To" },{ title: "Message" },{ title: "Createdate" }';

    $sms_stats= $this->event_model->getDailySmsLogStats($data['date_from'],$data['date_to'],$customer_id);
    foreach ($sms_stats as $r) {
        $labels .= $comma.'"'.humanize($r['createdate']).'"';
        $values .= $comma.$r['sms_count'];
        $comma = ',';  
    }

    $data['customer_sms_stats']=$this->event_model->getCustomerSmsLogStats($data['date_from'],$data['date_to']);

    $data['script'] = "".$this->data_table_script($dataset,$columns)."".$this->chart_script($labels,$values)."";
    $data['customers'] = $this->spazapp_model->get_all_customers();
    $data['customer_info'] = $this->spazapp_model->get_customer($customer_id);
    $data['page_title'] = 'SMS Report';
    $this->show_view('reports/sms_report', $data);
        
}

function email_report(){
    $comma='';
    $values='';
    $labels='';
    $dataset='';
    $name='';
    $customer   = $this->input->post("customer");
    $data['date_from'] = $this->session->userdata('dashboard_date_from');
    $data['date_to'] = $this->session->userdata('dashboard_date_to');
    $data['customers_results']= $this->event_model->get_email_log_customers($customer,$data['date_from'],$data['date_to']);
    $data['customer_name_result']= $this->event_model->get_customer_by_id($customer);   

    $data['email_results_1']= $this->event_model->get_email_log($customer,$data['date_from'],$data['date_to']);   
    foreach ($data['email_results_1'] as $row) {
        
        if(isset($row['customer'])){
            $name=$row['customer'];
        }

        if(isset($row['distributor'])){
            $name=$row['distributor'];
        }

     $dataset.='dataSet.push([
                    "'.$row['id'].'",
                    "'.$row['to'].'",
                    "'.$name.'",
                    "'.str_replace('_', "  ",$row['template']).'",
                    "'.$row['createdate'].'"
                    ]);';
    }

    $columns="{title:'Id'},
            {title:'To'},
            {title:'Customer'},
            {title:'Template'},
            {title:'Createdate'}";

    $data['email_results_stats']= $this->event_model->get_email_log_stats($customer,$data['date_from'],$data['date_to']); 
    foreach ($data['email_results_stats'] as $row) {                                                           
        $labels .= $comma.'"'.humanize(str_replace('_', "  ",$row['template'])).'"';
        $values .= $comma.$row['mail_count'];
        $comma = ',';

    }
    
    $data['daily_email']=$this->event_model->getDailyEmailStats($customer,$data['date_from'],$data['date_to']);

    $data['page_title'] = 'Email Report';
    $data['script'] = "".$this->data_table_script($dataset,$columns)."".$this->chart_script($labels,$values)."";
    $this->show_view('reports/email_report', $data);
   
   
}

 function data_table_script($dataset, $columns, $order_index=0){
        $data_table = $this->javascript_library->data_table_script($dataset, $columns, $order_index);
        return $data_table;
 }

function chart_script($labels,$values){
    $chart="var randomScalingFactor = function(){ return Math.round(Math.random()*100)};
            var barChartData = {
            labels : [".$labels."],
            datasets : [
            {
                fillColor : '#c8ba9e',
                strokeColor : '#c8ba9e',
                highlightFill : '#bca789',
                highlightStroke : '#b4a085',
                data : [".$values."]
                    }
                ]
            }
            window.onload = function(){
                var ctx = document.getElementById('canvas2').getContext('2d');
                window.myBar = new Chart(ctx).Bar(barChartData,{
                    responsive : true
                });
            }";
    return $chart;

}

function email_report_details(){

    $customer   = $this->input->post("customer");
    $from       = $this->input->post('from');
    $to         = $this->input->post('to');
    
    $data['page_title'] = 'Email Report';
    
    $data['email_results_1']= $this->event_model->get_email_log($customer);   
    $data['email_results_stats']= $this->event_model->get_email_log_stats($customer);   
    $data['customers_results']= $this->event_model->get_email_log_customers($customer);   
    $data['customer_name_result']= $this->event_model->get_customer_by_id($customer);   
    $data['query']= $this->event_model->get_email_log_export($customer); 
    
   
    $this->load->view('reports/email_report_details', $data);
}

function news_report(){
    $data['date_from'] = $this->session->userdata('dashboard_date_from');
    $data['date_to'] = $this->session->userdata('dashboard_date_to');
    $data['page_title'] = 'News Report';
    
    $data['news_results_1']= $this->news_model->get_all_news($data['date_from'],$data['date_to']);
    $data['news_results_stats']= $this->news_model->get_all_news_stats($data['date_from'],$data['date_to']);
    $data['query']= $this->news_model->news_csv($data['date_from'],$data['date_to']);
    
    $this->show_view('reports/news_report', $data);
    
}

function registration_report(){

        $data['date_from'] = $this->input->post('date_from');
        $data['date_to'] = $this->input->post('date_to');
        $data['trader_id'] = $this->input->post('trader_id');

        $labels1 = '';
        $values1 = '';
        $comma = '';
        $highest='';
        $count=1;
        $color='#f89548';
        $barColors='';
        $barColors='myObjBar.datasets[0].bars[0].fillColor = "#f89548";';
        $registration = $this->event_model->getMonthRegistrationsCount($trader_id='');
        foreach ($registration as $stat){
             $color = '#cabda2';
             $labels1 .= $comma.'"'.humanize($stat['last_name']." (".$stat['cellphone'].")").'"';
             $values1 .= $comma.humanize($stat['registrations']);
             $barColors.='myObjBar.datasets[0].bars['.$count.'].fillColor = "'.$color.'";';
             $comma = ',';
             $count++;
        }
            
    
        $dataset ='';
        $allRegistrations = $this->event_model->getMonthRegistrations();
        foreach($allRegistrations as $r){

            if(!empty( $r['trader_first_name']) || !empty( $r['trader_last_name'])){
                    $trader = $r['trader_first_name']." ".$r['trader_last_name'];
            }else{
                if($r['trader_id']==0){
                    $trader=''; 
                }else{
                    $trader = $r['trader_id'];
                }
            }
            
            $dataset.=' dataSet.push(["'.$r['event_log_id'].'",
            "'.$r['company_name'].'",
            "'.$r['username'].'",
            "'."<a href='/dashboard/trader_details/".$r['trader_id']."' role='button' data-toggle='modal'>".$trader."</a>".'",
            "'.$r['province_name'].'",
            "'.$r['region'].'",
            "'.$r['createdate'].'"]);';
        }

        $columns='{ title: "Id" },
                { title: "Company Name" },
                { title: "Cellphone" },
                { title: "Trader Id" },
                { title: "Province" },
                { title: "Region" },
                { title: "Createdate" }';

        $general_reg =  $this->event_model->getMonthRegistrations('general_reg'); 

        $data['script'] = "".$this->data_table_script($dataset,$columns)."".
        $this->colorful_bar_chart('"'."General Registrations".'"'.$comma.$labels1, count($general_reg).$comma.$values1, $barColors);
        $comma=',';

        $data['registration2']=$this->event_model->get_registration_stats_2($trader_id);
        $data['page_title'] = "Registration Report";
        $this->show_view('registration_report', $data);
    }

    function colorful_bar_chart($labels,$values, $barColors){
        $data=' var barChartData = {
            labels: ['.$labels.'],
            datasets: [
                {
                fillColor: "rgba(220,220,220,0.5)", 
                strokeColor: "rgba(220,220,220,0.8)", 
                highlightFill: "rgba(220,220,220,0.75)",
                highlightStroke: "rgba(220,220,220,1)",
                data: ['.$values.']
                }
            ]
        };
        var options = {
            scaleBeginAtZero: false,
            responsive: true,
            scaleStartValue : -50 
        };
        window.onload = function(){
            var ctx = document.getElementById("canvas2").getContext("2d");
            window.myObjBar = new Chart(ctx).Bar(barChartData,options, {
                  responsive : true
            });

            //nuevos colores
           '.$barColors.'
            myObjBar.update();
        }';

        return $data;
    }

    function trader_details($trader_id){
        error_reporting(0);
        
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');

        $dataset='';
        $allRegistrations = $this->event_model->getMonthRegistrations('',$trader_id,$data['date_from'],$data['date_to']);
       
        foreach($allRegistrations as $r){
          $dataset.=' dataSet.push(["'.$r['event_log_id'].'",
            "'.$r['company_name'].'",
            "'.$r['username'].'",
            "'.$r['trader_first_name'].'",
            "'.$r['province_name'].'",
            "'.$r['region'].'",
            "'.$r['createdate'].'"]);';
        }

        $columns='{ title: "Id" },
                { title: "Company Name" },
                { title: "Cellphone" },
                { title: "Trader" },
                { title: "Province" },
                { title: "Region" },
                { title: "Createdate" }';


        $data['registration_count']=$allRegistrations;  
           
        $user=$this->trader_model->get_user_from_trader_id($trader_id);  
        $data['last_seen']=$this->event_model->get_last_activity($user['id']);     
        $data['script'] = "".$this->data_table_script($dataset,$columns);
        $data['trader']=$this->trader_model->getTraderDetailsById($trader_id);
        $this->load->library('googlemaps');
      
        $trader_logs=$this->event_model->get_trader_locations($user['id'],$data['date_from'],$data['date_to']);
        foreach ($trader_logs as $key => $trader) {
       
            
            $config['center'] = $trader['long']. ', '.$trader['lat'];
            $config['zoom'] = 'auto';

            $this->googlemaps->initialize($config);

            $marker = array();
            $marker['position'] = $trader['long']. ', '.$trader['lat'];
            $marker['draggable'] = true;
            $marker['infowindow_content'] = "<strong>".$data['trader']->first_name." ".$data['trader']->last_name."</strong>".'<br/> Cellphone : '.$data['trader']->cellphone.'<br />'.'Createdate : '.$trader['createdate'];
            $marker['animation'] = 'DROP';
            $marker['icon'] = '/assets/img/custom_map_icon.png';
            $this->googlemaps->add_marker($marker);

        }

        $last_location=$this->event_model->get_current_location($user['id']);
        
        $data['map'] = $this->googlemaps->create_map();
        $data['current_location']=$this->getcurrentlocation($last_location['lat'],$last_location['long']);
        $data['page_title']="Spark Detailed Registrations Report";
        $this->show_view('trader_details', $data);
    }

    function getcurrentlocation($lng,$lat){
             $url = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='.trim($lat).','.trim($lng).'&sensor=false';
           
             $json = @file_get_contents($url);
             $data=json_decode($json);
             if(isset($data->status) && !empty($data->status)){
                $status = $data->status;
                 if($status=="OK")
                 {
                   return $data->results[0]->formatted_address;
                 }
                 else
                 {
                   return "Not found";
                 }
             }else
                 {
                   return "Not found";
                 }
             
            
    }

    public function mobile_devices()
    {

        $username = $this->input->post("username");
        $data['user_id'] = $username;

        $phone = $this->event_model->getDeviceChartInfo($username);

        $labels = '';
        $values = '';
        $comma = '';

        foreach ($phone as $stat){

             $labels .= $comma.'"'.humanize($stat['model']).'"';
             $comma = ',';
        }

        $comma = '';
        foreach ($phone as $stat){
             $values .= $comma.humanize($stat['total_devices']);
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
        }
        '; 

        $data["device"] = $this->event_model->getDevices($username); 
        $data["user"] = $this->event_model->selectUser(); 
        $data["page_title"] = "Mobile Devices Stats";

        $this->show_view('mobile_devices', $data);    
    }

    public function delivery_report()
    {
        $this->load->library('googlemaps');

        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');
        $data['page_title'] = "Delivery List"; 

        $data['delivery'] = $this->delivery_model->getAllDeliveries();

        $data['script'] = '
            $(function() {
                $("#datepicker" ).datepicker( { dateFormat: "yy-mm-dd" });
                $("#datepicker1" ).datepicker( { dateFormat: "yy-mm-dd" });
            });
        '; 

        $config['center'] = '-29.8534, 31.0300';
        $config['zoom'] = 'auto';
        $this->googlemaps->initialize($config);

        $data['map'] = $this->googlemaps->create_map();

        $this->show_view('delivery_report', $data);      
    }

    public function delivery_info($delivery_id)
    {

        $stop = false;
        $this->load->library('googlemaps');

        $data['page_title'] = "Delivery Information";

        $data['driver'] = array();

        $data['delivery'] = $this->delivery_model->getSingleDelivery($delivery_id);
        
        $data['driver']['name'] = $data['delivery']['name'];
        $data['driver']['licence_plate'] = $data['delivery']['licence_plate'];
        $data['driver']['delivery_completed'] = $data['delivery']['date_closed'];

        $distributor_id = $data['delivery']['distributor_id'];

        $orders = $this->delivery_model->get_full_delivery_orders($delivery_id);

        // Map One
        $config['center'] = '-29.7897, 31.0206';
        $config['zoom'] = 9;
        $config['map_name'] = 'map_one';
        $config['map_div_id'] = 'map_canvas_one';
        $this->googlemaps->initialize($config);

        $count = 0;
        foreach ($orders as $key => $order) {
            $count++;
            $driver_locations = $this->delivery_model->get_driver_locations($order['driver'], str_replace(' 00:00:00', '', $order['date']));
            if($driver_locations){
                foreach ($driver_locations as $key => $d_location) {

                    $marker = array();
                    $marker['position'] = $d_location['long'] . ', '.$d_location['lat'];
                    $marker['draggable'] = false;
                    $marker['infowindow_content'] = $d_location['createdate'].' Driver Heartbeat';
                    $marker['animation'] = 'DROP';
                    $marker['icon'] = 'http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld='.$key.'|FF0000|000000'; //this needs to be more generic
                    $this->googlemaps->add_marker($marker);

                }

                $data['driver']['a'][0]['action'] = 'Day started';
                $data['driver']['a'][0]['location'] = $data['delivery']['company_name'];
                $data['driver']['a'][0]['order_id'] = 'none';
                $data['driver']['a'][0]['unpacking_time'] = 'none';
                $data['driver']['a'][0]['timestamp'] = $driver_locations[0]['createdate'];
            }
            else
            {

                $data['driver']['a'][0]['action'] = 'Delivery NOT actioned yet';
                $data['driver']['a'][0]['location'] = 'none';
                $data['driver']['a'][0]['order_id'] = 'none';
                $data['driver']['a'][0]['unpacking_time'] = 'none';
                $data['driver']['a'][0]['timestamp'] = '0000-00-00 00:00:00';
                $stop = true;
            }
                //this is the customers shop location
                $marker = array();
                $marker['position'] = $order['location_lat']. ', '.$order['location_long'];
                $marker['draggable'] = false;
                $marker['infowindow_content'] = '<img src="/assets/uploads/customer/'.$order['store_picture'].'" alt="Store Picture" height="90" width="90"><br /><strong>'.$order['company_name']. '</strong>&nbsp; <a href="/dashboard/street_view?q='.$order['customer_id'].'"><i class="fa fa-male"></i></a> <br />'.$order['address'].'<br />'.$order['suburb'].'<br />'.$order['company_name'].'';
                $marker['animation'] = 'DROP';
                $marker['icon'] = '/assets/img/custom_map_icon.png';
                $this->googlemaps->add_marker($marker);

                if($order['nav_started']){

                    $data['driver']['a'][$count]['action'] = 'Nav started';
                    $data['driver']['a'][$count]['location'] = 'Heading to '. $order['company_name'] . ' ' . $order['suburb'];
                    $data['driver']['a'][$count]['order_id'] = $order['dist_order_id'];
                    $data['driver']['a'][$count]['unpacking_time'] = $order['time_taken'];
                    $data['driver']['a'][$count]['timestamp'] = $order['nav_started'];

                    $count++;
                }

                if($order['delivery_date'] != '0000-00-00 00:00:00'){

                    $data['driver']['a'][$count]['action'] = 'Completed Unpacking';
                    $data['driver']['a'][$count]['location'] = $order['company_name'] . ' ' . $order['suburb'];
                    $data['driver']['a'][$count]['order_id'] = $order['dist_order_id'];
                    $data['driver']['a'][$count]['unpacking_time'] = $order['time_taken'];
                    $data['driver']['a'][$count]['timestamp'] = $order['delivery_date'];
                }

                //this was the location that was sent when the order was completed (should be close to the shop)
                $marker = array();
                $marker['position'] = $order['lat']. ', '.$order['long'];
                $marker['draggable'] = false;
                $marker['infowindow_content'] = 'driver approved orders delivry'.$order['dist_order_id'];
                $marker['animation'] = 'DROP';
                //$marker['icon'] = '/assets/img/custom_map_icon.png';
                $this->googlemaps->add_marker($marker);

        }
        if(!$stop){
        $key = count($data['driver']['a'])+1;
            $data['driver']['a'][$key]['action'] = 'Day Ended';
            $data['driver']['a'][$key]['location'] = $data['delivery']['company_name'];
            $data['driver']['a'][$key]['order_id'] = 'none';
            $data['driver']['a'][$key]['unpacking_time'] = 'none';
            $data['driver']['a'][$key]['timestamp'] = $driver_locations[count($driver_locations)-1]['createdate'];
        }

        //loop through orders here and get the 2 longs and lats it has then add markers.
        // go fetch the other markers from the location_log for today and this user also.
        //get user from driver in the delivery table.

        $data['map_one'] = $this->googlemaps->create_map();
        $this->show_view('delivery_view', $data);
    }
    
    function insurance_sales_report(){
        
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');
        
        $death_certificate ='';
        
        $data['page_title'] = 'Insurance Sales Report';

        $data['sales_results']= $this->insurance_model->get_all_insurance_sales($data['date_from'], $data['date_to'], $death_certificate);
        $data['sales_results_stats']= $this->insurance_model->get_all_insurance_sales_stats($data['date_from'], $data['date_to'], $death_certificate);
        $data['funeral_stats']= $this->insurance_model->get_funeral_product_stats($data['date_from'], $data['date_to'], $death_certificate);
        

        $this->show_view('insurance/insurance_sales', $data);
    
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

        $data['page_title']="Claims";
        $this->show_view('insurance/claims', $data);
    
    }
    
    function product_sales_report($distributor_id='', $top_limit=''){
        
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');

        $status  = $this->input->post('status');
        $distributor_id  = $this->input->post('distributor_id');
        
        if(empty($top_limit)){
            $top_limit = "50";
        }
        
        $data['top_limit'] = $top_limit;
        $data['sales_results'] = $this->product_model->get_top_product_sales($distributor_id, $top_limit, $data['date_from'], $data['date_to'], $status ); 
        $data['distributors'] = $this->order_model->getDistributorNames(); 
        $data["statuses"]=$this->survey_model->get_gbl_statuses();
        $data['distributor'] = $this->product_model->getDistributor($distributor_id);

        $status_=$this->survey_model->getGblStatusById($status);
        if(!empty($status)){
            $data['status_name']=$status_['name'];  
            $data['status_id']=$status_['id'];  
        }

        $data['statuses'] = $this->product_model->get_product_sales_status();
        $data['page_title'] = "Top 50 Products Sales Report";
        $this->show_view('product_sales', $data);  
              
    }

    function customer_sales_report($distributor_id='', $top_limit=''){
        
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');
        $distributor_id  = $this->input->post('distributor_id');
 
        $data['page_title'] = "Customer Sales Report";
        
        if(empty($top_limit)){
            $top_limit = "50";
        }
        
        $data['top_limit'] = $top_limit;
        $sales_results = $this->product_model->get_top_customer_sales($distributor_id,$top_limit,$data['date_from'],$data['date_to'] ); 
        $data['sales_results'] = $sales_results['results'];
        $data['query']= $sales_results['query'];

        $data['distributors'] = $this->order_model->getDistributorNames(); 
      
        $data['distributor'] = $this->product_model->getDistributor($distributor_id);
        
        $this->show_view('customer_sales', $data);  
        
        
    }

    function daily_order_report(){

        $data['date_from']=$this->input->post('date_from');
        $data['date_to'] =$this->input->post('date_to');
       
        $data['distributors']=$this->order_model->getDistributorNames(); 
        $data["statuses"]=$this->survey_model->get_gbl_statuses();
        $data['order_results']= $this->order_model->getDailyOrders($data['date_from'],$data['date_to'],0);
        $data['order_stats']=$this->order_model->getDailyOrders($data['date_from'],$data['date_to'],30)['data'];
        $data['monthly_orders']=$this->order_model->monthlyOrderStats($distributor_id=''); 
         
        $data['page_title']="Order Report";
        $this->show_view('orders_report', $data);      
    }

    function daily_order_json(){
        $data['date_from']=$this->input->post('date_from');
        $data['date_to'] =$this->input->post('date_to');
        $order_results= $this->order_model->getDailyOrders($data['date_from'],$data['date_to'],0);
        echo json_encode($order_results);
    }

    function monthly_orders(){
      
        $data['page_title']='Test';

        $this->show_view('testing/testing',$data);
    }
    function monthly_orders_data($createdate=''){
        $monthly_orders=$this->order_model->monthlyOrderStats($distributor_id='',$createdate); 
        $return = array();
        foreach ($monthly_orders as $data) {
            $return[] = $data;
        }
      
        echo json_encode($return);
        
    }

    function daily_order_detail($createdate){
        $data['createdate'] = $createdate;
        $data['result'] = $this->order_model->getOrdersTotalByDate($createdate);
        $dataset='';
        foreach ($data['result'] as $r) {
            if(!empty($r['location_long']) && !empty($r['location_lat'])){
                $location=$this->getcurrentlocation($r['location_long'],$r['location_lat']);
            }else{
                $location='';
            }
            
             $dataset.='dataSet.push([
             "'.$r['order_id'].'",
             "'.$r['customer'].'",
             "'.$r['cellphone'].'",
             "'.$r['address'].'",
             "'.$location.'",
             "'.$r['status'].'",
             "'.$r['total'].'",
             "'.$r['createdate'].'"
             ]);';
                    
            }

        $columns='{ title: "Order id" },
            { title: "Customer" },
            { title: "Cellphone" },
            { title: "Address" },
            { title: "Location" },
            { title: "Status" },
            { title: "Total" },
            { title: "Createdate" }';

        $data['script'] = "".$this->data_table_script($dataset,$columns);
        $data['page_title']="Order Report : ".$createdate;
        $this->show_view('orders_report_details', $data);  
    }
    public function fridge_locations()

    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        $this->load->library('googlemaps');
        $data['page_title'] = 'Fridge Locations';
        $data['fidges'] =  $this->fridge_model->get_fridges_locations();
        $config['center'] = '-29.8590, 31.0189';
        $config['zoom'] = '1';
        $this->googlemaps->initialize($config);

        foreach ( $data['fidges'] as $fridge) 
        {
            //get status
            $max_temp = $fridge['expected_temp']+$fridge['tolerance'];
            $min_temp = $fridge['expected_temp']-$fridge['tolerance'];
            $off_temp = $fridge['considered_off'];
            $status['icon'] = 'brr_map_icon.png';
            $status['message'] = 'Optimal';

            if($fridge['temp'] > $max_temp){
                $status['icon'] = 'brr_warning_map_icon.png';
                $status['message'] = 'Too Hot';
            }

            if($fridge['temp'] < $min_temp){
                $status['icon'] = 'brr_warning_map_icon.png';
                $status['message'] = 'Too Cold';
            }

            if($fridge['temp'] > $off_temp){
                $status['icon'] = 'brr_off_map_icon.png';
                $status['message'] = 'Fridge is Off';
            }

            $marker = array();
            $marker['position'] = $fridge['long']. ', '.$fridge['lat'];
            $marker['draggable'] = true;
            $marker['infowindow_content'] = '<strong>'.$fridge['brand']. ' - '. $fridge['fridge_unit_code'] .'</strong>&nbsp; <a href="/dashboard/fridge_street_view/'.$fridge['id'].'"><i class="fa fa-male">View Street</i></a>'.
            '<br />Status: '.$status['message'].
            '<br />Location: '.$fridge['location_name'].
            '<br />Temp: '.$fridge['temp'].'&deg;'.
            '<br />Expected Temp Range: '.$max_temp.'&deg;-'.$min_temp.'&deg;'.
            '<br />Street: '.$fridge['street'].
            '<br />Region: '.$fridge['region'].
            '<br />Province: '.$fridge['province'];
            $marker['animation'] = 'DROP';
            $marker['icon'] = '/assets/img/' . $status['icon'];
            $this->googlemaps->add_marker($marker);
        }
        

        $data['map'] = $this->googlemaps->create_map();


        $this->show_view('fridge_locations', $data);
    }


    public function fridge_log_report(){

        $province_id=$this->input->post('province');
        $brand_id=$this->input->post('brand');
        $fridge_type_id=$this->input->post('fridge_type');
        $data['unit_code']=$this->input->post('fridge_uinit_code');

       
        $province = $this->spazapp_model->get_province();
        $brand = $this->spazapp_model->get_brands();
        $fridge_type = $this->fridge_model->get_fridge_type();

        $province_option='';
        foreach ($province as $row) {
            $province_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $brand_option='';
        foreach ($brand as $row) {
            $brand_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $brand_option='';
        foreach ($brand as $row) {
            $brand_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $fridge_type_option='';
        foreach ($fridge_type as $row) {
            $fridge_type_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $data['province_option']=$province_option;
        $data['brand_option']=$brand_option;
        $data['fridge_type_option']=$fridge_type_option;

        $fridges = $this->fridge_model->get_fridges($province_id,$brand_id,$fridge_type_id,$data['unit_code']);

        $result=$fridges['result'];
        $data['query']=$fridges['query'];

 
        $dataset ='';

        
    foreach ($result as $fr) {
         $dataset.='dataSet.push([
         "'."<a href='/dashboard/fridge_details/".$fr['id']."'><font color='#f89520'>".$fr['fridge_unit_code']."</font></a>".'",
         "'.$fr['fridge_type'].'",
         "'.$fr['location_name'].'",
         "'.$fr['temp'].'",
         "'.$fr['province'].'",
         "'.$fr['region'].'",
         "'.$fr['createdate'].'",
         "'."<li class='dropdown'><a href='#' class='dropdown-toggle btn' data-toggle='dropdown' style='color:black'>Actions <b class='caret'></b></a><ul class='dropdown-menu' role='menu'><li><a href='/dashboard/fridge_locations_history/".$fr['id']."'>Locations History</a></li><li><a href='/dashboard/daily_monthly_temperature/".$fr['id']."'>Daily Temperatures</a></li></ul></li></ul>".'"]);';
        }

     
        $data['script'] ="var dataSet = [ ];
        ". $dataset."
        $(document).ready(function() {
            $('#report_table').DataTable( {
                data:dataSet,
                columns: [
                    { title: 'fridge Unit Code' },
                    { title: 'Fridge type' },
                    { title: 'Location Name' },
                    { title: 'Temperature' },
                    { title: 'Province' },
                    { title: 'Region' },
                    { title: 'Createdate' },
                    { title: 'Actions' }
                ],
                 dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
            } );
        } );

      ";

        $data['page_title']="Fridge Report";

        $data['province'] = $this->spazapp_model->get_province_by_id($province_id);
        $data['brand'] = $this->spazapp_model->get_brands_by_id($brand_id);
        $data['fridge_type'] = $this->fridge_model->get_fridge_type_by_id($fridge_type_id);

        $this->show_view('frigde_log_report', $data); 

    }



 public function fridge_details($fridge_id)

    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        $this->load->library('googlemaps');
         
        $fridge=  $this->fridge_model->get_current_location($fridge_id);
        $data['fridge']=$fridge;

        //$config['center'] = '-29.8590, 31.0189';
        $config['center'] = $fridge['long']. ', '.$fridge['lat'];
        $config['zoom'] = 9;
        $this->googlemaps->initialize($config);

       
        //get status
        $max_temp = $fridge['expected_temp']+$fridge['tolerance'];
        $min_temp = $fridge['expected_temp']-$fridge['tolerance'];
        $off_temp = $fridge['considered_off'];

        $status['icon'] = 'brr_map_icon.png';
        $status['message'] = 'Optimal';
     
        if($fridge['temp'] > $max_temp){
            $status['icon'] = 'brr_warning_map_icon.png';
            $status['message'] = 'Too Hot';
        }

        if($fridge['temp'] < $min_temp){
            $status['icon'] = 'brr_warning_map_icon.png';
            $status['message'] = 'Too Cold';
        }

        if($fridge['temp'] > $off_temp){
            $status['icon'] = 'brr_off_map_icon.png';
            $status['message'] = 'Fridge is Off';
        }
        
        $data['status']=$status['message'];
        $marker = array();
        $marker['position'] = $fridge['long']. ', '.$fridge['lat'];
        $marker['draggable'] = true;
     
        $marker['infowindow_content'] = '<strong>'.$fridge['brand']. ' - '. $fridge['fridge_unit_code'] .'</strong>&nbsp; <a href="/dashboard/fridge_current_street_view/'.$fridge['id'].'"><i class="fa fa-male">Street View</i></a>'.
        '<br />Status: '.$status['message'].
        '<br />Location: '.$fridge['location_name'].
        '<br />Temp: '.$fridge['temp'].'&deg;'.
        '<br />Expected Temp Range: '.$max_temp.'&deg;-'.$min_temp.'&deg;'.
        '<br />Street: '.$fridge['street'].
        '<br />Region: '.$fridge['region'].
        '<br />Province: '.$fridge['province'].
        '<br />Createdate: '.$fridge['createdate'];
        $marker['animation'] = 'DROP';
        $marker['icon'] = '/assets/img/' . $status['icon'];
        $this->googlemaps->add_marker($marker);
        $data['map'] = $this->googlemaps->create_map();

   
       
        $comma ='';
        $values ='';
        $labels ='';
        $count = 0;
        $barColors='';

        // This is for Daily Temperature Chart
        $temp_result = $this->fridge_model->get_daily_temperatures($fridge_id);

        foreach ($temp_result as $row) {   

            $max_temp = $row['expected_temp']+$row['tolerance'];
            $min_temp = $row['expected_temp']-$row['tolerance'];
            $off_temp = $row['considered_off'];

            $brand=$fridge['brand'];
        
            $stat = 'Optimal';
            $color = '#27ade5';

            if($row['fridge_temp'] > $max_temp){
                $color = '#d49c2d';
                $stat = 'Too Hot';
            }

            if($row['fridge_temp'] < $min_temp){
                $color = '#d49c2d';
                $stat = 'Too Cold';
            }

            if($row['fridge_temp'] > $off_temp){
                $color = '#d22c2c';
                $stat = 'Fridge is Off';
            }

            $labels .= $comma.'"'.humanize($row['dates']." [".$stat."]").'"';
            $values .= $comma.$row['fridge_temp'];
            $barColors.='myObjBar.datasets[0].bars['.$count.'].fillColor = "'.$color.'";';
            $comma = ',';

            $count++;
        }
      
       //Getting list of all temperature logs
        $logs = $this->fridge_model->get_fridge_logs($fridge_id);

        $log_result=$logs['result'];
        $data['query']=$logs['query'];
        $dataset ='';

        foreach ($log_result as $logs) {
         $dataset.='dataSet.push([
         "'.$logs['id'].'",
         "'.$logs['fridge_unit_code'].'",
         "'.$logs['fridge_type'].'",
         "'.$logs['location_name'].'",
         "'.$logs['temperature'].'",
         "'.$logs['province'].'",
         "'.$logs['region'].'",
         "'.$logs['street'].'",
         "'.$logs['createdate'].'"]);';
        }

        $data['script'] ="var dataSet = [ ];
        ". $dataset."
        $(document).ready(function() {
            $('#report_table').DataTable( {
                data:dataSet,
                columns: [
                    { title: 'Id' },
                    { title: 'fridge Unit Code' },
                    { title: 'Fridge type' },
                    { title: 'Location Name' },
                    { title: 'Temperature' },
                    { title: 'Province' },
                    { title: 'Region' },
                    { title: 'Street' },
                    { title: 'Createdate' }
                ]
            } );
        } );".'
        var barChartData = {
            labels: ['.$labels.'],
            datasets: [
                {
                    label: "Fridge Temperature Chart",
                    fillColor: "rgba(220,220,220,0.5)", 
                    strokeColor: "rgba(220,220,220,0.8)", 
                    highlightFill: "rgba(220,220,220,0.75)",
                    highlightStroke: "rgba(220,220,220,1)",
                    data: ['.$values.']
                }
            ]
        };
        var options = {
            scaleBeginAtZero: false,
            responsive: true,
            scaleStartValue : -50 
        };
        window.onload = function(){
            var ctx = document.getElementById("canvas").getContext("2d");
            window.myObjBar = new Chart(ctx).Bar(barChartData,options, {
                  responsive : true
            });

            //nuevos colores
           '.$barColors.'
            myObjBar.update();
        }';

        $data['page_title']="Fridge Log Report";

        $this->show_view('frigde_log_details', $data);
    }
 public function faulty_fridge_report(){

        $fridge_id=$this->fridge_temps();

        $province_id=$this->input->post('province');
       
        $fridge_type_id=$this->input->post('fridge_type');
        $data['unit_code']=$this->input->post('fridge_uinit_code');

         $brand_id=2;

        $fridges = $this->fridge_model->get_fridges($province_id,$brand_id,$fridge_type_id,$data['unit_code'],$fridge_id);

        $result=$fridges['result'];
        $data['query']=$fridges['query'];

        $province = $this->spazapp_model->get_province();
        $brand = $this->spazapp_model->get_brands();
        $fridge_type = $this->fridge_model->get_fridge_type();

        $province_option='';
        foreach ($province as $row) {
            $province_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $brand_option='';
        foreach ($brand as $row) {
            $brand_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $brand_option='';
        foreach ($brand as $row) {
            $brand_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $fridge_type_option='';
        foreach ($fridge_type as $row) {
            $fridge_type_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $data['province_option']=$province_option;
        $data['brand_option']=$brand_option;
        $data['fridge_type_option']=$fridge_type_option;

 
        $dataset ='';

    foreach ($result as $fr) {
         $dataset.='dataSet.push([
         "'."<a href='/dashboard/fridge_details/".$fr['id']."'><font color='#f89520'>".$fr['fridge_unit_code']."</font></a>".'",
         "'.$fr['fridge_type'].'",
         "'.$fr['location_name'].'",
         "'.$fr['temp'].'",
         "'.$fr['province'].'",
         "'.$fr['region'].'",
         "'.$fr['createdate'].'",
         "'."<li class='dropdown'><a href='#' class='dropdown-toggle btn' data-toggle='dropdown' style='color:black'>Actions <b class='caret'></b></a><ul class='dropdown-menu' role='menu'><li><a href='/dashboard/fridge_locations_history/".$fr['id']."'>Locations History</a></li><li><a href='/dashboard/daily_monthly_temperature/".$fr['id']."'>Daily Temperatures</a></li></ul></li></ul>".'"]);';
        }

     
        $data['script'] ="var dataSet = [ ];
        ". $dataset."
        $(document).ready(function() {
            $('#report_table').DataTable( {
                data:dataSet,
                columns: [
                    { title: 'fridge Unit Code' },
                    { title: 'Fridge type' },
                    { title: 'Location Name' },
                    { title: 'Temperature' },
                    { title: 'Province' },
                    { title: 'Region' },
                    { title: 'Createdate' },
                    { title: 'Actions' }
                ],
                 dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
            } );
        } );

      ";

        $data['page_title']="Faulty Fridge Report";

        $data['province'] = $this->spazapp_model->get_province_by_id($province_id);
        $data['brand'] = $this->spazapp_model->get_brands_by_id($brand_id);
        $data['fridge_type'] = $this->fridge_model->get_fridge_type_by_id($fridge_type_id);

        $this->show_view('frigde_log_report', $data); 

    }

     public function fridge_street_view($fridge_id)
    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        $fridge =  $this->fridge_model->get_fridge_street($fridge_id);
        $data['page_title'] = $fridge['brand']." - ".' Street View';
       
        $this->load->library('googlemaps');

        $config['center'] = $fridge['long']. ', '.$fridge['lat'];
        $config['map_type'] = 'STREET';
        $config['streetViewPovHeading'] = 90;
        $this->googlemaps->initialize($config);
        $data['map'] = $this->googlemaps->create_map();


        $this->show_view('fridge_street_view', $data);
    }

     public function fridge_history_street_view($fridge_id,$log_id)
    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        $fridge =  $this->fridge_model->get_fridge_street_history($fridge_id,$log_id);

        $data['page_title'] = 'Street View';

        $data['fridge_info']='
            <table>
                <tr>
                   <td style="background-color:silver;" colspan="2">
                        <strong>Fridge Information</strong>
                   </td>
                 
                </tr>
                <tr>
                   <td style="background-color:#f9f9f9;"><b>Unit Code</b></td>
                   <td style="background-color:#f9f9f9;"> : '.$fridge['fridge_unit_code'].'</td>
                </tr>

                <tr>
                   <td style="background-color:#f9f9f9;"><b>Fridge Type</b></td>
                   <td style="background-color:#f9f9f9;"> : '.$fridge['fridge_type'].'</td>
                </tr>

                <tr>
                   <td style="background-color:#f9f9f9;"><b>Temperature</b></td>
                   <td style="background-color:#f9f9f9;"> : '.$fridge['temp'].'&deg;</td>
                </tr>
            </table>';

        $this->load->library('googlemaps');

        $config['center'] = $fridge['long']. ', '.$fridge['lat'];
        $config['map_type'] = 'STREET';
        $config['streetViewPovHeading'] = 90;
        $this->googlemaps->initialize($config);
        $data['map'] = $this->googlemaps->create_map();


        $this->show_view('fridge_street_view', $data);
    }
 public function fridge_current_street_view($fridge_id)
    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        $fridge =  $this->fridge_model->get_current_location($fridge_id);

        if(empty($fridge['long'])){
            $fridge =  $this->fridge_model->get_fridge_street($fridge_id);
        }
        
        $data['page_title'] = $fridge['brand']." - ".' Street View';
       
        $this->load->library('googlemaps');

        $config['center'] = $fridge['long']. ','.$fridge['lat'];
        $config['map_type'] = 'STREET';
        $config['streetViewPovHeading'] = 90;
        $this->googlemaps->initialize($config);
        $data['map'] = $this->googlemaps->create_map();


        $this->show_view('fridge_street_view', $data);
    }

      public function fridge_temparature()

    {

        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        $this->load->library('googlemaps');
        $data['page_title'] = 'Fridge Locations';
        $data['fidges'] =  $this->fridge_model->get_fridges_locations();

        $config['center'] = '-29.8590, 31.0189';
        $config['zoom'] = '1';
        $this->googlemaps->initialize($config);

        foreach ( $data['fidges'] as $fridge) 
        {
            $marker = array();
            $marker['position'] = $fridge['lat']. ', '.$fridge['long'];
            $marker['draggable'] = true;
            $marker['infowindow_content'] = '<strong>'.$fridge['brand']. '</strong>&nbsp; <a href="/dashboard/fridge_street_view/'.$fridge['id'].'"><i class="fa fa-male">View Street</i></a> <br />'.$fridge['street'].'<br />'.$fridge['region'].'<br />'.$fridge['province'].'';
            $marker['animation'] = 'DROP';
            $marker['icon'] = '/assets/img/brr_map_icon.png';
            $this->googlemaps->add_marker($marker);
        }
        

        $data['map'] = $this->googlemaps->create_map();


        $this->show_view('fridge_locations', $data);
    }

    function fridge_temps(){
        $fidges=  $this->fridge_model->get_fridges_locations();
        $comma='';
        $faulty='';
        foreach ( $fidges as $row) 
        {
            $max_temp = $row['expected_temp']+$row['tolerance'];
            $min_temp = $row['expected_temp']-$row['tolerance'];
            $off_temp = $row['considered_off'];

            if($row['temp'] > $max_temp  OR $row['temp'] < $min_temp OR $row['temp'] > $off_temp){
                $faulty.=$comma.$row['id'];
                $comma=',';
            }

        
        }
        return $faulty;
    }

     function get_trader($trader_id){
        // $array = array();
        $results= $this->event_model->get_trader_location_log($trader_id);
      
        echo json_encode($results);
     }

     public function trader_locations()
    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);
        $this->load->library('googlemaps');
        $data['page_title'] = 'Traders Locations';
        

        $config['center'] = '-29.8590, 31.0189';
        $config['zoom'] = '5';

        $this->googlemaps->initialize($config);

        $result = $this->event_model->get_traders();
        foreach ($result as $trader) 
        {
            if(!empty($trader['long'])){
                $marker = array();
                $marker['position'] = $trader['long']. ', '.$trader['lat'];
                $marker['draggable'] = true;
                $marker['infowindow_content'] = '<img src="/assets/uploads/customer/'.$trader['trader_picture'].'" alt="Trader Picture" height="90" width="90"><br /><strong>'.$trader['first_name']." ".$trader['last_name']. '</strong>&nbsp; <a href="/dashboard/trader_street_view?q='.$trader['id'].'"><i class="fa fa-male"></i></a> <br />'.$trader['address'].'<br />Province : '.$trader['name'].'<br/>'.'Region : '.$trader['region'].'<br/>'.$trader['suburb'].'<br />'.'Createdate : '.$trader['createdate'];
                $marker['animation'] = 'DROP';
                $marker['icon'] = '/assets/img/custom_map_icon.png';
                $this->googlemaps->add_marker($marker);
            }
           
        }

        $data['trader'] = $this->trader_model->get_traders_with_locations();
        $data['map'] = $this->googlemaps->create_map();
        $this->show_view('trader_locations', $data);
    }

    function trader_street_view(){
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);
         $id = $_GET['q'];
        $trader = $this->event_model->tradersById($id);
        $data['trader'] = $trader;
        $this->load->library('googlemaps');
        $data['page_title'] = $trader['first_name']." ".$trader['last_name'].' - Street View';

        $config['center'] = $trader['location_lat'].', '.$trader['location_long'];
        $config['map_type'] = 'STREET';
        $config['streetViewPovHeading'] = 90;
        $this->googlemaps->initialize($config);
        $data['map'] = $this->googlemaps->create_map();
        $this->show_view('trader_street_view', $data);
    }

       function fridge_locations_history($fridge_id){
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        $data['date_from']=$this->input->post('date_from');
        $data['date_to']=$this->input->post('date_to');
        $location=  $this->fridge_model->get_current_location($fridge_id);
        $result = $this->fridge_model->get_fridge_locations($fridge_id,$data['date_from'],$data['date_to']);
        $data['fridge']=$result;
        $this->load->library('googlemaps');
     
       // $config['center'] = '-29.8590, 31.0189';
        $config['center'] = $location['long'].','.$location['lat'];
        $config['zoom'] = 'auto';
        $config['directions'] = TRUE;
        //fridge_locations_historyfridge_locations_history$this->googlemaps->initialize($config);

        $dataset ='';
        $count=1;
        foreach ($result as $key=>$fridge) 
        {
       
            //get status
            $max_temp = $fridge['expected_temp']+$fridge['tolerance'];
            $min_temp = $fridge['expected_temp']-$fridge['tolerance'];
            $off_temp = $fridge['considered_off'];

            $status['icon'] = 'brr_map_icon.png';
            $status['message'] = 'Optimal';
         
            if($fridge['temp'] > $max_temp){
                $status['icon'] = 'brr_warning_map_icon.png';
                $status['message'] = 'Too Hot';
            }

            if($fridge['temp'] < $min_temp){
                $status['icon'] = 'brr_warning_map_icon.png';
                $status['message'] = 'Too Cold';
            }

            if($fridge['temp'] > $off_temp){
                $status['icon'] = 'brr_off_map_icon.png';
                $status['message'] = 'Fridge is Off';
            }

            //Working on location map
            $marker = array();
            $marker['position'] = $fridge['long']. ', '.$fridge['lat'];
            $marker['draggable'] = true;
            $marker['infowindow_content'] = '<strong>'.$key." ".$fridge['location_name']. '</strong>&nbsp; <a href="/dashboard/fridge_history_street_view/'.$fridge['id'].'/'.$fridge['log_id'].'"><i class="fa fa-male">Street View  </i></a> <br /> <b>Fridge unit code </b> : '.$fridge['fridge_unit_code'].'<br /> <b>Street </b>: '.$fridge['street'].'<br /> <b>Region</b> : '.$fridge['region'].'<br /> <b>Province</b> : '.$fridge['province'].'<br/>'.'<b>Createdate</b> : '.$fridge['createdate'].'<br/>'.'<b>Temperature</b> : '.$fridge['temp'].'&deg;';
            $marker['animation'] = 'DROP';
            $marker['icon'] = '/assets/img/'.$status['icon'];
            $this->googlemaps->add_marker($marker);

            //Data set to be displayed on  data table 
             $dataset.='dataSet.push([
             "'."<a href='/dashboard/fridge_details/".$fridge['id']."'>"."<font color='#f8993b'>".$fridge['fridge_unit_code']."</font>"."</a>".'",
             "'.$fridge['fridge_type'].'",
             "'.$fridge['location_name'].'",
             "'.$fridge['province'].'",
             "'.$fridge['region'].'",
             "'.$fridge['street'].'",
             "'.$fridge['createdate'].'"]);';
         $count++;
        }
         $this->googlemaps->initialize($config);
        //DataTable script
        $data['script'] ="var dataSet = [ ];
        ". $dataset."
        $(document).ready(function() {
            $('#report_table').DataTable( {
                data:dataSet,
                columns: [
                    { title: 'fridge Unit Code' },
                    { title: 'Fridge type' },
                    { title: 'Location Name' },
                    { title: 'Province' },
                    { title: 'Region' },
                    { title: 'Street' },
                    { title: 'Createdate' },
                ]
            } );
        } );";

        $data['map'] = $this->googlemaps->create_map();

        $data['page_title'] = 'Fridge Locations History';
        $this->show_view('fridge_locations_history', $data);

    }


    function daily_monthly_temperature($fridge_id){
        $data['date_from']=$this->input->post('date_from');
        $data['date_to']=$this->input->post('date_to');

        $comma ='';
        $values ='';
        $labels ='';
        $dataSet='';
        $barColors='';
        $count=0;
        $days=$this->fridge_model->get_fridges_daily_temperature($fridge_id,$data['date_from'],$data['date_to']);
        $data['query']=$days['query'];

        foreach ($days['result'] as $row) {   

            $max_temp = $row['expected_temp']+$row['tolerance'];
            $min_temp = $row['expected_temp']-$row['tolerance'];
            $off_temp = $row['considered_off'];

            $color = '#27ade5';

            if($row['temp'] > $max_temp){
                $color = '#d49c2d';
               
            }

            if($row['temp'] < $min_temp){
                $color = '#d49c2d';
            }

            if($row['temp'] > $off_temp){
                $color = '#d22c2c';

            }
            $barColors.='myObjBar.datasets[0].bars['.$count.'].fillColor = "'.$color.'";';

            $labels.= $comma.'"'.(date(" D d M Y", strtotime($row['createdate']))).'"';

            $values.=$comma.$row['temp'];

            $comma = ',';
            $count++;
        }

      
        $dataset='';
        foreach ($days['result'] as $item) {
    
         $dataset.='dataSet.push([
         "'."<a href='/cocacola/dashboard/fridge_details/".$item['id']."'>"."<font color='#f8993b'>".$item['fridge_unit_code']."</font>"."</a>".'",
         "'.$item['fridge_type'].'",
         "'.$item['temp'].'",
         "'.$item['location_name'].'",
         "'.$item['province'].'",
         "'.$item['region'].'",
         "'.$item['street'].'",
         "'.$item['createdate'].'"]);';

        }

        $data['script']="var dataSet = [ ];
        ". $dataset."
       
        $(document).ready(function() {
            $('#report_table').DataTable( {
                data:dataSet,
                columns: [
                    { title: 'fridge Unit Code' },
                    { title: 'Fridge type' },
                    { title: 'Temperature' },
                    { title: 'Location Name' },
                    { title: 'Province' },
                    { title: 'Region' },
                    { title: 'Street' },
                    { title: 'Createdate' },
                ],
                 dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
            } );
        } );


         var barChartData = {
            labels: [".$labels."],
           datasets: [
           {
            label: 'Fridge Temperature Chart',
            fillColor: 'rgba(220,220,220,0.5)', 
            strokeColor: 'rgba(220,220,220,0.8)', 
            highlightFill: 'rgba(220,220,220,0.75)',
            highlightStroke: 'rgba(220,220,220,1)',
            data: [".$values."]
            }
         ]
          
        };
       
        var options = {
            scaleBeginAtZero: false,
            responsive: true,
            scaleStartValue : -50 
        };
        window.onload = function(){
            var ctx = document.getElementById('canvas').getContext('2d');
            window.myObjBar = new Chart(ctx).Bar(barChartData,options, {
                  responsive : true
            });

            //nuevos colores
           ".$barColors."
            myObjBar.update();
        }";
        $data['fridge']=$this->fridge_model->get_fridges_monthly_temperature($fridge_id,$data['date_from'],$data['date_to']);
       
        $data['page_title']="Fridge Temperatures";
        $this->show_view('daily_monthly_temperature',$data);
    }

    function kpi(){
        $data['date_from'] = $this->input->post('date_from');
        $data['date_to'] = $this->input->post('date_to');

        $data['num_of_distributors']=$this->customer_model->getNumberOfDistributors($data['date_from'],$data['date_to']);
        $data['number_of_shops']=$this->customer_model->getNumberOfShops($data['date_from'],$data['date_to']);
        $data['number_of_logon_shops']=$this->customer_model->getNumberOfLogonShops()['total'];
        $data['number_of_active_shops']=$this->customer_model->getNumberOfActiveShops()['total'];
        $data['number_of_logged_sparks']=$this->customer_model->getNumberOfLoggedSparks()['total'];
        $data['number_of_active_sparks']=$this->customer_model->getNumberOfActiveSparks()['total'];
        $data['number_of_registered_sparks']=$this->customer_model->getNumberOfRegisteredSparks();
        $data['number_of_fmcg']=$this->product_model->getNumberOfFmcg();
        $data['number_of_fmcg_sold']=$this->product_model->getNumberOfFmcgSold($data['date_from'],$data['date_to']);
        $data['logged_in']=$this->event_model->getLoggedIn();
        $data['number_of_placed_orders_with_spark']=$this->order_model->getNumberOfOrdersWithSpark();
        $data['number_of_placed_orders']=$this->order_model->getNumberOfOrders('');
        $data['number_of_delivered_orders']=$this->order_model->getNumberOfOrders('delivered');
        $data['airtime_sales']=$this->airtime_model->getAirtimeSalesTotal();
        $data['number_of_airtime_purchase']=$this->airtime_model->get_total_number_of_airtime_purchase();
        $data['number_of_customers_placed_order']=$this->order_model->getNumberOfCustomersPlacedOrder();
        $data['repeat_orders']=$this->order_model->get_customer_repeat_orders();
        $data['suppliers']=$this->customer_model->get_suppliers();

        $supplier_sales='';
        $total_delivered=0;
        $total_placed=0;
        $data['sales_results']=$this->order_model->supplierSalesTotal($distributor_id='');

        foreach ($data['sales_results'] as  $value) {
            $total_recieved=$this->order_model->getSupplierDeliveredOrdersTotal($value['supplier_id'],$data['date_from'],$data['date_to'],$distributor_id='');
            $supplier_sales.="<tr>
                                <td>".$value['company_name']."</td>
                                <td>".number_format($total_recieved['total'],2,'.',', ')."</td>
                                <td>".number_format($value['total'],2,'.',', ')."</td>
                            </tr>";
            $total_delivered+=$total_recieved['total'];
            $total_placed+=$value['total'];
        }

        
        $data['total_placed']=$total_placed;
        $data['total_delivered']=$total_delivered;

        $data['supplier_sales']=$supplier_sales;

        //Calculating averages
        if($data['number_of_airtime_purchase']==0){
            $data['avg_airtime_purchase']=0;
        }else{
            $data['avg_airtime_purchase']=$data['airtime_sales']/$data['number_of_airtime_purchase'];
        }
        
        $data['page_title']="Key Performance Indicator";
        $this->show_view('kpi_report',$data);
    }

    function check_purchases(){
        $this->airtime_model->check_purchases('airtime_report');
        redirect ('/dashboard/airtime_report');
    }

    function customers(){
        $region_id='';
        $customer = $this->customer_model->get_customer_by_region($region_id);
        $dataset='';
        foreach ($customer as $r) {
            if(!empty($r['company_name'])){
               $company_name = $r['company_name'];
            }else{
                $company_name='Null';
            }
        $dataset.='dataSet.push(["'.$company_name.'",
        "'.$r['first_name']." ".$r['last_name'].'",
        "'.$r['trader_id'].'",
        "'.$r['region'].'",
        "'.$r['province'].'",
        "'.$r['createdate'].'"]);';
        }
        $columns="{ title: 'company_name'},
                  { title: 'Name'},
                  { title: 'Trader Id'},
                  { title: 'Region'},
                  { title: 'Province'},
                  { title: 'Createdate'}";

       
        $data['script'] = "".$this->data_table_script($dataset,$columns)."".$this->chart_script($labels='',$values='')."";

        $data['page_title']="customers";
        $this->show_view('customers_report',$data);
    }

    function no_location_orders(){
        $dataset='';
        $orders=$this->order_model->get_no_location_order();
        foreach ($orders as $r) {
        $dataset.='dataSet.push([
                "'.$r['company_name'].'",
                "'.$r['first_name']." ".$r['last_name'].'",
                "'.$r['order_id'].'",
                "'.$r['cellphone'].'",
                "'.$r['location_lat'].'",
                "'.$r['location_long'].'"
            ]);';
                
        }

         $columns="{ title: 'company_name'},
                   { title: 'Name'},
                   { title: 'Order Id'},
                   { title: 'Cellphone'},
                   { title: 'Location Lat'},
                   { title: 'Location Long'}";

        $data['script'] = "".$this->data_table_script($dataset,$columns)."".$this->chart_script($labels='',$values='')."";

        $data['page_title']="Orders with no Locations";
        $this->show_view('no_location_orders',$data);
    }

    function products_no_image(){
        $dataset='';
        $products = $this->product_model->get_products_no_image();
        foreach ($products as $r) {

         $dataset.='dataSet.push([
         "'."<a href='/management/products/edit/".$r['id']."' target='_blank'>"."<font color='#f8993b'>".$r['id']."</font>"."</a>".'",
         "'.preg_replace('/[^A-Za-z0-9\-]/', '',$r['stock_code']).'",
         "'.$r['barcode'].'",
         "'.preg_replace('/[^A-Za-z0-9\-]/', '',$r['name']).'"
         ]);';
                
        }

        $columns="{ title: 'ID'},{ title: 'Stock Code'},{ title: 'Barcode'},{ title: 'Name'}";
        $data['script'] = $this->data_table_script($dataset,$columns);

        $data['page_title']="Products with no image";
        $this->show_view('products_no_image',$data);
    }

    function products_zero_price(){
        $dataset='';
        
        $distributors = $this->spazapp_model->get_all_distributors();
        foreach ($distributors as $key => $distro) {
            $products = $this->product_model->get_supplier_distributor_products_zero_price($distro['id']);
            if($products){
                foreach ($products as $r) {

                 $dataset.='dataSet.push([
                 "'.$distro['company_name'].'",
                 "'."<a href='/management/products/edit/".$r['id']."' target='_blank'>"."<font color='#f8993b'>".$r['id']."</font>"."</a>".'",
                 "'.$r['stock_code'].'",
                 "'.$r['barcode'].'",
                 "'.$r['name'].'",
                 "'.$r['shrink_price'].'"]);';
                        
                }
            }
        }

        $columns="{ title: 'Distributor'},{ title: 'ID'},{ title: 'Stock Code'},{ title: 'Barcode'},{ title: 'Name'},{ title: 'Shrink Price'}";

        $data['script'] = "".$this->data_table_script($dataset,$columns)."".$this->chart_script($labels='',$values='')."";

        $data['page_title']="Products with no Price";
        $this->show_view('products_no_image',$data);
    }

    function ott_vouchers(){

        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');

        $data['voucher_stats']=$this->ott_model->get_ott_vouchers('stats',$data['date_from'],$data['date_to']);

        $data['page_title'] = "OTT Voucher Report";
        $this->show_view('ott_vouchers',$data);
    }

    function ott_json($user_id=''){
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');
        if(isset($user_id) && !empty($user_id)){
            $vouchers['data']=$this->ott_model->get_ott_vouchers('all',$data['date_from'],$data['date_to'], $user_id);
        }else{
            $vouchers['data']=$this->ott_model->get_ott_vouchers('all',$data['date_from'],$data['date_to']);
        }
    
        echo json_encode($vouchers);
    }

    function user_ott_voucher($user_id){
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');
        $user_info=$this->user_model->get_user($user_id);
        $data['page_title'] = $user_info->name." - OTT Voucher Report";
        $this->show_view('user_ott_vouchers',$data);
    }

    function electricity_transactions(){
        $data['page_title']="Electricity Transactions";
        $this->show_view("electricity",$data);
    }
    function electricity_json(){
        $elctricity['data']=$this->financial_model->get_electricity_transactions();
        echo json_encode($elctricity, JSON_PRETTY_PRINT);
    }


    function trader_commisions(){
        $data['page_title']="Traders Commission";
        $data['date_from']=$this->session->userdata('dashboard_date_from');
        $data['date_to']=$this->session->userdata('dashboard_date_to');
        $this->show_view("trader_commisions", $data);
    }

    function get_trader_commission_json($date_from,$date_to){
        // $date_from=$this->session->userdata('dashboard_date_from');
        // $date_to=$this->session->userdata('dashboard_date_to');
        $commissions['data']=$this->trader_model->get_trader_commisions($date_from, $date_to);
        echo json_encode($commissions);
    }


    function smartcall_purchase_report($json=false, $stats_request=false, $date_from="",$date_to=""){
        if($stats_request){
            $purchases['data'] = $this->smartcall_model->get_smart_call_purchases($stats_request, $date_from, $date_to);
        }else{
            $purchases['data'] = $this->smartcall_model->get_smart_call_purchases('', $date_from, $date_to);
        }
        
        if($json){
            echo json_encode($purchases);
        }else{
            $data['page_title']="Electricity Report";
            $this->show_view("smartcall_purchases",$data);
        }
    }

    function refund_electricity($msisdn, $amount, $reference){
        $this->financial_model->refund_electricity($msisdn, $amount, $reference);
    }

      public function single_spark_location($trader_id)
    {
        //Turn off all error reporting because of googles map depreciation error
        //error_reporting(0);
        $this->load->library('googlemaps');
        $data['info'] = $this->trader_model->get_trader_basic($trader_id);
        $config['center'] = '-29.085214, 26.15957609999998';
        $config['zoom'] = '5';
        // $config['center'] = $data['info']['location_lat']. ', '.$data['info']['location_long'];
        // $config['zoom'] = 'auto';
        $this->load->library('googlemaps');

        $user=$this->trader_model->get_user_from_trader_id($trader_id);  
        $data['last_seen']=$this->event_model->get_last_activity($user['id']); 

        $data['date_from']=$this->session->userdata('dashboard_date_from');
        $data['date_to']=$this->session->userdata('dashboard_date_to');
    
        $trader_logs=$this->event_model->get_trader_locations($user['id'], $data['date_from'], $data['date_to']);

        if(!empty($trader_logs)){
            foreach ($trader_logs as $key => $trader) {
                $this->googlemaps->initialize($config);
                $marker = array();
                $marker['position'] = $trader['long']. ', '.$trader['lat'];
                $marker['draggable'] = true;
                $marker['infowindow_content'] = "<strong>".$trader['name']."</strong>".'<br/> Cellphone : '.$trader['cellphone'].'<br />'.'Createdate : '.$trader['createdate'];
                $marker['animation'] = 'DROP';
                $marker['icon'] = '/assets/img/custom_map_icon.png';
                $this->googlemaps->add_marker($marker);

            }
            
        }else{
                $this->googlemaps->initialize($config);
                $marker = array();
                $marker['position'] = $data['info']['long']. ', '.$data['info']['lat'];
                $marker['draggable'] = true;
                $marker['infowindow_content'] = "<strong>".$data['info']['first_name']." ".$data['info']['last_name']."</strong>".'<br/> Cellphone : '.$data['info']['cellphone'].'<br />'.'Createdate : '.$data['info']['createdate'];
                $marker['animation'] = 'DROP';
                $marker['icon'] = '/assets/img/custom_map_icon.png';
                $this->googlemaps->add_marker($marker);
        }

        $data['map'] = $this->googlemaps->create_map();
        $last_location =$this->event_model->get_current_location($user['id']);
    
        if(!empty($last_location['long']) || !empty($last_location['long'])){  
            $data['current_location']=$this->getcurrentlocation($last_location['lat'],$last_location['long']);
        }else{
            $data['current_location']=$this->getcurrentlocation($data['info']['lat'],$data['info']['long']);
        }

        $data['page_title'] = 'Spark Location History';

        $this->show_view('single_spark_location', $data);
    }

   function deposit(){
        $data['date_from']=$this->session->userdata('dashboard_date_from');
        $data['date_to']=$this->session->userdata('dashboard_date_to');
        $data['deposit']=$this->financial_model->get_deposit($data['date_from'], $data['date_to']);
        $data["page_title"]="Deposit";
        $this->show_view("deposit",$data);
    }
    
    function user_credit_report(){
        $data['date_from']=$this->session->userdata('dashboard_date_from');
        $data['date_to']=$this->session->userdata('dashboard_date_to');
        $data['credits']=$this->financial_model->get_credits($data['date_from'], $data['date_to']);
        $data["page_title"]="User Credit Report";
        $this->show_view("user_credit_report",$data);
    }



}