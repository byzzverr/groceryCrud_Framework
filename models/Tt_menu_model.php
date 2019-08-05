<?php

class Tt_menu_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('financial_model');
      $this->load->model('taptuck_model');
      $this->load->model('tt_kid_model');
   }

   public function get_all_tt_menus(){
    
      $query = $this->db->query("SELECT id, merchant_id, label, description, price, period, valid_till FROM tt_menus");
      $result = $query->result_array();
      return $result;
   }    

   public function get_tt_menu($merchant_id, $period='All',$dotw=false){
      $result = $this->get_tt_menu_info($merchant_id, $period,$dotw);
      if($result){
         return $result;
      }
      return array();
   }

   function get_tt_menu_info($merchant_id,$period,$dotw){

      $taptuck_commission_percentage = 1.05;
      
      if($period == 'All' || $period == 'all'){
        
         $period = "'first_break','second_break','after_school'";
      }else{
         $period = "'$period'";
      }

      if(!$dotw){
         $dotw = "1,2,3,4,5,6,7";
      }

      $now = date("Y-m-d h:i:s");

         $query = $this->db->query("SELECT 
            m.id, m.merchant_id, m.label, m.description, m.price, p.period_id, m.category, m.valid_till 
            FROM 
            tt_menus m
            JOIN tt_menu_period_link p ON p.menu_id = m.id AND p.period_id IN ($period)
            JOIN tt_menu_day_link d ON d.menu_id = m.id AND d.day_id IN ($dotw)
            WHERE 
            m.merchant_id = ? 
            AND m.valid_till > ? 
            AND m.active = 'yes' 
            GROUP BY m.id
            ORDER BY m.category, m.label asc
            ", array($merchant_id, $now));


      if($query->num_rows() >= 1){
         $tt_menu = $query->result_array();
         foreach ($tt_menu as $key => $value) {
            $tt_menu[$key]['coin'] = $this->financial_model->tt_convert_category_to_coin($value['category']);
            $tt_menu[$key]['price'] = number_format(round(($value['price']*$taptuck_commission_percentage), 2), 2, '.', '');
         }
         return $tt_menu;
      }
      return false;
   }

   function deactivate_menu_item($menu_id){
      return($this->db->query("UPDATE tt_menus SET active = 'no' WHERE id = $menu_id"));
   }

   function activate_menu_item($menu_id){
      return($this->db->query("UPDATE tt_menus SET active = 'yes' WHERE id = $menu_id"));
   }

   function get_tt_menu_item($menu_id){
      
      $taptuck_commission_percentage = 1.05;

      $query = $this->db->query("SELECT id, merchant_id, label, description, price, period, category, valid_till FROM tt_menus WHERE id = ?", array($menu_id));
      if($query->num_rows() == 1){
         $menu_item = $query->row_array();
         $menu_item['price'] = round(($menu_item['price']*$taptuck_commission_percentage), 2);
         $menu_item['coin'] = $this->financial_model->tt_convert_category_to_coin($menu_item['category']);
         $menu_item['periods'] = $this->get_menu_item_periods($menu_item['id']);
         $menu_item['days'] = $this->get_menu_item_days($menu_item['id']);
         return $menu_item;
      }
      return false;
   }

    public function get_tt_menu_by_id($merchant_id){
   
      $query = $this->db->query("SELECT * FROM tt_menus as m 
                                 JOIN tt_orders as o ON m.id=o.menu_id 
                                 WHERE m.merchant_id IN ($merchant_id)");
      $result = $query->result_array();
    
      return $result;
   }

    public function get_menu_item_periods($menu_id){
   
      $query = $this->db->query("SELECT period_id FROM tt_menu_period_link WHERE menu_id = ?", array($menu_id));
      $result = $query->result_array();
      $return = array();

      foreach ($result as $key => $value) {
         $return[] = $value['period_id'];
      }
    
      return $return;
   }  

    public function get_menu_item_days($menu_id){
   
      $query = $this->db->query("SELECT day_id FROM tt_menu_day_link WHERE menu_id = ?", array($menu_id));
      $result = $query->result_array();
      $return = array();

      foreach ($result as $key => $value) {
         $return[] = $value['day_id'];
      }
    
      return $return;
   }    
}

