  <body role="document">

    <div class="container theme-showcase" role="main">

      <!-- Main jumbotron for a primary marketing message or call to action -->
      <div class="jumbotron">
        <form action="/insurapp/dashboard/user_stats" method="post">
          Select User: <select name="user_id"  class="selectpicker" data-show-subtext="true" data-live-search="true"  style="color:black; background-color: #fff" onchange="this.form.submit();">
            <?php 
            foreach ($users as $key => $user) {  
              if ($user['id'] == $user_info->id) { $selected = 'selected="selected"'; }else{ $selected = '';}
              ?>
             <option value="<?php echo $user["id"]; ?>" <?php echo $selected; ?>><?php echo $user['name']; ?></option>
            <? } ?>
            
          </select>
        </form>
        <h1><?php echo $page_title?></h1>
        <p>Welcome to user stats profile. From here you will ba able to see user details and recent activity.</p>
      </div>


      <h4>User Details</h4>
       <table class="table table-condensed">
              <tr>
                  <th>ID</th> <td><?php echo $user_info->id?></td>
              </tr>
              <tr>
                  <th>Email</th> <td><?php echo $user_info->email?></td>
              </tr>
              <tr>
                  <th>Last Login</th> <td><?php echo $user_info->last_login?></td>
              </tr>
      </table>

      <? if (isset($customer_info)){ ?>

      <h4>Customer Details</h4>
       <table class="table table-condensed">
              <tr>
                  <th>ID</th> <td><?php echo $customer_info['id']?></td>
              </tr>
              <tr>
                  <th>Company Name</th> <td><?php echo $customer_info['company_name']?></td>
              </tr>
              <tr>
                  <th>Name</th> <td><?php echo $customer_info['first_name']?> <?php echo $customer_info['last_name']?></td>
              </tr>
              <tr>
                  <th>Reards</th> <td><?php echo $customer_info['rewards']?></td>
              </tr>
              <tr>
                  <th>Airtime</th> <td><?php echo $customer_info['airtime']?></td>
              </tr>
      </table>

      <? } ?>

    <h4>User Stats</h4>

    <div id="chart_container" style="width: 100%">
      <canvas id="canvas" height="450" width="600"></canvas>
    </div>
<!--
       <table class="table table-condensed">
        <thead>
          <tr>
              <?php foreach ($user_stats as $stat){
                  echo "<th>".humanize($stat['action'])."</th>";
               } ?>
          </tr>
          <tr>
              <?php foreach ($user_stats as $stat){
                  echo "<td>".$stat['count']."</td>";
               } ?>
          </tr>
       </table>
-->
    <h4>User Events</h4>

      <?php 
      if(is_array($user_events) && count($user_events) >= 1){
        $results = $user_events;
      ?>
    <div style="height:300px; overflow:auto;">
    <table class="table table-condensed">
      <thead>
        <tr>
   <? 
   if (isset($controls)) { $results[0]['controls'] = '1'; }
   foreach ($results[0] as $key => $result){ 
          echo "<th>".humanize($key)."</th>";
   } ?>
        </tr>
      </thead>
      <tbody>
   <? foreach ($results as $result){ 
    if (isset($controls)) { $result['controls'] = '1'; }
      echo '<tr>';
      foreach ($result as $field_name => $value) {
        if (isset($controls) && $field_name == 'controls') { 
          echo "<td>"; 
          foreach ($controls as $action => $url) {
            echo ' <a href="'.$url.$result['id'].'">'.$action.'</a> ';
          }
          echo "</td>"; 
        }else{
          echo "<td>".humanize($value)."</td>"; 
        }
      }
      echo '</tr>';
   } ?>
      </tbody>
    </table>
  </div>
  <? } else {?>
  <p>There are no events for this user</p>
  <? } ?>
   <div class="form-actions">
    <a onclick="history.go(-1)" class="btn">Back</a>
   </div>  
    </div> <!-- /container -->