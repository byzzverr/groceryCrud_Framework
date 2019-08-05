<div class="container">
<div style="width: 320px; margin: 0 auto;">
   <h3>Reset Password</h3>
   <? if (!empty($error)): ?>
      <div class="alert alert-error">
         <b>Error!</b> <?= $error ?>
      </div>
   <? elseif (!empty($info)): ?>
      <div class="alert alert-info">
         <b>Info.</b> <?= $info ?>
      </div>
   <? endif; ?>
   <form class="well" method="POST" action="/login/forgot_password">
      <label>Username/Email</label>
      <input type="text" name="username" style="width: 260px;">
      <button type="submit" class="btn">Reset Password</button>
      <a style="float:right;" href="/login/">Back to Login</a>
   </form>

</div>
</div>
