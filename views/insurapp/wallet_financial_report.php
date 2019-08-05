  
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
              <button type="submit" class="btn" style="color: #000">Filter</button>
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
      <h4>Category grouping</h4>
      <canvas class="collapseDiv mediaCharts" id="doughnutChart" width="400" height="300"></canvas>
    </div>

    <div class="chart_right financial_breakdown" style="width: 50%;">
      <h3 class="collapseDiv">Breakdown</h3>
          <p>
            &nbsp; <span style="color: green; background: green"><i class="fa fa-square"></i></span>
            <strong>R <?php echo $total ?></strong>
            Account Balance &nbsp; 
          </p>
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
      <?  foreach($categories as $key => $iw){ ?>
          <p>
          &nbsp; <span style="color: <?php echo $iw['colour'] ?>; background: <?php echo $iw['colour'] ?>"><i class="fa fa-square"></i></span>
          <strong>R <?php echo $iw['balance'] ?></strong>
          <?php echo $key ?>&nbsp; 
          </p>

      <? } ?>
      <hr/>
    </div>


</div><hr/>
<table id="report_table" class="display"  style="width:100%;"></table>   
</div>
<script>
    var dataSet = [ ];
 
    <?php  foreach($transacions as $row){?>
    dataSet.push(["<? echo $row['id'];?>",
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
foreach($categories as $iw){ 
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
