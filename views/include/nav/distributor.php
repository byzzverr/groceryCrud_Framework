<?php
$user_info = $this->aauth->get_user();
?>
<!--- Bootstrap 4 -->
<nav class="navbar navbar-toggleable-md navbar-inverse navbar-inner">
      <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <a class="navbar-brand" href="#">Spazapp</a>
      <div class="collapse navbar-collapse " id="navbarsExampleDefault">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item active">
            <a class="nav-link" href="/">Home <span class="sr-only">(current)</span></a>
          </li>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">System Management</a>
            <div class="dropdown-menu" aria-labelledby="dropdown01">
              <a class="dropdown-item" href="/distributors/distributor_management/news">News Items</a>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Order Management</a>
            <div class="dropdown-menu" aria-labelledby="dropdown01">
                <a class="dropdown-item" href="/distributors/distributor_management/">Customers</a>
                <a class="dropdown-item" href="/distributors/distributor_management/products/3">Products</a>
                <a class="dropdown-item" href="/distributors/distributor_management/product_specials">Product Specials</a>
                <a class="dropdown-item" href="/distributors/distributor_management/orders">Orders</a>
                <a class="dropdown-item" href="/distributors/distributor_management/delivered_orders">Delivered Orders</a>
                <a class="dropdown-item" href="/distributors/distributor_management/distributor_product_allocation">Distributor product allocation</a>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Reports</a>
            <div class="dropdown-menu" aria-labelledby="dropdown01">
                <a class="dropdown-item" href="/distributors/distributor_reports/get_orders">All Orders</a>
                 <a class="dropdown-item" href="/distributors/distributor_reports/daily_orders">Daily Orders</a>
                 <a class="dropdown-item" href="/distributors/distributor_reports/sales_report">Sales Report</a>
                 <a class="dropdown-item" href="/distributors/distributor_reports/customer_locations">Customer Locations</a>
                 <a class="dropdown-item" href="/distributors/distributor_reports/customer_sales_report">To 50 Customer Sales Report</a>
                 <a class="dropdown-item" href="/distributors/distributor_reports/product_sales_report">To 50 Product Sales Report</a>
            </div>
          </li>
           <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Logistics</a>
              <ul class="dropdown-menu" role="menu">
                <a class="dropdown-item" href="/distributors/distributor_logistics/trucks/">Delivery Trucks</a>
                <a class="dropdown-item" href="/distributors/distributor_logistics/drivers/">Truck Drivers</a>
                <a class="dropdown-item" href="/distributors/distributor_logistics/approved_distributor_orders/">View Unassigned Approved Orders</a>
                <a class="dropdown-item" href="/distributors/distributor_logistics/delivered">View Delivered Orders</a>
                <a class="dropdown-item" href="/distributors/distributor_logistics/create_delivery">Deliveries</a>
              </ul>
            </li>
        </ul>

        <ul class="navbar-nav pull-right">
          <a class="navbar-text" href="#"><?php echo $user_info->name;?></a>
          <li class="divider-vertical"></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="http://example.com" id="dropdown02" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">User Management</a>
              <div class="dropdown-menu" aria-labelledby="dropdown02">
                  <a class="dropdown-item" href="/other_users/change_password">Update Password</a>
                  <a class="dropdown-item" href="/distributors/distributor_profile/">Edit My Profile</a>
                  <?php if ($user_info->parent_id == "0") { ?>
                  <a class="dropdown-item" href="/distributors/distributor_profile/company">Edit Company Profile</a>
                  <a class="dropdown-item" href="/distributors/distributor_profile/user">Company Users</a>
                  <?php } ?>
                  <a class="dropdown-item" href="/admin/logout">Logout</a>
              </div>
            </li>
        </ul>
      </div>
    </nav>