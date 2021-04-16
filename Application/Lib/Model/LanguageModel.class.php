<?php
/**
 * User: yuanshixiao
 * Date: 2018/1/30
 * Time: 14:45
 */

class LanguageModel extends Model
{
    protected $autoCheckFields = false;

    private $language_type = [];
    static $language_cache_key_pre = 'LANGUAGE_';

    /**
     * 获取语言类型
     * @return bool
     */
    public function getLanguageType()
    {
        return (new TbMsCmnCdModel())->getCdListY(TbMsCmnCdModel::$language_cd_pre);
    }

    /**
     * 保存翻译数据
     * @param $language_data
     */
    public function saveAllTrans($language_data)
    {
        $this->language_type = $this->getLanguageType();
        $has_failure = false;
        $this->startTrans();
        $user = $_SESSION['m_loginname'];
        foreach ($language_data as $v) {
            if ($this->checkParam($v)) {
                if ($this->translationExists($v['element'], $v['type'])) {
                    $res = $this->where(['element' => trim($v['element']), 'type' => trim($v['type'])])->save(['translation_content' => trim($v['translation_content']), 'updated_by' => $user]);
                    /*if ($content = trim($v['translation_content'])) {
                    } else {
                        $res = $this->where(['element' => trim($v['element']), 'type' => trim($v['type'])])->delete();
                    }*/
                } else {
                    $v['created_by'] = $user;
                    $v['updated_by'] = $user;
                    if ($content = trim($v['translation_content'])) $res = $this->add($v);
                }
                if ($res === false) {
                    $this->error = '保存失败';
                    $has_failure = true;
                    break;
                }
            } else {
                $has_failure = true;
                break;
            }
        }
        if ($has_failure) {
            $this->rollback();
            return false;
        } else {
            $this->commit();
            return true;
        }
    }

    /**
     * 保存校验
     * @param $param
     * @return bool
     */
    public function checkParam($param)
    {
        if (!trim($param['element']) || trim(!$param['type'])) {
            $this->error = '元素名称和语言类型不能为空';
            return false;
        }
        if (!in_array($param['type'], $this->language_type)) {
            $this->error = '语言类型错误';
            return false;
        }
        return true;
    }

    public function translationExists($element, $type)
    {
        if ($this->where(['element' => $element, 'type' => $type])->find()) {
            return true;
        } else {
            return false;
        }
    }

    public function flushCache()
    {
        $language = (new TbMsCmnCdModel())->getCdY(TbMsCmnCdModel::$language_cd_pre);
        $redis = RedisModel::connect_init();
        foreach ($language as $v) {
            $language_key = self::$language_cache_key_pre . $v['ETC2'];
            $translation = $this->where(['type' => $v['CD']])->getField('element,translation_content', true);
            $redis->set($language_key, json_encode($translation, JSON_UNESCAPED_UNICODE));
        }
    }

    public function getTranslation()
    {
        if (isLocalEnv()) {
            if (empty($_SESSION['translation'])) {
                $language_key = self::$language_cache_key_pre . LANG_SET;
                $redis = RedisModel::connect_init();
                $translation = json_decode($redis->get($language_key), true);
                $_SESSION['translation'] = $translation;
                return $translation;
            } else {
                return $_SESSION['translation'];
            }
        } else {
            $language_key = self::$language_cache_key_pre . LANG_SET;
            $redis = RedisModel::connect_init();
            if ($translation = $redis->get($language_key)) {
                return json_decode($translation, true);
            } else {
                $this->flushCache();
                $translation = $redis->get($language_key);
                return json_decode($translation, true);
            }
        }
    }

    public function getOneTranslation($lang)
    {
        clearL();
        $language_key = self::$language_cache_key_pre . $lang;
        $redis = RedisModel::connect_init();
        if ($translation = $redis->get($language_key)) {
            return json_decode($translation, true);
        } else {
            $this->flushCache();
            $translation = $redis->get($language_key);
            return json_decode($translation, true);
        }
    }

    /**
     * @param $lang
     *
     * @return mixed
     */
    public static function langToCode($lang)
    {
        $erp_lang_types = RedisModel::get_key('erp_lang_types');
        if (empty($erp_lang_types)) {
            $erp_lang_types = json_encode(array_column(B2bModel::get_code_cd('N00092', false, true), 'CD', 'ETC2'));
            RedisModel::set_key('erp_lang_types', $erp_lang_types, null, 3600);
        }
        $erp_lang_types = json_decode($erp_lang_types, true);
        return $erp_lang_types[$lang];
    }

    /**
     * @return mixed
     */
    public static function getCurrent()
    {
        return cookie('think_language');
    }

    /**
     * @param $language
     */
    public static function setCurrent($language)
    {
        clearL();
        cookie('think_language', $language);
        L((new LanguageModel())->getOneTranslation($language));
    }

    /**
     * @return mixed
     */
    public static function getCurrentCd()
    {
        return self::langToCode(cookie('think_language'));
    }

    public static function setUserAccessLanguage($user_name)
    {
        $language = ReviewMsg::getPathToLang(
            TbHrCardModel::getCardWorkPalceFromUser($user_name)
        );
        cookie('think_language', $language);
        L((new LanguageModel())->getOneTranslation($language));
    }

    //英文语言刷新
    public function flushEnCache()
    {
        //刷新语言
        $language_key = 'LANGUAGE_en-us';
        $language = new LanguageModel();
        $translation = $language->where(['type' => 'N000920200'])->getField('element,translation_content', true);
        $res = RedisModel::set_key($language_key, json_encode($translation, JSON_UNESCAPED_UNICODE));
        return $res;
    }
}