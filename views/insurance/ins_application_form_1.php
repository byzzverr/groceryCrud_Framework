<script>
 $(function() {
    $( ".date" ).datepicker( {dateFormat: 'yy-mm-dd' } );
  }); 
</script>
<!--
<fieldset>
    <div class="container">
        <div class="page-header">
            <h1><?php //echo humanize($page_title); ?></h1>
 <hr/>           
-->
<div id="responsecontainer">    
<!-- Text input-->

    
<div class="form-group">
  <label class="col-md-4 control-label" for="name">Name</label>  
  <div class="col-md-4">
  <input id="name" name="name" type="text" placeholder="Name" class="form-control input-md">
    
  </div>
</div>

<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="surname">Surname</label>  
  <div class="col-md-4">
  <input id="surname" name="surname" type="text" placeholder="Surname" class="form-control input-md">
    
  </div>
</div>   

<div class="form-group">
  <label class="col-md-4 control-label" for="date_of_birth">Date of birth</label>  
  <div class="col-md-4">
  <input class="date" type="text" id="date_of_birth" name="date_of_birth"  placeholder="Date of birth" >
    
  </div>
</div>

 <!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="id">ID</label>  
  <div class="col-md-4">
  <input id="id" name="id" type="text" placeholder="Identity Number" class="form-control input-md">
    
  </div>
</div>
            
<div class="form-group">
  <label class="col-md-4 control-label" for="passport_number">Passport Number</label>  
  <div class="col-md-4">
  <input id="passport_number" name="passport_number" type="text" placeholder="Passport Number" class="form-control input-md">
    
  </div>
</div>
<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="tel_cell">Cell</label>  
  <div class="col-md-4">
  <input id="tel_cell" name="tel_cell" type="text" placeholder="Cellphone No" class="form-control input-md">
    
  </div>
</div>
<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="email">Email</label>  
  <div class="col-md-4">
  <input id="email" name="email" type="email" placeholder="Email" class="form-control input-md">
    
  </div>
</div>
            
            
<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="postal_code">Postal Code</label>  
  <div class="col-md-4">
  <input id="postal_code" name="postal_code" type="text" placeholder="Postal Code" class="form-control input-md">
    
  </div>
</div>

<!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="ben_name">Beneficiary Name</label>  
  <div class="col-md-4">
  <input id="ben_name" name="ben_name" type="text" placeholder="Beneficiary Name" class="form-control input-md">
    
  </div>
</div>
    
 <!-- Text input-->
<div class="form-group">
  <label class="col-md-4 control-label" for="ben_name">Beneficiary ID</label>  
  <div class="col-md-4">
  <input id="ben_id_number" name="ben_id_number" type="text" placeholder="Beneficiary Identity Number" class="form-control input-md">
    
  </div>
</div>
    
<div class="form-group">
  <label class="col-md-4 control-label" for="plan">Plan</label>  
  <div class="col-md-4">
<!--  <input id="plan" name="plan" type="text" placeholder="Plan" class="form-control input-md">-->
  <select id="plan" class="form-control input-md" name="plan">
      <option>Select Value</option>
       <? foreach($products as $row){?>
        <option value="<? echo $row->type;?>"><? echo $row->type;?></option>
       <? } ?>
    </select>   
  </div>
</div>
    
<!--
<div class="form-group">
  <label class="col-md-4 control-label" for="ins_prod_id">Insurance Product Id</label>  
  <div class="col-md-4">
  <input id="ins_prod_id" name="ins_prod_id" type="text" placeholder="Plan" >
   <select id="ins_prod_id" class="form-control input-md" name="ins_prod_id">
      <option>Select Value</option>
       <? //foreach($products as $row){?>
        <option value="<? //echo $row->id;?>"><? //echo $row->type;?></option>
       <? //} ?>
    </select> 
  </div>
</div>
-->
        
            
 <div class="form-group">
  <label class="col-md-4 control-label" for="singlebutton"></label>
  <div class="col-md-4">
      <!--onclick="post_application_1();"--> 
    <button id="singlebutton" name="" onclick="post_application_1();" class="btn btn-primary">Save</button>
  </div>
</div>

    
<!--
</div>
    
<fieldset>
-->
