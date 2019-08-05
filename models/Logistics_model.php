<?php

class Logistics_model extends CI_Model {

   public function __construct()
   {
      $this->load->model('customer_model');
      parent::__construct();
   }

   function get_delivery_all_info($delivery_id){
      $query = $this->db->query("SELECT * FROM deliveries WHERE id = ? ", array($delivery_id));
      $delivery = $query->row_array();

      $delivery['orders'] = $this->get_all_orders_in_delivery($delivery_id);
    
      foreach ($delivery['orders'] as $key => $ord) {
         $delivery['orders'][$ord['id']] =  $this->order_model->get_all_order_info($ord['id']);
         unset($delivery['orders'][$key]);
      }

      return $delivery;

   }
function get_dis_delivery_all_info($delivery_id){
      $query = $this->db->query("SELECT * FROM deliveries WHERE id = ? ", array($delivery_id));
      $delivery = $query->row_array();

      $delivery['orders'] = $this->get_all_dis_orders_in_delivery($delivery_id);
    
      foreach ($delivery['orders'] as $key => $ord) {
         $delivery['orders'][$ord['id']] =  $this->order_model->get_all_order_info($ord['order_id']);
         unset($delivery['orders'][$key]);
      }

      return $delivery;

   }

   function update_order_deliveries($delivery_id, $date){

      $orders = $this->get_all_orders_in_delivery($delivery_id);
      $delivery = $this->get_delivery_info($delivery_id);

      if(isset($orders) && count($orders) > 0){
         foreach ($orders as $key => $order) {
            $this->update_order_delivery_date($order['id'], $delivery['date']);
            $this->update_customer_delivery($order, $delivery);
         }
      }
   }

   function update_order_delivery_date($order_id, $date){
      $this->db->query("UPDATE orders SET `delivery_date` = ?, `status` = 'Awaiting Delivery' WHERE id = ? ", array($date, $order_id));
   }

   function get_delivery_info($delivery_id){
      $query = $this->db->query("SELECT * FROM deliveries WHERE id = ? ", array($delivery_id));
      return $query->row_array();
   }

   function get_delivery_id($order_id){
      $query = $this->db->query("SELECT delivery_id FROM del_orders where order_id = ? ", array($order_id));
      $return = $query->row_array();
      return $return['delivery_id'];
   }

   function update_customer_delivery($order, $delivery){
      $query = $this->db->query("SELECT * FROM customers WHERE id = ? ", array($order['customer_id']));
      $customer = $query->row_array();

      $to = $customer['cellphone'];
      /*$to = '0827378714';*/
      $message = 'SPAZAPP: Order '.$order['id'].' has been set for delivery on '.$delivery['date'].' by driver: '.$delivery['driver'].'. Delivery Ref: '.$delivery['id'];
      $this->comms_model->send_sms($to, $message);

      $to = $customer['email'];
      $data['template'] = 'delivery_note';
      $data['message']['delivery'] = $delivery;
      $data['message']['customer'] = $customer;
      $data['subject'] = 'Your Order is on its way';
      $this->comms_model->send_email($to, $data);

   }

   function get_all_orders_in_delivery($delivery_id){
      $query = $this->db->query("SELECT ord.* FROM orders as ord, del_orders as del where del.delivery_id = ? and del.dist_order_id = ord.id", array($delivery_id));
      return $query->result_array();
   }
    
function get_all_dis_orders_in_delivery($delivery_id){
    
   $query = $this->db->query("SELECT ord.* FROM distributor_orders as ord, del_orders as del where del.delivery_id = ? and del.dist_order_id = ord.id", array($delivery_id));
return $query->result_array();
 
   }

   //Auto populate price in add order items

   function get_product_price($field_product_id) //, $order_id
   {
      $query = $this->db->select("shrink_price as 'price'")
                  ->from("prod_dist_price")
                  ->where("product_id =", $field_product_id)
                  ->get();
      $result = $query->row();
      return $result;

   }

}
