-- ============================================================
-- 学员管理系统 数据表脚本 (MySQL)
--
-- 重要：本文件【不含】创建数据库的语句。
-- 如果你的账号没有 CREATE DATABASE 权限（报错 #1044），
-- 请按下方步骤，先在 phpMyAdmin 手动建库，再导入本文件。
--
-- 手动建库步骤：
--   1) 打开 phpMyAdmin，左侧点「新建」
--   2) 数据库名填 hellcyms（与账号同名，且 config.php 里 DB_NAME 保持一致）
--      排序规则选 utf8mb4_unicode_ci，点「创建」
--   3) 点击左侧刚建好的数据库名，切到「导入」标签
--   4) 选择本文件，点「执行」
-- ============================================================

-- 1. 创建学员表
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name`          VARCHAR(50)  NOT NULL                COMMENT '姓名',
  `coach`         VARCHAR(20)  NOT NULL                COMMENT '带教教练',
  `class_name`    VARCHAR(10)  NOT NULL                COMMENT '班级',
  `gender`        VARCHAR(4)   NOT NULL                COMMENT '性别',
  `id_card`       VARCHAR(18)  NOT NULL                COMMENT '身份证号码',
  `phone`         VARCHAR(20)  NOT NULL                COMMENT '手机号',
  `reg_coach`     VARCHAR(20)  NOT NULL                COMMENT '报名教练',
  `reg_date`      DATE         NOT NULL                COMMENT '报名日期',
  `status`        VARCHAR(10)  NOT NULL DEFAULT '科目一' COMMENT '学习状态',
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_id_card` (`id_card`),
  KEY `idx_name` (`name`),
  KEY `idx_phone` (`phone`),
  KEY `idx_coach` (`coach`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='学员信息表';

-- 3. 插入示例数据（可删除）
INSERT INTO `students` (`name`, `coach`, `class_name`, `gender`, `id_card`, `phone`, `reg_coach`, `reg_date`, `status`) VALUES
('张三', '袁茂胜', 'C1', '男', '513001199001011234', '13800000001', '袁茂胜', '2026-01-10', '科目一'),
('李四', '罗巧林', 'C2', '女', '513001199202023456', '13800000002', '罗巧林', '2026-02-15', '科目二'),
('王五', '田兵', 'C1', '男', '513001199303034567', '13800000003', '其他', '2026-03-20', '科目三'),
('赵六', '袁茂胜', 'C2', '女', '513001199404045678', '13800000004', '袁茂胜', '2026-04-05', '毕业'),
('钱七', '罗巧林', 'C1', '男', '513001199505056789', '13800000005', '罗巧林', '2026-05-12', '退学');

-- 4. 创建后台管理员表
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `username`   VARCHAR(50)  NOT NULL                COMMENT '登录账号',
  `password`   VARCHAR(255) NOT NULL                COMMENT '密码（bcrypt哈希）',
  `real_name`  VARCHAR(50)  DEFAULT NULL            COMMENT '姓名/备注',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台管理员表';

-- 5. 插入默认管理员（账号 admin / 密码 123456）
--    密码已用 bcrypt 加密（对应明文 123456）。请登录后尽快修改默认密码。
INSERT INTO `admins` (`username`, `password`, `real_name`) VALUES
('admin', '$2y$12$mzNNCXb2qfQBUFGSGdSz.uWhugCOnU0VRviMFUwhU2DjR3grlYhaW', '系统管理员');
--    注意：上述 bcrypt 哈希中的 $2y$ 前缀与 PHP password_verify 兼容，可直接使用。

-- 6. 创建数据选项表（后台可动态管理：带教教练、报名教练、性别、班级、学习状态）
DROP TABLE IF EXISTS `options`;
CREATE TABLE `options` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `type`       VARCHAR(20)  NOT NULL                COMMENT '选项类型: coach/reg_coach/gender/class/status',
  `label`      VARCHAR(50)  NOT NULL                COMMENT '选项值',
  `sort_order` INT          NOT NULL DEFAULT 0      COMMENT '排序(越小越靠前)',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_label` (`type`, `label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='数据选项表';

-- 7. 插入默认选项（可在后台修改）
INSERT INTO `options` (`type`, `label`, `sort_order`) VALUES
('coach',     '袁茂胜', 1),
('coach',     '罗巧林', 2),
('coach',     '田兵', 3),
('coach',     '待上车', 4),
('reg_coach', '袁茂胜', 1),
('reg_coach', '罗巧林', 2),
('reg_coach', '田兵', 3),
('reg_coach', '许换', 4),
('reg_coach', '廖换', 5),
('reg_coach', '王毅', 6),
('reg_coach', '其他', 7),
('gender',    '男', 1),
('gender',    '女', 2),
('class',     'C1', 1),
('class',     'C2', 2),
('status',    '科目一', 1),
('status',    '科目二', 2),
('status',    '科目三', 3),
('status',    '毕业', 4),
('status',    '退学', 5);
