<?php

class Vpp_model extends CI_Model { 

   public function __construct()
   {
      parent::__construct();

        $this->load->model('financial_model');
        $this->load->library('comms_library');

        $this->userName = 'VPS003015z29';
        $this->password = 'aQdcPfMLFTRI';
        $this->wsdl_url = "https://vpstss05.vpsvirtual.com/CLIGenericTest/CLIVPS.asmx?wsdl";
   }
   
   function buy_voucher($user_id, $voucher, $amount, $cellphone){

   }
   
   
   //Service Calls
   function lotto($function,$info){
		try {
			/* This is untested for now /*
			/* Initialize webservice with your WSDL */
			$client = new SoapClient($this->wsdl_url);

			$login = array(
			"user" => $this->user,
			"pass" => $this->pass
			);
			  
			$settings = array_merge($login,$info);
			$log_id = $this->api_tracker('request', json_encode($settings));
			switch ($function) {
				case 'Login':
				/* $sample = array(
					'userName'  => 'VPS003015z29', 
					'password' => 'aQdcPfMLFTRI'
				); */
				$response =  $client->Login($settings);
                  break;
				case 'LottoGetDataFeedNames':
				/* $sample = array(
					'transactionRef'  => '123456789');
				*/
				$feed =  $client->LottoGetDataFeedNames($settings);
				$feedNames = get_object_vars($feed->LottoGetDataFeedNamesResult->feedNames)["string"];
                  break;
				case 'LottoGetGameInfo':
				/* $sample = array(
					'transactionRef'  => 'E34',
					'gameName'=>'Lotto'
				); */
				$response =  $client->LottoGetGameInfo($settings);
                  break;
              case 'LottoPlaceBet':
			  /* $sample = array(
					'transactionRef'  => 'rt', 
					'bet'=>['duration'=>2,
					'gameName'=>'Lotto',
					'boards'=>[
						'Board'=>[
							'quickpick'=>false,
							'selections'=>'1,2,3,4,5,6',
							'systemBetType'=>'NONE',
							'stake'=>20
						]
					],
					'addonPlayed'=>false,
					'extraPlayed'=>false
					]
				); */
				$response =  $client->LottoPlaceBet($settings);
                  break;
              default:
                  # code...
                  break;
          }
		}
		catch (SoapFault $fault) {
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
}