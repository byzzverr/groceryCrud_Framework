<script>
 $(function() {
    $( ".date" ).datepicker( {dateFormat: 'yy-mm-dd' } );
  }); 
</script>
<?
    $policy_number          = "";
    $relation_first_name    = "";
    $relation_surname       = "";
    $relation_type          = "";
    $relation_date_of_birth = "";
    $cover_level            = "";

foreach($ins_results_1 as $row){
    $policy_number          = $row->policy_number;
    $relation_first_name    = $row->relation_first_name;
    $relation_surname       = $row->relation_surname;
    $relation_type          = $row->relation_type;
    $relation_date_of_birth = $row->relation_date_of_birth;
    $cover_level            = $row->cover_level;
    
 }
?>
 <div class="form-group">
<label class="col-md-4 control-label" for="ben_name">Policy Number</label>  
  <div class="col-md-4">
  <input id="policy_number" name="policy_number" value="<? echo $_POST['policy_number'];?>"  type="text" placeholder="Policy Number" class="form-control input-md" readonly>
    
  </div>
</div>

<!-- Select Basic -->
<div class="form-group">
  <label class="col-md-4 control-label" for="address_type">Relation First Name</label>
  <div class="col-md-4">
     <input id="relation_first_name" name="relation_first_name" type="text"  value="<? echo $relation_first_name;?>" placeholder="Relation First Name" class="form-control input-md">
  </div>
</div>

    
<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="postal_code">Relation Surname</label>  
  <div class="col-md-4">
  <input id="relation_surname" name="relation_surname" type="text"  value="<? echo $relation_surname;?>"  placeholder="Relation Surname" class="form-control input-md">
    
  </div>
</div>
<div class="form-group">
  <label class="col-md-4 control-label" for="payment_reference_no">Relation Type</label>  
  <div class="col-md-4">
  <input id="relation_type" name="relation_type" type="text" value="<? echo $relation_type;?>"  placeholder="Relation Type" class="form-control input-md">
    
  </div>
</div>

<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="total_premium">Relation date of birth</label>  
  <div class="col-md-4">
  <input id="relation_date_of_birth" name="relation_date_of_birth" type="text" value="<? echo $relation_date_of_birth;?>"  placeholder="Relation date of birth" class="date">
    
  </div>
</div>

<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="cover_level">Cover level</label>  
  <div class="col-md-4">
  <input id="cover_level" name="cover_level" type="text" value="<? echo $cover_level;?>"  placeholder="Cover level" class="form-control input-md">
    
  </div>
</div>


    

 <div class="form-group">
  <label class="col-md-4 control-label" for="singlebutton"></label>
  <div class="col-md-4">
    <button id="singlebutton" name="" onclick="post_application_2();" class="btn btn-primary">Save</button>
  </div>
</div>