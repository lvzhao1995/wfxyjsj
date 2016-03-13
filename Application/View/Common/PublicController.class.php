<?php
namespace View\Common;

use Think\Controller;

class PublicController extends Controller
{
    public function _empty($name){
        $openid = I('get.openid');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $this->redirect('Index/index', array('openid' => $openid), 0);
    }
    
    public function _initialize(){
        
        $db=M('manage');
        $data=$db->field('appid,appsecret')->find();
        $jssdk = new JSSDK($data['appid'], $data['appsecret']);
        $signPackage = $jssdk->GetSignPackage();
        
        $openid=I('get.openid');
        $data['index']=U('Index/index','openid='.$openid);
        $data['kebiao']=U('Jwc/kebiao','openid='.$openid);
        $data['chengji']=U('Jwc/chengji','openid='.$openid);
        $data['xuanxiu']=U('Jwc/xuanxiu','openid='.$openid);
        $data['baoming']=U('Jwc/baoming','openid='.$openid);
        $data['jiansuo']=U('Lib/jiansuo','openid='.$openid);
        $data['jieyue']=U('Lib/jieyue','openid='.$openid);
        $data['weizhang']=U('Lib/weizhang','openid='.$openid);
        $data['bind']=U('Info/bind','openid='.$openid);
        $data['unbind']=U('Info/unbind','openid='.$openid);
        $this->url=$data;
        $this->signPackage=$signPackage;
    }
}