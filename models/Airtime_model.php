<?php

class Airtime_model extends CI_Model { 

   public function __construct()
   {
      parent::__construct();

        $this->load->model('financial_model');
        $this->load->library('comms_library');

        $this->user = 4438767;
        $this->pass = '084549By';
        $this->wsdl_url = "https://ws.freepaid.co.za/airtimeplus/?wsdl";
   }

/*
  STATUS CODES

  define( "airtimeOKAY", "000" );
  define( "airtimePENDING", "001" );
  define( "airtimeEMPTYORDER", "100" );
  define( "airtimeINVALIDUSER", "101" );
  define( "airtimeINVALIDLAST", "102" );
  define( "airtimeINVALIDPASS", "103" );
  define( "airtimeINVALIDNETWORK", "104" );
  define( "airtimeINVALIDSELLVALUE", "105" );
  define( "airtimeFUNDSEXCEEDED", "106" );
  define( "airtimeOUTOFSTOCK", "107" );
  define( "airtimeINVALIDCOUNT", "108" );
  define( "airtimeINVALIDREFNO", "109" );
  define( "airtimeINVALIDREQUEST", "110" );
  define( "airtimeSTILLBUSY", "111" );
  define( "airtimeINVALIDORDERNUMBER", "112" );
  define( "airtimeINVALIDEXTRA", "113" );
  define( "airtimeINTERNAL", "197" );
  define( "airtimeTEMPORARY", "198" );
  define( "airtimeUNKNOWN", "199" );

*/

   function update_vouchers($voucher){

      if(isset($voucher->description) && $voucher->description != NULL){

          $query = $this->db->query("SELECT * FROM `airtime_vouchers` WHERE description = ? AND network = ? AND sellvalue = ?", array($voucher->description, $voucher->network, $voucher->sellvalue));
          $result = $query->row_array();

      if(count($result) == 5){
          if($result['costprice'] != $voucher->costprice){
            $user_id = 0;
            $catgory = 'airtime_vouchers';
            $action = 'price change';
            $label = $voucher->description . ' ' . $voucher->network . ' ' . $voucher->sellvalue  . ': From: ' . $result['costprice'] . ' To: ' . $voucher->costprice;
            $value = $result['id'];
            $date = date("Y-m-d H:i:s");
            $this->event_model->private_track_event($user_id, $catgory, $action, $label, $value='', $date);

            $this->db->query("UPDATE `airtime_vouchers` SET costprice = ? WHERE id = ?", array($voucher->costprice, $result['id']));
          }
       }else{
        
          $this->db->query("INSERT INTO `airtime_vouchers` (description, network, sellvalue, costprice) VALUES (?,?,?,?)", array($voucher->description, $voucher->network, $voucher->sellvalue, $voucher->costprice));
       }
    }
  }

   function check_purchases(){

      $query = $this->db->query("SELECT * FROM `voucher_purchase_log` WHERE orderno != '-' AND orderno != '' AND status != '113' AND (status = '001' OR status = '111' OR voucher_info = '' OR status = '')"); //OR status = '112'
      $purchases = $query->result_array();

      foreach ($purchases as $key => $value) {
        $info['orderno'] = $value['orderno'];

        if(isset($info['orderno']) && $info['orderno'] != ''){
          $result = $this->airtime('queryOrder',$info);
          $voucher_info = json_encode($result);
        }

        if (isset($result) && isset($result->errorcode) && $result->balance != 0) {
         $this->db->query("UPDATE `voucher_purchase_log` SET status = ?, voucher_info = ? WHERE id = ?", array($result->errorcode, $voucher_info, $value['id']));
        }

      }

  }

  function get_purchase($purchase_id){
      $query = $this->db->query("SELECT * FROM `voucher_purchase_log` WHERE id = $purchase_id");
      $purchase = $query->row_array();
      return $purchase;
  }

  function get_data_vouchers($network='%'){
      //cellc, vodacom, mtn, heita

      //this will return only the pinless data products.
      $query = $this->db->query("SELECT * FROM `airtime_vouchers` WHERE network like 'pd-$network'");
      $result = $query->result_array();
      return $result;
  }

  function get_eskom_vouchers(){

      //this will return only eskom.
      $query = $this->db->query("SELECT * FROM `airtime_vouchers` WHERE network = 'eskom'");
      $result = $query->result_array();
      foreach ($result as $key => $value){
        $result[$key]['description'] = $value['description'];
      }
      return $result;
  }

  function get_airtime_vouchers(){
      //this will return only the pinless airtime products.
      $query = $this->db->query("SELECT * FROM `airtime_vouchers` WHERE network like 'p-%'");
      $result = $query->result_array();
      return $result;
  }

  function get_voucher($id){

      $query = $this->db->query("SELECT * FROM `airtime_vouchers` WHERE id = ?", array($id));
      $result = $query->row_array();
      return $result;
  }


  function buy_voucher($user_id, $voucher, $amount, $cellphone){

        if($voucher != ''){

            $voucher = $this->get_voucher($voucher);

            if($amount != '0'){
                $voucher['sellvalue'] = $amount;
            }

            $user = $this->aauth->get_user($user_id);
            $affordability = false;

            if($voucher['sellvalue'] <= $user->wallet_balance){
              $affordability = true;
            }

            if(!$affordability){
                return array('refno' => false, 'message' => '', 'result' => false);
            }else{

                $refno = $this->voucher_purchase_log($user_id, $voucher['id'], $cellphone,$voucher['sellvalue']);

                $info['refno'] = $refno;
                $info['network'] = $voucher['network'];
                $info['sellvalue'] = $voucher['sellvalue'];
                $info['count'] = 1;
                $info['extra'] = $cellphone;

                $response = $this->airtime('placeOrder',$info);
                $response->refno = $refno;

                if(!isset($response) || !isset($response->message)){
                    $response->orderno = 'failed';
                }

                $this->update_purchase($response);

                $info['orderno'] = $response->orderno;

                $result = false;
                $message = '';

                sleep(5);
                $its_perfect = false;
                for ($i=0; $i < 3; $i++) {

                  $result = $this->airtime('queryOrder',$info);

                  /*
                      [status] => 1
                      [errorcode] => 000
                      [message] => -
                      [balance] => 225.77
                      [orderno] => 2016100709574727
                      [costprice] => 1.9
                  */

                  $voucher_info = json_encode($result);
                  $message = '';
                  if(isset($result->message)){
                    $message = $result->message;
                  }

                  if($result->errorcode == '000' || $result->errorcode == '001'){
                    $its_perfect = true;
                  }

                  if (isset($result) && isset($result->errorcode) && $result->balance != 0) {
                    $this->db->query("UPDATE `voucher_purchase_log` SET status = ?, voucher_info = ? WHERE id = ?", array($result->errorcode, $voucher_info, $refno));
                    if($its_perfect == true){
                        $this->financial_model->airtime_purchase($user->username, $voucher['sellvalue'], $voucher['costprice'], $refno.'-'.$voucher['id'].'-'.$cellphone);
                        $user = $this->aauth->get_user($user_id);
                        //$message = 'Airtime Purchase Successful';
                        
                        //$this->comms_model->send_push_notification($user->pushtoken, $message, 'Airtime Purchase Successful');

                        /*
                        Queuing comms still under test,
                        that the reason I didn't comment-out the send_push_notification function 
                        */

                        $this->comms_library->queue_comm_group($user_id, 'airtime_success',  array('cellphone' =>  $cellphone));//Queueing Comms 
                        return array('refno' => $refno, 'message' => $message, 'result' => $result);
                    }
                  }

                  sleep(5);

                }

              return array('refno' => $refno, 'message' => $message, 'result' => $its_perfect);

            }
        }
    }


    function buy_voucher_newr($user_id, $voucher, $amount, $cellphone){

        set_time_limit(60);

        if($voucher != ''){

            $voucher = $this->get_voucher($voucher);
            
            if($amount != '0'){
                $voucher['sellvalue'] = $amount;
            }

            $user = $this->aauth->get_user($user_id);
            $affordability = false;

            if($voucher['sellvalue'] <= $user->wallet_balance){
              $affordability = true;
            }

            if(!$affordability){
                return array('refno' => false, 'message' => '', 'result' => false);
            }else{

                $refno = $this->voucher_purchase_log($user_id, $voucher['id'], $cellphone,$voucher['sellvalue']);

                $info['refno'] = $refno;
                $info['network'] = $voucher['network'];
                $info['sellvalue'] = $voucher['sellvalue'];
                $info['count'] = 1;
                $info['extra'] = $cellphone;

                $response = $this->airtime('placeOrder',$info);
                $response->refno = $refno;

                print_r($response);
                echo ' | purchase | ';

                if(!isset($response) || !isset($response->message)){
                    $response->orderno = 'failed';
                }

                $this->update_purchase($response);

                $info['orderno'] = $response->orderno;
                $qinfo = array();
                $qinfo['orderno'] = $response->orderno;

                $result = false;
                $message = '';

                sleep(10);
                $its_perfect = false;
                for ($i=0; $i < 4; $i++) { 

                  if(isset($qinfo['orderno']) && $qinfo['orderno'] != '' && strlen($qinfo['orderno']) > 5){

                    $result = $this->airtime('queryOrder',$qinfo);
                  }

                  print_r($result);
                  echo ' | query '.$i.' | ';

                  /*
                      [status] => 1
                      [errorcode] => 000
                      [message] => -
                      [balance] => 225.77
                      [orderno] => 2016100709574727
                      [costprice] => 1.9
                  */

                  $voucher_info = json_encode($result);
                  $message = '';
                  if(isset($result->message)){
                    $message = $result->message;
                  }

                  if($result->errorcode == '000'){
                    $its_perfect = true;
                  }

                  if (isset($result) && isset($result->errorcode) && $result->balance != 0) {
                    $this->db->query("UPDATE `voucher_purchase_log` SET status = ?, voucher_info = ? WHERE id = ?", array($result->errorcode, $voucher_info, $refno));
                    if($its_perfect == true){
                        $this->financial_model->airtime_purchase($user->username, $voucher['sellvalue'], $voucher['costprice'], $refno.'-'.$voucher['id'].'-'.$cellphone);
                        $user = $this->aauth->get_user($user_id);
                        $message = 'Airtime Purchase Successful';
                        $this->comms_model->send_push_notification($user->pushtoken,$message,'Airtime Purchase Successful');
                        return array('refno' => $refno, 'message' => $message, 'result' => $result);
                    }
                  }

                  sleep(4);

                }

              return array('refno' => $refno, 'message' => $message, 'result' => $its_perfect);
              
            }
        }
    }

  function voucher_purchase_log($user_id, $id, $cellphone, $amount){

    $this->db->query("INSERT INTO `voucher_purchase_log` (voucher_id, user_id, cellphone, amount, createdate) VALUES (?,?,?,?,NOW())", array($id, $user_id, $cellphone, $amount));
    $ref_id = $this->db->insert_id();
    return $ref_id;    
  }

  function get_customer_airtime_balance($customer_id){
    $airtime_balance = $this->financial_model->get_airtime_balance($customer_id);
    return $airtime_balance;
  } 

    function update_purchase($info){

        $this->db->query("UPDATE `voucher_purchase_log` SET orderno = ? WHERE id = ?", array($info->orderno, $info->refno));
    }

    function airtime($function,$info){

    /*

        [refno] => 10
        [network] => pd-vodacom
        [sellvalue] => 2
        [count] => 1
        [extra] => 0827378714

    */

    try {

          /* Initialize webservice with your WSDL */
          $client = new SoapClient($this->wsdl_url);

          $login = array(
            "user" => $this->user,
            "pass" => $this->pass
          );

          $settings = array_merge($login,$info);

          $log_id = $this->api_tracker('request', json_encode($settings));

          switch ($function) {
              case 'fetchBalance':
                  $response = $client->fetchBalance($settings);
                  break;
              case 'placeOrder':
                  $response = $client->placeOrder($settings);
                  break;
              case 'fetchOrder':
                  $response = $client->fetchOrder($settings);
                  break;
              case 'fetchOrderLatest':
                  $response = $client->fetchOrderLatest($settings);
                  break;
              case 'queryOrder':
                  if(isset($settings['orderno']) && $settings['orderno'] != '' && strlen($settings['orderno']) > 5){
                        $response = $client->queryOrder($settings);
                  }else{
                        $response = false;
                  }
                  break;
              case 'fetchProducts':
                  $response = $client->fetchProducts($settings);
                  break;
              default:
                  # code...
                  break;
          }

        } catch (SoapFault $fault) {
            trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
        }

        $this->api_tracker('response', json_encode($response), $log_id);

        return $response;
    }


    function api_tracker($type, $data, $id=''){

      if($type == 'request'){

        $this->db->query("INSERT INTO `airtime_api_log` (request, createdate) VALUES (?,NOW())", array($data));
        return $this->db->insert_id();

      }

      if($type == 'response' && $id != ''){

        $this->db->query("UPDATE `airtime_api_log` SET response = ? WHERE id = ?", array($data, $id));

    }
  }
function get_airtime_report($user_id,$status_id,$default_usergroup){
    
    
    if(isset($user_id) && !empty($user_id)){
      $where_user=" AND v.user_id='$user_id'";
      // if(isset($default_usergroup) && !empty($default_usergroup)){
      //   $where_user='';
      // }
    }else{
      $where_user='';
    }

    if(isset($status_id) && $status_id!=''){
      $where_status=" AND v.status='$status_id'";
    }else{
      $where_status='';
    }

    if(isset($default_usergroup) && !empty($default_usergroup)){
      $where_user_group=" AND a.default_usergroup='$default_usergroup'";
    }else{
      $where_user_group='';
    }
  

    $query = $this->db->query("SELECT v.id, a.name,
                          a.username,
                          v.cellphone,
                          v.orderno,
                          at.network,
                          s.name as status,
                          v.status as v_status,
                          v.amount,
                          at.costprice,
                          at.sellvalue,
                          v.createdate,
                          s.code, 
                          ag.name as default_usergroup, 
                          at.id as voucher_id
                          From voucher_purchase_log as v
                          join airtime_vouchers as at 
                          ON at.id=v.voucher_id 
                          JOIN aauth_users as a ON a.id=v.user_id
                          LEFT JOIN aauth_groups as ag ON a.default_usergroup=ag.id
                          LEFT JOIN airtime_status as s
                          ON s.code = v.status
                          WHERE 1 $where_user $where_status $where_user_group
                          GROUP BY v.createdate DESC");
    
    return $query->result_array();
 }

 function get_airtime_report_users(){
    $ids = array(14, 15, 16);
    $this ->db->select("aauth_users.name as username, aauth_users.id as user_id, cellphone");
    $this->db->from("aauth_users");
    return $this->db->get();
 }
function get_airtime_report_stats($user_id, $status_id,$default_usergroup){
    if(isset($user_id) && !empty($user_id)){
      $where_user=" AND v.user_id='$user_id'";
      // if(isset($default_usergroup) && !empty($default_usergroup)){
      //   $where_user='';
      // }
    }else{
      $where_user='';
    }

    if(isset($status_id) && $status_id!=''){
      $where_status=" AND v.status='$status_id'";
    }else{
      $where_status='';
    }

    if(isset($default_usergroup) && !empty($default_usergroup)){
      $where_user_group=" AND a.default_usergroup='$default_usergroup'";
    }else{
      $where_user_group='';
    }
    
    $query=$this->db->query("SELECT count(v.voucher_id) as description_count,
                          at.description,
                          at.network,
                          v.createdate,
                          sum(v.amount) as total
                          From voucher_purchase_log as v
                          JOIN airtime_vouchers as at ON at.id=v.voucher_id 
                          LEFT JOIN airtime_status as s ON s.code = v.status
                          JOIN aauth_users as a ON a.id=v.user_id
                          WHERE 1
                          $where_status
                          $where_user $where_user_group
                          GROUP BY at.network IN('cellc','pd-cellc','p-cellc')
                          , at.network IN('vodacom','pd-vodacom','p-vodacom')
                          , at.network IN('mtn','pd-mtn','p-mtn')
                          , at.network IN('heita','pd-heita','p-heita')
                          , at.network IN('telkom')
                          , at.network IN('neotel')
                          , at.network IN('worldchat')
                          , at.network IN('worldcall')
                          , at.network IN('branson')
                          , at.network IN('bela')
                           LIMIT 50");
   return $query->result();
 }


  function get_airtime_sell_price_total(){
      $this ->db->select("*");
      $this->db->from("voucher_purchase_log");
      $this->db ->join("aauth_users","aauth_users.id=voucher_purchase_log.user_id","left");
      $this->db->join("airtime_vouchers","voucher_purchase_log.voucher_id=airtime_vouchers.id","left");
     
      return  $this->db->get();
     
  }

  // Airtime Charts
  function getCellcCount(){

    $query = $this->db->select("COUNT(voucher_id) as cell_c")
              ->from("voucher_purchase_log")
              ->where("voucher_id", "73")
              ->get();

    $cellc = $query->row();

    return $cellc; 

  }

  function getMtnCount(){

    $query = $this->db->select("COUNT(voucher_id) as mtn")
            ->from("voucher_purchase_log")
            ->where("voucher_id", "75")
            ->get();

    $mtn = $query->row();

    return $mtn;
    
  }

  function getVodacomCount(){

    $query = $this->db->select("COUNT(voucher_id) as vodacom")
            ->from("voucher_purchase_log")
            ->where("voucher_id", "76")
            ->get();

    $vodacom = $query->row();

    return $vodacom;  
    
  }

  function getHeitaCount(){

    $query = $this->db->select("COUNT(voucher_id) as heita")
            ->from("voucher_purchase_log")
            ->where("voucher_id", "74")
            ->get();

    $heita= $query->row();

    return $heita;
    
  }

  function getAirtimeSalesTotal(){
      $date_from = $this->input->post('date_from');
      $date_to = $this->input->post('date_to');
      $where_date='';
      if(!empty($date_to)){
        $where_date="and v.createdate>='$date_from' and v.createdate<='$date_to'";
      }
      $query = $this->db->query("SELECT * FROM airtime_vouchers as at 
                                JOIN voucher_purchase_log as v 
                                ON at.id=v.voucher_id
                                WHERE  v.status='000' $where_date");

      $result = $query->result_array();
      $total=0.00;
      foreach ($result as $voucher) {
       $total += $voucher['amount'];
      }
      return $total;
  }

   function get_total_number_of_airtime_purchase(){
      $date_from = $this->input->post('date_from');
      $date_to = $this->input->post('date_to');
      $where_date='';
      if(!empty($date_to)){
        $where_date="AND v.createdate>='$date_from' and v.createdate<='$date_to'";
      }
      $query = $this->db->query("SELECT * FROM airtime_vouchers as at 
                                JOIN voucher_purchase_log as v 
                                ON at.id=v.voucher_id 
                                WHERE v.status='000'
                                $where_date");

      return $query->num_rows();

      
  }

  function get_airtime_statuses(){
    $query = $this->db->query("SELECT * FROM airtime_status WHERE 1");
    return $query->result_array();
  }
  function get_airtime_statuses_by_id($code){
    $query = $this->db->query("SELECT * FROM airtime_status WHERE code='$code'");
    return $query->row_array();
  }

  function get_user_purchase_stats($user_id, $cellphone){
    $query = $this->db->query("SELECT sum(amount) as 'total', count(id) as 'count' FROM `voucher_purchase_log` WHERE user_id = $user_id AND status != 107 and orderno != '-' and orderno != ''");
    $airtime = $query->row_array();
    $query2 = $this->db->query("SELECT distinct(cellphone) FROM `voucher_purchase_log` where user_id = $user_id AND cellphone != '$cellphone' AND status != 107 and orderno != '-' and orderno != ''");
    $numbers = $query2->num_rows();
    $query3 = $this->db->query("SELECT cellphone FROM `voucher_purchase_log` where user_id = $user_id AND cellphone = '$cellphone' AND status != 107 and orderno != '-' and orderno != ''");
    $personal = $query3->num_rows();

    return array('count' => $airtime['count'], 'total' => $airtime['total'], 'unique_numbers' => $numbers, 'self_sales' => $personal);
  }

  function update_status($voucher_log_id, $status_code){

        $this->db->query("UPDATE `voucher_purchase_log` SET status = $status_code WHERE id = ?", array($voucher_log_id));
  }

}
