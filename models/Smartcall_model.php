
<?php

class Smartcall_model extends CI_Model { 

   public function __construct()
   {
      parent::__construct();

        $this->load->model('financial_model');
        $this->load->library('comms_library');
   }


   function get_smart_call_purchases($stats_request, $date_from="",$date_to=""){
   		if(!empty($date_from) && !empty($date_to)){
   			$where_date = " and s.createdate>='$date_from' and s.createdate<='date_to'";
   		}else{
   			$where_date ='';
   		}
   		if($stats_request){
   			$query=$this->db->query("SELECT s.*, a.username, count(s.productId) as product_count, sum(s.amount) as total_amount FROM smartcall_purchases as s LEFT JOIN wallet_transactions as w  ON w.reference=s.orderReferenceId  LEFT JOIN aauth_users as a ON a.username = w.msisdn WHERE 1 $where_date
   				GROUP BY s.productId");
   		}else{
   			$query=$this->db->query("SELECT s.*, a.name, a.username FROM smartcall_purchases as s LEFT JOIN wallet_transactions as w  ON w.reference=s.orderReferenceId  LEFT JOIN aauth_users as a ON a.username = w.msisdn WHERE 1 $where_date
   				GROUP BY s.id");
   		}

   		$result = $query->result_array();

   		foreach ($result as $key => $value) {
   			if($value['productId']==47){
    			$name = "Eskom Direct";
    		}
    		if($value['productId']==23){
    			$name = "Municipalities";
    		}
    		if($value['productId']==220){
    			$name = "Free Basic";
    		}
    		if($stats_request){
    			$total_amount = $value['total_amount'];
    			$product_count = $value['product_count'];
    		}else{
    			$total_amount = '';
    			$product_count = '';
    		}

   			$return[$key] = array(
   				'id' => $value['id'],
          'username' => $value['username'],
   				'clientReference' => $value['clientReference'],
   				'smsRecipientMsisdn' => $value['smsRecipientMsisdn'],
   				'responseCode' => $value['responseCode'],
   				'productId' => $value['productId'],
   				'total_amount' => $total_amount,
   				'product_name' => $name,
   				'amount' => $value['amount'], 
   				'createdate' => $value['createdate'],
   				'updatedate' => $value['updatedate'],
   				'product_count' => $product_count,
   				);
   	  }

   	  return $return;
   }

}