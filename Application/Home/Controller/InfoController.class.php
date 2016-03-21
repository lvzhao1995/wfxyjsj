<?php
namespace Home\Controller;

use Think\Controller;
use Home\Common\do_encrypt;
/**
 * 与信息门户相关的操作
 * @author lvzhao1995
 *
 */
class InfoController extends Controller
{

    private $url;

    private $selfurl;
/**
 * 获取信息门户URL
 */
    public function _initialize()
    {
        $this->url = C('INFO_URL');
        $this->selfurl = 'http://' . $_SERVER['HTTP_HOST'];
    }
/**
 * 入口方法，处理用户消息
 * @param string $keyword 用户发送的消息
 * @param string $openid 用户openid
 */
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
/**
 * 获取用户读取信息门户信息使用的cookie
 * @param string $cookie 登录时的cookie
 * @return string
 */
    public function getCookie($cookie)
    {
        $matches = array();
        $data = $this->visitUrl($this->url, $cookie);
        preg_match_all('/Set\-Cookie:([^;]*);/', $data[0], $matches);
        $cookie = substr($matches[1][2], 1);
        return $cookie;
    }
/**
 * 用户绑定信息门户
 * @param string $openid 用户openid
 * @param string $number 学号
 * @param string $password 密码
 * @return int|string 错误码或登录后的cookie
 */
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
            $encrypt = new do_encrypt();
            $data = array('studentid' => $number,'password' => $encrypt->encrypt($password),'openid' => $openid
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
/**
 * 登录信息门户
 * @param string $number 学号
 * @param string $password 密码
 * @return number|string 错误码或cookie
 */
    private function login($number, $password)
    {
        $url = $this->url . 'userPasswordValidate.portal';
        $referer = $this->url . 'index.portal';
        $post_data = array('Login.Token1' => $number,'Login.Token2' => $password
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
/**
 * 获取用户的信息门户通知信息
 * @param string $cookie 登录的cookie
 * @return mixed
 */
    private function information($cookie)
    {
        $url = $this->url . 'pnull.portal?.pmn=view&.ia=false&action=informationCenterAjax&.f=f1104&.pen=pe162';
        $cookie = $this->getCookie($cookie) . '; ' . $cookie;
        $data = $this->visitUrl($url, $cookie, null, 0);
        
        $resData = json_decode($data[0], true);
        return $resData;
    }
/**
 * 获取校园卡余额
 * @param string $openid 用户openid
 * @return string
 */
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
/**
 * 判断用户是否绑定
 * @param string $openid 用户openid
 * @return int|string 错误码或cookie
 */
    function isBind($openid)
    {
        $db = M('Info');
        $data = $db->field('studentid,password')
            ->where(array('openid' => ':openid'
        ))
            ->bind(':openid', $openid)
            ->find();
        if (! empty($data)) {
            $jiami = new do_encrypt();
            $cookie = $this->login($data['studentid'], $jiami->decrypt($data['password']));
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
/**
 * 解除绑定
 * @param string $openid 用户openid
 * @return boolean
 */
    function unBind($openid)
    {
        $db = M('info');
        $res = $db->where(array('openid' => ':openid'
        ))
            ->bind(':openid', $openid)
            ->delete();
        if ($res) {
            return true;
        } else {
            return false;
        }
    }
/**
 * 通过信息门户获取教务处cookie
 * @param string $openid 用户openid
 * @return mixed
 */
    public function getJwcCookie($openid)
    {
        $cookie = $this->isBind($openid);
        if ($cookie != 403 && $cookie != 404) {
            $data = $this->visitUrl(C('JWC_URL') . 'default_zzjk.aspx', $cookie);
            preg_match_all('/Set\-Cookie:([^;]*);/', $data[0], $matches);
            $cookie = substr($matches[1][0], 1);
        }
        return $cookie;
    }
/**
 * 通过信息门户获取图书馆cookie
 * @param string $openid 用户openid
 * @return mixed
 */
    public function getLibCookie($openid)
    {
        $cookie = $this->isBind($openid);
        if ($cookie != 403 && $cookie != 404) {
            $data = $this->visitUrl(C('LIB_URL') . 'reader/hwthau.php', $cookie);
            preg_match_all('/Set\-Cookie:([^;]*);/', $data[0], $matches);
            $cookie = substr($matches[1][0], 1);
        }
        return $cookie;
    }
/**
 * 访问URL并获取内容
 * @param string $url 需要访问的url
 * @param string $cookie 需要带上的cookie
 * @param string $referer referer参数
 * @param number $head 是否获取head信息
 * @param array $post 需要post提交的数据
 */
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