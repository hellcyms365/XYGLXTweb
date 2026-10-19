<?php
/**
 * 手机端学员管理界面（独立页面，专为手机访问设计）
 * - 学员卡片式展示：姓名/性别/身份证/班级/手机/学习状态
 * - 顶部搜索框 + 带教教练筛选
 * - 底部：「所有学员管理」组合筛选、「带教查询」、「+新增」
 * - 左侧滑出筛选菜单（班级×状态组合）
 * 公开可看数据，新增/修改需登录后台 admin/
 */
require_once __DIR__ . '/includes/db.php';

// ---- 收集筛选参数 ----
$keyword = trim($_GET['keyword'] ?? '');
$coach   = trim($_GET['coach'] ?? '');   // 带教教练（带教查询）
$view    = trim($_GET['view'] ?? 'all'); // 组合筛选视图
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20; // 手机端固定每页 20

// ---- 解析组合视图 view：支持 所有/C1/C2/毕业/C2科目一/C1科目一... ----
$class  = '';
$status = '';
if ($view !== '' && $view !== 'all') {
    // 匹配纯班级
    if (in_array($view, CLASSES(), true)) {
        $class = $view;
    }
    // 匹配纯状态
    elseif (in_array($view, STATUSES(), true)) {
        $status = $view;
    }
    // 匹配 班级+状态 组合（如 C2科目一）
    else {
        foreach (CLASSES() as $c) {
            if (strpos($view, $c) === 0) {
                $class  = $c;
                $status = substr($view, strlen($c));
                break;
            }
        }
    }
}

// ---- 构建 WHERE ----
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
if ($class !== '') {
    $where[] = 'class_name = :class';
    $params[':class'] = $class;
}
if ($status !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---- 总数 / 分页 ----
$stmt = db()->prepare("SELECT COUNT(*) AS total FROM students $whereSql");
$stmt->execute($params);
$total     = (int)$stmt->fetch()['total'];
$totalPage = max(1, (int)ceil($total / $perPage));
$page      = min($page, $totalPage);
$offset    = ($page - 1) * $perPage;

$stmt = db()->prepare("SELECT * FROM students $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$students = $stmt->fetchAll();

// ---- 数据看板统计（全库）----
function mStatTotal(): int {
    return (int)db()->query('SELECT COUNT(*) AS c FROM students')->fetch()['c'];
}
function mStatByStatus(): array {
    $rows = db()->query('SELECT status AS k, COUNT(*) AS c FROM students GROUP BY status')->fetchAll();
    return array_map(fn($r) => ['label' => $r['k'], 'count' => (int)$r['c']], $rows);
}
function mStatByCoach(): array {
    $rows = db()->query('SELECT coach AS k, COUNT(*) AS c FROM students GROUP BY coach ORDER BY c DESC')->fetchAll();
    return array_map(fn($r) => ['label' => $r['k'], 'count' => (int)$r['c']], $rows);
}
$mTotalAll = mStatTotal();
$mByStatus = mStatByStatus();
$mByCoach  = mStatByCoach();
$mStatusColor = ['科目一' => 'c1', '科目二' => 'c2', '科目三' => 'c3', '毕业' => 'cg', '退学' => 'cr'];

// ---- 状态徽章 ----
function mStatusClass(string $s): string {
    $map = ['毕业' => 's-grad', '退学' => 's-quit'];
    return $map[$s] ?? 's-ing';
}
// 身份证脱敏
function mMaskId(string $v): string {
    return strlen($v) >= 15 ? substr($v, 0, 6) . '********' . substr($v, -4) : $v;
}
// 手机号脱敏
function mMaskPhone(string $v): string {
    return strlen($v) >= 11 ? substr($v, 0, 3) . '****' . substr($v, -4) : $v;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>所有学员管理</title>
<!-- PWA / 苹果主屏幕 -->
<link rel="manifest" href="manifest.json">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="学员管理">
<meta name="theme-color" content="#2b3a67">
<link rel="apple-touch-icon" href="assets/icon-180.png">
<link rel="icon" type="image/png" href="assets/favicon.png">
<link rel="stylesheet" href="css/mobile.css">
</head>
<body>

<!-- 顶部：标题 + 搜索 -->
<header class="m-top">
    <div class="m-title">所有学员管理</div>
    <form class="m-search" method="get" action="m.php">
        <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
        <input type="hidden" name="coach" value="<?= htmlspecialchars($coach) ?>">
        <input type="text" name="keyword" placeholder="输入关键词搜索" value="<?= htmlspecialchars($keyword) ?>">
        <button type="submit" class="m-search-btn">搜索</button>
    </form>
</header>

<!-- 数据看板：统计卡片（点击筛选） -->
<section class="m-dash">
    <div class="m-dash-cards">
        <a class="m-card-t m-c-all" href="m.php">
            <div class="m-t-num"><?= $mTotalAll ?></div>
            <div class="m-t-label">在册学员</div>
        </a>
        <?php foreach ($mByStatus as $st): ?>
        <a class="m-card-t m-c-<?= $mStatusColor[$st['label']] ?? 'c1' ?>" href="m.php?view=<?= urlencode($st['label']) ?>&coach=<?= urlencode($coach) ?>">
            <div class="m-t-num"><?= $st['count'] ?></div>
            <div class="m-t-label"><?= htmlspecialchars($st['label']) ?></div>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="m-dash-coach">
        <?php foreach ($mByCoach as $c): $pct = $mTotalAll > 0 ? round($c['count'] / $mTotalAll * 100) : 0; ?>
        <a class="m-coach-item" href="m.php?coach=<?= urlencode($c['label']) ?>&view=<?= urlencode($view) ?>">
            <span class="m-coach-label"><?= htmlspecialchars($c['label']) ?></span>
            <span class="m-coach-bar"><span class="m-coach-fill" style="width:<?= $pct ?>%"></span></span>
            <span class="m-coach-count"><?= $c['count'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- 结果统计 -->
<div class="m-result-info">共 <b><?= $total ?></b> 名学员<?= $coach !== '' ? ' · 带教：' . htmlspecialchars($coach) : '' ?></div>

<!-- 学员卡片列表 -->
<main class="m-list">
    <?php if (!$students): ?>
        <div class="m-empty">无匹配数据，请调整查询条件后再试</div>
    <?php endif; ?>
    <?php foreach ($students as $s): ?>
    <div class="m-card">
        <div class="m-card-head">
            <span class="m-name"><?= htmlspecialchars($s['name']) ?></span>
            <span class="m-gender m-g-<?= $s['gender'] === '女' ? 'f' : 'm' ?>"><?= htmlspecialchars($s['gender']) ?></span>
            <span class="m-badge <?= mStatusClass($s['status']) ?>"><?= htmlspecialchars($s['status']) ?></span>
        </div>
        <div class="m-card-line"><span class="k">身份证：</span><?= htmlspecialchars(mMaskId($s['id_card'])) ?></div>
        <div class="m-card-line"><span class="k">班级：</span><?= htmlspecialchars($s['class_name']) ?>　<span class="k">手机：</span><?= htmlspecialchars(mMaskPhone($s['phone'])) ?></div>
        <div class="m-card-line"><span class="k">带教教练：</span><?= htmlspecialchars($s['coach']) ?>　<span class="k">报名：</span><?= htmlspecialchars($s['reg_coach']) ?></div>
    </div>
    <?php endforeach; ?>

    <!-- 分页 -->
    <?php if ($totalPage > 1): ?>
    <?php $qs = "view=" . urlencode($view) . "&coach=" . urlencode($coach) . "&keyword=" . urlencode($keyword); ?>
    <div class="m-pager">
        <?php if ($page > 1): ?><a href="m.php?page=<?= $page-1 ?>&<?= $qs ?>">‹ 上一页</a><?php else: ?><span></span><?php endif; ?>
        <span class="m-pageinfo"><?= $page ?> / <?= $totalPage ?></span>
        <?php if ($page < $totalPage): ?><a href="m.php?page=<?= $page+1 ?>&<?= $qs ?>">下一页 ›</a><?php else: ?><span></span><?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<!-- 版权信息 -->
<footer class="m-footer">
    <p>© <?= date('Y') ?> 学员管理系统 · 数据仅供内部查阅</p>
</footer>

<!-- 底部操作栏 -->
<nav class="m-bottom">
    <button class="m-bitem" id="btnView" type="button">
        <span class="m-bico">👥</span>
        <span class="m-btxt"><?= $view === 'all' ? '所有学员' : htmlspecialchars($view) ?></span>
    </button>
    <button class="m-bitem" id="btnCoach" type="button">
        <span class="m-bico">👨‍🏫</span>
        <span class="m-btxt"><?= $coach === '' ? '带教查询' : htmlspecialchars($coach) ?></span>
    </button>
    <a class="m-bitem" href="admin/login.php">
        <span class="m-bico">⚙️</span>
        <span class="m-btxt">管理后台</span>
    </a>
</nav>

<!-- 左侧滑出：组合筛选菜单（所有学员/班级/状态组合） -->
<div class="m-mask" id="viewMask"></div>
<aside class="m-drawer" id="viewDrawer">
    <div class="m-drawer-title">学员筛选</div>
    <ul class="m-drawer-list" id="viewList"></ul>
</aside>

<!-- 左侧滑出：带教教练筛选 -->
<div class="m-mask" id="coachMask"></div>
<aside class="m-drawer" id="coachDrawer">
    <div class="m-drawer-title">带教查询</div>
    <ul class="m-drawer-list" id="coachList"></ul>
</aside>

<script>
// 组合筛选菜单数据：所有 + 班级 + 状态 + 班级×状态
const viewItems = [
    {label: '所有学员管理', view: 'all'},
    <?php foreach (CLASSES() as $c): ?>
    {label: '<?= $c ?>', view: '<?= $c ?>'},
    <?php endforeach; ?>
    <?php foreach (STATUSES() as $s): ?>
    {label: '<?= $s ?>', view: '<?= $s ?>'},
    <?php endforeach; ?>
    <?php foreach (CLASSES() as $c): foreach (STATUSES() as $s): ?>
    {label: '<?= $c ?><?= $s ?>', view: '<?= $c ?><?= $s ?>'},
    <?php endforeach; endforeach; ?>
];
const coachItems = [
    {label: '全部带教', view: ''},
    <?php foreach (COACHES() as $c): ?>
    {label: '<?= $c ?>', view: '<?= $c ?>'},
    <?php endforeach; ?>
];
const curView = <?= json_encode($view) ?>;
const curCoach = <?= json_encode($coach) ?>;
const curKw = <?= json_encode($keyword) ?>;

function buildList(items, cur, box) {
    box.innerHTML = '';
    items.forEach(it => {
        const li = document.createElement('li');
        li.textContent = it.label;
        if (it.view === cur) li.className = 'active';
        li.onclick = () => { location.href = 'm.php?view=' + encodeURIComponent(it.view) + '&coach=' + encodeURIComponent(curCoach) + '&keyword=' + encodeURIComponent(curKw); };
        box.appendChild(li);
    });
}
function openDrawer(el, mask) {
    el.classList.add('open');
    mask.classList.add('show');
}
function closeAll() {
    document.querySelectorAll('.m-drawer').forEach(d => d.classList.remove('open'));
    document.querySelectorAll('.m-mask').forEach(m => m.classList.remove('show'));
}
document.getElementById('btnView').onclick = () => openDrawer(document.getElementById('viewDrawer'), document.getElementById('viewMask'));
document.getElementById('btnCoach').onclick = () => openDrawer(document.getElementById('coachDrawer'), document.getElementById('coachMask'));
document.querySelectorAll('.m-mask').forEach(m => m.onclick = closeAll);
buildList(viewItems, curView, document.getElementById('viewList'));
buildList(coachItems, curCoach, document.getElementById('coachList'));

// 注册 Service Worker（PWA）
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('sw.js').catch(function () {});
    });
}
</script>
</body>
</html>
