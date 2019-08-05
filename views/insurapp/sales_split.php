
<style>

	#form td input{
		width:40px !important;
	}
	.percentage {
	    padding: 5px;
	    background-color: transparent;
	    color: red;
	    border: outset thin silver;
	    border-radius: 3px;
	}

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
<div class="container" id="app">
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
			   <td class="premium"> R {{ premium }}</td>
			</tr>
			<tr>
			   <th> Agency Total</th>
			   <td class="agency_total"> R {{ agency_total }}</td>
			</tr>

		</table>
<br/>
		  <table>

			<tr>
			   <th style="width:15%;">Party</th>
			   <th> Percentage</th>
			   <th> Rand Value</th>
			   <th> Tier 1</th>
			   <th> Tier 2</th>
			   <th> Tier 3</th>
			   <th> Tier 4</th>
			   <th> Tier 5</th>
			</tr>

		  <? 
		  unset($product['sales_split']['id'], $product['sales_split']['product_id'], $product['sales_split']['agency_id']);
		  	$total_rand_value = 0;
		  	$total_percentage = 0;
		  	$percentage = 'percentage_sub';
		  	$rand_value = 'rand_value_sub';

		  foreach ($product['sales_split'] as $key => $value) { 


		  	if(in_array($key, array('agency','branch','tier_1'))){
		  		$total_rand_value += $agency_total*$value/100;
		  		$total_percentage += $value;
		  		$percentage = 'percentage';
		  		$rand_value = 'rand_value';
		  	}else{
		  		$percentage = 'percentage_sub';
		  		$rand_value = 'rand_value_sub';
		  	}

		  ?>
			<tr>
			   <td><?=humanize($key)?></td>
			   <td> <input name="<?=$key?>" class="p_<?=$key?> <?=$percentage?> split" data-type="percentage" data-party="<?=$key?>" type="text" v-model="<?=$key?>" value="<? echo $value;?>" width="20px"/>% </td>
			   <td>R <input class="r_<?=$key?> <?=$rand_value?> split" data-type="<?=$rand_value?>" data-party="<?=$key?>" type="text" value="<? echo round($agency_total*$value/100,2);?>" width="20px"/></td>
			   <td>{{ <?=$key?>_tier_1 }} %</td>
			   <td>{{ <?=$key?>_tier_2 }} %</td>
			   <td>{{ <?=$key?>_tier_3 }} %</td>
			   <td>{{ <?=$key?>_tier_4 }} %</td>
			   <td>{{ <?=$key?>_tier_5 }} %</td>
			</tr>

		<? } 
			$total_percentage = round($total_percentage,2);
			$total_rand_value = number_format(round($total_rand_value,2));
		?>

			<tr class="totals">
			   <th>Totals</th>
			   <td id="total_percentage"> {{ total_percentage }} % </td>
			   <td id="total_rand_value"> R {{ total_rand_value }} </td>
			   <td></td>
			   <td></td>
			   <td></td>
			   <td></td>
			   <td></td>
			</tr>
			<tr>
				<td class="errors" colspan="9" style="background-color: #fff; border:none; color:red">
					 
				</td>
			</tr>
			<tr>
				<td colspan="9" style="background-color: #fff; border:none">
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


var app = new Vue({
  el: '#app',
  data: {
    total_percentage: <?=$total_percentage?>,
<? foreach ($product['sales_split'] as $key => $value) {  
    echo "    $key: $value,\n";
	foreach ($product['sales_split'] as $key2 => $value2) { 
		echo "    ".$key."_$key2: 0,\n";
	}
  } ?>
    premium: '<?=$product["premium"]?>',
    agency_total: '<?=$agency_total?>',
    total_rand_value: '<?=$total_rand_value?>'
	}
});



$( ".save_split" ).click(function() {

	if($("#total_percentage").html().replace("%","") != 100){
		$("#total_percentage").addClass('error');
		$(".errors").html('Total needs to equal 100%');
		return false;
	}

});

$( ".split" ).change(function() {
  	var premium = app.premium;
  	var agency_total = app.agency_total;
  	var changedVal = $( this ).val();
  	var type = $( this ).data('type');
  	var party = $( this ).data('party');


	switch(type) {
	    case "percentage":
	        $( ".r_"+party ).val((agency_total*changedVal/100).toFixed(2));
	        break;
	    case "rand_value":
	        $( ".p_"+party ).val((changedVal/agency_total*100).toFixed(1));
	        break;
	}

	switch(party){
		case "tier_2":
		if(changedVal > app.tier_1){
			$(".p_tier_2").parent('td').addClass('error');
		}else{
			$(".p_tier_2").parent('td').removeClass('error');
		}
		break;

	}

calculate_split();
	
});

$( document ).ready(function() {
    calculate_split()
});

function calculate_split(){
	premium = app.premium;
  	agency_total = app.agency_total;
	percentage = 0;
	rand_value = 0;

	app.agency_tier_1 = app.agency;
	app.agency_tier_2 = app.agency;
	app.agency_tier_3 = app.agency;
	app.agency_tier_4 = app.agency;
	app.agency_tier_5 = app.agency;

	app.branch_tier_1 = app.branch;
	app.branch_tier_2 = app.branch;
	app.branch_tier_3 = app.branch;
	app.branch_tier_4 = app.branch;
	app.branch_tier_5 = app.branch;

	app.tier_1_tier_1 = app.tier_1;
	app.tier_2_tier_2 = app.tier_2;
	app.tier_3_tier_3 = app.tier_3;
	app.tier_4_tier_4 = app.tier_4;
	app.tier_5_tier_5 = app.tier_5;

	app.tier_4_tier_5 = app.tier_4-app.tier_5;
	app.tier_3_tier_5 = app.tier_3_tier_4 = app.tier_3-app.tier_4;
	app.tier_2_tier_5 = app.tier_2_tier_4 = app.tier_2_tier_3 = app.tier_2-app.tier_3;
	app.tier_1_tier_5 = app.tier_1_tier_4 = app.tier_1_tier_3 = app.tier_1_tier_2 = app.tier_1-app.tier_2;


	if(app.tier_4 < app.tier_5){
		$(".p_tier_5").parent('td').addClass('error');
	}else{
		$(".p_tier_5").parent('td').removeClass('error');
	}
	
	if(app.tier_3 < app.tier_4){
		$(".p_tier_4").parent('td').addClass('error');
		$(".p_tier_5").parent('td').addClass('error');
	}else{
		$(".p_tier_4").parent('td').removeClass('error');
		$(".p_tier_5").parent('td').removeClass('error');
	}

	if(app.tier_2 < app.tier_3){
		$(".p_tier_3").parent('td').addClass('error');
		$(".p_tier_4").parent('td').addClass('error');
		$(".p_tier_5").parent('td').addClass('error');
	}else{
		$(".p_tier_3").parent('td').removeClass('error');
		$(".p_tier_4").parent('td').removeClass('error');
		$(".p_tier_5").parent('td').removeClass('error');
	}

	if(app.tier_1 < app.tier_2){
		$(".p_tier_2").parent('td').addClass('error');
		$(".p_tier_3").parent('td').addClass('error');
		$(".p_tier_4").parent('td').addClass('error');
		$(".p_tier_5").parent('td').addClass('error');
	}else{
		$(".p_tier_2").parent('td').removeClass('error');
		$(".p_tier_3").parent('td').removeClass('error');
		$(".p_tier_4").parent('td').removeClass('error');
		$(".p_tier_5").parent('td').removeClass('error');
	}

	var i;
	for (i = 1; i < 6; ++i) {
	}


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

	if(rand_value.toFixed(2) != agency_total){
		$("#total_rand_value").addClass('error');	
	}else{
		$("#total_percentage").removeClass('error');
		$("#total_rand_value").removeClass('error');
	}

	app.total_percentage = percentage;
	app.total_rand_value = rand_value.toFixed(2);
}

</script>
