<?php
error_reporting(E_ALL^E_NOTICE);
$t = time();
ini_set("pcre.backtrack_limit", 10000000);
define("THREADS_COUNT", 10);
define("DB", "center");
define("DB_TABLE", "goods");
define("DB_USER", "admin");
define("DB_PASS", "refergtmn1988");
define("DB_ADAPTER", "mysql");
define("DB_PORT", '3306');
define("DB_HOST", '192.168.0.100');
require_once dirname(__FILE__).'/Threadi/Loader.php';
require_once 'Loader.php';
spl_autoload_register(array('Loader', 'autoload'));
require_once 'database/ClassLoader.php';
spl_autoload_register(array('ClassLoader', 'autoload'));
$browsers = include 'browsers.php';
$browserLength = sizeof($browsers)-1;
$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_URL => "http://www.bestbuy.com/site/JVC+-+Bluetooth+Adapter+for+Select+JVC+In-Dash+Decks/1931496.p?id=1218301619245&skuId=1931496",
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 1000,
    CURLOPT_CONNECTTIMEOUT => 0,
    CURLOPT_RETURNTRANSFER => true,
));
$i = 0;
while($i < 1000) {
    $i++;
    $randomBrowserIndex = rand(0, $browserLength);
    $accept = $browsers[$randomBrowserIndex]["accept"];
    $userAgent = $browsers[$randomBrowserIndex]["useragent"];
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Cookie: s_vi=[CS]v1|2801F08A851D245F-600001080000237D[CE]; groupab=a; groupab2=a; groupab3=a; groupab4=a; groupab5=a; groupabc=a; groupabc2=a; groupabc3=a; groupabc4=a; groupabcd=c; groupabcde=c; groupabcde2=e; groupab90_10=a; groupab9Way=g; TLTSID=B02DBD00CF2A10CFD165830600E223E0; olspsessid=D48C754197376D674C70EF809684E4E6.bbolsp-app01-70; TLTUID=B02DBD00CF2A10CFD165830600E223E0; myrzsrvy_ci_i=status%3Dinvited_accepted_surveyed; ghp_pcon=tablet; srvy_ci_i=status%3Dinvited_accepted_surveyed; akaau=1342437624~id=d99d1a59c1d3611d42422010e03f251e; mnContext=CarAudio; mnprp=false; mt.v=2.314418052.1342431538694; track={'lastPage':'PDH','page':'Car%2C%20Marine%20%26%20GPS%3A%20Car%20%26%20GPS%20Accessories%3A%20Car%20Accessories%3A%20Bluetooth%20/%20Hands-Free%3A%20pdp','searchLastPage':'Car%2C%20Marine%20%26%20GPS%3A%20Car%20%26%20GPS%20Accessories%3A%20Car%20Accessories%3A%20Bluetooth%20/%20Hands-Free%3A%20pdp','lastCatId':'pdp','campaign':'rmx-pdp%2C1whxm51gbOgORrn6M8JOUlLjDtR0yY7Rx','campaign_date':'1342433177228'}; s_cc=true; s_sq=%5B%5BB%5D%5D; ci_IcsCsid=",
        "Accept: ".$accept,
        "User-Agent: ".$userAgent,
    ));
    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    if ($info["http_code"] != 200) {
        echo $result;
        var_dump($info);exit;
        echo "Bad Request: ".$info['http_code'].PHP_EOL;
    }
}
?>