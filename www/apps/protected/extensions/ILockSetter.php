<?php
/**
 * Lock setter interface
 * @author Slava Tutrinov
 * @package ulmart.transfer.system
 */
interface ILockSetter {
	
	public function activateLock($lockName = null);
	public function lockSetterFail($lockName = null);
	
}

?>