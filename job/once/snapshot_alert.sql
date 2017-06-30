use 3a;
CREATE TABLE IF NOT EXISTS `snapshot_history` (
  `snapid` int(10) NOT NULL AUTO_INCREMENT COMMENT '快照ID',
  `instanceid` int(10) NOT NULL COMMENT '实例ID',
  `elementid` int(10) NOT NULL COMMENT '元素ID',
  `updatetime` int(10) NOT NULL COMMENT '更新时间',
  `content` text COMMENT '查询条件',
  `snapshot` longtext NOT NULL COMMENT '存储查询结果',
  `manualupdate` int(10) DEFAULT NULL COMMENT '手动更新快照设置为1, 编辑和点击右下角快照按钮更新',
  PRIMARY KEY (`snapid`),
  KEY `instanceid` (`instanceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

CREATE TABLE IF NOT EXISTS `event_history` (
  `instanceid` int(10) NOT NULL COMMENT '实例ID',
  `elementid` int(10) NOT NULL COMMENT '元素ID',
  `triggertime` int(10) NOT NULL COMMENT '触发时间',
  `deletetime` int(10) NOT NULL DEFAULT '0' COMMENT '模型删除时间，保证elementid清0时联合主键唯一',
  `sevtext` varchar(32) NOT NULL COMMENT '事件级别',
  `trigtext` text NOT NULL COMMENT '事件详情',
  `action` varchar(16) NOT NULL COMMENT '触发动作',
  `status` tinyint(1) NOT NULL COMMENT '动作完成1/0',
  PRIMARY KEY (`elementid`,`triggertime`,`deletetime`),
  KEY `instanceid` (`instanceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

ALTER TABLE `element`  ADD COLUMN `snapid` INT(11) NULL DEFAULT NULL COMMENT '最新的快照id, 和快照历史表关联' AFTER `snapshot`;
ALTER TABLE `tenant`  ADD COLUMN `allowupdatesnapshot` INT(1) NULL DEFAULT '0' COMMENT '是否允许定时更新快照' AFTER `allowdownload`,  ADD COLUMN `alloweventalert` INT(1) NULL DEFAULT '0' COMMENT '是否允许事件预警' AFTER `allowupdatesnapshot`;
ALTER TABLE `tenant_role_mapping`  ADD COLUMN `allowupdatesnapshot` INT(1) NULL DEFAULT '0' COMMENT '是否允许定时更新快照' AFTER `allowdownload`,  ADD COLUMN `alloweventalert` INT(1) NULL DEFAULT '0' COMMENT '是否允许事件预警' AFTER `allowupdatesnapshot`;
ALTER TABLE `customer_navigate`  ADD COLUMN `mergesched` TEXT NULL COMMENT '合并定时计划字段数据' AFTER `mergedata`;
ALTER TABLE `element`  ADD INDEX `instanceid` (`instanceid`);
运行 import_snapshot.php
/*运行完脚本,测试后,没有问题时执行这个删除*/
ALTER TABLE `element`  DROP COLUMN `snapshot`;
