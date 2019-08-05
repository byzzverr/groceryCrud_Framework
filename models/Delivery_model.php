<?php

class Delivery_model extends CI_Model { 

public function __construct()
{
      parent::__construct();

        $this->load->model('spazapp_model');
   
 }

function get_delivery($user_id){

      $return = array();

      $date = date('Y-m-d 00:00:00');
      $query = $this->db->query("SELECT 
        `id` as `delivery_id`, total, weight, volume, `date`, date_closed, cashout_total
      FROM `deliveries`
      WHERE `date` = '$date' AND `driver` = $user_id");

      $result = $query->row_array();
      return $result;
    }

function get_deliveries($user_id){

      $return = array();

      $date = date('Y-m-d 00:00:00');
      $query = $this->db->query("SELECT 
        `c`.`company_name` as `store_name`,
        `d`.`id` as `delivery_id`,
        `c`.`address`,
        `c`.`location_lat`,
        `c`.`location_long`,
        `do`.`dist_order_id` as `distributor_order_id`  
      FROM `del_orders` as `do` 
      JOIN `deliveries` as `d` ON `d`.`id` = `do`.`delivery_id` 
      JOIN `distributor_orders` as `dis` ON `dis`.`id`=`do`.`dist_order_id` 
      JOIN `orders` as `o` ON `o`.`id` = `dis`.`order_id` 
      JOIN `customers` as `c` ON `c`.`id`=`o`.`customer_id` 
      WHERE `d`.`date` = '$date' AND `dis`.`status_id` != 9  AND  `d`.`driver` = $user_id");
      $delivery = $this->get_delivery($user_id);

      if($query->num_rows() == 0){
        if($delivery){
          $return['orders'] = array();
          $return['delivery'] = $delivery;
        }else{
          $return = array();
        }
      }else{  
          $result = $query->result_array();
          $return['orders'] = $result;
          $return['delivery'] = $delivery;
      }
        
     return $return;
}
function distributor_orders($distributor_id){
     
      $query = $this->db->query("SELECT `c`.`address`,`c`.`company_name` as `store_name`,`p`.`name`,`oi`.`price`,`oi`.`quantity` 
      FROM `distributor_orders` as `dis` 
      JOIN `distributors` as `distr` ON `dis`.`distributor_id` =`distr`.`id` 
      JOIN `orders` as `o` ON `o`.`id` = `dis`.`order_id` 
      JOIN `order_items` as `oi` ON `oi`.`distributor_order_id` =`o`.`id` 
      JOIN `customers` as `c` ON `c`.`id`=`o`.`customer_id`  
      JOIN `products` as `p` ON `p`.`id` =`oi`.`product_id` 
      WHERE `dis`.`distributor_id` ='$distributor_id' AND `dis`.`status_id` NOT IN(6)");
      $result = $query->result_array();

     return $result;
}
    
function delivery_reasons(){
  
        $query = $this->db->query("SELECT `id` as `reason_id`,`name` as `reason` FROM `del_reasons` WHERE 1");
        $result = $query->result_array();

        return $result;   
}

function confirm_main_order($distributor_order_id){
    
        $query = $this->db->query("SELECT order_id, status_id FROM  distributor_orders WHERE `id`='$distributor_order_id'");
        $orders = $query->result_array();
        $all_deliverd = true;
        foreach ($orders as $key => $value) {
          if($value['status_id'] != 9){
            $all_deliverd = false;
          }
        }

        if($all_deliverd){
          $this->db->query("UPDATE order SET status_id='5' WHERE `id`='".$orders[0]['order_id']."'");
        }


        return true;        
} 

  function confirm_delivery($delivery_id, $distributor_order_id, $data_to)
  {
      $data['status_id'] = "9";

      if(!empty($distributor_order_id))
      {
        $this->db->where("delivery_id", $delivery_id)->where("dist_order_id", $distributor_order_id)->update("del_orders", $data_to);
        $this->db->where("id", $distributor_order_id)->update("distributor_orders", $data);
        //move cash from holding to distro.
        $this->financial_model->distributor_order_delivered($distributor_order_id);
        $this->spazapp_model->comfirm_main_order($distributor_order_id);

        return "true";
      }
      else
      {
        return "false";
      }
  }

  function close_delivery_order($delivery_id, $distributor_order_id, $reason_id)
  {
      $data['status_id'] = "8";

      if($reason_id != "4")
      {
          $this->db->where("id", $distributor_order_id)->update("distributor_orders", $data);
          $query = $this->db->select("order_id")
                    ->from("distributor_orders")
                    ->where("id", $distributor_order_id)
                    ->get();
          $order = $query->row();
          $order_id = $order->order_id;
      }
      else
      { 
          $this->app_model->cancel_distributor_order($distributor_order_id);
      }
          return $distributor_order_id;
  }

  function close_delivery($delivery_id, $cashout_total)
  { 
      $date = date("Y-m-d H:i:s");
      $data = array("cashout_total" => $cashout_total,"date_closed" => $date);
      $this->db->where("id", $delivery_id)->update("deliveries", $data);
      return true;
  }

  function get_order_total($order_id)
  {
      $query = $this->db->select("id, round((price * quantity), 2) as total")
                ->from("order_items")
                ->where("order_id", $order_id)
                ->get();
      $result = $query->result_array();

      $total = 0;

      foreach ($result as $key => $value) {
        $total_amount = $total + $value['total'];
      }

      return $total_amount;

  }

function delivery_reasons_by_id($reason_id){
  
        $query = $this->db->query("SELECT `id` as `reason_id`,`name` as `reason` FROM `del_reasons` WHERE `id` = '$reason_id'");
        $result = $query->result_array();

        return $result;   
}
    
function delivery_cashout($delivery_id){
    
           //NB NB NB we need to fix this.


         $distributor_order_id='';
         $result = $this->get_delivery_by_id($delivery_id);
 
         /*foreach($result as $item){
                 $distributor_order_id = $item->distributor_order_id;
                 $this->db->query("UPDATE distributor_orders SET status_id='5' WHERE `id`='$distributor_order_id'");
         }*/
    
         if(!empty($distributor_order_id)){
           
            //$this->spazapp_model->complete_order($distributor_order_id);
            
            return "true"; 
        }

        return "true"; 
}


  function get_delivery_orders($delivery_id){
    $query = $this->db->query("SELECT dist_order_id FROM del_orders where delivery_id = $delivery_id");
    return $query->result_array();
  }


  function get_delivery_total($delivery_id){

    $total = 0;
    $orders = $this->get_delivery_orders($delivery_id);

    foreach ($orders as $key => $order) {
      $ord = $this->order_model->get_dis_order($order['dist_order_id']);
      if($ord['dist_order_status'] == 9){
        $total += $ord['items']['total_amount'];
      }
    }

    return $total;
  }

  function get_delivery_by_id($delivery_id){
       
         $query = $this->db->query("SELECT `g`.`name`,`do`.`dist_order_id` as `distributor_order_id`  
          FROM `deliveries` as `d` JOIN `del_orders` as `do` ON `d`.`id` = `do`.`delivery_id` 
          JOIN `orders` as `o` ON `o`.`id` = `do`.`dist_order_id` JOIN `customers` as `c` ON `c`.`id`=`o`.`customer_id` 
          JOIN `distributor_orders` as `dis` ON `dis`.`id`=`do`.`dist_order_id` 
          JOIN `gbl_statuses` as `g` ON  `g`.`id` =`dis`.`status_id` 
          WHERE `do`.`delivery_id` ='$delivery_id'");
          return $query->result();
  }  

  function nav_started($driver_user_id, $distributor_order_id){
    
    $this->load->model('customer_model');
    $this->load->model('event_model');
    $this->load->model('comms_model');
    $this->load->model('order_model');

    $order = $this->order_model->get_dis_order($distributor_order_id);

    $query = $this->db->query("UPDATE `del_orders` SET nav_started = NOW() WHERE `dist_order_id` = '$distributor_order_id'");

    $customer = $order['customer'];
    $data = array('order_id' => $order['order_id'], 'total' => $order['items']['total_amount']);
    $message = $this->comms_model->fetch_sms_message('driver_on_route', $data);

    
    $category = 'delivery';
    $action = 'nav_started';
    $label  = 'Driver is on his way to make the delivery.';
    $value  = $distributor_order_id;

    $this->event_model->track_event($category, $action, $label, $value, $driver_user_id);
    $this->order_model->add_order_comment($order['order_id'], $distributor_order_id,  $label, $driver_user_id);

    $this->comms_model->send_sms($customer['cellphone'], $message);

  }

  // Delivery Report
  public function getAllDeliveries()
  {
      $date_from = $this->session->userdata('dashboard_date_from');
      $date_to = $this->session->userdata('dashboard_date_to');

      $query = $this->db->select("d.id, d.total, d.date_closed, d.volume, d.weight, d.cashout_total, d.date, a.name, t.licence_plate, ds.company_name")
                  ->from("deliveries as d")
                  ->join("aauth_users as a", "a.id = d.driver")
                  ->join("del_trucks as t", "t.id = d.truck")
                  ->join("distributors as ds", "ds.id = d.distributor_id")
                  ->where("d.date >=", $date_from)
                  ->where("d.date <=", $date_to)
                  ->order_by("d.id DESC")
                  ->get();
      $result = $query->result_array();
      return $result;
  }

  public function getSingleDelivery($delivery_id)
  {
      $query = $this->db->select("d.distributor_id, d.total, d.weight, d.volume, d.date, d.date_closed, d.cashout_total, a.name, a.cellphone, t.licence_plate, ds.company_name")
                  ->from("deliveries as d")
                  ->join("aauth_users as a", "a.id = d.driver")
                  ->join("del_trucks as t", "t.id = d.truck")
                  ->join("distributors as ds", "ds.id = d.distributor_id")
                  ->where("d.id", $delivery_id)
                  ->get();
      $result = $query->row_array();
      return $result;
  }
 
  public function get_full_delivery_orders($delivery_id)
  {
    $query = $this->db->query("SELECT d.*, delo.*, o.customer_id, o.payment_type, o.order_type, o.status, o.delivery_type, do.*, c.*
      FROM del_orders delo
      JOIN deliveries d ON delo.delivery_id = d.id
      JOIN distributor_orders do ON delo.dist_order_id = do.id
      JOIN orders o ON do.order_id = o.id
      JOIN customers c ON o.customer_id = c.id
      WHERE delo.delivery_id = $delivery_id
      ORDER BY delo.delivery_date asc");
    return $query->result_array();
  }

  function get_driver_locations($user_id, $date){
    $sql = "SELECT * FROM location_log WHERE user_id = $user_id AND DATE(createdate) = '$date' ORDER BY createdate asc";
    $query = $this->db->query($sql);
    return $query->result_array();
  }

  public function deliveriesAll($delivery_id)
  {
      $query = $this->db->select("d.*, dl.*, do.*, gb.name as status_name")
                  ->from("del_orders as dl")
                  ->join("deliveries as d", "d.id = dl.delivery_id")
                  ->join("distributor_orders as do", "do.id = dl.dist_order_id")
                  ->join("gbl_statuses as gb", "gb.id = do.status_id")
                  ->where("dl.delivery_id", $delivery_id)
                  ->get();
      $result = $query->result_array();
      return $result; 
  }

function distributor_order_delivered($distributor_order_id){
     $data['status_id'] = "9";
     $this->db->where("id", $distributor_order_id)->update("distributor_orders", $data);
     //move cash from holding to distro.
     $this->financial_model->distributor_order_delivered($distributor_order_id);
     $this->spazapp_model->comfirm_main_order($distributor_order_id);

 }
}