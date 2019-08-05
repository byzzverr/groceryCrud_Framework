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
                  <li class="dropdown-header">News</li>
                  <li><a href="/suppliers/supplier_management/news">News Items</a></li> 
                </ul>

              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Order Management <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="/suppliers/supplier_management/all_distributors_customers">Customers</a></li>
                  <li><a href="/suppliers/supplier_management/all_distributors_produts">Products</a></li>
                  <li><a href="/suppliers/supplier_management/product_specials">Product Specials</a></li>
          </ul>
              </li>              

              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Reports <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  
                  <li><a href="/suppliers/suppliers_reports/get_supplier_orders">All Orders</a></li>
                  <li><a href="/suppliers/suppliers_reports/supplier_customer_stats">Customer Stats</a></li>
<!--                  <li><a href="/suppliers/suppliers_reports/supplier_survey_report">Survey Report</a></li>-->
                  <li><a href="/dashboard/survey_report">Survey Report</a></li>
                 
                  <li><a href="/suppliers/suppliers_reports/supplier_sales_report">Sales Report</a></li>
                 <li><a href="/suppliers/suppliers_reports/customer_locations">Customer Locations</a></li>

				
                </ul>
              </li> 
              <li class="dropdown">

                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Logistics <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="/suppliers/supplier_management/all_distributors_delivered">View Delivered Orders</a></li>

                </ul>

              </li>
              <li class="dropdown">

              </li>
              
              <li class="dropdown">

              </li>
            </ul>
            <ul class="nav pull-right">
              <li><div class="nav_user_details"><?php echo $user_info->name;?></div></li>
              <li class="divider-vertical"></li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">User Management <b class="caret"></b></a>
                <ul class="dropdown-menu">
                   <li><a href="/other_users/change_password">Update Password</a></li>
                  <li><a href="/suppliers/users/user">Users</a></li>

                  <li><a href="/admin/logout">Logout</a></li>
                </ul>
              </li>
          </ul>
          </div><!-- /.nav-collapse -->
        </div>
      </div><!-- /navbar-inner -->
    </div>

