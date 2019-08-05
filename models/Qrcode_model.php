<?php

class Qrcode_model extends CI_Model { 

   public function __construct()
   {
      	parent::__construct();
       $this->load->library('ciqrcode');
   }

	function generate_qrcode($data){
		$img_url="";
		if($data['action'] && $data['action'] == "generate_qrcode")
		{
			
			$qr_image=$data['qr_text'].'.png';
			$params['data'] = $data['qr_text'];
			$params['level'] = 'H';
			$params['size'] = 8;
			$params['savename'] =FCPATH."assets/uploads/qr_image/".$qr_image;
			if($this->ciqrcode->generate($params))
			{
				$img_url=$qr_image;	
				
			}
		}

		return $img_url;

	}

	function update_kids_qr_code(){
		$data['action'] = "generate_qrcode";
		$this->load->model('tt_kid_model');

		$query = $this->db->query("SELECT * FROM tt_kids WHERE device_identifier!='' || device_identifier!='null'");
      	$result = $query->result_array();

		foreach ($result as $key => $value) {
			$data['qr_text']=$value['device_identifier'];
			$data['name']=$value['first_name']." ".$value['last_name'];
			$img_url=$this->generate_qrcode($data);
			
		}

		echo json_encode($result);
	}

	function update_kid_qr_code($id){
		$data['action'] = "generate_qrcode";
		$this->load->model('tt_kid_model');

		$query = $this->db->query("SELECT * FROM tt_kids WHERE id=?", array($id));
      	$value = $query->row_array();

		$data['qr_text']=$value['device_identifier'];
		$data['name']=$value['first_name']." ".$value['last_name'];
		$img_url=$this->generate_qrcode($data);
		
		return $value;
	}


}
