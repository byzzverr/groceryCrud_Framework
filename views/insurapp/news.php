  <body role="document">

    <div class="container theme-showcase" role="main" style="background:#fff; padding-left:10px;">

      <!-- Main jumbotron for a primary marketing message or call to action -->
      <div class="jumbotron">
        <h1><?php echo $page_title?></h1>
        <p><?php echo $news['body']?></p>
        <p><a href="/admin/add_seen_news_event/<?php echo $news['id']?>" class="btn btn-primary btn-lg" role="button">I have read and understand &raquo;</a></p>
      </div>

    </div> <!-- /container -->