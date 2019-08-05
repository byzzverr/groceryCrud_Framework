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
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Reports <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="/insurapp/dashboard/branch_user_stats">User Stats</a></li>
                  
                </ul>
              </li>
             
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Finance <b class="caret"></b></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="/insurapp/financial/your_wallet">Wallet</a></li>
                  <li><a href="/insurapp/financial/your_wallet_transactions">Last 200 Wallet Transactions</a></li>
                  
                </ul>
              </li>
            </ul>
            <ul class="nav pull-right">
              <li><a href="/insurapp/users/user"><?php echo $user_info->name;?></a></li>
              
              <li class="divider-vertical"></li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">User Management <b class="caret"></b></a>
                <ul class="dropdown-menu">
                  
                  <li><a href="/insurapp/users/your_branch_user">Your Branch Users</a></li>
                  
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