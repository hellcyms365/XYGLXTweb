<?php
/**
 * 登录鉴权模块
 * 提供：会话启动、登录校验、强制登录跳转、当前管理员信息
 */

require_once __DIR__ . '/db.php';

// 启动会话（带安全参数）
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * 强制要求登录：未登录则跳转到登录页
 */
function require_login(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * 当前登录管理员（数组），未登录返回 null
 * @return array|null
 */
function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $admin = null;
    if ($admin === null) {
        $stmt = db()->prepare('SELECT id, username, real_name FROM admins WHERE id = :id');
        $stmt->execute([':id' => (int)$_SESSION['admin_id']]);
        $admin = $stmt->fetch() ?: null;
    }
    return $admin;
}

/**
 * 尝试登录，成功返回 true，失败返回 false
 * @param string $username
 * @param string $password
 * @return bool
 */
function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT id, username, password, real_name FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch();
    if (!$admin) {
        return false;
    }
    if (!password_verify($password, $admin['password'])) {
        return false;
    }
    // 会话固定防护：登录成功后重新生成 session id
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$admin['id'];
    $_SESSION['admin_name'] = $admin['real_name'] ?: $admin['username'];
    return true;
}
