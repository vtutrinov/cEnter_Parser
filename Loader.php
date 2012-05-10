<?php

/**
 * Loader
 *
 * @author Slava Tutrinov
 */
class Loader {
    
    public static function autoload($className) {
        $path = dirname(__FILE__).'/'.str_replace('_', '/', $className).'.php';

        if (!file_exists($path))
        {
          $vendorPath = dirname(__FILE__).'/vendor/'.$className.'.php';
          if (file_exists($vendorPath)) {
              require_once $vendorPath;
              return;
          }
          return false;
        }
        require_once $path;
    }
    
}

?>
