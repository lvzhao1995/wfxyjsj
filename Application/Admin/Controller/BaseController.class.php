<?php
namespace Admin\Controller;

use Admin\Common\PublicController;
use Admin\Common\accesstoken;

class BaseController extends PublicController
{

    public function reply()
    {
        $replytype = 0;
        $content = "";
        $keyword = "";
        $mode = 0;
        $ordernum = I('get.ordernum/d');
        if ($ordernum != 0) {
            $db = M('reply');
            $data = $db->field('keyword,replytype,content,mode')
                ->where(array(
                'ordernum' => ':ordernum'
            ))
                ->bind(':ordernum', $ordernum)
                ->select();
            if (! empty($data)) {
                foreach ($data as $v) {
                    $keyword .= $v['keyword'] . '，';
                }
                $keyword = trim($keyword, '，');
                $replytype = $v['replytype'];
                $content = str_replace('\n', '\\\\n', $v['content']);
                $mode = $v['mode'];
            }
        }
        
        $this->saveUrl = U('Base/savereply');
        $this->tuwenUrl = U('Base/selectTuwen');
        $this->keywordUrl = U('Base/keyword');
        if ($ordernum == 2) {
            $this->title = '关注自动回复';
        } elseif ($ordernum == 1) {
            $this->title = '消息自动回复';
        } else {
            $this->title = '关键词自动回复';
        }
        $this->keyword = $keyword;
        $this->replytype = $replytype;
        $this->content = $content;
        $this->mode = $mode;
        $this->ordernum = $ordernum;
        $this->display();
    }

    public function keyword()
    {
        $db = M('reply');
        $data = $db->distinct(true)
            ->field('ordernum')
            ->where(array(
            'ordernum' => array(
                'gt',
                2
            )
        ))
            ->select();
        
        if (! empty($data)) {
            $status = 0;
            $order = array();
            foreach ($data as $v) {
                $ordernum = $v['ordernum'];
                $keywordlist = $db->field('keyword')
                    ->where(array(
                    'ordernum' => ':ordernum'
                ))
                    ->bind(':ordernum', $ordernum)
                    ->select();
                $keyword = '';
                foreach ($keywordlist as $k) {
                    $keyword .= $k['keyword'] . '，';
                }
                $order[$ordernum]['key'] = trim($keyword, '，');
                $replydata = $db->field('replytype,mode')
                    ->where(array(
                    'ordernum' => ':ordernum'
                ))
                    ->bind(':ordernum', $ordernum)
                    ->find();
                $order[$ordernum]['replytype'] = $replydata['replytype'];
                $order[$ordernum]['mode'] = $replydata['mode'];
                $order[$ordernum]['ordernum'] = $ordernum;
                $order[$ordernum]['editUrl'] = U('Base/reply', 'ordernum=' . $ordernum);
                $order[$ordernum]['delUrl'] = U('Base/delreply', 'ordernum=' . $ordernum);
            }
        } else {
            $status = 1;
        }
        
        $this->order = $order;
        $this->status = $status;
        $this->title = '关键词自动回复';
        $this->addUrl = U('Base/reply?ordernum=0');
        $this->display();
    }

    public function delreply()
    {
        $db = M('reply');
        $ordernum = I('get.ordernum/d');
        $data = $db->where(array(
            'ordernum' => ':ordernum'
        ))
            ->bind(':ordernum', $ordernum)
            ->delete();
        if ($data !== false) {
            echo '删除成功！';
        } else {
            echo '未知错误，请刷新后重试！';
        }
    }

    public function tuwenlist()
    {
        $db = M('tuwen');
        $data = $db->field('id,title,url')->select();
        
        $this->tuwen = $data;
        $this->title = '图文管理';
        $this->addUrl = U('Base/tuwen/', 'id=0');
        $this->editUrl = U('Base/tuwen');
        $this->delUrl = U('Base/deltuwen');
        $this->display();
    }

    public function tuwen()
    {
        $id = I('get.id/d');
        if ($id != 0) {
            $db = M('tuwen');
            $data = $db->field('title,url,abstract,imgurl')
                ->where(array(
                'id' => ':id'
            ))
                ->bind(':id', $id)
                ->find();
            $data['abstract'] = str_replace('\n', "\n", $data['abstract']);
        }
        $data['id'] = $id;
        
        $this->title = '图文管理';
        $this->tuwen = $data;
        $this->uploadImgUrl = U('Base/uploadImg');
        $this->saveTuwenUrl = U('Base/saveTuwen');
        $this->tuwenlistUrl = U('Base/tuwenlist');
        $this->display();
    }

    public function deltuwen()
    {
        $id = I('get.id/d');
        $db = M('tuwen');
        $res = $db->where(array(
            'id' => ':id'
        ))
            ->bind(':id', $id)
            ->delete();
        if ($res === false) {
            echo '未知错误，请刷新后重试！';
        } else {
            echo '删除成功！';
        }
    }

    public function saveTuwen()
    {
        $resdata = array();
        $resdata['code'] = 1;
        if (I('post.title/s')!='') {
            $imgurl = I('post.imgurl');
            $id = I('post.id');
            if (false === strpos($imgurl, 'http') && ! empty($imgurl)) {
                $imgurl = 'http://' . $_SERVER['HTTP_HOST'] . $imgurl;
            }
            $db = M('tuwen');
            if ($id != 0) {
                $db->create();
                $db->imgurl = $imgurl;
                $db->save();
                $resdata['code'] = 0;
                $resdata['msg'] = '保存成功！';
            } else {
                unset($_POST['id']);
                $db->create();
                $db->imgurl = $imgurl;
                $db->add();
                $resdata['code'] = 0;
                $resdata['msg'] = '保存成功！';
            }
        } else {
            $resdata['msg'] = '标题不能为空！';
        }
        $this->ajaxReturn($resdata);
    }

    public function menu()
    {
        $db = M('manage');
        $data = $db->field('appid,appsecret,type')->find();
        
        if ($data['type'] > 0) {
            $access = new accesstoken();
            $menu = json_decode(file_get_contents('https://api.weixin.qq.com/cgi-bin/menu/get?access_token=' . $access->getAccessToken()), true);
        }
        if (isset($menu['menu'])) {
            $menu = $menu['menu']['button'];
        }
        $this->data = $data;
        $this->menu = $menu;
        $this->title = '自定义菜单';
        $this->setmenuUrl = U('Base/setmenu');
        $this->indexUrl = U('Index/index');
        $this->display();
    }

    public function selectTuwen()
    {
        include_once __DIR__ . '/../dbconfig.php';
        $db = M('tuwen');
        $data = $db->field('id,title,abstract,url,imgurl')->select();
        
        $this->data = $data;
        $this->display();
    }

    public function savereply()
    {
        if (isset($_POST['ordernum'])) {
            $ordernum = I('post.ordernum/d');
            $keyword = I('post.keyword');
            $keyword = explode('，', trim($keyword, '，'));
            $keyword = array_unique($keyword);
            $keyword = array_filter($keyword);
            
            $db = M('reply');
            $valite = $this->valiteKeyword($keyword, 'reply', $ordernum);
            if ($valite !== true) {
                echo '关键词“', $valite, '”已经存在';
                return;
            }
            if ($ordernum != '0') {
                $db->where(array(
                    'ordernum' => ':ordernum'
                ))
                    ->bind(':ordernum', $ordernum)
                    ->delete();
            } else {
                $data = $db->field('ordernum')
                    ->order(array(
                    'ordernum' => 'desc'
                ))
                    ->find();
                
                $ordernum = $data['ordernum'] + 1;
            }
            foreach ($keyword as $v) {
                $db->create();
                $db->keyword=$v;
                $db->ordernum=$ordernum;
                $db->add();
            }
            echo '保存成功！';
        } else {
            echo '未知错误！请检查数据后重试！';
        }
    }

    public function uploadImg()
    {
        $resdata = array();
        $resdata['code'] = 1;
        
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728; 
        $upload->exts = array(
            'jpg',
            'gif',
            'png',
            'jpeg',
            'JPG'
        );
        $upload->rootPath = './Uploads/'; 
        $upload->savePath = ''; 

        $info = $upload->uploadOne($_FILES['Filedata']);
        if (! $info) { 
            $resdata['msg'] = $upload->getError();
        } else { 
            $resdata['code'] = 0;
            $resdata['imgurl'] = '/'.C('INSTALL_PATH').'/Uploads/'.$info['savepath'].$info['savename'];
        }
        
        $this->ajaxReturn($resdata);
    }
}