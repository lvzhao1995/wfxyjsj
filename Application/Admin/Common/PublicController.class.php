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
            $this->success('退出登录成功', 'User/login');
            exit();
        }
        if (I('get.action') != 'login') {
            if (!session('?login') || session('login') != 'yes') {
                $this->redirect('User/login?action=login', '', 0);
            }
        }
    }
    
    public function _empty($name){
        $this->redirect('Index/index');
    }
}