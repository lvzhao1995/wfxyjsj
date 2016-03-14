<?php
namespace Home\Controller;

use Think\Controller;

class JwcController extends Controller
{

    private $url;

    public $starttime = 0;

    private $selfurl;

    public function _initialize()
    {
        $this->selfurl = 'http://' . $_SERVER['HTTP_HOST'] ;
        $db = M('manage');
        $starttime = $db->field('starttime')->find();
        if (empty($starttime)) {
            $this->starttime = time() - 60;
        } else {
            $this->starttime = $starttime['starttime'];
        }
        $this->url = C('JWC_URL');
    }

    private function readKecheng($cookie, $number)
    {
        $url1 = $this->url . 'xskbcx.aspx?xh=' . $number;
        $referer = $this->url . 'xs_main.aspx?xh=' . $number;
        $data = $this->visit_url($url1, $cookie, $referer);
        $data = iconv('gbk', 'utf-8', $data[0]);
        $kecheng = array();
        preg_match('/<table id=\"Table1\" class=\"blacktab\".*?>(.*?)<\/table>/ims', $data, $kecheng);
        $kecheng[0] = preg_replace('/<font.*?font>/', '', $kecheng[0]);
        $kecheng[1] = preg_replace('/<font.*?font>/', '', $kecheng[1]);
        return $kecheng;
    }

    function readXuanxiu($cookie, $number)
    {
        $url1 = $this->url . 'xf_xsqxxxk.aspx?xh=' . $number;
        $referer = $this->url . 'xs_main.aspx?xh=' . $number;
        $data = $this->visit_url($url1, $cookie, $referer);
        $data = iconv('gbk', 'utf-8', $data[0]);
        $kecheng = array();
        preg_match('/<table class="datelist" .*?id=\"DataGrid2\".*?>(.*?)<\/table>/ims', $data, $kecheng);
        $redata = array();
        $_block = array();
        if (preg_match_all('/<tr(?:.*?)?>(.*?)<\/tr>/ims', $kecheng[1], $_block)) {
            $flag = 0;
            $str_block = array();
            foreach ($_block[1] as $v) {
                if ($flag) {
                    preg_match_all('/<td(.*?)>(.*?)<\/td>/ims', $v, $str_block);
                    $redata[$flag] = array(
                        'kecheng' => $str_block[2][0],
                        'teacher' => $str_block[2][1],
                        'jiaoshi' => $str_block[2][7],
                        'date' => $str_block[2][6]
                    );
                }
                    $flag ++;
                
            }
        }
        return $redata;
    }

    private function dealXuanxiu($kecheng)
    {
        $redata = '';
        if (! empty($kecheng)) {
            foreach ($kecheng as $v) {
                $redata .= "\n" . '\ue231课程名称：' . $v['kecheng'] . "\n";
                $redata .= '\ue005授课教师：' . $v['teacher'] . "\n";
                $redata .= '\ue036上课教室：' . $v['jiaoshi'] . "\n";
                $redata .= '\ue026上课时间：' . $v['date'] . "\n" . '————————';
            }
        } else {
            $redata = '没有选修课\ue056';
        }
        return $redata;
    }

    private function chengjiHidden($cookie, $number)
    {
        $url1 = $this->url . 'xscj.aspx?xh=' . $number;
        $referer = $this->url . 'xs_main.aspx?xh=' . $number;
        $data = $this->visit_url($url1, $cookie, $referer);
        $matches = $this->getHidden($data[0]);
        return $matches[1][0];
    }

    function getChengji($cookie, $number, $xn = NULL, $xq = NULL, $wangye = NULL)
    {
        if (empty($xn)) {
            $yue = date('m');
            $nian = date('Y');
            $xn = ((int) $nian - 1) . '-' . $nian;
            if ((int) $yue < 7) {
                $xq = '1';
            } else {
                $xq = '2';
            }
        }
        $resData = array();
        $resData['xn'] = $xn;
        $resData['xq'] = $xq;
        $url = $this->url . 'xscj.aspx?xh=' . $number;
        $hidden = $this->chengjiHidden($cookie, $number);
        $post_data = array(
            '__EVENTTARGET' => '',
            '__EVENTARGUMENT' => '',
            '__VIEWSTATE' => $hidden,
            '__VIEWSTATEGENERATOR' => '8963BEEC',
            'ddlXN' => $xn,
            'ddlXQ' => $xq,
            'txtQSCJ' => '0',
            'txtZZCJ' => '100',
            'Button1' => ''
        );
        $data = $this->visit_url($url, $cookie, $url, 0, $post_data);
        $block = array();
        if (preg_match('/<table class=\"datelist\"(.*?)>(.*?)<\/table>/ims', $data[0], $block)) {
            $_block = array();
            if (preg_match_all('/<tr(?:.*?)?>(.*?)<\/tr>/ims', $block[2], $_block)) {
                $flag = 0;
                $str_block = array();
                $i = 1;
                if (count($_block[0]) == 1) {
                    $resData['status'] = 401;
                } else {
                    foreach ($_block[0] as $v) {
                        if ($flag) {
                            preg_match_all('/<td(.*?)>(.*?)<\/td>/ims', iconv('GBk', 'UTF-8', $v), $str_block);
                            $resData['data'][$i]['kemu'] = $str_block[2][1];
                            $resData['data'][$i]['chengji'] = $str_block[2][4];
                            if ($wangye) {
                                $resData['data'][$i]['juanmian'] = $str_block[2][3];
                            }
                            $i ++;
                        } else {
                            $flag ++;
                        }
                    }
                }
            }
        } else {
            $resData = 404;
        }
        return $resData;
    }

    private function getHidden($content)
    {
        $hidden_match = array();
        $patern = '/<input[^>]*?type=\"hidden\"[^>]*?name=\"__VIEWSTATE\"[^>]*?value=\"(.*?)\"[^>]*?>/im';
        if (preg_match_all($patern, $content, $hidden_match)) {
            return $hidden_match;
        }
        $patern = '/<input[^>]*?name=\"__VIEWSTATE\"[^>]*?type=\"hidden\"[^>]*?value=\"(.*?)\"[^>]*?>/im';
        if (preg_match_all($patern, $content, $hidden_match)) {
            return $hidden_match;
        }
        $patern = '/<input[^>]*?name=\"__VIEWSTATE\"[^>]*?value=\"(.*?)\"[^>]*?type=\"hidden\"[^>]*?>/im';
        if (preg_match_all($patern, $content, $hidden_match)) {
            return $hidden_match;
        }
        
        $patern = '/<input[^>]*?value=\"(.*?)\"[^>]*?name=\"__VIEWSTATE\"[^>]*?type=\"hidden\"[^>]*?>/im';
        if (preg_match_all($patern, $content, $hidden_match)) {
            return $hidden_match;
        }
        $patern = '/<input[^>]*?value=\"(.*?)\"[^>]*?type=\"hidden\"[^>]*?name=\"__VIEWSTATE\"[^>]*?>/im';
        if (preg_match_all($patern, $content, $hidden_match)) {
            return $hidden_match;
        }
        
        $patern = '/<input[^>]*?type=\"hidden\"[^>]*?value=\"(.*?)\"[^>]*?name=\"__VIEWSTATE\"[^>]*?>/im';
        if (preg_match_all($patern, $content, $hidden_match)) {
            return $hidden_match;
        }
    }

    private function analyse($data)
    {
        $data = preg_replace('/<font.*?<\/font><br><br>/ims', '', $data);
        $data = preg_replace(array(
            '/<td[^>]*>上午<\/td>/',
            '/<td[^>]*>下午<\/td>/',
            '/<td[^>]*>晚上<\/td>/',
            '/<td.*?节<\/td>/'
        ), '', $data);
        $kecheng = array();
        if (preg_match_all('/<tr.*?<\/tr>/ims', $data, $kecheng)) {
            $kb_course = array();
            $i = 1;
            foreach ($kecheng[0] as $c => $v) {
                if ($c == 0 || $c == 1 || $c == 3 || $c == 5 || $c == 7 || $c == 9 || $c == 11) {
                    continue;
                }
                $str_block = array();
                preg_match_all('/<td.*?>(.*?)<\/td>/ims', $v, $str_block);
                foreach ($str_block[1] as $x => $y1) {
                    $y1 = explode('<br><br>', $y1);
                    foreach ($y1 as $y) {
                        $y = ltrim($y, '<br>');
                        $y = explode('<br>', $y);
                        $data = array();
                        $data['coursename'] = $y[0];
                        $_temp = preg_replace('/周(.*)第(.*)节/', '', $y[1]);
                        $_temp = str_replace('}', '', $_temp);
                        $_temp = str_replace('{', '', $_temp);
                        if (strpos($_temp, '单') > 0) {
                            $data['coursesingle'] = 1;
                        } elseif (strpos($_temp, '双') > 0) {
                            $data['coursesingle'] = 0;
                        }
                        $_temp = preg_replace('#\|.*?周#ims', '', $_temp);
                        $data['courseplace'] = $y[3];
                        $data['courseteacher'] = $y[2];
                        $data['courseduring'] = str_replace('周', '', str_replace('第', '', $_temp));
                        $kb_course[$x + 1][$i][] = $data;
                    }
                }
                $i += 2;
            }
        }
        return json_encode($kb_course);
    }

    function isBind($openid, $kebiao = false)
    {
        $db = M('info');
        if ($kebiao) {
            $data = $db->field('studentid,kecheng_json')
                ->where(array(
                'openid' => ':openid'
            ))
                ->bind(':openid', $openid)
                ->find();
        } else {
            $data = $db->field('studentid')
                ->where(array(
                'openid' => ':openid'
            ))
                ->bind(':openid', $openid)
                ->find();
            $data['kecheng_json']=1;
        }
        if (! empty($data)) {
            if (($data['kecheng_json'] == '' || $data['kecheng_json'] == 'null'||$data['kecheng_json'] == null)) {
                $cookie = R('Home/Info/getJwcCookie', array($openid));
                if ($cookie == 404) {
                    return $cookie;
                } elseif ($cookie!=403) {
                    $kecheng = $this->readKecheng($cookie, $data['studentid']);
                    $data['kecheng_json'] = $this->analyse($kecheng[1]);
                    $data['openid'] = $openid;
                    $db->save($data);
                } else {
                    return 403;
                }
            }
            $rs = array();
            $rs['kecheng_json'] = isset($data['kecheng_json'])?$data['kecheng_json']:'';
            $rs['studentid'] = $data['studentid'];
            return $rs;
        } else {
            return 403;
        }
    }

    function setKey($keyword, $openid)
    {
        if ($keyword != '成绩' && $keyword != '选修课' && $keyword != '报名' && $keyword != '评教') {
            $rs = $this->isBind($openid, true);
        } else {
            $rs = $this->isBind($openid);
        }
        if ($rs == 404) {
            $content = '服务器不太稳定，请隔几十秒再试\ue403';
        } elseif ($rs!=403) {
            $kecheng = json_decode($rs['kecheng_json'], true);
            switch ($keyword) {
                case '课表':
                case '课程表':
                case '今天':
                    $i = date('w');
                    if ($i == 0)
                        $i = 7;
                    $content = $this->returnKebiao($kecheng[$i]);
                    break;
                case '明天':
                    $i = date('w');
                    $content = $this->returnKebiao($kecheng[$i + 1]);
                    break;
                case '周一':
                case '星期一':
                    $content = $this->returnKebiao($kecheng[1]);
                    break;
                case '周二':
                case '星期二':
                    $content = $this->returnKebiao($kecheng[2]);
                    break;
                case '周三':
                case '星期三':
                    $content = $this->returnKebiao($kecheng[3]);
                    break;
                case '周四':
                case '星期四':
                    $content = $this->returnKebiao($kecheng[4]);
                    break;
                case '周五':
                case '星期五':
                    $content = $this->returnKebiao($kecheng[5]);
                    break;
                case '周六':
                case '星期六':
                    $content = $this->returnKebiao($kecheng[6]);
                    break;
                case '周日':
                case '星期天':
                    $content = $this->returnKebiao($kecheng[7]);
                    break;
                case '成绩':
                    $cookie = R('Info/getJwcCookie', array($openid));
                    if ($cookie == 404) {
                        $content = '服务器不太稳定，请隔几十秒再试\ue403';
                    } elseif ($cookie) {
                        $data = $this->getChengji($cookie, $rs['studentid']);
                        if ($data != 404) {
                            $content = $data['xn'] . '学年第' . $data['xq'] . '学期成绩如下:';
                            $flag = 0;
                            foreach ($data['data'] as $chengji) {
                                if ($flag) {
                                    $content .= "\n" . $chengji['kemu'] . '----' . $chengji['chengji'];
                                } else {
                                    $flag ++;
                                }
                            }
                        } else {
                            $content = '入口被关闭了，等下次开放吧/撇嘴开放时间教务处决定的，我们也不知道';
                        }
                    } else {
                        $content = '你还未绑定，<a href="' . $this->selfurl . U('View/Login/index','openid='.$openid).'">点击此处</a>绑定后使用';
                    }
                    break;
                case '选修课':
                    $cookie = R('Info/getJwcCookie', array($openid));
                    if ($cookie == 404) {
                        $content = '服务器不太稳定，请隔几十秒再试\ue403';
                    } elseif ($cookie) {
                        $xxdata = $this->readXuanxiu($cookie, $rs['studentid']);
                        $content = $this->dealXuanxiu($xxdata);
                    } else {
                        $content = '你还未绑定，<a href="' . $this->selfurl . U('View/Login/index','openid='.$openid) . '">点击此处</a>绑定后使用';
                    }
                    break;
                case '报名':
                    $cookie = R('Info/getJwcCookie', array($openid));
                    if ($cookie == 404) {
                        $content = '服务器不太稳定，请隔几十秒再试\ue403';
                    } elseif ($cookie) {
                        $content = '<a href="' . $this->selfurl . U('View/Baoming/index','openid='.$openid) . '">点我开始报名</a>';
                    } else {
                        $content = '你还未绑定，<a href="' . $this->selfurl . U('View/Login/index','openid='.$openid).'">点击此处</a>绑定后使用';
                    }
                    break;
            }
            if ($cookie != 404 && $cookie) {
                if ($keyword != '成绩' && $keyword != '选修课' && $keyword != '报名' && $keyword != '评教') {
                    $content .= "\n" . '<a href="' . $this->selfurl . U('View/Kebiao/index','openid='.$openid). '">戳我查看全部课表</a>\ue40c';
                } elseif ($keyword == '成绩') {
                    $content .= "\n" . '<a href="' . $this->selfurl . U('View/Chengji/index','openid='.$openid) . '">戳我查询其他学期成绩</a>\ue40c';
                }
            }
        } else {
            $content = '你还未绑定，<a href="' . $this->selfurl . U('View/Login/index','openid='.$openid) . '">点击此处</a>绑定后使用';
        }
        $Resdata = array();
        $Resdata['replytype'] = 0;
        $Resdata['content'] = $content;
        return $Resdata;
    }

    private function returnKebiao($kecheng)
    {
        $week = (time() - $this->starttime) / 604800;
        if ($week < 0) {
            $contentStr = "还没开学呢\n";
        } else {
            $week = (int) $week + 1;
            $contentStr = '当前第' . $week . "周\n";
        }
        for ($i = 1; $i < 10; $i += 2) {
            foreach ($kecheng[$i] as $temp) {
                if ($temp['coursename'] != '&nbsp;') {
                    $during = array();
                    preg_match_all('/(.*?)-(.*)/', $temp['courseduring'], $during);
                    $start = $during[1][0];
                    $end = $during[2][0];
                    if ($week >= $start && $week <= $end) {
                        if (isset($temp['coursesingle'])) {
                            if ($temp['coursesingle'] == $week % 2) {
                                $courseCon .= "\n" . '\ue231课程名称：' . $temp['coursename'];
                                $courseCon .= "\n" . '\ue005授课教师：' . $temp['courseteacher'];
                                $courseCon .= "\n" . '\ue036上课教室：' . $temp['courseplace'];
                                $j = $i + 1;
                                $courseCon .= "\n\ue026上课时间：第$i-" . $j . "节\n————————";
                            }
                        } else {
                            $courseCon .= "\n" . '\ue231课程名称：' . $temp['coursename'];
                            $courseCon .= "\n" . '\ue005授课教师：' . $temp['courseteacher'];
                            $courseCon .= "\n" . '\ue036上课教室：' . $temp['courseplace'];
                            $j = $i + 1;
                            $courseCon .= "\n\ue026上课时间：第$i-" . $j . "节\n————————";
                        }
                    }
                }
            }
        }
        if (! empty($courseCon)) {
            $contentStr .= "\ue419课表如下\n————————$courseCon";
        } else {
            $contentStr .= '\ue057没有课呢';
        }
        return $contentStr;
    }

    private function visit_url($url, $cookie = null, $referer = null, $head = 0, $post = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($ch, CURLOPT_HEADER, $head);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (! empty($post)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        if (! empty($cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if (! empty($referer)) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $data = array();
        $data[0] = curl_exec($ch);
        $data[1] = curl_errno($ch);
        curl_close($ch);
        return $data;
    }

    function getBaomingMsg($number, $cookie)
    {
        $url = $this->url . 'bmxmb.aspx?xh=' . $number;
        $referer = $this->url . 'xs_main.aspx?xh=' . $number;
        $data = $this->visit_url($url, $cookie, $referer);
        
        $data = iconv("gbk", "utf-8", $data[0]);
        $matches = array();
        $Baominglist = '';
        $yiBaoming = '';
        $BaomingData = array();
        preg_match_all('/<input.*?>/ims', $data, $matches);
        preg_match('/<table class=\"datelist\".*?id=\"DBGrid\".*?>(.*?)<\/table>/ims', $data, $BaomingData);
        $_block = array();
        if (preg_match_all('/<tr(?:.*?)?>(.*?)<\/tr>/ims', $BaomingData[1], $_block)) {
            $flag = 0;
            $Baoming = array();
            foreach ($_block[0] as $v) {
                if ($flag) {
                    preg_match_all('/<td(.*?)>(.*?)<\/td>/ims', $v, $Baoming);
                    $Baoming[2][0] = str_replace('__doPostBack', '__showzkzh', $Baoming[2][0]);
                    $Baominglist .= '<tr><td>' . $Baoming[2][0] . '</td><td>' . $Baoming[2][1] . '</td><td>' . $Baoming[2][4] . '</td></tr>';
                } else {
                    $flag ++;
                }
            }
        }
        $Baominged = array();
        if (preg_match('/<table class=\"datelist\".*?id=\"DBGridInfo\".*?>(.*?)<\/table>/ims', $data, $Baominged)) {
            if (preg_match_all('/<tr(?:.*?)?>(.*?)<\/tr>/ims', $Baominged[1], $_block)) {
                $flag = 0;
                $Baomingeded = array();
                foreach ($_block[0] as $v) {
                    if ($flag) {
                        preg_match_all('/<td(.*?)>(.*?)<\/td>/ims', $v, $Baomingeded);
                        $yiBaoming .= '<tr><td>' . $Baomingeded[2][0] . '</td><td>';
                        $yiBaoming .= $Baomingeded[2][1] . '</td><td>';
                        $yiBaoming .= $Baomingeded[2][4] . '</td><td>';
                        $yiBaoming .= $Baomingeded[2][6] . '</td><td>';
                        $yiBaoming .= $Baomingeded[2][8] . '</td><td>';
                        $yiBaoming .= $Baomingeded[2][9] . '</td></tr>';
                    } else {
                        $flag ++;
                    }
                }
            }
        }
        preg_match('/id="Labsfzh">(.*?)<\/span>/ims', $data, $sfz);
        $resData = array();
        $resData['list'] = $Baominglist;
        $resData['yi'] = $yiBaoming;
        $resData['match'] = $matches;
        $resData['sfz'] = $sfz[1];
        return $resData;
    }
}