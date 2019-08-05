  <body role="document">

    <div class="container theme-showcase" role="main">

      <!-- Main jumbotron for a primary marketing message or call to action -->
      <div class="jumbotron title page">
        <h1><?php echo $page_title?></h1>
        <p>Welcome to the application. Let's sell</p>
      </div>

   >

   <div class="jumbotron col user">
        <h2>Users</h2>
        <p>Manage users </p>
        <p><a href="/insurapp/users/your_branch_user" class="btn btn-primary btn-lg" role="button">Users &raquo;</a></p>
    </div>

   <div class="jumbotron col user">
        <h2> Wallet </h2>
        <p>Monitor your wallet</p>
        <p><a href="/insurapp/financial/your_wallet" class="btn btn-primary btn-lg" role="button">Wallet &raquo;</a></p>
    </div>   

    <div class="jumbotron col user">
      <h2> Wallet transactions </h2>
      <p>Last 200 Wallet transactions</p>
      <p><a href="/insurapp/financial/your_wallet_transactions" class="btn btn-primary btn-lg" role="button">User Stats &raquo;</a></p>
    </div>


</div> <!-- /container -->