<script>
 $(function() {
    $( ".date" ).datepicker( {dateFormat: 'yy-mm-dd' } );
  }); 
</script>

<?
$policy_number  = '';
$initial        = '';
$tel_home       = '';
$tel_work       = '';
$bank_name      = '';
$bank_code      = '';
$acc_number     = '';
$acc_type       = '';
$account_holder = '';
$deduction_day  = '';
$occupation     = '';
$deduction_day  = '';
$employer       = '';
$employment_date= '';

foreach($ins_results_2 as $row){
    
$policy_number  = $row->policy_number;
$title          = $row->title;
$initial        = $row->initial;
$gender         = $row->gender;
$marital_status = $row->marital_status;
$tel_home       = $row->tel_home;
$tel_work       = $row->tel_work;
$bank_name      = $row->bank_name;
$bank_code      = $row->bank_code;
$acc_number     = $row->acc_number;
$acc_type       = $row->acc_type;
$account_holder = $row->account_holder;
$deduction_day  = $row->deduction_day;
$occupation     = $row->occupation;
$deduction_day  = $row->deduction_day;
$employer       = $row->employer;
$employment_date= $row->employment_date;
    
}
?>

<div class="form-group">
<label class="col-md-4 control-label" for="ben_name">Policy Number</label>  
  <div class="col-md-4">
  <input id="policy_number" name="policy_number" value="<? echo $policy_number;?>"  type="text" placeholder="Policy Number" class="form-control input-md" readonly>
    
  </div>
</div>

<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="name">Title</label>  
  <div class="col-md-4">
  <select id="title">
      <option><? echo $title;?> </option>
      <option>Mr</option>
      <option>Mrs</option>
      </select>
  </div>
</div>

<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="surname">Initial</label>  
  <div class="col-md-4">
  <input id="initial" name="initial" type="text" value="<? echo $initial;?>"  placeholder="initial" class="form-control input-md">
    
  </div>
</div>   
<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="marital_status">Marital status</label>  
  <div class="col-md-4">
  <select id="marital_status">
      <option><? echo $marital_status;?> </option>
      <option>Single</option>
      <option>Married</option>
      <option>Devorced</option>
      </select>
  </div>
</div>   
<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="surname">Gender</label>  
  <div class="col-md-4">
      <select id="gender">
      <option><? echo $gender;?> </option>
      <option>Female</option>
      <option>Male</option>
      </select>
  </div>
</div>   

<div class="form-group">
  <label class="col-md-4 control-label" for="date_of_birth">Tel home</label>  
  <div class="col-md-4">
  <input id="tel_home" name="tel_home" type='text'  value="<? echo $tel_home;?>"   placeholder="Tel home" class="form-control input-md">
    
  </div>
</div>

 <!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="id">Tel work</label>  
  <div class="col-md-4">
  <input id="tel_work" name="tel_work" type="text" value="<? echo $tel_work;?>" placeholder="Tel  work" class="form-control input-md">
    
  </div>
</div>
            
<div class="form-group">
  <label class="col-md-4 control-label" for="passport_number">Bank name</label>  
  <div class="col-md-4">
  <input id="bank_name" name="bank_name" type="text"  value="<? echo $bank_name;?>" placeholder="Bank name" class="form-control input-md">
    
  </div>
</div>
<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="bank_code">Bank code</label>  
  <div class="col-md-4">
  <input id="bank_code" name="bank_code" type="text"  value="<? echo $bank_code;?>"  placeholder="Bank code" class="form-control input-md">
    
  </div>
</div>
<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="email">Account number</label>  
  <div class="col-md-4">
  <input id="acc_number" name="acc_number" type="email" value="<? echo $acc_number;?>"  placeholder="Account number" class="form-control input-md">
    
  </div>
</div>

<div class="form-group">
  <label class="col-md-4 control-label" for="postal_code">Account type</label>  
  <div class="col-md-4">
  <input id="acc_type" name="acc_type" type="text" value="<? echo $acc_type;?>"  placeholder="Account type" class="form-control input-md">
    
  </div>
</div>

<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="account_holder">Account holder</label>  
  <div class="col-md-4">
  <input id="account_holder" name="account_holder" type="text"  value="<? echo $account_holder;?>"  placeholder="Account holder" class="form-control input-md">
    
  </div>
</div>
    
            <!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="ben_name">Deduction day</label>  
  <div class="col-md-4">
  <input id="deduction_day" name="deduction_day" type="text"  value="<? echo $deduction_day;?>"  placeholder="Deduction day" class="form-control input-md">
  </div>
</div>
    
<div class="form-group">
  <label class="col-md-4 control-label" for="plan">Occupation</label>  
  <div class="col-md-4">
  <input id="occupation" name="occupation" type="text" value="<? echo $occupation;?>" placeholder="Occupation" class="form-control input-md">
    
  </div>
</div>

<div class="form-group">
  <label class="col-md-4 control-label" for="employer">Employer</label>  
  <div class="col-md-4">
  <input id="employer" name="employer" type="text"  value="<? echo $employer;?>"  placeholder="Employer" class="form-control input-md">
    
  </div>
</div>

<div class="form-group">
  <label class="col-md-4 control-label" for="employer">Employment date</label>  
  <div class="col-md-4">
  <input id="employment_date" name="employment_date" type="text"   value="<? echo $employment_date;?>"  placeholder="Employment date" class="date">
    
  </div>
</div>

            
 <div class="form-group">
  <label class="col-md-4 control-label" for="singlebutton"></label>
  <div class="col-md-4">
      <!--onclick="post_application_1();"--> 
    <button id="singlebutton" name="" onclick="post_application_3();" class="btn btn-primary">Next</button>
  </div>
</div>
  