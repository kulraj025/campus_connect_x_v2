<?php if(!isset($pageTitle))$pageTitle='Dashboard'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="csrf" content="<?=csrf()?>">
<title><?=clean($pageTitle)?> — Campus Connect X</title>
<link rel="stylesheet" href="<?=BASE_URL?>/assets/css/app.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head><body>
<div class="sb-overlay" id="sb-overlay"></div>
<aside class="sidebar" id="sidebar">
  <a href="<?=BASE_URL?>/dashboard.php" class="sb-logo">
    <div class="sb-logo-icon">🎓</div>
    <div class="sb-logo-text"><strong>Campus Connect X</strong><span>Student Ecosystem</span></div>
  </a>
  <?php $p=basename($_SERVER['PHP_SELF']); ?>
  <div class="sb-section"><span class="sb-label">Main</span><nav class="sb-nav">
    <a href="<?=BASE_URL?>/dashboard.php" class="<?=$p==='dashboard.php'?'active':''?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>Dashboard</a>
    <a href="<?=BASE_URL?>/community.php" class="<?=$p==='community.php'?'active':''?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>Community<span class="sb-badge g">Live</span></a>
    <a href="<?=BASE_URL?>/abroad.php" class="<?=$p==='abroad.php'?'active':''?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>Abroad Hub<span class="sb-badge g">Live</span></a>
  </nav></div>
  <div class="sb-section"><span class="sb-label">Career</span><nav class="sb-nav">
    <a href="<?=BASE_URL?>/marketplace.php" class="<?=$p==='marketplace.php'?'active':''?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>Marketplace<span class="sb-badge g">Live</span></a>
    <a href="<?=BASE_URL?>/career.php" class="<?=$p==='career.php'?'active':''?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>Career Hub<span class="sb-badge g">Live</span></a>
    <a href="<?=BASE_URL?>/profile.php" class="<?=in_array($p,['profile.php','cv.php'])?'active':''?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profile & CV<span class="sb-badge g">Live</span></a>
  </nav></div>
  <div class="sb-footer">
    <a href="<?=BASE_URL?>/profile.php" class="sb-user">
      <div class="sb-avatar"><?=initials(auth()['name'])?></div>
      <div><span class="sb-uname"><?=clean(auth()['name'])?></span><span class="sb-uuni"><?=clean(auth()['university']??'Student')?></span></div>
    </a>
    <form method="POST" action="<?=BASE_URL?>/logout.php">
      <input type="hidden" name="csrf" value="<?=csrf()?>">
      <button type="submit" class="sb-logout"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>Sign out</button>
    </form>
  </div>
</aside>
<div class="main">
<header class="topbar">
  <button class="hamburger" id="hamburger"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
  <h1 class="topbar-title"><?=clean($pageTitle)?></h1>
  <div class="topbar-right">
    <button class="topbar-btn"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg><span class="ndot"></span></button>
    <a href="<?=BASE_URL?>/profile.php" style="display:flex;align-items:center;gap:8px;padding:4px 10px;border-radius:var(--r);transition:var(--t);" onmouseover="this.style.background='var(--surface2)'" onmouseout="this.style.background='none'">
      <div class="sb-avatar" style="width:30px;height:30px;font-size:11px;"><?=initials(auth()['name'])?></div>
      <span style="font-size:13px;font-weight:500;color:var(--text2);"><?=clean(explode(' ',auth()['name'])[0])?></span>
    </a>
  </div>
</header>
<?php if($s=getFlash('success')): ?><div style="padding:12px 28px 0"><div class="alert al-s" data-auto>✓ <?=clean($s)?></div></div><?php endif; ?>
<?php if($e=getFlash('error')): ?><div style="padding:12px 28px 0"><div class="alert al-e" data-auto>✕ <?=clean($e)?></div></div><?php endif; ?>
<div class="page">
