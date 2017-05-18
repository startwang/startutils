<?php
/*======================================================================================================================
 *   ProjectName: startutils
 *      FileName: Curl.php
 *          Desc: curl操作
 *        Author: start
 *         Email: start_wang@qq.com
 *      HomePage: 
 *       Version: 0.0.1
 *           IDE: PhpStorm
 *  CreationTime: 2017/3/7 11:27
 *    LastChange: 2017/3/7 11:27
 *       History:
 *       Example: 设置host     $Curl->hostIp = '127.0.0.1';
 *                设置域验证   $Curl->user = 'xxx', $mayfansCurl->pwd = 'xxx'
 *                设置代理     $Curl->proxy = array('ip'=>'xxx', 'user'=>'xxx', 'pwd'=>'xxx');
 *                其他设置     $Curl->options = array('CURLOPT_HEADER'=>true);
 *                get          $Curl->get('http://www.baidu.com', array('参数1'=>'xxx'));
 *                post         $Curl->post('http://www.baidu.com', array('参数1'=>'xxx'));
 =====================================================================================================================*/

namespace Start\Utils;

class Curl
{

    /**
     * 目标地址的域名
     * @var
     */
    protected $hostname;

    /**
     * 目标端口
     * @var int
     */
    public $port = 80;

    /**
     * 要host的ip地址
     * @var
     */
    public $hostIp;

    /**
     * 要host的数组
     * @var
     */
    public $hostArray;

    /**
     * 用户 - 域验证
     * @var
     */
    public $user;

    /**
     * 密码 - 域验证
     * @var
     */
    public $pwd;

    /**
     * 代理 ip, user, pwd
     * @var array
     */
    public $proxy = array();

    /**
     * cookie文件
     * @var
     */
    public $cookieFile;

    /**
     * 伪造访问来源
     * @var
     */
    public $referer;

    /**
     * 伪造浏览器信息
     * @var
     */
    public $userAgent;

    /**
     * 超时
     * @var int
     */
    public $outTime = 6;

    /**
     * 重试次数
     * @var int
     */
    public $retry = 3;

    /**
     * 多线程重试次数
     * @var array
     */
    public $retryMulti = array();

    /**
     * 头信息
     * @var array
     */
    public $headers = array();

    /**
     * curl设置
     * @var array
     */
    public $options = array();

    /**
     * 回调
     * @var null
     */
    public $callback = null;

    /**
     * 保存并行出错的url
     * @var array
     */
    public $multiErrorUrls = array();

    /**
     * 错误信息
     * @var string
     */
    protected $error = '';

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param string $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * @return mixed
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @param mixed $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    /**
     * MayfansCurl constructor.
     */
    public function __construct()
    {
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
        $this->cookieFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'startutils_curlcookie.txt';
    }

    /**
     * get
     * @param $url
     * @param array $param
     * @return mixed
     * @author start
     */
    public function get($url, $param = array())
    {
        return $this->request('get', $url, $param);
    }

    /**
     * post
     * @param $url
     * @param array $param
     * @return mixed
     * @author start
     */
    public function post($url, $param = array())
    {
        return $this->request('post', $url, $param);
    }

    /**
     * put
     * @param $url
     * @param array $param
     * @return mixed
     * @author start
     */
    public function put($url, $param = array())
    {
        return $this->request('put', $url, $param);
    }

    /**
     * delete
     * @param $url
     * @param array $param
     * @return mixed
     * @author start
     */
    public function delete($url, $param = array())
    {
        return $this->request('delete', $url, $param);
    }

    /**
     * 执行curl
     * @param $method
     * @param $url
     * @param array $param
     * @return mixed
     * @author start
     */
    public function request($method, $url, $param = array())
    {
        if(is_string($url)){
            return $this->single($method, $url, $param);
        } elseif(is_array($url) && count($url) > 0){
            return $this->multi($method, $url, $param);
        }
        return false;
    }

    /**
     * 单线程
     * @param $method
     * @param $url
     * @param array $param
     * @return mixed
     * @author start
     */
    public function single($method, $url, $param = array())
    {
        $ch = curl_init();
        if(is_array($param)){
            $param = http_build_query($param, '', '&');
        }

        $this->setHeader($ch);
        $this->setMethod($ch, $method);
        $this->setOptions($ch, $url, $param);

        $result = curl_exec($ch);
        if(!$result && --$this->retry > 0){
            $result = $this->single($method, $url, $param);
            $this->error .= curl_errno($ch) . ':' . curl_error($ch).'<br>';
        }

        curl_close($ch);

        if($this->callback && is_callable($this->callback)){
            call_user_func($this->callback, $result);
        }
        return $result;
    }

    /**
     * 执行单线程
     * @param $ch
     * @return mixed
     * @author start
     */
    public function singleExec($ch)
    {
        $result = curl_exec($ch);
        if(!$result && --$this->retry > 0){
            $result = $this->singleExec($ch);
            $this->error .= curl_errno($ch) . ':' . curl_error($ch).'<br>';
        }
        return $result;
    }

    /**
     * 多线程
     * @param $method
     * @param $urls
     * @param array $param
     * @return array
     * @author start
     */
    public function multi($method, $urls, $param = array())
    {
        $ch = $result = array();
        $handle = curl_multi_init();
        foreach($urls as $key => $url){
            $this->retryMulti[$url] = $this->retry;
            $ch[$key] = $this->addHandle($handle, $method, $url, !empty($param[$key])?$param[$key]:'');
        }

        $this->execHandle($handle);
        foreach($urls as $key => $url){
            $result[$key] = $this->multiExec($ch[$key], $url);

            if($result[$key] && in_array($url, $this->multiErrorUrls)){
                unset($this->multiErrorUrls[array_search($url, $this->multiErrorUrls)]);
            }

            if($this->callback && is_callable($this->callback)){
                call_user_func($this->callback, $result[$key]);
            }

            curl_multi_remove_handle($handle, $ch[$key]);
        }
        curl_multi_close($handle);

        return $result;
    }

    /**
     * 执行多线程
     * @param $ch
     * @param $url
     * @return mixed|string
     * @author start
     */
    public function multiExec($ch, $url)
    {
        $result = curl_multi_getcontent($ch);
        if(!$result && --$this->retryMulti[$url] > 0){
            $result = $this->multiExec($ch, $url);
            $this->error .= curl_errno($ch) . ':' . curl_error($ch).'<br>';
            if(!in_array($url, $this->multiErrorUrls)){
                array_push($this->multiErrorUrls, $url);
            }
        }
        return $result;
    }

    /**
     * 加入多线程处理
     * @param $handle
     * @param $method
     * @param $url
     * @param array $param
     * @return resource
     * @author start
     */
    public function addHandle($handle, $method, $url, $param = array())
    {
        $ch = curl_init();
        if(is_array($param)){
            $param = http_build_query($param, '', '&');
        }
        $this->setHeader($ch);
        $this->setMethod($ch, $method);
        $this->setOptions($ch, $url, $param);
        curl_multi_add_handle($handle, $ch);

        return $ch;
    }

    /**
     * 执行多线程处理
     * @param $handle
     * @author start
     */
    public function execHandle($handle)
    {
        $flag = null;
        do{
            curl_multi_exec($handle, $flag);
        } while ($flag > 0);
    }

    /**
     * 设置get/post
     * @param $ch
     * @param $method
     * @author start
     */
    protected function setMethod($ch, $method)
    {
        switch (strtolower($method)){
            case 'get':
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case 'put':
            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                break;
            default:
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
        }
    }

    /**
     * 设置curl选项
     * @param $ch
     * @param $url
     * @param $param
     * @author start
     */
    protected function setOptions($ch, $url, $param)
    {
        curl_setopt($ch, CURLOPT_URL, $url);
        if(!empty($param)){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        }

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->outTime);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);

        if($this->referer){
            curl_setopt($ch, CURLOPT_REFERER, $this->referer);
        }

        if($this->cookieFile){
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        }

        if(isset($this->proxy['ip']) && !empty($this->proxy['ip'])){
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy['ip']);
            if(!empty($this->proxy['user']) && !empty($this->proxy['pwd'])){
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxy['user'] . ":" . $this->proxy['pwd']);
            }
        }

        if($this->user && $this->pwd){
            curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->pwd);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        }

        if($this->hostIp){
            preg_match('#https?://(.*?)($|/)#m', $url, $urlResult);
            $resolve = array(sprintf("%s:%d:%s", $urlResult[1], $this->port, $this->hostIp));
            curl_setopt($ch, defined('CURLOPT_RESOLVE') ? CURLOPT_RESOLVE : CURLOPT_IPRESOLVE, $resolve);
        }

        if ($this->hostArray){
            curl_setopt($ch, defined('CURLOPT_RESOLVE') ? CURLOPT_RESOLVE : CURLOPT_IPRESOLVE, $this->hostArray);
        }

        foreach($this->options as $key => $value){
            if(constant(strtoupper($key))){
                curl_setopt($ch, constant(strtoupper($key)), $value);
            }
        }

    }

    /**
     * 设置header
     * @param $ch
     * @author start
     */
    protected function setHeader($ch)
    {
        $header = array();
        foreach($this->headers as $key => $value){
            $header[] = $key . ': '. $value;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }

    public function __destruct()
    {
        $this->retryMulti = array();
    }

}