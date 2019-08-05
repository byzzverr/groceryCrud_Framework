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
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Order Management <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="/reps/management/">Customers</a></li>
                  <li><a href="/reps/management/orders">Orders</a></li>
                </ul>
              </li>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Reports <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="/dashboard/get_orders">All Orders</a></li>
                  <li><a href="/dashboard/rep_login_report">Login Report</a></li>
                  <li><a href="/dashboard/sales_report">Sales Report</a></li>
                </ul>
            </li>
            
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Logistics <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="/logistics/">View Approved Orders</a></li>
                  <li><a href="/logistics/delivered">View Delivered Orders</a></li>
                </ul>
            </li>
            
             <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Call Schedules <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="/rep/">Today's Schedule</a></li>
                  
                </ul>
            </li>
              <!---<li><a href="/surveys/assessment/1">Surveys</a></li>-->
            </ul>
              
              
            <ul class="nav pull-right">
              
              <li><div class="nav_user_details"><?php echo $user_info->name;?></div></li>
              <li class="divider-vertical"></li>
              <li><a href="/admin/logout">Logout</a></li>
            </ul>
          </div><!-- /.nav-collapse -->
        </div>
      </div><!-- /navbar-inner -->
    </div>