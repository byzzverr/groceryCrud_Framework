 <body role="document">

    <div class="container theme-showcase" role="main">

      <!-- Main jumbotron for a primary marketing message or call to action -->
      
        <h3><?php echo $page_title?></h3>
     
         <?php if(isset($fridge_info)){ echo $fridge_info; } ?>
      <!-- Google Maps -->
      <div>

        <?php echo $map['html']; ?>
      </div>
    
      </div>
      <div class="form-actions">
        <a onclick="history.go(-1)" class="btn">Back</a>
      </div>
    </div>

<?php if(isset($map)) { ?>
  <?php echo $map['js']; ?>
<? } ?>