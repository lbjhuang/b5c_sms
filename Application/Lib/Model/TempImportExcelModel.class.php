<?php
/**
 * 临时数据修改，获取更新的SQL
 * User: b5m
 * Date: 2018/9/20
 * Time: 13:44
 */

class TempImportExcelModel extends BaseImportExcelModel
{

    protected $autoCheckFields  =   false;

    /**
     * 匹配验证模式
     */
    public function fieldMapping()
    {
        return [
            'skuId' => ['field_name' => L('SKU编码'), 'required' => false],
            'batchCode' => ['field_name' => L('批次号'), 'required' => false],
            'purCompany' => ['field_name' => L('采购公司'), 'required' => false],
            'purTeam' => ['field_name' => L('采购团队'), 'required' => false],
        ];
    }

    /**
     * 批更新
     * @param array $datas 需要更新的数据集合
     * @param object $model 模型
     * @param string $pk    主键
     * @return string $sql
     */
    public function saveAll($datas, $model, $pk = '')
    {
        $sql = '';
        $lists = [];
        isset($pk) or $pk = $model->getPk();
        foreach ($datas as $data) {
            foreach ($data as $key => $value) {
                if ($pk == $key) {
                    if (is_numeric($value))
                        $ids [] = '"' . $value . '"';
                    else
                        $ids [] = '"' . $value . '"';
                } else {
                    $lists [$key] .= sprintf("WHEN '%s' THEN '%s'", $data [$pk], $value);
                }
            }
        }

        foreach ($lists as $key => $value) {
            $sql .= sprintf("`%s` = CASE `%s` %s END,", $key, $pk, $value);
        }

        $sql = sprintf("UPDATE `%s` SET %s WHERE `%s` IN ( %s )", $model->getTableName(), rtrim($sql,','), $pk, implode(',', $ids));
        return $sql;
    }

    /**
     * 多条件判断-批量更新数据
     * @param $datas 要更新的数据集合
     * @param $model
     * @param string $pk 主键（也可以不是主键，主要用于筛选范围，提高更新性能）
     * @param array $where_field 更新条件，可以多个。注意：键名必须等于要更新的数据集合中的某个字段名
     * @return bool
     */
    public static function saveAllExtend($datas, $model, $pk = '', $where_field)
    {
        $where_field = (array) $where_field;
        if (empty($where_field)) {
            return false;
        }
        $sql = '';
        $lists = [];
        isset($pk) or $pk = $model->getPk();
        foreach ($datas as $data) {
            foreach ($data as $key => $value) {
                if ($pk == $key) {
                    if (is_numeric($value))
                        $ids [] = '"' . $value . '"';
                    else
                        $ids [] = '"' . $value . '"';
                } else if (in_array($key, $where_field)) {
                    continue;
                }else {
                    $sql_str = " WHEN ";
                    foreach ($where_field as $field) {
                        $sql_str .= "$field = '{$data[$field]}' AND ";
                    }
                    $sql_str = trim($sql_str, 'AND ');
                    $sql_str .= " THEN '{$value}'";
                    $lists [$key] .= $sql_str;
                }
            }
        }
        foreach ($lists as $key => $value) {
            $sql .= sprintf("`%s` = CASE %s END,", $key, $value);
        }
        $sql = sprintf("UPDATE `%s` SET %s WHERE `%s` IN ( %s )", $model->getTableName(), rtrim($sql,','), $pk, implode(',', $ids));
        return $sql;
    }

    /**
     * 数据组装
     */
    public function packData()
    {
        $temp = $batch = [];
        $company = array_flip(BaseModel::ourCompany());
        $purTeam = array_flip(BaseModel::spTeamCd());
        $s = [];
        foreach ($company as $key => $value) {
            $s [strtoupper($key)] = $value;
        }
        $company = $s;
        foreach ($this->data as $key => $value)
        {
            $batch = [];
            $batch ['SKU_ID'] = $value ['A']['value'];
            $batch ['batch_code'] = $value ['B']['value'];
            //$batch ['CON_COMPANY_CD'] =  $company [$value ['C']['value']]?$company [$value ['C']['value']]:$value ['C']['value'];
            $batch ['CON_COMPANY_CD'] = $value ['C']['value'];
            //$batch ['SP_TEAM_CD'] = $purTeam [$value ['D']['value']];
            $batch ['SP_TEAM_CD'] = $value ['D']['value'];
            //$batch ['id'] = null;

            $temp [$key] = $batch;
        }

//        foreach ($this->data as $key => $value) {
//            $tpl = sprintf("UPDATE tb_wms_bill SET CON_COMPANY_CD = (SELECT CD FROM tb_ms_cmn_cd WHERE CD_VAL = '%s' AND CD_NM = '我方公司' LIMIT 1), SP_TEAM_CD = (SELECT CD FROM tb_ms_cmn_cd WHERE CD_VAL = '%s' AND CD_NM = '采购团队' LIMIT 1) WHERE id = (SELECT bill_id FROM tb_wms_batch WHERE SKU_ID = '%s' AND batch_code = '%s' LIMIT 1);", $value ['C']['value'], $value ['D']['value'], $value ['A']['value'], $value ['B']['value']);
//            file_put_contents(__DIR__ . '/a.sql', $tpl . "\r\n", FILE_APPEND);
//        }

        $model = new Model();
        $tmpBill = [];
        foreach ($temp as $key => $value) {
            $ret = $model->table('tb_wms_batch')
                ->where(['batch_code' => ['eq', $value ['batch_code']], 'SKU_ID' => ['eq', $value ['SKU_ID']]])
                ->getField('SKU_ID as skuId, batch_code as batchCode, bill_id as billId');

            if ($ret [$value ['SKU_ID']]['billId']) {
                //$value ['id'] = $ret [$value ['SKU_ID']]['billId'];
                $tmpBill [$ret [$value ['SKU_ID']]['billId']][$key] = $value;
            }

            $ret = null;
        }

        $exception = [];

        foreach ($tmpBill as $key => $value) {
            $tag = true;
            $tmpTag = [];

            $tmpTag = array_column($value, 'CON_COMPANY_CD');

            if (count(array_flip($tmpTag)) > 1) {
                $tag = false;
            }

            if ($tag == false) {
                foreach ($value as $index => $r) {
                    $exception [$key][] = $index;
                }
            }
        }
        file_put_contents('/opt/logs/logstash/exception.txt', print_r($exception, true) . "\r\n", FILE_APPEND);
        file_put_contents('/opt/logs/logstash/context.txt', print_r($exception, true) . "\r\n", FILE_APPEND);
        echo '<pre/>';print_r($exception);print_r($tmpBill);exit;

        $temp = array_map(function($index, $r) use ($model) {
            echo '<pre/>';var_dump($index, $r);exit;
            $ret = $model->table('tb_wms_batch')
                ->where(['batch_code' => ['eq', $r ['batch_code']], 'SKU_ID' => ['eq', $r ['SKU_ID']]])
                ->getField('SKU_ID as skuId, batch_code as batchCode, bill_id as billId');

            if ($ret [$r ['SKU_ID']]['billId']) {
                $r ['id'] = $ret [$r ['SKU_ID']]['billId'];
                unset($r ['SKU_ID']);
                unset($r ['batch_code']);
                return $r;
            }
        }, $temp);

        $temp = array_filter($temp, function($r) {
            if (is_null($r))
                return false;
            else
                return true;
        });

        $this->data = $temp;
    }

    /**
     * 导入主入口函数
     *
     */
    public function import()
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 1800);
        parent::import();
        $this->packData();

        $sql = $this->saveAll($this->data, new TbWmsBillModel(), 'id');
        trace($sql,'updateBillCompanyAndPurTeam');
        echo $sql;exit;
    }
}