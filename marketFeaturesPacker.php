<?php
error_reporting(E_ALL^E_NOTICE^E_WARNING);
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

$packStartTime = time();
$config = new stdClass();
$config -> adapter = DB_ADAPTER;
$config -> params -> host = DB_HOST;
$config -> params -> port = DB_PORT;
$config -> params -> user = DB_USER;
$config -> params -> pwd = DB_PASS;
$config -> params -> dbname = DB;
$db = new DB($config);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->query("SET NAMES 'utf8'; SET CHARACTER SET 'utf8';");

$result = $db->query("SELECT COUNT(*) c FROM goods WHERE source='market'");
$c = $result->fetch();
$c = $c['c'];
$db = null;
$packerJoinPoint = new Threadi_JoinPoint;
$packerThreads = array();
for ($j = 0; $j < THREADS_COUNT; $j++) {
    $packerThreads[$j] = Threadi_ThreadFactory::getReturnableThread(array("Parser_YandexMarket", "packFeatures"));
    $packerThreads[$j]->start($c, THREADS_COUNT, $j);
    $packerJoinPoint->add($packerThreads[$j]);
}
$packerJoinPoint->waitTillReady();
$packExTime = time()-$packStartTime;
echo "Features execution time: ".$packExTime." sec.".PHP_EOL;

?>
