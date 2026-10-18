<?php
/**
 * 编辑学员页
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);

// ---- 读取原记录 ----
$stmt = db()->prepare('SELECT * FROM students WHERE id = :id');
$stmt->execute([':id' => $id]);
$student = $stmt->fetch();
if (!$student) {
    die('学员不存在');
}

$errors = [];
$old = [
    'name' => $student['name'], 'coach' => $student['coach'], 'class_name' => $student['class_name'],
    'gender' => $student['gender'], 'id_card' => $student['id_card'], 'phone' => $student['phone'],
    'reg_coach' => $student['reg_coach'], 'reg_date' => $student['reg_date'], 'status' => $student['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'name'       => trim($_POST['name'] ?? ''),
        'coach'      => trim($_POST['coach'] ?? ''),
        'class_name' => trim($_POST['class_name'] ?? ''),
        'gender'     => trim($_POST['gender'] ?? ''),
        'id_card'    => trim($_POST['id_card'] ?? ''),
        'phone'      => trim($_POST['phone'] ?? ''),
        'reg_coach'  => trim($_POST['reg_coach'] ?? ''),
        'reg_date'   => trim($_POST['reg_date'] ?? ''),
        'status'     => trim($_POST['status'] ?? ''),
    ];

    // ---- 校验 ----
    if ($old['name'] === '')           $errors[] = '姓名不能为空';
    if (!in_array($old['coach'], COACHES(), true))           $errors[] = '带教教练不合法';
    if (!in_array($old['class_name'], CLASSES(), true))      $errors[] = '班级不合法';
    if (!in_array($old['gender'], GENDERS(), true))          $errors[] = '性别不合法';
    if ($old['id_card'] === '')        $errors[] = '身份证号码不能为空'; // 可输入不完整或汉字，仅校验非空
    if ($old['phone'] === '')          $errors[] = '手机号不能为空';     // 可输入汉字或少位数，仅校验非空
    if (!in_array($old['reg_coach'], REG_COACHES(), true))   $errors[] = '报名教练不合法';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $old['reg_date'])) $errors[] = '报名日期格式不正确';
    if (!in_array($old['status'], STATUSES(), true))         $errors[] = '学习状态不合法';

    if (!$errors) {
        $stmt = db()->prepare(
            'UPDATE students SET name=:name, coach=:coach, class_name=:class_name, gender=:gender,
             id_card=:id_card, phone=:phone, reg_coach=:reg_coach, reg_date=:reg_date, status=:status
             WHERE id=:id'
        );
        $stmt->execute([
            ':name' => $old['name'], ':coach' => $old['coach'], ':class_name' => $old['class_name'],
            ':gender' => $old['gender'], ':id_card' => $old['id_card'], ':phone' => $old['phone'],
            ':reg_coach' => $old['reg_coach'], ':reg_date' => $old['reg_date'], ':status' => $old['status'],
            ':id' => $id,
        ]);
        header('Location: index.php?msg=updated');
        exit;
    }
}

$pageTitle = '编辑学员';
$activeMenu = '';
require __DIR__ . '/../includes/header.php';
?>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form class="form-card" method="post" action="edit.php?id=<?= $id ?>">
        <div class="form-grid">
            <div class="form-item">
                <label>姓名 <em>*</em></label>
                <input type="text" name="name" value="<?= htmlspecialchars($old['name']) ?>" maxlength="50" required>
            </div>
            <div class="form-item">
                <label>带教教练 <em>*</em></label>
                <select name="coach">
                    <?php foreach (COACHES() as $c): ?>
                        <option value="<?= $c ?>" <?= $old['coach'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-item">
                <label>班级 <em>*</em></label>
                <select name="class_name">
                    <?php foreach (CLASSES() as $c): ?>
                        <option value="<?= $c ?>" <?= $old['class_name'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-item">
                <label>性别 <em>*</em></label>
                <select name="gender">
                    <?php foreach (GENDERS() as $g): ?>
                        <option value="<?= $g ?>" <?= $old['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-item">
                <label>身份证号码 <em>*</em></label>
                <input type="text" name="id_card" value="<?= htmlspecialchars($old['id_card']) ?>" maxlength="18" placeholder="可填完整、不完整或汉字" required>
            </div>
            <div class="form-item">
                <label>手机号 <em>*</em></label>
                <input type="text" name="phone" value="<?= htmlspecialchars($old['phone']) ?>" maxlength="20" placeholder="可填汉字或少位数" required>
            </div>
            <div class="form-item">
                <label>报名教练 <em>*</em></label>
                <select name="reg_coach">
                    <?php foreach (REG_COACHES() as $c): ?>
                        <option value="<?= $c ?>" <?= $old['reg_coach'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-item">
                <label>报名日期 <em>*</em></label>
                <input type="date" name="reg_date" value="<?= htmlspecialchars($old['reg_date']) ?>" required>
            </div>
            <div class="form-item">
                <label>学习状态 <em>*</em></label>
                <select name="status">
                    <?php foreach (STATUSES() as $s): ?>
                        <option value="<?= $s ?>" <?= $old['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">保存修改</button>
            <a href="index.php" class="btn btn-plain">取消</a>
        </div>
    </form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
