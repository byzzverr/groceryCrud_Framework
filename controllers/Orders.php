<?php
class Orders extends CI_Controller {

  public function __construct()
  {
      parent::__construct();
	  $this->load->library("Aauth");
       $this->load->library('pagination');
      $this->load->helper('url');
      $this->load->model("survey_model");
      $this->load->model("order_model");
	  $this->load->library('grocery_CRUD');
	  
        $this->load->model('event_model');
	  $this->user = $this->aauth->get_user();
	  //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Survey')){
            $this->event_model->track('error','permissions', 'Survey');
            redirect('/admin/permissions');
        } 
  }
  //This is for grocery crud to do a join after a set relation 
  //http://www.grocerycrud.com/forums/topic/779-problem-with-where-and-set-relation/
  protected function _unique_join_name($field_name)
    {
	 return 'j'.substr(md5($field_name),0,8); 
    }
  function show_view($view, $data=''){
      $data['user_info'] = $this->user;
      $data['app_settings'] = $this->app_settings;
      $this->load->view($this->app_settings['app_folder'].'include/header', $data);
      $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), $data);
      $this->load->view($this->app_settings['app_folder'].$view, $data);
      $this->load->view($this->app_settings['app_folder'].'include/footer', $data);
    }
    
/*
    Sale Report Main Page
*/
public function sales_report()
    
{
	$data['page_title'] = 'Sales - Report';
    $data['suppliers_sql']= $this->order_model->all_suppliers();
    $data['order_query']= $this->order_model->get_sales_report();
	$this->show_view('sales_report_view', $data);
}

public function sales_report_details($supp_id)
{
         
    $order_date = $this->input->post('order_date');
 
    $data['order_query_']= $this->order_model->get_sales_report_($supp_id,$order_date);
    $data['order_query']= $this->order_model->get_sales_report_by_supplier_id($supp_id);
    $data['links'] = $this->pagination->create_links();
	$this->load->view('sales_report_details_view', $data);
}  

public function sales_report_details_()
    
{
    echo 'Date :'.$order_date = $this->input->post('order_date');
    $supp_id = $this->input->post('supplier_id');
   
	$data['page_title'] = 'Sales - Report';
    $data['order_query_']= $this->order_model->get_sales_report_($supp_id,$order_date);
    $data['order_query']= $this->order_model->get_sales_report_by_supplier_id_($supp_id,$order_date);
	$this->load->view('sales_report_details_view_', $data);
}


public function sales_report_details_per_product($supplier_id,$product_id)
    
{
    $supplier_id = $this->uri->segment(3);
    $product_id = $this->uri->segment(4);
    $data['page_title'] = 'Sales - Report Per Product';
    $data['order_query_']= $this->order_model->get_sales_report_by_product_id($supplier_id,$product_id);
	$this->load->view('sales_report_details_view_per_product', $data);
     
}
/*
    ---------------End of Sales Report functions------------------
*/
    }
?>