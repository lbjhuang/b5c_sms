<?php
/**
 * Created by PhpStorm.
 * User: due
 * Date: 2019/3/8
 * Time: 10:20
 */

use App\Models\TbSysReview;

@import('@.Model.Orm.TbSysReview');
class ReviewModel
{
    /**
     * $data = [
     * 'review_no' => 'WMS_ddasdfsdf',
     * 'review_type' => 'WMS',
     * 'review_status_cd' => '1',
     * 'allowed_man_json' => [
     * 'due', 'yansu'
     * ],
     * 'detail_json' => [
     * 'data' => [
     * 'name' => 'due',
     * 'type' => 'person',
     * ],
     * 'data_keys' => [
     * 'name' => L('姓名'),
     * 'type' => L('类型'),
     * ],
     * 'config' => [
     * 'view_type' => 'db',
     * 'agree_btn' => 1,
     * 'refuse_btn' => 1,
     * 'refuse_text' => 0,
     * 'detail_btn' => 1,
     * ]
     * ],
     * 'callback_function' => 'db_fun',
     * 'req_json' => [
     * 'status' => 0,
     * 'reason' => '不同意',
     * ],
     * 'res_json' => [
     * 'code' => 2000,
     * 'data' => [],
     * 'msg' => 'success'
     * ]
     * ];
     * @param $data
     */
    public static function create($data) {
        $model = new TbSysReview();
        $data['created_by'] = $_SESSION['m_loginname'];
        $data['updated_by'] = $_SESSION['m_loginname'];
        $model->fill($data);
        $model->save();
        return $model->id;
    }

    /**根据审批单号获取审批详情
     * @param $no
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     */
    public static function findByNo($no)
    {
        return TbSysReview::query()->where('review_no', $no)
            ->first();
    }

    public static function find($id)
    {
        return TbSysReview::find($id);
    }

    /**
     * 获取审批信息详情链接
     * @param $review_no string 审批单据好,tb_sys_reviews.review_no
     * @param bool $debug 调试模式
     * @return string
     */
    public static function getBtnUrl($review_no, $debug = false)
    {
        $corp_id = WechatMsg::CORP_ID;
        $cb_url = 'http://erp.gshopper.com/index.php?m=api&a=wechat_approve&review_no=' . $review_no;
        if ($_SERVER['HTTP_HOST'] != 'erp.gshopper.com') {
            $cb_url .= '&redirect=' . $_SERVER['HTTP_HOST'];
        }
        if ($debug) {
            $cb_url .= '&debug=1';
        }
    
        $cb_url = urlencode($cb_url);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$corp_id}&redirect_uri={$cb_url}&response_type=code&scope=snsapi_base#wechat_redirect";
    }


    
    /**
     * 获取调拨页面详情链接
     *
     * @return string
     */
    public static function getStockUrl($id)
    {
       
        $cb_url = 'http://erp.gshopper.com/index.php?m=index&a=index&source=email&actionType=transportation&id='.$id;
        if ($_SERVER['HTTP_HOST'] != 'erp.gshopper.com') {
            $cb_url= 'http://erp.gshopper.stage.com/index.php?m=index&a=index&source=email&actionType=transportation&id=' . $id;
        }
      
      
        return $cb_url;
    }
    /**生成审批号
     * @param $prefix
     * @return string
     */
    public static function genReviewNo($prefix)
    {
        $review_no = 'WR-' . $prefix . date('YmdHis') . '-';
        /*$max_no = M('sys_reviews', 'tb_')->where(['created_at' => ['egt', date('Y-m-d')]])
            ->order('id desc')
            ->lock(true)
            ->getField('review_no');
        if ($max_no) {
            $review_no .= sprintf("%05d", (substr($max_no, strlen($max_no) - 5) + 1));
        } else {
            $review_no .= '00001';
        }*/
        do {
            $random = mt_rand(1000, 9999);
        } while (M('sys_reviews', 'tb_')->where(['review_no' => $review_no . $random])->getField('id'));
        return $review_no . $random;
    }
}