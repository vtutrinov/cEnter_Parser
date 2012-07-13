<?php
define('BASE_URL', "http://www.enter.ru");
/**
 * Parser_SuperCategory
 *
 * @author Slava Tutrinov
 */
class Parser_SuperCategory {
    
    protected static $proxies = null;
    
    protected static $db = null;
    
    public static function parse($url, $proxies) {
//        echo "Start process with pid ".getmypid().PHP_EOL;
//        self::$proxies = $proxies;
        $randomProxy = Parser_Proxy::getRandom(self::$proxies);
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.7 (KHTML, like Gecko) Chrome/16.0.912.63 Safari/535.7",
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_PROXYUSERPWD => $randomProxy['user'].":".$randomProxy['pass'],
            CURLOPT_PROXY => $randomProxy['ip'].":".$randomProxy['port'],
            CURLOPT_URL => BASE_URL.$url,
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_TIMEOUT => 1000,
        ));
        //sub categories page
        $res = curl_exec($ch);
        while (curl_error($ch)) {
            $res = curl_exec($ch);
        }
        preg_match_all("/class=\"goodsbox.*?\"[\s\S]*?a href=\"(.*)\"/", $res, $matches);
        
        foreach ($matches[1] as $sUrl) {
            $url = BASE_URL.$sUrl;
            $randomProxy = Parser_Proxy::getRandom(self::$proxies);
            curl_setopt($ch, CURLOPT_PROXY, $randomProxy['ip'].":".$randomProxy["port"]);
            curl_setopt($ch, CURLOPT_URL, $url);
            $content = curl_exec($ch);
            while (curl_error($ch)) {
                $content = curl_exec($ch);
            }
            echo chr(27)."[0;33m"."PID: ".  getmypid().". First Level sub-categories".chr(27)."[0m";
            echo PHP_EOL;
            //identify simple goods list
            $isSimplePage = self::isSimplePage($content);
            if ($isSimplePage) {
                self::parseSimpleCategory($content, $ch);
            } else {
                self::parseSubCategoryContent($content, $ch, 2);
            }
        }
        self::$db = null;
    }
    
    private static function parseSubCategoryContent($content, $ch, $level) {
//        echo chr(27)."[0;31m"."PID: ".  getmypid().". Parse sub-category".chr(27)."[0m";
//        echo PHP_EOL;
        $l = $level+1;
        if (preg_match_all("/class=\"bCtg__eL$l\s\"[\s\S]*?a href=\"([\w\/\-_]*)?\">/" ,$content, $matches)) {
//            var_dump($matches[1]);
            foreach ($matches[1] as $url) {
                $proxy = Parser_Proxy::getRandom(self::$proxies);
                curl_setopt($ch, CURLOPT_URL, BASE_URL.$url);
                curl_setopt($ch, CURLOPT_PROXY, $proxy["ip"].":".$proxy["port"]);
                $c = curl_exec($ch);
                while (curl_error($ch)) {
                    $c = curl_exec($ch);
                }
                if (!self::isSimplePage($c)) {
                    self::parseSubCategoryContent($c, $ch, $l);
                }
            }
        } elseif (preg_match_all("/div class=\"photo\">[\s\t\r\n]*?<a href=\"(\/line.+)\">[\s\t\r\n]*?<img/", $content, $lines)) {
            $urls = $lines[1];
            //check pager existing
            if (preg_match("/div class=\"pageslist\"[\s\S]+?(\d+)<\/a>[\sli<>\/]+?<\/ul>/", $content, $lastpage)) {
                $lastPage = intval($lastpage[1]);
                $url = null;
                for ($i = 2; $i <= $lastPage; $i++) {
                    if (is_null($url)) {
                        $curlInfo = curl_getinfo($ch);
                        $url = $curlInfo['url'];
                    }
                    $proxy = Parser_Proxy::getRandom(self::$proxies);
                    curl_setopt($ch, CURLOPT_URL, $url."?page=".$i);
                    curl_setopt($ch, CURLOPT_PROXY, $proxy["ip"].":".$proxy["port"]);
                    $c = curl_exec($ch);
                    while (curl_error($ch)) {
                        $c = curl_exec($ch);
                    }
                    preg_match_all("/div class=\"photo\">[\s\t\r\n]*?<a href=\"(\/line.+)\"/", $c, $l);
                    $urls = array_merge($urls, $l[1]);
                }
            }
            foreach ($urls as $u) {
                self::parseSpecialLine($u, $ch);
            }
        } else {
            self::parseSimpleCategory($content, $ch);
        }
        
    }
    
    private static function parseSimpleCategory($content, $ch) {
//        echo chr(27)."[0;31m"."PID: ".  getmypid().". Parse simple category".chr(27)."[0m";
//        echo PHP_EOL;
        //search all goods link on current page (first page)
        $urls = array();
        $res = preg_match_all("/class=\"goodsbox\"[^\r\n]*?>[\s\t\r\n]*?<div class=\"photo\">[\s\t\r\n]*?<a href=\"(.+)\">[\s\t\r\n]*?<img/", $content, $goods);
        if (!$res) {
            preg_match_all("/class=\"goodsboxlink\"[\s\S]*?>[\s\t\r\n]*?<div class=\"photo\">[\s\t\r\n]*?<a href=\"(.+)\">[\s\t\r\n]*?<img/", $content, $goods);
        }
        array_walk($goods[1], function($value, $key) use (&$urls) {
            $urls[] = BASE_URL.$value;
        });
        //check pager existing
        if (preg_match("/div class=\"pageslist\"[\s\S]+?(\d+)<\/a>[\sli<>\/]+?<\/ul>/", $content, $lastpage)) {
            $lastPage = intval($lastpage[1]);
            $url = null;
            for ($i = 2; $i <= $lastPage; $i++) {
                if (is_null($url)) {
                    $curlInfo = curl_getinfo($ch);
                    $url = $curlInfo['url'];
                }
                $proxy = Parser_Proxy::getRandom(self::$proxies);
                curl_setopt($ch, CURLOPT_URL, $url."?page=".$i);
                curl_setopt($ch, CURLOPT_PROXY, $proxy["ip"].":".$proxy["port"]);
                $c = curl_exec($ch);
                while (curl_error($ch)) {
                    $c = curl_exec($ch);
                }
                $r = preg_match_all("/class=\"goodsbox\"[^\r\n]*?>[\s\t\r\n]*?<div class=\"photo\">[\s\t\r\n]*?<a href=\"(.+)\"/", $c, $l);
                if (!$r) {
                    preg_match_all("/class=\"goodsboxlink\"[\s\S]*?>[\s\t\r\n]*?<div class=\"photo\">[\s\t\r\n]*?<a href=\"(.+)\"/", $c, $l);
                }
                if (sizeof($l) > 0) {
                    $k = array();
                    array_walk($l[1], function($value, $key) use (&$k) {
                        $k[] = BASE_URL.$value;
                    });
                    $urls = array_merge($urls, $k);
                }
            }
        }
//        var_dump($urls);
        self::getGoodsByUrls($urls, $ch);
    }
    
    private static function isSimplePage($content) {
        return ((preg_match("/class=\"pageslist\"/", $content) || preg_match("/class=\"view\"/", $content)) && preg_match("/span=\"price\"/", $content));
    }
    
    private static function parseSpecialLine($url, $ch) {
//        echo chr(27)."[0;32m"."PID: ".  getmypid().". Parse special-line".chr(27)."[0m";
//        echo PHP_EOL;
        $proxy = Parser_Proxy::getRandom(self::$proxies);
        curl_setopt($ch, CURLOPT_URL, BASE_URL.$url);
        curl_setopt($ch, CURLOPT_PROXY, $proxy["ip"].":".$proxy["port"]);
        $result = curl_exec($ch);
        while (curl_error($ch)) {
            $result = curl_exec($ch);
        }
        //serach main series url
        preg_match("/class='bSet__eImage'>[\s\t\r\n]*?<a href=\"(.+)\"\s.*?\>/", $result, $main);
        $urls = array();
        if (sizeof($main) > 0) {
            $urls[] = BASE_URL.$main[1];
        }
        //serach other series url
        preg_match_all("/class=\"goodsbox\"[^\r\n]*?>[\s\t\r\n]*?<div class=\"photo\"[^\r\n]*?>[\s\r\t\n]*?<a href=\"(.*)\">/", $result, $uris);
        $urls = array_merge($urls, $uris[1]);
//        var_dump($urls);
        self::getGoodsByUrls($urls , $ch);
    }
    
    private static function getGoodsByUrls($urls, $ch) {
//        echo chr(27)."[1;33m"."PID: ".  getmypid().". Get goods by urls (".sizeof($urls).") ".chr(27)."[0m";
//        echo PHP_EOL;
        $file = realpath(dirname(__FILE__)."/../urls.txt");
        $stmt = Yii::app()->db->createCommand("INSERT INTO goods (name, articul, description, price, info, features, source) VALUES(:name, :art, :desc, :price, :info, :features, 'enter')");
        foreach ($urls as $url) {
            $proxy = Parser_Proxy::getRandom(self::$proxies);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_PROXY, $proxy["ip"].":".$proxy["port"]);
            $goodPage = curl_exec($ch);
            
            while (curl_error($ch)) {
                $goodPage = curl_exec($ch);
            }
            
            //parse name
            preg_match("/<h1>(.*)<\/h1>/", $goodPage, $matches);
            if (!isset($matches[1])) {
                echo chr(27)."[1;31m"."Damaged url: ".$url.".".chr(27)."[0m".PHP_EOL;
            }
            $name = $matches[1];
            $stmt->bindParam(":name", $name, PDO::PARAM_STR);
            
            //parse articul
            preg_match("/<span>Артикул\s(.*)<\/span>/ui", $goodPage, $matches);
            $art = $matches[1];
            $stmt->bindParam(":art", $art, PDO::PARAM_STR);
            
            //parse description
            $description = "";
            if (preg_match("/<span>Артикул.*?<\/span>[\s\t\r\n]*<\/div>[\s\t\r\n]*<div.*?>([^<]*)?<\/div>/ui", $goodPage, $matches)) {
                $description = trim($matches[1],"\r\n\s");
            }
            $stmt->bindParam(":desc", $description, PDO::PARAM_STR);
            
            //parse price
            preg_match("/class=\"font34\">[\s\S]*?class=\"price\">([\s\d]*)<\/span>/", $goodPage, $priceMatches);
            $price = intval(str_replace(" ", "", $priceMatches[1]));
            $stmt->bindParam(":price", $price, PDO::PARAM_INT);
            
            //parse info
            $info = "";
            if (preg_match("/<h2 class=\"bold\">.*<\/h2>[\s\t\r\n]*<div.*><\/div>[\s\t\r\n]*<ul\sclass=\"pb10\">([^<]*)<\/ul>/", $goodPage, $matches)) {
                $info = trim($matches[1],"\r\n\s");
            }
            $stmt->bindParam(":info", $info, PDO::PARAM_STR);
            
            //parse features
            $res = preg_match("/<div class=\"descriptionlist\">[\s\S]*?<\/div>[\s\r\t\n]*<\/div>[\s\r\t\n]*<\/div>/", $goodPage, $matches);
            $descString = $matches[0];
            $res = preg_match_all("/class=\"title\"><h3>(.*)<\/h3>[\s\S]*?class=\"description\">[\s\r\n\t](.*)<\/div>/", $descString, $descElements);
            $propNames = $descElements[1];
            $propValues = $descElements[2];
            $c = sizeof($propNames);
            $featuresDom = new DOMDocument();
            $featuresDom -> encoding = 'utf-8';
            $featuresNode = $featuresDom -> createElement("features", "");
            for ($i = 0; $i < $c; $i++) {
                $featureNode = $featuresDom -> createElement("feature", "");
                $featureNameNode = $featuresDom -> createElement("name", htmlentities(trim($propNames[$i], " "), ENT_QUOTES, 'UTF-8'));
                $featureValueNode = $featuresDom -> createElement("value", htmlentities(trim($propValues[$i], " "), ENT_QUOTES, 'UTF-8'));
                $featureNode ->appendChild($featureNameNode);
                $featureNode->appendChild($featureValueNode);
                $featuresNode->appendChild($featureNode);
            }
            $featuresDom->appendChild($featuresNode);
            $xml = $featuresDom->saveXML();
            $stmt->bindParam(":features", $xml, PDO::PARAM_STR);
            if (!$stmt->execute()) {
                echo chr(27)."[41m".chr(27)."[1;37mDamage url: ".$url.PHP_EOL;
                var_dump(curl_error($ch));
                echo $name;
                echo chr(27)."[0m";
                continue;
            }
//            echo $art." : ".$name.PHP_EOL;
        }
    }
    
}

?>
