<?php
/**
 * 后台布局 - 公共头部（侧边栏 + 顶部栏）
 * 用法：require_once 'includes/auth.php'; require_login(); 再 include header.php
 * 可选变量：$pageTitle（页面标题）、$activeMenu（当前高亮菜单，如 index/add）
 */

if (!isset($pageTitle)) $pageTitle = '后台管理';
$menu = isset($activeMenu) ? $activeMenu : '';
$admin = current_admin();
$adminName = $admin['real_name'] ?: ($admin['username'] ?? '管理员');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - 学员管理系统</title>

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
<body class="admin-body"<?= isset($bodyData) ? ' ' . $bodyData : '' ?>>
<div class="layout">
    <!-- 侧边栏 -->
    <aside class="sidebar">
        <div class="brand">
            <span class="brand-logo">🚗</span>
            <div class="brand-txt">
                <div class="brand-text">学员管理系统</div>
                <div class="brand-sub">教练后台</div>
            </div>
        </div>
        <nav class="nav">
            <div class="nav-section">学员管理</div>
            <a class="nav-link <?= $menu === 'index' ? 'active' : '' ?>" href="index.php">
                <span class="nav-ico">📋</span><span>学员列表</span>
            </a>
            <a class="nav-link <?= $menu === 'add' ? 'active' : '' ?>" href="add.php">
                <span class="nav-ico">➕</span><span>新增学员</span>
            </a>
            <a class="nav-link <?= $menu === 'import' ? 'active' : '' ?>" href="import.php">
                <span class="nav-ico">📥</span><span>批量导入</span>
            </a>
            <div class="nav-section">系统设置</div>
            <a class="nav-link <?= $menu === 'options' ? 'active' : '' ?>" href="options.php">
                <span class="nav-ico">⚙️</span><span>选项管理</span>
            </a>
            <a class="nav-link <?= $menu === 'users' ? 'active' : '' ?>" href="users.php">
                <span class="nav-ico">👤</span><span>用户管理</span>
            </a>
            <div class="nav-section">其他</div>
            <a class="nav-link" href="../index.php" target="_blank">
                <span class="nav-ico">🌐</span><span>查看前端</span>
            </a>
        </nav>
        <div class="sidebar-user">
            <div class="suser-avatar"><?= htmlspecialchars(mb_substr($adminName, 0, 1, 'UTF-8')) ?></div>
            <div class="suser-info">
                <div class="suser-name"><?= htmlspecialchars($adminName) ?></div>
                <div class="suser-role">管理员</div>
            </div>
        </div>
        <div class="sidebar-foot">
            <a class="nav-link" href="logout.php"><span class="nav-ico">⏻</span><span>退出登录</span></a>
        </div>
    </aside>

    <!-- 主内容区 -->
    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="nav-toggle" id="navToggle" type="button" aria-label="打开菜单">☰</button>
                <div class="topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
                <a class="btn-back" href="../index.php">🌐 查看前端</a>
            </div>
            <div class="topbar-user">
                <span class="user-avatar"><?= htmlspecialchars(mb_substr($adminName, 0, 1, 'UTF-8')) ?></span>
                <span class="user-name"><?= htmlspecialchars($adminName) ?></span>
                <a class="btn btn-plain btn-logout" href="logout.php">退出登录</a>
            </div>
        </header>
        <div class="nav-mask" id="navMask"></div>
        <main class="content">
