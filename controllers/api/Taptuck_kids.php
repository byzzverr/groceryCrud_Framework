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
class Taptuck_kids extends REST_Controller_Taptuck {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->library("Aauth");
        $this->load->model('event_model');
        $this->load->model('app_model');
        $this->load->model('financial_model');
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
    
     public function calendar_get()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','add_kids');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            $kid_id = false;
            if(isset($_GET['kid_id'])){
                $kid_id = $_GET['kid_id'];
            }

            if($user_id && $kid_id){

                $user = $this->aauth->get_user($user_id);
                $calendar = $this->tt_order_model->get_calendar($kid_id);
                
                 $message = [
                    'meta' => array('code' => 201),
                    'success' => true, 
                'data' => $calendar,
                'message' => "Ok"
                ];
                
            }else{
                $message = [
                'success' => false, 
                'data' => array(),
                'reason' => "Token not valid."
                ];
            }
        }else{

            $message = [
                'success' => false, 
                'data' => array(),
                'reason' => "Please post a valid token."
            ];
            
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_NOT_ACCEPTABLE); 
        }
        
    }    
     public function kids_get()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','add_kids');
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
                $user = $this->aauth->get_user($user_id);
                $parent = $this->tt_parent_model->get_tt_parent($user_id, $user->username);
                
              
                $data['kids'] = $parent['kids'];
                
                 $message = [
                    'meta' => array('code' => 201),
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
        }else{

            $message = [
                'success' => false, 
                'data' => array(),
                'reason' => "Please post a valid token."
            ];
            
        }        

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_NOT_ACCEPTABLE); 
        }
        
    }

    public function add_kid_post()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','add_kids');
        $requestjson = json_decode($requestjson, true);
        $error = false;

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $this->load->model('user_model');
        $this->load->model('customer_model');
        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        if($user_id){
            $parent = $this->tt_parent_model->get_tt_parent_from_user_id($user_id);
            $requestjson['parent_id'] = $parent['id'];
            $requestjson['created_at'] = date("Y-m-d H:i:s");
            $requestjson['updated_at'] = $requestjson['created_at'];
            
            $expected_array = array(
                'first_name',
                'last_name',
                'image_name',
                'birthday',
                'grade',
                'merchant_id',
                'diet_specific',
                'allergies',
                'food_preferences',
                'pocket_money',
                'daily_limit',
                'weekly_limit'
                );

            foreach ($expected_array as $field) {
                if(!isset($requestjson[$field])){
                    $error = true;
                    $message = "Could not find '$field' in post array";
                }
            }

            if(isset($requestjson['device_identifier']) && $requestjson['device_identifier'] != ''){

                $requestjson['device_identifier'] = trim(preg_replace("/[^0-9]/","",$requestjson['device_identifier']));
            }
            
            if(!$error){
                unset($requestjson['token']);
                $kid_id = $this->tt_kid_model->add_kid($requestjson);
                $kid = $this->tt_kid_model->get_tt_kid($kid_id);
                
                 $message = [
                    'meta' => array('code' => 201),
                    'success' => true, 
                'data' => $kid,
                'message' => "Ok"
                ];
                
            }else{

                $message = [
                'success' => false, 
                'data' => array(),
                'reason' => $message
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

    public function edit_kid_post()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','add_kids');
        $requestjson = json_decode($requestjson, true);
        $error = false;

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $this->load->model('user_model');
        $this->load->model('customer_model');
        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        if($user_id){
            $parent = $this->tt_parent_model->get_tt_parent_from_user_id($user_id);
            $requestjson['parent_id'] = $parent['id'];
            
            $expected_array = array(
                'first_name',
                'last_name',
                'image_name',
                'birthday',
                'grade',
                'merchant_id',
                'diet_specific',
                'allergies',
                'food_preferences',
                'pocket_money',
                'daily_limit',
                'weekly_limit'
                );

            foreach ($expected_array as $field) {
                if(!isset($requestjson[$field])){
                    $error = true;
                    $message = "Could not find '$field' in post array";
                }
            }
            
            if(!$error){
                unset($requestjson['token']);
                $kid_id = $requestjson['kid_id'];
                unset($requestjson['kid_id']);
                $kid_id = $this->tt_kid_model->edit_kid($kid_id, $requestjson);
                if($kid_id){
                    $kid = $this->tt_kid_model->get_tt_kid($kid_id);
                    
                     $message = [
                        'meta' => array('code' => 201),
                        'success' => true, 
                    'data' => $kid,
                    'message' => "Ok"
                    ];
                }else{
                     $message = [
                        'meta' => array('code' => 401),
                        'success' => false, 
                    'data' => array(),
                    'message' => "No kid matches that kid_id or you are not the parent."
                    ];
                }
                
            }else{

                $message = [
                'success' => false, 
                'data' => array(),
                'reason' => $message
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
    
    function remove_kid_delete(){
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','add_kids');
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
                $user = $this->aauth->get_user($user_id);
                $parent = $this->tt_parent_model->get_tt_parent($user_id,$user->username);
                $kid = $this->tt_kid_model->get_tt_kid($requestjson['kid_id']);
             
                
                if($kid['parent_id'] == $parent['id']){

                    $this->tt_kid_model->remove_kid($requestjson['kid_id']);
                    $parent = $this->tt_parent_model->get_tt_parent($user_id,$user->username);

                    $message = [
                    'success' => true, 
                    'data' => $parent,
                    'message' => 'Kid successfully removed'
                    ];

                }else{

                    $parent = $this->tt_parent_model->get_tt_parent($user_id,$user->username);
                    $message = [
                    'success' => false, 
                    'data' => $parent,
                    'message' => 'Parent can only remove their own kid.'
                    ];
                }
                
              }else{
                $message = [
                'success' => false, 
                'data' => array(),
                'message' => "Token not valid."
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
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_NOT_ACCEPTABLE); 
        }
    }   
    
    
    function add_device_post(){
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','add_kids');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){

            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                if(isset($requestjson['kid_id']) && isset($requestjson['device_identifier'])){
                    $parent = $this->tt_parent_model->get_tt_parent_from_user_id($user_id);
                    $data['parent'] = $parent;

                    $data['kid'] = $this->tt_kid_model->get_tt_kid($requestjson['kid_id']);

                    if(isset($requestjson['device_identifier']) && $requestjson['device_identifier'] != ''){

                        $requestjson['device_identifier'] = trim(preg_replace("/[^0-9]/","",$requestjson['device_identifier']));
                    }

                    $res = $this->tt_kid_model->add_kid_device($requestjson['kid_id'],$requestjson['device_identifier'], $data, $user_id);
                    

                    if($res){

                        $message = [
                            'success' => true, 
                            'data' => $data,
                            'message' => 'Identifier has been updated succesfully.'
                        ];

                    }else{

                        $message = [
                            'success' => false, 
                            'data' => $data,
                            'message' => 'Identifier already in use.'
                        ];
                    }
                    
                }else{
                     $message = [
                        'success' => false, 
                        'data' => array(),
                        'message' => "Kid_id and device_identifier are required"
                    ];
                }
                
            }else{
                $message = [
                    'success' => false, 
                    'data' => array(),
                    'message' => "Token not valid."
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
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_NOT_ACCEPTABLE); 
        }

    }


     public function scan_get()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $device_identifier = false;
        if(isset($_GET['device_identifier'])){
            $this->app_model->save_raw_data($_GET['device_identifier'],'api','merchant_scan_keychain');
            $device_identifier = $_GET['device_identifier'];
            $device_identifier = trim(preg_replace("/[^0-9]/","",$device_identifier));
        }

        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        if($device_identifier && $user_id){

            $user = $this->aauth->get_user($user_id);
            $kid = $this->tt_kid_model->get_tt_kid_from_device($device_identifier);
            
             $message = [
             'meta' => array( 'code' => 201),
            'success' => true, 
            'data' => $kid,
            'message' => "Ok"
            ];
            
        }else{
            $message = [
            'meta' => array( 'code' => 401, 'reason' => 'Nothing found that matches your user and identifier.'),
            'success' => false, 
            'data' => array()
            ];
        }

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_NOT_ACCEPTABLE); 
        }
        
    }

     public function diet_specific_get()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','add_kids');
        $requestjson = json_decode($requestjson, true);

        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }

        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        if($user_id){

            $user = $this->aauth->get_user($user_id);
            $diet_specific = $this->tt_kid_model->get_diet_specific($user->id);
            
             $message = [
             'meta' => array( 'code' => 201),
            'success' => true, 
            'data' => $diet_specific,
            'message' => "Ok"
            ];
            
        }else{
            $message = [
            'meta' => array( 'code' => 401, 'reason' => 'Please supply a valid token.'),
            'success' => false, 
            'data' => array()
            ];
        }

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED); 
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_NOT_ACCEPTABLE); 
        }
        
    }

    
}