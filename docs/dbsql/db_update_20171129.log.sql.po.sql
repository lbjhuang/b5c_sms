ALTER TABLE `tb_b2b_info` ADD `sale_tax` DOUBLE NOT NULL DEFAULT '0' COMMENT '销售端应缴税',
ADD `cur_tuishui` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '币种退税金额',
ADD `cur_saletax` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '币种销售端应缴税',
ADD `cur_other` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '币种其他收入';

-- 20171201
ALTER TABLE `tb_b2b_goods` ADD `jdesc` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '标记数据';
ALTER TABLE `tb_b2b_goods` CHANGE `jdesc` `jdesc` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '标记数据';

-- 20171205
ALTER TABLE `tb_b2b_info` CHANGE `PAYMENT_NODE` `PAYMENT_NODE` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 't付款节点' ;



