<?php

class User_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('financial_model');
   }

   public function user_search($search){
    
      $query = $this->db->query("SELECT * FROM aauth_users where username = ? or email = ?", array($search,$search));
      $result = $query->row_array();
      return $result;
   }

   public function get_user_from_username($username){
    
      $query = $this->db->query("SELECT * FROM aauth_users where username = ?", array($username));
      $result = $query->row_array();
      return $result;
   }

   public function user_wallet_find($msisdn){
      $msisdn = trim($msisdn);
      $user = $this->financial_model->get_internal_wallet($msisdn);
      if($user !== false){
        $result = $user;
      }else{
        $sql = "SELECT u.id, u.name, u.username as 'cellphone', u.email, u.user_link_id, u.default_app, g.name as 'user_group'  FROM aauth_users u, aauth_groups g where u.default_usergroup = g.id AND username = '$msisdn' limit 1";
        $query = $this->db->query($sql);
        $result = $query->row_array();
      }
      return $result;
   }

   function generate_token($user_id, $app='spazapp'){
    $token = $this->does_user_have_active_token($user_id);
    if($token){
      return $token;
    }else{
      $this->load->helper('string');
      $token = random_string('alnum', 20);
      if($app == 'taptuck'){
        $validtill = date("Y-m-d H:i:s",strtotime("+12 days"));
      }else{
        $validtill = date("Y-m-d H:i:s",strtotime("+16 minutes"));
      }
      $this->db->query("INSERT INTO `aauth_tokens` (app, user_id, token, validtill) VALUES ('$app', $user_id, '$token', '$validtill')");
      return $token;
    }
   }

   function get_user_from_token($token){
      $this->remove_expired_tokens();
      $query = $this->db->query("SELECT * FROM aauth_tokens where token = ? and validtill > NOW()", array($token));
      $result = $query->row_array();
      if(isset($result['user_id']) && $query->num_rows() == 1){
        $this->update_token_validtill($token, $result['app']);
        return $result['user_id'];
      }
      return false;
   }


    
function get_ip_user_token($token){
      $this->remove_expired_tokens();
      $query = $this->db->query("SELECT * FROM aauth_tokens where token = ? and validtill > NOW()", array($token));
      $result = $query->row_array();
      if(isset($result['ip_address'])){
        $this->update_token_validtill($token, $result['app']);
        return $result['ip_address'];
      }
      return false;
   }

   function does_user_have_active_token($user_id){
    $this->remove_expired_tokens();
    $query = $this->db->query("SELECT token, app FROM `aauth_tokens` WHERE user_id = $user_id");
      if($query->num_rows() >= 1){
        $res = $query->row_array();
        $this->update_token_validtill($res['token'], $res['app']);
        return $res['token'];
      }else{
        return false;
      }
   }

   function update_token_validtill($token, $app){
    if($app == 'taptuck'){
      $validtill = date("Y-m-d H:i:s",strtotime("+12 days"));
    }else{
      $validtill = date("Y-m-d H:i:s",strtotime("+16 minutes"));
    }
    $this->db->query("UPDATE aauth_tokens SET validtill = '$validtill' WHERE token = '$token'");
   }

   function remove_expired_tokens(){
      $this->db->query("DELETE FROM aauth_tokens where validtill < NOW()");
   }

   function save_imei($user_id, $imei){
      if(!$this->does_imei_exist($user_id, $imei)){
        $this->db->query("INSERT INTO `aauth_user_imei` (user_id, imei) VALUES ($user_id, '$imei')");
        return true;
      }
   }

   function does_imei_exist($user_id, $imei){
      $query = $this->db->query("SELECT * FROM `aauth_user_imei` WHERE user_id = $user_id AND imei = '$imei'");
      if($query->num_rows() >= 1){
        return true;
      }else{
        return false;
      }
   }

  function get_user($user_id){
    
      $query = $this->db->query("SELECT u.*, g.name as 'group_name' FROM `aauth_users` u, aauth_groups g WHERE u.default_usergroup = g.id AND u.id = $user_id");
      return $query->row();

     
  }

  function get_distributor_user($distributor_id){
      $query = $this->db->query("SELECT * FROM `aauth_users`  WHERE user_link_id='$distributor_id'");
      return $query->row();
  }

  function get_user_from_link_id($user_link_id, $default_usergroup=8){
      $query = $this->db->query("SELECT * FROM `aauth_users` WHERE user_link_id = $user_link_id and default_usergroup = $default_usergroup");
      return $query->row();
  }

  function get_customer_from_link_id($user_link_id, $default_usergroup=8){
      $query = $this->db->query("SELECT * FROM `aauth_users` WHERE user_link_id = $user_link_id and default_usergroup = $default_usergroup");
      return $query->row_array();
  }

  function get_general_user($user_id){

      $user = $this->get_user($user_id);
      

      if(isset($user)){
        $group_name = $user->group_name;
        //byron
      if($user->username == '0827378714'){
          $user->customer_id = 12;
      }

      //jason
      if($user->username == '0823371072'){
          $user->customer_id = 523;
      }

      //tim
      if($user->username == '0849846643'){
          $user->customer_id = 15;
      }

      //martin
      if($user->username == '0846698027'){
          $user->customer_id = 16;
      }
      
      //Denzel
      if($user->username == '0736740314'){
          $user->customer_id = 449;
      }

      //customer
      if($user->default_usergroup == 8){
          $query = $this->db->query("SELECT id, default_usergroup, user_link_id, user_link_id as 'customer_id', name, cellphone, email, username FROM `aauth_users` WHERE  id = $user_id");
          $user = $query->row();
          $user->customer_info = $this->customer_model->get_customer($user->user_link_id);
      }

      //spark
      if($user->default_usergroup == 19){
          $query = $this->db->query("SELECT id, default_usergroup, user_link_id, user_link_id as 'spark_id', name, cellphone, email, username FROM `aauth_users` WHERE  id = $user_id");
          $user = $query->row();
      }

      //tt_parent
      if($user->default_usergroup == 14){
          $query = $this->db->query("SELECT id, user_link_id, user_link_id as 'parent_id', name, cellphone, email, username FROM `aauth_users` WHERE id = $user_id");
          $user = $query->row();
      }

      //stokvel_chairman
      if($user->default_usergroup == 33 || $user->default_usergroup == 34){
          $query = $this->db->query("SELECT id, default_usergroup, user_link_id, user_link_id as 'customer_id', name, cellphone, email, username FROM `aauth_users` WHERE  id = $user_id");
          $user = $query->row();
          $this->load->model("stokvel_model");
          $user->user_link = $this->stokvel_model->get_customer($user->user_link_id);
      }

      if($user){
        $names = explode(' ', $user->name);
        $user->firstname = $names[0];
        $user->lastname = trim(str_replace($names[0], '', $user->name));
      }

      $user->group_name = $group_name;
      }
      
      
      return $user;
  }

 function change_password($user_id,$newpassword){

        $this->load->model('customer_model');

        $user_info = $this->get_user($user_id);
        if($user_info->cellphone == ''){
            $customer_info = $this->customer_model->get_customer($user_info->user_link_id);
            $user_info->cellphone = $customer_info['cellphone'];
        }
       
        $data['password'] = $newpassword;
        $data['email'] = $user_info->email;
        $data['cellphone'] = $user_info->cellphone;
        $data['username'] = $user_info->username;
        $hashed_password = $this->aauth->hash_password($newpassword, $user_id);
        
        $this->update_password($user_id, $hashed_password);

        //get sms message and send
        $message = $this->comms_model->fetch_sms_message('reset_password', $data);
        $this->comms_model->send_sms($data['cellphone'], $message);

        return 'Password for <strong>'.$data['username'].'</strong> has been reset to: <strong>'.$data['password'] . '</strong>. The user will be notified on '. $data['cellphone'];
  }

    function veryfy_password($user_id,$password){
         $hashed_password = $this->aauth->hash_password($password, $user_id);
     
        $query = $this->db->select('*')
                  ->from('aauth_users')
                  ->where('id', $user_id)
                  ->get();

        $result = $query->row_array();

         if($hashed_password==$result['pass']){
            return true;
         }else{
            return false;
         }


    }

    function reset_password($user_id,$secure=1){

      $this->load->model('customer_model');

        $user_info = $this->get_user($user_id);
        if($user_info->cellphone == ''){
            $customer_info = $this->customer_model->get_customer($user_info->user_link_id);
            $user_info->cellphone = $customer_info['cellphone'];
        }
       
        $data['password'] = $this->generateRandomString(6);
        $data['email'] = $user_info->email;
        $data['cellphone'] = $user_info->cellphone;
        $data['username'] = $user_info->username;
        $hashed_password = $this->aauth->hash_password($data['password'], $user_id);
        
        $this->update_password($user_id, $hashed_password);

        //get sms message and send
        $message = $this->comms_model->fetch_sms_message('reset_password', $data);
        $this->comms_model->send_sms($data['cellphone'], $message);
    
        $this->comms_library->queue_comm($user_id, 10, $data);

        if($secure == 1){
          return 'Password has been reset. The user will be notified by sms';
        }else{
          return 'Password for <strong>'.$data['username'].'</strong> has been reset to: <strong>'.$data['password'] . '</strong>. The user will be notified on '. $data['cellphone'];
        }
    }

      function update_password($user_id, $hashed_password){
         $this->db->query("UPDATE `aauth_users` SET pass = '$hashed_password' WHERE id = '$user_id'");
      }

    function generateRandomString($length=6) {
        /*$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';*/
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    function get_supplier($customer_id){
       
         $query= $this->db->query("SELECT s.id, s.company_name, dst.company_name as distributor, dst.id as distributor_id FROM `suppliers` s JOIN dist_supplier_link d ON s.id = d.supplier_id JOIN distributors dst ON dst.id=d.distributor_id WHERE s.id = '$customer_id'");
       
        return $query->row_array();
    }  
    function get_dist_supplier($customer_id){
      
         $query= $this->db->query("SELECT  s.company_name,dst.company_name as distributor, dst.id FROM `suppliers` s JOIN dist_supplier_link d ON s.id = d.supplier_id JOIN distributors dst ON dst.id=d.distributor_id WHERE s.id = '$customer_id'");
       
       return $query->result();
      }  
    
    function get_supplier_dist_id($customer_id){
    
         $query= $this->db->query("SELECT * FROM `suppliers` s JOIN dist_supplier_link d ON s.id = d.supplier_id WHERE d.supplier_id = '$customer_id'");
         
          return  $query->result_array();
    }
    
    // Update user on insert user Admin Function    
    function update_user_cellphone($email, $data)
    {
        $this->db->where("email", $email)->update("aauth_users", $data); 
    }

    // Updating push_tokens table functions
    function get_user_details($user_id)
    {
        $query = $this->db->select("*")
                    ->from("aauth_users")
                    ->where("id", $user_id)
                    ->get();
        $result = $query->row();
        return $result;
    }

    function get_user_from_push_token($app, $push_token)
    {
        $query = $this->db->select("user_id")
                    ->from("push_tokens")
                    ->where("app", $app)
                    ->where("push_token", $push_token)
                    ->get();
        $result = $query->row();
        if($result){
          return $result->user_id;
        }else{
          return false;
        }
    }
    
    function get_user_in_pushtokens($app, $user_id)
    {
        $query = $this->db->select("*")
                    ->from("push_tokens")
                    ->where("app", $app)
                    ->where("user_id", $user_id)
                    ->get();
        $result = $query->row();
        return $result;        
    }

    function update_push_token($user_id, $app, $data)
    {
      if(!empty($data['push_token'])){
        $this->db->where("user_id", $user_id)->where("app", $app)->update("push_tokens", $data);
      }
        return true;
    }

    function insert_new_push_token($data)
    {
        $this->db->insert("push_tokens", $data);
        $insert_id = $this->db->insert_id();
        return $insert_id;
    }
    
    function get_province_by_region_id($region_id){
        $query= $this->db->query("SELECT * FROM regions WHERE id ='$region_id'")->row_array();
        return $query;
    }
  
    function get_taptuck_user_profile($parent_id, $user_id =''){

        $result = $this->db->query("SELECT tk.id, 
        tk.first_name, 
        tk.last_name, 
        tk.device_identifier, 
        tk.meal_option as meal,
        tm.id as tm_id,
        tm.name, m.id as m_id,
        m.label, m.description
        
        FROM tt_kids as tk
        JOIN tt_merchants as tm ON tm.id = tk.merchant_id
        JOIN tt_menus as m ON m.merchant_id = tm.id
        WHERE parent_id ='$parent_id'");
    
        $result_array[] = array();
            
        foreach($result->result() as  $item) {
        $result_array[] = [

                    'id'                => $item->id,
                    'first_name'        => $item->first_name,
                    'last_name'         => $item->last_name,
                    'device_identifier' => $item->device_identifier,
                    'meal'              => $item->meal,
                    'merchant' => [
                                "id"    => $item->tm_id,
                                "name"  => $item->name,
                                'menu'  => [$this->get_tt_menu($item->tm_id)] 
                    ]
                ];

       } 
       
     return $result_array;
        
    }
    
    function assign_meal_to_kid($parent_id, $kid_id, $requestjson){
        
         $this->db->where("parent_id", $parent_id);
         $this->db->where("id", $kid_id);
         unset($requestjson['kid_id']);
         $this->db->update("tt_kids", $requestjson);
    }
    function get_tt_menu($merchant_id, $meal_id=''){
        if(!empty($meal_id)){
           $where_meal_id = "AND id IN($meal_id)"; 
        }else{
            $where_meal_id ='';
        }
        
        $result = $this->db->query("SELECT id, label, description, price FROM tt_menus WHERE merchant_id ='$merchant_id' $where_meal_id");
        
        return $result->result_array();
    }
    
    //Get tap tuck parent id
    public function get_parent_id($user_id)
    {
        $query = $this->db->select('id')
                  ->from('tt_parents')
                  ->where('user_id', $user_id)
                  ->get();
        $result = $query->row();
        return $result;
    }

    public function get_all_transactions($parent_id)
    {
        $query = $this->db->select('tr.*, tp.*, tk.*, tm.*, to.*')
                  ->from('tt_transactions as tr')
                  ->join('tt_parents as tp', 'tp.id = tr.parent_id')
                  ->join('tt_kids as tk', 'tk.parent_id = tr.parent_id')
                  ->join('tt_merchants as tm', 'tm.id = tr.merchant_id')
                  ->join('tt_orders as to', 'to.merchant_id = tm.id')
                  ->where('tr.parent_id', $parent_id)
                  ->get();
        $result = $query->result_array();
        return $result;
    }

    public function get_transactions($parent_id)
    {
        $result = $this->get_order_items($order_id,$type);
        $order = array();
        $order['total_amount'] = round($result['total_amount'], 2);
        $order['total_weight'] = round($result['total_weight'], 2);
        $order['total_volume'] = round($result['total_volume'], 2);
        return $order;
    }

    public function get_parents_transactions($parent_id)
    {
        $query = $this->db->select("amount, uuid, transaction_type, created_at, merchant_id")
                  ->from("tt_transactions")
                  ->where("parent_id", $parent_id)
                  ->get();
        $result = $query->result_array();

        foreach ($result as $key => $r) 
        {
            $parent = $this->get_parent_info($parent_id);
            $kid = $this->get_parent_kid($parent_id);
            $merchant = $this->get_merchant($r['merchant_id']);
            $result[$key]['parent'] = $parent;
            $result[$key]['kid'] = $kid;
            $result[$key]['merchant'] = $merchant;
        }

        return $result;
    }

    function get_parent_info($parent_id)
    {
        $query = $this->db->select("tp.id, tp.first_name, tp.last_name, au.email, au.cellphone")
                  ->from("tt_parents as tp") 
                  ->join("aauth_users as au", "au.id = tp.user_id")
                  ->where("tp.id", $parent_id)
                  ->get();
        $result = $query->row();

        $total = $this->db->select("amount, transaction_type")
                  ->from("tt_transactions")
                  ->where("parent_id", $parent_id)
                  ->get();
        $amounts = $total->result_array();

        $sum = 0;

        foreach ($amounts as $v) 
        {
            if($v['transaction_type'] == 'debit')
            {
                $sum = $sum + $v['amount'];
            }
            else
            {
                $sum = $sum - $v['amount'];
            }
        }

        $result->balance = $sum;
        return $result;
    }

    function get_parent_kid($parent_id)
    {
        $query = $this->db->select("id, first_name, last_name, device_identifier, image_name, balance, birthday, meal_option")
                  ->from("tt_kids")
                  ->where("parent_id", $parent_id)
                  ->get();
        $result = $query->row();
        return $result;
    }

    function get_merchant($merchant_id) 
    {
        $none = array();

        if($merchant_id)
        {
            $query = $this->db->select("id, name")
                      ->from("tt_merchants")
                      ->where("id", $merchant_id)
                      ->get();
            $result = $query->row_array();

            $list = $this->db->select("id, label, description, price, period")
                      ->from("tt_menus")
                      ->where("merchant_id", $merchant_id)
                      ->get();
            $return = $list->result_array();
            
            if($return)
            {
              $result['menu'] = $return;
            }
            else
            {
              $result['menu'] = $none;
            }

        }
        else
        {
            $result[] = $none;
        }

        return $result;
    }
    
     function parent_by_name($firstname, $lastname, $user_id){
            $query = $this->db->query("SELECT p.id, first_name, p.last_name, p.balance, p.updated_at, p.created_at
                 FROM tt_parents as p WHERE p.first_name ='$firstname' AND p.last_name ='$lastname'");

            $result_array = array();

            foreach($query->result() as  $item) {
            $result_array = [

                        'id'            => $item->id,
                        'userID'        => $user_id,
                        'firstName'     => $item->first_name,
                        'lastName'      => $item->last_name,
                        'balance'       => $item->balance,
                        'updated_at'    => $item->updated_at,
                        'created_at'    => $item->created_at
                    ];

           } 

         return $result_array;

     }
    function update_tt_parent_balance($parent_id, $requestjson){
            $this->db->where("id", $parent_id);
            $this->db->update("tt_parents", $requestjson);
    } 

    public function getParentId($user_id)
    {
        $query = $this->db->select('*')
                  ->from('tt_parents')
                  ->where('user_id', $user_id)
                  ->get();
        $result = $query->row_array();
        return $result;
    }

  
   function update_user_banned($data,$username)
    {
        $this->db->where("username", $username)->update("aauth_users", $data); 
    }
  
   function update_user($user_id,$data)
    {
        $this->db->where("id", $user_id)->update("aauth_users", $data); 
    }

    function get_verified_users(){
     $query= $this->db->query("SELECT 
                      c.id,
                      c.company_name,
                      u.name, 
                      c.createdate, 
                      u.username as 'msisdn', 
                      r.name as region, 
                      p.name as province
                      FROM aauth_users u, 
                      customers c, 
                      regions r, 
                      provinces p 
                      WHERE u.user_link_id = c.id 
                      AND c.region_id = r.id 
                      and c.province = p.id
                      and u.banned = 0 
                      and u.default_usergroup = 8");

     return  $query->result_array();
    }

    function get_user_group($default_usergroup){
       $query= $this->db->query("SELECT * FROM aauth_groups WHERE id='$default_usergroup'");
       return $query->row_array();
    } 
    function get_user_groups($default_usergroup){
       $query= $this->db->query("SELECT * FROM aauth_groups WHERE id IN($default_usergroup)");
       return $query->result_array();
    }

    function get_users(){
        $query = $this->db->query("SELECT * FROM `aauth_users` WHERE 1");
        return $query->result_array();
    }

    function get_taptuck_users_by_user_id($user_id){
        $query = $this->db->query("SELECT * FROM `aauth_users` a, push_tokens p WHERE a.id=p.user_id and a.id IN($user_id)");
        return $query->result_array();
    }



    /* MULTI COUNTRY STUFF FROM HERE */


  function format_location($region, $city, $country){

    $region = trim($region);
    $city = trim($city);
    $country = trim($country);
    
    //first check if country has been added.
    $country_query = $this->db->query("SELECT id, phonecode FROM countries where name = ? || nicename = ?",array(strtoupper($country),$country));
    $country_result = $country_query->row_array();
    if($country_result){
      $country = $country_result;
    }else{
      $this->db->insert("countries",array("name" => strtoupper($country), "nicename" => $country));
      $country = array("id" => $this->db->insert_id(), "phonecode" => "");
    }

    //second check if city has been added.
    $city_query = $this->db->query("SELECT id, country_id FROM cities where name = ? and country_id = ?",array($city, $country['id']));
    $city_result = $city_query->row_array();
    if($city_result){
      $city = $city_result;
    }else{
      $this->db->insert("cities",array("name" => $city, "country_id" => $country['id']));
      $city = array("id" => $this->db->insert_id());
    }

    //third check if region has been added.
    $region_query = $this->db->query("SELECT id FROM regions where name = ? AND city_id = ?",array($region, $city['id']));
    $region_result = $region_query->row_array();
    if($region_result){
      $region = $region_result;
    }else{
      $this->db->insert("regions",array("name" => $region, "city_id" => $city['id']));
      $region = array("id" => $this->db->insert_id());
    }

    return array(
      "country" => $country,
      "city" => $city,
      "region" => $region
    );

  }

  function find_anonymous_user($username){
    $user = $this->get_user_from_username(trim($username));
    if($user){
      $general_user = $this->get_general_user($user['id']);
      return $general_user->customer_id;
    }
    return false;
  }
   
}
