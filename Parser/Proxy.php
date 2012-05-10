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
            return $proxyList;
        }
        $c = count($proxyList);
        $mod = $c%$processCount;
        $limit = ($c - $mod)/$processCount;
        $offset = $limit*$pNum;
        if ($processCount == $pNum+1) {
            $limit = $limit+$mod;
        }
        return array_slice($proxyList, $offset, $limit);
    }
    
    public static function getRandom($proxyList) {
        if (is_array($proxyList)) {
            shuffle($proxyList);
            return $proxyList[0];
        }
        return $proxyList;
    }
    
}

?>
