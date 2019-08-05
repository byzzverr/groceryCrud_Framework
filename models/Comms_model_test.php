<?php

class Comms_model_test extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      // API access key from Google API's Console
     
         //  define( 'SPAZAPP_API_ACCESS_KEY', 'AAAAeP5jJRU:APA91bHyAmr-dXQa_uj-L7HxRtcG1b-N6yBLTgFvSJg_lYJuG47qM10fiHoMASxuxD49AHXZt7dclJvEWp3616dRcJ4_n_9RCznmfFalS-8Y9SyjOfaZwlcYP2KHQ7fT7PLosbCjUqU-RE0UDy3pmkQiyStNaf6lng' );
      
         // define( 'TAPTUCK_API_ACCESS_KEY', 'AIzaSyBwkMeYn6Sx-odoIjS4fObhU2jE6CVNahQ' );
         // /*define( 'TAPTUCK_API_ACCESS_KEY', 'AIzaSyBTCY20YBmGlNTkZD91-yHrxc9-ePiDNe0' );*/
         // define( 'TAPTUCK_API_ACCESS_KEY', 'AAAALi-Lp2o:APA91bFUJYB8rx_vK-exIzHnu4_6xYFeYtMhq-2HEGgjM0EWQ49mDyib-vfzOWF9Le_qkJV2JPPPGV7cKnu46-yTMGNQ8bLYPmS9LhsOI3DIZm5lGK5pGATBWK4TW3PaIEzxzbBt7OrO');

      $this->app_settings = get_app_settings(base_url());
      
   }


    public function get_com_group($com_group){
        $query=$this->CI->db->query("SELECT * FROM comms WHERE `group` IN($com_group_id)");
        return $query->result_array();

    }

function push_notification($user_id, $app, $message, $comm_queue_id, $attempts){
      $this->load->library("Aauth");
      $token = $this->aauth->get_pushtoken($user_id, $app);
      if($token){
         $this->send_push_notification($token,$message,$message, $comm_queue_id, $attempts);
      }
   }

function send_push_notification($token, $msg, $title, $comm_queue_id='', $attempts){
  error_reporting(0);//PHP error turned off
   $attempts=$attempts+1;
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

   if(!empty($token)){

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

      $data = json_decode($result, true);

      $url = 'https://fcm.googleapis.com/fcm/send';

      $status = 0;
      if($data['success'] == 1){
         $status = 1;
      }else{

    // Sends Push notification for iOS users

        $deviceToken = $token;

    // (iOS) Private key's passphrase.
    $passphrase = 'cyberia';

        $ctx = stream_context_create();
        // ck.pem is your certificate file
        stream_context_set_option($ctx, 'ssl', 'local_cert', 'taptuck.pem');
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
        $url = 'ssl://gateway.sandbox.push.apple.com:2195';
        // Open a connection to the APNS server
        $fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp){
            $result = "Failed to connect: $err $errstr" . PHP_EOL;
         }else{

           // Create the payload body
           $body['aps'] = array(
               'alert' => array(
                   'title' => $title,
                   'body' => $msg
               ),
               'sound' => 'default'
           );

           // Encode the payload as JSON
           $payload = json_encode($body);

           // Build the binary notification
           $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

           // Send it to the server
           $result = fwrite($fp, $msg, strlen($msg));

           // Close the connection to the server
           fclose($fp);

           if (!$result){
               $result = 'Message not delivered' . PHP_EOL;
               $status = 1;
           }
           else{
               $result = 'Message successfully delivered' . PHP_EOL;
               $status = 0;
           }

         }

      }
      
   }else{
      $status = 0;
      $attempts = 3;
      $result = 'no push token';
   }

   return array("result"=>$result, "status"=>$status);
   //print_r($result);
 
   $this->db->query("INSERT INTO `push_notifications_log` (`push_token`, `message`, `url`, `result`, `createdate`,`comm_queue_id`) VALUES (?,?,?,?,NOW(),?)", 
      array($token, json_encode($message), $url, $result, $comm_queue_id));
   $this->db->query("UPDATE comm_queue Set status = $status, attempts='$attempts', results = '$result' where id=$comm_queue_id");///Updating queued comms status as sent
   return $result;
}


public function send_sms($to, $message, $comm_queue_id, $attempts){
      $attempts=$attempts+1;
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

     //print_r($result);

      return $result;

      $this->db->query("INSERT INTO `sms_log` (`to`, `message`, `url`, `result`, `createdate`) VALUES (?,?,?,?,NOW())", array($to, urldecode($message), $url, $result));
      $this->db->query("UPDATE comm_queue SET status=1, attempts='$attempts', results = '$result' WHERE id='$comm_queue_id'");///Updating queued comms status as sent
   }

   public function send_email($to, $data, $comm_queue_id, $attempts){
         $attempts=$attempts+1;
         $pos = strpos($to, '@umsstokvel');    
         $cell = str_replace('@umsstokvel.co.za', '', $to);

            if($to == ''){
               $to = 'help@umsstokvel.co.za';
            }

            if($_SERVER['HTTP_HOST'] == 'admin.umsstokvel.bv'){
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
            $this->email->bcc('byzz.verr@gmail.com');

            $this->email->subject($data['subject']);
            $this->email->message($this->load->view($this->app_settings['app_folder'].'emails/'.$data['template'], $data['message'], TRUE));
            //$this->load->view('emails/'.$data['template'], $data['message']);
            $result = 'failed';
            if($this->email->send()){
               $result = 'success';
            }

            return $result;

           // print_r($result);

            $this->db->query("INSERT INTO `email_log` (`to`, `template`, `createdate`) VALUES (?,?,NOW())", array($to, $data['template']));
            $this->db->query("UPDATE comm_queue Set status=1, attempts='$attempts', results='$result' where id='$comm_queue_id'");///Updating queued comms status as sent
   }

  function send_distributor_order_comms($dis_order_id,$distributor_id){

      $data = array();

      $data['order_info'] = $this->order_model->get_dis_order_info($dis_order_id);

      $distributor =  $this->get_distributor($distributor_id);
      $this->send_email($distributor['email'], array('template' => $type, 'subject' => $subject, 'message' => $data));
   
}



}