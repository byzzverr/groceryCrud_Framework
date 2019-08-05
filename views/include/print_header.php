<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html" charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">

   <title><?php echo humanize($page_title); ?></title>

  <link rel='stylesheet' type='text/css' href='/assets/print/css/style.css' />
  <link rel='stylesheet' type='text/css' href='/assets/print/css/print.css' media="print" />
  <!--<script type='text/javascript' src='/assets/print/js/jquery-1.3.2.min.js'></script> -->
  <!--<script type='text/javascript' src='/assets/print/js/example.js'></script> -->


  <style type="text/css">

      body {
        background: rgb(204,204,204); 
      }
      page[size="A4"] {
        background: white;
        width: 21cm;
        display: block;
        margin: 0 auto;
        margin-bottom: 0.5cm;
        box-shadow: 0 0 0.5cm rgba(0,0,0,0.5);
      }
      @media print {
        body, page[size="A4"] {
          margin: 0;
          box-shadow: 0;
        }
      }

  </style>

  
</head>
<body role="document">
  <page size="A4">

    <div class="header">
        <img src="/assets/img/spazapp_logo.jpg" alt-"Logo"/>
    </div>