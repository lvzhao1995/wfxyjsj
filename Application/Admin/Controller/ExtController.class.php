<?php
namespace Admin\Controller;

use Admin\Common\PublicController;

class ExtController extends PublicController
{

    function applist()
    {
        $status = 1;
        $db = M('app');
        $data = $db->distinct(true)
            ->field('ordernum')
            ->select();
        if (! empty($data)) {
            foreach ($data as $v) {
                $ordernum = $v['ordernum'];
                $keywords = $db->field('keyword')
                    ->where(array('ordernum' => $ordernum
                ))
                    ->select();
                $keyword = '';
                foreach ($keywords as $keytmp) {
                    $keyword .= $keytmp['keyword'] . '，';
                }
                $appdata = $db->field('appname,mode')
                    ->where(array('ordernum' => $ordernum
                ))
                    ->find();
                $app[$ordernum] = $appdata;
                $app[$ordernum]['keyword'] = trim($keyword, '，');
                $app[$ordernum]['ordernum'] = $ordernum;
            }
            $status = 0;
        }
        
        $this->title = "应用设置";
        $this->app = $app;
        $this->status = $status;
        $this->editUrl = U('Ext/app');
        $this->display();
    }

    public function app()
    {
        $mode = 0;
        $appname = '';
        $classname = '';
        $keyword = '';
        $unsubscribe = 0;
        $ordernum = I('get.ordernum/d');
        if ($ordernum != 0) {
            $db = M('app');
            $data = $db->field('keyword')
                ->where(array('ordernum' => ':ordernum'
            ))
                ->bind(':ordernum', $ordernum)
                ->select();
            foreach ($data as $keywordtmp) {
                $keyword .= $keywordtmp['keyword'] . '，';
            }
            $keyword = trim($keyword, '，');
            $app = $db->field('appname,mode,unsubscribe')
                ->where(array('ordernum' => ':ordernum'
            ))
                ->bind(':ordernum', $ordernum)
                ->find();
            $app['keyword'] = $keyword;
            $app['ordernum'] = $ordernum;
        } else {
            $this->redirect('Index/index');
        }
        $this->app = $app;
        $this->title = "编辑扩展应用";
        $this->saveUrl = U('Ext/saveApp');
        $this->listUrl = U('Ext/applist');
        $this->display();
    }

    public function saveApp()
    {
        $ordernum = I('post.ordernum/d');
        if ($ordernum != 0) {
            $appname = $_POST['appname'];
            $keyword = $_POST['keyword'];
            $mode = $_POST['mode'];
            $unsubscribe = $_POST['unsubscribe'];
            $keyword = explode('，', trim($keyword, '，'));
            $keyword = array_unique($keyword);
            $keyword = array_filter($keyword);
            $valite = $this->valiteKeyword($keyword, 'app', $ordernum);
            if ($valite !== true) {
                echo '关键词“', $valite, '”已经存在';
                return;
            }
            $db = M('app');
            
            $db->where(array('ordernum' => ':ordernum'
            ))
                ->bind(':ordernum', $ordernum)
                ->delete();
            
            foreach ($keyword as $v) {
                $db->create();
                $db->keyword = $v;
                $db->add();
            }
            echo '保存成功！';
        }
    }

    public function forwardList()
    {
        $db = M('forward');
        $data = $db->field('ordernum')
            ->distinct(true)
            ->where(array('ordernum' => array('gt',0
        )
        ))
            ->select();
        
        if (! empty($data)) {
            $status = 0;
            
            foreach ($data as $v) {
                $ordernum = $v['ordernum'];
                $keywords = $db->field('keyword')
                    ->where(array('ordernum' => ':ordernum'
                ))
                    ->bind(':ordernum', $ordernum)
                    ->select();
                $keyword = '';
                foreach ($keywords as $keytmp) {
                    $keyword .= $keytmp['keyword'] . '，';
                }
                $keyword = trim($keyword, '，');
                
                $forwardtmp = $db->field('name,mode,url')
                    ->where(array('ordernum' => $ordernum
                ))
                    ->find();
                $forward[$ordernum] = $forwardtmp;
                $forward[$ordernum]['ordernum'] = $ordernum;
                $forward[$ordernum]['keyword'] = $keyword;
            }
        } else {
            $status = 1;
        }
        
        $this->status = $status;
        $this->forward = $forward;
        $this->title = "第三方融合";
        $this->addUrl = U('Ext/forward', 'ordernum=0');
        $this->editUrl = U('Ext/forward');
        $this->delUrl = U('Ext/delforward');
        $this->getForwardAll = U('Ext/getForwardAll');
        $this->saveUrl = U('Ext/saveForward');
        $this->display();
    }

    public function forward()
    {
        $ordernum = I('get.ordernum/d');
        if ($ordernum != 0) {
            $db = M('forward');
            $data = $db->field('keyword')
                ->where(array('ordernum' => ':ordernum'
            ))
                ->bind(':ordernum', $ordernum)
                ->select();
            $keyword = '';
            foreach ($data as $keywordtmp)
                $keyword .= $keywordtmp['keyword'] . '，';
            
            $keyword = trim($keyword, '，');
            $forward = $db->field('name,mode,url,token')
                ->where(array('ordernum' => ':ordernum'
            ))
                ->bind(':ordernum', $ordernum)
                ->find();
            $forward['ordernum'] = $ordernum;
            $forward['keyword'] = $keyword;
        } else {
            $forward['mode'] = 0;
        }
        
        $this->forward = $forward;
        $this->title = '编辑第三方融合';
        $this->saveUrl = U('Ext/saveForward');
        $this->listUrl = U('Ext/forwardList');
        $this->display();
    }

    public function saveForward()
    {
        if (isset($_POST['ordernum'])) {
            $keyword = I('post.keyword');
            $ordernum = I('post.ordernum/d');
            if ($ordernum != - 1) {
                $keyword = explode('，', trim($keyword, '，'));
                $keyword = array_unique($keyword);
                $keyword = array_filter($keyword);
                
                $valite = $this->valiteKeyword($keyword, 'forward', $ordernum);
                if ($valite !== true) {
                    echo '关键词“', $valite, '”已经存在';
                    return;
                }
            }else{
                $keyword=array('');
            }
            $db = M('forward');
            if ($ordernum != '0') {
                $db->where(array('ordernum' => ':ordernum'
                ))
                    ->bind(':ordernum', $ordernum)
                    ->delete();
            } else {
                $data = $db->field('ordernum')
                    ->order(array('ordernum' => 'desc'
                ))
                    ->find();
                $ordernum = $data['ordernum'] + 1;
                if ($ordernum == 0) {
                    $ordernum ++;
                }
            }
            foreach ($keyword as $v) {
                $db->create();
                $db->keyword = $v;
                $db->ordernum = $ordernum;
                $db->add();
            }
            echo '保存成功！';
        } else {
            echo '未知错误！请检查数据后重试！';
        }
    }

    public function delforward()
    {
        $ordernum = I('get.ordernum/d');
        $db = M('forward');
        $res = $db->where(array('ordernum' => ':ordernum'
        ))
            ->bind(':ordernum', $ordernum)
            ->delete();
        if ($res === false) {
            echo '未知错误，请刷新后重试！';
        } else {
            echo '删除成功！';
        }
    }

    public function getForwardAll()
    {
        $db = M('forward');
        $data = $db->field('url,token')
            ->where(array('ordernum' => - 1
        ))
            ->find();
        $resdata = array('check' => 0
        );
        if (! empty($data)) {
            $resdata['check'] = 1;
            $resdata['url'] = $data['url'];
            $resdata['token'] = $data['token'];
        }
        $this->ajaxReturn($resdata);
    }
    
    public function kebiao(){
        $db=M('manage');
        $data=$db->field('starttime')->find();
        if(empty($data)) {
            $status=1;
        }else{
            $status=0;
        }
        
        $this->starttime=$data['starttime'];
        $this->status=$status;
        $this->title="课表设置";
        $this->manageUrl=U('User/manage');
        $this->setUrl=U('Ext/setKebiao');
        $this->display();
    }
    
    public function setKebiao(){
        $act=I('post.act');
        if(isset($_POST['starttime'])){
            $starttime=I('post.starttime','','strtotime');
            $db=M('manage');
            $db->where('1')->save(array('starttime'=>$starttime));
            echo '保存成功！';
        }elseif($act=='clear'){
            $db=M('info');
            $db->where(1)->save(array('kecheng_json'=>null));
            echo '刷新成功！';
        }else{
            echo '参数错误，请刷新后重试';
        }
    }
}