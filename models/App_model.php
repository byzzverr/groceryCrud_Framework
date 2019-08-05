<?php

class App_model extends CI_Model { 

   public function __construct()
   {
      parent::__construct();
      $this->load->model('customer_model');
      $this->load->model('order_model');
      $this->load->library('comms_library');
      $this->app_settings = get_app_settings(base_url());
   }

   function get_distributor_info($id){
    $result = $this->db->query("SELECT * FROM distributors WHERE id = $id");
    return $result->row_array();
   }

  function save_raw_data($data,$source='app',$function='0'){
    $this->db->query("INSERT INTO `app_raw_data` (raw_data, source, function, createdate) VALUES (?,?,?,NOW())", array($data,$source,$function));
  }

  function get_order_items_total($order_items){
    $order_total = 0;
    foreach ($order_items as $order_key => $items) {
      $order_total += $order['price']*$order['quantity'];
    }
    return $order_total;
  }

  function calculate_drops_and_total($order_info){

    $order_total = 0;
    foreach ($order_info['items'] as $key => $value) {
      $distributor_orders[$value['distributor_id']][] = $value;
      $order_total += $value['price']*$value['quantity'];
    }

    $order_info['total'] = $order_total;
    $order_info['drops'] = count($distributor_orders);
    $order_info['delivery_charge'] = 0;
    unset($order_info['delivery_charge']);

    return $order_info;
  }

  function find_best_deals($order_info){

    //delivery charges
    $delivery_charges = array();
    $delivery_charge = 0;
    $distributor_orders = array();
    $grand_total = 0;
    $all_products = array();

    foreach ($order_info['items'] as $key => $value) {
      $distributor_orders[$value['distributor_id']][] = $value;
      $grand_total += $value['price']*$value['quantity'];
      $all_products[] = $value['product_id'];
    }

    foreach ($distributor_orders as $key => $dist_order) {
      $distributor = $this->get_distributor_info($key);
      $order_total = 0;
      $products = array();
      if($distributor['delivery_charge'] > 0 && $distributor['minimum_basket'] > 0){

        foreach ($dist_order as $order_key => $order) {
          $products[] = $order['product_id'];
          $order_total += $order['price']*$order['quantity'];
        }

        if($order_total < $distributor['minimum_basket']){
          $delivery_charge += $distributor['delivery_charge'];
          $delivery_charges[] = array(
              'distributor_id'  => $value['distributor_id'],
              'order_total'     => $order_total,
              'delivery_charge' => $distributor['delivery_charge'],
              'minimum_basket'  => $distributor['minimum_basket'],
              'difference'      => $distributor['minimum_basket']-$order_total,
              'products'        => $products
            );
        }
      }
    }

    $order_info['delivery_charge'] = $delivery_charge;
    $order_info['delivery_charges'] = $delivery_charges;
    $order_info['total'] = $grand_total+$delivery_charge;
    $order_info['drops'] = count($distributor_orders);
   
    //if there are no delivery charges it means we cannot make it any cheaper.
    if($delivery_charge == 0){
      return array("deal1" => $order_info, "deal2" => $order_info);
    }else{

      $distributors = $this->product_model->get_product_distributors_in_region($order_info['region_id'], $all_products);

      //there is only one distributor for this product in that region
      if($distributors <= 1){
        return array("deal1" => $order_info, "deal2" => $order_info);
      }

      //see if there are 2 distro's maybe you can merge some of the products in to remove the delivery charge.

      if(count($delivery_charges) == 1 && $order_info['drops'] != 1 && ($order_info['total']-$delivery_charges[0]['delivery_charge']) >= $delivery_charges[0]['minimum_basket']){
        //see how many products we can push to this distro.
        $deal2 = $this->re_populate_order($order_info, $delivery_charges[0]['distributor_id']);
        if(count($deal2['items']) == count($order_info['items'])){
              //if entire order can go there we are done
              $deal2 = $this->calculate_drops_and_total($deal2);
              return array("deal1" => $order_info, "deal2" => $deal2);
        }
        if(count($deal2['items']) < count($order_info['items'])){    
              $order_segment_total = $this->get_order_items_total($deal2['items']);
              if($order_segment_total >= $delivery_charges[0]['minimum_basket']){
                $deal2['items'] = array_merge($deal2['items'], $order_info['items']);
                //when you add false at the end it wont take cheapest. first come first added. deal2 added first in array.
                $deal2['items'] = $this->clean_item_duplicates($deal2['items'], false);
                $deal2 = $this->calculate_drops_and_total($deal2);
                return array("deal1" => $order_info, "deal2" => $deal2);
              }

        }
      }

      // if we cannot add to a basket to remove charge find people to deliver for free.

       //remove the distro we know has a delivery charge.
       unset($distributors[$delivery_charges[0]['distributor_id']]);
       //see if any of the others have free delivery
       $deal2items = array();
       foreach ($distributors as $distro) {
         $tempD = $this->get_distributor_info($distro);
         if($tempD['delivery_charge'] == 0){
            //distro has no delivery charge. try push order there.
            $deal2 = $this->re_populate_order($order_info, $distro);
            if(count($deal2['items']) == count($order_info['items'])){
              //if entire order can go there then we are done.
              $deal2 = $this->calculate_drops_and_total($deal2);
              return array("deal1" => $order_info, "deal2" => $deal2);
            }else{
              //entire order could not go to one distro. so try the next.
              $deal2items = array_merge($deal2items, $deal2['items']);
            }
         }
       }

       if(count($deal2items) == count($order_info['items'])){
          $deal2 = $order_info;
          $deal2['items'] = $deal2items;
          //ok nice we removed delivery charge.
          $deal2 = $this->calculate_drops_and_total($deal2);
          return array("deal1" => $order_info, "deal2" => $deal2);
       }else{
        if(count($deal2items) < count($order_info['items'])){
          //we are screwed. customer must pay for delivery.
          return array("deal1" => $order_info, "deal2" => $order_info);
        }
        if(count($deal2items) > count($order_info['items'])){
          $deal2items = $this->clean_item_duplicates($deal2items);
          $deal2 = $order_info;
          $deal2['items'] = $deal2items;
          $deal2 = $this->calculate_drops_and_total($deal2);
          return array("deal1" => $order_info, "deal2" => $deal2);
        }
      }

    }
    //if we couldnt solve it after all that. then give them back the original order.
    return array("deal1" => $order_info, "deal2" => $order_info);

  }


  function re_populate_order($order, $distributor_id){

        foreach ($order['items'] as $key => $product) 
        {
          /*$customer_type, $region_id, $category_id, $specials=false, $product_id=false, $distributor_id=false)*/
            $latest_product = $this->product_model->get_products_for_region(0, $order['region_id'], 0, false, $product['product_id'], $distributor_id);

            if($latest_product){
                $supplier_id = $latest_product['supplier_id'];
                $distributor_id = $latest_product['distributor_id'];

                $status_id = '8';

                $order['items'][$key]['product_id'] = $product['product_id'];
                $order['items'][$key]['supplier_id'] = $supplier_id;
                $order['items'][$key]['distributor_id'] = $distributor_id;
                $order['items'][$key]['quantity'] = $product['quantity'];
                $order['items'][$key]['price'] = $latest_product['shrink_price'];
                if($latest_product['is_special_now'] == 1){
                    $order['items'][$key]['price'] = $latest_product['special_shrink_price'];
                }
            }
        }

        return $order;
  }

  function clean_item_duplicates($items, $cheapest=true){

    $products = array();
    foreach ($items as $key => $item) {
      if(isset($products[$item['product_id']])){
        if($products[$item['product_id']]['price'] > $item['price'] && $cheapest){
          $products[$item['product_id']] = $item;
        }
      }else{
        $products[$item['product_id']] = $item;
      }
    }

    return $products;
  }

  function insert_order($customer_id, $order_info, $basket=false){

    if($basket == false){
      $order_table = 'orders';
      $order_items_table = 'order_items';
    }else{
      $order_table = 'basket_orders';
      $order_items_table = 'basket_order_items';
    }

    $customer = $this->customer_model->get_customer($customer_id);

    //check if payment type is Available
    if(!$this->payment_type_available($order_info['payment_type'])){
      return array('status' => 'fail', 'message' => 'The payment option you selected is not Available.');
    }

    if($order_info['delivery_type'] == 1){
      $order_info['delivery_type'] = 'cash van';
    }elseif($order_info['delivery_type'] == 2){
      $order_info['delivery_type'] = 'collect';
    }else{
      $order_info['delivery_type'] = 'deliver';
    }


    $order_cost = 0;
    foreach ($order_info['items'] as $item_key => $item) {
      $order_cost = $order_cost+($item['price']*$item['quantity']);
    }

    $account_order = false;
    //if this should come off their account
    if(($order_info['payment_type'] == 4 || $order_info['payment_type'] == 'Account') && $basket == false){
      $balance = $customer['balance'];

      if($order_cost > $balance){
        return array('status' => 'fail', 'message' => 'Insuficcient funds in your account.');
      }else{
        $account_order = true;
      }
    }

    $rewards_order = false;
    //if this should come off their rewards
    if($order_info['payment_type'] == 5){
      $rewards = $customer['rewards'];

      if($order_cost > $rewards){
        return array('status' => 'fail', 'message' => 'Insuficcient rewards points in your account.');
      }else{
        $rewards_order = true;
      }
    }

    if($basket){
      //need this for basket id
      $order_details = array($customer_id, $order_info['basket_id'], $order_info['replaced_order'], $order_info['payment_type'], $order_info['order_type'], $order_info['delivery_type']);
      $this->db->query("INSERT INTO `$order_table` (customer_id, basket_id, replaced_order, payment_type, order_type, status, delivery_type, createdate) VALUES (?,?,?,?,?,'Order Placed',?,NOW())", $order_details);
      $order_id = $this->db->insert_id();
      
    }else{

      /* GENERATE MASTERPASS QR CODE */

      $user = $this->user_model->get_user_from_link_id($customer_id, 8);
      $user_id = $user->id;
      $this->load->library('masterpass');

      $mpdata["user_id"] = $user_id;
      $mpdata["amount"] = $order_cost;
      $mpdata["merchantReference"] = $user_id.'_'.date("Ymd_His");
      $mpdata["useOnce"] = true;
      
      $result = $this->masterpass->create_code($mpdata);
      
      if($result){
          $masterpass_code = $result['code'];
      }else{
          $masterpass_code = NULL;
      }
      
      $this->db->query("INSERT INTO `$order_table` (customer_id, payment_type, order_type, status, delivery_type, masterpass_code, createdate) VALUES (?,?,?,'Order Placed',?,?,NOW())", 
        array($customer_id,$order_info['payment_type'], $order_info['order_type'], $order_info['delivery_type'], $masterpass_code));
      $order_id = $this->db->insert_id();

      $this->db->query("UPDATE masterpass_codes SET order_id = $order_id WHERE code = $masterpass_code");
    }
   
    //loop through order items and build distributor order array.
    $distributor_orders = array();
    foreach ($order_info['items'] as $key => $value) {
      $distributor_orders[$value['distributor_id']][] = $value;
    }

    foreach ($distributor_orders as $key => $dist_order) {

      if($basket){

        foreach ($dist_order as $dist_order_item) {

          $this->db->query("INSERT INTO `$order_items_table` (order_id, distributor_id, product_id, price, quantity) VALUES (?,?,?,?,?)", 
            array($order_id, $key, $dist_order_item['product_id'],$dist_order_item['price'],$dist_order_item['quantity']));

          $order_item_id = $this->db->insert_id();

        }
        
      }else{

          $this->db->query("INSERT INTO `distributor_orders` (order_id, distributor_id, status_id) VALUES (?,?,1)", array($order_id,$key));
          $distributor_order_id = $this->db->insert_id();

          foreach ($dist_order as $dist_order_item) {
            $this->db->query("INSERT INTO `$order_items_table` (order_id, distributor_order_id, product_id, price, quantity) VALUES (?,?,?,?,?)", 
              array($order_id, $distributor_order_id, $dist_order_item['product_id'],$dist_order_item['price'],$dist_order_item['quantity']));
            $order_item_id = $this->db->insert_id();

          }
      }

    }

    if($account_order){
      $reference = 'spazapp_order-'.$order_id;
      $this->financial_model->remove_from_customer_account('sale', $customer_id, $order_cost, $order_id, 0, $reference);
    }


    if($basket == false){

      $this->load->model('spazapp_model');
      $this->spazapp_model->send_order_comms($order_id);
      if($order_info['order_type'] != 'pos'){
        $this->spazapp_model->place_distributor_orders($order_id);
        $spark_comm = $this->financial_model->calculate_instant_spark_commission($order_cost);
        $this->financial_model->assign_spark_order_comm($spark_comm, $customer_id, $order_id);
      }
    
    }



    return array('status' => 'success', 'message' => $order_id);
	}

  function insert_dist_order($customer_id, $order, $order_info, $distributor_order_id=''){

    $account_order = false;
    $order_cost = 0;
    foreach ($order_info['items'] as $item_key => $item) {
      $order_cost = $order_cost+($item['price']*$item['quantity']);
    }
    
    //if this should come off their account
    if($order_info['payment_type'] == 4 || $order_info['payment_type'] == 'Account'){
       $account_order = true;
    }

    if($distributor_order_id == ''){
      $this->db->query("INSERT INTO `distributor_orders` (order_id, distributor_id, status_id) VALUES (?,?,1)", array($order_info['order_id'],$order_info['distributor_id']));
      $distributor_order_id = $this->db->insert_id();
    }else{
      $this->db->query("INSERT INTO `distributor_orders` (id, order_id, distributor_id, status_id) VALUES (?,?,?,16)", array($distributor_order_id, $order_info['order_id'],$order_info['distributor_id']));
    }

    foreach ($order['items'] as $dist_order_item) {
      $this->db->query("INSERT INTO `order_items` (order_id, distributor_order_id, product_id, price, quantity) VALUES (?,?,?,?,?)", 
        array($order_info['order_id'], $distributor_order_id, $dist_order_item['product_id'],$dist_order_item['price'],$dist_order_item['quantity']));
      $order_item_id = $this->db->insert_id();
    }

    if($account_order){
      $this->financial_model->remove_from_customer_account('account', $customer_id, $order_cost, $distributor_order_id, 0, 'replacement distributor order. Make purchase.');
    }

      $category = 'delivery';
      $action = 'order_replaced';
      $label  = 'Driver edited the order on delivery, replacing the old order with this one.';
      $value  = $distributor_order_id;

      $this->event_model->track_event($category, $action, $label, $value);
      $this->order_model->add_order_comment($order_info['order_id'], $distributor_order_id,  $label);

      $this->db->query("INSERT INTO `rewards` (customer_id, reward, reason, createdate) VALUES (?,?,?,NOW())", array($customer_id, ($order_cost/100), $distributor_order_id));

      return array('status' => 'success', 'message' => $distributor_order_id);
  }

  function replacement_dist_order($customer_id, $order, $order_info){

    $account_order = false;
    $order_cost = 0;
    foreach ($order_info['items']['items'] as $item_key => $item) {
      $order_cost = $order_cost+($item['price']*$item['quantity']);
    }

    //if this should come off their account
    if($order_info['payment_type'] == 4 || $order_info['payment_type'] == 'Account'){
       $account_order = true;
    }


      $this->db->query("INSERT INTO `distributor_orders` (order_id, distributor_id, status_id) VALUES (?,?,15)", array($order_info['order_id'],$order_info['distributor_id']));
      $distributor_order_id = $this->db->insert_id();

      foreach ($order['items'] as $dist_order_item) {
        $this->db->query("INSERT INTO `order_items` (order_id, distributor_order_id, product_id, price, quantity) VALUES (?,?,?,?,?)", 
          array($order_info['order_id'], $distributor_order_id, $dist_order_item['product_id'],$dist_order_item['price'],$dist_order_item['quantity']));
        $order_item_id = $this->db->insert_id();
      }


    $category = 'delivery';
    $action = 'order_replaced';
    $label  = 'This is a copy of the original order that was replaced.';
    $value  = $distributor_order_id;

    if($account_order){
      $this->order_model->add_order_comment($order_info['order_id'], $distributor_order_id,  'Refunded the amount of R'.$order_cost.' to customer.');
      $this->financial_model->add_to_spazapp_customer_account('refund', $customer_id, $order_cost, $distributor_order_id, 0, 'spazapp_refund_do-'.$distributor_order_id);
    }

    $this->event_model->track_event($category, $action, $label, $value);
    $this->order_model->add_order_comment($order_info['order_id'], $distributor_order_id,  $label);

    $this->db->query("INSERT INTO `rewards` (customer_id, reward, reason, createdate) VALUES (?,?,?,NOW())", array($customer_id, ($order_cost/100)*-1, $distributor_order_id));

  }

  function is_airtime_product($product_id){

    $query = $this->db->query("SELECT a.category_id FROM `products` a, categories b where a.id = ? and a.category_id = b.id and b. name like '%airtime%'", array($product_id));
    if($query->num_rows() == 1){
      return true;
    }
    return false;

  }

  function get_user($user_id){
      $query = $this->db->query("SELECT * FROM `aauth_users` WHERE id = $user_id");
      return $query->row();
  }

  function payment_type_available($payment_type){
    $query = $this->db->query("SELECT * FROM `payment_types` where status = 'Available' and id = ?", array($payment_type));
    if($query->num_rows() == 1){
      return true;
    }
    return false;
  }

  function get_payment_types(){
      $query = $this->db->query("SELECT * FROM `payment_types` WHERE status = 'Available' order by name desc");
      return $query->result_array();
  }

  function get_news_list(){
      $query = $this->db->query("SELECT * FROM `news`");
      return $query->result_array();
  }

  function get_news_item($news_id){
      $query = $this->db->query("SELECT * FROM `news` where id = ?", array($news_id));
      return $query->row_array();
  }

  function get_customer_info($id){
      return $this->customer_model->get_customer($id);
  }

  function get_basket_list($customer_id, $type='customer'){

    if($type == 'customer'){
      $field = 'b.customer_id';
    }

    if($type == 'stokvel'){
      $field = 'b.stokvel_id';
    }

    $query = $this->db->query("SELECT b.*, s.name as 'status_name' FROM `basket_orders` b, gbl_statuses s WHERE b.status_id = s.id AND $field = ? order by b.id desc", array($customer_id));
      $orders = $query->result_array();
      foreach ($orders as $key => $order) {
        unset($orders[$key]['basket_id']);
        $deldateraw = explode(' ', $order['delivery_date']);
        $orders[$key]['delivery_date'] = $deldateraw[0];
      }
      return $orders;
  }

  function get_basket($order_id){
      $query = $this->db->query("SELECT b.*, s.name as 'status_name' FROM `basket_orders` b, gbl_statuses s WHERE b.status_id = s.id AND b.id = ?", array($order_id));
      $order = $query->row_array();
      
      unset($order['basket_id']);
      $deldateraw = explode(' ', $order['delivery_date']);
      $order['delivery_date'] = $deldateraw[0];

      $customer_id = (isset($order['customer_id']) ? $order['customer_id'] : 0);

      // set third parameter to true if you want basket instead of order.

      if($this->app_settings['app_name'] == 'stokvel'){
        $items = $this->get_basket_items($order_id);
        if($order['status_id'] == 30){
          $wallet_discount = array(
            "id"  => "999999",
            "order_id"  => $order_id,
            "distributor_id"  => 0,
            "product_id"  => "9999999",
            "product_name"  => "Wallet Balance",
            "price" => -$this->financial_model->get_balance($customer_id),
            "quantity"  => "1"
        );
          $items['items'][] = $wallet_discount;
          $items['total_amount'] = $items['total_amount']-$wallet_discount;
        }
      }else{
        $items = $this->get_order_items($order_id, $customer_id, true);
      }

      return array_merge($order,$items);
  }

  function get_orders_list($customer_id){
      $query = $this->db->query("SELECT * FROM `orders` WHERE customer_id = ? order by id desc", array($customer_id));
      $orders = $query->result_array();
      foreach ($orders as $key => $order) {
        $deldateraw = explode(' ', $order['delivery_date']);
        $orders[$key]['delivery_date'] = $deldateraw[0];
      }
      return $orders;
  }

  function get_order($order_id){
      $query = $this->db->query("SELECT * FROM `orders` WHERE id = ?", array($order_id));
      $order = $query->row_array();
      $deldateraw = explode(' ', $order['delivery_date']);
      $order['delivery_date'] = $deldateraw[0];

      $customer_id = $order['customer_id'];

      $items = $this->get_order_items($order_id, $customer_id);
      return array_merge($order,$items);
  }

  function cancel_distributor_order($distributor_order_id){
    // we need to do much more here. like send comms and cancel the order on the distributor side.
      $this->db->query("UPDATE `distributor_orders` SET status_id = 14 WHERE id = ?", array($distributor_order_id));
      //check if we have multiple distributors on this order.
      $query = $this->db->query("SELECT order_id FROM `distributor_orders` WHERE id = ?", array($distributor_order_id));
      $result = $query->row_array();
      $order_id = $result['order_id'];    

      //if there is only one dist_order cancel the main order also.
      $query = $this->db->query("SELECT order_id FROM `distributor_orders` WHERE id = ?", array($distributor_order_id));
      if($query->num_rows() == 1){
        $this->cancel_order($order_id);
      }

      return true;
  }

  function cancel_order($order_id){
    
      $query = $this->db->query("UPDATE `orders` SET status = 'Cancelled' WHERE id = ?", array($order_id));

      $data['status_id'] = '14';
      $distributor = $this->db->where("order_id", $order_id)->update("distributor_orders", $data);
  
      $customer = $this->customer_model->get_customer_from_order($order_id);
      $customer['order_id'] = $order_id;
      $this->refund_order($order_id);
      $user=$this->user_model->get_user_from_link_id($customer['id']);
      $data['order_id'] = $order_id;
      $data['company_name'] = $customer['company_name'];
      $data['message'] = $customer;
      $this->comms_library->queue_comm_group($user->id, 'order_cancelled', $data);

      return true;
  }

  function refund_order($order_id){
    $order = $this->get_order($order_id);
    $order_cost = $order['total_amount'];

    if($order['payment_type'] == 4){
      $this->financial_model->add_to_spazapp_customer_account('refund', $order['customer_id'], $order_cost, $order_id, 0, 'spazapp_refund_o-'.$order_id);
    }

    $spark_comm = $this->financial_model->calculate_instant_spark_commission($order_cost);
    $this->financial_model->remove_spark_order_comm($spark_comm, $order['customer_id'], $order_id);
  }

  function get_product_specials_list($customer_type, $region_id, $category_id)
  {
      $this->load->model('product_model');
      $products = $this->product_model->get_products_for_region($customer_type, $region_id, $category_id, TRUE);
      return $products;
  }

  function get_product_list($customer_type, $region_id, $category_id)
  {
      $this->load->model('product_model');
      $products = $this->product_model->get_products_for_region($customer_type, $region_id, $category_id);
      return $products;
  }

  function get_customer_types(){
    $query = $this->db->query("SELECT id, name FROM customer_types");
    return $query->result_array();
  }

  function get_product_supplier($product_id){
    $query = $this->db->query("SELECT supplier_id FROM products WHERE id = $product_id");
    if($query->num_rows() != 0){
      $return = $query->row_array();
      return $return['supplier_id'];
    }
    return 0;
  }

  function get_supplier_distributor($supplier_id){
    $query = $this->db->query("SELECT distributor_id FROM suppliers WHERE id = $supplier_id");
    if($query->num_rows() != 0){
      $return = $query->result_array();
      return $return[0]['distributor_id'];
    }
    return 0;
  }

  function find_distributor($supplier_id, $region_id){
    $sql = "SELECT ds.distributor_id 
      FROM dist_supplier_link ds, dist_region_link dr
      WHERE ds.distributor_id = dr.distributor_id AND
      ds.supplier_id = $supplier_id AND 
      dr.region_id = $region_id";

    $query = $this->db->query($sql);
    if($query->num_rows() != 0){
      $return = $query->row_array();
      return $return['distributor_id'];
    }
    return 0;
  }

  function find_cheapest_distributor($supplier_id, $region_id, $product_id, $quantity){
    $sql = "SELECT ds.distributor_id, p.shrink_price
      FROM dist_supplier_link ds, dist_region_link dr, prod_dist_price p
      WHERE ds.distributor_id = dr.distributor_id AND
      ds.distributor_id = p.distributor_id AND
      ds.supplier_id = $supplier_id AND 
      dr.region_id = $region_id
      ORDER BY p.shrink_price ASC LIMIT 1";

    $query = $this->db->query($sql);
    if($query->num_rows() != 0){
      $return = $query->row_array();
      $special = $this->order_model->getProductSpecialStatus($product_id, 8, $return['distributor_id'], $quantity);
      return $return['distributor_id'];
    }
    return 0;
  }

  function get_recent_raw_data(){
      $query =  $this->db->query("SELECT * FROM `app_raw_data` order by id desc LIMIT 30");
      return $query->result_array();
  }

  function get_user_images($user_id, $all=''){

      if($all != ''){

          $region_id = 1;
          $customer_type = 1;
          $user = $this->user_model->get_general_user($user_id);
          if(isset($user->customer_info)){
            $region_id = $user->customer_info['region_id'];
            $customer_type = $user->customer_info['customer_type'];
          }

          $product_images = $this->product_model->get_products_for_region($customer_type, $region_id, 0);
          
          $cat_img_q = "SELECT id, icon FROM `categories` WHERE icon != ''";

          $pos_img_q = "SELECT id, picture FROM `products` WHERE picture != '' and category_id = 46";

      }else{

          $user = $this->user_model->get_user($user_id);

          $prod_img_q = "SELECT id, picture FROM `products` WHERE picture != '' AND editdate >= '".$user->last_login."' OR createdate >= '".$user->last_login."'";
          $cat_img_q = "SELECT id, icon FROM `categories` WHERE icon != '' AND editdate >= '".$user->last_login."' OR createdate >= '".$user->last_login."'";
          $query =  $this->db->query($prod_img_q);
          $product_images = $query->result_array();

          $pos_img_q = "SELECT id, picture FROM `products` WHERE picture != '' and category_id = 46";
      }
      
      $images = array();

      foreach ($product_images as $key => $image) {
        $images[] = array(
          'id' => $image['id'],
          'type' => 'product',
          'filename' => $image['picture']
          );
      }
      
      $query2 =  $this->db->query($cat_img_q);
      $category_images = $query2->result_array();

      foreach ($category_images as $key => $image) {
        $images[] = array(
          'id' => $image['id'],
          'type' => 'category',
          'filename' => $image['icon']
          );
      }

      $query3 =  $this->db->query($pos_img_q);
      $pos_images = $query3->result_array();

      foreach ($pos_images as $key => $image) {
        $images[] = array(
          'id' => $image['id'],
          'type' => 'product',
          'filename' => $image['picture']
          );
      }

      return $images;
  }

  function get_category_list($parent_id, $customer_type, $customer_id){

     $this->load->model('customer_model');

     $onelevel = false;
     
     $region = $this->customer_model->get_customer_region($customer_id);
     $region_id = $region['region_id'];
 
     if($parent_id != 0 || $onelevel){

        $sql = "SELECT cat.*
                FROM `products` as p 
                JOIN suppliers as su ON su.id = p.supplier_id
                JOIN prod_dist_price pdp ON pdp.product_id = p.id
                JOIN categories cat ON cat.id = p.category_id AND cat.parent_id = $parent_id
                LEFT JOIN prod_customer_type_link pct ON pct.product_id = p.id
                WHERE 
                pdp.distributor_id IN (
                                        SELECT ds.distributor_id 
                                        FROM dist_supplier_link ds, dist_region_link dr
                                        WHERE ds.distributor_id = dr.distributor_id AND
                                        ds.supplier_id = su.id AND 
                                        dr.region_id = $region_id  group by ds.distributor_id
                                      )
                AND (pct.customer_type = $customer_type OR pct.customer_type IS NULL)
                AND pdp.out_of_stock = 0
                AND cat.name != 'POS' 
                GROUP BY cat.id
                ORDER BY cat.name ASC";

      $q_res = $this->db->query($sql);
      $categories = $q_res->result_array();

    }else{

        $sql = "SELECT cat.*
                FROM `products` as p 
                JOIN suppliers as su ON su.id = p.supplier_id
                JOIN prod_dist_price pdp ON pdp.product_id = p.id
                JOIN categories subcat ON subcat.id = p.category_id 
                JOIN categories cat ON cat.id = subcat.parent_id AND cat.parent_id = 0
                JOIN prod_customer_type_link pct ON pct.product_id = p.id
                WHERE 
                pdp.distributor_id IN (
                                        SELECT ds.distributor_id 
                                        FROM dist_supplier_link ds, dist_region_link dr
                                        WHERE ds.distributor_id = dr.distributor_id AND
                                        ds.supplier_id = su.id AND 
                                        dr.region_id = $region_id group by ds.distributor_id
                                      ) 
                AND (pct.customer_type = $customer_type)
                AND pdp.out_of_stock = 0
                AND cat.name != 'POS' 
                GROUP BY cat.id
                ORDER BY cat.name ASC";

            $q_res = $this->db->query($sql);
            $categories = $q_res->result_array();
    }

    return $categories;
 }

  function get_all_regions($parent_id=0){

      $parent = ' AND parent_id = '.$parent_id;
      if($parent_id == 0){
        $parent = '';
      }
      $query =  $this->db->query("SELECT * FROM `regions` WHERE parent_id != 0 AND id > 3 $parent order by name asc", array($parent_id));
      return $query->result_array();
  }

/*  function get_category_list($parent_id, $crumbs=array()) {
      $query = $this->db->query("SELECT * FROM  `categories` WHERE id = ?", array($parent_id));
      $row = $query->row_array();
      $crumbs[] = $row;
      print_r($crumbs)
      if($row['parent_id'] == 0 || $row['parent_id'] == '') {
          krsort($crumbs); //sort array in descending order
          return $crumbs;
      } else {
          $this->get_category_list($row['parent_id'], $crumbs);
      }
  }
*/

  function xml_parents($parent_array=''){

        if(is_array($parent_array) && isset($parent_array['parent'])){
          $query =  $this->db->query("SELECT * FROM `categories` WHERE id = ?", array($parent_id));
          $parent_array['parent'] = $this->get_parents($query->row_array());
        }else{
          return $parent_array['parent'];
        }
    }

  function get_specials_list($customer_type, $region_id){

      $this->load->model('product_model');
      $category_id = 0;
      $specials = $this->product_model->get_products_for_region($customer_type, $region_id, $category_id, true);
      return $specials;
  }

  function get_promotions($category='broadsheets'){
      $date = date("Y-m-d H:i:s");
      $query = $this->db->query("SELECT * FROM `promotions` WHERE category = '$category' AND valid_till > '$date'");
      return $query->result_array();
  }

  function get_promotion($promotion_id){
      $date = date("Y-m-d H:i:s");
      $query = $this->db->query("SELECT * FROM `promotions` WHERE id = $promotion_id AND valid_till > '$date'");
      return $query->row_array();
  }

	function get_events(){
      $date = date("Y-m-d H:i:s");
    	$query = $this->db->query("SELECT * FROM `events` WHERE event_date >= '$date'");
    	return $query->result_array();
	}

  function get_event($event_id){
      $query = $this->db->query("SELECT * FROM `events` WHERE id = $event_id");
      return $query->row_array();
  }

  function log_promotion_view($user_id, $promotion_id){
      $date = date("Y-m-d H:i:s");
      $query = $this->db->query("INSERT INTO `promotion_views` (user_id, promotion_id, createdate) VALUES (?,?,NOW())", array($user_id, $promotion_id));
      return $query;
  }

  function log_event_view($user_id, $event_id){
      $date = date("Y-m-d H:i:s");
      $query = $this->db->query("INSERT INTO `event_id` (user_id, event_id, createdate) VALUES (?,?,NOW())", array($user_id, $event_id));
      return $query;
  }

  function get_all_orders($limit=0,$count=20){

    $user_info = $this->aauth->get_user();
    $company_name = $this->user_model->get_supplier($user_info->user_link_id);
    $date_from = $this->session->userdata('dashboard_date_from');
    $date_to = $this->session->userdata('dashboard_date_to');

    $supplier_id = $user_info->user_link_id;
   
    if(!empty($supplier_id)){
      $where_supp = "  p.supplier_id = '$supplier_id' AND";
    }else{
       $where_supp ='';
    }

    $query_string = "SELECT 
    a.id as 'order_number',
    b.company_name as 'customer',
    c.name as 'payment_type', 
    a.status, 
    a.delivery_date, 
    a.createdate,
    a.delivery_type
    FROM 
    `orders` as a, 
    `order_items` as oi,
    `customers` as b, 
    `products` as p, 
    `payment_types` as c
    WHERE 
    a.customer_id = b.id AND
    a.id = oi.order_id AND 
    p.id = oi.product_id AND 
    
    a.createdate > '$date_from' AND 
    a.createdate < '$date_to' GROUP BY oi.order_id ORDER by 
    a.createdate  desc ";

    $query = $this->db->query($query_string);
    $return['orders']= $query->result_array();

    $return['query'] = $query_string;
    return $return;
  }


function get_all_supplier_orders($limit=0,$count=20){

    $user_info = $this->aauth->get_user();
    $company_name = $this->user_model->get_supplier($user_info->user_link_id);
    $date_from = $this->session->userdata('dashboard_date_from');
    $date_to = $this->session->userdata('dashboard_date_to');
    $supplier_id = $user_info->user_link_id;
   
    $query_string = "SELECT 
    a.id as 'order_number',b.company_name as 'customer',c.name as 'payment_type', 
    a.status, a.delivery_date, a.createdate,a.delivery_type
    FROM 
    `orders` as a, 
    `order_items` as oi,
    `customers` as b, 
    `products` as p, 
    `payment_types` as c
    WHERE 
    a.customer_id = b.id AND
    a.id = oi.order_id AND 
    p.id = oi.product_id AND 
    p.supplier_id = '$supplier_id' AND
    a.createdate > '$date_from' AND 
    a.createdate < '$date_to' GROUP BY oi.order_id ORDER by 
    a.createdate  desc ";

    $query = $this->db->query($query_string);
    $return['orders']= $query->result_array();

    $return['query'] = $query_string;
    return $return;
  }

  function get_order_items($order_id, $customer_id=0){
      $query = $this->db->query("SELECT * FROM `order_items` WHERE order_id = ?", array($order_id));
      $result = $query->result_array();

      $total_amount = 0;
      $total_products = count($result);
      $region = $this->customer_model->get_customer_region($customer_id);
      $region_id = $region['region_id'];
      $region_name = $region['name'];
      
      foreach ($result as $key => $item) {

        if($customer_id != 0){
          $supplier_id = $this->get_product_supplier($item['product_id']);
          if(!$supplier_id || $supplier_id == ''){
            die('prod has no supplier');
          }
          $distributor_id = $this->find_distributor($supplier_id, $region_id);
          $query2 = $this->db->query("SELECT p.*, dp.* FROM `products` p
                                      LEFT JOIN prod_dist_price dp  ON p.id = dp.product_id
                                      WHERE dp.distributor_id = ? AND p.id = ?", 
                                      array($distributor_id, $item['product_id']));
          $product = $query2->row_array();
          $product['sell_price'] = $product['shrink_price'];
          $result[$key]['product_detail'] = $product;
        }

        $total_amount = $total_amount + ($item['price']*$item['quantity']);

      }

      return array('total_amount' => $total_amount, 'items' => $result, 'total_products' => $total_products);
  }

  function get_basket_items($order_id){
      
      $query = $this->db->query("SELECT * FROM `basket_order_items` WHERE order_id = ?", array($order_id));
      $result = $query->result_array();

      $total_amount = 0;
      $total_products = count($result);

      foreach ($result as $key => $item) {
        $total_amount += ($item['price']*$item['quantity']);
        $query = $this->db->query("SELECT id, stock_code, barcode, name FROM `products` WHERE id = ?", array($item['product_id']));
        $result[$key]['product_detail'] = $query->row_array();
      }

      return array('total_amount' => $total_amount, 'items' => $result, 'total_products' => $total_products);
  }

    function send_welcome($email, $name, $username, $cellphone, $password,$otp=''){

      $this->load->model('comms_model');

        $data['name'] = $name;
        if($otp != ''){
          $data['otp'] = $otp;
        }
        $data['password'] = $password;
        $data['username'] = $username;
        $user = $this->user_model->get_user_from_username($username);
        
        $this->comms_library->queue_comm_group($user['id'], 'welcome', $data);//Queueing Comms 
    }  

    function resend_otp($username){

      $this->load->model('comms_model');
      $user = $this->user_model->get_user_from_username($username);
      
      if($user['otp'] != '' && $user['banned'] == 1){
        $data['otp'] = $user['otp'];
        $data['username'] = $username;
        $data['default_app'] = $user['default_app'];
        
        //get sms message and send
        $message = $this->comms_model->fetch_sms_message('resend_otp', $data);
        $this->comms_model->send_sms($username, $message);

        /* 
          Comms queuing still under test,
          that the reason send_sms is not removed
        */
       // $this->comms_library->queue_comm($user['id'], 22, $data);///Queuing comms

        return true;
      }
      return false;
    }

    function create_customer($data){
      
      if(!isset($data['trader_id'])){
        $data['trader_id'] = 0;
      }

      $prov_city = 'province';
      if(isset($data['country_id'])){
        $prov_city = 'city_id';
        $data['province'] = $data['country_id'];
      }

      if(!isset($data['country_id'])){
        $data['country_id'] = 197;
      }


      $this->db->query("INSERT INTO customers (first_name, last_name, company_name, cellphone, email, location_lat, location_long, customer_type, region_id, $prov_city, country_id, trader_id, createdate) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())", array($data['first_name'], $data['last_name'], $data['shop_name'], $data['cellphone'], $data['email'], $data['lat'], $data['long'], $data['shop_type'], $data['region_id'], $data['province'], $data['country_id'], $data['trader_id']));
      return $this->db->insert_id();
    }
    
    function create_trader($data){
      $this->db->query("INSERT INTO traders (first_name, last_name, cellphone, email, location_lat, location_long, region_id, province, createdate) "
              . "VALUES (?,?,?,?,?,?,?,?,NOW())", array($data['first_name'], $data['last_name'],$data['cellphone'], $data['email'], $data['lat'], $data['long'], $data['region_id'], $data['province']));
      return $this->db->insert_id();
    }


    function update_user($user_id, $data){
      if(is_array($data) && count($data) >= 1){
        $data = $this->strip_db_rejects('aauth_users', $data);
        if(is_array($data) && count($data) >= 1){
          $this->db->where('id', $user_id);
          $this->db->update('aauth_users', $data);
        }
      }
    }

    function update_customer($customer_id, $data){

      if(is_array($data) && count($data) >= 1){
        if(isset($data['shop_type'])){
          $data['customer_type'] = $data['shop_type'];
          unset($data['shop_type']);
        }
        $data = $this->strip_db_rejects('customers', $data);
        if(is_array($data) && count($data) >= 1){
          $this->db->where('id', $customer_id);
          $this->db->update('customers', $data);
        }
      }
    }

    function update_user_customer($new_user_id,$customer_id){
      $this->db->query("UPDATE aauth_users SET user_link_id = ? WHERE id = ?", array($customer_id,$new_user_id));
    }


    function update_user_email($new_user_id,$email){
      $this->db->query("UPDATE aauth_users SET email = ? WHERE id = ?", array($email,$new_user_id));
    }

    function update_user_app($new_user_id,$app){
      $this->db->query("UPDATE aauth_users SET default_app = ? WHERE id = ?", array($app,$new_user_id));
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

  // Get All Distributor Orders

  function get_all_distributor_orders(){

    $distributor = $this->aauth->get_user();
    $distributor_id = $distributor->distributor_id;

    $date_from = $this->session->userdata('dashboard_date_from');
    $date_to = $this->session->userdata('dashboard_date_to');

    $query_string = "SELECT 
    a.id as 'order_number',
    b.company_name as 'customer',
    do.distributor_id as 'distributor',
    c.name as 'payment_type', 
    a.status, 
    a.delivery_date, 
    a.createdate,
    a.delivery_type,
    sum(oi.price * oi.quantity) as total,
    sum(oi.quantity) as product_count
    FROM `orders` as a, `order_items` as `oi`,`customers` as b, `payment_types` as c,`distributor_orders` as do 
    WHERE a.customer_id = b.id 
    AND a.id = do.order_id 
    AND a.id = oi.order_id 
    AND do.distributor_id = '$distributor_id'
    AND a.createdate > '$date_from' 
    AND a.createdate < '$date_to' 
    GROUP BY oi.order_id ORDER by a.createdate  desc ";

    $query = $this->db->query($query_string);
    
    $return['query'] = $query_string;
    $return['orders'] = $query->result_array();
    return $return;
  }

  function get_insurance_id($identity_number){

      $query = $this->db->query("SELECT `policy_number` FROM `ins_m_applications` WHERE `id` ='$identity_number' or `passport_number` = '$identity_number'");
      return $result = $query->result();
      
   }
  function get_insurance_by_policy_number($policy_number){

      $query = $this->db->query("SELECT `policy_number` FROM `ins_m_applications` WHERE policy_number ='$policy_number'");
   
      return $result = $query->result();
   }
      
  function get_ins_dependent_by_policy_number($policy_number){
      
      $query = $this->db->query("SELECT `policy_number` FROM `ins_m_app_dependants` WHERE policy_number ='$policy_number'");
      return $result = $query->result();
   }

   // Edit Order Post
   function edit_order($order_id, $order_info)
   {
      $compare = $this->db->query("SELECT `id`, `product_id`, (`price` * `quantity`) AS `old_total` FROM `order_items` WHERE order_id = '$order_id'");
      $each_item = $compare->result_array();

      $old_tot = 0;
      foreach ($each_item as $key => $old) {
        $old_tot += $old['old_total'];
      }
      $old_total = round($old_tot, 2);

      $new_item = $order_info['items'];
      $new_tot = 0;
      foreach ($new_item as $key => $new) {
        $sum = $new['quantity'] * $new['price'];
        $new_tot += $sum;
      }
      $new_total = round($new_tot, 2);

      $amount = $old_total - $new_total;

      $orders = $this->get_order_details($order_id);

      foreach($new_item as $key2 => $newproduct) // $each_item as $key => $product
      {
        foreach($each_item as $key => $product) // $new_item as $key2 => $newproduct
        {
          if (in_array($product['product_id'], $newproduct, TRUE)) {
            $this->update_order_items($order_id, $product['product_id'], array(
                "quantity" => $newproduct['quantity'], 
                "price" => $newproduct['price']       
                ));
          }
          elseif (in_array($product['product_id'], $newproduct, FALSE))
          {
            $this->delete_order_items($order_id, $product['product_id']);
          }
          else
          {

          }
        }
      }

      if($orders->name = "Account")
      {
        $this->load->model('financial_model');

        $this->financial_model->update_customer_order(array(
            "customer_id" => $orders->customer_id, 
            "order_id" => $order_id, 
            "amount" => $amount,
            "reference" => "Balance of app updated order",
            "added_by" => "0",
            "createdate" => date("Y-m-d H:i:s")            
          )
        );
      }

      return array('status' => 'success', 'message' => $order_id);

   }

   function update_order_items($order_id, $product_id, $data)
   {
      $this->db->where("order_id", $order_id)->where("product_id", $product_id)->update("order_items", $data);
   }

   function delete_order_items($order_id, $product_id)
   {
      $this->db->where("order_id", $order_id)->where("product_id", $product_id)->delete("order_items");
   }

   function get_order_details($order_id)
   {
      $query = $this->db->select("o.customer_id, p.name")
                ->from("orders o")
                ->join("payment_types as p", "p.id = o.payment_type")
                ->where("o.id", $order_id)
                ->get();
      $result = $query->row();
      return $result;
   }

   //Purchase Eskom Fix
   function get_customer_by_cellphone($cell)
   {
      $query = $this->db->select("id")
                ->from("customers")
                ->where("cellphone", $cell)
                ->get();
      $result = $query->row();
      return $result;
   }

  function store_long_lat($user_id, $long, $lat){
    if($user_id != 0 && $long != 0 && $lat != 0){
      $insert['user_id'] = $user_id;
      $insert['long'] = $long;
      $insert['lat'] = $lat;
      $insert['createdate'] = date("Y-m-d H:i:s");
      $this->db->insert('location_log',$insert);
    }
  }

   function generateRandomNumber($length = 4) {
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

  function store_otp($data){
    $otp = $this->generateRandomNumber();
    $this->db->query("INSERT INTO otp_verification (cellphone, otp) VALUES(?,?)", array($data['cellphone'],$otp));
    return $otp;
  }

  function send_otp($data){
      $this->load->model('comms_model');
       $data['default_app'] = $this->app_settings['app_name'];
        $message = $this->comms_model->fetch_sms_message('resend_otp', $data);
        $this->comms_model->send_sms($data['cellphone'], $message);
  }

  function verify_otp($data){

      $query = $this->db->query("SELECT * FROM otp_verification WHERE cellphone=? and otp=?",array($data['cellphone'],$data['otp']));
      $result = $query->row_array();

      if($result['otp']==$data['otp'] && $result['cellphone']==$data['cellphone']){
        return true;
      }else{
        return false;
      }
  }

 function get_provinces(){
    $query = $this->db->query("SELECT id, name FROM provinces");
    return $query->result_array();
  }

 function get_regions_from_province($province){
    $query = $this->db->query("SELECT id, name FROM regions where province_id = $province");
    return $query->result_array();
  }

 function get_global_statuses(){
    $query = $this->db->query("SELECT id, name FROM gbl_statuses");
    return $query->result_array();
  }

  function get_distributor_from_region($region_id){
    $query = $this->db->query("SELECT d.id, d.company_name FROM distributors d, dist_region_link r WHERE d.id = r.distributor_id AND r.region_id = $region_id");
    return $query->result_array();
  }

}
