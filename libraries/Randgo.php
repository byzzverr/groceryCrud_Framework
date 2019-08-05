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

class Randgo{

    private $_CI;

    public function __construct()
    {
        $this->_CI = &get_instance();

        ini_set("soap.wsdl_cache_enabled", "0");

        $this->_CI->load->config('randgo');
        $this->randgo_wsdl = $this->_CI->config->item('randgo_wsdl');

    }


    function test(){

        $test = array(
           "BusinessObject" => "Users",
           "Method" => "Login",
           "Password" => "TestPassword",
           "UserName" => "TestUsername"
        );

        $result = $this->simpleSoapCall($test);

        return $result;
    }

    function simpleSoapCall($data){

        $wsdl = $this->randgo_wsdl;

        try {

            $client = new SoapClient($wsdl, array('trace'   =>  1));        

        } catch (SoapFault $fault) {
            echo "SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})";
        }

        try {

            $xml = $data;

            echo "REQUEST:\n" . $client->__getLastRequest() . "\n";
            echo "RESPONSE:\n" . $client->__getLastResponse() . "\n";

           $result = $client->__getFunctions();

            return $result;

        } catch (SoapFault $fault) {

            echo "ERROR:\n";
            echo "REQUEST:\n" . $client->__getLastRequest() . "\n";
            echo "RESPONSE:\n" . $client->__getLastResponse() . "\n";
            return "SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})";

        }  

    }

}
