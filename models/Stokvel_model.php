<?php

class Stokvel_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('financial_model');
      $this->load->model('comms_model');
   }

   function create_customer($data, $stokvel_id){

      if(isset($data['rep_id'])){
        $data['trader_id'] = $data['rep_id'];
      }

      if(!isset($data['trader_id'])){
        $data['trader_id'] = 0;
      }
      
      if(!isset($data['country_id'])){
        $data['country_id'] = 197;
      }

      $prov_city = 'province';
      if(isset($data['city_id'])){
        $prov_city = 'city_id';
        $data['province'] = $data['city_id'];
      }

      $this->db->query("INSERT INTO customers 
        (customer_type,
        region_id, 
        $prov_city, 
        country_id, 
        createdate) VALUES (?,?,?,?,NOW())", 
        array($data['customer_type'], 
          $data['region_id'], 
          $data['province'], 
          $data['country_id'])
      );

      $customer_id = $this->db->insert_id();

      $this->add_stok_user_rel($data['user_id'], $stokvel_id, $data['customer_type']);

      return $customer_id;
   }

   function getStokvelDetails()
   {
      $query = $this->db->query("SELECT s.*,d.location_long, d.location_lat, d.picture,d.address FROM stokvels s LEFT JOIN distributors d on s.distributor_id = d.id");
      $result = $query->result_array();
      return $result;
   }

   function check_exist($cellphone){
      $query = $this->db->query("SELECT * FROM aauth_users WHERE cellphone ='$cellphone'");
      $result = $query->row_array();
      return $result;
   }

   function getPromotion_name($promotion_id){
      $query = $this->db->query("SELECT name FROM promotions WHERE id ='$promotion_id'");
      $result = $query->row_array();
      return $result;
   }

   function getStokvel_name($stokvel_id){
      $query = $this->db->query("SELECT name FROM stokvels WHERE id ='$stokvel_id'");
      $result = $query->row_array();
      return $result;
   }

   function get_stokvel_location($stokvel_id){
    $query = $this->db->query("SELECT c.distributor_id, c.*, p.name as province, r.name as region,d.*
                              FROM stokvels as c 
                              LEFT join dist_region_link as drl ON c.distributor_id=drl.distributor_id
                              LEFT join distributors as d ON drl.distributor_id=d.id
                              LEFT join regions as r ON drl.region_id=r.id
                              LEFT join provinces as p ON r.province_id=p.id  
                              WHERE c.id='$stokvel_id'");
    return $query->result_array();
   }

   function check_user_rel_exist($user_id, $stokvel_id, $customer_type){
      $query = $this->db->query("SELECT count(*) rws FROM user_stokvel_rel WHERE user_id ='$user_id' AND stokvel_id ='$stokvel_id' AND role_id ='$customer_type'");
      $result = $query->row_array();
      return $result;
   }

   function check_order_approved($basket_id, $user_id, $approved){
      $query = $this->db->query("SELECT id FROM basket_order_approvals WHERE user_id ='$user_id' AND basket_id ='$basket_id'");
      $result = $query->num_rows();
      return $result;
   }

   function add_stok_user_rel($user_id, $stokvel_id, $customer_type){
      $rel_exists = $this->check_user_rel_exist($user_id, $stokvel_id, $customer_type);
      
      if($rel_exists['rws'] > 0){

      }else{
        $this->db->query("INSERT INTO user_stokvel_rel 
        (user_id,
        stokvel_id, 
        role_id) VALUES (?,?,?)", 
        array($user_id, $stokvel_id, $customer_type)
      );
      }
      
   }


    function create_stokvel($data){

      $this->db->query("INSERT INTO stokvels 
        (name,
        distributor_id, 
        createdate) VALUES (?,?,NOW())", 
        array($data['stokvel_name'], $data['distributor_id'])
      );

      $stokvel_id = $this->db->insert_id();

      return $stokvel_id;
    }

    function create_member_customer($stokvel_id, $data){

      if(isset($data['rep_id'])){
        $data['trader_id'] = $data['rep_id'];
      }

      if(!isset($data['trader_id'])){
        $data['trader_id'] = 0;
      }

      if(!isset($data['country_id'])){
        $data['country_id'] = 197;
      }

      $prov_city = 'province';
      if(isset($data['city_id'])){
        $prov_city = 'city_id';
        $data['province'] = $data['city_id'];
      }

      $this->db->query("INSERT INTO customers 
        (stokvel_id, 
        region_id, 
        $prov_city, 
        country_id, 
        createdate) VALUES (?,?,?,?,NOW())", 
        array($stokvel_id, 
          $data['region_id'], 
          $data['province'], 
          $data['country_id'])
      );

      return $this->db->insert_id();

    }

    function remove_member($user_id, $stokvel_id){
      $this->db->query("DELETE from user_stokvel_rel WHERE id = $user_id and stokvel_id = $stokvel_id Limit 1");
    }

    function update_stokvel($stokvel_id, $data){

      $prov_city = 'province';
      if(isset($data['city_id'])){
        $prov_city = 'city_id';
        $data['province'] = $data['city_id'];
      }

      $this->db->query("UPDATE stokvels 
        SET name = ?,
        distributor_id = ? WHERE id = $stokvel_id", 
        array($data['stokvel_name'], $data['distributor_id'])
      );

      return $stokvel_id;
    }

    function change_user_stokvel_rel($user_id, $customer_type, $stokvel_id){

      $this->db->query("UPDATE user_stokvel_rel 
      SET role_id = ? WHERE user_id = ? AND stokvel_id = ?", 
      array($customer_type, $user_id, $stokvel_id));

    }

   public function get_all_customers(){
    
      $query = $this->db->query("SELECT * FROM customers WHERE company_name !='na na' ORDER BY createdate DESC");
      $result = $query->result_array();
      return $result;
   }

   public function get_stokvel_members($stokvel_id, $user_id=0){
    
      $query = $this->db->query("SELECT u.*, s.role_id, g.name as 'role_name' FROM aauth_users u, user_stokvel_rel s, customer_types g WHERE s.role_id = g.id AND s.stokvel_id = $stokvel_id AND s.user_id != $user_id AND s.user_id = u.id ORDER BY u.name DESC;");
      $result = $query->result_array();

      return $result;
   } 

   public function get_stokvel_members_count($stokvel_id){
    
      $query = $this->db->query("SELECT count(s.role_id) stokvel_members FROM aauth_users u, user_stokvel_rel s, customer_types g WHERE s.role_id = g.id 
AND s.stokvel_id = $stokvel_id AND s.user_id = u.id ORDER BY u.name DESC;");
      $result = $query->result_array();

      return $result;
   } 


   public function get_user_linked_stokvels($user_id){
    
      $query = $this->db->query("SELECT stokvel_id, role_id FROM user_stokvel_rel WHERE user_id = $user_id");
      $result = $query->result_array();

      return $result;
   }



  public function get_all_customers_info($date_from, $date_to, $region_id=''){

    if(!empty($date_from) && !empty($date_to)){
      $where_date=" and c.createdate>= '$date_from' and c.createdate<= '$date_to'";
    }else{
      $where_date='';
    }

    if(!empty($_POST['trader_id'])){
      $where_trader_id=" and c.trader_id='".$_POST['trader_id']."'";
    }else{
      $where_trader_id='';
    }

    if(!empty($_POST['region_id'])){
        $where_parent = " and (c.region_id='".$_POST['region_id']."' || r.parent_id='".$_POST['region_id']."')";
    }else{
        $where_parent='';
    }

    if(!empty($region_id)){
        $where_parent = " and (c.region_id='".$region_id."' || r.parent_id='".$region_id."')";
        
    }else{
        $where_parent='';
    }

    $query = $this->db->query("SELECT c.id, 
                              c.first_name,
                              c.trader_id,
                              c.last_name,
                              c.cellphone,
                              c.createdate,
                              c.address,
                              c.email,
                              c.company_name,
                              p.name as province,
                              r.name as region,
                              r.parent_id,
                              ct.name as customer_type,
                              t.first_name as trader_first_name,
                              t.last_name as trader_last_name,
                              (SELECT name FROM regions WHERE id = r.parent_id limit 1) as 'parent',
                              (SELECT name FROM aauth_users WHERE user_link_id = c.id  limit 1) as 'name'
                              FROM customers as c 
                              LEFT JOIN regions as r on r.id=c.region_id 
                              LEFT JOIN customer_types as ct on ct.id=c.customer_type 
                              LEFT JOIN provinces as p on p.id=r.province_id
                              LEFT JOIN traders as t on t.id=c.trader_id  
                              WHERE c.first_name != '' $where_parent $where_date $where_trader_id
                              ORDER BY c.createdate DESC");

      $result = $query->result_array();
      return $result;
  }    
  public function get_all_supplier_customers($supplier_id){
    
      $query = $this->db->query("SELECT * FROM customers as c 
        JOIN orders as o ON c.id = o.customer_id
        JOIN order_items as oi ON oi.order_id =o.id
        JOIN products as p ON oi.product_id = p.id WHERE p.supplier_id ='$supplier_id'");
      $result = $query->result_array();
      return $result;
   }   

   public function get_customer($customer_id){
    
      $result = $this->get_customer_info($customer_id);
      return $result;
   }

   function get_customer_info($customer_id){
      $query = $this->db->query("SELECT c.*, u.id as user_id 
        FROM customers c, aauth_users u 
        WHERE u.user_link_id = c.id AND u.default_usergroup IN (33,34) AND c.id = ?", array($customer_id));
      if($query->num_rows() == 1){
         $customer = $query->row_array();
          $customer['stokvels'] = $this->get_user_stokvels($customer['user_id']);
         return $customer;
      }
      return false;
   }

   function get_user_stokvels($user_id){
    $query = $this->db->query("SELECT r.stokvel_id, s.name as 'stokvel_name', r.role_id, ct.name as 'role' FROM user_stokvel_rel r, stokvels s, customer_types ct WHERE r.stokvel_id = s.id AND r.role_id = ct.id AND r.user_id = ?", array($user_id));

    $stokvels = $query->result_array();

    foreach ($stokvels as $key => $stokvel) {
      $stokvels[$key]['sms_remaining'] = count($this->stokvel_model->get_message_count($stokvel['stokvel_id'], $user_id));
    }

    return $stokvels;

   }

   function get_stokvel_seniors($user_id,$stokvel_id){
    
    $query = $this->db->query("SELECT r.user_id, c.name role FROM user_stokvel_rel r 
      JOIN customer_types c ON r.role_id = c.id
      WHERE 
      r.stokvel_id = $stokvel_id AND r.user_id = ? AND r.role_id < 17", array($user_id));

    $stokvels = $query->result_array();

    return $stokvels;

   }

   function get_basket_approvals($basket_id){

  $query = $this->db->query("SELECT r.user_id, c.name as 'role', a.id as 'approved' 
    FROM user_stokvel_rel as r 
    JOIN basket_orders as b ON r.stokvel_id = b.stokvel_id
    JOIN customer_types as c ON r.role_id = c.id
    LEFT JOIN basket_order_approvals as a ON r.user_id = a.user_id
    WHERE b.id = ? AND r.role_id in (14,15,16)", array($basket_id));

    $stokvels = $query->result_array();

    return $stokvels;

   }

  function get_basket_order_details($order_id){
    $query = $this->db->query("SELECT * FROM basket_orders WHERE id=?",array($order_id));
    return $query->row_array();
  }

  function get_approved_stokvel_seniors($user_id,$stokvel_id){
      $query = $this->db->query("SELECT count(r.user_id) unapproved FROM user_stokvel_rel r 
  LEFT JOIN customer_types c ON r.role_id = c.id
  WHERE r.stokvel_id = $stokvel_id AND r.user_id = ? AND r.role_id < 17 AND r.user_id NOT IN (select user_id FROM basket_order_approvals)", array($user_id));

      $stokvels = $query->result_array();

      return $stokvels;

     }



   function get_stokvel_approvals($user_id,$basket_id){
    $query = $this->db->query("SELECT * FROM basket_order_approvals where user_id = $user_id  AND basket_id = $basket_id");

    $stokvels = $query->result_array();

    return $stokvels;

   }

   function get_stokvel_info($stokvel_id){

      $query = $this->db->query("SELECT 
        s.id, 
        s.name, 
        d.id as 'distributor_id', 
        d.company_name as 'distributor', 
        d.picture as 'distributor_logo' 
        FROM stokvels as s, 
        distributors as d 
        WHERE s.distributor_id = d.id 
        AND s.id = ?", array($stokvel_id));

      if($query->num_rows() == 1){
         $stokvel = $query->row_array();
         return $stokvel;
      }
      return false;
   }

   function get_user_from_customer_id($customer_id){

      $query = $this->db->query("SELECT * FROM aauth_users WHERE user_link_id = ? AND (default_usergroup = 8 || default_usergroup = 1)", array($customer_id));
      if($query->num_rows() >= 1){
         return $query->row_array();
      }
      return false;

   }

   public function get_reps_customers($rep_user_id){
    
      $query = $this->db->query("SELECT cus.* FROM 
         customers as cus,
         aauth_users as cus_usr,
         aauth_users as rep
         WHERE 
         rep.id = ?
         AND cus.id = cus_usr.user_link_id
         AND rep.id = cus_usr.parent_id
         ", array($rep_user_id));
      $result = $query->result_array();
      return $result;
   }

   public function get_customer_from_user_username($username){

      $query = $this->db->query("SELECT user_link_id as 'customer_id' FROM aauth_users WHERE username = ?", array(trim($username)));
      $customer = $query->row_array();    
      return $this->get_customer($customer['customer_id']);
   }

   public function get_customer_from_user_id($user_id){

      $query = $this->db->query("SELECT user_link_id as 'customer_id' FROM aauth_users WHERE id = ?", array(trim($user_id)));
      $customer = $query->row_array();    
      return $this->get_customer($customer['customer_id']);
   }

   public function get_customer_from_order($order_id){

      $query = $this->db->query("SELECT customer_id FROM orders WHERE id = ?", array(trim($order_id)));
      $order =  $query->row_array();    
      return $this->get_customer($order['customer_id']);
   }

   public function get_customer_from_distributor_order($distributor_order_id){

      $query = $this->db->query("SELECT customer_id FROM distributor_orders WHERE id = ?", array(trim($distributor_order_id)));
      $order =  $query->row_array();    
      return $this->get_customer($order['customer_id']);
   }

   function get_customer_region($customer_id){

      $query = $this->db->query("SELECT a.region_id, b.name, a.customer_type FROM customers a, regions b WHERE a.region_id = b.id AND a.id = ?", array($customer_id));
      if($query->num_rows() == 1){
         $result = $query->row_array();
         return $result;
      }
      return false;

   }
   function get_customer_province($customer_id){

      $query = $this->db->query("SELECT a.province, b.name FROM customers a, provinces b WHERE a.province = b.id AND a.id = ?", array($customer_id));
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

   // Get Customer Details for location plotting

   function getCustomerDetails()
   {
      $query = $this->db->select("c.*,c.id, c.store_picture, c.company_name, c.address, c.suburb, c.location_lat, c.location_long, r.name")
                  ->from("customers as c")
                  ->join("regions as r", "r.id = c.region_id")
                  ->join("aauth_users as a", "a.user_link_id = c.id","left")
                  ->where("c.location_lat !=", 0)
                  ->where("c.location_long !=", 0)
                  ->get();
      $result = $query->result_array();
      return $result;
   }

   function getCustomerDetailsById($id)
   {
      $query = $this->db->select("c.company_name, c.first_name, c.last_name, c.cellphone, c.email, c.address, c.suburb, c.location_lat, c.location_long, r.name")
                  ->from("customers as c")
                  ->join("regions as r", "r.id = c.region_id")
                  ->where("c.id", $id)
                  ->get();
      $result = $query->row();
      return $result;
   }


   function getDistributorCustomerDetails($distributor_id) //region id
   {

      $regions = $this->getAllDistributorRegions($distributor_id);

      $region_id = '';
      $comma = '';
      foreach ($regions as $key => $r) {
          $region_id .= $comma."'".humanize($r['region_id'])."'";
          $comma = ',';
      }

      $query=$this->db->query("SELECT * FROM customers Where region_id IN ($region_id)");           
      $result = $query->result_array();
    
      return $result;
   }
 function getDistributorCustomerLocations($distributor_id) //region id
   {

      if(isset($_GET['customer_id'])){
        $where_id=" and id='".$_GET['customer_id']."'";
      }else{
        $where_id='';
      }

      $regions = $this->getAllDistributorRegions($distributor_id);

      $region_id = '';
      $comma = '';
      foreach ($regions as $key => $r) {
          $region_id .= $comma."'".humanize($r['region_id'])."'";
          $comma = ',';
      }

      $query=$this->db->query("SELECT * 
                              FROM customers 
                              Where region_id IN ($region_id) 
                              and location_long !='0' 
                              and location_lat !='0' $where_id");           
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

   public function get_company_name($customer_id,$distributor_id){
    
    $company_name='';
     $query = $this->db->query("SELECT u.company_name FROM `customers`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($customer_id));
      
     $result = $query->result_array();
      foreach ($result as $key => $item) {

        $company_name = $item['company_name'];

      }

      return $company_name;
   }
public function get_first_name($customer_id,$distributor_id){
    
    $company_name='';
     $query = $this->db->query("SELECT * FROM `customers`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($customer_id));
      
     $result = $query->result_array();
//print_r($query);
      foreach ($result as $key => $item) {

        $company_name = $item['first_name'];

      }

      return $company_name;
   }
public function get_last_name($customer_id,$distributor_id){
    
    $company_name='';
     $query = $this->db->query("SELECT * FROM `customers`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($customer_id));
      
     $result = $query->result_array();
     foreach ($result as $key => $item) {
        $company_name = $item['last_name'];

      }

      return $company_name;
   }

public function get_customer_type($customer_id,$distributor_id){
     $company_name='';
     $query = $this->db->query("SELECT * FROM `customers`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($customer_id));
     $result = $query->result_array();
     foreach ($result as $key => $item) {
        $company_name = $item['customer_type'];
      }
      return $company_name;
}
    
public function get_rewards($customer_id,$distributor_id){
     $company_name='';
     $query = $this->db->query("SELECT * FROM `customers`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($customer_id));   
     $result = $query->result_array();
      foreach ($result as $key => $item) {
          $company_name = $item['rewards'];
    }
      return $company_name;
}
    
public function get_store_picture($customer_id,$distributor_id){
     $company_name='';
     $query = $this->db->query("SELECT * FROM `customers`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($customer_id)); 
     $result = $query->result_array();
      foreach ($result as $key => $item) {
          $company_name = $item['store_picture'];
      }
      return $company_name;
}
    
public function get_callback_id($customer_id,$distributor_id){
    
     $company_name='';
     $query = $this->db->query("SELECT u.id FROM `customers`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($customer_id));
     $result = $query->result_array();

      foreach ($result as $key => $item) {
        $company_name = $item['id'];
      }
      return $company_name;
}
    
public function get_deleted_customer_user($primary_key){

    //$delete = $this->db->query("DELETE FROM `aauth_users` WHERE user_link_id='$primary_key'");
    if($delete){
       return "true";  
    }else{
        return "false";
    }
   
}

public function insert_pending_registration($post_array){

  $this->db->INSERT('pending_registration',$post_array);
}

function get_trader_customer($trader_id){
   $query = $this->db->query("SELECT * FROM `customers`  WHERE trader_id='$trader_id'");
   $result = $query->result_array();
   return $result;
}


function get_storeowner_customer($user_id,$user_link_id){
   $query = $this->db->query(
    "SELECT c.*,a.username,g.name,a.default_usergroup FROM 
    `customers` as `c`,
    `aauth_users` as `a`,  
    `aauth_groups` as `g`  
    WHERE `a`.`id`='$user_id' 
    AND `c`.`id`='$user_link_id' 
    AND `a`.`default_usergroup`=`g`.`id`"
    );
   $result = $query->row_array();
   return $result;
}

function get_insurance_reps(){
  $query = $this->db->query(
    "SELECT c.id,c.location_lat,
    c.location_long,
    c.store_picture,
    c.company_name,  
    c.address,
    a.name,
    c.suburb
    FROM ins_m_applications as ins, 
    aauth_users as a, 
    customers as c
    WHERE ins.sold_by=a.id
    AND c.id=a.user_link_id"
    );

   $result = $query->result_array();
   return $result;
}

function getNumberOfDistributors($date_from,$date_to){
   
      if(!empty($date_to)){
        $where_date="WHERE createdate>='$date_from' and createdate<='$date_to'";
      }else{
         $where_date='';
      }

      $sql="SELECT * FROM distributors $where_date";

      $query = $this->db->query($sql);

      return $query->num_rows();
    
   }
function getNumberOfShops($date_from,$date_to){
     
      if(!empty($date_to)){
        $where_date="and createdate>='$date_from' and createdate<='$date_to'";
      }else{
         $where_date='';
      }

      $query = $this->db->query("SELECT * FROM customers as c 
                                JOIN customer_types as ty
                                ON ty.id=c.customer_type 
                                WHERE c.customer_type IN('1') $where_date");
      return $query->num_rows();
  
   }

 public function getNumberOfLogonShops(){
      $date_from = $this->input->post('date_from');
      $date_to = $this->input->post('date_to');
      $where_date='';
      if(!empty($date_to)){
        $where_date="and c.createdate>='$date_from' and c.createdate<='$date_to'";
      }
      $query = $this->db->query("SELECT count(DISTINCT c.id) as total FROM 
        customers as c 
        JOIN customer_types as ty ON ty.id=c.customer_type
        JOIN aauth_users as a ON a.user_link_id=c.id AND a.default_usergroup = 8
        JOIN event_log as ev ON ev.user_id=a.id 
        WHERE c.customer_type IN ('1') $where_date");

      $result = $query->row_array();
      return $result;
   }

 public function getNumberOfActiveShops(){
      $date_from = $this->input->post('date_from');
      $date_to = $this->input->post('date_to');
      $where_date='';
      if(!empty($date_to)){
        $where_date="and c.createdate>='$date_from' and c.createdate<='$date_to'";
      }
      $query = $this->db->query("SELECT count(DISTINCT c.id) as total FROM 
        customers as c 
        JOIN customer_types as ty ON ty.id=c.customer_type
        JOIN aauth_users as a ON a.user_link_id=c.id AND a.default_usergroup = 8
        JOIN event_log as ev ON ev.user_id=a.id AND ev.action IN ('brand_connect','airtime_purchased','order_placed')
        WHERE c.customer_type IN ('1') $where_date");

      $result = $query->row_array();
      return $result;
   }

 public function getNumberOfActiveSparks(){
      $date_from = $this->input->post('date_from');
      $date_to = $this->input->post('date_to');
      $where_date='';
      if(!empty($date_to)){
        $where_date="and ev.createdate>='$date_from' and ev.createdate<='$date_to'";
      }
      $query = $this->db->query("SELECT count(DISTINCT c.trader_id) as total 
                                FROM customers as c 
                                JOIN aauth_users as a ON a.user_link_id=c.id AND a.default_usergroup = 8
                                JOIN traders as t ON t.id=c.trader_id 
                                JOIN event_log as ev ON ev.user_id=a.id AND ev.action IN ('brand_connect','airtime_purchased','order_placed')
                                WHERE c.trader_id > 0 $where_date");

      $result = $query->row_array();
      return $result;
   }

 public function getNumberOfLoggedSparks(){
      $date_from = $this->input->post('date_from');
      $date_to = $this->input->post('date_to');
      $where_date='';
      if(!empty($date_to)){
        $where_date="and t.createdate>='$date_from' and t.createdate<='$date_to'";
      }
      $query = $this->db->query("SELECT count(DISTINCT c.trader_id) as total 
                                FROM customers as c 
                                JOIN traders as t ON t.id=c.trader_id 
                                JOIN aauth_users as a ON a.user_link_id=t.id AND a.default_usergroup = 19
                                JOIN event_log as ev ON ev.user_id=a.id 
                                WHERE c.trader_id > 0 $where_date");

      $result = $query->row_array();
      return $result;
   }

    public function get_suppliers(){
        
          $query = $this->db->query("SELECT s.company_name as name, s.* FROM suppliers as s WHERE 1");

          $result = $query->result_array();
          return $result;
       }

    public function get_supplier_number_customers($supplier_id){
         $distributor_id = $this->input->post('distributor_id');
        if(!empty($distributor_id)){
          $where_dist="and do.distributor_id='$distributor_id'";
        }else{
          $where_dist="";
        }
        
        $date_from = $this->session->userdata('dashboard_date_from');
        $date_to = $this->session->userdata('dashboard_date_to');
        $query=$this->db->query("
          SELECT * 
          FROM orders as o 
          JOIN order_items as oi ON o.id=oi.order_id
          JOIN products as p ON p.id=oi.product_id
          JOIN distributor_orders as do ON do.order_id=o.id
          WHERE status IN('Approved','Order Placed') and p.supplier_id = '$supplier_id'
          and o.createdate>='$date_from' and o.createdate<='$date_to' $where_dist GROUP BY o.customer_id");

        return $query->num_rows();

       }  

   function getNumberOfRegisteredSparks(){
        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');
        $where_date='';
        if(!empty($date_to)){
          $where_date="Where createdate>='$date_from' and createdate<='$date_to'";
        }
        $query=$this->db->query("SELECT * FROM traders  $where_date");
        return $query->num_rows();

   }

   public function get_supplier_by_id($supplier_id){
      
        $query = $this->db->query("SELECT * FROM suppliers WHERE id='$supplier_id'");

        $result = $query->row_array();
        return $result;
     }

  public function get_customer_by_region($region_id){

        if(!empty($region_id)){
          $where_region="  t.id='$region_id'";
        }else{
          $where_region='';
        }
        $query = $this->db->query("SELECT c.id, c.*, p.name as province, r.name as region, c.createdate
          FROM customers as c 
          LEFT join regions as r ON c.region_id=r.id
          LEFT join provinces as p ON c.province=p.id 
          LEFT join traders as t ON c.trader_id=t.id
          WHERE $where_region");

        return $query->result_array();
        
     }

    function get_province($province_id){

       $query = $this->db->query("SELECT * FROM provinces WHERE id='$province_id'");
        return $query->row_array();

    }

    function get_distributors(){
      $query = $this->db->query("SELECT d.*, d.company_name as name, a.cellphone FROM distributors d, aauth_users a WHERE  d.id=a.user_link_id and a.default_usergroup=11");
        return $query->result_array();

    }

  function get_category_list($parent_id){
        
        if($parent_id == 0){
            $sql = "SELECT cat1.id, cat1.icon, cat1.name, cat1.description, cat1.parent_id
                FROM `products` as p 
                JOIN categories cat ON cat.id = p.category_id
                JOIN categories cat1 ON cat1.id = cat.parent_id AND cat1.parent_id = $parent_id
                GROUP BY cat1.id
                ORDER BY cat1.name ASC";
        }else{
            $sql = "SELECT cat.id, cat.icon, cat.name, cat.description, cat.parent_id
                FROM `products` as p 
                JOIN categories cat ON cat.id = p.category_id AND cat.parent_id = $parent_id
                GROUP BY cat.id
                ORDER BY cat.name ASC";
        }

      $q_res = $this->db->query($sql);
      $categories = $q_res->result_array();

    return $categories;
 }

     function get_category_products($category_id){

/*     $sql = "SELECT p.id, p.stock_code, p.barcode, p.featured, p.name, (SELECT image FROM product_images pi WHERE pi.product_id = p.id limit 1) as 'picture', p.pack_size, p.units, b.name as 'brand', b.tier
              FROM `products` as p 
              JOIN brands as b ON p.brand_id = b.id
              WHERE
              p.category_id = $category_id
              order by featured desc, p.name asc";*/

     $sql = "SELECT p.id, p.stock_code, p.barcode, p.featured, p.name, p.picture, p.pack_size, p.units, b.name as 'brand', b.tier
              FROM `products` as p 
              JOIN brands as b ON p.brand_id = b.id
              WHERE
              p.category_id = $category_id
              order by featured desc, p.name asc";

      $query =  $this->db->query($sql);

      $products = $query->result_array();

      $clean_products = array();

      if($products){
        foreach ($products as $key => $value) {
          if($value['picture'] == '' || empty($value['picture'])){
            $value['picture'] = '4e887-banner-1.png';
          }
          $clean_products[] = $value;
        }
      }
 
     return $clean_products;
    }

     function get_product($product_id){

     $sql = "SELECT p.id, p.stock_code, p.barcode, p.featured, p.name, p.pack_size, p.units, b.name as 'brand', b.tier, p.description, p.nutritional_info, p.directions_warnings
              FROM `products` as p 
              JOIN brands as b ON p.brand_id = b.id
              WHERE
              p.id = $product_id ORDER BY p.id DESC";

      $query =  $this->db->query($sql);

      $product = $query->row_array();
      $product['pictures'] = $this->get_product_images($product_id);

      if(count($product['pictures']) == 0 || empty($product['pictures'])){
        $product['pictures'] = array();
        $product['pictures'][] = array('picture' => '4e887-banner-1.png');
      }

     return $product;
    }


    function get_promotion($promotion_id){

     $sql = "SELECT * FROM promotions where id = $promotion_id";

      $query =  $this->db->query($sql);

      $promotion = $query->row_array();
      $promotion['pictures'] = $this->get_promotion_images($promotion_id);

      if(count($promotion['pictures']) == 0 || empty($promotion['pictures'])){
        $promotion['pictures'] = array();
        $promotion['pictures'][] = array('picture' => '4e887-banner-1.png');
      }

     return $promotion;
    }

    function get_product_images($product_id){

      $sql = "SELECT image as 'picture', id FROM product_images WHERE product_id = $product_id";
      $query =  $this->db->query($sql);
      $images = $query->result_array();
      return $images;
    }


    function get_promotion_images($promotion_id){

      $sql = "SELECT image as 'picture', id FROM promotion_images WHERE promotion_id = $promotion_id";
      $query =  $this->db->query($sql);
      $images = $query->result_array();
      return $images;
    }


    function get_store_order_items($store_id, $pure_item_list=false, $order_id=''){
      if(!empty($order_id)){
        $where_order_id=" and i.order_id=$order_id";
      }else{
        $where_order_id = "";
      }

      $query = $this->db->query("SELECT p.name, i.* FROM basket_order_items i LEFT JOIN products p ON p.id=i.product_id WHERE i.distributor_id=? $where_order_id", array($store_id));
      $items = $query->result_array();
      $data = [];
      foreach ($items as $key => $value) {
        $data['name'][$key] = $value['name'];
        $data['price'][$key] = $value['price'];
        $data['quantity'][$key] = $value['quantity'];
        $data['basket_item_id'][$key] = $value['id'];
        $data['order_id'][$key] = $value['order_id'];
        $data['distributor_id'][$key] = $value['distributor_id'];
        $data['product_id'][$key] = $value['product_id'];

      }

      if($pure_item_list){
        return $items;
      }else{
        return $data;
      }


    }

    function update_price($data){
        $query = $this->db->query("UPDATE basket_order_items SET price=? WHERE id=?", array($data['price'], $data['id']));
    }

    function complete_basket_quote($data){

        $this->db->where("id", $data['order_id']);
        $this->db->update('basket_orders', array('status_id' => 20));
        $user_info = $this->aauth->get_user();
        $data['items']=$this->get_basket_order_items($data['order_id']);
        $distributor_info = $this->get_distributor($user_info->user_link_id);
        $data['company_name'] =$distributor_info['company_name'];
        $email = $user_info->email;
        $this->comms_library->queue_comm_group($user_info->id, 'order_quoted', $data);//Queueing Comms 
      }

    function get_distributor($distributor_id){
      $query = $this->db->query("SELECT * FROM distributors where id =?",array($distributor_id));
      return $query->row_array();
    }

    function get_basket_order_items($order_id){
      $query = $this->db->query("SELECT * FROM basket_order_items as oi JOIN products as p ON p.id=oi.product_id where oi.order_id=?", array($order_id));
      return $query->result_array();
    }

    function mark_as_approved($order_id){
      $query = $this->db->query("UPDATE basket_orders SET status_id=? WHERE id =?",array(8, $order_id));
    }

    function get_product_info($product_id){
        $sql = "SELECT p.id, p.stock_code, p.barcode, p.featured, p.name, p.pack_size, p.units, b.name as 'brand', b.tier, p.description, p.nutritional_info, p.directions_warnings
              FROM `products` as p 
              LEFT JOIN brands as b ON p.brand_id = b.id
              WHERE
              p.id = $product_id";

      $query =  $this->db->query($sql);

      return $query->row_array();
    }

    function get_promotion_info($promotion_id){
        $sql = "SELECT * FROM `promotions` 
              WHERE
              id = $promotion_id";

      $query =  $this->db->query($sql);

      return $query->row_array();
    }

    function get_all_promotion_info($promotion_id){
        $sql = "SELECT p.*,pro.name as product_name,d.company_name FROM promotions p
LEFT JOIN distributors d on p.distributor_id = d.id
LEFT JOIN products pro on p.product_id = pro.id
WHERE p.id = $promotion_id";

      $query =  $this->db->query($sql);

      return $query->row_array();
    }

    function get_all_product_info($product_id){
        $sql = "SELECT p.*,s.company_name as supplier_name,c.name as category_name,b.name as brand_name FROM products p
LEFT JOIN categories c on p.category_id = c.id
LEFT JOIN brands b on p.brand_id = b.id
LEFT JOIN suppliers s on p.supplier_id = s.id
WHERE p.id = $product_id";

      $query =  $this->db->query($sql);

      return $query->row_array();
    }

      function upload_image($data){
         
              $number_of_files = sizeof($_FILES['uploadedimages']['tmp_name']);
              $files = $_FILES['uploadedimages'];
              $errors = array();
           
              for($i=0;$i<$number_of_files;$i++)
              {
                if($_FILES['uploadedimages']['error'][$i] != 0) $errors[$i][] = 'Couldn\'t upload file '.$_FILES['uploadedimages']['name'][$i];
              }
              if(sizeof($errors)==0)
              {
                $this->load->library('upload');
                $config['upload_path'] = FCPATH . 'images/';
                $config['allowed_types'] = '*';
                $config['encrypt_name'] = TRUE;
                for ($i = 0; $i < $number_of_files; $i++) {
                  $_FILES['uploadedimage']['name'] = $files['name'][$i];
                  $_FILES['uploadedimage']['type'] = $files['type'][$i];
                  $_FILES['uploadedimage']['tmp_name'] = $files['tmp_name'][$i];
                  $_FILES['uploadedimage']['error'] = $files['error'][$i];
                  $_FILES['uploadedimage']['size'] = $files['size'][$i];
                  
                  $this->upload->initialize($config);
                  if ($this->upload->do_upload('uploadedimage'))
                  {
                    $data['uploads'][$i] = $this->upload->data();
                    $return[$i] = array('message'=>$data['uploads'][$i]['file_name']." has been succesfully uploaded");
                    $this->save_photo($data['uploads'][$i]['file_name'], $data['product_id']);
                  }
                  else
                  {
                    $data['upload_errors'][$i] = $this->upload->display_errors();
                  }
                }
              }
              else
              {
                return $errors;
              }
              return $return;
            
              
       }


       function upload_promotion_image($data){
         
              $number_of_files = sizeof($_FILES['uploadedimages']['tmp_name']);
              $files = $_FILES['uploadedimages'];
              $errors = array();
           
              for($i=0;$i<$number_of_files;$i++)
              {
                if($_FILES['uploadedimages']['error'][$i] != 0) $errors[$i][] = 'Couldn\'t upload file '.$_FILES['uploadedimages']['name'][$i];
              }
              if(sizeof($errors)==0)
              {
                $this->load->library('upload');
                $config['upload_path'] = FCPATH . 'images/';
                $config['allowed_types'] = '*';
                $config['encrypt_name'] = TRUE;
                for ($i = 0; $i < $number_of_files; $i++) {
                  $_FILES['uploadedimage']['name'] = $files['name'][$i];
                  $_FILES['uploadedimage']['type'] = $files['type'][$i];
                  $_FILES['uploadedimage']['tmp_name'] = $files['tmp_name'][$i];
                  $_FILES['uploadedimage']['error'] = $files['error'][$i];
                  $_FILES['uploadedimage']['size'] = $files['size'][$i];
                  
                  $this->upload->initialize($config);
                  if ($this->upload->do_upload('uploadedimage'))
                  {
                    $data['uploads'][$i] = $this->upload->data();
                    $return[$i] = array('message'=>$data['uploads'][$i]['file_name']." has been succesfully uploaded");
                    $this->save_promotion_photo($data['uploads'][$i]['file_name'], $data['promotion_id']);
                  }
                  else
                  {
                    $data['upload_errors'][$i] = $this->upload->display_errors();
                  }
                }
              }
              else
              {
                return $errors;
              }
              return $return;
            
              
       }

       function save_photo($filename, $product_id){
           $this->db->query("INSERT INTO product_images (image, product_id) VALUES(?,?)", array($filename, $product_id));
       }

       function save_promotion_photo($filename, $promotion_id){
           $this->db->query("INSERT INTO promotion_images (image, promotion_id) VALUES(?,?)", array($filename, $promotion_id));
       }

       function approve_basket_order($basket_id, $user_id){
          $query = $this->db->query("SELECT id FROM basket_order_approvals WHERE basket_id = ? AND  user_id = ?", 
            array($basket_id, $user_id));
          if($query->num_rows() == 0){
           $this->db->query("INSERT INTO basket_order_approvals (basket_id, user_id) VALUES(?,?)", 
            array($basket_id, $user_id));
           }
          return true;        
      
       }

       function remove_picture($data){
           $this->db->query("DELETE FROM product_images WHERE id=?", array($data['picture_id']));
           unlink(FCPATH.$data['path']."/".$data['picture']);
       }

       function remove_prom_picture($data){
           $this->db->query("DELETE FROM promotion_images WHERE id=?", array($data['picture_id']));
           unlink(FCPATH.$data['path']."/".$data['picture']);
       }

       function get_stokvels(){
          $query=$this->db->query("SELECT * FROM stokvels WHERE 1");
          return $query->result_array();
       }

       function get_recipies(){
          $query=$this->db->query("SELECT * FROM recipies WHERE 1");
          return $query->result_array();
       }

       function get_recipe($recipe_id){
          $query=$this->db->query("SELECT * FROM recipies WHERE id=?", array($recipe_id));
          return $query->row_array();
       }

       function get_better_stokvels(){
          $query=$this->db->query("SELECT * FROM running_better_stokvel WHERE 1");
          return $query->result_array();
       }

       function get_better_stokvel($better_stokvel_id){
          $query=$this->db->query("SELECT * FROM running_better_stokvel WHERE id=?", array($better_stokvel_id));
          return $query->row_array();
       }

       function get_magazines(){
          $query=$this->db->query("SELECT * FROM magazine WHERE 1");
          return $query->result_array();
       }

       function get_magazine($magazine_id){
          $query=$this->db->query("SELECT * FROM magazine WHERE id=?", array($magazine_id));
          return $query->row_array();
       }

       function get_free_contents(){
          $query=$this->db->query("SELECT * FROM free_content WHERE 1");
          return $query->result_array();
       }

       function get_free_content($free_content_id){
          $query=$this->db->query("SELECT * FROM free_content WHERE id=?", array($free_content_id));
          return $query->row_array();
       }

      function send_comms($stokvel_id, $post_array, $user_id){
          $this->load->model("stokvel_model");
          $users = $this->get_stokvel_members($stokvel_id);
          foreach ($users as $key => $value) {
              $json_data = array("message" => $post_array['message']);
              $this->comms_library->queue_comm($value['id'], $post_array['comm_id'], $json_data, $user_type='user');
           }

           if(isset($users) && !empty($users)){
             $this->db->query("INSERT INTO comms_log (stokvel_id, user_id, createdate) VALUES(?,?,NOW())", array($stokvel_id, $user_id));
             return true;
           }else{
             return false;
           }
    }

    function get_message_count($stokvel_id, $user_id){
      //this needs to change to bring in messages for the month not for all time.
         $query = $this->db->query("SELECT * FROM comms_log WHERE stokvel_id=? and user_id=?", array($stokvel_id, $user_id));
         return $query->result_array();
    } 

  function get_promotions($category='broadsheets', $distributor_id=1){
      $date = date("Y-m-d H:i:s");
      $query = $this->db->query("SELECT * FROM `promotions` WHERE distributor_id = $distributor_id AND category = '$category' AND valid_till > '$date'");
      $promos =  $query->result_array();

      foreach ($promos as $key => $value) {
        $promos[$key]['images'] = $this->get_promotion_images($value['id']);
      }

      return $promos;
  }

    // function get_promotion_images($promotion_id){

    //   $query = $this->db->query("SELECT image FROM `promotion_images` WHERE promotion_id = $promotion_id");
    //     return $query->result_array();
    // }

  function get_events($distributor_id=1){
      $date = date("Y-m-d H:i:s");
      $query = $this->db->query("SELECT * FROM `events` WHERE /*distributor_id = $distributor_id AND*/ event_date > '$date'");
      $events =  $query->result_array();

      foreach ($events as $key => $value) {
        $events[$key]['images'] = $this->get_event_images($value['id']);
      }

      return $events;
  }

  function get_event_images($event_id){

    $query = $this->db->query("SELECT image FROM `event_images` WHERE event_id = $event_id");
    return $query->result_array();
  }

  function add_event_rsvp($user_id, $event_id, $guest_count){
    $this->db->query("INSERT INTO  `event_rsvp` (user_id, event_id, guest_count) VALUES (?,?,?)", array($user_id, $event_id, $guest_count));
  }
   
}

