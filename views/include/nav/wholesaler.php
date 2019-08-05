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
                  <li><a href="/management/orders">Orders</a></li>
                  <li><a href="/management/order_items">Order Items</a></li>
                </ul>
              </li>              
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Reports <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="/management/products">Products</a></li>
                  <li><a href="/dashboard/get_orders">All Orders</a></li>
                  <li><a href="/dashboard/customer_stats">Customer Stats</a></li>
                </ul>
              </li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Logistics <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="/logistics/">View Approved Orders</a></li>
                  <li><a href="/logistics/awaiting_delivery">View Orders Awaiting Delivery</a></li>
                  <li><a href="/logistics/delivered">View Delivered Orders</a></li>
                  <li><a href="/logistics/create_delivery">Create Delivery</a></li>
                </ul>
              </li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Airtime <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="/sim_control/purchased_list">Purchased List</a></li>
                  <li><a target="_blank" href="/sim_control/cron">Airtime Cron</a></li>
                  <li><a target="_blank" href="/sim_control/update_vouchers">Update Products</a></li>
                </ul>
              </li>
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