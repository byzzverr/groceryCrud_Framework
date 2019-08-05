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
class Insurance extends REST_Controller {

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

public function funeral_create_update_post(){
 
    $requestjson = file_get_contents('php://input');
    $requestjson = json_decode($requestjson, true);
    
    $this->app_model->save_raw_data(json_encode($requestjson));
    
    $message            =   '';
    $product_id         =   '';
    $data               =   '';
    $policy_number      =   '';
    
    
    if ($requestjson['token'] != '' && !empty($requestjson['token'])){
           
    $user_id = $this->user_model->get_user_from_token($requestjson['token']);
    unset($requestjson['token']);

    if($user_id){
        
            if(!empty($requestjson['policy_number'])){
                //update
            
                    $update = $requestjson;
                    unset($update['policy_number']);
                    $ins_app = $this->insurance_model->get_app_insurance_policy_number($requestjson['policy_number']);
                    if($ins_app){
                        $update_result = $this->insurance_model->update_policy_application($requestjson['policy_number'], $update);
                        if($ins_app['tel_cell'] == ''){
                            $sms = "Spazapp: Funeral Policy Activated. Expires: ".$ins_app['expiry_date'].". Policy Number: lib-2017-".$requestjson['policy_number'].". To claim call 0860 123 123 with valid death certificate.";
                            $this->comms_model->send_sms($requestjson['tel_cell'], $sms);
                        }
                    }else{

                    }

                    if($update_result){

                        $ins_results = $this->insurance_model->get_app_insurance_policy_number($requestjson['policy_number']);
                        $data['policy_number'] = $ins_results['policy_number'];
                        $data['policy'] = $ins_results;
                        $data['dependents'] = $this->insurance_model->get_app_dependent_policy_number($data['policy_number']);

                        $message = [
                            'success' => true, // Automatically generated by the model
                            'data' => $data,
                            'message' => "Policy updated"
                            ];   

                    }else{

                        $message = [
                            'success' => false, // Automatically generated by the model
                            'data' => "",
                            'message' => "Policy number does not exist"
                            ];    

                    }

            }else{
                //insert

                $policy_number = $this->insurance_model->valid_unique($requestjson);

                if(!$policy_number){

                    $requestjson['application_date'] = date('Y-m-d'); // Constant value
                    $requestjson['expiry_date'] = date("Y-m-d", strtotime("+30 days"));
                    $requestjson['sold_by'] = $user_id;
                    $requestjson['payment_reference_no'] = rand(100000,999999);
                    $policy = $this->insurance_model->insert_policy_application($requestjson);
                    if($policy){
                            $policy_number = $policy['policy_number'];

                            $data['policy_number'] = $policy['policy_number'];
                            $data['policy'] = $policy; 

                            $message = [
                            'success' => true, // Automatically generated by the model
                            'data' => $data,
                            'message' => 'Policy Inserted'
                            ];

                    }else{

                $message = [
                            'success' => false, // Automatically generated by the model
                            'data' => $data,
                            'message' => 'something fucked'
                            ];

                    }
                }else{
                    $data['policy_number'] = $policy_number;
                    $data['dependents'] = $this->insurance_model->get_app_dependent_policy_number($policy_number);
                $message = [
                            'success' => true, // Automatically generated by the model
                            'data' => $data,
                            'message' => 'Please make sure you use a unique id and cell number'
                            ];

                }

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



public function funeral_search_post(){
 
    $requestjson = file_get_contents('php://input');
    $requestjson = json_decode($requestjson, true);
    $this->app_model->save_raw_data(json_encode($requestjson));
    
    if ($requestjson['token'] != '' && !empty($requestjson['token'])){
           
    $user_id = $this->user_model->get_user_from_token($requestjson['token']);
    unset($requestjson['token']);

    if($user_id){
    
            unset($requestjson['token']);

            $message            =   '';
            $policy_number      =   '';
            $identity_number    =   '';
            $data               =   "";
            $tel_cell           =   '';
            $passport_number    =   '';
            $date_of_birth      =   '';


            if(isset($requestjson['id'])){
                $identity_number    =   $requestjson['id'];  
            }

            if(isset($requestjson['passport_number'])){
               $passport_number = $requestjson['passport_number'];

            }
            
            if(isset($requestjson['date_of_birth'])){
                $date_of_birth  = $requestjson['date_of_birth'];
            }
        
            if(isset($requestjson['tel_cell'])){
                $tel_cell  = $requestjson['tel_cell'];
            }

            $ins_results = array();

           if(!empty($identity_number) or !empty($tel_cell) or !empty($policy_number)){

                if(!empty($identity_number)){
                    $key = 'id';
                    $value = $identity_number;
                }

                if(!empty($tel_cell)){
                    $key = 'tel_cell';
                    $value = $tel_cell;
                }

                if(!empty($policy_number)){
                    $key = 'policy_number';
                    $value = $policy_number;
                }

                $ins_results = $this->insurance_model->key_search_for_application($key, $value);

             }else if(!empty($passport_number) && !empty($date_of_birth)){
                
                $ins_results = $this->insurance_model->passport_search_for_application($passport_number, $date_of_birth);

             }

                if(isset($ins_results['policy_number'])){

                        $data['policy_number'] = $ins_results['policy_number'];
                        $data['policy'] = $ins_results;
                        $expiry = $this->insurance_model->fetch_expiry($ins_results['policy_number']);
                        $product = $this->insurance_model->get_insurance_product_id($ins_results['ins_prod_id']);
                        $data['policy']['plan'] = $product['type'];
                        $data['policy']['premium'] = $product['premium'];
                        $data['product'] = $product;
                        $data['expired'] = $expiry['expired'];
                        $data['expiry_message'] = $expiry['message'];
                        $message       = "Policy found";
                        $success = true;
                }else{

                        $message       = "No policy matches your search criteria";
                        $success = false;
                }


                $message = [
                            'success' => $success, // Automatically generated by the model
                            'data' => $data,
                            'message' => $message
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




function insurance_renew_extend_post(){
    
    $requestjson = file_get_contents('php://input');
    $requestjson = json_decode($requestjson, true);
    $this->app_model->save_raw_data(json_encode($requestjson));
    
    $message        ='';
    $policy_number  ='';
         
    $user_id = $this->user_model->get_user_from_token($requestjson['token']);
    unset($requestjson['token']);

    if($user_id){
          
            if(isset($requestjson['policy_number'])){

            $ins_results = $this->insurance_model->get_app_insurance_policy_number($requestjson['policy_number']);       

                if(isset($ins_results['policy_number'])){
                    
                    $message = "Dependent has been added";
                }else{
                    $message = "Policy number does not exit";
                }

                $message = [
                        'success' => true, // Automatically generated by the model
                        'data' => $data,
                        'message' => $message
                        ];

            }else{
                        $message = [
                        'success' => false, // Automatically generated by the model
                        'data' => $data,
                        'message' => "Policy number required"
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


function insurance_dependent_post(){
    
    $requestjson = file_get_contents('php://input');
    $requestjson = json_decode($requestjson, true);
    $this->app_model->save_raw_data(json_encode($requestjson));
    
    $message        ='';
    $policy_number  ='';

    if ($requestjson['token'] != '' && !empty($requestjson['token'])){
           
    $user_id = $this->user_model->get_user_from_token($requestjson['token']);
    unset($requestjson['token']);

    if($user_id){
          
            $ins_results = $this->insurance_model->get_app_insurance_policy_number($requestjson['policy_number']);
        
            $policy_number2 ='';
            $policy_number2 = $ins_results['policy_number'];

            if(isset($requestjson['policy_number'])){

                if(!empty($policy_number2)){
                    $this->db->insert('ins_m_app_dependants', $requestjson);   
                    $data['dependents'] = $this->insurance_model->get_app_dependent_policy_number($requestjson['policy_number']);  
                    $message = "Dependent has been added";
                }else{
                    $message = "Policy number does not exit";
                }


//                $data = $requestjson['policy_number'];

                $message = [
                        'success' => true, // Automatically generated by the model
                        'data' => $data,
                        'message' => $message
                        ];

            }else{
                        $message = [
                        'success' => false, // Automatically generated by the model
                        'data' => $data,
                        'message' => "Policy number required"
                        ];
            }

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
    
function funeral_get_dependents_post(){
    
    $requestjson = file_get_contents('php://input');
    $requestjson = json_decode($requestjson, true);
    $this->app_model->save_raw_data(json_encode($requestjson));
    
    $message            ='';
    $policy_number      ='';
    $dependent_number   ='';
    $data   ='';

    if ($requestjson['token'] != '' && !empty($requestjson['token'])){
           
    $user_id = $this->user_model->get_user_from_token($requestjson['token']);
    unset($requestjson['token']);

    if($user_id){
            
            $ins_results = $this->insurance_model->get_app_insurance_policy_number($requestjson['policy_number']);

            $policy_number2 ='';
            $policy_number2 = $ins_results['policy_number'];

            if(isset($requestjson['policy_number'])){

               if(!empty($policy_number2)){

                $results = $this->insurance_model->get_app_dependent_policy_number($requestjson['policy_number']);  

                foreach($results as $row){
                    $dependent_number =$row['dependent_number'];

                }

                if(!empty($dependent_number)){

                     $data = $results;
                     $message ="Ok";

                }else{
                     $data ="";
                     $message ="No dependent found";
                 }  


                }else{
                    $message = "Policy number does not exit";
                } 

            $message = [
                        'success' => true, // Automatically generated by the model
                        'data' => $data,
                        'message' => $message
                        ];

            }else{
                        $message = [
                        'success' => true, // Automatically generated by the model
                        'data' => $data,
                        'message' => "Policy number required"
                        ];
            }


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
    
function funeral_remove_dependent_post(){
    
    $requestjson = file_get_contents('php://input');
    $requestjson = json_decode($requestjson, true);
    $this->app_model->save_raw_data(json_encode($requestjson));
    
    $message        ='';
    $policy_number  ='';

    if ($requestjson['token'] != '' && !empty($requestjson['token'])){
           
    $user_id = $this->user_model->get_user_from_token($requestjson['token']);
    unset($requestjson['token']);

    if($user_id){
        
                $ins_results = $this->insurance_model->get_app_insurance_policy_number($requestjson['policy_number']);

                $policy_number2 ='';
                $policy_number2 = $ins_results['policy_number'];

                if(isset($requestjson['policy_number'])){

                   if(!empty($policy_number2)){

                        $this->insurance_model->remove_dependent($requestjson['policy_number'],$requestjson['dependent_number']);  

                        $message = "Dependent has been removed";

                    }else{
                        $message = "Policy number does not exit";
                    } 

                }else{
                            $message = [
                            'success' => true, // Automatically generated by the model
                            'data' => $data,
                            'message' => "Policy number required"
                            ];
                }

  
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


function save_death_certificate_post(){
    
    $requestjson = file_get_contents('php://input');
    $requestjson = json_decode($requestjson, true);
    $nopicture = $requestjson;

    if(isset($requestjson['photo'])){
        $requestjson['death_certificate'] = $requestjson['photo'];
        $nopicture['photo'] = 'removed for space reasons';
    }

    $nopicture['death_certificate'] = 'removed for space reasons';
    $this->app_model->save_raw_data(json_encode($nopicture));

    $picture    =   '';
    $message    =   ''; 

    if ($requestjson['token'] != '' && !empty($requestjson['token'])){

        if(!isset($requestjson['policy_number']) || $requestjson['policy_number'] == 0){
            $requestjson['policy_number'] = 1;
        }

        $this->load->model('user_model');
        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        if($user_id){

                if(isset($requestjson['death_certificate']) && $requestjson['death_certificate'] != ''){

                    $picture_id = "claim";
                    $picture    = 'pic_' . $picture_id. '_' . $requestjson['policy_number'].'.jpg';

                    $this->load->model('spazapp_model');
                    $this->spazapp_model->base64_to_jpeg($requestjson['death_certificate'], 'assets/uploads/insurance/death_certificates/'.$picture);

                    unset($requestjson['token']);// Preventing post to pass into insert or update function

                    $this->insurance_model->add_death_certificate($requestjson['policy_number'],$picture);

                     $message = [
                    'success' => true, // Automatically generated by the model
                    'data' => array(),
                    'message' => "Death Certificate has been saved"
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
    
function save_picture_post(){
    
    $requestjson = file_get_contents('php://input');
    $requestjson = json_decode($requestjson, true);
    $nopicture = $requestjson;

    $requestjson['picture'] = $requestjson['photo'];
    $nopicture['photo'] = 'removed for space reasons';
    $this->app_model->save_raw_data(json_encode($nopicture));

    $picture    =   '';
    $message    =   '';

    if ($requestjson['token'] != '' && !empty($requestjson['token'])){

        $this->load->model('user_model');
        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        if($user_id){


                if(isset($requestjson['picture']) && $requestjson['picture'] != ''){

                    $picture_id = "insurance_pic";
                    $picture    = 'pic_' . $picture_id. '_' . $requestjson['policy_number'].'.jpg';

                    $this->load->model('spazapp_model');
                    $this->spazapp_model->base64_to_jpeg($requestjson['picture'], 'assets/uploads/insurance/pictures/'.$picture);

                    unset($requestjson['token']);// Preventing post to pass into insert or update function

                    $this->insurance_model->add_picture($requestjson['policy_number'],$picture);

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
 
function save_signature_post(){
    
    $requestjson = file_get_contents('php://input');
    $requestjson = json_decode($requestjson, true);
    $nopicture = $requestjson;
    $nopicture['signature'] = 'removed for space reasons';
    $this->app_model->save_raw_data(json_encode($nopicture));

    $signature  =   '';
    $message    =   '';
    
    if ($requestjson['token'] != '' && !empty($requestjson['token'])){

        $this->load->model('user_model');
        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        if($user_id){


                if(isset($requestjson['signature']) && $requestjson['signature'] != ''){

                    $signature_id   = "insurance_sign";
                    $signature      = 'sign_' . $signature_id . '_' . $requestjson['policy_number'].'.jpg';

                    $this->load->model('spazapp_model');
                    $this->spazapp_model->base64_to_jpeg($requestjson['signature'], 'assets/uploads/insurance/signatures/'.$signature);

                    unset($requestjson['token']);// Preventing post to pass into insert or update function

                    $this->insurance_model->add_signature($requestjson['policy_number'],$signature);

                     $message = [
                    'success' => true, // Automatically generated by the model
                    'data' => array(),
                    'message' => "Signature has been saved"
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
    
function funeral_get_product_post(){
    
    $requestjson = file_get_contents('php://input');
    $requestjson = json_decode($requestjson, true);
    
    $this->app_model->save_raw_data(json_encode($requestjson));
        
    $message  = '';
    $data     = '';
    
    if ($requestjson['token'] != '' && !empty($requestjson['token'])){

        $this->load->model('user_model');
        $user_id = $this->user_model->get_user_from_token($requestjson['token']);

        if($user_id){
            
                    $data['products'] = $this->insurance_model->get_ins_products();
                    $data['relation_types'] = $this->insurance_model->get_ins_relation_types();

                    $message = [
                    'success' => false, // Automatically generated by the model
                    'data' => array($data),
                    'message' => "Ok"
                    ];


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

}