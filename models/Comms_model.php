<?php

class Comms_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      // API access key from Google API's Console
      define( 'SPAZAPP_API_ACCESS_KEY', 'AAAAeP5jJRU:APA91bHyAmr-dXQa_uj-L7HxRtcG1b-N6yBLTgFvSJg_lYJuG47qM10fiHoMASxuxD49AHXZt7dclJvEWp3616dRcJ4_n_9RCznmfFalS-8Y9SyjOfaZwlcYP2KHQ7fT7PLosbCjUqU-RE0UDy3pmkQiyStNaf6lng' );
      /*define( 'TAPTUCK_API_ACCESS_KEY', 'AIzaSyBwkMeYn6Sx-odoIjS4fObhU2jE6CVNahQ' );*/
      /*define( 'TAPTUCK_API_ACCESS_KEY', 'AIzaSyBTCY20YBmGlNTkZD91-yHrxc9-ePiDNe0' );*/
      define( 'TAPTUCK_API_ACCESS_KEY', 'AAAALi-Lp2o:APA91bFUJYB8rx_vK-exIzHnu4_6xYFeYtMhq-2HEGgjM0EWQ49mDyib-vfzOWF9Le_qkJV2JPPPGV7cKnu46-yTMGNQ8bLYPmS9LhsOI3DIZm5lGK5pGATBWK4TW3PaIEzxzbBt7OrO');
   }

   function fetch_email_subject($template, $data){

      switch (base_url()) {
         case 'http://demo.supps365.co.za/':
            $company = 'SUPPS365';
            break;
         
         default:
            $company = 'SUPPS365';
            break;
      }

      $subjects = array(
         'add_to_customer_account' => "$company Funds have been added to your account.",
         'add_to_customer_airtime_account' => "$company Funds have been added to your account.",
         'remove_from_customer_account' => "$company You have made a purchase from your account.",
         'remove_from_customer_airtime_account' => "$company You have made an airtime purchase.",
         'order_invoice' => "$company Order Placed | {order_id}",
         'sale_order_placed' => "$company Order Placed | {order_id}",
         'pos_order_placed' => "$company POS Order Placed | {order_id}",
         'distributor_order' => "$company New Order | {order_id}",
         'welcome' => "$company Welcomes you!",
         'specials_sales' => "SPAZAPP SPECIALS SALES:"

         );

      if(isset($subjects[$template])){
         if(is_array($data) && count($data) >= 1){
            $subject = $this->replace_message_data($subjects[$template], $data);
         }else{
            $subject = $subjects[$template];
         }
      }else{
         die('EMAIL TEMPLATE DOES NOT EXIST');
      }

      return $subject;

   }

   function fetch_sms_message($template, $data){

      switch (base_url()) {
         case 'http://demo.supps365.co.za/':
            $company = 'SUPPS365';
            break;
         
         default:
            $company = 'SUPPS365';
            break;
      }

      if(isset($data['default_app'])){
         switch ($data['default_app']) {
            case 'spazapp':
               $company = 'SPAZAPP';
               break;
            case 'insurapp':
               $company = 'INSURAPP';
               break;
            case 'taptuck':
               $company = 'TAPTUCK';
               break;
            case 'supps365':
               $company = 'SUPPS365';
               break;
            
            default:
               $company = 'SUPPS365';
               break;
         }
      }

      $messages = array(
         'add_to_customer_account' => "$company R{amount} paid to your account. Avail R{balance}. Ref.{reference}. {createdate}",
         'add_to_customer_airtime_account' => "$company R{amount} added to your airtime account. Avail R{airtime_balance}. Ref.{order_item_id}. {createdate}",
         'remove_from_customer_account' => "$company Account Purchase made to the value of R{amount}. Avail R{balance}. Ref.{reference}. {createdate}",
         'remove_from_customer_airtime_account' => "$company Airtime purchase to the value of R{amount}. Avail R{airtime_balance}. Ref.{order_item_id}. {createdate}",
         'remove_from_customer_rewards' => "$company Rewards purchase to the value of R{amount}. Avail {rewards}. Ref.{order_item_id}. {createdate}",
         'welcome' => "$company Welcome to Spazapp! Username: {username} Password: {password} OTP: {otp}",
         'resend_otp' => "$company OTP: {otp}. Use this OTP to verify your account.",
         'first_order_reward' => "SPAZAPP REWARDS: We have topped up your account with R20.00 FREE to start ordering and selling airtime today!",
         'sale_order_placed' => "$company Your order has been placed! Order No: {order_id} Delivery Type: {delivery_type}. Total: R{total}.",
         'pos_order_placed' => "$company Your POS order has been placed! Order No: {order_id} Delivery Type: {delivery_type}. Total: R{total}.",
         'order_approved' => "$company Your order has been approved and is pending delivery. Order No: {order_id}. For queries call 031 537 3912",
         'driver_on_route' => "$company Your order is on its way. Order No: {order_id}. Delivery Total: {total} For queries call 031 537 3912",
         'order_delivered' => "$company Your order has been delivered. Order No: {order_id}. For queries call 031 537 3912",
         'order_cancelled' => "$company Your order has been cancelled. Order No: {order_id}. For queries call 031 537 3912",
         'order_complete' => "$company Your order has been completed. Order No: {order_id}. For queries call 031 537 3912",
         'reset_password' => "$company Your password has been reset. Username: {username} Password: {password} ",
         'specials_sales' => "SPAZAPP SPECIALS SALES:"
         );

      if(isset($messages[$template])){
         if(is_array($data) && count($data) >= 1){
            $message = $this->replace_message_data($messages[$template], $data);
         }else{
            $message = $messages[$template];
         }
      }else{
         die('SMS TEMPLATE DOES NOT EXIST: '.$template);
      }

      return $message;

   }

   function replace_message_data($message, $data){


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

   function send_sms($to, $message){

         if($_SERVER['HTTP_HOST'] == 'admin.spazapp.bv' || $_SERVER['HTTP_HOST'] == 'admin.taptuck.bv'){
            $to = '0827378714';
         }
         
         $to ='+27' . substr($to,-9);

         $message = trim($message);
         /*$url = "http://api.clickatell.com/http/sendmsg?user=logix_design&password=UmDqhJBY&api_id=3533336&to=$to&text=$message";
         $result = file_get_contents($url);*/


         $fields = array
         (
            'content'   => $message,
            'to'         => array($to)
         );
          
         $headers = array
         (
            'Authorization: BCc4PesHQq-62vi7Ncop-g==',
            'Content-Type: application/json',
            'Accept: application/json'
         );

         $url = 'https://platform.clickatell.com/messages';

         $ch = curl_init();
         curl_setopt( $ch,CURLOPT_URL, $url );
         curl_setopt( $ch,CURLOPT_POST, true );
         curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
         curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
         curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
         curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
         $result = curl_exec($ch );
         if(curl_error($ch)){
             $result = 'error:' . curl_error($ch);
         }
         curl_close( $ch );

      	$this->db->query("INSERT INTO `sms_log` (`to`, `message`, `url`, `result`, `createdate`) VALUES (?,?,?,?,NOW())", array($to, urldecode($message), $url, $result));
   }

   function send_support_mailer($user, $subject, $body){

        $data['template'] = 'support';
        $data['subject'] = 'SPAZAPP '. $subject;
        $data['message'] = (array) $user;
        $data['message']['body'] =  $body;
        $to = 'thabisongubane1992@gmail.com';
        //$to = 'support@spazapp.co.za, support@spazapp.zohosupport.com, martin@spazapp.co.za, kate@spazapp.co.za, tim@spazapp.co.za';

        $this->send_email($to, $data);
        return true;
   }




   function send_email($to, $data){

      $pos = strpos($to, '@umsstokvel');    
      $cell = str_replace('@umsstokvel.co.za', '', $to);

      $allowed_hosts = array('admin.spazapp.co.za', 'demo.spazapp.co.za','admin.spazapp.bv','umsstokvel.mm:8012','www.umsstokvel.co.za',"stockvel.mm");
      if ((isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $allowed_hosts)) || $pos !== false) {

         if(strlen($cell) == 10){
            return;
         }

         if($to == ''){
            $to = 'help@spazapp.co.za';
         }

         if($_SERVER['HTTP_HOST'] == 'admin.spazapp.bv'){
            $to = 'byron@spazapp.co.za';
         }

         $this->load->library('email');

         $config['mailtype']      = 'html';
         $config['protocol']    = 'smtp';
         $config['smtp_host']    = 'ssl://smtp.sendgrid.net';
         $config['smtp_port']    = '465';
         $config['smtp_timeout'] = '7';
         $config['smtp_user']    = 'Spazapp';
         $config['smtp_pass']    = 'M3ss1aH99';

         $this->email->initialize($config);

         $this->email->from('admin@umsstokvel.co.za', 'UMSSTOKVEL');
         $this->email->to($to); 
         //$this->email->bcc('byzz.verr@gmail.com');

         $this->email->subject($data['subject']);
         $this->email->message($this->load->view('stokvel/emails/'.$data['template'], $data['message'], TRUE));
         
         $this->email->send();

         $this->db->query("INSERT INTO `email_log` (`to`, `template`, `createdate`) VALUES (?,?,NOW())", array($to, $data['template']));

      }

   }

   function send_insurapp_email($to, $data){

      $pos = strpos($to, '@spazapp');    
      $cell = str_replace('@spazapp.co.za', '', $to);

      $allowed_hosts = array('admin.spazapp.co.za', 'demo.spazapp.co.za','admin.spazapp.bv');
      if ((isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $allowed_hosts)) || $pos !== false) {

         if(strlen($cell) == 10){
            return;
         }

         if($to == ''){
            $to = 'help@spazapp.co.za';
         }

         if($_SERVER['HTTP_HOST'] == 'admin.spazapp.bv'){
            $to = 'byron@spazapp.co.za';
         }

         $this->load->library('email');

         $config['mailtype']      = 'html';
         $config['protocol']    = 'smtp';
         $config['smtp_host']    = 'ssl://smtp.sendgrid.net';
         $config['smtp_port']    = '465';
         $config['smtp_timeout'] = '7';
         $config['smtp_user']    = 'Spazapp';
         $config['smtp_pass']    = 'M3ss1aH99';

         $this->email->initialize($config);

         $this->email->from('admin@spazapp.co.za', 'INSURAPP');
         $this->email->to("mpho@spazapp.co.za"); 
         $this->email->bcc('byzz.verr@gmail.com');

         $this->email->subject($data['subject']);
         $this->email->message($this->load->view('insurapp/emails/'.$data['template'], $data['message'], TRUE));
         $this->load->view('insurapp/emails/'.$data['template'], $data['message']);
         $this->email->send();

         $this->db->query("INSERT INTO `email_log` (`to`, `template`, `createdate`) VALUES (?,?,NOW())", array($to, $data['template']));
         
      }

   }

    function send_taptuck_email($to, $data, $comm_queue_id, $attempts){

     $pos = strpos($to, '@spazapp');    
      $cell = str_replace('@spazapp.co.za', '', $to);

       $allowed_hosts = array('admin.spazapp.co.za', 'demo.spazapp.co.za','admin.spazapp.bv');
      if ((isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], $allowed_hosts)) || $pos !== false) {

         if(strlen($cell) == 10){
            return;
         }

         if($to == ''){
            $to = 'help@spazapp.co.za';
         }

         if($_SERVER['HTTP_HOST'] == 'admin.spazapp.bv'){
            $to = 'byron@spazapp.co.za';
         }

         $this->load->library('email');

         $config['mailtype']      = 'html';
         $config['protocol']    = 'smtp';
         $config['smtp_host']    = 'ssl://smtp.sendgrid.net';
         $config['smtp_port']    = '465';
         $config['smtp_timeout'] = '7';
         $config['smtp_user']    = 'Spazapp';
         $config['smtp_pass']    = 'M3ss1aH99';

         $this->email->initialize($config);

         $this->email->from('admin@taptuck.co.za', 'TAPTUCK');
         $this->email->to($to); 
         $this->email->bcc('byzz.verr@gmail.com');

         $this->email->subject($data['subject']);
         $this->email->message($this->load->view('taptuck/emails/'.$data['template'], $data['message'], TRUE));
         //this->load->view('taptuck/emails/'.$data['template'], $data['message']);
         $this->email->send();


         $this->db->query("INSERT INTO `email_log` (`to`, `template`, `createdate`) VALUES (?,?,NOW())", array($to, $data['template']));
         $this->db->query("UPDATE comm_queue Set status=1, attempts=?, results=? where id=?", array($attempts, '', $comm_queue_id));///Updating queued comms status as sent


      }



   }

   function push_notification($user_id, $app, $message){
      $this->load->library("Aauth");
      $token = $this->aauth->get_pushtoken($user_id,$app);
      if($token){
         $this->send_push_notification($token,$message,$message);
      }
   }

function send_push_notification($token, $msg, $title, $user_id=''){
   // prep the bundle
   $registrationIds = array( $token );
   $msg = trim($msg);
   $message = array
   (

      'body'   => $msg,
      'title'     => $title,
      'subtitle'  => $title,
      'tickerText'=> $msg,
      'vibrate'   => 1,
      'sound'     => 1,
      'largeIcon' => 'large_icon',
      'smallIcon' => 'small_icon'
   
   );

   $fields = array
   (
      'registration_ids'   => $registrationIds,
      'data'               => $message
   );
    
   $headers = array
   (
      'Authorization: key=' . SPAZAPP_API_ACCESS_KEY,
      'Content-Type: application/json'
   );

   $ch = curl_init();
   curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
   curl_setopt( $ch,CURLOPT_POST, true );
   curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
   curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
   curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
   curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
   $result = curl_exec($ch );
   curl_close( $ch );

   $data = json_decode($result);

   if($data->success==1){
      $this->db->query("UPDATE comm_queue Set status=1 where user_id='$user_id'");
   }
 
   return $result;
}

function test_taptuck_push($token,$msg,$title){
   // prep the bundle
   $registrationIds = array( $token );
   $msg = trim($msg);
   $message = array
   (

      'body'   => $msg,
      'title'     => $title,
      'vibrate'   => 1,
      'sound'     => 1,
      'icon'   => ''
   );

   $fields = array
   (
      'to'=> $token,
      'notification'   => $message
   );
    
   $headers = array
   (
      'Authorization: key=' . TAPTUCK_API_ACCESS_KEY,
      'Content-Type: application/json'
   );

   $ch = curl_init();
   curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
   curl_setopt( $ch,CURLOPT_POST, true );
   curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
   curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
   curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
   curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
   $result = curl_exec($ch );
   curl_close( $ch );

/*   if(isset($ch)){
   echo '<pre>';
   print_r($fields);
   }*/
 
   return $result;
}

function get_comm($template){
       $query = $this->db->query("SELECT * FROM `comms` WHERE template = '$template'");
       return $query->row_array();
}


 function fetch_notification_message($template, $data){
      $comm_info=$this->get_comm($template);
      $messages = array(
         $comm_info['template'] => $comm_info['copy']
         );

      if(isset($messages[$template])){
         if(is_array($data) && count($data) >= 1){
            $message['message'] = $this->replace_message_data($messages[$template], $data);
            $message['copy']=$comm_info['copy'];
            $message['id']=$comm_info['id'];
            $message['title']=$comm_info['title'];
         }else{
            $message['message'] = $messages[$template];
            $message['copy']=$comm_info['copy'];
            $message['id']=$comm_info['id'];
            $message['title']=$comm_info['title'];
         }
      }

      return $message;

   }


 

}
