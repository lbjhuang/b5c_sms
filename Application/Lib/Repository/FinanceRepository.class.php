<?php
/**
 * User: yangsu
 * Date: 18/10/17
 * Time: 15:36
 */

class FinanceRepository extends Repository
{

    /**
     * @var array
     */
    public $exp_cell_name = [
        ['id', 'ID'],
        ['account_class_cd', '账户归属'],
        ['company_code', '公司名称'],
        ['account_type', '账户类型'],
        ['payment_channel_cd', '平台名称'],
        ['open_bank', '银行名称'],
        ['bank_short_name', '银行简称'],
        ['account_bank', '银行/平台账号'],
        ['currency_code', '币种'],
        ['swift_code', 'SWIFT Code'],
        ['bsb_no', 'BSB No.'],
        ['reason', '账户备注'],
        ['reason', '本地结算代码'],
        ['bank_address', '银行地址'],
        ['bank_address_detail', '银行详细地址'],
        ['bank_postal_code', '银行邮编'],
        ['bank_account_type', '账户种类'],
        ['state', '状态'],
    ];
    /**
     * @var string
     */
    public $exp_title = '银行账户导出';


    /**
     * @param $ids
     *
     * @return mixed
     */
    public function getAccounts($ids)
    {
        $where['id'] = ['IN', $ids];
        $accounts = $this->model->table('tb_fin_account_bank')
            ->where($where)
            ->select();
        return $accounts;
    }

    //税号列表搜索
    public function searchTaxNumberList($where, $limit, $is_excel) {
        $field = [
            'tax.*',
            'area.zh_name as country_name'
        ];
        $query = $this->model->table('tb_fin_tax_number tax')
            ->field($field)
            ->join('tb_ms_user_area area on area.id = tax.country_id')
            ->where($where);
        $query_copy = clone $query;
        $pages['total']        = (int) $query->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        if (false === $is_excel) {
            $query_copy->limit($limit[0], $limit[1]);
        }
        $db_res = $query_copy->order('updated_at desc')->select();
        if (empty($db_res)) {
            $db_res = [];
        }
        return [$db_res, $pages];
    }

    // 采购付款单信息
    public function getAuditInfo($where, $field = '*')
    {
        $where['deleted_at'] = ['EXP', 'IS NULL'];
        $where['deleted_by'] = ['EXP', 'IS NULL'];
        return $this->model->table('tb_pur_payment_audit')
        ->field($field)
        ->where($where)
        ->find();
    }
}