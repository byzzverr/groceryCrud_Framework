<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html" charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">

    <title><?php echo humanize($page_title); ?></title>

    <link type="text/css" rel="stylesheet" href="/assets/css/custom.css" />
    <link type="text/css" rel="stylesheet" href="/assets/css/style.css" />
    <link href="/assets/css/datepicker.css" rel="stylesheet" type="text/css" />
  

 <?php 
 
 if(isset($css_files)){ ?>
  
  <?php foreach($css_files as $file): ?>
   <link type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />
  <?php endforeach; 
  }else{
  ?>

  <link type="text/css" rel="stylesheet" href="/assets/grocery_crud/themes/bootstrap-v4/css/bootstrap/bootstrap.min.css" />
  <link type="text/css" rel="stylesheet" href="/assets/grocery_crud/themes/bootstrap-v4/css/elusive-icons/css/elusive-icons.min.css" />
  <link type="text/css" rel="stylesheet" href="/assets/grocery_crud/themes/bootstrap-v4/css/common.css" />
  <link type="text/css" rel="stylesheet" href="/assets/grocery_crud/themes/bootstrap-v4/css/list.css" />
  <link type="text/css" rel="stylesheet" href="/assets/grocery_crud/themes/bootstrap-v4/css/general.css" />
  <link type="text/css" rel="stylesheet" href="/assets/grocery_crud/themes/bootstrap-v4/css/plugins/animate.min.css" />
  <link type="text/css" rel="stylesheet" href="/assets/grocery_crud/themes/bootstrap-v4/css/main.css" />
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.css"/>

  <?php } 
 if(isset($js_files)){ 
  ?>
  
  <?php foreach($js_files as $file): ?>
   <script src="<?php echo $file; ?>"></script>
  <?php endforeach; 

  }else{
    ?>
  <script src="/assets/grocery_crud/js/jquery-1.11.1.min.js"></script>
  <script src="/assets/grocery_crud/themes/bootstrap-v4/build/js/global-libs.min.js"></script>
  <script src="/assets/grocery_crud/themes/bootstrap-v4/js/jquery-plugins/gc-dropdown.min.js"></script>
  <script src="/assets/grocery_crud/themes/bootstrap-v4/js/jquery-plugins/gc-modal.min.js"></script>
  <script src="/assets/grocery_crud/themes/bootstrap-v4/js/jquery-plugins/bootstrap-growl.min.js"></script>
  <script src="/assets/grocery_crud/themes/bootstrap-v4/js/jquery-plugins/jquery.print-this.js"></script>
  <script src="/assets/grocery_crud/themes/bootstrap-v4/js/datagrid/gcrud.datagrid.js"></script>
  <script src="/assets/grocery_crud/themes/bootstrap-v4/js/datagrid/list.js"></script>
  <script src="/assets/js/Chart.js"></script>
  <script src="/assets/grocery_crud/themes/twitter-bootstrap/js/jquery.functions.js"></script>
  <script src="/assets/js/jquery.scrolling-tabs.js"></script>
  <script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.16/datatables.min.js"></script>

<?php } ?>

<?php if (isset($script)) {
    echo '<script>'.$script.'</script>';
}?>

<script src="/assets/js/bootstrap-3.2.0.min.js"></script>
<script src="/assets/js/bootstrap-datepicker.js"></script>
<script src="/assets/js/custom.js"></script>
<script src="/assets/js/angular.min.js"></script>
<link rel="stylesheet" href="<?php echo base_url()?>/assets/css/bootstrap-select.min.css" />
<script src="<?php echo base_url()?>/assets/js/bootstrap-select.min.js"></script>
<style type="text/css">
.navbar{
  padding: .0rem 2rem;
  
}
.navbar-inverse .navbar-nav .nav-link{
  color:#fff;
}

</style>

<!--  
<?php  if($page_title == 'Users' || $page_title == 'Other Users'){ ?>
      <script>
      $(function() {
          $('.add-anchor').attr('href','/users/add_user/add');
      });
      </script>
<?php } ?>
<?php  if(isset($company->company_name) && $page_title = $company->company_name.' Users'){ ?>
      <script>
      $(function() {
          $('.add-anchor').attr('href','/distributors/distributor_profile/add_user/add');
      });
      </script>
<?php } ?>
<?php  if(isset($company->company_name) && $page_title = $company->company_name.' Deliveries'){ ?>
<script>
  $(function () {
    $('#options-content').append('<a class="close-anchor btn" href="/distributors/distributor_logistics/create_deliveries"><i class="icon-plus"></i> Add Delivery</a>');
  });
</script>
<?php } ?>



<?php  if($page_title == 'Spazapp Customers'){ ?>
<link rel="stylesheet" href="<?php echo base_url()?>/assets/css/bootstrap-select.min.css" />
<script src="<?php echo base_url()?>/assets/js/bootstrap-select.min.js"></script>
<?php 
  $regions = $this->spazapp_model->get_all_regions(); 
  $region_id=$this->uri->segment(3);
  if(isset($region_id)){
    $region = $this->spazapp_model->get_region($this->uri->segment(3)); 
  }else{
    $region='';
  }

  if(!empty($region)){
    $selected=$region['id'];
    $selected_name=$region['name'];
  }else{
    $selected="";
    $selected_name="Select Region";
  }
?>

<script>

  // $(function () {
  //   $('#options-content').append('<select class="selectpicker" data-show-subtext="true" data-live-search="true"  data-live-search="true" onchange="location = this.value;" style="color:#f89520;"><option value="<?php echo $selected?>" style="color:#f89520"><?php echo $selected_name?></option><option style="color:#f89520;" value="0">All</option><?php foreach ($regions as $r) {?><option value="<?php echo base_url()?>management/index/<?php echo $r->id?>"><?php echo $r->name?></option><?php } ?></select>');
  // });
</script>
<?php } ?>


<?php  if(isset($company->company_name) &&  $page_title = $company->company_name.' Orders'){ ?>
<link rel="stylesheet" href="<?php echo base_url()?>/assets/css/bootstrap-select.min.css" />
<script src="<?php echo base_url()?>/assets/js/bootstrap-select.min.js"></script>
<?php 
  // $statuses = $this->spazapp_model->get_order_all_status_id(); 
  // $region_id=$this->uri->segment(3);
  // if(isset($region_id)){
  //   $status = $this->spazapp_model->get_status($this->uri->segment(4)); 
  // }else{
  //   $status='';
  // }

  // if(!empty($status)){
  //   $selected=$status['id'];
  //   $selected_name=$status['name'];
  // }else{
  //   $selected="";
  //   $selected_name="Select Status";
  // }
?>

<script>


  $(function () {
    // $('#options-content').append(' <select class="selectpicker" data-show-subtext="true" data-live-search="true"  data-live-search="true" onchange="location = this.value;" style="color:#f89520;"><option value="<?php echo $selected?>" style="color:#f89520"><?php echo $selected_name?></option><option style="color:#f89520;" value="">All</option><?php foreach ($statuses as $r) {?><option value="<?php echo base_url()?>management/distributor_orders/<?php echo $this->uri->segment(3); ?>/<?php echo $r->id?>"><?php echo $r->name?></option><?php } ?> Add Delivery</select>');
  });


</script>
<?php } ?>

<style type="text/css">
td{
  
   max-width: 150px;
    word-wrap: break-word;

}
</style>

<script src="/assets/js/gc_custom.js"></script>
<?php if($page_title == 'Distributor Users'){ ?>
<script>
   $(function () {
       $('#distributor_id_field_box').addClass('hidden');
   });
</script>
  <?php } ?>
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

<?php if(isset($data['order_id']) && $page_title == 'Items from order id: '.$data['order_id']) {?>
<script>
  $(function () {
    $('#options-content').append('<a class="close-anchor btn" href="<?php echo base_url()?>management/orders" onclick="windowClose();return false;"><i class="icon-remove"></i> Close</a>');
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

<?php } ?>



<?php if(isset($data['order_id']) && $page_title == 'Store Owner - Items from order id: '.$data['order_id']) {?>
<script>
  $(function () {
    $('#options-content').append('<a class="close-anchor btn" href="<?php echo base_url()?>storeowner/management/orders" onclick="windowClose();return false;"><i class="icon-remove"></i> Close</a>');
  });
</script>

<?php } ?>

<?php if(isset($customer_name) && $page_title == $customer_name. " Orders") {?>
<script>
  $(function () {
    $('#options-content').append('<a class="close-anchor btn" href="<?php echo base_url()?>distributors/distributor_management" onclick="windowClose();return false;"><i class="icon-remove"></i> Close</a>');
  });
</script>

<?php } ?>

<?php if(isset($order_id) && $page_title == 'Order Items from Order Id : '.$order_id) {?>

  <script>
    $(function () {
      $('#options-content').append('<a class="close-anchor btn" href="<?php echo base_url()?>distributors/distributor_management/orders" onclick="windowClose();return false;"><i class="icon-remove"></i> Close</a>');
    });
  </script>

<?php } ?>

<?php if(isset($dis_order_id) && $page_title == 'Order Items - Order Id : '.$dis_order_id) {?>

  <script>
    $(function () {
      $('#options-content').append('<a class="close-anchor btn" href="<?php echo base_url()?>management/distributor_orders/<?php echo $this->uri->segment(5)?>" onclick="windowClose();return false;"><i class="icon-remove"></i> Close</a>');
    });
  </script>

<?php } ?>

<?php if(isset($policy_number) && $page_title = "Insurance Dependent - Policy number ".$policy_number) { ?>
<script>
  $(function () {
    $('#options-content').append('<a class="close-anchor btn" href="<?php echo base_url()?>/management/ins_applications" onclick="windowClose();return false;"><i class="icon-remove"></i> Close</a>');
  });
</script>
<?php } ?>
        
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
<link rel="stylesheet" href="<?php echo base_url()?>/assets/css/bootstrap-select.min.css" />
<script src="<?php echo base_url()?>/assets/js/bootstrap-select.min.js"></script>
<script>
  $(function () {
    $('#options-content').append('<select class="selectpicker" data-show-subtext="true" data-live-search="true" onchange="location = this.value;" name="stock_status" id="select-out_of_stock" type="text"><?php echo $option?><option value="/distributors/distributor_management/products/1" >Out of stock</opion><option value="/distributors/distributor_management/products/0">In stock</opion><option value="/distributors/distributor_management/products/3">All</opion></select>');
  });
</script>
    
<?php } ?>
    
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
    $('#options-content').append('<select class="selectpicker" data-show-subtext="true" data-live-search="true" onchange="location = this.value;" name="stock_status" id="select-out_of_stock" type="text"><?php echo $option?><option value="/suppliers/supplier_management/products/<?php echo $dist_id;?>/1" >Out of stock</opion><option value="/suppliers/supplier_management/products/<?php echo $dist_id;?>/0">In stock</opion><option value="/suppliers/supplier_management/products/<?php echo $dist_id;?>/3">All</opion></select>');
  });
</script>
    
<?php } ?>

<link rel="stylesheet" href="<?php echo base_url()?>/assets/css/bootstrap-select.min.css" />
<script src="<?php echo base_url()?>/assets/js/bootstrap-select.min.js"></script>
    
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
    $('#options-content').append('<select class="selectpicker" data-show-subtext="true" data-live-search="true" onchange="location = this.value;" name="stock_status" id="select-out_of_stock" type="text"><?php echo $option?><option value="/management/distributor_products/<?php echo $dist_id;?>/1" >Out of stock</opion><option value="/management/distributor_products/<?php echo $dist_id;?>/0">In stock</opion><option value="/management/distributor_products/<?php echo $dist_id;?>/3">All</opion></select>');
  });
</script>
    
<?php } ?>

<script type="text/javascript">
  $(function () {
   $('#options-content').append(' <a onclick="history.go(-1)" class="btn" style="color: #000"><i class="  icon-chevron-left"> </i>Back</a>');
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

</script>

<script type="text/javascript">
             $(document).ready(function() {
                       $("#search_field").attr('selectedIndex', 1);  
             });           
             
             $( "#filtercriteria" ).change(function() {
                    $( "#search_text" ).attr('value',$( "#filtercriteria option:selected" ).text());
                    $('#crud_search').trigger('click');
             });                
                
                        
</script>

<script type='text/javascript'>
        $(document).ready(function () {
            function exportTableToCSV($table, filename) {
                var $headers = $table.find('tr:has(th)')
                    ,$rows = $table.find('tr:has(td)')

                    // Temporary delimiter characters unlikely to be typed by keyboard
                    // This is to avoid accidentally splitting the actual contents
                    ,tmpColDelim = String.fromCharCode(11) // vertical tab character
                    ,tmpRowDelim = String.fromCharCode(0) // null character

                    // actual delimiter characters for CSV format
                    ,colDelim = '","'
                    ,rowDelim = '"\r\n"';

                    // Grab text from table into CSV formatted string
                    var csv = '"';
                    csv += formatRows($headers.map(grabRow));
                    csv += rowDelim;
                    csv += formatRows($rows.map(grabRow)) + '"';

                    // Data URI
                    var csvData = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv);

                // For IE (tested 10+)
                if (window.navigator.msSaveOrOpenBlob) {
                    var blob = new Blob([decodeURIComponent(encodeURI(csv))], {
                        type: "text/csv;charset=utf-8;"
                    });
                    navigator.msSaveBlob(blob, filename);
                } else {
                    $(this)
                        .attr({
                            'download': filename
                            ,'href': csvData
                            //,'target' : '_blank' //if you want it to open in a new window
                    });
                }

                //------------------------------------------------------------
                // Helper Functions 
                //------------------------------------------------------------
                // Format the output so it has the appropriate delimiters
                function formatRows(rows){
                    return rows.get().join(tmpRowDelim)
                        .split(tmpRowDelim).join(rowDelim)
                        .split(tmpColDelim).join(colDelim);
                }
                // Grab and format a row from the table
                function grabRow(i,row){
                     
                    var $row = $(row);
                    //for some reason $cols = $row.find('td') || $row.find('th') won't work...
                    var $cols = $row.find('td'); 
                    if(!$cols.length) $cols = $row.find('th');  

                    return $cols.map(grabCol)
                                .get().join(tmpColDelim);
                }
                // Grab and format a column from the table 
                function grabCol(j,col){
                    var $col = $(col),
                        $text = $col.text();

                    return $text.replace('"', '""'); // escape double quotes

                }
            }


            // This must be a hyperlink
            $("#export").click(function (event) {
                // var outputFile = 'export'
                var outputFile ="<?php echo $page_title?>";
                outputFile = outputFile.replace('.csv','') + '.csv'
                 
                // CSV
                exportTableToCSV.apply(this, [$('#dvData > table'), outputFile]);
                
                // IF CSV, don't do event.preventDefault() or return false
                // We actually need this to be a typical hyperlink
            });
        });
    </script>
<script type="text/javascript">
function printDiv(divName) {
     var printContents = document.getElementById(divName).innerHTML;
     var originalContents = document.body.innerHTML;

     document.body.innerHTML = printContents;

     window.print();

     document.body.innerHTML = originalContents;
}

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

table{
  width: 99%;
}

</style>
-->
<body role="document">

  <div class="header">
    <img src="/assets/<?=$app_settings['app_name']?>/img/logo.png" alt="Logo"/>
  </div>