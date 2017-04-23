<?php
/*======================================================================================================================
 *   ProjectName: startutils
 *      FileName: ValidationHelper.php
 *          Desc: 验证相关
 *        Author: start
 *         Email: start_wang@qq.com
 *      HomePage: 
 *       Version: 0.0.1
 *           IDE: PhpStorm
 *  CreationTime: 2017/4/23 16:03
 *    LastChange: 2017/4/23 16:03
 *       History:
 =====================================================================================================================*/

namespace Start\Utils;

class ValidationHelper
{

    /**
     * 验证是否为IP
     * @param $ip
     * @return bool
     * @author start
     */
    public static function isIP($ip)
    {
        $flag = filter_var($ip, FILTER_VALIDATE_IP) ? true : false;
        return $flag;
    }

    /**
     * 验证是否为Email
     * @param $mail
     * @return bool
     * @author start
     */
    public static function isEmail($mail)
    {
        $flag = filter_var($mail, FILTER_VALIDATE_EMAIL) ? true : false;
        return $flag;
    }

    /**
     * 验证是否为url
     * @param $url
     * @return bool
     * @author start
     */
    public static function isUrl($url){
        $flag = filter_var($url, FILTER_VALIDATE_URL) ? true : false;
        return $flag;
    }

}