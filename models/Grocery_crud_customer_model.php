<?php
class Grocery_crud_customer_model  extends grocery_CRUD_Model{
  
	function join_relation($field_name , $related_table, $related_field_title, $where="")
    {
  //   	$related_primary_key = $this->get_primary_key('traders');
  //   	if($related_primary_key !== false){

		// 	$unique_name = $this->_unique_join_name('trader_id');
		// 		$this->db->join( 'traders'.' as '.$unique_name , "$unique_name.$related_primary_key = {$this->table_name}.trader_id",'left');
			
		// 		$this->relation[$field_name] = array($field_name , $related_table , $related_field_title);

		// 		return true;
		// }else{
		// 	return false;
		// }

	}
}

	?>