 <?php
 require_once __DIR__ . '/../autoloader.php';
 use phpspider\core\phpspider;
 use phpspider\core\requests;
 use phpspider\core\db;
 use phpspider\core\selector;

/* Do NOT delete this comment */
/* 不要删除这段注释 */
$configs = array(
    'name' => '马蜂窝',
    'tasknum' => 1,
    'log_show' => true,
    'domains' => array(
        'www.mafengwo.cn'
    ),
    'scan_urls' => array(
        "http://www.mafengwo.cn",            // 随便定义一个入口，要不然会报没有入口url错误，但是这里其实没用
    ),
    'list_url_regexes' => array(
        "http://www.mafengwo.cn/mdd/base/list/pagedata_citylist",         // 城市列表页
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
        // 城市
        array(
            'name' => "city",
            'selector' => "//div[@class='title']",
            'required' => true,
        ),
        // 数量
        array(
            'name' => "nums",
            'selector' => "//a[@title='蜂蜂点评']",
            'required' => true,
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_start = function ($phpspider) {
    $db_config = $phpspider->get_config("db_config");
    db::set_connect('mafengwo_content', $db_config);
    db::_init();
    $header = [
        'Accept' => 'application/json, text/javascript, */*; q=0.01',
        'Accept-Encoding' => 'gzip, deflate',
        'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
        'Cache-Control' => 'no-cache',
        'Connection' => 'keep-alive',
        'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
        'Host' => 'www.mafengwo.cn',
        'Origin' => 'http://www.mafengwo.cn',
        'Pragma' => 'no-cache',
        'Referer' => 'http://www.mafengwo.cn/mdd/citylist/21536.html',
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.109 Safari/537.36',
        'X-Requested-With' => 'XMLHttpRequest',
    ];
    foreach ($header as $key => $value) {
        requests::set_header($key, $value);
    }
};

$spider->on_scan_page = function ($page, $content, $phpspider) {
    //for ($i = 0; $i < 298; $i++)
    //测试的时候先采集一个国家，要不然等的时间太长
    for ($i = 0; $i < 1; $i++) {
        // 全国热点城市
        $url = "http://www.mafengwo.cn/mdd/base/list/pagedata_citylist";
        $options = array(
        'method' => 'post',
        'url_type' => 'list_page',
        'params' => array(
            'mddid' => 21536,
            'page' => $i,
        )
        );
        $phpspider->add_url($url, $options);
    }
    return false;
};

$spider->on_list_page = function ($page, $content, $phpspider) {
    // 如果是城市列表页
    if (preg_match("#pagedata_citylist#", $page['request']['url'])) {
        preg_match_all('#<a href="(/travel-scenic-spot/mafengwo/.*?.html)"#', $content, $out);
        if (!empty($out[1])) {
            foreach ($out[1] as $url) {
                $url = 'http://www.mafengwo.cn' . $url;
                $options = array(
                    'method' => 'get',
                    'url_type' => 'content_page',
                );
                $phpspider->add_url($url, $options);
            }
        }
    }
};

$spider->on_extract_field = function ($fieldname, $data, $page) {
    if ($fieldname == 'city') {
        $data = preg_match("/<h1>(\S+)<\/h1>/", $data, $content);
        $data = trim($content[1]);
    }
    if ($fieldname == 'nums') {
        preg_match("/<span>(\S+)<\/span>/", $data, $content);
        preg_match("/(\d+)/", $content[1], $data);
        $data = trim($data[1]);
    }
    return $data;
};

$spider->on_extract_page = function ($page, $data) {
    $data = [
        'city' => $data['city'],
        'nums' => $data['nums'],
    ];
    $sql = "Select * From `mafengwo_content` Where `city`='{$data['city']}'";
    $row = db::get_one($sql);
    if (empty($row)) {
        db::insert("mafengwo_content", $data);
    }
    return $data;
};

$spider->start();
