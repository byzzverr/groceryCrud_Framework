<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Logistics extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('spazapp_model');
        $this->load->model('logistics_model');
        $this->load->model('order_model');
        $this->load->model('product_model');

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Management')){
            $this->event_model->track('error','permissions', 'Management');
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
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('orders');
        $crud->set_subject('Orders');
        $crud->where('orders.status','Approved');      

        $crud->set_relation('payment_type','payment_types','name');
        $crud->set_relation('customer_id','customers','company_name');

        $crud->columns('id','customer_id','status','order_items','total','createdate');

        $crud->callback_column('order_items',array($this,'_callback_approved_order_items'));
        $crud->callback_column('total',array($this,'_callback_order_total'));

        $crud->unset_delete();
        $crud->unset_add();
        $crud->unset_edit();

        $this->session->set_userdata(array('table' => 'orders'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Approved Orders';

        $this->crud_view($output);
    }

    function awaiting_delivery(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('orders');
        $crud->set_subject('Orders');
        $crud->where('orders.status','Awaiting Delivery');      

        $crud->set_relation('payment_type','payment_types','name');
        $crud->set_relation('customer_id','customers','company_name');

        $crud->columns('id','delivery_id','customer_id','status','delivery_date','order_items','total','createdate');

        $crud->callback_column('order_items',array($this,'_callback_order_items'));
        $crud->callback_column('total',array($this,'_callback_order_total'));

        $crud->unset_delete();

        $this->session->set_userdata(array('table' => 'orders'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));
        $crud->callback_column('delivery_id',array($this,'_callback_order_delivery'));

        $output = $crud->render();

        $output->page_title = 'Awaiting Delivery';

        $this->crud_view($output);
    }

    function delivered(){

        $crud = new grocery_CRUD();
        
        

        $crud->set_table('del_orders');
        $crud->set_subject('Delivered');
        
        $status_id = '9';
        $results= $this->spazapp_model->get_del_orders_by_status($status_id);
        
        $comma='';
        $dist_order_id='0';
        foreach($results as $item){
            $dist_order_id .=$comma.$item->id;
            $comma=',';
        }
        
        $where ='dist_order_id IN('.$dist_order_id.')';
      
        $crud->where($where);  
        
        $crud->columns('id','customer_id','payment_type','order_items','status','delivery_id','time_taken','signiture','delivery_date','total');
       
        $crud->set_field_upload('signiture','images/');
        
        $this->session->set_userdata(array('table' => 'del_orders'));
        
        $crud->callback_column('order_items',array($this,'_callback_order_items'));
        $crud->callback_column('customer_id',array($this,'_callback_del_order_customer'));
        $crud->callback_column('payment_type',array($this,'_callback_del_payment_type'));
        $crud->callback_column('status',array($this,'_callback_del_order_statuses'));
        $crud->callback_column('total',array($this,'_callback_order_total'));
     
        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));
        
        $crud->unset_delete();
        $crud->unset_add();
        $crud->unset_edit();
        
        $output = $crud->render();

        $output->page_title = 'Delivered Orders';

        $this->crud_view($output);
    }
    
    function _callback_del_order_items($value, $row){
        $count = $this->spazapp_model->get_del_order_item($row->dist_order_id);
        return '<a href="/distributors/distributor_management/order_item/'.$row->dist_order_id.'/'.$row->dist_order_id.'">'.$count.'</a>';
    }
    
    
    function _callback_del_order_customer($value, $row){
        $customer='';
        $results= $this->spazapp_model->get_del_order($row->dist_order_id);
        foreach($results as $item){
            $customer = $item->customer_id;
        }
        return $customer;
    }
    function _callback_del_order_total($value, $row){
        $total='';
        $results= $this->spazapp_model->get_del_order($row->dist_order_id);
       
        foreach($results as $item){
            $customer = number_format($item->total,2,',',' ');
        }
        return $customer;
    }
  
    function _callback_del_dist_order_id($value, $row){
        $dist_order_id='';
        $results= $this->spazapp_model->get_del_order($row->dist_order_id);
        foreach($results as $item){
            $dist_order_id = $item->dist_order_id;
        }
        return $dist_order_id;
    } 
    
    function _callback_del_order_statuses($value, $row){
        $status='';
        $results = $this->spazapp_model->get_dist_del_order_status($row->dist_order_id);
        foreach($results as $item){
            $status = $item->name;
        }
        return $status;
    }  
    
    function _callback_del_delivery_id($value, $row){
        $delivery_id='';
        $results = $this->spazapp_model->get_del_order($row->dist_order_id);
        foreach($results as $item){
            $delivery_id = $item->delivery_id;
        }
        return $delivery_id;
    }   
    
    function _callback_del_payment_type($value, $row){
        $payment_type='';
        $results = $this->spazapp_model->get_del_order($row->dist_order_id);
        foreach($results as $item){
            $payment_type = $item->payment_type;
        }
        return $payment_type;
    }
    function _callback_del_time_taken($value, $row){
        $time_taken='';
        $results = $this->spazapp_model->get_dist_del_order($row->dist_order_id);
        foreach($results as $item){
            $time_taken = $item->time_taken;
        }
        return $time_taken;
    }
    
    function _callback_del_delivery_date($value, $row){
        $delivery_date='';
        $results = $this->spazapp_model->get_del_order($row->dist_order_id);
        foreach($results as $item){
            $delivery_date = $item->delivery_date;
        }
        return $delivery_date;
    }
    
    function _callback_del_signiture($value, $row){
        $signiture='';
        $results = $this->spazapp_model->get_del_order($row->dist_order_id);
        foreach($results as $item){
            $signiture = $item->signiture;
        }
        return $signiture;
    }

  function update_orders_fields($post_array,$primary_key)
    {
        $order_id = $primary_key;
        $distributor_id = $this->uri->segment(3);
        $this->spazapp_model->update_save_orders_fields($order_id, $post_array);
        unset($post_array['order_id'],$post_array['customer_id'], $post_array['payment_type'], $post_array['order_type'], $post_array['delivery_type'], $post_array['delivery_date'], $post_array['createdate']);
        return $post_array;
    }
    function insert_orders_fields($post_array,$primary_key)
    {
        $order_id = $primary_key;
        $distributor_id = $this->uri->segment(3);
        $this->spazapp_model->insert_save_orders_fields($order_id, $post_array);
        unset($post_array['order_id'],$post_array['customer_id'], $post_array['payment_type'], $post_array['order_type'], $post_array['delivery_type'], $post_array['delivery_date'], $post_array['createdate']);
        return $post_array;
    }
    function view_delivery_note($order_id){

      $data = array();
      $data['page_title'] = "Delivery Note: $order_id";

      $this->load->view('include/print_header', $data);
      $this->load->view('print/delivery_note', $data);
      $this->load->view('include/print_footer', $data);

    }

    function view_packing_slip($delivery_id){

        $data = array();

        $data['delivery_info'] = $this->logistics_model->get_delivery_all_info($delivery_id);

        $data['page_title'] = "Packing Slip: $delivery_id"; //: $delivery_id

        //$this->load->view('include/print_header', $data);
        //$this->load->view('print/packing_slip', $data);
        //$this->load->view('include/print_footer', $data);

        $this->show_view('packing_slip', $data);

    }


    function create_delivery(){
		
        $crud = new grocery_CRUD();
        
        $crud->set_table('deliveries');
        $crud->set_subject('Delivery');

        $crud->set_relation_n_n('orders', 'del_orders', 'orders', 'delivery_id', 'dist_order_id', 'id','priority',array('status' => 'Approved'));
        $crud->set_relation('distributor_id','distributors','company_name');

        $crud->columns('id','distributor_id','driver','truck','orders','total','weight','volume','date');

        $crud->callback_column('driver',array($this,'_callback_driver_name'));
        $crud->callback_column('truck',array($this,'_callback_truck_name'));


        $crud->display_as('total','Total (R)')->display_as('weight','Weight (Kg)')->display_as('volume','Volume (Ltr)');

        $this->session->set_userdata(array('table' => 'deliveries'));

        $crud->unset_add();

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $crud->add_action('Packing Slip', '', '/logistics/view_packing_slip','ui-icon-plus');
        $crud->add_action('Delivery Route', '', '/logistics/delivery_route','ui-icon-flag');
        $crud->add_action('Delivery Report', '', '/dashboard/delivery_info','ui-icon-flag');

        $crud->callback_after_insert(array($this, '_callback_update_orders'));

        $output = $crud->render();

        $output->page_title = 'Assign Approved Orders';

        $this->crud_view($output);
    }

    // Call Backs for create deliveries
    function _callback_driver_name($value, $row)
    {
     
        $driver = $this->order_model->get_driver_name($row->driver);
        if($driver)
        {
            return $driver->name;
        }
        else
        {
            return "No Driver";
        }

    }
   function _callback_truck_name($value, $row){
     
        $truck = $this->order_model->get_truck_name($row->truck);
        if($truck)
        {
            return $truck->licence_plate;
        }
        else
        {
            return "No Truck";
        }

    }
 
    function approved_order_item($order_id){
        $crud = new grocery_CRUD();
        
        $crud->set_table('order_items');
        $crud->set_subject('Order Items');
        $crud->where('order_id', $order_id);

        $crud->set_relation('order_id','orders','id');
        $crud->set_relation('product_id','products','name');

        $crud->unset_delete();
        $crud->unset_edit();
        $crud->unset_add();

       // $crud->add_action('More', '', 'demo/action_more','ui-icon-plus');
        
         $crud->fields('order_id','product_id','price','quantity');
         $crud->edit_fields('order_id','product_id','price','quantity');
         $crud->columns('order_id','product_id','price','quantity');

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'order_items'));

       // $crud->set_rules('quantity', 'Quantity','required|numeric|Larger than 0');

        $crud->callback_add_field('order_id',array($this,'_add_default_id'));
       // $crud->callback_add_field('distributor_order_id',array($this,'_add_distributor_order_id'));

        // Price field needs to be read only, it can't be changed

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $data['order_id'] = $order_id;
       // $output->data = $data;

        $output->page_title = 'Items from approved order id: '.$order_id;

        $this->crud_view($output);
    }
    
    function _callback_update_orders($post_array,$primary_key){
     
        if(isset($post_array['date']) && $post_array['date'] != ''){
            //$this->spazapp_model->update_order_deliveries($primary_key, $post_array['date']);
        }
        return true;
    }

    function _callback_add_image($value, $row){
        return '<a href="http://www.spazapp.co.za/images/'.$value.'" target="_blank"><img src="http://www.spazapp.co.za/images/'.$value.'" width="100" /></a>';
    }

    function _callback_order_items($value, $row){
        $count = $this->spazapp_model->get_order_item_count($row->id);
        return $count;
        
    }
    
    function _callback_approved_order_items($value, $row){
        
        $count = $this->spazapp_model->get_order_item_count($row->id);
        return '<a href="/logistics/approved_order_item/'.$row->id.'">'.$count.'</a>';
    }
    
    function _callback_order_customer_field($value = '', $primary_key = null){
     
        $results = $this->spazapp_model->get_order_field($primary_key);
        $results_ = $this->spazapp_model->get_order_field_();
        
        $input = "<select name='customer_id' class='chosen-select'>";
        $input .= "<option selected value='".$results['customer_id']."' >".$results['company_name']."</option>";
        foreach($results_ as $item){
           $input .= "<option value='".$item->id."' >".$item->company_name."</option>";
        }
        $input .= "</select>";
        return $input;
       
    }
    
    function _callback_order_type_field($value = '', $primary_key = null){
     
       $results = $this->spazapp_model->get_order_field($primary_key);
        return "<input type='text' value='".$results['order_type']."' name='order_type'>";
       
    }
    
    function _callback_order_payment_type_field($value = '', $primary_key = null){
     
        $payment_type = $this->spazapp_model->get_order_field($primary_key);
        $payment_type_ = $this->spazapp_model->get_order_all_payment_type();
        
        $input = "<select name='payment_type' class='chosen-select'>";
        $input .= "<option selected value='".$payment_type['payment_type']."' >".$payment_type['name']."</option>";
        
        foreach($payment_type_ as $item)
        { 
            $input .= "<option value='".$item->id."' >".$item->name."</option>";
        }
        $input .= "</select>";
        return $input;
    }
    
    function _callback_order_status_field($value = '', $primary_key = null){
     
        $results = $this->spazapp_model->get_order_field($primary_key);
        return "<input type='text' value='".$results['name']."' name='name'>";
       
    }
    
    function _callback_distributor_id_field(){
     
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        return "<input type='text' value='".$distributor_id."' name='distributor_id' readonly>";
       
    }
    
    function _callback_order_delivertype_field($value = '', $primary_key = null){
     
        $results = $this->spazapp_model->get_order_field($primary_key);
        return "<input type='text' value='".$results['delivery_type']."' name='delivery_type'>";
       
    } 
    
    function _callback_order_deliverdate_field($value = '', $primary_key = null){
     
        $results = $this->spazapp_model->get_order_field($primary_key);
        return "<input type='date' value='".$results['delivery_date']."' name='delivery_date'  required='required'>";
       
    }
    
    function _callback_order_createdatedate_field($value = '', $primary_key = null){
     
        $results = $this->spazapp_model->get_order_field($primary_key);
        return "<input type='date' data-date='' data-date-format='yy-mm-dt'  value='".$results['createdate']."' name='createdate' required='required'>";
       
    }
    
    function _callback_order_status_id_field($value = '', $primary_key = null){
     
        $results = $this->spazapp_model->get_order_status_id($primary_key);
        $results_ = $this->spazapp_model->get_order_all_status_id();
        
        $input = "<select name='status_id' class='chosen-select'>";
        $input .= "<option selected value='".$results['status_id']."' >".$results['name']."</option>";
        foreach($results_ as $item){
            $input .= "<option value='".$item->id."' >".$item->name."</option>";
        }
        $input .= "</select>";
        return $input;
    }
    
    function _callback_order_delivery($value, $row){
        $delivery_id = $this->spazapp_model->get_delivery_id($row->id);
        return '<a href="/logistics/create_delivery/edit/'.$delivery_id.'"target="_blank">'.$delivery_id.'</a>';
    }

    function _callback_order_total($value, $row){
        $total = $this->spazapp_model->get_order_total($row->id);
        return "R".$total;
    } 
    function _callback_order_items_quantity($value, $row){
        $order_count = $this->spazapp_model->get_order_items_quantity($row->id);
        return $order_count;
    } 
    
    function _callback_order_customer_id($value, $row){
        $customer_id = $this->spazapp_model->get_order_customer_id($row->id);
        return $customer_id;
    }
    function callback_order_customer_id($value, $row){
        $customer_id = $this->spazapp_model->get_order_customer_id_($row->id);
        return $customer_id;
    }
    function _callback_order_status($value, $row){
        $tatus = $this->spazapp_model->get_order_status($row->id);
        return $tatus;
    } function callback_order_status($value, $row){
        $tatus = $this->spazapp_model->get_order_del_status($row->id);
        return $tatus;
    }
    function _callback_order_deliverdate($value, $row){
        $delivery_date = $this->spazapp_model->get_order_deliverydate($row->oid,$row->status_id);
        return $delivery_date;
    }  
    function _callback_order_createdate($value, $row){
        $create_date = $this->spazapp_model->get_order_createdate($row->id);
        return $create_date;
    }
    function _callback_order_paymenttype($value, $row){
        $payment_type = $this->spazapp_model->get_order_payment_type($row->id);
        return $payment_type;
    }
    function callback_order_payment_type($value, $row){
        $payment_type = $this->spazapp_model->get_order_payment_type_($row->id);
        return $payment_type;
    }

    function track_insert($post_array,$primary_key){
        $catgory = 'management';
        $action = 'insert';
        $label = 'User added a new entry to the '.$this->session->userdata('table').' table';
        $value = $primary_key;
        $this->event_model->track_event($catgory, $action, $label, $value);
        $this->session->unset_userdata(array('table'));
    }

    function track_update($post_array,$primary_key){
        $catgory = 'management';
        $action = 'update';
        $label = 'User updated an entry in the '.$this->session->userdata('table').' table';
        $value = $primary_key;
        $this->event_model->track_event($catgory, $action, $label, $value);
        $this->session->unset_userdata(array('table'));
    }
    
    public function delivery_route($id) 
    {

        $distributor = $this->order_model->getDistributor($id);
        $distributor_id = $distributor->distributor_id;

        $distributorAddress = $this->order_model->getDistributorAddress($distributor_id);
        $address = $distributorAddress->address;

        $orders = $this->order_model->getDeliveryRoutes($id);

        $gpsPoints = array();

        foreach ($orders as $row)
        { 
            $gpsPoints[] = humanize($row['location_lat'].', '.$row['location_long']);
        }

        $this->load->library('googlemaps');
        $data['page_title'] = 'Delivery Route';

        $config['center'] = '-29.7171, 31.0542';
        $config['zoom'] = 'auto';
        $config['directions'] = TRUE;
        $config['directionsMode'] = "DRIVING";
        $config['directionsStart'] = $address;
        $config['directionsWaypointArray'] = $gpsPoints;
        $config['directionsEnd'] = $address;
        $config['directionsDivID'] = 'directionsDiv';
        $this->googlemaps->initialize($config);
        $data['map'] = $this->googlemaps->create_map();

        $this->show_view('distributor_delivery_route', $data);

    }

    public function trucks(){
     
        $crud = new grocery_CRUD();
        
     
        $crud->set_table('del_trucks');
     
        $crud->set_subject('Trucks');

        $crud->set_relation('distributor_id','distributors','company_name');
      
        $this->session->set_userdata(array('table' => 'del_trucks'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Distributor Delivery Trucks';

        $this->crud_view($output);
    }

    public function drivers()
    {
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('aauth_users');
        $crud->set_subject('Drivers');

        $crud->where('default_usergroup','18');     
       
        $crud->columns('user_link_id','name','cellphone','email');

        $crud->set_relation('user_link_id','distributors','company_name');

        // $crud->unset_delete();        
        // $crud->unset_add();
        // $crud->unset_edit();
        
        $output = $crud->render();

        $output->page_title = 'Distributor Truck Drivers';

        $this->crud_view($output);        
    }
}