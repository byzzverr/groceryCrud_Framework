<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html" charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">

   <title><?php echo humanize($page_title); ?></title>

 <?php 
 
 if(isset($css_files)){ ?>
  
  <?php foreach($css_files as $file): ?>
   <link type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />
  <?php endforeach; 
  }else{
  ?>

  <link type="text/css" rel="stylesheet" href="/assets/grocery_crud/themes/twitter-bootstrap/css/bootstrap.min.css" /> <!-- Bootstrap v2.1.1 -->
  <link type="text/css" rel="stylesheet" href="/assets/grocery_crud/themes/twitter-bootstrap/css/bootstrap-responsive.css" />
  <link rel="stylesheet" href="//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css">

  <? } 
 if(isset($js_files)){ ?>
  
  <?php foreach($js_files as $file): ?>
   <script src="<?php echo $file; ?>"></script>
  <?php endforeach; 

  }else{
    ?>
      <script src="/assets/grocery_crud/js/jquery-1.10.2.min.js"></script>
      <script src="<?php echo base_url('assets/js/custom.js') ?>"></script>
      <script src="/assets/grocery_crud/js/jquery_plugins/ui/jquery-ui-1.10.3.custom.min.js"></script>
      <script src="/assets/grocery_crud/js/common/lazyload-min.js"></script>
      <script src="/assets/grocery_crud/js/common/list.js"></script>
      <script src="/assets/grocery_crud/themes/twitter-bootstrap/js/libs/bootstrap/bootstrap.min.js"></script>
      <script src="/assets/grocery_crud/themes/twitter-bootstrap/js/libs/bootstrap/application.js"></script>
      <script src="/assets/grocery_crud/themes/twitter-bootstrap/js/libs/modernizr/modernizr-2.6.1.custom.js"></script>
      <script src="/assets/grocery_crud/themes/twitter-bootstrap/js/libs/tablesorter/jquery.tablesorter.min.js"></script>
      <script src="/assets/grocery_crud/themes/twitter-bootstrap/js/cookies.js"></script>
      <script src="/assets/grocery_crud/themes/twitter-bootstrap/js/jquery.form.js"></script>
      <script src="/assets/grocery_crud/js/jquery_plugins/jquery.numeric.min.js"></script>
      <script src="/assets/grocery_crud/themes/twitter-bootstrap/js/libs/print-element/jquery.printElement.min.js"></script>
      <script src="/assets/grocery_crud/js/jquery_plugins/jquery.fancybox-1.3.4.js"></script>
      <script src="/assets/grocery_crud/js/jquery_plugins/jquery.easing-1.3.pack.js"></script>
      <script src="/assets/grocery_crud/themes/twitter-bootstrap/js/app/twitter-bootstrap.js"></script>
      <script src="/assets/grocery_crud/themes/twitter-bootstrap/js/jquery.functions.js"></script>
      <script src="/assets/js/Chart.js"></script>

<!-- Added these 2 here to get date picker working -->
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.2/jquery-ui.js"></script>


      <script src="/assets/grocery_crud/themes/twitter-bootstrap/js/jquery.functions.js"></script>
      

 <? } ?>

      <? if (isset($script)) {
        echo '<script>'.$script.'</script>';
      }?>

<?php  if($page_title == 'Users'){ ?>
      <script>
      $(function() {
          $('.add-anchor').attr('href','/users/add_user/add');
      });
      </script>
<? } ?>
  <script src="/assets/js/gc_custom.js"></script>
  <link href="<?php echo base_url('assets/css/custom.css') ?>" rel="stylesheet">


</head>
<body role="document">