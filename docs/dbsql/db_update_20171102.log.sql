-- 物流规则表，自动派单使用
DROP TABLE IF EXISTS `tb_ms_logistics_rules`;
CREATE TABLE `tb_ms_logistics_rules` (
  `ID` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `RULE_NAME` varchar(100) NOT NULL COMMENT '规则名称',
  `DESTN_COUNTRY` varchar(50) NOT NULL COMMENT '物流目的地国家,只可以有一个',
  `DESTN_CITY` varchar(50) DEFAULT NULL COMMENT '物流目的地城市,只可以有一个',
  `SALE_CHANNEL` varchar(1000) NOT NULL COMMENT '销售渠道|平台,CODE码，多个用逗号分隔',
  `SHIPPING_WHSE` varchar(50) NOT NULL COMMENT '出货仓库 CODE码只允许一个',
  `LOGISTICS_CODE` varchar(50) NOT NULL COMMENT '那一家快递,CODE码，来自字典表，只允许一个',
  `LOGISTICS_MODE` varchar(100) NOT NULL COMMENT '物流方式，例如：大韩通运或者德邦E邮宝，与快递公司有关',
  `IS_ENABLE` tinyint(2) unsigned DEFAULT '1' COMMENT '是否启用,默认1=启用,0=禁用',
  `IS_DELETE` tinyint(2) DEFAULT '0' COMMENT '是否删除,默认0=未删除,1=删除',
  `CREATOR` varchar(50) NOT NULL COMMENT '创建人',
  `UPDATE_TIME` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `CREATE_TIME` datetime NOT NULL COMMENT '添加时间',
  `REMARK` varchar(200) DEFAULT NULL COMMENT '备注内容,可为空',
  PRIMARY KEY (`ID`),
  KEY `destination` (`DESTN_COUNTRY`,`DESTN_CITY`),
  KEY `guds` (`SALE_CHANNEL`(255),`SHIPPING_WHSE`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

-- 物流方式|模式数据表
-- 主要是物流公司旗下可以支持的物流方式，平邮、航空小包，省内EMS，香港小包等等

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `tb_ms_logistics_mode`;
CREATE TABLE `tb_ms_logistics_mode` (
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID主键',
  `LOGISTICS_CODE` varchar(50) NOT NULL COMMENT '物流公司在码表中的CODE码',
  `LOGISTICS_MODE` varchar(200) NOT NULL COMMENT '物流模式中文名称',
  `SERVICE_CODE` varchar(100) DEFAULT NULL COMMENT '物流方式代码(服务代码)',
  `CREATOR` varchar(100) NOT NULL,
  `CREATE_TIME` datetime NOT NULL COMMENT '创建时间',
  `UPDATE_TIME` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `IS_ENABLE` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用',
  `IS_DELETE` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '是否否删除',
  `REMARK` varchar(255) DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `u_idx` (`LOGISTICS_CODE`,`LOGISTICS_MODE`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- 物流模式基础数据
BEGIN;
INSERT INTO `tb_ms_logistics_mode` VALUES (1, 'N000701200', '由德邦、泛亚取件后（转发大韩国通运）', NULL, 'ERPAdmin', '2017-10-23 20:09:06', '2017-10-27 17:14:03', 1, 0, '大韩通运');
INSERT INTO `tb_ms_logistics_mode` VALUES (2, 'N000705800', '顺丰快递', NULL, 'ERPAdmin', '2017-10-23 20:09:06', '2017-10-27 17:14:03', 1, 0, '菜鸟物流');
INSERT INTO `tb_ms_logistics_mode` VALUES (3, 'N000705800', '中通快递', NULL, 'ERPAdmin', '2017-10-24 16:13:20', '2017-10-27 17:14:03', 1, 0, '菜鸟对接');
INSERT INTO `tb_ms_logistics_mode` VALUES (4, 'N000705800', '圆通快递', NULL, 'ERPAdmin', '2017-10-24 16:21:51', '2017-10-27 17:14:03', 1, 0, '菜鸟物流对接');
INSERT INTO `tb_ms_logistics_mode` VALUES (5, 'N000705800', '百世快递（泛亚中转)', NULL, 'ERPAdmin', '2017-10-24 16:21:51', '2017-10-27 17:14:03', 1, 0, '菜鸟物流对接');
INSERT INTO `tb_ms_logistics_mode` VALUES (6, 'N000705800', 'Qxpress-上海', NULL, 'ERPAdmin', '2017-10-25 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (7, 'N000705800', 'Qxpress-广州', NULL, 'ERPAdmin', '2017-10-26 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (8, 'N000701600', '出口易专线', 'CUE', 'ERPAdmin', '2017-10-27 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (9, 'N000701600', '出口易Easy邮平邮（荷兰邮政平邮）', 'CUN', 'ERPAdmin', '2017-10-28 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (10, 'N000701600', '出口易上海本地中邮挂号', 'CLS', 'ERPAdmin', '2017-10-29 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (11, 'N000701600', '出口易新加坡小包挂号', 'SGP', 'ERPAdmin', '2017-10-30 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (12, 'N000701600', '出口易香港小包挂号', 'HTM', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (13, 'N000701600', '出口易大陆DHL', 'CND', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (14, 'N000701600', '出口易香港DHL', 'DHL', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (15, 'N000701600', '出口易省内EMS', 'EMI', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (16, 'N000701600', '出口易本地EMS', 'EMS', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (17, 'N000701600', '出口易Easy邮挂号（荷兰邮政挂号）', 'NLR', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (18, 'N000701600', '出口易香港小包', '香港小包', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (19, 'N000701600', '出口易香港平邮', '香港平邮', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (20, 'N000701600', '出口易德国专线挂号', 'CGT', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (21, 'N000701600', '出口易英国专线挂号', 'CET', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (22, 'N000701600', '出口易英国快线', 'CEF', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (23, 'N000701600', '出口易新加坡小包平邮', 'SGO', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (24, 'N000701600', '出口易本地E邮宝', 'EUU', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (25, 'N000701600', '出口易省外E邮宝', 'EUF', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (26, 'N000701600', '出口易国际E特快', 'ETK', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (27, 'N000701600', '出口易新加坡平邮', '新加坡平邮', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (28, 'N000701600', '出口易中东DHL', 'MED', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (29, 'N000701600', '出口易中邮平邮', 'CNI', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '出口易');
INSERT INTO `tb_ms_logistics_mode` VALUES (30, 'N000707200', 'WISH达上海仓上门揽件', '300-1', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万色WISH邮');
INSERT INTO `tb_ms_logistics_mode` VALUES (31, 'N000707200', 'WISH邮欧洲标准小包', '201-0', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万色WISH邮');
INSERT INTO `tb_ms_logistics_mode` VALUES (32, 'N000707200', 'WISH邮欧洲经济小包', '200-0', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万色WISH邮');
INSERT INTO `tb_ms_logistics_mode` VALUES (33, 'N000707200', 'WISH邮DHL经济小包', '30-0', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万色WISH邮');
INSERT INTO `tb_ms_logistics_mode` VALUES (34, 'N000707200', 'WISH邮平邮上海仓上门揽收', '0', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万色WISH邮');
INSERT INTO `tb_ms_logistics_mode` VALUES (35, 'N000707200', 'WISH邮挂号上海仓上门揽收', '1', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万色WISH邮');
INSERT INTO `tb_ms_logistics_mode` VALUES (36, 'N000701700', 'lazada线上接口', 'LGS-FM41', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, 'lazada线上接口');
INSERT INTO `tb_ms_logistics_mode` VALUES (37, 'N000705900', 'AliExpress无忧物流-优先', 'CAINIAO_PREMIUM', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '速卖通线上');
INSERT INTO `tb_ms_logistics_mode` VALUES (38, 'N000705900', 'AliExpress无忧物流-标准', 'CAINIAO_STANDARD', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '速卖通线上');
INSERT INTO `tb_ms_logistics_mode` VALUES (39, 'N000705900', 'AliExpress无忧物流-简易', 'CAINIAO_ECONOMY', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '速卖通线上');
INSERT INTO `tb_ms_logistics_mode` VALUES (40, 'N000705900', 'AliExpress中外运-西邮经济小包', 'SINOTRANS_PY', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '速卖通线上');
INSERT INTO `tb_ms_logistics_mode` VALUES (41, 'N000705900', 'AliExpress中外运-西邮标准小包', 'SINOTRANS_AM', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '速卖通线上');
INSERT INTO `tb_ms_logistics_mode` VALUES (42, 'N000707100', '万邑通-线上中国邮政平常小包+（上海）', 'ISP1014', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (43, 'N000707100', '万邑通-易递宝-DHL跨境电商包裹（上海）-eBay', 'WP-DEP103', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (44, 'N000707100', '万邑邮选–DHL跨境电商包裹（上海）', 'ISP0305', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (45, 'N000707100', '万邑邮选-荷兰渠道（挂号）-含电', 'WP-NLP001', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (46, 'N000707100', '万邑邮选-普通渠道（挂号）-上海', 'WP-CNP005', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (47, 'N000707100', '万邑邮选-普通渠道（平邮）-上海', 'WP-CNP006', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (48, 'N000707100', '万邑邮选-马来西亚渠道（挂号）', 'WP-MYP002', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (49, 'N000707100', '万邑邮选-马来西亚渠道（平邮）', 'WP-MYP001', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (50, 'N000707100', '万邑邮选-荷兰渠道（挂号）-不含电', 'WP-NLP011', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (51, 'N000707100', '万邑邮选-荷兰渠道（平邮）-含电', 'WP-NLP002', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (52, 'N000707100', '万邑邮选-荷兰渠道（平邮）-不含电', 'WP-NLP012', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (53, 'N000707100', '万邑邮选-新加坡渠道（挂号）', 'WP-SGP003', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (54, 'N000707100', '万邑邮选-新加坡渠道（平邮）', 'WP-SGP004', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (55, 'N000707100', '万邑通-快邑速递-DHL环球快递', 'ISP011189', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (56, 'N000707100', '万邑邮选-香港渠道（挂号）', 'WP-HKP002', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (57, 'N000707100', '万邑邮选-香港渠道（平邮）', 'WP-HKP001', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (58, 'N000707100', '万邑通-易递宝–香港渠道(平邮)', 'WP-HKP101', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (59, 'N000707100', '万邑通-易递宝-马来西亚渠道（平邮）', 'WP-MYP101', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (60, 'N000707100', 'USPS First Class Mail Tracked Service', NULL, 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (61, 'N000707100', '万邑通-优邑专线-英国达(小包裹)-含电', 'ISP041243', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '万邑通');
INSERT INTO `tb_ms_logistics_mode` VALUES (62, 'N000701100', '德邦E邮宝', 'SHA-EUB', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '德邦物流');
INSERT INTO `tb_ms_logistics_mode` VALUES (63, 'N000701100', '德邦E包裹', 'SHA-EBG', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '德邦物流');
INSERT INTO `tb_ms_logistics_mode` VALUES (64, 'N000701100', '德邦E特快', 'SHA-ETK', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '德邦物流');
INSERT INTO `tb_ms_logistics_mode` VALUES (65, 'N000706300', 'CNE全球特惠', 'CNE全球特惠', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, 'CNE合久成越');
INSERT INTO `tb_ms_logistics_mode` VALUES (66, 'N000706300', 'CNE全球通挂号', 'CNE全球通挂号', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, 'CNE合久成越');
INSERT INTO `tb_ms_logistics_mode` VALUES (67, 'N000706400', 'ebay-LK亚太物流', '0', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '中国邮政');
INSERT INTO `tb_ms_logistics_mode` VALUES (68, 'N000706500', 'IML-艾姆勒俄罗斯', NULL, 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '艾姆勒');
INSERT INTO `tb_ms_logistics_mode` VALUES (69, 'N000706600', '华侨国际', NULL, 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '华侨国际');
INSERT INTO `tb_ms_logistics_mode` VALUES (70, 'N000706700', '佳成国际', NULL, 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '佳成国际');
INSERT INTO `tb_ms_logistics_mode` VALUES (71, 'N000706800', '三态速递', NULL, 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '三态速递');
INSERT INTO `tb_ms_logistics_mode` VALUES (72, 'N000706900', '顺风俄罗斯小包挂号', '10', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '顺丰俄罗斯');
INSERT INTO `tb_ms_logistics_mode` VALUES (73, 'N000706900', '顺丰爱沙尼亚东欧海外仓-邮政经济渠道挂号', 'D4', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '顺丰俄罗斯');
INSERT INTO `tb_ms_logistics_mode` VALUES (74, 'N000707000', 'UBI-新西兰全程和半程', 'UBI.CN2NZ.NZPOST', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '利通UBI');
INSERT INTO `tb_ms_logistics_mode` VALUES (75, 'N000707000', 'UBI-欧盟小包半程查件服务', 'UBI.CN2EU.BPOST.EMC', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '利通UBI');
INSERT INTO `tb_ms_logistics_mode` VALUES (76, 'N000707000', 'UBI-俄罗斯服务', 'UBI.SPSR', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '利通UBI');
INSERT INTO `tb_ms_logistics_mode` VALUES (77, 'N000707000', 'UBI-墨西哥服务', 'UBI.CN2MX.SCM', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '利通UBI');
INSERT INTO `tb_ms_logistics_mode` VALUES (78, 'N000707000', 'UBI-加拿大全程服务', 'UBI.CN2CA.CPC', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '利通UBI');
INSERT INTO `tb_ms_logistics_mode` VALUES (79, 'N000707000', 'UBI-澳洲全程', 'UBI.ASP.CN2AU.AUPOST', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '利通UBI');
INSERT INTO `tb_ms_logistics_mode` VALUES (80, 'N000707000', 'UBI-欧盟小包全程查件服务', 'UBI.TRACK.MINIPACK.BPOST', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '利通UBI');
INSERT INTO `tb_ms_logistics_mode` VALUES (81, 'N000707000', 'UBI-新加坡、马来西亚', 'UBI.NJV', 'ERPAdmin', '2017-10-31 16:21:50', '2017-10-27 17:14:03', 1, 0, '利通UBI');
INSERT INTO `tb_ms_logistics_mode` VALUES (91, 'N000700300', 'w ', '3', 'Erpadmin', '2017-11-02 10:12:14', '2017-11-02 10:12:30', 1, 1, '');
INSERT INTO `tb_ms_logistics_mode` VALUES (92, 'N000700300', ' ', '3', 'Erpadmin', '2017-11-02 10:12:44', '2017-11-02 10:13:16', 1, 1, '');
INSERT INTO `tb_ms_logistics_mode` VALUES (93, 'N000700700', '1', '1', 'Erpadmin', '2017-11-02 10:19:19', '2017-11-02 10:20:24', 1, 1, '');
INSERT INTO `tb_ms_logistics_mode` VALUES (94, 'N000700700', '	菜鸟物流', '1', 'Erpadmin', '2017-11-02 10:20:33', '2017-11-02 10:20:33', 1, 0, '');
INSERT INTO `tb_ms_logistics_mode` VALUES (95, 'N000700400', '                        ', '                ', 'Erpadmin', '2017-11-02 10:48:58', '2017-11-02 10:49:08', 1, 1, '');
INSERT INTO `tb_ms_logistics_mode` VALUES (96, 'N000700100', '2222', '765756', 'Erpadmin', '2017-11-02 11:13:44', '2017-11-02 11:13:44', 1, 0, '');
INSERT INTO `tb_ms_logistics_mode` VALUES (98, 'N000700300', '           12', '', 'Erpadmin', '2017-11-02 14:06:35', '2017-11-02 14:06:35', 1, 0, '');
INSERT INTO `tb_ms_logistics_mode` VALUES (100, 'N000700200', '                  ', '', 'Erpadmin', '2017-11-02 14:07:38', '2017-11-02 14:07:38', 1, 0, '');
INSERT INTO `tb_ms_logistics_mode` VALUES (103, 'N000701300', '                                                                         ', '', 'Erpadmin', '2017-11-02 14:07:57', '2017-11-02 14:07:57', 1, 0, '');
INSERT INTO `tb_ms_logistics_mode` VALUES (104, 'N000700200', '12', '', 'Erpadmin', '2017-11-02 14:47:43', '2017-11-02 14:47:43', 1, 0, '');
INSERT INTO `tb_ms_logistics_mode` VALUES (105, 'N000700300', '我不知道', '', 'Erpadmin', '2017-11-02 15:32:10', '2017-11-02 15:32:10', 1, 0, '');
INSERT INTO `tb_ms_logistics_mode` VALUES (106, 'N000703600', '.', '', 'Erpadmin', '2017-11-02 15:32:57', '2017-11-02 15:33:25', 1, 1, '');
INSERT INTO `tb_ms_logistics_mode` VALUES (107, 'N000707000', '我三三', '', 'Erpadmin', '2017-11-02 15:49:04', '2017-11-02 15:49:20', 1, 1, '');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;

-- 修改物流公司绑定关系表，添加逻辑删除状态
ALTER TABLE `tb_ms_logistics_relation` ADD COLUMN IS_DELETE TINYINT(2) UNSIGNED DEFAULT '0' COMMENT '是否删除,逻辑删除';
-- 码表新增物流公司
INSERT INTO tb_ms_cmn_cd (CD,CD_NM,CD_VAL,USE_YN,SORT_NO,ETC,ETC2) VALUES ('N000707200','LOGISTICS_COMPANY','万色WISH邮','Y',0,'','');