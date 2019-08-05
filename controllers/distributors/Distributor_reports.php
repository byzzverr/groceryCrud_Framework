<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class distributor_reports extends CI_Controller {

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
        $this->load->model('delivery_model');
        $this->load->library('pagination');
        $this->load->model('news_model');
        $this->load->library('table');
        $this->user = $this->aauth->get_user();
        $d_id = $this->user->distributor_id;

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }          

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
/*        if (!$this->aauth->is_allowed('Dashboard')){
            $this->event_model->track('error','permissions', 'Dashboard');
            redirect('/');
        }*/

        // Check for Distributor id
        if ($d_id <= 0){
            $this->aauth->logout();
            redirect('/login');
        }

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

    function get_orders(){

        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $name = $this->order_model->getDistributorNameByID($distributor_id);

        $query_results['orders_results'] = $this->app_model->get_all_distributor_orders();
        $data['query_results'] = $query_results['orders_results']['orders'];
        $data['query'] = $query_results['orders_results']['query'];
    
        $data['page_title'] = $name->company_name.' All Orders';
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');

        $stores_orders = $this->spazapp_model->get_dist_strores_orders($distributor_id);
        $products_orders = $this->spazapp_model->get_dist_products_orders($distributor_id);
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

        $data['script'] = '';
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $name = $this->order_model->getDistributorNameByID($distributor_id);
        $data['name'] = $name;

        if($page_title == 'All Orders' || $page_title == $name->company_name.' All Orders'){

            $stores_orders = $this->spazapp_model->get_distributor_strores_orders();
            $products_orders = $this->spazapp_model->get_distributor_products_orders();
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

        if($page_title == 'Distributor - Sales Report' || $page_title == $name->company_name.' - Sales Report'){

            $data['suppliers'] = $this->order_model->getDistributorSupplierStats();            
            //$data['statuses'] = $this->order_model->get_all_atatuses();
            $data['statuses'] = $this->order_model->getDistributorStatuses();         

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

    // Sales Report

    function sales_report($limit=0)
    {

        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $name = $this->order_model->getDistributorNameByID($distributor_id);

        if(!empty($distributor_id)){
             $data['date_from'] = $this->input->post('date_from');
            $data['date_to']= $this->input->post('date_to');
            $data['sales'] = $this->order_model->getSupplierSales($distributor_id);
            $data['statuses']=$this->order_model->getDistributorStatuses(); 

            $distributor= $this->product_model->getDistributorById($distributor_id); 

         if(!empty($distributor_id)){
            $data['distributor_id'] = $distributor['id'];
            $data['distributor_name'] = $distributor['company_name'];
        }else{
            $data['distributor_name']= 'All';
            $data['distributor_id'] = '';

        }
        
        $total=0;
        $dataset='';
        $sales= $this->order_model->getSupplierSales($distributor_id);
        foreach ($sales as $row) {
         $dataset.='dataSet.push(["'.$row['order_id'].'","'.$row['customer'].'","'.$row['status'].'",
         "'.$row['payment_type'].'","'.number_format($row['total'],2,'.',' ').'","'.$row['createdate'].'"]);';
        }

        // "'."<li class='dropdown'><a href='#' class='dropdown-toggle btn' data-toggle='dropdown' style='color:black'>Actions <b class='caret'></b></a><ul class='dropdown-menu' role='menu'><li><a href='/dashboard/order_details/".$row['order_id']."'>View Details</a></li></ul></li></ul>".'"

        $columns="{ title: 'Order Id'},
                  { title: 'Customer'},
                  { title: 'Status'},
                  { title: 'Payment Type'},
                  { title: 'Total'},
                  { title: 'Createdate'}";

        $data['script'] = "".$this->data_table_script($dataset,$columns)."".$this->chart_script($labels='',$values='')."";

        $supplier_sales='';
        $total_delivered=0;
        $total_placed=0;
        $template = array(
        'table_open' => '<table class="table-bordered table-striped" class="mytable" style="100%;">'
        );

        $this->table->set_template($template);
        $this->table->set_heading('Supplier', 'Total Delivered Orders', 'Total Placed Orders');//Codeigniter Table Heading

        $data['sales_results']=$this->order_model->supplierSalesTotal($distributor_id);

        foreach ($data['sales_results'] as  $value) {

            $total_recieved=$this->order_model->getSupplierDeliveredOrdersTotal($value['supplier_id'],$data['date_from'],$data['date_to'],$distributor_id);

            $this->table->add_row(
                    $value['company_name'],
                    number_format($total_recieved['total'],2,'.',', '),
                    number_format($value['total'],2,'.',', ')
                );//Codeigniter Table rows

        }
      
        $data['supplier_sales']=$this->table->generate();
        $data['page_title']   = 'Sales Report';
          
        }
             
       
        $this->show_view('distributor_sales_report', $data);
    }

     function supplier_sales($distributor_id){
         //Getting total sales per supplier
        $result=$this->customer_model->get_suppliers();
        $supplier_sales='';
         foreach ($result as $r) {
            if($this->order_model->get_supplier_number_orders($r['id'],$distributor_id)>0){
                 $supplier_sales.="<tr>".
                "<td><strong>".$r['company_name']."</strong> : </td>"
                // ."<td>".$this->customer_model->get_supplier_number_customers($r['id'],$distributor_id)."</td>"
                // ."<td>".$this->order_model->get_supplier_number_products($r['id'],$distributor_id)['total']."</td>"
                ."<td>".$this->order_model->get_supplier_number_orders($r['id'],$distributor_id)."</td>"
                ."<td>".number_format($this->order_model->get_supplier_deliver_order_total($r['id'],$distributor_id)['total'],2,'.',' ')."</td>"
               
                ."</tr>";
            }
           
        }

        return $supplier_sales;
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
    // Customer Locations Google Maps Api
    
    function customer_locations()
    {
        //Turn off all error reporting because of googles map depreciation error
        //error_reporting(0);
        $this->load->library('googlemaps');

        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->user_link_id;

        $data['customer_info'] = $this->customer_model->getDistributorCustomerLocations($distributor_id);    
        
        foreach ($data['customer_info'] as $customer) 
        {
            $config['center'] = $customer['location_lat']. ', '.$customer['location_long'];
            $config['zoom'] = '6';
            
            $this->googlemaps->initialize($config);

            if(!empty($customer['store_picture'])){
                $store_picture='<img src="/assets/uploads/customer/'.$customer['store_picture'].'" alt="Store Picture" height="90" width="90"><br />';
            }else{
                $store_picture='<img src="/assets/uploads/customer/no_photo.jpg" alt="Store Picture" height="90" width="90"><br />';
            }

            $marker = array();
            $marker['position'] = $customer['location_lat']. ', '.$customer['location_long'];
            $marker['draggable'] = true;
            $marker['infowindow_content'] = $store_picture.'<strong>'.$customer['company_name']. '</strong>&nbsp; <a href="/distributors/distributor_reports/street_view?q='.$customer['id'].'"><i class="fa fa-male"></i></a> <br /><strong>Address</strong> : '.$customer['address'].'<br /> <strong>Suburb</strong> : '.$customer['suburb'].'<br /><strong>Customer Name</strong> : '.$customer['last_name'].'<br/>'.'<strong>Cellphone </strong> : '.$customer['cellphone'];
            $marker['animation'] = 'DROP';
            $marker['icon'] = '/assets/img/custom_map_icon.png';

            $this->googlemaps->add_marker($marker);

            $data['customer_id']=$customer['id'];
            $data['name']=$customer['cellphone']." - ". $customer['last_name'];
        }

        if(empty($marker['position'])){
            $data['alert']='<p class="alert1"><span class="closebtn">&times;</span>'."Can't find the location"."</p>";
        }else{
            $data['alert']='';
        }

        $dataset='';
        $data['customers'] = $this->customer_model->getDistributorCustomerDetails($distributor_id);
        
        if(isset($_POST['customer_id'])){
            $customer_id = $_POST['customer_id'];
        }else{
            $customer_id = '0';
        }

        
        $data['map'] = $this->googlemaps->create_map();

        $data['page_title'] = 'Customer Locations';
        $this->show_view('distributor_customer_locations', $data);
    }

    function street_view()
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
                $marker['position'] = $order['long']. ', '.$order['lat'];
                $marker['draggable'] = false;
                $marker['infowindow_content'] = 'Customer Signed for order: '.$order['dist_order_id'];
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

    function customer_sales_report($distributor_id='', $top_limit=''){
        
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
 
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
        
        $this->show_view('distributor_customer_sales', $data);  
        
        
    }

    function product_sales_report($distributor_id='', $top_limit=''){
        
        $data['from'] = $this->input->post('from');
        $data['to']   = $this->input->post('to');
        $status  = $this->input->post('status');

        $data['page_title'] = "Products Sales Report";
        
        if(empty($top_limit)){
            $top_limit = "50";
        }
        
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $name = $this->order_model->getDistributorNameByID($distributor_id);

        $data['top_limit'] = $top_limit;
        $data['sales_results'] = $this->product_model->get_top_product_sales($distributor_id,$top_limit,$data['from'],$data['to'] ); 
        $data['distributors'] = $this->order_model->getDistributorNames(); 

        $data["statuses"]=$this->survey_model->get_gbl_statuses();
        $data['distributor'] = $this->product_model->getDistributor($distributor_id);
        $data['company_name'] = $name->company_name;
        $status_=$this->survey_model->getGblStatusById($status);
        
        if(!empty($status)){
            $data['status_name']=$status_['name'];  
            $data['status_id']=$status_['id'];  
        }else{
            $data['status_name']='Select status';  
            $data['status_id']='';  
        }
        
        $this->show_view('dist_product_sales', $data);  
        
        
    }

    function daily_orders(){

        $data['date_from']=$this->input->post('date_from');
        $data['date_to'] =$this->input->post('date_to');

        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;

        $data["statuses"]=$this->survey_model->get_gbl_statuses();
        $data['order_results']= $this->order_model->getDailyOrders($data['date_from'],$data['date_to'],0,$distributor_id);
        $data['order_stats']=$this->order_model->getDailyOrders($data['date_from'],$data['date_to'],31,$distributor_id)['result'];
        $data['monthly_orders']=$this->order_model->monthlyOrderStats($distributor_id); 
         
        $data['page_title']="Order Report";
        $this->show_view('dist_orders_report', $data);      
    }

    function data_table_script($dataset,$columns){
    $datatable="var dataSet = [ ];
    ". $dataset."
    $(document).ready(function() {
        $('#report_table').DataTable( 
        {
            
            'bProcessing': true,
            'bServerSide': false,
            'bDeferRender': true,
            'order': [[ 0,'desc' ]],
            data:dataSet,
            columns: [
                ".$columns."
            ],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
        } 
        );
    } );";
    return $datatable;
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
}