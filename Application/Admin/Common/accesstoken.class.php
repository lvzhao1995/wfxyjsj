<?php
namespace Admin\Common;
class accesstoken
{

    private $appId;

    private $appSecret;

    public function __construct()
    {
        $db=M('manage');
        $data=$db->field('appid,appsecret')->find();
        $this->appId = $data['appId'];
        $this->appSecret = $data['appSecret'];
    }
    public function getAccessToken()
    {
        S(array('prefix'=>'wx'));
        if (($access_token=S('access_token'))!==false) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = json_decode($this->httpGet($url));
            $access_token = $res->access_token;
            if ($access_token) {
                S('access_token',$access_token,array('expire'=>7000));
            }
        } 
        return $access_token;
    }
    private function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
    
        $res = curl_exec($curl);
        curl_close($curl);
    
        return $res;
    }
}