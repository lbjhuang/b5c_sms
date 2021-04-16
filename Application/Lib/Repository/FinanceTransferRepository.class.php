<?php
/**
 * User: tr
 * Date: 19/04/30
 * Time: 15:36
 */

class FinanceTransferRepository extends Repository
{
    public function getRuleInfo($conditions = [])
    {    
        if (count($conditions) == 0) {
            return false;
        }
        $res = $this->model->table('tb_op_settlement_map m')
            ->join('left join tb_op_settlement_map_rules mr on m.map_id = mr.map_id')
            ->join('left join tb_op_settlement_rules r on r.rules_id = mr.rules_id')
            ->field('r.func_name, r.file_name')
            ->where($conditions)
            ->select();
        return $res[0];
    }


}