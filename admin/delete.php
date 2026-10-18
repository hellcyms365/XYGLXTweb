<?php
/**
 * 删除学员页（GET 触发，带前端确认；后端校验 id）
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('参数错误');
}

$stmt = db()->prepare('DELETE FROM students WHERE id = :id');
$stmt->execute([':id' => $id]);

header('Location: index.php?msg=deleted');
exit;
