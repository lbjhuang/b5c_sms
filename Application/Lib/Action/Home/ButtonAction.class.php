<?php
/**
 * User: yangsu
 * Date: 18/4/19
 * Time: 10:14
 */

class ButtonAction extends Action
{

    /**
     * @param null $param
     * @return bool
     */
    public static function hidden($param = null)
    {
        if (false === $param) {
            return true;
        }
        if (null === $param || in_array(strtolower($param), $_SESSION['actlist_value_lower'])) {
            return true;
        }
        return false;
    }

}