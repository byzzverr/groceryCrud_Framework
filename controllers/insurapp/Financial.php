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
        $this->load->model('comms_wallet_model');
        $this->load->model('customer_model');
        $this->load->model('user_model');
        $this->load->model('comms_model');
        $this->load->library('javascript_library');

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
            redirect('/'.$this->app_settings['app_folder'].'admin/permissions');
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
        });

        ';


        $this->show_view('financial_report', $data);
    }

    function comm(){
		
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');
        $data['page_title'] = 'Comm Wallet Breakdown';

        $data['wallets'] = $this->comms_wallet_model->get_all_wallets($data['date_from'], $data['date_to']);
        $data['wallet_total'] = 0;
        $data['platform_wallets'] = array();
        $data['platform_wallet_total'] = 0;
        foreach ($data['wallets'] as $key => $value) {
            if(isset($value['platform'])){
                $data['platform_wallets'][$value['cellphone']] = $value;
                $data['platform_wallets'][$value['cellphone']]['colour'] = "hsl(".rand(0,259).",100%,50%)";
                $data['platform_wallet_total'] += $value['balance'];
            }else{
                $data['wallet_total'] += $value['balance'];
            }
        }

        $data['grand_total'] =  $data['wallet_total'] + $data['platform_wallet_total'];

        $count = 50;
        $limit = 50;

    $data['script'] = '

        $(function() {
            $("#datepicker" ).datepicker( { dateFormat: "yy-mm-dd" });
            $("#datepicker1" ).datepicker( { dateFormat: "yy-mm-dd" });
        });

        ';


        $this->show_view('financial_report', $data);
    }


    function billruns(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_billruns');
        
        $crud->set_subject('billrun');

        $crud->set_relation('master_company','ins_master_companies','name');
        $crud->set_relation('month','months','name',null,'id ASC');
        $crud->set_relation('status','gbl_statuses','name');
        
        $crud->columns('master_company','year','month','start_date','end_date','agencies','branches','applications','status');
        $crud->fields('master_company','year','month','start_date','end_date','createdate','status');

        $crud->field_type('start_date', 'hidden');
        $crud->field_type('end_date', 'hidden');
        $crud->field_type('createdate', 'hidden');
        $crud->field_type('status', 'hidden','1');
              
        $crud->add_action('Populate Billrun', '', '/insurapp/financial/populate_billrun','ui-icon-plus');
        $crud->add_action('Payment Requests', '', '/insurapp/financial/billrun_invoices','ui-icon-plus');
       
        $crud->unset_delete();
        $crud->unset_edit();

        $this->session->set_userdata(array('table' => 'ins_billruns'));

        $crud->callback_before_insert(array($this, 'billrun_populate'));
        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Billruns';

        $this->crud_view($output);
    }

    function billrun_invoices($billrun_id){

        $billrun = $this->insurance_model->get_billrun($billrun_id);
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_billrun_invoices');
        
        $crud->set_subject('billrun');

        $crud->set_relation('billrun','ins_billruns','{month}/{year}');
        $crud->set_relation('branch','ins_branches','name');

        $crud->add_action('View Payment Request', '', '','ui-icon-image',array($this,'_callback_generate_invoice'));
        $crud->add_action('Download Applcations', '', '','ui-icon-image',array($this,'_callback_download_application'));
        $crud->add_action('Mark as Paid', '', '/insurapp/financial/invoice_paid','ui-icon-plus');
                     
        // $crud->add_action('View Invoice', '', '/insurapp/financial/generate_invoice','ui-icon-plus');
        // $crud->add_action('Download Applcations', '', '/insurapp/financial/generate_invoice_applications','ui-icon-plus');
      
       
        $crud->unset_delete();
        $crud->unset_edit();
        $crud->unset_add();

        $crud->where('billrun',$billrun_id);

        $this->session->set_userdata(array('table' => 'ins_billrun_invoices'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = $billrun['name'] .' '.date('F', mktime(0, 0, 0, $billrun['month'], 10)).' '.$billrun['year'].' Billrun Invoices';

        $this->crud_view($output);
    }

    function  _callback_download_application($value, $row){
        return site_url('insurapp/financial/download_applications/').'/'.$this->uri->segment(4).'/'.$row->id."/".$row->branch;
    }
    
    function  _callback_generate_invoice($value, $row){
        return site_url('insurapp/financial/generate_invoice/').'/'.$this->uri->segment(4).'/'.$row->id."/".$row->branch;
    }

    function populate_billrun($billrun_id){
        
        $data['billrun'] = $this->insurance_model->get_billrun($billrun_id);
        $data['invoices'] = $this->insurance_model->get_invoices($billrun_id);
        $data['sales_users'] = $this->insurance_model->get_master_company_sales_agents_id($data['billrun']['master_company']);
        $data['applications'] = $this->insurance_model->get_applications_from_user_group($data['sales_users'],$data['billrun']['start_date'],$data['billrun']['end_date']);
        $data_c = array();

        $data['agencies'] = array();
        $data['branches'] = array();
        $data['products'] = array();
        $data_c['total'] = 0;
        $data_c['applications'] = count($data['applications']);

        foreach ($data['applications'] as $key => $app) {
            $data['agencies'][$app['agency']] = $app['agency'];
            if(isset($data['branches'][$app['branch']])){
                $data['branches'][$app['branch']] += $app['premium'];
            }else{
                $data['branches'][$app['branch']] = $app['premium'];
            }

            $data['products'][$app['ins_prod_id']] = $app['ins_prod_id'];
            $data_c['total'] += $app['premium'];
        }

        $data_c['agencies'] = count($data['agencies']);
        $data_c['branches'] = count($data['branches']);
        $data_c['products'] = count($data['products']);

        if(!$data['invoices']){
            //Update billrun
            $data_c['status'] = 19;
            $this->insurance_model->update_billrun($billrun_id, $data_c);
            //Create Invoices
            foreach ($data['branches'] as $branch_id => $total) {
                $this->insurance_model->insert_invoice($billrun_id, $branch_id, $total);
            }
        }

        echo '<h4>Billrun Populated:</h4>';
        echo '<pre>';

        print_r($data_c);
        echo '</pre>';

        echo '<a href="/insurapp/financial/billruns/">Back</a>';
    }

    function invoice_paid($invoice_id){
        
        $data['invoice'] = $this->insurance_model->get_invoice($invoice_id);
        print_r($data['invoice']);
        exit;

        echo 'Not finshed here. need to do release all of the exact comms for this branch invoice.';
    }

    function generate_invoice($master_company_id, $invoice_id,  $branch_id){
        $data['invoice_id']=$invoice_id;
        $data['invoice_info'] = $this->insurance_model->get_appl_invoice($branch_id, $invoice_id);
        $branch_info = $this->insurance_model->get_branch($branch_id);
        $data['master_company_info'] = $this->insurance_model->get_master_company($master_company_id);
       
        $data['page_title']="Payment Request ID : ".$invoice_id;
        $data['master_company_id']=$master_company_id;
        $data['invoice_id']=$invoice_id;
        $data['branch_id']=$branch_id;

        $this->show_view("application_invoice", $data);
    }

    function resend_invoice($master_company_id, $invoice_id, $branch_id){

        $this->insurance_model->send_invoice($master_company_id, $invoice_id, $branch_id);
       // redirect("/insurapp/financial/generate_invoice/".$master_company_id."/".$invoice_id."/".$branch_id);
    }

    function download_applications($billrun_id, $invoice_id, $branch_id){
        $data['invoice_query'] = $this->insurance_model->get_applications_export_query($invoice_id, $branch_id);
        $this->download_csv($data['invoice_query']);

        redirect("/insurapp/financial/billrun_invoices/".$billrun_id);

    }

    function download_csv($query){
        $this->load->helper('download');
        $filename = $this->event_model->csv_from_query($query);
        $data = file_get_contents("./assets/exports/".$filename); 
        force_download($filename, $data);
    }



function reverse_comms($application_id){
        
        $data['application'] = $this->insurance_model->get_application_from_id($application_id);
        $data['sales_user'] = $this->insurance_model->define_user($data['application']['sold_by']);
        $data['product'] = $this->insurance_model->get_product($data['application']['ins_prod_id'], $data['sales_user']['link']['agency_id']);
        $data['page_title'] = $data['application']['policy_number'].' Application Breakdown';
        $data['transacions'] = $this->comms_wallet_model->search_comms($data['application']['policy_number']);
        $data['total'] = 0;
        $data['debits'] = 0;
        $data['credits'] = 0;
        $categories = array();
        foreach ($data['transacions'] as $key => $value) {

            $data['transacions'][$key]['colour'] = "hsl(".rand(0,359).",100%,50%)";
            
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

        echo 'Not finshed here. need to do reversal of the exact comms.';
    }


function application_comms($application_id){
        
        $data['application'] = $this->insurance_model->get_application_from_id($application_id);
        $data['sales_user'] = $this->insurance_model->define_user($data['application']['sold_by']);
        $data['product'] = $this->insurance_model->get_product($data['application']['ins_prod_id'], $data['sales_user']['link']['agency_id']);
        $data['page_title'] = $data['application']['policy_number'].' Application Breakdown';
        if($data['application']['payment_method'] == 'wallet' || $data['application']['payment_method'] == 'atm'){
            $data['transacions'] = $this->financial_model->search_wallet($data['application']['policy_number']);
        }else{
            $data['transacions'] = $this->comms_wallet_model->search_comms($data['application']['policy_number']);
        }
        $data['total'] = 0;
        $data['debits'] = 0;
        $data['credits'] = 0;
        $categories = array();
        foreach ($data['transacions'] as $key => $value) {

            $data['transacions'][$key]['colour'] = "hsl(".rand(0,359).",100%,50%)";
            
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

        $this->show_view('application_comm_report', $data);
    }

function user_wallet($user_id){
        
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');

        $data['user'] = $this->user_model->get_user($user_id);
        if(!is_object($data['user'])){
            $internal_wallet = $this->financial_model->get_internal_wallet($user_id);
            if($internal_wallet){
                $data['user'] = (object) array();
                $data['user']->username = $internal_wallet['id'];
                $data['user']->name = $internal_wallet['name'];
            }
        }
        $data['page_title'] = $data['user']->name.' Wallet Breakdown';
        $data['transacions'] = $this->comms_wallet_model->get_wallet_transactions($data['user']->username,$data['date_from'], $data['date_to']);
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

    function your_wallet(){
        $user_id=$this->aauth->get_user()->id;
      
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');

        $data['user'] = $this->user_model->get_user($user_id);
        if(!is_object($data['user'])){
            $internal_wallet = $this->financial_model->get_internal_wallet($user_id);
            if($internal_wallet){
                $data['user'] = (object) array();
                $data['user']->username = $internal_wallet['id'];
                $data['user']->name = $internal_wallet['name'];
            }
        }
        $data['page_title'] = $data['user']->name.' Wallet Breakdown';
        $data['transacions'] = $this->comms_wallet_model->get_wallet_transactions($data['user']->username,$data['date_from'], $data['date_to']);
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

        function user_wallet_comm($user_id){
        
        $data['date_from'] = $this->session->userdata('dashboard_date_from');
        $data['date_to'] = $this->session->userdata('dashboard_date_to');

        $data['user'] = $this->user_model->get_user($user_id);
        $data['page_title'] = $data['user']->name.' Wallet Breakdown';
        $data['transacions'] = $this->financial_model->get_wallet_transactions($data['user']->username,$data['date_from'], $data['date_to']);
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

        $data['wallets']= $this->financial_model->get_all_wallets();
        $this->show_view('reports/wallets_report', $data);
    }

    function wallets_comm(){
        
        $data['page_title'] = 'Comm Wallets';

        $data['wallets']= $this->comms_wallet_model->get_all_wallets();
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

     function your_wallet_transactions(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('wallet_transactions');
        $crud->set_subject('Wallet Transactions');

        $msisdn=$this->aauth->get_user()->username;
        $crud->where('msisdn',$msisdn);   

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

    function wallet_transactions_comm(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('comm_wallet_transactions');
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

    function billrun_populate($post_array){

        $number = cal_days_in_month(CAL_GREGORIAN, $post_array['month'], $post_array['year']);
        if($post_array['month'] >= 10){
            $month = $post_array['month'];
        }else{
            $month = "0".$post_array['month'];
        }
        $post_array['start_date'] = $post_array['year'].'-'.$month.'-01';
        $post_array['end_date'] = $post_array['year'].'-'.$month.'-'.$number;
        $post_array['status'] = 1;
        $post_array['createdate'] = date("Y-m-d H:i:s");

        return $post_array;
        
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

    function sales_comm_report(){
        $dataset=false;
        $comms=$this->insurance_model->get_sales_comms();
        foreach ($comms as $key => $value) {
        if(empty($value['sales_agent'])){
            $user=$this->financial_model->get_internal_wallet($value['msisdn']);
         }else{
            $user=array('name' => $value['sales_agent']);
         }

         $dataset.='dataSet.push(["'.$value['msisdn'].'",
                                "'.$user['name'].'",
                                "'.$value['debit'].'",
                                "'.$value['credit'].'",
                                "'.$value['policy_number'].'",
                                "'.$value['premium'].'",
                                "'.$value['product'].'",
                                "'.$value['createdate'].'"
                                ]);';
        }

        $columns="{title:'MSISDN'},
        {title:'Name'},
        {title:'Debit'},
        {title:'Credit'},
        {title:'Policy Number'},
        {title:'Premium'},
        {title:'Product'},
        {title:'Createdate'}";

        $data['script']=$this->javascript_library->data_table_script($dataset, $columns, 7);
        $data['page_title']="Sales Comm Report";
        $this->show_view("comm_report", $data);
    }


}