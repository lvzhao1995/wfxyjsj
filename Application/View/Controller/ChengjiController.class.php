<?php
namespace View\Controller;

use Think\Controller;

class ChengjiController extends Controller
{

    public function index()
    {
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $jwc = A('Home/Jwc');
        $rs = $jwc->isBind($openid);
        if ($rs === false) {
            $this->nobind = '';
        } else {
            $nj = '20' . substr($rs['studentid'], 0, 2);
            $nian = date('Y');
            $yue = date('m');
            if ($yue > 8) {
                $nian ++;
            }
            
            $this->rs = $rs;
            $this->nj = $nj;
            $this->nian = $nian;
        }
        
        $this->bindurl = U('Bind/index', 'openid=' . $openid);
        $this->url = U('Chengji/getcj');
        $this->openid = $openid;
        $this->title = '成绩查询';
        $this->display();
    }

    public function getcj()
    {
        $xn = I('post.xn');
        if ($xn != '') {
            $xq = I('post.xq/d');
            $openid = I('post.openid/s');
            if (! empty($xn) && ! empty($xq)) {
                
                $jwc = A('Home/Jwc');
                $info = A('Home/Info');
                
                $rs = $jwc->isBind($openid);
                if ($rs) {
                    $cookie = $info->getJwcCookie($openid);
                    $number = $rs['studentid'];
                    if ($cookie == 404) {
                        $resData['status'] = 404;
                    } elseif ($cookie) {
                        $chengji = $jwc->getChengji($cookie, $number, $xn, $xq, true);
                    }
                } else {
                    $resData['status'] = 403;
                }
            }
            if (isset($chengji)) {
                if ($chengji['status'] == 404) {
                    $resData['status'] = 404;
                } elseif ($chengji['status'] == 401) {
                    $resData['status'] = 401;
                } else {
                    $resData = $chengji;
                }
            }
            $this->ajaxReturn($resData);
        }
    }
}