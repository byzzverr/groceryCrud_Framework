<?php

class Tt_order_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('financial_model');
      $this->load->model('taptuck_model');
      $this->load->model('tt_kid_model');
      $this->load->model('tt_menu_model');
   }

   function place_order($user_info, $kid_id, $menu_id, $period, $date){

      $result['success'] = false;
      $menu_item = $this->tt_menu_model->get_tt_menu_item($menu_id);

      //get_the_day_of_the_week (dotw)
      $dayofweek = date('w', strtotime($date));
      $dayofweekname = date('l', strtotime($date));
      $dotw = $dayofweek+1; // sunday is 0 we need it to be 1

      if (!is_array($menu_item['days'])) {
         $menu_item['days'] = array($dotw);
      }

      if(in_array($dotw, $menu_item['days'])){

         if($menu_item['price'] <= $user_info->wallet_balance){
            $order = array(
                  "kid_id" => $kid_id,
                  "menu_id" => $menu_id,
                  "price" => $menu_item['price'],
                  "period" => $period,
                  "date" => $date,
                  "status" => 1,
                  "createdate" => date("Y-m-d H:i:s")
               );
            $order_id = $this->insert_order($order);

            $result['reason'] = 'An error occurred.';

            if($order_id){
               $this->financial_model->process_taptuck_order($order_id, 'add');
               $result['success'] = true;
               $order['id'] = $order_id;
               $result['order'] = $order;
               $result['reason'] = 'Success.';
            }


         }else{
            $result['success'] = false;
            $result['reason'] = 'Insuficient Funds';
         }
      }else{
         $result['success'] = false;
         $result['reason'] = 'Sorry: This '.$menu_item['category'].' is not available on a '.$dayofweekname.'.';
      }

      return $result;
   }

   function insert_order($order){
      if($this->db->insert('tt_orders', $order)){
         return $this->db->insert_id();
      }
      return false;
   }

   function update_order($order_id, $order){
      unset($order['label']);
      unset($order['description']);
      $this->db->where('id', $order_id);
      if($this->db->update('tt_orders', $order)){
         return $order_id;
      }
      return false;
   }

   function redeem_order($order_id){

      $order = $this->get_order($order_id);
      if($order['status'] == 1){

         $order['status'] = 17;
         if($this->update_order($order_id, $order)){
            $this->financial_model->process_taptuck_order($order_id, 'redeem');
            return array('success' => true, 'order' => $order);
         }
      }

      return false;

   }

   function cancel_order($order_id){

      $order_c = $this->get_order($order_id); 
      if($order_c['status'] == 1){
         $order['status'] = 14;
         if($this->update_order($order_id, $order)){
            $this->financial_model->process_taptuck_order($order_id, 'cancel');
            return true;
         }
      }

      return false;

   }

   function get_order($order_id){

      $query = $this->db->query("SELECT o.id, o.menu_id, o.price, o.period, o.date, o.status, m.label, m.description  FROM tt_orders o, tt_menus m WHERE o.menu_id = m.id AND o.id = ? AND o.status != 14", array($order_id));
      return $query->row_array();

   }

   function get_kid_orders($kid_id, $date=false){
      if($date){
         $where = " AND o.date = '$date'";
      }else{
         $date = date("Y-m-d");
         $where = " AND o.date >= '$date'";
      }
      $query = $this->db->query("SELECT o.id, o.menu_id, o.price, o.period, o.date, m.label, m.description, m.category  FROM tt_orders o, tt_menus m WHERE o.menu_id = m.id AND o.kid_id = ? AND o.status = 1 $where", array($kid_id));
      $return = $query->result_array();
      foreach ($return as $key => $order) {
         $return[$key]['coin'] = $this->financial_model->tt_convert_category_to_coin($order['category']);
      }

      return $return;
   }

   function get_kid_order_total($kid_id, $type='daily'){

      $date = date("Y-m-d");

      if($type == 'daily'){
         $where = " AND createdate = '$date'";
      }else{
         $day = date("N");
         $date = date('Y-m-d', strtotime($date . ' +'.$day.' day'));
         $where = " AND createdate >= '$date'";
      }
      $query = $this->db->query("SELECT sum(amount) as 'total' FROM tt_pocket_money_sales WHERE kid_id = ? $where", array($kid_id));
      $return = $query->row_array();
      return $return['total'];
   }

   function pocket_money_purchase($kid_id, $amount, $mwechant_username){

      $kid = $this->tt_kid_model->get_tt_kid_info($kid_id);

      if($kid['balance'] >= $amount){
         $this->db->insert('tt_pocket_money_sales', array(
            "kid_id" => $kid_id,
            "merchant_id" => $kid['merchant_id'],
            "amount" => $amount,
            "createdate" => date("Y-m-d H:i:s")
         ));
         $pocket_money_sale_id = $this->db->insert_id();
         $parent = $this->tt_parent_model->get_tt_parent_from_kid_id($kid_id);
         $this->financial_model->pocket_money_purchase($parent['username'], $mwechant_username, $amount, $pocket_money_sale_id);
         return true;
      }
      return false;
   }

   public function get_calendar($kid_id){

      $calendar = $this->get_next_2_weeks();
    
      $query = $this->db->query("SELECT o.id, o.menu_id, o.price, o.period, o.date, m.label, m.description, m.category  FROM tt_orders o, tt_menus m WHERE o.menu_id = m.id AND o.kid_id = ? AND o.status != 14", array($kid_id));
      $result = $query->result_array();
      if($result){

         foreach ($result as $order) {

            $order['coin'] = $this->financial_model->tt_convert_category_to_coin($order['category']);
            foreach ($calendar as $key => $day) {
               if(!isset($calendar[$key]['coins'])){
                  $calendar[$key]['coins'] = array('gold' => 0,'silver' => 0, 'bronze' => 0);
               }

               if($day['date'] ==  $order['date']){
                  $calendar[$key]['orders'][$order['period']][] = $order;
                  $calendar[$key]['coins'][$order['coin']]++;
               }
            }
         }
      }
      return $calendar;
   }

   function get_next_2_weeks(){

      $daysinmonth = date('t');
      $today = date("Y-m-d");
      $day = date("d");
      /*$day = 22;*/
      $day_clean = intval($day);
      $month = date("m");
      $year = date("Y");
      $nextyear = $year;
      $dayplus = $day_clean + 11;
      $month_clean = intval($month);
      $next_month = intval($month_clean+1);
      if($next_month <= 9){
         $next_month = "0".$next_month;
      }
      $calendar = array();

      if($month_clean == 12){
         $next_month = 1;
         $nextyear = $nextyear+1;
      }

      if($dayplus > $daysinmonth){

         $days2 = $dayplus-$daysinmonth; //days from month 2
         $calendar1 = $this->draw_calendar($month,$year);
         $calendar2 = $this->draw_calendar($next_month,$nextyear);
         $calendar1 = $this->strip_days($calendar1, $day_clean, $daysinmonth);
         $calendar2 = $this->strip_days($calendar2, 1, $days2);

         foreach ($calendar1 as $key => $value) {
            $calendar[] = $value;
         }

         foreach ($calendar2 as $key => $value) {
            $calendar[] = $value;
         }

      }else{

         $calendar1 = $this->draw_calendar($month,$year);
         $calendar1 = $this->strip_days($calendar1, $day, $dayplus);

         foreach ($calendar1 as $key => $value) {
            $calendar[] = $value;
         }
         
      }

      return $calendar;
   }

   function strip_days($calendar, $start_day, $end_day){
      foreach ($calendar as $day => $value) {
         if($day < $start_day || $day > $end_day){
            unset($calendar[$day]);
         }
      }
      return $calendar;
   }

function draw_calendar($month,$year){

   $month_clean = intval($month)-1;

   /* table headings */
   $day_names = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
   $month_names = array('January','February','March','April','May','June','July','August','September','October','November','December');

   /* days and weeks vars now ... */
   $running_day = date('w',mktime(0,0,0,$month,1,$year));
   $days_in_month = date('t',mktime(0,0,0,$month,1,$year));
   $days_in_this_week = 1;
   $day_counter = 0;
   $dates_array = array();
   $calendar = array();

   /* print "blank" days until the first of the current week */
   for($x = 0; $x < $running_day; $x++){
      $days_in_this_week++;
   }

   /* keep going with days.... */
   for($list_day = 1; $list_day < $days_in_month; $list_day++){

         $clean_day = $list_day;
         if($list_day < 10){
            $clean_day = "0".$list_day;
         }
         /* add in the day number */
         $calendar[$list_day]['day'] = $list_day;
         $calendar[$list_day]['day_name'] = $day_names[$running_day];
         $calendar[$list_day]['month'] = $month;
         $calendar[$list_day]['month_name'] = $month_names[$month_clean];
         $calendar[$list_day]['year'] = $year;
         $calendar[$list_day]['date'] = $year."-".$month."-".$clean_day;
         $calendar[$list_day]['orders']['first_break'] = array();
         $calendar[$list_day]['orders']['second_break'] = array();
         $calendar[$list_day]['orders']['after_school'] = array();

         
      if($running_day == 6){
         $running_day = -1;
         $days_in_this_week = 0;
      }
      $days_in_this_week++; $running_day++; $day_counter++;
   }

   /* finish the rest of the days in the week */
   if($days_in_this_week < 8){
      for($x = 1; $x <= (8 - $days_in_this_week); $x++){
         $clean_day = $list_day;
         if($list_day < 10){
            $clean_day = "0".$list_day;
         }
         $calendar[$list_day]['day'] = $list_day;
         $calendar[$list_day]['name'] = $day_names[$running_day];
         $calendar[$list_day]['month_name'] = $month_names[$month_clean];
         $calendar[$list_day]['year'] = $year;
         $calendar[$list_day]['date'] = $year."-".$month."-".$clean_day;
         $calendar[$list_day]['orders']['first_break'] = array();
         $calendar[$list_day]['orders']['second_break'] = array();
         $calendar[$list_day]['orders']['after_school'] = array();
      }
   }

  
   /* all done, return result */
   return $calendar;

}

function get_all_merchant_orders_kids($merchant_id, $order_date='', $period=''){

         $query = $this->db->query("SELECT k.last_name, k.first_name, k.device_identifier, m.merchant_id, k.allergies,
                                    k.food_preferences
                                    FROM tt_orders as o 
                                    JOIN tt_menus as m  ON m.id = o.menu_id 
                                    JOIN tt_kids as k ON k.id = o.kid_id 
                                    LEFT JOIN tt_diet_specific as sp ON sp.id = k.diet_specific 
                                    JOIN tt_parents as p ON k.parent_id = p.id
                                    JOIN gbl_statuses as g  ON g.id = o.status 
                                    WHERE m.merchant_id = $merchant_id and o.date = '$order_date' and o.period='$period' and o.status !=14 GROUP BY o.kid_id");

         return $query->result_array();

    }


   function get_all_merchant_orders($merchant_id, $order_date='', $period='', $overwiew=false, $status=false, $kid_id=''){

         if($kid_id !=''){
            $where_kid_id=" and o.kid_id=$kid_id";
         }else{
            $where_kid_id='';
         }

         if($order_date != ''){
            $where_date = " and o.date = '$order_date'";
         }else{
            $where_date = '';
         }

         if($period !=''){
            $where_period=" and o.period='$period' and o.status !=14";
         }else{
            $where_period='';
         }
         
         if($overwiew){
            $total = " , count(o.menu_id) as menu_count, sum(m.price) as total";
            $grou_by =" GROUP BY o.menu_id";
         }else{
            $grou_by='';
            $total='';
         }

         if($status){
            $where_status=" and status != 17 and status != 14";
         }else{
            $where_status='';
         }
         $query = $this->db->query("SELECT 
                                    o.date as createdate,
                                    m.label,
                                    m.description,  
                                    m.price,
                                    m.category,
                                    k.id as 'kid_id', 
                                    CONCAT(k.first_name,' ',k.last_name) as 'kid_name', 
                                    CONCAT(p.first_name,' ',p.last_name) as 'parent_name', 
                                     -- o.*,
                                    g.name as 'status',
                                    o.id as order_id,
                                    o.period,
                                    k.device_identifier,
                                    k.allergies,
                                    k.food_preferences,
                                    sp.name as diet_specific,
                                    k.grade,
                                    k.merchant_id
                                    $total
                                    FROM tt_orders as o 
                                    JOIN tt_menus as m  ON m.id = o.menu_id 
                                    JOIN tt_kids as k ON k.id = o.kid_id 
                                    LEFT JOIN tt_diet_specific as sp ON sp.id = k.diet_specific 
                                    JOIN tt_parents as p ON k.parent_id = p.id
                                    JOIN gbl_statuses as g  ON g.id = o.status 
                                    WHERE m.merchant_id = $merchant_id 
                                    $where_date $where_period $where_status $grou_by ORDER BY k.last_name desc");
         
            return $query->result_array();
       
    }

    function get_all_merchant_orders_stats($merchant_id){

         $query = $this->db->query("SELECT count( o.menu_id) as order_count, m.label 
                                    FROM tt_orders as o 
                                    JOIN tt_menus as m 
                                    ON m.id = o.menu_id 
                                    JOIN tt_kids as k 
                                    ON k.id = o.kid_id 
                                    WHERE k.merchant_id IN ($merchant_id) GROUP BY o.menu_id ORDER BY o.createdate DESC LIMIT 20");

         return $query->result_array();

    }
   function get_all_kids_merchant_orders_stats($merchant_id){

         $query = $this->db->query("SELECT count( o.kid_id) as order_count, k.first_name, k.last_name 
                                    FROM tt_orders as o 
                                    JOIN tt_menus as m 
                                    ON m.id = o.menu_id 
                                    JOIN tt_kids as k 
                                    ON k.id = o.kid_id 
                                    WHERE k.merchant_id IN ($merchant_id) GROUP BY o.kid_id ORDER BY o.createdate DESC LIMIT 20");

         return $query->result_array();

    }


    function send_order_placed($merchant_id){

         if(!empty($merchant_id)){
            $data = array();
            $date = date("Y-m-d");

             $data['order_info_first_break']= $this->tt_order_model->get_all_merchant_orders_kids($merchant_id, $date, 'first_break');

             $data['first_break_over_view']=$this->tt_order_model->get_all_merchant_orders($merchant_id, $date, 'first_break', true);

            $data['order_info_second_break']= $this->tt_order_model->get_all_merchant_orders_kids($merchant_id, $date, 'second_break');
            $data['second_break_over_view']=$this->tt_order_model->get_all_merchant_orders($merchant_id, $date, 'second_break', true);

            $data['order_info_after_school']= $this->tt_order_model->get_all_merchant_orders_kids($merchant_id, $date, 'after_school');
            $data['after_school_over_view']= $this->tt_order_model->get_all_merchant_orders($merchant_id, $date, 'after_school', true);

            $data['merchant_info'] = $this->tt_merchant_model->get_tt_merchant_info($merchant_id);
            $subject_date= $data['merchant_info']['name'] . ' - Printable ORDERS - ' . $date;

            $user_info=$this->user_model->get_user($data['merchant_info']['user_id']);
             
            if((!empty($data['order_info_first_break']) || !empty($data['order_info_second_break'])|| !empty($data['order_info_after_school'])) && !empty($user_info)){
               $data['user_info']=get_object_vars($user_info);
            
              $result=$this->comms_model->send_taptuck_email($data['user_info']['email'], array('template' => 'taptuck_order_placed', 'subject' => $subject_date, 'message' => $data));

            //$result=$this->comms_model->send_taptuck_email("mpho@spazapp.co.za", array('template' => 'taptuck_order_placed', 'subject' => $subject_date, 'message' => $data));

               $this->load->view('taptuck/emails/'.'taptuck_order_placed', $data);
            }
          
         }   
    }

 function send_order_unredeemed($merchant_id){
         if(!empty($merchant_id)){
            $data = array();
            $date = date("Y-m-d");

            $data['order_info_first_break']= $this->tt_order_model->get_all_merchant_orders($merchant_id, $date, 'first_break', false, true);
            $data['first_break_over_view']=$this->tt_order_model->get_all_merchant_orders($merchant_id, $date, 'first_break', true);

            $data['order_info_second_break']= $this->tt_order_model->get_all_merchant_orders($merchant_id, $date, 'second_break', false, true);
            $data['second_break_over_view']=$this->tt_order_model->get_all_merchant_orders($merchant_id, $date, 'second_break', true);


            $data['order_info_after_school']= $this->tt_order_model->get_all_merchant_orders($merchant_id, $date, 'after_school', false, true);
            $data['after_school_over_view']= $this->tt_order_model->get_all_merchant_orders($merchant_id, $date, 'after_school', true);

            $data['merchant_info'] = $this->tt_merchant_model->get_tt_merchant_info($merchant_id);
            $subject_date= $data['merchant_info']['name'] . ' - UNREDEEMED ORDERS - ' . $date;

            $user_info=$this->user_model->get_user($data['merchant_info']['user_id']);
             
            if((!empty($data['order_info_first_break']) || !empty($data['order_info_second_break'])|| !empty($data['order_info_after_school'])) && !empty($user_info)){
               $data['user_info']=get_object_vars($user_info);
            
               $result=$this->comms_model->send_taptuck_email($data['user_info']['email'], array('template' => 'taptuck_order_uredeemed', 'subject' => $subject_date, 'message' => $data));
               
               $this->load->view('taptuck/emails/'.'taptuck_order_uredeemed', $data);
            }
         }   
    }




   public function get_daily_orders($created_date, $merchant_id){
      $query=$this->db->query("SELECT k.id as kid_id, 
                  m.description, 
                  o.id,
                  o.menu_id, 
                  m.label,
                  o.createdate, 
                  k.first_name,
                  k.last_name,
                  p.first_name as p_first_name,
                  p.last_name as p_last_name,
                  g.name as status,
                  o.date
                  FROM tt_orders as o 
                  JOIN tt_menus m ON o.menu_id=m.id 
                  JOIN tt_kids as k ON k.id=o.kid_id
                  JOIN tt_parents as p ON p.id=k.parent_id
                  JOIN gbl_statuses as g ON g.id=o.status
                  WHERE g.id!='17' and g.id!='14' 
                  and  m.merchant_id='$merchant_id' And o.date='$created_date'");

    return $query->result_array();

   }


  public function get_daily_order_stats($created_date, $merchant_id,$status=''){
   $query=$this->db->query("SELECT COUNT(o.menu_id) as order_count, 
                           m.label as label 
                           FROM tt_orders as o 
                           JOIN tt_menus m ON o.menu_id=m.id
                           JOIN tt_kids as k ON k.id=o.kid_id
                           JOIN tt_parents as p ON p.id=k.parent_id
                           JOIN gbl_statuses as g ON g.id=o.status 
                           WHERE g.id!='17' and g.id!='14' 
                           and m.merchant_id='$merchant_id'  
                           AND o.date='$created_date'
                           GROUP BY o.menu_id DESC LIMIT 100");
   return $query->result_array();

  }

 function get_sales($date_from,$date_to,$merchant_id, $request_type='', $kids_order_value=false){
     
      if(!empty($date_from)){
         $where_date="AND o.date>='$date_from' AND o.date<='$date_to'";
         $where_createdate="AND o.createdate>='$date_from' AND o.createdate<='$date_to'";
      }else{
         $where_date='';
         $where_createdate='';
      }

      if(!empty($merchant_id)){
         $where_merchant_id = " and m.merchant_id='$merchant_id'";
      }else{
         $where_merchant_id = "";
      }
      if($kids_order_value){
         $kids_count = "COUNT(DISTINCT(o.kid_id)) as kids_count,";
      }else{
         $kids_count = "";
      }

      if(!empty($request_type)){
         $query = $this->db->query("SELECT 
                           m.category,
                           count(m.category) as category_count,
                           sum(o.price) as total
                           FROM tt_orders as o 
                           JOIN tt_menus m ON o.menu_id=m.id 
                           JOIN tt_kids as k ON k.id=o.kid_id
                           JOIN tt_parents as p ON p.id=k.parent_id
                           JOIN gbl_statuses as g ON g.id=o.status
                           WHERE  o.status != 14 $where_merchant_id
                           $where_date GROUP BY m.category");

         if($request_type=='time'){
              $query = $this->db->query("SELECT 
                           o.createdate,
                           SUBSTR(o.createdate, 11, 4) as sale_time,
                           count(o.id) as sales_count,
                           sum(o.price) as total
                           FROM tt_orders as o 
                           JOIN tt_menus m ON o.menu_id=m.id 
                           JOIN tt_kids as k ON k.id=o.kid_id
                           JOIN tt_parents as p ON p.id=k.parent_id
                           JOIN gbl_statuses as g ON g.id=o.status
                           WHERE o.status != 14 $where_merchant_id
                           $where_date GROUP BY SUBSTR(o.createdate, 11, 4) ");
         }

         if($request_type=='dayofweek'){
              $query = $this->db->query("SELECT 
                           o.createdate,
                           DAYOFWEEK(o.createdate) AS sale_time,
                           count(o.id) as sales_count,
                           sum(o.price) as total
                           FROM tt_orders as o 
                           JOIN tt_menus m ON o.menu_id=m.id 
                           JOIN tt_kids as k ON k.id=o.kid_id
                           JOIN tt_parents as p ON p.id=k.parent_id
                           JOIN gbl_statuses as g ON g.id=o.status
                           WHERE o.status != 14 $where_merchant_id
                           $where_date GROUP BY DAYOFWEEK(o.createdate)");
         }

       if($request_type=='daily'){

            $query = $this->db->query("SELECT 
                           $kids_count
                           SUBSTR(o.createdate, 1, 10) AS day,
                           count(o.id) as sales_count,
                           sum(o.price) as total,
                           o.createdate
                           FROM tt_orders as o 
                           JOIN tt_menus m ON o.menu_id=m.id 
                           JOIN tt_kids as k ON k.id=o.kid_id
                           JOIN tt_parents as p ON p.id=k.parent_id
                           JOIN gbl_statuses as g ON g.id=o.status
                           WHERE o.status != 14 $where_merchant_id
                           $where_createdate GROUP BY SUBSTR(o.createdate, 1, 10)  DESC LIMIT 31");
      }

      if($request_type=='monthly'){
              $query = $this->db->query("SELECT 
                           $kids_count
                           SUBSTR(o.createdate, 1, 7) AS month,
                           count(o.id) as sales_count,
                           sum(o.price) as total,
                           o.createdate
                           FROM tt_orders as o 
                           JOIN tt_menus m ON o.menu_id=m.id 
                           JOIN tt_kids as k ON k.id=o.kid_id
                           JOIN tt_parents as p ON p.id=k.parent_id
                           JOIN gbl_statuses as g ON g.id=o.status
                           WHERE o.status != 14 $where_merchant_id
                           $where_createdate GROUP BY SUBSTR(o.createdate, 1, 7) DESC");
         }

      if($request_type=='merchant'){
              $query = $this->db->query("SELECT 
                           count(o.id) as sales_count,
                           sum(o.price) as total,
                           o.createdate,
                           mc.name as merchant
                           FROM tt_orders as o 
                           JOIN tt_menus m ON o.menu_id=m.id 
                           JOIN tt_kids as k ON k.id=o.kid_id
                           JOIN tt_merchants as mc ON k.merchant_id=mc.id
                           JOIN tt_parents as p ON p.id=k.parent_id
                           JOIN gbl_statuses as g ON g.id=o.status
                           WHERE o.status != 14 $where_merchant_id
                           $where_createdate GROUP BY k.merchant_id DESC");
         }



         
      }else{
         $query = $this->db->query("SELECT k.id as kid_id, m.description, 
                           o.id,o.menu_id, 
                           m.label,
                           ROUND((o.price),2) as price,
                           o.date, 
                           k.first_name,
                           k.last_name,
                           p.first_name as p_first_name,
                           p.last_name as p_last_name,
                           g.name as status,
                           m.category
                           FROM tt_orders as o 
                           JOIN tt_menus m ON o.menu_id=m.id 
                           JOIN tt_kids as k ON k.id=o.kid_id
                           JOIN tt_parents as p ON p.id=k.parent_id
                           JOIN gbl_statuses as g ON g.id=o.status
                           WHERE  o.status != 14 $where_merchant_id 
                           $where_date");
      }
      
      $result=$query->result_array();
  

      return $result;
   }


  function get_daily_sales_stats($date_from,$date_to,$merchant_id,$coin_price){

      if(!empty($date_from)){
         $where_date="AND SUBSTR(`o`.`createdate`,1,10)>='$date_from' AND SUBSTR(`o`.`createdate`,1,10)<='$date_to'";
      }else{
         $where_date='';
      }

      if(!empty($coin_price)){
         if($coin_price==15){
            $where_coin=" AND o.price >= '0' and o.price <'30'";
         }
         
         if($coin_price==30){
            $where_coin=" AND o.price >= '$coin_price' and o.price <'50'";
         }

         if($coin_price==50){
            $where_coin=" AND o.price >= '$coin_price'";
         }

         
      }else{
         $where_coin='';
      }

      $query = $this->db->query("SELECT m.price,count(o.menu_id) as order_count, SUBSTR(`o`.`createdate`,1,10), m.label
            FROM tt_orders as o 
            JOIN tt_menus m ON o.menu_id=m.id
            WHERE m.merchant_id='$merchant_id' 
            AND o.status != 14 $where_date $where_coin GROUP BY o.menu_id");
      $result = $query->result_array();
      
      return $result;
   }

}