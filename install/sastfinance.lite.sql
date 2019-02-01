DROP Database IF EXISTS `<{DB_NAME}>`;
CREATE Database <{DB_NAME}>;
USE <{DB_NAME}>;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `uid` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `real_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `OPENID` varchar(255) NOT NULL,
  `SID` varchar(255) NOT NULL,
  `rtime` varchar(255) NOT NULL,
  `portrait` varchar(255) DEFAULT NULL COMMENT '头像',
  `ip` varchar(255) NOT NULL,
  `department` int DEFAULT -1 COMMENT '所属部门',
  PRIMARY KEY (`uid`)
);


-- ----------------------------
-- Table structure for authority
-- ----------------------------
DROP TABLE IF EXISTS `authority`;
CREATE TABLE `authority`  (
  `auth_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(11) UNSIGNED NOT NULL,
  `auth` tinyint DEFAULT 0 COMMENT '权限等级?',
  `forever` tinyint DEFAULT 1 COMMENT '永久权限?',
  `until` varchar(255) NOT NULL COMMENT '权限有效期',
  PRIMARY KEY (`auth_id`)
);

-- ----------------------------
-- Table structure for reimbursements
-- ----------------------------
DROP TABLE IF EXISTS `reimbursements`;
CREATE TABLE `reimbursements`  (
  `rid` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT '发起人',
  `name` varchar(255) NOT NULL COMMENT '报销内容',
  `remarks` varchar(2048) NOT NULL COMMENT '备注',
  `department` int DEFAULT -1 COMMENT '部门',
  `status` tinyint DEFAULT 0 COMMENT '当前状态',
  `money` int(11) NOT NULL COMMENT '金额',
  `invoice` varchar(255) DEFAULT NULL COMMENT '发票文件',
  `transaction_voucher` varchar(255) DEFAULT NULL COMMENT '交易凭证',
  `declaration` varchar(255) DEFAULT NULL COMMENT '申报单',
  `time` varchar(255) DEFAULT NULL COMMENT '申报日期',
  PRIMARY KEY (`rid`)
);

-- ----------------------------
-- Table structure for change_log
-- ----------------------------
DROP TABLE IF EXISTS `change_log`;
CREATE TABLE `change_log`  (
  `alid` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT '操作者',
  `rid` int(11) NOT NULL COMMENT '报销项目',
  `change_type` tinyint DEFAULT 0 COMMENT '操作类型',
  `status` tinyint DEFAULT 0 COMMENT '操作后状态',
  `remarks` varchar(255) NOT NULL COMMENT '备注',
  `time` varchar(255) DEFAULT NULL COMMENT '审批日期',
  PRIMARY KEY (`alid`)
);

-- ----------------------------
-- Table structure for department
-- ----------------------------
DROP TABLE IF EXISTS `department`;
CREATE TABLE `department`  (
  `did` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '部门名',
  `organization` int(11) DEFAULT -1 COMMENT '组织，先不写',
  PRIMARY KEY (`did`)
);