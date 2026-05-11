<?php
// login.php
require_once 'includes/config.php';
if(isLoggedIn()){header('Location:'.BASE_URL.'/dashboard.php');exit;}
$error='';$old=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $email=filter_var(trim($_POST['email']??''),FILTER_SANITIZE_EMAIL);
    $pass=$_POST['password']??'';$old=['email'=>$email];
    if(empty($email)||empty($pass)){$error='Enter your email and password.';}
    else{
        $stmt=db()->prepare("SELECT * FROM users WHERE email=? LIMIT 1");$stmt->execute([$email]);$user=$stmt->fetch();
        if($user&&password_verify($pass,$user['password'])){
            $_SESSION['user']=$user;session_regenerate_id(true);
            flash('success','Welcome back, '.$user['name'].'!');
            header('Location:'.BASE_URL.'/dashboard.php');exit;
        }else{$error='Invalid email or password.';}
    }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Sign In — Campus Connect X</title><link rel="stylesheet" href="<?=BASE_URL?>/assets/css/app.css"></head><body>
<div class="auth-wrap">
<div class="auth-left">
  <a href="<?=BASE_URL?>" class="auth-brand"><div class="auth-brand-icon">🎓</div>Campus Connect X</a>
  <div class="auth-hero"><h1>Welcome <span>back</span> to your campus.</h1>
  <p>Sign in to access your dashboard, connect with peers, and continue building your student career.</p></div>
  <div style="font-size:12px;color:#1E293B;">© <?=date('Y')?> Campus Connect X</div>
</div>
<div class="auth-right"><div class="auth-box">
  <h2>Sign in</h2>
  <p class="auth-sub">New here? <a href="<?=BASE_URL?>/register.php">Create an account</a></p>
  <?php if($error):?><div class="alert al-e"><?=clean($error)?></div><?php endif;?>
  <form method="POST" novalidate>
    <input type="hidden" name="csrf" value="<?=csrf()?>">
    <div class="form-group"><label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" placeholder="you@university.edu" value="<?=clean($old['email']??'')?>" required autofocus></div>
    <div class="form-group"><label class="form-label">Password
        <a href="#" style="color:var(--brand);font-size:12px;font-weight:500;">Forgot password?</a></label>
      <input type="password" name="password" class="form-control" placeholder="Enter your password" required></div>
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
      <input type="checkbox" id="rem" style="width:15px;height:15px;accent-color:var(--brand);">
      <label for="rem" style="font-size:13px;color:var(--text2);cursor:pointer;">Remember me</label>
    </div>
    <button type="submit" class="btn btn-primary btn-full btn-lg">Sign In →</button>
  </form>
</div></div></div>
</body></html>
