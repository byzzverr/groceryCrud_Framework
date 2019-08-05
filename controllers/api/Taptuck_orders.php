<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller_Taptuck.php';

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
class Taptuck_orders extends REST_Controller_Taptuck {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->library("Aauth");
        $this->load->model('event_model');
        $this->load->model('app_model');
        $this->load->model('financial_model');
        $this->load->model('taptuck_model');
        $this->load->model('user_model');
        $this->load->model('tt_kid_model');
        $this->load->model('tt_parent_model');
        $this->load->model('tt_order_model');

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['user_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['user_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
    }
    
     public function place_post()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
         
        $this->app_model->save_raw_data($requestjson,'api','place_order');
         
        $requestjson = json_decode($requestjson, true);
        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $kid_id = false;
        $menu_id = false;
        $period = false;
        $date = false;
                   
        if(isset($requestjson['kid_id'])){
            $kid_id = $requestjson['kid_id'];
        }

        if(isset($requestjson['menu_id'])){
            $menu_id = $requestjson['menu_id'];
        }

        if(isset($requestjson['period'])){
            $period = $requestjson['period'];
        }

        if(isset($requestjson['date'])){
            $date = $requestjson['date'];
        }



        $user_id = $this->user_model->get_user_from_token($requestjson['token']);
        $this->event_model->track('taptuck_orders','place_post', $user_id);
        
        if($user_id && $kid_id && $menu_id && $period && $date){
            $user_info = $this->aauth->get_user($user_id);
            $order = $this->tt_order_model->place_order($user_info, $kid_id, $menu_id, $period, $date);
            
            if($order['success']){
                $data['order'] = $order['order'];
                $data['wallet_balance'] = $this->financial_model->get_wallet_balance($user_info->username);
                $message = [
                    'success' => true,
                    'meta' => array( 'code' => 200),
                    'data' => $data
                ];
            }else{
                $message = [
                    'success' => false,
                    'meta' => array( 'code' => 401, 'reason' => $order['reason']),
                    'data' => array()   
                ];
            }


        }else{

            $message = [
                'success' => false,
                'meta' => array( 'code' => 401, 'reason' => 'All required fields were not sent.'),
                'data' => array()
            ];
        }

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED);
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_UNAUTHORIZED);
        }
    }   

     public function remove_delete()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
         
        $this->app_model->save_raw_data($requestjson,'api','place_order');
         
        $requestjson = json_decode($requestjson, true);
        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $order_id = false;

        if(isset($_GET['order_id'])){
            $order_id = $_GET['order_id'];
        }

        $user_id = $this->user_model->get_user_from_token($requestjson['token']);
        $this->event_model->track('taptuck_orders','remove_delete', $user_id);
        
        if($user_id && $order_id){
            $order = $this->tt_order_model->cancel_order($order_id);

            if($order){
                $user_info = $this->aauth->get_user($user_id);
                $data['wallet_balance'] = $this->financial_model->get_wallet_balance($user_info->username);
                $message = [
                    'success' => true,
                    'meta' => array( 'code' => 200),
                    'data' => $data
                ];
            }else{

            $message = [
                    'success' => false,
                    'meta' => array( 'code' => 401, 'reason' => 'Order already cancelled'),
                    'data' => array()
                ];

            }


        }else{

                $message = [
                    'success' => false,
                    'meta' => array( 'code' => 401, 'reason' => 'All required fields were not sent.'),
                    'data' => array()
                ];

        }

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED);
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_UNAUTHORIZED);
        }
    }

     public function redeem_post()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
         
        $this->app_model->save_raw_data($requestjson,'api','redeem_order');
         
        $requestjson = json_decode($requestjson, true);
        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $order_id = false;
                           
        if(isset($requestjson['order_id'])){
            $order_id = $requestjson['order_id'];
        }

        $user_id = $this->user_model->get_user_from_token($requestjson['token']);
        $this->event_model->track('taptuck_orders','redeem_post', $user_id);
        
        if($user_id && $order_id){
            $user_info = $this->aauth->get_user($user_id);
            $order = $this->tt_order_model->redeem_order($order_id);
            if($order['success']){
                $data['order'] = $order['order'];
                $data['wallet_balance'] = $this->financial_model->get_wallet_balance($user_info->username);
                $message = [
                    'success' => true,
                    'meta' => array( 'code' => 200),
                    'data' => $data
                ];
            }else{
                $message = [
                    'success' => false,
                    'meta' => array( 'code' => 401, 'reason' => 'already redeemed'),
                    'data' => array()
                ];
            }


        }else{

                $message = [
                    'success' => false,
                    'meta' => array( 'code' => 401, 'reason' => 'All required fields were not sent.'),
                    'data' => array()
                ];

        }

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED);
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_UNAUTHORIZED);
        }
    }


     public function pocket_money_purchase_post()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
         
        $this->app_model->save_raw_data($requestjson,'api','redeem_order');
         
        $requestjson = json_decode($requestjson, true);
        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $order_id = false;
        $amount = false;
                           
        if(isset($requestjson['kid_id'])){
            $kid_id = $requestjson['kid_id'];
        }                           

        if(isset($requestjson['amount'])){
            $amount = $requestjson['amount'];
        }

        $user_id = $this->user_model->get_user_from_token($requestjson['token']);
        
        if($user_id && $kid_id && $amount){
            $this->event_model->track('taptuck_orders','pocket_money_purchase', $user_id);
            $user_info = $this->aauth->get_user($user_id);

            $order = $this->tt_order_model->pocket_money_purchase($kid_id, $amount, $user_info->username);

            if($order){
                $data['wallet_balance'] = $this->financial_model->get_wallet_balance($user_info->username);
                $message = [
                    'success' => true,
                    'meta' => array( 'code' => 200),
                    'data' => $data
                ];
            }else{
                $message = [
                    'success' => false,
                    'meta' => array( 'code' => 401, 'reason' => 'Insufficient Funds.'),
                    'data' => array()
                ];
            }


        }else{

                $message = [
                    'success' => false,
                    'meta' => array( 'code' => 401, 'reason' => 'All required fields were not sent. kid_id, amount, token'),
                    'data' => array()
                ];

        }

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED);
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_UNAUTHORIZED);
        }
    } 

     public function redeem_all_post()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
         
        $this->app_model->save_raw_data($requestjson,'api','redeem_order');
         
        $requestjson = json_decode($requestjson, true);
        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $order_id = false;

        $expected_array = array(
            'kid_id',
            'period',
            'date'
            );

        $message = "Token not linked to any user.";

        $error = false;

        foreach ($expected_array as $field) {
            if(!isset($requestjson[$field])){
                $error = true;
                $message = "Could not find '$field' in post array";
            }
        }

        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        $this->event_model->track('taptuck_orders','redeem_post', $user_id);
        
        if($user_id && !$error){
            
            $user_info = $this->aauth->get_user($user_id);

            $order = $this->tt_order_model->redeem_orders($requestjson);

            if(!empty($order)){
                $data['orders'] = $order;
                $data['wallet_balance'] = $this->financial_model->get_wallet_balance($user_info->username);
                $message = [
                    'success' => true,
                    'meta' => array( 'code' => 200),
                    'data' => $data
                ];
            }else{
                $message = [
                    'success' => false,
                    'meta' => array( 'code' => 401, 'reason' => 'already redeemed'),
                    'data' => array()
                ];
            }


        }else{

                $message = [
                    'success' => false,
                    'meta' => array( 'code' => 401, 'reason' => $message),
                    'data' => array()
                ];

        }

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED);
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_UNAUTHORIZED);
        }
    }   

}