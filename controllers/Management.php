<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Management extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->library('comms_library');
        $this->load->library('javascript_library');
        $this->load->model('event_model');
        $this->load->model('spazapp_model');
        $this->load->model('customer_model');
        $this->load->model('product_model');
        $this->load->model('insurance_model');
        $this->load->model('news_model');
        $this->load->model('delivery_model');
        $this->load->model('qrcode_model');

        $this->user = $this->aauth->get_user();
        $this->app_settings = get_app_settings(base_url());

        //redirect if not logged in
        if (!$this->aauth->is_loggedin())
        {
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Management'))
        {
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

    function index($region_id='0')
    {
        
        $crud = new grocery_CRUD();
  
        
        $crud->set_language("english");
        $crud->set_table('customers');
        $crud->set_subject('Customers');
     
        $crud->set_relation('customer_type','customer_types','name');
        $crud->set_relation('region_id','regions','name');
        $crud->set_relation('province','provinces','name');
     
        if(($region_id > 0)){
            $crud->where('customers.region_id',$region_id);
        }
        $crud->columns('first_name','last_name','cellphone','company_name','address','province','region_id','trader_id','createdate');
        $crud->callback_column('store_picture',array($this,'_callback_customer_image'));
        $crud->callback_column('trader_id',array($this,'_callback_trader_id'));
        $crud->set_field_upload('store_picture','assets/uploads/customer');
        $crud->set_field_upload('inner_store_image','assets/uploads/inner_store_image');
        $crud->set_field_upload('outer_store_image','assets/uploads/outer_store_image');
        $crud->callback_after_upload(array($this,'create_crop'));
        $crud->order_by('createdate','DESC');
    
        $this->session->set_userdata(array('table' => 'customers'));

         $crud->set_lang_string('update_success_message',
             'Your data has been successfully stored into the database.<br/>
             Please wait while you are redirecting to the list page.
             <script type="text/javascript">
              window.location = "'.site_url(strtolower(__CLASS__).'/'.strtolower("customers")).'";
             </script>
             <div style="display:none">');

        $crud->unset_back_to_list();
        $crud->callback_after_insert(array($this, 'track_insert_customer'));
        $crud->callback_after_update(array($this, 'track_update'));
        $crud->callback_after_delete(array($this, 'deleted_customer'));
        
        $output = $crud->render();

        $output->page_title = 'Spazapp Customers';

        $this->crud_view($output);

       

    }

    function _callback_parent_name($value, $row){
        $parent = $this->spazapp_model->get_region($row->parent_id);
        return $parent['name'];
    }

    function _callback_location($value,$row){
         error_reporting(0);
             $url = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='.trim($row->location_lat).','.trim($row->location_long).'&sensor=false';
           
             $json = @file_get_contents($url);
             $data=json_decode($json);
             if(isset($data->status)){
                 $status = $data->status;
             }else{
                $status='';
             }
            
             if($status=="OK")
             {
               return $data->results[0]->formatted_address;
             }
             else
             {
               return "Not found";
             }
    }

    
    function traders()
    {
        $crud = new grocery_CRUD();
        
        $crud->set_table('traders');
        $crud->set_subject('Sparks');
        $crud->set_relation('region_id','regions','name');
        $crud->set_relation('province','provinces','name');

        $crud->columns('id','first_name','last_name','cellphone','status','region_id','province');
        // $crud->display_as('cellphone',"username");

       // $crud->callback_column('trader_picture',array($this,'_callback_trader_image'));
        // $crud->callback_column('username',array($this,'_callback_username'));
        // $crud->set_field_upload('trader_picture','assets/uploads/trader');
        $crud->callback_after_upload(array($this,'create_crop'));
        $crud->add_action('Registrations History', '', 'dashboard/trader_details');
        $crud->add_action('Location History', '', 'dashboard/single_spark_location');
        //$crud->unset_delete();

        $this->session->set_userdata(array('table' => 'traders'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));
        $crud->callback_after_delete(array($this, 'deleted_user'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Sparks';

        $this->crud_view($output);
    }

   
    function global_statuses()
    {
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('gbl_statuses');
        $crud->set_subject('Global Statuses');

        $crud->columns('id','name','parent');

        $this->session->set_userdata(array('table' => 'gbl_statuses'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Global Statuses';

        $this->crud_view($output);
    }

    function regions()
    {
         
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('regions');
        $crud->set_subject('Region');

        $crud->set_relation('parent_id','regions','name');
        $crud->set_relation('id','regions','name');//This is the search fix since we have parent_id joining regions to regions  
        $crud->set_relation('province_id','provinces','name');
        
         $crud->unset_delete();
     
        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'regions'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));
        $crud->fields('parent_id','name','province_id');

        $crud->display_as('id','Name');
        $crud->columns('parent_id','id','province_id','location_lat','location_long');
        
        $crud->callback_after_insert(array($this, 'update_region_locations'));
        $crud->callback_after_update(array($this, 'update_region_locations'));
        
        $output = $crud->render();

        $output->page_title = 'Spazapp Regions';

        $this->crud_view($output);
    }
    function update_region_locations($post_array,$primary_key)
    {
        
        $this->spazapp_model->update_locations($primary_key, $post_array);
        unset($post_array['parent_id'],$post_array['name']);
        return $post_array;
    }
  
    function all_distributors()
    {
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('distributors');
        $crud->set_subject('Distributor');

        $crud->columns('id','company_name','contact_name','products','number','email','address');
        $crud->display_as('contact_name','Contact Person'); 
        $crud->display_as('number','Phone Number'); 

        $crud->callback_column('company_name',array($this,'_callback_distributor_orders')); 
        $crud->callback_column('products',array($this,'_callback_addproducts_url'));   

        $crud->unset_delete();
        $crud->unset_add();
        $crud->unset_edit();

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'distributors'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Distributors Products';

        $this->crud_view($output);
    }

    public function _callback_addproducts_url($value, $row)
    {
        return "<a href='".site_url('management/select_products/'.$row->id)."'>Select Products</a>";
    }

    public function select_products($distributor_id)
    {
        $data['distributor'] = $this->product_model->getDistributor($distributor_id);
        $data['distributor_id'] = $distributor_id;
        $category = $this->input->post("category");
        $sub_category = $this->input->post("sub_category");

        $data['categories'] = $this->product_model->getParentCategories();

        $data['page_title']  = 'Distributor Product Allocation';

        $this->show_view('distributor_products_admin', $data);        
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

    function distributors(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('distributors');
        $crud->set_subject('Distributor');


        $crud->set_relation_n_n('regions', 'dist_region_link', 'regions', 'distributor_id', 'region_id', 'name','priority');
        $crud->set_relation_n_n('suppliers', 'dist_supplier_link', 'suppliers', 'distributor_id', 'supplier_id', 'company_name','priority');

        $crud->columns('company_name','contact_name','number','email','address','picture','suppliers','regions');
//      $crud->display_as('fName','Name');
//      $crud->display_as('lName','Last Name');
        $crud->callback_column('picture',array($this,'_callback_distributor_image')); 
        $crud->set_field_upload('picture','assets/uploads/distributors/');    
        $crud->add_action('Resend Daily Specials', '', 'daily_cron/send_daily_sales');

        $crud->unset_delete();
     
        //$crud->fields('fName','lName','Email','Cellphone','dob', 'DOBDay','DOBMonth','DOBYear', 'cellSubscribe','emailSubscribe','birthdayMessaging','reminderMessaging','marketingMessaging');
        //$crud->required_fields('fName','lName');

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'distributors'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Distributors';

        $this->crud_view($output);
    }
   
    function suppliers(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('suppliers');
        $crud->set_subject('Supplier');

        $crud->set_relation('customer_type','customer_types','name');
        $crud->set_relation_n_n('distributors', 'dist_supplier_link', 'distributors', 'supplier_id', 'distributor_id', 'company_name','priority');


//        $crud->columns('customer_id_id','first_name','last_name','UserName','ShopType','rewards');
//        $crud->display_as('fName','Name');
//        $crud->display_as('lName','Last Name');     

        $crud->unset_delete();
     
        //$crud->fields('fName','lName','Email','Cellphone','dob', 'DOBDay','DOBMonth','DOBYear', 'cellSubscribe','emailSubscribe','birthdayMessaging','reminderMessaging','marketingMessaging');
        //$crud->required_fields('fName','lName');

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'suppliers'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Suppliers';

        $this->crud_view($output);
    }

    function categories(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('categories');
        $crud->set_subject('Categories');

        $crud->set_relation('parent_id','categories','name');

//        $crud->columns('customer_id_id','first_name','last_name','UserName','ShopType','rewards');
//        $crud->display_as('fName','Name');
//        $crud->display_as('lName','Last Name');     

        $crud->unset_delete();
        $crud->set_field_upload('icon','assets/uploads/categories');
     
        //$crud->fields('fName','lName','Email','Cellphone','dob', 'DOBDay','DOBMonth','DOBYear', 'cellSubscribe','emailSubscribe','birthdayMessaging','reminderMessaging','marketingMessaging');
        //$crud->required_fields('fName','lName');

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'categories'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Categories';

        $this->crud_view($output);
    }

    function products($action=''){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('products');
        $crud->set_subject('Product');

        
        //$crud->set_relation('category_id','categories','name');
        $crud->set_relation('supplier_id','suppliers','company_name');
        $crud->set_relation('category_id','categories','name');

        if($action!=''){
            $crud->set_relation_n_n('customer_types','prod_customer_type_link', 'customer_types',  'product_id','customer_type', 'name','priority');
        }
        
        $crud->columns('stock_code','barcode','name','category_id','supplier_id','picture');
        
        $crud->edit_fields('stock_code','barcode','barcode_secondary','name','category_id','supplier_id','description', 'nutritional_info', 'directions_warnings','customer_types','picture','qty','no_of_shrink_in_case','pack_size','units','editdate','createdate');
        
        $crud->fields('stock_code','barcode','barcode_secondary','name','category_id','supplier_id',
            'description', 'nutritional_info', 'directions_warnings',
            'customer_types','picture','qty','no_of_shrink_in_case','pack_size','units','editdate','createdate');
        
        $crud->display_as('qty','No of Units in Shrink');  
        
         $crud->unset_texteditor('description','nutritional_info','directions_warnings');

        $crud->unset_delete();
       
        $crud->callback_column('picture',array($this,'_callback_add_image'));
        $crud->callback_column('qrcode',array($this,'_callback_qrcode'));
        $crud->callback_column('customer_type',array($this,'_callback_customer_type'));
     
        $crud->set_field_upload('picture','images/');

        $crud->callback_after_upload(array($this,'create_crop'));
     
        //$crud->fields('fName','lName','Email','Cellphone','dob', 'DOBDay','DOBMonth','DOBYear', 'cellSubscribe','emailSubscribe','birthdayMessaging','reminderMessaging','marketingMessaging');
        //$crud->required_fields('fName','lName');

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'products'));

        // $crud->set_lang_string('update_success_message',
        //      'Your data has been successfully stored into the database.<br/>
        //      Please wait while you are redirecting to the list page.
        //      <script type="text/javascript">
        //       window.location = "'.site_url(strtolower(__CLASS__).'/'.strtolower("spazapp_products")).'";
        //      </script>
        //      <div style="display:none">');

        // $crud->unset_back_to_list();

        $crud->callback_after_insert(array($this, 'track_insert'));
        //$crud->callback_after_insert(array($this, 'qrcode'));

        $crud->callback_after_update(array($this, 'track_update'));
        //$crud->callback_after_update(array($this, 'qrcode'));

        $output = $crud->render();

        $output->page_title = 'Spazapp  Products';
        $output->company_name = 'Spazapp';

        $this->crud_view($output);
    }

    function qrcode($post_array, $primary_key){
        $data['action']="generate_qrcode";
        $data['id']=$primary_key;
        $data['qr_text']=$post_array['barcode'];
        $this->qrcode_model->generate_qrcode($data);
    }

    function _callback_customer_type($value, $row){
        return "Test";
    }

    function _callback_category_id($value, $row){
        $cat=$this->product_model->getCagoriesByParentId($row->category_id);
        return $cat['name'];
    }

function distributor_products($distributor_id){
            
        
        $user_info = $this->aauth->get_user();
   
        $distributor = $this->spazapp_model->get_distributor($distributor_id);
    
       
        $status = $this->uri->segment(4);
        if($status=='3'){
            $status = '0,1';
        }
        $product_id  = $this->product_model->get_dist_product_1($distributor_id,$status);

        if(empty($product_id)){
            $product_id='0';
        }

        $crud = new grocery_CRUD();
        $crud->set_model('Crud_distributor_products_model');
        
        $crud->set_table('products');
        $crud->set_subject('Product');
        // state
        $state = $crud->getState();
        if($state == 'list'){
        $crud->set_relation_n_n('customer_types','prod_customer_type_link', 'customer_types',  'product_id','customer_type', 'name','priority');
        }
      
        $crud->where('products.id IN ('.$product_id.')');  
   
        $crud->columns('name','customer_types','stock_code','unit_price','shrink_price','case_price','picture','out_of_stock','region');

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
     
        $crud->edit_fields('name','stock_code','unit_price','shrink_price','case_price','out_of_stock','picture');
        
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
  
        $output->page_title   =  $distributor['company_name'].' - Products';
        $output->company_name =  $distributor['company_name'];

        $this->crud_view($output);
    }
    function _callback_name($value = '', $primary_key = null){
        $product_id = $primary_key;
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        $value = $this->product_model->get_product_basic_info($product_id, $distributor_id);
        return '<input id="field-unit_price" name="name" type="text" value="'.$value['name'].'" readonly>';

     }
  function field_callback_out_of_stock($value = '', $primary_key = null){
            $distributor_id = $this->uri->segment(3);

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
        
            $distributor_id = $this->uri->segment(3);
            $result = $this->product_model->get_dist_product($row->id,$distributor_id); 
           
            if(isset($result['unit_price'])){
               return $result['unit_price'];
            }

    }
    function _callback_shrink_price($value, $row)
    {
       
            $distributor_id = $this->uri->segment(3);
            $result = $this->product_model->get_dist_product($row->id, $distributor_id);


            if(isset($result['shrink_price'])){
               return $result['shrink_price'];
            }
    }
    function _callback_case_price($value, $row){
            $distributor_id = $this->uri->segment(3);
            $result = $this->product_model->get_dist_product($row->id, $distributor_id);

           if(isset($result['case_price'])){
            return $result['case_price'];
           }  
       
    }
    function callback_out_of_stock($value, $row){
            $distributor_id = $this->uri->segment(3);
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
        
            $distributor_id = $this->uri->segment(3);
            $result = $this->product_model->get_dist_product($row->id, $distributor_id);

            if(isset($result['name'])){

                 return $result['name'];

            }
    
    }
    
    function callback_stock_code($value, $row){
        
            $distributor_id = $this->uri->segment(3);
            $result = $this->product_model->get_dist_product($row->id, $distributor_id);

            if(isset($result['stock_code'])){
               return $result['stock_code'];
           }  
        
    }  
    
    function callback_region($value, $row) {
        
            $distributor_id = $this->uri->segment(3);
            $result = $this->product_model->get_dist_product($row->id, $distributor_id);

            if(isset($result['region'])){
               return $result['region'];
           }  

    }  
    
    function _callback_image($value, $row)
    {
        
            $user_info = $this->aauth->get_user();
            $distributor_id = $user_info->distributor_id;
            $status_id = $this->uri->segment(3);
            $result = $this->product_model->get_dist_product($row->id, $distributor_id);

            if(isset($result['stock_code'])){
               return '<a href="'.base_url().'images/'.$result['picture'].'" target="_blank"><img src="'.base_url().'images/'.$result['picture'].'" width="100" /></a>';
            }
        
        
    }
    function save_dist_pricing($post_array,$primary_key)
    {
        $product_id = $primary_key;
        $distributor_id = $this->uri->segment(3);
        $this->product_model->update_product_pricing($product_id, $distributor_id, $post_array);
        unset($post_array['unit_price'], $post_array['shrink_price'], $post_array['case_price'], $post_array['out_of_stock']);
        return $post_array;
    }

    function field_callback_unit_price($value = '', $primary_key = null)
    {
        $product_id = $primary_key;
        $distributor_id = $this->uri->segment(3);
        $prices = $this->product_model->get_product_prices($product_id, $distributor_id);
        return '<input id="field-unit_price" name="unit_price" type="text" value="'.$prices['unit_price'].'">';
    }

    function field_callback_shrink_price($value = '', $primary_key = null)
    {
        $product_id = $primary_key;
        $distributor_id = $this->uri->segment(3);
        $prices = $this->product_model->get_product_prices($product_id, $distributor_id);
        return '<input id="field-shrink_price" name="shrink_price" type="text" value="'.$prices['shrink_price'].'">';
    }

    function field_callback_case_price($value = '', $primary_key = null)
    {
        $product_id = $primary_key;
        $distributor_id = $this->uri->segment(3);
        $prices = $this->product_model->get_product_prices($product_id, $distributor_id);
        return '<input id="field-case_price" name="case_price" type="text" value="'.$prices['case_price'].'">';
    }

  

    function product_specials(){
       
        $crud = new grocery_CRUD();
        
        $crud->set_table('specials');
        $crud->set_subject('Product Special');

        $crud->set_relation('distributor_id','distributors','company_name');
        $crud->set_relation('product_id','products','name');
        $crud->set_relation('status_id','gbl_statuses','name');
        $crud->set_relation_n_n('regions', 'special_region_link', 'regions', 'special_id', 'region_id', 'name','priority');
       
        $crud->unset_delete();
        $crud->callback_column('picture',array($this,'_callback_add_image'));
        $crud->callback_column('sell_price',array($this,'_add_rand'));
     
        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'specials'));

        $crud->callback_after_insert(array($this, 'track_insert'));

        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Product Specials';

        $this->crud_view($output);
    }
     function _callback_distributorid($value = '', $primary_key = null){
//           $distributor_id = $this->uri->segment(4);
//        
//            $comma ='';
//            $values = '';
//            $value ='';
//            $equal='';
//        
//        if(!empty($distributor_id)){
//            $region_s = $this->product_model->get_regions_by_id($distributor_id); 
//             
//
//            foreach ($region_s as  $item) {
//                $item->region_id;
//                $value .= $comma.$item->region_id;
//                $comma = ',';
//                $equal = '=>';
//             }
//        
//        }
//        
//        if(!empty($value)){
//        $WHERE = 'id IN ('.$value.')';   
//            
//        $crud->set_relation_n_n('regions', 'special_region_link', 'regions', 'special_id', 'region_id', 'name','priority',$WHERE);
//        }else{
         //   }
        $distributor_id = $primary_key;
     
             $distributor_res1 = $this->spazapp_model->get_distributor($distributor_id);
         
             $company_name = $distributor_res1['company_name'];
             $dist_id = $distributor_res1['id'];
         
          if(empty($company_name)){
             $company_name ='Select distributor';
             $dist_id ='';
         }
         
         
        $distributor_res2 = $this->product_model->get_distributor();
        
        
        $input = '<select name="distributor_id" onchange="location = this.value" required>';
     
        $input .= "<option selected value='".$dist_id."'>".$company_name."</option>";
         
        foreach($distributor_res2 as $item)
        {
            $input .= "<option value='".$item->id."'>".$item->company_name."</option>";
        }
          
        $input .= "</select>";
         
            
        return $input;
     }
    

        function tavern_association_products(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('products');
        $crud->set_subject('Product');
        $crud->where('supplier_id',5);

        $crud->set_relation('category_id','categories','name');
        $crud->set_relation('supplier_id','suppliers','company_name');
        $crud->set_relation('customer_type','customer_types','name');

        $crud->columns('name','category_id','supplier_id','sell_price','picture','special');
//        $crud->display_as('fName','Name');
//        $crud->display_as('lName','Last Name');     

        $crud->unset_delete();
        $crud->callback_column('picture',array($this,'_callback_add_image'));

        $crud->set_field_upload('picture','images/');

        $crud->callback_after_upload(array($this,'create_crop'));
     
        //$crud->fields('fName','lName','Email','Cellphone','dob', 'DOBDay','DOBMonth','DOBYear', 'cellSubscribe','emailSubscribe','birthdayMessaging','reminderMessaging','marketingMessaging');
        //$crud->required_fields('fName','lName');

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'products'));

        $crud->callback_after_insert(array($this, 'track_insert'));

        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Products';

        $this->crud_view($output);
    }


    function fridges(){
        $crud = new grocery_CRUD();
        
        $crud->set_table('fridges');
        $crud->set_subject('Fridge');

        $crud->set_relation('fridge_type','fridge_types','name');
        $crud->set_relation('region_id','regions','name');
        $crud->set_relation('province','provinces','name');
        $crud->columns('id','fridge_type','brand_id','fridge_unit_code','location_name','region_id','province');
        $crud->unset_delete();

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'fridges'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $crud->add_action('Location History', '', '/cocacola/dashboard/fridge_locations_history');

        $output = $crud->render();

        $output->page_title = 'Fridges';

        $this->crud_view($output);
    }

    function fridge_log(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('fridge_log');
        $crud->set_subject('Fridge Log');

        $crud->set_relation('fridge_id','fridges','id');

        $crud->unset_delete();

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'fridge_log'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Fridge Log';

        $this->crud_view($output);
    }

 function fridge_types(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('fridge_types');
        $crud->set_subject('Fridge Log');
        $crud->set_relation('brand_id','brands','name');
        

        $crud->unset_delete();

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'fridge_types'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Types';

        $this->crud_view($output);
    }
    function payment_types(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('payment_types');
        $crud->set_subject('Payment Type');

        $crud->unset_delete();

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'payment_types'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Payment Types';

        $this->crud_view($output);
    }

    function customer_types(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('customer_types');
        $crud->set_subject('Customer Type');

        $crud->unset_delete();

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'customer_types'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Customer Types';

        $this->crud_view($output);
    }

    function orders(){
       
        $crud = new grocery_CRUD();
        
        $crud->set_table('orders');
        $crud->set_subject('Order');
        $crud->set_model('Grocery_crud_extend_model');
        /*$this->db->select('trader_id');*/
        $crud->set_relation('customer_id','customers','company_name');
        $crud->set_relation('payment_type','payment_types','name');
        $crud->set_relation('province','provinces','name');
        $crud->set_relation('region_id','regions','name');

        //$crud->set_relation('trader_id','traders','id'); 
        
        $crud->display_as('customer_id','Customer');
       
        $crud->add_action('Approve Order', '', '/management/approve_order','ui-icon-plus');
        $crud->add_action('Cancel Order', '', '/management/cancel_order');
        $crud->add_action('Resend Invoice', '', 'management/view_invoice');
        $crud->add_action('Print Invoice', '', '/management/print_invoice');
        $crud->add_action('View Comments', '', '/management/view_comments');

        $crud->callback_column('order_items',array($this,'_callback_order_items'));
        $crud->callback_column('total',array($this,'_callback_order_totals'));
        $crud->callback_column('region',array($this,'_callback_order_region'));
        //$crud->callback_column('trader_id',array($this,'_callback_trader_id'));
        
        $crud->columns('id','customer_id','payment_type','status','province','region_id','order_items','total','createdate');

        $crud->order_by('createdate','desc');

        $crud->unset_delete();

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'orders'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Orders';

        $this->crud_view($output);
    }
    
    function _callback_trader_id($value, $row){
       $trader_info=$this->trader_model->get_trader_info_basic($row->trader_id);
       return "[". $trader_info['id']."] ".$trader_info['first_name']." ".$trader_info['last_name'];

    }
    function approve_order($order_id){
        $this->spazapp_model->approve_order($order_id);
        $data['page_title'] = "Crud action";
        $data['action'] = "Order $order_id has been approved";
        $data['back_link'] = "/management/orders";
        $this->show_view('crud_action', $data);
    }
    function approve_distributor_order($distributor_order_id,$order_id){
        $this->spazapp_model->approve_distributor_order($distributor_order_id, $order_id);
        $data['page_title'] = "Crud action";
        $data['action'] = "Order $distributor_order_id has been approved";
        $data['back_link'] = "/management/distributor_orders";
        $this->show_view('crud_action', $data);
    }

     function approve_order_1($order_id){
        $this->spazapp_model->approve_order_test($order_id);
        $data['page_title'] = "Crud action";
        $data['action'] = "Order $order_id has been approved";
        $data['back_link'] = "/management/orders";
        $this->show_view('crud_action', $data);
    }

    function cancel_order($order_id)
    {
        $data['page_title'] = 'Spazapp Cancel Order';
        $data['item'] = $this->order_model->getAllItems($order_id);
        $data['order'] = $order_id;

        $total = 0;

        foreach ($data['item'] as $v) 
        {
            $sum = round($v['quantity'] * $v['price'], 2);
            $total = $total + $sum;
        }

        $data['grand_total'] = $total;
        $data['customer'] = $this->order_model->getCustomeName($order_id);

        $this->show_view('cancel_order', $data);

    }

    public function cancel_the_order()
    {
        $order_id = $this->input->post("order_id");
        $this->load->model('app_model');
        $result = $this->app_model->cancel_order($order_id);

        if($result = true)
        {
            $message = '<p style="color: green;">You successfully cancelled the order.</p> <br />';
            $this->session->set_flashdata('message', $message);
            redirect('/management/orders');
        }
        else
        {
            $message = '<p style="color: red;">Error!!! The order was not cancelled.</p> <br />';
            return;
        }
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

    function order_item($order_id){
        $crud = new grocery_CRUD();
        
        $crud->set_table('order_items');
        $crud->set_subject('Order Items');
        $crud->where('order_id', $order_id);

        $crud->set_relation('order_id','orders','id');
        $crud->set_relation('product_id','products','name');

        //$crud->unset_delete();

        $crud->add_action('More', '', 'demo/action_more','ui-icon-plus');
        
         $crud->fields('order_id','product_id','price','quantity');
         $crud->edit_fields('order_id','product_id','price','quantity');
         $crud->columns('order_id','product_id','price','quantity','total');

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'order_items'));

       // $crud->set_rules('quantity', 'Quantity','required|numeric|Larger than 0');
        $crud->callback_column('total', array($this,'_callback_order_item_total'));
        $crud->callback_add_field('order_id',array($this,'_add_default_id'));
       // $crud->callback_add_field('distributor_order_id',array($this,'_add_distributor_order_id'));

        // Price field needs to be read only, it can't be changed

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $data['order_id'] = $order_id;
        $output->data = $data;

        $output->page_title = 'Items from order id: '.$order_id;

        $this->crud_view($output);
    }

    function distro_order_item($distributor_order_id){
        $crud = new grocery_CRUD();
        
        $crud->set_table('order_items');
        $crud->set_subject('Order Items');
        $crud->where('distributor_order_id', $distributor_order_id);

        $crud->set_relation('order_id','orders','id');
        $crud->set_relation('product_id','products','name');

        //$crud->unset_delete();

        $crud->add_action('More', '', 'demo/action_more','ui-icon-plus');
        
         $crud->fields('order_id','product_id','price','quantity');
         $crud->edit_fields('order_id','product_id','price','quantity');
         $crud->columns('order_id','product_id','price','quantity','total');

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'order_items'));

       // $crud->set_rules('quantity', 'Quantity','required|numeric|Larger than 0');
        $crud->callback_column('total', array($this,'_callback_order_item_total'));
        $crud->callback_add_field('order_id',array($this,'_add_default_id'));
       // $crud->callback_add_field('distributor_order_id',array($this,'_add_distributor_order_id'));

        // Price field needs to be read only, it can't be changed

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $data['order_id'] = $distributor_order_id;
        $output->data = $data;

        $output->page_title = 'Items from distributor order id: '.$distributor_order_id;

        $this->crud_view($output);
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
        $output->data = $data;

        $output->page_title = 'Items from approved order id: '.$order_id;

        $this->crud_view($output);
    }
    function _add_distributor_order_id(){
        $dis_order_id = $this->uri->segment(4);
        return '<input type="text" value="'.$dis_order_id.'" name="distributor_order_id" readonly>';
    }

    function promotions(){

        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('promotions');
            $crud->set_subject('Promotions');

            $crud->change_field_type('createdate','invisible');

            $crud->set_field_upload('file','assets/uploads/promotions');

            $crud->callback_before_insert(array($this, 'set_createdate'));
            $crud->set_relation('product_id','products','name');

            $this->session->set_userdata('table', 'Promotions');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));

            $output = $crud->render();

            $output->page_title = 'Promotions';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }


    function news(){

        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('news');
            $crud->set_subject('News');

            $crud->change_field_type('createdate','invisible');
            $crud->callback_before_insert(array($this, 'set_createdate'));

            $this->session->set_userdata('table', 'News');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));
            
            $crud->fields('heading','default_user_group','body');
            
            $crud->edit_fields('heading','default_user_group','body');
            
            $crud->columns('heading','body','createdate','default_user_group');
          
            $crud->callback_field('default_user_group',array($this,'_callback_default_user_group'));
            
            $output = $crud->render();

            $output->page_title = 'News';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }
    
    function callback_default_user_group($value, $row){
     
       $results = $this->news_model->get_usergroup_by_id_($row->default_user_group);
        return $results;
       
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
    $input .= "<option selected value='".$usergroup_1['name']."' >".$usergroup_1['name']."</option>";


    foreach($usergroup_2 as $item)
    {
      $input .= "<option value='".$item->name."'>".$item->name."</option>";
    }
    $input .= "<option value='All'>all</option>";
    $input .= "</select>";
    return $input;
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

function _callback_qrcode($value, $row){
    return '<a href="'.$row->qrcode.'" target="_blank"><img src="'.$row->qrcode.'" width="100" /></a>';
}

function _callback_distributor_image($value, $row){
    return '<a href="'.base_url().'images/'.$value.'" target="_blank"><img src="'.base_url().'assets/uploads/distributors/'.$value.'" width="100" /></a>';
}

function _callback_customer_image($value, $row){
    return '<a href="'.base_url().'assets/uploads/customer/'.$value.'" target="_blank"><img src="'.base_url().'assets/uploads/distributors/'.$value.'" width="50" height="50" /></a>';
}

function _callback_trader_image($value, $row){
    return '<a href="'.base_url().'assets/uploads/trader/'.$value.'" target="_blank"><img src="'.base_url().'assets/uploads/distributors/'.$value.'" width="50" height="50" /></a>';
}

function _add_rand($value, $row){
    return 'R'.$value;
}

function _callback_order_items($value, $row){
    $count = $this->spazapp_model->get_order_item_count($row->id);
    return '<a href="/management/order_item/'.$row->id.'">'.$count.'</a>';
}

function _callback_dist_order_total($value, $row){
    $total = $this->spazapp_model->get_dist_order_total($row->dist_order_id, $this->uri->segment(3));
    return "R".$total;
}

function track_insert($post_array, $primary_key){
    $catgory = 'management';
    $action = 'insert';
    $label = 'User added a new entry to the '.$this->session->userdata('table').' table';
    $value = $primary_key;
    $this->event_model->track_event($catgory, $action, $label, $value);
    $this->session->unset_userdata(array('table'));
}

function track_insert_customer($post_array,$primary_key){
    $catgory = 'management';
    $action = 'insert';
    $label = 'User added a new entry to the '.$this->session->userdata('table').' table';
    $value = $primary_key;
    $this->event_model->track_event($catgory, $action, $label, $value);
    //$this->session->unset_userdata(array('table'));
    redirect('/management/customers');
}

function deleted_user($primary_key){
    $this->customer_model->get_deleted_customer_user($primary_key);   
}

function deleted_customer($primary_key){
    $this->customer_model->get_deleted_customer_user($primary_key);   
    redirect('/management/customers');
}

function track_update($post_array,$primary_key){
    $catgory = 'management';
    $action = 'update';
    $label = 'User updated an entry in the '.$this->session->userdata('table').' table';
    $value = $primary_key;
    $this->event_model->track_event($catgory, $action, $label, $value);
    $this->session->unset_userdata(array('table'));
}

function track_update_customer($post_array,$primary_key){
    $catgory = 'management';
    $action = 'update';
    $label = 'User updated an entry in the '.$this->session->userdata('table').' table';
    $value = $primary_key;
    $this->event_model->track_event($catgory, $action, $label, $value);
    $this->session->unset_userdata(array('table'));
    redirect('/management/customers');
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

    function view_invoice($order_id){

        if($order_id != ''){

            $this->load->model('spazapp_model');
            // $this->spazapp_model->send_order_comms($order_id);
            // $this->spazapp_model->place_distributor_orders($order_id);

            $data['order_info'] = $this->order_model->get_order_info($order_id);
            $data['page_title'] = 'Order Invoice';

            $this->show_view('order_invoice', $data);

        }
    }


     function view_distro_invoice($dist_order_id){

            $orders = $this->order_model->getOrderIdByDistOrderId($dist_order_id);
            $order_id = $orders['order_id'];

            $this->load->model('spazapp_model');
            $this->spazapp_model->send_order_comms($order_id);
            $this->spazapp_model->send_distributor_order_comms($dist_order_id,$orders['distributor_id']);

            $data['order_info'] = $this->order_model->get_dis_order_info($dist_order_id);
            $data['distributor'] = $this->order_model->getDistributorInfo($orders['distributor_id']);
            
            $data['page_title'] = 'Order Invoice';

            $this->show_view('distributor_invoice_print', $data);
            
    }


    function _add_default_id(){    
        return '<input id="field-order_id" name="order_id" type="text" value="'.$this->uri->segment(3).'" maxlength="10" readonly>';
        //return $return;
    }
    
    // Auto populate price on add order items
   
    function get_product_price($field_product_id){

        $this->load->model('logistics_model');
        $result = $this->logistics_model->get_product_price($field_product_id);
        $value = $result->price;
        echo $value;
    }

    // Distributor Listing to view their orders

    function _callback_distributor_orders($value, $row){
        $company = $this->order_model->getDistributorNameByID($row->id);
        return '<a href="/management/distributor_products/'.$row->id.'/3">'.$company->company_name.'</a>';
    }
   function insurance(){
		
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_m_funeral');
        $crud->set_subject('insurance');

        $crud->columns('id','type','member_benefit','spouse_option','premium','sale_reward','enabled');
        
        /*
        $crud->columns('id','type','member_benefit','spouse_option','customer_type','child_age_0_11_mths','child_age_1_5_yrs','child_age_6-13_yrs','child_age_14_21_yrs','premium','sale_reward','enabled');
        */
       
        $crud->unset_delete();

        $this->session->set_userdata(array('table' => 'ins_m_funeral'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Insurance';

        $this->crud_view($output);
    }
    function ins_applications(){
		
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_applications');
        
        $crud->set_subject('insurance');

        $crud->set_relation('ins_prod_id','ins_m_funeral','type');
        
        $crud->columns('policy_number','first_name','last_name','sa_id','passport_number','picture','signature','product','dependent','application_date');
        
        $crud->display_as('id','SA ID Number');  
        $crud->order_by('application_date','DESC');  
        
        $crud->callback_column('picture',array($this,'_callback_ins_applications_image'));
        $crud->callback_column('signature',array($this,'_callback_ins_applications_signature'));
        $crud->callback_column('product',array($this,'_callback_ins_product_info'));
        $crud->callback_column('dependent',array($this,'_callback_ins_dependent'));
       
        $crud->unset_delete();
        $crud->unset_add();
        $crud->unset_edit();

        $this->session->set_userdata(array('table' => 'ins_m_funeral'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Insurance Applications';

        $this->crud_view($output);
    }  
    
    function insurance_claims(){
		
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_m_applications');
        $crud->where('death_certificate !=','null');
        
        $crud->set_subject('insurance');

        $crud->set_relation('ins_prod_id','ins_m_funeral','type');
        
        $crud->columns('policy_number','name','surname','id','passport_number','picture','signature','death_certificate','product','dependent');
        
        $crud->display_as('id','SA ID Number');  
        
        $crud->callback_column('picture',array($this,'_callback_ins_applications_image'));
        $crud->callback_column('signature',array($this,'_callback_ins_applications_signature'));
        $crud->callback_column('death_certificate',array($this,'_callback_ins_applications_death_certificates'));
        $crud->callback_column('product',array($this,'_callback_ins_product_info'));
        $crud->callback_column('dependent',array($this,'_callback_ins_dependent'));
       
        $crud->unset_delete();
        $crud->unset_add();
        $crud->unset_edit();

        $this->session->set_userdata(array('table' => 'ins_m_funeral'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Insurance Claims';

        $this->crud_view($output);
    }    
    
    function _callback_ins_applications_death_certificates($value, $row){
        return '<a href="'.base_url().'aassets/uploads/insurance/death_certificates/'.$value.'" class="image-thumbnail"><img src="'.base_url().'assets/uploads/insurance/pictures/'.$value.'" width="100" /></a>';
    }
    
    function _callback_ins_applications_image($value, $row){
        return '<a href="'.base_url().'assets/uploads/insurance/pictures/'.$value.'" class="image-thumbnail"><img src="'.base_url().'assets/uploads/insurance/pictures/'.$value.'" width="100" /></a>';
    }
    
    function _callback_ins_applications_signature($value, $row){
        return '<a href="'.base_url().'assets/uploads/insurance/signatures/'.$value.'" class="image-thumbnail"><img src="'.base_url().'assets/uploads/insurance/signatures/'.$value.'" width="100" /></a>';
    }
    function _callback_ins_product_info($value, $row){
        $type='';
        $product = $this->insurance_model->get_insurance_product_id($row->ins_prod_id);
      
        return '<a href="/management/funeral_product_info/'.$row->ins_prod_id.'/'.$row->policy_number.'">'.$product['type'].'</a>';
    }
    
    function _callback_ins_dependent($value, $row){
    
        return '<a href="/management/ins_dependent/'.$row->policy_number.'">Dependent</a>';
    }
    
    
    function ins_application(){

            $data['ins_results'] = $this->insurance_model->get_all_insurance_app();
            $data['query'] = $this->insurance_model->get_all_info_csv();
            $data['query2'] = $this->insurance_model->get_dependants_csv();
            $data['page_title'] = "Insurance Applications"; 
            $this->show_view('insurance/ins_applications', $data);   
    }

    function ins_dependents($policy_number){

            $data['dependents'] = $this->insurance_model->get_ins_dependent_by_policy_number($policy_number);

            $data['page_title'] = "Dependents"; 
            $data['policy_number'] = $policy_number; 
            $this->show_view('insurance/ins_applications', $data);   
    }
    
    function ins_dependent($policy_number){
    
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_app_dependants');
        
        $crud->where('policy_number',$policy_number);
        
        $crud->set_subject('insurance');
        $crud->columns('policy_number','first_name','last_name','type','dob','createdate');
        $crud->unset_delete();
        ///$crud->unset_add();
        $crud->unset_edit();
        
         $crud->callback_field('policy_number',array($this,'_callback_policy_number'));
    
        $this->session->set_userdata(array('table' => 'ins_m_funeral'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Insurance Dependent';
        $output->policy_number = $policy_number;

        $this->crud_view($output);   
    }
    function _callback_policy_number(){
        $policy_number = $this->uri->segment(3);
        return '<input type="text" value="'.$policy_number.'" name="policy_number" readonly/>';
    }
    function funeral_product_info($product_id, $policy_number){
        
        $data['product_info'] = $this->insurance_model->get_insurance_product_id($product_id);
        $data['page_title'] = "Funeral Product Info"; 
        $data['policy_number'] = $policy_number; 
        $this->show_view('insurance/ins_applications', $data);   
    }

    function ins_application_form(){
        $data['page_title'] = "Insurance Applications"; 
        $data['products'] = $this->insurance_model->get_ins_products();
        $this->load->view('insurance/ins_application_form_1', $data);   
    }

    
    function location_updater(){
    
         $this->spazapp_model->get_location_updater();  
    }
    
    function cron_jobs()
    {
        $data['page_title'] = "CRON Jobs";
        //$data['inform'] = $this->spazapp_model->getImeiList();
        //$imei = $this->spazapp_model->getImeiList();
        $this->show_view('cron_jobs', $data);
    }

    function get_phone_imei()
    {
        $user_name = "stephenziv";
        $password = "checkimei";
        $url= "http://www.imei.info/api/checkimei/";
        $imeiArray = $this->spazapp_model->getImeiList();

        foreach ($imeiArray as $r) 
        {
            $imei = $r['imei'];
            $show = '';

            $fields = array(
            'login' => $user_name,
            'password' => $password,
            'imei' => $imei
            );

            //$field_string = http_build_query($fields);

            $ch = curl_init();

            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POST, 1);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields);

            $result = curl_exec($ch);
            //echo $result;
            $responsejson = json_decode($result, true);
            $data['json_imei'] = json_encode($responsejson);
            $data['model'] = $responsejson['model'];

            $show = $this->spaza_model->updateEMEI($imei, $data);
            curl_close($ch);
            sleep(1);
        }

        print_r($show);
    }

    public function pos_items()
    {
        $crud = new grocery_CRUD();
        
        $crud->set_table('products');
        $crud->set_subject('POS Items');

        $crud->where('category_id =', '46');

        //$crud->columns('name','category_id','supplier_id','picture','customer_type');
        $crud->set_relation_n_n('customer_types','prod_customer_type_link','customer_types','product_id','customer_type','name','priority','');      
        $crud->columns('name','category_id','supplier_id','picture','customer_types');        
        $crud->edit_fields('stock_code','barcode','name','category_id','supplier_id','customer_types','picture','editdate','createdate');       
        $crud->fields('stock_code','barcode','name','category_id','supplier_id','customer_types','picture','editdate','createdate');
        

        $crud->callback_column('picture',array($this,'_callback_add_image'));
        $crud->callback_after_upload(array($this,'create_crop'));
        $crud->display_as('qty','No of Units in Shrink');  
        $crud->unset_delete();
        $crud->callback_column('sell_price',array($this,'_add_rand'));
     
        $crud->set_field_upload('picture','images/');

        $this->session->set_userdata(array('table' => 'products'));
        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));


        $output = $crud->render();

        $output->page_title = 'POS Items';

        $this->crud_view($output);
    }

    //Function to print Invoice for distributors
    public function print_invoice($order_id)
    {
            if($order_id != '')
            {
                $data['order_info'] = $this->order_model->get_order_info($order_id);
                $data['page_title'] = 'Print Invoice';
                $this->show_view('order_invoice', $data);
            }
    }

    // View Order Comments
    function view_comments($id)
    {
        $crud = new grocery_CRUD();
              
        $crud->set_table('order_comments');
        $crud->set_subject('Comments');
        $crud->where('order_id', $id);
            
        $crud->set_relation('user_id','aauth_users','name'); 
           
        $crud->columns('id','user_id','order_id','distributor_order_id','comment','createdate'); 

        $crud->callback_add_field('user_id',array($this,'_add_user_id'));
        $crud->callback_add_field('distributor_order_id',array($this,'_add_dist_order_id'));
        $crud->callback_add_field('order_id',array($this,'_add_order_id')); 

        $crud->unset_delete();
        $crud->unset_edit();

        $output = $crud->render();

        $output->page_title = 'Order Comments';

        $this->crud_view($output);
    }

    function _add_user_id()
    { 
        $distributor = $this->aauth->get_user();
        $user_id = $distributor->id;   
        return '<input id="field_user_id" name="user_id" type="text" value="'.$user_id.'" readonly>';
    }

    function _add_dist_order_id()
    {   
        return '<input id="field_distributor_order_id" name="distributor_order_id" type="text" value="0" readonly>';
    }

    function _add_order_id()
    { 
        $order_id = $this->uri->segment(3);
        return '<input id="field_order_id" name="order_id" type="text" value="'.$order_id.'" readonly>';
    }

    // Distributor orders for admin
    function distributors_list(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('distributors');
        $crud->set_subject('Distributor');

        $crud->columns('id','company_name','contact_name','number','email','address');
        $crud->display_as('contact_name','Contact Person'); 
        $crud->display_as('number','Phone Number'); 

        $crud->callback_column('company_name',array($this,'_callback_distributor_links'));    

        $crud->unset_delete();
        $crud->unset_add();
        $crud->unset_edit();

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'distributors'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Distributors Orders';

        $this->crud_view($output);
    }

    function _callback_distributor_links($value, $row){
        $company = $this->order_model->getDistributorNameByID($row->id);
        return '<a href="/management/distributor_orders/'.$row->id.'">'.$company->company_name.'</a>';
    }

    function distributor_orders($id)
    {

        $dist_id = $id;
        $company = $this->order_model->getDistributorNameByID($dist_id);
       
         $crud = new grocery_CRUD();
        
        $crud->set_table('orders');
        $crud->set_subject('Order');

        $crud->set_relation('id','distributor_orders','order_id');
        $crud->set_model('Grocery_crud_extend_model');
        $this->db->select('j'.substr(md5('customer_id'),0,8).'.cellphone');
        $crud->set_relation('customer_id','customers','company_name');
        $crud->set_relation('payment_type','payment_types','name');
        $crud->set_relation('province','provinces','name');
        $crud->set_relation('region_id','regions','name');
        $crud->set_relation('status_id','gbl_statuses','name');
       //$crud->set_relation('distributor_id','distributors','company_name');
        $crud->display_as('customer','customer_id');
        $crud->where('distributor_id', $dist_id);
       
        $crud->add_action('Mark as Delivered', '', '','ui-icon-image',array($this,'_call_back_mark_as_delivered'));
        $crud->add_action('Resend Invoice', '', '','ui-icon-image',array($this,'callback_view_invoice'));
        $crud->add_action('Print Invoice', '', '','ui-icon-image',array($this,'callback_print_invoice'));
        $crud->add_action('View Comments', '', '','ui-icon-image',array($this,'callback_view_comments'));
        $crud->add_action('Edit Order Items', '', '','ui-icon-image',array($this,'callback_edit_order_items'));

        $crud->callback_column('order_items',array($this,'_callback_order_items_quantities'));
        $crud->callback_column('dist_order_id',array($this,'_callback_dist_order_id'));
        $crud->callback_column('total',array($this,'_callback_dist_order_total'));
        $crud->callback_column('region',array($this,'_callback_order_region'));
        $crud->callback_column('distributor_id',array($this,'_callback_distributor_id_column'));
        $crud->callback_column('customer',array($this,'_callback_company_name'));
        
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

    //Function to print Invoice for distributors
    public function print_distro_invoice($order_id, $distributor_id)
    {
        if($order_id != '')
        {

            $data['order_info'] = $this->order_model->get_dis_order_info($order_id);
            $data['distributor'] = $this->order_model->getDistributorInfo($distributor_id);
            $data['page_title'] = 'Print Invoice';

            $this->show_view('distributor_invoice_print', $data);
        }
    }


    function callback_view_invoice($primary_key , $row){
        return site_url('/management/view_distro_invoice').'/'.$row->dist_order_id;
    }

    function callback_print_invoice($primary_key , $row){
        return site_url('/management/print_distro_invoice').'/'.$row->dist_order_id."/".$this->uri->segment(3);
    }

    function callback_view_comments($primary_key , $row){
        return site_url('/distributors/distributor_management/view_comments').'/'.$row->dist_order_id;
    }
    
    function callback_edit_order_items($primary_key , $row){
        return site_url('/distributors/distributor_management/edit_order_items').'/'.$row->dist_order_id;
    }


    function _callback_dist_order_id($value , $row){
        return $this->order_model->get_distributor_order($row->id, $this->uri->segment(3))['id'];
    } 

    function _call_back_mark_as_delivered($primary_key , $row){
        $distributor_id = $this->uri->segment(3); 
        return site_url('management/mark_as_delivered').'/'.$distributor_id.'/'.$row->dist_order_id;
    }

    function mark_as_delivered($distributor_id, $distributor_order_id){
        $this->delivery_model->distributor_order_delivered($distributor_order_id);
        redirect('management/distributor_orders/'.$distributor_id);
    }

    function _callback_order_totals($value, $row){
        $total = $this->spazapp_model->get_order_total($row->id);
        return "R".$total;
    }

    function _callback_order_createdate($value, $row){
        $distributor_id= $this->uri->segment(3); 
        $order = $this->order_model->getOrderInformation($row->order_id,$distributor_id);
        return $order->createdate;
    }
    function _callback_cellphone($value, $row){
        echo"Test";
       return $this->customer_model->get_customer($row->customer_id)['cellphone'];
        
    }

    function _callback_dist_trader_id($value, $row){
        $dist_id = $this->uri->segment(3);       
        $query = $this->order_model->getOrderInformation($row->order_id,$dist_id);
        if(empty($query->trader_id)){
            $cname ='0';
        }else{
            $cname = $query->trader_id;
        }
        
        
        return $cname;
    }

    function _callback_company_names($value, $row){

        $dist_id = $this->uri->segment(3);       
        $this->load->model('order_model');
        $query = $this->order_model->getOrderInformation($row->order_id,$dist_id);
        if(empty($query->company_name)){
            $cname ='';
        }else{
            $cname = $query->company_name;
        }
        
        
        return $cname;
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

    function _callback_payment_types($value, $row){

        $dist_id = $this->uri->segment(3);
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

    function _callback_delivery_types($value, $row){

        $dist_id = $this->uri->segment(3);
        $this->load->model('order_model');
        $query = $this->order_model->getOrderInformation($row->order_id,$dist_id);
        if(empty($query->delivery_type)){
            $delivery='';
        }else{
        $delivery = $query->delivery_type;    
        }
        
        return $delivery;
    }

    function _callback_createdates($value, $row){

        $dist_id = $this->uri->segment(3);
        
        $this->load->model('order_model');
        $query = $this->order_model->getOrderInformation($row->order_id,$dist_id);
        if(empty($query->createdate)){
        $cdate ='';
        }else{
        $cdate = $query->createdate;    
        }
        
        return $cdate;
    }

    function _callback_order_items_quantities($value, $row){

        $distributor_id = $this->uri->segment(3);
         $order_count = $this->spazapp_model->get_distributor_order_items2($row->dist_order_id, $distributor_id);
        return '<a href="/management/dist_order_item/'.$row->dist_order_id."/".$row->id.'/'.$distributor_id.'">'.$order_count.'</a>';
    }

    function dist_order_item($dist_order_id, $order_id,  $distributor_id){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('order_items');
        $crud->set_subject('Order Items');
     
        $crud->where('distributor_order_id', $dist_order_id);
        //$crud->where('distributor_order_id', $dist_order_id);
        
        $crud->set_relation('order_id','orders','id');
        $crud->set_relation('product_id','products','name');

        $crud->columns('id','distributor_order_id','product_id','price','quantity','total');
        

        $crud->callback_column('total', array($this,'_callback_order_item_total'));

        $crud->unset_delete();
        $crud->unset_edit();
        $crud->unset_add();
       
        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'order_items'));

        $output = $crud->render();

        $output->page_title = 'Order Items - Order Id : '.$order_id;
        $output->dis_order_id =$order_id;

        $this->crud_view($output);
    }

    function _callback_order_item_total($value, $row) {
          $total = $row->quantity * $row->price;
          return round($total, 2);
    }

    function trading_zones()
    {
        $crud = new grocery_CRUD();
        
        $crud->set_table('trading_zones');
        $crud->set_subject('Trading Zones');
        
        $crud->set_relation('trader_id','traders','id');
        $crud->set_relation_n_n('regions','tz_region_link','regions','zone_id','region_id','name'); 

        $crud->columns('name', 'trader_id', 'regions', 'editdate');

        $crud->field_type('id', 'hidden');
         
        $output = $crud->render();

        $output->page_title = 'Trading Zones';
         
        $this->crud_view($output);
    }

    function comms(){

        
            $crud = new grocery_CRUD();

            
            $crud->set_table('comms');
            $crud->set_subject('Comms');

            $this->session->set_userdata('table', 'Comms');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));
            
            $crud->add_action('Send push notification', '', 'daily_cron/push_notification');

            $output = $crud->render();

            $output->page_title = 'Comms';

            $this->crud_view($output);

       }

    function comm_queue(){
        
            $crud = new grocery_CRUD();

            
            $crud->set_table('comm_queue');
            $crud->set_subject('Queue');
            $crud->set_relation('comm_id','comms','title');
           //$crud->set_relation('user_id','aauth_users','name');
            
            $crud->columns('type','comm_id','user_id','name','json','status','attempts','createdate');
            $crud->callback_column('type', array($this,'_callback_comm_type'));
            $crud->callback_column('user_id', array($this,'_callback_comm_user'));
            $crud->callback_column('name', array($this,'_callback_comm_name'));

            $crud->add_action('Resend Comm', '', '','ui-icon-image',array($this,'_call_back_resend_comm'));
            $this->session->set_userdata('table', 'comm_queue');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));
            $crud->order_by('createdate','DESC');
            $crud->unset_delete();
            //$crud->unset_edit();
            $crud->unset_add();
            $output = $crud->render();

            $output->page_title = 'Comm Queues';

            $this->crud_view($output);

       }

    function _callback_comm_type($value, $row){
       $result = $this->spazapp_model->get_comm($row->comm_id);
       if($result['id']==39){
        $return = $result['type']." [ Distributor Comm ] ";
       }else{
        $return = $result['type'];
       }
       return $return;
    }

    function _callback_comm_user($value, $row){
        if($row->comm_id==39){
            $distributor = $this->user_model->get_distributor_user($row->user_id);
            if(!isset($distributor->id)){
                $return="";
            }else{
                $return=$distributor->id;
            }
        }else{
            $user = $this->user_model->get_user($row->user_id);
            if(!isset($user->id)){
                $return="";
            }else{
                $return=$user->id;
            }
        }
        return $return;

    } 

    function _callback_comm_name($value, $row){
        $user = $this->user_model->get_user($row->user_id);
        if(!isset($user->id)){
            $return="";
        }else{
            $return=$user->name;
        }

        return $return;

    }

    function _call_back_resend_comm($value, $row){
        return site_url('/comms/send_queued_comms').'/'.$row->user_id.'/'.$row->id.'/force';
    }

    function app_design(){
        $crud = new grocery_CRUD();

        
        $crud->set_table('design');
        $crud->set_subject('Design');
        $crud->set_field_upload('logo','assets/img');
        $crud->callback_column('colour1',array($this,'_callback_colour1'));
        $crud->callback_column('colour2',array($this,'_callback_colour2'));
        $crud->callback_column('colour3',array($this,'_callback_colour3'));
        $crud->callback_column('colour4',array($this,'_callback_colour4'));

        $this->session->set_userdata('table', 'Comms');
        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));
        
        $output = $crud->render();

        $output->page_title = 'App Design';

        $this->crud_view($output);
    }
    
    function _callback_colour1($value, $row){
        return '<svg style="width:100%; height:100px">
                    <rect style="width:100%; height:100px;fill:#'.$row->colour1.';stroke-width:0;stroke:rgb(0,0,0)" />
                </svg>';
    }

    function _callback_colour2($value, $row){
      
        return '<svg style="width:100%; height:100px">
                    <rect style="width:100%; height:100px;fill:#'.$row->colour2.';stroke-width:0;stroke:rgb(0,0,0)" />
                </svg>';
    } 

    function _callback_colour3($value, $row){
        return '<svg style="width:100%; height:100px">
                    <rect style="width:100%; height:100px;fill:#'.$row->colour3.';stroke-width:0;stroke:rgb(0,0,0)" />
                </svg>';
    } 

    function _callback_colour4($value, $row){
         return '<svg style="width:100%; height:100px">
                    <rect style="width:100%; height:100px;fill:#'.$row->colour4.';stroke-width:0;stroke:rgb(0,0,0)" />
                </svg>';
    }

    function _callback_order_province($value, $row){
        $province = $this->customer_model->get_customer_province($row->customer_id);
        return $province['name'];
    }

    function _callback_order_region($value, $row){
        $region = $this->customer_model->get_customer_region($row->customer_id);
        return $region['name'];
    }

    function basket_orders(){
        $crud = new grocery_CRUD();

        
        $crud->set_table('basket_orders');
        $crud->set_subject('Basket Orders');
        $crud->columns('id','customer_id','payment_type','order_item','order_type','status','delivery_type','createdate');
        $crud->set_relation('customer_id','customers','company_name');
        $crud->set_relation('payment_type','payment_types','name');
        $crud->set_relation('status_id','gbl_statuses','name');
        $crud->display_as('id','Order Id');
        $this->session->set_userdata('table', 'basket_orders');
        $crud->callback_column('order_item',array($this,'_callback_basket_order_items'));
        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));
        
        $output = $crud->render();

        $output->page_title = 'Basket Orders';

        $this->crud_view($output);
    }

     function _callback_basket_order_items($value, $row){
        return "<a href='/management/basket_items/".$row->id."'>".$this->order_model->get_basket_order_items($row->id)."</a>";
    }

    function basket_items($order_id){
        $crud = new grocery_CRUD();

        
        $crud->set_table('basket_order_items');
        $crud->set_subject('Basket Order Items');
        $crud->where('order_id',$order_id);
        $this->session->set_userdata('table', 'basket_order_items');
        $crud->set_relation('distributor_id','distributors','company_name');
        $crud->set_relation('product_id','products','name');
        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));
        
        $output = $crud->render();

        $output->page_title = 'Basket Order Items'." - Order Id : ".$order_id;

        $this->crud_view($output);
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

    function _callback_company_name($value, $row){
        $user_info = $this->aauth->get_user();
        $dist_id = $user_info->distributor_id;
        
        $this->load->model('order_model');
        $query = $this->order_model->getOrderInformation($row->id,$dist_id);
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

     function _callback_order_items_quantity($value, $row){
        $user_info = $this->aauth->get_user();
        $distributor_id = $user_info->distributor_id;
        
        $order_count = $this->spazapp_model->get_order_items_($row->order_id, $row->id);
        return '<a href="/management/distro_order_item/'.$row->id.'">'.$order_count.'</a>';
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

    function approve_distro_order_action($primary_key , $row)
    {
    return site_url('/management/approve_distributor_order').'/'.$row->id."/".$row->order_id;
    }
  function customers(){
    $dataset=false;
    $data['date_from'] = $this->input->post('date_from');
    $data['date_to']= $this->input->post('date_to');
    $data['regions']=$this->spazapp_model->get_regions();
    $data['sparks']=$this->trader_model->get_all_traders();

    if(isset($_POST['trader_id'])){
    $data['spark']=$this->trader_model->get_trader($_POST['trader_id']);
    }

    if(isset($_POST['region_id'])){
    $data['region']=$this->spazapp_model->get_region($_POST['region_id']);  
    }
    
    $customers = $this->customer_model->get_all_customers_info($data['date_from'], $data['date_to']);
     foreach ($customers as $row) {
        
        if(isset($row['trader_first_name']) || isset($row['trader_last_name'])){
            $trader_id="[".$row['trader_id']."] ".$row['trader_first_name']." ".$row['trader_last_name'];
        }else{
            $trader_id='';
        }
        //<li><a  href='/management/index/delete/".$row['id']."' style='color:black'>Delete Customer</a></li>
        
         $dataset.='dataSet.push(["'.$row['id'].'",
         "'.$row['first_name'].'",
         "'.$row['last_name'].'",
         "'.$row['cellphone'].'",
         "'.$row['company_name'].'",
         "'.$row['customer_type'].'",
         "'.$row['province'].'",
         "'.$row['parent'].'",
         "'.$row['region'].'",
         "'.$trader_id.'",
         "'.$row['createdate'].'",
         "'."<li class='dropdown'><a href='#' class='dropdown-toggle btn' data-toggle='dropdown' style='color:black'>Actions <b class='caret'></b></a><ul class='dropdown-menu' role='menu'><li><a href='/management/index/edit/".$row['id']."' style='color:black'>Edit Customer</a></li></ul>".'"
        ]);';
        }

        $columns="  { title: 'ID'},
                    { title: 'First name'},
                    { title: 'Last name'},
                    { title: 'Cellphone'},
                    { title: 'Company name'},
                    { title: 'Customer type'},
                    { title: 'Province'},
                    { title: 'Parent Id'},
                    { title: 'Region id'},
                    { title: 'Trader id'},
                    { title: 'Createdate'},
                    { title: 'Action'}";

        $data['script'] = $this->data_table_script($dataset, $columns, 10, false, true);
       
        $data['page_title']="Spazapp Customers";
        $this->show_view("customers",$data);
  }

  function spazapp_products(){
    $dataset=false;
    $products=$this->product_model->get_all_products_info();

    foreach ($products as $r) {
         $dataset.='dataSet.push([
         "'.preg_replace('/[^a-zA-Z0-9_ -]/s','',$r['stock_code']).'",
         "'.preg_replace('/[^a-zA-Z0-9_ -]/s','',$r['name']).'",
         "'.$r['category_id'].'",
         "'.$r['customer_type'].'",
         "'.$r['supplier_id'].'",
         "'."<a href='".base_url()."images/".$r['picture']."' target='_blank'>"."<img src='".base_url()."images/".$r['picture']."'  width='100' /></a>".'",
          "'."<li class='dropdown'><a href='#' class='dropdown-toggle btn' data-toggle='dropdown' style='color:black'>Actions <b class='caret'></b></a><ul class='dropdown-menu' role='menu'><li><a href='/management/products/edit/".$r['id']."' style='color:black'>Edit Product</a></li></ul>".'"
        ]);';
        }

        $columns="  { title: 'Stock Code'},
                    { title: 'Name'},
                    { title: 'Category'},
                    { title: 'Customer_type'},
                    { title: 'Supplier Id'},
                    { title: 'Picture'},
                    { title: 'Picture'}";

        $data['script'] = "".$this->data_table_script($dataset,$columns);
       
        $data['page_title']="Spazapp Products";
        $this->show_view("products",$data);

  }

 function data_table_script($dataset, $columns, $order_index=0, $search=false, $individual_col_search=false){
        $data_table = $this->javascript_library->data_table_script($dataset, $columns, $order_index, $search, $individual_col_search);
        return $data_table;
 }


}
