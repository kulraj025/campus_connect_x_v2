<?php if(!isset($pageTitle)) $pageTitle='Dashboard'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="csrf" content="<?=csrf()?>">
<title><?=clean($pageTitle)?> — Campus Connect X</title>
<link rel="stylesheet" href="<?=BASE_URL?>/assets/css/app.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
<style>
/* ── Wider sidebar ────────────────────────────────── */
:root{ --sw:240px; }

/* ── Logo ─────────────────────────────────────────── */
.sb-logo{ padding:20px 16px 14px; border-bottom:1px solid rgba(255,255,255,.06); display:flex; align-items:center; gap:10px; text-decoration:none; }

/* ── Profile mini-card ────────────────────────────── */
.sb-profile{
  margin:14px 12px 0;
  background:rgba(255,255,255,.04);
  border:1px solid rgba(255,255,255,.07);
  border-radius:12px;
  overflow:hidden;
}
.sb-profile-banner{
  height:52px;
  background:linear-gradient(135deg,var(--brand),var(--accent));
  position:relative;
}
.sb-profile-av{
  position:absolute; bottom:-22px; left:50%; transform:translateX(-50%);
  width:44px; height:44px; border-radius:50%;
  border:2.5px solid #0F172A;
  background:linear-gradient(135deg,var(--brand),var(--accent));
  display:flex; align-items:center; justify-content:center;
  font-size:15px; font-weight:800; color:#fff; overflow:hidden;
}
.sb-profile-body{ padding:30px 12px 12px; text-align:center; }
.sb-profile-name{ font-size:12.5px; font-weight:700; color:#E2E8F0; margin-bottom:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sb-profile-role{ font-size:10px; color:#475569; margin-bottom:10px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sb-profile-stats{ display:flex; justify-content:space-around; padding:8px 0; border-top:1px solid rgba(255,255,255,.06); border-bottom:1px solid rgba(255,255,255,.06); margin-bottom:10px; }
.sb-pstat-val{ font-size:14px; font-weight:800; color:var(--brand); font-family:var(--fd); }
.sb-pstat-lbl{ font-size:9px; color:#475569; text-transform:uppercase; letter-spacing:.4px; }

/* ── Sidebar nav links ────────────────────────────── */
.sb-nav-group{ padding:10px 10px 4px; }
.sb-nav-label{ font-size:9px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#334155; padding:0 6px; margin-bottom:4px; display:block; }
.sb-nav{ display:flex; flex-direction:column; gap:1px; }
.sb-nav a{
  display:flex; align-items:center; gap:9px;
  padding:8px 10px; border-radius:8px;
  color:var(--sb-text); font-size:12.5px; font-weight:500;
  text-decoration:none; transition:var(--t); position:relative;
}
.sb-nav a:hover{ background:var(--sb-hover); color:#E2E8F0; }
.sb-nav a.active{ background:rgba(37,99,235,.2); color:#93C5FD; font-weight:600; }
.sb-nav a.active::before{ content:''; position:absolute; left:0; top:20%; bottom:20%; width:3px; background:var(--brand); border-radius:0 3px 3px 0; }
.sb-nav svg{ width:15px; height:15px; flex-shrink:0; opacity:.6; }
.sb-nav a:hover svg,.sb-nav a.active svg{ opacity:1; }
.sb-badge2{ margin-left:auto; font-size:9px; font-weight:700; background:var(--success); color:#fff; padding:1px 6px; border-radius:20px; }
.sb-unread{ margin-left:auto; background:var(--brand); color:#fff; font-size:9px; font-weight:800; padding:1px 6px; border-radius:20px; }

/* ── Sidebar footer: sign out ─────────────────────── */
.sb-footer-actions{ margin-top:auto; padding:10px 10px 14px; border-top:1px solid rgba(255,255,255,.06); }
.sb-signout{
  width:100%; background:rgba(255,255,255,.04);
  border:1px solid rgba(255,255,255,.07); color:#64748B;
  font-size:11px; padding:8px 10px; border-radius:8px;
  cursor:pointer; display:flex; align-items:center; gap:6px; transition:.15s;
}
.sb-signout:hover{ background:var(--sb-hover); color:#94A3B8; }

/* ── Topbar: nav tabs ─────────────────────────────── */
.topbar{ height:var(--hh); background:var(--surface); border-bottom:1px solid var(--border); display:flex; align-items:center; padding:0 20px; gap:2px; position:sticky; top:0; z-index:100; }
.topbar-title{ display:none; }
.top-nav{ display:flex; align-items:center; gap:1px; flex:1; overflow-x:auto; scrollbar-width:none; }
.top-nav::-webkit-scrollbar{ display:none; }
.top-nav a{
  display:flex; align-items:center; gap:5px; flex-shrink:0;
  padding:6px 11px; border-radius:var(--r);
  font-size:12.5px; font-weight:500; color:var(--text2);
  text-decoration:none; white-space:nowrap; transition:var(--t); position:relative;
}
.top-nav a:hover{ background:var(--surface2); color:var(--text); }
.top-nav a.active{ color:var(--brand); font-weight:700; }
.top-nav a.active::after{ content:''; position:absolute; bottom:-1px; left:8%; right:8%; height:2px; background:var(--brand); border-radius:2px; }
.top-nav svg{ width:14px; height:14px; flex-shrink:0; opacity:.65; }
.top-nav a:hover svg,.top-nav a.active svg{ opacity:1; }
.tnav-live{ font-size:8px; font-weight:800; background:#10B981; color:#fff; padding:1px 5px; border-radius:20px; margin-left:2px; letter-spacing:.3px; }

.topbar-right{ display:flex; align-items:center; gap:8px; margin-left:auto; }
.tbar-icon{ width:34px; height:34px; border-radius:var(--r); background:var(--surface2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; color:var(--text2); transition:var(--t); position:relative; text-decoration:none; }
.tbar-icon:hover{ background:var(--border); }
.tbar-icon svg{ width:16px; height:16px; }

@media(max-width:900px){
  :root{--sw:0px}
  .sidebar{transform:translateX(-260px)}
  .sidebar.open{transform:translateX(0);width:260px}
  .hamburger{display:flex!important}
  .top-nav a span.nav-label{ display:none }
}
</style>
</head>
<body>
<div class="sb-overlay" id="sb-overlay"></div>

<?php
$__u  = auth();
$__p  = basename($_SERVER['PHP_SELF']);

// Sidebar stats
$__posts = (int)(db()->prepare("SELECT COUNT(*) FROM posts WHERE user_id=?")->execute([$__u['id']]) ? db()->prepare("SELECT COUNT(*) FROM posts WHERE user_id=?")->execute([$__u['id']]) : 0);
$__stmtP = db()->prepare("SELECT COUNT(*) FROM posts WHERE user_id=?"); $__stmtP->execute([$__u['id']]); $__posts=$__stmtP->fetchColumn();
$__stmtF = db()->prepare("SELECT COUNT(*) FROM follows WHERE follower_id=?"); $__stmtF->execute([$__u['id']]); $__following=$__stmtF->fetchColumn();
$__stmtFw= db()->prepare("SELECT COUNT(*) FROM follows WHERE following_id=?");$__stmtFw->execute([$__u['id']]);$__followers=$__stmtFw->fetchColumn();
$__stmtM = db()->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0"); $__stmtM->execute([$__u['id']]); $__unreadMsg=$__stmtM->fetchColumn();
$__stmtN = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0"); $__stmtN->execute([$__u['id']]); $__unreadN=$__stmtN->fetchColumn();
?>

<!-- ══ SIDEBAR ══════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">

  <!-- Logo -->
  <a href="<?=BASE_URL?>/dashboard.php" class="sb-logo">
    <div class="sb-logo-icon">🎓</div>
    <div class="sb-logo-text">
      <strong>Campus Connect X</strong>
      <span>Student Ecosystem</span>
    </div>
  </a>

  <!-- Profile mini-card -->
  <div class="sb-profile">
    <div class="sb-profile-banner">
      <div class="sb-profile-av">
        <?php if(!empty($__u['avatar'])&&file_exists(__DIR__.'/../'.$__u['avatar'])):?>
          <img src="<?=BASE_URL.'/'.$__u['avatar']?>" style="width:100%;height:100%;object-fit:cover;" alt="">
        <?php else: echo initials($__u['name']); endif;?>
      </div>
    </div>
    <div class="sb-profile-body">
      <div class="sb-profile-name"><?=clean($__u['name'])?><?php if(!empty($__u['is_verified'])):?> <span style="color:#10B981;font-size:10px;">✓</span><?php endif;?></div>
      <div class="sb-profile-role"><?=clean($__u['university']??'Campus Connect X')?></div>
      <div class="sb-profile-stats">
        <div><div class="sb-pstat-val"><?=$__posts?></div><div class="sb-pstat-lbl">Posts</div></div>
        <div><div class="sb-pstat-val"><?=$__following?></div><div class="sb-pstat-lbl">Following</div></div>
        <div><div class="sb-pstat-val"><?=$__followers?></div><div class="sb-pstat-lbl">Followers</div></div>
      </div>
      <div class="sb-nav">
        <a href="<?=BASE_URL?>/profile.php" class="<?=$__p==='profile.php'?'active':''?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          My Profile
        </a>
        <a href="<?=BASE_URL?>/cv.php" class="<?=$__p==='cv.php'?'active':''?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          CV Builder
        </a>
        <a href="<?=BASE_URL?>/messages.php" class="<?=$__p==='messages.php'?'active':''?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          Messages
          <?php if($__unreadMsg):?><span class="sb-unread"><?=$__unreadMsg?></span><?php endif;?>
        </a>
        <a href="<?=BASE_URL?>/notifications.php" class="<?=$__p==='notifications.php'?'active':''?>">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          Notifications
          <?php if($__unreadN):?><span class="sb-unread"><?=$__unreadN?></span><?php endif;?>
        </a>
        <?php if(!empty($__u['is_admin'])):?>
        <a href="<?=BASE_URL?>/admin_ads.php" class="<?=$__p==='admin_ads.php'?'active':''?>" style="color:#FBBF24;">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Admin Panel
        </a>
        <?php endif;?>
      </div>
    </div>
  </div>

  <!-- Quick Links — boxed grid like screenshot -->
  <div style="margin:14px 12px 0;">
    <div style="font-size:9px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#334155;margin-bottom:8px;padding:0 2px;">Quick Links</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
      <?php
      $__ql = [
        ['dashboard.php',  '🏠', 'Dashboard'],
        ['community.php',  '👥', 'Community'],
        ['abroad.php',     '🌍', 'Abroad'],
        ['marketplace.php','💼', 'Market'],
        ['career.php',     '🚀', 'Career'],
        ['search.php',     '🔍', 'Search'],
      ];
      foreach($__ql as [$__file,$__icon,$__label]):
        $__isActive = $__p===$__file;
      ?>
      <a href="<?=BASE_URL?>/<?=$__file?>"
         style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;
                padding:10px 4px;border-radius:10px;text-decoration:none;font-size:10px;font-weight:600;
                background:<?=$__isActive?'rgba(37,99,235,.2)':'rgba(255,255,255,.04)'?>;
                border:1px solid <?=$__isActive?'rgba(37,99,235,.3)':'rgba(255,255,255,.07)'?>;
                color:<?=$__isActive?'#93C5FD':'#94A3B8'?>;
                transition:.15s;"
         onmouseover="this.style.background='rgba(255,255,255,.08)';this.style.color='#E2E8F0';"
         onmouseout="this.style.background='<?=$__isActive?'rgba(37,99,235,.2)':'rgba(255,255,255,.04)'?>';this.style.color='<?=$__isActive?'#93C5FD':'#94A3B8'?>';">
        <span style="font-size:18px;line-height:1;"><?=$__icon?></span>
        <span><?=$__label?></span>
      </a>
      <?php endforeach;?>
    </div>
  </div>

  <!-- Sign out -->
  <div class="sb-footer-actions">
    <form method="POST" action="<?=BASE_URL?>/logout.php">
      <input type="hidden" name="csrf" value="<?=csrf()?>">
      <button type="submit" class="sb-signout">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        Sign out of Campus Connect X
      </button>
    </form>
  </div>

</aside>

<!-- ══ MAIN ═════════════════════════════════════════ -->
<div class="main">

<!-- ── Topbar with feature tabs ──────────────────── -->
<header class="topbar">
  <button class="hamburger" id="hamburger" style="display:none;margin-right:8px;">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>

  <nav class="top-nav">
    <a href="<?=BASE_URL?>/dashboard.php" class="<?=$__p==='dashboard.php'?'active':''?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      <span class="nav-label">Dashboard</span>
    </a>
    <a href="<?=BASE_URL?>/community.php" class="<?=$__p==='community.php'?'active':''?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
      <span class="nav-label">Community</span><span class="tnav-live">Live</span>
    </a>
    <a href="<?=BASE_URL?>/abroad.php" class="<?=$__p==='abroad.php'?'active':''?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10z"/></svg>
      <span class="nav-label">Abroad Hub</span><span class="tnav-live">Live</span>
    </a>
    <a href="<?=BASE_URL?>/marketplace.php" class="<?=$__p==='marketplace.php'?'active':''?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      <span class="nav-label">Marketplace</span><span class="tnav-live">Live</span>
    </a>
    <a href="<?=BASE_URL?>/career.php" class="<?=$__p==='career.php'?'active':''?>">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
      <span class="nav-label">Career Hub</span><span class="tnav-live">Live</span>
    </a>
  </nav>

  <div class="topbar-right">
    <a href="<?=BASE_URL?>/notifications.php" class="tbar-icon" title="Notifications">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      <?php if($__unreadN):?><span style="position:absolute;top:-3px;right:-3px;background:#EF4444;color:#fff;font-size:9px;font-weight:800;min-width:15px;height:15px;border-radius:20px;display:flex;align-items:center;justify-content:center;padding:0 3px;"><?=$__unreadN?></span><?php endif;?>
    </a>
    <a href="<?=BASE_URL?>/profile.php" style="display:flex;align-items:center;gap:7px;padding:4px 10px;border-radius:var(--r);text-decoration:none;transition:var(--t);" onmouseover="this.style.background='var(--surface2)'" onmouseout="this.style.background=''">
      <div class="sb-avatar" style="width:28px;height:28px;font-size:10px;overflow:hidden;flex-shrink:0;">
        <?php if(!empty($__u['avatar'])&&file_exists(__DIR__.'/../'.$__u['avatar'])):?>
          <img src="<?=BASE_URL.'/'.$__u['avatar']?>" style="width:100%;height:100%;object-fit:cover;" alt="">
        <?php else: echo initials($__u['name']); endif;?>
      </div>
      <span style="font-size:12.5px;font-weight:500;color:var(--text2);"><?=clean(explode(' ',$__u['name'])[0])?></span>
    </a>
  </div>
</header>

<?php if($s=getFlash('success')):?><div style="padding:10px 24px 0"><div class="alert al-s" data-auto>✓ <?=clean($s)?></div></div><?php endif;?>
<?php if($e=getFlash('error')):  ?><div style="padding:10px 24px 0"><div class="alert al-e" data-auto>✕ <?=clean($e)?></div></div><?php endif;?>
<div class="page">