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

class Payhost{

    private $_CI;

    public function __construct()
    {
        $this->_CI = &get_instance();

        ini_set("soap.wsdl_cache_enabled", "0");

        $this->_CI->load->config('paygate');
        $this->wsdl = $this->_CI->config->item('payhost_wsdl');
        $this->payhost_id = $this->_CI->config->item('payhost_id');
        $this->payhost_password = $this->_CI->config->item('payhost_password');
    }


    function card_payment($data, $user){

        $payment = array();

        $payment['Customer'] = array(
            'Title' => 'na',
            'FirstName' => $user->firstname,
            'LastName' => $user->lastname,
            'Telephone' => $user->cellphone,
            'Email' => $user->email
            );
        $payment['VaultId'] = $data['card_token'];
        $payment['CVV'] = $data['cvv'];
        $payment['BudgetPeriod'] = 0;
        $payment['Order'] = array(
            'MerchantOrderId' => $data['cref'],
            'Currency' => 'ZAR',
            'Amount' => $data['amount']
            );

        $result = $this->simpleSoapCall('payment','CardPaymentRequest', $payment);
        return $result->CardPaymentResponse->Status;
    }

    function save_card($card){

        $result = false;
        if(isset($card['CardNumber'])&& isset($card['CardExpiryDate'])){

            $result = $this->simpleSoapCall('vault','CardVaultRequest', $card);
        }

        return $result->CardVaultResponse->Status;

    }

    function simpleSoapCall($type, $function, $data){


        try {

            $client = new SoapClient($this->wsdl, array('trace'   =>  1));        

        } catch (SoapFault $fault) {
            echo "SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})";
        }

        try {

            $data['Account']['PayGateId']   =  $this->payhost_id;
            $data['Account']['Password']    =   $this->payhost_password;

            $xml[$function] = $data;

            if($type == 'vault'){
                $result = $client->SingleVault($xml);
            }

            if($type == 'payment'){
                $result = $client->SinglePayment($xml);
            }

            //echo "REQUEST:\n" . $client->__getLastRequest() . "\n";
            //echo "RESPONSE:\n" . $client->__getLastResponse() . "\n";

            return $result;

        } catch (SoapFault $fault) {

            echo "REQUEST:\n" . $client->__getLastRequest() . "\n";
            echo "RESPONSE:\n" . $client->__getLastResponse() . "\n";
            echo "SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})";

        }  

    }

}
