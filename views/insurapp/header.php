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
  <link href="/assets/css/multi-select.css" media="screen" rel="stylesheet" type="text/css">

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
      <script src="//code.jquery.com/jquery-1.12.3.js"></script>



      <!-- Added these 2 here to get date picker working -->
      <script src="//code.jquery.com/jquery-1.10.2.js"></script>
      <script src="//code.jquery.com/ui/1.11.2/jquery-ui.js"></script>

      <link rel="stylesheet" href="<? echo base_url()?>/assets/css/bootstrap-select.min.css" />
      <script src="<? echo base_url()?>/assets/js/bootstrap-select.min.js"></script>
      
      
      <script src="/assets/grocery_crud/themes/twitter-bootstrap/js/jquery.functions.js"></script>
      <script src="/assets/js/jquery.scrolling-tabs.js"></script>
      

 <? } ?>

      <? if (isset($script)) {
        echo '<script>'.$script.'</script>';
      }?>

<?php  if($page_title == 'Users' || $page_title == 'Other Users'){ ?>
      <script>
      $(function() {
          $('.add-anchor').attr('href','/users/add_user/add');
      });
      </script>
<? } ?>
<?php  if(isset($company->company_name) && $page_title = $company->company_name.' Users'){ ?>
      <script>
      $(function() {
          $('.add-anchor').attr('href','/distributors/distributor_profile/add_user/add');
      });
      </script>
<? } ?>
<?php  if(isset($company->company_name) && $page_title = $company->company_name.' Deliveries'){ ?>
<script>
  $(function () {
    $('#options-content').append('<a class="close-anchor btn" href="/distributors/distributor_logistics/create_deliveries"><i class="icon-plus"></i> Add Delivery</a>');
  });
</script>
<? } ?>
<script src="/assets/js/gc_custom.js"></script>
<?php if($page_title == 'Distributor Users'){ ?>
<script>
   $(function () {
       $('#distributor_id_field_box').addClass('hidden');
   });
</script>
  <? } ?>
<script>
  $("#field-product_id").change(function(){
     $.ajax({
        'url':'/management/get_product_price/'+$("#field-product_id").val(),
        'success':function(response){
           $("#field-price").value(response);
        }
     });
  });
</script>

<?php if(isset($data['order_id']) && $page_title = 'Items from order id: '.$data['order_id']) {?>
<script>
  $(function () {
    $('#options-content').append('<a class="close-anchor btn" href="<? echo base_url()?>/management/orders" onclick="windowClose();return false;"><i class="icon-remove"></i> Close</a>');
  });

//  function windowClose() 
//  {
//    if (confirm('Are you sure you want to close the Window? All the unsaved data will be lost'))
//    {
//      window.open('','_parent','');
//      window.close();
//    }
//  }
</script>

<? } ?>

<?php if(isset($order_id) && $page_title = 'Items from order id: '.$order_id) {?>

  <script>
    $(function () {
      $('#options-content').append('<a class="close-anchor btn" href="<? echo base_url()?>/management/orders" onclick="windowClose();return false;"><i class="icon-remove"></i> Close</a>');
    });
  </script>

<? } ?>

<?php if(isset($policy_number) && $page_title = "Insurance Dependent - Policy number ".$policy_number) { ?>
<script>
  $(function () {
    $('#options-content').append('<a class="close-anchor btn" href="<? echo base_url()?>/management/ins_applications" onclick="windowClose();return false;"><i class="icon-remove"></i> Close</a>');
  });
</script>
<? } ?>
        
<?php if(isset($company_name) && $page_title == $company_name.' Products'){ 

    $status = $this->uri->segment(4);

    if($status==1){
        $status_name = "Out of stock";
    }
    if($status==0){
        $status_name = "In stock";
    }
    
    if($status == "3"){
        $option = '<option value="">All</opion>';
    } else{
        $option  = '<option value="">'.$status_name.'</opion>';
    }

?>

<script>
  $(function () {
    $('#options-content').append('<br/> <br/>In stock/ Out of stock : <select onchange="location = this.value;" name="stock_status" id="select-out_of_stock" type="text"><? echo $option?><option value="/distributors/distributor_management/products/1" >Out of stock</opion><option value="/distributors/distributor_management/products/0">In stock</opion><option value="/distributors/distributor_management/products/3">All</opion></select>');
  });
</script>
    
<? } ?>
    
<?php if(isset($company_name) && $page_title == $company_name.' : Products'){ 
        $dist_id = $this->uri->segment(4);
        $status = $this->uri->segment(5);
   
        if($status==1){
            $status_name = "Out of stock";
        }
        if($status==0){
            $status_name = "In stock";
        }
        
        if($status == "3"){
            $option = '<option value="">All</opion>';
        } else{
            $option  = '<option value="">'.$status_name.'</opion>';
        }
   
       
?>

<script>
  $(function () {
    $('#options-content').append('<br/> <br/>In stock/ Out of stock : <select onchange="location = this.value;" name="stock_status" id="select-out_of_stock" type="text"><? echo $option?><option value="/suppliers/supplier_management/products/<? echo $dist_id;?>/1" >Out of stock</opion><option value="/suppliers/supplier_management/products/<? echo $dist_id;?>/0">In stock</opion><option value="/suppliers/supplier_management/products/<? echo $dist_id;?>/3">All</opion></select>');
  });
</script>
    
<? } ?>


    
<?php if(isset($company_name) && $page_title == $company_name.' - Products'){ 
       

        $dist_id = $this->uri->segment(3);
        $status = $this->uri->segment(4);
   
        if($status==1){
            $status_name = "Out of stock";
        }
        if($status==0){
            $status_name = "In stock";
        }
        
        if($status == "3"){
            $option = '<option value="">All</opion>';
        } else{
            $option  = '<option value="">'.$status_name.'</opion>';
        }
   
       
?>

<script>
  $(function () {
    $('#options-content').append('<br/> <br/>In stock/ Out of stock : <select onchange="location = this.value;" name="stock_status" id="select-out_of_stock" type="text"><? echo $option?><option value="/management/distributor_products/<? echo $dist_id;?>/1" >Out of stock</opion><option value="/management/distributor_products/<? echo $dist_id;?>/0">In stock</opion><option value="/management/distributor_products/<? echo $dist_id;?>/3">All</opion></select>');
  });
</script>
    
<? } ?>
<script type="text/javascript">
  
   $(function() {
    $( "#from" ).datepicker( {dateFormat: 'yy-mm-dd' } );
    }); 
    
    $(function() {
        $( "#to" ).datepicker( {dateFormat: 'yy-mm-dd' } );
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
</script>
<span id="wait" style="display:none;width:50px;height:50px;border:0px solid black;position:absolute;top:30%;left:50%;padding:2px;">
<img src='/assets/images/demo_wait.gif' width="64" height="64" /></span>

<script type="text/javascript">
$(document).ready(function() {
    $('.nav-toggle').click(function(){

      var collapse_content_selector = $('.collapseDiv');         

      var toggle_switch = $(this);
      $(collapse_content_selector).toggle(function(){
        if($('.collapseDiv').css('display')=='none'){
          toggle_switch.html('Show Charts');
        }else{
          toggle_switch.html('Hide Charts');
        }
      });
    });
    
  }); 

 
$(function() {
    $( "#date" ).datepicker( {dateFormat: 'yy-mm-dd' } );
  }); 
    
$(function() {
    $( "#date1" ).datepicker( {dateFormat: 'yy-mm-dd' } );
  });
</script>


<!---<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>--->
    
<script src="/assets/js/table.js"></script>
<link href="/assets/css/table.css" rel="stylesheet">
<link href="<?php echo base_url('assets/css/custom.css') ?>" rel="stylesheet">
    
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
</head>
<body role="document">

  <div class="header">
    <img src="/assets/img/spazapp_logo.jpg" alt-"Logo"/>
  </div>