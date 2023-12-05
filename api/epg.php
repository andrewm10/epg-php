
<?php 
// epg.php 返回DIYP格式的节目数据
// author: aming.ou
// http://127.0.0.1/epg.php
$displayname = 'display-name';
$riqi = $_GET['date'];
$ch = $_GET['ch'];
$is_found = 0;

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

$sql = "SELECT channel_id  FROM epg_channel where name='" . $ch . "' limit 1";
$retval = $channel->query($sql);
$obj = array();
while ($row = $retval->fetchArray())
{
	array_push($obj, $row);
}
if (count($obj) <= 0)
{ 
	// 空节目表，可用以回看定位
	for($i = 0; $i <= 23; $i++)
	{
		sprintf("%02d", $i);
		$epg_datas[] = array("start" => sprintf("%02d", $i) . ":00",
			"end" => sprintf("%02d", $i) . ":59",
			"title" => "未知节目",
			"desc" => ""
			);
	}
	$age = array("channel_name" => "$ch",
		"date" => "$riqi",
		"epg_data" => $epg_datas
		);
	echo json_encode($age);
	return;
}
$sql = "SELECT * FROM epg_programme WHERE channel = (SELECT channel_id  FROM epg_channel where name='" . $ch . "' limit 1) AND sdate = '" . $riqi . "'";

$is_found = 0;
$retval = $channel->query($sql);
while ($row = $retval->fetchArray())
{
	$epg_datas[] = array("start" => $row['sstart'],
		"end" => $row['sstop'],
		"title" => $row['title'],
		"desc" => ""
		);
	$is_found = 1;
}
if ($is_found == 1)
{
	$age = array("channel_name" => "$ch",
		"date" => "$riqi",
		"epg_data" => $epg_datas
		);
	echo json_encode($age);
}
else
{ 
	// 空节目表，可用以回看定位
	for($i = 0; $i <= 23; $i++)
	{
		sprintf("%02d", $i);
		$epg_datas[] = array("start" => sprintf("%02d", $i) . ":00",
			"end" => sprintf("%02d", $i) . ":59",
			"title" => "精彩节目",
			"desc" => ""
			);
	}
	$age = array("channel_name" => "$ch",
		"date" => "$riqi",
		"epg_data" => $epg_datas
		);
	echo json_encode($age);
}

?>
