<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/7
 * Time: 10:58
 */
@import("@.Action.Scm.ScmBaseAction");
@import("@.Model.Scm.QuotationModel");
@import("@.Model.Scm.DemandModel");
class DisplayAction extends ScmBaseAction
{
    public function demand_list() {
        $this->display();
    }

    public function reportPrice_list() {
        $this->display();
    }

    public function demand_detail() {
        $this->display();
    }
    public function sell() {
        $this->display();
    }

    public function uppo() {
        $this->display();
    }

    public function demand_draft() {
        $this->display();
    }

    /**
     * @param $demand_id
     * @param $authkey
     * @param bool $is_wechat
     * @return $this|array
     */
    public function getCeoEmailContent($demand_id, $authkey,$is_wechat = false) {
        $logic_d = D('Scm/Demand','Logic');
        $demand = D('Scm/Demand')->field('id,demand_code,is_spot,demand_type,contract,sell_team,
        business_mode,our_company,sell_currency,sell_amount,customer,collection_time,expense_currency,
        expense,seller,remark,sales_leadership_approval_by,expected_sales_country,expected_sales_platform_cd')
            ->where(['id' => $demand_id])->find();
        $demand['expected_sales_country'] = D('Area')->getCountryNameByIds($demand['expected_sales_country']);
        $demand['expected_sales_platform_cd'] = D('Scm/Demand','Logic')->getPlatformCd($demand['expected_sales_platform_cd']);
        $demand['collection_time'] = json_decode($demand['collection_time'], true);
        $demand['sell_amount'] = number_format($demand['sell_amount'], 2);
        $demand['expense'] = number_format($demand['expense'], 2);
        $this->ce = M('DemandProfit', 'tb_sell_')->where(['demand_id' => $demand_id])->find();
        //cd转换
        $logic_d->cd2Val($demand, 'demand');
        $this->demand = $demand;
        //商品
        $goods = D('Scm/DemandGoods')->field('sku_id,goods_name,sku_attribute,purchase_number,spot_number,on_way_number,sell_price')->where(['demand_id' => $demand_id])->select();
        $logic_d->cd2Val($goods, 'demand_goods');
        $this->goods = $goods;
        $ce = M('DemandProfit', 'tb_sell_')->where(['demand_id' => $demand_id])->find();
        foreach ($ce as $k => &$v) {
            if (in_array($k, ['revenue','commodity_cost','commodity_cost_after_tax_refund','tax_refund','gross_profit','gross_profit_after_tax_refund', 'net_profit', 'revenue_spot', 'commodity_cost_spot', 'sale_cost_spot', 'gross_profit_spot', 'net_profit_spot', 'gross_profit_after_tax_refund_spot', 'revenue_total', 'commodity_cost_total', 'sale_cost_total', 'gross_profit_total', 'net_profit_total'])) {
                $v = number_format($v, 2);
            }
            if (in_array($k, ['gross_profit_rate_after_tax_refund', 'gross_profit_rate','gross_profit_rate_spot','net_profit_rate_spot', 'gross_profit_rate_after_tax_refund_spot', 'gross_profit_rate_total','net_profit_rate_total','net_profit_rate'])) {
                $v = number_format($v * 100, 2);
            }
            if ($k == 'ce_after_tax_refund') {
                $v = number_format($v, 0);
            }
        }
        $ce['expense_plus'] = number_format($ce['sale_cost'] + $ce['sale_cost_spot'], 2);
        $this->ce = $ce;
        $quotations = D('Scm/Quotation')->alias('t')
            ->field('our_company,supplier,supplier_no,po_amount,currency,purchaser,purchase_team,amount,drawback_amount,currency,drawback_time,payment_time,t.expense,expense_currency')
            ->where(['demand_id' => $demand_id, 'invalid' => 0, 'chosen' => QuotationModel::$chosen['chosen']])
            ->select();
        foreach ($quotations as &$q) {
            $q['payment_time'] = json_decode($q['payment_time'], true);
            $q['amount'] = number_format($q['amount'], 2);
            $q['drawback_amount'] = number_format($q['drawback_amount'], 2);
            $q['po_amount'] = number_format($q['po_amount'], 2);
            $q['expense'] = number_format($q['expense'], 2);
            $logic_d->cd2Val($q, 'quotation');
            $logic_d->cd2Val($q, 'currency');
        }
        $this->quotations = $quotations;
        //key，无须登录即可审批
        $this->authkey = $authkey;
        if ($is_wechat) {
            return [
                'ce'=>$ce,
                'demand'=>$demand,
                'goods'=>$goods,
                'quotations'=>$quotations,
            ];
        }
        $ceo = $this->fetch('Scm@Display:ceo_email_en');
        $this->cc_flg = true;
        $cc = $this->fetch('Scm@Display:ceo_email_en');
        return ['ceo' => $ceo, 'cc' => $cc];
    }
}