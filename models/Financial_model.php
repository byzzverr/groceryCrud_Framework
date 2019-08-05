<?php

class Financial_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('customer_model');
      $this->load->model('event_model');
      $this->load->model('global_app_model');
      $this->load->model('spazapp_model');
      $this->load->model('user_model');


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

      $this->internal_wallets['0999999994'] = array(
        'id'          =>  '0999999994',
        'name'        =>  'Spazapp Banking Fees',
        'email'       =>  'accounts@spazapp.co.za',
        'user_group'  =>  'Internal'
        );

      $this->internal_wallets['0999999993'] = array(
        'id'          =>  '0999999993',
        'name'        =>  'Spazapp OTT Vouchers',
        'email'       =>  'accounts@spazapp.co.za',
        'user_group'  =>  'Internal'
        );

      $this->internal_wallets['0999999992'] = array(
        'id'          =>  '0999999992',
        'name'        =>  'Spazapp OTT Comm',
        'email'       =>  'accounts@spazapp.co.za',
        'user_group'  =>  'Internal'
        );

      $this->internal_wallets['0999999991'] = array(
        'id'          =>  '0999999991',
        'name'        =>  'Spazapp Electricity',
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

   function get_all_wallets($date_from='', $date_to='', $sage_wallet=false, $group_id=''){
      $date_range = '';
      $GROUP_BY = 'GROUP BY w.msisdn';
      $sum = ", sum(w.credit-w.debit) as 'balance' ";
      $order_by ="order by balance desc";
      if($sage_wallet){
        $GROUP_BY='';
        $sum='';
        $order_by='';
      }
      if($date_from != '' && $date_to != ''){
        $date_range = "WHERE w.createdate > '$date_from' AND w.createdate < '$date_to'";
      }

      if(!empty($group_id)){
        $where_user_group="WHERE g.id='$group_id'";
      }else{
        $where_user_group='';
      }

      $query = $this->db->query("SELECT w.reference, 
        w.createdate, 
        w.credit, 
        w.debit, 
        u.id as 'user_id', 
        u.name, 
        w.msisdn as 'cellphone', 
        u.email, g.name as 'user_group' 
        $sum
        FROM wallet_transactions w  
        LEFT JOIN aauth_users u ON u.username = w.msisdn
        LEFT JOIN aauth_groups g ON u.default_usergroup = g.id
        $where_user_group $date_range $GROUP_BY $order_by ");

      $wallets = $query->result_array();

      foreach ($wallets as $key => $value) {
        $wal = $this->get_internal_wallet($value['cellphone']);
        if($wal){
          $wallets[$key]['user_id'] = $wal['id'];
          $wallets[$key]['name'] = $wal['name'];
          $wallets[$key]['cellphone'] = $wal['id'];
          $wallets[$key]['email'] = $wal['email'];
          $wallets[$key]['user_group'] = $wal['user_group'];
        }
      }

      return $wallets;
   }

   function get_taptuck_global_transactions(){

      $query = $this->db->query("SELECT w.id, u.name, u.email, w.msisdn, g.name as 'group', w.credit, w.debit, w.reference, w.createdate
        FROM wallet_transactions w  
        LEFT JOIN aauth_users u ON u.username = w.msisdn
        LEFT JOIN aauth_groups g ON u.default_usergroup = g.id 
        WHERE u.default_usergroup in (14,15)
        ORDER by w.createdate DESC");
      $wallets = $query->result_array();

      foreach ($wallets as $key => $value) {
        $wal = $this->get_internal_wallet($value['msisdn']);
        if($wal){
          $wallets[$key]['user_id'] = $wal['id'];
          $wallets[$key]['name'] = $wal['name'];
          $wallets[$key]['msisdn'] = $wal['id'];
          $wallets[$key]['email'] = $wal['email'];
          $wallets[$key]['user_group'] = $wal['user_group'];
        }
      }
      return $wallets;
   }

   function get_wallet_deposit_stats($msisdn,$date_from='',$date_to=''){

    $date_range = '';
    if($date_from != '' && $date_to != ''){
      $date_range = "WHERE w.createdate > '$date_from' AND w.createdate < '$date_to'";
    }

    $query = $this->db->query("SELECT SUM(credit) as 'total', count(id) as 'count' FROM wallet_transactions WHERE msisdn = '$msisdn' AND (reference LIKE '%bank-%' || reference LIKE '%card-%') $date_range");
    $result = $query->row_array();

    $total = 0;

    return array("count" => $result['count'], "total" => $result['total']);

   }

   function get_store_rewards($msisdn,$date_from='',$date_to=''){

    $date_range = '';
    if($date_from != '' && $date_to != ''){
      $date_range = "WHERE w.createdate > '$date_from' AND w.createdate < '$date_to'";
    }

    $query = $this->db->query("SELECT SUM(credit) as 'total', count(id) as 'count'  FROM wallet_transactions WHERE msisdn = '$msisdn' AND reference LIKE 'reward_store_signup-%' $date_range");
    $result = $query->row_array();

    return array("count" => $result['count'], "total" => $result['total']);

   }


   function get_wallet_transactions($msisdn, $date_from='',$date_to='', $limit=false){

      $date_range = '';
      if($date_from != '' && $date_to != ''){
        $date_range = "WHERE w.createdate > '$date_from' AND w.createdate < '$date_to'";
      }

      if(!$limit){
        $limit = ' Limit 25';
      }else{
        if($limit == 'all'){
          $limit = '';
        }
      }

      $query = $this->db->query("SELECT w.*, u.default_usergroup, u.name FROM wallet_transactions w LEFT JOIN  aauth_users u ON w.msisdn = u.username WHERE  w.msisdn = '$msisdn' order by w.createdate desc $limit");
      $transactions = $query->result_array();
      foreach ($transactions as $key => $value) {
        $transactions[$key]['category'] = $this->categorise_transaction($value);
      }
      return $transactions;

   }

  
   function categorise_transaction($transaction){

    $category = 'uncategorised';

    $reference = explode('-', $transaction['reference']);

    switch ($reference[0]) {
      case 'taptuck_order':
        switch ($transaction['default_usergroup']) {
          case 8:
          case 14:
          case 1:
            $category = 'purchase';
            break;
          default:
            $category = 'sale';
            break;
        }
        break;
      case 'taptuck_order_cancel':
      case 'taptuck_order_refund':
        $category = 'refund';
        break;
      case 'taptuck_order_redemtion':
      case 'airtime_data':
      case 'electricity':
      case 'ott_voucher':
        $category = 'sale';
        break;
      case 'spazapp_order_do':
        $category = 'sale';
        break;
      case 'spazapp_sale':
        $category = 'purchase';
        break;
      case 'brandconnect_task':
        switch ($transaction['default_usergroup']) {
          case 8:
          case 19:
          case 1:
            $category = 'commission';
            break;
          default:
            $category = 'bc_cost';
            break;
        }
        break;
      case 'comm_training_complete':
        switch ($transaction['default_usergroup']) {
          case 8:
          case 19:
          case 1:
            $category = 'commission';
            break;
          default:
            $category = 'reward_cost';
            break;
        }
        break;
      case 'insurance_sale_comm':
        switch ($transaction['default_usergroup']) {
          case 8:
            $category = 'commission';
            break;
          default:
            $category = 'commission';
            break;
        }
        break;
      case 'reward_store_signup':
      case 'comm_airtime_data':
      case 'comm_ott_voucher':
        $category = 'commission';
        break;
      case 'bank':
      case 'card':
      case 'masterpass':
      case 'ott_redemption':
        $category = 'deposit';
        break;
      case 'cashout':
      case 'taptuck_cashout':
        $category = 'cashout';
        break;

    }

    return $category;
   }

   function assign_spark_registration_comm($customer_id){

    $registration_reward = 5;
    $customer = $this->customer_model->get_customer($customer_id);
    if(isset($customer['trader']) && $customer['trader']){
      $reference = 'reward_store_signup-'.$customer_id;
      $this->add_wallet_transaction('credit', $registration_reward, $reference, $customer['trader']['username']);
    }
   }

   function ott_purchase($msisdn, $amount, $reference){

      $our_comm = $amount*0.04;
      $their_comm = $amount*0.02;
      $comm = $our_comm + $their_comm;
      $cost = $amount - $comm;

      $reference2 = 'comm_ott_voucher-'.$reference;
      $reference = 'ott_voucher-'.$reference;

      $this->add_wallet_transaction('debit', $amount, $reference, $msisdn);
      $this->add_wallet_transaction('credit', $cost, $reference, '0999999993'); //sale

      $this->add_wallet_transaction('credit', $our_comm, $reference2, '0999999992'); //our comm
      $this->add_wallet_transaction('credit', $their_comm, $reference2, $msisdn); //their commission

   }

   function electricity_purchase($msisdn, $amount, $reference){

      $reference = 'electricity-'.$reference;
      $this->add_wallet_transaction('debit', $amount, $reference, $msisdn);
      $this->add_wallet_transaction('credit', $amount, $reference, '0999999991'); //sale
   }

   function airtime_purchase($msisdn, $amount, $cost, $reference){

      $our_comm = $amount*0.01;
      $their_comm = $amount*0.03;
      $comm = $our_comm + $their_comm;
      $cost = $amount - $comm;

      $reference2 = 'comm_airtime_data-'.$reference;
      $reference = 'airtime_data-'.$reference;
      $this->add_wallet_transaction('debit', $amount, $reference, $msisdn);
      $this->add_wallet_transaction('credit', $cost, $reference, '0999999998'); //sale

      $this->add_wallet_transaction('credit', $our_comm, $reference2, '0999999995'); //our comm
      $this->add_wallet_transaction('credit', $their_comm, $reference2, $msisdn); //their commission
   }

   function refund_airtime_purchase($purchase_id, $sell_price){

      $purchase = $this->airtime_model->get_purchase($purchase_id);


      $user = $this->user_model->get_user($purchase['user_id']);
      $msisdn = $user->username;
      $reference = $purchase_id."-".$purchase['voucher_id']."-".$purchase['cellphone'];

      $amount = $purchase['amount'];
      if($sell_price > 0){
        $amount = $sell_price;
      }
     
      $our_comm = $amount*0.01;

      $their_comm = $amount*0.03;
      $comm = $our_comm + $their_comm;
      $cost = $amount - $comm;

      $reference2 = 'refund_comm_airtime_data-'.$reference;
      $reference = 'refund-airtime_data-'.$reference;

      $this->add_wallet_transaction('credit', $amount, $reference, $msisdn);
      $this->add_wallet_transaction('debit', $cost, $reference, '0999999998'); //sale

      $this->add_wallet_transaction('debit', $our_comm, $reference2, '0999999995'); //our comm
      $this->add_wallet_transaction('debit', $their_comm, $reference2, $msisdn); //their commission
   }


   function search_wallet($search_term){

      $query = $this->db->query("SELECT w.*, u.default_usergroup, u.name FROM wallet_transactions w LEFT JOIN  aauth_users u ON w.msisdn = u.username WHERE  w.reference LIKE '%$search_term'");
      $transactions = $query->result_array();
      foreach ($transactions as $key => $value) {
        $transactions[$key]['category'] = $this->categorise_transaction($value);
        if($value['name'] == ''){
          $internal_wallet = $this->get_internal_wallet($value['msisdn']);
          if($internal_wallet){
            $transactions[$key]['name'] = $internal_wallet['name'];
          }
        }
      }
      return $transactions;
   }

   function insurance_purchase($msisdn, $amount, $policy_number){

      $reference = 'insurance_purchase-'.$policy_number;
      $this->add_wallet_transaction('debit', $amount, $reference, $msisdn);
      
      $reference = 'insurance_sale-'.$policy_number;
      $this->add_wallet_transaction('credit', $amount, $reference, '0999999971'); //sale
   }

   function store_absa_log($data){

      $this->db->insert('absa_log', $data);
      return $this->db->insert_id();
   }

   function get_unprocessed_absa_payments(){

      $query = $this->db->query("SELECT * FROM absa_payments WHERE processed = 0");
      return $query->result_array();
   }

   function mark_absa_payment_as_processed($id){
    $this->db->query("UPDATE absa_payments SET processed = 1 WHERE id = $id");
   }

   function store_absa_transaction($data){

      $this->db->insert('absa_payments', $data);
      return $this->db->insert_id();
   }

   function insert_cref($cref){
    $data['Reference'] = $cref;
    $data['createdate'] = date("Y-m-d H:i:s");
    $this->db->insert('paygate',$data);
    return $this->db->insert_id();
   }

   function update_cref($data){
    $data->resultdate = date("Y-m-d H:i:s");
    $this->db->where('Reference',$data->Reference);
    $this->db->update('paygate',$data);
   }

   function notify_customer($type, $data){
      switch ($type) {
         case 'add_to_customer_account':
            $data['customer'] = $this->customer_model->get_customer($data['customer_id']);
            $data['balance'] = $this->get_balance($data['customer_id']);
            $user = $this->user_model->get_user_from_link_id($data['customer']['id'], 8);
            $this->comms_library->queue_comm_group($user->id, 'add_to_customer_account', $data);//Queuing the comms

             //$message = $this->comms_model->fetch_sms_message($type, $data);
            // //$this->comms_model->send_sms($data['customer']['cellphone'], $message);
            // $this->comms_model->push_notification($user->id, $user->default_app, $message);
            //get email message and send
            //$subject = $this->comms_model->fetch_email_subject($type, $data);
            // $this->comms_model->send_email($data['customer']['email'], array('template' => $type, 'subject' => $subject, 'message' => $data));
            
            break;
         
         case 'add_to_customer_airtime_account':
            $data['customer'] = $this->customer_model->get_customer($data['customer_id']);
            $data['airtime_balance'] = $this->get_airtime_balance($data['customer_id']);
            $user = $this->user_model->get_user_from_link_id($data['customer']['id'], 8);
            $this->comms_library->queue_comm_group($user->id, 'add_to_customer_airtime_account', $data);//Queuing the comms
            //get sms message and send
            //$message = $this->comms_model->fetch_sms_message($type, $data);
            // $this->comms_model->send_sms($data['customer']['cellphone'], $message);
            
            //get email message and send
            //$subject = $this->comms_model->fetch_email_subject($type, $data);
            // $this->comms_model->send_email($data['customer']['email'], array('template' => $type, 'subject' => $subject, 'message' => $data));

            
            break;

         case 'remove_from_customer_account':
            $data['customer'] = $this->customer_model->get_customer($data['customer_id']);
            $data['balance'] = $this->get_balance($data['customer_id']);
            $user = $this->user_model->get_user_from_link_id($data['customer']['id'], 8);
            $this->comms_library->queue_comm_group($user->id, 'remove_from_customer_account', $data);//Queuing the comms
            //get sms message and send
            // $message = $this->comms_model->fetch_sms_message($type, $data);
            // $this->comms_model->send_sms($data['customer']['cellphone'], $message);
            
            //get email message and send
            // $subject = $this->comms_model->fetch_email_subject($type, $data);
            // $this->comms_model->send_email($data['customer']['email'], array('template' => $type, 'subject' => $subject, 'message' => $data));
             
            break;        

        case 'remove_from_customer_rewards':
            $data['customer'] = $this->customer_model->get_customer($data['customer_id']);
            $data['rewards'] = $this->get_rewards($data['customer_id']);
            $user = $this->user_model->get_user_from_link_id($data['customer']['id']);
            $this->comms_library->queue_comm_group($user->id, 'remove_from_customer_rewards', $data);//Queuing the comms
            //get sms message and send
            //$message = $this->comms_model->fetch_sms_message($type, $data);
            // $this->comms_model->send_sms($data['customer']['cellphone'], $message);
/*            //get email message and send
            $subject = $this->comms_model->fetch_email_subject($type, $data);
            $this->comms_model->send_email($data['customer']['email'], array('template' => $type, 'subject' => $subject, 'message' => $data));*/
             
            break;
         
         case 'remove_from_customer_airtime_account':
            $data['customer'] = $this->customer_model->get_customer($data['customer_id']);
            $data['airtime_balance'] = $this->get_airtime_balance($data['customer_id']);
            $user = $this->user_model->get_user_from_link_id($data['customer']['id']);
            $this->comms_library->queue_comm_group($user->id, 'remove_from_customer_airtime_account', $data);//Queuing the comms
            //get sms message and send
            // $message = $this->comms_model->fetch_sms_message($type, $data);
            // $this->comms_model->send_sms($data['customer']['cellphone'], $message);
            
            //get email message and send
            // $subject = $this->comms_model->fetch_email_subject($type, $data);
            // $this->comms_model->send_email($data['customer']['email'], array('template' => $type, 'subject' => $subject, 'message' => $data));
            break;
      }

   }


  function get_rewards($customer_id){

      $rewards = 0;
      $query = $this->db->query("SELECT * FROM `rewards` WHERE customer_id = ?", array($customer_id));
      foreach ($query->result_array() as $key => $value) {
        $rewards = $value['reward'] + $rewards;
      }

      return $rewards;
  }

  function get_balance($customer_id){

    $user = $this->customer_model->get_user_from_customer_id($customer_id);
    $msisdn = $user['username'];

    return $this->get_wallet_balance($msisdn);

/*      $balance = 0;
      $query = $this->db->query("SELECT * FROM `customer_accounts` WHERE customer_id = ?", array($customer_id));
      foreach ($query->result_array() as $key => $value) {
        $balance += $value['amount'];
      }
      return $balance;
      */
  }

  function get_balance_from_user_id($user_id){

    $user = $this->user_model->get_user($user_id);
    if($user){
      $msisdn = $user->username;
      return $this->get_wallet_balance($msisdn);     
    }
    return 0;

/*      $balance = 0;
      $query = $this->db->query("SELECT * FROM `customer_accounts` WHERE customer_id = ?", array($customer_id));
      foreach ($query->result_array() as $key => $value) {
        $balance += $value['amount'];
      }
      return $balance;
      */
  }

  function get_wallet_balance($msisdn){

      $balance = 0;
      $query = $this->db->query("SELECT (SUM(credit)-SUM(debit)) as balance FROM `wallet_transactions` WHERE msisdn = ?", array(trim($msisdn)));
      
      $balance = $query->row_array()['balance'];
      return round($balance, 2);
  }

  function get_monthly_spend($msisdn){

      $start_date = date("Y-m-01 00:00:01");
      $end_date = date("Y-m-d H:i:s");
      $balance = 0;
      $query = $this->db->query("SELECT (SUM(debit)-SUM(credit)) as balance FROM `wallet_transactions` WHERE reference like '%order%' AND msisdn = ? and createdate >= ? and  createdate <= ?", array(trim($msisdn),$start_date, $end_date));
      
      $balance = $query->row_array()['balance'];
      return round($balance, 2);

  }

  function get_airtime_balance($customer_id){

      $balance = 0;
      $query = $this->db->query("SELECT * FROM `customer_airtime_accounts` WHERE customer_id = ?", array($customer_id));
      foreach ($query->result_array() as $key => $value) {
        $balance = $value['amount'] + $balance;
      }
      return $balance;
  }


/*  function add_to_taptuck_customer_account($parent, $amount, $reference){

    $date = date("Y-m-d H:i:s");

    $this->load->library('curl');

    $message = "TAPTUCK :) Hi, we received your deposit! Your deposit of R$amount has been converted into TapTuck coins. Ref: $reference";
    $endpoint = 'http://taptuck.spazapp.co.za/api/parent/credit';
    $method = 'PUT';
    $params = array(
      'user_id' => $parent['info']['user_id'],
      'amount' => round($amount),
      'key' => "BB496112-C720-4706-9D41-640528F7AC9A"
      );
    
    
    $result = $this->curl->_simple_call($method, $endpoint, $params);

    $result = json_decode($result, TRUE);

    if($result['meta']['code'] == 200){
      $this->comms_model->send_sms($parent['info']['cellphone'], $message);
      $this->event_model->private_track_event($parent['info']['user_id'], 'financial', 'bank_deposit_taptuck', 'User deposited R'.$amount.' into wallet: '.$parent['info']['cellphone'].' ', $amount, $date);
      return true;
    }else{
      return false;
    }
    
  }*/

  function has_task_been_rewarded_before($msisdn, $task_id){
    $reference = 'brandconnect_task-'.$task_id;
    $query = $this->db->query("SELECT * FROM wallet_transactions WHERE msisdn = '$msisdn' AND reference = '$reference'");
    if($query->num_rows() >= 1){
      return true;
    }
    return false;
  }

  function brand_connect_rewards($task_id, $customer_id){

    $this->load->model('task_model');
    $task = $this->task_model->get_task($task_id);
    $customer = $this->customer_model->get_customer($customer_id);

    $reference = 'brandconnect_task-'.$task_id;
    $type = 'brand_connect';

    if($task['reward_amount'] > 0){
      $amount = $task['reward_amount'];
      $transaction_id = $this->add_to_spazapp_customer_account($type, $customer_id, $amount, 0, 0, $reference);
    }

    if($task['trader_reward'] > 0 && $customer['trader_id'] != 0){
      $amount = $task['trader_reward'];
      $this->add_wallet_transaction('credit', $amount, $reference.'-'.$customer_id, $customer['trader']['username']);
    }

    if($task['spazapp_reward'] > 0){
      $amount = $task['spazapp_reward'];
      $this->add_wallet_transaction('credit', $amount, $reference.'-'.$customer_id, '0999999997'); // spazapp BC
    }

    return $transaction_id;

  }

  function distributor_order_delivered($distributor_order_id){
    $this->load->model('order_model');
    $username = $this->order_model->get_distributor_wallet($distributor_order_id);
    $order = $this->order_model->get_dis_order_info($distributor_order_id);

    $amount = $order['items']['total_amount'];

    $ref = $this->add_wallet_transaction('credit', $amount, 'spazapp_order_do-'.$distributor_order_id, $username); // pay distributor
    if($ref){
      $this->add_wallet_transaction('debit', $amount, 'spazapp_order_do-'.$distributor_order_id, '0999999999'); // spazapp holding
    }

    return $ref;
  }

  function add_to_spazapp_customer_account($type, $customer_id, $amount, $order_item_id=0, $added_by=0, $reference=''){

    // 0999999999 - Spazapp Holding
    // 0999999998 - Spazapp Airtime (this has not been used yet)
    // 0999999997 - Spazapp Brand Connect

    $createdate = date("Y-m-d H:i:s");
    $data['type'] = $type;
    $data['customer_id'] = $customer_id;
    $data['amount'] = $amount;
    $data['createdate'] = $createdate;
    $data['reference'] = $reference;

    $customer = $this->customer_model->get_customer($customer_id);

    switch ($type) {
      case 'airtime':
        //airtime moves immidiately to spazapp airtime
        $reference = 'Airtime Top Up';
        $comm_type = 'none';
        $transaction_id = $this->add_wallet_transaction('debit', $amount, $reference, $customer['username']);
        break;
      case 'sale':
        //on sale money moves to spaza holding till delivered.
        $reference = $order_item_id;
        $transaction_id = $this->add_wallet_transaction('credit', $amount, $reference, $customer['username']);
        $comm_type = 'add_to_customer_account';
        break;
      case 'brand_connect':
        //brand connect
        $transaction_id = $this->add_wallet_transaction('credit', $amount, $reference, $customer['username']);
        $comm_type = 'add_to_customer_account';
        break;
      case 'order_delivered':
        //cash now moved to distributors account
        $reference = $order_item_id;
        $transaction_id = $this->add_wallet_transaction('credit', $amount, $reference, $customer['username']);
        $comm_type = 'add_to_customer_account';
        break;
      case 'refund':
        //order cancelled before delivery - holding back to customer
        $transaction_id = $this->add_wallet_transaction('credit', $amount, $reference, $customer['username']);
        if($transaction_id){
          $this->add_wallet_transaction('debit', $amount, $reference, '0999999999'); //spazapp holding account
        }
        $comm_type = 'none';
        break;
      case 'recharge':
        //cash added to wallet from EFT or ATM
        $transaction_id = $this->add_wallet_transaction('credit', $amount, $reference, $customer['username']);
        $comm_type = 'none';
        break;
    }
   
    if($transaction_id){
      $this->notify_customer($comm_type, $data);
    }

    return $transaction_id;
    
  }

  function process_cc_payment($data, $paygate){

    $user = $this->aauth->get_user($data['user_id']);
    $cellphone = $user->username;
    $type = 'credit';
    $reference = $paygate->Reference;
    $amount = $data['amount'];

    $this->add_wallet_transaction($type, $amount, $reference, $cellphone);

  }

  function masterpass_payment($result){

    $type = 'credit';
    $reference = "masterpass-".$result['reference'];
    $amount = $result['amount'];

    if(isset($result['order_id']) && $result['order_id'] > 0){
      
      $this->load->model('order_model');

      $distributor_order = $this->order_model->get_distributor_order($result['order_id']);
      $cellphone = $this->order_model->get_distributor_wallet($distributor_order['id']);


      $user = $this->aauth->get_user($result['user_id']);
      $phone = $user->username;

      if($result['status'] == 'SUCCESS'){
        $this->add_wallet_transaction($type, $amount, $reference, $cellphone);
        $message = 'SPAZAPP: Masterpass payment SUCCESS to the value of R'.$amount.' for order '.$result['order_id'].'.';
      }else{
         $message = 'SPAZAPP: Masterpass payment FAILED to the value of R'.$amount.' for order '.$result['order_id'].'.';
      }

      $this->comms_model->send_sms($phone, $message);

    }else{
      $user = $this->aauth->get_user($result['user_id']);
      $cellphone = $user->username;

      if($result['status'] == 'SUCCESS'){
        $this->add_wallet_transaction($type, $amount, $reference, $cellphone);

        $message = 'SPAZAPP: SUCCESS Masterpass deposit of R'.$amount.' into your account.';
      }else{
        $message = 'SPAZAPP: FAILED Masterpass deposit of R'.$amount.' into your account.';
      }
      $this->comms_model->send_sms($cellphone, $message);
    }
  }

  function ott_redemption($cellphone, $result){

    $type = 'credit';
    $reference = "ott_redemption-".$result['unique_reference'];
    $amount = $result['value'];
    $this->add_wallet_transaction($type, $amount, $reference, $cellphone);

  }


  function add_wallet_transaction($type, $amount, $reference, $cellphone){

    // 0999999999 - Spazapp Holding
    // 0999999998 - Spazapp Airtime
    // 0999999997 - Spazapp Brand Connect
    // 0999999996 - Spazapp Rewards
    // 0999999995 - Spazapp Airtime comm

    // 0999999989 - Taptuck Commission
    // 0999999988 - Taptuck Holding

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
      $result = $this->db->query("INSERT INTO `wallet_transactions` 
        (msisdn, debit, credit, reference, createdate) VALUES (?, ?, ?, ?, NOW())", 
        array(trim($cellphone), trim($debit), trim($credit), trim($reference)));
        return $this->db->insert_id();
    }

    return $result;

  }


  function remove_from_customer_account($type, $customer_id, $amount, $order_item_id=0, $added_by=0, $reference=''){

    $data['type'] = $type;
    $data['customer_id'] = $customer_id;
    $data['amount'] = $amount;
    $data['createdate'] = date("Y-m-d H:i:s");
    

    $customer = $this->customer_model->get_customer($customer_id);
    $user = $this->user_model->get_user_from_link_id($customer_id);

    switch ($type) {

      case 'sale':
        $transaction_id = $this->add_wallet_transaction('debit', $amount, $reference, $customer['username']);
        if($transaction_id){
          $this->add_wallet_transaction('credit', $amount, $reference, '0999999999'); // spazapp holding
        }
        $comm_type = 'remove_from_customer_account';
        break;

      case 'rewards':
        $reference = 'Order Purchase using Rewards as payment type';
        $comm_type = 'remove_from_customer_rewards';
        $this->db->query("INSERT INTO `rewards` (customer_id, reward, reason, order_id, createdate) VALUES (?,?,?,?,NOW())", array($customer_id, -$amount, $reference, $order_ref));
        break;
    }
    $data['reference'] = $reference;
    if($transaction_id){
      $this->notify_customer($comm_type, $data);
    }

    return $reference;
    
  }

  function process_taptuck_order($order_id, $type, $comm=true){

    // 0999999989 - Taptuck Commission
    $taptuck_commission_percentage = 0.05;
    $taptuck_msisdn = '0999999989';
    $taptuck_holding = '0999999988';

    $query = $this->db->query("SELECT o.*, p.id as 'parent_id', k.merchant_id, k.first_name as 'kid_name', m.label as 'product', m.price as 'original_price' FROM tt_orders o, tt_kids k, tt_parents p, tt_menus m WHERE o.menu_id = m.id AND o.kid_id = k.id AND k.parent_id = p.id AND o.id = '$order_id'");
    $order = $query->row_array();

      //get parent
        $parent = $this->global_app_model->get_tt_user_from_parent_id($order['parent_id']);
      //get merchant
        $merchant = $this->global_app_model->get_tt_user_from_merchant_id($order['merchant_id']);
        //calculate commission
        $commission = $order['price']-$order['original_price'];

    if($type == 'add'){
        
        $uuid = "taptuck_order-".$order_id;
        //debit the parent
        $this->add_wallet_transaction('debit', $order['price'], $uuid, trim($parent['cellphone']));
        //credit taptuck holding
        $this->add_wallet_transaction('credit', $order['price'], $uuid, trim($taptuck_holding));

    }

    if($type == 'cancel'){

        $uuid = "taptuck_order_cancel-".$order_id;
        //credit the parent
        $this->add_wallet_transaction('credit', $order['price'], $uuid, trim($parent['cellphone']));
        //debit taptuck holding
        $this->add_wallet_transaction('debit', $order['price'], $uuid, trim($taptuck_holding));
    }

    if($type == 'redeem'){

        $uuid = "taptuck_order_redemtion-".$order_id;
        //debit the holding
        $this->add_wallet_transaction('debit', $order['price'], $uuid, trim($taptuck_holding));
        //credit the merchant - original price
        $this->add_wallet_transaction('credit', $order['original_price'], $uuid, trim($merchant['cellphone']));
        //credit taptuck with the commission
        $this->add_wallet_transaction('credit', $commission, $uuid, $taptuck_msisdn);

        if ($comm) {
          $this->comms_library->queue_comm($parent['user_id'], 45, $order);//Queuing the comm
        }
    }

  }

  function pocket_money_purchase($parent_username, $merchant_username, $amount, $pocket_money_sale_id){

    $taptuck_msisdn = '0999999989';

    $amount_p = $amount*1.05;
    $commission = $amount_p - $amount;

    $uuid = "taptuck_pocket_money_sale-".$pocket_money_sale_id;
    //debit the parent
    $this->add_wallet_transaction('debit', $amount_p, $uuid, $parent_username);
    //credit the merchant - original price
    $this->add_wallet_transaction('credit', $amount, $uuid, $merchant_username);
    //credit taptuck with the commission
    $this->add_wallet_transaction('credit', $commission, $uuid, $taptuck_msisdn);

      //add the comm later.
    //$this->comms_library->queue_comm($parent['user_id'], 50, array("kid_name" => $kid_name, "amount" => $amount, "merchant_name" => $merchant_name));//Queuing the comm
  }

  //Update Customer Account On Edit Order
  function update_customer_order($data)
  {
    $this->db->insert("customer_accounts", $data);
  }

  //Credit Taptuck Account
  function credit_taptuck($data)
  {
      $insert = $this->db->insert("tt_transactions", $data);
      if($insert)
      {
          return "success";
      }
      else
      {
          return "false";
      }
  }

  function tt_convert_rand_to_coin($price){
      if($price>50){
        $value = 'gold';
      }

      if($price<15){
        $value = 'bronze';
      }

      if($price>=15 && $price<30){
        $value = 'bronze';
      }

      if($price>29 && $price<50){
        $value = 'silver';
      }
      
      switch ($price) {
      case 15:
         $coin = 'bronze';
         break;

      case 30:
         $coin = 'silver';
         break;
         
      case 50:
         $coin = 'gold';
         break;  

      default:
        $coin=$value; 
         break;
    }
    return $coin;
  }

  function tt_convert_category_to_coin($category){

      
      switch ($category) {
      case 'Drink':
         $coin = 'bronze';
         break;

      case 'Snack':
         $coin = 'silver';
         break;
         
      case 'Meal':
         $coin = 'gold';
         break;  

      default:
        $coin='bronze'; 
         break;
    }
    return $coin;
  }


  function get_all_trader_wallets($trader_id){
  
      $query = $this->db->query("SELECT u.id as 'user_id', u.name, w.msisdn as 'cellphone', u.email, g.name as 'user_group', sum(w.credit-w.debit) as 'balance' 
        FROM wallet_transactions w  
        LEFT JOIN aauth_users u ON u.username = w.msisdn
        LEFT JOIN customers c ON u.cellphone = c.cellphone
        LEFT JOIN aauth_groups g ON u.default_usergroup = g.id
        WHERE c.trader_id='$trader_id' 
        GROUP BY w.msisdn order by balance desc");
      $wallets = $query->result_array();

      foreach ($wallets as $key => $value) {
        $wal = $this->get_internal_wallet($value['cellphone']);
        if($wal){
          $wallets[$key]['user_id'] = $wal['id'];
          $wallets[$key]['name'] = $wal['name'];
          $wallets[$key]['cellphone'] = $wal['id'];
          $wallets[$key]['email'] = $wal['email'];
          $wallets[$key]['user_group'] = $wal['user_group'];
        }
      }
      return $wallets;
   }

   function get_electricity_transactions(){
    $query=$this->db->query("SELECT w.msisdn, 
                            w.debit, 
                            w.credit, 
                            w.createdate, 
                            MID(w.reference,1,11) as ref,
                            a.name
                            FROM wallet_transactions as w LEFT JOIN aauth_users as a
                            ON w.msisdn=a.username
                            WHERE MID(w.reference,1,11)='electricity' 
                            ORDER BY w.id 
                            DESC LIMIT 200");
    $res= $query->result_array();
    $return[]=array();
    foreach ($res as $key => $value) {
      $return[$key]['msisdn']=$value['msisdn'];
      $return[$key]['debit']=$value['debit'];
      $return[$key]['credit']=$value['credit'];
      $return[$key]['createdate']=$value['createdate'];
      $return[$key]['ref']=$value['ref'];
      $return[$key]['name']=$value['name'];
      $return[$key]['user_group']=$this->user_model->user_wallet_find($value['msisdn'])['user_group'];
    }
    return $return;
   }

   function get_taptuck_transactions($type='', $msisdn='', $request_type='', $data=''){
      if($type=="cashout"){
        $where_reference=" and (w.reference = 'taptuck_cashout' || SUBSTRING_INDEX(SUBSTRING_INDEX(w.reference, '-', 1), ' ', -1)='taptuck_cashout')";
        $credit=" ";
      }else{
        $where_reference="";
        $credit=" and w.credit>0";
      }
      if(!empty($msisdn)){
        $where_msisdn=" and w.msisdn='$msisdn'";
      }else{
        $where_msisdn='';
      }

      $GROUP_BY   = "w.createdate";
      $SUM        = "";
      $count      = "";
      $where_date = "";
      $limit      = "";
      $createdate = "";
      $where_default_usergroup = "u.default_usergroup in (14,15) ";
      $where_reference="";


      if($request_type=='all_parent_deposit' || $request_type=='report_monthly' || $request_type=='daily_monthly'){
         $where_default_usergroup = "u.default_usergroup in (14) ";
         $where_order_not_cancelled=" and (SUBSTRING_INDEX(SUBSTRING_INDEX(w.reference, '-', 1), ' ', -1)!='taptuck_order_cancel')";
      }
      if(!empty($request_type) && $request_type=='report_monthly'){
        $GROUP_BY = "SUBSTR(w.createdate, 1, 7) ";
        $SUM = ",sum(w.credit) as total";
        $createdate = "SUBSTR(w.createdate, 1, 7) as report_createdate,";
        $count = "count(w.credit) as deposit_count,";
        $limit = "LIMIT 12";
        $where_default_usergroup = "u.default_usergroup in (14) ";
        $where_reference = " ";
      }

      if(!empty($request_type) && $request_type=='daily_monthly'){
        $GROUP_BY = "SUBSTR(w.createdate, 1, 10) ";
        $SUM = ",sum(w.credit) as total";
        $createdate = "SUBSTR(w.createdate, 1, 10) as report_createdate,";
        $limit = "LIMIT 31";
        $where_default_usergroup = "u.default_usergroup in (14) ";
        $where_reference = " ";
      }

      if(!empty($data['date_from'])){
        $where_date=" and w.createdate>='".$data['date_from']."' and w.createdate<='".$data['date_to']."'";
      }

      $query = $this->db->query("SELECT w.id, 
          u.id,
          $createdate
          $count
          u.name,
          u.email, 
          w.msisdn, 
          g.name as 'group', 
          w.credit, 
          w.debit, 
          w.reference,
          w.createdate
          $SUM
          FROM wallet_transactions w  
          LEFT JOIN aauth_users u ON u.username = w.msisdn
          LEFT JOIN aauth_groups g ON u.default_usergroup = g.id 
          WHERE $where_default_usergroup $where_order_not_cancelled
          $where_msisdn $credit $where_reference $where_date 
          GROUP by $GROUP_BY DESC $limit");
      $wallets = $query->result_array();

      foreach ($wallets as $key => $value) {
        $wal = $this->get_internal_wallet($value['msisdn']);
        if($wal){
          $wallets[$key]['user_id'] = $wal['id'];
          $wallets[$key]['name'] = $wal['name'];
          $wallets[$key]['msisdn'] = $wal['id'];
          $wallets[$key]['email'] = $wal['email'];
          $wallets[$key]['user_group'] = $wal['user_group'];
        }
      }
      return $wallets;
   }

   function get_taptuck_cashout_total($msisdn=''){
    if(!empty($msisdn)){
      $where_msisdn=" and w.msisdn='$msisdn'";
    }else{
      $where_msisdn='';
    }
    $query = $this->db->query("SELECT sum(w.credit) as total
        FROM wallet_transactions w  
        LEFT JOIN aauth_users u ON u.username = w.msisdn
        LEFT JOIN aauth_groups g ON u.default_usergroup = g.id 
        WHERE u.default_usergroup in (14,15) 
        and w.reference='taptuck_cashout'
        $where_msisdn
        ORDER by w.createdate DESC");
      $cashout_total = $query->row_array();
      return $cashout_total['total'];

   }

   function get_deposit($date_from,$date_to){
    if(!empty($date_from) && !empty($date_to)){
      $where_date=" AND t.createdate>='$date_from' AND t.createdate<='$date_to'";
    }else{
      $where_date="";
    }
    $query=$this->db->query("SELECT u.name, g.name as group_name, t.credit, t.reference, t.createdate FROM `wallet_transactions` t JOIN `aauth_users` u ON t.msisdn = u.username JOIN `aauth_groups` g ON u.default_usergroup = g.id WHERE  t.credit != 0 AND (t.reference LIKE '%bank%' OR t.reference LIKE '%card%' OR reference LIKE '%ott_redemption%' OR reference LIKE '%masterpass%') $where_date ORDER BY t.createdate DESC");
    return $query->result_array();
   } 

   function get_credits($date_from,$date_to){
    if(!empty($date_from) && !empty($date_to)){
      $where_date=" AND t.createdate>='$date_from' AND t.createdate<='$date_to'";
    }else{
      $where_date="";
    }
    $query=$this->db->query("SELECT u.name, u.username, g.name as group_name, sum(t.credit) as 'total', count(t.credit) as 'count' FROM `wallet_transactions` t JOIN `aauth_users` u ON t.msisdn = u.username JOIN `aauth_groups` g ON u.default_usergroup = g.id WHERE t.credit != 0 AND t.reference NOT LIKE '%bank%' AND t.reference NOT LIKE '%card%' AND reference NOT LIKE '%ott_redemption%' AND reference NOT LIKE '%masterpass%' $where_date GROUP BY t.msisdn order by total desc");
    return $query->result_array();
   }

   function queue_money_send($from_id, $from_msisdn, $beneficiary_msisdn, $beneficiary_id, $amount){

        $otp = rand(1000, 9999);
        $app = get_app_settings(base_url());
        
        if($this->db->query("INSERT INTO money_send_queue (from_id, from_msisdn, beneficiary_msisdn, beneficiary_id, amount, otp, createdate) VALUES (?,?,?,?,?,?,NOW())", array($from_id, $from_msisdn, $beneficiary_msisdn, $beneficiary_id, $amount, $otp))){

        $message = strtoupper($app['app_name']).": Money Send Transaction to $beneficiary_msisdn queued. Please enter OTP to approve transaction. OTP: $otp ";
        $this->comms_model->send_sms($from_msisdn, $message);

        return true;
        }
        return false;
   }

  function comfirm_money_send_otp($user_id, $otp){
    $query = $this->db->query("SELECT id FROM money_send_queue WHERE from_id = $user_id AND otp = $otp AND processed = 0");

    if($query->num_rows() == 1){
      return true;
    }

    return false;
  }

  function approve_money_send($user_id, $otp){

    $app = get_app_settings(base_url());

    $query = $this->db->query("SELECT * FROM money_send_queue WHERE from_id = $user_id AND otp = $otp AND processed = 0");

    if($query->num_rows() == 1){

      $money_send = $query->row_array();
      
      $reference = "cash_send-".$money_send['id'];

      $this->add_wallet_transaction('debit', $money_send['amount'], $reference, $money_send['from_msisdn']);
      $this->add_wallet_transaction('credit', $money_send['amount'], $reference, $money_send['beneficiary_msisdn']);

      $message = strtoupper($app['app_name']).": Money Send Transaction. R".$money_send['amount']." paid to your wallet from " . $money_send['from_msisdn'] .".";
        $this->comms_model->send_sms($money_send['from_msisdn'], $message);


      $query = $this->db->query("UPDATE money_send_queue SET processed = 1, processed_date = NOW() WHERE from_id = $user_id AND otp = $otp AND processed = 0");

    }

    return true;
  }

   function assign_spark_order_comm($reward, $customer_id, $order_id){

      $data['customer'] = $this->customer_model->get_customer($customer_id);
      if(isset($data['customer']['trader']) && $data['customer']['trader']){
        $data['balance'] = $this->get_wallet_balance($data['customer']['trader']['username']);
        $reference = 'reward_order_comm-'.$order_id;
        $data['reference'] = $reference;
        $data['createdate'] = date("Y-m-d H:i:s");
        $this->add_wallet_transaction('credit', $reward, $reference, $data['customer']['trader']['username']);
        $user = $this->user_model->get_user_from_link_id($data['customer']['trader_id'], 19);
        $this->comms_library->queue_comm_group($user->id, 'add_to_customer_account', $data);
      }
   }

   function remove_spark_order_comm($reward, $customer_id, $order_id){

      $data['customer'] = $this->customer_model->get_customer($customer_id);

      if(isset($data['customer']['trader']) && $data['customer']['trader']){
        $data['balance'] = $this->get_wallet_balance($data['customer']['trader']['username']);
        $reference = 'reverse_order_comm-'.$order_id;
        $data['reference'] = $reference;
        $data['createdate'] = date("Y-m-d H:i:s");
        $this->add_wallet_transaction('debit', $reward, $reference, $data['customer']['trader']['username']);
        $user = $this->user_model->get_user_from_link_id($data['customer']['trader_id'], 19);
        $this->comms_library->queue_comm_group($user->id, 'remove_from_customer_account', $data);
      }
   }

   function calculate_instant_spark_commission($order_total){

    /*
      R0 - R1500    = R5
      R1500 - R3000 = R10
      R3000 - R5000 = R15
      R5000+        = R25
    */

    $reward = 0;

    if($order_total > 1 && $order_total <= 1500){
      $reward = 5;
    }elseif($order_total <= 3000){
      $reward = 10;
    }elseif($order_total <= 5000){
      $reward = 15;
    }elseif($order_total > 5000){
      $reward = 25;
    }
    return $reward;
   }

}
