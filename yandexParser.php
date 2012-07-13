<?php
ini_set("pcre.backtrack_limit", 10000000);
$args = $argv;
$args = array_flip($args);
if (isset($args['test'])) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://market.yandex.ru/guru.xml?CMD=-RR=0,0,0,0-VIS=28066-CAT_ID=651600-EXC=1-PG=10&hid=91019&filter=&num=&greed_mode=false&CAT_ID=651600");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    $result = curl_exec($ch);
    preg_match_all("/class=\"cmp_chbx-input\"\sname=\"cmp_chbx_grp_([^\"]*)\"/", $result, $matches);
    preg_match("//", $result, $action);
    $newUrl = 
    var_dump($matches[1]);
    $post = array();
    foreach ($matches[1] as $match) {
        $post[] = $match."=on";
    }
    $poststr = implode("&", $post);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $poststr);
    
    exit;
} elseif (isset($args["htmldom"])) {
    require_once 'simplehtmldom/simple_html_dom.php';
    $html = file_get_html("cats.html");
    $catRows = $html->find(".rt");
    $dom = new DOMDocument('1.0', 'utf-8');
    $root = $dom->createElement("categories", "");
    foreach ($catRows as $row) {
        $catName = $row->children(0)->plaintext;
        $url = $row->children(1)->plaintext;
        preg_match("/hid=([\d]+)?[^d]{1}.*?CAT_ID=([\d]+)?[^\d]*/", $url, $matches);
        $hid = $matches[1];
        $catId = $matches[2];
        $catNode = $dom->createElement("category", "");
        $urlNode = $dom->createElement("url", $url);
        $nameNode = $dom->createElement("name", $catName);
        $hidNode = $dom->createElement("hid", $hid);
        $catIdNode = $dom->createElement("cid", $catId);
        $catNode->appendChild($nameNode);
        $catNode->appendChild($urlNode);
        $catNode->appendChild($hidNode);
        $catNode->appendChild($catIdNode);
        $root->appendChild($catNode);
    }
    $dom->appendChild($root);
    $dom->save("categories.xml");
    exit;
} elseif (isset($args['clients'])) {
    require_once 'simplehtmldom/simple_html_dom.php';
    $html = file_get_html("browsers.html");
    $catRows = $html->find(".rt");
    $dom = new DOMDocument('1.0', 'utf-8');
    $root = $dom->createElement("browsers", "");
    foreach ($catRows as $row) {
        $client = $row->children(0)->plaintext;
        $accept = $row->children(1)->plaintext;
        $catNode = $dom->createElement("browser", "");
        $urlNode = $dom->createElement("client", $client);
        $nameNode = $dom->createElement("accept", $accept);
        $catNode->appendChild($urlNode);
        $catNode->appendChild($nameNode);
        $root->appendChild($catNode);
    }
    $dom->appendChild($root);
    $dom->save("browsers.xml");
    exit;
} elseif (isset($args["browsers"])) {
    $dom = new DOMDocument('1.0', 'utf-8');
    $dom->load("browsers.xml");
    $brs = $dom->getElementsByTagName("browser");
    $str = '';
    foreach ($brs as $br) {
        $userAgent = $br->getElementsBytagName("client")->item(0)->nodeValue;
        $accept = $br->getElementsBytagName("accept")->item(0)->nodeValue;
        $str .= "\tarray('useragent' => '".$userAgent."', 'accept' => '".$accept."'),".PHP_EOL;
    }
    $str = "return array(".PHP_EOL.$str.");";
    file_put_contents("browsers.php", $str);
    exit;
}


$t = time();
error_reporting(E_ALL^E_NOTICE);
define("BASE_URL", "http://www.enter.ru");
define("DB", "center");
define("DB_TABLE", "goods");
define("DB_USER", "root");
define("DB_PASS", "123");
define("DB_ADAPTER", "mysql");
define("DB_PORT", '3306');
define("DB_HOST", '127.0.0.1');
define("THREADS_COUNT", 10);
require_once dirname(__FILE__).'/Threadi/Loader.php';
require_once 'Loader.php';
spl_autoload_register(array('Loader', 'autoload'));
require_once 'database/ClassLoader.php';
spl_autoload_register(array('ClassLoader', 'autoload'));

$dom = new DOMDocument('1.0', 'utf-8');
$dom->load('categories.xml');
$catsCount = $dom->getElementsByTagName("category")->length;

$joinPoint = new Threadi_JoinPoint();
for ($i = 0; $i < THREADS_COUNT; $i++) {
    $threads[$i] = Threadi_ThreadFactory::getReturnableThread(array('Parser_YandexMarket', 'parse'));
    $threads[$i] -> start(THREADS_COUNT, Parser_Proxy::getProxyList(THREADS_COUNT, $i), $i, $catsCount);
    $joinPoint->add($threads[$i]);
}
$joinPoint->waitTillReady();



$exTime = time()-$t;
echo "Parser execution time: ".$exTime." sec.".PHP_EOL;
?>