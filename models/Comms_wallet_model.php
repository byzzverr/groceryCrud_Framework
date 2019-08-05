<?php

class Comms_wallet_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('customer_model');
      $this->load->model('event_model');
      $this->load->model('global_app_model');
      $this->load->model('spazapp_model');
      $this->load->model('financial_model');
      $this->load->model('insurance_model');


      $this->internal_wallets = array();

      $this->internal_wallets['0999999999'] = array(
        'id'          =>  '0999999999',
        'name'        =>  'Spazapp Holding',
        'email'       =>  'accounts@spazapp.co.za',
        'user_group'  =>  'Internal'
        );

      $this->internal_wallets['0999999998'] = array(
        'id'          =>  '0999999998',
        'name'        =>  'Spazapp Airtime',
        'email'       =>  'accounts@spazapp.co.za',
        'user_group'  =>  'Internal'
        );

      $this->internal_wallets['0999999997'] = array(
        'id'          =>  '0999999997',
        'name'        =>  'Spazapp Brand Connect',
        'email'       =>  'brandconnect@spazapp.co.za',
        'user_group'  =>  'Internal'
        );

      $this->internal_wallets['0999999996'] = array(
        'id'          =>  '0999999996',
        'name'        =>  'Spazapp Rewards',
        'email'       =>  'rewards@spazapp.co.za',
        'user_group'  =>  'Internal'
        );

      $this->internal_wallets['0999999995'] = array(
        'id'          =>  '0999999995',
        'name'        =>  'Spazapp Airtime Comm',
        'email'       =>  'accounts@spazapp.co.za',
        'user_group'  =>  'Internal'
        );

      $this->internal_wallets['0999999989'] = array(
        'id'          =>  '0999999989',
        'name'        =>  'Taptuck Commission',
        'email'        =>  'accounts@taptuck.co.za',
        'user_group'  =>  'Internal'
        );

      $this->internal_wallets['0999999988'] = array(
        'id'          =>  '0999999988',
        'name'        =>  'Taptuck Holding',
        'email'        =>  'accounts@taptuck.co.za',
        'user_group'  =>  'Internal'
        );


      $this->internal_wallets['0999999971'] = array(
        'id'          =>  '0999999971',
        'name'        =>  'Insurapp Holding',
        'email'        =>  'accounts@insurapp.co.za',
        'user_group'  =>  'Internal'
        );

   }

   function get_internal_wallet($msisdn){
    //check if it is an internal wallet.
    if(isset($this->internal_wallets[$msisdn])){
      return $this->internal_wallets[$msisdn];
    }else{
      if(strlen(intval($msisdn)) < 9){
        //check if it is a distributor.
        if(strpos($msisdn, "000000") !== FALSE){
          $did = intval($msisdn);
          $distributor = $this->spazapp_model->get_distributor($did);
          if($distributor){
            return array(
              'id' => $msisdn,
              'name' => $distributor['company_name'],
              'email' => $distributor['email'],
              'user_group' => 'Distributor'
            );
          }
        }

        //check insurers
        if(strpos($msisdn, "0099000") !== FALSE){
          $insurer = $this->insurance_model->get_insurer(intval(str_replace("0099000", '', $msisdn)));
          if($insurer){
            return array(
              'id' => $msisdn,
              'name' => $insurer['name'],
              'email' => '',
              'user_group' => 'Insurer'
            );
          }
        }
        //check entity
        if(strpos($msisdn, "0098000") !== FALSE){
          $entity = $this->insurance_model->get_entity(intval(str_replace("0098000", '', $msisdn)));
          if($entity){
            return array(
              'id' => $msisdn,
              'name' => $entity['name'],
              'email' => '',
              'user_group' => 'Entity-'.$entity['type_name']
            );
          }
        }
        //check agency
        if(strpos($msisdn, "0097000") !== FALSE){
          $agency = $this->insurance_model->get_agency(intval(str_replace("0097000", '', $msisdn)));
          if($agency){
            return array(
              'id' => $msisdn,
              'name' => $agency['name'],
              'email' => '',
              'user_group' => 'Agency'
            );
          }
        }
        //check branch
        if(strpos($msisdn, "0096000") !== FALSE){
          $branch = $this->insurance_model->get_branch(intval(str_replace("0096000", '', $msisdn)));
          if($branch){
            return array(
              'id' => $msisdn,
              'name' => $branch['name'],
              'email' => '',
              'user_group' => 'Branch'
            );
          }
        }
      }
    }

      return false;
   }

   function get_balances($msisdn){
      $return = array();
      $return['wallet_balance'] = $this->financial_model->get_wallet_balance($msisdn);
      $return['pending_balance'] = $this->get_comm_wallet_balance($msisdn);
      return $return;
   }

   function get_all_wallets($date_from='',$date_to=''){
    $date_range = '';
    if($date_from != '' && $date_to != ''){
      $date_range = "WHERE w.createdate > '$date_from' AND w.createdate < '$date_to'";
    }
      $query = $this->db->query("SELECT u.id as 'user_id', u.name, w.msisdn as 'cellphone', u.email, g.name as 'user_group', sum(w.credit-w.debit) as 'balance' 
        FROM comm_wallet_transactions w  
        LEFT JOIN aauth_users u ON u.username = w.msisdn
        LEFT JOIN aauth_groups g ON u.default_usergroup = g.id
        $date_range
        GROUP BY w.msisdn order by balance desc");
      $wallets = $query->result_array();
      foreach ($wallets as $key => $value) {
        if($value['user_id'] == ''){
          $wal = $this->get_internal_wallet($value['cellphone']);
          if($wal){
            $wallets[$key]['user_id'] = $wal['id'];
            $wallets[$key]['name'] = $wal['name'];
            $wallets[$key]['cellphone'] = $wal['id'];
            $wallets[$key]['email'] = $wal['email'];
            $wallets[$key]['user_group'] = $wal['user_group'];
            $wallets[$key]['platform'] = 1;
          }
        }
      }
      return $wallets;
   }

   function get_wallet_transactions($msisdn, $date_from='',$date_to=''){

      $date_range = '';
      if($date_from != '' && $date_to != ''){
        $date_range = "WHERE w.createdate > '$date_from' AND w.createdate < '$date_to'";
      }

      $query = $this->db->query("SELECT w.*, u.default_usergroup FROM comm_wallet_transactions w LEFT JOIN  aauth_users u ON w.msisdn = u.username WHERE  w.msisdn = '$msisdn'");
      $transactions = $query->result_array();
      foreach ($transactions as $key => $value) {
        $transactions[$key]['category'] = $this->financial_model->categorise_transaction($value);
      }
      return $transactions;

   }

   function search_comms($search_term){

      $query = $this->db->query("SELECT w.*, u.default_usergroup, u.name FROM comm_wallet_transactions w LEFT JOIN  aauth_users u ON w.msisdn = u.username WHERE  w.reference LIKE '%$search_term'");
      $transactions = $query->result_array();
      foreach ($transactions as $key => $value) {
        $transactions[$key]['category'] = $this->financial_model->categorise_transaction($value);
        if($value['name'] == ''){
          $internal_wallet = $this->financial_model->get_internal_wallet($value['msisdn']);
          if($internal_wallet){
            $transactions[$key]['name'] = $internal_wallet['name'];
          }
        }
      }
      return $transactions;

   }

   function insurance_purchase($amount, $policy_number){

      $reference = 'insurance_sale-'.$policy_number;
      $this->add_comm_wallet_transaction('credit', $amount, $reference, '0999999971'); //sale
   }

  function get_comm_wallet_balance($msisdn){

      $balance = 0;
      $query = $this->db->query("SELECT * FROM `comm_wallet_transactions` WHERE msisdn = ?", array(trim($msisdn)));
      foreach ($query->result_array() as $key => $value) {
        $balance = $balance - $value['debit'];
        $balance = $balance + $value['credit'];
        
      }
      return $balance;
  }

  function add_comm_wallet_transaction($type, $amount, $reference, $cellphone){

    // please know that all distributors are assigned the username of 0000000000 with the last digits being their id. eg: tradehouse = 0000000001

    $credit = 0.00;
    $debit = 0.00;
    $result = true;

    if($type == 'credit'){
      $credit = $amount;
    }


    if($type == 'debit'){
      $debit = $amount;
    }

    $cellphone ='0' . substr($cellphone,-9);
    $msisdn ='27' . substr($cellphone,-9);

    if(strlen($cellphone) != 10 || substr($cellphone, 0,-9) != '0'){
        $result = false;
    }

    if(!is_numeric($amount) || $amount <= 0){
        $result = false;
    }

    if($result){
      $result = $this->db->query("INSERT INTO `comm_wallet_transactions` 
        (msisdn, debit, credit, reference, createdate) VALUES (?, ?, ?, ?, NOW())", 
        array(trim($cellphone), trim($debit), trim($credit), trim($reference)));
        return $this->db->insert_id();
    }

    return $result;

  }
}