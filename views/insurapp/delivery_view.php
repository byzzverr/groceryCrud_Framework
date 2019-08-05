<div class="container theme-showcase" role="main">
    <div class="jumbotron title page">
        <h1><strong><?php echo $page_title?></strong></h1>
    </div>

    <div>
    	<?php echo $map['html']; ?>
    </div>
     <?php if(isset($map)) { ?>
  	<?php echo $map['js']; ?>
	<? } ?>

    <div id="mtabs">
    	<ul>
		    <li class="active"><a href="#tab1" rel="tab1"><button>Delivery Information</button></a></li>
		   
	    </ul>
    </div>

<div id="tab1" class="mtab_content" style="height:300px; overflow:auto;">
    	<table class="table table-condensed">
    		<tr>
	    		<td>Driver</td>
	    		<td><?php echo $driver?></td>
    		</tr>

    		<tr>
    			<td>Fridge unit code</td>
    		 	<td><?php echo $fridge_unit_code?></td>
    		</tr>

    		<tr>
	    		<td>Fridge Type</td> 
	    		<td><?php echo $fridge_type?></td>
    		</tr>

    		<tr>
	    		<td>Brand Name</td> 
	    		<td><?php echo $brand?></td>
    		</tr>
    		
    	</table>
</div>
   
 </div>



 <script type="text/javascript">
$(document).ready(function(){
    $("#mtabs li").click(function() {
        $("#mtabs li").removeClass('active');
       	$(this).addClass("active");
        	$(".mtab_content").hide();
        	var selected_tab = $(this).find("a").attr("href");
        	$(selected_tab).fadeIn();
        	return false;
    });
    $("#simulate").click(function(){
    	$('a[rel="tab2"]').trigger("click");
  	});
});
</script>

<style type="text/css">
	#mtabs li {
	    display: inline;
	}
	.mtab_content {
    	display: none;}
	}
</style>