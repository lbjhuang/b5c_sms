<?php

/**
 * 商品审核模块
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/7/26
 * Time: 17:55
 */
class GudsChkModel extends RelationModel
{
    const PENDING = 'N000420100';   //审核中
    const DRAFT = 'N000420200';     //草稿
    const APPROVED = 'N000420400';  //审核成功
    const REJECT = 'N000420300';    //审核失败

    protected $trueTableName = 'tb_ms_guds_chk';
    protected $_map = [
        'guds' => 'GUDS_ID',
        'mainId' => 'MAIN_GUDS_ID',
        'chkStatus' => 'CHK_STATUS',
        'content' => 'CHK_CONTENT',
        'addTime' => 'ADD_TIME',
        'createTime' => 'UPDATE_TIME'
    ];

    static $_CHK_STATUS_VALS = array(
        'chkwait' => 'N000420100',   //审核中
        'chking' => 'N000420200',   //草稿
        'chksucc' => 'N000420400',  //审核成功
        'chkfail' => 'N000420300',  //审核失败
    );

    /**
     * 保存商品审核数据
     * add data
     * @param $params
     * @return mixed
     */
    public function saveData($params)
    {
        $data['MAIN_GUDS_ID'] = $params['MAIN_GUDS_ID'];
        $data['GUDS_ID'] = $params['GUDS_ID'];
        $data['CHK_STATUS'] = $params['CHK_STATUS'];
        $data['CHK_CONTENT'] = $params['CHK_CONTENT'];
        $data['ADD_TIME'] = date('Y-m-d H:i:s');
        $data['UPDATE_TIME'] = $data['ADD_TIME'];
        $this->startTrans();
        try{
            $res = $this->add($data);
            $sql = "UPDATE tb_ms_guds SET GUDS_REG_STAT_CD='{$params['CHK_STATUS']}' WHERE MAIN_GUDS_ID='{$params['MAIN_GUDS_ID']}';";
            $updateRes = $this->execute($sql);
        } catch (Exception $e){
            $this->rollback();
            return false;
        }

        if ($this->getDbError()){
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

    /**
     * 更新商品审核数据
     * update data
     * @param $params
     * @return mixed
     */
    public function updateData($params)
    {
        $where['MAIN_GUDS_ID'] = $params['MAIN_GUDS_ID'];
        $where['GUDS_ID'] = $params['GUDS_ID'];
        !empty($params['CHK_STATUS']) && $data['CHK_STATUS'] = $params['CHK_STATUS'];
        if (!empty($params['CHK_CONTENT']) || $params['CHK_STATUS'] ==self::APPROVED) {
            $data['CHK_CONTENT'] = $params['CHK_CONTENT'];
        }
        $data['UPDATE_TIME'] = date('Y-m-d H:i:s');

        $this->startTrans();
        try{
            $res = $this->where($where)->save($data);
            $sql = "UPDATE tb_ms_guds SET GUDS_REG_STAT_CD='{$params['CHK_STATUS']}' WHERE MAIN_GUDS_ID='{$params['MAIN_GUDS_ID']}';";
            $updateRes = $this->execute($sql);
        } catch (Exception $e){
            $this->rollback();
            return false;
        }

        if ($this->getDbError()){
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

    /**
     * 获取指定商品审核信息
     * @param $MAIN_GUDS_ID
     * @param $GUDS_ID
     * @return mixed
     */
    public function getChkData($MAIN_GUDS_ID, $GUDS_ID = null)
    {
        $where['MAIN_GUDS_ID'] = $MAIN_GUDS_ID;
        !empty($GUDS_ID) && $where['GUDS_ID'] = $GUDS_ID;
        return $this->where($where)->find();
    }

    /**
     * 通过mainId获取商品评论内容
     * @param $MAIN_GUDS_ID
     * @return mixed
     */
    public function getChkContent($MAIN_GUDS_ID, $GUDS_ID)
    {
        return $this->getChkData($MAIN_GUDS_ID, $GUDS_ID);
    }
}