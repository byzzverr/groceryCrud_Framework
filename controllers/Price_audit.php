<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Price_audit extends CI_Controller {

  public function __construct()
  {
      parent::__construct();
	  $this->load->library("Aauth");
      $this->load->helper('url');
      $this->load->model("survey_model");
	  $this->load->model("product_model");
	  $this->load->library('grocery_CRUD');
	  
        $this->load->model('event_model');
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
	$data["AllCategories"] = $this->product_model->getAllCategories();
	$data['page_title'] = 'Price Audit - Create';
	$this->show_view('auditCreate', $data);
  }
  
  public function maintain()
  {
	$crud = new grocery_CRUD();
        
        $crud->set_table('survey_list');
        $crud->set_subject('Price Audit');
		
		$crud->fields('title','subtitle','prefix','slug','enabled');
		$crud->field_type('prefix', 'invisible', "");
		$crud->field_type('slug', 'invisible', "");
		$crud->where('target_type','price_audit');
		
        $crud->change_field_type('createdate','invisible');
        $crud->callback_before_insert(array($this, 'set_createdate'));
     
		$crud->add_action('Questions', '', '/price_audit/questions','ui-icon-plus');
        $crud->add_action('Answers', '', '/price_audit/answers','ui-icon-plus');
        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'survey_list'));
		$crud->callback_before_insert(array($this,'setPrefix'));
        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'price_audit - Maintain';

        $this->crud_view($output);
  }
  
  function setPrefix($post_array)
  {
	  $slug=str_replace(" ","_",$post_array['title']);
	  $post_array['prefix'] = $slug;
	  $post_array['slug'] = $slug;
	  return $post_array;
  }
  
  function options(){

        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('options');
            $crud->set_subject('Options');

            $this->session->set_userdata('table', 'options');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));
			$crud->columns('option_text', 'option_type','created');
			$crud->fields('option_text');
             $crud->where('option_type',1);

            $crud->change_field_type('createdate','invisible');
            $crud->callback_before_insert(array($this, 'set_createdate'));

            $output = $crud->render();

            $output->page_title = 'price_audit Options';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }

    function questions($survey_id=1){
        try{
            $crud = new grocery_CRUD();
			$state = $crud->getState();
            
            $crud->set_table('survey_questions');
            $crud->set_subject('Question');
      			if($state == 'edit') //Go figure
      			{
      				$crud->set_relation_n_n("survey_options", "survey_question_option", "survey_options", "question_option.question_id", "option_id" , "option_text",null, array('option_type'=>1));
      			}
      			else
      			{
      				$crud->set_relation_n_n("survey_options", "survey_question_option", "survey_options", "question_id", "option_id" , "option_text",null, array('option_type'=>1));
      			}
      			$crud->fields('question_text','question_type','options','required','prefix','created');
      			$crud->field_type('prefix', 'hidden', $survey_id);
      			$crud->field_type('created', 'invisible', "");
      			
      			//These Keys are like this on purpose, dont change them unless you know what you are doing
      			$crud->field_type('question_type','dropdown',
      			array('0' => 'CheckBox', '2' => 'Free Text' , '3' => 'Multi Select'));
			
			       //$crud->add_action('Options', '', '/price_audit/options','ui-icon-plus');
            $this->session->set_userdata('table', 'survey_questions');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));
			      $crud->where('prefix',$survey_id);
            $crud->change_field_type('createdate','invisible');
            $crud->callback_before_insert(array($this, 'set_createdate'));
            $crud->callback_before_update(array($this, '_callback_implode_options'));
            $crud->callback_before_insert(array($this, '_callback_implode_options'));

            $output = $crud->render();

            $output->page_title = 'price_audit Questions';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }
	
	  function answers($survey_id){

        try{
            $crud = new grocery_CRUD();
			
			$crud->unset_delete();
			$crud->unset_edit();
			$crud->unset_add();
			
            
            $crud->set_table('response_answers');
            $crud->set_subject('Answers');

            $this->session->set_userdata('table', 'answers');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));

			$crud->set_relation('question_id','questions','question_text');
			$crud->set_relation('option_id','options','option_text');
			$crud->set_relation('response_id','customers','company_name');

			
			$crud->columns('response_id','question_id', 'option_id','text','created');
			$crud->fields('question_id', 'option_id','created');
            $crud->where('prefix',$survey_id);

            $crud->change_field_type('createdate','invisible');
            $crud->callback_before_insert(array($this, 'set_createdate'));

            $output = $crud->render();

            $output->page_title = 'price_audit Answers';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }
  
  
  public function save()
  { 
	$rand = substr(uniqid('', true), -5);
	$surveyName = "Price_Audit_$rand";
	$productName = array();
	$productPic = array();
	$surveyType=array();
	  if (isset($_POST['submit_pa'])) 
	  {
		  $category_id = $_POST['category'];
		  $numItems = $_POST['numItems'];
		  
		$products=$this->product_model->getRandomProductsByCategory($category_id, $numItems);
		$count=0;
		foreach($products as $product)
		{	
			if (!in_array($product['name'], $productName))
			{
				array_push($productName, $product['name']);		
				array_push($productPic, $product['picture']);
				array_push($surveyType,5);
				$count++;
			}
		}
		$this->survey_model->saveSurveyList($surveyName);
		$this->survey_model->saveSurveyQuestion($productName, $surveyType,$productPic, $surveyName);
		$data['records']=$count;
		$data['surveyName']=$surveyName;
		
		$data["AllCategories"] = $this->product_model->getAllCategories();
		$data['page_title'] = 'Price Audit - Create';
		$this->show_view('auditCreate', $data);
	  }
	}
	
	public function dashboard() {
    $data["active_surveys"] = $this->survey_model->getActiveSurveys();
    $data["survey_responses"] = $this->survey_model->getSurveyResponses();
	$data['page_title'] = 'price_audit - Dashboard';
	$this->show_view('survey_dashboard', $data);
  }

  public function response($surveySlug = "", $responseId = 0) {
    $data["user"] = array("email" => $this->user->email);

    if(!empty($surveySlug) && $responseId > 0) {

      $data["responses"] = $this->survey_model->getResponseData($surveySlug, $responseId);
      $data["valid_response"] = ($data["responses"] !== null);
    }
    else {
      $data["valid_response"] = false;
    }
	$data['page_title'] = 'price_audit - Responses';
	$this->show_view('survey_results', $data);
  }
  
  public function questions_unknown_method_to_remove($survey = "")
  {

    $surveyPrefix = "";
    $surveyData = $this->survey_model->getSurveyPrefix($survey);
    $data["valid_survey"] = true;
    $data["show_questions"] = true;
    $data["survey_errors"] = false;

    // check if the provided slug was valid
    if($surveyData != null) {

      // populate survery information
      $surveyPrefix = $surveyData->prefix;
      $data["survey_title"] = $surveyData->title;
      $data["survey_subtitle"] = $surveyData->subtitle;
    }
    else {
      $data["valid_survey"] = false; // display error
    }

    // check if the survey was submitted
    if($_SERVER['REQUEST_METHOD'] == 'POST' && $data["valid_survey"]) {

      $result = $this->survey_model->validateSubmission($surveyPrefix);
      if(array_key_exists("errors", $result)) {
        $data["errors"] = $result["errors"];
        $data["survey_errors"] = true;
      }
      else {
        $data["show_questions"] = false;
      }
    }

    // check if the user specified a valid survey
    if(!empty($surveyPrefix)) {

      $data["questions"] = $this->survey_model->getSurveyData($surveyPrefix);
      ($data["questions"] === null) ? $data["valid_survey"] = false: "";
    }
	$data['page_title']='price_audit - Questions';
	$this->show_view('survey', $data);
  }
  
  public function surveyList()
  {
	  $data["active_surveys"] = $this->survey_model->getActiveSurveys();
	  $data['page_title']='price_audit - List';
	  $this->show_view('survey_all', $data);
  }
}