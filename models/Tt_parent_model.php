<?php

class Tt_parent_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('financial_model');
      $this->load->model('taptuck_model');
      $this->load->model('tt_kid_model');
   }

   public function create_parent($parent){
    
      $result = false; 
      $parent['created_at'] = date("Y-m-d H:i:s");
      if($this->db->query("INSERT INTO tt_parents (user_id, first_name, last_name, created_at) VALUES (".$parent['user_id'].",'".$parent['first_name']."','".$parent['last_name']."','".$parent['created_at']."')")){
        $result = $this->db->insert_id();
      }
      return $result;
   } 
   public function get_all_tt_parents($merchant_id=''){
      if(!empty($merchant_id)){
        $where_merchant="WHERE  k.merchant_id='$merchant_id'";
      }else{
        $where_merchant='';
      }

      $query = $this->db->query("SELECT p.*, p.id, 
                              p.first_name, 
                              p.last_name, 
                              p.balance, 
                              p.created_at, 
                              a.cellphone, 
                              a.email,
                              k.merchant_id,
                              m.name as merchant
                              FROM tt_parents as p 
                              JOIN aauth_users as a ON a.id=p.user_id 
                              LEFT JOIN tt_kids as k ON k.parent_id=p.id
                              LEFT JOIN tt_merchants as m ON k.merchant_id=m.id
                              $where_merchant GROUP BY p.user_id");

      $result = $query->result_array();
      return $result;
   }    
  public function get_all_supplier_tt_parents(){
    
      $query = $this->db->query("SELECT * FROM tt_parents");
      $result = $query->result_array();
      return $result;
   }   

   public function get_tt_parent_from_kid_id($kid_id){

      $query = $this->db->query("SELECT p.*, u.username, u.id as 'user_id' FROM tt_parents p, tt_kids k, aauth_users u WHERE p.user_id = u.id AND k.parent_id = p.id AND k.id = ?", array($kid_id));
      $tt_parent = $query->row_array();
      return $tt_parent;

   }

   public function get_tt_parent($user_id, $username){
    
      $result = $this->get_tt_parent_info($user_id);
      if($result){
         $result['balance'] = $this->financial_model->get_wallet_balance($username);
         $result['kids'] = $this->tt_kid_model->get_tt_kids($result['id']);
         if(!$result['kids']){
          $result['kids'] = array();
         }
      }

      return $result;
   }

   function get_tt_parent_info($user_id){
      $query = $this->db->query("SELECT * FROM tt_parents WHERE user_id = ?", array($user_id));
      if($query->num_rows() == 1){
         $tt_parent = $query->row_array();
         return $tt_parent;
      }
      return false;
   }

   function get_user_from_tt_parent_id($tt_parent_id){

      $query = $this->db->query("SELECT u.* FROM aauth_users u, tt_parents p WHERE u.id = p.user_id AND p.id = ? AND default_usergroup = 14", array($tt_parent_id));
      if($query->num_rows() >= 1){
         return $query->row_array();
      }
      return false;

   }

   function get_tt_parent_from_user_id($user_id){

      $query = $this->db->query("SELECT * FROM tt_parents WHERE user_id = ?", array($user_id));
      if($query->num_rows() >= 1){
         return $query->row_array();
      }
      return false;

   }

   public function get_reps_tt_parents($rep_user_id){
    
      $query = $this->db->query("SELECT cus.* FROM 
         tt_parents as cus,
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

   public function get_tt_parent_from_user_username($username){

      $query = $this->db->query("SELECT user_link_id as 'tt_parent_id' FROM aauth_users WHERE username = ?", array(trim($username)));
      $tt_parent = $query->row_array();    
      return $this->get_tt_parent($tt_parent['tt_parent_id']);
   }

   public function get_tt_parent_from_order($order_id){

      $query = $this->db->query("SELECT tt_parent_id FROM orders WHERE id = ?", array(trim($order_id)));
      $order =  $query->row_array();    
      return $this->get_tt_parent($order['tt_parent_id']);
   }

   public function get_tt_parent_from_distributor_order($distributor_order_id){

      $query = $this->db->query("SELECT tt_parent_id FROM distributor_orders WHERE id = ?", array(trim($distributor_order_id)));
      $order =  $query->row_array();    
      return $this->get_tt_parent($order['tt_parent_id']);
   }

   function get_tt_parent_region($tt_parent_id){

      $query = $this->db->query("SELECT a.region_id, b.name,tt_parent_type FROM tt_parents a, regions b WHERE a.region_id = b.id AND a.id = ?", array($tt_parent_id));
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

   // Get Parent Details for location plotting

   function getParentDetails()
   {
      $query = $this->db->select("c.id, c.store_picture, c.company_name, c.address, c.suburb, c.location_lat, c.location_long, r.name")
                  ->from("tt_parents as c")
                  ->join("regions as r", "r.id = c.region_id")
                  ->where("c.location_lat !=", 0)
                  ->where("c.location_long !=", 0)
                  ->get();
      $result = $query->result_array();
      return $result;
   }

   function getParentDetailsById($id)
   {
      $query = $this->db->select("c.company_name, c.first_name, c.last_name, c.cellphone, c.email, c.address, c.suburb, c.location_lat, c.location_long, r.name")
                  ->from("tt_parents as c")
                  ->join("regions as r", "r.id = c.region_id")
                  ->where("c.id", $id)
                  ->get();
      $result = $query->row();
      return $result;
   }


   function getDistributorParentDetails($values) //region id
   {
      $query = $this->db->select("c.id, c.store_picture, c.company_name, c.address, c.suburb, c.location_lat, c.location_long, r.name, dr.region_id, d.contact_name")
                  ->from("tt_parents as c")
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

   public function get_company_name($tt_parent_id,$distributor_id){
    
    $company_name='';
     $query = $this->db->query("SELECT u.company_name FROM `tt_parents`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($tt_parent_id));
      
     $result = $query->result_array();
      foreach ($result as $key => $item) {

        $company_name = $item['company_name'];

      }

      return $company_name;
   }
public function get_first_name($tt_parent_id,$distributor_id){
    
    $company_name='';
     $query = $this->db->query("SELECT * FROM `tt_parents`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($tt_parent_id));
      
     $result = $query->result_array();
//print_r($query);
      foreach ($result as $key => $item) {

        $company_name = $item['first_name'];

      }

      return $company_name;
   }
public function get_last_name($tt_parent_id,$distributor_id){
    
    $company_name='';
     $query = $this->db->query("SELECT * FROM `tt_parents`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($tt_parent_id));
      
     $result = $query->result_array();
     foreach ($result as $key => $item) {
        $company_name = $item['last_name'];

      }

      return $company_name;
   }

public function get_tt_parent_type($tt_parent_id,$distributor_id){
     $company_name='';
     $query = $this->db->query("SELECT * FROM `tt_parents`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($tt_parent_id));
     $result = $query->result_array();
     foreach ($result as $key => $item) {
        $company_name = $item['tt_parent_type'];
      }
      return $company_name;
}
    
public function get_rewards($tt_parent_id,$distributor_id){
     $company_name='';
     $query = $this->db->query("SELECT * FROM `tt_parents`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($tt_parent_id));   
     $result = $query->result_array();
      foreach ($result as $key => $item) {
          $company_name = $item['rewards'];
    }
      return $company_name;
}
    
public function get_store_picture($tt_parent_id,$distributor_id){
     $company_name='';
     $query = $this->db->query("SELECT * FROM `tt_parents`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($tt_parent_id)); 
     $result = $query->result_array();
      foreach ($result as $key => $item) {
          $company_name = $item['store_picture'];
      }
      return $company_name;
}
    
public function get_callback_id($tt_parent_id,$distributor_id){
    
     $company_name='';
     $query = $this->db->query("SELECT u.id FROM `tt_parents`  u JOIN `regions` r ON u.region_id=r.id JOIN `dist_region_link` p ON r.id=p.region_id  WHERE p.distributor_id='$distributor_id' AND u.id = ?", array($tt_parent_id));
     $result = $query->result_array();

      foreach ($result as $key => $item) {
        $company_name = $item['id'];
      }
      return $company_name;
}
    
public function get_deleted_tt_parent_user($primary_key){

    //$delete = $this->db->query("DELETE FROM `aauth_users` WHERE user_link_id='$primary_key'");
    if($delete){
       return "true";  
    }else{
        return "false";
    }
   
   }
public function get_parent_by_kid_id($kid_id){
    $logged_user_info = $this->aauth->get_user();
    $merchant = $this->tt_merchant_model->get_tt_merchants_by_user_id($logged_user_info->id);

    $sql="SELECT * FROM `tt_parents` as p 
          JOIN tt_kids as k ON p.id = k.parent_id
          WHERE k.id='$kid_id' 
          AND k.merchant_id 
          iN ('".$merchant['merchant_id']."')";

    $query = $this->db->query($sql);

    $result = $query->row_array();
  
    return $result;
}

  public function get_kids($parent_id)
    {
        $query = $this->db->select('*')
                  ->from('tt_kids')
                  ->where('parent_id', $parent_id)
                  ->get();
        $result = $query->result_array();
        return $result;
    }

public function getParentKids($kid_id){
    $query = $this->db->query("SELECT k.*,p.last_name as p_last_name, p.user_id,
                              p.first_name as p_first_name
                              FROM `tt_parents` as p JOIN tt_kids as k ON p.id = k.parent_id
                              WHERE k.id='$kid_id'");
    $result = $query->row_array();
  
    return $result;
}

public function get_parent_for_merchant($merchant_id){
  
    $query = $this->db->query("SELECT p.*, concat(k.first_name, ' ',  k.last_name) as kid, u.*, p.id
                              FROM `tt_parents` as p 
                              JOIN aauth_users as u ON p.user_id = u.id
                              JOIN tt_kids as k ON p.id = k.parent_id
                              JOIN tt_merchants as m ON m.id = k.merchant_id
                              WHERE k.merchant_id = $merchant_id"); 
    $result = $query->result_array();
  
    return $result;
}

}

