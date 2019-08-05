<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Sim_control extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('airtime_model');
        //$this->load->library('WSSoapClient');

        /*$this->user = 1952947;
        $this->pass = '084549';
        $this->wsdl_url = "http://pi.d-n-s.name:3088/airtimeplus/?wsdl";*/
        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
/*        if (!$this->aauth->is_allowed('Sim Control')){
            $this->event_model->track('error','permissions', 'Management');
            redirect('/admin/permissions');
        } */
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

        $data['page_title'] = 'Buy Vouchers';
        $data['vouchers'] = $this->airtime_model->get_airtime_vouchers();
        $this->show_view('vouchers', $data);
    }

    function airtime_test(){

        $function = 'fetchProducts';
        $info = array();

        var_dump($this->airtime_model->airtime($function,$info));
    }

    function test_eskom(){
        echo 'running test: R20 eskom to 0827378714 : ';
        $result = $this->airtime_model->buy_voucher(123, 0, '0827378714');
        print_r($result);
    }

    function query_order($orderno){
        echo 'querying orderno : '.$orderno.': ';
        $info['orderno'] = $orderno;
        $result = $this->airtime_model->airtime('queryOrder',$info);
        print_r($result);
    }

    function update_vouchers(){

        $function = 'fetchProducts';
        $info = array();

        $vouchers = $this->airtime_model->airtime($function,$info);

        foreach ($vouchers->products as $key => $value) {
            $this->airtime_model->update_vouchers($value);
        }
    }


    function buy_voucher(){

        if(isset($_POST['voucher']) && $_POST['voucher'] != ''){
            $voucher = $this->airtime_model->get_voucher($_POST['voucher']);
            

            if(isset($_POST['amount']) && $_POST['amount'] != '0' && $_POST['amount'] < 11 && $_POST['amount'] > 1){
                $voucher['sellvalue'] = $_POST['amount'];
            }else{
                echo 'You can only purchase between R2 and R10';
                exit;
            }

            $refno = $this->airtime_model->voucher_purchase_log('',$voucher['id'], $_POST['cellphone'],$voucher['sellvalue']);

            $info['refno'] = trim($refno);
            $info['network'] = trim($voucher['network']);
            $info['sellvalue'] = trim($voucher['sellvalue']);
            $info['count'] = 1;
            $info['extra'] = trim($_POST['cellphone']);

            $response = $this->airtime_model->airtime('placeOrder',$info);
            $response->refno = $refno;

            if(!isset($response) || !isset($response->message)){
                $response->orderno = 'failed';
            }

            $this->airtime_model->update_purchase($response);

            print_r($response);
        }
    }

    function purchased_list(){

        try{
            $crud = new grocery_CRUD();

            
            $crud->set_table('voucher_purchase_log');
            $crud->set_subject('Purchase');

            $this->session->set_userdata('table', 'voucher_purchase_log');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));

            $crud->columns('voucher_id','user_id','cellphone','status','orderno','createdate');

            $crud->set_relation('user_id','aauth_users','name');
            $crud->set_relation('status','airtime_status','name');
            $crud->set_relation('voucher_id','airtime_vouchers','description');

            $output = $crud->render();

            $output->page_title = 'Purchases';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
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

    function cron(){
        $this->airtime_model->check_purchases();
    }
    
   

}