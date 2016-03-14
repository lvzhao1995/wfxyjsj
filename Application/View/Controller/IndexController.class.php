<?php
namespace View\Controller;

use View\Common\PublicController;

class IndexController extends PublicController
{

    public function index()
    {
        $openid = I('get.openid');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $this->display();
    }
    
    public function _empty($name){
        $openid = I('get.openid');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $this->redirect('Index/index', array('openid' => $openid), 0);
    }
    
}