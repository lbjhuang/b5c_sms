--  20170908
ALTER TABLE `tb_hr_empl_dept` CHANGE `ID1` `ID1` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID1-Department ID';
ALTER TABLE `tb_hr_empl_dept` CHANGE `ID2` `ID2` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID2-Employee ID';
ALTER TABLE `tb_hr_empl_dept` CHANGE `TYPE` `TYPE` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT '类型(0默认1负责人2其他)';
ALTER TABLE `tb_hr_empl_dept` CHANGE `ID1` `ID1` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID1-Employee ID';
ALTER TABLE `tb_hr_empl_dept` CHANGE `ID2` `ID2` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID2-Department ID';


--  20170919
ALTER TABLE `tb_hr_empl_dept` CHANGE `ID1` `ID1` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID1-Department ID';
ALTER TABLE `tb_hr_empl_dept` CHANGE `ID2` `ID2` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID2-Employee ID';
ALTER TABLE `tb_hr_empl_dept` CHANGE `TYPE` `TYPE` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' COMMENT '类型(0负责人1默认2员工上级关联)';


--  20170926 change that dept can repeat name
ALTER TABLE `tb_hr_dept` DROP INDEX `DEPT_NM` ,
ADD INDEX `DEPT_NM` ( `DEPT_NM` ) ;


