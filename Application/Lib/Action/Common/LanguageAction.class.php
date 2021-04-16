<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/15
 * Time: 16:05
 */

class LanguageAction extends Action
{
    private $check_data_type = ['get_hash', 'get_data'];

    public function index()
    {
        //$translation = (new LanguageModel())->getTranslation();
        $lan_arr = ['N000920200' => 'en-us', 'N000920100' => 'zh-cn', 'N000920300' => 'ja-jp', 'N000920400' => 'ko-kr'];
        $lang = new LanguageModel();
        $tmp_lang = $lang->select();
        $lang_list = [];
        foreach ($tmp_lang as $v) {
            if (!isset($lang_list[$v['element']])) {
                $lang_list[$v['element']]['en-us'] = '';
                $lang_list[$v['element']]['zh-cn'] = $v['element'];
                $lang_list[$v['element']]['ja-jp'] = '';
                $lang_list[$v['element']]['ko-kr'] = '';
            }
            $lang_list[$v['element']][$lan_arr[$v['type']]] = $v['translation_content'];
        }
        if (IS_POST) {
            $data_type = DataModel::getDataToArr('data_type');
            $language_type = DataModel::getDataToArr('language_type');
            if ($this->checkLanguageHashData($data_type)) {
                switch ($data_type) {
                    case 'get_hash':
                        foreach ($lang_list as $temp_key => $temp_val) {
                            $temp_hash = hash('sha1', $temp_key);
                            foreach ($temp_val as $k => $v) {
                                $hash_lang_list[$v] = $temp_hash;
                            }
                        }
                        break;
                    case 'get_data':
                        foreach ($lang_list as $temp_key => $temp_val) {
                            $hash_lang_list[hash('sha1', $temp_key)] = $temp_val;
                        }
                        break;
                }
                $this->ajaxReturn(['code' => 1, 'msg' => 'success', 'data' => $hash_lang_list], 'JSON');
            } elseif ($this->checkLanguageData($language_type, $lan_arr)) {
                $lang_list = array_column($lang_list, $language_type['to'], $language_type['from']);
            } else {
                $lang_list = (array)null;
            }
            $this->ajaxReturn(['code' => 1, 'msg' => 'success', 'data' => $lang_list], 'JSON');
        } else {
            $this->ajaxReturn(['code' => 1, 'msg' => 'success', 'data' => array_values($lang_list)], 'JSON');
        }
    }

    private function checkLanguageHashData($data_type)
    {
        if ($data_type && in_array($data_type, $this->check_data_type)) {
            return true;
        }
        return false;
    }

    /**
     * @param $language_type
     *
     * @return bool
     */
    private function checkLanguageData($language_type, $lan_arr)
    {
        $lan_val_arr = array_values($lan_arr);
        if ($language_type['from'] && $language_type['to']
            && in_array($language_type['from'], $lan_val_arr)
            && in_array($language_type['to'], $lan_val_arr)) {
            return true;
        }
        return false;
    }
}