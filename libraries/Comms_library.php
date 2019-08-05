<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Comms_library {

public function __construct() {

        // get main CI object
        $this->CI = & get_instance();

        // Dependancies
        $this->CI->load->library('session');
        $this->CI->load->library('email');
        $this->CI->load->library('aauth');
        $this->CI->load->database();
        $this->CI->load->helper('url');
        $this->CI->load->helper('string');
        $this->CI->load->helper('email');
        $this->CI->load->helper('language');
        $this->CI->lang->load('aauth');
        $this->CI->load->model('user_model');
        $this->CI->load->model('comms_model_test');
       
        $this->app_settings = get_app_settings(base_url());

        // config/aauth.php
        $this->CI->config->load('aauth');
        $somestupidthing = $this->CI->config->item('aauth');


        $this->config_vars =& $somestupidthing;
        //$pushtoken = "eiHgbzXvdbk:APA91bEdsGWklmoXTv0SpQN_dn30bkxu0MzQMCr2Iz-l_e5_jv4ZueebPQtN_ajgyrM4Q0y0C3XZihalruk6IwPhn5r69GFPF3iymoKWDzn9eUYNu_D-VUBS1t_WUS-IHVil-xk7q0St"; //Byron
    }
    
public function send_queued_comms($user_id, $comm_queue_id, $force_send=false){

  echo '<pre>';
      $message="";
      $queued_comms = $this->get_queued_comms($user_id, $comm_queue_id, $force_send); //Getting the queued comms with the status of 0
      foreach ($queued_comms as $key => $row) {
        if(isset($row['comm_id'])){ 
          $comm = $this->get_comm($row['comm_id']); //Getting the comms assigned to queued comms

            $data = json_decode($row['json'], true); //Converting Json to array
            $template = $comm['template'];
            $messages = array("$template" =>$comm['copy'],);

            if(is_array($data) && count($data) >= 1){
                $message = $this->replace_message_data($messages[$template], $data);
            }else{
                $message = $messages[$template];
            }

            if($row['user_type'] == 'user'){
              $user = $this->CI->aauth->get_user($row['user_id']);//getting user

              if($user->default_usergroup==8){
                $customer = $this->get_customer($row['user_id']); //getting customer info 
                $data['customer'] = $customer;
              }else{
                $customer='';
              }
            }

            if($row['user_type'] == 'distributor'){
                $this->CI->load->model('spazapp_model');
                $user = $this->CI->spazapp_model->get_distributor_object($row['user_id']);//getting user         
            }

            $attempts[$key] = $row['attempts'];//Getting the pre attempts
            $subject =  ucfirst($comm['app'])." : ".$comm['title'];

            //Send SMS
            if($comm['type']=="sms"){
                if(is_array($data) && count($data) >= 1){
                    $message = $this->replace_message_data($messages[$template], $data);
                }else{
                    $message = $messages[$template];
                }

                $this->CI->comms_model_test->send_sms($user->cellphone, $message , $row['id'], $attempts[$key]);
            }
          
            //Send push notification if is less than 3
            if($comm['type']=="push_notification" && $comm['id'] != 1 && $row['attempts'] < 3){
                $pushtoken = $this->CI->aauth->get_pushtoken($row['user_id'], $comm['app']);
                $this->CI->comms_model_test->send_push_notification($pushtoken, $message, $subject, $row['id'], $attempts[$key]);
            }

            //Send Email
            if($comm['type']=="email") { 
                 
                 if(!empty($customer)){
                    $email = $customer['email'];          
                 }else{
                    $email = $user->email;
                 }
                 
                 if(isset($data['order_info']['order_id'])){
                    $subject =  ucfirst($comm['app'])." : ".$comm['title']." | ".$data['order_info']['order_id'];
                 }

                 //Sending Distributor order
                 if($row['comm_id']==39){
                   $distributor = $this->CI->spazapp_model->get_distributor($data['order_info']['distributor_id']);
                   $email = $distributor['email'];
                 }

                 ///Support emails
                 if($comm['id']==20){

                    $email = 'support@spazapp.co.za, support@spazapp.zohosupport.com, tim@spazapp.co.za';
                 }

            
                 $this->CI->comms_model_test->send_email($email, array('template' => $template, 'subject' =>$subject, 'message' => $data), $row['id'], $attempts[$key]);


                 /*$output = $this->CI->load->view($this->app_settings['app_folder'].'emails/'.$template, $data);
                 echo $output->output->final_output;*/
                
            }
        }
        
          if($row['attempts'] < 3){
              echo  $row['id'] . " - " . $comm['type'] . ' - ' . $message . ' - ' . $template ;
          }
      
        echo '<br/>';

      }

      echo '</pre>';

  }
   
  public function queue_comm($user_id, $com_id, $com_json, $user_type='user'){
        $json_data = json_encode($com_json);
        if ($this->CI->db->table_exists('comm_queue') )
        {
         $this->CI->db->query("INSERT INTO comm_queue (comm_id,user_id,user_type,status,json,createdate) values('$com_id','$user_id','$user_type',0,'".addslashes($json_data)."',now())"); 
       }
  }

  public function queue_comm_group($user_id, $group, $com_json, $user_type='user'){
      $json_data = json_encode($com_json);
      if ($this->CI->db->table_exists('comm_queue') )
        {
          $comms = $this->get_comms($group);
          foreach ($comms as  $row) {
            if($user_id>0){
             $this->CI->db->query("INSERT INTO comm_queue (comm_id,user_id,user_type,status,json,createdate) values(".$row['id'].",'$user_id','$user_type',0,'".addslashes($json_data)."',now())"); 
            }

          } 
        }

  }

  public function get_comms($group){
    $query  = $this->CI->db->query("SELECT * FROM comms WHERE `group`='$group' AND status = 'Enabled'");
    return $query->result_array();

  }

  public function get_queued_comms($user_id, $comm_queue_id, $force_send=false){
    
    $where_user_comm='';

    if(!empty($user_id) && !empty($comm_queue_id)){
        $where_user_comm=" and id='$comm_queue_id'";
    }

    if(!$force_send){
        $where_user_comm .=" AND status='0'";
    }
  
    $query  = $this->CI->db->query("SELECT * FROM comm_queue WHERE attempts < 3 $where_user_comm");
    return $query->result_array();

  }

  public function get_comm($id){
    $query  = $this->CI->db->query("SELECT * FROM comms WHERE `id`='$id' AND status = 'Enabled'");
    return $query->row_array();
  }

  public function replace_message_data($message, $data){


    foreach ($data as $key => $value) {
       if(is_array($value)){
          foreach ($value as $key2 => $value2) {
             if(is_array($value2)){
                continue;
             }
            $message = str_replace('{'.$key2.'}', $value2, $message);
          }
       }else{
          $message = str_replace('{'.$key.'}', $value, $message);
       }
    }

    return $message;

 }

 public function get_customer($user_id){
   $query = $this->CI->db->query("SELECT c.*, u.id as 'user_id' 
                                    FROM customers c, aauth_users u 
                                    WHERE u.user_link_id = c.id 
                                    AND u.id  = '$user_id'");

   return $query->row_array();
 }


public function send_order_comms($order_id=811){
      $data = array();
      $data['order_info'] = $this->CI->order_model->get_order_info($order_id);
      $type = $data['order_info']['order_type'] . '_order_placed';
      $sub_data = array('order_id' => $data['order_info']['id']);
      $subject = $this->CI->comms_model->fetch_email_subject($type, $sub_data);
      $sub_data = array('order_id' => $data['order_info']['id'], 'delivery_type' => $data['order_info']['delivery_type'], 'total' => $data['order_info']['items']['total_amount']);

      echo '<pre>';
      print_r($data);

  }


  function get_recent_messages($user_id){

    $sql = "SELECT q.id, c.title, c.copy, q.json, q.createdate  FROM comm_queue q , comms c WHERE q.comm_id = c.id AND c.type = 'push_notification' AND c.group = 'marketing' AND q.user_id = $user_id AND q.user_type = 'user' order by q.createdate DESC limit 5";
    $query = $this->CI->db->query($sql);
    $marketing = $query->result_array();

    $sql = "SELECT q.id, c.title, c.copy, q.json, q.createdate 
    FROM comm_queue q , comms c 
    WHERE q.comm_id = c.id 
    AND c.type = 'push_notification' 
    AND c.group != 'marketing' 
    AND q.user_id = $user_id 
    AND q.user_type = 'user' 
    order by q.createdate DESC limit 10";

    $query = $this->CI->db->query($sql);

    $other = $query->result_array();

    $messages = array_merge($marketing, $other);

    foreach ($messages as $key => $message) {
      $json = json_decode($message['json'], TRUE);

      foreach ($json as $search => $replace) {
        if(is_array($replace)){
            foreach ($replace as $search2 => $replace2) {
              $messages[$key]['copy'] =  str_replace('{'.$search2.'}', $replace2, $messages[$key]['copy']);
            }
        }else{
            $messages[$key]['copy'] =  str_replace('{'.$search.'}', $replace, $messages[$key]['copy']);
        }
        unset($messages[$key]['json']);
      }
    }
    return $messages;

  }

  function create_comm($data){
    unset($data["user_id"]);
    $this->CI->db->insert("comms", $data);
    return $this->CI->db->insert_id();
  }


}
