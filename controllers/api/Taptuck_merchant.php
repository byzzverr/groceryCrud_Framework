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
class Taptuck_merchant extends REST_Controller_Taptuck {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->library("Aauth");
        $this->load->model('event_model');
        $this->load->model('app_model');
        $this->load->model('financial_model');
        $this->load->model('taptuck_model');
        $this->load->model('tt_merchant_model');
        $this->load->model('tt_menu_model');

        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        $this->methods['user_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['user_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
    }


 public function merchants_get()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
         
        $this->app_model->save_raw_data($requestjson,'api','merchants_get');
         
        $requestjson = json_decode($requestjson, true);
        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }
                   
        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        $merchant_id = false;

        if($user_id){
            
            $data = $this->tt_merchant_model->get_tt_merchants();
            if($data){
                $message = [
                    'meta' => array('code' => 201),
                    'success' => true,
                    'data' => $data
                ];
            }else{
                $message = [
                    'meta' => array( 'code' => 401, 'reason' => 'No murchants on the system.'),
                    'success' => false,
                    'data' => array()
                ];
            }

        }else{

            $message = [
                'meta' => array( 'code' => 401, 'reason' => 'No murchants on the system.'),
                'success' => false,
                'data' => array()
            ];

        }

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED);
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_UNAUTHORIZED);
        }
        
    }


 public function merchant_get()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
         
        $this->app_model->save_raw_data($requestjson,'api','merchant_get');
         
        $requestjson = json_decode($requestjson, true);
        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }
                   
        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        $merchant_id = false;

        if(isset($_GET['merchant_id'])){
            $merchant_id = $_GET['merchant_id'];
        }

        if($user_id && $merchant_id){
            
            $data = $this->tt_merchant_model->get_tt_merchant($merchant_id);
            if($data){

                $message = [
                    'meta' => array( 'code' => 201),
                    'success' => true,
                    'data' => $data
                ];
                
            }else{
                $message = [
                    'meta' => array( 'code' => 401,'reason' => 'No merchant matches that merchant id.'),
                    'success' => false,
                    'data' => array()
                ];
            }

        }else{

            $message = [
                'meta' => array( 'code' => 401, 'reason' => 'please supply merchant id.'),
                'success' => false,
                'data' => array()
            ];

        }

        if($message['success']){
            $this->set_response($message, REST_Controller_Taptuck::HTTP_CREATED);
        }else{
            $this->set_response($message, REST_Controller_Taptuck::HTTP_UNAUTHORIZED);
        }
        
    }

    
     public function menu_get()
    {
        $message ='';
        $requestjson = file_get_contents('php://input');
         
        $this->app_model->save_raw_data($requestjson,'api','menu_get');
         
        $requestjson = json_decode($requestjson, true);
        $auth = $this->input->server('HTTP_AUTHORIZATION');
        if($auth != '' && !empty($auth)){
            $requestjson['token'] =  $auth;
        }
                   
        $user_id = $this->user_model->get_user_from_token($requestjson['token']);


        $merchant_id = false;
        $period = false;

        if(isset($_GET['merchant_id'])){
            $merchant_id = $_GET['merchant_id'];
        }

        if(isset($_GET['period'])){
            $period = $_GET['period'];
        }else{
            $period = 'All';
        }

        if(isset($_GET['date'])){
            //get_the_day_of_the_week (dotw)
            $dayofweek = date('w', strtotime($_GET['date']));
            $dotw = $dayofweek+1; // sunday is 0 we need it to be 1
        }else{
            $dotw = false;
        }

        if($user_id && $merchant_id && $period){
            
            $data = $this->tt_menu_model->get_tt_menu($merchant_id, $period, $dotw);
            $message = [
                'meta' => array( 'code' => 201),
                'success' => true,
                'data' => $data
            ];

        }else{

            $message = [
                'meta' => array( 'code' => 401, 'reason' => 'please supply merchant id.'),
                'success' => false,
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