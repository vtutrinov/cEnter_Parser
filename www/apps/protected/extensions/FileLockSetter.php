<?php
/**
 * 
 * File lock setter behavior
 * @author Slava Tutrinov
 * @package ulmart.transfer.system
 */
class FileLockSetter implements ILockSetter {
	
	/**
	 * Implementation of the ILockSetter interface
	 * @param string|mixed $lockName Имя лока
	 */
	public function activateLock($lockName = null) {
		$fileDescriptor = fopen(Yii::app() -> getRuntimePath().DIRECTORY_SEPARATOR.$lockName.".lock", "w+");
		if (!flock($fileDescriptor, LOCK_EX)) {
			return false;
		}
		return $fileDescriptor;
	}
	
	/**
	 * Implementation of the ILockSetter interface
	 * @param string|mixed $lockName Имя лока
	 */
	public function lockSetterFail($lockName = null) {
		$message = (!is_null($lockName) && $lockName != '')?"Process ".$lockName." is locked! Exit ... ":"Process is locked! Exit ... ";
		Yii::app() -> printErrorMessage($message, true);
		exit();
	}
	
}

?>