<?php

class Survey_model extends CI_Model { 

   public function __construct()
   {
      parent::__construct();
   }

   function get_user_info($user_id){
    $query = $this->db->query("SELECT * FROM aauth_users WHERE id = ?", array($user_id));
    return $query->row_array();
   }

   function update_password($user_id, $password){
    $query = $this->db->query("UPDATE aauth_users SET pass = '$password' WHERE id = ?", array($user_id));
   }

   function save_company_id($company_id, $user_id){
    $query = $this->db->query("UPDATE aauth_users SET company_id = '$company_id' WHERE id = ?", array($user_id));
   }

   function get_survey($survey_id){

      $query = $this->db->query("SELECT * FROM surveys WHERE id = ?", array($survey_id));
      return $query->row_array();
   }

   function get_survey_result($survey_id, $user_id=''){
      $results = $this->get_assessment_results($survey_id,$user_id);

      return $results;
   }

  function get_assessment_results($survey_id, $user_id=''){

      if($user_id == ''){
        $user_info = $this->aauth->get_user();

        if (isset($user_info->id)) {
          $user_id = $user_info->id;
        }else{
          $user_id = '0';
        }
      }

      $query = $this->db->query("SELECT q.id, q.survey_id, q.question, q.type, q.options, q.answer as 'question_answer', q.priority, a.answer FROM questions q 
        LEFT JOIN answers a on a.question_id = q.id AND a.user_id = '$user_id'
        WHERE q.survey_id = ? ORDER BY q.priority ASC", array($survey_id));
      
      $questions = $query->result_array();

      $questions['total_questions'] = count($questions);

      return $questions;

   }

  function get_survey_questions($survey_id, $user_id=''){

      if($user_id == ''){
        $user_info = $this->aauth->get_user();
        if (isset($user_info->id)) {
          $user_id = $user_info->id;
        }else{
          $user_id = '0';
        }
      }

      $query = $this->db->query("SELECT q.id, q.survey_id, q.question, q.type, q.options, q.answer as 'question_answer', q.priority, a.answer FROM questions q
        LEFT JOIN answers a on a.question_id = q.id AND a.user_id = '$user_id'
        WHERE q.survey_id = ? ORDER BY q.priority ASC", array($survey_id));
      return $query->result_array();

   }

   function save_answer($question_id, $answer){

      $user_info = $this->aauth->get_user();

      if (isset($user_info->id)) {
        $user_id = $user_info->id;
      }else{
        $user_id = '0';
      }

      $query = $this->db->query("SELECT * FROM answers WHERE question_id = ? AND user_id = ?", array($question_id, $user_id));

      if($query->num_rows() == 1){
        $old_answer = $query->row_array();
        if($answer != $old_answer['answer']){
          $this->db->query("UPDATE answers SET answer = ? WHERE id = ?", array($answer,$old_answer['id']));
          $this->event_model->track_event('Assessment', 'Updated Answer', "User Updated question $question_id from ".$old_answer['answer']." to ".$answer, $old_answer['id']);
        }
      }else{
        $this->db->query("INSERT INTO answers (answer, question_id, user_id, createdate) VALUES (?,?,?,?)", array($answer, $question_id, $user_id, date("Y-m-d H:i:s")));
        $this->event_model->track_event('Assessment', 'Answered Question', "User answered question $question_id  with ".$answer, $this->db->insert_id());
      }

   }

   function get_question_position($survey_id, $user_id=''){

        if($user_id == ''){
          $user_info = $this->aauth->get_user();

          if (isset($user_info->id)) {
            $user_id = $user_info->id;
          }else{
            $user_id = '0';
          }
        }

        $query = $this->db->query("SELECT q.id, q.survey_id, q.question, q.type, q.options, q.answer as 'question_answer', q.priority, a.answer FROM questions q
        LEFT JOIN answers a on a.question_id = q.id AND a.user_id = '$user_id'
        WHERE q.survey_id = ? ORDER BY q.priority ASC", array($survey_id));

        $questions = $query->result_array();

        //print_r($questions);

      foreach ($questions as $key => $question) {
        if($question['answer'] == ''){
          return $key;
        }
      }

      return false;

   }

   function generate_certificate($chapter_id){


      $query = $this->db->query("SELECT module_id FROM mod_chapters WHERE chapter_id = ?", array($chapter_id));
      $module_id = $query->row_array();

      if($this->is_module_complete($module_id['module_id'])){
        $query1 = $this->db->query("SELECT a.* FROM chapters a, mod_chapters b WHERE module_id = ? AND a.id = b.chapter_id", array($module_id['module_id']));
        $chapters = $query1->result_array();

        $query = $this->db->query("SELECT * FROM modules WHERE id = ?", array($module_id['module_id']));
        $module = $query->row_array();

        $user_info = $this->aauth->get_user();

        if (isset($user_info->id)) {
          $user_id = $user_info->id;
        }else{
          $user_id = '0';
        }

        $filename = str_replace(' ', '_', $user_info->name).'_'.$module_id['module_id'].'.jpg';

          if($this->is_module_complete($module_id['module_id']) && !file_exists('./assets/img/certificate/'.$filename)){
            $subject = 'Module Complete';

            $data = array('subject' => $subject, 'email' => $user_info->email, 'name' => $user_info->name);
            
            $this->send_email('module_complete', $data);
          }
          $this->load->library('image_lib');

          $config['image_library'] = 'GD2';
          $config['source_image'] = './assets/img/certificate/blank_cert.jpg';
          $config['new_image'] = './assets/img/certificate/'.$filename;
          $config['wm_text'] = $module['name'];
          $config['wm_type'] = 'text';
          $config['wm_font_path'] = './assets/font/arial.ttf';
          $config['wm_font_size'] = '24';
          $config['wm_font_color'] = '000000';
          $config['wm_vrt_alignment'] = 'top';
          $config['wm_hor_alignment'] = 'center';
          $config['wm_vrt_offset'] = '480';
          $config['wm_hor_offset'] = '-20';

          $this->image_lib->initialize($config); 

          
          if ( ! $this->image_lib->watermark())
          {
              echo $this->image_lib->display_errors();
          }

          $config['image_library'] = 'GD2';
          $config['source_image'] = './assets/img/certificate/'.$filename;
          $config['new_image'] = './assets/img/certificate/'.$filename;
          $config['wm_text'] = $user_info->name;
          $config['wm_type'] = 'text';
          $config['wm_font_path'] = './assets/font/arial.ttf';
          $config['wm_font_size'] = '24';
          $config['wm_font_color'] = '000000';
          $config['wm_vrt_alignment'] = 'top';
          $config['wm_hor_alignment'] = 'center';
          $config['wm_vrt_offset'] = '320';
          $config['wm_hor_offset'] = '30';

          $this->image_lib->initialize($config); 

          
          if ( ! $this->image_lib->watermark())
          {
              echo $this->image_lib->display_errors();
          }

        }
   }

    function send_email($template, $data){

        $this->load->library('email');

        $config['mailtype'] = 'html';

        $this->email->initialize($config);

        $this->email->from('hulamin@deepcurrent.co.za', 'Hulamin Training');
        $this->email->to($data['email']);
        $this->email->bcc('byzz.verr@gmail.com');

        $this->email->subject($data['subject']);
        $this->email->message($this->load->view('templates/emails/'.$template.'.php', $data, TRUE));  

        /*$this->email->send();*/

        /*echo $this->email->print_debugger();*/

    }

}