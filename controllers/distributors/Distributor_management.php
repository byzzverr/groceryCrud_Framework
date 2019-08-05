<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Distributor_management extends CI_Controller{

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('spazapp_model');
        $this->load->model('customer_model');
        $this->load->model('order_model');
        $this->load->model('news_model');
        $this->load->model('delivery_model');

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

        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;

        $region = $this->customer_model->getDistributorRegion($distributor_id);
        $regions = $this->customer_model->getAllDistributorRegions($distributor_id);
        $region_id='';
        $comma = '';
        foreach ($regions as $key => $r) {
          
            $region_id .= $comma."'".humanize($r['region_id'])."'"; 
         
            $comma = ',';
        }
        if(empty($region_id)){
            $region_id=0;
        }
        
        if(!empty($region->company_name)){
            $distributor_name = $region->company_name; 
        }else{
            $distributor_name="";
        }
        
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('customers');
        $crud->set_subject('Customers');
      
        $where_in=" region_id IN ($region_id)";

        $crud->set_relation('customer_type','customer_types','name');
        $crud->set_relation('region_id','regions','name');

        $crud->columns('id','company_name','first_name','last_name','cellphone','store_picture','region_id','number_of_orders');

         $crud->where($where_in);

        $crud->callback_column('company_name',array($this,'_callback_store_name'));
        $crud->callback_column('store_picture',array($this,'_callback_customer_image'));
        $crud->callback_column('number_of_orders',array($this,'_callback_number_of_orders'));
        $crud->set_field_upload('store_picture','assets/uploads/customer');
        $crud->callback_after_upload(array($this,'create_crop'));
        
        $crud->unset_delete();
        $crud->unset_edit();
        $crud->unset_add();

        $this->session->set_userdata(array('table' => 'customers'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = $distributor_name.' Customers';

        $this->crud_view($output);
    }
    
    function _callback_store_name($primary_key, $row){

        $customer=$this->customer_model->get_customer($row->id);

        return "<a href='/distributors/distributor_management/customer_details/".$customer['id']."'>".$customer['company_name']."</a>";

    }
    function products_filter(){
        
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $distributor = $this->spazapp_model->get_distributor($distributor_id);
       
        $data['page_title'] = $distributor['company_name'] . ' Products';
       
        $this->load->view('include/header', $data);
        $this->load->view('include/nav/'. get_defult_page($this->user), $data);
         
        $this->load->view('reports/product_filter', $data); 
        $this->load->view('include/footer', $data);
    
    }

  
     function _callback_name($value = '', $primary_key = null){
        $product_id = $primary_key;
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $value = $this->product_model->get_product_basic_info($product_id, $distributor_id);
        return '<input id="field-unit_price" name="name" type="text" value="'.$value['name'].'" readonly>';

     }
    
    function _callback_category_id($value = '', $primary_key = null){
         $product_id = $primary_key;
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $value = $this->product_model->get_product_basic_info($product_id, $distributor_id);
        return '<input id="field-unit_price" name="category_id" type="text" value="'.$value['category_id'].'" readonly>';

     }
    function _callback_supplier_id($value = '', $primary_key = null){
         $product_id = $primary_key;
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $value = $this->product_model->get_product_basic_info($product_id, $distributor_id);
        return '<input id="field-unit_price" name="supplier_id" type="text" value="'.$value['supplier_id'].'" readonly>';

     }

    function products_extra_cargo(){
        $crud = new grocery_CRUD();
        
       
        $crud->set_table('products');
        $crud->set_subject('Product');
            
        $crud->set_relation('id','prod_dist_price','product_id',array('distributor_id' =>$distributor_id));
    
        $crud->set_relation('category_id','categories','name');
        $crud->set_relation('supplier_id','suppliers','company_name');
        $crud->set_relation('customer_type','customer_types','name'); 
           
        $crud->columns('name','category_id','supplier_id','unit_price','shrink_price','case_price','picture','special','distributor_id');   

        $crud->unset_delete();
        $crud->callback_column('picture',array($this,'_callback_add_image'));
        $crud->callback_column('sell_price',array($this,'_add_rand'));

        $crud->set_field_upload('picture','images/');

        $crud->callback_after_upload(array($this,'create_crop'));
     
        $this->session->set_userdata(array('table' => 'products'));

        $crud->callback_after_insert(array($this, 'track_insert'));

        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Products';

        $this->crud_view($output);
    }

    function products_tradehaus($distributor_id){
       
        $crud = new grocery_CRUD();
        
       
        $crud->set_table('products');
        $crud->set_subject('Product');
    
        $crud->set_relation('id','prod_dist_price','unit_price');
    

        $crud->set_relation('category_id','categories','name');
        $crud->set_relation('supplier_id','suppliers','company_name');
        $crud->set_relation('customer_type','customer_types','name'); 
           
        $crud->columns('name','category_id','supplier_id','unit_price','shrink_price','case_price','picture','special','distributor_id','id');
     
        $crud->unset_delete();
        $crud->callback_column('picture',array($this,'_callback_add_image'));
        $crud->callback_column('sell_price',array($this,'_add_rand'));

        $crud->set_field_upload('picture','images/');

        $crud->callback_after_upload(array($this,'create_crop'));
     
        $this->session->set_userdata(array('table' => 'products'));

        $crud->callback_after_insert(array($this, 'track_insert'));

        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Products';

        $this->crud_view($output);
    }
    
    function news(){

        
        try{
            $user_info = $this->aauth->get_user();
            //$company = $this->user_model->get_supplier($user_info->user_link_id);           
            $news_id = '';
            $data['page_title'] = 'Spazapp News';
            $data['results'] = $this->news_model->get_news_byid($news_id,get_defult_page($this->user));  
            $this->show_view('supplier/news',$data);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
       
   }

    
    function _callback_heading($value, $row){
    
       $results = $this->news_model->get_heading($row->id);
        return '<a href="'.base_url().'news/detailed/'.$row->id.'" target="_blank">'.$results.'</a>';
       
    }
    function view_news(){
       
    }
    
     function _callback_default_user_group($value = '', $primary_key = null){
    
        $usergroup_1 = $this->news_model->get_usergroup_by_id($primary_key);
        $usergroup_2 = $this->news_model->get_all_usergroup();
         
        if(!empty($usergroup_1['name'])){
            $usergroup = $usergroup_1['name'];
            $usergroup_id = $usergroup_1['id'];
        }else{
            $usergroup ='Select Value';
            $usergroup_id ='';
        }
         
        $input = "<select name='default_user_group' class='chosen-select'>";
        $input .= "<option selected value='".$usergroup_id."' >".$usergroup_1['name']."</option>";
        
        
        foreach($usergroup_2 as $item)
        {
          $input .= "<option value='".$item->id."'>".$item->name."</option>";
        }
        $input .= "<option value='All'>All</option>";
        $input .= "</select>";
        return $input;
     }
    function product_specials(){

        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $name = $this->order_model->getDistributorNameByID($distributor_id);
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('specials');
        $crud->set_subject('Product Special');
        $crud->where('distributor_id', $distributor_id);

        $suppliers = $this->spazapp_model->get_suppliers_per_distributor($distributor_id);
        $sups = '0';
        foreach ($suppliers as $key => $value) {
            $sups .= ','.$value['supplier_id'];
        }

        $crud->set_relation('distributor_id','distributors','company_name');
        $crud->set_relation('product_id','products','name',"supplier_id in ($sups)");
        
        $crud->set_relation('status_id','gbl_statuses','name');

        $crud->set_relation_n_n('regions', 'special_region_link', 'regions', 'special_id', 'region_id', 'name','priority');

        $crud->unset_delete();
        $crud->callback_column('picture',array($this,'_callback_add_image'));
        $crud->callback_column('sell_price',array($this,'_add_rand'));
     
        $crud->callback_field('distributor_id',array($this,'_callback_distributor_id'));
     
        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'specials'));

        $crud->callback_after_insert(array($this, 'track_insert'));

        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();
        if(!empty($name->company_name)){
            $company_name=$name->company_name;
        }else{
            $company_name='';
        }
        $output->page_title = $company_name.' Product Specials';

        $this->crud_view($output);
    }

    function _callback_distributor_id_column(){
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $name = $this->order_model->getDistributorNameByID($distributor_id);
        return $distributor_id;
    }

    function _callback_distributor_id(){
         $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $name = $this->order_model->getDistributorNameByID($distributor_id);
        return "<input value='".$distributor_id."' name='distributor_id' readonly>";
    }

    function orders(){
        $user_info = $this->aauth->get_user();
        $dist_id = $user_info->user_link_id;
        $company = $this->order_model->getDistributorNameByID($dist_id);

        $crud = new grocery_CRUD();
        
        $crud->set_table('orders');
        $crud->set_subject('Order');

        $crud->set_relation('id','distributor_orders','order_id');
        $crud->set_model('Grocery_crud_extend_model');
        
        $crud->set_relation('customer_id','customers','company_name');
        $crud->set_relation('payment_type','payment_types','name');
        $crud->set_relation('province','provinces','name');
        $crud->set_relation('region_id','regions','name');
        $crud->set_relation('status_id','gbl_statuses','name');
        $crud->set_relation('distributor_id','distributors','company_name');
        $crud->display_as('customer','customer_id');
        $crud->where('distributor_id', $dist_id);
       
        
        $crud->add_action('Resend Invoice', '', '','ui-icon-image',array($this,'callback_view_invoice'));
        $crud->add_action('Print Invoice', '', '','ui-icon-image',array($this,'callback_print_invoice'));
        $crud->add_action('View Comments', '', '','ui-icon-image',array($this,'callback_view_comments'));
        $crud->add_action('Edit Order Items', '', '','ui-icon-image',array($this,'callback_edit_order_items'));
        $crud->add_action('Mark as Delivered', '', '','ui-icon-image',array($this,'_call_back_mark_as_delivered'));

        $crud->callback_column('order_items',array($this,'_callback_order_items_quantity'));
        $crud->callback_column('total',array($this,'_callback_order_total'));
        $crud->callback_column('region',array($this,'_callback_order_region'));
        $crud->callback_column('distributor_id',array($this,'_callback_distributor_id_column'));
        $crud->callback_column('customer',array($this,'_callback_customer_id'));
        $crud->callback_column('dist_order_id',array($this,'_callback_dist_order_id'));

        $crud->columns('id','dist_order_id','customer','region_id','payment_type','status_id','order_items','total','createdate'); 

        $crud->order_by('createdate','desc');

        $crud->unset_delete();
        $crud->unset_edit();
        $crud->unset_add();

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'orders'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = $company->company_name. ' : Orders';

        $this->crud_view($output);
    }

    function callback_view_invoice($primary_key , $row){
        return site_url('/distributors/distributor_management/view_invoice').'/'.$row->dist_order_id;
    }

    function callback_print_invoice($primary_key , $row){
        return site_url('/distributors/distributor_management/print_invoice').'/'.$row->dist_order_id;
    }

    function callback_view_comments($primary_key , $row){
        return site_url('/distributors/distributor_management/view_comments').'/'.$row->dist_order_id;
    }
    
    function callback_edit_order_items($primary_key , $row){
        return site_url('/distributors/distributor_management/edit_order_items').'/'.$row->dist_order_id;
    }


    function _callback_dist_order_id($value , $row){
        $distributor_id = $this->spazapp_model->get_logged_in_distributor_id();
         return $this->order_model->get_distributor_order($row->id, $distributor_id['user_link_id'])['id'];
    } 

    function _callback_order_totals($value, $row){
        $total = $this->spazapp_model->get_order_total($row->id);
        return "R".$total;
    }  

    function _callback_customer_id($value, $row){
        $customer = $this->customer_model->get_customer($row->customer_id);
        return "<a href='/distributors/distributor_management/customer_details/".$row->id."'>".$customer['company_name']."</a>";
    }

    function _call_back_mark_as_delivered($primary_key , $row){
        $user_info = $this->aauth->get_user();
        $dist_id = $user_info->distributor_id;
        $company = $this->order_model->getDistributorNameByID($dist_id);
        return site_url('distributors/distributor_management/mark_as_delivered').'/'.$dist_id.'/'.$row->dist_order_id;
    }

    function mark_as_delivered($dist_id, $distributor_order_id){
        $this->delivery_model->distributor_order_delivered($distributor_order_id);
        redirect('/distributors/distributor_management/orders');
    }

    function _callback_regions($value, $row){

        $dist_id = $this->uri->segment(3);       
        $this->load->model('order_model');
        $query = $this->order_model->getOrderInformation($row->order_id,$dist_id);
        if(empty($query->region)){
            $region ='';
        }else{
            $region = $query->region;
        }
        
        
        return $region;
    }

    public function _callback_edit_order_url($value, $row)
    {
        return "<a href='".site_url('distributors/distributor_management/edit_order_price/'.$row->order_id.'/'.$row->id)."'>$value</a>";
        
    }
    function delivered_orders(){

        $user_info = $this->aauth->get_user();
        $dist_id = $user_info->user_link_id;
        $company = $this->order_model->getDistributorNameByID($dist_id);

        $crud = new grocery_CRUD();
        
        $crud->set_table('orders');
        $crud->set_subject('Order');

        $crud->set_relation('id','distributor_orders','order_id');
        $crud->set_model('Grocery_crud_extend_model');
        
        $crud->set_relation('customer_id','customers','company_name');
        $crud->set_relation('payment_type','payment_types','name');
        $crud->set_relation('province','provinces','name');
        $crud->set_relation('region_id','regions','name');
        $crud->set_relation('status_id','gbl_statuses','name');
        $crud->set_relation('distributor_id','distributors','company_name');
        $crud->display_as('customer','customer_id');
        $crud->where('distributor_id', $dist_id);
        $crud->where('j'.substr(md5('id'),0,8).'.status_id', '9');
       
        
        $crud->add_action('Resend Invoice', '', '','ui-icon-image',array($this,'callback_view_invoice'));
        $crud->add_action('Print Invoice', '', '','ui-icon-image',array($this,'callback_print_invoice'));
        $crud->add_action('View Comments', '', '','ui-icon-image',array($this,'callback_view_comments'));
        $crud->add_action('Edit Order Items', '', '','ui-icon-image',array($this,'callback_edit_order_items'));
        $crud->add_action('Mark as Delivered', '', '','ui-icon-image',array($this,'_call_back_mark_as_delivered'));

        $crud->callback_column('order_items',array($this,'_callback_order_items_quantity'));
        $crud->callback_column('total',array($this,'_callback_order_total'));
        $crud->callback_column('region',array($this,'_callback_order_region'));
        $crud->callback_column('distributor_id',array($this,'_callback_distributor_id_column'));
        $crud->callback_column('customer',array($this,'_callback_customer_id'));
        $crud->callback_column('dist_order_id',array($this,'_callback_dist_order_id'));

        $crud->columns('id','dist_order_id','customer','region_id','payment_type','status_id','order_items','total','createdate'); 

        $crud->order_by('createdate','desc');

        $crud->unset_delete();
        $crud->unset_edit();
        $crud->unset_add();

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'orders'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();
        if(!empty($company->company_name)){
            $company_name=$company->company_name;
        }else{
            $company_name='';
        }

        $output->page_title =  $company_name.' Delivered Orders';

        $this->crud_view($output);
    }
    
     function _callback_order_items_quantity($value, $row){
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;

        $order_count = $this->spazapp_model->get_distributor_order_items2($row->dist_order_id, $distributor_id);
        return '<a href="/distributors/distributor_management/order_item/'.$row->dist_order_id."/".$row->id.'/'.$distributor_id.'">'.$order_count.'</a>';
        //return $order_count;
    } 
     function insert_distributor_orders_fields($post_array,$primary_key)
    {
       
        $this->spazapp_model->insert_distributor_save_orders_fields($order_id, $post_array);
        unset($post_array['order_id'], $post_array['status_id']);
        return $post_array;
    }
    
    function _callback_order_distributor_id_field (){
        $user_info = $this->aauth->get_user();
        $dist_id = $user_info->distributor_id;
      
        return '<input type="text" name="order_id" value="'.$dist_id.'" readonly>';
    }
    function approve_orders_action($primary_key , $row)
    {
    return site_url('/distributors/distributor_management/approve_order').'/'.$row->id."/".$row->order_id;
    }
    function approve_order($id, $order_id){
        $this->spazapp_model->approve_distributor_order($id, $order_id);
        $data['page_title'] = "Crud action";
        $data['action'] = "Order $order_id has been approved";
        $data['back_link'] = "/distributors/distributor_management/orders";
        $this->show_view('crud_action', $data);
    }

    function order_items(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('order_items');
        $crud->set_subject('Order Items');

        $crud->set_relation('order_id','orders','id');
        $crud->set_relation('product_id','products','name');

        $crud->unset_delete();

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'order_items'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Order Items';

        $this->crud_view($output);
    }

    function order_item($distributor_order_id,$order_id){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('order_items');
        $crud->set_subject('Order Items');
        $crud->where('distributor_order_id', $distributor_order_id);
        //$crud->where('distributor_order_id', $dist_order_id);

        $crud->set_relation('order_id','orders','id');
        $crud->set_relation('product_id','products','name');

        $crud->columns('id','distributor_order_id','product_id','price','quantity','total');

        $crud->unset_delete();
       
        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'order_items'));

        //$crud->set_rules('quantity', 'Quantity','required|numeric|Larger than 0');
        $crud->callback_column('total', array($this,'_callback_order_item_total'));      
        $crud->callback_add_field('order_id',array($this,'_add_default_id'));
        $crud->callback_add_field('product_id',array($this,'_add_product_id'));
        $crud->callback_add_field('distributor_order_id',array($this,'_add_dist_order_id'));
        $crud->callback_add_field('price',array($this,'_add_price'));

        // Price field needs to be read only, it can't be changed

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->order_id = $order_id;
       
        $output->page_title = 'Order Items from Order Id : '.$order_id;

        $this->crud_view($output);
    }

    function _callback_order_item_total($value, $row) {
          $total = $row->quantity * $row->price;
          return round($total, 2);
    }
    
    function _add_product_id(){
    $order_id = $this->uri->segment(4);
    $distr_order_id = $this->uri->segment(5);
    $priduct_id = $this->uri->segment(6);
        
    
    if(isset($priduct_id)){
         $results_ = $this->spazapp_model->get_prodct_name_($priduct_id);
    }
    
      $input = "<select onchange='location = this.value;' name='product_id' class='chosen-select'>";
        
            
        foreach($results_ as $item)
            {
            if(isset($item->id)){
                $input .= "<option value='".$item->id."'>".$item->name."</option>";
             }
         
        }
       
       $results = $this->spazapp_model->get_prodct_name();  
        foreach($results as $item)
        {
          
            $input .= "<option value='/distributors/distributor_management/order_item/". $order_id."/".$distr_order_id."/".$item->id."/add' >".$item->name."</option>";
        }
    
        $input .= "</select>";
        return $input;
      
    }
    function _add_price(){
         $priduct_id = $this->uri->segment(6);
        $results = $this->spazapp_model->get_prodct_price($priduct_id);
        return "<input type='text' name = 'price' value='".$results."' readonly>";
    }
    function update_dob($post_array,$primary_key){
        $dates = explode('-', $post_array['dob']);

        $post_array['DOBDay'] = $dates[2];
        $post_array['DOBMonth'] = $dates[1];
        $post_array['DOBYear'] = $dates[0];

        return $post_array;
    }

    function _callback_add_image($value, $row){
        return '<a href="'.base_url().'images/'.$value.'" target="_blank"><img src="'.base_url().'images/'.$value.'" width="100" /></a>';
    }

    function _callback_customer_image($value, $row){
        return '<a href="'.base_url().'assets/uploads/customer/'.$value.'" target="_blank"><img src="'.base_url().'assets/uploads/customer/'.$value.'" width="50" height="50" /></a>';
    }  

    function _callback_number_of_orders($value, $row){
        $number_of_orders=$this->spazapp_model->get_customer_order_total($row->id);
        if($number_of_orders['total']>0){
            return "<a href='/distributors/distributor_management/customer_orders/".$row->id."'>".$number_of_orders['total']."</a>";
        }else{
            return '0';
        }
        
    }

    function _add_rand($value, $row){
        return 'R'.$value;
    }

    function _callback_order_items($value, $row){
        $count = $this->spazapp_model->get_order_item_count($row->id);
        return '<a href="/distributors/distributor_management/order_item/'.$row->id.'/'.$row->id.'">'.$count.'</a>';
    }

    function _callback_order_total($value, $row){
        $user_info = $this->aauth->get_user();
        $dist_id = $user_info->distributor_id;
        $total = $this->spazapp_model->get_dist_order_total($row->dist_order_id, $dist_id);
        return "R".$total;
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

    function fire_call_report(){
        $data['template'] = 'call_report';
        $data['message'] = array();
        $data['subject'] = 'SPAZAPP - Call Report';
        $data['message']['customers'] = $this->spazapp_model->get_reps_customers('19');
        $this->comms_model->send_email('mike@ldd.co.za', $data);
        $this->comms_model->send_email('tim@spazapp.co.za', $data);
        echo 'Call report Sent';
    }

    function view_email($template='call_report'){

        $data['message'] = array();
        $data['template'] = $template;
        $this->load->view('emails/'.$data['template'], $data['message'], false);

    }


    function set_createdate($post_array){

        $post_array['createdate'] = date("Y-m-d H:i:m");
        return $post_array;
    }

    function create_crop($uploader_response,$field_info, $files_to_upload)
    {

        $data = getimagesize('./images/'.$uploader_response[0]->name);
        
        $data['file_name'] = $uploader_response[0]->name;

        //set width and height here

        $cropped_width = 250;
        $cropped_height = 250;

        //Get image full size.
        $image_width = $data[0];
        $image_height = $data[1];
        $crop_x = 0;
        $crop_y = 0;

        if($image_width > $image_height){
            $ratio_calc = $image_height/$image_width;
            $new_width = $cropped_height*$ratio_calc;
            $ratio = $image_height / $cropped_height;
            $final_height = $image_height;
            $final_width = ($image_height/$cropped_height)*$cropped_width;
            if($image_width > $cropped_width){
                $crop_x = ($image_width-($cropped_width*$ratio))/2;
            }
        }else{
            $ratio_calc = $image_width/$image_height;
            $new_height = $cropped_width*$ratio_calc;
            $ratio = $image_width/$cropped_width;
            $final_width = $image_width;
            $final_height = ($image_width/$cropped_width)*$cropped_height;
            if($image_height > $cropped_height){
                $crop_y = ($image_height-($cropped_height*$ratio))/2;
            }
        }

        //calculate the difference in size between the original and the resized small one.

        //multiply the small values by the ratio
        $crop['p_crop_x'] = $crop_x;
        $crop['p_crop_y'] = $crop_y;
        $crop['p_crop_w'] = $final_width;
        $crop['p_crop_h'] = $final_height;

        $targ_w = $cropped_width;
        $targ_h = $cropped_height;
        $jpeg_quality = 90;
        $src = './images/'.$data['file_name'];

        $ext_explode = explode('.',$src);
        $ext = $ext_explode[count($ext_explode)-1];

        $src_new = str_replace('.'.$ext,'.jpg','./images/'.$data['file_name']);

        // Determine Content Type
        switch ($ext) {
            case "gif":
                $img_r = imagecreatefromgif($src);
                break;
            case "png":
                $img_r = imagecreatefrompng($src);
                break;
            case "jpeg":
            case "jpg":
                $img_r = imagecreatefromjpeg($src);
                break;
            default:
                $img_r = imagecreatefromjpeg($src);

        }

        $dst_r = ImageCreateTrueColor( $targ_w, $targ_h );
        imagecopyresampled($dst_r,$img_r,0,0,$crop['p_crop_x'],$crop['p_crop_y'],
        $targ_w,$targ_h,$crop['p_crop_w'],$crop['p_crop_h']);
        imagejpeg($dst_r,$src_new,$jpeg_quality);
    }

/*    function view_invoice($order_id){
        // Not working when we rescend invoice for now Unknown column 's.distributor_id' in 'field list'

        $this->spazapp_model->send_order_comms($order_id);
        $this->spazapp_model->place_distributor_orders($order_id);
        echo 'Invoice has been resent';
    }
*/
    function send_invoice($order_id){
        
            if(!empty($order_id)){
            $distributor = $this->aauth->get_user();          
            $distributor_id = $distributor->user_link_id;

            $this->load->model('spazapp_model');
            $this->spazapp_model->send_order_comms($order_id);

            $data['order_info'] = $this->order_model->get_dis_order_info($order_id);
            $data['distributor'] = $this->order_model->getDistributorInfo($distributor_id);
            
            $data['page_title'] = 'Order Invoice';

            $this->show_view('distributor_invoice_print', $data);

            }
    }

     function view_invoice($dist_order_id){


            $orders = $this->order_model->getOrderIdByDistOrderId($dist_order_id);
            $order_id = $orders['order_id'];
         
            $distributor_id = $orders['distributor_id'];

            $this->load->model('spazapp_model');
            $this->spazapp_model->send_order_comms($order_id);

            $this->spazapp_model->send_distributor_order_comms($dist_order_id,$distributor_id);

            $data['order_info'] = $this->order_model->get_dis_order_info($dist_order_id);
            $data['distributor'] = $this->order_model->getDistributorInfo($distributor_id);
            
            $data['page_title'] = 'Order Invoice';

            $this->show_view('distributor_invoice_print', $data);
            
    }

/*    function edit_order_invoice($order_id){
        // Not working when we rescend invoice for now Unknown column 's.distributor_id' in 'field list'
           
            $distributor = $this->aauth->get_user();          
            $distributor_id = $distributor->user_link_id;

            $this->load->model('spazapp_model');
            $this->spazapp_model->send_order_comms($order_id);
            $this->spazapp_model->send_distributor_order_comms($order_id);
        
            $data['order_info'] = $this->order_model->get_dis_order_info($order_id);
            $data['distributor'] = $this->order_model->getDistributorInfo($distributor_id);
            $data['page_title'] = 'Order Invoice';


    }*/

    function _add_default_id(){    
        return '<input id="field-order_id" name="order_id" type="text" value="'.$this->uri->segment(4).'" maxlength="10" readonly>';
        //return $return;
    }
    function _add_dist_order_id(){    
        return '<input id="field-order_id" name="distributor_order_id" type="text" value="'.$this->uri->segment(5).'" maxlength="10" readonly>';
        //return $return;
    }
    
    // Auto populate price on add order items
   
    function get_product_price($field_product_id){

        $this->load->model('logistics_model');
        $result = $this->logistics_model->get_product_price($field_product_id);
        $value = $result->price;
        echo $value;
    }

    // Callbacks For Distributors Orders Crud

    function _callback_delivery_type($value, $row){
        $user_info = $this->aauth->get_user();
        $dist_id = $user_info->distributor_id;
      
        $this->load->model('order_model');
        $query = $this->order_model->getOrderInformation($row->order_id,$dist_id);
        if(empty($query->delivery_type)){
            $delivery='';
        }else{
        $delivery = $query->delivery_type;    
        }
        
        return $delivery;
    }

    function _callback_createdate($value, $row){
        $user_info = $this->aauth->get_user();
        $dist_id = $user_info->distributor_id;
        $this->load->model('order_model');
        $query = $this->order_model->getOrderInformation($row->order_id,$dist_id);
        if(empty($query->createdate)){
        $cdate ='';
        }else{
        $cdate = $query->createdate;    
        }
        
        return $cdate;
    }

    function _callback_order_createdate($value, $row){
      
        $result = $this->event_model->get_order_info($row->id);
        
        return $result['createdate'];
    }

    function _callback_company_name($value, $row){
        $user_info = $this->aauth->get_user();
        $dist_id = $user_info->distributor_id;
        
        $this->load->model('order_model');
        $query = $this->order_model->getOrderInformation($row->order_id,$dist_id);
        if(empty($query->company_name)){
            $cname ='';
        }else{
            $cname = "<a href='/distributors/distributor_management/customer_details/".$query->id."'>".$query->company_name."</a>";
        }
        
        
        return $cname;
    }



    function _callback_payment_type($value, $row){
         $user_info = $this->aauth->get_user();
        $dist_id = $user_info->distributor_id;
        $cname='';
        $this->load->model('order_model');
        $query = $this->order_model->getOrderInformation($row->order_id,$dist_id);
        if(empty($query->name)){
            $pname='';
        }else{
        $pname = $query->name;    
        }
        
        return $pname;
    }

    function save_dist_pricing($post_array,$primary_key)
    {
        $product_id = $primary_key;
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $this->product_model->update_product_pricing($product_id, $distributor_id, $post_array);
        unset($post_array['unit_price'], $post_array['shrink_price'], $post_array['case_price'], $post_array['out_of_stock']);
        return $post_array;
    }

    function _callback_order_id($value, $row){
        $this->load->model('order_model');
        $query = $this->order_model->getOrderInformation($row->order_id);
        $order_id = $query->order_id;
        return $order_id;
    }

    function field_callback_unit_price($value = '', $primary_key = null)
    {
        $product_id = $primary_key;
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $prices = $this->product_model->get_product_prices($product_id, $distributor_id);
        return '<input id="field-unit_price" name="unit_price" type="text" value="'.$prices['unit_price'].'">';
    }
  

    function field_callback_shrink_price($value = '', $primary_key = null)
    {
        $product_id = $primary_key;
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $prices = $this->product_model->get_product_prices($product_id, $distributor_id);
        return '<input id="field-shrink_price" name="shrink_price" type="text" value="'.$prices['shrink_price'].'">';
    }

    function field_callback_case_price($value = '', $primary_key = null)
    {
        $product_id = $primary_key;
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $prices = $this->product_model->get_product_prices($product_id, $distributor_id);
        return '<input id="field-case_price" name="case_price" type="text" value="'.$prices['case_price'].'">';
    }

   
    
function products($action=''){
        
       
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->user_link_id;
        $distributor = $this->spazapp_model->get_distributor($distributor_id);
       
        $status = $this->uri->segment(4);
        if($status=='3'){
            $status = '0,1';
        }

        
        $product_id  = $this->product_model->get_dist_product_1($distributor_id,$status);
        if(empty($product_id)){
             $product_id  = '0';
        }
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('products');
        $crud->set_subject('Product');
        
        $crud->where('id IN ('.$product_id.')');  
        if($action!=''){
            $crud->set_relation_n_n('customer_types','prod_customer_type_link', 'customer_types',  'product_id','customer_type', 'name','priority');
        }
        
        $crud->columns('name','stock_code','unit_price','shrink_price','case_price','customer_types','picture','out_of_stock','region');

        $crud->unset_delete();
        $crud->unset_add();
     
        $crud->callback_column('out_of_stock',array($this,'callback_out_of_stock'));    
        $crud->callback_column('region',array($this,'callback_region'));
        $crud->callback_column('case_price',array($this,'_callback_case_price'));
        $crud->callback_column('sell_price',array($this,'_add_rand'));
        $crud->callback_column('unit_price',array($this,'_callback_unit_price'));
        $crud->callback_column('shrink_price',array($this,'_callback_shrink_price'));
        
        $crud->set_field_upload('picture','images/');

        $crud->callback_after_upload(array($this,'create_crop'));
     
        $crud->edit_fields('name','unit_price','shrink_price','case_price','out_of_stock','picture','customer_types');
        
        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'products'));

        $crud->callback_after_insert(array($this, 'track_insert'));

        $crud->callback_after_update(array($this, 'track_update'));
        $crud->callback_before_update(array($this, 'save_dist_pricing'));
        $crud->callback_field('name',array($this,'_callback_name'));
    
    
        $crud->callback_field('category_id',array($this,'_callback_category_id'));
        $crud->callback_field('supplier_id',array($this,'_callback_supplier_id'));
        $crud->callback_field('unit_price',array($this,'field_callback_unit_price'));
        $crud->callback_field('shrink_price',array($this,'field_callback_shrink_price'));
        $crud->callback_field('case_price',array($this,'field_callback_case_price'));
        $crud->callback_field('out_of_stock',array($this,'field_callback_out_of_stock'));

        $output = $crud->render();
  
        $output->page_title   =  $distributor['company_name'].' Products';
        $output->company_name =  $distributor['company_name'];

        $this->crud_view($output);
    }
    
    function field_callback_out_of_stock($value = '', $primary_key = null){
            $product_id = $primary_key;
            $user_info = $this->aauth->get_user();
            $distributor_id = $user_info->distributor_id;

            $out_of_stock = $this->product_model->get_out_of_stock($primary_key, $distributor_id);
         
            if($out_of_stock == 1){
                $value = 'Yes';
                $value2 = 'No';
                $out_of_stock2 = 0;
                
            }
            if($out_of_stock == 0){
                $value = 'No';
                $value2 = 'Yes';
                $out_of_stock2 =1;
               
            }

        return '<select id="field-category_id" name="out_of_stock" class="chosen-select" data-placeholder="Select Category id" style="width:300px">
                    <option value="'.$out_of_stock.'">'.$value.'</opion>
                    <option value="'.$out_of_stock2.'">'.$value2.'</opion>
                </select>';
    }  
   
    function _callback_unit_price($value, $row){
        
            $user_info = $this->aauth->get_user();
            $distributor_id = $user_info->distributor_id;
            $status_id = $this->uri->segment(4);
            $result = $this->product_model->get_dist_product($row->id,$distributor_id); 
            
            if(isset($result['unit_price'])){
               return $result['unit_price'];
            }

    }
    function _callback_shrink_price($value, $row)
    {
       
            $user_info = $this->aauth->get_user();
            $distributor_id = $user_info->distributor_id;
            $status_id = $this->uri->segment(4);
            $result = $this->product_model->get_dist_product($row->id, $distributor_id);


            if(isset($result['shrink_price'])){
               return $result['shrink_price'];
            }
    }
    function _callback_case_price($value, $row){
            $user_info = $this->aauth->get_user();
            $distributor_id = $user_info->distributor_id;
            $result = $this->product_model->get_dist_product($row->id, $distributor_id);

           if(isset($result['case_price'])){
            return $result['case_price'];
           }  
       
    }
    function callback_out_of_stock($value, $row){
        
            $user_info = $this->aauth->get_user();
            $distributor_id = $user_info->distributor_id;
            $result = $this->product_model->get_dist_product($row->id,$distributor_id);
        
            if($result['out_of_stock']==0){
                    $stock_status = "No";
            }
        
            if($result['out_of_stock']==1){
                    $stock_status = "Yes";
            }

            return $stock_status;
                
       
    }  
    function callback_name($value, $row){
        
            $user_info = $this->aauth->get_user();
            $distributor_id = $user_info->distributor_id;
            $result = $this->product_model->get_dist_product($row->id, $distributor_id);

            if(isset($result['name'])){

                 return $result['name'];

            }
    
    }
    
    function callback_stock_code($value, $row){
        
            $user_info = $this->aauth->get_user();
            $distributor_id = $user_info->distributor_id;
            $result = $this->product_model->get_dist_product($row->id, $distributor_id);

            if(isset($result['stock_code'])){
               return $result['stock_code'];
           }  
        
    }  
    
    function callback_region($value, $row) {
        
            $user_info = $this->aauth->get_user();
            $distributor_id = $user_info->distributor_id;
            $result = $this->product_model->get_dist_product($row->id, $distributor_id);

            if(isset($result['region'])){
               return $result['region'];
           }  

    }  
    
    function _callback_image($value, $row)
    {
        
            $user_info = $this->aauth->get_user();
            $distributor_id = $user_info->distributor_id;
            $result = $this->product_model->get_dist_product($row->id, $distributor_id);

            if(isset($result['stock_code'])){
               return '<a href="'.base_url().'images/'.$result['picture'].'" target="_blank"><img src="'.base_url().'images/'.$result['picture'].'" width="100" /></a>';
            }
        
        
    }
    
    // View Order Comments
    function view_comments($id){
            $crud = new grocery_CRUD();
                  
            $crud->set_table('order_comments');
            $crud->set_subject('Comments');
            $crud->where('distributor_order_id', $id);

            $crud->set_relation('user_id','aauth_users','name'); 

            $crud->columns('user_id','order_id','distributor_order_id','comment','createdate'); 

            $crud->callback_add_field('user_id',array($this,'_add_user_id'));
            $crud->callback_add_field('distributor_order_id',array($this,'_add_distributor_order_id'));
            $crud->callback_add_field('order_id',array($this,'_add_order_id')); 

            $crud->unset_delete();
            $crud->unset_edit();

            $output = $crud->render();

            $output->page_title = 'Order Comments';

            $this->crud_view($output);
    }

    function _add_user_id(){ 
            $distributor = $this->aauth->get_user();
            $user_id = $distributor->id;   
            return '<input id="field_user_id" name="user_id" type="text" value="'.$user_id.'" readonly>';
    }

    function _add_distributor_order_id()
    { 
            $distributor_order = $this->uri->segment(4);   
            return '<input id="field_distributor_order_id" name="distributor_order_id" type="text" value="'.$distributor_order.'" readonly>';
    }

    function _add_order_id(){ 
            $id = $this->uri->segment(4);
            $order = $this->order_model->get_Order_Id($id);
            $order_id = $order->order_id;
            return '<input id="field_order_id" name="order_id" type="text" value="'.$order_id.'" readonly>';
    }

    //Function to print Invoice for distributors
    public function print_invoice($order_id)
    {
        if($order_id != '')
        {
            $distributor = $this->aauth->get_user();          
            $distributor_id = $distributor->user_link_id;

            $data['order_info'] = $this->order_model->get_dis_order_info($order_id);
            $data['distributor'] = $this->order_model->getDistributorInfo($distributor_id);
            $data['page_title'] = 'Print Invoice';

            $this->show_view('distributor_invoice_print', $data);
        }
    }
    
    function distributor_product_allocation()
    {

        $distributor = $this->aauth->get_user();          
        $distributor_id = $distributor->user_link_id;
        //$data['distributor'] = $distributor;

        $category = $this->input->post("category");
        $sub_category = $this->input->post("sub_category");

        $data['categories'] = $this->product_model->getCategories();
        $data['distributor_id'] = $distributor_id;
        $data['distributor'] = $this->product_model->getDistributor($distributor_id);
        $data['page_title']  = 'Distributor Product Allocation';

        $this->show_view('distributor_products', $data);
    }
    

    function get_subcategories()
    {
        $category_id = $this->input->post('id');
        $sub_cats = $this->product_model->get_subcategories($category_id);
        $clean_cats = array();
        $clean_cats[0] = 'Please select...';
        foreach ($sub_cats as $key => $value) {
            $clean_cats[$value['id']] = $value['name'];
        }
        echo(json_encode($clean_cats));
    }

    function get_dis_prod_by_cat($category_id, $distributor_id)
    {

        $dis_products = $this->product_model->get_dis_prod_by_cat($category_id, $distributor_id);
        echo(json_encode($dis_products));
    }
    
  

      function save_dist_prod_alloc($distributor_id)
    {

        $products = $_POST;
        $info = '';
        $info1='';

        //this is a list of checked/not checked products.
        foreach ($products as $prodid => $p) 
        {
            $name='';
            //empty means it is NOT ticked
            if(empty($p))
            {
                //check if it existed.
                $process = $this->product_model->checkDistributorProducts($distributor_id, $prodid);
                $name = $this->product_model->getProductName($prodid);

                if($process)
                {
                    //it did exist means we should delete.
                    $remove = $this->product_model->deleteDistributorProduct($distributor_id, $prodid);
                    if($remove){
                        $info .= "<div class='alert alert-success' id='alert-success' style='color:red'><button type='button' class='close' data-dismiss='alert'>&times;</button> Product <strong>".$name."</strong> was removed from the list. </div>";

                    }else{
                        $info .= "<div class='alert alert-success' id='alert-success'><button type='button' class='close' data-dismiss='alert'>&times;</button> Product <strong>".$name."</strong> failed to be removed. </div>";
                    }

                }
                else
                {
                    $info1 .= "<div class='alert alert-success' id='alert-success' style='color:red'><button type='button' class='close' data-dismiss='alert'>&times;</button> Product <strong>".$name."</strong> had already been removed. </div>";
                }
            }
            else
            {
                //product WAS ticked. 

                $check = $this->product_model->checkDistributorProducts($distributor_id, $prodid);
                 $name = $this->product_model->getProductName($prodid);
                if($check)
                {
                    $info1 .= "<div class='alert alert-success' id='alert-success'><button type='button' class='close' data-dismiss='alert'>&times;</button> Product <strong>".$name."</strong> was already allocated. </div>";
                }
                else
                {

                    $data['distributor_id'] = $distributor_id;
                    $data['out_of_stock'] = "1";
                    $data['product_id'] = $prodid;
                    $data['unit_price'] = "0";
                    $data['shrink_price'] = "0";
                    $data['case_price'] = "0";

                    $insert = $this->product_model->insertDistributorProducts($data);

                    if($insert == "Incorrect information")
                    {
                        $info .= "<div class='alert alert-success' id='alert-success'><button type='button' class='close' data-dismiss='alert'>&times;</button> Product <strong>".$name."</strong> is missing distributor id or product id. </div>";
                    }
                    elseif($insert == "False") 
                    {
                        $info .= "<div class='alert alert-success' id='alert-success'><button type='button' class='close' data-dismiss='alert'>&times;</button> Product <strong>".$name."</strong> failed to insert. </div>";
                    }
                    else
                    {
                        $info .= "<div class='alert alert-success' id='alert-success'><button type='button' class='close' data-dismiss='alert'>&times;</button> Product <strong>".$name."</strong> was added successfully. </div>";
                    }

                }
            }
        }
        if(empty($info)){
            $info="<div class='alert alert-warning' id='alert-warning' style='color:gray'>Please tick the product or products you want  Add or Remove</div>";
        }
        print_r($info);
    }

    
    function edit_order_items($dist_order_id)
    {

        $distributor = $this->aauth->get_user();          
        $distributor_id = $distributor->user_link_id;
       
        $data['order_items']    = $this->order_model->get_orders_dist_order_id($dist_order_id);
        $data['distributor_id'] = $distributor_id;
        $data['page_title']     = 'Edit Order '. $dist_order_id;

        $this->show_view('edit_order_price', $data);
    }
    
    function update_order_items(){
        
        $order_items    = $_POST;
        $distributor    = $this->aauth->get_user();
        $user_id        = $distributor->id;
        $user_name      = $distributor->name;
        $distributor_order_id   ='';
        $order_id               ='';
        $item_ids       = explode('|', $_POST['item_ids']); 
       
        foreach ($item_ids as $item_id) {
      
            if(is_numeric($item_id) && $order_items['percent_'.$item_id] != 0){
                
                //go get dist_order_id and order_id
                $results                = $this->order_model->getDistOrder($item_id);
                $distributor_order_id   = $results['distributor_order_id'];
                $order_id               = $results['order_id'];
                $price          = $order_items['price_'.$item_id];
                $quantity       = $order_items['quantity_'.$item_id];
                $original_price = $order_items['original_price_'.$item_id];
                $percent        = $order_items["percent_".$item_id];
                $this->order_model->update_order_item($item_id, $price, $quantity);
                $comment        = "$percent% discount was applied to item_id: $item_id by $user_name.";
                
                $this->order_model->add_order_comment($order_id, $distributor_order_id, $comment, $user_id);
              
              
           }

        }
        
        //This is to send invoice if ($order_items['percent_'.$item_id] != 0)
        if(!empty($order_id)){
            $this->send_invoice($order_id);
         
        }
  
        
    }

    function customer_details($customer_id){
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);
        $data['province']=$this->customer_model->get_province($data['customer_info']['province']);
        $data['customer_info']=$this->customer_model->get_customer($customer_id);

        $this->load->library('googlemaps');

        $config['center'] = $data['customer_info']['location_lat']. ', '.$data['customer_info']['location_long'];
        $config['zoom'] = '9';

        $this->googlemaps->initialize($config);

        $marker = array();
         if(!empty($data['customer_info']['store_picture'])){
            $store_picture='<img src="/assets/uploads/customer/'.$data['customer_info']['store_picture'].'" alt="Store Picture" height="90" width="90"><br />';
        }else{
            $store_picture='<img src="/assets/uploads/customer/no_photo.jpg" alt="Store Picture" height="90" width="90"><br />';
        }
        $marker['position'] = $data['customer_info']['location_lat']. ', '.$data['customer_info']['location_long'];
        $marker['draggable'] = true;
        $marker['infowindow_content'] = $store_picture.'<strong>'.$data['customer_info']['company_name']. '</strong>&nbsp; <a href="/distributors/distributor_management/street_view?q='.$data['customer_info']['id'].'"><i class="fa fa-male"></i></a> <br /><strong>Address </strong>: '.$data['customer_info']['address'].'<br /><strong> Suburb </strong>: '.$data['customer_info']['suburb'].'<br/>'.$data['province']['name'].'<br/><strong>Latitude </strong> : '.$data['customer_info']['location_lat'].'<br/><strong>Longitude </strong>: '.$data['customer_info']['location_long'];
        $marker['animation'] = 'DROP';
        $marker['icon'] = '/assets/img/custom_map_icon.png';

        $this->googlemaps->add_marker($marker);
  
        $data['map'] = $this->googlemaps->create_map();
        $data['page_title']="Customer Details";

        $this->show_view('customer_details',$data);
    }

    public function street_view()
    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);
        $id = $_GET['q'];

        $customer = $this->customer_model->getCustomerDetailsById($id);
        $this->load->library('googlemaps');
        $config['center'] = $customer->location_lat.', '.$customer->location_long;
        $config['map_type'] = 'STREET';
        $config['streetViewPovHeading'] = 90;
        $this->googlemaps->initialize($config);

        $data['map'] = $this->googlemaps->create_map();
        $data['page_title'] = $customer->company_name.' Street View';
        $data['customer'] = $customer;
        $this->show_view('street_view', $data);
    }

    function customer_orders($customer_id){
        $distributor = $this->aauth->get_user();          
        $distributor_id = $distributor->user_link_id;
       
        $orders=$this->spazapp_model->get_customer_order_total($customer_id);

        $comma='';
        $order_id='';
        foreach ($orders as $r) {
        
          $order_id.=$comma."'".$r['id']."'";
          $comma=',';

        }
      
        $where_in="order_id IN ($order_id)";

        $crud = new grocery_CRUD();
        
        $crud->set_table('distributor_orders');
        $crud->set_subject('Order');
        $crud->where($where_in);
         $crud->where('distributor_id', $distributor_id);
      
        $crud->set_relation('status_id','gbl_statuses','name');
        $crud->set_relation('order_id','orders','id'); 

        $crud->columns('id', 'company_name','regions','payment_type','status_id','delivery_type','order_items','total','createdate'); //'order_items','total',

        $crud->display_as('customer_id','Customer');
        $crud->display_as('status_id','Status');
        $crud->display_as('company_name','Store name');

        $crud->add_action('Approve Order', '', '','ui-icon-plus',array($this,'approve_orders_action'));
        $crud->add_action('Resend Invoice', '', 'distributors/distributor_management/view_invoice');
        $crud->add_action('Print Invoice', '', 'distributors/distributor_management/print_invoice');
        $crud->add_action('View Comments', '', 'distributors/distributor_management/view_comments');
        $crud->add_action('Edit Order Items', '', 'distributors/distributor_management/edit_order_items'); 

        $crud->callback_column('order_items',array($this,'_callback_order_items_quantity'));
        $crud->callback_column('total',array($this,'_callback_order_total'));
        $crud->callback_column('delivery_type',array($this,'_callback_delivery_type'));
        $crud->callback_column('createdate',array($this,'_callback_createdate'));
        $crud->callback_column('company_name',array($this,'_callback_company_name'));
        $crud->callback_column('payment_type',array($this,'_callback_payment_type'));
        $crud->callback_column('order_id',array($this,'_callback_order_id'));
        $crud->callback_column('regions',array($this,'_callback_regions'));
     
        $crud->add_action('Mark as Delivered', '', '','ui-icon-image',array($this,'_call_back_mark_as_delivered'));
     
        $crud->order_by('createdate','desc');

        $crud->unset_delete();
        $crud->unset_add();
        

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'distributor_orders'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));
        $crud->callback_before_insert(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title =  $this->customer_model->get_customer($customer_id)['last_name'].' Orders';
        $output->customer_name = $this->customer_model->get_customer($customer_id)['last_name'];

        $this->crud_view($output);
    }
}