-- 取消现在主键，新加主键ID字段，和唯一索引：ID,GUDS_ID,LANGUAGE，三个属性都是唯一固定值，限制重复性。
-- 这样修改之后，图片的更新、品牌的更新，甚至 MainGudsId都可以进行修改，提高扩展和修改性。
ALTER TABLE tb_ms_guds_img
  DROP PRIMARY KEY,
  DROP INDEX INDEX_GUDS_ID,
  ADD COLUMN `ID` bigint	NOT NULL AUTO_INCREMENT COMMENT '商品图片id,主键ID',
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE INDEX u_idx (`GUDS_ID`,`GUDS_IMG_CD`,`LANGUAGE`),
  ADD INDEX idx (`MAIN_GUDS_ID`);


-- 修改主键为GUDS_ID
ALTER TABLE `tb_ms_guds`
  DROP PRIMARY KEY,
  DROP INDEX ix_ms_guds_01
ADD PRIMARY KEY (`GUDS_ID`),
CHANGE `GUDS_ID` `GUDS_ID` varchar(20) NOT NULL COMMENT '商品ID' FIRST,
ADD COLUMN `BRND_ID` int(10) unsigned NOT NULL COMMENT '品牌自增ID，不参与业务处理，只为了生成SKU自编码创建' AFTER `GUDS_ID`,
ADD COLUMN `GUDS_CAT` varchar(20) DEFAULT NULL COMMENT '前端类目，通用的后台商品类目，替换SLLR_CAT用' AFTER `CAT_CD`;

-- 同步品牌ID，数字格式
UPDATE `tb_ms_guds` AS G JOIN `tb_ms_brnd_str` AS B SET G.`BRND_ID` = B.BRND_ID WHERE G.`SLLR_ID`=B.`SLLR_ID`;
UPDATE `tb_ms_guds` AS G JOIN `tb_ms_sllr_cat` AS SC JOIN `tb_ms_cmn_cat` AS CC SET G.GUDS_CAT=CC.CAT_CD WHERE G.CAT_CD=SC.CAT_CD AND SC.COMM_CAT_CD = CC.CAT_CD;

-- SKU数据表更新主键，去掉SLLR_ID，`GUDS_ID`,`GUDS_OPT_ID`做主键即可保证唯一性;
ALTER TABLE `tb_ms_guds_opt`
  DROP PRIMARY KEY,
  ADD PRIMARY KEY(`GUDS_ID`,`GUDS_OPT_ID`),
  CHANGE `SLLR_ID` `SLLR_ID` varchar(50) NOT NULL COMMENT '판매자이이디 | 销售者ID，实际是商家ID' AFTER `GUDS_OPT_ID`;

-- 商品详情描述信息，代码中大部分搜索查询用的条件是 WHERE ( `MAIN_GUDS_ID` = '80008131' ) AND ( `SLLR_ID` = 'a2platinum' )
ALTER TABLE `tb_ms_guds_describe`
  DROP PRIMARY KEY,DROP INDEX idx,
  ADD COLUMN `ID` bigint	NOT NULL AUTO_INCREMENT COMMENT '商品描述ID，做主键' FIRST,
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE INDEX (`GUDS_ID`, `LANGUAGE`),
  ADD INDEX idx (`MAIN_GUDS_ID`),
  CHANGE `GUDS_ID` `GUDS_ID` VARCHAR ( 20 ) NOT NULL COMMENT '商品id' AFTER `ID`;

-- 商品详情主键调整，移除掉SLLR_ID主键；
-- SLLR_ID字段后续可以删除，没必要在这里加上。
-- 新增加ID字段为主键，更新原主键为唯一索引
ALTER TABLE `tb_ms_guds_dtl`
  DROP PRIMARY KEY,
  ADD COLUMN `ID` bigint	NOT NULL AUTO_INCREMENT COMMENT '商品描述ID，做主键' FIRST,
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE INDEX(`GUDS_ID`,`LANGUAGE`), -- 原GUDS_ID索引可以去掉。
  DROP INDEX INDEX_DTL_GUDS_ID,
  ADD INDEX  (`MAIN_GUDS_ID`); -- 添加新索引(`MAIN_GUDS_ID`) 和SLLR_ID因为线上日志大部分查询是已这两个字段为条件的。
