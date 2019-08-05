<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $page_title; ?></title>
<?php 
foreach($css_files as $file): ?>
  <link type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />
<?php endforeach; ?>
<link type="text/css" rel="stylesheet" href="/assets/css/custom.css" />
<?php foreach($js_files as $file): ?>
  <script src="<?php echo $file; ?>"></script>
<?php endforeach; ?>
</head>
<body>
  <header class="header">
    <img src="/assets/<?=$app_settings['app_name']?>/img/logo.png" alt="Logo"/>
  </header>