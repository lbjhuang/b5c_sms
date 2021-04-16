<?php 
/**
* user:huanzhu
* date:2017/9/28
* info:recruit log
*/
class TbHrJobsModel extends BaseModel
{
	
	protected $trueTableName = 'tb_hr_jobs';
	protected $_auto = [
        ['CREATE_TIME', 'getTime', Model:: MODEL_INSERT, 'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback']
    ];
     protected $_validate = [
        ['CD_VAL','require','请输入职位职位名称'],//默认情况下用正则进行验证
        //['ETC','require','请输入职位英文名称'],//默认情况下用正则进行验证
    ];
}


 ?>