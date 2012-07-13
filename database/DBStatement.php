<?php
/**
 * DBStatement
 *
 * @author Slava Tutrinov
 */
class DBStatement extends PDOStatement {

    protected $runtime;

    /**
     * DB object
     * @var DB
     */
    protected $pdo;

    protected function __construct($pdo) {
        $this -> pdo = $pdo;
    }

    public function  execute($input_parameters = null) {
        $start = microtime(true);
        $result = parent::execute($input_parameters);
        $this -> runtime = microtime(true)-$start;
        return $result;
    }

    public function getRuntime() {
        return $this -> runtime;
    }

    public function  fetch($fetchMode = null, $cursor_orientation = null, $cursor_offset = null) {
        if (is_null($fetchMode)) {
            $fetchMode = $this->pdo->getFetchMode();
        }
        return parent::fetch($fetchMode, $cursor_orientation, $cursor_offset);
    }

    public function  fetchAll($fetchMode = null, $column_index = null, $ctor_args = array()) {
        if (is_null($fetchMode)) {
            $fetchMode = $this->pdo->getFetchMode();
        }
        return parent::fetchAll($fetchMode);
    }

}
?>
