  <?
  $user_info = $this->aauth->get_user();

        switch ($user_info->default_usergroup) {
            case 1:
                $page = 'admin';
                break;
            case 4:
                $page = 'rep';
                break;
            case 8:
                $page = 'store_owner';
                break;
            case 11:
                $page = 'distributor';
                break;
            default:
                $page = 'store_owner';
                break;
        }

        //$this->load->view('include/nav/distributor/');
        $this->load->view('include/nav/'.$page);

  ?>
