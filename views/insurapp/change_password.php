
<style>

	.alert1{
	    padding: 5px;
	    background-color: transparent;
	    color: red;
	    border: outset thin silver;
	    border-radius: 3px;
	}
	.alert2{
	    padding: 20px;
	    background-color: transparent;
	    border: outset thin silver;
	    border-radius: 3px;
	    color: black;
	}

	.closebtn{
	    margin-left: 15px;
	    color: gray;
	    font-weight: bold;
	    float: right;
	    font-size: 22px;
	    line-height: 20px;
	    cursor: pointer;
	    transition: 0.0s;

	}

	.closebtn:hover {
	    color: silver;
	}
	.table td{
		background-color: #f9f9f9;
	}

	td{
		background-color: #f9f9f9;
	}
</style>

<fieldset>
<div class="container-fluid">
<div class="page-header">
    <h3><?php echo humanize($page_title); ?></h3>
</div>


		<form action="" method="POST">
  
		  <div id="form">
		  <p style="color:red"><?php echo $error?></p>
		  <table>
			<tr>
			   <td style="width:15%;  border:none">Name 
			   <td style="border:none">
			   : <input type="text" placeholder="Username" name="username" value="<? echo $user_info->name;?>" readonly />
			   </td>
			</td>

			<tr> 
			  <td style="border:none">Username </td>
			  <td style="border:none">
			  : <input type="text" placeholder="Username" name="username" value="<? echo $user_info->username;?>" readonly />
			  </td>
			</tr>

			<!-- <tr>
			   <td style="background-color: #fff; border:none">Password 
			   <td style="background-color: #fff; border:none">
			   : <input type="password" placeholder="Old Password" name="password" required/>
			   </td>

			</tr> -->
			<tr>
			   <td style="border:none">New Password</td>
			   <td style="border:none">
			   : <input type="password" placeholder="New Password" name="new_password" id="passOne" required/>
			   </td>
			</tr>

			<tr>
			   <td style="border:none">Confirm Password</td>
			   <td style="border:none">
			   : <input type="password" placeholder="Confirm Password" name="confirm_password" id="passTwo" required/>
			   </td>

			</tr>

			<tr>
				<td colspan="2" style="border:none">
				<!-- <span id="wait" style="display:none;">
					<img src='/assets/images/demo_wait.gif' width="34" height="34" />
				</span> -->

					 <button name="submit" class='btn button1' style='color:black' >Continue</button>
				</td>

			</tr>

		   </table>
		  </div>

		 

		 </form>
		 
			<?php echo $message?>

</div>

</fieldset>
<script>
var close = document.getElementsByClassName("closebtn");
var i;

for (i = 0; i < close.length; i++) {
    close[i].onclick = function(){
        var div = this.parentElement;
        div.style.opacity = "0";
        setTimeout(function(){ div.style.display = "none"; }, 60000);
    }
}
</script>
