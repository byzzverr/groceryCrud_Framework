<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Mp extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('news_model');
        $this->load->model('order_model');

    }

    function show_view($view, $data=''){
     
      $this->app_settings = get_app_settings(base_url());
      $this->load->view($this->app_settings['app_folder'].'include/header', $data);
      $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), $data);
      $this->load->view($this->app_settings['app_folder'].$view, $data);
      $this->load->view($this->app_settings['app_folder'].'include/footer', $data);
    }

    function pay($masterpass_code){
      $data['order_info']=$this->order_model->get_order_from_master_card($masterpass_code);
      $data['customer_info']=$this->customer_model->get_customer_from_order($data['order_info']['customer_id']);
      $total = 0;
      foreach ($data['order_info']['items']['items'] as $key => $item) { 
        $total += $item['price']*$item['quantity'];
      }
      $data['total']=number_format($total,2,'.',', ');
      $this->load->view('masterpass_pay',$data);

    }

}