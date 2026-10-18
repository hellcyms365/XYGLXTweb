# 学员管理系统（PHP + MySQL）

驾校「学员管理系统」源码，用于教练对学员的管理。纯原生 PHP + MySQL，无框架依赖，开箱即用。

## 功能特性

**前端（公开，无需登录）**
- 学员数据总览：统计卡片（在册总数、各学习状态人数）+ 教练分布 + 班级/性别分布 + 学员列表
- 支持关键词搜索、带教教练筛选、学习状态筛选、分页
- 手机号/身份证自动脱敏显示，保护学员隐私
- 仅查看，修改需登录后台

**后台（登录后可管理）**
- 后台登录鉴权：所有管理页面需登录后访问，账号密码校验 + 会话保护
- 后台管理布局：左侧导航栏 + 顶部栏，含当前用户展示与退出登录
- 学员信息管理：姓名、带教教练、班级、性别、身份证号码、手机号、报名教练、报名日期、学习状态
- 新增 / 编辑 / 删除学员：表单校验（手机号11位、身份证18位、日期格式、身份证唯一性）
- 列表展示：表格分页展示，学习状态标签分色（科目进行中=蓝、毕业=绿、退学=红）
- **用户管理**：后台新增/编辑/删除管理员账号，可重置密码；安全边界（不能删除自己、不能删除仅剩的管理员）
- **批量导入**：上传 CSV 文件批量录入学员，含字段校验与导入结果明细，可下载模板
- **数据导出**：学员列表一键导出 CSV（Excel 兼容，UTF-8 中文不乱码），支持按当前筛选条件导出
- **全界面响应式**：前端、后台、登录页适配手机/平板/桌面，宽表格支持横向滚动

**通用**
- 防注入 / 防 XSS：全部 SQL 使用 PDO 预编译，输出经 htmlspecialchars 转义
- 枚举值集中管理：在 `includes/db.php` 常量中修改即可全站生效

## 目录结构

```
学员管理系统-php/
├── index.php           # 公开前端页（无需登录，查看学员数据情况）
├── config.php          # 数据库配置
├── database.sql        # 建库建表 + 管理员/示例数据
├── admin/              # 后台管理（需登录）
│   ├── index.php       #   学员列表页（搜索/筛选/分页）
│   ├── add.php         #   新增学员
│   ├── edit.php        #   编辑学员
│   ├── delete.php      #   删除学员
│   ├── import.php      #   批量导入学员（CSV + 模板下载）
│   ├── export.php      #   导出学员数据（CSV，支持筛选）
│   ├── login.php       #   后台登录页
│   ├── logout.php      #   退出登录
│   ├── users.php       #   用户管理（管理员列表）
│   ├── user_add.php    #   新增管理员
│   ├── user_edit.php   #   编辑管理员（可重置密码）
│   ├── user_delete.php #   删除管理员
│   └── options.php     #   选项管理（动态增删改下拉选项）
├── includes/
│   ├── db.php          # 数据库连接 + 选项读取函数（get_options 等）
│   ├── auth.php        # 登录鉴权（会话/校验/强制登录）
│   ├── header.php      # 后台布局公共头部（侧边栏+顶部栏）
│   └── footer.php      # 后台布局公共尾部
└── css/
    ├── front.css       # 前端页面样式（含响应式）
    └── style.css       # 后台管理 + 登录页样式（含响应式）
```

## 部署步骤

### 环境要求
- PHP 7.4+（推荐 8.0+），启用 PDO MySQL 扩展
- MySQL 5.7+ / MariaDB

### 1. 建库并导入表

**如果你的账号没有建库权限（报错 #1044），请按下面「phpMyAdmin 手动建库」操作；** 有权限（root 等）时可直接用命令行导入。

**方式 A：phpMyAdmin 手动建库（推荐，无权限时必用）**
1. 打开 phpMyAdmin，左侧点「新建」
2. 数据库名填 `hellcyms`（与账号同名，可规避 #1044 权限问题；`config.php` 的 `DB_NAME` 已保持一致），排序规则选 `utf8mb4_unicode_ci`，点「创建」
3. 点击左侧刚建好的数据库名，切到「导入」标签
4. 选择 `database.sql`，点「执行」，会创建 `students` 表、`admins` 表、`options` 表，并插入示例数据和默认管理员

**方式 B：命令行（账号有 CREATE DATABASE 权限时）**
```bash
mysql -u root -p < database.sql
```

> ⚠️ 如果你之前已导入过旧版 `database.sql`（没有 admins 表），请**重新导入新版文件**，或用 SQL 补建管理员表：
> ```sql
> USE hellcyms;
> CREATE TABLE IF NOT EXISTS admins (
>   id INT UNSIGNED NOT NULL AUTO_INCREMENT,
>   username VARCHAR(50) NOT NULL,
>   password VARCHAR(255) NOT NULL,
>   real_name VARCHAR(50) DEFAULT NULL,
>   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
>   PRIMARY KEY (id), UNIQUE KEY uk_username (username)
> ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
> INSERT INTO admins (username, password, real_name)
> VALUES ('admin', '$2y$12$mzNNCXb2qfQBUFGSGdSz.uWhugCOnU0VRviMFUwhU2DjR3grlYhaW', '系统管理员');
> ```

### 2. 修改数据库配置
打开 `config.php`，填入你的数据库信息（**已为你填好账号 hellcyms 及密码**，如密码有变更请修改）：
```php
define('DB_HOST', 'localhost');  // 数据库主机
define('DB_PORT', 3306);         // 端口
define('DB_NAME', 'hellcyms');   // 库名（与账号同名）
define('DB_USER', 'hellcyms');   // 用户名
define('DB_PASS', '6613Mm436');  // 密码
```

### 3. 放置站点目录
将整个 `学员管理系统-php` 目录放到 Web 根目录（如 XAMPP 的 `htdocs`、phpStudy 的 `www`、Nginx/Apache 站点根）。

### 4. 访问系统
浏览器访问（以本机 PHP 内置服务器为例）：
```bash
cd 学员管理系统-php
php -S localhost:8000
```

- **前端（公开）**：打开 http://localhost:8000 ，无需登录即可查看学员数据总览（统计、分布、列表）
- **后台（需登录）**：访问 http://localhost:8000/admin/login.php 或点击前端右上角「管理后台」

### 5. 登录后台
- **默认账号**：`admin`
- **默认密码**：`123456`

登录后即可进入后台管理学员（增删改）。**请务必尽快修改默认密码**（可自行在 phpMyAdmin 中更新 `admins` 表，或用 PHP 生成新哈希）。

> 提示：前端只读展示，手机号/身份证已脱敏；完整敏感信息需登录后台查看。退出点击右上角或侧边栏「退出登录」。

## 苹果手机使用（PWA 添加到主屏幕）

系统已支持 **PWA（渐进式网页应用）**，可在 iPhone/iPad 上像原生 App 一样使用：

1. 用 **Safari** 打开系统网址（如 http://你的服务器/）
2. 点击底部工具栏的 **分享按钮**（方框加箭头）
3. 选择 **「添加到主屏幕」**
4. 点击「添加」——主屏幕会出现一个带「驾」图标的「学员管理」应用

之后从主屏幕打开即为**全屏独立应用**（无地址栏），图标为蓝紫渐变驾字图标，并支持**离线缓存**已加载的页面。

> 说明：这是网页增强的 PWA 应用，无需上架 App Store。真正原生的 iOS App（.ipa）需 Xcode 开发与签名，本系统不包含。

## 字段选项（后台可动态管理）

学员录入下拉的选项值（带教教练、报名教练、性别、班级、学习状态）已存入数据库 `options` 表，**登录后台「系统设置 → 选项管理」可动态增删改、调整排序**，无需改代码。默认值如下：

| 选项类型 | 默认可选值 |
|---|---|
| 带教教练 | 袁茂胜、罗巧林、田兵 |
| 报名教练 | 袁茂胜、罗巧林、田兵、许换、廖换、王毅、其他 |
| 性别 | 男、女 |
| 班级 | C1、C2 |
| 学习状态 | 科目一、科目二、科目三、毕业、退学 |

> 注意：修改/删除选项只影响后续录入的选择范围，不会改动已录入学员的数据（学员表保存的是当时选择的文本值）。

## 安全说明
- 已使用 PDO 预编译，防 SQL 注入
- 所有输出经 `htmlspecialchars()` 转义，防 XSS
- 密码使用 bcrypt 哈希存储，登录后 session 重新生成 ID 防会话固定
- 所有管理页面强制登录校验（`includes/auth.php` 的 `require_login()`）
- 生产环境请务必：修改默认管理员密码、修改 `config.php` 中的数据库密码、禁用目录浏览

> 说明：本系统为纯源码交付，需你在自己的 PHP + MySQL 环境中部署运行；示例数据可删除，不影响使用。
