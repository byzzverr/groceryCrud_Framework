<?php

class Product_model extends CI_Model {

   public function __construct()
   {
      $this->load->model('customer_model');
      $this->load->model('product_model');
      $this->load->model('spazapp_model');
   
      parent::__construct();
   }

  function insert_product($product){

    if(!isset($product['barcode']) || !isset($product['name']) || !is_numeric($product['barcode'])){
      return false;
    }

    $product_id = $this->does_product_already_exist($product['barcode'], $product['name']);
    $product = $this->strip_db_rejects('products', $product);
    
    if(!$product_id){
        if(is_numeric($product['barcode']) && $product['barcode'] != '' && strlen($product['barcode']) >= 10){
          $this->db->insert("products", $product);
          $product_id = $this->db->insert_id();
        }
      }else{

        if(is_numeric($product['barcode'])  && $product['barcode'] != '' && strlen($product['barcode']) >= 10){
          //update barcode and category only

          if(isset($product['category_id']) && is_int($product['category_id'])){
            $product_barcode['category_id'] = $product['category_id'];
          }

          $product_barcode['barcode'] = $product['barcode'];
          $this->db->where("id", $product_id);
          $this->db->update("products", $product_barcode);
        }
      }

      return $product_id;
  }

  function does_product_already_exist($barcode, $name){
      $barcode = trim(addslashes($barcode));
      $sql = "SELECT id FROM products WHERE barcode_secondary = '$barcode' || barcode = '$barcode' || name = " . '"'.$name.'"';
      $res = $this->db->query($sql);
      if($res->num_rows() >= 1){
          return $res->row_array()['id'];
      }else{
          return false;
      }
  }

function getProduct_name($product_id){
      $query = $this->db->query("SELECT name FROM products WHERE id ='$product_id'");
      $result = $query->row_array();
      return $result;
   }

   function get_products_no_image(){

      $query = $this->db->query("SELECT 
                                 id, stock_code, barcode, name
                                 FROM products
                                 WHERE
                                 picture = '' ORDER BY createdate ASC ");
   
      return $query->result_array();
   }

   function get_product_special($product_id, $distributor_id){
      $date = date("Y-m-d H:i:s");

      if(!empty($product_id)){

        $query = $this->db->query("SELECT 
                                   unit_price, shrink_price, case_price
                                   FROM specials
                                   WHERE
                                   start_date >= '$date' AND end_date < '$date' 
                                   AND product_id IN($product_id) 
                                   AND distributor_id = '$distributor_id'
                                   ");
     
        return $query->row_array();
      }
      return false;
   }

   function get_product_prices($product_id, $distributor_id){
    
    $special = $this->get_product_special($product_id, $distributor_id);

    if($special){
      return $special;
    }else{

      $query = $this->db->query("SELECT 
                                 unit_price, shrink_price, case_price
                                 FROM prod_dist_price
                                 WHERE
                                 product_id = '$product_id'
                                 AND distributor_id ='$distributor_id'
                                 ");
      return $query->row_array();
    }

   } 

   function get_product_basic_info($product_id){

      $query = $this->db->query("SELECT 
                                 p.id, p.category_id,  p.name, p.stock_code, p.barcode, p.picture, p.qty, p.pack_size, p.units, p.supplier_id, s.company_name as 'supplier'
                                 FROM products as p 
                                 LEFT JOIN suppliers as s on p.supplier_id = s.id
                                 WHERE p.id = ?", array($product_id));
      return $query->row_array();
   }

   function get_marketing_products(){

      $query = $this->db->query("SELECT 
                                 p.id, p.name,p.barcode, p.picture, p.qty, p.pack_size, p.units, p.unit_price, p.sell_price, p.supplier_id, s.company_name as 'supplier'
                                 FROM products as p 
                                 LEFT JOIN suppliers as s on p.supplier_id = s.id");
      return $query->result_array();

   }
   
    function getRandomProductsByCategory($category_id, $limit=999) {

	$query = $this->db->query("SELECT DISTINCT r1.id, r1.name, r1.picture FROM products AS r1 JOIN
									   (SELECT CEIL(RAND() *
													 (SELECT MAX(id)
														FROM products)) AS id)
										AS r2
								 WHERE r1.id >= r2.id
								 ORDER BY r1.id ASC
								 LIMIT $limit");
 
    return $query->result_array();
  }


  function getAllCategories()
  {
      
      $query = $this->db->query("SELECT id,name FROM categories WHERE id IN (select category_id from products)  ORDER BY name");

    if($query->num_rows() > 0)
      return $query->result_array();
    else
      return null;
  }

    function update_product_pricing($product_id, $distributor_id, $prices)
    {

      $prices = $this->strip_db_rejects('prod_dist_price', $prices);

      if($this->does_product_price_already_exist($product_id, $distributor_id) && is_numeric($prices['shrink_price'])){

            $this->db->query("UPDATE prod_dist_price SET stock_code = ?, unit_price = ?, shrink_price = ?, case_price = ?, out_of_stock = ? 
            WHERE distributor_id = ? AND product_id = ?", 
              array($prices['stock_code'], $prices['unit_price'], $prices['shrink_price'], $prices['case_price'], $prices['out_of_stock'], $distributor_id, $product_id));

        }else{

          $this->db->query("INSERT INTO prod_dist_price (distributor_id, stock_code, product_id, unit_price, shrink_price, case_price, out_of_stock) VALUES (?,?,?,?,?,?,?)", 
              array($distributor_id, $prices['stock_code'], $product_id, $prices['unit_price'], $prices['shrink_price'], $prices['case_price'], $prices['out_of_stock']));
        
        }


    }

    function does_product_price_already_exist($product_id, $distributor_id){
        $res = $this->db->query("SELECT product_id FROM prod_dist_price WHERE product_id = $product_id AND $distributor_id");
        if($res->num_rows() >= 1){
            return true;
        }else{
            return false;
        }
    }

    function update_product_customer_type($product_id){

      if(!$this->does_product_customer_type_link($product_id, 1)){
        $this->db->query("INSERT INTO prod_customer_type_link (product_id, customer_type, priority) VALUES ($product_id, 1, 0)");
      }
    }

    function does_product_customer_type_link($product_id, $customer_type){
        $res = $this->db->query("SELECT product_id FROM prod_customer_type_link WHERE product_id = $product_id AND customer_type = $customer_type");
        if($res->num_rows() >= 1){
            return true;
        }else{
            return false;
        }
    }

    function get_product_distributors_in_region($region_id, $product_array){

      $sql = "SELECT ds.distributor_id
              FROM dist_supplier_link ds, dist_region_link dr, prod_dist_price pp, products p
              WHERE ds.distributor_id = dr.distributor_id AND
              p.id = pp.product_id AND
              ds.supplier_id = p.supplier_id AND
              pp.product_id IN (".implode(',', $product_array).") AND
              dr.region_id = $region_id";
              
       $query = $this->db->query($sql);
       $distributors = $query->result_array();

       $return = array();
       foreach ($distributors as $dist) {
         $return[$dist['distributor_id']] = $dist['distributor_id'];
       }

       return $return;
    }

     function get_products_for_region($customer_type, $region_id, $category_id, $specials=false, $product_id=false, $distributor_id=false){

      if($customer_type == 0 || $customer_type == ''){
        $customer_type = "";
      }else{
        $customer_type = "AND (pct.customer_type = $customer_type)";
      }
      
      $category_where = "AND p.category_id = $category_id";
      if($category_id == 0){
        $category_where = '';
      }

      $specials_where = '';
      if($specials){
        $specials_where = 'AND s.shrink_price IS NOT NULL';
      }

      $product_where = '';
      if($product_id){
        if(is_array($product_id)){
          $product_id = implode(",", $product_id);
        }
        $product_where = "AND p.id IN ($product_id)";
      }

      //if you want a particular disto or you want to remove a particular distro
      $distributor_where = '';
      if($distributor_id !== false){
        if($distributor_id > 0){
          $distributor_where = "AND ds.distributor_id = $distributor_id";
        }else{
          $distributor_id = $distributor_id*-1;
          $distributor_where = "AND ds.distributor_id != $distributor_id";
        }
      }

     $date = date("Y-m-d H:i:s");
     $sql = "SELECT p.*, su.markup, su.id as 'supplier_id', pdp.distributor_id, pdp.out_of_stock, pdp.unit_price, pdp.shrink_price, pdp.case_price, s.unit_price as 'special_unit_price', s.shrink_price as 'special_shrink_price', s.case_price as 'special_case_price', s.start_date as 'special_start', s.end_date as 'special_end'
              FROM `products` as p 
              JOIN suppliers as su ON su.id = p.supplier_id
              JOIN prod_dist_price pdp ON pdp.product_id = p.id
              JOIN prod_customer_type_link pct ON pct.product_id = p.id
              LEFT JOIN specials as s ON s.distributor_id = pdp.distributor_id AND p.id = s.product_id AND start_date <= '$date' AND end_date > '$date' AND s.status_id = 8 AND s.count > 5
              WHERE 
              pdp.distributor_id IN (
                                      SELECT ds.distributor_id
                                      FROM dist_supplier_link ds, dist_region_link dr
                                      WHERE ds.distributor_id = dr.distributor_id AND
                                      ds.supplier_id = su.id AND
                                      dr.region_id = $region_id
                                      $distributor_where
                                    )
              $customer_type
              AND pdp.out_of_stock = 0
              $product_where
              $category_where
              $specials_where
              order by p.name asc, pdp.unit_price desc";

      $params = array($region_id, $customer_type, $category_id);
      $query =  $this->db->query($sql, $params);

      $products = $query->result_array();

      $clean_products = array();

      if($products){

        foreach ($products as $key => $value) {

          unset($products[$key]['description']);
          unset($products[$key]['nutritional_info']);
          unset($products[$key]['directions_warnings']);

          if($value['special_shrink_price'] > 0 && $value['special_shrink_price'] < $value['shrink_price']){

              $products[$key]['is_special_now'] = 1;

              $now = time(); // or your date as well
              $your_date = strtotime($value['special_end']);
              $datediff = $now - $your_date;
              
              $products[$key]['special_end'] = floor($datediff/(60*60*24));

          }else{
            $products[$key]['is_special_now'] = 0;
          }

          //add the markup based on supplier
          if($value['markup'] > 0){
            
            $markup = "0.".$value['markup'];

            if($value['markup'] < 10){
              $markup = "0.0".$value['markup'];
            }

            $markup = 1+$markup;


            $products[$key]['special_shrink_price'] = round($value['special_shrink_price'] * $markup, 2);
            $products[$key]['shrink_price'] = round($value['shrink_price'] * $markup, 2);
            $products[$key]['unit_price'] = round($value['unit_price'] * $markup, 2);
            $products[$key]['case_price'] = round($value['case_price'] * $markup, 2);
          }

          if(!isset($clean_products[$value['stock_code']]) || $products[$key]['shrink_price'] < $clean_products[$value['stock_code']]['shrink_price']){
            $clean_products[$value['stock_code']] = $products[$key];
          }

        }
      }
      

      //return one only as we were only looking for one product.
      if($product_id && $products){
        if(!is_array($product_id)){
          $clean_products = $clean_products[$value['stock_code']];
        }
      }

      return $clean_products;
    }


     function get_product($product_id){
      $date = date("Y-m-d H:i:s");
     $sql = "SELECT p.*, c.name as 'category_name', su.markup, su.id as 'supplier_id', pdp.distributor_id, pdp.out_of_stock, pdp.unit_price, pdp.shrink_price, pdp.case_price, s.unit_price as 'special_unit_price', s.shrink_price as 'special_shrink_price', s.case_price as 'special_case_price', s.start_date as 'special_start', s.end_date as 'special_end'
              FROM `products` as p 
              JOIN categories as c ON c.id = p.category_id
              JOIN suppliers as su ON su.id = p.supplier_id
              JOIN prod_dist_price pdp ON pdp.product_id = p.id
              JOIN prod_customer_type_link pct ON pct.product_id = p.id
              LEFT JOIN specials as s ON s.distributor_id = pdp.distributor_id AND p.id = s.product_id AND start_date <= '$date' AND end_date > '$date' AND s.status_id = 8 AND s.count > 5
              WHERE 
              p.id = $product_id";

      $query =  $this->db->query($sql);

      $products = $query->result_array();

      $clean_products = array();

      if($products){

        foreach ($products as $key => $value) {

          if($value['special_shrink_price'] > 0 && $value['special_shrink_price'] < $value['shrink_price']){

              $products[$key]['is_special_now'] = 1;

              $now = time(); // or your date as well
              $your_date = strtotime($value['special_end']);
              $datediff = $now - $your_date;
              
              $products[$key]['special_end'] = floor($datediff/(60*60*24));

          }else{
            $products[$key]['is_special_now'] = 0;
          }

          //add the markup based on supplier
          if($value['markup'] > 0){
            
            $markup = "0.".$value['markup'];

            if($value['markup'] < 10){
              $markup = "0.0".$value['markup'];
            }

            $markup = 1+$markup;


            $products[$key]['special_shrink_price'] = round($value['special_shrink_price'] * $markup, 2);
            $products[$key]['shrink_price'] = round($value['shrink_price'] * $markup, 2);
            $products[$key]['unit_price'] = round($value['unit_price'] * $markup, 2);
            $products[$key]['case_price'] = round($value['case_price'] * $markup, 2);
          }

          if(!isset($clean_products[$value['stock_code']]) || $products[$key]['shrink_price'] < $clean_products[$value['stock_code']]['shrink_price']){
            $clean_products[$value['stock_code']] = $products[$key];
          }

          return $clean_products[$value['stock_code']];
        }
      }
      

    }

function get_product_unit_price($product_id,$distributor_id,$status_id){
   
    $value =''; 
    $whre_out_stock ='';
    if(!empty($status_id)){
        $whre_out_stock ="AND out_of_stock='$status_id'";
    }
    $query = $this->db->query("SELECT * FROM `prod_dist_price` 
                              WHERE distributor_id='$distributor_id' 
                              $whre_out_stock 
                              AND product_id = ?", array($product_id));
    $result = $query->result_array();
     foreach ($result as $key => $item) {
         $value = $item['unit_price'];
         
      }

      return $value;
 }
    
function getCagoriesById($category_id){
      $results = $this->db->query("SELECT id,name,icon FROM categories WHERE parent_id ='$category_id'")->result_array();
    return $results;
}

function get_product_shrink_price($product_id,$distributor_id){
    
    $value =''; 
    $whre_out_stock ='';
    if(!empty($status_id)){
        $whre_out_stock ="AND out_of_stock='$status_id'";
    }
    $query = $this->db->query("SELECT * FROM `prod_dist_price` WHERE distributor_id='$distributor_id' $whre_out_stock AND  product_id = ?", array($product_id));
    $result = $query->result_array();
     foreach ($result as $key => $item) {
         $value = $item['shrink_price'];
         
      }

      return $value;
 }
    
function get_product_case_price($product_id,$distributor_id){
   
    $value =''; 
    $whre_out_stock ='';
    if(!empty($status_id)){
        $whre_out_stock ="AND out_of_stock='$status_id'";
    }
    $query = $this->db->query("SELECT * FROM 
                              `prod_dist_price` 
                              WHERE distributor_id='$distributor_id' 
                              $whre_out_stock 
                              AND  product_id = ?", array($product_id));
    $result = $query->result_array();
     foreach ($result as $key => $item) {
         $value = $item['case_price'];
         
      }

      return $value;
 }

function get_product_out_of_stock($product_id,$distributor_id){
   
    $value =''; 
    $whre_out_stock ='';
    if(!empty($status_id)){
        $whre_out_stock ="AND out_of_stock='$status_id'";
    }
    $query = $this->db->query("SELECT * FROM 
                              `prod_dist_price` 
                              WHERE distributor_id='$distributor_id' 
                              $whre_out_stock 
                              AND  product_id = ?", array($product_id));
    $result = $query->result_array();
    foreach ($result as $key => $item) {
       $value = $item['out_of_stock'];
       
    }

    return $value;
 }

    
public function get_regions_by_id($distributor_id){
      $value='';
      $query = $this->db->query("SELECT *
                                  FROM `dist_region_link` 
                                  WHERE `distributor_id`='$distributor_id'");
      $result = $query->result();
      return $result;
    }
    
public function get_distributor($supplier_id=''){
    $where_supplier='';
    if(!empty($supplier_id)){
      $where_supplier = "AND ds.supplier_id='$supplier_id'";
    }
    $query = $this->db->query("SELECT * 
                              FROM distributors d, dist_supplier_link ds 
                              WHERE d.id = ds.distributor_id
                              $where_supplier 
                              GROUP By d.company_name");
    return  $query->result();
  
    }
public function get_supplier_distributor_products($distributor_id,$status)
    {
 
       if(isset($status)){
           $where_status = " AND pr.out_of_stock = '$status'";
       }else{
           $where_status='';
       }
 
        $comma = '';
        $values='';
        $suppliers = $this->spazapp_model->get_suppliers_per_distributor($distributor_id);

        foreach ($suppliers as $supplier) {
            $values .= $comma.$supplier['supplier_id'];
            $comma = ',';
        }
    
        $query = $this->db->query("SELECT pr.out_of_stock, 
                                  p.picture,p.id,p.name,
                                  p.stock_code,
                                  p.barcode,
                                  c.name as category,
                                  s.company_name,
                                  pr.unit_price,
                                  pr.shrink_price,
                                  pr.case_price
                                  FROM products as p 
                                  JOIN categories as c ON c.id=p.category_id 
                                  JOIN suppliers as s ON p.supplier_id = s.id 
                                  JOIN prod_dist_price as pr ON p.id = pr.product_id AND pr.distributor_id = $distributor_id
                                  WHERE p.supplier_id IN ($values) 
                                  $where_status 
                                  GROUP BY p.name  ORDER BY p.id ASC");
       
        return  $query->result();
  
    }


public function get_supplier_distributor_products_zero_price($distributor_id,$status=0)
    {
 
        $where_status = " AND pr.out_of_stock = '$status'";
 
        $comma = '';
        $values='';
        $suppliers = $this->spazapp_model->get_suppliers_per_distributor($distributor_id);

        if($suppliers){

          foreach ($suppliers as $supplier) {
              $values .= $comma.$supplier['supplier_id'];
              $comma = ',';
          }
      
          $query = $this->db->query("SELECT pr.out_of_stock, 
                                    p.picture,p.id,p.name,
                                    p.stock_code,
                                    p.barcode,
                                    c.name as category,
                                    s.company_name,
                                    pr.unit_price,
                                    pr.shrink_price,
                                    pr.case_price
                                    FROM products as p 
                                    JOIN categories as c ON c.id=p.category_id 
                                    JOIN suppliers as s ON p.supplier_id = s.id 
                                    JOIN prod_dist_price as pr ON p.id = pr.product_id AND pr.distributor_id = $distributor_id
                                    WHERE p.supplier_id IN ($values) 
                                    $where_status 
                                    AND pr.shrink_price <= 0
                                    GROUP BY p.name  ORDER BY p.id ASC");
         
          return  $query->result_array();
        }

        return false;
  
    }
   
    function get_dist_product($product_id,$distributor_id){
    
        $query = $this->db->query("SELECT `pr`.`unit_price`,
                                `pr`.`shrink_price`,
                                `pr`.`case_price`,
                                `pr`.`out_of_stock`,
                                `r`.`name` as `region` 
                                FROM `prod_dist_price`  as `pr` 
                                JOIN `dist_region_link` as `dr` ON `dr`.`distributor_id` =`pr`.`distributor_id` 
                                JOIN `regions` as `r` ON `r`.`id` =`dr`.`region_id` 
                                WHERE  `pr`.`distributor_id`  = '$distributor_id' 
                                AND `pr`.`product_id` = ?", array($product_id));

        return $query->row_array();

     }

     function get_dist_product_1($distributor_id, $stock_status){
        if(!isset($stock_status)){
          $stock_status=0;
        }

        $query = $this->db->query("SELECT * FROM `prod_dist_price`
                                   WHERE `distributor_id`='$distributor_id' 
                                   AND `out_of_stock` IN($stock_status)")->result();

        $product_id  = '';
        $comma       = '';

        foreach ($query as $item) {
            $product_id .= $comma.$item->product_id;
            $comma = ',';
        }

        return $product_id;

     }   

    function get_supplier_product($distributor_id, $stock_status,$user_link_id){

        $query = $this->db->query("SELECT * 
                                  FROM `prod_dist_price` as `pdp` 
                                  JOIN `products` as `p` ON `pdp`.`product_id` = `p`.`id`
                                  WHERE `pdp`.`distributor_id`='$distributor_id' 
                                  AND `pdp`.`out_of_stock` IN($stock_status) 
                                  AND `p`.`supplier_id` ='$user_link_id'")->result();

        $product_id  = '';
        $comma       = '';

        foreach ($query as $item) {
            $product_id .= $comma.$item->product_id;
            $comma = ',';
        }

        return $product_id;

     }   

    function get_out_of_stock($product_id, $distributor_id){

          $query = $this->db->query("SELECT 
                                     out_of_stock
                                     FROM prod_dist_price
                                     WHERE
                                     product_id = $product_id
                                     AND distributor_id = $distributor_id
                                     ");
          $return = $query->row_array();
          return $return['out_of_stock'];

    }

    function get_category_by_id($cat_id){
     
        if(!empty($cat_id)){
            $where_cat_id = "id ='$cat_id'";
        }else{
            $where_cat_id = '1';
        }
        
        
        $data['results'] = $this->db->query("SELECT id,name,icon FROM categories WHERE $where_cat_id")->result_array();
        $data['sub_cats'] = 0; 
        
        
        if(!empty($cat_id)){
            
            $results = $this->db->query("SELECT id,name,icon,parent_id FROM categories WHERE parent_id = '$cat_id'")->result_array(); 

            foreach($results as $row){

                if($cat_id == $row->parent_id){
                    $data['sub_cats'] = 1; 
                }
         
            }   
        }
        
        return $data; 

    }

    function getCagoriesByParentId($category_id){

          $query = $this->db->query("SELECT id,name FROM categories WHERE id IN (select category_id from products) and id='$category_id' ORDER BY name");

        if($query->num_rows() > 0)
          return $query->row_array();
        else
          return null;
    }

    function getProductTier($brand_id){

          $query = $this->db->query("SELECT tier FROM brands WHERE  id='$brand_id'");

        if($query->num_rows() > 0)
          return $query->row_array();
        else
          return null;
    }

    function get_dis_prod_by_cat($category_id, $distributor_id){

          $query = $this->db->query("SELECT p.stock_code, p.barcode, p.id, p.name, dp.product_id as 'checked' FROM products p 
            LEFT JOIN prod_dist_price dp on dp.product_id = p.id AND distributor_id = $distributor_id
            WHERE p.category_id = $category_id ORDER BY name");

        if($query->num_rows() > 0)
          return $query->result_array();
        else
          return null;
    }
    
    function get_dist_products_by_category($distributor_id, $category_id){

          
    $query = $this->db->query( "SELECT `p`.`id`, `p`.`name`,
                              `pr`.`unit_price`,`pr`.`shrink_price`,`pr`.`case_price`,
                              `pr`.`out_of_stock`,`r`.`name` as `region` 
                                FROM `products` as `p`
                                JOIN `prod_dist_price`  as `pr` ON `p`.`id` = `pr`.`product_id`
                                JOIN `dist_region_link` as `dr` ON `dr`.`distributor_id` =`pr`.`distributor_id` 
                                JOIN `regions` as `r` ON `r`.`id` =`dr`.`region_id`  
                                WHERE  `pr`.`distributor_id`  = '$distributor_id' 
                                AND `p`.`category_id` ='$category_id' GROUP BY `p`.`name`");
   
    return  $query->result_array();

    }


    // Add and Delete distributor products

    public function deleteDistributorProduct($distributor_id, $product_id)
    {
        $query = $this->db->where("distributor_id", $distributor_id)->where("product_id", $product_id)->delete("prod_dist_price");

        if($query)
        {
            return "true";
        }
        else
        {
            return "false";
        }

    }

    public function insertDistributorProducts($data)
    {
        if(!isset($data['distributor_id']) || !isset($data['product_id']))
        {
            return "Incorrect information";
        }
        else
        {
            $query = $this->db->insert("prod_dist_price", $data);

            if($query)
            {
                return "true";
            }
            else
            {
                return "false";
            }
        }
    }

    public function checkDistributorProducts($distributor_id, $product_id)
    {
        $query = $this->db->select("distributor_id")
                    ->from("prod_dist_price")
                    ->where("product_id", $product_id)
                    ->where("distributor_id", $distributor_id)
                    ->get();
        $result = $query->num_rows();

        if($result > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getCategories()
    {
        $query = $this->db->select("id, name")
                    ->from("categories")
                    ->where("id !=", "46")
                    ->get();
        $result = $query->result_array();
        return $result;
    }

    public function getParentCategories()
    {
        $query = $this->db->select("id, name")
                    ->from("categories")
                    ->where("id !=", "46")
                    ->where("parent_id =", "0")
                    ->get();
        $result = $query->result_array();
        return $result;
    }

    public function get_subcategories($category_id)
    {
        $query = $this->db->from('categories')->where('parent_id', $category_id)->get();
        return $query->result_array();
    }

    public function get_distributor_id($product_id, $distributor_id)
    {
        $query = $this->db->select("distributor_id")
                  ->from("prod_dist_price")
                  ->where("product_id", $product_id)
                  ->where("distributor_id", $distributor_id)
                  ->get();
        $result = $query->row();

        if($result)
        {
            return $result->distributor_id;
        }
        else
        {
            return 0;
        }
    }

    public function getDistributor($distributor_id)
    {
        $query = $this->db->select("*")->from("distributors")->where("id", $distributor_id)->get();
        $return = $query->row();
        return $return;
    }

     public function getDistributorById($distributor_id)
    {
        $query = $this->db->select("*")->from("distributors")->where("id", $distributor_id)->get();
        $return = $query->row_array();
        return $return;
    }

    public function getProductName($id)
    {
        $query = $this->db->select("name")
                  ->from("products")
                  ->where("id", $id)
                  ->get();
        $result = $query->row();
        return $result->name;
    }
    
     function product_sales_query($distributor_id,$top_limit,$from_date,$to_date){
        
        if(!empty($distributor_id)){
            
        if($distributor_id== "all"){
              $dist_where =''; 
        }else{
              $dist_where ="AND do.distributor_id ='$distributor_id'";
        }
            
        }else{
              $dist_where ='';
        }

        if(!empty($from_date)){
              $date_from = "AND o.createdate >= '$from_date'";
        }else{
              $date_from ='';
        }

        if(!empty($to_date)){
              $date_to = " AND o.createdate <= '$to_date'";

        }else{
              $date_to = '';
        }

        $status = $this->input->post('status');

        if(!empty($status)){
          $status_w = "AND do.status_id ='$status'";
        }else{
          $status_w ='';
        }

        $sql = "SELECT p.id,p.name,oi.quantity,
                 SUM(oi.quantity) as product_count,
                 ROUND(SUM(oi.price * oi.quantity),2) as total
                 FROM orders o,order_items oi, products p, 
                 distributor_orders do, distributors d, gbl_statuses g
                 WHERE  o.id = oi.order_id AND p.id = oi.product_id 
                 AND do.order_id = o.id 
                 AND d.id = do.distributor_id 
                 AND g.id = do.status_id 
                 $status_w
                 $dist_where $date_from $date_to 
                 GROUP BY oi.product_id ORDER BY SUM(oi.quantity) DESC LIMIT $top_limit";
        
        return $sql;
    }  

    function get_top_product_sales($distributor_id='', $top_limit, $from_date, $to_date, $status){
        
        if(!empty($distributor_id)){
            
            if($distributor_id== "all"){
              $dist_where =''; 
            }else{
              $dist_where ="AND do.distributor_id ='$distributor_id'";
            }
            
        }else{
            $dist_where ='';
        }


        if(!empty($to_date)){
              $where_date = " AND o.createdate <= '$to_date' AND o.createdate >= '$from_date'";

        }else{
              $where_date = '';
        }

        if(!empty($status)){
          $status_w = "AND do.status_id ='$status'";
        }else{
          $status_w ='';
        }

        $query = $this->db->query("SELECT p.id,p.name,oi.quantity,
                                   SUM(oi.quantity) as product_count,
                                   ROUND(SUM(oi.price * oi.quantity),2) as total
                                   FROM orders o,order_items oi, products p, 
                                   distributor_orders do, distributors d, gbl_statuses g
                                   WHERE  o.id = oi.order_id AND p.id = oi.product_id 
                                   AND do.order_id = o.id 
                                   AND d.id = do.distributor_id 
                                   AND g.id = do.status_id and do.status_id=9
                                   $status_w $dist_where $where_date
                                   GROUP BY oi.product_id ORDER BY SUM(oi.quantity) DESC LIMIT $top_limit");
     
        return $query->result_array();
    }
    
   

    function get_top_customer_sales($distributor_id,$top_limit,$date_from,$date_to){
           
        if($distributor_id== "all"){
           
             $distributor_id ='';
        }
        
        if($distributor_id== ""){
           
             $distributor_id ='';
        }else{

             $distributor_id =" AND do.distributor_id = '$distributor_id'";
        } 

        if(!empty($date_from)){
              $date_from = "AND o.createdate >= '$date_from'";
        }else{
              $date_from ='';
        }

        if(!empty($date_to)){
              $date_to = " AND o.createdate <= '$date_to'";

        }else{
              $date_to = '';
        }

        $query = $this->db->query("SELECT c.company_name as customer, o.customer_id,
                                  COUNT(o.customer_id) as order_count, round(sum(oi.quantity*oi.price), 2) as total
                                  FROM orders o, distributor_orders do, distributors d, 
                                  customers c, order_items oi, gbl_statuses g
                                  WHERE  
                                  c.id = o.customer_id
                                  AND do.order_id = o.id 
                                  AND oi.order_id = o.id 
                                  AND d.id = do.distributor_id 
                                  AND g.id = do.status_id 
                                  AND do.status_id='9' $distributor_id $date_from $date_to  
                                  GROUP BY o.customer_id ORDER BY COUNT(o.customer_id) DESC  LIMIT $top_limit");
        return $query->result_array();
 
 
    }

    public function getNumberOfFmcgSold($date_from,$date_to){
      
      if(!empty($date_to)){
        $where_date="WHERE  o.createdate>='$date_from' and o.createdate<='$date_to'";
      }else{
        $where_date='';
      }
      $query = $this->db->query("SELECT oi.product_id FROM 
                                order_items as oi JOIN orders as o ON oi.order_id=o.id 
                                $where_date GROUP BY oi.product_id");

      $result = $query->num_rows();
      return $result;
   }
    public function getNumberOfFmcg(){
      
      $query = $this->db->query("SELECT id FROM products");

      $result = $query->num_rows();
      return $result;
   }

  function strip_db_rejects($table, $dirty_array){
      $clean_array = array();
      $table_fields = $this->db->list_fields($table);

      foreach ($dirty_array as $key => $value) {
        if(in_array($key, $table_fields)){
          $clean_array[$key] = $value;
        }
      }
      return $clean_array;
  }

  function get_product_sales_status(){
      $query = $this->db->query("SELECT * FROM gbl_statuses WHERE id IN(8, 9, 14)");
      return $query->result_array();
  }


  function get_all_products_info(){

    $query = $this->db->query("SELECT 
                            p.id,
                            p.name,
                            s.company_name as supplier_id,
                            p.stock_code,
                            cat.name as category_id,
                            p.picture,
                            group_concat(ct.name) as customer_type
                            FROM  products as p
                            LEFT JOIN categories as cat ON cat.id=p.category_id
                            LEFT JOIN suppliers as s ON p.supplier_id=s.id
                            LEFT JOIN  prod_customer_type_link as t ON p.id=t.product_id
                            LEFT JOIN customer_types as ct ON t.customer_type=ct.id
                            WHERE 1 GROUP BY p.id");


       return $query->result_array();
     
  }

  function get_customer_types($product_id){

    $query = $this->db->query("SELECT group_concat(name) as customer_type
      FROM 
      customer_types as ct
      INNER JOIN prod_customer_type_link as t ON t.customer_type=ct.id 
      WHERE product_id='$product_id' GROUP BY t.product_id");

    return $query->row_array()['customer_type'];

  }

}