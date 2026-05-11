<?php
// register.php
require_once 'includes/config.php';
if(isLoggedIn()){header('Location:'.BASE_URL.'/dashboard.php');exit;}
$errors=[];$old=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $old=$_POST;
    $name=s($_POST['name']??'');$username=strtolower(s($_POST['username']??''));
    $email=filter_var(trim($_POST['email']??''),FILTER_SANITIZE_EMAIL);
    $univ=s($_POST['university']??'');$dept=s($_POST['department']??'');
    $gradyr=(int)($_POST['graduation_year']??0);$pass=$_POST['password']??'';$conf=$_POST['password_confirmation']??'';
    if(strlen($name)<2)$errors['name']='Name required.';
    if(!preg_match('/^[a-z0-9_]{3,30}$/',$username))$errors['username']='3-30 chars, letters/numbers/underscore.';
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))$errors['email']='Valid email required.';
    if(strlen($univ)<2)$errors['university']='University required.';
    if(strlen($dept)<2)$errors['department']='Department required.';
    if($gradyr<2024||$gradyr>2035)$errors['graduation_year']='Valid year required.';
    if(strlen($pass)<8)$errors['password']='Min 8 characters.';
    if($pass!==$conf)$errors['password']='Passwords do not match.';
    if(empty($errors)){
        $chk=db()->prepare("SELECT id FROM users WHERE email=? OR username=?");$chk->execute([$email,$username]);
        if($chk->fetch()){$errors['email']='Email or username already taken.';}
        else{
            $hash=password_hash($pass,PASSWORD_BCRYPT,['cost'=>12]);
            db()->prepare("INSERT INTO users(name,username,email,password,university,department,graduation_year)VALUES(?,?,?,?,?,?,?)")->execute([$name,$username,$email,$hash,$univ,$dept,$gradyr]);
            $u=db()->prepare("SELECT * FROM users WHERE email=?");$u->execute([$email]);
            $_SESSION['user']=$u->fetch();session_regenerate_id(true);
            flash('success','Welcome to Campus Connect X, '.$name.'! 🎉');
            header('Location:'.BASE_URL.'/dashboard.php');exit;
        }
    }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Join Campus Connect X</title><link rel="stylesheet" href="<?=BASE_URL?>/assets/css/app.css"></head><body>
<div class="auth-wrap">
<div class="auth-left">
  <a href="<?=BASE_URL?>" class="auth-brand"><div class="auth-brand-icon">🎓</div>Campus Connect X</a>
  <div class="auth-hero">
    <h1>Your student <span>future</span> starts here.</h1>
    <p>Join verified students connecting, learning, and growing together in one powerful ecosystem.</p>
    <div class="auth-feats">
      <div class="auth-feat"><div class="auth-feat-dot"></div>Verified university student identity</div>
      <div class="auth-feat"><div class="auth-feat-dot"></div>Community + Career + Marketplace</div>
      <div class="auth-feat"><div class="auth-feat-dot"></div>Abroad survival guides &amp; tips</div>
      <div class="auth-feat"><div class="auth-feat-dot"></div>AI-powered CV builder &amp; PDF download</div>
      <div class="auth-feat"><div class="auth-feat-dot"></div>Student job board &amp; internships</div>
    </div>
  </div>
  <div style="font-size:12px;color:#1E293B;z-index:1;">© <?=date('Y')?> Campus Connect X</div>
</div>
<div class="auth-right"><div class="auth-box">
  <h2>Create your account</h2>
  <p class="auth-sub">Already have one? <a href="<?=BASE_URL?>/login.php">Sign in</a></p>
  <?php if(!empty($errors)):?><div class="alert al-e">Please fix the errors below.</div><?php endif;?>
  <form method="POST" novalidate>
    <input type="hidden" name="csrf" value="<?=csrf()?>">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control <?=isset($errors['name'])?'err':''?>" placeholder="John Doe" value="<?=clean($old['name']??'')?>" required autofocus>
        <?php if(isset($errors['name'])):?><span class="form-error"><?=$errors['name']?></span><?php endif;?>
      </div>
      <div class="form-group"><label class="form-label">Username</label>
        <div class="input-pfx"><span class="pfx">@</span>
        <input type="text" name="username" class="form-control <?=isset($errors['username'])?'err':''?>" placeholder="johndoe" value="<?=clean($old['username']??'')?>" required></div>
        <?php if(isset($errors['username'])):?><span class="form-error"><?=$errors['username']?></span><?php endif;?>
      </div>
    </div>
    <div class="form-group"><label class="form-label">University Email</label>
      <input type="email" name="email" class="form-control <?=isset($errors['email'])?'err':''?>" placeholder="you@university.edu" value="<?=clean($old['email']??'')?>" required>
      <?php if(isset($errors['email'])):?><span class="form-error"><?=$errors['email']?></span><?php endif;?>
    </div>
    <div class="form-group"><label class="form-label">University Name</label>
      <input type="text" name="university" class="form-control <?=isset($errors['university'])?'err':''?>" placeholder="University of Manchester" value="<?=clean($old['university']??'')?>" required>
      <?php if(isset($errors['university'])):?><span class="form-error"><?=$errors['university']?></span><?php endif;?>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Department</label>
        <input type="text" name="department" class="form-control <?=isset($errors['department'])?'err':''?>" placeholder="Computer Science" value="<?=clean($old['department']??'')?>" required>
        <?php if(isset($errors['department'])):?><span class="form-error"><?=$errors['department']?></span><?php endif;?>
      </div>
      <div class="form-group"><label class="form-label">Graduation Year</label>
        <select name="graduation_year" class="form-control">
          <option value="">Year</option>
          <?php for($y=date('Y');$y<=date('Y')+7;$y++):?>
            <option value="<?=$y?>" <?=($old['graduation_year']??'')==$y?'selected':''?>><?=$y?></option>
          <?php endfor;?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Password</label>
        <input type="password" name="password" class="form-control <?=isset($errors['password'])?'err':''?>" placeholder="Min 8 characters" required>
        <?php if(isset($errors['password'])):?><span class="form-error"><?=$errors['password']?></span><?php endif;?>
      </div>
      <div class="form-group"><label class="form-label">Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat password" required>
      </div>
    </div>
    <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:4px;">Create Account →</button>
    <p style="font-size:12px;color:var(--text3);text-align:center;margin-top:14px;">By joining you agree to our Terms &amp; Privacy Policy.</p>
  </form>
</div></div></div>
</body></html>
