<?php
// 
// 
// 
// 
// 
// 
// 
// 
// 

/**
 * B2bSearch 类
 * 
 * @category   
 * @package  
 * @subpackage  
 * @author    huaxin
 */
class B2bSearch {

    /**
     *  Arrange where info by request
     *  @param 
     *  @return 
     */
    static public function arrange_again_po($whereArr,$postArr){
        $whereArr = is_array($whereArr)?$whereArr:array();
        if(isset($whereArr['g.goods_title'])){
            unset($whereArr['g.goods_title']);
        }
        if(isset($whereArr['g.SKU_ID'])){
            unset($whereArr['g.SKU_ID']);
        }
        if(!empty($postArr['goods_title_info']) and !empty($postArr['search_type'])) {
            $keywords = trim($postArr['goods_title_info']);
            $Models = new Model();
            switch ($postArr['search_type']) {
                case 'SKU_ID':
                    $SKU_ID = $keywords;
                    if(empty($SKU_ID)) $SKU_ID='';
                    $where = array();
                    $where['SKU_ID'] = array('IN',$SKU_ID);
                    $subQuery = $Models->field('ORDER_ID')->table('tb_b2b_goods')->where($where)->select(false);
                    $whereArr['_string'] = " tb_b2b_order.ID in ".$subQuery." ";
                    break;
                case 'bar_code':
                    $SKU_ID = SkuModel::upcTosku($keywords);
                    if(empty($SKU_ID)) $SKU_ID='';
                    $where = array();
                    $where['SKU_ID'] = array(
                        array('eq',$SKU_ID),
                        array('exp','is not null'),
                        array('NEQ',''),
                        'and'
                    );
                    $subQuery = $Models->field('ORDER_ID')->table('tb_b2b_goods')->where($where)->select(false);
                    $whereArr['_string'] = " tb_b2b_order.ID in ".$subQuery." ";
                    break;
                case 'goods_title':
                    $where = array();
                    $where['goods_title'] = array('LIKE',"%{$keywords}%");
                    $subQuery = $Models->field('ORDER_ID')->table('tb_b2b_goods')->where($where)->select(false);
                    $whereArr['_string'] = " tb_b2b_order.ID in ".$subQuery." ";
                    break;
            }
        }

        return $whereArr;
    }

    /**
     *  Check PO ID is repeat or not
     *  @param 
     *  @return 
     */
    static public function usablePoId($poNum,$o_id){
        $where = array();
        $where['PO_ID'] = $poNum;
        if($o_id){
            $where['ID'] = array('NEQ',$o_id);
        }
        $Models = new Model();
        $result = $Models->field('*')->table('tb_b2b_order')->where($where)->find();
        $ret = true;
        if(isset($result['ID'])){
            $ret = false;
        }
        return $ret;
    }


}



