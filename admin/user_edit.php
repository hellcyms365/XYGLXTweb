<?php
/**
 * 用户管理 - 编辑管理员（改用户名/姓名，可选重置密码）
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me = current_admin();
$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT id, username, real_name FROM admins WHERE id = :id');
$stmt->execute([':id' => $id]);
$target = $stmt->fetch();
if (!$target) {
    die('管理员不存在');
}

$errors = [];
$old = ['username' => $target['username'], 'real_name' => $target['real_name'], 'password' => '', 'confirm' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'username'  => trim($_POST['username'] ?? ''),
        'real_name' => trim($_POST['real_name'] ?? ''),
        'password'  => $_POST['password'] ?? '',
        'confirm'   => $_POST['confirm'] ?? '',
    ];

    if ($old['username'] === '') {
        $errors[] = '用户名不能为空';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $old['username'])) {
        $errors[] = '用户名需为3-20位字母、数字或下划线';
    }
    if ($old['real_name'] === '') {
        $errors[] = '姓名不能为空';
    }
    if ($old['password'] !== '') {
        if (strlen($old['password']) < 6) {
            $errors[] = '密码至少6位';
        } elseif ($old['password'] !== $old['confirm']) {
            $errors[] = '两次输入的密码不一致';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM admins WHERE username = :username AND id <> :id');
        $stmt->execute([':username' => $old['username'], ':id' => $id]);
        if ($stmt->fetch()) {
            $errors[] = '该用户名已被其他账号使用';
        }
    }

    if (!$errors) {
        if ($old['password'] !== '') {
            $hash = password_hash($old['password'], PASSWORD_DEFAULT);
            $stmt = db()->prepare('UPDATE admins SET username=:u, real_name=:r, password=:p WHERE id=:id');
            $stmt->execute([':u' => $old['username'], ':r' => $old['real_name'], ':p' => $hash, ':id' => $id]);
        } else {
            $stmt = db()->prepare('UPDATE admins SET username=:u, real_name=:r WHERE id=:id');
            $stmt->execute([':u' => $old['username'], ':r' => $old['real_name'], ':id' => $id]);
        }
        // 若修改的是自己，同步更新会话名
        if ($id === (int)$me['id']) {
            $_SESSION['admin_name'] = $old['real_name'] ?: $old['username'];
        }
        header('Location: users.php?msg=updated');
        exit;
    }
}

$pageTitle = '编辑管理员';
$activeMenu = 'users';
require __DIR__ . '/../includes/header.php';
?>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form class="form-card form-narrow" method="post" action="user_edit.php?id=<?= $id ?>">
        <div class="form-grid form-grid-2">
            <div class="form-item">
                <label>用户名 <em>*</em></label>
                <input type="text" name="username" value="<?= htmlspecialchars($old['username']) ?>" maxlength="20" placeholder="3-20位字母/数字/下划线" required>
            </div>
            <div class="form-item">
                <label>姓名 <em>*</em></label>
                <input type="text" name="real_name" value="<?= htmlspecialchars($old['real_name']) ?>" maxlength="50" placeholder="管理员姓名" required>
            </div>
            <div class="form-item">
                <label>新密码</label>
                <input type="password" name="password" maxlength="50" placeholder="留空则不修改密码" autocomplete="new-password">
            </div>
            <div class="form-item">
                <label>确认新密码</label>
                <input type="password" name="confirm" maxlength="50" placeholder="再次输入新密码" autocomplete="new-password">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">保存修改</button>
            <a href="users.php" class="btn btn-plain">取消</a>
        </div>
    </form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
