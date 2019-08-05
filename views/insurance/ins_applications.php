
<script>
 

 $(function() {
    $( "#to" ).datepicker( {dateFormat: 'yy-mm-dd' } );
  }); 
    
$(function() {
    $( "#from" ).datepicker( {dateFormat: 'yy-mm-dd' } );
  }); 
    
</script>

<? if($page_title == "Insurance Applications"){?>
<script>

var dataSet = [ ];

<?php $count = 1; foreach($ins_results as $row){ ?>
    
dataSet.push(["<? echo $count;?>","<? echo $row['name'];?>","<? echo $row['surname'];?>","<? echo $row['id']?>","<? echo $row['passport_number']?>","<? echo $row['tel_cell']?>",
              "<? echo $row['email']?>","<a href='/management/funeral_product_info/<? echo $row['product_id']?>/<? echo $row['policy_number']?>'><? echo $row['type']?></a>",
              "<a href='/assets/uploads/insurance/pictures/<? echo $row['picture']?>'><image src='/assets/uploads/insurance/pictures/<? echo $row['picture']?>' style='width:50px'></a>",
              "<a href='/management/ins_dependents/<? echo $row['policy_number']?>'>Dependent</a>"
             ]);

<?php $count++; } ?>


$(document).ready(function() {
    $('#example').DataTable( {
        data:dataSet,
        columns: [
            { title: "Id" },
            { title: "Name" },
            { title: "Surname" },
            { title: "ID Number" },
            { title: "Passport Number " },
            { title: "Cellphone No" },
            { title: "Email" },
            { title: "Product Info" },
            { title: "Picture" },
            { title: "Dependents" }
        ]
    } );
} );
 
</script>

<fieldset>
<div class="container-fluid">
<div class="page-header">
    <h3><?php echo strtoupper(humanize($page_title)); ?></h3>
  
</div>

   <div class="jumbotron title page"> 
    
        <table> 
            <tr>
            <? if(isset($query) && $query != ''){ ?>

                <td>

                    <form method="post" action="/dashboard/download_csv">
                      <input name="query" type="hidden" value="<? echo $query; ?>">
                      <!-- <input type="image" src="/assets/img/csv_export.jpg" height="30px" class="" /> -->
                      <input type="submit" value="Download all Info CSV file"   style="color:black" />
                    </form> 

                </td>    
            <? } ?> 

            <? if(isset($query2) && $query2 != ''){ ?>

                <td>

                   <form method="post" action="/dashboard/download_csv">
                      <input name="query" type="hidden" value="<? echo $query2; ?>">
                      <!-- <input type="image" src="/assets/img/csv_export.jpg" height="30px" class="" /> -->
                      <input type="submit" value="Download dependent CSV file" style="color:black" />
                   </form>

                </td>


            <? } ?> 

            </tr>

            </table>     
        <div class="jumbotron title page"> 
            <table id="example" class="display" width="100%" style="font-size:11px;"></table>
       </div>

    </div>
   

</div>   

</fieldset>
<? } ?>


<? if($page_title == "Dependents"){?>

<script>

var dataSet = [ ];

<?php 
    $count = 1;
    foreach($dependents as $row){ ?>
    
dataSet.push(["<? echo $count;?>","<? echo $row->relation_first_name;?>", 
              "<? echo $row->relation_surname;?>","<? echo $row->relation_type?>", 
              "<? echo $row->relation_date_of_birth?>","<? echo $row->cover_level?>"
             ]);

<?php $count++; } ?>


$(document).ready(function() {
    $('#example').DataTable( {
        data:dataSet,
        columns: [
            { title: "Id" },
            { title: "Name" },
            { title: "Surname" },
            { title: "Relation type" },
            { title: "Date of birth" },
            { title: "Cover level" }
        ]
    } );
} );
 
</script>
<fieldset>
<div class="container-fluid">
<div class="page-header">
    <h3><?php echo strtoupper(humanize("POLICY NUMBER : ".$policy_number." - ".$page_title)); ?></h3>
  
</div>
    <div><a href ="/management/ins_application" class='btn btn-primar' style="color:gray">Back</a></div>
      <div class="jumbotron title page"> 
        <table id="example" class="display" width="100%" style="font-size:11px;"></table>
    </div>

</div>   

</fieldset>
<? } ?>



<? if($page_title == "Funeral Product Info"){?>


 
<fieldset>
<div class="container-fluid">
<div class="page-header">
    <h3><?php echo strtoupper(humanize($page_title." - "."POLICY NUMBER : ".$policy_number)); ?></h3>
  
</div>
       <br/>
        <table style="width:40%;">
        <tr><td colspan="2"><a href ="/management/ins_applications" class='btn btn-primar' style="color:gray">Back</a></td></tr>
       
                <tr>
                    <td><b>Type</b></td>           
                    <td> : <? echo $product_info['type']?></td>
                </tr>
                <tr>
                    <td><b>Member benefit</b></td> 
                    <td> : <? echo $product_info['member_benefit']?></td>
                </tr>
                <tr>
                    <td><b>Spouse option</b></td>  
                    <td> : <? echo $product_info['spouse_option']?></td>
                </tr>
                <tr>
                    <td><b>Child age 0 to 11 mths</b></td>
                    <td> : <? echo $product_info['child_age_0_11_mths']?></td>
                </tr>
                <tr>
                    <td><b>Child age 1 to 5 yrs</b></td>
                    <td> : <? echo $product_info['child_age_1_5_yrs']?></td>
                </tr>
                <tr>
                    <td><b>Child age 6 to 13 yrs</b></td>
                    <td> : <? echo $product_info['child_age_6_13_yrs']?></td>
                </tr>
                <tr>
                    <td><b>Child age 14 to 21 yrs</b></td>
                    <td> : <? echo $product_info['child_age_14_21_yrs']?></td>
                </tr>
                <tr>
                    <td><b>Premium</b></td>
                    <td> : <? echo $product_info['premium']?></td>
                </tr>
                <tr>
                    <td><b>Sale reward</b></td>
                    <td> : <? echo $product_info['sale_reward']?></td>
                </tr>
            
     
        </table>
</div>   

</fieldset>
<? } ?>

<script type="text/javascript">
    
   $(document).ready(function() {
        $('.nav-toggle').click(function(){

          var collapse_content_selector = $('.collapseDiv');         

          var toggle_switch = $(this);
          $(collapse_content_selector).toggle(function(){
            if($('.collapseDiv').css('display')=='none'){
              toggle_switch.html('Show');
            }else{
              toggle_switch.html('Hide');
            }
          });
        });
    });


</script>