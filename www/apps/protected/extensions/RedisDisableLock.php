<?php

/**
 * 
 * Класс, реализующий интерфейс снятия лока из Redis
 * @author Slava Tutrinov
 * @package ulmart.transfer.system
 */
class RedisDisableLock implements IDisableLock {
    
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
     * Implementation of the IDisableLock interface
     * @param string|mixed $lockName
     */
    public function disableLock($lockName) {
        $this->connection->Set($lockName."_lock", false);
    }

}

?>