<?php

class Example_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
   }

   public function get_row_by_id($table_name, $id){
    
      $query = $this->db->query("SELECT * FROM $table_name WHERE id = '$id'");
      $result = $query->result_array();
      return $result;
   }

}
