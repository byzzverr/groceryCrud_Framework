<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class App extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('app_model');
        $this->load->model('airtime_model');
        $this->load->model('financial_model');
        $this->load->model('customer_model');
        $this->load->model('user_model');
        $this->load->model('trader_model');

    }

    function index(){
        echo "These are not the droids you are looking for.";
    }

    function testformartin(){
        $result = $this->app_model->get_recent_raw_data();
        echo '<pre>';
        print_r($result);
        echo '<pre>';
    }

    function posttest(){
        $this->app_model->save_raw_data(json_encode($_REQUEST),'old_api','posttest');
        echo json_encode($_REQUEST);
    }

    function resend_order_comms($order_id=''){

            if($order_id != ''){

            $this->load->model('spazapp_model');
            $this->spazapp_model->send_order_comms($order_id);
            $this->spazapp_model->place_distributor_orders($order_id);
            echo 'done';

            }
    }

    function testforbyron(){
        if (!$this->aauth->is_loggedin()){
            $this->aauth->login('byron','tester');
        }  

        if ($this->aauth->is_loggedin()){

            $all_images = 1;

            // store the raw data for future reference
            $this->app_model->save_raw_data(json_encode($_REQUEST));



            $data['message'] = "LOGGEDIN";
            $data['list']['count'] = 0;
                
            $user_info = $this->aauth->get_user(); // remember this is an object not array.
            $customer_info = $this->app_model->get_customer_info($user_info->customer_id);
            $user_info->customer_type = $customer_info['customer_type'];

            $data['list']['data'] = "<name>" . $customer_info['first_name'] . "</name>";


            $order = '3,0,2,1175,1,159.99,1176,1,159.99';

            $data['message'] = "ORDERPLACED";
            $data['list'] = $this->place_order($user_info->customer_id,$order);

            print_r($data);

        }else{
            die('contact byron - something is wrong.');

        }
    }

    function testforandy(){
        if (!$this->aauth->is_loggedin()){
            $this->aauth->login('byron','tester');
        }  

        if ($this->aauth->is_loggedin()){

            $all_images = 1;

            // store the raw data for future reference
            $this->app_model->save_raw_data(json_encode($_REQUEST));



            $data['message'] = "LOGGEDIN";
            $data['list']['count'] = 0;
                
            $user_info = $this->aauth->get_user(); // remember this is an object not array.
            $customer_info = $this->app_model->get_customer_info($user_info->customer_id);
            $user_info->customer_type = $customer_info['customer_type'];

            $data['list']['data'] = "<name>" . $customer_info['first_name'] . "</name>";
        }else{
            die('contact byron - something is wrong.');

        }

            echo '<h2>LOGIN</h2>';

            echo '<textarea style="border:none; width:800px; height:200px; background:#ccc;">';

            $this->send_reply($data);

            echo '</textarea>';
            

        $array = array(
            0 => 'LISTPROD',
            1 => 'LISTSPECIAL',
            //2 => 'LOGOFF',
            3 => 'ORDERS',
            4 => 'PLACEORDER',
            5 => 'LISTAIRTIMEPRODS',
            //6 => 'BUYAIRTIME',
            7 => 'LISTCATS',
            8 => 'LISTNEWS',
            9 => 'GETNEWSITEM',
            10 => 'LISTPAYMENTTYPES',
            11 => 'LISTALLIMAGES',
            12 => 'LISTNEWIMAGES');

        foreach ($array as $key => $value) {

            echo '<h2>'.$value.'</h2>';

            echo '<textarea style="border:none; width:800px; height:200px; background:#ccc;">';

            switch ($key) {
                case 0:
                    $_GET['category_id'] = 1;
                    break;
                case 4:
                    $_GET['order'] = '4,1,1021,1,50.00';
                    break;
                case 9:
                    $_GET['news_id'] = 1;
                    break;
                
                default:
                    # code...
                    break;
            }

            $data = $this->parse_request($key,$user_info);

            $this->send_reply($data);

            echo '</textarea>';

        }
    }

    function input(){

        if(isset($_GET['user']) && isset($_GET['pass'])){
            $this->login(trim($_GET['user']), trim($_GET['pass']));

            $data['message'] = "LOGGEDIN";
            $data['list']['count'] = 0;


            if ($this->aauth->is_loggedin()){

                // store the raw data for future reference
                $this->app_model->save_raw_data(json_encode($_REQUEST),'old_api','input');
                    
                $user_info = $this->aauth->get_user(); // remember this is an object not array.
                $customer_info = $this->app_model->get_customer_info($user_info->customer_id);
                $user_info->customer_type = $customer_info['customer_type'];

                $data['list']['data'] .= "<name>" . $customer_info['first_name'] . "</name>";
            }

        }else{

            $requestjson = array();
            $requestjson = file_get_contents('php://input');
            $requestjson = json_decode($requestjson, true);

            if(!isset($requestjson['store_id']) && isset($_GET['store_id'])){
                $requestjson['store_id'] = $_GET['store_id'];
            }
        
            if(isset($requestjson['token']) && $requestjson['token'] != ''){


                if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                    $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                    $user_id = $user['id'];
                    $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                    $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
                }else{
                    $user_id = $this->user_model->get_user_from_token($requestjson['token']);
                }

                if($user_id){
                    $this->aauth->login_fast($user_id);
                }
            }else{

                $data['message'] = "INVALIDTOKEN";
                $data['list']['count'] = 0;
                $data['list']['data'] = '';
            }

            //byron put this here to login while testing.
            //$this->aauth->login_fast(2);

            if ($this->aauth->is_loggedin()){

                // store the raw data for future reference
                   
                $user_info = $this->aauth->get_user(); // remember this is an object not array.
                if(isset($user_info->customer_id)){
                    $customer_info = $this->app_model->get_customer_info($user_info->customer_id);
                    $user_info->customer_type = $customer_info['customer_type'];
                }else{
                    $customer_info = array();
                    $user_info->customer_type = 1;
                }

                if (isset($_GET['request'])) {
                    $data = $this->parse_request($_GET['request'],$user_info, $requestjson);
                } else {
                    $data['message'] = "NOCOMMAND";
                    $data['list']['count'] = 0;
                    $data['list']['data'] = '';
                }

            }else{
                //reset password
                if(isset($_GET['request']) && $_GET['request'] == 13 && isset($_GET['user_name'])){

                    $data = $this->parse_request($_GET['request'],$user_info, $requestjson);

                }else{

                    $data['message'] = "NOTLOGGEDIN";
                    $data['list']['count'] = 0;
                    $data['list']['data'] = '';
                    
                }

            }
        }

        $this->send_reply($data);
    }

    function parse_request($request, $user_info, $post){

        $raw_array = array();
        $raw_array['request'] = $request;
        $raw_array['post'] = $post;
        $raw_array['get'] = $_GET;
        $this->app_model->save_raw_data(json_encode($raw_array),'old_api','input('.$request.')');

        $LISTPROD = 0;
        $LISTSPECIAL = 1;
        $LOGOFF = 2;
        $ORDERS = 3;
        $PLACEORDER = 4;
        $LISTAIRTIMEPRODS = 5;
        $BUYAIRTIME = 6;
        $LISTCATS = 7;
        $LISTNEWS = 8;
        $GETNEWSITEM = 9;
        $LISTPAYMENTTYPES = 10;
        $LISTALLIMAGES = 11;
        $LISTNEWIMAGES = 12;
        $RESETPASSWORD = 13;

        switch ($request) {
            case $LISTPROD :   // List Product
                $data['message'] = "LISTPROD";
                if(isset($_GET['category_id']) && $_GET['category_id'] != '' && is_numeric($_GET['category_id'])){
                    $category_id = $_GET['category_id'];
                }else{
                    $category_id = 0;
                }

                $data['list'] = $this->get_product_list($user_info->customer_type, $user_info->customer_id, $category_id);
                break;
            case $LISTSPECIAL :   // List Product
                $data['message'] = "LISTSPECIAL";
                $data['list'] = $this->get_specials_list($user_info->customer_type, $user_info->customer_id);
                break;
            case $LOGOFF :  // Log out
                $data['message'] = "LOGGEDOUT";
                $data['list']['count'] = 0;
                $data['list']['data'] = '';
                $this->aauth->logout();
                break;
            case $ORDERS :  // Show Order History
                $data['message'] = "ORDERS";
                $data['list'] = $this->get_orders_list($user_info->customer_id);
                break;
            case $PLACEORDER : // Place an order
                $order = $_GET['order'];
                $data['message'] = "ORDERPLACED";
                $data['list'] = $this->place_order($user_info->customer_id,$order);
                break;
            case $LISTAIRTIMEPRODS : // List airtime products
                $data['message'] = "LISTAIRTIMEPRODS";
                $data['list'] = $this->get_airtime_products();
                break;
            case $BUYAIRTIME : // Buy airtime
                $data['message'] = "BUYAIRTIME";
                $order = $post;
                $data = $this->buy_airtime($user_info->id,$order);
                break;
            case $LISTCATS : // List Categories
                $data['message'] = "LISTCATS";
                if(isset($_GET['parent_id']) && $_GET['parent_id'] != '' && is_numeric($_GET['parent_id'])){
                    $data['message'] = "LISTSUBS";
                    $parent_id = $_GET['parent_id'];
                }else{
                    $data['message'] = "LISTCATS";
                    $parent_id = 0;
                }
                $data['list'] = $this->get_category_list($parent_id,$user_info->customer_type, $user_info->customer_id);
                break;
            case $LISTNEWS : // List all news items
                $data['message'] = "LISTNEWS";
                $data['list'] = $this->get_news_list();
                break;
            case $GETNEWSITEM : // List all news items
                $data['message'] = "GETNEWSITEM";
                $news_id = $_GET['news_id'];
                $data['list'] = $this->get_news_item($news_id);
                break;
            case $LISTPAYMENTTYPES : // List all payment types
                $data['message'] = "LISTPAYMENTTYPES";
                $data['list'] = $this->get_payment_types();
                break;            
            case $LISTALLIMAGES : // List all payment types
                $data['message'] = "LISTALLIMAGES";
                $data['list'] = $this->get_all_images($user_info->id,'all');
                break;
            case $LISTNEWIMAGES : // List all payment types
                $data['message'] = "LISTNEWIMAGES";
                $data['list'] = $this->get_new_images($user_info->id);
                break;
            case $RESETPASSWORD : // List all payment types
                
                if(isset($_GET['user_name']) && $_GET['user_name'] != ''){
                    $data = $this->reset_password($_GET['user_name']);
                }else{
                    $data['message'] = "DIDNOTPOSTUSERNAME";    
                }
                break;
            default : $Message = "UNKNOWNCOMMAND";
        }

        return $data;
    }

    function send_reply($data){

        $requestjson = array();
        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);

        if(!isset($requestjson['store_id']) && isset($_GET['store_id'])){
            $requestjson['store_id'] = $_GET['store_id'];
        }

        if (isset($_GET['store_id']) && !empty($_GET['store_id'] && $_GET['store_id']) != ''){
            $user = $this->customer_model->get_user_from_customer_id($_GET['store_id']);
            $user_id = $user['id'];
            $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
            $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
        }else{
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
        }

        $user_info = $this->aauth->get_user($user_id); // remember this is an object not array.

        if(isset($user_info->id)){
            if(isset($user_info->customer_id)){

                $rewards = $this->financial_model->get_rewards($user_info->customer_id);
                $balance = $this->financial_model->get_balance($user_info->customer_id);
                $airtime_balance = $this->financial_model->get_airtime_balance($user_info->customer_id);
            }else{

                $rewards = 0;
                $balance = 0;
                $airtime_balance = 0;
            }

            $data['list']['data'] .= "<balance>" . $balance . "</balance>";
            $data['list']['data'] .= "<airtime_balance>" . $airtime_balance . "</airtime_balance>";
            $data['list']['data'] .= "<rewards>" . $rewards . "</rewards>";
        }

        $string = "<ver>1.0.0</ver><reqstat>".$data['message']."</reqstat><count>".$data['list']['count']."</count>".$data['list']['data'];
        echo "<?xml version=\"1.0\" encoding=\"utf-8\"?><points>".str_replace('&', 'and', $string)."</points>";

    }

    function get_all_images($user_id,$all=''){
        $images = $this->app_model->get_user_images($user_id,$all);
        $count = count($images);

        $data = "<images>";
            $data .= "<item>";
            $data .= "<id>8546</id>";
            $data .= "<type>placeholder</type>";
            $data .= "<imagename>holder.png</imagename>";
            $data .= "</item>";

            $data .= "<item>";
            $data .= "<id>8547</id>";
            $data .= "<type>8ta airtime</type>";
            $data .= "<imagename>8ta.png</imagename>";
            $data .= "</item>";

            $data .= "<item>";
            $data .= "<id>8548</id>";
            $data .= "<type>cellc airtime</type>";
            $data .= "<imagename>cellc.png</imagename>";
            $data .= "</item>";

            $data .= "<item>";
            $data .= "<id>8549</id>";
            $data .= "<type>mtn airtime</type>";
            $data .= "<imagename>mtn.png</imagename>";
            $data .= "</item>";

            $data .= "<item>";
            $data .= "<id>8550</id>";
            $data .= "<type>vodacom airtime</type>";
            $data .= "<imagename>vodacom.png</imagename>";
            $data .= "</item>";
            
        foreach ($images as $key => $image) {
            $data .= "<item>";
            $data .= "<id>" . $image['id'] . "</id>";
            $data .= "<type>" . $image['type'] . "</type>";
            $data .= "<imagename>" . $image['filename'] . "</imagename>";
            $data .= "</item>";
        }
        $data .= "</images>";

        return array('count' => $count, 'data' => $data);
    }
    function get_new_images($user_id){
        $images = $this->app_model->get_user_images($user_id);
        $count = count($images);

        $data = "<images>";
        foreach ($images as $key => $image) {
            $data .= "<item>";
            $data .= "<id>" . $image['id'] . "</id>";
            $data .= "<type>" . $image['type'] . "</type>";
            $data .= "<imagechanged>" . $image['filename'] . "</imagechanged>";
            $data .= "</item>";
        }
        $data .= "</images>";

        return array('count' => $count, 'data' => $data);
    }

    function get_airtime_products(){
        $airtime = $this->airtime_model->get_airtime_vouchers();
        $mobile_data = $this->airtime_model->get_data_vouchers();
        $count = count($airtime)+count($mobile_data);
        $now = date("Y-m-d H:i:s");

        $data = "<vouchers>";
        foreach ($airtime as $key => $voucher) {
            $data .= "<item>";
            $data .= "<id>" . $voucher['id'] . "</id>";
            $data .= "<type>pinless_airtime</type>";
            $data .= "<netowrk>" . str_replace('p-','',$voucher['network']) . "</network>";
            $data .= "<description>" . $voucher['description'] . "</description>";
            $data .= "<sellvalue>" . $voucher['sellvalue'] . "</sellvalue>";
            $data .= "</item>";
        }
        foreach ($mobile_data as $key => $voucher) {
            $data .= "<item>";
            $data .= "<id>" . $voucher['id'] . "</id>";
            $data .= "<type>pinless_data</type>";
            $data .= "<netowrk>" . str_replace('pd-','',$voucher['network']) . "</network>";
            $data .= "<description>" . $voucher['description'] . "</description>";
            $data .= "<sellvalue>" . $voucher['sellvalue'] . "</sellvalue>";
            $data .= "</item>";
        }
        $data .= "</vouchers>";

        return array('count' => $count, 'data' => $data);
    }

    function reset_password($username){

        
        $data = "<password_reset>";
            $data .= "<sql>if you get RESETPASSWORD as message then golden. USERNAMEDOESNOTEXIST is the error.</sql>";
        $data .= "</password_reset>";

        $return['list'] =  array('count' => '0', 'data' => $data);

        $user = $this->user_model->user_search($username);
        if($user && isset($user['id'])){
            $this->event_model->track('login','app_reset_password', $user['id']);
            $this->user_model->reset_password($user['id']);
            $return['message'] = "RESETPASSWORD";
        }else{
            $this->event_model->track('login','app_reset_password_attempt', $username);
            $return['message'] = "USERNAMEDOESNOTEXIST";
        }

        return $return;        
        
    }

    function buy_airtime($user_id, $order_info){

        $result = $this->airtime_model->buy_voucher($user_id, $order_info['product'],$order_info['amount'],$order_info['cell']);
        $order_id = $result['refno'];
        
        $data = "<sql>'Now sure why this is here'</sql>";

        if($order_id){
            $data .= "<airtime_ordernum>$order_id</airtime_ordernum>";    
            $return['list'] = array('count' => '0', 'data' => $data);
            $this->event_model->track_event('app', 'airtime_purchased', 'Airtime was purchased through the app', $order_id);
            $return['message'] = "BUYAIRTIME";
        }else{
            $this->event_model->track_event('app', 'airtime_purchase_failed', 'Airtime purchase was attempted through the app error: NOFUNDS', '');
            $return['list'] = array('count' => '0', 'data' => $data);
            $return['message'] = "NOFUNDS";
        }

        return $return;
    }

    function get_product_list($customer_type, $customer_id, $category_id){

        $region = $this->customer_model->get_customer_region($customer_id);
        $region_id = $region['region_id'];
        $region_name = $region['name'];

        $products = $this->app_model->get_product_list($customer_type, $region_id, $category_id);
        $count = count($products);

        $data = "<products>";
        foreach ($products as $key => $product) {
            $startdate = strtotime($product['special_start']);
            $enddate = strtotime($product['special_end']);
            $enddate_split = explode(' ',$product['special_end']);
            $data .= "<item>";
            $data .= "<code>" . $product['id'] . "</code>";
            $data .= "<name>" . $product['name'] . "</name>";
            $data .= "<unit_price>" . $product['unit_price'] . "</unit_price>";
            $data .= "<shrink_price>" . $product['shrink_price'] . "</shrink_price>";
            $data .= "<case_price>" . $product['case_price'] . "</case_price>";
            $data .= "<price>" . $product['shrink_price'] . "</price>";
            if ($product['is_special_now'] == 1) {
                $data .= "<special_unit_price>" . $product['special_unit_price'] . "</special_unit_price>";
                $data .= "<special_shrink_price>" . $product['special_shrink_price'] . "</special_shrink_price>";
                $data .= "<special_case_price>" . $product['special_case_price'] . "</special_case_price>";
                $data .= "<special_price>" . $product['special_shrink_price'] . "</special_price>";
                $data .= "<special>1</special>";
                $data .= "<special_end>".$enddate_split[0]."</special_end>";
            } else {
                $data .= "<special>0</special>";
                $data .= "<special_end></special_end>";
            }
            $data .= "<image_prod>" . $product['picture'] . "</image_prod>";
            $data .= "</item>";
        }
        $data .= "</products>";

        return array('count' => $count, 'data' => $data);
    }

    function get_category_list($parent_id, $customer_type, $customer_id){

        $categories = $this->app_model->get_category_list($parent_id, $customer_type, $customer_id);
        $count = count($categories);
        $now = date("Y-m-d H:i:s");

        $data = "<categories>";
        foreach ($categories as $key => $category) {
            $data .= "<item>";
            $data .= "<code>" . $category['id'] . "</code>";
            $data .= "<name>" . $category['name'] . "</name>";
            $data .= "<image_cat>" . $category['icon'] . "</image_cat>";
            $data .= "</item>";
        }
        $data .= "</categories>";

        return array('count' => $count, 'data' => $data);
    }

    function get_payment_types(){

        $payment_types = $this->app_model->get_payment_types();
        $count = count($payment_types);
        $now = date("Y-m-d H:i:s");

        $data = "<payment_type_list>";
        foreach ($payment_types as $key => $type) {
            $data .= "<item>";
            $data .= "<pt_code>" . $type['id'] . "</pt_code>";
            $data .= "<pt_name>" . $type['name'] . "</pt_name>";
            $data .= "<pt_status>" . $type['status'] . "</pt_status>";
            $data .= "</item>";
        }
        $data .= "</payment_type_list>";

        return array('count' => $count, 'data' => $data);
    }

    function get_news_list(){

        $news_list = $this->app_model->get_news_list();
        $count = count($news_list);
        $now = date("Y-m-d H:i:s");

        $data = "<news_list>";
        foreach ($news_list as $key => $news) {
            $data .= "<item>";
            $data .= "<code>" . $news['id'] . "</code>";
            $data .= "<title>[NEW] " . $news['heading'] . "</title>";
            $data .= "</item>";
        }
        $data .= "</news_list>";

        return array('count' => $count, 'data' => $data);
    }

    function get_news_item($item_id){

        $news_item = $this->app_model->get_news_item($item_id);
        $count = count($news_item);
        $now = date("Y-m-d H:i:s");

        $data = "<news_item>";

        $data .= "<item>";
        $data .= "<code>" . $news_item['id'] . "</code>";
        $data .= "<news_title>" . $news_item['heading'] . "</news_title>";
        $data .= "<news_body>" . strip_tags($news_item['body']) . "</news_body>";
        $data .= "</item>";

        $data .= "</news_item>";

        return array('count' => $count, 'data' => $data);
    }

    function get_specials_list($customer_type, $customer_id){

        $region = $this->customer_model->get_customer_region($customer_id);
        $region_id = $region['region_id'];
        $region_name = $region['name'];

        $products = $this->app_model->get_specials_list($customer_type, $region_id);
        $count = count($products);


        $data = "<products>";
        foreach ($products as $key => $product) {

            $now = time(); // or your date as well
            $your_date = strtotime($product['special_end']);
            $datediff = $now - $your_date;
            $expires_in =  floor($datediff / (60 * 60 * 24));

            $data .= "<item>";
            $data .= "<code>" . $product['id'] . "</code>";
            $data .= "<name>" . $product['name'] . "</name>";
            $data .= "<special_unit_price>" . $product['special_unit_price'] . "</special_unit_price>";
            $data .= "<special_shrink_price>" . $product['special_shrink_price'] . "</special_shrink_price>";
            $data .= "<special_case_price>" . $product['special_case_price'] . "</special_case_price>";
            $data .= "<special_price>" . $product['special_shrink_price'] . "</special_price>";
            $data .= "<price>" . $product['shrink_price'] . "</price>";
            $data .= "<special>1</special>";
            $data .= "<special_end>".$expires_in."</special_end>";
            $data .= "<image_prod>" . $product['picture'] . "</image_prod>";
            $data .= "</item>";
        }
        $data .= "</products>";

        return array('count' => $count, 'data' => $data);
    }

    function get_orders_list($customer_id){

        $orders = $this->app_model->get_orders_list($customer_id);
        $count = count($orders);

        $data = "<orderhistory>";
        foreach ($orders as $key => $order) {
            $data .= "<order>";
            $data .= "<ordernumber>" . $order['id'] . "</ordernumber>";
            $data .= "<OrderDateTime>" . $order['createdate'] . "</OrderDateTime>";
            $data .= "<Status>" . $order['status'] . "</Status>";
            if ($order['delivery_date'] == "") {
                $data .= "<DeliveryDate>To Be Determined</DeliveryDate>";
            } else {
                $data .= "<DeliveryDate>" . $order['delivery_date'] . "</DeliveryDate>";
            }
            $data .= "</order>";
        }
        $data .= "</orderhistory>";

        return array('count' => $count, 'data' => $data);
    }

    function place_order($customer_id, $raw_order_info){
        $order_info = $this->clean_order_info($raw_order_info, $customer_id);
        $order_info['order_type'] = 'sale';

        $order_result = $this->app_model->insert_order($customer_id,$order_info);
        $rewards = $this->financial_model->get_rewards($customer_id);
        $balance = $this->financial_model->get_balance($customer_id);
        $airtime_balance = $this->financial_model->get_airtime_balance($customer_id);
        if($order_result['status'] == 'success'){

            $this->event_model->track_event('app', 'order_placed', 'an order was placed through the app', $order_result['message']);
            $order_id = $order_result['message'];
            $data = "<status>" . $order_result['status'] . "</status>";
            $data .= "<message>" . $order_result['message'] . "</message>";
            $data .= "<rewards>" . $rewards . "</rewards>";
            $data .= "<balance>" . $balance . "</balance>";
            $data .= "<airtime_balance>" . $airtime_balance . "</airtime_balance>";
            $data .= "<sql>'Now sure why this is here'</sql><ordernum>$order_id</ordernum>";

            return array('count' => '0', 'data' => $data);

        }else{

            $this->event_model->track_event('app', 'order_place_fail', 'an order was attempted through the app', $order_result);
            $rewards = $this->financial_model->get_rewards($customer_id);
            $data = "<status>" . $order_result['status'] . "</status>";
            $data .= "<message>" . $order_result['message'] . "</message>";
            $data .= "<rewards>" . $rewards . "</rewards>";
            $data .= "<balance>" . $balance . "</balance>";
            $data .= "<airtime_balance>" . $airtime_balance . "</airtime_balance>";
            $data .= "<sql>'Now sure why this is here'</sql><ordernum>none</ordernum>";

            return array('count' => '0', 'data' => $data);
        }
    }

    function clean_order_info($raw_order_info, $customer_id){
        $order_info = explode(',', $raw_order_info);

        if ($order_info[0] > 0) {
            $loop = 3;
            $region = $this->customer_model->get_customer_region($customer_id);
            $region_id = $region['region_id'];
            $region_name = $region['name'];
            $order['region_id'] = $region_id;

            $order['payment_type'] = $order_info[0];
            $order['delivery_type'] = $order_info[1];
            $order['order_type'] = 'sale';
            //hack to make sure we dont get negatives.
            for ($i=1; $i <= $order_info[2]; $i++) { 
                $order['items'][$i]['product_id'] = $order_info[$loop++];
                $supplier_id = $this->app_model->get_product_supplier($order['items'][$i]['product_id']);
                $distributor_id = $this->app_model->find_distributor($supplier_id, $region_id);

                $order['items'][$i]['supplier_id'] = $supplier_id;
                $order['items'][$i]['distributor_id'] = $distributor_id;
                $order['items'][$i]['quantity'] = $order_info[$loop++];
                $order['items'][$i]['price'] = $order_info[$loop++];
                $order['items'][$i]['rewards'] = round(($order['items'][$i]['price']*$order['items'][$i]['quantity']) / 100);
            }
        }

        return $order;
    }

    function login($username,$pass){
echo '11';exit;
        $rem_me = FALSE;
        if($this->aauth->login($username,$pass)){
            $this->event_model->track('login','app_login_successful');
            return true;

        }else{
            $this->event_model->track('login','app_login_attempt', $username);
            return false;
        }
    }
}
