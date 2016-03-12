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
    
    public function bind(){
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        
        $this->title="绑定信息门户";
        $this->openid=$openid;
        $this->indexUrl=U('Index/index','openid='.$openid);
        $this->bindUrl=U('Info/doBind');
        $this->display();
    }
    
    public function doBind(){
        if (isset($_POST['openid'])) {
            $usernum = I('post.usernum/s');
            $infopass = I('post.infopass/s');
            $openid = I('post.openid/s');
            $resData = array();
            $resData['status'] = 1;
            $resData['msg'] = '';
            if ($infopass != '') {
                $res=R('Home/Info/bind',array($openid,$usernum,$infopass));
                if ($res == 402) {
                    $resData['msg'] .= "绑定信息门户时遇未知错误！请重试";
                } elseif ($res == 404) {
                    $resData['msg'] .= '信息门户服务器无法访问，稍后重试';
                } elseif ($res==0) {
                    $resData['status'] = 0;
                    $resData['msg'] .= '信息门户绑定成功！';
                } else {
                    $resData['msg'] .= '信息门户密码错误！';
                }
            }else{
                $resData['msg'] .= '密码不能为空！';
            }
            $this->ajaxReturn($resData);
        }
    }
}