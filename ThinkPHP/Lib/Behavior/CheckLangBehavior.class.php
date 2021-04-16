<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();
/**
 * 语言检测 并自动加载语言包
 * @category   Extend
 * @package  Extend
 * @subpackage  Behavior
 * @author   liu21st <liu21st@gmail.com>
 */
class CheckLangBehavior extends Behavior {
    // 行为参数定义（默认值） 可在项目配置中覆盖
    protected $options   =  array(
            'LANG_SWITCH_ON'        => true,   // 默认关闭语言包功能
            'LANG_AUTO_DETECT' => true, // 自动侦测语言 开启多语言功能后有效
            'LANG_LIST'             => 'zh-cn,en-us', // 允许切换的语言列表 用逗号分隔
            'VAR_LANGUAGE'          => 'l',		// 默认语言切换变量
        );

    /**
     * 语言类型和语言码对应关系
     * @var array 语言码
     */
    protected $langCodeMap = array(
        'zh-cn' => 'N000920100',
        'en-us' => 'N000920200',
        'kr-kr' => 'N000920300',
        'jp-jp' => 'N000920400',
    );
    
    // 行为扩展的执行入口必须是run
    public function run(&$params){
        // 开启静态缓存
        $this->checkLanguage();
    }

    /**
     * 语言检查
     * 检查浏览器支持语言，并自动加载语言包
     * @access private
     * @return void
     */
    private function checkLanguage() {
        // 不开启语言包功能，仅仅加载框架语言文件直接返回
        if (!C('LANG_SWITCH_ON')){
            return;
        }
        $langSet = C('DEFAULT_LANG');

        $languageList = $this->getLanguageList(); // 获取目前配置的多语言列表
        $this->langCodeMap = array_flip($languageList);
        
        // 启用了语言包功能
        // 根据是否启用自动侦测设置获取语言选择
        if (C('LANG_AUTO_DETECT')){
            if(isset($_GET[C('VAR_LANGUAGE')])){
                $langSet = $_GET[C('VAR_LANGUAGE')];// url中设置了语言变量
                cookie('think_language',$langSet,365 * 24 * 3600);
            }elseif(cookie('think_language')){// 获取上次用户的选择
                if(empty(I('l')) && $this->checkLang()){
                    cookie('think_language',$this->go_lang(),365 * 24 * 3600);
                }
                $langSet = cookie('think_language');
            }elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){// 自动侦测浏览器语言
                preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
                $langSet = $matches[1];
                cookie('think_language',$langSet,365 * 24 * 3600);
            }
            
            if(false === stripos($languageList, $langSet)) { // 非法语言参数
                $langSet = C('DEFAULT_LANG');
                exit;
            }           
            /*if(false === stripos(C('LANG_LIST'),$langSet)) { // 非法语言参数
                $langSet = C('DEFAULT_LANG');
                exit;
            }*/

        }

        // 定义当前语言 和语言的Code码，方便后续的数据库查询使用。
        define('LANG_SET',strtolower($langSet));
        define('LANG_CODE', $this->langCodeMap[LANG_SET]);
        /*
        $group = '';
        $path    =   (defined('GROUP_NAME') && C('APP_GROUP_MODE')==1) ? BASE_LIB_PATH.'Lang/'.LANG_SET.'/' : LANG_PATH.LANG_SET.'/';
        // 读取项目公共语言包
        if(is_file(LANG_PATH.LANG_SET.'/common.php')){
            L(include LANG_PATH.LANG_SET.'/common.php');     
        }

        // 读取分组公共语言包
        if(defined('GROUP_NAME')){
            if(C('APP_GROUP_MODE')==1){ // 独立分组
                $file = $path.'common.php';
            }else{ // 普通分组
                $file = $path.GROUP_NAME.'.php';
                $group = GROUP_NAME.C('TMPL_FILE_DEPR');
            }
            if(is_file($file))
                L(include $file);
        }
        // 读取当前模块语言包
        if (is_file($path.$group.strtolower(MODULE_NAME).'.php'))
            L(include $path.$group.strtolower(MODULE_NAME).'.php');
        */
        L((new LanguageModel())->getTranslation());
    }

    // 获取多语言站点列表
    public function getLanguageList()
    {
        $languageList = cookie('think_lang_list'); 
        if (!$languageList) {
            $Dictionary = new DictionaryModel();
            $languageRes = $Dictionary->getLanguage('CD', 'ETC2');  
            cookie('think_lang_list', $languageRes, 3600);
            $languageList = cookie('think_lang_list'); 
        }
        return $languageList;
    }

    private function go_lang(){
        $lang = 'zh-cn';
        $lang_str = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $lang_arr = explode(',',$lang_str);
        if(false !== stripos($lang_arr[0],'en')){
            $lang = 'en-us';
        }
        return $lang;
    }

    private function checkLang(){
        // $lang_arr = ['zh-cn','en-us','ko-kr','ja-jp'];
        $lang_arr = $this->getLanguageList();
        $type = false;
        if(!in_array(cookie('think_language'),$lang_arr)){
            $type = true;
        }
        return $type;
    }
}


