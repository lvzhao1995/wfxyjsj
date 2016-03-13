<?php
namespace View\Controller;

use View\Common\PublicController;

class JwcController extends PublicController 
{

    public function chengji()
    {
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $jwc = A('Home/Jwc');
        $rs = $jwc->isBind($openid);
        if ($rs == 403) {
            $status = 403;
        } else {
            $nj = '20' . substr($rs['studentid'], 0, 2);
            $nian = date('Y');
            $yue = date('m');
            if ($yue > 8) {
                $nian ++;
            }
            $status=0;
            $this->rs = $rs;
            $this->nj = $nj;
            $this->nian = $nian;
            
        }
        
        $this->status=$status;
        $this->bindurl = U('Info/bind', 'openid=' . $openid);
        $this->cxUrl = U('Jwc/getcj');
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
                    $resData['status'] = 0;
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
        $cookie = R('Home/Info/getJwcCookie', array(
            $openid
        ));
        $rs = $jwc->isBind($openid);
        if ($cookie == 404 || $rs == 404) {
            $status = 404;
        } elseif ($cookie == 403 || $rs == 403) {
            $status = 403;
        } else {
            $status=0;
            $number = $rs['studentid'];
            $res = $jwc->getBaomingMsg($number, $cookie);
            
        }
        
        $this->res=$res;
        $this->title = '活动报名';
        $this->cookie = $cookie;
        $this->status = $status;
        $this->number = $number;
        $this->imgUrl = U('View/Jwc/getimg', 'cookie=' . $cookie . '&number=' . $number);
        $this->submitUrl = U('View/Jwc/subbaoming', 'openid=' . $openid);
        $this->bindurl = U('Info/bind', 'openid=' . $openid);
        $this->display();
    }
    
    public function getimg(){
        $cookie=I('get.cookie');
        $number=I('get.number');
        $url=C('JWC_URL').'readimagexs.aspx?xh='.$number;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_REFERER, C('JWC_URL').'bmxmb.aspx?xh='.$number);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $data = array();
        $data = curl_exec($ch);
        curl_close($ch);
        echo $data;
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

    public function kebiao()
    {
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        
        $jwc =A('Home/Jwc');
        $rs = $jwc->isBind($openid, true);
        if ($rs == 404) {
            $status=404;
        } elseif ($rs==403) {
            $status=403;
        } else {
            $status=0;
            $kebiao = json_decode($rs['kecheng_json'], true);
            
            $week = (time() - $jwc->starttime) / 604800;
            $week = (int) $week + 1;
            $color = array(
                'am-active',
                'am-primary',
                'am-success',
                'am-warning',
                'am-danger'
            );
            for ($i = 1; $i < 10; $i += 2) {
                for ($d = 1; $d < 8; $d ++) {
                    $during = array();
                    $flag = array();
                    $tmp = array();
                    $j = 0;
                    foreach ($kebiao[$d][$i] as $temp) {
                        if ($temp['coursename'] != '&nbsp;' && $temp['coursename'] != '') {
                            preg_match_all('/(.*?)-(.*)/', $temp['courseduring'], $during);
                            $start = $during[1][0];
                            $end = $during[2][0];
                            if (! isset($flag['coursename']) || $flag['coursename'] != $temp['coursename']) {
                                $j ++;
                                $tmp[$j]['coursename'] = $temp['coursename'];
                                $tmp[$j]['courseplace'] = $temp['courseplace'];
                                $tmp[$j]['coursesingle'] = $temp['coursesingle'];
                                if ($start == $end) {
                                    $tmp[$j]['during'] = $start;
                                } else {
                                    $tmp[$j]['during'] = $start . '-' . $end;
                                }
                            } elseif (! isset($temp['coursesingle'])) {
                                unset($tmp[$j]['coursesingle']);
                                if ($start == $end) {
                                    $tmp[$j]['during'] .= ',' . $start;
                                } else {
                                    $tmp[$j]['during'] .= ',' . $start . '-' . $end;
                                }
                            }
                            $flag = $temp;
                        }
                    }
                    foreach ($tmp as $k=>$v) {
                        $during = explode(',', $v['during']);
                        sort($during, SORT_NATURAL);
                        $v['during'] = implode(',', $during);
                        $tmp[$k]=$v;
                    }
                    $kebiao[$d][$i]=$tmp;
                }
            }
        }
            
        $this->color=$color;
        $this->status=$status;
        $this->title="我的课表";
        $this->kebiao=$kebiao;
        $this->week=$week;
        $this->openid=$openid;
        $this->bindUrl=U('Info/bind','openid='.$openid);
        $this->clear=U('Jwc/clearkb');
        $this->display();
    }
    
    public function clearkb(){
        if (isset($_POST['openid'])) {
            $openid=I('post.openid');
            $db=M('info');
            $re=$db->where(array('openid'=>':openid'))
                ->bind(':openid',$openid)
                ->save(array('kecheng_json'=>''));
            if($re!==false){
                $this->success();
            }
        }
    }
    
    public function xuanxiu(){
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        
        $jwc = A('Home/Jwc');
        $rs = $jwc->isBind($openid);
        if ($rs==404) {
            $status=404;
        }elseif($rs==403){
            $status=403;
        }else{
            $cookie = R('Home/Info/getJwcCookie',array($openid));
            $number = $rs['studentid'];
            if ($cookie == 404||$cookie==403 ) {
                $status=$cookie;
            } else{
                $status=0;
                $xxdata = $jwc->readxuanxiu($cookie, $number);
            }
        }
        
        $this->xxdata=$xxdata;
        $this->bindUrl=U('Info/bind','openid='.$openid);
        $this->status=$status;
        $this->title="选修课查询";
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