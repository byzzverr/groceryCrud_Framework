<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Financial extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('financial_model');
        $this->load->model('customer_model');
        $this->load->model('user_model');

        $this->user = $this->aauth->get_user();
        $this->app_settings = get_app_settings(base_url());

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Finanancial')){
            $this->event_model->track('error','permissions', 'Finanancial');
            redirect('/'.$this->app_settings.'admin/permissions');
        }

        if(isset($_POST['date_from']) || isset($_POST['date_to'])){

            $date_from = $_POST['date_from'];
            $date_to = $_POST['date_to'];

        }elseif($this->session->userdata('dashboard_date_from') && $this->session->userdata('dashboard_date_from') != ''){            

            $date_from = $this->session->userdata('dashboard_date_from');
            $date_to = $this->session->userdata('dashboard_date_to');

        }else{

            $date_minus1week = date("Y-m-d H:m", strtotime('-1 week', time()));
            $date_from = $date_minus1week;
            $date_to = date("Y-m-d H:i");
        }

        $this->session->set_userdata('dashboard_date_from', $date_from);
        $this->session->set_userdata('dashboard_date_to', $date_to);

    }

    function show_view($view, $data=''){
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
		
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');
        $data['page_title'] = 'Wallet Breakdown';

        $data['wallets'] = $this->financial_model->get_all_wallets($data['date_from'], $data['date_to']);
        $data['wallet_total'] = 0;
        $data['internal_wallets'] = array();
        $data['internal_wallet_total'] = 0;
        foreach ($data['wallets'] as $key => $value) {
            if($value['user_group'] == 'Internal'){
                $data['internal_wallets'][$value['cellphone']] = $value;
                $data['internal_wallets'][$value['cellphone']]['colour'] = "hsl(".rand(0,359).",100%,50%)";
                $data['internal_wallet_total'] += $value['balance'];
            }else{
                $data['wallet_total'] += $value['balance'];
            }
        }

        $data['grand_total'] =  $data['wallet_total'] + $data['internal_wallet_total'];

        $count = 50;
        $limit = 50;

        $data['script'] = '
        $(function() {
            $("#datepicker" ).datepicker( { dateFormat: "yy-mm-dd" });
            $("#datepicker1" ).datepicker( { dateFormat: "yy-mm-dd" });
        });';


        $this->show_view('financial_report', $data);
    }


function user_wallet($user_id){
        
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');

        $data['user'] = $this->user_model->get_user($user_id);
        $data['page_title'] = $data['user']->name.' Wallet Breakdown';
        $data['transacions'] = $this->financial_model->get_wallet_transactions($data['user']->username,$data['date_from'], $data['date_to'],'all');
        $data['total'] = 0;
        $data['debits'] = 0;
        $data['credits'] = 0;
        $categories = array();
        foreach ($data['transacions'] as $key => $value) {
            if(!isset($categories[$value['category']])){
                $categories[$value['category']]['colour'] = "hsl(".rand(0,359).",100%,50%)";
                $categories[$value['category']]['balance'] = 0;
            }
            if($value['debit'] == 0){
                $data['credits'] += $value['credit'];
                $categories[$value['category']]['balance'] += $value['credit'];
            }else{
                $data['debits'] += $value['debit'];
                $categories[$value['category']]['balance'] -= $value['debit'];
            }
        }
        $data['categories'] = $categories;
        $data['total'] =  $data['credits'] - $data['debits'];

    $data['script'] = '

        $(function() {
            $("#datepicker" ).datepicker( { dateFormat: "yy-mm-dd" });
            $("#datepicker1" ).datepicker( { dateFormat: "yy-mm-dd" });
        });

        ';


        $this->show_view('wallet_financial_report', $data);
    }

    function _callback_get_user_id($value, $row){
        $msisdn = $row->msisdn;
        $user = $this->user_model->user_wallet_find($msisdn);
        return $user['id'];
    }

    function _callback_get_user_name($value, $row){
        $msisdn = $row->msisdn;
        $user = $this->user_model->user_wallet_find($msisdn);
        return $user['name'];
    }

    function _callback_get_user_group($value, $row){
        $msisdn = $row->msisdn;
        $user = $this->user_model->user_wallet_find($msisdn);
        return $user['user_group'];
    }
  
    function wallets(){
        $data['page_title'] = 'Wallets';
        $data['wallets'] = $this->financial_model->get_all_wallets();
        $this->show_view('reports/wallets_report', $data);
    }

    function wallet_transactions(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('wallet_transactions');
        $crud->set_subject('Wallet Transactions');   

        $crud->callback_column('user_id',array($this,'_callback_get_user_id'));
        $crud->callback_column('name',array($this,'_callback_get_user_name'));
        $crud->callback_column('user_group',array($this,'_callback_get_user_group'));

        $crud->columns('id','user_id','name','user_group','msisdn','debit','credit','reference','createdate');

        $crud->order_by('id','desc');
        $crud->limit(200);

        $crud->unset_delete();
        $crud->unset_edit();
        $crud->unset_add();
    

        $output = $crud->render();

        $output->page_title = 'Last 200 Wallet Transactions';

        $this->crud_view($output);
    }

    function update_dob($post_array,$primary_key){
        $dates = explode('-', $post_array['dob']);

        $post_array['DOBDay'] = $dates[2];
        $post_array['DOBMonth'] = $dates[1];
        $post_array['DOBYear'] = $dates[0];

        return $post_array;
    }

    function _callback_add_image($value, $row){
        return '<a href="http://www.spazapp.co.za/images/'.$value.'" target="_blank"><img src="http://www.spazapp.co.za/images/'.$value.'" width="100" /></a>';
    }

    function _callback_order_items($value, $row){
        $count = $this->spazapp_model->get_order_item_count($row->id);
        return '<a href="/management/order_item/'.$row->id.'"target="_blank">'.$count.'</a>';
    }

    function _callback_order_total($value, $row){
        $total = $this->spazapp_model->get_order_total($row->id);
        return "R".$total;
    }

    function track_insert($post_array,$primary_key){
        if($this->session->userdata('table') == 'customer_accounts'){
            $this->financial_model->notify_customer('add_to_customer_account', $post_array);
        }
        $catgory = 'financial';
        $action = 'insert';
        $label = 'User added a new entry to the '.$this->session->userdata('table').' table';
        $value = $primary_key;
        $this->event_model->track_event($catgory, $action, $label, $value);
        $this->session->unset_userdata(array('table'));
    }

    function track_update($post_array,$primary_key){
        $catgory = 'financial';
        $action = 'update';
        $label = 'User updated an entry in the '.$this->session->userdata('table').' table';
        $value = $primary_key;
        $this->event_model->track_event($catgory, $action, $label, $value);
        $this->session->unset_userdata(array('table'));
    }

    function view_email($template='call_report'){
        $data['message'] = array();
        $data['template'] = $template;
        $this->load->view('emails/'.$data['template'], $data['message'], false);

    }

    function set_createdate($post_array){
        $post_array['createdate'] = date("Y-m-d H:i:m");
        return $post_array;
    }

    function customer_sage_export(){
        $data['page_title']="Sage Export";
        $this->show_view("sage_export",$data);
    }

    function sage_export_list($user_type="", $json=false){
        if(!empty($user_type)){
            if(!$json){
                $view="customer_sage_export";
            }
            
            if($user_type=="customers"){
                if($json){
                    $customers['data']=$this->customer_model->get_all_customers_info('','');
                    echo json_encode($customers);
                }
                
            }

            if($user_type=="sparks"){
                if($json){
                    $customers['data']=$this->trader_model->get_all_traders('','');
                    echo json_encode($customers);
                }
            }

            if($user_type=="distributors"){
                if($json){
                    $customers['data']=$this->customer_model->get_distributors();
                    echo json_encode($customers);
                }
            } 

            if($user_type=="suppliers"){
                if($json){
                    $customers['data']=$this->customer_model->get_suppliers();
                    echo json_encode($customers);
                }
                if(!$json){
                    $view="supplier_sage_export";
                }
            }

            if(!$json){
                $data['url']=base_url()."financial/sage_export_list/$user_type/".true;
                $data['page_title']=ucfirst($user_type." Sage Export");
                $this->show_view("$view", $data);
            }
        } 
        
    }

    function journal_sage_export(){
         $data['page_title']="Spazapp Journal Entries Sage Import";
        $this->show_view("journal_sage_export_list",$data);
    }

    function journal_sage_export_list($json=false){
        if($json){
            $transactions['data']=$this->financial_model->get_all_wallets('','', true, '');
            echo json_encode($transactions);
        }
        if(!$json){
            $data['url']=base_url()."financial/journal_sage_export_list/".true;
            $data['page_title']=ucfirst(" Journal Entries Sage Import");
            $this->show_view("journal_sage_export", $data);
        }

   }


}