<?php

class Trader_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('financial_model');
      $this->load->model('airtime_model');
      $this->load->model('event_model');
      $this->load->model('task_model');
   }

   public function get_all_traders($region_id=""){
    if(!empty($region_id)){
      $where_region=" and t.region_id=$region_id";
    }else{
      $where_region="";
    }
      $query = $this->db->query("SELECT u.name, u.id as 'user_id', u.username, t.*, r.name as 'region' FROM traders t, aauth_users u, regions r WHERE t.id = u.user_link_id and t.region_id = r.id and u.default_usergroup = 19
         $where_region");
      $result = $query->result_array();
      return $result;
   }    
  public function get_all_supplier_traders(){
    
      $query = $this->db->query("SELECT * FROM traders");
      $result = $query->result_array();
      return $result;
   }   

   public function get_trader_basic($trader_id){
    
      $result = $this->get_trader_info_basic($trader_id);
      return $result;
   }

   function get_trader_info_basic($trader_id){
      $query = $this->db->query("SELECT * FROM traders WHERE id = ?", array($trader_id));
      if($query->num_rows() == 1){
         $trader = $query->row_array();
          $user = $this->get_user_from_trader_id($trader_id);
         if($trader['email'] == ''){
            $trader['email'] = $user['email'];
         }
         if($trader['cellphone'] == ''){
            $trader['cellphone'] = $user['cellphone'];
         }

         $trader['username'] = $user['username'];
         $trader['zone_id'] = 0;
         $trader['zone_name'] = 'Not Assigned';
         $trader['long'] = $trader['location_lat'];
         $trader['lat'] = $trader['location_long'];
         return $trader;
       }
       return false;
     }

   public function get_trader($trader_id){
    
      $result = $this->get_trader_info($trader_id);
      if($result){
         $result['balance'] = $this->financial_model->get_wallet_balance($result['username']);
      }

      return $result;
   }

   function get_trader_info($trader_id){
      $query = $this->db->query("SELECT * FROM traders WHERE id = ?", array($trader_id));
      if($query->num_rows() == 1){
         $trader = $query->row_array();
          $user = $this->get_user_from_trader_id($trader_id);
         if($trader['email'] == ''){
            $trader['email'] = $user['email'];
         }
         if($trader['cellphone'] == ''){
            $trader['cellphone'] = $user['cellphone'];
         }

         $trader['username'] = $user['username'];
         $trader['zone_id'] = 0;
         $trader['zone_name'] = 'Not Assigned';
         $trader['regions'] = array();
         $trader['stores'] = array();

       $region_query = $this->db->query("SELECT tz.region_id, z.name, z.id, 
        c.id as 'store_id', c.company_name, c.cellphone, u.username, c.location_lat, c.location_long, c.address, c.suburb 
        FROM tz_region_link tz 
        JOIN trading_zones z on z.id = tz.zone_id
        JOIN customers c on c.region_id = tz.region_id AND c.trader_id = z.trader_id
        JOIN aauth_users u on c.id = u.user_link_id
        WHERE z.trader_id = ?", array($trader_id));

        if($region_query->num_rows() >= 1){
          $this->load->model('task_model');
          $this->load->model('order_model');
          foreach ($region_query->result_array() as $key => $value) {
            $store = array();
            $trader['zone_id'] = $value['id'];
            $trader['zone_name'] = $value['name'];
            $trader['regions'][$value['region_id']] = $value['region_id'];
            if($value['company_name'] != NULL){
              $recent_orders = $this->order_model->getOrderCountByCusID($value['store_id'], 30);
              $store = array(
              'store_id'  =>  $value['store_id'],
              'company_name'  =>  $value['company_name'],
              'cellphone' =>  $value['cellphone'],
              'username' =>  $value['username'],
              'user_name' =>  $value['username'],
              'location_lat'  =>  $value['location_lat'],
              'location_long' =>  $value['location_long'],
              'address' =>  $value['address'],
              'suburb'  =>  $value['suburb'],
              'bc_tasks' => $this->task_model->getActiveTasksCount($value['store_id'], 'customer'),
              'recent_orders' => $recent_orders['count'],
              'last_order_date'  =>  $recent_orders['date']
              );
              $trader['stores'][] = $store;
            }
          }
       }
       return $trader;
      }
      return false;
   }

   function get_trader_stores($trader_id){

    $trader = false;

    $region_query = $this->db->query("SELECT tz.region_id, z.name, z.id, 
        c.id as 'store_id', c.company_name, c.cellphone, c.location_lat, c.location_long, c.address, c.suburb 
        FROM tz_region_link tz 
        JOIN trading_zones z on z.id = tz.zone_id
        LEFT JOIN customers c on c.region_id = tz.region_id AND c.trader_id = z.trader_id
        WHERE z.trader_id = ?", array($trader_id));

        if($region_query->num_rows() >= 1){
          $this->load->model('task_model');
          foreach ($region_query->result_array() as $key => $value) {
            $store = array();
            $trader['zone_id'] = $value['id'];
            $trader['zone_name'] = $value['name'];
            $trader['regions'][$value['region_id']] = $value['region_id'];
            if($value['company_name'] != NULL){
              $store = array(
              'store_id'  =>  $value['store_id'],
              'company_name'  =>  $value['company_name'],
              'cellphone' =>  $value['cellphone'],
              'location_lat'  =>  $value['location_lat'],
              'location_long' =>  $value['location_long'],
              'address' =>  $value['address'],
              'suburb'  =>  $value['suburb'],
              'bc_tasks' => $this->task_model->getActiveTasksCount($value['store_id'], 'customer')
              );
              $trader['stores'][] = $store;
            }
          }
       }
       return $trader;
   }

   function get_user_from_trader_id($trader_id){

      $query = $this->db->query("SELECT * FROM aauth_users WHERE user_link_id = ? AND (default_usergroup = 19 || default_usergroup = 1)", array($trader_id));
      return $query->row_array();

   }

   public function get_trader_from_user_id($user_id){

      $query = $this->db->query("SELECT user_link_id as 'trader_id' FROM aauth_users WHERE id = ?", array(trim($user_id)));
      $trader = $query->row_array();    
      return $this->get_trader($trader['trader_id']);
   }

   public function get_trader_from_user_username($username){

      $query = $this->db->query("SELECT user_link_id as 'trader_id' FROM aauth_users WHERE username = ?", array(trim($username)));
      $trader = $query->row_array();    
      return $this->get_trader($trader['trader_id']);
   }

   public function get_trader_from_order($order_id){

      $query = $this->db->query("SELECT trader_id FROM orders WHERE id = ?", array(trim($order_id)));
      $order =  $query->row_array();    
      return $this->get_trader($order['trader_id']);
   }

   public function get_trader_from_distributor_order($distributor_order_id){

      $query = $this->db->query("SELECT trader_id FROM distributor_orders WHERE id = ?", array(trim($distributor_order_id)));
      $order =  $query->row_array();    
      return $this->get_trader($order['trader_id']);
   }

   function get_trader_region($trader_id){

      $query = $this->db->query("SELECT a.region_id, b.name,trader_type FROM traders a, regions b WHERE a.region_id = b.id AND a.id = ?", array($trader_id));
      if($query->num_rows() == 1){
         $result = $query->row_array();
         return $result;
      }
      return false;

   }

   // Functions for Distributor profile update and their users

   function get_distributor_details($distributor_id)
   {
      $query = $this->db->select("company_name, contact_name, number, email, address, picture")
                  ->from("distributors")
                  ->where("id", $distributor_id)
                  ->get();
      $result = $query->row();
      return $result;
   }

   function update_distributor($distributor_id, $data) {
      $this->db->where("id", $distributor_id)->update("distributors", $data);
   }

   function get_user_profile($id)
   {
      $query = $this->db->select("name, email, cellphone")
                  ->from("aauth_users")
                  ->where("id", $id)
                  ->get();
      $result = $query->row();
      return $result;
   }

   function update_profile($id, $data) {
      $this->db->where("id", $id)->update("aauth_users", $data);
   }

   // Get Trader Details for location plotting

   function getTraderDetails()
   {
      $query = $this->db->select("c.id, c.store_picture, c.company_name, c.address, c.suburb, c.location_lat, c.location_long, r.name")
                  ->from("traders as c")
                  ->join("regions as r", "r.id = c.region_id")
                  ->where("c.location_lat !=", 0)
                  ->where("c.location_long !=", 0)
                  ->get();
      $result = $query->result_array();
      return $result;
   }

   function getTraderDetailsById($id)
   {
      $query = $this->db->select("c.*, r.name as region, p.name as province")
                  ->from("traders as c")
                  ->join("regions as r", "r.id = c.region_id")
                  ->join("provinces as p", "p.id = c.province")
                  ->where("c.id", $id)
                  ->get();
      $result = $query->row();
      return $result;
   }


   function getDistributorTraderDetails($values) //region id
   {
      $query = $this->db->select("c.id, c.store_picture, c.company_name, c.address, c.suburb, c.location_lat, c.location_long, r.name, dr.region_id, d.contact_name")
                  ->from("traders as c")
                  ->join("regions as r", "r.id = c.region_id")
                  ->join("dist_region_link as dr", "dr.region_id = c.region_id")
                  ->join("distributors as d", "d.id = dr.distributor_id")
                  ->where("c.location_lat !=", 0)
                  ->where("c.location_long !=", 0)
                  ->where_in("c.region_id", array($values))
                  ->get();
      $result = $query->result_array();
      return $result;
   }

   public function getDistributorRegion($distributor_id)
   {
      $query = $this->db->select("d.id, d.company_name, dr.region_id")
                  ->from("distributors as d")
                  ->join("dist_region_link as dr", "d.id = dr.distributor_id")
                  ->where("dr.distributor_id", $distributor_id)
                  ->get();
      $result = $query->row();
      return $result;
   }

   // Getting All Distributor regions
   public function getAllDistributorRegions($distributor_id)
   {
      $query = $this->db->select("region_id")
                  ->from("dist_region_link")
                  ->where("distributor_id", $distributor_id)
                  ->get();
      $result = $query->result_array();
      return $result;
   }

   public function get_company_name($trader_id,$distributor_id){
    
    $company_name='';
     $query = $this->db->query("SELECT u.company_name FROM `traders`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($trader_id));
      
     $result = $query->result_array();
      foreach ($result as $key => $item) {

        $company_name = $item['company_name'];

      }

      return $company_name;
   }
public function get_first_name($trader_id,$distributor_id){
    
    $company_name='';
     $query = $this->db->query("SELECT * FROM `traders`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($trader_id));
      
     $result = $query->result_array();
//print_r($query);
      foreach ($result as $key => $item) {

        $company_name = $item['first_name'];

      }

      return $company_name;
   }
public function get_last_name($trader_id,$distributor_id){
    
    $company_name='';
     $query = $this->db->query("SELECT * FROM `traders`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($trader_id));
      
     $result = $query->result_array();
     foreach ($result as $key => $item) {
        $company_name = $item['last_name'];

      }

      return $company_name;
   }

public function get_trader_type($trader_id,$distributor_id){
     $company_name='';
     $query = $this->db->query("SELECT * FROM `traders`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($trader_id));
     $result = $query->result_array();
     foreach ($result as $key => $item) {
        $company_name = $item['trader_type'];
      }
      return $company_name;
}
    
public function get_rewards($trader_id,$distributor_id){
     $company_name='';
     $query = $this->db->query("SELECT * FROM `traders`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($trader_id));   
     $result = $query->result_array();
      foreach ($result as $key => $item) {
          $company_name = $item['rewards'];
    }
      return $company_name;
}
    
public function get_store_picture($trader_id,$distributor_id){
     $company_name='';
     $query = $this->db->query("SELECT * FROM `traders`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($trader_id)); 
     $result = $query->result_array();
      foreach ($result as $key => $item) {
          $company_name = $item['store_picture'];
      }
      return $company_name;
}
    
public function get_callback_id($trader_id,$distributor_id){
    
     $company_name='';
     $query = $this->db->query("SELECT u.id FROM `traders`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($trader_id));
     $result = $query->result_array();

      foreach ($result as $key => $item) {
        $company_name = $item['id'];
      }
      return $company_name;
}
    
public function get_deleted_trader_user($primary_key){

    $delete = $this->db->query("DELETE FROM `aauth_users` WHERE user_link_id='$primary_key'");
    if($delete){
       return "true";  
    }else{
        return "false";
    }
   
   }


   function get_trader_data($region_id=""){
    
    if(!empty($region_id)){
      $traders = $this->get_all_traders($region_id);
    }else{
      $traders = $this->get_all_traders();
    }
    foreach ($traders as $key => $trader) {
      //deposit count and total
      $deposits = $this->financial_model->get_wallet_deposit_stats($trader['username']);
      $traders[$key]['deposits_count'] = $deposits['count'];
      $traders[$key]['deposits_total'] = $deposits['total'];

      //airtime stats
      $airtime = $this->airtime_model->get_user_purchase_stats($trader['user_id'], $trader['username']);
      $traders[$key]['airtime_count'] = $airtime['count'];
      $traders[$key]['airtime_total'] = $airtime['total'];
      $traders[$key]['airtime_unique_numbers'] = $airtime['unique_numbers'];
      $traders[$key]['airtime_self_sales'] = $airtime['self_sales'];

      //total stores who have logged in after being added.
      $store_rewards = $this->financial_model->get_store_rewards($trader['username']);
      $traders[$key]['total_stores'] = $store_rewards['count'];
      $traders[$key]['store_rewards_total'] = $store_rewards['total'];

      //last activity
      $last_activity = $this->event_model->get_last_activity($trader['user_id']);
      $traders[$key]['last_activity'] = $last_activity['action'];
      $traders[$key]['last_activity_date'] = $last_activity['date'];

      //completed tasks
      $traders[$key]['completed_tasks'] = $this->task_model->get_spark_store_completed_taks($trader['id']);

      //wallet balance
      $traders[$key]['wallet_balance'] = $this->financial_model->get_wallet_balance($trader['username']);

    }
    return $traders;
   }

   function get_traders_with_locations(){
      $query=$this->db->query("SELECT t.*, t.id 
                              FROM traders as t, 
                              aauth_users as u, 
                              location_log  as l
                              WHERE u.user_link_id = t.id 
                              And l.user_id=u.id
                              AND l.long !='0' and l.lat !='0' 
                              GROUP BY l.user_id ORDER BY l.createdate desc");

      return $query->result_array();

   }

   function get_trader_commisions($date_from, $date_to){
      $query=$this->db->query("SELECT SUM(ROUND(i.price*i.quantity, 2)) as 'total', 
                          SUM(ROUND((i.price*i.quantity)*0.05, 2)) as 'comm', 
                          CONCAT(t.first_name, ' ', t.last_name) as 'trader', 
                          t.cellphone
                          from order_items i,
                          orders o,
                          customers c,
                          traders t
                          where i.order_id = o.id
                          AND c.id = o.customer_id
                          AND c.trader_id = t.id
                          AND c.trader_id != 0 
                          AND c.trader_id != ''
                          AND o.createdate >= '$date_from' 
                          AND o.createdate <= '$date_to'
                          group by c.trader_id");

      return $query->result_array();

   }

    function get_traders($region_id=''){
    $where_region = "";
    if(!empty($region_id)){
      $where_region=" and t.region_id=$region_id";
    }
    $query=$this->db->query("SELECT t.*,u.id as 'user_id', t.id, t.first_name,t.last_name,t.id, p.name, r.name as region
                FROM 
                aauth_users as u, 
                traders as t, 
                provinces as p, 
                regions as r
                WHERE t.province=p.id 
                and r.id=t.region_id 
                AND u.user_link_id = t.id 
                AND u.default_usergroup = 19 
                $where_region");

    $traders = $query->result_array();
    foreach ($traders as $key => $value) {
      $user_id = $value['user_id'];
      $query=$this->db->query("SELECT `long`, `lat`, `createdate` 
                  FROM location_log 
                  WHERE user_id =  '$user_id'
                  AND `long` !='' and `lat` !='' ORDER BY createdate desc LIMIT 1");

      $res = $query->row_array();

      if($res){
        $traders[$key]['long'] = $res['long'];
        $traders[$key]['lat'] = $res['lat'];
        $traders[$key]['createdate'] = $res['createdate'];
      }
    }

    return $traders;
  }

}

