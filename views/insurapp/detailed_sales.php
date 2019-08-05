
<fieldset>

	<div class="fluid-container">
	    <div class="page-header">
	    	<h3><?php echo (humanize($page_title)); ?></h3>
		</div>

		<form action="" method="post">
			 Date From : <input id="from" class="form-input-box" type="text" name="date_from" value="<? echo $date_from;?>" required/>
			 Date To : <input id="to" class="form-input-box " type="text" name="date_to" value="<? echo $date_to;?>"  required/>

			 <button type="submit"  class="btn" style="color:black"><i class="icon-search"></i> Filter</button>
		</form>

		<hr/>

		<h4>Total</h4>
		<table width="60%">
			<tr>
				<th width="50%">Count</th>
				<th>Total</th>
			</tr>
			<tr>
				<td><?=$sales['count']?></td>
				<td>R<?=$sales['total']?></td>
			</tr>
		</table>

<? if (isset($products) && count($products) >= 1){ ?>
		<h4>Products Sales</h4>
		<table width="100%">
			<tr>
				<th width="30%">Product</th>
				<th width="30%">Count</th>
				<th>Total</th>
			</tr>
				<?

					foreach ($products as $pname => $product) {

		echo "
			<tr>
				<td>$pname</td>
				<td>".$product['count']."</td>
				<td>R".$product['total']."</td>
			</tr>
			";
					}
				?>
		</table>
<? 
}
if (isset($agencies) && count($agencies) >= 1){ ?>
		<h4>Agency Sales</h4>
		<table width="100%">
			<tr>
				<th width="30%">Agency</th>
				<th width="30%">Count</th>
				<th>Total</th>
			</tr>
				<?

					foreach ($agencies as $aname => $agency) {

		echo "
			<tr>
				<td>$aname</th>
				<td>".$agency['count']."</td>
				<td>R".$agency['total']."</td>
			</tr>
			";
					}
				?>
		</table>
<? 
}
if (isset($branches) && count($branches) >= 1){ ?>
		<h4>Branch Sales</h4>
		<table width="100%">
			<tr>
				<th width="30%">Branch</th>
				<th width="30%">Count</th>
				<th>Total</th>
			</tr>
				<?

					foreach ($branches as $bname => $branch) {

		echo "
			<tr>
				<td>$bname</td>
				<td>".$branch['count']."</td>
				<td>R".$branch['total']."</td>
			</tr>
			";
					}
				?>
		</table>

<? 
}
if (isset($agents) && count($agents) >= 1){ ?>

		<h4>Agent Sales</h4>
		<table width="100%">
			<tr>
				<th width="30%">Sales Agent</th>
				<th width="30%">Count</th>
				<th>Total</th>
			</tr>
				<?

					foreach ($agents as $agname => $agent) {

		echo "
			<tr>
				<td>$agname</td>
				<td>".$agent['count']."</td>
				<td>R".$agent['total']."</td>
			</tr>
			";
					}
				?>
		</table>
<? } ?>

		<hr/>

		<table id="report_table" class="display"></table>

	</div>
</fieldset>