<?php
namespace Admin\Common;

use Think\Controller;

class PublicController extends Controller
{

    public function _initialize()
    {
        $action = I('get.action');
        if ($action == "logout") {
            session('login', null);
            session('[destroy]');
            $this->success('退出登录成功', 'User/login', 1);
            exit();
        }
        if (I('get.action') != 'login') {
            if (! session('?login') || session('login') != 'yes') {
                $this->redirect('User/login?action=login', '', 0);
            }
        }
        
        $data['index'] = U('Index/index');
        $data['subscribe'] = U('Base/reply', 'ordernum=2');
        $data['keyword'] = U('Base/keyword');
        $data['message'] = U('Base/reply', 'ordernum=1');
        $data['tuwenlist'] = U('Base/tuwenlist');
        $data['menu'] = U('Base/menu');
        $data['applist'] = U('Ext/applist');
        $data['forwardList'] = U('Ext/forwardList');
        $data['kebiao'] = U('Ext/kebiao');
        $data['password'] = U('User/changePass');
        $data['manage'] = U('User/manage');
        $this->url = $data;
    }

    public function _empty($name)
    {
        $this->redirect('Index/index');
    }

    public function valiteKeyword($keyword, $type, $ordernum)
    {
        $where1 = array('keyword' => array('in',$keyword
        )
        );
        $where2 = array('keyword' => array('in',$keyword
        ),'ordernum' => array('neq',':ordernum'
        )
        );
        $reply = M('reply');
        $app = M('app');
        $forward = M('forward');
        if ($type == 'reply') {
            $data1 = $reply->field('keyword')
                ->where($where2)
                ->bind(':ordernum', $ordernum)
                ->find();
            $data2 = $app->field('keyword')
                ->where($where1)
                ->bind(':ordernum', $ordernum)
                ->find();
            $data3 = $forward->field('keyword')
                ->where($where1)
                ->bind(':ordernum', $ordernum)
                ->find();
        } elseif ($type == 'app') {
            $data1 = $reply->field('keyword')
                ->where($where1)
                ->bind(':ordernum', $ordernum)
                ->find();
            $data2 = $app->field('keyword')
                ->where($where2)
                ->bind(':ordernum', $ordernum)
                ->find();
            $data3 = $forward->field('keyword')
                ->where($where1)
                ->bind(':ordernum', $ordernum)
                ->find();
        } elseif ($type == 'forward') {
            $data1 = $reply->field('keyword')
                ->where($where1)
                ->bind(':ordernum', $ordernum)
                ->find();
            $data2 = $app->field('keyword')
                ->where($where1)
                ->bind(':ordernum', $ordernum)
                ->find();
            $data3 = $forward->field('keyword')
                ->where($where2)
                ->bind(':ordernum', $ordernum)
                ->find();
        }
        if (! (empty($data1) && empty($data2) && empty($data3))) {
            return $data1['keyword'] . $data2['keyword'] . $data3['keyword'];
        } else {
            return true;
        }
    }
}