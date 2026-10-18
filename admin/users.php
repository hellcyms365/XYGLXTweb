<?php
/**
 * 用户管理 - 管理员列表页
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me = current_admin();

// 当前账号的角色判断（简单起见：用户名 admin 视为超级管理员）
$isSuper = ($me['username'] ?? '') === 'admin';

// ---- 查询全部管理员 ----
$admins = db()->query('SELECT id, username, real_name, created_at FROM admins ORDER BY id ASC')->fetchAll();

// 操作反馈
$msg = $_GET['msg'] ?? '';
$msgText = [
    'added'   => '管理员已添加',
    'updated' => '管理员信息已更新',
    'deleted' => '管理员已删除',
    'denied'  => '不能删除自己或仅剩的管理员',
][$msg] ?? '';

$pageTitle = '用户管理';
$activeMenu = 'users';
require __DIR__ . '/../includes/header.php';
?>

    <?php if ($msgText): ?>
        <div class="alert alert-success">✔ <?= htmlspecialchars($msgText) ?></div>
    <?php endif; ?>

    <div class="panel-toolbar">
        <div class="panel-toolbar-t">系统管理员账号</div>
        <a class="btn btn-primary" href="user_add.php">＋ 新增管理员</a>
    </div>

    <div class="table-card">
        <div class="table-head">
            <div class="table-head-t">管理员列表</div>
            <div class="table-head-sub">共 <strong><?= count($admins) ?></strong> 个账号</div>
        </div>
        <div class="table-wrap">
        <table class="table">
            <thead>
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>姓名</th>
                <th>角色</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$admins): ?>
                <tr><td colspan="6" class="empty">暂无管理员账号</td></tr>
            <?php endif; ?>
            <?php foreach ($admins as $a): ?>
                <tr>
                    <td class="t-id">#<?= $a['id'] ?></td>
                    <td class="t-name"><?= htmlspecialchars($a['username']) ?></td>
                    <td><?= htmlspecialchars($a['real_name'] ?: '—') ?></td>
                    <td>
                        <?php if ($a['username'] === 'admin'): ?>
                            <span class="badge badge-gold">超级管理员</span>
                        <?php elseif ($a['id'] === (int)$me['id']): ?>
                            <span class="badge badge-blue">当前账号</span>
                        <?php else: ?>
                            <span class="badge badge-cyan">管理员</span>
                        <?php endif; ?>
                    </td>
                    <td class="t-muted"><?= htmlspecialchars($a['created_at']) ?></td>
                    <td class="actions">
                        <a class="btn btn-small btn-edit" href="user_edit.php?id=<?= $a['id'] ?>">编辑</a>
                        <?php if ($a['id'] !== (int)$me['id']): ?>
                        <a class="btn btn-small btn-del" href="user_delete.php?id=<?= $a['id'] ?>" onclick="return confirm('确定删除管理员「<?= htmlspecialchars($a['username']) ?>」吗？');">删除</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
