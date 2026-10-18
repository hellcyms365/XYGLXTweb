<?php
/**
 * 数据库连接类（PDO，预编译防注入）
 */

require_once __DIR__ . '/../config.php';

/**
 * 获取 PDO 数据库连接实例（单例）
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // 生产环境请勿输出详细错误信息
            die('数据库连接失败：' . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}

/**
 * 选项类型定义（type -> 中文名）
 */
const OPTION_TYPES = [
    'coach'     => '带教教练',
    'reg_coach' => '报名教练',
    'gender'    => '性别',
    'class'     => '班级',
    'status'    => '学习状态',
];

/**
 * 获取指定类型的全部选项值（按 sort_order 排序），支持静态缓存
 * @param string $type coach/reg_coach/gender/class/status
 * @return string[]
 */
function get_options(string $type): array
{
    static $cache = [];
    if (isset($cache[$type])) {
        return $cache[$type];
    }
    $stmt = db()->prepare('SELECT label FROM options WHERE type = :type ORDER BY sort_order ASC, id ASC');
    $stmt->execute([':type' => $type]);
    $cache[$type] = array_column($stmt->fetchAll(), 'label');
    return $cache[$type];
}

/**
 * 获取某个类型是否存在指定选项值
 * @param string $type
 * @param string $value
 * @return bool
 */
function option_exists(string $type, string $value): bool
{
    return in_array($value, get_options($type), true);
}

// ---- 兼容函数：把旧常量用法 COACHES 等改为函数调用 COACHES() ----
function COACHES(): array    { return get_options('coach'); }       // 带教教练
function REG_COACHES(): array { return get_options('reg_coach'); }  // 报名教练
function GENDERS(): array    { return get_options('gender'); }      // 性别
function STATUSES(): array   { return get_options('status'); }      // 学习状态
function CLASSES(): array    { return get_options('class'); }       // 班级

/**
 * 安全取某类型第一个选项（为空时返回默认值）
 * @param string $type
 * @param string $default
 * @return string
 */
function first_option(string $type, string $default = ''): string
{
    $opts = get_options($type);
    return $opts ? $opts[0] : $default;
}

/**
 * 获取某类型的完整选项记录（含 id、label、sort_order），用于选项管理页
 * @param string $type
 * @return array[]
 */
function get_options_full(string $type): array
{
    $stmt = db()->prepare('SELECT id, label, sort_order FROM options WHERE type = :type ORDER BY sort_order ASC, id ASC');
    $stmt->execute([':type' => $type]);
    return $stmt->fetchAll();
}
