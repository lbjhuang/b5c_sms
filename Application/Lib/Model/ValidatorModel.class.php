<?php
/**
 * User: yangsu
 * Date: 18/5/21
 * Time: 10:50
 */

/**
 * Class ValidatorModel
 *
 * @package Application\Lib\Model
 */
class ValidatorModel extends \Illuminate\Validation\Factory
{
    /**
     * @var string
     */
    private static $message;
    /**
     * @var array
     */
    private static $headers =
        [
            'e' => 'rules/data is empty',
            'na' => 'rules/data is not a array'
        ];

    /***
     * 创建实例
     *
     * @return \Illuminate\Validation\Factory
     */
    public static function getInstance()
    {
        static $validator = null;
        if ($validator === null) {
            $test_translation_path = APP_SITE . '/resources/lang';
            $test_translation_locale = 'zh_CN';
            $translation_file_loader = new \Illuminate\Translation\FileLoader(new \Illuminate\Filesystem\Filesystem, $test_translation_path);
            $translator = new \Illuminate\Translation\Translator($translation_file_loader, $test_translation_locale);
            $validator = new \Illuminate\Validation\Factory($translator);
        }
        return $validator;
    }

    /**
     * @param array $rules 验证规则
     * @param array $data  验证数据
     *
     * @return bool
     */
    public static function validate($rules = [], $data = [], $customAttributes = [])
    {
        if (empty($rules) || empty($data)) {
            self::$message = self::$headers['e'];
            return false;
        }
        if (is_array($rules) && is_array($data)) {
            $v = self::vmake($rules, $data, $customAttributes);
            if ($v->fails()) {
                self::$message = $v->messages();
                return false;
            }
            return true;
        }
        self::$message = self::$headers['na'];
        return false;
    }

    /**
     * @param $rules
     * @param $data
     *
     * @return
     */
    private static function vmake($rules, $data, $customAttributes)
    {
        $v = self::getInstance()->make($data, $rules, [], $customAttributes);
        return $v;
    }

    /**
     * @return string
     */
    public static function getMessage()
    {
        return self::$message;
    }

    /**
     * @name 清空异常信息
     * @return string
     */
    public static function clearMessage()
    {
        self::$message = null;
        return self::$message;
    }
}