<?php
/**
 * User: huanzhu
 * Date: 18/3/15
 * Time: 18:06
 */
class PickApartAction extends BaseAction
{
    const  WAIT_CHECK_ORD = 'N001820700'; //待核单状态
	private $order_model = '';
    private $ms_ord = '';
    
    private $guds_model = '';
    private $pick_apart_model = '';
    private $ord_package = '';
    private static function get_condition_Data($param)
    {
        $data = Mainfunc::chooseParam($param);
        return $data;
    }

    public function _initialize()
    {
        if (!class_exists('ButtonAction')) {
            include_once APP_PATH . 'Lib/Action/Home/ButtonAction.class.php';
        }
    	header('Access-Control-Allow-Origin: *');
        header("Content-type: text/html; charset=utf-8");
        parent::_initialize();
    	$this->order_model = M("op_order","tb_");
        $this->guds_model = M("op_order_guds","tb_");
        $this->ms_ord = M("ms_ord","tb_");
        $this->pick_apart_model = new PickApartModel();
    }
    /**
     * 分拣列表
     */
    public function PickApartList()
    {
    	$this->display();
    }

    //分拣列表数据
    public function apart_list_data()
    {
    	$search_condition = self::get_condition_Data('data');
    	$search_condition = $search_condition['condition'];
    	$list_data = $this->pick_apart_model->lists($search_condition);
    	if(empty($list_data['res_data'])){
            $list_data['res_data'] = [];
        }
    	$this->jsonOut(array("code"=>200,"msg"=>"success","data"=>$list_data['res_data'],"total"=>$list_data['totals']));
    }

    //打印面单
    public function print_surface()
    {
    	$order_data = self::get_condition_Data('condition');
    	//$print_surface_data = $this->pick_apart_model->get_surface_data($order_data);
    	$print_surface_data = $this->pick_apart_model->get_surface_simple_data($order_data);
        empty($print_surface_data) or $this->assign('msg',$print_surface_data);
        $this->display("pickapartlist");
    }


    //获取文件流
    public function get_file_stream()
    {
    	//$filename = "files/kuaidi100/elec/pdf/KD1512028179689.pdf";
    	$filename = "test.pdf";
    	$direct_down_filename = ATTACHMENT_DIR_LOGISTIC.$filename;
    	$data = base64_encode($direct_down_filename);
    	$direct_down_filename = base64_decode($data); 
    }

    public function print_change_status($where_ms=array(),$type='')
    {
        $status['WHOLE_STATUS_CD'] = self::WAIT_CHECK_ORD;
        if (!$res_update = $this->ms_ord->where($where_ms)->save($status)) {
            $data = array("code"=>500,"msg"=>L("核单状态更新失败"));
            return $data;
         }
         if (empty($type)) {
            $this->jsonOut(array("code"=>200,"msg"=>"success","data"=>L("修改成功")));  
        } 
    }
    
    public function invoicepreview(){
        $this->display();   
    }

     
    //打印拣货单页面跳转
    
    public function printPartOrder()
    {
        $this->display('printPartOrder');
    }
}