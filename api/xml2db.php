
<?php 
// xml2db.php 联网获取xml格式的节目表存如sqlite数据库,为本地提供DIYP epg接口服务
// author: aming.ou
// http://127.0.0.1/xml2db.php
$save_all = 1; // 1 保存全量节目单, 0 仅保存list中频道相关的节目单
$deleteoffset = -8; // 清理xx天前的节目数据
error_reporting(0); //禁止输出错误提示
$displayname = 'display-name';
$n = 0;
$start = microtime(true);

class ChannelDB extends SQLite3
{
	function __construct()
	{
		$isnew = 1;
		$f = 'channel_epg.db';
		if (file_exists($f))
		{
			$isnew = 0;
		}
		$this->open($f);

		if ($isnew > 0)
		{ 
			// 初始化数据库
			$this->exec("CREATE TABLE 'list' (item text, title text, epg text, url text, isdel integer null default 120,constraint name_pk primary key (item,title))");
			$this->exec("CREATE TABLE if not exists 'access_log' (ip_address text, access_time text,url text)");
			$this->exec("CREATE TABLE if not exists 'epg_channel' ( `name` text, `channel_id` text,constraint name_pk primary key (name))");
			$this->exec("CREATE TABLE if not exists 'epg_programme' ( `title` text, `sdate` text, `sstart` text, `sstop` text, `channel` text, `sdesc` text, 'inserttime' text,constraint name_pk primary key (channel,sdate,sstart))");
			$this->exec("CREATE TABLE if not exists 'tmp_epg_channel' ( `name` text, `channel_id` text)");
			$this->exec("CREATE TABLE if not exists 'tmp_epg_programme' ( `title` text, `sdate` text, `sstart` text, `sstop` text, `channel` text, `sdesc` text, 'inserttime' text)"); 
			// 初始化频道表样例数据
			$this->exec("INSERT INTO `list` (`item`,`title`,`epg`,`url`,`isdel`) VALUES ('广东频道','广州综合','','http://nas.jdshipin.com:8801/gztv.php?id=zhonghe','90');");
			$this->exec("INSERT INTO `list` (`item`,`title`,`epg`,`url`,`isdel`) VALUES ('广东频道','广州新闻','','http://nas.jdshipin.com:8801/gztv.php?id=xinwen#http://113.100.193.10:9901/tsfile/live/1000_1.m3u8','90');");
			$this->exec("INSERT INTO `list` (`item`,`title`,`epg`,`url`,`isdel`) VALUES ('直播频道','CCTV2','','http://dbiptv.sn.chinamobile.com/PLTV/88888893/224/3221226195/index.m3u8?0.smil','120');");
		}
	}
}
// 连接数据库
$config = array();
$channel = new ChannelDB();
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
// 分析xml数据
function getContent($url)
{
	$process = curl_init($url); 
	// curl_setopt($process, CURLOPT_USERPWD, $username . ":" . $password);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
	$data = curl_exec($process);
	curl_close($process);
	return $data;
}
// 清空临时表
$result = $channel->query("delete from tmp_epg_channel");
$result = $channel->query("delete from tmp_epg_programme");
// 节目总表地址
$data = getContent("http://epg.51zmt.top:8000/e.xml");
$xml = simplexml_load_string($data);
// 开始事务处理
$channel->exec("BEGIN TRANSACTION;");
foreach ($xml->children() as $xmldata)
{
	if ($xmldata->getName() == "channel")
	{
		$result = $channel->query("INSERT INTO tmp_epg_channel(name,channel_id) VALUES ('" . $xmldata->$displayname . "','" . $xmldata->attributes()->id . "')");

		if (!$result)
		{
			echo $n . ' - ' . $channel->lastErrorMsg() . '<br>';
		}
	}

	if ($xmldata->getName() == "programme")
	{
		$start_time = substr($xmldata->attributes()->start, 8, 2) . ":" . substr($xmldata->attributes()->start, 10, 2);
		$stop_time = substr($xmldata->attributes()->stop, 8, 2) . ":" . substr($xmldata->attributes()->stop, 10, 2);
		$jm_date = substr($xmldata->attributes()->stop, 0, 4) . "-" . substr($xmldata->attributes()->stop, 4, 2) . "-" . substr($xmldata->attributes()->stop, 6, 2);
		$n ++ ;

		$replacement = str_replace("'", " ", $xmldata->title);
		$sql = "INSERT INTO tmp_epg_programme(channel,sdate,sstart,sstop,title,sdesc,inserttime) VALUES ('" . $xmldata->attributes()->channel . "','" . $jm_date . "','" . $start_time . "','" . $stop_time . "','" . $replacement . "','','" . $time . "')";
		$result = $channel->query($sql);

		if (!$result)
		{
			echo $n . ' = ' . $channel->lastErrorMsg() . '<br>'; 
			// echo  $xmldata->title . "<br>";
		}
	}
}
// 更新节目数据
if ($n > 0)
{
	$channel->exec("INSERT INTO access_log (ip_address,access_time,url) VALUES ('xml2db_ini','{$time}','{$n}');"); 
	// 把直播源list的频道追加/更新到epg频道表
	$channel->exec("insert or replace into epg_channel select a.* from tmp_epg_channel a where name in (select title from list);"); 
	// 根据条件是否保存全量epg频道表
	$count = $channel->querySingle("SELECT count(*) FROM 'list'");
	if ($count == 0 or $save_all == 1)
	{
		$channel->exec("insert or replace into epg_channel SELECT b.* FROM tmp_epg_channel b;");
	} 
	// 统计原节目单条目数
	$count = $channel->querySingle("SELECT count(*) FROM 'epg_programme'"); 
	// 追加节目单到epg_programme
	$channel->exec("insert or replace into epg_programme SELECT c.* FROM epg_channel b join tmp_epg_programme c on b.channel_id= c.channel;"); 
	// 统计节目单增加条目数
	$count = $channel->querySingle("SELECT count(*) FROM 'epg_programme'") - $count;
	$channel->exec("INSERT INTO access_log (ip_address,access_time,url) VALUES ('xml2db_add','{$time}','{$count}');");
	echo "done, add " . $count . '<br>'; 
	// 清理历史数据
	// $currentDate = date('Y-m-d'); // 获取当前日期
	$currentDate = $channel->querySingle("SELECT max(sdate) FROM 'epg_programme'");
	$newDate = strtotime($currentDate) + ($deleteoffset * 24 * 60 * 60);
	$formattedNewDate = date('Y-m-d', $newDate);
	echo 'epg_programme dates:' . $currentDate . ' <- ' . $formattedNewDate . '<br>';
	$channel->exec("delete from `epg_programme` where sdate < '{$formattedNewDate}';");
	echo 'Delete ' . $channel->changes() . ' records from epg_programme <br>';
}
else
{
	echo "none.";
}
// 写入硬盘
$channel->exec("COMMIT;");
$executionTime = number_format(microtime(true) - $start, 4);
$channel->exec("INSERT INTO access_log (ip_address,access_time,url) VALUES ('xml2db_cost','{$time}','{$executionTime}');");

$channel->close();

?>
