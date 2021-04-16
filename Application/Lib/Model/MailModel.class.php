<?php

/**
 * User: yangsu
 * Date: 18/1/31
 * Time: 19:49
 */
class MailModel
{
    /**
     * @param        $title
     * @param        $message
     * @param null   $cc
     * @param string $user
     *
     * @return bool
     */
    public static function mail_send($title, $message, $cc = null, $user = 'yangsu@gshopper.com')
    {
        $email = new SMSEmail();
        $cc = count($cc) ? $cc : null;
        $res = $email->sendEmail($user, $title, $message, $cc);
        return $res;
    }

    /**
     *  cc is array
     *
     * @param $data
     */
    public static function receivableRemindMailSend(array $data)
    {
        return self::sendMail($data);
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public static function sendMail(array $data)
    {
        list($title, $message, $cc, $user) = self::data2list($data);
        return self::mail_send($title, $message, $cc, $user);
    }

    /**
     * @param $data
     *
     * @return array
     */
    private static function data2list(array $data)
    {
        $title = $data['title'];
        $message = $data['message'];
        $cc = $data['cc'];
        $user = $data['user'];
        return array($title, $message, $cc, $user);
    }
}