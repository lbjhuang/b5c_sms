<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/22
 * Time: 13:21
 */

require_once APP_PATH.'Lib/Logic/BaseLogic.class.php';

class ScmBaseLogic extends BaseLogic
{
    static $step = [
        'demand_submit'     => 'N002120100',
        'demand_approve'    => 'N002120200',
        'purchase_claim'    => 'N002120300',
        'seller_choose'     => 'N002120400',
        'purchase_confirm'  => 'N002120500',
        'seller_submit'     => 'N002120600',
        'leader_approve'    => 'N002120700',
        'ceo_approve'       => 'N002120800',
        'upload_po'         => 'N002120900',
        'justice_approve'   => 'N002121000',
        'justice_stamp'     => 'N002121100',
        'po_archive'        => 'N002121200',
        'create_order'      => 'N002121300',
        'success'           => 'N002121400'
    ];

    protected $demand               = [];
    protected $demand_goods         = [];
    protected $quotation            = [];
    protected $quotations           = [];
    protected $quotation_goods      = [];
    protected $demand_m             = null;
    protected $demand_goods_m       = null;
    protected $quotation_m          = null;
    protected $quotation_goods_m    = null;

    protected function waterMarkToFile($po) {
        //pic_watermark
        $po_with_watermark = [];
        foreach ($po as $v) {
            $save_name                  = $v['save_name'];
            $original_name              = $v['original_name'];
            $original_name_exp          = explode('.',$original_name);
            $save_name_exp              = explode('.',$save_name);
//            if(in_array(end($save_name_exp),['pdf','doc','docx','png','jpg'])) {
                $res = ApiModel::waterMark($save_name);
                if($res['code'] == 2000) {
                    $watermark_name_exp = explode('.',$res['data']['name']);
                    $original_name_watermark    = str_replace('.'.end($original_name_exp),'-sy.'.end($watermark_name_exp),$original_name);
                    $po_with_watermark[] = ['original_name' => $original_name_watermark, 'save_name' => $res['data']['name']];
                }else {
                    $this->error = '生成水印失败';
                    ELog::add(['info'=>$this->error,'file_name'=>$save_name,'response'=>$res]);
                    break;
                }
//            }else {
//                $this->error='暂时只支持pdf、doc、docx、png、jpg文件加水印';
//                break;
//            }
        }
        if($this->error)  return false;
        return $po_with_watermark;
    }

    public function getDemand($id = null) {
        if($id) {
            return $this->demand = D('Scm/Demand')->lock(true)->where(['id'=>$id])->find();
        }else {
            if($this->demand) {
                return $this->demand;
            }else {
                return false;
            }
        }
    }

    public function getDemandGoods($demand_id = null) {
        if($demand_id) {
            return $this->demand_goods = D('Scm/DemandGoods')->where(['demand_id'=>$demand_id])->select();
        }else {
            if($this->demand_goods) {
                return $this->demand_goods;
            }else {
                return false;
            }
        }
    }

    public function getQuotationsChosen($demand_id = null) {
        return $this->getQuotations($demand_id,'chosen');
    }

    public function getQuotationsAll($demand_id = null) {
        return $this->getQuotations($demand_id,'all');
    }

    public function getQuotations($demand_id = null,$type = '') {
        $where['invalid']   = 0;
        switch ($type) {
            case 'chosen':
                $where['chosen'] = QuotationModel::$chosen['chosen'];
                break;
            case 'all':
                break;
            default:
                break;
        }
        if($demand_id) {
            $where['demand_id'] = $demand_id;
            return $this->quotations = D('Scm/Quotation')->lock(true)->where($where)->select();
        }else {
            if($this->quotations) {
                return $this->quotations;
            }elseif($this->demand) {
                $where['demand_id'] = $this->demand['id'];
                return $this->quotations = D('Scm/Quotation')->lock(true)->where($where)->select();
            }else {
                return false;
            }
        }
    }

    public function getQuotation($id) {
        if($id) {
            return $this->quotation = D('Scm/Quotation')->lock(true)->where(['id'=>$id])->find();
        }else {
            if($this->quotation) {
                return $this->quotation;
            }else {
                return false;
            }
        }
    }

    /**
     * 需求和报价是否同步
     * @param $demand_id
     * @return bool
     */
    public function isStepSync($demand_id) {
        $res = D('Scm/Demand')
            ->alias('t')
            ->join('left join tb_sell_quotation a on a.demand_id=t.id')
            ->where(['t.id'=>$demand_id,'_string'=>'a.step<>t.step','a.invalid'=>0,'chosen'=>QuotationModel::$chosen['chosen']])
            ->getField('a.id');
        $this->error = '需求和报价不同步';
        return $res ? false : true;
    }

    public function formatDemandGoodsUpcMore(array $goods, $upc_field = 'upc_id')
    {
        if($goods) {
            $sku_ids = array_column($goods,'sku_id');
            $product_sku_model =  D("Pms/PmsProductSku");
            $goods_skus = $product_sku_model->where([
                'sku_id' => ['in', $sku_ids]
            ])->field(['sku_id','upc_id','upc_more'])->select();
            $goods_skus = array_column($goods_skus,'upc_more','sku_id');
            foreach ($goods as &$good) {
                if(isset($goods_skus[$good['sku_id']]) && $goods_skus[$good['sku_id']]) {
                    $upc_more_arr = explode(',', $goods_skus[$good['sku_id']]);
                    $temp_skus = explode(",", str_replace(["\r\n", "\r", "\n"],'',$good[$upc_field]));
                    $upc_more_arr = array_unique(array_merge($upc_more_arr, $temp_skus));
                    $good[$upc_field] = implode(",\r\n", $upc_more_arr);
                }
            }
        }
        return $goods;
    }

}