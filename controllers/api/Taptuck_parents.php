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
class Taptuck_parents extends REST_Controller_Taptuck {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->library("Aauth");
        $this->load->model('event_model');
        $this->load->model('app_model');
        $this->load->model('financial_model');
        $this->load->model('user_model');
        $this->load->model('spazapp_model');
        $this->load->model('product_model');

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['user_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['user_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
    }
    
    public function credit_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','credit_post');
        $requestjson = json_decode($requestjson, true);

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            $parent = $this->user_model->get_parent_id($user_id);
            $parent_id = $parent->id;

            if (!empty($requestjson['amount'])){

                if($user_id)
                {
                    $data['parent_id'] = $parent_id;
                    $data['amount'] = $requestjson['amount'];
                    $data['uuid'] = $requestjson['token'];
                    $data['transaction_type'] = "credit";
                    $data['created_at'] = date("Y-m-d H:i:s");
                    $data['updated_at'] = date("Y-m-d H:i:s");

                    $result = $this->financial_model->credit_taptuck($data);

                    if($result == 'success')
                    {
                        $message = [
                        'success' => true, // Order placed successfully
                        'data' => array(),
                        'message' => "Your funds have been updated."
                        ];
                    }
                    else
                    {
                        $message = [
                        'success' => false, // order failed.
                        'data' => array(),
                        'message' => "Unable to complete this transaction."
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
                'message' => "There is no amount for this transaction."
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

        $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function transactions_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','transactions_post');
        $requestjson = json_decode($requestjson, true);

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            $parent = $this->user_model->get_parent_id($user_id);
            $parent_id = $parent->id;
            //$parent_id = '38';  // for testing

            if ($parent_id != '' && !empty($parent_id))
            {

                if($user_id)
                {
                    $return = $this->user_model->get_parents_transactions($parent_id);
                    $data['info'] = $return;
                    if($data)
                    {
                        $message = [
                            'success' => true, 
                            'data' => $data
                        ];
                    }
                    else
                    {
                        $message = [
                            'success' => false, 
                            'data' => '',
                            'message' => "Unable to process."
                        ];
                    }
                }
                else
                {
                    $message = [
                        'success' => false, 
                        'data' => '',
                        'message' => "Wrong account type."
                    ]; 
                }
            }
            else
            {
                $message = [
                    'success' => false, 
                    'data' => '',
                    'message' => "Please post a valid token."
                ];                
            }
        }
        else
        {

            $message = [
                'success' => false, 
                'data' => '',
                'message' => "Please post a valid token."
            ];
            
        }        

        $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }
    
    function parents_get(){
        
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','get_parents');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            
            if($user_id){
                $parent_user_id='';
                
                 if(!isset($requestjson['firstname'])){
                    
                    $message = [
                    'success' => true, 
                    'data' => '',
                    'message' => "firstname required"
                    ];   
                    
                }
                
                if(!isset($requestjson['lastname'])){
                    
                    $message = [
                    'success' => true, 
                    'data' => '',
                    'message' => "lastname required"
                    ];   
                    
                }
                
                if(isset($requestjson['firstname']) && isset($requestjson['lastname'])){
                    
                    $parent_info = $this->user_model->parent_by_name($requestjson['firstname'], $requestjson['lastname'], $user_id);

                    $token = $this->user_model->generate_token($user_id);

                    if(isset($requestjson['imei'])){
                        $this->user_model->save_imei($user_id, $requestjson['imei']);
                    }

                    $message = [
                        'meta' => array( 
                                         'token' => $token,
                                         'code' => '200'
                                         ),
                        'data' => $parent_info
                    ];
                    
                }
                
            }else{
                $message = [
                    'success' => false, 
                    'data' => '',
                    'message' => "Please post a valid token."
                ];                
            }
            
        }else{

            $message = [
                'success' => false, 
                'data' => '',
                'message' => "Please post a valid token."
            ];
            
        }  
        $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

function parents_post(){
        
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson);
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            
            if($user_id){
                $parent_user_id='';
                
                 if(!isset($requestjson['firstname'])){
                    
                    $message = [
                    'success' => true, 
                    'data' => '',
                    'message' => "firstname required"
                    ];   
                    
                }
                
                if(!isset($requestjson['lastname'])){
                    
                    $message = [
                    'success' => true, 
                    'data' => '',
                    'message' => "lastname required"
                    ];   
                    
                }
                
                if(isset($requestjson['firstname']) && isset($requestjson['lastname'])){
                    
                    $parent_info = $this->user_model->parent_by_name($requestjson['firstname'], $requestjson['lastname'], $user_id);

                    $token = $this->user_model->generate_token($user_id);

                    if(isset($requestjson['imei'])){
                        $this->user_model->save_imei($user_id, $requestjson['imei']);
                    }

                    $message = [
                        'meta' => array( 
                                         'token' => $token,
                                         'code' => '200'
                                         ),
                        'data' => $parent_info
                    ];
                    
                }
                
            }else{
                $message = [
                    'success' => false, 
                    'data' => '',
                    'message' => "Please post a valid token."
                ];                
            }
            
        }else{

            $message = [
                'success' => false, 
                'data' => '',
                'message' => "Please post a valid token."
            ];
            
        }  
        $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }
}
