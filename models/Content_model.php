<?php

class Content_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
   }

   public function get_faqs(){
    
      $query = $this->db->query("SELECT * FROM faqs WHERE enabled = ?", array(1));
      $result = $query->result_array();
      return $result;
   }
}