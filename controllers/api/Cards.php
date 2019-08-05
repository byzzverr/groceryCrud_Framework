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
class Cards extends REST_Controller_Taptuck {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->library("Aauth");
        $this->load->model('event_model');
        $this->load->model('app_model');
        $this->load->model('financial_model');
        $this->load->model('tt_kid_model');
        $this->load->model('tt_parent_model');
        $this->load->model('cards_model');

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['user_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['user_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
    }
    
     public function cards_get(){
        
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','get_cards');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }


        $this->load->model('user_model');
        $this->load->model('customer_model');
        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        if($user_id){
            $cards = $this->cards_model->get_cards($user_id);          
          
             $message = [
            'success' => true, 
            'data' => $cards,
            'message' => "Ok"
            ];
            
        }else{
            $message = [
            'success' => false, 
            'data' => array(),
            'reason' => "Token not valid."
            ];
        }
       

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_NOT_ACCEPTABLE); 
        }
        
    }

    public function card_post()
    {
        $message['success'] = true;
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','card_post');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        if($user_id){
                                  
           
            $requestjson['user_id'] = $user_id;

            if(!isset($requestjson['card_name'])){
                $message = [
                'success' => false, 
                'data' => array(),
                'message' => "card name is required"
                ];
                
            }

            if(!isset($requestjson['card_number']) || strlen($requestjson['card_number']) != 16){
                $message = [
                'success' => false, 
                'data' => array(),
                'message' => "16 digit card number is required"
                ];
                
            }
            
            if(!isset($requestjson['exp_year'])){
                $message = [
                'success' => false, 
                'data' => array(),
                'message' => "expiry year is required"
                ];
            }

            if(!isset($requestjson['exp_month'])){
                $message = [
                'success' => false, 
                'data' => array(),
                'message' => "expiry month is required"
                ];
            }

            if(!isset($requestjson['cvv']) || strlen($requestjson['cvv']) != 3){
                $message = [
                'success' => false, 
                'data' => array(),
                'message' => "3 digit cvv is required"
                ];
            }

            if($message['success']){

                if (strlen($requestjson['exp_month']) == 1) {
                    $requestjson['exp_month'] = "0".$requestjson['exp_month'];
                }

                if (strlen($requestjson['exp_year']) == 2) {
                    $requestjson['exp_year'] = "20".$requestjson['exp_year'];
                }
                
                //send back ONLY the card that was added.
                $card_id = $this->cards_model->add_card($requestjson);
                if($card_id){

                    $card = $this->cards_model->get_card($card_id);
                     $message = [
                    'success' => true, 
                    'data' => $card,
                    'message' => "New card added successfully"
                    ];
                    
                }else{

                    $cards = $this->cards_model->get_cards($user_id);
                     $message = [
                    'success' => false, 
                    'data' => $cards,
                    'message' => "Could not add new card. Please try again."
                    ];
                }       
            }
            
        }else{
            $message = [
            'success' => false, 
            'data' => array(),
            'reason' => "Token not valid."
            ];
        }

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_NOT_ACCEPTABLE); 
        }

    }

     public function card_get(){

        $requestjson = array(
          "card_name" => "B J VERREYNE",
          "card_number" => "4000000000000002",
          "exp_year" => "2018",
          "exp_month" => "02",
          "cvv" => "123"
        );

        $card_id = $this->cards_model->add_card($requestjson);

        print_r($card_id);

     }
     
     public function card_delete($card_id='')
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','card_delete');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if($card_id != '' && !empty($card_id)){
            $requestjson['card_id'] =  $card_id;
        }

        $this->load->model('user_model');
        $this->load->model('customer_model');
        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        if($user_id){

            if(!isset($requestjson['card_id'])){
                $message = [
                'success' => false, 
                'data' => array(),
                'message' => "please supply card_id"
                ];
            }else{

                $this->cards_model->delete_card($requestjson['card_id']);          
                $cards = $this->cards_model->get_cards($user_id);
              
                 $message = [
                'success' => true, 
                'data' => $cards,
                'message' => "Card removed successfully"
                ];
            }
            
        }else{
            $message = [
            'success' => false, 
            'data' => array(),
            'reason' => "Token not valid."
            ];
        }

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_NOT_ACCEPTABLE); 
        }
        
    }

    function payment_post(){

        $message['success'] = true;
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','payment_post');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        if($user_id){
           
            $requestjson['user_id'] = $user_id;
            $user = $this->user_model->get_general_user($user_id);


            if(!isset($requestjson['card_token'])){
                $message = [
                'success' => false, 
                'data' => array(),
                'message' => "card token is required"
                ];
            }

            $requestjson['card_token'] = trim($requestjson['card_token']);

            if(!$this->cards_model->check_ownership($requestjson['card_token'], $user_id)){
                $message = [
                'success' => false, 
                'data' => array(),
                'message' => "card token does not belong to you."
                ];
            }

            if(isset($requestjson['amount'])){
                $requestjson['amount'] = str_replace(',', '', $requestjson['amount']);
                $raw_amount = explode('.', $requestjson['amount']);
                if(is_array($raw_amount)){
                    $requestjson['amount'] = $raw_amount[0];
                }
            }

            if(!isset($requestjson['amount']) || $requestjson['amount'] < 2 || $requestjson['amount'] > 3000){
                $message = [
                'success' => false, 
                'data' => array(),
                'message' => "please post amount R2 - R3000 in rands eg: 23.45"
                ];
                
            }

            if(!isset($requestjson['cvv']) || strlen($requestjson['cvv']) != 3){
                $message = [
                'success' => false, 
                'data' => array(),
                'message' => "3 digit cvv is required"
                ];
            }

            if($message['success']){
                $amount = $requestjson['amount'];
                //amount in cents
                $requestjson['amount'] = str_replace(array('.',','), '', $requestjson['amount'])*100;
                $requestjson['cref'] = 'card-'.$user_id.'-'.$requestjson['amount'].'-'.rand(111111,999999);
                $paygate = $this->financial_model->insert_cref($requestjson['cref']);
                $pay_result = $this->cards_model->process_payment($requestjson, $user);

                if($pay_result['success']){
                    $requestjson['amount'] = $amount;
                    $this->financial_model->process_cc_payment($requestjson, $pay_result['data']);
                     $message = [
                    'success' => true, 
                    'data' => $pay_result['data'],
                    'message' => "transaction successful"
                    ];
                    
                }else{

                     $message = [
                    'success' => false, 
                    'message' => $pay_result['reason']
                    ];
                }       
            }
            
        }else{
            $message = [
            'success' => false, 
            'data' => array(),
            'reason' => "Token not valid."
            ];
        }

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_NOT_ACCEPTABLE); 
        }

    }
}