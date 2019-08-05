<fieldset>
<div class="container-fluid">
<div class="page-header">
    <h3><?php echo humanize($page_title  ); ?> </h3>
</div>

<table style="background-color:transparent;">

  <tr>
      <td style="background-color:#e4e4e4;border:none;"><a><b>Fridge Information</b></a></td>
      <? if(isset($query) && $query != ''){ ?>
      <form method="post" action="/dashboard/download_csv">
      <td style="width: 18%; background-color:#e4e4e4; border:none;">
      <input name="query" type="hidden" value="<? echo $query; ?>">
      <input type="submit" value="Download CSV File" class="csv-download" />   
      </td>            
      </form>
      <? } ?>
  </tr>

  <tr>
     <td style="width: 18%; background-color:#f9f9f9;"><b>Location Name</b></td>
     <td style="background-color:#f9f9f9;"> : <? echo $fridge['location_name']?></td>
  </tr>

  <tr>
     <td style="background-color:#f9f9f9;"><b>Fridge Type</b></td>
     <td style="background-color:#f9f9f9;"> : <? echo $fridge['fridge_type']?></td>
  </tr>

  <tr>
     <td style="background-color:#f9f9f9;"><b>Province</b></td>
     <td style="background-color:#f9f9f9;"> : <? echo $fridge['province']?></td>
  </tr>

  <tr>
     <td style="background-color:#f9f9f9;"><b>Region</b></td>
     <td style="background-color:#f9f9f9;"> : <? echo $fridge['region']?></td>
  </tr>

  <tr>
     <td style="background-color:#f9f9f9;"><b>Street</b></td> 
     <td style="background-color:#f9f9f9;"> : <? echo $fridge['street']?></td>
  </tr>

  <tr>
     <td style="background-color:#f9f9f9;"><b>Temperature</b></td>
     <td style="background-color:#f9f9f9;"> : <? echo $fridge['temp']?>&deg; [<? echo $status?>]</td>
  </tr>

</table>

<div class="row">
		  <div class="chart_left" style="width: 55%; border:outset solid gray;">
		  <a><h4>Daily Temperature  </h4></a><hr/>
          <canvas id="canvas" height="300" width="450"></canvas>
      </div>

      <div class="chart_right"  style="width: 40%; border:outset solid gray;">
        <a><h4>Current Location </h4></a><hr>
      <div>

      <?php echo $map['html']; ?>
     	</div>

  		<?php if(isset($map)) { ?>
  		  <?php echo $map['js']; ?>
  		<? } ?>   
</div>
<hr/><br/>
<table id="report_table" class="display"  style="font-size:11px; width:100%;"></table>   
</div>
</fieldset>
