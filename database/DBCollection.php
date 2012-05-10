<?php
/**
 * DBCollection
 *
 * @author Slava Tutrinov
 */
class DBCollection implements ArrayAccess, Iterator, Countable {

    protected $__instances = array();

    public function  __construct($array=null) {
        if (!is_null($array) && is_array($array)) {
            $this -> __instances = $array;
        }
    }
    public function getInstanceByName($name=null) {
            if(!is_null($name)) {
                    if (array_key_exists($name, $this -> __instances)) {
                            return $this -> __instances[$name];
                    } else {
                            throw new Exception("Requested instance of DB not initialized!");
                    }
            } else {
                    throw new Exception("Name of the requested instance of DB can't be null!");
            }
    }

    /**
     * Implementation of the Countable interface
     * @see Countable::count()
     * @return int
     */
    public function count() {
            return count($this -> __instances);
    }

    /**
     * Implementation of the ArrayAccess interface
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset) {
            return isset($this -> __instances[$offset]);
    }

    /**
     *
     * Implementation of the ArrayAccess interface
     * @param mixed $offset
     * @return AbsrtractErpSystem|null
     */
    public function offsetGet($offset) {
            return $this -> offsetExists($offset)?$this -> __instances[$offset]:null;
    }

    /**
     *
     * Implementation of the ArrayAccess interface.
     * @param string|mixed $offset
     * @param AbstractErpSystem $value
     * @return void
     */
    public function offsetSet($offset, $value) {
            if (is_null($offset)) {
                    $this -> __instances[] = $value;
            } else {
                    $this -> __instances[$offset] = $value;
            }
    }

    /**
     * Implementation of the ArrayAccess interface
     * @param string|mixed $offset
     * @return void
     */
    public function offsetUnset($offset) {
            unset($this -> __instances[$offset]);
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::current()
     */
    public function current() {
            return current($this -> __instances);
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::key()
     */
    public function key() {
            return key($this -> __instances);
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::next()
     */
    public function next() {
            return next($this -> __instances);
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::rewind()
     */
    public function rewind() {
            reset($this -> __instances);
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::valid()
     */
    public function valid() {
            $currentKey = key($this -> __instances);
            $isValid = (!is_null($currentKey) && (bool)$currentKey);
            return $isValid;
    }

}
?>
