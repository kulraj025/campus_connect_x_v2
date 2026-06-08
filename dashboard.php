<?php
require_once 'includes/config.php';
requireAuth();
$user = auth();

// Stats
$pc = db()->prepare("SELECT COUNT(*) FROM posts WHERE user_id=?");       $pc->execute([$user['id']]); $pc=$pc->fetchColumn();
$tc = db()->prepare("SELECT COUNT(*) FROM abroad_tips WHERE user_id=?"); $tc->execute([$user['id']]); $tc=$tc->fetchColumn();
$sc = db()->prepare("SELECT COUNT(*) FROM services WHERE user_id=?");    $sc->execute([$user['id']]); $sc=$sc->fetchColumn();
$jc = db()->prepare("SELECT COUNT(*) FROM jobs WHERE is_active=1");      $jc->execute();              $jc=$jc->fetchColumn();
$fc = db()->prepare("SELECT COUNT(*) FROM follows WHERE follower_id=?"); $fc->execute([$user['id']]); $fc=$fc->fetchColumn();
$ff = db()->prepare("SELECT COUNT(*) FROM follows WHERE following_id=?");$ff->execute([$user['id']]); $ff=$ff->fetchColumn();

// Profile
$ps = db()->prepare("SELECT * FROM profiles WHERE user_id=?"); $ps->execute([$user['id']]);
$profile = $ps->fetch() ?: [];
$skills  = json_decode($profile['skills'] ?? '[]', true) ?: [];

// Feed posts
$page = max(1,(int)($_GET['page']??1));
$rp   = db()->prepare("SELECT p.*,u.name,u.university,u.avatar,u.id as uid FROM posts p JOIN users u ON p.user_id=u.id ORDER BY p.created_at DESC LIMIT 10 OFFSET ".(($page-1)*10));
$rp->execute(); $rp=$rp->fetchAll();

// Liked post IDs
$likedIds = [];
if (!empty($rp)) {
    $ids = implode(',', array_column($rp,'id'));
    $lq  = db()->prepare("SELECT post_id FROM post_likes WHERE user_id=? AND post_id IN($ids)");
    $lq->execute([$user['id']]);
    $likedIds = array_column($lq->fetchAll(),'post_id');
}

// Active ads for dashboard
$ads = db()->prepare("SELECT * FROM ads WHERE is_active=1 AND (show_on='dashboard' OR show_on='all') AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW()) ORDER BY sort_order ASC, created_at DESC LIMIT 5");
$ads->execute(); $ads=$ads->fetchAll();

// Suggested connections
$sug = db()->prepare("SELECT u.* FROM users u WHERE u.id!=? AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id=?) ORDER BY RAND() LIMIT 4");
$sug->execute([$user['id'],$user['id']]); $sug=$sug->fetchAll();

// Unread messages count
$unreadMsg = db()->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0");
$unreadMsg->execute([$user['id']]); $unreadMsg=$unreadMsg->fetchColumn();

$pageTitle='Dashboard';
include 'includes/header.php';
?>

<style>
/* ── 3-column dashboard layout ─────────────────────── */
.dash-layout {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 20px;
  align-items: start;
}

/* ── Left sidebar: profile card ─────────────────────── */
.profile-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--rl);
  overflow: hidden;
  position: sticky;
  top: 20px;
}
.profile-card-banner {
  height: 70px;
  background: linear-gradient(135deg, var(--brand), var(--accent));
  position: relative;
}
.profile-card-av {
  position: absolute;
  bottom: -28px;
  left: 50%;
  transform: translateX(-50%);
  width: 56px; height: 56px;
  border-radius: 50%;
  border: 3px solid var(--surface);
  overflow: hidden;
  background: linear-gradient(135deg, var(--brand), var(--accent));
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; font-weight: 800; color: #fff;
  font-family: var(--fd);
}
.profile-card-body { padding: 36px 16px 16px; text-align: center; }
.profile-card-name { font-size: 15px; font-weight: 800; color: var(--text); margin-bottom: 3px; }
.profile-card-uni  { font-size: 11px; color: var(--text3); margin-bottom: 12px; }
.profile-card-stats { display: flex; justify-content: space-around; padding: 12px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); margin-bottom: 14px; }
.pcs-item { text-align: center; }
.pcs-val  { font-size: 16px; font-weight: 800; color: var(--brand); font-family: var(--fd); }
.pcs-lbl  { font-size: 10px; color: var(--text3); font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
.profile-card-skills { display: flex; flex-wrap: wrap; gap: 5px; justify-content: center; margin-bottom: 14px; }
.profile-card-links { display: flex; flex-direction: column; gap: 6px; }
.pc-link { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: var(--r); font-size: 12px; font-weight: 600; color: var(--text2); text-decoration: none; background: var(--surface2); transition: var(--t); }
.pc-link:hover { background: var(--border); color: var(--text); }
.pc-link.primary { background: var(--brand); color: #fff; }
.pc-link.primary:hover { opacity: .88; }

/* ── Centre: feed ──────────────────────────────────── */
.create-post-dash {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--rl);
  padding: 16px;
  margin-bottom: 16px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
}
.post-img-full {
  width: 100%;
  height: auto;
  display: block;
  max-height: 520px;
  object-fit: contain;
  background: var(--surface2);
}

/* ── Right sidebar: ads + suggestions ──────────────── */
.right-sidebar { position: sticky; top: 20px; display: flex; flex-direction: column; gap: 16px; }

/* Ad card in sidebar */
.dash-ad-card {
  border-radius: var(--rl);
  overflow: hidden;
  position: relative;
  min-height: 140px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  cursor: pointer;
  transition: transform .2s, box-shadow .2s;
  text-decoration: none;
}
.dash-ad-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.12); }
.dash-ad-img {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  object-fit: cover; z-index: 0;
}
.dash-ad-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,.8) 0%, rgba(0,0,0,.2) 60%, transparent 100%);
  z-index: 1;
}
.dash-ad-content { position: relative; z-index: 2; padding: 14px; }
.dash-ad-type { font-size: 9px; font-weight: 800; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: .8px; margin-bottom: 4px; }
.dash-ad-title { font-size: 13px; font-weight: 800; color: #fff; line-height: 1.3; margin-bottom: 4px; }
.dash-ad-cta { font-size: 11px; color: rgba(255,255,255,.75); font-weight: 600; }

/* Ad slideshow (multiple ads) */
.dash-ad-wrap { position: relative; border-radius: var(--rl); overflow: hidden; }
.dash-ad-slide { display: none; }
.dash-ad-slide.active { display: flex; flex-direction: column; justify-content: flex-end; }
.dash-ad-dots { display: flex; gap: 5px; justify-content: center; padding: 8px; background: var(--surface); }
.dash-ad-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--border); border: none; cursor: pointer; transition: .2s; }
.dash-ad-dot.active { background: var(--brand); transform: scale(1.3); }

/* Suggestion card */
.sugg-item { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--border); }
.sugg-item:last-child { border-bottom: none; }
.sugg-name { font-size: 13px; font-weight: 600; color: var(--text); }
.sugg-dept { font-size: 11px; color: var(--text3); }
.sugg-btn  { margin-left: auto; font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 20px; border: 1.5px solid var(--brand); color: var(--brand); background: none; cursor: pointer; transition: var(--t); white-space: nowrap; }
.sugg-btn:hover,.sugg-btn.following { background: var(--brand); color: #fff; }

/* Quick links */
.quick-links { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.ql-item { display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 12px 8px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); text-decoration: none; color: var(--text2); font-size: 11px; font-weight: 600; transition: var(--t); text-align: center; }
.ql-item:hover { border-color: var(--brand); color: var(--brand); background: #F5F3FF; }
.ql-icon { font-size: 20px; }

/* Right sidebar visible on all screens — stacks below feed on mobile */
@media(max-width:1200px){ .dash-layout{ grid-template-columns:1fr; } }
@media(min-width:1201px){ .dash-layout{ grid-template-columns:1fr 300px; } }
</style>

<div class="dash-layout">

  <!-- ══ CENTRE: Feed ══════════════════════════════════ -->
  <div>

    <!-- Create post -->
    <div class="create-post-dash">
      <?php if (!empty($user['avatar']) && file_exists(__DIR__.'/'.$user['avatar'])): ?>
        <img src="<?= BASE_URL.'/'.$user['avatar'] ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0;" alt="">
      <?php else: ?>
        <div class="cp-av"><?= initials($user['name']) ?></div>
      <?php endif; ?>
      <div style="flex:1;">
        <form method="POST" action="<?= BASE_URL ?>/community.php" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <textarea name="body" class="cp-input" placeholder="What's on your mind, <?= clean(explode(' ',$user['name'])[0]) ?>? Share with your campus..." data-max="1000" required style="width:100%;box-sizing:border-box;"></textarea>
          <div id="dash-img-preview" style="display:none;margin-top:8px;position:relative;">
            <img id="dash-img-thumb" style="max-width:100%;height:auto;border-radius:8px;border:1px solid var(--border);">
            <button type="button" onclick="clearDashImg()" style="position:absolute;top:6px;right:6px;background:rgba(0,0,0,.55);border:none;color:#fff;border-radius:50%;width:22px;height:22px;cursor:pointer;font-size:14px;">×</button>
          </div>
          <div class="cp-actions" style="margin-top:10px;">
            <select name="tag" class="form-control" style="width:auto;padding:6px 10px;font-size:12px;">
              <option value="community">💬 Community</option>
              <option value="general">📢 General</option>
              <option value="abroad">🌍 Abroad</option>
              <option value="skill">🎯 Skill</option>
            </select>
            <label for="dash-img-inp" style="display:flex;align-items:center;gap:4px;padding:6px 10px;background:var(--surface2);border:1px solid var(--border);border-radius:var(--r);font-size:12px;font-weight:500;color:var(--text2);cursor:pointer;">
              📷 Photo
            </label>
            <input type="file" id="dash-img-inp" name="post_image" accept="image/*" style="display:none;" onchange="previewDashImg(this)">
            <button type="submit" class="btn btn-primary btn-sm" style="margin-left:auto;">Post →</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Feed -->
    <?php if (empty($rp)): ?>
    <div class="card">
      <div class="empty-state">
        <span class="icon">📢</span>
        <h3>No posts yet</h3>
        <p>Be the first to share something! Or <a href="<?= BASE_URL ?>/community.php">visit Community</a>.</p>
      </div>
    </div>
    <?php else: foreach ($rp as $post):
      $isLiked = in_array($post['id'], $likedIds);
    ?>
    <div class="post-card" id="post-<?= $post['id'] ?>" style="margin-bottom:14px;">
      <div class="post-header">
        <?= avatarHtml(['id'=>$post['uid'],'name'=>$post['name'],'avatar'=>$post['avatar']??''], 40) ?>
        <div>
          <div class="post-author"><?= clean($post['name']) ?>
            <?php if($post['uid']==$user['id']):?><span style="font-size:10px;color:var(--brand);margin-left:5px;">You</span><?php endif;?>
          </div>
          <div class="post-meta"><?= clean($post['university']??'Student') ?> · <?= ago($post['created_at']) ?></div>
        </div>
        <span class="post-tag pt-<?= $post['tag'] ?>"><?= ucfirst($post['tag']) ?></span>
      </div>

      <!-- Image: natural size, no crop -->
      <?php if (!empty($post['image_path'])): ?>
      <div style="background:var(--surface2);text-align:center;line-height:0;">
        <img src="<?= BASE_URL.'/'.$post['image_path'] ?>" class="post-img-full" alt="" loading="lazy">
      </div>
      <?php endif; ?>

      <div class="post-body"><?= nl2br(clean($post['body'])) ?></div>
      <div class="post-footer">
        <button class="post-action like-btn <?= $isLiked?'liked':'' ?>" data-id="<?= $post['id'] ?>">
          <svg fill="<?= $isLiked?'currentColor':'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          <span class="lcount"><?= $post['likes_count'] ?></span>
        </button>
        <?php if($post['user_id']==$user['id']): ?>
        <form method="POST" action="api/del.php" style="margin-left:auto;" onsubmit="return confirm('Delete post?')">
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <input type="hidden" name="type" value="post">
          <input type="hidden" name="id"   value="<?= $post['id'] ?>">
          <button type="submit" class="post-action" style="color:var(--danger);">🗑</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; endif; ?>

    <div style="text-align:center;margin-top:8px;">
      <a href="<?= BASE_URL ?>/community.php" class="btn btn-ghost">See all posts in Community →</a>
    </div>
  </div>

  <!-- ══ RIGHT: Ads + Suggestions ═════════════════════ -->
  <div class="right-sidebar">

    <!-- ── Responsive Ads: clickable, any image size, all screens ── -->
    <?php if (!empty($ads)): ?>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--rl);overflow:hidden;">

      <!-- Header -->
      <div style="padding:9px 14px 8px;font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.6px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
        <span>📢 Notices &amp; Sponsored</span>
        <span style="font-size:9px;background:var(--surface2);color:var(--text3);padding:1px 7px;border-radius:4px;border:1px solid var(--border);font-weight:600;">Ad</span>
      </div>

      <!-- Slides -->
      <div id="dAdWrap" style="position:relative;">
        <?php foreach($ads as $ai=>$ad):
          $hasImg = !empty($ad['image_path']) && file_exists(__DIR__.'/'.$ad['image_path']);
        ?>

        <!-- Each slide IS the clickable link to ad_detail.php -->
        <a href="<?= BASE_URL ?>/ad_detail.php?id=<?= $ad['id'] ?>"
           data-dai="<?= $ai ?>"
           style="display:<?= $ai===0?'block':'none' ?>;
                  text-decoration:none;
                  position:relative;
                  overflow:hidden;
                  background:<?= clean($ad['bg_color']) ?>;
                  transition:opacity .2s;"
           onmouseover="this.style.opacity='.92'"
           onmouseout="this.style.opacity='1'">

          <?php if($hasImg): ?>
            <!-- Image: full width, auto height, object-fit:contain = NO CROP -->
            <img src="<?= BASE_URL.'/'.$ad['image_path'] ?>"
                 style="display:block;width:100%;height:auto;object-fit:contain;background:<?= clean($ad['bg_color']) ?>;"
                 alt="<?= clean($ad['title']) ?>">
            <!-- Text overlay on image -->
            <div style="position:absolute;bottom:0;left:0;right:0;z-index:2;
                        background:linear-gradient(to top,rgba(0,0,0,.85) 0%,rgba(0,0,0,.3) 55%,transparent 100%);
                        padding:clamp(10px,2vw,16px) clamp(10px,2vw,14px) clamp(10px,1.5vw,14px);">
              <div style="font-size:clamp(8px,1.1vw,9px);font-weight:800;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.7px;margin-bottom:3px;"><?= ucfirst(clean($ad['type'])) ?> <?= clean($ad['emoji']) ?></div>
              <div style="font-size:clamp(12px,1.6vw,14px);font-weight:800;color:#fff;line-height:1.3;margin-bottom:4px;"><?= clean($ad['title']) ?></div>
              <?php if(!empty($ad['cta_text'])): ?>
              <div style="font-size:clamp(9px,1.2vw,11px);color:rgba(255,255,255,.75);font-weight:600;">→ <?= clean($ad['cta_text']) ?></div>
              <?php endif; ?>
              <div style="font-size:clamp(8px,1vw,10px);color:rgba(255,255,255,.4);margin-top:4px;">Tap to view details</div>
            </div>

          <?php else: ?>
            <!-- Colour slide with animated shapes -->
            <div style="min-height:clamp(140px,18vw,180px);position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:flex-end;">
              <!-- Shapes -->
              <div style="position:absolute;inset:0;overflow:hidden;">
                <div style="position:absolute;width:55%;padding-bottom:55%;border-radius:50%;background:rgba(255,255,255,.06);top:-15%;right:-8%;animation:dAdFloat 7s ease-in-out infinite;"></div>
                <div style="position:absolute;width:35%;padding-bottom:35%;border-radius:50%;background:rgba(255,255,255,.06);bottom:5%;left:-6%;animation:dAdFloat 7s ease-in-out infinite;animation-delay:-3s;"></div>
              </div>
              <!-- Gradient -->
              <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.7) 0%,transparent 70%);"></div>
              <!-- Text -->
              <div style="position:relative;z-index:2;padding:clamp(12px,2vw,18px) clamp(12px,2vw,16px) clamp(12px,1.5vw,14px);">
                <div style="font-size:clamp(8px,1.1vw,9px);font-weight:800;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.7px;margin-bottom:3px;"><?= ucfirst(clean($ad['type'])) ?> <?= clean($ad['emoji']) ?></div>
                <div style="font-size:clamp(12px,1.6vw,14px);font-weight:800;color:#fff;line-height:1.3;margin-bottom:4px;"><?= clean($ad['title']) ?></div>
                <div style="font-size:clamp(10px,1.3vw,11px);color:rgba(255,255,255,.7);line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= clean(mb_substr($ad['body'],0,90)) ?></div>
                <?php if(!empty($ad['cta_text'])): ?>
                <div style="margin-top:8px;display:inline-block;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);color:#fff;font-size:clamp(9px,1.2vw,11px);font-weight:700;padding:4px 12px;border-radius:20px;"><?= clean($ad['cta_text']) ?></div>
                <?php endif; ?>
                <div style="font-size:clamp(8px,1vw,10px);color:rgba(255,255,255,.35);margin-top:5px;">Tap to view details</div>
              </div>
            </div>
          <?php endif; ?>

        </a>
        <?php endforeach; ?>

        <?php if(count($ads)>1): ?>
        <!-- Dot navigation -->
        <div id="dAdDots" style="display:flex;align-items:center;justify-content:center;gap:5px;padding:8px;background:var(--surface2);border-top:1px solid var(--border);">
          <?php foreach($ads as $ai=>$ad): ?>
          <button onclick="dAdGo(<?=$ai?>)"
                  data-dot="<?=$ai?>"
                  style="width:<?=$ai===0?'18px':'7px'?>;height:7px;border-radius:20px;
                         background:<?=$ai===0?'var(--brand)':'var(--border)'?>;
                         border:none;cursor:pointer;padding:0;transition:all .3s;"></button>
          <?php endforeach; ?>
        </div>
        <!-- Progress bar -->
        <div style="height:2px;background:var(--border);">
          <div id="dAdBar" style="height:100%;background:var(--brand);width:0%;transition:width linear;"></div>
        </div>
        <?php endif; ?>
      </div>

    </div>
    <?php endif; ?>

    <style>
    @keyframes dAdFloat {
      0%,100%{ transform:translateY(0) scale(1); }
      50%    { transform:translateY(-12px) scale(1.04); }
    }
    </style>

    <!-- Suggestions -->
    <?php if (!empty($sug)): ?>
    <div class="card">
      <div class="widget-title">👥 People You May Know</div>
      <div style="padding:0 14px 14px;">
        <?php foreach($sug as $su): ?>
        <div class="sugg-item">
          <?= avatarHtml($su, 34) ?>
          <div style="flex:1;min-width:0;">
            <div class="sugg-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= clean($su['name']) ?></div>
            <div class="sugg-dept"><?= clean($su['department']??$su['university']??'Student') ?></div>
          </div>
          <button class="sugg-btn" data-uid="<?= $su['id'] ?>" onclick="dashFollow(this)">Connect</button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Platform stats -->
    <div class="card">
      <div class="widget-title">📊 Platform Stats</div>
      <div style="padding:0 14px 14px;display:flex;flex-direction:column;gap:8px;">
        <?php
        $totalU = db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalJ = db()->query("SELECT COUNT(*) FROM jobs WHERE is_active=1")->fetchColumn();
        $totalS = db()->query("SELECT COUNT(*) FROM services WHERE is_active=1")->fetchColumn();
        $totalP = db()->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        ?>
        <?php foreach([['🎓','Students',$totalU],['🚀','Open Jobs',$totalJ],['💼','Services',$totalS],['📢','Posts',$totalP]] as [$ic,$lb,$vl]): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;">
          <span style="color:var(--text2);"><?= $ic ?> <?= $lb ?></span>
          <span style="font-weight:700;color:var(--brand);"><?= number_format($vl) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<script>
// Post image preview
function previewDashImg(input) {
  if (!input.files || !input.files[0]) return;
  const url = URL.createObjectURL(input.files[0]);
  document.getElementById('dash-img-thumb').src = url;
  document.getElementById('dash-img-preview').style.display = 'block';
}
function clearDashImg() {
  document.getElementById('dash-img-inp').value = '';
  document.getElementById('dash-img-preview').style.display = 'none';
}

// ── Dashboard Ad Slideshow: responsive, clickable, swipeable ──
(function(){
  const slides = document.querySelectorAll('[data-dai]');
  const dots   = document.querySelectorAll('[data-dot]');
  const bar    = document.getElementById('dAdBar');
  const wrap   = document.getElementById('dAdWrap');
  if (!slides.length || slides.length < 2) return;

  let cur = 0;
  const DUR = 6000;
  let timer;

  window.dAdGo = function(n) {
    // Hide current
    slides[cur].style.display = 'none';
    if (dots[cur]) {
      dots[cur].style.width  = '7px';
      dots[cur].style.background = 'var(--border)';
    }
    // Show next
    cur = (n + slides.length) % slides.length;
    slides[cur].style.display = 'block';
    if (dots[cur]) {
      dots[cur].style.width  = '18px';
      dots[cur].style.background = 'var(--brand)';
    }
    resetBar();
    clearTimeout(timer);
    timer = setTimeout(() => dAdGo(cur + 1), DUR);
  };

  function resetBar() {
    if (!bar) return;
    bar.style.transition = 'none';
    bar.style.width = '0%';
    requestAnimationFrame(() => requestAnimationFrame(() => {
      bar.style.transition = 'width ' + DUR + 'ms linear';
      bar.style.width = '100%';
    }));
  }

  // Pause on hover
  if (wrap) {
    wrap.addEventListener('mouseenter', () => {
      clearTimeout(timer);
      if (bar) bar.style.transition = 'none';
    });
    wrap.addEventListener('mouseleave', () => {
      timer = setTimeout(() => dAdGo(cur + 1), DUR);
      resetBar();
    });

    // Touch swipe
    let tx = 0;
    wrap.addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, {passive:true});
    wrap.addEventListener('touchend',   e => {
      const dx = e.changedTouches[0].clientX - tx;
      if (Math.abs(dx) > 40) dAdGo(dx < 0 ? cur+1 : cur-1);
    }, {passive:true});
  }

  // Start
  timer = setTimeout(() => dAdGo(1), DUR);
  resetBar();
})();

// Follow/connect
function dashFollow(btn) {
  const uid = btn.dataset.uid;
  btn.disabled = true;
  fetch('<?= BASE_URL ?>/api/follow.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({user_id: parseInt(uid)})
  })
  .then(r=>r.json())
  .then(d=>{
    if (d.success) {
      btn.textContent = d.following ? '✓ Following' : 'Connect';
      btn.classList.toggle('following', d.following);
    }
  })
  .finally(()=>{ btn.disabled=false; });
}
</script>

<?php include 'includes/footer.php'; ?>