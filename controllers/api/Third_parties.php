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
class Third_parties extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->library("Aauth");
		$this->load->model('task_model');
        $this->load->model('event_model');
        $this->load->model('app_model');
        $this->load->model('financial_model');
        $this->load->model('user_model');
    }

    public function register_anonymous_user_post()
    {

        $this->load->helper('email');

        $error = false;
        $message = '';
        $data = array();

        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){
           
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);

            if($user_id){
                
                $user = $this->user_model->get_user($user_id);

                // store the raw data for future reference
                $this->app_model->save_raw_data(json_encode($requestjson),'third_party_api','register_anonymous_user');

                $expected_array = array(
                    'location_long',
                    'location_lat',
                    'unique_id',
                    'country',
                    'city',
                    'region',
                    'shop_name',
                    'shop_type'
                    );

                foreach ($expected_array as $field) {
                    if(!isset($requestjson[$field])){
                        $error = true;
                        $message = "Could not find '$field' in post array";
                    }
                }

                $customer_id = $this->user_model->find_anonymous_user($user->name . '_' . $requestjson['unique_id']);

                if($customer_id){
                    $error = true;
                    $message = "unique_id has been used by you before.";
                    $data = array("store_id" => $customer_id);
                }

                if (!$error){

                    $this->load->model('user_model');

                    $requestjson['name'] = $user->name . ' ' . $requestjson['unique_id'];
                    $requestjson['first_name'] = $user->name;
                    $requestjson['last_name'] = $requestjson['unique_id'];
                    $requestjson['username'] = $user->name . '_' . $requestjson['unique_id'];
                    $requestjson['cellphone'] = $requestjson['username'];
                  
                    $otp=$this->app_model->generateRandomNumber(5);
                    $requestjson['otp']=$otp;

                    $requestjson['password'] = $otp;


                    $new_user_id = $this->aauth->create_user($requestjson['username'],$requestjson['password'],$requestjson['name'],$requestjson['username'],8);


                    $errors = $this->aauth->get_errors_array();

                    if(is_array($errors) && count($errors) >= 1){
                        $this->event_model->track('third_party_api','registration_attempt', $errors[0]);
                        $this->event_model->track('user','attempted_add_user', $errors[0]);
                        $message = [
                                'success' => false, // Automatically generated by the model
                                'data' => array(),
                                'message' => $errors[0]
                            ];
                    }else{

                        if(!isset($requestjson['email'])){
                            $requestjson['email'] = $requestjson['username'] . '@spazapp.co.za';
                        }
                        
                        $location = $this->user_model->format_location($requestjson['region'], $requestjson['city'], $requestjson['country']);
                      
                        $requestjson['country_id']        = $location['country']['id'];
                        $requestjson['city_id']           = $location['city']['id'];
                        $requestjson['region_id']         = $location['region']['id'];
                        $requestjson['customer_type']     = $requestjson['shop_type'];
                        $requestjson['lat']               = $requestjson['location_lat'];
                        $requestjson['long']              = $requestjson['location_long'];
                        
                        $customer_id = $this->app_model->create_customer($requestjson);
                        $this->app_model->update_user_customer($new_user_id,$customer_id);
                        

                        if(valid_email($requestjson['email'])){
                            $this->app_model->update_user_email($new_user_id,$requestjson['email']);
                        }

                        $this->event_model->track('user','add_user', $new_user_id);
                        $this->event_model->track('api','anonymous_registration');

                        $message = [
                            'success' => true, // Automatically generated by the model
                            'data' => array( 'store_id' => $customer_id),
                            'message' => 'Successfully registered.'
                        ];
                    }


                }else{
                    $message = [
                        'success' => false, // Automatically generated by the model
                        'data' => $data,
                        'message' => $message
                    ];
                }

                   
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


}