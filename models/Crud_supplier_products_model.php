<?php
error_reporting(0); // Turn off all error reporting

class Crud_supplier_products_model extends grocery_CRUD_Model  {
 
    function join_relation($field_name , $related_table , $related_field_title, $where="")
    {

    	
		$related_primary_key = $this->get_primary_key($related_table);

		if($related_primary_key !== false)
		{
			$unique_name = $this->_unique_join_name($field_name);
			$this->db->join( $related_table.' as '.$unique_name , "$unique_name.$related_primary_key = prod_dist_price.$field_name",'left');

			$this->relation[$field_name] = array($field_name , $related_table , $related_field_title);

			return true;
		}else{
                
            $distributor_id = $this->uri->segment(4);
			if($related_table == 'prod_dist_price'){
             
				$unique_name = $this->_unique_join_name($field_name);
                
		    	$q_add = '';
		    	foreach ($where as  $key => $wh) {
		    		$q_add .= " AND pr.$key = '$wh' ";
		    	}
                
                $sup_where = '';
                $comma = '';
                $suppliers = $this->spazapp_model->get_suppliers_per_distributor($distributor_id);

                foreach ($suppliers as $supplier) {
                    $sup_where .= $comma."'".humanize($supplier['supplier_id'])."'";
                    $comma = ',';
                }

                
				$this->db->join( $related_table.' as '.$unique_name , "$unique_name.product_id = {$this->table_name}.id $q_add",'left');
                
                if($_POST['stock_status']=1){
                      $stock_status = 0;
                }
                if($_POST['stock_status']=2){
                      $stock_status = 1;
                }
                if($_POST['stock_status']>0){
				    $this->db->where("$unique_name.out_of_stock = ",$stock_status);
                }
                
                $this->db->where_in("{$this->table_name}.supplier_id = ",str_replace("/","".$sup_where));
                $this->db->where("$unique_name.distributor_id = ",$distributor_id);
                
               
				$this->relation[$field_name] = array($field_name , $related_table , $related_field_title);

				return true;
			}
            
            
		}
    	return false;
    }

}