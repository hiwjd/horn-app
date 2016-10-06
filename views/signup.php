<div class="row" style="margin-top:100px;">
  <div class="small-4 large-4 columns">&nbsp;</div>

  <div class="small-4 large-4 columns">

    <h3>免费注册</h3>
    <ul>
      <?php $errs = $this->flashes("error"); ?>
      <?php foreach($errs as $err): ?>
        <li><?php echo $err; ?></li>
      <?php endforeach; ?>
    </ul>
    <ul>
      <?php $errs = $this->flashes("success"); ?>
      <?php foreach($errs as $err): ?>
        <li><?php echo $err; ?></li>
      <?php endforeach; ?>
    </ul>
    <form action="/signup" method="POST">
      <div class="row">
        <div class="large-12 columns">
          <label>企业/团队名称</label>
          <input type="text" name="name" placeholder="" />
        </div>
      </div>
      <div class="row">
        <div class="large-12 columns">
          <label>登录邮箱</label>
          <input type="text" name="email" placeholder="" />
        </div>
      </div>
      <div class="row">
        <div class="large-12 columns">
          <label>密码</label>
          <input type="password" name="pwd" placeholder="" />
        </div>
      </div>
      <div class="row">
        <div class="large-12 columns">
          <label>联系电话</label>
          <input type="text" name="tel" placeholder="" />
        </div>
      </div>
      <div class="row">
        <div class="large-8 columns">
          <label>验证码</label>
          <input type="text" name="captcha" placeholder="" />
        </div>
        <div class="large-4 columns">
        <label>&nbsp;</label>
        <img src="/captcha/signup" style="cursor:pointer; height:38px;" onclick="this.src='/captcha/signup?t='+(new Date()).getTime()" />
        </div>
      </div>
      <div class="row">
        <div class="large-12 columns">
          <input type="button" class="expanded button" value="注册" id="signup" />
        </div>
      </div>

    </form>
  </div>

  <div class="small-4 large-4 columns">&nbsp;</div>

</div>