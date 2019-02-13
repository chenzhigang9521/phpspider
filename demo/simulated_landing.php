<?php
require_once __DIR__ . '/../autoloader.php';
require_once __DIR__ . '/../vendor/autoload.php';
use phpspider\core\phpspider;
use phpspider\core\requests;
use phpspider\core\db;

// 登录请求url
$login_url = "http://www.waduanzi.com/login?url=http%3A%2F%2Fwww.waduanzi.com%2F";
// 提交的参数
$params = array(
    "LoginForm[returnUrl]" => "http%3A%2F%2Fwww.waduanzi.com%2F",
    "LoginForm[username]" => "13712899314",
    "LoginForm[password]" => "854230",
    "yt0" => "登录",
);
// 发送登录请求
requests::post($login_url, $params);
// 登录成功后本框架会把Cookie保存到www.waduanzi.com域名下，我们可以看看是否是已经收集到Cookie了
$cookies = requests::get_cookies("www.waduanzi.com");
print_r($cookies);  // 可以看到已经输出Cookie数组结构

// requests对象自动收集Cookie，访问这个域名下的URL会自动带上
// 接下来我们来访问一个需要登录后才能看到的页面
$url = "http://www.waduanzi.com/member";
$html = requests::get($url);
echo $html;     // 可以看到登录后的页面，非常棒👍
