<?php
namespace Home\Controller;

use Think\Controller;

class JwcController extends Controller
{

    public function index()
    {}

    public function isBind($openid, $kebiao = false)
    {
        return array('studentid'=>'13021140047');
    }

    public function getChengji($cookie, $id, $xn = NULL, $xq = NULL, $wangye = false)
    {
        return array(
            'status' => 0,
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