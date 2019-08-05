  <?
  $user_info = $this->aauth->get_user();
  ?>
  <div class="navbar navbar-inverse">
      <div class="navbar-inner">
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".navbar-responsive-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="#">SpazApp</a>
          <div class="nav-collapse collapse navbar-responsive-collapse">
            <ul class="nav">
              <li class="active"><a href="/admin/">Home</a></li>
               <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">System Management <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  
                  <li><a href="/storeowner/users/store_details">Store Details</a></li>

                </ul>
              </li> 
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Financial <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="/storeowner/financial/user_wallet">User wallet</a></li>
                 
                </ul>
              </li>              
             <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Order Management <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  
                  <li><a href="/storeowner/management/orders">Orders</a></li>

                </ul>
              </li>  
            </ul>
            <ul class="nav pull-right">
              <li><div class="nav_user_details"><?php echo $user_info->name;?></div></li>
              <li class="divider-vertical"></li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">User Management <b class="caret"></b></a>
                <ul class="dropdown-menu">
                 
                  <li><a href="/other_users/change_password">Update Password</a></li>
                  <li class="divider"></li>
                  <li><a href="/admin/logout">Logout</a></li>
                </ul>
              </li>
            </ul>
          </div><!-- /.nav-collapse -->
        </div>
      </div><!-- /navbar-inner -->
    </div>