--  20171121
ALTER TABLE `tb_hr_empl_dept` ADD `TYPE_LEVEL` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT '负责等级(1级2级)';

ALTER TABLE `tb_hr_empl_dept` ADD UNIQUE `uni_index` ( `ID1` , `ID2` , `TYPE` , `TYPE_LEVEL` ) ;

ALTER TABLE tb_hr_empl_dept DROP INDEX index;


