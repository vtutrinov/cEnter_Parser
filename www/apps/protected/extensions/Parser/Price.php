<?php

/**
 * Parser_Price
 *
 * @author Slava Tutrinov
 */
class Parser_Price {
    
    public static function parse($pNum, $pCount, $iCount) {
        $mod = $iCount%$pCount;//остаток от деления количества товаров в прайсе на количество потоков
        $limit = ($iCount-$mod)/$pCount;//количество товаров из прайса на один поток (если он не в конце списка потоков)
        $start = $pNum*$limit;
        if ($pNum == ($pCount-1)) {
            $limit = $limit+$mod;//количество товаров из прайса на один поток (если он в конце списка потоков)
        }
        $end = $start+$limit;
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->load(Yii::app()->getBasePath()."/data/input.xml");
        $items = $doc->getElementsByTagName("item");
        
        $command = Yii::app()->db->createCommand("INSERT INTO price(`id`, `name`, `shortname`, `rusname`) VALUES(:id, :n, :sn, :rn)");
        for ($i = $start; $i < $end; $i++) {
            $item = $items->item($i);
            $id = $item->getAttribute("id");
            $name = $item->getElementsByTagName("name")->item(0)->nodeValue;
            $sname = $item->getElementsByTagName("shortname")->item(0)->nodeValue;
            $rname = $item->getElementsByTagName("rusname")->item(0)->nodeValue;
            $command->bindParam(":id", $id, PDO::PARAM_INT);
            $command->bindParam(":n", $name, PDO::PARAM_STR);
            $command->bindParam(":sn", $sname, PDO::PARAM_STR);
            $command->bindParam(":rn", $rname, PDO::PARAM_STR);
            $command->execute();
        }
        return;
    }
    
}

?>
