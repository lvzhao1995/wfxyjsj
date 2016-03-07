<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html class="no-js">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="description" content="">
<meta name="keywords" content="">
<meta name="viewport"
  content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>首页</title>
<!-- Set render engine for 360 browser -->
<meta name="renderer" content="webkit">
<!-- No Baidu Siteapp-->
<meta http-equiv="Cache-Control" content="no-siteapp" />
<!-- Add to homescreen for Chrome on Android -->
<meta name="mobile-web-app-capable" content="yes">
<!-- Add to homescreen for Safari on iOS -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-title" content="Amaze UI" />
<!-- Tile icon for Win8 (144x144 + tile color) -->
<meta name="msapplication-TileColor" content="#0e90d2">
<link rel="stylesheet" href="http://cdn.amazeui.org/amazeui/2.4.2/css/amazeui.min.css">
<style type="text/css">
.demo-bar {
	position: relative;
	height: 50px;
	background: #3bb4f2;
	line-height: 29px;
	font-size: 20px;
	color: #FFF;
	padding: 10px;
	box-shadow: 0 0 3px rgba(0, 0, 0, .15);
}

.demo-bar:before, .demo-bar:after {
	content: " ";
	display: table;
}

.demo-bar:after {
	clear: both;
}

.demo-bar h1 {
	margin: 0;
	text-align: center;
	font-weight: 400;
	font-size: 20px;
}
</style>
</head>
<body>
  <header class="demo-bar">
    <h1>首页</h1>
  </header>
  <section>
    <nav data-am-widget="menu" class="am-menu  am-menu-stack">
      <ul class="am-menu-nav am-avg-sm-1">
        <li class="am-parent"><a href="#">教务处功能</a>
          <ul class="am-menu-sub am-collapse  am-avg-sm-2 ">
            <li><a href="kebiao.php?openid=<?php echo ($openid); ?>">课表查询</a></li>
            <li><a href="chengji.php?openid=<?php echo ($openid); ?>">成绩查询</a></li>
            <li><a href="xuanxiu.php?openid=<?php echo ($openid); ?>">选修课查询</a></li>
            <li><a href="baoming.php?openid=<?php echo ($openid); ?>">活动报名</a>
          </ul></li>
        <li class="am-parent"><a href="#">图书馆功能</a>
          <ul class="am-menu-sub am-collapse  am-avg-sm-3 ">
            <li><a href="jiansuo.php?openid=<?php echo ($openid); ?>">书目检索</a></li>
            <li><a href="jieyue.php?openid=<?php echo ($openid); ?>">当前借阅</a></li>
            <li><a href="weizhang.php?openid=<?php echo ($openid); ?>">违章查询</a>
          </ul></li>
        <li><a href="login.php?openid=<?php echo ($openid); ?>">绑定</a></li>
        <li><a href="jcbd.php?openid=<?php echo ($openid); ?>">取消绑定</a></li>
      </ul>
    </nav>
  </section>
  <footer class="am-footer"
    style="background: #555; color: #999; position: fixed; bottom: 0; width: 100%">
    <div class="am-g am-g-fixed">Copyright © 2015 All Rights Reserved.</div>
  </footer>
  <!--[if (gte IE 9)|!(IE)]><!-->
  <script src="http://libs.baidu.com/jquery/2.1.1/jquery.min.js"></script>
  <script>!window.jQuery&&document.write('<script src="/<?php echo (C("install_path")); ?>/Public/jquery.js"><\/script>');</script>
  <!--<![endif]-->
  <!--[if lte IE 8 ]>
<script src="http://libs.baidu.com/jquery/1.11.1/jquery.min.js"></script>
<![endif]-->
  <script src="http://cdn.amazeui.org/amazeui/2.4.2/js/amazeui.min.js"></script>
  <?php include_once 'wxjs.php';?>
</body>
</html>