<?php
/**
 * 用户管理 - 删除管理员
 * 安全边界：不能删除自己；不能删除最后一个管理员
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me = current_admin();
$id = (int)($_GET['id'] ?? 0);

// 不能删除自己
if ($id === (int)$me['id']) {
    header('Location: users.php?msg=denied');
    exit;
}

// 至少保留一个管理员
$count = (int)db()->query('SELECT COUNT(*) AS c FROM admins')->fetch()['c'];
if ($count <= 1) {
    header('Location: users.php?msg=denied');
    exit;
}

$stmt = db()->prepare('DELETE FROM admins WHERE id = :id');
$stmt->execute([':id' => $id]);

header('Location: users.php?msg=deleted');
exit;
