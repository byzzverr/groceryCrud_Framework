<?php

class Event_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();

   }

   public function track($category, $action, $value=''){
 
		switch ($action) {
			case 'app_login_successful':
				$label = 'User logged in on the app with correct details';
				break;
			case 'registration_attempt':
				$label = 'User attempted to register';
				break;
			case 'registration':
				$label = 'User registered';
				break;
			case 'app_login_attempt':
				$label = 'User tried to login to the app with incorrect details';
				break;
			case 'login_successful':
				$label = 'User logged in with correct details';
				break;
			case 'login_attempt':
				$label = 'User tried to login with incorrect details';
				break;
			case 'add_user':
				$label = 'A new user was added';
				break;
			case 'edit_user':
				$label = 'An existing user was edited';
				break;
			case 'attempted_add_user':
				$label = 'Attepted to add a new user';
				break;
			case 'permissions':
				$label = 'User was redirected after trying to access an area without correct permissions';
				break;
			case 'checklist_started':
				$label = 'User has started completing a checklist';
				break;
			case 'checklist_cancelled':
				$label = 'User cancelled completing the checklist';
				break;
			case 'checklist_submitted':
				$label = 'User completed the checklist';
				break;
			case 'checklist_skipped':
				$label = 'User failed to complete checklist';
				break;
			default:
				$label = 'User Interaction';
				break;
		}
      
      $this->track_event($category, $action, $label, $value);

   }

	function track_event($catgory, $action, $label, $value='', $user_id=0){

		$user_info = $this->aauth->get_user();

		if ($user_id == 0 && isset($user_info->id)) {
			$user_id = $user_info->id;
		}

		if(is_array($value)){
			$value = json_encode($value);
		}
		$value = addslashes($value);

		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		    $ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
		    $ip = $_SERVER['REMOTE_ADDR'];
		}
		
		if($action == 'checklist_submitted'){
			$this->track_timings($user_id, $value);
		}

		$query = $this->db->query("INSERT INTO `event_log` (user_id, category, action, label, value, ipaddress, createdate) VALUES ('$user_id', '$catgory', '$action', '$label', '$value', '$ip', '".date("Y-m-d H:i:s")."')");

	}

	function private_track_event($user_id, $catgory, $action, $label, $value='', $date){

		$value = addslashes($value);

		if ($user_id == '') {
			$user_id = 0;
		}

		if(is_array($value)){
			$value = json_encode($value);
		}
		$value = addslashes($value);

		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		    $ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
		    $ip = $_SERVER['REMOTE_ADDR'];
		}

		$query = $this->db->query("INSERT INTO `event_log` (user_id, category, action, label, value, ipaddress, createdate) VALUES ('$user_id', '$catgory', '$action', '$label', '$value', '$ip', '".date("Y-m-d H:i:s")."')");

	}

	function get_user_stats($user_id){
		$query = $this->db->query("SET sql_mode = '';");
		$query = $this->db->query("SELECT count(id) as 'count', action FROM `event_log` WHERE user_id = '$user_id' GROUP BY action ORDER BY createdate desc");
		return $query->result_array();
	}

	function get_users_by_group($user_group){
		$query = $this->db->query("SELECT id FROM `aauth_groups` WHERE name = '$user_group'");
		$group_id = $query->row_array();

		$query1 = $this->db->query("SELECT a.* FROM `aauth_users` as a, `aauth_user_to_group` as b  WHERE a.id = b.user_id AND b.group_id = '".$group_id['id']."'");
		return $query1->result_array();
	}

	function add_skipped_checklist_event($user_id, $checklist_id, $date){

		$query1 = $this->db->query("SELECT * FROM `event_log` WHERE user_id = '$user_id' AND action = 'checklist_skipped' AND value = '$checklist_id' AND createdate >= '".$date." 00:00:01' AND createdate <= '".$date." 23:59:59'");
		$event = $query1->row_array();

		if(!isset($event['id'])){
			$this->private_track_event($user_id, 'checklist', 'checklist_skipped', 'User failed to complete checklist', $checklist_id, $date);
		}
	}

	function seen_latest_news($user_id){

		$query = $this->db->query("SELECT id FROM `news` ORDER BY createdate desc");
		$news = $query->row_array();

		$query1 = $this->db->query("SELECT * FROM `event_log` WHERE user_id = '$user_id' AND action = 'viewed_news' AND value = '".$news['id']."'");
		$event = $query1->row_array();

		if(!isset($event['id'])){
			return false;
		}

		return true;
	}

	function add_seen_news_event($user_id, $news_id){
        
		$this->private_track_event($user_id, 'news', 'viewed_news', 'User comfirmed that news was read', $news_id, date('Y-m-d H:i:s'));
        
	}

	function get_latest_news(){
		$query = $this->db->query("SELECT * FROM `news` ORDER BY createdate desc");
		$news = $query->row_array();
		return $news;
	}

	function get_last_activity($user_id){
		$query = $this->db->query("SELECT action, createdate as 'date' FROM `event_log` WHERE user_id = '$user_id' ORDER BY createdate desc LIMIT 1");
		$event = $query->row_array();
		return $event;
	}

	function get_user_events($user_id){

      $query = $this->db->query("SELECT id as event_id, category, action, label, value, createdate FROM `event_log` WHERE user_id = '$user_id' ORDER BY createdate desc LIMIT 150");
      $result = $query->result_array();

      foreach ($result as $key => $value) {

		switch ($value['action']) {
			case 'add_user':
				$query_loop = $this->db->query("SELECT name FROM `aauth_users` WHERE id = '".$value['value']."'");
      			$result_loop = $query_loop->row_array();
      			if(isset($result_loop['name'])){
      				$result[$key]['value'] = $result_loop['name'];
      			}
				break;
			case 'edit_user':
				$query_loop = $this->db->query("SELECT name FROM `aauth_users` WHERE id = '".$value['value']."'");
      			$result_loop = $query_loop->row_array();
      			if(isset($result_loop['name'])){
      				$result[$key]['value'] = $result_loop['name'];
      			}
				break;
			case 'permissions':
				$label = 'User was redirected after trying to access an area without correct permissions';
				break;
			case 'checklist_started':
				$query_loop = $this->db->query("SELECT name FROM `checklists` WHERE id = '".$value['value']."'");
      			$result_loop = $query_loop->row_array();
      			if(isset($result_loop['name'])){
      				$result[$key]['value'] = $result_loop['name'];
      			}
				break;
			case 'checklist_cancelled':
				$query_loop = $this->db->query("SELECT name FROM `checklists` WHERE id = '".$value['value']."'");
      			$result_loop = $query_loop->row_array();
      			if(isset($result_loop['name'])){
      				$result[$key]['value'] = $result_loop['name'];
      			}
				break;
			case 'checklist_submitted':
				$query_loop = $this->db->query("SELECT name FROM `checklists` WHERE id = '".$value['value']."'");
      			$result_loop = $query_loop->row_array();
      			if(isset($result_loop['name'])){
      				$result[$key]['value'] = $result_loop['name'];
      			}
				break;
				case 'checklist_skipped':
				$query_loop = $this->db->query("SELECT name FROM `checklists` WHERE id = '".$value['value']."'");
      			$result_loop = $query_loop->row_array();
      			if(isset($result_loop['name'])){
      				$result[$key]['value'] = $result_loop['name'];
      			}
				break;
				case 'check_failed':
				$query_loop = $this->db->query("SELECT name FROM `checks` WHERE id = '".$value['value']."'");
      			$result_loop = $query_loop->row_array();
      			if(isset($result_loop['name'])){
      				$result[$key]['value'] = $result_loop['name'];
      			}
				break;
			default:
				$label = 'User Interaction';
				break;
		}
	}

      return $result;

	}	


	function get_customer_events($customer_id){

      $query = $this->db->query("SELECT c.id, a.action, a.label, c.company_name as 'Customer', a.createdate FROM `event_log` as a, `aauth_users` as b, customers as c WHERE a.user_id = b.id AND b.user_link_id = c.id AND c.id = '$customer_id' ORDER BY createdate desc LIMIT 150");
      $result = $query->result_array();
      return $result;
	}

	function track_timings($user_id,$checklist_id){
		//find the most recent checklist start with the same id.
      $query = $this->db->query("SELECT createdate FROM `event_log` WHERE user_id = '$user_id' AND value = '$checklist_id' AND action = 'checklist_started' ORDER BY createdate desc LIMIT 1");
      $result = $query->row_array();

      $then = $result['createdate'];
      $now = date("Y-m-d H:i:s");

      $time_between = strtotime($now) - strtotime($then);

      $query = $this->db->query("INSERT INTO checklist_timings (user_id, checklist_id, time, createdate) VALUES ('$user_id','$checklist_id','$time_between', NOW())");

	}

   function get_all_users($app="'spazapp'"){

		$query1 = $this->db->query("SELECT * FROM `aauth_users` WHERE default_app IN ($app) OR default_usergroup = 'Admin'");
		$result1 = $query1->result_array();
		return $result1;
   }


   function get_branch_all_users($app="'spazapp'",$branch_id){
 
		$query1 = $this->db->query("SELECT * FROM `aauth_users` WHERE default_app IN ($app) and default_usergroup = '25' and user_link_id='$branch_id'");
		$result1 = $query1->result_array();
		return $result1;
   }

   function get_user_from_id($id){

    $query1 = $this->db->query("SELECT * FROM `aauth_users` WHERE id = ?",array($id));
    $result1 = $query1->row();
    return $result1;
   }

	function get_all_events($limit=0,$count=20){

		$date_from = $this->session->userdata('dashboard_date_from');
		$date_to = $this->session->userdata('dashboard_date_to');

		$query_string = "SELECT * FROM `event_log` WHERE createdate > '$date_from' AND createdate < '$date_to' ORDER by createdate desc";

		$query = $this->db->query($query_string." LIMIT $limit,$count");
		$return['results'] = $query->result_array();

		$query1 = $this->db->query($query_string);
		$return['num_rows'] = $query1->num_rows();
		
		$return['query'] = $query_string;

		return $return;
	}

	function csv_from_query($query){

		if($query == 'get_users_and_checklist_stats'){

			$array =$this->get_users_and_checklist_stats();
			return $this->csv_from_array($array['results']);

		}elseif($query == 'get_average_checklist_timings'){

			$array =$this->get_average_checklist_timings();
			return $this->csv_from_array($array['results']);

		}else{

		$this->load->dbutil();

		$query = $this->db->query($query);
		$csv = $this->dbutil->csv_from_result($query);

		$path = './assets/exports/';
		$filename = 'export_'.date("Ymd-Hi").'.csv';

		$fp = fopen($path.$filename, 'w');
		fwrite($fp,$csv);
		fclose($fp);

		return $filename;

		}
	}

	function csv_from_array($array){

		$path = './assets/exports/';
		$filename = 'export_'.date("Ymd-Hi").'.csv';

		$fp = fopen($path.$filename, 'w');

		$new_array = array();
		foreach ($array[0] as $key => $value) {
		    $new_array[] = humanize($key);
		}

		fputcsv($fp, $new_array);

		foreach ($array as $fields) {
		    fputcsv($fp, $fields);
		}

		fclose($fp);
		return $filename;
	}

	// Additions For The Login Reports

	// Login Statistics Table Input
	function get_login_statics_with_filters($limit, $count)
	{

		$date_from = $this->session->userdata('dashboard_date_from');
		$date_to = $this->session->userdata('dashboard_date_to');

		$query_string = "SELECT `e`.`id`, `a`.`name`,`a`.`cellphone`, `e`.`ipaddress` as ip_address,
		`e`.createdate as last_activity, `e`.`label` as event_label FROM `event_log` `e` 
		JOIN `aauth_users` `a` ON `a`.`id` = `e`.`user_id` WHERE `e`.`category` = 'login' 
		AND `a`.`default_usergroup` NOT IN (14, 15, 16) AND createdate > '$date_from' 
		AND createdate < '$date_to' GROUP BY `e`.`id` DESC";

		$query = $this->db->query($query_string);
		$return['results'] = $query->result_array();

		$query1 = $this->db->query($query_string);
		$return['num_rows'] = $query1->num_rows();
		
		$return['query'] = $query_string;

		return $return;
	}

	// Login Statistics Table Input
	function get_rep_login_statics_with_filters($limit, $count, $distributor_id)
	{

		$date_from = $this->session->userdata('dashboard_date_from');
		$date_to = $this->session->userdata('dashboard_date_to');

		$query_string = "SELECT `a`.`id`, `a`.`name`, `e`.`ipaddress` as ip_address,
		 `a`.`last_activity`, `e`.`label` as event_label 
		 FROM `event_log` `e` 
		 JOIN `aauth_users` `a` ON `a`.`id` = `e`.`user_id` 
		 WHERE `a`.`distributor_id`='$distributor_id' 
		 AND `category` = 'login' 
		 AND `a`.`default_usergroup` NOT IN (14, 15, 16) 
		 AND createdate > '$date_from' 
		 AND createdate < '$date_to' GROUP BY `e`.`id` DESC";

		$query = $this->db->query($query_string." LIMIT $limit,$count");
		$return['results'] = $query->result_array();

		$query1 = $this->db->query($query_string);
		$return['num_rows'] = $query1->num_rows();
		
		$return['query'] = $query_string;

		return $return;
	}
    

	// Reset password count
	function get_reset_password_count()
    {
    	
      $date_from = $this->session->userdata('dashboard_date_from');
	  $date_to = $this->session->userdata('dashboard_date_to');

      $sql = "SELECT * FROM event_log WHERE action = 'reset_password' AND createdate > '$date_from' AND createdate < '$date_to' ";
      return $this->db->query($sql)->result();
    }
    // Reset password count
	function get_rep_reset_password_count($distributor_id)
    {
    	
      $date_from = $this->session->userdata('dashboard_date_from');
	  $date_to = $this->session->userdata('dashboard_date_to');

      $sql = "SELECT * FROM `event_log` `e` JOIN `aauth_users` `a` ON `a`.`id` = `e`.`user_id` WHERE `a`.`distributor_id`='$distributor_id' AND e.action = 'reset_password' AND `e`.`createdate` > '$date_from' AND `e`.`createdate` < '$date_to' ";
      return $this->db->query($sql)->result();
    }
 // Reset password count
	function get_rep_reset_registration_count($distributor_id)
    {
    	
      $date_from = $this->session->userdata('dashboard_date_from');
	  $date_to = $this->session->userdata('dashboard_date_to');

      $sql = "SELECT * FROM `event_log` `e` JOIN `aauth_users` `a` ON `a`.`id` = `e`.`user_id` WHERE `a`.`distributor_id`='$distributor_id' AND label = 'User registered' AND createdate > '$date_from' AND createdate < '$date_to' ";
      return $this->db->query($sql)->result();
    }
function get_rep_failed_login_count($distributor_id)
    {
      
      $date_from = $this->session->userdata('dashboard_date_from');
	  $date_to = $this->session->userdata('dashboard_date_to');

      $sql = "SELECT * FROM `event_log` `e` JOIN `aauth_users` `a` ON `a`.`id` = `e`.`user_id` WHERE `a`.`distributor_id`='$distributor_id' AND label = 'User tried to login with incorrect details' AND createdate > '$date_from' AND createdate < '$date_to' ";
      return $this->db->query($sql)->result();  
    }
 function get_rep_get_login_count($distributor_id)
    {
      
      $date_from = $this->session->userdata('dashboard_date_from');
	  $date_to = $this->session->userdata('dashboard_date_to');

      $sql = "SELECT * FROM `event_log` `e` JOIN `aauth_users` `a` ON `a`.`id` = `e`.`user_id` WHERE `a`.`distributor_id`='$distributor_id' AND label = 'User logged in with correct details' AND createdate > '$date_from' AND createdate < '$date_to' ";
      return $this->db->query($sql)->result();  
    }
 function get_rep_failed_registration_count($distributor_id)
    {
      
      $date_from = $this->session->userdata('dashboard_date_from');
	  $date_to = $this->session->userdata('dashboard_date_to');

      $sql = "SELECT * FROM `event_log` `e` JOIN `aauth_users` `a` ON `a`.`id` = `e`.`user_id` WHERE `a`.`distributor_id`='$distributor_id' AND label = 'User attempted to register'  AND createdate > '$date_from' AND createdate < '$date_to' ";
      return $this->db->query($sql)->result();  
    }

    // Registration count
    function get_registration_count()
    {
      
      $date_from = $this->session->userdata('dashboard_date_from');
	  $date_to = $this->session->userdata('dashboard_date_to');

      $sql = "SELECT * FROM event_log WHERE label = 'User registered' AND createdate > '$date_from' AND createdate < '$date_to' ";
      return $this->db->query($sql)->result();	  
    }


    // Failed Registration Count
    function get_failed_registration_count()
    {

      $date_from = $this->session->userdata('dashboard_date_from');
	  $date_to = $this->session->userdata('dashboard_date_to');

      $sql = "SELECT * FROM event_log WHERE label = 'User attempted to register' AND createdate > '$date_from' AND createdate < '$date_to' ";
      return $this->db->query($sql)->result();
    }
 
    // Successful Login Count
    function get_login_count()
    {

      $date_from = $this->session->userdata('dashboard_date_from');
	  $date_to = $this->session->userdata('dashboard_date_to');	

      $sql = "SELECT * FROM event_log WHERE label = 'User logged in with correct details' AND createdate > '$date_from' AND createdate < '$date_to' ";
      return $this->db->query($sql)->result();
    }

    // Failed Login Count
    function get_failed_login_count()
    {
      $date_from = $this->session->userdata('dashboard_date_from');
	  $date_to = $this->session->userdata('dashboard_date_to');	

      $sql = "SELECT * FROM event_log WHERE label = 'User tried to login with incorrect details' AND createdate > '$date_from' AND createdate < '$date_to' ";
      return $this->db->query($sql)->result();
    }

function get_event_log($start_date,$end_date){
    
        $this->db->select('event_log.id as event_id, user_id, category, event_log.id,last_activity, name, count(user_id) as number, label, action, event_log.createdate, username, count(event_log.user_id) as user_count' );
        $this->db->from('event_log' );
        $this->db->join('aauth_users', 'event_log.user_id = aauth_users.id' , 'left' ); 
        if(!empty($start_date) || !empty($start_date)){
        $this->db->where('event_log.createdate >=', $start_date);
        $this->db->where('event_log.createdate <=', $end_date);
        }
        $this->db->where('event_log.category =', 'login');
        $id = $this->uri->segment(3);
        if(isset($id)){
        $this->db->where('event_log.id >=', $id); 
        }
        $this->db->group_by('user_id');
        $this->db->order_by('event_log.createdate', 'asc');
        $this->db->limit(20);
        $query = $this->db->get();
        return $query;
          
 }
    
       
function get_event_log_details_1($start_date,$end_date,$id){
    
        $this->db->select('event_log.label as labels, count(event_log.label) as number' );
        $this->db->from('event_log' );
        $this->db->join('aauth_users', 'event_log.user_id = aauth_users.id' , 'left' );
        $this->db->where('event_log.category =', 'login');
      
        $this->db->where('event_log.user_id=', $id); 
        $this->db->group_by("event_log.label");
        $query = $this->db->get();
        return $query;
          
}
function get_event_log_details_2($start_date,$end_date,$id){
    
        $this->db->select('*');
        $this->db->from('event_log' );
        $this->db->where('event_log.category =', 'login');
        $id = $this->uri->segment(3);
        $this->db->where('event_log.user_id=', $id); 
        if(!empty($start_date)){
        $this->db->where('event_log.createdate >=', $start_date);    
        }
        if(!empty($end_date)){
        $this->db->where('event_log.createdate <=', $end_date);
        }
        $this->db->group_by('event_log.createdate');
        $this->db->order_by('event_log.createdate', 'desc');
        $this->db->limit(50);
        $query = $this->db->get();
        return $query;
          
    }
function get_unread_news($user_id,$value){
  $this->db->select('news.id, news.heading,news.body,news.createdate, event_log.user_id')
        ->from('news')
        ->join('event_log','event_log.value=news.id','left')
        ->where('value !=',$value)
        ->where('user_id =',$user_id);
  $query=     $this->db->get();
  
return $query->row();
   
          
}
function get_read_news($user_id){
  $this->db->select('event_log.value, news.id, news.heading,news.body,news.createdate, event_log.user_id')
        ->from('news')
        ->join('event_log','event_log.value=news.id','left')
        ->where('user_id =',$user_id);
  $query=     $this->db->get();
  
return $query;
   
          
}
function get_sms_log($from, $to){
	
    if(!empty($from)){
      	$where_date =" s.createdate >= '$from' AND s.createdate <= '$to'";  
    }else{
        $where_date ='';
    }
    
    $query_str = "SELECT s.*,
    (SELECT company_name FROM customers WHERE substr(`cellphone`,2,10)=substr(`s`.`to`,3,10) LIMIT 1) as company_name,
    (SELECT name FROM aauth_users WHERE username=substr(`s`.`to`,3,10) LIMIT 1) as name
     FROM `sms_log` as s WHERE $where_date ORDER BY createdate DESC";
    $query = $this->db->query($query_str);   
    return $query->result_array();
}
  
function getCustomerSmsLog($from, $to,$customer_id){
	if(!empty($from)){
		$where_date=" WHERE SUBSTR(`createdate`,1,10) >= '$from' AND SUBSTR(`createdate`,1,10) <= '$to'";
	}else{
		$where_date='';
	}
	
   
	$query = $this->db->query("SELECT * FROM `sms_log` $where_date ORDER BY createdate DESC");   
    return $query->result_array();
}

function getUserName($cellphone){
	$query = $this->db->query("SELECT * FROM aauth_users WHERE username='0".$cellphone."'");   
    return $query->row_array();
}
  

function getDailySmsLogStats($from_date, $to_date,$customer_id){


    if(!empty($from_date)){
    	$where_date=" where SUBSTR(`s`.`createdate`,1,10) >= '$from_date' AND SUBSTR(`s`.`createdate`,1,10) <= '$to_date'";
    }else{
    	$where_date='';
    }
    
    $query = $this->db->query("SELECT 
						    count(`s`.`to`) as sms_count,
						    SUBSTR(`s`.`createdate`,1,10) as createdate
						    FROM `sms_log` as s
						    $where_date
						    GROUP BY SUBSTR(`s`.`createdate`,1,10) 
						    DESC LIMIT 31");
 
    return $query->result_array();
}
function getCustomerSmsLogStats($from_date, $to_date){

    if(!empty($from_date)){
    	$where_date=" and SUBSTR(`createdate`,1,10) >= '$from_date' AND SUBSTR(`createdate`,1,10) <= '$to_date'";
    }else{
    	$where_date='';
    }
    
    $query = $this->db->query("SELECT 
						    count(`s`.`to`) as sms_count,
						    a.name,
						    s.to
						    FROM `sms_log` as s 
						    JOIN aauth_users as a ON substr(`a`.`cellphone`,2,10)=substr(`s`.`to`,4,10)
						    WHERE 1 $where_date
						    GROUP BY `s`.`to` ORDER BY count(`s`.`to`) 
						    DESC LIMIT 31");
 
    return $query->result_array();
}

   

function get_email_log($customer,$from,$to){
    if(!empty($customer)){
        $where_cust='AND `e`.`to` = "'.$customer.'"';
    }else{
        $where_cust='';
    } 
    
    if(!empty($from)){
        $where_date="AND `e`.`createdate` >= '$from' AND `e`.`createdate` <= '$to'";
    }else{
        $where_date='';
    }
 
      
    $query = $this->db->query("SELECT 
    							e.template,
						    	e.to,
						    	e.id, 
						    	e.createdate,
						    	c.company_name as customer,
						    	d.company_name as distributor
						    	FROM email_log as e
						    	LEFT JOIN customers as c ON e.to = c.email
						    	LEFT JOIN distributors as d ON e.to = d.email
						    	WHERE 1 $where_cust 
						    	$where_date GROUP BY e.id");
       
    $email_details = $query->result_array();
  
    return $email_details ;
}
    
function get_email_log_customers(){
    
        
    $query_str = "SELECT * FROM `aauth_users`";

    $query = $this->db->query($query_str);
    $sms = $query->result_array(); 


    $query = $this->db->query("SELECT a.*, e.*,
					    	`a`.`name` as to_name 
					    	FROM `email_log` as `e` 
					    	LEFT JOIN `aauth_users` as `a` 
					    	ON `e`.`to`=`a`.`email` 
					    	WHERE `e`.`to` !='' 
					    	GROUP BY `a`.`name`");
       
    $email_details = $query->result_array();
  
    return $email_details ;
}
function get_customer_by_id($customer){
   

    $query = $this->db->query("SELECT * FROM `aauth_users` WHERE `email`='$customer' LIMIT 0,1");
    return $query->result_array();   
}   
function get_email_log_stats($customer,$from,$to){
    
    if(!empty($customer)){
        $where_cust='AND `e`.`to` = "'.$customer.'"';
    }else{
        $where_cust='';
    }
    
      
    if(!empty($from)){
        $where_date="AND `e`.`createdate` >= '$from' AND `e`.`createdate` <= '$to'";
    }else{
        $where_date='';
    }
       
     $query = $this->db->query("SELECT count(`e`.`template`) as mail_count, 
     							c.*, e.*
						    	FROM `email_log` as `e` 
						    	LEFT JOIN `customers` as `c` 
						    	ON `e`.`to`=`c`.`email` 
						    	WHERE `e`.`to` !='' $where_cust 
						    	$where_date GROUP BY `e`.`template`");
       
    return $query->result_array();
  
}

function getDailyEmailStats($customer,$from,$to){
    
    if(!empty($from)){
        $where_date="AND `e`.`createdate` >= '$from' AND `e`.`createdate` <= '$to'";
    }else{
        $where_date='';
    }
       
     $query = $this->db->query("SELECT 
     							count(e.to) as mail_count, 
     							substr(e.createdate,1,10) as createdate,
     							e.to as email
						    	FROM email_log as e 
						    	WHERE e.to !='' $where_date GROUP BY substr(e.createdate,1,10) DESC LIMIT 30");
       
    $return= $query->result_array();
  
    return $return;
   
  
}

function getUserNameByEmail($email){
	$query = $this->db->query("SELECT * FROM aauth_users WHERE email='$email'");
	return $query->row_array();

}

function get_email_log_export($customer,$from,$to){
    
    if(!empty($customer)){
        $where_cust="AND `e`.`to` = '".$customer."'";
    }else{
        $where_cust='';
    }
      
    if(!empty($from)){
        $where_date="AND `e`.`createdate` >= '$from' AND `e`.`createdate` <= '$to'";
    }else{
        $where_date='';
    }
        $query_str = "SELECT * FROM `customers`";

        $query = $this->db->query($query_str);
        $sms = $query->result_array(); 
        
        $comma ='';
        $on='';
        foreach($sms as $row){
            $email = $row['email'];
            $on .= $comma."'".humanize($email)."'";
            $comma =',';
        }
      
    $query_string="SELECT `e`.`template`,`e`.`to`,`e`.`id`, `e`.`createdate`,`c`.`company_name` as name FROM `email_log` as `e` JOIN `customers` as `c` ON `e`.`id`=`c`.`id` WHERE `e`.`to` IN($on) $where_cust"; 
    return $query_string;
}

	// Registration Report

	public function getAllRegistrations($region_id)
	{

		$date_from = $this->session->userdata('dashboard_date_from');
      	$date_to = $this->session->userdata('dashboard_date_to');
      	$region = $region_id;

      	$condition = "c.region_id !=". 0;
        $condition_one = "c.region_id =". $region;

      	if($region == 0)
      	{
      		$placeSQL = $condition;
      	}

      	if($region != 0)
      	{
      		$placeSQL = $condition_one;
      	}

		$query = $this->db->select("e.ipaddress, e.createdate, au.name")
					->from("event_log e")
					->join("aauth_users as au", "au.id = e.user_id")
					->join("customers c", "c.id = e.user_id")
					->where("e.label", "User registered")
					->where("e.createdate >=", $date_from)
					->where("e.createdate <=", $date_to)
					->where($placeSQL)
					->get();
		$result = $query->result();
		return $result;
	}

	public function getMonthRegistrationsCount($trader_id,$type='')
	{
		$date_from = $this->input->post('date_from');
	    $date_to = $this->input->post('date_to');
	
		if(!empty($trader_id)){
			$where_trader_id ="AND c.trader_id='$trader_id'";
		}else{
			$where_trader_id='';
		}

		$where_date='';
	    if(!empty($date_from)){
			$where_date=" and  substr(c.createdate,1,10) >=substr('$date_from',1,10)
			AND substr(c.createdate,1,10)  <=substr('$date_to',1,10) ";
		}

		$limit="LIMIT ".days_in_month($month = SUBSTR(date('Y-m-d'),5,2), $year = SUBSTR(date('Y-m-d'),0,4));
		if(empty($limit=='')){
			$limit='';
		}
		$where_not_trader='';
		if($type=='general_reg'){
			$where_not_trader=" and c.trader_id =''";
		}
		$query = $this->db->query("SELECT 
								count(c.trader_id) as registrations, 
								substr(c.createdate,1,10) as createdate,
								t.first_name, 
								t.last_name,
								t.cellphone
								FROM customers as c 
								JOIN aauth_users as a ON c.id=a.user_link_id
								JOIN provinces as p ON p.id=c.province
								JOIN regions as r ON r.id=c.region_id
								JOIN traders as t ON c.trader_id=t.id
								WHERE  a.default_app='spazapp'
								$where_trader_id $where_date $where_not_trader
								GROUP BY c.trader_id
								ORDER BY c.createdate DESC limit 20");

        return $query->result_array(); 
       
		
	}

	public function getStokvelMonthRegistrationsCount($trader_id,$type='')
	{
		$date_from = $this->input->post('date_from');
	    $date_to = $this->input->post('date_to');
	
		if(!empty($trader_id)){
			$where_trader_id ="AND c.trader_id='$trader_id'";
		}else{
			$where_trader_id='';
		}

		$where_date='';
	    if(!empty($date_from)){
			$where_date=" and  substr(c.createdate,1,10) >=substr('$date_from',1,10)
			AND substr(c.createdate,1,10)  <=substr('$date_to',1,10) ";
		}

		$limit="LIMIT ".days_in_month($month = SUBSTR(date('Y-m-d'),5,2), $year = SUBSTR(date('Y-m-d'),0,4));
		if(empty($limit=='')){
			$limit='';
		}
		$where_not_trader='';
		if($type=='general_reg'){
			$where_not_trader=" and c.trader_id =''";
		}
		$query = $this->db->query("SELECT 
								 
								substr(c.createdate,1,10) as createdate
								
								FROM customers as c 
								JOIN aauth_users as a ON c.id=a.user_link_id
								JOIN provinces as p ON p.id=c.province
								JOIN regions as r ON r.id=c.region_id
								
								WHERE  a.default_app='spazapp'
								$where_trader_id $where_date $where_not_trader
								
								ORDER BY c.createdate DESC limit 20");

        return $query->result_array(); 
       
		
	}

	public function get_registration_stats($trader_id)
	{
	   	$date_from = $this->input->post('date_from');
	    $date_to = $this->input->post('date_to');
		if(!empty($trader_id)){
			$where_trader_id ="AND c.trader_id='$trader_id'";
		}else{
			$where_trader_id='';
		}

		$where_date='';
	    if(!empty($date_from)){
			$where_date=" and  substr(e.createdate,1,10) >=substr('$date_from',1,10)
			AND substr(e.createdate,1,10)  <=substr('$date_to',1,10) ";
		}

		$limit="LIMIT ".days_in_month($month = SUBSTR($date_to,5,2), $year = SUBSTR($date_to,5,2));

		$query = $this->db->query("SELECT 
								count(trader_id) as registrations, 
								substr(c.createdate,1,10) as createdate,
								t.first_name, 
								t.last_name
								FROM customers as c,
								traders as t
								WHERE c.trader_id=t.id
								AND $where_trader_id $where_date
								GROUP BY substr(c.createdate,1,10) DESC LIMIT 20");

        return $query->result_array(); 
       
		
	}
	public function get_registration_stats_2()
	{
	   	$date_from = $this->input->post('date_from');
	    $date_to = $this->input->post('date_to');
	    $where_date='';
	    if(!empty($date_from)){
			$where_date=" and  substr(e.createdate,1,10) >=substr('$date_from',1,10)
			AND substr(e.createdate,1,10)  <=substr('$date_to',1,10) ";
		}
		$query = $this->db->query("SELECT 
								count(e.id) as registrations, 
								substr(e.createdate,1,10) as createdate
								FROM customers as e, aauth_users as a
								WHERE a.user_link_id=e.id $where_date
								GROUP BY substr(e.createdate,1,10) desc LIMIT 20");

        return $query->result_array(); 
       
		
	}

	public function getMonthRegistrations($type='', $trader_id=''){
		$date_from = $this->input->post('date_from');
	    $date_to = $this->input->post('date_to');

		$where_date=" and substr(c.createdate,1,10)>=substr('$date_from',1,10) AND substr(c.createdate,1,10)<=substr('$date_to',1,10) ";
		if(empty($date_from) || empty($date_from)){
			$where_date='';
		}

		$where_not_trader='';
		if($type=='general_reg'){
			$where_not_trader=" and (c.trader_id = 0 || c.trader_id IS NULL)";
		}

		if($trader_id !=''){
			$where_trader_id=" and c.trader_id='$trader_id'";
		}else{
			$where_trader_id='';
		}


		$query = $this->db->query("SELECT 
								c.id as event_log_id,
								c.address, 
								c.createdate,
								r.name as region,
								p.name as province_name,
								a.user_link_id,
								a.username,
								c.*, 
								a.*
								FROM customers as c
								JOIN aauth_users as a ON c.id=a.user_link_id and a.default_usergroup = 8
								JOIN provinces as p ON p.id=c.province
								JOIN regions as r ON r.id=c.region_id
								WHERE 1 $where_date");

        return $query->result_array();
	}

	public function getWeekRegistrationsCount(){
		$query = $this->db->select("createdate as dates, COUNT(label) as registrations")
					->from("event_log")
					->where("label", "User Registered")
					->where("YEARWEEK(`createdate`) = YEARWEEK(CURDATE())")
					->group_by("dayofweek(createdate)")
					->get();
		$result = $query->result_array();
		return $result;
	}

	public function getRegions()
	{
		$query = $this->db->select("id, name")
					->from("regions")
					->where("id !=", 3)
					->get();
		$result = $query->result_array();
		return $result;
	}

	public function getDevices($username)
	{
		$condition = "i.user_id =". $username;
		$condition_one = "i.user_id !=". 0;

		if ($username <= 0)
		{
		  $placeSQL = $condition_one;
		}
		else
		{
		  $placeSQL = $condition;
		}

		$value = "0"; //  COUNT(i.model) as devices,
		$query = $this->db->select("a.name, i.imei, i.model, a.last_activity, c.company_name as shop_name")
					->from("aauth_user_imei i")
					->join("aauth_users as a", "a.id = i.user_id")
					->join("customers as c", "c.id = a.user_link_id")
					->where("i.model !=", $value)
					->where($placeSQL)
					->order_by("a.name")
					->get();
		$result = $query->result_array();
		return $result;
	}

	public function getDeviceChartInfo($username)
	{
		$condition = "user_id =". $username;
		$condition_one = "user_id !=". 0;

		if ($username <= 0)
		{
		  $placeSQL = $condition_one;
		}
		else
		{
		  $placeSQL = $condition;
		}

		$value = "0";
		$query = $this->db->select("COUNT(model) as total_devices, model")
					->from("aauth_user_imei")
					->where("model !=", $value)
					->where($placeSQL)
					->group_by("model")
					->get();
		$result = $query->result_array();
		return $result;
	}

	public function selectUser()
	{
		$value = "0";
		$query = $this->db->select("DISTINCT(i.user_id) as id, a.name")
					->from("aauth_user_imei i")
					->join("aauth_users as a", "a.id = i.user_id")
					->where("i.model !=", $value)
					->get();
		$result = $query->result_array();
		return $result;
	}

	function get_traders(){


		$query=$this->db->query("SELECT t.*,u.id as 'user_id', t.id, t.first_name,t.last_name,t.id, p.name, r.name as region
								FROM 
								aauth_users as u, 
								traders as t, 
								provinces as p, 
								regions as r
								WHERE t.province=p.id 
								and r.id=t.region_id 
								AND u.user_link_id = t.id 
								AND u.default_usergroup = 19");

		$traders = $query->result_array();
		foreach ($traders as $key => $value) {
			$user_id = $value['user_id'];
			$query=$this->db->query("SELECT `long`, `lat`, `createdate` 
									FROM location_log 
									WHERE user_id =  '$user_id'
									AND `long` !='' and `lat` !='' ORDER BY createdate desc LIMIT 1");

			$res = $query->row_array();

			if($res){
				$traders[$key]['long'] = $res['long'];
				$traders[$key]['lat'] = $res['lat'];
				$traders[$key]['createdate'] = $res['createdate'];
			}
		}

		return $traders;
	}

	function get_trader_location_log($trader_id){
		$where_user='';
		if(!empty($trader_id)){
			$where_user="and t.id = '$trader_id'";
		}
		$query=$this->db->query("SELECT t.id, 
								l.*,
								u.*,
								t.*, 
								l.createdate, 
								r.name as region, 
								p.name as province
								from location_log l, 
								aauth_users as u, 
								traders as t,
								provinces as p,
								regions as r
								WHERE u.user_link_id = t.id 
								and l.user_id=u.id 
								and t.province=p.id 
								and r.id=t.region_id 
								$where_user
								ORDER BY l.createdate desc LIMIT 1");
		return $query->result_array();

	}

	function tradersById($trader_id){
		$query=$this->db->query("SELECT * FROM traders WHERE id='$trader_id'");
		return $query->row_array();
	}

	function customer_user_link_id($user_link_id){
		$sql="SELECT
			c.company_name, 
			t.first_name, 
			t.last_name FROM customers as c 
			LEFT JOIN traders as t 
			ON c.trader_id=t.id 
			WHERE c.id='$user_link_id'";

		$query=$this->db->query($sql);
		return $query->row_array();
	}


	function get_user($user_id){
	      $query = $this->db->query("SELECT * FROM `aauth_users` WHERE id = $user_id");
	      return $query->row_array();
	  }



    public function getLoggedIn(){
      $date_from = $this->input->post('date_from');
      $date_to = $this->input->post('date_to');
      $where_date='';
      if(!empty($date_to)){
        $where_date="and createdate>='$date_from' and createdate<='$date_to'";
      }
      $query=$this->db->query("SELECT * FROM event_log WHERE action = 'login_successful'
       $where_date GROUP BY user_id");

      return $query->num_rows();
    }


    function get_trader_locations($user_id,$date_from,$date_to){
    	$where_date=" and createdate>='$date_from' and createdate<='date_to'";
    	if(empty($date_to) || empty($date_from)){
    		$where_date='';
    	}
    	$query=$this->db->query("SELECT * FROM 
    		location_log l, 
    		aauth_users a 
    		WHERE a.id=l.user_id and l.user_id=? $where_date 
    		ORDER BY l.createdate DESC", array($user_id));

      return $query->result_array();

    }

     function get_current_location($user_id){
    	$query=$this->db->query("SELECT * FROM location_log WHERE user_id=? ORDER BY createdate DESC",array($user_id));

      return $query->row_array();

    }

    function get_order_info($order_id){
		$query=$this->db->query("SELECT * FROM orders WHERE id='$order_id'");
		return $query->row_array();

    }

    function get_taptuck_registration_stats($type){
    	if($type=='daily'){
    		$group_by="substr(p.created_at,1,10)";
    		$label="substr(p.created_at,1,10)";
    		$kids_count='';
    		$limit=" LIMIT 31";
    	}
    	if($type=='tuckshop'){
    		$group_by="k.merchant_id";
    		$label="m.name";
    		$kids_count=", count(k.merchant_id) as kids_count";
    		$limit='';
    	}

    	$date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');

    	if(!empty($date_from) && !empty($date_to)){
    		$where_date =" and p.created_at>='$date_from' and p.created_at<='$date_to'";
    	}else{
    		$where_date='';
    	}
    	$query=$this->db->query("SELECT $label as label, m.id as merchant_id,
    							count(DISTINCT k.parent_id) as registrations_count
    							$kids_count
    							FROM 
					    		tt_kids as k, 
					    		tt_parents as p, 
					    		tt_merchants as m 
					    		WHERE m.id=k.merchant_id 
					    		and p.id=k.parent_id $where_date
					    		GROUP BY $group_by ORDER BY p.created_at DESC $limit");
		return $query->result_array();
    }

    function get_taptuck_registration($merchant_id=''){

    	$date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to'); 
    	
    	if(!empty($merchant_id)){
    		$where_merchant = " and k.merchant_id='$merchant_id'";
    	}else{
    		$where_merchant='';
    	}
    	if(!empty($date_from) && !empty($date_to)){
    		$where_date =" and p.created_at>='$date_from' and p.created_at<='$date_to'";
    	}else{
    		$where_date='';
    	}
	   	$query=$this->db->query("SELECT
	   	 						p.id as parent_id,
	   	 						p.first_name, 
						   	 	p.last_name, 
						   	 	p.created_at as createdate
						   	 	FROM 
					    		tt_kids as k, 
					    		tt_parents as p
					    		WHERE p.id=k.parent_id $where_merchant $where_date
					    		GROUP BY k.parent_id");
	   	 $result = $query->result_array();

	   	 $return = array();
	   	 foreach ($result as $key => $value) {
	   	 
	   	 	$return['registrations'][] = array(
	   	 		'parent_id'=>$value['parent_id'],
	   	 		'first_name'=>$value['first_name'],
	   	 		'last_name'=>$value['last_name'],
	   	 		'kids_count'=>$this->get_number_of_kids($value['parent_id'], $merchant_id),
	   	 		'createdate'=>$value['createdate']
	   	 		) ;
	   	 }
	   	
	   	 return $return;
	 
    }

    function get_number_of_kids($parent_id, $merchant_id){
    	if(!empty($merchant_id)){
    		$where_merchant = " and merchant_id='$merchant_id'";
    	}else{
    		$where_merchant='';
    	}
    	$query=$this->db->query("SELECT * FROM tt_kids WHERE  parent_id='$parent_id' $where_merchant");
    	return $query->num_rows();
    }
	
	function get_number_of_merchant($parent_id){
    	$query=$this->db->query("SELECT count(DISTINCT merchant_id) as total FROM tt_kids WHERE parent_id=?",array($parent_id));
    	return $query->row_array()['total'];
    }

   
}
