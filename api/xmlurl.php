<?php
// 程序查询仅限于当天，自定义xml.gz地址  放合适自己的节目源就行，全部往里面加载会卡死。
$urlxml = [
    'https://diyp.112114.xyz/pp.xml.gz',
    'http://epg.51zmt.top:8000/cc.xml.gz',    
];

/* 

仅供参考的xml.gz包地址  宝塔建议4左右小时更新一次即可
Crow丶：https://gitee.com/Black_crow/xmlgz/raw/master/e.xml.gz （当天全部节目）
Crow丶：https://gitee.com/Black_crow/xmlgz/raw/master/cc.xml.gz （当天央卫数不含港澳台）
    'https://epg.erw.cc/e.xml.gz',
老王：http://epg.51zmt.top:8000/e.xml.gz（当天全部节目）
老王：http://epg.51zmt.top:8000/cc.xml.gz（央视及各省卫视）
老王：http://epg.51zmt.top:8000/difang.xml.gz（地方及数字）

112114：https://diyp.112114.xyz/pp.xml.gz 

更多EPG节目表单下载（自己寻找合适自己的） 
http://epg.51zmt.top:8000/
https://epg.erw.cc/
https://epg.pw/xmltv.html?lang=zh-hant 


（香港服务器可用，部分地区被屏蔽）
https://diyp.112114.xyz/
https://epg.112114.eu.org/ 
https://epg.112114.xyz/

只支持类似于51zmt的xml类型的表单，不是所有的xml表单都可以遍历使用。
*/


?>
