<?php
require_once '../includes/config.php';
requireAuth();
if($_SERVER['REQUEST_METHOD']!=='POST') { header('Location:'.BASE_URL.'/dashboard.php'); exit; }
if(!hash_equals($_SESSION['csrf']??'', $_POST['csrf']??'')) { die('Invalid token'); }
$type=$_POST['type']??'';
$id=(int)($_POST['id']??0);
if(!$id) { header('Location:'.BASE_URL.'/dashboard.php'); exit; }
switch($type){
    case 'post':
        $chk=db()->prepare("SELECT id FROM posts WHERE id=? AND user_id=?"); $chk->execute([$id,auth()['id']]);
        if($chk->fetch()) db()->prepare("DELETE FROM posts WHERE id=?")->execute([$id]);
        header('Location:'.BASE_URL.'/community.php'); break;
    case 'tip':
        $chk=db()->prepare("SELECT id FROM abroad_tips WHERE id=? AND user_id=?"); $chk->execute([$id,auth()['id']]);
        if($chk->fetch()) db()->prepare("DELETE FROM abroad_tips WHERE id=?")->execute([$id]);
        header('Location:'.BASE_URL.'/abroad.php'); break;
    case 'job':
        $chk=db()->prepare("SELECT id FROM jobs WHERE id=? AND user_id=?"); $chk->execute([$id,auth()['id']]);
        if($chk->fetch()) db()->prepare("UPDATE jobs SET is_active=0 WHERE id=?")->execute([$id]);
        header('Location:'.BASE_URL.'/career.php'); break;
    case 'service':
        $chk=db()->prepare("SELECT id FROM services WHERE id=? AND user_id=?"); $chk->execute([$id,auth()['id']]);
        if($chk->fetch()) db()->prepare("UPDATE services SET is_active=0 WHERE id=?")->execute([$id]);
        header('Location:'.BASE_URL.'/marketplace.php'); break;
    default:
        header('Location:'.BASE_URL.'/dashboard.php');
}
exit;
