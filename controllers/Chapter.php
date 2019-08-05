<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Chapter extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->model('event_model');
        $this->load->model('survey_model');

        $this->user = $this->aauth->get_user();

        if (!$this->aauth->is_loggedin()){
            redirect('/login');
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

    function index($chapter_id){

        $question_position = $this->survey_model->get_question_position($chapter_id);

        if($question_position === false){
            $data['complete'] = true;
        }
        $logged_user_info = $this->aauth->get_user();
        if (!$this->event_model->seen_latest_news($logged_user_info->id)){
            redirect('/admin/news');
        } 

        $data['chapter'] = $this->survey_model->get_chapter($chapter_id);
        $data['page_title'] = $data['chapter']['name'];
        $this->show_view('chapter', $data);

    }

    function theory($chapter_id, $page=0){
        if($page === 'finished'){

            $data['chapter'] = $this->survey_model->get_chapter($chapter_id);
            $data['page_title'] = $data['chapter']['name'];
            $this->show_view('theory_finished', $data);

        }else{

            $theory = $this->survey_model->get_chapter_theory_pages($chapter_id);
            $data['theory_pages'] = $theory;
            $theory[$page]['content'] =  $this->populate_media($theory[$page]['content']);
            $data['theory'] = $theory[$page];
            $data['page'] = $page;
            $data['last_page'] = count($theory)-1;
            $data['chapter_id'] = $chapter_id;
            $data['page_title'] = $data['theory']['name'];
            $this->show_view('theory', $data);
        }
    }

    function populate_media($content){

        $content_exp = explode('{{', $content);

        foreach ($content_exp as $key => $value) {
            $second_exp = explode('}}', $value);
                if(isset($second_exp[0]) && is_numeric(trim($second_exp[0]))){
                    $content = str_replace('{{'.$second_exp[0].'}}', $this->survey_model->fetch_media($second_exp[0]), $content);
                }
        }

        return $content;
    }

    function assessment($chapter_id, $page=0){

        $data['chapter'] = $this->survey_model->get_chapter($chapter_id);
        $data['page_title'] = $data['chapter']['name'] . ' Assessment';
        $question_position = $this->survey_model->get_question_position($chapter_id);
        $questions = $this->survey_model->get_chapter_questions($chapter_id);
        $data['last_page'] = count($questions)-1;
        $data['chapter_id'] = $chapter_id;

        if($question_position === false){
            //make sure the entire test is actually finished.
            $data['message']['type'] = 'warning';
            $data['message']['message'] = 'You have already completed this Assessment.';
            $this->show_view('assessment_finished', $data);
            return true;
        }else{
            if($page == 0){
                $page = $question_position;
            }
        }

        if(isset($_POST['question_id'])){
            if(!isset($_POST['option']) || $_POST['option'] == ''){

                if($page == $data['last_page']){
                    $page = $page;
                }else{
                    $page = $page-1;
                }
                
                $data['message']['type'] = 'danger';
                $data['message']['message'] = 'Please answer the question to continue';
            }else{
                $this->survey_model->save_answer($_POST['question_id'], $_POST['option']);
                if($page == $data['last_page'] && $this->survey_model->get_question_position($chapter_id) === false){
                    $question_position = false;
                }
            }
        }

        if($question_position === false){
            $this->survey_model->generate_certificate($chapter_id);
            //make sure the entire test is actually finished.
            $data['message']['type'] = 'success';
            $data['message']['message'] = 'Well done! You have completed this assessment.';
            $this->show_view('assessment_finished', $data);

        }else{

            $data['question'] = $questions[$page];
            $data['page'] = $page;

            $this->show_view('assessment', $data);

        }
    }

    function results($chapter_id){
        $question_position = $this->survey_model->get_question_position($chapter_id);
        if($question_position !== false){
            redirect('/chapter/assessment/'.$chapter_id);
        }

        $data['chapter'] = $this->survey_model->get_chapter($chapter_id);
        $data['page_title'] = $data['chapter']['name'] . ' Results';
        $data['results'] = $this->survey_model->get_assessment_results($chapter_id);
        if($data['chapter']['pass_mark'] <= $data['results']['percentage']){
            $data['results']['status'] = '<span class="correct">Passed</span>';
        }else{
            $data['results']['status'] = '<span  class="incorrect">failed</span>';
        }
        
        $this->show_view('assessment_results', $data);

    }

    function test(){
        $img_src = $this->survey_model->generate_certificate(1);

        echo '<img src="/assets/img/certificate/'.$img_src.'"/>';
    }



}