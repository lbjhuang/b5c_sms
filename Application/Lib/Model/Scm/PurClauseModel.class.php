<?php

class PurClauseModel extends BaseModel
{
    protected $trueTableName = 'tb_pur_clause';

    public function addClause($quotation, $id) {
        $this->startTrans();
        foreach ($quotation as $key => $value) {
            if ($id) {
        	   $quotation[$key]['updated_by'] = $_SESSION['m_loginname']; 
            } else {
               $quotation[$key]['created_by'] = $_SESSION['m_loginname']; 
            }
        }
        $res = $this->addAll($quotation);
        M()->commit();
        return $res;
    }

}