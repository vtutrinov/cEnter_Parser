<?php
/**
 * DBManager
 *
 * @author Slava Tutrinov
 */
class DBManager {

    /**
     * @var DBCollection
     */
    protected static $__instances = null;

    protected static $__parameterizedConnectionInitialized = false;

    /**
     * Factory method for create and return requested DB instance
     * @param string $a
     * @param string $h
     * @param string $port
     * @param string $u
     * @param string $pwd
     * @param string $db
     * @return DB
     */
    public static function get($a = 'production', $h = null, $port = null, $u = null, $pwd = null, $db = null) {
        if (is_null(self::$__instances)) {
            self::$__instances = new DBCollection();
        }
        $args = func_get_args();
        $paramsCount = sizeof($args);
        if ($paramsCount == 0 || $paramsCount == 1) {
            $connectionName = (self::$__parameterizedConnectionInitialized)?'custom':'default';
            if (isset(self::$__instances[$connectionName])) {
                return self::$__instances -> getInstanceByName($connectionName);
            }
            $config = new Config(dirname(__FILE__)."/config.ini", $a);
            $config -> parse();
            $dbObj = new DB($config -> database);
            self::$__instances['default'] = $dbObj;
            return self::$__instances -> getInstanceByName('default');
        } elseif ($paramsCount == 2) {
            $connectionName = $a;
            $namespace = $h;
            if (isset(self::$__instances[$connectionName])) {
                return self::$__instances -> getInstanceByName($connectionName);
            }
            $config = new Config(dirname(__FILE__)."/config.ini", $namespace);
            $config -> parse();
            $dbObj = new DB($config -> database -> $connectionName);
            self::$__instances[$connectionName] = $dbObj;
            return self::$__instances -> getInstanceByName($connectionName);
        } elseif ($paramsCount == 6) {
            if (isset(self::$__instances['custom'])) {
                return self::$__instances -> getInstanceByName('custom');
            }
            self::$__parameterizedConnectionInitialized = true;
            $config = new stdClass();
            $config -> adapter = $a;
            $config -> params -> host = $h;
            $config -> params -> port = $port;
            $config -> params -> user = $u;
            $config -> params -> pwd = $pwd;
            $config -> params -> dbname = $db;
            $dbObj = new DB($config);
            self::$__instances['custom'] = $dbObj;
            return self::$__instances ->getInstanceByName('custom');
        } else {
            throw new ErrorException('Неверное количество параметров для инициализации соединения с БД!');
        }
    }

}
?>
