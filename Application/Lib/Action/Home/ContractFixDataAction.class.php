<?php

class ContractFixDataAction extends BaseAction
{
    public function fixUploadFileStatus()
    {
        // $sql = "select ID,CON_COMPANY_CD from tb_crm_contract where audit_status_cd = " . TbCrmContractModel::UPLOADING;
        // 获取目前所有待上传状态的记录
        // 将次状态更新
        // 归档人
        // 更新补充下盖章人根据公司id来判断
        $contractModel = M('crm_contract', 'tb_');
        $where['audit_status_cd'] = TbCrmContractModel::UPLOADING;
        $where['audit_status_sec_cd'] = ['exp', 'IS NULL'];
        $res = $contractModel->field('ID, CON_COMPANY_CD, CON_NO')->where($where)->select();
        foreach ($res as $key => $value) {
            $save = [];
            $save['file_by'] = 'Ruby.Wang';
            $save['audit_status_sec_cd'] = TbCrmContractModel::UPLOADING_FIRST;
            if (TbCrmContractModel::LEGAL_COM_NO === $value['CON_COMPANY_CD']) {
                $save['seal_by'] = TbCrmContractModel::SEAL_FIRST;
            } else {
                $save['seal_by'] = TbCrmContractModel::SEAL_SECOND;
            }
            $re = $contractModel->where(['ID' => $value['ID']])->save($save);
            if (false === $re) {
                p($save);
                p($value['ID']);
            }
            p($value['CON_NO']);
        }
        echo 'SUCCESS';
    }
    public function fixSupplierName()
    {
        set_time_limit(0);
        $sql = "select CON_NO,supplier_id,audit_status_cd FROM tb_crm_contract where (SP_NAME = '' OR SP_NAME IS NULL) AND CREATE_TIME > '2020-12-22 00:00:00' AND (audit_status_cd != 'N003660008' AND audit_status_cd != 'N003660001')";
        $res = M()->query($sql);
        $contractModel = M('crm_contract', 'tb_');
        foreach ($res as $key => $value) {
            $where = []; $save = [];
            $re = A('Supplier')->search_supplier_by_id($value['supplier_id']);
            if (!$re['SP_NAME']) {
                $lastSql = M()->_sql();
                p("{$value['CON_NO']}供应商id为{$value['supplier_id']}缺失供应商名称" . $lastSql);
                continue;
            }
            $where['CON_NO'] = $value['CON_NO'];
            $save['SP_NAME'] = $re['SP_NAME'];
            $result = $contractModel->where($where)->save($save);
            if ($result === false) {
                $lastSql = M()->_sql();
                p("{$value['CON_NO']}供应商id为{$value['supplier_id']}更新失败" . $lastSql);
            }
            p($value['CON_NO']);
        }
        echo "SUCCESS";
    }
    //13.历史数据处理-合同负责人填充
    //14.历史数据处理-签约人数据格式化
    public function fixContractorData()
    {
        /*是否含 .
                是
                    end
                否
                    是否含'-'
                        是
                            拆开，取前面的数字，bbm_admin oa_id ->M_NAME
                        否
                            bbm_admin EMP_SC_NM -> M_NAME*/
        // 获取签约人和合同ID

        $contractModel = M('crm_contract', 'tb_');
        $adminModel = M('admin', 'bbm_');
        $res = $contractModel->getField('ID,CONTRACTOR');
        $logRes = [];
        foreach ($res as $key => $value) {
            if (!$value) {
                continue;
            }
            if (false === strstr($value, '.')) {
                $temp = [];
                if (strstr($value, '-')) {
                    $oaId = explode('-', $value)[0];
                    if ($oaId) {
                        $temp['oaId'] = $oaId;
                        $name = '';
                        $name = $adminModel->where(['oa_id' => $oaId])->getField('M_NAME');
                    }
                } else {
                    $name = '';
                    $name = $adminModel->where(['EMP_SC_NM' => $value])->getField('M_NAME');
                }
                if ($name) {
                    if (false === $contractModel->where(['ID' => $key])->save(['CONTRACTOR' => $name])) {
                        p($key . '-' . $name);
                    }
                }
                
                $temp['ID'] = $key;
                $temp['value'] = $value;
                $temp['name'] = $name;
                $logRes[] = $temp;
            }        
        } 
        Logs(json_encode($logRes), __FUNCTION__.'----fixData', 'tr');   
        echo "SUCCESS";   
    }
    // 签约人数据格式化
    public function fixManageData()
    {
        $sql = "UPDATE tb_crm_contract b 
SET b.manager = b.CONTRACTOR 
WHERE
    ( b.manager IS NULL OR b.manager = '' )";
        $res = M()->query($sql);
        p($res);
    }

    public function fixAuditStatus()
    {
        $sql = "UPDATE tb_crm_contract SET audit_status_cd = 'N003660007' WHERE (audit_status_cd = '' OR audit_status_cd IS NULL)";
        $res = M()->query($sql);
        p($res);    
    }

    public function fixContractTel()
    {
        // $sql = "UPDATE tb_crm_contract SET BAK_CON_TEL = ' ' WHERE CON_NO = '202101070002'";
        $sql = "UPDATE tb_crm_contract SET BAK_CON_TEL = '-',BAK_CON_PHONE = '-' WHERE CON_NO = '202101120005'";
        $res = M()->query($sql);

        $sql = "UPDATE tb_crm_contract SET BAK_CON_TEL = '-',BAK_CON_PHONE = '-' WHERE CON_NO = '202101130001'";
        $res = M()->query($sql);
        echo "SUCCESS";

    }

    public function fixRepeatConData()
    {
        $sql = "UPDATE tb_crm_contract SET CON_NO = '202101060099' WHERE ID = '3381'";
        $res = M()->query($sql);
        p($res);

    }


    public function fixContractNo()
    {
        // 获取需要改动的合同列表结果
        $sql = "SELECT ID FROM tb_crm_contract WHERE (CON_NO IS NULL OR CON_NO = '')";
        $res = M()->query($sql);
        $contractModel = M('crm_contract', 'tb_');
        $TbCrmContractModel = D('TbCrmContract');
        foreach ($res as $key => $value) {
            $date = date('Y-m-d H:i:s', time());
            $save = [];
            $save['CREATE_TIME'] = $date;
            $save['CON_NO'] = $TbCrmContractModel->createContractNo();
            $re = $contractModel->where(['ID' => $value['ID']])->save($save);
            if (false === $re) {
                echo M()->_sql();
            }
        }
        echo "SUCCESS";
    }
}