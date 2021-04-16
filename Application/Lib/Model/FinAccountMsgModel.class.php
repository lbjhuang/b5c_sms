<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/4/2
 * Time: 10:26
 */

class FinAccountMsgModel
{
    /**
     * 写入审核信息
     * @param string $transferNo 转账编号
     * @param string $msg 批注
     * @param int $currentAuditStep 当前审核节点
     * @return int|bool 写入成功返回写入id,否则返回false
     */
    public static function writeAuditMsg($transferNo, $msg, $currentAuditStep)
    {
        $saveData ['transfer_no'] = $transferNo;
        $saveData ['msg'] = $msg;
        $saveData ['current_audit_step'] = $currentAuditStep;
        $saveData ['auditor_id'] = $_SESSION['userId'];
        $saveData ['auditor_nm'] = $_SESSION['emp_sc_nm'];
        $model = new Model();
        return $model->table('tb_fin_account_audit_msg')->add($saveData);
    }

    /**
     * 获取审核信息
     * @param string $transferNo 转账编号
     * @param int|null $currentAuditStep 为null时，获取全部转账批注
     * @return array|null 找到数据则返回，否则返回空
     */
    public static function getAuditMsg($transferNo, $currentAuditStep = null)
    {
        $model = new Model();
        $model->table('tb_fin_account_audit_msg');
        $condition ['transfer_no'] = $transferNo;
        if ($currentAuditStep)
            $conditions ['current_audit_step'] = $currentAuditStep;

        return $model->where($condition)->select();
    }

    /**
     * 清除审核信息
     * @param string $transferNo
     */
    public static function cleanAuditMsg($transferNo)
    {
        $model = new Model();
        $conditions ['transfer_no'] = $transferNo;
        $model->delete($conditions);
    }

    /**
     * 更新审核信息
     * @param $transferNo
     * @param $currentAuditStep
     * @param $msg
     * @param $userId
     * @return null|true
     */
    public static function updateAuditMsg($transferNo, $currentAuditStep, $msg, $userId)
    {
        $model = new Model();
        $data ['msg'] = $msg;
        $conditions ['auditor_id']  = $userId;
        $conditions ['current_audit_step'] = $currentAuditStep;
        $conditions ['transfer_no'] = $transferNo;
        $model->table('tb_fin_account_audit_msg')->where($conditions)->save($data);
        return $model->getLastSql();
    }
}