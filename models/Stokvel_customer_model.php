<?php

class Stokvel_customer_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('financial_model');
      $this->load->model('trader_model');
   }

   public function get_all_customers(){
    
      $query = $this->db->query("SELECT * FROM customers WHERE company_name !='na na' ORDER BY createdate DESC");
      $result = $query->result_array();
      return $result;
   }   

  public function get_all_customers_info($date_from, $date_to, $region_id=''){

    if(!empty($date_from) && !empty($date_to)){
      $where_date=" and c.createdate>= '$date_from' and c.createdate<= '$date_to'";
    }else{
      $where_date='';
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
                              c.createdate,
                              p.name as province,
                              r.name as region,
                              r.parent_id,
                              ct.name as customer_type,
                              (SELECT name FROM regions WHERE id = r.parent_id limit 1) as 'parent',
                              (SELECT name FROM aauth_users WHERE user_link_id = c.id  limit 1) as 'name'
                              FROM customers as c 
                              LEFT JOIN regions as r on r.id=c.region_id 
                              LEFT JOIN customer_types as ct on ct.id=c.customer_type 
                              LEFT JOIN provinces as p on p.id=r.province_id
                              WHERE 1 $where_parent $where_date 
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
      if($result){
         $result['balance'] = $this->financial_model->get_wallet_balance($result['username']);
         $result['rewards'] = $this->financial_model->get_rewards($customer_id);
      }

      if($result['trader_id'] != 0){
         $result['trader'] = $this->trader_model->get_trader_basic($result['trader_id']);
      }

      return $result;
   }

   function get_customer_info($customer_id){
      $query = $this->db->query("SELECT * FROM customers WHERE id = ?", array($customer_id));
      if($query->num_rows() == 1){
         $customer = $query->row_array();
          $user = $this->get_user_from_customer_id($customer_id);
          
         if($customer['email'] == ''){
            $customer['email'] = $user['email'];
         }

         if($customer['cellphone'] == ''){
            $customer['cellphone'] = $user['cellphone'];
         }


         $customer['username'] = $user['username'];
         return $customer;
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

   
}

