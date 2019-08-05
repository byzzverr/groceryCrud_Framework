<?php

class Spazapp_model extends CI_Model {

   public function __construct()
   {
      $this->load->model('customer_model');
      $this->load->model('order_model');
      $this->load->library('push_notifications');
      $this->load->library('comms_library');
      $this->load->model('comms_model');
      parent::__construct();
   }

   public function get_order_item_count($order_id){
 
      $query = $this->db->query("SELECT * FROM `order_items`  WHERE  order_id = ?", array($order_id));
      $result = $query->num_rows();

     
      return $result;
   } 
  public function get_del_order_item_count($order_id,$dist_order_id){
 
      $query = $this->db->query("SELECT * FROM `order_items`  WHERE distributor_order_id ='$dist_order_id' AND order_id = ?", array($order_id));
      $result = $query->num_rows();

     
      return $result;
   } 


   function comfirm_main_order($distributor_order_id){
    //confirm main order if ALL of the order is delivered.
    return true;
   }
  
   function fetch_50_stores(){

      $query = $this->db->query("SELECT * FROM cashvan_stores_original WHERE user_id = 0 LIMIT 50");
      $result = $query->result_array();
      return $result;
   }

  function mark_store_as_done($store_id, $user_id){

      $query = $this->db->query("UPDATE cashvan_stores_original SET user_id = $user_id WHERE id = $store_id");
   }

   public function get_all_customers(){
    
      return $this->customer_model->get_all_customers();
   }   

   public function get_customer($customer_id){
    
      return $this->customer_model->get_customer($customer_id);
   }

   public function get_reps_customers($rep_user_id){
    
      return $this->customer_model->get_reps_customers($rep_user_id);
   }

   public function get_parent_group_from_child_id($user_id){
    
      $query = $this->db->query("SELECT b.* FROM aauth_users a, aauth_users b, aauth_groups c WHERE a.id = ? AND a.default_usergroup = c.id AND c.parent_id = b.default_usergroup", array($user_id));
      $result = $query->result_array();

      return $result;
   }

   function get_user($id){
      $query = $this->db->query("SELECT * FROM aauth_users WHERE id = ?",array($id));
      return $query->row_array();
   }

   public function get_order_total($order_id){
    
     $query = $this->db->query("SELECT * FROM `order_items` WHERE order_id = ?", array($order_id));
      $result = $query->result_array();

      $total_amount = 0;
      $total_products = count($result);
      
      foreach ($result as $key => $item) {

        $total_amount = $total_amount + ($item['price']*$item['quantity']);

      }

      return $total_amount;
   }  
    
 public function get_dist_order_total($order_id){
  
     $query = $this->db->query("SELECT * FROM `order_items` WHERE distributor_order_id = ?", array($order_id));
      $result = $query->result_array();

      $total_amount = 0;
      $total_products = count($result);
      
      foreach ($result as $key => $item) {

        $total_amount = $total_amount + ($item['price']*$item['quantity']);

      }

      return $total_amount;
   } 
    
public function get_del_order_total($order_id){
    
     $query = $this->db->query("SELECT * FROM `order_items` WHERE order_id = ?", array($order_id));
      $result = $query->result_array();

      $total_amount = 0;
      $total_products = count($result);
      
      foreach ($result as $key => $item) {

        $total_amount = ($item['price']*$item['quantity']);

      }

      return $total_amount;
} 
    
public function get_order_total_($order_id){
    
     $query = $this->db->query("SELECT * FROM `orders`  u JOIN `distributor_orders` r ON u.id=r.order_id JOIN `order_items` p ON u.id=p.order_id  WHERE r.order_id = ?", array($order_id));
      
      $result = $query->result_array();

      $total_amount = 0;
      $total_products = count($result);
      
      foreach ($result as $key => $item) {

        $total_amount = $total_amount + ($item['price']*$item['quantity']);

      }

      return $total_amount;
   }

  function get_order_items($order_id){
      $query = $this->db->query("SELECT * FROM `order_items` WHERE order_id = ?", array($order_id));
      $result = $query->result_array();

      $total_amount = 0;
      $total_products = count($result);
      
      foreach ($result as $key => $item) {

        $total_amount = $total_amount + ($item['price']*$item['quantity']);

      }

      return array('total_amount' => $total_amount, 'items' => $result, 'total_products' => $total_products);
  }
function get_order_customer_id($order_id){
    $customer_id='';
      $query = $this->db->query("SELECT * FROM `orders` u INNER JOIN `customers` p ON u.customer_id=p.id JOIN `distributor_orders` r ON r.order_id=u.id  WHERE  r.order_id = ?", array($order_id));
      $result = $query->result_array();

     foreach ($result as $key => $item) {
         $customer_id = $item['company_name'];
         
      }

      return $customer_id;
  }
function _callback_del_order_customer_id($order_id){
    
    $customer_id='';
      $query = $this->db->query("SELECT * FROM `orders` u INNER JOIN `customers` p ON u.customer_id=p.id JOIN `distributor_orders` r ON r.order_id=u.id JOIN `gbl_statuses` g ON r.status_id=g.id  WHERE   r.order_id = ?", array($order_id));
      $result = $query->result_array();

     foreach ($result as $key => $item) {
         $customer_id = $item['company_name'];
         
      }

      return $customer_id;
  }
function get_order_del_deliverydate($order_id){
    $customer_id='';
      $query = $this->db->query("SELECT * FROM `orders` u INNER JOIN `customers` p ON u.customer_id=p.id JOIN `distributor_orders` r ON r.order_id=u.id JOIN `gbl_statuses` g ON r.status_id=g.id  WHERE  g.id='9' AND  r.order_id = ?", array($order_id));
      $result = $query->result_array();

     foreach ($result as $key => $item) {
         $customer_id = $item['delivery_date'];
         
      }

      return $customer_id;
  }
  
  
function get_order_deliverydate($order_id,$status_id){
      $delivery_date ='';
      $query = $this->db->query("SELECT * FROM `orders`
       u INNER JOIN `distributor_orders` p ON u.id=p.order_id  WHERE p.status_id='$status_id' and p.order_id = ?", array($order_id));
      $result = $query->result_array();

         foreach ($result as $key => $item) {
             $customer_id = $item['customer_id'];
             $delivery_date = $item['delivery_date'];
          }

      return $delivery_date;
  }
function get_order_createdate($order_id){
    $create_date ='';
      $query = $this->db->query("SELECT * FROM `orders`  u INNER JOIN `distributor_orders` p ON u.id=p.order_id  WHERE p.order_id = ?", array($order_id));
      $result = $query->result_array();

     foreach ($result as $key => $item) {
         $customer_id = $item['customer_id'];
         $create_date = $item['createdate'];
      }

      return $create_date;
  }
function get_order_status($order_id, $status_id){
      $status="";
      $query = $this->db->query("SELECT * FROM `distributor_orders` u INNER JOIN `gbl_statuses` p ON u.status_id=p.id  WHERE  u.status_id =  ?", array($status_id));
      $result = $query->result_array();

     foreach ($result as $key => $item) {
         $status = $item['name'];
      }

      return $status;
  }
    

function get_order_del_status($order_id,$distributor_id){
   
      $status="";
      $query = $this->db->query("SELECT * FROM `gbl_statuses` u INNER JOIN `distributor_orders` p ON p.status_id=u.id WHERE  p.status_id='9' and p.order_id = ?", array($order_id));
      $result = $query->result_array();

     foreach ($result as $key => $item) {
         //$customer_id = $item['customer_id'];
          $status = $item['name'];
      }

      return $status;
  }

public function get_order_items_quantity($order_id){
    
      $query = $this->db->query("SELECT * FROM order_items u JOIN `distributor_orders` r ON u.distributor_order_id=r.id  WHERE r.status_id='9' and u.order_id = ?", array($order_id));
      $result = $query->num_rows();
      return $result;
   }
public function get_order_items_($order_id, $dist_order_id){
    
      $query = $this->db->query("SELECT * 
      FROM order_items 
      WHERE distributor_order_id  = ?", array($dist_order_id));
      $result = $query->num_rows();
      return $result;
   }
public function get_distributor_order_items($order_id, $dist_order_id){
    
      $query = $this->db->query("SELECT * 
      FROM order_items 
      WHERE order_id  = ?", array($dist_order_id));
      $result = $query->num_rows();
      return $result;
   }
public function get_distributor_order_items2($distributor_order_id, $distributor_id){
    
      $query = $this->db->query("SELECT * FROM order_items i WHERE i.distributor_order_id = ?", array($distributor_order_id));
      $result = $query->num_rows();
      return $result;
   }
    
public function get_del_order_items_quantity($order_id){
    
      $query = $this->db->query("SELECT * FROM order_items u JOIN `distributor_orders` r ON u.order_id=r.id JOIN `distributor_orders` n ON n.order_id=u.id  WHERE n.status_id='9' and u.order_id = ?", array($order_id));
      $result = $query->num_rows();
      return $result;
} 
    
function get_order_payment_type($order_id){
    $payment_type ='';
   $query = $this->db->query("SELECT * FROM `orders` u  JOIN `distributor_orders` p ON u.id=p.order_id  JOIN `payment_types` r ON u.payment_type=r.id WHERE p.order_id = ?", array($order_id));
     $result = $query->result_array();
   
    
     foreach ($result as $key => $item) {
         $payment_type = $item['name'];
       
      }

      return $payment_type;
  }
function get_order_payment_type_($order_id){
    $payment_type='';
   $query = $this->db->query("SELECT * FROM `orders` u  JOIN `payment_types` r ON u.payment_type=r.id JOIN `distributor_orders` n ON n.order_id=u.id  WHERE n.status_id='9' and u.id = ?", array($order_id));
     $result = $query->result_array();
   
    
     foreach ($result as $key => $item) {
         $payment_type = $item['name'];
       
      }

      return $payment_type;
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
      
      $query = $this->db->query("SELECT count(a.id) as orders, b.company_name 
      FROM orders a, customers b 
      WHERE a.customer_id = b.id AND a.createdate > '$date_from' 
      AND a.createdate < '$date_to' group by b.company_name LIMIT 20");

      return $query->result_array();
   } 

    

   function get_dist_strores_orders($distributor_id){

      $date_from = $this->session->userdata('dashboard_date_from');
      $date_to = $this->session->userdata('dashboard_date_to');

      $sql = "SELECT count(DISTINCT o.id) as orders, 
              c.company_name 
              FROM orders o, 
              customers c, 
              order_items oi, 
              distributor_orders do
              WHERE o.customer_id = c.id 
              AND do.order_id = o.id 
              AND do.distributor_id='$distributor_id'
              AND o.createdate > '$date_from' 
              AND o.createdate < '$date_to' 
              group by c.id ORDER BY c.createdate DESC LIMIT 20";

      $query = $this->db->query($sql);

      return $query->result_array();
   }   
    function get_supplier_strores_orders($supplier_id){

      $date_from = $this->session->userdata('dashboard_date_from');
      $date_to = $this->session->userdata('dashboard_date_to');

      $query = $this->db->query("SELECT count(a.id) as orders, b.company_name FROM orders a, order_items o, products p, customers b 
        WHERE p.id=o.product_id AND p.supplier_id = '$supplier_id' 
        AND a.customer_id = b.id AND a.createdate > '$date_from' 
        AND a.createdate < '$date_to' group by b.company_name");

      return $query->result_array();
   }

   function get_products_orders(){

      $date_from = $this->session->userdata('dashboard_date_from');
      $date_to = $this->session->userdata('dashboard_date_to');

      $query = $this->db->query("SELECT 
                               `p`.`name`,
                               `p`.`id`,
                               SUM(`oi`.`quantity`) as 'quantity'
                               FROM 
                               `orders` as `o`,
                               `order_items` as `oi`,
                               `products` as `p`
                               WHERE 
                               `o`.`id` = `oi`.`order_id` AND
                               `p`.`id` = `oi`.`product_id` AND 
                               `o`.`createdate` > '$date_from' AND 
                               `o`.`createdate` < '$date_to'
                               GROUP BY
                               `p`.`id`
                               LIMIT 20");

      return $query->result_array();
   }



  function get_supplier_products_orders($supplier_id){

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
         o.createdate < '$date_to' AND 
         p.supplier_id ='$supplier_id'
         GROUP BY
         p.id
         LIMIT 20
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

   public function approve_order($order_id){
    
      $this->db->query("UPDATE `orders` SET status = 'Approved' WHERE id = '$order_id'");

      $query = $this->db->query("SELECT c.*, u.id as 'user_id' FROM customers c, orders o, aauth_users u WHERE u.user_link_id = c.id AND u.default_usergroup in (8,1) AND c.id = o.customer_id AND o.id = '$order_id'");
      $customer = $query->row_array();
     
      $message = $this->comms_model->fetch_sms_message('order_approved', array("order_id" => $order_id));
      
      // $this->comms_model->send_sms($customer['cellphone'], $message);
      // $this->comms_model->send_email($customer['email'], array('template' => 'order_approved', 'subject' => 'SPAZAPP: Order Approved', 'message' => $customer));
     
      $user = $this->aauth->get_user($customer['user_id']);
      $this->comms_model->send_push_notification($user->pushtoken,$message,'Order Approved');
      
      //Comms data to be queued
      $data["order_id"] = $order_id;
      $data['company_name'] = $customer['company_name'];
      $data['customer'] = $customer;

      /*
      Queuing comms still under test,
      that the reason I didn't comment-out the send_email and send_sms functions
      */
      $this->comms_library->queue_comm_group($customer['user_id'], 'order_approved', $data);//Queuing the comms
   } 

    public function confirm_delivery($delivery_id,$distributor_order_id){
    
      $query = $this->db->query("SELECT a.* FROM customers a, orders b, del_orders d , distributor_orders do WHERE a.id = b.customer_id AND d.delivery_id = '$delivery_id'");
      $customer = $query->row_array();
      
      $data["order_id"] = $order_id;
      $data['company_name'] = $customer['company_name'];
      $data['customer'] = $customer;
      $this->comms_library->queue_comm($customer['user_id'], 3, $data);//Queuing the comms
      
      //$message = $this->comms_model->fetch_sms_message('order_delivered', array("order_id" => $distributor_order_id));
      // $this->comms_model->send_sms($customer['cellphone'], $message);
      // $this->comms_model->send_email($customer['email'], array('template' => 'order_delivered', 'subject' => 'SPAZAPP: Order Delivered', 'message' => $customer));

     
      /*
      Queuing comms still under test,
      that the reason I didn't comment-out the send_email and send_sms functions
      */
      
   } 
    
/*  public function cancel_distributor_order($distributor_order_id){
    
      $query = $this->db->query("SELECT a.* FROM customers a, orders b, del_orders d , distributor_orders do WHERE a.id = b.customer_id AND do.id = '$distributor_order_id'");
      $customer = $query->row_array();
     
      $message = $this->comms_model->fetch_sms_message('order_cancelled', array("order_id" => $distributor_order_id));
     
      $this->comms_model->send_sms($customer['cellphone'], $message);
      $this->comms_model->send_email($customer['email'], array('template' => 'order_cancelled', 'subject' => 'SPAZAPP: Order cancelled', 'message' => $customer));
   } */
    
/*public function complete_order($distributor_order_id){
    
      $query = $this->db->query("SELECT d.* FROM distributors d, orders b, del_orders del, distributor_orders do WHERE d.id = do.distributor_id AND do.distributor_id = '$distributor_order_id'");
      $customer = $query->row_array();
     
      $message = $this->comms_model->fetch_sms_message('order_complete', array("order_id" => $distributor_order_id));
     
      $this->comms_model->send_sms($customer['cellphone'], $message);
      $this->comms_model->send_email($customer['email'], array('template' => 'order_complete', 'subject' => 'SPAZAPP: Order completed', 'message' => $customer));
   } */
    
public function approve_distributor_order($id, $order_id){
    
      $this->db->query("UPDATE `distributor_orders` SET status_id = '8' WHERE id = '$id'");
      $query = $this->db->query("SELECT a.* FROM customers a, orders b WHERE a.id = b.customer_id AND b.id = '$order_id'");
        
      $customer = $query->row_array();

      $message = $this->comms_model->fetch_sms_message('order_approved', array("order_id" => $order_id));
      $this->comms_model->send_sms($customer['cellphone'], $message);
      $this->comms_model->send_email($customer['email'], array('template' => 'order_approved', 'subject' => 'SPAZAPP: Order Approved', 'message' => $customer));
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
   
      $query = $this->db->query("SELECT delivery_id FROM del_orders WHERE dist_order_id = ? ", array($order_id));
      $return = $query->row_array();
      return $return['delivery_id'];
   } 
function get_delivery_id_($order_id_){
      $query = $this->db->query("SELECT delivery_id FROM del_orders WHERE dist_order_id = ? ", array($order_id_));
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
      $query = $this->db->query("SELECT ord.* FROM orders as ord, del_orders as del where del.delivery_id = ? and del.order_id = ord.id", array($delivery_id));
      return $query->result_array();
   }

   function get_suppliers_per_distributor($distributor_id){
      $query = $this->db->query("SELECT supplier_id FROM dist_supplier_link WHERE distributor_id = '$distributor_id'");
      return $query->result_array();
   }

   function send_order_comms($order_id){

        $data = array();

        $data['order_info'] = $this->order_model->get_order_info($order_id);
//        $this->load->view('emails/sale_order_placed', $data);
        // $type = $data['order_info']['order_type'] . '_order_placed';
        
        // $sub_data = array('order_id' => $data['order_info']['id']);

        // $subject = $this->comms_model->fetch_email_subject($type, $sub_data);
        // $this->comms_model->send_email($data['order_info']['customer']['email'], array('template' => $type, 'subject' => $subject, 'message' => $data));

        $sub_data = array('order_id' => $data['order_info']['id'], 'delivery_type' => $data['order_info']['delivery_type'], 'total' => $data['order_info']['items']['total_amount'],'masterpass_code' => $data['order_info']['masterpass_code']);
      
        $user = $this->user_model->get_user_from_link_id($data['order_info']['customer']['id']);
      
        $this->comms_library->queue_comm($user->id, 1, $data);//Queuing the comm
        $this->comms_library->queue_comm($user->id, 37, $sub_data );//Queuing the comm

        return $data['order_info'];

    }


   function send_distributor_order_comms($dis_order_id,$distributor_id){

          $data = array();

          $data['order_info'] = $this->order_model->get_dis_order_info($dis_order_id);
          $user = $this->user_model->get_user_from_link_id($data['order_info']['customer']['id']);

          $distributor =  $this->get_distributor($distributor_id);

          $type = 'distributor_order';
          $sub_data = array('order_id' => 'SUPPS365_'.$dis_order_id);

          $subject = $this->comms_model->fetch_email_subject($type, $sub_data);

          // $this->comms_model->send_email($distributor['email'], array('template' => $type, 'subject' => $subject, 'message' => $data));

          /*
          Queuing comms still under test,
          that the reason I didn't comment-out the send_email function 
          */

          $this->comms_library->queue_comm($distributor_id, 39, $data, 'distributor');//Queuing the comms
       
    }


     function get_distributor($distributor_id){
       $query = $this->db->query("SELECT * FROM distributors WHERE id ='$distributor_id'");
       return $query->row_array();
     }

     function get_distributor_object($distributor_id){
       $query = $this->db->query("SELECT * FROM distributors WHERE id ='$distributor_id'");
       return $query->row();
     }

     function get_all_distributors(){

       $query = $this->db->query("SELECT * FROM distributors ");
       return $query->result_array();
     }

    function place_distributor_orders($order_id){

        $data = array();
        $orders = $this->order_model->get_distributor_orders($order_id);

        foreach ($orders as $key => $order_info) {

            if(count($order_info['items']['items']) >= 1){

              $this->send_distributor_order_comms($order_info['distributor_order_id'],$order_info['distributor_id']);

            }
        }       
    }

    function base64_to_jpeg($base64_string, $output_file) {
        $ifp = fopen($output_file, "wb"); 

        fwrite($ifp, base64_decode($base64_string)); 
        fclose($ifp); 

        return $output_file; 
    }

 function get_order_field($order_id){
     
    $query = $this->db->query("SELECT * FROM `distributor_orders` u  JOIN `orders` p ON u.order_id=p.id JOIN `customers` c ON p.customer_id = c.id JOIN `payment_types` r ON p.payment_type=r.id WHERE u.id = ?", array($order_id));
     
      return $query->row_array();
 }
function get_order_status_id($order_id){
     
    $query = $this->db->query("SELECT * FROM `distributor_orders`  u  JOIN `gbl_statuses` p ON u.status_id=p.id  WHERE  u.id = ?", array($order_id));
     
     return $query->row_array();
 }
function get_order_all_status_id(){
     
    $query = $this->db->query("SELECT * FROM `gbl_statuses` WHERE 1");
     
      return $query->result();
 }
function get_order_all_payment_type(){
     
    $query = $this->db->query("SELECT * FROM `payment_types` WHERE 1");
     
      return $query->result();
 }
function get_order_field_(){
     
    $query = $this->db->query("SELECT * FROM `customers` WHERE 1");
     
      return $query->result();
 }
function get_dis_order_delivery_driver($order_id){
  $value ='';   
    $query = $this->db->query("SELECT * FROM `distributor_orders` u  JOIN `del_orders` r ON u.order_id=r.order_id JOIN `deliveries` d ON d.id=r.delivery_id  WHERE u.order_id = ?", array($order_id));
    $result = $query->result_array();
     foreach ($result as $key => $item) {
         $value = $item['driver'];
         
      }

      return $value;
 }
function get_dis_order_delivery_truck($order_id){
   $value ='';  
    $query = $this->db->query("SELECT * FROM `distributor_orders` u  JOIN `del_orders` r ON u.order_id=r.order_id JOIN `deliveries` d ON d.id=r.delivery_id  WHERE u.order_id = ?", array($order_id));
    $result = $query->result_array();
     foreach ($result as $key => $item) {
         $value = $item['truck'];
         
      }

      return $value;
 }
function get_dis_order_delivery_date($order_id){
    $value =''; 
    $query = $this->db->query("SELECT * FROM `distributor_orders` u  JOIN `del_orders` r ON u.order_id=r.order_id JOIN `deliveries` d ON d.id=r.delivery_id  WHERE u.order_id = ?", array($order_id));
    $result = $query->result_array();
     foreach ($result as $key => $item) {
         $value = $item['driver'];
         
      }

      return $value;
 }
function get_order_id($order_id){
    $value =''; 
    $query = $this->db->query("SELECT * FROM `distributor_orders` WHERE order_id = ?", array($order_id));
    $result = $query->result_array();
     foreach ($result as $key => $item) {
         $value = $item['order_id'];
         
      }

      return $value;
 }
function get_del_order_id($delivery_id){
    $value =''; 
    $query = $this->db->query("SELECT * FROM `del_orders` WHERE delivery_id = ?", array($delivery_id));
    $result = $query->result_array();
     foreach ($result as $key => $item) {
         $value = $item['dist_order_id'];
         
      }

      return $value;
 }
function get_prodct_price($product_id){
    $value =''; 
    $query = $this->db->query("SELECT price FROM order_items o JOIN products p ON o.product_id=p.id WHERE p.id='$product_id'");
   $result = $query->result_array();
     foreach ($result as $key => $item) {
         $value = $item['price'];
         
      }

      return $value;
 }
function get_order_distributor_id($dist_id){
    $value =''; 
    $query = $this->db->query("SELECT * FROM distributors WHERE id= ?", array($dist_id));
   $result = $query->result_array();
     foreach ($result as $key => $item) {
         $value = $item['company_name'];
         
      }

      return $value;
 }
public function get_dis_order_item_count($order_id){
    
      $query = $this->db->query("SELECT * FROM order_items WHERE order_id = '$order_id'");
      $result = $query->num_rows();
      return $result;
   }
    
public function get_prodct_name(){
      $query = $this->db->query("SELECT * FROM order_items o JOIN products p ON o.product_id=p.id WHERE 1 GROUP BY `name` ASC");
      return $query->result();
   }
    
public function get_all_regions(){
      $query = $this->db->query("SELECT * FROM regions WHERE 1 GROUP BY `name` ASC");
      return $query->result();
   }
    
public function get_prodct_name_($product_id){
      $query = $this->db->query("SELECT * FROM order_items o JOIN products p ON o.product_id=p.id WHERE product_id ='$product_id'");
       return $query->result();
   }
 function get_order_del_field($order_id){
     
    $query = $this->db->query("SELECT * FROM `distributor_orders` u  JOIN `del_orders` r ON u.order_id=r.order_id JOIN `deliveries` d ON d.id=r.delivery_id  WHERE u.order_id = ?", array($order_id));
     
      return $query->result();
 }  
function get_del_order_id_field(){
     
    $query = $this->db->query("SELECT * FROM `distributor_orders` where status_id = '8'");
     
      return $query->result();
 }   
 function update_save_orders_fields($order_id, $data){
     
$customer_id     = $data['customer_id'];
$payment_type    = $data['payment_type'];
$delivery_type   = $data['delivery_type'];
$delivery_date   = $data['delivery_date'];
$createdate      = $data['createdate'];
$order_type      = $data['order_type'];
$status_id       = $data['status_id'];
$order_id_       = $data['order_id'];
     
$this->db->query("UPDATE orders SET customer_id='$customer_id', payment_type = '$payment_type', order_type = '$order_type', delivery_type = '$delivery_type' , delivery_date = '$delivery_date' , createdate ='$createdate' where id = '$order_id_'");

        
    }
function insert_save_orders_fields($order_id, $data){
     
$customer_id     = $data['customer_id'];
$payment_type    = $data['payment_type'];
$delivery_type   = $data['delivery_type'];
$delivery_date   = $data['delivery_date'];
$createdate      = $data['createdate'];
$order_type      = $data['order_type'];
$order_id_       = $data['order_id'];
     
$this->db->query("INSERT INTO `orders` (`customer_id`, `payment_type`, `order_type`, `delivery_type` , `delivery_date`, `createdate`) VALUES('$customer_id','$payment_type','$order_type','$delivery_type' ,'$delivery_date' ,'$createdate')");

        
}
function update_save_del_orders_fields($data){
     
$order_id_      = $data['order_id'];
$driver         = $data['driver'];
$truck          = $data['truck'];
$delivery_date  = $data['date'];

$this->db->query("INSERT INTO `deliveries` (`driver`, `truck`, `date`) VALUES('$driver','$truck','$delivery_date')");
    

        
    }
    
   // All Distributor Orders Report

    function get_distributor_strores_orders(){

      $distributor = $this->aauth->get_user();
      $distributor_id = $distributor->distributor_id;

      $date_from = $this->session->userdata('dashboard_date_from');
      $date_to = $this->session->userdata('dashboard_date_to');

      $query = $this->db->query("SELECT count(a.id) as orders, b.company_name, do.distributor_id FROM orders a, customers b, distributor_orders do WHERE a.customer_id = b.id AND a.id = do.order_id AND do.distributor_id = '$distributor_id' AND a.createdate > '$date_from' AND a.createdate < '$date_to' group by b.company_name");

      return $query->result_array();
    }

    function get_distributor_products_orders(){

      $distributor = $this->aauth->get_user();
      $distributor_id = $distributor->distributor_id;

      $date_from = $this->session->userdata('dashboard_date_from');
      $date_to = $this->session->userdata('dashboard_date_to');

      $query = $this->db->query("SELECT 
         p.name,
         p.id,
         do.distributor_id,
         SUM(oi.quantity) as 'quantity'
         FROM 
         orders o,
         order_items oi,
         products p,
         distributor_orders do
         WHERE 
         o.id = oi.order_id AND
         p.id = oi.product_id AND
         o.id = do.order_id AND 
         do.distributor_id = '$distributor_id' AND 
         o.createdate > '$date_from' AND 
         o.createdate < '$date_to'
         GROUP BY
         p.id
         ");

      return $query->result_array();
   }
    function update_locations($primarykey, $post){
        $address = $post['name']." "."South Africa";
		$formattedAddr = str_replace(' ','+',$address);
        //Sending request and receive json data by address

        $geocodeFromAddr=file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddr.'&sensor=false');
        $output = json_decode($geocodeFromAddr);

        //Get latitude and longitute from json data

        $data['latitude']  = $output->results[0]->geometry->location->lat;
        $data['longitude'] = $output->results[0]->geometry->location->lng;
        
       $this->db->query("UPDATE regions SET location_lat='". $data['latitude']."',location_long='".$data['longitude']."' WHERE id ='$primarykey'");
    }
    
    
    
     function get_location_updater(){
        $query_str = "SELECT `c`.`address`, `r`.`name`, `c`.`id` FROM `regions` as `r` JOIN `customers` as `c` ON `r`.`id`=`c`.`region_id` ORDER BY `r`.`id` DESC";

        $query = $this->db->query($query_str);
        $result = $query->result_array(); 

        $comma ='';
        $on='';
        $add ='';
        $suburb='';
        foreach($result as $row){

            $add = $row['address']." ";

            $id = $row['id'];
            $address = $row['name']." ".$add."South Africa";

            $formattedAddr = str_replace(' ','+',$address);

            //Sending request and receive json data by address

            $geocodeFromAddr=file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddr.'&sensor=false');
            $output = json_decode($geocodeFromAddr);

            //Get latitude and longitute from json data

            $data['latitude']  = $output->results[0]->geometry->location->lat;
            $data['longitude'] = $output->results[0]->geometry->location->lng;

            $query_str = $this->db->query("UPDATE customers SET location_lat='". $data['latitude']."',location_long='".$data['longitude']."' WHERE location_lat ='0' AND id ='".$id."'");

            print_r($query_str);

        }             
     }
     
     public function getImeiList()
     {
        $query = $this->db->select("imei")
                  ->from("aauth_user_imei")
                  ->where("imei != '' AND model = 0")
                  ->get();
        $result = $query->result_array();
        return $result;
     }

     public function updateEMEI($imei, $data)
     {
        $this->db->where("imei", $imei)->update("aauth_user_imei", $data); 
     }
    
    function get_dist_del_order($order_id){
      
          $query = $this->db->query("SELECT c.company_name as customer_id, pt.name as payment_type 
          FROM orders as o
          JOIN payment_types as pt ON pt.id = o.payment_type 
          JOIN customers as c ON c.id = o.customer_id 
          WHERE o.id =  ?", array($order_id));
   
     return $query->result();

    
    }
    function get_dist_del_order_status($order_id){
        
          $query = $this->db->query("SELECT *
          FROM distributor_orders as o
          JOIN gbl_statuses as g ON g.id = o.status_id 
          WHERE o.id =  ?", array($order_id));
   
     return $query->result();

    
    }
  
    
    function get_dist_del_orders_by_distributor_id($distributor_id){
        
          $query = $this->db->query("SELECT *
          FROM  distributor_orders 
          WHERE status_id = '9' AND distributor_id = '$distributor_id'");

     return $query->result();

    
    }
    
    function get_del_orders_by_status($status_id){
        
          $query = $this->db->query("SELECT *
          FROM  distributor_orders 
          WHERE status_id = '$status_id'");

     return $query->result();

    
    }
    function get_dist_products_orders($distributor_id){

      $date_from = $this->session->userdata('dashboard_date_from');
      $date_to = $this->session->userdata('dashboard_date_to');
      $sql = "SELECT 
         `p`.`name`,
         `p`.`id`,
         SUM(`oi`.`quantity`) as 'quantity'
         FROM 
         `orders` as `o`,
         `order_items` as `oi`,
         `distributor_orders` as `do`,
         `products` as `p`
         WHERE 
         `o`.`id` = `oi`.`order_id` AND
         `p`.`id` = `oi`.`product_id` AND 
         `oi`.`order_id` = `do`.`order_id` AND 
         `do`.`distributor_id` = '$distributor_id' AND 
         `o`.`createdate` > '$date_from' AND 
         `o`.`createdate` < '$date_to'
         GROUP BY
         `p`.`id`
         ORDER BY o.createdate DESC LIMIT 20
         ";

      $query = $this->db->query($sql);

      return $query->result_array();
   }
    
   public function get_del_order_item($order_id){
    
      $query = $this->db->query("SELECT * FROM `order_items`  WHERE  distributor_order_id = ?", array($order_id));
      $result = $query->num_rows();

     
      return $result;
   } 
   public function get_del_dist_order_total($order_id){
    
      $query = $this->db->query("SELECT  sum(price * quantity) as total FROM `order_items`  WHERE  distributor_order_id = ?", array($order_id));
      $result = $query->result();

     
      return $result;
   }   
    
    function get_del_order($order_id){
      
          $query = $this->db->query("SELECT c.company_name as customer_id, pt.name as payment_type 
          FROM orders as o
          JOIN payment_types as pt ON pt.id = o.payment_type 
          JOIN customers as c ON c.id = o.customer_id 
          WHERE o.id =  ?", array($order_id));
   
     return $query->result();

    
    }
    
    function get_region_by_province($province_id){
        $query = $this->db->query("SELECT * FROM regions WHERE province_id = '$province_id' ORDER BY name ASC");
   
        return $query->result();
    }

    function getDistributorById($distributor_id){
       $query = $this->db->query("SELECT * FROM distributors WHERE id IN($distributor_id)");
       return $query->result_array();
     }

    public function approve_order_test($order_id){
    
      $this->db->query("UPDATE `orders` SET status = 'Approved' WHERE id = '$order_id'");
      $query = $this->db->query("SELECT a.* FROM customers a, orders b WHERE a.id = b.customer_id AND b.id = '$order_id'");
      $customer = $query->row_array();

      $message = $this->comms_model->fetch_sms_message('order_approved', array("order_id" => $order_id));

      $comms=$this->push_notifications->get_comms("order_approved");

      $this->push_notification($comms['id']);

      
   } 

    function get_province(){
      $query = $this->db->query("SELECT * FROM provinces WHERE 1");
       return $query->result_array();
    }

    function get_province_by_id($province_id){
      $query = $this->db->query("SELECT * FROM provinces WHERE id='$province_id'");
       return $query->row_array();
    }

    function get_brands(){
      $query = $this->db->query("SELECT * FROM brands WHERE 1");
       return $query->result_array();
    }

    function get_brands_by_id($brand_id){
      $query = $this->db->query("SELECT * FROM brands WHERE id='$brand_id'");
       return $query->row_array();
    }

    function get_region($region_id){
      $query = $this->db->query("SELECT * FROM regions WHERE id='$region_id'");
       return $query->row_array();
    }

    function get_region_parent($parent_id){
      $query = $this->db->query("SELECT * FROM regions WHERE parent_id='$parent_id'");
       return $query->row_array();
    }

    function get_status($status_id){
     
    $query = $this->db->query("SELECT * FROM `gbl_statuses` WHERE id='$status_id'");
     
      return $query->row_array();
   }

   function get_customer_order_total($customer_id){

    $distributor = $this->aauth->get_user();          
    $distributor_id = $distributor->user_link_id;

    $query = $this->db->query("SELECT *
                              FROM `distributor_orders` as do 
                              JOIN `orders` as o ON o.id=do.order_id
                              WHERE o.customer_id='$customer_id' and do.distributor_id='$distributor_id' GROUP BY do.order_id");
    $data=$query->result_array();
    $data['total']=$query->num_rows();
    
    return $data;

   }

   function get_customer_location($customer_id){
    $query = $this->db->query("SELECT a.id as user_id,c.id, c.*, p.name as province, r.name as region
                              FROM customers as c 
                              LEFT join regions as r ON c.region_id=r.id
                              LEFT join provinces as p ON c.province=p.id 
                              LEFT join aauth_users as a ON a.user_link_id=c.id 
                              WHERE c.id='$customer_id'");
    return $query->result_array();
   }

  function get_logged_in_distributor_id(){
       $user_id = $this->session->userdata('id');
       $query = $this->db->query("SELECT user_link_id FROM aauth_users WHERE id='$user_id'");
       return $query->row_array();
  }

function get_dist_order_total_by_date($distributor_id, $createdate){

     $query = $this->db->query("SELECT * FROM `order_items` as oi, orders as o, distributor_orders as do 
                              WHERE o.id=oi.order_id 
                              and do.id = oi.distributor_order_id 
                              and SUBSTR(o.createdate,1,10) = '$createdate' 
                              and distributor_id = '$distributor_id'");
      $result = $query->result_array();

      $total_amount = 0;
      $total_products = count($result);
      
      foreach ($result as $key => $item) {

        $total_amount = $total_amount + ($item['price']*$item['quantity']);

      }

      return $total_amount;
} 

 function get_order_total_by_date($createdate){
   
     $query = $this->db->query("SELECT * FROM `order_items` as oi, orders as o
                              WHERE o.id=oi.order_id 
                              and SUBSTR(o.createdate,1,10) = '$createdate'");
      $result = $query->result_array();

      $total_amount = 0;
      $total_products = count($result);
      
      foreach ($result as $key => $item) {

        $total_amount = $total_amount + ($item['price']*$item['quantity']);

      }

      return $total_amount;
   } 

  function get_comm($id){
    $query  = $this->db->query("SELECT * FROM comms WHERE `id`=?",array($id));
    return $query->row_array();
  }

  function get_regions(){
    $query = $this->db->query("SELECT * FROM regions WHERE 1");
     return $query->result_array();
  }

   
}
