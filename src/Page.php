<?php
/*======================================================================================================================
 *   ProjectName: startutils
 *      FileName: Page.php
 *          Desc: 分页类
 *        Author: start
 *         Email: start_wang@qq.com
 *      HomePage: 
 *       Version: 0.0.1
 *           IDE: PhpStorm
 *  CreationTime: 2017/4/25 11:01
 *    LastChange: 2017/4/25 11:01
 *       History:
 =====================================================================================================================*/

namespace Start\Utils;

class Page
{
    private $total;     //总记录
    private $listRow;   //每页显示行数;
    private $limit;     //偏移
    private $uri;       //地址
    private $pageNum;   //总页数
    private $lnum = 5;  //数字链接数
    public $config = array(
        'num'		=>		'记录',
        'first'		=>		'首页',
        'prev'		=>		'上一页',
        'next'		=>		'下一页',
        'last'		=>		'尾页'
    );

    public function __construct($total, $listRow = 10, $pa=''){
        $this->total = $total;
        $this->listRow = $listRow;
        $this->uri = $this->getURI($pa);
        $this->pageNum = ceil($this->total/$this->listRow);
        $this->page = !empty($_GET['page'])?$_GET['page']:1;
        if($this->page<1){
            $this->page=1;
        }
        if($this->page>$this->pageNum){
            $this->page=$this->pageNum;
        }
        $this->limit = $this->setLimit();
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setLimit(){
        return ' limit '.($this->page-1)*$this->listRow.','.$this->listRow;
    }

    private function getURI($pa){
        $url = $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':'?').$pa;
        $parse = parse_url($url);

        if (isset($parse['query'])) {
            parse_str($parse['query'],$paras);
            unset($paras['page']);
            $url = $parse['path'].'?'.http_build_query($paras);
        }

        return $url;
    }

    function __get($args){
        if ($args == 'limit') {
            return $this->limit;
        } else {
            return null;
        }
    }

    public function first(){
        $html='';
        if ($this->page == 1) {
            $html .= '&nbsp&nbsp'.$this->config['first'].'&nbsp&nbsp';
        } else {
            $html .= '&nbsp&nbsp<a href="'.$this->uri.'&page=1">'.$this->config['first'].'</a>&nbsp&nbsp';
        }

        return $html;
    }

    public function prev(){
        $html='';
        if ($this->page == 1) {
            $html .= '&nbsp&nbsp'.$this->config['prev'].'&nbsp&nbsp';
        } else {
            $html .= '&nbsp&nbsp<a href="'.$this->uri.'&page='.($this->page-1).'">'.$this->config['prev'].'</a>&nbsp&nbsp';
        }

        return $html;
    }

    public function pageList(){
        $start = $this->page>$this->lnum?$this->page-$this->lnum:1;
        $end = $this->page+$this->lnum<=$this->pageNum?$this->page+$this->lnum:$this->pageNum;
        $pageLink='';
        for ($i=$start;$i<=$end;$i++){
            if ($this->page == $i) {
                $pageLink .= '&nbsp'.$i.'&nbsp';
            } else {
                $pageLink .= '&nbsp<a href="'.$this->uri.'&page='.$i.'">'.$i.'</a>&nbsp';
            }
            if($this->page == 1 && $i == 1){ $pageLink = $i;}
        }
        return $pageLink;
    }

    public function next(){
        $html='';
        if ($this->page == $this->pageNum || $this->pageNum == 0) {
            $html .= '&nbsp&nbsp'.$this->config['next'].'&nbsp&nbsp';
        } else {
            $html .= '&nbsp&nbsp<a href="'.$this->uri.'&page='.($this->page+1).'">'.$this->config['next'].'</a>&nbsp&nbsp';
        }
        return $html;
    }

    public function last(){
        $html='';
        if ($this->page == $this->pageNum || $this->pageNum == 0) {
            $html .= '&nbsp&nbsp'.$this->config['last'].'&nbsp&nbsp';
        } else {
            $html .= '&nbsp&nbsp<a href="'.$this->uri.'&page='.$this->pageNum.'">'.$this->config['last'].'</a>&nbsp&nbsp';
        }

        return $html;
    }

    function fpage(){
        $html = $this->page.'/'.$this->pageNum.'&nbsp&nbsp共'.$this->total.$this->config['num'].'&nbsp&nbsp&nbsp&nbsp';
        $html .= $this->first();
        $html .= $this->prev();
        $html .= $this->pageList();
        $html .= $this->next();
        $html .= $this->last();
        return  $html;
    }
}