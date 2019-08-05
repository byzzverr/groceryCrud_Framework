

<fieldset>   
  <div id="responsecontainer" class="container">
    <div class="page-header">
        <h3><?php echo strtoupper(humanize($page_title)); ?></h3>
    </div>   
         <table id="report_table" class="display"  style=""></table>
  
  </div>
</fieldset>

<script type="text/javascript">

var dataSet = [ ];
    
<?php 
  $count = 1; foreach($wallets as $row){
  $user_id       = $row['user_id'];
  $name          = $row['name'];
  $cellphone     = $row['cellphone'];                                                         
  $balance       = $row['balance'];

  ?>
  dataSet.push(["<? echo $user_id;?>","<a href='/insurapp/financial/user_wallet/<? echo $user_id?>'><? echo $name;?></a>","<? echo $cellphone;?>","<? echo $balance;?>"]);

  <?php
  $count++; 
}
?>

$(document).ready(function() {

$('#report_table').DataTable( {
    data:dataSet,
    columns: [
        { title: "User Id" },
        { title: "Name" },
        { title: "Cellphone" },
        { title: "Balance" }
    ],dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
} );
} );

</script>