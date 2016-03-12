<?php
namespace View\Controller;

use Think\Controller;

class LibController extends Controller
{

    public function bookinfo()
    {
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $marc_no = I('get.marc_no');
        $book = R('Home/Lib/getBookInfo', array(
            $marc_no
        ));
        if ($book == 404) {
            $this->status = 404;
        } else {
            $this->status = 0;
            $this->book = $book;
        }
        $this->title = '图书信息';
        $this->doubanUrl = U('Lib/getDouban');
        $this->display();
    }

    public function getDouban()
    {
        $isbn = I('get.isbn');
        if ($isbn != '') {
            $url = 'https://api.douban.com/v2/book/isbn/' . $isbn;
            $content = file_get_contents($url);
            $content = str_replace('\n', '<br>', $content);
            $this->show(nl2br($content));
        }
    }

    public function jiansuo()
    {
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $keyword = I('get.keyword');
        
        $this->title = "书目检索";
        $this->keyword = $keyword;
        $this->openid = $openid;
        $this->submitUrl = U('Lib/result');
        $this->bookinfoUrl = U('Lib/bookinfo', 'openid=' . $openid);
        $this->display();
    }

    public function result()
    {
        if (isset($_POST['searchType'])) {
            $searchType = I('post.searchType');
            $searchStr = I('post.searchStr');
            $openid = I('post.openid');
            $lib = A('Home/Lib');
            $res = $lib->seach($searchStr, $searchType, false);
            if ($res['status'] == 0) {
                foreach ($res['data'] as $k => $v) {
                    preg_match("/可借：([0-9]{1,2})/", $v['num'], $num);
                    $res['data'][$k]['no'] = (int) $num[1];
                }
            }
        } else {
            $res['status'] == 401;
        }
        $this->ajaxReturn($res);
    }

    public function jieyue()
    {
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        
        $lib = A('Home/Lib');
        $return_str = "";
        $cookie = R('Home/Info/getLibCookie',array($openid));
        if ($cookie == 404) {
            $status=404;
        } elseif ($cookie==403) {
            $status=403;
        } else {
            $data = $lib->getLibData($cookie);
            if ($data[1]) {
                $status=404;
            } else {
                $book = $lib->trimData($data);
                $status=0;
            }
        }
        
        $this->status=$status;
        $this->openid=$openid;
        $this->book=$book;
        $this->title='借阅情况';
        $this->bindUrl=U('Info/bind','openid='.$openid);
        $this->xvjieUrl=U('Lib/xvjie');
        $this->display();
    }
    
    public function xvjie(){
        if (isset($_GET['openid'])) {
            $openid = I('get.openid');
            $lib = A('Home/Lib');
            $return_str = "";
            $cookie=R('Home/Info/getLibCookie',array($openid));
            $data = $lib->getLibData($cookie);
            if ($data[1]) {
                $return_str = '操作出错，请重试';
            } else {
                $book = $lib->trimData($data);
                $return_str=$lib->xujie($book);
            }
            $this->show($return_str);
        }
    }
    
    public function weizhang(){
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        
        $lib = A('Home/Lib');
        $cookie=R('Home/Info/getLibCookie',array($openid));
        if($cookie==404){
            $status=404;
        }elseif($cookie==403){
            $status=403;
            
        }else{
            $res = $lib->weizhang($cookie);
            if($res['status']==401){
                $status=401;
            }else{
                $status=0;
                $books=$res['data'];
            }
        }
        
        $this->status=$status;
        $this->res=$res;
        $this->openid=$openid;
        $this->title='图书馆违章情况';
        $this->bindUrl=U('View/Info/bind','openid='.$openid);
        $this->bookinfoUrl=U('View/Lib/bookinfo','openid='.$openid);
        $this->display();
    }
}
