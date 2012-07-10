<?php
ini_set("pcre.backtrack_limit", 10000000);//PCRE некорректно работает при длиннах строк более 1000000 байт
$content = file_get_contents("test.html");
preg_match("/thead_main(.*)?thead_dummy/sui", $content, $matches);
if (preg_last_error() == PREG_BACKTRACK_LIMIT_ERROR) {
    print 'Backtrack limit was exhausted!';
}
$ma = $matches[1];

//названия сравниваемых товаров
preg_match_all("/b-compare__model__link\">([^<]*)?</ui", $ma, $goodNames);
var_dump($goodNames);exit;
//echo $ma;
//свойства товаров
preg_match_all("/<tr\sid\=\"([^\"]*)?\".*?l-compare__name__i\">([^<]*).*?<\/tr>/ui", $content, $properties);
$propIds = $properties[1];
$propNames = $properties[2];

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
    var_dump($propValues[1]);
}
?>