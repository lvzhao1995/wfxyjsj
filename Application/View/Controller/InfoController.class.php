<?php
namespace View\Controller;

use Think\Controller;

class InfoController extends Controller
{

    public function unbind()
    {
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $unbind = R('Home/Info/unBind', array(
            $openid
        ));
        if ($unbind) {
            $this->status = 0;
        } else {
            $this->status = 1;
        }
        
        $this->title = "解除绑定";
        $this->bindUrl = U('Info/bind', 'openid=' . $openid);
    }
}