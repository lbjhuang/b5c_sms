<?php
/**
 * Class PromotionTagAction
 */

class PromotionTagAction extends BaseAction
{
    protected  $service ;

    public function __construct()
    {
        $this->service = new PromotionTagService();
    }

    public function index(){


    }
    public function getListData(){
        $request_data = DataModel::getDataNoBlankToArr();
        $data = $this->service->getListData($request_data);
        $this->ajaxSuccess($data);
    }

    public function getData(){
        $request_data = DataModel::getDataNoBlankToArr();
        $data = $this->service->getData($request_data);
        $this->ajaxSuccess($data);
    }

    /**
     *  列表数据
     */
    public function getList(){
        $request_data = DataModel::getDataNoBlankToArr();
        $data = $this->service->getList($request_data);
        $this->ajaxSuccess($data);
    }

    /**
     * 添加
     */
    public function create(){
        try{

            $request_data = DataModel::getDataNoBlankToArr();
            if (!$request_data){
                throw new Exception('请求参数不能为空');
            }
            $this->createValidate($request_data);
            $tag_info = $this->service->getFind(array('type_cd'=>$request_data['type_cd'],'tag_name'=>$request_data['tag_name'] ));
            if ($tag_info)  throw new Exception('同一标签类型下标签名称不可重复');
            $insert_data = array(
                'type_cd' => $request_data['type_cd'],
                'tag_name' => $request_data['tag_name'],
                'status' => 1,
                'create_by' => userName(),
                'create_at' => date("Y-m-d H:i:s"),
                'update_by' => userName(),
                'update_at' => date("Y-m-d H:i:s"),
            );
            $this->service->add($insert_data);
        }catch (Exception $exception){
            $this->ajaxError([],$exception->getMessage(),4000);
        }
        $this->ajaxSuccess();
    }

    /**
     *  编辑
     */
    public function edit()
    {
        try {
            $model = new Model();
            $model->startTrans();
            $request_data = DataModel::getDataNoBlankToArr();
            if (!$request_data) {
                throw new Exception('请求参数不能为空');
            }
            foreach ($request_data  as $datum){
                $this->editValidate($datum);
                $condition = array('id' => $datum['id']);
                $update_data = array(
                    'status' => $datum['status'],
                    'update_by' => userName(),
                    'update_at' => date("Y-m-d H:i:s"),
                );
                $res =  (new PromotionTagService($model))->update($condition, $update_data);
                if ($res === false){
                    throw new Exception('推广标签更新失败');
                }
            }
            $model->commit();
        } catch (Exception $exception) {
            $model->rollback();
            $this->ajaxError([], L($exception->getMessage()), 4000);
        }
        $this->ajaxSuccess();

    }

    /**
     * 参数验证
     * @param $request_data
     * @throws Exception
     */
    public function createValidate($datum) {
        $rules = array(
            'type_cd' => 'required|size:10',
            'tag_name' => 'required',

        );
        $custom_attributes = array(
            'type_cd'=>'推广标签类型',
            'tag_name'=>'推广标签名称',
        );
        $this->validate($rules, $datum, $custom_attributes);
    }

    /**
     * 参数验证
     * @param $request_data
     * @throws Exception
     */
    public function editValidate($datum) {
        $rules = array(
            'id' => 'required',
            'status' => 'required',
        );
        $custom_attributes = array(
            'type_cd'=>'推广标签ID',
            'tag_name'=>'推广标签状态',
        );
        $this->validate($rules, $datum, $custom_attributes);
    }

}