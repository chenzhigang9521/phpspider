<?php
// composer下载方式
// 先使用composer命令下载：
// composer require owner888/phpspider
// 引入加载器
//require './vendor/autoload.php';

// GitHub下载方式
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\phpspider;

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => '糗事百科',
    'log_show' => true,
    'tasknum' => 1,
    // 'save_running_state' => true,
    'domains' => array(
        'qiushibaike.com',
        'www.qiushibaike.com'
    ),
    'scan_urls' => array(
        'http://www.qiushibaike.com/'
    ),
    'list_url_regexes' => array(
        "http://www.qiushibaike.com/8hr/page/\d+\?s=\d+"
    ),
    'content_url_regexes' => array(
        "http://www.qiushibaike.com/article/\d+",
    ),
    'export' => array(
        'type' => 'csv',
        'table' => 'mafengwo_content',
        'file' => '/Users/zhigang/Desktop/work/project_code/phpspider/test.csv'
    ),
    'db_config' => array(
        'host'  => '127.0.0.1',
        'port'  => 3306,
        'user'  => 'root',
        'pass'  => '123456',
        'name'  => 'phpsider',
    ),
    'fields' => array(
        array(
            'name' => "article_title",
            'selector' => "//*[@id='single-next-link']//div[contains(@class,'content')]/text()[1]",
            'required' => true,
        ),
        array(
            'name' => "article_author",
            'selector' => "//div[contains(@class,'author')]//h2",
            'required' => true,
        ),
        array(
            'name' => "article_headimg",
            'selector' => "//div[contains(@class,'author')]//a[1]",
            'required' => true,
        ),
        array(
            'name' => "article_content",
            'selector' => "//*[@id='single-next-link']//div[contains(@class,'content')]",
            'required' => true,
        ),
        array(
            'name' => "article_publish_time",
            'selector' => "//div[contains(@class,'author')]//h2",
            'required' => true,
        ),
        array(
            'name' => "url",
            'selector' => "//div[contains(@class,'author')]//h2",   // 这里随便设置，on_extract_field回调里面会替换
            'required' => true,
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_handle_img = function ($fieldname, $img) {
    $regex = '/src="(https?:\/\/.*?)"/i';
    preg_match($regex, $img, $rs);
    if (!$rs) {
        return $img;
    }

    $url = $rs[1];
    $img = $url;
    return $img;
};

$spider->on_extract_field = function ($fieldname, $data, $page) {
    if ($fieldname == 'article_title') {
        if (strlen($data) > 10) {
            // 下面方法截取中文会有异常
            //$data = substr($data, 0, 10)."...";
            $data = mb_substr($data, 0, 10, 'UTF-8')."...";
            $data = trim($data);
        }
    } elseif ($fieldname == 'article_publish_time') {
        // 用当前采集时间戳作为发布时间
        $data = time();
    } elseif ($fieldname == 'url') { // 把当前内容页URL替换上面的field
        $data = $page['url'];
    }
    return $data;
};

$spider->start();
