<?php

/**
 * User: yangsu
 * Date: Tue, 21 Aug 2018 03:18:06 +0000.
 */

@import("@.Model.Orm.TbSystemSort");
@import("@.Model.Orm.TbSystemCoverValidate");
@import("@.Model.Orm.TbOpOrder");
import('ORG.Util.Date');// 导入日期类

/**
 * Class TbSystemSort
 *
 * @property int $id
 * @property string $sort_value
 * @package App\Models
 */
class SystemSortModel extends Model
{
    /**
     * @var null
     */
    public $order_id = null;
    /**
     * @var null
     */
    public $plat_cd = null;
    /**
     * @var array
     */
    public $has_default_warehouse = [
        'operation_designation' => 2
    ];
    /**
     * @var string
     */
    private $sort_value = 'erp_user_edit';
    /**
     * @var string
     */
    private $influence_table_name = 'tb_op_order';
    /**
     * @var array
     */
    private $validate_json = [];
    /**
     * @var array
     */
    public $update_datas = [];

    /**
     * SystemSortModel constructor.
     */
    public function __construct()
    {

    }

    /**
     * @param       $ORDER_ID
     * @param       $PLAT_CD
     * @param array $update_datas
     *
     * @return TbSystemCoverValidate
     */
    public static function setSystemSort($ORDER_ID, $PLAT_CD, $update_datas = [])
    {
        $systemSort = new SystemSortModel();
        $systemSort->order_id = $ORDER_ID;
        $systemSort->plat_cd = $PLAT_CD;
        $systemSort->update_datas = $update_datas;
        return $systemSort->updateSystemSort();
    }

    /**
     *
     */
    public function updateSystemSort($sorts_id = null)
    {
        if (empty($this->update_datas)) {
            $this->editOrderHasWarehouseState();
        }
        if (empty($sorts_id)) {
            $tb_system = TbSystemSort::where('sort_value', $this->sort_value)
                ->firstOrFail();
            $sorts_id = $tb_system['id'];
        }
        $where['sorts_id'] = $save['sorts_id'] = $sorts_id;
        $where['order_id'] = $save['order_id'] = $this->order_id;
        $where['plat_cd'] = $save['plat_cd'] = $this->plat_cd;
        $old_validate = TbSystemCoverValidate::where($where)
            ->first();
        $this->getValidateJson($old_validate->validate_json);
        $save['validate_json'] = $this->validate_json;
        $save['influence_table_name'] = $this->influence_table_name;
        return TbSystemCoverValidate::updateOrCreate($where, $save);
    }

    /**
     * @return mixed
     */
    private function editOrderHasWarehouseState()
    {
        /*$where['order_id'] = $this->order_id;
        $where['plat_cd'] = $this->plat_cd;
        $save['has_default_warehouse'] = $this->has_default_warehouse['operation_designation'];*/
        $res = TbOpOrder::where('ORDER_ID', $this->order_id)
            ->where('PLAT_CD', $this->plat_cd)
            ->update(['has_default_warehouse' => $this->has_default_warehouse['operation_designation']]);
        Logs($res, '$res', 'editOrderHasWarehouseState');
    }

    /**
     * @param $tb_system
     * @param $update_datas
     */
    private function getValidateJson(array $old_validate_arr = [])
    {
        if (empty($old_validate_arr)) {
            $old_validate_arr = [];
        }
        if (empty($this->update_datas)) {
            $this->update_datas = [
                "has_default_warehouse" => (object)null,
                "warehouse" => (object)null,
                "WAREHOUSE" => (object)null,
                "logistic_cd" => (object)null,
                "logistic_model_id" => (object)null
            ];
        }
        $temp_validate_json = $this->packArrayToValidate($old_validate_arr);
        $this->validate_json = $temp_validate_json;
        return $temp_validate_json;
    }

    /**
     * @param array $old_validate_arr
     *
     * @return array
     */
    private function packArrayToValidate(array $old_validate_arr = [])
    {

        foreach ($this->update_datas as $key => $value) {
            $lower_key = strtolower($key);
            $temp_validate_arr[$lower_key]['to'] = $value;
            $temp_validate_arr[$lower_key]['from'] = $old_validate_arr[$lower_key]['to'];
        }
        $temp_validate_json = array_merge($old_validate_arr, $temp_validate_arr);
        return $temp_validate_json;
    }

    /**
     * 批量更新 限制所
     * @param array $data 多为数组必须包含如下字段 thr_order_id, plat_cd
     * @author Redbo He
     * @date 2020/12/7 16:53
     */
    public function batchUpdateSystemSort(array  $data)
    {
        //$tb_system = TbSystemSort::where('sort_value', $this->sort_value)->firstOrFail();
        $tb_system_model = M('system_sorts','tb_');
        $tb_system = $tb_system_model->where(['sort_value' => ['eq', $this->sort_value]])->find();
        $GLOBALS['end_time_8']  =  microtime(true);
        $sorts_id = $tb_system['id'];
        $where['sorts_id'] = $save['sorts_id'] = $sorts_id;
        $where['order_id'] = $save['order_id'] = $this->order_id;
        $where['plat_cd']  = $save['plat_cd'] = $this->plat_cd;
        $thr_order_ids = array_column($data,'thr_order_id');

//        $old_validates = TbSystemCoverValidate::whereIn('order_id',$thr_order_ids )
//                            ->where('sorts_id',$sorts_id)
//                            ->get()->toArray();
        $tb_system_cover_validate_model =  M('system_cover_validates','tb_');
        $old_validates = $tb_system_cover_validate_model->where([
                'order_id' => ['in', $thr_order_ids],
                'sorts_id' => ['eq', $sorts_id]
        ])->select();
        # dd($old_validates);
        $order_plat_cd_map = $data_map =  [];
        foreach ($data as $val) {
            $index = $val['thr_order_id'] .'_'. $val['plat_cd'];
            $data_map[$index] = $val;
            $order_plat_cd_map[$index] =  $val['plat_cd'];
        }
        # 处理 key
        $result = [];
         #  提取出有效数据
        foreach ($old_validates as $old_validate) {
            $plat_cd = $old_validate['plat_cd'];
            $index = $old_validate['order_id']. '_'.$old_validate['plat_cd'];
            if(isset($order_plat_cd_map[$index]) && $order_plat_cd_map[$index] == $plat_cd)
            {
                $result[$index] = $old_validate;
            }
        }
        # 组装批量更新数据 与批量插入数据
        $date = new Date();
        $update_data =  $insert_data = [];
        $insert_data = $data_map;
        foreach ($result as $item)
        {
            $update_data[] = [
                'id' => $item['id'],
                'influence_table_name' =>  $this->influence_table_name,
                'updated_at' =>  $date->format(),
            ];
            $index = $item['order_id']. '_'.$item['plat_cd'];
            # 判断是否需要新插入
            unset($insert_data[$index]);
        }

        $model = M();
        try 
        {
            $model->startTrans();
            if($update_data)
            {
                $update_sql = BatchUpdate::getBatchUpdateSql('tb_system_cover_validates', $update_data, 'id');
                $res = $model->execute($update_sql);
                if(!$res)
                {
                    throw new ThinkException('映射关系表数据更新失败');
                }
            }

            if($insert_data)
            {
                 $data = [];
                 foreach ($insert_data as $insert) {
                     $data[] = [
                         'sorts_id' => $sorts_id,
                         'order_id' => $insert['thr_order_id'],
                         'plat_cd'  => $insert['plat_cd'],
                         'validate_json' => json_encode($this->getValidateJson()),
                         'influence_table_name' =>  $this->influence_table_name,
                         'created_at' =>  $date->format(),
                         'updated_at' =>  $date->format(),
                     ];
                 }
                 if($data)
                 {
                     $res = $tb_system_cover_validate_model->addAll($data);
                     if(!$res) {
                         throw new ThinkException('映射关系表数据插入失败');
                     }
                 }
            }
        } 
        catch (\Exception $e) 
        {
            $model->rollback();
            Log::record("【batchUpdateSystemSort 更新失败】".$e->__toString(), Log::ERR);
            return false;
        }
        $model->commit();
        return true;
    }

}
