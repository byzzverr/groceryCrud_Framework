<?php

class Training_Model extends CI_Model {

  function __construct() {
    parent::__construct();
  }

	function get_active_trainings() {
		$this->db->select("*")->from("bc_training")
			->where("status!=",7);
			$query = $this->db->get();
		if($query->num_rows() > 0)
			return $query->result_array();
		else
			return null;
	}

	function get_active_training($id) {
		$this->db->select("*")->from("bc_training")
			->where("status!=",7)
			->where("id",$id);
			$query = $this->db->get();
		if($query->num_rows() > 0)
			return $query->row_array();
		else
			return null;
	}

	function store_training_response($user_id, $training_id, $store_picture){
		$date = date("Y-m-d H:i:s");
		$this->db->insert('bc_training_responses',array(
												'user_id' => $user_id,
												'training_id' => $training_id,
												'picture' => $store_picture,
												'status' => 3,
												'createdate' => $date
												));
	}

	function get_task_image($training_id){
		$query = $this->db->query("SELECT picture FROM bc_training WHERE id = ?", array($training_id));
		$res = $query->row_array();
		return $res['picture'];
	}

	function get_training_stats($status_id,$stats_type){
      
   if(!empty($status_id)){
      $where_status=" WHERE `r`.`status`='$status_id'";
    }else{
      $where_status="";
    }

    if($stats_type=="user_stats"){
      $group_by=" `r`.`user_id`";
      $count=" `r`.`user_id`";
    }

    if($stats_type=="training_stats"){
      $group_by=" `t`.`type_link_id`";
      $count=" `r`.`task_id`";
    }

    $query = $this->db->query("SELECT a.id, 
                              count($count) as number_of_resp, 
                              s.name,
                              r.createdate,
                              a.name as user
                              FROM `bc_tasks` as `t`  
                              JOIN `bc_task_results` as `r` ON `r`.`task_id` = `t`.`task_id`  
                              JOIN `bc_training` as `s` ON `s`.`id` = `t`.`type_link_id`
                              LEFT JOIN `aauth_users` as `a` ON `r`.`user_id` = `a`.`id`
                              JOIN `gbl_statuses` as `st` ON `st`.`id` = `r`.`status`
                              $where_status 
                              GROUP BY $group_by LIMIT 20");
    
    return  $query->result();
    
    
}

function get_training_list($status_id){
      if(!empty($status_id)){
        $where_status=" WHERE `r`.`status`='$status_id'";
      }else{
        $where_status="";
      }
      
      $query = $this->db->query("SELECT t.*, 
                              r.*, 
                              a.name as user, 
                              s.*, 
                              t.name as task,
                              s.name as  training,
                              `r`.`id`, 
                              `r`.`id` as task_result_id, 
                              t.task_id as task_id,
                              `s`.`id` as training_id, 
                              st.name as status,
                              r.createdate,
                              `r`.`user_id`
                              FROM `bc_tasks` as `t`  
                              JOIN `bc_task_results` as `r` ON `r`.`task_id` = `t`.`task_id`  
                              JOIN `bc_training` as `s` ON `s`.`id` = `t`.`type_link_id`
                              JOIN `aauth_users` as `a` ON `r`.`user_id` = `a`.`id`
                              JOIN `gbl_statuses` as `st` ON `st`.`id` = `r`.`status`
                              $where_status 
                              GROUP BY `r`.`id`
                              ");
      
    return  $query->result();
   
  }

  function get_training_responses($training_id, $status_id='', $query_type=''){
      // $group_by = "r.id";
      if($query_type=='count_results'){
        $count_result = ", count(DISTINCT r.user_id) as user_count, count(r.task_id) as all_response_count";
      }else{
        $count_result='';
      }

      if(!empty($status_id)){
        $where_status=" and r.status='$status_id'";
      }else{
        $where_status='';
      }
      
     $query = $this->db->query("SELECT a.name as user, r.id, s.picture as training_pic, rs.picture, r.createdate, 
                              st.name as status $count_result, r.status as status_id                        
                              FROM bc_tasks as t  
                              JOIN bc_task_results as r ON r.task_id = t.task_id
                              JOIN bc_training as s ON s.id = t.type_link_id 
                              LEFT JOIN bc_training_responses as rs ON s.id = rs.training_id 
                              and rs.user_id = r.user_id
                              JOIN aauth_users as a ON r.user_id = a.id
                              LEFT JOIN gbl_statuses as st ON st.id = r.status
                              WHERE s.id ='$training_id' $where_status ");
     
    if($query_type=='count_results'){
     return $query->row_array();
    }else{
     return $query->result_array();
    }
  } 

  function get_training($training_id){
     $query = $this->db->query("SELECT * FROM bc_training WHERE id ='$training_id'");
     return $query->row_array();
  }
	
}