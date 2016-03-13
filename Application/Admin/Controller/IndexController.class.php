<?php
namespace Admin\Controller;

use Admin\Common\PublicController;

class IndexController extends PublicController
{

    public function index()
    {
        $db = M('manage');
        $data = $db->field('1')->find();
        if (empty($data)) {
            $shezhi = false;
        } else {
            $shezhi = true;
        }
        if (! $shezhi) {
            $status=1;
        }
        $this->title='后台首页';
        $this->display();
    }
}