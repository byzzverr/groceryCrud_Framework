<?php

class Taptuck_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('financial_model');
      $this->load->model('tt_parent_model');
      $this->load->model('tt_merchant_model');
   }

  function populate_user($user){

    /*
    GROUPS: [14] => 'TapTuckParent',
    GROUPS: [15] => 'TapTuckMerchant',
    GROUPS: [16] => 'TapTuckAdmin'
    */

    $data['id'] = $user->id;
    $data['email'] = $user->email;
    $data['username'] = $user->username;
    $data['name'] = $user->name;
    $data['default_usergroup'] = $user->default_usergroup;
    $data['pushtoken'] = $user->pushtoken;
    $data['wallet_balance'] = $user->wallet_balance;
    $data['user_type'] = 'none';

    $group = $user->default_usergroup;

    switch ($group) {
      case 14: 
        # parent
        $data['parent'] = $this->tt_parent_model->get_tt_parent($user->id, $user->username);
        $data['user_type'] = 'parent';
        if(!$data['parent']){
          $data['parent'] = array();
        }
        break;
      case 15: 
        # merchant
        $data['merchant'] = $this->tt_merchant_model->get_tt_merchant($user->id, true);
        $data['user_type'] = 'merchant';
        if(!$data['merchant']){
          $data['merchant'] = array();
        }
        break;
      case 16: 
        # admin
        $this->tt_parent_model->get_admin($user->id);
        $data['user_type'] = 'admin';
        break;
      default:
        # code...
        break;
    }

    return $data;

  }

  function unique_email($email){
    $query = $this->db->query("SELECT email FROM aauth_users WHERE email = ?", array($email));
    if($query->num_rows() >= 1){
      return false;
    }
    return true;
  }
}

