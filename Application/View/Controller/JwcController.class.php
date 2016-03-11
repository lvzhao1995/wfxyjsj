<?php
namespace View\Controller;

use Think\Controller;

class JwcController extends Controller
{

    public function chengji()
    {
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $jwc = A('Home/Jwc');
        $rs = $jwc->isBind($openid);
        if ($rs == 401) {
            $this->status = 401;
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
        
        $this->bindurl = U('Info/bind', 'openid=' . $openid);
        $this->url = U('Jwc/getcj');
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

    public function baoming()
    {
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $jwc = A('Home/Jwc');
        $cookie = R('Home/Info/getJwcCookie', array($openid));
        $rs = $jwc->isBind($openid);
        if ($cookie == 404 || $rs == 404) {
            $this->status = 404;
        } elseif ($cookie == 401 || $rs == 401) {
            $this->status = 401;
        } else {
            $number = $rs['studentid'];
            $res = $jwc->getBaomingMsg($number, $cookie);
            $this->number = $number;
            $this->imgUrl = U('View/Jwc/getimg', 'cookie=' . $cookie . '&number=' . $number);
        }
        
        $this->title='活动报名';
        $this->cookie = $cookie;
        $this->submitUrl = U('View/Jwc/subbaoming', 'openid=' . $openid);
        $this->bindurl = U('Info/bind', 'openid=' . $openid);
        $this->display();
    }

    public function subbaoming()
    {
        $cookie = I('post.cookie');
        if ($cookie != '') {
            $number = I('post.number');
            $data = I('post.');
            unset($data['number']);
            unset($data['cookie']);
            $url = C('JWC_URL') . 'bmxmb.aspx?xh=' . $number;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_REFERER, $url);
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            $data = curl_exec($ch);
            curl_close($ch);
        }
        $this->baoming();
    }
}