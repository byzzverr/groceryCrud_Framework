<?php
error_reporting(0); // Turn off all error reporting

class Crud_distributor_products_model extends grocery_CRUD_Model  {
 
    function join_relation($field_name , $related_table , $related_field_title, $where="")
    {
        
    	
		$related_primary_key = $this->get_primary_key($related_table);
       
		if($related_primary_key !== false)
		{
			$unique_name = $this->_unique_join_name($field_name);
			$this->db->join( $related_table.' as '.$unique_name , "$unique_name.$related_primary_key = {$this->table_name}.$field_name",'left');

			$this->relation[$field_name] = array($field_name , $related_table , $related_field_title);

			return true;
		}else{

			if($related_table == 'prod_dist_price'){
               
              
				$unique_name = $this->_unique_join_name($field_name);

		    	$q_add = '';
		    	foreach ($where as  $wh) {
		    		$q_add .= " AND $unique_name.$key = '$wh' ";
		    	}
                $product_id  = $this->product_model->get_dist_product_1($distributor_id,$status);
                echo $status;
				$this->db->join( $related_table.' as '.$unique_name , "$unique_name.product_id = {$this->table_name}.id $q_add",'left');
                $this->db->where_in("$unique_name.product_id",$product_id);
             
               
				$this->relation[$field_name] = array($field_name , $related_table , $related_field_title);

				return true;
			}
		}
    	return false;
    }

}