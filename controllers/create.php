<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Create extends CI_Controller {

  public function __construct()
  {
      parent::__construct();
      $this->load->helper('url');
      $this->load->model("survey_model");
  }
  
  public function index()
  {
   // $data["active_surveys"] = $this->survey_model->getActiveSurveys();
    $this->load->view('templates/create/header');
    $this->load->view('templates/create/nav');
    $this->load->view('templates/create/intro');
    $this->load->view('templates/create/footer');
  }
  
  public function save()
  { 
	  if (isset($_POST['submit_val'])) 
	  {
		$this->survey_model->saveSurveyList($_POST['surveyName']);
		$this->survey_model->saveSurveyQuestion($_POST['dynQuestion'], $_POST['dynAnswer'], $_POST['surveyName']);
		$this->load->view('templates/create/header');
		$this->load->view('templates/create/nav');
		$this->load->view('templates/create/intro');
		$this->load->view('templates/create/footer');
	  }
	}
}