  <?php
  $user_info = $this->aauth->get_user();
  ?>


    <!-- Bootstrap 4 -->
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
            <a class="nav-link dropdown-toggle" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Order Management</a>
            <div class="dropdown-menu" aria-labelledby="dropdown01">
                  <a class="dropdown-item"  href="/trader/management/customers">Customers</a>
                  <a class="dropdown-item"  href="/trader/management/orders">Orders</a>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Reports</a>
            <div class="dropdown-menu" aria-labelledby="dropdown01">
                  <a class="dropdown-item" href="/trader/dashboard/trader_locations">Sparks location</a>
                  <a class="dropdown-item" href="/trader/dashboard/sparks_report">Sparks Report</a>
            </div>
          </li>
         
        
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Brand Connect</a>
              <div class="dropdown-menu" aria-labelledby="dropdown01">
                 <a class="dropdown-item" href="/trader/task/task_result">BC Tasks Results</a>
            </div>
          </li>
         
        </ul>

        <ul class="navbar-nav pull-right">
          <a class="navbar-text" href="#"><?php echo $user_info->name;?></a>
          <li class="divider-vertical"></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="http://example.com" id="dropdown02" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">User Management</a>
              <div class="dropdown-menu" aria-labelledby="dropdown02">
                 
                  <a class="dropdown-item" href="/other_users/change_password">Update Password</a>
                  <a class="dropdown-item" href="/admin/logout">Logout</a>
              </div>
            </li>
        </ul>
      </div>
    </nav>