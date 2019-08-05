<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Management extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('spazapp_model');
        $this->load->model('customer_model');
        $this->load->model('product_model');
        $this->load->model('insurance_model');
        $this->load->model('news_model');
        $this->load->model('fridge_model');
       

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin())
        {
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Management'))
        {
            $this->event_model->track('error','permissions', 'Management');
            redirect('/admin/permissions');
        } 
    }

    function show_view($view, $data=''){
      //$this->app_settings = get_app_settings(base_url());
        //Did this for testing 
    $this->app_settings['app_folder']='africanunity/';
      $this->load->view($this->app_settings['app_folder'].'include/header', $data);
      $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), $data);
      $this->load->view($this->app_settings['app_folder'].$view, $data);
      $this->load->view($this->app_settings['app_folder'].'include/footer', $data);
    }

    function _example_output($output = null)
    {
       // $this->app_settings = get_app_settings(base_url());

        //Did this for testing 
        $this->app_settings['app_folder']='africanunity/';

        $this->load->view($this->app_settings['app_folder'].'include/header', $output);
        $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user));
        $this->load->view('example',$output);
        $this->load->view($this->app_settings['app_folder'].'include/footer', $output);
    }

 function fridges(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('fridges');
        $crud->set_subject('Fridge');

        $crud->set_relation('fridge_type','fridge_types','name');
        // $crud->set_relation('brand_id','brands','name');
        $crud->set_relation('region_id','regions','name');
        $crud->set_relation('province','provinces','name');

        // $crud->where('brand_id',2);
        $crud->columns('id','fridge_type','fridge_unit_code','location_name','region_id','province');
        $crud->unset_delete();

        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'fridges'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $crud->add_action('Location History', '', '/africanunity/dashboard/fridge_locations_history');
        $crud->add_action('Daily Temperature', '', '/africanunity/dashboard/daily_monthly_temperature');

        $crud->unset_delete();
        $crud->unset_add();
        $output = $crud->render();

        $output->page_title = 'Fridges';

        $this->crud_view($output);
    }


    function deliver_report($fridge_id){
          //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);
        $brand_id=2;
        $this->load->library('googlemaps');
        $data['page_title'] = 'Fridge Delivery';
        $data['fidges'] =  $this->fridge_model->get_fridges_deliveries($fridge_id,$brand_id);
        $config['center'] = '-29.8590, 31.0189';
        $config['zoom'] = '1';
        $this->googlemaps->initialize($config);

        foreach ( $data['fidges'] as $key => $fridge) 
        {
           
            $marker = array();
            $marker['position'] = $fridge['long'] . ', '.$fridge['lat'];
            $marker['draggable'] = false;
            $marker['infowindow_content'] = $fridge['createdate'].' Driver Heartbeat';
            $marker['animation'] = 'DROP';
          
            $marker['icon'] = 'http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld='.$key.'|FF0000|000000'; //this needs to be more generic
            $this->googlemaps->add_marker($marker);

            // $data['driver']=$fridge['driver'];
            // $data['fridge_type']=$fridge['fridge_type'];
            // $data['fridge_unit_code']=$fridge['fridge_unit_code'];
        }
        

        $data['map'] = $this->googlemaps->create_map();

        $this->show_view('delivery_view', $data);
    }
    
   
    

}
