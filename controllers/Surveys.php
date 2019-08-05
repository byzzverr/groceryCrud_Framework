<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Surveys extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('survey_model');

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            $rem_me = 1;
            $this->aauth->login($_GET['username'],$_GET['password'],$rem_me);
        }

        $this->user = $this->aauth->get_user();
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
   
    function index(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('surveys');
        $crud->set_subject('Survey');

        $crud->set_relation('complete_first','surveys','name');

        $crud->unset_delete();

        $crud->change_field_type('createdate','invisible');
        $crud->callback_before_insert(array($this, 'set_createdate'));

        $crud->add_action('Questions', '', '/surveys/questions','ui-icon-plus');
        $crud->add_action('Answers', '', '/surveys/answers','ui-icon-plus');
     
        //this is for tracking because i cannot pass a table var to the tracking functions below
        $this->session->set_userdata(array('table' => 'surveys'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Surveys';

        $this->crud_view($output);
    }

    function answers($survey_id=1){

        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('answers');
            $crud->set_subject('Answer');

            $this->session->set_userdata('table', 'answers');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));

            $crud->set_relation('question_id','questions','question');

             $crud->where('survey_id',$survey_id);

            $crud->change_field_type('createdate','invisible');
            $crud->callback_before_insert(array($this, 'set_createdate'));

            $output = $crud->render();

            $output->page_title = 'Survey Answers';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }

    function questions($survey_id=1){

        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('questions');
            $crud->set_subject('Question');

            $crud->columns('survey_id','question','answer','priority');

            $this->session->set_userdata('table', 'questions');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));

            $crud->set_relation('survey_id','surveys','name');

            $crud->order_by('priority','asc');
            $crud->where('survey_id',$survey_id);

            $crud->change_field_type('createdate','invisible');
            $crud->callback_before_insert(array($this, 'set_createdate'));

            $crud->callback_field('options',array($this,'_callback_explode_options'));
            $crud->callback_before_update(array($this, '_callback_implode_options'));
            $crud->callback_before_insert(array($this, '_callback_implode_options'));

            $output = $crud->render();

            $output->page_title = 'Survey Questions';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }

    function _callback_explode_options($value='',$primary_key=null){

        $return = '';
        if($value != ''){
            $options = explode('|', $value);
            foreach ($options as $key => $option) {
                $return .= '<input id="field-options_'.$key.'" class="options_'.$key.' option" name="options_'.$key.'" type="text" value="'.$option.'" maxlength="255"> <br>';
            }
        }else{
            $return .= '<input id="field-options_0" class="options_0 option" name="options_0" type="text" value="'.$value.'" maxlength="255"> <br>';
        }
        $return .= '<a class="add_input_link">+ Add</a>';
        return $return;

    }

    function _callback_implode_options($post_array, $primary_key){

        $return = '';
        $comma = '';

        foreach ($post_array as $key => $option) {
            if(strpos($key, 'options_') !== false && $option != ''){
                $return .= $comma.$option;
                $comma = '|';
            }
        }

        $post_array['options'] = $return;

        return $post_array;

    }

    function _callback_name_key($value, $row){

        $options = $this->survey_model->get_question_options($row->id);
        return $options[$value];
    }

    function _callback_get_options($value, $primary_key){

        $options = $this->survey_model->get_question_options($primary_key);
        $return = '<select name="option">';
        foreach ($options as $key => $value) {
            $return .= '<option value="'.$key.'">'.$value.'</option>';
        }

        $return .= '</select>';
        return $return;
    }

    function _callback_dissable_field($value, $primary_key){
        return '<input type="text" name="kim_code" value="'.$value.'" disabled="disabled" />';
    }

    function track_insert($post_array,$primary_key){
        $catgory = 'management';
        $action = 'insert';
        $label = 'User added a new entry to the '.$this->session->userdata('table').' table';
        $value = $primary_key;
        $this->event_model->track_event($catgory, $action, $label, $value);
        $this->session->unset_userdata(array('table'));
    }

    function track_update($post_array,$primary_key){
        $catgory = 'management';
        $action = 'update';
        $label = 'User updated an entry in the '.$this->session->userdata('table').' table';
        $value = $primary_key;
        $this->event_model->track_event($catgory, $action, $label, $value);
        $this->session->unset_userdata(array('table'));
    }

    function set_createdate($post_array){

        $post_array['createdate'] = date("Y-m-d H:i:m");
        return $post_array;
    }


    function assessment($survey_id, $page=0){

        $data['survey'] = $this->survey_model->get_survey($survey_id);
        $data['page_title'] = $data['survey']['name'];
        $question_position = $this->survey_model->get_question_position($survey_id);
        $questions = $this->survey_model->get_survey_questions($survey_id);
        $data['last_page'] = count($questions)-1;
        $data['survey_id'] = $survey_id;

        if($question_position === false){
            //make sure the entire test is actually finished.
            $data['message']['type'] = 'warning';
            $data['message']['message'] = 'You have already completed this Survey.';
            $this->show_view('survey_finished', $data);
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
                if($page == $data['last_page'] && $this->survey_model->get_question_position($survey_id) === false){
                    $question_position = false;
                }
            }
        }

        if($question_position === false){
            //$this->survey_model->generate_certificate($survey_id);
            //make sure the entire test is actually finished.
            $data['message']['type'] = 'success';
            $data['message']['message'] = 'Well done! You have completed this survey.';
            $this->show_view('survey_finished', $data);

        }else{

            $data['question'] = $questions[$page];
            $data['page'] = $page;

          $this->load->view('include/header_no_logo', $data);
          $this->load->view('survey', $data);
          $this->load->view('include/footer', $data);

        }
    }

    function results($survey_id){
        $question_position = $this->survey_model->get_question_position($survey_id);
        if($question_position !== false){
            redirect('/survey/assessment/'.$survey_id);
        }

        $data['survey'] = $this->survey_model->get_survey($survey_id);
        $data['page_title'] = $data['survey']['name'] . ' Results';
        $data['results'] = $this->survey_model->get_survey_result($survey_id);     
        $this->show_view('survey_results', $data);

    }


}