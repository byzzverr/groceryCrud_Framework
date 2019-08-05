  <body role="document">

    <div class="container theme-showcase" role="main">

      <!-- Main jumbotron for a primary marketing message or call to action -->
      <div class="jumbotron">
        <h1><?php echo $page_title?></h1>
      </div>

      <!-- Google Maps -->
      <div>

        <?php echo $map['html']; ?>
      </div>

    </div>

<?php if(isset($map)) { ?>
  <?php echo $map['js']; ?>
<? } ?>