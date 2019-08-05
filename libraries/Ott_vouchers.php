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

class Ott_vouchers{

    private $_CI;

    public function __construct()
    {
        $this->_CI = &get_instance();

        ini_set("soap.wsdl_cache_enabled", "0");

        $this->_CI->load->config('ott_vouchers');
        $this->ott_purchase_wsdl = $this->_CI->config->item('ott_purchase_wsdl');
        $this->ott_redeem_wsdl = $this->_CI->config->item('ott_redeem_wsdl');
        $this->ott_status_wsdl = $this->_CI->config->item('ott_status_wsdl');
        $this->ott_username = $this->_CI->config->item('ott_username');
        $this->ott_password = $this->_CI->config->item('ott_password');
        $this->ott_vendor_code = $this->_CI->config->item('ott_vendor_code');
        $this->ott_account_code = $this->_CI->config->item('ott_account_code');
    }


    function status($unique_reference){

        $status = array();

        $status['unique_reference'] = $unique_reference;
        $status['userName'] = $this->ott_username;
        $status['password'] = $this->ott_password;

        $result = $this->simpleSoapCall('status','GetStatus', $status);
        $result = $result->GetStatusResult;
       
        return $result;
    }

    function purchase($value, $user, $cellphone=false){

        $purchase = array();

        $purchase['branch'] = 'Spazapp';
        $purchase['cashier'] = $user['name'];
        $purchase['till_no'] = $user['id'];
        $purchase['sale_date'] = date("Y-m-d");
        $purchase['vendor_code'] = $this->ott_vendor_code;
        $purchase['value'] = $value;
        $purchase['unique_reference'] = 'SP'.$user['id'].'-'.date("ymdHis");
        $purchase['username'] = $this->ott_username;
        $purchase['password'] = $this->ott_password;

        $db_insert = strip_db_rejects('ott_p_requests', $purchase);
        $db_insert['user_id'] = $user['id'];
        $db_insert['cellphone'] = $cellphone;
        $db_insert['createdate'] = date("Y-m-d H:i:s");
        $this->_CI->db->insert("ott_p_requests", $db_insert);

        $result = $this->simpleSoapCall('purchase','GetVoucher', $purchase);
        $result = $result->GetVoucherResult;
       
        $result_array = array();

        if($result){
            foreach ($result as $key => $value) {
                $result_array[strtolower($key)] = $value;
            }
            $result_array['createdate'] = date("Y-m-d H:i:s");
            $result_array = strip_db_rejects('ott_p_responses', $result_array);

            if($cellphone){
                $this->_CI->load->model('comms_model');
                $message = 'SPAZAPP: ' . str_replace('{newline}', '', $result_array['message']);
                $this->_CI->comms_model->send_sms($cellphone, $message);
            }

            $this->_CI->db->insert("ott_p_responses", $result_array);
        }

        return $result;
    }

    function redeem($pinCode, $user){

        $redeem = array();

        $redeem['userName'] = $this->ott_username;
        $redeem['password'] = $this->ott_password;

        $redeem['unique_reference'] = 'SR'.$user['id'].'-'.date("ymdHis");
        $redeem['VendorID'] = $this->ott_vendor_code;
        $redeem['pinCode'] = $pinCode;
        $redeem['accountCode'] = $user['cellphone'];
        $redeem['clientID'] = $user['id'];
        $redeem['msisdn'] = $user['msisdn'];

        $db_insert = strip_db_rejects('ott_r_requests', $redeem);
        $db_insert['user_id'] = $user['id'];
        $db_insert['cellphone'] = $user['cellphone'];
        $db_insert['pin_code'] = $redeem['pinCode'];
        $db_insert['createdate'] = date("Y-m-d H:i:s");
        $this->_CI->db->insert("ott_r_requests", $db_insert);

        $result = $this->simpleSoapCall('redeem','RedeemVoucher', $redeem);

        $result = $result->RedeemVoucherResult;
       
        $result_array = array();

        if($result){
            foreach ($result as $key => $value) {
                $result_array[strtolower($key)] = $value;
            }

            $result_array = strip_db_rejects('ott_r_responses', $result_array);
            $result_array['createdate'] = date("Y-m-d H:i:s");
            $this->_CI->db->insert("ott_r_responses", $result_array);

            if($result_array['error_code'] == 0){
                $this->_CI->load->model('comms_model');
                $message = 'SPAZAPP: Successful redemption of OTT voucher PIN: '.$redeem['pinCode'].' to the value of R' . number_format($result_array['value']);
                $this->_CI->comms_model->send_sms($user['cellphone'], $message);
            }
        }

        return $result;
    }

    function simpleSoapCall($type, $function, $data){

        $wsdl = false;
        switch ($type) {
            case 'purchase':
                $wsdl = $this->ott_purchase_wsdl;
                break;

            case 'redeem':
                $wsdl = $this->ott_redeem_wsdl;
                break;

            case 'status':
                $wsdl = $this->ott_status_wsdl;
                break;
        }

        try {

            $client = new SoapClient($wsdl, array('trace'   =>  1));        

        } catch (SoapFault $fault) {
            echo "SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})";
        }

        try {

            $xml = $data;

/*            echo "REQUEST:\n" . $client->__getLastRequest() . "\n";
            echo "RESPONSE:\n" . $client->__getLastResponse() . "\n";*/

        switch ($function) {
            case 'GetVoucher':
                $result = $client->GetVoucher($xml);
                break;
            case 'GetStatus':
                $result = $client->GetStatus($xml);
                break;
            case 'RedeemVoucher':
                $result = $client->RedeemVoucher($xml);
                break;
        }


            return $result;

        } catch (SoapFault $fault) {

            echo "ERROR:\n";
            echo "REQUEST:\n" . $client->__getLastRequest() . "\n";
            echo "RESPONSE:\n" . $client->__getLastResponse() . "\n";
            return "SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})";

        }  

    }

}
