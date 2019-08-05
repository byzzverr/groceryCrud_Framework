  
  <div class="container">
   <div class="page-header">
     <h1><?php echo humanize($page_title); ?></h1>

     <div class="dashboard-list filter">
     <div class="jumbotron title page">
     <table>
      <tr>
        <td style="border:none" valign="middle">
          <form class="inline-form" method="post">
              From:
              <input id="datepicker" class="form-input-box" type="text" name="date_from" value="<? echo $date_from;?>" />
              To: 
              <input id="datepicker1" class="form-input-box" type="text" name="date_to" value="<? echo $date_to;?>" />
              <button type="submit" class="date-filter">Filter</button>
          </form>
        </td>

      </tr>
     </table>
      <span style="color:#12aa27" id="load"></span>
   </div>
   </div>

   </div>

<div class="row">

    <div class="chart_left" style="width: 50%">
      <h4>Platform Wallets</h4>
      <canvas class="collapseDiv mediaCharts" id="doughnutChart" width="400" height="300"></canvas>
    </div>

    <div class="chart_right financial_breakdown" style="width: 50%;">
      <h3 class="collapseDiv">Breakdown</h3>

      <?  foreach($platform_wallets as $iw){ ?>
          <p>
          &nbsp; <span style="color: <?php echo $iw['colour'] ?>; background: <?php echo $iw['colour'] ?>"><i class="fa fa-square"></i></span>
          <strong>R <?php echo $iw['balance'] ?></strong>
          <?php echo $iw['name'] ?>&nbsp; 
          </p>


      <? } ?>
      <hr/>
          <p>
            &nbsp; <span style="color: #F6A139; background: #F6A139"><i class="fa fa-square"></i></span>
            <strong>R <?php echo $platform_wallet_total ?></strong>
            Platform wallet total&nbsp; 
          </p>
          <p>
            &nbsp; <span style="color: #88BD3C; background: #88BD3C"><i class="fa fa-square"></i></span>
            <strong>R <?php echo $wallet_total ?></strong>
            User wallet total&nbsp; 
          </p>
          <p>
            &nbsp; <span style="color: #000; background: #000"><i class="fa fa-square"></i></span>
            <strong>R <?php echo number_format($grand_total) ?></strong>
            Grand total &nbsp; 
          </p>
    </div>


</div><hr/>
<table id="report_table" class="display"  style="font-size:11px; width:100%;"></table>   
</div>
<?
/*
<script>
    var dataSet = [ ];
 
    <?php  foreach($query_results as $row){?>
    dataSet.push(["<? echo $row['id'];?>",
                  "<? echo $row['name'];?>",
                  "<? echo $row['ip_address'];?>",
                  "<? echo $row['last_activity'];?>",
                  "<? echo $row['event_label'];?>"
                  ]);


    <?php  } ?>

    $(document).ready(function() {
        $('#report_table').DataTable( {
            data:dataSet,
            columns: [
                { title: "Id"},
                { title: "Name"},
                { title: "Ip Address"},
                { title: "Last Activity"},
                { title: "Event Label"}
            ]
        } );
    } );
</script>  
*/
?>
 
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
foreach($platform_wallets as $iw){ 
  if($iw['balance'] > 0){ echo $comma;?>
  {value: <?php echo $iw['balance'] ?>,color:"<?php echo $iw['colour'] ?>"}<? 
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
