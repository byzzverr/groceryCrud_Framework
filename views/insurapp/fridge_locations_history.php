<fieldset>
<div class="container-fluid">
<div class="page-header">
    <h3><?php echo (humanize($page_title)); ?></h3>
</div>
<form action="" method="post">
	 <table style="width:55%">
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
		      <button  class="button" >Filter</button>
		</td> 
	</table>
	<hr/>
</form>
 <!-- Google Maps -->
      <div>

        <?php echo $map['html']; ?>
      </div>

<hr/><br/>

<table id="report_table" class="display"  style="font-size:11px; width:100%;"></table>   
</div>
</fieldset>

<?php if(isset($map)) { ?>
  <?php echo $map['js']; ?>
<? } ?>