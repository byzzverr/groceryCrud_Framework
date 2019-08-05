<?php


if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Testing extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->library('');
        $this->load->model('event_model');
        $this->load->model('spazapp_model');
        $this->load->model('testing_model');
        $this->load->model('customer_model');
        $this->load->library("Aauth");

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Management')){
            $this->event_model->track('error','permissions', 'Management');
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

    function index(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('test_methods');

        $crud->set_subject('Test');

        $crud->columns('name','description','folder','controller','method_name','createdate');

/*        $this->session->set_userdata(array('table' => 'customers'));
        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));*/

        $output = $crud->render();

        $output->page_title = 'Spazapp API Test Methods';

        $this->crud_view($output);

    }

    function results_log(){
		
        $crud = new grocery_CRUD();
        
        $crud->set_table('test_results');

        $crud->set_subject('Test Result');

        $crud->unset_delete();
        $crud->unset_edit();


        $crud->set_relation('test_meathod_id','test_methods','name');
        $crud->set_relation('user_id','aauth_users','name');

        $crud->callback_column('test_json',array($this,'_shorten_result'));
        $crud->callback_column('result_json',array($this,'_shorten_result'));


        $output = $crud->render();

        $output->page_title = 'Test Results';

        $this->crud_view($output);

    }

    function test_list(){

        $data['page_title'] = 'Spazapp API Test Methods';
        $data['test_methods'] = $this->testing_model->fetch_api_test_methods();
        $this->show_view('/testing/test_list', $data);

    }

    function store_test_result($test_method_id){

        $this->load->model("testing_model");

        $requestjson = file_get_contents('php://input');

        $all_pieces = explode('||', $requestjson);

        $this->testing_model->store_test_results(array(
                'test_meathod_id'   =>  $test_method_id,
                'user_id'           =>  $this->aauth->is_loggedin(),
                'test_json'         =>  $all_pieces[0],
                'result_json'       =>  $all_pieces[1],
                'test_result'       =>  $all_pieces[2],
                'createdate'        =>  date('Y-m-d H:i:s')
        ));
    }
    function store_custom_test_result(){
        $this->load->model("testing_model");

        $requestjson = file_get_contents('php://input');

        $all_pieces = explode('||', $requestjson);

        $this->testing_model->store_test_results(array(
            'test_meathod_id'   =>  '0',
            'user_id'           =>  $this->user->id,
            'test_json'         =>  $all_pieces[0],
            'result_json'       =>  $all_pieces[1],
            'test_result'       =>  $all_pieces[2],
            'createdate'        =>  date('Y-m-d H:i:s')
        ));
    }

    function custom_test(){

         $data['page_title'] = 'Spazapp API Custom Test Methods';
         $this->show_view('/testing/custom_test_list', $data);
    }

    function custom_test_select(){
        $this->load->model("Testing_model");
        $data['h']=$this->testing_model->select();
        $data['h2']=$this->testing_model->select_by_id();
        $data['page_title'] = 'Spazapp API Custom Test Methods';
        $this->show_view('/testing/custom_test_select_list', $data);

    }

    public function _shorten_result($value, $row)
    {
        return substr($value, 0,25);
    }

function generateRandomNumber($length){
   $otp= $this->user_model->generateRandomNumber($length);
   echo $otp;
}
}




