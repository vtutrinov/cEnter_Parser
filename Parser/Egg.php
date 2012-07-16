<?php
/**
 * Parser_Egg
 *
 * @author Slava Tutrinov
 */
class Parser_Egg {
    
    protected static $compareUrl = "http://www.newegg.com/Product/Productcompare.aspx?CompareItemList=";
    
    protected static $pageCount = 0;
    
    public static function parse($urls, $tcount, $pid) {
        $urlCount = count($urls);
        $mod = $urlCount%$tcount;
        $limit = ($urlCount-$mod)/$tcount;
        $offset = $limit*$pid;
        if ($pid == ($tcount-1)) {
            $limit = $limit+$mod;
        }
        $end = $offset+$limit;
        $urlArr = array_slice($urls, $offset, $limit);
        $browsers = include 'browsers.php';
        $browserLength = sizeof($browsers)-1;
        $goods = array();
        for ($j = $offset; $j < $end; $j++) {
//            echo $j.PHP_EOL;
            $url = $urls[$j];
            preg_match("/SubCategory=([\d]*)?&/ui", $url, $m);
            $subCat = $m[1];
            $catFinished = false;
            $goods[$subCat] = array();
//            $goods[$subCat]['forcompare'] = array();
//            $goods[$subCat]['simpleUrls'] = array();
//            $compare = array();
            $simple = array();
            $l = 0;
            while (!$catFinished) {
                $l++;
                $u = $url."&Page=".$l;
                $ch = curl_init();
                $randomBrowserIndex = rand(0, $browserLength);
                $accept = $browsers[$randomBrowserIndex]["accept"];
                $userAgent = $browsers[$randomBrowserIndex]["useragent"];
                curl_setopt_array($ch, array(
                    CURLOPT_URL => $u,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_CONNECTTIMEOUT => 0,
                    CURLOPT_TIMEOUT => 1000,
                    CURLOPT_HTTPHEADER => array(
                        "Accept: ".$accept,
                        "User-Agent: ".$userAgent,
                    ),
                ));
                $html = curl_exec($ch);
                $info = curl_getinfo($ch);
                if ($u != $info['url']) {//redirect detected!!!
                    $goods[$subCat][] = $info['url'];break;
                }
                preg_match_all("/class=\"itemCell\"[\s\S]*?href=\"([^\s]*Product\.aspx[^\"]*)?\"/ui", $html, $gurls);
                $simple = array_merge($simple, $gurls[1]);
//                var_dump("http_code: ".$info['http_code']);
//                preg_match_all("/selectAndCompare\(\'([^\']*)?\'\)/ui", $html, $ids);
//                if (preg_match("/\-/ui", $ids[1][0])) {
//                    $compare = array_merge($compare, $ids[1]);
//                } else {
//                    $tmp = array();
//                    array_walk($ids[1], function($v, $k) use(&$tmp){
//                        $tmp[$k] = "http://www.newegg.com/Product/Product.aspx?Item=".$v;
//                    });
//                    $simple = array_merge($simple, $tmp);
//                }
                
                if ($l == 1) {
                    preg_match("/id=\"RecordCount_1\"[\s\S]*?>([\d]*)?</ui", $html, $totalCount);
                    $count = intval($totalCount[1]);
//                    var_dump($totalCount);exit;
                    self::$pageCount = ceil(($count/100));
                }
                if ($l == self::$pageCount) {
//                    var_dump("cat ".$url." finished. Pages: ".self::$pageCount);
                    $catFinished = true;
                }
            }
//            $goods[$subCat]['forcompare'] = $compare;
//            $goods[$subCat]['simpleUrls'] = $simple;
            $goods[$subCat] = $simple;
//            echo "Process ".getmypid()." . compare[".$subCat."]=".sizeof($goods[$subCat]['forcompare']).", simple[".$subCat."]=".sizeof($goods[$subCat]['simpleUrls']).PHP_EOL;
        }
//        echo "123".PHP_EOL;
        self::parseGoods($goods, $ch, $browsers, $browserLength);
    }
    
    /**
     *
     * @param array $goods = array([<catId>] => array(['forcompare'] => array(...), 'simple' => array()), <other_categories>) 
     */
    private static function parseGoods($goods ,$ch, $browsers, $browserLength) {
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
        
        $stm = $db->prepare("INSERT INTO goods(`name`, `articul`, `features`, `source`) VALUES(:n, :art, :feat, 'newegg')");
        
        foreach ($goods as $catId => $gUrls) {
            foreach ($gUrls as $url) {
                $randomBrowserIndex = rand(0, $browserLength);
                $accept = $browsers[$randomBrowserIndex]["accept"];
                $userAgent = $browsers[$randomBrowserIndex]["useragent"];
                curl_setopt_array($ch, array(
                    CURLOPT_URL => $url,
                    CURLOPT_HTTPHEADER => array(
                        "Accept: ".$accept,
                        "User-Agent: ".$userAgent,
                    ),
                ));
                $html = curl_exec($ch);
                preg_match("/itemprop=\"name\">([^<]*)?</ui", $html, $nameElements);
                preg_match("/Item=([^\"]*)$/ui", $url, $itemId);
                if (preg_match("/id=\"Details_Content\"([\s\S]*)?<\/fieldset>[\s\r\t\n]*?<\/div>[\s\t\r\n]*?<\/div>/sui", $html, $searchable)) {
                    preg_match_all("/<dt>([^<]*)?<\/dt>[\s\t\r\n]*?<dd>([^<]*)?<\/dd>/sui", $searchable[1], $properties);
                    $propNames = $properties[1];
                    $propValues = $properties[2];
                    $dom = new DOMDocument('1.0', 'utf-8');
                    $root = $dom->createElement("features", "");
                    $c = sizeof($propNames);
                    for ($i = 0; $i < $c; $i++) {
                        $featureNode = $dom->createElement("feature", "");
                        $featureNameNode = $dom->createElement("name", htmlentities(trim($propNames[$i], " "), ENT_QUOTES, 'UTF-8'));
                        $featureValueNode = $dom->createElement("value", htmlentities(trim($propValues[$i], " "), ENT_QUOTES, 'UTF-8'));
                        $featureNode->appendChild($featureNameNode);
                        $featureNode->appendChild($featureValueNode);
                        $root->appendChild($featureNode);
                    }
                    $dom->appendChild($root);
                    $xml = $dom->saveXML();
                    $name = $nameElements[1];
                    $articul = $itemId[1];
                    
                    $stm->bindParam(":n", $name, PDO::PARAM_STR);
                    $stm->bindParam(":art", $articul, PDO::PARAM_STR);
                    $stm->bindParam(":feat", $xml, PDO::PARAM_STR);
                    $stm->execute();
                    
                }
            }
        }    
    } 
    
}

?>