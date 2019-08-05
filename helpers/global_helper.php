<?php

function get_app_settings($base_url){
    switch ($base_url) {
        case 'http://admin.taptuck.bv/':
        case 'http://admin.taptuck.tn/':
        case 'http://admin.taptuck.co.za/':
        case 'http://demo.taptuck.co.za/':
        case 'http://taptuck.mm/':
        case 'https://taptuck.mm/':
            $app_folder = 'taptuck/';
            $app_name = 'taptuck';
            break;

        case 'http://cocacola.spazapp.co.za/':
        case 'http://cocacola.mm/':
        case 'http://cocacola.spazapp.bv/':
            $app_folder = 'cocacola/';
            $app_name = 'cocacola';
            break;

         case 'http://liberty.spazapp.co.za/':
         case 'http://libertylife.mm/':
         case 'http://liberty.spazapp.bv/':
            $app_folder = 'libertylife/';
            $app_name = 'libertylife';
            break;

         case 'http://supps365.bv/':
         case 'https://supps365.co.za/':
         case 'http://www.supps365.co.za/':
         case 'https://www.supps365.co.za/':
            $app_folder = 'supps365/';
            $app_name = 'supps365';
            break;

         case 'http://admin.umsstokvel.bv/':
         case 'http://admin.stokvel.tn/':
         case 'http://codeigniter/':
         case 'http://stockvel.mm/':
         case 'http://stokvel.tn/':
         case 'http://admin.umsstokvel.co.za/':
         case 'https://admin.umsstokvel.co.za/':
	       case 'http://demo.umsstokvel.co.za/':
            $app_folder = 'stokvel/';
            $app_name = 'stokvel';
            break;

         case 'http://admin.umsstokvel.bv/':
         case 'http://admin.spazapp.tn/':
         case 'http://stockvel.mm/':
         case 'http://admin.umsstokvel.co.za/':
         case 'https://admin.umsstokvel.co.za/':
         case 'http://demo.umsstokvel.co.za/':
            
            $app_name = 'spazapp';
            break;

         case 'http://aspis.spazapp.bv/':
            $app_folder = 'aspis/';
            $app_name = 'aspis';
            break;

         case 'http://insurapp.bv/':
         case 'http://admin.insurapp.bv/':
         case 'http://demo.insurapp.co.za/':
         case 'https://admin.insurapp.co.za/':
            $app_folder = 'insurapp/';
            $app_name = 'insurapp';
            break;   

        case 'http://insurapp.mm/':
            $app_folder = 'insurapp/';
            $app_name = 'insurapp';
            break;   

        case 'http://africanunity.insurapp.co.za/':
            $app_folder = 'africanunity/';
            $app_name = 'africanunity';
            break;
        
        case 'http://supps365.mm:8012/':
            $app_folder = 'supps365/';
            $app_name = 'africanunity';
            break;

        default:
            $app_folder = '';
            $app_name = 'spazapp';
            break;

    }

    //echo $app_folder;exit;

    return array('app_folder' => $app_folder, 'app_name' => $app_name);
}

function validateIdNumber($id) {
    $match = preg_match ("!^(\d{2})(\d{2})(\d{2})\d{7}$!", $id, $matches);
    if (!$match) {
    return false;
    }

    list (, $year, $month, $day) = $matches;

    /**
    * Check that the date is valid
    */
    if (!checkdate($month, $day, $year)) {
    return false;
    }

    /**
    * Now Check the control digit
    */
    $d = -1;

    $a = 0;
    for($i = 0; $i < 6; $i++) {
     $a += $id{2*$i};
    }

    $b = 0;
    for($i = 0; $i < 6; $i++) {
        $b = $b*10 + $id{2*$i+1};
    }
    $b *= 2;

    $c = 0;
    do {
        $c += $b % 10;
        $b = $b / 10;
    } while($b > 0);

        $c += $a;
        $d = 10 - ($c % 10);
        if($d == 10) $d = 0;

        if ($id{strlen($id)-1} == $d) {
        return true;
    }

return false;
}

function strip_db_rejects($table, $dirty_array){

  $_CI = &get_instance();
  $clean_array = array();
  $table_fields = $_CI->db->list_fields($table);

  foreach ($dirty_array as $key => $value) {
    if(in_array($key, $table_fields)){
      $clean_array[$key] = $value;
    }
  }
  return $clean_array;
}
