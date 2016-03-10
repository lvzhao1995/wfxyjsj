<?php
namespace View\Controller;

use Think\Controller;

class BaomingController extends Controller
{

    public function index()
    {
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $jwc = A('Home/Jwc');
        $cookie = R('Home/Info/getJwcCookie', $openid);
        $rs = $jwc->isBind($openid);
        if ($cookie == 404 || $rs == 404) {
            $this->status = 404;
            echo '<body><center><h1>服务器繁忙，请稍后再试！<h1></center></body>';
        } elseif ($cookie == 401 || $rs == 401) {
            $this->status = 401;
            $data = '未绑定';
        } else {
            $number = $rs['studentid'];
            $res = $jwc->getBaomingMsg($number, $cookie);
            $this->number = $number;
            $this->imgUrl = U('View/Baoming/img', 'cookie=' . $cookie . '&number=' . $number);
        }
        
        $this->cookie = $cookie;
        $this->submitUrl = U('View/Baoming/baoming', $openid);
        $this->bindurl = U('Bind/index', 'openid=' . $openid);
        $this->display();
    }
}