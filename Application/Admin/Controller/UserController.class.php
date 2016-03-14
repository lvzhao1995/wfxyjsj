<?php
namespace Admin\Controller;

use Admin\Common\PublicController;

class UserController extends PublicController
{

    public function login()
    {
        $status = 0;
        if (isset($_POST['username'])) {
            $status = 1;
            $username = I('post.username/s');
            $password = I('post.password/s', '', 'md5');
            if ($username != '' && $password != '') {
                $db = M('admin');
                $data = $db->field('1')
                    ->where(array('username' => ':username','password' => ':password'
                ))
                    ->bind(array(':username' => $username,':password' => $password
                ))
                    ->find();
                if (! empty($data)) {
                    session('login', 'yes');
                    session('user', $username);
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

    public function changePass()
    {
        if (isset($_POST['username'])) {
            $username = I('post.username/s');
            $pass = I('post.pass/s', '', 'md5');
            $newpass = I('post.newpass/s', '', 'md5');
            $repass = I('post.repass/s', '', 'md5');
            if ($username == session('user')) {
                if ($newpass == $repass) {
                    $db = M('admin');
                    $data = $db->field('1')
                        ->where(array('username' => ':username','password' => ':password'
                    ))
                        ->bind(array(':username' => $username,':password' => $pass
                    ))
                        ->find();
                    if (! empty($data)) {
                        $res = $db->where(array('username' => ':username'
                        ))
                            ->bind(':username', $username)
                            ->save(array('password' => $newpass
                        ));
                        if ($res === false) {
                            $msg = "未知错误，请重试！";
                        } else {
                            $msg = "修改成功！";
                        }
                    } else {
                        $msg = "原密码不正确！";
                    }
                } else {
                    $msg = "新密码两次输入不一致！";
                }
            } else {
                $msg = "用户名不符，请刷新后重试！";
            }
            echo $msg;
            return;
        }
        
        $this->title = "修改密码";
        $this->user = session('user');
        $this->display();
    }

    public function manage()
    {
        $db = M('manage');
        $data = $db->field('name,type,id,account,appid,appsecret,url,token,aeskey,aestype')->find();
        if (empty($data)) {
            $data['type'] = 0;
            $data['url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/' . U('Index/index');
            $data['aestype'] = 1;
        }
        
        $this->title = '公众号设置';
        $this->data = $data;
        $this->saveUrl = U('User/saveManage');
        $this->display();
    }

    public function saveManage()
    {
        if (isset($_POST['name'])) {
            $db = M('manage');
            $data = $db->field('1')->find();
            $db->create();
            if (! empty($data)) {
                $res=$db->where('1')->save();
            } else {
                $res=$db->add();
            }
            if ($res===false) {
                $msg = "未知错误，请稍后重试！";
            } else {
                $msg = "修改成功！";
            }
        } else {
            $msg = "参数错误，请刷新后重试！";
        }
        echo $msg;
    }
}