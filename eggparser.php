<?php
//echo urldecode('http://www.newegg.com/Product/Productcompare.aspx?CompareItemList=389%7C30-997-618%5E30-997-618-01%23%2C30-994-371%5E30-994-371-TS%2C30-994-935%5E30-994-935-TS%2C30-994-594%5E30-994-594-03%23%2C30-992-367%5E30-992-367-02%23');
//exit;
error_reporting(E_ALL^E_NOTICE);
$t = time();
ini_set("pcre.backtrack_limit", 10000000);
define("THREADS_COUNT", 10);
define("DB", "center");
define("DB_TABLE", "goods");
define("DB_USER", "admin");
define("DB_PASS", "refergtmn1988");
define("DB_ADAPTER", "mysql");
define("DB_PORT", '3306');
define("DB_HOST", '192.168.0.100');
require_once dirname(__FILE__).'/Threadi/Loader.php';
require_once 'Loader.php';
spl_autoload_register(array('Loader', 'autoload'));
require_once 'database/ClassLoader.php';
spl_autoload_register(array('ClassLoader', 'autoload'));
$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_URL => "http://www.newegg.com/ProductSort/CategoryList.aspx?style=0",
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 1000,
    CURLOPT_CONNECTTIMEOUT => 0,
    CURLOPT_RETURNTRANSFER => true,
));
$newEggMainHtml = curl_exec($ch);
preg_match_all("/li\sonmouseover[\s\S]*?class=\"borderBottom\"[\s\S]*?<a\shref=\"([^\"]*)\"/ui", $newEggMainHtml, $matches);
$urls = $matches[1];
curl_close($ch);
array_walk($urls, function($v, $k) use(&$urls){
    $urls[$k] = $v."&Pagesize=100";
});
$joinPoint = new Threadi_JoinPoint;
$threads = array();
for ($i = 0; $i < THREADS_COUNT; $i++) {
    $threads[$i] = Threadi_ThreadFactory::getReturnableThread(array("Parser_Egg", "parse"));
    $threads[$i]->start($urls, THREADS_COUNT, $i);
    $joinPoint->add($threads[$i]);
}
$joinPoint->waitTillReady();
$exTime = time()-$t;
echo "Execution time: ".$exTime." sec".PHP_EOL;
?>
