<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Pos extends CI_Controller {

  function __construct()
  {
      parent::__construct();
	  $this->load->library("Aauth");
      $this->load->helper('url');
      $this->load->model("task_model");
	  $this->load->model("customer_model");
	  $this->load->model("photosnap_model");
        $this->load->model('event_model');
		$this->load->library('grocery_CRUD');
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
        
    function index(){

        try{
            $crud = new grocery_CRUD();
            
            $pos_cat_id = 46;
            
            $crud->set_table('bc_pos');
            $crud->set_subject('Pos');

            $this->session->set_userdata('table', 'bc_pos');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));

            $crud->set_relation('status','gbl_statuses','name');
            $crud->set_relation('prod_1','products','name', array('category_id' => $pos_cat_id));
            $crud->set_relation('prod_2','products','name', array('category_id' => $pos_cat_id));
            $crud->set_relation('prod_3','products','name', array('category_id' => $pos_cat_id));
            $crud->set_relation('prod_4','products','name', array('category_id' => $pos_cat_id));
            $crud->set_relation('prod_5','products','name', array('category_id' => $pos_cat_id));
            $crud->set_relation('photosnap_id','bc_photosnaps','name');
          
            $crud->columns('name','status','status_2','prod_1','prod_2','prod_3','prod_4','prod_5','photosnap_id','createdate');
            $crud->unset_delete();

            $crud->change_field_type('createdate','invisible');

            $crud->callback_column('status_2',array($this,'_callback_status_2'));
            $crud->callback_before_insert(array($this, 'set_createdate'));

            $crud->display_as('status_2','Active/Inactive');

            $output = $crud->render();

            $output->page_title = 'POS';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }

    function _callback_status_2($value,$row){
        $result = $this->task_model->getPosActiveTask($row->id);

        if($result==true){
            return "Active";
        }else{
            return "<p style='color:red'>Inactive</p>";
        }
    }

	function responses(){

        try{
            $crud = new grocery_CRUD();
			
			
            
            $crud->set_table('bc_photosnaps_responses');
            $crud->set_subject('PhotoSnap');

            $this->session->set_userdata('table', 'bc_photosnaps_responses');

            $crud->set_relation('status','gbl_statuses','name');
			$crud->set_relation('user_id','aauth_users','name');
			
            $crud->unset_delete();
            $crud->unset_add();
            $crud->unset_edit();

            $crud->callback_column('picture',array($this,'_callback_reponse_image'));
            $crud->callback_column('photosnap_id',array($this,'_callback_task_image'));

            $crud->change_field_type('createdate','invisible');

            $crud->callback_before_insert(array($this, 'set_createdate'));

            $output = $crud->render();

            $output->page_title = 'PhotoSnap';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }

    function _callback_add_image($value, $row){
        return '<a href="'.base_url().'assets/uploads/bc/photosnap/'.$value.'" target="_blank"><img src="'.base_url().'assets/uploads/bc/photosnap/'.$value.'" width="100" /></a>';
    }

    function _callback_reponse_image($value, $row){
        return '<a href="'.base_url().'assets/uploads/customer/bc/photosnaps/'.$value.'" target="_blank"><img src="'.base_url().'assets/uploads/customer/bc/photosnaps/'.$value.'" width="100" /></a>';
    }

    function _callback_task_image($value, $row){
        $value = $this->photosnap_model->get_task_image($value);
        return '<a href="'.base_url().'assets/uploads/bc/photosnaps/'.$value.'" target="_blank"><img src="'.base_url().'assets/uploads/bc/photosnaps/'.$value.'" width="100" /></a>';
    }

    function create_crop($uploader_response,$field_info, $files_to_upload)
    {

        $data = getimagesize('./assets/uploads/bc/photosnap/'.$uploader_response[0]->name);
        
        $data['file_name'] = $uploader_response[0]->name;

        //set width and height here

        $cropped_width = 250;
        $cropped_height = 250;

        //Get image full size.
        $image_width = $data[0];
        $image_height = $data[1];
        $crop_x = 0;
        $crop_y = 0;

        if($image_width > $image_height){
            $ratio_calc = $image_height/$image_width;
            $new_width = $cropped_height*$ratio_calc;
            $ratio = $image_height / $cropped_height;
            $final_height = $image_height;
            $final_width = ($image_height/$cropped_height)*$cropped_width;
            if($image_width > $cropped_width){
                $crop_x = ($image_width-($cropped_width*$ratio))/2;
            }
        }else{
            $ratio_calc = $image_width/$image_height;
            $new_height = $cropped_width*$ratio_calc;
            $ratio = $image_width/$cropped_width;
            $final_width = $image_width;
            $final_height = ($image_width/$cropped_width)*$cropped_height;
            if($image_height > $cropped_height){
                $crop_y = ($image_height-($cropped_height*$ratio))/2;
            }
        }

        //calculate the difference in size between the original and the resized small one.

        //multiply the small values by the ratio
        $crop['p_crop_x'] = $crop_x;
        $crop['p_crop_y'] = $crop_y;
        $crop['p_crop_w'] = $final_width;
        $crop['p_crop_h'] = $final_height;

        $targ_w = $cropped_width;
        $targ_h = $cropped_height;
        $jpeg_quality = 90;
        $src = './assets/uploads/bc/photosnap/'.$data['file_name'];

        $ext_explode = explode('.',$src);
        $ext = $ext_explode[count($ext_explode)-1];

        $src_new = str_replace('.'.$ext,'.jpg','./assets/uploads/bc/photosnap/'.$data['file_name']);

        // Determine Content Type
        switch ($ext) {
            case "gif":
                $img_r = imagecreatefromgif($src);
                break;
            case "png":
                $img_r = imagecreatefrompng($src);
                break;
            case "jpeg":
            case "jpg":
                $img_r = imagecreatefromjpeg($src);
                break;
            default:
                $img_r = imagecreatefromjpeg($src);

        }

        $dst_r = ImageCreateTrueColor( $targ_w, $targ_h );
        imagecopyresampled($dst_r,$img_r,0,0,$crop['p_crop_x'],$crop['p_crop_y'],
        $targ_w,$targ_h,$crop['p_crop_w'],$crop['p_crop_h']);
        imagejpeg($dst_r,$src_new,$jpeg_quality);
    }

    function set_createdate($post_array){

        $post_array['createdate'] = date("Y-m-d H:i:m");
        return $post_array;
    }



    }
