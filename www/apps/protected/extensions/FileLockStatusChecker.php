<?php
/**
 * File lock checker behavior
 * @author zhulikovatyi
 * @package ulmart.transfer.system
 */
class FileLockStatusChecker implements ILockChecker {
	
	/**
	 * 
	 * Implementation of the ILockChecker interface
	 * @param string|mixed $lockName
	 */
	public function isProcessLocked($lockName) {
		$locked = false;
		$fp = fopen(Yii::app() -> getRuntimePath().DIRECTORY_SEPARATOR.$lockName.".lock", "w+");
                if (!flock($fp, LOCK_EX)) {
			$locked = true;
		}
		fclose($fp);
		return $locked;
	}
	
}

?>