<?php

/**
 * User: shenmo
 * Date: 19/07/25
 * Time: 15:46
 **/

/**
 * Class TbTrademarkModel
 *
 * @property int $id
 * @property string $trademark_name
 * @property string $trademark_code
 * @property string $img_url
 * @property \Carbon\Carbon $created_at
 * @property string $updated_by
 * @property \Carbon\Carbon $updated_at
 *
 * @package App\Models
 */
class TbTrademarkModel extends BaseModel
{


	public function __construct($name = '')
	{
		parent::__construct($name);
	}

	public $params;
	protected $trueTableName = 'tb_trademark_base';
	protected $_auto = [
		['create_at', 'getTime', Model::MODEL_INSERT, 'callback'],
		['create_by', 'getName', Model::MODEL_INSERT, 'callback'],
		['update_at', 'getTime', Model::MODEL_UPDATE, 'callback'],
		['update_by', 'getName', Model::MODEL_UPDATE, 'callback'],
	];

	const IS_DELETE_STATE_NO = 0; //正常
	const IS_DELETE_STATE_YES = 1; //删除

	/**
	 * ODM商标列表页查询筛选
	 *
	 * @param $params
	 *
	 * @return array 返回满足框架要求的筛选条件组合
	 */
	public static function search($params)
	{
		if ($params ['country_code']) {
			$conditions ['td.country_code'] = ['in', $params ['country_code']];
		}
		if ($params ['company_code']) {
			$conditions ['td.company_code'] = ['in', $params ['company_code']];
		}
		if ($params ['current_state']) {
			$conditions ['td.current_state'] = ['eq', $params ['current_state']];
		}
		if ($params ['trademark_name']) {
			$conditions ['trademark_name'] = ['like', '%' . $params['trademark_name'] . '%'];
		}

		return $conditions;
	}

	/**
	 * ODM-新增商标基础信息
	 *
	 * @param $params
	 *
	 * @return array
	 */
	public function createTrademark($params)
	{
		return $this->add($params);
	}

	/**
	 * ODM-编辑商标基础信息
	 *
	 * @param $params
	 *
	 * @return array
	 */
	public function updateTrademark($params)
	{
		return $this->save($params);
	}
}
