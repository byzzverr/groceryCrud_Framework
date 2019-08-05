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
class Taptuck_user extends REST_Controller_Taptuck {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->library("Aauth");
        $this->load->model('event_model');
        $this->load->model('app_model');
        $this->load->model('financial_model');
        $this->load->model('spazapp_model');
        $this->load->model('product_model');
        $this->load->model('taptuck_model');
        $this->load->model('user_model');

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['user_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['user_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
    }
    
     public function user_get()
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

        $user_info = $this->aauth->get_user($user_id);

        $this->event_model->track('taptuck_user_call','called_user_api', $user_id);

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
        unset($user_info->customer_id);
        unset($user_info->default_app);
        unset($user_info->distibutor_id);
        unset($user_info->user_link_id);
        unset($user_info->push_token);

        /*
        GROUPS: [14] => 'TapTuckParent',
        GROUPS: [15] => 'TapTuckMerchant',
        GROUPS: [16] => 'TapTuckAdmin'
        */

        $data = $this->taptuck_model->populate_user($user_info);

        $message = [
            'meta' => array( 'code' => 200),
            'success' => true,
            'data' => $data
        ];

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED);
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_UNAUTHORIZED);
        }
        
    }
    
}