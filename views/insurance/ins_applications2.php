<script>

var dataSet = [ ];

<?php $count = 1; foreach($ins_results as $row){
    

$title          = $row['title'];
$initial        = $row['initial'];
$name           = $row['name'];
$surname        = $row['surname'];                                                          
$gender         = $row['gender'];  
$marital_status = $row['marital_status'];
$id             = $row['id'];
$passport_number= $row['passport_number'];
$date_of_birth  = $row['date_of_birth'];
$tel_cell       = $row['tel_cell'];
$email          = $row['email'];
$postal_code    = $row['postal_code'];
$ben_name       = $row['ben_name'];
$ben_id_number  = $row['ben_id_number'];
$plan           = $row['plan'];

?>
dataSet.push(["<? echo $count;?>","<? echo $name;?>", "<? echo $surname;?>", "<? echo $date_of_birth;?>","<? echo $id?>", "<? echo $passport_number?>","<? echo $tel_cell?>","<? echo $email?>","<? echo $postal_code?>"]);

<?php $count++; } ?>


$(document).ready(function() {
    $('#example').DataTable( {
        data:dataSet,
        columns: [
            { title: "Id" },
            { title: "Name" },
            { title: "Surname" },
            { title: "Date of birth" },
            { title: "ID Number" },
            { title: "Passport Number " },
            { title: "Cellphono No" },
            { title: "Email" },
            { title: "Postal Code" }
        ]
    } );
} );
 
</script>


   
 <table> 
            <tr>
            <? if(isset($query) && $query != ''){ ?>

                 <td>
                    <a href='#' onclick="get_application_form();" class='btn btn-primar' style="color:black" id='details'>&#x0002B; Add Insurance </a>
                     
<!--                    <a href='<? //echo base_url()?>management/ins_application_form' class='btn btn-primar' style="color:black" id='details'>&#x0002B; Add Insurance</a>-->
                </td>

                <td>

                    <form method="post" action="/dashboard/download_csv">
                      <input name="query" type="hidden" value="<? echo $query; ?>">
                      <!-- <input type="image" src="/assets/img/csv_export.jpg" height="30px" class="" /> -->
                      <input type="submit" value="Download all Info CSV file"  class='btn btn-primar' style="color:black" />
                    </form> 

                </td>    
            <? } ?> 

            <? if(isset($query2) && $query2 != ''){ ?>

                <td>

                   <form method="post" action="/dashboard/download_csv">
                      <input name="query" type="hidden" value="<? echo $query2; ?>">
                      <!-- <input type="image" src="/assets/img/csv_export.jpg" height="30px" class="" /> -->
                      <input type="submit" value="Download dependent CSV file" class='btn btn-primar' style="color:black" />
                   </form>

                </td>


            <? } ?> 

            </tr>

            </table>     
            <br/>
        <table id="example" class="display" width="100%" style="font-size:11px;"></table>

