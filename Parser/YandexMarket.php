<?php
require_once 'simplehtmldom/simple_html_dom.php';


/**
 * Parser_YandexMarket
 *
 * @author Slava Tutrinov
 */
class Parser_YandexMarket {
    
    const PROXY_REQUEST_TIMEOUT = 60;
    const BASE_URL = "http://market.yandex.ru";
    const GOODS_PER_PAGE  =1000;
    const COMPARE_GOODS_COUNT = 100;//max value is 100
    
    protected static $ignoredCategories = array(651600, 432460);
    
    public function parse($count, $proxyList, $pid, $iCount) {
        $mod = $iCount%$count;
        $limit = ($iCount-$mod)/$count;
        $start = $pid*$limit;
        if ($pid == ($count-1)) {
            $limit = $limit+$mod;//количество категорий на один поток (если он в конце списка потоков)
        }
        $end = $start+$limit;
        $dom = new DOMDocument('1.0', 'utf-8');
        $successLoad  =false;
        while (!$successLoad) {
            $successLoad = $dom->load('categories.xml');
        }
        $items = $dom->getElementsByTagName("category");
        
        $browsers = include 'browsers.php';
        $browserLength = sizeof($browsers);
        
        $randomBrowserIndex = rand(0, $browserLength);
        $accept = $browsers[$randomBrowserIndex]["accept"];
        $userAgent = $browsers[$randomBrowserIndex]["useragent"];
        
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_TIMEOUT => 1000,
            CURLOPT_HTTPHEADER => array(
                "Cookie: yandexmarket=".self::GOODS_PER_PAGE.",,0,USD,1,,2,0,; yandex_gid=213; yandexuid=327117641265282904; yabs-sid=841336781265282905; fuid01=4ad315691190a495.XIN_5fFf2s_Vkn-NNqiA4TZdhD2N0jy3Fw5xR30sfKWkE_VFQWIYXC_fc9fl1oSywJaA9oRrhDKClVqZNeAvpHgZWYoPehIz7OfpsGFqJV_FprT7Z-vMFk4w2Gw6jCYQ",
                "Accept: ".$accept,
                "Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4",
                "Cache-Control:max-age=0",
                "Connection:keep-alive",
                "User-Agent: ".$userAgent,
            ),
        ));
//        echo $start." ".$end.PHP_EOL;exit;
        
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
        for ($i = $start; $i < $end; $i++) {
            $category = $items->item($i);
            $url = $category->getElementsByTagName("url")->item(0)->nodeValue;
            
            $urlElements = explode("&", $url);//елементы урла категории (в дальнейшем нужны для формирования нового урла с параметром сдвига страницы)
            
            
            $hid = $category->getElementsByTagName("hid")->item(0)->nodeValue;
            $cid = $category->getElementsByTagName("cid")->item(0)->nodeValue;
            
            if (in_array($cid, self::$ignoredCategories)) continue;
            
            $k = 1;
            $categoryNotFinished = true;
            $ids = array();
            $ids['vals'] = array();
            $isGroupCategory = false;
            while ($categoryNotFinished) {//пока в категории есть товары(просматриваем все страницы)
                $proxy = Parser_Proxy::getRandom($proxyList, self::PROXY_REQUEST_TIMEOUT);//берём рандомный прокси из списка, прокси должен "стоят" не меньше времени (в сек.), указанного вторым параметром
                
                $randomBrowserIndex = rand(0, $browserLength);
                $accept = $browsers[$randomBrowserIndex]["accept"];
                $userAgent = $browsers[$randomBrowserIndex]["useragent"];
                
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['user'].":".$proxy['pass']);
                curl_setopt($ch, CURLOPT_PROXY,$proxy['ip'].":".$proxy['port']);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Cookie: yandexmarket=".self::GOODS_PER_PAGE.",,0,USD,1,,2,0,; yandex_gid=213; yandexuid=327117641265282904; yabs-sid=841336781265282905; fuid01=4ad315691190a495.XIN_5fFf2s_Vkn-NNqiA4TZdhD2N0jy3Fw5xR30sfKWkE_VFQWIYXC_fc9fl1oSywJaA9oRrhDKClVqZNeAvpHgZWYoPehIz7OfpsGFqJV_FprT7Z-vMFk4w2Gw6jCYQ",
                    "Accept: ".$accept,
                    "Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4",
                    "Cache-Control:max-age=0",
                    "Connection:keep-alive",
                    "User-Agent: ".$userAgent,
                ));
                $result = curl_exec($ch);
                if ($result) {
                    $res = preg_match_all("/cmp_chbx-input\"[\s\S]*?name=\"([a-zA-Z_]+)?([^_\"]*)?\"/ui", $result, $idsMatches);
                    if ((substr($idsMatches[1][0], -4) == 'grp_') && $k == 1) {
                        $isGroupCategory = true;
                    }
                    if (!$res) {
                        while (!$res) {
                            $proxy = Parser_Proxy::getRandom($proxyList, self::PROXY_REQUEST_TIMEOUT);
                            $randomBrowserIndex = rand(0, $browserLength);
                            $accept = $browsers[$randomBrowserIndex]["accept"];
                            $userAgent = $browsers[$randomBrowserIndex]["useragent"];
                            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['user'].":".$proxy['pass']);
                            curl_setopt($ch, CURLOPT_PROXY,$proxy['ip'].":".$proxy['port']);
                            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                "Cookie: yandexmarket=".self::GOODS_PER_PAGE.",,0,USD,1,,2,0,; yandex_gid=213; yandexuid=327117641265282904; yabs-sid=841336781265282905; fuid01=4ad315691190a495.XIN_5fFf2s_Vkn-NNqiA4TZdhD2N0jy3Fw5xR30sfKWkE_VFQWIYXC_fc9fl1oSywJaA9oRrhDKClVqZNeAvpHgZWYoPehIz7OfpsGFqJV_FprT7Z-vMFk4w2Gw6jCYQ",
                                "Accept: ".$accept,
                                "Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4",
                                "Cache-Control:max-age=0",
                                "Connection:keep-alive",
                                "User-Agent: ".$userAgent,
                            ));
                            $result = curl_exec($ch);
                            $res = preg_match_all("/cmp_chbx-input\"[\s\S]*?name=\"([a-zA-Z_]+)?([^_\"]*)?\"/ui", $result, $idsMatches);
                            echo " -- ".$cid." -- ".PHP_EOL;
                        }
                    }
                    $ids['vals'] = array_merge($ids['vals'], $idsMatches[2]);
                }
                $k++;
                $rk = preg_match("/class=\"b-pager__page\"[\s\S]*?href=\"([^\"]*)\">".$k."</", $result, $pageMatches);
                if ($rk) {
                    $url = self::BASE_URL.$pageMatches[1];
                } else {
                    $categoryNotFinished = false;
                }
            }
            $ids['isGroupCategory'] = $isGroupCategory;
            if (sizeof($ids['vals']) == 0) {
                var_dump($result);
                var_dump(curl_error($ch));
                var_dump(curl_getinfo($ch));
            }
            echo $cid." - ".sizeof($ids['vals']).PHP_EOL;
            self::compareGoods($ids, $cid, $hid, $db, $ch, $proxyList);
        }
    }
    
    private static function parseCompareHtml($content) {
        preg_match("/thead_main(.*)?thead_dummy/sui", $content, $matches);
        $ma = $matches[1];
        //названия сравниваемых товаров
        preg_match_all("/b-compare__model__link\">([^<]*)?</ui", $ma, $goodNames);
        $goodIds = $goodNames[1];
        $result = array();
        foreach ($goodIds as $gName) {
                $result[$gName] = array();
        }
        //echo $ma;
        //свойства товаров
        preg_match_all("/<tr\sid\=\"([^\"]*)?\".*?l-compare__name__i\">([^<]*).*?<\/tr>/ui", $content, $properties);
        $propIds = $properties[1];
        $propNames = $properties[2];

        $m = 0;
        foreach ($properties[0] as $propertyHtml) {
            preg_match_all("/l-compare__model__i\">([^<]*)(.*?<\/div>)/su", $propertyHtml, $propValues);
            foreach ($propValues[1] as $key => $val) {
                if (mb_strlen($val, 'utf-8') == 0) {
                    if (preg_match("/b-compare__yes/sui", $propValues[2][$key])) {
                        $propValues[1][$key] = true;
                    }
                }  elseif ($val == "—") {
                    $propValues[1][$key] = false;
                } elseif ($val == " ") {
                    $propValues[1][$key] = null;
                }
            }
//            $propValues[1];
            $l = 0;
            foreach ($result as $prodName => $product) {
                $result[$prodName][] = array(
                    "pid" => $propIds[$m],
                    "pname" => $propNames[$m],
                    "pvalue" => $propValues[1][$l],
                );
                $l++;
            }
            $m++;
        }
        return $result;
    }

    public static function compareGoods($ids, $cid, $hid, $db, &$ch, &$proxyList) {
        
        $goodInsertQueryStatement = $db->prepare("INSERT into goods (`name`, `articul`, `source`) VALUES(:name, :articul, :source)");
        $propertyInsertQueryStatement = $db->prepare("INSERT IGNORE INTO properties (`group_id`, `name`, `strid`) VALUES(:gid, :name, :strid)");
        $propValueInsertQueryStatement = $db->prepare("INSERT INTO property_values (`prop_id`, `value`, `product_id`) VALUES(:propid, :val, :pid)");
        $propIdQueryStatement =  $db->prepare("SELECT id FROM properties WHERE group_id=:gid AND strid=:stid");
        
        
        $cmdParamName = ($ids['isGroupCategory'])?'CMP_GRP':'CMP';
        $compareXml = ($ids['isGroupCategory'])?'compare_grp.xml':'compare.xml';
        while ((sizeof($ids['vals']) > 0)) {
            if (sizeof($ids['vals']) <= self::COMPARE_GOODS_COUNT) {
                $currentIds = $ids['vals'];
                $ids['vals'] = array();
                echo getmypid().":".sizeof($currentIds).PHP_EOL;
            } else {
                $currentIds = array_splice($ids['vals'], 0, self::COMPARE_GOODS_COUNT);
                echo getmypid().":".$cid." - ".sizeof($currentIds).PHP_EOL;
            }
//                continue;
            $compareUrl = self::BASE_URL."/".$compareXml."/?"."hid=".$hid."&CAT_ID=".$cid."&CMD=-".$cmdParamName."=".implode(",", $currentIds);
            $proxy = Parser_Proxy::getRandom($proxyList, self::PROXY_REQUEST_TIMEOUT);//рандомный прокси с временм простоя больше, чем указан в параметре
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['user'].":".$proxy['pass']);
            curl_setopt($ch, CURLOPT_PROXY,$proxy['ip'].":".$proxy['port']);
            curl_setopt($ch, CURLOPT_URL, trim($compareUrl));
            $compareHtml = curl_exec($ch);
            $info = curl_getinfo($ch);
            if ($info['http_code'] != 200) {
                var_dump($info);
                echo $compareHtml;
            } else {
                $goodsWithDescriptions = self::parseCompareHtml($compareHtml);
                $n = 0;
                foreach ($goodsWithDescriptions as $prodName => $properties) {
                    $articul = $currentIds[$n];
                    $source = "market";
                    $goodInsertQueryStatement->bindParam(":name", $prodName, PDO::PARAM_STR);
                    $goodInsertQueryStatement->bindParam(":articul", $articul, PDO::PARAM_STR);
                    $goodInsertQueryStatement->bindParam(":source", $source, PDO::PARAM_STR);
                    $goodInsertQueryStatement->execute();
                    $gid = intval($db->lastInsertId());
                    foreach ($properties as $property) {
                        $propertyInsertQueryStatement->bindValue(":gid", $cid, PDO::PARAM_INT);
                        $propertyInsertQueryStatement->bindValue(":name", $property["pname"], PDO::PARAM_STR);
                        $propertyInsertQueryStatement->bindValue(":strid", $property["pid"], PDO::PARAM_STR);
                        $propertyInsertQueryStatement->execute();

                        $propId = intval($db->lastInsertId());
                        if ($prodId == 0) {
                            $propIdQueryStatement->bindValue(":gid", $cid, PDO::PARAM_INT);
                            $propIdQueryStatement->bindValue(":stid", $property['pid'], PDO::PARAM_STR);
                            $propIdQueryStatement->execute();
                            $r = $propIdQueryStatement->fetch();
                            $propId = intval($r['id']);
                        }
                        $propValueInsertQueryStatement->bindValue(":propid", $propId, PDO::PARAM_INT);
                        $propValueInsertQueryStatement->bindValue(":val", $property['pvalue'], PDO::PARAM_STR);
                        $propValueInsertQueryStatement->bindValue(":pid", $gid, PDO::PARAM_INT);
                        $propValueInsertQueryStatement->execute();
//                        echo "111111111111111111111111111111111111111111111111111111".PHP_EOL;
                    }
                    $n++;
                }
            }
            echo getmypid()."============================================".date("d-m-Y H:i:s", time()).PHP_EOL;
        }
        echo getmypid()." - end category ".$cid.PHP_EOL;
        return;
    }
    
    public static function packFeatures($totalCount, $threadsCount, $index) {
        $mod = $totalCount%$threadsCount;
        $limit = ($totalCount-$mod)/$threadsCount;
        $start = $index*$limit;
        if ($index == ($threadsCount-1)) {
            $limit = $limit+$mod;//количество категорий на один поток (если он в конце списка потоков)
        }
        $end = $start+$limit;
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
        
        $propertiesQueryStatement = $db->prepare("SELECT p.name n, pv.value v FROM properties AS p JOIN property_values AS pv ON (pv.prop_id=p.id) WHERE pv.product_id=:pid");
        $goodUpdateQueryStatement = $db->prepare("UPDATE goods SET features=:fe WHERE id=:pid");
        
        $sql = "SELECT id FROM goods WHERE source='market' LIMIT :limit OFFSET :offset";
//        echo $sql.PHP_EOL;exit;
        $stm = $db->prepare($sql);
        $stm->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stm->bindParam(":offset", $start, PDO::PARAM_INT);
        $stm->execute();
        while ($row = $stm->fetch()) {
            $propertiesQueryStatement->bindParam(":pid", $row['id'], PDO::PARAM_INT);
            $res = $propertiesQueryStatement->execute();
            $properties = $propertiesQueryStatement->fetchAll();
            $dom = new DOMDocument('1.0', 'utf-8');
            $rootNode = $dom->createElement("features", "");
            foreach ($properties as $property) {
                $propName = $property['n'];
                $propValue = $property['v'];
                $featureNode = $dom->createElement("feature", "");
                $featureNameNode = $dom->createElement("name", $propName);
                $featureValueNode = $dom->createElement("value", $propValue);
                $featureNode->appendChild($featureNameNode);
                $featureNode->appendChild($featureValueNode);
                $rootNode->appendChild($featureNode);
            }
            $dom->appendChild($rootNode);
            $xml = $dom->saveXML();
            $goodUpdateQueryStatement->bindParam(":fe", $xml, PDO::PARAM_STR);
            $goodUpdateQueryStatement->bindParam(":pid", $row['id'], PDO::PARAM_INT);
            $goodUpdateQueryStatement->execute();
        }
        return;
    }
    
    
}

?>
