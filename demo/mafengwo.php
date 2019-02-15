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
    //'save_running_state' => true,
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
    // 'export' => array(
    //     'type' => 'db',
    //     'table' => 'mafengwo_content',
    // ),
    'db_config' => array(
        'host'  => '127.0.0.1',
        'port'  => 3306,
        'user'  => 'root',
        'pass'  => '123456',
        'name'  => 'phpsider',
    ),
    'list_url_regexes' => ['x'],
    'content_url_regexes' => ['x'],
    'fields' => array(
        // 标题
        array(
            'name' => "city",
            'selector' => "//div[@class='title']",
            //'selector' => "//div[@id='Article']//h1",
            'required' => true,
        ),
        // 分类
        array(
            'name' => "nums",
            'selector' => "//div[@class='nums']",
            'required' => true,
        ),
    ),
 );

 $spider = new phpspider($configs);

 $spider->on_start = function ($phpspider) {
    $db_config = $phpspider->get_config("db_config");
    db::set_connect('mafengwo_content', $db_config);
    db::_init();
    requests::set_header('Referer', 'http://www.mafengwo.cn/mdd/citylist/21536.html');
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
    };

    $spider->on_extract_field = function ($fieldname, $data, $page) {
        if ($fieldname == 'city') {
            $data = preg_replace("/<p \S+<\/p>/", '', $data);
            $data = trim(preg_grep("/\S+/", [$data])[0]);
        }
        if ($fieldname == 'nums') {
            preg_match("/<b>\d+<\/b>/", $data, $content);
            $data = $content;
            $data = trim(preg_replace("/<b>|<\/b>/", '', $data)[0]);
        }
        return $data;
    };

    $spider->on_list_page = function ($page, $content, $phpspider) {
        // 如果是城市列表页
        if (preg_match("#pagedata_citylist#", $page['request']['url'])) {
            $data = json_decode($content, true);
            $html = $data['list'];
            preg_match_all('#<a href="/travel-scenic-spot/mafengwo/(.*?).html"#', $html, $out);
            if (!empty($out[1])) {
                foreach ($out[1] as $v) {
                    $url = "http://www.mafengwo.cn/gonglve/ajax.php?act=get_travellist&mddid={$v}";
                    $options = array(
                    'method' => 'post',
                    'url_type' => 'content_page',
                    'params' => array(
                        'mddid' => $v,
                        'pageid' => 'mdd_index',
                        'sort' => 1,
                        'cost' => 0,
                        'days' => 0,
                        'month' => 0,
                        'tagid' => 0,
                        'page' => 1,
                    )
                    );
                    $phpspider->add_url($url, $options);
                }
            }
        } else {// 如果是文章列表页
            $data = json_decode($content, true);
            $html = $data['list'];
            // 遇到第一页的时候，获取分页数，把其他分页全部入队列
            if ($page['request']['params']['page'] == 1) {
                $data_page = trim($data['page']);
                if (!empty($data_page)) {
                    preg_match('#<span class="count">共<span>(.*?)</span>页#', $data_page, $out);
                    for ($i = 0; $i < $out[1]; $i++) {
                        $v = $page['request']['params']['mddid'];
                        $url = "http://www.mafengwo.cn/gonglve/ajax.php?act=get_travellist&mddid={$v}&page={$i}";
                        $options = array(
                        'method' => 'post',
                        'params' => array(
                            'mddid' => $v,
                            'pageid' => 'mdd_index',
                            'sort' => 1,
                            'cost' => 0,
                            'days' => 0,
                            'month' => 0,
                            'tagid' => 0,
                            'page' => $i,
                        )
                        );
                        $phpspider->add_url($url, $options);
                    }
                }
            }

            // 获取内容页
            preg_match_all('#<a href="/i/(.*?).html" target="_blank">#', $html, $out);
            if (!empty($out[1])) {
                foreach ($out[1] as $v) {
                    $url = "http://www.mafengwo.cn/i/{$v}.html";
                    $phpspider->add_url($url);
                }
            }
        }
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

// $spider->on_extract_field = function ($fieldname, $data, $page) {
//     if ($fieldname == 'date') {
//         $data = trim(str_replace(array("出发时间","/"), "", strip_tags($data)));
//     }
//     return $data;
// };

    $spider->start();
