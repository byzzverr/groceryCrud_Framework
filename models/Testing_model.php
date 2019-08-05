<?php

class Testing_model extends CI_Model {

   public function __construct()
   {
      parent::__construct();
   }

   function fetch_api_test_methods(){
      $query = $this->db->query("SELECT * FROM test_methods WHERE status = 'Enabled'");
       return $query->result_array();
   }
    public function select()
    {

        $query = $this->db->order_by('controller', 'asc');
        $query = $this->db->order_by('method_name', 'asc');
        $query = $this->db->get('test_methods');

        return $query;
    }
    public function select_by_id()
{

    if(isset($_GET['id'])){
        $id=$_GET['id'];
        $this->db->where('id', "$id");
        $query = $this->db->get('test_methods');
        return $query;
    }
}
    function store_test_results($data){
   	  $this->db->insert('test_results', $data);
      return $this->db->insert_id();
   }
}
