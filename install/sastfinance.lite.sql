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
  `reg_ip` varchar(255) NOT NULL,
  `last_login_ip` varchar(255) DEFAULT NULL,
  `department` int DEFAULT -1 COMMENT '所属部门',
  `p_level` varchar(255) NOT NULL COMMENT '权限等级',
  `forever` tinyint DEFAULT 0 COMMENT '永久权限?',
  `until` varchar(255) NOT NULL COMMENT '权限有效期',
  PRIMARY KEY (`uid`)
);

-- ----------------------------
-- Table structure for reimbursements
-- ----------------------------
DROP TABLE IF EXISTS `reimbursements`;
CREATE TABLE `reimbursements`  (
  `rid` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT '发起人',
  `name` varchar(255) NOT NULL COMMENT '报销物品名称',
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
-- Table structure for approval_log
-- ----------------------------
DROP TABLE IF EXISTS `approval_log`;
CREATE TABLE `approval_log`  (
  `alid` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL COMMENT '审批人',
  `rid` int(11) NOT NULL COMMENT '报销记录',
  `status` tinyint DEFAULT 0 COMMENT '审批后状态',
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
  PRIMARY KEY (`did`)
);