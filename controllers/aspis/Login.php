<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Login extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Your own constructor code
        $this->load->library("Aauth");
        $this->load->model("example_model");
        $this->load->helper('url');
        $this->load->library('grocery_CRUD');
        $this->load->model('event_model');
        $this->load->model('user_model');
        $this->load->helper('global_helper');

        if ($this->aauth->is_loggedin()){
            redirect('/cocacola/cocacola');
        }    
    }

    function show_view($view, $data=''){

      
      $this->load->view('cocacola/include/header', $data);
      $this->load->view('cocacola/'.$view, $data);
      $this->load->view('cocacola/include/footer', $data);
    }

    function index($info=''){
        $data['page_title'] = 'Login';

        switch ($info) {
            case 'logout':
                $data['info'] = 'You have been logged out.';
                break;
            
            default:
                unset($data['info']);
                break;
        }
      
	    $this->show_view('templates/login', $data);
    }


	function process(){
		if(isset($_POST['username']) && isset($_POST['password'])){
            $username = $_POST['username'];
            if(isset($_POST['remember_me'])){$rem_me = TRUE;}else{$rem_me = FALSE;}
			if($this->aauth->login($_POST['username'],$_POST['password'],$rem_me,'spazapp')){

				$this->event_model->track('login','login_successful');
                redirect("/cocacola/cocacola");

			}else{
                $this->event_model->track('login','login_attempt', $username);
				$data['error'] = 'Incorrect username or password';
				$data['page_title'] = 'Login';
	    		$this->show_view('templates/login', $data);
			}
		}else{
			redirect("/cocacola/login");
		}
    }

    function process_app_login($username,$password){
        if(isset($username) && isset($password)){
            $username = $_POST['username'];
            if(isset($_POST['remember_me'])){$rem_me = TRUE;}else{$rem_me = FALSE;}
            if($this->aauth->login($_POST['username'],$_POST['password'],$rem_me)){

                $this->event_model->track('login','app_login_successful');

            }else{
                $this->event_model->track('login','app_login_attempt', $username);
                $data['error'] = 'Incorrect username or password';
                return $data;
            }
        }else{
            $data['error'] = 'Incorrect username or password';
            return $data;
        }
    }

    function forgot_password(){
        if(isset($_POST['username'])){
            $username = $_POST['username'];
            $user = $this->user_model->user_search($username);
            if($user && isset($user['id'])){
                $this->event_model->track('login','reset_password', $user['id']);
                $data['info'] = $this->user_model->reset_password($user['id']);
                $data['page_title'] = 'Forgot Password';
                $this->show_view('templates/forgot_password', $data);
            }else{
                $this->event_model->track('login','reset_password_attempt', $username);
                $data['error'] = 'User could not be found.';
                $data['page_title'] = 'Forgot Password';
                $this->show_view('templates/forgot_password', $data);
            }
        }else{
            $data['page_title'] = 'Forgot Password';
            $this->show_view('templates/forgot_password', $data);
        }
        
    }

}

/*

        //$this->aauth->is_admin()
        //$this->aauth->get_user()
        //$this->aauth->control_group("Mod")
        //$this->aauth->control_perm(1)
        //$this->aauth->list_groups()
        //$this->aauth->list_users()
        //$this->aauth->is_allowed(1)
        //$this->aauth->is_admin()
        //$this->aauth->create_perm("deneme",'defff')
        //$this->aauth->update_perm(3,'dess','asd')
        //$this->aauth->allow(1,1)
        //$this->aauth->add_member(1,1)
        //$this->aauth->deny(1,1)
        //$this->aauth->mail()
        //$this->aauth->create_user('seass@asds.com','asdasdsdsdasd','asd')
        //$this->aauth->verify_user(11, 'MLUguBbXpd9Eeu5B')
        //$this->aauth->remind_password('seass@asds.com')
        //$this->aauth->reset_password(11,'0ghUM3oIC95p7uMa')
        //$this->aauth->is_allowed(1)
        //$this->aauth->control(1)
        //$this->aauth->send_pm(1,2,'asd')
        //$this->session->flashdata('d')
        //$this->aauth->add_member(1,1)
        //$this->aauth->create_user('asd@asd.co','d')
        //$this->aauth->send_pm(1,2,'asd','sad')
        //$this->aauth->list_pms(1,0,3,1)
        //$this->aauth->get_pm(6, false)
        //$this->aauth->delete_pm(6)
        //$this->aauth->set_as_read_pm(13)
        //$this->aauth->create_group('aa')
         $this->aauth->create_perm('asdda')

*/