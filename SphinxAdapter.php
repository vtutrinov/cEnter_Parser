<?php
/**
 * Description of SphinxAdapter
 *
 * @author Slava Tutrinov
 */

class SphinxAdapter {

    public static function getSphinxKeywords($sQuery) {
        $aRequestString=preg_split('/[\s,-]+/', $sQuery, 5);
        $aKeyword = array();
        if ($aRequestString) {
            foreach ($aRequestString as $sValue)
            {
                if (strlen($sValue)>=3)
                {
                    $aKeyword[] .= "(".$sValue." | *".$sValue."*)";
                }
            }
            $sSphinxKeyword = implode(" & ", $aKeyword);
        }
        return $sSphinxKeyword;
    }
    
    public static function escapeString($string) {
        $from = array ( '\\', '(',')','|','-','!','@','~','"','&', '/', '^', '$', '=' );
        $to   = array ( '\\\\', '\(','\)','\|','\-','\!','\@','\~','\"', '\&', '\\/', '\^', '\$', '\=' );

        return str_replace ( $from, $to, $string );
    }

}

?>
