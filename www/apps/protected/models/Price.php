<?php

/**
 * Price
 *
 * @author Slava Tutrinov
 */
class Price extends CActiveRecord {
    
     public static function model($className = __CLASS__) {
         return parent::model($className);
     }
     
     public function tableName() {
         return 'price';
     }
    
}

?>
