<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Surveys extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->library("Aauth");
		$this->load->model('survey_model');
		$this->load->model('user_model');
        $this->load->model('customer_model');
        $this->load->model('app_model');
        $this->load->model('event_model');
        $this->load->model('task_model');
    }

	public function get_active_surveys_post()
  	{

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson);
        $requestjson = json_decode($requestjson, true);

        $token = $requestjson['token'];

        if ($token != '' && !empty($token)){

            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            $trader_id = false;
            $user_id = false;
            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }

            if($user_id){

            	$surveyCount = $this->survey_model->getActiveSurveys();
            	$data = $surveyCount;
				$message = [
				    'success' => true, // Automatically generated by the model
				    'data' => $data,
				    'message' => 'Successfully Retrieved'
				];

			}else{

				$message = [
				    'success' => false,
				    'data' => array(),
				    'message' => 'Please send an active token.'
				];
			}
		}else{

				$message = [
				    'success' => false,
				    'data' => array(),
				    'message' => 'Please send an active token.'
				];

		}

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
  	}

  	// All Active Price Surveys Post

  	public function get_active_price_surveys_post()
  	{

        $requestjson = file_get_contents('php://input');
        $this->app_model->save_raw_data($requestjson);
        $requestjson = json_decode($requestjson, true);

        $token = $requestjson['token'];

        if ($token != '' && !empty($token)){


            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            $trader_id = false;
            $user_id  = false;
            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }

            if($user_id){

            	$surveyCount = $this->survey_model->getActivePriceSurveys();
            	$data = $surveyCount;
				$message = [
				    'success' => true, // Automatically generated by the model
				    'data' => $data,
				    'message' => 'Successfully Retrieved'
				];

			}else{

				$message = [
				    'success' => false,
				    'data' => array(),
				    'message' => 'Please send an active token.'
				];
			}
		}else{

				$message = [
				    'success' => false,
				    'data' => array(),
				    'message' => 'Please send an active token.'
				];

		}

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
  	}
  
  	function get_survey_post()
	{

		$requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
		$message=array();
		$send_questions_also=false;
        $task_id = 0;

        if(isset($requestjson['task_id'])){
            $task_id = $requestjson['task_id'];
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){

            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            $trader_id = false;
            $user_id = false;
            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }

        }

        if($user_id){

			if (!isset($requestjson['survey_id']))
			{
				$message = [
				'success' => false,
				'message' => 'No Survey ID was supplied'
				];
			}
			else
			{
				$id = $requestjson['survey_id'];
				$data['surveys'] = $this->survey_model->getActiveSurveysById($id);

				if ($data['surveys'] == null){

					$message = [
					'success' => false,
					'message' => 'Survey does not exist'
					];

				}else{
			 
					$data['surveys']->questions = $this->survey_model->getSurveyQuestions($id);
					$data['surveys']->question_count = sizeOf($data['surveys']->questions);

					if($send_questions_also == false){
						unset($data['surveys']->questions);
					}

					if ($data['surveys']->question_count > 0){

						$this->task_model->change_task_status('survey', $id, $user_id, 2, $task_id); // Viewed

						$message = [
							'id' => $data['surveys']->id,
							'data' => $data['surveys'],
							'success' => true,
							'message' => 'OK'
						];
					}
					else
					{
						$message = [
						'id' => 0,
						'data' => array(),
						'question_count' => 0,
						'success' => false,
						'message' => 'Survey has no questions.'
						];
					}
				}
			}
		}else{

			$message = [
			    'success' => false,
			    'data' => array(),
			    'message' => 'Please send an active token.'
			];			
		}

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
  	}


  	// Active Price Surveys
  	function get_price_survey_post()
	{

		$requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
		$message=array();
		$send_questions_also=false;

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){

            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            $trader_id = false;
            $user_id = false;
            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }

        }

        if($user_id){

			if (!isset($requestjson['survey_id']))
			{
				$message = [
				'success' => false,
				'message' => 'No Survey ID was supplied'
				];
			}
			else
			{
				$id = $requestjson['survey_id'];
				$data['surveys'] = $this->survey_model->getActivePriceSurveysById($id);
                $task_id = 0;

                if(isset($requestjson['task_id'])){
                    $task_id = $requestjson['task_id'];
                }

				if ($data['surveys'] == null){

					$message = [
					'success' => false,
					'message' => 'Survey does not exist'
					];

				}else{
			 
					$data['surveys']->questions = $this->survey_model->getSurveyQuestions($id);
					$data['surveys']->question_count = sizeOf($data['surveys']->questions);

					if($send_questions_also == false){
						unset($data['surveys']->questions);
					}

					if ($data['surveys']->question_count > 0){

						$this->task_model->change_task_status('price_survey', $id, $user_id, 2, $task_id); // Viewed

						$message = [
							'id' => $data['surveys']->id,
							'data' => $data['surveys'],
							'success' => true,
							'message' => 'OK'
						];
					}
					else
					{
						$message = [
						'id' => 0,
						'data' => array(),
						'question_count' => 0,
						'success' => false,
						'message' => 'Survey has no questions.'
						];
					}
				}
			}
		}else{

			$message = [
			    'success' => false,
			    'data' => array(),
			    'message' => 'Please send an active token.'
			];			
		}

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
  	}
	
	function get_survey_question_post()
	{

		$requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
		$message=array();
        $task_id = 0;

        if(isset($requestjson['task_id'])){
            $task_id = $requestjson['task_id'];
        }

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){

            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            $trader_id = false;
            $user_id = false;

            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }

        }else{
        $user_id="";
    	}

        if($user_id){
		
			if (!isset($requestjson['survey_id']))
			{
				$message = [
	            'success' => false,
	            'message' => 'No Survey ID was supplied'
				];
			}
			else
			{
				$survey_id = $requestjson['survey_id'];
				$survey = $this->survey_model->getActiveSurveysById($survey_id);
				if ($survey==null)
				{
					$message = [
						'success' => false,
						'message' => 'Invalid or dissabled Survey ID supplied'
						];
				}
				else
				{
					$question = 0;
					if (isset($requestjson['question_id'])){
                        $question =  $requestjson['question_id'];
					}

					$data = $this->survey_model->getNextUnansweredQuestion($survey_id, $user_id);
					
					if (!is_object($data['Question']))
					{
						$message = [
						'success' => true,
						'data' => array(),
						'message' => 'All questions have been answered.'
						];

						 $this->task_model->change_task_status('survey', $survey_id, $user_id, 3, $task_id); // pending approval
						
						 $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
						 return;
					}

					$questions = $this->survey_model->getSurveyQuestions($survey_id);
					$question_count = sizeOf($questions);

					$question_number = $question_count - $data['Questions_left'] + 1;
					$data['Question']->question_number = $question_number;
					$message = [
						'id' => $data['Question']->id,
						'data' => $data['Question'],
						'success' => true,
						'message' => 'OK'
					];
				}
			}
		}else{

			$message = [
			    'success' => false,
			    'data' => array(),
			    'message' => 'Please send an active token.'
			];			
		}

        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
  
	}

	// placed token left original code commented
	function get_survey_results_post()
	{
		$requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);

        $this->load->model('user_model');
        $this->load->model('customer_model');
        $this->load->model('trader_model');

        $trader_id = false;
        $user_id = false;
        //this is here for when a trader completes a task on behalf of a store.
        if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
            $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
            $user_id = $user['id'];
            $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
            $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
        }else{
            $user_id = $this->user_model->get_user_from_token($requestjson['token']);
        }

        $survey_id = $requestjson['survey_id']; 
		
		$message=array();
		if (!isset($survey_id))
		{
			$message[0] = [
            'success' => false,
            'message' => 'No Survey ID was supplied'
			];
		}
		if (!$user_id)
		{
			$message[1] = [
            'success' => false,
            'message' => 'No User ID was supplied'
			];
		}
		if (sizeOf($message)==0)
		{
			$survey = $this->survey_model->getActiveSurveysById($survey_id);
			if ($survey==null)
			{
				$message = [
					'success' => false,
					'message' => 'Invalid Survey ID supplied'
					];
			}
			else
			{
				$responses = $this->survey_model->getResponsesPerSurvey($user_id, $survey_id);
				if ($responses==null)
				{
					$message = [
					'success' => false,
					'message' => 'No results found for this user.'
					];
				
				}
				else
				{
					$responseId = $responses->id;
					$data = $this->survey_model->getResponseData($survey_id, $responseId);
					if ($data==null)
					{
						$message = [
						'success' => false,
						'message' => 'Failed'
					];
					}
					else
					{
						$message = [
						'questions' => $data,
						'question_count'=>sizeOf($data['responses']),
						'success' => true,
						'message' => 'OK'
					];
					}
				}
			}
		}
		$this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
  	}

	function put_survey_answer_post()
	{
		$requestjson = file_get_contents('php://input');

        $requestjson = json_decode($requestjson, true);

		$message=array();

        if ($requestjson['token'] != '' && !empty($requestjson['token'])){

            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            $trader_id = false;
            $user_id = false;
            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }

        }else{
			$message = [
            'success' => false,
            'message' => 'Please supply a valid token.'
			];
    	}
        if (!$user_id){
			$message = [
            'success' => false,
            'message' => 'Please supply a valid token.'
			];
    	}

		if (!isset($requestjson['question_id']))
		{
			$message = [
            'success' => false,
            'message' => 'No Question ID was supplied'
			];
		}
		if (!isset($requestjson['answer']))
		{
			$message = [
            'success' => false,
            'message' => 'No answer was supplied'
			];
		}
		if (sizeOf($message)==0)
		{
			$questionId = $requestjson['question_id'];
			$uid = $user_id;
			$answer = $requestjson['answer'];

			$question = $this->survey_model->getQuestionDetailsId($questionId);
			if ($question==null)
			{
				$message = [
				'success' => false,
				'message' => 'Invalid Question ID supplied'
				];
			}
			else
			{
				$questionType = $question->question_type;
				$surveyId = $question->prefix;

				$response = new stdClass();
				$response->survey_id = $surveyId;
				$response->user_id = $uid;
				$response->question_id = $questionId;

                $task_id = 0;

                if(isset($requestjson['task_id'])){
                    $task_id = $requestjson['task_id'];
                }

				if(in_array($questionType, array(0,1,3))){
					if($questionType == 3){
						$response->option_id = 0;
						if(is_array($answer)){
							$answers = $answer;
						}else{
							$answers = explode(',', $answer);
						}
						$response->text = "";
						if(count($answers) > 1){
							foreach ($answers as $key => $answer) {
								$option_text = $this->survey_model->getQuestionOptionText((int)$answer);
								$response->text .= $option_text.",";
							}
						}else{
							$option_text = $this->survey_model->getQuestionOptionText((int)$answer);
							$response->text .= $option_text;
						}

					}else{

						$response->option_id = (int)$answer;
						$option_text = $this->survey_model->getQuestionOptionText($response->option_id);
						$response->text = $option_text;
					}
				}else{
					$response->text = $answer;
				}
						
				$date = date("Y-m-d H:i:s");
				$response->createdate = $date;
					
				if($this->survey_model->saveSurveyAnswer($response)){
					
					$this->task_model->change_task_status('survey', $surveyId, $user_id, 11, $task_id); // Continue
					$this->event_model->private_track_event($user_id, 'app', 'brand_connect', 'survey question answered', $surveyId, $date);
				}
				$questionsRemaining = $this->survey_model->remainingQuestions($response->survey_id, $user_id);

				if ($questionsRemaining==0)
				{
					$this->task_model->change_task_status('survey', $surveyId, $user_id, 3, $task_id); // Continue
				}

					$message = [
						'response' => $response,
						'questions_remaining'=>$questionsRemaining,
						'success' => true,
						'message' => 'OK'
					];
			}
		}
		$this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
	}
	
	function get_survey_report_post(){
        $requestjson = file_get_contents('php://input');
        $requestjson = json_decode($requestjson, true);
        $message=array();
        if ($requestjson['token'] != '' && !empty($requestjson['token'])){

            $this->load->model('user_model');
            $this->load->model('customer_model');
            $this->load->model('trader_model');

            $trader_id = false;
            $user_id = false;
            //this is here for when a trader completes a task on behalf of a store.
            if (isset($requestjson['store_id']) && !empty($requestjson['store_id'] && $requestjson['store_id']) != ''){
                $user = $this->customer_model->get_user_from_customer_id($requestjson['store_id']);
                $user_id = $user['id'];
                $trader_user_id = $this->user_model->get_user_from_token($requestjson['token']);
                $trader_id = $this->trader_model->get_trader_from_user_id($trader_user_id);
            }else{
                $user_id = $this->user_model->get_user_from_token($requestjson['token']);
            }

        }else{
            $message = [
                'success' => false,
                'message' => 'Please supply a valid token.'
            ];
        }
        if (!$user_id){

            $message = [
                'success' => false,
                'message' => 'Please supply a valid token.'
            ];
        }
        $survey_id = $requestjson['survey_id'];

        $query  = $this->survey_model->get_survey_results($survey_id,$user_id);

        if ($survey_id==null)
        {
            $message = [
                'success' => false,
                'message' => 'Invalid Survey ID supplied'
            ];
        }else{
            $message = [
                'RESULTS' => $query,
                'success' => true,
                'message' => 'OK'
            ];
        }
        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }
}