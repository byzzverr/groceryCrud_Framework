<?php

class Order_model extends CI_Model {

   public function __construct()
   {
      $this->load->model('customer_model');
      $this->load->model('product_model');
      $this->load->model('spazapp_model');
      $this->load->library('comms_library');
   
      parent::__construct();
   }

   function verify_basket_name($basket_name, $stokvel_id){
    $query = $this->db->query("SELECT id FROM basket_orders where basket_name = ? AND stokvel_id = ?",array($basket_name, $stokvel_id));
    if($query->num_rows() == 0){
      return true;
    }
    return false;
   }

   function get_order_by_cashvan($user_id){

      $query = $this->db->query("SELECT * FROM orders WHERE delivery_type = ?", array('cash van'));
      $result = $query->result_array();
      $total = 0;
      $orders = array();
      foreach ($result as $key => $order) {
         $orders[] = $this->get_order_info($order['id']);
      }

      return $orders;
   }

   function get_all_order_info($order_id){

      $order = $this->get_order_info($order_id);      
      $order['info'] = $this->get_order_items($order_id);
      return $order;
   }

   function get_order_info($order_id){
       $query = $this->db->query("SELECT o.*, do.distributor_id, do.id as distributor_order_id 
       FROM orders o LEFT 
       JOIN distributor_orders do ON o.id = do.order_id 
       WHERE o.id = '$order_id'");
       $order = $query->row_array();
       
        $order['customer'] = $this->customer_model->get_customer_info($order['customer_id']);
        $order['items'] = $this->get_order_items($order_id);
       
        $distributor_id = $order['distributor_id'];

        if(!empty($distributor_id)){
            $order['distributor_info'] = $this->order_model->get_distributor_info($distributor_id);
        }else{
            $order['distributor_info'] = '';
        }
      return $order;
   }

   function get_order_from_master_card($masterpass_code){

      $order = $this->get_order_info_from_masterpass_code($masterpass_code);      
      $order['info'] = $this->get_order_items($order['order_id']);
      return $order;
   }

   function get_order_info_from_masterpass_code($masterpass_code){
       $query = $this->db->query("SELECT o.id as order_id, o.*, do.distributor_id, do.id as distributor_order_id 
       FROM orders o LEFT 
       JOIN distributor_orders do ON o.id = do.order_id 
       WHERE o.masterpass_code = '$masterpass_code'");
       $order = $query->row_array();
       $order['order_id']=$query->row_array()['order_id'];
        $order['customer'] = $this->customer_model->get_customer_info($order['customer_id']);
        $order['items'] = $this->get_order_items($order['order_id']);
       
        $distributor_id = $order['distributor_id'];

        if(!empty($distributor_id)){
            $order['distributor_info'] = $this->order_model->get_distributor_info($distributor_id);
        }else{
            $order['distributor_info'] = '';
        }

        return $order;
   
   }


   function get_distributor_orders($order_id){
       $query = $this->db->query("SELECT o.*, do.distributor_id, do.id as distributor_order_id 
       FROM distributor_orders do 
       JOIN orders o ON o.id = do.order_id 
       WHERE o.id = '$order_id'");
       $orders = $query->result_array();

       foreach ($orders as $key => $order) {
          $orders[$key]['customer'] = $this->customer_model->get_customer_info($order['customer_id']);
          $orders[$key]['items'] = $this->get_order_items($order_id);
          $orders[$key]['distributor_info'] = $this->order_model->get_distributor_info($order['distributor_id']);
       }
       
      return $orders;
   }

      public function get_distributor_info($distributor_id)
    {
        $query = $this->db->select("company_name, contact_name, number, email, picture, address,
       vat_no, ck_number, bank_name, bank_account_type, bank_number, bank_branch")
                  ->from("distributors")
                  ->where("id", $distributor_id)
                  ->get();
        $result = $query->row_array();
        return $result;
    }

   function get_dis_order_info($dis_order_id){

        $query = $this->db->query("SELECT o.*, do.distributor_id, do.id as distributor_order_id FROM orders o LEFT JOIN distributor_orders do ON o.id = do.order_id WHERE do.id = '$dis_order_id'");
        $order = $query->row_array();
        $order['order_id'] = $order['id'];
        $order['id'] = $order['distributor_order_id'];
        $order['customer'] = $this->customer_model->get_customer_info($order['customer_id']);
        $order['items'] = $this->get_order_items($order['distributor_order_id'], 'dist_order');
        $order['distributor_info'] = $this->order_model->get_distributor_info($order['distributor_id']);
        
        return $order;
     }

     function get_dis_order($dist_order_id){
          $query = $this->db->query("SELECT o.*, do.distributor_id, do.id as 'distributor_order_id', do.status_id as 'dist_order_status' FROM orders o LEFT JOIN distributor_orders do ON o.id = do.order_id WHERE do.id = '$dist_order_id'");
          $order = $query->row_array();
          $order['order_id'] = $order['id'];
          unset($order['id']);
          $order['customer'] = $this->customer_model->get_customer_info($order['customer_id']);
          $order['items'] = $this->get_order_items($dist_order_id, 'dist_order');
          return $order;
       }

     function get_dis_order_no_cus($dist_order_id){
          $query = $this->db->query("SELECT o.*, do.distributor_id, do.id as 'distributor_order_id', do.status_id as 'dist_order_status' FROM orders o LEFT JOIN distributor_orders do ON o.id = do.order_id WHERE do.id = '$dist_order_id'");
          $order = $query->row_array();
          $order['order_id'] = $order['id'];
          unset($order['id']);
          $order['items'] = $this->get_order_items($dist_order_id, 'dist_order');
          return $order;
       }

   public function get_order_item_count($order_id){
    
      $query = $this->db->query("SELECT * FROM order_items WHERE order_id = '$order_id'");
      $result = $query->num_rows();
      return $result;
   }

   public function get_order_twv($order_id,$type='order'){
    
      $result = $this->get_order_items($order_id,$type);
      $order = array();
      $order['total_amount'] = round($result['total_amount'], 2);
      $order['total_weight'] = round($result['total_weight'], 2);
      $order['total_volume'] = round($result['total_volume'], 2);
      return $order;
   }


   public function count_all_orders($username){
     $customer = $this->customer_model->get_customer_from_user_username($username);
     $customer_id = $customer['id'];
     $query = $this->db->query("SELECT id FROM orders where customer_id = $customer_id");
      return $query->num_rows();
   }

   public function get_order_total($order_id,$type='order'){
    
     $result = $this->get_order_items($order_id,$type);
      return round($result['total_amount'], 2);
   }


   public function get_order_weight($order_id,$type='order'){
    
     $result = $this->get_order_items($order_id, $type);
      return round($result['total_weight'], 2);
   }

   public function get_order_volume($order_id,$type='order'){
    
     $result = $this->get_order_items($order_id,$type);
      return round($result['total_volume'], 2);
   }

  function get_order_items($order_id,$type='order'){

      $this->load->model('app_model');
      if($type == 'order'){
        $query = $this->db->query("SELECT * FROM `order_items` WHERE order_id = ?", array($order_id));
      }else{
        $query = $this->db->query("SELECT i.*, do.distributor_id FROM `order_items` i, distributor_orders do  WHERE do.id = i.distributor_order_id AND i.distributor_order_id = ?", array($order_id));
      }
      $result = $query->result_array();

      $total_amount = 0;
      $total_weight = 0;
      $total_grams = 0;
      $total_kg = 0;
      $total_volume = 0;
      $total_liters = 0;
      $total_milliliters = 0;
      $total_products = count($result);
      $customer = $this->customer_model->get_customer_from_order($order_id);
      
      foreach ($result as $key => $item) {

        $result[$key]['product'] = $this->product_model->get_product_basic_info($item['product_id']);
        //I added this to cater for old orders that dont have products etc.
        if(!$result[$key]['product']['supplier_id'] || $result[$key]['product']['supplier_id'] == ''){
          unset($result[$key]);
          continue;
        }

        if($type == 'order'){
            $distributor_id = $this->app_model->find_distributor($result[$key]['product']['supplier_id'], $customer['region_id']);
        }else{
          $distributor_id = $item['distributor_id'];
        }
        
        $result[$key]['product']['distributor_id'] = $distributor_id;
        $productMassTotal = $result[$key]['product']['qty']*$result[$key]['product']['pack_size'];
        $total_amount = $total_amount + ($item['price']*$item['quantity']);

        if($result[$key]['product']['units'] == 'gram')
        {
            $total = $total_grams + ($item['quantity']*($productMassTotal / 1000));
            $total_weight = $total_weight + $total; 
        }

        if($result[$key]['product']['units'] == 'kg')
        {
            $total = $total_kg + ($item['quantity']*$productMassTotal);
            $total_weight = $total_weight + $total; 
        }

        if($result[$key]['product']['units'] == 'milliliter')
        {
            $total = $total_milliliters + ($item['quantity']*($productMassTotal / 1000));
            $total_volume = $total_volume + $total; 
        }

        if($result[$key]['product']['units'] == 'liter')
        {
            $total = $total_liters + ($item['quantity']*$productMassTotal);
            $total_volume = $total_volume + $total; 
        }

      }

      return array('total_amount' => $total_amount, 'total_weight' => $total_weight, 'total_volume' => $total_volume, 'items' => $result, 'total_products' => $total_products);
  }

   public function get_orders_for_customer($customer_id){
    
      $date_from = $this->session->userdata('dashboard_date_from');
      $date_to = $this->session->userdata('dashboard_date_to');

      $query = $this->db->query("SELECT * FROM orders WHERE customer_id = '$customer_id' AND createdate > '$date_from' AND createdate < '$date_to'");
      $result = $query->result_array();

      foreach ($result as $key => $order) {
         $result[$key] = $this->get_full_order($order);
      }
      return $result;
   }

   public function get_full_order($order){
   
      $query = $this->db->query("SELECT * FROM order_items WHERE order_id = ?", array($order['id']));
      $result = $query->result_array();
      $total = 0;
      foreach ($result as $key => $item) {
         $total = $total + $item['price'];
      }

      $order['total'] = $total;
      $order['item_count'] = count($result);
      
      return $order;
   }

   function get_strores_orders(){

      $date_from = $this->session->userdata('dashboard_date_from');
      $date_to = $this->session->userdata('dashboard_date_to');

      $query = $this->db->query("SELECT count(a.id) as orders, b.company_name FROM orders a, customers b WHERE a.customer_id = b.id AND a.createdate > '$date_from' AND a.createdate < '$date_to' group by b.company_name");

      return $query->result_array();
   }

   function get_products_orders(){

      $date_from = $this->session->userdata('dashboard_date_from');
      $date_to = $this->session->userdata('dashboard_date_to');

      $query = $this->db->query("SELECT 
         p.name,
         p.id,
         SUM(oi.quantity) as 'quantity'
         FROM 
         orders o,
         order_items oi,
         products p
         WHERE 
         o.id = oi.order_id AND
         p.id = oi.product_id AND 
         o.createdate > '$date_from' AND 
         o.createdate < '$date_to'
         GROUP BY
         p.id
         ");

      return $query->result_array();
   }

   public function get_prod_order_items($customer_id){
    
      $date_from = $this->session->userdata('dashboard_date_from');
      /*$date_from = '2015-02-01 00:00:00';*/
      $date_to = $this->session->userdata('dashboard_date_to');

      $query = $this->db->query("SELECT 
         p.name,
         p.id,
         COUNT(oi.id) as 'numbers',
         SUM(oi.quantity) as 'quantity',
         ROUND(SUM(oi.price),2) as 'price'
         FROM 
         orders o,
         order_items oi,
         products p
         WHERE 
         p.id = oi.product_id AND 
         o.id = oi.order_id AND 
         o.customer_id = '$customer_id' AND 
         o.createdate > '$date_from' AND 
         o.createdate < '$date_to'
         GROUP BY
         p.id
         ");
      $result = $query->result_array();

      return $result;
   }

   // Sales Report Page Models

   public function get_daily_orders()
   {
      $date_from = $this->session->userdata('dashboard_date_from');
      $date_to = $this->session->userdata('dashboard_date_to'); 

      $query = $this->db->select("oi.product_id, p.name, c.company_name as buyer, s.company_name as supplier, o.status, o.payment_type, o.delivery_type, oi.quantity, oi.price")
         ->from("orders as o")
         ->join("customers as c", "c.id = o.customer_id")
         ->join("order_items as oi", "oi.id = o.id")
         ->join("products as p", "p.id = oi.product_id")
         ->join("suppliers as s", "s.id = p.supplier_id")
         ->where("o.status", "Order Placed")
         //->where("o.createdate", date(Y-m-d))
         ->where("o.createdate >", $date_from)
         ->where("o.createdate <=", $date_to)
         ->order_by("o.createdates")
         ->get();

      return $query->num_rows();
   }


   
   public function getOrderInformation($order_id)
   {
      $query = $this->db->select("r.name as region,o.customer_id, o.status, o.payment_type, o.delivery_type, o.createdate, c.company_name,c.id, p.name, c.trader_id")
                ->from("orders as o")
                ->join("customers as c", "c.id = o.customer_id")
                ->join("regions as r", "c.region_id = r.id")
                ->join("payment_types as p", "p.id = o.payment_type")
                ->where("o.id", $order_id)
                ->get();
      $result = $query->row();
      return $result;
   }

  public function getDistributorNameByID($id)
   {
      $data = "Your";
      $query = $this->db->select("company_name")
                ->from("distributors")
                ->where("id", $id)
                ->get();
      $result = $query->row();
      
      if ($query)
      {
        return $result;
      }
      else
      {
        return $data;
      }
   } 
   public function getDistributorNames()
   {
      $query = $this->db->select("*")
                ->from("distributors")
                ->get();
      $result = $query->result();
      return $result; 
   }


   // Auto populate fields

   public function getProductPrice($id)
   {

      $query = $this->db->select("shrink_price")
                ->from("prod_dist_price")
                ->where("product_id", $id)
                ->get();
      $result = $query->row();
      return $result;
   }

   public function getDistributorOrderId($id)
   {

      $data = "0";
      $query = $this->db->select("distributor_order_id")
                ->from("order_items")
                ->where("id", $id)
                ->get();
      $result = $query->row();

      if ($query)
      {
        return $result;
      }

      else
      {
        return $data;
      }
   }
 
 

   // Distributors Assign Approved Orders , $region_id

   public function get_distributor_approved_orders($distributor_id, $region_id, $area_id)
   {

      //$condition = "r.parent_id =". $region_id OR "c.region_id =". $region_id;
      $condition = "c.region_id =". $region_id;
      $condition_one = "c.region_id !=". 0;
      $condition_two = "c.region_id =". $region_id;

      if ($region_id <= 0 && $area_id <= 0)
      {
          $placeSQL = $condition_one;
      }
      else if ($region_id > 0 && $area_id <= 0)
      {
          $placeSQL = $condition;
      }
      else if ($region_id > 0 && $area_id > 0)
      {
          $placeSQL = $condition_two;
      }
      else
      {
          $placeSQL = $condition_one;
      }

      $query = $this->db->select("d.id, d.order_id, c.company_name, c.location_lat, c.location_long, c.address, c.suburb, c.region_id, r.name as region")
                  ->from("distributor_orders d")
                  ->join("orders as o", "o.id = d.order_id")
                  ->join("customers as c", "c.id = o.customer_id")
                  ->join("regions as r", "r.id = c.region_id")
                  ->where("d.distributor_id", $distributor_id)
                  ->where("d.status_id", 8)
                  ->where($placeSQL)
                  ->order_by("d.id desc")
                  ->limit("15")
                  ->get();

      $return = $query->result_array();

      foreach ($return as $key => $order) {
        
        $order_twv = array('total_amount' => 24.50, 'total_weight' => 50, 'total_volume' => 25);
        $order_twv = $this->get_order_twv($order['id'],'distributor');
        $return[$key]['total'] = $order_twv['total_amount'];
        $return[$key]['weight'] = $order_twv['total_weight'];
        $return[$key]['volume'] = $order_twv['total_volume'];
      }

      return $return;
   }

   // Assign Approved Orders , $region_id

   public function get_all_approved_orders($region_id, $area_id)
   {

      //$condition = "r.parent_id =". $region_id OR "c.region_id =". $region_id;
      $condition = "c.region_id =". $region_id;
      $condition_one = "c.region_id !=". 0;
      $condition_two = "c.region_id =". $area_id;

      if ($region_id <= 0 && $area_id <= 0)
      {
          $placeSQL = $condition_one;
      }
      else if ($region_id > 0 && $area_id <= 0)
      {
          $placeSQL = $condition;
      }
      else if ($region_id > 0 && $area_id > 0)
      {
          $placeSQL = $condition_two;
      }
      else
      {
          $placeSQL = $condition_one;
      }

      $query = $this->db->select("d.id, d.order_id, c.company_name, c.location_lat, c.location_long, c.address, c.suburb, c.region_id, r.name as region")
                  ->from("distributor_orders d")
                  ->join("orders as o", "o.id = d.order_id")
                  ->join("customers as c", "c.id = o.customer_id")
                  ->join("regions as r", "r.id = c.region_id")
                  ->where("d.status_id", 8)
                  ->where($placeSQL)
                  ->order_by("d.id")
                  ->get();

      $return = $query->result_array();

      foreach ($return as $key => $order) {
        $order_twv = $this->get_order_twv($order['id'],'distributor');
        $return[$key]['total'] = $order_twv['total_amount'];
        $return[$key]['weight'] = $order_twv['total_weight'];
        $return[$key]['volume'] = $order_twv['total_volume'];
      }

      return $return;
   }

   public function getDeliveryRoutes($id) 
   {

      $query = $this->db->select("d.driver, d.truck, d.date, COUNT(distinct(do.delivery_id)), dor.order_id, o.customer_id, c.company_name, c.location_lat, c.location_long, c.address")
                  ->from("deliveries d")
                  ->join("del_orders as do", "do.delivery_id = d.id")
                  ->join("distributor_orders as dor", "dor.id = do.dist_order_id")
                  ->join("orders as o", "o.id = dor.order_id")
                  ->join("customers as c", "c.id = o.customer_id")
                  ->where("d.id", $id)
                  ->group_by("dor.order_id")
                  ->get();

      $return = $query->result_array();
      return $return;

   }

   function copy_distributor_order($distributor_order_id){

        //go get the old order
        $order_info = $this->order_model->get_dis_order($distributor_order_id);
        $customer_id = $order_info['customer']['id'];
        //get customer region.
        $region = $this->customer_model->get_customer_region($customer_id);
        $region_id = $region['region_id'];
        $region_name = $region['name'];

        $order['region_id'] = $region_id;
        $order['payment_type'] = $order_info['payment_type'];
        $order['delivery_type'] = $order_info['delivery_type'];
        $order['order_type'] = $order_info['order_type'];

        foreach ($order_info['items']['items'] as $key => $product) {
            
            $supplier_id = $this->app_model->get_product_supplier($product['product_id']);

            $distributor_id = $this->app_model->find_distributor($supplier_id, $region_id);

            $order['items'][$key]['product_id'] = $product['product_id'];
            $order['items'][$key]['supplier_id'] = $supplier_id;
            $order['items'][$key]['distributor_id'] = $distributor_id;
            $order['items'][$key]['quantity'] = $product['quantity'];
            $order['items'][$key]['price'] = $product['price'];
            //minus the rewards
            $order['items'][$key]['rewards'] = round(($product['price']*$product['quantity']) / 100)*-1;
        }

      $this->app_model->replacement_dist_order($customer_id, $order, $order_info);

   }


   function delete_dis_order($distributor_order_id){

    $this->db->query("DELETE FROM distributor_orders WHERE id = $distributor_order_id");
    $this->db->query("DELETE FROM order_items WHERE distributor_order_id = $distributor_order_id");

   }

   public function getAllDeliveryRoutes($id) 
   {

      $query = $this->db->select("d.driver, d.truck, d.distributor_id, d.date, COUNT(distinct(do.delivery_id)), dor.order_id, o.customer_id, c.company_name, c.location_lat, c.location_long, c.address")
                  ->from("deliveries d")
                  ->join("del_orders as do", "do.delivery_id = d.id")
                  ->join("distributor_orders as dor", "dor.id = do.dist_order_id")
                  ->join("orders as o", "o.id = dor.order_id")
                  ->join("customers as c", "c.id = o.customer_id")
                  ->where("d.id", $id)
                  ->group_by("dor.order_id")
                  ->get();

      $return = $query->result_array();
      return $return;

   }

   public function getDistributorAddress($distributor_id)
   {

      $query = $this->db->select("address")
                  ->from("distributors")
                  ->where("id", $distributor_id)
                  ->get();

      $return = $query->row();
      return $return;

   }

    public function show_data_by_date_range($data) {
      
      $condition = "emp_date_of_join BETWEEN " . "'" . $data['date1'] . "'" . " AND " . "'" . $data['date2'] . "'";
      $this->db->select('*');
      $this->db->from('employee_info');
      $this->db->where($condition);
      $query = $this->db->get();

      if ($query->num_rows() > 0) {
        return $query->result();
      } else {
        return false;
      }
    }

   public function get_regions()
   {
      $query = $this->db->select("id, name")
                ->from("regions")
                ->where("parent_id", 3)
                ->get();

      $return = $query->result_array();
      return $return;
   } 

    public function get_dist_regions($distributor_id)
   {
        $locations = $this->customer_model->getAllDistributorRegions($distributor_id);
        $values = '';
        $comma = '';
        foreach ($locations as $key => $value) {
            $values .= $comma.humanize($value['region_id']);
            $comma = ',';
        }
        $query = $this->db->query("SELECT * FROM regions WHERE id IN($values)");
    
        $return = $query->result_array();
        return $return;
   }

   public function add_delivery($data)
   {
      // First Table Deliveries
      $this->db->insert("deliveries", $data);
      $insert_id = $this->db->insert_id();
      return $insert_id;
   }

   public function add_delivery_orders($dataOrders)
   {
      $this->db->insert("del_orders", $dataOrders);
   }

   public function get_area($region_id=null)
   {
      $result = $this->db->where('parent_id', $region_id)->get('regions')->result();
      $id = array('0');
      $name = array('Select An Area');
      for ($i=0; $i<count($result); $i++)
      {
          array_push($id, $result[$i]->id);
          array_push($name, $result[$i]->name);
      }
      return array_combine($id, $name);
   }

   public function update_delivery_orders($orders)
   {
      $data['status_id'] = '16';

      foreach ($orders as $o) 
      {
        $this->db->where("id", $o)->update("distributor_orders", $data);
      }      
   }

   public function getDistributor($id)
   {
      $query = $this->db->select("distributor_id")
                ->from("deliveries")
                ->where("id", $id)
                ->get();
      $result = $query->row();
      return $result;
   }

   public function getAllDistributors()
   {
      $query = $this->db->select("id, company_name")
                ->from("distributors")
                ->get();
      $return = $query->result_array();
      return $return;
   }
    
public function get_trend_orders($customer,$createdate,$from,$to)
   {
    if(!empty($from)){
          $where_date = " AND SUBSTR(`o`.`createdate`,1,10) >= '$from' AND SUBSTR(`o`.`createdate`,1,10)  <= '$to'";
      }else{
          $where_date ='';
    }
    if(!empty($customer)){
         $where_cust = " `c`.`id` ='$customer' AND ";
    }else{
        $where_cust = "";
    }
    if(!empty($createdate)){
         $where_createdate = " SUBSTR(`o`.`createdate`,1,7) ='$createdate' AND ";
    }else{
        $where_createdate = "";
    }

     $query_str = "SELECT SUBSTR(`o`.`createdate`,1,7) as crtdates, 
     `c`.`id` as `c_id`,
     `o`.`id`,`o`.`createdate`,
     `ty`.`name` as payment,
     `dis`.`company_name` as `distributor`,
     `p`.`name`, 
     `c`.`company_name` as `customer_name`,
     `s`.`company_name` as `supplier`,
     `o`.`status`, `o`.`payment_type`, 
     `o`.`delivery_type`, 
     `oi`.`quantity`, 
     `oi`.`price` 
     FROM `orders` as `o` 
     JOIN `customers` as `c` ON `c`.`id` = `o`.`customer_id` 
     JOIN `payment_types` as `ty` ON `ty`.`id` = `o`.`payment_type` 
     JOIN `order_items` as `oi` ON `oi`.`id` = `o`.`id` 
     JOIN `products` as `p` ON `p`.`id` = `oi`.`product_id` 
     JOIN `suppliers` as `s` ON `s`.`id` = `p`.`supplier_id`  
     JOIN `dist_supplier_link` as `dist` ON `dist`.`supplier_id` = `p`.`supplier_id`  
     JOIN `distributor_orders` as `dis_o` ON `dis_o`.`order_id` = `o`.`id` 
     JOIN `distributors` as `dis` ON `dis_o`.`distributor_id` = `dis`.`id` 
     WHERE $where_createdate $where_cust
     `o`.`status` IN ('Order Placed', 'Approved')
     $where_date";
    
      $query = $this->db->query($query_str);
     
      $supplier_orders = $query->result_array(); 
      return $supplier_orders ;

   }
public function get_trend_customers_stats($customer,$from,$to)
   {
      if(!empty($customer)){
        $where_cust = " `c`.`id` ='$customer' AND ";
      }else{
        $where_cust = "";
      }
      if(!empty($from)){
          $where_date = " AND SUBSTR(`o`.`createdate`,1,10) >= '$from' AND SUBSTR(`o`.`createdate`,1,10)  <= '$to'";
      }else{
          $where_date ='';
      }
      $query_str = "SELECT  SUM(`oi`.`quantity` * `oi`.`price`) as total,
      count(`oi`.`order_id`) AS `count_orders`,
      `o`.`createdate`, `c`.`id` as `c_id`,
      `o`.`id`, 
      SUBSTR(`o`.`createdate`,1,7) as crtdates,
      `ty`.`name` as payment,
      `dis`.`company_name` as `distributor`, 
      `p`.`name`, 
      `c`.`company_name` as `customer_name`, 
      `s`.`company_name` as `supplier`,
      `o`.`status`, `o`.`payment_type`, 
      `o`.`delivery_type`, 
      `oi`.`quantity`, 
      `oi`.`price`
      FROM `orders` as `o` 
      JOIN `customers` as `c` ON `c`.`id` = `o`.`customer_id` 
      JOIN `payment_types` as `ty` ON `ty`.`id` = `o`.`payment_type` 
      JOIN `order_items` as `oi` ON `oi`.`id` = `o`.`id` 
      JOIN `products` as `p` ON `p`.`id` = `oi`.`product_id` 
      JOIN `suppliers` as `s` ON `s`.`id` = `p`.`supplier_id`  
      JOIN `dist_supplier_link` as `dist` ON `dist`.`supplier_id` = `p`.`supplier_id`  
      JOIN `distributor_orders` as `dis_o` ON `dis_o`.`order_id` = `o`.`id` 
      JOIN `distributors` as `dis` ON `dis_o`.`distributor_id` = `dis`.`id` 
      WHERE $where_cust 
      `o`.`status` IN ('Order Placed','Approved') $where_date 
      GROUP BY SUBSTR(`o`.`createdate`,1,7) limit 0,12";
    
      $query = $this->db->query($query_str);
     
      $supplier_orders = $query->result_array(); 

      return $supplier_orders ;

   }
public function get_trend_customers_stats2($customer,$from,$to)
   {
    
     if(!empty($from)){
          $where_date = " AND SUBSTR(`o`.`createdate`,1,10) >= '$from' AND SUBSTR(`o`.`createdate`,1,10)  <= '$to'";
      }else{
          $where_date ='';
      }
       
      $query_str = "SELECT  SUM(`oi`.`quantity` * `oi`.`price`) as total, 
      count(`oi`.`order_id`) AS `count_orders`,
      `o`.`createdate`, 
      `c`.`id` as `c_id`,
      `o`.`id`, SUBSTR(`o`.`createdate`,1,7) as crtdates,
      `ty`.`name` as payment,
      `dis`.`company_name` as `distributor`, 
      `p`.`name`, `c`.`company_name` as `customer_name`,
      `s`.`company_name` as `supplier`,
      `o`.`status`, 
      `o`.`payment_type`, 
      `o`.`delivery_type`, 
      `oi`.`quantity`,
      `oi`.`price` 
      FROM `orders` as `o` 
      JOIN `customers` as `c` ON `c`.`id` = `o`.`customer_id` 
      JOIN `payment_types` as `ty` ON `ty`.`id` = `o`.`payment_type` 
      JOIN `order_items` as `oi` ON `oi`.`id` = `o`.`id` JOIN `products` as `p` ON `p`.`id` = `oi`.`product_id` JOIN `suppliers` as `s` ON `s`.`id` = `p`.`supplier_id`  
      JOIN `dist_supplier_link` as `dist` ON `dist`.`supplier_id` = `p`.`supplier_id`  
      JOIN `distributor_orders` as `dis_o` ON `dis_o`.`order_id` = `o`.`id` 
      JOIN `distributors` as `dis` ON `dis_o`.`distributor_id` = `dis`.`id` 
      WHERE  `o`.`status` IN ('Order Placed','Approved')
      $where_date  GROUP BY  `c`.`company_name` limit 0,12";
    
      $query = $this->db->query($query_str);
     
      $supplier_orders = $query->result_array(); 

      return $supplier_orders ;

   }
public function get_trend_customers($customer)
   {
   
        $query_string = "SELECT  `c`.`id` as 
        `c_id`,
        `c`.`company_name` as `customer_name` 
        FROM `orders` as `o` 
        JOIN `customers` as `c` ON `c`.`id` = `o`.`customer_id` 
        JOIN `distributor_orders` as `dis_o` ON `dis_o`.`order_id` = `o`.`id` 
        JOIN `distributors` as `dis` ON `dis_o`.`distributor_id` = `dis`.`id` 
        WHERE  `o`.`status` IN ('Order Placed','Approved') 
        GROUP BY `c`.`company_name` DESC";
      
        $query = $this->db->query($query_string);

        $supplier_orders = $query->result_array(); 

        return $supplier_orders ;

   }

public function get_trend_order_results_export($customer, $createdate)
   {
   
        if(!empty($customer)){
             $where_cust = " `c`.`id` ='$customer' AND ";
            }else{
            $where_cust = "";
        }
        if(!empty($createdate)){
             $where_createdate = " SUBSTR(`o`.`createdate`,1,7) ='$createdate' AND ";
            }else{
            $where_createdate = "";
        }

        $query_str = "SELECT   `c`.`id` as `c_id`,`o`.`id`, 
          SUBSTR(`o`.`createdate`,1,7) as crtdates, 
          `ty`.`name` as payment,
          `dis`.`company_name` as `distributor`, 
          `p`.`name`, 
          `c`.`company_name` as `customer_name`, 
          `s`.`company_name` as `supplier`,
          `o`.`status`, `o`.`payment_type`,
          `o`.`delivery_type`, 
          `oi`.`quantity`, 
          `oi`.`price` 
          FROM `orders` as `o` 
          JOIN `customers` as `c` ON `c`.`id` = `o`.`customer_id` 
          JOIN `payment_types` as `ty` ON `ty`.`id` = `o`.`payment_type` JOIN `order_items` as `oi` ON `oi`.`id` = `o`.`id` 
          JOIN `products` as `p` ON `p`.`id` = `oi`.`product_id` 
          JOIN `suppliers` as `s` ON `s`.`id` = `p`.`supplier_id`  
          JOIN `dist_supplier_link` as `dist` ON `dist`.`supplier_id` = `p`.`supplier_id`  
          JOIN `distributor_orders` as `dis_o` ON `dis_o`.`order_id` = `o`.`id` 
          JOIN `distributors` as `dis` ON `dis_o`.`distributor_id` = `dis`.`id`
          WHERE $where_createdate $where_cust 
          `o`.`status` IN ('Order Placed','Approved')";

        return $query_str ;

   }
    function get_all_atatuses(){
  
      
      $query = $this->db->query("SELECT * FROM gbl_statuses WHERE 1");
     
      $statuses = $query->result(); 
     
      return $statuses ;
    }

    public function getDistributorStatuses()
    {
        $query = $this->db->select("id, name")
                  ->from("gbl_statuses")
                  ->where_in("id", array("1","8","9","14"))
                  ->get();
        $result = $query->result();
        return $result;
    }

    // Distribuotr Invoice

    public function getDistributorInfo($distributor_id)
    {
        $query = $this->db->select("company_name, contact_name, number, email, picture, address,
       vat_no, ck_number, bank_name, bank_account_type, bank_number, bank_branch")
                  ->from("distributors")
                  ->where("id", $distributor_id)
                  ->get();
        $result = $query->row();
        return $result;
    }

    // Distributor create deliveries Additions

    public function getDistributorTrucks($distributor_id)
    {
        $query = $this->db->select("id, licence_plate")
                  ->from("del_trucks")
                  ->where("distributor_id", $distributor_id)
                  ->get();
        $result = $query->result_array();
        return $result;
    }

    function get_distributor_wallet($distributor_order_id){

      $wallet = '0000000000';
      $query = $this->db->select("distributor_id")
                  ->from("distributor_orders")
                  ->where("id", $distributor_order_id)
                  ->get();
        $result = $query->row_array();
        if($result){
          $username = substr($wallet,0,-strlen($result['distributor_id'])).$result['distributor_id'];
        }else{
          $username = $wallet;
        }

        return $username;
    }

    public function getDistributorDrivers($distributor_id)
    {
        $group = "18";

        $query = $this->db->select("id, name")
                  ->from("aauth_users")
                  ->where("user_link_id", $distributor_id)
                  ->where("default_usergroup", $group)
                  ->get();
        $result = $query->result_array();
        return $result;
    }

    function add_order_comment($order_id, $distributor_order_id, $comment, $user_id=0){
      if ($user_id == 0 && isset($user_info->id)) {
        $user_id = $user_info->id;
      }
      $sql = "INSERT INTO order_comments (order_id, distributor_order_id, comment, user_id, createdate) VALUES (?,?,?,?,NOW())";
      $this->db->query($sql, array($order_id, $distributor_order_id, $comment, $user_id));
    }

    // Call back for add distributor comments
    public function get_Order_Id($id)
    {
        $query = $this->db->select("order_id")
                  ->from("distributor_orders")
                  ->where("id", $id)
                  ->get();
        $result = $query->row();
        return $result;
    }

    // Callbacks for Distributor Assign Approved Deliveries
    public function get_driver_name($id)
    {
        $query = $this->db->select("name")
                  ->from("aauth_users")
                  ->where("id", $id)
                  ->get();
        $result = $query->row();
        return $result;
    }

    public function get_truck_name($id)
    {
        $query = $this->db->select("licence_plate, make, model")
                  ->from("del_trucks")
                  ->where("id", $id)
                  ->get();
        $result = $query->row();
        return $result;
    }

    // Driver Delivery date function
    public function getDriverDates($driver, $date)
    {
        $query = $this->db->select("id")
                  ->from("deliveries")
                  ->where("driver", $driver)
                  ->where("DATE(date) = '$date'")
                  ->get();
        $return = $query->result();
        return $return;
    }

    // Cancel Order Resources
    public function getAllItems($order_id)
    {
        $query = $this->db->select("oi.price, oi.quantity, p.name")
                    ->from("order_items as oi")
                    ->join("products as p", "p.id = oi.product_id")
                    ->where("order_id", $order_id)
                    ->get();
        $result = $query->result_array();
        return $result; 
    }

    public function getCustomeName($order_id)
    {
        $query = $this->db->select("o.customer_id, c.company_name")
                    ->from("orders as o")
                    ->join("customers as c", "c.id = o.customer_id")
                    ->where("o.id", $order_id)
                    ->get();
        $result = $query->row();
        return $result;
    }
     public function get_orders_dist_order_id($dist_order_id){
    
      $query = $this->db->query("SELECT 
                                d.shrink_price, 
                                oi.order_id, 
                                p.name, 
                                oi.price, 
                                oi.quantity, 
                                oi.distributor_order_id, 
                                oi.id
                                FROM order_items as oi 
                                JOIN products as p 
                                ON p.id = oi.product_id
                                JOIN prod_dist_price as d 
                                ON p.id = d.product_id
                                WHERE  oi.distributor_order_id ='$dist_order_id' 
                                GROUP BY oi.id");
                                $result = $query->result_array();

      return $result;
   } 

    
      public function getDistOrder($item_id)
    {
        $query = $this->db->select("*")
                    ->from("order_items")
                    ->where("id", $item_id)
                    ->get();
        $result = $query->row_array();
        return $result; 
    }
    
    function update_order_item($item_id, $price, $quantity){
    
        $query = $this->db->query("UPDATE order_items SET price='$price', quantity ='$quantity' WHERE id='$item_id'");

    }

    function getOrderCountByCusID($customer_id,$days=30){

      $date = date('Y-m-d', strtotime("-$days days"));
      $query = $this->db->query("SELECT id, createdate FROM orders WHERE customer_id = $customer_id AND createdate > '$date' ORDER BY createdate desc");
      $result['count'] = $query->num_rows();
      if($result['count'] > 0){
        $res = $query->result_array();
        $result['date'] = date( "Y-m-d", strtotime($res[0]['createdate']));
      }else{
        $result['date'] = 'Never';
      }
      
      return $result;
    }

    function get_product_sale($distributor_id)
    {
       
      $date = date("Y-m-d 00:00:00");
        
    
     $query=$this->db->query("SELECT 
                             p.id as product_id,
                             p.name, 
                             d.number, 
                             d.email, 
                             do.distributor_id, 
                             d.*,
                             SUM(oi.quantity) as product_count,
                             s.id as special_id,
                             oi.order_id
                             FROM orders o,order_items oi, products p, distributor_orders do, distributors d, specials s
                             WHERE  o.id = oi.order_id 
                             AND p.id = oi.product_id 
                             AND do.order_id = o.id 
                             AND d.id = do.distributor_id
                             AND s.product_id = p.id
                             AND s.start_date <= '$date'
                             AND s.end_date >= '$date'
                             AND o.createdate >= '$date'
                             AND d.id ='$distributor_id'
                             GROUP BY oi.product_id");
      return $query->result_array();
           
    }
    
    function get_daily_sales($distributor_id){
   
        $distributor_info = '';
        $results  ="SPECIALS SOLD: \n";

        $sales = $this->get_product_sale($distributor_id);
       
        foreach($sales as $item){

          $results .= '<tr><td>'.$item['name'] .'</td><td> [' .$item['product_count'] . "] \n".'</td></tr>';
          $name = $item['name'];
        
         }

         $data['distributor_info'] = $this->spazapp_model->get_distributor($distributor_id);
         $data['product_info'] = $results;

         if(!empty($name)){

              //Sending email
               $this->comms_model->send_email($data['distributor_info']['email'], array('template' => 'specials_sales', 'subject' => 'SPAZAPP: Daily Specials', 'message' => $data));

              //Sending sms
               $message = $this->comms_model->fetch_sms_message('specials_sales', $data);
              $this->comms_model->send_sms($data['distributor_info']['number'] , $message);

              $this->load->view('emails/specials_sales', $data);
        return $data;
        }else{
          return false;
        }
    }

    public function getSpecialsStatus($product_id, $status_id)
    {
        $query =  $this->db->select("id, status_id, limit")
                    ->from("specials")
                    ->where("product_id", $product_id)
                    ->where("status", $status_id)
                    ->get();
        $result = $query->row();

        if($result)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getProductSpecialStatus($product_id, $status_id, $distributor_id, $count)
    {

       $date = date("Y-m-d H:i:s");
       $query = $this->db->select("id, limit, count, shrink_price")
                    ->from("specials")
                    ->where("product_id", $product_id)
                    ->where("distributor_id", $distributor_id)
                    ->where("status_id", $status_id)
                    ->where("start_date" < $date)
                    ->where("end_date" > $date)
                    ->get();

        $result = $query->row_array();

        if($result){
          if($result->limit >= ($count + $result->count))
          {
              return false;
          }
        }
        
        return $result;
    }

    public function updateSpecialsOrders($product_id, $status_id, $distributor_id, $data)
    {
        $quantity = $data['addCount'];

        $count = $this->getSpecialsCount($product_id, $status_id, $distributor_id);
        $update['count'] = $quantity + $count;

        $query = $this->db->where("product_id", $product_id)
                ->where("status_id", $status_id)
                ->where("distributor_id", $distributor_id)
                ->update("specials", $update);

        if($query)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getSpecialsCount($product_id, $status_id, $distributor_id)
    {
        $query = $this->db->select("count")
                  ->from("specials")
                  ->where("product_id", $product_id)
                  ->where("distributor_id", $distributor_id)
                  ->where("status_id", $status_id)
                  ->get();
        $result = $query->row();
        if($result){
            return $result->count;
        }else{
          return false;
        }
    }

  public function getDailyOrders($startdate,$enddate,$limit,$distributor_id='')
    {
      
      if($limit>0){
        $limit="LIMIT $limit";
      }else{
        $limit='';
      }

      if(!empty($distributor_id)){
        $where_dist = " WHERE do.distributor_id='$distributor_id'";
      }else{
        $where_dist = " ";
      }
     
      $query = $this->db->query("SELECT o.id, 
              SUBSTR(o.createdate,1,10) as order_date, 
              count(DISTINCT o.id) as order_count,
              count(DISTINCT o.customer_id) as customer_count
              FROM orders as o 
              LEFT JOIN distributor_orders as do ON do.order_id = o.id
              JOIN customers as c ON c.id = o.customer_id $where_dist
              GROUP BY SUBSTR(o.createdate,1,10) DESC $limit");

      $result['result'] = $query->result_array();
      $dailytotal=0;
      $total=0;
      foreach ($result['result'] as $key=>$r) {
          $dailytotal = $this->spazapp_model->get_order_total_by_date($r['order_date']);
          if(!empty($distributor_id)){
            $dailytotal = $this->spazapp_model->get_dist_order_total_by_date($distributor_id, $r['order_date']);
          }
          $data['data'][]=array(
            'order_date'=>$r['order_date'],
            'order_count'=>$r['order_count'],
            'customer_count'=>$r['customer_count'],
            'total'=>  $dailytotal 
            );
          $total+=$dailytotal;
      }
      $data['total']=$total;
      return $data;
     
 
    }  


  public function monthlyOrderStats($distributor_id, $createdate=''){

        if(!empty($distributor_id)){
          $where_dist = " and do.distributor_id='$distributor_id'";
        }else{
          $where_dist = " ";
        }

        if(!empty($createdate)){
          $where_date = " WHERE SUBSTR(`o`.`createdate`,1,7) < '$createdate'";
        }else{
          $where_date='';
        }

        $query_str = "SELECT SUBSTR(`o`.`createdate`,1,7) as order_date, 
                      SUBSTR(`o`.`createdate`,1,7) as crtdates,
                      count(DISTINCT `oi`.`order_id`) as count_orders
                      FROM `orders` as `o` 
                      LEFT JOIN distributor_orders as do ON do.order_id = o.id
                      JOIN `order_items` as `oi` ON `o`.`id` = `oi`.`order_id`
                      JOIN `products` as `p` ON `oi`.`product_id` = `p`.`id`
                      $where_date 
                      GROUP BY SUBSTR(`o`.`createdate`,1,7) DESC LIMIT 13";
      //print_r($query_str);
        $query = $this->db->query($query_str);
       
        $supplier_orders = $query->result_array(); 

        return $supplier_orders ;
  }

public function getOrdersTotalByDate($createdate, $type='', $distributor_id=''){
     
      $this->db->select("o.id as order_id,
        c.company_name as customer, 
        c.cellphone,
        c.address, 
        c.location_lat, 
        c.location_long, 
        o.status, 
        o.payment_type, 
        o.delivery_type,
        o.status,
        o.createdate,
       round(sum(oi.price*oi.quantity),2) as total");
      $this->db->from("orders as o");
      $this->db->join("customers as c", "c.id = o.customer_id");
      $this->db->join("distributor_orders as do", "do.order_id = o.id");
      $this->db->join("order_items as oi", "oi.order_id = o.id");
      $this->db->join("products as p", "p.id = oi.product_id");
      $this->db->join("suppliers as s", "s.id = p.supplier_id");
      $this->db->where("SUBSTR(o.createdate,1,10) = '".$createdate."'");
     if(!empty($distributor_id)){
      $this->db->where('do.distributor_id',$distributor_id);
     }
     $this->db->group_by('o.id');
     $query = $this->db->get();

      $total=0;
      if($type=='total'){
        $results=$query->result_array();
        foreach ($results as $key => $value) {
          if(!empty($value['total'])){
            $total+=$value['total'];
          }
          
        }
        return $total;
      }else{
        return $query->result_array();
      }
      
}

 public function getOrderIdByDistOrderId($dis_order_id){
    $query = $this->db->select("*")
          ->from("orders as o")
          ->join("distributor_orders as do", "o.id = do.order_id")
          ->where("do.id", $dis_order_id)
          ->get();
    return $query->row_array();
 }

 function get_value_of_sales(){
    
    $query=$this->db->query("SELECT * ".$this->order_total_sql());
    $result = $query->result_array();

    $o_query = $this->db->query("SELECT order_type, payment_type, delivery_type FROM basket_orders WHERE 1");
    $order = $o_query->row_array();

    $i_query = $this->db->query("SELECT distributor_id, product_id, price, quantity FROM basket_order_items WHERE 1");
    $order['items'] = $i_query->result_array();

    return $order;

 }


 
  function get_customer_repeat_orders(){  
        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');
        $where_date='';
        if(!empty($date_to)){
          $where_date="and o.createdate>='$date_from' and o.createdate<='$date_to'";
        }
        $query=$this->db->query("SELECT * FROM orders as o 
                            JOIN customers as c ON c.id=o.customer_id
                            WHERE o.status != 'Cancelled' $where_date  
                            GROUP BY o.customer_id HAVING COUNT(o.customer_id) >= 2");
        return $query->num_rows(); 
  }



  function get_order_details($order_id){  
      $sql="SELECT p.name as product, g.name as status,oi.price, oi.quantity,
            o.createdate, s.company_name as supplier,
            c.company_name as customer
            FROM orders as o 
            JOIN order_items as oi ON o.id=oi.order_id
            JOIN products as p ON p.id=oi.product_id
            LEFT JOIN distributor_orders as do ON do.order_id=o.id
            JOIN gbl_statuses as g ON g.id=do.status_id
            LEFT JOIN customers as c ON c.id=o.customer_id
            JOIN suppliers as s ON s.id=p.supplier_id
            JOIN payment_types as pt ON pt.id=o.payment_type
            WHERE do.status_id='9' and o.id='$order_id'";
        $query=$this->db->query($sql);
        $data['result']=$query->result_array(); 
        $data['query']=$sql;
        return $data;
  }

  function get_basket($deal_id){

    $o_query = $this->db->query("SELECT order_type, payment_type, delivery_type, replaced_order FROM basket_orders WHERE id = $deal_id");
    $order = $o_query->row_array();

    $i_query = $this->db->query("SELECT distributor_id, product_id, price, quantity FROM basket_order_items WHERE order_id = $deal_id");
    $order['items'] = $i_query->result_array();

    return $order;

   }

function get_basket_order_items($order_id){
        $query=$this->db->query("SELECT * FROM basket_order_items WHERE order_id='$order_id'");
        return $query->num_rows(); 
}

 function get_no_location_order(){
      $query=$this->db->query("SELECT 
                        o.id as 'order_id', 
                        d.id as 'distributor_order_id',
                        c.id, 
                        c.first_name, 
                        c.last_name, 
                        c.company_name, 
                        c.cellphone,
                        c.*
                        FROM orders o, customers c, distributor_orders d 
                        WHERE o.id = d.order_id 
                        AND o.customer_id = c.id
                        AND c.location_long = '' 
                        order by o.id desc limit 100");
      return $query->result_array();
     }


    function supplierSalesTotal($distributor_id){
      $date_from = $this->input->post('date_from');
      $date_to = $this->input->post('date_to');

      if(!empty($date_to)){
        $where_date="and o.createdate>='$date_from' and o.createdate <= '$date_to'";
      }else{
        $where_date='';
      }

      $where_dist='';
      if(!empty($distributor_id)){
          $where_dist=" and do.distributor_id='$distributor_id'";
      }
       $query=$this->db->query("SELECT 
                                sum(oi.price*oi.quantity) as total,
                                s.company_name,
                                s.id as supplier_id
                                FROM distributor_orders as do 
                                JOIN orders as o ON o.id = do.order_id
                                JOIN order_items as oi ON oi.distributor_order_id = do.id
                                JOIN products as p ON p.id=oi.product_id
                                JOIN suppliers as s ON s.id=p.supplier_id
                                WHERE o.status != 'Cancelled' $where_date $where_dist
                                GROUP BY p.supplier_id");

      return $query->result_array();
   }

    function get_spark_store_sales(){
      $date_from = $this->input->post('date_from');
      $date_to = $this->input->post('date_to');

      if(!empty($date_to)){
        $where_date="and o.createdate>='$date_from' and o.createdate <= '$date_to'";
      }else{
        $where_date='';
      }

       $query=$this->db->query("SELECT 
                                sum(oi.price*oi.quantity) as 'total',
                                count(o.id) as 'sales',
                                s.id as 'spark_id',
                                s.cellphone as 'spark_cell',
                                CONCAT(s.first_name, ' ', s.last_name) as 'spark_name'
                                FROM orders as o 
                                JOIN order_items as oi ON oi.order_id = o.id
                                JOIN customers as c ON o.customer_id = c.id
                                JOIN traders as s ON s.id = c.trader_id
                                WHERE o.status != 'Cancelled' $where_date
                                GROUP BY c.trader_id");

      return $query->result_array();
   }

   function getNumberOfCustomersPlacedOrder(){

      $date_from = $this->input->post('date_from');
      $date_to = $this->input->post('date_to');  


      if(!empty($date_to)){
        $where_date="WHERE o.createdate >= '$date_from 00:00:00' and o.createdate <= '$date_to  00:00:00'";
      }else{
        $where_date='';
      }

      $query=$this->db->query("SELECT * FROM orders as o 
                              JOIN customers as c ON o.customer_id=c.id $where_date GROUP BY o.customer_id");
   
      return $query->num_rows();

   }

  function getNumberOfOrders($delivered){
    if(empty($delivered)){
        $delivered='';
    }else{
        $delivered=" and status = 'Delivered' ";
    }
    $date_from = $this->input->post('date_from');
    $date_to = $this->input->post('date_to');
    

    if(!empty($date_to)){
      $where_date="and createdate>='$date_from' and createdate <= '$date_to'";
    }else{
      $where_date='';
    }
    $query=$this->db->query("SELECT * FROM orders 
                              where status != 'Cancelled' $delivered $where_date");
    return $query->num_rows();

   }


 function getNumberOfOrdersWithSpark(){

    $date_from = $this->input->post('date_from');
    $date_to = $this->input->post('date_to');   

    if(!empty($date_to)){
      $where_date="and o.createdate>='$date_from' and o.createdate <= '$date_to'";
    }else{
      $where_date='';
    }
    $query=$this->db->query("SELECT * FROM orders AS o
                              JOIN customers AS c ON o.customer_id = c.id
                              where c.trader_id > 0 AND o.status != 'Cancelled' $where_date");
    return $query->num_rows();

   }


   function getSupplierSales($distributor_id){

    echo $distributor_id;

    $date_from = $this->input->post('date_from');
    $date_to = $this->input->post('date_to');

    if(!empty($date_to)){
      $where_date="and o.createdate>='$date_from' and o.createdate <= '$date_to'";
    }else{
      $where_date='';
    }
    
    $where_dist='';
    if(!empty($distributor_id)){
        $where_dist=" and do.distributor_id='$distributor_id'";
    }
    $query=$this->db->query("SELECT o.*, oi.*,do.*,p.*,g.*,s.*, 
                              c.company_name as customer,
                              pt.name as payment_type,
                              o.createdate,
                              sum(oi.price*oi.quantity) as total
                              FROM orders as o 
                              JOIN order_items as oi ON o.id=oi.order_id 
                              JOIN customers as c ON c.id=o.customer_id 
                              JOIN payment_types as pt ON o.payment_type=pt.id 
                              JOIN products as p ON p.id=oi.product_id
                              JOIN distributor_orders as do ON do.order_id=o.id 
                              JOIN gbl_statuses as g ON g.id=do.status_id
                              JOIN suppliers as s ON s.id=p.supplier_id
                              WHERE o.status='Order Placed' 
                              $where_date $where_dist GROUP BY o.id");

    return $query->result_array();
   }


   function getSupplierSalesSimple($distributor_id){


    $date_from = $this->input->post('date_from');
    $date_to = $this->input->post('date_to');

    if(!empty($date_to)){
      $where_date="and o.createdate>='$date_from' and o.createdate <= '$date_to'";
    }else{
      $where_date='';
    }
    
    $where_dist='';
    if(!empty($distributor_id)){
        $where_dist=" and do.distributor_id='$distributor_id'";
    }

    $sql = "SELECT oi.*,do.*,p.*,g.name as 'status_name',s.*, 
                              c.company_name as customer,
                              pt.name as payment_type,
                              o.createdate,
                              sum(oi.price*oi.quantity) as total
                              FROM distributor_orders as do
                              JOIN orders as o ON o.id=do.order_id
                              JOIN customers as c ON c.id=o.customer_id 
                              JOIN payment_types as pt ON o.payment_type=pt.id
                              JOIN order_items as oi ON do.id=oi.distributor_order_id 
                              JOIN products as p ON p.id=oi.product_id
                              JOIN gbl_statuses as g ON g.id=do.status_id
                              JOIN suppliers as s ON s.id=p.supplier_id
                              WHERE o.status !='Cancelled'
                              $where_date $where_dist GROUP BY do.id";


    $query=$this->db->query($sql);

    return $query->result_array();
   }


function getSupplierDeliveredOrdersTotal($supplier_id){

      $date_from = $this->input->post('date_from');
      $date_to= $this->input->post('date_to');
      $distributor_id = $this->input->post('distributor_id');

      if(!empty($distributor_id)){
        $where_dist=" and do.distributor_id='$distributor_id'";
      }else{
        $where_dist='';
      }
      if(!empty($date_to)){
        $where_date="and o.createdate>='$date_from' and o.createdate <= '$date_to'";
      }else{
        $where_date='';
      }

       $query=$this->db->query("SELECT 
                                sum(oi.price*oi.quantity) as total,
                                s.company_name,
                                s.id as supplier_id
                                FROM orders as o 
                                JOIN order_items as oi ON o.id=oi.order_id 
                                JOIN products as p ON p.id=oi.product_id
                                JOIN suppliers as s ON s.id=p.supplier_id
                                JOIN distributor_orders as do ON do.order_id=o.id 
                                WHERE do.status_id = 9 $where_date $where_dist
                                and p.supplier_id='$supplier_id' $where_date $where_dist");

      return $query->row_array();
   }

   function get_distributor_order($order_id, $distributor_id=false){

    if($distributor_id){
     $query = $this->db->query("SELECT *
                              FROM distributor_orders 
                              WHERE order_id=? AND distributor_id = ?",array($order_id, $distributor_id));
   }else{

      $query = $this->db->query("SELECT *
      FROM distributor_orders 
      WHERE order_id=? limit 1",array($order_id));

   }

     return $query->row_array();
   }

 function insert_stokvel_basket($stokvel_id, $order_info){
    $this->load->model('stokvel_model');
    $order = array();

    $stokvel = $this->stokvel_model->get_stokvel_info($stokvel_id);

    $distributor_id = $stokvel['distributor_id'];

    $order['basket_name'] = $order_info['basket_name'];
    $order['distributor_id'] = $distributor_id;
    $order['stokvel_id'] = $order_info['stokvel_id'];
    $order['status_id'] = 1;
    $order['delivery_type'] = 'collect';
    $order['discount'] = 0;
    $order['createdate'] = date("Y-m-d H:i:s");

    $this->db->insert('basket_orders', $order);
    $basket_id = $this->db->insert_id();
    $order['id'] = $basket_id;

    foreach ($order_info['items'] as $key => $product) 
    {
        $item['order_id'] = $order['id'];
        $item['distributor_id'] = $distributor_id;
        $item['product_id'] = $product['product_id'];
        $item['price'] = 0;
        $item['quantity'] = $product['quantity'];
        $order['items'][] = $item;
        $this->db->insert('basket_order_items', $item);
    }

    $this->event_model->track_event('app', 'basket_saved', 'a basket was added through the app', $basket_id);
       
    $data['status'] = 'success';
    $data['order'] = $order;

    return $data;  
 }

 function update_stokvel_basket($basket_id, $stokvel_id, $order_info){
    $this->load->model('stokvel_model');
    $stokvel = $this->stokvel_model->get_stokvel_info($stokvel_id);

    $order = array();

    $distributor_id = $stokvel['distributor_id'];  

    if(isset($order['delivery_date'])){
      $order['delivery_date'] = $order_info['delivery_date'];
    }
    if (isset($order['payment_type'])) {
      $order['payment_type'] = $order_info['payment_type'];
    }

    $order['id'] = $basket_id;
    $order['distributor_id'] = $distributor_id;
    $this->delete_basket_approvals($basket_id);

    if(isset($order_info['items'])){
      $this->delete_basket_order_items($basket_id);
      foreach ($order_info['items'] as $key => $product) 
      {
          $item['order_id'] = $order['id'];
          $item['distributor_id'] = $distributor_id;
          $item['product_id'] = $product['product_id'];
          $item['price'] = 0;
          $item['quantity'] = $product['quantity'];
          $order['items'][] = $item;
          $this->db->insert('basket_order_items', $item);
      }

      $this->event_model->track_event('app', 'basket_updated', 'a basket was updated through the app', $basket_id);
      $order['status_id'] = 1;

    }

    if(!empty($order)){
      $odup = $order;
      unset($odup['items']);
      $this->db->where('id', $basket_id);
      $this->db->update('basket_orders', $odup);
    }
      
    $data['status'] = 'success';
    $data['order'] = $order;

    return $data;  
 }

   function delete_basket_order_items($basket_id){
    $this->db->query("DELETE FROM basket_order_items WHERE order_id = $basket_id");
   }

function delete_basket_approvals($basket_id){
  $this->db->query("DELETE FROM basket_order_approvals WHERE basket_id = $basket_id");
}

  function submit_basket_for_quote($basket_id){

    $this->load->model('stokvel_model');
    $order = $this->app_model->get_basket($basket_id);
    $user = $this->stokvel_model->get_user_from_customer_id($order['customer_id']);
    $this->db->where("id", $basket_id);
    if($this->db->update('basket_orders', array('status_id' => 21))){
      $this->comms_library->queue_comm_group($user['id'], 'stokvel_quotation_request', $order);
      $this->comms_library->queue_comm_group($order['items'][0]['distributor_id'], 'distro_quotation_request', $order, 'distributor');

      return array("basket_id" => $basket_id);
    }
    return false;

  }

  function place_basket_order($basket_id,$customer_id){

    $this->load->model('stokvel_model');
    $order = $this->app_model->get_basket($basket_id);

    $user = $this->stokvel_model->get_user_from_customer_id($customer_id);


    $this->db->where("id", $basket_id);
    if($this->db->update('basket_orders', array('status_id' => 22))){
      $this->comms_library->queue_comm_group($user['id'], 'stokvel_order_placed', $order);
     
     //Can you please explain the line below
      //$this->comms_library->queue_comm_group($order['items'][0]['distributor_id'], 'distro_order_placed', $order, 'distributor');

      return array("basket_id" => $basket_id);
    }
    return false;

  }

  function copy_basket($basket_id, $basket_name){

    $this->load->model('stokvel_model');
    $order = $this->app_model->get_basket($basket_id);
    $stokvel_id = $order['stokvel_id'];
    unset($order['createdate']);
    unset($order['stokvel_id']);
    $order['basket_name'] = $basket_name;
    $result = $this->insert_stokvel_basket($stokvel_id, $order);
    if($result['status'] == 'success'){
      return $result['order'];
    }
    return false;

  }

}
