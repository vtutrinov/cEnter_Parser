<?php
/**
 * Lock checker interface
 * @author zhulikovatyi
 * @package ulmart.transfer.system
 */
interface ILockChecker {
	
	public function isProcessLocked($lockName);
	
}

?>