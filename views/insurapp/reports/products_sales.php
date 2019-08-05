<fieldset>
	<div class="fluid-container">

		<div class="page-header">
	    	<h3><?php echo humanize($page_title); ?> </h3>
	    </div>

	     <form action="" method="POST" >   
	      	Date From : <input id="from" class="form-input-box" type="text" name="date_from" value="<? echo $date_from;?>" />
	      	Date To : <input id="to" class="form-input-box " type="text" name="date_to" value="<? echo $date_to;?>"  />
			<button type="submit"  class="btn" style="color:black">Filter</button>
	    </form><hr/>

	    <div class="row">
	    <strong style="color:#29788b">Last 50 Product Sales</strong> 
	    	<canvas id="canvas" width="600" height="200"></canvas>
	    </div>

	    <hr/>

	    <table id="report_table" class="display" width="100%" ></table>
    
	</div>

</fieldset>

<script type="text/javascript">
	
	var img = new Image();

    img.setAttribute('crossOrigin', 'anonymous');

    img.onload = function () {
        var canvas = document.createElement('canvas');
        canvas.width =this.width;
        canvas.height =this.height;

        var ctx = canvas.getContext('2d');
        ctx.drawImage(this, 0, 0);

        var dataURL = canvas.toDataURL('image/png');

        alert(dataURL.replace(/^data:image\/(png|jpg);base64,/, ''));
    };

    img.src = url;

</script>
