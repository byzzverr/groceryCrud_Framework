<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
<script src="/assets/grocery_crud/themes/bootstrap-v4/js/bootstrap.min.js"></script>

<!-- Datatable plugins -->
<script type="text/javascript" src="<?php echo base_url();?>assets/js/dataTables.tableTools.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/buttons.flash.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/jszip.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/pdfmake.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/vfs_fonts.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/buttons.print.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/responsive.bootstrap.min.js"></script>
<link rel="stylesheet" rel="stylesheet" href="<?php echo base_url();?>assets/css/buttons.dataTables.min.css"/>
<link rel="stylesheet" rel="stylesheet" href="<?php echo base_url();?>assets/css/responsive.bootstrap.min.css"/>

</body>
</html>
<script type="text/javascript">
	 function searchTable() {
        var input, filter, found, table, tr, td, i, j;
        input = document.getElementById("myInput");
        filter = input.value.toUpperCase();
        table = document.getElementById("myTable");
        tr = table.getElementsByTagName("tr");
        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td");
            for (j = 0; j < td.length; j++) {
                if (td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                }
            }
            if (found) {
                tr[i].style.display = "";
                found = false;
            } else {
                tr[i].style.display = "none";
            }
        }
        
    }

     Chart.types.Doughnut.extend({
          name: "DoughnutTextInside",
          showTooltip: function() {
              this.chart.ctx.save();
              Chart.types.Doughnut.prototype.showTooltip.apply(this, arguments);
              this.chart.ctx.restore();
          },
          draw: function() {
              Chart.types.Doughnut.prototype.draw.apply(this, arguments);
              var width = this.chart.width,
                  height = this.chart.height;
              var fontSize = (height / 200);
              this.chart.ctx.font = fontSize + "em Verdana, black";
              this.chart.ctx.textBaseline = "middle";
              var text =  total.toFixed(2),
                  textX = Math.round((width - this.chart.ctx.measureText(text).width) / 2),
                  textY = height / 2;
              this.chart.ctx.fillText(text, textX, textY);
          }});

       function getRandomColor() {
              var letters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWZ';
              var color = '#';
              for (var i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
              }
              return color;
        }

    function getRandomColor() {
              var letters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWZ';
              var color = '#';
              for (var i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
              }
              return color;
        }
</script>