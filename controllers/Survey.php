<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Survey extends CI_Controller {

  public function __construct()
  {
      parent::__construct();
    $this->load->library("Aauth");
      $this->load->helper('url');
      $this->load->model("survey_model");
    $this->load->library('grocery_CRUD');
    $this->load->library('table');
    
        $this->load->model('event_model');
    $this->user = $this->aauth->get_user();
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
        $this->app_settings = get_app_settings(base_url());
  }
  //This is for grocery crud to do a join after a set relation 
  //http://www.grocerycrud.com/forums/topic/779-problem-with-where-and-set-relation/
  protected function _unique_join_name($field_name)
    {
   return 'j'.substr(md5($field_name),0,8); 
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
  $data['page_title'] = 'Survey - Create';
  $this->show_view('surveyCreate', $data);
  }
  
  public function index()
  {
      $crud = new grocery_CRUD();
      
      $crud->set_table('survey_list');
      $crud->set_subject('Survey');
    
      $crud->fields('title','subtitle','prefix','slug','enabled');
      $crud->edit_fields('title','subtitle','enabled','type');
      $crud->add_fields('title','subtitle','enabled','type');
      $crud->field_type('prefix', 'invisible', "");
      $crud->field_type('slug', 'invisible', "");
      $crud->where('target_type','survey');
    
      $crud->callback_add_field('type',array($this,'_add_type_field'));
      $crud->callback_add_field('enabled',array($this,'_add_enable_field'));
      $crud->columns('title','subtitle','target_type','enabled','type');
      $crud->callback_edit_field('enabled',array($this,'_add_enable_field'));
      
      $crud->change_field_type('createdate','invisible');
      $crud->callback_before_insert(array($this, 'set_createdate'));
     
      $crud->add_action('Questions', '', '/survey/questions','ui-icon-plus');
      $crud->add_action('Answers', '', '/survey/answers','ui-icon-plus');
      $crud->add_action('Report', '', '/survey/survey_summary','ui-icon-plus');
        //this is for tracking because i cannot pass a table var to the tracking functions below
      $this->session->set_userdata(array('table' => 'survey_list'));
      $crud->callback_before_insert(array($this,'setPrefix'));
      $crud->callback_after_insert(array($this, 'track_insert'));
      $crud->callback_after_update(array($this, 'track_update'));

      $crud->unset_delete();

      $output = $crud->render();

      $output->page_title = 'Survey - Maintain';

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

            
            $crud->set_table('survey_options');
            $crud->set_subject('Options');

            $this->session->set_userdata('table', 'survey_options');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));
      $crud->columns('option_text', 'option_type','created');
      $crud->fields('option_text');
             $crud->where('option_type',1);

            $crud->change_field_type('createdate','invisible');
            $crud->callback_before_insert(array($this, 'set_createdate'));

            $output = $crud->render();

            $output->page_title = 'Survey Options';

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
        $crud->set_relation_n_n("options", "survey_question_option", "survey_options", "survey_question_option.question_id", "option_id" , "option_text",null, array('option_type'=>1));
      }
      else
      {
        $crud->set_relation_n_n("options", "survey_question_option", "survey_options", "question_id", "option_id" , "option_text",null, array('option_type'=>1));
      }
      $crud->fields('question_text','question_type','options','required','prefix','priority','image','created');
      $crud->field_type('prefix', 'hidden', $survey_id);
      $crud->field_type('created', 'invisible', "");
      
      $crud->columns('question_text','question_type','required','created','image','options','priority');
      $crud->order_by('priority','desc');
      $crud->set_field_upload('image','assets/uploads/surveys/');
      $crud->callback_after_upload(array($this,'create_crop'));
            
      //These Keys are like this on purpose, dont change them unless you know what you are doing
      $crud->field_type('question_type','dropdown',
      array('0' => 'CheckBox', '2' => 'Free Text' , '3' => 'Multi Select', '1' => 'Single Select', '4' => 'Rand Value', '5' => 'Price Check'));
      
      //$crud->add_action('Options', '', '/survey/options','ui-icon-plus');
            $this->session->set_userdata('table', 'questions');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));
      $crud->where('prefix',$survey_id);
            $crud->change_field_type('createdate','invisible');
            $crud->callback_before_insert(array($this, 'set_createdate'));
            $crud->callback_before_update(array($this, '_callback_implode_options'));
            $crud->callback_before_insert(array($this, '_callback_implode_options'));

            $output = $crud->render();

            $output->page_title = 'Survey Questions';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }
   function create_crop($uploader_response,$field_info, $files_to_upload)
    {

        $data = getimagesize('./assets/uploads/surveys/'.$uploader_response[0]->name);
        
        $data['file_name'] = $uploader_response[0]->name;

        //set width and height here

        $cropped_width = 250;
        $cropped_height = 250;

        //Get image full size.
        $image_width = $data[0];
        $image_height = $data[1];
        $crop_x = 0;
        $crop_y = 0;

        if($image_width > $image_height){
            $ratio_calc = $image_height/$image_width;
            $new_width = $cropped_height*$ratio_calc;
            $ratio = $image_height / $cropped_height;
            $final_height = $image_height;
            $final_width = ($image_height/$cropped_height)*$cropped_width;
            if($image_width > $cropped_width){
                $crop_x = ($image_width-($cropped_width*$ratio))/2;
            }
        }else{
            $ratio_calc = $image_width/$image_height;
            $new_height = $cropped_width*$ratio_calc;
            $ratio = $image_width/$cropped_width;
            $final_width = $image_width;
            $final_height = ($image_width/$cropped_width)*$cropped_height;
            if($image_height > $cropped_height){
                $crop_y = ($image_height-($cropped_height*$ratio))/2;
            }
        }

        //calculate the difference in size between the original and the resized small one.

        //multiply the small values by the ratio
        $crop['p_crop_x'] = $crop_x;
        $crop['p_crop_y'] = $crop_y;
        $crop['p_crop_w'] = $final_width;
        $crop['p_crop_h'] = $final_height;

        $targ_w = $cropped_width;
        $targ_h = $cropped_height;
        $jpeg_quality = 90;
        $src = './assets/uploads/surveys/'.$data['file_name'];

        $ext_explode = explode('.',$src);
        $ext = $ext_explode[count($ext_explode)-1];

        $src_new = str_replace('.'.$ext,'.jpg','./assets/uploads/surveys/'.$data['file_name']);

        // Determine Content Type
        switch ($ext) {
            case "gif":
                $img_r = imagecreatefromgif($src);
                break;
            case "png":
                $img_r = imagecreatefrompng($src);
                break;
            case "jpeg":
            case "jpg":
                $img_r = imagecreatefromjpeg($src);
                break;
            default:
                $img_r = imagecreatefromjpeg($src);

        }

        $dst_r = ImageCreateTrueColor( $targ_w, $targ_h );
        imagecopyresampled($dst_r,$img_r,0,0,$crop['p_crop_x'],$crop['p_crop_y'],
        $targ_w,$targ_h,$crop['p_crop_w'],$crop['p_crop_h']);
        imagejpeg($dst_r,$src_new,$jpeg_quality);
    }
    function answers($survey_id){

        try{
            $crud = new grocery_CRUD();
      
            $crud->unset_delete();
            $crud->unset_edit();
            //$crud->unset_add();
      
            
            $crud->set_table('survey_response_answers');
            $crud->set_subject('Answers');

            $this->session->set_userdata('table', 'answers');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));

            $crud->set_relation('question_id','survey_questions','question_text');
            $crud->set_relation('option_id','survey_options','option_text');
            $crud->set_relation('user_id','customers','company_name');

            
            $crud->columns('user_id','question_id', 'option_id','text','image','created');
                $crud->set_field_upload('image','images/');
            $crud->fields('question_id', 'option_id','image');
            
            $crud->where('prefix',$survey_id);

           $crud->change_field_type('createdate','invisible');
            $crud->callback_before_insert(array($this, 'set_createdate'));

            $output = $crud->render();

            $output->page_title = 'Survey Answers';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }
  
  public function save()
  { 
    if (isset($_POST['submit_val'])) 
    {
    $this->survey_model->saveSurveyList($_POST['surveyName']);
    $this->survey_model->saveSurveyQuestion($_POST['dynQuestion'], $_POST['dynAnswer'], $_POST['dynOption'], $_POST['surveyName']);
    $data['surveyName']=$_POST['surveyName'];
    $data['page_title'] = 'Survey - Create';
    $this->show_view('surveyCreate', $data);
    }
  }
  
  public function dashboard() {
    $data["active_surveys"] = $this->survey_model->getActiveSurveys();
    $data["survey_responses"] = $this->survey_model->getSurveyResponses();
  $data['page_title'] = 'Survey - Dashboard';
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
  $data['page_title'] = 'Survey - Responses';
  $this->show_view('survey_results', $data);
  }
  
  public function surveyList()
  {
    $data["active_surveys"] = $this->survey_model->getActiveSurveys();
    $data['page_title']='Survey - List';
    $this->show_view('survey_all', $data);
  }

  public function survey_reports()
  {
      $crud = new grocery_CRUD();
      
      $crud->where('prefix',$this->uri->segment(3));
      $crud->set_table('survey_questions');
      $crud->set_subject('Survey');
      $crud->set_relation('prefix','survey_response_answers','question_id');
      $crud->unset_delete();
      $crud->unset_operations();
      $crud->add_action('View Report','','/survey/report_vew');
      $crud->unset_edit();
      $crud->columns('question_text');

      $output = $crud->render();

      $output->page_title = 'Survey Reports';

      $this->crud_view($output);
  }

  function survey_summary($survey_id){
    $question_id = $this->survey_model->getQuestion($survey_id);
    
    $dataset = false;
    $allResponses =0;
    $allPaticipants=0;
    $question = $this->survey_model->get_questions($survey_id);

    foreach($question as $key => $row) {
           $dataset.='dataSet.push([
               "'.$row['question_id'].'",
               "'."<a href='/survey/report_view/".$survey_id."/".$row['question_id']."'>".str_replace(array('"',"'"), '', $row['question_text'])."</a>".'",
               "'.$row['answer_count'].'",
               "'.$row['user_count'].'",
               "'.$row['created'].'"
               ]);';
        $allResponses=$row['answer_count'];
        
    }

    $columns="{ title: 'Id'},
    { title: 'Question'},
    { title: 'Answer Count'},
    { title: 'Participants Count'},
    { title: 'Date Created'}";

    $data['all'] = $this->survey_model->getAllUsers();
    $data['surveyTable'] = $this->survey_model->getSurveyById($survey_id)->row();
    $data['survey_id'] = $survey_id;
    $data['allResponses'] = $this->survey_model->get_questions($survey_id, 'all')['answer_count'];
    $data['questions_with_answer_count'] = $question;
    $data['allPaticipants'] = $this->survey_model->get_questions($survey_id, 'all')['user_count'];
    $data['approvedCount'] = $this->survey_model->countSurveyByStatus($survey_id,8);
    $data['pendingApprovalCount'] = $this->survey_model->countSurveyByStatus($survey_id,3);
    $data['viewedCount'] = $this->survey_model->countSurveyByStatus($survey_id,2);
    $data['continueCount'] = $this->survey_model->countSurveyByStatus($survey_id,11);
    $data["bc_result_count"] = $this->survey_model->get_survey_stats('','survey_stats', $survey_id);
    $data['script'] = $this->data_table_script($dataset, $columns, 1, false);
    $data['page_title'] = "Survey Summary";

    $this->show_view('survey_summary', $data);
  }

  public function task_results($status_id,$survey_id){

      $taskResult=$this->survey_model->getSurveyTaskByStatus($status_id,$survey_id);
      $dataset='';
      foreach ($taskResult as $key => $row) {
                $survey_id = $row->survey_id;
                $user_id = $row->user_id;
                $task_id = $row->task_id;
                $task_result_id = $row->task_result_id;

                $dataset.='dataSet.push([ 
                    "'.$row->id.'", 
                    "'. $row->user.'", 
                    "'.$row->task.'",
                    "'."<a href='/dashboard/survey_report_details/$survey_id/$user_id'>".$row->survey.'</a>",
                    "'.$row->status.'",
                    "'.$row->createdate.'"
                    ]);';
      }

      $columns='{ title: "ID" },
                    { title: "User Id" },
                    { title: "Task" },
                    { title: "Survey" },
                    { title: "Status" },
                    { title: "Createdate" }';

      $data['script'] = "".$this->data_table_script($dataset,$columns)."";
      $data['page_title']='Survey Result';
      $this->show_view('bc_result', $data);


  }

    
/*
Controller Loading report Models and Report View
*/
  public function report_view($survey_id, $question_id)
  {

    $data['page_title']='Survey - Report';
    $data["answers"] = $this->survey_model->getQuestionAnswers($survey_id, $question_id);
    $data["question"] = $this->survey_model->getQuestionDetailsId($question_id);
    $data["number_of_user"] = $this->survey_model->getNumberOfUsers();
    $data["stats_result"] = $this->survey_model->get_question_daily_answers($survey_id, $question_id);
    $data["answer_stats"] = $this->survey_model->get_question_answer_stats($survey_id, $question_id);

    $data['survey_info'] = $this->survey_model->getSurveyById($survey_id)->row();

    $this->show_view('survey_report', $data);
  }

  function survey_details($survey_id,$question_id){
    $response=$_GET['response'];
    $dataset='';
    $data['page_title']='Survey - Report';
    $data["result"] = $this->survey_model->getSurveyResponseDetail($survey_id, $question_id,$response);

    foreach ($data["result"] as $key => $r) {
        $dataset.=' dataSet.push(["'.$r['id'].'",
            "'.$r['Participant'].'",
            "'.$r['text'].'",
            "'.$r['createdate'].'"]);';
    }

    $columns='{ title: "Id" },{ title: "Participant" },{ title: "Answer" },{ title: "Createdate" }';

    $data['script'] = "".$this->data_table_script($dataset,$columns)."";

    $this->show_view('survey_details', $data);

  }

  function data_table_script($dataset, $columns, $order_index=0, $show_buttons=true){
    if($show_buttons){
      $buttuns = ",dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]";
    }else{
      $buttuns="";
    }
    $datatable="var dataSet = [ ];
    ". $dataset."
    $(document).ready(function() {
        $('#report_table').DataTable( 
        {
            'order': [[".$order_index.",'desc' ]],
            data:dataSet,
            columns: [
                ".$columns."
            ]
            $buttuns
        } 
        );
    } );";
    return $datatable;
}

  function _add_type_field()
  {
      return '<select id="field-type"  name="product_id" class="chosen-select" data-placeholder="Select The Survey Type" maxlength="100">
                <option value="Normal"  > Normal </option>
                <option value="Price"  > Price </option>
              </select>';
  }

  function _add_enable_field($value, $primary_key)
  {
   

   if($value==1){
    $option="Active";
    $option2="Inactive";
    $value2='0';
   }else{
   
    $option="Inactive";
    $option2="Active";
    $value2='1';
   }
    return "<select  name='enabled' class='chosen-select' data-placeholder='Select Value'>
              <option selected value='".$value."'>".$option."</option> 
              <option  value='".$value2."'>".$option2."</option> 

            </select>";

  }

   function survey_responses(){
        $crud = new grocery_CRUD();
        
        $crud->set_table('survey_response_answers');
        $crud->set_subject('Survey');
        $crud->set_relation('user_id','aauth_users','name');
        $crud->set_relation('question_id','survey_questions','question_text');
        $crud->order_by("createdate","DESC");
        $crud->columns('user_id','question_id','text','createdate');
        $crud->unset_delete();
        $crud->unset_operations();
        $crud->unset_edit();

        $output = $crud->render();

        $output->page_title = 'Survey Responses';

        $this->crud_view($output);
  }


}