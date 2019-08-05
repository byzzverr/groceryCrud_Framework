
<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Agent extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('spazapp_model');
        $this->load->model('customer_model');
        $this->load->model('product_model');
        $this->load->model('insurance_model');
        $this->load->model('news_model');
       

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin())
        {
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Agent'))
        {
            $this->event_model->track('error','permissions', 'agent');
            redirect('/admin/permissions');
        } 
    }

function show_view($view, $data=''){
      $data['user_info'] = $this->user;
      $data['app_settings'] = $this->app_settings;
      $this->load->view($this->app_settings['app_folder'].'include/header', $data);
      $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), $data);
      $this->load->view($this->app_settings['app_folder'].$view, $data);
      $this->load->view($this->app_settings['app_folder'].'include/footer', $data);
    }

    function crud_view($output){
        
        $output->user_info = $this->user;
        $output->app_settings = $this->app_settings;
        $this->load->view('include/crud_header', (array)$output);
        $this->load->view($this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), (array)$output);
        $this->load->view('crud_view', (array)$output);
        $this->load->view('include/crud_footer', (array)$output);
    }


function sales(){
        
        $crud = new grocery_CRUD();
        
        $crud->set_table('ins_applications');
        
        $crud->set_subject('insurance');
        $user_id=$this->aauth->get_user()->id;
        $crud->where('sold_by',$user_id);

        $crud->set_relation('ins_prod_id','ins_products','name');
        
        $crud->columns('ins_prod_id','policy_number','premium','first_name','last_name','sa_id','picture','signature');
        
        $crud->display_as('id','SA ID Number');  
        
        $crud->callback_column('picture',array($this,'_callback_ins_applications_image'));
        $crud->callback_column('signature',array($this,'_callback_ins_applications_signature'));
        $crud->callback_column('dependent',array($this,'_callback_ins_dependent'));

        $crud->add_action('See Comms', '', '/insurapp/financial/application_comms','ui-icon-plus');
       
        $crud->unset_delete();
        $crud->unset_add();
        /*$crud->unset_edit();*/

        $this->session->set_userdata(array('table' => 'ins_m_funeral'));

        $crud->callback_after_insert(array($this, 'track_insert'));
        $crud->callback_after_update(array($this, 'track_update'));

        $output = $crud->render();

        $output->page_title = 'Spazapp Insurance Applications';

        $this->crud_view($output);
    } 

  function _callback_ins_applications_death_certificates($value, $row){
        return '<a href="'.base_url().'aassets/uploads/insurance/death_certificates/'.$value.'" class="image-thumbnail"><img src="'.base_url().'assets/uploads/insurance/pictures/'.$value.'" width="100" /></a>';
    }
    
    function _callback_ins_applications_image($value, $row){
        return '<a href="'.base_url().'assets/uploads/insurance/pictures/'.$value.'" class="image-thumbnail"><img src="'.base_url().'assets/uploads/insurance/pictures/'.$value.'" width="100" /></a>';
    }
    
    function _callback_ins_applications_signature($value, $row){
        return '<a href="'.base_url().'assets/uploads/insurance/signatures/'.$value.'" class="image-thumbnail"><img src="'.base_url().'assets/uploads/insurance/signatures/'.$value.'" width="100" /></a>';
    }
    function _callback_ins_product_info($value, $row){
        $type='';
        $product = $this->insurance_model->get_insurance_product_id($row->ins_prod_id);
      
        return '<a href="/management/funeral_product_info/'.$row->ins_prod_id.'/'.$row->policy_number.'">'.$product['type'].'</a>';
    }
    
    function _callback_ins_dependent($value, $row){
    
        return '<a href="/management/ins_dependent/'.$row->policy_number.'">Dependent</a>';
    }


    function track_insert($post_array,$primary_key){
        $catgory = 'insurance';
        $action = 'insert';
        $label = 'User added a new entry to the '.$this->session->userdata('table').' table';
        $value = $primary_key;
        $this->event_model->track_event($catgory, $action, $label, $value);

        if($this->session->userdata('table') == 'ins_products'){
            $this->db->insert('ins_product_split',array("product_id" => $product_id));
        }

        $this->session->unset_userdata(array('table'));
    }

    function track_update($post_array,$primary_key){
        $catgory = 'insurance';
        $action = 'update';
        $label = 'User updated an entry in the '.$this->session->userdata('table').' table';
        $value = $primary_key;
        $this->event_model->track_event($catgory, $action, $label, $value);
        $this->session->unset_userdata(array('table'));
    }

    function _callback_colour1($value, $row){
        return '<svg style="width:100%; height:100px">
                    <rect style="width:100%; height:100px;fill:#'.$row->colour1.';stroke-width:0;stroke:rgb(0,0,0)" />
                </svg>';
    }

    function _callback_colour2($value, $row){
      
        return '<svg style="width:100%; height:100px">
                    <rect style="width:100%; height:100px;fill:#'.$row->colour2.';stroke-width:0;stroke:rgb(0,0,0)" />
                </svg>';
    } 

    function _callback_colour3($value, $row){
        return '<svg style="width:100%; height:100px">
                    <rect style="width:100%; height:100px;fill:#'.$row->colour3.';stroke-width:0;stroke:rgb(0,0,0)" />
                </svg>';
    } 

    function _callback_colour4($value, $row){
         return '<svg style="width:100%; height:100px">
                    <rect style="width:100%; height:100px;fill:#'.$row->colour4.';stroke-width:0;stroke:rgb(0,0,0)" />
                </svg>';
    }

}

?>