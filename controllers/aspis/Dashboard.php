<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->model("app_model");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('survey_model');
        $this->load->model('spazapp_model');
        $this->load->model('financial_model');
        $this->load->model('order_model');
        $this->load->model('airtime_model');
        $this->load->model('user_model');
        $this->load->model('customer_model');
        $this->load->model('news_model');
        $this->load->model('delivery_model');
        $this->load->model('insurance_model');
        $this->load->model('fridge_model');
       
        $this->load->library('pagination');

        $this->user = $this->aauth->get_user();

        //redirect if not logged in
        if (!$this->aauth->is_loggedin()){
            redirect('/cocacola/login');
        }         

        //redirect if no permissions for this
        //this needs to match the name in the permissions section.
/*        if (!$this->aauth->is_allowed('Dashboard')){
            $this->event_model->track('error','permissions', 'Dashboard');
            redirect('/admin/permissions');
        }*/


        if(isset($_POST['date_from']) || isset($_POST['date_to'])){

            $date_from = $_POST['date_from'];
            $date_to = $_POST['date_to'];

        }elseif($this->session->userdata('dashboard_date_from') && $this->session->userdata('dashboard_date_from') != ''){            

            $date_from = $this->session->userdata('dashboard_date_from');
            $date_to = $this->session->userdata('dashboard_date_to');

        }else{

            $date_minus1week = date("Y-m-d H:m", strtotime('-1 week', time()));
            $date_from = $date_minus1week;
            $date_to = date("Y-m-d H:i");
        }

        $this->session->set_userdata('dashboard_date_from', $date_from);
        $this->session->set_userdata('dashboard_date_to', $date_to);
        
    }


    function show_view($view, $data=''){
      $data['user_info'] = $this->user;
      $data['app_settings'] = $this->app_settings;
      $this->load->view( $this->app_settings['app_folder'].'include/header', $data);
      $this->load->view( $this->app_settings['app_folder'].'include/nav/'. get_defult_page($this->user), $data);
      $this->load->view( $this->app_settings['app_folder'].$view, $data);
      $this->load->view( $this->app_settings['app_folder'].'include/footer', $data);
    }

    function _example_output($output = null)
    {

        $this->load->model('checklist_model');
        $output->checklist_nav = $this->checklist_model->get_navigation();
        $this->load->view('include/header', $output);
        $this->load->view('include/nav/'. get_defult_page($this->user));
        $this->load->view('report_table',$output);
        $this->load->view('include/footer', $output);
    }

    
 public function fridge_locations()

    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        $this->load->library('googlemaps');
        $data['page_title'] = 'Fridge Locations';
        $data['fidges'] =  $this->fridge_model->get_fridges_locations();
        $config['center'] = '-29.8590, 31.0189';
        $config['zoom'] = '1';
        $this->googlemaps->initialize($config);

        foreach ( $data['fidges'] as $fridge) 
        {
            //get status
            $max_temp = $fridge['expected_temp']+$fridge['tolerance'];
            $min_temp = $fridge['expected_temp']-$fridge['tolerance'];
            $off_temp = $fridge['considered_off'];
            $status['icon'] = 'brr_map_icon.png';
            $status['message'] = 'Optimal';

            if($fridge['temp'] > $max_temp){
                $status['icon'] = 'brr_warning_map_icon.png';
                $status['message'] = 'Too Hot';
            }

            if($fridge['temp'] < $min_temp){
                $status['icon'] = 'brr_warning_map_icon.png';
                $status['message'] = 'Too Cold';
            }

            if($fridge['temp'] > $off_temp){
                $status['icon'] = 'brr_off_map_icon.png';
                $status['message'] = 'Fridge is Off';
            }

            $marker = array();
            $marker['position'] = $fridge['long']. ', '.$fridge['lat'];
            $marker['draggable'] = true;
            $marker['infowindow_content'] = '<strong>'.$fridge['brand']. ' - '. $fridge['fridge_unit_code'] .'</strong>&nbsp; <a href="/cocacola/dashboard/fridge_street_view/'.$fridge['id'].'"><i class="fa fa-male">View Street</i></a>'.
            '<br />Status: '.$status['message'].
            '<br />Location: '.$fridge['location_name'].
            '<br />Temp: '.$fridge['temp'].'&deg;'.
            '<br />Expected Temp Range: '.$max_temp.'&deg;-'.$min_temp.'&deg;'.
            '<br />Street: '.$fridge['street'].
            '<br />Region: '.$fridge['region'].
            '<br />Province: '.$fridge['province'];
            $marker['animation'] = 'DROP';
            $marker['icon'] = '/assets/img/' . $status['icon'];
            $this->googlemaps->add_marker($marker);
        }
        

        $data['map'] = $this->googlemaps->create_map();


        $this->show_view('fridge_locations', $data);
    }



      public function fridge_log_report(){

        $province_id=$this->input->post('province');
        $brand_id=$this->input->post('brand');
        $fridge_type_id=$this->input->post('fridge_type');
        $data['unit_code']=$this->input->post('fridge_uinit_code');

       
        $province = $this->spazapp_model->get_province();
        $brand = $this->spazapp_model->get_brands();
        $fridge_type = $this->fridge_model->get_fridge_type();

        $province_option='';
        foreach ($province as $row) {
            $province_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $brand_option='';
        foreach ($brand as $row) {
            $brand_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $brand_option='';
        foreach ($brand as $row) {
            $brand_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $fridge_type_option='';
        foreach ($fridge_type as $row) {
            $fridge_type_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $data['province_option']=$province_option;
        $data['brand_option']=$brand_option;
        $data['fridge_type_option']=$fridge_type_option;

        $fridges = $this->fridge_model->get_fridges($province_id,$brand_id,$fridge_type_id,$data['unit_code']);

        $result=$fridges['result'];
        $data['query']=$fridges['query'];

 
        $dataset ='';

        foreach ($result as $fr) {
         $dataset.='dataSet.push([
         "'."<a href='/cocacola/dashboard/fridge_details/".$fr['id']."'><font color='#f89520'>".$fr['fridge_unit_code']."</font></a>".'",
         "'.$fr['fridge_type'].'",
         "'.$fr['location_name'].'",
         "'.$fr['temp'].'",
         "'.$fr['province'].'",
         "'.$fr['region'].'",
         "'.$fr['street'].'",
         "'.$fr['createdate'].'",
         "'."<li class='dropdown'><a href='#' class='dropdown-toggle' data-toggle='dropdown'>Actions <b class='caret'></b></a><ul class='dropdown-menu' role='menu'><li><a href='/cocacola/dashboard/fridge_locations_history/".$fr['id']."'>Locations History</a></li><li><a href='/cocacola/dashboard/daily_monthly_temperature/".$fr['id']."'>Daily Temperatures</a></li></ul></li></ul>".'"]);';
        }

     
        $data['script'] ="var dataSet = [ ];
        ". $dataset."
        $(document).ready(function() {
            $('#report_table').DataTable( {
                data:dataSet,
                columns: [
                    { title: 'fridge Unit Code' },
                    { title: 'Fridge type' },
                    { title: 'Location Name' },
                    { title: 'Temperature' },
                    { title: 'Province' },
                    { title: 'Region' },
                    { title: 'Street' },
                    { title: 'Createdate' },
                    { title: 'Actions' }
                ]
            } );
        } );

      ";

        $data['page_title']="Fridge Report";

        $data['province'] = $this->spazapp_model->get_province_by_id($province_id);
        $data['brand'] = $this->spazapp_model->get_brands_by_id($brand_id);
        $data['fridge_type'] = $this->fridge_model->get_fridge_type_by_id($fridge_type_id);

        $this->show_view('frigde_log_report', $data); 

    }


    public function fridge_details($fridge_id)

    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        
        $fridge=  $this->fridge_model->get_current_location($fridge_id);
        $data['fridge']=$fridge;

        $this->load->library('googlemaps');
        //$config['center'] = '-29.8590, 31.0189';
        $config['center'] = $fridge['long']. ', '.$fridge['lat'];
        $config['zoom'] = '8';
        $this->googlemaps->initialize($config);

        //get status
        $max_temp = $fridge['expected_temp']+$fridge['tolerance'];
        $min_temp = $fridge['expected_temp']-$fridge['tolerance'];
        $off_temp = $fridge['considered_off'];

        $status['icon'] = 'brr_map_icon.png';
        $status['message'] = 'Optimal';
     
        if($fridge['temp'] > $max_temp){
            $status['icon'] = 'brr_warning_map_icon.png';
            $status['message'] = 'Too Hot';
        }

        if($fridge['temp'] < $min_temp){
            $status['icon'] = 'brr_warning_map_icon.png';
            $status['message'] = 'Too Cold';
        }

        if($fridge['temp'] > $off_temp){
            $status['icon'] = 'brr_off_map_icon.png';
            $status['message'] = 'Fridge is Off';
        }

        $data['status']=$status['message'];

        $marker = array();
        $marker['position'] = $fridge['long']. ', '.$fridge['lat'];
        $marker['draggable'] = true;
        $marker['infowindow_content'] = '<strong>'.$fridge['brand']. ' - '. $fridge['fridge_unit_code'] .'</strong>&nbsp; <a href="/cocacola/dashboard/fridge_current_street_view/'.$fridge['id'].'"><i class="fa fa-male">Street View</i></a>'.
        '<br />Status: '.$status['message'].
        '<br />Location: '.$fridge['location_name'].
        '<br />Temp: '.$fridge['temp'].'&deg;'.
        '<br />Expected Temp Range: '.$max_temp.'&deg;-'.$min_temp.'&deg;'.
        '<br />Street: '.$fridge['street'].
        '<br />Region: '.$fridge['region'].
        '<br />Province: '.$fridge['province'].
        '<br />Createdate: '.$fridge['createdate'];
        $marker['animation'] = 'DROP';
        $marker['icon'] = '/assets/img/' . $status['icon'];
        $this->googlemaps->add_marker($marker);
        $data['map'] = $this->googlemaps->create_map();

   
       
        $comma ='';
        $values ='';
        $labels ='';
        $count = 0;
        $barColors='';

        // This is for Daily Temperature Chart
        $temp_result = $this->fridge_model->get_daily_temperatures($fridge_id);

        foreach ($temp_result as $row) {   

            $max_temp = $row['expected_temp']+$row['tolerance'];
            $min_temp = $row['expected_temp']-$row['tolerance'];
            $off_temp = $row['considered_off'];

            $brand=$fridge['brand'];
          
            $stat = 'Optimal';
            $color = '#27ade5';

            if($row['fridge_temp'] > $max_temp){
                $color = '#d49c2d';
                $stat = 'Too Hot';
            }

            if($row['fridge_temp'] < $min_temp){
                $color = '#d49c2d';
                $stat = 'Too Cold';
            }

            if($row['fridge_temp'] > $off_temp){
                $color = '#d22c2c';
                $stat = 'Fridge is Off';
            }

            $labels .= $comma.'"'.humanize($row['dates']." [".$stat."]").'"';
            $values .= $comma.$row['fridge_temp'];
            $barColors.='myObjBar.datasets[0].bars['.$count.'].fillColor = "'.$color.'";';
            $comma = ',';

            $count++;
        }
      
       //Getting list of all temperature logs
        $logs = $this->fridge_model->get_fridge_logs($fridge_id);

        $log_result=$logs['result'];
        $data['query']=$logs['query'];
        $dataset ='';

        foreach ($log_result as $logs) {
         $dataset.='dataSet.push([
         "'.$logs['id'].'",
         "'.$logs['fridge_unit_code'].'",
         "'.$logs['fridge_type'].'",
         "'.$logs['location_name'].'",
         "'.$logs['temperature'].'",
         "'.$logs['province'].'",
         "'.$logs['region'].'",
         "'.$logs['street'].'",
         "'.$logs['createdate'].'"]);';
        }

        $data['script'] ="var dataSet = [ ];
        ". $dataset."
        $(document).ready(function() {
            $('#report_table').DataTable( {
                data:dataSet,
                columns: [
                    { title: 'Id' },
                    { title: 'fridge Unit Code' },
                    { title: 'Fridge type' },
                    { title: 'Location Name' },
                    { title: 'Temperature' },
                    { title: 'Province' },
                    { title: 'Region' },
                    { title: 'Street' },
                    { title: 'Createdate' }
                ]
            } );
        } );".'
        var barChartData = {
            labels: ['.$labels.'],
            datasets: [
                {
                    label: "Fridge Temperature Chart",
                    fillColor: "rgba(220,220,220,0.5)", 
                    strokeColor: "rgba(220,220,220,0.8)", 
                    highlightFill: "rgba(220,220,220,0.75)",
                    highlightStroke: "rgba(220,220,220,1)",
                    data: ['.$values.']
                }
            ]
        };
        var options = {
            scaleBeginAtZero: false,
            responsive: true,
            scaleStartValue : -50 
        };
        window.onload = function(){
            var ctx = document.getElementById("canvas").getContext("2d");
            window.myObjBar = new Chart(ctx).Bar(barChartData,options, {
                  responsive : true
            });

            //nuevos colores
           '.$barColors.'
            myObjBar.update();
        }';

        $data['page_title']="Fridge Log Report";

        $this->show_view('frigde_log_details', $data);
    }


     public function faulty_fridge_report(){

        $fridge_id=$this->fridge_temps();

        $province_id=$this->input->post('province');
       
        $fridge_type_id=$this->input->post('fridge_type');
        $data['unit_code']=$this->input->post('fridge_uinit_code');

         $brand_id=2;

        $fridges = $this->fridge_model->get_fridges($province_id,$brand_id,$fridge_type_id,$data['unit_code'],$fridge_id);

        $result=$fridges['result'];
        $data['query']=$fridges['query'];

        $province = $this->spazapp_model->get_province();
        $brand = $this->spazapp_model->get_brands();
        $fridge_type = $this->fridge_model->get_fridge_type();

        $province_option='';
        foreach ($province as $row) {
            $province_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $brand_option='';
        foreach ($brand as $row) {
            $brand_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $brand_option='';
        foreach ($brand as $row) {
            $brand_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $fridge_type_option='';
        foreach ($fridge_type as $row) {
            $fridge_type_option .= "<option value='".$row['id']."'>".$row['name']."</option>";
        }

        $data['province_option']=$province_option;
        $data['brand_option']=$brand_option;
        $data['fridge_type_option']=$fridge_type_option;

 
        $dataset ='';

          foreach ($result as $fr) {
         $dataset.='dataSet.push([
         "'."<a href='/cocacola/dashboard/fridge_details/".$fr['id']."'><font color='#f89520'>".$fr['fridge_unit_code']."</font></a>".'",
         "'.$fr['fridge_type'].'",
         "'.$fr['location_name'].'",
         "'.$fr['temp'].'",
         "'.$fr['province'].'",
         "'.$fr['region'].'",
         "'.$fr['street'].'",
         "'.$fr['createdate'].'",
         "'."<li class='dropdown'><a href='#' class='dropdown-toggle' data-toggle='dropdown'>Actions <b class='caret'></b></a><ul class='dropdown-menu' role='menu'><li><a href='/cocacola/dashboard/fridge_locations_history/".$fr['id']."'>Locations History</a></li><li><a href='/cocacola/dashboard/daily_temperature/".$fr['id']."'>Daily Temperatures Report</a></li><li><a href='/cocacola/dashboard/monthly_temperature/".$fr['id']."'>Monthly Temperatures Report</a></li></ul></li></ul>".'"]);';
        }

     
        $data['script'] ="var dataSet = [ ];
        ". $dataset."
        $(document).ready(function() {
            $('#report_table').DataTable( {
                data:dataSet,
                columns: [
                    { title: 'fridge Unit Code' },
                    { title: 'Fridge type' },
                    { title: 'Location Name' },
                    { title: 'Temperature' },
                    { title: 'Province' },
                    { title: 'Region' },
                    { title: 'Street' },
                    { title: 'Createdate' },
                    { title: 'Actions' }
                ]
            } );
        } );

      ";

        $data['page_title']="Faulty Fridge Report";

        $data['province'] = $this->spazapp_model->get_province_by_id($province_id);
        $data['brand'] = $this->spazapp_model->get_brands_by_id($brand_id);
        $data['fridge_type'] = $this->fridge_model->get_fridge_type_by_id($fridge_type_id);

        $this->show_view('frigde_log_report', $data); 

    }

     public function fridge_street_view($fridge_id)
    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        $fridge =  $this->fridge_model->get_fridge_street($fridge_id);
        $data['page_title'] = $fridge['brand']." - ".' Street View';
       
        $this->load->library('googlemaps');

        $config['center'] = $fridge['long']. ','.$fridge['lat'];
        $config['map_type'] = 'STREET';
        $config['streetViewPovHeading'] = 90;
        $this->googlemaps->initialize($config);
        $data['map'] = $this->googlemaps->create_map();


        $this->show_view('fridge_street_view', $data);
    }

  public function fridge_current_street_view($fridge_id)
    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        $fridge =  $this->fridge_model->get_current_location($fridge_id);

        if(empty($fridge['long'])){
            $fridge =  $this->fridge_model->get_fridge_street($fridge_id);
        } 

        $data['page_title'] = $fridge['brand']." - ".' Street View';
       
        $this->load->library('googlemaps');

        $config['center'] = $fridge['long']. ','.$fridge['lat'];
        $config['map_type'] = 'STREET';
        $config['streetViewPovHeading'] = 90;
        $this->googlemaps->initialize($config);
        $data['map'] = $this->googlemaps->create_map();


        $this->show_view('fridge_street_view', $data);
    }


      public function fridge_temparature()

    {

        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        $this->load->library('googlemaps');
        $data['page_title'] = 'Fridge Locations';
        $data['fidges'] =  $this->fridge_model->get_fridges_locations();

        $config['center'] = '-29.8590, 31.0189';
        $config['zoom'] = '1';
        $this->googlemaps->initialize($config);

        foreach ( $data['fidges'] as $fridge) 
        {
            $marker = array();
            $marker['position'] = $fridge['long']. ', '.$fridge['lat'];
            $marker['draggable'] = true;
            $marker['infowindow_content'] = '<strong>'.$fridge['brand']. '</strong>&nbsp; <a href="/cocacola/dashboard/fridge_street_view/'.$fridge['id'].'"><i class="fa fa-male">View Street</i></a> <br />'.$fridge['street'].'<br />'.$fridge['region'].'<br />'.$fridge['province'].'';
            $marker['animation'] = 'DROP';
            $marker['icon'] = '/assets/img/brr_map_icon.png';
            $this->googlemaps->add_marker($marker);
        }
        

        $data['map'] = $this->googlemaps->create_map();


        $this->show_view('fridge_locations', $data);
    }

    function fridge_temps(){
        $fidges=  $this->fridge_model->get_fridges_locations();
        $comma='';
        $faulty='';
        foreach ( $fidges as $row) 
        {
            $max_temp = $row['expected_temp']+$row['tolerance'];
            $min_temp = $row['expected_temp']-$row['tolerance'];
            $off_temp = $row['considered_off'];

            if($row['temp'] > $max_temp  OR $row['temp'] < $min_temp OR $row['temp'] > $off_temp){
                $faulty.=$comma.$row['id'];
                $comma=',';
            }

        
        }
        return $faulty;
    }
  public function fridge_history_street_view($fridge_id,$log_id)
    {
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        $fridge =  $this->fridge_model->get_fridge_street_history($fridge_id,$log_id);
        $data['page_title'] = $fridge['location_name']." - ".' Street View';
       $data['fridge_info']='
            <table>
                <tr>
                   <td style="background-color:silver;" colspan="2">
                        <strong>Fridge Information</strong>
                   </td>
                 
                </tr>
                <tr>
                   <td style="background-color:#f9f9f9;"><b>Unit Code</b></td>
                   <td style="background-color:#f9f9f9;"> : '.$fridge['fridge_unit_code'].'</td>
                </tr>

                <tr>
                   <td style="background-color:#f9f9f9;"><b>Fridge Type</b></td>
                   <td style="background-color:#f9f9f9;"> : '.$fridge['fridge_type'].'</td>
                </tr>

                <tr>
                   <td style="background-color:#f9f9f9;"><b>Temperature</b></td>
                   <td style="background-color:#f9f9f9;"> : '.$fridge['temp'].'&deg;</td>
                </tr>
            </table>';

        $this->load->library('googlemaps');
       
        $config['center'] = $fridge['long']. ', '.$fridge['lat'];
        $config['map_type'] = 'STREET';
        $config['streetViewPovHeading'] = 90;
        $this->googlemaps->initialize($config);
        $data['map'] = $this->googlemaps->create_map();

        $data['fridges']=$fridge;
        $this->show_view('fridge_street_view', $data);
    }
   function fridge_locations_history($fridge_id){
        //Turn off all error reporting because of googles map depreciation error
        error_reporting(0);

        $data['date_from']=$this->input->post('date_from');
        $data['date_to']=$this->input->post('date_to');
        $location=  $this->fridge_model->get_current_location($fridge_id);
        $result = $this->fridge_model->get_fridge_locations($fridge_id,$data['date_from'],$data['date_to']);
        $data['fridge']=$result;
        $this->load->library('googlemaps');
     
       // $config['center'] = '-29.8590, 31.0189';
        $config['center'] = $location['long'].','.$location['lat'];
        $config['zoom'] = 'auto';
        $config['directions'] = TRUE;
        //fridge_locations_historyfridge_locations_history$this->googlemaps->initialize($config);

        $dataset ='';
        $count=1;
        foreach ($result as $key=>$fridge) 
        {
       
            //get status
            $max_temp = $fridge['expected_temp']+$fridge['tolerance'];
            $min_temp = $fridge['expected_temp']-$fridge['tolerance'];
            $off_temp = $fridge['considered_off'];

            $status['icon'] = 'brr_map_icon.png';
            $status['message'] = 'Optimal';
         
            if($fridge['temp'] > $max_temp){
                $status['icon'] = 'brr_warning_map_icon.png';
                $status['message'] = 'Too Hot';
            }

            if($fridge['temp'] < $min_temp){
                $status['icon'] = 'brr_warning_map_icon.png';
                $status['message'] = 'Too Cold';
            }

            if($fridge['temp'] > $off_temp){
                $status['icon'] = 'brr_off_map_icon.png';
                $status['message'] = 'Fridge is Off';
            }

            //Working on location map
            $marker = array();
            $marker['position'] = $fridge['long']. ', '.$fridge['lat'];
            $marker['draggable'] = true;
            $marker['infowindow_content'] = '<strong>'.$key." ".$fridge['location_name']. '</strong>&nbsp; <a href="/cocacola/dashboard/fridge_history_street_view/'.$fridge['id'].'/'.$fridge['log_id'].'"><i class="fa fa-male"> Street View </i></a> <br /> <b>Fridge unit code </b> : '.$fridge['fridge_unit_code'].'<br /> <b>Street </b>: '.$fridge['street'].'<br /> <b>Region</b> : '.$fridge['region'].'<br /> <b>Province</b> : '.$fridge['province'].'<br/>'.'<b>Createdate</b> : '.$fridge['createdate'].'<br/>'.'<b>Temperature</b> : '.$fridge['temp'].'&deg;';
            $marker['animation'] = 'DROP';
            $marker['icon'] = '/assets/img/'.$status['icon'];
            $this->googlemaps->add_marker($marker);

            //Data set to be displayed on  data table 
             $dataset.='dataSet.push([
             "'."<a href='/dashboard/fridge_details/".$fridge['id']."'>"."<font color='#f8993b'>".$fridge['fridge_unit_code']."</font>"."</a>".'",
             "'.$fridge['fridge_type'].'",
             "'.$fridge['location_name'].'",
             "'.$fridge['province'].'",
             "'.$fridge['region'].'",
             "'.$fridge['street'].'",
             "'.$fridge['createdate'].'"]);';
         $count++;
        }
         $this->googlemaps->initialize($config);
        //DataTable script
        $data['script'] ="var dataSet = [ ];
        ". $dataset."
        $(document).ready(function() {
            $('#report_table').DataTable( {
                data:dataSet,
                columns: [
                    { title: 'fridge Unit Code' },
                    { title: 'Fridge type' },
                    { title: 'Location Name' },
                    { title: 'Province' },
                    { title: 'Region' },
                    { title: 'Street' },
                    { title: 'Createdate' },
                ]
            } );
        } );";

        $data['map'] = $this->googlemaps->create_map();

        $data['page_title'] = 'Fridge Locations History';
        $this->show_view('fridge_locations_history', $data);

    }

    function daily_monthly_temperature($fridge_id){
        $data['date_from']=$this->input->post('date_from');
        $data['date_to']=$this->input->post('date_to');

        $comma ='';
        $values ='';
        $labels ='';
        $dataSet='';
        $barColors='';
        $count=0;
        $days=$this->fridge_model->get_fridges_daily_temperature($fridge_id,$data['date_from'],$data['date_to']);
        $data['query']=$days['query'];

        foreach ($days['result'] as $row) {   

            $max_temp = $row['expected_temp']+$row['tolerance'];
            $min_temp = $row['expected_temp']-$row['tolerance'];
            $off_temp = $row['considered_off'];

            $color = '#27ade5';

            if($row['temp'] > $max_temp){
                $color = '#d49c2d';
               
            }

            if($row['temp'] < $min_temp){
                $color = '#d49c2d';
            }

            if($row['temp'] > $off_temp){
                $color = '#d22c2c';

            }
            $barColors.='myObjBar.datasets[0].bars['.$count.'].fillColor = "'.$color.'";';

            $labels.= $comma.'"'.(date(" D d M Y", strtotime($row['createdate']))).'"';

            $values.=$comma.$row['temp'];

            $comma = ',';
            $count++;
        }

      
        $dataset='';
        foreach ($days['result'] as $item) {
    
         $dataset.='dataSet.push([
         "'."<a href='/cocacola/dashboard/fridge_details/".$item['id']."'>"."<font color='#f8993b'>".$item['fridge_unit_code']."</font>"."</a>".'",
         "'.$item['fridge_type'].'",
         "'.$item['temp'].'",
         "'.$item['location_name'].'",
         "'.$item['province'].'",
         "'.$item['region'].'",
         "'.$item['street'].'",
         "'.$item['createdate'].'"]);';

        }

        $data['script']="var dataSet = [ ];
        ". $dataset."
       
        $(document).ready(function() {
            $('#report_table').DataTable( {
                data:dataSet,
                columns: [
                    { title: 'fridge Unit Code' },
                    { title: 'Fridge type' },
                    { title: 'Temperature' },
                    { title: 'Location Name' },
                    { title: 'Province' },
                    { title: 'Region' },
                    { title: 'Street' },
                    { title: 'Createdate' },
                ]
            } );
        } );


         var barChartData = {
            labels: [".$labels."],
           datasets: [
           {
            label: 'Fridge Temperature Chart',
            fillColor: 'rgba(220,220,220,0.5)', 
            strokeColor: 'rgba(220,220,220,0.8)', 
            highlightFill: 'rgba(220,220,220,0.75)',
            highlightStroke: 'rgba(220,220,220,1)',
            data: [".$values."]
            }
         ]
          
        };
       
        var options = {
            scaleBeginAtZero: false,
            responsive: true,
            scaleStartValue : -50 
        };
        window.onload = function(){
            var ctx = document.getElementById('canvas').getContext('2d');
            window.myObjBar = new Chart(ctx).Bar(barChartData,options, {
                  responsive : true
            });

            //nuevos colores
           ".$barColors."
            myObjBar.update();
        }";
        $data['fridge']=$this->fridge_model->get_fridges_monthly_temperature($fridge_id,$data['date_from'],$data['date_to']);
       
        $data['page_title']="Fridge Temperatures";
        $this->show_view('daily_monthly_temperature',$data);
    }

}