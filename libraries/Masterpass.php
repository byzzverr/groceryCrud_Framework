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

class Masterpass{

    private $_CI;

    public function __construct()
    {
        $this->_CI = &get_instance();

        ini_set("soap.wsdl_cache_enabled", "0");

        $this->_CI->load->config('masterpass');
        $this->endpoint = $this->_CI->config->item('masterpass_endpoint');
        $this->masterpass_id = $this->_CI->config->item('masterpass_id');
        $this->masterpass_password = $this->_CI->config->item('masterpass_password');
        $this->db = $this->_CI->db;
    }


    function create_code($data){

       $result = false;

        if(isset($data['amount']) && isset($data['merchantReference'])){

            /*$data['expiryDate'] = time() + (14 * 24 * 60 * 60);*/
            $data['expiryDate'] = 0;
            $result = json_decode($this->simpleRestCall('POST','code/create', $data), TRUE);
        }

        if(isset($result['code']) && is_numeric($result['code'])){
            $png = $this->simpleRestCall('GET','/public/qr/'.$result['code']);
            $dir = './assets/uploads/masterpass/codes/'.date("Ymd");
            $img = $dir.'/'.$result['code'].'.png';

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

                
            if(file_put_contents($img, $png)){
                $insert = array();
                $insert['qrcode'] = base_url().str_replace('./', '', $img);
                $insert['code'] = $result['code'];
                $insert['user_id'] = $data['user_id'];
                $insert['amount'] = $data['amount'];
                if(isset($data['order_id'])){
                    $insert['order_id'] = $data['order_id'];
                }
                $insert['amount'] = $data['amount'];
                $insert['merchant_reference'] = $data['merchantReference'];
                $insert['expiryDate'] = gmdate("Y-m-d H:i:s", strtotime($result['expiryDate']));
                $insert['createdate'] = date("Y-m-d H:i:s");
                $res = $this->db->insert('masterpass_codes', $insert);
                /*$sql = $this->db->set($result)->get_compiled_insert('masterpass_codes');
                echo $sql;*/
                unset($result['useOnce']);
                unset($result['user_id']);
            }else{
                return false;
            }
        return $insert;
        }else{
            return false;
        }


    }

    function process_payment_result($result){

        $code = false;
        $insert = array();
        $date = date("Y-m-d H:i:s");
        /*

        {
           "transactionId":41437,
           "reference":"2_20170425_093630",
           "amount":5,
           "currencyCode":"ZAR",
           "status":"SUCCESS",
           "bankResponse":{
              "retrievalReferenceNumber":43459,
              "authCode":" DEBUG",
              "bankResponse":"00"
           },
           "code":"9544871201",
           "msisdn":"27827378714",
           "cardInfo":{
              "cardType":"DEBIT",
              "binLast4":"500100-9695",
              "accountType":"CREDIT",
              "cardHolderName":"success"
           },
           "merchantId":380
        }

        */

        if(isset($result['reference'])){
            $code = $this->get_code_from_reference($result['reference']);
        }

        if($code){
            $result['reference'] = trim($result['reference']);
            $this->db->where('merchant_reference', $result['reference']);
            $this->db->update('masterpass_codes', array('paiddate' => $date));
            $insert['code_id'] = $code['id'];
            $insert['user_id'] = $code['user_id'];
            $insert['order_id'] = $code['order_id'];
        }

        $insert['transactionId'] = $result['transactionId'];
        $insert['reference'] = $result['reference'];
        $insert['amount'] = $result['amount'];
        $insert['status'] = $result['status'];
        if(isset($result['bankResponse'])){
            $insert['retrievalReferenceNumber'] = $result['bankResponse']['retrievalReferenceNumber'];
            $insert['authCode'] = $result['bankResponse']['authCode'];
            $insert['bankResponse'] = $result['bankResponse']['bankResponse'];
        }
        $insert['code'] = $result['code'];
        $insert['msisdn'] = $result['msisdn'];
        if(isset($result['cardInfo'])){
            $insert['cardType'] = $result['cardInfo']['cardType'];
            $insert['binLast4'] = $result['cardInfo']['binLast4'];
            $insert['accountType'] = $result['cardInfo']['accountType'];
            $insert['cardHolderName'] = $result['cardInfo']['cardHolderName'];
        }
        $insert['merchantId'] = $result['merchantId'];
        $insert['createdate'] = date("Y-m-d H:i:s");

        $this->db->insert('masterpass_results', $insert);

        if($code){
            return $insert;
        }
        return false;
    }

    function get_code_from_reference($reference){
        $reference = trim($reference);
        $query = $this->db->query("SELECT id, user_id, code, order_id FROM masterpass_codes WHERE merchant_reference = '$reference'");
        return $query->row_array();
    }

    function simpleRestCall($type='POST', $function, $data=array()){

/*                        return '{
"code": "5887389040",
"expiryDate": 1493105840913
}';*/

        $this->save_log('request',$data);

        $host = $this->endpoint . $function;
        $username = $this->masterpass_id;
        $password = $this->masterpass_password;
        $header = array();
        $header[] = 'Content-type: application/json';
        $header[] = 'Authorization: Basic ' . base64_encode($username.':'.$password);

        try {
            $ch = curl_init();

            if (FALSE === $ch)
                throw new Exception('failed to initialize');

            curl_setopt($ch, CURLOPT_URL,$host);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            if($type == 'POST'){
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }

            $content = curl_exec($ch);
            if (FALSE === $content){
                throw new Exception(curl_error($ch), curl_errno($ch));
            }

            curl_close($ch);
            if($type == 'POST'){
                $this->save_log('response',$content);
            }
            return $content;

        } catch(Exception $e) {

            $this->save_log('response',$e->getMessage());

            trigger_error(sprintf(
                'Curl failed with error #%d: %s',
                $e->getCode(), $e->getMessage()),
                E_USER_ERROR);

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
        
        if(!empty($json)){
            $this->_CI->db->insert('masterpass_api_log', $insert);
        }
    }

}