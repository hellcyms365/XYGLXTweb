<?php
/**
 * 学员列表页：展示全部学员，支持关键词搜索、带教教练筛选、学习状态筛选、分页
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

// ---- 收集筛选参数 ----
$keyword = trim($_GET['keyword'] ?? '');
$coach   = trim($_GET['coach'] ?? '');
$status  = trim($_GET['status'] ?? '');

// 每页条数（默认 20，可通过 ?limit= 指定，限制 1~100）
$perPage = (int)($_GET['limit'] ?? 20);
if ($perPage < 1) $perPage = 20;
if ($perPage > 100) $perPage = 100;
$page    = max(1, (int)($_GET['page'] ?? 1));

// ---- 构建 WHERE 条件（全部用预编译参数，防注入）----
$where  = [];
$params = [];

if ($keyword !== '') {
    $where[] = "(name LIKE :kw1 OR phone LIKE :kw2 OR id_card LIKE :kw3)";
    $params[':kw1'] = '%' . $keyword . '%';
    $params[':kw2'] = '%' . $keyword . '%';
    $params[':kw3'] = '%' . $keyword . '%';
}
if ($coach !== '') {
    $where[] = 'coach LIKE :coach';
    $params[':coach'] = '%' . $coach . '%';
}
if ($status !== '') {
    $where[] = 'status LIKE :status';
    $params[':status'] = '%' . $status . '%';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---- 查询总数（用于分页）----
$stmt = db()->prepare("SELECT COUNT(*) AS total FROM students $whereSql");
$stmt->execute($params);
$total     = (int)$stmt->fetch()['total'];
$totalPage = max(1, (int)ceil($total / $perPage));
$page      = min($page, $totalPage);
$offset    = ($page - 1) * $perPage;

// ---- 查询当前页数据 ----
$stmt = db()->prepare("SELECT * FROM students $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$students = $stmt->fetchAll();

// 学习状态对应的标签颜色
function statusClass(string $s): string {
    $map = [
        '毕业' => 'badge-green',
        '退学' => 'badge-red',
    ];
    return $map[$s] ?? 'badge-blue';
}

// ---- 统计（全库）----
function statTotal(): int {
    return (int)db()->query('SELECT COUNT(*) AS c FROM students')->fetch()['c'];
}
function statByStatus(): array {
    $rows = db()->query('SELECT status AS k, COUNT(*) AS c FROM students GROUP BY status')->fetchAll();
    $map = ['科目一' => 0, '科目二' => 0, '科目三' => 0, '毕业' => 0, '退学' => 0];
    foreach ($rows as $r) { $map[$r['k']] = (int)$r['c']; }
    return $map;
}
$totalAll = statTotal();
$byStatus = statByStatus();
$statusCardMeta = [
    '科目一' => ['blue',  '📘'],
    '科目二' => ['cyan',  '📗'],
    '科目三' => ['purple','📙'],
    '毕业'   => ['green', '🎓'],
    '退学'   => ['red',   '🚫'],
];

$pageTitle = '学员列表';
$activeMenu = 'index';
require __DIR__ . '/../includes/header.php';
?>

    <!-- 统计概览 -->
    <div class="admin-stats">
        <div class="astat astat-total">
            <div class="astat-ico">👥</div>
            <div class="astat-info">
                <div class="astat-num"><?= $totalAll ?></div>
                <div class="astat-label">在册学员</div>
            </div>
        </div>
        <?php foreach ($byStatus as $k => $cnt):
            $meta = $statusCardMeta[$k] ?? ['blue', '🏷️']; ?>
        <div class="astat astat-<?= $meta[0] ?>">
            <div class="astat-ico"><?= $meta[1] ?></div>
            <div class="astat-info">
                <div class="astat-num"><?= $cnt ?></div>
                <div class="astat-label"><?= htmlspecialchars($k) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 搜索与筛选 -->
    <form class="filter-bar" method="get" action="index.php">
        <input type="text" name="keyword" placeholder="搜索姓名 / 手机号 / 身份证" value="<?= htmlspecialchars($keyword) ?>">
        <select name="coach">
            <option value="">全部带教教练</option>
            <?php foreach (COACHES() as $c): ?>
                <option value="<?= $c ?>" <?= $coach === $c ? 'selected' : '' ?>><?= $c ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">全部学习状态</option>
            <?php foreach (STATUSES() as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">查询</button>
        <a href="index.php" class="btn btn-plain">重置</a>
        <label class="limit-label">每页
            <input type="number" name="limit" class="limit-input" min="1" max="100" value="<?= (int)$perPage ?>" onchange="this.form.submit()">
            行
        </label>
        <span class="filter-right">
            <a class="btn btn-secondary" href="export.php?keyword=<?= urlencode($keyword) ?>&coach=<?= urlencode($coach) ?>&status=<?= urlencode($status) ?>">⬇ 导出</a>
            <a class="btn btn-primary" href="add.php">＋ 新增学员</a>
        </span>
    </form>

    <!-- 学员表格 -->
    <div class="table-card">
        <div class="table-head">
            <div class="table-head-t">学员列表</div>
            <div class="table-head-sub">共 <strong><?= $total ?></strong> 名学员</div>
        </div>
        <div class="table-wrap">
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>姓名</th>
            <th>带教教练</th>
            <th>班级</th>
            <th>性别</th>
            <th>身份证号码</th>
            <th>手机号</th>
            <th>报名教练</th>
            <th>报名日期</th>
            <th>学习状态</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$students): ?>
            <tr><td colspan="11" class="empty">无匹配数据，请调整查询条件后再试</td></tr>
        <?php endif; ?>
        <?php foreach ($students as $s): ?>
            <tr>
                <td class="t-id">#<?= $s['id'] ?></td>
                <td class="t-name"><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['coach']) ?></td>
                <td><span class="tag-class"><?= htmlspecialchars($s['class_name']) ?></span></td>
                <td><?= htmlspecialchars($s['gender']) ?></td>
                <td class="t-muted"><?= htmlspecialchars($s['id_card']) ?></td>
                <td class="t-muted"><?= htmlspecialchars($s['phone']) ?></td>
                <td><?= htmlspecialchars($s['reg_coach']) ?></td>
                <td class="t-muted"><?= htmlspecialchars($s['reg_date']) ?></td>
                <td><span class="badge <?= statusClass($s['status']) ?>"><?= htmlspecialchars($s['status']) ?></span></td>
                <td class="actions">
                    <a class="btn btn-small btn-edit" href="edit.php?id=<?= $s['id'] ?>">编辑</a>
                    <a class="btn btn-small btn-del" href="delete.php?id=<?= $s['id'] ?>" onclick="return confirm('确定删除学员「<?= htmlspecialchars($s['name']) ?>」吗？');">删除</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
        </div>
    </div>

    <!-- 分页 -->
    <?php if ($totalPage > 1): ?>
    <?php $qsBase = "keyword=" . urlencode($keyword) . "&coach=" . urlencode($coach) . "&status=" . urlencode($status) . "&limit=" . (int)$perPage; ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="index.php?page=<?= $page - 1 ?>&<?= $qsBase ?>">上一页</a>
        <?php endif; ?>
        <span>第 <?= $page ?> / <?= $totalPage ?> 页（每页 <?= (int)$perPage ?> 行）</span>
        <?php if ($page < $totalPage): ?>
            <a href="index.php?page=<?= $page + 1 ?>&<?= $qsBase ?>">下一页</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
