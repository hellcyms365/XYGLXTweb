<?php
/**
 * 学员数据导出为 CSV（Excel 兼容）
 * 支持按当前筛选条件导出（keyword/coach/status），或全部导出
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

// ---- 收集筛选参数（与列表页一致）----
$keyword = trim($_GET['keyword'] ?? '');
$coach   = trim($_GET['coach'] ?? '');
$status  = trim($_GET['status'] ?? '');

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

$stmt = db()->prepare("SELECT id, name, coach, class_name, gender, id_card, phone, reg_coach, reg_date, status, created_at FROM students $whereSql ORDER BY id ASC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ---- 输出 CSV ----
// 带 UTF-8 BOM，Excel 可直接打开且中文不乱码
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="students_' . date('Ymd_His') . '.csv"');
echo "\xEF\xBB\xBF";

// 表头
$headers = ['ID', '姓名', '带教教练', '班级', '性别', '身份证号码', '手机号', '报名教练', '报名日期', '学习状态', '创建时间'];
echo implode(',', $headers) . "\n";

// 数据行（用双引号包裹，转义内部引号）
foreach ($rows as $r) {
    $line = [
        $r['id'],
        $r['name'],
        $r['coach'],
        $r['class_name'],
        $r['gender'],
        $r['id_card'],
        $r['phone'],
        $r['reg_coach'],
        $r['reg_date'],
        $r['status'],
        $r['created_at'],
    ];
    $line = array_map(function ($v) {
        return '"' . str_replace('"', '""', $v) . '"';
    }, $line);
    echo implode(',', $line) . "\n";
}
exit;
