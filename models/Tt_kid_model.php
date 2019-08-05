<?php

class Tt_kid_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('financial_model');
      $this->load->model('taptuck_model');
      $this->load->model('tt_merchant_model');
      $this->load->model('tt_parent_model');
      $this->load->model('tt_order_model');
      $this->load->model('tt_order_model');
      $this->load->model('qrcode_model');
      $this->load->model('comms_model');
      $this->load->model('user_model');
   }

   public function get_diet_specific($user_id=0){
    //for fututre use. $user_id

      $query = $this->db->query("SELECT * FROM tt_diet_specific");
      $result = $query->result_array();
      return $result;
   }

   public function get_all_tt_kids(){
    
      $query = $this->db->query("SELECT * FROM tt_kids");
      $result = $query->result_array();
      return $result;
   }    
  
   function get_tt_kids($tt_parent_id){
    
    $query = $this->db->query("SELECT id FROM tt_kids WHERE parent_id = ?", array($tt_parent_id));
        if($query->num_rows() >= 1){
           $tt_kids = $query->result_array();
           foreach ($tt_kids as $key => $kid) {
             $tt_kids[$key] = $this->get_tt_kid($kid['id']);
           }
           return $tt_kids;
        }
        return false;
   }

   public function get_tt_kid($tt_kid_id){
    
      $result = $this->get_tt_kid_info($tt_kid_id);
      $coins = array(
          'gold' => 0,
          'silver' => 0,
          'bronze' => 0);
      if($result){
         $result['merchant'] = $this->tt_merchant_model->get_tt_merchant($result['merchant_id'], false);
         if(!$result['merchant']){
          $result['merchant'] = array();
         }
         $result['orders'] = $this->tt_order_model->get_kid_orders($tt_kid_id);
         if($result['orders'] && !empty($result['orders'])){
            foreach ($result['orders'] as $key => $order) {
              switch ($order['coin']) {
                case 'gold':
                  $coins['gold']++;
                  break;
                case 'silver':
                  $coins['silver']++;
                  break;
                case 'bronze':
                  $coins['bronze']++;
                  break;
              }
            }
         }
      }

      $result['coins'] = $coins;
      return $result;
   }


   public function get_tt_kid_from_device($device_identifier){
      $date = date("Y-m-d");
      $result = false;    
      $res = $this->db->query("SELECT id FROM tt_kids WHERE device_identifier = ?",array($device_identifier));
      if($res->num_rows()){
        $tt_kid_id = $res->row_array();
        $tt_kid_id = $tt_kid_id['id'];

        $result = $this->get_tt_kid_info($tt_kid_id);
        if($result){
           $result['merchant'] = $this->tt_merchant_model->get_tt_merchant($result['merchant_id'], false);
           $result['orders'] = $this->tt_order_model->get_kid_orders($tt_kid_id, $date);
        }
      }

      return $result;
   }

   function get_tt_kid_info($tt_kid_id){
      $query = $this->db->query("SELECT * FROM tt_kids WHERE id = ?", array($tt_kid_id));
      if($query->num_rows() == 1){
         $tt_kid = $query->row_array();
         $tt_kid['balance'] = $this->calculate_kid_balance($tt_kid);
         return $tt_kid;
      }
      return false;
   }

   function calculate_kid_balance($tt_kid){

    $balance = 0;
    if($tt_kid['pocket_money'] == 'Y'){
      $parent = $this->tt_parent_model->get_tt_parent_from_kid_id($tt_kid['id']);
      $wallet_balance = $this->financial_model->get_wallet_balance($parent['username']);
      $daily_limit = $tt_kid['daily_limit'];
      $weekly_limit = $tt_kid['weekly_limit'];
      $daily_order_total = $this->tt_order_model->get_kid_order_total($tt_kid['id']);
      $weekly_order_total = $this->tt_order_model->get_kid_order_total($tt_kid['id'],'weekly');
      $daily_remaining = $daily_limit - $daily_order_total;
      $weekly_remaining = $weekly_limit - $weekly_order_total;

      if($weekly_remaining > 0){

        if($daily_remaining > 0){

          if($daily_remaining > $weekly_remaining){
              $balance = $weekly_remaining;
          }else{
            $balance = $daily_remaining;
          }

          if($balance > $wallet_balance){
            return $wallet_balance;
          }
        }
      }
    }
    return $balance;

   }

   function get_parent_from_tt_kid_id($tt_kid_id){

      $query = $this->db->query("SELECT * FROM aauth_users WHERE user_link_id = ? AND (default_usergroup = 8 || default_usergroup = 1)", array($tt_kid_id));
      if($query->num_rows() >= 1){
         return $query->row_array();
      }
      return false;

   }

    function get_taptuck_kids($parent_id, $first_name='', $birthday ='', $kid_id=''){
        
        if(!empty($first_name)){
            $where_name_dob = "AND first_name = '$first_name' AND birthday ='$birthday'";
        }else{
            $where_name_dob  =  ''; 
        }
        if(!empty($kid_id)){
            $where_kid_id =" AND id = '$kid_id'";
        }else{
            $where_kid_id ='';
        }
        
        $sql = "SELECT merchant_id, id, first_name, last_name,device_identifier, meal_option as meal, updated_at
        FROM tt_kids  WHERE parent_id ='$parent_id' $where_name_dob $where_kid_id";
      
        $query= $this->db->query($sql);
        return $query->result_array();
    }

    function add_kid($requestjson){
            if($this->db->insert("tt_kids", $requestjson)){
              return $this->db->insert_id();
            }
            return false;
    } 

    function edit_kid($kid_id, $requestjson){
      $kid = $this->get_tt_kid($kid_id);
      if(!isset($kid['parent_id'])){
        $kid = array("parent_id" => 0);
      }
      if($kid['parent_id'] == $requestjson['parent_id']){
          $this->db->where('id', $kid_id);
          if($this->db->update("tt_kids", $requestjson)){
            return $kid_id;
          }
        }
            return false;
    } 

    function remove_kid($kid_id){
            $this->db->where("id", $kid_id);
            $this->db->delete('tt_kids');
    }

    function add_kid_device($kid_id, $device_identifier, $data_array, $user_id){
            if($this->is_device_unique($device_identifier)){
              //numbers only
              $device_identifier = preg_replace('/[^0-9]/', '', $device_identifier);
              $this->db->where("id", $kid_id);
              $this->db->update('tt_kids', array("device_identifier" => $device_identifier));

              $data['action']="generate_qrcode";
              $data['qr_text']=$device_identifier;
              $data['qrcode']=base_url()."assets/uploads/qr_image/".$device_identifier.".png";
              $this->qrcode_model->generate_qrcode($data);

              $user_info = $this->user_model->get_user($user_id);

              $this->comms_model->send_taptuck_email($user_info->email, array('template' => 'add_kid_device', 'subject' => 'Added Kid Device', 'message' => $data_array), '', 0);
              //$this->comm_model->send_taptuck_email($user_info['email'], $data, '', $attempts=0);
              return true;
            }else{
              return false;
            }
    }

    function is_device_unique($device_identifier){

        $query= $this->db->query("SELECT id FROM tt_kids WHERE device_identifier = ?", array($device_identifier));
        if($query->num_rows() == 0){
          return true;
        }
        return false;

    }

    function get_tt_kid_diet($diet_id){
      $query = $this->db->query("SELECT * FROM tt_diet_specific WHERE id='$diet_id'");
      $tt_kid = $query->row_array();
      return $tt_kid;
      
   }

   function get_kid($parent_id){
      $query = $this->db->query("SELECT * FROM tt_kids WHERE parent_id='$parent_id'");
      return $query->row_array();

   }

   function get_tt_kid_diet_specific($kid_id){
      $query = $this->db->query("SELECT d.name FROM tt_kids as k, tt_diet_specific as d  WHERE k.diet_specific=d.id and k.id='$kid_id'");
      return $query->row_array();
   }

   function get_all_tt_kids_with_parents($merchant_id){
    if(!empty($merchant_id)){
      $where_merchant=" AND k.merchant_id='$merchant_id'";
    }else{
      $where_merchant='';
    }
      $query = $this->db->query("SELECT k.device_identifier, k.id, CONCAT(k.first_name,' ',k.last_name) as kid_name, CONCAT(p.first_name,' ',p.last_name) as parent_name, a.cellphone, a.email, k.image_name, k.birthday, k.device_identifier   FROM tt_kids as k LEFT JOIN tt_parents as p ON k.parent_id=p.id LEFT JOIN aauth_users as a ON a.id=p.user_id WHERE 1 $where_merchant");
      $result = $query->result_array();
      return $result;
   }


    function get_kids_with_parent_id($parent_id){
      $query = $this->db->query("SELECT k.id, CONCAT(k.first_name,' ',k.last_name) as kid_name, CONCAT(p.first_name,' ',p.last_name) as parent_name, a.cellphone, a.email, k.image_name, k.birthday   FROM tt_kids as k LEFT JOIN tt_parents as p ON k.parent_id=p.id LEFT JOIN aauth_users as a ON a.id=p.user_id WHERE k.parent_id=?", array($parent_id));
      $result = $query->result_array();
      return $result;
   }

    public function get_all_tt_kids_for_merchant($merchant_id){
      $query = $this->db->query("SELECT * FROM tt_kids WHERE merchant_id=?",array($merchant_id));
      return $query->result_array();
      
   }    


}

