<fieldset>
<div id="responseresults">
  
	<div class="fluid-container">
		<div class="page-header">
		    <h3><?php echo humanize($page_title); ?></h3>
		</div>
		
		<table>
			<tr>
				<td style="background-color:#fff; border:none">Name</td>
				<td style="background-color:#fff; border:none"> : <?php echo $user_info->name?></td>
			</tr>

			<tr>
				<td style="background-color:#fff; border:none">Email</td>
				<td style="background-color:#fff; border:none"> : <?php echo $user_info->email?></td>
			</tr>

			<tr>
				<td style="background-color:#fff; border:none">Cellphone</td>
				<td style="background-color:#fff; border:none"> : <?php echo $user_info->cellphone?></td>
			</tr>

			<tr>
				<td style="background-color:#fff; border:none" class="fieldset" colspan="2"><hr/>
				<a href="/users/reset_password/<?php echo  $user_info->id?>" class="btn button" style="color:black">Reset Password</a>
				<a href="/other_users/change_password" class="btn button" style="color:black">Update Password</a></td>
			</tr>
		</table>

	</div>
</div>
</fieldset>

