<?php
require 'config.php';
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $stmt=$pdo->prepare('SELECT * FROM users WHERE username=?');
    $stmt->execute([trim($_POST['username'] ?? '')]);
    $user=$stmt->fetch();
    if ($user && password_verify($_POST['password'] ?? '',$user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']=(int)$user['user_id'];
        $_SESSION['username']=$user['username'];
        $_SESSION['role']=$user['role'];
        header('Location: index.php'); exit;
    }
    $error='Incorrect username or password.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login | William Hardware</title><link rel="stylesheet" href="style.css"></head>
<body class="login"><form class="panel loginbox" method="post"><h1>William Hardware Shop</h1><p>Inventory Management Information System</p><?php if($error):?><p class="notice error"><?=h($error)?></p><?php endif;?><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><label>Username<input name="username" autocomplete="username" required></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button>Sign in</button><p class="muted"></p></form></body></html>
