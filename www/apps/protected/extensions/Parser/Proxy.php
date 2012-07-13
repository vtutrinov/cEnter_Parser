<?php

/**
 * Parser_Proxy
 *
 * @author Slava Tutrinov
 */
class Parser_Proxy {
    
    public static function getProxyList($processCount, $pNum=null) {
        $proxyList = require realpath(dirname(__FILE__).'/../proxies.php');
        if (is_null($pNum)) {
            array_walk($proxyList, function($val, $key) use(&$proxyList){
                $proxyList[$key]['lastRequestTime'] = 0;
            });
            return $proxyList;
        }
        $c = count($proxyList);
        $mod = $c%$processCount;
        $limit = ($c - $mod)/$processCount;
        $offset = $limit*$pNum;
        if ($processCount == $pNum+1) {
            $limit = $limit+$mod;
        }
        $res = array_slice($proxyList, $offset, $limit);
        array_walk($res, function($val, $key) use(&$res) {
            $res[$key]['lastRequestTime'] = 0;
        });
        return $res;
    }
    
    public static function getRandom(&$proxyList, $minStopTime = 0) {
        if (is_array($proxyList)) {
            shuffle($proxyList);
            $tmpTimeDiff = 0;
            $returnProxyKey = null;
            $waitRequire = true;
            foreach ($proxyList as $pKey => $proxy) {
                $timeDiff = time()-$proxy['lastRequestTime'];
                if ($timeDiff > $minStopTime) {
                    $returnProxyKey = $pKey;
                    $waitRequire = false;
                    break;
                } else {
                    if ($timeDiff > $tmpTimeDiff) {
                        $returnProxyKey = $pKey;
                        $tmpTimeDiff = $timeDiff;
                    }
                }
            }
            if ($waitRequire) {
                sleep($minStopTime-$tmpTimeDiff);
            }
            $proxyList[$returnProxyKey]['lastRequestTime'] = time();
            return $proxyList[$returnProxyKey];
        }
        return $proxyList;
    }
    
}

?>
