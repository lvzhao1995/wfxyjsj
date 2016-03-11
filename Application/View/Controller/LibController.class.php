<?php
namespace View\Controller;

use Think\Controller;

class LibController extends Controller
{

    public function bookinfo()
    {
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $marc_no = I('get.marc_no');
        $book = R('Home/Lib/getBookInfo', array($marc_no));
        if ($book == 404) {
            $this->status = 404;
        } else {
            $this->status = 0;
            $this->book = $book;
        }
        $this->title = '图书信息';
        $this->doubanUrl = U('Lib/getDouban');
        $this->display();
    }

    public function getDouban()
    {
        $isbn = I('get.isbn');
        if ($isbn != '') {
            $url = 'https://api.douban.com/v2/book/isbn/' . $isbn;
            $content = file_get_contents($url);
            $content = str_replace('\n', '<br>', $content);
            $this->show(nl2br($content));
        }
    }

    public function jiansuo()
    {
        $openid = I('get.openid', '');
        if ($openid == '') {
            $this->error('请在微信中点击自动回复的链接打开本页面！');
        }
        $keyword=I('get.keyword');
        
        $this->title="书目检索";
        $this->keyword=$keyword;
        $this->submitUrl=U('Lib/result');
        $this->openid=$openid;
        $this->display();
    }
    
    public function result(){
        if (isset($_POST['searchType'])) {
            $searchType = I('post.searchType');
            $searchStr = I('post.searchStr');
            $openid=I('post.openid');
            $lib = A('Home/Lib');
            $res = $lib->seach($searchStr, $searchType, false);
            if($res['status']==0){
                foreach ($res['data'] as $k=>$v){
                    preg_match("/可借：([0-9]{1,2})/", $v['num'], $num);
                    $res['data'][$k]['no']=(int)$num[1];
                }
            }
            $this->ajaxReturn($res);
//             if ($res[0]['name'] == '未检索到相关图书，请更换关键词重试！') {
//                 echo '<div class="main"><div><h3>未检索到相关图书，请更换关键词重试！</h3></div></div>';
//             }else{
//                 foreach ($res as $num => $datil) {
//                     echo '<a href="bookinfo.php?marc_no=', $datil['marc_no'], '&openid=', $openid, '">';
//                     echo '<div class="main"><div><h3>', $datil['name'], '</h3>';
//                     preg_match("/可借复本：([0-9]{1,2})/", $datil['num'], $num);
//                     if ((int) $num[1] > 0) {
//                         echo ' <span class="am-badge am-badge-success am-fr">可借</span>';
//                     } else {
//                         echo ' <span class="am-badge am-badge-warning am-fr">不可借</span>';
//                     }
//                     $datil['num']=str_replace('复本', '', $datil['num']);
//                     echo '</div><ul><li><span class="icon-info"></span>'. $datil['num'];
//                     echo '</li><li><span class="icon-people"></span>', $datil['people'];
//                     echo '</li><li><span class="icon-building"></span>', $datil['press'], '</li></ul></div></a>';
//                 }
//             }
        }
        
    }
}