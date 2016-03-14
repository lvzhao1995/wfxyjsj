<?php
namespace View\Controller;

use Think\Controller;

class IndexController extends Controller
{
    public function index(){
        $this->redirect('Index/index');
    }
}