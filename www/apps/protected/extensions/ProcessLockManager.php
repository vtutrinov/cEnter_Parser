<?php

class ProcessLockManager extends LockManager {
	
	public function __construct() {
		$this -> lockSetterBehavior = new FileLockSetter();
		$this -> lockDisableBehavior = new FileDisableLock();
		$this -> lockCheckerBehavior = new FileLockStatusChecker();
	}
	
	public function setILockSetter(ILockSetter $locker) {
		$this -> lockSetterBehavior = $locker;
	}
	
	public function setIDisableLock(IDisableLock $locker) {
		$this -> lockDisableBehavior = $locker;
	}
	
	public function setILockChecker(ILockChecker $locker) {
		$this -> lockCheckerBehavior = $locker;
	}
	
}

?>