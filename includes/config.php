<?php
define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','campus_connectv3');
define('BASE_URL','http://localhost/campus_connect_x_v2');
if(session_status()===PHP_SESSION_NONE){
    session_name('ccx');
    session_set_cookie_params(['lifetime'=>86400,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

function db():PDO{
    static $p=null;
    if(!$p){
        $p=new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",DB_USER,DB_PASS,[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false
        ]);
    }
    return $p;
}

function auth():?array{ return $_SESSION['user']??null; }
function isLoggedIn():bool{ return isset($_SESSION['user']); }
function requireAuth():void{ if(!isLoggedIn()){header('Location:'.BASE_URL.'/login.php');exit;} }

function csrf():string{
    if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verifyCsrf():void{
    if(!hash_equals($_SESSION['csrf']??'', $_POST['csrf']??'')){ http_response_code(403); die('Invalid token'); }
}

function clean(string $s):string{ return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }
function s(string $s):string{ return trim(strip_tags($s)); }

function flash(string $k,string $m):void{ $_SESSION['flash'][$k]=$m; }
function getFlash(string $k):?string{ $m=$_SESSION['flash'][$k]??null; unset($_SESSION['flash'][$k]); return $m; }

function ago(string $dt):string{
    $diff=(new DateTime())->diff(new DateTime($dt));
    if($diff->y)return $diff->y.'y ago';
    if($diff->m)return $diff->m.'mo ago';
    if($diff->d)return $diff->d.'d ago';
    if($diff->h)return $diff->h.'h ago';
    if($diff->i)return $diff->i.'m ago';
    return 'just now';
}

function initials(string $n):string{
    $w=explode(' ',trim($n)); $r='';
    foreach(array_slice($w,0,2) as $x) $r.=strtoupper(mb_substr($x,0,1));
    return $r;
}

function paginate(string $q,array $p,int $page,int $pp=10):array{
    $off=($page-1)*$pp;
    $c=db()->prepare("SELECT COUNT(*) FROM ($q) t"); $c->execute($p); $total=(int)$c->fetchColumn();
    $s=db()->prepare("$q LIMIT $pp OFFSET $off"); $s->execute($p);
    return['items'=>$s->fetchAll(),'total'=>$total,'page'=>$page,'pp'=>$pp,'pages'=>max(1,ceil($total/$pp))];
}
