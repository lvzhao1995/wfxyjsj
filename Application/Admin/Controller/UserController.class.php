<?php
namespace Admin\Controller;

use Admin\Common\PublicController;

class UserController extends PublicController
{

    public function login()
    {
        $status=0;
        if (isset($_POST['username'])) {
            $status = 1;
            $username = I('post.username/s');
            $password = I('post.password/s', '', 'md5');
            if ($username != '' && $password != '') {
                $db = M('admin');
                $data = $db->field('1')
                    ->where(array(
                    'username' => ':username',
                    'password' => ':password'
                ))
                    ->bind(array(
                    ':username' => $username,
                    ':password' => $password
                ))
                    ->find();
                if (! empty($data)) {
                    session('login', 'yes');
                    $this->redirect('Index/index');
                    $status = 0;
                } else {
                    $info = '帐号或密码有误！';
                }
            } else {
                $info = '账号或密码不能为空！';
            }
        }
        $this->info = $info;
        $this->status = $status;
        $this->display();
    }
}