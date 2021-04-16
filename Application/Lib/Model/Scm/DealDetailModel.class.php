<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/8
 * Time: 13:26
 */

class DealDetailModel extends BaseModel
{
    protected $trueTableName = 'tb_sell_deal_detail';

    public function dealList($param,$limit = '') {
        $where = $this->getWhere($param);
        $condition = implode(',',array_map(function($v){
            return '"'.$v.'"';
        },PmsBaseModel::getLangCondition()));
        $list = $this
            ->alias('t')
            ->field('t.*,demand_code,a.CD_VAL deal_type,b.CD_VAL business_mode,c.CD_VAL delivery_type,d.CD_VAL warehouse,e.CD_VAL currency,f.CD_VAL receive_mode,g.CD_VAL sell_currency,h.NAME receive_country,i.NAME receive_province,substring_index(group_concat(spu_name ORDER BY abs(right(language,6)-'.substr(TbMsCmnCdModel::$language_english_cd,-6).') desc ),",",1) goods_name')
            ->join('left join tb_ms_cmn_cd a on a.CD=t.deal_type')
            ->join('left join tb_ms_cmn_cd b on b.CD=t.business_mode')
            ->join('left join tb_ms_cmn_cd c on c.CD=t.delivery_type')
            ->join('left join tb_ms_cmn_cd d on d.CD=t.warehouse')
            ->join('left join tb_ms_cmn_cd e on e.CD=t.currency')
            ->join('left join tb_ms_cmn_cd f on f.CD=t.receive_mode')
            ->join('left join tb_ms_cmn_cd g on g.CD=t.sell_currency')
            ->join('left join tb_crm_site h on h.ID=t.receive_country')
            ->join('left join tb_crm_site i on i.ID=t.receive_province')
            ->join('left join '.PMS_DATABASE.'.product_sku pa on pa.sku_id=t.sku_id')
            ->join('left join '.PMS_DATABASE.'.product_detail pb on pb.spu_id=pa.spu_id and pb.language in ('.$condition.')')
            ->where($where)
            ->group('t.id')
            ->order('deal_time desc')
            ->limit($limit)
            ->select();
        $list = SkuModel::getInfo($list,'sku_id',['image_url','attributes']);
        $demand_l   = D('Scm/Demand','Logic');
        $list = $demand_l->formatDemandGoodsUpcMore($list,'bar_code');
        return $list;
    }

    public function dealCount($param) {
        $where = $this->getWhere($param);
        $condition = implode(',',array_map(function($v){
            return '"'.$v.'"';
        },PmsBaseModel::getLangCondition()));
        $sql   = $this
            ->alias('t')
            ->field('t.id')
            ->join('left join '.PMS_DATABASE.'.product_sku pa on pa.sku_id=t.sku_id')
            ->join('left join '.PMS_DATABASE.'.product_detail pb on pb.spu_id=pa.spu_id and pb.language in ('.$condition.')')
            ->where($where)
            ->group('t.id')
            ->buildSql();
        return M()->table($sql.' a')->count();
    }

    public function getWhere($param) {
        $where = [];
        if($param['search_id']) {
            $serch_ids = explode(',',$param['search_id']);
            $complex['t.sku_id']      = ['in',$serch_ids];
            $complex['t.bar_code']    = ['in',$serch_ids];
            $complex['pa.upc_id']         = ['in',$serch_ids];
            foreach ($serch_ids as $serch_id) {
                $find_in[] = " FIND_IN_SET('{$serch_id}',pa.upc_more)";
            }
            $find_in_string = implode('OR', $find_in);
            $complex['_string'] = $find_in_string;
            $complex['_logic']      = 'or';
            $where['_complex']      = $complex;
        }
        $param['goods_name'] ? $where['spu_name'] = ['like',['%'.$param['goods_name'].'%']] : '';
        $param['supplier'] ? $where['supplier'] = ['like',['%'.$param['supplier'].'%']] : '';
        $param['customer'] ? $where['customer'] = ['like',['%'.$param['customer'].'%']] : '';
        $param['deal_type'] ? $where['deal_type'] = $param['deal_type'] : '';
        $param['business_mode'] ? $where['business_mode'] = $param['business_mode'] : '';
        $param['delivery_type'] ? $where['delivery_type'] = $param['delivery_type'] : '';
        $param['receive_mode'] ? $where['receive_mode'] = $param['receive_mode'] : '';
        $param['receive_country'] ? $where['receive_country'] = $param['receive_country'] : '';
        $param['receive_province'] ? $where['receive_province'] = $param['receive_province'] : '';
        $param['warehouse'] ? $where['warehouse'] = $param['warehouse'] : '';
        if($param['start_time'] && $param['end_time']) {
            $where['t.deal_time'] = ['between',[$param['start_time'].' 00:00:00',$param['end_time'].' 23:59:59']];
        }elseif($param['start_time']) {
            $where['t.deal_time'] = ['egt',$param['start_time'].' 00:00:00'];
        }elseif($param['end_time']) {
            $where['t.deal_time'] = ['elt',$param['end_time'].' 23:59:59'];
        }
        return $where;
    }
}