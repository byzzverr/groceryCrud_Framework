<!--   
   <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
   <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
   <script src="//cdnjs.cloudflare.com/ajax/libs/lodash.js/1.3.1/lodash.min.js"></script>
   <script src="<?php echo base_url('assets/js/bootstrap.min.js') ?>"></script>
   <script src="<?php echo base_url('assets/js/custom.js') ?>"></script>
-->
</body>
</html>


<!-- <script type="text/javascript" src="jquery-1.12.0.min.js"></script>
<script type="text/javascript" src="<?php echo base_url();?>assets/js/dataTables.tableTools.min.js"></script> -->
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
<script type="text/javascript" src="<?php echo base_url();?>assets/js/dataTables.editor.min.js"></script>
<link rel="stylesheet" rel="stylesheet" href="<?php echo base_url();?>assets/css/buttons.dataTables.min.css"/>
<link rel="stylesheet" rel="stylesheet" href="<?php echo base_url();?>assets/css/responsive.bootstrap.min.css"/>
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
</script>