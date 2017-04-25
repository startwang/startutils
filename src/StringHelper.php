<?php
/*======================================================================================================================
 *   ProjectName: startutils
 *      FileName: StringHelper.php
 *          Desc: 字符串操作相关
 *        Author: start
 *         Email: start_wang@qq.com
 *      HomePage: 
 *       Version: 0.0.1
 *           IDE: PhpStorm
 *  CreationTime: 2017/4/23 15:54
 *    LastChange: 2017/4/23 15:54
 *       History:
 =====================================================================================================================*/

namespace Start\Utils;

class StringHelper
{

    /**
     * 字母数据随机数
     * @param int $number
     * @param null $prefix
     * @return string
     * @author start
     */
    public static function randomStr($number = 10, $prefix = null)
    {
        $prefix = empty($prefix) ? date ( 'his' ) : $prefix.'_';
        $chars = '0123456789abcdefghijklmnopqrstuvwxyz_';
        for($x = 0; $x < $number; $x ++) {
            $prefix .= $chars {mt_rand ( 0, strlen ( $chars ) - 1 )};
        }
        list($usec, $sec) = explode(" ", microtime());
        return $prefix . $sec.$usec*1000000;
    }

    /**
     * 唯一ID
     * @return string
     * @author start
     */
    public static function getUniqid()
    {
        return md5(uniqid(rand(), true));
    }

}