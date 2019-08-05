<?php

class Insurance_model extends CI_Model { 

    public function __construct(){
        
        parent::__construct();

        $this->load->model('financial_model');
        $this->load->model('user_model');
        $this->load->model('comms_wallet_model');
        $this->load->model('comms_model');
        
        $this->insurapp_db = $this->load->database('insurapp', TRUE);
    }

    function get_funeral_insurance(){

      //this will return only funeral insurance.
      $query = $this->insurapp_db->query("SELECT * FROM `ins_m_funeral` WHERE enabled = 'Y'");
      $result = $query->result_array();
      return $result;
    }

    
    function get_insurance_id($identity_number){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_applications` WHERE `id` ='$identity_number' or `passport_number` = '$identity_number'");
        return $result = $query->result();
     }
        
    function get_insurance_by_policy_number($policy_number){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_applications` WHERE policy_number ='$policy_number'");
        return $result = $query->result();
    }

    function get_ins_dependent_by_policy_number($policy_number){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_m_app_dependants` WHERE policy_number ='$policy_number'");
        return $result = $query->result();
    }
        
    function update_purchase($info){
        $this->insurapp_db->query("UPDATE `voucher_purchase_log` SET orderno = ? WHERE id = ?", array($info->orderno, $info->refno));
    }

    function update_application_status($policy_number, $status){
        $this->insurapp_db->query("UPDATE `ins_applications` SET sale_complete = ? WHERE policy_number = ?", array($status, $policy_number));
    }

    function get_all_insurance_app(){
          $query = $this->insurapp_db->query("SELECT a.id, a.name, a.surname, a.passport_number, a.tel_cell, a.email,
          a.picture, a.policy_number, p.type, p.id as product_id FROM `ins_applications` as a JOIN ins_m_funeral as p ON a.ins_prod_id = p.id  WHERE 1");
          $result = $query->result_array();
          return $result;
    } 

    function get_all_info_csv(){
          $query = "SELECT * FROM `ins_applications` WHERE 1";
          return $query;
    }

    function policy_wording_validation($id, $policy_number){

      $sql = "SELECT * FROM `ins_applications` WHERE policy_wording_id = '$id' && policy_number = '$policy_number'";
      $query = $this->insurapp_db->query($sql);

        if($query->num_rows() == 1){
            return false;
        }

      $sql = "SELECT * FROM `ins_used_policy_wording_id` WHERE policy_wording_id = '$id'";
      $query = $this->insurapp_db->query($sql);
        if($query->num_rows() == 0){
            return false;
        }
        return true;
    }

    function get_dependants_csv(){
        $query = "SELECT * FROM `ins_m_app_dependants` WHERE 1";
        return $query;
    }

    function agency_product_credit($agency_id, $product_id){
        $query = $this->insurapp_db->query("SELECT credit FROM `ins_agen_prod_link` WHERE agency_id = $agency_id AND product_id = $product_id");
        return $query->row_array();
    }


    function valid_unique($data){
        
        //here is where you need to make sure the id and cell are unique and being
      
       $this->insurapp_db->select("*");
        $this->insurapp_db->from("ins_applications");
        
        if(!empty($data['id'])){
        $this->insurapp_db->where("id", $data['id']);
        }
        
        if(!empty($data['tel_cell'])){
        $this->insurapp_db->or_where("tel_cell", $data['tel_cell']);
        } 
        
        if(!empty($data['passport_number'])){
        $this->insurapp_db->or_where("passport_number", $data['passport_number']);
        } 
       
        
        $query= $this->insurapp_db->get();
  
        $result = $query->row_array();
       
        if(!empty($result["policy_number"])){
            return $result['policy_number'];
        }else{
            return false;
        }       
    }

    function update_policy_application($policy_number, $data){

        if($this->get_application_from_policy_no($policy_number)){
                    //if data exists
            if(isset($data['product_data'])){
                //add it seperately
                $update = $this->allocate_policy_data($policy_number, $data['product_data']);
                // then remove it so it doesnt interfere with the next bit.
                unset($data['product_data']);
            }

            $data['expiry_date'] = $this->calculate_expiry($policy_number);
            unset($data['token']);

            if(isset($data['product_id'])){
                $data['ins_prod_id'] = $data['product_id'];
            }

            unset($data['product_id']);

            $update = $this->strip_db_rejects('ins_applications', $data);

            if(isset($data['tel_cell']) && strlen($data['tel_cell']) < 10){
                $data['tel_cell'] = "0".$data['tel_cell'];
            }

            $this->insurapp_db->where('policy_number', $policy_number);
            if($this->insurapp_db->update('ins_applications', $update)){
                return true;
            }
            return false;
        }else{
            return false;
        }
    }

    function allocate_policy_data($policy_number, $data){

        $date = date("Y-m-d H:i:s");
        foreach ($data as $key => $value) {
            if($this->does_policy_data_exist($policy_number, $key)){
                $this->insurapp_db->where('policy_number', $policy_number);
                $this->insurapp_db->where('name', $key);
                $update = array('value' => $value, 'createdate' => $date);
                $this->insurapp_db->update('ins_application_data', $update);
            }else{
                $insert = array(
                        'policy_number' => $policy_number,
                        'name' => $key,
                        'value' => $value,
                        'createdate' => $date
                        );
                $this->insurapp_db->insert('ins_application_data', $insert);
            }
        }
    }

    function does_policy_data_exist($policy_number, $name){
        $query = $this->insurapp_db->query("SELECT id FROM `ins_application_data` WHERE policy_number = '$policy_number' AND name = '$name'");
        if($query->num_rows() == 0){
            return false;
        }
        return true;
    }

    function define_identifier($identifier){

        $return = array();
        $identifier = trim($identifier);
        if(validateIdNumber($identifier)){
            $return['sa_id'] = $identifier;
        }else{
            $return['tel_cell'] = $identifier;
        }
        return $return;
    }

    function get_policy_number($product_id, $identifier, $user_id){
        
        $date = date("Y-m-d H:i:s");
        $data = $this->define_identifier($identifier);

        $incomplete_application = $this->get_incomplete_application($product_id, $data);
        if($incomplete_application){
            return $incomplete_application;
        }else{
            $data['ins_prod_id'] = $product_id;
            $data['application_date'] = $date;
            $data['sold_by'] = $user_id;
            return $this->insert_policy_application($data);
        }
    }

    function calculate_expiry($policy_number){

        //this needs work. go fetch product decide on the type. if funeral it is monthly etc.
        //if life or credit life. then based on term.

        $policy = $this->get_entire_policy($policy_number);

        $application_date = date("Y-m-d");
        if(isset($policy['application_date']) && strlen($policy['application_date']) >= 10){
            $application_date = $policy['application_date'];
        }

        $months = 1;

        foreach ($policy['data'] as $key => $value) {
            if(isset($value['name']) && $value['name'] == 'loan_term'){
                $months = intval($value['value']);
            }
        }

        if(!is_int($months)){
            $months = 1;
        }

        $expiry_date = date("Y-m-d", strtotime($application_date . " +".$months." months"));

        return $expiry_date;
    }

    function add_policy_wording_id($policy_wording_id){

            $this->insurapp_db->insert('ins_used_policy_wording_id', array(
        'policy_wording_id' => $policy_wording_id, 
        'createdate' => date("Y-m-d H:i:s")
        ));

        }

    function insert_policy_application($data){
        if($this->insurapp_db->insert('ins_applications', $data)){
            $policy_number = $this->insert_policy_number($this->insurapp_db->insert_id(), $data);
            return $policy_number;
        }
        return false;
    }

    function insert_policy_number($id, $data){
        $this->insurapp_db->where('id', $id);
        $product = $this->get_product($data['ins_prod_id']);
        $update = array();
        $update['policy_number'] = 'IA'.$product['code'].$id;
        if($this->insurapp_db->update('ins_applications', $update)){
            return $update['policy_number'];
        }
        return false;
    }

    function key_search_for_application($key, $value){

        $query = $this->insurapp_db->query("SELECT * FROM `ins_applications` WHERE 
            `$key` ='$value' 
            ");
        return $result = $query->row_array(); 
    }

    function passport_search_for_application($passport, $dob){

        $query = $this->insurapp_db->query("SELECT * FROM `ins_applications` WHERE 
            `passport_number` = '$passport' AND
            `date_of_birth` = '$dob'
            ");
        return $result = $query->row_array(); 
    }

    function fetch_expiry($policy_number){

        $policy = $this->get_application_from_policy_no($policy_number);
        if($policy){

            $today = date("Y-m-d");
            if($policy['expiry_date'] >= $today){
                $expired = false;
                $message =  'Your policy exires on: '.$policy['expiry_date'];
            }else{
                $expired = true;
                $message =  'Your policy exired on: '.$policy['expiry_date'];
            }

        }else{
            $expired = true;
            $message = 'Policy ' . $policy_number . ' does not exist.';
        }

        return array('expired' => $expired, 'message' => $message);

    }
    
    function get_app_insurance_by_option($identity_number,$passport_number,$date_of_birth, $cellphone_number){
    
       $query = $this->insurapp_db->query("SELECT * 
       FROM `ins_applications` 
       WHERE `id` ='$identity_number' or `passport_number` = '$passport_number' or `date_of_birth` = '$date_of_birth' or `tel_cell` = '$cellphone_number'"); 
       return $result = $query->row_array(); 
        
    }
    
    function get_application_policy_number($id){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_applications` WHERE id ='$id'");
        return $result = $query->row_array();
    }

    function get_application_from_policy_no($policy_number){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_applications` WHERE policy_number =?", array($policy_number));
        return $result = $query->row_array();
    }

    function get_entire_policy($policy_number){
        $query = $this->insurapp_db->query("SELECT a.*, s.name as 'status_name' FROM `ins_applications` a, ins_statuses s WHERE a.sale_complete = s.code AND a.policy_number ='$policy_number'");
        $policy = $query->row_array();
        $policy['product'] = $this->get_detailed_product($policy['ins_prod_id'],true);
       
        $policy['dependants'] = $this->get_dependants($policy_number);
        $policy['data'] = $this->get_application_data($policy_number);
        return $policy;
    }

    function does_policy_data_match($cellphone, $policy_number, $product_id){
        $query = $this->insurapp_db->query("SELECT id FROM `ins_applications` WHERE tel_cell = ? AND policy_number = ? AND ins_prod_id = ?", array($cellphone, $policy_number, $product_id));

        if($query->row_array()){
            return $this->get_entire_policy($policy_number);
        }else{
            return $query->row_array();
        }
    }

    function get_app_dependent_policy_number($policy_number){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_m_app_dependants` WHERE policy_number ='$policy_number'");
        return $result = $query->result_array();
    }

    function get_application_data($policy_number){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_application_data` WHERE policy_number = '$policy_number'");
        return $result = $query->result_array();
    }

    function get_ins_products(){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_m_funeral` WHERE 1");
        return $result = $query->result();
    }

    function get_insurance_product_id($product_id){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_m_funeral` WHERE `id`=$product_id");
        return $query->row_array();
    }
     function get_ins_relation_types(){
         
        $query = $this->insurapp_db->query("SELECT * FROM `ins_m_dependent_types` WHERE 1");
        return $result = $query->result();
    }
    function remove_dependent($policy_number,$dependent_number){
        $query = $this->insurapp_db->query("DELETE  FROM `ins_m_app_dependants` WHERE `policy_number`='$policy_number' AND `dependent_number`='$dependent_number'");
    }  

    function add_signature($policy_number,$signature){
        $query = $this->insurapp_db->query("UPDATE `ins_applications` SET `signature`='$signature' WHERE `policy_number`='$policy_number'");
    }

    function add_picture($policy_number,$picture){
        $query = $this->insurapp_db->query("UPDATE `ins_applications` SET `picture`='$picture' WHERE `policy_number`='$policy_number'");
    }
    
    function get_all_insurance_sales($user_type){
        $where_user_type='';
        $user = $this->aauth->get_user();
        $user_link_id = $user->user_link_id;
        $user_id = $user->id;
         switch ($user_type) {
            
            case 'master_company':
               
                $where_user_type=" AND p.master_company='$user_link_id'";
                break;

            case 'agency':
               
                $where_user_type=" AND a.user_link_id='$user_link_id'";
                break;

            case 'agent':
               
                $where_user_type=" AND app.sold_by='$user_id'";
                break;

             case 'admin':
               
                $where_user_type=" ";
                break;

          default:
                $where_user_type = '';
                break;
        }

        $date_from = $this->session->userdata('dashboard_date_from');
        $date_to = $this->session->userdata('dashboard_date_to');
        
      
        $where_date     =   "";
        if(!empty($date_from)){
            $where_date="AND app.application_date >= '$date_from' AND app.application_date <= '$date_to'";
        }
        
      
        $query = $this->insurapp_db->query("SELECT 
                                app.policy_number,
                                app.sa_id as id, 
                                app.passport_number, 
                                app.payment_reference_no,
                                a.name, 
                                p.type, 
                                p.name as product,
                                app.premium, 
                                app.application_date, 
                                app.expiry_date,
                                app.sa_id,
                                a.name as sold_by
                                FROM `ins_applications` as app 
                                JOIN ins_products as p  ON app.ins_prod_id = p.id
                                JOIN aauth_users as a  ON app.sold_by = a.id
                                WHERE 1 $where_user_type
                                $where_date  ");      
       
        return $result = $query->result(); 
    }
      
    function get_all_insurance_sales_stats($id, $user_type, $date_from='', $date_to='', $branch_id){
        if(!empty($date_from)){
            $where_date="and app.application_date >= '$date_from' and app.application_date <= '$date_to' ";
        }else{
            $where_date="";
        }

        if(!empty($branch_id)){
            $where_branch=" and b.id=$branch_id";
        }else{
            $where_branch="";
        }

        $where_sold_by='';
        switch ($user_type) {
            case 'branch':
                $where_sold_by = " and b.id = $id ";
                break;
            case 'agency':
                $where_sold_by = " and b.agency = $id ";
                break;
            case 'master_company':
                $where_sold_by = " and b.agency in ($id) ";
                break;
            default:
                $where_sold_by = '';
                break;
        }

        $query = $this->insurapp_db->query("SELECT 
                                count(app.sold_by) as number_of_sales,
                                SUM(app.premium) as premium, 
                                b.name as sales_person
                                FROM `ins_applications` as app 
                                JOIN ins_products as p  ON app.ins_prod_id = p.id
                                JOIN aauth_users as a  ON app.sold_by = a.id
                                JOIN ins_branches as b ON a.user_link_id = b.id
                                JOIN ins_agencies as ag ON b.agency = ag.id
                                JOIN ins_statuses as s ON s.code = app.sale_complete
                                WHERE a.default_app in('insurapp','aspis', 'royal') 
                                $where_date $where_sold_by $where_branch
                                GROUP BY b.id ORDER BY app.application_date DESC LIMIT 50");              
        return $result = $query->result(); 

    }

     function get_products_sale_stats($user_type){
        $where_user_type='';
        $user_link_id=$this->aauth->get_user()->user_link_id;
        $user_id=$this->aauth->get_user()->id;
        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');
        $where_date     =   "";
        switch ($user_type) {
            case 'master_company':
                $where_user_type=" AND p.master_company='$user_link_id'";
                break;
            case 'agency':
                $where_user_type=" AND a.user_link_id='$user_link_id'";
                break;
            case 'agent':
                $where_user_type=" AND app.sold_by='$user_id'";
                break;
            case 'admin':   
                $where_user_type=" ";
                break;
          default:
                $where_user_type = '';
                break;
        }
       
        if(!empty($date_from)){
            $where_date ="AND app.application_date >= '".$date_from."' AND app.application_date <= '".$date_to."'";
        }
        $query = $this->insurapp_db->query("SELECT 
                                count(app.ins_prod_id) as number_of_sales,
                                SUM(app.premium) as premium, 
                                a.name as sales_person,
                                p.name
                                FROM `ins_applications` as app 
                                JOIN ins_products as p  ON app.ins_prod_id = p.id
                                JOIN aauth_users as a  ON app.sold_by = a.id
                                WHERE 1
                                $where_user_type
                                GROUP BY app.ins_prod_id ORDER BY 
                                app.application_date DESC LIMIT 50");      
                
        return $result = $query->result(); 
    }
    
    
    function get_funeral_product_stats($from, $to, $death_certificate){
        
        $where_date     =   "";
        $where_certf    =   "";
  
        if(!empty($from)){
            $where_date =" AND app.application_date >= '$from' AND app.application_date <= '$to'";
        }
        
        if(!empty($death_certificate)){ 
            $where_certf = " AND app.death_certificate != 'null'";
        }
        
        $query = $this->insurapp_db->query("SELECT 

        count(app.ins_prod_id) as product_count, 
        f.type as product_type
        FROM `ins_applications` as app 
        JOIN ins_m_funeral as f  ON app.ins_prod_id = f.id
        JOIN aauth_users as a  ON app.sold_by = a.id
        WHERE app.sold_by != 'null' $where_date $where_certf GROUP BY app.sold_by");

        return $result = $query->result(); 

    }

    function add_death_certificate($policy_number,$death_certificate){
        
        $query = $this->insurapp_db->query("UPDATE `ins_applications` SET `death_certificate`='$death_certificate' WHERE `policy_number`='$policy_number'");
    }


    /*  HERE IS THE NEW STUFF BYRON WROTE */

    function get_dependants($policy_number){
        $return = array();
        $query = $this->insurapp_db->query("SELECT p.dependants FROM `ins_applications` a, ins_products p WHERE a.ins_prod_id = p.id AND a.policy_number = '$policy_number'");
        $app = $query->row_array();

        $return['total_slots'] = $app['dependants'];

        $query = $this->insurapp_db->query("SELECT * FROM `ins_app_dependants` WHERE `policy_number` = '$policy_number'");
        $return['dependants'] = $query->result_array();

        $return['available_slots'] = $return['total_slots'] - $query->num_rows();

        return $return;
    }

    function add_dependant($policy_number, $data){

        $application = $this->get_application_from_policy_no($policy_number);
        $product = $this->get_product_simple($application['ins_prod_id']);

        $result = array();

        $required_array = array('first_name','last_name','type','dob');


        foreach ($required_array as $value) {
            if(!array_key_exists($value, $data)){
                $result['error'] = 'Please supply dependant ' . $value;
                return $result;
            }
        }

        $dependants = $this->get_dependants($policy_number);

        if($dependants['available_slots'] <= 0){
            $result['error'] = 'All dependants have already been added to this policy';
            return $result;
        }

        if($product['dependants'] == 1 && $product['spouse'] == 1 && $data['type'] != 'spouse'){
            $result['error'] = 'You can only add a spouse as a dependant to this policy.';
            return $result;
        }

        if($product['spouse'] == 0 && $data['type'] == 'spouse'){
            $result['error'] = 'You cannot add a spouse to this policy.';
            return $result;
        }

        if(strpos('Family 1+5', $product['name']) === false && $data['type'] == 'relative'){
            $result['error'] = 'You cannot add a relative to this policy.';
            return $result;
        }

        $age = $this->age_from_dob($data['dob']);

        if($data['type'] == 'child' && $age > 21){
            $result['error'] = 'Child dependants cannot be over 21.';
            return $result;
        }

        $spouse_max = 64;

        if($product['premium_over_65'] > 0){
            $spouse_max = 74;
        }

        if($data['type'] == 'spouse'){

            if($age < 18){
                $result['error'] = 'Spouse dependants cannot be under 18 years old.';
                return $result;
            }

            if($age > $spouse_max){
                $result['error'] = 'Spouse cannot be over 64 years old.';
                return $result;
            }
        }

        if($data['type'] == 'relative'){

            if($age < 18){
                $result['error'] = 'Relative dependants cannot be under 18 years old.';
                return $result;
            }

            if($age > 64){
                $result['error'] = 'Relative cannot be over 64 years old.';
                return $result;
            }
        }

        $spouse = 0;

        if(count($dependants['dependants']) > 0){
            foreach ($dependants['dependants'] as $key => $dependant) {

                if($dependant['type'] == 'spouse'){
                    $spouse++;
                }

                if($spouse > 0 && $data['type'] == 'spouse'){
                    $result['error'] = 'You can only have one spouse.';
                    return $result;
                }
                if($data['dob'] == $dependant['dob'] && $data['first_name'] == $dependant['first_name']){
                    $result['error'] = 'You cannot load the same dependant twice';
                    return $result;
                }
            }
        }

        if(!isset($result['error'])){

        $data['policy_number'] = $policy_number;
        $result = $this->insert_dependant($data);

        }

        return $result;

    }

    function insert_dependant($data){
        $data['createdate'] = date("Y-m-d H:i:s");
        $data = $this->strip_db_rejects('ins_app_dependants', $data);
        if($this->insurapp_db->insert('ins_app_dependants', $data)){
            return true;
        }
        return array('error' => 'An error occurred while adding this dependant');
    }

    function remove_dependant($policy_number, $dependant_id){


        $dependant_id = intval($dependant_id);

        if(is_int($dependant_id)){
            $sql = "DELETE FROM ins_app_dependants WHERE policy_number = '$policy_number' AND id = $dependant_id";
            if($this->insurapp_db->query($sql)){
                return true;
            }
            return array("error" => "An error occurred, no dependant could be removed.");
        }else{
            return array('error' => 'Invalid dependant_id supplied.');
        }
    }

    function get_product($product_id, $agency_id=false){
      
        $query = $this->insurapp_db->query("SELECT p.*, i.name as 'insurer_name' FROM `ins_products` p, `ins_entities` i WHERE p.insurer = i.id AND p.id = $product_id");
        $product = $query->row_array();
        $query = $this->insurapp_db->query("SELECT * FROM `ins_product_split` WHERE `product_id` = $product_id");
        $product['split'] = $query->row_array();
        if($agency_id){
            $query = $this->insurapp_db->query("SELECT * FROM `ins_product_sales_split` WHERE `product_id` = $product_id AND `agency_id` = $agency_id");
            $product['sales_split'] = $query->row_array();
            $query = $this->insurapp_db->query("SELECT credit FROM `ins_agen_prod_link` WHERE `product_id` = $product_id AND `agency_id` = $agency_id");
            $product['credit'] = $query->row_array()['credit'];
        }

        return $product;
    }

    function get_master_company_sales_agents_id($master_company_id){
        $query = $this->insurapp_db->query("SELECT u.id as 'user_id' FROM aauth_users u, ins_branches b, ins_agencies a, ins_master_companies m WHERE u.user_link_id = b.id AND a.id = b.agency AND m.id = a.master_company AND m.id = $master_company_id");
        $result = $query->result_array();
        $return = array();
        foreach ($result as $key => $value) {
            $return[] = $value['user_id'];
        }
        return $return;
    }

    function get_branch_sales_agents_id($branch_id){
        $query = $this->insurapp_db->query("SELECT u.id as 'user_id' FROM aauth_users u, ins_branches b WHERE u.user_link_id = b.id AND b.id = $branch_id AND u.default_usergroup in (26,27,28,29,30)");
        $result = $query->result_array();
        $return = array();
        foreach ($result as $key => $value) {
            $return[] = $value['user_id'];
        }
        return $return;
    }

    function get_applications_from_user_group($user_array, $date_from, $date_to){
        $ids = implode(',', $user_array);
        $query = $this->insurapp_db->query("SELECT u.id as 'sales_agent', b.id as 'branch', a.id as 'agency', ap.policy_number, ap.ins_prod_id, ap.application_date, ap.premium FROM ins_applications ap, aauth_users u, ins_branches b, ins_agencies a, ins_master_companies m WHERE ap.sold_by = u.id AND u.user_link_id = b.id AND a.id = b.agency AND m.id = a.master_company AND u.id IN ($ids) AND ap.sale_complete = 1 AND ap.application_date >= '$date_from' AND ap.application_date <= '$date_to'");
        $result = $query->result_array();
        return $result;
    }

    function get_invoice($invoice_id){
        $query = $this->insurapp_db->query("SELECT i.branch as branch_id, i.id, i.total, b.name, b.name as agency_name, b.agency, b.bank, b.bank_accno, b.bank_branch, b.bank_bcode, br.master_company, br.start_date, br.end_date 
            FROM ins_billrun_invoices i, ins_branches b, ins_billruns br
            WHERE 
            i.branch = b.id 
            AND i.billrun = br.id
            AND i.id = $invoice_id"
            );
        $result = $query->row_array();
        return $result;
    }

    function get_billrun_users($billrun_id){
        $query = $this->insurapp_db->query("SELECT b.id as branch_id, a.username, a.id, a.user_link_id, a.cellphone, a.name
            FROM ins_billrun_invoices i, 
            ins_branches b,
            ins_agencies ag,
            ins_billruns br, 
            aauth_users a
            WHERE 
            i.branch = b.id 
            AND i.billrun = br.id
            AND b.agency = ag.id
            AND a.user_link_id = b.agency
            AND i.billrun = $billrun_id"
            );
        $result = $query->result_array();
        return $result;
    }

    function get_invoices($billrun_id){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_billrun_invoices` WHERE `billrun` = $billrun_id");
        $result = $query->result_array();
        return $result;
    }
    function get_billrun($id){
        $query = $this->insurapp_db->query("SELECT b.*, mc.name FROM `ins_billruns` b, ins_master_companies mc WHERE b.master_company = mc.id AND b.id = $id");
        $result = $query->row_array();
        return $result;
    }

    function insert_invoice($billrun_id, $branch_id, $total){
        $this->insurapp_db->query("INSERT INTO ins_billrun_invoices (billrun, branch, total, createdate) VALUES (?, ?, ?, NOW())", array($billrun_id, $branch_id, $total));
    }

    function update_billrun($billrun_id, $data){
        $data = $this->strip_db_rejects('ins_billruns',$data);
        $this->insurapp_db->where('id', $billrun_id);
        $this->insurapp_db->update('ins_billruns', $data);
    }

    function get_master_company($id){
        $query = $this->insurapp_db->query("SELECT ms.id, ms.*, a.email, a.cellphone FROM `ins_master_companies` as ms LEFT JOIN aauth_users as a ON a.user_link_id=ms.id WHERE ms.id = $id");
        $result = $query->row_array();
        return $result;
    }

    function get_agency($id){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_agencies` WHERE `id` = $id");
        $result = $query->row_array();
        return $result;
    }    


    function get_branch($branch_id){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_branches` WHERE id = $branch_id");
        $result = $query->row_array();
        return $result;
    } 
    function get_branch_user($branch_id){
        $query = $this->insurapp_db->query("SELECT * FROM aauth_users WHERE user_link_id = $branch_id and default_app in('insurapp','aspis', 'royal') ");
        $result = $query->row_array();
        return $result;
    }

    function get_agency_staff($branch_id, $agency_id=''){
        $query = $this->insurapp_db->query("SELECT * FROM aauth_users WHERE user_link_id IN($branch_id, $agency_id) and default_app in('insurapp','aspis', 'royal') ");
        $result = $query->result_array();
        return $result;
    }

    function get_branches(){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_branches`");
        $result = $query->result_array();
        return $result;
    }
    function get_insurer($insurer_id){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_entities` WHERE id = $insurer_id");
        $result = $query->row_array();
        return $result;
    }

    function get_insurers(){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_entities` WHERE type=1");
        $result = $query->result_array();
        return $result;
    }

    function get_entities(){
        $query = $this->insurapp_db->query("SELECT a.*, b.name as 'type_name' FROM `ins_entities` a, ins_entity_types b WHERE a.type = b.id");
        $result = $query->result_array();
        return $result;
    }

    function get_entity($entity_id){
        $query = $this->insurapp_db->query("SELECT a.*, b.name as 'type_name' FROM `ins_entities` a, ins_entity_types b WHERE a.type = b.id AND a.id = $entity_id");
        $result = $query->row_array();
        return $result;
    }

    function get_agency_products($agency_id){

        $query = $this->insurapp_db->query("SELECT product_id FROM `ins_agen_prod_link` WHERE agency_id = $agency_id");
        $result = $query->row_array();
        foreach ($result as $key => $value) {
            $prods += ','.$value['product_id'];
        }
        return $prods;
    }

    function update_premium_split($product_id, $data){
        $this->insurapp_db->where('product_id', $product_id);
        if($this->insurapp_db->update('ins_product_split', $data)){
            return true;
        }
        return false;
    }


    function insert_premium_split($product_id, $data){
        $data['product_id'] = $product_id;
        $this->insurapp_db->insert('ins_product_split', $data);
    }

    function update_sales_split($agency_id, $product_id, $data){
        $this->insurapp_db->where('agency_id', $agency_id);
        $this->insurapp_db->where('product_id', $product_id);
        if($this->insurapp_db->update('ins_product_sales_split', $data)){
            return true;
        }
        return false;
    }

    /*

id,name,parent_id
23,InsMasterCompany,0
24,InsAgency,0
25,InsBranch,0
26,InsTellerA,0
27,InsSalesAgentA,0
28,InsTellerB,0
29,InsSalesAgentB,0
30,InsSalesAgentC,0


    */

    function define_user($user_id){

        $query = $this->db->query("SELECT u.id, u.user_link_id, u.parent_id, u.email, u.cellphone, u.username, u.name, u.default_usergroup, u.default_app, g.name as 'group' FROM `aauth_users` u, aauth_groups g WHERE u.default_usergroup = g.id AND u.id = $user_id");
        $user = $query->row_array();

        //usergroup tells us his priviladges
        //user_link tells us master_company, agency or branch

        switch ($user['default_usergroup']) {
            case 8:
            case 19:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.name = 'Spazapp'");
                $user['default_usergroup'] = 26;
                $user['link'] = $query->row_array();
                break;
            case 23:
                $query = $this->insurapp_db->query("SELECT id as 'master_company_id', name as 'master_company' FROM `ins_master_companies` WHERE id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                break;
            case 24:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name FROM ins_master_companies m, ins_agencies a WHERE m.id = a.master_company AND a.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                break;
            case 25:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                break;
            case 26:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                break;
            case 27:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                $user['parents'] = array(1 => $user['parent_id']);
                break;
            case 28:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                $query = $this->insurapp_db->query("SELECT parent_id FROM aauth_users WHERE id = '".$user['parent_id']."'");
                $parent1 = $query->row_array();
                $user['parents'] = array(1 => $parent1['parent_id'], 2 => $user['parent_id']);
                break;
            case 29:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                $query = $this->insurapp_db->query("SELECT parent_id FROM aauth_users WHERE id = '".$user['parent_id']."'");
                $parent2 = $query->row_array();
                $query = $this->insurapp_db->query("SELECT parent_id FROM aauth_users WHERE id = '".$parent2['parent_id']."'");
                $parent1 = $query->row_array();
                $user['parents'] = array(1 => $parent1['parent_id'], 2 => $parent2['parent_id'], 3 => $user['parent_id']);
                break;
            case 30:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                $query = $this->insurapp_db->query("SELECT parent_id FROM aauth_users WHERE id = '".$user['parent_id']."'");
                $parent3 = $query->row_array();
                $query = $this->insurapp_db->query("SELECT parent_id FROM aauth_users WHERE id = '".$parent3['parent_id']."'");
                $parent2 = $query->row_array();
                $query = $this->insurapp_db->query("SELECT parent_id FROM aauth_users WHERE id = '".$parent2['parent_id']."'");
                $parent1 = $query->row_array();
                $user['parents'] = array(1 => $parent1['parent_id'], 2 => $parent2['parent_id'], 3 => $parent3['parent_id'], 4 => $user['parent_id']);
                break;
        }

        return $user;
    }

    function define_user_simple($user_id){

        $query = $this->insurapp_db->query("SELECT u.id, u.user_link_id, u.parent_id, u.email, u.cellphone, u.username, u.name, u.default_usergroup, u.default_app, g.name as 'group' FROM `aauth_users` u, aauth_groups g WHERE u.default_usergroup = g.id AND u.id = $user_id");
        $user = $query->row_array();

        //usergroup tells us his priviladges
        //user_link tells us master_company, agency or branch

        switch ($user['default_usergroup']) {
            case 8:
            case 19:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.name = 'Spazapp'");
                $user['default_usergroup'] = 26;
                $user['link'] = $query->row_array();
                break;
            case 23:
                $query = $this->insurapp_db->query("SELECT id as 'master_company_id', name as 'master_company' FROM `ins_master_companies` WHERE id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                break;
            case 24:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name FROM ins_master_companies m, ins_agencies a WHERE m.id = a.master_company AND a.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                break;
            case 25:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                break;
            case 26:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                break;
            case 27:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                $user['parents'] = array(1 => $user['parent_id']);
                break;
            case 28:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                break;
            case 29:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                break;
            case 30:
                $query = $this->insurapp_db->query("SELECT m.id as 'master_company_id', m.name as 'master_company', a.id as 'agency_id', a.name as agency_name, b.id as 'branch_id', b.name as 'branch_name' FROM ins_master_companies m, ins_agencies a, ins_branches b WHERE a.id = b.agency AND m.id = a.master_company AND b.id = '".$user['user_link_id']."'");
                $user['link'] = $query->row_array();
                break;
        }

        return $user;
    }

    function get_application_from_id($id){
        $query = $this->insurapp_db->query("SELECT * FROM  ins_applications WHERE id = '".$id."'");
        $application = $query->row_array();
        $query = $this->insurapp_db->query("SELECT * FROM  ins_application_data WHERE policy_number = '".$application['policy_number']."'");
        $application['data'] = $query->result_array();
        return $application;
    }

    function get_application($policy_number){
        $query = $this->insurapp_db->query("SELECT a.*, u.name as 'sales_agent', p.name as 'product_name', e.name as 'insurer' FROM  ins_applications a
        LEFT JOIN aauth_users u ON a.sold_by = u.id
        LEFT JOIN ins_products p ON a.ins_prod_id = p.id
        LEFT JOIN ins_entities e ON p.insurer = e.id
        WHERE policy_number = '".$policy_number."'");
        $application = $query->row_array();
        $query = $this->insurapp_db->query("SELECT * FROM  ins_application_data WHERE policy_number = '".$policy_number."'");
        $application['data'] = $query->result_array();
        return $application;
    }
 
    function get_incomplete_application($product_id, $data){

        if(isset($data['sa_id'])){
            $field = 'sa_id';
            $value = $data['sa_id'];

        }else{
            $field = 'tel_cell';
            $value = $data['tel_cell'];
        }

        $query = $this->insurapp_db->query("SELECT policy_number, first_name, last_name, sa_id, dob, passport_number, tel_cell, email_address, postal_code, beneficiary_name, beneficiary_sa_id, language, picture, signature, policy_wording_id
            FROM  ins_applications
        WHERE $field = '".$value."' AND ins_prod_id = $product_id AND sale_complete = 0");
        $application = $query->row_array();
        if($application){
            $query = $this->insurapp_db->query("SELECT * FROM  ins_application_data WHERE policy_number = '".$application['policy_number']."'");
            $application['data'] = $query->result_array();

            return $application;
        }

        return false;
    }

    function get_product_types($agency_id){
        $query = $this->insurapp_db->query("SELECT distinct(t.id), t.name FROM  ins_agen_prod_link l, ins_products p, ins_types t WHERE p.id = l.product_id AND t.id = p.type AND p.status = 8 AND l.agency_id = '".$agency_id."' ORDER BY l.priority DESC");
        $types = $query->result_array();
        return $types;
    }

    function get_products($agency_id='', $type_id){
        $type_query = '';
        $where_agency='';
        if($type_id != 'all' && !empty($type_id)){
            $type_query = "AND p.type = $type_id";
        }
        if(!empty($agency_id)){
            $where_agency ="AND l.agency_id = $agency_id";
        }

        $query = $this->insurapp_db->query("SELECT distinct(p.id), p.name, p.premium, p.image, p.code,
                                p.type as 'type_id', t.name as 'type', l.credit
                                FROM  ins_agen_prod_link l, ins_products p, ins_types t 
                                WHERE p.id = l.product_id 
                                AND t.id = p.type 
                                AND p.status = 8 $where_agency
                                $type_query ORDER BY l.priority ASC");
        $products = $query->result_array();

        return $products;
    }

function get_detailed_product($product_id, $terms=false){

        $query = $this->insurapp_db->query("SELECT distinct(p.id), p.name, p.premium, p.image, p.code, p.dependants, p.beneficiary, p.spouse, p.terms, 
                                    p.type as 'type_id', t.name as 'type', l.credit, p.description
                FROM  ins_agen_prod_link l, ins_products p, ins_types t 
                WHERE p.id = l.product_id AND t.id = p.type AND p.status = 8 AND p.id = '".$product_id."'");
        $product = $query->row_array();

        $query = $this->insurapp_db->query("SELECT distinct(language), file FROM ins_terms_audio WHERE status = 8 AND product_id = '".$product['id']."'");
        $product['terms_audio'] = $query->result_array();

        $languages = array('English' => 'English','Afrikaans' => 'Afrikaans','Zulu' => 'Zulu');

        foreach ($product['terms_audio'] as $key => $value) {
            if(in_array($value['language'], $languages)){
                unset($languages[$value['language']]);
            }

            if(empty($value['file'])){
                $product['terms_audio'][$key]['file'] = 'default_terms.mp3';
            }

        }

        foreach ($languages as $key => $language) {
            $product['terms_audio'][] = array('language' => $language, 'file' => 'default_terms.mp3');
        }

        $query = $this->insurapp_db->query("SELECT distinct(name), value FROM ins_settings WHERE status = 8 AND product_id = '".$product['id']."'");
        $product['settings'] = $query->result_array();        

        if($terms){
            $query = $this->insurapp_db->query("SELECT heading, copy, file FROM ins_terms WHERE status = 8 AND id = '".$product['terms']."'");
            $product['terms'] = $query->row_array();
        }

        return $product;
    }

    function get_product_premium($product_id, $data){

        $return = array();
        $return['message'] = '';
        $return['premium'] = '';
        $return['error'] = false;

        if(isset($data['loan_amount']) && isset($data['loan_term'])){
            $data['amount'] = $data['loan_amount'];
            $data['term'] = $data['loan_term'];
        }


        if(isset($data['term']) && $data['term'] >= 1 && isset($data['amount']) && $data['amount'] >= 100){
            $product = $this->get_product($product_id);
            $premium = $product['premium'];
            if(isset($data['dob']) && $this->age_from_dob($data['dob']) >= 65){
                $premium = $product['premium_over_65'];
            }
            $return['premium'] = $premium * ($data['amount']/1250) * $data['term'];
        }else{

            $product = $this->get_product($product_id);
            $premium = $product['premium'];
            if(isset($data['dob']) && $this->age_from_dob($data['dob']) >= 65){
                $premium = $product['premium_over_65'];
            }
            
            if(isset($data['term'])){
                $product['premium'] = $premium*$data['term'];
            }

            $return['premium'] = ceil($product['premium']);
        }

        if($return['premium'] <= 0){
            $return['message'] = 'This product is not available for the customers age.';
            $return['error'] = true;
        }
            
        return $return;
    }

    function applications_search($search_term){

        $search_term = trim($search_term);
        $quote = str_replace('QUOTE_', '', $search_term);

        $query = $this->insurapp_db->query("SELECT 
            CONCAT('QUOTE_',a.id) as 'quote_number',
            a.policy_number, a.first_name, a.last_name, a.application_date, a.expiry_date,
            s.name as 'type',
            u.name as 'sales_agent', p.name as 'product_name' FROM  ins_applications a
            LEFT JOIN ins_statuses s ON a.sale_complete = s.code
            LEFT JOIN aauth_users u ON a.sold_by = u.id
            LEFT JOIN ins_products p ON a.ins_prod_id = p.id
            WHERE
            a.sale_complete IN (1,99)
            AND (
                a.policy_number = '".$search_term."'
            OR a.tel_cell = '".$search_term."'
            OR a.sa_id = '".$search_term."'
            OR a.id = '".$quote."')");

        $policies = $query->result_array();
        if($policies){
            foreach ($policies as $key => $policy) {
                $query = $this->insurapp_db->query("SELECT * FROM  ins_application_data WHERE policy_number = '".$policy['policy_number']."'");
                $policies[$key]['data'] = $query->result_array();
            }
        }
        return $policies;
    }

    function customer_search($idcell){

        $idcell = trim($idcell);

        $field = 'passport_number';
        if(strlen($idcell) == 10){
            $field = 'tel_cell';
        }
        if(strlen($idcell) == 13){
            $field = 'sa_id';
        }

        $query = $this->insurapp_db->query("SELECT first_name, last_name, sa_id, dob, passport_number, tel_cell, email_address, postal_code, beneficiary_name, beneficiary_sa_id, language, signature, picture FROM ins_applications WHERE $field = '$idcell' AND first_name != '' AND last_name != '' ORDER BY id DESC limit 1");
        $customer = $query->result_array();

        return $customer;

    }

    function strip_db_rejects($table, $dirty_array){
      $clean_array = array();
      $table_fields = $this->insurapp_db->list_fields($table);

      foreach ($dirty_array as $key => $value) {
        if(in_array($key, $table_fields)){
          $clean_array[$key] = $value;
        }
      }
      return $clean_array;
    }

    function allocate_funds_and_comms($policy_number, $user_id, $premium){

        $this->load->model("insurapp_financial_model");

        $user = $this->define_user($user_id);
        $policy = $this->get_application($policy_number);
        $agency_id = $user['link']['agency_id'];
        $branch_id = $user['link']['branch_id'];
        $product = $this->get_product($policy['ins_prod_id'], $agency_id);
        $msisdn = $user['username'];
        $group = $user['group'];
        $reference = 'insurance_sale_comm-'.$policy_number;
        $amount = 0;
        
        if($policy['payment_method'] == 'wallet' || $policy['payment_method'] == 'atm'){
            $credit = false;
        }
        
        if($policy['payment_method'] == 'credit'){
            $credit = true;
        }

        /* if we bought this with cash then we do the transaction immidiately. */

        if(!$credit){
            $this->financial_model->insurance_purchase($msisdn, $premium, $policy_number);
        }

        foreach ($product['split'] as $entity => $value) {

            //fetch insurer
            if($entity == 'insurer_split' && $value > 0){

                $amount = $premium*$value/100;
                if(!$credit){
                    $this->insurapp_financial_model->add_wallet_transaction('credit', $amount, $reference, $this->get_insurer_wallet($product['insurer']));
                    $this->insurapp_financial_model->add_wallet_transaction('debit', $amount, $reference, '0999999971');
                }else{
                    $this->comms_wallet_model->add_comm_wallet_transaction('credit', $amount, $reference, $this->get_insurer_wallet($product['insurer']));
                    $this->comms_wallet_model->add_comm_wallet_transaction('debit', $amount, $reference, '0999999971');
                }

            }else{

                if(strpos($entity, "_split") !== false && $entity != 'sales_channel_split'){

                    $amount = $premium*$value/100;
                    if(!$credit){
                        $this->insurapp_financial_model->add_wallet_transaction('credit', $amount, $reference, $this->get_entity_wallet($product['split'][str_replace('_split', '', $entity)]));
                        $this->insurapp_financial_model->add_wallet_transaction('debit', $amount, $reference, '0999999971');
                    }else{
                        $this->comms_wallet_model->add_comm_wallet_transaction('credit', $amount, $reference, $this->get_entity_wallet($product['split'][str_replace('_split', '', $entity)]));
                        $this->comms_wallet_model->add_comm_wallet_transaction('debit', $amount, $reference, '0999999971');
                    }
                    /*$entity = $this->get_entity($product['split'][str_replace('_split', '', $entity)]);*/
                }

                if($entity == 'sales_channel_split'){

                    $total_sales_comm = $premium*$value/100;

                    //Agency
                    $value = $product['sales_split']['agency'];
                    if($value > 0){
                        $amount = $total_sales_comm*$value/100;

                        if(!$credit){
                            $this->insurapp_financial_model->add_wallet_transaction('credit', $amount, $reference, $this->get_agency_wallet($agency_id));
                            $this->insurapp_financial_model->add_wallet_transaction('debit', $amount, $reference, '0999999971');
                        }else{
                            $this->comms_wallet_model->add_comm_wallet_transaction('credit', $amount, $reference, $this->get_agency_wallet($agency_id));
                            $this->comms_wallet_model->add_comm_wallet_transaction('debit', $amount, $reference, '0999999971');
                        }
                    }

                    //Branch
                    $value = $product['sales_split']['branch'];
                    if($value > 0){
                        $amount = $total_sales_comm*$value/100;

                        if(!$credit){
                            $this->insurapp_financial_model->add_wallet_transaction('credit', $amount, $reference, $this->get_branch_wallet($branch_id));
                            $this->insurapp_financial_model->add_wallet_transaction('debit', $amount, $reference, '0999999971');
                        }else{
                            $this->comms_wallet_model->add_comm_wallet_transaction('credit', $amount, $reference, $this->get_branch_wallet($branch_id));
                            $this->comms_wallet_model->add_comm_wallet_transaction('debit', $amount, $reference, '0999999971');
                        }
                    }

                    $parent_id = false;
                    $to_credit = $msisdn;
                    $tier_amounts = array();
                    $tier_percentages = array();
                    $initial_tier = str_replace('InsTier', '', $group);
                    //echo "Sales Comm = " . $total_sales_comm . "\n";

                    //we should only start at the tier the user is in who made the sale
                    for ($i=intval(str_replace('InsTier', '', $group)); $i > 0; $i--) {

                        //start at 5 and work our way up.
                        $value = $product['sales_split']['tier_'.$i];
                        $tier_percentages[$i] = $value;
                        //echo "Tier = " . $i . "\n";

                        if($value > 0){

                            if(isset($user['parents'][$i])){
                                $parent = $this->user_model->get_user($user['parents'][$i]);
                                $to_credit = $parent->username;

                            }
                                //echo "To credit = " . $to_credit . "\n";

                            //this is to account for the comm paid to thier child.
                            if($i < $initial_tier && isset($tier_percentages[$i+1])){
                                $value = $value - $tier_percentages[$i+1];
                            }
                            
                            //echo "Tier Percentage = " . $value . "\n";
                            
                            $amount = $total_sales_comm*$value/100;
                            $tier_amounts[$i] = $amount;
                            //echo "total comm = " . $amount . "\n";

                            if(!$credit){

                                //there will only ever be one tier of sales so. assign it. minus it from the premium total. then minus us from spazapp wallet and add it to insurapp wallet.

                                $spaazpp_comm_total = $amount;
                                //add the financial transactions on both systems. then remove it automatically.

                                $this->financial_model->add_wallet_transaction('credit', $amount, $reference, $to_credit);
                                $this->financial_model->add_wallet_transaction('debit', $amount, $reference, '0999999971');

                                $this->insurapp_financial_model->add_wallet_transaction('credit', $amount, $reference, $to_credit);
                                $this->insurapp_financial_model->add_wallet_transaction('debit', $amount, $reference, '0999999971');

                                $this->insurapp_financial_model->add_wallet_transaction('debit', $amount, $reference.'_spazapp', $to_credit);


                            }else{
                                $this->comms_wallet_model->add_comm_wallet_transaction('credit', $amount, $reference, $to_credit);
                                $this->comms_wallet_model->add_comm_wallet_transaction('debit', $amount, $reference, '0999999971');
                            }
                        }                       
                    }

                    if(isset($spaazpp_comm_total)){
                        $prem_minus_comm = $premium-$spaazpp_comm_total;
                        $this->insurapp_financial_model->add_wallet_transaction('credit', $amount, $reference.'spazapp', '0999999971');
                        $this->financial_model->add_wallet_transaction('debit', $amount, $reference.'_insurapp', '0999999971');
                    }
                }
            }           
        }

        return true;     
    }

    function get_product_id_from_agency_link_id($link_id){

        $query = $this->insurapp_db->query("SELECT product_id FROM `ins_agen_prod_link` WHERE `id`=$link_id");
        return $query->row_array()['product_id'];

    }

    function get_insurer_wallet($insurer_id){

      $wallet = '0099000000';
      $username = substr($wallet,0,-strlen($insurer_id)).$insurer_id;
      return $username;
    }

    function get_entity_wallet($entity_id){

      $wallet = '0098000000';
      $username = substr($wallet,0,-strlen($entity_id)).$entity_id;
      return $username;
    }

    function get_agency_wallet($agency_id){

      $wallet = '0097000000';
      $username = substr($wallet,0,-strlen($agency_id)).$agency_id;
      return $username;
    }

    function get_branch_wallet($branch_id){

      $wallet = '0096000000';
      $username = substr($wallet,0,-strlen($branch_id)).$branch_id;
      return $username;
    }


   

  function get_master_company_products($master_company_id){

    $query = $this->insurapp_db->query("SELECT * FROM ins_products WHERE master_company='$master_company_id'");

    return $result = $query->result_array(); 

  }   

  function get_product_simple($product_id){

    $query = $this->insurapp_db->query("SELECT * FROM ins_products WHERE id='$product_id'");
    return $result = $query->row_array(); 

  }

   function get_comm_wallet_transactions($msisdn,$date_from,$date_to){
    if(!empty($date_from)){
        $where_date=" and createdate>='$date_from' and createdate<='$date_to'";
    }else{
        $where_date='';
    }
    
    $query = $this->insurapp_db->query("SELECT * FROM comm_wallet_transactions WHERE msisdn='$msisdn' $where_date");

    $transactions = $result = $query->result_array(); 

    foreach ($transactions as $key => $trans) {
        $transactions[$key]['application'] = $this->get_application(trim(str_replace('insurance_sale_comm-', '', $trans['reference'])));
        $transactions[$key]['sales_agent'] = $this->define_user_simple($transactions[$key]['application']['sold_by']);
    }

    return $transactions;

  }

  function get_applications($id, $type, $date_from, $date_to, $branch_id='', $request_type=''){

    if(!empty($date_from)){
        $where_date=" and app.application_date>='$date_from' and app.application_date<='$date_to'";
    }else{
        $where_date='';
    }
    
    if(!empty($branch_id)){
        $where_branch=" and b.id=$branch_id";
    }else{
        $where_branch="";
    }

    if($branch_id=="undefined"){
        $where_branch="";
    }

    $where_user_type=false;
    switch ($type) {
        case 'branch':
            $where_user_type = " and b.id = $id ";
            break;

        case 'agency':
            $where_user_type = " and b.agency = $id ";
            break;

        case 'master_company':
            $where_user_type = " and b.agency in ($id) ";
            break;  

       default:
            $where_user_type = " ";
            break;

    }

    $group_by='';
    $select='';
    if($request_type=="products"){
        $group_by="GROUP BY app.ins_prod_id";
        $select="count(app.ins_prod_id) as number_of_sales, SUM(app.premium) as total_premium,";
    } 
    if($request_type=="stats"){
        $group_by="GROUP BY app.ins_prod_id";
        $select="count(app.ins_prod_id) as number_of_sales, SUM(app.premium) as total_premium,";
    }
  
 
    $query = $this->insurapp_db->query("SELECT app.*, 
                            app.id,
                            p.name as 'product_name', 
                            pt.name as 'product_type', 
                            a.name as 'agency',
                            b.name as 'branch',
                            u.name as 'agent',
                            u.name as 'sold_by',
                            $select
                            s.name as status
                            FROM ins_applications as app, 
                            ins_products as p, 
                            ins_types as pt,
                            aauth_users as u,
                            ins_branches as b,
                            ins_agencies as a,
                            ins_statuses as s
                            WHERE 
                            app.ins_prod_id = p.id
                            AND p.type = pt.id
                            AND s.code = app.sale_complete
                            AND app.sold_by = u.id
                            AND u.user_link_id = b.id
                            AND b.agency = a.id
                            AND sale_complete = 1 $where_user_type 
                            $where_branch $where_date $group_by");
    return $query->result_array();

  }

  function age_from_dob($dob){
      //explode the date to get month, day and year
      $birthDate = explode("-", $dob);
      $age = date("Y") - $birthDate[0];
      return $age;
  }

  function send_application_comms($policy_number, $data){

    $this->load->library('encrypt');

    $product = $this->get_product($data['product_id']);
    $policy = $this->get_application_from_policy_no($policy_number);

    $to = $data['tel_cell'];
    $product_name = $product['name'];
    $insurer = $product['insurer_name'];
    $premium = $data['premium'];
    $term = $data['product_data']['loan_term'];
    if(isset($data['product_data']['loan_amount'])){
        $loan_amount = $data['product_data']['loan_amount'];
    }
    $expiry_date = $policy['expiry_date'];

    $encrypted_string = $this->encrypt->encode($to.'_'.$policy_number.'_'.$data['product_id']);
    $url = base_url().'insurapp/policy_wording/display?hash='.$encrypted_string;
    $short_url = $this->generate_short_url($url);

    $update_array = array('short_url' => $short_url);

    $this->insurapp_db->where('policy_number', $policy_number);
    $this->insurapp_db->update('ins_applications', $update_array);

    if(!isset($data['payment_method']) && isset($data['is_quote'])){
        
        $message = '';
    
    }elseif($data['payment_method'] == 'atm'){

        $message = $insurer . ': To activate '.$product_name.' deposit R'.$premium.' at any ABSA ATM into 6253481161 using reference: '.$policy_number;
        
    }else{

        switch ($product['type']) {
            case 1:
                # funeal
                $message = "$insurer: You paid R$premium for $term month(s) of $product_name. Your policy number is $policy_number. Cover expires on $expiry_date. See policy terms here $short_url";
                break;
            
            case 2:
                # vdc
                $message = "$insurer: You paid R$premium for $term month(s) of $product_name to the value of R$loan_amount. Your policy number is $policy_number. Cover expires on $expiry_date. See policy terms here $short_url";
                break;

            case 3:
                # life
                $message = "$insurer: You paid R$premium for $term month(s) of $product_name to the value of R$loan_amount. Your policy number is $policy_number. Cover expires on $expiry_date. See policy terms here $short_url";
                break;

            default:
                 $message = "$insurer: You paid R$premium for $term month(s) of $product_name. Your policy number is $policy_number. Cover expires on $expiry_date. See policy terms here $short_url";
                break;
        }
        
    
    }
    if($message != ''){
        $this->comms_model->send_sms($to, $message);
    }
  }

  function generate_short_url($url){

    // Get API key from : http://code.google.com/apis/console/
    $apiKey = 'AIzaSyClrs5lAdHpC9c0CTH06rRSCfcE4opBumI';

    $postData = array('longUrl' => $url);
    $jsonData = json_encode($postData);

    $curlObj = curl_init();

    curl_setopt($curlObj, CURLOPT_URL, 'https://www.googleapis.com/urlshortener/v1/url?key='.$apiKey);
    curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curlObj, CURLOPT_HEADER, 0);
    curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
    curl_setopt($curlObj, CURLOPT_POST, 1);
    curl_setopt($curlObj, CURLOPT_POSTFIELDS, $jsonData);

    $response = curl_exec($curlObj);

    // Change the response json string to object
    $json = json_decode($response);

    curl_close($curlObj);

    return $json->id;

  }

  function get_sales_agents_for_parent($current_user_id){
    $query = $this->insurapp_db->query("SELECT id, name from aauth_users where default_usergroup in (26,27,28,29) AND id != $current_user_id");
    return $query->result_array();
  }

  function get_applications_export_query($billrun_id, $branch_id){

    $billrun = $this->get_billrun($billrun_id);

    $query="SELECT
            app.*,
            adata1.value as 'loan_amount',
            adata2.value as 'loan_term',
            p.name, p.code, p.cover, p.insurer,
            pt.name as 'insurance_type', 
            u.name as 'sold_by', u.username as 'sold_by_username', 
            a.name as 'agency',
            b.name as 'branch'
            FROM ins_applications as app
            JOIN ins_products as p ON app.ins_prod_id = p.id
            JOIN ins_types as pt ON p.type = pt.id
            JOIN aauth_users as u ON app.sold_by = u.id
            JOIN ins_branches as b ON u.user_link_id = b.id
            JOIN ins_agencies as a ON b.agency = a.id
            LEFT JOIN ins_application_data AS adata1 ON adata1.policy_number = app.policy_number AND adata1.name = 'loan_amount'
            LEFT JOIN ins_application_data as adata2 on adata2.policy_number = app.policy_number AND adata2.name = 'loan_term'
            WHERE 
            app.application_date >= '".$billrun['start_date']."'
            AND app.application_date <= '".$billrun['end_date']."'
            AND app.sale_complete = 1 
            AND b.id = $branch_id";

    return $query;
  }

  function send_invoice($master_company_id, $invoice_id, $branch_id){
     $data['invoice_info'] = $this->get_appl_invoice($branch_id, $invoice_id);
     $data['branch_info'] = $this->get_branch_user($branch_id);
     $data['invoice_id']=$invoice_id;
     $data['master_company_info'] = $this->get_master_company($master_company_id);
     $this->comms_model->send_insurapp_email($data['branch_info']['email'], array('template' => 'application_invoice', 'subject' => 'INSURAPP: Payment Request', 'message' => $data));

  }


  function get_appl_invoice($branch_id, $invoice_id){
    $query = $this->insurapp_db->query("SELECT 
                            sum(app.premium) as total_premium, 
                            count(app.ins_prod_id) as product_count, 
                            p.name as 'product_name', 
                            a.name as 'agency', 
                            b.name as 'branch', 
                            u.name as 'sold_by',
                            app.application_date,
                            bl.start_date,
                            bl.end_date,
                            (SELECT name from ins_entities WHERE id=p.insurer) as insurer,
                            bi.id as invoice_id
                            FROM ins_applications as app, 
                            ins_products as p, 
                            ins_types as pt,
                            aauth_users as u,
                            ins_branches as b,
                            ins_agencies as a,
                            ins_billrun_invoices as bi,
                            ins_billruns as bl
                            WHERE 
                            app.ins_prod_id = p.id
                            AND p.type = pt.id
                            AND app.application_date >= bl.start_date 
                            AND app.application_date <= bl.end_date
                            AND app.sold_by = u.id
                            AND u.user_link_id = b.id
                            AND b.agency = a.id
                            AND bi.branch = b.id
                            AND bl.id = bi.billrun
                            AND app.sale_complete = 1 
                            AND b.id = ? 
                            AND bi.id = ? 
                            and (u.default_app='aspis' or u.default_app='insurapp')
                            AND app.sale_complete = 1
                            GROUP BY app.ins_prod_id ", array($branch_id, $invoice_id));

    return $query->result_array();

  }

  function get_sales_comms(){
    $query=$this->insurapp_db->query("SELECT c.*, 
                        a.policy_number, 
                        a.premium, 
                        p.name as'product',
                        u.name as 'sales_agent',
                        c.createdate
                        from
                        comm_wallet_transactions c
                        JOIN ins_applications a ON c.reference like CONCAT('%-',a.policy_number)
                        JOIN ins_products p ON a.ins_prod_id = p.id
                        LEFT JOIN aauth_users u ON u.username = c.msisdn
                        WHERE a.sale_complete = 1");

    return $query->result_array();
  }

  function get_sales($id, $type='',$date_from, $date_to, $branch_id){

    if(!empty($date_from) && !empty($date_to)){
        $where_date="and app.application_date >= '$date_from' and app.application_date <= '$date_to' ";
    }else{
        $where_date="";
    }

    if(!empty($branch_id)){
        $where_branch=" and b.id=$branch_id";
    }else{
        $where_branch="";
    }

    $where_sold_by='';
    switch ($type) {
        case 'branch':
            $where_sold_by = " and b.id = $id ";
            break;
        case 'agency':
            $where_sold_by = " and b.agency = $id ";
            break;
        case 'master_company':
            $where_sold_by = " and b.agency in ($id) ";
            break;
        default:
            $where_sold_by = '';
            break;
    }


    $query=$this->insurapp_db->query("SELECT 
                    app.policy_number,
                    app.sa_id, 
                    app.passport_number, 
                    app.payment_reference_no,
                    a.name, 
                    p.type, 
                    p.name as product,
                    p.name as product_name,
                    app.premium, 
                    app.application_date, 
                    app.expiry_date, 
                    a.name as sold_by,
                    app.picture,
                    app.signature,
                    app.sold_by as user_id,
                    ag.name as agency,
                    b.name as branch,
                    a.name as agent,
                    s.name as status
                    FROM `ins_applications` as app 
                    JOIN ins_products as p  ON app.ins_prod_id = p.id
                    JOIN aauth_users as a  ON app.sold_by = a.id
                    JOIN ins_branches as b ON a.user_link_id = b.id
                    JOIN ins_agencies as ag ON b.agency = ag.id
                    JOIN ins_statuses as s ON s.code = app.sale_complete
                    WHERE a.default_app in('insurapp','aspis', 'royal') 
                    $where_sold_by
                    $where_date $where_branch
                    GROUP BY app.policy_number");



    return $query->result_array();
  }

  function product_sales($id, $type='',$date_from, $date_to, $branch_id){
        if(!empty($date_from) && !empty($date_to)){
            $where_date="and app.application_date >= '$date_from' and app.application_date <= '$date_to' ";
        }else{
            $where_date="";
        }

        if(!empty($branch_id)){
            $where_branch=" and b.id=$branch_id";
        }else{
            $where_branch="";
        }

        $where_sold_by='';
        switch ($type) {
            case 'branch':
                $where_sold_by = " and b.id = $id ";
                break;
            case 'agency':
                $where_sold_by = " and b.agency = $id ";
                break;
            case 'master_company':
                $where_sold_by = " and b.agency in ($id) ";
                break;
            default:
                $where_sold_by = '';
                break;
        }



        $query = $this->insurapp_db->query("SELECT 
                                count(app.ins_prod_id) as number_of_sales,
                                SUM(app.premium) as total_premium, 
                                p.name as product_name
                                FROM `ins_applications` as app 
                                JOIN ins_products as p  ON app.ins_prod_id = p.id
                                JOIN aauth_users as a  ON app.sold_by = a.id
                                JOIN ins_branches as b ON a.user_link_id = b.id
                                JOIN ins_agencies as ag ON b.agency = ag.id
                                JOIN ins_statuses as s ON s.code = app.sale_complete
                                WHERE a.default_app in('insurapp','aspis', 'royal') 
                                $where_sold_by
                                $where_date $where_branch
                                GROUP BY app.ins_prod_id  
                                DESC");      
                
        return $result = $query->result_array(); 
  }

  function get_users_from_link_id($user_link_id){
      $query = $this->insurapp_db->query("SELECT * FROM `aauth_users` WHERE user_link_id = $user_link_id and default_app='insurapp'");
      return $query->result_array();
  }

  function insert_claim($post_array, $type=''){
        $this->insurapp_db->query("INSERT INTO ins_claims (policy_number, id_number, cellphone, email, product_id, createdate, first_name, last_name) VALUES(?,?,?,?,?,?, ?, ?)", array($post_array['policy_number'], $post_array['id_number'], $post_array['cellphone'], $post_array['email'], $post_array['product_id'], date("Y-m-d h:i:s"), $post_array['first_name'], $post_array['last_name']));
  }

  function send_email($policy_number){
    $data['applicant_info']=$this->get_applicant_info($policy_number);
    $data['claim_info']=$this->get_claim_info($policy_number);
    $data['base_url']=base_url();

    $this->comms_model->send_insurapp_email("mpho@spazapp.co.za, insurappclaims@monitorkzn.co.za", array('template' => 'insurance_claim', 'subject' => 'INSURAPP: Claim', 'message' => $data));

  }

  function upload_document($policy_number, $type){
        $config['upload_path'] = 'assets/uploads/insurance/claim_documents/';
        $config['allowed_types'] = '*';
        $config['max_filename'] = '255';
        $config['encrypt_name'] = TRUE;
        $config['max_size'] = '1112024'; //1 MB
        if(isset($policy_number) && !empty($policy_number)){
            if (isset($_FILES['file']['name'])) {
            if (0 < $_FILES['file']['error']) {
                echo 'Error during file upload' . $_FILES['file']['error'];
            } else {
                if (file_exists('assets/uploads/insurance/claim_documents/' . $_FILES['file']['name'])) {
                    echo 'File already exists : ' . $_FILES['file']['name'];
                } else {
                    $this->load->library('upload', $config);
                    if (!$this->upload->do_upload('file')) {
                        echo $this->upload->display_errors();
                    } else {

                        $upload_data = $this->upload->data(); //Returns array of containing all of the data related to the file you uploaded.
                        $file_name = $upload_data['file_name'];
                        echo 'File successfully uploaded :' . $_FILES['file']['name'];

                        if($type=='id_document_doc'){
                            $this->insurapp_db->query("UPDATE ins_claims SET id_document=? WHERE policy_number=?", array($file_name, $policy_number));
                       
                        }

                        if($type=='death_certificate'){
                            $this->insurapp_db->query("UPDATE ins_claims SET death_certificate=? WHERE policy_number=?", array($file_name, $policy_number));
                            
                        }
                      
                      
                    }
                }
            }
            } else {
                echo 'Please choose a file';
            }

        }else{
            echo "Please enter policy number";
        }
        
    }

    function update_claim($policy_number, $post_array){
        if(isset($post_array['id_document'])){
             $this->insurapp_db->query("UPDATE ins_claims SET  id_document=? WHERE policy_number=?",  array($post_array['id_document'], $policy_number));
        }

         if(isset($post_array['death_certificate'])){
             $this->insurapp_db->query("UPDATE ins_claims SET death_certificate=?WHERE policy_number=?",  array($post_array['death_certificate'], $policy_number));
        }
       
      
    }

    function policy_number_exit_in_claim($policy_number){
        $query=$this->insurapp_db->query("SELECT * FROM ins_claims WHERE policy_number=?", array($policy_number));
        if(!empty($query->row_array()['policy_number'])){
            return true;
        }else{
            return false;
        }
    }


    function get_claim_info($policy_number){
        $query=$this->insurapp_db->query("SELECT * FROM ins_claims c, ins_products p WHERE p.id=c.product_id and policy_number=?", array($policy_number));
        return $query->row_array();

    }


    function get_applicant_info($policy_number){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_applications` WHERE policy_number ='$policy_number'");
        return $result = $query->row_array();
    }

    function get_agency_branches($agency_id){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_branches` where agency=?", array($agency_id));
        $result = $query->result_array();
        return $result;
    }

    function verify_policy($policy_number){
        $policy=$this->get_applicant_info($policy_number);
        if(!empty($policy['policy_number'])){
            return true;
        }else{
            return false;
        }
    }

    function get_status($code){
        $query = $this->insurapp_db->query("SELECT * FROM `ins_statuses` WHERE code=?", array($code));
        return $result = $query->row_array();

    }

}