<?php
/**
 * 后台登录页
 */
require_once __DIR__ . '/../includes/auth.php';

// 已登录则直接进入后台
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = '请输入账号和密码';
    } elseif (attempt_login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = '账号或密码错误';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 学员管理系统</title>

    <!-- PWA / 苹果主屏幕 -->
    <link rel="manifest" href="../manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="学员管理">
    <meta name="theme-color" content="#1f2a44">
    <link rel="apple-touch-icon" href="../assets/icon-180.png">
    <link rel="icon" type="image/png" href="../assets/favicon.png">

    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="login-body">
<div class="login-wrap">
    <form class="login-card" method="post" action="login.php">
        <div class="login-brand">
            <div class="logo-circle">🚗</div>
            <h1>学员管理系统</h1>
            <p>教练后台登录</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-item">
            <label>账号</label>
            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" autocomplete="username" placeholder="请输入登录账号" required autofocus>
        </div>
        <div class="form-item">
            <label>密码</label>
            <input type="password" name="password" autocomplete="current-password" placeholder="请输入密码" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">登 录</button>
        <div class="login-foot">仅限内部教练使用 · 请妥善保管账号</div>
    </form>
</div>
</body>
</html>
