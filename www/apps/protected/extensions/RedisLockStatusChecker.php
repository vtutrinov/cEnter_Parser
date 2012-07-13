<?php

/**
 * 
 * Класс, реализующий интерфейс проверки статуса лока из Redis
 * @author zhulikovatyi
 * @package ulmart.transfer.system
 */
class RedisLockStatusChecker implements ILockChecker {

    /**
     *
     * @var RedisServer 
     */
    protected $connection = null;

    public function __construct() {
        $this->connection = new RedisServer(REDIS_HOST, REDIS_PORT);
        $this->connection->Select(REDIS_DB_INDEX);
    }

    /**
     * 
     * Implementation of the ILockChecker interface
     * @param string|mixed $lockName
     */
    public function isProcessLocked($lockName) {
        if ($this->connection->Exists($lockName."_lock")) {
            return $this->connection->Get($lockName."_lock");
        } else {
            return false;
        }
    }

}

?>