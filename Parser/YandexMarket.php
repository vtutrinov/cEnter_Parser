<?php
require_once 'simplehtmldom/simple_html_dom.php';
/**
 * Parser_YandexMarket
 *
 * @author Slava Tutrinov
 */
class Parser_YandexMarket {
    
    const PROXY_REQUEST_TIMEOUT = 60;
    const BASE_URL = "http://market.yandex.ru/";
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
        $dom->load('categories.xml');
        $items = $dom->getElementsByTagName("category");
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_CONNECTTIMEOUT => 0,
            CURLOPT_TIMEOUT => 1000,
            CURLOPT_HTTPHEADER => array(
                "Cookie: yandexmarket=".self::GOODS_PER_PAGE.",,0,USD,1,,2,0,; yandex_gid=213; yandexuid=327117641265282904; yabs-sid=841336781265282905; fuid01=4ad315691190a495.XIN_5fFf2s_Vkn-NNqiA4TZdhD2N0jy3Fw5xR30sfKWkE_VFQWIYXC_fc9fl1oSywJaA9oRrhDKClVqZNeAvpHgZWYoPehIz7OfpsGFqJV_FprT7Z-vMFk4w2Gw6jCYQ",
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4",
                "Cache-Control:max-age=0",
                "Connection:keep-alive",
                "User-Agent:Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.7 (KHTML, like Gecko) Chrome/16.0.912.63 Safari/535.7",
            ),
        ));
//        echo $start." ".$end.PHP_EOL;exit;
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
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['user'].":".$proxy['pass']);
                curl_setopt($ch, CURLOPT_PROXY,$proxy['ip'].":".$proxy['port']);
                curl_setopt($ch, CURLOPT_URL, $url);
                $result = curl_exec($ch);
                if ($result) {
                    $res = preg_match_all("/cmp_chbx-input\"[\s\S]*?name=\"([a-zA-Z_]+)?([^_\"]*)?\"/ui", $result, $idsMatches);
                    if ((substr($idsMatches[1][0], -4) == 'grp_') && $k == 1) {
                        $isGroupCategory = true;
                    }
                    $ids['vals'] = array_merge($ids['vals'], $idsMatches[2]);
                    echo $cid." - ".$k." - ".sizeof($ids['vals']).PHP_EOL;
                    $categoryNotFinished = false;
                }
                $k++;
                if (preg_match("/class=\"b-pager__page\"\shref=\"([^\"]*)\">".$k."</", $result, $pageMatches)) {
                    $url = $pageMatches [1];
                } else {
                    $categoryNotFinished = false;
                }
            }
            $ids['isGroupCategory'] = $isGroupCategory;
//            echo sizeof($ids['vals']).PHP_EOL;exit;
            $cmdParamName = ($ids['isGroupCategory'])?'CMP_GRP':'CMP';
            $compareXml = ($ids['isGroupCategory'])?'compare_grp.xml':'compare.xml';
            while (sizeof($ids['vals']) > 0) {
                $currentIds = array_splice($ids['vals'], 0, self::COMPARE_GOODS_COUNT);
                $compareUrl = self::BASE_URL.$compareXml."/?"."hid=".$hid."&CAT_ID=".$cid."&CMD=-".$cmdParamName."=".implode(",", $currentIds);
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
                    self::parseCompareHtml($compareHtml);
                }
            }
        }
    }
    
    private static function parseCompareHtml($compareHtml) {
        $html = str_get_html($compareHtml);
        $goodLinks = $html->find("#thead_main", 0)->find(".b-compare__model__link");
        foreach ($goodLinks as $link) {
            echo $link->plaintext.PHP_EOL;
        }
        return;
    }
    
}

?>
