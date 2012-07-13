<?php
spl_autoload_unregister(array('YiiBase', 'autoload'));
require_once 'ClassLoader.php';
spl_autoload_register(array('ClassLoader', 'autoload'));
spl_autoload_register(array('YiiBase', 'autoload'));
require_once Yii::app()->getBasePath().'/extensions/Threadi/Loader.php';

/**
 * ParserCommand
 *
 * @author Slava Tutrinov
 */
class ParserCommand extends CConsoleCommand {
    
    public function beforeAction($action, $params) {
        return true;
    }

    public function actionStart() {
//        $t = time();
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, "http://enter.ru");
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.7 (KHTML, like Gecko) Chrome/16.0.912.63 Safari/535.7");
//        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
//        curl_setopt($ch, CURLOPT_TIMEOUT, 1000);
//        $result = curl_exec($ch);
//        curl_close($ch);
//
//        preg_match_all("/class=\"bToplink\"[\s\S]*?href=\"(.*)?\"/", $result, $matches);
//        $superCategoryCount = sizeof($matches[1]);
//        $threads = array();
//        $joinPoint = new Threadi_JoinPoint;
//        $proxies = require_once 'proxies.php';
//        $proxiesCount = sizeof($proxies);
//        $treadProxyCount = floor($proxiesCount / $superCategoryCount);
//
//        $db = Yii::app()->db;
//        $c1 = $db->createCommand("TRUNCATE TABLE property_values");
//        $c1->execute();
//        $c2 = $db->createCommand("TRUNCATE TABLE properties");
//        $c2->execute();
//        $c3 = $db->createCommand("TRUNCATE TABLE goods");
//        $c3->execute();
//        Yii::app()->db->active = false;
//
//        for ($i = 0; $i < $superCategoryCount; $i++) {
//            $threads[$i] = Threadi_ThreadFactory::getReturnableThread(array('Parser_SuperCategory', 'parse'));
//            $threads[$i]->start($matches[1][$i], Parser_Proxy::getProxyList($superCategoryCount, $i));
//            $joinPoint->add($threads[$i]);
//        }
//        $joinPoint->waitTillReady();
//        $exTime = time() - $t;
//        echo "Enter.Ru parsing finished? ex. time - " . $exTime . " s" . PHP_EOL;
        $t = time();
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->load(Yii::app()->getBasePath() . '/categories.xml');
        $catsCount = $dom->getElementsByTagName("category")->length;

        $joinPoint = new Threadi_JoinPoint();
        for ($i = 0; $i < THREADS_COUNT; $i++) {
            $threads[$i] = Threadi_ThreadFactory::getReturnableThread(array('Parser_YandexMarket', 'parse'));
            $threads[$i]->start(THREADS_COUNT, Parser_Proxy::getProxyList(THREADS_COUNT, $i), $i, $catsCount);
            $joinPoint->add($threads[$i]);
        }
        $joinPoint->waitTillReady();



        $exTime = time() - $t;
        echo "Parser execution time: " . $exTime . " sec." . PHP_EOL;


        Yii::app()->db->active = true;
        $cpmmand = Yii::app()->db->createCommand("SELECT COUNT(*) c FROM goods WHERE source='market'");
        $c = $cpmmand->queryRow();
        $c = $c['c'];
        Yii::app()->db->active = false;
        $packerJoinPoint = new Threadi_JoinPoint;
        $packerThreads = array();
        for ($j = 0; $j < THREADS_COUNT; $j++) {
            $packerThreads[$j] = Threadi_ThreadFactory::getReturnableThread(array("Parser_YandexMarket", "packFeatures"));
            $packerThreads[$j]->start($c, THREADS_COUNT, $j);
            $packerJoinPoint->add($packerThreads[$j]);
        }
        $packerJoinPoint->waitTillReady();
        $packExTime = time() - $packStartTime;
        echo "Features execution time: " . $packExTime . " sec." . PHP_EOL;
    }
    
    public function actionLoadPrice() {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom -> formatOutput = true;
        $dom -> load(Yii::app()->getBasePath()."/data/input.xml");
        $items = $dom->getElementsByTagName("item");
        $c = $items->length;
        unset($dom);
        $joinPoint = new Threadi_JoinPoint;
        $threads = array();
        for ($i = 0; $i < THREADS_COUNT; $i++) {
            $threads[$i] = Threadi_ThreadFactory::getReturnableThread(array('Parser_Price', 'parse'));
            $threads[$i] -> start($i, THREADS_COUNT, $c);
            $joinPoint->add($threads[$i]);
        }
        $joinPoint->waitTillReady();
        
    }

}

?>
