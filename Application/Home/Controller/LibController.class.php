<?php
namespace Home\Controller;

use Think\Controller;

class LibController extends Controller
{

    private $url;

    private $captcha = '';

    private $selfurl;

    public function _initialize()
    {
        $this->url = C('LIB_URL');
        $this->selfurl = 'http://' . $_SERVER['HTTP_HOST'];
    }

    function seach($strText, $SearchType = 'title', $respond = TRUE)
    {
        $strText = urlencode($strText);
        $url1 = $this->url . 'opac/openlink.php?strSearchType=' . $SearchType . '&match_flag=forward&historyCount=1&doctype=ALL&with_ebook=off&strText=' . $strText;
        if ($respond === true) {
            $data = $this->visit_url($url1, null, 0, 2);
        } else {
            $data = $this->visit_url($url1, null, 0, 0);
        }
        $bookdetail = array();
        if ($data[1] == 0) {
            $seach = preg_replace('/<a([^>]*)>/', '', $data[0]);
            $seach = str_replace('</a>', '', $seach);
            $seach = preg_replace('/<img([^>]*)>/', '', $seach);
            $book = array();
            preg_match_all('/<ol(.*?)id=\"search_book_list\">(.*?)<\/ol>/ims', $seach, $book);
            $booklist = array();
            preg_match_all('/<li class=.*?<\/span>[0-9]*[.](.*?)<\/h3>.*?<span>(.*?)<\/span>(.*?)<br \/>(.*?)<br.*?<div id=\"detail([0-9]*).*?<\/li>/ims', $book[2][0], $booklist);
            $j = count($booklist[1]);
            if ($j > 9 && $respond) {
                $j = 9;
            } elseif ($j == 0) {
                $bookdetail['status'] = 401;
                return $bookdetail;
            }
        } else {
            $bookdetail['status'] = 404;
            return $bookdetail;
        }
        $qian = array(
            ' ',
            '　',
            '\t',
            '\n',
            '\r',
            '&nbsp;'
        );
        $hou = array(
            '',
            '',
            '',
            '',
            '',
            ''
        );
        $bookdetail['status'] = 0;
        for ($i = 0; $i < $j; $i ++) {
            $bookdetail['data'][$i]['name'] = $this->unicode_decode(str_replace($qian, $hou, $booklist[1][$i]));
            $bookdetail['data'][$i]['num'] = str_replace(array('<br>','复本'), array(' ',''), str_replace($qian, $hou, $booklist[2][$i]));
            $bookdetail['data'][$i]['people'] = $this->unicode_decode(str_replace($qian, $hou, $booklist[3][$i]));
            $bookdetail['data'][$i]['press'] = $this->unicode_decode(str_replace($qian, $hou, $booklist[4][$i]));
            $bookdetail['data'][$i]['marc_no'] = $this->unicode_decode(str_replace($qian, $hou, $booklist[5][$i]));
        }
        return $bookdetail;
    }

    private function news($key, $openid)
    {
        $bookResult = $this->seach($key);
        $bookInfo = array();
        $bookInfo[0]['title'] = '潍坊学院图书馆检索系统';
        $bookInfo[0]['url'] = $this->selfurl . U('View/Jiansuo/index', 'openid=' . $openid);
        $i = 1;
        if ($bookResult['status'] == 404) {
            $bookInfo[$i]['title'] = '由于图书馆服务器响应时间过长，请点击使用网页版查询';
            $bookInfo[$i]['picurl'] = 'http://mmbiz.qpic.cn/mmbiz/vyqUV3qbLgYm0mmyDfqINh4Sz2CjHshnvRHhyNAnq3Wpv6DTZILIPxC7yuB1QXWM71GmF2QiceoAE9KNXmcPEbQ/0';
            $bookInfo[$i]['url'] = $this->selfurl . U('View/Jiansuo/index', 'openid=' . $openid . '&keyword=' . $key);
        } elseif ($bookResult['status'] == 401) {
            $bookInfo[$i]['title'] = '没有找到相关图书，请更换关键词后重试';
        } else {
            foreach ($bookResult['data'] as $v) {
                $bookInfo[$i]['title'] = '【' . $v['name'] . "】\n" . $v['num'] . "\n" . $v['people'] . "\n" . $v['press'];
                $bookInfo[$i]['picurl'] = 'http://mmbiz.qpic.cn/mmbiz/vyqUV3qbLgYm0mmyDfqINh4Sz2CjHshnvRHhyNAnq3Wpv6DTZILIPxC7yuB1QXWM71GmF2QiceoAE9KNXmcPEbQ/0';
                $bookInfo[$i]['url'] = $this->selfurl . U('View/Bookinfo/index', 'marc_no=' . $v['marc_no'] . '&openid=' . $openid);
            }
        }
        
        return $bookInfo;
    }

    private function unicode_decode($name)
    {
        $name = str_replace(array(
            '&#x',
            ';'
        ), array(
            '\\u',
            ''
        ), $name);
        $pattern = '/([\w]+)|(\\\u([\w]{4}))/i';
        $matches = array();
        preg_match_all($pattern, $name, $matches);
        if (! empty($matches)) {
            $name = '';
            for ($j = 0; $j < count($matches[0]); $j ++) {
                $str = $matches[0][$j];
                if (strpos($str, '\\u') === 0) {
                    $code = base_convert(substr($str, 2, 2), 16, 10);
                    $code2 = base_convert(substr($str, 4), 16, 10);
                    $c = chr($code) . chr($code2);
                    $c = iconv('UCS-2BE', 'UTF-8', $c);
                    $name .= $c;
                } else {
                    $name .= $str;
                }
            }
        }
        return $name;
    }

    function getLibData($cookie)
    {
        $url = $this->url . 'reader/book_lst.php';
        $data = $this->visit_url($url, $cookie);
        $data['cookie'] = $cookie;
        return $data;
    }

    function trimData($data)
    {
        $tushu = array();
        $book = array();
        if (preg_match('/<table width=\"100%\".*?>(.*?)<\/table>/ims', $data[0], $tushu)) {
            if (preg_match_all('/<tr>(.*?)<\/tr>/ims', $tushu[1], $tushu)) {
                $flag = 0;
                $book['chaoqi'] = false;
                foreach ($tushu[1] as $v) {
                    if ($flag) {
                        $str_block = array();
                        preg_match_all('/<td.*?>(.*?)<\/td>/ims', $v, $str_block);
                        $tmp = array();
                        preg_match('/<a.*?>(.*?)<\/a>/ims', $str_block[1][1], $tmp);
                        $book['data'][$flag]['name'] = $this->unicode_decode($tmp[1]);
                        $book['data'][$flag]['time'] = preg_replace('/<font.*?>|<\/font>| /', '', $str_block[1][3]);
                        if ((time() + 24 * 60 * 60) > strtotime($book[$flag]['time'])) {
                            $book['chaoqi'] = true;
                        }
                        $book['data'][$flag]['jieci'] = $str_block[1][4];
                        $book['data'][$flag]['shuku'] = $str_block[1][5];
                        preg_match('/getInLib.*?;/', $str_block[1][7], $tmp);
                        $replace = ',"' . $data['cookie'] . '");';
                        $tmp[0] = str_replace(');', $replace, $tmp[0]);
                        $book['data'][$flag]['xujie'] = '$this->' . $tmp[0];
                    }
                    $flag ++;
                }
            }
            $book['count'] = $flag - 1;
        } else {
            $book['count'] = 0;
        }
        return $book;
    }

    private function getInLib($barcode, $check, $num, $cookie)
    {
        if (empty($this->captcha)) {
            $this->getCaptcha($cookie);
        }
        $xujie_url = $this->url . 'reader/ajax_renew.php?bar_code=' . $barcode . '&check=' . $check . '&captcha=' . $this->captcha . '&time=' . (time() * 1000);
        $data = $this->visit_url($xujie_url, $cookie, 0);
        return $data[0];
    }

    private function getCaptcha($cookie)
    {
        $yzm_url = $this->url . 'reader/captcha.php';
        $data = $this->visit_url($yzm_url, $cookie, 0);
        $tempfname = tempnam(sys_get_temp_dir(), 'yzm');
        $fp = fopen($tempfname, 'w');
        fwrite($fp, $data[0]);
        fclose($fp);
        $yzm = new valite();
        $yzm->setImage($tempfname);
        $yzm->getHec();
        $this->captcha = $yzm->run();
        unlink($tempfname);
    }

    function setKey($key, $openid)
    {
        $Result = array();
        if (mb_substr($key, 0, 2, 'UTF-8') == '检索') {
            $keyword = mb_substr($key, 2, 100, 'UTF-8');
            if (empty($keyword)) {
                $Result['replytype'] = 0;
                $Result['content'] = '请发送“检索+书名”使用图书检索系统/调皮例如：检索西游记\ue056';
                return $Result;
            } else {
                $Result['replytype'] = 1;
                $Result['content'] = json_encode($this->news($keyword, $openid));
                return $Result;
            }
        } else {
            $cookie = R('Info/getLibCookie', array($openid));
            if ($cookie === 403) {
                $Result['replytype'] = 0;
                $Result['content'] = '需要绑定才能使用此功能，<a href="' . $this->selfurl . U('View/Login/index', 'openid=' . $openid) . '">点击绑定</a>';
                return $Result;
            }
            $data = $this->getLibData($cookie);
            if ($data[1]) {
                $Result['replytype'] = 0;
                $Result['content'] = '学校的服务器出问题了呢\ue403过一段时间再试吧';
                return $Result;
            }
            $book = $this->trimData($data);
            if ($key == '续借') {
                $return_str = $this->xujie($book);
            } elseif ($key == '图书') {
                if ($book['count'] == 0) {
                    $return_str .= '一本书都不借，真浪费图书馆/敲打';
                } else {
                    $return_str .= '当前借阅' . $book['count'] . "本书\n-----------------";
                    foreach ($book['data'] as $c => $v) {
                        $xvjie = strtotime($v['time']) - 2592000;
                        $return_str .= "\n" . '\ue148书名：' . $v['name'] . "\n";
                        $return_str .= '\ue026应还日期：' . $v['time'] . ((strtotime($v['time']) + 24 * 60 * 60) < time() ? '超期!' : '') . "\n";
                        $return_str .= '\ue038书库：' . $v['shuku'] . "\n";
                        $return_str .= '\ue232是否可续借:' . ((! $v['jieci']) && $xvjie < time() && (! $book['chaoqi']) ? '是' : '否') . "\n" . '-----------------';
                    }
                    if ($book['chaoqi']) {
                        $return_str .= '存在超期图书，请尽快处理！';
                    }
                }
            }
            $Result['replytype'] = 0;
            $Result['content'] = $return_str;
            return $Result;
        }
    }

    private function visit_url($url, $cookie = null, $head = 1, $timeout = 3)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($ch, CURLOPT_HEADER, $head);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($timeout != 0) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        }
        if ($cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        $data = array();
        $data[0] = curl_exec($ch);
        $data[1] = curl_errno($ch);
        curl_close($ch);
        return $data;
    }

    function xujie($book)
    {
        $return_str = '';
        if ($book['count'] == 0) {
            $return_str .= '还没借书就想续借我真的做不了\ue407';
        } elseif ($book['chaoqi']) {
            $return_str .= '存在超期图书！请尽快处理！在此之前无法续借\ue40f发送“图书”查看详情';
        } else {
            $return_str .= '当前在借' . $book['count'] . '本书';
            $chenggong = $shibai = $chaoqi = 0;
            foreach ($book as $c => $v) {
                    $xvjie = strtotime($v['time']) - 2592000;
                    if ((! $v['jieci']) && $xvjie < time()) {
                        $data = eval('return ' . $v['xujie']);
                        $chenggong ++;
                    } else {
                        $shibai ++;
                    }
            }
            if ($chenggong) {
                $return_str .= '，续借成功' . $chenggong . '本';
            }
            if ($shibai) {
                $return_str .= '，因为已续借过或未到续借时间而不能续借' . $shibai . '本';
            }
        }
        return $return_str;
    }

    function getBookInfo($marc_no)
    {
        $url = $this->url . '/opac/item.php?marc_no=' . $marc_no;
        $data = $this->visit_url($url, null, 0);
        if ($data[1] == 0) {
            $data = preg_replace('/<a([^>]*)>/', '', $data[0]);
            $data = str_replace('</a>', '', $data);
            $data = preg_replace('/<img([^>]*)>/', '', $data);
            $data = preg_replace('/class=\"[^\"]*?\"/', '', $data);
            $data = preg_replace('/width=\"[^\"]*?\"/', '', $data);
            $book = array();
            preg_match_all('/<table(.*?)id=\"item\">(.*?)<\/table>/ims', $data, $book);
            $booklist = str_replace('<td >年卷期</td>', '', $book[0][0]);
            $find = '/<td.*?&nbsp.*?<\/td>/';
            $booklist = preg_replace($find, '', $booklist);
            $booklist = str_replace('(点击展开详细)', '', $booklist);
            $booklist = preg_replace('/title=\"[^\"]*?\"/', '', $booklist);
            preg_match_all('/<div id=\"item_detail\"(.*?)>(.*?)<\/div>/ims', $data, $book);
            $book[0][0] = str_replace('<div style=\"text-align:left;color:blue;\" id=\"showMoreAnchor\" ><strong>全部MARC细节信息>></strong>', '', $book[0][0]);
            $book[0][0] = str_ireplace('<dt>ISBN', '<dt id="isbn">ISBN', $book[0][0]);
            $resData = array(
                'list' => $booklist,
                'info' => $book[0][0]
            );
            return $resData;
        } else {
            return 404;
        }
    }

    function weizhang($cookie)
    {
        $url = $this->url . 'reader/fine_pec.php';
        $data = $this->visit_url($url, $cookie);
        if (preg_match_all('/<table.*?>.*?<\/table>/ims', $data[0], $data)) {
            if (preg_match_all('/<tr>(.*?)<\/tr>/ims', $data[0][0], $data)) {
                $info = array();
                $i = 1;
                $qian = array(
                    ' ',
                    '　',
                    '\t',
                    '\n',
                    '\r',
                    '&nbsp;'
                );
                $resData = array();
                $marc_no = array();
                $name = array();
                foreach ($data[1] as $book) {
                    preg_match_all('/<td.*?>(.*?)<\/td>/ims', $book, $info);
                    preg_match('/<a.*?marc_no\=([0-9]*)\"/ims', $info[1][2], $marc_no);
                    preg_match('/<a.*?>(.*?)<\/a>/ims', $info[1][2], $name);
                    $resData['data'][$i]['name'] = $this->unicode_decode(str_replace($qian, '', $name[1]));
                    $resData['data'][$i]['marc_no'] = $marc_no[1];
                    $resData['data'][$i]['date'] = str_replace($qian, '', $info[1][5]);
                    $resData['data'][$i]['guanc'] = str_replace($qian, '', $info[1][6]);
                    $resData['data'][$i]['need'] = str_replace($qian, '', $info[1][7]);
                    $resData['data'][$i]['zhuangtai'] = str_replace($qian, '', $info[1][9]);
                    $i ++;
                }
            }
        } else {
            $resData['status'] = '没有违章信息！';
        }
        return $resData;
    }

}