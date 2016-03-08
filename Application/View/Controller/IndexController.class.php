<?php
namespace View\Controller;

use Think\Controller;

class IndexController extends Controller
{

    public function index()
    {
        $openid = I('get.openid');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $this->openid = $openid.'.html';
        $this->display();
    }
}