<?php

class Tt_merchant_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('financial_model');
      $this->load->model('taptuck_model');
      $this->load->model('tt_kid_model');
      $this->load->model('tt_menu_model');
   }

   public function get_all_tt_merchants(){
    
      $query = $this->db->query("SELECT m.*, u.username, u.name, m.name as merchant FROM tt_merchants as m, aauth_users as u WHERE m.user_id = u.id");
      $result = $query->result_array();
      return $result;
   }

  public function get_tt_merchants(){
    
      $query = $this->db->query("SELECT * FROM tt_merchants");
      $result = $query->result_array();
      return $result;
   }   

   public function get_tt_merchant($merchant_id, $menu=true){
    
      $result = $this->get_tt_merchant_info($merchant_id);
      if($result && $menu){
         $result['menu'] = $this->tt_menu_model->get_tt_menu($merchant_id);
      }
      return $result;
   }

   function get_tt_merchant_info($merchant_id){
      $query = $this->db->query("SELECT * FROM tt_merchants WHERE id = ?", array($merchant_id));
      if($query->num_rows() == 1){
         $tt_merchant = $query->row_array();
         return $tt_merchant;
      }
      return false;
   }

   public function get_tt_merchants_by_user_id($user_id){
    
      $query = $this->db->query("SELECT * FROM tt_merchants WHERE user_id='$user_id'");
      $result = $query->result_array();
      $data='';
      $data['merchant_id']='';
      $comma='';
      foreach ($result as $row) {
           
            $data['merchant_id'].=$comma.$row['id']; 
            $data['name']=$row['name'];
            $data['created_at'] =  $row['created_at'];
            $data['updated_at'] = $row['updated_at'];
      $comma=', ';
      }
      
       return $data;
   
   }  

   public function get_daily_sales_stats($date_from,$date_to,$merchant_id,$coin_price){

      if(!empty($date_from)){
         $where_date="AND SUBSTR(`o`.`createdate`,1,10)>='$date_from' AND SUBSTR(`o`.`createdate`,1,10)<='$date_to'";
      }else{
         $where_date='';
      }

      if(!empty($coin_price)){
         if($coin_price==15){
            $where_coin=" AND o.price >= '0' and o.price <'30'";
         }
         
         if($coin_price==30){
            $where_coin=" AND o.price >= '$coin_price' and o.price <'50'";
         }

         if($coin_price==50){
            $where_coin=" AND o.price >= '$coin_price'";
         }

         
      }else{
         $where_coin='';
      }

      $query = $this->db->query("SELECT m.price,count(o.menu_id) as order_count, SUBSTR(`o`.`createdate`,1,10), m.label
            FROM tt_orders as o 
            JOIN tt_menus m ON o.menu_id=m.id
            WHERE m.merchant_id='$merchant_id' 
            AND o.status != 14 $where_date $where_coin GROUP BY o.menu_id");
      $result = $query->result_array();
      
      return $result;
   }

   public function get_daily_sales($date_from,$date_to,$merchant_id,$coin_price){

      if(!empty($date_from)){
         $where_date="AND SUBSTR(o.createdate,1,10)>='$date_from' AND SUBSTR(o.createdate,1,10)<='$date_to'";
      }else{
         $where_date='';
      }

      if(!empty($coin_price)){
         if($coin_price==15){
            $where_coin=" AND o.price >= '0' and o.price <'30'";
         }
         
         if($coin_price==30){
            $where_coin=" AND o.price >= '$coin_price' and o.price <'50'";
         }

         if($coin_price==50){
            $where_coin=" AND o.price >= '$coin_price'";
         }

      }else{
         $where_coin='';
      }

      $sql="SELECT k.id as kid_id, m.description, o.id,o.menu_id, m.label,ROUND((o.price),2) as price,o.createdate, k.first_name,k.last_name,
            p.first_name as p_first_name,p.last_name as p_last_name,g.name as status
            FROM tt_orders as o 
            JOIN tt_menus m ON o.menu_id=m.id 
            JOIN tt_kids as k ON k.id=o.kid_id
            JOIN tt_parents as p ON p.id=k.parent_id
            JOIN gbl_statuses as g ON g.id=o.status
            WHERE m.merchant_id='$merchant_id' AND o.status != 14 $where_date $where_coin";

      $query = $this->db->query($sql);

      $result['sales']=$query->result_array();
      $result['query']=$sql;

      return $result;
   }

   public function get_daily_orders($date_from,$date_to,$merchant_id,$status=''){

      if(!empty($date_from)){
         $where_date="AND o.date>='$date_from' AND o.date<='$date_to'";
      }else{
         $where_date=" And o.date>='".date('Y-m-d')."'";
      }

      if(!empty($status)){
         $where_date .= "AND o.status = $status";
      }

      $sql="SELECT o.date, k.id as kid_id, m.description, o.id,o.menu_id, m.label,o.createdate, k.first_name,k.last_name,
            p.first_name as p_first_name,p.last_name as p_last_name,g.name as status
            FROM tt_orders as o 
            JOIN tt_menus m ON o.menu_id=m.id 
            JOIN tt_kids as k ON k.id=o.kid_id
            JOIN tt_parents as p ON p.id=k.parent_id
            JOIN gbl_statuses as g ON g.id=o.status
            WHERE m.merchant_id='$merchant_id' $where_date";

      $query=$this->db->query($sql);

      $result['sales']=$query->result_array();
      $result['query']=$sql;

      return $result;
   }


    public function get_daily_order_stats($date_from,$date_to,$merchant_id,$status=''){
    
      if(!empty($date_from)){
         $where_date="AND SUBSTR(`o`.`date`,1,10)>='$date_from' AND SUBSTR(`o`.`date`,1,10)<='$date_to'";
      }else{
         $where_date=" AND SUBSTR(date,1,10)>='".date('Y-m-d')."'";
      }

      if(!empty($status)){
         $where_date .= "AND o.status = $status";
      }

      $query=$this->db->query("SELECT COUNT(o.menu_id) as order_count, m.label as label 
            FROM tt_orders as o 
            JOIN tt_menus m ON o.menu_id=m.id 
            WHERE m.merchant_id='$merchant_id'  $where_date
            GROUP BY o.menu_id DESC LIMIT 31");

      if($query->num_rows() >= 1){
         return $query->result_array();
      }
      return false;
   }


   function get_transactions($merchant_id){
       $query=$this->db->query("SELECT *
            FROM tt_kids as k JOIN tt_parents as p ON k.parent_id=p.id JOIN aauth_users as a ON p.user_id=a.id JOIN wallet_transactions as w ON a.username=w.msisdn WHERE k.merchant_id='$merchant_id'");
      $result = $query->result_array();
   print_r($result);
      return $result;
   }


   public function get_coin_stat($date_from,$date_to,$merchant_id){

      if(!empty($date_from)){
         $where_date="AND SUBSTR(o.createdate,1,10)>='$date_from' AND SUBSTR(o.createdate,1,10)<='$date_to'";
      }else{
         $where_date='';
      }

      $sql="SELECT o.price, round(sum(o.price), 2) as total, o.id,o.menu_id, m.label,o.createdate, k.first_name,k.last_name,
            p.first_name as p_first_name,p.last_name as p_last_name,g.name as status, count(o.menu_id) as coin_count
            FROM tt_orders as o 
            JOIN tt_menus m ON o.menu_id=m.id 
            JOIN tt_kids as k ON k.id=o.kid_id
            JOIN tt_parents as p ON p.id=k.parent_id
            JOIN gbl_statuses as g ON g.id=o.status
            WHERE m.merchant_id='$merchant_id' AND o.status != 14 $where_date 
            GROUP BY  o.price in(o.price>0 and o.price<30), (o.price>=30 and o.price<50), o.price>=50
            -- o.price<=15,o.price in(o.price<50, o.price>30),o.price >=50
            ";

      $query = $this->db->query($sql);

      return $query->result_array();
   
   }

 
 function get_merchant_for_kid($kid_id){
      $query = $this->db->query("SELECT * 
                                 FROM tt_kids as k 
                                 JOIN tt_merchants as mc 
                                 ON mc.id=k.merchant_id 
                                 WHERE k.id='$kid_id'");

      return $query->row_array();
 }
    
}

