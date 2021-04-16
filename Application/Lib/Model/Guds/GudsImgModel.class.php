<?php

/**
 * 商品图片模块
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/7/28
 * Time: 14:16
 */
class GudsImgModel extends RelationModel
{
    protected $trueTableName = 'tb_ms_guds_img';

    /**
     * 获取商品图片数据
     * @param array $where
     * @return mixed
     */
    public function getGudsImgData($where = array())
    {
        $field = '  tb_ms_guds_img.SLLR_ID,
                    tb_ms_guds_img.MAIN_GUDS_ID,
                    tb_ms_guds_img.GUDS_ID,
                    tb_ms_guds_img.GUDS_IMG_CD,
                    tb_ms_guds_img.GUDS_IMG_ORGT_FILE_NM,
                    tb_ms_guds_img.GUDS_IMG_SYS_FILE_NM,
                    tb_ms_guds_img.GUDS_IMG_CDN_ADDR,
                    tb_ms_guds_img.LANGUAGE';

        $res = $this->field($field)->where($where)->select();
        return $res;
    }

    /**
     * 通过$MAIN_GUDS_ID||/&& $LANGUAGE 获取商品图片详情
     * @param $MAIN_GUDS_ID  主商品
     * @param $LANGUAGE   语言
     * @return mixed
     */
    public function getGudsByMgudsIdAndLang($MAIN_GUDS_ID, $LANGUAGE)
    {
        $where = empty($MAIN_GUDS_ID) ? array() : array('MAIN_GUDS_ID' => array('eq', $MAIN_GUDS_ID));
        $where = empty($where) ? array() : (empty($LANGUAGE) ? $where : array_merge($where, array('LANGUAGE' => array('eq', $LANGUAGE))));
        return $this->getGudsImgData($where);
    }

    /**
     * 通过$GUDS_ID||/&& $SLLR_ID 获取商品图片详情
     * @param $GUDS_ID  商品id
     * @param $SLLR_ID  品牌id
     * @return mixed
     */
    public function getGudsImgByGudsIdAndSllrId($GUDS_ID, $SLLR_ID)
    {
        $where = empty($GUDS_ID) ? array() : array('GUDS_ID' => array('eq', $GUDS_ID));
        $where = empty($where) ? array() : (empty($SLLR_ID) ? $where : array_merge($where, array('SLLR_ID' => array('eq', $SLLR_ID))));
        return $this->getGudsImgData($where);
    }

    /**
     * 通过$MAIN_GUDS_ID||/&& $SLLR_ID 获取商品图片详情
     * @param $MAIN_GUDS_ID  商品主id
     * @param $SLLR_ID  品牌id
     * @return mixed
     */
    public function getGudsImgByMainIdAndSllrId($MAIN_GUDS_ID, $SLLR_ID)
    {
        $where = empty($MAIN_GUDS_ID) ? array() : array('MAIN_GUDS_ID' => array('eq', $MAIN_GUDS_ID));
        $where = empty($where) ? array() : (empty($SLLR_ID) ? $where : array_merge($where, array('SLLR_ID' => array('eq', $SLLR_ID))));
        return $this->getGudsImgData($where);
    }

    /**
     * 保存图片数据
     * add data
     * @param array $params 数据参数，主要来自客户端
     * @param array $saveGudsRes 保存商品的结果
     *
     * @return mixed
     */
    public function saveData($params, $saveGudsRes)
    {
        if (empty($params)) return false;
        $newImg = $valuesArr = [];
        //根据保存成功的SPU，保存去图片
        foreach ($saveGudsRes['langData'] as $key => $val) {
            if (empty($params['langData'][$key]['imgData'])) continue;

            $newImg[$key]['SLLR_ID'] = $val['sllrId'];
            $newImg[$key]['MAIN_GUDS_ID'] = $val['mainId'];
            $newImg[$key]['GUDS_ID'] = $val['gudsId'];
            $newImg[$key]['GUDS_IMG_CD'] = 'N000080200';
            $newImg[$key]['GUDS_IMG_ORGT_FILE_NM'] = $params['langData'][$key]['imgData']['orgtName'];
            $newImg[$key]['GUDS_IMG_SYS_FILE_NM'] = $params['langData'][$key]['imgData']['newName'];
            $newImg[$key]['GUDS_IMG_CDN_ADDR'] = $params['langData'][$key]['imgData']['cdnAddr'];
            $newImg[$key]['SYS_REGR_ID'] = $val['sllrId'];
            $newImg[$key]['SYS_CHGR_ID'] = $val['sllrId'];
            $newImg[$key]['LANGUAGE'] = $key;

            $valuesArr[] = "('" . implode("','", $newImg[$key]) . "')";
        }

        $fields = implode(',', array_keys(reset($newImg)));
        $valueStr = implode(',', $valuesArr);
        $dataSql = "INSERT INTO {$this->trueTableName} ({$fields}) VALUES {$valueStr};";
        $saveRes = !empty($valueStr) ? $this->execute($dataSql) : false;//执行数据保存
        return $saveRes;
    }

    /**
     * 更新图片数据
     * update data
     * @param $params
     * @return mixed
     */
    public function updateData($params)
    {
        if (empty($params['GUDS_ID']) && empty($params['SLLR_ID'])){
            return false;
        }

        //更新条件
        $where['GUDS_IMG_CD'] = $params['GUDS_IMG_CD'] ? $params['GUDS_IMG_CD'] : 'N000080200';
        $where['GUDS_ID'] = $params['GUDS_ID'];
        $where['LANGUAGE'] = $params['LANGUAGE'];

        //更新数据
        $data['GUDS_IMG_CD'] = !empty($params['GUDS_IMG_CD']) ? $params['GUDS_IMG_CD'] : 'N000080200';
        !empty($params['GUDS_IMG_ORGT_FILE_NM']) && $data['GUDS_IMG_ORGT_FILE_NM'] = $params['GUDS_IMG_ORGT_FILE_NM'];
        !empty($params['GUDS_IMG_SYS_FILE_NM']) && $data['GUDS_IMG_SYS_FILE_NM'] = $params['GUDS_IMG_SYS_FILE_NM'];
        !empty($params['GUDS_IMG_CDN_ADDR']) && $data['GUDS_IMG_CDN_ADDR'] = $params['GUDS_IMG_CDN_ADDR'];
        $data['SYS_REGR_ID'] = $params['SLLR_ID'];
        $data['SYS_CHGR_ID'] = $params['SLLR_ID'];
        $data['SYS_REG_DTTM'] = date('Y-m-d H:i:s');
        $data['SYS_CHG_DTTM'] = date('Y-m-d H:i:s');
        $data['LANGUAGE'] = $params['LANGUAGE'];
        $data['updated_time'] = date('Y-m-d H:i:s');

        //有就更新，没有就添加
        $image = $this->where($where)->select();
        if (!empty($image)){
            $res = $this->where($where)->save($data);
            return $res;
        } else{
            $data['MAIN_GUDS_ID'] = $params['MAIN_GUDS_ID'];
            $data['GUDS_ID'] = $params['GUDS_ID'];
            return $this->add($data);
        }
    }

}