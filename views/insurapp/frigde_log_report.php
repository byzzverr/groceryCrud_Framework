<fieldset>
<div class="container-fluid">
<div class="page-header">
    <h3><?php echo humanize($page_title); ?> </h3>
</div>
   
<div class="row">

<table style="background-color: #f9f9f9">
<tr>
<form action="" method="POST">
	<td style="border:none" >
	Province : <select name='province'>
	<option value="<?php echo $province['id']?>" ><?php echo $province['name']?></option>
	<?php echo $province_option ?></select>
	</td>

	<td style="border:none" >Frigde Type : <br/><select name="fridge_type">
	<option value="<?php echo $fridge_type['id']?>" ><?php echo $fridge_type['name']?></option>
	<?php echo $fridge_type_option ?></select>
	</td>

	<td style="border:none" colspan="2" ><br><button  style="color:#000" class="btn">Filter </button></td>
</form>
 <form method="post" action="/dashboard/download_csv">
	<td style="border:none" >
		<? if(isset($query) && $query != ''){ ?>
    
          
              <input name="query" type="hidden" value="<? echo $query; ?>"><br>
              <!-- <input type="image" src="/assets/img/csv_export.jpg" height="30px" class="" /> -->
              <input type="submit" value="Download CSV File" style="color:#000" class="btn"/>
           
        
        <? } ?>
	</td>
</form>
</tr>
<tr><td colspan="5" style="background-color: #f9f9f9;border:none"><hr/></td></tr>

<form action="" method="POST">

<tr>
		<td style="border:none;width: 10%; background-color: #f9f9f9;" >Fridge Unit Code </td>
		<td style="border:none; width: 15%; background-color: #f9f9f9;" > 
		<input type="search" name="fridge_uinit_code" value="<? echo $unit_code?>">
		</td>
		<td style="border:none; background-color: #f9f9f9;" colspan="2"><button  style="color:#000" class="btn">Search </button></td>
</tr>
</table>
</form>

		 <div>
            <!-- <canvas id="canvas" height="500" width="850"></canvas> -->
        </div>
       
</div>
<hr/><br/>

<table id="report_table" class="display"  style="font-size:11px; width:100%;"></table>   
</div>
</fieldset>
