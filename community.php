<?php
// community.php
require_once 'includes/config.php'; requireAuth();
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['body'])){
    $body=s($_POST['body']??'');$tag=in_array($_POST['tag']??'',['community','abroad','skill','general'])?$_POST['tag']:'community';
    if(strlen($body)>=5&&strlen($body)<=1000){db()->prepare("INSERT INTO posts(user_id,body,tag)VALUES(?,?,?)")->execute([auth()['id'],$body,$tag]);flash('success','Post published!');}
    else flash('error','Post must be 5–1000 characters.');
    header('Location:'.BASE_URL.'/community.php');exit;
}
$page=max(1,(int)($_GET['page']??1));$tag=in_array($_GET['tag']??'',['community','abroad','skill','general',''])?($_GET['tag']??''):'';
$w=$tag?"WHERE p.tag='$tag'":'';
$r=paginate("SELECT p.*,u.name,u.university FROM posts p JOIN users u ON p.user_id=u.id $w ORDER BY p.created_at DESC",[],  $page,10);
$posts=$r['items'];
$likedIds=[];
if(!empty($posts)){$ids=implode(',',array_column($posts,'id'));$lq=db()->prepare("SELECT post_id FROM post_likes WHERE user_id=? AND post_id IN($ids)");$lq->execute([auth()['id']]);$likedIds=array_column($lq->fetchAll(),'post_id');}
$sug=db()->prepare("SELECT * FROM users WHERE id!=? ORDER BY RAND() LIMIT 4");$sug->execute([auth()['id']]);$sug=$sug->fetchAll();
$colors=['linear-gradient(135deg,#10B981,#059669)','linear-gradient(135deg,#F59E0B,#D97706)','linear-gradient(135deg,#8B5CF6,#7C3AED)','linear-gradient(135deg,#EF4444,#DC2626)'];
$pageTitle='Community';include 'includes/header.php';
?>
<div class="feed-layout">
<div>
  <form method="POST"><input type="hidden" name="csrf" value="<?=csrf()?>">
    <div class="create-post">
      <div class="cp-av"><?=initials(auth()['name'])?></div>
      <div style="flex:1;">
        <textarea name="body" class="cp-input" placeholder="Share something with your campus..." data-max="1000" required></textarea>
        <div class="cp-actions">
          <select name="tag" class="form-control" style="width:auto;padding:7px 12px;font-size:12px;">
            <option value="community">💬 Community</option><option value="abroad">🌍 Abroad</option>
            <option value="skill">🎯 Skill</option><option value="general">📢 General</option>
          </select>
          <button type="submit" class="btn btn-primary btn-sm" style="margin-left:auto;">Post →</button>
        </div>
      </div>
    </div>
  </form>
  <div class="filter-bar">
    <a href="community.php" class="btn btn-sm <?=!$tag?'btn-primary':'btn-ghost'?>">All</a>
    <?php foreach(['community'=>'💬','abroad'=>'🌍','skill'=>'🎯','general'=>'📢'] as $t=>$i):?>
      <a href="?tag=<?=$t?>" class="btn btn-sm <?=$tag===$t?'btn-primary':'btn-ghost'?>"><?=$i?> <?=ucfirst($t)?></a>
    <?php endforeach;?>
  </div>
  <div class="post-feed">
    <?php if(empty($posts)):?>
      <div class="card"><div class="empty-state"><div class="icon">👋</div><h3>No posts yet</h3><p>Be the first to share something!</p></div></div>
    <?php else: foreach($posts as $post):?>
    <div class="post-card">
      <div class="post-header">
        <div class="post-av" style="background:linear-gradient(135deg,#2563EB,#8B5CF6);"><?=initials($post['name'])?></div>
        <div><div class="post-author"><?=clean($post['name'])?></div><div class="post-meta"><?=clean($post['university']??'Student')?> · <?=ago($post['created_at'])?></div></div>
        <span class="post-tag pt-<?=$post['tag']?>"><?=ucfirst($post['tag'])?></span>
      </div>
      <div class="post-body"><?=nl2br(clean($post['body']))?></div>
      <div class="post-footer">
        <button class="post-action like-btn <?=in_array($post['id'],$likedIds)?'liked':''?>" data-id="<?=$post['id']?>">
          <svg fill="<?=in_array($post['id'],$likedIds)?'currentColor':'none'?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          <span class="lcount"><?=$post['likes_count']?></span> Likes
        </button>
        <?php if($post['user_id']==auth()['id']):?>
        <form method="POST" action="api/del.php" style="margin-left:auto;" onsubmit="return confirm('Delete?')">
          <input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="type" value="post"><input type="hidden" name="id" value="<?=$post['id']?>">
          <button type="submit" class="post-action" style="color:var(--danger);">🗑 Delete</button>
        </form>
        <?php endif;?>
      </div>
    </div>
    <?php endforeach; endif;?>
  </div>
  <?php if($r['pages']>1):?><div class="pagination">
    <?php for($i=1;$i<=$r['pages'];$i++):?>
      <<?=$i==$page?'span class="active"':'a href="?page='.$i.($tag?"&tag=$tag":'').'"'?>><?=$i?></<?=$i==$page?'span':'a'?>>
    <?php endfor;?>
  </div><?php endif;?>
</div>
<div>
  <div class="card widget" style="margin-bottom:16px;">
    <div class="widget-title">🔥 Trending Topics</div>
    <?php foreach(['#StudyAbroad','#FreelanceLife','#InternshipHunt','#CVTips','#CampusLife','#SkillShare'] as $i=>$t):?>
    <a href="#" class="trend-item"><span class="trend-num"><?=$i+1?></span><div><div class="trend-text"><?=$t?></div><div class="trend-count"><?=rand(20,200)?> posts</div></div></a>
    <?php endforeach;?>
  </div>
  <div class="card widget">
    <div class="widget-title">👥 Connect with Students</div>
    <?php foreach($sug as $i=>$su):?>
    <div class="student-item">
      <div class="st-av" style="background:<?=$colors[$i%4]?>;"><?=initials($su['name'])?></div>
      <div><div class="st-name"><?=clean($su['name'])?></div><div class="st-dept"><?=clean($su['department']??'Student')?></div></div>
      <button class="follow-btn" onclick="this.textContent='✓ Connected';this.style.cssText='border:1.5px solid var(--success);color:var(--success);background:none;font-size:11px;font-weight:600;padding:4px 12px;border-radius:var(--rf);cursor:default'">Connect</button>
    </div>
    <?php endforeach;?>
  </div>
</div>
</div>
<?php include 'includes/footer.php';?>
