<?php

class Rewards_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('customer_model');
      $this->load->model('event_model');
      $this->load->model('global_app_model');
      $this->load->model('spazapp_model');
      $this->load->model('user_model');
      $this->load->model('financial_model');
      $this->load->model('order_model');

   }

   function get_spend_breakdown($app='supps365'){

      $data = array(
         array('tier' => 'blue', 'spend_start' => 0, 'spend_end' => 150000, 'percent' => 0)
      );

      if($app == 'supps365'){

         $data = array(
               array('tier' => 'blue', 'spend_start' => 0, 'spend_end' => 4999, 'percent' => 0),
               array('tier' => 'bronze', 'spend_start' => 5000, 'spend_end' => 9999, 'percent' => 2),
               array('tier' => 'silver', 'spend_start' => 10000, 'spend_end' => 14999, 'percent' => 5),
               array('tier' => 'gold', 'spend_start' => 15000, 'spend_end' => 250000, 'percent' => 7.5)
           );
      }

      return $data;

   }

   function categorise_user($username, $current_sale_total=0, $app='supps365'){

      $monthly_spend = $this->financial_model->get_monthly_spend($username);
      $monthly_spend += $current_sale_total;    

      $tiers = $this->get_spend_breakdown($app);

      $current_tier = 'blue';
      $current_discount = 0;

      foreach ($tiers as $key => $value) {
        if($monthly_spend >= $value['spend_start'] && $monthly_spend <= $value['spend_end']){

            $current_tier = $value['tier'];
            $current_discount = $value['percent'];
        }
      }

      if($app == 'supps365'){
         //if NO orders - 20% off first order.
         $order_count = $this->order_model->count_all_orders($username);
         if($order_count == 0){
           $current_discount = 20;
         }  
      }

      $rewards = array('current_spend' => $monthly_spend, 'current_tier' => $current_tier, 'current_discount' => $current_discount);

      return $rewards;

   }  
}