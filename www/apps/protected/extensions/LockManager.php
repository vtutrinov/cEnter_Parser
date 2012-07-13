<?php

abstract class LockManager {

	/**
	 * Lock setter behavior
	 * @var ILockSetter
	 */
	protected $lockSetterBehavior;
	
	/**
	 * Lock disable behavior
	 * @var IDisableLock
	 */
	protected $lockDisableBehavior;
	
	/**
	 * Lock checker behavior
	 * @var ILockChecker
	 */
	protected $lockCheckerBehavior;
	
	final function setLock($lockName = null) {
		return $this -> lockSetterBehavior -> activateLock($lockName);
	}
	
	final function disableLock($lockName) {
		$this -> lockDisableBehavior -> disableLock($lockName);
	}
	
	final function isProcessLocked($lockName) {
		return $this -> lockCheckerBehavior -> isProcessLocked($lockName);
	}
	
	public function failSetLock($lockName = null) {
		$this -> lockSetterBehavior -> lockSetterFail($lockName);
	}
	
}

?>