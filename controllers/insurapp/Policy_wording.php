<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class policy_wording extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('event_model');
        $this->load->model('insurance_model');
        $this->load->library('encrypt');
    }

    function show_view($view, $data=''){
     
      $this->load->view($view, $data);

    }




function encode($policy_number){

    $policy = $this->insurance_model->get_application_from_policy_no($policy_number);
    $encrypted_string = $this->encrypt->encode($policy['tel_cell'].'_'.$policy_number.'_'.$policy['ins_prod_id']);
    $url = base_url().'insurapp/policy_wording/display?hash='.$encrypted_string;

    echo $url;

}


function display(){

        $hash = str_replace(' ', '+', $_GET['hash']);
        $decoded = $this->encrypt->decode($hash);
        $policy_data = explode('_', $decoded);
        $data = array();

        if(count($policy_data) == 3){

            $cellphone = $policy_data[0];
            $policy_number = $policy_data[1];
            $product_id = $policy_data[2];

            /*if(strlen($cellphone) == 9){
                $cellphone = '0'.$cellphone;
            }*/

            $policy = $this->insurance_model->does_policy_data_match($cellphone, $policy_number, $product_id);

            if($policy){
                $data['policy'] = $policy;
                $this->show_view('insurapp/policy_document', $data);

            }else{
                die('An error occurred pleas contact support.');   
            }
        }else{
            die('An error occurred.');
        }

    }
}