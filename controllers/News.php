<?php
class News extends CI_Controller {

  public function __construct()
  {
      parent::__construct();
	  $this->load->library("Aauth");
       $this->load->library('pagination');
      $this->load->helper('url');
      $this->load->model("survey_model");
      $this->load->model("order_model");
	  $this->load->library('grocery_CRUD');
	  $this->load->model('event_model');
	  $this->load->model('news_model');
	  $this->load->model('user_model');
      
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
    
function detailed($news_id){
    
    $data['page_title'] = 'News';
    $data['news_results']= $this->news_model->get_news_byid($news_id);
   
	$this->show_view('reports/report_table', $data);
}

}