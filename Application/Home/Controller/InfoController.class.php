<?php
namespace Home\Controller;

use Think\Controller;

class InfoController extends Controller
{

    private $url;

    private $selfurl;

    public function _initialize()
    {
        $this->url = C('INFO_URL');
        $this->selfurl = 'http://' . $_SERVER['HTTP_HOST'];
    }

    function setKey($keyword, $openid)
    {
        switch ($keyword) {
            case '余额':
                $content = $this->Yue($openid);
                break;
            case '取消绑定':
                if ($this->unBind($openid)) {
                    $content = '取消绑定成功！如需要重新绑定请<a href="' . $this->selfurl . U('View/Login/index', 'openid=' . $openid) . $openid . '">点击绑定</a>';
                } else {
                    $content = '你没有绑定！如果需要绑定请<a href="' . $this->selfurl . U('View/Login/index', 'openid=' . $openid) . $openid . '">点击绑定</a>';
                }
                break;
            case 'unsubscribe':
                $this->unBind($openid);
                return '';
        }
        $resdata = array();
        $resdata['replytype'] = 0;
        $resdata['content'] = $content;
        return $resdata;
    }

    public function getCookie($cookie)
    {
        $matches = array();
        $data = $this->visitUrl($this->url, $cookie);
        preg_match_all('/Set\-Cookie:([^;]*);/', $data[0], $matches);
        $cookie = substr($matches[1][2], 1);
        return $cookie;
    }

    function bind($openid, $number, $password)
    {
        $flag = $this->login($number, $password);
        if ($flag == 404) {
            return 404;
        } elseif ($flag != 400) {
            $db = M('Info');
            $db->where('openid=:openid')
                ->bind(':openid', $openid)
                ->delete();
            $encrypt = new \Home\Controller\do_encrypt();
            $data = array(
                'studentid' => $number,
                'password' => $encrypt->encrypt($password)
            );
            
            if ($db->create($data)) {
                if ($db->add()) {
                    return 0;
                } else {
                    return 402;
                }
            } else {
                return 403;
            }
        } else {
            return 400;
        }
    }

    private function login($number, $password)
    {
        $url = $this->url . 'userPasswordValidate.portal';
        $referer = $this->url . 'index.portal';
        $post_data = array(
            'Login.Token1' => $number,
            'Login.Token2' => $password
        );
        $data = $this->visitUrl($url, null, $referer, 1, $post_data);
        
        if ($data[1] > 0) {
            return 404;
        }
        $matches = array();
        preg_match_all('/handleLogin([^\(]*)\(/', $data[0], $matches);
        if (substr($matches[1][0], 0) == 'Failure') {
            return 400;
        } else {
            preg_match_all('/Set\-Cookie:([^;]*);/', $data[0], $matches);
            $cookie = substr($matches[1][0], 1);
            return $cookie;
        }
    }

    private function information($cookie)
    {
        $url = $this->url . 'pnull.portal?.pmn=view&.ia=false&action=informationCenterAjax&.f=f1104&.pen=pe162';
        $cookie = $this->getCookie($cookie) . '; ' . $cookie;
        $data = $this->visitUrl($url, $cookie, null, 0);
        
        $resData = json_decode($data[0], true);
        return $resData;
    }

    private function Yue($openid)
    {
        $cookie = $this->isBind($openid);
        if (! $cookie) {
            return '需要绑定才能使用此功能，<a href="' . $this->selfurl . U('View/Login/index', 'openid=' . $openid) . '">点击绑定</a>';
        } elseif ($cookie == 404) {
            return '学校的服务器似乎出了点问题呢，稍后再试吧\ue403';
        }
        $array = $this->information($cookie);
        $res = strip_tags($array[1]['description']);
        return $res;
    }

    function isBind($openid)
    {
        $db = M('Info');
        $data = $db->getFieldByOpenid($openid, 'studentid,password');
        if (! empty($data)) {
            $jiami = new do_encrypt();
            $cookie = $this->login(key($data), $jiami->decrypt(current($data)));
            if ($cookie) {
                return $cookie;
            } else {
                $db->where('openid=:openid')
                    ->bind(':openid', $openid)
                    ->delete();
                return 403;
            }
        } else {
            return 403;
        }
    }

    function unBind($openid)
    {
        $db = M('info');
        $res = $db->where(array(
            'openid' => ':openid'
        ))
            ->bind(':openid', $openid)
            ->delete();
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    public function getJwcCookie($openid)
    {
        $cookie = $this->isBind($openid);
        if ($cookie!=403 && $cookie != 404) {
            $data = $this->visitUrl(C('JWC_URL') . 'default_zzjk.aspx', $cookie);
            preg_match_all('/Set\-Cookie:([^;]*);/', $data[0], $matches);
            $cookie = substr($matches[1][0], 1);
        }
        return $cookie;
    }

    public function getLibCookie($openid)
    {
        $cookie = $this->isBind($openid);
        if ($cookie!=403 && $cookie != 404) {
            $data = $this->visitUrl(C('LIB_URL') . 'reader/hwthau.php', $cookie);
            preg_match_all('/Set\-Cookie:([^;]*);/', $data[0], $matches);
            $cookie = substr($matches[1][0], 1);
        }
        return $cookie;
    }

    private function visitUrl($url, $cookie = null, $referer = null, $head = 1, $post = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($ch, CURLOPT_HEADER, $head);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (! empty($cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if (! empty($referer)) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        if (! empty($post)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $data = curl_exec($ch);
        $data = array();
        $data[0] = curl_exec($ch);
        $data[1] = curl_errno($ch);
        curl_close($ch);
        return $data;
    }
}