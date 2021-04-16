<?php

/**
 * User: yangsu
 * Date: 17/7/31
 * Time: 11:10
 */
class AuthorAction extends Action
{
    public $base_arr = [];
    public $base_del_arr = [];
    public $Node = '';

    public function login()
    {


    }

    /**
     * 同步节点
     */
    public function sync_node()
    {
        if (!S('$files') || !S('$customer_functions')) {
            list($files, $customer_functions) = $this->get_model_data();
            S('$files', $files);
            S('$customer_functions', $customer_functions);
        }
        $files_s = S('$files');
        $customer_functions_s = S('$customer_functions');
        foreach ($files_s as $v) {
            $this->sync_this_node($v, 1);
            $this->sync_this_node($v, 2);
            foreach ($customer_functions_s[$v] as $n) {
                $this->sync_this_node($n['action'], 3);
            }
        }

    }

    /**
     * @param $node_act
     * @param $level
     * @return bool
     */
    private function sync_this_node($node_act, $level)
    {
        $res = false;
        if ($this->check_node($node_act, $level)) {
            die($node_act);
            switch ($level) {
                case 1:
                    $this->add_node($NAME, $TITLE, $CTL, $ACT, $pid, 1);
                    break;
                case 2:
                    $this->add_node($NAME, $TITLE, 'select_node_ben', null, $pid, 2);
                    break;
                case 3:
                    $this->add_node($NAME, $TITLE, $CTL, $ACT, $pid, 3);
                    break;
                default:
                    break;
            }
        }
        return $res;
    }

    /**
     * @param $node_act
     * @param $level
     * @return bool
     */
    private function check_node($node_act, $level)
    {
        if ($level == 2) {
            $where['CTL'] = array('EQ', 'select_node_ben');
        } else {
            $where['CTL'] = array('EQ', $node_act);
        }
        trace($where, '$where');
        $res = $this->Node->where($where)->select();
        $res_status = count($res) > 0 ? false : true;
        return $res_status;
    }

    /**
     * @param string $NAME require
     * @param $TITLE
     * @param $CTL
     * @param $ACT
     * @param int $STATUS require
     * @param string $ICON
     * @param int $pid require
     * @param int $LEVEL require
     * @return mixed
     */
    private function add_node($NAME, $TITLE, $CTL, $ACT, $pid, $LEVEL, $STATUS = 1, $ICON = '&#xe613;')
    {

        $data['NAME'] = $NAME;
        $data['TITLE'] = $TITLE;
        $data['CTL'] = $CTL;
        $data['ACT'] = $ACT;
        $data['STATUS'] = $STATUS;
        $data['ICON'] = '&#xe613;';
        $data['pid'] = $pid;
        $data['LEVEL'] = $LEVEL;
        $data['REMARK'] = 'sync';
        $check_status = $this->Node->add($data);
        return $check_status;
    }

    /**
     * 获取所有模型
     */
    public function get_all_model()
    {
        list($files, $customer_functions) = $this->get_model_data();
        $this->assign('customer_functions', $customer_functions);
        $this->assign('files', $files);
        $this->display();
    }

    protected function getController()
    {
        $module_path = __DIR__;  //控制器路径
        if (!is_dir($module_path)) return null;
        $module_path .= '/*.class.php';
        $ary_files = glob($module_path);
        foreach ($ary_files as $file) {
            if (is_dir($file)) {
                continue;
            } else {
                $files[] = basename($file, C('DEFAULT_C_LAYER') . '.class.php');
            }
        }
        return $files;
    }

    /**
     * @param $model
     * @return array
     */
    private function get_action($model)
    {
        $functions = get_class_methods(get_class(A($model)));
        $inherents_functions = array('_initialize', '__construct', 'getActionName', 'isAjax', 'display', 'show', 'fetch', 'buildHtml', 'assign', '__set', 'get', '__get', '__isset', '__call', 'error', 'success', 'ajaxReturn', 'redirect', '__destruct', '_empty', 'test', '_get_menu', '_get_menu_all', 'request_do', 'setParams', 'generateMethod', 'getDataDirectory', 'backCommonLanguagePackage', 'getCommonFileContent', 'import_supplier_customer', 'import_translation', 'import_supplier_customer', 'show_log', 'clean', 'getParams');
        $base_del = array('_initialize', '_get_menu', '_get_menu_all', '__get', '__construct', '__set', '__isset', '__call', '__destruct', 'get');
        $base_del_arr = $this->base_del_arr;
        $inherents_functions_b = array_merge($inherents_functions, $base_del_arr);
        foreach ($functions as $func) {
            $reflection = new ReflectionMethod (A($model), $func);
            if ($model == 'Base') {
                if (!in_array(trim($func), $base_del)) {
                    $funcs['action'] = $func;
                    $funcs['doc'] = $reflection->getDocComment();
                    if ($reflection->isPublic()) {
                        $customer_functions[] = $funcs;
                    }
                }
            } else {
                if (!in_array(trim($func), $inherents_functions_b)) {
                    $funcs['action'] = $func;
                    $funcs['doc'] = $reflection->getDocComment();
                    if ($reflection->isPublic()) {
                        $customer_functions[] = $funcs;
                    }
                }
            }
        }
        return $customer_functions;
    }

    private function get_base()
    {
        $this->base_arr = $this->get_action('Base');
        return $this->base_arr;
    }

    /**
     * @return array
     */
    private function get_model_data()
    {
        $files = $this->getController();
        foreach ($files as $m) {
            $customer_functions[$m] = $this->get_action($m);
        }
        $customer_functions['base'] = $this->get_base();
        return array($files, $customer_functions);
    }

}