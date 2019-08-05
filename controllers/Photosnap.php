<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Photosnap extends CI_Controller {

  function __construct()
  {
      parent::__construct();
	  $this->load->library("Aauth");
      $this->load->helper('url');
      $this->load->model("task_model");
	  $this->load->model("customer_model");
      $this->load->model("photosnap_model");
	  $this->load->model("survey_model");
        $this->load->model('event_model');
		$this->load->library('grocery_CRUD');
	  $this->user = $this->aauth->get_user();
	  //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
        if (!$this->aauth->is_allowed('Brand_connect')){
            $this->event_model->track('error','permissions', 'Brand_connect');
            redirect('/admin/permissions');
        } 
        $this->app_settings = get_app_settings(base_url());
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
            
            
            
            $crud->set_table('bc_photosnaps');
            $crud->set_subject('PhotoSnap');

            $this->session->set_userdata('table', 'bc_photosnaps');
            $crud->callback_after_insert(array($this, 'track_insert'));
            $crud->callback_after_update(array($this, 'track_update'));

            $crud->set_relation('status','gbl_statuses','name');

            $crud->set_field_upload('picture','assets/uploads/bc/photosnaps/');
            
            $crud->unset_delete();
            $crud->callback_column('picture',array($this,'_callback_add_image'));
            $crud->callback_after_upload(array($this,'create_crop'));
            $crud->add_action('Report', '', '/photosnap/photosnap_summary','ui-icon-flag');

            $crud->change_field_type('createdate','invisible');

            $crud->callback_before_insert(array($this, 'set_createdate'));

            $output = $crud->render();

            $output->page_title = 'PhotoSnap';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }

    function photosnap_summary($photosnap_id){
        $dataset=false;
        $data['photosnap_response'] = $this->photosnap_model->get_photosnap_responses($photosnap_id);
        foreach ($data['photosnap_response'] as $key => $r) {
        $dataset.=' dataSet.push(["'.$r['id'].'",
                "'.$r['user'].'",
                "'."<a href='/assets/uploads/customer/bc/photosnaps/".$r['picture']."'><img src='/assets/uploads/customer/bc/photosnaps/".$r['picture']."' width='100' /></a>".'",
                "'.$r['createdate'].'"]);';
        }

        $columns='{ title: "Id" },
        { title: "Participant" },
        { title: "Picture" },
        { title: "Createdate" }';

        $data['photosnap_id'] = $photosnap_id;
        $data['viewed_count'] = $this->photosnap_model->get_photosnap_responses($photosnap_id, 2);
        $data['pending_approval_count'] = $this->photosnap_model->get_photosnap_responses($photosnap_id, 3);
        $data['approved_count'] = $this->photosnap_model->get_photosnap_responses($photosnap_id, 8);
        $data['continue_count'] = $this->photosnap_model->get_photosnap_responses($photosnap_id, 11);
        $data['photosnap'] = $this->photosnap_model->get_photosnap($photosnap_id);
        $data['count_results'] = $this->photosnap_model->get_photosnap_responses($photosnap_id,'', 'count_results');
        $data['all'] = $this->survey_model->getAllUsers();
        $data['script'] = $this->data_table_script($dataset, $columns, 1, false);
        $data['page_title'] = "Photosnap Summary";
        
        $this->show_view('bc_photosnap_summary',$data);
    }

    function photosnaps_response($status_id, $photosnap_id){
        $dataset=false;
        $data['photosnap'] = $this->photosnap_model->get_photosnap($photosnap_id);
        $data['photosnap_response'] = $this->photosnap_model->get_photosnap_responses($photosnap_id, $status_id);
        foreach ($data['photosnap_response'] as $key => $r) {
        $dataset.=' dataSet.push(["'.$r['id'].'",
                "'.$r['user'].'",
                "'."<a href='/assets/uploads/customer/bc/photosnaps/".$r['picture']."'><img src='/assets/uploads/customer/bc/photosnaps/".$r['picture']."' width='100' /></a>".'",
                "'.$r['status'].'",
                "'.$r['createdate'].'"
                ]);';
        }

        $columns='{ title: "Id" },
        { title: "Participant" },
        { title: "Picture" },
        { title: "Status" },
        { title: "Createdate" }';

        $data['script'] = $this->data_table_script($dataset, $columns, 1, true);

        $data['page_title'] = "Photosnap Response";
        $this->show_view('bc_photosnaps_responses',$data);
    }

	function responses(){

        try{
            $crud = new grocery_CRUD();
			
			
            
            $crud->set_table('bc_photosnaps_responses');
            $crud->set_subject('PhotoSnap');

            $this->session->set_userdata('table', 'bc_photosnaps_responses');

            //$crud->set_relation('status','gbl_statuses','name');
            $crud->set_relation('user_id','aauth_users','name');
			$crud->set_relation('photosnap_id','bc_photosnaps','name');
			
            $crud->unset_delete();
            $crud->unset_add();
            $crud->unset_edit();

            $crud->callback_column('picture',array($this,'_callback_reponse_image'));
            $crud->callback_column('photosnap_image',array($this,'_callback_task_image'));
            $crud->callback_column('status',array($this,'_callback_status_id'));
            $crud->callback_column('bc_task_results_id',array($this,'_callback_task_id'));
            $crud->columns('user_id','photosnap_id','photosnap_image','picture','status','createdate');
            $crud->order_by('createdate','desc');
            $crud->change_field_type('createdate','invisible');

            $crud->callback_before_insert(array($this, 'set_createdate'));

            $output = $crud->render();

            $output->page_title = 'PhotoSnap';

            $this->crud_view($output);

        }catch(Exception $e){
            show_error($e->getMessage().' --- '.$e->getTraceAsString());
        }
    }
     function _callback_status_id($value, $row){
        $status = $this->task_model->get_task_status($row->user_id,$row->photosnap_id);
        return  $status['name'];
     }

     function _callback_task_id($value, $row){
        $task = $this->task_model->get_task_status($row->user_id,$row->photosnap_id);
        return  $task['id'];
     }

     function _status_callback($value, $row)
      {
          
          $status = $this->task_model->getStatusById($row->status);

          if ($row->status == 3 || $row->status == 11 ||  $row->status == 5 ||  $row->status == 8)
          {
            $user_id = $this->task_model->get_user_from_result_id($row->id);
            return '<a href="/task/update_task/'.$user_id.'/'.$row->task_id.'/'.$row->id.'">'.$status->name.'</a>';
          }
          else
          {
            return $status->name;
          }
      }

    function _callback_add_image($value, $row){
        return '<a href="'.base_url().'assets/uploads/bc/photosnaps/'.$value.'" target="_blank"><img src="'.base_url().'assets/uploads/bc/photosnaps/'.$value.'" width="100" /></a>';
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

    function data_table_script($dataset, $columns, $order_index=0, $show_buttons=true){
        if($show_buttons){
          $buttuns = ",dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]";
        }else{
          $buttuns="";
        }
        $datatable="var dataSet = [ ];
        ". $dataset."
        $(document).ready(function() {
            $('#report_table').DataTable( 
            {
                'order': [[".$order_index.",'desc' ]],
                data:dataSet,
                columns: [
                    ".$columns."
                ]
                $buttuns
            } 
            );
        } );";
        return $datatable;
    }



    }
