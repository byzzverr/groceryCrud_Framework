<fieldset>
<div id="responseresults">
  
<div class="container-fluid">
<div class="page-header">
    <h3><?php echo humanize($page_title); ?></h3>
</div>
<table style="width:90%">
<form action="" method="post">
	
		<td style="border:none; background-color: #f9f9f9">
		    From 
		</td>   
		<td style="border:none; background-color: #f9f9f9">
		   : <input  type="text" name="date_from" id="from" class="date" value="<? echo $date_from;?>" placeholder="Select start date">
		</td>    
		<td style="border:none; background-color: #f9f9f9">
		    To 
		</td> 

		<td style="border:none; background-color: #f9f9f9">
		     : <input  type="text" name="date_to" id="to" class="date" value="<? echo $date_to;?>" placeholder="Select end date">
		</td> 

		<td style="border:none; background-color: #f9f9f9">
		      <button style="color:#000" class="btn" >Filter</button>
		</td> 
	

</form>
<form method="post" action="/dashboard/download_csv">
    <td style="border:none; background-color: #f9f9f9" >
        <? if(isset($query) && $query != ''){ ?>
    
          
              <input name="query" type="hidden" value="<? echo $query; ?>">
              <!-- <input type="image" src="/assets/img/csv_export.jpg" height="30px" class="" /> -->
              <input type="submit" value="Download CSV File" style="color:#000" class="btn"/>
           
        
        <? } ?>
    </td>
</form>
</table>
<hr/>
 <div class="row"> 
 <div class="chart_left" style="width: 50%;">
 	 <h4>Daily Temperature</h4> <br/>          
    <canvas id="canvas" height="300" width="500"></canvas>  

 </div>
 <div class="chart_right" style="width: 40%;">
 	 <h4>Monthly Temperature</h4> <br/>          
    <canvas id="canvas2" height="300" width="500"></canvas>  

 </div>
</div><hr/>
<table id="report_table" class="display" width="100%"></table>
</div>

</div>

</fieldset>

<?php
 	$comma ='';
    $values1 ='';
    $labels1 ='';
    $dataset='';
    $barColors='';
    $count=0;
	foreach ($fridge as $row) {   

            $max_temp = $row['expected_temp']+$row['tolerance'];
            $min_temp = $row['expected_temp']-$row['tolerance'];
            $off_temp = $row['considered_off'];

            $color = '#27ade5';

            if($row['temp'] > $max_temp){
                $color = '#d49c2d';
               
            }

            if($row['temp'] < $min_temp){
                $color = '#d49c2d';
                $stat = 'Too Cold';
            }

            if($row['temp'] > $off_temp){
                $color = '#d22c2c';
                $stat = 'Fridge is Off';
            }
            $barColors.='myObjBar.datasets[0].bars['.$count.'].fillColor = "'.$color.'";';

           $labels1.= $comma.'"'.(date("M Y", strtotime($row['createdate']))).'"';
            
            $values1.=$comma.$row['temp'];

            $comma = ',';
          $count++;
      }
    

?>
<script type="text/javascript">
	
	var barChartData1 = {
            labels: [<?php echo $labels1?>],
           datasets: [{data: [<?php echo $values1?>]}]
          
        };
       
        var options = {
            scaleBeginAtZero: false,
            responsive: true,
            scaleStartValue : -50 
        };
   
        var ctx = document.getElementById("canvas2").getContext("2d");
        window.myObjBar = new Chart(ctx).Bar(barChartData1,options, {responsive : true});

        //nuevos colores
       <?php echo $barColors?>
        myObjBar.update();
  
        
</script>

