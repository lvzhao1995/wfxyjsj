<?php
namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller
{

    private $token;

    private $encodingaeskey;

    private $appid;

    private $timestamp;

    private $encrypt_type = '';

    private $nonce;

    private $weixin;

    private $username = '1';

    private $msgid;

    public function _before_index()
    {
        $wechatCon = M('manage');
        $data = $wechatCon->field('token,aeskey,appid,id')->find();
        $this->token = $data['token'];
        $this->encodingaeskey = $data['aeskey'];
        $this->appid = $data['appid'];
        $this->weixin = $data['id'];
        $this->timestamp = I('get.timestamp');
        $this->encrypt_type = I('get.encrypt_type');
        $this->nonce = I('get.nonce');
    }

    public function index()
    {
        if (isset($_GET['echostr'])) {
            $this->valid();
        } else {
            $this->dealMsg();
        }
    }

    private function valid()
    {
        $echoStr = I('get.echostr');
        if ($this->checkSignature()) {
            echo $echoStr;
            exit();
        }
    }

    private function checkSignature()
    {
        $signature = I('get.signature');
        
        $tmpArr = array(
            $this->token,
            $this->timestamp,
            $this->nonce
        );
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    private function dealMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        
        if (! empty($postStr)) {
            if ($this->encrypt_type == 'aes') {
                $crypt = new WXBizMsgCrypt($this->token, $this->encodingaeskey, $this->appid);
                $postData = '';
                $crypt->decryptMsg(I('get.msg_signature'), $this->timestamp, $this->nonce, $postStr, $postData);
                $postStr = $postData;
            }
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($postObj->ToUserName != $this->weixin) {
                $this->respondText('参数错误，请重试');
            } else {
                $this->username = $postObj->FromUserName;
                $this->msgid = $postObj->MsgId;
                $msgType = trim($postObj->MsgType);
                switch ($msgType) {
                    case "voice":
                        $this->receiveVoice($postObj);
                        break;
                    case "text":
                        $this->receiveText($postObj->Content);
                        break;
                    case "event":
                        $this->receiveEvent($postObj);
                        break;
                    default:
                        $this->respondText('您的消息我们已经收到，我们将在24小时内回复');
                }
            }
        } else {
            echo "";
            exit(0);
        }
    }

    private function receiveVoice($object)
    {
        if (isset($object->Recognition)) {
            $this->receiveText($object->Recognition);
        } else {
            $this->respondText('您的消息我们已经收到，我们将在24小时内回复');
        }
    }

    private function receiveText($keyword)
    {
        $qian = array(
            " ",
            "　",
            "\t",
            "\n",
            "\r",
            "\x0B",
            "\0"
        );
        $keyword = str_replace($qian, '', $keyword);
        $keyword = trim($keyword, '!.！');
        
        $respond = M('reply');
        
        $sql = $respond->field('replytype,content')
            ->where("'%s' like concat(`keyword`,'%%') and `mode`=1", $keyword)
            ->fetchSql(true)
            ->find();
        $resData = $respond->field('replytype,content')
            ->where(array(
            'keyword' => ':keyword',
            'mode' => 0
        ))
            ->bind(':keyword', $keyword)
            ->union($sql)
            ->find();
        if (! empty($resData)) {
            $this->respondMsg($resData);
        } else {
            $respond = M('app');
            $sql = $respond->field('classname')
                ->where("'%s' like concat(`keyword`,'%%') and `mode`=1", $keyword)
                ->fetchSql(true)
                ->find();
            $resData = $respond->field('classname')
                ->where(array(
                'keyword' => ':keyword',
                'mode' => 0
            ))
                ->bind(':keyword', $keyword)
                ->union($sql)
                ->find();
            if (! empty($resData)) {
                $this->runApp($resData['classname'], $keyword);
            } else {
                $respond = M('forward');
                $sql = $respond->field('url,token')
                    ->where("'%s' like concat(`keyword`,'%%') and `mode`=1", $keyword)
                    ->fetchSql(true)
                    ->find();
                $resData = $respond->field('url,token')
                    ->where(array(
                    'keyword' => ':keyword',
                    'mode' => 0
                ))
                    ->bind(':keyword', $keyword)
                    ->union($sql)
                    ->find();
                if (! empty($resData)) {
                    $this->forward($keyword, $resData['url'], $resData['token']);
                } else {
                    $resData = $respond->field('url,token')
                        ->where('`ordernum`=-1')
                        ->find();
                    if (! empty($resData)) {
                        $this->forward($keyword, $resData['url'], $resData['token'], true);
                    } else {
                        $respond = M('reply');
                        $resData = $respond->field('replytype,content')
                            ->where('`keywrd`="nofind"')
                            ->find();
                        if (!empty($resData)) {
                            $this->respondMsg($resData);
                        } else {
                            echo '';
                            exit(0);
                        }
                    }
                }
            }
        }
    }

    private function receiveEvent($object)
    {
        switch ($object->Event) {
            case 'subscribe':
                $this->receiveText('subscribe');
                break;
            case 'unsubscribe':
                $this->unsubscribeEvent();
                break;
            case 'CLICK':
                $this->receiveText($object->EventKey);
            default:
                echo '';
        }
    }

    private function unsubscribeEvent()
    {
        $classname = '';
        include __DIR__ . '/dbconfig.php';
        $stmt = $mysqli->prepare('select `classname` from `app` where `unsubscribe`=1');
        $stmt->execute();
        $stmt->bind_result($classname);
        while ($stmt->fetch()) {
            $this->runApp($classname, 'unsubscribe');
        }
        $stmt->close();
        $mysqli->close();
    }

    private function respondText($contentStr)
    {
        $time = time();
        $contentStr = str_replace('[openid]', $this->username, $contentStr);
        $contentStr = $this->emoji2utf8($contentStr);
        $resultStr = '<xml>
                    <ToUserName><![CDATA[' . $this->username . ']]></ToUserName>
                    <FromUserName><![CDATA[' . $this->weixin . ']]></FromUserName>
                    <CreateTime>' . $time . '</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[' . $contentStr . ']]></Content>
                    </xml>';
        if ($this->encrypt_type == 'aes') {
            include_once __DIR__ . '/class/wxBizMsgCrypt.php';
            $encrypt = new WXBizMsgCrypt($this->token, $this->encodingaeskey, $this->appid);
            $encryptMsg = '';
            $encrypt->encryptMsg($resultStr, $this->timestamp, $this->nonce, $encryptMsg);
            echo $encryptMsg;
        } else {
            echo $resultStr;
        }
    }

    private function respondNews($content)
    {
        $newsItem = json_decode($content, true);
        $articleCount = count($newsItem);
        $time = time();
        $resultStr = '<xml>
            <ToUserName><![CDATA[' . $this->username . ']]></ToUserName>
            <FromUserName><![CDATA[' . $this->weixin . ']]></FromUserName>
            <CreateTime>' . $time . '</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <ArticleCount>' . $articleCount . '</ArticleCount>
            <Articles>';
        foreach ($newsItem as $item) {
            $item['url'] = str_replace('[openid]', $this->username, $item['url']);
            $resultStr .= '<item>';
            if (isset($item['title']) && $item['title'] != '') {
                $resultStr .= '<Title><![CDATA[' . $item['title'] . ']]></Title>';
            }
            if (isset($item['description']) && $item['description'] != '') {
                $resultStr .= '<Description><![CDATA[' . $item['description'] . ']]></Description>';
            }
            if (isset($item['picurl']) && $item['picurl'] != '') {
                $resultStr .= '<PicUrl><![CDATA[' . $item['picurl'] . ']]></PicUrl>';
            }
            if (isset($item['url']) && $item['url'] != '') {
                $resultStr .= '<Url><![CDATA[' . $item['url'] . ']]></Url>';
            }
            $resultStr .= '</item>';
        }
        $resultStr .= '</Articles>
            </xml>';
        if ($this->encrypt_type == 'aes') {
            include_once __DIR__ . '/class/wxBizMsgCrypt.php';
            $encrypt = new WXBizMsgCrypt($this->token, $this->encodingaeskey, $this->appid);
            $encryptMsg = '';
            $encrypt->encryptMsg($resultStr, $this->timestamp, $this->nonce, $encryptMsg);
            echo $encryptMsg;
        } else {
            echo $resultStr;
        }
    }

    private function respondMsg($resData)
    {
        switch ($resData['replytype']) {
            case '0':
                $this->respondText($resData['content']);
                break;
            case '1':
                $this->respondNews($resData['content']);
                break;
            default:
                echo '';
        }
    }

    private function emoji2utf8($content)
    {
        $content = preg_replace_callback('/(\\\u([\w]{4}))/', function ($matches) {
            if (! empty($matches)) {
                $name = '';
                
                $str = $matches[0];
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
            return $name;
        }, $content);
        return $content;
    }

    private function runApp($classname, $key)
    {
        include __DIR__ . '/class/' . $classname . '/index.php';
        $app = eval('return new ' . $classname . ';');
        $res = $app->setKey($key, $this->username);
        $this->respondMsg($res);
    }

    private function forward($keyword, $url, $token, $tj = false)
    {
        $time = time();
        $text = '<xml>
                 <ToUserName><![CDATA[' . $this->weixin . ']]></ToUserName>
                 <FromUserName><![CDATA[' . $this->username . ']]></FromUserName>
                 <CreateTime>' . $time . '</CreateTime>
                 <MsgType><![CDATA[text]]></MsgType>
                 <Content><![CDATA[' . $keyword . ']]></Content>
                 <MsgId>' . $this->msgid . '</MsgId>
               </xml>';
        
        $tmpArr = array(
            $token,
            $time,
            $this->nonce
        );
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        
        $url .= '&nonce=' . $this->nonce . '&timestamp=' . $time . '&signature=' . $tmpStr;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $text);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $data = curl_exec($ch);
        $flag = 0;
        while (curl_errno($ch) != 0 && $flag < 4) {
            $data = curl_exec($ch);
            echo curl_error($ch);
            $flag ++;
        }
        if (curl_errno($ch) != 0) {
            echo '';
        } elseif ($tj) {
            $resText = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($resText->MsgType == 'text') {
                $this->respondText($resText->Content . "\n\n本消息为自动回复，可发送消息继续聊天。\n有问题请等待人工回复或<a href='http://sighttp.qq.com/authd?IDKEY=0c6214d0db3daaace325de4a71b9216ada134d37be10c7bc'>点击进行qq咨询</a>");
            } else {
                if ($this->encrypt_type == 'aes') {
                    include_once __DIR__ . '/class/wxBizMsgCrypt.php';
                    $encrypt = new WXBizMsgCrypt($this->token, $this->encodingaeskey, $this->appid);
                    $encryptMsg = '';
                    $encrypt->encryptMsg($data, $this->timestamp, $this->nonce, $encryptMsg);
                    echo $encryptMsg;
                } else {
                    echo $data;
                }
            }
        } else {
            if ($this->encrypt_type == 'aes') {
                include_once __DIR__ . '/class/wxBizMsgCrypt.php';
                $encrypt = new WXBizMsgCrypt($this->token, $this->encodingaeskey, $this->appid);
                $encryptMsg = '';
                $encrypt->encryptMsg($data, $this->timestamp, $this->nonce, $encryptMsg);
                echo $encryptMsg;
            } else {
                echo $data;
            }
        }
        curl_close($ch);
    }
}