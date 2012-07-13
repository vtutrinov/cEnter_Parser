<?php
/**
 * File lock disable behavior
 * @author Slava Tutrinov
 * @package ulmart.transfer.system
 */
class FileDisableLock implements IDisableLock {
	
	/**
	 * 
	 * Implementation of the IDisableLock interface
	 * @param string|mixed $lockName
	 */
	public function disableLock($fhandler) {
		flock($fhandler, LOCK_UN);
		fclose($fhandler);
	}
	
}

?>