
<?php

class Fridge_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
      $this->load->model('customer_model');
      $this->load->model('event_model');
      $this->load->model('global_app_model');
   } 

   public function get_fridge_from_code($fridge_unit_code){

      $query = $this->db->query("SELECT * FROM fridges WHERE fridge_unit_code = ?", array(trim($fridge_unit_code)));
      $fridge = $query->row_array();    
      return $fridge;
   }

   public function get_log_info($log_id){

      $query = $this->db->query("SELECT * FROM fridge_log WHERE id = ?", array(trim($log_id)));
      $log = $query->row_array();
      return $log;
   }

   public function get_fridge_types(){

      $query = $this->db->query("SELECT t.id, t.name, b.name as 'brand' FROM fridge_types t, brands b WHERE b.id = t.brand_id");
      $types = $query->result_array();
      return $types;
   }

   public function get_user_id_from_imei($imei){

      $query = $this->db->query("SELECT user_id FROM aauth_user_imei WHERE imei = ?", array(trim($imei)));
      $user = $query->row_array();    
      if($user){
         return $user['user_id'];
      }else{
         return false;
      }
   }

   function add_fridge($data){
      $this->load->model('app_model');

      $clean_data = $this->app_model->strip_db_rejects("fridges", $data);
      $clean_data['createdate'] = date("Y-m-d H:i:s");

      if(!$this->db->insert("fridges", $clean_data)){
          return  false;
      }

      return $this->db->insert_id();
      
   }

   function update_fridge($data){
      $this->load->model('app_model');

      $clean_data = $this->app_model->strip_db_rejects("fridges", $data);

      $this->db->where('fridge_unit_code', $clean_data['fridge_unit_code']);
      unset($clean_data['fridge_unit_code']);
      
      if(!$this->db->update("fridges", $clean_data)){
          return  false;
      }

      return true;
      
   }

   function is_fridge_unique($fridge_unit_code){

      $query = $this->db->query("SELECT id FROM fridges WHERE fridge_unit_code = ?", array(trim($fridge_unit_code)));
      if($query->num_rows() != 0){
         return false;
      }else{
         return true;
      }
   }

   function add_fridge_log($app, $fridge_id, $user_id){
      if($this->db->query("INSERT INTO fridge_log (app, fridge_id, user_id, start) VALUES (?,?,?,NOW())", array(trim($app), $fridge_id, $user_id))){
         return $this->db->insert_id();
      }
      return false;
   }

   function add_fridge_temp($data){
      if($this->db->insert("fridge_temperatures", $data)){
         return $this->db->insert_id();
      }
      return false;
   }

   function add_fridge_location($data){
      if($data['long'] != 0 && $data['lat'] != 0){
         if($this->db->insert("fridge_locations", $data)){
            return $this->db->insert_id();
         }
      }
      return false;
   }

   function frdge_sql(){

   	$query ="SELECT f.id, f.location_name,b.name as brand,r.name as region,
   			f.long, f.lat,f.fridge_unit_code,p.name as province, t.name as fridge_type, f.street
   			FROM fridges as f, fridge_types as t, brands as b, regions  as r, provinces as p WHERE
   			 t.id=f.fridge_type
   			AND t.brand_id=b.id 
   			AND r.id=f.region_id 
   			AND p.id=f.province";

   
   	return $query;
   }
   function get_fridges($province_id, $brand_id='',$fridge_type_id,$fridge_uinit_code='',$fridge_id=''){
      $where_province='';
      if(!empty($province_id)){
         $where_province=" AND f.province='$province_id'";
      }

      $where_brand='';
      if(!empty($brand_id)){
         $where_brand =" AND t.brand_id='$brand_id'";
      }

      $where_fridge_type='';
      if(!empty($fridge_type_id)){
         $where_fridge_type=" AND t.id='$fridge_type_id'";

      }
      $where_uinit_code='';
      if(!empty($fridge_uinit_code)){
         $where_uinit_code=" AND f.fridge_unit_code LIKE '%$fridge_uinit_code%'";

      }  

      $where_id='';
      if(!empty($fridge_id)){
         $where_id=" AND f.id IN($fridge_id)";

      }


   	  $sql="SELECT f.id, 
               r.name as region,
               f.long,
               f.lat,

               (SELECT temp 
               FROM fridge_temperatures 
               WHERE fridge_id = f.id order by createdate desc limit 1) 
               as 'temp',

               (SELECT `long` 
               FROM fridge_locations 
               WHERE fridge_id = f.id order by createdate desc limit 1) 
               as 'long_latest',

               (SELECT `lat`
               FROM fridge_locations 
               WHERE fridge_id = f.id order by createdate desc limit 1) 
               as 'lat_latest',

               f.fridge_unit_code,
               f.location_name,
               p.name as province, 
               t.name as fridge_type,
               f.street,
               f.createdate
               
               FROM 
               fridges as f, 
               fridge_types as t, 
               regions  as r, 
               provinces as p 
               WHERE  t.id=f.fridge_type
               AND r.id=f.region_id 
               AND p.id=f.province
               $where_province $where_brand $where_fridge_type $where_uinit_code $where_id
               ORDER BY f.id desc";

   		$query=$this->db->query($sql);
        
   		$data['result'] = $query->result_array();

         $data['query'] =$sql;
         return $data;

   }



   function get_fridge_street($fridge_id){
      

         $query=$this->db->query($this->frdge_sql()." AND f.id='$fridge_id'");

         return $query->row_array();

   }

   function get_fridge_street_history($fridge_id,$log_id){
   	
         $sql="SELECT loc.long, 
            loc.lat, 
            f.fridge_unit_code,
            f.street,
            f.location_name,
            tm.temp,
            loc.createdate,
            r.name as region,
            tp.name as fridge_type,
            p.name as province
            FROM 
            fridge_locations as loc
            JOIN fridges as f ON f.id=loc.fridge_id
            JOIN provinces as p ON p.id=f.province
            JOIN regions as r ON r.id=f.region_id
            JOIN fridge_types as tp ON tp.id=f.fridge_type
            JOIN fridge_temperatures as tm ON tm.log_id=loc.log_id
            WHERE f.id='$fridge_id' 
            AND loc.log_id='$log_id'";

   		$query=$this->db->query($sql);

   		return $query->row_array();

   }

   function get_fridges_locations($brand_id=''){
         $where_brand='';
         if(!empty($brand_id)){
            $where_brand=" AND t.brand_id='$brand_id'";
         }
   
         $query=$this->db->query("SELECT 
            f.id, 
            f.location_name,
            b.name as brand,
            r.name as region,
            -- f.long, 
            -- f.lat,
            f.fridge_unit_code,
            p.name as province, 
            t.name as fridge_type, 
            t.capacity, 
            t.expected_temp, 
            t.tolerance, 
            t.considered_off,
            f.street,
            (SELECT temp FROM fridge_temperatures WHERE fridge_id = f.id order by createdate desc limit 1) as 'temp',
            (SELECT fridge_locations.long FROM fridge_locations WHERE fridge_id = f.id order by createdate desc limit 1) as 'long',
            (SELECT lat FROM fridge_locations WHERE fridge_id = f.id order by createdate desc limit 1) as 'lat'
            FROM fridges as f, 
            fridge_types as t, 
            brands as b, 
            regions  as r, 
            provinces as p 
            WHERE
             t.id=f.fridge_type
            AND t.brand_id=b.id 
            AND r.id=f.region_id 
            AND p.id=f.province
            $where_brand");

         return $query->result_array();

   }  

 

   function get_fridge_type(){
         $query=$this->db->query("SELECT * FROM fridge_types WHERE 1");

         return $query->result_array();
   } 

    function get_fridge_type_by_id($id){
         $query=$this->db->query("SELECT * FROM fridge_types WHERE id='$id'");

         return $query->row_array();
   }


   function get_fridge_logs($fridge_id){
       $sql="SELECT 
            f.id, 
            f.location_name,
            b.name as brand,
            r.name as region,
            f.long, 
            f.lat,
            f.fridge_unit_code,
            p.name as province, 
            t.name as fridge_type, 
            t.capacity, 
            t.expected_temp, 
            t.tolerance, 
            t.considered_off,
            f.street,
            tm.createdate,
            tm.temp as temperature,
            p.name
            FROM fridge_log as l,
            fridges as f, 
            provinces as p,
            fridge_temperatures as tm,
            fridge_types as t, 
            brands as b, 
            regions  as r
            WHERE
             t.id=f.fridge_type
            AND t.brand_id=b.id 
            AND r.id=f.region_id 
            AND p.id=f.province
            AND f.id=tm.fridge_id
            AND f.province=p.id
            AND f.id='$fridge_id'
            GROUP BY tm.createdate DESC";

         $query=$this->db->query($sql);
        
         return $query->result_array();
       

   }

      function get_daily_temperatures($fridge_id){

         $query=$this->db->query("SELECT `tm`.`createdate` as dates,
            t.capacity, 
            t.expected_temp, 
            t.tolerance, 
            t.considered_off,
            tm.temp as fridge_temp
            FROM fridges as f,
            fridge_types as t, 
            brands as b, 
            regions  as r,
            fridge_temperatures as tm
            WHERE t.id=f.fridge_type
            AND t.brand_id=b.id 
            AND r.id=f.region_id 
            AND tm.fridge_id=f.id
            AND f.id='$fridge_id' GROUP BY `tm`.`createdate` DESC LIMIT 25");

         return $query->result_array();

   }

     function get_current_location($fridge_id){
   
         $query=$this->db->query("SELECT 
            f.id, 
            f.location_name,
            b.name as brand,
            r.name as region,
            -- loc.long, 
            -- loc.lat,
            f.fridge_unit_code,
            p.name as province, 
            t.name as fridge_type, 
            t.capacity, 
            t.expected_temp, 
            t.tolerance, 
            t.considered_off,
            f.street,
            (SELECT temp FROM fridge_temperatures WHERE fridge_id = f.id order by id desc limit 1) as 'temp',
            (SELECT lat FROM fridge_locations WHERE fridge_id = f.id order by id desc limit 1) as 'lat',
            (SELECT loc.long FROM fridge_locations as loc WHERE loc.fridge_id = f.id order by loc.id desc limit 1) as 'long',
            (SELECT createdate FROM fridge_temperatures WHERE fridge_id = f.id order by id desc limit 1) as 'createdate'

            FROM fridges as f, 
            -- fridge_locations as loc, 
            fridge_types as t, 
            brands as b, 
            regions  as r, 
            provinces as p 
            WHERE
             t.id=f.fridge_type
            AND t.brand_id=b.id 
            AND r.id=f.region_id 
            -- AND loc.fridge_id=f.id 
            AND p.id=f.province
            AND f.id='$fridge_id'
            ");

         return $query->row_array();

   }

  
  function get_fridges_deliveries($fridge_id='',$brand_id=''){
        
         $query=$this->db->query("SELECT 
            loc.id, 
            f.location_name,
            b.name as brand,
            r.name as region,
            loc.long, 
            loc.lat,
            loc.createdate,
            f.fridge_unit_code,
            p.name as province, 
            t.name as fridge_type, 
            t.capacity, 
            t.expected_temp, 
            t.tolerance, 
            t.considered_off,
            f.street,
            tm.temp
            FROM fridges as f, 
            fridge_types as t, 
            fridge_locations as loc, 
            brands as b, 
            regions  as r, 
            provinces as p,
            fridge_temperatures as tm
            WHERE
             t.id=f.fridge_type
            AND t.brand_id=b.id 
            AND r.id=f.region_id 
            AND p.id=f.province
            AND f.id=tm.fridge_id
            AND f.id=tm.fridge_id
            AND t.brand_id='$brand_id'
            AND loc.fridge_id='$fridge_id'");

         return $query->result_array();

   }

   function get_fridge_locations($id,$date_from,$date_to){
      if(!empty($date_from) && !empty($date_to)){
         $where_date="AND loc.createdate>='$date_from' AND loc.createdate<='$date_to'";
      }else{
         $where_date='';
      }

      $query=$this->db->query("SELECT
            tp.expected_temp,
            tp.tolerance,
            tp.considered_off,
            f.id,
            loc.log_id,
            f.fridge_unit_code,
            loc.long,
            loc.lat,
            f.location_name,
            p.name as province,
            loc.createdate,
            f.street, 
            tp.name as fridge_type, 
            r.name as region,
            tm.temp
            FROM fridge_locations as loc JOIN fridges as f ON loc.fridge_id=f.id 
            JOIN provinces as p ON p.id=f.province
            JOIN regions as r ON r.id=f.region_id
            JOIN fridge_log as log ON loc.log_id=log.id
            JOIN fridge_types as tp ON tp.id=f.fridge_type 
            JOIN fridge_temperatures as tm ON tm.log_id=log.id
            WHERE loc.fridge_id='$id'
            $where_date LIMIT 200");
      return $query->result_array();
   }
 
 function get_fridges_daily_temperature($id,$date_from,$date_to){

  if(!empty($date_from) && !empty($date_to)){
         $where_date="AND l.start>='$date_from' AND l.start<='$date_to'";
   }else{
         $where_date='';
   }


     $sql="SELECT
         f.fridge_unit_code,
         f.location_name,
         tp.name as fridge_type,
         f.street,
         f.id,
         r.name as region,
         p.name as province,
         SUBSTR(l.start,1,10) as createdate,
         tp.expected_temp,
         tp.tolerance,
         tp.considered_off,

         (
         SELECT temp FROM fridge_temperatures WHERE  
         SUBSTR(createdate,1,10) = SUBSTR(l.start,1,10) AND fridge_id='$id' ORDER BY id DESC limit 1
         ) as 'temp'

         FROM fridge_log as l
         JOIN fridges as f ON l.fridge_id=f.id
         JOIN fridge_types as tp ON tp.id=f.fridge_type
         JOIN provinces as p ON p.id=f.province
         -- JOIN fridge_locations as loc ON loc.log_id=l.id
         JOIN regions as r ON r.id=f.region_id
         WHERE l.fridge_id='$id' $where_date 
         GROUP BY SUBSTR(start,1,10) 
         ORDER BY l.id 
         DESC LIMIT 7";

      $query=$this->db->query($sql);

      $data['result']=$query->result_array();
      $data['query']=$sql;

      return $data;
 }


 function get_fridges_monthly_temperature($id,$date_from,$date_to){
 if(!empty($date_from) && !empty($date_to)){
         $where_date="AND l.start>='$date_from' AND l.start<='$date_to'";
   }else{
         $where_date='';
   }

       $query=$this->db->query("SELECT
            l.id,
            SUBSTR(l.start,1,7) as createdate,
            ty.expected_temp,
            ty.tolerance,
            ty.considered_off,
            (SELECT temp FROM fridge_temperatures WHERE  
            SUBSTR(createdate,1,7) = SUBSTR(l.start,1,7) AND fridge_id='$id' ORDER BY id DESC limit 1) as 'temp'

            FROM fridge_log as l
            JOIN fridges as f ON l.fridge_id=f.id
            JOIN fridge_types as ty ON ty.id=f.fridge_type
            JOIN provinces as p ON p.id=f.province
            JOIN fridge_locations as loc ON loc.log_id=l.id
            JOIN regions as r ON r.id=f.region_id
            WHERE l.fridge_id='$id' $where_date GROUP BY SUBSTR(start,1,7) ORDER BY l.id DESC LIMIT 7"
         );

   
      return $query->result_array();
 }

  function get_recent_locations($fridge_id, $request='', $date_from, $date_to){

      $limit='LIMIT 20';
      $where_long_lat='';
      $lEFT="";
      $where_date='';
      
      if(!empty($date_from) && !empty($date_to)){
         $where_date=" and loc.createdate >='$date_from' and loc.createdate<='date_to'";
      }

      if($request=='maps'){
         $order_by="loc.createdate";
         $where_long_lat=" AND  loc.lat!=0 AND loc.long!=0";
         $lEFT="LEFT ";
         if(!empty($date_from) && !empty($date_to)){
            $where_date=" and loc.createdate >='$date_from' and loc.createdate<='date_to'";
         }
    
      } 
      if($request=='table'){
         $order_by="tm.createdate";
         $limit="";

      }
      
      if($request=='chart' ){
          $order_by="tm.createdate";
      } 

      if($request=="single row"){
          $order_by="tm.createdate";
          $limit="LIMIT 1";
      } 

    
      $query=$this->db->query("SELECT 
                           tm.log_id,
                           f.id, 
                           f.location_name,
                           b.name as brand,
                           r.name as region,
                           loc.long, 
                           loc.lat,
                           f.fridge_unit_code,
                           p.name as province, 
                           t.name as fridge_type, 
                           t.capacity, 
                           t.expected_temp, 
                           t.tolerance, 
                           t.considered_off,
                           f.street,
                           loc.createdate,
                           tm.createdate as temp_createdate,
                           tm.temp
                           FROM fridges as f  
                           JOIN fridge_types as t ON t.id=f.fridge_type
                           JOIN fridge_log as fl ON fl.fridge_id=f.id
                           JOIN fridge_temperatures as tm ON fl.id=tm.log_id AND tm.fridge_id=f.id
                           $lEFT JOIN fridge_locations as loc ON loc.log_id=fl.id AND loc.fridge_id=f.id
                           LEFT JOIN brands as b ON t.brand_id=b.id 
                           LEFT JOIN regions  as r ON r.id=f.region_id 
                           LEFT JOIN provinces as p ON p.id=f.province
                           WHERE f.id='$fridge_id' $where_long_lat $where_date
                           GROUP BY fl.id 
                           ORDER BY $order_by DESC 
                           $limit");

         if($request=="single row"){
            return $query->row_array();
         }else{
            return $query->result_array();
         }
         
   }

}
