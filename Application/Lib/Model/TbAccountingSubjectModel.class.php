<?php

/**
 * User: shenmo
 * Date: 19/06/17
 * Time: 10:46
 **/
@import("@.Model.ORM");
use Application\Lib\Model\ORM;

/**
 * Class TbAccountingSubject
 *
 * @property int $id
 * @property string $subject_name
 * @property string $subject_code
 * @property string $p_subject_code
 * @property string $sort_level
 * @property integer $level
 * @property integer $p_level
 * @property integer $subject_type_cd
 * @property integer $count
 * @property string $use_source
 * @property string $is_delete_state
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class TbAccountingSubjectModel extends ORM
{

	protected $table = 'tb_accounting_subject';
	const IS_DELETE_STATE_NO = 0; //正常
	const IS_DELETE_STATE_YES = 1; //删除

	//会计科目级次映射
	public static $levelMap = [
		'N002900001' => 1,
		'N002900002' => 2,
		'N002900003' => 3,
		'N002900004' => 4,
		'N002900005' => 5,
		'N002900006' => 6,
	];

	//会计科目级次code长度映射
	public static $levelLengthMap = [
		'N002900001' => 4,
		'N002900002' => 6,
		'N002900003' => 8,
		'N002900004' => 10,
		'N002900005' => 12,
		'N002900006' => 14,
	];

	//会计科目类型映射
	public static $subjectTypeMap = [
		'N002890001' => '资产',
		'N002890002' => '负债',
		'N002890003' => '共同',
		'N002890004' => '权益',
		'N002890005' => '成本',
		'N002890006' => '损益',
	];

	protected $fillable = [
		'subject_name',
		'subject_code',
		'p_subject_code',
		'level',
		'p_level',
		'subject_type_cd',
		'subject_type_name',
		'use_count',
		'use_source',
		'is_delete_state',
		'created_by',
		'updated_by',
	];

	public static function getList($where, $limit, $is_excel = false)
	{
		$model = M('accounting_subject', 'tb_');
		$where['is_delete_state'] =  self::IS_DELETE_STATE_NO;
		$query = $model->field('id, subject_name, subject_code, p_subject_code, subject_type_cd, level, p_level, use_count, is_delete_state, created_by')->where($where);
		$query_copy = clone $query;
		$pages['total'] = $query->count();
		$pages['current_page'] = $limit[0];
		$pages['per_page'] = $limit[1];
		if (false === $is_excel) {
			$query_copy->limit($limit[0], $limit[1]);
		}
		$db_res = $query_copy->order('subject_code asc')->select();
		return [$db_res, $pages];
	}

	public static function getDetail($where)
	{
		if (empty($where)) return [];
		$model = M('accounting_subject', 'tb_');
		$where['is_delete_state'] =  self::IS_DELETE_STATE_NO;
		$db_res = $model->field('id, subject_name, subject_code, p_subject_code, subject_type_cd, level, p_level, use_count')->where($where)->order('subject_code desc')->find();
		return $db_res;
	}

	//条件筛选
	public static function getwhere($data)
	{
		if (!empty($data['id']))
			$where['id'] = $data['id'];
		if (!empty($data['subject_name']))
			$where['subject_name'] = array("like","%{$data['subject_name']}%");
		if (!empty($data['subject_code']))
			$where['subject_code'] = array("like","%{$data['subject_code']}%");
		if (!empty($data['p_subject_code']))
			$where['p_subject_code'] = $data['p_subject_code'];
		if (!empty($data['level']))
			$where['level'] = self::$levelMap[$data['level']];
		if (!empty($data['p_level']))
			$where['p_level'] = self::$levelMap[$data['p_level']];
		if (!empty($data['subject_type_cd']))
			$where['subject_type_cd'] = $data['subject_type_cd'];
		if (!empty($data['is_delete_state']))
			$where['is_delete_state'] = $data['is_delete_state'];
		if (!empty($data['created_by']))
			$where['created_by'] = $data['created_by'];

		$where = is_array($where)?$where:array();
		return $where;

	}

	public static function updateAccountingSubject($data)
	{
		$where_id['id'] = $data['id'];
		if (!empty($data['level'])) {
			$data['level'] = self::$levelMap[$data['level']];
			if (!empty($data['p_level'])) {
				$data['p_level'] = self::$levelMap[$data['p_level']];
			} else {
				//父级次 = 级次 - 1
				$data['p_level'] = $data['level'] - 1;
			}
		}
		return TbAccountingSubjectModel::updateOrCreate($where_id, $data);
	}
}
