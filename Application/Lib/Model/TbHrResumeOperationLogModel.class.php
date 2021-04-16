<?php 
/**
* user:huanzhu
* date:2017/9/28
* info:recruit log
*/
class TbHrResumeOperationLogModel extends BaseModel
{
	
	protected $trueTableName = 'tb_hr_resume_operation_log';
	protected $_auto = [
        ['CREATE_TIME', 'getTime', Model:: MODEL_INSERT, 'callback'],
        ['UPDATE_TIME', 'getTime', Model::MODEL_BOTH, 'callback'],
        ['CREATE_USER_ID', 'getName', Model::MODEL_INSERT, 'callback'],
        ['UPDATE_USER_ID', 'getName', Model::MODEL_BOTH, 'callback']
    ];
}


 ?>