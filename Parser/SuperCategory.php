<?php

/**
 * Parser_SuperCategory
 *
 * @author Slava Tutrinov
 */
class Parser_SuperCategory {
    
    protected static $proxies = null;
    
    public static function parse($url, $proxies) {
        self::$proxies = $proxies;
        $randomProxy = Parser_Proxy::getRandom(self::$proxies);
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_PROXYUSERPWD => $randomProxy['user'].":".$randomProxy['pass'],
            CURLOPT_PROXY => $randomProxy['ip'].":".$randomProxy['port'],
            CURLOPT_URL => BASE_URL.$url,
        ));
        //sub categories page
        $res = curl_exec($ch);
        preg_match_all("/class=\"goodsbox.*?\"[\s\S]*?a href=\"(.*)\"/", $res, $matches);
        foreach ($matches[1] as $sUrl) {
            $url = BASE_URL.$sUrl;
            $randomProxy = Parser_Proxy::getRandom(self::$proxies);
            curl_setopt($ch, CURLOPT_PROXY, $randomProxy['ip'].":".$randomProxy["port"]);
            curl_setopt($ch, CURLOPT_URL, $url);
            $content = curl_exec($ch);
            //identify simple goods list
            $isSimplePage = self::isSimplePage($content);
            if ($isSimplePage) {
                self::parseSimpleCategory($content, $ch);
            } else {
                self::parseSubCategoryContent($content, $ch, 2);
            }
        }
    }
    
    private static function parseSubCategoryContent($content, $ch, $level) {
        $l = $level+1;
        if (preg_match_all("/class=\"bCtg__eL$l\s\"[\s\S]*?a href=\"([\w\/\-_]*)?\">/" ,$content, $matches)) {
//            var_dump($matches[1]);
            foreach ($matches[1] as $url) {
                $proxy = Parser_Proxy::getRandom(self::$proxies);
                curl_setopt($ch, CURLOPT_URL, BASE_URL.$url);
                curl_setopt($ch, CURLOPT_PROXY, $proxy["ip"].":".$proxy["port"]);
                $c = curl_exec($ch);
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
        //search all goods link on current page (first page)
        $urls = array();
        preg_match_all("/class=\"goodsbox\"[^\r\n]*?>[\s\t\r\n]*?<div class=\"photo\">[\s\t\r\n]*?<a href=\"(.+)\">[\s\t\r\n]*?<img/", $content, $goods);
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
                preg_match_all("/class=\"goodsbox\"[^\r\n]*?>[\s\t\r\n]*?<div class=\"photo\">[\s\t\r\n]*?<a href=\"(.+)\"/", $c, $l);
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
        $proxy = Parser_Proxy::getRandom(self::$proxies);
        curl_setopt($ch, CURLOPT_URL, BASE_URL.$url);
        curl_setopt($ch, CURLOPT_PROXY, $proxy["ip"].":".$proxy["port"]);
        $result = curl_exec($ch);
        //serach main series url
        preg_match("/class='bSet__eImage'>[\s\t\r\n]*?<a href=\"(.+)\"\s.*?\>/", $result, $main);
        $urls = array();
        if (sizeof($main) > 0) {
            $urls[] = $main[1];
        }
        //serach other series url
        preg_match_all("/class=\"goodsbox\"[^\r\n]*?>[\s\t\r\n]*?<div class=\"photo\"[^\r\n]*?>[\s\r\t\n]*?<a href=\"(.*)\">/", $result, $uris);
        $urls = array_merge($urls, $uris[1]);
//        var_dump($urls);
        self::getGoodsByUrls($urls , $ch);
    }
    
    private static function getGoodsByUrls($urls, $ch) {
        $file = realpath(dirname(__FILE__)."/../urls.txt");
        $db = DBManager::get('mysql', 'localhost', '3306', 'root', '123', DB);
        $db->exec("SET NAMES utf8; SET CHARACTER SET utf8");
        $stmt = $db ->prepare("INSERT INTO ".DB_TABLE." (name, articul, description, price, info, features) VALUES(:name, :art, :desc, :price, :info, :features)");
        foreach ($urls as $url) {
            $proxy = Parser_Proxy::getRandom(self::$proxies);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_PROXY, $proxy["ip"].":".$proxy["port"]);
            $goodPage = curl_exec($ch);
            
            //parse name
            preg_match("/class=\"pagehead\">[\s\S]*?<div class=\"clear\"><\/div>[\s\t\r\n]*?<h1>(.*)<\/h1>/", $goodPage, $matches);
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
            preg_match("/<div class=\"descriptionlist\">[\s\S]*?<\/div>[\s\r\t\n]*<\/div>[\s\r\t\n]*<\/div>/", $goodPage, $matches);
            $descString = $matches[0];
            preg_match_all("/class=\"title\"><h3>(.*)<\/h3>[\s\S]*?class=\"description\">[\s\r\n\t](.*)<\!\-\-/", $descString, $descElements);
            $propNames = $descElements[1];
            $propValues = $descElements[2];
            $c = sizeof($propNames);
            $featuresDom = new DOMDocument();
            $featuresDom -> encoding = 'UTF-8';
            $featuresNode = $featuresDom -> createElement("features", "");
            for ($i = 0; $i < $c; $i++) {
                $featureNode = $featuresDom -> createElement("feature", "");
                $featureNameNode = $featuresDom -> createElement("name", htmlentities(trim($propNames[$i], " ")));
                $featureValueNode = $featuresDom -> createElement("value", htmlentities(trim($propValues[$i], " ")));
                $featureNode ->appendChild($featureNameNode);
                $featureNode->appendChild($featureValueNode);
                $featuresNode->appendChild($featureNode);
            }
            $featuresDom->appendChild($featuresNode);
            $xml = $featuresDom->saveXML();
            $stmt->bindParam(":features", $xml, PDO::PARAM_STR);
            $stmt->execute();
        }
    }
    
}

?>
