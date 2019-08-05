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
          <a class="brand" href="#">insurapp</a>
          <div class="nav-collapse collapse navbar-responsive-collapse">
            <ul class="nav">
              <li class="active"><a href="/insurapp/admin/">Home</a></li>
               <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Settings <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="/insurapp/insurance/settings">Product Settings </a></li>
                    <li><a href="/insurapp/insurance/types">Products Types </a></li>
                    <li><a href="/insurapp/insurance/underwriters">Products Underwriters </a></li>
                    <li><a href="/insurapp/insurance/master_companies">Master Companies </a></li>
                    <li><a href="/insurapp/insurance/agencies">Agencies </a></li>
                    <li><a href="/insurapp/insurance/branches">Branches </a></li>
                    <li><a href="/insurapp/insurance/entities">Entities </a></li>
                    <li><a href="/insurapp/insurance/design">Designs </a></li>
                    <li><a href="/insurapp/insurance/terms">Product Terms </a></li>
                    <li><a href="/insurapp/insurance/terms_audio">Product Terms Audio </a></li>
                    <li><a href="/insurapp/insurance/statuses">Statuses </a></li>
                </ul>
              </li>
               <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Products <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="/insurapp/insurance/products">Products </a></li>
                </ul>
              </li>
               <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Sales <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="/insurapp/insurance/applications">Applications </a></li>
                    <li><a href="/insurapp/insurance/awaiting_payment">Awaiting Bank Payment</a></li>
                    <li><a href="/insurapp/insurance/sales">Policies</a></li>
                    <li><a href="/insurapp/insurance/detailed_sales">Detailed Sales </a></li>
                </ul>
              </li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Reports <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="/insurapp/dashboard/user_stats">User Stats</a></li>
                    <li><a href="/insurapp/dashboard/insurance_sales_report">Sales Overview</a></li>
                    <li><a href="/insurapp/dashboard/products_sales">Product Sales</a></li>
                </ul>
              </li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Financial <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="/insurapp/financial/billruns">Billruns</a></li>
                  <li><a href="/insurapp/financial/">Wallet Dashboard</a></li>
                  <li><a href="/insurapp/financial/comm">Comm Dashboard</a></li>
                  <li><a href="/insurapp/financial/wallets">Customer Wallets</a></li>
                  <li><a href="/insurapp/financial/wallets_comm">Comm Wallets</a></li>
                  <li><a href="/insurapp/financial/wallet_transactions">Last 200 Wallet Transactions</a></li>
                  <li><a href="/insurapp/financial/wallet_transactions_comm">Last 200 Comm Transactions</a></li>
                  <li><a href="/insurapp/financial/sales_comm_report">Sales Comm</a></li>
                </ul>
              </li>
            </ul>
            <ul class="nav pull-right">
              <li><a href="/insurapp/users/user"><?php echo $user_info->name;?></a></li>
              <!-- <li><div class="nav_user_details"><a href="/insurapp/users/user"><?php echo $user_info->name;?></a></div></li> -->
              <li class="divider-vertical"></li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">User Management <b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li><a href="/insurapp/users/master_company_users">Master Company Users</a></li>
                  <li><a href="/insurapp/users/agency_users">Agency Users</a></li>
                  <li><a href="/insurapp/users/branch_users">Branch Users</a></li>
                  <li><a href="/insurapp/users/sales_users">Sales Agents</a></li>
                  <li><a href="/other_users/change_password">Update Password</a></li>
                  <li class="divider"></li>
                  <li><a href="/insurapp/admin/logout">Logout</a></li>
                </ul>
              </li>
            </ul>
          </div><!-- /.nav-collapse -->
        </div>
      </div><!-- /navbar-inner -->
    </div>