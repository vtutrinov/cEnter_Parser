<?php
/**
 * DB
 *
 * @author Slava Tutrinov
 */

define('E_OUTPUT_NONE',    1 << 0);
define('E_OUTPUT_BROWSER', 1 << 1);
define('E_OUTPUT_MEMORY',  1 << 2);

define('D_USERMESSAGE', 1 << 0);
define('D_QUERY',       1 << 1);
define('D_ERRORINFO',   1 << 2);
define('D_CUSTOM',      1 << 3);
define('D_ALL', D_USERMESSAGE|D_QUERY|D_ERRORINFO|D_CUSTOM);


class DB extends PDO {

    const ON_ERROR_EXIT = PDO::ERRMODE_WARNING;
    const ON_ERROR_EXEPTION = PDO::ERRMODE_EXCEPTION;
    const ON_ERROR_SILENT = PDO::ERRMODE_SILENT;

    const ERROR_OUTPUT_NONE    = E_OUTPUT_NONE;
    const ERROR_OUTPUT_BROWSER = E_OUTPUT_BROWSER;
    const ERROR_OUTPUT_MEMORY  = E_OUTPUT_MEMORY;

    const DUMP_USERMESSAGE = D_USERMESSAGE;
    const DUMP_QUERY       = D_QUERY;
    const DUMP_ERRORINFO   = D_ERRORINFO;
    const DUMP_CUSTOM      = D_CUSTOM;
    const DUMP_ALL         = D_ALL;
    
    /**
     * @var Interface_DSNReturnable
     */
    protected $__adapter = null;

    protected $__errorMode = null;

    protected $__isConnectionPersistent = false;

    protected $__errorOutput = self::ERROR_OUTPUT_NONE;

    protected $__dumpMode = self::DUMP_USERMESSAGE;

    protected $__customDumpHandler = null;

    protected $__fetchMode = PDO::FETCH_ASSOC;

    protected $queryString = '';

    protected $runtime = 0;

    public function __construct(stdClass $config) {
        $adapterName = "Adapter_".ucfirst($config -> adapter);
        $this -> __adapter = new $adapterName;
        $dsn = $this -> __adapter -> getDSN($config);
        parent::__construct($dsn, $config -> params -> user, $config -> params -> pwd);
        
        /**
         * set attributes
         */

        //persistent connection mode
        $persistentInConfig = $config -> params -> persistent;
        $persistentMode = ($persistentInConfig === 1 || $persistentInConfig === 0)?(bool)$persistentInConfig:true;
        $this -> __isConnectionPersistent = $persistentMode;
        $this -> setAttribute(PDO::ATTR_PERSISTENT, $persistentMode);

        //query result fetch mode
        $fetchMode = $config -> params -> fetchmode;
        if ((bool)$fetchMode) {
            $fetchModeName = "PDO::FETCH_".strtoupper($fetchMode);
            $this -> __fetchMode = constant($fetchModeName);
        }

        //error handling mode
        $errorMode = $config -> params -> err -> handling;
        if ((bool)$errorMode) {
            $errorModeName = "DB::ON_ERROR_".strtoupper($errorMode);
            $this -> __errorMode = constant($errorModeName);
            $this -> setAttribute(PDO::ATTR_ERRMODE, constant($errorModeName));
        }
        
        //error output mode
        $errorOutputMode = $config -> params -> err -> output;
        if ((bool)$errorOutputMode) {
            $this -> setErrorOutput($errorOutputMode);
        }

        //error dump mode
        $errorDumpMode = $config -> params -> err -> dump;
        if ((bool)$errorDumpMode) {
            $this ->setDumpMode($errorDumpMode, $config -> params -> err -> dumphandler);
        }
        //custom statement handler (DBStatement extends PDOStatement)
        $this ->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('DBStatement', array($this)));

    }

    public function setErrorOutput($errorOutputMode) {
        if (is_string($errorOutputMode)) {
            $errorOutputModeParts = explode("|", $errorOutputMode);
            $r = 0;
            foreach ($errorOutputModeParts as $er) {
                $errorOutputModeName = "DB::ERROR_OUTPUT_".strtoupper($er);
                if (!(bool)constant($errorOutputModeName)) {
                    throw new DBExeption($errorOutputModeName." constant is not specified!");
                }
                if ($r == 0) {
                    $this -> __errorOutput = constant($errorOutputModeName);
                } else {
                    $this -> __errorOutput = $this -> __errorOutput|constant($errorOutputModeName);
                }
                $r++;
            }
            return;
        } elseif (is_integer($errorOutputMode)) {
            $this -> __errorOutput = $errorOutputMode;
        } else {
            throw new DBException("Error output mode should be integer or string format");
        }
        return;
    }

    public function getErrorOutput() {
        return $this -> __errorOutput;
    }

    public function setDumpMode($errorDumpMode, $handler) {
        if (is_string($errorDumpMode)) {
            $dumpModeParts = explode('|', $errorDumpMode);
            $r = 0;
            foreach ($dumpModeParts as $dm) {
                $errorDumpModeName = "DB::DUMP_".strtoupper($dm);
                if (!(bool)constant($errorDumpModeName)) {
                    throw new DBExeption($errorDumpModeName." constant is not specified!");
                }
                if (constant($errorDumpModeName) == self::DUMP_CUSTOM) {
                    $this -> __customDumpHandler = new $handler;
                }
                if (constant($errorDumpModeName) == self::DUMP_ALL) {
                    $this -> __dumpMode = constant($errorDumpModeName);break;
                }
                if ($r == 0) {
                    $this -> __dumpMode = constant($errorDumpModeName);
                } else {
                    $this -> __dumpMode = $this -> __dumpMode|constant($errorDumpModeName);
                }
                $r++;
            }
        } elseif (is_integer($errorDumpMode)) {
            $this -> __dumpMode = $errorDumpMode;
        } else {
            throw new DBException("Dump mode should be integer or string format");
        }
    }

    public function getDumpMode() {
        return $this -> __dumpMode;
    }

    public function getErrorHandlingMode() {
        return $this -> __errorMode;
    }

    public function setDumpHandler(Interface_DumpHandler $dumpHandler) {
        $this -> __customDumpHandler = $dumpHandler;
    }

    public function getDumpHandler() {
        return $this -> __customDumpHandler;
    }

    public function getConnection() {
        return $this -> __connection;
    }

    public function query($statement) {
        $this -> queryString = $statement;
        $start = microtime(true);
        $result = parent::query($statement);
        $this -> runtime = microtime(true)-$start;
        return ($result)?$result:false;
    }

    public function fetch() {
        
    }

    public function getQueryString() {
        return $this -> queryString;
    }

    public function getRuntime() {
        return $this -> runtime;
    }

    public function getFetchMode() {
        return $this -> __fetchMode;
    }

}
?>
