<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Task extends CI_Controller {

  public function __construct()
  {
      parent::__construct();
	    $this->load->library("Aauth");
      $this->load->helper('url');
      $this->load->model("task_model");
	    $this->load->model("customer_model");
	    $this->load->model("survey_model");
      $this->load->model('event_model');
      $this->load->model('financial_model');
      $this->load->library('grocery_CRUD');
		  $this->load->library('javascript_library');
	    $this->user = $this->aauth->get_user();
      $this->app_settings = get_app_settings(base_url());
	    //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Survey')){
            $this->event_model->track('error','permissions', 'Survey');
            redirect('/admin/permissions');
        } 
  }
function show_view($view, $data=''){
      $data['user_info'] = $this->user;
      $data['app_settings'] = $this->app_settings;
      $this->load->view($this->app_settings['app_folder'].'include/header', $data);
      $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), $data);
      $this->load->view($this->app_settings['app_folder'].$view, $data);
      $this->load->view($this->app_settings['app_folder'].'include/footer', $data);
    }

    function crud_view($output){
        
        $output->user_info = $this->user;
        $output->app_settings = $this->app_settings;
        $this->load->view('include/crud_header', (array)$output);
        $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), (array)$output);
        $this->load->view('crud_view', (array)$output);
        $this->load->view('include/crud_footer', (array)$output);
    }
	
	public function create()
	{
		$data['script']='';
		$data['customers']=$this->customer_model->get_all_customers();
		$data['surveys']=$this->survey_model->getActiveSurveys();
		$data['date_from']=date("Y-m-d H:i");
		$data['script'] .= '
        $(function() {
            $("#datepicker" ).datepicker( { dateFormat: "yy-mm-dd" });
        });
        ';
		$data['page_title'] = 'Task - Create';
		$this->show_view('taskCreate', $data);
	}

	
	function index(){

        try{
            $crud = new grocery_CRUD();
			
			
            
            $crud->set_table('bc_tasks');
            $crud->set_subject('BC Task');

            $this->session->set_userdata('table', 'bc_tasks');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));

            $crud->set_relation('status','gbl_statuses','name');
            $crud->set_relation('user_id','aauth_users','name');
        		$crud->set_relation('type_link_id','survey_list','title');

      			$crud->columns('name','type','reward_amount','trader_reward','start_date','end_date','status_2','createdate');
      		  $crud->fields('name', 'description','type','type_link_id','limit','reward_amount','trader_reward','spazapp_reward','regions','customer_types','start_date','end_date','createdate');

            $crud->set_relation_n_n('regions', 'bc_t_to_region', 'regions', 'task_id', 'region_id', 'name','priority');

            $crud->set_relation_n_n('customer_types', 'bc_t_to_ct', 'customer_types', 'task_id', 'customer_type_id', 'name','priority');

            $crud->required_fields('date','title','category_id','amount');

            $crud->callback_add_field('type_link_id',array($this,'__task_type_link_id'));
            $crud->callback_add_field('description',array($this,'__task_description'));
            $crud->callback_edit_field('type_link_id',array($this,'_task_type_link_id'));

            $crud->callback_column('status_2',array($this,'_callback_status_2'));
            $crud->display_as('status_2',"Active/Inactive");
            $crud->display_as('reward_amount',"Customer Reward");

            //$crud->change_field_type('createdate','invisible');

            $crud->callback_before_insert(array($this, 'set_createdate'));
            $crud->unset_delete();

            $output = $crud->render();

            $output->page_title = 'Tasks';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }

    

    function _callback_status_2($value,$row){

      $result = $this->task_model->get_active_task($row->task_id);

      if($result){
        return "Active";
      }else{
        return "<p style='color:red'>Inactive</p>";
      }

    }

    function __task_description(){
      return "<textarea>"."</textarea>";
    }

    function get_active_bc_by_type($type=false){

        $html = 'Please select a BC type';

        if($type){
           
            $result = $this->task_model->get_active_bc_by_type($type);

            $html = '';

            foreach ($result as $key => $task) {
                $html .= '<option value="'.$task['id'].'">'.$task['name'].'</option>';
            }

        }
        echo $html;       
    }

    function __task_type_link_id(){
       
        return '<select id="field-type_link_id" name="type_link_id"></select>';
    }
    
    function _task_type_link_id($value, $primary_key){
        $html = 'Please select a BC type';
        $type_link_id = '';
        $task_name = '';
        $type='';
        
        $type_results = $this->task_model->get_task_type_by_id($primary_key);
        
        foreach($type_results as $item){
            $type = $item->type;
            $type_link_id = $item->type_link_id;
        }
        
        if(!empty($type)){

            $result1 = $this->task_model->get_active_bc_by_type_id($type, $type_link_id);

            foreach ($result1 as $key => $task) {
                $html = '<option value="'.$task['id'].'">'.$task['name'].'</option>';
            } 

            $result = $this->task_model->get_active_bc_by_type($type);

            foreach ($result as $key => $task) {
                $html .= '<option value="'.$task['id'].'">'.$task['name'].'</option>';
            }   
        }
        
        
        return '<select id="field-type_link_id" name="type_link_id">'.$html.'</select>';
    }

    public function save()
    { 
      if (
      (isset($_POST['submit_ta'])) &&
      (isset($_POST['customer'])) &&
      (isset($_POST['survey'])) &&
      (isset($_POST['description'])) &&
      (isset($_POST['amount'])) &&
      (isset($_POST['date_from'])) ) 
      {
        $this->task_model->createTask($_POST['customer'],$_POST['survey'], $_POST['description'], $_POST['amount'], $_POST['date_from']);
        $data['script']='';
        $data['customers']=$this->customer_model->get_all_customers();
        $data['surveys']=$this->survey_model->getActiveSurveys();
        $data['date_from']=date("Y-m-d H:i");
        $data['script'] .= '
        $(function() {
            $("#datepicker" ).datepicker( { dateFormat: "yy-mm-dd" });
        });
        ';
        $data['page_title'] = 'Task - Create';
        $data['success']=true;
      }
      else
      {
          $data['failed']=true;
      }
        $this->show_view('taskCreate', $data);
      }

      function set_createdate($post_array){

            $post_array['createdate'] = date("Y-m-d H:i:m");
            return $post_array;
      }

      public function task_results()
      {

/*        
          $to = '0827378714';
          $message = 'testing new clickatell account';
          $this->comms_model->send_sms($to, $message);

          exit;
*/

          $crud = new grocery_CRUD();
          
          $crud->set_table('bc_task_results');
          $crud->set_subject('Bc Task Results');
    
          $crud->set_relation('task_id','bc_tasks','type');
          $crud->set_relation('task_id','bc_tasks','name');
         // / $crud->set_relation('status','gbl_statuses','name');
          $crud->set_relation('user_id','aauth_users','name');
          $crud->set_relation('user_id','aauth_users','default_usergroup');

         
             
          $crud->columns('id','user','store_type','task','task_type','order_id','status','createdate');
          $crud->callback_column('status',array($this,'_status_callback'));
          $crud->callback_column('task_type',array($this,'_callback_task_type'));
          $crud->callback_column('store_type',array($this,'_callback_user_group'));
          $crud->callback_column('user',array($this,'_username_callback'));
          $crud->callback_column('user_id',array($this,'_callback_user_id'));
          $crud->order_by('createdate','desc');

          $crud->callback_column('task',array($this,'_taskname_callback')); 
          
          $crud->unset_edit();
          $crud->unset_add();
          $crud->unset_delete();
       

          $output = $crud->render();

          $output->page_title = 'Brand Connect Task Results';

          $this->crud_view($output);

      }

      function task_result(){

        $data['date_from'] = $this->input->post('date_from');
        $data['date_to'] = $this->input->post('date_to');
        $data['search_text'] = $this->input->post('search_text');

        $dataset = false;
        $data['results'] = $this->task_model->get_all_tasks($data['date_from'], $data['date_to']);
        $data['page_title'] = "Brand Connect Task Results";
          $this->show_view('bc_task_results', $data);
      }


      function _callback_task_type($value,$row){
        $survey=$this->survey_model->get_task_type($row->task_id);

        return $survey['type'];
      }

      function _callback_user_group($value,$row){

        $user=$this->task_model->getUsernameById($row->user_id);
          if($user)
          {
            return $user->default_usergroup;
          }
          else
          {
            return "Null";
          }     
        
      }

      function _status_callback($value, $row)
      {
          
          $status = $this->task_model->getStatusById($row->status);

          if ($row->status == 3 || $row->status == 11 ||  $row->status == 5 ||  $row->status == 8)
          {
            $user_id = $this->task_model->get_user_from_result_id($row->id);
            return '<a href="/task/update_task/'.$user_id.'/'.$row->task_id.'/'.$row->id.'">'.$status->name.'</a>';
          }
          else
          {
            return $status->name;
          }
      }

      function _username_callback($value, $row)
      {
          $username = $this->task_model->getUsernameById($row->user_id);

          if($username)
          {
            return $username->name;
          }
          else
          {
            return "Not A Customer";
          }        
      }

 
      function _callback_user_id($value, $row)
      {
          $username = $this->task_model->getUsernameById($row->user_id);

          if($username)
          {
            return $username->id;
          }
          else
          {
            return "";
          }        
      }

      function _taskname_callback($value, $row)
      {
          $task = $this->task_model->getTasknameById($row->task_id);

          if($task == '')
          {
            return '';
          }
          else
          {
            return $task->name;
          }        
      }

      public function update_task($user_id, $task_id, $task_result_id)
      {
          //print_r($data['user']);exit;
          $data['user'] = $this->user_model->get_general_user($user_id);
          $data['bcTask'] = $this->task_model->getTaskDetails($task_id);
          $data['bcTaskResult'] = $this->task_model->getTaskResult($task_result_id);

          $data['customer'] = $this->customer_model->get_customer((isset($data['user']->user_link_id) ? $data['user']->user_link_id : ''));

          if($data['bcTask']->type == 'survey')
          {
              $name = $data['bcTask']->type_link_id;
              $data['survey'] = $this->task_model->getSurveyByName($name);
              $survey_id = $data['survey']->id;
              $data['q_and_a'] = $this->task_model->getSuveryResults($survey_id, $user_id);
              $data['responses'] = $this->task_model->responsesCount($survey_id, $user_id);
              $data['qAndAns'] = $this->task_model->getSurveyQuestionsAndAnswers($survey_id, $user_id);
              $data['answeredCount'] = count($data['responses']);
              $data['answered'] = $this->task_model->countQuestionAnswered($survey_id, $user_id);
          }

          if($data['bcTask']->type == 'photosnaps')
          {
              $data['photo'] = $this->task_model->getPhotosnapsById($data['bcTask']->type_link_id, $user_id);
          }

          if($data['bcTask']->type == 'price_survey')
          {

              $bc_task_id = $data['bcTask']->type_link_id;
              $data['survey'] = $this->task_model->getSurveyByName($bc_task_id);
              $survey_id = $data['survey']->id;
              $data['qs_and_a'] = $this->task_model->getPriceSuveryResults($survey_id, $user_id);
              $data['responses'] = $this->task_model->responsesCount($survey_id, $user_id);
              $data['qAndAns'] = $this->task_model->getSurveyQuestionsAndAnswers($survey_id, $user_id);
              $data['priceAnsweredCount'] = count($data['responses']);
              $data['answered'] = $this->task_model->countQuestionAnswered($survey_id, $user_id);
          }

          if($data['bcTask']->type == 'pos')
          {
              $data['posPhoto'] = $this->task_model->getPosPhotosnaps($data['bcTask']->type_link_id);
          }

          $data['user_id']=$user_id;
          $data['task_id']=$task_id;
          $data['task_result_id']=$task_result_id;
          $data['status']=$this->task_model->getBcTaskResult($user_id, $task_id, $task_result_id);
          $data['page_title'] = "Brand Connect Task Result";
          $this->show_view('update_task', $data);
      }

      function decline_task($user_id, $task_id, $task_result_id){
          $note = $this->input->post("reason");
          if(strlen($note) < 3){
            $note = false;
          }
        $this->task_model->updateTaskDeclined($task_id, $user_id, $note);
        header('Location: '.base_url().'task/update_task/'.$user_id.'/'.$task_id.'/'.$task_result_id.'');
      }

      public function reward_customer()
      {
          $type = 'brand_connect';
          $customer_id = $this->input->post("customer_id");
          $user_id = $this->input->post("user_id");
          $amount = $this->input->post("amount");
          $reference = $this->input->post("reference"); 
          $task_id = $this->input->post("task");
          $note = $this->input->post("note");
          if(strlen($note) < 3){
            $note = false;
          }
          $already_rewarded = $this->task_model->has_task_been_rewarded_before($task_id, $user_id);

          if(!$already_rewarded){

            $result = $this->financial_model->brand_connect_rewards($task_id, $customer_id);
            if($result){
              $this->task_model->updateTaskRewarded($task_id, $user_id, $result, $note);
            }else{
                die('An error occurred please contact support.');
            }

            redirect('/task/task_result');

          }else{

            $this->task_model->update_insert_task_result($task_id, $user_id, 8);
            redirect('/task/task_result');

          }
      }

    function data_table_script($dataset, $columns, $order_index=0, $search=false){
        $data_table = $this->javascript_library->data_table_script($dataset, $columns, $order_index, $search);
        return $data_table;
    }

    function quick_bc_task_photosnap(){
      $data['page_title']="BC Result";
      $this->show_view("quick_bc_result",$data);
    }
    function quick_bc_result(){
      $result['data'] = $this->task_model->get_quick_bc_task();
      echo json_encode($result);
    }
}
