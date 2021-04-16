<?php

/**
 * 商品简介
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/9/15
 * Time: 15:47
 */
class GudsDescModel extends RelationModel
{
    protected $trueTableName = "tb_ms_guds_describe";

    /**
     * 商品简介批量添加
     * @param $params  参数
     * @param $SLLR_ID  品牌 ID
     * @param $MAIN_GUDS_ID  主ID
     * @param $GUDS_ID  商品ID
     * @return mixed
     */
    public function saveAllData($params, $SLLR_ID, $MAIN_GUDS_ID, $GUDS_ID)
    {
        $data = array();
        foreach ($params as $val) {
            $data[] = array(
                'SLLR_ID' => $SLLR_ID,
                'MAIN_GUDS_ID' => $MAIN_GUDS_ID,
                'GUDS_ID' => $GUDS_ID,
                'GUDS_DESCRIBE' => $val['gudsDesc'],
                'LANGUAGE' => $val['lang'],
                'updated_time' => data('Y-m-d H:i:s'),
            );
        }
        return $this->addAll($data);
    }

    /**
     * 商品简介添加
     * @param array $params 数据参数，主要来自客户端
     * @param array $saveGudsRes 保存商品的结果
     * @return mixed
     */
    public function saveData($params, $saveGudsRes)
    {
        if (empty($params)) return  false;

        $descArr = $newDesc = $valuesArr = [];
        foreach ($params['desc'] as $key => $val) {
            foreach ($val as $lang => $desc) {
                $descArr[$lang][$key] = array('gudsInfo' => $key, 'productDetail' => $desc);
            }
        }

        foreach ($saveGudsRes['langData'] as $key => $val) {
            if (empty($descArr[$key])) {
                continue;
            }

            $newDesc[$key]['SLLR_ID'] = $val['sllrId'];
            $newDesc[$key]['MAIN_GUDS_ID'] = $val['mainId'];
            $newDesc[$key]['GUDS_ID'] = $val['gudsId'];
            $newDesc[$key]['GUDS_DESCRIBE'] = addslashes(json_encode(array_values($descArr[$key])));
            $newDesc[$key]['LANGUAGE'] = $key;
            $newDesc[$key]['updated_time'] = date('Y-m-d H:i:s');
            $valuesArr[] = "('" . implode("','", $newDesc[$key]) . "')";
        }

        $fields = implode(',', array_keys(reset($newDesc)));
        $valueStr = implode(',', $valuesArr);
        $dataSql = "INSERT INTO {$this->trueTableName} ({$fields}) VALUES {$valueStr};";
        $saveRes = !empty($valueStr) ? $this->execute($dataSql) : false;//执行数据保存
        return $saveRes;
    }

    /**
     * 获取商品说明数据
     * @param $params
     * @return mixed
     */
    public function getDescData($params)
    {
        return $this->where($params)->select();
    }

    /**
     * 获取商品说明数据通过商品id 和 语言
     * @param $gudsId
     * @param $lang
     * @return mixed
     */
    public function getDescDataByGudsIdAndLang($gudsId, $lang)
    {
        $where = array('GUDS_ID' => $gudsId, 'LANGUAGE' => $lang);
        return $this->getDescData($where);
    }

    /**
     * 获取商品说明数据通过商品主Id 和 品牌Id
     * @param $mainId
     * @param $SllrId
     * @return mixed
     */
    public function getDescDataByMainIdAndSllrId($mainId, $SllrId)
    {
        $where = array('MAIN_GUDS_ID' => $mainId, 'SLLR_ID' => $SllrId);
        return $this->getDescData($where);
    }

    /**
     * 更新数据
     * @param $params
     * @return mixed
     */
    public function updateData($params)
    {
        $where['GUDS_ID'] = $params['gudsId'];
        !empty($params['gudsId']) && $data['GUDS_ID'] = $params['gudsId'];
        !empty($params['mainId']) && $data['MAIN_GUDS_ID'] = $params['mainId'];
        !empty($params['desc']) && $data['GUDS_DESCRIBE'] = $params['desc'];
        !empty($params['lang']) && $data['LANGUAGE'] = $params['lang'];
        $data['updated_time'] = date('Y-m-d H:i:s');
        return $this->add($data,$where,true);
    }
}
