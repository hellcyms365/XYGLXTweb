<?php
/**
 * 用户管理 - 新增管理员
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$errors = [];
$old = ['username' => '', 'real_name' => '', 'password' => '', 'confirm' => ''];

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
    if (strlen($old['password']) < 6) {
        $errors[] = '密码至少6位';
    }
    if ($old['password'] !== $old['confirm']) {
        $errors[] = '两次输入的密码不一致';
    }

    if (!$errors) {
        // 检查用户名是否重复
        $stmt = db()->prepare('SELECT id FROM admins WHERE username = :username');
        $stmt->execute([':username' => $old['username']]);
        if ($stmt->fetch()) {
            $errors[] = '该用户名已存在';
        }
    }

    if (!$errors) {
        $hash = password_hash($old['password'], PASSWORD_DEFAULT);
        $stmt = db()->prepare('INSERT INTO admins (username, password, real_name) VALUES (:u, :p, :r)');
        $stmt->execute([
            ':u' => $old['username'],
            ':p' => $hash,
            ':r' => $old['real_name'],
        ]);
        header('Location: users.php?msg=added');
        exit;
    }
}

$pageTitle = '新增管理员';
$activeMenu = 'users';
require __DIR__ . '/../includes/header.php';
?>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form class="form-card form-narrow" method="post" action="user_add.php">
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
                <label>登录密码 <em>*</em></label>
                <input type="password" name="password" maxlength="50" placeholder="至少6位" autocomplete="new-password" required>
            </div>
            <div class="form-item">
                <label>确认密码 <em>*</em></label>
                <input type="password" name="confirm" maxlength="50" placeholder="再次输入密码" autocomplete="new-password" required>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">保存</button>
            <a href="users.php" class="btn btn-plain">取消</a>
        </div>
    </form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
