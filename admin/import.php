<?php
/**
 * 学员批量导入
 * 支持：上传 CSV 文件（UTF-8，可带 BOM），批量新增学员
 * 模板：下载 import_template.csv
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$result = null; // ['success'=>[], 'fail'=>[]]

// ---- 下载模板 ----
if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="import_template.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM，Excel 正确识别中文
    $cols = ['姓名', '带教教练', '班级', '性别', '身份证号码', '手机号', '报名教练', '报名日期', '学习状态'];
    echo implode(',', $cols) . "\n";
    // 示例行
    echo '张三,袁茂胜,C1,男,513001199001011234,13800000001,袁茂胜,2026-01-10,科目一' . "\n";
    exit;
}

// ---- 处理上传 ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result = ['success' => [], 'fail' => [['row' => 0, 'reason' => '文件上传失败']]];
    } elseif (!is_uploaded_file($file['tmp_name'])) {
        $result = ['success' => [], 'fail' => [['row' => 0, 'reason' => '非法上传']]];
    } else {
        $result = process_csv($file['tmp_name']);
    }
}

/**
 * 解析并导入 CSV
 * @param string $path
 * @return array{success:array,fail:array}
 */
function process_csv(string $path): array
{
    $content = file_get_contents($path);
    // 去掉 UTF-8 BOM
    if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
        $content = substr($content, 3);
    }
    $content = str_replace(["\r\n", "\r"], "\n", $content);
    $lines = explode("\n", $content);

    $success = [];
    $fail = [];

    // 固定字段顺序（与模板一致）
    $fields = ['name', 'coach', 'class_name', 'gender', 'id_card', 'phone', 'reg_coach', 'reg_date', 'status'];

    // 是否有表头（第一行包含"姓名"字样则跳过）
    $skipHeader = false;
    if ($lines && strpos($lines[0], '姓名') !== false) {
        $skipHeader = true;
    }

    foreach ($lines as $i => $line) {
        $rowNum = $i + 1;
        $line = trim($line);
        if ($line === '') continue;
        if ($skipHeader && $i === 0) continue;

        // 解析 CSV 行（处理引号包裹的含逗号字段，如姓名/教练一般无逗号，简单 split）
        // 显式传入 $escape 参数（PHP 8.4 起默认值将改变，需显式指定以避免弃用警告）
        $cols = str_getcsv($line, ',', '"', '\\');

        if (count($cols) < count($fields)) {
            $fail[] = ['row' => $rowNum, 'reason' => '字段不足（应为9列：姓名,带教教练,班级,性别,身份证,手机号,报名教练,报名日期,学习状态）'];
            continue;
        }

        $data = [];
        foreach ($fields as $k => $f) {
            $data[$f] = normalize_field($cols[$k] ?? '');
        }

        // ---- 校验 ----
        $reason = validate_row($data);
        if ($reason !== '') {
            $fail[] = ['row' => $rowNum, 'reason' => $reason];
            continue;
        }

        // 入库
        $stmt = db()->prepare(
            'INSERT INTO students (name, coach, class_name, gender, id_card, phone, reg_coach, reg_date, status)
             VALUES (:name, :coach, :class_name, :gender, :id_card, :phone, :reg_coach, :reg_date, :status)'
        );
        $stmt->execute([
            ':name' => $data['name'], ':coach' => $data['coach'], ':class_name' => $data['class_name'],
            ':gender' => $data['gender'], ':id_card' => $data['id_card'], ':phone' => $data['phone'],
            ':reg_coach' => $data['reg_coach'], ':reg_date' => $data['reg_date'], ':status' => $data['status'],
        ]);
        $success[] = ['row' => $rowNum, 'name' => $data['name']];
    }

    return ['success' => $success, 'fail' => $fail];
}

/**
 * 规范化字段值：去除首尾空白、全角空格转半角、压缩连续空格
 * 避免因 Excel 导出带来的不可见空格导致「选项不合法」误判
 */
function normalize_field(string $v): string
{
    // 兼容 PHP 8.1+：若调用方误传 null，转空串而非触发 Deprecated
    if ($v === null) { $v = ''; }
    $v = trim($v);
    $v = str_replace(["\u{3000}", "\t"], ' ', $v); // 全角空格/制表符 -> 半角空格
    $v = preg_replace('/\s+/u', ' ', $v);          // 压缩连续空白
    return trim($v);
}

/**
 * 校验单行数据
 * @param array $d
 * @return string 空串表示通过，否则返回错误原因
 */
function validate_row(array $d): string
{
    if ($d['name'] === '') return '姓名不能为空';
    if (!in_array($d['coach'], COACHES(), true)) return '带教教练不合法';
    if (!in_array($d['class_name'], CLASSES(), true)) return '班级不合法';
    if (!in_array($d['gender'], GENDERS(), true)) return '性别不合法';
    if ($d['id_card'] === '') return '身份证号不能为空'; // 可输入不完整或汉字，仅校验非空
    if ($d['phone'] === '') return '手机号不能为空';     // 可输入汉字或少位数，仅校验非空
    if (!in_array($d['reg_coach'], REG_COACHES(), true)) return '报名教练不合法';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['reg_date'])) return '报名日期格式不正确（YYYY-MM-DD）';
    if (!in_array($d['status'], STATUSES(), true)) return '学习状态不合法';
    return '';
}

$pageTitle = '批量导入';
$activeMenu = 'import';
require __DIR__ . '/../includes/header.php';
?>

    <div class="panel-toolbar">
        <div class="panel-toolbar-t">批量导入学员</div>
        <a class="btn btn-secondary" href="import.php?template=1">⬇ 下载 CSV 模板</a>
    </div>

    <?php if ($result && $result['fail']): ?>
        <div class="alert alert-error">
            导入失败 <?= count($result['fail']) ?> 条（见下方明细），成功 <?= count($result['success']) ?> 条
        </div>
    <?php elseif ($result && $result['success']): ?>
        <div class="alert alert-success">✔ 成功导入 <?= count($result['success']) ?> 条学员记录</div>
    <?php endif; ?>

    <form class="form-card" method="post" action="import.php" enctype="multipart/form-data">
        <div class="form-item">
            <label>选择 CSV 文件 <em>*</em></label>
            <input type="file" name="csv_file" accept=".csv,text/csv" required>
            <div class="form-hint">支持 UTF-8 编码 CSV（可带 BOM，Excel 另存为 CSV 时选择 UTF-8）。文件需含 9 列，顺序与模板一致。</div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">开始导入</button>
            <a href="index.php" class="btn btn-plain">返回列表</a>
        </div>
    </form>

    <!-- 字段格式说明 -->
    <div class="table-card">
        <div class="table-head">
            <div class="table-head-t">字段格式要求</div>
            <div class="table-head-sub">列顺序与模板一致</div>
        </div>
        <div class="table-wrap">
        <table class="table">
            <thead><tr><th>列</th><th>字段</th><th>可选值 / 格式</th></tr></thead>
            <tbody>
                <tr><td>1</td><td class="t-name">姓名</td><td>必填，文本</td></tr>
                <tr><td>2</td><td class="t-name">带教教练</td><td><?= htmlspecialchars(implode('、', COACHES())) ?></td></tr>
                <tr><td>3</td><td class="t-name">班级</td><td><?= htmlspecialchars(implode('、', CLASSES())) ?></td></tr>
                <tr><td>4</td><td class="t-name">性别</td><td><?= htmlspecialchars(implode('、', GENDERS())) ?></td></tr>
                <tr><td>5</td><td class="t-name">身份证号码</td><td>必填，可完整、不完整或汉字</td></tr>
                <tr><td>6</td><td class="t-name">手机号</td><td>必填，可汉字或少位数</td></tr>
                <tr><td>7</td><td class="t-name">报名教练</td><td><?= htmlspecialchars(implode('、', REG_COACHES())) ?></td></tr>
                <tr><td>8</td><td class="t-name">报名日期</td><td>YYYY-MM-DD</td></tr>
                <tr><td>9</td><td class="t-name">学习状态</td><td><?= htmlspecialchars(implode('、', STATUSES())) ?></td></tr>
            </tbody>
        </table>
        </div>
    </div>

    <?php if ($result && ($result['fail'] || $result['success'])): ?>
    <div class="table-card">
        <div class="table-head">
            <div class="table-head-t">导入结果明细</div>
            <div class="table-head-sub">成功 <?= count($result['success']) ?> 条 / 失败 <?= count($result['fail']) ?> 条</div>
        </div>
        <div class="table-wrap">
        <table class="table">
            <thead><tr><th>行号</th><th>状态</th><th>内容</th></tr></thead>
            <tbody>
                <?php foreach ($result['success'] as $s): ?>
                <tr><td class="t-id"><?= $s['row'] ?></td><td><span class="badge badge-green">成功</span></td><td><?= htmlspecialchars($s['name']) ?></td></tr>
                <?php endforeach; ?>
                <?php foreach ($result['fail'] as $f): ?>
                <tr><td class="t-id"><?= $f['row'] ?></td><td><span class="badge badge-red">失败</span></td><td class="t-muted"><?= htmlspecialchars($f['reason']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
