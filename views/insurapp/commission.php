
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
				<th><?=$sales['count']?></th>
				<th>R<?=$sales['total']?></th>
			</tr>
		</table>

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
				<th>$pname</th>
				<th>".$product['count']."</th>
				<th>R".$product['total']."</th>
			</tr>
			";
					}
				?>
		</table>

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
				<th>$aname</th>
				<th>".$agency['count']."</th>
				<th>R".$agency['total']."</th>
			</tr>
			";
					}
				?>
		</table>

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
				<th>$bname</th>
				<th>".$branch['count']."</th>
				<th>R".$branch['total']."</th>
			</tr>
			";
					}
				?>
		</table>

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
				<th>$agname</th>
				<th>".$agent['count']."</th>
				<th>R".$agent['total']."</th>
			</tr>
			";
					}
				?>
		</table>

		<hr/>

		<table id="report_table" class="display"></table>

	</div>
</fieldset>