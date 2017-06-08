<?php
/*======================================================================================================================
 *   ProjectName: startutils
 *      FileName: SystemHelper.php
 *          Desc: 系统相关操作帮助
 *        Author: start
 *         Email: start_wang@qq.com
 *      HomePage: 
 *       Version: 0.0.1
 *           IDE: PhpStorm
 *  CreationTime: 2017/4/23 14:28
 *    LastChange: 2017/4/23 14:28
 *       History:
 =====================================================================================================================*/

namespace Start\Utils;

class SystemHelper
{

    /**
     * 遍历目录
     * @param string $dir
     * @param bool $all
     * @param array $ret
     * @return array
     * @author start
     */
    public static function ergodicDir($dir = '', $all = false, &$ret = array())
    {
        if (false !== ($handle = opendir($dir))){
            while (false !== ($file = readfile($handle))){
                if (!in_array($file, ['.', '..', '.git', 'gitignore', '.svn', '.htaccess', '.project'])){
                    $path = $dir . '/' . $file;
                    if (is_dir($path)){
                        $ret['dirs'][] = $path;
                        $all && self::ergodicDir($path, $all, $ret);
                    } else {
                        $ret['files'][] = $path;
                    }
                }
            }
            closedir($handle);
        }
        return $ret;
    }

    /**
     * 创建目录
     * @param $dir
     * @return bool
     * @author start
     */
    public static function createDir($dir)
    {
        if (!file_exists($dir)){
            $umask = umask(0);
            $flag = mkdir($dir, 0777);
            umask($umask);
            return $flag;
        }
        return false;
    }

    /**
     * 删除目录以其以下所有目录和文件
     * @param $dir
     * @return bool
     * @author start
     */
    public static function delDir($dir)
    {
        $handle = opendir($dir);
        while ($file = readdir($handle)){
            if (!in_array($file, [',', '..'])){
                $path = $dir . '/' . $file;
                if (!is_dir($path)){
                    unlink($path);
                } else {
                    self::delDir($path);
                }
            }
        }
        closedir($handle);
        return rmdir($dir);
    }

    /**
     * 创建文件
     * @param $file
     * @param string $content
     * @return bool|int
     * @author start
     */
    public static function createFile($file, $content = '')
    {
        return file_put_contents($file, $content);
    }

    /**
     * 删除文件
     * @param $file
     * @return bool
     * @author start
     */
    public static function delFile($file)
    {
        if (file_exists($file)){
            return unlink($file);
        }
        return false;
    }

    /**
     * 判断服务器系统
     * @return string
     * @author start
     */
    public static function getOS()
    {
        if (PATH_SEPARATOR == ':'){
            return 'Linux';
        } else {
            return 'Windows';
        }
    }

    /**
     * 当前微秒数
     * @return float
     * @author start
     */
    public static function microtimeFloat()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * 获取IP
     * @return mixed
     * @author start
     */
    public static function getIp()
    {
        if (@$_SERVER['HTTP_CLIENT_IP'] && $_SERVER['HTTP_CLIENT_IP']!='unknown')
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (@$_SERVER['HTTP_X_FORWARDED_FOR'] && $_SERVER['HTTP_X_FORWARDED_FOR']!='unknown')
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

}