<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Push_notifications {

    public function __construct() {

        // get main CI object
        $this->CI = & get_instance();

        // Dependancies
        $this->CI->load->library('session');
        $this->CI->load->library('email');
        $this->CI->load->database();
        $this->CI->load->helper('url');
        $this->CI->load->helper('string');
        $this->CI->load->helper('email');
        $this->CI->load->helper('language');
        $this->CI->lang->load('aauth');
        $this->CI->load->model('financial_model');
        $this->CI->load->model('user_model');

        // config/aauth.php
        $this->CI->config->load('aauth');
        $somestupidthing = $this->CI->config->item('aauth');

        $this->config_vars =& $somestupidthing;
    }

    function get_user($token){

        $query = $this->CI->db->query("SELECT user_id FROM push_tokens WHERE push_token = '$token'");
        $result = $query->row_array();
        return $result;

    }

    function insert_comms($token,$result,$comm_id){
            $user = $this->get_user($token);

            $createdate = date('Y-m-d H:i:s');
            $user_id = $user['user_id'];

            $this->add_comms($user_id,$token,$result,$comm_id,$createdate);
    }

    function add_comms($user_id,$token,$result,$comm_id,$createdate){
         $query = $this->CI->db->query("INSERT INTO 
                comm_queue(`user_id`,`comm_id`,`status`,`jason`,`to`,`results`,`createdate`) 
                VALUES('$user_id','$comm_id','','','$token','$result',NOW())");
    }

    function get_comms($template){
       $query = $this->CI->db->query("SELECT * FROM `comms` WHERE template = '$template'");
       return $query->row_array();
    }

   
}