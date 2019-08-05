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
class Wallet extends REST_Controller_Taptuck {

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
    
    function wallet_transactions_post(){
        $message['success'] = true;
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','wallet_transactions_post');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $user_id = $this->user_model->get_user_from_token($requestjson['token']);
        $user_info = $this->user_model->get_user($user_id);
        if($user_id){

            $transactions = $this->financial_model->get_wallet_transactions($user_info->username);
            $message = [
            'success' => true, 
            'data' => $transactions,
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

    function transactions_break_down_post(){
        $message['success'] = true;
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','transactions_break_down_post');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $user_id = $this->user_model->get_user_from_token($requestjson['token']);
        $user_info = $this->user_model->get_user($user_id);

        if($user_id){

            $data['sale'] = 0;
            $data['deposit'] = 0;
            $data['commission'] = 0;
            $data['refund'] = 0;
            $data['purchase'] = 0;
            
            $transactions = $this->financial_model->get_wallet_transactions($user_info->username);
            foreach ($transactions as $key => $value) {

                if($value['category']=='deposit' or $value['category']=='sale' or $value['category']=='commission' or $value['category']=='refund' or $value['category']=='purchase'){
                     if($value['debit'] == 0){
                        $data[$value['category']] += $value['credit'];
                    }else{
                        $data[$value['category']] -= $value['debit'];
                    }
                }
                   
            }

            $message = [
            'success' => true, 
            'data' => $data,
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

    function wallet_combined_post(){
        $message['success'] = true;
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','wallet_combined_post');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $user_id = $this->user_model->get_user_from_token($requestjson['token']);
        $user_info = $this->user_model->get_user($user_id);

        if($user_id){

            $balance = $this->financial_model->get_wallet_balance($user_info->username);
            
            $data['balance'] = $balance;
            $data['sale'] = 0;
            $data['deposit'] = 0;
            $data['commission'] = 0;
            $data['refund'] = 0;
            $data['purchase'] = 0;
            
            $transactions = $this->financial_model->get_wallet_transactions($user_info->username);
            foreach ($transactions as $key => $value) {

                if($value['category']=='deposit' or $value['category']=='sale' or $value['category']=='commission' or $value['category']=='refund' or $value['category']=='purchase'){
                     if($value['debit'] == 0){
                        $data[$value['category']] += $value['credit'];
                    }else{
                        $data[$value['category']] -= $value['debit'];
                    }
                }
                   
            }

            $message = [
            'success' => true, 
            'data' => array("breakdown" => $data, "transactions" => $transactions),
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

    function masterpass_get(){

        $this->load->library('masterpass');
        $data["amount"] = 5;
        $data["merchantReference"] = "topuptest".date("Ymd_His");
        $data["useOnce"] = true;
        
        $result = $this->masterpass->create_code($data);
        print_r($result);
    }

    function masterpass_code_post(){

        $result['success'] = false;
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','masterpass_code');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if(isset($requestjson['token']) && isset($requestjson['amount'])){

            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            $this->load->library('masterpass');
            $data["user_id"] = $user_id;
            $data["amount"] = $requestjson['amount'];
            $data["merchantReference"] = $user_id.'_'.date("Ymd_His");
            $data["useOnce"] = true;
            
            $result = $this->masterpass->create_code($data);
            if($result){
                $result['success'] = true;
            }else{
                $result = array();
                $result['success'] = false;
                $result['message'] = "Failed. Please try again.";
            }
        }
        
        if($result['success']){
            $this->set_response($result, REST_Controller_Taptuck::HTTP_CREATED); 
        }else{
            $this->set_response($result, REST_Controller_Taptuck::HTTP_NOT_ACCEPTABLE); 
        }
    }

    function queue_money_send_post(){
        $message['success'] = true;
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','queue_money_send');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        if($user_id){
        $user_info = $this->user_model->get_user($user_id);
        $amount = $requestjson['amount'];
        $beneficiary_msisdn = '0' . substr($requestjson['beneficiary_msisdn'],-9);

            $balance = $this->financial_model->get_wallet_balance($user_info->username);

            if($balance >= $amount && $amount >= 25 && $amount <= 2500){

                $beneficiary = $this->user_model->get_user_from_username($beneficiary_msisdn);

                if($beneficiary){

                    $this->financial_model->queue_money_send($user_id, $user_info->username, $beneficiary_msisdn, $beneficiary['id'], $amount);
                    $balance = $this->financial_model->get_wallet_balance($user_info->username);

                    $message = [
                        'success' => true, 
                        'data' => array("balance" => $balance),
                        'message' => "Money send has been queued. Please approve transaction with OTP."
                    ];
                }else{

                    $message = [
                        'success' => false, 
                        'data' => array(),
                        'reason' => "Beneficiary number ($beneficiary_msisdn) could not be found."
                    ];

                }

            }else{

                $message = [
                    'success' => false, 
                    'data' => array(),
                    'reason' => "Not enough funds in wallet. Amount must be over R25 and under R2500."
                ];

            }
                        
        }else{
            $message = [
            'success' => false, 
            'data' => array(),
            'reason' => "Token not valid or no amount sent."
            ];
        }

       $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); 

    }


    function approve_money_send_post(){
        $message['success'] = true;
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','approve_money_send');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $user_id = $this->user_model->get_user_from_token($requestjson['token']);
        $user_info = $this->user_model->get_user($user_id);

        if($user_id){
            $otp = false;
            if(isset($requestjson['otp'])){
            $otp = trim($requestjson['otp']);
            }

            if($otp){

                $otp_confirmation = $this->financial_model->comfirm_money_send_otp($user_id, $otp);
                
                if($otp_confirmation){

                    $this->financial_model->approve_money_send($user_id, $otp);

                    $balance = $this->financial_model->get_wallet_balance($user_info->username);

                    $message = [
                        'success' => true, 
                        'data' => array("balance" => $balance),
                        'message' => "Money send has been completed."
                    ];
                }else{

                    $message = [
                        'success' => false, 
                        'data' => array(),
                        'reason' => "OTP Incorrect."
                    ];

                }

            }else{

                $message = [
                    'success' => false, 
                    'data' => array(),
                    'reason' => "Please supply an OTP."
                ];

            }
                        
        }else{
            $message = [
            'success' => false, 
            'data' => array(),
            'reason' => "Token not valid or no amount sent."
            ];
        }

            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); 

    }
}