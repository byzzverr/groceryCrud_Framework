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
                  <li><a href="/management/suppliers">Suppliers</a></li>
                  <li><a href="/management/categories">Categories</a></li>
                 
                  </li>
                </ul>
              </li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Order Management <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                   <li><a href="/management/customers">Customers</a></li>
                  <li><a href="/management/products">Products</a></li>
                  <li><a href="/management/all_distributors/">Distributor Products</a></li>
                </ul>
              </li>              
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Reports <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
              
                    <li><a href="/dashboard/products_no_image">Products With No Image</a></li>
                    <li><a href="/dashboard/products_zero_price">Products With Zero Price</a></li>
             
                </ul>
              </li> 

               <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Brand Connect <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="/task/task_result">BC Tasks Results</a></li>
                  <li class="divider"></li>
                  <li><a href="/photosnap/responses">PhotoSnap Responses</a></li>
                 
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