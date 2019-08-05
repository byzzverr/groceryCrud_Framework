<?php

class Survey_Model extends CI_Model {

  function __construct() {
    parent::__construct();
  }

  /*
  get the survey information provided 
  with the survey slug from the url
  */
  function getSurveyPrefix($slug) {

    // get the survey prefix from the url slug
    $this->db->select("*")->from("survey_list")->where("slug", $slug)->where("enabled", 1);
    $query = $this->db->get();

    if($query->num_rows() > 0)
      return $query->row();
    else
      return null;
  }
  
    /*
  get all survey's available
  */
  function getAllSurveys() {

    $this->db->select("*")->from("survey_list")->where("type", "Normal")->order_by("title");
    return $this->db->get()->result();
  }

  /*
  get all active survey's available
  */
  function getActiveSurveys() {

    $this->db->select("*")->from("survey_list")->where("enabled", 1)->where("type", "Normal");
    return $this->db->get()->result();
  }


  // Get Active Price Surveys
  function getActivePriceSurveys() {

    $this->db->select("*")->from("survey_list")->where("enabled", 1)->where("type", "Price");
    return $this->db->get()->result();
  }
  
  function getActiveSurveysById($survey_id) {

    $this->db->select("title, subtitle, prefix, id")->from("survey_list")->where(array("enabled"=> 1,"id"=> $survey_id));
    $query= $this->db->get();
    return $query->row();
  }

  // Get Active Price Survey By ID

  function getActivePriceSurveysById($survey_id) {

    $this->db->select("title, subtitle, prefix, id")->from("survey_list")->where(array("enabled"=> 1,"id"=> $survey_id,"type"=> "Price"));
    $query= $this->db->get();
    return $query->row();
  }

  function getActiveSurveysByPrefix($prefix) {

    $this->db->select("title, slug")->from("survey_list")->where(array("enabled"=> 1,"prefix"=> $prefix));
    $query= $this->db->get();
    return $query->row();
  }
  
  function getNextUnansweredQuestion($survey_id, $user_id)
  {

      $data = array();

      $surveyPrefix='test';
     if($this->db->table_exists("survey_questions") && $this->db->table_exists("survey_options")) {

      //get all the questions that have not been answered yet.
      $query_c = $this->db->query("SELECT q.id from survey_questions q 
        LEFT JOIN survey_response_answers a on q.id = a.question_id AND a.survey_id = ? AND a.user_id = ? 
        WHERE a.id is null AND prefix = ? order by q.id", array($survey_id, $user_id, $survey_id));

      $data['Questions_left'] = $query_c->num_rows();

      $query = $this->db->query("SELECT q.* from survey_questions q 
        LEFT JOIN survey_response_answers a on q.id = a.question_id AND a.survey_id = ? AND a.user_id = ? 
        WHERE a.id is null AND prefix = ? order by q.id asc limit 1", array($survey_id, $user_id, $survey_id));

      // determine if any questions have options
      foreach($query->result() as $question) {

          // get all options for this question
          $this->db->select("id as option_id, option_text as option_value")->from("survey_options")
      ->join('survey_question_option','survey_options.id = survey_question_option.option_id')
      ->where("survey_question_option.question_id", $question->id);
          $option_query = $this->db->get();

          // add all options to this questions contents
          $question->options = array();
          foreach($option_query->result() as $option) {
            array_push($question->options, $option);
          }

          $question->option_count = count($question->options);
      } // end foreach - all questions

    $data['Question'] = $query->row();

    if($data['Question']){
      if(strlen($data['Question']->image) < 5){
        $data['Question']->image = null;
      }
    }

    return $data;
    } // end if - valid table

    return null;
  }

  function mark_survey_as_complete($survey_id, $user_id){
    $this->load->model('task_model');
    $task = $this->task_model->get_task_from_type_link('survey', $survey_id);
    $this->task_model->insert_task_result($task['id'], $user_id, 3); // Pending approval
  }
  
  function remainingQuestions($survey_id, $user_id)
  {
      $surveyPrefix='test';
     if($this->db->table_exists("survey_questions") && $this->db->table_exists("survey_options")) {

      $query = $this->db->query("SELECT q.* from survey_questions q 
        LEFT JOIN survey_response_answers a on q.id = a.question_id AND a.survey_id = ? AND a.user_id = ? 
        WHERE a.id is null AND q.prefix = ? order by q.id asc", array($survey_id, $user_id, $survey_id));

      return $query->num_rows();
    } // end if - valid table

    return 0;
  }  

  function getQuestionById($surveyPrefix, $row=0)
  {
	  if (!is_numeric($row)){
		  $row=0;
    } 

	   if($this->db->table_exists("survey_questions") && $this->db->table_exists("survey_options")) {

      $this->db->select("survey_questions.*")->from("survey_questions")->join('survey_response_answers','survey_questions.id = survey_response_answers.question_id','LEFT')->where("prefix",$surveyPrefix)->where("survey_questions.id!=",null)->where('survey_response_answers.id',null)->order_by("survey_questions.id", "asc")->limit($row,1);
      $question_query = $this->db->get();
      // determine if any questions have options
      foreach($question_query->result() as $question) {
        if($question->question_type == 0 || $question->question_type == 3  || $question->question_type == 5) {

          // get all options for this question
          $this->db->select("id as answer_id, option_text as answer_value")->from("survey_options")
		  ->join('survey_question_option','survey_options.id = survey_question_option.option_id')
		  ->where("survey_question_option.question_id", $question->id);
          $option_query = $this->db->get();

          // add all options to this questions contents
          $question->options = array();
          foreach($option_query->result() as $option) {
            array_push($question->options, $option);
          }
        }
		
      } // end foreach - all questions
		$data['Question'] = $question_query->row();
	  $this->db->select("*")->from("survey_response_answers")->where("question_id", $data['Question']->id);
        $data['Answers'] = $responses = $this->db->get()->row();
		return $data;
    } // end if - valid table

    return null;
  }
  
  function getQuestionDetailsId($id)
  {
	  $this->db->select("*")->from("survey_questions")->where("id",$id);
	  $query = $this->db->get();

    if($query->num_rows() > 0)
      return $query->row();
    else
      return null;
  }

  /*
  merge two arrays by the 'created' property in contained objects
  */
  function mergeByCreateProperty($arrayOne, $arrayTwo) {

    if(empty($arrayOne)) return $arrayTwo;
    if(empty($arrayTwo)) return $arrayOne;

    $result = array();
    $length = sizeof($arrayOne) + sizeof($arrayTwo);
    $posOne = 0;
    $posTwo = 0;
    for($i = 0; $i < $length; $i++) {

      if($posOne < sizeof($arrayOne) && $posTwo < sizeof($arrayTwo) &&
        $arrayOne[$posOne]->created > $arrayTwo[$posTwo]->created) {
        array_push($result, $arrayOne[$posOne++]);
      }
      else if($posTwo < sizeof($arrayTwo)) {
        array_push($result, $arrayTwo[$posTwo++]);
      }
      else {
        array_push($result, $arrayOne[$posOne++]);
      }
    }
    return $result;
  }

  /*
  get all survey responses
  */
  function getSurveyResponses() {

    $this->db->select("*")->from("survey_list");
    $surveys = $this->db->get()->result();

    $surveyResponses = array();
    foreach($surveys as $survey) {
      $this->db->select("*")->from("survey_response_answers")->where("prefix",$survey->prefix)->order_by("created", "desc");
      $responses = $this->db->get()->result();

      // add the survey data to each response
      foreach($responses as $response) {
        $response->survey_title = $survey->title;
        $response->survey_slug = $survey->slug;
        $this->db->select("email")->from("aauth_users")->where("id", $response->user_id);
        $response->email = $this->db->get()->row()->email;
      }
      $surveyResponses = $this->mergeByCreateProperty($surveyResponses, $responses);
    }

    return $surveyResponses;
  }
  
  function getResponses($userId)
  {
    $this->db->select("*")->from("survey_response_answers")->where("user_id", $userId);
      $responseData = $this->db->get();
    if($responseData->num_rows() > 0)
      return $responseData->row();
    else
      return null;
  }

  function getQuestionOptionText($option_id)
  {
	  $this->db->select("option_text")->from("survey_options")->where("id", $option_id);
      $responseData = $this->db->get();
    if($responseData->num_rows() > 0){
      $return = $responseData->row();
      return $return->option_text;
    }else{
      return null;
      }
  }

  // NO Database Table must be discarded???

  function getResponsesPerSurvey($userId, $survey_id)
  {
	  $this->db->select("*")->from("survey_response_answers")->where("user_id", $userId)->where("survey_id",$survey_id);
      $responseData = $this->db->get();
	  if($responseData->num_rows() > 0){
      $option =  $responseData->row();
    return $option->text;
    }
    else{
      return null;
    }
  }
  /*
  get all response information
  param - surveySlug - the slug for a valid survey
  param - responseId - the id for the specific response
  */
  function getResponseData($surveySlug, $responseId) {

  $surveySlug = str_replace(" ","_",$surveySlug);
    // check if the survey slug is valid
    $this->db->select("*")->from("survey_list")->where("slug", $surveySlug);
    $query = $this->db->get();
    if($query->num_rows() > 0) {

      // check if the id is valid
      $prefix = $query->row()->prefix;
      $this->db->select("*")->from("survey_response_answers")->where("id", $responseId)->where("prefix",$surveySlug);
      $responseData = $this->db->get();
      if($responseData->num_rows() > 0 ) {

        $responseInfo = array();
        $this->db->select("email")->from("aauth_users")->where("id", $responseData->row()->user_id);
        $responseInfo["email"] = $this->db->get()->row()->email;
        $result = array();

        // get all questions
        $this->db->select("*")->from("survey_questions")->where("prefix",$surveySlug);
        $questions = $this->db->get()->result();

        foreach($questions as $question) {
          $result[$question->id] = array("survey_question" => $question->question_text);
        }

        // get all response information
        $this->db->select("*")->from("survey_response_answers")->where("response_id", $responseId);
        $responses = $this->db->get()->result();

        foreach($responses as $response) {

          if(!isset($result[$response->question_id]["response"]) || !is_array($result[$response->question_id]["response"])) 
		  { 
			  $result[$response->question_id]["responseId"] = array();
			  $result[$response->question_id]["response"] = array();
			  
				$result[$response->question_id]["answeredDate"] = array();
		  }
          if($response->option_id == 0) {
			  array_push($result[$response->question_id]["responseId"], $response->id);
            array_push($result[$response->question_id]["response"], $response->text);
			array_push($result[$response->question_id]["answeredDate"], $response->created);
          }
          else {
            $this->db->select("*")->from("survey_options")->where("id", $response->option_id);
			array_push($result[$response->question_id]["responseId"], $response->id);
            array_push($result[$response->question_id]["response"], $this->db->get()->row()->option_text);
			array_push($result[$response->question_id]["answeredDate"], $response->created);
          }
        }

        $responseInfo["responses"] = $result;
        return $responseInfo;
      }
    }
    return null;
  }

  /*
  get all survey data for the provided survey prefix
  param - surveyPrefix of table - example 's1'
  return - null if invalid survey prefix or all question 
  data for provided survey prefix
  */
  function getSurveyQuestions($surveyPrefix) {

    if($this->db->table_exists("survey_questions") &&
      $this->db->table_exists("survey_options")) {

      $this->db->select("*")->from("survey_questions")->where("prefix",$surveyPrefix)->order_by("question_type", "asc");
      $question_query = $this->db->get();

      // determine if any questions have options
      foreach($question_query->result() as $question) {

        // get all options for this question
        //$this->db->select("*")->from("survey_options")->join('survey_question_option','survey_options.id = survey_question_option.option_id','INNER')->where(array("survey_options.id", $question->id, "survey_question_option.question_id", $question->id));
        $option_query = $this->db->query("SELECT so.id, so.option_text, so.option_type FROM survey_options so, survey_question_option sqo WHERE so.id = sqo.option_id AND sqo.question_id = ?", array($question->id));

        // add all options to this questions contents
        $question->options = array();
        foreach($option_query->result() as $option) {
          array_push($question->options, $option);
        }

      } // end foreach - all questions

      return $question_query->result();
    } // end if - valid table

    return null;
  } // end function - get survey data

  /*
  validate the survery submission
  param - surveyPrefix of table - example 's1'
  */
  function validateSubmission($surveyPrefix) {

    // get the survey questions/answers and ensure the survey is valid
    $surveyData = $this->getSurveyQuestions($surveyPrefix);
    $errors = array();
    if($surveyData != null) {

      $responses = array();
      if(isset($_POST["email_field"]) && !empty($_POST["email_field"]) && filter_var($_POST["email_field"], FILTER_VALIDATE_EMAIL)) {
        $responses["email"] = $_POST["email_field"];
      }
      else {
        array_push($errors, "'Email Address' is required and must be valid.");
      }

      foreach($surveyData as $question) {

        if(isset($_POST["question_" . $question->id]) && !empty($_POST["question_" . $question->id])) {

          // question has a response, set the response attribute & add object to final responses
          // put all responses in an array (incase of multiple responses for a single question)
          if(!is_array($_POST["question_" . $question->id]))
            $question->response = array($_POST["question_" . $question->id]);
          else
            $question->response = $_POST["question_" . $question->id];

          $responses[($question->id)] = $question;
        }
        else {

          // check if the question is required
          if($question->required == 1) {

            // error - question is required but is blank/does not exist
            array_push($errors, "'" . $question->question_text . "' is required.");
          }
          elseif(isset($_POST["question_" . $question->id]) ) {

            // question has no response, but is not required
            // put all responses in an array (incase of multiple responses for a single question)
            if(!is_array($_POST["question_" . $question->id]))
              $question->response = array($_POST["question_" . $question->id]);
            else
              $question->response = $_POST["question_" . $question->id];
            $responses[($question->id)] = $question;
          }
        }
      }

      if(sizeof($errors) > 0) 
        return array("errors" => $errors);
      else
        return array("success" => $this->submitData($surveyPrefix, $responses));
    }

    return null;
  } // end function - validate submission

function saveSurveyAnswer($response) {

  if(!$this->is_answer_duplicate($response)){
    $this->db->insert("survey_response_answers", $response);
    return true;
  }else{
    return false;
  }
}

function is_answer_duplicate($response) {
  $query = $this->db->query("SELECT id from survey_response_answers WHERE survey_id = ? AND user_id = ? AND question_id = ?", array($response->survey_id, $response->user_id, $response->question_id));
  if($query->num_rows() >= 1){
    return true;
  }
  return false;
}



  /*
  submit the data to the database
  param - surveyPrefix of table - example 's1'
  param - responses of questions - question object including responses
  */
    function submitDataFromAPI($surveyPrefix, $response) {
      $userId = $response->userId;

    // create a response & retrieve the id
    $responseId = 0;
	
	$this->db->select("*")->from("survey_response_answers")->where(array("user_id"=>$userId, "prefix"=>$surveyPrefix));
      $respQuery = $this->db->get();
	  if($respQuery->num_rows() > 0) {
      // get user's id
      $responseId = $respQuery->row()->id;
    }
	else
	{
		$this->db->insert("survey_response_answers", array("user_id" => $userId, "prefix"=>$surveyPrefix));
		$responseId = $this->db->insert_id();
	}
	
	//hack to get the username into this table
	$responseId = $response->userId;
	
	
    // prepare insert responses
    $insert_data = array();

      if(isset($response->response) && $response->response != null) {
        $single_response = $response->response;
          // generate response data
          $response_data = array();
          $response_data["response_id"] = $responseId;
          $response_data["question_id"] = $response->id;

          // check if the question was multiple choice
          if($response->question_type == 0 || $response->question_type == 3) {

            // associate proper option id & ignore text field
            $response_data["option_id"] = $single_response;
            $response_data["text"] = null;
          }
          // check if the question was simple input or text input
          elseif($response->question_type == 1 || $response->question_type == 2 || $response->question_type == 5 ) {

            // set proper text & ignore option id
            $response_data["option_id"] = 0;
            $response_data["text"] = $single_response;
          }

          array_push($insert_data, $response_data);
        
      }
      else {

        // generate response data
        $response_data = array();
        $response_data["response_id"] = $responseId;
        $response_data["question_id"] = $response->id;
        $response_data["option_id"] = 0;
        $response_data["text"] = null;
        array_push($insert_data, $response_data);
      }
    

    $this->db->insert_batch("survey_response_answers", $insert_data);

    return null;
  }
  
  function submitData($surveyPrefix, $responses) {

    // check if user exists
    $this->db->select("*")->from("aauth_users")->where("email", $responses["email"]);
    $emailQuery = $this->db->get();
    $userId = 0;
    if($emailQuery->num_rows() > 0) {

      // get user's id
      $userId = $emailQuery->row()->id;
    }
    else 
    {

      // add user
      $this->db->insert("aauth_users", array("email" => $responses["email"]));

      // get user's id
      $this->db->select("*")->from("survey_users")->where("email", $responses["email"]);
      $emailQuery = $this->db->get();
      $userId = $emailQuery->row()->id;
    }

    // create a response & retrieve the id
    $responseId = 0;
	
	$this->db->select("*")->from("responses")->where(array("user_id"=>$userId, "prefix"=>$surveyPrefix));
      $respQuery = $this->db->get();
	  if($respQuery->num_rows() > 0) {
      // get user's id
      $responseId = $respQuery->row()->id;
    }
	else
	{
		$this->db->insert("responses", array("user_id" => $userId, "prefix"=>$surveyPrefix));
		$responseId = $this->db->insert_id();
	}
    // prepare insert responses
    $insert_data = array();
    foreach ($responses as $response) {
  
      if($response == $responses["email"]) continue;

      if(isset($response->response) && $response->response != null) {
        foreach($response->response as $single_response) {
          // generate response data
          $response_data = array();
          $response_data["response_id"] = $responseId;
          $response_data["question_id"] = $response->id;

          // check if the question was multiple choice
          if($response->question_type == 0 || $response->question_type == 3) {

            // associate proper option id & ignore text field
            $response_data["option_id"] = $single_response;
            $response_data["text"] = null;
          }
          // check if the question was simple input or text input
          elseif($response->question_type == 1 || $response->question_type == 2  || $response->question_type == 5) {

            // set proper text & ignore option id
            $response_data["option_id"] = 0;
            $response_data["text"] = $single_response;
          }

          array_push($insert_data, $response_data);
        }
      }
      else {

        // generate response data
        $response_data = array();
        $response_data["response_id"] = $responseId;
        $response_data["question_id"] = $response->id;
        $response_data["option_id"] = 0;
        $response_data["text"] = null;
        array_push($insert_data, $response_data);
      }
    }

    $this->db->insert_batch("response_answers", $insert_data);

    return null;
  }
  
  public function saveSurveyQuestion($questions, $answers, $options,$prefix)
  {
	//Nasty hack, its late ok!
	$count=0;
	foreach ( $questions as $question) 
	{
		$this->db->insert("survey_questions", array("question_text" =>$questions[$count] , "question_type"=>$answers[$count],"required"=>1,"prefix"=>$prefix));
		$questionId = $this->db->insert_id();
		if (sizeof($options)>0)
		{
			$questionOption = explode(",",$options[$count]);
			foreach ($questionOption as $option)
			{
				$this->db->insert("survey_options", array( "option_text"=>$option));
			}
		}
			$count+=1;
		
	}
  }
  
  public function saveSurveyList($prefix)
	{
	 $slug=str_replace(" ","_",$prefix);
	 $target = 'survey';
	 if (strpos($slug,'_Audit')>0)
		 $target='price_audit';
		$this->db->insert("survey_list", array("title" =>$prefix , "target_type"=>$target, "prefix"=>$slug,"slug"=>$slug,"enabled"=>1));
	}

  // ADDITIONS
  //__________

  // (1.) Check if a question exists

  public function getQuestionAsked($question_id)
  {
    $response = $this->db->where("id", $question_id)->get("questions");
    $v = $response->row();
    if($v > 0)
    {
      return $question_id;
    }
    else
    {
      return null;
    }

  }

  // (2.) Check if there is an answer to a question

  public function getAnswerToQuestion($question_id) 
  {

    $answer = $this->db->select("answer")
      ->from("answers")
      ->where("question_id", $question_id);

      if ($answer > 0)
      {
        return $question_id;
      }
      else
      {
        return null;
      }
  }

  // GENERATE SURVEY REVEIWS DATA FOR SURVEY ANALYSIS

  // (1) Get Survey By ID whether active or not

  public function getSurveyById($survey_id)
  {

    return $this->db->where("id", $survey_id)->get("survey_list");

  }

  // (2) Generating all responses captured by survey_id will generate user count

  public function getAllResponsesBySurveyId($survey_id) 
  {
    $sql = "SELECT * FROM survey_response_answers WHERE survey_id = ? ";
    return $this->db->query($sql, array($survey_id))->row();
  }

  // Getting the option value to pass in comtroller
  public function getQuestion($survey_id) 
  {
    $sql = "SELECT question_id FROM survey_response_answers WHERE survey_id = ?";
    return $this->db->query($sql, array($survey_id))->row();
  } 


  // (3) Generating Questions to a Survey using the prefix

  public function getAllQuestionsByPrefix($prefix)
  {
    $sql = "SELECT * FROM survey_questions WHERE prefix = ?";
    $result = $this->db->query($sql, array($prefix))->result();
    return $result;
  }

  // (4) Retreiving all the people who participated in a survey

  public function getAllSurveyUsersBySurveyId($survey_id)
  {
    $sql = "SELECT distinct(user_id) FROM survey_response_answers WHERE survey_id = ? ";
    $result = $this->db->query($sql, array($survey_id))->result();
    return $result;
  }

  // (5) Retrieving all options available to a question

  public function getAllOptionsByQuestionId($question_id, $survey_id)
  {
    if ($question_id = null) { $question_id = 0; }

    $query = $this->db->where("question_id", $question_id)
      ->get("survey_question_option");

    foreach ($query->result() as $opt)
    {
      $sqlNew = $this->db->where("id", $opt->id)
        ->get("survey_option")->result();
    }

    return $query;

  } 
  public function getAllUsers()
  {
    $result = $this->db->get("aauth_users")->result();
    return $result;
  }

  // get questions with answer count
  public function getQuestionsWithAnswerCount($survey_id)
  {
    $result = $this->db->select("q.id, q.question_text,q.created, a.survey_id")
        ->from("survey_response_answers a")
        ->join("survey_questions as q", "a.question_id = q.id")
        ->join("bc_tasks as t", "t.type_link_id = a.survey_id")
        ->where("a.survey_id", $survey_id)
        ->group_by("a.question_id")
        ->get();

    return $result->result(); 

  }


  // Get Servey Results for users

  // (1) Get All Survey Questions

  public function getAllSurveyQuestionsByPrefix($prefix)
  {
    $query = $this->db->where("prefix", $prefix)->get("survey_questions")->result();
    return $query;
  }

  // (2) Get All User Responses to answers

  public function getAllUserAnswers($survey_id, $user_id)
  {
    $result = $this->db->where("survey_id", $survey_id)
      ->where("user_id", $user_id)
      ->get("survey_response_answers")->result();
    return $result;
  }

  // (3) Get Questions By Prefix
  function getQuestionAnswers($survey_id, $question_id)
  {
      $query = $this->db->query("SELECT r.question_id, 
                              r.survey_id,
                              r.id,
                              r.text, 
                              a.name as user,
                              r.createdate,
                              a.name
                              FROM survey_response_answers as r 
                              JOIN aauth_users as a ON a.id=r.user_id
                              JOIN bc_tasks as t ON t.type_link_id=r.survey_id
                              where r.survey_id='$survey_id' 
                              and r.question_id='$question_id'");

      return $query->result();
  } 
function getSurveyResponseDetail($survey_id, $question_id,$response)
  {
      $query = $this->db->query("SELECT r.question_id, 
                              r.id,
                              r.text, 
                              r.createdate,
                              a.name as Participant
                              FROM survey_response_answers as r 
                              JOIN aauth_users as a ON a.id=r.user_id
                              where r.survey_id='$survey_id' 
                              and r.question_id='$question_id' and r.text='$response'
                              and r.text !=''
                              ");

      return $query->result_array();
  } 

  function get_question_daily_answers($survey_id, $question_id){
     $query = $this->db->query("SELECT count(r.user_id) as count_answer, 
                              r.text, 
                              SUBSTR(r.createdate,1,10) as createdate                         
                              FROM survey_questions as q
                              JOIN survey_response_answers as r ON q.id=r.question_id
                              JOIN aauth_users as a ON a.id=r.user_id
                              JOIN bc_tasks as t ON t.type_link_id=r.survey_id
                              where r.survey_id='$survey_id' 
                              and r.question_id='$question_id'
                              GROUP BY SUBSTR(r.createdate,1,10) DESC LIMIT 20");

      return $query->result_array();

  }

  function get_question_answer_stats($survey_id, $question_id){
     $question = $this->get_survey_question($question_id);


/*array('0' => 'CheckBox', '2' => 'Free Text' , '3' => 'Multi Select', '1' => 'Single Select', '4' => 'Rand Value', '5' => 'Price Check'));*/

  if(in_array($question['question_type'], array(2,4,5))){
       $query = $this->db->query("SELECT count(r.user_id) as count_answer, 
                                r.text
                                FROM survey_response_answers as r
                                JOIN survey_questions as q ON q.id=r.question_id
                                where r.survey_id='$survey_id' 
                                and r.question_id='$question_id'
                                GROUP BY r.text DESC");
  }elseif($question['question_type'] == 3){

$query = $this->db->query("SELECT count(s.id) as count_answer, 
                                s.value as 'text'
                                FROM survey_response_answers as r
                                JOIN survey_multi_select_split as s ON r.id=s.response_id
                                where r.survey_id='$survey_id' 
                                and r.question_id='$question_id'
                                GROUP BY s.value DESC");

  }else{

       $query = $this->db->query("SELECT count(r.user_id) as count_answer, 
                                o.option_text as 'text'
                                FROM survey_response_answers as r
                                JOIN survey_options as o ON r.option_id=o.id
                                where r.survey_id='$survey_id' 
                                and r.question_id='$question_id'
                                GROUP BY r.option_id DESC");

  }

      return $query->result_array();

  }


  function get_survey_question($question_id){
    $query = $this->db->query("SELECT * FROM survey_questions WHERE id = $question_id");
    return $query->row_array();
  }

    
function getNumberOfUsers()
  {

      $query = $this->db->query("SELECT * FROM aauth_users WHERE 1");

      return $query->num_rows();
  }
    
  function get_survey_results($survey_id,$user_id){
    $this->db->select('question_text as QUESTION, text as ANSWER, username as USERNAME, createdate as DATE' );
    $this->db->from ('survey_response_answers' );
    $this->db->join ('survey_questions', 'survey_response_answers.question_id = survey_questions.id' , 'left' );
    $this->db->join ('aauth_users', 'survey_response_answers.user_id = aauth_users.id' , 'left' );
    $this->db->where('user_id',$user_id);
    $this->db->where('survey_id',$survey_id);
    $query = $this->db->get();
    return $query->result();
  }
function get_survey_list($status_id, $date_from='', $date_to=''){
      if(!empty($date_from) && !empty($date_to)){
        $where_date =" and r.createdate>='$date_from' and r.createdate<='$date_to'";
      }else{
        $where_date='';
      }
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
                              s.title as  survey,
                              `r`.`id`, 
                              `r`.`id` as task_result_id, 
                              t.task_id as task_id,
                              `s`.`id` as survey_id, 
                              st.name as status,
                              r.createdate,
                              `r`.`user_id`
                              FROM `bc_tasks` as `t`  
                              JOIN `bc_task_results` as `r` ON `r`.`task_id` = `t`.`task_id`  
                              JOIN `survey_list` as `s` ON `s`.`id` = `t`.`type_link_id`
                              JOIN `aauth_users` as `a` ON `r`.`user_id` = `a`.`id`
                              JOIN `gbl_statuses` as `st` ON `st`.`id` = `r`.`status`
                              $where_status $where_date
                              GROUP BY `r`.`id`
                              ");
      
    return  $query->result();
   
  }

function get_survey_stats($status_id, $stats_type, $survey_id='', $date_from='', $date_to=''){
      
   if(!empty($status_id)){
      $where_status=" WHERE `r`.`status`='$status_id'";
    }else{
      $where_status="";
    }

    if($stats_type=="user_stats"){
      $group_by="  `r`.`user_id`";
      $count=" DISTINCT `r`.`user_id`";
    }

    if($stats_type=="survey_stats"){
      $group_by=" `t`.`type_link_id`";
      $count=" `r`.`task_id`";
    }

    if(!empty($survey_id)){
      $where_survey_id =" and `t`.`type_link_id` ='$survey_id'";
    }else{
      $where_survey_id='';
    }

    if(!empty($date_from) && !empty($date_to)){
      $where_date=" and r.createdate>='$date_from' and r.createdate<='$date_to'";
    }else{
      $where_date='';
    }

    $query = $this->db->query("SELECT a.id, 
                              count($count) as number_of_resp, 
                              s.title,
                              r.createdate,
                              a.name as user
                              FROM `bc_tasks` as `t`  
                              JOIN `bc_task_results` as `r` ON `r`.`task_id` = `t`.`task_id`  
                              JOIN `survey_list` as `s` ON `s`.`id` = `t`.`type_link_id`
                              JOIN `aauth_users` as `a` ON `r`.`user_id` = `a`.`id`
                              JOIN `gbl_statuses` as `st` ON `st`.`id` = `r`.`status`
                              $where_status $where_survey_id $where_date
                              GROUP BY $group_by LIMIT 20");
    if(!empty($where_survey_id)){
       return  $query->row_array();
    }else{
       return  $query->result();
    }
    
}

function get_gbl_statuses_by_id($tatus){
    if(!empty($tatus)){
      $where_status = " WHERE id IN($tatus)";
    }else{
      $where_status ='';
    }
    
    $query = $this->db->query("SELECT * FROM gbl_statuses $where_status");

    return  $query->result(); 
}

function getGblStatusById($tatus){
    if(!empty($tatus)){
      $where_status = " WHERE id IN($tatus)";
    }else{
      $where_status ='';
    }
    
    $query = $this->db->query("SELECT * FROM gbl_statuses $where_status");

    return  $query->row_array(); 
}
function get_gbl_statuses(){
   
    $query = $this->db->query("SELECT * FROM gbl_statuses");

    return  $query->result(); 
}
    
function get_survey_csv(){
   $query= "SELECT count(`su`.`prefix`) as number_of_questions, `s`.`title` FROM `survey_list` as `s` JOIN `survey_questions` as`su` ON `su`.`prefix` = `s`.`id` GROUP BY `su`.`prefix`";
    return $query;
   
  }

function get_survey_questions($survey_id,$user_id){

    $query = $this->db->query("SELECT r.question_id, q.*, r.*,a.*,r.createdate
                              FROM survey_questions as q 
                              JOIN survey_response_answers as r ON r.question_id=q.id
                              JOIN aauth_users as a ON a.id=r.user_id
                              WHERE q.prefix='$survey_id' and a.id='$user_id'");

    return  $query->result_array();

   
}  

function get_survey_questions_csv($survey_id){

$query="SELECT sq.question_text as question_text_,     
        count(sa.question_id) as count_, sq.id as question_id
        FROM survey_questions as sq 
        JOIN survey_response_answers as sa 
        ON sq.id=sa.question_id
        WHERE sa.survey_id = '$survey_id'
        GROUP BY question_id";
    
return $query;
}  
    
function get_supplier_survey_questions($survey_id,$user_link_id){

    $this->db->select('survey_questions.image as photo, survey_questions.question_text as question_text_, count(survey_response_answers.question_id) as count_, survey_questions.id as question_id');
    $this->db->from ('survey_questions');
    $this->db->join ('survey_response_answers', 'survey_questions.id=survey_response_answers.question_id', 'left' ); 
   
    $this->db->where("prefix",$survey_id);
 
    $this->db->group_by("question_id");
    return $this->db->get ();
   
  }    
function get_survey_by_survey_id($survey_id){

    $this->db->select("*");
    $this->db->from ('survey_list'); 
    $this->db->where("id",$survey_id);
    return $this->db->get ();
   
  } 
function get_survey_participants($survey_id,$question_id){

    $this->db->select("count(user_id) as users");
    $this->db->from ('survey_response_answers'); 
    $this->db->where("question_id",$question_id);
    return $this->db->get ();
   
  } 
function get_survey_participants_stats($survey_id,$question_id){

    $this->db->select("count(user_id) as participants_, text");
    $this->db->from ('survey_response_answers'); 
    $this->db->where("question_id",$question_id);
    $this->db->group_by("text");
    return $this->db->get ();
   
  }  
function get_survey_question_by_id($question_id){

    $this->db->select("*");
    $this->db->from ('survey_questions'); 
    $this->db->where("id",$question_id);
    return $this->db->get ();
   
  }  
 function get_survey_answers($survey_id,$question_id){

    $this->db->select('*');
    $this->db->from ('survey_response_answers');
    $this->db->join ('survey_questions', 'survey_questions.id=survey_response_answers.question_id', 'left' ); 
    $this->db->join ('aauth_users', 'survey_response_answers.user_id = aauth_users.id' , 'left' );
    $this->db->join ('customers', 'aauth_users.user_link_id = customers.id' , 'left' );
    $this->db->where("question_id",$question_id);
    //$this->db->group_by("text");
    return $this->db->get ();
   
  }    
    
 function get_survey_answers_csv($survey_id,$question_id){

    return $query ="SELECT sa.id, sa.text, a.name as users, c.company_name, sa.createdate
            FROM survey_response_answers as sa 
            JOIN survey_questions as sq ON sa.question_id = sq.id
            JOIN aauth_users as a ON a.id = sa.user_id
            JOIN customers as c ON c.id = a.user_link_id
            WHERE sa.question_id = '$question_id'";
  }   

  function survey_tatuses(){
   
    $query = $this->db->query("SELECT * FROM gbl_statuses WHERE id IN('11','5','3','8')");

    return  $query->result(); 
  }  

  function get_response($question_id,$user_id){
    $query = $this->db->query("SELECT * FROM  survey_response_answers WHERE question_id='$question_id' and user_id='$user_id'");
    $result=$query->result_array();
    $comma='';
    $response='';
    foreach ($result as $r) {
      $response.=$r['text'];
      
    }
    return  $response;
  }

  function get_task_type($task_id){

    $query = $this->db->query("SELECT * 
                              FROM  bc_tasks 
                              WHERE task_id='$task_id' ");
    return $query->row_array();

  }

  function countSurveyByStatus($survey_id,$status_id){
    $query = $this->db->query("SELECT  *
                              FROM `bc_tasks` as `t`  
                              JOIN `bc_task_results` as `r` ON `r`.`task_id` = `t`.`task_id`  
                              WHERE `t`.`type_link_id`='$survey_id' and r.status='$status_id'");

    return $query->num_rows();
  }

  function get_participant_count($survey_id,$question_id){
      $query = $this->db->query("SELECT count(DISTINCT r.user_id) as participant_count 
                                FROM bc_tasks as t 
                                JOIN survey_response_answers as r ON r.survey_id=t.type_link_id
                                JOIN aauth_users as a ON a.id=r.user_id
                                WHERE r.question_id='$question_id' 
                                and r.survey_id='$survey_id'  
                                and r.text !=''");
      return $query->row_array();
  }


  function getSurveyTaskByStatus($status_id,$survey_id){
    $query = $this->db->query("SELECT t.*, 
                              r.*, 
                              a.name as user, 
                              s.*, 
                              t.name as task,
                              s.title as  survey,
                              `r`.`id`, 
                              `r`.`id` as task_result_id, 
                              t.task_id as task_id,
                              `s`.`id` as survey_id, 
                              st.name as status,
                              r.createdate,
                              `r`.`user_id`
                              FROM `bc_tasks` as `t`  
                              JOIN `bc_task_results` as `r` ON `r`.`task_id` = `t`.`task_id`  
                              JOIN `survey_list` as `s` ON `s`.`id` = `t`.`type_link_id`
                              LEFT JOIN `aauth_users` as `a` ON `r`.`user_id` = `a`.`id`
                              JOIN `gbl_statuses` as `st` ON `st`.`id` = `r`.`status`
                              WHERE `t`.`type_link_id`='$survey_id' and r.status='$status_id'");

    return $query->result();
  }

  function get_questions($survey_id, $type=''){
    $group_by = "GROUP BY r.question_id";
    if($type=='all'){
      $group_by='';
    }
    
    $query = $this->db->query("SELECT 
                              q.id as question_id,
                              q.question_text, 
                              count(r.question_id) as answer_count, 
                              count(DISTINCT r.user_id) as user_count,
                              q.created
                              FROM survey_questions as q
                              JOIN survey_response_answers as r ON q.id=r.question_id
                              JOIN aauth_users as a ON a.id=r.user_id
                              JOIN bc_tasks as t ON t.type_link_id=r.survey_id
                              WHERE r.survey_id='$survey_id' $group_by");
    if($type=='all'){
      return $query->row_array();
    }else{
      return $query->result_array();
    }
    
       
    
  }
}
?>
