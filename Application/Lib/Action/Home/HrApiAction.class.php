<?php



/**
 * Created by sublime.
 * User: b5m
 * Date: 17/8/7
 * Time: 17:55
 * By: huanzhu , huaxin
 */

class HrApiAction extends BaseAction
{
    private $HrModel;  //数据模型
    private $dept;  //部门信息
    private $emplId;//用户的emplId

    public function __construct()
    {
        parent::__construct();
        //初始化实例化模型
        $this->HrModel = new TbHrModel();
        $this->emplId = M('admin', 'bbm_')->where(['M_ID' => DataModel::userId()])->getField('empl_id');

    }


    /**
     * 晋升列表
     * @param string $value [description]
     */
    public function promotionList()
    {
        $data = $_REQUEST;
        $page['this_page'] = !empty($data['page']['this_page']) ? $data['page']['this_page'] : 1;
        $page['page_size'] = !empty($data['page']['page_size']) ? $data['page']['page_size'] : 20;
        $request = $data['data'];
        $where = $this->getPromotionListWhere($request);

        $res = $this->HrModel->getPromotionList($where, $page);

        $res['data'] = array_map(function ($value) {
            if (!$this->dept) {
                $this->dept = $this->HrModel->getLevelDept();
            }
            $tmp = $this->getParentTree($this->dept, $value['dept_id'], 1);
            if (count($tmp) != 1) {
                unset($tmp[0]);
                $value['DEPT_NM'] = implode('>', array_column($tmp, 'DEPT_NM'));
            }

            return $value;
        }, $res['data']);

        $this->ajaxSuccess($res);


    }

    /**
     * 发起晋升申请
     *
     */
    public function promotionAdd()
    {
        try {

            $data = $_REQUEST;
            if(empty($data['promotion_raise_type_cd'])){
                throw new Exception(L('晋升加薪单为必填'));
            }
            $raiseType = $data['promotion_raise_type_cd'];
            #策略模式
            $method = "verificationSubmitPromotionAdd{$raiseType}";
            if (!method_exists($this, $method)) throw new Exception(L('晋升加薪单类型有误'));
            #过滤参数
            $data = $this->$method($data);
           

            $res = DataModel::$success_return;
            $Model = new Model();
            $Model->startTrans();
            $promotionNumber = $this->createPromotionNumber();

            $deptId = $data['dept_id'];
            $emplId = $data['empl_id'];

            #候选人不能为ceo
            $ceoId = $this->HrModel->getCeo()['EMPL_ID'];
            if ($ceoId == $emplId) {
                throw new Exception(L('候选人不能为ceo'));
            }

            #查询是支持部门还是非支持部门  N002510200支持部门
            $dept = $this->HrModel->getDeptById($deptId)['TYPE'];

            


            #查询用户信息
            $userName = $this->HrModel->getUserByEmplId($emplId);
            $type = $dept == 'N002510200' ? 'N003640001' : 'N003640002';
          
            if($type == 'N003640002'){
                $earning = [
                    'gp_earning1',
                    'gp_earning2',
                    'odm_earning1',
                    'odm_earning1',
                    'gp_earning1_amount',
                    'gp_earning2_amount',
                    'odm_earning1_amount',
                    'odm_earning1_amount',
                ];
                foreach($earning as $value){
                    #兼容0

                    if (!isset($data[$value]) || (empty($data[$value]) && $data[$value] !== 0 && $data[$value] !== "0") || ctype_space(''.$data[$value])) {
                        throw new Exception(L('GP、ODM收入情况为必填'));
                    }
                }
                
                if (empty($data['personal_last_month_sale_amount']) || empty($data['personal_last_month_sale_earning'])) {
                    throw new Exception(L('个人前六个月销售与盈亏为必填'));
                }
                if (count($data['personal_last_month_sale_amount']) < 6 || count($data['personal_last_month_sale_earning']) < 6) {
                    throw new Exception(L('个人前六个月销售与盈亏为必填'));
                }
                foreach($data['personal_last_month_sale_amount'] as $value){
                    if ((empty($value) && $value !== "0" && $value !== 0) || ctype_space(''.$value)) {
                        throw new Exception(L('个人前六个月销售与盈亏为必填1'));
                    } 
                }
              
                foreach ($data['personal_last_month_sale_earning'] as $value) {
                    if ((empty($value) && $value !== "0" && $value !== 0) || ctype_space(''.$value)) {
                        throw new Exception(L('个人前六个月销售与盈亏为必填2'));
                    } 
                }
            }
            
         
           
            #分多种情况 得出status
            #查找该部门下以及其上级部门至ceo前是否有层级大于自己的领导 有则走部门领导审批流程
            $leader = $this->getLeaderByDeptId($deptId);
            
            $lastLeader = !empty($leader[count($leader) - 1]['EMPL_ID']) ? $leader[count($leader) - 1]['EMPL_ID'] : '';

            if ((count($leader) == 1 && $leader[0]['EMPL_ID'] == $emplId) || count($leader) == 0 || $lastLeader == $emplId) {
                #不需要部门负责人审批
                #无领导  直接找ceo或者  业务部门-ceo
                if ($type == 'N003640001' && $raiseType != 'N003750002') {
                    #支持部门 要过所有的业务总监
                    $status = 'N003630002';
                    $nextApprover = $this->getAllBusinessDirector();
                } else {
                    #过ceo即可
                    $status = 'N003630006';
                    $nextApprover = [$this->HrModel->getCeo()];
                }
                #查找ceo
                #部门父级id为0的为gshopper
            } else {
                #需要部门负责人审批
                $leaderEmplValue = array_column($leader, 'EMPL_ID');
                if (in_array($emplId, $leaderEmplValue)) {
                    //自己为领导  那么需要找自己以上的
                    foreach ($leader as $key => $value) {
                        if ($value['EMPL_ID'] == $emplId) {
                            $nextApprover = [$leader[$key + 1]];
                            break;
                        }
                    }
                } else {
                    //候选人为员工
                    $nextApprover = [$leader[0]];
                }
                #待部门领导审批
                $status = 'N003630001';
            }
            
            #本次审核人的erp name集合
            foreach ($nextApprover as $key => $value) {
                if ($value['EMPL_ID'] == $emplId) {
                    unset($nextApprover[$key]);
                }
            }
            $currentApprover = implode(',', array_column($nextApprover, 'ERP_ACT'));

            #写入主表
            $promotionDataInsert = [];
            $promotionDataInsert['empl_id'] = $emplId;
            $promotionDataInsert['promotion_no'] = $promotionNumber;
            $promotionDataInsert['rating'] = $data['rating'];
            if ($data['is_first_promote'] != 1) {
                $promotionDataInsert['last_promotion_time'] = date('Y-m-d H:i:s', strtotime($data['last_promotion_time']));
                $promotionDataInsert['last_promotion_before_job_id'] = !empty($data['last_promotion_before_job_id']) ? $data['last_promotion_before_job_id'] : 0;
                $promotionDataInsert['last_salary_amount'] = !empty($data['last_salary_amount']) ? $data['last_salary_amount'] : 0;


            }

            $promotionDataInsert['promotion_time'] = date('Y-m-d H:i:s', strtotime($data['promotion_time']));
            $promotionDataInsert['current_approver'] = $currentApprover;
            $promotionDataInsert['dept_id'] = $data['dept_id'];
            $promotionDataInsert['current_job_id'] = !empty($data['current_job_id']) ? $data['current_job_id'] : 0;
            $promotionDataInsert['promotion_job_id'] = !empty($data['promotion_job_id']) ? $data['promotion_job_id'] : 0;

            $promotionDataInsert['now_salary_amount'] = !empty($data['now_salary_amount']) ? $data['now_salary_amount'] : 0;
            $promotionDataInsert['raise_salary_amount'] = !empty($data['raise_salary_amount']) ? $data['raise_salary_amount'] : 0;

            $promotionDataInsert['currency_cd'] = !empty($data['currency_cd']) ? $data['currency_cd'] : '';
            $promotionDataInsert['promotion_raise_type_cd'] = !empty($data['promotion_raise_type_cd']) ? $data['promotion_raise_type_cd'] : '';

            $promotionDataInsert['status'] = $status;
            $promotionDataInsert['type'] = $type;
            $promotionDataInsert['is_first_promote'] = $data['is_first_promote'];
            $promotionDataInsert['created_at'] = date('Y-m-d H:i:s');
            $promotionDataInsert['created_by'] = userName();
            $promotionDataInsert['updated_at'] = date('Y-m-d H:i:s');
            $promotionDataInsert['updated_by'] = userName();


            $re1 = $promotionId = $this->HrModel->insertPromotion($promotionDataInsert, $Model);
            if (empty($re1)) {
                throw new Exception(L('写入主表数据异常，请稍后再试'));
            }
            $promotionDeatailInsert['promotion_id'] = $promotionId;
            $promotionDeatailInsert['work_content'] = $data['work_content'];
            $promotionDeatailInsert['reward_punishment_record'] = $data['reward_punishment_record'];
            $promotionDeatailInsert['direct_leadership_opinion'] = $data['direct_leadership_opinion'];

            $promotionDeatailInsert['gp_earning1'] = $data['gp_earning1'];
            $promotionDeatailInsert['gp_earning2'] = $data['gp_earning2'];
            $promotionDeatailInsert['odm_earning1'] = $data['odm_earning1'];
            $promotionDeatailInsert['odm_earning2'] = $data['odm_earning2'];

            $promotionDeatailInsert['gp_earning1_amount'] = $data['gp_earning1_amount'];
            $promotionDeatailInsert['gp_earning2_amount'] = $data['gp_earning2_amount'];
            $promotionDeatailInsert['odm_earning1_amount'] = $data['odm_earning1_amount'];
            $promotionDeatailInsert['odm_earning2_amount'] = $data['odm_earning2_amount'];

            $promotionDeatailInsert['personal_last_month_sale_amount'] = !empty($data['personal_last_month_sale_amount'])?json_encode($data['personal_last_month_sale_amount']):'[]';
            $promotionDeatailInsert['personal_last_month_sale_earning'] = !empty($data['personal_last_month_sale_earning']) ? json_encode($data['personal_last_month_sale_earning']) : '[]';



            

            $promotionDeatailInsert['created_at'] = date('Y-m-d H:i:s');
            $promotionDeatailInsert['created_by'] = userName();

            $re2 = $this->HrModel->insertPromotionDetail($promotionDeatailInsert, $Model);

            $insertApprover = [];
            #写入审批表

            foreach ($nextApprover as $value) {
                $arr = [];
                $arr['promotion_id'] = $promotionId;
                $arr['empl_id'] = $emplId;
                $arr['approver_id'] = $value['EMPL_ID'];
                $arr['result'] = 0;
                #这里对应主表的status
                $arr['type'] = $status;
                $arr['created_at'] = date('Y-m-d H:i:s');
                $arr['created_by'] = userName();
                $arr['updated_at'] = date('Y-m-d H:i:s');
                $arr['updated_by'] = userName();
                $insertApprover[] = $arr;
            }
            $re3 = $this->HrModel->insertApproverAll($insertApprover, $Model);

            if (!$re2 || !$re3) {
                throw new Exception(L('写入数据异常，请稍后再试'));
            }
            $log = [];
            $log['promotion_id'] = $promotionId;
            $log['empl_id'] = $emplId;
            $log['type'] = 1;

            $this->addApproverLog($Model, $log);
            $Model->commit();
            #通知wx
            if($status != 'N003630006'){
                $process = $raiseType == 'N003750002' ? '加薪流程' : '晋升流程';
                $this->sendWechatMsgByWid($nextApprover, '员工' . $userName . '的'.$process.'等待您审批。', $promotionId);
            }

            // throw new Exception(L('调拨ID异常'));
        } catch (\Exception $exception) {

            $res = $this->catchException($exception, $Model);
        }
        #解锁单号

        // RedisModel::unlock('PromotionNumber_' . $promotionNumber);
        $this->ajaxReturn($res);
    }

    /**
     * 同意/不同意晋升
     *
     */
    public function approver()
    {
        try {

            $approverType = $_REQUEST['approver_type']; #1为同意 2为不同意
            $promotionId = $_REQUEST['id'];

            if (empty($promotionId) || !in_array($approverType, [1, 2])) {
                throw new Exception(L('参数有误'));
            }
            $res = DataModel::$success_return;
            $Model = new Model();
            $Model->startTrans();

            $approver = M('hr_promotion_approver', 'tb_')->where(['approver_id' => $this->emplId, 'promotion_id' => $promotionId, 'result' => 0])->find();

            $promotion = M('hr_promotion', 'tb_')->where(['id' => $promotionId, 'status' => ['IN', ['N003630001', 'N003630002', 'N003630003']]])->find();
            
            if (empty($approver)) {
                throw new Exception(L('当前无权限审核'));
            }
            if (empty($promotion)) {
                throw new Exception(L('当前无可审核的晋升单'));
            }
            $raiseType = $promotion['promotion_raise_type_cd'];
            $status = $promotion['status'];
            $emplId = $promotion['empl_id'];
            $deptId = $promotion['dept_id'];
            $lastJobId = $promotion['last_promotion_before_job_id'];
            $nowJobId = $promotion['promotion_job_id'];
            $currency_cd = $promotion['currency_cd'];
            $currency_cd_val = M('ms_cmn_cd', 'tb_')->where(['CD'=> $currency_cd])->getField('CD_VAL');
            #查询用户信息
            $userName = $this->HrModel->getUserByEmplId($emplId);
            // $nextStatus = '';
            #下一个审核流转
            $nextPromotion = [];
            #更新主表
            $updatePromotion = [];
            #更新审核表
            $updateApprover = [];

            #同意
            if ($approverType == 1) {
                $method = "handerApprover{$status}";
                if (!method_exists($this, $method)) throw new Exception(L('晋升单状态有误'));
                #分多种情况

                $nextPromotion = $this->$method($promotion);

                $nextStatus = $nextPromotion['next_status'];
                $nextApprover = $nextPromotion['next_approver'];
                foreach ($nextApprover as $key => $value) {
                    if ($value['EMPL_ID'] == $emplId) {
                        unset($nextApprover[$key]);
                    }
                }
                $isApproverAdd = $nextPromotion['is_approver_add'];
                if ($isApproverAdd) {
                    $insertApprover = [];
                    #写入审批表
                    foreach ($nextApprover as $value) {
                        $arr = [];
                        $arr['promotion_id'] = $promotionId;
                        $arr['empl_id'] = $emplId;
                        $arr['approver_id'] = $value['EMPL_ID'];
                        $arr['result'] = 0;
                        #这里对应主表的status
                        $arr['type'] = $nextStatus;
                        $arr['created_at'] = date('Y-m-d H:i:s');
                        $arr['created_by'] = userName();
                        $arr['updated_at'] = date('Y-m-d H:i:s');
                        $arr['updated_by'] = userName();
                        $insertApprover[] = $arr;
                    }
                    $addApproverRe = $this->HrModel->insertApproverAll($insertApprover, $Model);
                    if (!$addApproverRe) {
                        throw new Exception(L('处理失败，请稍后重试'));
                    }
                }
                if ($nextStatus != 'N003630004') {
                    $updatePromotion['current_approver'] = implode(',', array_column($nextApprover, 'ERP_ACT'));
                }else{
                    $updatePromotion['current_approver'] = '';
                }
                $updatePromotion['status'] = $nextStatus;

                $updatePromotion['updated_at'] = date('Y-m-d H:i:s');
                $updatePromotion['updated_by'] = userName();
                
                

            } else {
                #不同意逻辑较简单 代码量较少 就不使用策略模式了
                if (in_array($status, ['N003630001', 'N003630003'])) {
                    $updatePromotion['status'] = 'N003630005';
                    $updatePromotion['updated_at'] = date('Y-m-d H:i:s');
                    $updatePromotion['updated_by'] = userName();
                } else {
                    #判断是否不同意的>百分之50  =50是不管用的  此时如果有50%同意的还是可以过

                    $noAgreeCount = M('hr_promotion_approver', 'tb_')->where(['promotion_id' => $promotionId, 'result' => 2, 'type' => $status])->count();
                    $allCount = M('hr_promotion_approver', 'tb_')->where(['promotion_id' => $promotionId, 'type' => $status])->count();
                    if (($noAgreeCount + 1) / $allCount > 0.5) {
                        #不同意超过半数了 直接失败
                        $updatePromotion['status'] = 'N003630005';
                        $updatePromotion['updated_at'] = date('Y-m-d H:i:s');
                        $updatePromotion['updated_by'] = userName();
                    } else {
                        $updatePromotion['status'] = $status;
                        $updatePromotion['updated_at'] = date('Y-m-d H:i:s');
                        $updatePromotion['updated_by'] = userName();
                        #已经审核了一个人 去除掉一个当前审核人


                    }
                    $nextApprover = [];
                    $currentApprover = explode(',', $promotion['current_approver']);
                    if (count($currentApprover) != 1) {
                        foreach ($currentApprover as $key => $value) {
                            if ($value != userName()) {
                                array_push($nextApprover, ['ERP_ACT' => $value]);
                            }
                        }

                        $updatePromotion['current_approver'] = implode(',', array_column($nextApprover, 'ERP_ACT'));
                    }

                }

                #处理审核失败后  当前审批人
                if( $updatePromotion['status'] == 'N003630005'){
                    $updatePromotion['current_approver'] = '';
                }
            }


            #处理审核表数据
            $updateApprover['result'] = $approverType;
            $updateApprover['updated_at'] = date('Y-m-d H:i:s');
            $updateApprover['updated_by'] = userName();
            $updateApproverRe = $this->HrModel->updateApprover($Model, $approver['id'], $updateApprover);

            #更新主表
            
            $updatePromotionRe = $Model->table('tb_hr_promotion')->where(['id' => $promotionId])->save($updatePromotion);
            if (empty($updateApproverRe) || empty($updatePromotionRe)) {
                throw new Exception(L('更新失败，请稍后再试'));
            }
            #更新候选人
            if ($updatePromotion['status'] == 'N003630004' && $raiseType == 'N003750001') {
                $updateUser['empl_id'] = $emplId;
                $updateUser['job_id'] = $promotion['promotion_job_id'];
                #job表无需在事务内 防止锁表
                $tmpJob = M('hr_jobs', 'tb_')->where(['ID' => $updateUser['job_id']])->find();
                $updateUser['job_name'] = $tmpJob['CD_VAL'];
                $updateUser['job_name_en'] = $tmpJob['ETC'];


                
                $updateCardRe = $this->HrModel->updateTbCardByEmplId($Model, $updateUser);
                $updateEmplRe = $this->HrModel->updateTbEmplByEmplId($Model, $updateUser);
               
                if (empty($updateCardRe) || empty($updateEmplRe)) {
                    throw new Exception(L('更新失败，请稍后再试'));
                }
            }
            #日志
            $log = [];
            $log['promotion_id'] = $promotionId;
            $log['empl_id'] = $emplId;

            if ($status == 'N003630001') {
                $log['type'] = 2;
            }
            if ($status == 'N003630002') {
                $log['type'] = 3;
            }
            if ($status == 'N003630003') {
                $log['type'] = 4;
            }
            $log['result'] = $approverType == 1 ? 1 : 0;

            $this->addApproverLog($Model, $log);
            #将写表操作全部操作完
            $Model->commit();
            #微信消息通知调用接口  放在commit之后
            #分多种情况
            if ($updatePromotion['status'] == 'N003630004') {

                #晋升单状态终结了
                #晋升成功，通知本人及所有领导
                $sendMsgLeader = $this->getLeaderByDeptId($deptId);
                $sendMsgLeader = array_map(function ($value) use ($emplId) {
                    if ($value['EMPL_ID'] != $emplId) {
                        return $value;
                    }
                }, $sendMsgLeader);

                $jobs = M('hr_jobs', 'tb_')->where(['ID' => ["IN", [$lastJobId, $nowJobId]]])->select();

                $jobs = array_column($jobs, null, 'ID');
                $content1 = '';
                $content2 = '';
                #读取币种 和格式化千位符

                if($raiseType == 'N003750002'){
                    $content1 = '恭喜，您部门的员工' . $userName . '加薪成功，薪资由' . $currency_cd_val. number_format($promotion['now_salary_amount']) . '增加为' . $currency_cd_val .number_format($promotion['raise_salary_amount']) . '。';
                    $content2 = '恭喜您，加薪成功，薪资由' . $currency_cd_val . number_format($promotion['now_salary_amount']) . '增加为' . $currency_cd_val . number_format($promotion['raise_salary_amount']) . '。';
                }else{
                    $content1 = '恭喜，您部门的员工' . $userName . '晋升成功，职位由' . $jobs[$lastJobId]['CD_VAL'] . '晋升为' . $jobs[$nowJobId]['CD_VAL'] . '。';
                    $content2 = '恭喜您，晋升成功，职位由' . $jobs[$lastJobId]['CD_VAL'] . '晋升为' . $jobs[$nowJobId]['CD_VAL'] . '。';
                }
                $this->sendWechatMsgByWid($sendMsgLeader, $content1, $promotionId);
                #通知本人
                $this->sendWechatMsgByWid([['EMPL_ID' => $emplId]], $content2 , $promotionId);
            }

            if ($updatePromotion['status'] == 'N003630005') {
                #晋升单状态终结了
                #晋升失败了
                $sendMsgLeader = M('hr_promotion_approver', 'tb_')->where(['promotion_id' => $promotionId, 'result' => 1, 'type' => 'N003630001'])->field('approver_id as EMPL_ID')->select();
                $sendMsgLeader = array_map(function ($value) use ($emplId) {
                    if ($value['EMPL_ID'] != $emplId) {
                        return $value;
                    }
                }, $sendMsgLeader);
                $process = $raiseType == 'N003750002' ? '加薪' : '晋升';
                $this->sendWechatMsgByWid($sendMsgLeader, '真遗憾，您部门的员工' . $userName . $process.'失败。', $promotionId);

            }

            if (!in_array($updatePromotion['status'], ['N003630005', 'N003630004']) && $status != 'N003630003') {
                if ($status == 'N003630001' && $nextStatus != 'N003630006') {
                    #通知业务部门
                    $process = $raiseType == 'N003750002' ? '加薪流程' : '晋升流程';
                    $this->sendWechatMsgByWid($nextApprover, '员工' . $userName . '的'.$process.'等待您审批。', $promotionId);
                }
            }


        } catch (\Exception $exception) {
            $res = $this->catchException($exception, $Model);
        }
        $this->ajaxReturn($res);

    }

    #部门领导审核 同意
    private function handerApproverN003630001($promotion)
    {

        $deptId = $promotion['dept_id'];
        $status = $promotion['status'];
        $type = $promotion['type'];
        $raiseType = $promotion['promotion_raise_type_cd'];
        $leader = $this->getLeaderByDeptId($deptId);
        $nextLeader = [];
        $isApproverAdd = 0;
        foreach ($leader as $key => $value) {
            
            if ($value['EMPL_ID'] == $this->emplId && $leader[$key + 1] && $leader[$key + 1]['EMPL_ID'] != $this->emplId) {
                //有下一个领导可以审核
                $nextLeader = [$leader[$key + 1]];
                break;
            }
        }
       
        if (empty($nextLeader)) {
            #无领导可以审核  如果是支持部门 过业务部门
            if ($type == 'N003640001'&& $raiseType != 'N003750002') {
                #支持部门 要过所有的业务总监
                $nextStatus = 'N003630002';
                $nextApprover = $this->getAllBusinessDirector();
                $isApproverAdd = 1;
            } else {
                #过ceo即可
                $nextStatus = 'N003630006';
                $nextApprover = [$this->HrModel->getCeo()];
                $isApproverAdd = 1;
            }
        } else {
            #有下个业务领导就还是继续往下走 status不变 只是换了一个当前审批人
            $nextStatus = $status;
            $nextApprover = $nextLeader;
            $isApproverAdd = 1;
        }
        return [
            'next_status' => $nextStatus,
            'next_approver' => $nextApprover,
            'is_approver_add' => $isApproverAdd
        ];
    }

    #业务部门总监审核 同意
    private function handerApproverN003630002($promotion)
    {
        $isApproverAdd = 0;
        $status = $promotion['status'];
        $currentApprover = explode(',', $promotion['current_approver']);
        $promotionId = $promotion['id'];


        #只要支持率大于百分之50就可以给进入下一个带推送ceo审批了
        $agreeCount = M('hr_promotion_approver', 'tb_')->where(['promotion_id' => $promotionId, 'result' => 1, 'type' => $status])->count();
        $allCount = M('hr_promotion_approver', 'tb_')->where(['promotion_id' => $promotionId, 'type' => $status])->count();


        if (($agreeCount + 1) / $allCount >= 0.5) {
            #进入推送到ceo审核节点【即CEO N003630003之前一个节点N003630006】
            $nextStatus = 'N003630006';
            $nextApprover = [$this->HrModel->getCeo()];
            $isApproverAdd = 1;
        } else {
            $nextStatus = $status;
            #已经审核了一个人 去除掉一个当前审核人
            $nextApprover = [];
            foreach ($currentApprover as $key => $value) {
                if ($value != userName()) {
                    array_push($nextApprover, ['ERP_ACT' => $value]);
                }
            }
        }
        return [
            'next_status' => $nextStatus,
            'next_approver' => $nextApprover,
            'is_approver_add' => $isApproverAdd
        ];
    }

    #ceo审核 同意
    private function handerApproverN003630003($promotion)
    {
        return [
            'next_status' => 'N003630004',
            'next_approver' => [],
            'is_approver_add' => 0
        ];
    }

    /**
     * 晋升详情
     * @param string $value [description]
     */
    public function promotionDetail()
    {
        try {
            $res = DataModel::$success_return;
            $data = $_REQUEST;
            if (empty($data['id'])) throw new Exception(L('id不能为空'));
            #晋升信息
            $promotion = $this->HrModel->getPromotionDetail($data['id']);
            $deptId = $promotion['dept_id'];
            $emplId = $promotion['empl_id'];
            if (!$this->dept) {
                $this->dept = $this->HrModel->getLevelDept();
            }

            $tmp = $this->getParentTree($this->dept, $deptId, 1);
            unset($tmp[0]);
            $promotion['DEPT_NM'] = implode('>', array_column($tmp, 'DEPT_NM'));
            $promotion['is_first_promote'] = !empty($promotion['is_first_promote']) ? $promotion['is_first_promote'] : 0;
            #基础信息
            $card = $this->HrModel->getPromotionCardDetail($emplId);
            #计算司龄
            $perJobDate = $card['PER_JOB_DATE'];
            if ($perJobDate == '0000-00-00 00:00:00') {
                $perJobDate = '';
                $card['COMPANY_AGE'] = '';
            }
            if (!empty($perJobDate)) {
                $year = (date('Y') - date('Y', strtotime($perJobDate))) ? (date('Y') - date('Y', strtotime($perJobDate))) : 0;
                $month = (date('m') - date('m', strtotime($perJobDate))) ? (date('m') - date('m', strtotime($perJobDate))) : 0;
                $card['COMPANY_AGE'] = $year * 12 + $month;
                // if ($card['COMPANY_AGE'] == 0) {
                //     $card['COMPANY_AGE'] = '未满一个月';
                // } else {
                //     $card['COMPANY_AGE'] = $card['COMPANY_AGE'] . '月';
                // }
            }
            #是否有权限看到同意/不同意按钮
            $is_show_button = 0;
            $currentApprover = explode(',', $promotion['current_approver']);
            if (in_array(userName(), $currentApprover)) {
                $is_show_button = 1;
            }
            #格式化时间
            $promotion['last_promotion_time'] = date('Y-m',strtotime($promotion['last_promotion_time']));
            $promotion['promotion_month'] = (int)date('m', strtotime($promotion['promotion_time']));
            $card['PER_JOB_DATE'] = date('Y-m-d',strtotime($card['PER_JOB_DATE']));
            #业务和技术分开展示
            $earning = [];
            $score = [];
          
            if($promotion['type'] == 'N003640002'){
               
                $leaderAll = $this->getLeaderByDeptId($deptId);
                #得出所有领导  按照顺序
                $promotionMonth = date('m', strtotime($promotion['promotion_time']));
                $promotionYear = date('Y', strtotime($promotion['promotion_time']));

                $earningKey = [];
                
                if ($promotionMonth == '01') $earningKey = range(7, 12);
                if ($promotionMonth == '07') $earningKey = range(1, 6);
                $requestPromotionYear  = $promotionMonth == '01' ? $promotionYear - 1 : $promotionYear;
                
                #获取java接口
                #不取接口  取数据库
                // $requestDate = [];
                // $requestData = [];
                // $requestHeader = [];
                // foreach($earningKey as $key=>$value){
                //     if($value < 10){
                //         $requestDate[$key] = $requestPromotionYear . '0' . $value;
                //     }else{
                //         $requestDate[$key] = $requestPromotionYear .  $value;
                //     }

                // }

                // $requestDate = implode(',',$requestDate);

                // $requestData['date'] = $requestDate;


                // $requestHeader = [
                //     "erp-cookie: PHPSESSID=".session_id().';',
                //     "erp-req: true",
                // ];
                // $leader = '';
                // $insUrl = INSIGHT . '/insight-backend/profitLoss/profitLossByLeader';
                // Logs((['url' => $insUrl, 'data' => $requestData, 'header' => $requestHeader]), __FUNCTION__ . '----send data', 'promotionGetJavaData');
                // $scoreData = HttpTool::Curl_post_json_header($insUrl, $requestData, $requestHeader);
                // Logs((['url' => $insUrl, 'data' => $requestData, 'header' => $requestHeader, 'res' => $scoreData]), __FUNCTION__ . '----res', 'promotionGetJavaData');

                // if($scoreData){
                //     $scoreData = json_decode($scoreData, 1)['datas'];
                //     $scoreDataKey = array_keys($scoreData);
                //     $scoreDataKey = array_map(function($value){
                //         if(!empty(explode('-', $value)[1])){
                //             return (explode('-', $value)[1]);
                //         }
                //     }, $scoreDataKey);
                //     $scoreDataKey = array_unique($scoreDataKey);
                //     $scoreDataKey = array_filter($scoreDataKey);

                //     foreach($leaderAll as $leaderValue){

                //         $tmpLeader = '';
                //         $tmpLeaderAllName = '';


                //         if(!empty(explode('.', $leaderValue['ERP_ACT'])[0])){
                //             $tmpLeader = explode('.', $leaderValue['ERP_ACT'])[0];
                //             $tmpLeaderAllName = $leaderValue['ERP_ACT'];
                //         }
                //         if (!empty(explode(' ', $leaderValue['ERP_ACT'])[0])) {
                //             $tmpLeader = explode(' ', $leaderValue['ERP_ACT'])[0];
                //             $tmpLeaderAllName = $leaderValue['ERP_ACT'];
                //         }
                //         if(in_array($tmpLeader, $scoreDataKey)){
                //             $leader = $tmpLeader;
                //             break;
                //         }
                //     }

                // }else{
                //     $scoreData = [];
                // }
                #个人销售额与盈亏
                $personalLastMonthSaleAmount = !empty($promotion['personal_last_month_sale_amount']) ? json_decode($promotion['personal_last_month_sale_amount'],1) : [];
                $personalLastMonthSaleEarning = !empty($promotion['personal_last_month_sale_earning']) ? json_decode($promotion['personal_last_month_sale_earning'],1) : [];
                $tmpAmount = 0;
                $tmpEarning = 0;
                foreach ($earningKey as $key => $value) {
                    // $tmpScoreValue = $value;
                    // if ($value < 10) $tmpScoreValue = '0'.$value;
                  
                    $apiAmount = $personalLastMonthSaleAmount[$key]; #等待接入java接口获取 改为读数据库
                    $apiEarning = $personalLastMonthSaleEarning[$key]; 


                    $earning['list'][] = [
                        'title' => $value . '月',
                        'amount' => !empty($apiAmount) ? round($apiAmount) : 0,
                        'earning' => !empty($apiEarning) ? round($apiEarning) : 0
                    ];
                    $tmpAmount += round($apiAmount);
                    $tmpEarning += round($apiEarning);
                    if ($key == 2 || $key == 5) {

                        if ($key == 2 && $promotionMonth == '07') $quarter = 1;
                        if ($key == 5 && $promotionMonth == '07') $quarter = 2;
                        if ($key == 2 && $promotionMonth == '01') $quarter = 3;
                        if ($key == 5 && $promotionMonth == '01') $quarter = 4;


                        $earning['list'][] = [
                            'title' => 'Q' . $quarter,
                            'amount' => $tmpAmount,
                            'earning' => $tmpEarning
                        ];
                        $tmpAmount = 0;
                        $tmpEarning = 0;
                    }
                }
               
                // $earning['leader'] = !empty($tmpLeaderAllName)? $tmpLeaderAllName:'';
                $earning['list'] = array_map(function($value){
                    $value['amount'] = number_format($value['amount'],0);
                    $value['earning'] = number_format($value['earning'],0);
                   
                    return $value;
                }, $earning['list']);

                
            }else{
                
                #请求参数
                $scoreYear1 = 0;
                $scoreYear2 = 0;
                $scoreQuarter1 = 0;
                $scoreQuarter2 = 0;
                $promotionMonth = date('m', strtotime($promotion['promotion_time']));
                $promotionYear = date('Y', strtotime($promotion['promotion_time']));
                if($promotionMonth == '01'){
                    #1月份
                    $scoreQuarter1 = 3;
                    $scoreQuarter2 = 4;
                    $scoreYear1 = $scoreYear2 = $promotionYear - 1;
                    
                }else{
                    $scoreQuarter1 = 1;
                    $scoreQuarter2 = 2;
                    $scoreYear1 = $promotionYear;
                    $scoreYear2 = $promotionYear;
                }
                #得出候选人最高级部门(gshopper之下)
                if (!$this->dept) {
                    $this->dept = $this->HrModel->getLevelDept();
                }
                $allDept = $this->getParentTree($this->dept,$deptId,1);
                $deptHight = !empty($allDept[1]['DEPT_NM'])? $allDept[1]['DEPT_NM']:'';
                // $this->ajaxReturn($deptHight);
                #请求IT接口
                #无测试接口 直接写到配置文件
                $baseUrl = IT_API. '/kpi/query.php';
                $url1 = $baseUrl. "?year=$scoreYear1&quar=$scoreQuarter1";
               
                Logs((['url' => $url1]), __FUNCTION__ . '----send data', 'promotionGetITData');
                $score1 = HttpTool::curlGet($url1, 10, 10);
                Logs((['url' => $url1,'res'=> $score1]), __FUNCTION__ . '----res', 'promotionGetITData');
                $score1 = json_decode($score1,1);
                $scoreDetail1 = 0;
                
               
                if (!empty($score1) && count($score1) > 0) {
                    #处理
                   
                    $score1 = array_column($score1, null, 'name');
                    
                    if ($deptHight == 'Strategy' && !empty($score1['Strategy'])) {
                        $scoreDetail1 = $score1['Strategy']['score'];
                    } elseif (in_array($deptHight, ['R&D'])) {
                        $scoreDetail1 = round(($score1['ERP']['score'] + $score1['Insight']['score'] + $score1['Evolution Team']['score']) / 3,0);
                    } else {
                        $scoreDetail1 = !empty($score1[$deptHight]['score']) ? $score1[$deptHight]['score'] : 0;
                    }
                   
                    if ($deptHight == 'Strategy' && empty($score1['Strategy'])) {
                        #取所有部门均值
                        $score1Value = array_column($score1, 'score');
                        $scoreDetail1 = round(array_sum($score1Value) / count($score1Value), 0);
                    }
                   

                }
                $score['list'][] = [
                    'title'=>'Q'. $scoreQuarter1.'业务评分',
                    'value'=> $scoreDetail1
                ];
                $url2 = $baseUrl . "?year=$scoreYear2&quar=$scoreQuarter2";
                
                Logs((['url' => $url2]), __FUNCTION__ . '----send data', 'promotionGetITData');
                $score2 = HttpTool::curlGet($url2, 10, 10);
                Logs((['url' => $url2, 'res' => $score2]), __FUNCTION__ . '----res', 'promotionGetITData');
                $score2 = json_decode($score2, 1);
                $scoreDetail2 = 0;
                if (!empty($score2) && count($score2) > 0) {
                    #处理
                    $score2 = array_column($score2, null, 'name');
                    if ($deptHight == 'Strategy' && !empty($score2['Strategy'])) {
                        $scoreDetail2 = $score2['Strategy']['score'];
                    } elseif (in_array($deptHight, ['R&D'])) {
                
                        $scoreDetail2 = round(($score2['ERP']['score'] + $score2['Insight']['score'] + $score2['Evolution Team']['score']) / 3, 0);
                    } else {
                        $scoreDetail2 = !empty($score2[$deptHight]['score']) ? $score2[$deptHight]['score'] : 0;
                    }
                    if ($deptHight == 'Strategy' && empty($score2['Strategy'])) {
                        #取所有部门均值
                        $score2Value = array_column($score2, 'score');
                        $scoreDetail2 = round(array_sum($score2Value) / count($score2Value), 0);
                    }

                }
                
                $score['list'][] = [
                    'title' => 'Q' . $scoreQuarter2 . '业务评分',
                    'value' => $scoreDetail2
                ];

                if((int)$promotionMonth == 7){
                    #反转位置
                    $score['list'] = array_reverse($score['list']);
                }
                $score['dept'] = $deptHight;


            }
            
            $approver = M('hr_promotion_approver', 'tb_')->where(['promotion_id' => $data['id'], 'approver_id' => $this->emplId, 'result' => 0])->find();
            if (empty($approver)) {
                $is_show_button = 0;
            }
            if($promotion['status'] == 'N003630006'){
                $is_show_button = 0;
            }

            $button = [
                'is_show' => $is_show_button
            ];
            $res['data'] = [
                'promotion' => $promotion,
                'card' => $card,
                'button' => $button,
                'earning' => $earning,
                'score' => $score
            ];

        } catch (\Exception $exception) {
            $res = $this->catchException($exception);
        }


        $this->ajaxReturn($res);
    }

    /**
     * 候选人列表搜索 -- 过滤离职状态
     * @param string $value [description]
     */
    public function searchUser()
    {
        $data = $_REQUEST;
        $name = !empty($data['name']) ? $data['name'] : '';
        #晋升信息
        $user = $this->HrModel->getUser($name);
        $this->ajaxSuccess($user);
    }
    #验证晋升加薪单为晋升时
    private function verificationSubmitPromotionAddN003750001($data)
    {


        $rules = [
            'empl_id' => 'required|numeric',
            'dept_id' => 'required|numeric',
            'work_content' => 'required',
            // 'rating' => 'required|numeric',
            'current_job_id' => 'required|numeric',
            'promotion_job_id' => 'required|numeric',
            'promotion_time' => 'required',
            'reward_punishment_record' => 'required',
            'direct_leadership_opinion' => 'required',
        ];


        $custom_attributes = [
            'empl_id' => '候选人',
            'dept_id' => '归属部门',
            'work_content' => '主要工作内容',
            'rating' => '努力程度评级',
            'last_promotion_time' => '上次晋升日期',
            'last_promotion_before_job_id' => '上次晋升前职位',
            'current_job_id' => '当前职位',
            'promotion_job_id' => '晋升后职位',
            'promotion_time' => '晋升日期',
            'reward_punishment_record' => '奖惩记录',
            'direct_leadership_opinion' => '直属领导意见',
        ];
        #validate 避免验证被跳过
        $data['1'] = 1;

        $this->validate($rules, $data, $custom_attributes);
        foreach ($rules as $key => $value) {
            if (empty($data[$key])) {
                throw new Exception(L($custom_attributes[$key] . '不能为0或者空'));
            }
        }
        #单独验证

        if (!isset($data['is_first_promote']) || !in_array($data['is_first_promote'], [0, 1])) {
            throw new Exception(L('参数is_first_promote有误'));
        }
        if ($data['is_first_promote'] != 1 && (empty($data['last_promotion_time']) || empty($data['last_promotion_before_job_id']))) {
            #不为首次  校验字段
            throw new Exception(L('上次晋升信息为必传'));
        }
        #对数据进行base64转化
        $data['work_content'] = base64_decode($data['work_content']);
        $data['reward_punishment_record'] = base64_decode($data['reward_punishment_record']);
        $data['direct_leadership_opinion'] = base64_decode($data['direct_leadership_opinion']);
        return $data;
    }
    #验证晋升加薪单为加薪时
    private function verificationSubmitPromotionAddN003750002($data)
    {


        $rules = [
            'empl_id' => 'required|numeric',
            'dept_id' => 'required|numeric',
            'work_content' => 'required',
            // 'rating' => 'required|numeric',
            //            'last_promotion_time' => 'required',    #首次晋升  参数不为必须
            //            'last_promotion_before_job_id' => 'required|numeric',
            // 'current_job_id' => 'required|numeric',
            // 'promotion_job_id' => 'required|numeric',
            'now_salary_amount' => 'required|numeric',
            'raise_salary_amount' => 'required|numeric',
            'promotion_time' => 'required',
            'reward_punishment_record' => 'required',
            'direct_leadership_opinion' => 'required',
            'currency_cd' => 'required',
        ];


        $custom_attributes = [
            'empl_id' => '候选人',
            'dept_id' => '归属部门',
            'work_content' => '主要工作内容',
            'rating' => '努力程度评级',
            'last_promotion_time' => '上次晋升日期',
            'last_promotion_before_job_id' => '上次晋升前职位',
            'current_job_id' => '当前职位',
            'promotion_job_id' => '晋升后职位',
            'promotion_time' => '晋升日期',
            'reward_punishment_record' => '奖惩记录',
            'direct_leadership_opinion' => '直属领导意见',
            'now_salary_amount' => '当前月薪',
            'raise_salary_amount' => '加薪后月薪',
            'currency_cd' => '月薪币种'

        ];
        #validate 避免验证被跳过
        $data['1'] = 1;

        $this->validate($rules, $data, $custom_attributes);
        foreach ($rules as $key => $value) {
            if (empty($data[$key])) {
                throw new Exception(L($custom_attributes[$key] . '不能为0或者空'));
            }
        }
        #单独验证
        if (!isset($data['is_first_promote']) || !in_array($data['is_first_promote'], [0, 1])) {
            throw new Exception(L('参数is_first_promote有误'));
        }
        if ($data['is_first_promote'] != 1 && (empty($data['last_promotion_time']) || empty($data['last_salary_amount']))) {
            #不为首次  校验字段
            throw new Exception(L('上次加薪信息为必传'));
        }
        if($data['is_first_promote'] != 1 && $data['last_salary_amount'] >= $data['now_salary_amount']){
            throw new Exception(L('加薪后薪资必须大于加薪前薪资'));
        }
        if ($data['now_salary_amount'] >= $data['raise_salary_amount']) {
            throw new Exception(L('加薪后薪资必须大于加薪前薪资'));
        }
         #对数据进行base64转化
        $data['work_content'] = base64_decode($data['work_content']);
        $data['reward_punishment_record'] = base64_decode($data['reward_punishment_record']);
        $data['direct_leadership_opinion'] = base64_decode($data['direct_leadership_opinion']);
        return $data;
    }
    #生成晋升单号
    private function createPromotionNumber()
    {

        $startNumber = M('hr_promotion', 'tb_')->order('id desc')->where(['created_at' => ['egt', date('Y-m-d')]])->getField('promotion_no');

        $startNumber = !empty($startNumber) ? str_pad((int)substr($startNumber, -4) + 1, 4, "0", STR_PAD_LEFT) : '0001';

        $promotionNumber = 'JSD' . date('Ymd') . $startNumber;

        #暂不使用  测试环境太卡
        // for ($i = 0; $i < 3; $i++) {
        //     if (RedisModel::lock('PromotionNumber_' . $promotionNumber, 10)) {
        //         break;
        //     }
        //     $startNumber = $startNumber + 1;
        //     $promotionNumber  = 'JSD' . date('Ymd') . $startNumber;
        // }
        return $promotionNumber;
    }

    /**
     *
     * 根据EMPL_id 获取该用户所有部门的层级  以及该部门下的职位
     *
     */
    public function getDeptByUser()
    {



        $data = [];
        $id = $_REQUEST['empl_id'];
        if (empty($id)) $this->ajaxError('empl_id不能为空');
        $dept = $this->HrModel->getDeptByUser($id);
        
        $dept = $this->getDeptLevel($dept);
       
        #N002510200支持部门
         $dept  = array_map(function($value){
            $value['TYPE'] = $value['TYPE'] == 'N002510200' ? 'N003640001' : 'N003640002';
            return $value;
         }, $dept);
        $job = $this->HrModel->getJobById($id);
        $data['dept'] = $dept;
        $data['job'] = $job;
        $this->ajaxSuccess($data);
    }


    public function ajaxSuccess($data = [], $msg = 'success', $code = 200)
    {
        if (empty($code) && $this->success_code) {
            $code = $this->success_code;
        }
        $this->ajaxReturn(['data' => $data, 'msg' => L($msg), 'code' => $code]);
    }

    public function ajaxError($msg = 'error', $code = -1)
    {
        if (!$code) {
            $code = -1;
        }
        $this->ajaxReturn(['msg' => L($msg), 'code' => $code]);
    }

    #根据部门Id寻找层级
    private function getDeptLevel($data)
    {
        if (!$this->dept) {
            $this->dept = $this->HrModel->getLevelDept();
        }
        
        foreach ($data as $key => $v) {


            $tmpDept = $this->getParentTree($this->dept, $v['ID'], 1);
            if ($tmpDept[0] && $tmpDept[0]['PAR_DEPT_ID'] == 0) {

                $data[$key]['dept_name_parent'] = implode('>', array_column($tmpDept, 'DEPT_NM'));
                unset($tmpDept);
            } else {
                unset($data[$key]);
            }


        }
        return $data;
    }

    #迭代法 找出所有父级
    private function getParentTree($arr, $id, $sort = 0)
    {
        $par_arr = array();

        while ($id != 0) {
            $tmp = $id;
            foreach ($arr as $v) {
                if ($v['ID'] == $id) {
                    $par_arr[] = $v;
                    $id = $v['PAR_DEPT_ID'];
                    break;
                }
            }
            if ($tmp == $id) {
                #找不到父级 终止
                $id = 0;
            }
        }
        if ($sort) $par_arr = array_reverse($par_arr);
        return $par_arr;
    }
    #迭代法 找出所有子部门
    private function getChildTree($list, $root = 0 )
    {
        $tree     = array();
        $packData = array();
        foreach ($list as $data) {
            $tmp = [
                'value'=>$data['ID'],
                'label'=>$data['DEPT_NM'],
                'PAR_DEPT_ID' => $data['PAR_DEPT_ID'],
            ];
            $packData[$data['ID']] = $tmp;
        }
        foreach ($packData as $key => $val) {
            if ($val['PAR_DEPT_ID'] == $root) {
                //代表跟节点, 重点一
                $tree[] = &$packData[$key];
            } else {
                //找到其父类,重点二
                $packData[$val['PAR_DEPT_ID']]['children'][] = &$packData[$key];
            }
        }
        return $tree;
    }
    #根据部门id找出所有子部门  无层级要求
    public function getChildArr($arr, $pid = 0){
        $task = array($pid); //任务表
        $tree = array(); //地区表(存放找到的数据)
        // 如果任务表为不空就执行循环
        while (!empty($task)) {
            $flag = false; //定义一个参数为假，用于判断有没有找到子栏目
            foreach ($arr as $key => $value) {
                // 如果$arr的值得pid = 参数传来的pid就执行(初始也就是0)所以先会输出湖北和北京
                if ($value['PAR_DEPT_ID'] == $pid) {
                    
                    $tree[] = $value; //将找到的值写入到 地区表
                    array_push($task, $value['ID']); //把找到的地区id加入任务栈，用于记录
                    $pid = $value['ID']; //在把该地区的id赋给 $pid 便于下次循环
                    unset($arr[$key]); //把找到单元unset掉
                    $flag = true; //说明找到了子栏目
                }
            }
            //当循环到底的时候(也就是没有id对应的pid时)循环就不为真，便将执行上面定义的那个假参数
            if ($flag == false) {
                array_pop($task); //会将任务表中的id从后到前删除
                $pid = end($task); //将任务表的内部指针，移到最后一个单元，并将他赋给pid
            }
            // print_r($task);打开可以看到任务表的运行过程
        }
       
        return $tree; //返回地区表
    }
    #根据部门Id找出所有层级部门的负责人
    private function getLeaderByDeptId($deptId)
    {
        if (!$this->dept) {
            $this->dept = $this->HrModel->getLevelDept();
        }


        $allDept = $this->getParentTree($this->dept, $deptId, 1);

        #去除gshopper
        unset($allDept[0]);
        $allDept = array_reverse($allDept);

        #找出自己所属部门 以及之上的所有领导
        $leader = [];
        foreach ($allDept as $value) {
            $tmp = $this->HrModel->summaryPeopleInDept($value['ID']);


            $tmp = array_reverse($tmp);

            $leader = array_merge((array)$leader, (array)$tmp);

        }

        return $leader;
    }

    #获取所有的业务总监  部门的子部门包含业务部门的  也算  又要迭代找一遍
    private function getAllBusinessDirector()
    {
        if (!$this->dept) {
            $this->dept = $this->HrModel->getLevelDept();
        }
        #先找出所有包含业务部门的部门
        $business = $this->HrModel->getBusinessDept();
        $business = array_column($business, 'ID');
        $arr = [];

        foreach ($business as $value) {

            $tmp = $this->getParentTree($this->dept, $value, 1);
            unset($tmp[0]);
            $tmp = array_column($tmp, 'ID');
            $arr = array_merge((array)$arr, (array)$tmp);
        }
        $data = $this->HrModel->getAllBusinessDirector($arr);

        return $data;
    }

    private function getPromotionListWhere($data)
    {


        $where = [];
        if (!empty($data['promotion_no'])) $where['tb_hr_promotion.promotion_no'] = $data['promotion_no'];
        if (!empty($data['status'])) $where['tb_hr_promotion.status'] = ['IN', $data['status']];
        if (!empty($data['current_approver'])) $where['tb_hr_promotion.current_approver'] = ['like', '%' . $data['current_approver'] . '%'];
        if (!empty($data['empl_id'])) $where['tb_hr_promotion.empl_id'] = ['IN', $data['empl_id']];
        if (!empty($data['created_at']) && count($data['created_at']) == 2) $where['tb_hr_promotion.created_at'] = ['between', [$data['created_at']['start_date'], $data['created_at']['end_date']]];
        if (!empty($data['created_by'])) $where['tb_hr_promotion.created_by'] = ['IN', $data['created_by']];
        if (!empty($data['promotion_time'])) $where['tb_hr_promotion.promotion_time'] = $data['promotion_time'].'-01';
        if (!empty($data['promotion_raise_type_cd'])) $where['tb_hr_promotion.promotion_raise_type_cd'] = ['IN', $data['promotion_raise_type_cd']];
        if (!empty($data['dept_id'])) {
            #找出包含的所有子部门
            if (!$this->dept) {
                $this->dept = $this->HrModel->getLevelDept();
            }
            $deptChild = $this->getChildArr($this->dept, $data['dept_id']);
            $deptChild = array_column($deptChild,'ID');
            array_push($deptChild, $data['dept_id']);
            $where['tb_hr_promotion.dept_id'] = ['IN', $deptChild];
            
        }
       
        #权限模块
        #hr和ceo可以看到全部数据
        #hr 改成Helen Yuan 、Cara Cai、 Vera Zhai
        #其他人只能看到自己&&晋升成功的  or  自己审批过 or 待自己审批的
        #查找hr 和ceo
        $userId = DataModel::userId();;
        $userEmplId = M('admin', 'bbm_')->where(['M_ID' => $userId])->getField('empl_id');
        // $hr = $this->HrModel->getHr();
        // $hr = array_column($hr, 'M_ID');
        $ceo = $this->HrModel->getCeo();


        
        $hrArr = ['Helen.Yuan', 'Cara.Cai', 'Vera.Zhai', 'Helen Yuan', 'Cara Cai', 'Vera Zhai'];

        $hrName1 = userName();
        $hrName2 = $_SESSION['emp_sc_nm'];


        if (!in_array($hrName1, $hrArr) && !in_array($hrName2, $hrArr) &&  $this->emplId!= $ceo['EMPL_ID']) {
            #既不是Hr 又不是 ceo
            #则只能看到自己 && 晋升成功的  or  自己审批过 or 待自己审批的
            #查找自己审批过 or 待自己审批的晋升单号
            $approverListId = M('hr_promotion_approver', 'tb_')->where(['approver_id' => $this->emplId])->group('promotion_id')->select();
            $promotionIds = array_column($approverListId, 'promotion_id');

            $where['_string'] = "(tb_hr_promotion.empl_id = $userEmplId and tb_hr_promotion.status = 'N003630004')";
            if (count($promotionIds) > 0) {
                $promotionIds = "(" . implode(",", $promotionIds) . ")";
                $where['_string'] .= " or tb_hr_promotion.id in  $promotionIds";
            }
        }
        return $where;
    }

    private function addApproverLog($model, $data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = userName();
        #操作人empl_id
        $data['approver_id'] = $this->emplId;
        $logRe = $model->table('tb_hr_promotion_log ')->add($data);
        if (empty($logRe)) {
            throw new Exception(L('写入日志失败，请稍后再试'));
        }

    }


    public function approverlogList()
    {

        $id = $_REQUEST['id'];  #晋升单id
        if (empty($id)) $this->ajaxError('晋升单id不能为空');

        $data = [];
        $data = M('hr_promotion_log', 'tb_')->where(['promotion_id' => $id])->select();
        if (count($data) < 1) {
            goto outjson;
        }
        $data = array_map(function ($value) {
            if ($value['result'] === '1') $value['result'] = '同意';
            if ($value['result'] === '0') $value['result'] = '不同意';
            return $value;
        }, $data);
        $userId = DataModel::userId();

        #是否为Hr ceo
        // $hr = $this->HrModel->getHr();
        // $hr = array_column($hr, 'M_ID');


        $hrArr = ['Helen.Yuan', 'Cara.Cai', 'Vera.Zhai', 'Helen Yuan', 'Cara Cai', 'Vera Zhai'];

        $hrName1 = userName();
        $hrName2 = $_SESSION['emp_sc_nm'];


        




        $ceo = $this->HrModel->getCeo();
        $isHrCeo = 0;
        if (in_array($hrName1, $hrArr) || in_array($hrName2, $hrArr) || $this->emplId == $ceo['EMPL_ID']) {
            #hr 或者
            $isHrCeo = 1;
        }

        if ($isHrCeo) goto outjson;

        #查找候选人是否被当前打开日志的人领导
        #查找候选人所有部门
        $emplId = $data[0]['empl_id'];
        $deptIds = M('hr_empl_dept', 'tb_')->where(['ID2' => $emplId])->select();

        #看下候选人是否领导
        foreach ($deptIds as $value) {
            if (!$this->dept) {
                $this->dept = $this->HrModel->getLevelDept();
            }
            if ($emplId != $this->emplId) {

                if ($value['TYPE'] == 1) {
                    //员工  则需要找出该部门往上所有的部门的领导 看下操作者是否在里面
                    $tmpDeptId = $value['ID1'];
                    $tmpParents = $this->getParentTree($this->dept, $tmpDeptId, 1);
                    $tmpParents = array_column($tmpParents, 'ID');
                    #查找一下操作者是否在领导者里面
                    $tmpUser = M('hr_empl_dept', 'tb_')->where(['ID2' => $this->emplId, 'ID1' => ['IN', $tmpParents], 'TYPE' => 0])->find();
                    if ($tmpUser) {
                        goto outjson;
                    }
                } else {
                    #候选人也为领导  查找职级是否大于此人
                    $tmpDeptId = $value['ID1'];
                    $tmpParents = $this->getParentTree($this->dept, $tmpDeptId);
                    $tmpParents = array_column($tmpParents, 'ID');
                    unset($tmpParents[0]);

                    #在本部门之上的所有领导都可以看
                    $tmpUser = M('hr_empl_dept', 'tb_')->where(['ID2' => $this->emplId, 'ID1' => ['IN', $tmpParents], 'TYPE' => 0])->find();
                    if ($tmpUser) {
                        goto outjson;
                    }
                    #操作者的等级排序  #如果跟候选人同属同一部门  且都为领导 比较SORT字段
                    $createSort = M('hr_empl_dept', 'tb_')->where(['ID2' => $this->emplId, 'ID1' => $value['ID1'], 'TYPE' => 0])->find();
                    if ($createSort && $createSort['SORT'] && $createSort['SORT'] > $value['SORT']) {
                        goto outjson;
                    }


                }
            }

        }


        #业务部门总监   且  候选人部门为支持部门
        $promitionType = M('hr_promotion', 'tb_')->where(['id' => $id])->getField('type');
        if ($promitionType == 'N003640001') {
            #支持部门
            $nextApprover = $this->getAllBusinessDirector();
            $nextApprover = array_column($nextApprover, 'EMPL_ID');
            if (in_array($this->emplId, $nextApprover)) {
                //当前用户为业务部门总监 则看到自己审批的
                foreach ($data as $key => $value) {
                    if ($value['type'] == 1 || $value['approver_id'] == $this->emplId) {
                        continue;
                    } else {
                        foreach ($value as $k1 => $v1) {
                            if ($k1 != 'type') {
                                $data[$key][$k1] = "***";
                            }
                        }
                    }
                }
                goto outjson;
            }

        }


        #自己发起的 只能看到hr发起信息
        if ($data[0]['empl_id'] == $this->emplId) {

            foreach ($data as $key => $value) {
                if ($value['type'] == 1) {
                    continue;
                } else {

                    foreach ($value as $k1 => $v1) {
                        if ($k1 != 'type') {
                            $data[$key][$k1] = "***";
                        }
                    }
                }
            }

            goto outjson;
        }

        #其他情况  看到加密的
        foreach ($data as $key => $value) {

            foreach ($value as $k1 => $v1) {
                if ($k1 != 'type') {
                    $data[$key][$k1] = "***";
                }
            }

        }


        outjson:


        $this->ajaxSuccess($data);
    }

    private function sendWechatMsgByWid($emplIdArr, $content, $promotionId)
    {

        $content = $content . '<a href="http://' . $_SERVER['HTTP_HOST'] . '/index.php?m=index&a=index&source=email&actionType=promotionDetail&id=' . $promotionId . '">【去查看】</a>';

        $emplIdArr = array_column($emplIdArr, 'EMPL_ID');
        if (count($emplIdArr) > 0) {
            $widArr = M('hr_empl_wx', 'tb_')->field('wid')->where(['uid' => ['IN', $emplIdArr]])->select();
            $widArr = array_column($widArr, 'wid');
            foreach ($widArr as $key => $value) {
                if ($value) {
                    Logs((['wid' => $value, 'content' => $content]), __FUNCTION__ . '----send data', 'promotionApproverSendWx');
                    $res = ApiModel::WorkWxSendMessagePost($value, $content);
                    #记录日志
                    Logs([$res], __FUNCTION__ . '----send res', 'storePushOrderSendWx');
                }
            }
        }
    }


    //传入月份，推送待CEO审批的到ceo审批节点
    public function batchPushToCeoNode(){
        $next_status = 'N003630003';
        $model = M();
        $promotion_model = M('hr_promotion','tb_');
        $promotion_approve_model = M('hr_promotion_approver','tb_');
        // $apply_time = $_REQUEST['apply_date'];
        $apply_id = $_REQUEST['id'];
        if(empty($apply_id)){
            throw new Exception(L('id必传'));
        }
        $where = "a.status = 'N003630006' and a.id  = '$apply_id'";
        $promotions = $promotion_model
            ->alias('a')
            ->field('a.id,a.status,b.M_NAME as employee_name,a.promotion_raise_type_cd')
            ->join('bbm_admin b on a.empl_id = b.empl_id')
            ->where($where)->select();
        $success = 0;

        foreach ($promotions as $pk=>$pv){
            $model->startTrans();
            $id = $pv['id'];
            $update_promotion['status'] = $next_status;
            $update_promotion['updated_at'] = date("Y-m-d H:i:s");
            $update_promotion['updated_by'] = userName();

            $update_approve['result'] = 0;
            $update_approve['type'] = $next_status;
            $update_approve['updated_at'] = date('Y-m-d H:i:s');
            $update_approve['updated_by'] = userName();
            $raiseType = $pv['promotion_raise_type_cd'];
            $process = $raiseType == 'N003750002' ? '加薪流程' : '晋升流程';
            $res1 = $model->table('tb_hr_promotion')->where(['id'=>$id])->save($update_promotion);
            if(!$res1){
                $model->rollback();
                continue;
            }
            $res2 = $model->table('tb_hr_promotion_approver')->where(['promotion_id'=>$id,'type'=> 'N003630006'])->save($update_approve);

            if(!$res2){
                $model->rollback();
                continue;
            }

            $model->commit();
            $success += 1;
            //发消息到ceo
            $send_people = $nextApprover = [$this->HrModel->getCeo()];
            $this->sendWechatMsgByWid($send_people, '员工' . $pv['employee_name'] . '的'.$process.'等待您审批。', $id);

        }
        return $this->ajaxSuccess(['total_success'=>$success],'操作成功','200');
    }
    public function getPromotionType(){
        $data = M('ms_cmn_cd', 'tb_')->field('CD,CD_VAL')->where(['CD'=>['like', 'N00375%'],'USE_YN'=>'Y'])->select();
        $this->ajaxSuccess($data);
    }
    #从第二层开始  层级返回部门
    public function getDeptAll(){
        if (!$this->dept) {
            $this->dept = $this->HrModel->getLevelDept();
        }
        
        $allDept = $this->getChildTree($this->dept);
        #去除gshopper
        $allDept = $allDept[0]['children'];
        $this->ajaxSuccess($allDept);
      
    }

    public function getCurrency(){
        $data = M('ms_cmn_cd', 'tb_')->field('CD,CD_VAL')->where(['CD' => ['like', 'N00059%'], 'USE_YN' => 'Y'])->select();
        $this->ajaxSuccess($data);
    }
}
