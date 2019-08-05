<?php

class Global_app_model extends CI_Model { 

   public function __construct()
   {
      parent::__construct();
      $this->load->model('user_model');
      $this->load->model('customer_model');
      $this->load->model('trader_model');
   }

  function define_app_by_msisdn($clref){

    $data = false;

    $user = $this->user_model->get_user_from_username($clref);

    $spazapp_usergroups = array(1,2,4,5,6,7,10,11,12,17,18,19);
    $taptuck_usergroups = array(14,15,16);
    
    if(in_array($user['default_usergroup'], $spazapp_usergroups)){
      $data['app'] = 'spazapp';
      $data['user_id'] = $user['id'];
      $data['group'] = $user['default_usergroup'];
      $data['name'] = $user['name'];
      $data['msisdn'] = $user['username'];
    }

    if($data == false){

        $taptuck_customer = $this->get_tt_parent_from_user_username($clref);
        $data = false;

        if($taptuck_customer){
          $data['app'] = 'taptuck';
          $data['user_id'] = $user['id'];
          $data['group'] = $user['default_usergroup'];
          $data['name'] = $user['name'];
          $data['msisdn'] = $user['username'];
        }
    }

    if($data != false){    
      switch ($user['default_usergroup']) {
        case 8: // customer
          $data['user_link'] = $this->customer_model->get_customer_from_user_username($clref);
          break;
        case 19: //spark
          $data['user_link'] = $this->trader_model->get_trader_from_user_username($clref);
          break;
        case 14: //tt parent
          $data['user_link'] = $this->get_tt_parent_from_user_username($clref);
          break;
        default:
          $data['user_link'] = array();
          break;
      }
    }

      return $data;
  }


  /* TAPTUCK */

   public function get_tt_parent_from_user_username($username){

      $query = $this->db->query("SELECT u.username as 'cellphone', u.email, u.name, p.* FROM aauth_users u, tt_parents p  WHERE p.user_id = u.id AND u.username = ?", array(trim($username)));
      $parent = $query->row_array();    
      return $parent;
   }

   function get_tt_user_from_parent_id($parent_id){

      $query = $this->db->query("SELECT u.username as 'cellphone', u.email, u.name, p.* FROM aauth_users u, tt_parents p  WHERE p.user_id = u.id AND p.id = ?", array(trim($parent_id)));
      $parent = $query->row_array();    
      return $parent;

   }

    function get_tt_user_from_merchant_id($merchant_id){

      $query = $this->db->query("SELECT u.username as 'cellphone', u.email, u.name, m.* FROM aauth_users u, tt_merchants m  WHERE m.user_id = u.id AND m.id = ?", array(trim($merchant_id)));
      $parent = $query->row_array();    
      return $parent;

   }

    function get_app_design($app){

      $query = $this->db->query("SELECT logo, colour1, colour2, colour3, colour4 FROM design WHERE app = ?", array(trim($app)));
      $design = $query->row_array();    
      if(!$design){
        $query = $this->db->query("SELECT logo, colour1, colour2, colour3, colour4 FROM design WHERE app = 'spazapp'");
        $design = $query->row_array();    
      }
      return $design;

   }


}
