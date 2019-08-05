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
class Basic extends REST_Controller {

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
        $this->load->model('global_app_model');
        $this->load->model('trader_model');
        $this->load->model('rewards_model');
        $this->load->model('stokvel_model');

        $this->app_settings = get_app_settings(base_url());

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['user_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['user_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    public function test_test_get(){

        $this->load->model('insurance_model');
        $url = 'http://google.com/byron';
        $this->insurance_model->generate_short_url($url);
    }

    public function skip_login_post()
    {

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
        $not_verified = false;
        $this->app_model->save_raw_data(json_encode($requestjson),'api','login');

        if(isset($requestjson['region_id'])){

                $this->event_model->track('skip_login','app_login_skipped', $requestjson['region_id']);

                $user_info = $this->aauth->get_user(5); // remember this is an object not array.
                            
                if(!isset($requestjson['app'])){
                    $app = $user_info->app;
                }

                $username = $user_info->username;

                unset($user_info->pass);
                unset($user_info->parent_id);
                unset($user_info->banned);
                unset($user_info->last_login);
                unset($user_info->last_activity);
                unset($user_info->last_login_attempt);
                unset($user_info->forgot_exp);
                unset($user_info->remember_time);
                unset($user_info->remember_exp);
                unset($user_info->verification_code);
                unset($user_info->ip_address);
                unset($user_info->login_attempts);
                unset($user_info->cellphone);
                unset($user_info->username);
                unset($user_info->customer_id);

                $token = $this->user_model->generate_token($user_info->id);

                if(isset($requestjson['imei'])){
                    $this->user_model->save_imei($user_info->id, $requestjson['imei']);
                }

                $user_link = '';

                switch ($user_info->default_usergroup) {
                    case 8:  // Customer
                        $this->load->model('customer_model');
                        $user_link = $this->customer_model->get_customer($user_info->user_link_id);
                        break;
                    case 19: // Trader
                        $this->load->model('trader_model');
                        $user_link = $this->trader_model->get_trader($user_info->user_link_id);
                        break;

                    case 18: // Driver
                        $user_link = '';
                        break;
                    case 23: // Driver
                    case 24: // Driver
                    case 25: // Driver
                    case 26: // Driver
                    case 27: // Driver
                    case 28: // Driver
                    case 29: // Driver
                    case 30: // Driver
                        $user_link = '';
                        $this->load->model('comms_wallet_model');
                        $wallets = $this->comms_wallet_model->get_balances($username);
                        break;
                    
                    default:
                        # code...
                        break;
                }

                $app_design = $this->global_app_model->get_app_design($app);

                $rewards = $this->rewards_model->categorise_user($username);

                $data = array( 
                         'demo_user' => true,
                         'user' => $user_info,
                         'user_link' => $user_link,
                         'rewards' => $rewards,
                         'app_design' => $app_design,
                         'token' => $token
                         );
                if(isset($wallets)){
                    $data['wallets'] = $wallets;
                }

                $message = [
                    'success' => true, // Automatically generated by the model
                    'data' => $data,
                    'message' => 'Successfully logged in'
                ];
        }else{

            $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => 'Region not received.'
            ];
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function login_post()
    {

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
        $not_verified = false;
        $this->app_model->save_raw_data(json_encode($requestjson),'api','login');

        if(isset($requestjson['username']) && isset($requestjson['password'])){

            $this->aauth->logout();
        
            $app = $this->app_settings['app_name'];
        
            $result = $this->aauth->login(trim($requestjson['username']), trim($requestjson['password']), FALSE, $app);
            if(!$result && $this->aauth->get_errors_array()[0] == "Please verify your account."){

                $not_verified = true;
            }
            
               if ($this->aauth->is_loggedin()){

                // store the raw data for future reference
                    
                $user_info = $this->aauth->get_user(); // remember this is an object not array.
                
                if(!isset($requestjson['app'])){
                    $app = $user_info->app;
                }

                $username = $user_info->username;

                $this->event_model->track('login','app_login_successful', $user_info->username);

                unset($user_info->pass);
                unset($user_info->parent_id);
                unset($user_info->banned);
                unset($user_info->last_login);
                unset($user_info->last_activity);
                unset($user_info->last_login_attempt);
                unset($user_info->forgot_exp);
                unset($user_info->remember_time);
                unset($user_info->remember_exp);
                unset($user_info->verification_code);
                unset($user_info->ip_address);
                unset($user_info->login_attempts);
                unset($user_info->customer_id);

                $user_info->balance = $this->financial_model->get_wallet_balance($user_info->username);

                $token = $this->user_model->generate_token($user_info->id);

                if(isset($requestjson['imei'])){
                    $this->user_model->save_imei($user_info->id, $requestjson['imei']);
                }

                $user_link = '';

                switch ($user_info->default_usergroup) {
                    case 8:  // Customer
                        $user_link = $this->customer_model->get_customer($user_info->user_link_id);
                        break;
                    case 19: // Trader
                        $user_link = $this->trader_model->get_trader($user_info->user_link_id);
                        break;

                    case 18: // Driver
                        $user_link = '';
                        break;
                    case 23: // Driver
                    case 24: // Driver
                    case 25: // Driver
                    case 26: // Driver
                    case 27: // Driver
                    case 28: // Driver
                    case 29: // Driver
                    case 30: // Driver
                        $user_link = '';
                        $this->load->model('comms_wallet_model');
                        $wallets = $this->comms_wallet_model->get_balances($username);
                        break;
                    case 33: // ChairmanTreasurer
                    case 34: // Member
                        $user_link = $this->stokvel_model->get_customer($user_info->user_link_id);
                        break;
                   
                    default:
                        # code...
                        break;
                }

                $app_design = $this->global_app_model->get_app_design($app);

                $rewards = $this->rewards_model->categorise_user($username,'0',$app);

                $data = array( 
                         'demo_user' => false,
                         'user' => $user_info,
                         'user_link' => $user_link,
                         'rewards' => $rewards,
                         'app_design' => $app_design,
                         'token' => $token
                         );
                if(isset($wallets)){
                    $data['wallets'] = $wallets;
                }

                $message = [
                    'success' => true, // Automatically generated by the model
                    'data' => $data,
                    'message' => 'Successfully logged in'
                ];
            }else{

                $error = "Incorrect cell number and password combination.";

                if($not_verified){
                    $this->app_model->resend_otp($requestjson['username']);
                    $error = "Please validate your account using your OTP.";
                }

                $this->event_model->track('login','app_login_attempt', $requestjson['username']);
                $message = [
                    'success' => false, // Automatically generated by the model
                    'data' => array(),
                    'message' => $error
                ];
            }
        }else{

            $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => 'Data not received.'
            ];
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function login_get()
    {
        // $this->some_model->update_user( ... );
        $message = [
            'success' => true, // Automatically generated by the model
            'data' => array( 'username' => $this->post('username'),
                             'password' => $this->post('password'),
                             'imei' => $this->post('imei'),
                             'method' => 'get',
                             'token' => 'letsmakeiteasy'
                             ),
            'message' => 'Successfully logged in'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function register_post()
    {

        $this->load->helper('email');

        $error = false;
        $message = '';

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
        $this->app_model->save_raw_data(json_encode($requestjson),'api','basic_register');

        if($requestjson['shop_type'] == 10){
            $this->register_trader_post();
        }else{
            
        //file_put_contents('assets/uploads/customer/'.$requestjson['first_name'].'.jpg', base64_decode($requestjson['photo']));

        $expected_array = array(
            'first_name',
            'last_name',
            'cellphone',
            'password',
            'confirm_password',
            'shop_name',
            'region_id',
            'shop_type'
            );

        foreach ($expected_array as $field) {
            if(!isset($requestjson[$field])){
                $error = true;
                $message = "Could not find '$field' in post array";
            }
        }

        if (!$error){

            // store the raw data for future reference
            $this->app_model->save_raw_data(json_encode($requestjson),'api','register');
                
            $this->load->model('user_model');

            $requestjson['name'] = $requestjson['first_name'] . ' ' . $requestjson['last_name'];
          
            $otp=$this->app_model->generateRandomNumber();
            $requestjson['otp']=$otp;

            $new_user_id = $this->aauth->create_user($requestjson['cellphone'],$requestjson['password'],$requestjson['name'],$requestjson['cellphone'],8);
            $errors = $this->aauth->get_errors_array();

            if(is_array($errors) && count($errors) >= 1){
                $this->event_model->track('app','registration_attempt', $errors[0]);
                $this->event_model->track('user','attempted_add_user', $errors[0]);
                $message = [
                        'success' => false, // Automatically generated by the model
                        'data' => array(),
                        'message' => $errors[0]
                    ];
            }else{

                if(!isset($requestjson['email'])){
                    $requestjson['email'] = $requestjson['cellphone'] . '@spazapp.co.za';
                }
                
                $province = $this->user_model->get_province_by_region_id($requestjson['region_id']);
                
                $requestjson['province']        = $province['province_id']; 
                $requestjson['customer_type']   = $requestjson['shop_type'];
                
                $customer_id = $this->app_model->create_customer($requestjson);
                $this->app_model->update_user_customer($new_user_id,$customer_id);
                

                if(valid_email($requestjson['email'])){
                    $this->app_model->update_user_email($new_user_id,$requestjson['email']);
                }

                
                $this->app_model->send_welcome($requestjson['email'],$requestjson['name'],$requestjson['cellphone'],$requestjson['cellphone'],$requestjson['password']);

                $this->event_model->track('user','add_user', $new_user_id);
                $this->event_model->track('app','registration');

                $message = [
                    'success' => true, // Automatically generated by the model
                    'data' => array( 'new_user_id' => $new_user_id),
                    'message' => 'Successfully registered. Please login'
                ];
            }


        }else{
            $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => $message
            ];
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
        }

    }


  public function register_trader_post()
    {

        $this->load->helper('email');

        $error = false;
        $message = '';

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);

        //file_put_contents('assets/uploads/customer/'.$requestjson['first_name'].'.jpg', base64_decode($requestjson['photo']));

        $expected_array = array(
            'first_name',
            'last_name',
            'cellphone',
            'password',
            'confirm_password',
            'region_id'
            );

        foreach ($expected_array as $field) {
            if(!isset($requestjson[$field])){
                $error = true;
                $message = "Could not find '$field' in post array";
            }
        }


        if (!$error){


            // store the raw data for future reference
            $this->app_model->save_raw_data(json_encode($requestjson),'api','trader_register');
                
            $this->load->model('user_model');

            $requestjson['name'] = $requestjson['first_name'] . ' ' . $requestjson['last_name'];
            $requestjson['otp']=$this->app_model->generateRandomNumber();
         
            $new_user_id = $this->aauth->create_user($requestjson['cellphone'],$requestjson['password'],$requestjson['name'],$requestjson['cellphone'],19);
            $errors = $this->aauth->get_errors_array();

            if(!isset($requestjson['email'])){
                $requestjson['email'] = $requestjson['cellphone'] . '@spazapp.co.za';
            }

            if(is_array($errors) && count($errors) >= 1){
                $this->event_model->track('app','registration_attempt', $errors[0]);
                $this->event_model->track('user','attempted_add_user', $errors[0]);
                $message = [
                        'success' => false, // Automatically generated by the model
                        'data' => array(),
                        'message' => $errors[0]
                    ];
            }else{
                $province = $this->user_model->get_province_by_region_id($requestjson['region_id']);
                $requestjson['province'] = $province['province_id'];
                
                $customer_id = $this->app_model->create_trader($requestjson);
                $this->app_model->update_user_customer($new_user_id,$customer_id);
                if(valid_email($requestjson['email'])){
                    $this->app_model->update_user_email($new_user_id,$requestjson['email']);
                }

                $this->app_model->send_welcome($requestjson['email'],$requestjson['name'],$requestjson['cellphone'],$requestjson['cellphone'],$requestjson['password']);

                $this->event_model->track('user','add_user', $new_user_id);
                $this->event_model->track('app','registration');

                $message = [
                    'success' => true, // Automatically generated by the model
                    'data' => array( 'new_user_id' => $new_user_id),
                    'message' => 'Successfully registered. Please login'
                ];
            }


        }else{
            $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => $message
            ];
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }


  public function register_stokvel_post()
    {

        $this->load->helper('email');

        $error = false;
        $message = '';

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);

        $expected_array = array(
            'name',
            'id_number',
            'cellphone',
            'email',
            'password',
            'confirm_password',
            'stokvel_name',
            'customer_type',
            'province_id',
            'region_id',
            'distributor_id'
            );

        foreach ($expected_array as $field) {
            if(!isset($requestjson[$field])){
                $error = true;
                $message = "Could not find '$field' in post array";
            }
        }

        if (!$error){

            // store the raw data for future reference
            $this->app_model->save_raw_data(json_encode($requestjson),'api','stokvel_register');
                
            $this->load->model('user_model');
            $this->load->model('stokvel_model');
            $requestjson['otp'] = $this->app_model->generateRandomNumber();
            $requestjson['cellphone'] = '0' . substr($requestjson['cellphone'],-9);
         
            /* CREATE USER */

            $new_user_id = $this->aauth->create_user($requestjson['cellphone'],$requestjson['password'],$requestjson['name'],$requestjson['cellphone'],33);

            $errors = $this->aauth->get_errors_array();

            if(!isset($requestjson['email'])){
                $requestjson['email'] = $requestjson['cellphone'] . '@umsstokvel.co.za';
            }

            if(is_array($errors) && count($errors) >= 1){
                $this->event_model->track('app','registration_attempt', $errors[0]);
                $this->event_model->track('user','attempted_add_user', $errors[0]);
                $message = [
                        'success' => false, // Automatically generated by the model
                        'data' => array(),
                        'message' => $errors[0]
                    ];
            }else{

                $requestjson['province'] = $requestjson['province_id'];
                $requestjson['user_id'] = $new_user_id;
                
                $stokvel_id = $this->stokvel_model->create_stokvel($requestjson);
                $customer_id = $this->stokvel_model->create_customer($requestjson, $stokvel_id);

                $this->app_model->update_user_customer($new_user_id,$customer_id);

                if(valid_email($requestjson['email'])){
                    $this->app_model->update_user_email($new_user_id,$requestjson['email']);
                }

                $this->app_model->send_welcome($requestjson['email'],$requestjson['name'],$requestjson['cellphone'],$requestjson['cellphone'],$requestjson['password']);

                $this->event_model->track('user','add_stokvel_user', $new_user_id);
                $this->event_model->track('app','stokvel_registration');

                $message = [
                    'success' => true, // Automatically generated by the model
                    'data' => array( 'new_user_id' => $new_user_id),
                    'message' => 'Successfully registered. Please login.'
                ];
            }


        }else{
            $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => $message
            ];
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }


    public function register_get()
    {
        // $this->some_model->update_user( ... );
        $message = [
            'success' => true, // Automatically generated by the model
            'data' => array( 'new_user_id' => 0),
            'message' => 'Do the monkey with me!'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

     public function get_user_profile_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','get_user_profile');
        $requestjson = json_decode($requestjson, true);

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $this->load->model('customer_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){

                $user = $this->user_model->get_general_user($user_id);
                $customer_id = $user->customer_id;
                $customer = $this->customer_model->get_customer_info($customer_id);
                $result = array_merge($customer, (array)$user);

                unset($result['mPezaID']);
                unset($result['createdate']);
                unset($result['location_long']);
                unset($result['location_lat']);

                $message = [
                'success' => true, // Automatically generated by the model
                'data' => $result,
                'message' => "Please see user details."
                ];

            }else{
                $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => "Token not valid."
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

    public function profile_update_post()
    {

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
        $nopicture = $requestjson;
        $nopicture['photo'] = 'removed for space reasons';
        $this->app_model->save_raw_data(json_encode($nopicture),'api','profile_update');
        $store_picture = '';

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            
            if($user_id){

                $user = $this->user_model->get_general_user($user_id);
                $customer_id = $user->customer_id;

                $error = false;
                $message = '';
                $date = date("Y-m-d H:i:s");

                if(isset($requestjson['photo']) && $requestjson['photo'] != ''){
                    $store_picture = 'store_front_' . $customer_id.'.jpg';
                    $this->load->model('spazapp_model');
                    $this->spazapp_model->base64_to_jpeg($requestjson['photo'], 'assets/uploads/customer/'.$store_picture);
                    unset($requestjson['photo']);
                    $requestjson['store_picture'] = $store_picture;
                }

                unset($requestjson['token']);

                $expected_array = array(
                    'first_name',
                    'last_name',
                    'id_number',
                    'email',
                    'password',
                    'confirm_password',
                    'company_name',
                    'shop_type',
                    'address',
                    'suburb',
                    'province',
                    'location_long',
                    'location_lat',
                    'region_id',
                    'store_picture'
                    );

                $accepted_array = array();
                $rejected_array = array();

                foreach ($requestjson as $key => $field) {
                    if(in_array($key, $expected_array)){
                        $accepted_array[$key] = $field;
                    }else{
                        $error = true;
                        $rejected_array[] = $key;
                    }
                }
                
                if (!$error){
                       
                    $this->load->model('user_model');

                    if(isset($accepted_array['first_name']) && isset($accepted_array['last_name'])){
                        $accepted_array['name'] = $accepted_array['first_name'] . ' ' . $accepted_array['last_name'];
                    }

                    if(isset($accepted_array['password'])){
                        $this->aauth->update_password($user_id, $accepted_array['password']);
                    }

                    $this->app_model->update_user($user_id, $accepted_array);
                    $this->app_model->update_customer($customer_id, $accepted_array);
                        
                    $this->event_model->private_track_event($user_id, 'app', 'update_user', 'user updated', '', $date);

                    $user_info = $this->aauth->get_user($user_id); // remember this is an object not array.
                                
                    if(!isset($requestjson['app'])){
                        $app = $user_info->app;
                    }

                    $username = $user_info->username;

                    unset($user_info->pass);
                    unset($user_info->parent_id);
                    unset($user_info->banned);
                    unset($user_info->last_login);
                    unset($user_info->last_activity);
                    unset($user_info->last_login_attempt);
                    unset($user_info->forgot_exp);
                    unset($user_info->remember_time);
                    unset($user_info->remember_exp);
                    unset($user_info->verification_code);
                    unset($user_info->ip_address);
                    unset($user_info->login_attempts);
                    unset($user_info->cellphone);
                    unset($user_info->username);
                    unset($user_info->customer_id);

                    $token = $this->user_model->generate_token($user_info->id);

                    if(isset($requestjson['imei'])){
                        $this->user_model->save_imei($user_info->id, $requestjson['imei']);
                    }

                    $user_link = '';

                    switch ($user_info->default_usergroup) {
                        case 8:  // Customer
                            $this->load->model('customer_model');
                            $user_link = $this->customer_model->get_customer($user_info->user_link_id);
                            break;
                        case 19: // Trader
                            $this->load->model('trader_model');
                            $user_link = $this->trader_model->get_trader($user_info->user_link_id);
                            break;

                        case 18: // Driver
                            $user_link = '';
                            break;
                        case 23: // Driver
                        case 24: // Driver
                        case 25: // Driver
                        case 26: // Driver
                        case 27: // Driver
                        case 28: // Driver
                        case 29: // Driver
                        case 30: // Driver
                            $user_link = '';
                            $this->load->model('comms_wallet_model');
                            $wallets = $this->comms_wallet_model->get_balances($username);
                            break;
                        
                        default:
                            # code...
                            break;
                    }

                    $app_design = $this->global_app_model->get_app_design($app);

                    $rewards = $this->rewards_model->categorise_user($username);

                    $data = array( 
                             'demo_user' => false,
                             'user' => $user_info,
                             'user_link' => $user_link,
                             'rewards' => $rewards,
                             'app_design' => $app_design,
                             'token' => $token
                             );
                    if(isset($wallets)){
                        $data['wallets'] = $wallets;
                    }

                    $message = [
                        'success' => true, // Automatically generated by the model
                        'data' => $data,
                        'message' => 'Successfully updated user'
                    ];


                }else{
                    $message = [
                        'success' => false, // Automatically generated by the model
                        'data' => array('illegal_array' => $rejected_array, 'expected_array' => $expected_array),
                        'message' => "please only post fields from the expected array."
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


    public function location_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','location');
        $requestjson = json_decode($requestjson, true);

        $message = [
            'success' => false, // Automatically generated by the model
            'data' => array(),
            'message' => 'no token found'
        ];

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            if($user_id){
                if(isset($requestjson['long']) && isset($requestjson['lat'])){
                    $this->app_model->store_long_lat($user_id, $requestjson['long'],$requestjson['lat']);
                }
                $message = [
                    'success' => true, // Automatically generated by the model
                    'data' => array(),
                    'message' => 'Location Stored.'
                ];
            }else{
                $message = [
                    'success' => false, // Automatically generated by the model
                    'data' => array(),
                    'message' => 'Token invalid or expired'
                ];
            }
            
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code

    }


    public function keepalive_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','keepalive');
        $requestjson = json_decode($requestjson, true);

        $message = [
            'success' => false, // Automatically generated by the model
            'data' => array(),
            'message' => 'no token found'
        ];

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            if($user_id){
                if(isset($requestjson['long']) && isset($requestjson['lat'])){
                    $this->app_model->store_long_lat($user_id, $requestjson['long'],$requestjson['lat']);
                }
                $message = [
                    'success' => true, // Automatically generated by the model
                    'data' => array(),
                    'message' => 'Token expiry has been updated'
                ];
            }else{
                $message = [
                    'success' => false, // Automatically generated by the model
                    'data' => array(),
                    'message' => 'Token invalid or expired'
                ];
            }
            
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code

    }

    public function keepalive_get()
    {
        // $this->some_model->update_user( ... );
        $message = [
            'success' => true, // Automatically generated by the model
            'data' => array(),
            'message' => 'Do the monkey with me!'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function logout_post()
    {
        $this->aauth->logout();
        $message = [
            'success' => true, // Automatically generated by the model
            'data' => array(),
            'message' => 'You have been logged out'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function logout_get()
    {
        $this->aauth->logout();
        $message = [
            'success' => true, // Automatically generated by the model
            'data' => array(),
            'message' => 'You have been logged out'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function promotions_post()
    {
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','promotions');
        $requestjson = json_decode($requestjson, true);
        $promotions = $this->app_model->get_promotions($requestjson['category']);
        $message = [
            'success' => true, // Automatically generated by the model
            'data' => array('promotions' => $promotions, 'count' => count($promotions)),
            'message' => 'generated list of promotions with count'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function events_post()
    {
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','events');
        $events = $this->app_model->get_events();
        $message = [
            'success' => true, // Automatically generated by the model
            'data' => array('events' => $events, 'count' => count($events)),
            'message' => 'generated list of events with count'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function event_post()
    {
        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
        $this->app_model->save_raw_data(json_encode($requestjson),'api','event');

        if ($requestjson['token'] != '' && !empty($requestjson['token']) && $requestjson['event_id'] != '' && !empty($requestjson['event_id'])){

            $this->load->model('user_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            if($user_id){
                $event_id = $requestjson['event_id'];
                $event = $this->app_model->get_event($event_id);
                $this->app_model->log_event_view($user_id, $event_id);
                $message = [
                    'success' => true, // Automatically generated by the model
                    'data' => array('event' => array($event)),
                    'message' => 'generated event'
                ];

            }else{
                $message = [
                    'success' => false, // Automatically generated by the model
                    'data' => array(),
                    'message' => 'Not logged in'
                ];
            }
        }else{
            $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => 'No promotion id found'
            ];
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    } 

    public function promotions_get()
    {
        $message = [
            'success' => true, 
            'data' => array(),
            'message' => 'Do the monkey with me!'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function promotionnt_post()
    {

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
        $this->app_model->save_raw_data(json_encode($requestjson),'api','promotions');

        if ($requestjson['promotion_id'] != '' && !empty($requestjson['promotion_id'])){

            $this->load->model('user_model');

            $promotion_id = $requestjson['promotion_id'];
            $promotion = $this->app_model->get_promotion($promotion_id);
            $message = [
                'success' => true, // Automatically generated by the model
                'data' => array('promotion' => array($promotion)),
                'message' => 'generated individual promotion'
            ];

        }else{
            $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => 'No promotion id found'
            ];
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function promotion_post()
    {

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
        $this->app_model->save_raw_data(json_encode($requestjson),'api','promotion');

        if ($requestjson['token'] != '' && !empty($requestjson['token']) && $requestjson['promotion_id'] != '' && !empty($requestjson['promotion_id'])){

            $this->load->model('user_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            if($user_id){

                $promotion_id = $requestjson['promotion_id'];
                $promotion = $this->app_model->get_promotion($promotion_id);
                $this->app_model->log_promotion_view($user_id, $promotion_id);
                $message = [
                    'success' => true, // Automatically generated by the model
                    'data' => array('promotion' => array($promotion)),
                    'message' => 'generated list of promotions with count'
                ];

            }else{
                $message = [
                    'success' => false, // Automatically generated by the model
                    'data' => array(),
                    'message' => 'Not logged in'
                ];
            }
        }else{
            $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => 'No promotion id found'
            ];
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function password_reset_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','password_reset');

        $requestjson = json_decode($requestjson, true);
        if(isset($requestjson['username'])){
            $username = $requestjson['username'];
            $user = $this->user_model->user_search($username);
            if($user && isset($user['id'])){
                $this->event_model->track('login','app_reset_password', $user['id']);
                $this->user_model->reset_password($user['id']);
                $message = "Password reset Successfully.";
                $success = true;
            }else{
                $this->event_model->track('login','app_reset_password_attempt', $username);
                $message = "User name does not exist.";
                $success = false;
            }
        }else{

            $message = "Please send active username.";
            $success = false;
        }
        

        $message = [
            'success' => $success, // Automatically generated by the model
            'data' => array(),
            'message' => $message
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function password_reset_get()
    {
        $message = [
            'success' => true, 
            'data' => array(),
            'message' => 'Do the monkey with me!'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function update_password_post()
    {

            $requestjson = file_get_contents('php://input');
            $this->app_model->save_raw_data($requestjson,'api','update_password');
            $requestjson = json_decode($requestjson, true);


            if(isset($requestjson['username']) && isset($requestjson['password'])){
                $this->aauth->login(trim($requestjson['username']), trim($requestjson['password']));

                if ($this->aauth->is_loggedin()){

                    $username = $requestjson['username'];
                    $user_info = $this->aauth->get_user(); // remember this is an object not array.
              
                    $new_password = trim($requestjson['new_password']);
                    $hashed_password = $this->aauth->hash_password($new_password, $user_info->id);
                    $this->user_model->update_password($user_info->id,$hashed_password);
                    
                    $message = "Password updated Successfully.";
                    $success = true;
                

                }else{

                    $message = "Username and password combination invalid.";
                    $success = false;
                }
            }else{

                    $message = "Please provide username and password.";
                    $success = false;
                }
        

        $message = [
            'success' => $success, // Automatically generated by the model
            'data' => array(),
            'message' => $message
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function update_password_get()
    {
        $message = [
            'success' => true, 
            'data' => array(),
            'message' => 'Do the monkey with me!'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function promotion_get()
    {
        $message = [
            'success' => true, 
            'data' => array(),
            'message' => 'Do the monkey with me!'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function cashvancashup_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','cashvancashup');

        $requestjson = json_decode($requestjson, true);
        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            if(isset($user_id) && $user_id != ''){
                $this->load->model('order_model');
                $message = "List of orders for this cash van user.";
                $orders = $this->order_model->get_order_by_cashvan($user_id);
            }else{
                $message = "Please send an active token.";
                $orders = array();
                $success = false;
            }
        }

        $message = [
            'success' => true, // Automatically generated by the model
            'data' => $orders,
            'message' => $message
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function cashvancashup_get()
    {
        $message = [
            'success' => true, 
            'data' => array(),
            'message' => 'Do the monkey with me!'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function get_customer_types_post()
    {

        $requestjson = file_get_contents('php://input');
        
        $data = $this->app_model->get_customer_types();

        $message = [
            'success' => true, 
            'data' => $data, 
            'message' => 'OK'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); 
    }


    public function get_provinces_post()
    {

        $requestjson = file_get_contents('php://input');
        
        $data = $this->app_model->get_provinces();

        $message = [
            'success' => true, 
            'data' => $data, 
            'message' => 'OK'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); 
    }

    public function global_statuses_get()
    {
      
        $data = $this->app_model->get_global_statuses();

        $message = [
            'success' => true, 
            'data' => $data, 
            'message' => 'OK'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); 
    }

    public function get_order_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','get_order_post');
        $data = array();

        $requestjson = json_decode($requestjson, true);
        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            if(isset($requestjson['order_id']) && is_numeric($requestjson['order_id'])){
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
                if(isset($user_id) && $user_id != ''){
                    if($requestjson['order_id']){
                        $order_id = $requestjson['order_id'];
                        $this->load->model('app_model');
                        $data = $this->app_model->get_order($order_id);
                        $message = "See order information.";
                        $success = true;
                    }else{
                        $message = "Please supply a valid order id.";
                    }
                }else{
                    $message = "Please send an active token.";
                    $success = false;
                }
            }else{
                $message = "Please send an order id.";
                $success = false;
            }
        }else{
            $message = "Please send an active token.";
            $success = false;
        }

        $message = [
            'success' => $success, 
            'data' => $data, 
            'message' => $message
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function cancel_order_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','cancel_order_post');
        $data = array();

        $requestjson = json_decode($requestjson, true);
        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            if(isset($requestjson['order_id']) && is_numeric($requestjson['order_id'])){
                $this->load->model('app_model');
                $data = $this->app_model->cancel_order($requestjson['order_id']);
                $message = "Order successfully cancelled.";
                $success = true;

            }else{
                $message = "Please send an order id.";
                $success = false;
            }
        }else{
            $message = "Please send an active token.";
            $success = false;
        }

        $message = [
            'success' => $success, 
            'message' => $message
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }
    
    public function cancel_ditro_order_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','cancel_distro_order');
        $data = array();

        $requestjson = json_decode($requestjson, true);
        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            if(isset($requestjson['distro_order_id']) && is_numeric($requestjson['distro_order_id'])){
                $this->load->model('app_model');
                $data = $this->app_model->cancel_distributor_order($requestjson['distro_order_id']);
                $message = "Order successfully cancelled.";
                $success = true;

            }else{
                $message = "Please send an order id.";
                $success = false;
            }
        }else{
            $message = "Please send an active token.";
            $success = false;
        }

        $message = [
            'success' => $success, 
            'message' => $message
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function edit_order_post()
    {
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','edit_order');
        $data = array();
        $requestjson = json_decode($requestjson, true);  
        
        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            if(isset($requestjson['order_id']) && is_numeric($requestjson['order_id'])){
                $this->load->model('app_model');
                $data = $this->app_model->edit_order($requestjson['order_id'], $requestjson);
                $message = "Order successfully updated.";
                $success = true;

            }else{
                $message = "Please send an order id.";
                $success = false;
            }             
        }
        else{
            $message = "Please send an active token.";
            $success = false;
        }

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code     
    }

    public function get_all_regions_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','get_all_regions_post');
        $data = array();
        $requestjson = json_decode($requestjson, true);

        $parent_id = 0;
        if(isset($requestjson['parent_id']) && is_numeric($requestjson['parent_id'])){
            $parent_id = $requestjson['parent_id'];
        }
        
        $this->load->model('app_model');
        $data = $this->app_model->get_all_regions($parent_id);
        $message = "OK";
        $success = true;

        $return = [
            'success' => $success, 
            'data' => $data, 
            'message' => $message
        ];

        $this->set_response($return, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function support_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','support');

        $requestjson = json_decode($requestjson, true);
        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            if(isset($user_id) && $user_id != ''){
                if($requestjson['subject'] && $requestjson['body']){
                    
                    $subject = $requestjson['subject'];
                    $body = $requestjson['body'];
                    $user = $this->aauth->get_user($user_id); // remember this is an object not array.

                    $this->load->model('comms_model');
                    $this->comms_model->send_support_mailer($user, $subject, $body);
                    $message = "Support query has been sent.";
                }else{
                    $message = "Please post a subject and body for support query.";
                }

            }else{
                $message = "Please send an active token.";
                $orders = array();
                $success = false;
            }
        }

        $message = [
            'success' => true, // Automatically generated by the model
            'message' => $message
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function support_get()
    {
        $message = [
            'success' => true, 
            'data' => array(),
            'message' => 'Do the monkey with me!'
        ];

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function marketing_product_get()
    {

/*
    [0] => Array
        (
            [id] => 1
            [name] => KNORR PKT SOUP MINESTRONE 10X50G 
            [barcode] => 999999
            [picture] => b3f14-knorr-pkt-soup-minestrone-10x50g.jpg
            [qty] => 0
            [pack_size] => 
            [units] => gram
            [supplier_id] => 1
            [supplier] => Unilever
            [distributor_id] => 1
        )
*/
        $this->load->model('product_model');
        $products = $this->product_model->get_marketing_products();
        $data = '<table>';
        $data .= '<tr>
            <td>picture</td>
            <td>id</td>
            <td>name</td>
            <td>barcode</td>
            <td>supplier</td>
            <td>distributor</td>
            <td>unit price</td>
            <td>sell price</td>
        </tr>';

        foreach ($products as $key => $prod) {
            $data .= '<tr>
                <td><img src="https://admin.spazapp.co.za/images/'.$prod['picture'].'" width="100px"/></td>
                <td>'.$prod['id'].'</td>
                <td>'.$prod['name'].'</td>
                <td>'.$prod['barcode'].'</td>
                <td>'.$prod['supplier'].'</td>
                <td>'.$prod['distributor'].'</td>
                <td>R '.$prod['unit_price'].'</td>
                <td>R '.$prod['sell_price'].'</td>
            </tr>';
        }

        $data .= '</table>';

        echo $data;
    }

    public function update_push_token_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson,'api','update_push_token_post');
        $requestjson = json_decode($requestjson, true);

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
            $this->load->model('user_model');
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id)
            {


                if(isset($requestjson['type'])){
                    $data['type'] = $requestjson['type'];
                }
                $app = $requestjson['app'];
                $push_token = $requestjson['push_token'];

                $user = $this->user_model->get_user_details($user_id);

                $isThere = $this->user_model->get_user_in_pushtokens($app, $user_id);

                if($isThere)
                {
                    $data['push_token'] = $push_token;
                    $post = $this->user_model->update_push_token($user_id, $app, $data);

                    if($post)
                    {
                        $message = [
                        'success' => true, // Automatically generated by the model
                        'data' => array(),
                        'message' => "Your details have been successfully updated."
                        ];
                    }
                    else
                    {
                        $message = [
                        'success' => false, // Automatically generated by the model
                        'data' => array(),
                        'message' => "Unable to update new details."
                        ];                        
                    }
                }
                else
                {
                    $data['user_id'] = $user_id;
                    $data['app'] = $app;
                    $data['push_token'] = $push_token;
                    $data['createdate'] = date('Y-m-d H:i:s');

                    $insert = $this->user_model->insert_new_push_token($data);

                    if($insert)
                    {
                        $message = [
                        'success' => true, // Automatically generated by the model
                        'data' => array(),
                        'message' => "Your details have been successfully added."
                        ];
                    }
                    else
                    {
                        $message = [
                        'success' => false, // Automatically generated by the model
                        'data' => array(),
                        'message' => "Unable to update new details."
                        ];                        
                    }
                }
            }
            else
            {
                $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => "Token not valid."
                ];
            }
        }
        else
        {

            $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => "Please post a valid token."
            ];
            
        }        

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    
    public function get_region_by_province_post()
    {

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);

        if(!$requestjson['province_id']){

                 $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => "province id is required."
                ];  

        }else{
        
        
            $data['regions'] = $this->app_model->get_regions_from_province($requestjson['province_id']);   
            
            if(!empty($data['regions']))
            {
                $message = [
                'success' => true, // Automatically generated by the model
                'data' => $data['regions'],
                'message' => "Ok"
                ];  
            }
            else
            {
                 $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => "No regions found"
                ];  
            }
        }
                
        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }
    
    public function get_distributors_from_region_post()
    {

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);

        if(!$requestjson['region_id']){

                 $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => "region id is required."
                ];  

        }else{
        
        
            $data['distributors'] = $this->app_model->get_distributor_from_region($requestjson['region_id']);   
            
            if(!empty($data['distributors']))
            {
                $message = [
                'success' => true, // Automatically generated by the model
                'data' => $data['distributors'],
                'message' => "Ok"
                ];  
            }
            else
            {
                 $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => "No distributors found"
                ];  
            }
        }
                
        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function get_cats_post()
    {

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
        
        $message ='';
        $data = array();
      
        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
           
            $this->load->model('user_model');

            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }
            
            if($user_id)
            {
                $user           = $this->user_model->get_general_user($user_id);      
                $customer_id    = $user->customer_id;
                $region         = $this->customer_model->get_customer_region($customer_id);
                $customer_type  = $region['customer_type'];

                if(!isset($requestjson['cat_id']) || $requestjson['cat_id'] == '')
                {
                    $requestjson['cat_id'] = 0;
                }
                
                
                /*$data['categories'] = $this->app_model->get_category_list($requestjson['cat_id'], $customer_type, $customer_id);*/
            
                $categories = $this->app_model->get_category_list($requestjson['cat_id'], $customer_type, $customer_id);
               
                $data['sub_cats'] = 0;
                foreach($categories as $row)
                {
                    
                    if($requestjson['cat_id'] == $row['parent_id']){
                        $data['sub_cats'] = 1;
                    }
                     
                }
               
                $message = [
                'success' => true, // Automatically generated by the model
                'data'    => $categories,
                'message' => "Ok"
                ];

            }
            else
            {
                $message = [
                'success' => false, // Automatically generated by the model
                'data'    => array(),
                'message' => "Token not valid."
                ];
            }
            
        }
        else
        {   
            $message = [
            'success' => false, // Automatically generated by the model
            'data' => array(),
            'message' => "Please post a valid token."
            ];
        }        
        
        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }
 
    public function get_products_post()
    {

        $requestjson = file_get_contents('php://input');
    $this->app_model->save_raw_data($requestjson,'api','get_products_post');
        $requestjson = json_decode($requestjson, true);
        
        $message ='';
        $customer_type = ''; 
        $region_id = '';
        if ($requestjson['token'] != '' && !empty($requestjson['token']))
        {
           if(!isset($requestjson['cat_id'])){
            $requestjson['cat_id'] = 0;
           }
            $this->load->model('user_model');
            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }
            
            if($user_id)
            {
                $user           = $this->user_model->get_general_user($user_id);      
                $customer_id    = $user->customer_id;
                $region         = $this->customer_model->get_customer_region($customer_id);
                $customer_type  = $region['customer_type'];  
                $region_id      = $region['region_id'];
                $data['balance'] = $this->financial_model->get_wallet_balance($user->username);
                
                if(isset($requestjson['specials']))
                {
                    $requestjson['cat_id'] = 0;
                    $products = $this->app_model->get_product_specials_list($customer_type, $region_id, $requestjson['cat_id']);

                }else{
                    $products = $this->app_model->get_product_list($customer_type, $region_id, $requestjson['cat_id']);
                }

        $data['products'] = array();
                foreach ($products as $key => $value) {
                    $data['products'][] = $value;
                }

                $message = [
                'success' => true, // Automatically generated by the model
                'data' => $data,
                'message' => "Ok"
                ];                
            }
            else
            {
                $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => "Token not valid."
                ];
            }            
        }
        else
        {   
            $message = [
            'success' => false, // Automatically generated by the model
            'data' => array(),
            'message' => "Please post a valid token."
            ];
        }        
        
        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    } 

    public function get_product_post()
    {

        $requestjson = file_get_contents('php://input');
	$this->app_model->save_raw_data($requestjson,'api','get_product_post');
        $requestjson = json_decode($requestjson, true);
        
        $message ='';
        $customer_type = ''; 
        $region_id = '';
        if ($requestjson['token'] != '' && !empty($requestjson['token']))
        {
           if(!isset($requestjson['product_id'])){
            $requestjson['product_id'] = 0;
           }
            $this->load->model('user_model');
            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }
            
            if($user_id)
            {
                
                $data = $this->product_model->get_product($requestjson['product_id']);

                $message = [
                'success' => true, // Automatically generated by the model
                'data' => $data,
                'message' => "Ok"
                ];                
            }
            else
            {
                $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => "Token not valid."
                ];
            }            
        }
        else
        {   
            $message = [
            'success' => false, // Automatically generated by the model
            'data' => array(),
            'message' => "Please post a valid token."
            ];
        }        
        
        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

     public function new_region_post()
    {

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);

        $this->app_model->save_raw_data(json_encode($requestjson),'api','new_region');

        if(!isset($requestjson['province'])){
            $requestjson['province'] = '0';
        }
        
        $expected_array = array(
            'first_name',
            'last_name',
            'email',
            'cellphone',
            'province'
            );
        $message ='Ok';

        foreach ($expected_array as $field) {
            if(!isset($requestjson[$field])){
                $error = true;
                $message = "Could not find '$field' in post array";
            }
        }

        if(!isset($requestjson['province'])){
            $requestjson['province'] = '0';
        }

        $requestjson['name'] = $requestjson['first_name']." ".$requestjson['last_name'];
        unset($requestjson['first_name']);
        unset($requestjson['last_name']);

        unset($requestjson['region_id']);
        unset($requestjson['shop_type']);
        unset($requestjson['long']);
        unset($requestjson['lat']);
        unset($requestjson['photo']);

        $this->customer_model->insert_pending_registration($requestjson);

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    function masterpass_result_post(){

/*

{
   "transactionId":41437,
   "reference":"2_20170420_130706",
   "amount":5,
   "currencyCode":"ZAR",
   "status":"SUCCESS",
   "bankResponse":{
      "retrievalReferenceNumber":43459,
      "authCode":" DEBUG",
      "bankResponse":"00"
   },
   "code":"9544871201",
   "msisdn":"27827378714",
   "cardInfo":{
      "cardType":"DEBIT",
      "binLast4":"500100-9695",
      "accountType":"CREDIT",
      "cardHolderName":"success"
   },
   "merchantId":380
}

*/
        
        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);

        $this->app_model->save_raw_data(json_encode($requestjson),'api','masterpass_result');

        $this->load->library('masterpass');
        $result = $this->masterpass->process_payment_result($requestjson);
        if($result){
            $this->financial_model->masterpass_payment($result);
        }

        $message = [
        'success' => true,
        'message' => "OK"
        ];

        $this->set_response($message, REST_Controller::HTTP_OK);
    }


    public function get_orders_post()
    {

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson);
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

/*public function verify_otp_post(){

        $error = false;
        $message = '';

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data(json_encode($requestjson),'api','verify_otp');
        $requestjson = json_decode($requestjson, true);

        $expected_array = array(
            'otp',
            'username'
            );

             foreach ($expected_array as $field) {
                if(empty($requestjson[$field])){
                    $error = true;
                    $message = "Could not find '$field' in post array";
                }
            }
            if (!$error){

                $result = $this->aauth->verify_otp($requestjson['otp'],$requestjson['username']);
                if($requestjson['otp']==$result['otp'] || $requestjson['username']==$result['username']){
                    if($requestjson['otp']==$result['otp']){
                        $msg='Your account has been verified.';
                        $this->aauth->unban_user_with_username($requestjson['username']);
                    }else{
                        $msg='Incorrect OTP';
                        
                    }

                    $message = [
                    'success' => true,
                    'message' => $msg
                    ];
                }else{
                    $message = [
                        'success' => false, // Automatically generated by the model
                        'data' => array(),
                        'message' => "Incorrect username and otp combination."
                    ];
                }

            }else{
                    $message = [
                        'success' => false, // Automatically generated by the model
                        'data' => array(),
                        'message' => $message
                    ];
            }
                        
            $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code


    }*/

    public function resend_otp_post(){
        $error = false;
        $message = '';
        $msg='';
        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data(json_encode($requestjson),'api','resend_otp');
        $requestjson = stripslashes($requestjson);
        $requestjson = json_decode($requestjson, true);

        $expected_array = array(
            'username'
            );

             foreach ($expected_array as $field) {
                if(empty($requestjson[$field])){
                    $error = true;
                    $message = "Could not find '$field' in post array";
                }
            }
            if (!$error){

                    $result = $this->aauth->check_username($requestjson['username']);
                    if($result){
                        $msg = 'Username does not exist';
                    }else{
                        $result = $this->app_model->resend_otp($requestjson['username']);
                        $msg = 'Your OTP has been resent.';
                        if(!$result){
                            $msg = 'Your account is already verified.';
                        }
                    }

                    $message = [
                    'success' => true,
                    'message' => $msg
                    ];

            }else{
                    $message = [
                        'success' => false, // Automatically generated by the model
                        'data' => array(),
                        'message' => $message
                    ];
            }
                        
            $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    public function get_balance_post(){

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
        
        $message ='';
        $customer_type = ''; 
        $region_id = '';
        if ($requestjson['token'] != '' && !empty($requestjson['token']))
        {
           if(!isset($requestjson['cat_id'])){
            $requestjson['cat_id'] = 0;
           }
            $this->load->model('user_model');
            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }
            
            if($user_id)
            {
                $user           = $this->user_model->get_general_user($user_id);      
                $data['balance'] = $this->financial_model->get_wallet_balance($user->username);
                
                $message = [
                'success' => true, // Automatically generated by the model
                'data' => $data,
                'message' => "Ok"
                ];                
            }
            else
            {
                $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => "Token not valid."
                ];
            }            
        }
        else
        {   
            $message = [
            'success' => false, // Automatically generated by the model
            'data' => array(),
            'message' => "Please post a valid token."
            ];
        }
        
        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }    

    public function get_messages_post(){

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
        
        $message ='';
        $customer_type = ''; 
        $region_id = '';

        $data['messages'][0] = array(
                "id" => "1",
                "title" => "Welcome to Spazapp!",
                "copy" => "Your busines in a box. Sell, Buy and Save.",
                "createdate" => date("Y-m-d H:i:s")
            );
        if ($requestjson['push_token'] != '' && !empty($requestjson['push_token']))
        {

            $this->load->model('user_model');

            $user_id = $this->user_model->get_user_from_push_token('spazapp', $requestjson['push_token']);

           
            if($user_id)
            {

                $this->load->library('comms_library');
                $data['messages'] = $this->comms_library->get_recent_messages($user_id);
                
                $message = [
                'success' => true, // Automatically generated by the model
                'data' => $data,
                'message' => "Ok"
                ];                
            }
            else
            {
                $message = [
                'success' => true, // Automatically generated by the model
                'data' => $data,
                'message' => "Ok"
                ];
            }            
        }
        else
        {   
            $message = [
            'success' => false, // Automatically generated by the model
            'data' => array(),
            'message' => "Please post a push_token."
            ];
        }
        
        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }


  function verify_cellphone_post(){
        $message="";
        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);

        $otp = $this->app_model->store_otp($requestjson);
        if(!empty($otp)){
            $requestjson['otp'] = $otp;
            $this->app_model->send_otp($requestjson);
            $message = [
                'success' => true, // Automatically generated by the model
                'data' => array(),
                'message' => "Otp has been sent to: ".$requestjson['cellphone']
            ];  
        }else{
            $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => "Oops something went wrong."
            ];  
        }
              
        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    function verify_otp_post(){
        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);

        $is_verified = $this->app_model->verify_otp($requestjson);
        if($is_verified){
             $this->aauth->unban_user_with_username($requestjson['cellphone']);
             $message = [
                'success' => true, // Automatically generated by the model
                'data' => array(),
                'message' => "Your otp has been successfully verified"
            ];  
        }else{
             $message = [
                'success' => false, // Automatically generated by the model
                'data' => array(),
                'message' => "Incorrect cellphone and otp combination."
            ];  
        }

         $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code

    }


}
