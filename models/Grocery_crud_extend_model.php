<?php
class Grocery_crud_extend_model  extends grocery_CRUD_Model{
  
	function join_relation($field_name , $related_table , $related_field_title, $where="")
    {
		

		$related_primary_key = $this->get_primary_key($related_table);
		$distributor_order_unique_name=$this->_unique_join_name('id');
		$customers_table=$this->_unique_join_name('customer_id');

		if($related_primary_key !== false){

			if($related_table=='customers'){
				$unique_name = $this->_unique_join_name($field_name);
				$this->db->join( $related_table.' as '.$customers_table , "$customers_table.$related_primary_key = orders.$field_name",'left');
			
				$this->relation[$field_name] = array($field_name , $related_table , $related_field_title);

				return true;
			}


			if($related_table=='regions'){
				$unique_name = $this->_unique_join_name($field_name);
				$this->db->join( $related_table.' as '.$unique_name , "$unique_name.$related_primary_key = $customers_table.$field_name",'left');
			
				$this->relation[$field_name] = array($field_name , $related_table , $related_field_title);

				return true;
			}
			if($related_table=='provinces'){
				$unique_name = $this->_unique_join_name($field_name);
				$this->db->join( $related_table.' as '.$unique_name , "$unique_name.$related_primary_key = $customers_table.$field_name",'left');
			
				$this->relation[$field_name] = array($field_name , $related_table , $related_field_title);

				return true;
			}

			if($related_table=='traders'){
				$unique_name = $this->_unique_join_name($field_name);
				$this->db->join( $related_table.' as '.$unique_name , "$unique_name.$related_primary_key = $customers_table.$field_name",'left');
			
				$this->relation[$field_name] = array($field_name , $related_table , $related_field_title);

				return true;
			}
			// //$dist_order_field_name='';
			if($related_table=='distributor_orders'){
				
				
				$unique_name = $this->_unique_join_name($field_name);
				$distributor_id=$this->spazapp_model->get_logged_in_distributor_id()['user_link_id'];
				
				if(is_numeric($this->uri->segment(3)) && $this->uri->segment(3)>0){
					$distributor_id = $this->uri->segment(3);
				}

				$this->db->join( $related_table.' as '.$unique_name , "$unique_name.order_id = {$this->table_name}.$field_name AND $unique_name.distributor_id = " . $distributor_id);

				$this->relation[$field_name] = array($field_name , $related_table , $related_field_title);
			
				return true;
			}

			$unique_name_2 = $this->_unique_join_name('id');

			if($related_table=='payment_types'){
				$unique_name = $this->_unique_join_name($field_name);
				$this->db->join( $related_table.' as '.$unique_name , "$unique_name.$related_primary_key = {$this->table_name}.$field_name");
			
				$this->relation[$field_name] = array($field_name , $related_table , $related_field_title);

				return true;
			}

			if($related_table=='gbl_statuses'){

				$unique_name = $this->_unique_join_name($field_name);
				$this->db->join( $related_table.' as '.$unique_name , "$unique_name.$related_primary_key = $unique_name_2.$field_name");
			
				$this->relation[$field_name] = array($field_name , $related_table , $related_field_title);

				return true;
			}

			// if($related_table=='distributors'){

			// 	$unique_name = $this->_unique_join_name($field_name);
			// 	//$distributor_unique_name = $this->_unique_join_name('');
			// 	$this->db->join( $related_table.' as '.$unique_name , "$unique_name.$related_primary_key = $unique_name_2.$field_name");
			
			// 	$this->relation[$field_name] = array($field_name , $related_table , $related_field_title);

			// 	return true;
			// }

			//$this->db->where('do.distributor_id',1);
	        
				
		}else{
            
            $distributor_id = $this->uri->segment(4);
            
		}

		
    	return false;
    }

    
}
?>