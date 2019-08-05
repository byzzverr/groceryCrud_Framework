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
   <link rel="stylesheet" rel="stylesheet" href="/assets/css/bootflat.min.css"/>
   <link rel="stylesheet" rel="stylesheet" href="/assets/css/font-awesome.css"/>
   <link rel="stylesheet" rel="stylesheet" href="/assets/css/style.css"/>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css">
  <link rel="stylesheet" href="/assets/css/jquery.scrolling-tabs.css" type="text/css">

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
      <script src="/assets/js/vue.js"></script>
    <script src="//code.jquery.com/jquery-1.12.3.js"></script>



<!-- Added these 2 here to get date picker working -->
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.2/jquery-ui.js"></script>


      <script src="/assets/grocery_crud/themes/twitter-bootstrap/js/jquery.functions.js"></script>
      <script src="/assets/js/jquery.scrolling-tabs.js"></script>
      

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
<?php if($page_title = 'Distributor Users'){ ?>
<script>
   $(function () {
       $('#distributor_id_field_box').addClass('hidden');
   });
</script>
  <? } ?>
<!-- <script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script> -->
    
<script src="/assets/js/table.js"></script>
<link href="/assets/css/table.css" rel="stylesheet">
<link href="<?php echo base_url('assets/insurapp/css/custom.css') ?>" rel="stylesheet">
<script type="text/javascript" src="jquery-1.12.0.min.js"></script>
 
<script type="text/javascript" src="<?php echo base_url();?>assets/js/dataTables.tableTools.min.js"></script>
<!-- <script type="text/javascript" src="https://cdn.datatables.net/tabletools/2.2.2/swf/copy_csv_xls_pdf.swf"></script> -->
<script type="text/javascript" src="<?php echo base_url();?>assets/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/buttons.flash.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/jszip.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/pdfmake.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/vfs_fonts.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/buttons.print.min.js"></script>
<link rel="stylesheet" rel="stylesheet" href="<?php echo base_url();?>assets/css/buttons.dataTables.min.css"/>

<link rel="stylesheet" href="<? echo base_url()?>/assets/css/bootstrap-select.min.css" />
<script src="<? echo base_url()?>/assets/js/bootstrap-select.min.js"></script>
          
<script type="text/javascript">
			var counter = 0;
			$(function(){
				$('p#add_field').click(function(){
					counter += 1;
					 $('#container').append(
					 '<label>Question No. ' + counter + '</label><input class="form-control" placeholder="Question" id="field_' + counter + '" name="dynQuestion[]' + '" type="text" /><br />'
					 +'<label>Answer Type</label><select class="form-control" placeholder="Answer Field Type" id="field_' + counter + '" name="dynAnswer[]' + '"> \
						  <option value=0>Multi Select</option> \
						  <option value=1>Input</option> \
						  <option value=2>Text</option> \
						  <option value=3>CheckBox</option> \
						</select>'
					 +'<input class="form-control" placeholder="Answer Options (Comma seperated)" id="option_' + counter + '" name="dynOption[]' + '" type="text" /><br />'
						);
				});
			});
</script>

<script>
$(document).ready(function(){
    $(document).ajaxStart(function(){
        $("#wait").css("display", "block");
    });
    $(document).ajaxComplete(function(){
        $("#wait").css("display", "none");
    });
    $(".button").click(function(){
         $("#wait").css("display", "block");
    });
});

$(function() {
    $( "#from" ).datepicker( {dateFormat: 'yy-mm-dd' } );
  }); 
    
$(function() {
    $( "#to" ).datepicker( {dateFormat: 'yy-mm-dd' } );
  });
</script>

<style type="text/css">

input[type=button], input[type=submit], input[type=reset], input[type=text] {
  /*  background-color: #4CAF50;
    border: none;
    color: white;*/
    /*padding: 16px 32px;*/
    text-decoration: none;
    margin: 4px 2px;
    cursor: pointer;
}
th{
  background-color: #f3f3f3;
}
td{
  background-color: #fafafa;
}

</style>


</head>
<body role="document">

  <div class="header">
    <img src="/assets/insurapp/img/insurapp.png" width="50px" alt-"Logo"/>
  </div>