<?php

namespace Home\Controller;

use Think\Controller; 
use Home\Common\WXBizMsgCrypt;
/**
 * 处理微信消息
 * @author lvzhao1995
 *
 */
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
/**
 * 获取公众号相关信息，供后续方法使用
 */
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
/**
 * 判断当前请求是否为验证token
 */
    public function index()
    {
        if (isset($_GET['echostr'])) {
            $this->valid();
        } else {
            $this->dealMsg();
        }
    }
/**
 * 配合验证token
 */
    private function valid()
    {
        $echoStr = I('get.echostr');
        if ($this->checkSignature()) {
            echo $echoStr;
            exit();
        }
    }
/**
 * 验证消息是否合法
 */
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
/**
 * 判断消息类型，选择方法进行下一步处理
 */
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
/**
 * 接收到语音时的处理方法
 * @param mixed $object 接收到的消息体
 */
    private function receiveVoice($object)
    {
        if (isset($object->Recognition)) {//有语音识别结果，按文字消息进行处理
            $this->receiveText($object->Recognition);
        } else {
            $this->respondText('您的消息我们已经收到，我们将在24小时内回复');
        }
    }
/**
 * 接收到文字消息时的处理方法
 * @param string $keyword 文本消息内容
 */
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
/**
 * 接收到事件时的处理方法
 * @param mixed $object 接收到的消息体
 */
    private function receiveEvent($object)
    {
        switch ($object->Event) {
            case 'subscribe':
                $this->receiveText('subscribe');
                break;
            case 'unsubscribe':
                $this->unSubscribe();
                break;
            case 'CLICK':
                $this->receiveText($object->EventKey);
            default:
                echo '';
        }
    }
/**
 * 用户取消订阅事件
 */
    private function unSubscribe()
    {
        $table=M('app');
        $data=$table->field('classname')->where('`unsubscribe`=1')->select();
        foreach ($data as $v){
            $this->runApp($v['classname'], 'unsubscribe');
        }
    }
/**
 * 回复用户文字消息
 * @param string $contentStr 文本消息的内容
 */
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
            $encrypt = new WXBizMsgCrypt($this->token, $this->encodingaeskey, $this->appid);
            $encryptMsg = '';
            $encrypt->encryptMsg($resultStr, $this->timestamp, $this->nonce, $encryptMsg);
            echo $encryptMsg;
        } else {
            echo $resultStr;
        }
    }
/**
 * 回复用户图文消息
 * @param array $content 图文消息相关信息组成的数组
 */
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
            $encrypt = new WXBizMsgCrypt($this->token, $this->encodingaeskey, $this->appid);
            $encryptMsg = '';
            $encrypt->encryptMsg($resultStr, $this->timestamp, $this->nonce, $encryptMsg);
            echo $encryptMsg;
        } else {
            echo $resultStr;
        }
    }
/**
 * 判断回复消息类型，选择相应的方法
 * @param array $resData 回复消息信息数组
 */
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
/**
 * 处理回复内容中的emoji表情，使其在微信中正常显示
 * @param string $content 文本内容
 * @return string
 */
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
/**
 * 调用其他应用对消息进行处理
 * @param string $classname 其他应用的方法路径
 * @param string $key 用户发送的消息内容
 */
    private function runApp($classname, $key)
    {
        $app=A($classname);
        $res = $app->setKey($key, $this->username);
        $this->respondMsg($res);
    }
/**
 * 将消息转发到其他第三方
 * @param string $keyword 用户发送的消息内容
 * @param string $url 第三发的url
 * @param string $token 第三方的token
 * @param boolean $robot 是否是机器人
 */
    private function forward($keyword, $url, $token, $robot = false)
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
            $flag ++;
        }
        if (curl_errno($ch) != 0) {
            echo '';
        } elseif ($robot) {
            $resText = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($resText->MsgType == 'text') {
                $qq=M('manage');
                $data=$qq->field('qq')->find();
                $res=$resText->Content . "\n\n本消息为自动回复，可发送消息继续聊天。\n有问题请等待人工回复";
                if(isset($data['qq'])){
                    $res.="或<a href='{$data['qq']}'>点击进行qq咨询</a>";
                }
                $this->respondText($res);
            } else {
                if ($this->encrypt_type == 'aes') {
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
    }
}