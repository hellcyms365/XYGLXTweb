<?php
/**
 * 选项管理 - 后台动态维护数据选项（带教教练、报名教练、性别、班级、学习状态）
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$msg = $_GET['msg'] ?? '';
$msgText = [
    'added'   => '选项已添加',
    'updated' => '选项已修改',
    'deleted' => '选项已删除',
    'invalid' => '操作参数不合法',
][$msg] ?? '';

// ---- 处理动作 ----
if (($_SERVER['REQUEST_METHOD'] === 'POST')) {
    $action = $_POST['action'] ?? '';
    $type   = $_POST['type'] ?? '';
    $label  = trim($_POST['label'] ?? '');

    if (!isset(OPTION_TYPES[$type])) {
        header('Location: options.php?msg=invalid');
        exit;
    }

    if ($action === 'add') {
        if ($label === '') {
            $msgText = '选项值不能为空';
        } else {
            $max = (int)db()->query('SELECT COALESCE(MAX(sort_order),0) AS m FROM options WHERE type = ' . db()->quote($type))->fetch()['m'];
            $stmt = db()->prepare('INSERT INTO options (type, label, sort_order) VALUES (:t, :l, :s)');
            $stmt->execute([':t' => $type, ':l' => $label, ':s' => $max + 1]);
            header('Location: options.php?msg=added');
            exit;
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || $label === '') {
            $msgText = '选项值不能为空';
        } else {
            $stmt = db()->prepare('UPDATE options SET label = :l WHERE id = :id AND type = :t');
            $stmt->execute([':l' => $label, ':id' => $id, ':t' => $type]);
            header('Location: options.php?msg=updated');
            exit;
        }
    } elseif ($action === 'move') {
        $id   = (int)($_POST['id'] ?? 0);
        $dir  = $_POST['dir'] ?? '';
        move_option($type, $id, $dir === 'up' ? -1 : 1);
        header('Location: options.php');
        exit;
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = db()->prepare('DELETE FROM options WHERE id = :id AND type = :t');
        $stmt->execute([':id' => $id, ':t' => $type]);
        header('Location: options.php?msg=deleted');
        exit;
    }
}

/**
 * 调整选项排序
 */
function move_option(string $type, int $id, int $delta): void
{
    $stmt = db()->prepare('SELECT * FROM options WHERE id = :id AND type = :t');
    $stmt->execute([':id' => $id, ':t' => $type]);
    $cur = $stmt->fetch();
    if (!$cur) return;

    $stmt = db()->prepare(
        'SELECT * FROM options WHERE type = :t AND sort_order < :s ORDER BY sort_order DESC LIMIT 1'
    );
    $stmt->execute([':t' => $type, ':s' => $cur['sort_order']]);
    $neighbor = $delta < 0 ? $stmt->fetch() : null;

    if ($neighbor === null) {
        $stmt = db()->prepare(
            'SELECT * FROM options WHERE type = :t AND sort_order > :s ORDER BY sort_order ASC LIMIT 1'
        );
        $stmt->execute([':t' => $type, ':s' => $cur['sort_order']]);
        $neighbor = $delta > 0 ? $stmt->fetch() : null;
    }

    if ($neighbor) {
        $u = db()->prepare('UPDATE options SET sort_order = :s1 WHERE id = :id1');
        $u->execute([':s1' => $neighbor['sort_order'], ':id1' => $cur['id']]);
        $u2 = db()->prepare('UPDATE options SET sort_order = :s2 WHERE id = :id2');
        $u2->execute([':s2' => $cur['sort_order'], ':id2' => $neighbor['id']]);
    }
}

$pageTitle = '选项管理';
$activeMenu = 'options';
require __DIR__ . '/../includes/header.php';
?>

    <?php if ($msgText): ?>
        <div class="alert alert-success">✔ <?= htmlspecialchars($msgText) ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
        💡 选项值用于学员录入时的下拉选择（带教教练、报名教练、性别、班级、学习状态）。修改/删除选项不影响已录入的学员数据，仅影响后续录入的选择范围。删除前请确认没有在用。
    </div>

    <?php foreach (OPTION_TYPES as $type => $title): ?>
    <div class="table-card">
        <div class="table-head">
            <div class="table-head-t"><?= htmlspecialchars($title) ?></div>
            <div class="table-head-sub">
                <form class="inline-form" method="post" action="options.php">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="type" value="<?= $type ?>">
                    <input type="text" name="label" placeholder="输入新选项值" required maxlength="50">
                    <button type="submit" class="btn btn-small btn-primary">＋ 添加</button>
                </form>
            </div>
        </div>
        <div class="table-wrap">
        <table class="table">
            <thead><tr><th>排序</th><th>选项值</th><th>操作</th></tr></thead>
            <tbody>
            <?php $opts = get_options_full($type); ?>
            <?php if (!$opts): ?>
                <tr><td colspan="3" class="empty">暂无选项，请在右上方添加</td></tr>
            <?php endif; ?>
            <?php foreach ($opts as $o): ?>
                <tr>
                    <td class="t-id">#<?= $o['sort_order'] ?></td>
                    <td>
                        <form class="inline-form inline-edit" method="post" action="options.php">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="type" value="<?= $type ?>">
                            <input type="hidden" name="id" value="<?= $o['id'] ?>">
                            <input type="text" name="label" value="<?= htmlspecialchars($o['label']) ?>" maxlength="50">
                            <button type="submit" class="btn btn-small btn-edit">保存</button>
                        </form>
                    </td>
                    <td>
                        <div class="actions">
                            <form class="inline-form" method="post" action="options.php">
                                <input type="hidden" name="action" value="move">
                                <input type="hidden" name="type" value="<?= $type ?>">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <input type="hidden" name="dir" value="up">
                                <button type="submit" class="btn btn-small btn-plain" <?= $o['sort_order'] <= 1 ? 'disabled' : '' ?>>↑</button>
                            </form>
                            <form class="inline-form" method="post" action="options.php">
                                <input type="hidden" name="action" value="move">
                                <input type="hidden" name="type" value="<?= $type ?>">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <input type="hidden" name="dir" value="down">
                                <button type="submit" class="btn btn-small btn-plain">↓</button>
                            </form>
                            <form class="inline-form" method="post" action="options.php" onsubmit="return confirm('确定删除选项「<?= htmlspecialchars($o['label']) ?>」吗？');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="type" value="<?= $type ?>">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <button type="submit" class="btn btn-small btn-del">删除</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
