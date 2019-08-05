<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Ascendis_daily_cron extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        
        $this->load->helper('url');
        $this->load->library('csvimport');
        $this->load->model('event_model');
        $this->load->model('product_model');
        $this->load->model('order_model');
        $this->load->model('comms_model');
        
    }

    function product_import(){

/*        $filename = 'product_import_template.csv';
        $distributor_id = 2; // extra cargo*/

        /*$filename = 'Advance Price List.csv';
        $distributor_id = 12; // advance*/

        /*$filename = 'SPAZPRCE.CSV';
        $distributor_id = 14; // yarona*/

        $filename = 'ascendis_products.csv';
        $distributor_id = 1; // ascendis
        
        
        $file = file_get_contents("./assets/csv/$filename");
        $lines = str_getcsv ($file, ",", '"');
        //$lines = explode("\n", $file);

        $headers = array(
            '﻿stock_code',
            'barcode',
            'name',
            'description',
            'nutritional_info',
            'directions_warnings',
            'shrink_price'
        );

        $newlines = array();
        $count = 0;
        $count2 = 0;
        $tits = 0;
        foreach ($lines as $key => $value) {
            $count++;

            if($tits == 0){
                if($count == 7){

                    $hack = $lines = explode("\n", $value);
                    $price = $hack[0];
                    $value = $price;
                    $stock_code = $hack[1];
                }

                if($count == 1 && $tits != 0){
                    $newlines[$count2][] = $stock_code;
                }
                
                $newlines[$count2][] = $value;
                
                
                if($count == 7){
                    $count2++;
                    $count = 0;
                    $tits++;
                }
            }else{
                if($count == 6){

                    $hack = $lines = explode("\n", $value);
                    $price = $hack[0];
                    $value = $price;
                    $stock_code = $hack[1];
                }

                if($count == 1 && $tits != 0){
                    $newlines[$count2][] = $stock_code;
                }
                
                $newlines[$count2][] = $value;
                
                
                if($count == 6){
                    $count2++;
                    $count = 0;
                    $tits++;
                }
            }
        }

        foreach ($newlines as $key => $value) {
            $product = $value;
            foreach ($product as $prd_key => $prd_item) {
                $product_import[$key][trim(strtolower($headers[$prd_key]))] = trim($prd_item);
            }
        }

        if(!$product_import){
            $this->event_model->private_track_event('', 'daily_cron', 'product_import', 'error', 'An error occurred while running import ERROR: '.$this->csvimport->get_errors() , date("Y-m-d"));
            print_r($this->csvimport->get_errors());
        }else{

            if(count($product_import[1]) < 3){
                $this->event_model->private_track_event('', 'daily_cron', 'product_import', 'error', 'An error occurred while running import ERROR: CSV not formatted correctly.' , date("Y-m-d"));
                die("CSV not formatted correctly.");
            }

            $updated_array = array();
            $failed_array = array();
            $date = date("Y-m-d h:i:s");
            foreach ($product_import as $key => $prod) {

                $product_id = $this->product_model->insert_product($prod);
               
                if($product_id){

                    if($distributor_id == 14){
                        $prod['case_price'] = $prod['shrink_price'];
                        $prod['shrink_price'] = $prod['unit_price'];
                        $prod['unit_price'] = 0.00;
                    }

                    if(!isset($prod['case_price'])){
                        $prod['case_price'] = 0.00;
                    }
                    if(!isset($prod['out_of_stock'])){
                        $prod['out_of_stock'] = 0;
                    }
                    $prod['createdate'] = $date;

                    $this->product_model->update_product_customer_type($product_id);

                    $this->product_model->update_product_pricing($product_id, $distributor_id, $prod);
                    
                    $updated_array[$prod['barcode']] = $prod['name'];
                }else{
                    $failed_array[$prod['barcode']] = $prod['name'];
                }
            }

            if(!empty($updated_array)){
                $this->event_model->private_track_event('', 'daily_cron', 'product_import', 'success', 'Imported successfully ['.json_encode($updated_array).']', date("Y-m-d"));
            }

            if(!empty($failed_array)){
                $this->event_model->private_track_event('', 'daily_cron', 'product_import', 'failed', 'Import failed ['.json_encode($failed_array).']', date("Y-m-d"));
            }

            echo '<pre>';
            echo "updated/added: \n";
            print_r($updated_array);
            echo "failed: \n";
            print_r($failed_array);
            echo '</pre>';
        }
    }
    

    function payment_file(){

        $this->load->model('financial_model');
        $date = date("Y-m-d H:i:s");

/*        if(ENVIRONMENT == 'development'){
            $incoming = "./assets/absa/incoming/";
            $processed = "./assets/absa/processed/";
        }else{*/
            $incoming = "/var/www/admin.spazapp.co.za/absa_docs/netup/NMB00311/incoming/";
            $processed = "/var/www/admin.spazapp.co.za/absa_docs/netup/NMB00311/processed/";
        /*}*/

        $dh  = opendir($incoming);
        while (false !== ($filename = readdir($dh))) {
            if(strlen($filename) > 3){
                $files[] = $filename;
            }
        }
        if(!isset($files)){
            die('Nothing to process');
        }
        sort($files);

        foreach ($files as $key => $file) {

            $xml = file_get_contents($incoming.$file);
            $obj = new SimpleXMLElement($xml);
            $arr = json_decode(json_encode($obj), TRUE);

            $header = array();
            $transactions = array();
            $footer = array();

            if(isset($arr['NOTIF-HEADER']) && is_array($arr['NOTIF-HEADER']) && count($arr['NOTIF-HEADER']) == 6 && isset($arr['NOTIF-TRAILER']) && is_array($arr['NOTIF-TRAILER']) && count($arr['NOTIF-TRAILER']) == 4){

                $header = $arr['NOTIF-HEADER'];
                $footer = $arr['NOTIF-TRAILER'];

                $log['senquence_no'] = trim($header['SEQUENCE-NO']); 
                $log['creation_date'] = trim($header['CREATION-DATE'].$header['CREATION-TIME']); 
                $log['client_id'] = trim($header['CLIENT-ID']); 
                $log['client_acn'] = trim($header['CLIENT-ACN']); 
                $log['sname'] = trim($header['SNAME']); 
                $log['total_cr'] = trim($footer['TOTAL-CR']); 
                $log['total_dt'] = trim($footer['TOTAL-DT']); 
                $log['total_recs'] = trim($footer['TOTAL-RECS']); 
                $log['check_sum'] = trim($footer['CHECK-SUM']); 
                $log['date_added'] = trim($date); 

                $log_id = $this->financial_model->store_absa_log($log);

                if(isset($arr['DETAILS']['TRANSACTION']) && is_array($arr['DETAILS']['TRANSACTION']) && is_numeric($log_id)){
                    
                    if($log['total_recs'] == 1){
                        $transactions[] = $arr['DETAILS']['TRANSACTION'];
                    }else{
                        $transactions = $arr['DETAILS']['TRANSACTION'];
                    }

                    foreach ($transactions as $key => $trans) {

                        $error = false;
                        $message = '';

                        $trans_insert['log_id'] = trim($log_id);
                        $trans_insert['trg_acc'] = trim($trans['TRG-ACC']);
                        $trans_insert['event_no'] = trim($trans['EVENT-NO']);
                        $trans_insert['clref'] = trim($trans['CLREF']);
                        $trans_insert['curr'] = trim($trans['CURR']);
                        $trans_insert['amt'] = trim($trans['AMT']);
                        $trans_insert['acc-bal'] = trim($trans['ACC-BAL']);
                        $trans_insert['tran_type'] = trim($trans['TRAN-TYPE']);
                        $trans_insert['payment_date'] = trim($trans['PDATE'].$trans['PTIME']);
                        $trans_insert['clr_paym_ind'] = trim($trans['CLR-PAYM-IND']);
                        $trans_insert['paym_desc'] = trim($trans['PAYM-DESC']);
                        $trans_insert['check_sum'] = trim($trans['CHECKSUM']);
                        $trans_insert['processed'] = 0;
                        $trans_insert['date_added'] = trim($date);

                        $reference = 'bank-'.$trans_insert['clref'] . '_' . $trans_insert['check_sum'];

                        // check if the cell number matches a customer.
                        $phone_number_only = trim(preg_replace("/[^0-9]/","",$trans_insert['clref'])); //remove everything but numbers
                        if($this->assign_to_account($trans_insert['amt'], $reference, $phone_number_only)){


                            $bankfee = 0;

                            if($trans_insert['paym_desc'] == 'CARDLESS CASH DEP'){
                                $bankfee = 5;
                            }

                            if($trans_insert['paym_desc'] == 'CASH DEP BRANCH'){
                                $bankfee = 20;
                            }

                            if($bankfee > 0){
                                //remove banking fees
                                $this->financial_model->add_wallet_transaction('debit', $bankfee, str_replace('bank-', 'bankfees-', $reference), $phone_number_only);
                                //add banking fees
                                $this->financial_model->add_wallet_transaction('credit', $bankfee, str_replace('bank-', 'bankfees-', $reference), '0999999994');
                            }
                            
                            $message .= '|'.$trans_insert['clref'] .'-'. $trans_insert['amt'];
                            $trans_insert['processed'] = 1;

                        }else if($this->assign_to_ins_policy(trim($trans_insert['clref']))){
                            //check if this is for an insurance policy:
                            $this->load->model('insurance_model');
                            $policy_number = trim($trans_insert['clref']);
                            $policy = $this->insurance_model->get_application_from_policy_no($policy_number);

                            if($policy['premium'] <= $trans_insert['amt']){
                                $paid = $this->insurance_model->allocate_funds_and_comms($policy_number, $policy['sold_by'], $policy['premium']);
                                if($paid){
                                    $this->insurance_model->update_policy_application($policy_number, array('sale_complete' => '1'));
                                    $smsmessage = 'Aspis: Thank you for choosing Aspis. Your policy number is '.$policy_number.'. See policy terms here http://testingurl.com/ad337';
                                    $this->comms_model->send_sms($policy['tel_cell'], $smsmessage);
                                    $message .= '|'.$trans_insert['clref'] .'-'. $trans_insert['amt'];
                                }
                            }else{
                                $this->insurance_model->update_policy_application($policy_number, array('sale_complete' => '44')); // insufficient deposit
                                $smsmessage = 'Aspis: Thank you for your deposit on '.$policy_number.'. Your deposit is insufficient please contact xxxxxx';
                                $this->comms_model->send_sms($policy['tel_cell'], $smsmessage);
                            }

                        }else{
                            $error = true;
                            $message .= '|FAIL_'.$trans_insert['clref'] .'-'. $trans_insert['amt'];
                        }
                        
                        $this->financial_model->store_absa_transaction($trans_insert);
                    }

                    $this->load->model('comms_model');
                    $this->comms_model->send_sms('0827378714', substr($message, 0, 160));

                    if(copy($incoming.$file, $processed.$file)){
                        unlink($incoming.$file);
                    }
                }else{
                    // add a comm here to go to byron and tell him something is broken
                    if(copy($incoming.$file, $processed.'failed_'.$file)){
                        unlink($incoming.$file);
                    }
                }
            }
        }
    }

    function process_absa_payment_queue(){

        $this->load->model('financial_model');
        $payments = $this->financial_model->get_unprocessed_absa_payments();

        foreach ($payments as $key => $payment) {

            $error = false;
            $message = 'problem depisits';

            $reference = $payment['clref'] . '_' . $payment['check_sum'];

            $phone_number_only = trim(preg_replace("/[^0-9]/","",$payment['clref']));
            
            // check if the cell number matches a customer.
            if($this->assign_to_account($payment['amt'], $reference, $phone_number_only)){
                $bankfee = 5;
                if($payment['paym_desc'] == 'CASH DEP BRANCH'){
                    $bankfee = 20;
                }
                //remove banking fees
                $this->financial_model->add_wallet_transaction('debit', $bankfee, str_replace('bank-', 'bankfees-', $reference), $phone_number_only);
                //add banking fees
                $this->financial_model->add_wallet_transaction('credit', $bankfee, str_replace('bank-', 'bankfees-', $reference), '0999999994');
                $payment['processed'] = 1;
                $this->financial_model->mark_absa_payment_as_processed($payment['id']);
            }else{
                $error = true;
                $message .= '_'.$payment['clref'] .'-'. $payment['amt'];
            }
        }

        if($error){
            $this->load->model('comms_model');
            $this->comms_model->send_sms('0827378714', $message);
        }
    }

    function assign_to_account($amount, $reference, $clref){

        $this->load->model('financial_model');
        $this->load->model('customer_model');
        $this->load->model('global_app_model');
        $this->load->model('comms_model');

        //check which app is doing this:
        $user = $this->global_app_model->define_app_by_msisdn($clref);

        if(!$user){
            return false;
        }

        $transaction_id = false;
        $message = 'FUNDS ADDED: R'.$amount.' added to wallet: '.$clref;

        $transaction_id = $this->financial_model->add_wallet_transaction('credit', $amount, $reference, $clref);

        switch ($user['app']) {
            case 'spazapp':
                $message = 'SPAZAPP: R'.$amount.' has been added to your spazapp wallet: '.$clref;
                break;
            
            case 'taptuck':
                $message = 'TAPTUCK: R'.$amount.' has been added to your taptuck wallet: '.$clref;
                break;
        }


        if($transaction_id){
            
            $this->comms_model->send_sms($clref, $message);
            $this->comms_model->push_notification($user['user_id'], $user['app'], $message);

            return true;
        }

        return false;
    }

    function assign_to_ins_policy($clref){

        $this->load->model('insurance_model');

        $policy = $this->insurance_model->get_application_from_policy_no($clref);

        return $policy;
    }

/*    function force_registration(){

        $this->load->model('spazapp_model');
        $this->load->helper('string');

        $pass = random_string('alnum',8);

        $stores = $this->spazapp_model->fetch_50_stores();

        foreach ($stores as $key => $store) {

            $store['cellphone'] = trim($store['cellphone']);

            if(strlen($store['cellphone']) != 9){
                $store['cellphone'] = substr($store['cellphone'],-9, 9);
            }

            $store['cellphone'] = "0".$store['cellphone'];
            $store['cellphone'] = str_replace(' ', '', $store['cellphone']);
                
            if(is_numeric($store['cellphone'])){
                $data = array(
                "first_name"        =>      "na",
                "last_name"         =>      "na",
                "id_number"         =>      "",
                "email"             =>      "",
                "cellphone"         =>      $store['cellphone'],
                "password"          =>      $pass,
                "confirm_password"  =>      $pass,
                "shop_name"         =>      $store['shop_name'],
                "shop_type"         =>      "spaza",
                "long"              =>      $store['longitude'],
                "lat"               =>      $store['latitude']
                );

                print_r($data);
                exit;
                $data_string = json_encode($data);

                $ch = curl_init('http://admin.spazapp.bv/api/basic/register');
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string))
                );

                $result = curl_exec($ch);
                $result = json_decode($result, TRUE);


                if($result['success']){
                    echo '<br/>Successfull <br/>';
                    $this->spazapp_model->mark_store_as_done($store['id'], $result['data']['new_user_id']);
                }else{
                    echo '<br/>Failed <br/>';
                }
            }
        }

        print_r($result);

    }*/

    function update_product_pricing(){

        $distributor_id = 1;

            //select * current products.
            $result = $this->db->query("SELECT id as 'product_id', unit_price, sell_price as 'shrink_price', special, special_start, special_end, special_price  FROM products");
            $products = $result->result_array();
            
            //loop through each one.
            foreach ($products as $product) {
            //insert new hard coded distributor pricing based on current products.
                $case_price = 0.00;
            if(!$this->does_product_price_already_exist($product['product_id'])){
                $this->db->query("INSERT INTO prod_dist_price (distributor_id, product_id, unit_price, shrink_price, case_price) VALUES (?,?,?,?,?)", 
                    array($distributor_id, $product['product_id'], $product['unit_price'], $product['shrink_price'], $case_price));

                //if there is a special create a special for that dist, prod, region.
                if($product['special_start'] != NULL){
                    $this->db->query("INSERT INTO specials (distributor_id, product_id, unit_price, shrink_price, case_price, start_date, end_date) VALUES (?,?,?,?,?,?,?)", 
                    array($distributor_id, $product['product_id'], $product['unit_price'], $product['special_price'], $case_price, $product['special_start'], $product['special_end']));
                }
            }
        }
    }

    function does_product_price_already_exist($product_id){
        $res = $this->db->query("SELECT product_id FROM prod_dist_price WHERE product_id = $product_id");
        if($res->num_rows() >= 1){
            return true;
        }else{
            return false;
        }
    }
    
 function send_daily_sales($distributor_id='2'){
       
    // $distributors = $this->spazapp_model->get_all_distributors();
    // foreach ($distributors as  $row) {
    //     $results = $this->order_model->get_daily_sales($row['id']);
    // }

    $results = $this->order_model->get_daily_sales($distributor_id);
    return $results;
 }

 function test(){
    $message = 'test test test';
    $this->comms_model->send_sms('0827378714', substr($message, 0, 160));
 }

 function push_notification($template){

    $this->load->model('comms_model');
    $comm = $this->comms_model->get_comm($template);
    $comm_id = $comm['id'];

    // $result = $this->comms_model->send_push_notification('eCzOlsYBf2E:APA91bFVPgBk8LHcQybbpTxMQ0m_7IhUy4CpUoCq336EWYdhWo_IbGg3vo4i1HGIdLewgp8BtXZoGOKeYRsU2Y43oTcpJJsxLU_je14UHDmFybX8QrZnOcmJ2jeBMVMc0m6EK_5wv9ol',$comm_id); //byrons phone
   
    $result = $this->comms_model->send_push_notification('efu9RzOPVZk:APA91bGDKxtdR40nbmPENHdyKOGWvrRXrQovsQTIOBPkHN4FePbrxCwVZ3pWsMJUlf-WdzQrFjk5YVXfXOGHgEGIEeVRYVgXoQzNgwh9GpawfdsV0ZYRbizAEdhW1ypJVhdUIoBPHRos',$comm_id,$comm['copy'],$comm['title']); // fnb phone
    $result = $this->comms_model->send_push_notification('eiHgbzXvdbk:APA91bEdsGWklmoXTv0SpQN_dn30bkxu0MzQMCr2Iz-l_e5_jv4ZueebPQtN_ajgyrM4Q0y0C3XZihalruk6IwPhn5r69GFPF3iymoKWDzn9eUYNu_D-VUBS1t_WUS-IHVil-xk7q0St',$comm_id,$comm['copy'],$comm['title']); // byron phone
    

 }


  function test_push_taptuck(){
    $this->load->model('comms_model');
    //$result = $this->comms_model->send_push_notification('eCzOlsYBf2E:APA91bFVPgBk8LHcQybbpTxMQ0m_7IhUy4CpUoCq336EWYdhWo_IbGg3vo4i1HGIdLewgp8BtXZoGOKeYRsU2Y43oTcpJJsxLU_je14UHDmFybX8QrZnOcmJ2jeBMVMc0m6EK_5wv9ol'); //byrons phone
    /*$result = $this->comms_model->send_push_notification('c4U4zPWdwSQ:APA91bEet8fiLXbj2VVp3walMoLzbDPy4iH1V3vKVrTX7jb2e0esO-Q81STo24xZfizM_zoOv7FBSqtR3TACeHvUnZMn0R8T_aHM0ziy-Ra6eIGraaMw2qrbv62AmbcHiGk6rDNsX-uX'); //byrons phone
    print_r($result);*/
    //$result = $this->comms_model->send_push_notification('efu9RzOPVZk:APA91bGDKxtdR40nbmPENHdyKOGWvrRXrQovsQTIOBPkHN4FePbrxCwVZ3pWsMJUlf-WdzQrFjk5YVXfXOGHgEGIEeVRYVgXoQzNgwh9GpawfdsV0ZYRbizAEdhW1ypJVhdUIoBPHRos'); // fnb phone
    //print_r($result);

    $title = 'Pigs Fly';
    $body = 'in the middle of July';
    $result = $this->comms_model->test_taptuck_push('fBylVkx2ctg:APA91bH3ROlkPyFp0sbU_QuAi4zfUEJnHXcQBsOMSjYinM_AaHsaoFOjz1sLkFMU8U2Cw6ve2sCjXIGDDUKlZn2GJFYmRoQvcMzcuFJJBR1MP7RDMkLctjmsiw7BxCK9GwMIzBxIC1DA', $body, $title); //jono phone
    print_r($result);
    $result = $this->comms_model->test_taptuck_push('firyx0YkhvE:APA91bEhv3idkoHbwJ0W6zoRfq7HadfQjbQMUIHTiBgsb5rdkFU426YFaeSNcHoF80sQgiYkxLlC0S8qCq299wEEMmd0Kt5deES7nDK1uxzcOOxcxYm5MUfT-Rkt-_s47M_qkVxGxO6m', $body, $title); //byron
    print_r($result);
 }



function test_ott_voucher(){
    $this->load->model('user_model');
    $this->load->library('Ott_vouchers');
    $value = 5;
    $user_object = $this->user_model->get_general_user(2);
    $user = array(
            "name" => $user_object->name, 
            "id" => $user_object->id
            );
    $cellphone = '0827378714';
    $result = $this->ott_vouchers->purchase($value, $user, $cellphone);
    print_r($result);
}

function test_ott_voucher_status(){
    $this->load->model('user_model');
    $this->load->library('Ott_vouchers');
    $unique_reference = 'SP-2-170815085454';
    $result = $this->ott_vouchers->status($unique_reference);
    print_r($result);
}

function test_ott_voucher_redemption(){
    $this->load->model('user_model');
    $this->load->library('Ott_vouchers');
    $pinCode = '714070142432';
    $user_object = $this->user_model->get_general_user(2);
    $user = array(
            "name" => $user_object->name, 
            "id" => $user_object->id,
            "msisdn" => '27' . substr($user_object->username,-9),
            "cellphone" => '0' . substr($user_object->username,-9)
            );

    $result = $this->ott_vouchers->redeem($pinCode, $user);
    print_r($result);
}

function smartcall_eskom_products(){

    $prods = array(23,47,220);
    $this->load->library('Smartcall');

    foreach ($prods as $prod_id) {
        $result = $this->smartcall->get_product($prod_id);
        print_r($result);
    }
}


function test_smartcall(){

    $this->load->library('Smartcall');
    $result = $this->smartcall->get_networks();
    foreach ($result['networks'] as $key => $value) {
        if($value['description'] == 'Electricity-Eskom'){
            print_r($value);
        }
    }

    $user_object = $this->user_model->get_general_user(2);
    $user = array(
            "name" => $user_object->name, 
            "id" => $user_object->id,
            "msisdn" => '27' . substr($user_object->username,-9),
            "cellphone" => '0' . substr($user_object->username,-9)
            );

    $product_id = 47;
    $amount = 12;

    $result2 = $this->smartcall->purchase($product_id, $user['msisdn'], $user['id'], $amount);
    print_r($result2);
}

function test_randgo(){
    $this->load->library('Randgo');
    $result = $this->randgo->test();

}

function send_comms(){
    $this->load->library('comms_library');
    $this->comms_library->send_queued_comms('','',false);
}

}