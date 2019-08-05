<?php

class News_model extends CI_Model {

public function __construct()
{
    $this->load->model('customer_model');
    $this->load->model('order_model');
    parent::__construct();
}

function get_all_usergroup(){

    $query = $this->db->query("SELECT * FROM `aauth_groups`");

    return  $query->result();

 }
function get_usergroup_by_id($primary_key){

    $usergroup_id ='';
    $query = $this->db->query("SELECT * FROM `news` WHERE `id`='$primary_key'");
    $result1 = $query->result();

    foreach ($result1 as $item) {
     $usergroup_id = $item->default_user_group;
     }


    $query1 = $this->db->query("SELECT * FROM `aauth_groups`  WHERE `id` ='$usergroup_id'");

    return $query1->row_array();
 }
    
function get_usergroup_by_id_($default_user_group){
    $value='';
    $query = $this->db->query("SELECT * FROM `aauth_groups` WHERE `id`= ?", array($default_user_group));
    $result = $query->result_array();
     foreach ($result as $key => $item) {
         $value = $item['name'];

      }

      return $value;
}
function get_news_byid($news_id,$get_defult_page){
   
    if(!empty($news_id)){
        $where_value = "`id` = '$news_id' AND ";
    }else{
        $where_value ="`id` >= '1' AND ";
    }
    if(!empty($get_defult_page)){
        $where_default = "`default_user_group`='$get_defult_page' OR `default_user_group`='All'";
    }else{
        $where_default ='';
    }
    $sql = "SELECT * FROM `news` WHERE $where_value $where_default";
    $query = $this->db->query($sql);
    return  $query->result();     
}
    
    
function get_all_news($from,$to){
    if(!empty($from)){
        $where_date = " AND `e`.`createdate` >= '$from 00:00:00' AND `e`.`createdate` <= '$to 00:00:00'";
    }else{
        $where_date='';
    }
    $sql = "SELECT `a`.`name` as username, `n`.`heading`,`e`.`createdate`,`n`.`body` FROM `news` as `n` JOIN `event_log` as `e` ON `n`.`id` = `e`.`value` JOIN `aauth_users` as `a` ON `a`.`id` = `e`.`user_id` WHERE `e`.`category` ='news' $where_date";
    $query = $this->db->query($sql);
    return  $query->result();   
}

function get_all_news_stats($from,$to){
    if(!empty($from)){
        $where_date = " AND `e`.`createdate` > '$from 00:00:00' AND `e`.`createdate` < '$to 00:00:00'";
    }else{
        $where_date='';
    }

    $sql = "SELECT `n`.`heading`,`e`.`createdate`, count(`e`.`user_id`) as news_count FROM `news` as `n` JOIN `event_log` as `e` ON `n`.`id` = `e`.`value` JOIN `aauth_users` as `a` ON `a`.`id` = `e`.`user_id` WHERE `e`.`category` ='news' $where_date GROUP BY `e`.`value`";
    $query = $this->db->query($sql);

    return  $query->result();   
}
   
    
    
function news_csv($from,$to){
    if(!empty($from)){
    $where_date = " AND `e`.`createdate` > '$from 00:00:00' AND `e`.`createdate` < '$to 00:00:00'";
    }else{
    $where_date='';
    }
    $sql = "SELECT `a`.`name` as username, `n`.`heading`,`e`.`createdate`,`n`.`body` FROM `news` as `n` JOIN `event_log` as `e` ON `n`.`id` = `e`.`value` JOIN `aauth_users` as `a` ON `a`.`id` = `e`.`user_id` WHERE `e`.`category` ='news' $where_date";
    return $sql;
}

    
    
function get_heading($newsid){
$value='';
$query = $this->db->query("SELECT * FROM `news` WHERE `id`= ?", array($newsid));
$result = $query->result_array();

foreach ($result as $key => $item) {
     $value = $item['heading'];

  }

  return $value;
}

function specified_news(){
    $user_info = $this->aauth->get_user();   
    $get_defult_page = get_defult_page($this->user);

    $user_id = $user_info->id;
    $all = 'All';
    $comma =',';
    $sql = "SELECT * FROM `news` as `n` JOIN `event_log` as `e` ON `n`.`id` = `e`.`value` JOIN `aauth_users` as `a` ON `a`.`id` = `e`.`user_id` WHERE `e`.`category` ='news' AND `n`.`default_user_group` IN ('$get_defult_page' $comma '$all') AND `e`.`user_id`='$user_id'";
    $query = $this->db->query($sql)->result();
    $comma ='';
    $value='';
    foreach($query as $news){
        $value .= $comma.$news->value;
        $comma =',';
    }
    if(!empty($value)){
       $in =  "`id` NOT IN($value) AND";
    }else{
        $in ='';
    }
    $sql2 = "SELECT * FROM `news` WHERE  $in `default_user_group` IN ('$get_defult_page' $comma '$all')  GROUP BY `createdate` DESC";
    $query2 = $this->db->query($sql2); 
   
    return  $query2->result(); 
}

    
    
}?>