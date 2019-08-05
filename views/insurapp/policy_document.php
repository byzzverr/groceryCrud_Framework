<!DOCTYPE html>
<html class="google mmfb" lang="en">
<meta charset="utf-8">
<meta content="initial-scale=1, minimum-scale=1, width=device-width" name="viewport">
<title><?php echo $policy['product']['type']?> BENEFIT STATEMENT</title>
<link href="//fonts.googleapis.com/css?family=RobotoDraft:300,400,500,700,italic|Product+Sans:400&amp;lang=en" rel="stylesheet">
<link type="text/css" rel="stylesheet" href="/assets/insurapp/css/basic.css" />
<script src="/assets/insurapp/css/basic.js"></script>
<div style="margin:2% 10%; text-align: center;" id="content">

<?php

$loan_amount = false;

foreach ($policy['data'] as $key => $value) {
	if($value['name'] == 'loan_term'){
		$loan_term = $value['value'];
	}
	
	if($value['name'] == 'loan_amount' && $value['value'] > 0){
		$loan_amount = $value['value'];
	}
}

unset($key);
unset($value);
$dependants_table = '<table>';
$dependants_table .= '<tr><th>First Name</th><th>Last Name</th><th>DOB</th><th>Type</th></tr>';
$dependants_table .= '<tr><td>'.$policy['first_name'].'</td><td>'.$policy['last_name'].'</td><td>'.$policy['dob'].'</td><td>Main Member</td></tr>';


if(isset($policy['dependants']['dependants'])){
	foreach ($policy['dependants']['dependants'] as $key => $value) {

		$dependants_table .= '<tr><td>'.$value['first_name'].'</td><td>'.$value['last_name'].'</td><td>'.$value['dob'].'</td><td>'.$value['type'].'</td></tr>';

	}
}

$dependants_table .= '</table>';

$array = array(
	'product_type' 				=> strtoupper($policy['product']['type']),
	'status_name' 				=> strtoupper($policy['status_name']),
	'policy_number' 			=> $policy['policy_number'],
	'premium' 					=> 'R ' . $policy['premium'],
	'loan_term'					=> $loan_term,
	'loan_amount'				=> $loan_amount,
	'product_name' 				=> $policy['product']['name'],
	'first_name' 				=> $policy['first_name'],
	'last_name' 				=> $policy['last_name'],
	'id_number' 				=> $policy['sa_id'],
	'dob' 						=> $policy['dob'],
	'postal_code' 				=> $policy['postal_code'],
	'tel_cell' 					=> $policy['tel_cell'],
	'application_date' 			=> $policy['application_date'],
	'expiry_date' 				=> $policy['expiry_date'],
	'waiting_period_date' 		=> $policy['application_date'],
	'beneficiary_name' 			=> $policy['beneficiary_name'],
	'beneficiary_id' 			=> $policy['beneficiary_sa_id'],
	'dependants_table' 			=> $dependants_table
	);

if($policy['product']['type_id'] == 1){
	$array['waiting_period_date'] .= ' + 6 months';
}else{
	$array['waiting_period_date'] = 'No waiting period applies.';
}

$terms = $policy['product']['terms']['copy'];

foreach ($array as $key => $value) {
	if($value && $value != false){
		$terms = str_replace("[$key]", $value, $terms);
	}
}

print_r($terms);

?>
</div>
<pre>
	
<?
//unset($policy['product']['terms']['copy']);
//print_r($policy);
?>
</pre>
</html>