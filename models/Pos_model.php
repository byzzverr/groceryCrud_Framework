<?php

class Pos_Model extends CI_Model {

  function __construct() {
    parent::__construct();
    $this->load->model('product_model');
    $this->load->model('photosnap_model');
  }

	function get_active_poses() {
		$this->db->select("id, name, status, createdate")->from("bc_pos")
			->where("status!=",7);
			$query = $this->db->get();
		if($query->num_rows() > 0)
			return $query->result_array();
		else
			return null;
	}

	function get_active_pos($id) {
		$this->db->select("*")->from("bc_pos")
			->where("status!=",7)
			->where("id",$id);
			$query = $this->db->get();
		if($query->num_rows() > 0){

			$pos =  $query->row_array();
			if($pos['prod_1'] != 0){
				$pos['products'][] = $this->product_model->get_product_basic_info($pos['prod_1']);
			}
			if($pos['prod_2'] != 0){
				$pos['products'][] = $this->product_model->get_product_basic_info($pos['prod_2']);
			}
			if($pos['prod_3'] != 0){
				$pos['products'][] = $this->product_model->get_product_basic_info($pos['prod_3']);
			}
			if($pos['prod_4'] != 0){
				$pos['products'][] = $this->product_model->get_product_basic_info($pos['prod_4']);
			}
			if($pos['prod_5'] != 0){
				$pos['products'][] = $this->product_model->get_product_basic_info($pos['prod_5']);
			}
			unset($pos['prod_1'],$pos['prod_2'],$pos['prod_3'],$pos['prod_4'],$pos['prod_5']);

			if($pos['photosnap_id'] != 0){
				$pos['photosnap'] = $this->photosnap_model->get_active_photosnap($pos['photosnap_id']);
				unset($pos['photosnap_id']);
			}
			return $pos;
		}else{
			return null;
		}
	}

	function store_photosnap_response($user_id, $photosnap_id, $store_picture){
		$date = date("Y-m-d H:i:s");
		$this->db->insert('bc_photosnaps_responses',array(
												'user_id' => $user_id,
												'photosnap_id' => $photosnap_id,
												'picture' => $store_picture,
												'status' => 3,
												'createdate' => $date
												));
	}

	function get_task_image($photosnap_id){
		$query = $this->db->query("SELECT picture FROM bc_photosnaps WHERE id = ?", array($photosnap_id));
		$res = $query->row_array();
		return $res['picture'];
	}
	
}