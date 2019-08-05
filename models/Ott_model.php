<?php

class Ott_model extends CI_Model {

   public function __construct()
   {
      $this->load->model('customer_model');
      $this->load->model('product_model');
      $this->load->model('spazapp_model');
   
      parent::__construct();
   }


	public function get_ott_vouchers($type,$date_from,$date_to,$user_id=false){
		if($user_id){
			$where_user=" and rq.user_id='$user_id'";
		}else{
			$where_user='';
		}
		//Getting all the voucher sales
		if($type=='all'){
			$query=$this->db->query("SELECT rq.*, rs.*, a.name as user_id 
								FROM ott_p_requests as rq,
								ott_p_responses as rs, 
								aauth_users as a
								WHERE rq.unique_reference=rs.unique_reference 
								and a.id=rq.user_id 
								and rq.createdate>='$date_from' 
								and rq.createdate<='$date_to'
								$where_user");
		}

		//Getting stats sales per user
		if($type=='stats'){
			$query=$this->db->query("SELECT 
								count(rs.unique_reference) as sales_count,
								SUM(rq.value) as total,
								rq.user_id as id,
								(SELECT name FROM aauth_users WHERE id=rq.user_id) as user_id
								FROM ott_p_requests as rq,
								ott_p_responses as rs
								WHERE rq.unique_reference=rs.unique_reference 
								and rq.createdate>='$date_from' 
								and rq.createdate<='$date_to'
								GROUP BY rq.user_id");
		}
		
		return $query->result_array();
	}

}