<?php
/**
 * 公开前端页：无需登录即可查看学员数据情况
 * 展示：统计数据卡片 + 筛选 + 学员列表 + 分页
 * 修改数据需登录后台 admin/
 */
require_once __DIR__ . '/includes/db.php';

// ---- 手机端自动跳转：手机访问 index.php 时跳转到手机版 m.php ----
// 保留当前筛选参数；_m=0 或 _m=1 可强制指定（桌面/手机）
$isMobileUA = preg_match('/Mobile|Android|iPhone|iPad|iPod|Windows Phone|Opera Mini|IEMobile/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
if (isset($_GET['_m'])) {
    $forceMobile = ($_GET['_m'] === '1');
} else {
    $forceMobile = (bool)$isMobileUA;
}
if ($forceMobile) {
    $qs = [];
    foreach (['keyword', 'coach', 'status', 'class', 'gender', 'page'] as $k) {
        if (($_GET[$k] ?? '') !== '') $qs[] = $k . '=' . urlencode($_GET[$k]);
    }
    $target = 'm.php' . ($qs ? '?' . implode('&', $qs) : '');
    header('Location: ' . $target);
    exit;
}

// ---- 收集筛选参数 ----
$keyword = trim($_GET['keyword'] ?? '');
$coach   = trim($_GET['coach'] ?? '');
$status  = trim($_GET['status'] ?? '');
$class   = trim($_GET['class'] ?? '');
$gender  = trim($_GET['gender'] ?? '');

// 每页条数（默认 20，可通过 ?limit= 指定，限制 1~100）
$perPage = (int)($_GET['limit'] ?? 20);
if ($perPage < 1) $perPage = 20;
if ($perPage > 100) $perPage = 100;
$page    = max(1, (int)($_GET['page'] ?? 1));

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
if ($status !== '') {
    $where[] = 'status LIKE :status';
    $params[':status'] = '%' . $status . '%';
}
if ($class !== '') {
    $where[] = 'class_name LIKE :class';
    $params[':class'] = '%' . $class . '%';
}
if ($gender !== '') {
    $where[] = 'gender LIKE :gender';
    $params[':gender'] = '%' . $gender . '%';
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

// ---- 统计数据（全库）----
function statTotal(): int {
    return (int)db()->query('SELECT COUNT(*) AS c FROM students')->fetch()['c'];
}
function statByField(string $field): array {
    $rows = db()->query("SELECT $field AS k, COUNT(*) AS c FROM students GROUP BY $field ORDER BY c DESC")->fetchAll();
    return array_map(fn($r) => ['label' => $r['k'], 'count' => (int)$r['c']], $rows);
}
$totalAll   = statTotal();
$byStatus   = statByField('status');
$byCoach    = statByField('coach');
$byClass    = statByField('class_name');
$byGender   = statByField('gender');

function statusClass(string $s): string {
    $map = ['毕业' => 'badge-green', '退学' => 'badge-red'];
    return $map[$s] ?? 'badge-blue';
}
// 身份证脱敏显示
function maskIdCard(string $v): string {
    return strlen($v) >= 15 ? substr($v, 0, 6) . '********' . substr($v, -4) : $v;
}
// 手机号脱敏显示
function maskPhone(string $v): string {
    return strlen($v) >= 11 ? substr($v, 0, 3) . '****' . substr($v, -4) : $v;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>学员数据总览 - 学员管理系统</title>

    <!-- PWA / 苹果主屏幕 -->
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="学员管理">
    <meta name="theme-color" content="#1f2a44">
    <link rel="apple-touch-icon" href="assets/icon-180.png">
    <link rel="icon" type="image/png" href="assets/favicon.png">

    <link rel="stylesheet" href="css/front.css">
</head>
<body>

<!-- 顶部导航 -->
<header class="site-header">
    <div class="header-inner">
        <div class="logo">
            <span class="logo-ico">🚗</span>
            <span class="logo-text">学员管理系统</span>
        </div>
        <div class="header-right">
            <span class="live-tag"><span class="dot"></span> 数据实时更新</span>
            <a class="btn btn-admin" href="admin/login.php">🔒 管理后台</a>
        </div>
    </div>
</header>

<!-- 主内容 -->
<main class="page">

    <!-- 页头 -->
    <section class="hero">
        <div class="hero-inner">
            <h1>学员数据总览</h1>
            <p>驾校学员学习进度一览，数据实时统计</p>
        </div>
    </section>

    <!-- 统计卡片 -->
    <section class="stat-cards">
        <a class="card card-total" href="index.php">
            <div class="card-num"><?= $totalAll ?></div>
            <div class="card-label">在册学员</div>
            <div class="card-ico">👥</div>
        </a>
        <?php
        $statusColor = ['科目一' => 'blue', '科目二' => 'cyan', '科目三' => 'purple', '毕业' => 'green', '退学' => 'red'];
        $statusIco   = ['科目一' => '📘', '科目二' => '📗', '科目三' => '📙', '毕业' => '🎓', '退学' => '🚫'];
        foreach ($byStatus as $s): ?>
        <a class="card card-<?= $statusColor[$s['label']] ?? 'blue' ?>" href="index.php?status=<?= urlencode($s['label']) ?>">
            <div class="card-num"><?= $s['count'] ?></div>
            <div class="card-label"><?= htmlspecialchars($s['label']) ?></div>
            <div class="card-ico"><?= $statusIco[$s['label']] ?? '🏷️' ?></div>
        </a>
        <?php endforeach; ?>
    </section>

    <!-- 分布概览 -->
    <section class="dist-row">
        <div class="panel">
            <h3 class="panel-title">👨‍🏫 按带教教练分布 <span class="panel-hint">（点击查看该教练学员）</span></h3>
            <div class="bar-list">
                <?php foreach ($byCoach as $c):
                    $pct = $totalAll > 0 ? round($c['count'] / $totalAll * 100) : 0; ?>
                    <a class="bar-item" href="index.php?coach=<?= urlencode($c['label']) ?>">
                        <span class="bar-label"><?= htmlspecialchars($c['label']) ?></span>
                        <span class="bar-track"><span class="bar-fill" style="width:<?= $pct ?>%"></span></span>
                        <span class="bar-count"><?= $c['count'] ?>人</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="panel">
            <h3 class="panel-title">📊 其他分布 <span class="panel-hint">（点击查看）</span></h3>
            <div class="mini-grid">
                <div class="mini">
                    <div class="mini-title">班级</div>
                    <?php foreach ($byClass as $c): ?>
                        <a class="mini-row" href="index.php?class=<?= urlencode($c['label']) ?>"><span><?= htmlspecialchars($c['label']) ?></span><b><?= $c['count'] ?></b></a>
                    <?php endforeach; ?>
                </div>
                <div class="mini">
                    <div class="mini-title">性别</div>
                    <?php foreach ($byGender as $g): ?>
                        <a class="mini-row" href="index.php?gender=<?= urlencode($g['label']) ?>"><span><?= htmlspecialchars($g['label']) ?></span><b><?= $g['count'] ?></b></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- 列表 -->
    <section class="list-section">
        <div class="list-head">
            <h2>学员列表</h2>
            <span class="list-count">共 <?= $total ?> 名学员</span>
        </div>

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
            <select name="class">
                <option value="">全部班级</option>
                <?php foreach (CLASSES() as $c): ?>
                    <option value="<?= $c ?>" <?= $class === $c ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
            </select>
            <select name="gender">
                <option value="">全部性别</option>
                <?php foreach (GENDERS() as $g): ?>
                    <option value="<?= $g ?>" <?= $gender === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-query">查询</button>
            <a href="index.php" class="btn btn-reset">重置</a>
            <label class="limit-label">每页
                <input type="number" name="limit" class="limit-input" min="1" max="100" value="<?= (int)$perPage ?>" onchange="this.form.submit()">
                行
            </label>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>姓名</th>
                    <th>带教教练</th>
                    <th>班级</th>
                    <th>性别</th>
                    <th>手机号</th>
                    <th>报名教练</th>
                    <th>报名日期</th>
                    <th>学习状态</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$students): ?>
                    <tr><td colspan="8" class="empty">无匹配数据，请调整查询条件后再试</td></tr>
                <?php endif; ?>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td class="t-name"><?= htmlspecialchars($s['name']) ?></td>
                        <td><?= htmlspecialchars($s['coach']) ?></td>
                        <td><span class="tag-class"><?= htmlspecialchars($s['class_name']) ?></span></td>
                        <td><?= htmlspecialchars($s['gender']) ?></td>
                        <td><?= maskPhone($s['phone']) ?></td>
                        <td><?= htmlspecialchars($s['reg_coach']) ?></td>
                        <td><?= htmlspecialchars($s['reg_date']) ?></td>
                        <td><span class="badge <?= statusClass($s['status']) ?>"><?= htmlspecialchars($s['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 分页 -->
        <?php if ($totalPage > 1): ?>
        <?php $qsBase = "keyword=" . urlencode($keyword) . "&coach=" . urlencode($coach) . "&status=" . urlencode($status) . "&class=" . urlencode($class) . "&gender=" . urlencode($gender) . "&limit=" . (int)$perPage; ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="index.php?page=<?= $page-1 ?>&<?= $qsBase ?>">‹ 上一页</a>
            <?php endif; ?>
            <span class="page-info">第 <?= $page ?> / <?= $totalPage ?> 页（每页 <?= (int)$perPage ?> 行）</span>
            <?php if ($page < $totalPage): ?>
                <a href="index.php?page=<?= $page+1 ?>&<?= $qsBase ?>">下一页 ›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="privacy-tip">🔒 为保护学员隐私，手机号/身份证已做脱敏处理，完整信息请登录后台查看</div>
    </section>
</main>

<footer class="site-footer">
    <p>© <?= date('Y') ?> 学员管理系统 · 数据仅供内部查阅</p>
</footer>
<script>
    // 注册 Service Worker（PWA 离线可用；仅在支持的环境生效）
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('sw.js').catch(function () {});
        });
    }
</script>
</body>
</html>
