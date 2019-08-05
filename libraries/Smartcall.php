<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Rest Controller
 * A fully RESTful server implementation for CodeIgniter using one library, one config file and one controller.
 *
 * @package         CodeIgniter
 * @subpackage      Libraries
 * @category        Libraries
 * @author          Byron Verreyne
 * @version         1.0.0
 */

class Smartcall{

    private $_CI;

    public function __construct()
    {
        $this->_CI = &get_instance();

        ini_set("soap.wsdl_cache_enabled", "0");

        $this->_CI->load->config('smartcall');
        $this->smartcall_service_endpoint = $this->_CI->config->item('smartcall_service_endpoint');
        $this->smartcall_username = $this->_CI->config->item('smartcall_username');
        $this->smartcall_password = $this->_CI->config->item('smartcall_password');
        $this->bearer = $this->get_bearer();
    }

    function save_bearer($bearer){

        $this->_CI->db->query("DELETE FROM third_party_tokens WHERE app = 'smartcall_bearer'");

        $validtill = date("Y-m-d H:i:s",strtotime("+30 days"));
        $data = array(
                'app' => 'smartcall_bearer',
                 'token' => $bearer,
                 'createdate' => date("Y-m-d H:i:s"),
                 'validtill' => $validtill
             );
        $this->_CI->db->insert("third_party_tokens", $data);

    }

    function get_bearer(){
        
        $query = $this->_CI->db->query("SELECT token FROM third_party_tokens WHERE app = 'smartcall_bearer'");
        if($query->num_rows() > 0){
            $result = $query->row_array();
           return  $result['token'];
        }else{
            return '';
        }
    }

    function auth($flush=false){

        $function = 'auth';
        if($flush){
            $function = 'auth/token';
            $result = $this->simpleRestCall('DELETE', $function);
            $function = 'auth';
        }
        $result = $this->simpleRestCall('POST', $function);

        if(isset($result['accessToken'])){

            $this->save_bearer($result['accessToken']);
            $this->bearer = $result['accessToken'];

        }else{
            die("Smartcall could not Authenticate");
        }
    }

    function get_networks(){

        $function = 'smartload/networks';
        $result = $this->simpleRestCall('GET', $function);
        return $result;
    }

    function get_network($network_id){

        $function = 'smartload/network';
        $result = $this->simpleRestCall('GET', $function);
        return $result;
    }


    function get_product($product_id){

        $function = 'smartload/products/'.$product_id;
        $result = $this->simpleRestCall('GET', $function);
        return $result;
    }


    function purchase($product_id, $msisdn, $user_id, $amount, $device_id=''){


        $data = array(
          "smartloadId" => '27827378714',
          "clientReference" => $user_id.date("ymdHis"),
          "smsRecipientMsisdn"  => $msisdn,
          "deviceId"    => $device_id,
          "productId"   => $product_id,
          "amount"  => $amount,
          "pinless" => false,
          "sendSms" => true
        );

        $this->insert_purchase($data);

        $function = 'smartload/recharges';
        $result = $this->simpleRestCall('POST', $function, $data);

        $result_clean = array();
        
        if(isset($result['recharge']) && is_array($result['recharge'])){
            $result_clean = $result['recharge'];
            $result_clean['error'] = $result['error'];
            $result_clean['responseCode'] = $result['responseCode'];
        }else{
            if(isset($result['message'])){
                $result_clean['code'] = $result['code'];
                $result_clean['message'] = $result['message'];
            }
        }

        $this->update_purchase($data['clientReference'], $result_clean);
        return $result;
    }

    function query($reference){

        $function = "smartload/recharges/27827378714/$reference";
        $result = $this->simpleRestCall('GET', $function);

        if(isset($result['transaction']) && is_array($result['transaction'])){
            $result_clean = $result['transaction'];
            $result_clean['error'] = $result['error'];
            $result_clean['responseCode'] = $result['responseCode'];
            unset($result_clean['amount']);
            unset($result_clean['cost']);
        }else{
            if(isset($result['message'])){
                $result_clean['code'] = $result['code'];
                $result_clean['message'] = $result['message'];
            }
        }

        $this->update_purchase($reference, $result_clean);

        return $result;
    }


    function simpleRestCall($type='POST', $function, $data=array()){


        $host = $this->smartcall_service_endpoint . $function;
        $username = $this->smartcall_username;
        $password = $this->smartcall_password;
        $header = array();
        $header[] = 'Content-type: application/json';

        if($function == 'auth' || $function == 'auth/token'){
            $header[] = 'Authorization: Basic ' . base64_encode($username.':'.$password);
        }else{

            if($this->bearer && strlen($this->bearer) >= 5){
                $header[] = 'Authorization: Bearer ' . trim($this->bearer);               
            }else{
                $this->auth();
                return $this->simpleRestCall($type, $function, $data);
            }
        }

        try {
            $ch = curl_init();

            if (FALSE === $ch)
                throw new Exception('failed to initialize');

            curl_setopt($ch, CURLOPT_URL,$host);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
            if($type == 'POST'){
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }

            $content = curl_exec($ch);
            if (FALSE === $content){
                throw new Exception(curl_error($ch), curl_errno($ch));
            }

            curl_close($ch);
            $json =  json_decode($content, TRUE);

            if(isset($json['responseDescription']) && $json['responseDescription'] == 'Authorization denied. Token validation failed'){
                $this->auth();
                return $this->simpleRestCall($type, $function, $data);
            }

            if(isset($json['responseDescription']) && $json['responseDescription'] == 'Maximum concurrent session limit reached. Limit is 20'){
                $this->auth('flush');
                return $this->simpleRestCall($type, $function, $data);
            }

            if(!isset($json['responseDescription']) || (isset($json['responseDescription']) && $json['responseDescription'] != "Authentication successful")){
                $this->save_log('request',$data);
                
                if (!$json || empty($json)) {
                    $this->save_log('response',$content);
                }else{
                    $this->save_log('response',$json);
                }
            }
            return $json;

        } catch(Exception $e) {

            trigger_error(sprintf(
                'Curl failed with error #%d: %s',
                $e->getCode(), $e->getMessage()), E_USER_ERROR);

        }
    }

    function save_log($type,$data){

        $json = $data;

        if(is_array($data)){
            $json = json_encode($data);
        }

        $insert = array(
            'type' => $type,
            'json' => $json,
            'createdate' => date("Y-m-d H:i:s")
            );
        
        $this->_CI->db->insert('smartcall_api_log', $insert);
    }

    function insert_purchase($data){
        $data['createdate'] = date("Y-m-d H:i:s");
        $data = $this->strip_db_rejects('smartcall_purchases', $data);
        $this->_CI->db->insert("smartcall_purchases",$data);
    }

    function update_purchase($clientReference, $data){
        if(!empty($data)){
            $data['updatedate'] = date("Y-m-d H:i:s");
            $data = $this->strip_db_rejects('smartcall_purchases', $data);
            $this->_CI->db->where("clientReference",$clientReference)->update("smartcall_purchases",$data);
        }
    }

    function strip_db_rejects($table, $dirty_array){

      $clean_array = array();
      $table_fields = $this->_CI->db->list_fields($table);

      foreach ($dirty_array as $key => $value) {
        if(in_array($key, $table_fields)){
          $clean_array[$key] = $value;
        }
      }
      return $clean_array;
    }

}
