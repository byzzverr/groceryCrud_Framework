<?php

class Task_Model extends CI_Model {

  function __construct() {
    parent::__construct();
    $this->load->model('customer_model');
  }

	function getActiveTasks($user_id) {
		$customer = $this->customer_model->get_customer_from_user_id($user_id);
        $customer_type = $customer['customer_type'];
		$region = $this->customer_model->get_customer_region($customer['id']);
        $region_id = $region['region_id'];
        $region_name = $region['name'];

		$this->db->select("bc_tasks.task_id, bc_tasks.name, bc_tasks.description, bc_tasks.type, bc_tasks.type_link_id, bc_tasks.reward_amount, bc_tasks.createdate, bc_task_results.status, gbl_statuses.name as 'status_name'")->from("bc_tasks")
			->join('bc_task_results', 'bc_tasks.task_id = bc_task_results.task_id AND bc_task_results.user_id = ' . $user_id, 'LEFT')
			->join('gbl_statuses', 'bc_task_results.status = gbl_statuses.id', 'LEFT')
			->join('bc_t_to_region', "bc_tasks.task_id = bc_t_to_region.task_id AND bc_t_to_region.region_id = $region_id",'JOIN')
			->join('bc_t_to_ct', "bc_tasks.task_id = bc_t_to_ct.task_id AND bc_t_to_ct.customer_type_id = $customer_type",'JOIN')
			->where("bc_tasks.status!=",7)
			->where("bc_tasks.status!=",14)
			->where("bc_tasks.start_date<=", date('Y-m-d H:i:s'))
			->where("bc_tasks.end_date>=", date('Y-m-d H:i:s'))
			->order_by("bc_tasks.start_date", "DESC");

		$query = $this->db->get();

		if($query->num_rows() > 0){
			$tasks = $query->result_array();
			foreach ($tasks as $key => $task) {

				if($task['type'] == 'pos'){
					$tasks[$key]['photosnap_id'] = $this->get_photosnap_id_from_pos($task['type_link_id']);
				}

				if($task['status'] == null){
					$tasks[$key]['status'] = 1;
					$tasks[$key]['status_name'] = 'New';
				}
			}
			return $tasks;
		}else{
			return null;
		}
	}

	function getActiveTasksCount($user_id, $table='user') {

		if($table != 'user'){
			$user = $this->customer_model->get_user_from_customer_id($user_id);
			$user_id = $user['id'];
		}
		$count = 0;
		$this->db->select("bc_tasks.task_id, bc_task_results.status")->from("bc_tasks")
			->join('bc_task_results', 'bc_tasks.task_id = bc_task_results.task_id AND bc_task_results.user_id = ' . $user_id, 'LEFT')
			->join('gbl_statuses', 'bc_task_results.status = gbl_statuses.id', 'LEFT')
			->where("bc_tasks.status!=",7)
			->where("bc_tasks.status!=",14)
			->where("bc_tasks.start_date<=", date('Y-m-d H:i:s'))
			->where("bc_tasks.end_date>=", date('Y-m-d H:i:s'))
			->order_by("bc_tasks.start_date", "DESC");
			$query = $this->db->get();
		if($query->num_rows() > 0){
			$tasks = $query->result_array();
			foreach ($tasks as $key => $task) {

				if($task['status'] == null){
					$tasks[$key]['status'] = 1;
					$tasks[$key]['status_name'] = 'New';
				}

				if($tasks[$key]['status'] == 1){
					$count++;
				}
			}
			return $count;
		}else{
			return 0;
		}
	}

	function get_photosnap_id_from_pos($pos_id){
		$result = $this->db->query("SELECT photosnap_id FROM bc_pos WHERE id = ?", array($pos_id));
		$return = $result->row_array();
		return $return['photosnap_id'];
	}

	function get_task_from_type_link($type,$link_id, $loop=0) {

		$this->db->select("bc_tasks.*, gbl_statuses.name as 'status_name'")->from("bc_tasks")
			->join('gbl_statuses', 'bc_tasks.status = gbl_statuses.id')
			->where("bc_tasks.type=", $type)
			->where("bc_tasks.type_link_id=", $link_id);
			$query = $this->db->get();

		if($query->num_rows() > 0){
			return $query->row_array();
		}elseif($type == 'survey' && $loop == 0){
			return $this->get_task_from_type_link('price_survey',$link_id,$loop++);
		}else{
			return null;
		}
	}

	function change_task_status($type, $link_id, $user_id, $status, $task_id=0){
		$this->load->model('task_model');

		if($task_id == 0){
			$task = $this->get_task_from_type_link($type, $link_id);
			$task_id = $task['task_id'];
		}

		if($task_id > 0){
			$this->update_insert_task_result($task_id, $user_id, $status);
		}
	}

	function save_order_id_to_task($task_id, $order_id, $user_id){

			$this->db->query("UPDATE bc_task_results SET status = 10, order_id = ?, createdate = NOW() WHERE task_id = ? AND user_id = ?", array($order_id, $task_id, $user_id));
			return true;
	}

	function createTask($user_id, $prefix, $description, $amount, $expiry_date, $active=1)
	{
		$this->db->insert("task", array("user_id" => $user_id, "prefix"=>$prefix,"description"=>$description,"amount"=>$amount,"expiry_date"=>$expiry_date,"active"=>$active));
		$responseId = $this->db->insert_id();
	}
	
	function setTaskAvailability($id,$active)
	{
		$this->db->query("UPDATE task SET active = ? WHERE task_id = ?", array($active,$id));
			return true;
	}

	function get_active_bc_by_type($type){

		$table = false;
		$field = 'name';
		$where = '';
		switch ($type) {
			case 'survey':
				$table = 'survey_list';
				$where = " type = 'Normal' AND ";
				$field = 'title';
				break;
			case 'price_survey':
				$table = 'survey_list';
				$where = " type = 'Price' AND ";
				$field = 'title';
				break;
			case 'photosnaps':
				$table = 'bc_photosnaps';
				break;
			case 'pos':
				$table = 'bc_pos';
				break;
			case 'training':
				$table = 'bc_training';
				break;
		}

		$this->db->select("id, $field as 'name'")
					->from($table)
					->where($where . "status !=", 7);
					$query = $this->db->get();
				if($query->num_rows() > 0){
					return $query->result_array();
				}else{
					return null;
				}
	}
  
    function updateTaskStatusByPrefix($user_id='', $prefix='', $status='') {
		if (($user_id!='') && ($prefix!='') &&($status!=''))
		{
			$this->db->query("UPDATE task SET status = ? WHERE user_id = ? and prefix = ?", array($status, $user_id,$prefix));
			return true;
		}
		else
		{
			return false;
		}
	}
	
	function updateTaskStatusById($id, $status) {
		if ($id!='')
		{
			$this->db->query("UPDATE task SET status = ? WHERE id = ?", array($status, $id));
			return true;
		}
		else
		{
			return false;
		}
	}
	
	function getCustomerByPrefix($prefix) {
		if ($id!='')
		{
			$this->db->query("UPDATE task SET status = ? WHERE id = ?", array($status, $id));
			return true;
		}
		else
		{
			return false;
		}
	}

	function insert_task_result($task_id, $user_id, $status){
		
		if($task_id == null || $task_id == ''){
			$task_id = 0;
		}
		$this->db->query("INSERT INTO bc_task_results (task_id, user_id, status, createdate) VALUES (?,?,?,NOW())", array($task_id, $user_id, $status));
	}

	function update_insert_task_result($task_id, $user_id, $status){
		if ($task_id == null){
			$task_id = 0;
		}
		$result = $this->fetch_task_result($task_id, $user_id);

		if($result){
			//only change to viewed if status = 1 (new)
			if($status == 2 && $result['status'] != 1){
				return true;
			}

			//if current status is approved. It must never change.
			if($result['status'] == 8){
				return true;
			}
			$this->db->query("UPDATE bc_task_results SET status = ?, createdate = NOW() WHERE task_id = ? AND user_id = ?", array($status, $task_id, $user_id));
		}else{
			$this->insert_task_result($task_id, $user_id, $status);
		}
	}

	function fetch_task_result($task_id, $user_id){
		$query = $this->db->query("SELECT id, status FROM bc_task_results WHERE task_id = ? AND user_id = ?", array($task_id, $user_id));
		if($query->num_rows() == 0){
			return false;
		}else{
			return $query->row_array();
		}

	}

	// Bc Task Results

	public function getStatusById($id)
	{
		$query = $this->db->select("*")
					->from("gbl_statuses")
					->where("id", $id)
					->get();
		$result = $query->row();
		return $result;
	}


	public function getUsernameById($id)
	{
		$query = $this->db->select("a.id,a.name, ag.name as default_usergroup")
					->from("aauth_users as a")
					->join("aauth_groups as ag","a.default_usergroup=ag.id","LEFT")
					->where("a.id", $id)
					->get();
		$result = $query->row();
		return $result;
	}

	public function get_task($id)
	{
		$query = $this->db->select("*")
					->from("bc_tasks")
					->where("task_id", $id)
					->get();
		$result = $query->row_array();

		if($query)
		{
			return $result;
		}
		else
		{
			return false;
		}
	}

	public function getTasknameById($id)
	{
		$query = $this->db->select("name")
					->from("bc_tasks")
					->where("task_id", $id)
					->get();
		$result = $query->row();

		if($query)
		{
			return $result;
		}
		else
		{
			return false;
		}
	}

	public function getTaskDetails($id)
	{
		$query = $this->db->select("b.task_id, b.name, b.description, b.type, b.type_link_id, b.reward_amount, b.start_date, b.end_date, g.name as status")
					->from("bc_tasks b")
					->join("bc_task_results as r", "r.task_id = b.task_id")
					->join("gbl_statuses as g", "g.id = r.status")
					->where("b.task_id", $id)
					->get();
		$result = $query->row();
		return $result;
	}

	public function getSurveyByName($id)
	{
		$query = $this->db->select("s.id, s.title, COUNT(q.prefix) as questions")
					->from("survey_list s")
					->join("survey_questions as q", "q.prefix = s.id")
					->where("s.id", $id)
					->group_by("s.id")
					->get();
		$result = $query->row();
		return $result;
	}

	public function getUserByName($username)
	{
		$query = $this->db->select("id, email, cellphone")
					->from("aauth_users")
					->where("name", $username)
					->get();
		$result = $query->row();
		return $result;
	}

	public function getSuveryResults($survey_id, $user_id)
	{
      
		$query = $this->db->select("sr.id, sq.question_text, so.option_text")
					->from("survey_response_answers sr")
					->join("survey_questions as sq", "sq.id = sr.question_id")
					->join("survey_options as so", "so.id = sr.option_id")
					->where("sr.survey_id", $survey_id)
					->where("sr.user_id", $user_id)
					->get();
		$result = $query->result_array();
      
		return $result;
	}

	public function responsesCount($survey_id, $user_id)
	{
		$query = $this->db->select("id, option_id, text")
					->from("survey_response_answers")
					->where("survey_id", $survey_id)
					->where("user_id", $user_id)
					->get();
		$result = $query->result_array();
		return $result;
	}

	public function countQuestionAnswered($survey_id, $user_id)
	{
		$query = $this->db->select("id, option_id, text")
					->from("survey_response_answers")
					->where("survey_id", $survey_id)
					->where("user_id", $user_id)
					->get();
		$result = $query->result_array();

		$count = 0;

		foreach ($result as $v) 
		{
			if($v['option_id'] != 0)
			{
				$count = $count + 1;
			}
			elseif ($v['text'] != null) 
			{
				$count = $count + 1;
			}
			else
			{
				$count = $count + 0;
			}
		}

		return $count;
	}

	public function getPriceSuveryResults($survey_id, $user_id)
	{
		$query = $this->db->select("sr.id, sq.question_text, sr.text")
					->from("survey_response_answers sr")
					->join("survey_questions as sq", "sq.id = sr.question_id")
					->where("sr.survey_id", $survey_id)
					->where("sr.user_id", $user_id)
					->get();
		$result = $query->result_array();
		return $result;
	}

	public function getSurveyQuestionsAndAnswers($survey_id, $user_id)
	{
		$query = $this->db->select("id, question_text")
					->from("survey_questions")
					->where("prefix", $survey_id)
					->get();
		$return = $query->result_array();

      	foreach ($return as $key => $question) {

	        $answers = $this->getUserAnswers($question['id'], $user_id);
	        $return[$key]['answer'] = $answers;
      	}

      	return $return;
	}

	function getUserAnswers($question_id, $user_id)
	{
		$query = $this->db->select("sr.id, sr.option_id, sr.text, so.option_text") // sr.text,
					->from("survey_response_answers as sr")
					->join("survey_options as so", "so.id = sr.option_id", "LEFT")
					->where("sr.question_id", $question_id)
					->where("sr.user_id", $user_id)
					->get();
		$result = $query->row();

		$return = '';

		if($result)
		{
			if($result->option_id > 0 && $result->text == '')
			{
				$return = $result->option_text .' (ID:'.$result->option_id.') Question Answered.';
			}
			else if($result->text != '')
			{
				$return = $result->text;
			}
			else
			{
				$return = '<span style="color: red;">'.$result->option_id.'</span>';
			}
		}
		else
		{
			$return = '<span style="color: red;">Not Answered</span>';
		}

		return $return;
	}

	public function getPhotosnapsById($id, $user_id='')
	{
		if($user_id == ''){

			$query = $this->db->select("bc.picture as required, bcr.picture as submitted")
						->from("bc_photosnaps bc")
						->join("bc_photosnaps_responses as bcr", "bcr.photosnap_id = bc.id")
						->where("bc.id", $id)
						->get();
			$result = $query->row();
			
		}else{

			$query = $this->db->select("bc.picture as required, bcr.picture as submitted")
						->from("bc_photosnaps bc")
						->join("bc_photosnaps_responses as bcr", "bcr.photosnap_id = bc.id")
						->where("bc.id", $id)
						->where("bcr.user_id", $user_id)
						->get();
			$result = $query->row();
		}

		

		return $result;
	}

	public function getPosPhotosnaps($id)
	{
		$query = $this->db->select("p.id, ps.picture")
					->from("bc_pos as p")
					->join("bc_photosnaps as ps", "ps.id = p.photosnap_id")
					->where("p.id", $id)
					->get();
		$result = $query->row();
		return $result;
	}

	public function getResultStatus($id)
	{
		$query = $this->db->select("b.id, b.user_id, g.name")
					->from("bc_task_results b")
					->join("gbl_statuses as g", "g.id = b.status")
					->where("b.id", $id)
					->get();
		$result = $query->row();
		return $result;
	}

	public function getTaskResult($id)
	{
		$query = $this->db->select("b.*, g.name as 'status'")
					->from("bc_task_results b")
					->join("gbl_statuses as g", "g.id = b.status")
					->where("b.id", $id)
					->get();
		$result = $query->row();
		return $result;
	}

	public function has_task_been_rewarded_before($task_id, $user_id)
	{
		$user = $this->aauth->get_user($user_id);
		$this->load->model('financial_model');
		return $this->financial_model->has_task_been_rewarded_before((isset($user->username) ? $user->username : ''), $task_id);
	}

	public function updateTaskRewarded($task_id, $user_id, $transaction_id, $note=false)
	{
		$data['status'] = "8";
		$data['transaction_id'] = $transaction_id;
		if($note){
			$data['note'] = $note;
		}
		$this->db->where("user_id", $user_id)->where("task_id", $task_id)->update("bc_task_results", $data);
		$this->notify_thirdparty($user_id, $task_id, $data['status'], $note); 
	}

	public function updateTaskDeclined($task_id, $user_id, $note='')
	{
		$data['status'] = 18;
		if($note){
			$data['note'] = $note;
		}
		$this->db->where("user_id", $user_id)->where("task_id", $task_id)->update("bc_task_results", $data);
		$this->notify_thirdparty($user_id, $task_id, $data['status'], $note); 
	}

	function notify_thirdparty($user_id, $task_id, $status, $note){

		$this->load->model('user_model');
		$user = $this->user_model->get_general_user($user_id);

		if(isset($user->customer_info) && $user->customer_info['first_name'] == 'mvendr'){
			$postfields = array(
				"store_id" => $user->user_link_id,
				"task_id" => $task_id,
				"status_id" => $status,
				"task_note" => $note
			);

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL,"http://172.99.68.62/spazapp/statusCallback.php");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'postman-token: 57548d5a-9720-fdba-f73a-4f498f50d683',
			    'Content-Type: multipart/form-data;'
			    ));
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, 
			          http_build_query($postfields));

			// receive server response ...
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$server_output = curl_exec ($ch);

			curl_close ($ch);

			var_dump($server_output);
		}
	}

	public function get_user_from_result_id($result_id){

		$query = $this->db->select("b.user_id")
					->from("bc_task_results b")
					->where("b.id", $result_id)
					->get();
		$result = $query->row();
		return $result->user_id;

	}
    public function get_task_type_by_id($task_id)
	{
		$query = $this->db->query("SELECT * FROM bc_tasks  WHERE task_id ='$task_id'");
				
		return $query->result();
	}
    
    function get_active_bc_by_type_id($type, $type_link_id){

		$table = false;
		$field = 'name';
		$where = '';
		switch ($type) {
			case 'survey':
				$table = 'survey_list';
				$where = " type = 'Normal' AND ";
				$field = 'title';
				break;
			case 'price_survey':
				$table = 'survey_list';
				$where = " type = 'Price' AND ";
				$field = 'title';
				break;
			case 'photosnaps':
				$table = 'bc_photosnaps';
				break;
			case 'pos':
				$table = 'bc_pos';
				break;
			case 'training':
				$table = 'bc_training';
				break;
		}

		$this->db->select("id, $field as 'name'")
					->from($table)
					->where($where . "status !=", 7)
					->where("id", $type_link_id);
					$query = $this->db->get();
				if($query->num_rows() > 0){
					return $query->result_array();
				}else{
					return null;
				}
	}

	function get_spark_store_completed_taks($spark_id){

		$query = $this->db->query("SELECT * from bc_task_results where user_id IN (select u.id FROM aauth_users u, customers c WHERE u.user_link_id = c.id and u.default_usergroup = 8 and c.trader_id = $spark_id )");
		return $query->num_rows();
	}


	function get_active_task($task_id){
		
		$date=date('Y-m-d H:i:s');
		$query = $this->db->query("SELECT * from bc_tasks 
								WHERE start_date<= '$date' 
								AND end_date>= '$date' and task_id = ?", array($task_id));
	
		if($query->num_rows() > 0){
			return true;
		}else{
			return null;
		}
		
	}

	function getPosActiveTask($type_link_id){
		$date=date('Y-m-d H:i:s');
		$query = $this->db->query("SELECT * from bc_tasks 
								WHERE start_date<= '$date' 
								and type='pos'
								AND end_date>= '$date' and type_link_id = ?", array($type_link_id));
	
		if($query->num_rows() > 0){
			return true;
		}else{
			return null;
		}

	}

	function getBcTaskResult($user_id, $task_id, $task_result_id){
		$query = $this->db->query("SELECT b.*, g.name as status_name from bc_task_results as b 
								JOIN gbl_statuses as g ON b.status=g.id  
								where b.user_id ='$user_id' and b.id='$task_result_id'");
		return $query->row_array();

	}

	function get_task_status($user_id,$photosnap_id){
		$query = $this->db->query("SELECT g.name,r.id FROM bc_tasks as t, bc_task_results as r,gbl_statuses as g WHERE r.task_id=t.task_id and g.id=r.status and r.user_id='$user_id' and t.type_link_id='$photosnap_id'");
		return $query->row_array();

	}

	function get_all_tasks($date_from, $date_to, $region=""){
		if(!empty($region)){
			$where_region = " and c.region_id=$region";
		}else{
			$where_region = "";
		}
		if(isset($date_from) && !empty($date_to)){
			$where_date =" and r.createdate>='$date_from' and r.createdate<='$date_to'";
		}else{
			$where_date='';
		}

		if(isset($_POST['search_text']) && !empty($_POST['search_text'])){
			$search_text = $_POST['search_text'];
			$or_like = " and a.name LIKE '%$search_text%' or a.default_usergroup LIKE '%$search_text%' or t.name LIKE '%$search_text%' or t.type LIKE '%$search_text%' or g.name LIKE '%$search_text%' or rg.name LIKE '%$search_text%'";
		}else{
			$or_like='';
		}

		
		$query = $this->db->query("SELECT r.status as status_id, r.id, r.task_id, r.user_id, a.name as user, gr.name as store_type, t.name as task, t.type as task_type, r.order_id, g.name as status, rg.name as region, r.createdate, c.location_lat, c.location_long,s.name as stokvel,cu.name as role
FROM bc_task_results as r 
LEFT JOIN bc_tasks as t ON r.task_id=t.task_id 
LEFT JOIN gbl_statuses as g ON g.id=r.status 
LEFT JOIN aauth_users as a ON r.user_id=a.id 
LEFT JOIN customers as c ON a.user_link_id=c.id 
LEFT JOIN regions as rg ON rg.id=c.region_id 
LEFT JOIN aauth_groups as gr ON a.default_usergroup=gr.id 
LEFT JOIN user_stokvel_rel sr ON r.user_id = sr.user_id
LEFT JOIN stokvels s ON sr.stokvel_id = s.id
LEFT JOIN customer_types cu ON sr.role_id = cu.id WHERE 1 $or_like $where_date $where_region");
//echo $this->db->last_query();exit;
		return $query->result_array();

	}
function get_all_supps365_tasks($date_from, $date_to){

		if(isset($date_from) && !empty($date_to)){
			$where_date =" and r.createdate>='$date_from' and r.createdate<='$date_to'";
		}else{
			$where_date='';
		}

		if(isset($_POST['search_text']) && !empty($_POST['search_text'])){
			$search_text = $_POST['search_text'];
			$or_like = " and a.name LIKE '%$search_text%' or a.default_usergroup LIKE '%$search_text%' or t.name LIKE '%$search_text%' or t.type LIKE '%$search_text%' or g.name LIKE '%$search_text%' or rg.name LIKE '%$search_text%'";
		}else{
			$or_like='';
		}

		$query = $this->db->query("SELECT 
			r.status as status_id,
			r.id,
			r.task_id,
			r.user_id,
			a.name as user,
			gr.name as store_type,
			t.name as task,
			t.type as task_type,
			r.order_id,
			g.name as status,
			rg.name as region,
			r.createdate, 
			c.company_name
			FROM bc_task_results as r 
			LEFT JOIN bc_tasks as t ON r.task_id=t.task_id 
			LEFT JOIN gbl_statuses as g ON g.id=r.status 
			LEFT JOIN aauth_users as a ON r.user_id=a.id
			LEFT JOIN customers as c ON a.user_link_id=c.id
			LEFT JOIN regions as rg ON rg.id=c.region_id
			LEFT JOIN aauth_groups as gr ON a.default_usergroup=gr.id
			WHERE a.default_usergroup!=19 $or_like $where_date");

		return $query->result_array();

	}

	function get_quick_bc_task(){

		$query = $this->db->query("SELECT a.id as user_id, 
			a.name, 
			c.location_lat, 
			c.location_long,
			c.company_name,
			rg.name as region,
			(SELECT `picture`  FROM bc_photosnaps_responses 
			WHERE photosnap_id = 6 and user_id=r.user_id order by createdate desc limit 1) as outside_store,
			(SELECT `picture`  FROM bc_photosnaps_responses 
			WHERE photosnap_id = 7 and user_id=r.user_id  order by createdate desc limit 1) as inside_store,
			r.createdate,
			ct.name as customer_type,
			c.valid
			FROM bc_task_results as r 
			JOIN bc_tasks as t ON r.task_id=t.task_id 
			JOIN bc_photosnaps as bcp ON bcp.id=t.type_link_id 
			JOIN gbl_statuses as g ON g.id=r.status 
			JOIN aauth_users as a ON r.user_id=a.id
			JOIN customers as c ON a.user_link_id=c.id
			JOIN customer_types as ct ON c.customer_type=ct.id
			LEFT JOIN regions as rg ON rg.id=c.region_id
			JOIN aauth_groups as gr ON a.default_usergroup=gr.id WHERE bcp.id IN (6, 7) GROUP BY r.user_id");
		return $query->result_array();


	}
	

}