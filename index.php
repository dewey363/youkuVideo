<?php
require_once 'vendor/autoload.php';
use Ares333\CurlMulti\Core;

//需要采集的关键词存储到key.txt
$file = 'key.txt';
$fp = fopen($file, 'r');

//初始化curl
$curl = new Core ();
$curl->maxThread = 3;
$curl->cbTask = array (
	'cbTask',
	''
);
$curl->start ();

//自动通过此函数添加任务
function cbTask() {
	global $curl;
	$keyword = getKeywords();
	if(empty($keyword)) {
		return '';
	}
	$curl->add (
		array (
			'url' => 'http://www.soku.com/search_video/q_'.urlencode($keyword).'?page=1',
			'args' => 1,
		), 
		'cb1'
	);
}

//完成一次采集调取一次该函数
function cb1($r, $args) {
	global $curl;
	echo $r ['info'] ['url'] . " finished\n";
	
	if(stripos($r['content'], '抱歉，没有找到') !== false) {
		return '';
    }

	//识别视频标题、链接
	$html = \phpQuery::newDocumentHTML($r['content']);
    $dom = $html['.sk-vlist .v-link a'];
    foreach ($dom as $key => $value) {
        $value = pq($value);
        $href = $value->attr("href");
        $title = $value->attr("title");

        file_put_contents('youku.txt', $title."\t".$href."\n", FILE_APPEND);
    }
    \phpQuery::unloadDocuments();

    //生成下一页的链接
    $nextUrl = strstr($r['info']['url'], '?page='.$args, true)."?page=".($args + 1);
    $curl->add (
		array (
			'url' => $nextUrl,
			'args' => $args + 1
		), 
		'cb1'
	);
}

//从文本中获取一个关键词
function getKeywords() {
	global $fp;
	if(!feof($fp)) {
		return fgets($fp);
	}
	return false;
}