<fieldset>
<div class="container">
    <div class="page-header">
        
        <script>

        var dataSet = [ ];

        <?php 
            $count = 1;
            foreach($sales_results as $row){ 

            $id_number ='';

            if(!empty($row->id)){
            $id_number = $row->id;
            }

            if(!empty($row->passport_number)){
            $id_number = $row->passport_number;
            }
        ?> 
            dataSet.push(["<? echo $row->policy_number;?>", 
                      "<? echo $id_number;?>","<? echo $row->name?>", 
                      "<? echo $row->type?>","<? echo $row->premium?>", "<? echo $row->sale_reward?>", 
                      "<? echo $row->payment_reference_no?>", 
                      "<? echo $row->application_date?>","<? echo $row->expiry_date?>"
                     ]);

        <?php $count++; } ?>


        $(document).ready(function() {
            $('#example').DataTable( {
                data:dataSet,
                columns: [
                    { title: "Policy number" },
                    { title: "SA ID / Passport" },
                    { title: "Sales person"},
                    { title: "Policy name" },
                    { title: "Premium" },
                    { title: "Sale rewards" },
                    { title: "Payment reference no" },
                    { title: "Application date" },
                    { title: "Expiry date" }
                ], dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
            } );
        } );


            <?    

            $comma ='';
            $values ='';
            $labels ='';
            foreach ($sales_results_stats as $row) {

            $labels .= $comma.'"'.humanize($row->sales_person).'"';
            $values .= $comma.$row->premium;
            $comma = ',';



            }?>

            var randomScalingFactor = function(){ return Math.round(Math.random()*100)};
            var barChartData = {
            labels : [<?php echo $labels?>],
            datasets : [
            {
                fillColor : "#c8ba9e",
                strokeColor : "#c8ba9e",
                highlightFill : "#bca789",
                highlightStroke : "#b4a085",
                data : [<?php echo $values?>]
                    }
                ]
            }
            window.onload = function(){
                var ctx = document.getElementById("canvas2").getContext("2d");
                window.myBar = new Chart(ctx).Bar(barChartData,{
                    responsive : true
                });
            }

        </script>
        
    <h3><?php echo humanize($page_title); ?></h3><br/>
    <div class="row"> 
       
        <div class="chart_left"  style="width:38%;">
            
            <table id="mydata" style="width:100%;">

                <tr>
                    <th><b>Sales Person</b></th>
                    
                    <th><b>Number of Claims</b></th>
                    <th><b>Premium</b></th>
                </tr>
                <? 
                $Total_number =0;
                $Total_premium =0;
                $Total_sale_reward =0;
                foreach ($sales_results_stats as $row1) { 
                $Total_premium += $row1->premium;
                $Total_number += $row1->number_of_sales;
                $Total_sale_reward += $row1->count_sales;
                ?>
                <tbody style="overflow-y:scroll; height:100px;"> 
                <tr>
                    <td style="color:#f8931d;"><? echo $row1->sales_person;?></td>   
                    <td>  <? echo $row1->number_of_sales;?></td>
                    <td>  <? echo $row1->premium;?></td>

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
      
        <div class="chart_right" style="width: 50%">
             <a><h4>Insurance sales chart</h4></a>
            <canvas id="canvas2" width="300" height="180"></canvas>
        </div>
    </div>
     <hr/>
     <a><h4>Funeral product sales chart</h4></a>
    <canvas id="canvas" width="300" height="200"></canvas>
      
    
        <script>
        <?    

        $comma ='';
        $values ='';
        $labels ='';
        foreach ($funeral_stats as $row) {

        $labels .= $comma.'"'.humanize($row->product_type).'"';
        $values .= $comma.$row->product_count;
        $comma = ',';



        }?>

        var data = {
        labels: [<? echo $labels?>],
        datasets : [
        {
             fillColor : "#c8ba9e",
            strokeColor : "#c8ba9e",
            highlightFill : "#bca789",
            highlightStroke : "#b4a085",
            data : [<?php echo $values?>]
                }
            ]
        };
        var context = document.getElementById('canvas').getContext('2d');
        var skillsChart = new Chart(context).Bar(data);


        </script>
    <div><hr/>
    <table id="example" class="display" width="100%" style="font-size:11px;"></table>

        
        </div>
    </div>
</div>   
</fieldset>

<script type="text/javascript" src="jquery-1.12.0.min.js"></script>
 
<script type="text/javascript" src="<?php echo base_url();?>assets/js/dataTables.tableTools.min.js"></script>
<!-- <script type="text/javascript" src="https://cdn.datatables.net/tabletools/2.2.2/swf/copy_csv_xls_pdf.swf"></script> -->
<script type="text/javascript" src="<?php echo base_url();?>assets/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/buttons.flash.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/jszip.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/pdfmake.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/vfs_fonts.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/buttons.print.min.js"></script>
<link rel="stylesheet" rel="stylesheet" href="<?php echo base_url();?>assets/css/buttons.dataTables.min.css"/>

