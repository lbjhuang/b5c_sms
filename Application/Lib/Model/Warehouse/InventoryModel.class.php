<?php
class InventoryModel extends BaseModel
{
	const INVE_FIN_CONFIRMING = 'N003520003'; // 财务确认中主状态
	const INVE_IN_AUDIT = 'N003520002'; // 审核中 
	const INVE_IN_CHECK = 'N003520001'; // 盘点中 
	const INVE_IN_ADJUST = 'N003520004'; // 调整差异中 
	const INVE_DONE = 'N003520005'; // 已完成 
	const INVE_IN_CHECK_FIRST = 'N003520006'; // 初盘中
	const INVE_IN_CHECK_SEC = 'N003520007'; // 复盘中 
	const INVE_FIN_CONFIRM_NONEED = 'N003520009'; // 财务无需确认 
	const INVE_FIN_CONFIRM_IN = 'N003520008'; // 财务确认中次状态 
	const INVE_IN_AUDIT_SEC = 'N003520011'; // 审核复核中
	const INVE_IN_AUDIT_FIRST = 'N003520010'; // 初次审核中 

	const INVE_OUTGOING = 'N000950400'; // 收发类别，盘亏出库
	const INVE_STORAGE = 'N000940400'; // 收发类别，盘盈入库

	const INVE_STOCK_TYPE = 'N002440100'; // 现货库存
	const INVE_STOCK_TYPE_BROKEN = 'N002440400'; // 残次品
	
	const INVE_MONEY_CURRENCY = 'N000590100'; // 币种，默认USD

	const INVE_IN_OUT_STORAGE_TYPE = 'N002350706'; // 出入库关联单据类型

	const GOODS_TYPE_ALL = 'N003730001'; // 全部
	const GOODS_TYPE_NORMAL = 'N003730002'; // 正品
	const GOODS_TYPE_BROKEN = 'N003730003'; // 残次品


	public static $status = [ // 调整状态
		'0' => '待调整',
		'1' => '已调整',
	];

	public static $type = [ // 盘点类型
		'1' => '盘盈',
		'2' => '盘亏'
	];

	public static $goodsType = [ // 商品类型
		'N003730002' => '1',
		'N003730003' => '2'
	];
	protected $trueTableName = 'tb_wms_inventory';
	protected $_auto = [
	    ['created_at', 'getTime', Model::MODEL_INSERT, 'callback'],
	    ['created_by', 'getLoginName', Model::MODEL_INSERT, 'callback'],
	    ['updated_at', 'getTime', Model::MODEL_UPDATE, 'callback'],
	    ['updated_by', 'getLoginName', Model::MODEL_UPDATE, 'callback'],
	];

	public function createInvNo()
	{
		// 先获取时间是今天的最新的盘点单号的后四位数，加1
		// 判断下是否已达到9999最大限制
        $date = date("Y-m-d");
        $where['created_at'] = array('gt', $date);
        $max_id = self::where($where)->count();
        if ($max_id == 9999) {
        	throw new Exception(L('无法创建盘点单，今日盘点数量已达上限~'));
        }
        $type = 'PD';
        $date = date("Ymd", strtotime($date));
        $wrate_id = $max_id + 1;
        $w_len = strlen($wrate_id);
        $b_id = '';
        if ($w_len < 4) {
            for ($i = 0; $i < 4 - $w_len; $i++) {
                $b_id .= '0';
            }
        }
        return  $type. $date . $b_id . $wrate_id;
	}

	public static function getStatusList()
	{
		if (S('inventoryStatus')) {
			return S('inventoryStatus');
		}
		$cmnModel = M('ms_cmn_cd', 'tb_');
		$where['CD'] = ['LIKE', 'N00352%'];
		$res = $cmnModel->field('CD, CD_VAL, ETC, ETC2')->where($where)->select();
		$data = [];

		foreach ($res as $key => $value) {
			if (empty($value['ETC'])) {
				$childData = [];
				$childData['value'] = $value['CD'];
				$childData['label'] = $value['CD_VAL'];
				if (!empty($value['ETC2'])) {
					$childData['childrenName'] = $value['ETC2'];
				}
				$data[$value['CD']] = $childData;
			}
		}

		foreach ($res as $key => $value) {
			if (!empty($value['ETC'])) {
				$childrenData = [];
				$childrenData['value'] = $value['CD'];
				$childrenData['label'] = $value['CD_VAL'];
				$data[$value['ETC']]['children'][] = $childrenData;
			}
		}
		// p($data);die;
		S('inventoryStatus', $data, DataMain::$cachetime);
		return $data;
	}

	// 由于调用现存量获取SKU的相关信息时，多次调用发现筛选条件会自动叠加，导致获取结果失败，所以根据查询sql改造后直接获取
	public static function getRealNumberStockInfo($sku_id, $warehouse_cd, $otherParam = [])
	{
		if ($sku_id) {
			$sku_id_str = $sku_id;
			if (strstr($sku_id, ',')) {
				$sku_id_arr = explode(',',$sku_id);
				$sku_id_str = implode("','", $sku_id_arr);
			}
			$skuSql = "AND ( t1.SKU_ID LIKE '{$sku_id}%' OR t1.SKU_ID in ('{$sku_id_str}')) ";
		}
		if ($otherParam['sale_team_cd']) {
			$saleTeamSql = "AND t1.sale_team_code in ('{$otherParam['sale_team_cd']}') ";
		}
		if ($otherParam['goods_type_cd'] === InventoryModel::GOODS_TYPE_NORMAL) {
			$goodsTypeSql = "AND t1.vir_type != 'N002440400'";
		}
		if ($otherParam['goods_type_cd'] === InventoryModel::GOODS_TYPE_BROKEN) {
			$goodsTypeSql = "AND t1.vir_type = 'N002440400'";
		}
		$sql = "SELECT
	t1.SKU_ID AS skuId,
	t1.vir_type,
	SUM( t1.total_inventory ) AS amountTotalNum,
	SUM( t1.available_for_sale_num ) AS amountSaleNum,
	SUM( t1.occupied + IFNULL( t12.childOccupied, 0 ) ) AS amountOccupiedNum,
	SUM( IFNULL( t12.childLocking, 0 ) ) AS amountLockingNum
FROM
	tb_wms_batch t1
	LEFT JOIN tb_wms_bill t11 ON t1.bill_id = t11.id
	LEFT JOIN (
	SELECT
		tab2.batch_id,
		sum( tab2.occupied ) AS childOccupied,
		sum( tab2.available_for_sale_num ) AS childLocking,
		tab2.SKU_ID 
	FROM
		tb_wms_batch_child tab2 
	GROUP BY
		tab2.batch_id,
		tab2.SKU_ID 
	) t12 ON t1.id = t12.batch_id 
	AND t1.SKU_ID = t12.SKU_ID 
WHERE
	(
		t11.type = 1 
		AND t1.vir_type != 'N002440200' 
		AND t1.vir_type != 'N002410200' 
		{$skuSql}
		{$saleTeamSql}
		{$goodsTypeSql}
		AND t1.total_inventory > 0 
	) 
	AND ( t11.warehouse_id IN ( '{$warehouse_cd}' ) ) 
GROUP BY
t1.SKU_ID, t1.vir_type";
		return M()->query($sql);
	}
}