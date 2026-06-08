<?php
require_once 'includes/config.php';
requireAuth();
$user = auth();

// Handle new post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['body'])) {
    verifyCsrf();
    if (!rateLimit('post_'.auth()['id'], 5, 60)) {
        flash('error', 'Too many posts. Wait a minute.');
        header('Location:'.BASE_URL.'/community.php'); exit;
    }
    $body = s($_POST['body'] ?? '');
    $tag  = in_array($_POST['tag'] ?? '', ['community','abroad','skill','general']) ? $_POST['tag'] : 'community';
    $imgPath = null;

    if (!empty($_FILES['post_image']['name']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES['post_image']['tmp_name']);
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        if (isset($allowed[$mime]) && $_FILES['post_image']['size'] <= 10*1024*1024) {
            $fn  = 'post_'.auth()['id'].'_'.time().'.'.$allowed[$mime];
            $dir = __DIR__.'/uploads/posts/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $dir.$fn)) {
                $imgPath = 'uploads/posts/'.$fn;
            }
        } else {
            flash('error', 'Image must be JPG/PNG/WEBP/GIF under 10MB.');
            header('Location:'.BASE_URL.'/community.php'); exit;
        }
    }

    if (strlen($body) >= 5 && strlen($body) <= 1000) {
        db()->prepare("INSERT INTO posts(user_id,body,tag,image_path)VALUES(?,?,?,?)")
           ->execute([auth()['id'], $body, $tag, $imgPath]);
        flash('success', 'Post published!');
    } else {
        flash('error', 'Post must be 5–1000 characters.');
    }
    header('Location:'.BASE_URL.'/community.php'); exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$tag  = in_array($_GET['tag'] ?? '', ['community','abroad','skill','general','']) ? ($_GET['tag'] ?? '') : '';
$w    = $tag ? "WHERE p.tag='$tag'" : '';

$r = paginate("SELECT p.*,u.name,u.university,u.id as uid,u.avatar FROM posts p JOIN users u ON p.user_id=u.id $w ORDER BY p.created_at DESC", [], $page, 10);
$posts = $r['items'];

// Liked post IDs
$likedIds = [];
if (!empty($posts)) {
    $ids = implode(',', array_column($posts, 'id'));
    $lq  = db()->prepare("SELECT post_id FROM post_likes WHERE user_id=? AND post_id IN($ids)");
    $lq->execute([auth()['id']]);
    $likedIds = array_column($lq->fetchAll(), 'post_id');
}

// Who current user follows (to correctly show Connect vs Connected)
$followingIds = [];
$fq = db()->prepare("SELECT following_id FROM follows WHERE follower_id=?");
$fq->execute([auth()['id']]);
$followingIds = array_column($fq->fetchAll(), 'following_id');

// Suggested students
$sug = db()->prepare("SELECT * FROM users WHERE id!=? ORDER BY RAND() LIMIT 5");
$sug->execute([auth()['id']]); $sug = $sug->fetchAll();

$pageTitle = 'Community';
include 'includes/header.php';
?>

<style>
/* ── Post image: any size, never cropped ── */
.post-img {
  display: block;
  width: 100%;
  height: auto;        /* preserves original aspect ratio */
  max-height: 600px;   /* reasonable cap so giant images don't overwhelm */
  object-fit: contain; /* show full image, no crop */
  background: var(--surface2);
  border-radius: 0;
}
.follow-btn-wrap .connected {
  border: 1.5px solid var(--success);
  color: var(--success);
  background: none;
  font-size: 11px;
  padding: 4px 12px;
  border-radius: 99px;
  cursor: default;
}
.follow-btn-wrap .connect {
  border: 1.5px solid var(--brand);
  color: var(--brand);
  background: none;
  font-size: 11px;
  padding: 4px 12px;
  border-radius: 99px;
  cursor: pointer;
  transition: var(--t);
}
.follow-btn-wrap .connect:hover { background: var(--brand); color: #fff; }
</style>

<div class="feed-layout">

  <!-- MAIN FEED -->
  <div>
    <!-- Create Post -->
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <div class="create-post">
        <?= avatarHtml($user, 40) ?>
        <div style="flex:1;">
          <textarea name="body" class="cp-input" placeholder="What's on your mind? Share with your campus..." data-max="1000" required></textarea>

          <!-- Image preview — natural size -->
          <div id="post-img-preview" style="display:none;margin-top:10px;position:relative;">
            <img id="post-img-thumb" style="max-width:100%;height:auto;border-radius:8px;border:1px solid var(--border);display:block;">
            <button type="button" onclick="clearPostImg()" style="position:absolute;top:6px;right:6px;background:rgba(0,0,0,.55);border:none;color:#fff;border-radius:50%;width:24px;height:24px;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;">×</button>
            <div id="img-dim-label" style="font-size:11px;color:var(--text3);margin-top:4px;"></div>
          </div>

          <div class="cp-actions">
            <select name="tag" class="form-control" style="width:auto;padding:7px 12px;font-size:12px;">
              <option value="community">💬 Community</option>
              <option value="abroad">🌍 Abroad</option>
              <option value="skill">🎯 Skill</option>
              <option value="general">📢 General</option>
            </select>

            <label for="post-img-input" style="display:flex;align-items:center;gap:5px;padding:7px 12px;background:var(--surface2);border:1px solid var(--border);border-radius:var(--r);font-size:12px;font-weight:500;color:var(--text2);cursor:pointer;transition:var(--t);" onmouseover="this.style.background='var(--border)'" onmouseout="this.style.background='var(--surface2)'">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
              Photo
            </label>
            <input type="file" id="post-img-input" name="post_image" accept="image/*" style="display:none;" onchange="previewPostImg(this)">
            <span style="font-size:11px;color:var(--text3);">JPG/PNG/WEBP/GIF · max 10MB</span>

            <button type="submit" class="btn btn-primary btn-sm" style="margin-left:auto;">Post →</button>
          </div>
        </div>
      </div>
    </form>

    <!-- Filter tabs -->
    <div class="filter-bar">
      <a href="community.php" class="btn btn-sm <?= !$tag?'btn-primary':'btn-ghost' ?>">All</a>
      <?php foreach(['community'=>'💬','abroad'=>'🌍','skill'=>'🎯','general'=>'📢'] as $t=>$i): ?>
        <a href="?tag=<?= $t ?>" class="btn btn-sm <?= $tag===$t?'btn-primary':'btn-ghost' ?>"><?= $i ?> <?= ucfirst($t) ?></a>
      <?php endforeach; ?>
    </div>

    <!-- Posts feed -->
    <div class="post-feed">
      <?php if (empty($posts)): ?>
        <div class="card"><div class="empty-state">
          <span class="icon">👋</span>
          <h3>No posts yet</h3>
          <p>Be the first to share something with your campus!</p>
        </div></div>
      <?php else: foreach ($posts as $post): ?>
      <div class="post-card" id="post-<?= $post['id'] ?>">
        <div class="post-header">
          <?= avatarHtml(['id'=>$post['uid'],'name'=>$post['name'],'avatar'=>$post['avatar']??''], 40) ?>
          <div>
            <div class="post-author"><?= clean($post['name']) ?>
              <?php if($post['uid'] === auth()['id']): ?><span style="font-size:10px;color:var(--brand);font-weight:600;margin-left:6px;">You</span><?php endif;?>
            </div>
            <div class="post-meta"><?= clean($post['university'] ?? 'Student') ?> · <?= ago($post['created_at']) ?></div>
          </div>
          <span class="post-tag pt-<?= $post['tag'] ?>"><?= ucfirst($post['tag']) ?></span>
        </div>

        <!-- Post image — full natural dimensions, no crop -->
        <?php if (!empty($post['image_path'])): ?>
        <div style="background:var(--surface2);text-align:center;">
          <img
            src="<?= BASE_URL.'/'.$post['image_path'] ?>"
            class="post-img"
            alt="Post image"
            loading="lazy"
          >
        </div>
        <?php endif; ?>

        <div class="post-body"><?= nl2br(clean($post['body'])) ?></div>

        <div class="post-footer">
          <button class="post-action like-btn <?= in_array($post['id'], $likedIds)?'liked':'' ?>" data-id="<?= $post['id'] ?>">
            <svg fill="<?= in_array($post['id'],$likedIds)?'currentColor':'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span class="lcount"><?= $post['likes_count'] ?></span>
          </button>
          <?php if ($post['user_id'] == auth()['id'] || !empty(auth()['is_admin'])): ?>
          <form method="POST" action="api/del.php" style="margin-left:auto;" onsubmit="return confirm('Delete this post?')">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="type" value="post">
            <input type="hidden" name="id"   value="<?= $post['id'] ?>">
            <button type="submit" class="post-action" style="color:var(--danger);" title="<?= !empty(auth()['is_admin']) && $post['user_id'] != auth()['id'] ? 'Admin: Delete post' : 'Delete post' ?>">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
            </button>
          </form>
          <?php if (!empty(auth()['is_admin']) && $post['user_id'] != auth()['id']): ?>
          <span style="font-size:9px;color:var(--danger);font-weight:700;margin-left:4px;">🛡 Admin</span>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($r['pages'] > 1): ?>
    <div class="pagination">
      <?php for ($i=1;$i<=$r['pages'];$i++): ?>
        <<?= $i==$page?'span class="active"':'a href="?page='.$i.($tag?"&tag=$tag":'').'"' ?>><?= $i ?></<?= $i==$page?'span':'a' ?>>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- RIGHT SIDEBAR -->
  <div>
    <div class="card widget" style="margin-bottom:16px;">
      <div class="widget-title">🔥 Trending Topics</div>
      <?php foreach(['#StudyAbroad','#FreelanceLife','#InternshipHunt','#CVTips','#CampusLife','#SkillShare'] as $i=>$t): ?>
      <a href="#" class="trend-item">
        <span class="trend-num"><?= $i+1 ?></span>
        <div><div class="trend-text"><?= $t ?></div><div class="trend-count"><?= rand(20,200) ?> posts</div></div>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="card widget">
      <div class="widget-title">👥 Students to Connect</div>
      <?php foreach ($sug as $su): 
        $isFollowing = in_array($su['id'], $followingIds);
      ?>
      <div class="student-item">
        <?= avatarHtml($su, 36) ?>
        <div style="flex:1;min-width:0;">
          <div class="st-name"><?= clean($su['name']) ?></div>
          <div class="st-dept"><?= clean($su['department'] ?? 'Student') ?></div>
        </div>
        <div class="follow-btn-wrap">
          <button
            class="follow-btn <?= $isFollowing ? 'connected' : 'connect' ?>"
            data-uid="<?= $su['id'] ?>"
            data-following="<?= $isFollowing ? '1' : '0' ?>"
            onclick="toggleFollow(this)"
          ><?= $isFollowing ? '✓ Connected' : 'Connect' ?></button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<script>
// ── Image preview (natural size) ──────────────────────────
function previewPostImg(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  const url  = URL.createObjectURL(file);
  const img  = document.getElementById('post-img-thumb');
  img.onload = function() {
    document.getElementById('img-dim-label').textContent =
      img.naturalWidth + ' × ' + img.naturalHeight + 'px · ' +
      (file.size / 1024).toFixed(0) + ' KB';
    URL.revokeObjectURL(url);
  };
  img.src = url;
  document.getElementById('post-img-preview').style.display = 'block';
}
function clearPostImg() {
  document.getElementById('post-img-input').value = '';
  document.getElementById('post-img-preview').style.display = 'none';
  document.getElementById('img-dim-label').textContent = '';
}

// ── Follow / Connect toggle (persists correctly) ──────────
function toggleFollow(btn) {
  const uid       = btn.dataset.uid;
  const following = btn.dataset.following === '1';
  btn.disabled = true;

  fetch('<?= BASE_URL ?>/api/follow.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({user_id: parseInt(uid)})
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const nowFollowing = data.following;
      btn.dataset.following = nowFollowing ? '1' : '0';
      btn.textContent      = nowFollowing ? '✓ Connected' : 'Connect';
      btn.className        = 'follow-btn ' + (nowFollowing ? 'connected' : 'connect');
      btn.disabled         = nowFollowing; // lock when connected, allow unfollow click
      if (nowFollowing) btn.disabled = false; // allow toggling back
    }
  })
  .catch(() => {})
  .finally(() => { btn.disabled = false; });
}
</script>

<?php include 'includes/footer.php'; ?>