
<fieldset>
<div class="container-fluid">
<div class="page-header">
    <h3><?php echo humanize($page_title); ?></h3>
  
    </div>
       <br/>
    <div class="row">
   
    <div class="jumbotron title page"> 
    <form action="" method="POST"  >   
      Date From : <input id="from" class="form-input-box" type="text" name="date_from" value="<? echo $date_from;?>" />
      Date To : <input id="to" class="form-input-box " type="text" name="date_to" value="<? echo $date_to;?>"  />

        <button type="submit"  class="btn" style="color:black">Filter</button>
    </form>
 
    </div>

        <div class="chart_left"  style="width:38%;">
           <div id="" style="overflow-y: scroll; height:400px;"> 
            <table id="mydata" style="width:100%;">

                <tr>
                    <th><b>Sales Person</b></th>  
                    <th><b>Number of Sales</b></th>
                    <th><b>Premium</b></th>
                </tr>
                <? 
                $Total_number =0;
                $Total_premium =0;
                foreach ($sales_results_stats as $row1) { 
                    $Total_premium += $row1->premium;
                    $Total_number += $row1->number_of_sales;
                ?>
                <tbody style="overflow-y:scroll; height:100px;"> 
                <tr>
                    <td style="color:#f8931d;"><? echo $row1->sales_person;?></td>   
                    <td>  <? echo $row1->number_of_sales;?></td>
                    <td>  R <? echo $row1->premium;?></td>       
                </tr>
                </tbody>
                <? } ?>
                <tr><td colspan="4"></td></tr>
                <tr>
                    <td><b>Total</b></td>
                    <td><b><? echo $Total_number;?></b></td> 
                    <td><b><? echo "R ". number_format($Total_premium,2,'.',' ');?></b></td> 
                </tr>
            </table>   
         </div>
         </div>
      
        <div class="chart_right" style="width: 50%">
            <div class="collapseDiv">
                 <a><h4>Last 50 Sales</h4></a>
                <canvas id="canvas" width="300" height="180"></canvas>
            </div>
        </div>
    </div>
    <div>
        <hr/>
        <table id="report_table" class="display" width="100%" ></table>
    
    </div>
        

</div>   

</fieldset>



