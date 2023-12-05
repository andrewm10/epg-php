<?php 
// live.php 返回DIYP的txt格式的频道接口数据
// author: aming.ou
// http://127.0.0.1/live.php
class ChannelDB extends SQLite3
{
	function __construct()
	{
		$this->open("channel_epg.db");
	}
}
$config = array();
$channel = new ChannelDB();
$group = 'xxxxx'; 
// 当前IP
$ip = $_SERVER['REMOTE_ADDR'];
$time = date("Y-m-d H:i:s"); 
// 当前url
$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; 
// 获取最后来源地址
if (empty($_SERVER['HTTP_REFERER']))
{
	$source_link = $url;
}
else
{
	$source_link = $_SERVER['HTTP_REFERER'];
}
$source_link = urldecode($source_link);
// 将IP地址记录到日志文件或数据库中
$result = $channel->query("INSERT or ignore INTO access_log (ip_address,access_time,url) VALUES ('{$ip}','{$time}','{$source_link}');");

$result = $channel->query('select * from list where isdel > 0 order by isdel;');
while ($row = $result->fetchArray())
{
	$config[] = ['item' => $row[0], 'title' => sprintf("%s,%s", $row[1], $row[3])];
}
$groupconfig = array_reduce($config, function($result, $item)
	{
		$gender = $item['item'];
		if (!isset($result[$gender]))
		{
			$result[$gender] = [];
		}
		$result[$gender][] = $item;
		return $result;
	}, []); 
// print_r($groupconfig);
$config = array();
foreach ($groupconfig as $item => $titles)
{
	$config[] = sprintf("%s,#genre#", $item); 
	// print_r($titles);
	foreach ($titles as $k => $v)
	{
		$config[] = $v['title'];
	}
}
echo implode(PHP_EOL, $config);
// echo iconv("UTF-8","GBK",implode('<br>',$config));

?>