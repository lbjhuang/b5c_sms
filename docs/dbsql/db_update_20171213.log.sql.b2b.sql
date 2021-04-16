
ALTER TABLE `tb_b2b_goods` ADD `percent_sale` FLOAT NOT NULL DEFAULT '0' COMMENT '销售团队分成';
ALTER TABLE `tb_b2b_goods` ADD `percent_purchasing` FLOAT NOT NULL DEFAULT '0' COMMENT '采购团队分成';
ALTER TABLE `tb_b2b_goods` ADD `percent_introduce` FLOAT NOT NULL DEFAULT '0' COMMENT '介绍团队分成';

ALTER TABLE `tb_b2b_order` ADD `submit_state` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '2' COMMENT '提交状态(0无1草稿2已提交)';

--  20171221
CREATE TABLE `tb_b2b_history_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `ORDER_ID` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'OrderId',
  `bk_info` text CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT '标记数据',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='历史删除b2b订单表';

ALTER TABLE `tb_b2b_history_order` ENGINE = InnoDB;

ALTER TABLE `tb_b2b_log` ENGINE = InnoDB;



