<?php
error_reporting(E_ALL^E_NOTICE);
$t = time();
require_once dirname(__FILE__).'/Threadi/Loader.php';
require_once 'database/ClassLoader.php';
spl_autoload_register(array('ClassLoader', 'autoload'));
require_once 'sphinxapi.php';

//$sphinx = new SphinxClient();
//$sphinx->SetServer("localhost", 9312);
//$sphinx->SetMatchMode(SPH_MATCH_EXTENDED);
//$sphinx->SetRankingMode(SPH_RANK_SPH04);
//$sphinx->SetSortMode(SPH_SORT_RELEVANCE);
//$result = $sphinx->query('"Вытяжка Krona Stella 5P 600"/1', "centerGoods");
//var_dump($result['matches']);

$db = DBManager::get('mysql', '127.0.0.1', '3307', '', '', '');
$r = $db->query("SELECT * from centerGoods WHERE MATCH('Вытяжка Krona Stella 5P 600 aerg54') OPTION ranker=sph04")->fetch();
var_dump($r);
return;
define("DB", "center");
define("DB_TABLE", "goods");
define("DB_USER", "root");
define("DB_PASS", "123");
define("DB_ADAPTER", "mysql");
define("DB_PORT", '3306');
define("DB_HOST", '127.0.0.1');
define("THREADS_COUNT", 13);

$dom = new DOMDocument('1.0', 'utf-8');
$dom -> formatOutput = true;
$dom -> load(dirname(__FILE__)."/input.xml");
$items = $dom->getElementsByTagName("item");

$itemsCount = $items->length;
unset($dom);
$joinPoint = new Threadi_JoinPoint;
$threads = array();
for ($i = 0; $i < THREADS_COUNT; $i++) {
    $threads[$i] = Threadi_ThreadFactory::getReturnableThread(array('XmlAnalizer', 'search'));
    $threads[$i] -> start($i, THREADS_COUNT, $itemsCount);
    $joinPoint->add($threads[$i]);
}
$joinPoint->waitTillReady();
$result = array();
$defined = 0;
for ($j = 0; $j < THREADS_COUNT; $j++) {
    $res = unserialize($threads[$j] ->getResult());
    $result = $res['xml'];
    $defined += intval($res['defined']);
}
$str = implode(PHP_EOL, $result);
file_put_contents(dirname(__FILE__)."/output.xml", "<?xml version='1.0' charset='utf-8' ?>".PHP_EOL."<items>".PHP_EOL.$str.PHP_EOL."</items>");
echo PHP_EOL."Total: ".(time()-$t)." s . Defined goods: ".$defined.PHP_EOL;


class XmlAnalizer {

    /**
     * @param int $pNum Process num
     * @param int $pCount Process count
     * @param int $iCount Total items count
     */
    public static function search($pNum, $pCount, $iCount) {
        $xml = array();
        $mod = $iCount%$pCount;//остаток от деления количества товаров в прайсе на количество потоков
        $limit = ($iCount-$mod)/$pCount;//количество товаров из прайса на один поток (если он не в конце списка потоков)
        $start = $pNum*$limit;
        if ($pNum == ($pCount-1)) {
            $limit = $limit+$mod;//количество товаров из прайса на один поток (если он в конце списка потоков)
        }
        $end = $start+$limit;
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->load(dirname(__FILE__)."/input.xml");
        $items = $doc->getElementsByTagName("item");
        $db = DBManager::get(DB_ADAPTER, DB_HOST, DB_PORT, DB_USER, DB_PASS, DB);
        $db->exec("SET NAMES utf8; SET CHARACTER SET utf8;");
        $dbSphinx = DBManager::get(DB_ADAPTER, DB_HOST, '3307', '', '', '');
        $k = 0;
        for ($i = $start; $i < $end; $i++) {
            $item = $items->item($i);
            $name = $item->getElementsByTagName("name")->item(0)->nodeValue;
//            $res
            $shortName = "%".$item->getElementsByTagName("shortname")->item(0)->nodeValue."%";
            $ruName = "%".$item->getElementsByTagName("rusname")->item(0)->nodeValue."%";
            $stmt->bindValue(":full_name", $name, PDO::PARAM_STR);
            $stmt->bindValue(":en_name", $shortName, PDO::PARAM_STR);
            $stmt->bindValue(":ru_name", $ruName, PDO::PARAM_STR);
            $result = $stmt->execute();
            if ($result) {
                $row = $stmt->fetch();
                if ($row) {
                    $domf = new DOMDocument();
                    $f = html_entity_decode($row['features'], ENT_QUOTES, 'utf-8');
                    $f = str_replace("&", "&amp;", $f);
                    $domf -> loadXML($f);
                    $infoNode = $doc->createElement("info", $row["info"]);
                    $descNode = $doc->createElement("description", $row['description']);
                    $features = $domf->getElementsByTagName('features')->item(0);
                    $item->appendChild($descNode);
                    $item->appendChild($infoNode);
                    $item->appendChild($doc->importNode($features, true));
                    $k++;
                }
                //test+++
//                if (sizeof($xml) == 4) {
//                    return "asd";
//                    return serialize(implode(PHP_EOL, $xml));
//                }
                //test---
            }
            $xml[] = $doc->saveXML($item);
        }
        $str = implode(PHP_EOL, $xml);
        $result = array(
            'xml' => $str,
            'defined' => $k,
        );
        file_put_contents(dirname(__FILE__)."/parsed_data/output_".$pNum.".xml", $str);
        return serialize($result);
    }

}

?>
