  <div class="container">
   <div class="page-header">
     <h1><?php echo $page_title; ?></h1>
   </div>

<div class="row">

    <div class="chart_left financial_breakdown" style="width: 50%;">
      <h4>Premium Split</h4>
      <canvas class="collapseDiv mediaCharts" id="doughnutChart" width="400" height="300"></canvas>
      <h4>Breakdown</h4>
          <p>
            &nbsp; <span style="color: orange; background: orange"><i class="fa fa-square"></i></span>
            <strong>R <?php echo $credits ?></strong>
            Credits &nbsp; 
          </p>
          <p>
            &nbsp; <span style="color: yellow; background: yellow"><i class="fa fa-square"></i></span>
            <strong>R <?php echo $debits ?></strong>
            Debits &nbsp; 
          </p>

      <hr/>

          <? 
          foreach($transacions as $iw){ 
            if($iw['credit'] > 0){
              $percent = round(($iw['credit']/$credits)*100,2);
              echo '
              <p>
                &nbsp; <span style="background-color: '.$iw['colour'].'; color: '.$iw['colour'].'"><i class="fa fa-square"></i></span>
                <strong>R '.$iw['credit'].'</strong> ('.$percent.'%)
                '.$iw['name'].' &nbsp; 
              </p>
              ';


            }
          } 
          ?>

    </div>

    <div class="chart_right" style="width: 50%">
      <h4>Product Details</h4>

      <table  width="100%">
        <tr>
          <th>Sold By</th>
          <td><? echo $sales_user['name']; ?></td>
        </tr>
        <tr>
          <th>Tier</th>
          <td><? echo $sales_user['group']; ?></td>
        </tr>
        <tr>
          <th>Product</th>
          <td><? echo $product['name']; ?></td>
        </tr>
        <tr>
          <th>Premium</th>
          <td>R <? echo $application['premium']; ?></td>
        </tr>
        <tr>
          <th>Product</th>
          <td><? echo $product['name']; ?></td>
        </tr>
        <tr>
          <th>Product Details</th>
          <td><? foreach($application['data'] as $app_data){ echo $app_data['name'] .' : ' . $app_data['value'] .' <br/>'; } ?></td>
        </tr>
        <? foreach($product['split'] as $key => $split){ 
          if(strpos($key, '_split') !== FALSE){
        ?>
        <tr>
          <th><?=$key?></th>
          <td><?=$split?>%</td>
        </tr>
        <? }} ?>

        <? foreach($product['sales_split'] as $key => $split){ 
          $difference = 100/$product['split']['sales_channel_split'];
          if(in_array($key, array('agency','branch','tier_1','tier_2','tier_3','tier_4','tier_5'))){
        ?>
        <tr>
          <th><?=$key?></th>
          <td><?=$split/$difference?>%</td>
        </tr>
        <? }} ?>

      </table>
    </div>

</div><hr/>
<table id="report_table" class="display"  style="width:100%;"></table>   
</div>
<script>
    var dataSet = [ ];
 
    <?php  foreach($transacions as $row){?>
    dataSet.push(["<? echo $row['id'];?>",
                  "<? echo $row['name'];?>",
                  "<? echo $row['msisdn'];?>",
                  "<? echo $row['debit'];?>",
                  "<? echo $row['credit'];?>",
                  "<? echo $row['reference'];?>",
                  "<? echo $row['category'];?>",
                  "<? echo $row['createdate'];?>"
                  ]);


    <?php  } ?>

    $(document).ready(function() {
        $('#report_table').DataTable( {
            data:dataSet,
            columns: [
                { title: "Id"},
                { title: "Name"},
                { title: "Msisdn"},
                { title: "Debit"},
                { title: "Credit"},
                { title: "Reference"},
                { title: "Category"},
                { title: "Reference"}
            ]
        } );
    } );
</script>  
 
<script type="text/javascript">
    // ======================================================
    // Doughnut Chart
    // ======================================================

    // Doughnut Chart Options
    var doughnutOptions = {
    //Boolean - Whether we should show a stroke on each segment
    segmentShowStroke : true,
      
    //String - The colour of each segment stroke
    segmentStrokeColor : "#fff",
    
    //Number - The width of each segment stroke
    segmentStrokeWidth : 2,
    
    //The percentage of the chart that we cut out of the middle.
    percentageInnerCutout : 50,
    
    //Boolean - Whether we should animate the chart 
    animation : true,
    
    //Number - Amount of animation steps
    animationSteps : 100,
    
    //String - Animation easing effect
    animationEasing : "easeOutBounce",
    
    //Boolean - Whether we animate the rotation of the Doughnut
    animateRotate : true,

    //Boolean - Whether we animate scaling the Doughnut from the centre
    animateScale : true,
    
    //Function - Will fire on animation completion.
    onAnimationComplete : null
  }


  // Doughnut Chart Data
  var doughnutData = [
<? 
$comma = '';
foreach($transacions as $iw){ 
  if($iw['credit'] > 0){ echo $comma;?>
  {value: <?php echo $iw['credit'] ?>,color:"<?php echo $iw['colour'] ?>"}<? 
  $comma = ',';
  }
} 
?>

  ]

  //Get the context of the Doughnut Chart canvas element we want to select
  var ctx = document.getElementById("doughnutChart").getContext("2d");

  // Create the Doughnut Chart
  var mydoughnutChart = new Chart(ctx).Doughnut(doughnutData, doughnutOptions);
  
</script>
