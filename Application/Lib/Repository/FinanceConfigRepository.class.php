<?php

/**
 *
 * Class FinReportRepository
 */
class FinanceConfigRepository extends Repository
{

    protected  $res = array(
        'code' => 2000,
        'msg'  => "",
        'data'  => []
    );

    public function getCreateBy(){
        $list = M('report_config','tb_fin_')->field('create_by')->select();
        if ($list){
            $list = array_unique(array_column($list,'create_by'));
            $this->res['data'] = $list;
        }
        return $this->res;
    }

    public function getfinDate(){
        $list = M('report_config','tb_fin_')->field('year,quarter')->order('year,quarter')->select();
        $data = array();
        if ($list){
            foreach ($list as $item){
                $val = $item['year'].' '.$item['quarter'];
                if (!in_array($val,$data)){
                    array_push($data,$val);
                }
            }
            $this->res['data'] = $data;
        }
        return $this->res;
    }


    public function getList($post_data){

        $whereStr = "1=1";
        if (isset($post_data['search'])){

            if (!empty($post_data['search']['fin_date'])){
                $fin_date = explode(' ',$post_data['search']['fin_date']);
                if (count($fin_date) > 1){
                    $whereStr.= " AND year = '".$fin_date[0]."'";
                    $whereStr.= " AND quarter =  '".$fin_date[1]."'";
                }
            }

            if (!empty($post_data['search']['create_start_date'])){
                $whereStr .= " AND create_at >= '".$post_data['search']['create_start_date']."'";
            }

            if (!empty($post_data['search']['create_end_date'])){
                $whereStr .= " AND create_at <= '".date("Y-m-d",strtotime("+1 day",strtotime($post_data['search']['create_end_date'])))."'";
            }

            if (!empty($post_data['search']['create_by'])){
                $whereStr .= " AND create_by = '".$post_data['search']['create_by']."'";
            }

        }
        $pages = array(
            'per_page' => 10,
            'current_page' => 1
        );
        if (isset($post_data['pages']) && !empty($post_data['pages']['per_page']) && !empty($post_data['pages']['current_page'])){
            $pages = array(
                'per_page' =>$post_data['pages']['per_page'],
                'current_page' => $post_data['pages']['current_page']
            );
        }

        $count = M('report_config','tb_fin_')
            ->field('id as fin_report_id ,year,quarter,title,create_by,create_at,update_by,update_at')
            ->whereString($whereStr)
            ->count();
        $data['total'] = $count;
        $data['data'] = array();
        if ($count){
            $list = M('report_config','tb_fin_')
                ->field('id as fin_report_id ,year,quarter,title,create_by,create_at,update_by,update_at')
                ->whereString($whereStr)
                ->order('tb_fin_report_config.create_at desc')
                ->limit(($pages['current_page'] - 1) * $pages['per_page'], $pages['per_page'])
                ->select();
            $data['data'] = $list;
        }


        $this->res['data'] = $data;
        return $this->res;
    }

    public function getJoinList($condition){
        $list = M('report_config','tb_fin_')
            ->field('       
                tb_fin_report_config_detail.subsection,
                tb_fin_report_config_detail.content')
            ->join('LEFT JOIN tb_fin_report_config_detail ON tb_fin_report_config.id = tb_fin_report_config_detail.fin_report_id')
            ->where($condition)
            ->order('tb_fin_report_config_detail.subsection')
            ->select();
       return $list;
    }
    public function savaData($post_data,$type){
        $model = M();
        $model->startTrans();
        try{
            if (empty($post_data)){
                throw new \Exception('参数不能为空');
            }
            if ( empty($post_data['year']) || empty($post_data['quarter']) || empty($post_data['data'])){
                throw new \Exception('参数有误');
            }

            if (!$post_data['fin_report_id']){

                $ret = $model->table("tb_fin_report_config")->where(array('year'=>$post_data['year'],'quarter'=>$post_data['quarter']))->find();
                if ($ret){
                    $this->res['code'] = 4001;
                    $this->res['msg'] = '报告时间重复，请选择其他时间';
                    return $this->res;
                }

                // 添加
                $add_data_config = array(
                    'title' => isset($post_data['title']) ? $post_data['title'] : "",
                    'year' => $post_data['year'],
                    'quarter' => $post_data['quarter'],
                    'create_by' => $_SESSION['m_loginname'],
                    'create_at' => date('Y-m-d H:i:s'),
                    'update_by' => $_SESSION['m_loginname'],
                    'update_at' => date('Y-m-d H:i:s'),

                );
                $fin_report_id = $model->table("tb_fin_report_config")->add($add_data_config);
                if (!$fin_report_id){
                    throw new  Exception('财报添加失败-主');
                }
                $add_data_config_detail = array();
                foreach ($post_data['data'] as $itme){
                    $tmep_data = array();
                    $tmep_data['type'] = $type;
                    $tmep_data['fin_report_id'] = $fin_report_id;
                    $tmep_data['subsection'] = $itme['subsection'];
                    $tmep_data['content'] = $itme['content'];
                    $add_data_config_detail[] =  $tmep_data;
                }
                $res = $model->table('tb_fin_report_config_detail')->addAll($add_data_config_detail);
                if (!$res){
                    throw new  Exception('财报添加失败-副');
                }
                $this->res['data'] = array('fin_report_id'=>$fin_report_id);
            }else{
                // 修改
                $update_data_config = array(
                    'title' => isset($post_data['title']) ? $post_data['title'] : "",
                    'year' => $post_data['year'],
                    'quarter' => $post_data['quarter'],
                    'update_by' => $_SESSION['m_loginname'],
                    'update_at' => date('Y-m-d H:i:s'),

                );
                $ret = $model->table("tb_fin_report_config")->where(array('year'=>$post_data['year'],
                    'quarter'=>$post_data['quarter'],'id'=>array('NEQ',$post_data['fin_report_id'])))->find();
                if ($ret){
                    $this->res['code'] = 4001;
                    $this->res['msg'] = '报告时间重复，请选择其他时间';
                    return $this->res;
                }


                $model->table("tb_fin_report_config")->where(array('id'=>$post_data['fin_report_id']))->save($update_data_config);
                $model->table('tb_fin_report_config_detail')->where(array('fin_report_id'=>$post_data['fin_report_id'],'type'=>$type))->delete();
                $add_data_config_detail = array();
                foreach ($post_data['data'] as $itme){
                    $tmep_data = array();
                    $tmep_data['type'] = $type;
                    $tmep_data['fin_report_id'] = $post_data['fin_report_id'];
                    $tmep_data['subsection'] = $itme['subsection'];
                    $tmep_data['content'] = $itme['content'];
                    $add_data_config_detail[] =  $tmep_data;
                }
                $res = $model->table('tb_fin_report_config_detail')->addAll($add_data_config_detail);
                if (!$res){
                    throw new  Exception('财报添加失败-副');
                }
                $this->res['data'] = array('fin_report_id'=>$post_data['fin_report_id']);
            }
            $model->commit();
        }catch (\Exception $exception){
            $model->rollback();
            $this->res['code'] = 4000;
            $this->res['msg'] = $exception->getMessage();
        }
        return $this->res;
    }
}
