<?php
/**
 * Класс, реализующий установку локов в Redis
 * @author Slava Tutrinov
 * @package ulmart.transfer.system
 */
class RedisLockSetter implements ILockSetter {
    
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
	 * Implementation of the ILockSetter interface
	 * @param string|mixed $lockName
	 */
	public function activateLock($lockName = null) {
            $this->connection->Set($lockName."_lock", true);
	}
	
	/**
	 * Implementation of the ILockSetter interface
	 * @param string|mixed $lockName Имя лока
	 */
	public function lockSetterFail($lockName = null) {
            
	}
	
}

?>