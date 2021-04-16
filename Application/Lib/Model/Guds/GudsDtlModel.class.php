<?php

/**
 * 商品详情内容
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/9/15
 * Time: 15:48
 */
class GudsDtlModel extends RelationModel
{
    protected $trueTableName = "tb_ms_guds_dtl";

    /**
     * 添加商品详情内容
     * @param array $params 数据参数，主要来自客户端
     * @param array $saveGudsRes 保存商品的结果
     * @return mixed
     */
    public function saveData($params, $saveGudsRes)
    {
        $newDetail = $valuesArr = [];
        foreach ($saveGudsRes['langData'] as $key => $val) {
            if (empty($params['langData'][$key]['detail'])) continue;

            //$newDetail[$key]['SLLR_ID'] = $val['sllrId'];
            $newDetail[$key]['MAIN_GUDS_ID'] = $val['mainId'];
            $newDetail[$key]['GUDS_ID'] = $val['gudsId'];
            $newDetail[$key]['GUDS_DTL_CONT'] = $params['langData'][$key]['detail'];
            $newDetail[$key]['GUDS_DTL_CONT_WEB'] = $params['langData'][$key]['detail'];
            $newDetail[$key]['GUDS_DTL_CDN_ADDR'] = '';
            $newDetail[$key]['LANGUAGE'] = $key;
            $newDetail[$key]['SYS_REGR_ID'] = $_SESSION['m_loginname'];
            $newDetail[$key]['SYS_REG_DTTM'] = date('Y-m-d H:i:s');
            $newDetail[$key]['SYS_CHGR_ID'] = $_SESSION['m_loginname'];
            $newDetail[$key]['SYS_CHG_DTTM'] = date('Y-m-d H:i:s');
            $newDetail[$key]['updated_time'] = date('Y-m-d H:i:s');

            $valuesArr[] = "('" . implode("','", $newDetail[$key]) . "')";
        }

        $fields = implode(',', array_keys(reset($newDetail)));
        $valueStr = implode(',', $valuesArr);
        $dataSql = "INSERT INTO {$this->trueTableName} ({$fields}) VALUES {$valueStr};";
        $saveRes = !empty($valueStr) ? $this->execute($dataSql) : false;//执行数据保存
        return $saveRes;
    }

    /**
     * 获取商品详情数据
     * @param $params
     * @return mixed
     */
    public function getDtlData($params)
    {
        return $this->where($params)->select();
    }

    /**
     * 获取商品详情数据通过商品id 和 语言
     * @param $gudsId
     * @param $lang
     * @return mixed
     */
    public function getDtlDataByGudsIdAndLang($gudsId, $lang)
    {
        $where = array('GUDS_ID' => $gudsId, 'LANGUAGE' => $lang);
        return $this->getDtlData($where);
    }

    /**
     * 更新商品详情数据
     * @param $params
     * @return mixed
     */
    public function updateData($params)
    {
        $where['GUDS_ID'] = $params['GUDS_ID'];
        $where['LANGUAGE'] = $params['lang'];
        !empty($params['GUDS_ID']) && $data['GUDS_ID'] = $params['GUDS_ID'];
        !empty($params['MAIN_GUDS_ID']) && $data['MAIN_GUDS_ID'] = $params['MAIN_GUDS_ID'];
        !empty($params['GUDS_DTL_CONT']) && $data['GUDS_DTL_CONT'] = $params['GUDS_DTL_CONT'];
        !empty($params['GUDS_DTL_CONT_WEB']) && $data['GUDS_DTL_CONT_WEB'] = $params['GUDS_DTL_CONT_WEB'];
        !empty($params['GUDS_DTL_CDN_ADDR']) && $data['GUDS_DTL_CDN_ADDR'] = $params['GUDS_DTL_CDN_ADDR'];
        !empty($params['lang']) && $data['LANGUAGE'] = $params['lang'];
        $data['updated_time'] = date('Y-m-d H:i:s');
        return $this->add($data, $where, true);
    }
}