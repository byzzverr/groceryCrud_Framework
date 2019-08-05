
<style>

	.alert1{
	    padding: 5px;
	    background-color: transparent;
	    color: red;
	    border: outset thin silver;
	    border-radius: 3px;
	}
	.alert2{
	    padding: 20px;
	    background-color: transparent;
	    border: outset thin silver;
	    border-radius: 3px;
	    color: black;
	}

	.closebtn{
	    margin-left: 15px;
	    color: gray;
	    font-weight: bold;
	    float: right;
	    font-size: 22px;
	    line-height: 20px;
	    cursor: pointer;
	    transition: 0.0s;

	}

	.closebtn:hover {
	    color: silver;
	}

</style>

<fieldset>
<div class="container-fluid">
<div class="page-header">
    <h3><?php echo humanize($page_title); ?></h3>
</div>


		<form action="" method="POST">
  
		  <div id="form">
		  <p style="color:red"><?php echo $error?></p>
		  

		  <table>

			<tr>
			   <th> Name</th>
			   <td> <?=$product['name'] ?></td>
			</tr>
			<tr>
			   <th> Premium</th>
			   <td class="premium"> R<?=$product['premium'] ?></td>
			</tr>

		</table>
<br/>
		  <table>

			<tr>
			   <th>Party</th>
			   <th>Entity</th>
			   <th> Percentage</th>
			   <th> Rand Value</th>
			</tr>

		  <? 
		  unset($product['split']['id'], $product['split']['product_id']);
		  	$total_rand_value = 0;
		  	$total_percentage = 0;

		  foreach ($product['split'] as $key => $value) { 

		  	$select = '';

		  	if (strpos($key, 'split') !== false) {

			  	$total_rand_value += $product['premium']*$value/100;
			  	$total_percentage += $value;
			  	if(isset($product['split'][str_replace('_split', '', $key)])){
			  		$entity = $product['split'][str_replace('_split', '', $key)];
			  		$select = '<select name="'.str_replace('_split', '', $key).'" >';
			  		foreach ($entities as $ety) {
			  			$selected = '';
			  			if($entity == $ety['id']){
			  				$selected = 'selected="selected"';
			  			}
			  			$select .= '<option value="'.$ety['id'].'" '.$selected.'>'.$ety['name'].' - '.$ety['type_name'].'</option>';
			  		}
			  		$select .= '</select>';
			  	}

		  ?>
			<tr>
			   <td><?=humanize($key)?></td>
			   <td> <?=$select?></td>
			   <td><input name="<?=$key?>" class="p_<?=$key?> percentage split" data-type="percentage" data-party="<?=$key?>" type="text" value="<? echo $value;?>" width="20px"/>%</td>
			   <td>R <input class="r_<?=$key?> rand_value split" data-type="rand_value" data-party="<?=$key?>" type="text"  value="<? echo round($product['premium']*$value/100,2);?>" width="20px"/></td>
			</tr>

		<? 
			} 
		}
		?>

			<tr class="totals">
			   <th>Totals</th>
			   <td>&nbsp;</td>
			   <td id="total_percentage"> <? echo round($total_percentage,2);?> </td>
			   <td id="total_rand_value"> R <? echo $total_rand_value;?> </td>
			</tr>
			<tr>
				<td class="errors" colspan="3" style="background-color: #fff; border:none; color:red">
					 
				</td>
			</tr>
			<tr>
				<td colspan="4" style="background-color: #fff; border:none">
					 <button class='btn save_split'>Save</button>
					 <a href="/insurapp/insurance/products" class="btn" role="button">Back</a>
				</td>
			</tr>


		   </table>
		  </div>

		 </form>
		 

</div>

</fieldset>
<script>

$( ".save_split" ).click(function() {

	if($("#total_percentage").html().replace("%","") != 100){
		$("#total_percentage").addClass('error');
		$(".errors").html('Total needs to equal 100%');
		return false;
	}

});

$( ".split" ).change(function() {
  	var premium = $( ".premium" ).html().replace("R","");
  	var changedVal = $( this ).val();
  	var type = $( this ).data('type');
  	var party = $( this ).data('party');

  	/*console.log(premium);
  	console.log(changedVal);
  	console.log(type);
  	console.log(party);*/

	switch(type) {
	    case "percentage":
	        $( ".r_"+party ).val((premium*changedVal/100).toFixed(2));
	        break;
	    case "rand_value":
	        $( ".p_"+party ).val((changedVal/premium*100).toFixed(1));
	        break;
	}

	calculate_split()
});

function calculate_split(){
	premium = $( ".premium" ).html().replace("R","");
	percentage = 0;
	rand_value = 0;

	$.each($(".percentage"), function() {
	  percentage = Number(percentage)+Number($(this).val());
	});

	$.each($(".rand_value"), function() {
	  rand_value += Number($(this).val());
	});

	if(percentage != 100){
		$("#total_percentage").addClass('error');	
	}else{
		$("#total_percentage").removeClass('error');
		$("#total_rand_value").removeClass('error');
	}

	if(rand_value.toFixed(2) != premium){
		$("#total_rand_value").addClass('error');	
	}else{
		$("#total_percentage").removeClass('error');
		$("#total_rand_value").removeClass('error');
	}

	$("#total_percentage").html(percentage + "%");
	$("#total_rand_value").html("R "+(rand_value.toFixed(2)));
}

</script>
