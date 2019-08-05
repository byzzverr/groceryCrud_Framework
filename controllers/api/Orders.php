<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Orders extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->library("Aauth");
        $this->load->model('event_model');
        $this->load->model('app_model');
        $this->load->model('financial_model');
        $this->load->model('user_model');
        $this->load->model('task_model');
        $this->load->model('order_model');
        $this->load->model('product_model');

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['user_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['user_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
    }


    public function get_saved_baskets_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','get_saved_baskets_post');
        $requestjson = json_decode($requestjson, true);

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }

            if($user_id){

                $user = $this->user_model->get_general_user($user_id);
                $customer_id = $user->user_link_id;

                $result = $this->app_model->get_basket_list($customer_id);

                if($result){

                    $message = [
                    'success' => true, // Order placed successfully
                    'data' => $result,
                    'message' => "Please see saved baskets."
                    ];
                }else{
                    $message = [
                    'success' => true, // no baskets but still successfull call
                    'data' => $result,
                    'message' => 'No saved baskets.'
                    ];
                }
            }else{
                $message = [
                'success' => false, // token not valid
                'data' => array(),
                'message' => "Token not valid."
                ];
            }
        }else{

            $message = [
                'success' => false, // no token found
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function get_saved_basket_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','get_saved_basket_post');
        $requestjson = json_decode($requestjson, true);

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }

            if($user_id){

                $user = $this->user_model->get_general_user($user_id);
                $customer_id = $user->user_link_id;

                $result = $this->app_model->get_basket($requestjson['basket_id']);

                if($result){

                    $message = [
                    'success' => true, // Order placed successfully
                    'data'  => $result,
                    'message' => "Please see saved baskets."
                    ];
                }else{
                    $message = [
                    'success' => false, // order failed.
                    'data' => $result,
                    'message' => "No basket found."
                    ];
                }
            }else{
                $message = [
                'success' => false, // token not valid
                'data' => array(),
                'message' => "Token not valid."
                ];
            }
        }else{

            $message = [
                'success' => false, // no token found
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }


    public function validate_checkout_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','validate_checkout_post');
        $requestjson = json_decode($requestjson, true);

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }

            if ($requestjson['order_type'] != '' && !empty($requestjson['order_type'])){

                if($user_id){

                    $user = $this->user_model->get_general_user($user_id);
                    $customer_id = $user->user_link_id;

                    $result = $this->validate_checkout($customer_id, $requestjson);

                    if($result['status'] == 'success'){

                        $message = [
                        'success' => true, // Order placed successfully
                        'data' => $result,
                        'message' => "Please see order details."
                        ];
                    }else{
                        $message = [
                        'success' => false, // order failed.
                        'data' => $result
                        ];
                    }
                }else{
                    $message = [
                    'success' => false, // token not valid
                    'data' => array(),
                    'message' => "Token not valid."
                    ];
                }
            }else{
                $message = [
                'success' => false, // order type was empty
                'data' => array(),
                'message' => "Please send an order type."
                ];
            }
        }else{

            $message = [
                'success' => false, // no token found
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }


    function validate_checkout($customer_id, $order_info, $action=false, $replaced_order=false)
    {
        $region = $this->customer_model->get_customer_region($customer_id);
        $region_id = $region['region_id'];
        $region_name = $region['name'];

        $order['region_id'] = $region_id;
        $order['payment_type'] = $order_info['payment_type'];
        $order['delivery_type'] = $order_info['delivery_type'];
        $order['order_type'] = $order_info['order_type'];
        if(isset($order_info['basket_name'])){
            $order['basket_name'] = $order_info['basket_name'];
        }
        $count = 0;

        foreach ($order_info['items'] as $key => $product) 
        {
            /*$customer_type, $region_id, $category_id, $specials=false, $product_id=false, $distributor_id=false)*/
            $latest_product = $this->product_model->get_products_for_region(0, $region_id, 0, false, $product['product_id']);

            if($latest_product){
                $count++;
                $supplier_id = $latest_product['supplier_id'];
                $distributor_id = $latest_product['distributor_id'];

                $status_id = '8';

                $order['items'][$count]['product_id'] = $product['product_id'];
                $order['items'][$count]['supplier_id'] = $supplier_id;
                $order['items'][$count]['distributor_id'] = $distributor_id;
                $order['items'][$count]['quantity'] = $product['quantity'];
                $order['items'][$count]['price'] = $latest_product['shrink_price'];
                if($latest_product['is_special_now'] == 1){
                    $order['items'][$count]['price'] = $latest_product['special_shrink_price'];
                }
            }
        }

        //this means the customer has confirmed their order.
        //we now need to provide 2 deals back instead of one.
        //deal 1 is the cheapest no matter what delivery charge.
        //deal 2 we remove all delivery charges possible if required.

        if($action){
            if(!$replaced_order){
                $replaced_order = 0;
            }

            //when you add true as the 3rd parameter it stores basket only not order.
            if($action == 'save_basket'){
                $order['replaced_order'] = 0;
                $order['basket_id'] = 0;
                $basket = $this->app_model->insert_order($customer_id,$order,TRUE);
                $order['basket_id'] = $basket['message'];
            }else{
                //spazapp calculate the deals.
                //when you add true as the 3rd parameter it stores basket only not order.
                $deals = $this->app_model->find_best_deals($order);
                $deals['deal1']['basket_id'] = 0;
                $deals['deal1']['replaced_order'] = $replaced_order;
                $deal1 = $this->app_model->insert_order($customer_id,$deals['deal1'],TRUE);
                $deals['deal2']['basket_id'] = $deal1['message'];
                $deals['deal2']['replaced_order'] = $replaced_order;
                $deal2 = $this->app_model->insert_order($customer_id,$deals['deal2'],TRUE);
                $deals['deal1']['id'] = $deal1['message'];
                $deals['deal2']['id'] = $deal2['message'];
                $order = $deals;
            }
        }

        $rewards = 0;
        $balance = $this->financial_model->get_balance($customer_id);

        $this->event_model->track_event('app', 'basket_placed', 'a basket was added through the app', 'success');
        
        $data['status'] = 'success';
        $data['rewards'] = $rewards;
        $data['balance'] = $balance;
        $data['order'] = $order;

        return $data;
    }


    public function save_basket_post(){

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
        $user_id = $this->user_model->get_user_from_token($requestjson['token']);
        $user = $this->user_model->get_general_user($user_id);
        $customer_id = $user->user_link_id;
    
        if(!$this->order_model->verify_basket_name($requestjson['basket_name'], $customer_id)){

            $message = [
                'success' => false, // no token found
                'data' => array(),
                'message' => "You already have a list with this name."
            ];
            
            $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code

        }else{
            $this->calculate_deals_post('save_basket');
        }
    }

    public function calculate_deals_post($action='calculate')
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','calculate_deals_post');
        $requestjson = json_decode($requestjson, true);

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }

            if ($requestjson['order_type'] != '' && !empty($requestjson['order_type'])){

                if($user_id){

                    $user = $this->user_model->get_general_user($user_id);
                    $customer_id = $user->user_link_id;

                    $replaced_order = false;
                    if(isset($requestjson['replaced_order']) && $requestjson['replaced_order'] > 0){
                        $replaced_order = $requestjson['replaced_order'];
                    }

                    $result = $this->validate_checkout($customer_id, $requestjson, $action, $replaced_order);

                    if($result['status'] == 'success'){

                        $message = [
                        'success' => true, // Order placed successfully
                        'data' => $result,
                        'message' => "Please see order details."
                        ];
                    }else{
                        $message = [
                        'success' => false, // order failed.
                        'data' => $result
                        ];
                    }
                }else{
                    $message = [
                    'success' => false, // token not valid
                    'data' => array(),
                    'message' => "Token not valid."
                    ];
                }
            }else{
                $message = [
                'success' => false, // order type was empty
                'data' => array(),
                'message' => "Please send an order type."
                ];
            }
        }else{

            $message = [
                'success' => false, // no token found
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }


    public function get_payment_types_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','get_payment_types_post');
        $requestjson = json_decode($requestjson, true);

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }
        
            if($user_id){

                $user = $this->user_model->get_general_user($user_id);
                $customer_id = $user->user_link_id;

                $payment_types = $this->app_model->get_payment_types();

                if($payment_types){

                    $message = [
                    'success' => true, // Order placed successfully
                    'data' => $payment_types,
                    'message' => "Please see payment types."
                    ];

                }else{
                    $message = [
                    'success' => false, // order failed.
                    'data' => array(),
                    'message' => "No order types available"
                    ];
                }
            }else{
                $message = [
                'success' => false, // token not valid
                'data' => array(),
                'message' => "Token not valid."
                ];
            }

        }else{

            $message = [
                'success' => false, // no token found
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function get_orders_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','get_orders_post');
        $requestjson = json_decode($requestjson, true);

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }
        
            if($user_id){

                $user = $this->user_model->get_general_user($user_id);
                $customer_id = $user->user_link_id;

                $orders = $this->app_model->get_orders_list($customer_id);

                if($orders){

                    $message = [
                    'success' => true, // Order placed successfully
                    'data' => $orders,
                    'message' => "Please see order details."
                    ];

                }else{
                    $message = [
                    'success' => false, // order failed.
                    'data' => array(),
                    'message' => "No orders placed"
                    ];
                }
            }else{
                $message = [
                'success' => false, // token not valid
                'data' => array(),
                'message' => "Token not valid."
                ];
            }

        }else{

            $message = [
                'success' => false, // no token found
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function place_order_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','place_order_post');
        $requestjson = json_decode($requestjson, true);

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }

            if(isset($requestjson['deal_id']) && $requestjson['deal_id'] != 0){
                //ok so if you are getting a dealid it is the checkout v2.0
                //populate the order from the basket db and carry on like normal.
                $payment_type = $requestjson['payment_type'];
                $requestjson = $this->order_model->get_basket($requestjson['deal_id']);
                $requestjson['payment_type'] = $payment_type;
            }

            if ($requestjson['order_type'] != '' && !empty($requestjson['order_type'])){

                if($user_id){

                    $user = $this->user_model->get_general_user($user_id);
                    $customer_id = $user->user_link_id;

                    $result = $this->place_order($customer_id, $requestjson, $user->username);

                    if($result['status'] == 'success'){

                        //check if a task id was sent.
                        if (isset($requestjson['task_id']) && $requestjson['task_id'] != '' && !empty($requestjson['task_id'])){
                            $this->task_model->save_order_id_to_task($requestjson['task_id'], $result['order_id'], $user_id);
                        }

                        $message = [
                        'success' => true, // Order placed successfully
                        'data' => $result,
                        'message' => "Please see order details."
                        ];
                    }else{
                        $message = [
                        'success' => false, // order failed.
                        'data' => $result
                        ];
                    }
                }else{
                    $message = [
                    'success' => false, // token not valid
                    'data' => array(),
                    'message' => "Token not valid."
                    ];
                }
            }else{
                $message = [
                'success' => false, // order type was empty
                'data' => array(),
                'message' => "Please send an order type."
                ];
            }
        }else{

            $message = [
                'success' => false, // no token found
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    function place_order($customer_id, $order_info, $username='')
    {
        $region = $this->customer_model->get_customer_region($customer_id);
        $region_id = $region['region_id'];
        $region_name = $region['name'];

        $order['region_id'] = $region_id;
        $order['payment_type'] = $order_info['payment_type'];
        $order['delivery_type'] = $order_info['delivery_type'];
        $order['order_type'] = $order_info['order_type'];

        if(isset($order_info['delivery_date'])){
            $order['delivery_date'] = $order_info['delivery_date'];
        }


        $order_total = 0;
        $new_total = 0;

        foreach ($order_info['items'] as $key => $product) 
        {
            $order_total += ($product['price']*$product['quantity']);
        }

        if(isset($username)){
            $this->load->model('rewards_model');
            $rewards = $this->rewards_model->categorise_user($username, $order_total);
        }

        $order['discount'] = $rewards['current_discount'];
        $discount = $rewards['current_discount']/100;

        foreach ($order_info['items'] as $key => $product) 
        {

            if(!isset($product['supplier_id']) || empty($product['supplier_id'])){
                $supplier_id = $this->app_model->get_product_supplier($product['product_id']);
            }else{
                $supplier_id = $product['supplier_id'];
            }

            if(!isset($product['distributor_id']) || empty($product['distributor_id'])){
                $distributor_id = $this->app_model->find_distributor($supplier_id, $region_id);
            }else{
                $distributor_id = $product['distributor_id'];
            }

            $status_id = '8';

            $order['items'][$key]['product_id'] = $product['product_id'];
            $order['items'][$key]['supplier_id'] = $supplier_id;
            $order['items'][$key]['distributor_id'] = $distributor_id;
            $order['items'][$key]['quantity'] = $product['quantity'];
            $item_discount = round($product['price']*$discount, 2);
            $order['items'][$key]['price'] = $product['price']-$item_discount;

            $new_total += (($product['price']-$item_discount)*$product['quantity']);

            $reward = $this->order_model->getProductSpecialStatus($product['product_id'], $status_id, $distributor_id, $product['quantity']);

            if($reward == true)
            {
                $data['addCount'] = $product['quantity'];

                $giveRewards = $this->order_model->updateSpecialsOrders($product['product_id'], $status_id, $data);

                if($giveRewards)
                {
                    $message = "Specials updated";
                }
                else
                {
                    $message = "Failed to update specials";
                }
            }
            else
            {
                $message = "No specials";
            }
        }

        $distributor = $this->app_model->get_distributor_info($distributor_id);

        $data['delivery_charge'] = 0;
        $data['delivery_charge_details'] = array();

        if($order_total < $distributor['minimum_basket']){
              $delivery_charge = $distributor['delivery_charge'];
              $delivery_charges = array(
                  'distributor_id'  => $distributor_id,
                  'order_total'     => $order_total,
                  'delivery_charge' => $distributor['delivery_charge'],
                  'minimum_basket'  => $distributor['minimum_basket'],
                  'difference'      => $distributor['minimum_basket']-$order_total
                );

            $data['delivery_charge'] = $delivery_charge;
            $data['delivery_charge_details'] = $delivery_charges;
            $new_total += $delivery_charge;
        }

        if(isset($delivery_charge)){

            $delivery_item = array();
            $delivery_item['product_id'] = 1;
            $delivery_item['supplier_id'] = $supplier_id;
            $delivery_item['distributor_id'] = $distributor_id;
            $delivery_item['quantity'] = 1;
            $delivery_item['price'] = $delivery_charge;

            $order['items'][] = $delivery_item;
        }

        $order_result = $this->app_model->insert_order($customer_id,$order);
        $balance = $this->financial_model->get_balance($customer_id);

        if($order_result['status'] == 'success'){

            if (isset($order_info['replaced_order']) && $order_info['replaced_order'] > 0) {
                 $this->app_model->cancel_order($order_info['replaced_order']);
             } 

            $this->event_model->track_event('app', 'order_placed', 'an order was placed through the app', $order_result['message']);
            $order_id = $order_result['message'];
            
            $data['product_special'] = $reward;
            $data['status'] = $order_result['status'];
            $data['order_id'] = $order_result['message'];
            $data['order_total'] = $new_total;
            $data['discount'] = $order['discount'];
            $data['balance'] = $balance;

            return $data;
        }
        else
        {
            $this->event_model->track_event('app', 'order_place_fail', 'an order was attempted through the app', $order_result);
            $rewards = $this->financial_model->get_rewards($customer_id);
            
            $data['status'] = $order_result['status'];
            $data['message'] = $order_result['message'];
            $data['rewards'] = $rewards;
            $data['balance'] = $balance;

            return $data;
        }
    }

    public function edit_distributor_order_post()
    {
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','edit_distributor_order_post');
        $requestjson = json_decode($requestjson, true);

        if ($requestjson['token'] != '' && !empty($requestjson['token']))
        {
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if ($requestjson['distributor_order_id'] != '' && !empty($requestjson['distributor_order_id']))
            {
                if($user_id)
                {

                    $order = $requestjson['order'];

                    $result = $this->replace_order($requestjson['distributor_order_id'], $order);

                    if($result['status'] == 'success')
                    {

                        $message = [
                        'success' => true, // Order placed successfully
                        'data' => $result,
                        'message' => "Please see order details."
                        ];
                    }
                    else
                    {
                        $message = [
                        'success' => false, // order failed.
                        'data' => $result
                        ];
                    }
                }
                else
                {
                    $message = [
                    'success' => false, // token not valid
                    'data' => array(),
                    'message' => "Token not valid."
                    ];
                }
            }
            else
            {
                $message = [
                'success' => false, // order type was empty
                'data' => array(),
                'message' => "Please send an order type."
                ];
            }
        }
        else
        {
            $message = [
                'success' => false, // no token found
                'data' => array(),
                'message' => "Please post a valid token."
            ];            
        }        

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    function replace_order($distributor_order_id, $order_info)
    {
        $this->load->model('order_model');
        //go get the old order
        $old_order = $this->order_model->get_dis_order($distributor_order_id);
        $customer_id = $old_order['customer']['id'];
        $distributor_id = $old_order['distributor_id'];
        $order_id = $old_order['order_id'];
        $order_info['order_id'] = $order_id;
        $order_info['distributor_id'] = $distributor_id;
        
        //get customer region.
        $region = $this->customer_model->get_customer_region($customer_id);
        $region_id = $region['region_id'];
        $region_name = $region['name'];

        $order['region_id'] = $region_id;
        $order['payment_type'] = $order_info['payment_type'];
        $order['delivery_type'] = $order_info['delivery_type'];
        $order['order_type'] = $order_info['order_type'];

        foreach ($order_info['items'] as $key => $product) 
        {            
            $supplier_id = $this->app_model->get_product_supplier($product['product_id']);
            $distributor_id = $this->app_model->find_distributor($supplier_id, $region_id);

            $order['items'][$key]['product_id'] = $product['product_id'];
            $order['items'][$key]['supplier_id'] = $supplier_id;
            $order['items'][$key]['distributor_id'] = $distributor_id;
            $order['items'][$key]['quantity'] = $product['quantity'];
            $order['items'][$key]['price'] = $product['price'];
            $order['items'][$key]['rewards'] = round(($product['price']*$product['quantity']) / 100);
        }

        //copy the old order to a new order id
        $this->order_model->copy_distributor_order($distributor_order_id);

        //remove the old order
        $this->order_model->delete_dis_order($distributor_order_id);

        //create the new order ontop of the old id.
        $order_result = $this->app_model->insert_dist_order($customer_id, $order, $order_info, $distributor_order_id);

        $rewards = $this->financial_model->get_rewards($customer_id);
        $balance = $this->financial_model->get_balance($customer_id);

        if($order_result['status'] == 'success')
        {
            $this->event_model->track_event('app', 'dis_order_replaced', 'a distributor order was replaced through the app', $order_result['message']);
            
            $data['status'] = $order_result['status'];
            $data['distributor_order_id'] = $order_result['message'];
            $data['rewards'] = $rewards;
            $data['balance'] = $balance;

            return $data;
        }
        else
        {
            $this->event_model->track_event('app', 'dis_order_replace_fail', 'a distrbutor order replacement was attempted through the app', $order_result);
            $rewards = $this->financial_model->get_rewards($customer_id);
            
            $data['status'] = $order_result['status'];
            $data['message'] = $order_result['message'];
            $data['rewards'] = $rewards;
            $data['balance'] = $balance;

            return $data;
        }
    }
}