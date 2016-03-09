<?php
namespace Home\Controller;

use Think\Controller;

class JwcController extends Controller
{
    private $url;
    
    public $starttime = 0;
    
    private $selfurl;

//     public function _initialize(){
//         $dirname = json_decode(file_get_contents(__DIR__ . "/../../global/config.json"));
//         $this->selfurl = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $dirname->dirname;
//         include __DIR__ . '/../../dbconfig.php';
//         $stmt = $mysqli->prepare('select `starttime` from `manage` where 1 limit 1');
//         $stmt->execute();
//         $stmt->store_result();
//         $starttime = 0;
//         $stmt->bind_result($starttime);
//         if (! $stmt->fetch()) {
//             $starttime = time() - 60;
//         }
//         $this->starttime = $starttime;
//     }

    public function isBind($openid, $kebiao = false)
    {
         return array('studentid'=>'13021140047');
    }

    public function getChengji($cookie, $id, $xn = NULL, $xq = NULL, $wangye = false)
    {
        
        return array(
            'status' => 403,
            'xn' => $xn,
            'xq' => $xq,
            'data' => array(
                array(
                    'kemu' => 'df',
                    'juanmian' => 45,
                    'chengji' => 60
                ),array(
                    'kemu'=>'dgh',
                    'juanmian'=>87,
                    'chengji'=>49
                )
            )
        );
    }
}