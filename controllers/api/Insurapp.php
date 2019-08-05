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
class Insurapp extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
    //
        $this->load->library("Aauth");
        $this->load->model('event_model');
        $this->load->model('app_model');
        $this->load->model('financial_model');
        $this->load->model('user_model');
        $this->load->model('insurance_model');
        $this->load->model('comms_model');   
    }

     public function get_product_types_post()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','get_product_types_get');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                $user = $this->insurance_model->define_user($user_id);
                if(isset($user['link']['agency_id'])){

                    $data = array();
                    $data['user'] = $user;
                    $data['product_types'] = $this->insurance_model->get_product_types($user['link']['agency_id']);
                    $data['wallets'] = $this->comms_wallet_model->get_balances($user['username']);
                    
                     $message = [
                        'meta' => array('code' => 201),
                        'success' => true, 
                    'data' => $data,
                    'message' => "Ok"
                    ];
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array(),
                    'message' => "This user does not have permission to sell insurance."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array(),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => false, 
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
        
    }

     public function get_products_post($type='all')
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','get_products_post');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                $user = $this->insurance_model->define_user($user_id);
                if(isset($user['link']['agency_id'])){

                    $data = array();
                    $data['user'] = $user;
                    $data['products'] = $this->insurance_model->get_products($user['link']['agency_id'], $type);
                    $data['wallets'] = $this->comms_wallet_model->get_balances($user['username']);
                    
                     $message = [
                        'meta' => array('code' => 201),
                        'success' => true, 
                    'data' => $data,
                    'message' => "Ok"
                    ];
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array(),
                    'message' => "This user does not have permission to sell insurance."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array(),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => false, 
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
        
    }


     public function policies_search_post()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','policies_search');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                $user = $this->insurance_model->define_user($user_id);
                if(isset($user['link']['agency_id'])){

                    if(isset($requestjson['search_term']) && strlen($requestjson['search_term']) >= 5){

                        $policies = $this->insurance_model->applications_search($requestjson['search_term']);

                        if($policies){

                            $data = array();
                            $data['status'] = 1;
                            $data['policies'] = $policies;
                            $data['wallets'] = $this->comms_wallet_model->get_balances($user['username']);
                            
                             $message = [
                                'meta' => array('code' => 201),
                                'success' => true, 
                            'data' => $data,
                            'message' => "Ok"
                            ];
                        }else{

                            $message = [
                            'success' => true, 
                            'data' => array("status" => 0, "policies" => array()),
                            'message' => "There are no policies that match your search."
                            ];
                        }
                    }else{
                        $message = [
                        'success' => true, 
                        'error' => true,  
                        'data' => array("status" => "N"),
                        'message' => "Please supply a search_term."
                        ];
                    }
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array("status" => "N"),
                    'message' => "This user does not have permission to sell insurance."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array("status" => "N"),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => false, 
                'data' => array("status" => "N"),
                'message' => "Please post a valid token."
            ];
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
        
    }

     public function policy_search_post()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','policy_search');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                $user = $this->insurance_model->define_user($user_id);
                if(isset($user['link']['agency_id'])){

                    if(isset($requestjson['policy_number']) && strlen($requestjson['policy_number']) >= 5){

                        $policies = $this->insurance_model->get_application($requestjson['policy_number']);

                        if($policies){

                            $data = array();
                            $data['status'] = 1;
                            $data['policies'] = $policies;
                            $data['wallets'] = $this->comms_wallet_model->get_balances($user['username']);
                            
                             $message = [
                                'meta' => array('code' => 201),
                                'success' => true, 
                            'data' => $data,
                            'message' => "Ok"
                            ];
                        }else{

                            $message = [
                            'success' => true, 
                            'data' => array("status" => 0, "policies" => array()),
                            'message' => "There are no policies that match your search."
                            ];
                        }
                    }else{
                        $message = [
                        'success' => true, 
                        'error' => true,  
                        'data' => array("status" => "N"),
                        'message' => "Please supply a policy_number."
                        ];
                    }
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array("status" => "N"),
                    'message' => "This user does not have permission to sell insurance."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array("status" => "N"),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => false, 
                'data' => array("status" => "N"),
                'message' => "Please post a valid token."
            ];
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
    }

     public function customer_search_post()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','customer_search');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                $user = $this->insurance_model->define_user($user_id);
                if(isset($user['link']['agency_id'])){

                    if(isset($requestjson['customer_idcell']) && strlen($requestjson['customer_idcell']) >= 10){

                        $customer = $this->insurance_model->customer_search($requestjson['customer_idcell']);

                        if($customer){

                            $data = array();
                            $data['status'] = 1;
                            $data['customer'] = $customer;
                            $data['wallets'] = $this->comms_wallet_model->get_balances($user['username']);
                            
                             $message = [
                                'meta' => array('code' => 201),
                                'success' => true, 
                            'data' => $data,
                            'message' => "Ok"
                            ];
                        }else{

                            $sa_id = '';
                            $tel_cell = '';
                            if(strlen($requestjson['customer_idcell']) == 10){
                                $tel_cell = $requestjson['customer_idcell'];
                            }
                            if(strlen($requestjson['customer_idcell']) == 13){
                                $sa_id = $requestjson['customer_idcell'];
                            }

                            $message = [
                            'success' => true, 
                            'data' => array("status" => 0, "customer" => array(array(
                                                                    "first_name" => "",
                                                                    "last_name" => "",
                                                                    "sa_id" => $sa_id,
                                                                    "dob" => "",
                                                                    "passport_number" => "",
                                                                    "tel_cell" => $tel_cell,
                                                                    "email_address" => "",
                                                                    "postal_code" => "",
                                                                    "beneficiary_name" => " ",
                                                                    "beneficiary_sa_id" => "",
                                                                    "language" => "",
                                                                    "signature" => "",
                                                                    "picture" => ""))
                                        ),
                            'message' => "There is no customer that matches your search."
                            ];
                        }
                    }else{
                        $message = [
                        'success' => true, 
                        'error' => true,  
                        'data' => array("status" => "N"),
                        'message' => "Please supply customer_idcell."
                        ];
                    }
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array("status" => "N"),
                    'message' => "This user does not have permission to sell insurance."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array("status" => "N"),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => false, 
                'data' => array("status" => "N"),
                'message' => "Please post a valid token."
            ];
        }        

        if(isset($requestjson['customer_idcell']) && $requestjson['customer_idcell'] == '0827378888'){
            $message = [
                'success' => true, 
                'error' => true, 
                'data' => array("status" => "N"),
                'message' => "You have exceeded the maxumum amount of policies."
            ];
        }

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
        
    }

     public function get_product_post($product_id)
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','get_product_post');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                $user = $this->insurance_model->define_user($user_id);
                if(isset($user['link']['agency_id'])){

                    $data = array();
                    $data['user'] = $user;
                    $data['product'] = $this->insurance_model->get_detailed_product($product_id);
                    $data['wallets'] = $this->comms_wallet_model->get_balances($user['username']);
                    
                     $message = [
                        'meta' => array('code' => 201),
                        'success' => true, 
                    'data' => $data,
                    'message' => "Ok"
                    ];
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array(),
                    'message' => "This user does not have permission to sell insurance."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array(),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => false, 
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
        
    }

     public function get_product_premium_post($product_id)
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','get_product_premium');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                $user = $this->insurance_model->define_user($user_id);
                if(isset($user['link']['agency_id'])){

                    $data = array();

                    $product = $this->insurance_model->get_product_premium($product_id, $requestjson);

                    if($product['error']){
                        
                         $message = [
                        'success' => true, 
                        'error' => true, 
                        'data' => array(),
                        'message' => $product['message']
                        ];

                    }else{
                    
                        $data['premium'] = $product['premium'];
                        
                         $message = [
                            'meta' => array('code' => 201),
                            'success' => true, 
                            'error' => false, 
                            'data' => $data,
                            'message' => "Ok"
                        ];
                    }
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array(),
                    'message' => "This user does not have permission to sell insurance."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array(),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => true, 
                'error' => true, 
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
        
    }  

     public function get_policy_number_post($product_id)
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','get_policy_number');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token']) && isset($requestjson['identifier'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                $user = $this->insurance_model->define_user($user_id);
                $has_permission = $this->insurance_model->agency_product_credit($user['link']['agency_id'], $product_id);
                if($has_permission){
                    $credit = $has_permission['credit'];
                    $can_afford = true;
                }else{
                    $credit = false;
                }

                if(!$credit){
                    //check here if they have enough money
                    $can_afford = true;
                }

                if(isset($user['link']['agency_id']) && $has_permission && $can_afford){

                    $data = array();
                    $policy_number = $this->insurance_model->get_policy_number($product_id, $requestjson['identifier'], $user_id);

                    if($policy_number){

                        $data['application_data'] = 'none';

                        if(is_array($policy_number)){

                            $data['application_data'] = $policy_number;
                            $policy_number = $data['application_data']['policy_number'];
                        }
                        
                        $data['policy_number'] = $policy_number;
                        $data['wallets'] = $this->comms_wallet_model->get_balances($user['username']);
                         $message = [
                            'meta' => array('code' => 201),
                            'success' => true, 
                            'data' => $data,
                            'message' => "Ok"
                        ];

                    }else{

                         $message = [
                        'success' => true, 
                        'error' => true, 
                        'data' => array(),
                        'message' => 'An error occurred please try again.'
                        ];
                    }
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array(),
                    'message' => "This user does not have permission to sell insurance."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array(),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => false, 
                'data' => array(),
                'message' => "Please post a valid token and user identifier"
            ];
            
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
        
    }

     public function policy_wording_validation_post()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','policy_wording_validation_post');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                $user = $this->insurance_model->define_user($user_id);
                if(isset($user['link']['agency_id'])){

                    if(isset($requestjson['policy_wording_id']) && strlen($requestjson['policy_wording_id']) >= 4){

                        $policy_number = false;
                        
                        if(isset($requestjson['policy_number'])){
                            $policy_number = $requestjson['policy_number'];
                        }

                        $exists = $this->insurance_model->policy_wording_validation($requestjson['policy_wording_id'], $policy_number);

                        if($exists){
                           $data = array();
                             $message = [
                                'meta' => array('code' => 201),
                                'success' => true, 
                                'error' => true, 
                            'data' => $data,
                            'message' => "Policy wording id already used."
                            ];
                        }else{
                           $data = array();
                             $message = [
                                'meta' => array('code' => 201),
                                'success' => true, 
                            'data' => $data,
                            'message' => "Policy wording is unique."
                            ];
                        }
                    }else{
                        $message = [
                        'success' => true, 
                        'error' => true, 
                        'data' => array("status" => "N"),
                        'message' => "Please supply policy_wording_id over 5 digits."
                        ];
                    }
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array("status" => "N"),
                    'message' => "This user does not have permission to sell insurance."
                    ];
                }
            }else{

                $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array("status" => "N"),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => false, 
                'data' => array("status" => "N"),
                'message' => "Please post a valid token."
            ];
        }        

        if(isset($requestjson['customer_idcell']) && $requestjson['customer_idcell'] == '0827378888'){
            $message = [
                'success' => true, 
                'error' => true, 
                'data' => array("status" => "N"),
                'message' => "You have exceeded the maxumum amount of policies."
            ];
        }

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
        
    }

    public function get_payment_options_post($product_id)
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','get_payment_options');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                $time = date("H:i:s");
                $user = $this->insurance_model->define_user($user_id);
                $has_permission = $this->insurance_model->agency_product_credit($user['link']['agency_id'], $product_id);
                if($has_permission){
                    $credit = $has_permission['credit'];
                    $can_afford = true;
                }else{
                    $credit = false;
                }

                if(!$credit){
                    $payment_options = array('wallet');
                }else{
                    if(strtotime($time) <= strtotime("18:00:00") && strtotime($time) >= strtotime("07:00:00")){
                        $payment_options = array('credit','wallet');
                    }else{
                        $payment_options = array('wallet');
                    }
                        $payment_options = array('credit','wallet');
                }

                if(isset($user['link']['agency_id']) && $has_permission && isset($payment_options)){

                    $data = array();
                  
                    $data['payment_options'] = $payment_options;
                    $data['wallets'] = $this->comms_wallet_model->get_balances($user['username']);
                     $message = [
                        'meta' => array('code' => 201),
                        'success' => true, 
                        'data' => $data,
                        'message' => "Ok"
                    ];

                }else{
                    $message = [
                    'success' => true, 
                    'error' => true,  
                    'data' => array(),
                    'message' => "This user does not have permission to sell insurance."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array(),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => false, 
                'data' => array(),
                'message' => "Please post a valid token and user identifier"
            ];
            
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
        
    }  
     
     public function update_application_post($policy_number='')
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','update_application');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token']) && isset($policy_number)){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){

                $user = $this->insurance_model->define_user($user_id);
                if(isset($requestjson['product_data']['dob'])){
                    $requestjson['product_data']['dob'] = $requestjson['dob'];
                }
                $requestjson['premium'] = $this->insurance_model->get_product_premium($requestjson['product_id'], $requestjson['product_data'])['premium'];
                $has_permission = $this->insurance_model->agency_product_credit($user['link']['agency_id'], $requestjson['product_id']);
                if($has_permission){
                    $credit = $has_permission['credit'];
                    $can_afford = true;
                }else{
                    $credit = false;
                }

                if(!$credit){
                    //check here if they have enough money
                    $can_afford = true;
                }

                if(isset($user['link']['agency_id']) && $has_permission && $can_afford){

                    $data = array();
                    $requestjson['sold_by'] = $user_id;
                    $requestjson['ins_prod_id'] = $requestjson['product_id'];
                    $success = $this->insurance_model->update_policy_application($policy_number, $requestjson);

                    if($success){
                        
                         $data['wallets'] = $this->comms_wallet_model->get_balances($user['username']);  
                         $message = [
                            'meta' => array('code' => 201),
                            'success' => true, 
                            'data' => $data,
                            'message' => "Ok"
                        ];

                    }else{

                         $message = [
                        'success' => true, 
                        'error' => true, 
                        'data' => array(),
                        'message' => 'An error occurred please try again.'
                        ];
                    }
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array(),
                    'message' => "This user does not have permission to sell insurance."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array(),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => true, 
                'error' => true, 
                'data' => array(),
                'message' => "Please post a valid token and user policy_number"
            ];
            
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }       
    }

     public function complete_application_post($policy_number='')
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','complete_application');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token']) && isset($policy_number)){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){

                $user = $this->insurance_model->define_user($user_id);

                $requestjson['product_data']['dob'] = $requestjson['dob'];
                $requestjson['premium'] = $this->insurance_model->get_product_premium($requestjson['product_id'], $requestjson['product_data'], 'force_amount')['premium'];

                $has_permission = $this->insurance_model->agency_product_credit($user['link']['agency_id'], $requestjson['product_id']);

                if($has_permission){
                    $credit = $has_permission['credit'];
                }else{
                    $credit = false;
                }

                $can_afford = false;

                $balance = $this->financial_model->get_wallet_balance($user['username']);
                if($requestjson['premium'] <= $balance){
                    $can_afford = true;
                }


                if(isset($user['link']['agency_id']) && $has_permission){

                    $payment_approved = false;
                    $payment_error_message = 'Please supply a payment method.';

                    if(isset($requestjson['payment_method'])){

                        $requestjson['payment_method'] = strtolower($requestjson['payment_method']);

                        switch ($requestjson['payment_method']) {
                            case 'credit':
                                if(!$credit){
                                    $payment_error_message = 'Sorry credit is not available on this sale at this time.';
                                }else{
                                    $requestjson['sale_complete'] = 1;
                                    $payment_approved = true;
                                }
                                break;

                            case 'wallet':
                                if(!$can_afford){
                                    $payment_error_message = 'Sorry you do not have sufficient funds in your green wallet.';
                                }else{
                                    $requestjson['sale_complete'] = 1;
                                    $payment_approved = true;
                                }
                                break;

                            case 'atm':
                            // waiting for payment
                                $requestjson['sale_complete'] = 22;
                                    $payment_approved = true;
                                break;
                        }
                    }elseif (isset($requestjson['is_quote']) && $requestjson['is_quote']) {
                        $payment_approved = true;
                        $requestjson['sale_complete'] = 99;
                    }

                    if ($payment_approved) {
                    
                        $data = array();
                        $requestjson['sold_by'] = $user_id;
                        $success = $this->insurance_model->update_policy_application($policy_number, $requestjson);
                        
                        if(!$this->insurance_model->policy_wording_validation($requestjson['policy_wording_id'], $policy_number)){
                            $this->insurance_model->add_policy_wording_id($requestjson['policy_wording_id']);
                        }

                        if($success){
                            
                            $data['policy_number'] = $policy_number;

                            if(isset($requestjson['payment_method']) && $requestjson['payment_method'] != 'atm'){
                                $paid = $this->insurance_model->allocate_funds_and_comms($policy_number, $user_id, $requestjson['premium']);
                            }else{
                                $paid = true;
                            }
                            
                            if($paid){

                                $this->insurance_model->send_application_comms($policy_number, $requestjson);

                                 $data['wallets'] = $this->comms_wallet_model->get_balances($user['username']);
                                 $data['status'] = 'Saved Correctly.';
                                 $message = [
                                    'meta' => array('code' => 201),
                                    'success' => true, 
                                    'data' => $data,
                                    'message' => "Saved Correctly."
                                ];
                            }else{
                                $this->insurance_model->update_policy_application($policy_number, array('sale_complete' => '99'));
                                $message = [
                                'meta' => array('code' => 201),
                                'success' => true, 
                                'data' => $data,
                                'message' => 'An error occurred with payment. But the order did place correctly.'
                                ];
                            }

                        }else{

                             $message = [
                            'success' => true, 
                            'error' => true, 
                            'data' => array(),
                            'message' => 'An error occurred please try again.'
                            ];
                        }
                    }else{

                         $message = [
                        'success' => true, 
                        'error' => true, 
                        'data' => array(),
                        'message' => $payment_error_message
                        ];
                    }
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array(),
                    'message' => "This user does not have permission to sell insurance."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array(),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => true, 
                'error' => true, 
                'data' => array(),
                'message' => "Please post a valid token and user policy_number"
            ];
            
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }       
    }

    function save_picture_post($policy_number){
       
        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
        $nopicture = $requestjson;
        $nopicture['picture'] = substr($requestjson['picture'], 0, 25);
        $this->app_model->save_raw_data(json_encode($nopicture),'insurapp','save_picture');

        $picture    =   '';
        $message    =   '';

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $type =  'picture';
        if(isset($requestjson['type']) && !empty($requestjson['type'])){
            $type =  $requestjson['type'];
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token']) && isset($policy_number)){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                $user = $this->insurance_model->define_user($user_id);

                if(isset($requestjson['picture']) && $requestjson['picture'] != ''){

                    $picture_id = "ins_".$type;
                    $picture    = 'pic_' . $picture_id. '_' . $policy_number.'.jpg';

                    $this->load->model('spazapp_model');
                    $this->spazapp_model->base64_to_jpeg($requestjson['picture'], 'assets/uploads/insurance/'.$type.'s/'.$picture);

                    unset($requestjson['token']);// Preventing post to pass into insert or update function

                    switch ($type) {
                        case 'picture':
                            $this->insurance_model->add_picture($policy_number,$picture);
                            break;
                        case 'signature':
                            $this->insurance_model->add_signature($policy_number,$picture);
                            break;
                        case 'death_certificate':
                            $this->insurance_model->add_death_certificate($policy_number,$picture);
                            break;
                        default:
                            $this->insurance_model->add_picture($policy_number,$picture);
                            break;
                    }

                     $message = [
                    'success' => true, // Automatically generated by the model
                    'data' => array(),
                    'message' => "Picture has been saved"
                    ];

                }

            }else{
                    $message = [
                        'success' => false, // Automatically generated by the model
                        'data' => array(),
                        'message' => "Your Token has expired."
                    ];
            }

        }else{
                $message = [
                    'success' => false, // Automatically generated by the model
                    'data' => array(),
                    'message' => "Please post a valid token."
                ];
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code  
    }    

    public function allocate_funds_post($policy_number, $product_id) {

        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','allocate_funds.');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token']) && isset($policy_number)){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                $this->insurance_model->allocate_funds_and_comms($policy_number, $user_id);
            }
        }
    }

/* DEPENDANTS */

     public function get_dependants_post($policy_number=false)
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','get_dependants');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){

                if($policy_number){

                    $data = array();
                    $data = $this->insurance_model->get_dependants($policy_number);
                   
                     $message = [
                        'meta' => array('code' => 201),
                        'success' => true, 
                    'data' => $data,
                    'message' => "Ok"
                    ];
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array(),
                    'message' => "Please supply a valid policy number."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array(),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => false, 
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
        
    }


     public function add_dependant_post($policy_number=false)
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','add_dependant');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                
                if($policy_number){

                    $data = array();

                    $result = $this->insurance_model->add_dependant($policy_number, $requestjson);

                    if(isset($result['error'])){
                        
                         $message = [
                        'success' => true, 
                        'error' => true, 
                        'data' => array(),
                        'message' => $result['error']
                        ];

                    }else{
                    
                        $data['dependants'] = $this->insurance_model->get_dependants($policy_number);
                        
                         $message = [
                            'meta' => array('code' => 201),
                            'success' => true, 
                            'data' => $data,
                            'message' => "Ok"
                        ];
                    }
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array(),
                    'message' => "Please supply a policy number."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array(),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => false, 
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
        
    }  


     public function remove_dependant_post($policy_number=false)
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','remove_dependant');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                
                if($policy_number && isset($requestjson['dependant_id'])){

                    $data = array();

                    $result = $this->insurance_model->remove_dependant($policy_number, $requestjson['dependant_id']);

                    if(isset($result['error'])){
                        
                         $message = [
                        'success' => true, 
                        'error' => true, 
                        'data' => array(),
                        'message' => $result['error']
                        ];

                    }else{
                    
                        $data['dependants'] = $this->insurance_model->get_dependants($policy_number);
                        
                         $message = [
                            'meta' => array('code' => 201),
                            'success' => true, 
                            'data' => $data,
                            'message' => "Ok"
                        ];
                    }
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array(),
                    'message' => "Please supply a policy number and dependant id."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array(),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => false, 
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
        
    }  

     public function resend_policy_sms_post()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'insurapp','policy_search');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                $user = $this->insurance_model->define_user($user_id);
                if(isset($user['link']['agency_id'])){

                    if(isset($requestjson['policy_number']) && strlen($requestjson['policy_number']) >= 5){

                        $policies = $this->insurance_model->get_application($requestjson['policy_number']);

                        if($policies){

                            $data = array();
                            $data['status'] = 1;

                            $message = $policies['insurer'].": ".$policies['product_name'].". Your policy number is ".$policies['policy_number'].". Cover expires on ".$policies['expiry_date'].". See policy terms here " . $policies['short_url'];

                            $this->comms_model->send_sms($policies['tel_cell'], $message);
                            
                             $message = [
                                'meta' => array('code' => 201),
                                'success' => true, 
                                'message' => "Policy sms has been sent to: " . $policies['tel_cell']
                            ];
                        }else{

                            $message = [
                            'success' => true, 
                            'data' => array("status" => 0, "policies" => array()),
                            'message' => "There are no policies that match your search."
                            ];
                        }
                    }else{
                        $message = [
                        'success' => true, 
                        'error' => true,  
                        'data' => array("status" => "N"),
                        'message' => "Please supply a policy_number."
                        ];
                    }
                    
                }else{
                    $message = [
                    'success' => true, 
                    'error' => true, 
                    'data' => array("status" => "N"),
                    'message' => "This user does not have permission to sell insurance."
                    ];
                }
            }else{

                $message = [
                    'success' => false, 
                    'data' => array("status" => "N"),
                    'message' => "Token not valid"
                ];
                
            } 
        }else{

            $message = [
                'success' => false, 
                'data' => array("status" => "N"),
                'message' => "Please post a valid token."
            ];
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller::HTTP_NOT_ACCEPTABLE); 
        }
    }


}