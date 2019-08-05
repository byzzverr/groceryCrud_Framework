<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Unit_Tracking extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->helper('url');
        $this->load->model('app_model');

    }

    function index(){
        echo "These are not the droids you are looking for.";
    }


    function input(){

        // store the raw data for future reference
        $this->app_model->save_raw_data(json_encode($_REQUEST),'Unit_Tracking');
                    
    }

}